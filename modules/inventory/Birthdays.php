<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');
require_once 'functions/ICalFnc.php';

$school = UserSchool(); $staffId = (int)($_SESSION['STAFF_ID'] ?? 0);
$syear = UserSyear();

// ── Export iCal ──────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'ical') {
    $bdays = DBGet(DBQuery("
        SELECT s.student_id, s.first_name, s.last_name, s.birthdate,
               ib.gift_budget, ib.gift_description
        FROM students s
        JOIN student_enrollment se ON s.student_id = se.student_id
        LEFT JOIN inventory_birthdays ib ON s.student_id = ib.student_id AND ib.school_id='$school'
        WHERE se.syear='$syear' AND se.school_id='$school'
        AND s.birthdate IS NOT NULL AND s.birthdate != '' AND s.birthdate != '0000-00-00'
        AND (se.end_date IS NULL OR se.end_date >= '" . date('Y-m-d') . "')
        ORDER BY MONTH(s.birthdate), DAY(s.birthdate)
    "));
    $cal = icalHeader('openSIS Student Birthdays');
    $thisYear = date('Y');
    foreach ($bdays as $b) {
        $bdate = $b['BIRTHDATE'];
        $thisYearBday = $thisYear . substr($bdate, 4); // Replace year with current year
        $cal .= buildEvent([
            'uid' => 'bday-' . $b['STUDENT_ID'] . '-' . $thisYear . '@opensis',
            'summary' => $b['FIRST_NAME'] . ' ' . $b['LAST_NAME'] . ' Birthday',
            'desc' => ($b['GIFT_DESCRIPTION'] ? 'Gift: ' . $b['GIFT_DESCRIPTION'] : ''),
            'date' => $thisYearBday,
            'allday' => true,
        ]);
    }
    $cal .= "END:VCALENDAR\r\n";
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="birthdays.ics"');
    echo $cal; exit;
}

// ── Save gift/collection ─────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'save' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $studentId = (int)optional_param('student_id', 0, PARAM_INT);
    $budget = (float)optional_param('gift_budget', 0, PARAM_NUMBER);
    $collected = (float)optional_param('collected_amount', 0, PARAM_NUMBER);
    $giftDesc = mysqli_real_escape_string($connection, optional_param('gift_description', '', PARAM_RAW));
    $status = mysqli_real_escape_string($connection, optional_param('status', 'upcoming', PARAM_RAW));
    $eventDate = mysqli_real_escape_string($connection, optional_param('event_date', '', PARAM_RAW));
    $notes = mysqli_real_escape_string($connection, optional_param('notes', '', PARAM_RAW));

    if ($studentId && $eventDate) {
        $existing = DBGet(DBQuery("SELECT id FROM inventory_birthdays WHERE student_id=$studentId AND school_id='$school' AND event_date='$eventDate'"));
        if (!empty($existing)) {
            DBQuery("UPDATE inventory_birthdays SET gift_budget=$budget, collected_amount=$collected,
                     gift_description='$giftDesc', status='$status', notes='$notes', updated_by=$staffId
                     WHERE id=" . $existing[1]['ID']);
        } else {
            DBQuery("INSERT INTO inventory_birthdays (school_id, student_id, event_date, gift_budget, collected_amount, gift_description, status, notes, updated_by)
                     VALUES ('$school', $studentId, '$eventDate', $budget, $collected, '$giftDesc', '$status', '$notes', $staffId)");
        }

        // Record in finance if collected > 0
        if ($collected > 0) {
            $studentName = DBGet(DBQuery("SELECT CONCAT(first_name,' ',last_name) AS name FROM students WHERE student_id=$studentId"));
            $sName = mysqli_real_escape_string($connection, $studentName[1]['NAME'] ?? '');
            DBQuery("INSERT INTO inventory_finance (school_id, category, title, student_id, amount, payment_type, payment_date, status, reference_type, notes, updated_by)
                     VALUES ('$school', 'Birthday Gift', 'Birthday collection for $sName', $studentId, $collected, 'collection', '$eventDate', 'paid', 'birthday', '$notes', $staffId)
                     ON DUPLICATE KEY UPDATE amount = VALUES(amount)");
        }
    }
}

// ── List upcoming birthdays ──────────────────────────────────────────
$month = (int)optional_param('month', (int)date('m'), PARAM_INT);
$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];

$birthdays = DBGet(DBQuery("
    SELECT s.student_id, s.first_name, s.last_name, s.birthdate,
           ib.gift_budget, ib.collected_amount, ib.gift_description, ib.status AS gift_status
    FROM students s
    JOIN student_enrollment se ON s.student_id = se.student_id
    LEFT JOIN inventory_birthdays ib ON s.student_id = ib.student_id AND ib.school_id='$school'
    WHERE se.syear='$syear' AND se.school_id='$school'
    AND s.birthdate IS NOT NULL AND s.birthdate != '' AND s.birthdate != '0000-00-00'
    AND MONTH(s.birthdate) = $month
    AND (se.end_date IS NULL OR se.end_date >= '" . date('Y-m-d') . "')
    ORDER BY DAY(s.birthdate)
"));

PopTable('header', 'Birthdays & Gifts — ' . $monthNames[$month]);
echo '<div style="margin-bottom:10px">';
echo '<a href="Modules.php?modname=' . urlencode($_REQUEST['modname']) . '&modfunc=ical" class="btn btn-info btn-sm"><i class="icon-calendar3"></i> Export iCal</a> ';
for ($m = 1; $m <= 12; $m++) {
    $active = ($m === $month) ? 'btn-primary' : 'btn-default';
    echo '<a href="Modules.php?modname=' . urlencode($_REQUEST['modname']) . '&month=' . $m . '" class="btn ' . $active . ' btn-xs">' . substr($monthNames[$m], 0, 3) . '</a> ';
}
echo '</div>';

$CSRF = CSRFSecure::CreateToken();

if (!empty($birthdays)):
?>
<table class="table table-bordered table-condensed table-striped">
<thead><tr><th>Student</th><th>Birthday</th><th>Age</th><th>Gift Budget</th><th>Collected</th><th>Gift</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php foreach ($birthdays as $b):
    $bdate = $b['BIRTHDATE'];
    $age = date('Y') - (int)substr($bdate, 0, 4);
    $thisYearBday = date('Y') . substr($bdate, 4);
?>
<tr>
    <td><strong><?php echo htmlspecialchars($b['FIRST_NAME'] . ' ' . $b['LAST_NAME']); ?></strong></td>
    <td><?php echo date('M j', strtotime($bdate)); ?></td>
    <td><?php echo $age; ?></td>
    <td><?php echo $b['GIFT_BUDGET'] ? '$' . number_format((float)$b['GIFT_BUDGET'], 2) : '-'; ?></td>
    <td><?php echo $b['COLLECTED_AMOUNT'] ? '$' . number_format((float)$b['COLLECTED_AMOUNT'], 2) : '-'; ?></td>
    <td><?php echo htmlspecialchars($b['GIFT_DESCRIPTION'] ?? ''); ?></td>
    <td><?php echo htmlspecialchars($b['GIFT_STATUS'] ?? 'upcoming'); ?></td>
    <td>
        <form method="POST" class="form-inline" style="display:inline">
            <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
            <input type="hidden" name="modfunc" value="save">
            <input type="hidden" name="student_id" value="<?php echo $b['STUDENT_ID']; ?>">
            <input type="hidden" name="event_date" value="<?php echo $thisYearBday; ?>">
            <input type="hidden" name="TOKEN" value="<?php echo $CSRF; ?>"><?php $CSRF = CSRFSecure::CreateToken(); ?>
            <input type="number" name="gift_budget" class="form-control input-sm" placeholder="Budget" step="0.01" style="width:70px" value="<?php echo $b['GIFT_BUDGET'] ?? ''; ?>">
            <input type="number" name="collected_amount" class="form-control input-sm" placeholder="Collected" step="0.01" style="width:70px" value="<?php echo $b['COLLECTED_AMOUNT'] ?? ''; ?>">
            <input type="text" name="gift_description" class="form-control input-sm" placeholder="Gift" style="width:100px" value="<?php echo htmlspecialchars($b['GIFT_DESCRIPTION'] ?? ''); ?>">
            <select name="status" class="form-control input-sm" style="width:90px">
                <?php foreach (['upcoming','collecting','purchased','delivered'] as $st): ?>
                <option <?php echo ($b['GIFT_STATUS'] ?? 'upcoming') === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-xs btn-success" title="Save"><i class="icon-checkmark3"></i></button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<div class="alert alert-info">No birthdays found for <?php echo $monthNames[$month]; ?>.</div>
<?php endif; ?>

<?php PopTable('footer'); ?>
