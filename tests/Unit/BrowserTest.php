<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/BrowserFnc.php';

class BrowserTest extends TestCase
{
    public function testDetectsFirefox(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0';
        $this->assertEquals('Firefox', browser());
    }

    public function testDetectsNetscape(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux) Gecko/20060728 Netscape/8.1';
        $this->assertEquals('Netscape', browser());
    }

    public function testDetectsMozilla(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) Gecko/20100101';
        $this->assertEquals('Mozilla', browser());
    }

    public function testDetectsIE(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)';
        $this->assertEquals('IE', browser());
    }

    public function testDetectsOpera(): void
    {
        // Opera with MSIE but without Gecko - code checks MSIE first, then Opera inside
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows) Opera 7.0';
        $this->assertEquals('Opera', browser());
    }

    public function testDetectsOtherBrowsers(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'curl/7.68.0';
        $this->assertEquals('Others browsers', browser());
    }
}
