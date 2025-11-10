<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
  header('Location: index.php');
  exit();
}

$success = '';
$pdo = new PDO("pgsql:host=localhost;port=5432;dbname=stock_entry_db", "postgres", "postgres");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $entry_date = $_POST['entry_date'];
    $item_no = $_POST['item_no'];
    $item_name = $_POST['item_name'];
    $quantity = $_POST['quantity'];
    $cost = $_POST['cost'];
    $total = $_POST['total'];

    $query = "INSERT INTO stock_entries (entry_date, item_no, item_name, quantity, cost, total_amount)
              VALUES (:entry_date, :item_no, :item_name, :quantity, :cost, :total)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
      'entry_date' => $entry_date,
      'item_no' => $item_no,
      'item_name' => $item_name,
      'quantity' => $quantity,
      'cost' => $cost,
      'total' => $total
    ]);

    $success = "Stock Entry Added Successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Stock Entry Form</title>
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
    input[type="text"],
    input[type="number"] {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
    }

    .submit-btn {
      margin-top: 20px;
      padding: 12px 18px;
      background-color: #28a745;
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
      background-color: #218838;
    }

    .success-message {
      text-align: center;
      margin-bottom: 20px;
      font-weight: 600;
      color: #007bff;
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
  <script>
    function calculateTotal() {
      let qty = parseFloat(document.getElementById('quantity').value) || 0;
      let cost = parseFloat(document.getElementById('cost').value) || 0;
      document.getElementById('total').value = qty * cost;
    }
  </script>
</head>
<body>

<header>
  <button onclick="history.back()">← Back</button>
  <form method="POST" action="../../frontend/logout.php">
    <button type="submit">Logout</button>
  </form>
</header>

<form method="POST" class="dashboard">
  <h2>Stock Entry Form</h2>

  <?php if ($success): ?>
    <p class="success-message"><?= $success ?></p>
  <?php endif; ?>

  <label>Date:</label>
  <input type="date" name="entry_date" required>

  <label>Item No:</label>
  <input type="text" name="item_no" required>

  <label>Item Name:</label>
  <input type="text" name="item_name" required>

  <label>Quantity:</label>
  <input type="number" name="quantity" id="quantity" oninput="calculateTotal()" required>

  <label>Cost per item:</label>
  <input type="number" name="cost" id="cost" oninput="calculateTotal()" required>

  <label>Total Amount:</label>
  <input type="number" name="total" id="total" readonly>

  <button type="submit" name="submit" class="submit-btn">Add</button>
</form>

</body>
</html>
