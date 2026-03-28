<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/PasswordHashFnc.php';

class PasswordHashTest extends TestCase
{
    public function testGenerateNewHashReturnsNonEmptyString(): void
    {
        $hash = GenerateNewHash('testpassword');
        $this->assertNotEmpty($hash);
        $this->assertIsString($hash);
    }

    public function testGenerateNewHashProducesBcryptHash(): void
    {
        $hash = GenerateNewHash('mypassword');
        // password_hash with PASSWORD_DEFAULT produces $2y$ prefix (bcrypt)
        $this->assertMatchesRegularExpression('/^\$2[aby]\$/', $hash);
    }

    public function testVerifyHashReturnsTrueForCorrectPassword(): void
    {
        $password = 'correcthorse';
        $hash = GenerateNewHash($password);
        $result = VerifyHash($password, $hash);
        $this->assertNotEmpty($result);
    }

    public function testVerifyHashReturnsFalseForWrongPassword(): void
    {
        $hash = GenerateNewHash('rightpassword');
        $result = VerifyHash('wrongpassword', $hash);
        $this->assertEquals(0, $result);
    }

    public function testVerifyHashReturnsFalseForEmptyPassword(): void
    {
        $hash = GenerateNewHash('somepassword');
        $result = VerifyHash('', $hash);
        $this->assertEquals(0, $result);
    }

    public function testGenerateNewHashProducesUniqueHashes(): void
    {
        $hash1 = GenerateNewHash('samepassword');
        $hash2 = GenerateNewHash('samepassword');
        // bcrypt uses random salt, so hashes should differ
        $this->assertNotEquals($hash1, $hash2);
        // But both should verify
        $this->assertNotEmpty(VerifyHash('samepassword', $hash1));
        $this->assertNotEmpty(VerifyHash('samepassword', $hash2));
    }

    public function testSpecialCharactersInPassword(): void
    {
        $password = "p@\$\$w0rd!#%^&*()_+{}|:<>?";
        $hash = GenerateNewHash($password);
        $this->assertNotEmpty(VerifyHash($password, $hash));
    }

    public function testUnicodePassword(): void
    {
        $password = 'كلمة المرور';
        $hash = GenerateNewHash($password);
        $this->assertNotEmpty(VerifyHash($password, $hash));
    }

    public function testLongPassword(): void
    {
        $password = str_repeat('a', 1000);
        $hash = GenerateNewHash($password);
        // bcrypt truncates at 72 bytes, but should still work
        $this->assertNotEmpty(VerifyHash($password, $hash));
    }
}
