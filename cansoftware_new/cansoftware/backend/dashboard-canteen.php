<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'canteen') {
  header('Location: index.php');
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Canteen Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f0f2f5;
      padding: 20px;
    }
    .dashboard {
      max-width: 600px;
      margin: auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .dashboard h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #2c3e50;
    }
    .button-group, .sub-options {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-top: 20px;
    }
    .sub-options {
      margin-left: 10px;
    }
    button {
      padding: 10px 15px;
      font-size: 16px;
      border: none;
      border-radius: 6px;
      background-color: #007bff;
      color: white;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    button:hover {
      background-color: #0056b3;
    }
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    header button {
      font-size: 18px;
      background: none;
      border: none;
      cursor: pointer;
      color: #007bff;
    }
    header form button {
      background-color: #dc3545;
      color: white;
      border: none;
      border-radius: 5px;
      padding: 8px 15px;
      cursor: pointer;
    }
  </style>
</head>
<body>

<header>
  <button onclick="goBack()">← Back</button>
  <form method="POST" action="logout.php">
    <button type="submit">Logout</button>
  </form>
</header>

<div class="dashboard">
  <h2>Canteen Dashboard</h2>

  <div class="button-group" id="mainButtons">
    <button onclick="alert('Requests clicked!')">Requests</button>
    <button onclick="showReports()">Reports</button>
  </div>

  <div class="button-group" id="reportOptions" style="display:none;">
    <button onclick="showSalesReports()">Sales Report</button>
    <button onclick="showStockReports()">Stock Report</button>
    <button onclick="alert('Order Report clicked')">Order Report</button>
    <button onclick="alert('Topup Report clicked')">Topup Report</button>
    <button onclick="showMain()">Back to Dashboard</button>
  </div>

  <div class="sub-options" id="salesReportOptions" style="display:none;">
    <button onclick="location.href='billwise.php'">Billwise</button>
    <button onclick="location.href='itemwise.php'">Itemwise</button>
    <button onclick="location.href='salesman_report_form.php'">Salesman Wise</button>
    <button onclick="location.href='groupwise.php'">Groupwise</button>
    <button onclick="location.href='salesman_entry_report.php'">Sales Terminal</button>
    <button onclick="location.href='modewise.php'">Modewise</button>
    <button onclick="location.href='timewise.php'">Timewise</button>
    <button onclick="location.href='totalsales.php'">Total Sales</button>
    <button onclick="showReports()">Back to Reports</button>
  </div>

  <div class="sub-options" id="stockReportOptions" style="display:none;">
<button onclick="location.href='stock_entry.php'">Stock Entry Itemwise</button>
    <button onclick="alert('Stock Wastage Item wise clicked')">Stock Wastage Itemwise</button>
    <button onclick="alert('Today Item Stock clicked')">Today Item Stock</button>
    <button onclick="alert('Today Item Zero Stock clicked')">Today Item Zero Stock</button>
    <button onclick="alert('Item Closing Stock clicked')">Item Closing Stock</button>
    <button onclick="showReports()">Back to Reports</button>
  </div>
</div>

<script>
  function showReports() {
    document.getElementById('mainButtons').style.display = 'none';
    document.getElementById('salesReportOptions').style.display = 'none';
    document.getElementById('stockReportOptions').style.display = 'none';
    document.getElementById('reportOptions').style.display = 'flex';
  }

  function showSalesReports() {
    document.getElementById('reportOptions').style.display = 'none';
    document.getElementById('salesReportOptions').style.display = 'flex';
    document.getElementById('stockReportOptions').style.display = 'none';
  }

  function showStockReports() {
    document.getElementById('reportOptions').style.display = 'none';
    document.getElementById('salesReportOptions').style.display = 'none';
    document.getElementById('stockReportOptions').style.display = 'flex';
  }

  function showMain() {
    document.getElementById('reportOptions').style.display = 'none';
    document.getElementById('salesReportOptions').style.display = 'none';
    document.getElementById('stockReportOptions').style.display = 'none';
    document.getElementById('mainButtons').style.display = 'flex';
  }

  function goBack() {
    const main = document.getElementById('mainButtons');
    const reports = document.getElementById('reportOptions');
    const sales = document.getElementById('salesReportOptions');
    const stock = document.getElementById('stockReportOptions');

    if (sales.style.display === 'flex' || stock.style.display === 'flex') {
      // If in sales/stock reports → go back to reports menu
      showReports();
    } else if (reports.style.display === 'flex') {
      // If in reports menu → go back to main dashboard
      showMain();
    } else {
      // If already in main dashboard → go back to login/index.php
      location.href = '../frontend/index.php';
    }
  }
</script>

</body>
</html>
