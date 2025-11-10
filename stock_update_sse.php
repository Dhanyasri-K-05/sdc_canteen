<?php
require_once 'config/database.php';
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

$last_update = isset($_GET['last_update']) ? $_GET['last_update'] : 0;

$database = new Database();
$db = $database->getConnection();

// Check for changes every second
while (true) {
    $query = "SELECT * FROM food_items WHERE is_active=1 AND updated_at > ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$last_update]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($items)) {
        echo "data: " . json_encode($items) . "\n\n";
        // Send the latest timestamp to client
        $last_update = time();
    }

    ob_flush();
    flush();
    sleep(1);
}
?>
