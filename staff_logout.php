<?php
// File: staff_logout.php
// Staff Logout Functionality - Handles user logout and session cleanup

// Start session to access session variables
session_start();

// Destroy all session data by emptying the session array
$_SESSION = array();

// If session cookies are used, delete the session cookie to completely clear session
if (ini_get("session.use_cookies")) {
    // Get session cookie parameters
    $params = session_get_cookie_params();
    // Set session cookie to expire in the past to effectively delete it
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session completely
session_destroy();

// Redirect to login page after successful logout
header("Location: staff_login.php");
exit();
?>