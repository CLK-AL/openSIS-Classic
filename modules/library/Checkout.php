<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');

$school = UserSchool();
$staffId = (int)($_SESSION['STAFF_ID'] ?? 0);

// ── Return book ──────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'return' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $id = (int)optional_param('id', 0, PARAM_INT);
    if ($id > 0) {
        $checkout = DBGet(DBQuery("SELECT book_id FROM library_checkout WHERE id=$id AND status='checked_out'"));
        if (!empty($checkout)) {
            DBQuery("UPDATE library_checkout SET status='returned', return_date='" . date('Y-m-d') . "', returned_by=$staffId WHERE id=$id");
            DBQuery("UPDATE library_books SET available_copies = available_copies + 1 WHERE book_id=" . $checkout[1]['BOOK_ID']);
        }
    }
}

// ── Checkout book ────────────────────────────────────────────────────
if ($_REQUEST['modfunc'] === 'checkout' && CSRFSecure::ValidateToken(optional_param('TOKEN', '', PARAM_RAW))) {
    $bookId = (int)optional_param('book_id', 0, PARAM_INT);
    $borrowerType = optional_param('borrower_type', 'student', PARAM_ALPHA);
    $borrowerId = (int)optional_param('borrower_id', 0, PARAM_INT);
    $dueDate = mysqli_real_escape_string($connection, optional_param('due_date', '', PARAM_RAW));
    $notes = mysqli_real_escape_string($connection, optional_param('notes', '', PARAM_RAW));

    if ($bookId && $borrowerId && $dueDate) {
        $avail = DBGet(DBQuery("SELECT available_copies FROM library_books WHERE book_id=$bookId AND school_id='$school'"));
        if (!empty($avail) && (int)$avail[1]['AVAILABLE_COPIES'] > 0) {
            DBQuery("INSERT INTO library_checkout (book_id, school_id, borrower_type, borrower_id, checkout_date, due_date, status, notes, checked_out_by)
                     VALUES ($bookId, '$school', '$borrowerType', $borrowerId, '" . date('Y-m-d') . "', '$dueDate', 'checked_out', '$notes', $staffId)");
            DBQuery("UPDATE library_books SET available_copies = available_copies - 1 WHERE book_id=$bookId");
            echo '<div class="alert alert-success">Book checked out successfully.</div>';
        } else {
            echo '<div class="alert alert-danger">No copies available.</div>';
        }
    }
}

// ── Active checkouts ─────────────────────────────────────────────────
$checkouts = DBGet(DBQuery("
    SELECT lc.*, lb.title AS book_title, lb.author,
           CASE WHEN lc.borrower_type='student' THEN CONCAT(s.first_name, ' ', s.last_name)
                ELSE CONCAT(st.first_name, ' ', st.last_name) END AS borrower_name
    FROM library_checkout lc
    JOIN library_books lb ON lc.book_id = lb.book_id
    LEFT JOIN students s ON lc.borrower_type='student' AND lc.borrower_id = s.student_id
    LEFT JOIN staff st ON lc.borrower_type='staff' AND lc.borrower_id = st.staff_id
    WHERE lc.school_id='$school' AND lc.status='checked_out'
    ORDER BY lc.due_date
"));

$books = DBGet(DBQuery("SELECT book_id, title, author, available_copies FROM library_books WHERE school_id='$school' AND is_active='Y' AND available_copies > 0 ORDER BY title"));

PopTable('header', 'Checkout Book');
$CSRF = CSRFSecure::CreateToken();
?>
<form method="POST">
    <input type="hidden" name="modname" value="<?php echo htmlspecialchars($_REQUEST['modname']); ?>">
    <input type="hidden" name="modfunc" value="checkout">
    <input type="hidden" name="TOKEN" value="<?php echo $CSRF; ?>">
    <div class="row">
        <div class="col-md-3"><div class="form-group"><label>Book</label>
            <select name="book_id" class="form-control input-sm" required>
                <option value="">-- Select Book --</option>
                <?php foreach ($books as $b): ?>
                <option value="<?php echo $b['BOOK_ID']; ?>"><?php echo htmlspecialchars($b['TITLE']); ?> (<?php echo $b['AVAILABLE_COPIES']; ?> avail)</option>
                <?php endforeach; ?>
            </select></div></div>
        <div class="col-md-2"><div class="form-group"><label>Borrower Type</label>
            <select name="borrower_type" class="form-control input-sm">
                <option value="student">Student</option>
                <option value="staff">Staff</option>
            </select></div></div>
        <div class="col-md-2"><div class="form-group"><label>Borrower ID</label>
            <input type="number" name="borrower_id" class="form-control input-sm" required></div></div>
        <div class="col-md-2"><div class="form-group"><label>Due Date</label>
            <input type="date" name="due_date" class="form-control input-sm" required value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>"></div></div>
        <div class="col-md-2"><div class="form-group"><label>Notes</label>
            <input type="text" name="notes" class="form-control input-sm"></div></div>
        <div class="col-md-1"><div class="form-group"><label>&nbsp;</label>
            <button type="submit" class="btn btn-primary btn-sm btn-block">Checkout</button></div></div>
    </div>
</form>
<?php PopTable('footer'); ?>

<?php
PopTable('header', 'Active Checkouts');
$columns = ['BOOK_TITLE'=>'Book', 'AUTHOR'=>'Author', 'BORROWER_NAME'=>'Borrower', 'BORROWER_TYPE'=>'Type', 'CHECKOUT_DATE'=>'Checked Out', 'DUE_DATE'=>'Due Date'];
$link['return'] = ['link' => "Modules.php?modname=$_REQUEST[modname]&modfunc=return&TOKEN=" . CSRFSecure::CreateToken(), 'variables' => ['id' => 'ID']];
ListOutput($checkouts, $columns, 'Checkout', 'Checkouts', $link);
PopTable('footer');
?>
