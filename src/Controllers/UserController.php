<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function createUser(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Validate request data
        if (!isset($data['username']) || empty($data['username'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Username is required'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        try {
            $user = $this->userService->createUser($data['username']);

            $response->getBody()->write(json_encode($user));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(409); // Conflict
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'An unexpected error occurred'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    public function getUser(Request $request, Response $response, array $args): Response
    {
        $username = $args['username'];

        $user = $this->userService->getUserByUsername($username);

        if (!$user) {
            $response->getBody()->write(json_encode([
                'error' => 'User not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // Remove sensitive information
        unset($user['api_token']);

        $response->getBody()->write(json_encode($user));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}