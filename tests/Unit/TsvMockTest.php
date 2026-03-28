<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TsvMockTest extends TestCase
{
    public function testFromTsvParsesHeadersAndRows(): void
    {
        $tsv = "NAME\tAGE\tGRADE\nAlice\t15\t10\nBob\t16\t11\n";
        $rs = TsvResultSet::fromTsv($tsv);
        $this->assertEquals(2, $rs->count());

        $row1 = $rs->fetchRow();
        $this->assertEquals('Alice', $row1['NAME']);
        $this->assertEquals('15', $row1['AGE']);
        $this->assertEquals('10', $row1['GRADE']);

        $row2 = $rs->fetchRow();
        $this->assertEquals('Bob', $row2['NAME']);

        $this->assertNull($rs->fetchRow());
    }

    public function testFromTsvUpperCasesHeaders(): void
    {
        $rs = TsvResultSet::fromTsv("name\tCity\nJohn\tBoston\n");
        $row = $rs->fetchRow();
        $this->assertArrayHasKey('NAME', $row);
        $this->assertArrayHasKey('CITY', $row);
    }

    public function testFromArray(): void
    {
        $rs = TsvResultSet::fromArray([
            ['student_id' => '101', 'first_name' => 'Alice'],
            ['student_id' => '102', 'first_name' => 'Bob'],
        ]);
        $this->assertEquals(2, $rs->count());
        $row = $rs->fetchRow();
        $this->assertEquals('101', $row['STUDENT_ID']);
        $this->assertEquals('Alice', $row['FIRST_NAME']);
    }

    public function testEmpty(): void
    {
        $rs = TsvResultSet::empty();
        $this->assertEquals(0, $rs->count());
        $this->assertNull($rs->fetchRow());
    }

    public function testReset(): void
    {
        $rs = TsvResultSet::fromTsv("X\n1\n2\n");
        $rs->fetchRow();
        $rs->fetchRow();
        $this->assertNull($rs->fetchRow());

        $rs->reset();
        $this->assertEquals('1', $rs->fetchRow()['X']);
    }

    public function testDbFetchRowWorkWithTsvResultSet(): void
    {
        $rs = TsvResultSet::fromTsv("ID\tNAME\n1\tTest\n");
        $row = db_fetch_row($rs);
        $this->assertEquals('1', $row['ID']);
        $this->assertEquals('Test', $row['NAME']);
        $this->assertNull(db_fetch_row($rs));
    }

    public function testDBGetWithTsvResultSet(): void
    {
        $rs = TsvResultSet::fromTsv("STUDENT_ID\tFIRST_NAME\tLAST_NAME\n101\tAlice\tSmith\n102\tBob\tJones\n");
        $result = DBGet($rs);
        $this->assertCount(2, $result);
        $this->assertEquals('Alice', $result[1]['FIRST_NAME']);
        $this->assertEquals('Jones', $result[2]['LAST_NAME']);
    }

    public function testDBGetWithIndex(): void
    {
        $rs = TsvResultSet::fromTsv("COURSE_ID\tTITLE\n10\tMath\n20\tScience\n10\tMath Advanced\n");
        $result = DBGet($rs, [], ['COURSE_ID']);
        $this->assertArrayHasKey('10', $result);
        $this->assertArrayHasKey('20', $result);
        // Course 10 has 2 entries
        $this->assertEquals('Math', $result['10'][1]['TITLE']);
        $this->assertEquals('Math Advanced', $result['10'][2]['TITLE']);
    }

    public function testDBGetWithFunctions(): void
    {
        // Define a test formatting function
        if (!function_exists('_test_upper')) {
            function _test_upper($value, $key) { return strtoupper($value); }
        }

        $rs = TsvResultSet::fromTsv("NAME\tCITY\nalice\tboston\n");
        $result = DBGet($rs, ['NAME' => '_test_upper']);
        $this->assertEquals('ALICE', $result[1]['NAME']);
        $this->assertEquals('boston', $result[1]['CITY']); // CITY not transformed
    }

    public function testDBGetEmptyResult(): void
    {
        $rs = TsvResultSet::empty();
        $result = DBGet($rs);
        $this->assertEquals([], $result);
    }
}
