<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');
require_once 'functions/ICalFnc.php';

$school = UserSchool(); $staffId = (int)($_SESSION['STAFF_ID'] ?? 0);

// ── Export iCal ──────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'ical') {
    $trips = DBGet(DBQuery("SELECT * FROM inventory_field_trips WHERE school_id='$school' ORDER BY trip_date"));
    $cal = icalHeader('openSIS Field Trips');
    foreach ($trips as $t) {
        $cal .= buildEvent([
            'uid' => 'trip-' . $t['ID'] . '@opensis',
            'summary' => $t['TITLE'] . ' - ' . $t['DESTINATION'],
            'desc' => $t['DESCRIPTION'] . ($t['COST_PER_STUDENT'] ? "\nCost: $" . $t['COST_PER_STUDENT'] . '/student' : ''),
            'date' => $t['TRIP_DATE'],
            'end_date' => $t['RETURN_DATE'] ?: $t['TRIP_DATE'],
            'allday' => true,
        ]);
    }
    $cal .= "END:VCALENDAR\r\n";
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="field_trips.ics"');
    echo $cal; exit;
}

// ── Delete ───────────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'remove' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('id', 0, PARAM_INT);
    if ($id > 0) {
        DBQuery("DELETE FROM inventory_trip_students WHERE trip_id=$id");
        DBQuery("DELETE FROM inventory_field_trips WHERE id=$id AND school_id='$school'");
    }
}

// ── Save ─────────────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'save' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('trip_id', 0, PARAM_INT);
    $title = mysqli_real_escape_string($connection, optional_param('title', '', PARAM_RAW));
    $desc = mysqli_real_escape_string($connection, optional_param('description', '', PARAM_RAW));
    $dest = mysqli_real_escape_string($connection, optional_param('destination', '', PARAM_RAW));
    $tripDate = mysqli_real_escape_string($connection, optional_param('trip_date', '', PARAM_RAW));
    $retDate = mysqli_real_escape_string($connection, optional_param('return_date', '', PARAM_RAW));
    $depTime = mysqli_real_escape_string($connection, optional_param('departure_time', '', PARAM_RAW));
    $retTime = mysqli_real_escape_string($connection, optional_param('return_time', '', PARAM_RAW));
    $cost = (float)optional_param('cost_per_student', 0, PARAM_NUMBER);
    $budget = (float)optional_param('total_budget', 0, PARAM_NUMBER);
    $maxSt = (int)optional_param('max_students', 0, PARAM_INT);
    $status = mysqli_real_escape_string($connection, optional_param('status', 'planned', PARAM_RAW));
    $notes = mysqli_real_escape_string($connection, optional_param('notes', '', PARAM_RAW));

    if ($title && $tripDate) {
        if ($id > 0) {
            DBQuery("UPDATE inventory_field_trips SET title='$title', description='$desc', destination='$dest',
                     trip_date='$tripDate', return_date=".($retDate?"'$retDate'":"NULL").", departure_time='$depTime',
                     return_time='$retTime', cost_per_student=$cost, total_budget=$budget,
                     max_students=".($maxSt?:0).", status='$status', notes='$notes', updated_by=$staffId
                     WHERE id=$id AND school_id='$school'");
        } else {
            DBQuery("INSERT INTO inventory_field_trips (school_id, title, description, destination, trip_date, return_date,
                     departure_time, return_time, cost_per_student, total_budget, max_students, status, organizer_id, notes, updated_by)
                     VALUES ('$school','$title','$desc','$dest','$tripDate',".($retDate?"'$retDate'":"NULL").",'$depTime','$retTime',
                     $cost,$budget,".($maxSt?:0).",'$status',$staffId,'$notes',$staffId)");
        }
    }
}

$trips = DBGet(DBQuery("SELECT * FROM inventory_field_trips WHERE school_id='$school' ORDER BY trip_date DESC"));

PopTable('header', 'Field Trips & Tours');
echo '<div style="margin-bottom:10px"><a href="Modules.php?modname=' . urlencode($_REQUEST['modname']) . '&modfunc=ical" class="btn btn-info btn-sm"><i class="icon-calendar3"></i> Export iCal</a></div>';

$CSRF = CSRFSecure::CreateToken();
$editId = (int)optional_param('edit_id', 0, PARAM_INT);
$edit = null;
if ($editId) { $e = DBGet(DBQuery("SELECT * FROM inventory_field_trips WHERE id=$editId AND school_id='$school'")); $edit = $e[1] ?? null; }
?>
<div class="panel panel-body" style="background:#f9f9f9;margin-bottom:15px">
<h6><?php echo $edit ? 'Edit Trip' : 'Add Field Trip'; ?></h6>
<form method="POST">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="hidden" name="modfunc" value="save">
    <input type="hidden" name="trip_id" value="<?php echo $editId; ?>">
    <input type="hidden" name="TOKEN" value="<?php echo $CSRF; ?>">
    <div class="row">
        <div class="col-md-3"><div class="form-group"><label>Title *</label>
            <input type="text" name="title" class="form-control input-sm" required value="<?php echo htmlspecialchars($edit['TITLE'] ?? ''); ?>"></div></div>
        <div class="col-md-3"><div class="form-group"><label>Destination</label>
            <input type="text" name="destination" class="form-control input-sm" value="<?php echo htmlspecialchars($edit['DESTINATION'] ?? ''); ?>"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Trip Date *</label>
            <input type="date" name="trip_date" class="form-control input-sm" required value="<?php echo $edit['TRIP_DATE'] ?? ''; ?>"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Return Date</label>
            <input type="date" name="return_date" class="form-control input-sm" value="<?php echo $edit['RETURN_DATE'] ?? ''; ?>"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Status</label>
            <select name="status" class="form-control input-sm">
            <?php foreach (['planned','confirmed','in_progress','completed','cancelled'] as $st): ?>
            <option <?php echo ($edit['STATUS'] ?? 'planned') === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
            <?php endforeach; ?></select></div></div>
    </div>
    <div class="row">
        <div class="col-md-1"><div class="form-group"><label>Depart</label>
            <input type="time" name="departure_time" class="form-control input-sm" value="<?php echo $edit['DEPARTURE_TIME'] ?? ''; ?>"></div></div>
        <div class="col-md-1"><div class="form-group"><label>Return</label>
            <input type="time" name="return_time" class="form-control input-sm" value="<?php echo $edit['RETURN_TIME'] ?? ''; ?>"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Cost/Student</label>
            <input type="number" name="cost_per_student" class="form-control input-sm" step="0.01" value="<?php echo $edit['COST_PER_STUDENT'] ?? '0'; ?>"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Budget</label>
            <input type="number" name="total_budget" class="form-control input-sm" step="0.01" value="<?php echo $edit['TOTAL_BUDGET'] ?? '0'; ?>"></div></div>
        <div class="col-md-1"><div class="form-group"><label>Max</label>
            <input type="number" name="max_students" class="form-control input-sm" value="<?php echo $edit['MAX_STUDENTS'] ?? ''; ?>"></div></div>
        <div class="col-md-5"><div class="form-group"><label>Notes</label>
            <input type="text" name="notes" class="form-control input-sm" value="<?php echo htmlspecialchars($edit['NOTES'] ?? ''); ?>"></div></div>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><?php echo $edit ? 'Update' : 'Add Trip'; ?></button>
</form>
</div>

<?php
$columns = ['TITLE'=>'Trip', 'DESTINATION'=>'Destination', 'TRIP_DATE'=>'Date', 'RETURN_DATE'=>'Return',
            'COST_PER_STUDENT'=>'$/Student', 'TOTAL_BUDGET'=>'Budget', 'COLLECTED_AMOUNT'=>'Collected', 'STATUS'=>'Status'];
$link['edit']['link'] = "Modules.php?modname=$_REQUEST[modname]&edit_id=";
$link['edit']['variables'] = array('edit_id' => 'ID');
$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&TOKEN=" . CSRFSecure::CreateToken();
$link['remove']['variables'] = array('id' => 'ID');
ListOutput($trips, $columns, 'Field Trip', 'Field Trips', $link);
PopTable('footer');
?>
