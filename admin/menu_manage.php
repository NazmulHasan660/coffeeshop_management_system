<?php
include '../config/db.php';
session_start();

// Handle Soft Delete Menu Item at the top to avoid header issues
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);

    // Soft delete: mark is_deleted = 1 instead of removing the row
    $stmt = $conn->prepare("UPDATE menu_items SET is_deleted = 1 WHERE id = ?");
    if (!$stmt) {
        die("Prepare statement failed: " . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        die("Soft delete execution failed: " . htmlspecialchars($stmt->error));
    }
    $stmt->close();

    // Redirect after soft delete
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Add New Menu Item
if (isset($_POST['add_menu'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);

    if ($name && $desc && $price >= 0 && $category) {
        $stmt = $conn->prepare("INSERT INTO menu_items (name, description, price, category, available, is_deleted) VALUES (?, ?, ?, ?, 1, 0)");
        $stmt->bind_param("ssds", $name, $desc, $price, $category);
        $stmt->execute();
        $stmt->close();

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Toggle Availability
if (isset($_GET['toggle_id'])) {
    $toggle_id = intval($_GET['toggle_id']);

    $stmt = $conn->prepare("SELECT available FROM menu_items WHERE id = ? AND is_deleted = 0");
    $stmt->bind_param("i", $toggle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();

    if ($item) {
        $new_available = $item['available'] ? 0 : 1;
        $stmt = $conn->prepare("UPDATE menu_items SET available = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_available, $toggle_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Edit Menu Item - load existing data (only if not deleted)
$editing_item = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ? AND is_deleted = 0");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $editing_item = $result_edit->fetch_assoc();
    $stmt->close();
}

// Handle Update Menu Item
if (isset($_POST['update_menu'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);

    if ($name && $desc && $price >= 0 && $category) {
        $stmt = $conn->prepare("UPDATE menu_items SET name=?, description=?, price=?, category=? WHERE id=? AND is_deleted = 0");
        $stmt->bind_param("ssdsi", $name, $desc, $price, $category, $id);
        $stmt->execute();
        $stmt->close();

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch all non-deleted menu items ordered by name ascending
$result = $conn->query("SELECT * FROM menu_items WHERE is_deleted = 0 ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Menu Management - Coffee Shop</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        /* Your existing CSS here (unchanged) */
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
        .sidebar a:hover,
        .sidebar a.active {
            background-color: #563B2A;
            transform: scale(1.04);
            font-weight: 700;
        }
        .content {
            margin-left: 240px;
            padding: 40px 60px 100px;
            flex: 1;
            background-color: #fff9f4;
            max-width: 1200px;
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
        }
        form.menu-form {
            background: white;
            padding: 28px 36px;
            border-radius: 18px;
            box-shadow: 0 10px 36px rgba(111, 78, 55, 0.15);
            margin-bottom: 45px;
            user-select: none;
        }
        form.menu-form .form-control {
            border-radius: 12px;
            border: 2px solid #d7c4b6;
            padding: 10px 15px;
            font-size: 1rem;
            color: #4b3621;
            transition: border-color 0.3s ease;
        }
        form.menu-form .form-control:focus {
            outline: none;
            border-color: #6F4E37;
            box-shadow: 0 0 10px rgba(111, 78, 55, 0.35);
        }
        form.menu-form .row > div {
            margin-bottom: 20px;
        }
        form.menu-form button {
            background-color: #6F4E37;
            color: #f4eee8;
            border: none;
            border-radius: 30px;
            font-weight: 700;
            font-size: 1.15rem;
            padding: 12px 42px;
            cursor: pointer;
            box-shadow: 0 8px 28px rgba(111, 78, 55, 0.4);
            transition: background-color 0.3s ease;
            user-select: none;
        }
        form.menu-form button:hover,
        form.menu-form button:focus {
            background-color: #563B2A;
            outline: none;
            box-shadow: 0 10px 34px rgba(86, 59, 42, 0.7);
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
        tbody tr td.actions {
            width: 200px;
            white-space: nowrap;
        }
        .btn-success {
            background-color: #28a745;
            border: none;
            color: white;
            border-radius: 20px;
            padding: 6px 14px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
            display: inline-block;
            user-select: none;
        }
        .btn-success:hover,
        .btn-success:focus {
            background-color: #1e7e34;
            outline: none;
        }
        .btn-danger {
            background-color: #dc3545;
            border: none;
            color: white;
            border-radius: 20px;
            padding: 6px 14px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
            display: inline-block;
            user-select: none;
        }
        .btn-danger:hover,
        .btn-danger:focus {
            background-color: #bd2130;
            outline: none;
        }
        .btn-edit,
        .btn-delete {
            border: none;
            border-radius: 20px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            user-select: none;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-edit {
            background-color: #6F4E37;
            color: white;
            margin-right: 10px;
            box-shadow: 0 6px 20px rgba(111, 78, 55, 0.4);
        }
        .btn-edit:hover,
        .btn-edit:focus {
            background-color: #563B2A;
            outline: none;
            box-shadow: 0 8px 24px rgba(86, 59, 42, 0.7);
        }
        .btn-delete {
            background-color: #d9534f;
            color: white;
            box-shadow: 0 6px 20px rgba(217, 83, 79, 0.4);
        }
        .btn-delete:hover,
        .btn-delete:focus {
            background-color: #c9302c;
            outline: none;
            box-shadow: 0 8px 24px rgba(201, 48, 44, 0.7);
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
                padding: 25px 30px 100px;
            }
            form.menu-form .row > div {
                margin-bottom: 15px;
            }
            tbody tr {
                display: block;
                margin-bottom: 20px;
                box-shadow: none;
                border: 1px solid #d7c4b6;
                border-radius: 12px;
                padding: 15px;
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
                content: attr(data-label);
                text-transform: capitalize;
            }
            tbody td.actions {
                text-align: center;
                padding: 10px 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>Coffee Shop Admin</h3>
    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="inventory_manage.php"><i class="bi bi-box-seam"></i> Inventory</a>
    <a href="menu_manage.php" class="active"><i class="bi bi-journal-text"></i> Menu Management</a>
    <a href="sales_report.php"><i class="bi bi-graph-up"></i> Sales Reports</a>
    <a href="../user/place_order.php" target="_blank"><i class="bi bi-cart"></i> Place Order</a>
    <a href="../auth/logout.php" style="margin-top: 50px;"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="content">
    <h1>Menu Management</h1>

    <?php if ($editing_item): ?>
        <form class="menu-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" novalidate>
            <input type="hidden" name="id" value="<?php echo $editing_item['id']; ?>" />
            <div class="row g-4">
                <div class="col-md-3 col-12">
                    <input type="text" name="name" class="form-control" placeholder="Name" value="<?php echo htmlspecialchars($editing_item['name']); ?>" required />
                </div>
                <div class="col-md-4 col-12">
                    <input type="text" name="description" class="form-control" placeholder="Description" value="<?php echo htmlspecialchars($editing_item['description']); ?>" required />
                </div>
                <div class="col-md-2 col-12">
                    <input type="number" min="0" step="0.01" name="price" class="form-control" placeholder="Price ($)" value="<?php echo htmlspecialchars($editing_item['price']); ?>" required />
                </div>
                <div class="col-md-2 col-12">
                    <input type="text" name="category" class="form-control" placeholder="Category" value="<?php echo htmlspecialchars($editing_item['category']); ?>" required />
                </div>
                <div class="col-md-1 col-12 d-grid">
                    <button type="submit" name="update_menu">Update</button>
                </div>
            </div>
        </form>
    <?php else: ?>
        <form class="menu-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" novalidate>
            <div class="row g-4">
                <div class="col-md-3 col-12">
                    <input type="text" name="name" class="form-control" placeholder="Name" required />
                </div>
                <div class="col-md-4 col-12">
                    <input type="text" name="description" class="form-control" placeholder="Description" required />
                </div>
                <div class="col-md-2 col-12">
                    <input type="number" min="0" step="0.01" name="price" class="form-control" placeholder="Price ($)" required />
                </div>
                <div class="col-md-2 col-12">
                    <input type="text" name="category" class="form-control" placeholder="Category" required />
                </div>
                <div class="col-md-1 col-12 d-grid">
                    <button type="submit" name="add_menu">Add</button>
                </div>
            </div>
        </form>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Price ($)</th>
                <th>Category</th>
                <th>Stock</th>
                <th>Availability</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="Name"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td data-label="Description"><?php echo htmlspecialchars($row['description']); ?></td>
                    <td data-label="Price ($)"><?php echo number_format($row['price'], 2); ?></td>
                    <td data-label="Category"><?php echo htmlspecialchars($row['category']); ?></td>
                    <td data-label="Stock"><?php echo intval($row['stock']); ?></td>
                    <td data-label="Availability" style="text-align:center;">
                        <?php if ($row['available']): ?>
                            <a href="?toggle_id=<?php echo $row['id']; ?>"
                               title="Set as Unavailable"
                               class="btn-success">
                                Available
                            </a>
                        <?php else: ?>
                            <a href="?toggle_id=<?php echo $row['id']; ?>"
                               title="Set as Available"
                               class="btn-danger">
                                Unavailable
                            </a>
                        <?php endif; ?>
                    </td>
                    <td data-label="Actions" class="actions">
                        <a href="?edit_id=<?php echo $row['id']; ?>" class="btn-edit" title="Edit"><i class="bi bi-pencil-square"></i> Edit</a>
                        <a href="?delete_id=<?php echo $row['id']; ?>" class="btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this menu item?');"><i class="bi bi-trash"></i> Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if ($result->num_rows === 0): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding: 20px; color: #6F4E37;">No menu items found.</td>
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
