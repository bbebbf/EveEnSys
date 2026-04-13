<?php
declare(strict_types=1);

class UserDto
{
    public function __construct(
        public readonly int     $userId,
        public readonly string  $userGuid,
        public readonly string  $userEmail,
        public readonly bool    $userIsNew,
        public readonly bool    $userIsActive,
        public readonly int     $userRole,
        public readonly string  $userName,
        public readonly ?string $userPasswd,
        public readonly ?string $userLastLogin,
        public readonly bool    $hasPendingPasswordReset   = false,
        public readonly bool    $hasPendingActivationToken = false,
        public readonly ?int    $totalEventsCreated = null,
        public readonly ?int    $upcomingEventsCreated = null,
        public readonly ?int    $totalEnrollmentsCreated = null,
        public readonly ?int    $upcomingEnrollmentsCreated = null,
    ) {}
}
