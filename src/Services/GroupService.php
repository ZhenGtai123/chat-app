<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;

class GroupService
{
    private GroupRepository $groupRepository;
    private UserRepository $userRepository;

    public function __construct(
        GroupRepository $groupRepository,
        UserRepository $userRepository
    ) {
        $this->groupRepository = $groupRepository;
        $this->userRepository = $userRepository;
    }

    public function createGroup(string $name, string $description, int $userId): array
    {
        // Validate group name
        if (empty($name) || strlen($name) < 3 || strlen($name) > 50) {
            throw new \InvalidArgumentException('Group name must be between 3 and 50 characters');
        }

        // Validate user
        $user = $this->userRepository->getUserById($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        // Create group
        return $this->groupRepository->createGroup($name, $description, $userId);
    }

    public function getAllGroups(): array
    {
        return $this->groupRepository->getAllGroups();
    }

    public function getGroupById(int $id): ?array
    {
        $group = $this->groupRepository->getGroupById($id);

        if (!$group) {
            return null;
        }

        // Add members to group data
        $group['members'] = $this->groupRepository->getGroupMembers($id);

        return $group;
    }

    public function joinGroup(int $groupId, int $userId): bool
    {
        // Validate group
        $group = $this->groupRepository->getGroupById($groupId);
        if (!$group) {
            throw new \InvalidArgumentException('Group not found');
        }

        // Validate user
        $user = $this->userRepository->getUserById($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        // Check if user is already a member
        if ($this->groupRepository->isUserMember($groupId, $userId)) {
            return true; // User is already a member
        }

        // Join group
        return $this->groupRepository->joinGroup($groupId, $userId);
    }

    public function isUserMember(int $groupId, int $userId): bool
    {
        return $this->groupRepository->isUserMember($groupId, $userId);
    }
}