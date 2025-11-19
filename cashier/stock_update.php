<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/FoodItem.php';
require_once '../classes/Order.php';
require_once __DIR__ . '/auto_stock_reset.php';


requireRole('cashier');

// Auto reset coffee and tea stock after 11 AM
resetCoffeeTeaStock();


$database = new Database();
$db = $database->getConnection();
$foodItem = new FoodItem($db);
$order = new Order($db);

$success_message = '';
$error_message = '';

$time_restriction_message = '';

// No lock system: allow stock updates for all items at any time.
// The auto-reset still runs in `auto_stock_reset.php` at/after 11:00 AM.
$current_time = date('H:i:s');
$stock_entry_allowed = true; // always allow updates



$search = "";
if (isset($_GET['search']) && $_GET['search'] !== "") {
    $search = trim($_GET['search']);
    $stmt = $db->prepare("
        SELECT * FROM food_items
        WHERE name LIKE :search 

        ORDER BY name ASC
    ");
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    $stmt->execute();
    $all_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->query("SELECT * FROM food_items ORDER BY name ASC");
    $all_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}




if ($_POST) {
    try {

         // Get item name to check if it's coffee/tea
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $check_item = $db->prepare("SELECT name FROM food_items WHERE id = ?");
        $check_item->execute([$item_id]);
        $item_data = $check_item->fetch(PDO::FETCH_ASSOC);
        $item_name = $item_data ? strtolower($item_data['name']) : '';
        
        // Check if it's coffee or tea
        $is_coffee_tea = (strpos($item_name, 'coffee') !== false || strpos($item_name, 'tea') !== false);
      {  
        // Increment or decrement stock
        /* if (isset($_POST['adjust_stock'])) {
            $item_id = intval($_POST['item_id']);
            $change = intval($_POST['change']); // +1 or -1
            $stmt = $db->prepare("UPDATE food_items SET quantity_available = quantity_available + :chg WHERE id = :id");
            $stmt->bindValue(':chg', $change, PDO::PARAM_INT);
            $stmt->bindValue(':id', $item_id, PDO::PARAM_INT);
            $stmt->execute();
            $success_message = "Stock updated successfully!";
        } */


            if (isset($_POST['increase_stock']) || isset($_POST['decrease_stock'])) {
    $item_id = intval($_POST['item_id']);
    $adjust_qty = intval($_POST['adjust_quantity']);

    if ($adjust_qty < 1) $adjust_qty = 1; // prevent invalid input

    if (isset($_POST['increase_stock'])) {
        $stmt = $db->prepare("UPDATE food_items 
                              SET quantity_available = quantity_available + :qty,
                                last_stock_update = NOW()
                              WHERE id = :id");
        $stmt->bindValue(':qty', $adjust_qty, PDO::PARAM_INT);
        $stmt->bindValue(':id', $item_id, PDO::PARAM_INT);
        $stmt->execute();
        $success_message = "Stock increased by {$adjust_qty}";
    }

    if (isset($_POST['decrease_stock'])) {
        $stmt = $db->prepare("UPDATE food_items 
                              SET quantity_available = GREATEST(quantity_available - :qty, 0) ,
                               last_stock_update = NOW()
                              WHERE id = :id");
        $stmt->bindValue(':qty', $adjust_qty, PDO::PARAM_INT);
        $stmt->bindValue(':id', $item_id, PDO::PARAM_INT);
        $stmt->execute();
        $success_message = "Stock decreased by {$adjust_qty}";
    }
}





        // Manual stock update
        if (isset($_POST['manual_update'])) {
            $item_id = intval($_POST['item_id']);
            $new_qty = intval($_POST['new_quantity']);
            $stmt = $db->prepare("UPDATE food_items SET quantity_available = :qty,  last_stock_update = NOW()  WHERE id = :id");
            $stmt->bindValue(':qty', $new_qty, PDO::PARAM_INT);
            $stmt->bindValue(':id', $item_id, PDO::PARAM_INT);
            $stmt->execute();
            $success_message = "Stock quantity set successfully!";
        }

          if ($success_message !== '') {
            $redirect = $_SERVER['PHP_SELF'] . "?success=" . urlencode($success_message);
            if (!headers_sent()) {
                header("Location: $redirect");
                exit;
            } else {
                echo "<script>window.location.href=" . json_encode($redirect) . ";</script>";
                exit;
            }
        }

      

    } }catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

//$all_items = $foodItem->getAllItems();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard - PSG iTech Canteen System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-warning">
        <div class="container">
            <a class="navbar-brand text-dark" href="#">Cashier Dashboard - PSG iTech Canteen System</a>
            <div class="d-flex align-items-center">
                  <li class="nav-item">
                        <a class="nav-link  text-dark" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link  active text-dark" href="stock_update.php">Stock update</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="stock_report.php">Stock Report</a>
                    </li>
                <span class="navbar-text me-3 text-dark">
                    <i class="fas fa-user"></i> Cashier: <?php echo $_SESSION['roll_no']; ?>
                </span>
                <a class="btn btn-outline-dark btn-sm" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
          <?php if ($time_restriction_message): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="fas fa-clock"></i> <?php echo $time_restriction_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

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

        <!-- Current Food Items with Stock Controls -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Current Food Items & Stock</h5>
            </div>
<!-- searching --> 

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        
        <form method="get" class="d-flex">
            <input type="text" name="search" 
                   class="form-control form-control-sm me-2"
                   placeholder="Search items..."
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
        </form>
    </div>
  



            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Available Time</th>
                                <th>Quantity Available</th>
                                <th>Adjust Stock</th>
                                <th>Manual Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_items as $item): 
                                // Check if this item is coffee or tea
                                $item_name_lower = strtolower($item['name']);
                                $is_coffee_tea = (strpos($item_name_lower, 'coffee') !== false || strpos($item_name_lower, 'tea') !== false);
                                $item_disabled = ($is_coffee_tea && !$stock_entry_allowed);
                            ?>
                                <tr<?php echo $item_disabled ? ' class="table-warning"' : ''; ?>>
                                    <td>
                                        <?php echo htmlspecialchars($item['name']); ?>
                                        <?php if ($item_disabled): ?>
                                            <!-- No lock system: this block should not display. -->
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo ucfirst($item['category']); ?></td>
                                    <td><?php echo $item['time_available']; ?></td>
                                    <td><strong><?php echo $item['quantity_available']; ?></strong></td>
                                    <td>
    <form method="POST" class="d-flex align-items-center"  onkeypress="return event.keyCode != 13;">
        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">

        <!-- Adjustment input (default 1) -->
        <input type="number" name="adjust_quantity" value="0" min="1" class="form-control form-control-sm w-50 me-2">

        <!-- Increase -->
        <button type="submit" name="increase_stock" class="btn btn-success btn-sm me-1">+</button>

        <!-- Decrease -->
        <button type="submit" name="decrease_stock" class="btn btn-danger btn-sm">-</button>
    </form>
</td>

                                    <td>
                                        <form method="POST" class="d-flex">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <input type="number" name="new_quantity" class="form-control form-control-sm me-2" min="0" required>
                                            <button type="submit" name="manual_update" class="btn btn-primary btn-sm">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($all_items)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No items available.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    

  <script>
        (function(){
            function pad(n){ return n < 10 ? '0' + n : n; }

            // Update visible client clock every second
            function updateClock(){
                var el = document.getElementById('clientTime');
                if (!el) return;
                var now = new Date();
                var h = now.getHours();
                var ampm = h >= 12 ? 'PM' : 'AM';
                h = h % 12 || 12;
                el.textContent = h + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds()) + ' ' + ampm;
            }
            setInterval(updateClock, 1000);
            updateClock();

            // Poll server every 60s to run/trigger auto-reset. If reset happened, reload page.
            function pollReset(){
                fetch('/auto_stock_reset.php?ajax=1', {cache: 'no-store'})
                    .then(function(resp){ if (!resp.ok) throw new Error('Network'); return resp.json(); })
                    .then(function(data){
                        if (data && data.reset && Array.isArray(data.updated) && data.updated.length > 0){
                            // Inform user and reload to reflect new quantities
                            try{ alert('Auto-reset performed for: ' + data.updated.map(function(i){return i.name;}).join(', ') + '. The page will reload to show updated stock.'); } catch(e){}
                            location.reload(true);
                        }
                    }).catch(function(){ /* silently ignore errors */ });
            }

            // Start polling after a small delay so page load is smooth
            setTimeout(function(){
                pollReset();
                setInterval(pollReset, 60 * 1000);
            }, 5000);
        })();
        </script>



</body>
</html>
