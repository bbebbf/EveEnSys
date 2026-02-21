<?php
declare(strict_types=1);

class UserDto
{
    public function __construct(
        public readonly int     $userId,
        public readonly string  $userEmail,
        public readonly bool    $userIsActive,
        public readonly int     $userRole,
        public readonly string  $userName,
        public readonly string  $userPasswd,
        public readonly ?string $userLastLogin,
    ) {}
}
