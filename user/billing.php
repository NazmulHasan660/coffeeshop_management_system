<?php
include '../config/db.php';
session_start();

$tax_rate = 0.10;        // 10% tax
$discount_rate = 0.05;   // 5% discount example

$quantities   = $_POST['quantity'] ?? [];
$sizes        = $_POST['size'] ?? [];
$temperatures = $_POST['temperature'] ?? [];

if (empty($quantities) || !is_array($quantities)) {
    die("Invalid order data. Please <a href='place_order.php'>go back</a> and select items.");
}

// Collect items with quantity > 0; validate sizes and temperatures
$order_items = [];
foreach ($quantities as $menu_item_id => $qty) {
    $qty = intval($qty);
    if ($qty > 0) {
        $size = $sizes[$menu_item_id] ?? 'Medium';
        $temperature = $temperatures[$menu_item_id] ?? 'Hot';

        $allowed_sizes = ['Small', 'Medium', 'Large'];
        $allowed_temperatures = ['Hot', 'Cold'];

        if (!in_array($size, $allowed_sizes)) $size = 'Medium';
        if (!in_array($temperature, $allowed_temperatures)) $temperature = 'Hot';

        $order_items[$menu_item_id] = [
            'quantity' => $qty,
            'size' => $size,
            'temperature' => $temperature
        ];
    }
}

if (empty($order_items)) {
    die("No items selected. Please <a href='place_order.php'>go back</a> and select items.");
}

// Prepare placeholders for IN clause
$placeholders = implode(',', array_fill(0, count($order_items), '?'));

// Fetch menu item details including description for snapshot
$sql = "
    SELECT id, name, description, price, stock, available, is_deleted
    FROM menu_items
    WHERE id IN ($placeholders)
      AND available = 1
      AND (is_deleted = 0 OR is_deleted IS NULL)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error (prepare): " . $conn->error);
}

$types = str_repeat('i', count($order_items));
$params = array_keys($order_items);
$refs = [];
foreach ($params as $key => $val) {
    $refs[$key] = &$params[$key];
}
call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $refs));

$stmt->execute();
$result = $stmt->get_result();

$items_in_order = [];
$total = 0.0;

// Validate stock and prepare order item records (including snapshots)
while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    if (!isset($order_items[$id])) continue;

    $qty = $order_items[$id]['quantity'];

    if ($row['stock'] < $qty) {
        die("Sorry, insufficient stock for " . htmlspecialchars($row['name'], ENT_QUOTES) . ". Please <a href='place_order.php'>go back</a>.");
    }

    $line_total = $row['price'] * $qty;

    $items_in_order[$id] = [
        'id' => $id,
        'name' => $row['name'],
        'description' => $row['description'],
        'price' => $row['price'],
        'quantity' => $qty,
        'line_total' => $line_total,
        'size' => $order_items[$id]['size'],
        'temperature' => $order_items[$id]['temperature'],
    ];

    $total += $line_total;
}

$stmt->close();

if (empty($items_in_order)) {
    die("No valid items found to order. Please <a href='place_order.php'>go back</a>.");
}

// Calculate tax, discount, and final amount
$tax = round($total * $tax_rate, 2);
$discount = round($total * $discount_rate, 2);
$final_amount = round($total + $tax - $discount, 2);

$user_id = $_SESSION['user_id'] ?? null; // nullable for guests
$order_date = date('Y-m-d H:i:s');

$conn->begin_transaction();

try {
    // Insert the order
    $stmt_order = $conn->prepare("INSERT INTO orders (user_id, order_date, total, tax, discount, final_amount) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt_order) throw new Exception("Prepare failed (orders): " . $conn->error);

    $stmt_order->bind_param("issddd", $user_id, $order_date, $total, $tax, $discount, $final_amount);
    $stmt_order->execute();
    $order_id = $stmt_order->insert_id;
    $stmt_order->close();

    // Insert order items with snapshot data
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price, size, temperature, item_name, item_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt_item) throw new Exception("Prepare failed (order_items): " . $conn->error);

    // Prepare stock update statement
    $stmt_update_stock = $conn->prepare("UPDATE menu_items SET stock = stock - ? WHERE id = ? AND stock >= ?");
    if (!$stmt_update_stock) throw new Exception("Prepare failed (update stock): " . $conn->error);

    foreach ($items_in_order as $item) {
        $stmt_item->bind_param(
            "iiidssss",
            $order_id,
            $item['id'],
            $item['quantity'],
            $item['price'],
            $item['size'],
            $item['temperature'],
            $item['name'],
            $item['description']
        );
        $stmt_item->execute();

        $stmt_update_stock->bind_param("iii", $item['quantity'], $item['id'], $item['quantity']);
        $stmt_update_stock->execute();

        if ($stmt_update_stock->affected_rows === 0) {
            throw new Exception("Failed to update stock for " . htmlspecialchars($item['name'], ENT_QUOTES));
        }
    }

    $stmt_item->close();
    $stmt_update_stock->close();

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    die("Order processing failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Order Invoice - Coffee Shop</title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,700&family=Roboto&display=swap" rel="stylesheet" />

<!-- Bootstrap CSS -->
<link href="../css/bootstrap.min.css" rel="stylesheet" />

<style>
    body {
        background-color: #6F4E37;
        color: #f4eee8;
        font-family: 'Roboto', sans-serif;
        min-height: 100vh;
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 30px;
    }
    .invoice-box {
        background-color: #f4eee8;
        color: #4b3621;
        max-width: 720px;
        width: 100%;
        border-radius: 20px;
        padding: 40px 50px;
        box-shadow: 0 10px 30px rgba(111, 78, 55, 0.7);
        font-size: 1rem;
    }
    h1 {
        font-family: 'Playfair Display', serif;
        font-style: italic;
        font-weight: 700;
        font-size: 3rem;
        margin-bottom: 25px;
        text-align: center;
        letter-spacing: 2px;
        color: #4b3621;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }
    thead {
        background-color: #6F4E37;
        color: #f4eee8;
        font-weight: 700;
    }
    th, td {
        padding: 12px 15px;
        border-bottom: 1px solid #ddd;
        text-align: left;
        vertical-align: top;
    }
    tbody tr:hover {
        background-color: #ecdac8;
        cursor: default;
        color: #6F4E37;
        transition: background-color 0.3s ease;
    }
    .totals-row td {
        font-weight: 700;
        font-size: 1rem;
    }
    .text-right {
        text-align: right;
    }
    .btn-coffee {
        display: block;
        background-color: #6F4E37;
        color: #f4eee8;
        border: none;
        border-radius: 30px;
        padding: 14px 40px;
        font-weight: 700;
        font-size: 1.25rem;
        margin: 0 auto;
        width: fit-content;
        text-align: center;
        transition: background-color 0.3s ease;
        text-decoration: none;
        box-shadow: 0 5px 16px rgba(111, 78, 55, 0.7);
    }
    .btn-coffee:hover, .btn-coffee:focus {
        background-color: #563B2A;
        color: #f4eee8;
        outline: none;
        box-shadow: 0 6px 20px rgba(86, 59, 42, 0.8);
        text-decoration: none;
    }
    /* Responsive */
    @media (max-width: 768px) {
        .invoice-box {
            padding: 30px 25px;
            font-size: 0.9rem;
        }
        h1 {
            font-size: 2.5rem;
        }
        .btn-coffee {
            width: 100%;
        }
    }
</style>
</head>
<body>

<div class="invoice-box">
    <h1>Order Invoice</h1>

    <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_id); ?></p>
    <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order_date); ?></p>

    <table>
        <thead>
            <tr>
                <th>Menu Item</th>
                <th>Description</th>
                <th>Size</th>
                <th>Temperature</th>
                <th>Unit Price ($)</th>
                <th>Quantity</th>
                <th>Line Total ($)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items_in_order as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($item['description'])); ?></td>
                    <td><?php echo htmlspecialchars($item['size']); ?></td>
                    <td><?php echo htmlspecialchars($item['temperature']); ?></td>
                    <td><?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo intval($item['quantity']); ?></td>
                    <td><?php echo number_format($item['line_total'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="totals-row">
                <td colspan="6" class="text-right">Total</td>
                <td><?php echo number_format($total, 2); ?></td>
            </tr>
            <tr class="totals-row">
                <td colspan="6" class="text-right">Tax (<?php echo $tax_rate * 100; ?>%)</td>
                <td><?php echo number_format($tax, 2); ?></td>
            </tr>
            <tr class="totals-row">
                <td colspan="6" class="text-right">Discount (<?php echo $discount_rate * 100; ?>%)</td>
                <td>-<?php echo number_format($discount, 2); ?></td>
            </tr>
            <tr class="totals-row">
                <td colspan="6" class="text-right">Final Amount</td>
                <td><?php echo number_format($final_amount, 2); ?></td>
            </tr>
        </tbody>
    </table>

    <a href="place_order.php" class="btn-coffee" role="button">Place Another Order</a>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
