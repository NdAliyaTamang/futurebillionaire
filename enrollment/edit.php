<?php
/**
 * ENROLLMENT – EDIT PAGE
 * -----------------------
 * This page allows staff to EDIT an existing enrollment.
 * It:
 *  - Loads the enrollment by ID
 *  - Loads all students and courses for dropdowns
 *  - Shows a big, clear form
 *  - Sends changes to update_status.php using POST
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session if not already running
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only logged-in users can edit
require_login();
// Only admin and staff roles can access the enrollment module
require_role(['admin', 'staff']);

// Get current user for greeting
$user = current_user();

// Read enrollment ID from query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID, go back with error
if ($id <= 0) {
    set_flash('error', 'Invalid enrollment ID.');
    header('Location: index.php');
    exit;
}

// Load the enrollment record
$stmt = db()->prepare("
    SELECT e.EnrollmentID, e.StudentID, e.CourseID, e.EnrollmentDate,
           e.Status, e.FinalGrade
    FROM enrollment e
    WHERE e.EnrollmentID = :id
");
$stmt->execute([':id' => $id]);
$enroll = $stmt->fetch();

// If not found, back to list
if (!$enroll) {
    set_flash('error', 'Enrollment record not found.');
    header('Location: index.php');
    exit;
}

// Load all students for dropdown
$students = db()->query("
    SELECT StudentID, CONCAT(FirstName, ' ', LastName) AS FullName
    FROM student
    ORDER BY FirstName, LastName
")->fetchAll();

// Load all courses for dropdown
$courses = db()->query("
    SELECT CourseID, CourseCode, CourseName
    FROM course
    ORDER BY CourseName
")->fetchAll();

// Allowed status values
$statuses = ['registered','in-progress','completed','failed','dropped'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Enrollment</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
/* Big, centered, clear style like the list page */

:root {
  --primary: #2563eb;
  --primary-dark: #1d4ed8;
  --danger: #dc2626;
  --bg: #f1f5f9;
  --surface: #ffffff;
  --border: #d1d5db;
  --text: #111827;
  --muted: #6b7280;
}

/* Center the page on screen */
body {
  margin: 0;
  background: var(--bg);
  font-family: system-ui, sans-serif;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 30px;
}

/* Main white card */
.page {
  width: 100%;
  max-width: 900px;
  background: var(--surface);
  border-radius: 16px;
  padding: 32px 32px 28px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.15);
}

/* Header section */
.header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}

.page-title {
  font-size: 36px;
  font-weight: 800;
  margin: 0;
}

.page-sub {
  font-size: 18px;
  color: var(--muted);
  margin-top: 6px;
}

/* User info */
.user-box {
  text-align: right;
  font-size: 16px;
}
.user-role {
  font-size: 14px;
  color: var(--muted);
}
.btn-top {
  display: inline-block;
  margin-top: 8px;
  padding: 8px 14px;
  font-size: 14px;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: #f9fafb;
  color: var(--text);
  text-decoration: none;
}
.btn-top:hover { background: #e5e7eb; }

/* Flash messages */
.flash {
  padding: 14px 16px;
  border-radius: 10px;
  font-size: 18px;
  margin-bottom: 20px;
}
.flash-success {
  background: #e7f6ee;
  border: 2px solid #34d399;
}
.flash-error {
  background: #fde2e1;
  border: 2px solid #f87171;
}

/* Form layout */
form {
  margin-top: 10px;
}

.field {
  margin-bottom: 18px;
}

label {
  display: block;
  margin-bottom: 6px;
  font-size: 18px;
  font-weight: 600;
}

.input, .select {
  width: 100%;
  padding: 12px 14px;
  font-size: 18px;
  border-radius: 8px;
  border: 2px solid var(--border);
}

.input:focus,
.select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(37,99,235,0.18);
}

/* Buttons */
.btn {
  padding: 12px 20px;
  font-size: 18px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  text-decoration: none;
  display: inline-block;
}

.btn-primary {
  background: var(--primary);
  color: #fff;
}
.btn-primary:hover { background: var(--primary-dark); }

.btn-secondary {
  background: #e5e7eb;
  color: #111;
}
.btn-secondary:hover { background: #d4d4d8; }

.actions-row {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 6px;
}

/* Helper text */
.helper {
  font-size: 14px;
  color: var(--muted);
}
</style>
</head>
<body>
<div class="page">

  <!-- Header section -->
  <div class="header-bar">
    <div>
      <h1 class="page-title">Edit Enrollment</h1>
      <div class="page-sub">
        Update the student, course, date, status or grade for this enrollment.
      </div>
    </div>
    <div class="user-box">
      <strong><?= htmlspecialchars($user['name']) ?></strong><br>
      <span class="user-role"><?= htmlspecialchars($user['role']) ?></span><br>
      <a href="index.php" class="btn-top">Back to list</a>
    </div>
  </div>

  <!-- Flash messages if any (for example, validation errors) -->
  <?php render_flash(); ?>
  
<!-- your page HTML... -->

<script>
// Auto-hide flash after 6 seconds
setTimeout(() => {
    document.querySelectorAll('.flash').forEach(el => {
        el.style.transition = "opacity 0.8s";
        el.style.opacity = "0";
        setTimeout(() => el.remove(), 800);
    });
}, 6000);
</script>

</body>
</html>


  <!-- EDIT FORM 
       This sends the updated data to update_status.php using POST -->
  <form method="post" action="update_status.php">
    <!-- We keep the ID hidden so update_status.php knows which row to update -->
    <input type="hidden" name="EnrollmentID" value="<?= (int)$enroll['EnrollmentID'] ?>">

    <!-- Student dropdown -->
    <div class="field">
      <label for="student">Student</label>
      <select class="select" id="student" name="StudentID" required>
        <option value="">Select a student…</option>
        <?php foreach ($students as $stu): ?>
          <option
            value="<?= (int)$stu['StudentID'] ?>"
            <?= $stu['StudentID'] == $enroll['StudentID'] ? 'selected' : '' ?>
          >
            <?= htmlspecialchars($stu['FullName']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="helper">Choose which student this enrollment belongs to.</div>
    </div>

    <!-- Course dropdown -->
    <div class="field">
      <label for="course">Course</label>
      <select class="select" id="course" name="CourseID" required>
        <option value="">Select a course…</option>
        <?php foreach ($courses as $c): ?>
          <option
            value="<?= (int)$c['CourseID'] ?>"
            <?= $c['CourseID'] == $enroll['CourseID'] ? 'selected' : '' ?>
          >
            <?= htmlspecialchars($c['CourseCode'] . ' — ' . $c['CourseName']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="helper">Select which course the student is enrolled in.</div>
    </div>

    <!-- Enrollment date -->
    <div class="field">
      <label for="date">Enrollment date</label>
      <input
        class="input"
        type="date"
        id="date"
        name="EnrollmentDate"
        value="<?= htmlspecialchars($enroll['EnrollmentDate']) ?>"
        required
      >
      <div class="helper">The date when this enrollment was created.</div>
    </div>

    <!-- Status dropdown -->
    <div class="field">
      <label for="status">Status</label>
      <select class="select" id="status" name="Status" required>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= $s ?>" <?= $s === $enroll['Status'] ? 'selected' : '' ?>>
            <?= ucfirst($s) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="helper">
        Example statuses: registered, in-progress, completed, failed, dropped.
      </div>
    </div>

    <!-- Grade -->
    <div class="field">
      <label for="grade">Final grade (optional)</label>
      <input
        class="input"
        type="text"
        id="grade"
        name="Grade"
        placeholder="e.g. A, B+, 75"
        value="<?= htmlspecialchars($enroll['FinalGrade'] ?? '') ?>"
      >
      <div class="helper">
        Leave blank if the student has not completed the course yet.
      </div>
    </div>

    <!-- Buttons -->
    <div class="actions-row">
      <a href="index.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Save changes</button>
    </div>
  </form>
</div>
</body>
</html>
