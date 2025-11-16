<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/FoodItem.php';
require_once '../classes/Order.php';

requireRole('cashier');

$database = new Database();
$db = $database->getConnection();
$foodItem = new FoodItem($db);
$order = new Order($db);
 


$success_message = '';
$error_message = '';

// Handle food item operations
if ($_POST) {


// morning balance getting function

 if (isset($_POST['balance'])) {
    $balance = intval($_POST['balance']);

    // Prepare PDO statement
    $stmt = $db->prepare("INSERT INTO morning_balance (balance) VALUES (:balance)");

    // Bind value
    $stmt->bindValue(':balance', $balance, PDO::PARAM_INT);

    // Execute
    if ($stmt->execute()) {
       // echo "Morning balance recorded successfully!";
    } else {
        $errorInfo = $stmt->errorInfo();
        echo "Error: " . $errorInfo[2];
    }
} 









    try {
        if (isset($_POST['add_item'])) {
            $name = $_POST['name'];
            $description = $_POST['description'];
            $price = intval($_POST['price']);
           $quantity_available = intval($_POST['quantity']);
            $category = $_POST['category'];
            $time_available = $_POST['time_available'];
            
            $foodItem->requestAddItem($name, $description, $price,$quantity_available, $category, $time_available, $_SESSION['user_id']);
            $success_message = "Add item request submitted for admin approval!";
            
        } /* elseif (isset($_POST['update_item'])) {
            $item_id = $_POST['item_id'];
            $changes = [];
            
            if (!empty($_POST['name'])) $changes['name'] = $_POST['name'];
            if (!empty($_POST['description'])) $changes['description'] = $_POST['description'];
            if (!empty($_POST['price'])) $changes['price'] = floatval($_POST['price']);
            if (!empty($_POST['category'])) $changes['category'] = $_POST['category'];
            if (!empty($_POST['time_available'])) $changes['time_available'] = $_POST['time_available'];
            
            if (!empty($changes)) {
                $foodItem->requestUpdateItem($item_id, $changes, $_SESSION['user_id']);
                $success_message = "Update item request submitted for admin approval!";
            } else {
                $error_message = "No changes specified for update.";
            }
            
        } elseif (isset($_POST['delete_item'])) {
            $item_id = $_POST['item_id'];
            $foodItem->requestDeleteItem($item_id, $_SESSION['user_id']);
            $success_message = "Delete item request submitted for admin approval!";
        } */
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get all food items
$all_items = $foodItem->getAllItems();

// Get stock report data
$stock_data = $order->getStockReport();
// --- Get today's stats safely ---
$today_stats = $order->getTodayStats();

// Provide defaults if null
if (!is_array($today_stats)) {
    $today_stats = [
        'orders'  => 0,
        'revenue' => 0.0,
        'items'   => 0
    ];
}



// Fetch morning balance (latest entry)
//$stmt = $db->query("SELECT balance FROM morning_balance ORDER BY id DESC LIMIT 1");
$today = date("Y-m-d");

// Prepare the query
$stmt = $db->prepare("SELECT balance 
                      FROM morning_balance 
                      WHERE dates = :today 
                      ORDER BY id DESC 
                      LIMIT 1");

// Execute with today's date
$stmt->execute([':today' => $today]);

// Fetch balance
$morning_balance = $stmt->fetchColumn();

// If nothing found, set default = 0
if ($morning_balance === false) {
    $morning_balance = 0;
}


// Total revenue = today revenue + morning balance
$total_revenue = $today_stats['revenue'] + $morning_balance;



/*
if (isset($_POST['update'])) {
    $food_name = trim($_POST['food_name']);
    $foodItem->updateTodaysSpecial($food_name);
    echo "<script>alert('Today’s special updated successfully!');</script>";
}
    */

if (isset($_POST['update'])) {
    $food_name = trim($_POST['food_name']);

    if ($food_name === 'no_items') {
        // ✅ If user selected "None", mark as no_items
        $foodItem->updateTodaysSpecial('no_items');
        echo "<script>alert('Today’s special cleared successfully!');</script>";
    } else {
        $foodItem->updateTodaysSpecial($food_name);
        echo "<script>alert('Today’s special updated successfully!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard - Food Ordering System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-warning">
        <div class="container">
            <a class="navbar-brand text-dark" href="#">Cashier Dashboard - PSG iTech Canteen System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>


            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active text-dark" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link  text-dark" href="stock_update.php">Stock update</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="stock_report.php">Stock Report</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="recharge_wallet.php">Recharge Wallet</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="navbar-text me-3 text-dark">
                        <i class="fas fa-user"></i> Cashier: <?php echo $_SESSION['roll_no']; ?>
                    </span>
                    <a class="btn btn-outline-dark btn-sm" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">


 <!-- morning balance-->   
<form method="POST">
    <label>Morning Balance:</label>
    <input type="number" name="balance" required>
    <button type="submit">Submit</button>
</form>

<?php
$current_special = $foodItem->getTodaysSpecial();
$current_name = '';

if ($current_special && isset($current_special['name'])) {
    $current_name = $current_special['name'];
} else {
    $current_name = 'no_items'; // default to None
}
?>


<form method="POST">
    <label>Select Today's Special:</label>
    <select name="food_name" class="form-select" required>
        <option value="no_items" <?php echo ($current_name === 'no_items') ? 'selected' : ''; ?>>None</option>
        <?php
        $query = "SELECT name FROM food_items WHERE is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $selected = ($item['name'] === $current_name) ? 'selected' : '';
            echo "<option value='{$item['name']}' {$selected}>{$item['name']}</option>";
        }
        ?>
    </select>
    <button type="submit" name="update" class="btn btn-success mt-2">Update Special</button>
</form>










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

        <div class="row">
            <!-- Add New Item -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-plus"></i> Add New Food Item</h5>
                        <small class="text-muted">Requires admin approval</small>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Item Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">Price (₹)</label>
                                <input type="number" class="form-control" id="price" name="price" step="1" min="0" required>
                            </div>
                             <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" step="1" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="breakfast">Breakfast</option>
                                    <option value="lunch">Lunch</option>
                                    <option value="snacks">Snacks</option>
                                    <option value="beverages">Beverages</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="time_available" class="form-label">Available Time</label>
                                <select class="form-select" id="time_available" name="time_available" required>
                                    <option value="">Select Time</option>
                                    <option value="06:00-11:00">Breakfast (06:00-11:00)</option>
                                    <option value="12:00-16:00">Lunch (12:00-16:00)</option>
                                    <option value="16:00-19:00">Snacks (16:00-19:00)</option>
                                    <option value="06:00-22:00">All Day (06:00-22:00)</option>
                                </select>
                            </div>
                            <button type="submit" name="add_item" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Request Add Item
                            </button>
                        </form>
                    </div>
                </div>
            </div>

          


        <!-- Quick Stock Overview -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar"></i> Today's Sales Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    $today_stats = $order->getTodayStats();
                    ?>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-primary"><?php echo $today_stats['orders']; ?></h4>
                            <p class="mb-0">Orders Today</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-success">₹<?php echo number_format($today_stats['revenue'], 2); ?></h4>
                            <p class="mb-0">Revenue Today</p>
                        </div>
                    </div>
                    <div class="col-md-3">
    <div class="text-center">
       <!--  <h4 class="text-success">₹<php echo number_format($today_stats['revenue'], 2); ?></h4> -->
       <!--  <p class="mb-0">Revenue Today</p> -->
        
           <h4 class="text-success"> Amount to return <strong>₹<?php echo number_format($total_revenue, 2); ?></strong></h4>
        
    </div>
</div>

                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-info"><?php echo $today_stats['items']; ?></h4>
                            <p class="mb-0">Items Sold</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <a href="stock_report.php" class="btn btn-outline-primary">
                                <i class="fas fa-chart-line"></i> View Detailed Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
