<?php
// File: profile.php
// Staff Profile Management - Allows staff members to view and update their personal information
// Security Note: Users can only update their own profile, not change sensitive fields like status

// Include required files for authentication, staff model, and database connection
require_once __DIR__ . '/../includes/auth_check.php';  // Ensures user is logged in
require_once __DIR__ . '/../includes/staff_model.php'; // Database operations for staff
require_once __DIR__ . '/../includes/db.php';          // Database connection

// Initialize staff model for database operations
$staffModel = new StaffModel();
$errors = []; // Array to store validation errors
$success_message = ''; // Variable to store success message

// Get current staff member data using session user ID
// Security: Using session userID prevents users from accessing other profiles
$currentUserId = $_SESSION['userID'];
$currentStaff = $staffModel->getStaffById($currentUserId);

// Redirect to dashboard if staff profile not found (prevents errors)
if (!$currentStaff) {
    $_SESSION['error_message'] = "Staff profile not found!";
    header("Location: staff_dashboard.php");
    exit();
}

// Handle form submission for profile update
// Only processes when form is submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data using trim() to remove whitespace
    // Note: Input validation should be enhanced for production (e.g., email format)
    $data = [
        'FirstName' => trim($_POST['FirstName']),  // User's first name
        'LastName'  => trim($_POST['LastName']),   // User's last name
        'Email'     => trim($_POST['Email']),      // User's email address
        'Department'=> trim($_POST['Department']), // User's department
        'IsActive'  => $currentStaff['IsActive']   // Keep original status - cannot be changed by user (security)
    ];

    // Validate required fields - ensures no empty values are submitted
    // Note: Additional validation (e.g., email format, name length) could be added
    if (empty($data['FirstName'])) $errors[] = "First name is required";
    if (empty($data['LastName']))  $errors[] = "Last name is required";
    if (empty($data['Email']))     $errors[] = "Email is required";
    if (empty($data['Department']))$errors[] = "Department is required";

    // If no validation errors, attempt to update profile in database
    if (empty($errors)) {
        try {
            // Call updateStaff method to persist changes to database
            $staffModel->updateStaff($currentStaff['StaffID'], $data);
            $success_message = "Profile updated successfully!";
            
            // Refresh staff data to show updated information on form
            // Important: This ensures form shows current database values after update
            $currentStaff = $staffModel->getStaffById($currentUserId);
        } catch (Exception $e) {
            // Store database error message for user feedback
            // Note: In production, log detailed error but show generic message to user
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Future Billionaire Academy</title>
    <!-- Include Bootstrap CSS for responsive styling and components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome for icons (enhances visual feedback) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Global body styling with gradient background for visual appeal */
        body {
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #182848, #4b6cb7);
            color: white;
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Glass morphism effect for cards - modern UI design */
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px); /* Creates frosted glass effect */
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2); /* Subtle border */
        }
        
        /* User info header styling - displays current user info */
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: right;
        }
        
        /* Page header styling with brand color */
        h1 {
            color: #f1c40f;
            font-weight: 600;
        }
        
        /* Profile header section styling - centers avatar and name */
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        /* Profile avatar circle styling - visual representation of user */
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%; /* Makes it circular */
            background: linear-gradient(135deg, #f1c40f, #f39c12);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 3rem;
            color: white;
        }
        
        /* Form control styling for dark theme compatibility */
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        /* Form control focus state - highlights active field */
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #f1c40f; /* Yellow border on focus */
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(241, 196, 15, 0.25); /* Bootstrap-like focus shadow */
        }
        
        /* Primary button styling with gradient for visual hierarchy */
        .btn-primary {
            background: linear-gradient(135deg, #f1c40f, #f39c12);
            border: none;
            color: #182848;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- User information and navigation header -->
        <!-- Shows current user info and provides navigation links -->
        <div class="user-info">
            Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong> 
            (<?php echo htmlspecialchars($_SESSION['role'] ?? 'Staff'); ?>) | 
            <!-- Navigation links to key sections -->
            <a href="staff_dashboard.php" style="color: #f1c40f; margin-right: 10px;">Dashboard</a>
            <a href="staff_list.php" style="color: #f1c40f; margin-right: 10px;">Staff List</a>
            <a href="staff_logout.php" style="color: #f1c40f;">Logout</a>
        </div>

        <!-- Page header with title and back button -->
        <!-- Provides clear page context and navigation option -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>My Profile</h1>
            <a href="staff_dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <!-- Success message alert -->
        <!-- Displayed when profile is successfully updated -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Error messages alert -->
        <!-- Displayed when validation or database errors occur -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Main profile card -->
        <!-- Contains the profile form and user information -->
        <div class="glass-card">
            <!-- Profile Header with avatar and basic info -->
            <!-- Visual representation of the user profile -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i> <!-- User icon as placeholder for avatar -->
                </div>
                <h2><?php echo htmlspecialchars($currentStaff['FirstName'] . ' ' . $currentStaff['LastName']); ?></h2>
                <p class="text-muted"><?php echo htmlspecialchars($currentStaff['Department']); ?> Department</p>
            </div>

            <!-- Profile Update Form -->
            <!-- Form for updating user information with proper field grouping -->
            <form method="POST">
                <div class="row g-3">
                    <!-- Read-only Username field -->
                    <!-- Username cannot be changed for security reasons -->
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                        <small class="text-muted">Username cannot be changed</small>
                    </div>
                    
                    <!-- Read-only Role field -->
                    <!-- Role changes require administrator privileges -->
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['role']); ?>" readonly>
                        <small class="text-muted">Role cannot be changed</small>
                    </div>

                    <!-- Editable First Name field -->
                    <!-- Required field for user identification -->
                    <div class="col-md-6">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="FirstName" class="form-control" 
                               value="<?php echo htmlspecialchars($currentStaff['FirstName']); ?>" required>
                    </div>
                    
                    <!-- Editable Last Name field -->
                    <!-- Required field for user identification -->
                    <div class="col-md-6">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="LastName" class="form-control" 
                               value="<?php echo htmlspecialchars($currentStaff['LastName']); ?>" required>
                    </div>
                    
                    <!-- Editable Email field -->
                    <!-- Required field for communication and notifications -->
                    <div class="col-md-6">
                        <label class="form-label">Email *</label>
                        <input type="email" name="Email" class="form-control" 
                               value="<?php echo htmlspecialchars($currentStaff['Email']); ?>" required>
                    </div>
                    
                    <!-- Editable Department field -->
                    <!-- Required field for organizational structure -->
                    <div class="col-md-6">
                        <label class="form-label">Department *</label>
                        <input type="text" name="Department" class="form-control" 
                               value="<?php echo htmlspecialchars($currentStaff['Department']); ?>" required>
                    </div>
                    
                    <!-- Read-only Status field -->
                    <!-- Status is controlled by administrators for security -->
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <input type="text" class="form-control" 
                               value="<?php echo $currentStaff['IsActive'] ? 'Active' : 'Inactive'; ?>" readonly>
                    </div>
                </div>

                <!-- Form action buttons -->
                <!-- Submit and cancel options for user -->
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Profile
                    </button>
                    <a href="staff_dashboard.php" class="btn btn-outline-light">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Additional information card -->
        <!-- Provides helpful information about profile management -->
        <div class="mt-4 glass-card">
            <h5><i class="fas fa-info-circle me-2"></i>Profile Information</h5>
            <p class="mb-2">You can update your personal information here. Changes will be reflected across the system.</p>
            <ul class="mb-0">
                <li>Username and role cannot be changed for security reasons</li>
                <li>Contact an administrator for role changes or username updates</li>
                <li>Hire date and status are managed by the system</li>
            </ul>
        </div>
    </div>

    <!-- Include Bootstrap JavaScript for interactive components -->
    <!-- Enables Bootstrap features like dismissable alerts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>