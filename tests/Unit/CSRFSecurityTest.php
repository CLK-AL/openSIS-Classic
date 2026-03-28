<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/CSRFSecurityFnc.php';

class CSRFSecurityTest extends TestCase
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

    public function testCreateTokenReturnsHexString(): void
    {
        $token = CSRFSecure::CreateToken();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function testCreateTokenStoresInSession(): void
    {
        $token = CSRFSecure::CreateToken();
        $this->assertEquals($token, $_SESSION['_TOKEN']);
        $this->assertArrayHasKey('_TOKEN_EXPIRY', $_SESSION);
    }

    public function testCreateTokenSetsExpiryInFuture(): void
    {
        CSRFSecure::CreateToken();
        $this->assertGreaterThan(time(), $_SESSION['_TOKEN_EXPIRY']);
    }

    public function testCreateTokenFieldOutputsHiddenInput(): void
    {
        ob_start();
        CSRFSecure::CreateTokenField();
        $output = ob_get_clean();

        $this->assertStringContainsString('<input', $output);
        $this->assertStringContainsString("type='hidden'", $output);
        $this->assertStringContainsString("name='TOKEN'", $output);
        $this->assertStringContainsString($_SESSION['_TOKEN'], $output);
    }

    public function testValidateTokenReturnsTrueForValidToken(): void
    {
        $token = CSRFSecure::CreateToken();
        $_POST['TOKEN'] = $token;

        $result = CSRFSecure::ValidateToken($token);
        $this->assertTrue($result);
    }

    public function testValidateTokenDestroysTokenAfterUse(): void
    {
        $token = CSRFSecure::CreateToken();
        $_POST['TOKEN'] = $token;

        CSRFSecure::ValidateToken($token);
        $this->assertArrayNotHasKey('_TOKEN', $_SESSION);
        $this->assertArrayNotHasKey('_TOKEN_EXPIRY', $_SESSION);
    }

    public function testValidateTokenReturnsFalseForWrongToken(): void
    {
        CSRFSecure::CreateToken();
        $_POST['TOKEN'] = 'wrong_token';

        $result = CSRFSecure::ValidateToken('wrong_token');
        $this->assertFalse($result);
    }

    public function testValidateTokenReturnsFalseWhenNoSessionToken(): void
    {
        $_POST['TOKEN'] = 'some_token';
        $result = CSRFSecure::ValidateToken('some_token');
        $this->assertFalse($result);
    }

    public function testValidateTokenReturnsFalseWhenNoPostToken(): void
    {
        CSRFSecure::CreateToken();
        $result = CSRFSecure::ValidateToken('some_token');
        $this->assertFalse($result);
    }

    public function testValidateTokenReturnsFalseForExpiredToken(): void
    {
        $token = CSRFSecure::CreateToken();
        $_POST['TOKEN'] = $token;
        // Manually expire the token
        $_SESSION['_TOKEN_EXPIRY'] = time() - 10;

        $result = CSRFSecure::ValidateToken($token);
        $this->assertFalse($result);
    }

    public function testCreateTokenGeneratesUniqueTokens(): void
    {
        $token1 = CSRFSecure::CreateToken();
        $token2 = CSRFSecure::CreateToken();
        $this->assertNotEquals($token1, $token2);
    }

    public function testTokenCannotBeReused(): void
    {
        $token = CSRFSecure::CreateToken();
        $_POST['TOKEN'] = $token;

        // First validation succeeds
        $this->assertTrue(CSRFSecure::ValidateToken($token));

        // Second validation fails (token destroyed)
        $_POST['TOKEN'] = $token;
        $this->assertFalse(CSRFSecure::ValidateToken($token));
    }
}
