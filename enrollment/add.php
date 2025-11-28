<?php
/**
 * ENROLLMENT – ADD PAGE
 * ----------------------
 * This page is used to CREATE a new enrollment.
 * It:
 *  - Loads all students and courses for dropdowns
 *  - Shows a big, clear form (easy to read)
 *  - Sends the data to validate.php using POST
 *  - validate.php will then check the input and (if valid) call store.php
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only logged-in users can access this page
require_login();
// Only admin and staff roles can access the enrollment module
require_role(['admin', 'staff']);


// Get current user (for greeting)
$user = current_user();

// Load all students for the dropdown
$students = db()->query("
    SELECT StudentID, CONCAT(FirstName, ' ', LastName) AS FullName
    FROM student
    ORDER BY FirstName, LastName
")->fetchAll();

// Load all courses for the dropdown
$courses = db()->query("
    SELECT CourseID, CourseCode, CourseName
    FROM course
    ORDER BY CourseName
")->fetchAll();

// Allowed statuses for new enrollments
$statuses = ['registered','in-progress','completed','failed','dropped'];

// Default date: today (you can change to empty string if you prefer)
$today = date('Y-m-d');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Enrollment</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
/* Same big, centered style as list + edit */

:root {
  --primary: #2563eb;
  --primary-dark: #1d4ed8;
  --bg: #f1f5f9;
  --surface: #ffffff;
  --border: #d1d5db;
  --text: #111827;
  --muted: #6b7280;
}

/* Center the card on the screen */
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

/* Main white container */
.page {
  width: 100%;
  max-width: 900px;
  background: var(--surface);
  border-radius: 16px;
  padding: 32px 32px 28px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.15);
}

/* Header bar */
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

/* Form fields */
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

/* Helper text under fields */
.helper {
  font-size: 14px;
  color: var(--muted);
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
</style>
</head>
<body>
<div class="page">

  <!-- Header -->
  <div class="header-bar">
    <div>
      <h1 class="page-title">Add Enrollment</h1>
      <div class="page-sub">
        Create a new enrollment by selecting a student, course, date and status.
      </div>
    </div>
    <div class="user-box">
      <strong><?= htmlspecialchars($user['name']) ?></strong><br>
      <span class="user-role"><?= htmlspecialchars($user['role']) ?></span><br>
      <a href="index.php" class="btn-top">Back to list</a>
    </div>
  </div>

  <!-- Flash messages (for example, validation errors from validate.php) -->
  
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


  <!-- ADD FORM
       This form posts to validate.php, which will validate and then call store.php -->
  <form method="post" action="validate.php">
    <!-- Student -->
    <div class="field">
      <label for="student">Student</label>
      <select class="select" id="student" name="StudentID" required>
        <option value="">Select a student…</option>
        <?php foreach ($students as $stu): ?>
          <option value="<?= (int)$stu['StudentID'] ?>">
            <?= htmlspecialchars($stu['FullName']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="helper">Choose the student you want to enroll.</div>
    </div>

    <!-- Course -->
    <div class="field">
      <label for="course">Course</label>
      <select class="select" id="course" name="CourseID" required>
        <option value="">Select a course…</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= (int)$c['CourseID'] ?>">
            <?= htmlspecialchars($c['CourseCode'] . ' — ' . $c['CourseName']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="helper">Select the course the student will be enrolled in.</div>
    </div>

    <!-- Enrollment Date -->
    <div class="field">
      <label for="date">Enrollment date</label>
      <input
        class="input"
        type="date"
        id="date"
        name="EnrollmentDate"
        value="<?= htmlspecialchars($today) ?>"
        required
      >
      <div class="helper">The date when the enrollment is created.</div>
    </div>

    <!-- Status -->
    <div class="field">
      <label for="status">Status</label>
      <select class="select" id="status" name="Status" required>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= $s ?>" <?= $s === 'registered' ? 'selected' : '' ?>>
            <?= ucfirst($s) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="helper">
        For a new enrollment this is usually <strong>registered</strong> or <strong>in-progress</strong>.
      </div>
    </div>

    <!-- Grade (optional) -->
    <div class="field">
      <label for="grade">Final grade (optional)</label>
      <input
        class="input"
        type="text"
        id="grade"
        name="Grade"
        placeholder="e.g. A, B+, 75"
      >
      <div class="helper">Leave blank if the course is not completed yet.</div>
    </div>

    <!-- Buttons -->
    <div class="actions-row">
      <a href="index.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Save enrollment</button>
    </div>
  </form>
</div>
</body>
</html>
