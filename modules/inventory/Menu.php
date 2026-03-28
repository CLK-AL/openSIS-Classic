<?php
include('../../RedirectModulesInc.php');
$menu['inventory']['admin'] = array(
    'inventory/Equipment.php'=>'Equipment Catalog',
    'inventory/Checkout.php'=>'Checkout / Return',
    'inventory/Maintenance.php'=>'Maintenance Log',
    1=>'Activities',
    'inventory/FieldTrips.php'=>'Field Trips & Tours',
    'inventory/Birthdays.php'=>'Birthdays & Gifts',
    2=>'Finance',
    'inventory/Finance.php'=>'Collections & Payments',
    'inventory/FinanceReport.php'=>'Finance Report',
    3=>'Reports',
    'inventory/EquipmentReport.php'=>'Inventory Report',
    4=>'Setup',
    'inventory/Categories.php'=>'Categories',
    'inventory/Locations.php'=>'Locations',
);

$menu['inventory']['teacher'] = array(
    'inventory/Equipment.php'=>'Equipment Catalog',
    'inventory/Checkout.php'=>'Checkout / Return',
    'inventory/FieldTrips.php'=>'Field Trips & Tours',
    'inventory/Birthdays.php'=>'Birthdays & Gifts',
    'inventory/Finance.php'=>'Collections & Payments',
);

$menu['inventory']['parent'] = array(
    'inventory/FieldTrips.php'=>'Field Trips & Tours',
    'inventory/Birthdays.php'=>'Birthdays',
);
?>
