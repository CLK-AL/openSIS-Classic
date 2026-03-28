<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');

$school = UserSchool();
$staffId = (int)($_SESSION['STAFF_ID'] ?? 0);

if ($_REQUEST['modfunc'] === 'remove' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('id', 0, PARAM_INT);
    if ($id > 0) DBQuery("DELETE FROM inventory_equipment WHERE equipment_id=$id AND school_id='$school'");
}

if ($_REQUEST['modfunc'] === 'save' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('equipment_id', 0, PARAM_INT);
    $name = mysqli_real_escape_string($connection, optional_param('name', '', PARAM_RAW));
    $desc = mysqli_real_escape_string($connection, optional_param('description', '', PARAM_RAW));
    $catId = (int)optional_param('category_id', 0, PARAM_INT);
    $locId = (int)optional_param('location_id', 0, PARAM_INT);
    $serial = mysqli_real_escape_string($connection, optional_param('serial_number', '', PARAM_RAW));
    $asset = mysqli_real_escape_string($connection, optional_param('asset_tag', '', PARAM_RAW));
    $barcode = mysqli_real_escape_string($connection, optional_param('barcode', '', PARAM_RAW));
    $mfr = mysqli_real_escape_string($connection, optional_param('manufacturer', '', PARAM_RAW));
    $model = mysqli_real_escape_string($connection, optional_param('model', '', PARAM_RAW));
    $purchDate = mysqli_real_escape_string($connection, optional_param('purchase_date', '', PARAM_RAW));
    $cost = (float)optional_param('purchase_cost', 0, PARAM_NUMBER);
    $warranty = mysqli_real_escape_string($connection, optional_param('warranty_expiry', '', PARAM_RAW));
    $condition = mysqli_real_escape_string($connection, optional_param('condition_status', 'Good', PARAM_RAW));
    $qty = max(1, (int)optional_param('total_quantity', 1, PARAM_INT));

    if ($name) {
        if ($id > 0) {
            DBQuery("UPDATE inventory_equipment SET name='$name', description='$desc',
                     category_id=" . ($catId ?: 'NULL') . ", location_id=" . ($locId ?: 'NULL') . ",
                     serial_number='$serial', asset_tag='$asset', barcode='$barcode',
                     manufacturer='$mfr', model='$model',
                     purchase_date=" . ($purchDate ? "'$purchDate'" : 'NULL') . ",
                     purchase_cost=" . ($cost ?: 'NULL') . ",
                     warranty_expiry=" . ($warranty ? "'$warranty'" : 'NULL') . ",
                     condition_status='$condition', total_quantity=$qty, updated_by=$staffId
                     WHERE equipment_id=$id AND school_id='$school'");
        } else {
            DBQuery("INSERT INTO inventory_equipment (school_id, name, description, category_id, location_id,
                     serial_number, asset_tag, barcode, manufacturer, model, purchase_date, purchase_cost,
                     warranty_expiry, condition_status, total_quantity, available_quantity, updated_by)
                     VALUES ('$school', '$name', '$desc', " . ($catId ?: 'NULL') . ", " . ($locId ?: 'NULL') . ",
                     '$serial', '$asset', '$barcode', '$mfr', '$model',
                     " . ($purchDate ? "'$purchDate'" : 'NULL') . ", " . ($cost ?: 'NULL') . ",
                     " . ($warranty ? "'$warranty'" : 'NULL') . ", '$condition', $qty, $qty, $staffId)");
        }
    }
}

$search = optional_param('q', '', PARAM_RAW);
$where = "e.school_id='$school' AND e.is_active='Y'";
if ($search) {
    $s = mysqli_real_escape_string($connection, $search);
    $where .= " AND (e.name LIKE '%$s%' OR e.serial_number LIKE '%$s%' OR e.asset_tag LIKE '%$s%' OR e.barcode LIKE '%$s%')";
}

$equipment = DBGet(DBQuery("SELECT e.*, c.title AS category_name, l.title AS location_name
    FROM inventory_equipment e
    LEFT JOIN inventory_categories c ON e.category_id = c.id
    LEFT JOIN inventory_locations l ON e.location_id = l.id
    WHERE $where ORDER BY e.name"));

$categories = DBGet(DBQuery("SELECT * FROM inventory_categories WHERE school_id='$school' ORDER BY sort_order, title"));
$locations = DBGet(DBQuery("SELECT * FROM inventory_locations WHERE school_id='$school' ORDER BY sort_order, title"));

PopTable('header', 'Lab Equipment Catalog');
$CSRF = CSRFSecure::CreateToken();
?>
<form method="GET" class="form-inline" style="margin-bottom:15px">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="text" name="q" class="form-control input-sm" placeholder="Search name, serial, tag..." value="<?php echo htmlspecialchars($search); ?>" style="width:300px">
    <button type="submit" class="btn btn-default btn-sm">Search</button>
</form>

<?php
$editId = (int)optional_param('edit_id', 0, PARAM_INT);
$edit = null;
if ($editId) { $e = DBGet(DBQuery("SELECT * FROM inventory_equipment WHERE equipment_id=$editId AND school_id='$school'")); $edit = $e[1] ?? null; }
?>
<div class="panel panel-body" style="background:#f9f9f9;margin-bottom:15px">
<h6><?php echo $edit ? 'Edit Equipment' : 'Add New Equipment'; ?></h6>
<form method="POST">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="hidden" name="modfunc" value="save">
    <input type="hidden" name="equipment_id" value="<?php echo $editId; ?>">
    <input type="hidden" name="TOKEN" value="<?php echo $CSRF; ?>">
    <div class="row">
        <div class="col-md-3"><div class="form-group"><label>Name *</label>
            <input type="text" name="name" class="form-control input-sm" required value="<?php echo htmlspecialchars($edit['NAME'] ?? ''); ?>"></div></div>
        <div class="col-md-3"><div class="form-group"><label>Category</label>
            <select name="category_id" class="form-control input-sm"><option value="">-- None --</option>
            <?php foreach ($categories as $c): ?><option value="<?php echo $c['ID']; ?>" <?php echo ($edit['CATEGORY_ID'] ?? '') == $c['ID'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['TITLE']); ?></option><?php endforeach; ?>
            </select></div></div>
        <div class="col-md-3"><div class="form-group"><label>Location</label>
            <select name="location_id" class="form-control input-sm"><option value="">-- None --</option>
            <?php foreach ($locations as $l): ?><option value="<?php echo $l['ID']; ?>" <?php echo ($edit['LOCATION_ID'] ?? '') == $l['ID'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($l['TITLE']); ?></option><?php endforeach; ?>
            </select></div></div>
        <div class="col-md-3"><div class="form-group"><label>Condition</label>
            <select name="condition_status" class="form-control input-sm">
            <?php foreach (['New','Excellent','Good','Fair','Poor','Broken','Retired'] as $cs): ?><option <?php echo ($edit['CONDITION_STATUS'] ?? 'Good') === $cs ? 'selected' : ''; ?>><?php echo $cs; ?></option><?php endforeach; ?>
            </select></div></div>
    </div>
    <div class="row">
        <div class="col-md-2"><div class="form-group"><label>Manufacturer</label>
            <input type="text" name="manufacturer" class="form-control input-sm" value="<?php echo htmlspecialchars($edit['MANUFACTURER'] ?? ''); ?>"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Model</label>
            <input type="text" name="model" class="form-control input-sm" value="<?php echo htmlspecialchars($edit['MODEL'] ?? ''); ?>"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Serial #</label>
            <input type="text" name="serial_number" class="form-control input-sm" value="<?php echo htmlspecialchars($edit['SERIAL_NUMBER'] ?? ''); ?>"></div></div>
        <div class="col-md-1"><div class="form-group"><label>Asset Tag</label>
            <input type="text" name="asset_tag" class="form-control input-sm" value="<?php echo htmlspecialchars($edit['ASSET_TAG'] ?? ''); ?>"></div></div>
        <div class="col-md-1"><div class="form-group"><label>Qty</label>
            <input type="number" name="total_quantity" class="form-control input-sm" min="1" value="<?php echo $edit['TOTAL_QUANTITY'] ?? 1; ?>"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Purchase Date</label>
            <input type="date" name="purchase_date" class="form-control input-sm" value="<?php echo $edit['PURCHASE_DATE'] ?? ''; ?>"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Cost</label>
            <input type="number" name="purchase_cost" class="form-control input-sm" step="0.01" value="<?php echo $edit['PURCHASE_COST'] ?? ''; ?>"></div></div>
    </div>
    <div class="row">
        <div class="col-md-2"><div class="form-group"><label>Warranty Expiry</label>
            <input type="date" name="warranty_expiry" class="form-control input-sm" value="<?php echo $edit['WARRANTY_EXPIRY'] ?? ''; ?>"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Barcode</label>
            <input type="text" name="barcode" class="form-control input-sm" value="<?php echo htmlspecialchars($edit['BARCODE'] ?? ''); ?>"></div></div>
        <div class="col-md-8"><div class="form-group"><label>Description</label>
            <input type="text" name="description" class="form-control input-sm" value="<?php echo htmlspecialchars($edit['DESCRIPTION'] ?? ''); ?>"></div></div>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><?php echo $edit ? 'Update' : 'Add Equipment'; ?></button>
</form>
</div>

<?php
$columns = ['NAME'=>'Equipment', 'CATEGORY_NAME'=>'Category', 'LOCATION_NAME'=>'Location',
            'SERIAL_NUMBER'=>'Serial #', 'CONDITION_STATUS'=>'Condition',
            'TOTAL_QUANTITY'=>'Total', 'AVAILABLE_QUANTITY'=>'Available'];
$link['edit']['link'] = "Modules.php?modname=$_REQUEST[modname]&edit_id=";
$link['edit']['variables'] = array('edit_id' => 'EQUIPMENT_ID');
$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&TOKEN=" . CSRFSecure::CreateToken();
$link['remove']['variables'] = array('id' => 'EQUIPMENT_ID');
ListOutput($equipment, $columns, 'Item', 'Items', $link);
PopTable('footer');
?>
