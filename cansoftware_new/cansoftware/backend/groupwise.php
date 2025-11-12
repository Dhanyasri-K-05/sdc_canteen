<?php 
session_start(); 
if ($_SESSION['role'] !== 'canteen') header('Location: index.php'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Groupwise Report Filter</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f0f2f5;
      margin: 30px;
      color: #333;
    }

    .dashboard {
      max-width: 600px;
      margin: 0 auto;
      background: white;
      padding: 25px 30px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .dashboard h2 {
      text-align: center;
      margin-bottom: 30px;
      font-weight: 600;
      color: #2c3e50;
    }

    label {
      display: block;
      margin: 12px 0 6px;
      font-weight: 500;
    }

    input[type="date"],
    select {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
    }

    .submit-btn {
      margin-top: 20px;
      padding: 12px 18px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      font-size: 16px;
      cursor: pointer;
      width: 100%;
      transition: background-color 0.25s ease;
    }

    .submit-btn:hover {
      background-color: #0056b3;
    }

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    header button {
      font-size: 20px;
      background: none;
      border: none;
      cursor: pointer;
      color: #007bff;
    }

    header form {
      margin: 0;
    }

    header form button {
      padding: 8px 15px;
      background: #dc3545;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
  </style>
</head>
<body>

<header>
  <button onclick="history.back()">← Back</button>
  <form method="POST" action="logout.php">
    <button type="submit">Logout</button>
  </form>
</header>

<form method="POST" action="groupwise-report.php" class="dashboard">
  <h2>Groupwise Report</h2>
  <label>Bill Mode:</label>
    <div class="radio-group">
    <label><input type="radio" name="mode" value="UPI"> UPI</label>
    <label><input type="radio" name="mode" value="CASH"> CASH</label>
    <label><input type="radio" name="mode" value="" checked> Both</label>
  </div>
  <label for="category">Item Category:</label>
  <select name="category" id="category" required>
    <option value="Snacks">Snacks</option>
    <option value="Beverage">Beverage</option>
    <option value="Breakfast">Breakfast</option>
    <option value="Meals">Meals</option>
  </select>
  <label for="from_date">From Date:</label>
  <input type="date" name="from_date" id="from_date" required>

  <label for="to_date">To Date:</label>
  <input type="date" name="to_date" id="to_date" required>

  <button type="submit" class="submit-btn">Generate Report</button>
</form>
</body>
</html>
