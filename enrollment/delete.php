<?php
// delete.php – handle delete request

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_role(['admin', 'staff']);


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$enrollmentId = (int)($_POST['id'] ?? 0);
if ($enrollmentId <= 0) {
    set_flash('error', 'Invalid enrollment ID.');
    header('Location: index.php');
    exit;
}

// 1. Load the record to check business rules
$stmt = db()->prepare("
    SELECT FinalGrade
    FROM enrollment
    WHERE EnrollmentID = :id
");
$stmt->execute([':id' => $enrollmentId]);
$row = $stmt->fetch();

if (!$row) {
    set_flash('error', 'Enrollment not found.');
    header('Location: index.php');
    exit;
}

// 2. Business rule: do not delete if there is a final grade
if ($row['FinalGrade'] !== null) {
    set_flash('error', 'Cannot delete an enrollment that already has a final grade.');
    header('Location: index.php');
    exit;
}

// 3. If validations passed, perform the delete
$stmt = db()->prepare("DELETE FROM enrollment WHERE EnrollmentID = :id");
$stmt->execute([':id' => $enrollmentId]);

set_flash('success', 'Enrollment deleted successfully.');
header('Location: index.php');
exit;
