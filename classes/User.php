<?php
require_once(__DIR__ . '/../config/database.php');

class User {
    private $conn;
    private $table_name = "users";
     private $staff_table = "staff";

    public function __construct($db) {
        $this->conn = $db;
    }

   
    // ✅ Check if roll_no exists in staff table
    private function isStaff($roll_no) {
        $query = "SELECT id FROM " . $this->staff_table . " WHERE staff_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$roll_no]);
        return $stmt->fetch() ? true : false;
    }

    // ✅ Register user with auto role assignment
    public function register($roll_no, $email, $password) {
        // Determine role automatically
        $role = $this->isStaff($roll_no) ? 'staff' : 'user';

        $query = "INSERT INTO " . $this->table_name . " (roll_no, email, password, role, wallet_balance, created_at) 
                  VALUES (?, ?, ?, ?, 0.00, NOW())";
        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $result = $stmt->execute([$roll_no, $email, $hashed_password, $role]);
            if ($result) {
                error_log("New user registered: Roll Number: $roll_no, Email: $email, Role: $role");
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            throw new Exception("Registration failed: " . $e->getMessage());
        }
    }

    public function login($roll_no, $password) {
        $query = "SELECT id, roll_no, email, password, role, wallet_balance, created_at FROM " . $this->table_name . " WHERE roll_no = ? OR email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$roll_no, $roll_no]); // Allow login with username or email
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Log successful login
            error_log("User logged in: Roll Number: " . $user['roll_no'] . ", Role: " . $user['role']);
            
            unset($user['password']); // Remove password from returned data
            return $user;
        }
        
        return false;
    }





    public function getUserById($id) {
        $query = "SELECT id, roll_no, email, role, wallet_balance, created_at FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateWalletBalance($user_id, $amount) {
        $query = "UPDATE " . $this->table_name . " SET wallet_balance = wallet_balance + ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$amount, $user_id]);
    }

    public function getWalletBalance($user_id) {
        $query = "SELECT wallet_balance FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['wallet_balance'] : 0;
    }

    public function updateRole($user_id, $role) {
        $valid_roles = ['user', 'cashier', 'admin'];
        if (!in_array($role, $valid_roles)) {
            throw new Exception("Invalid role specified");
        }
        
        $query = "UPDATE " . $this->table_name . " SET role = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$role, $user_id]);
    }

    public function getAllUsers() {
        $query = "SELECT id, roll_no, email, role, wallet_balance, created_at FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalUsers() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function getUsersByRole($role) {
        $query = "SELECT id, roll_no, email, role, wallet_balance, created_at FROM " . $this->table_name . " WHERE role = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkUserExists($roll_no, $email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE roll_no = ? OR email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$roll_no, $email]);
        return $stmt->fetch() ? true : false;
    }
}
?>
