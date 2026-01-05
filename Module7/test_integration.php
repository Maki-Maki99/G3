<?php
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/bi_module.php';

$bi = new BIModule();

echo "<h1>Module 7 (DB-Direct) Test</h1>";

$types = ['Inventory Stock', 'Sales Summary', 'Profit & Loss', 'Transaction Report'];

foreach ($types as $t) {
    $id = $bi->generateReport($t);
    echo "<p><b>$t</b> generated. Report ID: <b>$id</b> - <a href='" . BASE_URL . "Module7/view_report.php?id=$id'>View</a></p>";
}

echo "<hr>";
echo "<p><a href='" . BASE_URL . "Module7/index.php'>Back to BI</a></p>";

$bi->close();
?>
