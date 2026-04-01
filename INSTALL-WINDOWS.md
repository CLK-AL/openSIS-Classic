# Windows Installation Guide

Three options for running openSIS Classic on Windows: XAMPP (recommended), Bitnami, or manual.

---

## Option 1: XAMPP (Apache Friends) — Recommended

XAMPP is the most popular PHP development environment for Windows. It includes Apache, MySQL/MariaDB, PHP, and phpMyAdmin in a single installer.

### Step 1: Download and Install XAMPP

1. Go to [https://www.apachefriends.org/download.html](https://www.apachefriends.org/download.html)
2. Download **XAMPP for Windows** (PHP 8.x version)
3. Run the installer as Administrator
4. Select components: **Apache**, **MySQL**, **PHP**, **phpMyAdmin** (minimum required)
5. Install to default location: `C:\xampp`
6. When prompted by Windows Firewall, click **Allow Access**

### Step 2: Start Services

1. Open **XAMPP Control Panel** (run as Administrator)
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**
4. Both should show green "Running" status
5. Verify by opening [http://localhost](http://localhost) in your browser

### Step 3: Create Database

1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click **Databases** tab
3. Enter database name: `opensis`
4. Select collation: `utf8mb4_unicode_ci`
5. Click **Create**
6. Click **Privileges** tab → **Add user account**
7. Set:
   - Username: `opensis`
   - Host: `localhost`
   - Password: (choose a strong password)
   - Check: **Grant all privileges on database "opensis"**
8. Click **Go**

### Step 4: Install openSIS

1. Download or clone the openSIS repository
2. Copy the entire folder to `C:\xampp\htdocs\opensis`
3. Create `Data.php` in `C:\xampp\htdocs\opensis\`:

```php
<?php
$DatabaseServer   = 'localhost';
$DatabaseUsername  = 'opensis';
$DatabasePassword  = 'your_password_here';
$DatabaseName      = 'opensis';
$DatabasePort      = '3306';
$DatabaseType      = 'mysqli';
```

4. Open [http://localhost/opensis/install/](http://localhost/opensis/install/)
5. Follow the web-based installer

### Step 5: Configure PHP (Optional)

Edit `C:\xampp\php\php.ini`:

```ini
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 300
memory_limit = 256M
```

Restart Apache from XAMPP Control Panel after changes.

### SQLite Mode (No MySQL)

If you prefer not to use MySQL, create `Data.php` with:

```php
<?php
$DatabaseType = 'sqlite';
$DatabaseName = __DIR__ . '/data/opensis.db';
$DatabaseServer = '';
$DatabaseUsername = '';
$DatabasePassword = '';
$DatabasePort = '';
```

Create the `data` folder: `mkdir C:\xampp\htdocs\opensis\data`

---

## Option 2: Bitnami WAMP Stack

Bitnami provides a pre-configured Apache + MySQL + PHP stack for Windows with one-click installation.

### Step 1: Download Bitnami

1. Go to [https://bitnami.com/stack/wamp](https://bitnami.com/stack/wamp)
2. Download **Bitnami WAMP Stack** (latest PHP 8.x)
3. Run the installer
4. During installation, set a **MySQL root password** (remember it)
5. Default install path: `C:\Bitnami\wampstack-8.x`

### Step 2: Start Services

1. Open **Bitnami WAMP Stack Manager** from Start Menu
2. Go to **Manage Servers** tab
3. Ensure **Apache** and **MySQL** are running (click Start if needed)

### Step 3: Create Database

1. Open **Bitnami WAMP Stack** console (from Manager → Open Terminal)
2. Run:

```cmd
mysql -u root -p
```

3. Enter your root password, then run:

```sql
CREATE DATABASE opensis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'opensis'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON opensis.* TO 'opensis'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 4: Install openSIS

1. Copy the openSIS folder to `C:\Bitnami\wampstack-8.x\apache2\htdocs\opensis`
2. Create `Data.php` (same content as XAMPP Step 4 above)
3. Open [http://localhost/opensis/install/](http://localhost/opensis/install/)
4. Follow the web-based installer

### Step 5: Enable Apache Modules

Edit `C:\Bitnami\wampstack-8.x\apache2\conf\httpd.conf`:

Uncomment these lines (remove the `#`):
```
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
```

And change `AllowOverride None` to `AllowOverride All` in the `<Directory>` block for htdocs.

Restart Apache from the Bitnami Manager.

---

## Option 3: Manual Installation

For advanced users who prefer manual setup.

### Prerequisites

Download and install separately:
1. **PHP 8.x**: [https://windows.php.net/download](https://windows.php.net/download) (Thread Safe, x64)
2. **MySQL 8.0**: [https://dev.mysql.com/downloads/mysql/](https://dev.mysql.com/downloads/mysql/)
3. **Apache 2.4**: [https://www.apachelounge.com/download/](https://www.apachelounge.com/download/)

### PHP Configuration

1. Extract PHP to `C:\php`
2. Copy `php.ini-production` to `php.ini`
3. Edit `php.ini`, uncomment these extensions:

```ini
extension=curl
extension=gd
extension=intl
extension=mbstring
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=pdo_sqlite
extension=zip
```

4. Set:
```ini
extension_dir = "C:\php\ext"
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 300
memory_limit = 256M
```

5. Add `C:\php` to your system PATH

### Apache Configuration

1. Extract Apache to `C:\Apache24`
2. Edit `C:\Apache24\conf\httpd.conf`:

```apache
# Load PHP module
LoadModule php_module "C:/php/php8apache2_4.dll"
AddHandler application/x-httpd-php .php
PHPIniDir "C:/php"

# Document root
DocumentRoot "C:/Apache24/htdocs"
<Directory "C:/Apache24/htdocs">
    AllowOverride All
    Require all granted
</Directory>

# Enable modules
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
```

3. Install as Windows service: `C:\Apache24\bin\httpd.exe -k install`
4. Start: `C:\Apache24\bin\httpd.exe -k start`

### Install openSIS

1. Copy openSIS to `C:\Apache24\htdocs\opensis`
2. Create `Data.php` and database as described in XAMPP Steps 3-4
3. Open [http://localhost/opensis/install/](http://localhost/opensis/install/)

---

## Using PHP Built-in Server (Development Only)

For quick testing without Apache:

```cmd
cd C:\path\to\opensis
php -S localhost:8080
```

Open [http://localhost:8080/install/](http://localhost:8080/install/)

---

## Troubleshooting

### Apache won't start
- Check if port 80 is in use: `netstat -ano | findstr :80`
- Skype, IIS, or another web server may be using port 80
- Change Apache port in `httpd.conf`: `Listen 8080`

### PHP extensions not loading
- Ensure `extension_dir` points to the correct path in `php.ini`
- For XAMPP: `extension_dir = "C:\xampp\php\ext"`
- Restart Apache after any php.ini changes

### MySQL connection refused
- Ensure MySQL service is running
- Check credentials in `Data.php` match what you created
- Try `127.0.0.1` instead of `localhost` in `$DatabaseServer`

### File permissions
- Right-click the opensis folder → Properties → Security
- Ensure the user running Apache has Read/Write access
- For XAMPP, the `data` folder (SQLite) needs write permission

### mod_rewrite not working
- Ensure `mod_rewrite` is enabled in Apache config
- Ensure `AllowOverride All` is set for the opensis directory
- Restart Apache after changes

### "Maximum execution time exceeded"
- Increase `max_execution_time` in `php.ini` to `300` or higher

---

## Recommended: Windows + Docker Desktop

For the simplest Windows experience, install [Docker Desktop](https://www.docker.com/products/docker-desktop/) and use the Docker setup:

```powershell
copy .env.example .env
docker compose up -d
# Open http://localhost:8080/install/
```

This avoids all manual Apache/PHP/MySQL configuration.
