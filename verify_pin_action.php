<?php
session_start(); // start session for current admin

// Load required files // auth, db, user model, validation, audit
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
require_once '../includes/user_model.php';
require_once '../includes/validation.php';
require_once '../includes/audit.php';

// Only admins can confirm actions with PIN
requireRole(['Admin']);

$pdo = getDB(); // get PDO connection
$adminID = $_SESSION['userID']; // acting admin user ID
$message = ""; // feedback message for result

// Fetch acting admin PIN hash from admin table
$stmt = $pdo->prepare("SELECT AdminPINHash FROM admin WHERE UserID = ?");
$stmt->execute([$adminID]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// If admin row not found, stop processing
if (!$admin) {
    die("Admin record not found.");
}

// Handle form submission from forwarded pages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // PIN entered by acting admin
    $pin    = trim($_POST['admin_pin'] ?? '');

    // Action type: "create", "update", "delete"
    $action = trim($_POST['action'] ?? '');

    // Data for create / update / delete actions
    $extra       = $_POST['extra'] ?? []; // used for create staff/student/admin
    $role        = $_POST['role'] ?? '';  // user role
    $username    = $_POST['username'] ?? ''; // username to create or update
    $password    = $_POST['password'] ?? ''; // password for create or update
    $targetUser  = $_POST['target_user'] ?? ''; // ID of user to update/delete
    $isActive    = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0; // status on update

    // Optional new admin PIN when editing an admin user
    $newAdminPin = $_POST['new_admin_pin'] ?? '';

    // Validate acting admin PIN format // must be exactly 6 digits
    if (!validateAdminPIN($pin)) {
        $message = "<div class='alert alert-danger'>❌ PIN must be exactly 6 digits.</div>";
    }
    // Verify PIN against stored hash
    elseif (!password_verify($pin, $admin['AdminPINHash'])) {
        $message = "<div class='alert alert-danger'>❌ Incorrect Admin PIN.</div>";
        logAction($adminID, "PIN Failed", "Admin", null, "Incorrect PIN at verify_pin_action");
    }
    else {

        // ===========================================================
        // EXECUTE REQUESTED ACTION AFTER SUCCESSFUL PIN
        // ===========================================================

        if ($action === "create") {
            // Create a new user using existing createUser model
            $result = createUser($username, $password, $role, $extra);

            if ($result === "exists") {
                $message = "<div class='alert alert-danger'>⚠ Username already exists.</div>";
            } elseif ($result) {
                $message = "<div class='alert alert-success'>✅ User created successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger'>❌ Failed to create user.</div>";
            }
        }

        elseif ($action === "update") {
            // Update existing user // password may be blank (keep old) or new
            $result = updateUser($targetUser, $username, $password, $role, $isActive);

            if ($result) {
                $message = "<div class='alert alert-success'>✅ User updated successfully!</div>";

                // If the final role is Admin and a new admin PIN was provided
                if ($role === 'Admin' && $newAdminPin !== '') {
                    // Extra check: validate PIN format again for safety
                    if (validateAdminPIN($newAdminPin)) {

                        // Hash new PIN using password_hash // same logic as change_pin.php
                        $newPinHash = password_hash($newAdminPin, PASSWORD_DEFAULT);

                        // Update admin table with new PIN and reset lock/attempts
                        $pinStmt = $pdo->prepare("
                            UPDATE admin 
                            SET AdminPINHash = ?, 
                                FailedPinAttempts = 0,
                                PinLastChanged = NOW(),
                                PinLockUntil = NULL
                            WHERE UserID = ?
                        ");
                        $pinStmt->execute([$newPinHash, $targetUser]);

                        $message .= "<div class='alert alert-info mt-2'>🔐 Admin PIN updated successfully for this user.</div>";
                        logAction($adminID, "Admin PIN changed", "Admin", $targetUser, "PIN updated via edit_user + PIN confirm");
                    } else {
                        $message .= "<div class='alert alert-warning mt-2'>⚠ User updated, but new PIN was invalid and not changed.</div>";
                    }
                }

            } else {
                $message = "<div class='alert alert-danger'>❌ Failed to update user.</div>";
            }
        }

        elseif ($action === "delete") {
            // Delete user using model function
            $result = deleteUser($targetUser);

            if ($result) {
                $message = "<div class='alert alert-success'>🗑 User deleted successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger'>❌ Failed to delete user.</div>";
            }
        }

        // Log action in audit trail
        logAction($adminID, "Admin Action: $action", "Admin", $targetUser ?: null, "Action executed via PIN confirmation");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin PIN Verification</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="background:#243B55; padding:40px; font-family:Poppins,sans-serif;">

<div class="card p-4" style="max-width:450px; margin:auto;">

    <h3 class="text-center mb-3">Enter Admin PIN</h3>

    <?= $message ?> <!-- show error / success -->

    <form method="POST">

        <!-- Keep action type -->
        <input type="hidden" name="action" value="<?= htmlspecialchars($_POST['action'] ?? '') ?>">
        <!-- Keep username -->
        <input type="hidden" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        <!-- Keep password -->
        <input type="hidden" name="password" value="<?= htmlspecialchars($_POST['password'] ?? '') ?>">
        <!-- Keep role -->
        <input type="hidden" name="role" value="<?= htmlspecialchars($_POST['role'] ?? '') ?>">
        <!-- Keep target user ID -->
        <input type="hidden" name="target_user" value="<?= htmlspecialchars($_POST['target_user'] ?? '') ?>">
        <!-- Keep is_active flag -->
        <input type="hidden" name="is_active" value="<?= htmlspecialchars($_POST['is_active'] ?? '0') ?>">
        <!-- Keep optional new admin pin -->
        <input type="hidden" name="new_admin_pin" value="<?= htmlspecialchars($_POST['new_admin_pin'] ?? '') ?>">

        <?php
        // Preserve extra data for create user (admin/staff/student details)
        if (!empty($_POST['extra']) && is_array($_POST['extra'])) {
            foreach ($_POST['extra'] as $k => $v) {
                echo "<input type='hidden' name='extra[" . htmlspecialchars($k) . "]' value='" . htmlspecialchars($v) . "'>";
            }
        }
        ?>

        <!-- Admin PIN input -->
        <label>Enter 6-Digit Admin PIN</label>
        <input type="password" maxlength="6" name="admin_pin" class="form-control" placeholder="******" required>

        <button class="btn btn-warning w-100 mt-3">Verify PIN</button>
    </form>

    <!-- Back to manage users -->
    <a href="manage_user.php" class="btn btn-secondary w-100 mt-3">← Back to Manage Users</a>

</div>

</body>
</html>
