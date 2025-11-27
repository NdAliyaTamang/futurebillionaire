<?php
session_start(); // Session

require_once '../includes/auth_model.php'; // Auth model
require_once '../includes/audit.php'; // Audit
require_once '../includes/db.php'; // DB

$error = ''; // Error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username  = trim($_POST['username']); // Username
    $password  = $_POST['password']; // Password
    $role      = trim($_POST['role']); // Role
    $admin_pin = $_POST['admin_pin'] ?? null; // Admin PIN

    $user = getUserByUsername($username); // Fetch user
    $pdo  = getDB(); // DB

    // Log attempt
    function logLoginAttempt($pdo, $userID, $success) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO loginattempts (UserID, IsSuccessful, AttemptTime)
                VALUES (:u, :s, NOW())
            ");
            $stmt->execute([':u' => $userID, ':s' => $success ? 1 : 0]);
        } catch (PDOException $e) { }
    }

    // User not found
    if (!$user) {
        $error = "❌ User not found!";
        logLoginAttempt($pdo, null, false);
        goto endLogin;
    }

    // Inactive
    if ($user['IsActive'] == 0) {
        $error = "⚠️ Account pending admin approval.";
        logLoginAttempt($pdo, $user['UserID'], false);
        goto endLogin;
    }

    // Wrong role
    if ($user['Role'] !== $role) {
        $error = "❌ Role mismatch!";
        logLoginAttempt($pdo, $user['UserID'], false);
        goto endLogin;
    }

    // Admin PIN check
    if ($role === "Admin") {

        // 6 digit PIN required
        if (empty($admin_pin) || !preg_match("/^[0-9]{6}$/", $admin_pin)) {
            $error = "❌ Admin PIN must be exactly 6 digits!";
            logLoginAttempt($pdo, $user['UserID'], false);
            goto endLogin;
        }

        // Get admin
        $stmt = $pdo->prepare("SELECT AdminPINHash FROM admin WHERE UserID = ?");
        $stmt->execute([$user['UserID']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            $error = "❌ Admin record not found!";
            goto endLogin;
        }

        // Verify PIN hash
        if (!password_verify($admin_pin, $admin['AdminPINHash'])) {
            $error = "❌ Incorrect Admin PIN!";
            goto endLogin;
        }
    }

    // Verify password
    $result = verifyUser($username, $password, $role);

    if ($result && is_array($result)) {

        $_SESSION['userID']   = $result['UserID']; 
        $_SESSION['role']     = $result['Role'];
        $_SESSION['username'] = $result['Username'];

        session_regenerate_id(true);

        header("Location: dashboard.php");
        exit();
    }

    $error = "❌ Invalid username or password!";
}

endLogin:
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | School Management</title>
  <link rel="stylesheet" href="../assets/css/auth-style.css">

  <style>
    body {
      background: linear-gradient(135deg, #4b6cb7, #182848);
      font-family: "Poppins", sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
    }
    .auth-container {
      background: #fff;
      padding: 40px 30px;
      border-radius: 16px;
      width: 400px;
      text-align: center;
      box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    }
    label {
      text-align: left;
      display: block;
      font-size: 14px;
      margin-top: 10px;
      color: #555;
    }
    input, select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }
    button {
      width: 100%;
      padding: 10px;
      background: linear-gradient(135deg, #4b6cb7, #182848);
      border: none;
      border-radius: 8px;
      margin-top: 20px;
      color: #fff;
      font-weight: 600;
      cursor: pointer;
    }
    .error-msg { 
      color: #e74c3c; 
      margin-top: 10px; 
      font-weight: 500; 
    }
  </style>
</head>
<body>

<div class="auth-container">
  <h2>Welcome Back 👋</h2>

  <?php if ($error): ?>
      <p class="error-msg"><?= $error ?></p>
  <?php endif; ?>

  <form method="POST">

    <label>Username</label>
    <input type="text" name="username" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <label>Select Role</label>
    <select name="role" id="role-select" required>
      <option value="">-- Choose Role --</option>
      <option value="Admin">Admin</option>
      <option value="Staff">Staff</option>
      <option value="Student">Student</option>
    </select>

    <!-- Admin PIN -->
    <div id="adminPinField" style="display:none;">
      <label>Admin PIN</label>
      <input type="password"
             name="admin_pin"
             maxlength="6"
             pattern="\d{6}"
             placeholder="Enter 6-digit Admin PIN">
      <small style="font-size:12px; color:#888;">Admin PIN must be exactly 6 digits.</small>
    </div>

    <button type="submit">Login</button>

  </form>

  <p><a href="reset_request.php">Forgot Password?</a></p>
  <p>Don't have an account? <a href="register.php">Register here</a></p>

</div>

<script>
// Toggle PIN field
document.addEventListener("DOMContentLoaded", () => {
    const role = document.getElementById("role-select");
    const pin  = document.getElementById("adminPinField");

    role.addEventListener("change", () => {
        pin.style.display = role.value === "Admin" ? "block" : "none";
    });
});
</script>

</body>
</html>
