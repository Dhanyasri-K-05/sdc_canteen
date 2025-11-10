<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../config/env.php';

// Load Razorpay SDK
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

requireRole('user','staff');

if (!isset($_SESSION['wallet_topup'])) {
    header('Location: wallet.php');
    exit();
}

$wallet_topup = $_SESSION['wallet_topup'];
$amount = $wallet_topup['amount'];

// Load Razorpay configuration from environment
$razorpay_key_id = $_ENV['RAZORPAY_KEY_ID'] ?? "rzp_test_RGySBmq7ZEVe32";
$razorpay_key_secret = $_ENV['RAZORPAY_KEY_SECRET'] ?? "ULoJlZy4G1Dbv35tBIdv3rRe";

// Initialize Razorpay API
$api = new Api($razorpay_key_id, $razorpay_key_secret);

try {
    // Create Razorpay order for wallet top-up
    $razorpayOrderData = [
        'receipt' => 'wallet_topup_' . time(),
        'amount' => $amount * 100, // Amount in paise
        'currency' => 'INR',
        'payment_capture' => 1 // Auto-capture payment
    ];
    
    $razorpayOrder = $api->order->create($razorpayOrderData);
    $razorpay_order_id = $razorpayOrder['id'];
    
    // Store Razorpay order ID in session
    $_SESSION['wallet_razorpay_order_id'] = $razorpay_order_id;
    
    // Log successful order creation
    error_log("Wallet top-up Razorpay order created: Amount: $amount, Razorpay Order ID: $razorpay_order_id");
    
} catch (Exception $e) {
    // Log error
    error_log("Wallet top-up Razorpay order creation failed: " . $e->getMessage());
    
    header('Location: wallet.php?error=payment_failed');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Top-up Payment - Food Ordering System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center bg-primary text-white">
                        <h4><i class="fas fa-wallet"></i> Wallet Top-up Payment</h4>
                    </div>
                    <div class="card-body text-center">
                        <h5>Amount: ₹<?php echo number_format($amount, 2); ?></h5>
                        <p>Click the button below to proceed with payment</p>
                        <button id="rzp-button1" class="btn btn-primary btn-lg">
                            <i class="fas fa-credit-card"></i> Pay Now
                        </button>
                        <br><br>
                        <a href="wallet.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var options = {
            "key": "<?php echo $razorpay_key_id; ?>",
            "amount": <?php echo $amount * 100; ?>, // Amount in paise
            "currency": "INR",
            "name": "Food Ordering System",
            "description": "Wallet Top-up",
            "order_id": "<?php echo $razorpay_order_id; ?>",
            "handler": function (response){
                // Payment successful
                window.location.href = 'wallet_verify_payment.php?payment_id=' + response.razorpay_payment_id + '&order_id=' + response.razorpay_order_id + '&signature=' + response.razorpay_signature;
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
                    window.location.href = 'wallet.php';
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
