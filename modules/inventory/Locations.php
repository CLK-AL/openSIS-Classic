<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');
$school = UserSchool(); $staffId = (int)($_SESSION['STAFF_ID'] ?? 0);

if ($_REQUEST['modfunc'] === 'remove' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('id', 0, PARAM_INT);
    if ($id > 0) DBQuery("DELETE FROM inventory_locations WHERE id=$id AND school_id='$school'");
}
if ($_REQUEST['modfunc'] === 'save' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $title = mysqli_real_escape_string($connection, optional_param('title', '', PARAM_RAW));
    $room = mysqli_real_escape_string($connection, optional_param('room', '', PARAM_RAW));
    $building = mysqli_real_escape_string($connection, optional_param('building', '', PARAM_RAW));
    if ($title) DBQuery("INSERT INTO inventory_locations (school_id, title, room, building, updated_by) VALUES ('$school', '$title', '$room', '$building', $staffId)");
}

$locs = DBGet(DBQuery("SELECT * FROM inventory_locations WHERE school_id='$school' ORDER BY sort_order, title"));
PopTable('header', 'Equipment Locations');
$CSRF = CSRFSecure::CreateToken();
?>
<form method="POST" class="form-inline" style="margin-bottom:15px">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="hidden" name="modfunc" value="save"><input type="hidden" name="TOKEN" value="<?php echo $CSRF; ?>">
    <input type="text" name="title" class="form-control input-sm" placeholder="Location name" required style="width:200px">
    <input type="text" name="room" class="form-control input-sm" placeholder="Room" style="width:100px">
    <input type="text" name="building" class="form-control input-sm" placeholder="Building" style="width:150px">
    <button type="submit" class="btn btn-primary btn-sm">Add Location</button>
</form>
<?php
$columns = ['TITLE'=>'Location', 'ROOM'=>'Room', 'BUILDING'=>'Building'];
$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&TOKEN=" . CSRFSecure::CreateToken();
$link['remove']['variables'] = array('id' => 'ID');
ListOutput($locs, $columns, 'Location', 'Locations', $link);
PopTable('footer');
?>
