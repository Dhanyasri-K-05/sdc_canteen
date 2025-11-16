<?php
require_once 'config/database.php';
session_start();
date_default_timezone_set('Asia/Kolkata');

$message = '';

if (!isset($_SESSION['verified_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$email = $_SESSION['verified_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $message = "<div class='alert alert-danger'>Passwords do not match.</div>";
    } elseif (strlen($new) < 6) {
        $message = "<div class='alert alert-danger'>Password must be at least 6 characters long.</div>";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password=?, reset_code=NULL, reset_code_expiry=NULL WHERE email=?");
        $stmt->execute([$hashed, $email]);

        // ✅ Destroy session so reset flow ends cleanly
        session_destroy();

        // ✅ Display success message and redirect
        echo "
        <script>
            alert('✅ Password updated successfully!');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1500);
        </script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container vh-100 d-flex align-items-center justify-content-center'>
    <div class='col-md-4'>
        <div class='card shadow'>
            <div class='card-header bg-primary text-white text-center'>
                <h4>Set New Password</h4>
            </div>
            <div class='card-body'>
                <?php echo $message; ?>
                <form method='POST'>
                    <div class='mb-3'>
                        <label>New Password</label>
                        <input type='password' name='new_password' class='form-control' required>
                    </div>
                    <div class='mb-3'>
                        <label>Confirm Password</label>
                        <input type='password' name='confirm_password' class='form-control' required>
                    </div>
                    <div class='d-grid'>
                        <button type='submit' class='btn btn-success'>Update Password</button>
                    </div>
                </form>
                <hr>
                <div class='text-center'>
                    <a href='index.php' class='btn btn-outline-secondary btn-sm'>Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
