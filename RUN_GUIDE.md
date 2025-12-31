# راهنمای اجرای ربات

## 🪟 اجرا در Windows (تست محلی)

### روش 1: استفاده از Webhook (توصیه می‌شود)

#### مرحله 1: نصب XAMPP یا WAMP

1. دانلود و نصب [XAMPP](https://www.apachefriends.org/) یا [WAMP](https://www.wampserver.com/)
2. فعال کردن Apache و MySQL در کنترل پنل

#### مرحله 2: کپی کردن فایل‌ها

```bash
# کپی پروژه به پوشه htdocs
xcopy /E /I d:\BOTS\goldSalek C:\xampp\htdocs\goldSalek
```

#### مرحله 3: ایجاد پایگاه داده

1. باز کردن `http://localhost/phpmyadmin`
2. ایجاد پایگاه داده جدید:
   - نام: `gold_salek_bot`
   - Collation: `utf8mb4_unicode_ci`
3. وارد کردن اسکیما:
   - انتخاب پایگاه داده
   - تب Import
   - انتخاب فایل `database/schema.sql`
   - کلیک Go

#### مرحله 4: تنظیم .env

در پوشه `C:\xampp\htdocs\goldSalek` فایل `.env` ایجاد کنید:

```env
BOT_TOKEN=8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4
DB_HOST=localhost
DB_NAME=gold_salek_bot
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
BOT_WEBHOOK_URL=https://yourdomain.com/index.php
DEBUG_MODE=true
```

#### مرحله 5: استفاده از ngrok (برای تست Webhook)

1. دانلود [ngrok](https://ngrok.com/download)
2. اجرای ngrok:

```bash
ngrok http 80
```

3. کپی کردن URL (مثلاً: `https://abc123.ngrok.io`)
4. تنظیم Webhook:

```bash
curl -X POST "https://api.telegram.org/bot8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4/setWebhook" -d "url=https://abc123.ngrok.io/goldSalek/index.php"
```

#### مرحله 6: تست

1. ربات را در تلگرام باز کنید
2. `/start` را ارسال کنید
3. باید پاسخ بگیرید!

---

### روش 2: استفاده از Long Polling (بدون نیاز به Webhook)

#### ایجاد فایل `poll.php`:

```php
<?php
require 'index.php';

$botToken = getenv('BOT_TOKEN');
if (!$botToken) {
    die("Bot token not found!\n");
}

$bot = new \GoldSalekBot\Bot($botToken);

echo "Bot is running... Press Ctrl+C to stop.\n";

$offset = 0;
while (true) {
    $url = "https://api.telegram.org/bot{$botToken}/getUpdates?offset={$offset}&timeout=10";
    $response = @file_get_contents($url);
    
    if ($response === false) {
        sleep(1);
        continue;
    }
    
    $data = json_decode($response, true);
    
    if ($data && $data['ok'] && !empty($data['result'])) {
        foreach ($data['result'] as $update) {
            $bot->handleUpdate(json_encode($update));
            $offset = $update['update_id'] + 1;
        }
    }
    
    usleep(500000); // 0.5 second
}
```

#### اجرا:

```bash
cd d:\BOTS\goldSalek
php poll.php
```

**نکته:** این روش برای تست است. برای production از Webhook استفاده کنید.

---

## 🐧 اجرا در VPS Linux (Production)

### مرحله 1: آپلود فایل‌ها

```bash
# استفاده از SCP
scp -r d:\BOTS\goldSalek user@your-server:/var/www/html/

# یا استفاده از FTP/SFTP
```

### مرحله 2: نصب وابستگی‌ها

```bash
ssh user@your-server
cd /var/www/html/goldSalek

# نصب PHP و MySQL (اگر نصب نشده)
sudo apt update
sudo apt install php8.0 php8.0-fpm php8.0-mysql php8.0-curl mysql-server -y
```

### مرحله 3: تنظیم پایگاه داده

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE gold_salek_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'goldbot'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON gold_salek_bot.* TO 'goldbot'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
mysql -u goldbot -p gold_salek_bot < database/schema.sql
```

### مرحله 4: تنظیم .env

```bash
nano .env
```

```env
BOT_TOKEN=8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4
DB_HOST=localhost
DB_NAME=gold_salek_bot
DB_USER=goldbot
DB_PASS=YOUR_SECURE_PASSWORD
DB_CHARSET=utf8mb4
BOT_WEBHOOK_URL=https://yourdomain.com/index.php
DEBUG_MODE=false
```

### مرحله 5: تنظیم دسترسی‌ها

```bash
sudo chown -R www-data:www-data /var/www/html/goldSalek
sudo chmod 755 /var/www/html/goldSalek
sudo chmod 644 /var/www/html/goldSalek/.env
sudo chmod 755 /var/www/html/goldSalek/index.php
```

### مرحله 6: تنظیم Nginx

```bash
sudo nano /etc/nginx/sites-available/goldSalek
```

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
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/goldSalek /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### مرحله 7: تنظیم SSL (اختیاری اما توصیه می‌شود)

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d yourdomain.com
```

### مرحله 8: تنظیم Webhook

```bash
curl -X POST "https://api.telegram.org/bot8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4/setWebhook" \
  -d "url=https://yourdomain.com/index.php"
```

### مرحله 9: بررسی Webhook

```bash
curl "https://api.telegram.org/bot8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4/getWebhookInfo"
```

---

## ✅ تست ربات

### 1. تست اتصال به پایگاه داده

```bash
php scripts/test_db.php
```

### 2. تست افزودن ادمین

```bash
php scripts/add_admin.php 8504577397 admin
```

### 3. تست در تلگرام

1. ربات را باز کنید
2. `/start` را ارسال کنید
3. اگر ادمین هستید، منوی مدیریت را می‌بینید
4. اگر کاربر عادی هستید، فرم ثبت‌نام را می‌بینید

---

## 🔧 عیب‌یابی

### ربات پاسخ نمی‌دهد

1. **بررسی Webhook:**
   ```bash
   curl "https://api.telegram.org/bot8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4/getWebhookInfo"
   ```

2. **بررسی لاگ‌ها:**
   ```bash
   # Linux
   tail -f /var/log/php8.0-fpm.log
   tail -f /var/log/nginx/error.log
   
   # Windows (XAMPP)
   C:\xampp\apache\logs\error.log
   ```

3. **تست مستقیم:**
   ```bash
   php -r "require 'index.php';"
   ```

### خطای اتصال به پایگاه داده

1. بررسی فایل `.env`
2. تست اتصال:
   ```bash
   mysql -u goldbot -p gold_salek_bot -e "SELECT 1;"
   ```

### خطای 404 در Webhook

1. بررسی URL در Webhook
2. بررسی تنظیمات Nginx/Apache
3. بررسی دسترسی فایل `index.php`

---

## 📝 نکات مهم

1. **برای تست محلی:** از ngrok استفاده کنید
2. **برای production:** حتماً SSL فعال کنید
3. **امنیت:** فایل `.env` را در `.gitignore` قرار دهید
4. **پشتیبان‌گیری:** به صورت منظم از پایگاه داده بکاپ بگیرید

---

## 🚀 دستورات سریع

```bash
# تست اتصال
php scripts/test_db.php

# افزودن ادمین
php scripts/add_admin.php 8504577397 admin

# بررسی Webhook
curl "https://api.telegram.org/bot8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4/getWebhookInfo"

# تنظیم Webhook
curl -X POST "https://api.telegram.org/bot8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4/setWebhook" \
  -d "url=https://yourdomain.com/index.php"

# حذف Webhook (برای استفاده از Long Polling)
curl -X POST "https://api.telegram.org/bot8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4/deleteWebhook"
```

