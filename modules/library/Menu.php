<?php
include('../../RedirectModulesInc.php');
$menu['library']['admin'] = array(
    'library/Books.php'=>'Book Catalog',
    'library/Checkout.php'=>'Checkout / Return',
    'library/Overdue.php'=>'Overdue Books',
    1=>'Reports',
    'library/BookReport.php'=>'Inventory Report',
    2=>'Setup',
    'library/Categories.php'=>'Categories',
);

$menu['library']['teacher'] = array(
    'library/Books.php'=>'Book Catalog',
    'library/Checkout.php'=>'Checkout / Return',
    'library/Overdue.php'=>'Overdue Books',
);

$menu['library']['parent'] = array(
    'library/Books.php'=>'Book Catalog',
);
?>
