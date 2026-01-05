<?php
class APIClient {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    private function tableExists($tableName) {
        $tableName = $this->db->escape($tableName);
        $sql = "SHOW TABLES LIKE '$tableName'";
        $result = $this->db->query($sql);
        return ($result && $result->num_rows > 0);
    }

    public function getModuleEndpoints() {
        if (!$this->tableExists('module_endpoints')) {
            return [];
        }

        // Only active endpoints
        $sql = "SELECT * FROM module_endpoints WHERE is_active = 1";
        $result = $this->db->query($sql);

        $endpoints = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $endpoints[] = $row;
            }
        }

        return $endpoints;
    }

    public function callAPI($url, $method = 'GET', $data = null) {
        // If curl isn't enabled, return a clear error
        if (!function_exists('curl_init')) {
            return [
                'success' => false,
                'error' => 'cURL is not enabled in PHP',
                'data' => null,
                'http_code' => 0
            ];
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $headers = [];

        $method = strtoupper($method);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                $payload = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                $headers[] = 'Content-Type: application/json';
            }
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);

        curl_close($ch);

        if ($curlErr) {
            return [
                'success' => false,
                'error' => $curlErr,
                'data' => null,
                'http_code' => $httpCode ?: 0
            ];
        }

        // Try decode JSON
        $decoded = null;
        if ($response !== false && $response !== '') {
            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response: ' . json_last_error_msg(),
                    'data' => null,
                    'http_code' => $httpCode
                ];
            }
        }

        return [
            'success' => true,
            'data' => $decoded,
            'http_code' => $httpCode
        ];
    }

    public function logAPICall($module, $endpoint, $method, $requestData, $responseCode, $responseData) {
        // If table doesn't exist, just skip logging
        if (!$this->tableExists('api_call_logs')) {
            return;
        }

        $module = $this->db->escape((string)$module);
        $endpoint = $this->db->escape((string)$endpoint);
        $method = $this->db->escape((string)$method);

        $requestDataJson = $this->db->escape(json_encode($requestData));
        $responseDataJson = $this->db->escape(json_encode($responseData));

        $responseCode = (int)$responseCode;

        $sql = "INSERT INTO api_call_logs (module_name, endpoint, request_method, request_data, response_code, response_data)
                VALUES ('$module', '$endpoint', '$method', '$requestDataJson', $responseCode, '$responseDataJson')";

        $this->db->query($sql);
    }

    public function fetchAllModuleData() {
        $endpoints = $this->getModuleEndpoints();
        $allData = [];

        foreach ($endpoints as $endpoint) {
            $moduleName = $endpoint['module_name'] ?? 'Unknown';
            $url = $endpoint['endpoint_url'] ?? '';
            $method = $endpoint['request_method'] ?? 'GET';

            if ($url === '') {
                $allData[$moduleName] = ['error' => 'Missing endpoint_url'];
                $this->logAPICall($moduleName, '(missing url)', $method, null, 0, ['error' => 'Missing endpoint_url']);
                continue;
            }

            $response = $this->callAPI($url, $method);

            // LOG ALWAYS (success or fail)
            $this->logAPICall(
                $moduleName,
                $url,
                $method,
                null,
                $response['http_code'] ?? 0,
                $response['success'] ? ($response['data'] ?? null) : ['error' => $response['error'] ?? 'Unknown error']
            );

            if (!empty($response['success'])) {
                $allData[$moduleName] = $response['data'];
            } else {
                $allData[$moduleName] = ['error' => $response['error'] ?? 'Unknown error'];
            }
        }

        return $allData;
    }
}
?>
