<?php
#***************************************************************************************
#  iCal Import/Export — Export school events and calendar as .ics files
#***************************************************************************************

include('../../RedirectModulesInc.php');
require_once 'functions/ICalFnc.php';

global $connection;

$exportType = optional_param('export_type', '', PARAM_ALPHA);

// ── Export action ────────────────────────────────────────────────────
if ($exportType && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $ical = '';
    $filename = 'opensis_' . $exportType . '_' . date('Y-m-d') . '.ics';

    switch ($exportType) {
        case 'events':
            $ical = exportEvents();
            break;
        case 'schooldays':
            $ical = exportSchoolDays();
            break;
        case 'markingperiods':
            $ical = exportMarkingPeriods();
            break;
    }

    if ($ical) {
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($ical));
        echo $ical;
        exit;
    }
}

// ── Import action ────────────────────────────────────────────────────
if (isset($_REQUEST['modfunc']) && $_REQUEST['modfunc'] === 'import' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    if (isset($_FILES['ics_file']) && $_FILES['ics_file']['error'] === UPLOAD_ERR_OK) {
        $content = file_get_contents($_FILES['ics_file']['tmp_name']);
        $events = parseICal($content);
        $imported = 0;
        $skipped = 0;
        $syear = UserSyear();
        $school = UserSchool();
        $staffId = (int)($_SESSION['STAFF_ID'] ?? 0);

        // Get default calendar
        $cal = DBGet(DBQuery("SELECT calendar_id FROM school_calendars WHERE syear='$syear' AND school_id='$school' LIMIT 1"));
        $calId = $cal[1]['CALENDAR_ID'] ?? 1;

        foreach ($events as $evt) {
            if (empty($evt['summary']) || empty($evt['dtstart'])) {
                $skipped++;
                continue;
            }
            $title = mysqli_real_escape_string($connection, substr($evt['summary'], 0, 50));
            $desc  = mysqli_real_escape_string($connection, $evt['description'] ?? '');
            $date  = mysqli_real_escape_string($connection, $evt['dtstart']);

            // Check duplicate
            $existing = DBGet(DBQuery("SELECT id FROM calendar_events WHERE syear='$syear' AND school_id='$school' AND school_date='$date' AND title='$title' LIMIT 1"));
            if (!empty($existing)) {
                $skipped++;
                continue;
            }

            DBQuery("INSERT INTO calendar_events (syear, school_id, calendar_id, school_date, title, description, updated_by)
                     VALUES ('$syear', '$school', '$calId', '$date', '$title', '$desc', '$staffId')");
            $imported++;
        }

        echo '<div class="alert alert-success">';
        echo "Imported: <strong>$imported</strong> events. Skipped: <strong>$skipped</strong> (duplicates or missing data).";
        echo '</div>';
    } else {
        echo '<div class="alert alert-danger">Please select an .ics file to upload.</div>';
    }
}

// ── Export functions ─────────────────────────────────────────────────
function exportEvents(): string
{
    $syear = UserSyear();
    $school = UserSchool();
    $events = DBGet(DBQuery("
        SELECT id, title, description, school_date
        FROM calendar_events
        WHERE syear='$syear' AND school_id='$school'
        ORDER BY school_date
    "));

    $cal = icalHeader('openSIS School Events');
    foreach ($events as $e) {
        $cal .= buildEvent([
            'uid'     => 'event-' . $e['ID'] . '@opensis',
            'summary' => $e['TITLE'],
            'desc'    => $e['DESCRIPTION'] ?? '',
            'date'    => $e['SCHOOL_DATE'],
            'allday'  => true,
        ]);
    }
    $cal .= "END:VCALENDAR\r\n";
    return $cal;
}

function exportSchoolDays(): string
{
    $syear = UserSyear();
    $school = UserSchool();
    $days = DBGet(DBQuery("
        SELECT ac.school_date, ac.minutes, sc.title AS calendar_title
        FROM attendance_calendar ac
        JOIN school_calendars sc ON ac.calendar_id = sc.calendar_id AND ac.syear = sc.syear
        WHERE ac.syear='$syear' AND ac.school_id='$school'
        ORDER BY ac.school_date
    "));

    $cal = icalHeader('openSIS School Days');
    foreach ($days as $d) {
        $cal .= buildEvent([
            'uid'     => 'schoolday-' . $d['SCHOOL_DATE'] . '@opensis',
            'summary' => 'School Day' . ($d['MINUTES'] ? " ({$d['MINUTES']} min)" : ''),
            'desc'    => $d['CALENDAR_TITLE'] ?? '',
            'date'    => $d['SCHOOL_DATE'],
            'allday'  => true,
        ]);
    }
    $cal .= "END:VCALENDAR\r\n";
    return $cal;
}

function exportMarkingPeriods(): string
{
    $syear = UserSyear();
    $school = UserSchool();
    $periods = DBGet(DBQuery("
        SELECT marking_period_id, title, short_name, start_date, end_date, post_start_date, post_end_date
        FROM marking_periods
        WHERE syear='$syear' AND school_id='$school'
        ORDER BY start_date
    "));

    $cal = icalHeader('openSIS Marking Periods');
    foreach ($periods as $p) {
        // Period range
        $cal .= buildEvent([
            'uid'     => 'mp-' . $p['MARKING_PERIOD_ID'] . '@opensis',
            'summary' => $p['TITLE'] . ' (' . ($p['SHORT_NAME'] ?? '') . ')',
            'desc'    => 'Marking Period: ' . $p['TITLE'],
            'date'    => $p['START_DATE'],
            'end_date'=> $p['END_DATE'],
            'allday'  => true,
        ]);
        // Grade posting window
        if (!empty($p['POST_START_DATE']) && $p['POST_START_DATE'] !== '0000-00-00') {
            $cal .= buildEvent([
                'uid'     => 'gradepost-' . $p['MARKING_PERIOD_ID'] . '@opensis',
                'summary' => 'Grade Posting: ' . $p['TITLE'],
                'desc'    => 'Grade posting window for ' . $p['TITLE'],
                'date'    => $p['POST_START_DATE'],
                'end_date'=> $p['POST_END_DATE'],
                'allday'  => true,
            ]);
        }
    }
    $cal .= "END:VCALENDAR\r\n";
    return $cal;
}

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
            // iCal all-day DTEND is exclusive, so add 1 day
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
    if (!empty($d['desc'])) {
        $e .= "DESCRIPTION:" . icalEsc($d['desc']) . "\r\n";
    }
    $e .= "END:VEVENT\r\n";
    return $e;
}

function icalEsc(string $s): string
{
    $s = str_replace('\\', '\\\\', $s);
    $s = str_replace("\n", '\\n', $s);
    $s = str_replace(',', '\\,', $s);
    $s = str_replace(';', '\\;', $s);
    return $s;
}

// ── iCal Parser ──────────────────────────────────────────────────────
function parseICal(string $data): array
{
    $events = [];
    // Unfold continued lines
    $data = preg_replace('/\r?\n[ \t]/', '', $data);
    $blocks = preg_split('/(?=BEGIN:VEVENT)/i', $data);

    foreach ($blocks as $block) {
        if (stripos($block, 'BEGIN:VEVENT') === false) continue;
        $event = ['summary' => '', 'description' => '', 'dtstart' => '', 'dtend' => ''];

        $lines = preg_split('/\r?\n/', $block);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;

            $colonPos = strpos($line, ':');
            if ($colonPos === false) continue;

            $prop = strtoupper(substr($line, 0, $colonPos));
            $value = substr($line, $colonPos + 1);

            // Strip parameters (e.g., DTSTART;VALUE=DATE:)
            $propName = explode(';', $prop)[0];

            switch ($propName) {
                case 'SUMMARY':
                    $event['summary'] = icalUnesc($value);
                    break;
                case 'DESCRIPTION':
                    $event['description'] = icalUnesc($value);
                    break;
                case 'DTSTART':
                    $event['dtstart'] = parseICalDate($value);
                    break;
                case 'DTEND':
                    $event['dtend'] = parseICalDate($value);
                    break;
            }
        }
        if ($event['summary'] || $event['dtstart']) {
            $events[] = $event;
        }
    }
    return $events;
}

function parseICalDate(string $value): string
{
    $value = trim($value);
    // Format: 20250315 or 20250315T090000 or 20250315T090000Z
    $clean = preg_replace('/[^0-9]/', '', substr($value, 0, 8));
    if (strlen($clean) >= 8) {
        return substr($clean, 0, 4) . '-' . substr($clean, 4, 2) . '-' . substr($clean, 6, 2);
    }
    return $value;
}

function icalUnesc(string $s): string
{
    $s = str_replace('\\n', "\n", $s);
    $s = str_replace('\\,', ',', $s);
    $s = str_replace('\\;', ';', $s);
    $s = str_replace('\\\\', '\\', $s);
    return trim($s);
}

// ═════════════════════════════════════════════════════════════════════
// UI
// ═════════════════════════════════════════════════════════════════════
$CSRF_TOKEN = CSRFSecure::CreateToken();

PopTable('header', 'iCal Export');
?>
<p>Export school calendar data as iCalendar (.ics) files compatible with Google Calendar, Outlook, and Apple Calendar.</p>

<div class="row" style="margin-top:15px">
    <?php foreach ([
        'events' => ['School Events', 'icon-calendar3', 'Calendar events for this school year'],
        'schooldays' => ['School Days', 'icon-alarm-check', 'Attendance calendar with minutes'],
        'markingperiods' => ['Marking Periods', 'icon-chart', 'Semesters, quarters, grade posting windows'],
    ] as $type => $info): ?>
    <div class="col-md-4" style="margin-bottom:15px">
        <div class="panel panel-body text-center" style="padding:20px">
            <i class="<?php echo $info[1]; ?>" style="font-size:40px;color:#666;display:block;margin-bottom:10px"></i>
            <h5><?php echo $info[0]; ?></h5>
            <p class="text-muted"><?php echo $info[2]; ?></p>
            <form method="POST">
                <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
                <input type="hidden" name="export_type" value="<?php echo $type; ?>">
                <input type="hidden" name="TOKEN" value="<?php echo $CSRF_TOKEN; ?>">
                <?php $CSRF_TOKEN = CSRFSecure::CreateToken(); ?>
                <button type="submit" class="btn btn-primary">
                    <i class="icon-download"></i> Export .ics
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
PopTable('footer');

$CSRF_TOKEN = CSRFSecure::CreateToken();
PopTable('header', 'iCal Import');
?>
<p>Import events from an iCalendar (.ics) file into the school calendar.</p>

<form method="POST" enctype="multipart/form-data" style="margin-top:10px">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="hidden" name="modfunc" value="import">
    <input type="hidden" name="TOKEN" value="<?php echo $CSRF_TOKEN; ?>">
    <div class="form-group">
        <label>Select iCalendar file (.ics)</label>
        <input type="file" name="ics_file" accept=".ics,text/calendar" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-info">
        <i class="icon-upload"></i> Import Events
    </button>
</form>

<?php PopTable('footer'); ?>
