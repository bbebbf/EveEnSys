<?php
declare(strict_types=1);

class SubscriberDto
{
    public function __construct(
        public readonly int     $subscriberId,
        public readonly string  $subscriberGuid,
        public readonly int     $eventId,
        public readonly int     $creatorUserId,
        public readonly bool    $subscriberIsCreator,
        public readonly ?string $subscriberName,
        public readonly string  $subscriberEnrollTimestamp,
    ) {}
}
