<?php
session_start();
if ($_SESSION['role'] !== 'canteen') {
    header('Location: index.php');
    exit();
}

$conn = pg_connect("host=localhost dbname=candb user=postgres password=postgres");
if (!$conn) {
    die("Connection failed.");
}

$mode = pg_escape_string($conn, $_POST['mode'] ?? '');
$from_date = pg_escape_string($conn, $_POST['from_date'] ?? '');
$to_date = pg_escape_string($conn, $_POST['to_date'] ?? '');
$item_name = pg_escape_string($conn, $_POST['item_name'] ?? '');

// Build WHERE clause dynamically
$conditions = ["1=1"];
if (!empty($mode)) {
    $conditions[] = "mode = '$mode'";
}
if (!empty($from_date)) {
    $conditions[] = "sale_timestamp::date >= '$from_date'";
}
if (!empty($to_date)) {
    $conditions[] = "sale_timestamp::date <= '$to_date'";
}
if (!empty($item_name)) {
    $conditions[] = "item_name ILIKE '$item_name'";
}

$where_clause = implode(" AND ", $conditions);

// Query: Group by item_name
$query = "
  SELECT item_name, SUM(quantity) AS total_quantity, SUM(amount) AS total_amount
  FROM sales
  WHERE $where_clause
  GROUP BY item_name
  ORDER BY item_name;
";

$result = pg_query($conn, $query);
if (!$result) {
    die("Query failed: " . pg_last_error($conn));
}

$total_quantity = 0;
$total_amount = 0;
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Itemwise Report</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
      background: #f0f2f5;
    }
    .report-container {
      background: white;
      padding: 25px;
      border-radius: 8px;
      max-width: 900px;
      margin: auto;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #2c3e50;
    }
    .subtitle {
      text-align: center;
      margin-bottom: 15px;
      font-size: 15px;
      color: #555;
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

  <h2>Itemwise Report</h2>
  <div class="subtitle">
    Mode: <strong><?= $mode ?: 'Both' ?></strong> &nbsp;|&nbsp;
    Date Range: <?= $from_date ?: '-' ?> to <?= $to_date ?: '-' ?> &nbsp;|&nbsp;
    Item: <?= $item_name ?: 'All Items' ?>
  </div>

  <?php if (pg_num_rows($result) > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Item Name</th>
          <th>Quantity</th>
          <th>Amount (₹)</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = pg_fetch_assoc($result)): 
          $total_quantity += $row['total_quantity'];
          $total_amount += $row['total_amount'];
        ?>
          <tr>
            <td><?= htmlspecialchars($row['item_name']) ?></td>
            <td><?= $row['total_quantity'] ?></td>
            <td><?= number_format($row['total_amount'], 2) ?></td>
          </tr>
        <?php endwhile; ?>
        <tr class="totals">
          <td>Total</td>
          <td><?= $total_quantity ?></td>
          <td><?= number_format($total_amount, 2) ?></td>
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
