<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/User.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$success_message = '';
$error_message = '';

// Handle role update
if ($_POST && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    try {
        if ($user->updateRole($user_id, $new_role)) {
            $success_message = "User role updated successfully!";
        } else {
            $error_message = "Failed to update user role.";
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get all users
$all_users = $user->getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
              <a class="btn btn-outline-light btn-sm me-2" href="javascript:history.back()">
        <i class="fas fa-arrow-left"></i> Back
    </a>
            <a class="navbar-brand" href="dashboard.php">Admin Dashboard</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="reports.php">Reports</a>
                <a class="nav-link active" href="manage_users.php">Manage Users</a>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><i class="fas fa-users"></i> Manage Users</h2>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5>All Users</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Current Role</th>
                                <!-- <th>Wallet Balance</th> -->
                                <!-- <th>Registered</th> -->
                                <!-- <th>Actions</th> -->
                            </tr>
                        </thead>

    <tbody>
    <?php foreach ($all_users as $user_data): ?>
        <?php if ($user_data['role'] == 'cashier' || $user_data['role'] == 'admin'): ?>
            <tr>
                <td><?php echo $user_data['id']; ?></td>
                <td><?php echo $user_data['roll_no']; ?></td>
                <td><?php echo $user_data['email']; ?></td>
                <td>
                    <span class="badge bg-<?php 
                        echo $user_data['role'] == 'admin' ? 'danger' : 'warning'; 
                    ?>">
                        <?php echo ucfirst($user_data['role']); ?>
                    </span>
                </td>
                <!-- <td>₹<php echo number_format($user_data['wallet_balance'], 2); ?></td> -->
                <!-- <td><php echo date('d/m/Y', strtotime($user_data['created_at'])); ?></td> -->
                <!-- <td>
                    <php if ($user_data['id'] != $_SESSION['user_id']): ?>
                        <button class="btn btn-sm btn-outline-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#roleModal<php echo $user_data['id']; ?>">
                            <i class="fas fa-edit"></i> Change Role
                        </button>
                    <php else: ?>
                        <span class="text-muted">Current User</span>
                    <php endif; ?>
                </td> -->
            </tr>

            <!-- Role Change Modal -->
            <div class="modal fade" id="roleModal<?php echo $user_data['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Change Role for <?php echo $user_data['roll_no']; ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="user_id" value="<?php echo $user_data['id']; ?>">
                                <div class="mb-3">
                                    <label for="new_role<?php echo $user_data['id']; ?>" class="form-label">New Role</label>
                                    <select class="form-select" id="new_role<?php echo $user_data['id']; ?>" name="new_role" required>
                                        <option value="cashier" <?php echo $user_data['role'] == 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                                        <option value="admin" <?php echo $user_data['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                <div class="alert alert-warning">
                                    <small>
                                        <strong>Warning:</strong> Changing a user's role will affect their access permissions immediately.
                                    </small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="update_role" class="btn btn-primary">Update Role</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</tbody>

                    </table>
                </div>
            </div>
        </div>

        <!-- User Statistics -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total Users</h5>
                        <h3><?php echo count($all_users); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5>Cashiers</h5>
                        <h3><?php echo count(array_filter($all_users, function($u) { return $u['role'] == 'cashier'; })); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5>Admins</h5>
                        <h3><?php echo count(array_filter($all_users, function($u) { return $u['role'] == 'admin'; })); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
