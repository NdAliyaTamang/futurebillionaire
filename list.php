<?php
// -------------------------------------
// Start session and require authentication
// -------------------------------------
session_start();
require_once '../includes/auth_check.php';

// Only Admin can view lists
requireRole(['Admin']);

require_once '../includes/db.php';


// -------------------------------------
// Get DB connection
// -------------------------------------
$pdo = getDB();


// -------------------------------------
// Get the type of list to display
// Example: list.php?type=students
// -------------------------------------
$type = $_GET['type'] ?? '';


// -------------------------------------
// Variables that change dynamically
// -------------------------------------
$title   = "";   // Page title
$columns = [];   // Table heading names
$query   = "";   // SQL query


// -------------------------------------
// Configure SQL + table columns based on type
// -------------------------------------
switch ($type) {

    // ----- STUDENT LIST -----
    case "students":
        $title = "Student List";
        $columns = ["Student ID", "Username", "Email"];
        $query = "
            SELECT s.StudentID AS col1,
                   u.Username AS col2,
                   u.Email AS col3
            FROM student s
            LEFT JOIN user u ON s.UserID = u.UserID
            ORDER BY s.StudentID DESC
        ";
        break;

    // ----- STAFF LIST -----
    case "staff":
        $title = "Staff List";
        $columns = ["Staff ID", "Username", "Email"];
        $query = "
            SELECT st.StaffID AS col1,
                   u.Username AS col2,
                   u.Email AS col3
            FROM staff st
            LEFT JOIN user u ON st.UserID = u.UserID
            ORDER BY st.StaffID DESC
        ";
        break;

    // ----- COURSE LIST -----
    case "courses":
        $title = "Course List";
        $columns = ["Course ID", "Course Name", "Course Code"];
        $query = "
            SELECT CourseID AS col1,
                   CourseName AS col2,
                   CourseCode AS col3
            FROM course
            ORDER BY CourseID DESC
        ";
        break;

    // ----- ENROLLMENT LIST -----
    case "enrollments":
        $title = "Enrollment List";
        $columns = ["Enrollment ID", "Student ID", "Course ID"];
        $query = "
            SELECT EnrollmentID AS col1,
                   StudentID AS col2,
                   CourseID AS col3
            FROM enrollment
            ORDER BY EnrollmentID DESC
        ";
        break;

    // If wrong type provided
    default:
        die("<h2 style='color:white; text-align:center;'>Invalid List Type</h2>");
}


// -------------------------------------
// Execute SQL query and fetch results
// -------------------------------------
$stmt = $pdo->query($query);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo $title; ?></title>

<style>
/* Premium dark-glass UI styling */
body {
  background: linear-gradient(135deg, #0f1c2e, #1b2f4a);
  font-family: Poppins, sans-serif;
  margin: 0;
  padding: 0;
  color: #fff;
}

/* Top Bar */
.header {
  padding: 20px 40px;
  background: rgba(255,255,255,0.07);
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255,255,255,0.15);
}

.header h1 {
  margin: 0;
}

/* Back Button */
.back-btn {
  padding: 8px 15px;
  border-radius: 8px;
  background: #3498db;
  text-decoration: none;
  color: white;
}

/* Page Container */
.container {
  max-width: 1100px;
  margin: 40px auto;
}

/* Glass box effect */
.glass-box {
  background: rgba(255,255,255,0.09);
  padding: 20px;
  border-radius: 14px;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255,255,255,0.2);
}

/* Table style */
table {
  width: 100%;
  border-collapse: collapse;
}

th {
  background: rgba(255,255,255,0.15);
  padding: 12px;
  text-align: left;
}

td {
  padding: 10px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}

tr:hover {
  background: rgba(255,255,255,0.07);
}
</style>
</head>

<body>

<!-- Top header -->
<div class="header">
  <h1><?php echo $title; ?></h1>
  <a class="back-btn" href="dashboard.php">← Back</a>
</div>

<div class="container">
  <div class="glass-box">

    <h2><?php echo $title; ?></h2>

    <!-- Table rendering -->
    <table>
      <thead>
        <tr>
        <?php foreach ($columns as $col): ?>
            <th><?php echo $col; ?></th>
        <?php endforeach; ?>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($data as $row): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['col1']); ?></td>
          <td><?php echo htmlspecialchars($row['col2']); ?></td>
          <td><?php echo htmlspecialchars($row['col3']); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>

    </table>

  </div>
</div>

</body>
</html>
