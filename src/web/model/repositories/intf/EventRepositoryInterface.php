<?php
declare(strict_types=1);

require_once __DIR__ . '/EventRepositoryTypes.php';

interface EventRepositoryInterface
{
    /** @return EventDto[] */
    public function findUpcoming(int $limit): array;

    /** @return EventDto[] */
    public function findAllUpcoming(EventsSearchCriteria $criteria): array;

    /** @return EventDto[] */
    public function findAll(EventsSearchCriteria $criteria): array;

    /** @return EventDto[] */
    public function findAllNew(EventsSearchCriteria $criteria): array;

    /** @return EventDto[] */
    public function findAllByUser(int $userId): array;

    public function findById(int $id): ?EventDto;

    public function findByGuid(string $guid): ?EventDto;

    public function create(int $creatorUserId, array $data): string;

    public function update(int $eventId, array $data): void;

    public function delete(int $eventId): void;

    public function setVisible(int $eventId, bool $visible): void;

    public function isOwner(int $eventId, int $userId): bool;

    public function deleteSubscribersByEvent(int $eventId): void;

    public function deleteSubscribersForUserEvents(int $userId): void;

    public function deleteSubscribersByCreator(int $userId): void;

    public function deleteAllByUser(int $userId): void;

    /** @return SubscriberDto[] */
    public function findSubscribersByEvent(int $eventId): array;

    /** @return SubscriberDto[] */
    public function findEnrolledByUser(int $userId): array;

    /** @return SubscriberDto[] */
    public function findAllUpcomingEnrollments(): array;

    public function countSubscribers(int $eventId): int;

    public function isUserEnrolledAsSelf(int $eventId, int $userId): bool;

    public function createSubscriber(int $eventId, int $creatorUserId, bool $isCreator, ?string $name): void;

    public function findSubscriberByGuid(string $subscriberGuid): ?SubscriberDto;

    public function deleteSubscriber(string $subscriberGuid, int $creatorUserId, bool $ignoreCreator = false): bool;

    /** @return string[] */
    public function findGuidsNewOrUpdatedSince(\DateTimeImmutable $since): array;
}
