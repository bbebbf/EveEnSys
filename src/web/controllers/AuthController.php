<?php
declare(strict_types=1);

class AuthController
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private PasswordResetRepositoryInterface $resetRepo,
        private ActivationTokenRepositoryInterface $activationRepo,
        private OidcProviderRepositoryInterface $oidcProviderRepo,
        private EventRepositoryInterface $eventRepo,
        private OidcIdentityRepositoryInterface $oidcIdentityRepo,
        private SessionInterface $session,
        private ViewInterface $view,
        private ResponseInterface $response,
    ) {}

    public function showRegister(): void
    {
        $this->session->requireGuest();
        $this->view->render('auth/register', ['pageTitle' => 'Registrieren', 'errors' => [], 'old' => []]);
    }

    public function register(Request $req): void
    {
        $this->session->requireGuest();

        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $name    = trim($req->post('name', ''));
        $email   = trim($req->post('email', ''));
        $pwd     = $req->post('password', '');
        $confirm = $req->post('password_confirm', '');
        $errors  = [];

        if ($name === '') {
            $errors['name'] = 'Name ist erforderlich.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Der Name darf maximal 100 Zeichen lang sein.';
        }

        if ($email === '') {
            $errors['email'] = 'E-Mail-Adresse ist erforderlich.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
        } elseif (mb_strlen($email) > 100) {
            $errors['email'] = 'Die E-Mail-Adresse darf maximal 100 Zeichen lang sein.';
        } elseif ($this->userRepo->findByEmail($email) !== null) {
            $errors['email'] = 'Diese E-Mail-Adresse ist bereits registriert.';
        }

        if (strlen($pwd) < 8
            || !preg_match('/[A-Z]/', $pwd)
            || !preg_match('/[a-z]/', $pwd)
            || !preg_match('/[0-9]/', $pwd)
        ) {
            $errors['password'] = 'Das Passwort muss mindestens 8 Zeichen lang sein und Groß- und Kleinbuchstaben sowie mindestens eine Zahl enthalten.';
        }

        if ($pwd !== $confirm) {
            $errors['password_confirm'] = 'Die Passwörter stimmen nicht überein.';
        }

        if (!empty($errors)) {
            $this->view->render('auth/register', [
                'pageTitle' => 'Registrieren',
                'errors'    => $errors,
                'old'       => ['name' => $name, 'email' => $email],
            ]);
            return;
        }

        $hash   = password_hash($pwd, PASSWORD_BCRYPT);
        $userId = $this->userRepo->create($name, $email, $hash);

        $rawToken = $this->activationRepo->createToken($userId);

        $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
        $link    = $baseUrl . '/activate-account?token=' . urlencode($rawToken);

        $subject = APP_CONFIG->getAppTitleShort() . '-Konto aktivieren';
        $body    = "Hallo {$name},\r\n\r\n"
            . "Vielen Dank für Ihre Registrierung bei " . APP_CONFIG->getAppTitleShort() . ".\r\n\r\n"
            . "Klicken Sie auf den folgenden Link, um Ihr Konto zu aktivieren (gültig für 24 Stunden):\r\n"
            . $link . "\r\n\r\n"
            . "Falls Sie sich nicht registriert haben, können Sie diese E-Mail ignorieren.\r\n";

        $headers = "From: " . $this->getNoReplyAddress() . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n";

        mail($email, $subject, $body, $headers);

        $this->response->redirect('/activation-sent');
    }

    public function showActivationSent(): void
    {
        $this->session->requireGuest();
        $this->view->render('auth/activation_sent', ['pageTitle' => 'Konto aktivieren']);
    }

    public function activateAccount(Request $req): void
    {
        $rawToken = $req->get('token', '');
        $record   = $rawToken !== '' ? $this->activationRepo->findValidByToken($rawToken) : null;

        if ($record === null) {
            $this->session->setFlash('error', 'Dieser Aktivierungslink ist ungültig oder abgelaufen. Bitte registrieren Sie sich erneut.');
            $this->response->redirect('/login');
        }

        $this->userRepo->activate((int)$record['user_id']);
        $this->activationRepo->markUsed((int)$record['token_id']);

        $this->session->setFlash('success', 'Konto aktiviert. Sie können sich jetzt anmelden.');
        $this->response->redirect('/login');
    }

    public function showLogin(): void
    {
        $this->session->requireGuest();
        $this->view->render('auth/login', [
            'pageTitle'         => 'Anmelden',
            'errors'            => [],
            'old'               => [],
            'oidcProviderInfos' => $this->oidcProviderRepo->findAllActiveInfos()
        ]);
    }

    public function login(Request $req): void
    {
        $this->session->requireGuest();

        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $email  = trim($req->post('email', ''));
        $pwd    = $req->post('password', '');
        $errors = [];

        $user = $this->userRepo->findByEmail($email);

        if (!$user || $user->userPasswd === null || !password_verify($pwd, $user->userPasswd)) {
            $errors['general'] = 'Ungültige E-Mail-Adresse oder falsches Passwort.';
        } elseif (!$user->userIsActive) {
            $errors['general'] = 'Das Konto ist nicht aktiv.';
        }

        if (!empty($errors)) {
            $this->view->render('auth/login', [
                'pageTitle' => 'Anmelden',
                'errors'    => $errors,
                'old'       => ['email' => $email],
            ]);
            return;
        }

        $this->session->login($user);
        $this->userRepo->updateLastLogin($user->userId);
        $this->response->redirect('/events');
    }

    public function showForgotPassword(): void
    {
        $this->view->render('auth/forgot_password', ['pageTitle' => 'Passwort vergessen']);
    }

    public function sendPasswordReset(Request $req): void
    {
        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $email = trim($req->post('email', ''));
        $user  = filter_var($email, FILTER_VALIDATE_EMAIL)
            ? $this->userRepo->findByEmail($email)
            : null;

        if ($user !== null && $user->userIsActive) {
            $rawToken = $this->resetRepo->createToken($user->userId);

            $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
            $link    = $baseUrl . '/reset-password?token=' . urlencode($rawToken);

            $subject = APP_CONFIG->getAppTitleShort() . '-Passwort zurücksetzen';
            $body    = "Hallo {$user->userName},\r\n\r\n"
                . "Sie haben eine Passwortzurücksetzung für Ihr " . APP_CONFIG->getAppTitleShort() . "-Konto angefordert.\r\n\r\n"
                . "Klicken Sie auf den folgenden Link, um ein neues Passwort festzulegen (gültig für 1 Stunde):\r\n"
                . $link . "\r\n\r\n"
                . "Falls Sie diese Anfrage nicht gestellt haben, können Sie diese E-Mail ignorieren.\r\n";

            $headers = "From: " . $this->getNoReplyAddress() . "\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n";

            mail($user->userEmail, $subject, $body, $headers);
        }

        // Always show the same message to prevent user enumeration
        $this->session->setFlash('success', 'Falls diese E-Mail-Adresse registriert ist, erhalten Sie in Kürze einen Link zum Zurücksetzen.');
        $this->response->redirect('/forgot-password');
    }

    public function showResetPassword(Request $req): void
    {
        $rawToken = $req->get('token', '');
        $record   = $rawToken !== '' ? $this->resetRepo->findValidByToken($rawToken) : null;

        if ($record === null) {
            $this->session->setFlash('error', 'Dieser Link zum Zurücksetzen des Passworts ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen an.');
            $this->response->redirect('/forgot-password');
        }

        $this->view->render('auth/reset_password', [
            'pageTitle' => 'Neues Passwort festlegen',
            'token'     => $rawToken,
            'errors'    => [],
        ]);
    }

    public function resetPassword(Request $req): void
    {
        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $rawToken = $req->post('token', '');
        $record   = $rawToken !== '' ? $this->resetRepo->findValidByToken($rawToken) : null;

        if ($record === null) {
            $this->session->setFlash('error', 'Dieser Link zum Zurücksetzen des Passworts ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen an.');
            $this->response->redirect('/forgot-password');
        }

        $newPwd  = $req->post('new_password', '');
        $confirm = $req->post('new_password_confirm', '');
        $errors  = [];

        if (strlen($newPwd) < 8
            || !preg_match('/[A-Z]/', $newPwd)
            || !preg_match('/[a-z]/', $newPwd)
            || !preg_match('/[0-9]/', $newPwd)
        ) {
            $errors['new_password'] = 'Das Passwort muss mindestens 8 Zeichen lang sein und Groß- und Kleinbuchstaben sowie mindestens eine Zahl enthalten.';
        }

        if ($newPwd !== $confirm) {
            $errors['new_password_confirm'] = 'Die Passwörter stimmen nicht überein.';
        }

        if (!empty($errors)) {
            $this->view->render('auth/reset_password', [
                'pageTitle' => 'Neues Passwort festlegen',
                'token'     => $rawToken,
                'errors'    => $errors,
            ]);
            return;
        }

        $this->userRepo->updatePassword((int)$record['user_id'], password_hash($newPwd, PASSWORD_BCRYPT));
        $this->resetRepo->markUsed((int)$record['reset_id']);

        $this->session->setFlash('success', 'Ihr Passwort wurde zurückgesetzt. Sie können sich jetzt anmelden.');
        $this->response->redirect('/login');
    }

    public function showProfile(string $guid): void
    {
        $this->session->requireLogin();
        if ($guid !== $this->session->getUserGuid()) {
            $this->response->abort403();
        }
        $this->renderProfileEditPage($guid, [], []);
    }

    public function updateName(Request $req, string $guid): void
    {
        $this->session->requireLogin();
        if ($guid !== $this->session->getUserGuid()) {
            $this->response->abort403();
        }

        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $name   = trim($req->post('name', ''));
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Name ist erforderlich.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Der Name darf maximal 100 Zeichen lang sein.';
        }

        if (!empty($errors)) {
            $this->renderProfileEditPage($guid, $errors, []);
            return;
        }

        $this->userRepo->updateName($this->session->getUserId(), $name);
        $this->session->setUserName($name);
        $this->session->setFlash('success', 'Anzeigename erfolgreich aktualisiert.');
        $this->response->redirect('/profile/' . $guid);
    }

    public function updatePassword(Request $req, string $guid): void
    {
        $this->session->requireLogin();
        if ($guid !== $this->session->getUserGuid()) {
            $this->response->abort403();
        }

        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $currentPwd = $req->post('current_password', '');
        $newPwd     = $req->post('new_password', '');
        $confirm    = $req->post('new_password_confirm', '');
        $errors     = [];

        $user = $this->userRepo->findByGuid($guid);

        if ($user->userPasswd === null) {
            $this->response->abort403();
        }

        if (!password_verify($currentPwd, $user->userPasswd)) {
            $errors['current_password'] = 'Das aktuelle Passwort ist falsch.';
        }

        if (strlen($newPwd) < 8
            || !preg_match('/[A-Z]/', $newPwd)
            || !preg_match('/[a-z]/', $newPwd)
            || !preg_match('/[0-9]/', $newPwd)
        ) {
            $errors['new_password'] = 'Das Passwort muss mindestens 8 Zeichen lang sein und Groß- und Kleinbuchstaben sowie mindestens eine Zahl enthalten.';
        }

        if ($newPwd !== $confirm) {
            $errors['new_password_confirm'] = 'Die Passwörter stimmen nicht überein.';
        }

        if (!empty($errors)) {
            $this->renderProfileEditPage($guid, [], $errors);
            return;
        }

        $this->userRepo->updatePassword($this->session->getUserId(), password_hash($newPwd, PASSWORD_BCRYPT));
        $this->session->setFlash('success', 'Passwort erfolgreich geändert.');
        $this->response->redirect('/profile/' . $guid);
    }

    public function showAdminUsers(): void
    {
        $this->session->requireLogin();
        if (!$this->session->isAdmin()) {
            $this->response->abort403();
        }
        $users            = $this->userRepo->findAll();
        $adminCount       = $this->userRepo->countAdmins();
        $oidcByUser       = $this->oidcIdentityRepo->findAllGroupedByUserId();
        $oidcProviderInfos = $this->oidcProviderRepo->findAllActiveInfos();
        $this->view->render('admin/users', [
            'pageTitle'         => 'Benutzerverwaltung',
            'users'             => $users,
            'adminCount'        => $adminCount,
            'oidcByUser'        => $oidcByUser,
            'oidcProviderInfos' => $oidcProviderInfos,
        ]);
    }

    public function toggleAdminRole(Request $req, string $guid): void
    {
        $this->session->requireLogin();
        if (!$this->session->isAdmin()) {
            $this->response->abort403();
        }
        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $user = $this->userRepo->findByGuid($guid);
        if ($user === null) {
            $this->response->abort404();
        }

        if ($user->userRole >= 1) {
            if ($user->userId === $this->session->getUserId()) {
                $this->session->setFlash('error', 'Sie können sich selbst die Administrator-Rechte nicht entziehen.');
                $this->response->redirect('/admin/users');
            }
            if ($this->userRepo->countAdmins() <= 1) {
                $this->session->setFlash('error', 'Es muss mindestens ein Administrator vorhanden sein.');
                $this->response->redirect('/admin/users');
            }
            $this->userRepo->setRole($user->userId, 0);
            $this->session->setFlash('success', 'Administrator-Rechte von ' . $user->userName . ' wurden entzogen.');
        } else {
            if (!$user->userIsActive) {
                $this->session->setFlash('error', 'Einem inaktiven Benutzer können keine Administrator-Rechte vergeben werden.');
                $this->response->redirect('/admin/users');
            }
            $this->userRepo->setRole($user->userId, 1);
            $this->session->setFlash('success', $user->userName . ' wurde zum Administrator ernannt.');
        }

        $this->response->redirect('/admin/users');
    }

    public function toggleActive(Request $req, string $guid): void
    {
        $this->session->requireLogin();
        if (!$this->session->isAdmin()) {
            $this->response->abort403();
        }
        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $user = $this->userRepo->findByGuid($guid);
        if ($user === null) {
            $this->response->abort404();
        }

        if ($user->userIsActive) {
            if ($user->userId === $this->session->getUserId()) {
                $this->session->setFlash('error', 'Sie können sich selbst nicht deaktivieren.');
                $this->response->redirect('/admin/users');
                return;
            }
            if ($user->userRole >= 1 && $this->userRepo->countAdmins() <= 1) {
                $this->session->setFlash('error', 'Der einzige Administrator kann nicht deaktiviert werden.');
                $this->response->redirect('/admin/users');
                return;
            }
            $this->userRepo->setActive($user->userId, false);
            $this->session->setFlash('success', $user->userName . ' wurde deaktiviert.');
        } else {
            if ($user->userIsNew) {
                $this->session->setFlash('error', 'Neue Benutzer müssen sich über den Aktivierungslink aktivieren.');
                $this->response->redirect('/admin/users');
                return;
            }
            $this->userRepo->setActive($user->userId, true);
            $this->session->setFlash('success', $user->userName . ' wurde reaktiviert.');
        }

        $this->response->redirect('/admin/users');
    }

    public function showDeleteProfile(string $guid): void
    {
        $this->session->requireLogin();
        if ($guid !== $this->session->getUserGuid()) {
            $this->response->abort403();
        }
        $user = $this->userRepo->findByGuid($guid);
        if ($user->userRole >= 1
            && $this->userRepo->countAdmins() === 1
            && $this->userRepo->countAll() > 1
        ) {
            $this->session->setFlash('error', 'Ihr Konto kann nicht gelöscht werden, solange Sie der einzige Administrator sind. Ernennen Sie zuerst einen anderen Administrator.');
            $this->response->redirect('/profile/' . $guid);
        }
        $this->view->render('profile/confirm_delete', [
            'pageTitle' => 'Profil löschen',
            'user'      => $user,
            'errors'    => [],
            'oidcOnly'  => ($user->userPasswd === null),
        ]);
    }

    public function deleteProfile(Request $req, string $guid): void
    {
        $this->session->requireLogin();
        if ($guid !== $this->session->getUserGuid()) {
            $this->response->abort403();
        }

        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $user     = $this->userRepo->findByGuid($guid);
        $password = $req->post('password', '');
        $errors   = [];

        if ($user->userRole >= 1
            && $this->userRepo->countAdmins() === 1
            && $this->userRepo->countAll() > 1
        ) {
            $this->session->setFlash('error', 'Ihr Konto kann nicht gelöscht werden, solange Sie der einzige Administrator sind. Ernennen Sie zuerst einen anderen Administrator.');
            $this->response->redirect('/profile/' . $guid);
        }

        if ($user->userPasswd !== null && !password_verify($password, $user->userPasswd)) {
            $errors['password'] = 'Das Passwort ist falsch.';
        }

        if (!empty($errors)) {
            $this->view->render('profile/confirm_delete', [
                'pageTitle' => 'Profil löschen',
                'user'      => $user,
                'errors'    => $errors,
                'oidcOnly'  => ($user->userPasswd === null),
            ]);
            return;
        }

        $this->eventRepo->deleteSubscribersForUserEvents($user->userId);
        $this->eventRepo->deleteSubscribersByCreator($user->userId);
        $this->eventRepo->deleteAllByUser($user->userId);
        $this->resetRepo->deleteByUser($user->userId);
        $this->activationRepo->deleteByUser($user->userId);
        $this->oidcIdentityRepo->deleteByUser($user->userId);
        $this->userRepo->delete($user->userId);

        $this->session->logout();
        $this->session->setFlash('success', 'Ihr Profil wurde gelöscht.');
        $this->response->redirect('/login');
    }

    public function logout(): void
    {
        $this->session->requireLogin();

        if (!$this->session->validateCsrf($_POST['_csrf'] ?? '')) {
            $this->response->abort403();
        }

        $this->session->logout();
        $this->response->redirect('/login');
    }

    private function getNoReplyAddress(): string
    {
        return APP_CONFIG->getAppTitleShort() . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>';
    }

    private function renderProfileEditPage(string $userGuid, array $nameErrors, array $pwdErrors): void
    {
        $user = $this->userRepo->findByGuid($userGuid);
        $this->view->render('profile/edit', [
            'pageTitle'         => 'Profil',
            'user'              => $user,
            'nameErrors'        => $nameErrors,
            'pwdErrors'         => $pwdErrors,
            'linkedIdentities'  => $this->oidcIdentityRepo->findByUserId($user->userId),
            'oidcProviderInfos' => $this->oidcProviderRepo->findAllActiveInfos(),
        ]);
    }
}
