<?php
// shared/sidebar.php
require_once __DIR__ . '/config.php';

// --- Ensure $current_page exists ---
if (!isset($current_page) || empty($current_page)) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $current_page = basename($path);
    if (empty($current_page)) {
        $current_page = basename($_SERVER['SCRIPT_NAME']);
    }
}
$current_page = strtolower($current_page);

// helper: check if any of the names matches current page
function is_active($names) {
    global $current_page;
    foreach ((array)$names as $n) {
        if ($current_page === strtolower(basename($n))) return true;
    }
    return false;
}
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>shared/sidebar.css">

<aside class="fixed top-0 left-0 w-60 h-screen bg-gray-100 text-gray-800 flex flex-col shadow-md z-50">
  <!-- Title -->
  <div class="p-4 text-xl font-semibold flex items-center gap-2 border-b border-gray-300">
    <span>Coffee Business</span>
  </div>

  <!-- Navigation -->
  <nav class="flex-1 px-3 py-4 space-y-2 overflow-y-auto">

    <!-- Inventory Dropdown -->
    <details class="group" <?php if (is_active(['index.php','locations.php','transactions.php','alerts.php'])) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-200">
        <span>Inventory</span>
        <i class="fas fa-chevron-down transform transition-transform group-open:rotate-180"></i>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="<?php echo BASE_URL; ?>Module1/index.php"
           class="block px-2 py-1 rounded <?php echo is_active('index.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Product Items</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module1/locations.php"
           class="block px-2 py-1 rounded <?php echo is_active('locations.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Manage Warehouse</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module1/alerts.php"
           class="block px-2 py-1 rounded <?php echo is_active('alerts.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Alerts & Reports</span>
        </a>
      </div>
    </details>
    
    <!-- Procurement Dropdown -->
    <details class="group" <?php if (is_active(['purchase_requisitions.php','purchase_orders.php','invoices.php','suppliers.php'])) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-200">
        <span>Procurement</span>
        <i class="fas fa-chevron-down transform transition-transform group-open:rotate-180"></i>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="<?php echo BASE_URL; ?>Module3/purchase_requisitions.php"
           class="block px-2 py-1 rounded <?php echo is_active('purchase_requisitions.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Purchase Requisitions</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>Module3/purchase_orders.php"
           class="block px-2 py-1 rounded <?php echo is_active('purchase_orders.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Purchase Orders</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module3/invoices.php"
           class="block px-2 py-1 rounded <?php echo is_active('invoices.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Invoice</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module3/suppliers.php"
           class="block px-2 py-1 rounded <?php echo is_active('suppliers.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Suppliers</span>
        </a>
      </div>
    </details>

    <?php
      // simple URL-based detection for E-Commerce page
      $is_ecommerce = (strpos($_SERVER['REQUEST_URI'], 'Module6/ecommerce.php') !== false);
    ?>

    <!-- E-Commerce Dropdown (Module 6) -->
    <details class="group" <?php if ($is_ecommerce) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-200">
        <span>E-Commerce</span>
        <i class="fas fa-chevron-down transform transition-transform group-open:rotate-180"></i>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="<?php echo BASE_URL; ?>Module6/ecommerce.php"
           class="block px-2 py-1 rounded <?php echo $is_ecommerce ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Online Store</span>
        </a>
      </div>
    </details>

    <!-- Business Intelligence (Module 7) -->
    <details class="group" <?php if (strpos($_SERVER['REQUEST_URI'],'Module7') !== false) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-200">
        <span>Business Intelligence</span>
        <i class="fas fa-chevron-down transform transition-transform group-open:rotate-180"></i>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="<?php echo BASE_URL; ?>Module7/index.php"
          class="block px-2 py-1 rounded <?php echo (strpos($_SERVER['REQUEST_URI'],'Module7/index.php') !== false) ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Reports</span>
        </a>
      </div>
    </details>

    <!-- Finance Dropdown -->
    <details class="group" <?php if (is_active(['general_ledger.php','finance_dashboard.php','warehouse_stock.php','accounts_receivable.php','payroll_to_finance.php'])) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-200">
        <span>Finance & Accounting</span>
        <i class="fas fa-chevron-down transform transition-transform group-open:rotate-180"></i>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="<?php echo BASE_URL; ?>Module5/general_ledger.php"
           class="block px-2 py-1 rounded <?php echo is_active('general_ledger.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>General Ledger</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module5/finance_dashboard.php"
           class="block px-2 py-1 rounded <?php echo is_active('finance_dashboard.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Accounts Payable</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module5/warehouse_stock.php"
           class="block px-2 py-1 rounded <?php echo is_active('warehouse_stock.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Asset Ledger</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module5/accounts_receivable.php"
           class="block px-2 py-1 rounded <?php echo is_active('accounts_receivable.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Accounts Receivable</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module5/payroll_to_finance.php"
           class="block px-2 py-1 rounded <?php echo is_active('payroll_to_finance.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Financial Reports and Compliance</span>
        </a>
      </div>
    </details>

    <!-- Sales & Support Dropdown -->
    <details class="group" <?php if (is_active(['customers.php','quotes.php','sales_orders.php','customer_leads.php','support_tickets.php','reports.php']) && strpos($_SERVER['REQUEST_URI'],'Module8') !== false) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-200">
        <span>Sales & Support</span>
        <i class="fas fa-chevron-down transform transition-transform group-open:rotate-180"></i>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="<?php echo BASE_URL; ?>Module8/index.php"
           class="block px-2 py-1 rounded <?php echo (strpos($_SERVER['REQUEST_URI'],'Module8/index.php') !== false) ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Dashboard</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module8/customers.php"
           class="block px-2 py-1 rounded <?php echo is_active('customers.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Customers</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module8/quotes.php"
           class="block px-2 py-1 rounded <?php echo is_active('quotes.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Quotes</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module8/sales_orders.php"
           class="block px-2 py-1 rounded <?php echo is_active('sales_orders.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Sales Orders</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module8/customer_leads.php"
           class="block px-2 py-1 rounded <?php echo is_active('customer_leads.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Customer Leads</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module8/support_tickets.php"
           class="block px-2 py-1 rounded <?php echo is_active('support_tickets.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Support Tickets</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module8/reports.php"
           class="block px-2 py-1 rounded <?php echo is_active('reports.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Reports</span>
        </a>
      </div>
    </details>

    <!-- Ticketing Dropdown -->
    <details class="group" <?php if (strpos($_SERVER['REQUEST_URI'],'Module2') !== false) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-200">
        <span>Ticketing</span>
        <i class="fas fa-chevron-down transform transition-transform group-open:rotate-180"></i>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="<?php echo BASE_URL; ?>Module2/index.php"
           class="block px-2 py-1 rounded <?php echo (strpos($_SERVER['REQUEST_URI'],'Module2/index.php') !== false) ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Dashboard</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module2/add_ticket.php"
           class="block px-2 py-1 rounded <?php echo is_active('add_ticket.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Add Ticket</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module2/view_ticket.php"
           class="block px-2 py-1 rounded <?php echo is_active('view_ticket.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>View Tickets</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module2/manage_solutions.php"
           class="block px-2 py-1 rounded <?php echo is_active('manage_solutions.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Manage Solutions</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module2/customer_communications.php"
           class="block px-2 py-1 rounded <?php echo is_active('customer_communications.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Customer Communications</span>
        </a>
      </div>
    </details>

    <!-- Human Resources Dropdown -->
    <details class="group" <?php if (strpos($_SERVER['REQUEST_URI'],'Module10') !== false) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-200">
        <span>Human Resources</span>
        <i class="fas fa-chevron-down transform transition-transform group-open:rotate-180"></i>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="<?php echo BASE_URL; ?>Module10/index.php"
           class="block px-2 py-1 rounded <?php echo (strpos($_SERVER['REQUEST_URI'],'Module10/index.php') !== false) ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>HR Dashboard</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module10/employees.php"
           class="block px-2 py-1 rounded <?php echo is_active('employees.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Employee Management</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module10/employee_documents.php"
           class="block px-2 py-1 rounded <?php echo is_active('employee_documents.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Employee Documents</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module10/payroll.php"
           class="block px-2 py-1 rounded <?php echo is_active('payroll.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Payroll Management</span>
        </a>

        <a href="<?php echo BASE_URL; ?>Module10/attendance.php"
           class="block px-2 py-1 rounded <?php echo is_active('attendance.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Attendance Management</span>
        </a>
 
        <a href="<?php echo BASE_URL; ?>Module10/leave.php"
           class="block px-2 py-1 rounded <?php echo is_active('leave.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          <span>Leave Management</span>
        </a>

      </div>
    </details>

  </nav>
</aside>
