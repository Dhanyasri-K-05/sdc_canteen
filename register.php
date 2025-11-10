<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'classes/User.php';

// if (isLoggedIn()) {
//     switch ($_SESSION['role']) {
//         case 'admin':
//             header('Location: admin/dashboard.php');
//             break;
//         case 'cashier':
//             header('Location: cashier/dashboard.php');
//             break;
//         case 'user':
//             header('Location: user/dashboard.php');
//             break;
//         default:
//             header('Location: user/dashboard.php');
//     }
//     exit();
// }

$error_message = '';
$success_message = '';

if ($_POST && isset($_POST['register'])) {
    $roll_no = trim($_POST['roll_no']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
   // $role = "user"; 
    
    // Validation
    if (empty($roll_no) || empty($email) || empty($password) || empty($confirm_password) ) {
        $error_message = "Please fill in all fields";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address";
    }
    //elseif (!in_array($role, ['user', 'cashier', 'admin'])) {
    //     $error_message = "Please select a valid role";}
     else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $user = new User($db);
            
            // Check if username or email already exists
            $query = "SELECT id FROM users WHERE roll_no = ? OR email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$roll_no, $email]);
            
            if ($stmt->fetch()) {
                $error_message = "Roll Number or email already exists";
            } else {
               /*  // Register new user with selected role
                if ($user->register($roll_no, $email, $password, $role)) {
                    $success_message = "Registration successful! You can now login with your credentials.";
                    // Clear form data after successful registration
                    $_POST = array();
                } else {
                    $error_message = "Registration failed. Please try again.";
                } */


 $result = $user->register($roll_no, $email, $password);

if ($result) {
    $success_message = "Registration successful! You can now login.";
    $_POST = array();
} else {
    $error_message = "Registration failed. Please try again.";
}



            }
        } catch (Exception $e) {
            $error_message = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Food Ordering System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-light">
        <div class="row w-100">
            <div class="col-md-5 offset-md-3">
                <div class="card shadow">
                    <div class="card-header bg-success text-white text-center">
                        <h4>Register</h4>
                        <p class="mb-0">Create New Account</p>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success">
                                <?php echo $success_message; ?>
                                <br><a href="index.php" class="btn btn-sm btn-primary mt-2">Go to Login</a>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="roll_no" class="form-label">Roll Number *</label>
                                        <input type="text" class="form-control" id="roll_no" name="roll_no" 
                                               value="<?php echo isset($_POST['roll_no']) ? htmlspecialchars($_POST['roll_no']) : ''; ?>" required>
                                        <div class="form-text">Enter Roll Number</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                        <div class="form-text">Valid email address</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password *</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <div class="form-text">At least 6 characters</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <div class="form-text">Must match password</div>
                                    </div>
                                </div>
                            </div>
                                                       
                            <div class="d-grid">
                                <button type="submit" name="register" class="btn btn-success btn-lg">
                                    <i class="fas fa-user-plus"></i> Register Account
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account? <a href="index.php" class="btn btn-outline-primary btn-sm">Login here</a></p>
                            <p class="mb-0 mt-2"><a href="index.php" class="text-muted">← Back to Home</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showRoleInfo() {
            const roleSelect = document.getElementById('role');
            const roleInfo = document.getElementById('roleInfo');
            const allRoleInfos = document.querySelectorAll('.role-info');
            
            // Hide all role info cards
            allRoleInfos.forEach(info => info.style.display = 'none');
            
            if (roleSelect.value) {
                roleInfo.style.display = 'block';
                const selectedInfo = document.getElementById(roleSelect.value + 'Info');
                if (selectedInfo) {
                    selectedInfo.style.display = 'block';
                }
            } else {
                roleInfo.style.display = 'none';
            }
        }
        
        // Show role info if role is already selected (after form submission with errors)
        document.addEventListener('DOMContentLoaded', function() {
            showRoleInfo();
        });
    </script>
</body>
</html>
