<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

requireRole(['Staff']);
$username = $_SESSION['username'];
$pdo = getDB();

// Get Staff ID
$stmt = $pdo->prepare("SELECT StaffID FROM Staff 
                       INNER JOIN User ON Staff.UserID = User.UserID 
                       WHERE User.Username = ?");
$stmt->execute([$username]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);
$staffID = $staff['StaffID'] ?? 0;

// Fetch students enrolled in this staff's courses
$query = "
    SELECT s.StudentID, CONCAT(s.FirstName, ' ', s.LastName) AS StudentName, 
           s.Email, c.CourseName
    FROM Enrollment e
    INNER JOIN Student s ON e.StudentID = s.StudentID
    INNER JOIN Course c ON e.CourseID = c.CourseID
    WHERE c.StaffID = ?
    ORDER BY c.CourseName, s.FirstName
";
$stmt = $pdo->prepare($query);
$stmt->execute([$staffID]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Enrolled Students</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body {
    background: linear-gradient(135deg, #141E30, #243B55);
    color: #fff;
    font-family: "Poppins", sans-serif;
  }
  .container {
    background: rgba(255,255,255,0.1);
    border-radius: 15px;
    padding: 30px;
    margin-top: 40px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
  }
  table { color: #fff; margin-top: 20px; }
  th { background: #4b6cb7; }
  a.btn-back {
    margin-top: 15px;
    display: inline-block;
    background: #3498db;
    color: #fff;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
  }
  a.btn-back:hover { background: #21618c; }
</style>
</head>
<body>
<div class="container">
  <h2 class="text-center mb-4">🎓 Enrolled Students</h2>

  <?php if (empty($students)): ?>
    <div class="alert alert-info text-center">No students enrolled in your courses yet.</div>
  <?php else: ?>
  <table class="table table-striped table-hover">
    <thead class="table-dark">
      <tr>
        <th>Student ID</th>
        <th>Student Name</th>
        <th>Email</th>
        <th>Course</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($students as $s): ?>
      <tr>
        <td><?= $s['StudentID'] ?></td>
        <td><?= htmlspecialchars($s['StudentName']) ?></td>
        <td><?= htmlspecialchars($s['Email']) ?></td>
        <td><?= htmlspecialchars($s['CourseName']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <div class="text-center">
    <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
  </div>
</div>
</body>
</html>
