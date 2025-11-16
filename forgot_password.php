<?php
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
date_default_timezone_set('Asia/Kolkata'); // ✅ Fix timezone mismatch

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = "<div class='alert alert-danger'>Please enter your registered email.</div>";
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $db->exec("SET time_zone = '+05:30'"); // ✅ MySQL time fix

        // Check if email exists
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate code + expiry
            $code = rand(100000, 999999);
            $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

            // Store in DB
            $update = $db->prepare("UPDATE users SET reset_code=?, reset_code_expiry=? WHERE email=?");
            $update->execute([$code, $expiry, $email]);

            // Send email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'imashwinm777@gmail.com'; // ✅ your Gmail
                $mail->Password = 'tzolfuseemgwefpv';       // ✅ your app password (no spaces)
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('imashwinm777@gmail.com', 'PSG iTech Canteen System');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = "Password Reset Code - PSG iTech Canteen System";
                $mail->Body = "
                    <div style='font-family:Arial,sans-serif;padding:20px;background:#f9f9f9;border-radius:8px;'>
                        <h2 style='color:#333;'>Password Reset Request</h2>
                        <p>Hello <strong>{$user['roll_no']}</strong>,</p>
                        <p>Your password reset code is:</p>
                        <h3 style='color:#007bff;'>$code</h3>
                        <p>This code will expire in <strong>10 minutes</strong>.</p>
                        <hr>
                        <p>If you did not request this, please ignore this email.</p>
                        <p>Regards,<br><strong>PSG iTech Canteen System</strong></p>
                    </div>
                ";

                $mail->send();
                $_SESSION['reset_email'] = $email;

                header("Location: verify_code.php");
                exit;
            } catch (Exception $e) {
                $message = "<div class='alert alert-danger'>Email failed to send: {$mail->ErrorInfo}</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>No account found with that email.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container vh-100 d-flex align-items-center justify-content-center">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h4>Forgot Password</h4>
            </div>
            <div class="card-body">
                <?php echo $message; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter your registered email" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Send Reset Code</button>
                    </div>
                </form>
                <hr>
                <div class="text-center">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
