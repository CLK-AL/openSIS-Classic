<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/DbDateFnc.php';

class DbDateTest extends TestCase
{
    public function testDBDateOracleFormat(): void
    {
        $result = DBDate('oracle');
        $this->assertMatchesRegularExpression('/^\d{2}-[A-Z]{3}-\d{2}$/', $result);
    }

    public function testDBDateMysqlFormat(): void
    {
        $result = DBDate('mysql');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result);
    }

    public function testDBDatePostgresFormat(): void
    {
        $result = DBDate('postgres');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result);
    }

    public function testDBDateDefaultFormat(): void
    {
        $result = DBDate('unknown');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result);
    }

    public function testDBDateDefaultParamIsOracle(): void
    {
        $result = DBDate();
        $this->assertMatchesRegularExpression('/^\d{2}-[A-Z]{3}-\d{2}$/', $result);
    }

    // DaySname tests
    public function testDaySnameForwardMapping(): void
    {
        $this->assertEquals('M', DaySname('Monday'));
        $this->assertEquals('T', DaySname('Tuesday'));
        $this->assertEquals('W', DaySname('Wednesday'));
        $this->assertEquals('H', DaySname('Thursday'));
        $this->assertEquals('F', DaySname('Friday'));
        $this->assertEquals('S', DaySname('Saturday'));
        $this->assertEquals('U', DaySname('Sunday'));
    }

    public function testDaySnameReverseMapping(): void
    {
        $this->assertEquals('Monday', DaySname('M', '2'));
        $this->assertEquals('Thursday', DaySname('H', '2'));
        $this->assertEquals('Sunday', DaySname('U', '2'));
    }

    public function testDaySnameInvalidDay(): void
    {
        $this->assertEquals('', DaySname('Holiday'));
    }

    // DaySnameMod tests
    public function testDaySnameModMultiDayString(): void
    {
        $result = DaySnameMod('MTW');
        $this->assertStringContainsString('Monday', $result);
        $this->assertStringContainsString('Tuesday', $result);
        $this->assertStringContainsString('Wednesday', $result);
    }

    public function testDaySnameModSingleCode(): void
    {
        $result = DaySnameMod('F', '2');
        $this->assertEquals('Friday', $result);
    }

    // MonthFormatter tests
    public function testMonthFormatterForward(): void
    {
        $this->assertEquals('01', MonthFormatter('JAN'));
        $this->assertEquals('06', MonthFormatter('JUN'));
        $this->assertEquals('12', MonthFormatter('DEC'));
    }

    public function testMonthFormatterReverse(): void
    {
        $this->assertEquals('JAN', MonthFormatter('01', '2'));
        $this->assertEquals('JUN', MonthFormatter('06', '2'));
        $this->assertEquals('DEC', MonthFormatter('12', '2'));
    }

    public function testMonthFormatterInvalid(): void
    {
        $this->assertEquals('', MonthFormatter('XXX'));
    }
}
