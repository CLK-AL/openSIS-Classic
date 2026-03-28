<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// PragRepFnc already loaded in bootstrap

require_once TEST_ROOT . '/functions/DrawTabFnc.php';

class DrawTabTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_REQUEST['_openSIS_PDF']);
    }

    public function testDrawTabReturnsActiveListItem(): void
    {
        $result = DrawTab('Students');
        $this->assertStringContainsString("<li class='active'", $result);
        $this->assertStringContainsString('Students', $result);
        $this->assertStringContainsString('</li>', $result);
    }

    public function testDrawTabWithLink(): void
    {
        $result = DrawTab('Schedule', 'Modules.php?modname=scheduling');
        $this->assertStringContainsString('HREF=', $result);
        $this->assertStringContainsString('scheduling', $result);
    }

    public function testDrawTabWithoutLink(): void
    {
        $result = DrawTab('Info');
        $this->assertStringContainsString('javascript:void(0)', $result);
    }

    public function testDrawTabReplacesSpaces(): void
    {
        $result = DrawTab('My Title');
        $this->assertStringContainsString('&nbsp;', $result);
    }

    public function testDrawInactiveTabNoActiveClass(): void
    {
        $result = DrawinactiveTab('Inactive');
        $this->assertStringContainsString('<li id=', $result);
        $this->assertStringNotContainsString("class='active'", $result);
    }

    public function testDrawInactiveTabWithLink(): void
    {
        $result = DrawinactiveTab('Other', 'Modules.php?modname=other');
        $this->assertStringContainsString('HREF=', $result);
    }

    public function testDrawRoundedRectReturnsTable(): void
    {
        $result = DrawRoundedRect('Title');
        $this->assertStringContainsString('<table', $result);
        $this->assertStringContainsString('Title', $result);
        $this->assertStringContainsString('</table>', $result);
    }

    public function testDrawTabPdfMode(): void
    {
        $_REQUEST['_openSIS_PDF'] = true;
        $result = DrawTab('PDF Tab');
        $this->assertStringContainsString('javascript:void(0)', $result);
    }
}
