<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function createUser(string $username): array
    {
        // Validate username
        if (empty($username) || strlen($username) < 3 || strlen($username) > 20) {
            throw new \InvalidArgumentException('Username must be between 3 and 20 characters');
        }

        // Check if username contains only valid characters
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new \InvalidArgumentException('Username may only contain letters, numbers, and underscores');
        }

        // Check if user already exists
        $existingUser = $this->userRepository->getUserByUsername($username);
        if ($existingUser) {
            throw new \RuntimeException('Username is already taken');
        }

        // Create user
        return $this->userRepository->createUser($username);
    }

    public function getUserByUsername(string $username): ?array
    {
        return $this->userRepository->getUserByUsername($username);
    }

    public function getUserById(int $id): ?array
    {
        return $this->userRepository->getUserById($id);
    }

    public function getUserByToken(string $token): ?array
    {
        return $this->userRepository->getUserByToken($token);
    }
}