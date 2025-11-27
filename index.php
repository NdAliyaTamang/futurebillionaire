<?php
session_start();
require_once "../includes/db.php";

if (isset($_SESSION['role'])) {
    header("Location: dashboard.php");
    exit;
}

$pdo = getDB();

// ✅ Fetch top 3 active courses dynamically
$query = "SELECT CourseName, Description, StartDate 
          FROM Course 
          WHERE IsActive = 1 
          ORDER BY StartDate ASC 
          LIMIT 3";
$stmt = $pdo->prepare($query);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome | Future Billionaire Academy</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }

    body {
      background: linear-gradient(135deg, rgba(24,40,72,0.95), rgba(75,108,183,0.95)), 
                  url("https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1600&q=80");
      background-size: cover;
      background-position: center;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      color: #fff;
      overflow-x: hidden;
    }

    .container {
      text-align: center;
      margin-top: 5%;
      animation: fadeIn 1.2s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }

    h1 { font-size: 40px; font-weight: 700; color: #f9f9f9; }
    h2 { font-size: 18px; font-weight: 400; margin-bottom: 30px; opacity: 0.9; }

    .features, .courses {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 25px;
      margin-bottom: 40px;
    }

    .feature-card, .course-card {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(10px);
      width: 260px;
      padding: 25px 20px;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.25);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .feature-card:hover, .course-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.4);
    }

    .feature-card img, .course-card img {
      width: 60px;
      height: 60px;
      margin-bottom: 15px;
      border-radius: 50%;
      object-fit: cover;
    }

    .feature-card h3, .course-card h3 {
      margin-bottom: 10px;
      font-size: 18px;
      color: #f1c40f;
    }

    .feature-card p, .course-card p {
      font-size: 14px;
      color: #f9f9f9;
      opacity: 0.9;
    }

    .buttons { margin-top: 30px; }

    .btn {
      display: inline-block;
      padding: 12px 30px;
      border-radius: 10px;
      text-decoration: none;
      color: #fff;
      font-weight: 600;
      margin: 10px;
      font-size: 16px;
      transition: 0.3s;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    .btn-login { background: linear-gradient(135deg, #00c6ff, #0072ff); }
    .btn-register { background: linear-gradient(135deg, #2ecc71, #27ae60); }
    .btn:hover { transform: translateY(-3px); opacity: 0.9; }

    .section-title {
      text-align: center;
      margin: 40px 0 15px;
      font-size: 24px;
      color: #f1c40f;
      letter-spacing: 1px;
    }

    footer {
      text-align: center;
      padding: 15px 10px;
      font-size: 14px;
      background: rgba(0,0,0,0.4);
      border-top: 1px solid rgba(255,255,255,0.1);
    }
    footer span { color: #f1c40f; font-weight: 600; }

    @media (max-width: 768px) {
      h1 { font-size: 28px; }
      .feature-card, .course-card { width: 90%; }
      .features, .courses { gap: 15px; }
    }
  </style>
</head>
<body>

  <div class="container">
    <h1>Welcome to Future Billionaire Academy</h1>
    <h2>Empowering students, connecting staff, and inspiring success worldwide 🌍</h2>

    <!-- Features -->
    <div class="features">
      <div class="feature-card">
        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135810.png" alt="Student Portal">
        <h3>Student Portal</h3>
        <p>Access grades, courses, and personalized dashboards — all in one place.</p>
      </div>
      <div class="feature-card">
        <img src="https://cdn-icons-png.flaticon.com/512/2942/2942073.png" alt="Staff Tools">
        <h3>Staff Tools</h3>
        <p>Manage courses, monitor students, and keep academic progress on track.</p>
      </div>
      <div class="feature-card">
        <img src="https://cdn-icons-png.flaticon.com/512/1828/1828817.png" alt="Admin Control">
        <h3>Admin Control</h3>
        <p>Approve new users, manage departments, and maintain a secure system.</p>
      </div>
    </div>

    <!-- ✅ Updated Course Links -->
    <h2 class="section-title"> Popular Courses</h2>
    <div class="courses">
      <?php if (!empty($courses)): ?>
        <?php foreach ($courses as $course): ?>
          <a href="course_view.php?id=<?php echo urlencode($course['CourseName']); ?>" class="course-card" style="text-decoration:none; color:inherit;">
            <img src="https://source.unsplash.com/500x500/?education,<?php echo urlencode($course['CourseName']); ?>" alt="Course">
            <h3><?php echo htmlspecialchars($course['CourseName']); ?></h3>
            <p><?php echo htmlspecialchars(substr($course['Description'], 0, 80)); ?>...</p>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No active courses found. Please check back later.</p>
      <?php endif; ?>
    </div>

    <!-- ✅ Updated buttons -->
    <div class="buttons">
      <a href="login.php" class="btn btn-login">Login</a>
      <a href="register.php" class="btn btn-register"> Register</a>
    </div>
  </div>

  <footer>© <?php echo date('Y'); ?> <span>FUTURE BILLIONAIRE PVT LTD</span> | Dream. Learn. Achieve.</footer>
</body>
</html>
