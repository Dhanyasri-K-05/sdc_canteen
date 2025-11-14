<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../classes/Order.php');
require_once(__DIR__ . '/../classes/FoodItem.php');
require_once(__DIR__ . '/../classes/User.php');
require_once(__DIR__ . '/../config/session.php');

header('Content-Type: application/json');

// Check if user is cashier or admin
requireRole('cashier', 'admin');

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    $bill_number = $data['bill_number'];
    $items = $data['items'];
    $total_amount = $data['total_amount'];
    $payment_method = $data['payment_method'];
    $amount_received = $data['amount_received'];
    $cashier_id = $_SESSION['user_id'];

    // Create order
    $order = new Order($db);
    $order_id = $order->createOrder($cashier_id, $total_amount, $payment_method === 'gpay' ? 'razorpay' : 'wallet');

    // Update bill number
    $stmt = $db->prepare("UPDATE orders SET bill_number = ? WHERE id = ?");
    $stmt->execute([$bill_number, $order_id]);

    // Insert order items and update stock
    foreach ($items as $item) {
        // Insert order item
        $order->addOrderItem($order_id, $item['id'], $item['quantity'], $item['price']);

        // Update stock
        $stmt = $db->prepare("
            UPDATE food_items 
            SET quantity_available = quantity_available - ? 
            WHERE id = ? AND quantity_available >= ?
        ");
        $stmt->execute([$item['quantity'], $item['id'], $item['quantity']]);

        // Check if stock was actually updated
        if ($stmt->rowCount() === 0) {
            throw new Exception("Insufficient stock for item: " . $item['name']);
        }

        // Check if stock is low (less than 10) and create notification
        $check_stock = $db->prepare("SELECT quantity_available FROM food_items WHERE id = ?");
        $check_stock->execute([$item['id']]);
        $current_stock = $check_stock->fetchColumn();

        if ($current_stock < 10) {
            $notify = $db->prepare("
                INSERT INTO stock_notifications (item_id, notification_type) 
                VALUES (?, 'stock_update')
            ");
            $notify->execute([$item['id']]);
        }
    }

    // Update payment status to completed
    $order->updatePaymentStatus($order_id, 'completed');

    // Create wallet transaction for cash payments
    if ($payment_method === 'cash') {
        $stmt_transaction = $db->prepare("
            INSERT INTO wallet_transactions (user_id, transaction_type, amount, description, order_id) 
            VALUES (?, 'credit', ?, ?, ?)
        ");

        $stmt_transaction->execute([
            $cashier_id,
            $amount_received,
            'Cash payment for order ' . $bill_number,
            $order_id
        ]);
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order processed successfully',
        'order_id' => $order_id,
        'bill_number' => $bill_number
    ]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error processing order: ' . $e->getMessage()
    ]);
}
