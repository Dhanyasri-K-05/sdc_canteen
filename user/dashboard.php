<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/FoodItem.php';
require_once '../classes/Order.php';
require_once '../classes/User.php';


 //$timeout_duration = 60;

if (!isset($_SESSION['roll_no'])) {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'update_activity') {
    $_SESSION['LAST_ACTIVITY'] = time();
    // echo "updated";
    exit();
}
// if (isset($_SESSION['LAST_ACTIVITY']) && 
//    (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
//     session_unset();     
//     session_destroy();   
//     header("Location: ../index.php");
//     exit();
// }
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $_SESSION['LAST_ACTIVITY'] = time();
// }
$_SESSION['LAST_ACTIVITY'] = time();
// echo "updated";

requireRole('user','staff');

$database = new Database();
$db = $database->getConnection();
$foodItem = new FoodItem($db);
$order = new Order($db);
$userObj = new User($db); 
date_default_timezone_set('Asia/Kolkata');
// Get current time and available food items
$current_time = date('H:i');
$current_category = $foodItem->getCurrentCategory();
$available_items = $foodItem->getAvailableItemsByTime($current_time);

// Group items by category
$items_by_category = [];
foreach ($available_items as $item) {
    $items_by_category[$item['category']][] = $item;
}

 $query = "SELECT wallet_balance FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && isset($user['wallet_balance'])) {
    $_SESSION['wallet_balance'] = floatval($userObj->decryptBalance($user['wallet_balance']));
} else {
    $_SESSION['wallet_balance'] = 0.00;
}
 


// Handle add to cart
// if ($_POST && isset($_POST['add_to_cart'])) {
//     $item_id = $_POST['item_id'];
//     $quantity = $_POST['quantity'];
    
//     // Get item details
//     $item = $foodItem->getItemById($item_id);
    
//     if ($item && $quantity > 0) {
//         // Initialize cart if not exists
//         if (!isset($_SESSION['cart'])) {
//             $_SESSION['cart'] = [];
//         }
        
//         // Check if item already in cart
//         $found = false;
//         foreach ($_SESSION['cart'] as &$cart_item) {
//             if ($cart_item['id'] == $item_id) {
//                 $cart_item['quantity'] += $quantity;
//                 $found = true;
//                 break;
//             }
//         }
        
//         // Add new item to cart
//         if (!$found) {
//             $_SESSION['cart'][] = [
//                 'id' => $item['id'],
//                 'name' => $item['name'],
//                 'price' => $item['price'],
//                 'quantity' => $quantity,
//                 'category' => $item['category']
//             ];
//         }
        
//         $success_message = "Added to cart: " . $item['name'] . " x " . $quantity;
//     }
// }

// // Handle remove from cart
// if ($_GET && isset($_GET['remove_item'])) {
//     $remove_id = $_GET['remove_item'];
    
//     if (isset($_SESSION['cart'])) {
//         foreach ($_SESSION['cart'] as $key => $item) {
//             if ($item['id'] == $remove_id) {
//                 unset($_SESSION['cart'][$key]);
//                 break;
//             }
//         }
        
//         // Re-index array
//         $_SESSION['cart'] = array_values($_SESSION['cart']);
//     }
// }

// // Handle clear cart
// if ($_GET && isset($_GET['clear_cart'])) {
//     unset($_SESSION['cart']);
// }

// // Calculate cart total
// $cart_total = 0;
// if (isset($_SESSION['cart'])) {
//     foreach ($_SESSION['cart'] as $item) {
//         $cart_total += $item['price'] * $item['quantity'];
//     }
// }


// =================== ADD TO CART ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);

    // Get item details from DB
    $item = $foodItem->getItemById($item_id);

    if ($item && $quantity > 0) {
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Check if item already exists in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$cart_item) {
            if ($cart_item['id'] == $item_id) {
                $cart_item['quantity'] += $quantity; // increase quantity
                $found = true;
                break;
            }
        }

        // If new item, add to cart
        if (!$found) {
            $_SESSION['cart'][] = [
                'id'       => $item['id'],       // must match DB column
                'name'     => $item['name'],
                'price'    => $item['price'],
                'quantity' => $quantity,
                'category' => $item['category']
            ];
        }
    }

    // Redirect to avoid duplicate form submission
    header("Location: dashboard.php?added=1");
    exit();
}

// =================== REMOVE ITEM ===================
// if (isset($_GET['remove_item'])) {
//     $remove_id = intval($_GET['remove_item']);
//     if (isset($_SESSION['cart'])) {
//         foreach ($_SESSION['cart'] as $key => $item) {
//             if ($item['id'] == $remove_id) {
//                 unset($_SESSION['cart'][$key]);
//                 break;
//             }
//         }
//         $_SESSION['cart'] = array_values($_SESSION['cart']); // re-index
//     }

//     header("Location: dashboard.php?removed=1");
//     exit();
// }

if (isset($_GET['update_item'])) {
    $update_id = intval($_GET['update_item']);
    $action = $_GET['action'] ?? '';

    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $update_id) {
                if ($action === 'increment') {
                    $_SESSION['cart'][$key]['quantity'] += 1;
                } elseif ($action === 'decrement') {
                    $_SESSION['cart'][$key]['quantity'] -= 1;
                    // remove if it hits 0
                    if ($_SESSION['cart'][$key]['quantity'] <= 0) {
                        unset($_SESSION['cart'][$key]);
                    }
                }
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']); // reindex
    }

    header("Location: dashboard.php?updated=1");
    exit();
}

// Handle full remove
if (isset($_GET['remove_item'])) {
    $remove_id = intval($_GET['remove_item']);
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $remove_id) {
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']); // reindex
    }

    header("Location: dashboard.php?removed=1");
    exit();
}


// =================== CLEAR CART ===================
if (isset($_GET['clear_cart'])) {
    unset($_SESSION['cart']);
    header("Location: dashboard.php?cleared=1");
    exit();
}

// =================== CART TOTAL ===================
$cart_total = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_total += $item['price'] * $item['quantity'];
    }
}

// Get user's recent orders
$recent_orders = $order->getUserOrders($_SESSION['user_id']);




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Food Ordering System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">PSG iTech Canteen System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                     <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="wallet.php">Wallet</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
        <li class="nav-item">
            <a class="nav-link" href="../canteen_order.php">Canteen Orders</a>
        </li>
    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="navbar-text me-3">
                        <i class="fas fa-user"></i> <?php echo $_SESSION['roll_no']; ?>
                    </span>
                    <!-- <span class="navbar-text me-3">
                        <i class="fas fa-wallet"></i> ₹<php echo number_format($_SESSION['wallet_balance'], 2); ?>
                    </span> -->
                    <a href="wallet.php" class="navbar-text me-3 text-decoration-none">
                    <i class="fas fa-wallet"></i> ₹<?php echo number_format($_SESSION['wallet_balance'], 2); ?>
                    </a>
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
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php 
                    switch ($_GET['error']) {
                        case 'empty_cart':
                            echo "Your cart is empty. Please add items before checkout.";
                            break;
                        case 'insufficient_balance':
                            echo "Insufficient wallet balance. Please add money to your wallet.";
                            break;
                        case 'payment_failed':
                            echo "Payment failed. Please try again.";
                            break;
                        default:
                            echo "An error occurred. Please try again.";
                    }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Menu Section -->
            <div class="col-md-8">
                <!-- <div class="current-time mb-4">
                    <h5><i class="fas fa-clock"></i> Current Time: <?php echo date('H:i'); ?> - Showing: <?php echo ucfirst($current_category); ?> Menu</h5>
                </div> -->

                <?php if (empty($available_items)): ?>
                    <div class="alert alert-info">
                        <h5>No items available at this time</h5>
                        <p>Please check back during our service hours:</p>
                        <ul>
                            <li>Breakfast: 06:00 - 11:00</li>
                            <li>Lunch: 12:00 - 16:00</li>
                            <li>Snacks: 16:00 - 19:00</li>
                            <li>Beverages: 06:00 - 22:00</li>
                        </ul>
                    </div>
                <?php else: ?>
                    <?php foreach ($items_by_category as $category => $items): ?>
                        <div class="category-section mb-4">
                            <h4 class="text-capitalize mb-3">
                                <i class="fas fa-utensils"></i> <?php echo $category; ?>
                            </h4>
                            <div class="row">
                             <!--    ?php foreach ($items as $item): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title">?php echo $item['name']; ?></h5>
                                                <p class="card-text">?php echo $item['description']; ?></p>
                                                <p class="card-text">
                                                    <strong>Price: ₹?php echo number_format($item['price'], 2); ?></strong>
                                                </p>
                                                <p class="card-text">
                                                    <small class="text-muted">Available: ?php echo $item['time_available']; ?></small>
                                                </p>
                                                <form method="POST" class="d-flex align-items-center">
                                                    <input type="hidden" name="item_id" value="?php echo $item['id']; ?>">
                                                    <input type="number" name="quantity" value="1" min="1" max="10" class="form-control me-2" style="width: 80px;">
                                                    <button type="submit" name="add_to_cart" class="btn btn-primary">
                                                        <i class="fas fa-cart-plus"></i> Add
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                ?php endforeach; ?> -->



<?php foreach ($items as $item): ?>
    <?php 
        $is_available = $item['quantity_available'] > 0;
    ?>
    <div class="col-md-6 mb-3">
        <div class="card h-100 <?php echo $is_available ? '' : 'bg-light text-muted'; ?>">
            <div class="card-body">
                <h5 class="card-title"><?php echo $item['name']; ?></h5>
                <p class="card-text"><?php echo $item['description']; ?></p>
                <p class="card-text">
                    <strong>Price: ₹<?php echo number_format($item['price'], 2); ?></strong>
                </p>
               

                <?php if ($is_available): ?>
                    <form method="POST" class="d-flex align-items-center">
                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $item['quantity_available']; ?>" class="form-control me-2" style="width: 80px;">
                        <button type="submit" name="add_to_cart" class="btn btn-primary">
                            <i class="fas fa-cart-plus"></i> Add
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
<?php endforeach; ?>








                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Cart Section -->
            <div class="col-md-4">
                <div class="card" >
                    <div class="card-header">
                        <h5><i class="fas fa-shopping-cart"></i> Your Cart</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($_SESSION['cart'])): ?>
                            <p class="text-muted">Your cart is empty</p>
                        <?php else: ?>
                            <?php foreach ($_SESSION['cart'] as $cart_item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?php echo $cart_item['name']; ?></strong><br>
                                        <small>₹<?php echo number_format($cart_item['price'], 2); ?> x <?php echo $cart_item['quantity']; ?></small>
                                    </div>
                                    <!-- <div>
                                        <span class="me-2">₹<?php echo number_format($cart_item['price'] * $cart_item['quantity'], 2); ?></span>
                                        <a href="?remove_item=<?php echo $cart_item['id']; ?>" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div> -->
                                    <div class="d-flex align-items-center">
                            <!-- Decrement -->
                            <a href="?update_item=<?php echo $cart_item['id']; ?>&action=decrement"
                               class="btn btn-sm btn-outline-secondary me-1">-</a>

                            <!-- Quantity -->
                            <span><?php echo $cart_item['quantity']; ?></span>

                            <!-- Increment -->
                            <a href="?update_item=<?php echo $cart_item['id']; ?>&action=increment"
                               class="btn btn-sm btn-outline-secondary ms-1">+</a>

                            <!-- Item total -->
                            <span class="ms-3">
                                ₹<?php echo number_format($cart_item['price'] * $cart_item['quantity'], 2); ?>
                            </span>

                            <!-- Remove fully -->
                            <a href="?remove_item=<?php echo $cart_item['id']; ?>"
                               class="btn btn-sm btn-outline-danger ms-2">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                                </div>
                                <hr>
                            <?php endforeach; ?>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <strong>Total: ₹<?php echo number_format($cart_total, 2); ?></strong>
                                <a href="?clear_cart=1" class="btn btn-sm btn-outline-secondary">Clear Cart</a>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                    <i class="fas fa-credit-card"></i> Proceed to Payment
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                
                 <?php
                // Filter only orders created within the last 24 hours
                $recent_orders = array_filter($recent_orders, function($order) {
                    $created_at = strtotime($order['created_at']);
                    $now = time();
                    return ($now - $created_at) <= 86400;
                });
                ?>

                <!-- Recent Orders -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6><i class="fas fa-history"></i> Recent Orders</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_orders)): ?>
                            <p class="text-muted">No orders yet</p>
                        <?php else: ?>
                            <?php foreach (array_slice($recent_orders, 0, 5) as $recent_order): ?>
                                <div class="mb-2">
                                    <small>
                                        <strong><?php echo $recent_order['bill_number']; ?></strong><br>
                                        ₹<?php echo number_format($recent_order['total_amount'], 2); ?> - 
                                        <span class="badge bg-<?php echo $recent_order['payment_status'] == 'completed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($recent_order['payment_status']); ?>
                                        </span><br>
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
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Choose Payment Method</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card payment-option" onclick="selectPayment('wallet')">
                                <div class="card-body text-center">
                                    <i class="fas fa-wallet fa-3x text-primary mb-3"></i>
                                    <h5>Wallet Payment</h5>
                                    <p>Current Balance: ₹<?php echo number_format($_SESSION['wallet_balance'], 2); ?></p>
                                    <?php if ($_SESSION['wallet_balance'] < $cart_total): ?>
                                        <small class="text-danger">Insufficient balance</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card payment-option" onclick="selectPayment('razorpay')">
                                <div class="card-body text-center">
                                    <i class="fas fa-credit-card fa-3x text-success mb-3"></i>
                                    <h5>Online Payment</h5>
                                    <p>Pay via Razorpay</p>
                                    <small class="text-muted">Cards, UPI, Net Banking</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="process_payment.php" id="paymentForm">
                        <input type="hidden" name="payment_method" id="selectedPaymentMethod">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="confirmPaymentBtn" disabled>
                            Confirm Payment - ₹<?php echo number_format($cart_total, 2); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPayment(method) {
            // Remove previous selection
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('border-primary');
            });
            
            // Add selection to clicked option
            event.currentTarget.classList.add('border-primary');
            
            // Set payment method
            document.getElementById('selectedPaymentMethod').value = method;
            document.getElementById('confirmPaymentBtn').disabled = false;
            
            // Check wallet balance for wallet payment
            if (method === 'wallet') {
                const walletBalance = <?php echo $_SESSION['wallet_balance']; ?>;
                const cartTotal = <?php echo $cart_total; ?>;
                
                if (walletBalance < cartTotal) {
                    alert('Insufficient wallet balance. Please add money to your wallet or choose online payment.');
                    document.getElementById('confirmPaymentBtn').disabled = true;
                    return;
                }
            }
        }



let lastUpdate = 0;
const evtSource = new EventSource('stock_update_sse.php?last_update=' + lastUpdate);

evtSource.onmessage = function(e) {
    const items = JSON.parse(e.data);

    items.forEach(item => {
        // Update quantity on page
        const qtyElement = document.querySelector(`#item-qty-${item.id}`);
        if (qtyElement) {
            qtyElement.textContent = item.quantity_available;
        }

        // Disable order button if stock is 0
        const btn = document.querySelector(`#order-btn-${item.id}`);
        if (btn) {
            btn.disabled = item.quantity_available <= 0;
        }
    });
};


 function checkSession() {
            fetch("timeout.php")  
                .then(response => response.text())
                .then(data => {
                    if (data === "expired") {
                        window.location.href = "../index.php";
                    }
                });
        }
        setInterval(checkSession, 60000);


        function resetActivity() {
    fetch("dashboard.php?action=update_activity"); 
}
document.addEventListener("click", resetActivity);
document.addEventListener("keydown", resetActivity);
document.addEventListener("touchstart", resetActivity);
document.addEventListener("mousemove", resetActivity);














    </script>
</body>





























    
</html>
