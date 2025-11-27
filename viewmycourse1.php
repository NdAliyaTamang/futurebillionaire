<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

requireRole(['Student']); // Only students can access

$pdo = getDB();
$userID = $_SESSION['userID'];

// Get StudentID from user
$q = $pdo->prepare("SELECT StudentID FROM student WHERE UserID = ?");
$q->execute([$userID]);
$student = $q->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student profile not found");
}

$studentID = $student['StudentID'];

// Fetch all enrolled courses
$sql = "
    SELECT c.CourseName, c.CourseCode, c.Credits, c.Description, c.StartDate,
           e.EnrollmentDate, e.Status
    FROM enrollment e
    INNER JOIN course c ON e.CourseID = c.CourseID
    WHERE e.StudentID = ?
    ORDER BY c.CourseName ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$studentID]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>My Courses</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{
    background: linear-gradient(135deg,#212e68,#9e69d3);
    min-height:100vh;
    padding:40px;
    font-family:Poppins,sans-serif;
}
.box{
    max-width:800px;
    margin:auto;
    background:#fff;
    border-radius:12px;
    padding:25px;
}
h2{ text-align:center; margin-bottom:25px; }
.course-box{
    padding:15px;
    border-radius:10px;
    margin-bottom:15px;
    background:#f4f6f9;
    border-left:5px solid #4b6cb7;
}
</style>
</head>
<body>

<div class="box">
<h2>My Enrolled Courses</h2>

<?php if (empty($courses)): ?>
    <p>No courses enrolled yet.</p>
<?php else: ?>
    <?php foreach ($courses as $c): ?>
        <div class="course-box">
            <h4><?= htmlspecialchars($c['CourseName']) ?> (<?= htmlspecialchars($c['CourseCode']) ?>)</h4>
            <p><strong>Credits:</strong> <?= $c['Credits'] ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($c['Status']) ?></p>
            <p><strong>Start Date:</strong> <?= htmlspecialchars($c['StartDate']) ?></p>
            <p><strong>Enrolled On:</strong> <?= htmlspecialchars($c['EnrollmentDate']) ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<a href="dashboard.php" class="btn btn-primary mt-3">⬅ Back to Dashboard</a>
</div>

</body>
</html>
