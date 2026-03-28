<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/PasswordHashFnc.php';
require_once TEST_ROOT . '/functions/CSRFSecurityFnc.php';

/**
 * Integration test: Authentication workflow.
 *
 * Tests the login flow components together:
 * CSRF token → password verification → session setup.
 * (No actual DB; simulates the authentication logic.)
 */
class AuthenticationWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    public function testFullLoginWorkflow(): void
    {
        // Step 1: Page generates a CSRF token
        $token = CSRFSecure::CreateToken();
        $this->assertNotEmpty($token);

        // Step 2: User submits form with token + credentials
        $_POST['TOKEN'] = $token;
        $_POST['USERNAME'] = 'admin';
        $_POST['PASSWORD'] = 'secret123';

        // Step 3: Validate CSRF token
        $csrfValid = CSRFSecure::ValidateToken($token);
        $this->assertTrue($csrfValid);

        // Step 4: Verify password against stored hash
        $storedHash = GenerateNewHash('secret123');
        $passwordValid = VerifyHash($_POST['PASSWORD'], $storedHash);
        $this->assertNotEmpty($passwordValid);

        // Step 5: Set session variables (simulating index.php logic)
        $_SESSION['STAFF_ID'] = 1;
        $_SESSION['USERNAME'] = 'admin';
        $_SESSION['PROFILE_ID'] = 1;
        $_SESSION['UserSchool'] = 1;
        $_SESSION['UserSyear'] = '2025-2026';

        $this->assertEquals(1, $_SESSION['STAFF_ID']);
        $this->assertEquals('admin', $_SESSION['USERNAME']);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $token = CSRFSecure::CreateToken();
        $_POST['TOKEN'] = $token;

        $csrfValid = CSRFSecure::ValidateToken($token);
        $this->assertTrue($csrfValid);

        $storedHash = GenerateNewHash('correct_password');
        $passwordValid = VerifyHash('wrong_password', $storedHash);
        $this->assertEquals(0, $passwordValid);

        // Session should NOT be populated
        $this->assertArrayNotHasKey('STAFF_ID', $_SESSION);
    }

    public function testLoginFailsWithInvalidCsrf(): void
    {
        CSRFSecure::CreateToken();
        $_POST['TOKEN'] = 'forged_token';

        $csrfValid = CSRFSecure::ValidateToken('forged_token');
        $this->assertFalse($csrfValid);

        // Should not proceed to password check
        $this->assertArrayNotHasKey('STAFF_ID', $_SESSION);
    }

    public function testLoginFailsWithExpiredCsrf(): void
    {
        $token = CSRFSecure::CreateToken();
        $_POST['TOKEN'] = $token;

        // Simulate expired token
        $_SESSION['_TOKEN_EXPIRY'] = time() - 120;

        $csrfValid = CSRFSecure::ValidateToken($token);
        $this->assertFalse($csrfValid);
    }

    public function testCsrfTokenNotReusableAcrossLoginAttempts(): void
    {
        $token = CSRFSecure::CreateToken();
        $_POST['TOKEN'] = $token;

        // First attempt succeeds
        $this->assertTrue(CSRFSecure::ValidateToken($token));

        // Attacker replays the same token
        $_POST['TOKEN'] = $token;
        $this->assertFalse(CSRFSecure::ValidateToken($token));
    }

    public function testLogoutClearsSession(): void
    {
        // Setup logged-in state
        $_SESSION['STAFF_ID'] = 1;
        $_SESSION['USERNAME'] = 'admin';
        $_SESSION['PROFILE_ID'] = 1;
        $_SESSION['UserSchool'] = 1;
        $_SESSION['language'] = 'en';

        // Simulate logout (index.php?modfunc=logout)
        $keysToRemove = ['STAFF_ID', 'USERNAME', 'PROFILE_ID', 'UserSchool'];
        foreach ($keysToRemove as $key) {
            unset($_SESSION[$key]);
        }

        $this->assertArrayNotHasKey('STAFF_ID', $_SESSION);
        $this->assertArrayNotHasKey('USERNAME', $_SESSION);
    }

    public function testSessionFixationPrevention(): void
    {
        // Before login, record session ID
        $oldSessionId = session_id();

        // Simulate login - regenerate session ID
        session_regenerate_id(true);
        $newSessionId = session_id();

        // Session ID should change after login to prevent fixation
        $this->assertNotEquals($oldSessionId, $newSessionId);
    }

    public function testMultipleLoginAttemptsWithFreshTokens(): void
    {
        // Attempt 1: wrong password
        $token1 = CSRFSecure::CreateToken();
        $_POST['TOKEN'] = $token1;
        $this->assertTrue(CSRFSecure::ValidateToken($token1));

        $hash = GenerateNewHash('correct');
        $this->assertEquals(0, VerifyHash('wrong', $hash));

        // Attempt 2: correct password with new token
        $token2 = CSRFSecure::CreateToken();
        $_POST['TOKEN'] = $token2;
        $this->assertTrue(CSRFSecure::ValidateToken($token2));
        $this->assertNotEmpty(VerifyHash('correct', $hash));
    }
}
