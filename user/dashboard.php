<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/FoodItem.php';
require_once '../classes/Order.php';

if (!isset($_SESSION['roll_no'])) {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'update_activity') {
    $_SESSION['LAST_ACTIVITY'] = time();
    exit();
}

$_SESSION['LAST_ACTIVITY'] = time();

requireRole('user', 'staff');

$database = new Database();
$db = $database->getConnection();
$foodItem = new FoodItem($db);
$order = new Order($db);
date_default_timezone_set('Asia/Kolkata');

$current_time = date('H:i');
$current_category = $foodItem->getCurrentCategory();
$available_items = $foodItem->getAvailableItemsByTime($current_time);

$items_by_category = [];
foreach ($available_items as $item) {
    $items_by_category[$item['category']][] = $item;
}

// =================== ADD TO CART ===================
// =================== ADD TO CART ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);

    $item = $foodItem->getItemById($item_id);

    if ($item && $quantity > 0) {
        // CRITICAL: Validate quantity against available stock
        if ($quantity > $item['quantity_available']) {
            header("Location: dashboard.php?error=stock_exceeded&item=" . urlencode($item['name']) . "&available=" . $item['quantity_available']);
            exit();
        }

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $found = false;
        foreach ($_SESSION['cart'] as &$cart_item) {
            if ($cart_item['id'] == $item_id) {
                // Check if adding more would exceed stock
                $new_quantity = $cart_item['quantity'] + $quantity;
                if ($new_quantity > $item['quantity_available']) {
                    header("Location: dashboard.php?error=stock_exceeded&item=" . urlencode($item['name']) . "&available=" . $item['quantity_available']);
                    exit();
                }
                $cart_item['quantity'] = $new_quantity;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $_SESSION['cart'][] = [
                'id'       => $item['id'],
                'name'     => $item['name'],
                'price'    => $item['price'],
                'quantity' => $quantity,
                'category' => $item['category']
            ];
        }
    }

    header("Location: dashboard.php?added=1");
    exit();
}
// =================== UPDATE CART ITEM ===================
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
                    if ($_SESSION['cart'][$key]['quantity'] <= 0) {
                        unset($_SESSION['cart'][$key]);
                    }
                }
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }

    header("Location: dashboard.php?updated=1");
    exit();
}

// =================== REMOVE FROM CART ===================
if (isset($_GET['remove_item'])) {
    $remove_id = intval($_GET['remove_item']);
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $remove_id) {
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']);
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
    <style>
        .stock-badge {
            transition: all 0.3s ease;
        }

        .stock-update-animation {
            animation: pulse 0.5s ease-in-out;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        .out-of-stock {
            opacity: 0.6;
            position: relative;
        }

        .out-of-stock::after {
            content: "OUT OF STOCK";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            z-index: 10;
        }

        .websocket-status {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 1000;
        }

        .ws-connected {
            background: #28a745;
            color: white;
        }

        .ws-disconnected {
            background: #dc3545;
            color: white;
        }
    </style>
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

    <!-- WebSocket Status Indicator -->
    <div id="wsStatus" class="websocket-status ws-disconnected">
        <i class="fas fa-circle"></i> Connecting...
    </div>

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
                    case 'insufficient_stock':
                        $itemName = $_GET['item'] ?? 'Unknown item';
                        $available = $_GET['available'] ?? 0;
                        $requested = $_GET['requested'] ?? 0;
                        echo "Insufficient stock for {$itemName}. Available: {$available}, Requested: {$requested}. Please refresh the page.";
                        break;
                    case 'item_not_found':
                        $itemName = $_GET['item'] ?? 'Unknown item';
                        echo "{$itemName} is no longer available. Please refresh the page.";

                        break;
                    case 'stock_exceeded':
                        $itemName = $_GET['item'] ?? 'Item';
                        $available = $_GET['available'] ?? 0;
                        echo htmlspecialchars($itemName) . " - Only {$available} item(s) available in stock. Please adjust quantity.";
                        break;
                    default:
                        echo "An error occurred. Please try again.";
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['info'])): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?php
                switch ($_GET['info']) {
                    case 'payment_cancelled':
                        echo "Payment was cancelled. Stock has been restored.";
                        break;
                    default:
                        echo "Information message.";
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="row">
            <!-- Menu Section -->
            <div class="col-md-8">
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
                                <?php foreach ($items as $item): ?>
                                    <?php $is_available = $item['quantity_available'] > 0; ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 item-card" data-item-id="<?php echo $item['id']; ?>"
                                            id="item-card-<?php echo $item['id']; ?>">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                                    <span class="badge bg-info stock-badge" id="stock-badge-<?php echo $item['id']; ?>">
                                                        Stock: <span id="stock-qty-<?php echo $item['id']; ?>"><?php echo $item['quantity_available']; ?></span>
                                                    </span>
                                                </div>
                                                <p class="card-text"><?php echo htmlspecialchars($item['description']); ?></p>
                                                <p class="card-text">
                                                    <strong>Price: ₹<?php echo number_format($item['price'], 2); ?></strong>
                                                </p>

                                                <form method="POST" class="d-flex align-items-center item-form-<?php echo $item['id']; ?>"
                                                    style="<?php echo !$is_available ? 'display: none !important;' : ''; ?>">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <input type="number" name="quantity" value="1" min="1"
                                                        max="<?php echo $item['quantity_available']; ?>"
                                                        class="form-control me-2" style="width: 80px;"
                                                        id="qty-input-<?php echo $item['id']; ?>"
                                                        oninput="if(this.value > this.max) this.value = this.max; if(this.value < this.min) this.value = this.min;">
                                                    <button type="submit" name="add_to_cart" class="btn btn-primary">
                                                        <i class="fas fa-cart-plus"></i> Add
                                                    </button>
                                                </form>

                                                <div class="alert alert-danger mt-2 out-of-stock-msg"
                                                    id="oos-msg-<?php echo $item['id']; ?>"
                                                    style="<?php echo $is_available ? 'display: none;' : ''; ?>">
                                                    Out of Stock
                                                </div>
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
                <div class="card">
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
                                        <strong><?php echo htmlspecialchars($cart_item['name']); ?></strong><br>
                                        <small>₹<?php echo number_format($cart_item['price'], 2); ?> x <?php echo $cart_item['quantity']; ?></small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <a href="?update_item=<?php echo $cart_item['id']; ?>&action=decrement"
                                            class="btn btn-sm btn-outline-secondary me-1">-</a>
                                        <span><?php echo $cart_item['quantity']; ?></span>
                                        <a href="?update_item=<?php echo $cart_item['id']; ?>&action=increment"
                                            class="btn btn-sm btn-outline-secondary ms-1">+</a>
                                        <span class="ms-3">
                                            ₹<?php echo number_format($cart_item['price'] * $cart_item['quantity'], 2); ?>
                                        </span>
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
        // WebSocket Connection for Real-time Stock Updates
        let ws;
        let reconnectInterval = 3000;
        let reconnectTimer;
        let isConnected = false;

        function connectWebSocket() {
            console.log('Attempting to connect to WebSocket...');

            try {
                ws = new WebSocket('ws://localhost:8080');

                ws.onopen = function() {
                    console.log('✅ WebSocket connected successfully');
                    isConnected = true;
                    updateWSStatus(true);
                    clearTimeout(reconnectTimer);
                };

                ws.onmessage = function(event) {
                    console.log('📨 Raw message received:', event.data);

                    try {
                        const message = JSON.parse(event.data);
                        console.log('📦 Parsed message:', message);

                        if (message.type === 'initial_stock') {
                            console.log('🔄 Initial stock data received with', message.data.length, 'items');
                            updateStockDisplay(message.data);
                        } else if (message.type === 'stock_update') {
                            console.log('⚡ Stock update received for', message.data.length, 'items');
                            updateStockDisplay(message.data);
                            showStockUpdateNotification(message.data);
                        } else {
                            console.log('⚠️ Unknown message type:', message.type);
                        }
                    } catch (e) {
                        console.error('❌ Error parsing WebSocket message:', e);
                        console.error('Raw data:', event.data);
                    }
                };

                ws.onerror = function(error) {
                    console.error('❌ WebSocket error:', error);
                    isConnected = false;
                    updateWSStatus(false);
                };

                ws.onclose = function(event) {
                    console.log('🔌 WebSocket disconnected. Code:', event.code, 'Reason:', event.reason);
                    isConnected = false;
                    updateWSStatus(false);

                    // Attempt to reconnect
                    console.log('⏱️ Reconnecting in', reconnectInterval / 1000, 'seconds...');
                    reconnectTimer = setTimeout(connectWebSocket, reconnectInterval);
                };
            } catch (error) {
                console.error('❌ Failed to create WebSocket:', error);
                updateWSStatus(false);
                reconnectTimer = setTimeout(connectWebSocket, reconnectInterval);
            }
        }

        function updateWSStatus(connected) {
            const statusDiv = document.getElementById('wsStatus');
            if (!statusDiv) {
                console.warn('⚠️ wsStatus element not found');
                return;
            }

            if (connected) {
                statusDiv.className = 'websocket-status ws-connected';
                statusDiv.innerHTML = '<i class="fas fa-circle"></i> Live Updates Active';
                console.log('✅ Status updated: Connected');
            } else {
                statusDiv.className = 'websocket-status ws-disconnected';
                statusDiv.innerHTML = '<i class="fas fa-circle"></i> Reconnecting...';
                console.log('⚠️ Status updated: Disconnected');
            }
        }

        function updateStockDisplay(items) {
            console.log('🔄 Updating display for', items.length, 'items');

            items.forEach(item => {
                const itemId = item.id;
                const newQty = parseInt(item.quantity_available);

                console.log(`📝 Processing item ${itemId}: ${item.name}, Qty: ${newQty}`);

                // Update stock badge
                const stockQtyElement = document.getElementById(`stock-qty-${itemId}`);
                const stockBadge = document.getElementById(`stock-badge-${itemId}`);
                const qtyInput = document.getElementById(`qty-input-${itemId}`);
                const itemCard = document.getElementById(`item-card-${itemId}`);
                const itemForm = document.querySelector(`.item-form-${itemId}`);
                const oosMsg = document.getElementById(`oos-msg-${itemId}`);

                if (!stockQtyElement) {
                    console.warn(`⚠️ Stock quantity element not found for item ${itemId}`);
                    return;
                }

                const oldQty = parseInt(stockQtyElement.textContent);

                if (oldQty !== newQty) {
                    console.log(`🔄 Stock changed for ${item.name}: ${oldQty} → ${newQty}`);

                    // Animate the update
                    if (stockBadge) {
                        stockBadge.classList.add('stock-update-animation');
                        setTimeout(() => {
                            stockBadge.classList.remove('stock-update-animation');
                        }, 500);
                    }

                    stockQtyElement.textContent = newQty;

                    // Update badge color based on stock level
                    if (stockBadge) {
                        stockBadge.classList.remove('bg-info', 'bg-warning', 'bg-danger');
                        if (newQty === 0) {
                            stockBadge.classList.add('bg-danger');
                        } else if (newQty < 5) {
                            stockBadge.classList.add('bg-warning');
                        } else {
                            stockBadge.classList.add('bg-info');
                        }
                    }

                    // Handle out of stock
                    if (qtyInput) {
                        qtyInput.max = newQty;
                        if (newQty === 0) {
                            console.log(`❌ Item ${item.name} is out of stock`);
                            qtyInput.value = 0;
                            if (itemForm) itemForm.style.display = 'none';
                            if (oosMsg) oosMsg.style.display = 'block';
                            if (itemCard) itemCard.classList.add('out-of-stock');
                        } else {
                            console.log(`✅ Item ${item.name} is in stock`);
                            if (itemForm) itemForm.style.display = 'flex';
                            if (oosMsg) oosMsg.style.display = 'none';
                            if (itemCard) itemCard.classList.remove('out-of-stock');
                            if (parseInt(qtyInput.value) > newQty) {
                                qtyInput.value = newQty;
                            }
                        }
                    }
                }
            });
        }

        function showStockUpdateNotification(items) {
            items.forEach(item => {
                if (item.quantity_available === 0) {
                    showToast(`${item.name} is now out of stock`, 'warning');
                } else if (item.quantity_available < 5) {
                    showToast(`${item.name} - Low stock: ${item.quantity_available} remaining`, 'info');
                }
            });
        }

        function showToast(message, type = 'info') {
            console.log('🔔 Showing toast:', message);

            const toast = document.createElement('div');
            toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 250px;';
            toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        // Payment selection
        function selectPayment(method) {
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('border-primary');
            });

            event.currentTarget.classList.add('border-primary');
            document.getElementById('selectedPaymentMethod').value = method;
            document.getElementById('confirmPaymentBtn').disabled = false;

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

        // Session management
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

        // Initialize WebSocket connection on page load
        window.addEventListener('load', function() {
            console.log('🚀 Page loaded, initializing WebSocket...');
            connectWebSocket();

            // Test if elements exist
            setTimeout(() => {
                console.log('🔍 Checking for stock elements...');
                const badges = document.querySelectorAll('[id^="stock-badge-"]');
                console.log(`Found ${badges.length} stock badges`);
            }, 1000);
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            console.log('👋 Page unloading, closing WebSocket...');
            if (ws && isConnected) {
                ws.close();
            }
        });

        // Manual reconnect button (for debugging)
        window.reconnectWS = function() {
            console.log('🔄 Manual reconnect requested');
            if (ws) {
                ws.close();
            }
            connectWebSocket();
        };
    </script>
    <!-- <script>
        // WebSocket Connection for Real-time Stock Updates
        let ws;
        let reconnectInterval = 3000;
        let reconnectTimer;

        function connectWebSocket() {
            ws = new WebSocket('ws://localhost:8080');

            ws.onopen = function() {
                console.log('WebSocket connected');
                updateWSStatus(true);
                clearTimeout(reconnectTimer);
            };

            ws.onmessage = function(event) {
                try {
                    const message = JSON.parse(event.data);
                    console.log('Received:', message);

                    if (message.type === 'initial_stock') {
                        // Initial stock data loaded
                        console.log('Initial stock data received');
                        updateStockDisplay(message.data);
                    } else if (message.type === 'stock_update') {
                        // Real-time stock update
                        updateStockDisplay(message.data);
                        showStockUpdateNotification(message.data);
                    }
                } catch (e) {
                    console.error('Error parsing WebSocket message:', e);
                }
            };

            ws.onerror = function(error) {
                console.error('WebSocket error:', error);
                updateWSStatus(false);
            };

            ws.onclose = function() {
                console.log('WebSocket disconnected');
                updateWSStatus(false);
                // Attempt to reconnect
                reconnectTimer = setTimeout(connectWebSocket, reconnectInterval);
            };
        }

        function updateWSStatus(connected) {
            const statusDiv = document.getElementById('wsStatus');
            if (connected) {
                statusDiv.className = 'websocket-status ws-connected';
                statusDiv.innerHTML = '<i class="fas fa-circle"></i> Live Updates Active';
            } else {
                statusDiv.className = 'websocket-status ws-disconnected';
                statusDiv.innerHTML = '<i class="fas fa-circle"></i> Reconnecting...';
            }
        }

        function updateStockDisplay(items) {
            items.forEach(item => {
                const itemId = item.id;
                const newQty = item.quantity_available;

                // Update stock badge
                const stockQtyElement = document.getElementById(`stock-qty-${itemId}`);
                const stockBadge = document.getElementById(`stock-badge-${itemId}`);
                const qtyInput = document.getElementById(`qty-input-${itemId}`);
                const itemCard = document.getElementById(`item-card-${itemId}`);
                const itemForm = document.querySelector(`.item-form-${itemId}`);
                const oosMsg = document.getElementById(`oos-msg-${itemId}`);

                if (stockQtyElement) {
                    const oldQty = parseInt(stockQtyElement.textContent);

                    if (oldQty !== newQty) {
                        // Animate the update
                        stockBadge.classList.add('stock-update-animation');
                        setTimeout(() => {
                            stockBadge.classList.remove('stock-update-animation');
                        }, 500);

                        stockQtyElement.textContent = newQty;

                        // Update badge color based on stock level
                        stockBadge.classList.remove('bg-info', 'bg-warning', 'bg-danger');
                        if (newQty === 0) {
                            stockBadge.classList.add('bg-danger');
                        } else if (newQty < 5) {
                            stockBadge.classList.add('bg-warning');
                        } else {
                            stockBadge.classList.add('bg-info');
                        }
                    }
                }

                // Handle out of stock
                if (qtyInput) {
                    qtyInput.max = newQty;
                    if (newQty === 0) {
                        qtyInput.value = 0;
                        if (itemForm) itemForm.style.display = 'none';
                        if (oosMsg) oosMsg.style.display = 'block';
                        if (itemCard) itemCard.classList.add('out-of-stock');
                    } else {
                        if (itemForm) itemForm.style.display = 'flex';
                        if (oosMsg) oosMsg.style.display = 'none';
                        if (itemCard) itemCard.classList.remove('out-of-stock');
                        if (parseInt(qtyInput.value) > newQty) {
                            qtyInput.value = newQty;
                        }
                    }
                }
            });
        }

        function showStockUpdateNotification(items) {
            items.forEach(item => {
                if (item.quantity_available === 0) {
                    // Show toast notification for out of stock
                    showToast(`${item.name} is now out of stock`, 'warning');
                }
            });
        }

        function showToast(message, type = 'info') {
            // Simple toast notification
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed`;
            toast.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 250px;';
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        // Payment selection
        function selectPayment(method) {
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('border-primary');
            });

            event.currentTarget.classList.add('border-primary');
            document.getElementById('selectedPaymentMethod').value = method;
            document.getElementById('confirmPaymentBtn').disabled = false;

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

        // Session management
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

        // Initialize WebSocket connection on page load
        window.addEventListener('load', function() {
            connectWebSocket();
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (ws) {
                ws.close();
            }
        });
    </script> -->

    <script>
        // Check for abandoned Razorpay orders every 30 seconds
        setInterval(function() {
            fetch('check_pending_orders.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'cancelled') {
                        // Reload page to show updated stock
                        window.location.reload();
                    }
                })
                .catch(error => console.error('Error checking pending orders:', error));
        }, 3000000); // 30 seconds
    </script>
</body>

</html>