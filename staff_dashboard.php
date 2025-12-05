<?php
// File: staff_dashboard.php
// Dashboard for School Management System - Main staff dashboard with statistics and quick actions
// Purpose: Central hub for staff members with role-based access to system features

// Enable error reporting for debugging during development
// IMPORTANT: Disable error display in production (set display_errors to 0)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files for authentication, database, and authentication model
// These files provide essential functionality for user authentication and data access
require_once __DIR__ . '/../includes/auth_check.php';    // Ensures user is logged in and has valid session
require_once __DIR__ . '/../includes/db.php';           // Database connection setup
require_once __DIR__ . '/../includes/auth_model.php';   // Authentication-related database functions

// Initialize variables for dashboard statistics with default values
// Prevents undefined variable warnings if database queries fail
$pendingUsersCount = 0;    // Number of users awaiting approval (admin only)
$totalStaffCount = 0;      // Count of active staff members in the system
$recentActivity = [];      // Array to store recent login activity records

try {
    // Get count of pending user approvals for admin display
    // This function should return count of users with pending status
    $pendingUsersCount = countPendingUsers();
    
    // Get total staff count from database
    // Using direct PDO query for simple count operation
    $pdo = getDB(); // Get database connection from db.php
    $stmt = $pdo->query("SELECT COUNT(*) FROM Staff WHERE IsActive = 1");
    $totalStaffCount = $stmt->fetchColumn(); // Fetch single value from first column
    
    // Get recent login activity (last 5 logins) for activity feed
    // Shows recent system usage for monitoring and awareness
    $stmt = $pdo->query("
        SELECT Username, LastLogin 
        FROM User 
        WHERE LastLogin IS NOT NULL 
        ORDER BY LastLogin DESC 
        LIMIT 5
    ");
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch as associative array
    
} catch (PDOException $e) {
    // Log database errors but don't break the dashboard
    // Using error_log to record issues without displaying to users
    error_log("Dashboard data error: " . $e->getMessage());
    // Note: In production, consider showing a user-friendly error message
}

// Determine user role for role-based content and permissions
// Role determines which features and data are visible to the user
$isAdmin = ($_SESSION['role'] === 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Future Billionaire Academy</title>
    <!-- Include Bootstrap CSS for responsive layout and components -->
    <!-- Using CDN for simplicity; consider local hosting for production -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome for icons -->
    <!-- Icons enhance visual appeal and improve usability -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Global body styling with gradient background */
        /* Creates immersive visual experience consistent with brand */
        body {
            font-family: "Poppins", sans-serif; /* Modern, readable font */
            background: linear-gradient(135deg, #182848, #4b6cb7); /* Brand gradient */
            color: white; /* High contrast text on dark background */
            min-height: 100vh; /* Full viewport height */
            margin: 0; /* Remove default margin */
            padding: 0; /* Remove default padding - container handles spacing */
        }
        
        /* Glass morphism effect for cards */
        /* Modern UI design trend with semi-transparent, blurred background */
        .glass-card {
            background: rgba(255, 255, 255, 0.1); /* Semi-transparent white */
            backdrop-filter: blur(10px); /* Frosted glass effect */
            border-radius: 15px; /* Rounded corners */
            padding: 20px; /* Internal spacing */
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3); /* Depth shadow */
            border: 1px solid rgba(255, 255, 255, 0.2); /* Subtle border definition */
            height: 100%; /* Ensures cards fill their container evenly */
        }
        
        /* Dashboard header styling */
        .dashboard-header {
            padding: 30px 0; /* Vertical spacing */
            text-align: center; /* Center align header content */
        }
        
        /* Welcome text styling with accent color */
        .welcome-text {
            color: #f1c40f; /* Brand accent color (yellow/gold) */
            font-weight: 600; /* Semi-bold for emphasis */
            margin-bottom: 5px; /* Space below welcome text */
        }
        
        /* Statistics card hover animation */
        /* Interactive feedback for better user experience */
        .stat-card {
            transition: transform 0.3s ease; /* Smooth hover animation */
        }
        
        /* Hover effect for stat cards */
        .stat-card:hover {
            transform: translateY(-5px); /* Subtle lift effect */
        }
        
        /* Statistics icon styling */
        .stat-icon {
            font-size: 2.5rem; /* Large icon size for visual impact */
            margin-bottom: 15px; /* Space below icon */
            color: #f1c40f; /* Brand accent color */
        }
        
        /* Quick action button styling */
        .quick-action {
            background: rgba(255, 255, 255, 0.15); /* Semi-transparent background */
            border: none; /* Remove default button border */
            color: white; /* Text color for contrast */
            padding: 15px; /* Comfortable click area */
            border-radius: 10px; /* Rounded corners */
            transition: all 0.3s ease; /* Smooth hover transition */
            text-align: center; /* Center align text */
            display: block; /* Make links behave like block elements */
            text-decoration: none; /* Remove underline from links */
            margin-bottom: 15px; /* Space between quick action items */
        }
        
        /* Quick action hover effect */
        .quick-action:hover {
            background: rgba(241, 196, 15, 0.3); /* Brand color with transparency */
            color: white; /* Maintain text color */
            transform: translateY(-3px); /* Subtle lift effect */
            text-decoration: none; /* Ensure no underline on hover */
        }
        
        /* User info header styling */
        .user-info {
            background: rgba(255, 255, 255, 0.1); /* Semi-transparent background */
            padding: 10px 15px; /* Comfortable internal spacing */
            border-radius: 8px; /* Slightly rounded corners */
            margin-bottom: 15px; /* Space below user info bar */
            font-size: 14px; /* Smaller font for secondary information */
            text-align: right; /* Align content to the right */
        }
        
        /* Activity list styling for recent activity feed */
        .activity-list {
            list-style-type: none; /* Remove default list bullets */
            padding: 0; /* Remove default padding */
            margin: 0; /* Remove default margin */
        }
        
        /* Individual activity item styling */
        .activity-item {
            padding: 10px 15px; /* Comfortable internal spacing */
            border-bottom: 1px solid rgba(255, 255, 255, 0.1); /* Subtle separator */
        }
        
        /* Remove border from last activity item */
        .activity-item:last-child {
            border-bottom: none; /* Clean appearance for last item */
        }
        
        /* Improve accessibility for focus states */
        .quick-action:focus {
            outline: 2px solid #f1c40f; /* Visible focus indicator for keyboard users */
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- User information and navigation header -->
        <!-- Shows current user info and provides main navigation links -->
        <div class="user-info">
            Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong> 
            (<?php echo htmlspecialchars($_SESSION['role'] ?? 'Staff'); ?>) | 
            <!-- Navigation links to key sections -->
            <!-- Using inline styles for simple hover states; consider CSS classes for production -->
            <a href="main_dashboard.php" style="color: #f1c40f; margin-right: 10px;">Main Dashboard</a>
            <a href="staff_list.php" style="color: #f1c40f; margin-right: 10px;">Staff List</a>
            <a href="staff_logout.php" style="color: #f1c40f;">Logout</a>
        </div>

        <!-- Dashboard header with welcome message and system name -->
        <!-- Primary page header with branding and system identification -->
        <div class="dashboard-header">
            <h1 class="welcome-text">Future Billionaire Academy</h1>
            <p class="lead">Staff Management System Dashboard</p>
        </div>

        <!-- Statistics and Quick Actions Row -->
        <!-- Main content area with two-column layout: stats (left) and actions (right) -->
        <div class="row mb-4">
            <!-- Statistics Cards Column (wider column for primary information) -->
            <div class="col-md-8">
                <div class="row">
                    <!-- Total Staff Statistics Card -->
                    <!-- Shows count of active staff members in the system -->
                    <div class="col-md-6 mb-4">
                        <div class="glass-card stat-card text-center">
                            <i class="fas fa-users stat-icon"></i> <!-- Users icon -->
                            <h3><?php echo $totalStaffCount; ?></h3> <!-- Dynamic count from database -->
                            <p>Total Active Staff</p> <!-- Card label -->
                        </div>
                    </div>
                    
                    <!-- Pending Approvals Statistics Card (Admin only) -->
                    <!-- Conditional card only visible to admin users -->
                    <?php if ($isAdmin): ?>
                    <div class="col-md-6 mb-4">
                        <div class="glass-card stat-card text-center">
                            <i class="fas fa-user-clock stat-icon"></i> <!-- Clock icon for pending items -->
                            <h3><?php echo $pendingUsersCount; ?></h3> <!-- Dynamic count from database -->
                            <p>Pending Approvals</p> <!-- Card label -->
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- System Status Statistics Card -->
                    <!-- Static card showing system availability -->
                    <div class="col-md-6 mb-4">
                        <div class="glass-card stat-card text-center">
                            <i class="fas fa-check-circle stat-icon" style="color: #2ecc71;"></i> <!-- Green check icon -->
                            <h3>Online</h3> <!-- Static status indicator -->
                            <p>System Status</p> <!-- Card label -->
                        </div>
                    </div>
                    
                    <!-- Recent Activity Statistics Card -->
                    <!-- Shows count of recent login activities -->
                    <div class="col-md-6 mb-4">
                        <div class="glass-card stat-card text-center">
                            <i class="fas fa-chart-line stat-icon"></i> <!-- Chart icon for activity -->
                            <h3><?php echo count($recentActivity); ?></h3> <!-- Dynamic count from array -->
                            <p>Recent Logins</p> <!-- Card label -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions Panel Column (narrower column for navigation) -->
            <div class="col-md-4">
                <div class="glass-card">
                    <h4 class="mb-3"><i class="fas fa-bolt me-2"></i>Quick Actions</h4>
                    
                    <!-- Main Dashboard Quick Action -->
                    <!-- Primary navigation to main dashboard -->
                    <a href="main_dashboard.php" class="quick-action">
                        <i class="fas fa-home me-2"></i>Main Dashboard
                    </a>
                    
                    <!-- Staff List Quick Action -->
                    <!-- Navigation to view all staff members -->
                    <a href="staff_list.php" class="quick-action">
                        <i class="fas fa-list me-2"></i>View All Staff
                    </a>
                    
                    <!-- Admin-only quick actions -->
                    <?php if ($isAdmin): ?>
                    <!-- Add New Staff Quick Action (Admin only) -->
                    <!-- Navigation to staff creation form -->
                    <a href="staff_create.php" class="quick-action">
                        <i class="fas fa-user-plus me-2"></i>Add New Staff
                    </a>
                    
                    <!-- Pending Approvals Quick Action (Admin only, shows when there are pending approvals) -->
                    <!-- Conditional action that only appears when there are pending items -->
                    <!-- Red background highlights urgency -->
                    <?php if ($pendingUsersCount > 0): ?>
                    <a href="staff_pending_approvals.php" class="quick-action" style="background: rgba(231, 76, 60, 0.3);">
                        <i class="fas fa-exclamation-circle me-2"></i>Review Pending Approvals
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- My Profile Quick Action -->
                    <!-- Navigation to user's profile page -->
                    <a href="staff_profile.php" class="quick-action">
                        <i class="fas fa-user-cog me-2"></i>My Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity and System Information Row -->
        <!-- Secondary content area with activity feed and system info -->
        <div class="row">
            <!-- Recent Activity Panel Column (wider column for detailed list) -->
            <div class="col-md-8 mb-4">
                <div class="glass-card">
                    <h4 class="mb-3"><i class="fas fa-history me-2"></i>Recent Activity</h4>
                    
                    <?php if (!empty($recentActivity)): ?>
                        <!-- Activity list when there are recent logins -->
                        <ul class="activity-list">
                            <?php foreach ($recentActivity as $activity): ?>
                                <li class="activity-item">
                                    <div class="d-flex justify-content-between">
                                        <span>
                                            <i class="fas fa-user me-2"></i>
                                            <?php echo htmlspecialchars($activity['Username']); ?>
                                        </span>
                                        <span class="text-muted">
                                            <?php 
                                            // Format last login date or show 'Never' if no login recorded
                                            // Using htmlspecialchars for security even though date() returns safe format
                                            if ($activity['LastLogin']) {
                                                echo htmlspecialchars(date('M j, Y g:i A', strtotime($activity['LastLogin'])));
                                            } else {
                                                echo 'Never';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <!-- Empty state message when no activity exists -->
                        <p class="text-center text-muted py-3">No recent activity to display.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- System Information Panel Column (narrower column for user info) -->
            <div class="col-md-4 mb-4">
                <div class="glass-card">
                    <h4 class="mb-3"><i class="fas fa-info-circle me-2"></i>System Information</h4>
                    
                    <!-- Last Login Information -->
                    <!-- Shows user's last activity timestamp from session -->
                    <div class="mb-3">
                        <small class="text-muted">Last Login</small>
                        <div>
                            <i class="fas fa-calendar me-2"></i>
                            <?php 
                            // Display last activity timestamp or 'First login' if not set
                            if (isset($_SESSION['LAST_ACTIVITY'])) {
                                echo htmlspecialchars(date('M j, Y g:i A', $_SESSION['LAST_ACTIVITY']));
                            } else {
                                echo 'First login';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- User Role Information -->
                    <!-- Shows current user's role from session -->
                    <div class="mb-3">
                        <small class="text-muted">Your Role</small>
                        <div>
                            <i class="fas fa-user-tag me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['role'] ?? 'Not set'); ?>
                        </div>
                    </div>
                    
                    <!-- Additional Navigation Section -->
                    <!-- Secondary navigation options -->
                    <div class="mt-4 pt-3 border-top">
                        <small class="text-muted">Navigation</small>
                        <div>
                            <a href="main_dashboard.php" class="btn btn-sm btn-outline-light w-100 mt-2">
                                <i class="fas fa-home me-1"></i> Main Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JavaScript for interactive components -->
    <!-- Required for Bootstrap components that use JavaScript (modals, tooltips, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Optional: Add JavaScript for enhanced interactivity -->
    <!-- Example: Auto-refresh dashboard data or add animations -->
    <!--
    <script>
        // Example: Auto-refresh dashboard every 60 seconds
        setTimeout(function() {
            window.location.reload();
        }, 60000);
    </script>
    -->
</body>
</html>