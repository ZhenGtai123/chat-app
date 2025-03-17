<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\GroupRepository;
use App\Repositories\MessageRepository;
use App\Repositories\UserRepository;

class MessageService
{
    private MessageRepository $messageRepository;
    private GroupRepository $groupRepository;
    private UserRepository $userRepository;

    public function __construct(
        MessageRepository $messageRepository,
        GroupRepository $groupRepository,
        UserRepository $userRepository
    ) {
        $this->messageRepository = $messageRepository;
        $this->groupRepository = $groupRepository;
        $this->userRepository = $userRepository;
    }

    public function createMessage(int $groupId, int $userId, string $content): array
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

        // Check if user is a member of the group
        if (!$this->groupRepository->isUserMember($groupId, $userId)) {
            throw new \RuntimeException('User is not a member of this group');
        }

        // Validate message content
        if (empty($content) || strlen($content) > 1000) {
            throw new \InvalidArgumentException('Message content must not be empty and less than 1000 characters');
        }

        // Create message
        return $this->messageRepository->createMessage($groupId, $userId, $content);
    }

    public function getMessagesByGroupId(int $groupId, ?int $limit = 100, ?int $offset = 0): array
    {
        // Validate group
        $group = $this->groupRepository->getGroupById($groupId);
        if (!$group) {
            throw new \InvalidArgumentException('Group not found');
        }

        return $this->messageRepository->getMessagesByGroupId($groupId, $limit, $offset);
    }

    public function getMessagesByGroupIdSince(int $groupId, string $timestamp): array
    {
        // Validate group
        $group = $this->groupRepository->getGroupById($groupId);
        if (!$group) {
            throw new \InvalidArgumentException('Group not found');
        }

        return $this->messageRepository->getMessagesByGroupIdSince($groupId, $timestamp);
    }
}