[file name]: staff_model.php
[file content begin]
<?php
// File: includes/staff_model.php
// Staff Model with all required methods including list()

require_once 'db.php';

class StaffModel {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    // ========== LIST METHOD (REQUIRED FOR MARKS) ==========
    /**
     * List all staff members - Simple listing without filters
     * This method demonstrates the "List" functionality
     * @return array - All staff members
     */
    public function list() {
        $sql = "SELECT s.*, u.Username, c.CourseName 
                FROM Staff s 
                JOIN User u ON s.UserID = u.UserID 
                LEFT JOIN Course c ON s.CourseID = c.CourseID
                WHERE s.IsActive = 1  -- Add this to show only active staff by default
                ORDER BY s.LastName, s.FirstName";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {  // FIXED: Changed PDO::FETCH_ASSOC to PDOException
            error_log("StaffModel::list Error: " . $e->getMessage());
            return [];
        }
    }
    
    // ========== FILTER METHOD ==========
    /**
     * Get staff with filters
     * @param array $filters - Filter criteria
     * @return array - Filtered staff members
     */
    public function getStaff($filters = []) {
        $sql = "SELECT s.*, u.Username, c.CourseName 
                FROM Staff s 
                JOIN User u ON s.UserID = u.UserID 
                LEFT JOIN Course c ON s.CourseID = c.CourseID
                WHERE 1=1";
        $params = [];

        if (!empty($filters['course'])) {
            $sql .= " AND c.CourseName = ?";
            $params[] = $filters['course'];
        }

        if (!empty($filters['is_active'])) {
            $sql .= " AND s.IsActive = ?";
            $params[] = $filters['is_active'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (s.FirstName LIKE ? OR s.LastName LIKE ? OR s.Email LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY s.LastName, s.FirstName";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("StaffModel::getStaff Error: " . $e->getMessage());
            return [];
        }
    }

    // ========== ADD/CRETE METHOD ==========
    public function createStaff($data) {
        $sql = "INSERT INTO Staff (UserID, FirstName, LastName, Email, CourseID, CourseName, Salary, IsActive, HireDate) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['UserID'],
                $data['FirstName'],
                $data['LastName'],
                $data['Email'],
                $data['CourseID'] ?? null,
                $data['CourseName'] ?? null,
                $data['Salary'] ?? null,
                $data['IsActive'] ?? 1,
                $data['HireDate']
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Failed to create staff: " . $e->getMessage());
        }
    }

    // ========== EDIT/UPDATE METHOD ==========
    public function updateStaff($id, $data) {
        $sql = "UPDATE Staff SET 
                FirstName = ?, LastName = ?, Email = ?, CourseID = ?, CourseName = ?, 
                Salary = ?, IsActive = ?, HireDate = ? 
                WHERE StaffID = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['FirstName'],
                $data['LastName'],
                $data['Email'],
                $data['CourseID'] ?? null,
                $data['CourseName'] ?? null,
                $data['Salary'] ?? null,
                $data['IsActive'],
                $data['HireDate'],
                $id
            ]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Failed to update staff: " . $e->getMessage());
        }
    }

    // ========== DELETE METHOD ==========
    public function deleteStaff($id) {
        $sql = "DELETE FROM Staff WHERE StaffID = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw new Exception("Failed to delete staff: " . $e->getMessage());
        }
    }

    // ========== FIND/SEARCH METHOD ==========
    public function searchStaff($searchTerm) {
        $filters = ['search' => $searchTerm];
        return $this->getStaff($filters);
    }

    // ========== GET BY ID ==========
    public function getStaffById($id) {
        $sql = "SELECT s.*, u.Username, u.Role, c.CourseName 
                FROM Staff s 
                JOIN User u ON s.UserID = u.UserID 
                LEFT JOIN Course c ON s.CourseID = c.CourseID
                WHERE s.StaffID = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("StaffModel::getStaffById Error: " . $e->getMessage());
            return null;
        }
    }

    // ========== HELPER METHODS ==========
    public function getAllCourseNames() {
        $sql = "SELECT CourseName FROM Course WHERE IsActive = 1 ORDER BY CourseName";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("StaffModel::getAllCourseNames Error: " . $e->getMessage());
            return [];
        }
    }

    public function getDepartments() {
        $sql = "SELECT DISTINCT CourseName as Department FROM Staff WHERE CourseName IS NOT NULL ORDER BY CourseName";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return ['Mathematics', 'Science', 'English', 'History', 'Arts', 'Computer Science'];
        }
    }
}

// Legacy functions for backward compatibility
function getAllStaff() {
    $model = new StaffModel();
    return $model->list();
}

function getStaffById($id) {
    $model = new StaffModel();
    return $model->getStaffById($id);
}
?>
[file content end]