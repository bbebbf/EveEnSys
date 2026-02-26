<?php
declare(strict_types=1);

class EventDto
{
    public function __construct(
        public readonly int     $eventId,
        public readonly string  $eventGuid,
        public readonly int     $creatorUserId,
        public readonly bool    $eventIsVisible,
        public readonly string  $eventTitle,
        public readonly ?string $eventDescription,
        public readonly string  $eventDate,
        public readonly ?string $eventLocation,
        public readonly ?float  $eventDurationHours,
        public readonly ?int    $eventMaxSubscriber,
        public readonly ?string $creatorName = null,
    ) {}
}
