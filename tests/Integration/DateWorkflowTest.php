<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// MonthNwSwitchFnc.php already loaded in bootstrap

/**
 * Integration test: Date handling workflow.
 *
 * Tests month conversion, date formatting, and ProperTime working together
 * as they do across scheduling, attendance, and grades modules.
 */
class DateWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        chdir(TEST_ROOT);
        require_once TEST_ROOT . '/functions/PrepareDateFnc.php';
    }

    public function testMysqlDateMonthExtraction(): void
    {
        // MySQL date: 2025-09-15
        $date = '2025-09-15';
        $monthNum = substr($date, 5, 2); // '09'
        $monthChar = MonthNWSwitch($monthNum, 'tochar');
        $this->assertEquals('SEP', $monthChar);
    }

    public function testOracleDateMonthExtraction(): void
    {
        // Oracle date format: 15-SEP-25
        $date = '15-SEP-25';
        $monthChar = substr($date, 3, 3); // 'SEP'
        $monthNum = MonthNWSwitch($monthChar, 'tonum');
        $this->assertEquals('09', $monthNum);
    }

    public function testDateComponentParsing(): void
    {
        $date = '2025-03-28';
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        $day = substr($date, 8, 2);

        $this->assertEquals('2025', $year);
        $this->assertEquals('03', $month);
        $this->assertEquals('28', $day);
    }

    public function testOracleDateComponentParsing(): void
    {
        $date = '28-MAR-25';
        $day = substr($date, 0, 2);
        $month = substr($date, 3, 3);
        $year = substr($date, 7, 2);

        $this->assertEquals('28', $day);
        $this->assertEquals('MAR', $month);
        $this->assertEquals('25', $year);
    }

    public function testMonthRoundTripConversion(): void
    {
        // num → char → num should preserve value
        for ($i = 1; $i <= 12; $i++) {
            $num = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $char = MonthNWSwitch($num, 'tochar');
            $backToNum = MonthNWSwitch($char, 'tonum');
            $this->assertEquals($num, $backToNum, "Round-trip failed for month $i");
        }
    }

    public function testAllMonthConversions(): void
    {
        $expected = [
            '01' => 'JAN', '02' => 'FEB', '03' => 'MAR', '04' => 'APR',
            '05' => 'MAY', '06' => 'JUN', '07' => 'JUL', '08' => 'AUG',
            '09' => 'SEP', '10' => 'OCT', '11' => 'NOV', '12' => 'DEC',
        ];

        foreach ($expected as $num => $char) {
            $this->assertEquals($char, MonthNWSwitch($num, 'tochar'));
            $this->assertEquals($num, MonthNWSwitch($char, 'tonum'));
        }
    }

    public function testProperTimeFormatsCorrectly(): void
    {
        $this->assertEquals('8:30 AM', ProperTime('08:30:00'));
        $this->assertEquals('1:00 PM', ProperTime('13:00:00'));
        $this->assertEquals('12:00 AM', ProperTime('00:00:00'));
        $this->assertEquals('12:00 PM', ProperTime('12:00:00'));
    }

    public function testProperTimeWithDifferentFormats(): void
    {
        $this->assertEquals('3:45 PM', ProperTime('15:45'));
        $this->assertEquals('9:15 AM', ProperTime('09:15'));
    }

    public function testDateComparisonForAttendance(): void
    {
        // Simulate checking if a date falls within a marking period
        $startDate = '2025-09-01';
        $endDate = '2025-12-20';
        $checkDate = '2025-10-15';

        $this->assertTrue($checkDate >= $startDate && $checkDate <= $endDate);

        $outsideDate = '2026-01-10';
        $this->assertFalse($outsideDate >= $startDate && $outsideDate <= $endDate);
    }

    public function testSchoolYearDateRangeValidation(): void
    {
        $syearStart = '2025-08-15';
        $syearEnd = '2026-06-15';

        // September is within school year
        $sep = '2025-09-01';
        $this->assertTrue($sep >= $syearStart && $sep <= $syearEnd);

        // July is outside school year
        $jul = '2025-07-01';
        $this->assertFalse($jul >= $syearStart && $jul <= $syearEnd);
    }
}
