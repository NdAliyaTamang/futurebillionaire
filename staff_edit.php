<?php

// ===============================================================
// STAFF EDIT PAGE — ADMIN ONLY
// This file allows admin to edit a staff member's information.
// It loads existing staff data, updates it, and stays on the page
// showing a success message instead of redirecting.
// ===============================================================

// Load authentication check (ensures only logged in users access)
require_once '../includes/auth_check.php';

// Load staff model (used for fetching + updating staff)
require_once '../includes/staff_model.php';

// ===============================================================
// ACCESS CONTROL — ONLY ADMIN CAN OPEN THIS PAGE
// ===============================================================
if ($_SESSION['role'] !== 'Admin') {
    die("Access denied."); // force stop if not admin
}

// ===============================================================
// VALIDATE STAFF ID FROM URL
// /staff_edit.php?id=##
// ===============================================================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: staff_list.php"); // redirect if invalid
    exit();
}

$model = new StaffModel();
$staffId = (int)$_GET['id']; // convert ID to integer

// Get staff details from DB
$staff = $model->getStaffById($staffId);

// If staff not found, return to staff list
if (!$staff) {
    $_SESSION['error_message'] = "Staff not found.";
    header("Location: staff_list.php");
    exit();
}

// ===============================================================
// LOAD ALL COURSES FOR DROPDOWN
// ===============================================================
$courses = $model->getAllCourseNames();

// Holds error messages
$errors = [];

// Pre-fill form with current staff data
$data = $staff;

// Success message (shown after update — stays on page)
$success = "";

// ===============================================================
// HANDLE FORM SUBMISSION — WHEN ADMIN UPDATES STAFF
// ===============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize & update form data (overwrite $data values)
    $data['FirstName']  = trim($_POST['FirstName']);
    $data['LastName']   = trim($_POST['LastName']);
    $data['Email']      = trim($_POST['Email']);
    $data['CourseName'] = trim($_POST['CourseName']);
    $data['Salary']     = trim($_POST['Salary']);
    $data['HireDate']   = trim($_POST['HireDate']);
    $data['IsActive']   = (int)$_POST['IsActive'];

    // ===============================================================
    // LOOKUP CourseID using CourseName
    // A staff must have a CourseID → so we search it.
    // ===============================================================
    $stmt = getDB()->prepare("SELECT CourseID FROM Course WHERE CourseName=?");
    $stmt->execute([$data['CourseName']]);
    $data['CourseID'] = $stmt->fetchColumn() ?: null;

    // Validate required fields (optional)
    if (empty($data['FirstName'])) $errors[] = "First name is required.";
    if (empty($data['LastName']))  $errors[] = "Last name is required.";
    if (empty($data['Email']))     $errors[] = "Email is required.";
    if (empty($data['HireDate']))  $errors[] = "Hire date is required.";

    // ===============================================================
    // UPDATE STAFF (ONLY IF NO ERRORS)
    // ===============================================================
    if (empty($errors)) {
        try {

            // Update staff in DB
            $model->updateStaff($staffId, $data);

            // SUCCESS MESSAGE (STAY ON SAME PAGE)
            $success = "✅ Staff details updated successfully!";

            // DO NOT REDIRECT
            // Page stays here so admin sees success message.

        } catch (Exception $e) {
            // Capture error message from database or model
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<title>Edit Staff Member</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* ===============================================================
   PAGE THEME — MATCHES YOUR CURRENT SCREENSHOT/UI
   =============================================================== */
body { 
    background: linear-gradient(135deg,#182848,#4b6cb7); 
    color:white; 
    font-family:Poppins; 
    padding:40px; 
}
.container { 
    background:rgba(255,255,255,0.08); 
    padding:30px; 
    border-radius:15px; 
    width:85%; 
    max-width:850px; 
    margin:auto; 
}
h1 { color:#f1c40f; }
label { font-weight:600; }
input, select { border-radius:8px !important; }
.btn-primary { background:#27ae60; border:none; }
.btn-primary:hover { background:#1f8a4f; }
</style>
</head>

<body>

<div class="container">

    <!-- ===============================================================
         PAGE HEADER — TITLE + BACK BUTTON
         =============================================================== -->
    <div class="d-flex justify-content-between mb-4">
        <h1>Edit Staff Member</h1>

        <!-- Back button ALWAYS visible + does not get overlapped -->
        <a href="staff_list.php" class="btn btn-secondary">Back to List</a>
    </div>

    <!-- ===============================================================
         SUCCESS MESSAGE — SHOWN AFTER UPDATE
         Stays on page until admin clicks back.
         =============================================================== -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <!-- ===============================================================
         ERROR MESSAGES — DISPLAY ALL FORM VALIDATION ERRORS
         =============================================================== -->
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ===============================================================
         STAFF INFO BOX — STATIC INFORMATION ABOUT USER
         =============================================================== -->
    <div class="p-3 bg-dark bg-opacity-25 rounded mb-4">
        <h4>Staff Information</h4>
        <p><strong>ID:</strong> #<?= $staff['StaffID'] ?></p>
        <p><strong>Username:</strong> <?= htmlspecialchars($staff['Username']) ?></p>
        <p><strong>User ID:</strong> <?= $staff['UserID'] ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($staff['Role']) ?></p>
    </div>

    <!-- ===============================================================
         EDIT STAFF FORM — PRE-FILLED WITH EXISTING VALUES
         =============================================================== -->
    <form method="POST">

        <div class="row g-3">

            <!-- First Name -->
            <div class="col-md-6">
                <label>First Name *</label>
                <input type="text" name="FirstName" 
                       value="<?= htmlspecialchars($data['FirstName']) ?>" 
                       class="form-control" required>
            </div>

            <!-- Last Name -->
            <div class="col-md-6">
                <label>Last Name *</label>
                <input type="text" name="LastName" 
                       value="<?= htmlspecialchars($data['LastName']) ?>" 
                       class="form-control" required>
            </div>

            <!-- Email -->
            <div class="col-md-6">
                <label>Email *</label>
                <input type="email" name="Email" 
                       value="<?= htmlspecialchars($data['Email']) ?>" 
                       class="form-control" required>
            </div>

            <!-- Course Dropdown -->
            <div class="col-md-6">
                <label>Course *</label>
                <select name="CourseName" class="form-select">
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c ?>" 
                                <?= $data['CourseName'] === $c ? 'selected' : '' ?>>
                            <?= $c ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Salary -->
            <div class="col-md-6">
                <label>Salary (DKK)</label>
                <input type="number" step="0.01" name="Salary"
                       value="<?= htmlspecialchars($data['Salary']) ?>"
                       class="form-control">
            </div>

            <!-- Hire Date -->
            <div class="col-md-6">
                <label>Hire Date *</label>
                <input type="date" name="HireDate" 
                       value="<?= htmlspecialchars($data['HireDate']) ?>" 
                       class="form-control" required>
            </div>

            <!-- Active / Inactive Status -->
            <div class="col-md-6">
                <label>Status</label>
                <select name="IsActive" class="form-select">
                    <option value="1" <?= $data['IsActive'] ? 'selected':'' ?>>Active</option>
                    <option value="0" <?= !$data['IsActive'] ? 'selected':'' ?>>Inactive</option>
                </select>
            </div>

        </div>

        <!-- Submit Button -->
        <button class="btn btn-primary mt-4">Update Staff</button>

    </form>
</div>

</body>
</html>
