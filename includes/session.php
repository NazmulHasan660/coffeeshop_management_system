<?php
session_start();

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}

function check_admin() {
    check_login();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: ../user/dashboard.php");
        exit();
    }
}

function check_user() {
    check_login();
    if ($_SESSION['role'] !== 'user') {
        header("Location: ../admin/dashboard.php");
        exit();
    }
}
?>
