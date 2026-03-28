<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/langFnc.php';

/**
 * Integration test: Language selection → session storage → RTL direction flow.
 *
 * Simulates the full language workflow as it happens during login and page rendering.
 */
class LanguageWorkflowTest extends TestCase
{
    private array $savedSession;
    private array $savedRequest;
    private array $savedCookie;

    protected function setUp(): void
    {
        chdir(TEST_ROOT);
        $this->savedSession = $_SESSION ?? [];
        $this->savedRequest = $_REQUEST ?? [];
        $this->savedCookie = $_COOKIE ?? [];
        $_SESSION = [];
        $_REQUEST = [];
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->savedSession;
        $_REQUEST = $this->savedRequest;
        $_COOKIE = $this->savedCookie;
    }

    public function testSupportedLanguagesFileLoads(): void
    {
        include TEST_ROOT . '/lang/supportedLanguages.php';
        $this->assertIsArray($supportedLanguages);
        $this->assertArrayHasKey('en', $supportedLanguages);
        $this->assertArrayHasKey('ar', $supportedLanguages);
        $this->assertArrayHasKey('fr', $supportedLanguages);
        $this->assertArrayHasKey('es', $supportedLanguages);
    }

    public function testSupportedLanguagesHaveRequiredKeys(): void
    {
        include TEST_ROOT . '/lang/supportedLanguages.php';
        foreach ($supportedLanguages as $code => $lang) {
            $this->assertArrayHasKey('name', $lang, "Language $code missing 'name'");
            $this->assertArrayHasKey('direction', $lang, "Language $code missing 'direction'");
            $this->assertContains($lang['direction'], ['ltr', 'rtl'], "Language $code has invalid direction");
        }
    }

    public function testLanguageSelectionFromRequestSetsSession(): void
    {
        // Simulate: user selects Arabic from login dropdown
        $_REQUEST['language'] = 'ar';
        $_REQUEST['modfunc'] = '';

        include TEST_ROOT . '/lang/supportedLanguages.php';

        // Replicate language.php logic
        if (isset($_REQUEST['language']) && in_array($_REQUEST['language'], array_keys($supportedLanguages))) {
            $langCode = $_REQUEST['language'];
        }
        $_SESSION['language'] = $langCode;

        $this->assertEquals('ar', $_SESSION['language']);
        $this->assertEquals('rtl', langDirection());
    }

    public function testLanguageFallbackToCookie(): void
    {
        // No REQUEST, but cookie has preference
        $_COOKIE['remember_me_lang'] = 'fr';

        include TEST_ROOT . '/lang/supportedLanguages.php';

        $langCode = null;
        if (isset($_REQUEST['language']) && in_array($_REQUEST['language'], array_keys($supportedLanguages))) {
            $langCode = $_REQUEST['language'];
        } elseif (isset($_COOKIE['remember_me_lang']) && in_array($_COOKIE['remember_me_lang'], array_keys($supportedLanguages))) {
            $langCode = $_COOKIE['remember_me_lang'];
        }

        if (!isset($langCode)) {
            $langCode = 'en';
        }

        $_SESSION['language'] = $langCode;
        $this->assertEquals('fr', $_SESSION['language']);
        $this->assertEquals('ltr', langDirection());
    }

    public function testLanguageFallbackToEnglishDefault(): void
    {
        // No REQUEST, no cookie
        include TEST_ROOT . '/lang/supportedLanguages.php';

        $langCode = null;
        if (isset($_REQUEST['language']) && in_array($_REQUEST['language'], array_keys($supportedLanguages))) {
            $langCode = $_REQUEST['language'];
        } elseif (isset($_COOKIE['remember_me_lang']) && in_array($_COOKIE['remember_me_lang'], array_keys($supportedLanguages))) {
            $langCode = $_COOKIE['remember_me_lang'];
        }

        if (!isset($langCode)) {
            $langCode = 'en';
        }

        $_SESSION['language'] = $langCode;
        $this->assertEquals('en', $_SESSION['language']);
        $this->assertEquals('ltr', langDirection());
    }

    public function testInvalidLanguageRequestFallsBackToDefault(): void
    {
        $_REQUEST['language'] = 'xx_INVALID';

        include TEST_ROOT . '/lang/supportedLanguages.php';

        $langCode = null;
        if (isset($_REQUEST['language']) && in_array($_REQUEST['language'], array_keys($supportedLanguages))) {
            $langCode = $_REQUEST['language'];
        }

        if (!isset($langCode)) {
            $langCode = 'en';
        }

        $_SESSION['language'] = $langCode;
        $this->assertEquals('en', $_SESSION['language']);
    }

    public function testInvalidCookieLangIgnored(): void
    {
        $_COOKIE['remember_me_lang'] = 'MALICIOUS_CODE';

        include TEST_ROOT . '/lang/supportedLanguages.php';

        $langCode = null;
        if (isset($_COOKIE['remember_me_lang']) && in_array($_COOKIE['remember_me_lang'], array_keys($supportedLanguages))) {
            $langCode = $_COOKIE['remember_me_lang'];
        }

        if (!isset($langCode)) {
            $langCode = 'en';
        }

        $_SESSION['language'] = $langCode;
        $this->assertEquals('en', $_SESSION['language']);
    }

    public function testRtlDirectionDeterminesHtmlAttribute(): void
    {
        $_SESSION['language'] = 'ar';
        $dir = (langDirection() == 'rtl') ? 'dir="rtl"' : 'dir="ltr"';
        $this->assertEquals('dir="rtl"', $dir);
    }

    public function testLtrDirectionDeterminesHtmlAttribute(): void
    {
        $_SESSION['language'] = 'en';
        $dir = (langDirection() == 'rtl') ? 'dir="rtl"' : 'dir="ltr"';
        $this->assertEquals('dir="ltr"', $dir);
    }

    public function testAllLanguageFilesExist(): void
    {
        include TEST_ROOT . '/lang/supportedLanguages.php';
        foreach (array_keys($supportedLanguages) as $code) {
            $file = TEST_ROOT . "/lang/lang_{$code}.php";
            $this->assertFileExists($file, "Missing language file for '$code'");
        }
    }
}
