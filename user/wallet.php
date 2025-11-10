<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/User.php';

if (!isset($_SESSION['roll_no'])) {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'update_activity') {
    $_SESSION['LAST_ACTIVITY'] = time();
    // echo "updated";
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']); // gets current file name

$_SESSION['LAST_ACTIVITY'] = time();

requireRole('user','staff');

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$success_message = '';
$error_message = '';

// Handle success message from Razorpay payment
if (isset($_GET['success']) && $_GET['success'] === 'topup_completed') {
    $amount = $_GET['amount'] ?? 0;
    $success_message = "₹" . number_format($amount, 2) . " added to your wallet successfully via Razorpay!";
}

// Handle error message from Razorpay payment
if (isset($_GET['error']) && $_GET['error'] === 'payment_failed') {
    $error_message = "Payment failed. Please try again.";
}

if ($_POST && isset($_POST['add_money'])) {
    $amount = floatval($_POST['amount']);
    //$payment_method = $_POST['payment_method'] ?? 'razorpay';
    
    if ($amount > 0) {
       /*  if ($payment_method === 'direct') {
            // Direct add money (existing functionality)
            try {
                $user->updateWalletBalance($_SESSION['user_id'], $amount);
                $_SESSION['wallet_balance'] = $user->getWalletBalance($_SESSION['user_id']);
                
                // Record transaction
                $query = "INSERT INTO wallet_transactions (user_id, transaction_type, amount, description) VALUES (?, 'credit', ?, 'Wallet top-up')";
                $stmt = $db->prepare($query);
                $stmt->execute([$_SESSION['user_id'], $amount]);
                
                $success_message = "₹" . number_format($amount, 2) . " added to your wallet successfully!";
            } catch (Exception $e) {
                $error_message = "Error adding money to wallet: " . $e->getMessage();
            }
        } */ 
            // Store wallet top-up details in session for Razorpay processing
            $_SESSION['wallet_topup'] = [
                'amount' => $amount,
                'user_id' => $_SESSION['user_id']
            ];
            
            header('Location: wallet_razorpay_payment.php');
            exit();
        
    } else {
        $error_message = "Please enter a valid amount";
    }
}

// Get transaction history
$query = "SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_balance = $user->getWalletBalance($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet - Food Ordering System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <!-- <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Food Ordering System</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Menu</a>
                <span class="navbar-text me-3">Welcome, <?php echo $_SESSION['roll_no']; ?></span>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav> -->

    <!-- <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">

        <a class="navbar-brand" href="dashboard.php">Food Ordering System</a>


        <ul class="navbar-nav me-auto">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">Menu</a>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto d-flex align-items-center">
            <li class="nav-item">
                <span class="navbar-text me-3">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['roll_no']; ?>
                </span>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../logout.php">Logout</a>
            </li>
        </ul>
    </div>
</nav> -->



    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">

 <a class="btn btn-outline-light btn-sm me-2" href="javascript:history.back()">
        <i class="fas fa-arrow-left"></i> Back
    </a>


            <a class="navbar-brand" href="#">PSG iTech Canteen System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link " href="dashboard.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="wallet.php">Wallet</a>
                    </li>
                       <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
        <li class="nav-item">
            <a class="nav-link" href="../canteen_order.php">Canteen Orders</a>
        </li>
    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="navbar-text me-3">
                        <i class="fas fa-user"></i> <?php echo $_SESSION['roll_no']; ?>
                    </span>
                    <!-- <span class="navbar-text me-3">
                        <i class="fas fa-wallet"></i> ₹<php echo number_format($_SESSION['wallet_balance'], 2); ?>
                    </span> -->
                    <a href="wallet.php" class="navbar-text me-3 text-decoration-none">
                    <i class="fas fa-wallet"></i> ₹<?php echo number_format($_SESSION['wallet_balance'], 2); ?>
                    </a>
                    <a class="btn btn-outline-light btn-sm" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-wallet"></i> Wallet Balance</h5>
                    </div>
                    <div class="card-body text-center">
                        <h2 class="text-success">₹<?php echo number_format($current_balance, 2); ?></h2>
                        <p class="text-muted">Current Balance</p>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-plus-circle"></i> Add Money to Wallet</h5>
                    </div>
                    <!-- <div class="card-body">
                        <php if ($success_message): ?>
                            <div class="alert alert-success"><php echo $success_message; ?></div>
                        <php endif; ?> -->

                        <div class="card-body">
    <?php if ($success_message): ?>
        <div class="alert alert-success" id="success-alert">
            <?php echo $success_message; ?>
        </div>
        <script>
            // Hide the alert after 30 seconds (30000 ms)
            setTimeout(function() {
                let alertBox = document.getElementById("success-alert");
                if (alertBox) {
                    // Optional: fade out smoothly
                    alertBox.style.transition = "opacity 1s ease";
                    alertBox.style.opacity = "0";
                    setTimeout(() => alertBox.remove(), 1000); // remove after fade
                }
            }, 30000);
        </script>
    <?php endif; ?>


                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <form method="POST" id="walletForm">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount (₹)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="amount" name="amount" min="1" step="1" required>
                                </div>
                            </div>
                            
                        <!--     <div class="row mb-3">
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-primary w-100" onclick="setAmount(100)">₹100</button>
                                </div>
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-primary w-100" onclick="setAmount(500)">₹500</button>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-primary w-100" onclick="setAmount(1000)">₹1000</button>
                                </div>
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-primary w-100" onclick="setAmount(2000)">₹2000</button>
                                </div>
                            </div> -->
                            
                            <!-- Payment Method Selection 
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                 <div class="row">
                                    <div class="col-6">
                                        <div class="card payment-method-option" onclick="selectPaymentMethod('direct')">
                                            <div class="card-body text-center p-2">
                                                <i class="fas fa-plus-circle fa-2x text-success mb-2"></i>
                                                <h6 class="mb-0">Direct Add</h6>
                                                <small class="text-muted">Instant</small>
                                            </div>
                                        </div>
                                    </div> -->
                                   <!--  <div class="col-6">
                                        <div class="card payment-method-option" onclick="selectPaymentMethod('razorpay')">
                                            <div class="card-body text-center p-2">
                                                <i class="fas fa-credit-card fa-2x text-primary mb-2"></i>
                                                <h6 class="mb-0">Online Payment</h6>
                                                <small class="text-muted">Razorpay</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> -->
                            
                            <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="direct">
                            <button type="submit" name="add_money" class="btn btn-success w-100" id="addMoneyBtn">
                                <i class="fas fa-plus"></i> Add Money
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history"></i> Transaction History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($transactions)): ?>
                            <p class="text-muted">No transactions yet</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($transactions, 0, 10)  as $transaction): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($transaction['transaction_type'] === 'credit'): ?>
                                                        <span class="badge bg-success">Credit</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Debit</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo ($transaction['transaction_type'] === 'credit' ? '+' : '-') . '₹' . number_format($transaction['amount'], 2); ?>
                                                </td>
                                                <td><?php echo $transaction['description']; ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setAmount(amount) {
            document.getElementById('amount').value = amount;
        }
        
        function selectPaymentMethod(method) {
            // Remove previous selection
            document.querySelectorAll('.payment-method-option').forEach(option => {
                option.classList.remove('border-primary');
            });
            
            // Add selection to clicked option
            event.currentTarget.classList.add('border-primary');
            
            // Set payment method
            document.getElementById('selectedPaymentMethod').value = method;
            
            // Update button text based on method
            const btn = document.getElementById('addMoneyBtn');
            if (method === 'razorpay') {
                btn.innerHTML = '<i class="fas fa-credit-card"></i> Pay with Razorpay';
                btn.className = 'btn btn-primary w-100';
            } else {
                btn.innerHTML = '<i class="fas fa-plus"></i> Add Money';
                btn.className = 'btn btn-success w-100';
            }
        }
        
        // Set default selection
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.payment-method-option').classList.add('border-primary');
        });
    </script>
    
    <style>
        .payment-method-option {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .payment-method-option.border-primary {
            border: 2px solid #0d6efd !important;
        }
    </style>
</body>
<script>
        function checkSession() {
            fetch("timeout.php")  
                .then(response => response.text())
                .then(data => {
                    if (data === "expired") {
                        window.location.href = "../index.php";
                    }
                });
        }
        setInterval(checkSession, 60000);


        function resetActivity() {
    fetch("dashboard.php?action=update_activity"); 
}
document.addEventListener("click", resetActivity);
document.addEventListener("keydown", resetActivity);
document.addEventListener("touchstart", resetActivity);
document.addEventListener("mousemove", resetActivity);

</script>
</html>
