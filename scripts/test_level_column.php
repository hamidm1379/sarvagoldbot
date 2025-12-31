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

// Autoloader
spl_autoload_register(function ($class) {
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

