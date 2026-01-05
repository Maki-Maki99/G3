<?php
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/bi_module.php';

if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . 'Module7/index.php');
    exit;
}

$reportId = (int)$_GET['id'];
$bi = new BIModule();
$reportData = $bi->getReportData($reportId);

if (!$reportData) {
    header('Location: ' . BASE_URL . 'Module7/index.php');
    exit;
}

require_once __DIR__ . '/../shared/sidebar.php';

$report = $reportData['report'];
$data = $reportData['data'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($report['report_name']); ?></title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>shared/sidebar.css">
</head>
<body class="bg-gray-50">
  <main class="ml-60 p-6">

    <div class="flex items-center justify-between mb-6">
      <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($report['report_name']); ?></h1>

      <div class="flex gap-2">
        <a href="<?php echo BASE_URL; ?>Module7/index.php" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
          Back
        </a>

        <a href="<?php echo BASE_URL; ?>Module7/export_report.php?id=<?php echo (int)$reportId; ?>&format=csv"
           class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">
          Download Excel (CSV)
        </a>

        <a href="<?php echo BASE_URL; ?>Module7/export_report.php?id=<?php echo (int)$reportId; ?>&format=pdf"
           class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">
          Download PDF
        </a>
      </div>
    </div>

    <section class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-semibold mb-4">Report Details</h2>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div><strong>Type:</strong> <?php echo htmlspecialchars($report['report_type']); ?></div>
        <div><strong>From:</strong> <?php echo htmlspecialchars($report['date_from'] ?? 'All'); ?></div>
        <div><strong>To:</strong> <?php echo htmlspecialchars($report['date_to'] ?? 'All'); ?></div>
        <div><strong>Generated At:</strong> <?php echo htmlspecialchars($report['generated_at']); ?></div>
        <div><strong>Department:</strong> <?php echo htmlspecialchars($report['department'] ?? 'All'); ?></div>
        <div><strong>Region:</strong> <?php echo htmlspecialchars($report['region'] ?? 'All'); ?></div>
        <div><strong>Status:</strong> <?php echo htmlspecialchars($report['status']); ?></div>
        <div><strong>Source:</strong> <?php echo htmlspecialchars($report['source_module']); ?></div>
      </div>
    </section>

    <section class="bg-white rounded-lg shadow p-6">
      <h2 class="text-xl font-semibold mb-4">Report Data</h2>

      <?php if (empty($data)): ?>
        <p class="text-sm text-gray-600">No data found for this report.</p>
      <?php else: ?>
        <div class="overflow-x-auto">
          <?php if ($report['report_type'] === 'Sales Summary'): ?>
            <table class="w-full text-sm border">
              <thead class="bg-gray-100">
                <tr>
                  <th class="p-2 border text-left">Product ID</th>
                  <th class="p-2 border text-left">Product Name</th>
                  <th class="p-2 border text-left">Total Qty</th>
                  <th class="p-2 border text-left">Total Amount</th>
                  <th class="p-2 border text-left">Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($data as $row): ?>
                  <tr>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['product_id']); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['total_quantity']); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['total_amount']); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['date']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

          <?php elseif ($report['report_type'] === 'Inventory Stock'): ?>
            <table class="w-full text-sm border">
              <thead class="bg-gray-100">
                <tr>
                  <th class="p-2 border text-left">Product ID</th>
                  <th class="p-2 border text-left">Product Name</th>
                  <th class="p-2 border text-left">Current Stock</th>
                  <th class="p-2 border text-left">Reorder Level</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($data as $row): ?>
                  <tr>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['product_id']); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['current_stock']); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['reorder_level']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

          <?php elseif ($report['report_type'] === 'Profit & Loss'): ?>
            <table class="w-full text-sm border">
              <thead class="bg-gray-100">
                <tr>
                  <th class="p-2 border text-left">Date</th>
                  <th class="p-2 border text-left">Revenue</th>
                  <th class="p-2 border text-left">Expenses</th>
                  <th class="p-2 border text-left">Profit</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($data as $row): ?>
                  <tr>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['date']); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['revenue']); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['expenses']); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['profit']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

          <?php elseif ($report['report_type'] === 'Transaction Report'): ?>
            <table class="w-full text-sm border">
              <thead class="bg-gray-100">
                <tr>
                  <th class="p-2 border text-left">Transaction ID</th>
                  <th class="p-2 border text-left">Type</th>
                  <th class="p-2 border text-left">Amount</th>
                  <th class="p-2 border text-left">Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($data as $row): ?>
                  <tr>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['transaction_type']); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['amount']); ?></td>
                    <td class="p-2 border"><?php echo htmlspecialchars($row['date']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>

  </main>
</body>
</html>

<?php $bi->close(); ?>
