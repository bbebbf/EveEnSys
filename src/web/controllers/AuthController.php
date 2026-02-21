<?php
declare(strict_types=1);

class AuthController
{
    private UserRepository $userRepo;
    private PasswordResetRepository $resetRepo;

    public function __construct(private mysqli $db)
    {
        $this->userRepo  = new UserRepository($db);
        $this->resetRepo = new PasswordResetRepository($db);
    }

    public function showRegister(): void
    {
        Session::requireGuest();
        View::render('auth/register', ['pageTitle' => 'Register', 'errors' => [], 'old' => []]);
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
            $errors['name'] = 'Name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Name must not exceed 100 characters.';
        }

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        } elseif (mb_strlen($email) > 100) {
            $errors['email'] = 'Email must not exceed 100 characters.';
        } elseif ($this->userRepo->findByEmail($email) !== null) {
            $errors['email'] = 'This email address is already registered.';
        }

        if (strlen($pwd) < 8
            || !preg_match('/[A-Z]/', $pwd)
            || !preg_match('/[a-z]/', $pwd)
            || !preg_match('/[0-9]/', $pwd)
        ) {
            $errors['password'] = 'Password must be at least 8 characters and contain uppercase and lowercase letters and at least one number.';
        }

        if ($pwd !== $confirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            View::render('auth/register', [
                'pageTitle' => 'Register',
                'errors'    => $errors,
                'old'       => ['name' => $name, 'email' => $email],
            ]);
            return;
        }

        $hash = password_hash($pwd, PASSWORD_BCRYPT);
        $this->userRepo->create($name, $email, $hash);

        Session::setFlash('success', 'Account created. Please log in.');
        $this->redirect('/login');
    }

    public function showLogin(): void
    {
        Session::requireGuest();
        View::render('auth/login', ['pageTitle' => 'Login', 'errors' => [], 'old' => []]);
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
            $errors['general'] = 'Invalid email or password.';
        } elseif (!$user->userIsActive) {
            $errors['general'] = 'Account is not active.';
        }

        if (!empty($errors)) {
            View::render('auth/login', [
                'pageTitle' => 'Login',
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
        View::render('auth/forgot_password', ['pageTitle' => 'Forgot Password']);
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

            $subject = 'Reset your EveEnSys password';
            $body    = "Hello {$user->userName},\r\n\r\n"
                . "You requested a password reset for your EveEnSys account.\r\n\r\n"
                . "Click the link below to set a new password (valid for 1 hour):\r\n"
                . $link . "\r\n\r\n"
                . "If you did not request this, you can safely ignore this email.\r\n";

            $headers = "From: EveEnSys <noreply@eveensys.local>\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n";

            mail($user->userEmail, $subject, $body, $headers);
        }

        // Always show the same message to prevent user enumeration
        Session::setFlash('success', 'If that email address is registered, you will receive a reset link shortly.');
        $this->redirect('/forgot-password');
    }

    public function showResetPassword(Request $req): void
    {
        $rawToken = $req->get('token', '');
        $record   = $rawToken !== '' ? $this->resetRepo->findValidByToken($rawToken) : null;

        if ($record === null) {
            Session::setFlash('error', 'This password reset link is invalid or has expired. Please request a new one.');
            $this->redirect('/forgot-password');
        }

        View::render('auth/reset_password', [
            'pageTitle' => 'Set New Password',
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
            Session::setFlash('error', 'This password reset link is invalid or has expired. Please request a new one.');
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
            $errors['new_password'] = 'Password must be at least 8 characters and contain uppercase and lowercase letters and at least one number.';
        }

        if ($newPwd !== $confirm) {
            $errors['new_password_confirm'] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            View::render('auth/reset_password', [
                'pageTitle' => 'Set New Password',
                'token'     => $rawToken,
                'errors'    => $errors,
            ]);
            return;
        }

        $this->userRepo->updatePassword((int)$record['user_id'], password_hash($newPwd, PASSWORD_BCRYPT));
        $this->resetRepo->markUsed((int)$record['reset_id']);

        Session::setFlash('success', 'Your password has been reset. You can now log in.');
        $this->redirect('/login');
    }

    public function showProfile(): void
    {
        Session::requireLogin();
        $user = $this->userRepo->findById(Session::getUserId());
        View::render('profile/edit', [
            'pageTitle'     => 'My Profile',
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
            $errors['name'] = 'Name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Name must not exceed 100 characters.';
        }

        if (!empty($errors)) {
            $user = $this->userRepo->findById(Session::getUserId());
            View::render('profile/edit', [
                'pageTitle'  => 'My Profile',
                'user'       => $user,
                'nameErrors' => $errors,
                'pwdErrors'  => [],
            ]);
            return;
        }

        $this->userRepo->updateName(Session::getUserId(), $name);
        Session::setUserName($name);
        Session::setFlash('success', 'Display name updated successfully.');
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
            $errors['current_password'] = 'Current password is incorrect.';
        }

        if (strlen($newPwd) < 8
            || !preg_match('/[A-Z]/', $newPwd)
            || !preg_match('/[a-z]/', $newPwd)
            || !preg_match('/[0-9]/', $newPwd)
        ) {
            $errors['new_password'] = 'Password must be at least 8 characters and contain uppercase and lowercase letters and at least one number.';
        }

        if ($newPwd !== $confirm) {
            $errors['new_password_confirm'] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            View::render('profile/edit', [
                'pageTitle'  => 'My Profile',
                'user'       => $user,
                'nameErrors' => [],
                'pwdErrors'  => $errors,
            ]);
            return;
        }

        $this->userRepo->updatePassword(Session::getUserId(), password_hash($newPwd, PASSWORD_BCRYPT));
        Session::setFlash('success', 'Password changed successfully.');
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
