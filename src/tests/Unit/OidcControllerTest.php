<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\AbortException;
use Tests\Fakes\FakeResponse;
use Tests\Fakes\RedirectException;

class OidcControllerTest extends TestCase
{
    private MockObject       $userRepo;
    private MockObject       $identityRepo;
    private MockObject       $providerRepo;
    private MockObject       $provisioner;
    private MockObject       $loginEventNotifier;
    private MockObject       $session;
    private MockObject       $view;
    private FakeResponse     $response;
    private \OidcController  $controller;

    protected function setUp(): void
    {
        $this->userRepo            = $this->createMock(\UserRepositoryInterface::class);
        $this->identityRepo        = $this->createMock(\OidcIdentityRepositoryInterface::class);
        $this->providerRepo        = $this->createMock(\OidcProviderRepositoryInterface::class);
        $this->provisioner         = $this->createMock(\OidcUserProvisioner::class);
        $this->loginEventNotifier  = $this->createMock(\LoginEventNotifier::class);
        $this->session             = $this->createMock(\SessionInterface::class);
        $this->view                = $this->createMock(\ViewInterface::class);
        $this->response            = new FakeResponse();

        $this->controller = new \OidcController(
            $this->userRepo,
            $this->identityRepo,
            $this->providerRepo,
            $this->provisioner,
            $this->loginEventNotifier,
            $this->session,
            $this->view,
            $this->response,
        );
    }

    // -------------------------------------------------------------------------
    // unlinkIdentity()
    // -------------------------------------------------------------------------

    public function test_unlink_identity_aborts_403_for_wrong_guid(): void
    {
        $_POST = ['_csrf' => 'tok'];

        $this->session->method('getUserGuid')->willReturn('other-guid');

        $this->expectException(AbortException::class);
        $this->expectExceptionMessage('HTTP 403');

        $this->controller->unlinkIdentity(new \Request(), 'my-guid', 1);
    }

    public function test_unlink_identity_blocks_removal_of_last_auth_method(): void
    {
        $_POST = ['_csrf' => 'tok'];
        $user = $this->makeUser(userPasswd: null);

        $this->session->method('getUserGuid')->willReturn('my-guid');
        $this->session->method('validateCsrf')->willReturn(true);
        $this->session->method('getUserId')->willReturn(1);
        $this->userRepo->method('findById')->willReturn($user);
        $this->identityRepo->method('countByUser')->willReturn(1);

        $this->identityRepo->expects($this->never())->method('deleteById');
        $this->session->expects($this->once())->method('setFlash')->with('error', $this->anything());

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('/profile/my-guid');

        $this->controller->unlinkIdentity(new \Request(), 'my-guid', 42);
    }

    public function test_unlink_identity_succeeds_when_password_exists(): void
    {
        $_POST = ['_csrf' => 'tok'];
        $user = $this->makeUser(userPasswd: 'hashed');

        $this->session->method('getUserGuid')->willReturn('my-guid');
        $this->session->method('validateCsrf')->willReturn(true);
        $this->session->method('getUserId')->willReturn(1);
        $this->userRepo->method('findById')->willReturn($user);
        $this->identityRepo->method('countByUser')->willReturn(1);

        $this->identityRepo->expects($this->once())->method('deleteById')->with(42, 1);
        $this->session->expects($this->once())->method('setFlash')->with('success', $this->anything());

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('/profile/my-guid');

        $this->controller->unlinkIdentity(new \Request(), 'my-guid', 42);
    }

    public function test_unlink_identity_succeeds_when_multiple_identities_exist(): void
    {
        $_POST = ['_csrf' => 'tok'];
        $user = $this->makeUser(userPasswd: null);

        $this->session->method('getUserGuid')->willReturn('my-guid');
        $this->session->method('validateCsrf')->willReturn(true);
        $this->session->method('getUserId')->willReturn(1);
        $this->userRepo->method('findById')->willReturn($user);
        $this->identityRepo->method('countByUser')->willReturn(2);

        $this->identityRepo->expects($this->once())->method('deleteById')->with(42, 1);
        $this->session->expects($this->once())->method('setFlash')->with('success', $this->anything());

        $this->expectException(RedirectException::class);

        $this->controller->unlinkIdentity(new \Request(), 'my-guid', 42);
    }

    // -------------------------------------------------------------------------
    // callback() — state validation
    // -------------------------------------------------------------------------

    public function test_callback_redirects_with_error_on_state_mismatch(): void
    {
        $_GET = ['state' => 'bad-state', 'code' => 'irrelevant'];

        $this->session->method('getOidcState')->willReturn('expected-state');
        $this->session->expects($this->once())->method('setFlash')->with('error', $this->anything());

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('/login');

        $this->controller->callback(new \Request(), 'google');
    }

    public function test_callback_redirects_with_error_on_provider_error_param(): void
    {
        $_GET = ['state' => 'correct', 'error' => 'access_denied'];

        $this->session->method('getOidcState')->willReturn('correct');
        $this->session->expects($this->once())->method('clearOidcData');
        $this->session->expects($this->once())->method('setFlash')->with('error', $this->anything());

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('/login');

        $this->controller->callback(new \Request(), 'google');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUser(?string $userPasswd = null): \UserDto
    {
        return new \UserDto(
            userId:       1,
            userGuid:     'my-guid',
            userEmail:    'user@example.com',
            userIsNew:    false,
            userIsActive: true,
            userRole:     0,
            userName:     'Test User',
            userPasswd:   $userPasswd,
            userLastLogin: null,
        );
    }
}
