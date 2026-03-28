<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');

$school = UserSchool();

$overdue = DBGet(DBQuery("
    SELECT lc.*, lb.title AS book_title, lb.author, lb.barcode,
           CASE WHEN lc.borrower_type='student' THEN CONCAT(s.first_name, ' ', s.last_name)
                ELSE CONCAT(st.first_name, ' ', st.last_name) END AS borrower_name,
           DATEDIFF('" . date('Y-m-d') . "', lc.due_date) AS days_overdue
    FROM library_checkout lc
    JOIN library_books lb ON lc.book_id = lb.book_id
    LEFT JOIN students s ON lc.borrower_type='student' AND lc.borrower_id = s.student_id
    LEFT JOIN staff st ON lc.borrower_type='staff' AND lc.borrower_id = st.staff_id
    WHERE lc.school_id='$school' AND lc.status='checked_out' AND lc.due_date < '" . date('Y-m-d') . "'
    ORDER BY lc.due_date
"));

PopTable('header', 'Overdue Books');
$columns = ['BOOK_TITLE'=>'Book', 'AUTHOR'=>'Author', 'BORROWER_NAME'=>'Borrower', 'BORROWER_TYPE'=>'Type',
            'CHECKOUT_DATE'=>'Checked Out', 'DUE_DATE'=>'Due Date', 'DAYS_OVERDUE'=>'Days Overdue'];
ListOutput($overdue, $columns, 'Overdue Book', 'Overdue Books');
PopTable('footer');
?>
