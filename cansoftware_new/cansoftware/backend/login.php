<?php
session_start();
require 'db.php';
$role = $_POST['role'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$query = "SELECT * FROM users WHERE role = :role AND username = :username AND password = :password";
$stmt = $pdo->prepare($query);
$stmt->execute([
    'role' => $role,
    'username' => $username,
    'password' => $password
]);
$user = $stmt->fetch();

if ($user) {
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;

    if ($role === 'principal') {
        header("Location: dashboard-principal.php");
    } elseif ($role === 'canteen') {
        header("Location: dashboard-canteen.php");
    }
    elseif($role==='Admin'){
        header("Location:../backend/admin/admin-dashboard.php");
    }
    exit();
} else {
    $_SESSION['error'] = "Username or password is incorrect.";
    header("Location:../frontend/index.php");
    exit();
}
