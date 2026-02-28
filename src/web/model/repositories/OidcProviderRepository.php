<?php
declare(strict_types=1);

class OidcProviderRepository
{
    public function __construct(private mysqli $db) {}

    /**
     * Returns active providers only, keyed by providerKey.
     *
     * @return OidcProviderInfoDto[providerKey] => OidcProviderInfoDto
     */
    public function findAllActiveInfos(): array
    {
        $result = $this->db->query(
            "SELECT oidc_provider_id, oidc_provider_key,
                    oidc_provider_label, oidc_provider_image_svg,
                    oidc_provider_is_active
               FROM oidc_provider
              WHERE oidc_provider_is_active = b'1'
              ORDER BY oidc_provider_id"
        );

        $dtos = [];
        while ($row = $result->fetch_assoc()) {
            $dto = $this->mapRowInfo($row);
            $dtos[$dto->providerKey] = $dto;
        }
        return $dtos;
    }

    public function findByKey(string $providerKey): ?OidcProviderDto
    {
        $stmt = $this->db->prepare(
            'SELECT oidc_provider_id, oidc_provider_key, oidc_provider_label,
                    oidc_provider_image_svg, oidc_provider_discovery_url,
                    oidc_provider_client_id, oidc_provider_client_secret,
                    oidc_provider_redirect_uri, oidc_provider_scopes, oidc_provider_is_active
               FROM oidc_provider
              WHERE oidc_provider_key = ?'
        );
        $stmt->bind_param('s', $providerKey);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $this->mapRow($row) : null;
    }

    private function mapRow(array $row): OidcProviderDto
    {
        return new OidcProviderDto(
            providerId:   (int)$row['oidc_provider_id'],
            providerKey:  $row['oidc_provider_key'],
            label:        $row['oidc_provider_label'],
            imageSvg:     $row['oidc_provider_image_svg'],
            discoveryUrl: $row['oidc_provider_discovery_url'],
            clientId:     $row['oidc_provider_client_id'],
            clientSecret: $row['oidc_provider_client_secret'],
            redirectUri:  $row['oidc_provider_redirect_uri'],
            scopes:       array_values(array_filter(explode(' ', $row['oidc_provider_scopes']))),
            isActive:     (bool)$row['oidc_provider_is_active']
        );
    }

    private function mapRowInfo(array $row): OidcProviderInfoDto
    {
        return new OidcProviderInfoDto(
            providerId:   (int)$row['oidc_provider_id'],
            providerKey:  $row['oidc_provider_key'],
            label:        $row['oidc_provider_label'],
            imageSvg:     $row['oidc_provider_image_svg'],
            isActive:     (bool)$row['oidc_provider_is_active']
        );
    }
}
