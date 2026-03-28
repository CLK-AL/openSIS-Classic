#!/usr/bin/env bash
#
# openSIS Classic — Local Linux Installer
#
# Installs Apache, MySQL/MariaDB, PHP 8.x and configures openSIS
# to run on a standard Linux system without Docker.
#
# Usage:
#   chmod +x install.sh
#   sudo ./install.sh
#
# Tested on: Ubuntu 22.04/24.04, Debian 12
#
set -euo pipefail

# ── Defaults (override via environment) ───────────────────────────────
DB_NAME="${DB_NAME:-opensis}"
DB_USER="${DB_USER:-opensis}"
DB_PASS="${DB_PASS:-opensis_secret}"
DB_ROOT_PASS="${DB_ROOT_PASS:-}"
APP_PORT="${APP_PORT:-80}"
INSTALL_DIR="${INSTALL_DIR:-/var/www/opensis}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info()  { echo -e "${GREEN}[INFO]${NC} $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }

# ── Pre-flight ────────────────────────────────────────────────────────
if [ "$(id -u)" -ne 0 ]; then
    error "This script must be run as root (use sudo)."
fi

# Detect distro
if [ -f /etc/os-release ]; then
    . /etc/os-release
    DISTRO="$ID"
else
    error "Cannot detect Linux distribution."
fi

info "Detected distribution: $DISTRO ($VERSION_ID)"

# ── Install packages ──────────────────────────────────────────────────
install_packages() {
    case "$DISTRO" in
        ubuntu|debian|linuxmint|pop)
            info "Updating package lists..."
            apt-get update -qq

            info "Installing Apache, PHP 8.x, and MySQL..."
            DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
                apache2 \
                mysql-server \
                php php-mysql php-gd php-zip php-intl php-mbstring php-xml php-curl \
                libapache2-mod-php \
                curl \
                unzip \
                > /dev/null
            ;;
        fedora|rhel|centos|rocky|alma)
            info "Installing Apache, PHP 8.x, and MySQL..."
            dnf install -y -q \
                httpd \
                mysql-server \
                php php-mysqlnd php-gd php-zip php-intl php-mbstring php-xml php-curl \
                curl \
                unzip
            ;;
        *)
            error "Unsupported distribution: $DISTRO. Install Apache, PHP 8.x (with mysqli, gd, zip, intl, mbstring), and MySQL 8.0 manually."
            ;;
    esac
}

install_packages

# ── Enable Apache modules ─────────────────────────────────────────────
case "$DISTRO" in
    ubuntu|debian|linuxmint|pop)
        a2enmod rewrite headers > /dev/null 2>&1 || true
        APACHE_SVC="apache2"
        APACHE_CONF_DIR="/etc/apache2/sites-available"
        APACHE_USER="www-data"
        ;;
    fedora|rhel|centos|rocky|alma)
        APACHE_SVC="httpd"
        APACHE_CONF_DIR="/etc/httpd/conf.d"
        APACHE_USER="apache"
        ;;
esac

# ── Start and configure MySQL ─────────────────────────────────────────
info "Starting MySQL..."
systemctl enable mysql > /dev/null 2>&1 || systemctl enable mysqld > /dev/null 2>&1 || true
systemctl start mysql 2>/dev/null || systemctl start mysqld 2>/dev/null || true

info "Creating database and user..."
if [ -n "$DB_ROOT_PASS" ]; then
    MYSQL_CMD="mysql -u root -p${DB_ROOT_PASS}"
else
    MYSQL_CMD="mysql -u root"
fi

$MYSQL_CMD <<EOSQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
SET GLOBAL event_scheduler = ON;
SET GLOBAL sql_mode = 'NO_ENGINE_SUBSTITUTION';
FLUSH PRIVILEGES;
EOSQL

info "Database '${DB_NAME}' and user '${DB_USER}' created."

# ── Copy application files ────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

if [ "$SCRIPT_DIR" != "$INSTALL_DIR" ]; then
    info "Copying application to ${INSTALL_DIR}..."
    mkdir -p "$INSTALL_DIR"
    rsync -a --exclude='.git' --exclude='vendor' --exclude='node_modules' \
          --exclude='test-results' --exclude='playwright-report' \
          "$SCRIPT_DIR/" "$INSTALL_DIR/"
else
    info "Application already in ${INSTALL_DIR}."
fi

# ── Generate Data.php ─────────────────────────────────────────────────
info "Generating Data.php..."
cat > "${INSTALL_DIR}/Data.php" <<EOPHP
<?php
\$DatabaseServer   = 'localhost';
\$DatabaseUsername  = '${DB_USER}';
\$DatabasePassword  = '${DB_PASS}';
\$DatabaseName      = '${DB_NAME}';
\$DatabasePort      = '3306';
\$DatabaseType      = 'mysqli';
EOPHP

# ── Set permissions ───────────────────────────────────────────────────
info "Setting file permissions..."
chown -R "${APACHE_USER}:${APACHE_USER}" "$INSTALL_DIR"
find "$INSTALL_DIR" -type d -exec chmod 755 {} \;
find "$INSTALL_DIR" -type f -exec chmod 644 {} \;
chmod 600 "${INSTALL_DIR}/Data.php"

mkdir -p "${INSTALL_DIR}/tmp"
chown "${APACHE_USER}:${APACHE_USER}" "${INSTALL_DIR}/tmp"
chmod 775 "${INSTALL_DIR}/tmp"

# ── Configure PHP ─────────────────────────────────────────────────────
info "Configuring PHP..."
PHP_INI=$(php -r 'echo php_ini_loaded_file();' 2>/dev/null || echo "")
PHP_CONF_DIR=$(php -r 'echo PHP_CONFIG_FILE_SCAN_DIR;' 2>/dev/null || echo "/etc/php/conf.d")

if [ -d "$PHP_CONF_DIR" ]; then
    cat > "${PHP_CONF_DIR}/99-opensis.ini" <<EOINI
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 300
memory_limit = 256M
session.cookie_httponly = 1
session.cookie_samesite = Strict
date.timezone = UTC
EOINI
    info "PHP config written to ${PHP_CONF_DIR}/99-opensis.ini"
else
    warn "Could not find PHP conf.d directory. Configure PHP manually."
fi

# ── Configure Apache VirtualHost ──────────────────────────────────────
info "Configuring Apache..."

case "$DISTRO" in
    ubuntu|debian|linuxmint|pop)
        cat > "${APACHE_CONF_DIR}/opensis.conf" <<EOVHOST
<VirtualHost *:${APP_PORT}>
    ServerName localhost
    DocumentRoot ${INSTALL_DIR}

    <Directory ${INSTALL_DIR}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/opensis-error.log
    CustomLog \${APACHE_LOG_DIR}/opensis-access.log combined
</VirtualHost>
EOVHOST
        a2dissite 000-default > /dev/null 2>&1 || true
        a2ensite opensis > /dev/null 2>&1
        ;;
    fedora|rhel|centos|rocky|alma)
        cat > "${APACHE_CONF_DIR}/opensis.conf" <<EOVHOST
<VirtualHost *:${APP_PORT}>
    ServerName localhost
    DocumentRoot ${INSTALL_DIR}

    <Directory ${INSTALL_DIR}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOVHOST
        ;;
esac

# ── Restart services ──────────────────────────────────────────────────
info "Restarting Apache..."
systemctl enable "$APACHE_SVC" > /dev/null 2>&1 || true
systemctl restart "$APACHE_SVC"

# ── Done ──────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  openSIS Classic installed successfully!${NC}"
echo -e "${GREEN}══════════════════════════════════════════════════════${NC}"
echo ""
echo "  Application:  ${INSTALL_DIR}"
echo "  Database:     ${DB_NAME} (user: ${DB_USER})"
echo "  URL:          http://localhost:${APP_PORT}/install/"
echo ""
echo "  Next steps:"
echo "  1. Open http://localhost:${APP_PORT}/install/ in your browser"
echo "  2. Complete the web-based installer"
echo "  3. Login with the admin account you create"
echo ""
echo "  To uninstall:  sudo ./uninstall.sh"
echo ""
