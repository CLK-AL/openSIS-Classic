<?php
/**
 * iCalendar build/parse functions — used by ICalExport.php and tests.
 */

if (!function_exists('icalHeader')) {

function icalHeader(string $name): string
{
    $h  = "BEGIN:VCALENDAR\r\n";
    $h .= "VERSION:2.0\r\n";
    $h .= "PRODID:-//openSIS//openSIS Classic//EN\r\n";
    $h .= "CALSCALE:GREGORIAN\r\n";
    $h .= "METHOD:PUBLISH\r\n";
    $h .= "X-WR-CALNAME:" . icalEsc($name) . "\r\n";
    return $h;
}

function buildEvent(array $d): string
{
    $e  = "BEGIN:VEVENT\r\n";
    $e .= "UID:" . icalEsc($d['uid']) . "\r\n";
    $e .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
    $startDate = str_replace('-', '', substr($d['date'], 0, 10));
    if (!empty($d['allday'])) {
        $e .= "DTSTART;VALUE=DATE:$startDate\r\n";
        if (!empty($d['end_date'])) {
            $endTs = strtotime($d['end_date'] . ' +1 day');
            $e .= "DTEND;VALUE=DATE:" . date('Ymd', $endTs) . "\r\n";
        } else {
            $endTs = strtotime($d['date'] . ' +1 day');
            $e .= "DTEND;VALUE=DATE:" . date('Ymd', $endTs) . "\r\n";
        }
    } else {
        $e .= "DTSTART:$startDate\r\n";
    }
    $e .= "SUMMARY:" . icalEsc($d['summary']) . "\r\n";
    if (!empty($d['desc'])) $e .= "DESCRIPTION:" . icalEsc($d['desc']) . "\r\n";
    $e .= "END:VEVENT\r\n";
    return $e;
}

function parseICal(string $data): array
{
    $events = [];
    $data = preg_replace('/\r?\n[ \t]/', '', $data);
    $blocks = preg_split('/(?=BEGIN:VEVENT)/i', $data);
    foreach ($blocks as $block) {
        if (stripos($block, 'BEGIN:VEVENT') === false) continue;
        $event = ['summary'=>'', 'description'=>'', 'dtstart'=>'', 'dtend'=>''];
        $lines = preg_split('/\r?\n/', $block);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;
            $colonPos = strpos($line, ':');
            if ($colonPos === false) continue;
            $prop = strtoupper(substr($line, 0, $colonPos));
            $value = substr($line, $colonPos + 1);
            $propName = explode(';', $prop)[0];
            switch ($propName) {
                case 'SUMMARY': $event['summary'] = icalUnesc($value); break;
                case 'DESCRIPTION': $event['description'] = icalUnesc($value); break;
                case 'DTSTART': $event['dtstart'] = parseICalDate($value); break;
                case 'DTEND': $event['dtend'] = parseICalDate($value); break;
            }
        }
        if ($event['summary'] || $event['dtstart']) $events[] = $event;
    }
    return $events;
}

function parseICalDate(string $value): string
{
    $clean = preg_replace('/[^0-9]/', '', substr(trim($value), 0, 8));
    if (strlen($clean) >= 8) return substr($clean,0,4).'-'.substr($clean,4,2).'-'.substr($clean,6,2);
    return $value;
}

function icalEsc(string $s): string
{
    return str_replace(['\\', "\n", ',', ';'], ['\\\\', '\\n', '\\,', '\\;'], $s);
}

function icalUnesc(string $s): string
{
    return trim(str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], $s));
}

} // end function_exists guard
