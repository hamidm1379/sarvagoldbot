# راهنمای نصب و راه‌اندازی

## پیش‌نیازها

- سرور Linux (Ubuntu/Debian)
- PHP 8.0 یا بالاتر
- MariaDB 10.3 یا بالاتر (یا MySQL 5.7+)
- Nginx یا Apache
- دسترسی root یا sudo

## مراحل نصب

### 1. نصب PHP و MariaDB

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.0 php8.0-fpm php8.0-mysql php8.0-curl php8.0-mbstring mariadb-server mariadb-client nginx -y

# راه‌اندازی MariaDB
sudo systemctl start mariadb
sudo systemctl enable mariadb
sudo mysql_secure_installation
```

### 2. ایجاد پایگاه داده

```bash
sudo mysql -u root -p
# یا
sudo mariadb -u root -p
```

در MariaDB:

```sql
CREATE DATABASE gold_salek_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'goldbot_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON gold_salek_bot.* TO 'goldbot_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. وارد کردن اسکیما

```bash
cd /var/www/html/goldSalek
mysql -u goldbot_user -p gold_salek_bot < database/schema.sql
```

### 4. تنظیم فایل .env

```bash
nano .env
```

محتویات:

```env
BOT_TOKEN=8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4
DB_HOST=localhost
DB_NAME=gold_salek_bot
DB_USER=goldbot_user
DB_PASS=YOUR_SECURE_PASSWORD
DB_CHARSET=utf8mb4
BOT_WEBHOOK_URL=https://yourdomain.com/index.php
DEBUG_MODE=false
```

### 5. تنظیم دسترسی‌ها

```bash
sudo chown -R www-data:www-data /var/www/html/goldSalek
sudo chmod 755 /var/www/html/goldSalek
sudo chmod 644 /var/www/html/goldSalek/.env
sudo chmod 755 /var/www/html/goldSalek/index.php
```

### 6. تنظیم Nginx

فایل تنظیمات:

```bash
sudo nano /etc/nginx/sites-available/goldSalek
```

محتویات:

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

    location ~ /\.ht {
        deny all;
    }
}
```

فعال‌سازی:

```bash
sudo ln -s /etc/nginx/sites-available/goldSalek /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 7. تنظیم SSL (اختیاری اما توصیه می‌شود)

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d yourdomain.com
```

### 8. تنظیم Webhook

```bash
curl -X POST "https://api.telegram.org/bot8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4/setWebhook" \
  -d "url=https://yourdomain.com/index.php"
```

بررسی Webhook:

```bash
curl "https://api.telegram.org/bot8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4/getWebhookInfo"
```

### 9. اضافه کردن مدیر

```bash
sudo mysql -u root -p gold_salek_bot
# یا
sudo mariadb -u root -p gold_salek_bot
```

```sql
INSERT INTO admins (telegram_id, username) VALUES (YOUR_TELEGRAM_ID, 'admin');
```

برای پیدا کردن Telegram ID:
- به [@userinfobot](https://t.me/userinfobot) پیام دهید

### 10. تست

1. ربات را در تلگرام استارت کنید
2. اگر مدیر هستید، باید منوی مدیریت را ببینید
3. اگر کاربر عادی هستید، باید فرم ثبت‌نام را ببینید

## عیب‌یابی

### بررسی لاگ‌ها

```bash
# لاگ PHP
sudo tail -f /var/log/php8.0-fpm.log

# لاگ Nginx
sudo tail -f /var/log/nginx/error.log

# لاگ MariaDB
sudo tail -f /var/log/mysql/error.log
# یا
sudo journalctl -u mariadb -f
```

### تست اتصال به پایگاه داده

```bash
mysql -u goldbot_user -p gold_salek_bot -e "SELECT COUNT(*) FROM users;"
```

### تست PHP

```bash
php -r "echo 'PHP Version: ' . phpversion() . PHP_EOL;"
```

### بررسی Webhook

```bash
curl "https://api.telegram.org/bot8568469873:AAHlLjYzI4FJVLK1NX_dbBHtf_bTI2kSjc4/getWebhookInfo"
```

## به‌روزرسانی

```bash
cd /var/www/html/goldSalek
git pull
# یا
# کپی فایل‌های جدید
sudo systemctl reload php8.0-fpm
sudo systemctl reload nginx
```

## پشتیبان‌گیری

```bash
# پشتیبان‌گیری از پایگاه داده
mysqldump -u goldbot_user -p gold_salek_bot > backup_$(date +%Y%m%d).sql

# پشتیبان‌گیری از فایل‌ها
tar -czf goldSalek_backup_$(date +%Y%m%d).tar.gz /var/www/html/goldSalek
```

## امنیت

1. فایل `.env` را در `.gitignore` قرار دهید
2. از رمز عبور قوی برای MariaDB استفاده کنید
3. SSL را فعال کنید
4. فایروال را تنظیم کنید:

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

5. دسترسی SSH را محدود کنید
6. به‌روزرسانی‌های امنیتی را نصب کنید:

```bash
sudo apt update && sudo apt upgrade -y
```

