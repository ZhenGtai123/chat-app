<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\MessageService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MessageController
{
    private MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    public function getMessages(Request $request, Response $response, array $args): Response
    {
        $groupId = (int) $args['id'];

        // Get query parameters
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? (int) $params['limit'] : 100;
        $offset = isset($params['offset']) ? (int) $params['offset'] : 0;

        // Check for since parameter (for polling)
        if (isset($params['since']) && !empty($params['since'])) {
            try {
                $messages = $this->messageService->getMessagesByGroupIdSince($groupId, $params['since']);

                $response->getBody()->write(json_encode([
                    'messages' => $messages,
                    'timestamp' => date('Y-m-d H:i:s')
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
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

        // Get paginated messages
        try {
            $messages = $this->messageService->getMessagesByGroupId($groupId, $limit, $offset);

            $response->getBody()->write(json_encode([
                'messages' => $messages,
                'timestamp' => date('Y-m-d H:i:s')
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
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

    public function createMessage(Request $request, Response $response, array $args): Response
    {
        $groupId = (int) $args['id'];
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        // Validate request data
        if (!isset($data['content']) || empty($data['content'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Message content is required'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        try {
            $message = $this->messageService->createMessage($groupId, $user['id'], $data['content']);

            $response->getBody()->write(json_encode($message));
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
                ->withStatus(403); // Forbidden
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