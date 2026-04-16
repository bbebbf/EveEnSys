<?php
declare(strict_types=1);

class OidcController
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private OidcIdentityRepositoryInterface $identityRepo,
        private OidcProviderRepositoryInterface $providerRepo,
        private OidcUserProvisioner $provisioner,
        private LoginEventNotifier $loginEventNotifier,
        private SessionInterface $session,
        private ViewInterface $view,
        private ResponseInterface $response,
    ) {}

    // -------------------------------------------------------------------------
    // GET /auth/oidc/{providerKey}/login  — start login flow (guest only)
    // GET /auth/oidc/{providerKey}/link   — start link flow  (login required)
    // Both routes call redirect(); the mode is detected from login state.
    // -------------------------------------------------------------------------
    public function redirect(string $providerKey, string $mode): void
    {
        if ($mode === 'link') {
            $this->session->requireLogin();
        } else {
            $this->session->requireGuest();
        }

        $provider  = $this->getProvider($providerKey);
        $discovery = $this->fetchDiscovery($provider->discoveryUrl);

        $nonce = bin2hex(random_bytes(16));
        $state = bin2hex(random_bytes(16));

        $this->session->setOidcPending($nonce, $state, $providerKey);

        if ($mode === 'link') {
            $this->session->setOidcLinkUserId($this->session->getUserId());
        }

        $params = http_build_query([
            'response_type' => 'code',
            'client_id'     => $provider->clientId,
            'redirect_uri'  => $provider->redirectUri,
            'scope'         => implode(' ', $provider->scopes),
            'state'         => $state,
            'nonce'         => $nonce,
            'prompt'        => 'select_account',
        ]);

        $this->response->redirect($discovery['authorization_endpoint'] . '?' . $params);
    }

    // -------------------------------------------------------------------------
    // GET /auth/oidc/{providerKey}/callback
    // -------------------------------------------------------------------------
    public function callback(Request $req, string $providerKey): void
    {
        // --- 1. Validate state ---
        $returnedState = $req->get('state', '');
        $storedState   = $this->session->getOidcState();
        if ($storedState === '' || !hash_equals($storedState, $returnedState)) {
            $this->session->setFlash('error', 'Ungültige Sitzung. Bitte versuche es erneut.');
            $this->response->redirect('/login');
        }

        // --- 2. Handle provider-side errors ---
        $error = $req->get('error', '');
        if ($error !== '') {
            $this->session->clearOidcData();
            $this->session->setFlash('error', 'Anmeldung abgebrochen.');
            $this->response->redirect('/login');
        }

        // --- 3. Confirm provider matches ---
        if ($this->session->getOidcProvider() !== $providerKey) {
            $this->session->clearOidcData();
            $this->session->setFlash('error', 'Anbieter-Konflikt. Bitte erneut versuchen.');
            $this->response->redirect('/login');
        }

        $code = $req->get('code', '');
        if ($code === '') {
            $this->session->clearOidcData();
            $this->session->setFlash('error', 'Kein Autorisierungscode erhalten.');
            $this->response->redirect('/login');
        }

        // --- 4. Exchange code for tokens ---
        $provider      = $this->getProvider($providerKey);
        $discovery     = $this->fetchDiscovery($provider->discoveryUrl);
        $tokenResponse = $this->exchangeCode($discovery['token_endpoint'], $code, $provider);

        if (!isset($tokenResponse['id_token'])) {
            $this->session->clearOidcData();
            $this->session->setFlash('error', 'Kein ID-Token vom Anbieter erhalten.');
            $this->response->redirect('/login');
        }

        // --- 5. Decode and validate ID token ---
        $claims = $this->decodeAndValidateIdToken(
            $tokenResponse['id_token'],
            $provider,
            $discovery['issuer'],
            $this->session->getOidcNonce()
        );

        if ($claims === null) {
            $this->session->clearOidcData();
            $this->session->setFlash('error', 'ID-Token ungültig oder abgelaufen.');
            $this->response->redirect('/login');
        }

        // --- 6. Extract claims ---
        $sub   = (string)($claims['sub'] ?? '');
        $email = strtolower(trim((string)($claims['email'] ?? '')));
        $name  = trim((string)($claims['name'] ?? ($claims['given_name'] ?? '')));

        if ($sub === '' || $email === '') {
            $this->session->clearOidcData();
            $this->session->setFlash('error', 'Der Anbieter hat keine E-Mail-Adresse oder Benutzer-ID zurückgegeben.');
            $this->response->redirect('/login');
        }

        // --- 7. Determine mode ---
        $linkUserId = $this->session->getOidcLinkUserId();
        $this->session->clearOidcData();

        if ($linkUserId !== null) {
            // Link mode: add identity to the already-logged-in user
            $existing = $this->identityRepo->findByProviderSub($provider->providerId, $sub);
            if ($existing !== null && $existing->userId !== $linkUserId) {
                $this->session->setFlash('error', 'Dieser Anbieter-Account ist bereits mit einem anderen Konto verknüpft.');
            } elseif ($existing === null) {
                $this->identityRepo->create($linkUserId, $provider->providerId, $sub);
                $this->session->setFlash('success', 'Anbieter erfolgreich verknüpft.');
            } else {
                $this->session->setFlash('success', 'Anbieter ist bereits verknüpft.');
            }
            $guid = $this->session->getUserGuid();
            $this->response->redirect('/profile/' . $guid);
        }

        // Login mode
        $user = $this->provisioner->findOrProvision($provider->providerId, $sub, $email, $name);

        if ($user === null) {
            $this->session->setFlash('error', 'Konto konnte nicht erstellt oder gefunden werden.');
            $this->response->redirect('/login');
        }

        $this->session->login($user);
        $this->loginEventNotifier->notifyIfNewEventsSince($user->userLastLogin);
        $this->userRepo->updateLastLogin($user->userId);
        $this->response->redirect('/events');
    }

    // -------------------------------------------------------------------------
    // POST /profile/{guid}/oidc/{identityId}/unlink
    // -------------------------------------------------------------------------
    public function unlinkIdentity(Request $req, string $guid, int $identityId): void
    {
        $this->session->requireLogin();
        if ($guid !== $this->session->getUserGuid()) {
            $this->response->abort403();
        }
        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $userId = (int)$this->session->getUserId();
        $user   = $this->userRepo->findById($userId);

        $linkedCount = $this->identityRepo->countByUser($userId);
        $hasPassword = ($user->userPasswd !== null);

        if (!$hasPassword && $linkedCount <= 1) {
            $this->session->setFlash('error', 'Du kannst die letzte Anmeldemethode nicht entfernen.');
            $this->response->redirect('/profile/' . $guid);
        }

        $this->identityRepo->deleteById($identityId, $userId);
        $this->session->setFlash('success', 'Anbieter-Verknüpfung wurde aufgehoben.');
        $this->response->redirect('/profile/' . $guid);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getProvider(string $providerKey): OidcProviderDto
    {
        $provider = $this->providerRepo->findByKey($providerKey);
        if ($provider === null) {
            $this->response->abort404();
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
            $this->session->setFlash('error', 'Anmeldeanbieter nicht erreichbar. Bitte versuche es später erneut.');
            $this->response->redirect('/login');
        }

        $doc = json_decode($body, true);
        if (!is_array($doc)
            || !isset($doc['authorization_endpoint'], $doc['token_endpoint'], $doc['issuer'])
        ) {
            $this->session->setFlash('error', 'Ungültiges Konfigurationsdokument des Anbieters.');
            $this->response->redirect('/login');
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
            $this->session->setFlash('error', 'Token-Austausch fehlgeschlagen. Bitte erneut versuchen.');
            $this->response->redirect('/login');
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

}

