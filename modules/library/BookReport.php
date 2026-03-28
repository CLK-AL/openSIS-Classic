<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');

$school = UserSchool();

$stats = DBGet(DBQuery("SELECT COUNT(*) AS total_books, SUM(total_copies) AS total_copies,
    SUM(available_copies) AS available_copies, SUM(total_copies - available_copies) AS checked_out
    FROM library_books WHERE school_id='$school' AND is_active='Y'"));

$byCat = DBGet(DBQuery("SELECT COALESCE(c.title, 'Uncategorized') AS category, COUNT(*) AS book_count,
    SUM(b.total_copies) AS total_copies
    FROM library_books b LEFT JOIN library_book_categories c ON b.category_id = c.id
    WHERE b.school_id='$school' AND b.is_active='Y'
    GROUP BY c.title ORDER BY book_count DESC"));

PopTable('header', 'Library Inventory Report');
?>
<div class="row" style="margin-bottom:15px">
    <div class="col-md-3"><div class="panel panel-body text-center"><h4><?php echo $stats[1]['TOTAL_BOOKS'] ?? 0; ?></h4><small>Book Titles</small></div></div>
    <div class="col-md-3"><div class="panel panel-body text-center"><h4><?php echo $stats[1]['TOTAL_COPIES'] ?? 0; ?></h4><small>Total Copies</small></div></div>
    <div class="col-md-3"><div class="panel panel-body text-center"><h4><?php echo $stats[1]['AVAILABLE_COPIES'] ?? 0; ?></h4><small>Available</small></div></div>
    <div class="col-md-3"><div class="panel panel-body text-center text-danger"><h4><?php echo $stats[1]['CHECKED_OUT'] ?? 0; ?></h4><small>Checked Out</small></div></div>
</div>
<?php
$columns = ['CATEGORY'=>'Category', 'BOOK_COUNT'=>'Books', 'TOTAL_COPIES'=>'Copies'];
ListOutput($byCat, $columns, 'Category', 'Categories');
PopTable('footer');
?>
