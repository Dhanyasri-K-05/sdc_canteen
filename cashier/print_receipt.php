<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../classes/Order.php');
require_once(__DIR__ . '/../classes/User.php');
require_once(__DIR__ . '/../config/session.php');

// Require cashier or admin role
requireRole('cashier', 'admin');

if (!isset($_GET['order_id'])) {
    die('Order ID not provided');
}

$order_id = $_GET['order_id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

$orderModel = new Order($db);
$userModel = new User($db);

// Fetch order details
$order = $orderModel->getOrderById($order_id);

if (!$order) {
    die('Order not found');
}

// Fetch cashier details
$cashier = $userModel->getUserById($order['user_id']);

// Fetch order items
$items = $orderModel->getOrderItems($order_id);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $order['bill_number']; ?></title>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }

        body {
            font-family: 'Courier New', monospace;
            max-width: 300px;
            margin: 20px auto;
            padding: 20px;
            background: white;
        }

        .receipt {
            border: 2px dashed #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0;
            font-size: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 12px;
        }

        .items {
            margin: 20px 0;
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
            padding: 10px 0;
        }

        .item {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 12px;
        }

        .item-name {
            flex: 1;
        }

        .item-qty {
            width: 30px;
            text-align: center;
        }

        .item-price {
            width: 60px;
            text-align: right;
        }

        .totals {
            margin-top: 15px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 13px;
        }

        .grand-total {
            font-size: 18px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
            border-top: 1px solid #333;
            padding-top: 10px;
        }

        .print-btn {
            margin: 20px auto;
            display: block;
            padding: 10px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .print-btn:hover {
            background: #5568d3;
        }
    </style>
</head>

<body>
    <button class="print-btn no-print" onclick="window.print()">🖨️ Print Receipt</button>

    <div class="receipt">
        <div class="header">
            <h1>CANTEEN</h1>
            <p>PSG ITECH</p>
            <p>Tax Invoice / Bill of Supply / Cash Memo</p>
            <p>(Original for Recipient)</p>
        </div>

        <div class="info-row">
            <span>Bill No:</span>
            <span><strong><?php echo $order['bill_number']; ?></strong></span>
        </div>
        <div class="info-row">
            <span>Date:</span>
            <span><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></span>
        </div>
        <div class="info-row">
            <span>Time:</span>
            <span><?php echo date('h:i:s A', strtotime($order['created_at'])); ?></span>
        </div>
        <div class="info-row">
            <span>Cashier:</span>
            <span><?php echo htmlspecialchars($cashier['email']); ?></span>
        </div>
        <div class="info-row">
            <span>Payment:</span>
            <span><?php echo strtoupper($order['payment_method']); ?></span>
        </div>

        <div class="items">
            <div class="item" style="font-weight: bold; border-bottom: 1px solid #333; padding-bottom: 5px;">
                <div class="item-name">Item</div>
                <div class="item-qty">Qty</div>
                <div class="item-price">Amount</div>
            </div>

            <?php foreach ($items as $item): ?>
                <div class="item">
                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="item-qty"><?php echo $item['quantity']; ?></div>
                    <div class="item-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                </div>
                <div class="item" style="font-size: 11px; color: #666; margin-top: -5px;">
                    <div class="item-name" style="padding-left: 10px;">@ ₹<?php echo number_format($item['price'], 2); ?> each</div>
                    <div class="item-qty"></div>
                    <div class="item-price"></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="totals">
            <div class="total-row">
                <span>Sub Total:</span>
                <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
            <div class="total-row">
                <span>Tax (CGST + SGST):</span>
                <span>₹0.00</span>
            </div>
            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>

        <div class="footer">
            <p>*** Thank You! Visit Again ***</p>
            <p>PSG ITECH CANTEEN</p>
            <p>For any queries: support@canteen.com</p>
            <p style="margin-top: 10px; font-size: 10px;">
                This is a computer generated receipt
            </p>
        </div>
    </div>

    <script>
        // Auto print on load (optional - uncomment to enable)
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>

</html>