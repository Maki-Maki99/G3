<?php
// Module9/project_details.php
if (file_exists(__DIR__ . '/../shared/header.php')) include_once __DIR__ . '/../shared/header.php';
else { echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><div class="container my-4">'; }

include_once __DIR__ . '/project_functions.php';
global $conn;

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo "<div class='alert alert-warning'>Missing project id</div>"; exit; }
$res = db_query("SELECT * FROM projects WHERE project_id = {$id} LIMIT 1");
$project = $res ? mysqli_fetch_assoc($res) : null;
if (!$project) { echo "<div class='alert alert-warning'>Project not found</div>"; exit; }

// Budget check via Finance connector
$budgetInfo = getProjectBudget($id);
?>

<h2><?= htmlspecialchars($project['project_name']) ?> <small class="text-muted">#<?= $project['project_id'] ?></small></h2>
<p><?= htmlspecialchars($project['description']) ?></p>

<div class="mb-3">
    <strong>Budget (Finance):</strong>
    <?php if ($budgetInfo === null): ?>
        <span class="text-warning">Finance service unavailable</span>
    <?php else: ?>
        ₱<?= number_format($budgetInfo['used_budget'] ?? 0,2) ?> used / ₱<?= number_format($budgetInfo['allocated_budget'] ?? ($project['budget_planned'] ?? 0),2) ?> allocated
    <?php endif; ?>
    <a class="btn btn-sm btn-outline-primary ms-3" href="task_manage.php?project_id=<?= $id ?>">Manage Tasks</a>
</div>

<div class="card">
  <div class="card-body">
    <h5>Tasks</h5>
    <?php
    $tasks = [];
    if ($conn) {
        $r = db_query("SELECT * FROM project_tasks WHERE project_id = {$id} ORDER BY created_at DESC");
        if ($r) while ($row = mysqli_fetch_assoc($r)) $tasks[] = $row;
    }
    ?>
    <table class="table">
      <thead><tr><th>#</th><th>Task</th><th>Assigned</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (empty($tasks)): ?>
        <tr><td colspan="4" class="text-center">No tasks yet</td></tr>
      <?php else: foreach($tasks as $t): ?>
        <tr>
          <td><?= $t['task_id'] ?></td>
          <td><?= htmlspecialchars($t['task_name']) ?></td>
          <td><?= htmlspecialchars($t['assigned_employee_id']) ?></td>
          <td><?= htmlspecialchars($t['status']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
if (file_exists(__DIR__ . '/../shared/footer.php')) include_once __DIR__ . '/../shared/footer.php';
else echo '</div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>';
?>
