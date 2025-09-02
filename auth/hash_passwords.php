<?php
// This script hashes existing plain passwords in users table using password_hash()

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config/db.php';  // Your DB connection

try {
    $conn->begin_transaction();

    // Fetch all users with their current passwords
    $users = $conn->query("SELECT id, password FROM users");
    if (!$users) {
        throw new Exception("Query failed: " . $conn->error);
    }

    // Prepare update statement
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    while ($user = $users->fetch_assoc()) {
        $plainPassword = $user['password'];

        // Skip if password already hashed
        // password_get_info returns algo=0 if plain text (not hashed)
        if (password_get_info($plainPassword)['algo'] !== 0) {
            continue;  // Already hashed, skip
        }

        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        if ($hashedPassword === false) {
            throw new Exception("Failed to hash password for user ID {$user['id']}");
        }

        $stmt->bind_param('si', $hashedPassword, $user['id']);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update user ID {$user['id']}: " . $stmt->error);
        }
    }

    $conn->commit();
    $stmt->close();

    echo "All plain text passwords have been securely hashed successfully.";

} catch (Exception $e) {
    $conn->rollback();
    echo "Error hashing passwords: " . $e->getMessage();
}
