<?php
include '../config/db.php';

// Fetch menu items that are available
$sql = "SELECT id, name, description, price, stock, available, is_deleted FROM menu_items 
        WHERE stock > 0 AND available = 1 AND (is_deleted = 0 OR is_deleted IS NULL)
        ORDER BY name ASC";
$result = $conn->query($sql);

$menu_items = [];

while ($row = $result->fetch_assoc()) {
    $menu_items[] = $row;
}

header('Content-Type: application/json');
echo json_encode($menu_items);
exit();
