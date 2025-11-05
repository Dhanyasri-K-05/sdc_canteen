<?php 
session_start(); 
if ($_SESSION['role'] !== 'canteen') header('Location: index.php'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Wastage Entry - Date Selection</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f0f2f5;
      margin: 30px;
      color: #333;
    }

    .dashboard {
      max-width: 500px;
      margin: 0 auto;
      background: white;
      padding: 25px 30px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .dashboard h2 {
      text-align: center;
      margin-bottom: 25px;
      font-weight: 600;
      color: #2c3e50;
    }

    label {
      display: block;
      margin: 12px 0 6px;
      font-weight: 500;
    }

    input[type="date"] {
      width: 100%;
      padding: 10px;
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

<form method="POST" action="wastage_entry_report.php" class="dashboard">
  <h2>Select Wastage Entry Date</h2>

  <label for="entry_date">Date:</label>
  <input type="date" name="entry_date" id="entry_date" required>

  <button type="submit" class="submit-btn">Generate</button>
</form>

</body>
</html>
