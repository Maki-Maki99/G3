<?php
require '../db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Supplier ID is required']);
    exit;
}

$supplier_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            id AS supplier_id,
            code AS supplier_code,
            name AS supplier_name,
            contact_info,
            address,
            performance_rating
        FROM suppliers 
        WHERE id = ?
    ");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$supplier) {
        echo json_encode(['error' => 'Supplier not found']);
        exit;
    }
    
    echo json_encode(['status' => 'success', 'supplier' => $supplier]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
