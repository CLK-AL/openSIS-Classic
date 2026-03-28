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
    if (!empty($d['location'])) $e .= "LOCATION:" . icalEsc($d['location']) . "\r\n";
    if (!empty($d['status'])) $e .= "STATUS:" . strtoupper($d['status']) . "\r\n";

    // VALARM reminders (array of minutes-before triggers)
    if (!empty($d['alarms'])) {
        foreach ((array)$d['alarms'] as $alarm) {
            $e .= buildAlarm($alarm);
        }
    }

    $e .= "END:VEVENT\r\n";
    return $e;
}

/**
 * Build a VTODO component (task/reminder).
 */
function buildTodo(array $d): string
{
    $t  = "BEGIN:VTODO\r\n";
    $t .= "UID:" . icalEsc($d['uid']) . "\r\n";
    $t .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
    $t .= "SUMMARY:" . icalEsc($d['summary']) . "\r\n";
    if (!empty($d['desc'])) $t .= "DESCRIPTION:" . icalEsc($d['desc']) . "\r\n";
    if (!empty($d['due'])) {
        $due = str_replace('-', '', substr($d['due'], 0, 10));
        $t .= "DUE;VALUE=DATE:$due\r\n";
    }
    if (!empty($d['priority'])) $t .= "PRIORITY:" . (int)$d['priority'] . "\r\n";
    $status = strtoupper($d['status'] ?? 'NEEDS-ACTION');
    $t .= "STATUS:$status\r\n";

    if (!empty($d['alarms'])) {
        foreach ((array)$d['alarms'] as $alarm) {
            $t .= buildAlarm($alarm);
        }
    }

    $t .= "END:VTODO\r\n";
    return $t;
}

/**
 * Build a VALARM component.
 * $alarm can be:
 *   - integer: minutes before (negative trigger)
 *   - array: ['minutes'=>N, 'action'=>'DISPLAY'|'EMAIL', 'description'=>'...']
 */
function buildAlarm($alarm): string
{
    if (is_numeric($alarm)) {
        $alarm = ['minutes' => (int)$alarm];
    }
    $minutes = (int)($alarm['minutes'] ?? 60);
    $action = strtoupper($alarm['action'] ?? 'DISPLAY');
    $desc = $alarm['description'] ?? 'Reminder';

    // Convert minutes to ISO 8601 duration
    $days = intdiv($minutes, 1440);
    $hours = intdiv($minutes % 1440, 60);
    $mins = $minutes % 60;
    $duration = 'P';
    if ($days > 0) $duration .= $days . 'D';
    $duration .= 'T';
    if ($hours > 0) $duration .= $hours . 'H';
    if ($mins > 0) $duration .= $mins . 'M';
    if ($duration === 'PT') $duration = 'PT0M';

    $a  = "BEGIN:VALARM\r\n";
    $a .= "TRIGGER:-$duration\r\n";
    $a .= "ACTION:$action\r\n";
    $a .= "DESCRIPTION:" . icalEsc($desc) . "\r\n";
    $a .= "END:VALARM\r\n";
    return $a;
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
