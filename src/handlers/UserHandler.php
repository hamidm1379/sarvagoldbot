<?php

namespace GoldSalekBot\Handlers;

use GoldSalekBot\Bot;
use GoldSalekBot\Models\User;
use GoldSalekBot\Models\Product;
use GoldSalekBot\Models\Category;
use GoldSalekBot\Models\Collection;
use GoldSalekBot\Models\WeightRange;
use GoldSalekBot\Models\WageRange;
use GoldSalekBot\Models\Contact;

class UserHandler
{
    private $bot;
    private $userModel;
    private $productModel;
    private $categoryModel;
    private $collectionModel;
    private $weightRangeModel;
    private $wageRangeModel;
    private $userStates = [];

    public function __construct(Bot $bot)
    {
        $this->bot = $bot;
        $this->userModel = new User();
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->collectionModel = new Collection();
        $this->weightRangeModel = new WeightRange();
        $this->wageRangeModel = new WageRange();
    }

    public function handle($text, $telegramId)
    {
        $user = $this->userModel->findByTelegramId($telegramId);
        $state = $this->getUserState($telegramId);
        
        // Check if user is admin
        $adminModel = new \GoldSalekBot\Models\Admin();
        $isAdmin = $adminModel->isAdmin($telegramId);

        // Check channel membership (skip for admins)
        if (!$isAdmin) {
            if (!$this->bot->isChannelMember($telegramId, '@sarvagold')) {
                $this->showChannelMembershipRequired();
                return;
            }
        }

        // Handle registration flow
        if (!$user) {
            // If admin, auto-create user with approved status
            if ($isAdmin) {
                $message = $this->bot->getMessage();
                $firstName = $message['from']['first_name'] ?? 'Admin';
                $lastName = $message['from']['last_name'] ?? '';
                
                // Create user with approved status
                $user = $this->userModel->create($telegramId, $firstName, $lastName);
            } else {
                $this->handleRegistration($text, $telegramId);
                return;
            }
        }

        // Users are now auto-approved, so no need to check approval status
        
        // Show main menu if user sends /start
        if ($text === '/start') {
            $this->showMainMenu($isAdmin);
            return;
        }

        // Handle admin button - let Bot.php handle it
        if ($text === '🔐 ادمین' && $isAdmin) {
            return; // Bot.php will check and route to admin
        }

        // Handle state-based actions
        if ($state) {
            $this->handleState($text, $telegramId, $state);
            return;
        }

        // Handle main menu commands
        switch ($text) {
            case '📦 محصولات':
                $this->showCategories();
                break;
            case '🔍 جستجو محصولات':
                $this->askForProductCode();
                break;
            case '☎️ تماس با ما':
                $this->showContact();
                break;
            case '/start':
                $this->showMainMenu($isAdmin);
                break;
            default:
                // Check if it's back button
                if ($text === '🔙 بازگشت') {
                    $state = $this->getUserState($telegramId);
                    // If in a waiting state, clear it and show main menu
                    if ($state && in_array($state, ['waiting_product_code', 'waiting_wage', 'waiting_weight'])) {
                        $this->clearUserState($telegramId);
                        $this->showMainMenu($isAdmin);
                    } else {
                        $this->showMainMenu($isAdmin);
                    }
                }
                // Check if it's a category name
                elseif ($category = $this->categoryModel->findByName($text)) {
                    $this->showCategoryProducts($category['id'], 0);
                }
                // Check if it's a product view button (🔍 مشاهده کد XXXX)
                elseif (preg_match('/^🔍 مشاهده کد (\d+)$/', $text, $matches)) {
                    $productCode = $matches[1];
                    $product = $this->productModel->findByCode($productCode);
                    if ($product) {
                        $this->showProductDetails($product['id']);
                    } else {
                        $this->bot->sendMessage(
                            $this->bot->getChatId(),
                            "⚠️ محصولی با کد <b>{$productCode}</b> یافت نشد."
                        );
                    }
                }
                // Check if it's a navigation button (◀️ قبلی or ▶️ بعدی)
                elseif (preg_match('/^(◀️ قبلی|▶️ بعدی):(\d+):(\d+)$/', $text, $matches)) {
                    $categoryId = (int)$matches[2];
                    $offset = (int)$matches[3];
                    $this->showCategoryAllProducts($categoryId, $offset);
                }
                // Check if it's a product code search (supports both Persian and English numerals)
                elseif ($this->isNumericString($text)) {
                    $this->searchProductByCode($text);
                } else {
                    $this->showMainMenu($isAdmin);
                }
                break;
        }
    }

    public function handleCallback($data, $telegramId, $messageId)
    {
        // Check if user is admin
        $adminModel = new \GoldSalekBot\Models\Admin();
        $isAdmin = $adminModel->isAdmin($telegramId);

        // Check channel membership (skip for admins)
        if (!$isAdmin) {
            if (!$this->bot->isChannelMember($telegramId, '@sarvagold')) {
                $this->showChannelMembershipRequired();
                return;
            }
        }

        $parts = explode(':', $data);
        $action = $parts[0] ?? '';

        switch ($action) {
            case 'category':
                $categoryId = $parts[1] ?? null;
                $offset = 0;
                if (isset($parts[2]) && $parts[2] === 'offset') {
                    $offset = (int)($parts[3] ?? 0);
                }
                $this->showCategoryProducts($categoryId, $offset);
                break;
            case 'category_collection':
                $categoryId = $parts[1] ?? null;
                $this->showCategoryCollections($categoryId, $messageId);
                break;
            case 'category_weight':
                $categoryId = $parts[1] ?? null;
                $this->showCategoryWeightRanges($categoryId, $messageId);
                break;
            case 'category_wage':
                $categoryId = $parts[1] ?? null;
                $this->showCategoryWageRanges($categoryId, $messageId);
                break;
            case 'category_all':
                $categoryId = $parts[1] ?? null;
                $offset = 0;
                if (isset($parts[2]) && $parts[2] === 'offset') {
                    $offset = (int)($parts[3] ?? 0);
                }
                $this->showCategoryAllProducts($categoryId, $offset);
                break;
            case 'collection':
                $collectionId = $parts[1] ?? null;
                $this->showCollectionProducts($collectionId);
                break;
            case 'weight':
                $weight = $parts[1] ?? null;
                $this->showWeightProducts($weight);
                break;
            case 'weight_search_category':
                $categoryId = $parts[1] ?? null;
                $this->askForWeightCollection($categoryId, $messageId);
                break;
            case 'weight_search_collection':
                $categoryId = $parts[1] ?? null;
                $collectionId = $parts[2] ?? null;
                if ($collectionId == '0') {
                    $collectionId = null;
                }
                $this->askForWeightRange($categoryId, $collectionId, $messageId);
                break;
            case 'weight_range':
                $weightRangeId = $parts[1] ?? null;
                $categoryId = isset($parts[2]) ? $parts[2] : null;
                $collectionId = isset($parts[3]) && $parts[3] != '0' ? $parts[3] : null;
                $this->showWeightRangeProducts($weightRangeId, $categoryId, $collectionId);
                break;
            case 'wage_search_category':
                $categoryId = $parts[1] ?? null;
                $this->askForWageCollection($categoryId, $messageId);
                break;
            case 'wage_search_collection':
                $categoryId = $parts[1] ?? null;
                $collectionId = $parts[2] ?? null;
                if ($collectionId == '0') {
                    $collectionId = null;
                }
                $this->askForWageRange($categoryId, $collectionId, $messageId);
                break;
            case 'wage_range':
                $wageRangeId = $parts[1] ?? null;
                $categoryId = isset($parts[2]) ? $parts[2] : null;
                $collectionId = isset($parts[3]) && $parts[3] != '0' ? $parts[3] : null;
                $this->showWageRangeProducts($wageRangeId, $categoryId, $collectionId);
                break;
            case 'wage':
                $wage = $parts[1] ?? null;
                $this->showWageProducts($wage);
                break;
            case 'product':
                $productId = $parts[1] ?? null;
                $this->showProductDetails($productId);
                break;
            case 'filter':
                $this->showFilterMenu();
                break;
            case 'back':
                $adminModel = new \GoldSalekBot\Models\Admin();
                $isAdmin = $adminModel->isAdmin($telegramId);
                $this->showMainMenu($isAdmin);
                break;
            case 'check_channel_membership':
                // Re-check channel membership
                if (!$this->bot->isChannelMember($telegramId, '@sarvagold')) {
                    $this->showChannelMembershipRequired();
                } else {
                    $adminModel = new \GoldSalekBot\Models\Admin();
                    $isAdmin = $adminModel->isAdmin($telegramId);
                    $this->showMainMenu($isAdmin);
                }
                break;
        }
    }

    private function handleRegistration($text, $telegramId)
    {
        $state = $this->getUserState($telegramId);
        $message = $this->bot->getMessage();
        $firstName = $message['from']['first_name'] ?? '';

        if ($state === 'waiting_first_name') {
            $this->setUserState($telegramId, 'waiting_last_name');
            $this->setUserData($telegramId, 'first_name', $text);
            $this->bot->sendMessage(
                $this->bot->getChatId(),
                "✅ نام شما ثبت شد: {$text}\n\nلطفاً نام خانوادگی خود را وارد کنید:"
            );
        } elseif ($state === 'waiting_last_name') {
            $firstName = $this->getUserData($telegramId, 'first_name');
            $lastName = $text;
            
            $user = $this->userModel->create($telegramId, $firstName, $lastName);
            
            $this->clearUserState($telegramId);
            
            // Check if user is admin for main menu
            $adminModel = new \GoldSalekBot\Models\Admin();
            $isAdmin = $adminModel->isAdmin($telegramId);
            
            // Create inline keyboard buttons
            $inlineKeyboard = [
                [[
                    'text' => '🏠 رفتن به منوی اصلی',
                    'callback_data' => 'back'
                ]]
            ];
            
            $keyboard = ['inline_keyboard' => $inlineKeyboard];
            
            $this->bot->sendMessage(
                $this->bot->getChatId(),
                "✅ ثبت نام شما با موفقیت انجام شد!\n\n" .
                "🆔 کد کاربری شما: <b>{$user['internal_id']}</b>\n\n" .
                "🎉 اکنون می‌توانید از تمام امکانات ربات استفاده کنید.",
                $keyboard
            );
        } else {
            $this->setUserState($telegramId, 'waiting_first_name');
            $this->bot->sendMessage(
                $this->bot->getChatId(),
                "💫 <b>به ربات پیشرفته گالری Sarva Gold خوش آمدید</b> 💫\n\n" .
                "✨ دسترسی آسان و دقیق به تمامی محصولات موجود در گالری\n" .
                "👥 جهت استفاده همکاران عزیز\n\n" .
                "📝 برای استفاده از ربات، لطفاً ابتدا ثبت نام کنید.\n\n" .
                "👤 لطفاً نام خود را وارد کنید:"
            );
        }
    }

    private function handleState($text, $telegramId, $state)
    {
        // Check for back button first, regardless of state
        if ($text === '🔙 بازگشت') {
            $this->clearUserState($telegramId);
            $adminModel = new \GoldSalekBot\Models\Admin();
            $isAdmin = $adminModel->isAdmin($telegramId);
            $this->showMainMenu($isAdmin);
            return;
        }

        if ($state === 'waiting_product_code') {
            $this->searchProductByCode($text);
            $this->clearUserState($telegramId);
        } elseif ($state === 'waiting_wage') {
            $categoryId = $this->getUserData($telegramId, 'wage_category_id');
            $collectionId = $this->getUserData($telegramId, 'wage_collection_id');
            $this->searchProductByWage($text, $categoryId, $collectionId);
            $this->clearUserState($telegramId);
        } elseif ($state === 'waiting_weight') {
            $categoryId = $this->getUserData($telegramId, 'weight_category_id');
            $collectionId = $this->getUserData($telegramId, 'weight_collection_id');
            $this->searchProductByWeight($text, $categoryId, $collectionId);
            $this->clearUserState($telegramId);
        }
    }

    private function showMainMenu($isAdmin = false)
    {
        $keyboard = [
            'keyboard' => [
                [
                    ['text' => '📦 محصولات'],
                    ['text' => '🔍 جستجو محصولات']
                ],
                [
                    ['text' => '☎️ تماس با ما']
                ]
            ],
            'resize_keyboard' => true,
            'persistent' => true
        ];

        // Add admin button if user is admin
        if ($isAdmin) {
            $keyboard['keyboard'][1][] = ['text' => '🔐 ادمین'];
        }

        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "🏠 <b>منوی اصلی</b>\n\n" .
            "لطفاً یکی از گزینه‌های زیر را انتخاب کنید:",
            $keyboard
        );
    }

    private function showCategories()
    {
        $categories = $this->categoryModel->getAll();
        
        if (empty($categories)) {
            $this->bot->sendMessage(
                $this->bot->getChatId(),
                "⚠️ هیچ دسته‌بندی موجود نیست."
            );
            return;
        }

        // Create reply keyboard buttons arranged in rows (2 per row)
        $keyboardButtons = [];
        $row = [];
        foreach ($categories as $category) {
            $row[] = ['text' => $category['name']];
            if (count($row) === 2) {
                $keyboardButtons[] = $row;
                $row = [];
            }
        }
        // Add remaining buttons if any
        if (!empty($row)) {
            $keyboardButtons[] = $row;
        }
        // Add back button
        $keyboardButtons[] = [['text' => '🔙 بازگشت']];

        $keyboard = [
            'keyboard' => $keyboardButtons,
            'resize_keyboard' => true,
            'persistent' => true
        ];

        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "📦 <b>دسته‌بندی محصولات</b>\n\n" .
            "لطفاً یک دسته‌بندی را انتخاب کنید:",
            $keyboard
        );
    }

    private function showCategoryProducts($categoryId, $offset = 0)
    {
        $category = $this->categoryModel->findById($categoryId);
        if (!$category) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ دسته‌بندی یافت نشد.");
            return;
        }

        // Show menu with three options: Collection, Weight, Wage
        $this->showCategoryFilterMenu($categoryId);
    }

    private function showCategoryFilterMenu($categoryId)
    {
        $category = $this->categoryModel->findById($categoryId);
        if (!$category) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ دسته‌بندی یافت نشد.");
            return;
        }

        $text = "📦 <b>{$category['name']}</b>\n\n";
        $text .= "لطفاً نوع فیلتر را انتخاب کنید:";

        $inlineKeyboard = [
            [[
                'text' => '🧩 کالکشن',
                'callback_data' => "category_collection:{$categoryId}"
            ]],
            [[
                'text' => '⚖️ وزن',
                'callback_data' => "category_weight:{$categoryId}"
            ]],
            [[
                'text' => '💰 اجرت',
                'callback_data' => "category_wage:{$categoryId}"
            ]],
            [[
                'text' => '📋 نمایش همه محصولات',
                'callback_data' => "category_all:{$categoryId}"
            ]],
            [[
                'text' => '🔙 بازگشت',
                'callback_data' => 'back'
            ]]
        ];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function showCategoryAllProducts($categoryId, $offset = 0)
    {
        $category = $this->categoryModel->findById($categoryId);
        if (!$category) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ دسته‌بندی یافت نشد.");
            return;
        }

        $filters = ['category_id' => $categoryId];
        $products = $this->productModel->getPaginated($filters, 10, $offset);
        $total = $this->productModel->count($filters);

        if (empty($products)) {
            $this->bot->sendMessage(
                $this->bot->getChatId(),
                "⚠️ محصولی در این دسته‌بندی یافت نشد."
            );
            return;
        }

        // Navigation buttons in reply keyboard
        $keyboardButtons = [];
        $navRow = [];
        if ($offset > 0) {
            $navRow[] = ['text' => "◀️ قبلی:{$categoryId}:" . ($offset - 10)];
        }
        if ($offset + 10 < $total) {
            $navRow[] = ['text' => "▶️ بعدی:{$categoryId}:" . ($offset + 10)];
        }
        if (!empty($navRow)) {
            $keyboardButtons[] = $navRow;
        }
        
        $keyboardButtons[] = [['text' => '🔙 بازگشت']];

        $keyboard = [
            'keyboard' => $keyboardButtons,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        // Send product images with navigation buttons on last image
        $this->sendProductImages($products, $keyboard);
    }

    private function showCategoryCollections($categoryId, $messageId = null)
    {
        $category = $this->categoryModel->findById($categoryId);
        if (!$category) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ دسته‌بندی یافت نشد.");
            return;
        }

        $collections = $this->collectionModel->getAll($categoryId);
        
        if (empty($collections)) {
            $text = "⚠️ کالکشنی برای دسته‌بندی <b>{$category['name']}</b> یافت نشد.";
            if ($messageId) {
                $this->bot->editMessageText($this->bot->getChatId(), $messageId, $text);
            } else {
                $this->bot->sendMessage($this->bot->getChatId(), $text);
            }
            return;
        }

        // Get user level
        $callbackQuery = $this->bot->getCallbackQuery();
        $message = $this->bot->getMessage();
        $telegramId = $callbackQuery ? $callbackQuery['from']['id'] : ($message ? $message['from']['id'] : null);
        $user = $telegramId ? $this->userModel->findByTelegramId($telegramId) : null;
        $userLevel = $user ? ($user['level'] ?? 'general') : 'general';

        $text = "📦 <b>{$category['name']}</b>\n\n";
        $text .= "🧩 <b>کالکشن‌ها</b>\n\n";
        $text .= "لطفاً یک کالکشن را انتخاب کنید:";

        $inlineKeyboard = [];
        foreach ($collections as $collection) {
            $collectionName = $collection['name'];
            
            // Calculate display wage based on user level
            $displayWage = \GoldSalekBot\Models\User::calculateDisplayWage(
                $collection['wage_percentage'], 
                $userLevel
            );
            
            // Add wage to button text if user can see it
            if ($displayWage !== null) {
                $collectionName .= " (💰 {$displayWage}%)";
            }
            
            $inlineKeyboard[] = [[
                'text' => $collectionName,
                'callback_data' => "collection:{$collection['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => "category:{$categoryId}"
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        
        if ($messageId) {
            $this->bot->editMessageText($this->bot->getChatId(), $messageId, $text, $keyboard);
        } else {
            $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
        }
    }

    private function showCategoryWeightRanges($categoryId, $messageId = null)
    {
        $category = $this->categoryModel->findById($categoryId);
        if (!$category) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ دسته‌بندی یافت نشد.");
            return;
        }

        $weightRanges = $this->weightRangeModel->getAll($categoryId, null);

        $text = "📦 <b>{$category['name']}</b>\n\n";
        $text .= "⚖️ <b>بازه‌های وزن</b>\n\n";

        if (empty($weightRanges)) {
            $text .= "⚠️ بازه وزنی برای این دسته‌بندی یافت نشد.\n";
            $text .= "لطفاً وزن را به صورت دستی وارد کنید (گرم):\n";
            $text .= "مثال: 5.5";
            
            $callbackQuery = $this->bot->getCallbackQuery();
            $message = $this->bot->getMessage();
            $telegramId = $callbackQuery ? $callbackQuery['from']['id'] : ($message ? $message['from']['id'] : null);
            
            if ($telegramId) {
                $this->setUserState($telegramId, 'waiting_weight');
                $this->setUserData($telegramId, 'weight_category_id', $categoryId);
                $this->setUserData($telegramId, 'weight_collection_id', null);
            }
            
            $keyboard = [
                'keyboard' => [
                    [['text' => '🔙 بازگشت']]
                ],
                'resize_keyboard' => true,
                'persistent' => true
            ];
            
            // Can't use editMessageText for reply keyboard, so send new message
            $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
            return;
        }

        $text .= "لطفاً بازه وزن مورد نظر را انتخاب کنید:\n\n";

        $inlineKeyboard = [];
        foreach ($weightRanges as $range) {
            $displayName = "{$range['name']} ({$range['min_weight']} تا {$range['max_weight']} گرم)";
            $inlineKeyboard[] = [[
                'text' => $displayName,
                'callback_data' => "weight_range:{$range['id']}:{$categoryId}:0"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => "category:{$categoryId}"
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        
        if ($messageId) {
            $this->bot->editMessageText($this->bot->getChatId(), $messageId, $text, $keyboard);
        } else {
            $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
        }
    }

    private function showCategoryWageRanges($categoryId, $messageId = null)
    {
        $category = $this->categoryModel->findById($categoryId);
        if (!$category) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ دسته‌بندی یافت نشد.");
            return;
        }

        $wageRanges = $this->wageRangeModel->getAll($categoryId, null);

        $text = "📦 <b>{$category['name']}</b>\n\n";
        $text .= "💰 <b>بازه‌های اجرت</b>\n\n";

        if (empty($wageRanges)) {
            $text .= "⚠️ بازه اجرتی برای این دسته‌بندی یافت نشد.\n";
            $text .= "لطفاً اجرت را به صورت دستی وارد کنید (%):\n";
            $text .= "مثال: 15";
            
            $callbackQuery = $this->bot->getCallbackQuery();
            $message = $this->bot->getMessage();
            $telegramId = $callbackQuery ? $callbackQuery['from']['id'] : ($message ? $message['from']['id'] : null);
            
            if ($telegramId) {
                $this->setUserState($telegramId, 'waiting_wage');
                $this->setUserData($telegramId, 'wage_category_id', $categoryId);
                $this->setUserData($telegramId, 'wage_collection_id', null);
            }
            
            $keyboard = [
                'keyboard' => [
                    [['text' => '🔙 بازگشت']]
                ],
                'resize_keyboard' => true,
                'persistent' => true
            ];
            
            // Can't use editMessageText for reply keyboard, so send new message
            $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
            return;
        }

        $text .= "لطفاً بازه اجرت مورد نظر را انتخاب کنید:\n\n";

        $inlineKeyboard = [];
        foreach ($wageRanges as $range) {
            $displayName = $range['name'];
            $inlineKeyboard[] = [[
                'text' => $displayName,
                'callback_data' => "wage_range:{$range['id']}:{$categoryId}:0"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => "category:{$categoryId}"
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        
        if ($messageId) {
            $this->bot->editMessageText($this->bot->getChatId(), $messageId, $text, $keyboard);
        } else {
            $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
        }
    }

    private function showProductDetails($productId)
    {
        $product = $this->productModel->findById($productId);
        
        if (!$product || $product['status'] !== 'active') {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ محصول یافت نشد.");
            return;
        }
        
        // Check what media fields actually have values (not just truthy checks)
        $hasImageFileId = !empty($product['image_file_id']);
        $hasImagePath = !empty($product['image_path']);
        $hasVideo = !empty($product['video_file_id']);
        $hasAnimation = !empty($product['animation_file_id']);
        
        $sent = false;
        
        // Try to send photo (without caption)
        if ($hasImageFileId || $hasImagePath) {
            // Try file_id first
            if ($hasImageFileId) {
                $result = $this->bot->sendPhoto($this->bot->getChatId(), $product['image_file_id'], '');
                if ($result) {
                    $sent = true;
                }
            }
            
            // If file_id failed or doesn't exist, try image_path
            if (!$sent && $hasImagePath) {
                // Check if image_path is a valid URL
                if (filter_var($product['image_path'], FILTER_VALIDATE_URL)) {
                    $result = $this->bot->sendPhoto($this->bot->getChatId(), $product['image_path'], '');
                    if ($result) {
                        $sent = true;
                    }
                }
            }
        }
        // Try video if photo didn't work
        elseif ($hasVideo) {
            $result = $this->bot->sendVideo($this->bot->getChatId(), $product['video_file_id'], '');
            if ($result) {
                $sent = true;
            }
        }
        // Try animation if photo and video didn't work
        elseif ($hasAnimation) {
            $result = $this->bot->sendAnimation($this->bot->getChatId(), $product['animation_file_id'], '');
            if ($result) {
                $sent = true;
            }
        }
        
        // If media failed to send, show product info as text message
        if (!$sent) {
            // Use simple format without emojis when media is not available
            $simpleProductInfo = $this->formatProductInfoSimple($product);
            $this->bot->sendMessage($this->bot->getChatId(), $simpleProductInfo);
        }
    }

    /**
     * Format product information as text (with emojis for captions)
     * @param array $product
     * @return string
     */
    private function formatProductInfo($product)
    {
        $text = "📦 <b>{$product['name']}</b>\n\n";
        $text .= "🔢 <b>کد محصول:</b> {$product['product_code']}\n";
        
        if ($product['category_name']) {
            $text .= "📂 <b>دسته‌بندی:</b> {$product['category_name']}\n";
        }
        
        if ($product['collection_name']) {
            $text .= "🎨 <b>کالکشن:</b> {$product['collection_name']}\n";
        }
        
        $text .= "💰 <b>اجرت:</b> {$product['wage_percentage']}%\n";
        $text .= "⚖️ <b>وزن:</b> {$product['weight']} گرم";
        
        return $text;
    }

    /**
     * Format product information as simple text (without emojis and product name)
     * @param array $product
     * @return string
     */
    private function formatProductInfoSimple($product)
    {
        $text = "کد محصول: {$product['product_code']}\n";
        
        if ($product['category_name']) {
            $text .= "دسته‌بندی: {$product['category_name']}\n";
        }
        
        if ($product['collection_name']) {
            $text .= "کالکشن: {$product['collection_name']}\n";
        }
        
        $text .= "اجرت: {$product['wage_percentage']}%\n";
        $text .= "وزن: {$product['weight']} گرم";
        
        return $text;
    }


    private function askForProductCode()
    {
        $this->setUserState($this->bot->getMessage()['from']['id'], 'waiting_product_code');
        $keyboard = [
            'keyboard' => [
                [['text' => '🔙 بازگشت']]
            ],
            'resize_keyboard' => true,
            'persistent' => true
        ];
        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "🔍 لطفاً کد محصول را وارد کنید:\n\n" .
            "مثال: 1001",
            $keyboard
        );
    }

    private function askForWage()
    {
        // Start by asking for category
        $this->askForWageCategory();
    }

    private function askForWageCategory()
    {
        $categories = $this->categoryModel->getAll();
        $text = "💰 <b>جستجو بر اساس اجرت</b>\n\n";
        $text .= "لطفاً دسته‌بندی را انتخاب کنید:\n\n";
        
        $inlineKeyboard = [];
        foreach ($categories as $category) {
            $inlineKeyboard[] = [[
                'text' => $category['name'],
                'callback_data' => "wage_search_category:{$category['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'back'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function askForWageCollection($categoryId, $messageId = null)
    {
        $collections = $this->collectionModel->getAll($categoryId);
        $category = $this->categoryModel->findById($categoryId);
        $categoryName = $category ? $category['name'] : '';
        
        // Get user level
        $callbackQuery = $this->bot->getCallbackQuery();
        $message = $this->bot->getMessage();
        $telegramId = $callbackQuery ? $callbackQuery['from']['id'] : ($message ? $message['from']['id'] : null);
        $user = $telegramId ? $this->userModel->findByTelegramId($telegramId) : null;
        $userLevel = $user ? ($user['level'] ?? 'general') : 'general';
        
        $text = "💰 <b>جستجو بر اساس اجرت</b>\n\n";
        $text .= "دسته‌بندی: <b>{$categoryName}</b>\n\n";
        $text .= "لطفاً کالکشن را انتخاب کنید:\n\n";
        
        if (empty($collections)) {
            $text .= "⚠️ کالکشنی برای این دسته‌بندی وجود ندارد.\n\n";
        }

        $inlineKeyboard = [];
        foreach ($collections as $collection) {
            $collectionName = $collection['name'];
            
            // Calculate display wage based on user level
            $displayWage = \GoldSalekBot\Models\User::calculateDisplayWage(
                $collection['wage_percentage'], 
                $userLevel
            );
            
            // Add wage to button text if user can see it
            if ($displayWage !== null) {
                $collectionName .= " (💰 {$displayWage}%)";
            }
            
            $inlineKeyboard[] = [[
                'text' => $collectionName,
                'callback_data' => "wage_search_collection:{$categoryId}:{$collection['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => 'بدون کالکشن',
            'callback_data' => "wage_search_collection:{$categoryId}:0"
        ]];
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'back'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        
        if ($messageId) {
            $this->bot->editMessageText($this->bot->getChatId(), $messageId, $text, $keyboard);
        } else {
            $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
        }
    }

    private function askForWageRange($categoryId, $collectionId = null, $messageId = null)
    {
        $wageRanges = $this->wageRangeModel->getAll($categoryId, $collectionId);
        
        $category = $this->categoryModel->findById($categoryId);
        $categoryName = $category ? $category['name'] : '';
        
        $text = "💰 <b>جستجو بر اساس اجرت</b>\n\n";
        $text .= "دسته‌بندی: <b>{$categoryName}</b>\n";
        
        if ($collectionId) {
            $collection = $this->collectionModel->findById($collectionId);
            $collectionName = $collection ? $collection['name'] : '';
            $text .= "کالکشن: <b>{$collectionName}</b>\n";
        } else {
            $text .= "کالکشن: <b>همه</b>\n";
        }
        
        $text .= "\nلطفاً بازه اجرت مورد نظر را انتخاب کنید:\n\n";

        if (empty($wageRanges)) {
            $text .= "⚠️ بازه اجرتی برای این دسته‌بندی و کالکشن یافت نشد.\n";
            $text .= "لطفاً اجرت را به صورت دستی وارد کنید (%):\n";
            $text .= "مثال: 15";
            
            $callbackQuery = $this->bot->getCallbackQuery();
            $message = $this->bot->getMessage();
            $telegramId = $callbackQuery ? $callbackQuery['from']['id'] : ($message ? $message['from']['id'] : null);
            
            if ($telegramId) {
                $this->setUserState($telegramId, 'waiting_wage');
                $this->setUserData($telegramId, 'wage_category_id', $categoryId);
                $this->setUserData($telegramId, 'wage_collection_id', $collectionId);
            }
            
            $keyboard = [
                'keyboard' => [
                    [['text' => '🔙 بازگشت']]
                ],
                'resize_keyboard' => true,
                'persistent' => true
            ];
            
            // send new message (reply keyboard)
            $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
            return;
        }

        $inlineKeyboard = [];
        foreach ($wageRanges as $range) {
            $displayName = $range['name'];
            $callbackData = "wage_range:{$range['id']}:{$categoryId}";
            if ($collectionId) {
                $callbackData .= ":{$collectionId}";
            } else {
                $callbackData .= ":0";
            }
            $inlineKeyboard[] = [[
                'text' => $displayName,
                'callback_data' => $callbackData
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'back'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        
        if ($messageId) {
            $this->bot->editMessageText($this->bot->getChatId(), $messageId, $text, $keyboard);
        } else {
            $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
        }
    }

    private function askForWeight()
    {
        // Start by asking for category
        $this->askForWeightCategory();
    }

    private function askForWeightCategory()
    {
        $categories = $this->categoryModel->getAll();
        $text = "⚖️ <b>جستجو بر اساس وزن</b>\n\n";
        $text .= "لطفاً دسته‌بندی را انتخاب کنید:\n\n";
        
        $inlineKeyboard = [];
        foreach ($categories as $category) {
            $inlineKeyboard[] = [[
                'text' => $category['name'],
                'callback_data' => "weight_search_category:{$category['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'back'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function askForWeightCollection($categoryId, $messageId = null)
    {
        $collections = $this->collectionModel->getAll($categoryId);
        $category = $this->categoryModel->findById($categoryId);
        $categoryName = $category ? $category['name'] : '';
        
        // Get user level
        $callbackQuery = $this->bot->getCallbackQuery();
        $message = $this->bot->getMessage();
        $telegramId = $callbackQuery ? $callbackQuery['from']['id'] : ($message ? $message['from']['id'] : null);
        $user = $telegramId ? $this->userModel->findByTelegramId($telegramId) : null;
        $userLevel = $user ? ($user['level'] ?? 'general') : 'general';
        
        $text = "⚖️ <b>جستجو بر اساس وزن</b>\n\n";
        $text .= "دسته‌بندی: <b>{$categoryName}</b>\n\n";
        $text .= "لطفاً کالکشن را انتخاب کنید:\n\n";
        
        if (empty($collections)) {
            $text .= "⚠️ کالکشنی برای این دسته‌بندی وجود ندارد.\n\n";
        }

        $inlineKeyboard = [];
        foreach ($collections as $collection) {
            $collectionName = $collection['name'];
            
            // Calculate display wage based on user level
            $displayWage = \GoldSalekBot\Models\User::calculateDisplayWage(
                $collection['wage_percentage'], 
                $userLevel
            );
            
            // Add wage to button text if user can see it
            if ($displayWage !== null) {
                $collectionName .= " (💰 {$displayWage}%)";
            }
            
            $inlineKeyboard[] = [[
                'text' => $collectionName,
                'callback_data' => "weight_search_collection:{$categoryId}:{$collection['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => 'بدون کالکشن',
            'callback_data' => "weight_search_collection:{$categoryId}:0"
        ]];
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'back'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        
        if ($messageId) {
            $this->bot->editMessageText($this->bot->getChatId(), $messageId, $text, $keyboard);
        } else {
            $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
        }
    }

    private function askForWeightRange($categoryId, $collectionId = null, $messageId = null)
    {
        // Get weight ranges filtered by category and collection
        $weightRanges = $this->weightRangeModel->getAll($categoryId, $collectionId);
        
        $category = $this->categoryModel->findById($categoryId);
        $categoryName = $category ? $category['name'] : '';
        
        $text = "⚖️ <b>جستجو بر اساس وزن</b>\n\n";
        $text .= "دسته‌بندی: <b>{$categoryName}</b>\n";
        
        if ($collectionId) {
            $collection = $this->collectionModel->findById($collectionId);
            $collectionName = $collection ? $collection['name'] : '';
            $text .= "کالکشن: <b>{$collectionName}</b>\n";
        } else {
            $text .= "کالکشن: <b>همه</b>\n";
        }
        
        $text .= "\nلطفاً بازه وزن مورد نظر را انتخاب کنید:\n\n";

        if (empty($weightRanges)) {
            $text .= "⚠️ بازه وزنی برای این دسته‌بندی و کالکشن یافت نشد.\n";
            $text .= "لطفاً بازه وزن را به صورت دستی وارد کنید (گرم):\n";
            $text .= "مثال: 5.5";
            
            $callbackQuery = $this->bot->getCallbackQuery();
            $message = $this->bot->getMessage();
            $telegramId = $callbackQuery ? $callbackQuery['from']['id'] : ($message ? $message['from']['id'] : null);
            
            if ($telegramId) {
                $this->setUserState($telegramId, 'waiting_weight');
                $this->setUserData($telegramId, 'weight_category_id', $categoryId);
                $this->setUserData($telegramId, 'weight_collection_id', $collectionId);
            }
            
            $keyboard = [
                'keyboard' => [
                    [['text' => '🔙 بازگشت']]
                ],
                'resize_keyboard' => true,
                'persistent' => true
            ];
            
            // Can't use editMessageText for reply keyboard, so send new message
            $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
            return;
        }

        $inlineKeyboard = [];
        foreach ($weightRanges as $range) {
            $displayName = "{$range['name']} ({$range['min_weight']} تا {$range['max_weight']} گرم)";
            $callbackData = "weight_range:{$range['id']}:{$categoryId}";
            if ($collectionId) {
                $callbackData .= ":{$collectionId}";
            } else {
                $callbackData .= ":0";
            }
            $inlineKeyboard[] = [[
                'text' => $displayName,
                'callback_data' => $callbackData
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'back'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        
        if ($messageId) {
            $this->bot->editMessageText($this->bot->getChatId(), $messageId, $text, $keyboard);
        } else {
            $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
        }
    }

    private function searchProductByWage($wage, $categoryId = null, $collectionId = null)
    {
        if (!is_numeric($wage)) {
            $this->bot->sendMessage(
                $this->bot->getChatId(),
                "⚠️ لطفاً یک عدد معتبر برای اجرت وارد کنید.\n\n" .
                "مثال: 15"
            );
            return;
        }

        $filters = ['wage_percentage' => (float)$wage];
        
        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        }
        
        if ($collectionId) {
            $filters['collection_id'] = $collectionId;
        }
        
        $products = $this->productModel->getAll($filters);

        if (empty($products)) {
            $categoryName = '';
            $collectionName = '';
            if ($categoryId) {
                $category = $this->categoryModel->findById($categoryId);
                $categoryName = $category ? $category['name'] : '';
            }
            if ($collectionId) {
                $collection = $this->collectionModel->findById($collectionId);
                $collectionName = $collection ? $collection['name'] : '';
            }
            
            $text = "⚠️ محصولی با اجرت <b>{$wage}%</b>";
            if ($categoryName) {
                $text .= "\nدسته‌بندی: {$categoryName}";
            }
            if ($collectionName) {
                $text .= "\nکالکشن: {$collectionName}";
            }
            $text .= "\n\nیافت نشد.";
            
            $this->bot->sendMessage($this->bot->getChatId(), $text);
            return;
        }

        $this->showWageProducts($wage, $categoryId, $collectionId);
    }

    private function searchProductByWeight($weight, $categoryId = null, $collectionId = null)
    {
        if (!is_numeric($weight)) {
            $this->bot->sendMessage(
                $this->bot->getChatId(),
                "⚠️ لطفاً یک عدد معتبر برای وزن وارد کنید.\n\n" .
                "مثال: 5.5"
            );
            return;
        }

        $filters = ['weight' => (float)$weight];
        
        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        }
        
        if ($collectionId) {
            $filters['collection_id'] = $collectionId;
        }
        
        $products = $this->productModel->getAll($filters);

        if (empty($products)) {
            $categoryName = '';
            $collectionName = '';
            if ($categoryId) {
                $category = $this->categoryModel->findById($categoryId);
                $categoryName = $category ? $category['name'] : '';
            }
            if ($collectionId) {
                $collection = $this->collectionModel->findById($collectionId);
                $collectionName = $collection ? $collection['name'] : '';
            }
            
            $text = "⚠️ محصولی با وزن <b>{$weight} گرم</b>";
            if ($categoryName) {
                $text .= "\nدسته‌بندی: {$categoryName}";
            }
            if ($collectionName) {
                $text .= "\nکالکشن: {$collectionName}";
            }
            $text .= "\n\nیافت نشد.";
            
            $this->bot->sendMessage($this->bot->getChatId(), $text);
            return;
        }

        $this->showWeightProducts($weight, $categoryId, $collectionId);
    }

    private function searchProductByCode($code)
    {
        // Convert Persian numerals to English numerals
        $normalizedCode = $this->normalizePersianNumbers($code);
        
        $product = $this->productModel->findByCode($normalizedCode);
        
        if (!$product) {
            $this->bot->sendMessage(
                $this->bot->getChatId(),
                "⚠️ محصولی با کد <b>{$code}</b> یافت نشد."
            );
            return;
        }

        $this->showProductDetails($product['id']);
    }

    /**
     * Convert Persian numerals to English numerals
     * @param string $text
     * @return string
     */
    private function normalizePersianNumbers($text)
    {
        $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        return str_replace($persianNumbers, $englishNumbers, $text);
    }

    /**
     * Check if string contains only numbers (Persian or English)
     * @param string $text
     * @return bool
     */
    private function isNumericString($text)
    {
        // Normalize Persian numbers to English first
        $normalized = $this->normalizePersianNumbers($text);
        // Check if it's numeric after normalization
        return is_numeric($normalized);
    }

    private function showFilterMenu()
    {
        $inlineKeyboard = [
            [[
                'text' => '📂 دسته‌بندی',
                'callback_data' => 'filter:category'
            ]],
            [[
                'text' => '🧩 کالکشن',
                'callback_data' => 'filter:collection'
            ]],
            [[
                'text' => '⚖️ وزن',
                'callback_data' => 'filter:weight'
            ]],
            [[
                'text' => '💰 اجرت',
                'callback_data' => 'filter:wage'
            ]],
            [[
                'text' => '🔙 بازگشت',
                'callback_data' => 'back'
            ]]
        ];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];

        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "🔍 <b>فیلتر محصولات</b>\n\n" .
            "لطفاً نوع فیلتر را انتخاب کنید:",
            $keyboard
        );
    }

    private function showCollectionProducts($collectionId)
    {
        $collection = $this->collectionModel->findById($collectionId);
        if (!$collection) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ کالکشن یافت نشد.");
            return;
        }

        $filters = ['collection_id' => $collectionId];
        $products = $this->productModel->getAll($filters);

        if (empty($products)) {
            $this->bot->sendMessage(
                $this->bot->getChatId(),
                "⚠️ محصولی در این کالکشن یافت نشد."
            );
            return;
        }

        // Only back button
        $keyboard = [
            'keyboard' => [[['text' => '🔙 بازگشت']]],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        // Send product images with back button on last image
        $this->sendProductImages($products, $keyboard);
    }

    private function showWeightProducts($weight, $categoryId = null, $collectionId = null)
    {
        $filters = ['weight' => $weight];
        
        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        }
        
        if ($collectionId) {
            $filters['collection_id'] = $collectionId;
        }
        
        $products = $this->productModel->getAll($filters);

        if (empty($products)) {
            $this->bot->sendMessage(
                $this->bot->getChatId(),
                "⚠️ محصولی با وزن {$weight} گرم یافت نشد."
            );
            return;
        }

        $categoryName = '';
        $collectionName = '';
        if ($categoryId) {
            $category = $this->categoryModel->findById($categoryId);
            $categoryName = $category ? $category['name'] : '';
        }
        if ($collectionId) {
            $collection = $this->collectionModel->findById($collectionId);
            $collectionName = $collection ? $collection['name'] : '';
        }

        // Only back button
        $keyboard = [
            'keyboard' => [[['text' => '🔙 بازگشت']]],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        // Send product images with back button on last image
        $this->sendProductImages($products, $keyboard);
    }

    private function showWeightRangeProducts($weightRangeId, $categoryId = null, $collectionId = null)
    {
        $weightRange = $this->weightRangeModel->findById($weightRangeId);
        
        if (!$weightRange) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ بازه وزن یافت نشد.");
            return;
        }

        $filters = [
            'weight_min' => $weightRange['min_weight'],
            'weight_max' => $weightRange['max_weight']
        ];

        // Use category from user selection (priority) or from weight range
        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        } elseif ($weightRange['category_id']) {
            $filters['category_id'] = $weightRange['category_id'];
        }

        // Use collection from user selection (priority) or from weight range
        if ($collectionId !== null) {
            if ($collectionId) {
                $filters['collection_id'] = $collectionId;
            }
        } elseif ($weightRange['collection_id']) {
            $filters['collection_id'] = $weightRange['collection_id'];
        }

        $products = $this->productModel->getAll($filters);

        if (empty($products)) {
            $categoryName = '';
            $collectionName = '';
            if ($categoryId) {
                $category = $this->categoryModel->findById($categoryId);
                $categoryName = $category ? $category['name'] : '';
            }
            if ($collectionId) {
                $collection = $this->collectionModel->findById($collectionId);
                $collectionName = $collection ? $collection['name'] : '';
            }
            
            $text = "⚠️ محصولی در بازه وزن <b>{$weightRange['name']}</b> ({$weightRange['min_weight']} تا {$weightRange['max_weight']} گرم)";
            if ($categoryName) {
                $text .= "\nدسته‌بندی: {$categoryName}";
            }
            if ($collectionName) {
                $text .= "\nکالکشن: {$collectionName}";
            }
            $text .= "\n\nیافت نشد.";
            
            $this->bot->sendMessage($this->bot->getChatId(), $text);
            return;
        }

        $categoryName = '';
        $collectionName = '';
        if ($categoryId) {
            $category = $this->categoryModel->findById($categoryId);
            $categoryName = $category ? $category['name'] : '';
        }
        if ($collectionId) {
            $collection = $this->collectionModel->findById($collectionId);
            $collectionName = $collection ? $collection['name'] : '';
        }

        // Only back button
        $keyboard = [
            'keyboard' => [[['text' => '🔙 بازگشت']]],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        // Send product images with back button on last image
        $this->sendProductImages($products, $keyboard);
    }

    private function showWageProducts($wage, $categoryId = null, $collectionId = null)
    {
        $filters = ['wage_percentage' => $wage];
        
        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        }
        
        if ($collectionId) {
            $filters['collection_id'] = $collectionId;
        }
        
        $products = $this->productModel->getAll($filters);

        if (empty($products)) {
            $this->bot->sendMessage(
                $this->bot->getChatId(),
                "⚠️ محصولی با اجرت {$wage}% یافت نشد."
            );
            return;
        }

        $categoryName = '';
        $collectionName = '';
        if ($categoryId) {
            $category = $this->categoryModel->findById($categoryId);
            $categoryName = $category ? $category['name'] : '';
        }
        if ($collectionId) {
            $collection = $this->collectionModel->findById($collectionId);
            $collectionName = $collection ? $collection['name'] : '';
        }

        // Only back button
        $keyboard = [
            'keyboard' => [[['text' => '🔙 بازگشت']]],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        // Send product images with back button on last image
        $this->sendProductImages($products, $keyboard);
    }

    private function showWageRangeProducts($wageRangeId, $categoryId = null, $collectionId = null)
    {
        $wageRange = $this->wageRangeModel->findById($wageRangeId);
        
        if (!$wageRange) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ بازه اجرت یافت نشد.");
            return;
        }

        $filters = [
            'wage_min' => $wageRange['min_wage'],
            'wage_max' => $wageRange['max_wage']
        ];

        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        } elseif ($wageRange['category_id']) {
            $filters['category_id'] = $wageRange['category_id'];
        }

        if ($collectionId !== null) {
            if ($collectionId) {
                $filters['collection_id'] = $collectionId;
            }
        } elseif ($wageRange['collection_id']) {
            $filters['collection_id'] = $wageRange['collection_id'];
        }

        $products = $this->productModel->getAll($filters);

        if (empty($products)) {
            $categoryName = '';
            $collectionName = '';
            if ($categoryId) {
                $category = $this->categoryModel->findById($categoryId);
                $categoryName = $category ? $category['name'] : '';
            }
            if ($collectionId) {
                $collection = $this->collectionModel->findById($collectionId);
                $collectionName = $collection ? $collection['name'] : '';
            }
            
            $text = "⚠️ محصولی در بازه اجرت <b>{$wageRange['name']}</b>";
            if ($categoryName) {
                $text .= "\nدسته‌بندی: {$categoryName}";
            }
            if ($collectionName) {
                $text .= "\nکالکشن: {$collectionName}";
            }
            $text .= "\n\nیافت نشد.";
            
            $this->bot->sendMessage($this->bot->getChatId(), $text);
            return;
        }

        $categoryName = '';
        $collectionName = '';
        if ($categoryId) {
            $category = $this->categoryModel->findById($categoryId);
            $categoryName = $category ? $category['name'] : '';
        }
        if ($collectionId) {
            $collection = $this->collectionModel->findById($collectionId);
            $collectionName = $collection ? $collection['name'] : '';
        }

        // Only back button
        $keyboard = [
            'keyboard' => [[['text' => '🔙 بازگشت']]],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        // Send product images with back button on last image
        $this->sendProductImages($products, $keyboard);
    }

    private function sendProductImages($products, $keyboard = null)
    {
        $productsWithMedia = [];
        foreach ($products as $product) {
            $photo = $product['image_file_id'] ?? $product['image_path'] ?? null;
            $video = $product['video_file_id'] ?? null;
            $animation = $product['animation_file_id'] ?? null;
            if ($photo || $video || $animation) {
                $productsWithMedia[] = $product;
            }
        }
        
        if (empty($productsWithMedia)) {
            // If no media, send keyboard in a message
            if ($keyboard) {
                $this->bot->sendMessage($this->bot->getChatId(), '📦 محصولات', $keyboard);
            }
            return;
        }
        
        // Send all media except the last one
        $lastIndex = count($productsWithMedia) - 1;
        $successCount = 0;
        
        foreach ($productsWithMedia as $index => $product) {
            $photo = $product['image_file_id'] ?? $product['image_path'] ?? null;
            $video = $product['video_file_id'] ?? null;
            $animation = $product['animation_file_id'] ?? null;
            
            // Add keyboard only to the last media
            $hasKeyboard = ($index === $lastIndex && $keyboard);
            
            $sent = false;
            
            if ($photo) {
                // Try file_id first, fallback to image_path if file_id fails
                $photoToSend = $product['image_file_id'] ?? null;
                if ($photoToSend) {
                    $result = $hasKeyboard 
                        ? $this->bot->sendPhoto($this->bot->getChatId(), $photoToSend, '', $keyboard)
                        : $this->bot->sendPhoto($this->bot->getChatId(), $photoToSend, '');
                    
                    if ($result) {
                        $sent = true;
                        $successCount++;
                    } elseif ($product['image_path'] ?? null) {
                        // Fallback to image_path if file_id failed
                        $result = $hasKeyboard 
                            ? $this->bot->sendPhoto($this->bot->getChatId(), $product['image_path'], '', $keyboard)
                            : $this->bot->sendPhoto($this->bot->getChatId(), $product['image_path'], '');
                        if ($result) {
                            $sent = true;
                            $successCount++;
                        }
                    }
                } elseif ($product['image_path'] ?? null) {
                    // Use image_path directly
                    $result = $hasKeyboard 
                        ? $this->bot->sendPhoto($this->bot->getChatId(), $product['image_path'], '', $keyboard)
                        : $this->bot->sendPhoto($this->bot->getChatId(), $product['image_path'], '');
                    if ($result) {
                        $sent = true;
                        $successCount++;
                    }
                }
            } elseif ($video) {
                $result = $hasKeyboard 
                    ? $this->bot->sendVideo($this->bot->getChatId(), $video, '', $keyboard)
                    : $this->bot->sendVideo($this->bot->getChatId(), $video, '');
                if ($result) {
                    $sent = true;
                    $successCount++;
                }
            } elseif ($animation) {
                $result = $hasKeyboard 
                    ? $this->bot->sendAnimation($this->bot->getChatId(), $animation, '', $keyboard)
                    : $this->bot->sendAnimation($this->bot->getChatId(), $animation, '');
                if ($result) {
                    $sent = true;
                    $successCount++;
                }
            }
            
            // If this was the last item and we couldn't send it, send keyboard separately
            if ($index === $lastIndex && !$sent && $keyboard) {
                $this->bot->sendMessage($this->bot->getChatId(), '📦', $keyboard);
            }
        }
        
        // If no media was sent successfully, at least send the keyboard
        if ($successCount === 0 && $keyboard) {
            $this->bot->sendMessage($this->bot->getChatId(), '📦', $keyboard);
        }
    }

    private function showContact()
    {
        $contactModel = new Contact();
        $contact = $contactModel->get();
        
        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "☎️ <b>تماس با ما</b>\n\n" .
            "📍 <b>آدرس:</b>\n" .
            "{$contact['address']}\n\n" .
            "📱 <b>شماره تماس و واتساپ:</b>\n" .
            "{$contact['phone']}"
        );
    }

    private function getUserState($telegramId)
    {
        return $this->userStates[$telegramId]['state'] ?? null;
    }

    private function buildProductButtons(array $products, int $perRow = 4): array
    {
        $keyboardButtons = [];
        $row = [];

        foreach ($products as $product) {
            $row[] = ['text' => "🔍 مشاهده کد {$product['product_code']}"];

            if (count($row) === $perRow) {
                $keyboardButtons[] = $row;
                $row = [];
            }
        }

        if (!empty($row)) {
            $keyboardButtons[] = $row;
        }

        return $keyboardButtons;
    }

    private function setUserState($telegramId, $state)
    {
        if (!isset($this->userStates[$telegramId])) {
            $this->userStates[$telegramId] = [];
        }
        $this->userStates[$telegramId]['state'] = $state;
    }

    private function clearUserState($telegramId)
    {
        unset($this->userStates[$telegramId]);
    }

    private function getUserData($telegramId, $key)
    {
        return $this->userStates[$telegramId]['data'][$key] ?? null;
    }

    private function setUserData($telegramId, $key, $value)
    {
        if (!isset($this->userStates[$telegramId])) {
            $this->userStates[$telegramId] = [];
        }
        if (!isset($this->userStates[$telegramId]['data'])) {
            $this->userStates[$telegramId]['data'] = [];
        }
        $this->userStates[$telegramId]['data'][$key] = $value;
    }

    /**
     * Show message requiring channel membership
     */
    private function showChannelMembershipRequired()
    {
        $inlineKeyboard = [
            [[
                'text' => '📢 عضویت در کانال',
                'url' => 'https://t.me/sarvagold'
            ]],
            [[
                'text' => '✅ بررسی مجدد',
                'callback_data' => 'check_channel_membership'
            ]]
        ];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];

        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "⚠️ <b>عضویت در کانال الزامی است</b>\n\n" .
            "برای استفاده از ربات، لطفاً ابتدا در کانال زیر عضو شوید:\n\n" .
            "📢 <a href='https://t.me/sarvagold'>Sarva Gold (ناصر سالک)</a>\n\n" .
            "پس از عضویت، روی دکمه «بررسی مجدد» کلیک کنید.",
            $keyboard
        );
    }
}

