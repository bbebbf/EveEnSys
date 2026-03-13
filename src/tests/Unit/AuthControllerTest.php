<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\AbortException;
use Tests\Fakes\FakeResponse;
use Tests\Fakes\RedirectException;

class AuthControllerTest extends TestCase
{
    private MockObject       $userRepo;
    private MockObject       $resetRepo;
    private MockObject       $activationRepo;
    private MockObject       $oidcProviderRepo;
    private MockObject       $eventRepo;
    private MockObject       $oidcIdentityRepo;
    private MockObject       $session;
    private MockObject       $view;
    private FakeResponse     $response;
    private MockObject       $emailSender;
    private \AuthController  $controller;

    protected function setUp(): void
    {
        $this->userRepo         = $this->createMock(\UserRepositoryInterface::class);
        $this->resetRepo        = $this->createMock(\PasswordResetRepositoryInterface::class);
        $this->activationRepo   = $this->createMock(\ActivationTokenRepositoryInterface::class);
        $this->oidcProviderRepo = $this->createMock(\OidcProviderRepositoryInterface::class);
        $this->eventRepo        = $this->createMock(\EventRepositoryInterface::class);
        $this->oidcIdentityRepo = $this->createMock(\OidcIdentityRepositoryInterface::class);
        $this->session          = $this->createMock(\SessionInterface::class);
        $this->view             = $this->createMock(\ViewInterface::class);
        $this->response         = new FakeResponse();
        $this->emailSender      = $this->createMock(\EmailSender::class);

        $this->controller = new \AuthController(
            $this->userRepo,
            $this->resetRepo,
            $this->activationRepo,
            $this->oidcProviderRepo,
            $this->eventRepo,
            $this->oidcIdentityRepo,
            $this->session,
            $this->view,
            $this->response,
            $this->emailSender,
        );
    }

    // -------------------------------------------------------------------------
    // login()
    // -------------------------------------------------------------------------

    public function test_login_logs_in_and_redirects_on_valid_credentials(): void
    {
        $hash = password_hash('Secret1!', PASSWORD_BCRYPT);
        $user = $this->makeUser(userPasswd: $hash, userIsActive: true);

        $_POST = ['_csrf' => 'tok', 'email' => 'user@example.com', 'password' => 'Secret1!'];

        $this->session->method('validateCsrf')->willReturn(true);
        $this->userRepo->method('findByEmail')->with('user@example.com')->willReturn($user);
        $this->session->expects($this->once())->method('login')->with($user);
        $this->userRepo->expects($this->once())->method('updateLastLogin')->with($user->userId);

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('/events');

        $this->controller->login(new \Request());
    }

    public function test_login_renders_error_on_wrong_password(): void
    {
        $hash = password_hash('RightPass1!', PASSWORD_BCRYPT);
        $user = $this->makeUser(userPasswd: $hash, userIsActive: true);

        $_POST = ['_csrf' => 'tok', 'email' => 'user@example.com', 'password' => 'WrongPass1!'];

        $this->session->method('validateCsrf')->willReturn(true);
        $this->userRepo->method('findByEmail')->willReturn($user);
        $this->session->expects($this->never())->method('login');
        $this->view->expects($this->once())->method('render')->with(
            'auth/login',
            $this->callback(fn($d) => isset($d['errors']['general']))
        );

        $this->controller->login(new \Request());
    }

    public function test_login_renders_error_when_account_inactive(): void
    {
        $hash = password_hash('Secret1!', PASSWORD_BCRYPT);
        $user = $this->makeUser(userPasswd: $hash, userIsActive: false);

        $_POST = ['_csrf' => 'tok', 'email' => 'user@example.com', 'password' => 'Secret1!'];

        $this->session->method('validateCsrf')->willReturn(true);
        $this->userRepo->method('findByEmail')->willReturn($user);
        $this->session->expects($this->never())->method('login');
        $this->view->expects($this->once())->method('render')->with(
            'auth/login',
            $this->callback(fn($d) => isset($d['errors']['general']))
        );

        $this->controller->login(new \Request());
    }

    // -------------------------------------------------------------------------
    // register()
    // -------------------------------------------------------------------------

    public function test_register_renders_error_when_email_already_exists(): void
    {
        $_POST = [
            '_csrf'            => 'tok',
            'name'             => 'Alice',
            'email'            => 'alice@example.com',
            'password'         => 'Secret1!',
            'password_confirm' => 'Secret1!',
        ];
        $_SERVER['HTTP_HOST'] = 'localhost';

        $this->session->method('validateCsrf')->willReturn(true);
        $this->userRepo->method('findByEmail')->willReturn($this->makeUser());
        $this->userRepo->expects($this->never())->method('create');
        $this->view->expects($this->once())->method('render')->with(
            'auth/register',
            $this->callback(fn($d) => isset($d['errors']['email']))
        );

        $this->controller->register(new \Request());
    }

    public function test_register_renders_error_when_password_too_weak(): void
    {
        $_POST = [
            '_csrf'            => 'tok',
            'name'             => 'Alice',
            'email'            => 'alice@example.com',
            'password'         => 'weak',
            'password_confirm' => 'weak',
        ];

        $this->session->method('validateCsrf')->willReturn(true);
        $this->userRepo->method('findByEmail')->willReturn(null);
        $this->userRepo->expects($this->never())->method('create');
        $this->view->expects($this->once())->method('render')->with(
            'auth/register',
            $this->callback(fn($d) => isset($d['errors']['password']))
        );

        $this->controller->register(new \Request());
    }

    // -------------------------------------------------------------------------
    // activateAccount()
    // -------------------------------------------------------------------------

    public function test_activate_account_activates_user_and_redirects(): void
    {
        $_GET = ['token' => 'rawtoken'];

        $this->activationRepo->method('findValidByToken')
            ->with('rawtoken')
            ->willReturn(['user_id' => 5, 'token_id' => 11]);
        $this->userRepo->expects($this->once())->method('activate')->with(5);
        $this->activationRepo->expects($this->once())->method('markUsed')->with(11);
        $this->session->expects($this->once())->method('setFlash')->with('success', $this->anything());

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('/login');

        $this->controller->activateAccount(new \Request());
    }

    public function test_activate_account_redirects_with_error_on_invalid_token(): void
    {
        $_GET = ['token' => 'badtoken'];

        $this->activationRepo->method('findValidByToken')->willReturn(null);
        $this->userRepo->expects($this->never())->method('activate');
        $this->session->expects($this->once())->method('setFlash')->with('error', $this->anything());

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('/login');

        $this->controller->activateAccount(new \Request());
    }

    // -------------------------------------------------------------------------
    // deleteProfile()
    // -------------------------------------------------------------------------

    public function test_delete_profile_removes_all_user_data_and_redirects(): void
    {
        $hash = password_hash('Secret1!', PASSWORD_BCRYPT);
        $user = $this->makeUser(userId: 3, userGuid: 'guid-x', userPasswd: $hash, userRole: 0);

        $_POST = ['_csrf' => 'tok', 'password' => 'Secret1!'];

        $this->session->method('validateCsrf')->willReturn(true);
        $this->session->method('getUserGuid')->willReturn('guid-x');
        $this->userRepo->method('findByGuid')->willReturn($user);
        $this->userRepo->method('countAdmins')->willReturn(1);
        $this->userRepo->method('countAll')->willReturn(1);

        $this->eventRepo->expects($this->once())->method('deleteSubscribersForUserEvents')->with(3);
        $this->eventRepo->expects($this->once())->method('deleteSubscribersByCreator')->with(3);
        $this->eventRepo->expects($this->once())->method('deleteAllByUser')->with(3);
        $this->resetRepo->expects($this->once())->method('deleteByUser')->with(3);
        $this->activationRepo->expects($this->once())->method('deleteByUser')->with(3);
        $this->oidcIdentityRepo->expects($this->once())->method('deleteByUser')->with(3);
        $this->userRepo->expects($this->once())->method('delete')->with(3);
        $this->session->expects($this->once())->method('logout');

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('/login');

        $this->controller->deleteProfile(new \Request(), 'guid-x');
    }

    public function test_delete_profile_renders_error_on_wrong_password(): void
    {
        $hash = password_hash('RightPass1!', PASSWORD_BCRYPT);
        $user = $this->makeUser(userId: 3, userGuid: 'guid-x', userPasswd: $hash, userRole: 0);

        $_POST = ['_csrf' => 'tok', 'password' => 'WrongPass1!'];

        $this->session->method('validateCsrf')->willReturn(true);
        $this->session->method('getUserGuid')->willReturn('guid-x');
        $this->userRepo->method('findByGuid')->willReturn($user);
        $this->userRepo->method('countAdmins')->willReturn(1);
        $this->userRepo->method('countAll')->willReturn(1);

        $this->userRepo->expects($this->never())->method('delete');
        $this->view->expects($this->once())->method('render')->with(
            'profile/edit',
            $this->callback(fn($d) => isset($d['deleteErrors']['password']))
        );

        $this->controller->deleteProfile(new \Request(), 'guid-x');
    }

    // -------------------------------------------------------------------------
    // showAdminUsers()
    // -------------------------------------------------------------------------

    public function test_show_admin_users_aborts_403_for_non_admin(): void
    {
        $this->session->method('isAdmin')->willReturn(false);

        $this->expectException(AbortException::class);
        $this->expectExceptionMessage('HTTP 403');

        $this->controller->showAdminUsers();
    }

    public function test_show_admin_users_renders_user_list_for_admin(): void
    {
        $this->session->method('isAdmin')->willReturn(true);
        $this->userRepo->method('findAll')->willReturn([]);
        $this->userRepo->method('countAdmins')->willReturn(1);
        $this->view->expects($this->once())->method('render')->with('admin/users', $this->anything());

        $this->controller->showAdminUsers();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUser(
        int     $userId     = 1,
        string  $userGuid   = 'user-guid',
        string  $userEmail  = 'user@example.com',
        bool    $userIsNew  = false,
        bool    $userIsActive = true,
        int     $userRole   = 0,
        string  $userName   = 'Test User',
        ?string $userPasswd = null,
    ): \UserDto {
        return new \UserDto(
            userId:      $userId,
            userGuid:    $userGuid,
            userEmail:   $userEmail,
            userIsNew:   $userIsNew,
            userIsActive: $userIsActive,
            userRole:    $userRole,
            userName:    $userName,
            userPasswd:  $userPasswd,
            userLastLogin: null,
        );
    }
}
