<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');
$school = UserSchool(); $staffId = (int)($_SESSION['STAFF_ID'] ?? 0);

if ($_REQUEST['modfunc'] === 'save' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $eqId = (int)optional_param('equipment_id', 0, PARAM_INT);
    $mDate = mysqli_real_escape_string($connection, optional_param('maintenance_date', date('Y-m-d'), PARAM_RAW));
    $mType = mysqli_real_escape_string($connection, optional_param('maintenance_type', '', PARAM_RAW));
    $desc = mysqli_real_escape_string($connection, optional_param('description', '', PARAM_RAW));
    $cost = (float)optional_param('cost', 0, PARAM_NUMBER);
    $by = mysqli_real_escape_string($connection, optional_param('performed_by', '', PARAM_RAW));
    $next = mysqli_real_escape_string($connection, optional_param('next_maintenance', '', PARAM_RAW));
    if ($eqId) {
        DBQuery("INSERT INTO inventory_maintenance (equipment_id, school_id, maintenance_date, maintenance_type, description, cost, performed_by, next_maintenance, updated_by)
                 VALUES ($eqId, '$school', '$mDate', '$mType', '$desc', ".($cost?:"NULL").", '$by', ".($next?"'$next'":"NULL").", $staffId)");
    }
}

$logs = DBGet(DBQuery("SELECT im.*, ie.name AS equip_name FROM inventory_maintenance im
    JOIN inventory_equipment ie ON im.equipment_id = ie.equipment_id
    WHERE im.school_id='$school' ORDER BY im.maintenance_date DESC LIMIT 100"));
$equip = DBGet(DBQuery("SELECT equipment_id, name FROM inventory_equipment WHERE school_id='$school' AND is_active='Y' ORDER BY name"));

PopTable('header', 'Maintenance Log');
$CSRF = CSRFSecure::CreateToken();
?>
<form method="POST" style="margin-bottom:15px">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="hidden" name="modfunc" value="save"><input type="hidden" name="TOKEN" value="<?php echo $CSRF; ?>">
    <div class="row">
        <div class="col-md-2"><div class="form-group"><label>Equipment</label>
            <select name="equipment_id" class="form-control input-sm" required><option value="">--</option>
            <?php foreach ($equip as $e): ?><option value="<?php echo $e['EQUIPMENT_ID']; ?>"><?php echo htmlspecialchars($e['NAME']); ?></option><?php endforeach; ?>
            </select></div></div>
        <div class="col-md-2"><div class="form-group"><label>Date</label>
            <input type="date" name="maintenance_date" class="form-control input-sm" value="<?php echo date('Y-m-d'); ?>"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Type</label>
            <select name="maintenance_type" class="form-control input-sm">
            <option>Preventive</option><option>Repair</option><option>Calibration</option><option>Inspection</option><option>Replacement</option>
            </select></div></div>
        <div class="col-md-2"><div class="form-group"><label>Performed By</label>
            <input type="text" name="performed_by" class="form-control input-sm"></div></div>
        <div class="col-md-1"><div class="form-group"><label>Cost</label>
            <input type="number" name="cost" class="form-control input-sm" step="0.01"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Next Due</label>
            <input type="date" name="next_maintenance" class="form-control input-sm"></div></div>
        <div class="col-md-1"><div class="form-group"><label>&nbsp;</label>
            <button type="submit" class="btn btn-primary btn-sm btn-block">Log</button></div></div>
    </div>
    <div class="form-group"><label>Description</label>
        <input type="text" name="description" class="form-control input-sm" placeholder="Work performed..."></div>
</form>
<?php
$columns = ['EQUIP_NAME'=>'Equipment', 'MAINTENANCE_DATE'=>'Date', 'MAINTENANCE_TYPE'=>'Type', 'DESCRIPTION'=>'Description', 'COST'=>'Cost', 'PERFORMED_BY'=>'By', 'NEXT_MAINTENANCE'=>'Next Due'];
ListOutput($logs, $columns, 'Log Entry', 'Log Entries');
PopTable('footer');
?>
