<?php session_start(); if ($_SESSION['role'] !== 'principal') header('Location: index.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Principal Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="dashboard">
    <h2>Principal Dashboard</h2>
    <p>Welcome, Principal!</p>
    <!-- Add your principal functions here -->
  </div>
</body>
</html>
