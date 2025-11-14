<?php
ob_clean();
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require __DIR__ . '/vendor/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

try {
    // 🔹 Read and decode input
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    // 🔸 Validate incoming JSON
    if (!$data || empty($data['items']) || empty($data['bill_number']) || empty($data['order_date'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing data']);
        exit;
    }

    // 🧩 Connect to database
    $pdo = new PDO("mysql:host=10.10.33.245;dbname=food_ordering_system", "root", "Dtipl@25");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 🖨️ Initialize printer
    $connector = new WindowsPrintConnector("Rugtek82");
    $printer = new Printer($connector);

    // 🧩 Group items by category
    $grouped = [];
    foreach ($data['items'] as $item) {
        $category = strtolower(trim($item['category'] ?? 'uncategorized'));
        $grouped[$category][] = [
            'product_name' => $item['name'] ?? 'Unnamed',
            'quantity'     => (int)($item['quantity'] ?? 1),
            'rate'         => (float)($item['price'] ?? 0),
            'total'        => (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1)
        ];
    }

    // 🕒 Format date & time
    $createdAt   = strtotime($data['order_date']);
    $billDate    = date('d-M-Y', $createdAt);
    $billTime    = date('h:i A', $createdAt);
    $dateTimeStr = "Date: {$billDate}   Time: {$billTime}\n";

    // 🔁 Print one bill per category
    foreach ($grouped as $category => $items) {
        $printer->setEmphasis(true);
        $printer->text("BILL NO: " . $data['bill_number'] . "\n");
        $printer->setEmphasis(false);
        $printer->text($dateTimeStr);
        $printer->text(str_repeat("-", 48) . "\n");

        // Header row
        $printer->setEmphasis(true);
        $printer->text(
            str_pad("ITEM", 20) .
            str_pad("QTY", 6, ' ', STR_PAD_LEFT) .
            str_pad("RATE", 10, ' ', STR_PAD_LEFT) .
            str_pad("TOTAL", 12, ' ', STR_PAD_LEFT) . "\n"
        );
        $printer->setEmphasis(false);
        $printer->text(str_repeat("-", 48) . "\n");

        // Items under each category
        foreach ($items as $it) {
            $printer->text(
                str_pad(substr($it['product_name'], 0, 20), 20) .
                str_pad($it['quantity'], 6, ' ', STR_PAD_LEFT) .
                str_pad(number_format($it['rate'], 2), 10, ' ', STR_PAD_LEFT) .
                str_pad(number_format($it['total'], 2), 12, ' ', STR_PAD_LEFT) . "\n"
            );
        }

        $printer->text(str_repeat("-", 48) . "\n\n");
        $printer->cut(); // ✂️ Cut between categories
    }

    // ✅ Close printer
    $printer->close();

    // ✅ Update bill_printed = 1 after successful print
    $updateStmt = $pdo->prepare("UPDATE orders SET bill_printed = 1 WHERE bill_number = :bill_number");
    $updateStmt->execute(['bill_number' => $data['bill_number']]);

    ob_end_clean();
    echo json_encode(['status' => 'success', 'message' => 'Bill printed per category and marked as printed']);
    exit;

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
?>
