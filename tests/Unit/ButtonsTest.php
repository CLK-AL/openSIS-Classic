<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/ButtonsFnc.php';

class ButtonsTest extends TestCase
{
    public function testSubmitButtonOnly(): void
    {
        $result = Buttons('Save');
        $this->assertStringContainsString('type=SUBMIT', $result);
        $this->assertStringContainsString('value="Save"', $result);
        $this->assertStringContainsString('btn-primary', $result);
        $this->assertStringNotContainsString('type=RESET', $result);
    }

    public function testSubmitAndResetButtons(): void
    {
        $result = Buttons('Save', 'Cancel');
        $this->assertStringContainsString('value="Save"', $result);
        $this->assertStringContainsString('value="Cancel"', $result);
        $this->assertStringContainsString('btn-primary', $result);
        $this->assertStringContainsString('btn-default', $result);
    }

    public function testExtraAttributes(): void
    {
        $result = Buttons('Go', '', 'disabled');
        $this->assertStringContainsString('disabled', $result);
    }
}
