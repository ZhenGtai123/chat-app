<?php
declare(strict_types=1);

use App\Repositories\GroupRepository;
use App\Repositories\MessageRepository;
use App\Repositories\UserRepository;
use App\Services\GroupService;
use App\Services\MessageService;
use App\Services\UserService;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

return [
    // Database connection
    PDO::class => function () {
        $dbPath = $_ENV['DB_PATH'] ?? 'database/chat.sqlite';

        // Ensure directory exists
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        // Create file if it doesn't exist
        if (!file_exists($dbPath)) {
            file_put_contents($dbPath, '');

            // Initialize schema immediately if creating a new database
            $pdo = new PDO("sqlite:$dbPath");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $schemaPath = __DIR__ . '/../database/migrations/schema.sql';
            if (file_exists($schemaPath)) {
                $sql = file_get_contents($schemaPath);
                $pdo->exec($sql);
            }

            return $pdo;
        }

        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    },

    // Repositories
    UserRepository::class => function (ContainerInterface $container) {
        return new UserRepository($container->get(PDO::class));
    },

    GroupRepository::class => function (ContainerInterface $container) {
        return new GroupRepository($container->get(PDO::class));
    },

    MessageRepository::class => function (ContainerInterface $container) {
        return new MessageRepository($container->get(PDO::class));
    },

    // Services
    UserService::class => function (ContainerInterface $container) {
        return new UserService($container->get(UserRepository::class));
    },

    GroupService::class => function (ContainerInterface $container) {
        return new GroupService(
            $container->get(GroupRepository::class),
            $container->get(UserRepository::class)
        );
    },

    MessageService::class => function (ContainerInterface $container) {
        return new MessageService(
            $container->get(MessageRepository::class),
            $container->get(GroupRepository::class),
            $container->get(UserRepository::class)
        );
    },
];