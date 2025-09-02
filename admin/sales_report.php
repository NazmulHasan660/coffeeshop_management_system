<?php
include '../config/db.php';
session_start();

// Fetch total sales by day for the last 30 days
$sales_labels = [];
$sales_data = [];
$sales_result = $conn->query("
    SELECT DATE(order_date) AS sale_date, IFNULL(SUM(total),0) AS total_sales
    FROM orders
    WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY sale_date
    ORDER BY sale_date
");
while ($row = $sales_result->fetch_assoc()) {
    $sales_labels[] = $row['sale_date'];
    $sales_data[] = floatval($row['total_sales']);
}

// Fetch top 5 best selling menu items by quantity sold
$top_items_labels = [];
$top_items_data = [];
$top_items_result = $conn->query("
    SELECT mi.name, SUM(oi.quantity) AS qty_sold
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    GROUP BY oi.menu_item_id
    ORDER BY qty_sold DESC
    LIMIT 5
");
while ($row = $top_items_result->fetch_assoc()) {
    $top_items_labels[] = $row['name'];
    $top_items_data[] = intval($row['qty_sold']);
}

// Low stock items count
$low_stock_count = $conn->query("SELECT COUNT(*) FROM menu_items WHERE stock <= 10")->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Sales Report - Coffee Shop Admin</title>
<link href="../css/bootstrap.min.css" rel="stylesheet" />
<script src="../js/Chart.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

<style>
    body {
        background-color: #f4eee8;
        color: #4b3621;
        font-family: 'Roboto', sans-serif;
        min-height: 100vh;
        margin: 0;
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
        user-select: none;
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
    .content {
        margin-left: 240px;
        padding: 40px 60px 100px;
        flex: 1;
        background-color: #fff9f4;
        max-width: 1100px;
        width: 100%;
    }
    h1 {
        font-family: 'Playfair Display', serif;
        font-style: italic;
        font-weight: 800;
        font-size: 2.75rem;
        margin-bottom: 10px;
        color: #6F4E37;
        letter-spacing: 2px;
    }
    .subtitle {
        font-style: italic;
        font-size: 1.2rem;
        margin-bottom: 40px;
        color: #7a5a44;
    }
    .report-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        margin-bottom: 50px;
    }
    .card {
        background: linear-gradient(135deg, #6F4E37, #5B3C28);
        color: white;
        border-radius: 16px;
        padding: 30px 40px;
        flex: 1 1 320px;
        position: relative;
        user-select: none;
        box-shadow: 0 12px 36px rgba(111, 78, 55, 0.35);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
        transform: translateY(-7px);
        box-shadow: 0 18px 52px rgba(86, 59, 42, 0.6);
    }
    .card .icon {
        font-size: 4rem;
        position: absolute;
        top: 18px;
        right: 20px;
        opacity: 0.15;
        user-select: none;
    }
    .card-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 10px;
        font-family: 'Playfair Display', serif;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }
    .card-text {
        font-size: 3.6rem;
        font-weight: 900;
        line-height: 1;
    }
    .card small {
        font-weight: 600;
        opacity: 0.8;
        margin-top: 6px;
    }
    canvas {
        background: white;
        border-radius: 14px;
        padding: 22px 18px 25px;
        box-shadow: 0 8px 26px rgba(111, 78, 55, 0.2);
        max-width: 100%;
        user-select:none;
    }
    .chart-section {
        margin-bottom: 50px;
    }
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
            padding: 18px 0;
            text-align: center;
        }
        .sidebar a {
            display: inline-flex;
            margin: 8px 14px;
            font-size: 1.1rem;
        }
        .content {
            margin-left: 0;
            padding: 25px 20px 120px;
        }
        .report-cards {
            flex-direction: column;
        }
    }
</style>
</head>
<body>

<div class="sidebar">
    <h3>Coffee Shop Admin</h3>
    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="inventory_manage.php"><i class="bi bi-box-seam"></i> Inventory</a>
    <a href="menu_manage.php"><i class="bi bi-journal-text"></i> Menu Management</a>
    <a href="sales_report.php" class="active"><i class="bi bi-graph-up"></i> Sales Reports</a>
    <a href="../user/place_order.php" target="_blank"><i class="bi bi-cart"></i> Place Order</a>
    <a href="../auth/logout.php" style="margin-top: 50px;"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="content">
    <h1>Sales Reports</h1>
    <p class="subtitle">Overview of sales performance and inventory alerts.</p>

    <div class="report-cards">
        <div class="card">
            <div class="icon bi bi-currency-dollar"></div>
            <div class="card-title">Total Sales (30 days)</div>
            <div class="card-text">
                $<?php echo number_format(array_sum($sales_data), 2); ?>
            </div>
            <small>Revenue over the last 30 days</small>
        </div>
        <div class="card">
            <div class="icon bi bi-bar-chart-line-fill"></div>
            <div class="card-title">Top Selling Items</div>
            <div class="card-text">
                <?php echo count($top_items_labels); ?> Items
            </div>
            <small>Based on quantity sold</small>
        </div>
        <div class="card">
            <div class="icon bi bi-exclamation-triangle-fill"></div>
            <div class="card-title">Low Stock Alerts</div>
            <div class="card-text">
                <?php echo $low_stock_count; ?>
            </div>
            <small>Items with stock ≤ 10</small>
        </div>
    </div>

    <div class="chart-section">
        <h3 style="font-family: 'Playfair Display', serif; font-style: italic; color: #6F4E37; margin-bottom: 22px;">Daily Sales (Last 30 Days)</h3>
        <canvas id="dailySalesChart" height="130"></canvas>
    </div>

    <div class="chart-section">
        <h3 style="font-family: 'Playfair Display', serif; font-style: italic; color: #6F4E37; margin-bottom: 22px;">Top 5 Best-Selling Items</h3>
        <canvas id="bestSellersChart" height="140"></canvas>
    </div>
</div>

<script>
const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
const dailySalesChart = new Chart(dailySalesCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($sales_labels); ?>,
        datasets: [{
            label: 'Total Sales ($)',
            data: <?php echo json_encode($sales_data); ?>,
            fill: true,
            borderColor: '#6F4E37',
            backgroundColor: 'rgba(111, 78, 55, 0.3)',
            tension: 0.3,
            pointBackgroundColor: '#6F4E37',
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: {
                ticks: {color: '#4b3621', maxRotation: 30, minRotation: 30},
                grid: {color: '#e0d6cd'},
            },
            y: {
                ticks: {color: '#4b3621'},
                grid: {color: '#e0d6cd'},
                beginAtZero: true,
            }
        },
        plugins: {
            legend: {labels: {color: '#563B2A', font: {weight: '600'}}}
        }
    }
});

const bestSellersCtx = document.getElementById('bestSellersChart').getContext('2d');
const bestSellersChart = new Chart(bestSellersCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($top_items_labels); ?>,
        datasets: [{
            label: 'Quantity Sold',
            data: <?php echo json_encode($top_items_data); ?>,
            backgroundColor: '#6F4E37',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: {ticks: {color: '#4b3621', font: {weight: '600'}}, grid: {display: false}},
            y: {beginAtZero: true, ticks: {color: '#4b3621'}, grid: {color: '#e0d6cd'}},
        },
        plugins: {legend: {display: false}},
    }
});
</script>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
