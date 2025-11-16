<?php
// receive_status_update.php in Module3 directory
session_start();
require '../db.php';

// Get JSON data from request
 $json = file_get_contents('php://input');
 $data = json_decode($json, true);

// Check if required data is present
if (!isset($data['invoice_number']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required data']);
    exit;
}

try {
    // Update invoice status in the database
    $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE invoice_number = ?");
    $stmt->execute([$data['status'], $data['invoice_number']]);
    
    // Return success response
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
