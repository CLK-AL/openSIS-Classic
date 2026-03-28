<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/ICalFnc.php';

class ICalParserTest extends TestCase
{
    public function testIcalHeaderFormat(): void
    {
        $h = icalHeader('Test Calendar');
        $this->assertStringContainsString('BEGIN:VCALENDAR', $h);
        $this->assertStringContainsString('VERSION:2.0', $h);
        $this->assertStringContainsString('PRODID:-//openSIS', $h);
        $this->assertStringContainsString('X-WR-CALNAME:Test Calendar', $h);
    }

    public function testBuildEventAllDay(): void
    {
        $e = buildEvent([
            'uid' => 'test-1@opensis', 'summary' => 'School Holiday',
            'desc' => 'No classes', 'date' => '2025-12-25', 'allday' => true,
        ]);
        $this->assertStringContainsString('BEGIN:VEVENT', $e);
        $this->assertStringContainsString('UID:test-1@opensis', $e);
        $this->assertStringContainsString('DTSTART;VALUE=DATE:20251225', $e);
        $this->assertStringContainsString('DTEND;VALUE=DATE:20251226', $e);
        $this->assertStringContainsString('SUMMARY:School Holiday', $e);
        $this->assertStringContainsString('DESCRIPTION:No classes', $e);
        $this->assertStringContainsString('END:VEVENT', $e);
    }

    public function testBuildEventDateRange(): void
    {
        $e = buildEvent([
            'uid' => 'mp-1@opensis', 'summary' => 'Semester 1',
            'desc' => '', 'date' => '2025-08-15', 'end_date' => '2025-12-20', 'allday' => true,
        ]);
        $this->assertStringContainsString('DTSTART;VALUE=DATE:20250815', $e);
        $this->assertStringContainsString('DTEND;VALUE=DATE:20251221', $e); // +1 day (exclusive)
    }

    public function testBuildEventEscaping(): void
    {
        $e = buildEvent([
            'uid' => 'test@opensis', 'summary' => 'Event, with; special\\chars',
            'desc' => "Line one\nLine two", 'date' => '2025-01-01', 'allday' => true,
        ]);
        $this->assertStringContainsString('Event\\, with\\; special\\\\chars', $e);
        $this->assertStringContainsString('Line one\\nLine two', $e);
    }

    public function testParseICalSingle(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nSUMMARY:Test Event\r\nDTSTART:20250315\r\nDTEND:20250316\r\nDESCRIPTION:A test\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $events = parseICal($ics);
        $this->assertCount(1, $events);
        $this->assertEquals('Test Event', $events[0]['summary']);
        $this->assertEquals('2025-03-15', $events[0]['dtstart']);
        $this->assertEquals('2025-03-16', $events[0]['dtend']);
        $this->assertEquals('A test', $events[0]['description']);
    }

    public function testParseICalMultiple(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nSUMMARY:Event 1\r\nDTSTART:20250101\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nSUMMARY:Event 2\r\nDTSTART:20250201\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $events = parseICal($ics);
        $this->assertCount(2, $events);
        $this->assertEquals('Event 1', $events[0]['summary']);
        $this->assertEquals('Event 2', $events[1]['summary']);
    }

    public function testParseICalValueDateParam(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nSUMMARY:Holiday\r\nDTSTART;VALUE=DATE:20251225\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $events = parseICal($ics);
        $this->assertEquals('2025-12-25', $events[0]['dtstart']);
    }

    public function testParseICalDateTimeFormat(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nSUMMARY:Meeting\r\nDTSTART:20250315T090000Z\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $events = parseICal($ics);
        $this->assertEquals('2025-03-15', $events[0]['dtstart']);
    }

    public function testParseICalFoldedLines(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nSUMMARY:This is a very long\r\n  event summary\r\nDTSTART:20250101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $events = parseICal($ics);
        $this->assertEquals('This is a very long event summary', $events[0]['summary']);
    }

    public function testParseICalEscapedChars(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nSUMMARY:Test\\, with\\; escapes\r\nDTSTART:20250101\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        $events = parseICal($ics);
        $this->assertEquals('Test, with; escapes', $events[0]['summary']);
    }

    public function testParseICalEmpty(): void
    {
        $this->assertEquals([], parseICal(''));
        $this->assertEquals([], parseICal('not ical data'));
    }

    public function testRoundTrip(): void
    {
        $cal = icalHeader('Test');
        $cal .= buildEvent(['uid' => 'rt@opensis', 'summary' => 'Round Trip Event', 'desc' => 'Testing', 'date' => '2025-06-15', 'allday' => true]);
        $cal .= "END:VCALENDAR\r\n";

        $parsed = parseICal($cal);
        $this->assertCount(1, $parsed);
        $this->assertEquals('Round Trip Event', $parsed[0]['summary']);
        $this->assertEquals('2025-06-15', $parsed[0]['dtstart']);
        $this->assertEquals('Testing', $parsed[0]['description']);
    }
}
