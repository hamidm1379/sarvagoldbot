<?php

/**
 * Script to add admin to the bot
 * Usage: php scripts/add_admin.php <telegram_id> [username]
 */

// Load environment variables (lightweight replacement for index.php)
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0 || strpos($line, '=') === false) {
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

// PSR-4 autoloader (case-insensitive for Linux compatibility)
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

    // Try exact path first
    if (file_exists($file)) {
        require_once $file;
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
                require_once $currentPath . $foundFile;
                return;
            }
        }
    }
});

$telegramId = $argv[1] ?? null;
$username = $argv[2] ?? null;

if (!$telegramId) {
    echo "Usage: php scripts/add_admin.php <telegram_id> [username]\n";
    echo "Example: php scripts/add_admin.php 123456789 admin\n";
    exit(1);
}

try {
    $adminModel = new \GoldSalekBot\Models\Admin();
    $adminModel->addAdmin($telegramId, $username);
    echo "✅ Admin added successfully!\n";
    echo "Telegram ID: {$telegramId}\n";
    if ($username) {
        echo "Username: {$username}\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

