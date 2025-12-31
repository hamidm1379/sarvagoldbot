<?php

/**
 * Run migration: Add level column to users table
 * Usage: php scripts/run_migration_user_level.php
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

echo "📊 Running migration: Add level column to users table...\n";
echo "Database: {$dbname}\n";
echo "Host: {$host}\n\n";

try {
    // Connect to MariaDB/MySQL database
    // Note: mysql: DSN works with both MySQL and MariaDB
    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Check if column already exists
    $checkSql = "SHOW COLUMNS FROM `users` LIKE 'level'";
    $stmt = $pdo->query($checkSql);
    if ($stmt->rowCount() > 0) {
        echo "✅ Column 'level' already exists in 'users' table.\n";
        echo "   Migration already applied.\n";
        exit(0);
    }
    
    // Read migration file
    $migrationFile = __DIR__ . '/../database/migration_add_user_level.sql';
    if (!file_exists($migrationFile)) {
        die("❌ Migration file not found: {$migrationFile}\n");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Execute migration
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Remove comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement)) {
            continue;
        }
        if (stripos($statement, 'SET NAMES') !== false || 
            stripos($statement, 'SET FOREIGN_KEY') !== false) {
            continue; // Already executed
        }
        
        try {
            echo "🔧 Executing: " . substr($statement, 0, 80) . "...\n";
            $pdo->exec($statement);
            echo "✅ Successfully executed!\n";
        } catch (PDOException $e) {
            // Check if it's "duplicate column" or "duplicate key" error
            if (strpos($e->getMessage(), 'Duplicate column') !== false ||
                strpos($e->getMessage(), 'Duplicate key') !== false) {
                echo "⚠️  Already exists (ignoring): " . substr($statement, 0, 60) . "...\n";
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n✅ Migration completed successfully!\n";
    echo "   Column 'level' added to 'users' table.\n";
    echo "   All existing users have been set to 'general' level.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

