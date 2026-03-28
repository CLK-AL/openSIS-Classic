<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// ParamLibFnc.php already loaded in bootstrap

class CleanParamTest extends TestCase
{
    // --- PARAM_RAW ---
    public function testRawReturnsUnchanged(): void
    {
        $this->assertEquals('<script>alert(1)</script>', clean_param('<script>alert(1)</script>', PARAM_RAW));
    }

    // --- PARAM_INT ---
    public function testIntCastsToInteger(): void
    {
        $this->assertSame(42, clean_param('42', PARAM_INT));
    }

    public function testIntWithTextReturnsZero(): void
    {
        $this->assertSame(0, clean_param('abc', PARAM_INT));
    }

    public function testIntWithFloatTruncates(): void
    {
        $this->assertSame(3, clean_param('3.14', PARAM_INT));
    }

    public function testIntWithNegative(): void
    {
        $this->assertSame(-5, clean_param('-5', PARAM_INT));
    }

    public function testIntWithMixedString(): void
    {
        $this->assertSame(123, clean_param('123abc', PARAM_INT));
    }

    // --- PARAM_NUMBER ---
    public function testNumberCastsToFloat(): void
    {
        $this->assertSame(3.14, clean_param('3.14', PARAM_NUMBER));
    }

    public function testNumberWithInteger(): void
    {
        $this->assertSame(42.0, clean_param('42', PARAM_NUMBER));
    }

    // --- PARAM_ALPHA ---
    public function testAlphaStripsNumbers(): void
    {
        $this->assertEquals('hello', clean_param('hello123', PARAM_ALPHA));
    }

    public function testAlphaStripsSpecialChars(): void
    {
        $this->assertEquals('test', clean_param('te$st!', PARAM_ALPHA));
    }

    public function testAlphaAllowsHash(): void
    {
        $result = clean_param('test#value', PARAM_ALPHA);
        $this->assertStringContainsString('#', $result);
    }

    public function testAlphaPreservesCase(): void
    {
        $this->assertEquals('HelloWorld', clean_param('HelloWorld', PARAM_ALPHA));
    }

    // --- PARAM_ALPHANUM ---
    public function testAlphanumAllowsLettersAndNumbers(): void
    {
        $this->assertEquals('Test123', clean_param('Test123', PARAM_ALPHANUM));
    }

    public function testAlphanumStripsSpecialChars(): void
    {
        $this->assertEquals('user42', clean_param('user@42!', PARAM_ALPHANUM));
    }

    // --- PARAM_BOOL ---
    public function testBoolOnReturnsOne(): void
    {
        $this->assertEquals(1, clean_param('on', PARAM_BOOL));
    }

    public function testBoolYesReturnsOne(): void
    {
        $this->assertEquals(1, clean_param('yes', PARAM_BOOL));
    }

    public function testBoolOffReturnsZero(): void
    {
        $this->assertEquals(0, clean_param('off', PARAM_BOOL));
    }

    public function testBoolNoReturnsZero(): void
    {
        $this->assertEquals(0, clean_param('no', PARAM_BOOL));
    }

    public function testBoolEmptyReturnsZero(): void
    {
        $this->assertEquals(0, clean_param('', PARAM_BOOL));
    }

    public function testBoolTruthyStringReturnsOne(): void
    {
        $this->assertEquals(1, clean_param('something', PARAM_BOOL));
    }

    // --- PARAM_NOTAGS ---
    public function testNotagsStripsHtml(): void
    {
        $this->assertEquals('hello', clean_param('<b>hello</b>', PARAM_NOTAGS));
    }

    public function testNotagsStripsScriptTags(): void
    {
        $result = clean_param('<script>alert("xss")</script>', PARAM_NOTAGS);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    // --- PARAM_FILE ---
    public function testFileCleansTraversal(): void
    {
        $result = clean_param('../../etc/passwd', PARAM_FILE);
        $this->assertStringNotContainsString('..', $result);
    }

    public function testFileStripsControlChars(): void
    {
        $result = clean_param("file\x00name.txt", PARAM_FILE);
        $this->assertStringNotContainsString("\x00", $result);
    }

    public function testFileStripsBackslash(): void
    {
        $result = clean_param('path\\file.txt', PARAM_FILE);
        $this->assertStringNotContainsString('\\', $result);
    }

    // --- PARAM_PATH ---
    public function testPathNormalizesSlashes(): void
    {
        $result = clean_param('path\\to\\file', PARAM_PATH);
        $this->assertStringNotContainsString('\\', $result);
    }

    public function testPathCleansTraversal(): void
    {
        $result = clean_param('/var/../../../etc/passwd', PARAM_PATH);
        $this->assertStringNotContainsString('..', $result);
    }

    // --- PARAM_SEQUENCE ---
    public function testSequenceAllowsNumbersAndCommas(): void
    {
        $this->assertEquals('1,2,3,4', clean_param('1,2,3,4', PARAM_SEQUENCE));
    }

    public function testSequenceStripsLetters(): void
    {
        $this->assertEquals('123', clean_param('abc123def', PARAM_SEQUENCE));
    }

    // --- PARAM_MAIL ---
    public function testMailAllowsValidEmail(): void
    {
        $this->assertEquals('user@example.com', clean_param('user@example.com', PARAM_MAIL));
    }

    public function testMailStripsInvalidChars(): void
    {
        $result = clean_param('user<script>@test.com', PARAM_MAIL);
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    // --- PARAM_PHONE ---
    public function testPhoneAllowsValidFormat(): void
    {
        $this->assertEquals('+1(555)123-4567', clean_param('+1(555)123-4567', PARAM_PHONE));
    }

    public function testPhoneStripsLetters(): void
    {
        $result = clean_param('555-CALL-ME', PARAM_PHONE);
        $this->assertStringNotContainsString('C', $result);
        $this->assertStringNotContainsString('A', $result);
    }

    // --- Array handling ---
    public function testArrayCleaningRecursive(): void
    {
        $input = ['42', '99', 'abc'];
        $result = clean_param($input, PARAM_INT);
        $this->assertSame([42, 99, 0], $result);
    }

    // --- PARAM_SPCL ---
    public function testSpclRemovesDangerousChars(): void
    {
        $result = clean_param('hello*world@test', PARAM_SPCL);
        $this->assertStringNotContainsString('*', $result);
        $this->assertStringNotContainsString('@', $result);
    }

    // --- optional_param ---
    public function testOptionalParamReturnsDefaultWhenMissing(): void
    {
        $_POST = [];
        $_GET = [];
        $result = optional_param('nonexistent', 'default_val', PARAM_RAW);
        $this->assertEquals('default_val', $result);
    }

    public function testOptionalParamReadsFromPost(): void
    {
        $_POST['myvar'] = '42';
        $_GET = [];
        $result = optional_param('myvar', '0', PARAM_INT);
        $this->assertSame(42, $result);
        unset($_POST['myvar']);
    }

    public function testOptionalParamReadsFromGet(): void
    {
        $_POST = [];
        $_GET['myvar'] = '42';
        $result = optional_param('myvar', '0', PARAM_INT);
        $this->assertSame(42, $result);
        unset($_GET['myvar']);
    }

    public function testOptionalParamPostTakesPrecedence(): void
    {
        $_POST['myvar'] = '10';
        $_GET['myvar'] = '20';
        $result = optional_param('myvar', '0', PARAM_INT);
        $this->assertSame(10, $result);
        unset($_POST['myvar'], $_GET['myvar']);
    }
}
