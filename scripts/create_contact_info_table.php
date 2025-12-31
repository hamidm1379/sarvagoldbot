<?php

/**
 * Script to create contact_info table
 * Usage: php scripts/create_contact_info_table.php
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
    
    echo "🔄 Creating contact_info table...\n\n";
    
    // Create contact_info table
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS `contact_info` (
      `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `address` TEXT NOT NULL,
      `phone` VARCHAR(50) NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    try {
        $connection->exec("SET NAMES utf8mb4");
        $connection->exec($createTableSQL);
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
    
    // Insert default contact info (only if table is empty)
    try {
        $existing = $db->fetchOne("SELECT COUNT(*) as count FROM contact_info");
        if ($existing['count'] == 0) {
            $insertSQL = "INSERT INTO `contact_info` (`address`, `phone`) VALUES (?, ?)";
            $db->query($insertSQL, [
                'بازار بزرگ تهران پاساژ طلا و جواهر خرداد طبقه همکف پلاک 68',
                '02155612268'
            ]);
            echo "✅ Default contact info inserted\n";
        } else {
            echo "ℹ️  Contact info already exists, skipping insert...\n";
        }
    } catch (PDOException $e) {
        echo "⚠️  Warning inserting default data: " . $e->getMessage() . "\n";
    }
    
    // Verify table was created
    try {
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM contact_info");
        $contact = $db->fetchOne("SELECT * FROM contact_info LIMIT 1");
        echo "\n✅ contact_info table created successfully!\n";
        echo "   Records in table: {$result['count']}\n";
        if ($contact) {
            echo "   Address: {$contact['address']}\n";
            echo "   Phone: {$contact['phone']}\n";
        }
    } catch (PDOException $e) {
        echo "\n❌ Error verifying table: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    echo "\n✅ Migration completed!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
