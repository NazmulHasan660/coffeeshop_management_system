<?php
include '../config/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Utility: Handle profile photo upload
function uploadProfilePhoto($file) {
    $targetDir = "../uploads/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);

    $fileName = basename($file["name"]);
    $targetFile = $targetDir . uniqid() . "-" . preg_replace('/[^A-Za-z0-9_\-.]/', '', $fileName);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($imageFileType, $allowedTypes)) {
        return ['error' => 'Only JPG, JPEG, PNG & GIF files are allowed.'];
    }

    if ($file["size"] > 2 * 1024 * 1024) {
        return ['error' => 'File size must be less than 2MB.'];
    }

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return ['path' => $targetFile];
    }
    return ['error' => 'Error uploading file.'];
}

// Handle Add/Edit Employee submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add_employee']) || isset($_POST['edit_employee']))) {
    $name = trim($_POST['name']);
    $position = trim($_POST['position']);  // position/ designation as string
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact_info']);
    $salary = floatval($_POST['salary']);
    $date_hired = $_POST['date_hired'];
    $role = trim($_POST['role']);
    $shift = trim($_POST['shift_time']);
    $notes = trim($_POST['notes']);
    $status = $_POST['status'];

    $profile_photo_path = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadProfilePhoto($_FILES['profile_photo']);
        if (isset($upload['error'])) {
            die("<script>alert('".$upload['error']."'); window.history.back();</script>");
        } else {
            $profile_photo_path = $upload['path'];
        }
    }

    if (isset($_POST['edit_employee'])) {
        $employee_id = intval($_POST['employee_id']);
        if ($profile_photo_path) {
            $stmt = $conn->prepare("UPDATE employee SET name=?, position=?, email=?, contact_info=?, salary=?, date_hired=?, role=?, shift_time=?, notes=?, profile_photo=?, status=? WHERE employee_id=?");
            if (!$stmt) { die("Prepare failed: " . $conn->error); }
            $stmt->bind_param("ssssdssssssi", $name, $position, $email, $contact, $salary, $date_hired, $role, $shift, $notes, $profile_photo_path, $status, $employee_id);
        } else {
            $stmt = $conn->prepare("UPDATE employee SET name=?, position=?, email=?, contact_info=?, salary=?, date_hired=?, role=?, shift_time=?, notes=?, status=? WHERE employee_id=?");
            if (!$stmt) { die("Prepare failed: " . $conn->error); }
            $stmt->bind_param("ssssdssss si", $name, $position, $email, $contact, $salary, $date_hired, $role, $shift, $notes, $status, $employee_id);
        }
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO employee (name, position, email, contact_info, salary, date_hired, role, shift_time, notes, profile_photo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) { die("Prepare failed: " . $conn->error); }
        $stmt->bind_param("ssssdssssss", $name, $position, $email, $contact, $salary, $date_hired, $role, $shift, $notes, $profile_photo_path, $status);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: employee.php");
    exit;
}

// Handle Delete Employee
if (isset($_GET['delete'])) {
    $employee_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM employee WHERE employee_id = ?");
    if (!$stmt) { die("Prepare failed: " . $conn->error); }
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $stmt->close();
    header("Location: employee.php");
    exit;
}

// Fetch employee to edit
$editEmp = null;
if (isset($_GET['edit'])) {
    $employee_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM employee WHERE employee_id = ?");
    if (!$stmt) { die("Prepare failed: " . $conn->error); }
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $editEmp = $res->fetch_assoc();
    $stmt->close();
}

// Fetch all employees
$emp_sql = "SELECT * FROM employee ORDER BY employee_id DESC";
$employees_res = $conn->query($emp_sql);
if (!$employees_res) {
    die("Employee fetch query failed: " . $conn->error);
}
$employees = $employees_res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Employee Management - Coffee Shop Admin</title>
<link href="../css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
<style>
/* Coffee theme styling same as before */
body {
    background-color: #f4eee8;
    color: #4b3621;
    font-family: 'Roboto', sans-serif;
    margin: 0; min-height: 100vh; display: flex; flex-direction: column;
}
.sidebar {
    height: 100vh; background-color: #6F4E37; color: white; padding-top: 25px;
    position: fixed; width: 240px; font-weight: 600;
    box-shadow: 4px 0 14px rgba(111, 78, 55, 0.3);
}
.sidebar h3 {
    font-family: 'Playfair Display', serif; font-style: italic;
    font-size: 1.8rem; letter-spacing: 3px; text-align: center; margin-bottom: 40px;
}
.sidebar a {
    color: #f4eee8; text-decoration: none; display: flex;
    align-items: center; padding: 14px 25px; margin-bottom: 10px;
    font-size: 1.15rem; border-radius: 8px;
    transition: background-color 0.3s ease, transform 0.2s ease;
    font-weight: 600;
}
.sidebar a .bi {
    font-size: 1.3rem; margin-right: 12px;
}
.sidebar a:hover, .sidebar a.active {
    background-color: #563B2A; transform: scale(1.04); font-weight: 700;
}
.sidebar a[href="employee.php"] {
    background-color: #563B2A; font-weight: 700;
}
.content {
    margin-left: 240px; padding: 40px 50px 100px;
    flex: 1; background-color: #fff9f4; overflow-x: auto;
}
h1, h2 {
    font-family: 'Playfair Display', serif; font-style: italic;
    font-weight: 700; color: #6F4E37; letter-spacing: 2px;
    text-shadow: 1px 1px 1px rgba(111, 78, 55, 0.25);
}
h1 { font-size: 3rem; margin-bottom: 15px; }
h2 { margin-top: 40px; margin-bottom: 20px; }
table {
    width: 100%; border-collapse: collapse; margin-bottom: 30px;
}
th, td {
    border: 1px solid #a9746e;
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
form {
    background-color: #d7c4aa;
    padding: 20px;
    border-radius: 8px;
    max-width: 600px;
    margin-bottom: 60px;
}
label {
    display: block;
    margin: 10px 0 5px;
    font-weight: 600;
}
input[type="text"], input[type="number"], input[type="date"], input[type="email"],
select, textarea {
    width: 100%;
    padding: 7px;
    border: 1px solid #8b6b4a;
    border-radius: 4px;
    box-sizing: border-box;
}
textarea {
    resize: vertical; min-height: 70px;
}
button {
    margin-top: 15px;
    background-color: #6F4E37;
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 700;
}
button:hover {
    background-color: #54391f;
}
.btn-secondary {
    background-color: #a9746e;
    color: white;
    padding: 8px 14px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 700;
    margin-left: 10px;
}
.btn-secondary:hover {
    background-color: #816045;
}
.actions a, .actions button {
    margin-right: 8px;
    font-weight: 600;
    cursor: pointer;
}
.actions button {
    background: none;
    color: #a94442;
    border: none;
    padding: 0;
}
.actions button:hover {
    color: #843534;
}
.profile-photo-thumb {
    height: 50px; width: 50px; object-fit: cover; border-radius: 5px;
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
    margin-top: auto;
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
<script>
function confirmDelete(id) {
    if (confirm("Are you sure you want to delete this employee?")) {
        window.location.href = 'employee.php?delete=' + id;
    }
}
function cancelEdit() {
    window.location.href = 'employee.php';
}
</script>
</head>
<body>
<div class="sidebar">
<h3>Coffee Shop Admin</h3>
<a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
<a href="inventory_manage.php"><i class="bi bi-box-seam"></i> Inventory</a>
<a href="menu_manage.php"><i class="bi bi-journal-text"></i> Menu Management</a>
<a href="sales_report.php"><i class="bi bi-graph-up"></i> Sales Reports</a>
<a href="employee.php" class="active"><i class="bi bi-people"></i> Employee Management</a>
<a href="../user/place_order.php" target="_blank"><i class="bi bi-cart"></i> Place Order</a>
<a href="../auth/logout.php" style="margin-top: 50px;"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>
<div class="content">
<h1>Employee Management</h1>

<h2>Employee List</h2>
<table>
<thead>
<tr>
    <th>ID</th><th>Name</th><th>Designation</th><th>Email</th><th>Contact Info</th><th>Salary</th><th>Date Hired</th><th>Role</th><th>Shift</th><th>Notes</th><th>Photo</th><th>Status</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php if ($employees): foreach ($employees as $emp): ?>
<tr>
    <td><?= htmlspecialchars($emp['employee_id']) ?></td>
    <td><?= htmlspecialchars($emp['name']) ?></td>
    <td><?= htmlspecialchars($emp['position']) ?></td>
    <td><?= htmlspecialchars($emp['email']) ?></td>
    <td><?= htmlspecialchars($emp['contact_info']) ?></td>
    <td><?= number_format($emp['salary'], 2) ?></td>
    <td><?= htmlspecialchars($emp['date_hired']) ?></td>
    <td><?= htmlspecialchars($emp['role']) ?></td>
    <td><?= htmlspecialchars($emp['shift_time']) ?></td>
    <td><?= nl2br(htmlspecialchars($emp['notes'])) ?></td>
    <td>
        <?php if ($emp['profile_photo'] && file_exists($emp['profile_photo'])): ?>
            <img src="<?= htmlspecialchars(substr($emp['profile_photo'], 3)) ?>" alt="Profile" class="profile-photo-thumb" />
        <?php else: ?>
            N/A
        <?php endif; ?>
    </td>
    <td><?= htmlspecialchars(ucfirst($emp['status'])) ?></td>
    <td class="actions">
        <a href="employee.php?edit=<?= $emp['employee_id'] ?>" class="btn-secondary">Edit</a>
        <button onclick="confirmDelete(<?= $emp['employee_id'] ?>)">Delete</button>
    </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="13">No employees found.</td></tr>
<?php endif; ?>
</tbody>
</table>

<h2><?= isset($editEmp) ? 'Edit Employee' : 'Add New Employee' ?></h2>
<form method="POST" action="employee.php" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="employee_id" value="<?= isset($editEmp) ? $editEmp['employee_id'] : '' ?>">

    <label for="name">Name:</label>
    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($editEmp['name'] ?? '') ?>">

    <label for="position">Designation:</label>
    <input type="text" id="position" name="position" required value="<?= htmlspecialchars($editEmp['position'] ?? '') ?>">

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($editEmp['email'] ?? '') ?>">

    <label for="contact_info">Contact Info:</label>
    <input type="text" id="contact_info" name="contact_info" value="<?= htmlspecialchars($editEmp['contact_info'] ?? '') ?>">

    <label for="salary">Salary:</label>
    <input type="number" step="0.01" id="salary" name="salary" required value="<?= htmlspecialchars($editEmp['salary'] ?? '') ?>">

    <label for="date_hired">Date Hired:</label>
    <input type="date" id="date_hired" name="date_hired" required value="<?= htmlspecialchars($editEmp['date_hired'] ?? '') ?>">

    <label for="role">Role:</label>
    <input type="text" id="role" name="role" value="<?= htmlspecialchars($editEmp['role'] ?? '') ?>">

    <label for="shift_time">Shift Time:</label>
    <input type="text" id="shift_time" name="shift_time" value="<?= htmlspecialchars($editEmp['shift_time'] ?? '') ?>">

    <label for="notes">Notes:</label>
    <textarea id="notes" name="notes"><?= htmlspecialchars($editEmp['notes'] ?? '') ?></textarea>

    <label for="profile_photo">Profile Photo: <?php if (isset($editEmp['profile_photo']) && $editEmp['profile_photo'] && file_exists($editEmp['profile_photo'])): ?>
        <img src="<?= htmlspecialchars(substr($editEmp['profile_photo'], 3)) ?>" alt="Current" class="profile-photo-thumb" />
    <?php endif; ?></label>
    <input type="file" id="profile_photo" name="profile_photo" accept="image/*">

    <label for="status">Status:</label>
    <select id="status" name="status" required>
        <option value="active" <?= (isset($editEmp['status']) && $editEmp['status'] === 'active') ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= (isset($editEmp['status']) && $editEmp['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
    </select>

    <button type="submit" name="<?= isset($editEmp) ? 'edit_employee' : 'add_employee' ?>">
        <?= isset($editEmp) ? 'Update Employee' : 'Add Employee' ?>
    </button>
    <?php if (isset($editEmp)): ?>
        <button type="button" onclick="cancelEdit()" style="background:#a9746e;">Cancel</button>
    <?php endif; ?>
</form>
</div>
<footer>
&copy; <?= date('Y'); ?> Coffee Shop Management &middot;
<a href="dashboard.php">Dashboard</a> &middot;
<a href="menu_manage.php">Menu</a> &middot;
<a href="inventory_manage.php">Inventory</a> &middot;
<a href="sales_report.php">Sales</a> &middot;
<a href="employee.php">Employees</a>
</footer>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
