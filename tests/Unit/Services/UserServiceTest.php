<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\UserRepository;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    private $userRepository;
    private $userService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userService = new UserService($this->userRepository);
    }

    public function testCreateUserWithValidUsername()
    {
        $username = 'testuser';
        $expectedUser = [
            'id' => 1,
            'username' => $username,
            'api_token' => 'sometoken',
            'created_at' => '2023-01-01 00:00:00'
        ];

        $this->userRepository->expects($this->once())
            ->method('getUserByUsername')
            ->with($username)
            ->willReturn(null);

        $this->userRepository->expects($this->once())
            ->method('createUser')
            ->with($username)
            ->willReturn($expectedUser);

        $result = $this->userService->createUser($username);

        $this->assertEquals($expectedUser, $result);
    }

    public function testCreateUserWithInvalidUsername()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->userService->createUser('ab'); // Too short
    }

    public function testCreateUserWithInvalidCharacters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->userService->createUser('test@user'); // Contains invalid character
    }

    public function testCreateUserWithExistingUsername()
    {
        $username = 'existinguser';
        $existingUser = [
            'id' => 1,
            'username' => $username,
            'api_token' => 'sometoken',
            'created_at' => '2023-01-01 00:00:00'
        ];

        $this->userRepository->expects($this->once())
            ->method('getUserByUsername')
            ->with($username)
            ->willReturn($existingUser);

        $this->expectException(\RuntimeException::class);
        $this->userService->createUser($username);
    }

    public function testGetUserByUsername()
    {
        $username = 'testuser';
        $expectedUser = [
            'id' => 1,
            'username' => $username,
            'api_token' => 'sometoken',
            'created_at' => '2023-01-01 00:00:00'
        ];

        $this->userRepository->expects($this->once())
            ->method('getUserByUsername')
            ->with($username)
            ->willReturn($expectedUser);

        $result = $this->userService->getUserByUsername($username);

        $this->assertEquals($expectedUser, $result);
    }

    public function testGetUserByUsernameNotFound()
    {
        $username = 'nonexistentuser';

        $this->userRepository->expects($this->once())
            ->method('getUserByUsername')
            ->with($username)
            ->willReturn(null);

        $result = $this->userService->getUserByUsername($username);

        $this->assertNull($result);
    }

    public function testGetUserByToken()
    {
        $token = 'validtoken';
        $expectedUser = [
            'id' => 1,
            'username' => 'testuser',
            'api_token' => $token,
            'created_at' => '2023-01-01 00:00:00'
        ];

        $this->userRepository->expects($this->once())
            ->method('getUserByToken')
            ->with($token)
            ->willReturn($expectedUser);

        $result = $this->userService->getUserByToken($token);

        $this->assertEquals($expectedUser, $result);
    }

    public function testGetUserByTokenNotFound()
    {
        $token = 'invalidtoken';

        $this->userRepository->expects($this->once())
            ->method('getUserByToken')
            ->with($token)
            ->willReturn(null);

        $result = $this->userService->getUserByToken($token);

        $this->assertNull($result);
    }
}