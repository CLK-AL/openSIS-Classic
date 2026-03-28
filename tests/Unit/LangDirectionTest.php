<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/langFnc.php';

class LangDirectionTest extends TestCase
{
    protected function setUp(): void
    {
        // Ensure supportedLanguages can be loaded
        // The function uses include_once relative to CWD
        chdir(TEST_ROOT);
    }

    protected function tearDown(): void
    {
        unset($_SESSION['language']);
    }

    public function testReturnsLtrForEnglish(): void
    {
        $_SESSION['language'] = 'en';
        $this->assertEquals('ltr', langDirection());
    }

    public function testReturnsLtrForFrench(): void
    {
        $_SESSION['language'] = 'fr';
        $this->assertEquals('ltr', langDirection());
    }

    public function testReturnsLtrForSpanish(): void
    {
        $_SESSION['language'] = 'es';
        $this->assertEquals('ltr', langDirection());
    }

    public function testReturnsRtlForArabic(): void
    {
        $_SESSION['language'] = 'ar';
        $this->assertEquals('rtl', langDirection());
    }

    public function testReturnsRtlForHebrew(): void
    {
        $_SESSION['language'] = 'he';
        $this->assertEquals('rtl', langDirection());
    }

    public function testReturnsLtrWhenNoSessionLanguage(): void
    {
        unset($_SESSION['language']);
        $this->assertEquals('ltr', langDirection());
    }

    public function testReturnsLtrForUnsupportedLanguage(): void
    {
        $_SESSION['language'] = 'xx';
        $this->assertEquals('ltr', langDirection());
    }

    public function testReturnsLtrForEmptyLanguage(): void
    {
        $_SESSION['language'] = '';
        $this->assertEquals('ltr', langDirection());
    }
}
