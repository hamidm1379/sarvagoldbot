<?php

// Test keyboard structure
$keyboard = [
    'keyboard' => [
        [
            ['text' => '📦 محصولات'],
            ['text' => '🔍 جستجو با کد محصول']
        ],
        [
            ['text' => '☎️ تماس با ما'],
            ['text' => '🔐 ادمین']
        ]
    ],
    'resize_keyboard' => true,
    'persistent' => true
];

echo "Keyboard JSON:\n";
echo json_encode($keyboard, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "Keyboard structure is valid: " . (json_last_error() === JSON_ERROR_NONE ? "YES" : "NO") . "\n";

