<?php
require '../db.php';
require '../shared/config.php';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Sales & Support Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <base href="<?php echo BASE_URL; ?>">
  <style>
    .card{padding:1rem;margin-bottom:1rem}
    .table{width:100%;border-collapse:collapse;margin-top:8px}
    .table th,.table td{padding:10px;border:1px solid #eee;text-align:left}
    .btn-summary{
      padding:6px 10px;
      border-radius:6px;
      border:0;
      background:#007bff;
      color:#fff;
      cursor:pointer;
      text-decoration:none;
      display:inline-block;
    }
    .btn-summary:hover{background:#0062d6}
    .status-pending,
    .status-processed,
    .status-shipped,
    .status-delivered{
      color:white;
      font-weight:600;
      padding:2px 8px;
      border-radius:999px;
      display:inline-block;
      font-size:0.85em;
    }
    .status-pending{background:#ffc107;color:#000;}
    .status-processed{background:#17a2b8;}
    .status-shipped{background:#28a745;}
    .status-delivered{background:#007bff;}
  </style>
</head>
<body>
  <?php include '../shared/sidebar.php'; ?>

  <div class="container" style="margin-left:18rem;">
    <header class="hero card">
      <h1>Sales & Support Dashboard</h1>
      <p class="small">Quick access to customer, quoting, sales orders and support tools.</p>
    </header>

    <section class="card">
      <h3>Recent Sales Orders</h3>
      <div id="orders"></div>
    </section>
  </div>

  <script>
  // Helpers
  function fmt(v){
    if (typeof v === 'number') return v.toFixed(2);
    const n = parseFloat(v);
    return isNaN(n) ? '0.00' : n.toFixed(2);
  }
  function escapeHtml(s){
    if (!s && s !== 0) return '';
    return String(s).replace(/[&<>"'`=\/]/g, function(c){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'})[c];
    });
  }

  const ordersContainer = document.getElementById('orders');

  // Load recent orders and render table with "View Summary" link to sales_orders.php
  async function loadOrders(){
    try {
      const res = await fetch('Module8/api/sales_orders.php?action=list');
      const j = await res.json();

      if (!j || j.status !== 'success' || !Array.isArray(j.data)) {
        ordersContainer.innerHTML = '<div class="alert alert-info">No recent orders found.</div>';
        return;
      }

      const rows = j.data.slice(0, 10).map(o => {
        const id           = o.id; // from sales_orders table
        const orderNumber  = escapeHtml(o.order_number ?? '');
        const customerName = escapeHtml(o.customer_name ?? '');
        const total        = fmt(o.total ?? 0);
        const statusRaw    = (o.status || '').toLowerCase();
        const statusText   = escapeHtml((o.status || '').toUpperCase());

        return `
          <tr>
            <td>${orderNumber}</td>
            <td>${customerName}</td>
            <td><span class="status-${statusRaw}">${statusText}</span></td>
            <td>₱${total}</td>
            <td>
              <a class="btn-summary" href="Module8/sales_orders.php?order_id=${id}">
                Order Summary
              </a>
            </td>
          </tr>
        `;
      }).join('');

      ordersContainer.innerHTML = `
        <table class="table">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Status</th>
              <th>Total</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      `;
    } catch (err) {
      console.error('loadOrders error', err);
      ordersContainer.innerHTML = '<div class="alert alert-error">Failed to load orders.</div>';
    }
  }

  // Initial load
  loadOrders();
  </script>
</body>
</html>
