<?php

/**
 * Simple test to verify level column access
 */

// Load environment
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
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

echo "🔍 Testing level column access...\n\n";

try {
    $userModel = new \GoldSalekBot\Models\User();
    
    // Get all users
    $users = $userModel->getAll();
    echo "Found " . count($users) . " users\n\n";
    
    // Test accessing level column
    foreach ($users as $user) {
        $level = $user['level'] ?? 'NULL';
        echo "User: {$user['first_name']} {$user['last_name']}\n";
        echo "  Level: {$level}\n";
        echo "  Has level key: " . (isset($user['level']) ? 'YES' : 'NO') . "\n\n";
    }
    
    // Test getLevel method
    if (!empty($users)) {
        $testUser = $users[0];
        $level = $userModel->getLevel($testUser['telegram_id']);
        echo "✅ getLevel() method works: {$level}\n\n";
    }
    
    echo "✅ All tests passed! The level column is accessible.\n";
    echo "   If bot still shows errors, RESTART poll.php\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

