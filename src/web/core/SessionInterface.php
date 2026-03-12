<?php
declare(strict_types=1);

interface SessionInterface
{
    public function isLoggedIn(): bool;
    public function isAdmin(): bool;

    /** Redirects to /login and exits if the user is not logged in. */
    public function requireLogin(): void;

    /** Redirects to /events and exits if the user is already logged in. */
    public function requireGuest(): void;

    public function login(UserDto $user): void;
    public function logout(): void;

    public function getUserId(): ?int;
    public function getUserGuid(): ?string;
    public function getUserName(): ?string;
    public function getUserEmail(): ?string;
    public function setUserName(string $name): void;

    public function validateCsrf(string $token): bool;
    public function setFlash(string $key, string $message): void;

    // OIDC helpers
    public function setOidcPending(string $nonce, string $state, string $providerKey): void;
    public function setOidcLinkUserId(int $userId): void;
    public function getOidcState(): string;
    public function getOidcProvider(): string;
    public function getOidcNonce(): string;
    public function getOidcLinkUserId(): ?int;
    public function clearOidcData(): void;
}
