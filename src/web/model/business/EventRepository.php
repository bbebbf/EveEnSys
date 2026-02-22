<?php
declare(strict_types=1);

class EventRepository
{
    public function __construct(private mysqli $db) {}

    /** @return EventDto[] */
    public function findUpcoming(int $limit): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, u.user_name AS creator_name
               FROM event e
               JOIN `user` u ON e.creator_user_id = u.user_id
              WHERE e.event_date >= NOW()
              ORDER BY e.event_date ASC
              LIMIT ?'
        );
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $events = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $events[] = $this->mapEventRow($row);
        }
        return $events;
    }

    /** @return EventDto[] */
    public function findAllUpcoming(): array
    {
        $result = $this->db->query(
            'SELECT e.*, u.user_name AS creator_name
               FROM event e
               JOIN `user` u ON e.creator_user_id = u.user_id
              WHERE e.event_date >= NOW()
              ORDER BY e.event_date ASC'
        );
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $this->mapEventRow($row);
        }
        return $events;
    }

    /** @return EventDto[] */
    public function findAll(): array
    {
        $result = $this->db->query(
            'SELECT e.*, u.user_name AS creator_name
               FROM event e
               JOIN `user` u ON e.creator_user_id = u.user_id
              ORDER BY e.event_date ASC'
        );
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $this->mapEventRow($row);
        }
        return $events;
    }

    /** @return EventDto[] */
    public function findAllByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, u.user_name AS creator_name
               FROM event e
               JOIN `user` u ON e.creator_user_id = u.user_id
               WHERE u.user_id = ?
              ORDER BY e.event_date ASC'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $events = [];
        while ($row = $stmt->get_result()->fetch_assoc()) {
            $events[] = $this->mapEventRow($row);
        }
        return $events;
    }

    public function findById(int $id): ?EventDto
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, u.user_name AS creator_name
               FROM event e
               JOIN `user` u ON e.creator_user_id = u.user_id
              WHERE e.event_id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $this->mapEventRow($row) : null;
    }

    public function findByGuid(string $guid): ?EventDto
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, u.user_name AS creator_name
               FROM event e
               JOIN `user` u ON e.creator_user_id = u.user_id
              WHERE e.event_guid = ?'
        );
        $stmt->bind_param('s', $guid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $this->mapEventRow($row) : null;
    }

    public function create(int $creatorUserId, array $data): string
    {
        $guid = $this->generateGuid();
        $stmt = $this->db->prepare(
            'INSERT INTO event (event_guid, creator_user_id, event_title, event_description, event_date, event_duration_hours, event_max_subscriber)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'sisssdi',
            $guid,
            $creatorUserId,
            $data['event_title'],
            $data['event_description'],
            $data['event_date'],
            $data['event_duration_hours'],
            $data['event_max_subscriber']
        );
        $stmt->execute();
        return $guid;
    }

    public function update(int $eventId, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE event
                SET event_title = ?, event_description = ?, event_date = ?,
                    event_duration_hours = ?, event_max_subscriber = ?
              WHERE event_id = ?'
        );
        $stmt->bind_param(
            'sssdii',
            $data['event_title'],
            $data['event_description'],
            $data['event_date'],
            $data['event_duration_hours'],
            $data['event_max_subscriber'],
            $eventId
        );
        $stmt->execute();
    }

    public function delete(int $eventId): void
    {
        $stmt = $this->db->prepare('DELETE FROM event WHERE event_id = ?');
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
    }

    public function isOwner(int $eventId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM event WHERE event_id = ? AND creator_user_id = ?'
        );
        $stmt->bind_param('ii', $eventId, $userId);
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_row()[0];
    }

    public function deleteSubscribersByEvent(int $eventId): void
    {
        $stmt = $this->db->prepare('DELETE FROM subscriber WHERE event_id = ?');
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
    }

    /** @return SubscriberDto[] */
    public function findSubscribersByEvent(int $eventId): array
    {
        $stmt = $this->db->prepare(
            'SELECT s.subscriber_id, s.event_id, s.creator_user_id, s.subscriber_is_creator,
                    s.subscriber_enroll_timestamp,
                    IF(s.subscriber_is_creator, u.user_name, s.subscriber_name) AS subscriber_name
               FROM subscriber s
               JOIN `user` u ON s.creator_user_id = u.user_id
              WHERE s.event_id = ?
              ORDER BY s.subscriber_enroll_timestamp ASC'
        );
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $rows = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $this->mapSubscriberRow($row);
        }
        return $rows;
    }

    public function countSubscribers(int $eventId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM subscriber WHERE event_id = ?');
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_row()[0];
    }

    public function isUserEnrolledAsSelf(int $eventId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM subscriber WHERE event_id = ? AND creator_user_id = ? AND subscriber_is_creator = 1'
        );
        $stmt->bind_param('ii', $eventId, $userId);
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_row()[0];
    }

    public function createSubscriber(int $eventId, int $creatorUserId, bool $isCreator, ?string $name): void
    {
        $now          = date('Y-m-d H:i:s');
        $isCreatorInt = $isCreator ? 1 : 0;
        $stmt = $this->db->prepare(
            'INSERT INTO subscriber (event_id, creator_user_id, subscriber_is_creator, subscriber_name, subscriber_enroll_timestamp)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('iiiss', $eventId, $creatorUserId, $isCreatorInt, $name, $now);
        $stmt->execute();
    }

    public function deleteSubscriber(int $subscriberId, int $creatorUserId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM subscriber WHERE subscriber_id = ? AND creator_user_id = ?'
        );
        $stmt->bind_param('ii', $subscriberId, $creatorUserId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    private function generateGuid(): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM event WHERE event_guid = ?'
        );
        for ($i = 0; $i < 10; $i++) {
            $guid = '';
            for ($j = 0; $j < 8; $j++) {
                $guid .= $chars[random_int(0, 63)];
            }
            $stmt->bind_param('s', $guid);
            $stmt->execute();
            if ((int)$stmt->get_result()->fetch_row()[0] === 0) {
                return $guid;
            }
        }
        throw new \RuntimeException('Failed to generate unique event GUID');
    }

    private function mapEventRow(array $row): EventDto
    {
        return new EventDto(
            eventId:            (int)$row['event_id'],
            eventGuid:          $row['event_guid'],
            creatorUserId:      (int)$row['creator_user_id'],
            eventTitle:         $row['event_title'],
            eventDescription:   $row['event_description'] ?? null,
            eventDate:          $row['event_date'],
            eventDurationHours: isset($row['event_duration_hours']) ? (float)$row['event_duration_hours'] : null,
            eventMaxSubscriber: isset($row['event_max_subscriber']) ? (int)$row['event_max_subscriber'] : null,
            creatorName:        $row['creator_name'] ?? null,
        );
    }

    private function mapSubscriberRow(array $row): SubscriberDto
    {
        return new SubscriberDto(
            subscriberId:              (int)$row['subscriber_id'],
            eventId:                   (int)$row['event_id'],
            creatorUserId:             (int)$row['creator_user_id'],
            subscriberIsCreator:       (bool)$row['subscriber_is_creator'],
            subscriberName:            $row['subscriber_name'] ?? null,
            subscriberEnrollTimestamp: $row['subscriber_enroll_timestamp'],
        );
    }
}
