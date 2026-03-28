<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');
require_once 'functions/ICalFnc.php';
$school = UserSchool(); $staffId = (int)($_SESSION['STAFF_ID'] ?? 0);

// ── Export iCal with VALARM reminders ─────────────────────────────────
if ($_REQUEST['modfunc'] === 'ical') {
    $cos = DBGet(DBQuery("
        SELECT ic.*, ie.name AS equip_name,
               CASE WHEN ic.borrower_type='student' THEN CONCAT(s.first_name,' ',s.last_name)
                    ELSE CONCAT(st.first_name,' ',st.last_name) END AS borrower_name
        FROM inventory_checkout ic
        JOIN inventory_equipment ie ON ic.equipment_id = ie.equipment_id
        LEFT JOIN students s ON ic.borrower_type='student' AND ic.borrower_id = s.student_id
        LEFT JOIN staff st ON ic.borrower_type='staff' AND ic.borrower_id = st.staff_id
        WHERE ic.school_id='$school' AND ic.status='checked_out' AND ic.due_date IS NOT NULL ORDER BY ic.due_date"));
    $cal = icalHeader('openSIS Equipment Due Dates');
    foreach ($cos as $c) {
        $cal .= buildTodo([
            'uid' => 'eqdue-' . $c['ID'] . '@opensis',
            'summary' => 'Return: ' . $c['EQUIP_NAME'] . ' (x' . $c['QUANTITY'] . ')',
            'desc' => 'Borrowed by ' . $c['BORROWER_NAME'] . "\nPurpose: " . ($c['PURPOSE'] ?? ''),
            'due' => $c['DUE_DATE'],
            'priority' => 1,
            'status' => 'NEEDS-ACTION',
            'alarms' => [1440, 60],
        ]);
        $cal .= buildEvent([
            'uid' => 'eqreturn-' . $c['ID'] . '@opensis',
            'summary' => 'Equipment Due: ' . $c['EQUIP_NAME'],
            'desc' => 'Qty: ' . $c['QUANTITY'] . ' | ' . $c['BORROWER_NAME'],
            'date' => $c['DUE_DATE'],
            'allday' => true,
            'alarms' => [1440, 60],
        ]);
    }
    $cal .= "END:VCALENDAR\r\n";
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="equipment_due_dates.ics"');
    echo $cal; exit;
}

if ($_REQUEST['modfunc'] === 'return' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('id', 0, PARAM_INT);
    if ($id > 0) {
        $co = DBGet(DBQuery("SELECT equipment_id, quantity FROM inventory_checkout WHERE id=$id AND status='checked_out'"));
        if (!empty($co)) {
            DBQuery("UPDATE inventory_checkout SET status='returned', return_date='".date('Y-m-d')."', returned_by=$staffId WHERE id=$id");
            DBQuery("UPDATE inventory_equipment SET available_quantity = available_quantity + ".$co[1]['QUANTITY']." WHERE equipment_id=".$co[1]['EQUIPMENT_ID']);
        }
    }
}

if ($_REQUEST['modfunc'] === 'checkout' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $eqId = (int)optional_param('equipment_id', 0, PARAM_INT);
    $bType = optional_param('borrower_type', 'staff', PARAM_ALPHA);
    $bId = (int)optional_param('borrower_id', 0, PARAM_INT);
    $qty = max(1, (int)optional_param('quantity', 1, PARAM_INT));
    $due = mysqli_real_escape_string($connection, optional_param('due_date', '', PARAM_RAW));
    $purpose = mysqli_real_escape_string($connection, optional_param('purpose', '', PARAM_RAW));

    if ($eqId && $bId) {
        $avail = DBGet(DBQuery("SELECT available_quantity FROM inventory_equipment WHERE equipment_id=$eqId"));
        if (!empty($avail) && (int)$avail[1]['AVAILABLE_QUANTITY'] >= $qty) {
            DBQuery("INSERT INTO inventory_checkout (equipment_id, school_id, borrower_type, borrower_id, quantity, checkout_date, due_date, status, purpose, checked_out_by)
                     VALUES ($eqId, '$school', '$bType', $bId, $qty, '".date('Y-m-d')."', ".($due?"'$due'":"NULL").", 'checked_out', '$purpose', $staffId)");
            DBQuery("UPDATE inventory_equipment SET available_quantity = available_quantity - $qty WHERE equipment_id=$eqId");
            echo '<div class="alert alert-success">Equipment checked out.</div>';
        } else {
            echo '<div class="alert alert-danger">Insufficient quantity available.</div>';
        }
    }
}

$checkouts = DBGet(DBQuery("
    SELECT ic.*, ie.name AS equip_name, ie.serial_number,
           CASE WHEN ic.borrower_type='student' THEN CONCAT(s.first_name,' ',s.last_name)
                ELSE CONCAT(st.first_name,' ',st.last_name) END AS borrower_name
    FROM inventory_checkout ic
    JOIN inventory_equipment ie ON ic.equipment_id = ie.equipment_id
    LEFT JOIN students s ON ic.borrower_type='student' AND ic.borrower_id = s.student_id
    LEFT JOIN staff st ON ic.borrower_type='staff' AND ic.borrower_id = st.staff_id
    WHERE ic.school_id='$school' AND ic.status='checked_out' ORDER BY ic.checkout_date DESC"));

$equip = DBGet(DBQuery("SELECT equipment_id, name, available_quantity FROM inventory_equipment WHERE school_id='$school' AND is_active='Y' AND available_quantity > 0 ORDER BY name"));

PopTable('header', 'Checkout Equipment');
$CSRF = CSRFSecure::CreateToken();
?>
<form method="POST">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="hidden" name="modfunc" value="checkout"><input type="hidden" name="TOKEN" value="<?php echo $CSRF; ?>">
    <div class="row">
        <div class="col-md-3"><div class="form-group"><label>Equipment</label>
            <select name="equipment_id" class="form-control input-sm" required><option value="">-- Select --</option>
            <?php foreach ($equip as $e): ?><option value="<?php echo $e['EQUIPMENT_ID']; ?>"><?php echo htmlspecialchars($e['NAME']); ?> (<?php echo $e['AVAILABLE_QUANTITY']; ?>)</option><?php endforeach; ?>
            </select></div></div>
        <div class="col-md-2"><div class="form-group"><label>Type</label>
            <select name="borrower_type" class="form-control input-sm"><option value="staff">Staff</option><option value="student">Student</option></select></div></div>
        <div class="col-md-1"><div class="form-group"><label>ID</label>
            <input type="number" name="borrower_id" class="form-control input-sm" required></div></div>
        <div class="col-md-1"><div class="form-group"><label>Qty</label>
            <input type="number" name="quantity" class="form-control input-sm" value="1" min="1"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Due Date</label>
            <input type="date" name="due_date" class="form-control input-sm"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Purpose</label>
            <input type="text" name="purpose" class="form-control input-sm"></div></div>
        <div class="col-md-1"><div class="form-group"><label>&nbsp;</label>
            <button type="submit" class="btn btn-primary btn-sm btn-block">Go</button></div></div>
    </div>
</form>
<?php PopTable('footer'); ?>

<?php
echo '<div style="margin:10px 0"><a href="Modules.php?modname=' . urlencode($_REQUEST['modname']) . '&modfunc=ical" class="btn btn-info btn-sm"><i class="icon-calendar3"></i> Export Due Dates (.ics with reminders)</a></div>';

PopTable('header', 'Active Checkouts');
$columns = ['EQUIP_NAME'=>'Equipment', 'BORROWER_NAME'=>'Borrower', 'QUANTITY'=>'Qty', 'CHECKOUT_DATE'=>'Out', 'DUE_DATE'=>'Due', 'PURPOSE'=>'Purpose'];
$link['return'] = ['link' => "Modules.php?modname=$_REQUEST[modname]&modfunc=return&TOKEN=" . CSRFSecure::CreateToken(), 'variables' => ['id' => 'ID']];
ListOutput($checkouts, $columns, 'Checkout', 'Checkouts', $link);
PopTable('footer');
?>
