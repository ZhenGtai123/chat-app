<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\GroupService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GroupController
{
    private GroupService $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function getGroups(Request $request, Response $response): Response
    {
        $groups = $this->groupService->getAllGroups();

        $response->getBody()->write(json_encode($groups));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function getGroup(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        $group = $this->groupService->getGroupById($id);

        if (!$group) {
            $response->getBody()->write(json_encode([
                'error' => 'Group not found'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $response->getBody()->write(json_encode($group));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function createGroup(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        // Validate request data
        if (!isset($data['name']) || empty($data['name'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Group name is required'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $description = $data['description'] ?? '';

        try {
            $group = $this->groupService->createGroup($data['name'], $description, $user['id']);

            $response->getBody()->write(json_encode($group));
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
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'An unexpected error occurred'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    public function joinGroup(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $request->getAttribute('user');

        try {
            $joined = $this->groupService->joinGroup($id, $user['id']);

            if ($joined) {
                $response->getBody()->write(json_encode([
                    'message' => 'Successfully joined the group'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            } else {
                $response->getBody()->write(json_encode([
                    'message' => 'Already a member of the group'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            }
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'An unexpected error occurred'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}