<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/PreparePhpSelfFnc.php';

class PreparePhpSelfTest extends TestCase
{
    protected function setUp(): void
    {
        $_REQUEST = [];
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        $_REQUEST = [];
        $_COOKIE = [];
    }

    public function testBasicUrl(): void
    {
        $request = ['modname' => 'students/Student.php', 'student_id' => '123'];
        $result = PreparePHP_SELF($request);
        $this->assertStringStartsWith('Modules.php?modname=students/Student.php', $result);
        $this->assertStringContainsString('student_id=123', $result);
    }

    public function testExcludesCookies(): void
    {
        $_COOKIE['session_id'] = 'abc123';
        $request = ['modname' => 'test.php', 'session_id' => 'abc123', 'page' => '1'];
        $result = PreparePHP_SELF($request);
        $this->assertStringNotContainsString('session_id', $result);
        $this->assertStringContainsString('page=1', $result);
    }

    public function testNestedArrayParams(): void
    {
        $request = ['modname' => 'test.php', 'filter' => ['name' => 'John']];
        $result = PreparePHP_SELF($request);
        $this->assertStringContainsString('filter[name]=John', $result);
    }

    public function testSpacesReplacedWithPlus(): void
    {
        $request = ['modname' => 'test.php', 'q' => 'John Smith'];
        $result = PreparePHP_SELF($request);
        $this->assertStringContainsString('q=John+Smith', $result);
    }

    public function testEmptyValuesExcluded(): void
    {
        $request = ['modname' => 'test.php', 'empty' => '', 'filled' => 'yes'];
        $result = PreparePHP_SELF($request);
        $this->assertStringNotContainsString('empty=', $result);
        $this->assertStringContainsString('filled=yes', $result);
    }
}
