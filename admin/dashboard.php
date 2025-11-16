<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/Order.php';
require_once '../classes/FoodItem.php';
require_once '../classes/User.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$order = new Order($db);
$foodItem = new FoodItem($db);
$user = new User($db);
$all_items = $foodItem->getAllItems();


// Get statistics
$stats = [
    'total_orders' => $order->getTotalOrders(),
    'total_revenue' => $order->getTotalRevenue(),
    'pending_approvals' => $foodItem->getPendingApprovalsCount(),
    'total_users' => $user->getTotalUsers()
];

// Get recent orders
$recent_orders = $order->getRecentOrders(10);

// Get pending approvals
$pending_approvals = $foodItem->getPendingApprovals();



// Handle direct update item
if ($_POST && isset($_POST['update_item'])) {
    $item_id = $_POST['item_id'];
    $new_name = !empty($_POST['name']) ? $_POST['name'] : null;
    $new_description = !empty($_POST['description']) ? $_POST['description'] : null;
    $new_price = !empty($_POST['price']) ? $_POST['price'] : null;
    $new_category = !empty($_POST['category']) ? $_POST['category'] : null;

    try {
        $updated = $foodItem->updateItem($item_id, $new_name, $new_description, $new_price, $new_category);

        if ($updated) {
            $success_message = "Food item updated successfully!";
            // Refresh the items for dropdown
            $all_items = $foodItem->getAllItems();
        } else {
            $error_message = "No changes were made.";
        }
    } catch (Exception $e) {
        $error_message = "Error updating item: " . $e->getMessage();
    }
}


















// Handle approval/rejection
if ($_POST && isset($_POST['action']) && isset($_POST['approval_id'])) {
    $approval_id = $_POST['approval_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'approve') {
            $foodItem->approveChange($approval_id, $_SESSION['user_id']);
            $success_message = "Change approved successfully!";
        } elseif ($action === 'reject') {
            $foodItem->rejectChange($approval_id, $_SESSION['user_id']);
            $success_message = "Change rejected successfully!";
        }
        
        // Refresh pending approvals
        $pending_approvals = $foodItem->getPendingApprovals();
        $stats['pending_approvals'] = $foodItem->getPendingApprovalsCount();
        
    } catch (Exception $e) {
        $error_message = "Error processing request: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Food Ordering System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Admin Dashboard - PSG iTech Canteen System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                   
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Manage Users</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="navbar-text me-3">
                        <i class="fas fa-user-shield"></i> Admin: <?php echo $_SESSION['roll_no']; ?>
                    </span>
                    <a class="btn btn-outline-light btn-sm" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>


    

    <div class="container mt-4">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $stats['total_orders']; ?></h4>
                                <p class="mb-0">Total Orders</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-shopping-cart fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>₹<?php echo number_format($stats['total_revenue'], 2); ?></h4>
                                <p class="mb-0">Total Revenue</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-rupee-sign fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $stats['pending_approvals']; ?></h4>
                                <p class="mb-0">Pending Approvals</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $stats['total_users']; ?></h4>
                                <p class="mb-0">Total Users</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
    <!-- Pending Approvals -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-clock"></i> Pending Approvals</h5>
            </div>
            <div class="card-body">
                <?php if (empty($pending_approvals)): ?>
                    <p class="text-muted">No pending approvals</p>
                <?php else: ?>
                    <?php foreach ($pending_approvals as $approval): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title">
                                            <?php echo ucfirst($approval['action_type']); ?> Request
                                        </h6>
                                        <p class="card-text">
                                            <strong>Item:</strong> <?php echo $approval['item_name']; ?><br>
                                            <strong>Requested by:</strong> <?php echo $approval['cashier_name']; ?><br>
                                            <strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($approval['created_at'])); ?>
                                        </p>
                                        <!-- Existing approval details here -->
                                    </div>
                                    <div>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="approval_id" value="<?php echo $approval['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm me-2">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Side: Update Item + Recent Orders -->
    <div class="col-md-4">
        <!-- Update Item -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-edit"></i> Update Food Item</h5>
                <small class="text-muted">Requires admin approval</small>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="item_id" class="form-label">Select Item</label>
                        <select class="form-select" id="item_id" name="item_id" required>
                            <option value="">Select Item to Update</option>
                            <?php foreach ($all_items as $item): ?>
                                <option value="<?php echo $item['id']; ?>">
                                    <?php echo $item['name']; ?> - ₹<?php echo $item['price']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="update_name" class="form-label">New Name (optional)</label>
                        <input type="text" class="form-control" id="update_name" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="update_description" class="form-label">New Description (optional)</label>
                        <textarea class="form-control" id="update_description" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="update_price" class="form-label">New Price (optional)</label>
                        <input type="number" class="form-control" id="update_price" name="price" step="1" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="update_category" class="form-label">New Category (optional)</label>
                        <select class="form-select" id="update_category" name="category">
                            <option value="">Keep Current</option>
                            <option value="breakfast">Breakfast</option>
                            <option value="lunch">Lunch</option>
                            <option value="snacks">Snacks</option>
                            <option value="beverages">Beverages</option>
                        </select>
                    </div>
                    <button type="submit" name="update_item" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Update Item
                    </button>
                </form>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Recent Orders</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_orders)): ?>
                    <p class="text-muted">No recent orders</p>
                <?php else: ?>
                    <?php foreach ($recent_orders as $recent_order): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo $recent_order['bill_number']; ?></strong>
                                <span class="badge bg-<?php echo $recent_order['payment_status'] == 'completed' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($recent_order['payment_status']); ?>
                                </span>
                            </div>
                            <small class="text-muted">
                                <?php echo $recent_order['roll_no']; ?> - 
                                ₹<?php echo number_format($recent_order['total_amount'], 2); ?><br>
                                <?php echo date('d/m/Y H:i', strtotime($recent_order['created_at'])); ?>
                            </small>
                        </div>
                        <hr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
