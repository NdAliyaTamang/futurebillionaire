<?php
// Include required files for authentication, staff model, and database connection
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/staff_model.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user has Admin role (required for deletion)
requireRole(['Admin']);

// Initialize staff model and message variables
$staffModel = new StaffModel();
$errors = [];
$success = "";

// Get staff ID from URL parameter and validate
$staffId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($staffId <= 0) {
    // Redirect to staff list if invalid ID provided
    header("Location: staff_list.php");
    exit();
}

// Fetch staff member details from database
$staff = $staffModel->getStaffById($staffId);
if (!$staff) {
    // Set error message and redirect if staff member not found
    $_SESSION['error_message'] = "Staff member not found!";
    header("Location: staff_list.php");
    exit();
}

// Handle form submission for permanent deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Prepare and execute SQL DELETE statement
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM Staff WHERE StaffID = ?");
        $stmt->execute([$staffId]);
        $success = " Staff member deleted permanently!";
    } catch (PDOException $e) {
        // Catch database errors and display to user
        $errors[] = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Delete Staff Member</title>
  <!-- Bootstrap CSS for responsive styling -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Gradient background for entire page */
    body {
      background: linear-gradient(135deg, #ff6a00 0%, #ee0979 100%);
      background-attachment: fixed;
      min-height: 100vh;
      padding: 20px;
      color: #333;
    }
    /* Main container styling */
    .container {
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      padding: 30px;
      max-width: 850px;
      margin: 40px auto;
    }
    /* Danger button styling for delete action */
    .btn-danger {
      background-color: #e74c3c;
      border: none;
    }
    .btn-danger:hover {
      background-color: #c0392b;
    }
    /* User info header styling */
    .user-info {
      background: rgba(255, 255, 255, 0.1);
      padding: 10px 15px;
      border-radius: 8px;
      margin-bottom: 15px;
      font-size: 14px;
      text-align: right;
      background: rgba(52, 73, 94, 0.1);
    }
  </style>
</head>
<body>
<div class="container">
  <!-- User information and navigation header -->
  <div class="user-info">
    Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong> 
    (<?php echo htmlspecialchars($_SESSION['role'] ?? 'Staff'); ?>) | 
    <a href="staff_list.php" style="color: #3498db; margin-right: 10px;">Staff List</a>
    <a href="staff_logout.php" style="color: #3498db;">Logout</a>
  </div>

  <!-- Page header with back button -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Delete Staff Member</h1>
    <a href="staff_list.php" class="btn btn-secondary">← Back to List</a>
  </div>

  <!-- Success message display with auto-redirect -->
  <?php if ($success): ?>
    <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
    <script>
      // Automatically redirect to staff list after 2 seconds on success
      setTimeout(() => {
        window.location.href = "staff_list.php";
      }, 2000);
    </script>
  <?php endif; ?>

  <!-- Error messages display -->
  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Delete confirmation form -->
  <form method="POST">
    <!-- Staff information display (read-only) -->
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">First Name</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($staff['FirstName']); ?>" readonly>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Last Name</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($staff['LastName']); ?>" readonly>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Email</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($staff['Email']); ?>" readonly>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Department</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($staff['Department']); ?>" readonly>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Hire Date</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($staff['HireDate']); ?>" readonly>
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Salary (DKK)</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($staff['Salary']); ?>" readonly>
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Status</label>
        <input type="text" class="form-control" value="<?= $staff['IsActive'] ? 'Active' : 'Inactive'; ?>" readonly>
      </div>
    </div>

    <!-- Final warning confirmation -->
    <div class="alert alert-danger mt-4 text-center">
       Are you sure you want to <strong>permanently delete</strong> this staff member?
    </div>

    <!-- Form action buttons -->
    <div class="d-flex justify-content-between">
      <a href="staff_list.php" class="btn btn-outline-secondary">Cancel</a>
      <button type="submit" class="btn btn-danger">Confirm Delete</button>
    </div>
  </form>
</div>
</body>
</html>