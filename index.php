<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Coffee Shop Access</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,700&family=Roboto&display=swap');
  body {
    margin: 0;
    height: 100vh;
    background: linear-gradient(135deg, #6f4e37 0%, #d7c4b6 100%);
    font-family: 'Roboto', sans-serif;
    color: #fff9f4;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .container {
    background: #4b3621;
    padding: 50px 60px;
    border-radius: 24px;
    box-shadow: 0 10px 40px rgba(111, 78, 55, 0.7);
    max-width: 360px;
    width: 100%;
    text-align: center;
    user-select: none;
  }
  h1 {
    font-family: 'Playfair Display', serif;
    font-style: italic;
    font-weight: 700;
    font-size: 2.8rem;
    margin-bottom: 20px;
    color: #f4eee8;
    letter-spacing: 3px;
  }
  p {
    margin-bottom: 40px;
    font-size: 1.1rem;
    color: #d7c4b6;
  }
  .btn-group {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-direction: column;
  }
  a.button {
    padding: 16px 0;
    background-color: #a67c52;
    color: #f4eee8;
    font-weight: 700;
    text-decoration: none;
    border-radius: 30px;
    font-size: 1.4rem;
    box-shadow: 0 6px 20px rgba(166, 124, 82, 0.7);
    transition: background-color 0.3s ease, transform 0.2s ease;
    user-select: none;
    display: block;
  }
  a.button:hover, a.button:focus {
    background-color: #d4b79e;
    color: #4b3621;
    transform: scale(1.06);
    outline: none;
  }
  /* Responsive */
  @media (max-width: 480px) {
    .container {
      padding: 40px 30px;
    }
    h1 {
      font-size: 2rem;
      letter-spacing: 1.8px;
    }
  }
</style>
</head>
<body>
<div class="container" role="main">
  <h1>Coffee Shop</h1>
  <p>Choose your access type</p>
  <div class="btn-group" role="group" aria-label="User, Admin and Employee access">
    <a href="user/place_order.php" class="button" role="button" aria-pressed="false" tabindex="0">User</a>
    <a href="auth/login.php" class="button" role="button" aria-pressed="false" tabindex="0">Admin</a>
    <a href="employee/login.php" class="button" role="button" aria-pressed="false" tabindex="0">Employee</a>
  </div>
</div>
</body>
</html>
