<?php
declare(strict_types=1);

class UserRepository
{
    public function __construct(private mysqli $db) {}

    public function findByEmail(string $email): ?UserDto
    {
        $stmt = $this->db->prepare(
            'SELECT user_id, user_email, user_is_active, user_role, user_name, user_passwd, user_last_login
               FROM `user`
              WHERE user_email = ?'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $this->mapRow($row) : null;
    }

    public function findById(int $id): ?UserDto
    {
        $stmt = $this->db->prepare(
            'SELECT user_id, user_email, user_is_active, user_role, user_name, user_passwd, user_last_login
               FROM `user`
              WHERE user_id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $this->mapRow($row) : null;
    }

    public function create(string $name, string $email, string $hashedPwd): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `user` (user_email, user_is_active, user_role, user_name, user_passwd)
             VALUES (?, b'0', 0, ?, ?)"
        );
        $stmt->bind_param('sss', $email, $name, $hashedPwd);
        $stmt->execute();
        return $this->db->insert_id;
    }

    public function activate(int $userId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `user` SET user_is_active = b'1' WHERE user_id = ?"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE `user` SET user_last_login = NOW() WHERE user_id = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }

    public function updateName(int $userId, string $name): void
    {
        $stmt = $this->db->prepare(
            'UPDATE `user` SET user_name = ? WHERE user_id = ?'
        );
        $stmt->bind_param('si', $name, $userId);
        $stmt->execute();
    }

    public function updatePassword(int $userId, string $hashedPwd): void
    {
        $stmt = $this->db->prepare(
            'UPDATE `user` SET user_passwd = ? WHERE user_id = ?'
        );
        $stmt->bind_param('si', $hashedPwd, $userId);
        $stmt->execute();
    }

    private function mapRow(array $row): UserDto
    {
        return new UserDto(
            userId:        (int)$row['user_id'],
            userEmail:     $row['user_email'],
            userIsActive:  (bool)ord((string)$row['user_is_active']),
            userRole:      (int)$row['user_role'],
            userName:      $row['user_name'],
            userPasswd:    $row['user_passwd'],
            userLastLogin: $row['user_last_login'],
        );
    }
}
