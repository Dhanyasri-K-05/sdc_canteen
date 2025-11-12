<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/User.php';
require_once '../config/env.php';

// Load Razorpay SDK
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

requireRole('user','staff');

if (!isset($_GET['payment_id']) || !isset($_SESSION['wallet_topup']) || !isset($_SESSION['wallet_razorpay_order_id'])) {
    header('Location: wallet.php?error=payment_failed');
    exit();
}

$payment_id = $_GET['payment_id'];
$razorpay_order_id = $_GET['order_id'];
$signature = $_GET['signature'];
$wallet_topup = $_SESSION['wallet_topup'];
$amount = $wallet_topup['amount'];

// Load Razorpay configuration from environment
$razorpay_key_id = $_ENV['RAZORPAY_KEY_ID'] ?? "rzp_test_RGySBmq7ZEVe32";
$razorpay_key_secret = $_ENV['RAZORPAY_KEY_SECRET'] ?? "ULoJlZy4G1Dbv35tBIdv3rRe";

// Initialize Razorpay API
$api = new Api($razorpay_key_id, $razorpay_key_secret);

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

try {
    // Verify payment signature
    $attributes = [
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_payment_id' => $payment_id,
        'razorpay_signature' => $signature
    ];
    
    $api->utility->verifyPaymentSignature($attributes);
    
    // Signature verification successful - add money to wallet
    $db->beginTransaction();
    
    // Add money to wallet
    $user->updateWalletBalance($_SESSION['user_id'], $amount);
    $_SESSION['wallet_balance'] = $user->getWalletBalance($_SESSION['user_id']);
    
    // Record transaction
    $query = "INSERT INTO wallet_transactions (user_id, transaction_type, amount, description) VALUES (?, 'credit', ?, 'Wallet top-up ')";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $amount]);
    
    $db->commit();
    
    // Clear session data
    unset($_SESSION['wallet_topup']);
    unset($_SESSION['wallet_razorpay_order_id']);
    
    // Log successful payment verification
    error_log("Wallet top-up payment verified successfully: Amount: $amount, Payment ID: $payment_id");
    
    header('Location: wallet.php?success=topup_completed&amount=' . $amount);
    exit();
    
} catch (SignatureVerificationError $e) {
    // Payment signature verification failed
    error_log("Wallet top-up payment signature verification failed: " . $e->getMessage());
    header('Location: wallet.php?error=payment_failed');
    exit();
    
} catch (Exception $e) {
    // Other errors
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    
    error_log("Wallet top-up payment verification error: " . $e->getMessage());
    header('Location: wallet.php?error=payment_failed');
    exit();
}
?>
