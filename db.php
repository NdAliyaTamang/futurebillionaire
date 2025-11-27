<?php
function getDB() {
    // Use static variable to maintain single database connection instance
    static $pdo = null;
    
    // Check if database connection doesn't exist yet
    if ($pdo === null) {
        // Define Data Source Name for MySQL connection
        $dsn = 'mysql:host=localhost;dbname=school_management;charset=utf8mb4';
        
        // Create new PDO instance with connection details and options
        $pdo = new PDO($dsn, 'root', '', [
            // Set error mode to throw exceptions on errors
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Set default fetch mode to return associative arrays
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    
    // Return the database connection instance
    return $pdo;
}
?>