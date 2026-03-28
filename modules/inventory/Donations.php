<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');

$school = UserSchool(); $staffId = (int)($_SESSION['STAFF_ID'] ?? 0);

// ── Delete ───────────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'remove' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('id', 0, PARAM_INT);
    if ($id > 0) DBQuery("DELETE FROM inventory_donations WHERE id=$id AND school_id='$school'");
}

// ── Save ─────────────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'save' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $donationType = mysqli_real_escape_string($connection, optional_param('donation_type', 'money', PARAM_RAW));
    $donorName = mysqli_real_escape_string($connection, optional_param('donor_name', '', PARAM_RAW));
    $donorType = mysqli_real_escape_string($connection, optional_param('donor_type', 'parent', PARAM_RAW));
    $donorContact = mysqli_real_escape_string($connection, optional_param('donor_contact', '', PARAM_RAW));
    $title = mysqli_real_escape_string($connection, optional_param('title', '', PARAM_RAW));
    $desc = mysqli_real_escape_string($connection, optional_param('description', '', PARAM_RAW));
    $value = (float)optional_param('monetary_value', 0, PARAM_NUMBER);
    $qty = max(1, (int)optional_param('quantity', 1, PARAM_INT));
    $condition = mysqli_real_escape_string($connection, optional_param('condition_status', 'New', PARAM_RAW));
    $donDate = mysqli_real_escape_string($connection, optional_param('donation_date', date('Y-m-d'), PARAM_RAW));
    $itemType = mysqli_real_escape_string($connection, optional_param('item_type', '', PARAM_RAW));
    $benefStudentId = (int)optional_param('beneficiary_student_id', 0, PARAM_INT);
    $benefTag = mysqli_real_escape_string($connection, optional_param('beneficiary_tag', '', PARAM_RAW));
    $receipt = mysqli_real_escape_string($connection, optional_param('receipt_number', '', PARAM_RAW));
    $notes = mysqli_real_escape_string($connection, optional_param('notes', '', PARAM_RAW));

    if ($donorName && $title) {
        DBQuery("INSERT INTO inventory_donations (school_id, donation_type, donor_name, donor_type, donor_contact,
                 title, description, monetary_value, quantity, condition_status, donation_date, item_type,
                 beneficiary_student_id, beneficiary_tag, receipt_number, status, notes, updated_by)
                 VALUES ('$school', '$donationType', '$donorName', '$donorType', '$donorContact',
                 '$title', '$desc', $value, $qty, '$condition', '$donDate', '$itemType',
                 " . ($benefStudentId ?: 'NULL') . ", " . ($benefTag ? "'$benefTag'" : 'NULL') . ",
                 '$receipt', 'received', '$notes', $staffId)");

        // Auto-record in finance
        if ($value > 0) {
            DBQuery("INSERT INTO inventory_finance (school_id, category, title, description, student_id, amount,
                     payment_type, payment_date, status, reference_type, reference_id, notes, updated_by)
                     VALUES ('$school', 'Donation', 'Donation: $title', 'From: $donorName', " . ($benefStudentId ?: 'NULL') . ",
                     $value, 'donation', '$donDate', 'paid', 'donation', LAST_INSERT_ID(), '$notes', $staffId)");
        }

        // If book donation, auto-add to library
        if ($donationType === 'book' && $title) {
            $libCheck = DBQuery("SHOW TABLES LIKE 'library_books'");
            if ($libCheck && db_fetch_row($libCheck) !== null) {
                DBQuery("INSERT INTO library_books (school_id, title, author, total_copies, available_copies, notes, updated_by)
                         VALUES ('$school', '$title', '$desc', $qty, $qty, 'Donated by $donorName', $staffId)");
                echo '<div class="alert alert-info">Book also added to Library catalog.</div>';
            }
        }

        // If equipment donation, auto-add to inventory
        if ($donationType === 'equipment' && $title) {
            DBQuery("INSERT INTO inventory_equipment (school_id, name, description, condition_status,
                     total_quantity, available_quantity, purchase_cost, notes, updated_by)
                     VALUES ('$school', '$title', '$desc', '$condition', $qty, $qty, $value,
                     'Donated by $donorName', $staffId)");
            echo '<div class="alert alert-info">Equipment also added to Inventory catalog.</div>';
        }

        echo '<div class="alert alert-success">Donation recorded.</div>';
    }
}

// ── Acknowledge ──────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'acknowledge' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('id', 0, PARAM_INT);
    if ($id > 0) DBQuery("UPDATE inventory_donations SET acknowledgement_sent='Y', updated_by=$staffId WHERE id=$id AND school_id='$school'");
}

// ── Filter ───────────────────────────────────────────────────────────
$filterType = optional_param('filter_type', '', PARAM_RAW);
$where = "d.school_id='$school'";
if ($filterType) $where .= " AND d.donation_type='" . mysqli_real_escape_string($connection, $filterType) . "'";

$donations = DBGet(DBQuery("
    SELECT d.*, CONCAT(s.first_name, ' ', s.last_name) AS beneficiary_name
    FROM inventory_donations d
    LEFT JOIN students s ON d.beneficiary_student_id = s.student_id
    WHERE $where ORDER BY d.donation_date DESC LIMIT 200
"));

// Stats
$stats = DBGet(DBQuery("SELECT
    COUNT(*) AS total_donations,
    SUM(monetary_value) AS total_value,
    SUM(CASE WHEN donation_type='money' THEN monetary_value ELSE 0 END) AS money_total,
    SUM(CASE WHEN donation_type='book' THEN quantity ELSE 0 END) AS book_count,
    SUM(CASE WHEN donation_type='equipment' THEN quantity ELSE 0 END) AS equip_count,
    SUM(CASE WHEN acknowledgement_sent='N' THEN 1 ELSE 0 END) AS unacknowledged
    FROM inventory_donations WHERE school_id='$school'"));

PopTable('header', 'Donations');
?>
<div class="row" style="margin-bottom:15px">
    <div class="col-md-2"><div class="panel panel-body text-center"><h4><?php echo $stats[1]['TOTAL_DONATIONS'] ?? 0; ?></h4><small>Total Donations</small></div></div>
    <div class="col-md-2"><div class="panel panel-body text-center text-success"><h4>$<?php echo number_format((float)($stats[1]['TOTAL_VALUE'] ?? 0), 2); ?></h4><small>Total Value</small></div></div>
    <div class="col-md-2"><div class="panel panel-body text-center"><h4>$<?php echo number_format((float)($stats[1]['MONEY_TOTAL'] ?? 0), 2); ?></h4><small>Cash Donations</small></div></div>
    <div class="col-md-2"><div class="panel panel-body text-center"><h4><?php echo $stats[1]['BOOK_COUNT'] ?? 0; ?></h4><small>Books Donated</small></div></div>
    <div class="col-md-2"><div class="panel panel-body text-center"><h4><?php echo $stats[1]['EQUIP_COUNT'] ?? 0; ?></h4><small>Equipment Items</small></div></div>
    <div class="col-md-2"><div class="panel panel-body text-center text-warning"><h4><?php echo $stats[1]['UNACKNOWLEDGED'] ?? 0; ?></h4><small>Pending Thanks</small></div></div>
</div>

<form method="GET" class="form-inline" style="margin-bottom:10px">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <label>Type:</label>
    <select name="filter_type" class="form-control input-sm" style="width:150px;margin:0 10px">
        <option value="">All</option>
        <?php foreach (['money','book','equipment','supplies','clothing','food','other'] as $t): ?>
        <option <?php echo $filterType === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-default btn-sm">Filter</button>
</form>

<?php $CSRF = CSRFSecure::CreateToken(); ?>
<div class="panel panel-body" style="background:#f9f9f9;margin-bottom:15px">
<h6>Record Donation</h6>
<form method="POST">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="hidden" name="modfunc" value="save">
    <input type="hidden" name="TOKEN" value="<?php echo $CSRF; ?>">
    <div class="row">
        <div class="col-md-2"><div class="form-group"><label>Type *</label>
            <select name="donation_type" class="form-control input-sm">
            <option value="money">Money</option><option value="book">Book</option>
            <option value="equipment">Equipment</option><option value="supplies">Supplies</option>
            <option value="clothing">Clothing</option><option value="food">Food</option>
            <option value="other">Other</option>
            </select></div></div>
        <div class="col-md-2"><div class="form-group"><label>Donor Name *</label>
            <input type="text" name="donor_name" class="form-control input-sm" required></div></div>
        <div class="col-md-2"><div class="form-group"><label>Donor Type</label>
            <select name="donor_type" class="form-control input-sm">
            <option>parent</option><option>community</option><option>business</option>
            <option>alumni</option><option>staff</option><option>government</option><option>ngo</option>
            </select></div></div>
        <div class="col-md-3"><div class="form-group"><label>Item / Description *</label>
            <input type="text" name="title" class="form-control input-sm" required placeholder="e.g. 10 Science textbooks"></div></div>
        <div class="col-md-1"><div class="form-group"><label>Value $</label>
            <input type="number" name="monetary_value" class="form-control input-sm" step="0.01" value="0"></div></div>
        <div class="col-md-1"><div class="form-group"><label>Qty</label>
            <input type="number" name="quantity" class="form-control input-sm" min="1" value="1"></div></div>
        <div class="col-md-1"><div class="form-group"><label>Date</label>
            <input type="date" name="donation_date" class="form-control input-sm" value="<?php echo date('Y-m-d'); ?>"></div></div>
    </div>
    <div class="row">
        <div class="col-md-2"><div class="form-group"><label>Condition</label>
            <select name="condition_status" class="form-control input-sm">
            <option>New</option><option>Like New</option><option>Good</option><option>Fair</option><option>Used</option>
            </select></div></div>
        <div class="col-md-2"><div class="form-group"><label>Donor Contact</label>
            <input type="text" name="donor_contact" class="form-control input-sm" placeholder="Email or phone"></div></div>
        <div class="col-md-2"><div class="form-group"><label>For Student ID</label>
            <input type="number" name="beneficiary_student_id" class="form-control input-sm" placeholder="Optional"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Beneficiary Tag</label>
            <select name="beneficiary_tag" class="form-control input-sm">
            <option value="">-- General Pool --</option>
            <option value="needy">Needy Student</option><option value="scholarship">Scholarship</option>
            <option value="orphan">Orphan Fund</option><option value="medical">Medical Aid</option>
            <option value="uniform">Uniform Fund</option><option value="meals">Meal Program</option>
            </select></div></div>
        <div class="col-md-2"><div class="form-group"><label>Receipt #</label>
            <input type="text" name="receipt_number" class="form-control input-sm"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Notes</label>
            <input type="text" name="notes" class="form-control input-sm"></div></div>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Record Donation</button>
</form>
</div>

<?php
$columns = ['DONATION_TYPE'=>'Type', 'DONOR_NAME'=>'Donor', 'TITLE'=>'Item', 'MONETARY_VALUE'=>'Value',
            'QUANTITY'=>'Qty', 'DONATION_DATE'=>'Date', 'BENEFICIARY_NAME'=>'For Student',
            'BENEFICIARY_TAG'=>'Tag', 'ACKNOWLEDGEMENT_SENT'=>'Thanked', 'STATUS'=>'Status'];
$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&TOKEN=" . CSRFSecure::CreateToken();
$link['remove']['variables'] = array('id' => 'ID');
ListOutput($donations, $columns, 'Donation', 'Donations', $link);
PopTable('footer');
?>
