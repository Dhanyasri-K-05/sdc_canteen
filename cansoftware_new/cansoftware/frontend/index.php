<?php session_start(); ?>
<?php if (isset($_SESSION['error'])): ?>
  <div class="error-message"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Report Generation System</title>
  <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
  <div class="login-container">
      <a class="btn btn-outline-light btn-sm me-2" href="javascript:history.back()">
        <i class="fas fa-arrow-left"></i> Back
    </a>
      <a class="btn btn-outline-light btn-sm" href="../../../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
    <h2>Report Generation System</h2>
    <form action="../backend/login.php" method="post">
      <label for="role">Login As</label>
      <select name="role" required>
        <option value="">Select Role</option>
        <option value="principal">Principal</option>
        <option value="canteen">Canteen</option>
        <option value="Admin">Admin</option>
      </select>

      <label for="username">Username</label>
      <input type="text" name="username" required>

      <label for="password">Password</label>
      <input type="password" name="password" required>

      <button type="submit">Login</button>
      <?php
        if (isset($_SESSION['error'])) {
          echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
          unset($_SESSION['error']);
        }
      ?>
    </form>
  </div>
</body>
</html>
