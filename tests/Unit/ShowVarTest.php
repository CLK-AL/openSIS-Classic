<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/ShowVarFnc.php';

class ShowVarTest extends TestCase
{
    public function testShowVarReturnsStringWhenRequested(): void
    {
        $result = ShowVar(['key' => 'value'], 'Y');
        $this->assertStringContainsString('key', $result);
        $this->assertStringContainsString('value', $result);
        $this->assertStringContainsString('<pre>', $result);
    }

    public function testShowVarWithoutPreTags(): void
    {
        $result = ShowVar('hello', 'Y', 'N');
        $this->assertStringContainsString('hello', $result);
        $this->assertStringNotContainsString('<pre>', $result);
    }

    public function testShowVarOutputsWhenNotStringMode(): void
    {
        ob_start();
        ShowVar('test output');
        $output = ob_get_clean();
        $this->assertStringContainsString('test output', $output);
    }

    public function testShowVarWithArray(): void
    {
        $result = ShowVar([1, 2, 3], 'Y');
        $this->assertStringContainsString('Array', $result);
    }
}
