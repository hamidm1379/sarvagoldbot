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

class AdminHandler
{
    private $bot;
    private $userModel;
    private $productModel;
    private $categoryModel;
    private $collectionModel;
    private $weightRangeModel;
    private $wageRangeModel;
    private $contactModel;
    private $adminStates = [];

    public function __construct(Bot $bot)
    {
        $this->bot = $bot;
        $this->userModel = new User();
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->collectionModel = new Collection();
        $this->weightRangeModel = new WeightRange();
        $this->wageRangeModel = new WageRange();
        $this->contactModel = new Contact();
    }

    public function handle($text, $telegramId)
    {
        // Check for "بازگشت به منوی اصلی" first, even if in state
        if ($text === '🔙 بازگشت به منوی اصلی') {
            $this->clearAdminState($telegramId);
            $this->showMainMenu();
            return;
        }

        $state = $this->getAdminState($telegramId);

        if ($state) {
            $this->handleState($text, $telegramId, $state);
            return;
        }

        switch ($text) {
            case '/start':
            case '/admin':
                $this->showAdminMenu();
                break;
            case '🔙 بازگشت به منوی اصلی':
                $this->showMainMenu();
                break;
            case '➕ Add Product':
            case '➕ افزودن محصول':
                $this->startAddProduct($telegramId);
                break;
            case '✏️ Edit Product':
            case '✏️ ویرایش محصول':
                $this->askForProductCodeToEdit($telegramId);
                break;
            case '❌ Delete / Disable Product':
            case '❌ حذف / غیرفعال کردن محصول':
                $this->askForProductCodeToDelete($telegramId);
                break;
            case '🗂 Manage Categories':
            case '🗂 مدیریت دسته‌بندی‌ها':
                $this->showCategoryManagement();
                break;
            case '🧩 Manage Collections':
            case '🧩 مدیریت کالکشن‌ها':
                $this->showCollectionManagement();
                break;
            case '⚖️ Manage Weight Ranges':
            case '⚖️ مدیریت بازه‌های وزن':
                $this->showWeightRangeManagement();
                break;
            case '💰 Manage Wage Ranges':
            case '💰 مدیریت بازه‌های اجرت':
                $this->showWageRangeManagement();
                break;
            case '👥 Approve / Reject Users':
            case '👥 تایید / رد کاربران':
                $this->showPendingUsers();
                break;
            case '📊 View Users List':
            case '📊 مشاهده لیست کاربران':
                $this->showUsersList();
                break;
            case '⭐ مدیریت سطح کاربران':
            case '⭐ Manage User Levels':
                $this->showUserLevelManagement();
                break;
            case '📞 مدیریت تماس با ما':
                $this->showContactManagement();
                break;
            default:
                $this->showAdminMenu();
                break;
        }
    }

    public function handleCallback($data, $telegramId, $messageId)
    {
        $parts = explode(':', $data);
        $action = $parts[0] ?? '';

        switch ($action) {
            case 'admin_menu':
                $this->showAdminMenu();
                break;
            case 'cancel_add_product':
                $this->clearAdminState($telegramId);
                $this->bot->sendMessage($this->bot->getChatId(), "❌ افزودن محصول لغو شد.");
                $this->showAdminMenu();
                break;
            case 'approve_user':
                $userTelegramId = $parts[1] ?? null;
                $this->approveUser($userTelegramId);
                break;
            case 'reject_user':
                $userTelegramId = $parts[1] ?? null;
                $this->rejectUser($userTelegramId);
                break;
            case 'set_user_level':
                $userTelegramId = $parts[1] ?? null;
                $level = $parts[2] ?? null;
                $this->setUserLevel($userTelegramId, $level);
                break;
            case 'manage_user_level':
                $userTelegramId = $parts[1] ?? null;
                $this->showUserLevelOptions($userTelegramId);
                break;
            case 'search_user_level':
                $this->askForUserSearch($telegramId);
                break;
            case 'clear_user_search':
                $this->showUserLevelManagement();
                break;
            case 'delete_category':
                $categoryId = $parts[1] ?? null;
                $this->deleteCategory($categoryId);
                break;
            case 'add_category':
                $this->askForCategoryName($telegramId);
                break;
            case 'delete_collection':
                $collectionId = $parts[1] ?? null;
                $this->deleteCollection($collectionId);
                break;
            case 'set_collection_wage':
                $collectionId = $parts[1] ?? null;
                $this->setAdminData($telegramId, 'collection_wage_id', $collectionId);
                $this->setAdminState($telegramId, 'add_collection_wage');
                $this->bot->sendMessage(
                    $this->bot->getChatId(),
                    "💰 لطفاً درصد اجرت کالکشن را وارد کنید (مثال: 5.5 یا 10):"
                );
                break;
            case 'add_collection':
                $this->askForCollectionName($telegramId);
                break;
            case 'select_collection_category':
                $categoryId = $parts[1] ?? null;
                $this->setAdminData($telegramId, 'collection_category_id', $categoryId == '0' ? null : $categoryId);
                $this->setAdminState($telegramId, 'add_collection_name');
                $this->bot->sendMessage(
                    $this->bot->getChatId(),
                    "➕ لطفاً نام کالکشن جدید را وارد کنید:"
                );
                break;
            case 'category_select':
                $categoryId = $parts[1] ?? null;
                $this->setAdminData($telegramId, 'category_id', $categoryId);
                $this->setAdminState($telegramId, 'add_product_collection');
                $this->askForCollection($categoryId);
                break;
            case 'collection_select':
                $collectionId = $parts[1] ?? null;
                if ($collectionId == '0') {
                    $this->setAdminData($telegramId, 'collection_id', null);
                    $this->setAdminData($telegramId, 'collection_wage', null);
                } else {
                    $this->setAdminData($telegramId, 'collection_id', $collectionId);
                    // Get collection wage if exists
                    $collection = $this->collectionModel->findById($collectionId);
                    $collectionWage = $collection && isset($collection['wage_percentage']) && $collection['wage_percentage'] !== null 
                        ? $collection['wage_percentage'] 
                        : null;
                    $this->setAdminData($telegramId, 'collection_wage', $collectionWage);
                }
                $this->setAdminState($telegramId, 'add_product_code');
                $this->bot->sendMessage(
                    $this->bot->getChatId(),
                    "🏷️ لطفاً کد محصول را وارد کنید (۴ رقم عددی):"
                );
                break;
            case 'edit_product':
                $productId = $parts[1] ?? null;
                $this->showProductEditOptions($productId, $telegramId);
                break;
            case 'delete_product':
                $productId = $parts[1] ?? null;
                $this->deleteProductPermanently($productId);
                break;
            case 'disable_product':
                $productId = $parts[1] ?? null;
                $this->disableProduct($productId);
                break;
            case 'edit_product_field':
                $productId = $parts[1] ?? null;
                $field = $parts[2] ?? null;
                $this->askForFieldValue($telegramId, $productId, $field);
                break;
            case 'edit_category_select':
                $productId = $parts[1] ?? null;
                $categoryId = $parts[2] ?? null;
                $this->updateProductField($productId, 'category_id', $categoryId, $telegramId);
                break;
            case 'edit_collection_select':
                $productId = $parts[1] ?? null;
                $collectionId = $parts[2] ?? null;
                $value = $collectionId == '0' ? '0' : $collectionId;
                $this->updateProductField($productId, 'collection_id', $value, $telegramId);
                break;
            case 'delete_weight_range':
                $weightRangeId = $parts[1] ?? null;
                $this->deleteWeightRange($weightRangeId);
                break;
            case 'add_weight_range':
                $this->askForWeightRangeName($telegramId);
                break;
            case 'weight_range_category_select':
                $categoryId = $parts[1] ?? null;
                $this->setAdminData($telegramId, 'weight_range_category_id', $categoryId == '0' ? null : $categoryId);
                $this->setAdminData($telegramId, 'weight_range_collection_id', null);
                $this->setAdminState($telegramId, 'add_weight_range_min');
                $this->bot->sendMessage(
                    $this->bot->getChatId(),
                    "⚖️ لطفاً حداقل وزن را وارد کنید (گرم):\nمثال: 0"
                );
                break;
            case 'delete_wage_range':
                $wageRangeId = $parts[1] ?? null;
                $this->deleteWageRange($wageRangeId);
                break;
            case 'add_wage_range':
                $this->askForWageRangeName($telegramId);
                break;
            case 'delete_user':
                $userTelegramId = $parts[1] ?? null;
                $this->confirmDeleteUser($userTelegramId);
                break;
            case 'confirm_delete_user_yes':
                $userTelegramId = $parts[1] ?? null;
                $this->deleteUser($userTelegramId);
                break;
            case 'confirm_delete_user_no':
                $this->showUsersList();
                break;
            case 'wage_range_category_select':
                $categoryId = $parts[1] ?? null;
                $this->setAdminData($telegramId, 'wage_range_category_id', $categoryId == '0' ? null : $categoryId);
                $this->setAdminData($telegramId, 'wage_range_collection_id', null);
                $this->setAdminState($telegramId, 'add_wage_range_min');
                $this->bot->sendMessage(
                    $this->bot->getChatId(),
                    "💰 لطفاً حداقل اجرت را وارد کنید (%):\nمثال: 0"
                );
                break;
            case 'edit_contact_address':
                $this->askForContactAddress($telegramId);
                break;
            case 'edit_contact_phone':
                $this->askForContactPhone($telegramId);
                break;
        }
    }

    private function showAdminMenu()
    {
        $keyboard = [
            'keyboard' => [
                [
                    ['text' => '➕ افزودن محصول'],
                    ['text' => '✏️ ویرایش محصول']
                ],
                [
                    ['text' => '❌ حذف / غیرفعال کردن محصول'],
                    ['text' => '🗂 مدیریت دسته‌بندی‌ها']
                ],
                [
                    ['text' => '🧩 مدیریت کالکشن‌ها'],
                    ['text' => '⚖️ مدیریت بازه‌های وزن']
                ],
                [
                    ['text' => '💰 مدیریت بازه‌های اجرت'],
                    ['text' => '👥 تایید / رد کاربران']
                ],
                [
                    ['text' => '📊 مشاهده لیست کاربران'],
                    ['text' => '⭐ مدیریت سطح کاربران']
                ],
                [
                    ['text' => '📞 مدیریت تماس با ما']
                ],
                [
                    ['text' => '🔙 بازگشت به منوی اصلی']
                ]
            ],
            'resize_keyboard' => true,
            'persistent' => true
        ];

        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "🔐 <b>پنل مدیریت</b>\n\n" .
            "لطفاً یکی از گزینه‌های زیر را انتخاب کنید:",
            $keyboard
        );
    }

    private function showMainMenu()
    {
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

        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "🏠 <b>منوی اصلی</b>\n\n" .
            "لطفاً یکی از گزینه‌های زیر را انتخاب کنید:",
            $keyboard
        );
    }

    private function startAddProduct($telegramId)
    {
        $this->setAdminState($telegramId, 'add_product_image');
        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "➕ <b>افزودن محصول جدید</b>\n\n" .
            "ابتدا تصویر، گیف یا ویدیو محصول را ارسال کنید (الزامی)."
        );
    }

    private function handleState($text, $telegramId, $state)
    {
        $message = $this->bot->getMessage();
        $photo = $message['photo'] ?? null;
        $video = $message['video'] ?? null;
        $animation = $message['animation'] ?? null;

        switch ($state) {
            case 'add_product_image':
                if (!$photo && !$video && !$animation) {
                    $keyboard = [
                        'inline_keyboard' => [[
                            ['text' => '❌ انصراف', 'callback_data' => 'cancel_add_product']
                        ]]
                    ];
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ لطفاً تصویر، گیف یا ویدیو محصول را ارسال کنید.", $keyboard);
                    return;
                }
                
                // Handle photo
                if ($photo) {
                    $imageFileId = end($photo)['file_id'];
                    $this->setAdminData($telegramId, 'image_file_id', $imageFileId);
                    $this->setAdminData($telegramId, 'video_file_id', null);
                    $this->setAdminData($telegramId, 'animation_file_id', null);
                }
                // Handle video
                elseif ($video) {
                    $videoFileId = $video['file_id'];
                    $this->setAdminData($telegramId, 'video_file_id', $videoFileId);
                    $this->setAdminData($telegramId, 'image_file_id', null);
                    $this->setAdminData($telegramId, 'animation_file_id', null);
                }
                // Handle animation (GIF)
                elseif ($animation) {
                    $animationFileId = $animation['file_id'];
                    $this->setAdminData($telegramId, 'animation_file_id', $animationFileId);
                    $this->setAdminData($telegramId, 'image_file_id', null);
                    $this->setAdminData($telegramId, 'video_file_id', null);
                }
                
                $this->setAdminState($telegramId, 'add_product_category');
                $this->askForCategory();
                break;

            case 'add_product_collection':
                // Handle text input "0" to skip collection
                if ($text === '0') {
                    $this->setAdminData($telegramId, 'collection_id', null);
                    $this->setAdminState($telegramId, 'add_product_code');
                    $this->bot->sendMessage(
                        $this->bot->getChatId(),
                        "🏷️ لطفاً کد محصول را وارد کنید (۴ رقم عددی):"
                    );
                } else {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ لطفاً از دکمه‌های زیر استفاده کنید یا '0' را برای رد کردن وارد کنید.");
                }
                break;

            case 'add_product_code':
                if (empty(trim($text))) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ لطفاً کد محصول را وارد کنید.");
                    return;
                }
                // Normalize Persian numbers to English
                $normalizedCode = $this->normalizePersianNumbers($text);
                // Validate: exactly 4 digits, no symbols, no decimals
                if (!$this->isValidProductCode($text)) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ کد محصول باید دقیقاً ۴ رقم عددی باشد (بدون علامت و اعشار).");
                    return;
                }
                // Check if code already exists (using normalized code)
                $existing = $this->productModel->findByCodeForAdmin($normalizedCode);
                if ($existing) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ این کد محصول قبلاً استفاده شده است. لطفاً کد دیگری وارد کنید.");
                    return;
                }
                // Store normalized code
                $this->setAdminData($telegramId, 'product_code', $normalizedCode);
                
                // Check if collection has wage
                $collectionWage = $this->getAdminData($telegramId, 'collection_wage');
                if ($collectionWage !== null) {
                    // Use collection wage, skip asking for wage
                    $this->setAdminData($telegramId, 'wage_percentage', $collectionWage);
                    $this->setAdminState($telegramId, 'add_product_weight');
                    $this->bot->sendMessage(
                        $this->bot->getChatId(),
                        "✅ اجرت از کالکشن استفاده می‌شود: {$collectionWage}%\n\n" .
                        "⚖️ لطفاً وزن را وارد کنید (گرم):\nمثال: 6.25"
                    );
                } else {
                    // Ask for wage
                    $this->setAdminState($telegramId, 'add_product_wage');
                    $this->bot->sendMessage(
                        $this->bot->getChatId(),
                        "💰 لطفاً درصد اجرت را وارد کنید:\nمثال: 8"
                    );
                }
                break;

            case 'add_product_wage':
                if (!is_numeric($text)) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ درصد اجرت باید عدد باشد.");
                    return;
                }
                $this->setAdminData($telegramId, 'wage_percentage', $text);
                $this->setAdminState($telegramId, 'add_product_weight');
                $this->bot->sendMessage(
                    $this->bot->getChatId(),
                    "⚖️ لطفاً وزن را وارد کنید (گرم):\nمثال: 6.25"
                );
                break;

            case 'add_product_weight':
                if (!is_numeric($text)) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ وزن باید عدد باشد.");
                    return;
                }
                $this->setAdminData($telegramId, 'weight', $text);

                $data = [
                    'product_code' => $this->getAdminData($telegramId, 'product_code'),
                    'name' => $this->getAdminData($telegramId, 'product_code'),
                    'category_id' => $this->getAdminData($telegramId, 'category_id'),
                    'collection_id' => $this->getAdminData($telegramId, 'collection_id'),
                    'wage_percentage' => $this->getAdminData($telegramId, 'wage_percentage'),
                    'weight' => $this->getAdminData($telegramId, 'weight'),
                    'image_file_id' => $this->getAdminData($telegramId, 'image_file_id'),
                    'video_file_id' => $this->getAdminData($telegramId, 'video_file_id'),
                    'animation_file_id' => $this->getAdminData($telegramId, 'animation_file_id'),
                    'status' => 'active'
                ];

                $productId = $this->productModel->create($data);
                $this->clearAdminState($telegramId);

                $this->bot->sendMessage(
                    $this->bot->getChatId(),
                    "✅ محصول با موفقیت افزوده شد!\n\n" .
                    "کد محصول: {$data['product_code']}"
                );
                $this->showAdminMenu();
                break;

            case 'edit_product_code':
                // Normalize Persian numbers to English
                $normalizedCode = $this->normalizePersianNumbers($text);
                $product = $this->productModel->findByCodeForAdmin($normalizedCode);
                if (!$product) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ محصولی با این کد یافت نشد.");
                    return;
                }
                $this->clearAdminState($telegramId);
                $this->showProductEditOptions($product['id'], $telegramId);
                break;

            case 'delete_product_code':
                // Normalize Persian numbers to English
                $normalizedCode = $this->normalizePersianNumbers($text);
                $product = $this->productModel->findByCodeForAdmin($normalizedCode);
                if (!$product) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ محصولی با این کد یافت نشد.");
                    return;
                }
                $this->clearAdminState($telegramId);
                $this->confirmDeleteProduct($product['id'], $telegramId);
                break;

            case 'add_category_name':
                $existing = $this->categoryModel->findByName($text);
                if ($existing) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ این دسته‌بندی قبلاً وجود دارد.");
                    return;
                }
                $this->categoryModel->create($text);
                $this->clearAdminState($telegramId);
                $this->bot->sendMessage($this->bot->getChatId(), "✅ دسته‌بندی افزوده شد: {$text}");
                $this->showCategoryManagement();
                break;

            case 'add_collection_name':
                $categoryId = $this->getAdminData($telegramId, 'collection_category_id');
                $existing = $this->collectionModel->findByName($text);
                if ($existing) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ این کالکشن قبلاً وجود دارد.");
                    return;
                }
                $this->setAdminData($telegramId, 'collection_name', $text);
                $this->setAdminState($telegramId, 'add_collection_wage_new');
                $this->bot->sendMessage(
                    $this->bot->getChatId(),
                    "💰 لطفاً درصد اجرت کالکشن را وارد کنید (یا 0 برای بدون اجرت):"
                );
                break;

            case 'add_collection_wage_new':
                $categoryId = $this->getAdminData($telegramId, 'collection_category_id');
                $collectionName = $this->getAdminData($telegramId, 'collection_name');
                $wagePercentage = null;
                if (is_numeric($text) && floatval($text) > 0) {
                    $wagePercentage = floatval($text);
                }
                $this->collectionModel->create($collectionName, $categoryId, $wagePercentage);
                $this->clearAdminState($telegramId);
                $wageText = $wagePercentage ? " با اجرت {$wagePercentage}%" : " بدون اجرت";
                $this->bot->sendMessage($this->bot->getChatId(), "✅ کالکشن افزوده شد: {$collectionName}{$wageText}");
                $this->showCollectionManagement();
                break;

            case 'add_collection_wage':
                $collectionId = $this->getAdminData($telegramId, 'collection_wage_id');
                $wagePercentage = null;
                if (is_numeric($text) && floatval($text) > 0) {
                    $wagePercentage = floatval($text);
                }
                $this->collectionModel->updateWage($collectionId, $wagePercentage);
                $this->clearAdminState($telegramId);
                $collection = $this->collectionModel->findById($collectionId);
                $wageText = $wagePercentage ? "{$wagePercentage}%" : "حذف شد";
                $this->bot->sendMessage($this->bot->getChatId(), "✅ اجرت کالکشن {$collection['name']} به {$wageText} تنظیم شد.");
                $this->showCollectionManagement();
                break;

            case 'edit_product_field':
                $productId = $this->getAdminData($telegramId, 'edit_product_id');
                $field = $this->getAdminData($telegramId, 'edit_field');
                // Skip category_id and collection_id as they are handled via callbacks
                if ($field === 'category_id' || $field === 'collection_id') {
                    return;
                }
                // For image field, check if media (photo/video/animation) was sent
                if ($field === 'image') {
                    // If no media was sent and no text (empty message), show error
                    if (!$photo && !$video && !$animation && empty($text)) {
                        $this->bot->sendMessage($this->bot->getChatId(), "⚠️ لطفاً تصویر، گیف یا ویدیو ارسال کنید.");
                        return;
                    }
                    // If media was sent, pass empty string as text (updateProductField will read from message)
                    if ($photo || $video || $animation) {
                        $this->updateProductField($productId, $field, '', $telegramId);
                        return;
                    }
                }
                // For other fields, require text input
                if (empty($text) && $field !== 'image') {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ لطفاً مقدار جدید را وارد کنید.");
                    return;
                }
                $this->updateProductField($productId, $field, $text, $telegramId);
                break;

            case 'add_weight_range_name':
                if (empty(trim($text))) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ لطفاً نام بازه وزن را وارد کنید.");
                    return;
                }
                $this->setAdminData($telegramId, 'weight_range_name', $text);
                $this->setAdminState($telegramId, 'add_weight_range_category');
                $this->askForWeightRangeCategory();
                break;

            case 'add_weight_range_min':
                if (!is_numeric($text)) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ حداقل وزن باید عدد باشد.");
                    return;
                }
                $this->setAdminData($telegramId, 'weight_range_min', (float)$text);
                $this->setAdminState($telegramId, 'add_weight_range_max');
                $this->bot->sendMessage(
                    $this->bot->getChatId(),
                    "⚖️ لطفاً حداکثر وزن را وارد کنید (گرم):\nمثال: 6"
                );
                break;

            case 'add_weight_range_max':
                if (!is_numeric($text)) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ حداکثر وزن باید عدد باشد.");
                    return;
                }
                $minWeight = $this->getAdminData($telegramId, 'weight_range_min');
                $maxWeight = (float)$text;
                
                if ($maxWeight <= $minWeight) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ حداکثر وزن باید بیشتر از حداقل وزن باشد.");
                    return;
                }

                $data = [
                    'name' => $this->getAdminData($telegramId, 'weight_range_name'),
                    'min_weight' => $minWeight,
                    'max_weight' => $maxWeight,
                    'category_id' => $this->getAdminData($telegramId, 'weight_range_category_id'),
                    'collection_id' => null
                ];

                $this->weightRangeModel->create($data);
                $this->clearAdminState($telegramId);
                $this->bot->sendMessage(
                    $this->bot->getChatId(),
                    "✅ بازه وزن با موفقیت افزوده شد!\n\n" .
                    "نام: {$data['name']}\n" .
                    "بازه: {$minWeight} تا {$maxWeight} گرم"
                );
                $this->showWeightRangeManagement();
                break;
            case 'add_wage_range_name':
                if (empty(trim($text))) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ لطفاً نام بازه اجرت را وارد کنید.");
                    return;
                }
                $this->setAdminData($telegramId, 'wage_range_name', $text);
                $this->setAdminState($telegramId, 'add_wage_range_category');
                $this->askForWageRangeCategory();
                break;

            case 'add_wage_range_min':
                if (!is_numeric($text)) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ حداقل اجرت باید عدد باشد.");
                    return;
                }
                $this->setAdminData($telegramId, 'wage_range_min', (float)$text);
                $this->setAdminState($telegramId, 'add_wage_range_max');
                $this->bot->sendMessage(
                    $this->bot->getChatId(),
                    "💰 لطفاً حداکثر اجرت را وارد کنید (%):\nمثال: 15"
                );
                break;

            case 'add_wage_range_max':
                if (!is_numeric($text)) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ حداکثر اجرت باید عدد باشد.");
                    return;
                }
                $minWage = $this->getAdminData($telegramId, 'wage_range_min');
                $maxWage = (float)$text;
                
                if ($maxWage <= $minWage) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ حداکثر اجرت باید بیشتر از حداقل اجرت باشد.");
                    return;
                }

                $data = [
                    'name' => $this->getAdminData($telegramId, 'wage_range_name'),
                    'min_wage' => $minWage,
                    'max_wage' => $maxWage,
                    'category_id' => $this->getAdminData($telegramId, 'wage_range_category_id'),
                    'collection_id' => null
                ];

                $this->wageRangeModel->create($data);
                $this->clearAdminState($telegramId);
                $this->bot->sendMessage(
                    $this->bot->getChatId(),
                    "✅ بازه اجرت با موفقیت افزوده شد!\n\n" .
                    "نام: {$data['name']}\n" .
                    "بازه: {$minWage}% تا {$maxWage}%"
                );
                $this->showWageRangeManagement();
                break;

            case 'edit_contact_address':
                if (empty(trim($text))) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ لطفاً آدرس را وارد کنید.");
                    return;
                }
                $phone = $this->getAdminData($telegramId, 'contact_phone');
                $this->contactModel->update($text, $phone);
                $this->clearAdminState($telegramId);
                $this->bot->sendMessage(
                    $this->bot->getChatId(),
                    "✅ آدرس با موفقیت به‌روزرسانی شد!\n\n" .
                    "📍 <b>آدرس جدید:</b>\n{$text}"
                );
                $this->showContactManagement();
                break;

            case 'edit_contact_phone':
                if (empty(trim($text))) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ لطفاً شماره تماس را وارد کنید.");
                    return;
                }
                $address = $this->getAdminData($telegramId, 'contact_address');
                $this->contactModel->update($address, $text);
                $this->clearAdminState($telegramId);
                $this->bot->sendMessage(
                    $this->bot->getChatId(),
                    "✅ شماره تماس با موفقیت به‌روزرسانی شد!\n\n" .
                    "📱 <b>شماره تماس جدید:</b>\n{$text}"
                );
                $this->showContactManagement();
                break;
            case 'search_user_level_input':
                $this->handleUserSearch($text, $telegramId);
                break;
        }
    }

    private function askForCategory()
    {
        $categories = $this->categoryModel->getAll();
        $text = "📂 لطفاً دسته‌بندی را انتخاب کنید:\n\n";
        
        $inlineKeyboard = [];
        foreach ($categories as $category) {
            $inlineKeyboard[] = [[
                'text' => $category['name'],
                'callback_data' => "category_select:{$category['id']}"
            ]];
        }

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function askForCollection($categoryId)
    {
        $collections = $this->collectionModel->getAll($categoryId);
        $text = "🧩 لطفاً کالکشن را انتخاب کنید:\n\n";
        
        if (empty($collections)) {
            $text .= "⚠️ کالکشنی برای این دسته‌بندی وجود ندارد.\n\n";
        }

        $inlineKeyboard = [];
        foreach ($collections as $collection) {
            $inlineKeyboard[] = [[
                'text' => $collection['name'],
                'callback_data' => "collection_select:{$collection['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => 'رد کردن',
            'callback_data' => 'collection_select:0'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function askForProductCodeToEdit($telegramId)
    {
        $this->setAdminState($telegramId, 'edit_product_code');
        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "✏️ لطفاً کد محصول را وارد کنید:"
        );
    }

    private function askForProductCodeToDelete($telegramId)
    {
        $this->setAdminState($telegramId, 'delete_product_code');
        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "❌ لطفاً کد محصول را وارد کنید:"
        );
    }

    private function showProductEditOptions($productId, $telegramId)
    {
        $product = $this->productModel->findById($productId);
        if (!$product) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ محصول یافت نشد.");
            return;
        }

        $text = "✏️ <b>ویرایش محصول</b>\n\n";
        $text .= "کد: {$product['product_code']}\n";
        $text .= "نام: {$product['name']}\n";
        $text .= "دسته‌بندی: {$product['category_name']}\n";
        if ($product['collection_name']) {
            $text .= "کالکشن: {$product['collection_name']}\n";
        }
        $text .= "اجرت: {$product['wage_percentage']}%\n";
        $text .= "وزن: {$product['weight']} گرم\n";

        $inlineKeyboard = [
            [['text' => 'ویرایش کد', 'callback_data' => "edit_product_field:{$productId}:product_code"]],
            [['text' => 'ویرایش نام', 'callback_data' => "edit_product_field:{$productId}:name"]],
            [['text' => 'ویرایش دسته‌بندی', 'callback_data' => "edit_product_field:{$productId}:category_id"]],
            [['text' => 'ویرایش کالکشن', 'callback_data' => "edit_product_field:{$productId}:collection_id"]],
            [['text' => 'ویرایش اجرت', 'callback_data' => "edit_product_field:{$productId}:wage_percentage"]],
            [['text' => 'ویرایش وزن', 'callback_data' => "edit_product_field:{$productId}:weight"]],
            [['text' => 'ویرایش تصویر', 'callback_data' => "edit_product_field:{$productId}:image"]],
            [['text' => '🔙 بازگشت', 'callback_data' => 'admin_menu']]
        ];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function askForFieldValue($telegramId, $productId, $field)
    {
        $this->setAdminState($telegramId, 'edit_product_field');
        $this->setAdminData($telegramId, 'edit_product_id', $productId);
        $this->setAdminData($telegramId, 'edit_field', $field);

        if ($field === 'category_id') {
            $this->askForCategoryForEdit($productId);
            return;
        }

        if ($field === 'collection_id') {
            $product = $this->productModel->findById($productId);
            $categoryId = $product['category_id'] ?? null;
            $this->askForCollectionForEdit($productId, $categoryId);
            return;
        }

        $messages = [
            'product_code' => 'لطفاً کد جدید را وارد کنید (۴ رقم عددی):',
            'name' => 'لطفاً نام جدید را وارد کنید:',
            'wage_percentage' => 'لطفاً درصد اجرت جدید را وارد کنید:',
            'weight' => 'لطفاً وزن جدید را وارد کنید:',
            'image' => 'لطفاً تصویر، گیف یا ویدیو جدید را ارسال کنید:'
        ];

        $message = $messages[$field] ?? 'لطفاً مقدار جدید را وارد کنید:';
        $this->bot->sendMessage($this->bot->getChatId(), $message);
    }

    private function askForCategoryForEdit($productId)
    {
        $categories = $this->categoryModel->getAll();
        $text = "📂 لطفاً دسته‌بندی جدید را انتخاب کنید:\n\n";
        
        $inlineKeyboard = [];
        foreach ($categories as $category) {
            $inlineKeyboard[] = [[
                'text' => $category['name'],
                'callback_data' => "edit_category_select:{$productId}:{$category['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'admin_menu'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function askForCollectionForEdit($productId, $categoryId = null)
    {
        $collections = $this->collectionModel->getAll($categoryId);
        $text = "🧩 لطفاً کالکشن جدید را انتخاب کنید:\n\n";
        
        if (empty($collections)) {
            $text .= "⚠️ کالکشنی برای این دسته‌بندی وجود ندارد.\n\n";
        }

        $inlineKeyboard = [];
        foreach ($collections as $collection) {
            $inlineKeyboard[] = [[
                'text' => $collection['name'],
                'callback_data' => "edit_collection_select:{$productId}:{$collection['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => 'حذف کالکشن',
            'callback_data' => "edit_collection_select:{$productId}:0"
        ]];
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'admin_menu'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function updateProductField($productId, $field, $value, $telegramId)
    {
        $updateData = [];

        switch ($field) {
            case 'product_code':
                // Normalize Persian numbers to English
                $normalizedCode = $this->normalizePersianNumbers($value);
                // Validate: exactly 4 digits, no symbols, no decimals
                if (!$this->isValidProductCode($value)) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ کد محصول باید دقیقاً ۴ رقم عددی باشد (بدون علامت و اعشار).");
                    return;
                }
                // Store normalized code
                $updateData['product_code'] = $normalizedCode;
                break;
            case 'name':
                if (empty(trim($value))) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ نام محصول نمی‌تواند خالی باشد.");
                    return;
                }
                $updateData['name'] = $value;
                break;
            case 'category_id':
                if (!is_numeric($value)) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ ID دسته‌بندی باید عدد باشد.");
                    return;
                }
                $updateData['category_id'] = $value;
                break;
            case 'collection_id':
                if ($value == '0') {
                    $updateData['collection_id'] = null;
                } else {
                    if (!is_numeric($value)) {
                        $this->bot->sendMessage($this->bot->getChatId(), "⚠️ ID کالکشن باید عدد باشد.");
                        return;
                    }
                    $updateData['collection_id'] = $value;
                }
                break;
            case 'wage_percentage':
                if (!is_numeric($value)) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ درصد اجرت باید عدد باشد.");
                    return;
                }
                $updateData['wage_percentage'] = $value;
                break;
            case 'weight':
                if (!is_numeric($value)) {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ وزن باید عدد باشد.");
                    return;
                }
                $updateData['weight'] = $value;
                break;
            case 'image':
                $message = $this->bot->getMessage();
                $photo = $message['photo'] ?? null;
                $video = $message['video'] ?? null;
                $animation = $message['animation'] ?? null;
                
                if ($photo) {
                    $updateData['image_file_id'] = end($photo)['file_id'];
                    $updateData['video_file_id'] = null;
                    $updateData['animation_file_id'] = null;
                } elseif ($video) {
                    $updateData['video_file_id'] = $video['file_id'];
                    $updateData['image_file_id'] = null;
                    $updateData['animation_file_id'] = null;
                } elseif ($animation) {
                    $updateData['animation_file_id'] = $animation['file_id'];
                    $updateData['image_file_id'] = null;
                    $updateData['video_file_id'] = null;
                } else {
                    $this->bot->sendMessage($this->bot->getChatId(), "⚠️ لطفاً تصویر، گیف یا ویدیو ارسال کنید.");
                    return;
                }
                break;
        }

        $this->productModel->update($productId, $updateData);
        $this->clearAdminState($telegramId);
        $this->bot->sendMessage($this->bot->getChatId(), "✅ محصول با موفقیت به‌روزرسانی شد.");
        $this->showAdminMenu();
    }

    private function confirmDeleteProduct($productId, $telegramId)
    {
        $product = $this->productModel->findById($productId);
        if (!$product) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ محصول یافت نشد.");
            return;
        }

        $text = "❌ <b>حذف / غیرفعال کردن محصول</b>\n\n";
        $text .= "کد: {$product['product_code']}\n";
        $text .= "آیا مطمئن هستید؟";

        $inlineKeyboard = [
            [['text' => '🗑 حذف کامل', 'callback_data' => "delete_product:{$productId}"]],
            [['text' => '🚫 غیرفعال کردن', 'callback_data' => "disable_product:{$productId}"]],
            [['text' => '❌ انصراف', 'callback_data' => 'admin_menu']]
        ];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function disableProduct($productId)
    {
        $this->productModel->update($productId, ['status' => 'inactive']);
        $this->bot->sendMessage($this->bot->getChatId(), "✅ محصول غیرفعال شد.");
        $this->showAdminMenu();
    }

    private function deleteProductPermanently($productId)
    {
        $product = $this->productModel->findById($productId);
        if (!$product) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ محصول یافت نشد.");
            $this->showAdminMenu();
            return;
        }

        $this->productModel->deletePermanently($productId);
        $this->bot->sendMessage($this->bot->getChatId(), "✅ محصول به طور کامل حذف شد.");
        $this->showAdminMenu();
    }

    private function showCategoryManagement()
    {
        $categories = $this->categoryModel->getAll();
        
        $text = "🗂 <b>مدیریت دسته‌بندی‌ها</b>\n\n";
        if (empty($categories)) {
            $text .= "هیچ دسته‌بندی وجود ندارد.\n\n";
        } else {
            foreach ($categories as $category) {
                $text .= "🔹 {$category['name']}\n";
            }
            $text .= "\n";
        }

        $inlineKeyboard = [];
        foreach ($categories as $category) {
            $inlineKeyboard[] = [[
                'text' => "❌ حذف {$category['name']}",
                'callback_data' => "delete_category:{$category['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => '➕ افزودن دسته‌بندی',
            'callback_data' => 'add_category'
        ]];
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'admin_menu'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function askForCategoryName($telegramId)
    {
        $this->setAdminState($telegramId, 'add_category_name');
        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "➕ لطفاً نام دسته‌بندی جدید را وارد کنید:"
        );
    }

    private function deleteCategory($categoryId)
    {
        try {
            $this->categoryModel->delete($categoryId);
            $this->bot->sendMessage($this->bot->getChatId(), "✅ دسته‌بندی حذف شد.");
        } catch (\Exception $e) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ خطا در حذف دسته‌بندی. ممکن است محصولاتی به آن وابسته باشند.");
        }
        $this->showCategoryManagement();
    }

    private function showCollectionManagement()
    {
        $collections = $this->collectionModel->getAll();
        
        $text = "🧩 <b>مدیریت کالکشن‌ها</b>\n\n";
        if (empty($collections)) {
            $text .= "هیچ کالکشنی وجود ندارد.\n\n";
        } else {
            foreach ($collections as $collection) {
                $categoryName = '';
                if ($collection['category_id']) {
                    $category = $this->categoryModel->findById($collection['category_id']);
                    $categoryName = $category ? " ({$category['name']})" : '';
                }
                $wageInfo = '';
                if (isset($collection['wage_percentage']) && $collection['wage_percentage'] !== null) {
                    $wageInfo = " | 💰 اجرت: {$collection['wage_percentage']}%";
                }
                $text .= "🔹 {$collection['name']}{$categoryName}{$wageInfo}\n";
            }
            $text .= "\n";
        }

        $inlineKeyboard = [];
        foreach ($collections as $collection) {
            $inlineKeyboard[] = [
                [
                    'text' => "💰 اجرت {$collection['name']}",
                    'callback_data' => "set_collection_wage:{$collection['id']}"
                ],
                [
                    'text' => "❌ حذف {$collection['name']}",
                    'callback_data' => "delete_collection:{$collection['id']}"
                ]
            ];
        }
        $inlineKeyboard[] = [[
            'text' => '➕ افزودن کالکشن',
            'callback_data' => 'add_collection'
        ]];
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'admin_menu'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function askForCollectionName($telegramId)
    {
        $categories = $this->categoryModel->getAll();
        $text = "➕ لطفاً دسته‌بندی کالکشن را انتخاب کنید:\n\n";
        
        $inlineKeyboard = [];
        foreach ($categories as $category) {
            $inlineKeyboard[] = [[
                'text' => $category['name'],
                'callback_data' => "select_collection_category:{$category['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => 'بدون دسته‌بندی',
            'callback_data' => 'select_collection_category:0'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function deleteCollection($collectionId)
    {
        $this->collectionModel->delete($collectionId);
        $this->bot->sendMessage($this->bot->getChatId(), "✅ کالکشن حذف شد.");
        $this->showCollectionManagement();
    }

    private function showPendingUsers()
    {
        $users = $this->userModel->getAllPending();
        
        if (empty($users)) {
            $this->bot->sendMessage($this->bot->getChatId(), "✅ هیچ کاربری در انتظار تایید نیست.");
            return;
        }

        $text = "👥 <b>کاربران در انتظار تایید</b>\n\n";
        
        $inlineKeyboard = [];
        foreach ($users as $user) {
            $text .= "👤 {$user['first_name']} {$user['last_name']}\n";
            $text .= "🆔 {$user['internal_id']}\n";
            $text .= "📅 " . date('Y-m-d H:i', strtotime($user['created_at'])) . "\n\n";
            
            $inlineKeyboard[] = [
                [
                    'text' => "✅ تایید {$user['first_name']}",
                    'callback_data' => "approve_user:{$user['telegram_id']}"
                ],
                [
                    'text' => "❌ رد {$user['first_name']}",
                    'callback_data' => "reject_user:{$user['telegram_id']}"
                ]
            ];
        }
        
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'admin_menu'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function approveUser($telegramId)
    {
        $this->userModel->updateStatus($telegramId, 'approved');
        $user = $this->userModel->findByTelegramId($telegramId);
        
        $this->bot->sendMessage(
            $telegramId,
            "✅ درخواست شما تایید شد!\n\n" .
            "اکنون می‌توانید از ربات استفاده کنید.\n\n" .
            "🆔 کد کاربری شما: <b>{$user['internal_id']}</b>"
        );
        
        $this->bot->sendMessage($this->bot->getChatId(), "✅ کاربر تایید شد.");
        $this->showPendingUsers();
    }

    private function rejectUser($telegramId)
    {
        $this->userModel->updateStatus($telegramId, 'rejected');
        
        $this->bot->sendMessage(
            $telegramId,
            "❌ متأسفانه درخواست شما رد شد.\n\n" .
            "لطفاً با پشتیبانی تماس بگیرید."
        );
        
        $this->bot->sendMessage($this->bot->getChatId(), "❌ کاربر رد شد.");
        $this->showPendingUsers();
    }

    private function showUsersList()
    {
        $users = $this->userModel->getAll();
        
        if (empty($users)) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ هیچ کاربری وجود ندارد.");
            return;
        }

        // Calculate statistics
        $approved = 0;
        $pending = 0;
        $rejected = 0;

        foreach ($users as $user) {
            $status = $user['status'];
            if ($status === 'approved') $approved++;
            elseif ($status === 'pending') $pending++;
            elseif ($status === 'rejected') $rejected++;
        }

        // Create header with statistics
        $text = "📊 <b>لیست کاربران</b>\n\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "📈 <b>آمار کلی:</b>\n";
        $text .= "✅ تایید شده: <b>{$approved}</b>\n";
        $text .= "⏳ در انتظار: <b>{$pending}</b>\n";
        $text .= "❌ رد شده: <b>{$rejected}</b>\n";
        $text .= "📊 کل کاربران: <b>" . count($users) . "</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        // Create organized and complete list format
        $text .= "<b>📋 لیست کاربران:</b>\n\n";

        $userCount = 0;
        $maxUsersPerMessage = 15; // Limit users per message to avoid message length issues
        
        // Display users in organized format with all information
        foreach ($users as $index => $user) {
            $status = $user['status'];
            $statusText = [
                'approved' => '✅ تایید شده',
                'pending' => '⏳ در انتظار',
                'rejected' => '❌ رد شده'
            ][$status] ?? $status;
            
            $fullName = trim($user['first_name'] . ' ' . $user['last_name']);
            $internalId = $user['internal_id'];
            $createdAt = date('Y/m/d H:i', strtotime($user['created_at']));
            
            // Format as organized list item with all information
            $text .= "<b>┌─────────────────────────────────────</b>\n";
            $text .= "<b>│</b> 👤 <b>نام:</b> {$fullName}\n";
            $text .= "<b>│</b> 🆔 <b>کد کاربری:</b> <code>{$internalId}</code>\n";
            $text .= "<b>│</b> 📊 <b>وضعیت:</b> {$statusText}\n";
            $text .= "<b>│</b> 📅 <b>تاریخ ثبت:</b> {$createdAt}\n";
            $text .= "<b>└─────────────────────────────────────</b>\n\n";
            
            $userCount++;
            
            // If we've reached the limit, send this message and start a new one
            if ($userCount >= $maxUsersPerMessage && $index < count($users) - 1) {
                $text .= "<i>... ادامه در پیام بعدی</i>";
                
                $keyboard = [
                    'inline_keyboard' => [[
                        ['text' => '🔙 بازگشت', 'callback_data' => 'admin_menu']
                    ]]
                ];
                $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
                
                // Reset for next batch
                $text = "<b>📋 ادامه لیست کاربران:</b>\n\n";
                $userCount = 0;
            }
        }

        // Add back button only (no delete buttons)
        $keyboard = [
            'inline_keyboard' => [[
                ['text' => '🔙 بازگشت', 'callback_data' => 'admin_menu']
            ]]
        ];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function showUserLevelManagement()
    {
        $users = $this->userModel->getAll();
        
        if (empty($users)) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ هیچ کاربری وجود ندارد.");
            return;
        }

        // Calculate level statistics
        $levelStats = [
            'general' => 0,
            'vip' => 0,
            'level1' => 0,
            'level2' => 0,
            'level3' => 0,
            'level4' => 0
        ];

        foreach ($users as $user) {
            $level = $user['level'] ?? 'general';
            if (isset($levelStats[$level])) {
                $levelStats[$level]++;
            }
        }

        $levelNames = [
            'general' => '👤 عمومی',
            'vip' => '⭐ VIP',
            'level1' => '1️⃣ سطح یک',
            'level2' => '2️⃣ سطح دو',
            'level3' => '3️⃣ سطح سه',
            'level4' => '4️⃣ سطح چهار'
        ];

        $text = "⭐ <b>مدیریت سطح کاربران</b>\n\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "📊 <b>آمار سطوح:</b>\n";
        foreach ($levelStats as $level => $count) {
            $levelText = $levelNames[$level] ?? $level;
            $text .= "{$levelText}: <b>{$count}</b>\n";
        }
        $text .= "📊 کل کاربران: <b>" . count($users) . "</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $text .= "📋 <b>لیست کاربران:</b>\n\n";

        $inlineKeyboard = [];
        $userCount = 0;
        $maxUsersPerMessage = 8;

        foreach ($users as $index => $user) {
            $userLevel = $user['level'] ?? 'general';
            $levelText = $levelNames[$userLevel] ?? '👤 عمومی';
            $fullName = trim($user['first_name'] . ' ' . $user['last_name']);
            $internalId = $user['internal_id'];
            
            // Format as organized list item
            $text .= "<b>┌─────────────────────────────────────</b>\n";
            $text .= "<b>│</b> 👤 <b>نام:</b> {$fullName}\n";
            $text .= "<b>│</b> 🆔 <b>کد کاربری:</b> <code>{$internalId}</code>\n";
            $text .= "<b>│</b> ⭐ <b>سطح:</b> {$levelText}\n";
            $text .= "<b>└─────────────────────────────────────</b>\n\n";
            
            $inlineKeyboard[] = [[
                'text' => "⭐ تغییر سطح {$fullName}",
                'callback_data' => "manage_user_level:{$user['telegram_id']}"
            ]];
            
            $userCount++;
            
            // If we've reached the limit, send this message and start a new one
            if ($userCount >= $maxUsersPerMessage && $index < count($users) - 1) {
                $text .= "<i>... ادامه در پیام بعدی</i>";
                
                $keyboard = [
                    'inline_keyboard' => array_merge($inlineKeyboard, [[
                        ['text' => '🔙 بازگشت', 'callback_data' => 'admin_menu']
                    ]])
                ];
                $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
                
                // Reset for next batch
                $text = "<b>📋 ادامه لیست کاربران:</b>\n\n";
                $inlineKeyboard = [];
                $userCount = 0;
            }
        }

        // Add search and back buttons
        $keyboard = [
            'inline_keyboard' => array_merge($inlineKeyboard, [
                [['text' => '🔍 جستجو کاربر', 'callback_data' => 'search_user_level']],
                [['text' => '🔙 بازگشت', 'callback_data' => 'admin_menu']]
            ])
        ];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function showUserLevelOptions($telegramId)
    {
        $user = $this->userModel->findByTelegramId($telegramId);
        
        if (!$user) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ کاربر یافت نشد.");
            return;
        }

        $currentLevel = $user['level'] ?? 'general';
        $fullName = trim($user['first_name'] . ' ' . $user['last_name']);
        
        $levelNames = [
            'general' => '👤 عمومی',
            'vip' => '⭐ VIP',
            'level1' => '1️⃣ سطح یک',
            'level2' => '2️⃣ سطح دو',
            'level3' => '3️⃣ سطح سه',
            'level4' => '4️⃣ سطح چهار'
        ];
        
        $currentLevelText = $levelNames[$currentLevel] ?? '👤 عمومی';

        $text = "⭐ <b>تغییر سطح کاربر</b>\n\n";
        $text .= "👤 کاربر: <b>{$fullName}</b>\n";
        $text .= "🆔 کد کاربری: <b>{$user['internal_id']}</b>\n";
        $text .= "⭐ سطح فعلی: {$currentLevelText}\n\n";
        $text .= "لطفاً سطح جدید را انتخاب کنید:";

        $inlineKeyboard = [];
        foreach ($levelNames as $level => $levelText) {
            $isCurrent = ($level === $currentLevel);
            $buttonText = $isCurrent ? "✓ {$levelText}" : $levelText;
            $inlineKeyboard[] = [[
                'text' => $buttonText,
                'callback_data' => "set_user_level:{$telegramId}:{$level}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'admin_menu'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function setUserLevel($telegramId, $level)
    {
        $validLevels = ['general', 'vip', 'level1', 'level2', 'level3', 'level4'];
        
        if (!in_array($level, $validLevels)) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ سطح نامعتبر است.");
            return;
        }

        $user = $this->userModel->findByTelegramId($telegramId);
        
        if (!$user) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ کاربر یافت نشد.");
            return;
        }

        $this->userModel->updateLevel($telegramId, $level);
        
        $levelNames = [
            'general' => '👤 عمومی',
            'vip' => '⭐ VIP',
            'level1' => '1️⃣ سطح یک',
            'level2' => '2️⃣ سطح دو',
            'level3' => '3️⃣ سطح سه',
            'level4' => '4️⃣ سطح چهار'
        ];
        
        $levelText = $levelNames[$level] ?? $level;
        $fullName = trim($user['first_name'] . ' ' . $user['last_name']);

        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "✅ سطح کاربر <b>{$fullName}</b> به <b>{$levelText}</b> تغییر یافت."
        );
        
        $this->showUserLevelManagement();
    }

    private function askForUserSearch($telegramId)
    {
        $this->setAdminState($telegramId, 'search_user_level_input');
        $keyboard = [
            'keyboard' => [
                [['text' => '🔙 بازگشت']]
            ],
            'resize_keyboard' => true,
            'persistent' => true
        ];
        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "🔍 <b>جستجو کاربر</b>\n\n" .
            "لطفاً یکی از موارد زیر را وارد کنید:\n" .
            "• نام یا نام خانوادگی\n" .
            "• کد کاربری (مثال: USER-0001)\n\n" .
            "مثال: علی یا USER-0001",
            $keyboard
        );
    }

    private function handleUserSearch($query, $telegramId)
    {
        if (empty(trim($query))) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ لطفاً عبارت جستجو را وارد کنید.");
            return;
        }

        $users = $this->userModel->search($query);
        $this->clearAdminState($telegramId);

        if (empty($users)) {
            $keyboard = [
                'inline_keyboard' => [[
                    ['text' => '🔍 جستجوی مجدد', 'callback_data' => 'search_user_level'],
                    ['text' => '🔙 بازگشت', 'callback_data' => 'clear_user_search']
                ]]
            ];
            $this->bot->sendMessage(
                $this->bot->getChatId(),
                "⚠️ هیچ کاربری با عبارت <b>{$query}</b> یافت نشد.",
                $keyboard
            );
            return;
        }

        $this->showUserSearchResults($users, $query);
    }

    private function showUserSearchResults($users, $query)
    {
        $levelNames = [
            'general' => '👤 عمومی',
            'vip' => '⭐ VIP',
            'level1' => '1️⃣ سطح یک',
            'level2' => '2️⃣ سطح دو',
            'level3' => '3️⃣ سطح سه',
            'level4' => '4️⃣ سطح چهار'
        ];

        $text = "🔍 <b>نتایج جستجو</b>\n\n";
        $text .= "📝 عبارت جستجو: <b>{$query}</b>\n";
        $text .= "📊 تعداد نتایج: <b>" . count($users) . "</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $inlineKeyboard = [];
        $userCount = 0;
        $maxUsersPerMessage = 8;

        foreach ($users as $index => $user) {
            $userLevel = $user['level'] ?? 'general';
            $levelText = $levelNames[$userLevel] ?? '👤 عمومی';
            $fullName = trim($user['first_name'] . ' ' . $user['last_name']);
            $internalId = $user['internal_id'];
            
            // Format as organized list item
            $text .= "<b>┌─────────────────────────────────────</b>\n";
            $text .= "<b>│</b> 👤 <b>نام:</b> {$fullName}\n";
            $text .= "<b>│</b> 🆔 <b>کد کاربری:</b> <code>{$internalId}</code>\n";
            $text .= "<b>│</b> ⭐ <b>سطح:</b> {$levelText}\n";
            $text .= "<b>└─────────────────────────────────────</b>\n\n";
            
            $inlineKeyboard[] = [[
                'text' => "⭐ تغییر سطح {$fullName}",
                'callback_data' => "manage_user_level:{$user['telegram_id']}"
            ]];
            
            $userCount++;
            
            // If we've reached the limit, send this message and start a new one
            if ($userCount >= $maxUsersPerMessage && $index < count($users) - 1) {
                $text .= "<i>... ادامه در پیام بعدی</i>";
                
                $keyboard = [
                    'inline_keyboard' => array_merge($inlineKeyboard, [[
                        ['text' => '🔙 بازگشت', 'callback_data' => 'clear_user_search']
                    ]])
                ];
                $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
                
                // Reset for next batch
                $text = "<b>📋 ادامه نتایج جستجو:</b>\n\n";
                $inlineKeyboard = [];
                $userCount = 0;
            }
        }

        // Add search and back buttons
        $keyboard = [
            'inline_keyboard' => array_merge($inlineKeyboard, [
                [['text' => '🔍 جستجوی مجدد', 'callback_data' => 'search_user_level']],
                [['text' => '🔙 بازگشت به لیست', 'callback_data' => 'clear_user_search']]
            ])
        ];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function showWeightRangeManagement()
    {
        $weightRanges = $this->weightRangeModel->getAll();
        
        $text = "⚖️ <b>مدیریت بازه‌های وزن</b>\n\n";
        if (empty($weightRanges)) {
            $text .= "هیچ بازه وزنی وجود ندارد.\n\n";
        } else {
            foreach ($weightRanges as $range) {
                $text .= "🔹 {$range['name']}\n";
                $text .= "   بازه: {$range['min_weight']} تا {$range['max_weight']} گرم\n";
                if ($range['category_name']) {
                    $text .= "   دسته‌بندی: {$range['category_name']}\n";
                }
                if ($range['collection_name']) {
                    $text .= "   کالکشن: {$range['collection_name']}\n";
                }
                $text .= "\n";
            }
        }

        $inlineKeyboard = [];
        foreach ($weightRanges as $range) {
            $displayName = "{$range['name']} ({$range['min_weight']}-{$range['max_weight']} گرم)";
            $inlineKeyboard[] = [[
                'text' => "❌ حذف {$displayName}",
                'callback_data' => "delete_weight_range:{$range['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => '➕ افزودن بازه وزن',
            'callback_data' => 'add_weight_range'
        ]];
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'admin_menu'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function showWageRangeManagement()
    {
        $wageRanges = $this->wageRangeModel->getAll();
        
        $text = "💰 <b>مدیریت بازه‌های اجرت</b>\n\n";
        if (empty($wageRanges)) {
            $text .= "هیچ بازه اجرتی وجود ندارد.\n\n";
        } else {
            foreach ($wageRanges as $range) {
                $text .= "🔹 {$range['name']}\n";
                $text .= "   بازه: {$range['min_wage']}% تا {$range['max_wage']}%\n";
                if ($range['category_name']) {
                    $text .= "   دسته‌بندی: {$range['category_name']}\n";
                }
                if ($range['collection_name']) {
                    $text .= "   کالکشن: {$range['collection_name']}\n";
                }
                $text .= "\n";
            }
        }

        $inlineKeyboard = [];
        foreach ($wageRanges as $range) {
            $displayName = "{$range['name']} ({$range['min_wage']}%-{$range['max_wage']}%)";
            $inlineKeyboard[] = [[
                'text' => "❌ حذف {$displayName}",
                'callback_data' => "delete_wage_range:{$range['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => '➕ افزودن بازه اجرت',
            'callback_data' => 'add_wage_range'
        ]];
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'admin_menu'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function showContactManagement()
    {
        $contact = $this->contactModel->get();
        
        $text = "📞 <b>مدیریت تماس با ما</b>\n\n";
        $text .= "📍 <b>آدرس فعلی:</b>\n{$contact['address']}\n\n";
        $text .= "📱 <b>شماره تماس فعلی:</b>\n{$contact['phone']}\n\n";
        $text .= "لطفاً یکی از گزینه‌های زیر را انتخاب کنید:";

        $inlineKeyboard = [
            [
                ['text' => '✏️ ویرایش آدرس', 'callback_data' => 'edit_contact_address']
            ],
            [
                ['text' => '✏️ ویرایش شماره تماس', 'callback_data' => 'edit_contact_phone']
            ],
            [
                ['text' => '🔙 بازگشت', 'callback_data' => 'admin_menu']
            ]
        ];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function askForContactAddress($telegramId)
    {
        $contact = $this->contactModel->get();
        $this->setAdminData($telegramId, 'contact_phone', $contact['phone']);
        $this->setAdminState($telegramId, 'edit_contact_address');
        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "✏️ <b>ویرایش آدرس</b>\n\n" .
            "📍 <b>آدرس فعلی:</b>\n{$contact['address']}\n\n" .
            "لطفاً آدرس جدید را وارد کنید:"
        );
    }

    private function askForContactPhone($telegramId)
    {
        $contact = $this->contactModel->get();
        $this->setAdminData($telegramId, 'contact_address', $contact['address']);
        $this->setAdminState($telegramId, 'edit_contact_phone');
        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "✏️ <b>ویرایش شماره تماس</b>\n\n" .
            "📱 <b>شماره تماس فعلی:</b>\n{$contact['phone']}\n\n" .
            "لطفاً شماره تماس جدید را وارد کنید:"
        );
    }

    private function askForWeightRangeName($telegramId)
    {
        $this->setAdminState($telegramId, 'add_weight_range_name');
        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "➕ لطفاً نام بازه وزن را وارد کنید:\n\n" .
            "مثال: 0 تا 6 گرم"
        );
    }

    private function askForWageRangeName($telegramId)
    {
        $this->setAdminState($telegramId, 'add_wage_range_name');
        $this->bot->sendMessage(
            $this->bot->getChatId(),
            "➕ لطفاً نام بازه اجرت را وارد کنید:\n\n" .
            "مثال: 0 تا 6 درصد"
        );
    }

    private function askForWeightRangeCategory()
    {
        $categories = $this->categoryModel->getAll();
        $text = "📂 لطفاً دسته‌بندی را انتخاب کنید (اختیاری):\n\n";
        
        $inlineKeyboard = [];
        foreach ($categories as $category) {
            $inlineKeyboard[] = [[
                'text' => $category['name'],
                'callback_data' => "weight_range_category_select:{$category['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => 'بدون دسته‌بندی',
            'callback_data' => 'weight_range_category_select:0'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function askForWageRangeCategory()
    {
        $categories = $this->categoryModel->getAll();
        $text = "📂 لطفاً دسته‌بندی را انتخاب کنید (اختیاری):\n\n";
        
        $inlineKeyboard = [];
        foreach ($categories as $category) {
            $inlineKeyboard[] = [[
                'text' => $category['name'],
                'callback_data' => "wage_range_category_select:{$category['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => 'بدون دسته‌بندی',
            'callback_data' => 'wage_range_category_select:0'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function askForWeightRangeCollection($categoryId = null)
    {
        $collections = $this->collectionModel->getAll($categoryId);
        $text = "🧩 لطفاً کالکشن را انتخاب کنید (اختیاری):\n\n";
        
        if (empty($collections)) {
            $text .= "⚠️ کالکشنی برای این دسته‌بندی وجود ندارد.\n\n";
        }

        $inlineKeyboard = [];
        foreach ($collections as $collection) {
            $inlineKeyboard[] = [[
                'text' => $collection['name'],
                'callback_data' => "weight_range_collection_select:{$collection['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => 'بدون کالکشن',
            'callback_data' => 'weight_range_collection_select:0'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function askForWageRangeCollection($categoryId = null)
    {
        $collections = $this->collectionModel->getAll($categoryId);
        $text = "🧩 لطفاً کالکشن را انتخاب کنید (اختیاری):\n\n";
        
        if (empty($collections)) {
            $text .= "⚠️ کالکشنی برای این دسته‌بندی وجود ندارد.\n\n";
        }

        $inlineKeyboard = [];
        foreach ($collections as $collection) {
            $inlineKeyboard[] = [[
                'text' => $collection['name'],
                'callback_data' => "wage_range_collection_select:{$collection['id']}"
            ]];
        }
        $inlineKeyboard[] = [[
            'text' => 'بدون کالکشن',
            'callback_data' => 'wage_range_collection_select:0'
        ]];
        $inlineKeyboard[] = [[
            'text' => '🔙 بازگشت',
            'callback_data' => 'admin_menu'
        ]];

        $keyboard = ['inline_keyboard' => $inlineKeyboard];
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function deleteWeightRange($weightRangeId)
    {
        $this->weightRangeModel->delete($weightRangeId);
        $this->bot->sendMessage($this->bot->getChatId(), "✅ بازه وزن حذف شد.");
        $this->showWeightRangeManagement();
    }

    private function deleteWageRange($wageRangeId)
    {
        $this->wageRangeModel->delete($wageRangeId);
        $this->bot->sendMessage($this->bot->getChatId(), "✅ بازه اجرت حذف شد.");
        $this->showWageRangeManagement();
    }

    private function confirmDeleteUser($telegramId)
    {
        $user = $this->userModel->findByTelegramId($telegramId);
        
        if (!$user) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ کاربر یافت نشد.");
            $this->showUsersList();
            return;
        }

        $userName = trim($user['first_name'] . ' ' . $user['last_name']);
        $internalId = $user['internal_id'];
        
        $text = "⚠️ <b>تایید حذف کاربر</b>\n\n";
        $text .= "👤 نام: <b>{$userName}</b>\n";
        $text .= "🆔 کد کاربری: <code>{$internalId}</code>\n\n";
        $text .= "آیا مطمئن هستید که می‌خواهید این کاربر را حذف کنید؟";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ بله، حذف کن', 'callback_data' => "confirm_delete_user_yes:{$telegramId}"],
                    ['text' => '❌ خیر، انصراف', 'callback_data' => 'confirm_delete_user_no']
                ]
            ]
        ];
        
        $this->bot->sendMessage($this->bot->getChatId(), $text, $keyboard);
    }

    private function deleteUser($telegramId)
    {
        $user = $this->userModel->findByTelegramId($telegramId);
        
        if (!$user) {
            $this->bot->sendMessage($this->bot->getChatId(), "⚠️ کاربر یافت نشد.");
            $this->showUsersList();
            return;
        }

        $userName = trim($user['first_name'] . ' ' . $user['last_name']);
        $internalId = $user['internal_id'];
        
        $this->userModel->delete($telegramId);
        $this->bot->sendMessage($this->bot->getChatId(), "✅ کاربر <b>{$userName}</b> ({$internalId}) با موفقیت حذف شد.");
        $this->showUsersList();
    }

    public function getAdminState($telegramId)
    {
        return $this->adminStates[$telegramId]['state'] ?? null;
    }

    private function setAdminState($telegramId, $state)
    {
        if (!isset($this->adminStates[$telegramId])) {
            $this->adminStates[$telegramId] = [];
        }
        $this->adminStates[$telegramId]['state'] = $state;
    }

    private function clearAdminState($telegramId)
    {
        unset($this->adminStates[$telegramId]);
    }

    private function getAdminData($telegramId, $key)
    {
        return $this->adminStates[$telegramId]['data'][$key] ?? null;
    }

    private function setAdminData($telegramId, $key, $value)
    {
        if (!isset($this->adminStates[$telegramId])) {
            $this->adminStates[$telegramId] = [];
        }
        if (!isset($this->adminStates[$telegramId]['data'])) {
            $this->adminStates[$telegramId]['data'] = [];
        }
        $this->adminStates[$telegramId]['data'][$key] = $value;
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
     * Check if string contains only numbers (Persian or English) and is exactly 4 digits
     * @param string $text
     * @return bool
     */
    private function isValidProductCode($text)
    {
        // Normalize Persian numbers to English first
        $normalized = $this->normalizePersianNumbers($text);
        // Check if it's exactly 4 digits
        return preg_match('/^\d{4}$/', $normalized);
    }
}

