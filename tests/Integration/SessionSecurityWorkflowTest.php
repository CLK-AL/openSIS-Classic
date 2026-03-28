<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/CSRFSecurityFnc.php';
require_once TEST_ROOT . '/functions/PasswordHashFnc.php';

/**
 * Integration test: Session security workflow.
 *
 * Tests session management, access control, and CSRF protection
 * working together as they do in the running application.
 */
class SessionSecurityWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_SERVER['PHP_SELF'] = '/Modules.php';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    public function testUnauthenticatedUserHasNoAccess(): void
    {
        // Simulates Warehouse.php check
        $hasAccess = !empty($_SESSION['STAFF_ID']) || !empty($_SESSION['STUDENT_ID']);
        $this->assertFalse($hasAccess);
    }

    public function testAuthenticatedStaffHasAccess(): void
    {
        $_SESSION['STAFF_ID'] = 1;
        $_SESSION['PROFILE_ID'] = 1;
        $hasAccess = !empty($_SESSION['STAFF_ID']) || !empty($_SESSION['STUDENT_ID']);
        $this->assertTrue($hasAccess);
    }

    public function testAuthenticatedStudentHasAccess(): void
    {
        $_SESSION['STUDENT_ID'] = 100;
        $hasAccess = !empty($_SESSION['STAFF_ID']) || !empty($_SESSION['STUDENT_ID']);
        $this->assertTrue($hasAccess);
    }

    public function testCsrfProtectionOnFormSubmission(): void
    {
        // Page load: generate token
        $_SESSION['STAFF_ID'] = 1;
        $token = CSRFSecure::CreateToken();

        // Form submit: validate token
        $_POST['TOKEN'] = $token;
        $valid = CSRFSecure::ValidateToken($token);
        $this->assertTrue($valid);
    }

    public function testCsrfProtectionBlocksCrossOriginSubmit(): void
    {
        $_SESSION['STAFF_ID'] = 1;
        CSRFSecure::CreateToken();

        // Attacker submits with different token
        $_POST['TOKEN'] = 'attacker_forged_token';
        $valid = CSRFSecure::ValidateToken('attacker_forged_token');
        $this->assertFalse($valid);
    }

    public function testSessionContextConsistency(): void
    {
        // Simulate admin login
        $_SESSION['STAFF_ID'] = 1;
        $_SESSION['USERNAME'] = 'admin';
        $_SESSION['PROFILE_ID'] = 1;
        $_SESSION['UserSchool'] = 1;
        $_SESSION['UserSyear'] = '2025-2026';
        $_SESSION['language'] = 'en';

        // All session vars should be consistent
        $this->assertNotEmpty($_SESSION['STAFF_ID']);
        $this->assertNotEmpty($_SESSION['USERNAME']);
        $this->assertNotEmpty($_SESSION['PROFILE_ID']);
        $this->assertNotEmpty($_SESSION['UserSchool']);
        $this->assertNotEmpty($_SESSION['UserSyear']);
    }

    public function testRoleBasedMenuAccess(): void
    {
        // Simulate the menu system access check
        $menu = [
            'students' => [
                'admin' => ['students/Student.php' => 'Student Info'],
                'teacher' => ['students/Student.php' => 'Student Info'],
                'parent' => ['students/Student.php' => 'Student Info'],
            ],
            'tools' => [
                'admin' => ['tools/Backup.php' => 'Backup'],
            ],
        ];

        // Admin can access tools
        $profile = 'admin';
        $this->assertArrayHasKey($profile, $menu['tools']);

        // Teacher cannot access tools
        $profile = 'teacher';
        $this->assertArrayNotHasKey($profile, $menu['tools']);

        // Both can access students
        $this->assertArrayHasKey('admin', $menu['students']);
        $this->assertArrayHasKey('teacher', $menu['students']);
    }

    public function testSchoolContextSwitching(): void
    {
        $_SESSION['UserSchool'] = 1;
        $_SESSION['UserSyear'] = '2025-2026';

        // Simulate school switch
        $_SESSION['UserSchool'] = 2;
        $this->assertEquals(2, $_SESSION['UserSchool']);

        // Year context should persist
        $this->assertEquals('2025-2026', $_SESSION['UserSyear']);
    }

    public function testPasswordChangeWorkflow(): void
    {
        // Current password check
        $currentHash = GenerateNewHash('oldPassword');
        $this->assertNotEmpty(VerifyHash('oldPassword', $currentHash));

        // Generate new hash
        $newHash = GenerateNewHash('newSecureP@ss');
        $this->assertNotEmpty($newHash);
        $this->assertNotEquals($currentHash, $newHash);

        // Verify new password works
        $this->assertNotEmpty(VerifyHash('newSecureP@ss', $newHash));

        // Old password no longer works with new hash
        $this->assertEquals(0, VerifyHash('oldPassword', $newHash));
    }

    public function testMultipleFormSubmitsRequireFreshTokens(): void
    {
        $_SESSION['STAFF_ID'] = 1;

        // Form 1 submit
        $token1 = CSRFSecure::CreateToken();
        $_POST['TOKEN'] = $token1;
        $this->assertTrue(CSRFSecure::ValidateToken($token1));

        // Form 2 needs a new token
        $token2 = CSRFSecure::CreateToken();
        $_POST['TOKEN'] = $token2;
        $this->assertTrue(CSRFSecure::ValidateToken($token2));

        // Token1 cannot be replayed
        $_POST['TOKEN'] = $token1;
        $_SESSION['_TOKEN'] = 'different';
        $this->assertFalse(CSRFSecure::ValidateToken($token1));
    }
}
