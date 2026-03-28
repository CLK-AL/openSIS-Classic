<?php
include('../../RedirectModulesInc.php');
$menu['inventory']['admin'] = array(
    'inventory/Equipment.php'=>'Equipment Catalog',
    'inventory/Checkout.php'=>'Checkout / Return',
    'inventory/Maintenance.php'=>'Maintenance Log',
    1=>'Reports',
    'inventory/EquipmentReport.php'=>'Inventory Report',
    2=>'Setup',
    'inventory/Categories.php'=>'Categories',
    'inventory/Locations.php'=>'Locations',
);

$menu['inventory']['teacher'] = array(
    'inventory/Equipment.php'=>'Equipment Catalog',
    'inventory/Checkout.php'=>'Checkout / Return',
);
?>
