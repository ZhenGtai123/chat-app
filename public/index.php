<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Set up dependency injection container
$containerBuilder = new ContainerBuilder();
if ($_ENV['APP_ENV'] === 'production') {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

// application container
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
$container = $containerBuilder->build();

// Create app
AppFactory::setContainer($container);
$app = AppFactory::create();

// middleware
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(
    (bool)$_ENV['APP_DEBUG'],
    true,
    true
);

// routes
(require __DIR__ . '/../config/routes.php')($app);


$app->run();