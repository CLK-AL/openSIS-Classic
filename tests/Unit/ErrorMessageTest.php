<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/ErrorMessageFnc.php';

class ErrorMessageTest extends TestCase
{
    public function testSingleErrorWarning(): void
    {
        $result = ErrorMessage(['Something went wrong'], 'error');
        $this->assertStringContainsString('alert-warning', $result);
        $this->assertStringContainsString('Something went wrong', $result);
    }

    public function testMultipleErrors(): void
    {
        $result = ErrorMessage(['Error 1', 'Error 2', 'Error 3'], 'error');
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>Error 1</li>', $result);
        $this->assertStringContainsString('<li>Error 2</li>', $result);
        $this->assertStringContainsString('<li>Error 3</li>', $result);
    }

    public function testDangerCode(): void
    {
        $result = ErrorMessage(['Bad input'], 'danger');
        $this->assertStringContainsString('alert-danger', $result);
    }

    public function testNoteCode(): void
    {
        $result = ErrorMessage(['Note this'], 'note');
        $this->assertStringContainsString('alert-warning', $result);
    }

    public function testEmptyArrayReturnsNull(): void
    {
        $result = ErrorMessage([]);
        $this->assertNull($result);
    }

    public function testNonArrayReturnsNull(): void
    {
        $result = ErrorMessage('not an array');
        $this->assertNull($result);
    }

    public function testDuplicateErrorsDeduped(): void
    {
        $result = ErrorMessage(['Same error', 'Same error', 'Same error']);
        $this->assertStringNotContainsString('<ul>', $result);
        $this->assertStringContainsString('Same error', $result);
    }

    public function testOptionsAttribute(): void
    {
        $result = ErrorMessage(['Test'], 'error', 'style="color:red"');
        $this->assertStringContainsString('style="color:red"', $result);
    }
}
