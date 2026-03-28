<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Validates menu structure integrity across all modules.
 */
class MenuStructureTest extends TestCase
{
    public function testAllModulesHaveMenuFiles(): void
    {
        $moduleDirs = glob(TEST_ROOT . '/modules/*', GLOB_ONLYDIR);
        foreach ($moduleDirs as $dir) {
            $module = basename($dir);
            if ($module === 'miscellaneous') continue; // no menu
            $this->assertFileExists("$dir/Menu.php", "Module '$module' missing Menu.php");
        }
    }

    public function testAllMenuFilesHaveAdminRole(): void
    {
        $menuFiles = glob(TEST_ROOT . '/modules/*/Menu.php');
        foreach ($menuFiles as $file) {
            $content = file_get_contents($file);
            $module = basename(dirname($file));
            $this->assertStringContainsString(
                "\$menu['$module']['admin']",
                $content,
                "Menu.php for '$module' missing admin role"
            );
        }
    }

    public function testMenuPagesReferenceOwnModule(): void
    {
        $menuFiles = glob(TEST_ROOT . '/modules/*/Menu.php');
        foreach ($menuFiles as $file) {
            $content = file_get_contents($file);
            $module = basename(dirname($file));
            preg_match_all("/'([a-z]+)\/[^']+\.php/", $content, $matches);
            foreach ($matches[1] as $ref) {
                // Pages should reference their own module or users (for teacher programs)
                $this->assertTrue(
                    $ref === $module || $ref === 'users',
                    "Menu.php for '$module' references foreign module '$ref'"
                );
            }
        }
    }

    public function testModuleCount(): void
    {
        $menuFiles = glob(TEST_ROOT . '/modules/*/Menu.php');
        $this->assertCount(11, $menuFiles, "Expected 11 modules with Menu.php");
    }

    public function testInventoryHasDonationsAndNeeds(): void
    {
        $content = file_get_contents(TEST_ROOT . '/modules/inventory/Menu.php');
        $this->assertStringContainsString('Donations', $content);
        $this->assertStringContainsString('Student Needs', $content);
        $this->assertStringContainsString('Field Trips', $content);
        $this->assertStringContainsString('Birthdays', $content);
        $this->assertStringContainsString('Finance', $content);
    }

    public function testLibraryHasCheckoutAndOverdue(): void
    {
        $content = file_get_contents(TEST_ROOT . '/modules/library/Menu.php');
        $this->assertStringContainsString('Checkout', $content);
        $this->assertStringContainsString('Overdue', $content);
        $this->assertStringContainsString('Categories', $content);
    }

    public function testToolsHasVCardICalTranslation(): void
    {
        $content = file_get_contents(TEST_ROOT . '/modules/tools/Menu.php');
        $this->assertStringContainsString('TranslationManager', $content);
        $this->assertStringContainsString('VCardExport', $content);
        $this->assertStringContainsString('ICalExport', $content);
    }
}
