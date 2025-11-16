<?php
/**
 * Auto Stock Reset for Coffee and Tea
 * This script automatically sets coffee and tea stock to 0 after 11:00 AM
 * Run this script via cron job every minute or call it from a scheduled task
 * Or include it in frequently accessed pages
 */

require_once __DIR__ . '/config/database.php';

function resetCoffeeTeaStock() {
    $database = new Database();
    $db = $database->getConnection();
    
    $current_time = date('H:i:s');
    $current_date = date('Y-m-d');
    
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
            }
        }
        
        return true;
    }
    
    return false;
}

// If this script is called directly, execute the reset
if (php_sapi_name() === 'cli' || basename($_SERVER['PHP_SELF']) === 'auto_stock_reset.php') {
    resetCoffeeTeaStock();
    echo "Stock reset check completed at " . date('Y-m-d H:i:s') . "\n";
}
?>
