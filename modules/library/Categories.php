<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');

$school = UserSchool();
$staffId = (int)($_SESSION['STAFF_ID'] ?? 0);

if ($_REQUEST['modfunc'] === 'remove' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('id', 0, PARAM_INT);
    if ($id > 0) DBQuery("DELETE FROM library_book_categories WHERE id=$id AND school_id='$school'");
}

if ($_REQUEST['modfunc'] === 'save' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $title = mysqli_real_escape_string($connection, optional_param('title', '', PARAM_RAW));
    $sort = (int)optional_param('sort_order', 0, PARAM_INT);
    if ($title) {
        DBQuery("INSERT INTO library_book_categories (school_id, title, sort_order, updated_by)
                 VALUES ('$school', '$title', $sort, $staffId)");
    }
}

$cats = DBGet(DBQuery("SELECT * FROM library_book_categories WHERE school_id='$school' ORDER BY sort_order, title"));

PopTable('header', 'Book Categories');
$CSRF = CSRFSecure::CreateToken();
?>
<form method="POST" class="form-inline" style="margin-bottom:15px">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="hidden" name="modfunc" value="save">
    <input type="hidden" name="TOKEN" value="<?php echo $CSRF; ?>">
    <input type="text" name="title" class="form-control input-sm" placeholder="Category name" required style="width:250px">
    <input type="number" name="sort_order" class="form-control input-sm" placeholder="Sort" style="width:80px" value="0">
    <button type="submit" class="btn btn-primary btn-sm">Add Category</button>
</form>
<?php
$columns = ['TITLE'=>'Category', 'SORT_ORDER'=>'Sort Order'];
$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&TOKEN=" . CSRFSecure::CreateToken();
$link['remove']['variables'] = array('id' => 'ID');
ListOutput($cats, $columns, 'Category', 'Categories', $link);
PopTable('footer');
?>
