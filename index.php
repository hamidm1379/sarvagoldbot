<?php

/**
 * Gold Salek Telegram Bot
 * Entry Point
 */

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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

// Set timezone
date_default_timezone_set('Asia/Tehran');

// Error reporting (disable in production)
if (getenv('DEBUG_MODE') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Autoloader (case-insensitive for Linux compatibility)
spl_autoload_register(function ($class) {
    // Skip if class already exists
    if (class_exists($class, false)) {
        return;
    }
    
    $prefix = 'GoldSalekBot\\';
    $baseDir = __DIR__ . '/src/';
    
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

// Get bot token
$botToken = getenv('BOT_TOKEN');
if (!$botToken) {
    error_log("Bot token not found in environment variables");
    http_response_code(500);
    die("Bot token not configured");
}

// Get update from Telegram
$update = file_get_contents('php://input');

if (empty($update)) {
    http_response_code(400);
    die("No update received");
}

// Initialize bot
try {
    $bot = new \GoldSalekBot\Bot($botToken);
    $bot->handleUpdate($update);
} catch (\Exception $e) {
    error_log("Bot error: " . $e->getMessage());
    http_response_code(500);
    die("Internal server error");
}

http_response_code(200);
echo "OK";

