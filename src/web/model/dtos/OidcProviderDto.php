<?php
declare(strict_types=1);

class OidcProviderDto
{
    public function __construct(
        public readonly int     $providerId,
        public readonly string  $providerKey,
        public readonly string  $label,
        public readonly ?string $imageSvg,
        public readonly string  $discoveryUrl,
        public readonly string  $clientId,
        public readonly string  $clientSecret,
        public readonly string  $redirectUri,
        public readonly array   $scopes,
        public readonly bool    $isActive,
    ) {}
}
