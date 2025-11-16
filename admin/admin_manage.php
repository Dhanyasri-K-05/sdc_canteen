<?php
// admin_manage.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

$success = '';
$error = '';

try {
    // Add Admin
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
        $email = trim($_POST['admin_email']);
        $roll_no = 'ADM_' . uniqid();
        $password = trim($_POST['admin_password']);
        if ($password === '') $password = $roll_no;
        if (empty($email)) throw new Exception("Email required.");
        $userModel->createUser($roll_no, $email, $password, 'admin');
        $success = "Admin added. Default Roll No: $roll_no";
    }

    // Edit Admin
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_admin'])) {
        $id = intval($_POST['admin_id']);
        $email = trim($_POST['admin_email_edit']);
        if (empty($email)) throw new Exception("Email required.");
        $userModel->updateUser($id, $email, 'admin');
        $success = "Admin updated.";
    }

    // Delete Admin
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
        $id = intval($_POST['admin_id']);
        // prevent deleting yourself
        if ($id == $_SESSION['user_id']) throw new Exception("Cannot delete current admin while logged in.");
        if ($userModel->deleteUser($id)) $success = "Admin deleted.";
        else $error = "Failed to delete admin.";
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

$admins = $userModel->getUsersByRole('admin');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Manage Admins</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">Admin Dashboard</a>
    <div>
         <a class="btn btn-sm btn-outline-light me-1" href="dashboard.php">Dashboard</a>
      <a class="btn btn-sm btn-outline-light me-1" href="user_manage.php">Manage Users</a>
      <a class="btn btn-sm btn-outline-light me-1" href="staff_manage.php">Manage Staff</a>
      <a class="btn btn-sm btn-outline-light ms-2" href="../logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <h3>Manage Admins</h3>

  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-2">
    <div></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal"><i class="fas fa-plus"></i> Add Admin</button>
  </div>

  <?php if (count($admins) === 0): ?>
    <p class="text-muted">No admins found.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped table-bordered align-middle text-center">
        <thead class="table-dark"><tr><th>ID</th><th>Roll No</th><th>Email</th><th>Created At</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($admins as $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['id']) ?></td>
            <td><?= htmlspecialchars($a['roll_no']) ?></td>
            <td><?= htmlspecialchars($a['email']) ?></td>
            <td><?= htmlspecialchars(date('d-m-Y', strtotime($a['created_at']))) ?></td>
            <td>
              <button class="btn btn-sm btn-warning" onclick="openEditAdminModal('<?= $a['id'] ?>','<?= htmlspecialchars($a['email'], ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
              <?php if ($a['id'] != $_SESSION['user_id']): ?>
                <button class="btn btn-sm btn-danger" onclick="openDeleteAdminModal('<?= $a['id'] ?>','<?= htmlspecialchars($a['email'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
              <?php else: ?>
                <button class="btn btn-sm btn-secondary" disabled title="Cannot delete current admin"><i class="fas fa-user-shield"></i></button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header"><h5 class="modal-title">Add Admin</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <label class="form-label">Email</label>
          <input name="admin_email" type="email" class="form-control" required>
          <label class="form-label mt-2">Password (leave blank to use generated Roll No)</label>
          <input name="admin_password" class="form-control">
        </div>
        <div class="modal-footer"><button type="submit" name="add_admin" class="btn btn-primary">Add</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header"><h5 class="modal-title">Edit Admin</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="admin_id" id="edit_admin_id">
          <label class="form-label">Email</label>
          <input type="email" name="admin_email_edit" id="edit_admin_email" class="form-control" required>
        </div>
        <div class="modal-footer"><button type="submit" name="edit_admin" class="btn btn-success">Save</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Admin Modal -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-danger text-white"><h5 class="modal-title">Confirm Delete</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body text-center">
          <input type="hidden" name="admin_id" id="delete_admin_id">
          <p>Delete admin <strong id="delete_admin_text"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="submit" name="delete_admin" class="btn btn-danger">Delete</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openEditAdminModal(id,email){
  document.getElementById('edit_admin_id').value = id;
  document.getElementById('edit_admin_email').value = email;
  new bootstrap.Modal(document.getElementById('editAdminModal')).show();
}
function openDeleteAdminModal(id,email){
  document.getElementById('delete_admin_id').value = id;
  document.getElementById('delete_admin_text').innerText = email;
  new bootstrap.Modal(document.getElementById('deleteAdminModal')).show();
}
</script>
</body>
</html>
