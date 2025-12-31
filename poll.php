<?php

/**
 * Long Polling Script for Testing
 * Usage: php poll.php
 * 
 * Note: This is for testing only. Use Webhook for production.
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

// Error reporting
if (getenv('DEBUG_MODE') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Autoloader (case-insensitive for Linux compatibility)
spl_autoload_register(function ($class) {
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

$botToken = getenv('BOT_TOKEN');
if (!$botToken) {
    die("❌ Bot token not found in .env file!\n");
}

echo "🤖 Bot is running with Long Polling...\n";
echo "📱 Bot Token: " . substr($botToken, 0, 10) . "...\n";
echo "⏹️  Press Ctrl+C to stop.\n\n";

$bot = new \GoldSalekBot\Bot($botToken);

$offset = 0;
$errorCount = 0;
$maxErrors = 10;

while (true) {
    try {
        $url = "https://api.telegram.org/bot{$botToken}/getUpdates?offset={$offset}&timeout=10";
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $errorCount++;
            if ($errorCount >= $maxErrors) {
                echo "❌ Too many errors. Exiting...\n";
                break;
            }
            echo "⚠️  Connection error. Retrying...\n";
            sleep(2);
            continue;
        }
        
        $errorCount = 0; // Reset error count on success
        
        $data = json_decode($response, true);
        
        if (!$data || !$data['ok']) {
            echo "⚠️  API Error: " . ($data['description'] ?? 'Unknown error') . "\n";
            sleep(2);
            continue;
        }
        
        if (!empty($data['result'])) {
            foreach ($data['result'] as $update) {
                echo "📨 Received update #{$update['update_id']}\n";
                $bot->handleUpdate(json_encode($update));
                $offset = $update['update_id'] + 1;
            }
        }
        
        usleep(500000); // 0.5 second delay
        
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        sleep(2);
    }
}

echo "\n👋 Bot stopped.\n";

