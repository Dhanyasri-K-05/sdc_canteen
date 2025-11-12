<?php
session_start();

$timeout_duration = 600; // 10 minutes

if (isset($_SESSION['LAST_ACTIVITY']) && 
    (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    
    session_unset();
    session_destroy();
    echo "expired"; 
    exit();
}

echo "active";
