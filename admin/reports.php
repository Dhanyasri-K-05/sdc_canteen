<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/Order.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$order = new Order($db);

// Handle report generation
$report_data = [];
$report_type = '';
$date_range = '';

if ($_POST && isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    $date_range = date('d/m/Y', strtotime($start_date)) . ' to ' . date('d/m/Y', strtotime($end_date));
    
    switch ($report_type) {
        case 'daily':
            $report_data = $order->getDailyReport($start_date, $end_date);
            break;
        case 'weekly':
            $report_data = $order->getWeeklyReport($start_date, $end_date);
            break;
        case 'monthly':
            $report_data = $order->getMonthlyReport($start_date, $end_date);
            break;
        case 'yearly':
            $report_data = $order->getYearlyReport($start_date, $end_date);
            break;
        case 'items':
            $report_data = $order->getItemSalesReport($start_date, $end_date);
            break;
    }
}

// Handle CSV download
if ($_GET && isset($_GET['download']) && isset($_GET['type'])) {
    $download_type = $_GET['type'];
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
    
    // Generate CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $download_type . '_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    switch ($download_type) {
        case 'daily':
            $data = $order->getDailyReport($start_date, $end_date);
            fputcsv($output, ['Date', 'Orders', 'Revenue', 'Items Sold']);
            foreach ($data as $row) {
                fputcsv($output, [$row['date'], $row['total_orders'], $row['total_revenue'], $row['total_items']]);
            }
            break;
        case 'items':
            $data = $order->getItemSalesReport($start_date, $end_date);
            fputcsv($output, ['Item Name', 'Category', 'Quantity Sold', 'Revenue']);
            foreach ($data as $row) {
                fputcsv($output, [$row['name'], $row['category'], $row['total_quantity'], $row['total_revenue']]);
            }
            break;
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Admin Dashboard</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="reports.php">Reports</a>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
        
        <!-- Report Generation Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Generate Report</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select class="form-select" id="report_type" name="report_type" required>
                                <option value="">Select Report Type</option>
                                <option value="daily" <?php echo ($report_type == 'daily') ? 'selected' : ''; ?>>Daily Sales</option>
                                <option value="weekly" <?php echo ($report_type == 'weekly') ? 'selected' : ''; ?>>Weekly Sales</option>
                                <option value="monthly" <?php echo ($report_type == 'monthly') ? 'selected' : ''; ?>>Monthly Sales</option>
                                <option value="yearly" <?php echo ($report_type == 'yearly') ? 'selected' : ''; ?>>Yearly Sales</option>
                                <option value="items" <?php echo ($report_type == 'items') ? 'selected' : ''; ?>>Item Sales</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo $_POST['start_date'] ?? date('Y-m-01'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo $_POST['end_date'] ?? date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" name="generate_report" class="btn btn-primary">
                                    <i class="fas fa-chart-line"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Results -->
        <?php if (!empty($report_data)): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><?php echo ucfirst($report_type); ?> Report - <?php echo $date_range; ?></h5>
                    <a href="?download=<?php echo $report_type; ?>&start_date=<?php echo $_POST['start_date']; ?>&end_date=<?php echo $_POST['end_date']; ?>" 
                       class="btn btn-success btn-sm">
                        <i class="fas fa-download"></i> Download CSV
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <?php if ($report_type == 'daily'): ?>
                                        <th>Date</th>
                                        <th>Total Orders</th>
                                        <th>Total Revenue</th>
                                        <th>Items Sold</th>
                                    <?php elseif ($report_type == 'weekly'): ?>
                                        <th>Week</th>
                                        <th>Total Orders</th>
                                        <th>Total Revenue</th>
                                        <th>Items Sold</th>
                                    <?php elseif ($report_type == 'monthly'): ?>
                                        <th>Month</th>
                                        <th>Total Orders</th>
                                        <th>Total Revenue</th>
                                        <th>Items Sold</th>
                                    <?php elseif ($report_type == 'yearly'): ?>
                                        <th>Year</th>
                                        <th>Total Orders</th>
                                        <th>Total Revenue</th>
                                        <th>Items Sold</th>
                                    <?php elseif ($report_type == 'items'): ?>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Quantity Sold</th>
                                        <th>Total Revenue</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $row): ?>
                                    <tr>
                                        <?php if ($report_type == 'items'): ?>
                                            <td><?php echo $row['name']; ?></td>
                                            <td><?php echo ucfirst($row['category']); ?></td>
                                            <td><?php echo $row['total_quantity']; ?></td>
                                            <td>₹<?php echo number_format($row['total_revenue'], 2); ?></td>
                                        <?php else: ?>
                                            <td><?php echo $row['date'] ?? $row['week'] ?? $row['month'] ?? $row['year']; ?></td>
                                            <td><?php echo $row['total_orders']; ?></td>
                                            <td>₹<?php echo number_format($row['total_revenue'], 2); ?></td>
                                            <td><?php echo $row['total_items']; ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <?php if ($report_type != 'items'): ?>
                                <tfoot>
                                    <tr class="table-dark">
                                        <th>Total</th>
                                        <th><?php echo array_sum(array_column($report_data, 'total_orders')); ?></th>
                                        <th>₹<?php echo number_format(array_sum(array_column($report_data, 'total_revenue')), 2); ?></th>
                                        <th><?php echo array_sum(array_column($report_data, 'total_items')); ?></th>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
