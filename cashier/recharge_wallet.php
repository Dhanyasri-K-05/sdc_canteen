<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/FoodItem.php';
require_once '../classes/Order.php';
require_once '../classes/User.php'; // ✅ Correct path to your User class

// ✅ Create database connection
$database = new Database();
$db = $database->getConnection();

// ✅ Create class objects
$foodItem = new FoodItem($db);
$order = new Order($db);
$user = new User($db); // ✅ Now $user is available to call updateWalletBalance()

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $roll_no = trim($_POST['roll_no']);
    $amount = floatval($_POST['amount']);

    // ✅ Find user ID using roll number
    $query = "SELECT id FROM users WHERE roll_no = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$roll_no]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $user_id = $user_data['id'];

        // ✅ Update wallet balance
        if ($user->updateWalletBalance($user_id, $amount)) {
            $_SESSION['wallet_status'] = "✅ Wallet recharged successfully for Roll No: $roll_no (+₹$amount)";
        } else {
            $_SESSION['wallet_status'] = "❌ Failed to update wallet balance.";
        }
    } else {
        $_SESSION['wallet_status'] = "⚠️ Invalid Roll Number!";
    }

    header("Location: recharge_wallet.php");
    exit();
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Recharge Wallet</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f7fc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .form-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            width: 340px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        button {
            width: 100%;
            background: #2e8b57;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover {
            background: #1f6b40;
        }

        /* Popup Modal */
        #popupModal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            text-align: center;
        }
        #popupContent {
            background: white;
            display: inline-block;
            margin-top: 20%;
            padding: 20px;
            border-radius: 10px;
            min-width: 280px;
        }
        #popupMessage {
            font-size: 16px;
            margin-bottom: 15px;
        }
        #okBtn {
            background-color: green;
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            cursor: pointer;
        }
        #okBtn:hover {
            background-color: #0b7d0b;
        }
    </style>
</head>
<body>
     <nav class="navbar navbar-expand-lg navbar-dark bg-warning">
        <div class="container">
            <a class="navbar-brand text-dark" href="#">Cashier Dashboard - PSG iTech Canteen System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>


            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link  text-dark" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link  text-dark" href="stock_update.php">Stock update</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="stock_report.php">Stock Report</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active text-dark" href="recharge_wallet.php">Recharge Wallet</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="navbar-text me-3 text-dark">
                        <i class="fas fa-user"></i> Cashier: <?php echo $_SESSION['roll_no']; ?>
                    </span>
                    <a class="btn btn-outline-dark btn-sm" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>


<div class="form-box">
    <h3>💳 Wallet Recharge</h3>

    <form method="POST">
        <label>Student Roll Number:</label>
        <input type="text" name="roll_no" required>

        <label>Amount to Add (₹):</label>
        <input type="number" name="amount" step="0.01" required>

        <button type="submit">Recharge</button>
    </form>
</div>

<!-- ✅ Popup Modal -->
<div id="popupModal">
    <div id="popupContent">
        <p id="popupMessage"></p>
        <button id="okBtn" onclick="closePopup()">OK</button>
    </div>
</div>

<script>
function closePopup() {
    document.getElementById('popupModal').style.display = 'none';
}

<?php
if (isset($_SESSION['wallet_status'])) {
    $msg = addslashes($_SESSION['wallet_status']);
    unset($_SESSION['wallet_status']);
    echo "window.onload = function() {
        document.getElementById('popupMessage').innerText = '$msg';
        document.getElementById('popupModal').style.display = 'block';
    };";
}
?>
</script>

</body>
</html>
