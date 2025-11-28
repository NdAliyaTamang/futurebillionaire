<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/auth.php';

// Only logged in users can submit the form
require_login();
require_role(['admin', 'staff']);


// Extract incoming form values and normalise them
$studentId = (int)($_POST['StudentID'] ?? 0);
$courseId  = (int)($_POST['CourseID']  ?? 0);
$date      = $_POST['EnrollmentDate'] ?? '';
$status    = $_POST['Status'] ?? 'registered';

// Keep a copy of the input so the form can be repopulated on error
$_SESSION['OLD'] = [
    'StudentID'      => $studentId,
    'CourseID'       => $courseId,
    'EnrollmentDate' => $date,
    'Status'         => $status,
];

// Run simple validation rules before hitting the database
$errors = [];

if ($studentId <= 0) {
    $errors[] = 'Please select a student.';
}

if ($courseId <= 0) {
    $errors[] = 'Please select a course.';
}

if ($date === '') {
    $errors[] = 'Please choose an enrollment date.';
}

$allowedStatuses = ['registered', 'in-progress', 'completed', 'failed', 'dropped'];
if (!in_array($status, $allowedStatuses, true)) {
    $errors[] = 'Invalid status value.';
}

// If we found validation errors, send the user back to the form
if ($errors) {
    foreach ($errors as $e) {
        set_flash('error', $e);
    }

    header('Location: add.php');
    exit;
}

// If validation passes, hand off to the script that does the INSERT
require_once __DIR__ . '/store.php';
