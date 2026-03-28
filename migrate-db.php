#!/usr/bin/env php
<?php
/**
 * openSIS Classic — Database Migration Tool
 *
 * Migrates data between SQLite, MySQL, and PostgreSQL.
 * Reads from the source database, creates schema + copies all data to the target.
 *
 * Usage:
 *   php migrate-db.php --from sqlite --to mysql [options]
 *   php migrate-db.php --from mysql --to pgsql [options]
 *   php migrate-db.php --from sqlite --to pgsql [options]
 *   php migrate-db.php --from mysql --to sqlite [options]
 *   php migrate-db.php --from pgsql --to mysql [options]
 *   php migrate-db.php --from pgsql --to sqlite [options]
 *
 * Options:
 *   --from          Source type: sqlite, mysql, pgsql
 *   --to            Target type: sqlite, mysql, pgsql
 *   --source-dsn    Source DSN (auto-detected from Data.php if omitted)
 *   --target-dsn    Target DSN (required)
 *   --target-user   Target DB username (mysql/pgsql)
 *   --target-pass   Target DB password (mysql/pgsql)
 *   --dry-run       Show what would be done without writing
 *   --drop-tables   Drop existing tables in target before creating
 *   --batch-size    INSERT batch size (default: 500)
 *
 * Examples:
 *   # SQLite → MySQL
 *   php migrate-db.php --from sqlite --to mysql \
 *       --target-dsn "mysql:host=localhost;dbname=opensis" \
 *       --target-user opensis --target-pass secret
 *
 *   # MySQL → PostgreSQL
 *   php migrate-db.php --from mysql --to pgsql \
 *       --source-dsn "mysql:host=localhost;dbname=opensis" \
 *       --target-dsn "pgsql:host=localhost;dbname=opensis" \
 *       --target-user opensis --target-pass secret
 *
 *   # MySQL → SQLite
 *   php migrate-db.php --from mysql --to sqlite \
 *       --target-dsn "sqlite:/var/www/opensis/data/opensis.db"
 */

set_time_limit(0);
ini_set('memory_limit', '512M');

// ── Parse CLI arguments ──────────────────────────────────────────────
$opts = getopt('', [
    'from:', 'to:',
    'source-dsn:', 'source-user:', 'source-pass:',
    'target-dsn:', 'target-user:', 'target-pass:',
    'dry-run', 'drop-tables', 'batch-size:',
]);

$fromType   = $opts['from'] ?? '';
$toType     = $opts['to'] ?? '';
$srcDsn     = $opts['source-dsn'] ?? '';
$srcUser    = $opts['source-user'] ?? '';
$srcPass    = $opts['source-pass'] ?? '';
$tgtDsn     = $opts['target-dsn'] ?? '';
$tgtUser    = $opts['target-user'] ?? '';
$tgtPass    = $opts['target-pass'] ?? '';
$dryRun     = isset($opts['dry-run']);
$dropTables = isset($opts['drop-tables']);
$batchSize  = (int)($opts['batch-size'] ?? 500);

if (!$fromType || !$toType) {
    fwrite(STDERR, "Usage: php migrate-db.php --from <sqlite|mysql|pgsql> --to <sqlite|mysql|pgsql> [options]\n");
    fwrite(STDERR, "Run with --help for full options.\n");
    exit(1);
}

if ($fromType === $toType) {
    fwrite(STDERR, "Error: --from and --to must be different.\n");
    exit(1);
}

// ── Auto-detect source from Data.php ─────────────────────────────────
if (!$srcDsn && file_exists(__DIR__ . '/Data.php')) {
    include __DIR__ . '/Data.php';
    if (isset($DatabaseType)) {
        if ($DatabaseType === 'sqlite' && $fromType === 'sqlite') {
            $srcDsn = "sqlite:$DatabaseName";
        } elseif (($DatabaseType === 'mysqli' || $DatabaseType === 'mysql') && $fromType === 'mysql') {
            $port = $DatabasePort ?? 3306;
            $srcDsn = "mysql:host=$DatabaseServer;port=$port;dbname=$DatabaseName;charset=utf8mb4";
            $srcUser = $srcUser ?: $DatabaseUsername;
            $srcPass = $srcPass ?: $DatabasePassword;
        }
    }
}

if (!$srcDsn) {
    fwrite(STDERR, "Error: Cannot determine source DSN. Use --source-dsn or ensure Data.php exists.\n");
    exit(1);
}
if (!$tgtDsn) {
    fwrite(STDERR, "Error: --target-dsn is required.\n");
    exit(1);
}

info("Migration: $fromType → $toType");
info("Source: $srcDsn");
info("Target: $tgtDsn");
if ($dryRun) info("DRY RUN — no changes will be written");

// ── Connect ──────────────────────────────────────────────────────────
try {
    $src = new PDO($srcDsn, $srcUser ?: null, $srcPass ?: null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    if ($fromType === 'sqlite') $src->exec('PRAGMA journal_mode=WAL');
} catch (PDOException $e) {
    fwrite(STDERR, "Error connecting to source: " . $e->getMessage() . "\n");
    exit(1);
}

try {
    // Create target directory for SQLite
    if ($toType === 'sqlite' && preg_match('/sqlite:(.+)/', $tgtDsn, $m)) {
        $dir = dirname($m[1]);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
    }

    $tgt = new PDO($tgtDsn, $tgtUser ?: null, $tgtPass ?: null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    if ($toType === 'sqlite') {
        $tgt->exec('PRAGMA journal_mode=WAL');
        $tgt->exec('PRAGMA foreign_keys=OFF');
    }
    if ($toType === 'mysql') {
        $tgt->exec("SET FOREIGN_KEY_CHECKS=0");
        $tgt->exec("SET sql_mode='NO_ENGINE_SUBSTITUTION'");
    }
    if ($toType === 'pgsql') {
        $tgt->exec("SET session_replication_role = 'replica'"); // disable FK checks
    }
} catch (PDOException $e) {
    fwrite(STDERR, "Error connecting to target: " . $e->getMessage() . "\n");
    exit(1);
}

// ── Get source tables ────────────────────────────────────────────────
$tables = getSourceTables($src, $fromType);
info("Found " . count($tables) . " tables");

// ── Migrate each table ───────────────────────────────────────────────
$totalRows = 0;
$tgt->beginTransaction();

foreach ($tables as $table) {
    // Get columns from source
    $columns = getColumns($src, $fromType, $table);
    if (empty($columns)) {
        warn("Skipping $table — no columns found");
        continue;
    }

    // Generate CREATE TABLE for target
    $createSql = generateCreateTable($table, $columns, $toType);

    if ($dropTables && !$dryRun) {
        $dropSql = ($toType === 'pgsql')
            ? "DROP TABLE IF EXISTS \"$table\" CASCADE"
            : "DROP TABLE IF EXISTS `$table`";
        $tgt->exec($dropSql);
    }

    if (!$dryRun) {
        try {
            $tgt->exec($createSql);
        } catch (PDOException $e) {
            if ($dropTables) {
                warn("Failed to create $table: " . $e->getMessage());
                continue;
            }
            // Table may already exist
        }
    }

    // Copy data
    $count = copyTableData($src, $tgt, $table, $columns, $toType, $batchSize, $dryRun);
    $totalRows += $count;
    info("  $table: $count rows" . ($dryRun ? ' (dry run)' : ''));
}

if (!$dryRun) {
    $tgt->commit();
}

// Re-enable constraints
if ($toType === 'mysql' && !$dryRun) $tgt->exec("SET FOREIGN_KEY_CHECKS=1");
if ($toType === 'pgsql' && !$dryRun) $tgt->exec("SET session_replication_role = 'origin'");
if ($toType === 'sqlite' && !$dryRun) $tgt->exec('PRAGMA foreign_keys=ON');

info("");
info("Migration complete: " . count($tables) . " tables, $totalRows rows" . ($dryRun ? ' (dry run)' : ''));

// ── Generate Data.php for target ─────────────────────────────────────
if (!$dryRun) {
    info("");
    info("Update your Data.php to use the new database:");
    if ($toType === 'sqlite') {
        if (preg_match('/sqlite:(.+)/', $tgtDsn, $m)) {
            info('  $DatabaseType = \'sqlite\';');
            info('  $DatabaseName = \'' . $m[1] . '\';');
        }
    } elseif ($toType === 'mysql') {
        info('  $DatabaseType = \'mysqli\';');
        info('  $DatabaseServer = \'' . ($tgtUser ? parseDsnHost($tgtDsn) : 'localhost') . '\';');
        info('  $DatabaseName = \'' . parseDsnDbName($tgtDsn) . '\';');
        info('  $DatabaseUsername = \'' . $tgtUser . '\';');
        info('  $DatabasePassword = \'***\';');
    } elseif ($toType === 'pgsql') {
        info('  $DatabaseType = \'pgsql\';');
        info('  $DatabaseServer = \'' . parseDsnHost($tgtDsn) . '\';');
        info('  $DatabaseName = \'' . parseDsnDbName($tgtDsn) . '\';');
        info('  $DatabaseUsername = \'' . $tgtUser . '\';');
        info('  $DatabasePassword = \'***\';');
    }
}

// ═════════════════════════════════════════════════════════════════════
// Functions
// ═════════════════════════════════════════════════════════════════════

function getSourceTables(PDO $pdo, string $type): array
{
    $tables = [];
    switch ($type) {
        case 'sqlite':
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
            break;
        case 'mysql':
            $stmt = $pdo->query("SHOW TABLES");
            break;
        case 'pgsql':
            $stmt = $pdo->query("SELECT tablename AS name FROM pg_tables WHERE schemaname='public' ORDER BY tablename");
            break;
    }
    while ($row = $stmt->fetch()) {
        $tables[] = array_values($row)[0];
    }
    return $tables;
}

function getColumns(PDO $pdo, string $type, string $table): array
{
    $columns = [];
    switch ($type) {
        case 'sqlite':
            $stmt = $pdo->query("PRAGMA table_info(\"$table\")");
            while ($row = $stmt->fetch()) {
                $columns[] = [
                    'name'    => $row['name'],
                    'type'    => strtoupper($row['type'] ?: 'TEXT'),
                    'notnull' => (bool)$row['notnull'],
                    'default' => $row['dflt_value'],
                    'pk'      => (bool)$row['pk'],
                ];
            }
            break;
        case 'mysql':
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
            while ($row = $stmt->fetch()) {
                $columns[] = [
                    'name'    => $row['Field'],
                    'type'    => strtoupper($row['Type']),
                    'notnull' => ($row['Null'] === 'NO'),
                    'default' => $row['Default'],
                    'pk'      => ($row['Key'] === 'PRI'),
                    'auto'    => (strpos($row['Extra'] ?? '', 'auto_increment') !== false),
                ];
            }
            break;
        case 'pgsql':
            $stmt = $pdo->query("
                SELECT column_name, data_type, is_nullable, column_default,
                       (SELECT COUNT(*) FROM information_schema.key_column_usage k
                        JOIN information_schema.table_constraints tc ON k.constraint_name=tc.constraint_name
                        WHERE tc.constraint_type='PRIMARY KEY' AND k.table_name='$table'
                        AND k.column_name=c.column_name) AS is_pk
                FROM information_schema.columns c
                WHERE table_name='$table' AND table_schema='public'
                ORDER BY ordinal_position
            ");
            while ($row = $stmt->fetch()) {
                $columns[] = [
                    'name'    => $row['column_name'],
                    'type'    => strtoupper($row['data_type']),
                    'notnull' => ($row['is_nullable'] === 'NO'),
                    'default' => $row['column_default'],
                    'pk'      => ((int)$row['is_pk'] > 0),
                    'auto'    => (strpos($row['column_default'] ?? '', 'nextval') !== false),
                ];
            }
            break;
    }
    return $columns;
}

function generateCreateTable(string $table, array $columns, string $toType): string
{
    $q = ($toType === 'pgsql') ? '"' : '`';
    $colDefs = [];
    $pks = [];

    foreach ($columns as $col) {
        $name = $q . $col['name'] . $q;
        $type = mapColumnType($col['type'], $toType, !empty($col['auto']));
        $nn = $col['notnull'] ? ' NOT NULL' : '';
        $auto = '';

        if (!empty($col['auto']) || ($col['pk'] && preg_match('/INTEGER|INT|SERIAL/i', $col['type']))) {
            switch ($toType) {
                case 'sqlite':
                    $type = 'INTEGER';
                    $auto = ' PRIMARY KEY AUTOINCREMENT';
                    $nn = '';
                    break;
                case 'mysql':
                    $type = 'INT';
                    $auto = ' AUTO_INCREMENT';
                    break;
                case 'pgsql':
                    $type = 'SERIAL';
                    $nn = '';
                    $auto = '';
                    break;
            }
        }

        if ($col['pk']) $pks[] = $col['name'];

        $def = $col['default'];
        $defaultSql = '';
        if ($def !== null && $def !== '' && !$auto && strpos($def ?? '', 'nextval') === false) {
            $def = preg_replace('/^\'|\'$/','', $def);
            if (strtoupper($def) === 'CURRENT_TIMESTAMP' || $def === "datetime('now')") {
                $defaultSql = ($toType === 'pgsql') ? ' DEFAULT CURRENT_TIMESTAMP' : ' DEFAULT CURRENT_TIMESTAMP';
            } elseif (strtoupper($def) === 'NULL') {
                $defaultSql = ' DEFAULT NULL';
            } else {
                $defaultSql = " DEFAULT '$def'";
            }
        }

        $colDefs[] = "  $name $type$nn$defaultSql$auto";
    }

    // Add PRIMARY KEY constraint (unless handled inline by SQLite AUTOINCREMENT)
    $hasAutoInline = false;
    foreach ($columns as $col) {
        if (!empty($col['auto']) || ($col['pk'] && preg_match('/INTEGER|INT|SERIAL/i', $col['type']))) {
            if ($toType === 'sqlite') $hasAutoInline = true;
        }
    }

    if (!empty($pks) && !$hasAutoInline) {
        $pkCols = implode(', ', array_map(fn($p) => $q . $p . $q, $pks));
        $colDefs[] = "  PRIMARY KEY ($pkCols)";
    }

    $tableName = $q . $table . $q;
    $engine = ($toType === 'mysql') ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';

    return "CREATE TABLE IF NOT EXISTS $tableName (\n" . implode(",\n", $colDefs) . "\n)$engine";
}

function mapColumnType(string $srcType, string $toType, bool $isAuto = false): string
{
    // Normalize source type
    $upper = strtoupper(preg_replace('/\(.+\)/', '', $srcType));

    $map = [
        'sqlite' => [
            'INT' => 'INTEGER', 'INTEGER' => 'INTEGER', 'BIGINT' => 'INTEGER',
            'TINYINT' => 'INTEGER', 'SMALLINT' => 'INTEGER', 'MEDIUMINT' => 'INTEGER',
            'VARCHAR' => 'TEXT', 'CHARACTER VARYING' => 'TEXT', 'CHAR' => 'TEXT',
            'TEXT' => 'TEXT', 'LONGTEXT' => 'TEXT', 'MEDIUMTEXT' => 'TEXT',
            'BLOB' => 'BLOB', 'LONGBLOB' => 'BLOB', 'MEDIUMBLOB' => 'BLOB',
            'REAL' => 'REAL', 'FLOAT' => 'REAL', 'DOUBLE' => 'REAL',
            'DECIMAL' => 'REAL', 'NUMERIC' => 'REAL',
            'DATE' => 'DATE', 'DATETIME' => 'DATETIME', 'TIMESTAMP' => 'TIMESTAMP',
            'BOOLEAN' => 'INTEGER', 'ENUM' => 'TEXT', 'SET' => 'TEXT',
            'SERIAL' => 'INTEGER',
        ],
        'mysql' => [
            'INT' => 'INT', 'INTEGER' => 'INT', 'BIGINT' => 'BIGINT',
            'TINYINT' => 'TINYINT', 'SMALLINT' => 'SMALLINT', 'MEDIUMINT' => 'MEDIUMINT',
            'VARCHAR' => 'VARCHAR(255)', 'CHARACTER VARYING' => 'VARCHAR(255)', 'CHAR' => 'CHAR(255)',
            'TEXT' => 'TEXT', 'LONGTEXT' => 'LONGTEXT', 'MEDIUMTEXT' => 'MEDIUMTEXT',
            'BLOB' => 'BLOB', 'LONGBLOB' => 'LONGBLOB', 'MEDIUMBLOB' => 'MEDIUMBLOB',
            'REAL' => 'DOUBLE', 'FLOAT' => 'FLOAT', 'DOUBLE' => 'DOUBLE',
            'DECIMAL' => 'DECIMAL(10,2)', 'NUMERIC' => 'DECIMAL(10,2)',
            'DATE' => 'DATE', 'DATETIME' => 'DATETIME', 'TIMESTAMP' => 'TIMESTAMP',
            'BOOLEAN' => 'TINYINT(1)', 'ENUM' => 'TEXT', 'SET' => 'TEXT',
            'SERIAL' => 'INT',
        ],
        'pgsql' => [
            'INT' => 'INTEGER', 'INTEGER' => 'INTEGER', 'BIGINT' => 'BIGINT',
            'TINYINT' => 'SMALLINT', 'SMALLINT' => 'SMALLINT', 'MEDIUMINT' => 'INTEGER',
            'VARCHAR' => 'VARCHAR(255)', 'CHARACTER VARYING' => 'VARCHAR(255)', 'CHAR' => 'CHAR(255)',
            'TEXT' => 'TEXT', 'LONGTEXT' => 'TEXT', 'MEDIUMTEXT' => 'TEXT',
            'BLOB' => 'BYTEA', 'LONGBLOB' => 'BYTEA', 'MEDIUMBLOB' => 'BYTEA',
            'REAL' => 'DOUBLE PRECISION', 'FLOAT' => 'REAL', 'DOUBLE' => 'DOUBLE PRECISION',
            'DECIMAL' => 'NUMERIC(10,2)', 'NUMERIC' => 'NUMERIC(10,2)',
            'DATE' => 'DATE', 'DATETIME' => 'TIMESTAMP', 'TIMESTAMP' => 'TIMESTAMP',
            'BOOLEAN' => 'BOOLEAN', 'ENUM' => 'TEXT', 'SET' => 'TEXT',
            'SERIAL' => 'SERIAL',
        ],
    ];

    // Try exact match, then partial
    if (isset($map[$toType][$upper])) {
        return $map[$toType][$upper];
    }

    // Handle VARCHAR(n), CHAR(n), etc. — preserve size for mysql/pgsql
    if (preg_match('/^(VARCHAR|CHAR)\((\d+)\)/i', $srcType, $m)) {
        if ($toType === 'sqlite') return 'TEXT';
        return strtoupper($m[1]) . '(' . $m[2] . ')';
    }
    if (preg_match('/^(DECIMAL|NUMERIC)\((\d+),\s*(\d+)\)/i', $srcType, $m)) {
        if ($toType === 'sqlite') return 'REAL';
        if ($toType === 'pgsql') return 'NUMERIC(' . $m[2] . ',' . $m[3] . ')';
        return 'DECIMAL(' . $m[2] . ',' . $m[3] . ')';
    }

    // Fallback
    return ($toType === 'sqlite') ? 'TEXT' : 'TEXT';
}

function copyTableData(PDO $src, PDO $tgt, string $table, array $columns, string $toType, int $batchSize, bool $dryRun): int
{
    $q = ($toType === 'pgsql') ? '"' : '`';
    $sq = ($toType === 'pgsql') ? '"' : '`';

    $colNames = array_map(fn($c) => $c['name'], $columns);

    // Read from source
    $srcQ = ($toType === 'pgsql') ? '"' : '`'; // source quoting doesn't matter for PDO
    try {
        $stmt = $src->query("SELECT * FROM \"$table\"");
    } catch (PDOException $e) {
        try {
            $stmt = $src->query("SELECT * FROM `$table`");
        } catch (PDOException $e2) {
            $stmt = $src->query("SELECT * FROM $table");
        }
    }

    $rows = $stmt->fetchAll();
    if (empty($rows) || $dryRun) return count($rows);

    // Build INSERT
    $quotedCols = implode(', ', array_map(fn($c) => $q . $c . $q, $colNames));
    $placeholders = implode(', ', array_fill(0, count($colNames), '?'));
    $insertSql = "INSERT INTO $q$table$q ($quotedCols) VALUES ($placeholders)";

    $insertStmt = $tgt->prepare($insertSql);
    $count = 0;

    foreach ($rows as $row) {
        $values = [];
        foreach ($colNames as $col) {
            $values[] = $row[$col] ?? ($row[strtoupper($col)] ?? ($row[strtolower($col)] ?? null));
        }
        try {
            $insertStmt->execute($values);
            $count++;
        } catch (PDOException $e) {
            // Skip duplicates / constraint violations
            if (strpos($e->getMessage(), 'UNIQUE') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
                continue;
            }
            warn("  Row insert failed in $table: " . substr($e->getMessage(), 0, 120));
        }
    }

    // Reset auto-increment sequence for PostgreSQL
    if ($toType === 'pgsql') {
        foreach ($columns as $col) {
            if (!empty($col['auto']) || ($col['pk'] && preg_match('/INT|SERIAL/i', $col['type']))) {
                try {
                    $tgt->exec("SELECT setval(pg_get_serial_sequence('\"$table\"', '{$col['name']}'), COALESCE((SELECT MAX(\"{$col['name']}\") FROM \"$table\"), 1))");
                } catch (PDOException $e) {
                    // Sequence may not exist
                }
            }
        }
    }

    return $count;
}

function parseDsnHost(string $dsn): string
{
    if (preg_match('/host=([^;]+)/', $dsn, $m)) return $m[1];
    return 'localhost';
}

function parseDsnDbName(string $dsn): string
{
    if (preg_match('/dbname=([^;]+)/', $dsn, $m)) return $m[1];
    return '';
}

function info(string $msg): void
{
    echo "\033[32m[INFO]\033[0m $msg\n";
}

function warn(string $msg): void
{
    echo "\033[33m[WARN]\033[0m $msg\n";
}
