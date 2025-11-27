<?php

// Sanitize plain text input
function cleanText($v): string {
    return trim(filter_var($v ?? '', FILTER_SANITIZE_STRING));
}

// Validate username: 4–20 chars, letters/numbers/underscore
function validateUsername(string $u): bool {
    return (bool)preg_match('/^[A-Za-z0-9_]{4,20}$/', $u);
}

// Validate email: must be a valid format and end with @school.edu
function validateEmail(string $e): bool {
    $e = trim($e);                      // removes spaces, tabs, newline
    $e = filter_var($e, FILTER_SANITIZE_EMAIL); // removes illegal chars

    if (!filter_var($e, FILTER_VALIDATE_EMAIL)) return false;

    return str_ends_with($e, '@school.edu');
}

// Validate password strength: 8+ chars, uppercase, lowercase, number
function validatePassword(string $p): bool {
    return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $p);
}

// Validate name: letters + spaces, 2–40 chars
function validateName(string $name): bool {
    return (bool)preg_match('/^[A-Za-z ]{2,40}$/', $name);
}

// Validate age: must be numeric between 5 and 100
function validateAge($age): bool {
    return is_numeric($age) && $age >= 5 && $age <= 100;
}

// Validate GPA: numeric 0.0–4.0 or empty
function validateGPA($gpa): bool {
    if ($gpa === '' || $gpa === null) return true;
    return is_numeric($gpa) && $gpa >= 0 && $gpa <= 4;
}

// Validate salary: numeric, >= 0 (or empty)
function validateSalary($salary): bool {
    if ($salary === '' || $salary === null) return true;
    return is_numeric($salary) && $salary >= 0;
}

// Validate department name: letters + spaces only
function validateDepartment(string $d): bool {
    if ($d === '') return true;
    return (bool)preg_match('/^[A-Za-z ]+$/', $d);
}

// Validate date of birth: must be a real date AND in the past
function validateDOB(string $dob): bool {
    if (!$dob) return false;
    $ts = strtotime($dob);
    return $ts !== false && $ts < time();
}

// Validate hire date: must be real date AND not in the future
function validateHireDate(string $date): bool {
    if (!$date) return false;
    $ts = strtotime($date);
    return $ts !== false && $ts <= time();
}

// Validate role selection
function validateRole(string $role): bool {
    return in_array($role, ['Admin', 'Staff', 'Student'], true);
}

// Validate account status (0 or 1)
function validateStatus($s): bool {
    return in_array($s, ['0', '1', 0, 1], true);
}

// Validate avatar selection: must be from predefined allowed list
function validateAvatar(string $url): bool {

    $allowedAvatars = [
        "https://cdn-icons-png.flaticon.com/512/2922/2922510.png",
        "https://cdn-icons-png.flaticon.com/512/2922/2922522.png",
        "https://cdn-icons-png.flaticon.com/512/2922/2922561.png",
        "https://cdn-icons-png.flaticon.com/512/2922/2922656.png",
        "https://cdn-icons-png.flaticon.com/512/2922/2922688.png",
        "https://cdn-icons-png.flaticon.com/512/2922/2922711.png"
    ];

    return in_array($url, $allowedAvatars, true);
}

// Validate profile image upload (extension + MIME + size)
function validateProfileImage(array $file): bool {

    if ($file['error'] !== 0) return false;

    // Max allowed size: 2MB
    if ($file['size'] > 2 * 1024 * 1024) return false;

    // Allowed extensions
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) return false;

    // Verify MIME type for security
    $mime = mime_content_type($file['tmp_name']);
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMime, true)) return false;

    return true;
}

// Validate course codes like: CSC101, IT202, MATH500
function validateCourseCode(string $code): bool {
    return (bool)preg_match('/^[A-Z]{3,5}[0-9]{3,5}$/', strtoupper($code));
}

// Validate credit hours (1–60)
function validateCredits($credits): bool {
    return is_numeric($credits) && $credits >= 1 && $credits <= 60;
}

// Validate course fee (>= 0 or empty)
function validateFee($fee): bool {
    if ($fee === '' || $fee === null) return true;
    return is_numeric($fee) && $fee >= 0;
}

// Validate final grade (0–100 or empty)
function validateFinalGrade($grade): bool {
    if ($grade === '' || $grade === null) return true;
    return is_numeric($grade) && $grade >= 0 && $grade <= 100;
}

// Validate public registration based on role type
function validatePublicRegister(array $data, string $role): array {

    $errors = [];

    // Common fields for all roles
    if (!validateUsername($data['username'])) $errors[] = "Invalid username.";
    if (!validateEmail($data['email']))       $errors[] = "Invalid school email.";
    if (!validatePassword($data['password'])) $errors[] = "Weak password.";
    if (!validateName($data['first_name']))   $errors[] = "Invalid first name.";
    if (!validateName($data['last_name']))    $errors[] = "Invalid last name.";

    // Student-specific rules
    if ($role === 'Student') {
        if (!validateDOB($data['dob']))        $errors[] = "Invalid date of birth.";
        if (!validateAge($data['age']))        $errors[] = "Invalid age.";
        if (!validateGPA($data['gpa']))        $errors[] = "Invalid GPA.";
    }

    // Staff-specific rules
    if ($role === 'Staff') {
        if (!validateDepartment($data['department'])) $errors[] = "Invalid department.";
        if (!validateSalary($data['salary']))         $errors[] = "Invalid salary.";
        if (!validateHireDate($data['hire_date']))    $errors[] = "Invalid hire date.";
    }

    return $errors;
}

//
// ====================================================
//       ADMIN PIN VALIDATION (ADDED AT BOTTOM)
// ====================================================
//

// Validate Admin PIN format: EXACT 6 digits
function validateAdminPIN(string $pin): bool {
    return (bool)preg_match('/^[0-9]{6}$/', $pin);
}

// Validate OLD PIN format only (DB verification happens in change_pin.php)
function validateOldAdminPIN(string $pin): bool {
    return (bool)preg_match('/^[0-9]{6}$/', $pin);
}

?>
