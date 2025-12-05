<?php
// --------------------------------------------------------
// ERROR REPORTING (DEV ONLY)
// Shows all errors to help debugging during development.
// --------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --------------------------------------------------------
// ACCESS CONTROL — ONLY ADMIN CAN ACCESS
// Protects this page so only Admin role can open it.
// --------------------------------------------------------
require_once '../includes/auth_check.php';

if ($_SESSION['role'] !== 'Admin') {
    die("Access denied. Admin only.");
}

// --------------------------------------------------------
// DB + AUTH MODEL
// Loads the DB connection + createUser() function.
// --------------------------------------------------------
require_once '../includes/db.php';
require_once '../includes/auth_model.php';

$pdo = getDB();        // DB connection
$errors = [];          // Holds validation error messages
$success = "";         // Holds success message for UI

// --------------------------------------------------------
// LOAD ALL COURSES
// Fetch all available courses for dropdown.
// --------------------------------------------------------
try {
    $courseStmt = $pdo->query("
        SELECT CourseID, CourseName 
        FROM Course 
        ORDER BY CourseName ASC
    ");
    $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $courses = [];
}

// --------------------------------------------------------
// INITIAL EMPTY FORM VALUES
// Used so form resets after success.
// --------------------------------------------------------
$username = "";
$password = "";
$firstname = "";
$lastname = "";
$email = "";
$courseid = "";
$coursename = "";
$salary = "";
$hiredate = date('Y-m-d');

// --------------------------------------------------------
// PROCESS FORM SUBMISSION
// Executes when admin submits the form.
// --------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Normalize POST keys
    $data = [];
    foreach ($_POST as $key => $value) {
        $data[strtolower($key)] = trim($value);
    }

    // Extract form values
    $username   = $data['username'] ?? '';
    $password   = $data['password'] ?? '';
    $firstname  = $data['firstname'] ?? '';
    $lastname   = $data['lastname'] ?? '';
    $email      = $data['email'] ?? '';
    $courseid   = $data['courseid'] ?? '';
    $coursename = $data['coursename'] ?? '';
    $salary     = $data['salary'] ?? '';
    $hiredate   = $data['hiredate'] ?? '';

    // --------------------------------------------------------
    // VALIDATION
    // Ensures all required fields are filled.
// --------------------------------------------------------
    if (empty($username))   $errors[] = "Username is required.";
    if (empty($password))   $errors[] = "Password is required.";
    if (empty($firstname))  $errors[] = "First name is required.";
    if (empty($lastname))   $errors[] = "Last name is required.";
    if (empty($email))      $errors[] = "Email is required.";
    if (empty($courseid))   $errors[] = "Please select a course.";
    if (empty($hiredate))   $errors[] = "Hire date is required.";

    // Only proceed if no errors
    if (empty($errors)) {
        try {
            // --------------------------------------------------------
            // CHECK USERNAME UNIQUE
            // --------------------------------------------------------
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM User WHERE Username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("⚠ Username already exists.");
            }

            // --------------------------------------------------------
            // CHECK EMAIL UNIQUE IN STAFF TABLE
            // --------------------------------------------------------
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Staff WHERE Email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("⚠ Email already exists.");
            }

            // --------------------------------------------------------
            // CREATE USER (FIX ADDED: Email included)
            // Now stores email into User table as well.
// --------------------------------------------------------
            $userId = createUser($username, $password, 'Staff', $email);

            if (!$userId) {
                throw new Exception("Failed to create user.");
            }

            // --------------------------------------------------------
            // INSERT STAFF RECORD
            // --------------------------------------------------------
            $stmt = $pdo->prepare("
                INSERT INTO Staff (
                    UserID, FirstName, LastName, Email,
                    CourseID, CourseName, Salary, HireDate, IsActive
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");

            $stmt->execute([
                $userId,
                ucfirst($firstname),
                ucfirst($lastname),
                $email,
                $courseid,
                $coursename,
                is_numeric($salary) ? $salary : 0,
                $hiredate
            ]);

            // --------------------------------------------------------
            // SUCCESS MESSAGE SHOWN ON SAME PAGE
            // --------------------------------------------------------
            $success = "✅ Staff member <b>$firstname $lastname</b> created successfully!";

            // --------------------------------------------------------
            // CLEAR FORM AFTER SUCCESS
            // --------------------------------------------------------
            $username = "";
            $password = "";
            $firstname = "";
            $lastname = "";
            $email = "";
            $courseid = "";
            $coursename = "";
            $salary = "";
            $hiredate = date('Y-m-d');

        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Add New Staff Member</title>

<link rel="stylesheet" href="../assets/style.css">

<style>
/* Page background & fonts */
body {
    font-family: "Poppins", sans-serif;
    background: linear-gradient(135deg, #182848, #4b6cb7);
    color: white;
}

/* Main container */
.container {
    background: rgba(255,255,255,0.08);
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 900px;
    margin: 50px auto;
}

/* Heading */
h1 {
    color: #f1c40f;
    text-align:center;
    margin-top: 5px;
}

/* Top-right back button container */
.back-container {
    width: 100%;
    text-align: right;
    margin-bottom: 10px;
}

/* Form labels */
label { display:block; margin-top:12px; }

/* Inputs & selects */
input, select {
    width:100%;
    padding:10px;
    border-radius:6px;
    border:none;
    margin-top:5px;
}

/* Buttons */
.btn-create {
    background:#27ae60;
    padding:10px 20px;
    border-radius:6px;
    border:none;
    margin-top:15px;
    color:white;
}
.btn-create:hover { background:#1e8449; }

.btn-cancel {
    background:none;
    border:2px solid #e74c3c;
    padding:10px 20px;
    border-radius:6px;
    margin-left:10px;
    color:#e74c3c;
}
.btn-cancel:hover { background:#e74c3c; color:white; }

.btn-back {
    background:none;
    border:2px solid #3498db;
    padding:6px 14px;
    border-radius:6px;
    color:#3498db;
    text-decoration:none;
    font-size: 14px;
}
.btn-back:hover { background:#3498db; color:white; }

/* Alerts */
.alert-success {
    background: #2ecc71;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    color: white;
}
.alert-danger {
    background: #e74c3c;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    color: white;
}
</style>
</head>

<body>
<div class="container">

<!-- Back button top-right -->
<div class="back-container">
    <a href="staff_list.php" class="btn-back">← Back</a>
</div>

<h1>Add New Staff Member</h1>

<!-- SUCCESS MESSAGE -->
<?php if ($success): ?>
<div class="alert-success"><?= $success ?></div>
<?php endif; ?>

<!-- ERROR MESSAGES -->
<?php if (!empty($errors)): ?>
<div class="alert-danger">
    <h3>⚠ Please fix the following:</h3>
    <ul>
        <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- STAFF CREATION FORM -->
<form method="POST">

    <!-- Username -->
    <label>Username *</label>
    <input type="text" name="Username" value="<?= htmlspecialchars($username) ?>" required>

    <!-- Password -->
    <label>Password *</label>
    <input type="password" name="Password" required>

    <!-- First Name -->
    <label>First Name *</label>
    <input type="text" name="FirstName" value="<?= htmlspecialchars($firstname) ?>" required>

    <!-- Last Name -->
    <label>Last Name *</label>
    <input type="text" name="LastName" value="<?= htmlspecialchars($lastname) ?>" required>

    <!-- Email -->
    <label>Email *</label>
    <input type="email" name="Email" value="<?= htmlspecialchars($email) ?>" required>

    <!-- Course Dropdown -->
    <label>Assign Course *</label>
    <select name="CourseID" id="courseSelect" required>
        <option value="">Select Course</option>
        <?php foreach ($courses as $c): ?>
            <option value="<?= $c['CourseID'] ?>"
                <?= ($courseid == $c['CourseID']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['CourseName']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- Hidden CourseName -->
    <input type="hidden" name="CourseName" id="CourseNameField" value="<?= htmlspecialchars($coursename) ?>">

    <!-- Salary -->
    <label>Salary (DKK)</label>
    <input type="number" name="Salary" min="0" step="0.01" value="<?= htmlspecialchars($salary) ?>">

    <!-- Hire Date -->
    <label>Hire Date *</label>
    <input type="date" name="HireDate" value="<?= htmlspecialchars($hiredate) ?>" required>

    <!-- Submit -->
    <button type="submit" class="btn-create">Create Staff Member</button>

    <!-- Cancel -->
    <a href="staff_list.php" class="btn-cancel">Cancel</a>

</form>
</div>

<script>
// Auto-fill CourseName field when course is selected
document.getElementById("courseSelect").addEventListener("change", function() {
    document.getElementById("CourseNameField").value =
        this.options[this.selectedIndex].text;
});
</script>

</body>
</html>
