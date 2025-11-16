<?php
require_once 'config/database.php';
session_start();
date_default_timezone_set('Asia/Kolkata'); // ✅ ensure same timezone

$message = '';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$db->exec("SET time_zone = '+05:30'"); // ✅ MySQL timezone fix

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $stmt = $db->prepare("SELECT * FROM users WHERE email=? AND reset_code=? AND reset_code_expiry > NOW()");
    $stmt->execute([$email, $code]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['verified_email'] = $email;
        header("Location: reset_password.php");
        exit;
    } else {
        $message = "<div class='alert alert-danger'>Invalid or expired code. Try again.</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container vh-100 d-flex align-items-center justify-content-center">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h4>Verify Code</h4>
            </div>
            <div class="card-body">
                <?php echo $message; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label>Verification Code</label>
                        <input type="text" name="code" class="form-control" placeholder="Enter 6-digit code" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">Verify</button>
                    </div>
                </form>
                <hr>
                <div class="text-center">
                    <a href="forgot_password.php" class="btn btn-outline-secondary btn-sm">Resend Code</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
