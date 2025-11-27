<?php
session_start();

// load access control //
require_once '../includes/auth_check.php';
// load user functions //
require_once '../includes/user_model.php';
// load validation helpers //
require_once '../includes/validation.php';

// only admin can create user //
requireRole(['Admin']);

$message = ""; // store feedback message //

// check if email exists in user table //
function emailExists($email) {
    $pdo = getDB(); // connect db //
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE Email = ?");
    $stmt->execute([$email]);
    return $stmt->fetchColumn() > 0; // true if exists //
}

// when form submitted //
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? ''); // get username //
    $password = trim($_POST['password'] ?? ''); // get password //
    $role     = $_POST['role'] ?? '';          // get role //

    $errors = []; // error container //
    $extra  = []; // role-specific container //

    // validate username //
    if (!validateUsername($username)) {
        $errors[] = "Username must be 4–20 characters.";
    }

    // validate role //
    if (!validateRole($role)) {
        $errors[] = "Invalid role.";
    }

    // validate password strength //
    if (!validatePassword($password)) {
        $errors[] = "Password must be strong (upper, lower, number).";
    }

    //-----------------------------------------------------
    // ADMIN FIELDS
    //-----------------------------------------------------
    if ($role === 'Admin') {

        $adminEmail = trim($_POST['admin_email'] ?? ''); // read admin email //

        if (!validateEmail($adminEmail)) {
            $errors[] = "Admin email must end with @school.edu.";
        }

        if (emailExists($adminEmail)) {
            $errors[] = "Email already registered.";
        }

        $extra['email'] = $adminEmail; // save admin email //
    }

    //-----------------------------------------------------
    // STAFF FIELDS
    //-----------------------------------------------------
    if ($role === 'Staff') {

        $firstName  = trim($_POST['first_name'] ?? '');
        $lastName   = trim($_POST['last_name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $email      = filter_var($email, FILTER_SANITIZE_EMAIL);
        $department = trim($_POST['department'] ?? '');
        $salary     = trim($_POST['salary'] ?? '');
        $hireDate   = trim($_POST['hire_date'] ?? '');

        // required check //
        if ($firstName === '' || $lastName === '' || $email === '' ||
            $department === '' || $salary === '' || $hireDate === '') {
            $errors[] = "All staff fields are required.";
        }

        // validate names //
        if (!validateName($firstName)) $errors[] = "Invalid first name.";
        if (!validateName($lastName))  $errors[] = "Invalid last name.";

        // validate email //
        if (!validateEmail($email))          $errors[] = "Staff email invalid.";
        if (emailExists($email))             $errors[] = "Email already registered.";

        // validate department //
        if (!validateDepartment($department)) $errors[] = "Invalid department.";

        // validate salary //
        if (!validateSalary($salary)) $errors[] = "Invalid salary.";

        // validate hire date //
        if (!validateHireDate($hireDate)) $errors[] = "Invalid hire date.";

        // store staff fields //
        $extra = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'department' => $department,
            'salary'     => floatval($salary),
            'hire_date'  => $hireDate
        ];
    }

    //-----------------------------------------------------
    // STUDENT FIELDS
    //-----------------------------------------------------
    if ($role === 'Student') {

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $email     = filter_var($email, FILTER_SANITIZE_EMAIL);
        $dob       = trim($_POST['dob'] ?? '');
        $age       = trim($_POST['age'] ?? '');
        $gpa       = trim($_POST['gpa'] ?? '');

        // required fields //
        if ($firstName === '' || $lastName === '' || $email === '' || $dob === '' || $age === '') {
            $errors[] = "All student fields except GPA must be filled.";
        }

        // validate names //
        if (!validateName($firstName)) $errors[] = "Invalid first name.";
        if (!validateName($lastName))  $errors[] = "Invalid last name.";

        // validate email //
        if (!validateEmail($email)) $errors[] = "Student email invalid.";
        if (emailExists($email))     $errors[] = "Email already registered.";

        // validate DOB //
        if (!validateDOB($dob)) $errors[] = "Invalid DOB.";

        // validate age //
        if (!validateAge($age)) $errors[] = "Invalid age.";

        // validate GPA //
        if (!validateGPA($gpa)) $errors[] = "Invalid GPA (0.0 - 4.0).";

        // store student fields //
        $extra = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'dob'        => $dob,
            'age'        => intval($age),
            'gpa'        => ($gpa === '' ? null : floatval($gpa))
        ];
    }

    //-----------------------------------------------------
    // show errors if any
    //-----------------------------------------------------
    if (!empty($errors)) {
        $message = "<div class='alert alert-danger'><strong>Error:</strong><br>" .
                    implode("<br>", $errors) . "</div>";
    }

    //-----------------------------------------------------
    // redirect to PIN verification if no errors
    //-----------------------------------------------------
    else {

        echo "<form id='redirectPIN' method='POST' action='verify_pin_action.php'>";

        echo "<input type='hidden' name='action' value='create'>";
        echo "<input type='hidden' name='username' value='".htmlspecialchars($username)."'>";
        echo "<input type='hidden' name='password' value='".htmlspecialchars($password)."'>";
        echo "<input type='hidden' name='role' value='".htmlspecialchars($role)."'>";

        foreach ($extra as $key => $val) {
            echo "<input type='hidden' name='extra[$key]' value='".htmlspecialchars($val)."'>";
        }

        echo "</form>";

        echo "<script>document.getElementById('redirectPIN').submit();</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add User</title> <!-- page title -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* page bg */
body {
    background: linear-gradient(135deg,#212e68,#9e69d3);
    padding: 40px;
    min-height: 100vh;
    font-family: 'Poppins',sans-serif;
}

/* form box */
.form-box {
    max-width: 650px;
    margin: auto;
    background: linear-gradient(135deg,#6c8fd6,#4b6cb7);
    padding: 30px;
    border-radius: 15px;
    color: white;
}
</style>
</head>

<body>

<div class="form-box">

<h1 class="text-center text-warning">Add New User</h1>

<?= $message ?> <!-- show validation messages -->

<form method="POST">

    <!-- USERNAME -->
    <label>Username *</label> <!-- fixed label -->
    <input type="text"
           name="username"
           class="form-control mb-2"
           placeholder="e.g., john_doe21"
           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

    <!-- PASSWORD -->
    <label>Password *</label> <!-- FIXED: correct label -->
    <input type="password"
           name="password"
           class="form-control mb-2"
           autocomplete="new-password"
           placeholder="Min 8 chars: Upper, lower & number">

    <!-- ROLE -->
    <label>Role *</label>
    <select name="role" id="roleSelect" class="form-select mb-2">
        <option value="">-- Select Role --</option>
        <option value="Admin"   <?= ($_POST['role'] ?? '') === "Admin" ? "selected" : "" ?>>Admin</option>
        <option value="Staff"   <?= ($_POST['role'] ?? '') === "Staff" ? "selected" : "" ?>>Staff</option>
        <option value="Student" <?= ($_POST['role'] ?? '') === "Student" ? "selected" : "" ?>>Student</option>
    </select>

    <!-- ADMIN SECTION -->
    <div id="adminSection" style="display:none;">
        <label>Admin Email</label>
        <input type="email"
               name="admin_email"
               class="form-control mb-2"
               placeholder="e.g., admin@school.edu"
               value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>">
    </div>

    <!-- COMMON section -->
    <div id="commonProfileSection" style="display:none;">

        <label>First Name</label>
        <input type="text" name="first_name" class="form-control mb-2"
               placeholder="e.g., Michael"
               value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">

        <label>Last Name</label>
        <input type="text" name="last_name" class="form-control mb-2"
               placeholder="e.g., Johnson"
               value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">

        <label>Email</label>
        <input type="email" name="email" class="form-control mb-2"
               placeholder="e.g., user@school.edu"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

    </div>

    <!-- STAFF -->
    <div id="staffSection" style="display:none;">

        <label>Department</label>
        <input type="text" name="department" class="form-control mb-2"
               placeholder="e.g., Computer Science"
               value="<?= htmlspecialchars($_POST['department'] ?? '') ?>">

        <label>Salary</label>
        <input type="number" step="0.01" name="salary" class="form-control mb-2"
               placeholder="e.g., 50000"
               value="<?= htmlspecialchars($_POST['salary'] ?? '') ?>">

        <label>Hire Date</label>
        <input type="date" name="hire_date" class="form-control mb-2"
               value="<?= htmlspecialchars($_POST['hire_date'] ?? '') ?>">

    </div>

    <!-- STUDENT -->
    <div id="studentSection" style="display:none;">

        <label>Date of Birth</label>
        <input type="date" name="dob" class="form-control mb-2"
               value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">

        <label>Age</label>
        <input type="number" name="age" class="form-control mb-2"
               placeholder="e.g., 19"
               value="<?= htmlspecialchars($_POST['age'] ?? '') ?>">

        <label>GPA (optional)</label>
        <input type="number" step="0.01" name="gpa" class="form-control mb-2"
               placeholder="e.g., 3.75"
               value="<?= htmlspecialchars($_POST['gpa'] ?? '') ?>">

    </div>

    <!-- SUBMIT -->
    <button type="submit" class="btn btn-success w-100 mt-3">Create User</button>

    <!-- BACK -->
    <a href="manage_user.php" class="btn btn-warning w-100 mt-3">← Back</a>

</form>

</div>

<script>
// handle role section visibility //
const roleSelect = document.getElementById('roleSelect');
const adminSection = document.getElementById('adminSection');
const commonSection = document.getElementById('commonProfileSection');
const staffSection = document.getElementById('staffSection');
const studentSection = document.getElementById('studentSection');

function updateSections() {
    adminSection.style.display = 'none';
    commonSection.style.display = 'none';
    staffSection.style.display = 'none';
    studentSection.style.display = 'none';

    const val = roleSelect.value;

    if (val === 'Admin') adminSection.style.display = 'block';
    if (val === 'Staff') {
        commonSection.style.display = 'block';
        staffSection.style.display  = 'block';
    }
    if (val === 'Student') {
        commonSection.style.display = 'block';
        studentSection.style.display = 'block';
    }
}

roleSelect.addEventListener('change', updateSections);
updateSections(); // run on page load
</script>

</body>
</html>
