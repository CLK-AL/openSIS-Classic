<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/SqliteAdapter.php';

class SqliteAdapterTest extends TestCase
{
    private string $dbPath;
    private SqliteConnection $conn;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/opensis_test_' . uniqid() . '.db';
        $this->conn = new SqliteConnection($this->dbPath);
    }

    protected function tearDown(): void
    {
        unset($this->conn);
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    public function testCreatesDatabaseFile(): void
    {
        $this->assertFileExists($this->dbPath);
    }

    public function testCreateTable(): void
    {
        $result = $this->conn->query("CREATE TABLE test (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL)");
        $this->assertNotFalse($result);
    }

    public function testInsertAndSelect(): void
    {
        $this->conn->query("CREATE TABLE students (id INTEGER PRIMARY KEY AUTOINCREMENT, first_name TEXT, last_name TEXT)");
        $this->conn->query("INSERT INTO students (first_name, last_name) VALUES ('Alice', 'Smith')");
        $this->conn->query("INSERT INTO students (first_name, last_name) VALUES ('Bob', 'Jones')");

        $result = $this->conn->query("SELECT * FROM students ORDER BY id");
        $this->assertInstanceOf(SqliteResultSet::class, $result);

        $row1 = $result->fetch_assoc();
        $this->assertEquals('Alice', $row1['first_name']);

        $row2 = $result->fetch_assoc();
        $this->assertEquals('Jones', $row2['last_name']);

        $this->assertNull($result->fetch_assoc());
    }

    public function testTranslateMysqlAutoIncrement(): void
    {
        $result = $this->conn->query("CREATE TABLE test (id INT(11) NOT NULL AUTO_INCREMENT, name VARCHAR(255), PRIMARY KEY (id))");
        $this->assertNotFalse($result);

        $this->conn->query("INSERT INTO test (name) VALUES ('hello')");
        $rs = $this->conn->query("SELECT * FROM test");
        $row = $rs->fetch_assoc();
        $this->assertEquals('hello', $row['name']);
    }

    public function testTranslateConcat(): void
    {
        $this->conn->query("CREATE TABLE people (first TEXT, last TEXT)");
        $this->conn->query("INSERT INTO people VALUES ('John', 'Doe')");

        $rs = $this->conn->query("SELECT CONCAT(first, ' ', last) AS full_name FROM people");
        $row = $rs->fetch_assoc();
        $this->assertEquals('John Doe', $row['full_name']);
    }

    public function testTranslateNow(): void
    {
        $this->conn->query("CREATE TABLE log (created TEXT)");
        $this->conn->query("INSERT INTO log (created) VALUES (NOW())");
        $rs = $this->conn->query("SELECT created FROM log");
        $row = $rs->fetch_assoc();
        $this->assertNotEmpty($row['created']);
        // Should be a valid datetime
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $row['created']);
    }

    public function testTranslateShowTables(): void
    {
        $this->conn->query("CREATE TABLE test_table (id INTEGER PRIMARY KEY)");
        $rs = $this->conn->query("SHOW TABLES LIKE 'test_table'");
        $row = $rs->fetch_assoc();
        $this->assertEquals('test_table', $row['name']);
    }

    public function testTranslateShowTablesNoMatch(): void
    {
        $rs = $this->conn->query("SHOW TABLES LIKE 'nonexistent'");
        $this->assertNull($rs->fetch_assoc());
    }

    public function testInsertOrReplace(): void
    {
        $this->conn->query("CREATE TABLE config (key TEXT PRIMARY KEY, value TEXT)");
        $this->conn->query("INSERT INTO config VALUES ('version', '1.0')");
        // ON DUPLICATE KEY UPDATE → INSERT OR REPLACE
        $this->conn->query("INSERT INTO config (key, value) VALUES ('version', '2.0') ON DUPLICATE KEY UPDATE value='2.0'");
        $rs = $this->conn->query("SELECT value FROM config WHERE key='version'");
        $row = $rs->fetch_assoc();
        $this->assertEquals('2.0', $row['value']);
    }

    public function testInsertIgnore(): void
    {
        $this->conn->query("CREATE TABLE uq (id INTEGER PRIMARY KEY, name TEXT)");
        $this->conn->query("INSERT INTO uq VALUES (1, 'first')");
        // Should not throw
        $this->conn->query("INSERT IGNORE INTO uq VALUES (1, 'duplicate')");
        $rs = $this->conn->query("SELECT name FROM uq WHERE id=1");
        $row = $rs->fetch_assoc();
        $this->assertEquals('first', $row['name']);
    }

    public function testTranslateLimitOffset(): void
    {
        $this->conn->query("CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
        for ($i = 1; $i <= 10; $i++) {
            $this->conn->query("INSERT INTO items (name) VALUES ('item$i')");
        }

        $rs = $this->conn->query("SELECT name FROM items ORDER BY id LIMIT 3, 2");
        $row1 = $rs->fetch_assoc();
        $this->assertEquals('item4', $row1['name']);
        $row2 = $rs->fetch_assoc();
        $this->assertEquals('item5', $row2['name']);
        $this->assertNull($rs->fetch_assoc());
    }

    public function testEscapeString(): void
    {
        $escaped = $this->conn->escapeString("O'Malley");
        $this->assertStringContainsString("''", $escaped);
    }

    public function testSkipsMysqlSetStatements(): void
    {
        $result = $this->conn->query("SET GLOBAL event_scheduler = ON");
        $this->assertTrue($result);

        $result = $this->conn->query("set @userId= 1;");
        $this->assertTrue($result);
    }

    public function testTranslateEngine(): void
    {
        $sql = "CREATE TABLE test (id INTEGER PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $result = $this->conn->query($sql);
        $this->assertNotFalse($result);
    }

    public function testI18nTableWorkflow(): void
    {
        // Simulate the Translation Manager i18n table
        $this->conn->query("CREATE TABLE i18n (
            translation_key TEXT NOT NULL,
            lang TEXT NOT NULL,
            translation TEXT NOT NULL DEFAULT '',
            last_updated TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_by INTEGER DEFAULT NULL,
            PRIMARY KEY (translation_key, lang)
        )");

        $this->conn->query("INSERT INTO i18n (translation_key, lang, translation) VALUES ('_login', 'en', 'Login')");
        $this->conn->query("INSERT INTO i18n (translation_key, lang, translation) VALUES ('_login', 'he', 'כניסה')");
        $this->conn->query("INSERT INTO i18n (translation_key, lang, translation) VALUES ('_save', 'en', 'Save')");

        // Count
        $rs = $this->conn->query("SELECT COUNT(*) AS cnt FROM i18n");
        $row = $rs->fetch_assoc();
        $this->assertEquals('3', $row['cnt']);

        // Filter by language
        $rs = $this->conn->query("SELECT translation FROM i18n WHERE lang='he'");
        $row = $rs->fetch_assoc();
        $this->assertEquals('כניסה', $row['translation']);

        // Upsert
        $this->conn->query("INSERT INTO i18n (translation_key, lang, translation) VALUES ('_login', 'en', 'Sign In') ON DUPLICATE KEY UPDATE translation='Sign In'");
        $rs = $this->conn->query("SELECT translation FROM i18n WHERE translation_key='_login' AND lang='en'");
        $row = $rs->fetch_assoc();
        $this->assertEquals('Sign In', $row['translation']);
    }

    public function testStudentEnrollmentWorkflow(): void
    {
        $this->conn->query("CREATE TABLE students (
            student_id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT, last_name TEXT, gender TEXT
        )");
        $this->conn->query("CREATE TABLE student_enrollment (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER, school_id INTEGER, syear TEXT,
            start_date TEXT, end_date TEXT
        )");

        $this->conn->query("INSERT INTO students (first_name, last_name, gender) VALUES ('Alice', 'Smith', 'Female')");
        $this->conn->query("INSERT INTO students (first_name, last_name, gender) VALUES ('Bob', 'Jones', 'Male')");
        $this->conn->query("INSERT INTO student_enrollment (student_id, school_id, syear, start_date) VALUES (1, 1, '2025', '2025-08-15')");
        $this->conn->query("INSERT INTO student_enrollment (student_id, school_id, syear, start_date) VALUES (2, 1, '2025', '2025-08-15')");

        // Join query
        $rs = $this->conn->query("SELECT s.first_name, s.last_name, se.start_date
            FROM students s
            JOIN student_enrollment se ON s.student_id = se.student_id
            WHERE se.syear = '2025'
            ORDER BY s.last_name");

        $row1 = $rs->fetch_assoc();
        $this->assertEquals('Jones', $row1['last_name']);
        $row2 = $rs->fetch_assoc();
        $this->assertEquals('Smith', $row2['last_name']);
    }
}
