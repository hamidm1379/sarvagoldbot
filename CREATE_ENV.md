# ایجاد فایل .env

فایل `.env` ایجاد شد! ✅

## محتویات فایل:

```env
BOT_TOKEN=8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4
DB_HOST=localhost
DB_NAME=gold_salek_bot
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
DEBUG_MODE=true
```

## تنظیمات پایگاه داده

**مهم:** قبل از اجرای ربات، باید:

1. **پایگاه داده را ایجاد کنید:**
   ```sql
   CREATE DATABASE gold_salek_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **اسکیما را وارد کنید:**
   ```bash
   mysql -u root -p gold_salek_bot < database/schema.sql
   # یا
   mariadb -u root -p gold_salek_bot < database/schema.sql
   ```

3. **یا از phpMyAdmin:**
   - ایجاد پایگاه داده `gold_salek_bot`
   - Import فایل `database/schema.sql`

## اگر رمز عبور MariaDB دارید

فایل `.env` را ویرایش کنید و `DB_PASS` را تنظیم کنید:

```env
DB_PASS=your_password
```

## تست

```bash
# تست اتصال به پایگاه داده
php scripts/test_db.php

# تست افزودن ادمین
php scripts/add_admin.php 8504577397 admin

# اجرای ربات (Long Polling)
php poll.php
```

