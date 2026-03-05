<?php
declare(strict_types=1);

interface OidcIdentityRepositoryInterface
{
    public function findByProviderSub(int $providerId, string $sub): ?OidcIdentityDto;

    /** @return OidcIdentityDto[] */
    public function findByUserId(int $userId): array;

    /** @return array<int, OidcIdentityDto[]> keyed by user_id */
    public function findAllGroupedByUserId(): array;

    public function create(int $userId, int $providerId, string $sub): void;

    public function deleteById(int $identityId, int $userId): void;

    public function deleteByUser(int $userId): void;

    public function countByUser(int $userId): int;
}
