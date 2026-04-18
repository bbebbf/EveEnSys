<?php
declare(strict_types=1);

class AppSession implements SessionInterface
{
    public function isLoggedIn(): bool { return Session::isLoggedIn(); }
    public function isAdmin(): bool    { return Session::isAdmin(); }

    public function requireLogin(): void { Session::requireLogin(); }
    public function requireGuest(): void { Session::requireGuest(); }

    public function login(UserDto $user): void { Session::login($user); }
    public function logout(): void            { Session::logout(); }

    public function getUserId(): ?int      { return Session::getUserId(); }
    public function getUserGuid(): ?string { return Session::getUserGuid(); }
    public function getUserName(): ?string { return Session::getUserName(); }
    public function getUserEmail(): ?string { return Session::getUserEmail(); }
    public function setUserName(string $name): void { Session::setUserName($name); }

    public function validateCsrf(string $token): bool           { return Session::validateCsrf($token); }
    public function setFlash(string $key, string $message): void { Session::setFlash($key, $message); }

    public function setOidcPending(string $nonce, string $state, string $providerKey): void
    {
        Session::setOidcPending($nonce, $state, $providerKey);
    }

    public function setOidcLinkUserId(int $userId): void { Session::setOidcLinkUserId($userId); }
    public function getOidcState(): string                { return Session::getOidcState(); }
    public function getOidcProvider(): string             { return Session::getOidcProvider(); }
    public function getOidcNonce(): string                { return Session::getOidcNonce(); }
    public function getOidcLinkUserId(): ?int             { return Session::getOidcLinkUserId(); }
    public function clearOidcData(): void                 { Session::clearOidcData(); }

    public function setNewOrUpdatedEventGuids(array $guids): void { Session::setNewOrUpdatedEventGuids($guids); }
    public function isEventGuidInNewOrUpdated(string $guid): bool { return Session::isEventGuidInNewOrUpdated($guid); }
    public function removeEventGuidFromNewOrUpdated(string $guid): void { Session::removeEventGuidFromNewOrUpdated($guid); }
}
