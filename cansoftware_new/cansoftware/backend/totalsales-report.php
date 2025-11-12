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

// Format dates
$from_timestamp = !empty($from_date) ? date("Y-m-d", strtotime($from_date)) : "";
$to_timestamp = !empty($to_date) ? date("Y-m-d", strtotime($to_date)) : "";

// Mode condition
$mode_condition = "";
if ($mode == "UPI" || $mode == "CASH") {
    $mode_condition = "AND mode = '$mode'";
}

// Query to get totals
$query = "
    SELECT 
        COUNT(DISTINCT bill_no) AS total_bills,
        SUM(quantity) AS total_quantity,
        SUM(amount) AS total_amount
    FROM sales
    WHERE 1=1
";

if (!empty($from_timestamp)) {
    $query .= " AND sale_timestamp::date >= '$from_timestamp'";
}
if (!empty($to_timestamp)) {
    $query .= " AND sale_timestamp::date <= '$to_timestamp'";
}
if (!empty($mode_condition)) {
    $query .= " $mode_condition";
}

$result = pg_query($con, $query);
if (!$result) {
    die("Query failed: " . pg_last_error($con));
}

$data = pg_fetch_assoc($result);
$total_bills = $data['total_bills'] ?? 0;
$total_quantity = $data['total_quantity'] ?? 0;
$total_amount = $data['total_amount'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
  <title>Total Sales Report</title>
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

  <h2>Total Sales Report</h2>
  <div class="mode-info">
    Mode: <strong><?= htmlspecialchars($mode) ?></strong>
  </div>
  <div class="date-range">
    From: <strong><?= $from_timestamp ?: "-" ?></strong> &nbsp;
    To: <strong><?= $to_timestamp ?: "-" ?></strong>
  </div>

  <table>
    <thead>
      <tr>
        <th>No. of Bills</th>
        <th>Total Quantity</th>
        <th>Total Amount (₹)</th>
      </tr>
    </thead>
    <tbody>
      <tr class="totals">
        <td><?= $total_bills ?></td>
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

</div>
</body>
</html>
