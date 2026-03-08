<?php
declare(strict_types=1);

class OidcIdentityRepository implements OidcIdentityRepositoryInterface
{
    public function __construct(private mysqli $db) {}

    public function findByProviderSub(int $providerId, string $sub): ?OidcIdentityDto
    {
        $stmt = $this->db->prepare(
            'SELECT i.oidc_id, i.user_id, i.oidc_provider_id, p.oidc_provider_key,
                    i.oidc_provider_sub, i.oidc_linked_at
               FROM oidc_identity i
               JOIN oidc_provider p ON p.oidc_provider_id = i.oidc_provider_id
              WHERE i.oidc_provider_id = ? AND i.oidc_provider_sub = ?'
        );
        $stmt->bind_param('is', $providerId, $sub);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $result->free();
        $stmt->close();
        return $row ? $this->mapRow($row) : null;
    }

    /** @return OidcIdentityDto[] */
    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT i.oidc_id, i.user_id, i.oidc_provider_id, p.oidc_provider_key,
                    i.oidc_provider_sub, i.oidc_linked_at
               FROM oidc_identity i
               JOIN oidc_provider p ON p.oidc_provider_id = i.oidc_provider_id
              WHERE i.user_id = ?
              ORDER BY i.oidc_linked_at ASC'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        $stmt->close();
        return array_map(fn($r) => $this->mapRow($r), $rows);
    }

    /** @return array<int, OidcIdentityDto[]> */
    public function findAllGroupedByUserId(): array
    {
        $result = $this->db->query(
            'SELECT i.oidc_id, i.user_id, i.oidc_provider_id, p.oidc_provider_key,
                    i.oidc_provider_sub, i.oidc_linked_at
               FROM oidc_identity i
               JOIN oidc_provider p ON p.oidc_provider_id = i.oidc_provider_id
              ORDER BY i.user_id, i.oidc_linked_at ASC'
        );
        $grouped = [];
        foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
            $grouped[(int)$row['user_id']][] = $this->mapRow($row);
        }
        $result->free();
        return $grouped;
    }

    public function create(int $userId, int $providerId, string $sub): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO oidc_identity (user_id, oidc_provider_id, oidc_provider_sub) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('iis', $userId, $providerId, $sub);
        $stmt->execute();
        $stmt->close();
    }

    public function deleteById(int $identityId, int $userId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM oidc_identity WHERE oidc_id = ? AND user_id = ?'
        );
        $stmt->bind_param('ii', $identityId, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function deleteByUser(int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM oidc_identity WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM oidc_identity WHERE user_id = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = (int)$result->fetch_row()[0];
        $result->free();
        $stmt->close();
        return $count;
    }

    private function mapRow(array $row): OidcIdentityDto
    {
        return new OidcIdentityDto(
            identityId:  (int)$row['oidc_id'],
            userId:      (int)$row['user_id'],
            providerId:  (int)$row['oidc_provider_id'],
            providerKey: $row['oidc_provider_key'],
            providerSub: $row['oidc_provider_sub'],
            linkedAt:    new \DateTimeImmutable($row['oidc_linked_at']),
        );
    }
}
