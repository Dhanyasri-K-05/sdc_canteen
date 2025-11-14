<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/Order.php';

requireRole('user', 'staff');

// Function to restore stock
function restoreStock($db, $cart, $reason = "cancelled")
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

// Check if there's a pending order to cancel
if (!isset($_SESSION['pending_order'])) {
    // No pending order, just redirect
    header('Location: dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$order = new Order($db);

$cart = $_SESSION['pending_order']['cart'];
$order_id = $_SESSION['current_order_id'] ?? null;
$reason = $_GET['reason'] ?? 'user_cancelled';

try {
    // ============================================
    // RESTORE STOCK IMMEDIATELY
    // ============================================
    $restored = restoreStock($db, $cart, $reason);

    if (!$restored) {
        throw new Exception("Failed to restore stock");
    }

    // Mark order as cancelled if order was created
    if ($order_id) {
        try {
            $order->updatePaymentStatus($order_id, 'cancelled');
            error_log("Order marked as cancelled - Order ID: $order_id, Reason: $reason");
        } catch (Exception $e) {
            error_log("Failed to update order status: " . $e->getMessage());
        }
    }

    // Clear session data
    unset($_SESSION['current_order_id']);
    unset($_SESSION['pending_order']);
    unset($_SESSION['razorpay_order_created_at']);
    unset($_SESSION['cart']); // Also clear cart

    error_log("✅ Payment cancelled and stock restored - User: {$_SESSION['user_id']}, Reason: $reason");

    // Redirect based on how we got here
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'no-cors') {
        // Beacon request - just exit
        http_response_code(200);
        exit();
    } else {
        // Normal request - redirect
        header('Location: dashboard.php?info=payment_cancelled');
        exit();
    }
} catch (Exception $e) {
    error_log("❌ Error in cancel_razorpay_payment: " . $e->getMessage());

    // Still try to clear session even if stock restore failed
    unset($_SESSION['current_order_id']);
    unset($_SESSION['pending_order']);
    unset($_SESSION['razorpay_order_created_at']);

    header('Location: dashboard.php?error=cancellation_error');
    exit();
}
