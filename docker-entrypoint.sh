#!/bin/bash
set -e

DATA_FILE="/var/www/html/Data.php"

# Generate Data.php from environment variables if it doesn't exist
if [ ! -f "$DATA_FILE" ]; then
    echo "Generating Data.php from environment variables..."
    cat > "$DATA_FILE" <<EOPHP
<?php
\$DatabaseServer   = '${DB_HOST:-db}';
\$DatabaseUsername  = '${DB_USER:-opensis}';
\$DatabasePassword  = '${DB_PASS:-opensis_secret}';
\$DatabaseName      = '${DB_NAME:-opensis}';
\$DatabasePort      = '${DB_PORT:-3306}';
\$DatabaseType      = 'mysqli';
EOPHP
    chown www-data:www-data "$DATA_FILE"
    echo "Data.php created."
fi

exec "$@"
