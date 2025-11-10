<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
  header('Location: index.php');
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f5f5f5;
      margin: 0;
      padding: 0;
    }

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 20px;
      background: #f8f9fa;
      border-bottom: 1px solid #ddd;
    }

    header button, header form button {
      font-size: 16px;
      background: none;
      border: none;
      cursor: pointer;
    }

    header form button {
      background: #dc3545;
      color: white;
      padding: 8px 15px;
      border-radius: 5px;
    }

    .dashboard {
      max-width: 600px;
      margin: 40px auto;
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      text-align: center;
    }

    .dashboard h2 {
      margin-bottom: 30px;
      color: #333;
    }

    .button-group {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .button-group button {
      padding: 12px 20px;
      font-size: 16px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .button-group button:hover {
      background-color: #0056b3;
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
    <h2>Admin Dashboard</h2>

    <div class="button-group">
      <button onclick="location.href='stock_entry_form.php'">Stock Entry</button>
      <button onclick="location.href='wastage_entry.php'">Wastage Entry</button>
        <button onclick="location.href='admin_requests.php'"></button>
    </div>
  </div>

  <script>
    function goBack() {
      if (window.history.length > 1) {
        window.history.back();
      } else {
        window.location.href = 'dashboard-canteen.php';
      }
    }
  </script>

</body>
</html>
