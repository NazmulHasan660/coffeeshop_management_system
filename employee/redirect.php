<?php
session_start();
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'employee') {
    header("Location: employee_dashboard.php");
    exit;
} else {
    header("Location: ../auth/login.php");
    exit;
}
