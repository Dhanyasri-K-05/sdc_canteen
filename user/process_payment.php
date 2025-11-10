<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/Order.php';
require_once '../classes/User.php';
require_once '../classes/FoodItem.php';


requireRole('user','staff');

if ($_POST && isset($_POST['payment_method'])) {
    $database = new Database();
    $db = $database->getConnection();
    $order = new Order($db);
    $user = new User($db);
    
    $cart = $_SESSION['cart'] ?? [];
    $payment_method = $_POST['payment_method'];
    
    if (empty($cart)) {
        header('Location: dashboard.php?error=empty_cart');
        exit();
    }
    
    // Calculate total
    $total_amount = 0;
    foreach ($cart as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }
    
    try {
        if ($payment_method === 'wallet') {
            // Check wallet balance
            $wallet_balance = $user->getWalletBalance($_SESSION['user_id']);
            
            if ($wallet_balance < $total_amount) {
                header('Location: dashboard.php?error=insufficient_balance');
                exit();
            }
            
            // Process wallet payment
            $db->beginTransaction();

        


            
            // Create order
          $order_id = $order->createOrder($_SESSION['user_id'], $total_amount, 'wallet', $cart);

            // Add order items
            foreach ($cart as $item) {
                $order->addOrderItem($order_id, $item['id'], $item['quantity'], $item['price']);
            }
            
            // Deduct from wallet
            $user->updateWalletBalance($_SESSION['user_id'], -$total_amount);

     

            
            // Record wallet transaction
            $query = "INSERT INTO wallet_transactions (user_id, transaction_type, amount, description, order_id) VALUES (?, 'debit', ?, 'Order payment', ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['user_id'], $total_amount, $order_id]);
            
            // Update order status
            $order->updatePaymentStatus($order_id, 'completed');

                   

            
            // Update session wallet balance
            $_SESSION['wallet_balance'] = $user->getWalletBalance($_SESSION['user_id']);
            
            $db->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            
            header('Location: order_success.php?order_id=' . $order_id);
            exit();
            
        } elseif ($payment_method === 'razorpay') {
            // Store order details in session for Razorpay processing
            $_SESSION['pending_order'] = [
                'cart' => $cart,
                'total_amount' => $total_amount
            ];
            
            header('Location: create_razorpay_order.php');
            exit();
        }
        
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollback();
        }
        header('Location: dashboard.php?error=payment_failed');
        exit();
    }
}

header('Location: dashboard.php');
exit();
?>
