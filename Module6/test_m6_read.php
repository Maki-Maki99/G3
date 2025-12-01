<?php
// test_m6_read.php
require '../db.php';
$sku = 'SY002'; // example sku from coffee.sql (Caramel Syrup)
$stmt = $pdo->prepare("SELECT sku, total_quantity FROM products WHERE sku = :sku LIMIT 1");
$stmt->execute([':sku'=>$sku]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Product {$sku} total_quantity = " . ($r['total_quantity'] ?? 'NULL') . PHP_EOL;
echo "Product locations:" . PHP_EOL;
$ps = $pdo->prepare("SELECT l.code, pl.quantity FROM product_locations pl JOIN locations l ON l.id = pl.location_id WHERE pl.product_id = (SELECT id FROM products WHERE sku = :sku LIMIT 1)");
$ps->execute([':sku'=>$sku]);
foreach($ps->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "- {$row['code']} => {$row['quantity']}" . PHP_EOL;
}
