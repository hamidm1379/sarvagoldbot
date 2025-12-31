<?php

/**
 * Check if level column exists in users table
 * Usage: php scripts/check_user_level_column.php
 */

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!empty($name)) {
            $_ENV[$name] = $value;
            putenv($name . '=' . $value);
        }
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'gold_salek_bot';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

echo "🔍 Checking database configuration...\n";
echo "Database: {$dbname}\n";
echo "Host: {$host}\n";
echo "User: {$username}\n\n";

try {
    // Connect to MariaDB/MySQL database
    // Note: mysql: DSN works with both MySQL and MariaDB
    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Connected to database successfully!\n\n";
    
    // Check if column exists
    $checkSql = "SHOW COLUMNS FROM `users` LIKE 'level'";
    $stmt = $pdo->query($checkSql);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Column 'level' EXISTS in 'users' table.\n\n";
        
        // Show column details
        $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Column details:\n";
        foreach ($columnInfo as $key => $value) {
            echo "  {$key}: {$value}\n";
        }
        
        // Check existing user levels
        $usersSql = "SELECT COUNT(*) as total, level, COUNT(*) as count FROM users GROUP BY level";
        $usersStmt = $pdo->query($usersSql);
        $levels = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n📊 User levels distribution:\n";
        foreach ($levels as $level) {
            echo "  {$level['level']}: {$level['count']} users\n";
        }
        
    } else {
        echo "❌ Column 'level' DOES NOT EXIST in 'users' table.\n";
        echo "   You need to run the migration:\n";
        echo "   php scripts/run_migration_user_level.php\n";
        exit(1);
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

