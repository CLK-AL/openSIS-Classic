<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/ConfigFnc.php';

class ConfigFncTest extends TestCase
{
    protected function setUp(): void
    {
        global $_openSIS, $openSISTitle, $DefaultSyear;
        $_openSIS = [];
        $openSISTitle = 'openSIS Student Information System';
        $DefaultSyear = '2025';
    }

    public function testConfigReturnsTitle(): void
    {
        $this->assertEquals('openSIS Student Information System', Config('TITLE'));
    }

    public function testConfigReturnsSyear(): void
    {
        $this->assertEquals('2025', Config('SYEAR'));
    }

    public function testConfigReturnsNullForUnknown(): void
    {
        $this->assertNull(Config('NONEXISTENT'));
    }

    public function testConfigCachesResult(): void
    {
        global $_openSIS;
        Config('TITLE');
        $this->assertArrayHasKey('Config', $_openSIS);
        $this->assertEquals('openSIS Student Information System', $_openSIS['Config'][1]['TITLE']);
    }
}
