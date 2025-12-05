<?php
// File: staff_filter.php
// Staff Filtering Functionality - Allows filtering and sorting of staff members by department, status, and other criteria

// Include required files for authentication, staff model, and database connection
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/staff_model.php';
require_once __DIR__ . '/../includes/db.php';

// Initialize staff model for database operations
$staffModel = new StaffModel();
$filteredResults = []; // Array to store filtered staff results
$activeFilters = []; // Array to track currently active filters for display

// Get all departments from database for dropdown options
$departments = $staffModel->getDepartments();

// Handle filter form submission when POST request is made
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve filter values from form submission with default empty values
    $department = $_POST['department'] ?? '';
    $status = $_POST['status'] ?? '';
    $sort = $_POST['sort'] ?? '';
    
    // Build filters array for database query
    $filters = [];
    if (!empty($department)) {
        $filters['department'] = $department;
        $activeFilters['Department'] = $department; // Track active filter for UI display
    }
    if (!empty($status)) {
        $filters['is_active'] = $status;
        // Convert status code to human-readable text for display
        $activeFilters['Status'] = $status == 1 ? 'Active' : 'Inactive';
    }
    
    // Get filtered staff results from database based on applied filters
    $filteredResults = $staffModel->getStaff($filters);
    
    // Apply client-side sorting if sort option is selected
    if (!empty($sort)) {
        usort($filteredResults, function($a, $b) use ($sort) {
            switch ($sort) {
                case 'name_asc': return strcmp($a['LastName'], $b['LastName']); // Sort by last name A-Z
                case 'name_desc': return strcmp($b['LastName'], $a['LastName']); // Sort by last name Z-A
                case 'department': return strcmp($a['Department'], $b['Department']); // Sort by department name
                case 'hire_date': return strtotime($a['HireDate']) - strtotime($b['HireDate']); // Sort by hire date (oldest first)
                default: return 0; // No sorting
            }
        });
        // Convert sort parameter to human-readable text for display
        $activeFilters['Sort By'] = ucfirst(str_replace('_', ' ', $sort));
    }
}

// Check if current user has admin privileges for role-based UI elements
$isAdmin = ($_SESSION['role'] === 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter Staff - Future Billionaire Academy</title>
    <!-- Include Bootstrap CSS for responsive styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Glass morphism effect for modern UI */
        body {
            background: linear-gradient(135deg, #182848, #4b6cb7);
            min-height: 100vh;
            padding: 20px;
            font-family: "Poppins", sans-serif;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        h1 {
            color: #f1c40f;
            font-weight: 600;
        }
        
        .filter-card {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        /* Custom form styling for dark theme */
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #f1c40f;
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(241, 196, 15, 0.25);
        }
        
        .btn-filter {
            background: linear-gradient(135deg, #f1c40f, #f39c12);
            border: none;
            color: #182848;
            font-weight: 600;
            padding: 10px 25px;
        }
        
        /* Active filters display styling */
        .active-filters {
            background: rgba(241, 196, 15, 0.2);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .filter-badge {
            background: rgba(52, 152, 219, 0.3);
            padding: 5px 10px;
            border-radius: 20px;
            margin: 0 5px 5px 0;
            display: inline-block;
        }
        
        /* Table styling for results display */
        .table {
            background: rgba(52, 75, 120, 0.8);
            color: #fff;
            border-radius: 10px;
            overflow: hidden;
        }
        
        /* Status badge colors for active/inactive staff */
        .status-badge.active {
            background: #2ecc71;
            padding: 5px 12px;
            border-radius: 8px;
        }
        
        .status-badge.inactive {
            background: #e74c3c;
            padding: 5px 12px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation Header with user info -->
        <div class="user-info d-flex justify-content-between align-items-center">
            <div>
                <strong>Future Billionaire Academy</strong> | Staff Portal
            </div>
            <div>
                Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> 
                (<?php echo htmlspecialchars($_SESSION['role']); ?>)
            </div>
        </div>

        <!-- Main Navigation Menu -->
        <nav class="navbar navbar-expand-lg navbar-dark mb-4">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="staff_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="staff_list.php"><i class="fas fa-list"></i> Staff List</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="staff_find.php"><i class="fas fa-search"></i> Search Staff</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="staff_filter.php"><i class="fas fa-filter"></i> Filter Staff</a>
                        </li>
                        <?php if ($isAdmin): ?>
                        <!-- Admin-only navigation items -->
                        <li class="nav-item">
                            <a class="nav-link" href="staff_pending_approvals.php"><i class="fas fa-user-clock"></i> Pending Approvals</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="staff_create.php"><i class="fas fa-user-plus"></i> Add Staff</a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="staff_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Page Header with title and back button -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-filter me-2"></i>Filter Staff Members</h1>
            <a href="staff_list.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>

        <div class="glass-card">
            <!-- Filter Form -->
            <form method="POST" id="filterForm">
                <div class="filter-card">
                    <h4 class="mb-3 text-warning"><i class="fas fa-sliders-h me-2"></i>Filter Options</h4>
                    
                    <div class="row g-3">
                        <!-- Department filter dropdown -->
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <select name="department" class="form-select">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept; ?>" <?= ($_POST['department'] ?? '') === $dept ? 'selected' : ''; ?>>
                                        <?= $dept; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Status filter dropdown -->
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="1" <?= ($_POST['status'] ?? '') === '1' ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?= ($_POST['status'] ?? '') === '0' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <!-- Sort options dropdown -->
                        <div class="col-md-4">
                            <label class="form-label">Sort By</label>
                            <select name="sort" class="form-select">
                                <option value="">Default Order</option>
                                <option value="name_asc" <?= ($_POST['sort'] ?? '') === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                                <option value="name_desc" <?= ($_POST['sort'] ?? '') === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                                <option value="department" <?= ($_POST['sort'] ?? '') === 'department' ? 'selected' : ''; ?>>Department</option>
                                <option value="hire_date" <?= ($_POST['sort'] ?? '') === 'hire_date' ? 'selected' : ''; ?>>Hire Date</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Form action buttons -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-filter w-100">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button type="button" id="resetFilters" class="btn btn-outline-light w-100">
                                <i class="fas fa-redo me-2"></i>Reset Filters
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Active Filters Display - Shows currently applied filters -->
            <?php if (!empty($activeFilters)): ?>
                <div class="active-filters">
                    <h5><i class="fas fa-tags me-2"></i>Active Filters:</h5>
                    <?php foreach ($activeFilters as $filterName => $filterValue): ?>
                        <span class="filter-badge">
                            <strong><?= $filterName; ?>:</strong> <?= $filterValue; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Filter Results Section -->
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <!-- Results summary -->
                <div class="alert alert-info mb-3">
                    <i class="fas fa-chart-bar me-2"></i>
                    Found <strong><?php echo count($filteredResults); ?></strong> staff member(s) matching your criteria
                </div>

                <?php if (empty($filteredResults)): ?>
                    <!-- No results message -->
                    <div class="text-center py-5">
                        <i class="fas fa-filter fa-3x mb-3 text-muted"></i>
                        <h4>No staff members found</h4>
                        <p class="text-muted">Try adjusting your filter criteria.</p>
                    </div>
                <?php else: ?>
                    <!-- Results table -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Hire Date</th>
                                    <th>Status</th>
                                    <?php if ($isAdmin): ?><th>Actions</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filteredResults as $staff): ?>
                                    <tr>
                                        <td><?= $staff['StaffID']; ?></td>
                                        <td><?= htmlspecialchars($staff['FirstName'] . ' ' . $staff['LastName']); ?></td>
                                        <td><?= htmlspecialchars($staff['Email']); ?></td>
                                        <td><?= htmlspecialchars($staff['Department']); ?></td>
                                        <td><?= date('M d, Y', strtotime($staff['HireDate'])); ?></td>
                                        <td>
                                            <span class="status-badge <?= $staff['IsActive'] ? 'active' : 'inactive'; ?>">
                                                <?= $staff['IsActive'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <?php if ($isAdmin): ?>
                                            <!-- Admin action buttons -->
                                            <td>
                                                <a href="staff_edit.php?id=<?= $staff['StaffID']; ?>" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="staff_delete.php?id=<?= $staff['StaffID']; ?>" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Initial state message before filters are applied -->
                <div class="text-center py-5">
                    <i class="fas fa-filter fa-4x mb-3 text-warning"></i>
                    <h3>Filter Staff Directory</h3>
                    <p class="text-muted">Use the filter options above to find specific groups of staff members.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Include Bootstrap JavaScript for interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript to handle reset filters button functionality
        document.getElementById('resetFilters').addEventListener('click', function() {
            document.getElementById('filterForm').reset(); // Clear all form fields
        });
    </script>
</body>
</html>