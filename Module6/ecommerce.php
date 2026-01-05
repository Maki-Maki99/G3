<?php
require '../db.php';
session_start();

if (!isset($_SESSION['m6_cart'])) {
    $_SESSION['m6_cart'] = [];
}

$action = $_REQUEST['action'] ?? 'list';

/* ----------------------------------------------------------- 
   HTML HEADER & FOOTER
----------------------------------------------------------- */
function render_header($title = "E-Commerce Module 6") {
    // tell sidebar which page is active
    $current_page = 'ecommerce.php';

    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <title>{$title}</title>
        <link rel='stylesheet' href='style.css'>
        <style>
            .hidden{display:none;}
            .input.small{width:80px;}
            .cart-table{width:100%; border-collapse:collapse;}
            .cart-table th, .cart-table td{padding:8px; border:1px solid #ddd; text-align:left;}
            .product-grid{display:flex; flex-wrap:wrap; gap:12px;}
            .product-card{border:1px solid #ddd; padding:12px; width:220px; border-radius:6px;}
            .btn{display:inline-block; padding:8px 12px; background:#eee; border-radius:4px; text-decoration:none; color:#000;}
            .btn-primary{background:#007bff; color:#fff;}
            .btn-success{background:#28a745; color:#fff;}
            .btn-danger{background:#dc3545; color:#fff;}
            .alert{padding:12px; border-radius:6px; margin-bottom:12px;}
            .alert-error{background:#f8d7da; color:#842029;}
            .alert-success{background:#d1e7dd; color:#0f5132;}
            .alert-info{background:#cff4fc; color:#055160;}
            .stock-badge{display:inline-block; padding:4px 8px; border-radius:12px; font-size:0.9em;}
            .in-stock{background:#e6f4ea; color:#066a2e;}
            .out-stock{background:#fff0f0; color:#7a2a2a;}
            .shop-header{display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;}
            .shop-nav a{margin-left:10px;}
        </style>
    </head>
    <body>
    ";

    // ✅ Shared sidebar, no extra <aside> wrapper
    if (file_exists('../shared/sidebar.php')) {
        include '../shared/sidebar.php';
    }

    // ✅ Main content area pushed to the right of the fixed sidebar
    echo "
    <div class='main-wrapper' style='margin-left: 18rem;'>
        <main class='content'>
            <div class='shop-header'>
                <h1>E-Commerce Module 6</h1>
                <div class='shop-nav'>
                    <a href='?action=list'>Shop</a>
                    <a href='?action=cart'>Cart (" . count($_SESSION['m6_cart']) . ")</a>
                </div>
            </div>
    ";
}

function render_footer() {
    echo "
        </main>
    </div>
    <script>
        // Toggle payment fields in checkout form
        function togglePaymentFields() {
            var pm = document.getElementById('payment_method') ? document.getElementById('payment_method').value : '';
            var online = document.getElementById('online_opts');
            var bank   = document.getElementById('bank_opts');
            var cash   = document.getElementById('cash_opts');
            if (online) online.style.display = (pm === 'online') ? 'block' : 'none';
            if (bank)   bank.style.display   = (pm === 'bank')   ? 'block' : 'none';
            if (cash)   cash.style.display   = (pm === 'cash')   ? 'block' : 'none';
        }
        document.addEventListener('DOMContentLoaded', function() {
            var pmEl = document.getElementById('payment_method');
            if(pmEl) pmEl.addEventListener('change', togglePaymentFields);
            togglePaymentFields();
        });
    </script>
    </body>
    </html>";
}

/* ----------------------------------------------------------- 
   HELPERS: Product and Customer helpers
----------------------------------------------------------- */
function get_product_with_stock($pdo, $sku) {
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.sku, 
            p.name, 
            p.unit_price,
            COALESCE(SUM(pl.quantity), 0) AS total_quantity
        FROM products p
        LEFT JOIN product_locations pl ON p.id = pl.product_id
        WHERE p.sku = ?
        GROUP BY p.id, p.sku, p.name, p.unit_price
    ");
    $stmt->execute([$sku]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_all_products_with_stock($pdo) {
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.sku, 
            p.name, 
            p.unit_price,
            COALESCE(SUM(pl.quantity), 0) AS total_quantity
        FROM products p
        LEFT JOIN product_locations pl ON p.id = pl.product_id
        GROUP BY p.id, p.sku, p.name, p.unit_price
        ORDER BY p.name ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_customers($pdo) {
    $stmt = $pdo->query("SELECT id, name, email FROM customers ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_customer_by_id($pdo, $id) {
    $stmt = $pdo->prepare("SELECT id, name, email FROM customers WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_customer_by_name($pdo, $name) {
    $stmt = $pdo->prepare("SELECT id, name, email FROM customers WHERE LOWER(name) = LOWER(:name) LIMIT 1");
    $stmt->execute([':name' => $name]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function create_customer($pdo, $name, $email = null) {
    $stmt = $pdo->prepare("INSERT INTO customers (name, email, created_at) VALUES (:name, :email, NOW())");
    $stmt->execute([':name' => $name, ':email' => $email]);
    return $pdo->lastInsertId();
}

/* ----------------------------------------------------------- 
   ACTION: LIST
----------------------------------------------------------- */
if ($action === 'list') {
    render_header("Product List");
    $products = get_all_products_with_stock($pdo);

    echo "<div class='product-grid'>";
    foreach ($products as $p) {
        $sku = htmlspecialchars($p['sku']);
        $inStock = $p['total_quantity'] > 0;
        $disabled = $inStock ? "" : "disabled style='opacity:0.6;'";

        echo "
            <div class='product-card'>
                <div class='product-name'>" . htmlspecialchars($p['name']) . "</div>
                <div class='price'>₱" . number_format($p['unit_price'], 2) . "</div>
                <div class='stock-badge " . ($inStock ? "in-stock" : "out-stock") . "'>
                    " . ($inStock ? "Stock: " . (int)$p['total_quantity'] : "Out of Stock") . "
                </div>
                <div style='margin-top:10px;'>
                    <a class='btn btn-primary' href='?action=view&sku={$sku}' $disabled>View Product</a>
                </div>
            </div>";
    }
    echo "</div>";
    render_footer();
    exit;
}

/* ----------------------------------------------------------- 
   ACTION: VIEW PRODUCT
----------------------------------------------------------- */
if ($action === 'view') {
    $sku = $_GET['sku'] ?? null;
    if (!$sku) { die("Invalid product"); }

    $p = get_product_with_stock($pdo, $sku);
    if (!$p) { die("Product not found"); }

    render_header($p['name']);

    $inStock = $p['total_quantity'] > 0;

    echo "
    <div class='card'>
        <div class='product-detail'>
            <div class='product-detail-info'>
                <h2>" . htmlspecialchars($p['name']) . " <small>(SKU: " . htmlspecialchars($sku) . ")</small></h2>
                <div class='price'>₱" . number_format($p['unit_price'], 2) . "</div>
                <div class='stock-badge " . ($inStock ? "in-stock" : "out-stock") . "'>
                    Available: " . (int)$p['total_quantity'] . "
                </div>

                <form method='post' class='product-detail-actions'>
                    <input type='hidden' name='action' value='add_to_cart'>
                    <input type='hidden' name='sku' value='" . htmlspecialchars($sku) . "'>
                    <label>Quantity</label>
                    <input class='input' type='number' name='qty' min='1' max='" . (int)$p['total_quantity'] . "' value='1' required " . ($inStock ? "" : "disabled") . " style='width:80px;'>
                    <button class='btn btn-primary' " . ($inStock ? "" : "disabled") . ">Add to Cart</button>
                </form>
                <a href='?action=list' class='btn' style='margin-top:10px;'>Back to Shop</a>
            </div>
        </div>
    </div>";

    render_footer();
    exit;
}

/* ----------------------------------------------------------- 
   ACTION: ADD TO CART (with real stock check)
----------------------------------------------------------- */
if ($action === 'add_to_cart') {
    $sku = $_POST['sku'] ?? null;
    $qty = max(1, min(100, (int)($_POST['qty'] ?? 1)));

    $p = get_product_with_stock($pdo, $sku);
    if (!$p || $p['total_quantity'] < $qty) {
        render_header("Cannot Add to Cart");
        $available = $p['total_quantity'] ?? 0;
        echo "<div class='alert alert-error'>Not enough stock. Only {$available} available.</div>";
        echo "<a class='btn' href='?action=view&sku=" . htmlspecialchars($sku) . "'>Go Back</a>";
        render_footer();
        exit;
    }

    $found = false;
    foreach ($_SESSION['m6_cart'] as &$item) {
        if ($item['sku'] === $sku) {
            $new_qty = $item['qty'] + $qty;
            if ($new_qty > $p['total_quantity']) {
                render_header("Cannot Add More");
                echo "<div class='alert alert-error'>Only {$p['total_quantity']} in stock. You already have {$item['qty']} in cart.</div>";
                render_footer();
                exit;
            }
            $item['qty'] = $new_qty;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['m6_cart'][] = ['sku' => $sku, 'qty' => $qty];
    }

    header("Location: ?action=cart");
    exit;
}

/* ----------------------------------------------------------- 
   ACTION: UPDATE CART
----------------------------------------------------------- */
if ($action === 'update_cart') {
    if (!empty($_POST['qty']) && is_array($_POST['qty'])) {
        $new_cart = [];
        foreach ($_POST['qty'] as $sku => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0) continue;

            $p = get_product_with_stock($pdo, $sku);
            if ($p && $qty <= $p['total_quantity']) {
                $new_cart[] = ['sku' => $sku, 'qty' => $qty];
            } else {
                render_header("Update Failed");
                $avail = $p['total_quantity'] ?? 0;
                echo "<div class='alert alert-error'>Cannot update {$sku}: only {$avail} available.</div>";
                echo "<a class='btn' href='?action=cart'>Back to Cart</a>";
                render_footer();
                exit;
            }
        }
        $_SESSION['m6_cart'] = $new_cart;
    }
    header("Location: ?action=cart");
    exit;
}

/* ----------------------------------------------------------- 
   ACTION: REMOVE FROM CART
----------------------------------------------------------- */
if ($action === 'remove_from_cart') {
    $sku = $_GET['sku'] ?? null;
    $_SESSION['m6_cart'] = array_filter($_SESSION['m6_cart'], fn($i) => $i['sku'] !== $sku);
    header("Location: ?action=cart");
    exit;
}

/* ----------------------------------------------------------- 
   ACTION: CART VIEW  (with checkout form)
----------------------------------------------------------- */
if ($action === 'cart') {
    render_header("Your Cart");

    if (empty($_SESSION['m6_cart'])) {
        echo "<div class='alert alert-info'>Your cart is empty.</div>";
        echo "<a class='btn btn-primary' href='?action=list'>Continue Shopping</a>";
        render_footer();
        exit;
    }

    echo "<h2>Your Cart</h2>";
    echo "<form method='post' action='?action=update_cart'>";
    echo "<table class='cart-table'>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>";

    $grand_total = 0;

    foreach ($_SESSION['m6_cart'] as $item) {
        $p = get_product_with_stock($pdo, $item['sku']);
        if (!$p) continue;

        $line_total = $p['unit_price'] * $item['qty'];
        $grand_total += $line_total;

        echo "<tr>
                <td><strong>" . htmlspecialchars($p['name']) . "</strong><br><small>SKU: " . htmlspecialchars($item['sku']) . "</small></td>
                <td>₱" . number_format($p['unit_price'], 2) . "</td>
                <td>
                    <input type='number' name='qty[" . htmlspecialchars($item['sku']) . "]' value='" . (int)$item['qty'] . "' 
                           min='1' max='" . (int)$p['total_quantity'] . "' class='input small' style='width:70px;' required>
                    <br><small>Max available: " . (int)$p['total_quantity'] . "</small>
                </td>
                <td>₱" . number_format($line_total, 2) . "</td>
                <td><a href='?action=remove_from_cart&sku=" . urlencode($item['sku']) . "' class='btn btn-danger btn-small'>Remove</a></td>
              </tr>";
    }

    echo "  </tbody>
            <tfoot>
                <tr>
                    <td colspan='3'><strong>Grand Total</strong></td>
                    <td colspan='2'><strong>₱" . number_format($grand_total, 2) . "</strong></td>
                </tr>
            </tfoot>
          </table>";

    echo "<button class='btn btn-primary' style='margin-top:15px;'>Update Cart</button>";
    echo "</form>";

    // Checkout form (existing customer OR name + payment)
    $customers = get_customers($pdo);
    echo "<div style='margin-top:40px; padding:25px; background:#f0f8ff; border-radius:10px; border:1px solid #007bff;'>
            <h3>Proceed to Checkout</h3>
            <form method='post'>
                <input type='hidden' name='action' value='checkout'>
                <div style='margin-bottom:10px;'>
                    <label><strong>Choose Existing Customer (optional):</strong></label><br>
                    <select name='customer_id' style='padding:10px; width:320px;'>
                        <option value=''>-- Select customer --</option>";
    foreach ($customers as $c) {
        $cid = (int)$c['id'];
        $cname = htmlspecialchars($c['name']);
        echo "<option value='{$cid}'>{$cname}</option>";
    }
    echo "      </select>
                </div>

                <div style='margin-bottom:10px;'>
                    <label><strong>Or enter Customer Name (will be used if provided):</strong></label><br>
                    <input class='input' type='text' name='customer_name' placeholder='Customer name only, e.g. John Doe' style='width:320px; padding:10px;'>
                </div>

                <div style='font-size:0.9em; color:#333; margin-bottom:12px;'>
                    <em>Typed name overrides the selected customer. If it matches an existing name, that customer is used; otherwise a new customer is created.</em>
                </div>

                <hr style='margin:15px 0;'>

                <div style='margin-bottom:10px;'>
                    <label><strong>Payment Method:</strong></label><br>
                    <select id='payment_method' name='payment_method' style='padding:10px; width:320px;' required>
                        <option value=''>-- Select payment method --</option>
                        <option value='online'>Online Payment (GCASH / PayMaya)</option>
                        <option value='bank'>Bank Account</option>
                        <option value='cash'>Cash</option>
                    </select>
                </div>

                <div id='online_opts' class='hidden' style='margin-bottom:10px;'>
                    <label><strong>Provider:</strong></label><br>
                    <select name='online_provider' style='padding:8px; width:200px; margin-bottom:8px;'>
                        <option value='GCASH'>GCASH</option>
                        <option value='PayMaya'>PayMaya</option>
                    </select><br>
                    <label><strong>Number / Transaction ID:</strong></label><br>
                    <input class='input' type='text' name='online_number' placeholder='e.g. 09171234567 or TXN12345' style='width:320px; padding:10px;'>
                </div>

                <div id='bank_opts' class='hidden' style='margin-bottom:10px;'>
                    <label><strong>Bank Account ID / Reference:</strong></label><br>
                    <input class='input' type='text' name='bank_account_id' placeholder='e.g. ACCT-12345678' style='width:320px; padding:10px;'>
                </div>

                <div id='cash_opts' class='hidden' style='margin-bottom:10px;'>
                    <label><strong>Cash Received (₱):</strong></label><br>
                    <input class='input' type='number' step='0.01' min='0' name='cash_received' placeholder='Enter cash amount received from customer' style='width:200px; padding:10px;'>
                </div>

                <button class='btn btn-success' style='padding:12px 30px; font-size:16px;'>Checkout Now</button>
            </form>
          </div>";

    render_footer();
    exit;
}

/* ----------------------------------------------------------- 
   ACTION: CHECKOUT – FINAL STOCK CHECK + DEDUCT + INSERT SALES ORDER + PAYMENT
   - Supports customer_name input: prefer the typed name; if empty use selected customer_id.
   - If name matches an existing customer (case-insensitive) use that one; otherwise create a new customer row.
   - Payment handling:
       * online -> requires online_number, provider; payment recorded as paid (payments table)
       * bank   -> requires bank_account_id; payment recorded as paid (payments table)
       * cash   -> requires cash_received >= total; payment recorded as paid and change displayed (payments table)
----------------------------------------------------------- */
if ($action === 'checkout') {
    $posted_name = trim((string)($_POST['customer_name'] ?? ''));
    $posted_customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;

    if (empty($_SESSION['m6_cart'])) {
        die("Invalid request.");
    }

    try {
        // Resolve customer: typed name takes precedence if provided
        if ($posted_name !== '') {
            // Try find existing by exact name (case-insensitive)
            $existing = get_customer_by_name($pdo, $posted_name);
            if ($existing) {
                $customer = $existing;
                $customer_id = (int)$existing['id'];
            } else {
                // Create new customer with provided name; email left NULL
                $pdo->beginTransaction();
                $newId = create_customer($pdo, $posted_name, null);
                $pdo->commit();
                $customer = get_customer_by_id($pdo, $newId);
                $customer_id = (int)$newId;
            }
        } elseif ($posted_customer_id > 0) {
            $customer = get_customer_by_id($pdo, $posted_customer_id);
            $customer_id = $posted_customer_id;
        } else {
            // Neither name nor id provided
            render_header("Checkout Failed");
            echo "<div class='alert alert-error'>Please select an existing customer or enter a customer name.</div>";
            echo "<a class='btn' href='?action=cart'>Back to Cart</a>";
            render_footer();
            exit;
        }

        if (!$customer) {
            render_header("Checkout Failed");
            echo "<div class='alert alert-error'>Customer not found or could not be created.</div>";
            echo "<a class='btn' href='?action=cart'>Back to Cart</a>";
            render_footer();
            exit;
        }

        // Final stock validation (recheck live quantities)
        foreach ($_SESSION['m6_cart'] as $item) {
            $p = get_product_with_stock($pdo, $item['sku']);
            if (!$p || $item['qty'] > $p['total_quantity']) {
                render_header("Checkout Failed");
                echo "<div class='alert alert-error'>
                        Item <strong>" . htmlspecialchars($item['sku']) . "</strong> no longer has enough stock.<br>
                        Required: {$item['qty']}, Available: " . ($p['total_quantity'] ?? 0) . "
                      </div>";
                echo "<a class='btn' href='?action=cart'>Back to Cart</a>";
                render_footer();
                exit;
            }
        }

        // Gather payment inputs & validate later after total calculated
        $payment_method = $_POST['payment_method'] ?? '';
        $online_provider = $_POST['online_provider'] ?? '';
        $online_number = trim((string)($_POST['online_number'] ?? ''));
        $bank_account_id = trim((string)($_POST['bank_account_id'] ?? ''));
        $cash_received = isset($_POST['cash_received']) ? (float)$_POST['cash_received'] : null;

        // Deduct stock via API (or direct query if no API)
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
             . $_SERVER['HTTP_HOST']
             . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/api.php?action=deduct_stock';

        // API expects a plain array of items
        $payload = json_encode(array_values($_SESSION['m6_cart']));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $res = $response ? json_decode($response, true) : null;

        render_header("Order Status");

        if ($httpCode === 200 && !empty($res['success'])) {
            // Deduction succeeded: insert sales order and items (and payment if provided)
            try {
                $pdo->beginTransaction();

                // Compute totals
                $subtotal = 0.0;
                $getProductInfo = $pdo->prepare("SELECT id, unit_price FROM products WHERE sku = :sku LIMIT 1");

                foreach ($_SESSION['m6_cart'] as $item) {
                    $getProductInfo->execute([':sku' => $item['sku']]);
                    $prod = $getProductInfo->fetch(PDO::FETCH_ASSOC);
                    if ($prod) {
                        $subtotal += ((float)$prod['unit_price'] * (int)$item['qty']);
                    }
                }

                $discount = 0.0;
                $tax = 0.0;
                $total = round($subtotal - $discount + $tax, 2);

                // Validate payment inputs based on chosen method
                $payment_note = null;
                $payment_insert_amount = 0.0;
                $payment_should_insert = false;
                $finance_txn = null;
                $finance_sync_status = 'pending'; // default

                if ($payment_method === 'online') {
                    // require online_number
                    if ($online_number === '') {
                        throw new Exception("Online payment selected but no number/reference provided.");
                    }
                    $payment_note = "Online payment ({$online_provider}) reference/number: {$online_number}";
                    $payment_insert_amount = $total;
                    $payment_should_insert = true;
                    $finance_txn = $online_number;
                    $finance_sync_status = 'synced';
                } elseif ($payment_method === 'bank') {
                    if ($bank_account_id === '') {
                        throw new Exception("Bank payment selected but no bank account id/reference provided.");
                    }
                    $payment_note = "Bank transfer reference: {$bank_account_id}";
                    $payment_insert_amount = $total;
                    $payment_should_insert = true;
                    $finance_txn = $bank_account_id;
                    $finance_sync_status = 'synced';
                } elseif ($payment_method === 'cash') {
                    if ($cash_received === null || $cash_received === '') {
                        throw new Exception("Cash payment selected but no cash amount provided.");
                    }
                    if (!is_numeric($cash_received)) {
                        throw new Exception("Invalid cash amount.");
                    }
                    // ensure received is >= total
                    if ($cash_received < $total) {
                        throw new Exception("Cash received (₱" . number_format($cash_received,2) . ") is less than the order total (₱" . number_format($total,2) . ").");
                    }
                    $change = round($cash_received - $total, 2);
                    $payment_note = "Cash received: ₱" . number_format($cash_received, 2) . ($change > 0 ? "; Change: ₱" . number_format($change,2) : "");
                    $payment_insert_amount = $total;
                    $payment_should_insert = true;
                    $finance_txn = null;
                    $finance_sync_status = 'pending';
                } else {
                    throw new Exception("Please select a valid payment method.");
                }

                $order_number = 'SO-' . time() . '-' . random_int(1000, 9999);

                // Insert sales order (include finance_transaction_id and finance_sync_status if present)
                $stmt = $pdo->prepare("
                    INSERT INTO sales_orders
                        (order_number, customer_id, status, subtotal, discount, tax, total, created_at, finance_transaction_id, finance_sync_status)
                    VALUES
                        (:order_number, :customer_id, :status, :subtotal, :discount, :tax, :total, NOW(), :finance_transaction_id, :finance_sync_status)
                ");
                $stmt->execute([
                    ':order_number' => $order_number,
                    ':customer_id'  => $customer_id,
                    ':status'       => 'processed',
                    ':subtotal'     => $subtotal,
                    ':discount'     => $discount,
                    ':tax'          => $tax,
                    ':total'        => $total,
                    ':finance_transaction_id' => $finance_txn,
                    ':finance_sync_status' => $finance_sync_status
                ]);

                $sales_order_id = $pdo->lastInsertId();

                // Insert items
                $itemStmt = $pdo->prepare("
                    INSERT INTO sales_order_items
                        (sales_order_id, product_id, description, qty, unit_price, line_total)
                    VALUES
                        (:sales_order_id, :product_id, :description, :qty, :unit_price, :line_total)
                ");

                $getProductBySku = $pdo->prepare("SELECT id, name, unit_price FROM products WHERE sku = :sku LIMIT 1");

                foreach ($_SESSION['m6_cart'] as $item) {
                    $getProductBySku->execute([':sku' => $item['sku']]);
                    $prod = $getProductBySku->fetch(PDO::FETCH_ASSOC);
                    if (!$prod) continue;

                    $line_total = (float)$prod['unit_price'] * (int)$item['qty'];

                    $itemStmt->execute([
                        ':sales_order_id' => $sales_order_id,
                        ':product_id'     => $prod['id'],
                        ':description'    => $prod['name'] . " (SKU: " . $item['sku'] . ")",
                        ':qty'            => (int)$item['qty'],
                        ':unit_price'     => $prod['unit_price'],
                        ':line_total'     => $line_total
                    ]);
                }

                // Insert payment record if applicable
                if ($payment_should_insert && $payment_insert_amount > 0) {
                    $payStmt = $pdo->prepare("
                        INSERT INTO payments
                            (customer_id, amount, payment_date, note, created_at)
                        VALUES
                            (:customer_id, :amount, :payment_date, :note, NOW())
                    ");
                    $payStmt->execute([
                        ':customer_id' => $customer_id,
                        ':amount' => $payment_insert_amount,
                        ':payment_date' => date('Y-m-d'),
                        ':note' => $payment_note
                    ]);

                    $payment_id = $pdo->lastInsertId();

                    // Optionally, you could allocate payment to invoices here (payment_allocations),
                    // but we don't have invoice ids for sales_orders in this simplified flow.
                }

                $pdo->commit();

                // Clear cart
                $_SESSION['m6_cart'] = [];

                $customer_name = htmlspecialchars($customer['name']);
                echo "<div class='alert alert-success'>
                        Order placed successfully!<br>
                        Order Number: <strong>" . htmlspecialchars($order_number) . "</strong><br>
                        Customer: <strong>{$customer_name}</strong><br>
                        Total: <strong>₱" . number_format($total,2) . "</strong><br>";
                if ($payment_method === 'cash') {
                    $cash_received_display = number_format((float)$cash_received, 2);
                    $change_display = number_format(max(0, $cash_received - $total), 2);
                    echo "Payment: <strong>Cash</strong><br>";
                    echo "Cash Received: <strong>₱{$cash_received_display}</strong><br>";
                    echo "Change: <strong>₱{$change_display}</strong><br>";
                } elseif ($payment_method === 'online') {
                    echo "Payment: <strong>Online ({$online_provider})</strong><br>";
                    echo "Reference/Number: <strong>" . htmlspecialchars($online_number) . "</strong><br>";
                } elseif ($payment_method === 'bank') {
                    echo "Payment: <strong>Bank Transfer</strong><br>";
                    echo "Reference/Account ID: <strong>" . htmlspecialchars($bank_account_id) . "</strong><br>";
                }
                echo "<br>Thank you for your purchase!
                      </div>";
                echo "<a class='btn btn-primary' href='?action=list'>Continue Shopping</a>";

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                // Note: stock already deducted by API; consider compensating in production
                echo "<div class='alert alert-error'>Order save failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                echo "<a class='btn' href='?action=cart'>Back to Cart</a>";
            }
        } else {
            $msg = $res['message'] ?? $res['error'] ?? 'Stock deduction failed.';
            echo "<div class='alert alert-error'>Checkout failed: " . htmlspecialchars($msg) . "</div>";
            // if API returned failed_items provide brief diagnostics
            if (!empty($res['failed_items']) && is_array($res['failed_items'])) {
                echo "<div style='margin-top:8px;'><strong>Failed items:</strong><ul>";
                foreach ($res['failed_items'] as $fi) {
                    $sku = htmlspecialchars($fi['sku'] ?? '');
                    $err = htmlspecialchars($fi['error'] ?? 'unknown');
                    echo "<li>{$sku}: {$err}</li>";
                }
                echo "</ul></div>";
            }
            echo "<a class='btn' href='?action=cart'>Back to Cart</a>";
        }

        render_footer();
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        render_header("Checkout Failed");
        echo "<div class='alert alert-error'>Unexpected error: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<a class='btn' href='?action=cart'>Back to Cart</a>";
        render_footer();
        exit;
    }
}

// Default
render_header();
echo "<p>Welcome to Coffee Shop Online Store.</p>";
echo "<a class='btn btn-primary' href='?action=list'>Start Shopping</a>";
render_footer();
?>