<?php
// File: auth_model.php
// Authentication Model - Handles all user authentication and authorization database operations
// Purpose: Provides functions for user verification, password management, and user status management

require_once 'db.php';    // DB connection helper - provides getDB() function
// require_once 'audit.php'; // Comment out or remove this line - Audit logging disabled for now

/**
 * Get user by username and role - for role-specific authentication
 * @param string $username The username to search for
 * @param string $role The user's role (Admin, Staff, Student, etc.)
 * @return array|false User data as associative array or false if not found
 * 
 * Security Note: This ensures users can only login with their correct role assignment
 */
function getUserByUsernameAndRole($username, $role) {
    $pdo = getDB(); // Get database connection
    
    $stmt = $pdo->prepare("
        SELECT *
        FROM user
        WHERE Username = ? AND Role = ?
        LIMIT 1
    ");
    $stmt->execute([$username, $role]);
    return $stmt->fetch(PDO::FETCH_ASSOC); // Return single row as associative array
}

/**
 * Get user by username (without role filter) - used for password reset and logging
 * @param string $username The username to search for
 * @return array|false User data as associative array or false if not found
 * 
 * Note: Less restrictive than getUserByUsernameAndRole - used when role is unknown
 */
function getUserByUsername($username) {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT * FROM user WHERE Username = ? LIMIT 1");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Verify user login credentials - core authentication function
 * @param string $username The username provided
 * @param string $passwordPlain The plain text password (will be verified against hash)
 * @param string $role The expected user role
 * @return array|false User data if authenticated, false if authentication fails
 * 
 * Security Features:
 * 1. Checks both username and role for multi-role user support
 * 2. Only allows active users (IsActive = 1) to login
 * 3. Uses password_verify() for secure password comparison
 * 4. Updates login count and timestamp on successful login
 */
function verifyUser($username, $passwordPlain, $role) {
    $pdo = getDB();

    // Query for user with matching username, role, and active status
    $stmt = $pdo->prepare("
        SELECT *
        FROM user
        WHERE Username = ? AND Role = ? AND IsActive = 1
    ");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password using PHP's built-in password_verify() function
    // This securely compares the plain text password with the stored hash
    if ($user && password_verify($passwordPlain, $user['PasswordHash'])) {
        // Update login statistics on successful authentication
        $pdo->prepare("
            UPDATE user 
            SET LoginCount = LoginCount + 1,
                LastLogin = NOW()
            WHERE UserID = ?
        ")->execute([$user['UserID']]);

        // Audit logging (commented out for now)
        // logAction($user['UserID'], "Login Successful", "User", $user['UserID'], "Correct credentials");
        
        return $user; // Return user data for session creation
    }

    return false; // Authentication failed
}

/**
 * Create password reset token for a user
 * @param string $usernameOrEmail Username or email address to identify user
 * @return string|false Reset token if successful, false if user not found or error
 * 
 * Security Features:
 * 1. Generates cryptographically secure random token
 * 2. Tokens expire after 30 minutes
 * 3. Clears any existing pending tokens for the user
 * 4. Searches across multiple tables (user, student, staff) for email matching
 */
function createPasswordResetToken($usernameOrEmail) {
    try {
        $pdo = getDB();

        // Find user by username or email (searching across related tables)
        $stmt = $pdo->prepare("
            SELECT u.UserID
            FROM user u
            LEFT JOIN student s ON u.UserID = s.UserID
            LEFT JOIN staff st ON u.UserID = st.UserID
            WHERE u.Username = :ue OR s.Email = :ue OR st.Email = :ue
            LIMIT 1
        ");
        $stmt->execute([':ue' => $usernameOrEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return false; // User not found

        // Generate secure random token (64 hex characters = 32 bytes)
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes')); // 30-minute expiry

        // Remove any existing pending tokens for this user (prevent token flooding)
        $pdo->prepare("
            DELETE FROM passwordreset
            WHERE UserID = :uid AND Status = 'Pending'
        ")->execute(['uid' => $user['UserID']]);

        // Insert new reset token
        $insert = $pdo->prepare("
            INSERT INTO passwordreset (UserID, Token, ExpiresAt, Status)
            VALUES (:uid, :token, :exp, 'Pending')
        ");
        $insert->execute([
            ':uid'   => $user['UserID'],
            ':token' => $token,
            ':exp'   => $expiresAt
        ]);

        return $token; // Return token for emailing to user

    } catch (PDOException $e) {
        // Log error but don't expose details to user
        error_log("Password reset token creation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify password reset token validity
 * @param string $token The reset token to verify
 * @return array|false Token data if valid and not expired, false otherwise
 * 
 * Features:
 * 1. Automatically expires old tokens on verification attempt
 * 2. Checks token status is 'Pending'
 * 3. Includes user information in return data
 */
function verifyPasswordResetToken($token) {
    $pdo = getDB();

    // Clean up expired tokens before checking new one
    $pdo->prepare("
        UPDATE passwordreset 
        SET Status = 'Expired'
        WHERE ExpiresAt < NOW() AND Status = 'Pending'
    ")->execute();

    // Verify token exists, is pending, and get associated user info
    $stmt = $pdo->prepare("
        SELECT pr.*, u.Username
        FROM passwordreset pr
        JOIN user u ON pr.UserID = u.UserID
        WHERE pr.Token = :token AND pr.Status = 'Pending'
    ");
    $stmt->execute([':token' => $token]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Reset user password using valid token
 * @param string $token Valid password reset token
 * @param string $newPassword New plain text password
 * @return bool True if password reset successful, false otherwise
 * 
 * Security Features:
 * 1. Verifies token before allowing reset
 * 2. Uses password_hash() for secure password storage
 * 3. Marks token as 'Used' after successful reset
 * 4. Updates password in user table
 */
function resetPassword($token, $newPassword) {
    $pdo = getDB();
    $tokenData = verifyPasswordResetToken($token); // Verify token first

    if (!$tokenData) return false; // Invalid or expired token

    // Update user's password with new hash
    $pdo->prepare("
        UPDATE user 
        SET PasswordHash = :pw 
        WHERE UserID = :uid
    ")->execute([
        ':pw'  => password_hash($newPassword, PASSWORD_DEFAULT),
        ':uid' => $tokenData['UserID']
    ]);

    // Mark token as used to prevent reuse
    $pdo->prepare("
        UPDATE passwordreset
        SET Status = 'Used', UsedAt = NOW()
        WHERE Token = :t
    ")->execute([':t' => $token]);

    return true; // Password reset successful
}

/**
 * Get all pending user approvals (users awaiting activation)
 * @return array List of pending users with their details
 * 
 * Note: Used by admin interface to review new user registrations
 */
function getPendingUsers() {
    $pdo = getDB();
    $stmt = $pdo->query("
        SELECT UserID, Username, Role, CreatedDate
        FROM user
        WHERE IsActive = 0
        ORDER BY CreatedDate DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Activate (approve) a user and their associated profile
 * @param int $userId The UserID to activate
 * @return bool True if activation successful, false on error
 * 
 * Features:
 * 1. Uses database transaction for atomic operations
 * 2. Activates user in all related tables (user, staff, student)
 * 3. Rolls back on error to maintain data consistency
 */
function activateUser($userId) {
    $pdo = getDB();

    try {
        $pdo->beginTransaction(); // Start transaction

        // Activate user across all relevant tables
        $pdo->prepare("UPDATE user SET IsActive = 1 WHERE UserID = ?")->execute([$userId]);
        $pdo->prepare("UPDATE staff SET IsActive = 1 WHERE UserID = ?")->execute([$userId]);
        $pdo->prepare("UPDATE student SET IsActive = 1 WHERE UserID = ?")->execute([$userId]);

        $pdo->commit(); // Commit all changes
        
        // Audit logging (commented out for now)
        // logAction(null, "User approved", "User", $userId, "Approved by Admin");
        return true;

    } catch (PDOException $e) {
        $pdo->rollBack(); // Rollback on error
        error_log("User activation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Deactivate a user and their associated profile
 * @param int $userId The UserID to deactivate
 * @return bool True if deactivation successful, false on error
 * 
 * Note: Similar to activateUser but sets IsActive = 0
 */
function deactivateUser($userId) {
    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        // Deactivate user across all relevant tables
        $pdo->prepare("UPDATE user SET IsActive = 0 WHERE UserID = ?")->execute([$userId]);
        $pdo->prepare("UPDATE staff SET IsActive = 0 WHERE UserID = ?")->execute([$userId]);
        $pdo->prepare("UPDATE student SET IsActive = 0 WHERE UserID = ?")->execute([$userId]);

        $pdo->commit();
        
        // Audit logging (commented out for now)
        // logAction(null, "User deactivated", "User", $userId, "Disabled by Admin");
        return true;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("User deactivation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Count pending users awaiting approval
 * @return int Number of pending users
 * 
 * Note: Used for dashboard statistics and notification badges
 */
function countPendingUsers() {
    $pdo = getDB();
    return $pdo->query("SELECT COUNT(*) FROM user WHERE IsActive = 0")->fetchColumn();
}

/**
 * Create a new user account
 * @param string $username Desired username
 * @param string $passwordPlain Plain text password (will be hashed)
 * @param string $role User role (Admin, Staff, Student, etc.)
 * @return int|false New UserID if successful, false on error
 * 
 * Security Features:
 * 1. Uses password_hash() for secure password storage
 * 2. Sets user as active immediately (IsActive = 1)
 * 3. Uses transaction for data consistency
 * 
 * Note: This function creates only the user record. Additional profile
 * creation (staff/student) should be handled separately.
 */
function createUser($username, $passwordPlain, $role) {
    $pdo = getDB();
    
    try {
        $pdo->beginTransaction();
        
        // Insert new user with hashed password
        $stmt = $pdo->prepare("
            INSERT INTO user (Username, PasswordHash, Role, IsActive, CreatedDate) 
            VALUES (?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $username,
            password_hash($passwordPlain, PASSWORD_DEFAULT), // Hash password
            $role
        ]);
        
        $userId = $pdo->lastInsertId(); // Get the new UserID
        $pdo->commit();
        
        return $userId;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("createUser Error: " . $e->getMessage());
        return false;
    }
}
?>