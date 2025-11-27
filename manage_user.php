<?php
require_once '../includes/validation.php';

// Start session and protect page for Admin only
session_start();
require_once '../includes/auth_check.php';
require_once '../includes/user_model.php';
requireRole(['Admin']); // Only Admin can access user directory

//  Pagination setup  //
$perPage = 10;                                       // Users per page
$page    = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page from URL
if ($page < 1) $page = 1;                            // Safety check

//  Filters and sorting  //
$search = $_GET['search'] ?? "";                     // Username search
$role   = $_GET['role']   ?? "";                     // Role filter
$sort   = $_GET['sort']   ?? "UserID";               // Sort column
$order  = $_GET['order']  ?? "ASC";                  // Sort order

//  Fetch paginated data  //
// This returns users + total + totalPages etc.
$pagination = getUsersPaginated($search, $role, $sort, $order, $page, $perPage);

// Extract values from pagination array
$users      = $pagination['users'];                  // List of users for this page
$totalUsers = $pagination['total'];                  // Total users matching filters
$totalPages = $pagination['totalPages'];             // Total pages available

//  Toggle order for clickable headers //
$toggleOrder = ($order === "ASC") ? "DESC" : "ASC";  // Flip order for header links
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Directory</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* Outer gradient background */
body {
  background: linear-gradient(135deg, #212e68ff 0%, #9e69d3ff 100%);
  background-attachment: fixed;
  padding: 40px;
  min-height: 100vh;
  font-family: "Poppins", sans-serif;
}

/* Inner glass card container */
.container-box {
  background: linear-gradient(135deg, #6c8fd6ff, #4b6cb7);
  border-radius: 15px;
  padding: 30px;
  color: white;
  box-shadow: 0 10px 40px rgba(0,0,0,0.4);
}

/* Heading style */
h1 {
  color: #f1c40f;
  font-weight: 600;
  margin: 0;
}

/* Table base style */
.table {
  background: rgba(52,75,120,0.8);
  color: #fff;
  border-radius: 10px;
  overflow: hidden;
}
.table thead {
  background: rgba(0,0,0,0.35);
}
.table-bordered > :not(caption) > * {
  border-color: rgba(255,255,255,0.25);
}

/* Status badges */
.badge-success {
  background: #2ecc71;
}
.badge-secondary {
  background: #e74c3c;
}

/* Top-right buttons */
.btn-add {
  background: #2ecc71;
  border: none;
}
.btn-add:hover {
  background: #27ae60;
}
.btn-back {
  border: 2px solid #f1c40f;
  color: #f1c40f;
}
.btn-back:hover {
  background: #f1c40f;
  color: #000;
}

/* Pagination buttons */
.pagination .page-link {
  background: rgba(255,255,255,0.2);
  color: white;
  border: none;
}
.pagination .page-link:hover {
  background: rgba(255,255,255,0.4);
}

/* Small controls for search/filter */
.filter-input {
  max-width: 180px;
}
</style>
</head>
<body>

<div class="container-box">

  <!-- Top heading + action buttons -->
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>USER DIRECTORY</h1>

      <div>
        <a href="add_user.php" class="btn btn-add me-2">+ Add User</a>
        <a href="dashboard.php" class="btn btn-back">← Back to Dashboard</a>
      </div>
  </div>

  <!-- Filter row (search, role, order) -->
  <form method="GET" class="row g-3 mb-4">
      <!-- Search by username -->
      <div class="col-md-3">
        <input type="text"
               class="form-control filter-input"
               name="search"
               placeholder="Search username..."
               value="<?= htmlspecialchars($search) ?>">
      </div>

      <!-- Filter by role -->
      <div class="col-md-3">
        <select name="role" class="form-select filter-input">
            <option value="">All Roles</option>
            <option value="Admin"   <?= $role=="Admin"   ? "selected" : "" ?>>Admin</option>
            <option value="Staff"   <?= $role=="Staff"   ? "selected" : "" ?>>Staff</option>
            <option value="Student" <?= $role=="Student" ? "selected" : "" ?>>Student</option>
        </select>
      </div>

      <!-- Sort order (ASC / DESC) -->
      <div class="col-md-2">
         <select name="order" class="form-select filter-input">
            <option value="ASC"  <?= $order=="ASC"  ? "selected" : "" ?>>Ascending</option>
            <option value="DESC" <?= $order=="DESC" ? "selected" : "" ?>>Descending</option>
         </select>
      </div>

      <!-- Apply / Clear buttons -->
      <div class="col-md-4">
        <button class="btn btn-primary">Apply</button>
        <a href="manage_user.php" class="btn btn-secondary">Clear</a>
      </div>
  </form>

  <!-- User table -->
  <div class="table-responsive">
    <table class="table table-bordered text-center align-middle">
        <thead>
            <tr>
                <!-- Sortable ID column -->
                <th>
                  <a href="?sort=UserID&order=<?= $toggleOrder ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>">
                    ID
                  </a>
                </th>

                <!-- Sortable Username column -->
                <th>
                  <a href="?sort=Username&order=<?= $toggleOrder ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>">
                    Username
                  </a>
                </th>

                <!-- Email (not sorted) -->
                <th>Email</th>

                <!-- Sortable Role column -->
                <th>
                  <a href="?sort=Role&order=<?= $toggleOrder ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>">
                    Role
                  </a>
                </th>

                <th>Login Count</th>
                <th>Last Login</th>
                <th>Status</th>
                <th width="180">Actions</th>
            </tr>
        </thead>
        <tbody>

        <?php if (empty($users)): ?>
            <!-- No users row -->
            <tr>
              <td colspan="8">No users found</td>
            </tr>

        <?php else: ?>
            <!-- Loop users -->
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int)$u['UserID'] ?></td>
                <td><?= htmlspecialchars($u['Username']) ?></td>
                <td><?= htmlspecialchars($u['Email']) ?></td>
                <td><?= htmlspecialchars($u['Role']) ?></td>
                <td><?= (int)$u['LoginCount'] ?></td>
                <td><?= htmlspecialchars($u['LastLogin']) ?></td>

                <td>
                    <?php if ($u['IsActive']): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Inactive</span>
                    <?php endif; ?>
                </td>

                <td>
                    <a href="edit_user.php?id=<?= (int)$u['UserID'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete_user.php?id=<?= (int)$u['UserID'] ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>

        </tbody>
    </table>
  </div>

  <!-- Pagination controls -->
  <nav>
    <ul class="pagination justify-content-center mt-3">

        <!-- Previous button -->
        <?php if ($page > 1): ?>
            <li class="page-item">
               <a class="page-link"
                  href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>">
                 Previous
               </a>
            </li>
        <?php endif; ?>

        <!-- Page numbers -->
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($page == $i ? 'active' : '') ?>">
               <a class="page-link"
                  href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>">
                 <?= $i ?>
               </a>
            </li>
        <?php endfor; ?>

        <!-- Next button -->
        <?php if ($page < $totalPages): ?>
            <li class="page-item">
               <a class="page-link"
                  href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>">
                 Next
               </a>
            </li>
        <?php endif; ?>

    </ul>
  </nav>

  <!-- Total users footer -->
  <div class="text-end mt-3" style="color:#f1c40f; font-weight:bold;">
      Total Users: <?= (int)$totalUsers ?>
  </div>

</div>

</body>
</html>