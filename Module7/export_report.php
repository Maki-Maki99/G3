<?php
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/bi_module.php';

// --- Validate params ---
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo "Missing report id.";
    exit;
}

$reportId = (int)$_GET['id'];
$format = strtolower($_GET['format'] ?? 'csv'); // csv|excel|xls|pdf

$bi = new BIModule();
$reportData = $bi->getReportData($reportId);

if (!$reportData) {
    http_response_code(404);
    echo "Report not found.";
    exit;
}

$report = $reportData['report'];
$data = $reportData['data'];

$reportNameSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $report['report_name'] ?? 'report');

// --- Helpers ---
function outputCsv($filename, $headers, $rows) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');

    // Excel-friendly UTF-8 BOM
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, $headers);
    foreach ($rows as $r) {
        fputcsv($out, $r);
    }
    fclose($out);
    exit;
}

function asExcelText($value) {
    // Leading apostrophe forces Excel to treat as text, preventing ####### when column is narrow
    return "'" . (string)$value;
}

// --- CSV / Excel export (Excel opens CSV) ---
if ($format === 'csv' || $format === 'excel' || $format === 'xls') {
    $type = $report['report_type'] ?? '';
    $rows = [];

    if ($type === 'Sales Summary') {
        $headers = ['product_id', 'product_name', 'total_quantity', 'total_amount', 'date'];
        foreach ($data as $d) {
            $rows[] = [
                $d['product_id'] ?? '',
                $d['product_name'] ?? '',
                $d['total_quantity'] ?? '',
                $d['total_amount'] ?? '',
                asExcelText($d['date'] ?? '')
            ];
        }
    } elseif ($type === 'Inventory Stock') {
        $headers = ['product_id', 'product_name', 'current_stock', 'reorder_level'];
        foreach ($data as $d) {
            $rows[] = [
                $d['product_id'] ?? '',
                $d['product_name'] ?? '',
                $d['current_stock'] ?? '',
                $d['reorder_level'] ?? ''
            ];
        }
    } elseif ($type === 'Profit & Loss') {
        $headers = ['date', 'revenue', 'expenses', 'profit'];
        foreach ($data as $d) {
            $rows[] = [
                asExcelText($d['date'] ?? ''),
                $d['revenue'] ?? '',
                $d['expenses'] ?? '',
                $d['profit'] ?? ''
            ];
        }
    } elseif ($type === 'Transaction Report') {
        $headers = ['transaction_id', 'transaction_type', 'amount', 'date'];
        foreach ($data as $d) {
            $rows[] = [
                $d['transaction_id'] ?? '',
                $d['transaction_type'] ?? '',
                $d['amount'] ?? '',
                asExcelText($d['date'] ?? '')
            ];
        }
    } else {
        $headers = ['data'];
        foreach ($data as $d) {
            $rows[] = [json_encode($d)];
        }
    }

    outputCsv($reportNameSafe . ".csv", $headers, $rows);
}

// --- PDF export via TCPDF (offline, no composer) ---
if ($format === 'pdf') {
    // Your TCPDF folder is: C:\xampp\htdocs\Group3\tcpdf\tcpdf.php
    // This relative path is correct from Module7/ -> ../tcpdf/tcpdf.php
    $tcpdfPath = __DIR__ . '/../tcpdf/tcpdf.php';

    if (!file_exists($tcpdfPath)) {
        http_response_code(500);
        echo "TCPDF not found at: " . htmlspecialchars($tcpdfPath);
        exit;
    }

    require_once $tcpdfPath;

    // Landscape for Transaction Report (optional but helpful)
    $orientation = (($report['report_type'] ?? '') === 'Transaction Report') ? 'L' : 'P';

    $pdf = new TCPDF($orientation, 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Coffee Business');
    $pdf->SetAuthor('Module 7 BI');
    $pdf->SetTitle($report['report_name'] ?? 'Report');
    $pdf->SetMargins(10, 15, 10);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, (string)($report['report_name'] ?? 'Report'), 0, 1, 'L');
    $pdf->Ln(2);

    // Metadata
    $pdf->SetFont('helvetica', '', 10);
    $meta = "Type: " . ($report['report_type'] ?? '') . "\n" .
            "From: " . ($report['date_from'] ?? 'All') . "\n" .
            "To: " . ($report['date_to'] ?? 'All') . "\n" .
            "Department: " . ($report['department'] ?? 'All') . "\n" .
            "Region: " . ($report['region'] ?? 'All') . "\n" .
            "Generated At: " . ($report['generated_at'] ?? '');
    $pdf->MultiCell(0, 6, $meta, 0, 'L');
    $pdf->Ln(4);

    // Build HTML table
    if (!empty($data) && is_array($data)) {
        $keys = array_keys($data[0]);

        $html = '<table border="1" cellpadding="5" cellspacing="0">
                    <thead>
                      <tr style="background-color:#eeeeee;">';
        foreach ($keys as $k) {
            $html .= '<th><b>' . htmlspecialchars((string)$k) . '</b></th>';
        }
        $html .= '     </tr>
                    </thead>
                    <tbody>';

        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($keys as $k) {
                $html .= '<td>' . htmlspecialchars((string)($row[$k] ?? '')) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '   </tbody>
                  </table>';
    } else {
        $html = '<p>No data available.</p>';
    }

    $pdf->writeHTML($html, true, false, true, false, '');

    $filename = $reportNameSafe . '.pdf';
    $pdf->Output($filename, 'D');
    exit;
}

http_response_code(400);
echo "Invalid format.";
