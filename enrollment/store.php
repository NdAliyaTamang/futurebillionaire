<?php
// store.php – handle "Add Enrollment" form submission

// 1. Include database, flash messages and auth helper
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/auth.php';

// 2. Only logged-in users can add enrollments
require_login();
require_role(['admin', 'staff']);


// 3. Make sure this script is only called via POST (form submit)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); // prevent direct access
    exit;
}

// 4. Read values from the form
//    IMPORTANT: names must match add.php fields
$studentId = (int)($_POST['StudentID'] ?? 0);
$courseId  = (int)($_POST['CourseID'] ?? 0);
$dateRaw   = trim($_POST['EnrollmentDate'] ?? '');
$status    = $_POST['Status'] ?? '';
$gradeRaw  = trim($_POST['Grade'] ?? '');


// 5. Prepare an array to collect validation error messages
$errors = [];

// 6. Required field checks
if ($studentId <= 0) {
    $errors[] = 'Please select a student.';
}
if ($courseId <= 0) {
    $errors[] = 'Please select a course.';
}
if ($dateRaw === '') {
    $errors[] = 'Please enter an enrollment date.';
}

// 7. Validate date format + future date rule
if ($dateRaw !== '') {
    $dt = DateTime::createFromFormat('Y-m-d', $dateRaw);
    $dateErrors = DateTime::getLastErrors();

    if (!$dt || $dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0) {
        $errors[] = 'Please enter a valid date (YYYY-MM-DD).';
    } else {
        $today = new DateTime('today');
        if ($dt > $today) {
            $errors[] = 'Enrollment date cannot be in the future.';
        }
    }
}

// 8. Allowed status values
$allowedStatuses = ['registered', 'in-progress', 'completed', 'dropped', 'failed'];
if (!in_array($status, $allowedStatuses, true)) {
    $errors[] = 'Invalid status value.';
}

// 9. Grade validation (optional field)
$grade = null;
if ($gradeRaw !== '') {
    if (!is_numeric($gradeRaw)) {
        $errors[] = 'Grade must be a number.';
    } else {
        $grade = (float)$gradeRaw;
        if ($grade < 0 || $grade > 100) {
            $errors[] = 'Grade must be between 0 and 100.';
        }
    }
}

// 10. Confirm student exists
if ($studentId > 0) {
    $stmt = db()->prepare("SELECT 1 FROM student WHERE StudentID = :sid");
    $stmt->execute([':sid' => $studentId]);
    if (!$stmt->fetch()) {
        $errors[] = 'Selected student does not exist.';
    }
}

// 11. Confirm course exists
if ($courseId > 0) {
    $stmt = db()->prepare("SELECT 1 FROM course WHERE CourseID = :cid");
    $stmt->execute([':cid' => $courseId]);
    if (!$stmt->fetch()) {
        $errors[] = 'Selected course does not exist.';
    }
}

// 12. Business rule: prevent duplicate enrollment
if ($studentId > 0 && $courseId > 0) {
    $stmt = db()->prepare("
        SELECT 1 
        FROM enrollment 
        WHERE StudentID = :sid AND CourseID = :cid
    ");
    $stmt->execute([':sid' => $studentId, ':cid' => $courseId]);

    if ($stmt->fetch()) {
        $errors[] = 'This student is already enrolled in this course.';
    }
}


// 13. If any validation errors → show clean flash message
if (!empty($errors)) {

    /*
     * FIXED ERROR MESSAGE FORMAT:
     * ---------------------------
     * We use "\n" (new line) instead of "<br>".
     * flash.php will convert new lines to <br> automatically.
     * This avoids seeing "<br>" printed on the screen.
     */
    
    $msg = "Enrollment could not be created:\n- " . implode("\n- ", $errors);

    set_flash('error', $msg);
    header('Location: add.php');
    exit;
}


// 14. Insert if everything is valid
$sql = "
  INSERT INTO enrollment (StudentID, CourseID, EnrollmentDate, Status, FinalGrade)
  VALUES (:sid, :cid, :date, :status, :grade)
";

$stmt = db()->prepare($sql);
$stmt->execute([
    ':sid'   => $studentId,
    ':cid'   => $courseId,
    ':date'  => $dateRaw,
    ':status'=> $status,
    ':grade' => $grade,
]);

// 15. Show success flash and go back to list
set_flash('success', 'Enrollment created successfully.');
header('Location: index.php');
exit;

?>
