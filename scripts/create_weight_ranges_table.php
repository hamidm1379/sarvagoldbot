<?php

/**
 * Script to create weight_ranges table
 * Usage: php scripts/create_weight_ranges_table.php
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

// Autoloader
spl_autoload_register(function ($class) {
    if (class_exists($class, false)) {
        return;
    }
    
    $prefix = 'GoldSalekBot\\';
    $baseDir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

try {
    $db = \GoldSalekBot\Database::getInstance();
    $connection = $db->getConnection();
    
    echo "🔄 Creating weight_ranges table...\n\n";
    
    // Create weight_ranges table directly
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS `weight_ranges` (
      `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(255) NOT NULL,
      `min_weight` DECIMAL(10,2) NOT NULL,
      `max_weight` DECIMAL(10,2) NOT NULL,
      `category_id` INT(11) UNSIGNED DEFAULT NULL,
      `collection_id` INT(11) UNSIGNED DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      INDEX `idx_category_id` (`category_id`),
      INDEX `idx_collection_id` (`collection_id`),
      INDEX `idx_weight_range` (`min_weight`, `max_weight`),
      FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
      FOREIGN KEY (`collection_id`) REFERENCES `collections`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    try {
        $connection->exec("SET NAMES utf8mb4");
        $connection->exec("SET FOREIGN_KEY_CHECKS = 0");
        $connection->exec($createTableSQL);
        $connection->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "✅ Table creation SQL executed\n";
    } catch (PDOException $e) {
        // Ignore "table already exists" errors
        if (strpos($e->getMessage(), 'already exists') === false && 
            strpos($e->getMessage(), 'Duplicate') === false) {
            echo "⚠️  Warning: " . $e->getMessage() . "\n";
            throw $e;
        } else {
            echo "ℹ️  Table already exists, skipping creation...\n";
        }
    }
    
    // Verify table was created
    try {
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM weight_ranges");
        echo "\n✅ weight_ranges table created successfully!\n";
        echo "   Records in table: {$result['count']}\n";
    } catch (PDOException $e) {
        echo "\n❌ Error verifying table: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    echo "\n✅ Migration completed!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

