<?php
// Load DB connection & audit logger
require_once 'db.php';
require_once 'audit.php';

// Shared PDO instance
$pdo = getDB();

/*
   GET USERS WITHOUT PAGINATION
 */
function getUsers($search = "", $role = "", $sort = "UserID", $order = "ASC") {
    global $pdo;

    // Define allowed sort columns to prevent SQL injection
    $allowedSort = ["UserID", "Username", "Role", "CreatedDate", "Email"];
    if (!in_array($sort, $allowedSort)) $sort = "UserID";
    // Validate order direction, default to ASC if invalid
    $order = strtoupper($order) === "DESC" ? "DESC" : "ASC";

    // Base SQL query with WHERE 1 for easy condition appending
    $sql = "SELECT * FROM user WHERE 1 ";
    $params = [];

    // Add search filter if provided
    if ($search !== "") {
        $sql .= " AND Username LIKE ? ";
        $params[] = "%$search%";
    }

    // Add role filter if provided
    if ($role !== "") {
        $sql .= " AND Role = ? ";
        $params[] = $role;
    }

    // Append sorting to the query
    $sql .= " ORDER BY $sort $order";

    // Execute prepared statement and return all results
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
   COUNT USERS BASED ON FILTERS
 */
function countAllUsers($search = "", $role = "") {
    $pdo = getDB();

    // Base count query with WHERE 1 for easy condition appending
    $sql = "SELECT COUNT(*) FROM user WHERE 1 ";
    $params = [];

    // Add search filter if provided
    if ($search !== "") {
        $sql .= " AND Username LIKE ? ";
        $params[] = "%$search%";
    }

    // Add role filter if provided
    if ($role !== "") {
        $sql .= " AND Role = ? ";
        $params[] = $role;
    }

    // Execute count query and return integer result
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/* 
   GET USERS WITH PAGINATION (FULLY FIXED)
 */
function getUsersPaginated($search, $role, $sort, $order, $page, $perPage) {
    $pdo = getDB();

    // Validate sort column against allowed values
    $allowedSort = ["UserID", "Username", "Email", "Role", "CreatedDate"];
    if (!in_array($sort, $allowedSort)) $sort = "UserID";

    // Validate order direction
    $order = strtoupper($order) === "DESC" ? "DESC" : "ASC";

    // Ensure page and perPage are positive integers
    $page    = max(1, (int)$page);
    $perPage = max(1, (int)$perPage);
    $offset  = ($page - 1) * $perPage;

    // Build WHERE filters
    $where  = " WHERE 1 ";
    $params = [];

    // Add search filter if provided
    if ($search !== "") {
        $where .= " AND Username LIKE ? ";
        $params[] = "%$search%";
    }

    // Add role filter if provided
    if ($role !== "") {
        $where .= " AND Role = ? ";
        $params[] = $role;
    }

    // ----- Count total -----
    // Get total number of records matching filters
    $sqlCount = "SELECT COUNT(*) FROM user $where";
    $stm = $pdo->prepare($sqlCount);
    $stm->execute($params);
    $total = (int)$stm->fetchColumn();

    // ----- Get paginated users -----
    // Build main query with pagination
    $sql = "
        SELECT *
        FROM user
        $where
        ORDER BY $sort $order
        LIMIT ? OFFSET ?
    ";

    $stm = $pdo->prepare($sql);

    // Bind search/role filters
    $pos = 1;
    foreach ($params as $p) {
        $stm->bindValue($pos++, $p);
    }

    // Bind pagination parameters as integers
    $stm->bindValue($pos++, (int)$perPage, PDO::PARAM_INT);
    $stm->bindValue($pos++, (int)$offset, PDO::PARAM_INT);

    $stm->execute();
    $users = $stm->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total pages for pagination UI
    $totalPages = max(1, ceil($total / $perPage));

    // Return comprehensive pagination result
    return [
        "users"      => $users,
        "total"      => $total,
        "page"       => $page,
        "perPage"    => $perPage,
        "totalPages" => $totalPages
    ];
}

/*    GET SINGLE USER BY ID
 */
function getUserById($id) {
    global $pdo;
    // Fetch single user by ID
    $stmt = $pdo->prepare("SELECT * FROM user WHERE UserID = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/*
   CREATE USER (ADMIN / STAFF / STUDENT)
 */
function createUser($username, $passwordPlain, $role, $extra = []) {
    $pdo = getDB();

    try {
        // Check duplicate username
        $check = $pdo->prepare("SELECT COUNT(*) FROM user WHERE Username = ?");
        $check->execute([$username]);
        if ($check->fetchColumn() > 0) return "exists";

        // Start transaction for atomic operations
        $pdo->beginTransaction();

        // Auto email if not provided
        $email = $extra['email'] ?? ($username . "@school.edu");

        // Insert user into main user table
        $stmt = $pdo->prepare("
            INSERT INTO user (Username, PasswordHash, Role, Email, IsActive)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $username,
            password_hash($passwordPlain, PASSWORD_DEFAULT),
            $role,
            $email
        ]);

        // Get the newly created user ID
        $userId = $pdo->lastInsertId();

        // Create staff record if role is Staff
        if ($role === "Staff") {
            $pdo->prepare("
                INSERT INTO staff (UserID, FirstName, LastName, Email, Department, Salary, HireDate, IsActive)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ")->execute([
                $userId,
                $extra['first_name'] ?? ucfirst($username),
                $extra['last_name'] ?? "Staff",
                $email,
                $extra['department'] ?? null,
                $extra['salary'] ?? null,
                $extra['hire_date'] ?? date("Y-m-d")
            ]);
        }

        // Create student record if role is Student
        if ($role === "Student") {
            $pdo->prepare("
                INSERT INTO student (UserID, FirstName, LastName, DateOfBirth, Email, Age, GPA, IsActive)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ")->execute([
                $userId,
                $extra['first_name'] ?? ucfirst($username),
                $extra['last_name'] ?? "Student",
                $extra['dob'] ?? date("Y-m-d"),
                $email,
                $extra['age'] ?? null,
                $extra['gpa'] ?? null
            ]);
        }

        // Commit transaction if all operations succeed
        $pdo->commit();
        // Log the user creation action
        logAction(null, "Created User", "User", $userId, "Username: $username Role: $role");

        return $userId;

    } catch (PDOException $e) {
        // Rollback transaction on any error
        $pdo->rollBack();
        return false;
    }
}

/* 
   UPDATE USER BASIC FIELDS
 */
function updateUser($id, $username, $password, $role, $isActive) {
    global $pdo;

    // Check if password is being updated
    if ($password !== "") {
        // Update with password change
        $stmt = $pdo->prepare("
            UPDATE user
            SET Username=?, PasswordHash=?, Role=?, IsActive=?
            WHERE UserID=?
        ");
        return $stmt->execute([
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $role,
            $isActive,
            $id
        ]);
    } else {
        // Update without changing password
        $stmt = $pdo->prepare("
            UPDATE user
            SET Username=?, Role=?, IsActive=?
            WHERE UserID=?
        ");
        return $stmt->execute([
            $username,
            $role,
            $isActive,
            $id
        ]);
    }
}

/* 
   DELETE USER
 */
function deleteUser($id) {
    global $pdo;
    // Delete user by ID
    $stmt = $pdo->prepare("DELETE FROM user WHERE UserID = ?");
    return $stmt->execute([$id]);
}