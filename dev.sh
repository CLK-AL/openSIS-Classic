#!/usr/bin/env bash
#
# openSIS Classic — Development Server
#
# Runs PHP built-in server for local development without Apache.
# Uses the existing Data.php for database connection.
#
# Usage:
#   ./dev.sh                    # http://localhost:8080
#   ./dev.sh 9090               # http://localhost:9090
#   DB_HOST=127.0.0.1 ./dev.sh  # custom DB host
#
set -euo pipefail

PORT="${1:-8080}"
HOST="${DEV_HOST:-localhost}"
DIR="$(cd "$(dirname "$0")" && pwd)"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# ── Check PHP ─────────────────────────────────────────────────────────
if ! command -v php &>/dev/null; then
    echo -e "\033[0;31m[ERROR]\033[0m PHP not found. Install PHP 8.x first."
    exit 1
fi

PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION;')
if [ "$PHP_VER" -lt 8 ]; then
    echo -e "\033[0;31m[ERROR]\033[0m PHP 8.x required (found PHP ${PHP_VER})."
    exit 1
fi

# ── Check required extensions ─────────────────────────────────────────
MISSING=""
for ext in mysqli gd mbstring intl; do
    if ! php -m 2>/dev/null | grep -qi "^${ext}$"; then
        MISSING="${MISSING} ${ext}"
    fi
done

if [ -n "$MISSING" ]; then
    echo -e "${YELLOW}[WARN]${NC} Missing PHP extensions:${MISSING}"
    echo "Install them with: sudo apt install$(echo "$MISSING" | sed 's/ / php-/g')"
fi

# ── Generate Data.php if missing ──────────────────────────────────────
if [ ! -f "${DIR}/Data.php" ]; then
    DB_TYPE="${DB_TYPE:-sqlite}"

    if [ "$DB_TYPE" = "sqlite" ]; then
        mkdir -p "${DIR}/data"
        echo -e "${GREEN}[INFO]${NC} Creating Data.php with SQLite (default, no MySQL needed)..."
        cat > "${DIR}/Data.php" <<EOPHP
<?php
\$DatabaseType      = 'sqlite';
\$DatabaseName      = __DIR__ . '/data/opensis.db';
\$DatabaseServer    = '';
\$DatabaseUsername   = '';
\$DatabasePassword   = '';
\$DatabasePort       = '';
EOPHP
    else
        echo -e "${GREEN}[INFO]${NC} Creating Data.php with MySQL..."
        cat > "${DIR}/Data.php" <<EOPHP
<?php
\$DatabaseServer   = '${DB_HOST:-localhost}';
\$DatabaseUsername  = '${DB_USER:-opensis}';
\$DatabasePassword  = '${DB_PASS:-opensis_secret}';
\$DatabaseName      = '${DB_NAME:-opensis}';
\$DatabasePort      = '${DB_PORT:-3306}';
\$DatabaseType      = 'mysqli';
EOPHP
    fi
    echo -e "${GREEN}[INFO]${NC} Data.php created. Edit it to change database settings."
fi

# ── Check database connection ─────────────────────────────────────────
DB_TYPE_VAL=$(php -r "include '${DIR}/Data.php'; echo \$DatabaseType;" 2>/dev/null || echo "sqlite")

if [ "$DB_TYPE_VAL" = "sqlite" ]; then
    DB_PATH=$(php -r "include '${DIR}/Data.php'; echo \$DatabaseName;" 2>/dev/null || echo "")
    echo -e "${GREEN}[INFO]${NC} Using SQLite database: ${DB_PATH}"
else
    DB_HOST_VAL=$(php -r "include '${DIR}/Data.php'; echo \$DatabaseServer;" 2>/dev/null || echo "localhost")
    DB_USER_VAL=$(php -r "include '${DIR}/Data.php'; echo \$DatabaseUsername;" 2>/dev/null || echo "opensis")
    if command -v mysqladmin &>/dev/null; then
        if mysqladmin ping -h "$DB_HOST_VAL" -u "$DB_USER_VAL" --silent 2>/dev/null; then
            echo -e "${GREEN}[INFO]${NC} MySQL connection OK (${DB_USER_VAL}@${DB_HOST_VAL})"
        else
            echo -e "${YELLOW}[WARN]${NC} Cannot connect to MySQL at ${DB_HOST_VAL}. Make sure MySQL is running."
        fi
    fi
fi

# ── Run tests if requested ────────────────────────────────────────────
if [ "${2:-}" = "--test" ]; then
    echo -e "${GREEN}[INFO]${NC} Running PHPUnit tests..."
    if [ -f "${DIR}/vendor/bin/phpunit" ]; then
        "${DIR}/vendor/bin/phpunit" --testdox
    else
        echo -e "${YELLOW}[WARN]${NC} PHPUnit not installed. Run: composer install"
    fi
    exit 0
fi

# ── Start dev server ──────────────────────────────────────────────────
echo ""
echo -e "${GREEN}══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  openSIS Development Server${NC}"
echo -e "${GREEN}══════════════════════════════════════════════════════${NC}"
echo ""
echo "  URL:       http://${HOST}:${PORT}"
echo "  Install:   http://${HOST}:${PORT}/install/"
echo "  Root:      ${DIR}"
echo "  PHP:       $(php -v | head -1)"
echo ""
echo "  Press Ctrl+C to stop"
echo ""

php -S "${HOST}:${PORT}" -t "$DIR"
