//<?php
// Module9/project_form.php
if (file_exists(_DIR_ . '/../shared/header.php')) include_once _DIR_ . '/../shared/header.php';
else { echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><div class="container my-4">'; }

include_once _DIR_ . '/project_functions.php';
global $conn;

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$project = null;

if ($project_id > 0) {
    $res = db_query("SELECT * FROM projects WHERE project_id=$project_id");
    $project = $res ? mysqli_fetch_assoc($res) : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'project_code' => $_POST['project_code'] ?? '',
        'project_name' => $_POST['project_name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'start_date' => $_POST['start_date'] ?? null,
        'end_date' => $_POST['end_date'] ?? null,
        'budget_planned' => floatval($_POST['budget_planned'] ?? 0),
        'created_by' => 1 // placeholder, update with logged-in user
    ];

    if ($project) {
        // TODO: implement updateProject() in project_functions.php
        // updateProject($project_id, $data);
        $msg = "Update functionality not yet implemented.";
    } else {
        $id = createProject($data);
        if ($id) {
            header("Location: project_list.php");
            exit;
        } else {
            $msg = "Failed to create project.";
        }
    }
}

?>

<div class="container my-4">
  <h3><?= $project ? 'Edit Project' : 'Add New Project' ?></h3>
  <?php if(isset($msg)) echo "<div class='alert alert-warning'>$msg</div>"; ?>
  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Project Code</label>
      <input type="text" name="project_code" class="form-control" value="<?= $project['project_code'] ?? '' ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Project Name</label>
      <input type="text" name="project_name" class="form-control" value="<?= $project['project_name'] ?? '' ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control"><?= $project['description'] ?? '' ?></textarea>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Start Date</label>
        <input type="date" name="start_date" class="form-control" value="<?= $project['start_date'] ?? '' ?>">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">End Date</label>
        <input type="date" name="end_date" class="form-control" value="<?= $project['end_date'] ?? '' ?>">
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Planned Budget</label>
      <input type="number" step="0.01" name="budget_planned" class="form-control" value="<?= $project['budget_planned'] ?? '' ?>">
    </div>
    <button type="submit" class="btn btn-success"><?= $project ? 'Update' : 'Create' ?></button>
    <a href="project_list.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>

<?php
if (file_exists(_DIR_ . '/../shared/footer.php')) include_once _DIR_ . '/../shared/footer.php';
else echo '</div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>';
?>
