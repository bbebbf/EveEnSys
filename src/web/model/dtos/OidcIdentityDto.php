<?php
declare(strict_types=1);

class OidcIdentityDto
{
    public function __construct(
        public readonly int    $identityId,
        public readonly int    $userId,
        public readonly int    $providerId,
        public readonly string $providerKey,
        public readonly string $providerSub,
        public readonly \DateTimeImmutable $linkedAt,
    ) {}
}
