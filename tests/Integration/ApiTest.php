<?php
declare(strict_types=1);

namespace Tests\Integration;

use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class ApiTest extends TestCase
{
    private $app;
    private $container;

    protected function setUp(): void
    {
        // Create test environment
        $containerBuilder = new ContainerBuilder();

        // Use in-memory SQLite database for tests
        $containerBuilder->addDefinitions([
            \PDO::class => function() {
                $pdo = new \PDO('sqlite::memory:');
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

                // Initialize schema
                $sql = file_get_contents(__DIR__ . '/../../database/migrations/schema.sql');
                $pdo->exec($sql);

                return $pdo;
            },
        ]);

        // Load application container definitions
        $containerBuilder->addDefinitions(__DIR__ . '/../../config/container.php');
        $this->container = $containerBuilder->build();

        // Create app with container
        AppFactory::setContainer($this->container);
        $this->app = AppFactory::create();

        // Add middleware
        $this->app->addBodyParsingMiddleware();
        $this->app->addErrorMiddleware(true, true, true);

        // Register routes
        (require __DIR__ . '/../../config/routes.php')($this->app);
    }

    /**
     * Test creating a user
     *
     * @return array User data with API token
     */
    public function testCreateUser()
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/users')
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody(['username' => 'testuser']);

        $response = $this->app->handle($request);

        $this->assertEquals(201, $response->getStatusCode());

        $userData = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($userData);
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('username', $userData);
        $this->assertArrayHasKey('api_token', $userData);
        $this->assertEquals('testuser', $userData['username']);

        return $userData;
    }

    /**
     * Test creating a group
     *
     * @depends testCreateUser
     * @return array Combined user and group data
     */
    public function testCreateGroup(array $user)
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/groups')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-API-Token', $user['api_token'])
            ->withParsedBody([
                'name' => 'Test Group',
                'description' => 'This is a test group'
            ]);

        $response = $this->app->handle($request);

        $this->assertEquals(201, $response->getStatusCode());

        $groupData = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($groupData);
        $this->assertArrayHasKey('id', $groupData);
        $this->assertArrayHasKey('name', $groupData);
        $this->assertEquals('Test Group', $groupData['name']);

        return [
            'user' => $user,
            'group' => $groupData
        ];
    }

    /**
     * Test getting all groups
     *
     * @depends testCreateUser
     */
    public function testGetGroups(array $user): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/groups')
            ->withHeader('X-API-Token', $user['api_token']);

        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($responseData);
        $this->assertGreaterThanOrEqual(1, count($responseData));
    }

    /**
     * Test creating a message
     *
     * @depends testCreateGroup
     * @return array Combined user, group and message data
     */
    public function testCreateMessage(array $data)
    {
        $user = $data['user'];
        $group = $data['group'];

        $request = (new ServerRequestFactory())->createServerRequest(
            'POST',
            '/groups/' . $group['id'] . '/messages'
        )
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-API-Token', $user['api_token'])
            ->withParsedBody([
                'content' => 'Hello, world!'
            ]);

        $response = $this->app->handle($request);

        $this->assertEquals(201, $response->getStatusCode());

        $messageData = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($messageData);
        $this->assertArrayHasKey('id', $messageData);
        $this->assertArrayHasKey('content', $messageData);
        $this->assertEquals('Hello, world!', $messageData['content']);
        $this->assertEquals($group['id'], $messageData['group_id']);
        $this->assertEquals($user['id'], $messageData['user_id']);

        return [
            'user' => $user,
            'group' => $group,
            'message' => $messageData
        ];
    }

    /**
     * Test getting messages from a group
     *
     * @depends testCreateMessage
     */
    public function testGetMessages(array $data): void
    {
        $user = $data['user'];
        $group = $data['group'];

        $request = (new ServerRequestFactory())->createServerRequest(
            'GET',
            '/groups/' . $group['id'] . '/messages'
        )->withHeader('X-API-Token', $user['api_token']);

        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('messages', $responseData);
        $this->assertArrayHasKey('timestamp', $responseData);
        $this->assertIsArray($responseData['messages']);
        $this->assertCount(1, $responseData['messages']);
        $this->assertEquals('Hello, world!', $responseData['messages'][0]['content']);
    }

    /**
     * Test joining a group
     *
     * @depends testCreateGroup
     */
    public function testJoinGroup(array $data): void
    {
        // Create a second user
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/users')
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody(['username' => 'seconduser']);

        $response = $this->app->handle($request);
        $secondUser = json_decode((string) $response->getBody(), true);

        // Second user joins the group
        $request = (new ServerRequestFactory())->createServerRequest(
            'POST',
            '/groups/' . $data['group']['id'] . '/join'
        )->withHeader('X-API-Token', $secondUser['api_token']);

        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        // Check that user is in the group
        $request = (new ServerRequestFactory())->createServerRequest(
            'GET',
            '/groups/' . $data['group']['id']
        )->withHeader('X-API-Token', $secondUser['api_token']);

        $response = $this->app->handle($request);
        $groupData = json_decode((string) $response->getBody(), true);

        $this->assertCount(2, $groupData['members']);
    }

    /**
     * Test authentication failure
     */
    public function testAuthenticationFailure(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/groups');

        $response = $this->app->handle($request);

        $this->assertEquals(401, $response->getStatusCode());

        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    /**
     * Test invalid token
     */
    public function testInvalidToken(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/groups')
            ->withHeader('X-API-Token', 'invalid_token');

        $response = $this->app->handle($request);

        $this->assertEquals(401, $response->getStatusCode());

        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    /**
     * Test resource not found
     *
     * @depends testCreateUser
     */
    public function testResourceNotFound(array $user): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/groups/999')
            ->withHeader('X-API-Token', $user['api_token']);

        $response = $this->app->handle($request);

        $this->assertEquals(404, $response->getStatusCode());

        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('error', $responseData);
    }
}