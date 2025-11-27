<?php
require_once '../includes/db.php';
require_once '../includes/auth_model.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $dob = $_POST['dob'];
    $age = (date('Y') - date('Y', strtotime($dob)));

    try {
        $pdo = getDB();
        $pdo->beginTransaction();

        // 1️⃣ Create new user (inactive until admin approves)
        $stmtUser = $pdo->prepare("
            INSERT INTO User (Username, PasswordHash, Role, IsActive)
            VALUES (?, ?, 'Student', 0)
        ");
        $stmtUser->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        $userId = $pdo->lastInsertId();

        // 2️⃣ Create linked student profile
        $stmtStudent = $pdo->prepare("
            INSERT INTO Student (UserID, FirstName, LastName, DateOfBirth, Email, Age, GPA, IsActive)
            VALUES (?, ?, ?, ?, ?, ?, NULL, 1)
        ");
        $stmtStudent->execute([$userId, $firstName, $lastName, $dob, $email, $age]);

        $pdo->commit();
        $message = "<p class='success-msg'>✅ Registration successful! Your account is pending admin approval.</p>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "<p class='error-msg'>⚠️ Registration failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Registration</title>
  <style>
    body {
      background: linear-gradient(135deg, #182848, #4b6cb7);
      font-family: "Poppins", sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      color: #333;
      margin: 0;
    }
    .auth-container {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.25);
      width: 420px;
      max-width: 90%;
      padding: 40px 30px;
      text-align: center;
      animation: fadeIn 0.6s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    h2 {
      margin-bottom: 20px;
      color: #182848;
      font-size: 22px;
      font-weight: 600;
    }
    label {
      display: block;
      text-align: left;
      color: #555;
      font-size: 14px;
      margin-top: 10px;
    }
    input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      margin-top: 5px;
      outline: none;
      transition: border 0.3s ease;
    }
    input:focus {
      border-color: #4b6cb7;
      box-shadow: 0 0 4px rgba(75,108,183,0.4);
    }
    button {
      margin-top: 20px;
      width: 100%;
      padding: 10px;
      background: linear-gradient(135deg, #4b6cb7, #182848);
      color: white;
      font-weight: 600;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: transform 0.2s, background 0.3s;
    }
    button:hover {
      transform: translateY(-2px);
      background: linear-gradient(135deg, #5d7ad2, #223b7a);
    }
    .success-msg {
      color: #2ecc71;
      font-weight: 600;
      margin-top: 10px;
    }
    .error-msg {
      color: #e74c3c;
      font-weight: 600;
      margin-top: 10px;
    }
    a {
      display: inline-block;
      margin-top: 15px;
      text-decoration: none;
      color: #4b6cb7;
      font-weight: 500;
      transition: color 0.2s;
    }
    a:hover { color: #182848; }
  </style>
</head>
<body>
  <div class="auth-container">
    <h2> Student Registration</h2>
    <?php echo $message; ?>
    <form method="POST">
      <label>Username</label>
      <input type="text" name="username" placeholder="Enter username" required>

      <label>Password</label>
      <input type="password" name="password" placeholder="Enter password" required>

      <label>First Name</label>
      <input type="text" name="first_name" placeholder="Enter first name" required>

      <label>Last Name</label>
      <input type="text" name="last_name" placeholder="Enter last name" required>

      <label>Email</label>
      <input type="email" name="email" placeholder="Enter email address" required>

      <label>Date of Birth</label>
      <input type="date" name="dob" required>

    <a href="login.php">Register</a>
    </form>
    <a href="login.php">⬅ Back to Login</a>
  </div>
</body>
</html>
