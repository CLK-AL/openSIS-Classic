<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/SqlSecurityFnc.php';

class SqlSecurityFilterTest extends TestCase
{
    // All tests run without DB connection (dbAvailable='no')

    public function testCleanStringPassesThrough(): void
    {
        $result = sqlSecurityFilter('hello world', 'no');
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('hello', $result);
    }

    public function testEmptyStringReturnsEmpty(): void
    {
        $this->assertEquals('', sqlSecurityFilter('', 'no'));
    }

    public function testBlocksUnionSelect(): void
    {
        $this->assertEquals('', sqlSecurityFilter('1 UNION SELECT * FROM users', 'no'));
    }

    public function testBlocksDropTable(): void
    {
        $this->assertEquals('', sqlSecurityFilter('DROP TABLE students', 'no'));
    }

    public function testBlocksDeleteStatement(): void
    {
        $this->assertEquals('', sqlSecurityFilter('DELETE FROM users', 'no'));
    }

    public function testBlocksInsertStatement(): void
    {
        $this->assertEquals('', sqlSecurityFilter('INSERT INTO users VALUES(1)', 'no'));
    }

    public function testBlocksTruncate(): void
    {
        $this->assertEquals('', sqlSecurityFilter('TRUNCATE TABLE users', 'no'));
    }

    public function testBlocksSleepInjection(): void
    {
        $this->assertEquals('', sqlSecurityFilter("1; sleep(5)", 'no'));
    }

    public function testBlocksSemicolon(): void
    {
        $this->assertEquals('', sqlSecurityFilter('value; DROP TABLE--', 'no'));
    }

    public function testBlocksDirectoryTraversal(): void
    {
        $this->assertEquals('', sqlSecurityFilter('../../../etc/passwd', 'no'));
    }

    public function testBlocksUrlEncodedTraversal(): void
    {
        $this->assertEquals('', sqlSecurityFilter('..%2f..%2fetc/passwd', 'no'));
    }

    public function testBlocksSqlComment(): void
    {
        $this->assertEquals('', sqlSecurityFilter("admin'--", 'no'));
    }

    public function testBlocksUrlEncodedUnion(): void
    {
        $this->assertEquals('', sqlSecurityFilter('1 union%20select%20*', 'no'));
    }

    public function testStripsHtmlTags(): void
    {
        $result = sqlSecurityFilter('<b>bold</b>', 'no');
        $this->assertStringNotContainsString('<b>', $result);
        $this->assertStringContainsString('bold', $result);
    }

    public function testEncodesHtmlEntities(): void
    {
        // After stripping tags, entities are encoded
        $result = sqlSecurityFilter('value with "quotes"', 'no');
        $this->assertStringContainsString('&quot;', $result);
    }

    public function testBlocksConcatFunction(): void
    {
        $this->assertEquals('', sqlSecurityFilter("concat(username, password)", 'no'));
    }

    public function testBlocksExtractFunction(): void
    {
        $this->assertEquals('', sqlSecurityFilter("extract value from table", 'no'));
    }

    public function testBlocksSkipGrantTables(): void
    {
        $this->assertEquals('', sqlSecurityFilter("skip-grant-tables", 'no'));
    }

    // Array handling
    public function testHandlesArrayInput(): void
    {
        $input = ['name' => 'John', 'city' => 'Boston'];
        $result = sqlSecurityFilter($input, 'no');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
    }

    public function testFiltersmaliciousArrayValues(): void
    {
        $input = ['safe' => 'hello', 'evil' => 'DROP TABLE users'];
        $result = sqlSecurityFilter($input, 'no');
        $this->assertIsArray($result);
        // The malicious entry should be removed
        $this->assertArrayNotHasKey('evil', $result);
    }

    public function testHandlesEmptyArray(): void
    {
        $result = sqlSecurityFilter([], 'no');
        $this->assertEquals([], $result);
    }

    public function testHandlesNestedArrays(): void
    {
        $input = ['outer' => ['inner' => 'safe value']];
        $result = sqlSecurityFilter($input, 'no');
        $this->assertIsArray($result);
    }

    // Object handling
    public function testHandlesObjectInput(): void
    {
        $input = new stdClass();
        $input->name = 'John';
        $input->city = 'Boston';
        $result = sqlSecurityFilter($input, 'no');
        $this->assertIsArray($result);
    }

    // Default case
    public function testReturnsEmptyForUnknownTypes(): void
    {
        $this->assertEquals('', sqlSecurityFilter(null, 'no'));
    }

    public function testCaseInsensitiveBlocking(): void
    {
        $this->assertEquals('', sqlSecurityFilter('UnIoN SeLeCt * FROM users', 'no'));
    }

    public function testSafeNumericString(): void
    {
        $result = sqlSecurityFilter('12345', 'no');
        $this->assertStringContainsString('12345', $result);
    }

    public function testSafeAlphanumericString(): void
    {
        $result = sqlSecurityFilter('Student2024', 'no');
        $this->assertStringContainsString('Student2024', $result);
    }
}
