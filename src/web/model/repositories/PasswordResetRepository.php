<?php
declare(strict_types=1);

class PasswordResetRepository
{
    public function __construct(private mysqli $db) {}

    /**
     * Deletes any existing tokens for the user, creates a new one that
     * expires in one hour, and returns the raw (unhashed) token to be
     * sent by email.
     */
    public function createToken(int $userId): string
    {
        $this->deleteByUser($userId);

        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        $stmt = $this->db->prepare(
            "INSERT INTO password_reset (user_id, reset_token_hash, reset_expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))"
        );
        $stmt->bind_param('is', $userId, $tokenHash);
        $stmt->execute();

        return $rawToken;
    }

    /**
     * Hashes the raw token and looks up a record that is not yet expired
     * and not yet used. Returns ['reset_id' => ..., 'user_id' => ...] or null.
     */
    public function findValidByToken(string $rawToken): ?array
    {
        $tokenHash = hash('sha256', $rawToken);

        $stmt = $this->db->prepare(
            "SELECT reset_id, user_id
               FROM password_reset
              WHERE reset_token_hash = ?
                AND reset_expires_at > NOW()
                AND reset_used = b'0'"
        );
        $stmt->bind_param('s', $tokenHash);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row ?: null;
    }

    public function markUsed(int $resetId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE password_reset SET reset_used = b'1' WHERE reset_id = ?"
        );
        $stmt->bind_param('i', $resetId);
        $stmt->execute();
    }

    public function deleteByUser(int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM password_reset WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }
}
