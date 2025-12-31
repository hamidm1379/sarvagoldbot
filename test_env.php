<?php

/**
 * Test .env file loading
 */

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

echo "✅ .env file loaded!\n\n";
echo "BOT_TOKEN: " . (getenv('BOT_TOKEN') ?: 'NOT FOUND') . "\n";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NOT FOUND') . "\n";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'NOT FOUND') . "\n";
echo "DB_USER: " . (getenv('DB_USER') ?: 'NOT FOUND') . "\n";
echo "DEBUG_MODE: " . (getenv('DEBUG_MODE') ?: 'NOT FOUND') . "\n";

