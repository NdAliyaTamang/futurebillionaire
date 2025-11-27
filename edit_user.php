<?php
session_start(); 

require_once '../includes/auth_check.php';
require_once '../includes/user_model.php';
require_once '../includes/validation.php';
require_once '../includes/db.php';
require_once '../includes/audit.php';

requireRole(['Admin']);

$pdo = getDB();
$id = intval($_GET['id'] ?? 0);

// log access
logAction($_SESSION['userID'], "OPEN_EDIT_USER", "user", $id, "Admin opened Edit Page");

$user = getUserById($id);
if (!$user) die("User not found.");

$message = "";

$staffData   = null;
$studentData = null;

$currentDbRole = $user['Role'];

// load staff info
if ($currentDbRole === 'Staff') {
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE UserID = ?");
    $stmt->execute([$id]);
    $staffData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// load student info
if ($currentDbRole === 'Student') {
    $stmt = $pdo->prepare("SELECT * FROM student WHERE UserID = ?");
    $stmt->execute([$id]);
    $studentData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ------------------------------------------------------------
// HANDLE SUBMIT (VALIDATE ONLY – DO NOT UPDATE HERE)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = $_POST['role'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $staffDepartment = trim($_POST['department'] ?? '');
    $staffSalary     = trim($_POST['salary'] ?? '');

    $studentGpa = trim($_POST['gpa'] ?? '');

    $newAdminPin = trim($_POST['new_admin_pin'] ?? '');

    $errors = [];

    if (!validateUsername($username)) {
        $errors[] = "Username must be 4–20 characters.";
    }

    if (!validateRole($role)) {
        $errors[] = "Invalid role.";
    }

    if (!validateStatus($isActive)) {
        $errors[] = "Invalid status.";
    }

    if ($password !== '' && !validatePassword($password)) {
        $errors[] = "Password must be 8+ chars (upper, lower, number).";
    }

    if (($currentDbRole === 'Staff' || $role === 'Staff')) {

        if ($staffDepartment !== '' && !validateDepartment($staffDepartment)) {
            $errors[] = "Department must contain letters/spaces.";
        }

        if ($staffSalary !== '' && !validateSalary($staffSalary)) {
            $errors[] = "Salary must be numeric.";
        }
    }

    if (($currentDbRole === 'Student' || $role === 'Student')) {

        if ($studentGpa !== '' && !validateGPA($studentGpa)) {
            $errors[] = "GPA must be 0.0–4.0.";
        }
    }

    // validation failed
    if (!empty($errors)) {

        logAction($_SESSION['userID'], "FAILED_UPDATE_USER", "user", $id, "Validation failed");

        $message = "<div class='alert alert-danger'><strong>Fix the following:</strong><br>" .
            implode("<br>", array_map('htmlspecialchars', $errors)) .
            "</div>";
    }

    // NO DB UPDATE – redirect to PIN verification
    else {

        // forward all values to verify_pin_action.php
        echo "<form id='redirectPIN' method='POST' action='verify_pin_action.php'>";

        echo "<input type='hidden' name='action' value='update'>";
        echo "<input type='hidden' name='target_user' value='".htmlspecialchars($id)."'>";
        echo "<input type='hidden' name='username' value='".htmlspecialchars($username)."'>";
        echo "<input type='hidden' name='password' value='".htmlspecialchars($password)."'>";
        echo "<input type='hidden' name='role' value='".htmlspecialchars($role)."'>";
        echo "<input type='hidden' name='is_active' value='".htmlspecialchars($isActive)."'>";

        // staff extras
        if ($role === 'Staff') {
            echo "<input type='hidden' name='extra[department]' value='".htmlspecialchars($staffDepartment)."'>";
            echo "<input type='hidden' name='extra[salary]' value='".htmlspecialchars($staffSalary)."'>";
        }

        // student extras
        if ($role === 'Student') {
            echo "<input type='hidden' name='extra[gpa]' value='".htmlspecialchars($studentGpa)."'>";
        }

        // admin PIN update
        if ($role === 'Admin') {
            echo "<input type='hidden' name='new_admin_pin' value='".htmlspecialchars($newAdminPin)."'>";
        }

        echo "</form>";

        echo "<script>document.getElementById('redirectPIN').submit();</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: linear-gradient(135deg, #212e68, #9e69d3);
    min-height: 100vh;
    padding: 40px;
    font-family: "Poppins", sans-serif;
}
.box {
    max-width: 650px;
    margin: auto;
    background: linear-gradient(135deg, #6c8fd6, #4b6cb7);
    padding: 30px;
    border-radius: 15px;
    color: white;
}
h1 {
    color: #f1c40f;
}
.form-control, .form-select {
    border-radius: 10px;
}
.btn-save {
    background-color: #2ecc71;
    border-radius: 10px;
}
</style>
</head>
<body>

<div class="box">

<h1>Edit User</h1>

<?= $message ?>

<form method="POST">

    <!-- Username -->
    <div class="mb-3">
        <label class="form-label">Username *</label>
        <input type="text" name="username" class="form-control"
               value="<?= htmlspecialchars($_POST['username'] ?? $user['Username']) ?>" required>
    </div>

    <!-- Email -->
    <div class="mb-3">
        <label class="form-label">Email (read only)</label>
        <input type="email" class="form-control" value="<?= htmlspecialchars($user['Email']) ?>" disabled>
    </div>

    <!-- Role -->
    <div class="mb-3">
        <?php $selectedRole = $_POST['role'] ?? $user['Role']; ?>
        <label class="form-label">Role *</label>
        <select name="role" class="form-select">
            <option value="Admin" <?= $selectedRole==='Admin'?'selected':'' ?>>Admin</option>
            <option value="Staff" <?= $selectedRole==='Staff'?'selected':'' ?>>Staff</option>
            <option value="Student" <?= $selectedRole==='Student'?'selected':'' ?>>Student</option>
        </select>
    </div>

    <!-- Active checkbox -->
    <div class="form-check mb-3">
        <?php $isActiveChecked = isset($_POST['is_active']) ? true : (bool)$user['IsActive']; ?>
        <input class="form-check-input" type="checkbox" name="is_active" <?= $isActiveChecked?'checked':'' ?>>
        <label class="form-check-label">Active user</label>
    </div>

    <!-- New password -->
    <div class="mb-3">
        <label class="form-label">New Password (optional)</label>
        <input type="password" name="password" class="form-control">
    </div>

    <!-- NEW ADMIN PIN (ONLY IF EDITING ADMIN USER) -->
    <?php if ($currentDbRole === 'Admin'): ?>
        <div class="mb-3">
            <label class="form-label">New Admin PIN (optional)</label>
            <input type="password" maxlength="6" name="new_admin_pin" class="form-control"
                   placeholder="6-digit PIN">
        </div>
    <?php endif; ?>

    <!-- STAFF EXTRA -->
    <?php if ($currentDbRole === 'Staff'): ?>
        <?php
        $deptValue   = $_POST['department'] ?? ($staffData['Department'] ?? '');
        $salaryValue = $_POST['salary'] ?? ($staffData['Salary'] ?? '');
        ?>
        <div class="mb-3">
            <label class="form-label">Department</label>
            <input type="text" name="department" class="form-control"
                   value="<?= htmlspecialchars($deptValue) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Salary</label>
            <input type="number" step="0.01" name="salary" class="form-control"
                   value="<?= htmlspecialchars($salaryValue) ?>">
        </div>
    <?php endif; ?>

    <!-- STUDENT EXTRA -->
    <?php if ($currentDbRole === 'Student'): ?>
        <?php $gpaValue = $_POST['gpa'] ?? ($studentData['GPA'] ?? ''); ?>
        <div class="mb-3">
            <label class="form-label">GPA</label>
            <input type="number" step="0.01" name="gpa" class="form-control"
                   value="<?= htmlspecialchars($gpaValue) ?>">
        </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-save w-100 mt-3">Save Changes</button>

    <div class="text-center mt-3">
        <a href="manage_user.php" class="back-btn">← Back</a>
    </div>

</form>

</div>

</body>
</html>
