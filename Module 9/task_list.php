//<?php
// Module9/task_list.php
if (file_exists(_DIR_ . '/../shared/header.php')) include_once _DIR_ . '/../shared/header.php';
else { echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><div class="container my-4">'; }

include_once _DIR_ . '/project_functions.php';
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

$r = db_query("SELECT * FROM project_tasks WHERE project_id=$project_id ORDER BY created_at DESC");
?>

<div class="container my-4">
  <h3>Tasks for Project #<?= $project_id ?></h3>
  <a href="task_form.php?project_id=<?= $project_id ?>" class="btn btn-primary mb-3">Add New Task</a>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Task Code</th>
        <th>Name</th>
        <th>Assigned Employee</th>
        <th>Status</th>
        <th>Start</th>
        <th>End</th>
        <th>% Complete</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = mysqli_fetch_assoc($r)) { ?>
        <tr>
          <td><?= htmlspecialchars($row['task_code']) ?></td>
          <td><?= htmlspecialchars($row['task_name']) ?></td>
          <td><?= intval($row['assigned_employee_id']) ?></td>
          <td><?= htmlspecialchars($row['status']) ?></td>
          <td><?= htmlspecialchars($row['start_date']) ?></td>
          <td><?= htmlspecialchars($row['end_date']) ?></td>
          <td><?= intval($row['percent_complete']) ?>%</td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
  <a href="project_list.php" class="btn btn-secondary">Back to Projects</a>
</div>

<?php
if (file_exists(_DIR_ . '/../shared/footer.php')) include_once _DIR_ . '/../shared/footer.php';
else echo '</div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>';
?>
