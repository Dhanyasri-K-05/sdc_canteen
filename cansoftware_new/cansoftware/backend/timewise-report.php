<?php
session_start();
if ($_SESSION['role'] !== 'canteen') {
    header('Location: index.php');
    exit();
}

$con = pg_connect("host=localhost dbname=candb user=postgres password=postgres");
if (!$con) {
    die("Database connection failed.");
}

// Fetch POST data
$mode = $_POST["bill_mode"] ?? "Both";
$from_date = $_POST["from_date"] ?? "";
$to_date = $_POST["to_date"] ?? "";
$from_time = $_POST["from_time"] ?? "";
$to_time = $_POST["to_time"] ?? "";

// Format timestamps
$from_timestamp = date("Y-m-d H:i:s", strtotime("$from_date $from_time"));
$to_timestamp = date("Y-m-d H:i:s", strtotime("$to_date $to_time"));

// Mode condition
$mode_condition = "";
if ($mode == "UPI" || $mode == "CASH") {
    $mode_condition = "AND mode = '$mode'";
}

// Query grouped by item
$query = "
    SELECT item_name, SUM(quantity) AS total_quantity, SUM(amount) AS total_amount
    FROM sales
    WHERE sale_timestamp BETWEEN '$from_timestamp' AND '$to_timestamp'
    $mode_condition
    GROUP BY item_name
    ORDER BY item_name;
";

$result = pg_query($con, $query);
if (!$result) {
    die("Query failed: " . pg_last_error($con));
}

$total_quantity = 0;
$total_amount = 0;
?>

<!DOCTYPE html>
<html>
<head>
  <title>Timewise Sales Report</title>
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

  <h2>Timewise Sales Report</h2>
  <div class="mode-info">
    Mode: <strong><?= htmlspecialchars($mode) ?></strong>
  </div>
  <div class="date-range">
    From: <strong><?= htmlspecialchars($from_timestamp) ?></strong> &nbsp;
    To: <strong><?= htmlspecialchars($to_timestamp) ?></strong>
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
