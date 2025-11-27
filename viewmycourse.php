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

// Fetch courses taught by this staff
$stmt = $pdo->prepare("SELECT CourseID, CourseName, CourseCode, Credits, Fee, StartDate, IsActive 
                       FROM Course WHERE StaffID = ?");
$stmt->execute([$staffID]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Courses</title>
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
  table {
    color: #fff;
    margin-top: 20px;
  }
  th {
    background: #4b6cb7;
  }
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
  <h2 class="text-center mb-4">📘 My Courses</h2>

  <?php if (empty($courses)): ?>
    <div class="alert alert-info text-center">You are not assigned to any courses yet.</div>
  <?php else: ?>
  <table class="table table-striped table-hover">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Course Name</th>
        <th>Code</th>
        <th>Credits</th>
        <th>Fee</th>
        <th>Start Date</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($courses as $c): ?>
      <tr>
        <td><?= $c['CourseID'] ?></td>
        <td><?= htmlspecialchars($c['CourseName']) ?></td>
        <td><?= htmlspecialchars($c['CourseCode']) ?></td>
        <td><?= $c['Credits'] ?></td>
        <td>$<?= number_format($c['Fee'], 2) ?></td>
        <td><?= $c['StartDate'] ?></td>
        <td>
          <span class="badge <?= $c['IsActive'] ? 'bg-success' : 'bg-secondary' ?>">
            <?= $c['IsActive'] ? 'Active' : 'Inactive' ?>
          </span>
        </td>
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
