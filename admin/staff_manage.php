<?php
// staff_manage.php
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
    // Add Staff
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
        $roll_no = trim($_POST['staff_roll_no']);
        $email = trim($_POST['staff_email']);
        $role = $_POST['staff_role'] ?? 'staff';
        $password = trim($_POST['staff_password']);
        if ($password === '') $password = $roll_no;
        if (empty($roll_no) || empty($email)) throw new Exception("Roll No and Email required.");
        $userModel->createUser($roll_no, $email, $password, $role);
        $success = "Staff added.";
    }

    // Edit Staff
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_staff'])) {
        $id = intval($_POST['staff_id']);
        $email = trim($_POST['staff_email_edit']);
        $role = $_POST['staff_role_edit'] ?? 'staff';
        if (empty($email)) throw new Exception("Email required.");
        $userModel->updateUser($id, $email, $role);
        $success = "Staff updated.";
    }

    // Delete Staff
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
        $id = intval($_POST['staff_id']);
        if ($userModel->deleteUser($id)) $success = "Staff deleted.";
        else $error = "Failed to delete staff.";
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

$staffs = $userModel->getUsersByRole('staff');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Manage Staff</title>
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
      <a class="btn btn-sm btn-outline-light" href="admin_manage.php">Manage Admins</a>
      <a class="btn btn-sm btn-outline-light ms-2" href="../logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <h3>Manage Staff</h3>

  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-2">
    <div></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal"><i class="fas fa-plus"></i> Add Staff</button>
  </div>

  <?php if (count($staffs) === 0): ?>
    <p class="text-muted">No staff found.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped table-bordered align-middle text-center">
        <thead class="table-dark"><tr><th>ID</th><th>Roll No</th><th>Email</th><th>Role</th><th>Created At</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($staffs as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s['id']) ?></td>
            <td><?= htmlspecialchars($s['roll_no']) ?></td>
            <td><?= htmlspecialchars($s['email']) ?></td>
            <td><?= htmlspecialchars(ucfirst($s['role'])) ?></td>
            <td><?= htmlspecialchars(date('d-m-Y', strtotime($s['created_at']))) ?></td>
            <td>
              <button class="btn btn-sm btn-warning" onclick="openEditStaffModal('<?= $s['id'] ?>','<?= htmlspecialchars($s['email'], ENT_QUOTES) ?>','<?= $s['role'] ?>')"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm btn-danger" onclick="openDeleteStaffModal('<?= $s['id'] ?>','<?= htmlspecialchars($s['roll_no'], ENT_QUOTES) ?>')"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header"><h5 class="modal-title">Add Staff</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <label class="form-label">Roll No</label>
          <input name="staff_roll_no" class="form-control" required>
          <label class="form-label mt-2">Email</label>
          <input name="staff_email" type="email" class="form-control" required>
          <label class="form-label mt-2">Role</label>
          <select name="staff_role" class="form-select">
            <option value="staff">Staff</option>
            <option value="cashier">Cashier</option>
          </select>
          <label class="form-label mt-2">Password (leave blank to use Roll No)</label>
          <input name="staff_password" class="form-control">
        </div>
        <div class="modal-footer"><button type="submit" name="add_staff" class="btn btn-primary">Add</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header"><h5 class="modal-title">Edit Staff</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="staff_id" id="edit_staff_id">
          <label class="form-label">Email</label>
          <input type="email" name="staff_email_edit" id="edit_staff_email" class="form-control" required>
          <label class="form-label mt-2">Role</label>
          <select name="staff_role_edit" id="edit_staff_role" class="form-select">
            <option value="staff">Staff</option>
            <option value="cashier">Cashier</option>
          </select>
        </div>
        <div class="modal-footer"><button type="submit" name="edit_staff" class="btn btn-success">Save</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Staff Modal -->
<div class="modal fade" id="deleteStaffModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-danger text-white"><h5 class="modal-title">Confirm Delete</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body text-center">
          <input type="hidden" name="staff_id" id="delete_staff_id">
          <p>Delete staff <strong id="delete_staff_text"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="submit" name="delete_staff" class="btn btn-danger">Delete</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openEditStaffModal(id,email,role){
  document.getElementById('edit_staff_id').value = id;
  document.getElementById('edit_staff_email').value = email;
  document.getElementById('edit_staff_role').value = role;
  new bootstrap.Modal(document.getElementById('editStaffModal')).show();
}
function openDeleteStaffModal(id,roll){
  document.getElementById('delete_staff_id').value = id;
  document.getElementById('delete_staff_text').innerText = roll;
  new bootstrap.Modal(document.getElementById('deleteStaffModal')).show();
}
</script>
</body>
</html>
