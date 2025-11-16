<?php
require_once '../config/session.php';
require_once(__DIR__ . '/../config/database.php');
require_once '../classes/User.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Fetch lists
$admins = $user->getUsersByRole('admin');
$staffs = $user->getUsersByRole('staff');
$users = $user->getUsersByRole('user');

$success_message = '';
$error_message = '';

// detect current file
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <title>Manage Users - Admin</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }

        .navbar {
            width: 100%;
            background: #1a73e8;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar .logo {
            color: #fff;
            font-size: 22px;
            font-weight: bold;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 25px;
        }

        .nav-links li a {
            color: white;
            text-decoration: none;
            padding: 10px 14px;
            font-weight: 500;
            transition: 0.3s;
        }

        .nav-links li a:hover {
            background: #0f5cd7;
            border-radius: 6px;
        }

        .logout { background: #d9534f; padding: 10px 14px; border-radius: 6px; }
        .logout:hover { background: #b94744 !important; }

        .page-container {
            padding: 40px;
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="logo">SDC Canteen Admin</div>
        <ul class="nav-links">

           <?php if ($current_page !== 'dashboard.php') : ?>
                <li><a href="dashboard.php">Dashboard</a></li>
            <?php endif; ?>

            <?php if ($current_page !== 'user_manage.php') : ?>
                <li><a href="user_manage.php">Manage Users</a></li>
            <?php endif; ?>

            <?php if ($current_page !== 'admin_manage.php') : ?>
                <li><a href="admin_manage.php">Manage Admins</a></li>
            <?php endif; ?>

             

            <?php if ($current_page !== 'staff_manage.php') : ?>
                <li><a href="staff_manage.php">Manage Staff</a></li>
            <?php endif; ?>

            <li><a href="logout.php" class="logout">Logout</a></li>
        </ul>
    </nav>

    <div class="page-container">
        <h1 class="mb-4">Manage Users</h1>

        <!-- Your user table or content can go here -->
        <!-- Example placeholder area -->
        <div class="alert alert-info">User management interface goes here.</div>

    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
