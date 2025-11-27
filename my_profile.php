<?php
session_start(); // Start user session for authentication handling

// Load authentication check logic
require_once '../includes/auth_check.php';

// Load database connection file
require_once '../includes/db.php';

// Load input validation helper functions
require_once '../includes/validation.php';

// Allow access only for Admin, Staff, and Student roles
requireRole(['Admin', 'Staff', 'Student']);

$pdo     = getDB();              // Get database connection
$userID  = $_SESSION['userID'];  // Get currently logged-in user ID
$role    = $_SESSION['role'];    // Get currently logged-in user role
$message = "";                   // Initialize feedback message container

// Fetch user profile data based on current role
if ($role === 'Staff') {
    // Fetch staff profile data from staff table
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE UserID = ?");
} elseif ($role === 'Student') {
    // Fetch student profile data from student table
    $stmt = $pdo->prepare("SELECT * FROM student WHERE UserID = ?");
} else {
    // Fetch admin profile data from user table
    $stmt = $pdo->prepare("SELECT * FROM user WHERE UserID = ?");
}
$stmt->execute([$userID]); // Execute fetch query
$profile = $stmt->fetch(PDO::FETCH_ASSOC); // Store profile data

// Predefined avatar options displayed to all roles
$avatarOptions = [
    "https://cdn-icons-png.flaticon.com/512/2922/2922510.png",
    "https://cdn-icons-png.flaticon.com/512/2922/2922513.png",
    "https://cdn-icons-png.flaticon.com/512/2922/2922561.png",
    "https://cdn-icons-png.flaticon.com/512/2922/2922656.png",
    "https://cdn-icons-png.flaticon.com/512/2922/2922688.png",
    "https://cdn-icons-png.flaticon.com/512/2922/2922716.png",
    "https://cdn-icons-png.flaticon.com/512/2922/2922721.png",
    "https://cdn-icons-png.flaticon.com/512/2922/2922739.png"
];

// Handle avatar selection request
if (isset($_POST['choose_avatar'])) {
    // Fetch the selected avatar URL
    $avatarUrl = trim($_POST['choose_avatar']);

    // Update avatar image for the correct role table
    if ($avatarUrl !== '') {
        if ($role === 'Staff') {
            $pdo->prepare("UPDATE staff SET ProfileImage=? WHERE UserID=?")->execute([$avatarUrl, $userID]);
        } elseif ($role === 'Student') {
            $pdo->prepare("UPDATE student SET ProfileImage=? WHERE UserID=?")->execute([$avatarUrl, $userID]);
        } else {
            $pdo->prepare("UPDATE user SET ProfileImage=? WHERE UserID=?")->execute([$avatarUrl, $userID]);
        }

        $profile['ProfileImage'] = $avatarUrl; // Update profile image preview
        $message = "<div class='alert alert-success'>✅ Avatar updated successfully.</div>";
    }
}

// Handle profile image upload request
if (isset($_POST['upload_image']) && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image']; // Uploaded file data

    // Validate uploaded image for file type and size
    if (!validateProfileImage($file)) {
        $message = "<div class='alert alert-danger'>❌ Invalid image. Use JPG/PNG/WEBP and max 2MB.</div>";
    } else {
        // Extract file information
        $imgName = $file['name'];
        $tmp     = $file['tmp_name'];
        $ext     = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));

        // Generate new unique filename for storing uploaded image
        $newName = "IMG_" . $userID . "_" . time() . "." . $ext;
        $uploadPath = "../uploads/profile/" . $newName;

        // Move uploaded file to storage folder
        if (move_uploaded_file($tmp, $uploadPath)) {
            // Update stored profile image name in correct table
            if ($role === 'Staff') {
                $pdo->prepare("UPDATE staff SET ProfileImage=? WHERE UserID=?")->execute([$newName, $userID]);
            } elseif ($role === 'Student') {
                $pdo->prepare("UPDATE student SET ProfileImage=? WHERE UserID=?")->execute([$newName, $userID]);
            } else {
                $pdo->prepare("UPDATE user SET ProfileImage=? WHERE UserID=?")->execute([$newName, $userID]);
            }

            $profile['ProfileImage'] = $newName; // Update preview
            $message = "<div class='alert alert-success'>✅ Profile image updated.</div>";
        } else {
            // Show upload failure message
            $message = "<div class='alert alert-danger'>❌ Failed to upload image.</div>";
        }
    }
}

// Handle request to update profile fields
if (isset($_POST['update_profile'])) {

    $errors = []; // Collect validation error messages

    // Extract and validate email for all roles
    $email = trim($_POST['email'] ?? '');
    if (!validateEmail($email)) {
        $errors[] = "Email must be valid and end with @school.edu.";
    }

    // Validate student/staff first and last names
    if ($role === 'Staff' || $role === 'Student') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');

        if (!validateName($firstName)) $errors[] = "First name must contain only letters and spaces (2–40 characters).";
        if (!validateName($lastName))  $errors[] = "Last name must contain only letters and spaces (2–40 characters).";
    }

    // Handle Admin profile update
    if ($role === 'Admin' && empty($errors)) {
        // Update only email for Admin (username & role are read-only)
        $pdo->prepare("UPDATE user SET Email=? WHERE UserID=?")->execute([$email, $userID]);
        $profile['Email'] = $email; // Refresh displayed email
        $message = "<div class='alert alert-success'>✅ Profile updated successfully.</div>";
    }

    // Handle Staff profile update
    if ($role === 'Staff' && empty($errors)) {
        // Update basic fields
        $pdo->prepare("UPDATE staff SET FirstName=?, LastName=?, Email=? WHERE UserID=?")
            ->execute([$firstName, $lastName, $email, $userID]);

        // Update displayed values
        $profile['FirstName'] = $firstName;
        $profile['LastName']  = $lastName;
        $profile['Email']     = $email;

        $message = "<div class='alert alert-success'>✅ Profile updated successfully.</div>";
    }

    // Handle Student profile update
    if ($role === 'Student') {

        $dob = trim($_POST['dob'] ?? ''); // Extract DOB value
        $age = trim($_POST['age'] ?? ''); // Extract Age value

        // Validate DOB & age
        if (!validateDOB($dob))  $errors[] = "Date of birth must be valid and in the past.";
        if (!validateAge($age))  $errors[] = "Age must be between 5 and 100.";

        // Proceed only if no errors exist
        if (empty($errors)) {
            $pdo->prepare("UPDATE student SET FirstName=?, LastName=?, Email=?, DateOfBirth=?, Age=? WHERE UserID=?")
                ->execute([$firstName, $lastName, $email, $dob, $age, $userID]);

            // Update displayed values
            $profile['FirstName']   = $firstName;
            $profile['LastName']    = $lastName;
            $profile['Email']       = $email;
            $profile['DateOfBirth'] = $dob;
            $profile['Age']         = $age;

            $message = "<div class='alert alert-success'>✅ Profile updated successfully.</div>";
        }
    }

    // Display errors when validation fails
    if (!empty($errors)) {
        $message = "<div class='alert alert-danger'><strong>⚠ Please fix the following:</strong><br>" .
                   implode("<br>", array_map('htmlspecialchars', $errors)) .
                   "</div>";
    }
}
// Handle Admin PIN change request (Admin only)
if ($role === 'Admin' && isset($_POST['change_pin'])) {

    $oldPin = $_POST['old_pin'] ?? '';
    $newPin = $_POST['new_pin'] ?? '';
    $confirmPin = $_POST['confirm_pin'] ?? '';

    // Validate new PIN format (must be exactly 6 digits)
    if (!preg_match("/^[0-9]{6}$/", $newPin)) {
        $message = "<div class='alert alert-danger'>❌ New PIN must be exactly 6 digits.</div>";
    } elseif ($newPin !== $confirmPin) {
        $message = "<div class='alert alert-danger'>❌ New PIN and Confirm PIN do not match.</div>";
    } else {
        // Fetch current stored PIN hash
        $stmt = $pdo->prepare("SELECT AdminPINHash FROM admin WHERE UserID=?");
        $stmt->execute([$userID]);
        $adminRow = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify old PIN
        if (!$adminRow || !password_verify($oldPin, $adminRow['AdminPINHash'])) {
            $message = "<div class='alert alert-danger'>❌ Old PIN is incorrect.</div>";
        } else {
            // Hash and update new PIN
            $newHash = password_hash($newPin, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admin SET AdminPINHash=? WHERE UserID=?")
                ->execute([$newHash, $userID]);

            $message = "<div class='alert alert-success'>✅ Admin PIN updated successfully.</div>";
        }
    }
}

// Handle password change request
if (isset($_POST['change_password'])) {

    // Extract old and new passwords
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';

    // Fetch stored password hash from user table
    $u = $pdo->prepare("SELECT PasswordHash FROM user WHERE UserID=?");
    $u->execute([$userID]);
    $row = $u->fetch(PDO::FETCH_ASSOC);

    // Validate old password and new password strength
    if (!$row || !password_verify($old, $row['PasswordHash'])) {
        $message = "<div class='alert alert-danger'>❌ Old password is incorrect.</div>";
    } elseif (!validatePassword($new)) {
        $message = "<div class='alert alert-danger'>❌ New password must be strong enough.</div>";
    } else {
        // Hash new password and save it
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE user SET PasswordHash=? WHERE UserID=?")->execute([$newHash, $userID]);

        $message = "<div class='alert alert-success'>✅ Password changed successfully.</div>";
    }
}

// Determine correct profile image path (uploaded file or avatar URL)
$imgPath = "";
if (!empty($profile['ProfileImage'])) {
    if (strpos($profile['ProfileImage'], 'http') === 0) {
        // Directly use full external URL for avatars
        $imgPath = $profile['ProfileImage'];
    } else {
        // Load uploaded image from profile folder
        $imgPath = "../uploads/profile/" . $profile['ProfileImage'];
    }
} else {
    // Use default fallback avatar when no image is set
    $imgPath = "https://cdn-icons-png.flaticon.com/512/847/847969.png";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>My Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* Page background style */
body { background:#243B55; padding:40px; font-family:Poppins,sans-serif; }

/* Main content box styling */
.box { max-width:650px; margin:auto; background:#fff; padding:25px; border-radius:12px; }

/* Page title styling */
h2 { text-align:center; margin-bottom:20px; }

/* Avatar image styling */
.avatar { width:130px; height:130px; border-radius:50%; object-fit:cover; display:block; margin:auto; }

/* Buttons used for profile saving and password change */
.btn-save { background:#3498db; color:#fff; width:100%; border:none; }
.btn-pass { background:#9b59b6; color:#fff; width:100%; border:none; }

/* Back link styling */
.btn-back { font-weight:bold; text-decoration:none; }
.btn-back:hover { text-decoration:underline; }

/* Password strength progress bar */
#passwordStrengthBar { height:8px; border-radius:4px; background:#ddd; }

/* Avatar grid container styles */
.avatar-grid { display:flex; flex-wrap:wrap; gap:10px; justify-content:center; margin-top:10px; }

/* Avatar selection styling */
.avatar-choice img { width:60px; height:60px; border-radius:50%; border:2px solid transparent; }
.avatar-choice img:hover { border-color:#3498db; }
</style>

</head>
<body>

<div class="box">

<h2>My Profile (<?php echo htmlspecialchars($role); ?>)</h2>

<?php echo $message; // Output success or error messages ?>

<!-- Profile image section including upload and avatar selection -->
<div id="imageSection">

    <!-- Display current profile image -->
    <img src="<?php echo htmlspecialchars($imgPath); ?>" class="avatar">

    <!-- Image upload form -->
    <form method="POST" enctype="multipart/form-data" class="mt-3 text-center">
        <input type="file" name="profile_image" class="form-control">
        <button class="btn btn-dark mt-2" name="upload_image">Upload Image</button>
    </form>

    <!-- Toggle button for showing avatar selection -->
    <button type="button" class="btn btn-outline-secondary w-100 mt-2" onclick="toggleAvatarSection()">
        🎭 Choose Avatar
    </button>

    <!-- Avatar selection grid (hidden until toggled) -->
    <div id="avatarSection" style="display:none;">
        <div class="mt-2 text-center"><small>Select an avatar:</small></div>
        <div class="avatar-grid">
            <?php foreach ($avatarOptions as $url): ?>
                <form method="POST">
                    <button type="submit" name="choose_avatar" value="<?php echo htmlspecialchars($url); ?>" class="avatar-choice">
                        <img src="<?php echo htmlspecialchars($url); ?>">
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    </div>

    <hr>
</div>

<!-- VIEW MODE — Shows profile information based on role -->
<div id="viewMode">

    <?php if ($role === 'Admin'): ?>
        <!-- Show Admin username, role, and email -->
        <p><strong>Username:</strong> <?php echo htmlspecialchars($profile['Username']); ?></p>
        <p><strong>Role:</strong> <?php echo htmlspecialchars($profile['Role']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['Email']); ?></p>

    <?php elseif ($role === 'Staff'): ?>
        <!-- Show Staff profile details -->
        <p><strong>First Name:</strong> <?php echo htmlspecialchars($profile['FirstName']); ?></p>
        <p><strong>Last Name:</strong> <?php echo htmlspecialchars($profile['LastName']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['Email']); ?></p>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($profile['Department']); ?></p>
        <p><strong>Salary:</strong> <?php echo htmlspecialchars($profile['Salary']); ?></p>

    <?php else: ?>
        <!-- Show Student profile details -->
        <p><strong>First Name:</strong> <?php echo htmlspecialchars($profile['FirstName']); ?></p>
        <p><strong>Last Name:</strong> <?php echo htmlspecialchars($profile['LastName']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['Email']); ?></p>
        <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($profile['DateOfBirth']); ?></p>
        <p><strong>Age:</strong> <?php echo htmlspecialchars($profile['Age']); ?></p>
        <p><strong>GPA:</strong> <?php echo htmlspecialchars($profile['GPA']); ?></p>
    <?php endif; ?>

    <!-- Button to open Edit Profile form -->
    <button class="btn btn-primary w-100 mt-3" onclick="showEditProfile()">Edit Profile</button>

    <!-- Button to open Change Password form -->
    <button class="btn btn-pass w-100 mt-2" onclick="showPasswordForm()">Change Password</button>

    <!-- Admin-only button to change Admin PIN -->
    <?php if ($role === 'Admin'): ?>
    <button class="btn btn-warning w-100 mt-2" onclick="window.location='change_pin.php'">Change My PIN</button>
    <?php endif; ?>

    <!-- Link to return to dashboard -->
    <div class="text-center mt-3">
        <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
    </div>

</div>

<!-- EDIT PROFILE MODE — Allows editing role-specific fields -->
<form method="POST" id="editMode" style="display:none;">

    <?php if ($role === 'Admin'): ?>

        <!-- Admin username (read-only) -->
        <div class="mb-2"><label>Username</label>
            <input type="text" value="<?php echo htmlspecialchars($profile['Username']); ?>" class="form-control" disabled>
        </div>

        <!-- Admin role (read-only) -->
        <div class="mb-2"><label>Role</label>
            <input type="text" value="<?php echo htmlspecialchars($profile['Role']); ?>" class="form-control" disabled>
        </div>

        <!-- Admin email field -->
        <div class="mb-2"><label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($profile['Email']); ?>" class="form-control">
        </div>

    <?php elseif ($role === 'Staff'): ?>

        <!-- Staff editable personal details -->
        <div class="mb-2"><label>First Name</label>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['FirstName']); ?>" class="form-control">
        </div>

        <div class="mb-2"><label>Last Name</label>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['LastName']); ?>" class="form-control">
        </div>

        <div class="mb-2"><label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($profile['Email']); ?>" class="form-control">
        </div>

        <!-- Staff department and salary are read-only -->
        <div class="mb-2"><label>Department</label>
            <input type="text" value="<?php echo htmlspecialchars($profile['Department']); ?>" class="form-control" disabled>
        </div>

        <div class="mb-2"><label>Salary</label>
            <input type="text" value="<?php echo htmlspecialchars($profile['Salary']); ?>" class="form-control" disabled>
        </div>

    <?php else: ?> <!-- Student -->

        <!-- Student editable details -->
        <div class="mb-2"><label>First Name</label>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['FirstName']); ?>" class="form-control">
        </div>

        <div class="mb-2"><label>Last Name</label>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['LastName']); ?>" class="form-control">
        </div>

        <div class="mb-2"><label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($profile['Email']); ?>" class="form-control">
        </div>

        <div class="mb-2"><label>Date of Birth</label>
            <input type="date" name="dob" value="<?php echo htmlspecialchars($profile['DateOfBirth']); ?>" class="form-control">
        </div>

        <div class="mb-2"><label>Age</label>
            <input type="number" name="age" value="<?php echo htmlspecialchars($profile['Age']); ?>" class="form-control">
        </div>

        <!-- Student GPA is read-only -->
        <div class="mb-2"><label>GPA</label>
            <input type="text" value="<?php echo htmlspecialchars($profile['GPA']); ?>" class="form-control" disabled>
        </div>

    <?php endif; ?>

    <!-- Save profile changes button -->
    <button name="update_profile" class="btn btn-save mt-3">Save Changes</button>

    <!-- Cancel editing and return to view mode -->
    <button type="button" class="btn btn-secondary w-100 mt-2" onclick="showViewMode()">Cancel</button>

</form>

<!-- PASSWORD CHANGE MODE -->
<form method="POST" id="passwordMode" style="display:none;">

    <!-- Old password input -->
    <div class="mb-2"><label>Old Password</label>
        <input type="password" name="old_password" class="form-control">
    </div>

    <!-- New password input with strength meter -->
    <div class="mb-2"><label>New Password</label>
        <div class="input-group">
            <input type="password" name="new_password" id="newPassword" class="form-control">
            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">Show</button>
        </div>
        <small class="text-muted">Use a strong password (mixed characters recommended).</small>

        <!-- Password strength meter bar -->
        <div class="mt-2">
            <div id="passwordStrengthBar"></div>
            <small id="passwordStrengthText"></small>
        </div>
    </div>

    <!-- Submit password change -->
    <button name="change_password" class="btn btn-pass mt-3">Change Password</button>

    <!-- Cancel and go back to view mode -->
    <button type="button" class="btn btn-secondary w-100 mt-2" onclick="showViewMode()">Cancel</button>

</form>

</div>

<!-- JavaScript functionality -->
<script>
// Show view mode and hide edit/password modes
function showViewMode() {
    document.getElementById("imageSection").style.display = "block";
    document.getElementById("viewMode").style.display = "block";
    document.getElementById("editMode").style.display = "none";
    document.getElementById("passwordMode").style.display = "none";
    const av = document.getElementById("avatarSection");
    if (av) av.style.display = "none";
}

// Show profile editing mode
function showEditProfile() {
    document.getElementById("imageSection").style.display = "block";
    document.getElementById("viewMode").style.display = "none";
    document.getElementById("editMode").style.display = "block";
    document.getElementById("passwordMode").style.display = "none";
    const av = document.getElementById("avatarSection");
    if (av) av.style.display = "none";
}

// Show password change form
function showPasswordForm() {
    document.getElementById("imageSection").style.display = "none";
    document.getElementById("viewMode").style.display = "none";
    document.getElementById("editMode").style.display = "none";
    document.getElementById("passwordMode").style.display = "block";
    const av = document.getElementById("avatarSection");
    if (av) av.style.display = "none";
}

// Toggle avatar selection section
function toggleAvatarSection() {
    const av = document.getElementById("avatarSection");
    if (!av) return;
    av.style.display = (av.style.display === "none" || av.style.display === "") ? "block" : "none";
}

// Toggle password visibility on click
function togglePassword() {
    const input = document.getElementById('newPassword');
    input.type = (input.type === 'password') ? 'text' : 'password';
}

// Password strength meter functionality
const pwdInput = document.getElementById('newPassword');
const bar = document.getElementById('passwordStrengthBar');
const text = document.getElementById('passwordStrengthText');

// Update strength meter as user types new password
if (pwdInput) {
    pwdInput.addEventListener('input', function() {
        const val = pwdInput.value;
        let score = 0;

        // Calculate strength score based on password content
        if (val.length >= 8) score++;
        if (/[a-z]/.test(val)) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        // Set meter width based on score
        const width = (score / 5) * 100;
        bar.style.width = width + "%";

        // Set meter color and text based on strength
        if (score <= 1) {
            bar.style.background = "#e74c3c";
            text.textContent = "Weak";
        } else if (score <= 3) {
            bar.style.background = "#f1c40f";
            text.textContent = "Medium";
        } else {
            bar.style.background = "#2ecc71";
            text.textContent = "Strong";
        }
    });
}
</script>

</body>
</html>
