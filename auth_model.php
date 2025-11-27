<?php
require_once 'db.php';    // DB connection helper
require_once 'audit.php'; // Audit logging helper

// Get user by username + role
function getUserByUsernameAndRole($username, $role) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT *
        FROM user
        WHERE Username = ? AND Role = ?
        LIMIT 1
    ");
    $stmt->execute([$username, $role]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get user by username (no role) - used by password reset / logging
function getUserByUsername($username) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM user WHERE Username = ? LIMIT 1");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Verify login with username + password + role
function verifyUser($username, $passwordPlain, $role) {
    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT *
        FROM user
        WHERE Username = ? AND Role = ? AND IsActive = 1
    ");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($passwordPlain, $user['PasswordHash'])) {
        $pdo->prepare("
            UPDATE user 
            SET LoginCount = LoginCount + 1,
                LastLogin = NOW()
            WHERE UserID = ?
        ")->execute([$user['UserID']]);

        logAction($user['UserID'], "Login Successful", "User", $user['UserID'], "Correct credentials");
        return $user;
    }

    return false;
}

// Create password reset token
function createPasswordResetToken($usernameOrEmail) {
    try {
        $pdo = getDB();

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

        if (!$user) return false;

        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $pdo->prepare("
            DELETE FROM passwordreset
            WHERE UserID = :uid AND Status = 'Pending'
        ")->execute(['uid' => $user['UserID']]);

        $insert = $pdo->prepare("
            INSERT INTO passwordreset (UserID, Token, ExpiresAt, Status)
            VALUES (:uid, :token, :exp, 'Pending')
        ");
        $insert->execute([
            ':uid'   => $user['UserID'],
            ':token' => $token,
            ':exp'   => $expiresAt
        ]);

        return $token;

    } catch (PDOException $e) {
        return false;
    }
}

// Verify reset token and expire old ones
function verifyPasswordResetToken($token) {
    $pdo = getDB();

    $pdo->prepare("
        UPDATE passwordreset 
        SET Status = 'Expired'
        WHERE ExpiresAt < NOW() AND Status = 'Pending'
    ")->execute();

    $stmt = $pdo->prepare("
        SELECT pr.*, u.Username
        FROM passwordreset pr
        JOIN user u ON pr.UserID = u.UserID
        WHERE pr.Token = :token AND pr.Status = 'Pending'
    ");
    $stmt->execute([':token' => $token]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Reset password and mark token used
function resetPassword($token, $newPassword) {
    $pdo = getDB();
    $tokenData = verifyPasswordResetToken($token);

    if (!$tokenData) return false;

    $pdo->prepare("
        UPDATE user 
        SET PasswordHash = :pw 
        WHERE UserID = :uid
    ")->execute([
        ':pw'  => password_hash($newPassword, PASSWORD_DEFAULT),
        ':uid' => $tokenData['UserID']
    ]);

    $pdo->prepare("
        UPDATE passwordreset
        SET Status = 'Used', UsedAt = NOW()
        WHERE Token = :t
    ")->execute([':t' => $token]);

    return true;
}

// Get all pending users
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

// Approve (activate) user + profile
function activateUser($userId) {
    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE user SET IsActive = 1 WHERE UserID = ?")->execute([$userId]);
        $pdo->prepare("UPDATE staff SET IsActive = 1 WHERE UserID = ?")->execute([$userId]);
        $pdo->prepare("UPDATE student SET IsActive = 1 WHERE UserID = ?")->execute([$userId]);

        $pdo->commit();
        logAction(null, "User approved", "User", $userId, "Approved by Admin");
        return true;

    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

// Deactivate user + profile
function deactivateUser($userId) {
    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE user SET IsActive = 0 WHERE UserID = ?")->execute([$userId]);
        $pdo->prepare("UPDATE staff SET IsActive = 0 WHERE UserID = ?")->execute([$userId]);
        $pdo->prepare("UPDATE student SET IsActive = 0 WHERE UserID = ?")->execute([$userId]);

        $pdo->commit();
        logAction(null, "User deactivated", "User", $userId, "Disabled by Admin");
        return true;

    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

// Count pending users
function countPendingUsers() {
    $pdo = getDB();
    return $pdo->query("SELECT COUNT(*) FROM user WHERE IsActive = 0")->fetchColumn();
}
?>
