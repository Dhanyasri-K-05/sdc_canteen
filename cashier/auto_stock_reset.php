<?php
/**
 * Auto Stock Reset for Coffee and Tea
 * This script automatically sets coffee and tea stock to 0 after 11:00 AM
 * Run this script via cron job every minute or call it from a scheduled task
 * Or include it in frequently accessed pages
 */

require_once __DIR__ . '/../config/database.php';


function resetCoffeeTeaStock() {
    $database = new Database();
    $db = $database->getConnection();

    // Optional offset (in seconds) to compensate for server clock skew.
    // Sources checked in order: getenv, $_ENV
    $offset_seconds = 0;
    $envVal = getenv('AUTO_RESET_OFFSET_SECONDS');
    if ($envVal !== false) {
        $offset_seconds = intval($envVal);
    } elseif (isset($_ENV['AUTO_RESET_OFFSET_SECONDS'])) {
        $offset_seconds = intval($_ENV['AUTO_RESET_OFFSET_SECONDS']);
    }

    $now = time() + $offset_seconds;
    $current_time = date('H:i:s', $now);
    $current_date = date('Y-m-d', $now);

    $result = ['reset' => false, 'updated' => []];

    // Check if it's after 11:00 AM
    if ($current_time >= '11:00:00') {
        // Check if we already reset today
        $check_query = "SELECT id, name, last_stock_update FROM food_items 
                        WHERE LOWER(name) IN ('coffee', 'tea', 'masala tea') 
                        AND is_active = 1";
        $stmt = $db->prepare($check_query);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $last_reset_date = $item['last_stock_update'] ? date('Y-m-d', strtotime($item['last_stock_update'])) : null;

            // Only reset if not already reset today or if last_stock_update is before 11 AM today
            if ($last_reset_date !== $current_date || 
                ($last_reset_date === $current_date && strtotime($item['last_stock_update']) < strtotime($current_date . ' 11:00:00'))) {

                $update_query = "UPDATE food_items 
                                 SET quantity_available = 0, 
                                     last_stock_update = NOW() 
                                 WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([$item['id']]);

                error_log("Stock reset for {$item['name']} at " . date('Y-m-d H:i:s'));
                $result['reset'] = true;
                $result['updated'][] = ['id' => $item['id'], 'name' => $item['name']];
            }
        }
    }

    return $result;
}

// If this script is called directly, execute the reset
if (php_sapi_name() === 'cli' || basename($_SERVER['PHP_SELF']) === 'auto_stock_reset.php') {
    $res = resetCoffeeTeaStock();
    // If called via ajax, return JSON
    if (isset($_GET['ajax']) && $_GET['ajax']) {
        header('Content-Type: application/json');
        echo json_encode($res);
        exit;
    }

    // CLI or normal web call: print simple message
    echo "Stock reset check completed at " . date('Y-m-d H:i:s') . "\n";
}
?>
