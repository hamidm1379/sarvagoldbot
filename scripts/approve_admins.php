<?php

/**
 * Auto-approve all admins
 * Usage: php scripts/approve_admins.php
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

// Autoloader (case-insensitive for Linux compatibility)
spl_autoload_register(function ($class) {
    $prefix = 'GoldSalekBot\\';
    $baseDir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // Try exact path first
    if (file_exists($file)) {
        require $file;
        return;
    }
    
    // Case-insensitive search for directory structure
    $parts = explode('/', str_replace('\\', '/', $relativeClass));
    $fileName = array_pop($parts);
    $currentPath = $baseDir;
    
    // Navigate through directories case-insensitively
    foreach ($parts as $part) {
        if (empty($part)) continue;
        
        $found = false;
        if (is_dir($currentPath)) {
            $items = scandir($currentPath);
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($currentPath . $item) && strcasecmp($item, $part) === 0) {
                    $currentPath .= $item . '/';
                    $found = true;
                    break;
                }
            }
        }
        
        if (!$found) {
            return; // Directory not found
        }
    }
    
    // Find file case-insensitively
    if (is_dir($currentPath)) {
        $files = scandir($currentPath);
        foreach ($files as $foundFile) {
            if (strcasecmp($foundFile, $fileName . '.php') === 0) {
                require $currentPath . $foundFile;
                return;
            }
        }
    }
});

try {
    $adminModel = new \GoldSalekBot\Models\Admin();
    $userModel = new \GoldSalekBot\Models\User();
    $db = \GoldSalekBot\Database::getInstance();
    
    // Get all admins
    $sql = "SELECT telegram_id FROM admins";
    $admins = $db->fetchAll($sql);
    
    if (empty($admins)) {
        echo "⚠️  No admins found in database.\n";
        exit(1);
    }
    
    echo "📋 Found " . count($admins) . " admin(s)\n\n";
    
    $approved = 0;
    $created = 0;
    
    foreach ($admins as $admin) {
        $telegramId = $admin['telegram_id'];
        $user = $userModel->findByTelegramId($telegramId);
        
        if ($user) {
            // Update status to approved
            if ($user['status'] !== 'approved') {
                $userModel->updateStatus($telegramId, 'approved');
                echo "✅ Approved user: {$telegramId} ({$user['first_name']} {$user['last_name']})\n";
                $approved++;
            } else {
                echo "ℹ️  Already approved: {$telegramId} ({$user['first_name']} {$user['last_name']})\n";
            }
        } else {
            // Create user with approved status
            $internalId = 'ADMIN-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $sql = "INSERT INTO users (telegram_id, first_name, last_name, internal_id, status) 
                    VALUES (?, 'Admin', '', ?, 'approved')";
            $db->query($sql, [$telegramId, $internalId]);
            echo "➕ Created and approved admin: {$telegramId}\n";
            $created++;
        }
    }
    
    echo "\n✅ Done!\n";
    echo "   Approved: {$approved}\n";
    echo "   Created: {$created}\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

