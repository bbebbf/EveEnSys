<?php
declare(strict_types=1);

class OidcProviderInfoDto
{
    public function __construct(
        public readonly int     $providerId,
        public readonly string  $providerKey,
        public readonly string  $label,
        public readonly ?string $imageSvg,
        public readonly bool    $isActive,
    ) {}
}
