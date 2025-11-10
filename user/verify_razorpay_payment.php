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

if (!isset($_GET['payment_id']) || !isset($_SESSION['current_order_id'])) {
    header('Location: dashboard.php?error=payment_failed');
    exit();
}

$payment_id = $_GET['payment_id'];
$razorpay_order_id = $_GET['order_id'];
$signature = $_GET['signature'];
$order_id = $_SESSION['current_order_id'];

// Load Razorpay configuration from environment
$razorpay_key_id = $_ENV['RAZORPAY_KEY_ID'] ?? "rzp_test_RGySBmq7ZEVe32";
$razorpay_key_secret = $_ENV['RAZORPAY_KEY_SECRET'] ?? "ULoJlZy4G1Dbv35tBIdv3rRe";

// Initialize Razorpay API
$api = new Api($razorpay_key_id, $razorpay_key_secret);

$database = new Database();
$db = $database->getConnection();
$order = new Order($db);

try {
    // Verify payment signature
    $attributes = [
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_payment_id' => $payment_id,
        'razorpay_signature' => $signature
    ];
    
    $api->utility->verifyPaymentSignature($attributes);
    
    // Signature verification successful - update order with payment details
    $order->updateRazorpayDetails($order_id, $razorpay_order_id, $payment_id);
    $order->updatePaymentStatus($order_id, 'completed');
    
    // Clear session data
    unset($_SESSION['cart']);
    unset($_SESSION['pending_order']);
    unset($_SESSION['current_order_id']);
    
    // Log successful payment verification
    error_log("Payment verified successfully: Order ID: $order_id, Payment ID: $payment_id");
    
    header('Location: order_success.php?order_id=' . $order_id);
    exit();
    
} catch (SignatureVerificationError $e) {
    // Payment signature verification failed
    error_log("Payment signature verification failed: " . $e->getMessage());
    $order->updatePaymentStatus($order_id, 'failed');
    header('Location: dashboard.php?error=payment_failed');
    exit();
    
} catch (Exception $e) {
    // Other errors
    error_log("Payment verification error: " . $e->getMessage());
    $order->updatePaymentStatus($order_id, 'failed');
    header('Location: dashboard.php?error=payment_failed');
    exit();
}
?>
