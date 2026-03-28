#!/usr/bin/env bash
#
# openSIS Classic — Uninstaller
#
# Removes application files, database, Apache config.
# Does NOT remove Apache/PHP/MySQL packages.
#
# Usage:  sudo ./uninstall.sh
#
set -euo pipefail

DB_NAME="${DB_NAME:-opensis}"
DB_USER="${DB_USER:-opensis}"
DB_ROOT_PASS="${DB_ROOT_PASS:-}"
INSTALL_DIR="${INSTALL_DIR:-/var/www/opensis}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

if [ "$(id -u)" -ne 0 ]; then
    echo -e "${RED}[ERROR]${NC} Run as root (sudo)." && exit 1
fi

echo -e "${YELLOW}This will remove:${NC}"
echo "  - Database: ${DB_NAME}"
echo "  - Database user: ${DB_USER}"
echo "  - Application directory: ${INSTALL_DIR}"
echo "  - Apache config: opensis.conf"
echo ""
read -rp "Are you sure? (y/N): " confirm
if [[ "$confirm" != [yY] ]]; then
    echo "Aborted." && exit 0
fi

# Remove database
echo -e "${GREEN}[INFO]${NC} Removing database..."
if [ -n "$DB_ROOT_PASS" ]; then
    MYSQL_CMD="mysql -u root -p${DB_ROOT_PASS}"
else
    MYSQL_CMD="mysql -u root"
fi

$MYSQL_CMD <<EOSQL 2>/dev/null || true
DROP DATABASE IF EXISTS \`${DB_NAME}\`;
DROP USER IF EXISTS '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOSQL

# Remove Apache config
echo -e "${GREEN}[INFO]${NC} Removing Apache config..."
if [ -f /etc/apache2/sites-available/opensis.conf ]; then
    a2dissite opensis > /dev/null 2>&1 || true
    rm -f /etc/apache2/sites-available/opensis.conf
    a2ensite 000-default > /dev/null 2>&1 || true
    systemctl restart apache2 2>/dev/null || true
elif [ -f /etc/httpd/conf.d/opensis.conf ]; then
    rm -f /etc/httpd/conf.d/opensis.conf
    systemctl restart httpd 2>/dev/null || true
fi

# Remove PHP config
rm -f /etc/php/*/conf.d/99-opensis.ini 2>/dev/null || true
rm -f /etc/php.d/99-opensis.ini 2>/dev/null || true

# Remove application files
if [ -d "$INSTALL_DIR" ]; then
    echo -e "${GREEN}[INFO]${NC} Removing ${INSTALL_DIR}..."
    rm -rf "$INSTALL_DIR"
fi

echo ""
echo -e "${GREEN}openSIS Classic has been uninstalled.${NC}"
echo "Apache, PHP, and MySQL packages were NOT removed."
