<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/SqlSecurityFnc.php';

/**
 * Integration test: Input validation pipeline.
 *
 * Tests the full flow of user input through parameter cleaning
 * then SQL security filtering, as happens in a real request.
 */
class InputValidationWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        $_POST = [];
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
    }

    /**
     * Simulates: User submits a student name via form → clean_param → sqlSecurityFilter
     */
    public function testSafeStudentNamePassesBothFilters(): void
    {
        $_POST['student_name'] = 'John Smith';
        $cleaned = optional_param('student_name', '', PARAM_NOTAGS);
        $secured = sqlSecurityFilter($cleaned, 'no');

        $this->assertStringContainsString('John', $secured);
        $this->assertStringContainsString('Smith', $secured);
    }

    /**
     * Simulates: Attacker submits SQL injection via student name field
     */
    public function testSqlInjectionInNameBlocked(): void
    {
        $_POST['student_name'] = "Robert'; DROP TABLE students;--";
        $cleaned = optional_param('student_name', '', PARAM_NOTAGS);
        $secured = sqlSecurityFilter($cleaned, 'no');

        $this->assertStringNotContainsString('DROP', $secured);
        $this->assertEquals('', $secured);
    }

    /**
     * Simulates: XSS attempt in a text field
     */
    public function testXssInTextFieldBlocked(): void
    {
        $_POST['comment'] = '<script>document.cookie</script>';
        $cleaned = optional_param('comment', '', PARAM_NOTAGS);

        $this->assertStringNotContainsString('<script>', $cleaned);
        $this->assertStringNotContainsString('</script>', $cleaned);
    }

    /**
     * Simulates: Integer ID parameter with injection attempt
     */
    public function testIntegerParamBlocksInjection(): void
    {
        $_GET['student_id'] = '1 OR 1=1';
        $cleaned = optional_param('student_id', 0, PARAM_INT);

        $this->assertSame(1, $cleaned);
    }

    /**
     * Simulates: Module name parameter with path traversal
     */
    public function testModulePathTraversalBlocked(): void
    {
        $_GET['modname'] = '../../../etc/passwd';
        $cleaned = optional_param('modname', '', PARAM_FILE);

        $this->assertStringNotContainsString('..', $cleaned);
        $this->assertStringNotContainsString('etc/passwd', $cleaned);
    }

    /**
     * Simulates: Bulk form submission with mixed safe/unsafe values
     */
    public function testBulkFormSubmissionFiltered(): void
    {
        $_POST['first_name'] = 'Alice';
        $_POST['last_name'] = 'Johnson';
        $_POST['grade'] = '95';
        $_POST['notes'] = 'Good student; SELECT * FROM passwords';

        $firstName = optional_param('first_name', '', PARAM_NOTAGS);
        $lastName = optional_param('last_name', '', PARAM_NOTAGS);
        $grade = optional_param('grade', 0, PARAM_INT);
        $notes = sqlSecurityFilter(optional_param('notes', '', PARAM_NOTAGS), 'no');

        $this->assertEquals('Alice', $firstName);
        $this->assertEquals('Johnson', $lastName);
        $this->assertSame(95, $grade);
        // Notes contain "SELECT " which triggers SQL filter
        $this->assertEquals('', $notes);
    }

    /**
     * Simulates: Search query parameter handling
     */
    public function testSearchQuerySanitized(): void
    {
        $_GET['search'] = 'John <b>Smith</b>';
        $cleaned = optional_param('search', '', PARAM_NOTAGS);

        $this->assertEquals('John Smith', $cleaned);
        $this->assertStringNotContainsString('<b>', $cleaned);
    }

    /**
     * Simulates: Email parameter validation
     */
    public function testEmailParameterCleaned(): void
    {
        $_POST['email'] = 'parent@school.edu';
        $cleaned = optional_param('email', '', PARAM_MAIL);
        $this->assertEquals('parent@school.edu', $cleaned);
    }

    public function testMaliciousEmailBlocked(): void
    {
        $_POST['email'] = 'admin@school.edu<script>alert(1)</script>';
        $cleaned = optional_param('email', '', PARAM_MAIL);
        $this->assertStringNotContainsString('<', $cleaned);
        $this->assertStringNotContainsString('>', $cleaned);
    }

    /**
     * Simulates: Phone number parameter
     */
    public function testPhoneParameterCleaned(): void
    {
        $_POST['phone'] = '+1(555)123-4567';
        $cleaned = optional_param('phone', '', PARAM_PHONE);
        $this->assertEquals('+1(555)123-4567', $cleaned);
    }

    /**
     * Simulates: Missing required fields use defaults
     */
    public function testMissingFieldsUseDefaults(): void
    {
        $schoolId = optional_param('school_id', 1, PARAM_INT);
        $syear = optional_param('syear', '2025-2026', PARAM_RAW);

        $this->assertSame(1, $schoolId);
        $this->assertEquals('2025-2026', $syear);
    }

    /**
     * Simulates: Sequence parameter (comma-separated IDs for bulk operations)
     */
    public function testSequenceParameterFiltered(): void
    {
        $_POST['student_ids'] = '1,2,3,4,5';
        $cleaned = optional_param('student_ids', '', PARAM_SEQUENCE);
        $this->assertEquals('1,2,3,4,5', $cleaned);
    }

    public function testSequenceWithInjectionFiltered(): void
    {
        $_POST['student_ids'] = '1,2,3; DROP TABLE students';
        $cleaned = optional_param('student_ids', '', PARAM_SEQUENCE);
        // Only digits and commas remain
        $this->assertMatchesRegularExpression('/^[0-9,]+$/', $cleaned);
    }

    /**
     * Simulates: Boolean toggle (e.g., maintenance mode switch)
     */
    public function testBooleanToggleParameter(): void
    {
        $_POST['maintenance'] = 'on';
        $this->assertEquals(1, optional_param('maintenance', 0, PARAM_BOOL));

        $_POST['maintenance'] = 'off';
        $this->assertEquals(0, optional_param('maintenance', 0, PARAM_BOOL));
    }

    /**
     * Simulates: Unicode input (Arabic student name)
     */
    public function testUnicodeInputPreserved(): void
    {
        $_POST['student_name'] = 'محمد أحمد';
        $cleaned = optional_param('student_name', '', PARAM_RAW);
        $this->assertEquals('محمد أحمد', $cleaned);
    }

    /**
     * Simulates: Double-encoded attack with directory traversal
     */
    public function testDoubleEncodedTraversalBlocked(): void
    {
        // URL-decoded form of ..%2f traversal
        $input = '../../../etc/passwd';
        $secured = sqlSecurityFilter($input, 'no');
        $this->assertEquals('', $secured);
    }
}
