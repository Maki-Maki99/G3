<?php
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/bi_module.php';

$bi = new BIModule();
$error = '';
$today = date('Y-m-d');

// Handle DELETE (must be before sidebar output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report_id'])) {
    $deleteId = (int)$_POST['delete_report_id'];
    $bi->deleteReport($deleteId);

    header("Location: " . BASE_URL . "Module7/index.php");
    exit;
}

// Handle GENERATE (must be before sidebar output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $reportType  = $_POST['report_type'] ?? '';
    $dateFrom    = !empty($_POST['date_from']) ? $_POST['date_from'] : null;
    $dateTo      = !empty($_POST['date_to']) ? $_POST['date_to'] : null;
    $department  = !empty($_POST['department']) ? $_POST['department'] : null;
    $region      = !empty($_POST['region']) ? $_POST['region'] : null;

    // UI-side validation
    if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
        $error = "Invalid date range: From date must be earlier than or equal to To date.";
    } elseif ($dateFrom && $dateFrom > $today) {
        $error = "From date cannot be later than today ($today).";
    } elseif ($dateTo && $dateTo > $today) {
        $error = "To date cannot be later than today ($today).";
    }

    if (!$error) {
        try {
            $reportId = $bi->generateReport($reportType, $dateFrom, $dateTo, $department, $region);
            header("Location: " . BASE_URL . "Module7/view_report.php?id=" . (int)$reportId);
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Safe to include sidebar now (it outputs HTML)
require_once __DIR__ . '/../shared/sidebar.php';

$recentReports = $bi->getRecentReports(10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Module 7 - Business Intelligence</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>shared/sidebar.css">
</head>
<body class="bg-gray-50">
  <main class="ml-60 p-6">
    <h1 class="text-3xl font-bold mb-6">Business Intelligence (Module 7)</h1>

    <section class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-semibold mb-4">Generate Report</h2>

      <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="post" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Report Type</label>
          <select name="report_type" class="w-full border rounded p-2" required>
            <option value="">Select Report Type</option>
            <option value="Sales Summary">Sales Summary</option>
            <option value="Inventory Stock">Inventory Stock</option>
            <option value="Profit & Loss">Profit & Loss</option>
            <option value="Transaction Report">Transaction Report</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">From Date</label>
          <input type="date" name="date_from" class="w-full border rounded p-2" max="<?php echo $today; ?>" />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">To Date</label>
          <input type="date" name="date_to" class="w-full border rounded p-2" max="<?php echo $today; ?>" />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Department</label>
          <input type="text" name="department" class="w-full border rounded p-2" placeholder="Department" />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Region</label>
          <input type="text" name="region" class="w-full border rounded p-2" placeholder="Region" />
        </div>

        <div class="md:col-span-4">
          <button type="submit" name="generate_report" class="bg-blue-600 text-white px-4 py-2 rounded">
            Generate Report
          </button>
        </div>
      </form>
    </section>

    <section class="bg-white rounded-lg shadow p-6">
      <h2 class="text-xl font-semibold mb-4">Available Reports</h2>

      <div class="overflow-x-auto">
        <table class="w-full text-sm border">
          <thead class="bg-gray-100">
            <tr>
              <th class="p-2 border text-left">Report Type</th>
              <th class="p-2 border text-left">Date Range</th>
              <th class="p-2 border text-left">Department</th>
              <th class="p-2 border text-left">Region</th>
              <th class="p-2 border text-left">Generated At</th>
              <th class="p-2 border text-left">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentReports)): ?>
              <tr><td class="p-2 border" colspan="6">No reports yet.</td></tr>
            <?php else: ?>
              <?php foreach ($recentReports as $r): ?>
                <tr>
                  <td class="p-2 border"><?php echo htmlspecialchars($r['report_type']); ?></td>
                  <td class="p-2 border">
                    <?php
                      $df = $r['date_from'] ?? null;
                      $dt = $r['date_to'] ?? null;
                      echo ($df && $dt) ? htmlspecialchars($df . " to " . $dt) : "All time";
                    ?>
                  </td>
                  <td class="p-2 border"><?php echo htmlspecialchars($r['department'] ?? 'All'); ?></td>
                  <td class="p-2 border"><?php echo htmlspecialchars($r['region'] ?? 'All'); ?></td>
                  <td class="p-2 border"><?php echo htmlspecialchars($r['generated_at']); ?></td>

                  <td class="p-2 border">
                    <div class="flex items-center gap-3">
                        <a
                        href="<?php echo BASE_URL; ?>Module7/view_report.php?id=<?php echo (int)$r['id']; ?>"
                        class="inline-flex items-center px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700 text-sm"
                        >
                        View
                        </a>

                        <form method="post" class="m-0 p-0 inline-flex"
                            onsubmit="return confirm('Delete this report? This cannot be undone.');">
                        <input type="hidden" name="delete_report_id" value="<?php echo (int)$r['id']; ?>">
                        <button
                            type="submit"
                            class="inline-flex items-center px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 text-sm"
                        >
                            Delete
                        </button>
                        </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

  </main>
</body>
</html>

<?php $bi->close(); ?>
