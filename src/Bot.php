<?php

namespace GoldSalekBot;

use GoldSalekBot\Handlers\UserHandler;
use GoldSalekBot\Handlers\AdminHandler;
use GoldSalekBot\Handlers\ProductHandler;

class Bot
{
    private $token;
    private $apiUrl;
    private $update;
    private $chatId;
    private $message;
    private $callbackQuery;
    private $userHandler;
    private $adminHandler;
    private $productHandler;

    public function __construct($token)
    {
        $this->token = $token;
        $this->apiUrl = "https://api.telegram.org/bot{$token}/";
        $this->userHandler = new UserHandler($this);
        $this->adminHandler = new AdminHandler($this);
        $this->productHandler = new ProductHandler($this);
    }

    public function handleUpdate($update)
    {
        $this->update = json_decode($update, true);
        
        if (!$this->update) {
            return;
        }

        $this->message = $this->update['message'] ?? null;
        $this->callbackQuery = $this->update['callback_query'] ?? null;
        
        if ($this->callbackQuery) {
            $this->chatId = $this->callbackQuery['message']['chat']['id'] ?? null;
            $this->handleCallbackQuery();
        } elseif ($this->message) {
            $this->chatId = $this->message['chat']['id'] ?? null;
            $this->handleMessage();
        }
    }

    private function handleMessage()
    {
        $text = $this->message['text'] ?? '';
        $from = $this->message['from'] ?? [];
        $telegramId = $from['id'] ?? null;
        $photo = $this->message['photo'] ?? null;
        $video = $this->message['video'] ?? null;
        $animation = $this->message['animation'] ?? null;

        // Check if user is admin
        $adminModel = new \GoldSalekBot\Models\Admin();
        $isAdmin = $adminModel->isAdmin($telegramId);

        // Admin button routes to admin handler
        if ($text === '🔐 ادمین' && $isAdmin) {
            $this->adminHandler->handle('/admin', $telegramId);
            return;
        }

        // If admin has an active state, route to admin handler
        if ($isAdmin) {
            // Check if it's an admin menu button
            $adminButtons = [
                '➕ افزودن محصول',
                '➕ Add Product',
                '✏️ ویرایش محصول',
                '✏️ Edit Product',
                '❌ حذف / غیرفعال کردن محصول',
                '❌ Delete / Disable Product',
                '🗂 مدیریت دسته‌بندی‌ها',
                '🗂 Manage Categories',
                '🧩 مدیریت کالکشن‌ها',
                '🧩 Manage Collections',
                '⚖️ مدیریت بازه‌های وزن',
                '⚖️ Manage Weight Ranges',
                '💰 مدیریت بازه‌های اجرت',
                '💰 Manage Wage Ranges',
                '👥 تایید / رد کاربران',
                '👥 Approve / Reject Users',
                '📊 مشاهده لیست کاربران',
                '📊 View Users List',
                '⭐ مدیریت سطح کاربران',
                '⭐ Manage User Levels',
                '📞 مدیریت تماس با ما',
                '🔙 بازگشت به منوی اصلی'
            ];

            // Check if admin has state or clicked admin button or sent media (photo/video/animation when in state)
            $adminState = $this->adminHandler->getAdminState($telegramId);
            if ($adminState || in_array($text, $adminButtons) || (($photo || $video || $animation) && $adminState)) {
                $this->adminHandler->handle($text, $telegramId);
                return;
            }
        }

        // Handle user messages (all users see main menu)
        $this->userHandler->handle($text, $telegramId);
    }

    private function handleCallbackQuery()
    {
        $data = $this->callbackQuery['data'] ?? '';
        $from = $this->callbackQuery['from'] ?? [];
        $telegramId = $from['id'] ?? null;
        $messageId = $this->callbackQuery['message']['message_id'] ?? null;

        // Answer callback query
        $this->answerCallbackQuery($this->callbackQuery['id']);

        // Route user-specific callbacks (weight search etc.) always to UserHandler
        $parts = explode(':', $data);
        $action = $parts[0] ?? '';
        $userActions = [
            'weight_search_category',
            'weight_search_collection',
            'weight_range',
            'wage_search_category',
            'wage_search_collection',
            'wage_range',
            'weight',
            'wage',
            'filter',
            'category',
            'category_collection',
            'category_weight',
            'category_wage',
            'category_all',
            'collection',
            'product'
        ];
        if (in_array($action, $userActions, true)) {
            $this->userHandler->handleCallback($data, $telegramId, $messageId);
            return;
        }

        // Check if user is admin
        $adminModel = new \GoldSalekBot\Models\Admin();
        if ($adminModel->isAdmin($telegramId)) {
            $this->adminHandler->handleCallback($data, $telegramId, $messageId);
        } else {
            $this->userHandler->handleCallback($data, $telegramId, $messageId);
        }
    }

    public function sendMessage($chatId, $text, $keyboard = null, $parseMode = 'HTML')
    {
        // Telegram doesn't allow empty messages, use a default message if empty
        if (empty(trim($text))) {
            $text = '📦';
        }
        
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->apiRequest('sendMessage', $data);
    }

    public function sendPhoto($chatId, $photo, $caption = '', $keyboard = null, $parseMode = 'HTML')
    {
        $data = [
            'chat_id' => $chatId,
            'photo' => $photo
        ];

        // Only add caption if it's not empty
        if (!empty(trim($caption))) {
            $data['caption'] = $caption;
            $data['parse_mode'] = $parseMode;
        }

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->apiRequest('sendPhoto', $data);
    }

    public function sendVideo($chatId, $video, $caption = '', $keyboard = null, $parseMode = 'HTML')
    {
        $data = [
            'chat_id' => $chatId,
            'video' => $video
        ];

        // Only add caption if it's not empty
        if (!empty(trim($caption))) {
            $data['caption'] = $caption;
            $data['parse_mode'] = $parseMode;
        }

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->apiRequest('sendVideo', $data);
    }

    public function sendAnimation($chatId, $animation, $caption = '', $keyboard = null, $parseMode = 'HTML')
    {
        $data = [
            'chat_id' => $chatId,
            'animation' => $animation
        ];

        // Only add caption if it's not empty
        if (!empty(trim($caption))) {
            $data['caption'] = $caption;
            $data['parse_mode'] = $parseMode;
        }

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->apiRequest('sendAnimation', $data);
    }

    public function editMessageText($chatId, $messageId, $text, $keyboard = null, $parseMode = 'HTML')
    {
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $parseMode
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->apiRequest('editMessageText', $data);
    }

    public function editMessageCaption($chatId, $messageId, $caption, $keyboard = null, $parseMode = 'HTML')
    {
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'caption' => $caption,
            'parse_mode' => $parseMode
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        return $this->apiRequest('editMessageCaption', $data);
    }

    public function deleteMessage($chatId, $messageId)
    {
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId
        ];

        return $this->apiRequest('deleteMessage', $data);
    }

    public function answerCallbackQuery($callbackQueryId, $text = '', $showAlert = false)
    {
        $data = [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert
        ];

        return $this->apiRequest('answerCallbackQuery', $data);
    }

    public function getFile($fileId)
    {
        $data = ['file_id' => $fileId];
        $response = $this->apiRequest('getFile', $data);
        
        if ($response && isset($response['result']['file_path'])) {
            return "https://api.telegram.org/file/bot{$this->token}/" . $response['result']['file_path'];
        }
        
        return null;
    }

    private function apiRequest($method, $data)
    {
        $url = $this->apiUrl . $method;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            // Check if it's a file identifier error (common and expected, don't log)
            $responseLower = strtolower($response);
            if (strpos($responseLower, 'wrong file identifier') === false && 
                strpos($responseLower, 'file identifier') === false) {
                error_log("Telegram API error: HTTP {$httpCode} - {$response}");
            }
            return null;
        }

        $result = json_decode($response, true);
        
        if (!$result || !$result['ok']) {
            $errorDescription = $result['description'] ?? 'Unknown error';
            // Only log non-critical errors (file identifier errors are common and expected)
            $errorLower = strtolower($errorDescription);
            if (strpos($errorLower, 'wrong file identifier') === false && 
                strpos($errorLower, 'file identifier') === false &&
                strpos($errorLower, 'message text is empty') === false) {
                error_log("Telegram API error: " . $errorDescription);
            }
            return null;
        }

        return $result;
    }

    public function getChatId()
    {
        return $this->chatId;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getCallbackQuery()
    {
        return $this->callbackQuery;
    }

    /**
     * Check if user is a member of the channel
     * @param int $userId Telegram user ID
     * @param string $channelUsername Channel username (e.g., '@sarvagold')
     * @return bool
     */
    public function isChannelMember($userId, $channelUsername = '@sarvagold')
    {
        $data = [
            'chat_id' => $channelUsername,
            'user_id' => $userId
        ];

        $response = $this->apiRequest('getChatMember', $data);
        
        if (!$response || !isset($response['result'])) {
            return false;
        }

        $status = $response['result']['status'] ?? '';
        
        // User is a member if status is 'member', 'administrator', 'creator', or 'restricted'
        // User is NOT a member if status is 'left' or 'kicked'
        return in_array($status, ['member', 'administrator', 'creator', 'restricted']);
    }
}

