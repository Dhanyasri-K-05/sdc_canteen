<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/Order.php';

requireRole('user', 'staff');

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

// Check if user has a pending Razorpay order that's older than 10 minutes
if (
    isset($_SESSION['razorpay_order_created_at']) &&
    isset($_SESSION['pending_order']) &&
    isset($_SESSION['current_order_id'])
) {

    $orderAge = time() - $_SESSION['razorpay_order_created_at'];

    // If order is older than 10 minutes (600 seconds), auto-cancel it
    if ($orderAge > 600) {
        try {
            $db->beginTransaction();

            $cart = $_SESSION['pending_order']['cart'];
            $order_id = $_SESSION['current_order_id'];

            // Restore stock
            foreach ($cart as $item) {
                $updateStmt = $db->prepare("UPDATE food_items 
                                             SET quantity_available = quantity_available + :qty 
                                             WHERE id = :id");
                $updateStmt->execute([':qty' => $item['quantity'], ':id' => $item['id']]);
                error_log("Auto-restored stock (timeout) - Item ID: {$item['id']}, Qty: {$item['quantity']}");
            }

            // Mark order as cancelled
            $order = new Order($db);
            $order->updatePaymentStatus($order_id, 'cancelled');

            $db->commit();

            // Clear session
            unset($_SESSION['current_order_id']);
            unset($_SESSION['pending_order']);
            unset($_SESSION['razorpay_order_created_at']);

            error_log("Auto-cancelled expired order - Order ID: $order_id");

            echo json_encode(['status' => 'cancelled', 'message' => 'Order expired and stock restored']);
            exit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            error_log("Failed to auto-cancel order: " . $e->getMessage());
        }
    }
}

echo json_encode(['status' => 'ok']);
