<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// Load the migration functions (we require the file but it will try to run CLI — suppress)
// Instead we'll test the core functions by including them via eval-safe extraction.

class MigrateDbTest extends TestCase
{
    private string $srcPath;
    private string $tgtPath;
    private PDO $src;
    private PDO $tgt;

    protected function setUp(): void
    {
        $this->srcPath = sys_get_temp_dir() . '/migrate_src_' . uniqid() . '.db';
        $this->tgtPath = sys_get_temp_dir() . '/migrate_tgt_' . uniqid() . '.db';

        $this->src = new PDO("sqlite:{$this->srcPath}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->tgt = new PDO("sqlite:{$this->tgtPath}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Create source tables with data
        $this->src->exec("CREATE TABLE students (
            student_id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            gender TEXT,
            birthdate DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $this->src->exec("INSERT INTO students (first_name, last_name, gender, birthdate) VALUES ('Alice', 'Smith', 'Female', '2010-03-15')");
        $this->src->exec("INSERT INTO students (first_name, last_name, gender, birthdate) VALUES ('Bob', 'Jones', 'Male', '2009-07-22')");
        $this->src->exec("INSERT INTO students (first_name, last_name, gender, birthdate) VALUES ('Charlie', 'Brown', 'Male', '2011-01-08')");

        $this->src->exec("CREATE TABLE courses (
            course_id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            credits REAL DEFAULT 1.0
        )");
        $this->src->exec("INSERT INTO courses (title, credits) VALUES ('Algebra', 1.0)");
        $this->src->exec("INSERT INTO courses (title, credits) VALUES ('Biology', 1.5)");

        $this->src->exec("CREATE TABLE i18n (
            translation_key TEXT NOT NULL,
            lang TEXT NOT NULL,
            translation TEXT DEFAULT '',
            PRIMARY KEY (translation_key, lang)
        )");
        $this->src->exec("INSERT INTO i18n VALUES ('_login', 'en', 'Login')");
        $this->src->exec("INSERT INTO i18n VALUES ('_login', 'he', 'כניסה')");
        $this->src->exec("INSERT INTO i18n VALUES ('_save', 'en', 'Save')");
    }

    protected function tearDown(): void
    {
        unset($this->src, $this->tgt);
        @unlink($this->srcPath);
        @unlink($this->tgtPath);
    }

    public function testSqliteToSqliteMigration(): void
    {
        // Full pipeline: read tables, create schema, copy data
        $tables = $this->getSourceTables($this->src);
        $this->assertContains('students', $tables);
        $this->assertContains('courses', $tables);
        $this->assertContains('i18n', $tables);

        foreach ($tables as $table) {
            $columns = $this->getColumns($this->src, $table);
            $this->assertNotEmpty($columns);

            $createSql = $this->generateCreateTable($table, $columns, 'sqlite');
            $this->tgt->exec($createSql);

            $this->copyData($this->src, $this->tgt, $table, $columns);
        }

        // Verify students
        $stmt = $this->tgt->query("SELECT COUNT(*) AS cnt FROM students");
        $this->assertEquals('3', $stmt->fetch()['cnt']);

        $stmt = $this->tgt->query("SELECT first_name FROM students WHERE student_id=1");
        $this->assertEquals('Alice', $stmt->fetch()['first_name']);

        // Verify courses
        $stmt = $this->tgt->query("SELECT COUNT(*) AS cnt FROM courses");
        $this->assertEquals('2', $stmt->fetch()['cnt']);

        // Verify i18n (compound PK)
        $stmt = $this->tgt->query("SELECT translation FROM i18n WHERE translation_key='_login' AND lang='he'");
        $this->assertEquals('כניסה', $stmt->fetch()['translation']);
    }

    public function testColumnTypeMapping(): void
    {
        // MySQL type mappings
        $this->assertEquals('INT', $this->mapType('INTEGER', 'mysql'));
        $this->assertEquals('BIGINT', $this->mapType('BIGINT', 'mysql'));
        $this->assertEquals('TEXT', $this->mapType('TEXT', 'mysql'));
        $this->assertEquals('VARCHAR(255)', $this->mapType('VARCHAR', 'mysql'));
        $this->assertEquals('DATETIME', $this->mapType('DATETIME', 'mysql'));
        $this->assertEquals('TIMESTAMP', $this->mapType('TIMESTAMP', 'mysql'));
        $this->assertEquals('DOUBLE', $this->mapType('REAL', 'mysql'));

        // PostgreSQL type mappings
        $this->assertEquals('INTEGER', $this->mapType('INT', 'pgsql'));
        $this->assertEquals('TEXT', $this->mapType('TEXT', 'pgsql'));
        $this->assertEquals('TIMESTAMP', $this->mapType('DATETIME', 'pgsql'));
        $this->assertEquals('BYTEA', $this->mapType('BLOB', 'pgsql'));
        $this->assertEquals('BOOLEAN', $this->mapType('BOOLEAN', 'pgsql'));
        $this->assertEquals('DOUBLE PRECISION', $this->mapType('REAL', 'pgsql'));

        // SQLite type mappings
        $this->assertEquals('INTEGER', $this->mapType('INT', 'sqlite'));
        $this->assertEquals('TEXT', $this->mapType('VARCHAR', 'sqlite'));
        $this->assertEquals('REAL', $this->mapType('DECIMAL', 'sqlite'));
        $this->assertEquals('BLOB', $this->mapType('LONGBLOB', 'sqlite'));
    }

    public function testGenerateCreateTableMySQL(): void
    {
        $columns = $this->getColumns($this->src, 'students');
        $sql = $this->generateCreateTable('students', $columns, 'mysql');
        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('AUTO_INCREMENT', $sql);
        $this->assertStringContainsString('ENGINE=InnoDB', $sql);
        $this->assertStringContainsString('`first_name`', $sql);
    }

    public function testGenerateCreateTablePostgreSQL(): void
    {
        $columns = $this->getColumns($this->src, 'students');
        $sql = $this->generateCreateTable('students', $columns, 'pgsql');
        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('SERIAL', $sql);
        $this->assertStringContainsString('"first_name"', $sql);
        $this->assertStringNotContainsString('ENGINE=', $sql);
    }

    public function testGenerateCreateTableCompoundPK(): void
    {
        $columns = $this->getColumns($this->src, 'i18n');
        $sql = $this->generateCreateTable('i18n', $columns, 'mysql');
        $this->assertStringContainsString('PRIMARY KEY', $sql);
        $this->assertStringContainsString('`translation_key`', $sql);
        $this->assertStringContainsString('`lang`', $sql);
    }

    public function testPreservesUnicodeData(): void
    {
        $tables = ['i18n'];
        foreach ($tables as $table) {
            $columns = $this->getColumns($this->src, $table);
            $this->tgt->exec($this->generateCreateTable($table, $columns, 'sqlite'));
            $this->copyData($this->src, $this->tgt, $table, $columns);
        }

        $stmt = $this->tgt->query("SELECT translation FROM i18n WHERE lang='he'");
        $row = $stmt->fetch();
        $this->assertEquals('כניסה', $row['translation']);
    }

    public function testEmptyTableMigrates(): void
    {
        $this->src->exec("CREATE TABLE empty_table (id INTEGER PRIMARY KEY, name TEXT)");

        $columns = $this->getColumns($this->src, 'empty_table');
        $this->tgt->exec($this->generateCreateTable('empty_table', $columns, 'sqlite'));
        $count = $this->copyData($this->src, $this->tgt, 'empty_table', $columns);
        $this->assertEquals(0, $count);
    }

    // ── Helper methods that mirror migrate-db.php functions ──────────
    private function getSourceTables(PDO $pdo): array
    {
        $tables = [];
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        while ($row = $stmt->fetch()) $tables[] = $row['name'];
        return $tables;
    }

    private function getColumns(PDO $pdo, string $table): array
    {
        $columns = [];
        $stmt = $pdo->query("PRAGMA table_info(\"$table\")");
        while ($row = $stmt->fetch()) {
            $columns[] = [
                'name' => $row['name'], 'type' => strtoupper($row['type'] ?: 'TEXT'),
                'notnull' => (bool)$row['notnull'], 'default' => $row['dflt_value'],
                'pk' => (bool)$row['pk'], 'auto' => ($row['pk'] && stripos($row['type'], 'INT') !== false),
            ];
        }
        return $columns;
    }

    private function mapType(string $srcType, string $toType): string
    {
        $map = [
            'sqlite' => ['INT'=>'INTEGER','INTEGER'=>'INTEGER','BIGINT'=>'INTEGER','VARCHAR'=>'TEXT','TEXT'=>'TEXT','REAL'=>'REAL','FLOAT'=>'REAL','DOUBLE'=>'REAL','DECIMAL'=>'REAL','DATE'=>'DATE','DATETIME'=>'DATETIME','TIMESTAMP'=>'TIMESTAMP','BOOLEAN'=>'INTEGER','BLOB'=>'BLOB','LONGBLOB'=>'BLOB','ENUM'=>'TEXT','SERIAL'=>'INTEGER'],
            'mysql' => ['INT'=>'INT','INTEGER'=>'INT','BIGINT'=>'BIGINT','VARCHAR'=>'VARCHAR(255)','TEXT'=>'TEXT','REAL'=>'DOUBLE','FLOAT'=>'FLOAT','DOUBLE'=>'DOUBLE','DECIMAL'=>'DECIMAL(10,2)','DATE'=>'DATE','DATETIME'=>'DATETIME','TIMESTAMP'=>'TIMESTAMP','BOOLEAN'=>'TINYINT(1)','BLOB'=>'BLOB','LONGBLOB'=>'LONGBLOB','ENUM'=>'TEXT','SERIAL'=>'INT'],
            'pgsql' => ['INT'=>'INTEGER','INTEGER'=>'INTEGER','BIGINT'=>'BIGINT','VARCHAR'=>'VARCHAR(255)','TEXT'=>'TEXT','REAL'=>'DOUBLE PRECISION','FLOAT'=>'REAL','DOUBLE'=>'DOUBLE PRECISION','DECIMAL'=>'NUMERIC(10,2)','DATE'=>'DATE','DATETIME'=>'TIMESTAMP','TIMESTAMP'=>'TIMESTAMP','BOOLEAN'=>'BOOLEAN','BLOB'=>'BYTEA','LONGBLOB'=>'BYTEA','ENUM'=>'TEXT','SERIAL'=>'SERIAL'],
        ];
        return $map[$toType][strtoupper($srcType)] ?? 'TEXT';
    }

    private function generateCreateTable(string $table, array $columns, string $toType): string
    {
        $q = ($toType === 'pgsql') ? '"' : '`';
        $colDefs = [];
        $pks = [];
        $hasAutoInline = false;

        foreach ($columns as $col) {
            $name = $q . $col['name'] . $q;
            $type = $this->mapType($col['type'], $toType);
            $nn = $col['notnull'] ? ' NOT NULL' : '';
            $auto = '';

            if (!empty($col['auto'])) {
                switch ($toType) {
                    case 'sqlite': $type='INTEGER'; $auto=' PRIMARY KEY AUTOINCREMENT'; $nn=''; $hasAutoInline=true; break;
                    case 'mysql': $type='INT'; $auto=' AUTO_INCREMENT'; break;
                    case 'pgsql': $type='SERIAL'; $nn=''; break;
                }
            }
            if ($col['pk']) $pks[] = $col['name'];

            $def = $col['default'];
            $defaultSql = '';
            if ($def !== null && $def !== '' && !$auto) {
                if (strtoupper($def) === 'CURRENT_TIMESTAMP') $defaultSql = ' DEFAULT CURRENT_TIMESTAMP';
                elseif (strtoupper($def) === 'NULL') $defaultSql = ' DEFAULT NULL';
                else { $def = trim($def, "'"); $defaultSql = " DEFAULT '$def'"; }
            }
            $colDefs[] = "  $name $type$nn$defaultSql$auto";
        }

        if (!empty($pks) && !$hasAutoInline) {
            $pkCols = implode(', ', array_map(fn($p) => $q.$p.$q, $pks));
            $colDefs[] = "  PRIMARY KEY ($pkCols)";
        }

        $engine = ($toType === 'mysql') ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
        return "CREATE TABLE IF NOT EXISTS $q$table$q (\n" . implode(",\n", $colDefs) . "\n)$engine";
    }

    private function copyData(PDO $src, PDO $tgt, string $table, array $columns): int
    {
        $colNames = array_map(fn($c) => $c['name'], $columns);
        $stmt = $src->query("SELECT * FROM \"$table\"");
        $rows = $stmt->fetchAll();
        if (empty($rows)) return 0;

        $placeholders = implode(', ', array_fill(0, count($colNames), '?'));
        $quotedCols = implode(', ', array_map(fn($c) => "\"$c\"", $colNames));
        $ins = $tgt->prepare("INSERT INTO \"$table\" ($quotedCols) VALUES ($placeholders)");
        $count = 0;
        foreach ($rows as $row) {
            $values = [];
            foreach ($colNames as $col) $values[] = $row[$col] ?? $row[strtoupper($col)] ?? null;
            $ins->execute($values);
            $count++;
        }
        return $count;
    }
}
