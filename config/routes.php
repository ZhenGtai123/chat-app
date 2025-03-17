<?php
declare(strict_types=1);

use App\Controllers\GroupController;
use App\Controllers\MessageController;
use App\Controllers\UserController;
use App\Middleware\UserAuthMiddleware;
use Slim\App;

return function (App $app) {
    // User routes
    $app->post('/users', [UserController::class, 'createUser']);
    $app->get('/users/{username}', [UserController::class, 'getUser']);

    // Add authorization middleware to protected routes
    $app->group('', function ($app) {
        // Group routes
        $app->get('/groups', [GroupController::class, 'getGroups']);
        $app->post('/groups', [GroupController::class, 'createGroup']);
        $app->get('/groups/{id}', [GroupController::class, 'getGroup']);
        $app->post('/groups/{id}/join', [GroupController::class, 'joinGroup']);

        // Message routes
        $app->get('/groups/{id}/messages', [MessageController::class, 'getMessages']);
        $app->post('/groups/{id}/messages', [MessageController::class, 'createMessage']);
    })->add(UserAuthMiddleware::class);
};