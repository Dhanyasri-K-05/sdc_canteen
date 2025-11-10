<?php

session_start();
if ($_SESSION['role'] !== 'canteen') {
    header('Location: index.php');
    exit();
}

require_once __DIR__ . '/../../canteen_invoice/canteen_php_invoice/db.php'; // Adjust path as needed

$salesmanName = isset($_GET['salesman_name']) ? $_GET['salesman_name'] : null;
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : null;

if (!$salesmanName || !$fromDate || !$toDate) {
    header('Location: salesman_report_form.php');
    exit();
}

// Fetch report data
$query = "SELECT 
            :salesmanName AS salesman_name,
            SUM(quantity) AS total_quantity,
            SUM(total_amount) AS total_amount
          FROM invoices
          WHERE salesman_id = :salesmanName
            AND invoice_date BETWEEN :fromDate AND :toDate";
$stmt = $pdo->prepare($query);
$stmt->execute([
    'salesmanName' => $salesmanName,
    'fromDate' => $fromDate,
    'toDate' => $toDate
]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Salesman Report</title>
    <link rel="stylesheet" href="../frontend/styles.css" />
    <style>
        .container {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <a href="salesman_report_form.php" class="back-btn">← Back</a>
    <div class="container">
        <h2>Salesman Report</h2>
        <table>
            <tr>
                <th>Salesman Name</th>
                <th>Quantity</th>
                <th>Total Amount</th>
            </tr>
            <?php if ($report && $report['total_quantity'] > 0): ?>
            <tr>
                <td><?= htmlspecialchars($report['salesman_name']) ?></td>
                <td><?= htmlspecialchars($report['total_quantity']) ?></td>
                <td><?= htmlspecialchars($report['total_amount']) ?></td>
            </tr>
            <?php else: ?>
            <tr>
                <td colspan="3" style="color:red;">No data found for selected criteria.</td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>

</php>