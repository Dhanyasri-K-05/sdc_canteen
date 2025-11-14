<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/Order.php';
require_once '../classes/User.php';
require_once '../classes/FoodItem.php';

requireRole('user', 'staff');

if ($_POST && isset($_POST['payment_method'])) {
    $database = new Database();
    $db = $database->getConnection();
    $order = new Order($db);
    $user = new User($db);
    $foodItem = new FoodItem($db);

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
        // ============================================
        // CRITICAL: Check AND Reserve stock with proper locking
        // ============================================
        $db->beginTransaction();

        // Verify stock availability and lock rows FOR UPDATE
        foreach ($cart as $item) {
            $stmt = $db->prepare("SELECT id, name, quantity_available 
                                  FROM food_items 
                                  WHERE id = :id 
                                  FOR UPDATE"); // This locks the row until commit/rollback
            $stmt->execute([':id' => $item['id']]);
            $currentStock = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$currentStock) {
                $db->rollback();
                header('Location: dashboard.php?error=item_not_found&item=' . urlencode($item['name']));
                exit();
            }

            // Check if sufficient stock is available
            if ($currentStock['quantity_available'] < $item['quantity']) {
                $db->rollback();
                error_log("Insufficient stock for user {$_SESSION['user_id']} - Item: {$item['name']}, Available: {$currentStock['quantity_available']}, Requested: {$item['quantity']}");
                header('Location: dashboard.php?error=insufficient_stock&item=' . urlencode($item['name']) . '&available=' . $currentStock['quantity_available'] . '&requested=' . $item['quantity']);
                exit();
            }
        }

        // ============================================
        // REDUCE STOCK IMMEDIATELY with validation
        // ============================================
        foreach ($cart as $item) {
            // Double-check before reducing (paranoid validation)
            $checkStmt = $db->prepare("SELECT quantity_available FROM food_items WHERE id = :id");
            $checkStmt->execute([':id' => $item['id']]);
            $currentQty = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($currentQty['quantity_available'] < $item['quantity']) {
                $db->rollback();
                error_log("RACE CONDITION DETECTED - Item ID: {$item['id']}, Available: {$currentQty['quantity_available']}, Requested: {$item['quantity']}");
                header('Location: dashboard.php?error=insufficient_stock&item=' . urlencode($item['name']) . '&available=' . $currentQty['quantity_available'] . '&requested=' . $item['quantity']);
                exit();
            }

            // Reduce stock with WHERE clause validation
            $updateStmt = $db->prepare("UPDATE food_items 
                                         SET quantity_available = quantity_available - :qty,
                                             updated_at = NOW()
                                         WHERE id = :id 
                                         AND quantity_available >= :qty");
            $updateStmt->execute([
                ':qty' => $item['quantity'],
                ':id' => $item['id']
            ]);

            // Verify the update actually happened
            if ($updateStmt->rowCount() === 0) {
                $db->rollback();
                error_log("Failed to reduce stock (possibly race condition) - Item ID: {$item['id']}, Qty: {$item['quantity']}");

                // Fetch current stock to show user
                $checkStmt = $db->prepare("SELECT name, quantity_available FROM food_items WHERE id = :id");
                $checkStmt->execute([':id' => $item['id']]);
                $itemData = $checkStmt->fetch(PDO::FETCH_ASSOC);

                header('Location: dashboard.php?error=insufficient_stock&item=' . urlencode($itemData['name']) . '&available=' . $itemData['quantity_available'] . '&requested=' . $item['quantity']);
                exit();
            }

            error_log("Stock reduced successfully - Item ID: {$item['id']}, Qty: {$item['quantity']}, User: {$_SESSION['user_id']}");
        }

        // ============================================
        // Stock successfully reserved - now process payment
        // ============================================

        if ($payment_method === 'wallet') {
            // Check wallet balance
            $wallet_balance = $user->getWalletBalance($_SESSION['user_id']);

            if ($wallet_balance < $total_amount) {
                // Restore stock before rollback
                foreach ($cart as $item) {
                    $restoreStmt = $db->prepare("UPDATE food_items 
                                                 SET quantity_available = quantity_available + :qty 
                                                 WHERE id = :id");
                    $restoreStmt->execute([':qty' => $item['quantity'], ':id' => $item['id']]);
                    error_log("Stock restored due to insufficient wallet - Item ID: {$item['id']}, Qty: {$item['quantity']}");
                }

                $db->rollback();
                header('Location: dashboard.php?error=insufficient_balance');
                exit();
            }

            // Create order
            $order_id = $order->createOrder($_SESSION['user_id'], $total_amount, 'wallet');

            // Add items to order
            foreach ($cart as $item) {
                $order->addOrderItem($order_id, $item['id'], $item['quantity'], $item['price']);
            }

            // Deduct from wallet
            $user->updateWalletBalance($_SESSION['user_id'], -$total_amount);

            // Record wallet transaction
            $query = "INSERT INTO wallet_transactions (user_id, transaction_type, amount, description, order_id) 
                     VALUES (?, 'debit', ?, 'Order payment', ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$_SESSION['user_id'], $total_amount, $order_id]);

            // Update order status
            $order->updatePaymentStatus($order_id, 'completed');

            // Update session wallet balance
            $_SESSION['wallet_balance'] = $user->getWalletBalance($_SESSION['user_id']);

            // Commit everything together
            $db->commit();

            // Clear cart
            unset($_SESSION['cart']);

            error_log("Wallet payment completed - Order ID: $order_id, User: {$_SESSION['user_id']}");
            header('Location: order_success.php?order_id=' . $order_id);
            exit();
        } elseif ($payment_method === 'razorpay') {
            // Commit stock reduction (payment will be verified later)
            $db->commit();

            error_log("Stock reserved for Razorpay payment - User: {$_SESSION['user_id']}, Amount: $total_amount");

            // Store order details in session for Razorpay processing
            $_SESSION['pending_order'] = [
                'cart' => $cart,
                'total_amount' => $total_amount
            ];

            header('Location: create_razorpay_order.php');
            exit();
        }
    } catch (Exception $e) {
        // Restore stock on any error
        if (isset($db) && $db->inTransaction()) {
            try {
                // Try to restore stock before rollback
                foreach ($cart as $item) {
                    $restoreStmt = $db->prepare("UPDATE food_items 
                                                 SET quantity_available = quantity_available + :qty 
                                                 WHERE id = :id");
                    $restoreStmt->execute([':qty' => $item['quantity'], ':id' => $item['id']]);
                    error_log("Stock restored due to exception - Item ID: {$item['id']}, Qty: {$item['quantity']}");
                }
            } catch (Exception $restoreError) {
                error_log("Failed to restore stock: " . $restoreError->getMessage());
            }

            $db->rollback();
        }

        error_log("Payment processing error for user {$_SESSION['user_id']}: " . $e->getMessage());
        header('Location: dashboard.php?error=payment_failed&msg=' . urlencode($e->getMessage()));
        exit();
    }
}
