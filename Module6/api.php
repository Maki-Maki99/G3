<?php

require '../db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? null;

if ($action === 'check_stock') {
    $sku = $_GET['sku'] ?? null;
    $location_id = isset($_GET['location_id']) ? (int)$_GET['location_id'] : null;

    if (!$sku) {
        http_response_code(400);
        echo json_encode(['error' => 'sku parameter is required']);
        exit;
    }

    echo json_encode(check_stock($pdo, $sku, $location_id));
    exit;
}

if ($action === 'deduct_stock') {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);

    $items = null;

    if (is_array($body) && isset($body['cart']) && is_array($body['cart'])) {
        $items = array_values($body['cart']);
    } elseif (is_array($body) && isset($body['items']) && is_array($body['items'])) {
        $items = array_values($body['items']);
    } elseif (is_array($body) && array_values($body) === $body && !empty($body)) {
        // already a numeric-indexed array
        $items = $body;
    }

    if (!is_array($items) || empty($items)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid or empty JSON payload. Expected an array of items or {cart:[...]}.' 
        ]);
        exit;
    }

    $result = deduct_stock_batch($pdo, $items);

    // If some items failed, include a concise list of failed SKUs and reasons for easier diagnostics
    if (isset($result['success']) && $result['success'] === false && isset($result['results']) && is_array($result['results'])) {
        $failed = [];
        foreach ($result['results'] as $r) {
            if (empty($r['success'])) {
                $failed[] = [
                    'sku' => $r['sku'] ?? null,
                    'qty' => $r['qty'] ?? ($r['quantity'] ?? null),
                    'error' => $r['error'] ?? 'unknown'
                ];
            }
        }
        if (!empty($failed)) {
            $result['failed_items'] = $failed;
            // Ensure top-level message includes number of failures
            $result['message'] = count($failed) . ' item(s) failed to deduct';
        }
    }

    echo json_encode($result);
    exit;
}

// Unknown action
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown or missing action']);
exit;

/* ==================== FUNCTIONS ==================== */

function check_stock(PDO $pdo, string $sku, ?int $location_id = null): array
{
    if ($location_id !== null) {
        $stmt = $pdo->prepare("
            SELECT pl.quantity, p.total_quantity
            FROM product_locations pl
            JOIN products p ON p.id = pl.product_id
            WHERE p.sku = :sku AND pl.location_id = :loc
            LIMIT 1
        ");
        $stmt->execute([':sku' => $sku, ':loc' => $location_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return [
                'sku' => $sku,
                'location_id' => $location_id,
                'quantity' => (int)$row['quantity'],
                'total_quantity' => (int)$row['total_quantity']
            ];
        }
    }

    // Fallback to global stock (recomputed from product_locations to avoid stale total_quantity)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(pl.quantity), 0) AS total_quantity
        FROM products p
        LEFT JOIN product_locations pl ON p.id = pl.product_id
        WHERE p.sku = :sku
        GROUP BY p.id
        LIMIT 1
    ");
    $stmt->execute([':sku' => $sku]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $row ? (int)$row['total_quantity'] : 0;

    return [
        'sku' => $sku,
        'quantity' => $total,  // available globally
        'total_quantity' => $total
    ];
}

/**
 * Deduct stock for a batch of items.
 *
 * - Uses product_locations as source of truth.
 * - For items with location_id: only deduct from that location.
 * - For items without location_id: deduct across locations (biggest first) until qty satisfied.
 * - Recomputes products.total_quantity after successful deductions for each product.
 *
 * Returns an array:
 *  - success: boolean (true if all items succeeded)
 *  - message
 *  - results: list of per-item results
 */
function deduct_stock_batch(PDO $pdo, array $items): array
{
    $results = [];

    try {
        $pdo->beginTransaction();

        // Prepared statements reused
        $getProductStmt = $pdo->prepare("SELECT id FROM products WHERE sku = :sku LIMIT 1");
        $updateLocationStmt = $pdo->prepare("UPDATE product_locations SET quantity = quantity - :qty WHERE product_id = :pid AND location_id = :loc AND quantity >= :qty");
        $updateProductTotalStmt = $pdo->prepare("UPDATE products SET total_quantity = (SELECT COALESCE(SUM(quantity),0) FROM product_locations WHERE product_id = :pid) WHERE id = :pid");
        $totalAvailableStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM product_locations WHERE product_id = :pid FOR UPDATE");
        $selectLocationsForUpdate = $pdo->prepare("SELECT location_id, quantity FROM product_locations WHERE product_id = :pid AND quantity > 0 ORDER BY quantity DESC FOR UPDATE");

        foreach ($items as $item) {
            $sku = $item['sku'] ?? null;
            $qty = (int)($item['qty'] ?? $item['quantity'] ?? 0);
            $location_id = isset($item['location_id']) ? (int)$item['location_id'] : null;

            if (!$sku || $qty <= 0) {
                $results[] = [
                    'sku' => $sku,
                    'qty' => $qty,
                    'success' => false,
                    'error' => 'Invalid SKU or quantity'
                ];
                continue;
            }

            // Fetch product id
            $getProductStmt->execute([':sku' => $sku]);
            $productId = $getProductStmt->fetchColumn();
            if (!$productId) {
                $results[] = [
                    'sku' => $sku,
                    'qty' => $qty,
                    'success' => false,
                    'error' => 'Product not found'
                ];
                continue;
            }

            $deducted = false;

            // CASE 1: Location-specific deduction requested
            if ($location_id !== null) {
                // Lock the specific product_location row implicitly by the UPDATE condition
                $updateLocationStmt->execute([':qty' => $qty, ':pid' => $productId, ':loc' => $location_id]);

                if ($updateLocationStmt->rowCount() > 0) {
                    // Recompute and set product total from product_locations (avoids stale totals)
                    $updateProductTotalStmt->execute([':pid' => $productId]);

                    $results[] = [
                        'sku' => $sku,
                        'location_id' => $location_id,
                        'qty' => $qty,
                        'success' => true
                    ];
                    $deducted = true;
                } else {
                    $results[] = [
                        'sku' => $sku,
                        'location_id' => $location_id,
                        'qty' => $qty,
                        'success' => false,
                        'error' => 'Insufficient stock at requested location'
                    ];
                    // do not continue to global deduction when location was explicitly requested
                    continue;
                }
            }

            // CASE 2: Global deduction (if not already deducted)
            if (!$deducted) {
                // First, check total available across all locations (and lock rows)
                $totalAvailableStmt->execute([':pid' => $productId]);
                $totalAvailable = (int)$totalAvailableStmt->fetchColumn();

                if ($totalAvailable < $qty) {
                    $results[] = [
                        'sku' => $sku,
                        'qty' => $qty,
                        'success' => false,
                        'error' => 'Insufficient stock'
                    ];
                    continue;
                }

                // Get location rows (locked) and deduct across them
                $selectLocationsForUpdate->execute([':pid' => $productId]);
                $locations = $selectLocationsForUpdate->fetchAll(PDO::FETCH_ASSOC);

                $need = $qty;
                foreach ($locations as $locRow) {
                    if ($need <= 0) break;
                    $lid = (int)$locRow['location_id'];
                    $availableAtLoc = (int)$locRow['quantity'];
                    if ($availableAtLoc <= 0) continue;

                    $take = min($availableAtLoc, $need);
                    $updateLocationStmt->execute([':qty' => $take, ':pid' => $productId, ':loc' => $lid]);

                    if ($updateLocationStmt->rowCount() > 0) {
                        $need -= $take;
                    }
                    // If for some reason update failed (race), we will continue because total was previously checked
                }

                if ($need === 0) {
                    // Recompute and set product total from product_locations
                    $updateProductTotalStmt->execute([':pid' => $productId]);

                    $results[] = [
                        'sku' => $sku,
                        'qty' => $qty,
                        'success' => true,
                        'note' => 'deducted from locations'
                    ];
                    $deducted = true;
                } else {
                    // Should not normally happen because we checked totals, but handle gracefully
                    $results[] = [
                        'sku' => $sku,
                        'qty' => $qty,
                        'success' => false,
                        'error' => 'Could not deduct required quantity (concurrent update?)'
                    ];
                }
            }
        }

        $pdo->commit();

        $all_success = true;
        foreach ($results as $r) {
            if (empty($r['success'])) {
                $all_success = false;
                break;
            }
        }

        return [
            'success' => $all_success,
            'message' => $all_success ? 'Stock deducted successfully' : 'Some items failed',
            'results' => $results
        ];

    } catch (Exception $e) {
        // In case of fatal error, rollback any changes
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage(),
            'results' => $results
        ];
    }
}
?>