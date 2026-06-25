<?php

declare(strict_types=1);

namespace ImobiHub;

use DateTimeImmutable;
use PDO;

final class AdminRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(string $fullName, string $email, string $passwordHash): int
    {
        $now = (new DateTimeImmutable())->format(DATE_ATOM);

        $stmt = $this->pdo->prepare(
            'INSERT INTO admins (full_name, email, password_hash, created_at, updated_at)
             VALUES (:full_name, :email, :password_hash, :created_at, :updated_at)'
        );

        $stmt->execute([
            ':full_name' => $fullName,
            ':email' => strtolower($email),
            ':password_hash' => $passwordHash,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => strtolower($email)]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admins WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function createResetToken(int $adminId, string $tokenHash, DateTimeImmutable $expiresAt): void
    {
        $now = (new DateTimeImmutable())->format(DATE_ATOM);

        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_password_resets (admin_id, token_hash, expires_at, created_at)
             VALUES (:admin_id, :token_hash, :expires_at, :created_at)'
        );

        $stmt->execute([
            ':admin_id' => $adminId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt->format(DATE_ATOM),
            ':created_at' => $now,
        ]);
    }

    public function consumeResetToken(string $tokenHash): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, admin_id, expires_at, used_at
             FROM admin_password_resets
             WHERE token_hash = :token_hash
             LIMIT 1'
        );
        $stmt->execute([':token_hash' => $tokenHash]);
        $token = $stmt->fetch();

        if (!is_array($token)) {
            return null;
        }

        if (!empty($token['used_at'])) {
            return null;
        }

        $expiresAt = new DateTimeImmutable((string) $token['expires_at']);
        if ($expiresAt < new DateTimeImmutable()) {
            return null;
        }

        $update = $this->pdo->prepare('UPDATE admin_password_resets SET used_at = :used_at WHERE id = :id');
        $update->execute([
            ':used_at' => (new DateTimeImmutable())->format(DATE_ATOM),
            ':id' => (int) $token['id'],
        ]);

        return (int) $token['admin_id'];
    }

    public function updatePassword(int $adminId, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE admins
             SET password_hash = :password_hash, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            ':password_hash' => $passwordHash,
            ':updated_at' => (new DateTimeImmutable())->format(DATE_ATOM),
            ':id' => $adminId,
        ]);
    }
}
