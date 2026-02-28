<?php
declare(strict_types=1);

class OidcController
{
    private UserRepository         $userRepo;
    private OidcIdentityRepository $identityRepo;
    private OidcProviderRepository $providerRepo;

    public function __construct(private mysqli $db)
    {
        $this->userRepo     = new UserRepository($db);
        $this->identityRepo = new OidcIdentityRepository($db);
        $this->providerRepo = new OidcProviderRepository($db);
    }

    // -------------------------------------------------------------------------
    // GET /auth/oidc/{providerKey}/login  — start login flow (guest only)
    // GET /auth/oidc/{providerKey}/link   — start link flow  (login required)
    // Both routes call redirect(); the mode is detected from login state.
    // -------------------------------------------------------------------------
    public function redirect(string $providerKey, string $mode): void
    {
        if ($mode === 'link') {
            Session::requireLogin();
        } else {
            Session::requireGuest();
        }

        $provider  = $this->getProvider($providerKey);
        $discovery = $this->fetchDiscovery($provider->discoveryUrl);

        $nonce = bin2hex(random_bytes(16));
        $state = bin2hex(random_bytes(16));

        Session::setOidcPending($nonce, $state, $providerKey);

        if ($mode === 'link') {
            Session::setOidcLinkUserId(Session::getUserId());
        }

        $params = http_build_query([
            'response_type' => 'code',
            'client_id'     => $provider->clientId,
            'redirect_uri'  => $provider->redirectUri,
            'scope'         => implode(' ', $provider->scopes),
            'state'         => $state,
            'nonce'         => $nonce,
        ]);

        ControllerTools::redirect($discovery['authorization_endpoint'] . '?' . $params);
    }

    // -------------------------------------------------------------------------
    // GET /auth/oidc/{providerKey}/callback
    // -------------------------------------------------------------------------
    public function callback(Request $req, string $providerKey): void
    {
        // --- 1. Validate state ---
        $returnedState = $req->get('state', '');
        $storedState   = Session::getOidcState();
        if ($storedState === '' || !hash_equals($storedState, $returnedState)) {
            Session::setFlash('error', 'Ungültige Sitzung. Bitte versuchen Sie es erneut.');
            ControllerTools::redirect('/login');
        }

        // --- 2. Handle provider-side errors ---
        $error = $req->get('error', '');
        if ($error !== '') {
            Session::clearOidcData();
            Session::setFlash('error', 'Anmeldung abgebrochen.');
            ControllerTools::redirect('/login');
        }

        // --- 3. Confirm provider matches ---
        if (Session::getOidcProvider() !== $providerKey) {
            Session::clearOidcData();
            Session::setFlash('error', 'Anbieter-Konflikt. Bitte erneut versuchen.');
            ControllerTools::redirect('/login');
        }

        $code = $req->get('code', '');
        if ($code === '') {
            Session::clearOidcData();
            Session::setFlash('error', 'Kein Autorisierungscode erhalten.');
            ControllerTools::redirect('/login');
        }

        // --- 4. Exchange code for tokens ---
        $provider      = $this->getProvider($providerKey);
        $discovery     = $this->fetchDiscovery($provider->discoveryUrl);
        $tokenResponse = $this->exchangeCode($discovery['token_endpoint'], $code, $provider);

        if (!isset($tokenResponse['id_token'])) {
            Session::clearOidcData();
            Session::setFlash('error', 'Kein ID-Token vom Anbieter erhalten.');
            ControllerTools::redirect('/login');
        }

        // --- 5. Decode and validate ID token ---
        $claims = $this->decodeAndValidateIdToken(
            $tokenResponse['id_token'],
            $provider,
            $discovery['issuer'],
            Session::getOidcNonce()
        );

        if ($claims === null) {
            Session::clearOidcData();
            Session::setFlash('error', 'ID-Token ungültig oder abgelaufen.');
            ControllerTools::redirect('/login');
        }

        // --- 6. Extract claims ---
        $sub   = (string)($claims['sub'] ?? '');
        $email = strtolower(trim((string)($claims['email'] ?? '')));
        $name  = trim((string)($claims['name'] ?? ($claims['given_name'] ?? '')));

        if ($sub === '' || $email === '') {
            Session::clearOidcData();
            Session::setFlash('error', 'Der Anbieter hat keine E-Mail-Adresse oder Benutzer-ID zurückgegeben.');
            ControllerTools::redirect('/login');
        }

        // --- 7. Determine mode ---
        $linkUserId = Session::getOidcLinkUserId();
        Session::clearOidcData();

        if ($linkUserId !== null) {
            // Link mode: add identity to the already-logged-in user
            $existing = $this->identityRepo->findByProviderSub($provider->providerId, $sub);
            if ($existing !== null && $existing->userId !== $linkUserId) {
                Session::setFlash('error', 'Dieser Anbieter-Account ist bereits mit einem anderen Konto verknüpft.');
            } elseif ($existing === null) {
                $this->identityRepo->create($linkUserId, $provider->providerId, $sub);
                Session::setFlash('success', 'Anbieter erfolgreich verknüpft.');
            } else {
                Session::setFlash('success', 'Anbieter ist bereits verknüpft.');
            }
            $guid = Session::getUserGuid();
            ControllerTools::redirect('/profile/' . $guid);
        }

        // Login mode
        $user = $this->findOrProvisionUser($provider->providerId, $sub, $email, $name);

        if ($user === null) {
            Session::setFlash('error', 'Konto konnte nicht erstellt oder gefunden werden.');
            ControllerTools::redirect('/login');
        }

        Session::login($user);
        $this->userRepo->updateLastLogin($user->userId);
        ControllerTools::redirect('/events');
    }

    // -------------------------------------------------------------------------
    // POST /profile/{guid}/oidc/{identityId}/unlink
    // -------------------------------------------------------------------------
    public function unlinkIdentity(Request $req, string $guid, int $identityId): void
    {
        Session::requireLogin();
        if ($guid !== Session::getUserGuid()) {
            ControllerTools::abort_Forbidden_403();
        }
        if (!Session::validateCsrf($req->post('_csrf', ''))) {
            ControllerTools::abort_Forbidden_403();
        }

        $userId = (int)Session::getUserId();
        $user   = $this->userRepo->findById($userId);

        $linkedCount = $this->identityRepo->countByUser($userId);
        $hasPassword = ($user->userPasswd !== null);

        if (!$hasPassword && $linkedCount <= 1) {
            Session::setFlash('error', 'Sie können die letzte Anmeldemethode nicht entfernen.');
            ControllerTools::redirect('/profile/' . $guid);
        }

        $this->identityRepo->deleteById($identityId, $userId);
        Session::setFlash('success', 'Anbieter-Verknüpfung wurde aufgehoben.');
        ControllerTools::redirect('/profile/' . $guid);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getProvider(string $providerKey): OidcProviderDto
    {
        $provider = $this->providerRepo->findByKey($providerKey);
        if ($provider === null) {
            ControllerTools::abort_NotFound_404();
        }
        return $provider;
    }

    private function fetchDiscovery(string $url): array
    {
        $context = stream_context_create([
            'https' => [
                'method'  => 'GET',
                'header'  => 'Accept: application/json',
                'timeout' => 5,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            error_log('OIDC: failed to fetch discovery from ' . $url);
            Session::setFlash('error', 'Anmeldeanbieter nicht erreichbar. Bitte versuchen Sie es später erneut.');
            ControllerTools::redirect('/login');
        }

        $doc = json_decode($body, true);
        if (!is_array($doc)
            || !isset($doc['authorization_endpoint'], $doc['token_endpoint'], $doc['issuer'])
        ) {
            Session::setFlash('error', 'Ungültiges Konfigurationsdokument des Anbieters.');
            ControllerTools::redirect('/login');
        }

        return $doc;
    }

    private function exchangeCode(string $tokenEndpoint, string $code, OidcProviderDto $provider): array
    {
        $body = http_build_query([
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $provider->redirectUri,
            'client_id'     => $provider->clientId,
            'client_secret' => $provider->clientSecret,
        ]);

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json",
                'content' => $body,
                'timeout' => 10,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $raw = @file_get_contents($tokenEndpoint, false, $context);
        if ($raw === false) {
            error_log('OIDC: token endpoint unreachable: ' . $tokenEndpoint);
            Session::setFlash('error', 'Token-Austausch fehlgeschlagen. Bitte erneut versuchen.');
            ControllerTools::redirect('/login');
        }

        $result = json_decode($raw, true);
        return is_array($result) ? $result : [];
    }

    /**
     * Decodes the JWT ID token payload and validates essential claims.
     * Full signature verification is skipped because the token is received
     * server-to-server over TLS from the token endpoint we called ourselves.
     * We validate: iss, aud, exp (with 60 s clock skew), nonce.
     */
    private function decodeAndValidateIdToken(
        string          $idToken,
        OidcProviderDto $provider,
        string          $expectedIssuer,
        string          $expectedNonce
    ): ?array {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(
            (string)base64_decode(strtr($parts[1], '-_', '+/')),
            true
        );

        if (!is_array($payload)) {
            return null;
        }

        // Validate issuer (trim trailing slash for Azure AD compatibility)
        if (rtrim((string)($payload['iss'] ?? ''), '/') !== rtrim($expectedIssuer, '/')) {
            error_log('OIDC: issuer mismatch: ' . ($payload['iss'] ?? 'none'));
            return null;
        }

        // Validate audience (aud may be string or array per spec)
        $aud = $payload['aud'] ?? null;
        if (is_string($aud)) {
            $aud = [$aud];
        }
        if (!is_array($aud) || !in_array($provider->clientId, $aud, true)) {
            error_log('OIDC: audience mismatch');
            return null;
        }

        // Validate expiry
        if (!isset($payload['exp']) || (int)$payload['exp'] < (time() - 60)) {
            error_log('OIDC: token expired');
            return null;
        }

        // Validate nonce
        if (($payload['nonce'] ?? '') !== $expectedNonce) {
            error_log('OIDC: nonce mismatch');
            return null;
        }

        return $payload;
    }

    /**
     * 1. Known OIDC identity  → return existing user
     * 2. Email matches user   → link + (auto-activate if inactive) + return user
     * 3. New user             → create + link + activate + return user
     */
    private function findOrProvisionUser(
        int    $providerId,
        string $sub,
        string $email,
        string $name
    ): ?UserDto {
        $identity = $this->identityRepo->findByProviderSub($providerId, $sub);
        if ($identity !== null) {
            return $this->userRepo->findById($identity->userId);
        }

        $existing = $this->userRepo->findByEmail($email);
        if ($existing !== null) {
            $this->identityRepo->create($existing->userId, $providerId, $sub);
            if (!$existing->userIsActive) {
                $this->userRepo->activate($existing->userId);
            }
            return $this->userRepo->findById($existing->userId);
        }

        $displayName = $name !== '' ? $name : explode('@', $email)[0];
        $newUserId   = $this->userRepo->createOidc($displayName, $email);
        $this->identityRepo->create($newUserId, $providerId, $sub);
        $this->userRepo->activate($newUserId);

        return $this->userRepo->findById($newUserId);
    }


}
