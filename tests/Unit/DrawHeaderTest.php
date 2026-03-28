<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/DrawHeaderFnc.php';

class DrawHeaderTest extends TestCase
{
    protected function setUp(): void
    {
        global $_openSIS;
        $_openSIS = [];
        unset($_REQUEST['_openSIS_PDF']);
    }

    public function testDrawHeaderWithLeft(): void
    {
        ob_start();
        DrawHeader('Student Info');
        $output = ob_get_clean();
        $this->assertStringContainsString('panel-heading', $output);
        $this->assertStringContainsString('Student Info', $output);
        $this->assertStringContainsString('panel-title', $output);
    }

    public function testDrawHeaderWithRight(): void
    {
        ob_start();
        DrawHeader('', 'Button HTML');
        $output = ob_get_clean();
        $this->assertStringContainsString('heading-elements', $output);
        $this->assertStringContainsString('Button HTML', $output);
    }

    public function testDrawHeaderWithCenter(): void
    {
        ob_start();
        DrawHeader('', '', 'Center Content');
        $output = ob_get_clean();
        $this->assertStringContainsString('Center Content', $output);
    }

    public function testDrawHeaderEmpty(): void
    {
        ob_start();
        DrawHeader();
        $output = ob_get_clean();
        $this->assertStringNotContainsString('panel-heading', $output);
    }

    public function testDrawHeaderWithAll(): void
    {
        ob_start();
        DrawHeader('Left', 'Right', 'Center');
        $output = ob_get_clean();
        $this->assertStringContainsString('Left', $output);
        $this->assertStringContainsString('Right', $output);
        $this->assertStringContainsString('Center', $output);
    }
}
