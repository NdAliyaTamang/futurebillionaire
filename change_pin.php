<?php
session_start(); // Start session to access logged-in user info

// Load authentication, database, and audit logging files
require_once '../includes/auth_check.php';   // Ensures only allowed roles access this page
require_once '../includes/db.php';           // Database connection file
require_once '../includes/audit.php';        // For audit logging of PIN changes

// Only Admin users are allowed to access this page
requireRole(['Admin']); // Block Staff + Student

$pdo    = getDB();       // Get database connection
$userID = $_SESSION['userID']; // Current admin user ID
$message = "";           // Display success/error messages
$locked = false;         // Boolean to detect temporary PIN lock status

// ================================================
// FETCH ADMIN DETAILS INCLUDING ATTEMPTS + LOCK INFO
// ================================================
$stmt = $pdo->prepare("
    SELECT AdminPINHash, FailedPinAttempts, PinLastChanged, PinLockUntil
    FROM admin WHERE UserID = ?
");
$stmt->execute([$userID]); // Execute fetch query
$admin = $stmt->fetch(PDO::FETCH_ASSOC); // Store admin data

// ================================================
// CHECK IF PIN CHANGE IS LOCKED DUE TO TOO MANY FAILS
// ================================================
if (!empty($admin['PinLockUntil']) && strtotime($admin['PinLockUntil']) > time()) {
    $locked = true; // Mark page as locked

    // Calculate how many minutes remain in the lock
    $remaining = strtotime($admin['PinLockUntil']) - time();
    $minutes = ceil($remaining / 60);

    // Show lock message
    $message = "<div class='alert alert-danger'>
                    ❌ Too many incorrect attempts.<br>
                    Try again in <strong>{$minutes} minutes</strong>.
                </div>";
}

// ================================================
// HANDLE PIN CHANGE SUBMISSION
// ================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {

    // Extract submitted form input
    $oldPin = trim($_POST['old_pin']);               // Old PIN entered by user
    $newPin = trim($_POST['new_pin']);               // New PIN
    $confirmPin = trim($_POST['confirm_pin']);       // Confirm PIN

    // Validate new PIN format (exact 6 digits)
    if (!preg_match("/^[0-9]{6}$/", $newPin)) {
        $message = "<div class='alert alert-danger'>❌ New PIN must be exactly 6 digits.</div>";
    }
    elseif ($newPin !== $confirmPin) {
        // Confirm PIN must match new PIN
        $message = "<div class='alert alert-danger'>❌ New PIN and Confirm PIN do not match.</div>";
    }
    else {
        // Verify old PIN matches stored hashed PIN
        if (!password_verify($oldPin, $admin['AdminPINHash'])) {

            // Increase failed attempt count
            $newAttempts = $admin['FailedPinAttempts'] + 1;

            // If attempts reach 3, lock PIN change for 10 minutes
            if ($newAttempts >= 3) {
                $lockTime = date("Y-m-d H:i:s", time() + 600); // Add 10 minutes

                $pdo->prepare("UPDATE admin SET FailedPinAttempts=0, PinLockUntil=? WHERE UserID=?")
                    ->execute([$lockTime, $userID]);

                $message = "<div class='alert alert-danger'>
                                ❌ Incorrect PIN entered 3 times.<br>
                                PIN change is locked for 10 minutes.
                            </div>";
            } else {
                // Update attempts count normally
                $pdo->prepare("UPDATE admin SET FailedPinAttempts=? WHERE UserID=?")
                    ->execute([$newAttempts, $userID]);

                $left = 3 - $newAttempts;
                $message = "<div class='alert alert-danger'>
                                ❌ Old PIN is incorrect.<br>
                                Attempts left: <strong>$left</strong>
                            </div>";
            }

            // Log failed attempt in audit trail
            logAction($userID, "Failed PIN attempt", "Admin", $userID, "Incorrect old PIN");

        } 
        else {
            // Reset failed attempts after successful validation
            $pdo->prepare("UPDATE admin SET FailedPinAttempts=0 WHERE UserID=?")
                ->execute([$userID]);

            // Hash new PIN securely using bcrypt
            $newHash = password_hash($newPin, PASSWORD_DEFAULT);

            // Update admin table with new PIN + timestamp
            $pdo->prepare("
                UPDATE admin 
                SET AdminPINHash=?, PinLastChanged=NOW(), PinLockUntil=NULL 
                WHERE UserID=?
            ")->execute([$newHash, $userID]);

            // Success message
            $message = "<div class='alert alert-success'>✅ PIN updated successfully.</div>";

            // Log successful PIN change into audit table
            logAction($userID, "Admin PIN changed", "Admin", $userID, "PIN update successful");

            // Update memory variable for display
            $admin['PinLastChanged'] = date("Y-m-d H:i:s");
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Change Admin PIN</title>

<!-- Load Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* Page background styling */
body { background:#243B55; padding:40px; font-family:Poppins,sans-serif; }

/* White box container for central card layout */
.box { max-width:450px; margin:auto; background:#fff; padding:25px; border-radius:12px; }

/* PIN strength dots */
.pin-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:5px; background:#bbb; }
.pin-dot.active { background:#4b6cb7; }
</style>
</head>

<body>

<div class="box">

<h3 class="text-center mb-3">Change Admin PIN</h3>

<!-- Back button to return to My Profile -->
<a href="my_profile.php" class="btn btn-secondary w-100 mb-3">← Back to My Profile</a>

<!-- Display success/error messages -->
<?= $message ?>

<!-- Show last PIN changed timestamp -->
<?php if (!empty($admin['PinLastChanged'])): ?>
    <p class="text-muted">
        <small>Last PIN changed: <?= htmlspecialchars($admin['PinLastChanged']) ?></small>
    </p>
<?php endif; ?>

<!-- If PIN is LOCKED, hide form -->
<?php if ($locked): ?>

<div class="alert alert-warning text-center">
    🔒 PIN change is temporarily locked.
</div>

<a href="profile.php" class="btn btn-dark w-100">Return to Profile</a>

<?php else: ?>

<!-- ============================= -->
<!-- CHANGE PIN FORM START -->
<!-- ============================= -->
<form method="POST">

    <!-- Old PIN input field -->
    <div class="mb-3">
        <label>Old PIN</label>
        <div class="input-group">
            <input type="password" name="old_pin" id="oldPin" maxlength="6" class="form-control">
            <button type="button" class="btn btn-outline-secondary" onclick="togglePin('oldPin')">Show</button>
        </div>
    </div>

    <!-- New PIN input with strength meter -->
    <div class="mb-3">
        <label>New PIN</label>
        <div class="input-group">
            <input type="password" name="new_pin" id="newPin" maxlength="6" pattern="\d{6}" class="form-control" oninput="updatePinStrength()">
            <button type="button" class="btn btn-outline-secondary" onclick="togglePin('newPin')">Show</button>
        </div>

        <!-- Strength meter using animated dots -->
        <div class="mt-2">
            <span class="pin-dot" id="dot1"></span>
            <span class="pin-dot" id="dot2"></span>
            <span class="pin-dot" id="dot3"></span>
            <span class="pin-dot" id="dot4"></span>
            <span class="pin-dot" id="dot5"></span>
            <span class="pin-dot" id="dot6"></span>
            <br>
            <small id="pinStrengthText" class="text-muted"></small>
        </div>
    </div>

    <!-- Confirm PIN input -->
    <div class="mb-3">
        <label>Confirm New PIN</label>
        <div class="input-group">
            <input type="password" name="confirm_pin" id="confirmPin" maxlength="6" pattern="\d{6}" class="form-control">
            <button type="button" class="btn btn-outline-secondary" onclick="togglePin('confirmPin')">Show</button>
        </div>
    </div>

    <!-- Submit button to update PIN -->
    <button name="change_pin" class="btn btn-warning w-100 mt-3">Update PIN</button>

    <!-- Cancel button to return to profile -->
    <a href="profile.php" class="btn btn-secondary w-100 mt-2">Cancel</a>

</form>

<?php endif; ?>

</div>

<!-- JavaScript Functions -->
<script>
// Toggle show/hide for PIN input fields
function togglePin(id) {
    const field = document.getElementById(id);
    field.type = (field.type === "password") ? "text" : "password";
}

// Update PIN strength meter in real-time
function updatePinStrength() {
    const pin = document.getElementById('newPin').value;

    const dots = [dot1, dot2, dot3, dot4, dot5, dot6];

    // Reset all dots before updating
    dots.forEach(d => d.classList.remove('active'));

    // Activate dots based on PIN length
    for (let i = 0; i < pin.length; i++) {
        dots[i].classList.add('active');
    }

    // Display PIN strength text
    const txt = document.getElementById('pinStrengthText');
    if (pin.length === 0) {
        txt.textContent = "";
    } else if (pin.length < 3) {
        txt.textContent = "Weak PIN";
    } else if (pin.length < 6) {
        txt.textContent = "Medium PIN";
    } else {
        txt.textContent = "Strong PIN";
    }
}
</script>

</body>
</html>
