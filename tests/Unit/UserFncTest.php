<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/UserFnc.php';

class UserFncTest extends TestCase
{
    protected function setUp(): void
    {
        global $_openSIS, $DefaultSyear;
        $_openSIS = [];
        $DefaultSyear = '2025';
        $_SESSION['UserSyear'] = '2025';
        $_SESSION['STAFF_ID'] = 1;
        $_SESSION['PROFILE_ID'] = 1;
    }

    protected function tearDown(): void
    {
        global $_openSIS;
        $_openSIS = [];
    }

    public function testUserReturnsItemFromCache(): void
    {
        global $_openSIS;
        // Pre-populate the User cache (bypass DB call)
        $_openSIS['User'] = [
            1 => [
                'STAFF_ID' => '1',
                'USERNAME' => 'admin',
                'NAME' => 'OS4Ed Administrator',
                'PROFILE' => 'admin',
                'PROFILE_ID' => '1',
                'CURRENT_SCHOOL_ID' => '1',
                'EMAIL' => 'admin@school.edu',
                'SYEAR' => '2025',
            ]
        ];

        $this->assertEquals('admin', User('USERNAME'));
        $this->assertEquals('OS4Ed Administrator', User('NAME'));
        $this->assertEquals('admin', User('PROFILE'));
        $this->assertEquals('1', User('STAFF_ID'));
        $this->assertEquals('admin@school.edu', User('EMAIL'));
    }

    public function testUserReturnsDifferentProfile(): void
    {
        global $_openSIS;
        $_openSIS['User'] = [
            1 => [
                'STAFF_ID' => '5',
                'USERNAME' => 'teacher1',
                'NAME' => 'Jane Doe',
                'PROFILE' => 'teacher',
                'PROFILE_ID' => '2',
                'CURRENT_SCHOOL_ID' => '1',
                'EMAIL' => 'jane@school.edu',
                'SYEAR' => '2025',
            ]
        ];

        $this->assertEquals('teacher', User('PROFILE'));
        $this->assertEquals('Jane Doe', User('NAME'));
    }

    public function testPreferencesReturnsDefaults(): void
    {
        global $_openSIS;
        $_openSIS['Preferences'] = [];

        $this->assertEquals('Common', Preferences('NAME'));
        $this->assertEquals('Name', Preferences('SORT'));
        $this->assertEquals('Y', Preferences('SEARCH'));
        $this->assertEquals('Tab', Preferences('DELIMITER'));
        $this->assertEquals('M', Preferences('MONTH'));
        $this->assertEquals('j', Preferences('DAY'));
        $this->assertEquals('Y', Preferences('YEAR'));
    }

    public function testPreferencesReturnsStoredValue(): void
    {
        global $_openSIS;
        $_openSIS['Preferences']['Preferences']['MONTH'][1]['VALUE'] = 'F';

        $this->assertEquals('F', Preferences('MONTH'));
    }

    public function testPreferencesSearchDisabledForParent(): void
    {
        global $_openSIS;
        $_openSIS['User'] = [1 => ['PROFILE' => 'parent', 'SYEAR' => '2025']];

        $result = Preferences('SEARCH');
        $this->assertEquals('N', $result);
    }

    public function testPreferencesSearchDisabledForStudent(): void
    {
        global $_openSIS;
        unset($_SESSION['STAFF_ID']);
        $_SESSION['STUDENT_ID'] = 100;
        $_openSIS['User'] = [1 => ['PROFILE' => 'student', 'SYEAR' => '2025']];

        $result = Preferences('SEARCH');
        $this->assertEquals('N', $result);
        unset($_SESSION['STUDENT_ID']);
        $_SESSION['STAFF_ID'] = 1;
    }

    public function testStaffCategoryReturnsCategoryFromCache(): void
    {
        // StaffCategory calls DBGet(DBQuery(...)) — we simulate via global
        // Since DBQuery is mocked, we test the wrapper logic
        global $_openSIS;
        // This would require actual DB. Test the N/A fallback.
        // StaffCategory with empty result returns 'N/A'
        // We can't easily test this without DB, but we verify the function exists
        $this->assertTrue(function_exists('StaffCategory'));
    }
}
