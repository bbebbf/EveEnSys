<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

// Load app config
$_appConfigFile = dirname(APP_ROOT) . '/_config/app-config.json';
$_appConfig = false;
$_appConfig_valid = false;
if (file_exists($_appConfigFile)) {
    $_appConfig = json_decode(file_get_contents($_appConfigFile), true);
}
$_appConfig_valid = is_array($_appConfig);
define('APP_TITLE_SHORT', $_appConfig_valid && array_key_exists('AppTitleShort', $_appConfig) ? $_appConfig['AppTitleShort'] : 'No App Title');
define('APP_IMPRESS_URL', $_appConfig_valid && array_key_exists('AppImpressUrl', $_appConfig) ? $_appConfig['AppImpressUrl'] : '');
unset($_appConfig);


// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Load infrastructure
require APP_ROOT . '/core/db.php';
require APP_ROOT . '/core/Session.php';
require APP_ROOT . '/core/Request.php';
require APP_ROOT . '/core/Router.php';
require APP_ROOT . '/core/View.php';

// Load DTOs
require APP_ROOT . '/model/dtos/UserDto.php';
require APP_ROOT . '/model/dtos/EventDto.php';
require APP_ROOT . '/model/dtos/SubscriberDto.php';
require APP_ROOT . '/model/dtos/OidcIdentityDto.php';
require APP_ROOT . '/model/dtos/OidcProviderDto.php';
require APP_ROOT . '/model/dtos/OidcProviderInfoDto.php';

// Load repositories
require APP_ROOT . '/model/repositories/UserRepository.php';
require APP_ROOT . '/model/repositories/EventRepository.php';
require APP_ROOT . '/model/repositories/PasswordResetRepository.php';
require APP_ROOT . '/model/repositories/ActivationTokenRepository.php';
require APP_ROOT . '/model/repositories/OidcIdentityRepository.php';
require APP_ROOT . '/model/repositories/OidcProviderRepository.php';

// Load controllers
require APP_ROOT . '/controllers/ControllerTools.php';
require APP_ROOT . '/controllers/AuthController.php';
require APP_ROOT . '/controllers/EventController.php';
require APP_ROOT . '/controllers/OidcController.php';

// Global output escaping helper
function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Format an event date for display (e.g. "22.02.2026 um 14:30 Uhr")
function format_event_date(\DateTimeImmutable $eventDate): string
{
    return $eventDate !== null ? $eventDate->format('d.m. \u\m H:i \U\h\r') : '';
}

Session::start();

$db  = db_connect();
$req = new Request();
$router = new Router($req);

// --- Routes ---
$router->get('/',                      fn() => (new EventController($db))->home());
$router->get('/events',                fn() => (new EventController($db))->index());
$router->get('/events/all',            fn() => (new EventController($db))->indexAll());
$router->get('/events/my',             fn() => (new EventController($db))->indexMy());
$router->get('/events/enrolled',       fn() => (new EventController($db))->indexEnrolled());
$router->get('/events/create',         fn() => (new EventController($db))->showCreate());
$router->post('/events/create',        fn() => (new EventController($db))->create($req));
$router->get('/events/{guid}',           fn($p) => (new EventController($db))->show($p['guid']));
$router->get('/events/{guid}/edit',      fn($p) => (new EventController($db))->showEdit($req, $p['guid']));
$router->post('/events/{guid}/edit',     fn($p) => (new EventController($db))->update($req, $p['guid']));
$router->get('/events/{guid}/delete',    fn($p) => (new EventController($db))->showDelete($p['guid']));
$router->post('/events/{guid}/delete',   fn($p) => (new EventController($db))->delete($req, $p['guid']));
$router->post('/events/{guid}/enroll',             fn($p) => (new EventController($db))->enroll($req, $p['guid']));
$router->post('/events/{guid}/toggle-visible',     fn($p) => (new EventController($db))->toggleVisible($req, $p['guid']));
$router->get('/events/{guid}/unenroll/{subGuid}',    fn($p) => (new EventController($db))->showUnenroll($p['guid'], $p['subGuid']));
$router->post('/events/{guid}/unenroll/{subGuid}',   fn($p) => (new EventController($db))->unenroll($req, $p['guid'], $p['subGuid']));
$router->get('/admin/users',                              fn() => (new AuthController($db))->showAdminUsers());
$router->post('/admin/users/{guid}/toggle-admin',         fn($p) => (new AuthController($db))->toggleAdminRole($req, $p['guid']));
$router->get('/profile/{guid}',                fn($p) => (new AuthController($db))->showProfile($p['guid']));
$router->post('/profile/{guid}/name',          fn($p) => (new AuthController($db))->updateName($req, $p['guid']));
$router->post('/profile/{guid}/password',      fn($p) => (new AuthController($db))->updatePassword($req, $p['guid']));
$router->get('/profile/{guid}/delete',         fn($p) => (new AuthController($db))->showDeleteProfile($p['guid']));
$router->post('/profile/{guid}/delete',        fn($p) => (new AuthController($db))->deleteProfile($req, $p['guid']));
$router->get('/forgot-password',       fn() => (new AuthController($db))->showForgotPassword());
$router->post('/forgot-password',      fn() => (new AuthController($db))->sendPasswordReset($req));
$router->get('/reset-password',        fn() => (new AuthController($db))->showResetPassword($req));
$router->post('/reset-password',       fn() => (new AuthController($db))->resetPassword($req));
$router->get('/register',              fn() => (new AuthController($db))->showRegister());
$router->post('/register',             fn() => (new AuthController($db))->register($req));
$router->get('/activation-sent',       fn() => (new AuthController($db))->showActivationSent());
$router->get('/activate-account',      fn() => (new AuthController($db))->activateAccount($req));
$router->get('/login',                 fn() => (new AuthController($db))->showLogin());
$router->post('/login',                fn() => (new AuthController($db))->login($req));
$router->post('/logout',               fn() => (new AuthController($db))->logout());
$router->get('/auth/oidc/{providerId}/login',    fn($p) => (new OidcController($db))->redirect($p['providerId'], 'login'));
$router->get('/auth/oidc/{providerId}/link',     fn($p) => (new OidcController($db))->redirect($p['providerId'], 'link'));
$router->get('/auth/oidc/{providerId}/callback', fn($p) => (new OidcController($db))->callback($req, $p['providerId']));
$router->post('/profile/{guid}/oidc/{identityId}/unlink',
    fn($p) => (new OidcController($db))->unlinkIdentity($req, $p['guid'], (int)$p['identityId']));

try {
    $router->dispatch();
} catch (mysqli_sql_exception $e) {
    error_log('DB error: ' . $e->getMessage());
    http_response_code(500);
    include APP_ROOT . '/views/errors/500.php';
} catch (Throwable $e) {
    error_log('Unhandled error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    include APP_ROOT . '/views/errors/500.php';
}
