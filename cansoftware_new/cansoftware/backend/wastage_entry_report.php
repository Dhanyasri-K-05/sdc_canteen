<?php
session_start();
if ($_SESSION['role'] !== 'canteen') {
  header('Location: index.php');
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entry_date'])) {
    $selectedDate = $_POST['entry_date'];

    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=stock_entry_db", "postgres", "postgres");

    // Fetch all stock entries for the selected date
    $query = "SELECT * FROM stock_entries WHERE entry_date = :entry_date ORDER BY item_no";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['entry_date' => $selectedDate]);
    $stockEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $wastageEntries = [];

    foreach ($stockEntries as $entry) {
        $itemName = $entry['item_name'];
        $itemNo = $entry['item_no'];
        $stockQty = $entry['quantity'];
        $cost = $entry['cost'];

        // Get total quantity sold for this item on this date
        $saleStmt = $pdo->prepare("
            SELECT COALESCE(SUM(quantity), 0) as sold_qty 
            FROM sales 
            WHERE item_name = :item_name AND DATE(sale_timestamp) = :entry_date
        ");
        $saleStmt->execute([
            'item_name' => $itemName,
            'entry_date' => $selectedDate
        ]);
        $soldQty = $saleStmt->fetch(PDO::FETCH_ASSOC)['sold_qty'];

        $unsoldQty = $stockQty - $soldQty;

        if ($unsoldQty > 0) {
            $totalAmount = $unsoldQty * $cost;

            // Insert into wastage table
            $insertStmt = $pdo->prepare("
                INSERT INTO stock_wastage_entries (entry_date, item_no, item_name, quantity, cost, total_amount)
                VALUES (:entry_date, :item_no, :item_name, :quantity, :cost, :total)
            ");
            $insertStmt->execute([
                'entry_date' => $selectedDate,
                'item_no' => $itemNo,
                'item_name' => $itemName,
                'quantity' => $unsoldQty,
                'cost' => $cost,
                'total' => $totalAmount
            ]);

            $wastageEntries[] = [
                'item_no' => $itemNo,
                'item_name' => $itemName,
                'quantity' => $unsoldQty,
                'cost' => $cost,
                'total_amount' => $totalAmount
            ];
        }
    }
} else {
    header('Location: wastage_entry.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Wastage Entry Report</title>
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
  <a href="wastage_entry.php" class="back-btn">← Back</a>
  <h2>Wastage Entry Report</h2>
  <h3>Date: <?= htmlspecialchars($selectedDate) ?></h3>

  <?php if (count($wastageEntries) > 0): ?>
  <table>
    <tr>
      <th>Item No</th>
      <th>Item Name</th>
      <th>Unsold Quantity</th>
      <th>Cost per Item</th>
      <th>Total Amount</th>
    </tr>
    <?php foreach ($wastageEntries as $entry): ?>
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
    <p style="text-align:center; color:red;">No wastage entries found (All items sold).</p>
  <?php endif; ?>
</div>

</body>
</html>
