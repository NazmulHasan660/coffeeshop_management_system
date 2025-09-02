<?php
// Start session and include DB config
include '../config/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Fetch dashboard data safely
$menu_count = 0;
$low_stock_count = 0;
$total_sales_today = 0.0;

$result = $conn->query("SELECT COUNT(*) AS cnt FROM menu_items");
if ($result) {
    $row = $result->fetch_assoc();
    $menu_count = (int)$row['cnt'];
}

$result = $conn->query("SELECT COUNT(*) AS cnt FROM menu_items WHERE stock <= 10");
if ($result) {
    $row = $result->fetch_assoc();
    $low_stock_count = (int)$row['cnt'];
}

$result = $conn->query("SELECT IFNULL(SUM(final_amount),0) AS total_sales FROM orders WHERE DATE(order_date) = CURDATE()");
if ($result) {
    $row = $result->fetch_assoc();
    $total_sales_today = (float)$row['total_sales'];
}

// Fetch orders assigned to employees with status info
$order_sql = "
    SELECT o.order_id, u.name AS customer_name, o.order_date, o.status, e.name AS employee_name
    FROM orders o
    LEFT JOIN user u ON o.user_id = u.user_id
    LEFT JOIN employee e ON o.employee_id = e.employee_id
    ORDER BY o.order_date DESC
";
$order_results = $conn->query($order_sql);

// Handle order status updates from employees
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['order_status'];
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard - Coffee Shop</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f4eee8;
            color: #4b3621;
            font-family: 'Roboto', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .sidebar {
            height: 100vh;
            background-color: #6F4E37;
            color: white;
            padding-top: 25px;
            position: fixed;
            width: 240px;
            font-weight: 600;
            box-shadow: 4px 0 14px rgba(111, 78, 55, 0.3);
        }
        .sidebar h3 {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 1.8rem;
            letter-spacing: 3px;
            text-align: center;
            margin-bottom: 40px;
        }
        .sidebar a {
            color: #f4eee8;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 14px 25px;
            margin-bottom: 10px;
            font-size: 1.15rem;
            border-radius: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-weight: 600;
        }
        .sidebar a .bi {
            font-size: 1.3rem;
            margin-right: 12px;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #563B2A;
            transform: scale(1.04);
            font-weight: 700;
        }
        .sidebar a.active {
            background-color: #563B2A;
            font-weight: 700;
        }
        .content {
            margin-left: 240px;
            padding: 40px 50px 100px;
            flex: 1;
            background-color: #fff9f4;
            overflow-x: auto;
        }
        h1 {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-weight: 700;
            font-size: 3rem;
            margin-bottom: 15px;
            color: #6F4E37;
            letter-spacing: 2px;
            text-shadow: 1px 1px 1px rgba(111, 78, 55, 0.25);
        }
        p.subtitle {
            font-style: italic;
            font-size: 1.2rem;
            margin-bottom: 40px;
            color: #7a5a44;
            letter-spacing: 1px;
        }
        .dashboard-cards {
            display: flex;
            gap: 28px;
            flex-wrap: wrap;
            margin-bottom: 55px;
        }
        .card {
            background: linear-gradient(135deg, #6F4E37 0%, #5B3C28 100%);
            color: white;
            border-radius: 18px;
            padding: 36px 42px;
            box-shadow: 0 12px 36px rgba(111, 78, 55, 0.3);
            flex: 1 1 300px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(111, 78, 55, 0.5);
        }
        .card .icon {
            font-size: 4rem;
            position: absolute;
            top: 18px;
            right: 20px;
            opacity: 0.12;
            user-select: none;
        }
        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.7rem;
            font-weight: 800;
            margin-bottom: 18px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }
        .card-text {
            font-size: 3.8rem;
            font-weight: 900;
            line-height: 1;
        }
        .card small {
            font-weight: 600;
            opacity: 0.85;
            margin-top: 8px;
        }
        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 22px;
            margin-bottom: 50px;
        }
        .btn-coffee, .btn-secondary-coffee {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 42px;
            font-size: 1.25rem;
            font-weight: 700;
            border-radius: 30px;
            cursor: pointer;
            box-shadow: 0 9px 28px rgba(111, 78, 55, 0.4);
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            user-select: none;
        }
        .btn-coffee {
            background-color: #6F4E37;
            color: #f4eee8;
            border: none;
        }
        .btn-coffee:hover, .btn-coffee:focus {
            background-color: #563B2A;
            transform: scale(1.07);
            box-shadow: 0 12px 36px rgba(86, 59, 42, 0.8);
            outline: none;
        }
        .btn-secondary-coffee {
            background-color: #f4eee8;
            color: #6F4E37;
            border: 2px solid #6F4E37;
        }
        .btn-secondary-coffee:hover, .btn-secondary-coffee:focus {
            background-color: #d7c4b6;
            color: #4b3621;
            box-shadow: 0 8px 25px rgba(111, 78, 55, 0.3);
            transform: scale(1.04);
            outline: none;
        }
        .btn-coffee .bi, .btn-secondary-coffee .bi {
            font-size: 1.5rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #a9746e;
        }
        th, td {
            padding: 12px;
            text-align: left;
            color: #4b3621;
        }
        th {
            background-color: #d7c4aa;
        }
        tr:nth-child(even) {
            background-color: #ede3d1;
        }
        select, input[type="submit"] {
            padding: 7px 12px;
            border-radius: 6px;
            border: 1px solid #8b6b4a;
            font-weight: 600;
            cursor: pointer;
        }
        footer {
            background-color: #6F4E37;
            color: #f4eee8;
            text-align: center;
            padding: 20px 0;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Roboto', sans-serif;
            letter-spacing: 0.1em;
            box-shadow: 0 -6px 24px rgba(86, 59, 42, 0.55);
            user-select: none;
            margin-top: 40px;
        }
        footer a {
            color: #f4eee8;
            text-decoration: underline;
            margin: 0 8px;
            transition: color 0.3s ease;
        }
        footer a:hover, footer a:focus {
            color: #d7c4b6;
            outline: none;
        }
        @media (max-width: 992px) {
            .dashboard-cards { flex-direction: column; gap: 38px; }
            .content { padding: 30px 35px 120px; }
        }
        @media (max-width: 576px) {
            .sidebar { width: 100%; height: auto; position: relative; padding: 18px 0; text-align: center; }
            .sidebar a { display: inline-flex; margin: 6px 12px; font-size: 1.1rem;}
            .content { margin-left: 0; padding: 25px 20px 130px; }
            .btn-coffee, .btn-secondary-coffee { font-size: 1.1rem; padding: 12px 27px; }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <h3>Coffee Shop Admin</h3>
    <a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="inventory_manage.php"><i class="bi bi-box-seam"></i> Inventory</a>
    <a href="menu_manage.php"><i class="bi bi-journal-text"></i> Menu Management</a>
    <a href="sales_report.php"><i class="bi bi-graph-up"></i> Sales Reports</a>
    <a href="employee.php"><i class="bi bi-people"></i> Employee Management</a>
    <a href="../user/place_order.php" target="_blank"><i class="bi bi-cart"></i> Place Order</a>
    <a href="../auth/logout.php" style="margin-top: 50px;"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>
<div class="content">
    <h1>Dashboard</h1>
    <p class="subtitle">Welcome, Admin! Here's the current overview.</p>
    <div class="dashboard-cards">
        <div class="card">
            <div class="icon bi bi-card-list"></div>
            <div class="card-title">Menu Items</div>
            <div class="card-text"><?php echo $menu_count; ?></div>
            <small>Items available in the menu</small>
        </div>
        <div class="card">
            <div class="icon bi bi-exclamation-triangle"></div>
            <div class="card-title">Low Stock Alerts</div>
            <div class="card-text"><?php echo $low_stock_count; ?></div>
            <small>Items with stock ≤ 10</small>
        </div>
        <div class="card">
            <div class="icon bi bi-currency-dollar"></div>
            <div class="card-title">Total Sales Today</div>
            <div class="card-text">$<?php echo number_format($total_sales_today, 2); ?></div>
            <small>Revenue generated today</small>
        </div>
    </div>

    <!-- Employee Orders Section -->
    <h2>Orders Assigned to Employees</h2>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Employee</th>
                <th>Order Date</th>
                <th>Status</th>
                <th>Update Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($order_results && $order_results->num_rows > 0): ?>
            <?php while ($order = $order_results->fetch_assoc()): ?>
                <tr>
                    <td><?= $order['order_id'] ?></td>
                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                    <td><?= htmlspecialchars($order['employee_name'] ?? 'Unassigned') ?></td>
                    <td><?= $order['order_date'] ?></td>
                    <td><?= ucfirst($order['status']) ?></td>
                    <td>
                        <form method="post" style="margin: 0;">
                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                            <select name="order_status" required>
                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="in work" <?= $order['status'] === 'in work' ? 'selected' : '' ?>>In Work</option>
                                <option value="ready" <?= $order['status'] === 'ready' ? 'selected' : '' ?>>Ready</option>
                                <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                            <input type="submit" name="update_order_status" value="Update" class="btn-coffee" />
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No orders found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<footer>
    &copy; <?php echo date('Y'); ?> Coffee Shop Management &middot;
    <a href="dashboard.php">Dashboard</a> &middot;
    <a href="menu_manage.php">Menu</a> &middot;
    <a href="inventory_manage.php">Inventory</a> &middot;
    <a href="sales_report.php">Sales</a> &middot;
    <a href="employee.php">Employees</a>
</footer>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
