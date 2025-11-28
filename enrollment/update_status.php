<?php
// update_status.php – handle "Edit Enrollment" form submission

// 1. Include helpers
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/auth.php';

// 2. Only admin/staff can update enrollments
require_login();
require_role(['admin', 'staff']);

// 3. Only accept POST requests (form submission)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 4. Read values from the form
//    IMPORTANT: names must match your edit.php form fields.
//    I’m assuming your edit form has a hidden input:
//    <input type="hidden" name="EnrollmentID" value="...">
$enrollmentId = (int)($_POST['EnrollmentID'] ?? 0);
$studentId    = (int)($_POST['StudentID'] ?? 0);
$courseId     = (int)($_POST['CourseID'] ?? 0);
$dateRaw      = trim($_POST['EnrollmentDate'] ?? '');
$status       = $_POST['Status'] ?? '';
$gradeRaw     = trim($_POST['Grade'] ?? '');

// 5. Collect validation errors
$errors = [];

// 5a. Enrollment ID must be a positive number
if ($enrollmentId <= 0) {
    $errors[] = 'Invalid enrollment ID.';
}

// 5b. Required fields
if ($studentId <= 0) {
    $errors[] = 'Please select a student.';
}
if ($courseId <= 0) {
    $errors[] = 'Please select a course.';
}
if ($dateRaw === '') {
    $errors[] = 'Please enter an enrollment date.';
}

// 5c. Validate date format and not in the future
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

// 5d. Allowed status values
$allowedStatuses = ['registered', 'in-progress', 'completed', 'dropped', 'failed'];
if (!in_array($status, $allowedStatuses, true)) {
    $errors[] = 'Invalid status value.';
}

// 5e. Grade validation (optional)
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

// 5f. Check that the enrollment actually exists
if ($enrollmentId > 0) {
    $stmt = db()->prepare("SELECT 1 FROM enrollment WHERE EnrollmentID = :eid");
    $stmt->execute([':eid' => $enrollmentId]);
    if (!$stmt->fetch()) {
        $errors[] = 'Enrollment record not found.';
    }
}

// 5g. Confirm student exists
if ($studentId > 0) {
    $stmt = db()->prepare("SELECT 1 FROM student WHERE StudentID = :sid");
    $stmt->execute([':sid' => $studentId]);
    if (!$stmt->fetch()) {
        $errors[] = 'Selected student does not exist.';
    }
}

// 5h. Confirm course exists
if ($courseId > 0) {
    $stmt = db()->prepare("SELECT 1 FROM course WHERE CourseID = :cid");
    $stmt->execute([':cid' => $courseId]);
    if (!$stmt->fetch()) {
        $errors[] = 'Selected course does not exist.';
    }
}

// 6. If any errors → build a clean multi-line flash message and go back
if (!empty($errors)) {

    /*
     * IMPORTANT:
     * We use "\n" for new lines here, NOT "<br>".
     * flash.php will convert "\n" into <br> tags when rendering.
     * This prevents "<br>" from showing as text and keeps the message in ONE box.
     */
    $msg = "Enrollment could not be updated:\n- " . implode("\n- ", $errors);

    set_flash('error', $msg);

    // Send user back to the edit form, keeping the same ID in the URL
    if ($enrollmentId > 0) {
        header('Location: edit.php?id=' . $enrollmentId);
    } else {
        header('Location: index.php');
    }
    exit;
}

// 7. No validation errors → update the record
$sql = "
    UPDATE enrollment
    SET StudentID = :sid,
        CourseID = :cid,
        EnrollmentDate = :date,
        Status = :status,
        FinalGrade = :grade
    WHERE EnrollmentID = :eid
";

$stmt = db()->prepare($sql);
$stmt->execute([
    ':sid'   => $studentId,
    ':cid'   => $courseId,
    ':date'  => $dateRaw,
    ':status'=> $status,
    ':grade' => $grade,
    ':eid'   => $enrollmentId,
]);

// 8. Success message + redirect to list
set_flash('success', 'Enrollment updated successfully.');
header('Location: index.php');
exit;
