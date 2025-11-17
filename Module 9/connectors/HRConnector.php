//<?php
// Module9/connectors/HRConnector.php
// Read-only connector to Module 10 (HR). Returns associative array or null.

$config = include _DIR_ . '/config.php';

function getEmployeeDetails($employee_id) {
    global $config;
    $base = rtrim($config['HR_BASE_URL'], '/');
    // Expected HR endpoint: GET {base}/employee.php?id={id}
    $url = $base . '/employee.php?id=' . urlencode(intval($employee_id));

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $config['CONNECT_TIMEOUT']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false || $code !== 200) {
        // HR service unavailable
        return null;
    }

    $data = json_decode($resp, true);
    if (json_last_error() !== JSON_ERROR_NONE) return null;

    // Map response to expected format for Module 9
    // Only keep 'status' (lowercase)
    $result = [];
    $result['employee_id'] = isset($data['employee_id']) ? intval($data['employee_id']) : null;
    $result['status'] = isset($data['status']) ? strtolower($data['status']) : 'inactive';
    $result['first_name'] = isset($data['first_name']) ? $data['first_name'] : '';
    $result['last_name'] = isset($data['last_name']) ? $data['last_name'] : '';

    return $result;
}
