<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Load infrastructure
require APP_ROOT . '/core/db.php';
require APP_ROOT . '/core/AppConfig.php';
require APP_ROOT . '/core/AppLogo.php';
require APP_ROOT . '/core/Session.php';
require APP_ROOT . '/core/Request.php';
require APP_ROOT . '/core/Router.php';
require APP_ROOT . '/core/View.php';
require APP_ROOT . '/core/Globals.php';

// Load service interfaces
require APP_ROOT . '/core/SessionInterface.php';
require APP_ROOT . '/core/ViewInterface.php';
require APP_ROOT . '/core/ResponseInterface.php';

// Load service implementations
require APP_ROOT . '/core/AppSession.php';
require APP_ROOT . '/core/AppView.php';
require APP_ROOT . '/core/AppResponse.php';

// Core
require APP_ROOT . '/core/EmailGenerator.php';

// Load DTOs
require APP_ROOT . '/model/dtos/UserDto.php';
require APP_ROOT . '/model/dtos/EventDto.php';
require APP_ROOT . '/model/dtos/SubscriberDto.php';
require APP_ROOT . '/model/dtos/OidcIdentityDto.php';
require APP_ROOT . '/model/dtos/OidcProviderDto.php';
require APP_ROOT . '/model/dtos/OidcProviderInfoDto.php';

// Load repository interfaces
require APP_ROOT . '/model/repositories/intf/UserRepositoryInterface.php';
require APP_ROOT . '/model/repositories/intf/EventRepositoryInterface.php';
require APP_ROOT . '/model/repositories/intf/PasswordResetRepositoryInterface.php';
require APP_ROOT . '/model/repositories/intf/ActivationTokenRepositoryInterface.php';
require APP_ROOT . '/model/repositories/intf/OidcIdentityRepositoryInterface.php';
require APP_ROOT . '/model/repositories/intf/OidcProviderRepositoryInterface.php';

// Load repositories
require APP_ROOT . '/model/repositories/impl/UserRepository.php';
require APP_ROOT . '/model/repositories/impl/EventRepository.php';
require APP_ROOT . '/model/repositories/impl/PasswordResetRepository.php';
require APP_ROOT . '/model/repositories/impl/ActivationTokenRepository.php';
require APP_ROOT . '/model/repositories/impl/OidcIdentityRepository.php';
require APP_ROOT . '/model/repositories/impl/OidcProviderRepository.php';

// Load tools
require APP_ROOT . '/tools/Email.php';
require APP_ROOT . '/tools/EmailSenderInterface.php';
require APP_ROOT . '/tools/EmailSenderPhpMail.php';
require APP_ROOT . '/tools/EmailSenderMailjet.php';
require APP_ROOT . '/tools/FileTools.php';
require APP_ROOT . '/tools/IcsGenerator.php';

// Load controllers
require APP_ROOT . '/controllers/ControllerTools.php';
require APP_ROOT . '/controllers/AuthController.php';
require APP_ROOT . '/controllers/EventController.php';
require APP_ROOT . '/controllers/OidcUserProvisioner.php';
require APP_ROOT . '/controllers/OidcController.php';

Session::start();

// Load app config
$_appConfig = new AppConfig();
define('APP_CONFIG', $_appConfig);
unset($_appConfig);

date_default_timezone_set(APP_CONFIG->getTimezone());

// Connect to DB and initialize router
$db  = db_connect();
$req = new Request();
$router = new Router($req);

// Instantiate repositories
$userRepo         = new UserRepository($db);
$eventRepo        = new EventRepository($db,
    APP_CONFIG->getDelayedStartMinutes(),
    APP_CONFIG->getNewEventsDaysOld(),
    APP_CONFIG->isNewEventApprovalRequired()
    );
$resetRepo        = new PasswordResetRepository($db);
$activationRepo   = new ActivationTokenRepository($db);
$oidcIdentityRepo = new OidcIdentityRepository($db);
$oidcProviderRepo = new OidcProviderRepository($db);

// Instantiate services
$session  = new AppSession();
$view     = new AppView();
$response = new AppResponse();

$emailGenerator    = new EmailGenerator(APP_CONFIG->getEmailSender(), APP_CONFIG->getNotificationFromEmail());

// Instantiate controllers
$authController  = new AuthController($userRepo, $resetRepo, $activationRepo, $oidcProviderRepo, $eventRepo, $oidcIdentityRepo, $session, $view, $response, $emailGenerator);
$eventController = new EventController($eventRepo, $userRepo, $session, $view, $response, $emailGenerator);
$oidcProvisioner = new OidcUserProvisioner($userRepo, $oidcIdentityRepo);
$oidcController  = new OidcController($userRepo, $oidcIdentityRepo, $oidcProviderRepo, $oidcProvisioner, $session, $view, $response);

// --- Routes ---
$router->get('/',                      fn() => $eventController->home());
$router->get('/kiosk',                 fn() => $eventController->kiosk());
$router->get('/events',                fn() => $eventController->index());
$router->get('/events/all',            fn() => $eventController->indexAll());
$router->get('/events/my',             fn() => $eventController->indexMy());
$router->get('/events/new',            fn() => $eventController->indexNew());
$router->get('/events/enrolled',       fn() => $eventController->indexEnrolled());
$router->get('/events/create',         fn() => $eventController->showCreate());
$router->post('/events/create',        fn() => $eventController->create($req));
$router->get('/events/{guid}',           fn($p) => $eventController->show($req, $p['guid']));
$router->get('/events/{guid}/ical',      fn($p) => $eventController->downloadIcal($p['guid']));
$router->get('/events/{guid}/edit',      fn($p) => $eventController->showEdit($req, $p['guid']));
$router->post('/events/{guid}/edit',     fn($p) => $eventController->update($req, $p['guid']));
$router->post('/events/{guid}/delete',   fn($p) => $eventController->delete($req, $p['guid']));
$router->post('/events/{guid}/enroll',             fn($p) => $eventController->enroll($req, $p['guid']));
$router->post('/events/{guid}/toggle-visible',     fn($p) => $eventController->toggleVisible($req, $p['guid']));
$router->post('/events/{guid}/unenroll/{subGuid}',   fn($p) => $eventController->unenroll($req, $p['guid'], $p['subGuid']));
$router->get('/admin/enrollments',                        fn() => $eventController->indexAdminEnrolled());
$router->get('/admin/users',                              fn() => $authController->showAdminUsers());
$router->post('/admin/users/{guid}/toggle-admin',         fn($p) => $authController->toggleAdminRole($req, $p['guid']));
$router->post('/admin/users/{guid}/toggle-active',        fn($p) => $authController->toggleActive($req, $p['guid']));
$router->post('/admin/users/{guid}/delete',               fn($p) => $authController->deleteUserAsAdmin($req, $p['guid']));
$router->get('/profile/{guid}',                fn($p) => $authController->showProfile($p['guid']));
$router->post('/profile/{guid}/name',          fn($p) => $authController->updateName($req, $p['guid']));
$router->post('/profile/{guid}/password',      fn($p) => $authController->updatePassword($req, $p['guid']));
$router->post('/profile/{guid}/delete',        fn($p) => $authController->deleteProfile($req, $p['guid']));
$router->get('/forgot-password',       fn() => $authController->showForgotPassword());
$router->post('/forgot-password',      fn() => $authController->sendPasswordReset($req));
$router->get('/reset-password',        fn() => $authController->showResetPassword($req));
$router->post('/reset-password',       fn() => $authController->resetPassword($req));
$router->get('/register',              fn() => $authController->showRegister());
$router->post('/register',             fn() => $authController->register($req));
$router->get('/activation-sent',       fn() => $authController->showActivationSent());
$router->get('/activate-account',      fn() => $authController->activateAccount($req));
$router->get('/login',                 fn() => $authController->showLogin());
$router->post('/login',                fn() => $authController->login($req));
$router->post('/logout',               fn() => $authController->logout());
$router->get('/auth/oidc/{providerId}/login',    fn($p) => $oidcController->redirect($p['providerId'], 'login'));
$router->get('/auth/oidc/{providerId}/link',     fn($p) => $oidcController->redirect($p['providerId'], 'link'));
$router->get('/auth/oidc/{providerId}/callback', fn($p) => $oidcController->callback($req, $p['providerId']));
$router->post('/profile/{guid}/oidc/{identityId}/unlink',
    fn($p) => $oidcController->unlinkIdentity($req, $p['guid'], (int)$p['identityId']));
$router->get('/privacypolicy',
    fn() => View::render('legal/privacypolicy', ['pageTitle' => 'Datenschutzerklärung']));
$router->get('/about',
    fn() => View::render('legal/about', ['pageTitle' => 'Über EvEnSys']));


try {
    $router->dispatch();
} catch (mysqli_sql_exception $e) {
    error_log('DB error: ' . $e->getMessage());
    ControllerTools::abort_InternalServerError_500();
} catch (Throwable $e) {
    error_log('Unhandled error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    ControllerTools::abort_InternalServerError_500();
}
