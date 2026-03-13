<?php
declare(strict_types=1);

$srcRoot = dirname(__DIR__) . '/web';

// APP_ROOT is expected by AppConfig to locate the optional app-config.json.
// In tests the config file won't be found, so AppConfig returns safe defaults
// (no delay → getDelayedCurrentDateTime() returns the real current time).
define('APP_ROOT', __DIR__ . '/../web/public');
require $srcRoot . '/core/AppConfig.php';
define('APP_CONFIG', new AppConfig());

// DTOs
require $srcRoot . '/model/dtos/UserDto.php';
require $srcRoot . '/model/dtos/EventDto.php';
require $srcRoot . '/model/dtos/SubscriberDto.php';
require $srcRoot . '/model/dtos/OidcIdentityDto.php';
require $srcRoot . '/model/dtos/OidcProviderDto.php';
require $srcRoot . '/model/dtos/OidcProviderInfoDto.php';

// Repository interfaces
require $srcRoot . '/model/repositories/intf/UserRepositoryInterface.php';
require $srcRoot . '/model/repositories/intf/EventRepositoryInterface.php';
require $srcRoot . '/model/repositories/intf/PasswordResetRepositoryInterface.php';
require $srcRoot . '/model/repositories/intf/ActivationTokenRepositoryInterface.php';
require $srcRoot . '/model/repositories/intf/OidcIdentityRepositoryInterface.php';
require $srcRoot . '/model/repositories/intf/OidcProviderRepositoryInterface.php';

// Service interfaces
require $srcRoot . '/core/SessionInterface.php';
require $srcRoot . '/core/ViewInterface.php';
require $srcRoot . '/core/ResponseInterface.php';

// Request (read-only wrapper around superglobals, no side effects)
require $srcRoot . '/core/Request.php';

// Globals / helpers
require $srcRoot . '/core/Globals.php';

// Tools
require $srcRoot . '/tools/EmailSender.php';

// Controllers
require $srcRoot . '/controllers/ControllerTools.php';
require $srcRoot . '/controllers/AuthController.php';
require $srcRoot . '/controllers/EventController.php';
require $srcRoot . '/controllers/OidcUserProvisioner.php';
require $srcRoot . '/controllers/OidcController.php';

// Test fakes
require __DIR__ . '/Fakes/FakeResponse.php';
