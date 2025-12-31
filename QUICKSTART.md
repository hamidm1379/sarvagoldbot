# راهنمای سریع شروع

## نصب سریع (5 دقیقه)

### 1. آماده‌سازی پایگاه داده

```bash
mysql -u root -p
# یا
mariadb -u root -p
```

```sql
CREATE DATABASE gold_salek_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'goldbot'@'localhost' IDENTIFIED BY 'password123';
GRANT ALL PRIVILEGES ON gold_salek_bot.* TO 'goldbot'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
mysql -u goldbot -p gold_salek_bot < database/schema.sql
# یا
mariadb -u goldbot -p gold_salek_bot < database/schema.sql
```

### 2. تنظیم .env

فایل `.env` را در ریشه پروژه ایجاد کنید:

```env
BOT_TOKEN=8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4
DB_HOST=localhost
DB_NAME=gold_salek_bot
DB_USER=goldbot
DB_PASS=password123
DB_CHARSET=utf8mb4
DEBUG_MODE=true
```

### 3. اضافه کردن مدیر

```bash
php scripts/add_admin.php YOUR_TELEGRAM_ID
```

برای پیدا کردن Telegram ID خود:
- به [@userinfobot](https://t.me/userinfobot) پیام دهید

### 4. تست اتصال

```bash
php scripts/test_db.php
```

### 5. تنظیم Webhook

```bash
curl -X POST "https://api.telegram.org/bot8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4/setWebhook" \
  -d "url=https://yourdomain.com/index.php"
```

### 6. تست ربات

1. ربات را در تلگرام باز کنید
2. `/start` را ارسال کنید
3. اگر مدیر هستید، منوی مدیریت را می‌بینید

## استفاده

### کاربران

1. `/start` → ثبت نام
2. منتظر تایید مدیر
3. استفاده از منوی اصلی

### مدیران

1. `/start` یا `/admin` → منوی مدیریت
2. ➕ افزودن محصول
3. 👥 تایید کاربران
4. 🗂 مدیریت دسته‌بندی‌ها

## دستورات مفید

```bash
# مشاهده لاگ‌ها
tail -f /var/log/php8.0-fpm.log

# تست Webhook
curl "https://api.telegram.org/bot8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4/getWebhookInfo"

# پشتیبان‌گیری
mysqldump -u goldbot -p gold_salek_bot > backup.sql
# یا
mariadb-dump -u goldbot -p gold_salek_bot > backup.sql
```

## مشکلات رایج

### ربات پاسخ نمی‌دهد
- Webhook را بررسی کنید
- لاگ‌ها را چک کنید
- اتصال به پایگاه داده را تست کنید

### خطای اتصال به پایگاه داده
- اطلاعات .env را بررسی کنید
- دسترسی کاربر MariaDB را چک کنید

### مدیر نمی‌تواند وارد شود
- Telegram ID را بررسی کنید
- از اسکریپت `add_admin.php` استفاده کنید

