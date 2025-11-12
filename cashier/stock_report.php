<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/Order.php';

requireRole('cashier');

$database = new Database();
$db = $database->getConnection();
$order = new Order($db);

// Get date range from form or default to today
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get stock report data
$stock_report = $order->getStockReport($start_date, $end_date);
$daily_summary = $order->getDailySummary($start_date, $end_date);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Report - Cashier Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-warning">
        <div class="container">
            <a class="navbar-brand text-dark" href="dashboard.php">Cashier Dashboard</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-dark" href="dashboard.php">Dashboard</a>
                <a class="nav-link active text-dark" href="stock_report.php">Stock Report</a>
                <a class="nav-link text-dark" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><i class="fas fa-chart-line"></i> Stock & Sales Report</h2>
        
        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <?php foreach ($daily_summary as $summary): ?>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5><?php echo date('d/m/Y', strtotime($summary['date'])); ?></h5>
                            <p class="mb-1">Orders: <?php echo $summary['total_orders']; ?></p>
                            <p class="mb-1">Revenue: ₹<?php echo number_format($summary['total_revenue'], 2); ?></p>
                            <p class="mb-0">Items: <?php echo $summary['total_items']; ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Detailed Stock Report -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-table"></i> Item-wise Sales Report</h5>
                <span class="badge bg-primary">
                    <?php echo date('d/m/Y', strtotime($start_date)); ?> 
                    <?php if ($start_date != $end_date): ?>
                        to <?php echo date('d/m/Y', strtotime($end_date)); ?>
                    <?php endif; ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($stock_report)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No sales data found for the selected date range.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Price per Unit</th>
                                    <th>Quantity Sold</th>
                                    <th>Total Revenue</th>
                                    <th>Last Sold</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_quantity = 0;
                                $total_revenue = 0;
                                foreach ($stock_report as $item): 
                                    $total_quantity += $item['total_quantity'];
                                    $total_revenue += $item['total_revenue'];
                                ?>
                                    <tr>
                                        <td><?php echo $item['name']; ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo ucfirst($item['category']); ?>
                                            </span>
                                        </td>
                                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo $item['total_quantity']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong>₹<?php echo number_format($item['total_revenue'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($item['last_sold'])); ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark">
                                    <th colspan="3">Total</th>
                                    <th><?php echo $total_quantity; ?></th>
                                    <th>₹<?php echo number_format($total_revenue, 2); ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Category-wise Breakdown -->
        <?php if (!empty($stock_report)): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie"></i> Category-wise Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php 
                        $category_stats = [];
                        foreach ($stock_report as $item) {
                            $category = $item['category'];
                            if (!isset($category_stats[$category])) {
                                $category_stats[$category] = ['quantity' => 0, 'revenue' => 0];
                            }
                            $category_stats[$category]['quantity'] += $item['total_quantity'];
                            $category_stats[$category]['revenue'] += $item['total_revenue'];
                        }
                        
                        foreach ($category_stats as $category => $stats):
                        ?>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="text-capitalize"><?php echo $category; ?></h5>
                                        <p class="mb-1">Quantity: <strong><?php echo $stats['quantity']; ?></strong></p>
                                        <p class="mb-0">Revenue: <strong>₹<?php echo number_format($stats['revenue'], 2); ?></strong></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
