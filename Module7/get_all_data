<?php

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

function fetch_url($url, $timeout) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FAILONERROR => false,
        CURLOPT_HEADER => false
    ]);
    $resp = curl_exec($ch);
    $errNo = curl_errno($ch);
    $errMsg = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errNo) {
        return ['error' => true, 'curl_errno' => $errNo, 'curl_error' => $errMsg];
    }

    // Try to decode JSON if possible
    $decoded = json_decode($resp, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return ['error' => false, 'http_code' => $httpCode, 'data' => $decoded];
    } else {
        // return raw as fallback
        return ['error' => false, 'http_code' => $httpCode, 'data_raw' => $resp];
    }
}

$output = ['fetched_at' => date('Y-m-d H:i:s'), 'modules' => []];

foreach ($ENDPOINTS as $name => $url) {
    $result = fetch_url($url, $HTTP_TIMEOUT);
    $output['modules'][$name] = $result;
}

// Pretty-print JSON for readability
echo json_encode($output, JSON_PRETTY_PRINT)
