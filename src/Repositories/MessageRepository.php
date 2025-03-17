<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class MessageRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        // Ensure foreign keys are enabled
        $this->pdo->exec('PRAGMA foreign_keys = ON;');
    }

    public function createMessage(int $groupId, int $userId, string $content): array
    {
        try {
            $this->pdo->beginTransaction();

            // First check if the group exists
            $stmt = $this->pdo->prepare('
                SELECT 1 FROM groups WHERE id = :group_id
            ');
            $stmt->execute([':group_id' => $groupId]);

            if (!$stmt->fetchColumn()) {
                throw new PDOException("Group with ID $groupId does not exist");
            }

            // Then check if the user exists
            $stmt = $this->pdo->prepare('
                SELECT 1 FROM users WHERE id = :user_id
            ');
            $stmt->execute([':user_id' => $userId]);

            if (!$stmt->fetchColumn()) {
                throw new PDOException("User with ID $userId does not exist");
            }

            // Create the message
            $stmt = $this->pdo->prepare('
                INSERT INTO messages (group_id, user_id, content)
                VALUES (:group_id, :user_id, :content)
            ');

            $stmt->execute([
                ':group_id' => $groupId,
                ':user_id' => $userId,
                ':content' => $content
            ]);

            $id = (int) $this->pdo->lastInsertId();

            // Get the full message data including the timestamp
            $stmt = $this->pdo->prepare('
                SELECT m.id, m.group_id, m.user_id, m.content, m.created_at, u.username
                FROM messages m
                JOIN users u ON m.user_id = u.id
                WHERE m.id = :id
            ');

            $stmt->execute([':id' => $id]);
            $message = $stmt->fetch();

            if (!$message) {
                throw new PDOException('Failed to retrieve created message');
            }

            $this->pdo->commit();

            // Convert id, group_id, and user_id to integers
            $message['id'] = (int) $message['id'];
            $message['group_id'] = (int) $message['group_id'];
            $message['user_id'] = (int) $message['user_id'];

            return $message;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // Add context to the error message
            throw new PDOException('Failed to create message: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getMessagesByGroupId(int $groupId, ?int $limit = 100, ?int $offset = 0): array
    {
        try {
            // First check if the group exists
            $stmt = $this->pdo->prepare('
                SELECT 1 FROM groups WHERE id = :group_id
            ');
            $stmt->execute([':group_id' => $groupId]);

            if (!$stmt->fetchColumn()) {
                throw new PDOException("Group with ID $groupId does not exist");
            }

            // Ensure limit and offset are valid
            $limit = max(1, min(1000, $limit ?? 100));
            $offset = max(0, $offset ?? 0);

            $stmt = $this->pdo->prepare('
                SELECT m.id, m.group_id, m.user_id, m.content, m.created_at, u.username
                FROM messages m
                JOIN users u ON m.user_id = u.id
                WHERE m.group_id = :group_id
                ORDER BY m.created_at DESC
                LIMIT :limit OFFSET :offset
            ');

            $stmt->bindValue(':group_id', $groupId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $messages = $stmt->fetchAll();

            // Convert id, group_id, and user_id to integers
            return array_map(function ($message) {
                $message['id'] = (int) $message['id'];
                $message['group_id'] = (int) $message['group_id'];
                $message['user_id'] = (int) $message['user_id'];
                return $message;
            }, $messages);
        } catch (PDOException $e) {
            // Add context to the error message
            throw new PDOException('Failed to get messages by group ID: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getMessagesByGroupIdSince(int $groupId, string $timestamp): array
    {
        try {
            // First check if the group exists
            $stmt = $this->pdo->prepare('
                SELECT 1 FROM groups WHERE id = :group_id
            ');
            $stmt->execute([':group_id' => $groupId]);

            if (!$stmt->fetchColumn()) {
                throw new PDOException("Group with ID $groupId does not exist");
            }

            // Validate timestamp format
            if (!strtotime($timestamp)) {
                throw new \InvalidArgumentException("Invalid timestamp format: $timestamp");
            }

            $stmt = $this->pdo->prepare('
                SELECT m.id, m.group_id, m.user_id, m.content, m.created_at, u.username
                FROM messages m
                JOIN users u ON m.user_id = u.id
                WHERE m.group_id = :group_id AND m.created_at > :timestamp
                ORDER BY m.created_at ASC
            ');

            $stmt->execute([
                ':group_id' => $groupId,
                ':timestamp' => $timestamp
            ]);

            $messages = $stmt->fetchAll();

            // Convert id, group_id, and user_id to integers
            return array_map(function ($message) {
                $message['id'] = (int) $message['id'];
                $message['group_id'] = (int) $message['group_id'];
                $message['user_id'] = (int) $message['user_id'];
                return $message;
            }, $messages);
        } catch (PDOException $e) {
            // Add context to the error message
            throw new PDOException('Failed to get messages since timestamp: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Check if the messages table exists
     */
    public function checkTableExists(): bool
    {
        try {
            $stmt = $this->pdo->query("
                SELECT name FROM sqlite_master 
                WHERE type='table' AND name='messages'
            ");

            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }
}