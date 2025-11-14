<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/Order.php';
require_once '../config/env.php';

// Load Razorpay SDK
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

requireRole('user', 'staff');

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
    $db->beginTransaction();

    // Create order in database
    $order_id = $order->createOrder($_SESSION['user_id'], $total_amount, 'razorpay');

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

    // Store order ID and timestamp in session
    $_SESSION['current_order_id'] = $order_id;
    $_SESSION['razorpay_order_created_at'] = time(); // Track when order was created

    // Log successful order creation
    error_log("Razorpay order created successfully: Order ID: $order_id, Razorpay Order ID: $razorpay_order_id");
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }

    // Restore stock on order creation failure
    try {
        $db->beginTransaction();
        foreach ($pending_order['cart'] as $item) {
            $restoreStmt = $db->prepare("UPDATE food_items 
                                         SET quantity_available = quantity_available + :qty 
                                         WHERE id = :id");
            $restoreStmt->execute([':qty' => $item['quantity'], ':id' => $item['id']]);
            error_log("Stock restored after order creation failure - Item ID: {$item['id']}, Qty: {$item['quantity']}");
        }
        $db->commit();
    } catch (Exception $restoreError) {
        error_log("Failed to restore stock: " . $restoreError->getMessage());
    }

    // Log error
    error_log("Razorpay order creation failed: " . $e->getMessage());

    // Clear session
    unset($_SESSION['pending_order']);
    unset($_SESSION['current_order_id']);

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
                        <a href="javascript:cancelPayment()" class="btn btn-secondary">Cancel & Go Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--    <script>
        var paymentInProgress = false;
        var paymentCompleted = false;

        function cancelPayment() {
            if (confirm('Are you sure you want to cancel this payment? Your reserved items will be released.')) {
                window.location.href = 'cancel_razorpay_payment.php';
            }
        }

        // Detect browser back button
        window.addEventListener('beforeunload', function(e) {
            if (paymentInProgress && !paymentCompleted) {
                // Payment was in progress but not completed
                navigator.sendBeacon('cancel_razorpay_payment.php');
            }
        });

        // Handle browser back/forward navigation
        window.addEventListener('popstate', function(e) {
            if (!paymentCompleted) {
                window.location.href = 'cancel_razorpay_payment.php';
            }
        });

        var options = {
            "key": "<?php echo $razorpay_key_id; ?>",
            "amount": <?php echo $total_amount * 100; ?>,
            "currency": "INR",
            "name": "Food Ordering System",
            "description": "Order Payment",
            "order_id": "<?php echo $razorpay_order_id; ?>",
            "handler": function(response) {
                // Payment successful
                paymentCompleted = true;
                paymentInProgress = false;
                window.location.href = 'verify_razorpay_payment.php?payment_id=' + response.razorpay_payment_id +
                    '&order_id=' + response.razorpay_order_id +
                    '&signature=' + response.razorpay_signature;
            },
            "prefill": {
                "name": "<?php echo $_SESSION['roll_no']; ?>",
                "email": "<?php echo $_SESSION['email'] ?? ''; ?>"
            },
            "theme": {
                "color": "#3399cc"
            },
            "modal": {
                "ondismiss": function() {
                    // Payment cancelled by user
                    paymentInProgress = false;
                    paymentCompleted = false;
                    alert('Payment cancelled. Redirecting...');
                    window.location.href = 'cancel_razorpay_payment.php';
                }
            }
        };

        var rzp1 = new Razorpay(options);

        rzp1.on('payment.failed', function(response) {
            // Payment failed
            paymentInProgress = false;
            paymentCompleted = false;
            alert('Payment failed: ' + response.error.description);
            window.location.href = 'cancel_razorpay_payment.php?reason=failed';
        });

        document.getElementById('rzp-button1').onclick = function(e) {
            paymentInProgress = true;
            rzp1.open();
            e.preventDefault();
        }

        // Auto-trigger payment on page load
        window.onload = function() {
            setTimeout(function() {
                paymentInProgress = true;
                rzp1.open();
            }, 500);
        }
    </script> -->

    <script>
        var paymentInProgress = false;
        var paymentCompleted = false;
        var razorpayModalOpen = false;
        var stockRestored = false;

        function cancelPayment() {
            if (stockRestored) {
                window.location.href = 'dashboard.php';
                return;
            }

            if (confirm('Are you sure you want to cancel this payment? Your reserved items will be released.')) {
                paymentInProgress = false;
                razorpayModalOpen = false;
                window.location.href = 'cancel_razorpay_payment.php?reason=user_cancelled';
            }
        }

        // Send cancellation via beacon (for immediate page close)
        function sendCancellationBeacon() {
            if (!paymentCompleted && !stockRestored) {
                stockRestored = true;
                const url = 'cancel_razorpay_payment.php?reason=page_close';

                // Try multiple methods
                if (navigator.sendBeacon) {
                    navigator.sendBeacon(url);
                    console.log('Beacon sent for stock restoration');
                } else {
                    // Fallback for older browsers
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', url, false); // Synchronous
                    xhr.send();
                }
            }
        }

        // Detect page unload (close tab, refresh, navigate away)
        window.addEventListener('beforeunload', function(e) {
            if (razorpayModalOpen && !paymentCompleted && !stockRestored) {
                console.log('⚠️ Page unloading - sending stock restoration request');
                sendCancellationBeacon();

                // Show warning
                e.preventDefault();
                e.returnValue = 'Payment is in progress. Leaving will cancel your order.';
                return e.returnValue;
            }
        });

        // Detect page hide (more reliable than beforeunload on mobile)
        window.addEventListener('pagehide', function(e) {
            if (razorpayModalOpen && !paymentCompleted && !stockRestored) {
                console.log('⚠️ Page hiding - sending stock restoration request');
                sendCancellationBeacon();
            }
        });

        // Handle browser back button
        history.pushState({
            page: 'razorpay_payment'
        }, '', '');
        window.addEventListener('popstate', function(e) {
            if (razorpayModalOpen && !paymentCompleted && !stockRestored) {
                console.log('⬅️ Back button pressed');
                history.pushState({
                    page: 'razorpay_payment'
                }, '', '');

                if (confirm('Payment is in progress. Do you want to cancel and restore your items?')) {
                    window.location.href = 'cancel_razorpay_payment.php?reason=back_button';
                } else {
                    history.pushState({
                        page: 'razorpay_payment'
                    }, '', '');
                }
            }
        });

        var options = {
            "key": "<?php echo $razorpay_key_id; ?>",
            "amount": <?php echo $total_amount * 100; ?>,
            "currency": "INR",
            "name": "Food Ordering System",
            "description": "Order Payment",
            "order_id": "<?php echo $razorpay_order_id; ?>",
            "handler": function(response) {
                // ✅ Payment successful
                console.log('✅ Payment successful');
                paymentCompleted = true;
                paymentInProgress = false;
                razorpayModalOpen = false;

                window.location.href = 'verify_razorpay_payment.php?payment_id=' + response.razorpay_payment_id +
                    '&order_id=' + response.razorpay_order_id +
                    '&signature=' + response.razorpay_signature;
            },
            "prefill": {
                "name": "<?php echo $_SESSION['roll_no']; ?>",
                "email": "<?php echo $_SESSION['email'] ?? ''; ?>"
            },
            "theme": {
                "color": "#3399cc"
            },
            "modal": {
                "backdropclose": false,
                "escape": false,
                "ondismiss": function() {
                    // ❌ Modal dismissed (X button clicked)
                    console.log('❌ Razorpay modal dismissed');
                    paymentInProgress = false;
                    razorpayModalOpen = false;

                    if (!paymentCompleted && !stockRestored) {
                        alert('Payment cancelled. Your reserved items will be released.');
                        window.location.href = 'cancel_razorpay_payment.php?reason=modal_dismissed';
                    }
                }
            }
        };

        var rzp1 = new Razorpay(options);

        rzp1.on('payment.failed', function(response) {
            // ❌ Payment failed
            console.log('❌ Payment failed:', response.error);
            paymentInProgress = false;
            paymentCompleted = false;
            razorpayModalOpen = false;

            alert('Payment failed: ' + response.error.description + '\nYour items will be released.');
            window.location.href = 'cancel_razorpay_payment.php?reason=payment_failed';
        });

        document.getElementById('rzp-button1').onclick = function(e) {
            paymentInProgress = true;
            razorpayModalOpen = true;
            rzp1.open();
            e.preventDefault();
        }

        // Auto-trigger payment
        window.onload = function() {
            setTimeout(function() {
                paymentInProgress = true;
                razorpayModalOpen = true;
                rzp1.open();
            }, 500);
        }
    </script>
</body>

</html>