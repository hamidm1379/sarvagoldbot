<?php

/**
 * Script to create all database tables
 * Usage: php scripts/create_all_tables.php
 * 
 * This script creates all required tables for the bot
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

echo "🔄 Creating all database tables...\n\n";

try {
    // Initialize all models - this will create tables automatically
    echo "Creating users table...\n";
    $userModel = new \GoldSalekBot\Models\User();
    echo "✅ users table ready\n\n";
    
    echo "Creating admins table...\n";
    $adminModel = new \GoldSalekBot\Models\Admin();
    echo "✅ admins table ready\n\n";
    
    echo "Creating categories table...\n";
    $categoryModel = new \GoldSalekBot\Models\Category();
    echo "✅ categories table ready\n\n";
    
    echo "Creating collections table...\n";
    $collectionModel = new \GoldSalekBot\Models\Collection();
    echo "✅ collections table ready\n\n";
    
    echo "Creating products table...\n";
    $productModel = new \GoldSalekBot\Models\Product();
    echo "✅ products table ready\n\n";
    
    echo "Creating weight_ranges table...\n";
    $weightRangeModel = new \GoldSalekBot\Models\WeightRange();
    echo "✅ weight_ranges table ready\n\n";
    
    echo "Creating wage_ranges table...\n";
    $wageRangeModel = new \GoldSalekBot\Models\WageRange();
    echo "✅ wage_ranges table ready\n\n";
    
    echo "Creating contact_info table...\n";
    $contactModel = new \GoldSalekBot\Models\Contact();
    echo "✅ contact_info table ready\n\n";
    
    echo "✅ All tables created successfully!\n";
    echo "\n💡 Tip: Use 'php scripts/add_admin.php YOUR_TELEGRAM_ID' to add an admin.\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

