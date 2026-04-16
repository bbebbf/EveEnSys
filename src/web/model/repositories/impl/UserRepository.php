<?php
declare(strict_types=1);

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private mysqli $db,
        private int $delayedStartMinutes,
    ) {}

    public function findByEmail(string $email): ?UserDto
    {
        $stmt = $this->db->prepare(
            'SELECT user_id, user_guid, user_email, user_is_new, user_is_active, user_role, user_name, user_passwd, user_last_login
               FROM `user`
              WHERE user_email = ?'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $result->free();
        $stmt->close();
        return $row ? $this->mapRow($row) : null;
    }

    public function findById(int $id): ?UserDto
    {
        $stmt = $this->db->prepare(
            'SELECT user_id, user_guid, user_email, user_is_new, user_is_active, user_role, user_name, user_passwd, user_last_login
               FROM `user`
              WHERE user_id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $result->free();
        $stmt->close();
        return $row ? $this->mapRow($row) : null;
    }

    public function findByGuid(string $guid): ?UserDto
    {
        $stmt = $this->db->prepare(
            'SELECT user_id, user_guid, user_email, user_is_new, user_is_active, user_role, user_name, user_passwd, user_last_login
               FROM `user`
              WHERE user_guid = ?'
        );
        $stmt->bind_param('s', $guid);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $result->free();
        $stmt->close();
        return $row ? $this->mapRow($row) : null;
    }

    public function create(string $name, string $email, string $hashedPwd): int
    {
        $guid = $this->generateGuid();
        $stmt = $this->db->prepare(
            "INSERT INTO `user` (user_guid, user_email, user_is_new, user_is_active, user_role, user_name, user_passwd)
             VALUES (?, ?, b'1', b'0', 0, ?, ?)"
        );
        $stmt->bind_param('ssss', $guid, $email, $name, $hashedPwd);
        $stmt->execute();
        $insertId = $this->db->insert_id;
        $stmt->close();
        return $insertId;
    }

    public function createOidc(string $name, string $email): int
    {
        $guid = $this->generateGuid();
        $stmt = $this->db->prepare(
            "INSERT INTO `user` (user_guid, user_email, user_is_new, user_is_active, user_role, user_name, user_passwd)
             VALUES (?, ?, b'1', b'0', 0, ?, NULL)"
        );
        $stmt->bind_param('sss', $guid, $email, $name);
        $stmt->execute();
        $insertId = $this->db->insert_id;
        $stmt->close();
        return $insertId;
    }

    public function activate(int $userId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `user` SET user_is_active = b'1', user_is_new = b'0' WHERE user_id = ?"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        // Promote to admin if no admin exists yet
        if ($this->countAdmins() === 0) {
            $this->setRole($userId, 1);
        }
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE `user` SET user_last_login = NOW() WHERE user_id = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function updateName(int $userId, string $name): void
    {
        $stmt = $this->db->prepare(
            'UPDATE `user` SET user_name = ? WHERE user_id = ?'
        );
        $stmt->bind_param('si', $name, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function updatePassword(int $userId, string $hashedPwd): void
    {
        $stmt = $this->db->prepare(
            'UPDATE `user` SET user_passwd = ? WHERE user_id = ?'
        );
        $stmt->bind_param('si', $hashedPwd, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function removePassword(int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE `user` SET user_passwd = NULL WHERE user_id = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function delete(int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM `user` WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function setActive(int $userId, bool $active): void
    {
        if ($active) {
            $stmt = $this->db->prepare("UPDATE `user` SET user_is_active = b'1' WHERE user_id = ?");
        } else {
            $stmt = $this->db->prepare("UPDATE `user` SET user_is_active = b'0', user_role = 0 WHERE user_id = ?");
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function setRole(int $userId, int $role): void
    {
        $stmt = $this->db->prepare('UPDATE `user` SET user_role = ? WHERE user_id = ?');
        $stmt->bind_param('ii', $role, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function countAdmins(): int
    {
        $result = $this->db->query("SELECT COUNT(*) FROM `user` WHERE user_is_active = b'1' AND user_role >= 1");
        $count = (int)$result->fetch_row()[0];
        $result->free();
        return $count;
    }

    public function countAll(): int
    {
        $result = $this->db->query("SELECT COUNT(*) FROM `user`");
        $count = (int)$result->fetch_row()[0];
        $result->free();
        return $count;
    }

    /** @return UserDto[] */
    public function findAll(): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.user_id, u.user_guid, u.user_email, u.user_is_new, u.user_is_active, u.user_role, u.user_name, u.user_passwd, u.user_last_login,
                    EXISTS (
                        SELECT 1 FROM password_reset pr
                         WHERE pr.user_id          = u.user_id
                           AND pr.reset_expires_at > NOW()
                           AND pr.reset_used        = b\'0\'
                    ) AS has_pending_password_reset,
                    EXISTS (
                        SELECT 1 FROM activation_token at
                         WHERE at.user_id          = u.user_id
                           AND at.token_expires_at > NOW()
                           AND at.token_used        = b\'0\'
                    ) AS has_pending_activation_token,
                    (
                        SELECT COUNT(*) FROM event e
                        WHERE e.creator_user_id = u.user_id
                    ) AS total_events_created,
                    (
                        SELECT COUNT(*) FROM event e
                        WHERE e.creator_user_id = u.user_id
                        AND DATE_ADD(e.event_date, INTERVAL ? MINUTE) >= NOW()
                    ) AS upcoming_events_created,
                    (
                        SELECT COUNT(*) FROM subscriber s
                        WHERE s.creator_user_id = u.user_id
                    ) AS total_enrollments_created,
                    (
                        SELECT COUNT(*) FROM subscriber s
                        JOIN event e ON e.event_id = s.event_id
                        WHERE s.creator_user_id = u.user_id
                        AND DATE_ADD(e.event_date, INTERVAL ? MINUTE) >= NOW()
                    ) AS upcoming_enrollments_created
               FROM `user` u
              ORDER BY u.user_name ASC'
        );
        $stmt->bind_param('ii', $this->delayedStartMinutes, $this->delayedStartMinutes);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $this->mapRow($row);
        }
        $result->free();
        $stmt->close();
        return $users;
    }

    private function generateGuid(): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM `user` WHERE user_guid = ?'
        );
        for ($i = 0; $i < 10; $i++) {
            $guid = '';
            for ($j = 0; $j < 8; $j++) {
                $guid .= $chars[random_int(0, 63)];
            }
            $stmt->bind_param('s', $guid);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = (int)$result->fetch_row()[0];
            $result->free();
            if ($count === 0) {
                $stmt->close();
                return $guid;
            }
        }
        $stmt->close();
        throw new \RuntimeException('Failed to generate unique user GUID');
    }

    private function mapRow(array $row): UserDto
    {
        $pastEventsCreated = null;
        $upcomingEventsCreated = null;
        if (isset($row['total_events_created']) && isset($row['upcoming_events_created'])) {
            $upcomingEventsCreated = (int)$row['upcoming_events_created'];
            $pastEventsCreated = (int)$row['total_events_created'] - $upcomingEventsCreated;
        }

        $pastEnrollmentsCreated = null;
        $upcomingEnrollmentsCreated = null;
        if (isset($row['total_enrollments_created']) && isset($row['upcoming_enrollments_created'])) {
            $upcomingEnrollmentsCreated = (int)$row['upcoming_enrollments_created'];
            $pastEnrollmentsCreated = (int)$row['total_enrollments_created'] - $upcomingEnrollmentsCreated;
        }

        return new UserDto(
            userId:        (int)$row['user_id'],
            userGuid:      $row['user_guid'],
            userEmail:     $row['user_email'],
            userIsNew:     (bool)$row['user_is_new'],
            userIsActive:  (bool)$row['user_is_active'],
            userRole:      (int)$row['user_role'],
            userName:      $row['user_name'],
            userPasswd:                $row['user_passwd'] ?? null,
            userLastLogin:             isset($row['user_last_login']) ? new \DateTimeImmutable($row['user_last_login']) : null,
            hasPendingPasswordReset:   (bool)($row['has_pending_password_reset']   ?? false),
            hasPendingActivationToken: (bool)($row['has_pending_activation_token'] ?? false),
            pastEventsCreated:         $pastEventsCreated,
            upcomingEventsCreated:     $upcomingEventsCreated,
            pastEnrollmentsCreated:    $pastEnrollmentsCreated,
            upcomingEnrollmentsCreated:$upcomingEnrollmentsCreated,
        );
    }
}
