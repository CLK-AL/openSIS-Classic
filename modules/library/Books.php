<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');

$school = UserSchool();
$staffId = (int)($_SESSION['STAFF_ID'] ?? 0);

// ── Delete ───────────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'remove' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('id', 0, PARAM_INT);
    if ($id > 0) {
        DBQuery("DELETE FROM library_books WHERE book_id=$id AND school_id='$school'");
    }
}

// ── Save ─────────────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'save' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('book_id', 0, PARAM_INT);
    $title = mysqli_real_escape_string($connection, optional_param('title', '', PARAM_RAW));
    $author = mysqli_real_escape_string($connection, optional_param('author', '', PARAM_RAW));
    $isbn = mysqli_real_escape_string($connection, optional_param('isbn', '', PARAM_RAW));
    $catId = (int)optional_param('category_id', 0, PARAM_INT);
    $publisher = mysqli_real_escape_string($connection, optional_param('publisher', '', PARAM_RAW));
    $year = (int)optional_param('publish_year', 0, PARAM_INT);
    $copies = max(1, (int)optional_param('total_copies', 1, PARAM_INT));
    $location = mysqli_real_escape_string($connection, optional_param('location', '', PARAM_RAW));
    $barcode = mysqli_real_escape_string($connection, optional_param('barcode', '', PARAM_RAW));
    $notes = mysqli_real_escape_string($connection, optional_param('notes', '', PARAM_RAW));

    if ($title) {
        if ($id > 0) {
            DBQuery("UPDATE library_books SET title='$title', author='$author', isbn='$isbn',
                     category_id=" . ($catId ?: 'NULL') . ", publisher='$publisher',
                     publish_year=" . ($year ?: 'NULL') . ", total_copies=$copies,
                     location='$location', barcode='$barcode', notes='$notes', updated_by=$staffId
                     WHERE book_id=$id AND school_id='$school'");
        } else {
            DBQuery("INSERT INTO library_books (school_id, title, author, isbn, category_id, publisher,
                     publish_year, total_copies, available_copies, location, barcode, notes, updated_by)
                     VALUES ('$school', '$title', '$author', '$isbn', " . ($catId ?: 'NULL') . ",
                     '$publisher', " . ($year ?: 'NULL') . ", $copies, $copies, '$location', '$barcode', '$notes', $staffId)");
        }
    }
}

// ── List ─────────────────────────────────────────────────────────────
$search = optional_param('q', '', PARAM_RAW);
$where = "b.school_id='$school' AND b.is_active='Y'";
if ($search) {
    $s = mysqli_real_escape_string($connection, $search);
    $where .= " AND (b.title LIKE '%$s%' OR b.author LIKE '%$s%' OR b.isbn LIKE '%$s%' OR b.barcode LIKE '%$s%')";
}

$books = DBGet(DBQuery("SELECT b.*, c.title AS category_name
    FROM library_books b
    LEFT JOIN library_book_categories c ON b.category_id = c.id
    WHERE $where ORDER BY b.title"));

$categories = DBGet(DBQuery("SELECT * FROM library_book_categories WHERE school_id='$school' ORDER BY sort_order, title"));

// ── UI ───────────────────────────────────────────────────────────────
PopTable('header', 'Book Catalog');

$CSRF = CSRFSecure::CreateToken();
?>
<form method="GET" class="form-inline" style="margin-bottom:15px">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="text" name="q" class="form-control input-sm" placeholder="Search title, author, ISBN..." value="<?php echo htmlspecialchars($search); ?>" style="width:300px">
    <button type="submit" class="btn btn-default btn-sm">Search</button>
    <a href="Modules.php?modname=<?php echo urlencode($_REQUEST['modname']); ?>" class="btn btn-default btn-sm">Reset</a>
</form>

<?php
// Edit form
$editId = (int)optional_param('edit_id', 0, PARAM_INT);
$editBook = null;
if ($editId) {
    $editBook = DBGet(DBQuery("SELECT * FROM library_books WHERE book_id=$editId AND school_id='$school'"));
    $editBook = $editBook[1] ?? null;
}
?>
<div class="panel panel-body" style="background:#f9f9f9;margin-bottom:15px">
<h6><?php echo $editBook ? 'Edit Book' : 'Add New Book'; ?></h6>
<form method="POST" class="form-horizontal">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="hidden" name="modfunc" value="save">
    <input type="hidden" name="book_id" value="<?php echo $editId; ?>">
    <input type="hidden" name="TOKEN" value="<?php echo $CSRF; ?>">
    <div class="row">
        <div class="col-md-4">
            <div class="form-group"><label>Title *</label>
            <input type="text" name="title" class="form-control input-sm" required value="<?php echo htmlspecialchars($editBook['TITLE'] ?? ''); ?>"></div>
        </div>
        <div class="col-md-3">
            <div class="form-group"><label>Author</label>
            <input type="text" name="author" class="form-control input-sm" value="<?php echo htmlspecialchars($editBook['AUTHOR'] ?? ''); ?>"></div>
        </div>
        <div class="col-md-2">
            <div class="form-group"><label>ISBN</label>
            <input type="text" name="isbn" class="form-control input-sm" value="<?php echo htmlspecialchars($editBook['ISBN'] ?? ''); ?>"></div>
        </div>
        <div class="col-md-3">
            <div class="form-group"><label>Category</label>
            <select name="category_id" class="form-control input-sm">
                <option value="">-- None --</option>
                <?php foreach ($categories as $c): ?>
                <option value="<?php echo $c['ID']; ?>" <?php echo ($editBook['CATEGORY_ID'] ?? '') == $c['ID'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['TITLE']); ?></option>
                <?php endforeach; ?>
            </select></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2">
            <div class="form-group"><label>Publisher</label>
            <input type="text" name="publisher" class="form-control input-sm" value="<?php echo htmlspecialchars($editBook['PUBLISHER'] ?? ''); ?>"></div>
        </div>
        <div class="col-md-1">
            <div class="form-group"><label>Year</label>
            <input type="number" name="publish_year" class="form-control input-sm" value="<?php echo $editBook['PUBLISH_YEAR'] ?? ''; ?>"></div>
        </div>
        <div class="col-md-1">
            <div class="form-group"><label>Copies</label>
            <input type="number" name="total_copies" class="form-control input-sm" min="1" value="<?php echo $editBook['TOTAL_COPIES'] ?? 1; ?>"></div>
        </div>
        <div class="col-md-2">
            <div class="form-group"><label>Location</label>
            <input type="text" name="location" class="form-control input-sm" value="<?php echo htmlspecialchars($editBook['LOCATION'] ?? ''); ?>"></div>
        </div>
        <div class="col-md-2">
            <div class="form-group"><label>Barcode</label>
            <input type="text" name="barcode" class="form-control input-sm" value="<?php echo htmlspecialchars($editBook['BARCODE'] ?? ''); ?>"></div>
        </div>
        <div class="col-md-4">
            <div class="form-group"><label>Notes</label>
            <input type="text" name="notes" class="form-control input-sm" value="<?php echo htmlspecialchars($editBook['NOTES'] ?? ''); ?>"></div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><?php echo $editBook ? 'Update' : 'Add Book'; ?></button>
    <?php if ($editBook): ?>
    <a href="Modules.php?modname=<?php echo urlencode($_REQUEST['modname']); ?>" class="btn btn-default btn-sm">Cancel</a>
    <?php endif; ?>
</form>
</div>

<?php
$columns = ['TITLE'=>'Title', 'AUTHOR'=>'Author', 'ISBN'=>'ISBN', 'CATEGORY_NAME'=>'Category',
            'TOTAL_COPIES'=>'Total', 'AVAILABLE_COPIES'=>'Available', 'LOCATION'=>'Location', 'BARCODE'=>'Barcode'];
$link['edit']['link'] = "Modules.php?modname=$_REQUEST[modname]&edit_id=";
$link['edit']['variables'] = array('edit_id' => 'BOOK_ID');
$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&TOKEN=" . CSRFSecure::CreateToken();
$link['remove']['variables'] = array('id' => 'BOOK_ID');
ListOutput($books, $columns, 'Book', 'Books', $link);

PopTable('footer');
?>
