<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        // Ensure foreign keys are enabled
        $this->pdo->exec('PRAGMA foreign_keys = ON;');
    }

    public function createUser(string $username): array
    {
        // Generate a unique API token
        $apiToken = bin2hex(random_bytes(16));

        try {
            // Use a transaction for data consistency
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('
                INSERT INTO users (username, api_token)
                VALUES (:username, :api_token)
            ');

            $stmt->execute([
                ':username' => $username,
                ':api_token' => $apiToken
            ]);

            $id = (int) $this->pdo->lastInsertId();

            // Fetch the created user to get all fields including the timestamp
            $stmt = $this->pdo->prepare('
                SELECT id, username, api_token, created_at
                FROM users
                WHERE id = :id
            ');

            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new PDOException('Failed to retrieve created user');
            }

            $this->pdo->commit();

            // Convert id to integer
            $user['id'] = (int) $user['id'];

            return $user;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // Add context to the error message
            throw new PDOException('Failed to create user: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getUserByUsername(string $username): ?array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT id, username, api_token, created_at
                FROM users
                WHERE username = :username
            ');

            $stmt->execute([':username' => $username]);

            $user = $stmt->fetch();

            if (!$user) {
                return null;
            }

            // Convert id to integer
            $user['id'] = (int) $user['id'];

            return $user;
        } catch (PDOException $e) {
            // Add context to the error message
            throw new PDOException('Failed to get user by username: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getUserById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT id, username, api_token, created_at
                FROM users
                WHERE id = :id
            ');

            $stmt->execute([':id' => $id]);

            $user = $stmt->fetch();

            if (!$user) {
                return null;
            }

            // Convert id to integer
            $user['id'] = (int) $user['id'];

            return $user;
        } catch (PDOException $e) {
            // Add context to the error message
            throw new PDOException('Failed to get user by ID: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getUserByToken(string $token): ?array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT id, username, api_token, created_at
                FROM users
                WHERE api_token = :token
            ');

            $stmt->execute([':token' => $token]);

            $user = $stmt->fetch();

            if (!$user) {
                return null;
            }

            // Convert id to integer
            $user['id'] = (int) $user['id'];

            return $user;
        } catch (PDOException $e) {
            // Add context to the error message
            throw new PDOException('Failed to get user by token: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Check if the users table exists
     */
    public function checkTableExists(): bool
    {
        try {
            $stmt = $this->pdo->query("
                SELECT name FROM sqlite_master 
                WHERE type='table' AND name='users'
            ");

            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }
}