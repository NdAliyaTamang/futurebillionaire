<?php

// Session, Role & Timeout Middleware


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout (in seconds)
$timeout_duration = 900; // 15 minutes

// Redirect helper
function redirectToLogin($reason = 'unauthorized') {
    header("Location: ../user_code/login.php?error=" . urlencode($reason));
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['userID']) || !isset($_SESSION['role'])) {
    redirectToLogin();
}


// Session timeout check

if (isset($_SESSION['LAST_ACTIVITY'])) {
    if ((time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        session_unset();
        session_destroy();
        redirectToLogin('timeout');
    }
}
$_SESSION['LAST_ACTIVITY'] = time(); // update activity timestamp

/**
 * Enforce role-based access
 * Usage: requireRole(['Admin']);  // restricts page to Admin only
 */
function requireRole(array $allowedRoles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        echo "<div style='
                background:#ffe6e6;
                color:#c0392b;
                padding:15px;
                border:1px solid #e74c3c;
                border-radius:8px;
                margin:20px auto;
                width:80%;
                font-family:sans-serif;
                text-align:center;
             '>
             ⚠️ Access Denied: You do not have permission to view this page.
             <br><br>
             <a href='../user_code/dashboard.php' style='color:#2980b9;text-decoration:none;'>Go back to Dashboard</a>
             </div>";
        exit();
    }
}
?>
