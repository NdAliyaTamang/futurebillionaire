<?php
session_start(); // start session
require_once '../includes/auth_check.php'; // login check
require_once '../includes/auth_model.php'; // role check function
require_once '../includes/db.php'; // db connection

requireRole(['Admin','Staff','Student']); // allow only these roles

// auto logout after 10 minutes //
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 600) {
    session_unset();
    session_destroy();
    header("Location: login.php?expired=1");
    exit;
}
$_SESSION['last_activity'] = time(); // update last activity

// session variables //
$username = $_SESSION['username'];
$role     = $_SESSION['role'];
$userID   = $_SESSION['userID'];

$pdo = getDB(); // database connection

// default profile image //
$defaultAvatar = "https://cdn-icons-png.flaticon.com/512/847/847969.png";
$profileImg = $defaultAvatar;

// get admin profile image //
if ($role === 'Admin') {
    $stmt = $pdo->prepare("SELECT ProfileImage FROM user WHERE UserID=?");
    $stmt->execute([$userID]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($r['ProfileImage'])) {
        $profileImg = (strpos($r['ProfileImage'],'http')===0) ? $r['ProfileImage'] : "../uploads/profile/".$r['ProfileImage'];
    }
}

// get staff profile image //
elseif ($role === 'Staff') {
    $stmt = $pdo->prepare("SELECT ProfileImage FROM staff WHERE UserID=?");
    $stmt->execute([$userID]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($r['ProfileImage'])) {
        $profileImg = (strpos($r['ProfileImage'],'http')===0) ? $r['ProfileImage'] : "../uploads/profile/".$r['ProfileImage'];
    }
}

// get student profile image //
else {
    $stmt = $pdo->prepare("SELECT ProfileImage FROM student WHERE UserID=?");
    $stmt->execute([$userID]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($r['ProfileImage'])) {
        $profileImg = (strpos($r['ProfileImage'],'http')===0) ? $r['ProfileImage'] : "../uploads/profile/".$r['ProfileImage'];
    }
}

// admin card counts //
$pendingCount = ($role==='Admin') ? countPendingUsers() : 0;
$totalStudents = $totalStaff = $totalCourses = $totalEnrollment = 0;

// admin stats //
if ($role === 'Admin') {
    $totalStudents   = $pdo->query("SELECT COUNT(*) FROM student")->fetchColumn();
    $totalStaff      = $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();
    $totalCourses    = $pdo->query("SELECT COUNT(*) FROM course")->fetchColumn();
    $totalEnrollment = $pdo->query("SELECT COUNT(*) FROM enrollment")->fetchColumn();
}

// staff stats //
$myCourses = $myStudents = 0;

if ($role==='Staff') {
    $q = $pdo->prepare("SELECT StaffID FROM staff WHERE UserID=?");
    $q->execute([$userID]);
    $s = $q->fetch(PDO::FETCH_ASSOC);

    if ($s) {
        $staffID = $s['StaffID'];

        $c1 = $pdo->prepare("SELECT COUNT(*) FROM course WHERE StaffID=?");
        $c1->execute([$staffID]);
        $myCourses = $c1->fetchColumn();

        $c2 = $pdo->prepare("
            SELECT COUNT(DISTINCT e.StudentID)
            FROM enrollment e
            JOIN course c ON e.CourseID=c.CourseID
            WHERE c.StaffID=?
        ");
        $c2->execute([$staffID]);
        $myStudents = $c2->fetchColumn();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">

<!-- page title //-->
<title><?php echo ucfirst($role); ?> Dashboard</title>

<!-- external CSS file //-->
<link rel="stylesheet" href="../assets/css/auth-style.css">

<style>
/* page background and layout //*/
body {
  font-family:Poppins,sans-serif;
  background:linear-gradient(135deg,#141E30,#243B55);
  margin:0;
  color:white;
  min-height:100vh;
  display:flex;
  flex-direction:column;
}

/* header bar //*/
header {
  background:linear-gradient(90deg,#182848,#4b6cb7);
  padding:20px 40px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}

/* navigation links //*/
nav a {
  color:white;
  text-decoration:none;
  margin-left:20px;
}

/* logout button //*/
.logout-btn {
  background:#e74c3c;
  padding:8px 16px;
  border-radius:20px;
}

/* profile picture //*/
.profile-icon {
  width:42px;
  height:42px;
  border-radius:50%;
  object-fit:cover;
  border:2px solid white;
}

/* main dashboard area //*/
main {
  flex:1;
  padding:40px;
  text-align:center;
}

/* card container //*/
.dashboard-cards {
  display:flex;
  justify-content:center;
  flex-wrap:wrap;
  gap:25px;
  margin-bottom:30px;
}

/* card style //*/
.card {
  width:220px;
  padding:25px;
  border-radius:15px;
  cursor:pointer;
  color:white;
  box-shadow:0 8px 20px rgba(0,0,0,0.3);
}

/* card colors //*/
.blue {background:linear-gradient(135deg,#3498db,#2980b9);}
.green {background:linear-gradient(135deg,#2ecc71,#27ae60);}
.purple {background:linear-gradient(135deg,#9b59b6,#8e44ad);}
.red {background:linear-gradient(135deg,#e74c3c,#c0392b);}

/* admin controls buttons //*/
.btn-row a {
  background:#3498db;
  padding:10px 20px;
  border-radius:8px;
  color:white;
  margin:8px;
  display:inline-block;
  text-decoration:none;
}

/* footer //*/
footer {
  padding:15px;
  background:rgba(0,0,0,0.3);
  text-align:center;
}
</style>
</head>

<body>

<!-- header section //-->
<header>

  <!-- welcome message //-->
  <h1>Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo $role; ?>)</h1>

  <!-- navigation items //-->
  <nav>

    <!-- pending count for admin only //-->
    <?php if ($role==='Admin'): ?>
      <a href="approve_user.php">Pending <span class="badge-pending"><?= $pendingCount ?></span></a>
    <?php endif; ?>

    <!-- profile page link //-->
    <a href="my_profile.php">
      <img src="<?= $profileImg ?>" class="profile-icon">
    </a>

    <!-- logout link //-->
    <a class="logout-btn" href="logout.php">Logout</a>
  </nav>
</header>

<!-- main body section //-->
<main>

<?php if ($role==='Admin'): ?>

<!-- admin dashboard cards //-->
<div class="dashboard-cards">

  <!-- student count card //-->
  <div class="card blue" onclick="location.href='list.php?type=students'">
    <h3>Students</h3>
    <p><?= $totalStudents ?></p>
  </div>

  <!-- staff count card //-->
  <div class="card green" onclick="location.href='list.php?type=staff'">
    <h3>Staff</h3>
    <p><?= $totalStaff ?></p>
  </div>

  <!-- courses count card //-->
  <div class="card purple" onclick="location.href='list.php?type=courses'">
    <h3>Courses</h3>
    <p><?= $totalCourses ?></p>
  </div>

  <!-- enrollment count card //-->
  <div class="card red" onclick="location.href='list.php?type=enrollments'">
    <h3>Enrollments</h3>
    <p><?= $totalEnrollment ?></p>
  </div>

</div>

<!-- admin control buttons //-->
<h2>Admin Controls</h2>

<div class="btn-row">
  <a href="approve_user.php">Approve Users</a>
  <a href="manage_user.php">Manage Users</a>
  <a href="course_list.php">Manage Courses</a>
  <a href="staff_list.php">Manage Staff</a>
  <a href="manage_students.php">Manage Students</a>
  <a href="list_enrollment.php">Manage Enrollment</a>
</div>

<?php endif; ?>


<!-- staff dashboard section //-->
<?php if ($role==='Staff'): ?>

<h2>Staff Dashboard</h2>

<div class="dashboard-cards">
  <div class="card blue">
    <h3>My Courses</h3>
    <p><?= $myCourses ?></p>
  </div>
  <div class="card green">
    <h3>My Students</h3>
    <p><?= $myStudents ?></p>
  </div>
</div>

<div class="btn-row">
  <a href="viewmycourse.php">View My Courses</a>
  <a href="viewenrolledstudent.php">View Enrolled Students</a>
  <a href="my_profile.php">My Profile</a>
</div>

<?php endif; ?>


<!-- student dashboard section //-->
<?php if ($role==='Student'): ?>

<h2>Student Dashboard</h2>

<div class="btn-row">
  <a href="viewmycourse1.php">View My Courses</a>
  <a href="viewmygrades.php">View My Grades</a>
  <a href="my_profile.php">My Profile</a>
</div>

<?php endif; ?>

</main>

<!-- footer section //-->
<footer>
  © <?= date('Y') ?> FUTURE BILLIONAIRE PVT LTD
</footer>

</body>
</html>
