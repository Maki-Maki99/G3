//<?php
// Module9/project_functions.php
// Basic CRUD stubs and integration checks (assignment blocking and budget check).
// IMPORTANT: This file assumes you have a shared DB connection at ../shared/db.php
// and that connectors are placed at Module9/connectors/*

include_once _DIR_ . '/connectors/HRConnector.php';
include_once _DIR_ . '/connectors/FinanceConnector.php';
include_once _DIR_ . '/logs/log_helper.php';

// Adjust path to your shared DB connection file if different:
$dbpath = _DIR_ . '/../shared/db.php';
if (file_exists($dbpath)) {
    include_once $dbpath;
} else {
    // If no shared db, create a lightweight mysqli connection here or handle gracefully.
    $conn = null;
}

// ======= Helper: safe query wrapper (read-only use) =======
function db_query($sql) {
    global $conn;
    if (!$conn) return false;
    $res = mysqli_query($conn, $sql);
    return $res;
}

// ======= Projects: Create (simplified) =======
function createProject($data) {
    global $conn;
    if (!$conn) return false;
    $stmt = $conn->prepare("INSERT INTO projects (project_code, project_name, description, start_date, end_date, budget_planned, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssddi",
        $data['project_code'],
        $data['project_name'],
        $data['description'],
        $data['start_date'],
        $data['end_date'],
        $data['budget_planned'],
        $data['created_by']
    );
    $ok = $stmt->execute();
    $stmt->close();
    return $ok ? $conn->insert_id : false;
}

// ======= Tasks: Create with HR check (UPDATED) =======
function assignTaskToEmployee($taskData) {
    // $taskData expects: project_id, task_name, assigned_employee_id, start_date, end_date, estimated_hours
    $empId = intval($taskData['assigned_employee_id']);
    $employee = getEmployeeDetails($empId);
    
    if ($employee === null) {
        module9_log('integration.log', "ASSIGN_ATTEMPT employee_id={$empId} result=FAIL reason=HR_UNAVAILABLE");
        return ['ok' => false, 'message' => 'HR service unavailable. Try again later.'];
    }

    $status = isset($employee['status']) ? strtolower($employee['status']) : '';

    // Only allow 'active' employees to be assigned
    if ($status !== 'active') {
        module9_log('integration.log', "ASSIGN_ATTEMPT employee_id={$empId} result=BLOCKED reason=\"{$status}\" response=" . json_encode($employee));
        return ['ok' => false, 'message' => "Cannot assign employee: {$status}"];
    }

    // Passed HR check — now insert task
    global $conn;
    if (!$conn) return ['ok' => false, 'message' => 'Database connection not available'];

    $stmt = $conn->prepare("INSERT INTO project_tasks (project_id, task_code, task_name, description, assigned_employee_id, start_date, end_date, estimated_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $task_code = 'T' . time();
    $stmt->bind_param("isssiidd",
        $taskData['project_id'],
        $task_code,
        $taskData['task_name'],
        $taskData['description'],
        $taskData['assigned_employee_id'],
        $taskData['start_date'],
        $taskData['end_date'],
        $taskData['estimated_hours']
    );
    $ok = $stmt->execute();
    $inserted_id = $ok ? $conn->insert_id : false;
    $stmt->close();

    module9_log('integration.log', "ASSIGN_ATTEMPT employee_id={$empId} project_id={$taskData['project_id']} result=" . ($ok ? 'OK' : 'DB_FAIL') );
    return ['ok' => (bool)$ok, 'task_id' => $inserted_id];
}

// ======= Budget Check before adding cost/approving spend =======
function checkProjectBudget($project_id) {
    $budget = getProjectBudget($project_id);
    if ($budget === null) {
        module9_log('integration.log', "BUDGET_CHECK project_id={$project_id} result=FAIL reason=FINANCE_UNAVAILABLE");
        return ['ok' => false, 'message' => 'Finance service unavailable. Try again later.'];
    }

    $allocated = isset($budget['allocated_budget']) ? floatval($budget['allocated_budget']) : 0.0;
    $used = isset($budget['used_budget']) ? floatval($budget['used_budget']) : 0.0;
    $remaining = isset($budget['remaining_budget']) ? floatval($budget['remaining_budget']) : ($allocated - $used);

    if ($remaining <= 0) {
        module9_log('integration.log', "BUDGET_CHECK project_id={$project_id} result=BLOCKED remaining={$remaining} response=" . json_encode($budget));
        return ['ok' => false, 'message' => "Budget limit reached: remaining = {$remaining}"];
    }

    module9_log('integration.log', "BUDGET_CHECK project_id={$project_id} result=OK remaining={$remaining}");
    return ['ok' => true, 'allocated' => $allocated, 'used' => $used, 'remaining' => $remaining];
}

// Add other CRUD helpers (updateProject, deleteProject, listProjects, getProjectById, updateTask, etc.) as needed.
