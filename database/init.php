<?php
declare(strict_types=1);

// Load environment variables
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Database path
$dbPath = $_ENV['DB_PATH'];
$dbDir = dirname($dbPath);

// Create directory if it doesn't exist
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

echo "Initializing database at: $dbPath\n";

// Initialize SQLite database
try {
    // Check if file exists, if not create it
    if (!file_exists($dbPath)) {
        file_put_contents($dbPath, '');
        echo "Created new database file\n";
    }

    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Execute migration script
    $sql = file_get_contents(__DIR__ . '/migrations/schema.sql');

    // Split SQL into individual statements for better error reporting
    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "Executed statement: " . substr($statement, 0, 40) . "...\n";
            } catch (PDOException $e) {
                echo "Error executing statement: $statement\n";
                echo "Error: " . $e->getMessage() . "\n";
                // Continue with other statements
            }
        }
    }

    // Verify tables were created
    $tables = [
        'users',
        'groups',
        'group_members',
        'messages'
    ];

    echo "Verifying tables...\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
        if ($stmt->fetchColumn()) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' does not exist!\n";
        }
    }

    echo "Database initialization completed successfully\n";
} catch (PDOException $e) {
    echo "Database initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}