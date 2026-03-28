<?php
/**
 * SQLite Adapter for openSIS Classic
 *
 * Provides a lightweight SQLite backend for small schools that don't
 * need a full MySQL server. Plugs into the existing DatabaseInc.php
 * switch($DatabaseType) pattern using $DatabaseType = 'sqlite'.
 *
 * Setup in Data.php:
 *   $DatabaseType = 'sqlite';
 *   $DatabaseName = __DIR__ . '/data/opensis.db';
 *
 * The adapter wraps PDO SQLite with a mysqli-compatible interface so
 * existing code (DBQuery, db_fetch_row, DBGet) works unchanged.
 */

class SqliteConnection
{
    private PDO $pdo;

    public function __construct(string $dbPath)
    {
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $this->pdo = new PDO("sqlite:$dbPath", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Enable WAL mode for better concurrent read performance
        $this->pdo->exec('PRAGMA journal_mode=WAL');
        $this->pdo->exec('PRAGMA foreign_keys=ON');
    }

    public function query(string $sql)
    {
        $sql = $this->translateSql($sql);

        // Skip MySQL-specific SET/SHOW statements
        if (preg_match('/^\s*SET\s/i', $sql)) {
            return true;
        }

        try {
            $stmt = $this->pdo->query($sql);
            if ($stmt === false) {
                return false;
            }
            return new SqliteResultSet($stmt);
        } catch (PDOException $e) {
            // Log but don't die for non-critical errors
            error_log("SQLite query error: " . $e->getMessage() . " SQL: " . substr($sql, 0, 200));
            return false;
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function escapeString(string $value): string
    {
        // PDO::quote adds surrounding quotes, strip them
        $quoted = $this->pdo->quote($value);
        return substr($quoted, 1, -1);
    }

    /**
     * Translate MySQL-specific SQL to SQLite-compatible SQL.
     */
    private function translateSql(string $sql): string
    {
        // SHOW TABLES LIKE → sqlite_master query
        if (preg_match("/^SHOW\s+TABLES\s+LIKE\s+'([^']+)'/i", $sql, $m)) {
            return "SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '{$m[1]}'";
        }

        // SHOW COLUMNS FROM → pragma table_info
        if (preg_match('/^SHOW\s+COLUMNS\s+FROM\s+(\S+)/i', $sql, $m)) {
            return "PRAGMA table_info({$m[1]})";
        }

        // SHOW VARIABLES → return empty
        if (preg_match('/^SHOW\s+VARIABLES/i', $sql)) {
            return "SELECT '' AS Variable_name, '' AS Value WHERE 0";
        }

        // ENGINE=InnoDB and other MySQL table options
        $sql = preg_replace('/\s+ENGINE\s*=\s*\w+/i', '', $sql);
        $sql = preg_replace('/\s+DEFAULT\s+CHARSET\s*=\s*\w+/i', '', $sql);
        $sql = preg_replace('/\s+COLLATE\s*=?\s*\w+/i', '', $sql);
        $sql = preg_replace('/\s+AUTO_INCREMENT\s*=\s*\d+/i', '', $sql);
        $sql = preg_replace('/\s+CHARACTER\s+SET\s+\w+/i', '', $sql);

        // AUTO_INCREMENT → AUTOINCREMENT (in column defs)
        // Handle AUTO_INCREMENT: SQLite requires INTEGER PRIMARY KEY AUTOINCREMENT
        // Remove NOT NULL before AUTO_INCREMENT and mark column for PRIMARY KEY inline
        if (preg_match('/AUTO_INCREMENT/i', $sql) && preg_match('/CREATE\s+TABLE/i', $sql)) {
            // Remove separate PRIMARY KEY (col) clause
            $sql = preg_replace('/,\s*PRIMARY\s+KEY\s*\([^)]+\)/i', '', $sql);
            // Convert: col INT(...) NOT NULL AUTO_INCREMENT → col INTEGER PRIMARY KEY AUTOINCREMENT
            $sql = preg_replace('/(\w+)\s+\w*INT\(\d+\)\s*(NOT\s+NULL\s*)?AUTO_INCREMENT/i', '$1 INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
            $sql = preg_replace('/\bAUTO_INCREMENT\b/i', 'PRIMARY KEY AUTOINCREMENT', $sql);
        }

        // INT(n) → INTEGER, BIGINT → INTEGER, TINYINT → INTEGER
        $sql = preg_replace('/\b(TINY|SMALL|MEDIUM|BIG)?INT\(\d+\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/\bBIGINT\b/i', 'INTEGER', $sql);
        $sql = preg_replace('/\bTINYINT\b/i', 'INTEGER', $sql);

        // UNSIGNED → (remove)
        $sql = preg_replace('/\bUNSIGNED\b/i', '', $sql);

        // DATETIME, TIMESTAMP → keep as TIMESTAMP (SQLite stores as text but
        // datetime functions work natively on ISO-8601 strings)
        // Strip ON UPDATE CURRENT_TIMESTAMP (MySQL-only trigger syntax)
        $sql = preg_replace('/\bON\s+UPDATE\s+CURRENT_TIMESTAMP\b/i', '', $sql);

        // ENUM('a','b','c') → TEXT CHECK(col IN ('a','b','c')) is ideal but
        // complex to wire into arbitrary column defs, so store allowed values
        // as a CHECK comment and keep as TEXT. Values are semicolon-joined in a comment.
        $sql = preg_replace_callback('/\bENUM\s*\(([^)]+)\)/i', function($m) {
            // Extract values for a CHECK constraint comment
            $vals = $m[1]; // e.g. 'admin','teacher','parent'
            return "TEXT /* ENUM($vals) */";
        }, $sql);

        // LONGTEXT, MEDIUMTEXT → TEXT
        $sql = preg_replace('/\b(LONG|MEDIUM)TEXT\b/i', 'TEXT', $sql);

        // LONGBLOB, MEDIUMBLOB → BLOB
        $sql = preg_replace('/\b(LONG|MEDIUM)BLOB\b/i', 'BLOB', $sql);

        // DOUBLE(m,n) → REAL
        $sql = preg_replace('/\bDOUBLE\s*\(\d+,\s*\d+\)/i', 'REAL', $sql);
        $sql = preg_replace('/\bFLOAT\s*\(\d+,\s*\d+\)/i', 'REAL', $sql);
        $sql = preg_replace('/\bDECIMAL\s*\(\d+,\s*\d+\)/i', 'REAL', $sql);

        // IF NOT EXISTS for CREATE TABLE (SQLite supports this natively)
        // INDEX creation inside CREATE TABLE → strip (add separately)
        // KEY `name` (...) → strip
        $sql = preg_replace('/,\s*(?:UNIQUE\s+)?(?:KEY|INDEX)\s+`?\w+`?\s*\([^)]+\)/i', '', $sql);

        // REPLACE backtick-quoted identifiers that conflict with SQLite keywords
        // (most backtick usage is fine in SQLite)

        // INSERT IGNORE → INSERT OR IGNORE
        $sql = preg_replace('/\bINSERT\s+IGNORE\b/i', 'INSERT OR IGNORE', $sql);

        // ON DUPLICATE KEY UPDATE → upsert (simplified)
        if (preg_match('/\bON\s+DUPLICATE\s+KEY\s+UPDATE\b/i', $sql)) {
            $sql = preg_replace('/\bINSERT\s+INTO\b/i', 'INSERT OR REPLACE INTO', $sql);
            $sql = preg_replace('/\s+ON\s+DUPLICATE\s+KEY\s+UPDATE\s+.+$/i', '', $sql);
        }

        // LIMIT with comma syntax: LIMIT offset, count → LIMIT count OFFSET offset
        if (preg_match('/LIMIT\s+(\d+)\s*,\s*(\d+)/i', $sql, $m)) {
            $sql = preg_replace('/LIMIT\s+\d+\s*,\s*\d+/i', "LIMIT {$m[2]} OFFSET {$m[1]}", $sql);
        }

        // CONCAT(a, b, c) → (a || b || c)
        $sql = preg_replace_callback('/\bCONCAT\s*\(([^)]+)\)/i', function ($m) {
            $parts = explode(',', $m[1]);
            return '(' . implode(' || ', array_map('trim', $parts)) . ')';
        }, $sql);

        // NOW() → datetime('now')
        $sql = preg_replace('/\bNOW\s*\(\)/i', "datetime('now')", $sql);

        // CURDATE() → date('now')
        $sql = preg_replace('/\bCURDATE\s*\(\)/i', "date('now')", $sql);

        // IFNULL already works in SQLite

        return $sql;
    }
}

/**
 * Wraps a PDOStatement to provide a fetch_assoc()-compatible interface.
 */
class SqliteResultSet
{
    private \PDOStatement $stmt;
    public int $num_rows;

    public function __construct(\PDOStatement $stmt)
    {
        $this->stmt = $stmt;
        // num_rows not reliably available for SELECT in PDO/SQLite
        $this->num_rows = 0;
    }

    public function fetch_assoc(): ?array
    {
        $row = $this->stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }
}
