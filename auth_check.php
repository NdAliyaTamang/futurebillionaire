<?php
// ===============================================
// Session, Role & Timeout Middleware
// ===============================================
// Purpose: Centralized security middleware for user authentication and authorization
// This file should be included at the top of every protected page in the staff portal

// Check if session is already started to avoid session start errors
// PHP_SESSION_NONE constant indicates sessions are not yet initialized
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start the session to access session variables
}

// Session timeout configuration (in seconds)
// 900 seconds = 15 minutes - balance between security and user convenience
$timeout_duration = 900;

// Redirect helper function for consistent login redirection
/**
 * Redirects user to login page with optional error reason
 * @param string $reason Optional error message to display on login page
 */
function redirectToLogin($reason = '') {
    $login_url = 'staff_login.php'; // Default login page URL
    
    // Append error reason as URL parameter if provided
    if (!empty($reason)) {
        // urlencode() ensures special characters are properly encoded for URL safety
        $login_url .= '?error=' . urlencode($reason);
    }
    
    // Perform the redirect and stop further script execution
    header("Location: " . $login_url);
    exit(); // CRITICAL: Stop script execution after header redirect
}

// ===============================================
// PRIMARY AUTHENTICATION CHECK
// ===============================================

// Check if user is logged in by verifying essential session variables exist
// $_SESSION['userID'] stores the unique identifier of the logged-in user
// $_SESSION['role'] stores the user's role (Admin/Staff) for authorization
if (!isset($_SESSION['userID']) || !isset($_SESSION['role'])) {
    // If either session variable is missing, user is not properly authenticated
    redirectToLogin('Please login to access this page');
}

// ===============================================
// SESSION TIMEOUT MANAGEMENT
// ===============================================

// Check for session timeout to automatically log out inactive users
// This prevents security risks from unattended logged-in sessions
if (isset($_SESSION['LAST_ACTIVITY'])) {
    // Calculate time elapsed since last activity
    $time_since_last_activity = time() - $_SESSION['LAST_ACTIVITY'];
    
    // Check if timeout duration has been exceeded
    if ($time_since_last_activity > $timeout_duration) {
        // Session has expired - clear all session data
        session_unset();    // Remove all session variables
        session_destroy();  // Destroy the session completely
        
        // Redirect to login with timeout reason
        redirectToLogin('session_timeout');
    }
}

// Update last activity timestamp on every page load
// This resets the timeout counter on user interaction
$_SESSION['LAST_ACTIVITY'] = time();

// ===============================================
// ROLE-BASED AUTHORIZATION FUNCTION
// ===============================================

/**
 * Enforce role-based access control (RBAC)
 * Restricts page access to users with specific roles
 * 
 * @param array $allowedRoles Array of role strings that are permitted
 * @return void If user's role is not in allowed roles, displays error and exits
 * 
 * Usage examples:
 *   requireRole(['Admin']);               // Only Admin can access
 *   requireRole(['Admin', 'Manager']);    // Admin OR Manager can access
 *   requireRole(['Staff', 'Teacher']);    // Staff OR Teacher can access
 */
function requireRole(array $allowedRoles) {
    // Check if user has a valid session role and if it's in the allowed roles list
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        // User does not have required role - display access denied message
        
        // Using inline CSS instead of external styles to ensure display even if stylesheets fail
        echo "<div style='
                background:#ffe6e6;                 /* Light red background for error */
                color:#c0392b;                     /* Dark red text for contrast */
                padding:15px;                      /* Comfortable internal spacing */
                border:1px solid #e74c3c;          /* Red border for emphasis */
                border-radius:8px;                 /* Rounded corners for modern look */
                margin:20px auto;                  /* Centered with top/bottom margin */
                width:80%;                         /* Responsive width */
                font-family:sans-serif;            /* Fallback font family */
                text-align:center;                 /* Center aligned text */
                box-shadow: 0 4px 6px rgba(0,0,0,0.1); /* Subtle shadow for depth */
             '>
              <h3 style=\"margin-top:0;color:#c0392b;\">
                <i class=\"fas fa-ban\" style=\"margin-right:8px;\"></i>
                Access Denied
              </h3>
              <p>You do not have permission to view this page.</p>
              <p><small>Required role: " . htmlspecialchars(implode(' or ', $allowedRoles)) . "</small></p>
              <hr style=\"border-color:#e74c3c;margin:15px 0;\">
              <a href='staff_list.php' 
                 style='
                    color:#2980b9;
                    text-decoration:none;
                    padding:8px 16px;
                    background:white;
                    border:1px solid #2980b9;
                    border-radius:4px;
                    display:inline-block;
                 '>
                 <i class=\"fas fa-arrow-left\" style=\"margin-right:5px;\"></i>
                 Go back to Staff List
              </a>
             </div>";
        
        // Stop script execution to prevent further page rendering
        exit();
    }
    
    // If user has required role, function completes silently and script continues
}

// ===============================================
// ADDITIONAL SECURITY RECOMMENDATIONS (COMMENTED)
// ===============================================

/*
// Optional: Regenerate session ID periodically to prevent session fixation attacks
// Recommended: Regenerate every 5-10 minutes or on privilege escalation
$session_regeneration_interval = 300; // 5 minutes
if (!isset($_SESSION['SESSION_REGENERATED']) || 
    (time() - $_SESSION['SESSION_REGENERATED']) > $session_regeneration_interval) {
    session_regenerate_id(true); // true = delete old session
    $_SESSION['SESSION_REGENERATED'] = time();
}

// Optional: Check user agent consistency to prevent session hijacking
if (isset($_SESSION['HTTP_USER_AGENT'])) {
    if ($_SESSION['HTTP_USER_AGENT'] !== $_SERVER['HTTP_USER_AGENT']) {
        // User agent changed - possible session hijacking
        session_unset();
        session_destroy();
        redirectToLogin('security_violation');
    }
} else {
    // Store user agent on first authenticated request
    $_SESSION['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
}

// Optional: Validate IP address (less recommended for mobile users/dynamic IPs)
if (isset($_SESSION['REMOTE_ADDR'])) {
    if ($_SESSION['REMOTE_ADDR'] !== $_SERVER['REMOTE_ADDR']) {
        // IP address changed - may indicate session theft
        // Note: This can cause issues for users with dynamic IPs
        // Consider logging instead of blocking for production
        error_log("IP mismatch for user {$_SESSION['userID']}");
    }
} else {
    $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
}
*/
?>