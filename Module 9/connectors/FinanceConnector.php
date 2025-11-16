<?php
// Module9/connectors/FinanceConnector.php
// Read-only connector to Module 5 (Finance). Returns associative array or null.

$config = include __DIR__ . '/config.php';

function getProjectBudget($project_id) {
    global $config;
    $base = rtrim($config['FINANCE_BASE_URL'], '/');
    // Expected Finance endpoint: GET {base}/project_budget.php?project_id={id}
    $url = $base . '/project_budget.php?project_id=' . urlencode(intval($project_id));

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $config['CONNECT_TIMEOUT']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false || $code !== 200) {
        return null;
    }

    $data = json_decode($resp, true);
    if (json_last_error() !== JSON_ERROR_NONE) return null;
    return $data;
}