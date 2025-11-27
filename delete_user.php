<?php
session_start(); 
// start session so we can read current admin user id //

require_once '../includes/auth_check.php'; 
// include authentication check to restrict access //

require_once '../includes/user_model.php'; 
// include user model for getUserById and deleteUser functions //

require_once '../includes/db.php'; 
// include database connection script //

require_once '../includes/audit.php'; 
// include audit logger to record admin actions in AuditLogs table //

requireRole(['Admin']); 
// allow only admin role to open delete_user page //

$pdo = getDB(); 
// create PDO database connection //

$id = intval($_GET['id'] ?? 0); 
// read id of user to delete from url //

$user = getUserById($id); 
// fetch main user record from user table //

if (!$user) { 
    // stop if user not found in database //
    die("User not found!"); 
}

$profile = null; 
// variable to store staff or student profile data for display in UI //

// load staff profile if user role is staff //
if ($user['Role'] === "Staff") {
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE UserID=?"); 
    // prepare query for staff table //
    $stmt->execute([$id]); 
    // execute staff query //
    $profile = $stmt->fetch(PDO::FETCH_ASSOC); 
    // fetch staff profile row if available //
}

// load student profile if user role is student //
if ($user['Role'] === "Student") {
    $stmt = $pdo->prepare("SELECT * FROM student WHERE UserID=?"); 
    // prepare query for student table //
    $stmt->execute([$id]); 
    // execute student query //
    $profile = $stmt->fetch(PDO::FETCH_ASSOC); 
    // fetch student profile row if available //
}

$deleted  = false; 
// flag to track if user was deleted successfully //

$pinError = ""; 
// message to display if admin enters wrong pin or pin not found //

// process delete request when form is submitted //
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $enteredPin = trim($_POST['admin_pin'] ?? ''); 
    // read admin pin from form input //

    $adminUserID = $_SESSION['userID']; 
    // get current logged in admin user id from session //

    $stmt = $pdo->prepare("SELECT AdminPINHash FROM admin WHERE UserID = ?"); 
    // prepare query to fetch admin pin hash for current admin //

    $stmt->execute([$adminUserID]); 
    // execute pin lookup query //

    $adminRow = $stmt->fetch(PDO::FETCH_ASSOC); 
    // fetch admin row from admin table //

    if (!$adminRow) {
        // if admin row not found, log this as a system problem //
        logAction($adminUserID, "Admin PIN missing for delete", "admin", $adminUserID, "Admin row missing while deleting user ID {$id}");
        $pinError = "Your admin PIN is not set."; 
        // show error when no PIN exists for this admin //
    } else {

        $storedHash = $adminRow['AdminPINHash']; 
        // store the admin pin hash from database //

        if (password_verify($enteredPin, $storedHash)) { 
            // verify that entered pin matches stored hash //

            // log that pin verification succeeded before deletion //
            logAction(
                $adminUserID,
                "Admin PIN verified for delete user",
                "user",
                $id,
                "Admin verified PIN to delete user '{$user['Username']}' (Role: {$user['Role']}, Email: {$user['Email']})"
            );

            // call deleteUser model function to remove user //
            deleteUser($id); 

            // log that user account has been deleted //
            logAction(
                $adminUserID,
                "User deleted",
                "user",
                $id,
                "Deleted user '{$user['Username']}' with role '{$user['Role']}' and email '{$user['Email']}'"
            );

            $deleted = true; 
            // mark deletion successful so UI can show confirmation //

        } else {
            // log failed admin pin attempt for security audit //
            logAction(
                $adminUserID,
                "Failed admin PIN for delete user",
                "user",
                $id,
                "Wrong PIN while trying to delete user '{$user['Username']}' (UserID: {$id})"
            );

            $pinError = "Incorrect admin PIN."; 
            // show message in UI when pin is wrong //
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Delete User</title> 
<!-- title for delete user page -->

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> 
<!-- include bootstrap cdn for styles -->

<style>
body {
    background: linear-gradient(135deg, #212e68, #9e69d3);
    /* gradient background for full page */
    font-family: Poppins, sans-serif;
    padding: 40px;
    min-height: 100vh;
}
.box {
    max-width: 650px;
    margin: auto;
    background: linear-gradient(135deg, #6c8fd6, #4b6cb7);
    padding: 35px;
    border-radius: 15px;
    color: white;
    box-shadow: 0 10px 40px rgba(0,0,0,0.4);
    /* main card container style */
}
h2 {
    text-align: center;
    color: #f1c40f;
    font-weight: 600;
    margin-bottom: 25px;
    /* heading style */
}
.section-title {
    font-size: 17px;
    font-weight: 600;
    color: #ffd966;
    margin-top: 15px;
    margin-bottom: 10px;
    border-bottom: 1px solid rgba(255,255,255,0.4);
    padding-bottom: 5px;
    /* section header for info group */
}
.info-line {
    font-size: 15px;
    margin-bottom: 6px;
    /* small info text line for labels and values */
}
.warning-box {
    background: rgba(255, 0, 0, 0.2);
    border-left: 5px solid #e74c3c;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    /* visual warning box before delete */
}
.success-box {
    background: rgba(0,255,0,0.25);
    border-left: 5px solid #2ecc71;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    /* success message box after delete */
}
.btn-danger {
    background: #e74c3c;
    border: none;
    border-radius: 8px;
    /* delete button style */
}
.btn-danger:hover {
    background: #c0392b;
    /* delete button hover color */
}
.btn-secondary {
    background: #95a5a6;
    border-radius: 8px;
    /* cancel or back button style */
}
.btn-secondary:hover {
    background: #7f8c8d;
}
.pin-error {
    color: #ffb3b3;
    font-weight: bold;
    margin-bottom: 10px;
    /* style for pin error message text */
}
</style>
</head>

<body>

<div class="box">
    
<?php if ($deleted): ?>
    <!-- show this block only when user has been deleted -->

    <h2>User Deleted</h2>

    <div class="success-box">
        ✅ <strong><?= htmlspecialchars($user['Username']) ?></strong> has been removed from the system.
        <!-- message confirming account deletion -->
    </div>

    <a href="manage_user.php" class="btn btn-secondary w-100">← Back to User Directory</a>
    <!-- link to return back to manage user page -->

<?php else: ?>

    <h2>Delete User</h2>

    <div class="warning-box">
        ⚠️ <strong>Warning:</strong> This action cannot be undone.
        <!-- warning that delete is permanent -->
    </div>

    <div class="section-title">Account Information</div>
    <!-- header for account info section -->

    <p class="info-line"><strong>User ID:</strong> <?= $user['UserID'] ?></p>
    <p class="info-line"><strong>Username:</strong> <?= htmlspecialchars($user['Username']) ?></p>
    <p class="info-line"><strong>Email:</strong> <?= htmlspecialchars($user['Email']) ?></p>
    <p class="info-line"><strong>Role:</strong> <?= htmlspecialchars($user['Role']) ?></p>
    <p class="info-line"><strong>Status:</strong> <?= $user['IsActive'] ? "Active" : "Inactive" ?></p>

    <?php if ($user['Role'] === "Staff" && $profile): ?>
        <div class="section-title">Staff Profile</div>
        <!-- extra profile information for staff user -->
        <p class="info-line"><strong>Name:</strong> <?= $profile['FirstName'] ?> <?= $profile['LastName'] ?></p>
        <p class="info-line"><strong>Department:</strong> <?= $profile['Department'] ?></p>
        <p class="info-line"><strong>Salary:</strong> <?= $profile['Salary'] ?></p>
        <p class="info-line"><strong>Hire Date:</strong> <?= $profile['HireDate'] ?></p>
    <?php endif; ?>

    <?php if ($user['Role'] === "Student" && $profile): ?>
        <div class="section-title">Student Profile</div>
        <!-- extra profile information for student user -->
        <p class="info-line"><strong>Name:</strong> <?= $profile['FirstName'] ?> <?= $profile['LastName'] ?></p>
        <p class="info-line"><strong>Date of Birth:</strong> <?= $profile['DateOfBirth'] ?></p>
        <p class="info-line"><strong>Age:</strong> <?= $profile['Age'] ?></p>
        <p class="info-line"><strong>GPA:</strong> <?= $profile['GPA'] ?></p>
    <?php endif; ?>

    <?php if ($pinError): ?>
        <div class="pin-error">❌ <?= $pinError ?></div>
        <!-- display error message if pin is wrong or missing -->
    <?php endif; ?>

    <form method="POST" class="mt-3">
        <!-- form where admin enters pin to confirm delete -->

        <label><strong>Enter Admin PIN to Confirm Deletion</strong></label>
        <input type="password"
               name="admin_pin"
               class="form-control mb-3"
               placeholder="Enter your 6-digit admin PIN"
               required>
        <!-- password field to input admin pin for delete permission -->

        <button name="confirm_delete" class="btn btn-danger w-100 mb-3">
            ❌ Delete User Permanently
            <!-- button that triggers deletion logic -->
        </button>
    </form>

    <a href="manage_user.php" class="btn btn-secondary w-100">Cancel</a>
    <!-- button to go back without deleting user -->

<?php endif; ?>

</div>

</body>
</html>
