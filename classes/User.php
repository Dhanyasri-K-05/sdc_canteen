<?php
require_once(__DIR__ . '/../config/database.php');


class User {
    private $conn;
    private $table_name = "users";
     private $staff_table = "staff";
     private $dept_table="dept";
     private $encryption_key;

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
    

   /*  // ✅ Register user with auto role assignment
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
 */

    // ✅ Register user with auto role assignment
public function register($roll_no, $email, $password) {
    // Determine role automatically
    $role = $this->isStaff($roll_no) ? 'staff' : 'user';

    // Encrypt the default wallet balance (0.00)
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

    public function login($roll_no, $password) {
        $query = "SELECT id, roll_no, email, password, role, wallet_balance, created_at FROM " . $this->table_name . " WHERE roll_no = ? ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$roll_no]); // Allow login with username or email
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Log successful login
            error_log("User logged in: Roll Number: " . $user['roll_no'] . ", Role: " . $user['role']);
            
            unset($user['password']); // Remove password from returned data
            return $user;
        }

          // 2️⃣ If not found → try logging in as a department
    $deptQuery = "SELECT id, dept_name, password 
                  FROM departments 
                  WHERE dept_name = ?";

    $deptStmt = $this->conn->prepare($deptQuery);
    $deptStmt->execute([$roll_no]);   // roll_no field contains dept name
    $dept = $deptStmt->fetch(PDO::FETCH_ASSOC);

    if ($dept && password_verify($password, $dept['password'])) {
        return [
            'id' => $dept['id'],
            'roll_no' => $dept['dept_name'],
            'role' => 'department',
            'login_type' => 'department'
        ];
    }
        
        return false;
    }





    public function getUserById($id) {
        $query = "SELECT id, roll_no, email, role, wallet_balance, created_at FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

     Private $cipher_method = "AES-256-CBC"; 


    // Encrypt value
 Public function encryptBalance($amount) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher_method));
    $encrypted = openssl_encrypt($amount, $this->cipher_method, $this->encryption_key, 0, $iv);
    return base64_encode($encrypted . "::" . $iv);
}
 
// Decrypt value
 Public function decryptBalance($encrypted_value) {
    list($encrypted_data, $iv) = explode("::", base64_decode($encrypted_value), 2);
    return openssl_decrypt($encrypted_data, $this->cipher_method, $this->encryption_key, 0, $iv);
} 

   /*  public function updateWalletBalance($user_id, $amount) {
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
    } */
 

    public function updateWalletBalance($user_id, $amount) {
    // Get current encrypted balance
    $query = "SELECT wallet_balance FROM " . $this->table_name . " WHERE id = ?";
    $stmt = $this->conn->prepare($query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $current_balance = 0;
    if ($result && $result['wallet_balance']) {
        $current_balance = $this->decryptBalance($result['wallet_balance']);
    }

    // Calculate new balance
    $new_balance = $current_balance + $amount;

    // Encrypt and update
    $encrypted_balance = $this->encryptBalance($new_balance);
    $update_query = "UPDATE " . $this->table_name . " SET wallet_balance = ? WHERE id = ?";
    $update_stmt = $this->conn->prepare($update_query);
    return $update_stmt->execute([$encrypted_balance, $user_id]);
}

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

    public function deleteUser($id) {
    $query = "DELETE FROM users WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
}

public function createUser($roll_no, $email, $password, $role = 'user') {
    $query = "INSERT INTO users (roll_no, email, password, role, created_at)
              VALUES (:roll_no, :email, :password, :role, NOW())";

    $stmt = $this->conn->prepare($query);

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt->bindParam(':roll_no', $roll_no);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':role', $role);

    return $stmt->execute();
}

public function updateUser($id, $email, $role = 'user') {
    $query = "UPDATE users SET email = :email, role = :role WHERE id = :id";
    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    return $stmt->execute();
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
