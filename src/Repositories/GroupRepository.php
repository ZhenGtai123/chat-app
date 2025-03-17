<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class GroupRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        // Ensure foreign keys are enabled
        $this->pdo->exec('PRAGMA foreign_keys = ON;');
    }

    public function createGroup(string $name, string $description, int $userId): array
    {
        try {
            $this->pdo->beginTransaction();

            // Create the group
            $stmt = $this->pdo->prepare('
                INSERT INTO groups (name, description, created_by)
                VALUES (:name, :description, :created_by)
            ');

            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':created_by' => $userId
            ]);

            $groupId = (int) $this->pdo->lastInsertId();

            // Add the creator as a member
            $stmt = $this->pdo->prepare('
                INSERT INTO group_members (group_id, user_id)
                VALUES (:group_id, :user_id)
            ');

            $stmt->execute([
                ':group_id' => $groupId,
                ':user_id' => $userId
            ]);

            // Get the full group data
            $stmt = $this->pdo->prepare('
                SELECT id, name, description, created_at, created_by
                FROM groups
                WHERE id = :id
            ');

            $stmt->execute([':id' => $groupId]);
            $group = $stmt->fetch();

            if (!$group) {
                throw new PDOException('Failed to retrieve created group');
            }

            $this->pdo->commit();

            // Convert id and created_by to integers
            $group['id'] = (int) $group['id'];
            $group['created_by'] = (int) $group['created_by'];

            return $group;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // Add context to the error message
            throw new PDOException('Failed to create group: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getAllGroups(): array
    {
        try {
            $stmt = $this->pdo->query('
                SELECT id, name, description, created_at, created_by
                FROM groups
                ORDER BY created_at DESC
            ');

            $groups = $stmt->fetchAll();

            // Convert id and created_by to integers
            return array_map(function ($group) {
                $group['id'] = (int) $group['id'];
                $group['created_by'] = (int) $group['created_by'];
                return $group;
            }, $groups);
        } catch (PDOException $e) {
            // Add context to the error message
            throw new PDOException('Failed to get all groups: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getGroupById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT id, name, description, created_at, created_by
                FROM groups
                WHERE id = :id
            ');

            $stmt->execute([':id' => $id]);

            $group = $stmt->fetch();

            if (!$group) {
                return null;
            }

            // Convert id and created_by to integers
            $group['id'] = (int) $group['id'];
            $group['created_by'] = (int) $group['created_by'];

            return $group;
        } catch (PDOException $e) {
            // Add context to the error message
            throw new PDOException('Failed to get group by ID: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function joinGroup(int $groupId, int $userId): bool
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

            // Then check if the user exists
            $stmt = $this->pdo->prepare('
                SELECT 1 FROM users WHERE id = :user_id
            ');
            $stmt->execute([':user_id' => $userId]);

            if (!$stmt->fetchColumn()) {
                throw new PDOException("User with ID $userId does not exist");
            }

            // SQLite-friendly INSERT OR IGNORE to handle the case where the user is already a member
            $stmt = $this->pdo->prepare('
                INSERT OR IGNORE INTO group_members (group_id, user_id)
                VALUES (:group_id, :user_id)
            ');

            $stmt->execute([
                ':group_id' => $groupId,
                ':user_id' => $userId
            ]);

            // Return true if a row was inserted, false if the user was already a member
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Add context to the error message
            throw new PDOException('Failed to join group: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function isUserMember(int $groupId, int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT 1
                FROM group_members
                WHERE group_id = :group_id AND user_id = :user_id
            ');

            $stmt->execute([
                ':group_id' => $groupId,
                ':user_id' => $userId
            ]);

            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            // Add context to the error message
            throw new PDOException('Failed to check if user is a member: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getGroupMembers(int $groupId): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT u.id, u.username, gm.joined_at
                FROM group_members gm
                JOIN users u ON gm.user_id = u.id
                WHERE gm.group_id = :group_id
                ORDER BY gm.joined_at ASC
            ');

            $stmt->execute([':group_id' => $groupId]);

            $members = $stmt->fetchAll();

            // Convert id to integer
            return array_map(function ($member) {
                $member['id'] = (int) $member['id'];
                return $member;
            }, $members);
        } catch (PDOException $e) {
            // Add context to the error message
            throw new PDOException('Failed to get group members: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Check if the groups and group_members tables exist
     */
    public function checkTablesExist(): bool
    {
        try {
            $stmt = $this->pdo->query("
                SELECT COUNT(*) FROM sqlite_master 
                WHERE type='table' AND (name='groups' OR name='group_members')
            ");

            return $stmt->fetchColumn() == 2;
        } catch (PDOException $e) {
            return false;
        }
    }
}