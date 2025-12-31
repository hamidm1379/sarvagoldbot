# افزودن ادمین به ربات

## ادمین اضافه شده

**Telegram ID:** `8504577397`

## روش‌های افزودن ادمین

### روش 1: استفاده از اسکریپت PHP

```bash
php scripts/add_admin.php 8504577397 admin
```

### روش 2: استفاده از SQL

```bash
mysql -u root -p gold_salek_bot < database/add_admin.sql
```

یا مستقیماً در MySQL:

```sql
USE gold_salek_bot;
INSERT INTO `admins` (`telegram_id`, `username`) 
VALUES (8504577397, 'admin')
ON DUPLICATE KEY UPDATE `username`='admin';
```

### روش 3: اگر پایگاه داده هنوز ایجاد نشده

فایل `database/schema.sql` به‌روزرسانی شده و ادمین به صورت خودکار اضافه می‌شود.

## بررسی

برای بررسی اینکه ادمین اضافه شده است:

```sql
SELECT * FROM admins WHERE telegram_id = 8504577397;
```

## استفاده

پس از اضافه شدن ادمین:
1. ربات را در تلگرام باز کنید
2. `/start` یا `/admin` را ارسال کنید
3. منوی مدیریت را خواهید دید

