<?php
require_once(__DIR__ . '/../config/database.php');

class User {
    private $conn;
    private $table_name = "users";
    private $staff_table = "staff";
    private $encryption_key;
    private $cipher_method = "AES-256-CBC"; 

    public function __construct($db) {
        $this->conn = $db;
        $this->encryption_key = $_ENV['ENCRYPTION_KEY'] ?? 'default_32_byte_secret_key_1234567890';
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
        $role = $this->isStaff($roll_no) ? 'staff' : 'user';
        $encrypted_balance = $this->encryptBalance(0.00);

        $query = "INSERT INTO " . $this->table_name . " 
                  (roll_no, email, password, role, wallet_balance, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $result = $stmt->execute([$roll_no, $email, $hashed_password, $role, $encrypted_balance]);
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

    // ✅ Login
    public function login($roll_no, $password) {
        $query = "SELECT id, roll_no, email, password, role, wallet_balance, created_at 
                  FROM " . $this->table_name . " 
                  WHERE roll_no = ? OR email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$roll_no, $roll_no]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            error_log("User logged in: Roll Number: " . $user['roll_no'] . ", Role: " . $user['role']);
            unset($user['password']);
            return $user;
        }
        return false;
    }

    // ✅ Get user by ID
    public function getUserById($id) {
        $query = "SELECT id, roll_no, email, role, wallet_balance, created_at 
                  FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ✅ Encrypt wallet balance
    public function encryptBalance($amount) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher_method));
        $encrypted = openssl_encrypt($amount, $this->cipher_method, $this->encryption_key, 0, $iv);
        return base64_encode($encrypted . "::" . $iv);
    }

    // ✅ Decrypt wallet balance
    public function decryptBalance($encrypted_value) {
        list($encrypted_data, $iv) = explode("::", base64_decode($encrypted_value), 2);
        return openssl_decrypt($encrypted_data, $this->cipher_method, $this->encryption_key, 0, $iv);
    }

    // ✅ Update wallet balance (encrypted)
    public function updateWalletBalance($user_id, $amount) {
        $query = "SELECT wallet_balance FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $current_balance = 0;
        if ($result && $result['wallet_balance']) {
            $current_balance = $this->decryptBalance($result['wallet_balance']);
        }

        $new_balance = $current_balance + $amount;
        $encrypted_balance = $this->encryptBalance($new_balance);

        $update_query = "UPDATE " . $this->table_name . " SET wallet_balance = ? WHERE id = ?";
        $update_stmt = $this->conn->prepare($update_query);
        return $update_stmt->execute([$encrypted_balance, $user_id]);
    }

    // ✅ Get wallet balance (decrypted)
    public function getWalletBalance($user_id) {
        $query = "SELECT wallet_balance FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['wallet_balance']) {
            return $this->decryptBalance($result['wallet_balance']);
        }
        return 0;
    }

    // ✅ Update role
    public function updateRole($user_id, $role) {
        $valid_roles = ['user', 'cashier', 'admin', 'staff'];
        if (!in_array($role, $valid_roles)) {
            throw new Exception("Invalid role specified");
        }

        $query = "UPDATE " . $this->table_name . " SET role = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$role, $user_id]);
    }

    // ✅ Get all users
    public function getAllUsers() {
        $query = "SELECT id, roll_no, email, role, wallet_balance, created_at 
                  FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ Total users
    public function getTotalUsers() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    // ✅ Get users by role
    public function getUsersByRole($role) {
        $query = "SELECT id, roll_no, email, role, wallet_balance, created_at 
                  FROM " . $this->table_name . " WHERE role = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ Check if user exists
    public function checkUserExists($roll_no, $email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE roll_no = ? OR email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$roll_no, $email]);
        return $stmt->fetch() ? true : false;
    }

    /* ================================================================
       🔐 FORGOT PASSWORD SYSTEM METHODS
       ================================================================ */

    // ✅ Get user by email
    public function getUserByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ✅ Store reset code + expiry
    public function storeResetCode($email, $code, $expiry) {
        $query = "UPDATE " . $this->table_name . " SET reset_code = :code, reset_code_expiry = :expiry WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':expiry', $expiry);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
    }

    // ✅ Verify reset code
    public function verifyResetCode($email, $code) {
        $query = "SELECT reset_code, reset_code_expiry FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data && $data['reset_code'] == $code && strtotime($data['reset_code_expiry']) > time()) {
            // clear code once verified
            $clear = $this->conn->prepare("UPDATE " . $this->table_name . " SET reset_code = NULL, reset_code_expiry = NULL WHERE email = :email");
            $clear->bindParam(':email', $email);
            $clear->execute();
            return true;
        }
        return false;
    }

    // ✅ Update password securely
    public function updatePassword($email, $password) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':password', $hashed);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
    }
}
?>
