<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');
$school = UserSchool();

$stats = DBGet(DBQuery("SELECT COUNT(*) AS total_items, SUM(total_quantity) AS total_qty,
    SUM(available_quantity) AS available_qty, SUM(total_quantity - available_quantity) AS checked_out,
    SUM(COALESCE(purchase_cost,0) * total_quantity) AS total_value
    FROM inventory_equipment WHERE school_id='$school' AND is_active='Y'"));

$byCat = DBGet(DBQuery("SELECT COALESCE(c.title,'Uncategorized') AS category, COUNT(*) AS item_count,
    SUM(e.total_quantity) AS total_qty, SUM(COALESCE(e.purchase_cost,0)*e.total_quantity) AS value
    FROM inventory_equipment e LEFT JOIN inventory_categories c ON e.category_id=c.id
    WHERE e.school_id='$school' AND e.is_active='Y' GROUP BY c.title ORDER BY value DESC"));

$byCondition = DBGet(DBQuery("SELECT condition_status, COUNT(*) AS cnt FROM inventory_equipment
    WHERE school_id='$school' AND is_active='Y' GROUP BY condition_status ORDER BY cnt DESC"));

PopTable('header', 'Equipment Inventory Report');
?>
<div class="row" style="margin-bottom:15px">
    <div class="col-md-2"><div class="panel panel-body text-center"><h4><?php echo $stats[1]['TOTAL_ITEMS'] ?? 0; ?></h4><small>Items</small></div></div>
    <div class="col-md-2"><div class="panel panel-body text-center"><h4><?php echo $stats[1]['TOTAL_QTY'] ?? 0; ?></h4><small>Total Qty</small></div></div>
    <div class="col-md-2"><div class="panel panel-body text-center"><h4><?php echo $stats[1]['AVAILABLE_QTY'] ?? 0; ?></h4><small>Available</small></div></div>
    <div class="col-md-2"><div class="panel panel-body text-center text-danger"><h4><?php echo $stats[1]['CHECKED_OUT'] ?? 0; ?></h4><small>Checked Out</small></div></div>
    <div class="col-md-4"><div class="panel panel-body text-center"><h4>$<?php echo number_format((float)($stats[1]['TOTAL_VALUE'] ?? 0), 2); ?></h4><small>Total Value</small></div></div>
</div>
<h6>By Category</h6>
<?php
$columns = ['CATEGORY'=>'Category', 'ITEM_COUNT'=>'Items', 'TOTAL_QTY'=>'Quantity', 'VALUE'=>'Value'];
ListOutput($byCat, $columns, 'Category', 'Categories');
echo '<h6 style="margin-top:15px">By Condition</h6>';
$columns2 = ['CONDITION_STATUS'=>'Condition', 'CNT'=>'Count'];
ListOutput($byCondition, $columns2, 'Status', 'Statuses');
PopTable('footer');
?>
