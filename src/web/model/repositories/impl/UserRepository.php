<?php
declare(strict_types=1);

class UserRepository implements UserRepositoryInterface
{
    public function __construct(private mysqli $db) {}

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
        $result = $this->db->query(
            'SELECT user_id, user_guid, user_email, user_is_new, user_is_active, user_role, user_name, user_passwd, user_last_login
               FROM `user`
              ORDER BY user_name ASC'
        );
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $this->mapRow($row);
        }
        $result->free();
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
        return new UserDto(
            userId:        (int)$row['user_id'],
            userGuid:      $row['user_guid'],
            userEmail:     $row['user_email'],
            userIsNew:     (bool)$row['user_is_new'],
            userIsActive:  (bool)$row['user_is_active'],
            userRole:      (int)$row['user_role'],
            userName:      $row['user_name'],
            userPasswd:    $row['user_passwd'] ?? null,
            userLastLogin: $row['user_last_login'],
        );
    }
}
