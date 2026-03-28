<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');

$school = UserSchool(); $staffId = (int)($_SESSION['STAFF_ID'] ?? 0);
$syear = UserSyear();

// ── Delete ───────────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'remove' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('id', 0, PARAM_INT);
    if ($id > 0) DBQuery("DELETE FROM inventory_student_needs WHERE id=$id AND school_id='$school'");
}

// ── Save need ────────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'save' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $studentId = (int)optional_param('student_id', 0, PARAM_INT);
    $needType = mysqli_real_escape_string($connection, optional_param('need_type', '', PARAM_RAW));
    $priority = mysqli_real_escape_string($connection, optional_param('priority', 'medium', PARAM_RAW));
    $desc = mysqli_real_escape_string($connection, optional_param('description', '', PARAM_RAW));
    $cost = (float)optional_param('estimated_cost', 0, PARAM_NUMBER);
    $notes = mysqli_real_escape_string($connection, optional_param('notes', '', PARAM_RAW));

    if ($studentId && $needType) {
        DBQuery("INSERT INTO inventory_student_needs (school_id, student_id, need_type, priority, description,
                 estimated_cost, status, date_identified, notes, updated_by)
                 VALUES ('$school', $studentId, '$needType', '$priority', '$desc', $cost,
                 'open', '" . date('Y-m-d') . "', '$notes', $staffId)");
        echo '<div class="alert alert-success">Need recorded.</div>';
    }
}

// ── Fulfill from donation ────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'fulfill' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('id', 0, PARAM_INT);
    $amount = (float)optional_param('funded_amount', 0, PARAM_NUMBER);
    $donationId = (int)optional_param('donation_id', 0, PARAM_INT);
    if ($id > 0) {
        DBQuery("UPDATE inventory_student_needs SET status='fulfilled', funded_amount=$amount,
                 funded_by_donation_id=" . ($donationId ?: 'NULL') . ",
                 date_fulfilled='" . date('Y-m-d') . "', updated_by=$staffId
                 WHERE id=$id AND school_id='$school'");
        echo '<div class="alert alert-success">Need marked as fulfilled.</div>';
    }
}

// ── Filter ───────────────────────────────────────────────────────────
$filterStatus = optional_param('filter_status', 'open', PARAM_RAW);
$where = "n.school_id='$school'";
if ($filterStatus) $where .= " AND n.status='" . mysqli_real_escape_string($connection, $filterStatus) . "'";

$needs = DBGet(DBQuery("
    SELECT n.*, CONCAT(s.first_name, ' ', s.last_name) AS student_name, s.student_id AS sid
    FROM inventory_student_needs n
    JOIN students s ON n.student_id = s.student_id
    WHERE $where ORDER BY
        CASE n.priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 END,
        n.date_identified DESC
    LIMIT 200
"));

// Stats
$stats = DBGet(DBQuery("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status='open' THEN 1 ELSE 0 END) AS open_needs,
    SUM(CASE WHEN status='fulfilled' THEN 1 ELSE 0 END) AS fulfilled,
    SUM(CASE WHEN priority='critical' AND status='open' THEN 1 ELSE 0 END) AS critical,
    SUM(CASE WHEN status='open' THEN estimated_cost ELSE 0 END) AS funding_needed,
    SUM(funded_amount) AS total_funded
    FROM inventory_student_needs WHERE school_id='$school'"));

// Available donations for fulfillment
$availDonations = DBGet(DBQuery("SELECT id, title, donor_name, monetary_value FROM inventory_donations
    WHERE school_id='$school' AND donation_type='money' AND monetary_value > 0 ORDER BY donation_date DESC LIMIT 50"));

$needTypes = ['Books', 'Uniform', 'School Supplies', 'Lunch/Meals', 'Transportation', 'Medical', 'Glasses/Vision',
              'Shoes/Clothing', 'Tuition', 'Tutoring', 'Technology', 'Sports Equipment', 'Field Trip Fee', 'Other'];

PopTable('header', 'Student Needs & Aid');
?>
<div class="row" style="margin-bottom:15px">
    <div class="col-md-2"><div class="panel panel-body text-center text-danger"><h4><?php echo $stats[1]['CRITICAL'] ?? 0; ?></h4><small>Critical</small></div></div>
    <div class="col-md-2"><div class="panel panel-body text-center text-warning"><h4><?php echo $stats[1]['OPEN_NEEDS'] ?? 0; ?></h4><small>Open Needs</small></div></div>
    <div class="col-md-2"><div class="panel panel-body text-center text-success"><h4><?php echo $stats[1]['FULFILLED'] ?? 0; ?></h4><small>Fulfilled</small></div></div>
    <div class="col-md-3"><div class="panel panel-body text-center text-danger"><h4>$<?php echo number_format((float)($stats[1]['FUNDING_NEEDED'] ?? 0), 2); ?></h4><small>Funding Needed</small></div></div>
    <div class="col-md-3"><div class="panel panel-body text-center text-success"><h4>$<?php echo number_format((float)($stats[1]['TOTAL_FUNDED'] ?? 0), 2); ?></h4><small>Total Funded</small></div></div>
</div>

<form method="GET" class="form-inline" style="margin-bottom:10px">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <label>Status:</label>
    <select name="filter_status" class="form-control input-sm" style="width:130px;margin:0 10px">
        <?php foreach (['open','in_progress','fulfilled','cancelled'] as $st): ?>
        <option <?php echo $filterStatus === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
        <?php endforeach; ?>
        <option value="">All</option>
    </select>
    <button type="submit" class="btn btn-default btn-sm">Filter</button>
</form>

<?php $CSRF = CSRFSecure::CreateToken(); ?>
<div class="panel panel-body" style="background:#f9f9f9;margin-bottom:15px">
<h6>Tag Student Need</h6>
<form method="POST">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="hidden" name="modfunc" value="save">
    <input type="hidden" name="TOKEN" value="<?php echo $CSRF; ?>">
    <div class="row">
        <div class="col-md-2"><div class="form-group"><label>Student ID *</label>
            <input type="number" name="student_id" class="form-control input-sm" required></div></div>
        <div class="col-md-2"><div class="form-group"><label>Need Type *</label>
            <select name="need_type" class="form-control input-sm">
            <?php foreach ($needTypes as $nt): ?><option><?php echo $nt; ?></option><?php endforeach; ?>
            </select></div></div>
        <div class="col-md-2"><div class="form-group"><label>Priority</label>
            <select name="priority" class="form-control input-sm">
            <option value="critical" style="color:red">Critical</option>
            <option value="high">High</option>
            <option value="medium" selected>Medium</option>
            <option value="low">Low</option>
            </select></div></div>
        <div class="col-md-2"><div class="form-group"><label>Est. Cost</label>
            <input type="number" name="estimated_cost" class="form-control input-sm" step="0.01" value="0"></div></div>
        <div class="col-md-4"><div class="form-group"><label>Description</label>
            <input type="text" name="description" class="form-control input-sm" placeholder="e.g. Needs winter coat, size M"></div></div>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Tag Need</button>
</form>
</div>

<?php if (!empty($needs)): ?>
<table class="table table-bordered table-condensed table-striped">
<thead><tr>
    <th>Student</th><th>Need</th><th>Priority</th><th>Description</th>
    <th>Est. Cost</th><th>Funded</th><th>Status</th><th>Date</th><th>Action</th>
</tr></thead>
<tbody>
<?php foreach ($needs as $n):
    $priColor = ['critical'=>'danger','high'=>'warning','medium'=>'info','low'=>'default'][$n['PRIORITY']] ?? 'default';
?>
<tr>
    <td><strong><?php echo htmlspecialchars($n['STUDENT_NAME']); ?></strong><br><small>ID: <?php echo $n['SID']; ?></small></td>
    <td><?php echo htmlspecialchars($n['NEED_TYPE']); ?></td>
    <td><span class="label label-<?php echo $priColor; ?>"><?php echo ucfirst($n['PRIORITY']); ?></span></td>
    <td><?php echo htmlspecialchars($n['DESCRIPTION'] ?? ''); ?></td>
    <td>$<?php echo number_format((float)$n['ESTIMATED_COST'], 2); ?></td>
    <td>$<?php echo number_format((float)$n['FUNDED_AMOUNT'], 2); ?></td>
    <td><?php echo $n['STATUS']; ?></td>
    <td><?php echo $n['DATE_IDENTIFIED']; ?></td>
    <td>
        <?php if ($n['STATUS'] === 'open'): ?>
        <form method="POST" class="form-inline" style="display:inline">
            <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
            <input type="hidden" name="modfunc" value="fulfill">
            <input type="hidden" name="id" value="<?php echo $n['ID']; ?>">
            <input type="hidden" name="TOKEN" value="<?php echo $CSRF; ?>"><?php $CSRF = CSRFSecure::CreateToken(); ?>
            <input type="number" name="funded_amount" class="form-control input-sm" step="0.01" style="width:70px" value="<?php echo $n['ESTIMATED_COST']; ?>">
            <select name="donation_id" class="form-control input-sm" style="width:140px">
                <option value="">-- Link Donation --</option>
                <?php foreach ($availDonations as $d): ?>
                <option value="<?php echo $d['ID']; ?>"><?php echo htmlspecialchars(substr($d['TITLE'],0,25)); ?> ($<?php echo $d['MONETARY_VALUE']; ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-xs btn-success" title="Mark Fulfilled"><i class="icon-checkmark3"></i></button>
        </form>
        <?php else: echo $n['DATE_FULFILLED'] ?? ''; endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<div class="alert alert-info">No <?php echo $filterStatus ?: ''; ?> student needs found.</div>
<?php endif; ?>

<?php PopTable('footer'); ?>
