<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/PercentFnc.php';

class PercentTest extends TestCase
{
    public function testBasicPercentage(): void
    {
        $this->assertEquals('85%', Percent(0.85));
    }

    public function testZeroPercent(): void
    {
        $this->assertEquals('0%', Percent(0));
    }

    public function testHundredPercent(): void
    {
        $this->assertEquals('100%', Percent(1));
    }

    public function testOverHundredPercent(): void
    {
        $this->assertEquals('150%', Percent(1.5));
    }

    public function testDecimalPrecision(): void
    {
        $this->assertEquals('85.71%', Percent(0.857142));
    }

    public function testCustomDecimals(): void
    {
        $this->assertEquals('85.714%', Percent(0.857142, 3));
    }

    public function testZeroDecimals(): void
    {
        $this->assertEquals('86%', Percent(0.857, 0));
    }

    public function testNegativePercent(): void
    {
        $this->assertEquals('-50%', Percent(-0.5));
    }

    public function testSmallFraction(): void
    {
        $this->assertEquals('0.5%', Percent(0.005));
    }

    public function testVerySmallValue(): void
    {
        $this->assertEquals('0.01%', Percent(0.0001));
    }
}
