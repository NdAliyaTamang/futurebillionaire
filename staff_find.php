<?php
// File: staff_find.php
// Staff Search Functionality - Allows searching staff members by name, email, or department

// Include required files for authentication, staff model, and database connection
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/staff_model.php';
require_once __DIR__ . '/../includes/db.php';

// Initialize staff model for database operations
$staffModel = new StaffModel();
$searchResults = []; // Array to store search results
$searchTerm = ''; // Variable to store the current search term

// Handle search functionality for both POST and GET requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['search'])) {
    // Get search term from either POST or GET request with fallback to empty string
    $searchTerm = $_POST['search_term'] ?? $_GET['search'] ?? '';
    
    // Only perform search if search term is not empty
    if (!empty($searchTerm)) {
        $filters = ['search' => $searchTerm]; // Create filters array for search
        $searchResults = $staffModel->getStaff($filters); // Get search results from database
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
    <title>Search Staff - Future Billionaire Academy</title>
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
        
        /* Custom search box styling */
        .search-box {
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            padding: 15px 25px;
            color: white;
            font-size: 16px;
        }
        
        .search-box:focus {
            background: rgba(255, 255, 255, 0.2);
            border-color: #f1c40f;
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(241, 196, 15, 0.25);
        }
        
        /* Search button styling */
        .btn-search {
            background: linear-gradient(135deg, #f1c40f, #f39c12);
            border: none;
            color: #182848;
            font-weight: 600;
            padding: 15px 30px;
            border-radius: 50px;
        }
        
        /* Table styling for search results */
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
                            <a class="nav-link active" href="staff_find.php"><i class="fas fa-search"></i> Search Staff</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="staff_filter.php"><i class="fas fa-filter"></i> Filter Staff</a>
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
            <h1><i class="fas fa-search me-2"></i>Search Staff Members</h1>
            <a href="staff_list.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>

        <div class="glass-card">
            <!-- Search Form -->
            <form method="POST" class="mb-5">
                <div class="row g-3 align-items-center">
                    <div class="col-md-8">
                        <!-- Search input field with current search term preserved -->
                        <input type="text" name="search_term" class="form-control search-box" 
                               placeholder="Enter name, email, or department to search..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <!-- Search submit button -->
                        <button type="submit" class="btn btn-search w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </div>
            </form>

            <!-- Search Results Section -->
            <?php if (!empty($searchTerm)): ?>
                <!-- Results summary showing search term and result count -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Found <strong><?php echo count($searchResults); ?></strong> result(s) for "<?php echo htmlspecialchars($searchTerm); ?>"
                </div>

                <?php if (empty($searchResults)): ?>
                    <!-- No results message when search returns empty -->
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                        <h4>No results found</h4>
                        <p class="text-muted">Try different search terms or check the spelling.</p>
                    </div>
                <?php else: ?>
                    <!-- Results table when search returns data -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <?php if ($isAdmin): ?><th>Actions</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($searchResults as $staff): ?>
                                    <tr>
                                        <td><?= $staff['StaffID']; ?></td>
                                        <td><?= htmlspecialchars($staff['FirstName'] . ' ' . $staff['LastName']); ?></td>
                                        <td><?= htmlspecialchars($staff['Email']); ?></td>
                                        <td><?= htmlspecialchars($staff['Department']); ?></td>
                                        <td>
                                            <span class="status-badge <?= $staff['IsActive'] ? 'active' : 'inactive'; ?>">
                                                <?= $staff['IsActive'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <?php if ($isAdmin): ?>
                                            <!-- Admin action buttons for each staff member -->
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
                <!-- Initial state message before search is performed -->
                <div class="text-center py-5">
                    <i class="fas fa-search fa-4x mb-3 text-warning"></i>
                    <h3>Search Staff Directory</h3>
                    <p class="text-muted">Use the search box above to find staff members by name, email, or department.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Include Bootstrap JavaScript for interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
