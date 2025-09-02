<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $employee_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND employee_id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sii", $status, $order_id, $employee_id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: employee_dashboard.php?msg=Status updated");
        exit;
    } else {
        die("Failed to update order status: " . $conn->error);
    }
} else {
    header("Location: employee_dashboard.php");
    exit;
}
