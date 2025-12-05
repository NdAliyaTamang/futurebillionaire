<?php
// File: staff_pending_approvals.php
// Pending User Approvals Management
// Admin-only page for reviewing and approving pending user registrations

// Include required files for authentication and database connection
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_model.php';

// Restrict access to Admins only
requireRole(['Admin']);

// Initialize variables
$pendingUsers = [];
$success_message = '';
$error_message = '';

// Handle approval action
if (isset($_GET['action']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $userId = (int)$_GET['id'];
    $action = $_GET['action'];
    
    try {
        if ($action === 'approve') {
            if (activateUser($userId)) {
                $success_message = "User approved successfully!";
            } else {
                $error_message = "Failed to approve user. Please try again.";
            }
        } elseif ($action === 'reject') {
            // You might want to implement a reject function that deletes or marks as rejected
            $error_message = "Reject functionality not yet implemented.";
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
    
    // Redirect to clear URL parameters
    header("Location: staff_pending_approvals.php?success=" . urlencode($success_message) . "&error=" . urlencode($error_message));
    exit();
}

// Get success/error messages from URL parameters
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Fetch pending users
try {
    $pendingUsers = getPendingUsers();
} catch (Exception $e) {
    $error_message = "Failed to load pending users: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals - Future Billionaire Academy</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Consistent styling with your existing pages */
        body {
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #182848, #4b6cb7);
            color: white;
            min-height: 100vh;
            padding: 20px;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: right;
        }
        
        h1 {
            color: #f1c40f;
            font-weight: 600;
        }
        
        .table {
            background: rgba(52, 75, 120, 0.8);
            color: #fff;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead {
            background-color: rgba(0, 0, 0, 0.4);
            color: #fff;
        }
        
        .table-bordered > :not(caption) > * {
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .btn-approve {
            background-color: #27ae60;
            border: none;
            color: white;
        }
        
        .btn-approve:hover {
            background-color: #219653;
        }
        
        .btn-reject {
            background-color: #e74c3c;
            border: none;
            color: white;
        }
        
        .btn-reject:hover {
            background-color: #c0392b;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- User information and navigation header -->
        <div class="user-info">
            Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong> 
            (<?php echo htmlspecialchars($_SESSION['role'] ?? 'Staff'); ?>) | 
            <a href="staff_dashboard.php" style="color: #f1c40f; margin-right: 10px;">Dashboard</a>
            <a href="staff_list.php" style="color: #f1c40f; margin-right: 10px;">Staff List</a>
            <a href="staff_logout.php" style="color: #f1c40f;">Logout</a>
        </div>

        <!-- Page header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Pending User Approvals</h1>
            <a href="staff_dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <!-- Success and error messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="glass-card">
            <?php if (empty($pendingUsers)): ?>
                <!-- Empty state when no pending users -->
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>No Pending Approvals</h3>
                    <p>All user accounts are currently approved. Great work!</p>
                    <a href="staff_dashboard.php" class="btn btn-primary mt-3">
                        <i class="fas fa-tachometer-alt me-2"></i>Return to Dashboard
                    </a>
                </div>
            <?php else: ?>
                <!-- Pending users table -->
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingUsers as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['UserID']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($user['Role']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($user['CreatedDate']))); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="staff_pending_approvals.php?action=approve&id=<?php echo $user['UserID']; ?>" 
                                               class="btn btn-approve btn-sm"
                                               onclick="return confirm('Are you sure you want to approve <?php echo htmlspecialchars($user['Username']); ?>?')">
                                                <i class="fas fa-check me-1"></i>Approve
                                            </a>
                                            <button type="button" class="btn btn-reject btn-sm" disabled title="Reject functionality coming soon">
                                                <i class="fas fa-times me-1"></i>Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary -->
                <div class="mt-3 p-3 bg-dark bg-opacity-25 rounded">
                    <strong>Summary:</strong> 
                    <?php echo count($pendingUsers); ?> pending user<?php echo count($pendingUsers) !== 1 ? 's' : ''; ?> awaiting approval
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Additional information -->
        <div class="mt-4 glass-card">
            <h5><i class="fas fa-info-circle me-2"></i>About Pending Approvals</h5>
            <p class="mb-2">When you approve a user:</p>
            <ul class="mb-0">
                <li>The user account is activated immediately</li>
                <li>The user can now log in to the system</li>
                <li>Their staff/student profile is also activated</li>
                <li>They will appear in the appropriate lists</li>
            </ul>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>