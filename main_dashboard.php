<?php
// File: main_dashboard.php
// Central Dashboard for School Management System - Provides overview of all system modules and statistics

// Enable error reporting for debugging during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple authentication check - redirect to login if user is not authenticated
if (!isset($_SESSION['userID']) || !isset($_SESSION['role'])) {
    header("Location: staff_login.php");
    exit();
}

// Include required files for database connection and authentication model
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_model.php';

// Initialize variables for dashboard statistics with default values
$pendingUsersCount = 0;
$totalStaffCount = 0;
$totalStudentsCount = 0;
$totalCoursesCount = 0;
$recentActivity = [];

try {
    // Get count of pending user approvals for admin display
    $pendingUsersCount = countPendingUsers();
    
    // Get total staff count from database
    $pdo = getDB();
    $stmt = $pdo->query("SELECT COUNT(*) FROM Staff WHERE IsActive = 1");
    $totalStaffCount = $stmt->fetchColumn();
    
    // Get total students count (if Student table exists) - wrapped in try/catch for safety
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM Student WHERE IsActive = 1");
        $totalStudentsCount = $stmt->fetchColumn();
    } catch (Exception $e) {
        $totalStudentsCount = 0; // Set to 0 if table doesn't exist yet
    }
    
    // Get total courses count (if Course table exists) - wrapped in try/catch for safety
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM Course WHERE IsActive = 1");
        $totalCoursesCount = $stmt->fetchColumn();
    } catch (Exception $e) {
        $totalCoursesCount = 0; // Set to 0 if table doesn't exist yet
    }
    
    // Get recent login activity for the activity feed
    $stmt = $pdo->query("
        SELECT Username, LastLogin, Role
        FROM User 
        WHERE LastLogin IS NOT NULL 
        ORDER BY LastLogin DESC 
        LIMIT 6
    ");
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Log database errors but don't break the page
    error_log("Dashboard data error: " . $e->getMessage());
}

// Check if current user has admin privileges for role-based content
$isAdmin = ($_SESSION['role'] === 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Dashboard - Future Billionaire Academy</title>
    <!-- Include Bootstrap CSS for responsive layout and components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Global body styling with gradient background */
        body {
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #182848, #4b6cb7);
            color: white;
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Glass morphism effect for cards */
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            height: 100%;
            transition: transform 0.3s ease;
        }
        
        /* Hover effect for glass cards */
        .glass-card:hover {
            transform: translateY(-5px);
        }
        
        /* Dashboard header styling */
        .dashboard-header {
            padding: 30px 0;
            text-align: center;
        }
        
        /* Welcome text styling with accent color */
        .welcome-text {
            color: #f1c40f;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        /* Statistics icon styling */
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        /* Module card styling for system modules */
        .module-card {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            padding: 20px;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-align: center;
            display: block;
            text-decoration: none;
            margin-bottom: 20px;
            height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        /* Hover effect for module cards */
        .module-card:hover {
            background: rgba(241, 196, 15, 0.3);
            color: white;
            transform: translateY(-5px);
            text-decoration: none;
        }
        
        /* Module icon styling */
        .module-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #f1c40f;
        }
        
        /* User info header styling */
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        /* Activity list styling for recent activity feed */
        .activity-list {
            list-style-type: none;
            padding: 0;
        }
        
        /* Individual activity item styling */
        .activity-item {
            padding: 10px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Remove border from last activity item */
        .activity-item:last-child {
            border-bottom: none;
        }
        
        /* Role badge styling for user roles in activity feed */
        .badge-role {
            background: rgba(52, 152, 219, 0.3);
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- User information and navigation header -->
        <div class="user-info d-flex justify-content-between align-items-center">
            <div>
                <strong>Future Billionaire Academy</strong> | Main Portal
            </div>
            <div>
                Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong> 
                (<?php echo htmlspecialchars($_SESSION['role'] ?? 'Staff'); ?>) | 
                <!-- Navigation links to key sections -->
                <a href="staff_dashboard.php" style="color: #f1c40f; margin-right: 10px;">Staff Dashboard</a>
                <a href="staff_list.php" style="color: #f1c40f; margin-right: 10px;">Staff List</a>
                <a href="staff_logout.php" style="color: #f1c40f;">Logout</a>
            </div>
        </div>

        <!-- Main dashboard header with school name and tagline -->
        <div class="dashboard-header">
            <h1 class="welcome-text">Future Billionaire Academy</h1>
            <p class="lead">Complete School Management System</p>
        </div>

        <!-- Statistics Overview Section -->
        <div class="row mb-5">
            <!-- Total Staff Stat Card -->
            <div class="col-md-3 mb-4">
                <div class="glass-card stat-card text-center">
                    <i class="fas fa-users stat-icon" style="color: #3498db;"></i>
                    <h3><?php echo $totalStaffCount; ?></h3>
                    <p>Total Staff</p>
                </div>
            </div>
            
            <!-- Total Students Stat Card -->
            <div class="col-md-3 mb-4">
                <div class="glass-card stat-card text-center">
                    <i class="fas fa-user-graduate stat-icon" style="color: #2ecc71;"></i>
                    <h3><?php echo $totalStudentsCount; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            
            <!-- Total Courses Stat Card -->
            <div class="col-md-3 mb-4">
                <div class="glass-card stat-card text-center">
                    <i class="fas fa-book stat-icon" style="color: #9b59b6;"></i>
                    <h3><?php echo $totalCoursesCount; ?></h3>
                    <p>Total Courses</p>
                </div>
            </div>
            
            <!-- Pending Approvals Stat Card (Admin Only) -->
            <?php if ($isAdmin): ?>
            <div class="col-md-3 mb-4">
                <div class="glass-card stat-card text-center">
                    <i class="fas fa-user-clock stat-icon" style="color: #e74c3c;"></i>
                    <h3><?php echo $pendingUsersCount; ?></h3>
                    <p>Pending Approvals</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- System Modules Grid Section -->
        <div class="row mb-5">
            <div class="col-12">
                <h3 class="text-center mb-4"><i class="fas fa-th-large me-2"></i>System Modules</h3>
            </div>
            
            <!-- Staff Management Module (Active) -->
            <div class="col-md-3 mb-4">
                <a href="staff_dashboard.php" class="module-card">
                    <i class="fas fa-users module-icon"></i>
                    <h5>Staff Management</h5>
                    <small>Manage staff members, roles, and departments</small>
                </a>
            </div>
            
            <!-- Student Management Module (Coming Soon) -->
            <div class="col-md-3 mb-4">
                <a href="#" class="module-card" style="background: rgba(255,255,255,0.1);">
                    <i class="fas fa-user-graduate module-icon"></i>
                    <h5>Student Management</h5>
                    <small><em>Coming Soon</em></small>
                </a>
            </div>
            
            <!-- Course Management Module (Coming Soon) -->
            <div class="col-md-3 mb-4">
                <a href="#" class="module-card" style="background: rgba(255,255,255,0.1);">
                    <i class="fas fa-book module-icon"></i>
                    <h5>Course Management</h5>
                    <small><em>Coming Soon</em></small>
                </a>
            </div>
            
            <!-- Enrollment Management Module (Coming Soon) -->
            <div class="col-md-3 mb-4">
                <a href="#" class="module-card" style="background: rgba(255,255,255,0.1);">
                    <i class="fas fa-clipboard-list module-icon"></i>
                    <h5>Enrollment Management</h5>
                    <small><em>Coming Soon</em></small>
                </a>
            </div>
        </div>

        <!-- Recent Activity and Quick Actions Section -->
        <div class="row">
            <!-- Recent Activity Column -->
            <div class="col-md-8 mb-4">
                <div class="glass-card">
                    <h4 class="mb-3"><i class="fas fa-history me-2"></i>Recent System Activity</h4>
                    
                    <?php if (!empty($recentActivity)): ?>
                        <ul class="activity-list">
                            <?php foreach ($recentActivity as $activity): ?>
                                <li class="activity-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-user me-2"></i>
                                            <strong><?php echo htmlspecialchars($activity['Username']); ?></strong>
                                            <span class="badge-role ms-2"><?php echo htmlspecialchars($activity['Role']); ?></span>
                                        </div>
                                        <span class="text-muted">
                                            <?php 
                                            // Format last login date or show 'Never logged in'
                                            if ($activity['LastLogin']) {
                                                echo date('M j, Y g:i A', strtotime($activity['LastLogin']));
                                            } else {
                                                echo 'Never logged in';
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
            
            <!-- Quick Actions Column -->
            <div class="col-md-4 mb-4">
                <div class="glass-card">
                    <h4 class="mb-3"><i class="fas fa-bolt me-2"></i>Quick Actions</h4>
                    
                    <div class="d-grid gap-2">
                        <!-- Quick action buttons for common tasks -->
                        <a href="staff_dashboard.php" class="btn btn-outline-light text-start">
                            <i class="fas fa-tachometer-alt me-2"></i>Staff Dashboard
                        </a>
                        
                        <a href="staff_list.php" class="btn btn-outline-light text-start">
                            <i class="fas fa-list me-2"></i>View Staff List
                        </a>
                        
                        <?php if ($isAdmin): ?>
                        <!-- Admin-only quick actions -->
                        <a href="staff_create.php" class="btn btn-outline-light text-start">
                            <i class="fas fa-user-plus me-2"></i>Add New Staff
                        </a>
                        
                        <?php if ($pendingUsersCount > 0): ?>
                        <!-- Highlighted action when pending approvals exist -->
                        <a href="staff_pending_approvals.php" class="btn btn-outline-warning text-start">
                            <i class="fas fa-exclamation-circle me-2"></i>Pending Approvals (<?php echo $pendingUsersCount; ?>)
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <a href="staff_profile.php" class="btn btn-outline-light text-start">
                            <i class="fas fa-user-cog me-2"></i>My Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JavaScript for interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>