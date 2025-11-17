//<?php
// Module9/task_form.php
if (file_exists(_DIR_ . '/../shared/header.php')) include_once _DIR_ . '/../shared/header.php';
else { echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><div class="container my-4">'; }

include_once _DIR_ . '/project_functions.php';
include_once _DIR_ . '/connectors/HRConnector.php';

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

$employees = [];
// Example: fetch first 20 active employees for dropdown
$res = db_query("SELECT employee_id, CONCAT(first_name,' ',last_name) AS full_name, status FROM hr_employees WHERE status='active' LIMIT 20");
while ($row = mysqli_fetch_assoc($res)) $employees[] = $row;

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskData = [
        'project_id' => $project_id,
        'task_name' => $_POST['task_name'],
        'description' => $_POST['description'],
        'assigned_employee_id' => intval($_POST['assigned_employee_id']),
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'estimated_hours' => floatval($_POST['estimated_hours'])
    ];

    $result = assignTaskToEmployee($taskData);
    $msg = $result['message'] ?? ($result['ok'] ? 'Task created successfully!' : 'Error');
    if ($result['ok']) header("Location: task_list.php?project_id=$project_id");
}

?>

<div class="container my-4">
  <h3>Add Task for Project #<?= $project_id ?></h3>
  <?php if($msg) echo "<div class='alert alert-warning'>$msg</div>"; ?>
  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Task Name</label>
      <input type="text" name="task_name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control"></textarea>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Start Date</label>
        <input type="date" name="start_date" class="form-control">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">End Date</label>
        <input type="date" name="end_date" class="form-control">
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Estimated Hours</label>
      <input type="number" step="0.1" name="estimated_hours" class="form-control">
    </div>
    <div class="mb-3">
      <label class="form-label">Assign Employee</label>
      <select name="assigned_employee_id" class="form-select" required>
        <option value="">-- Select Employee --</option>
        <?php foreach($employees as $e) {
            echo "<option value='{$e['employee_id']}'>" . htmlspecialchars($e['full_name']) . "</option>";
        } ?>
      </select>
    </div>
    <button type="submit" class="btn btn-success">Add Task</button>
    <a href="task_list.php?project_id=<?= $project_id ?>" class="btn btn-secondary">Cancel</a>
  </form>
</div>

<?php
if (file_exists(_DIR_ . '/../shared/footer.php')) include_once _DIR_ . '/../shared/footer.php';
else echo '</div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>';
?>
