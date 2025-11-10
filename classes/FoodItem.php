<?php
class FoodItem {
    private $conn;
    private $table_name = "food_items";
    private $requests_table = "cashier_requests";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllItems() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE is_active = 1 ORDER BY category, name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getItemById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCurrentCategory() {
        $current_time = date('H:i');
        
        if ($current_time >= '06:00' && $current_time <= '11:00') {
            return 'breakfast';
        } elseif ($current_time >= '12:00' && $current_time <= '16:00') {
            return 'lunch';
        } elseif ($current_time >= '16:00' && $current_time <= '19:00') {
            return 'snacks';
        } else {
            return 'beverages';
        }
    }

    public function getAvailableItemsByTime($current_time) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE is_active = 1 AND (
            (category = 'breakfast' AND ? BETWEEN '06:00' AND '11:00') OR
            (category = 'lunch' AND ? BETWEEN '12:00' AND '16:00') OR
            (category = 'snacks' AND ? BETWEEN '16:00' AND '19:00') OR
            (category = 'beverages' AND ? BETWEEN '06:00' AND '22:00')
        ) ORDER BY category, name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$current_time, $current_time, $current_time, $current_time]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

  public function requestAddItem($name, $description, $price, $quantity_available, $category, $time_available, $cashier_id) {
    $request_data = json_encode([
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'quantity_available' => $quantity_available,
        'category' => $category,
        'time_available' => $time_available
    ]);

    $query = "INSERT INTO " . $this->requests_table . " (cashier_id, request_type, request_data) 
              VALUES (?, 'add_item', ?)";
    $stmt = $this->conn->prepare($query);
    return $stmt->execute([$cashier_id, $request_data]);
}


 /*    public function requestUpdateItem($item_id, $changes, $cashier_id) {
        $request_data = json_encode($changes);

        $query = "INSERT INTO " . $this->requests_table . " (cashier_id, request_type, food_item_id, request_data) VALUES (?, 'update_item', ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$cashier_id, $item_id, $request_data]);
    } */

    public function requestDeleteItem($item_id, $cashier_id) {
        $request_data = json_encode(['action' => 'delete']);

        $query = "INSERT INTO " . $this->requests_table . " (cashier_id, request_type, food_item_id, request_data) VALUES (?, 'delete_item', ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$cashier_id, $item_id, $request_data]);
    }

    public function getPendingApprovals() {
        $query = "SELECT cr.*, fi.name as item_name, fi.price as current_price, fi.category as current_category, 
                         fi.description as current_description, u.roll_no as cashier_name,
                         cr.request_type as action_type, cr.request_data as change_data
                  FROM " . $this->requests_table . " cr
                  LEFT JOIN " . $this->table_name . " fi ON cr.food_item_id = fi.id
                  JOIN users u ON cr.cashier_id = u.id
                  WHERE cr.status = 'pending'
                  ORDER BY cr.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingApprovalsCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->requests_table . " WHERE status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function approveChange($request_id, $admin_id) {
        $this->conn->beginTransaction();
        
        try {
            // Get request details
            $query = "SELECT * FROM " . $this->requests_table . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                throw new Exception("Request not found");
            }
            
            $request_data = json_decode($request['request_data'], true);
            
            // Execute the requested action
            switch ($request['request_type']) {
                case 'add_item':
                    $query = "INSERT INTO " . $this->table_name . " (name, description, price, quantity_available, category, time_available) VALUES (?, ?, ?,?, ?, ?)";
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([
                        $request_data['name'],
                        $request_data['description'],
                        $request_data['price'],
                        $request_data['quantity_available'],
                        $request_data['category'],
                        $request_data['time_available']
                    ]);
                    break;
                    
                case 'update_item':
                    $set_clauses = [];
                    $values = [];
                    
                    foreach ($request_data as $field => $value) {
                        $set_clauses[] = "$field = ?";
                        $values[] = $value;
                    }
                    
                    $values[] = $request['food_item_id'];
                    
                    $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $set_clauses) . " WHERE id = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute($values);
                    break;
                    
                case 'delete_item':
                    $query = "UPDATE " . $this->table_name . " SET is_active = 0 WHERE id = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([$request['food_item_id']]);
                    break;
            }
            
            // Update request status
            $query = "UPDATE " . $this->requests_table . " SET status = 'approved', admin_id = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$admin_id, $request_id]);
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function rejectChange($request_id, $admin_id) {
        $query = "UPDATE " . $this->requests_table . " SET status = 'rejected', admin_id = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$admin_id, $request_id]);
    }




public function updateItem($id, $name = null, $description = null, $price = null, $category = null) {
    $fields = [];
    $params = [];

    if ($name !== null) {
        $fields[] = "name = :name";
        $params[':name'] = $name;
    }
    if ($description !== null) {
        $fields[] = "description = :description";
        $params[':description'] = $description;
    }
    if ($price !== null) {
        $fields[] = "price = :price";
        $params[':price'] = $price;
    }
    if ($category !== null) {
        $fields[] = "category = :category";
        $params[':category'] = $category;
    }

    if (empty($fields)) {
        return false; // No changes
    }

    $sql = "UPDATE food_items SET " . implode(", ", $fields) . " WHERE id = :id";
    $stmt = $this->conn->prepare($sql);
    $params[':id'] = $id;

    return $stmt->execute($params);
}



public function createOrder($user_id, $bill_number, $items, $payment_method = 'wallet') {
    $this->conn->beginTransaction();
    try {
        // Calculate total
        $total_amount = 0;
        foreach ($items as $item) {
            $total_amount += $item['quantity'] * $item['price'];
        }

        // Convert items to JSON dictionary: foodname => qty
        $items_array = [];
        foreach ($items as $item) {
            $items_array[$item['food_item_name']] = $item['quantity']; 
        }
        $items_json = json_encode($items_array);

        // Insert into orders (now including items JSON)
        $stmt = $this->conn->prepare("
            INSERT INTO orders (user_id, bill_number, total_amount, payment_method, items)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $bill_number, $total_amount, $payment_method, $items_json]);
        $order_id = $this->conn->lastInsertId();

        // Insert each item to order_items table
        $stmt = $this->conn->prepare("
            INSERT INTO order_items (order_id, food_item_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");
        foreach ($items as $item) {
            $stmt->execute([$order_id, $item['food_item_id'], $item['quantity'], $item['price']]);
        }

        $this->conn->commit();
        return $order_id;

    } catch (Exception $e) {
        $this->conn->rollback();
        throw $e;
    }
}


public function approveOrder($order_id, $admin_id) {
    $this->conn->beginTransaction();
    try {
        // Get all items for this order
        $stmt = $this->conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Reduce stock
        foreach ($items as $item) {
            $stmt2 = $this->conn->prepare("
                UPDATE food_items 
                SET quantity_available = quantity_available - ? 
                WHERE id = ? AND quantity_available >= ?
            ");
            $stmt2->execute([$item['quantity'], $item['food_item_id'], $item['quantity']]);
        }

        // Update payment_status to 'completed' or status to 'approved'
        $stmt = $this->conn->prepare("
            UPDATE orders SET payment_status = 'completed' WHERE id = ?
        ");
        $stmt->execute([$order_id]);

        $this->conn->commit();
        return true;

    } catch (Exception $e) {
        $this->conn->rollback();
        throw $e;
    }
}

public function rejectOrder($order_id, $admin_id) {
    $stmt = $this->conn->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?");
    return $stmt->execute([$order_id]);
}


      public function reduceStockByName($name, $quantity) {
        $query = "UPDATE {$this->table_name} 
                  SET quantity_available = quantity_available - :quantity 
                  WHERE food_name = :name AND quantity_available >= :quantity";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':name', $name);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() === 0) {
                throw new Exception("Insufficient stock or invalid item: $name");
            }
            return true;
        }
        throw new Exception("Failed to update stock for item: $name");
    }



    public function getItemByName($name) {
    $query = "SELECT * FROM {$this->table_name} WHERE name = :name LIMIT 1";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}












}
?>
