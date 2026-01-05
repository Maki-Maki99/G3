<?php
require_once 'db_connection.php';

class BIModule {
    private $db;

    public function __construct() {
        $this->db = new DatabaseConnection();
    }

    private function tableExists($tableName) {
        $tableName = $this->db->escape($tableName);
        $sql = "SHOW TABLES LIKE '$tableName'";
        $result = $this->db->query($sql);
        return ($result && $result->num_rows > 0);
    }

    private function columnExists($tableName, $columnName) {
        $tableName = $this->db->escape($tableName);
        $columnName = $this->db->escape($columnName);
        $sql = "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'";
        $result = $this->db->query($sql);
        return ($result && $result->num_rows > 0);
    }

    private function buildDateWhere($dateColumn, $dateFrom, $dateTo, $wrapDateFn = false) {
        $where = [];

        if ($dateFrom) {
            $df = $this->db->escape($dateFrom);
            $where[] = ($wrapDateFn ? "DATE($dateColumn) >= '$df'" : "$dateColumn >= '$df'");
        }

        if ($dateTo) {
            $dt = $this->db->escape($dateTo);
            $where[] = ($wrapDateFn ? "DATE($dateColumn) <= '$dt'" : "$dateColumn <= '$dt'");
        }

        return !empty($where) ? ("WHERE " . implode(" AND ", $where)) : "";
    }

    private function validateDates($dateFrom, $dateTo) {
        $today = date('Y-m-d');

        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            throw new Exception("Invalid date range: From date must be earlier than or equal to To date.");
        }
        if ($dateFrom && $dateFrom > $today) {
            throw new Exception("Invalid date: From date cannot be later than today ($today).");
        }
        if ($dateTo && $dateTo > $today) {
            throw new Exception("Invalid date: To date cannot be later than today ($today).");
        }
    }

    public function generateReport($reportType, $dateFrom = null, $dateTo = null, $department = null, $region = null) {
        $this->validateDates($dateFrom, $dateTo);

        $reportTypeClean = $this->db->escape($reportType);
        $reportName = $reportTypeClean . " Report";
        $sourceModule = "Module7";
        $status = "generated";

        $hasDateFrom = $this->columnExists('bi_reports', 'date_from');
        $hasDateTo   = $this->columnExists('bi_reports', 'date_to');
        $hasDept     = $this->columnExists('bi_reports', 'department');
        $hasRegion   = $this->columnExists('bi_reports', 'region');

        $cols = ["report_name", "report_type", "source_module", "status"];
        $vals = [
            "'" . $this->db->escape($reportName) . "'",
            "'" . $reportTypeClean . "'",
            "'" . $this->db->escape($sourceModule) . "'",
            "'" . $this->db->escape($status) . "'"
        ];

        if ($hasDateFrom) { $cols[] = "date_from"; $vals[] = $dateFrom ? "'" . $this->db->escape($dateFrom) . "'" : "NULL"; }
        if ($hasDateTo)   { $cols[] = "date_to";   $vals[] = $dateTo   ? "'" . $this->db->escape($dateTo) . "'"   : "NULL"; }
        if ($hasDept)     { $cols[] = "department";$vals[] = $department ? "'" . $this->db->escape($department) . "'" : "NULL"; }
        if ($hasRegion)   { $cols[] = "region";    $vals[] = $region     ? "'" . $this->db->escape($region) . "'"     : "NULL"; }

        $sql = "INSERT INTO bi_reports (" . implode(",", $cols) . ")
                VALUES (" . implode(",", $vals) . ")";
        $this->db->query($sql);

        $reportId = (int)$this->db->getConnection()->insert_id;

        switch ($reportType) {
            case 'Sales Summary':
                $this->processSalesSummary($reportId, $dateFrom, $dateTo);
                break;
            case 'Inventory Stock':
                $this->processInventoryStock($reportId);
                break;
            case 'Profit & Loss':
                $this->processProfitLoss($reportId, $dateFrom, $dateTo);
                break;
            case 'Transaction Report':
                $this->processTransactionReport($reportId, $dateFrom, $dateTo);
                break;
        }

        return $reportId;
    }

    private function processInventoryStock($reportId) {
        if (!$this->tableExists('bi_inventory_summary')) return;

        $sql = "SELECT 
                    id AS product_id,
                    name AS product_name,
                    COALESCE(total_quantity, 0) AS current_stock,
                    COALESCE(min_qty, 0) AS reorder_level
                FROM products
                ORDER BY name ASC";

        $result = $this->db->query($sql);
        while ($row = $result->fetch_assoc()) {
            $productId = (int)$row['product_id'];
            $productName = $this->db->escape($row['product_name']);
            $stock = (int)$row['current_stock'];
            $reorder = (int)$row['reorder_level'];

            $ins = "INSERT INTO bi_inventory_summary (report_id, product_id, product_name, current_stock, reorder_level)
                    VALUES ($reportId, '$productId', '$productName', $stock, $reorder)";
            $this->db->query($ins);
        }
    }

    private function processSalesSummary($reportId, $dateFrom, $dateTo) {
        if (!$this->tableExists('bi_sales_summary')) return;

        $whereSql = $this->buildDateWhere("so.created_at", $dateFrom, $dateTo, true);

        $sql = "SELECT
                    soi.product_id AS product_id,
                    COALESCE(p.name, CONCAT('Product ', soi.product_id)) AS product_name,
                    COALESCE(DATE(so.created_at), CURDATE()) AS sale_date,
                    SUM(COALESCE(soi.qty,0)) AS total_qty,
                    SUM(COALESCE(soi.line_total, (COALESCE(soi.qty,0) * COALESCE(soi.unit_price,0)))) AS total_amount
                FROM sales_order_items soi
                LEFT JOIN sales_orders so ON so.id = soi.sales_order_id
                LEFT JOIN products p ON p.id = soi.product_id
                $whereSql
                GROUP BY soi.product_id, COALESCE(DATE(so.created_at), CURDATE())
                ORDER BY sale_date DESC";

        $result = $this->db->query($sql);
        while ($row = $result->fetch_assoc()) {
            $productId = (int)$row['product_id'];
            $productName = $this->db->escape($row['product_name']);
            $qty = (int)$row['total_qty'];
            $amt = (float)$row['total_amount'];
            $date = $this->db->escape($row['sale_date']);

            $ins = "INSERT INTO bi_sales_summary (report_id, product_id, product_name, total_quantity, total_amount, date)
                    VALUES ($reportId, '$productId', '$productName', $qty, $amt, '$date')";
            $this->db->query($ins);
        }
    }

    private function processProfitLoss($reportId, $dateFrom, $dateTo) {
        if (!$this->tableExists('bi_profit_loss')) return;

        $payWhereSql = $this->buildDateWhere("payment_date", $dateFrom, $dateTo, false);
        $payrollWhereSql = $this->buildDateWhere("payroll_date", $dateFrom, $dateTo, false);

        $invWhereParts = ["status = 'paid'"];
        if ($dateFrom) $invWhereParts[] = "invoice_date >= '" . $this->db->escape($dateFrom) . "'";
        if ($dateTo)   $invWhereParts[] = "invoice_date <= '" . $this->db->escape($dateTo) . "'";
        $invWhereSql = "WHERE " . implode(" AND ", $invWhereParts);

        $sql = "
            SELECT d.day AS date,
                   COALESCE(r.revenue,0) AS revenue,
                   COALESCE(e.expenses,0) AS expenses,
                   (COALESCE(r.revenue,0) - COALESCE(e.expenses,0)) AS profit
            FROM (
                SELECT payment_date AS day FROM payments $payWhereSql
                UNION
                SELECT payroll_date AS day FROM payroll_expenses $payrollWhereSql
                UNION
                SELECT invoice_date AS day FROM invoices $invWhereSql
            ) d
            LEFT JOIN (
                SELECT payment_date AS day, SUM(amount) AS revenue
                FROM payments
                $payWhereSql
                GROUP BY payment_date
            ) r ON r.day = d.day
            LEFT JOIN (
                SELECT day, SUM(exp) AS expenses
                FROM (
                    SELECT payroll_date AS day, SUM(COALESCE(net_pay,0)) AS exp
                    FROM payroll_expenses
                    $payrollWhereSql
                    GROUP BY payroll_date
                    UNION ALL
                    SELECT invoice_date AS day, SUM(total_amount) AS exp
                    FROM invoices
                    $invWhereSql
                    GROUP BY invoice_date
                ) x
                GROUP BY day
            ) e ON e.day = d.day
            ORDER BY d.day DESC
        ";

        $result = $this->db->query($sql);
        while ($row = $result->fetch_assoc()) {
            $date = $this->db->escape($row['date']);
            $revenue = (float)$row['revenue'];
            $expenses = (float)$row['expenses'];
            $profit = (float)$row['profit'];

            $ins = "INSERT INTO bi_profit_loss (report_id, revenue, expenses, profit, date)
                    VALUES ($reportId, $revenue, $expenses, $profit, '$date')";
            $this->db->query($ins);
        }
    }

    private function processTransactionReport($reportId, $dateFrom, $dateTo) {
        if (!$this->tableExists('bi_transactions')) return;

        // Payments
        $wherePayments = $this->buildDateWhere("payment_date", $dateFrom, $dateTo, false);
        $sql = "SELECT id AS transaction_id, 'payment' AS transaction_type, amount, payment_date AS date
                FROM payments
                $wherePayments
                ORDER BY payment_date DESC";
        $res = $this->db->query($sql);
        while ($row = $res->fetch_assoc()) {
            $tid = $this->db->escape($row['transaction_id']);
            $type = $this->db->escape($row['transaction_type']);
            $amt = (float)$row['amount'];
            $date = $this->db->escape($row['date']);

            $ins = "INSERT INTO bi_transactions (report_id, transaction_id, transaction_type, amount, date)
                    VALUES ($reportId, '$tid', '$type', $amt, '$date')";
            $this->db->query($ins);
        }

        // Supplier invoices
        $whereInvoices = $this->buildDateWhere("invoice_date", $dateFrom, $dateTo, false);
        $sql = "SELECT id AS transaction_id, CONCAT('invoice_', status) AS transaction_type, total_amount AS amount, invoice_date AS date
                FROM invoices
                $whereInvoices
                ORDER BY invoice_date DESC";
        $res = $this->db->query($sql);
        while ($row = $res->fetch_assoc()) {
            $tid = $this->db->escape($row['transaction_id']);
            $type = $this->db->escape($row['transaction_type']);
            $amt = (float)$row['amount'];
            $date = $this->db->escape($row['date']);

            $ins = "INSERT INTO bi_transactions (report_id, transaction_id, transaction_type, amount, date)
                    VALUES ($reportId, '$tid', '$type', $amt, '$date')";
            $this->db->query($ins);
        }

        // Stock transactions
        $whereStock = $this->buildDateWhere("trans_date", $dateFrom, $dateTo, true);
        $sql = "SELECT id AS transaction_id, type AS transaction_type, qty AS amount, DATE(trans_date) AS date
                FROM stock_transactions
                $whereStock
                ORDER BY trans_date DESC";
        $res = $this->db->query($sql);
        while ($row = $res->fetch_assoc()) {
            $tid = $this->db->escape($row['transaction_id']);
            $type = $this->db->escape($row['transaction_type']);
            $amt = (float)$row['amount'];
            $date = $this->db->escape($row['date']);

            $ins = "INSERT INTO bi_transactions (report_id, transaction_id, transaction_type, amount, date)
                    VALUES ($reportId, '$tid', '$type', $amt, '$date')";
            $this->db->query($ins);
        }
    }

    public function getReportData($reportId) {
        $reportId = (int)$reportId;

        $sql = "SELECT * FROM bi_reports WHERE id = $reportId";
        $result = $this->db->query($sql);
        $report = ($result) ? $result->fetch_assoc() : null;

        if (!$report) return null;

        $reportType = $report['report_type'];
        $data = [];

        switch ($reportType) {
            case 'Sales Summary':
                if ($this->tableExists('bi_sales_summary')) {
                    $sql = "SELECT * FROM bi_sales_summary WHERE report_id = $reportId";
                    $result = $this->db->query($sql);
                    while ($row = $result->fetch_assoc()) $data[] = $row;
                }
                break;

            case 'Inventory Stock':
                if ($this->tableExists('bi_inventory_summary')) {
                    $sql = "SELECT * FROM bi_inventory_summary WHERE report_id = $reportId";
                    $result = $this->db->query($sql);
                    while ($row = $result->fetch_assoc()) $data[] = $row;
                }
                break;

            case 'Profit & Loss':
                if ($this->tableExists('bi_profit_loss')) {
                    $sql = "SELECT * FROM bi_profit_loss WHERE report_id = $reportId";
                    $result = $this->db->query($sql);
                    while ($row = $result->fetch_assoc()) $data[] = $row;
                }
                break;

            case 'Transaction Report':
                if ($this->tableExists('bi_transactions')) {
                    $sql = "SELECT * FROM bi_transactions WHERE report_id = $reportId";
                    $result = $this->db->query($sql);
                    while ($row = $result->fetch_assoc()) $data[] = $row;
                }
                break;
        }

        return [
            'report' => $report,
            'data' => $data
        ];
    }

    public function getRecentReports($limit = 10) {
        $limit = (int)$limit;
        $sql = "SELECT * FROM bi_reports ORDER BY generated_at DESC LIMIT $limit";
        $result = $this->db->query($sql);

        $reports = [];
        while ($row = $result->fetch_assoc()) $reports[] = $row;
        return $reports;
    }

    // NEW: Delete report + its data
    public function deleteReport($reportId) {
        $reportId = (int)$reportId;

        if ($this->tableExists('bi_sales_summary')) {
            $this->db->query("DELETE FROM bi_sales_summary WHERE report_id = $reportId");
        }
        if ($this->tableExists('bi_inventory_summary')) {
            $this->db->query("DELETE FROM bi_inventory_summary WHERE report_id = $reportId");
        }
        if ($this->tableExists('bi_profit_loss')) {
            $this->db->query("DELETE FROM bi_profit_loss WHERE report_id = $reportId");
        }
        if ($this->tableExists('bi_transactions')) {
            $this->db->query("DELETE FROM bi_transactions WHERE report_id = $reportId");
        }

        $this->db->query("DELETE FROM bi_reports WHERE id = $reportId");
        return true;
    }

    public function close() {
        $this->db->close();
    }
}
