<?php
session_start();
include '../config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password_input = $_POST['password'] ?? '';

    if ($username === '' || $password_input === '') {
        $error = 'Please enter both username and password.';
    } else {
        // Adjust your employee users table and columns accordingly
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND role = 'employee'");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password_input, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                header('Location: employee_dashboard.php');
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Employee Login - Coffee Shop</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,700&family=Roboto&display=swap" rel="stylesheet" />
<link href="../css/bootstrap.min.css" rel="stylesheet" />
<style>
    body {
        margin: 0;
        min-height: 100vh;
        background: linear-gradient(135deg, #6F4E37 0%, #563B2A 100%);
        font-family: 'Roboto', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        color: #f4eee8;
    }
    .login-container {
        background-color: #f4eee8;
        color: #4b3621;
        padding: 50px 45px;
        border-radius: 24px;
        box-shadow: 0 15px 40px rgba(111, 78, 55, 0.7);
        max-width: 400px;
        width: 100%;
        text-align: center;
        font-size: 1rem;
    }
    h2 {
        font-family: 'Playfair Display', serif;
        font-style: italic;
        font-weight: 700;
        font-size: 2.8rem;
        margin-bottom: 25px;
        letter-spacing: 3px;
        color: #6F4E37;
        user-select: none;
    }
    form {
        margin-top: 20px;
    }
    label {
        display: block;
        margin: 15px 0 6px 0;
        font-weight: 600;
        font-size: 1.05rem;
        user-select: none;
    }
    input[type="text"], input[type="password"] {
        width: 100%;
        padding: 14px 18px;
        border-radius: 14px;
        border: 2px solid #c9b89c;
        font-size: 1rem;
        transition: border-color 0.3s ease;
        font-family: 'Roboto', sans-serif;
        color: #4b3621;
    }
    input[type="text"]:focus, input[type="password"]:focus {
        outline: none;
        border-color: #6F4E37;
        box-shadow: 0 0 10px #a67c52;
    }
    button {
        margin-top: 30px;
        width: 100%;
        background-color: #6F4E37;
        color: #f4eee8;
        border: none;
        padding: 14px 0;
        font-size: 1.25rem;
        font-weight: 700;
        border-radius: 30px;
        cursor: pointer;
        box-shadow: 0 10px 30px rgba(111, 78, 55, 0.8);
        transition: background-color 0.3s ease;
        user-select: none;
    }
    button:hover, button:focus {
        background-color: #563B2A;
        outline: none;
        box-shadow: 0 12px 38px rgba(86, 59, 42, 0.9);
    }
    .error-message {
        margin-top: 20px;
        color: #d9534f;
        font-weight: 700;
        font-size: 1rem;
    }
    /* Responsive */
    @media (max-width: 480px) {
        .login-container {
            padding: 40px 25px;
        }
        h2 {
            font-size: 2.2rem;
            letter-spacing: 2px;
        }
    }
</style>
</head>
<body>
<div class="login-container" role="main" aria-labelledby="loginHeading">
    <h2 id="loginHeading">Employee Login</h2>
    <?php if ($error): ?>
        <div class="error-message" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" action="login.php" novalidate>
        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            required
            autofocus
            autocomplete="username"
            placeholder="Enter your username"
        />
        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            required
            autocomplete="current-password"
            placeholder="Enter your password"
        />
        <button type="submit" aria-label="Log in to Coffee Shop Employee">Log In</button>
    </form>
</div>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
