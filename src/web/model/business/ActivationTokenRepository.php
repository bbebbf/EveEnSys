<?php
declare(strict_types=1);

class ActivationTokenRepository
{
    public function __construct(private mysqli $db) {}

    /**
     * Deletes any existing activation tokens for the user, creates a new one
     * that expires in 24 hours, and returns the raw (unhashed) token to be
     * sent by email.
     */
    public function createToken(int $userId): string
    {
        $this->deleteByUser($userId);

        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        $stmt = $this->db->prepare(
            "INSERT INTO activation_token (user_id, token_hash, token_expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))"
        );
        $stmt->bind_param('is', $userId, $tokenHash);
        $stmt->execute();

        return $rawToken;
    }

    /**
     * Hashes the raw token and looks up a record that is not yet expired
     * and not yet used. Returns ['token_id' => ..., 'user_id' => ...] or null.
     */
    public function findValidByToken(string $rawToken): ?array
    {
        $tokenHash = hash('sha256', $rawToken);

        $stmt = $this->db->prepare(
            "SELECT token_id, user_id
               FROM activation_token
              WHERE token_hash = ?
                AND token_expires_at > NOW()
                AND token_used = b'0'"
        );
        $stmt->bind_param('s', $tokenHash);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row ?: null;
    }

    public function markUsed(int $tokenId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE activation_token SET token_used = b'1' WHERE token_id = ?"
        );
        $stmt->bind_param('i', $tokenId);
        $stmt->execute();
    }

    public function deleteByUser(int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM activation_token WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }
}
