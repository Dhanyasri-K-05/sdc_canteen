<?php
// user_manage.php
require_once __DIR__ . '/../config/session.php'; // must define session + requireRole()
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';

requireRole('admin'); // admin-only

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

$success = '';
$error = '';

try {
    // Add User
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
        $roll_no = trim($_POST['user_roll_no']);
        $email = trim($_POST['user_email']);
        $password = trim($_POST['user_password']);
        if ($password === '') $password = $roll_no;
        if (empty($roll_no) || empty($email)) throw new Exception("Roll No and Email required.");
        $userModel->createUser($roll_no, $email, $password, 'user');
        $success = "User created.";
    }

    // Edit User
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
        $id = intval($_POST['user_id']);
        $email = trim($_POST['user_email_edit']);
        // For users page, role remains 'user' (as per your choice)
        if (empty($email)) throw new Exception("Email required.");
        $userModel->updateUser($id, $email, 'user');
        $success = "User updated.";
    }

    // Delete User
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
        $id = intval($_POST['user_id']);
        if ($userModel->deleteUser($id)) $success = "User deleted.";
        else $error = "Failed to delete user.";
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Fetch users (role = user)
$users = $userModel->getUsersByRole('user');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Manage Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">Admin Dashboard</a>
    <div>
       <a class="btn btn-sm btn-outline-light me-1" href="dashboard.php">Dashboard</a>
      <a class="btn btn-sm btn-outline-light me-1" href="staff_manage.php">Manage Staff</a>
      <a class="btn btn-sm btn-outline-light" href="admin_manage.php">Manage Admins</a>
      <a class="btn btn-sm btn-outline-light ms-2" href="../logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <h3>Manage Users</h3>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-2">
    <div></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
      <i class="fas fa-plus"></i> Add User
    </button>
  </div>

  <?php if (count($users) === 0): ?>
    <p class="text-muted">No users found.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped table-bordered align-middle text-center">
        <thead class="table-dark">
          <tr><th>ID</th><th>Roll No</th><th>Email</th><th>Created At</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['id']) ?></td>
            <td><?= htmlspecialchars($u['roll_no']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars(date('d-m-Y', strtotime($u['created_at']))) ?></td>
            <td>
              <button class="btn btn-sm btn-warning" onclick="openEditUserModal('<?= $u['id'] ?>','<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>')">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="openDeleteUserModal('<?= $u['id'] ?>','<?= htmlspecialchars($u['roll_no'], ENT_QUOTES) ?>')">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Add User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Roll No</label>
          <input name="user_roll_no" class="form-control" required>
          <label class="form-label mt-2">Email</label>
          <input type="email" name="user_email" class="form-control" required>
          <label class="form-label mt-2">Password (leave blank to use Roll No)</label>
          <input type="text" name="user_password" class="form-control">
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_user" class="btn btn-primary">Add</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit User Modal (single) -->
<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header"><h5 class="modal-title">Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="user_id" id="edit_user_id">
          <label class="form-label">Email</label>
          <input type="email" name="user_email_edit" id="edit_user_email" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button type="submit" name="edit_user" class="btn btn-success">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete User Modal (single) -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-danger text-white"><h5 class="modal-title">Confirm Delete</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body text-center">
          <input type="hidden" name="user_id" id="delete_user_id">
          <p>Are you sure you want to delete user <strong id="delete_user_text"></strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openEditUserModal(id, email){
  document.getElementById('edit_user_id').value = id;
  document.getElementById('edit_user_email').value = email;
  new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
function openDeleteUserModal(id, roll){
  document.getElementById('delete_user_id').value = id;
  document.getElementById('delete_user_text').innerText = roll;
  new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
}
</script>
</body>
</html>
