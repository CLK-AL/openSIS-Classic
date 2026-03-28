<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/SortFnc.php';

class SortFncTest extends TestCase
{
    public function testVerifyDateSortValid(): void
    {
        $this->assertTrue(VerifyDate_sort('Jan/15/2025'));
    }

    public function testVerifyDateSortInvalidNoSlash(): void
    {
        $this->assertFalse(VerifyDate_sort('2025-01-15'));
    }

    public function testVerifyDateSortInvalidDate(): void
    {
        $this->assertFalse(VerifyDate_sort('Feb/30/2025'));
    }

    public function testDateToTimestamp(): void
    {
        $dates = ['Jan/01/2025', 'Dec/25/2025'];
        $result = date_to_timestamp($dates);
        $this->assertCount(2, $result);
        $this->assertIsInt($result[0]);
        $this->assertLessThan($result[1], $result[0]);
    }

    public function testPointToNumber(): void
    {
        $points = ['85 / 100', '92 / 100', '<b>75</b> / 100'];
        $result = point_to_number($points);
        $this->assertEquals(['85', '92', '75'], $result);
    }

    public function testPercentToNumber(): void
    {
        $percents = ['85%', '92.5%', '<b>75%</b>'];
        $result = percent_to_number($percents);
        $this->assertEquals(['85', '92.5', '75'], $result);
    }

    public function testRangeToNumber(): void
    {
        $ranges = ['90 - 100', '80 - 89', '70 - 79'];
        $result = range_to_number($ranges);
        $this->assertEquals(['90', '80', '70'], $result);
    }

    public function testRankToNumber(): void
    {
        $ranks = ['1 out of 30', '5 out of 30', '15 out of 30'];
        $result = rank_to_number($ranks);
        $this->assertEquals(['1', '5', '15'], $result);
    }

    public function testPointToNumberEmpty(): void
    {
        $this->assertEquals([], point_to_number([]));
    }

    public function testPercentToNumberEmpty(): void
    {
        $this->assertEquals([], percent_to_number([]));
    }
}
