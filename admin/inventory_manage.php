<?php
include '../config/db.php';
session_start();

// Handle stock update
if (isset($_POST['update_stock'])) {
    $id = intval($_POST['id']);
    $stock = intval($_POST['stock']);
    if ($stock >= 0) {
        $stmt = $conn->prepare("UPDATE menu_items SET stock = ? WHERE id = ?");
        $stmt->bind_param("ii", $stock, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: inventory_manage.php");
        exit();
    }
}

// Fetch all menu items
$result = $conn->query("SELECT * FROM menu_items ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Inventory Management - Coffee Shop</title>
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
      max-width: 1150px;
      width: 100%;
    }
    h1 {
      font-family: 'Playfair Display', serif;
      font-style: italic;
      color: #6F4E37;
      letter-spacing: 2px;
      font-weight: 800;
      font-size: 2.75rem;
      margin-bottom: 25px;
      user-select: none;
    }
    p.description {
      font-style: italic;
      font-size: 1.1rem;
      color: #7a5a44;
      margin-bottom: 40px;
    }
    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 12px;
      user-select: none;
    }
    thead tr {
      background-color: #6F4E37;
      color: #f4eee8;
      font-weight: 700;
    }
    thead th {
      padding: 14px 12px;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      font-family: 'Playfair Display', serif;
      font-size: 1rem;
    }
    tbody tr {
      background-color: white;
      box-shadow: 0 5px 18px rgba(111, 78, 55, 0.1);
      border-radius: 12px;
      transition: background-color 0.3s ease;
    }
    tbody tr:hover {
      background-color: #ecdac8;
      color: #6F4E37;
      cursor: pointer;
    }
    tbody td {
      padding: 15px 12px;
      vertical-align: middle;
      font-size: 1rem;
      color: #4b3621;
    }
    tbody td input[type="number"] {
      width: 90px;
      border-radius: 12px;
      border: 2px solid #d7c4b6;
      padding: 6px 10px;
      font-size: 1rem;
      color: #4b3621;
      transition: border-color 0.3s ease;
    }
    tbody td input[type="number"]:focus {
      outline: none;
      border-color: #6F4E37;
      box-shadow: 0 0 10px rgba(111, 78, 55, 0.6);
    }
    tbody td.low-stock {
      font-weight: 700;
      color: #d9534f;
    }
    tbody td.update-action {
      width: 110px;
      white-space: nowrap;
    }
    button.btn-update {
      background-color: #6F4E37;
      color: white;
      border: none;
      border-radius: 25px;
      padding: 8px 22px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 6px 18px rgba(111,78,55,0.4);
      transition: background-color 0.3s ease;
      user-select: none;
    }
    button.btn-update:hover, button.btn-update:focus {
      background-color: #563B2A;
      outline: none;
      box-shadow: 0 8px 28px rgba(86,59,42,0.7);
    }
    /* Responsive */
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
      tbody tr {
        display: block;
        margin-bottom: 18px;
        box-shadow: none;
        border: 1px solid #d7c4b6;
        border-radius: 12px;
        padding: 16px;
      }
      tbody tr:hover {
        background-color: #e9dfd3;
        color: #6F4E37;
        cursor: default;
      }
      tbody td {
        display: block;
        text-align: right;
        padding-left: 50%;
        position: relative;
        font-size: 0.95rem;
      }
      tbody td::before {
        position: absolute;
        top: 14px;
        left: 18px;
        width: 45%;
        white-space: nowrap;
        font-weight: 700;
        color: #6F4E37;
      }
      tbody td:nth-of-type(1)::before { content: "Name"; }
      tbody td:nth-of-type(2)::before { content: "Category"; }
      tbody td:nth-of-type(3)::before { content: "Stock"; }
      tbody td:nth-of-type(4)::before { content: "Update Stock"; }
      tbody td.low-stock::before { content: "Stock"; }
      tbody td.update-action::before { content: "Action"; }
      tbody td.update-action button {
        width: 100%;
        padding: 10px 0;
      }
    }
    /* Footer */
    footer {
      background-color: #6F4E37;
      color: #f4eee8;
      text-align: center;
      padding: 20px 0;
      font-size: 1rem;
      font-weight: 600;
      font-family: 'Roboto', sans-serif;
      letter-spacing: 0.1em;
      user-select: none;
      box-shadow: 0 -6px 24px rgba(86, 59, 42, 0.55);
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
  </style>
</head>
<body>

<div class="sidebar">
  <h3>Coffee Shop Admin</h3>
  <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="inventory_manage.php" class="active"><i class="bi bi-box-seam"></i> Inventory</a>
  <a href="menu_manage.php"><i class="bi bi-journal-text"></i> Menu Management</a>
  <a href="sales_report.php"><i class="bi bi-graph-up"></i> Sales Reports</a>
  <a href="../user/place_order.php" target="_blank"><i class="bi bi-cart"></i> Place Order</a>
  <a href="../auth/logout.php" style="margin-top: 50px;"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="content">
  <h1>Inventory Management</h1>
  <p class="description">Update stock levels for your coffee menu items below.</p>

  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Category</th>
        <th>Current Stock</th>
        <th>Update Stock</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td data-label="Name"><?php echo htmlspecialchars($row['name']); ?></td>
          <td data-label="Category"><?php echo htmlspecialchars($row['category']); ?></td>
          <td data-label="Current Stock" class="<?php echo ($row['stock'] <= 10) ? 'low-stock' : ''; ?>">
            <?php echo $row['stock']; ?><?php if ($row['stock'] <= 10) echo " (Low!)"; ?>
          </td>
          <td data-label="Update Stock" class="update-action">
            <form method="post" action="inventory_manage.php" novalidate>
              <input type="hidden" name="id" value="<?php echo $row['id']; ?>" />
              <input type="number" name="stock" min="0" value="<?php echo $row['stock']; ?>" required />
              <button type="submit" name="update_stock" class="btn-update" title="Update Stock">Update</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      <?php if ($result->num_rows === 0): ?>
        <tr>
          <td colspan="4" style="text-align:center; padding: 20px; color: #6F4E37;">No menu items found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> Coffee Shop Management &middot;
    <a href="dashboard.php">Dashboard</a> &middot;
    <a href="menu_manage.php">Menu Management</a> &middot;
    <a href="inventory_manage.php">Inventory</a> &middot;
    <a href="sales_report.php">Sales Reports</a>
</footer>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
