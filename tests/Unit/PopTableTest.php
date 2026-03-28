<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/PopTableFnc.php';
require_once TEST_ROOT . '/functions/DrawTabFnc.php';

class PopTableTest extends TestCase
{
    protected function setUp(): void
    {
        global $_openSIS;
        $_openSIS = [];
        unset($_REQUEST['_openSIS_PDF']);
    }

    public function testPopTableHeader(): void
    {
        ob_start();
        PopTable('header', 'Test Title');
        $output = ob_get_clean();
        $this->assertStringContainsString('class="panel"', $output);
        $this->assertStringContainsString('tabbable', $output);
        $this->assertStringContainsString('Test', $output);
        $this->assertStringContainsString('panel-body', $output);
    }

    public function testPopTableFooter(): void
    {
        ob_start();
        PopTable('footer');
        $output = ob_get_clean();
        $this->assertStringContainsString('</div>', $output);
    }

    public function testPopTableFooterWithTitle(): void
    {
        ob_start();
        PopTable('footer', 'Save Button');
        $output = ob_get_clean();
        $this->assertStringContainsString('panel-footer', $output);
        $this->assertStringContainsString('Save Button', $output);
    }

    public function testPopTableCustomDivAttributes(): void
    {
        ob_start();
        PopTable('header', 'Custom', 'class="custom-panel"');
        $output = ob_get_clean();
        $this->assertStringContainsString('class="custom-panel"', $output);
    }
}
