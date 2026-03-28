<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/DeCodedsFnc.php';

class DeCodedsTest extends TestCase
{
    protected function setUp(): void
    {
        global $_openSIS;
        $_openSIS = [];
    }

    public function testDeCodedsReturnsDecodedValueFromCache(): void
    {
        global $_openSIS;
        // Pre-populate the cache (simulates what DB lookup would produce)
        $_openSIS['DeCodeds']['5'] = ['M' => 'Male', 'F' => 'Female'];

        $result = DeCodeds('M', 'CUSTOM_5');
        $this->assertEquals('Male', $result);
    }

    public function testDeCodedsReturnsFemale(): void
    {
        global $_openSIS;
        $_openSIS['DeCodeds']['5'] = ['M' => 'Male', 'F' => 'Female'];

        $result = DeCodeds('F', 'CUSTOM_5');
        $this->assertEquals('Female', $result);
    }

    public function testDeCodedsReturnsRedForUnknownCode(): void
    {
        global $_openSIS;
        $_openSIS['DeCodeds']['5'] = ['M' => 'Male', 'F' => 'Female'];

        $result = DeCodeds('X', 'CUSTOM_5');
        $this->assertStringContainsString('red', $result);
        $this->assertStringContainsString('X', $result);
    }

    public function testDeCodedsReturnsEmptyForEmptyValue(): void
    {
        global $_openSIS;
        $_openSIS['DeCodeds']['5'] = ['M' => 'Male'];

        $this->assertEquals('', DeCodeds('', 'CUSTOM_5'));
    }

    public function testCleanParamMod(): void
    {
        // cleanParamMod decodes a custom encoding: character code * 3, separated by 'P'
        // 'A' = chr(65), so 65*3 = 195 → "195"
        $encoded = (65 * 3) . 'P' . (66 * 3); // "195P198" → "AB"
        $this->assertEquals('AB', cleanParamMod($encoded));
    }

    public function testCleanParamModHello(): void
    {
        // H=72, e=101, l=108, l=108, o=111
        $encoded = implode('P', [72*3, 101*3, 108*3, 108*3, 111*3]);
        $this->assertEquals('Hello', cleanParamMod($encoded));
    }
}
