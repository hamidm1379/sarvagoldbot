# Gold Salek Telegram Bot

یک ربات تلگرام کامل برای کاتالوگ طلا و جواهر با پنل مدیریت داخلی.

## ویژگی‌ها

- ✅ ثبت‌نام کاربران با تایید مدیر
- ✅ مرور محصولات بر اساس دسته‌بندی، کالکشن، وزن و اجرت
- ✅ جستجوی محصول با کد
- ✅ پنل مدیریت کامل داخل ربات
- ✅ مدیریت دسته‌بندی‌ها و کالکشن‌ها
- ✅ پشتیبانی کامل از زبان فارسی (RTL)
- ✅ استفاده از PDO و Prepared Statements
- ✅ ساختار MVC-like

## نیازمندی‌ها

- PHP 8.0 یا بالاتر
- MariaDB 10.3 یا بالاتر (یا MySQL 5.7+)
- cURL extension
- PDO extension
- دسترسی به VPS با Linux

## نصب و راه‌اندازی

### 1. کلون کردن پروژه

```bash
cd /var/www/html
git clone <repository-url> goldSalek
cd goldSalek
```

### 2. تنظیمات پایگاه داده

```bash
# وارد MariaDB شوید
mysql -u root -p
# یا اگر از MariaDB استفاده می‌کنید:
# mariadb -u root -p

# پایگاه داده و کاربر ایجاد کنید
CREATE DATABASE gold_salek_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'goldbot_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON gold_salek_bot.* TO 'goldbot_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# وارد کردن اسکیما
mysql -u goldbot_user -p gold_salek_bot < database/schema.sql
# یا
mariadb -u goldbot_user -p gold_salek_bot < database/schema.sql
```

### 3. تنظیم فایل .env

```bash
cp config/.env.example .env
nano .env
```

محتویات `.env` را به این صورت تنظیم کنید:

```env
BOT_TOKEN=8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4
DB_HOST=localhost
DB_NAME=gold_salek_bot
DB_USER=goldbot_user
DB_PASS=your_secure_password
DB_CHARSET=utf8mb4
BOT_WEBHOOK_URL=https://yourdomain.com/index.php
DEBUG_MODE=false
```

### 4. تنظیم دسترسی‌ها

```bash
chmod 755 index.php
chmod 644 .env
chown -R www-data:www-data /var/www/html/goldSalek
```

### 5. تنظیم Webhook

دو روش برای دریافت به‌روزرسانی‌ها وجود دارد:

#### روش 1: Webhook (توصیه می‌شود)

```bash
curl -X POST "https://api.telegram.org/bot8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4/setWebhook" \
  -d "url=https://yourdomain.com/index.php"
```

#### روش 2: Long Polling (برای تست)

یک فایل `poll.php` ایجاد کنید:

```php
<?php
require 'index.php';

$botToken = getenv('BOT_TOKEN');
$bot = new \GoldSalekBot\Bot($botToken);

$offset = 0;
while (true) {
    $url = "https://api.telegram.org/bot{$botToken}/getUpdates?offset={$offset}";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data['ok'] && !empty($data['result'])) {
        foreach ($data['result'] as $update) {
            $bot->handleUpdate(json_encode($update));
            $offset = $update['update_id'] + 1;
        }
    }
    
    sleep(1);
}
```

اجرا با:
```bash
php poll.php
```

### 6. تنظیم Nginx (اختیاری)

اگر از Nginx استفاده می‌کنید، یک فایل تنظیمات ایجاد کنید:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/goldSalek;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 7. اضافه کردن مدیر

برای اضافه کردن خودتان به عنوان مدیر، وارد MariaDB شوید:

```sql
USE gold_salek_bot;
INSERT INTO admins (telegram_id, username) VALUES (YOUR_TELEGRAM_ID, 'admin');
```

برای پیدا کردن Telegram ID خود:
1. به ربات [@userinfobot](https://t.me/userinfobot) پیام دهید
2. ID خود را کپی کنید

## ساختار پروژه

```
goldSalek/
├── config/
│   └── .env.example          # فایل نمونه تنظیمات
├── database/
│   └── schema.sql            # اسکیما پایگاه داده
├── src/
│   ├── Bot.php               # کلاس اصلی ربات
│   ├── Database.php           # اتصال به پایگاه داده
│   ├── handlers/
│   │   ├── UserHandler.php   # مدیریت کاربران
│   │   ├── AdminHandler.php  # پنل مدیریت
│   │   └── ProductHandler.php
│   └── models/
│       ├── User.php          # مدل کاربر
│       ├── Product.php       # مدل محصول
│       ├── Category.php      # مدل دسته‌بندی
│       ├── Collection.php    # مدل کالکشن
│       └── Admin.php         # مدل مدیر
├── index.php                 # نقطه ورود
├── composer.json             # وابستگی‌ها
└── README.md                 # مستندات
```

## استفاده

### برای کاربران:

1. ربات را استارت کنید: `/start`
2. نام و نام خانوادگی را وارد کنید
3. منتظر تایید مدیر بمانید
4. پس از تایید، از منوی اصلی استفاده کنید

### برای مدیران:

1. ربات را استارت کنید: `/start` یا `/admin`
2. از منوی مدیریت استفاده کنید:
   - ➕ افزودن محصول
   - ✏️ ویرایش محصول
   - ❌ حذف / غیرفعال کردن محصول
   - 🗂 مدیریت دسته‌بندی‌ها
   - 🧩 مدیریت کالکشن‌ها
   - 👥 تایید / رد کاربران
   - 📊 مشاهده لیست کاربران

## امنیت

- ✅ استفاده از Prepared Statements برای جلوگیری از SQL Injection
- ✅ اعتبارسنجی ورودی‌ها
- ✅ مدیریت خطاها
- ✅ فایل `.env` در `.gitignore` قرار دارد

## عیب‌یابی

### لاگ خطاها

خطاها در فایل لاگ PHP ثبت می‌شوند. برای مشاهده:

```bash
tail -f /var/log/php/error.log
```

### تست اتصال به پایگاه داده

```php
<?php
require 'src/Database.php';
try {
    $db = \GoldSalekBot\Database::getInstance();
    echo "Database connection successful!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### تست Webhook

```bash
curl -X POST "https://api.telegram.org/bot8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4/getWebhookInfo"
```

## پشتیبانی

برای مشکلات و سوالات، با پشتیبانی تماس بگیرید.

## مجوز

این پروژه برای استفاده شخصی و تجاری آزاد است.

