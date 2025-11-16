<?php
/**
 * Test Script for Stock Management System
 * Run this to verify all features are working correctly
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auto_stock_reset.php';

echo "=================================================\n";
echo "STOCK MANAGEMENT SYSTEM - TEST SCRIPT\n";
echo "=================================================\n\n";

$database = new Database();
$db = $database->getConnection();

// Test 1: Check if columns exist
echo "TEST 1: Checking Database Columns\n";
echo "-------------------------------------------------\n";
try {
    $stmt = $db->query("DESCRIBE food_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $has_quantity = false;
    $has_last_update = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'quantity_available') {
            $has_quantity = true;
            echo "✅ Column 'quantity_available' exists\n";
        }
        if ($column['Field'] === 'last_stock_update') {
            $has_last_update = true;
            echo "✅ Column 'last_stock_update' exists\n";
        }
    }
    
    if (!$has_quantity || !$has_last_update) {
        echo "❌ Required columns missing! Run migration script.\n";
    } else {
        echo "✅ All required columns present\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking columns: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check current time and stock entry status
echo "TEST 2: Stock Entry Time Check\n";
echo "-------------------------------------------------\n";
$current_time = date('H:i:s');
$stock_entry_allowed = ($current_time >= '15:00:00');
echo "Current Time: " . date('h:i:s A') . "\n";
echo "Stock Entry Status: " . ($stock_entry_allowed ? "✅ ALLOWED" : "❌ RESTRICTED (allowed after 3 PM)") . "\n";

echo "\n";

// Test 3: Check Coffee/Tea items
echo "TEST 3: Coffee & Tea Items Check\n";
echo "-------------------------------------------------\n";
try {
    $stmt = $db->query("
        SELECT id, name, quantity_available, last_stock_update 
        FROM food_items 
        WHERE LOWER(name) LIKE '%coffee%' OR LOWER(name) LIKE '%tea%'
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo "⚠️  No coffee or tea items found in database\n";
    } else {
        foreach ($items as $item) {
            echo "Item: {$item['name']}\n";
            echo "  Quantity: {$item['quantity_available']}\n";
            echo "  Last Update: " . ($item['last_stock_update'] ? $item['last_stock_update'] : 'Never') . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Check auto-reset logic
echo "TEST 4: Auto-Reset Logic Test\n";
echo "-------------------------------------------------\n";
$current_hour = date('H');
if ($current_hour >= 11) {
    echo "Current time is after 11 AM\n";
    echo "Attempting auto-reset...\n";
    
    $result = resetCoffeeTeaStock();
    if ($result) {
        echo "✅ Auto-reset executed successfully\n";
    } else {
        echo "ℹ️  Items already reset today or no items to reset\n";
    }
    
    // Check results
    $stmt = $db->query("
        SELECT name, quantity_available, last_stock_update 
        FROM food_items 
        WHERE LOWER(name) LIKE '%coffee%' OR LOWER(name) LIKE '%tea%'
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "After reset:\n";
    foreach ($items as $item) {
        echo "  {$item['name']}: {$item['quantity_available']}\n";
    }
} else {
    echo "ℹ️  Current time is before 11 AM\n";
    echo "   Auto-reset only works after 11:00 AM\n";
}

echo "\n";

// Test 5: Check all food items
echo "TEST 5: All Food Items Stock Status\n";
echo "-------------------------------------------------\n";
try {
    $stmt = $db->query("
        SELECT name, category, quantity_available, last_stock_update 
        FROM food_items 
        WHERE is_active = 1
        ORDER BY category, name
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $by_category = [];
    foreach ($items as $item) {
        $by_category[$item['category']][] = $item;
    }
    
    foreach ($by_category as $category => $cat_items) {
        echo "\n" . strtoupper($category) . ":\n";
        foreach ($cat_items as $item) {
            $stock_status = $item['quantity_available'] > 0 ? "✅" : "❌";
            echo "  {$stock_status} {$item['name']}: {$item['quantity_available']} units\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Simulate stock update (if allowed)
echo "TEST 6: Stock Update Simulation\n";
echo "-------------------------------------------------\n";
if ($stock_entry_allowed) {
    echo "✅ Stock entry is allowed\n";
    echo "   You can now test stock updates through the web interface\n";
    echo "   Navigate to: cashier/stock_update.php\n";
} else {
    echo "❌ Stock entry is restricted (before 3 PM)\n";
    echo "   Stock updates will be blocked until 3:00 PM\n";
    echo "   Web interface buttons should be disabled\n";
}

echo "\n";

// Summary
echo "=================================================\n";
echo "TEST SUMMARY\n";
echo "=================================================\n";
echo "System Status:\n";
echo "  Current Time: " . date('h:i:s A') . "\n";
echo "  Stock Entry: " . ($stock_entry_allowed ? "✅ ALLOWED" : "❌ RESTRICTED") . "\n";
echo "  Auto-Reset: " . ($current_hour >= 11 ? "✅ ACTIVE" : "⏰ WAITING (after 11 AM)") . "\n";

// Check if any coffee/tea has stock after 11 AM
if ($current_hour >= 11) {
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM food_items 
        WHERE (LOWER(name) LIKE '%coffee%' OR LOWER(name) LIKE '%tea%')
        AND quantity_available > 0
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "  ⚠️  WARNING: Coffee/Tea items have stock after 11 AM!\n";
        echo "     Auto-reset may not be working properly\n";
    } else {
        echo "  ✅ Coffee/Tea correctly reset (if it was after 11 AM)\n";
    }
}

echo "\nNext Steps:\n";
if (!$stock_entry_allowed) {
    echo "  1. Wait until 3:00 PM to test stock entry\n";
    echo "  2. Navigate to cashier/stock_update.php\n";
    echo "  3. Verify buttons are enabled and working\n";
} else {
    echo "  1. Test stock entry at cashier/stock_update.php\n";
    echo "  2. Verify real-time updates on user/dashboard.php\n";
    echo "  3. Test ordering with and without stock\n";
}

if ($current_hour < 11) {
    echo "  4. Wait until 11:00 AM to test auto-reset\n";
    echo "  5. Run this script again after 11 AM\n";
}

echo "\n=================================================\n";
echo "Test completed at: " . date('Y-m-d h:i:s A') . "\n";
echo "=================================================\n";
?>
