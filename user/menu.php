<?php
require_once '../config/session.php';
require_once '../config/database.php';

requireRole('user','staff');
// Database connection
$database = new Database();
$db = $database->getConnection();

// Fetch all active items
$query = "SELECT * FROM food_items WHERE is_active = 1 ORDER BY category, name";
$stmt = $db->prepare($query);
$stmt->execute();
$all_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group items by category
$items_by_category = [];
foreach ($all_items as $item) {
    $items_by_category[$item['category']][] = $item;
}

$current_page = basename($_SERVER['PHP_SELF']); // gets current file name
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Food Ordering System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>
<body>
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
                        <a class="nav-link active" href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="wallet.php">Wallet</a>
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
    <h2 class="mb-4">Menu</h2>

    <?php if (empty($all_items)): ?>
        <div class="alert alert-info">No items available.</div>
    <?php else: ?>
        <?php foreach ($items_by_category as $category => $items): ?>
            <div class="mb-4">
                <h4 class="text-capitalize"><?php echo $category; ?></h4>
                <div class="row">
                    <?php foreach ($items as $item): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?php echo $item['image']; ?>" class="card-img-top" alt="<?php echo $item['name']; ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $item['name']; ?></h5>
                                    <p class="card-text"><?php echo $item['description']; ?></p>
                                    <p><strong>Price:</strong> ₹<?php echo number_format($item['price'], 2); ?></p>
                                   
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


<script>
let lastUpdate = 0;
const evtSource = new EventSource('stock_update_sse.php?last_update=' + lastUpdate);

evtSource.onmessage = function(e) {
    const items = JSON.parse(e.data);

    items.forEach(item => {
        // Update quantity on page
        const qtyElement = document.querySelector(`#item-qty-${item.id}`);
        if (qtyElement) {
            qtyElement.textContent = item.quantity_available;
        }

        // Disable order button if stock is 0
        const btn = document.querySelector(`#order-btn-${item.id}`);
        if (btn) {
            btn.disabled = item.quantity_available <= 0;
        }
    });
};
</script>
















</body>
</html>
