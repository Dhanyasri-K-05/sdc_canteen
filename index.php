<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'classes/User.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'cashier':
            header('Location: cashier/dashboard.php');
            break;
        case 'user':
            header('Location: user/dashboard.php');
            break;
        case 'staff':
            header('Location: user/dashboard.php');
            break;
        default:
            header('Location: user/dashboard.php');
    }
    exit();
}

$error_message = '';
$success_message = '';

if ($_POST && isset($_POST['login'])) {
    $roll_no = trim($_POST['roll_no']);
    $password = $_POST['password'];
    
    if (empty($roll_no) || empty($password)) {
        $error_message = "Please fill in all fields";
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $user = new User($db);
            
            $user_data = $user->login($roll_no, $password);
            
            if ($user_data) {
                // Set session variables

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user_data['id'];
                $_SESSION['roll_no'] = $user_data['roll_no'];
                $_SESSION['email'] = $user_data['email'];
                $_SESSION['role'] = $user_data['role'];
                $_SESSION['wallet_balance'] = $user_data['wallet_balance'];
                // header('Location: user/dashboard.php');
                // Redirect based on role
                switch ($user_data['role']) {
                    case 'admin':
                        header('Location: admin/dashboard.php');
                        break;
                    case 'cashier':
                        header('Location: cashier/dashboard.php');
                        break;
                    case 'user':
                        header('Location: user/dashboard.php');
                        break;
                    case 'staff':
                        header('Location: user/dashboard.php');
                        break;
                    default:
                        header('Location: user/dashboard.php');
                }
                exit();
            } else {
                $error_message = "Invalid Roll Number/Email or Password";
            }
        } catch (Exception $e) {
            $error_message = "Login failed: " . $e->getMessage();
        }
    }
}

// Check for success message from registration
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success_message = "Registration successful! Please login with your credentials.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Food Ordering System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/index.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center bg-light">
        <div class="row w-100">
            <div class="col-md-4 offset-md-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Login</h4>
                        <p class="mb-0">PSG iTech Canteen System</p>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="roll_no" class="form-label">Roll Number or Email</label>
                                <input type="text" class="form-control" id="roll_no" name="roll_no" 
                                       value="<?php echo isset($_POST['roll_no']) ? htmlspecialchars($_POST['roll_no']) : ''; ?>" 
                                       placeholder="Enter your Roll Number or email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="login" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="text-center">
                            <p class="mb-2">Don't have an account? 
                                <a href="register.php" class="btn btn-outline-success btn-sm">Register here</a>
                            </p>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
