<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/Order.php';
require_once '../classes/FoodItem.php';
require_once '../config/env.php';

// Load Razorpay SDK
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

requireRole('user', 'staff');

// Function to restore stock
function restoreStock($db, $cart, $reason = "unknown")
{
    try {
        $db->beginTransaction();

        foreach ($cart as $item) {
            $updateStmt = $db->prepare("UPDATE food_items 
                                         SET quantity_available = quantity_available + :qty,
                                             updated_at = NOW()
                                         WHERE id = :id");
            $updateStmt->execute([':qty' => $item['quantity'], ':id' => $item['id']]);
            error_log("Stock restored ({$reason}) - Item ID: {$item['id']}, Qty: {$item['quantity']}");
        }

        $db->commit();
        return true;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        error_log("Failed to restore stock: " . $e->getMessage());
        return false;
    }
}

// Check if required data exists
if (!isset($_GET['payment_id']) || !isset($_SESSION['current_order_id']) || !isset($_SESSION['pending_order'])) {
    error_log("Verify payment: Missing required session data");

    // Try to restore stock if we have cart data
    if (isset($_SESSION['pending_order'])) {
        $database = new Database();
        $db = $database->getConnection();
        restoreStock($db, $_SESSION['pending_order']['cart'], "missing_session_data");
        unset($_SESSION['pending_order']);
        unset($_SESSION['current_order_id']);
        unset($_SESSION['razorpay_order_created_at']);
    }

    header('Location: dashboard.php?error=payment_failed');
    exit();
}

$payment_id = $_GET['payment_id'];
$razorpay_order_id = $_GET['order_id'];
$signature = $_GET['signature'];
$order_id = $_SESSION['current_order_id'];
$cart = $_SESSION['pending_order']['cart'];

// Load Razorpay configuration
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

    // ✅ Signature verification successful
    // Stock was already reduced in process_payment.php, so just complete the order

    $db->beginTransaction();

    // Update order with payment details
    $order->updateRazorpayDetails($order_id, $razorpay_order_id, $payment_id);
    $order->updatePaymentStatus($order_id, 'completed');

    $db->commit();

    // Clear session data
    unset($_SESSION['cart']);
    unset($_SESSION['pending_order']);
    unset($_SESSION['current_order_id']);
    unset($_SESSION['razorpay_order_created_at']);

    error_log("✅ Payment verified successfully - Order ID: $order_id, Payment ID: $payment_id");

    header('Location: order_success.php?order_id=' . $order_id);
    exit();
} catch (SignatureVerificationError $e) {
    // ❌ Payment signature verification failed - RESTORE STOCK
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }

    error_log("❌ Signature verification failed: " . $e->getMessage());

    // Restore stock immediately
    restoreStock($db, $cart, "signature_verification_failed");

    // Mark order as failed
    try {
        $order->updatePaymentStatus($order_id, 'failed');
    } catch (Exception $orderUpdateError) {
        error_log("Failed to update order status: " . $orderUpdateError->getMessage());
    }

    // Clear session
    unset($_SESSION['current_order_id']);
    unset($_SESSION['pending_order']);
    unset($_SESSION['razorpay_order_created_at']);

    header('Location: dashboard.php?error=payment_verification_failed');
    exit();
} catch (Exception $e) {
    // ❌ Other errors - RESTORE STOCK
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }

    error_log("❌ Payment verification error: " . $e->getMessage());

    // Restore stock immediately
    restoreStock($db, $cart, "payment_error");

    // Mark order as failed
    try {
        $order->updatePaymentStatus($order_id, 'failed');
    } catch (Exception $orderUpdateError) {
        error_log("Failed to update order status: " . $orderUpdateError->getMessage());
    }

    // Clear session
    unset($_SESSION['current_order_id']);
    unset($_SESSION['pending_order']);
    unset($_SESSION['razorpay_order_created_at']);

    header('Location: dashboard.php?error=payment_failed&message=' . urlencode($e->getMessage()));
    exit();
}
