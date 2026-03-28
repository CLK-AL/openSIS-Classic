<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/UrlFnc.php';

class UrlFncTest extends TestCase
{
    public function testGetStringBetween(): void
    {
        $this->assertEquals('world', get_string_between('hello[world]end', '[', ']'));
    }

    public function testGetStringBetweenEmpty(): void
    {
        $this->assertEquals('', get_string_between('no brackets', '[', ']'));
    }

    public function testGetStringBetweenNested(): void
    {
        $result = get_string_between('key[value]', '[', ']');
        $this->assertEquals('value', $result);
    }

    public function testGetStringBetweenAtStart(): void
    {
        $result = get_string_between('[first]rest', '[', ']');
        $this->assertEquals('first', $result);
    }

    public function testEncodeUrlNonModulesPassesThrough(): void
    {
        $result = encode_url('index.php?test=1');
        $this->assertEquals('index.php?test=1', $result);
    }
}
