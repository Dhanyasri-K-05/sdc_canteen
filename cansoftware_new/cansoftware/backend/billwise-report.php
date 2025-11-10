<?php
session_start();
if ($_SESSION['role'] !== 'canteen') {
    header('Location: index.php');
    exit();
}
require 'db.php'; // Ensure this connects to PostgreSQL properly
// Get form data
$mode = $_POST['mode'] ?? '';
$fromDate = $_POST['from_date'] ?? '';
$toDate = $_POST['to_date'] ?? '';
$fromBill = $_POST['from_billno'] ?? '';
$toBill = $_POST['to_billno'] ?? '';

$sql = "SELECT bill_no, quantity, amount, mode FROM sales WHERE 1=1";
$params = [];
if (!empty($mode)) {
    $sql .= " AND mode = :mode";
    $params[':mode'] = $mode;
}

if (!empty($fromDate)) {
    $sql .= " AND sale_timestamp::date >= :fromDate";
    $params[':fromDate'] = date('Y-m-d', strtotime($fromDate));
}

if (!empty($toDate)) {
    $sql .= " AND sale_timestamp::date <= :toDate";
    $params[':toDate'] = date('Y-m-d', strtotime($toDate));
}

if (!empty($fromBill)) {
    $sql .= " AND bill_no >= :fromBill";
    $params[':fromBill'] = $fromBill;
}

if (!empty($toBill)) {
    $sql .= " AND bill_no <= :toBill";
    $params[':toBill'] = $toBill;
}

$sql .= " ORDER BY bill_no ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Billwise Sales Report</title>
  <style>
    body {
      font-family: Arial;
      padding: 20px;
      background: #f0f2f5;
    }
    .report-container {
      background: white;
      padding: 25px;
      border-radius: 8px;
      max-width: 800px;
      margin: auto;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #2c3e50;
    }
    .date-range {
      text-align: center;
      margin-top: -10px;
      margin-bottom: 10px;
      font-size: 15px;
      color: #555;
    }
    .mode-info {
      text-align: center;
      font-size: 16px;
      font-weight: 500;
      color: #333;
      margin-bottom: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      padding: 10px;
      border: 1px solid #ccc;
      text-align: center;
    }
    th {
      background-color: #007bff;
      color: white;
    }
    .totals {
      font-weight: bold;
      background-color: #f1f1f1;
    }
    .back-logout {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
    }
    .back-logout button {
      padding: 8px 14px;
      border: none;
      border-radius: 5px;
      color: white;
      cursor: pointer;
    }
    .back-logout .back {
      background-color: #6c757d;
    }
    .back-logout .logout {
      background-color: #dc3545;
    }
  </style>
</head>
<body>
<div class="report-container">

  <div class="back-logout">
    <button class="back" onclick="history.back()">← Back</button>
    <form method="POST" action="logout.php">
      <button class="logout" type="submit">Logout</button>
    </form>
  </div>
  <h2>Billwise Sales Report</h2>
  <div class="mode-info">
    Mode: <strong><?= htmlspecialchars($mode ?: 'BOTH') ?></strong>
  </div>
  <div class="date-range">
    From: <strong><?= htmlspecialchars($fromDate ?: '-') ?></strong> &nbsp;
    To: <strong><?= htmlspecialchars($toDate ?: '-') ?></strong>
  </div>
  <?php if ($results && count($results) > 0): 
    $totalAmount = 0;
    $totalQty = 0;
  ?>
    <table>
      <thead>
        <tr>
          <th>Bill No</th>
          <th>Quantity</th>
          <th>Amount (₹)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($results as $row): 
          $totalAmount += $row['amount'];
          $totalQty += $row['quantity'];
        ?>
          <tr>
            <td><?= htmlspecialchars($row['bill_no']) ?></td>
            <td><?= htmlspecialchars($row['quantity']) ?></td>
            <td><?= number_format($row['amount'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
        <tr class="totals">
          <td>Total</td>
          <td><?= $totalQty ?></td>
          <td><?= number_format($totalAmount, 2) ?></td>
        </tr>
      </tbody>
    </table>
      <div style="text-align: center; margin-top: 20px;">
    <button onclick="window.print()" 
            style="padding: 10px 20px; font-size: 16px; border: none; 
                   background-color: #28a745; color: white; border-radius: 5px; cursor: pointer;">
      🖨️ Print Report
    </button>
  </div>
  <?php else: ?>
    <p style="text-align:center; color: #dc3545;">No records found for the selected criteria.</p>
  <?php endif; ?>
</div>
</body>
</html>
