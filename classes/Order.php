<?php
class Order
{
    private $conn;
    private $table_name = "orders";
    private $items_table = "order_items";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // UPDATED FUNCTION
    public function createOrder($user_id, $total_amount, $payment_method, $items_array)
    {
        $bill_number = 'BILL' . date('Ymd') . rand(1000, 9999);

        // Convert array to JSON
        $items_json = json_encode($items_array);

        $query = "INSERT INTO " . $this->table_name . " (user_id, bill_number, total_amount, payment_method, items)
              VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $bill_number, $total_amount, $payment_method, $items_json]);

        return $this->conn->lastInsertId();
    }

    public function addOrderItem($order_id, $food_item_id, $quantity, $price)
    {
        $query = "INSERT INTO " . $this->items_table . " (order_id, food_item_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$order_id, $food_item_id, $quantity, $price]);
    }

    public function updatePaymentStatus($order_id, $status)
    {
        $query = "UPDATE " . $this->table_name . " SET payment_status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$status, $order_id]);
    }

    public function updateRazorpayDetails($order_id, $razorpay_order_id, $razorpay_payment_id = null)
    {
        $query = "UPDATE " . $this->table_name . " SET razorpay_order_id = ?, razorpay_payment_id = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$razorpay_order_id, $razorpay_payment_id, $order_id]);
    }

    public function getOrderById($order_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$order_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOrderItems($order_id)
    {
        $query = "SELECT oi.*, fi.name FROM " . $this->items_table . " oi 
                  JOIN food_items fi ON oi.food_item_id = fi.id 
                  WHERE oi.order_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserOrders($user_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentOrders($limit = 10)
    {
        $limit = (int)$limit;
        $query = "SELECT o.*, u.roll_no FROM " . $this->table_name . " o 
                  JOIN users u ON o.user_id = u.id 
                  ORDER BY o.created_at DESC LIMIT $limit";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalOrders()
    {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function getTotalRevenue()
    {
        $query = "SELECT SUM(total_amount) as revenue FROM " . $this->table_name . " WHERE payment_status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['revenue'] ?? 0;
    }

    public function getTodayStats()
    {
        $today = date('Y-m-d');

        $query = "SELECT 
                    COUNT(*) as orders,
                    COALESCE(SUM(total_amount), 0) as revenue,
                    COALESCE(SUM((SELECT SUM(quantity) FROM " . $this->items_table . " WHERE order_id = o.id)), 0) as items
                  FROM " . $this->table_name . " o 
                  WHERE DATE(created_at) = ? AND payment_status = 'completed'";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$today]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getStockReport($start_date = null, $end_date = null)
    {
        if (!$start_date) $start_date = date('Y-m-d');
        if (!$end_date) $end_date = date('Y-m-d');

        $query = "SELECT 
                    fi.name,
                    fi.category,
                    fi.price,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price) as total_revenue,
                    MAX(o.created_at) as last_sold
                  FROM food_items fi
                  JOIN " . $this->items_table . " oi ON fi.id = oi.food_item_id
                  JOIN " . $this->table_name . " o ON oi.order_id = o.id
                  WHERE DATE(o.created_at) BETWEEN ? AND ? 
                    AND o.payment_status = 'completed'
                  GROUP BY fi.id, fi.name, fi.category, fi.price
                  ORDER BY total_revenue DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDailySummary($start_date, $end_date)
    {
        $query = "SELECT 
                    DATE(o.created_at) as date,
                    COUNT(*) as total_orders,
                    SUM(o.total_amount) as total_revenue,
                    SUM(oi.quantity) as total_items
                  FROM " . $this->table_name . " o
                  JOIN " . $this->items_table . " oi ON oi.order_id = o.id
                  WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.payment_status = 'completed'
                  GROUP BY DATE(o.created_at)
                  ORDER BY date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDailyReport($start_date, $end_date)
    {
        $query = "SELECT 
                    DATE(o.created_at) as date,
                    COUNT(*) as total_orders,
                    SUM(o.total_amount) as total_revenue,
                    SUM(oi.quantity) as total_items
                  FROM " . $this->table_name . " o
                  JOIN " . $this->items_table . " oi ON oi.order_id = o.id
                  WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.payment_status = 'completed'
                  GROUP BY DATE(o.created_at)
                  ORDER BY date";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWeeklyReport($start_date, $end_date)
    {
        $query = "SELECT 
                    CONCAT(YEAR(o.created_at), '-W', LPAD(WEEK(o.created_at), 2, '0')) as week,
                    COUNT(*) as total_orders,
                    SUM(o.total_amount) as total_revenue,
                    SUM(oi.quantity) as total_items
                  FROM " . $this->table_name . " o
                  JOIN " . $this->items_table . " oi ON oi.order_id = o.id
                  WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.payment_status = 'completed'
                  GROUP BY YEAR(o.created_at), WEEK(o.created_at)
                  ORDER BY YEAR(o.created_at), WEEK(o.created_at)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyReport($start_date, $end_date)
    {
        $query = "SELECT 
                    DATE_FORMAT(o.created_at, '%Y-%m') as month,
                    COUNT(*) as total_orders,
                    SUM(o.total_amount) as total_revenue,
                    SUM(oi.quantity) as total_items
                  FROM " . $this->table_name . " o
                  JOIN " . $this->items_table . " oi ON oi.order_id = o.id
                  WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.payment_status = 'completed'
                  GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
                  ORDER BY DATE_FORMAT(o.created_at, '%Y-%m')";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getYearlyReport($start_date, $end_date)
    {
        $query = "SELECT 
                    YEAR(o.created_at) as year,
                    COUNT(*) as total_orders,
                    SUM(o.total_amount) as total_revenue,
                    SUM(oi.quantity) as total_items
                  FROM " . $this->table_name . " o
                  JOIN " . $this->items_table . " oi ON oi.order_id = o.id
                  WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.payment_status = 'completed'
                  GROUP BY YEAR(o.created_at)
                  ORDER BY YEAR(o.created_at)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getItemSalesReport($start_date, $end_date)
    {
        $query = "SELECT 
                    fi.name,
                    fi.category,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price) as total_revenue
                  FROM food_items fi
                  JOIN " . $this->items_table . " oi ON fi.id = oi.food_item_id
                  JOIN " . $this->table_name . " o ON oi.order_id = o.id
                  WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.payment_status = 'completed'
                  GROUP BY fi.id, fi.name, fi.category
                  ORDER BY total_revenue DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Payment method report between dates.
     * Returns rows with: payment_mode, payment_method, orders_count, total_amount
     */
    /**
     * Payment method report between dates.
     * Optional filters: filter_mode (payment_mode) and filter_method (payment_method).
     * Returns rows with: payment_mode, payment_method, orders_count, total_amount
     */
    public function getPaymentMethodReport($start_date = null, $end_date = null, $filter_mode = null, $filter_method = null, $group_by = 'both')
    {
        if (!$start_date) $start_date = date('Y-m-d');
        if (!$end_date) $end_date = date('Y-m-d');
        $params = [$start_date, $end_date];

        // Build query depending on grouping preference
        if ($group_by === 'mode') {
            $query = "SELECT UPPER(TRIM(COALESCE(o.payment_mode, 'unknown'))) as payment_mode,
                             COUNT(*) as orders_count,
                             COALESCE(SUM(o.total_amount), 0) as total_amount
                      FROM " . $this->table_name . " o
                      WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.payment_status = 'completed'";

            // apply optional filters
            if (!empty($filter_mode)) {
                $query .= " AND UPPER(TRIM(COALESCE(o.payment_mode, 'unknown'))) = ?";
                $params[] = strtoupper(trim($filter_mode));
            }
            if (!empty($filter_method)) {
                $query .= " AND UPPER(TRIM(COALESCE(o.payment_method, 'unknown'))) = ?";
                $params[] = strtoupper(trim($filter_method));
            }

            $query .= " GROUP BY payment_mode ORDER BY payment_mode ASC";

        } elseif ($group_by === 'method') {
            $query = "SELECT UPPER(TRIM(COALESCE(o.payment_method, 'unknown'))) as payment_method,
                             COUNT(*) as orders_count,
                             COALESCE(SUM(o.total_amount), 0) as total_amount
                      FROM " . $this->table_name . " o
                      WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.payment_status = 'completed'";

            if (!empty($filter_mode)) {
                $query .= " AND UPPER(TRIM(COALESCE(o.payment_mode, 'unknown'))) = ?";
                $params[] = strtoupper(trim($filter_mode));
            }
            if (!empty($filter_method)) {
                $query .= " AND UPPER(TRIM(COALESCE(o.payment_method, 'unknown'))) = ?";
                $params[] = strtoupper(trim($filter_method));
            }

            $query .= " GROUP BY payment_method ORDER BY payment_method ASC";

        } else {
            // default: group by both mode & method (combination)
            $query = "SELECT 
                            UPPER(TRIM(COALESCE(o.payment_mode, 'unknown'))) as payment_mode,
                            UPPER(TRIM(COALESCE(o.payment_method, 'unknown'))) as payment_method,
                            COUNT(*) as orders_count,
                            COALESCE(SUM(o.total_amount), 0) as total_amount
                        FROM " . $this->table_name . " o
                        WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.payment_status = 'completed'";

            if (!empty($filter_mode)) {
                $query .= " AND UPPER(TRIM(COALESCE(o.payment_mode, 'unknown'))) = ?";
                $params[] = strtoupper(trim($filter_mode));
            }
            if (!empty($filter_method)) {
                $query .= " AND UPPER(TRIM(COALESCE(o.payment_method, 'unknown'))) = ?";
                $params[] = strtoupper(trim($filter_method));
            }

            $query .= " GROUP BY payment_mode, payment_method
                        ORDER BY payment_mode ASC, payment_method ASC";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return distinct normalized payment modes present in completed orders within an optional date range.
     * If start_date/end_date are omitted, returns from all time.
     */
    public function getDistinctPaymentModes($start_date = null, $end_date = null)
    {
        $query = "SELECT DISTINCT UPPER(TRIM(COALESCE(payment_mode, 'unknown'))) as payment_mode
                  FROM " . $this->table_name . "
                  WHERE payment_status = 'completed'";

        $params = [];
        if ($start_date && $end_date) {
            $query .= " AND DATE(created_at) BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
        }
        $query .= " ORDER BY payment_mode ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_column($rows, 'payment_mode');
    }

    /**
     * Return distinct normalized payment methods present in completed orders within an optional date range.
     */
    public function getDistinctPaymentMethods($start_date = null, $end_date = null)
    {
        $query = "SELECT DISTINCT UPPER(TRIM(COALESCE(payment_method, 'unknown'))) as payment_method
                  FROM " . $this->table_name . "
                  WHERE payment_status = 'completed'";

        $params = [];
        if ($start_date && $end_date) {
            $query .= " AND DATE(created_at) BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
        }
        $query .= " ORDER BY payment_method ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_column($rows, 'payment_method');
    }


    public function getTopSellingItems($limit = 5)
    {
        $limit = (int)$limit;
        $query = "SELECT fi.id as food_item_id, fi.name as food_name, fi.price as price, SUM(oi.quantity) as total_sold
                  FROM " . $this->items_table . " oi
                  JOIN food_items fi ON oi.food_item_id = fi.id
                  JOIN " . $this->table_name . " o ON oi.order_id = o.id
                  WHERE o.payment_status = 'completed'
                  GROUP BY fi.id, fi.name, fi.price
                  ORDER BY total_sold DESC
                  LIMIT $limit";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
