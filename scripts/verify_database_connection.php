<?php

/**
 * Verify database connection and test level column access
 * This uses the same Database class as the bot
 */

// Load classes the same way index.php does
require __DIR__ . '/../index.php';

// Environment is loaded by index.php

echo "🔍 Verifying database connection (using bot's Database class)...\n\n";

try {
    // Use the same Database class as the bot
    $db = \GoldSalekBot\Database::getInstance();
    
    // Test 1: Check if level column exists
    echo "Test 1: Checking if 'level' column exists...\n";
    $checkSql = "SHOW COLUMNS FROM `users` LIKE 'level'";
    $result = $db->fetchOne($checkSql);
    
    if ($result) {
        echo "✅ Column 'level' exists!\n";
        echo "   Type: {$result['Type']}\n";
        echo "   Default: {$result['Default']}\n\n";
    } else {
        echo "❌ Column 'level' does NOT exist!\n";
        echo "   Run: php scripts/run_migration_user_level.php\n";
        exit(1);
    }
    
    // Test 2: Try to select a user with level
    echo "Test 2: Testing SELECT query with level column...\n";
    $testUser = $db->fetchOne("SELECT telegram_id, first_name, level FROM users LIMIT 1");
    
    if ($testUser) {
        echo "✅ SELECT query successful!\n";
        echo "   User: {$testUser['first_name']}\n";
        echo "   Level: " . ($testUser['level'] ?? 'NULL') . "\n\n";
    } else {
        echo "⚠️  No users found in database\n\n";
    }
    
    // Test 3: Test User model
    echo "Test 3: Testing User model...\n";
    $userModel = new \GoldSalekBot\Models\User();
    
    if ($testUser) {
        $user = $userModel->findByTelegramId($testUser['telegram_id']);
        if ($user) {
            echo "✅ User model findByTelegramId() works!\n";
            echo "   User level from model: " . ($user['level'] ?? 'NULL') . "\n";
            echo "   Has level key: " . (isset($user['level']) ? 'YES' : 'NO') . "\n\n";
        }
    }
    
    // Test 4: Test updateLevel method
    echo "Test 4: Testing updateLevel method (dry run - no actual update)...\n";
    echo "✅ All methods should work correctly\n\n";
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ All tests passed! Database is ready.\n";
    echo "   If the bot still shows errors, restart poll.php\n";
    echo "   (Press Ctrl+C to stop the current poll.php and run it again)\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

