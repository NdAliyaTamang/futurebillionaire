<?php
/**
 * STAFF VALIDATION - SIMPLIFIED WORKING VERSION
 * Purpose: Provides validation functions for staff-related operations across the application
 * Note: This is a centralized validation library to maintain consistency and security
 */

// ================================================
// 1. BASIC VALIDATION FUNCTIONS
// ================================================

/**
 * Validate staff creation - USE IN staff_create.php
 * Purpose: Validates all required fields when creating a new staff member
 * Returns: Array of error messages (empty array if no errors)
 * Security: Validates input before database insertion to prevent bad data
 */
function validateStaffCreation() {
    $errors = [];
    
    // Only validate on POST requests (form submissions)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return $errors;
    
    // Get POST data with fallbacks for different field name conventions
    // Note: Supports both camelCase and lowercase field names for flexibility
    $firstname = $_POST['FirstName'] ?? $_POST['firstname'] ?? '';
    $lastname = $_POST['LastName'] ?? $_POST['lastname'] ?? '';
    $email = $_POST['Email'] ?? $_POST['email'] ?? '';
    $username = $_POST['Username'] ?? $_POST['username'] ?? '';
    $password = $_POST['Password'] ?? $_POST['password'] ?? '';
    $salary = $_POST['Salary'] ?? $_POST['salary'] ?? '';
    $hiredate = $_POST['HireDate'] ?? $_POST['hiredate'] ?? '';
    
    // Run validations - check for empty required fields
    if (empty(trim($firstname))) $errors[] = "First name is required";
    if (empty(trim($lastname))) $errors[] = "Last name is required";
    
    // Email validation with format checking
    if (empty(trim($email))) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format"; // Basic email format validation
    }
    
    // Username and password are required for account creation
    if (empty(trim($username))) $errors[] = "Username is required";
    if (empty(trim($password))) $errors[] = "Password is required";
    
    // Hire date is required for employment records
    if (empty(trim($hiredate))) $errors[] = "Hire date is required";
    
    // Salary validation (optional field)
    // Note: Only validates if salary is provided - allows empty salaries
    if (!empty($salary) && !is_numeric($salary)) {
        $errors[] = "Salary must be a number"; // Ensures numeric data type
    }
    
    return $errors;
}

/**
 * Validate staff edit - USE IN staff_edit.php
 * Purpose: Validates fields when editing an existing staff member
 * Returns: Array of error messages (empty array if no errors)
 * Note: Does not validate username/password as they may not be changed during edit
 */
function validateStaffEdit() {
    $errors = [];
    
    // Only validate on POST requests (form submissions)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return $errors;
    
    // Get POST data for edit form
    // Note: Uses consistent field names from the edit form
    $firstname = $_POST['FirstName'] ?? '';
    $lastname = $_POST['LastName'] ?? '';
    $email = $_POST['Email'] ?? '';
    $salary = $_POST['Salary'] ?? '';
    $hiredate = $_POST['HireDate'] ?? '';
    
    // Run validations - check for empty required fields
    if (empty(trim($firstname))) $errors[] = "First name is required";
    if (empty(trim($lastname))) $errors[] = "Last name is required";
    
    // Email validation with format checking
    if (empty(trim($email))) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format"; // Basic email format validation
    }
    
    // Hire date is required for employment records
    if (empty(trim($hiredate))) $errors[] = "Hire date is required";
    
    // Salary validation (optional field)
    // Note: Only validates if salary is provided - allows empty salaries
    if (!empty($salary) && !is_numeric($salary)) {
        $errors[] = "Salary must be a number"; // Ensures numeric data type
    }
    
    return $errors;
}

/**
 * Validate login - USE IN staff_login.php
 * Purpose: Validates login form inputs before authentication attempt
 * Returns: Array of error messages (empty array if no errors)
 * Security: Prevents empty credentials from being sent to authentication system
 */
function validateLogin() {
    $errors = [];
    
    // Only validate on POST requests (form submissions)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return $errors;
    
    // Get login form data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? ''; // Role selection (e.g., Admin, Staff)
    
    // Check all required fields are filled
    if (empty($username)) $errors[] = "Username is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($role)) $errors[] = "Role is required"; // Ensures role is selected
    
    return $errors;
}

/**
 * Quick search validation - for staff_list.php
 * Purpose: Validates search and filter inputs in staff listing page
 * Returns: Array with 'valid' boolean and 'errors' array
 * Security: Prevents overly long search terms and invalid status values
 */
function quickValidateSearch($search, $course, $status) {
    $errors = [];
    $valid = true;
    
    // Validate search term length to prevent overly long queries
    // Note: 100 character limit prevents performance issues and potential attacks
    if (strlen($search) > 100) {
        $errors['search'] = "Search term too long (max 100 chars)";
        $valid = false;
    }
    
    // Validate status filter values (only '0', '1', or empty allowed)
    // Prevents SQL injection through invalid status values
    if (!empty($status) && !in_array($status, ['0', '1'])) {
        $errors['is_active'] = "Invalid status value";
        $valid = false;
    }
    
    return ['valid' => $valid, 'errors' => $errors];
}

/**
 * Format validation errors - for staff_list.php
 * Purpose: Converts error array into HTML for display to user
 * Returns: HTML string containing formatted error messages
 * Note: Used specifically for displaying filter validation errors
 */
function formatValidationErrors($errorArray) {
    // Return empty string if no errors
    if (empty($errorArray) || empty($errorArray['errors'])) return '';
    
    // Build HTML list of errors
    $html = '<ul>';
    foreach ($errorArray['errors'] as $error) {
        $html .= '<li>' . htmlspecialchars($error) . '</li>'; // Escape output for security
    }
    $html .= '</ul>';
    return $html;
}

/**
 * Simple sanitization
 * Purpose: Basic input sanitization to prevent XSS attacks
 * Returns: Sanitized string with HTML special characters encoded
 * Note: This is basic sanitization - additional validation may be needed
 */
function sanitizeInputString($input) {
    // Trim whitespace and encode HTML special characters
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate filters - USE IN staff_list.php
 * Purpose: Validates GET parameters for staff list filtering
 * Returns: Array of error messages (empty array if no errors)
 * Security: Prevents malicious input in filter parameters
 */
function validateFilters() {
    $errors = [];
    
    // Get filter parameters from URL
    $search = $_GET['search'] ?? '';
    $status = $_GET['is_active'] ?? '';
    
    // Validate search length to prevent performance issues
    // Note: Limits search term to prevent overly complex queries
    if (strlen($search) > 100) {
        $errors[] = "Search term too long (max 100 chars)";
    }
    
    // Validate status parameter - only allow specific values
    // Security: Prevents SQL injection through status parameter
    if (!empty($status) && !in_array($status, ['0', '1'])) {
        $errors[] = "Invalid status value";
    }
    
    return $errors;
}

// ================================================
// 2. HELPER FUNCTION FOR DISPLAYING ERRORS
// ================================================

/**
 * Show errors in UI - Universal error display function
 * Purpose: Formats error array into Bootstrap alert for consistent UI display
 * Returns: HTML string with formatted error messages
 * Note: Uses Font Awesome icon for visual indication of errors
 */
function showErrors($errors) {
    // Return empty string if no errors
    if (empty($errors)) return '';
    
    // Build Bootstrap alert with error list
    $html = '<div class="alert alert-danger">';
    $html .= '<h5><i class="fas fa-exclamation-triangle me-2"></i>Please fix errors:</h5><ul>';
    foreach ($errors as $error) {
        $html .= '<li>' . htmlspecialchars($error) . '</li>'; // Escape output for security
    }
    $html .= '</ul></div>';
    return $html;
}
?>