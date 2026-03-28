<?php
include('../../RedirectModulesInc.php');
include('SetupInc.php');

$school = UserSchool();

$totals = DBGet(DBQuery("SELECT
    SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) AS total_collected,
    SUM(CASE WHEN status='pending' THEN amount ELSE 0 END) AS total_pending,
    SUM(CASE WHEN status='overdue' THEN amount ELSE 0 END) AS total_overdue,
    SUM(CASE WHEN status='refunded' THEN amount ELSE 0 END) AS total_refunded,
    COUNT(*) AS total_records
    FROM inventory_finance WHERE school_id='$school'"));

$byCat = DBGet(DBQuery("SELECT category, COUNT(*) AS cnt,
    SUM(amount) AS total, SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) AS paid
    FROM inventory_finance WHERE school_id='$school' GROUP BY category ORDER BY total DESC"));

PopTable('header', 'Finance Report');
?>
<div class="row" style="margin-bottom:15px">
    <div class="col-md-3"><div class="panel panel-body text-center text-success"><h4>$<?php echo number_format((float)($totals[1]['TOTAL_COLLECTED'] ?? 0), 2); ?></h4><small>Collected</small></div></div>
    <div class="col-md-3"><div class="panel panel-body text-center text-warning"><h4>$<?php echo number_format((float)($totals[1]['TOTAL_PENDING'] ?? 0), 2); ?></h4><small>Pending</small></div></div>
    <div class="col-md-3"><div class="panel panel-body text-center text-danger"><h4>$<?php echo number_format((float)($totals[1]['TOTAL_OVERDUE'] ?? 0), 2); ?></h4><small>Overdue</small></div></div>
    <div class="col-md-3"><div class="panel panel-body text-center"><h4><?php echo $totals[1]['TOTAL_RECORDS'] ?? 0; ?></h4><small>Total Records</small></div></div>
</div>
<?php
$columns = ['CATEGORY'=>'Category', 'CNT'=>'Records', 'TOTAL'=>'Total Amount', 'PAID'=>'Collected'];
ListOutput($byCat, $columns, 'Category', 'Categories');
PopTable('footer');
?>
