<?php
session_start();
if ($_SESSION['role'] !== 'canteen') {
  header('Location: index.php');
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entry_date'])) {
    $selectedDate = $_POST['entry_date'];

    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=stock_entry_db", "postgres", "postgres");

    $query = "SELECT * FROM stock_entries WHERE entry_date = :entry_date ORDER BY item_no";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['entry_date' => $selectedDate]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Redirect if date not selected
    header('Location: stock_entry.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Stock Entry Report</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f6f8;
      margin: 30px;
    }
    .container {
      max-width: 1000px;
      margin: 0 auto;
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 10px;
      color: #2c3e50;
    }
    h3 {
      text-align: center;
      margin-top: 0;
      color: #555;
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
    tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    .back-btn {
      margin-bottom: 20px;
      display: inline-block;
      padding: 8px 15px;
      background: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 5px;
    }
  </style>
</head>
<body>

<div class="container">
  <a href="stock_entry.php" class="back-btn">← Back</a>
  <h2>Stock Entry Report</h2>
  <h3>Date: <?= htmlspecialchars($selectedDate) ?></h3>

  <?php if (count($entries) > 0): ?>
  <table>
    <tr>
      <th>Item No</th>
      <th>Item Name</th>
      <th>Quantity</th>
      <th>Cost per Item</th>
      <th>Total Amount</th>
    </tr>
    <?php foreach ($entries as $entry): ?>
      <tr>
        <td><?= htmlspecialchars($entry['item_no']) ?></td>
        <td><?= htmlspecialchars($entry['item_name']) ?></td>
        <td><?= htmlspecialchars($entry['quantity']) ?></td>
        <td><?= htmlspecialchars($entry['cost']) ?></td>
        <td><?= htmlspecialchars($entry['total_amount']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <p style="text-align:center; color:red;">No stock entries found for this date.</p>
  <?php endif; ?>
</div>

</body>
</html>
