<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');

$school = UserSchool(); $staffId = (int)($_SESSION['STAFF_ID'] ?? 0);

// ── Delete ───────────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'remove' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('id', 0, PARAM_INT);
    if ($id > 0) DBQuery("DELETE FROM inventory_finance WHERE id=$id AND school_id='$school'");
}

// ── Save ─────────────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'save' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $cat = mysqli_real_escape_string($connection, optional_param('category', 'General', PARAM_RAW));
    $title = mysqli_real_escape_string($connection, optional_param('title', '', PARAM_RAW));
    $desc = mysqli_real_escape_string($connection, optional_param('description', '', PARAM_RAW));
    $studentId = (int)optional_param('student_id', 0, PARAM_INT);
    $amount = (float)optional_param('amount', 0, PARAM_NUMBER);
    $payType = mysqli_real_escape_string($connection, optional_param('payment_type', 'cash', PARAM_RAW));
    $payDate = mysqli_real_escape_string($connection, optional_param('payment_date', date('Y-m-d'), PARAM_RAW));
    $dueDate = mysqli_real_escape_string($connection, optional_param('due_date', '', PARAM_RAW));
    $status = mysqli_real_escape_string($connection, optional_param('status', 'paid', PARAM_RAW));
    $receipt = mysqli_real_escape_string($connection, optional_param('receipt_number', '', PARAM_RAW));
    $notes = mysqli_real_escape_string($connection, optional_param('notes', '', PARAM_RAW));

    if ($title && $amount) {
        DBQuery("INSERT INTO inventory_finance (school_id, category, title, description, student_id, amount,
                 payment_type, payment_date, due_date, status, receipt_number, notes, updated_by)
                 VALUES ('$school', '$cat', '$title', '$desc', ".($studentId ?: 'NULL').", $amount,
                 '$payType', '$payDate', ".($dueDate ? "'$dueDate'" : 'NULL').", '$status', '$receipt', '$notes', $staffId)");
    }
}

// ── Filter ───────────────────────────────────────────────────────────
$filterCat = optional_param('filter_cat', '', PARAM_RAW);
$where = "f.school_id='$school'";
if ($filterCat) $where .= " AND f.category='" . mysqli_real_escape_string($connection, $filterCat) . "'";

$finance = DBGet(DBQuery("
    SELECT f.*, CONCAT(s.first_name, ' ', s.last_name) AS student_name
    FROM inventory_finance f
    LEFT JOIN students s ON f.student_id = s.student_id
    WHERE $where ORDER BY f.payment_date DESC LIMIT 200
"));

$categories = ['Field Trip', 'Birthday Gift', 'Lab Fee', 'Book Fee', 'Activity Fee', 'Donation', 'Supplies', 'General'];

PopTable('header', 'Collections & Payments');
$CSRF = CSRFSecure::CreateToken();
?>
<form method="GET" class="form-inline" style="margin-bottom:10px">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <label>Category:</label>
    <select name="filter_cat" class="form-control input-sm" style="width:150px;margin:0 10px">
        <option value="">All</option>
        <?php foreach ($categories as $c): ?><option <?php echo $filterCat === $c ? 'selected' : ''; ?>><?php echo $c; ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-default btn-sm">Filter</button>
</form>

<div class="panel panel-body" style="background:#f9f9f9;margin-bottom:15px">
<h6>Record Payment / Collection</h6>
<form method="POST">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="hidden" name="modfunc" value="save">
    <input type="hidden" name="TOKEN" value="<?php echo $CSRF; ?>">
    <div class="row">
        <div class="col-md-2"><div class="form-group"><label>Category</label>
            <select name="category" class="form-control input-sm">
            <?php foreach ($categories as $c): ?><option><?php echo $c; ?></option><?php endforeach; ?>
            </select></div></div>
        <div class="col-md-3"><div class="form-group"><label>Title *</label>
            <input type="text" name="title" class="form-control input-sm" required placeholder="e.g. Museum trip fee"></div></div>
        <div class="col-md-1"><div class="form-group"><label>Amount *</label>
            <input type="number" name="amount" class="form-control input-sm" step="0.01" required></div></div>
        <div class="col-md-1"><div class="form-group"><label>Student ID</label>
            <input type="number" name="student_id" class="form-control input-sm" placeholder="Optional"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Payment Type</label>
            <select name="payment_type" class="form-control input-sm">
            <option>cash</option><option>check</option><option>card</option><option>transfer</option><option>online</option><option>collection</option>
            </select></div></div>
        <div class="col-md-1"><div class="form-group"><label>Date</label>
            <input type="date" name="payment_date" class="form-control input-sm" value="<?php echo date('Y-m-d'); ?>"></div></div>
        <div class="col-md-1"><div class="form-group"><label>Status</label>
            <select name="status" class="form-control input-sm">
            <option>paid</option><option>pending</option><option>overdue</option><option>refunded</option>
            </select></div></div>
        <div class="col-md-1"><div class="form-group"><label>&nbsp;</label>
            <button type="submit" class="btn btn-primary btn-sm btn-block">Add</button></div></div>
    </div>
    <div class="row">
        <div class="col-md-2"><div class="form-group"><label>Receipt #</label>
            <input type="text" name="receipt_number" class="form-control input-sm"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Due Date</label>
            <input type="date" name="due_date" class="form-control input-sm"></div></div>
        <div class="col-md-8"><div class="form-group"><label>Notes</label>
            <input type="text" name="notes" class="form-control input-sm"></div></div>
    </div>
</form>
</div>

<?php
$columns = ['CATEGORY'=>'Category', 'TITLE'=>'Title', 'STUDENT_NAME'=>'Student', 'AMOUNT'=>'Amount',
            'PAYMENT_TYPE'=>'Type', 'PAYMENT_DATE'=>'Date', 'STATUS'=>'Status', 'RECEIPT_NUMBER'=>'Receipt'];
$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&TOKEN=" . CSRFSecure::CreateToken();
$link['remove']['variables'] = array('id' => 'ID');
ListOutput($finance, $columns, 'Payment', 'Payments', $link);
PopTable('footer');
?>
