<?php
require_once '../includes/db.php';
$pdo = getDB();

// change to match your actual username in the User table
$username = 'admin';  

$stmt = $pdo->prepare("SELECT PasswordHash FROM User WHERE Username = ?");
$stmt->execute([$username]);
$row = $stmt->fetch();

echo "<h3>Testing password for user: $username</h3>";
if (!$row) {
    echo "❌ No such user found.";
    exit;
}

$hash = $row['PasswordHash'];
echo "Stored hash: " . htmlspecialchars($hash) . "<br>";

if (password_verify('123456', $hash)) {
    echo "<b style='color:green;'>✅ password_verify() returned TRUE</b>";
} else {
    echo "<b style='color:red;'>❌ password_verify() returned FALSE</b>";
}
?>
