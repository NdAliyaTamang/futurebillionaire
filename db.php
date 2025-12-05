<?php
/**
 * Database connection singleton function
 * Creates and returns a single PDO instance to avoid multiple connections
 * Implements the singleton pattern for efficient database connection management
 */
function getDB() {
    // Static variable to hold the single PDO instance across function calls
    static $pdo = null;
    
    // Create new PDO instance only if it doesn't exist yet
    if ($pdo === null) {
        // Database connection string (DSN) with host, database name, and charset
        $dsn = 'mysql:host=localhost;dbname=school_management;charset=utf8mb4';
        
        // Create PDO instance with connection details and options
        $pdo = new PDO($dsn, 'root', '', [  // Using root user with no password (adjust for production)
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC    // Return results as associative arrays by default
        ]);
    }
    
    // Return the singleton PDO instance
    return $pdo;
}
?>