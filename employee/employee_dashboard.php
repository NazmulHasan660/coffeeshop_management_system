<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../auth/login.php");
    exit;
}

$employee_id = $_SESSION['user_id'];

$sql = "
SELECT o.order_id, o.order_date, o.status, u.name as customer_name
FROM orders o
LEFT JOIN user u ON o.user_id = u.user_id
WHERE o.employee_id = ?
ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Employee Dashboard - Coffee Shop</title>
<link href="../css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
<style>
    body {
        background-color: #f4eee8;
        color: #4b3621;
        font-family: 'Roboto', sans-serif;
        margin: 0;
    }
    .content {
        max-width: 900px;
        margin: 50px auto;
        background: #fff9f4;
        padding: 30px;
        box-shadow: 0 0 20px rgba(111, 78, 55, 0.3);
        border-radius: 12px;
    }
    h1 {
        font-family: 'Playfair Display', serif;
        font-style: italic;
        font-weight: 700;
        font-size: 2.8rem;
        margin-bottom: 30px;
        color: #6F4E37;
        letter-spacing: 2px;
        text-shadow: 1px 1px 1px rgba(111, 78, 55, 0.25);
        text-align: center;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    th, td {
        padding: 12px;
        border: 1px solid #a9746e;
        text-align: left;
    }
    th {
        background-color: #d7c4aa;
    }
    tr:nth-child(even) {
        background-color: #ede3d1;
    }
    .btn-status {
        background-color: #6F4E37;
        color: #f4eee8;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 700;
        transition: background-color 0.3s;
    }
    .btn-status:hover {
        background-color: #563B2A;
    }
    form {
        display: inline;
    }
</style>
<script>
function updateStatus(orderId) {
    const select = document.getElementById('status-select-' + orderId);
    const newStatus = select.value;
    if(confirm(`Change status of order ${orderId} to "${newStatus}"?`)) {
        window.location.href = `employee_dashboard.php?update_status=1&order_id=${orderId}&status=${encodeURIComponent(newStatus)}`;
    } else {
        select.value = select.getAttribute('data-current');
    }
}
</script>
</head>
<body>
<div class="content">
    <h1>Welcome Employee! Your Orders</h1>
    <?php
    // Handle status update via GET (you may want to move this to POST with a form)
    if (isset($_GET['update_status']) && $_GET['update_status'] == '1') {
        $ord_id = intval($_GET['order_id']);
        $new_status = $_GET['status'];

        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE order_id=? AND employee_id=?");
        $stmt->bind_param("sii", $new_status, $ord_id, $employee_id);
        if ($stmt->execute()) {
            echo "<p style='color:green;'>Order $ord_id status updated to $new_status.</p>";
        } else {
            echo "<p style='color:red;'>Failed to update order status: " . htmlspecialchars($conn->error) . "</p>";
        }
        $stmt->close();
    }
    ?>
    <?php if (count($orders) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Order Date</th>
                <th>Status</th>
                <th>Change Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= $order['order_id'] ?></td>
                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                <td><?= $order['order_date'] ?></td>
                <td><?= ucfirst($order['status']) ?></td>
                <td>
                    <select id="status-select-<?= $order['order_id'] ?>" data-current="<?= htmlspecialchars($order['status']) ?>" onchange="updateStatus(<?= $order['order_id'] ?>)">
                        <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="in work" <?= $order['status'] == 'in work' ? 'selected' : '' ?>>In Work</option>
                        <option value="ready" <?= $order['status'] == 'ready' ? 'selected' : '' ?>>Ready</option>
                        <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No orders assigned to you yet.</p>
    <?php endif; ?>
</div>
</body>
</html>
