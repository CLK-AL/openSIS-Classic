<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// MonthNwSwitchFnc.php already loaded in bootstrap

class MonthNwSwitchTest extends TestCase
{
    /**
     * @dataProvider monthNumToCharProvider
     */
    public function testNumToChar(string $num, string $expected): void
    {
        $this->assertEquals($expected, MonthNWSwitch($num, 'tochar'));
    }

    public static function monthNumToCharProvider(): array
    {
        return [
            ['01', 'JAN'], ['02', 'FEB'], ['03', 'MAR'], ['04', 'APR'],
            ['05', 'MAY'], ['06', 'JUN'], ['07', 'JUL'], ['08', 'AUG'],
            ['09', 'SEP'], ['10', 'OCT'], ['11', 'NOV'], ['12', 'DEC'],
        ];
    }

    /**
     * @dataProvider monthCharToNumProvider
     */
    public function testCharToNum(string $char, string $expected): void
    {
        $this->assertEquals($expected, MonthNWSwitch($char, 'tonum'));
    }

    public static function monthCharToNumProvider(): array
    {
        return [
            ['JAN', '01'], ['FEB', '02'], ['MAR', '03'], ['APR', '04'],
            ['MAY', '05'], ['JUN', '06'], ['JUL', '07'], ['AUG', '08'],
            ['SEP', '09'], ['OCT', '10'], ['NOV', '11'], ['DEC', '12'],
        ];
    }

    public function testSingleDigitMonthPadded(): void
    {
        // Single digit '1' should be padded to '01' then converted
        $this->assertEquals('JAN', MonthNWSwitch('1', 'tochar'));
    }

    public function testCaseInsensitiveCharToNum(): void
    {
        $this->assertEquals('01', MonthNWSwitch('jan', 'tonum'));
        $this->assertEquals('06', MonthNWSwitch('Jun', 'tonum'));
    }

    public function testAlreadyNumReturnsNum(): void
    {
        // If input is already numeric (short), tonum returns as-is
        $this->assertEquals('05', MonthNWSwitch('05', 'tonum'));
    }

    public function testAlreadyCharReturnsChar(): void
    {
        // If input is already 3 chars, tochar returns as-is
        $this->assertEquals('JAN', MonthNWSwitch('JAN', 'tochar'));
    }

    public function testBothDirectionRoundTrips(): void
    {
        // 'both' goes num2char then char2num
        $this->assertEquals('05', MonthNWSwitch('05', 'both'));
        $this->assertEquals('12', MonthNWSwitch('12', 'both'));
    }

    public function testDecemberZeroEdgeCase(): void
    {
        // '00' maps to DEC in num2char
        $this->assertEquals('DEC', MonthNWSwitch('00', 'tochar'));
    }
}
