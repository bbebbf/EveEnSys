<?php
declare(strict_types=1);

class AuthController
{
    private UserRepository $userRepo;
    private PasswordResetRepository $resetRepo;
    private ActivationTokenRepository $activationRepo;

    public function __construct(private mysqli $db)
    {
        $this->userRepo       = new UserRepository($db);
        $this->resetRepo      = new PasswordResetRepository($db);
        $this->activationRepo = new ActivationTokenRepository($db);
    }

    public function showRegister(): void
    {
        Session::requireGuest();
        View::render('auth/register', ['pageTitle' => 'Registrieren', 'errors' => [], 'old' => []]);
    }

    public function register(Request $req): void
    {
        Session::requireGuest();

        if (!Session::validateCsrf($req->post('_csrf', ''))) {
            http_response_code(403);
            exit('Invalid CSRF token.');
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
            View::render('auth/register', [
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

        $subject = 'EveEnSys-Konto aktivieren';
        $body    = "Hallo {$name},\r\n\r\n"
            . "Vielen Dank für Ihre Registrierung bei EveEnSys.\r\n\r\n"
            . "Klicken Sie auf den folgenden Link, um Ihr Konto zu aktivieren (gültig für 24 Stunden):\r\n"
            . $link . "\r\n\r\n"
            . "Falls Sie sich nicht registriert haben, können Sie diese E-Mail ignorieren.\r\n";

        $headers = "From: EveEnSys <noreply@eveensys.local>\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n";

        mail($email, $subject, $body, $headers);

        $this->redirect('/activation-sent');
    }

    public function showActivationSent(): void
    {
        Session::requireGuest();
        View::render('auth/activation_sent', ['pageTitle' => 'Konto aktivieren']);
    }

    public function activateAccount(Request $req): void
    {
        $rawToken = $req->get('token', '');
        $record   = $rawToken !== '' ? $this->activationRepo->findValidByToken($rawToken) : null;

        if ($record === null) {
            Session::setFlash('error', 'Dieser Aktivierungslink ist ungültig oder abgelaufen. Bitte registrieren Sie sich erneut.');
            $this->redirect('/login');
        }

        $this->userRepo->activate((int)$record['user_id']);
        $this->activationRepo->markUsed((int)$record['token_id']);

        Session::setFlash('success', 'Konto aktiviert. Sie können sich jetzt anmelden.');
        $this->redirect('/login');
    }

    public function showLogin(): void
    {
        Session::requireGuest();
        View::render('auth/login', ['pageTitle' => 'Anmelden', 'errors' => [], 'old' => []]);
    }

    public function login(Request $req): void
    {
        Session::requireGuest();

        if (!Session::validateCsrf($req->post('_csrf', ''))) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        $email  = trim($req->post('email', ''));
        $pwd    = $req->post('password', '');
        $errors = [];

        $user = $this->userRepo->findByEmail($email);

        if (!$user || !password_verify($pwd, $user->userPasswd)) {
            $errors['general'] = 'Ungültige E-Mail-Adresse oder falsches Passwort.';
        } elseif (!$user->userIsActive) {
            $errors['general'] = 'Das Konto ist nicht aktiv.';
        }

        if (!empty($errors)) {
            View::render('auth/login', [
                'pageTitle' => 'Anmelden',
                'errors'    => $errors,
                'old'       => ['email' => $email],
            ]);
            return;
        }

        Session::login($user);
        $this->userRepo->updateLastLogin($user->userId);
        $this->redirect('/events');
    }

    public function showForgotPassword(): void
    {
        View::render('auth/forgot_password', ['pageTitle' => 'Passwort vergessen']);
    }

    public function sendPasswordReset(Request $req): void
    {
        if (!Session::validateCsrf($req->post('_csrf', ''))) {
            http_response_code(403);
            exit('Invalid CSRF token.');
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

            $subject = 'EveEnSys-Passwort zurücksetzen';
            $body    = "Hallo {$user->userName},\r\n\r\n"
                . "Sie haben eine Passwortzurücksetzung für Ihr EveEnSys-Konto angefordert.\r\n\r\n"
                . "Klicken Sie auf den folgenden Link, um ein neues Passwort festzulegen (gültig für 1 Stunde):\r\n"
                . $link . "\r\n\r\n"
                . "Falls Sie diese Anfrage nicht gestellt haben, können Sie diese E-Mail ignorieren.\r\n";

            $headers = "From: EveEnSys <noreply@eveensys.local>\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n";

            mail($user->userEmail, $subject, $body, $headers);
        }

        // Always show the same message to prevent user enumeration
        Session::setFlash('success', 'Falls diese E-Mail-Adresse registriert ist, erhalten Sie in Kürze einen Link zum Zurücksetzen.');
        $this->redirect('/forgot-password');
    }

    public function showResetPassword(Request $req): void
    {
        $rawToken = $req->get('token', '');
        $record   = $rawToken !== '' ? $this->resetRepo->findValidByToken($rawToken) : null;

        if ($record === null) {
            Session::setFlash('error', 'Dieser Link zum Zurücksetzen des Passworts ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen an.');
            $this->redirect('/forgot-password');
        }

        View::render('auth/reset_password', [
            'pageTitle' => 'Neues Passwort festlegen',
            'token'     => $rawToken,
            'errors'    => [],
        ]);
    }

    public function resetPassword(Request $req): void
    {
        if (!Session::validateCsrf($req->post('_csrf', ''))) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        $rawToken = $req->post('token', '');
        $record   = $rawToken !== '' ? $this->resetRepo->findValidByToken($rawToken) : null;

        if ($record === null) {
            Session::setFlash('error', 'Dieser Link zum Zurücksetzen des Passworts ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen an.');
            $this->redirect('/forgot-password');
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
            View::render('auth/reset_password', [
                'pageTitle' => 'Neues Passwort festlegen',
                'token'     => $rawToken,
                'errors'    => $errors,
            ]);
            return;
        }

        $this->userRepo->updatePassword((int)$record['user_id'], password_hash($newPwd, PASSWORD_BCRYPT));
        $this->resetRepo->markUsed((int)$record['reset_id']);

        Session::setFlash('success', 'Ihr Passwort wurde zurückgesetzt. Sie können sich jetzt anmelden.');
        $this->redirect('/login');
    }

    public function showProfile(): void
    {
        Session::requireLogin();
        $user = $this->userRepo->findById(Session::getUserId());
        View::render('profile/edit', [
            'pageTitle'     => 'Mein Profil',
            'user'          => $user,
            'nameErrors'    => [],
            'pwdErrors'     => [],
        ]);
    }

    public function updateName(Request $req): void
    {
        Session::requireLogin();

        if (!Session::validateCsrf($req->post('_csrf', ''))) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        $name   = trim($req->post('name', ''));
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Name ist erforderlich.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Der Name darf maximal 100 Zeichen lang sein.';
        }

        if (!empty($errors)) {
            $user = $this->userRepo->findById(Session::getUserId());
            View::render('profile/edit', [
                'pageTitle'  => 'Mein Profil',
                'user'       => $user,
                'nameErrors' => $errors,
                'pwdErrors'  => [],
            ]);
            return;
        }

        $this->userRepo->updateName(Session::getUserId(), $name);
        Session::setUserName($name);
        Session::setFlash('success', 'Anzeigename erfolgreich aktualisiert.');
        $this->redirect('/profile');
    }

    public function updatePassword(Request $req): void
    {
        Session::requireLogin();

        if (!Session::validateCsrf($req->post('_csrf', ''))) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        $currentPwd = $req->post('current_password', '');
        $newPwd     = $req->post('new_password', '');
        $confirm    = $req->post('new_password_confirm', '');
        $errors     = [];

        $user = $this->userRepo->findById(Session::getUserId());

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
            View::render('profile/edit', [
                'pageTitle'  => 'Mein Profil',
                'user'       => $user,
                'nameErrors' => [],
                'pwdErrors'  => $errors,
            ]);
            return;
        }

        $this->userRepo->updatePassword(Session::getUserId(), password_hash($newPwd, PASSWORD_BCRYPT));
        Session::setFlash('success', 'Passwort erfolgreich geändert.');
        $this->redirect('/profile');
    }

    public function logout(): void
    {
        Session::requireLogin();

        if (!Session::validateCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        Session::logout();
        $this->redirect('/login');
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }
}
