<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once TEST_ROOT . '/functions/VCardFnc.php';

class VCardParserTest extends TestCase
{
    public function testBuildVCardBasic(): void
    {
        $vcf = buildVCard(['first' => 'John', 'last' => 'Doe', 'email' => 'john@school.edu']);
        $this->assertStringContainsString('BEGIN:VCARD', $vcf);
        $this->assertStringContainsString('VERSION:3.0', $vcf);
        $this->assertStringContainsString('N:Doe;John', $vcf);
        $this->assertStringContainsString('FN:John Doe', $vcf);
        $this->assertStringContainsString('EMAIL;TYPE=WORK:john@school.edu', $vcf);
        $this->assertStringContainsString('END:VCARD', $vcf);
    }

    public function testBuildVCardFullContact(): void
    {
        $vcf = buildVCard([
            'prefix' => 'Dr', 'first' => 'Jane', 'last' => 'Smith', 'middle' => 'A',
            'suffix' => 'Jr', 'email' => 'jane@work.com', 'email_home' => 'jane@home.com',
            'phone' => '555-1234', 'phone_home' => '555-5678', 'phone_cell' => '555-9999',
            'street' => '123 Main St', 'city' => 'Springfield', 'state' => 'IL', 'zip' => '62701',
            'bday' => '1985-06-15', 'gender' => 'Female', 'org' => 'School',
            'title' => 'Teacher', 'nickname' => 'Janey', 'note' => 'Test',
            'categories' => 'Staff',
        ]);
        $this->assertStringContainsString('N:Smith;Jane;A;Dr;Jr', $vcf);
        $this->assertStringContainsString('TEL;TYPE=WORK:555-1234', $vcf);
        $this->assertStringContainsString('TEL;TYPE=HOME:555-5678', $vcf);
        $this->assertStringContainsString('TEL;TYPE=CELL:555-9999', $vcf);
        $this->assertStringContainsString('EMAIL;TYPE=HOME:jane@home.com', $vcf);
        $this->assertStringContainsString('ADR;TYPE=HOME:;;123 Main St;Springfield;IL;62701;', $vcf);
        $this->assertStringContainsString('BDAY:19850615', $vcf);
        $this->assertStringContainsString('X-GENDER:F', $vcf);
        $this->assertStringContainsString('ORG:School', $vcf);
        $this->assertStringContainsString('TITLE:Teacher', $vcf);
        $this->assertStringContainsString('NICKNAME:Janey', $vcf);
        $this->assertStringContainsString('NOTE:Test', $vcf);
        $this->assertStringContainsString('CATEGORIES:Staff', $vcf);
    }

    public function testBuildVCardEscaping(): void
    {
        $vcf = buildVCard(['first' => 'John', 'last' => 'O;Brien', 'note' => 'Has, special; chars\\here']);
        $this->assertStringContainsString('O\\;Brien', $vcf);
        $this->assertStringContainsString('Has\\, special\\; chars\\\\here', $vcf);
    }

    public function testParseVCardSingle(): void
    {
        $vcf = "BEGIN:VCARD\r\nVERSION:3.0\r\nN:Smith;Alice;;;\r\nTEL;TYPE=CELL:555-1234\r\nEMAIL:alice@test.com\r\nEND:VCARD\r\n";
        $cards = parseVCards($vcf);
        $this->assertCount(1, $cards);
        $this->assertEquals('Alice', $cards[0]['first']);
        $this->assertEquals('Smith', $cards[0]['last']);
        $this->assertEquals('alice@test.com', $cards[0]['email']);
        $this->assertEquals('555-1234', $cards[0]['phone_cell']);
    }

    public function testParseVCardMultiple(): void
    {
        $vcf = "BEGIN:VCARD\r\nVERSION:3.0\r\nN:Doe;John;;;\r\nEND:VCARD\r\nBEGIN:VCARD\r\nVERSION:3.0\r\nN:Brown;Bob;;;\r\nEND:VCARD\r\n";
        $cards = parseVCards($vcf);
        $this->assertCount(2, $cards);
        $this->assertEquals('John', $cards[0]['first']);
        $this->assertEquals('Bob', $cards[1]['first']);
    }

    public function testParseVCardWithAddress(): void
    {
        $vcf = "BEGIN:VCARD\r\nN:Test;User;;;\r\nADR;TYPE=HOME:;;123 Oak Ave;Boston;MA;02101;US\r\nEND:VCARD\r\n";
        $cards = parseVCards($vcf);
        $this->assertStringContainsString('123 Oak Ave', $cards[0]['street']);
        $this->assertEquals('Boston', $cards[0]['city']);
        $this->assertEquals('MA', $cards[0]['state']);
        $this->assertEquals('02101', $cards[0]['zip']);
    }

    public function testParseVCardWithBirthday(): void
    {
        $vcf = "BEGIN:VCARD\r\nN:Test;User;;;\r\nBDAY:19900315\r\nEND:VCARD\r\n";
        $cards = parseVCards($vcf);
        $this->assertEquals('1990-03-15', $cards[0]['bday']);
    }

    public function testParseVCardPhoneTypes(): void
    {
        $vcf = "BEGIN:VCARD\r\nN:Test;User;;;\r\nTEL;TYPE=HOME:111\r\nTEL;TYPE=WORK:222\r\nTEL;TYPE=CELL:333\r\nEND:VCARD\r\n";
        $cards = parseVCards($vcf);
        $this->assertEquals('111', $cards[0]['phone_home']);
        $this->assertEquals('222', $cards[0]['phone_work']);
        $this->assertEquals('333', $cards[0]['phone_cell']);
    }

    public function testParseVCardFoldedLines(): void
    {
        // RFC 6350: long lines are folded with CRLF + space
        $vcf = "BEGIN:VCARD\r\nN:VeryLongLastName;VeryLongFirst\r\n Name;;;\r\nEND:VCARD\r\n";
        $cards = parseVCards($vcf);
        $this->assertEquals('VeryLongFirstName', $cards[0]['first']);
    }

    public function testParseVCardEmpty(): void
    {
        $this->assertEquals([], parseVCards(''));
        $this->assertEquals([], parseVCards('not a vcard'));
    }

    public function testRoundTrip(): void
    {
        $original = buildVCard(['first' => 'Alice', 'last' => 'Smith', 'email' => 'alice@test.com', 'phone_cell' => '555-0001']);
        $parsed = parseVCards($original);
        $this->assertEquals('Alice', $parsed[0]['first']);
        $this->assertEquals('Smith', $parsed[0]['last']);
        $this->assertEquals('alice@test.com', $parsed[0]['email']);
        $this->assertEquals('555-0001', $parsed[0]['phone_cell']);
    }
}
