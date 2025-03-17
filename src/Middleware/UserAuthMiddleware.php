<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Services\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;

class UserAuthMiddleware implements MiddlewareInterface
{
    private UserService $userService;
    private ResponseFactory $responseFactory;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->responseFactory = new ResponseFactory();
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        // Check for API token in headers
        $token = $request->getHeaderLine('X-API-Token');

        if (empty($token)) {
            $response = $this->responseFactory->createResponse(401);
            $response->getBody()->write(json_encode([
                'error' => 'Authentication required'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // Verify token and get user
        $user = $this->userService->getUserByToken($token);

        if (!$user) {
            $response = $this->responseFactory->createResponse(401);
            $response->getBody()->write(json_encode([
                'error' => 'Invalid API token'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // Add user to request attributes
        $request = $request->withAttribute('user', $user);

        // Process the request
        return $handler->handle($request);
    }
}