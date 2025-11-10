<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

/* function requireRole($required_role) {
    requireLogin();
    
    if ($_SESSION['role'] !== $required_role) {
        header('Location: ../unauthorized.php');
        exit();
    }
} */



    function requireRole(...$allowedRoles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        // Redirect to unauthorized page or login
        header('Location: ../unauthorized.php');
        exit();
    }
}


function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

function logout() {
    session_destroy();
    header('Location: ../index.php');
    exit();
}

// Update wallet balance in session
function updateSessionWalletBalance($new_balance) {
    if (isLoggedIn()) {
        $_SESSION['wallet_balance'] = $new_balance;
    }
}
?>
