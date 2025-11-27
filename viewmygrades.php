<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

requireRole(['Student']);

$pdo = getDB();
$userID = $_SESSION['userID'];

// Get Student profile
$q = $pdo->prepare("SELECT StudentID, GPA FROM student WHERE UserID = ?");
$q->execute([$userID]);
$student = $q->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student not found");
}

$studentID = $student['StudentID'];
$gpa       = $student['GPA'];

// Fetch grades from enrollment
$sql = "
    SELECT c.CourseName, c.CourseCode, c.Credits,
           e.FinalGrade, e.Status
    FROM enrollment e
    INNER JOIN course c ON e.CourseID = c.CourseID
    WHERE e.StudentID = ?
    ORDER BY c.CourseName ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$studentID]);
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>My Grades</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background: linear-gradient(135deg,#212e68,#9e69d3);
    min-height:100vh;
    padding:40px;
    font-family:Poppins,sans-serif;
}
.box{
    max-width:850px;
    margin:auto;
    background:#fff;
    padding:25px;
    border-radius:12px;
}
.grade-card{
    background:#f4f6f9;
    border-left:5px solid #9b59b6;
    padding:15px;
    margin-bottom:15px;
    border-radius:10px;
}
.gpa-box{
    background:#4b6cb7;
    padding:18px;
    border-radius:10px;
    color:#fff;
    margin-bottom:20px;
    text-align:center;
}
</style>
</head>
<body>

<div class="box">
<h2 class="text-center"> My Grades</h2>

<div class="gpa-box">
    <h4>Your GPA: <?= $gpa !== null ? $gpa : "N/A" ?></h4>
</div>

<?php if (empty($grades)): ?>
    <p>No grades available yet.</p>
<?php else: ?>
    <?php foreach ($grades as $g): ?>
        <div class="grade-card">
            <h4><?= htmlspecialchars($g['CourseName']) ?> (<?= htmlspecialchars($g['CourseCode']) ?>)</h4>
            <p><strong>Credits:</strong> <?= $g['Credits'] ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($g['Status']) ?></p>
            <p><strong>Final Grade:</strong> <?= $g['FinalGrade'] !== null ? $g['FinalGrade'] : "Not Graded" ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<a href="dashboard.php" class="btn btn-primary mt-3">⬅ Back to Dashboard</a>
</div>

</body>
</html>
