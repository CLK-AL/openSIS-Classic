<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Smoke test: verify every page referenced in Menu.php files exists as a file.
 * This ensures no broken menu links and provides PHPUnit coverage for all
 * module pages (complementing Playwright E2E tests).
 */
class ModulePageExistsTest extends TestCase
{
    #[DataProvider('allMenuPagesProvider')]
    public function testModulePageExists(string $module, string $pagePath): void
    {
        // Strip query strings for file existence check
        $filePath = preg_replace('/[?&].*$/', '', $pagePath);
        $fullPath = TEST_ROOT . '/modules/' . $filePath;
        $this->assertFileExists($fullPath, "Menu references non-existent page: modules/$filePath");
    }

    #[DataProvider('allMenuPagesProvider')]
    public function testModulePageHasNoSyntaxErrors(string $module, string $pagePath): void
    {
        $filePath = preg_replace('/[?&].*$/', '', $pagePath);
        $fullPath = TEST_ROOT . '/modules/' . $filePath;
        if (!file_exists($fullPath)) {
            $this->markTestSkipped("File not found: $fullPath");
        }
        $output = [];
        $exitCode = 0;
        exec("php -l " . escapeshellarg($fullPath) . " 2>&1", $output, $exitCode);
        $this->assertEquals(0, $exitCode, "Syntax error in modules/$filePath: " . implode("\n", $output));
    }

    public static function allMenuPagesProvider(): array
    {
        $pages = [];
        $menuFiles = glob(TEST_ROOT . '/modules/*/Menu.php');
        foreach ($menuFiles as $menuFile) {
            $module = basename(dirname($menuFile));
            $content = file_get_contents($menuFile);
            // Match 'module/Page.php' or 'module/Page.php?params'
            preg_match_all("/'" . preg_quote($module, '/') . "\/([^']+\.php[^']*)'/", $content, $matches);
            foreach ($matches[0] as $match) {
                $path = trim($match, "'");
                // Deduplicate
                $key = "$module|$path";
                if (!isset($pages[$key])) {
                    $pages[$key] = [$module, $path];
                }
            }
        }
        return $pages;
    }
}
