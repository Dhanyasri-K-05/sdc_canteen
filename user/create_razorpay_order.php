<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/Order.php';
require_once '../config/env.php';

// Load Razorpay SDK
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

requireRole('user','staff');

if (!isset($_SESSION['pending_order'])) {
    header('Location: dashboard.php');
    exit();
}

$pending_order = $_SESSION['pending_order'];
$total_amount = $pending_order['total_amount'];

// Load Razorpay configuration from environment
$razorpay_key_id = $_ENV['RAZORPAY_KEY_ID'] ?? "rzp_test_RGySBmq7ZEVe32";
$razorpay_key_secret = $_ENV['RAZORPAY_KEY_SECRET'] ?? "ULoJlZy4G1Dbv35tBIdv3rRe";

// Initialize Razorpay API
$api = new Api($razorpay_key_id, $razorpay_key_secret);

// Create order in database first
$database = new Database();
$db = $database->getConnection();
$order = new Order($db);

try {

 /*    // Check stock availability
foreach ($pending_order['cart'] as $item) {
    $currentStock = $foodItem->getItemById($item['id']);
    if ($currentStock['quantity_available'] < $item['quantity']) {
        header('Location: dashboard.php?error=insufficient_stock');
        exit();
    }
} */

    $db->beginTransaction();
    
    // Create order in database
   // Convert cart to key:value format for JSON column
    $items_array = [];
    foreach ($pending_order['cart'] as $item) {
        $qty = $item['quantity'];
        $rate = $item['price'];
        $total = $qty * $rate;

        $items_array[$item['name']] = [
            'quantity' => $qty,
            'rate' => $rate,
            'total' => $total
        ];
    }


    $order_id = $order->createOrder($_SESSION['user_id'], $total_amount, 'razorpay', $items_array);

    
    // Add order items
    foreach ($pending_order['cart'] as $item) {
        $order->addOrderItem($order_id, $item['id'], $item['quantity'], $item['price']);
    }


    
    // Create real Razorpay order
    $razorpayOrderData = [
        'receipt' => 'order_' . $order_id,
        'amount' => $total_amount * 100, // Amount in paise
        'currency' => 'INR',
        'payment_capture' => 1 // Auto-capture payment
    ];
    
    $razorpayOrder = $api->order->create($razorpayOrderData);
    $razorpay_order_id = $razorpayOrder['id'];
    
    // Update order with real Razorpay order ID
    $order->updateRazorpayDetails($order_id, $razorpay_order_id);
    
    $db->commit();
    
    // Store order ID in session
    $_SESSION['current_order_id'] = $order_id;
    
    // Log successful order creation
    error_log("Razorpay order created successfully: Order ID: $order_id, Razorpay Order ID: $razorpay_order_id");

 /*    // Deduct stock for each item
foreach ($pending_order['cart'] as $item) {
    $foodItem->reduceStock($item['id'], $item['quantity']); 
    // Implement reduceStock() in FoodItem class:
    // UPDATE food_items SET quantity_available = quantity_available - ? WHERE id = ?
} */

    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    
    // Log error
    error_log("Razorpay order creation failed: " . $e->getMessage());
    
    header('Location: dashboard.php?error=payment_failed');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Food Ordering System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h4>Complete Payment</h4>
                    </div>
                    <div class="card-body text-center">
                        <h5>Order Total: ₹<?php echo number_format($total_amount, 2); ?></h5>
                        <p>Click the button below to proceed with payment</p>
                        <button id="rzp-button1" class="btn btn-success btn-lg">Pay Now</button>
                        <br><br>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var options = {
            "key": "<?php echo $razorpay_key_id; ?>",
            "amount": <?php echo $total_amount * 100; ?>, // Amount in paise
            "currency": "INR",
            "name": "Food Ordering System",
            "description": "Order Payment",
            "order_id": "<?php echo $razorpay_order_id; ?>",
            "handler": function (response){
                // Payment successful
                window.location.href = 'verify_razorpay_payment.php?payment_id=' + response.razorpay_payment_id + '&order_id=' + response.razorpay_order_id + '&signature=' + response.razorpay_signature;
            },
            "prefill": {
                "name": "<?php echo $_SESSION['roll_no']; ?>",
                "email": "<?php echo $_SESSION['email']; ?>"
            },
            "theme": {
                "color": "#3399cc"
            },
            "modal": {
                "ondismiss": function(){
                    // Payment cancelled
                    alert('Payment cancelled');
                    window.location.href = 'dashboard.php';
                }
            }
        };
        
        var rzp1 = new Razorpay(options);
        
        document.getElementById('rzp-button1').onclick = function(e){
            rzp1.open();
            e.preventDefault();
        }
        
        // Auto-trigger payment on page load
        window.onload = function() {
            rzp1.open();
        }
    </script>
</body>
</html>
