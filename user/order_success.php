<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/Order.php';
require_once '../classes/BarcodeGenerator.php';

requireRole('user','staff');

if (!isset($_GET['order_id'])) {
    header('Location: dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$order = new Order($db);

$order_details = $order->getOrderById($_GET['order_id']);

if (!$order_details || $order_details['user_id'] != $_SESSION['user_id']) {
    header('Location: dashboard.php');
    exit();
}

// Generate QR code for the bill
/* $bill_data = json_encode([
    'bill_number' => $order_details['bill_number'],
    'total_amount' => $order_details['total_amount'],
    'date' => $order_details['created_at'],
    'customer' => $_SESSION['roll_no']
]); */

$bill_data = $order_details['bill_number'];


$barcode_filename = 'bill_' . $order_details['bill_number'];
$barcode_path = BarcodeGeneratorClass::generateBarcode($bill_data, $barcode_filename);
// echo "$barcode_path";

// Get order items
$order_items = $order->getOrderItems($_GET['order_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - Food Ordering System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }
        .print-only { display: none; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success no-print">
        <div class="container">
            <a class="navbar-brand" href="#">Food Ordering System</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Back to Dashboard</a>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white text-center">
                        <h4><i class="fas fa-check-circle"></i> Order Successful!</h4>
                        <p class="mb-0">Thank you for your order</p>
                    </div>
                    <div class="card-body" id="billContent">
                        <!-- Bill Header -->
                        <div class="text-center mb-4">
                            <h5>Food Ordering System</h5>
                            <p class="mb-1">Digital Receipt</p>
                            <hr>
                        </div>

                        <!-- Bill Details -->
                        <div class="row mb-3">
                            <div class="col-6">
                                <strong>Bill Number:</strong> <?php echo $order_details['bill_number']; ?>
                            </div>
                            <div class="col-6 text-end">
                                <strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($order_details['created_at'])); ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <strong>Customer:</strong> <?php echo $_SESSION['roll_no']; ?>
                            </div>
                            <div class="col-6 text-end">
                                <strong>Payment:</strong> <?php echo ucfirst($order_details['payment_method']); ?>
                            </div>
                        </div>

                        <hr>

                        <!-- Order Items -->
                        <h6>Order Items:</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?php echo $item['name']; ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                        <td>₹<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark">
                                    <th colspan="3">Total Amount</th>
                                    <th>₹<?php echo number_format($order_details['total_amount'], 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>

                        <hr>

                        <!-- QR Code -->
                        <div class="text-center">
                            <h6>Scan Barcode Code for Bill Details</h6>
                            <?php if ($barcode_path): ?>
                                <img src="<?php echo $barcode_path; ?>" alt="Barcode Code" class="img-fluid" style="max-width: 400px;">
                            <?php else: ?>
                                <p class="text-muted">Barcode Code generation failed</p>
                            <?php endif; ?>
                        </div>

                        <div class="text-center mt-3">
                            <small class="text-muted">Thank you for choosing our service!</small>
                        </div>
                    </div>
                    
                    <div class="card-footer text-center no-print">
                       <!--  <button class="btn btn-primary me-2" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Bill
                        </button>
                        <button class="btn btn-success" onclick="downloadBill()">
                            <i class="fas fa-download"></i> Download Bill
                        </button> -->
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-shopping-cart"></i> New Order
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
       /*  function downloadBill() {
            // Create a new window with just the bill content
            const billContent = document.getElementById('billContent').innerHTML;
            const newWindow = window.open('', '_blank');
            newWindow.document.write(`
                <html>
                <head>
                    <title>Bill - ${<?php echo json_encode($order_details['bill_number']); ?>}</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; }
                        @media print { 
                            body { margin: 0; padding: 10px; }
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="card">
                            <div class="card-body">
                                ${billContent}
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            `);
            newWindow.document.close(); */
            
           /*  // Auto print the new window
            setTimeout(() => {
                newWindow.print();
            }, 500);
        } */
    </script>
</body>
</html>
