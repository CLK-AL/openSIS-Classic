<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/ReindexResultsFnc.php';

class ReindexResultsTest extends TestCase
{
    public function testReindexesFrom1(): void
    {
        $input = ['a' => 'first', 'b' => 'second', 'c' => 'third'];
        $result = ReindexResults($input);
        $this->assertEquals([1 => 'first', 2 => 'second', 3 => 'third'], $result);
    }

    public function testReindexesZeroBasedArray(): void
    {
        $input = [0 => 'x', 1 => 'y', 2 => 'z'];
        $result = ReindexResults($input);
        $this->assertEquals([1 => 'x', 2 => 'y', 3 => 'z'], $result);
    }

    public function testEmptyArray(): void
    {
        $this->assertEquals([], ReindexResults([]));
    }

    public function testPreservesValues(): void
    {
        $input = ['key' => ['nested' => 'value']];
        $result = ReindexResults($input);
        $this->assertEquals([1 => ['nested' => 'value']], $result);
    }
}
