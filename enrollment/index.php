<?php
/**
 * ENROLLMENT MANAGEMENT – MAIN LIST PAGE
 *
 * This page does:
 * - Security check: only logged-in users with role admin or staff can access
 * - Reads filters from URL (search + status + recent)
 * - Uses pagination (8 rows per page)
 * - Runs COUNT(*) query to know total rows
 * - Runs main SELECT query with LIMIT/OFFSET for current page
 * - Shows table with Edit / Delete actions
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/auth.php';

// Make sure session is active (needed for user and flash messages)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: user must be logged in and be admin/staff
require_login();
require_role(['admin', 'staff']);

// Get the current logged-in user (for greeting in the header)
$user = current_user();

// Read search keyword and status filter from URL (GET parameters)
// Example: index.php?q=ali&status=registered&page=2
$keyword = $_GET['q'] ?? '';
$status  = $_GET['status'] ?? '';

// NEW: "Recent enrollments" filter (last X days)
$showRecent = isset($_GET['recent']) && $_GET['recent'] === '1';
$recentDays = 30; // you can change this to 7/14/etc.

// Pagination settings: how many rows per page
$perPage = 8;

// Current page number, from the URL. Default is 1.
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}

// We will build a common WHERE clause string and params array.
// This will be reused in both COUNT(*) query and main SELECT query.
$where   = " WHERE 1=1 ";
$params  = [];

// If user typed a search keyword, we filter by:
// - student first name
// - student last name
// - course name
// - course code
if ($keyword !== '') {
    $where .= " AND (
        s.FirstName  LIKE :kw1 OR
        s.LastName   LIKE :kw2 OR
        c.CourseName LIKE :kw3 OR
        c.CourseCode LIKE :kw4
    )";
    $like = "%{$keyword}%";
    $params[':kw1'] = $like;
    $params[':kw2'] = $like;
    $params[':kw3'] = $like;
    $params[':kw4'] = $like;
}

// If user selected a status, only show that status
if ($status !== '') {
    $where .= " AND e.Status = :status ";
    $params[':status'] = $status;
}

// NEW: If "recent" filter is active, only show enrollments from last X days
if ($showRecent) {
    $where .= " AND e.EnrollmentDate >= DATE_SUB(CURDATE(), INTERVAL :days DAY) ";
    $params[':days'] = $recentDays;
}

/**
 * STEP 1 – COUNT QUERY
 *
 * We first count how many rows match the current filters.
 * This is needed to calculate how many pages we have.
 */
$countSql = "
  SELECT COUNT(*) AS total
  FROM enrollment e
  JOIN student s ON s.StudentID = e.StudentID
  JOIN course  c ON c.CourseID  = e.CourseID
  $where
";

$stmt = db()->prepare($countSql);
$stmt->execute($params);
$totalRows = (int)$stmt->fetchColumn();

// Calculate total number of pages, at least 1
$totalPages = max(1, (int)ceil($totalRows / $perPage));

// If requested page number is bigger than total pages, clamp it
if ($page > $totalPages) {
    $page = $totalPages;
}

// OFFSET tells the database how many rows to skip before starting to return data
$offset = ($page - 1) * $perPage;

/**
 * STEP 2 – MAIN SELECT QUERY
 *
 * Now we actually fetch the rows for the current page, using:
 * - Same WHERE conditions as the COUNT query
 * - ORDER BY to show newest first
 * - LIMIT and OFFSET for pagination
 */
$sql = "
  SELECT e.EnrollmentID, e.EnrollmentDate, e.FinalGrade, e.Status,
         CONCAT(s.FirstName,' ',s.LastName) AS StudentName,
         c.CourseCode, c.CourseName
  FROM enrollment e
  JOIN student s ON s.StudentID = e.StudentID
  JOIN course  c ON c.CourseID  = e.CourseID
  $where
  ORDER BY e.EnrollmentDate DESC, e.EnrollmentID DESC
  LIMIT :limit OFFSET :offset
";

// For this query we reuse the same params array,
// but we also add :limit and :offset for pagination.
$paramsWithPaging = $params;
$paramsWithPaging[':limit']  = (int)$perPage;
$paramsWithPaging[':offset'] = (int)$offset;

$stmt = db()->prepare($sql);
$stmt->execute($paramsWithPaging);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// List of valid statuses, used for the status drop-down filter
$statuses = ['registered','in-progress','completed','dropped','failed'];

/**
 * Helper function: returns a small colored badge HTML for a given status.
 * Example: registered, completed, in-progress, etc.
 */
function badge(string $s): string {
    $colors = [
        'completed'   => '#16a34a',
        'registered'  => '#2563eb',
        'in-progress' => '#f59e0b',
        'failed'      => '#dc2626',
        'dropped'     => '#dc2626',
    ];
    $c = $colors[$s] ?? '#6b7280';

    return "<span style='
      padding:6px 14px;
      font-size:18px;
      border-radius:999px;
      color:$c;
      border:1px solid $c;
      background-color:{$c}10;
    '>".ucfirst($s)."</span>";
}

/**
 * Helper function: builds a pagination link.
 * It keeps the current search keyword and status and recent flag in the URL
 * so when we click pages, the filters remain applied.
 */
function page_link(int $page, string $keyword, string $status, bool $recent): string {
    $query = ['page' => $page];
    if ($keyword !== '') {
        $query['q'] = $keyword;
    }
    if ($status !== '') {
        $query['status'] = $status;
    }
    if ($recent) {
        $query['recent'] = '1';
    }
    return 'index.php?' . http_build_query($query);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Enrollment Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
:root {
  --primary: #2563eb;
  --primary-dark: #1d4ed8;
  --danger: #dc2626;
  --bg: #f1f5f9;
  --surface: #ffffff;
  --text: #111;
  --muted: #6b7280;
  --border: #d1d5db;
}

/* Basic page layout: center a white card on a soft grey background */
body {
  margin: 0;
  background: var(--bg);
  font-family: system-ui, sans-serif;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 30px;
}

/* Main white container */
.page {
  width: 100%;
  max-width: 1300px;
  background: var(--surface);
  border-radius: 16px;
  padding: 40px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.15);
}

/* Header with page title and user area */
.header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.page-title {
  font-size: 42px;
  font-weight: 800;
  margin: 0;
}

.page-sub {
  font-size: 18px;
  color: var(--muted);
  margin-top: 8px;
}

/* Top-right corner user info */
.user-box {
  text-align: right;
  font-size: 16px;
}
.user-role {
  font-size: 14px;
  color: var(--muted);
}

/* Flash message styles */
.flash {
  padding: 16px 20px;
  border-radius: 10px;
  font-size: 16px;
  margin-bottom: 20px;
}
.flash-success {
  background: #e7f6ee;
  border: 2px solid #34d399;
}
.flash-error {
  background: #fde2e1;
  border: 2px solid #f87171;
}

/* Button styles */
.btn {
  padding: 10px 18px;
  font-size: 16px;
  border-radius: 8px;
  border: none;
  text-decoration: none;
  cursor: pointer;
  font-weight: 600;
}

.btn-primary {
  background: var(--primary);
  color: #fff;
}
.btn-primary:hover { background: var(--primary-dark); }

.btn-secondary {
  background: #e5e7eb;
  color: #111;
}

.btn-danger {
  background: var(--danger);
  color: #fff;
}

/* Inputs and select styling */
.input, .select {
  padding: 10px 12px;
  font-size: 16px;
  border-radius: 8px;
  border: 2px solid var(--border);
}

/* Toolbar row: search form, filter form, add button */
.toolbar {
  display: grid;
  grid-template-columns: 1.8fr 1.3fr auto;
  gap: 20px;
  margin-bottom: 16px;
}

/* Recent filter buttons row */
.recent-row {
  display: flex;
  justify-content: flex-start;
  gap: 10px;
  margin-bottom: 10px;
  font-size: 14px;
}

.recent-link {
  padding: 6px 12px;
  border-radius: 999px;
  border: 1px solid var(--border);
  text-decoration: none;
  color: var(--text);
  background: #f9fafb;
}
.recent-link.active {
  background: var(--primary);
  color: #fff;
  border-color: var(--primary-dark);
}

/* Table styling */
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 15px;
}

th {
  background: #f3f4f6;
  padding: 14px;
  text-align: left;
  font-size: 16px;
}

td {
  padding: 14px;
  border-bottom: 2px solid #f0f0f0;
}

tr:hover td {
  background: #f9fafb;
}

/* Actions column for Edit/Delete */
.actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

/* Pagination controls at bottom */
.pagination {
  margin-top: 18px;
  display: flex;
  justify-content: center;
  gap: 8px;
  font-size: 14px;
}

.page-link {
  padding: 6px 10px;
  border-radius: 8px;
  border: 1px solid var(--border);
  text-decoration: none;
  color: var(--text);
  background: #f9fafb;
}

.page-link:hover {
  background: #e5e7eb;
}

.page-link.active {
  background: var(--primary);
  color: #fff;
  border-color: var(--primary-dark);
}
</style>
</head>
<body>
<div class="page">

  <!-- Header section: title and user info -->
  <div class="header-bar">
    <div>
      <h1 class="page-title">Enrollment Management</h1>
      <div class="page-sub">
        Search, filter and paginate enrollment records (8 per page).
        <?php if ($showRecent): ?>
          <br><strong>Showing recent enrollments from the last <?= $recentDays ?> days.</strong>
        <?php endif; ?>
      </div>
    </div>

    <div class="user-box">
      <strong><?= htmlspecialchars($user['name']) ?></strong><br>
      <span class="user-role"><?= htmlspecialchars($user['role']) ?></span><br><br>
      <a href="/student-record-system/index.php" class="btn btn-secondary">Dashboard</a>
      <a href="../auth/logout.php" class="btn btn-danger">Logout</a>
    </div>
  </div>

  <!-- Show any success or error messages -->
  <?php render_flash(); ?>

  <!-- NEW: Recent filter links (All vs Recent) -->
  <div class="recent-row">
    <a href="index.php<?= ($keyword || $status) ? '?' . http_build_query(['q'=>$keyword,'status'=>$status]) : '' ?>"
       class="recent-link <?= !$showRecent ? 'active' : '' ?>">
      All enrollments
    </a>

    <a href="index.php?<?= http_build_query(array_filter([
          'q'      => $keyword,
          'status' => $status,
          'recent' => '1'
        ])) ?>"
       class="recent-link <?= $showRecent ? 'active' : '' ?>">
      Recent enrollments (last <?= $recentDays ?> days)
    </a>
  </div>

  <!-- Toolbar: search, filter, add enrollment -->
  <div class="toolbar">
    <!-- SEARCH FORM: keyword search -->
    <form method="get" action="index.php" style="display:flex; gap:10px;">
      <input
        class="input"
        type="text"
        name="q"
        placeholder="Search student or course…"
        value="<?= htmlspecialchars($keyword) ?>"
      >
      <!-- keep recent flag when searching -->
      <?php if ($showRecent): ?>
        <input type="hidden" name="recent" value="1">
      <?php endif; ?>
      <button class="btn btn-primary" type="submit">Search</button>
      <!-- Clear button resets filters and goes back to full list -->
      <a href="index.php" class="btn btn-secondary">Clear</a>
    </form>

    <!-- FILTER FORM: status drop-down -->
    <form method="get" action="index.php" style="display:flex; gap:10px;">
      <select class="select" name="status">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= $s ?>" <?= $s === $status ? 'selected' : '' ?>>
            <?= ucfirst($s) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <!-- keep search + recent when changing status -->
      <?php if ($keyword !== ''): ?>
        <input type="hidden" name="q" value="<?= htmlspecialchars($keyword) ?>">
      <?php endif; ?>
      <?php if ($showRecent): ?>
        <input type="hidden" name="recent" value="1">
      <?php endif; ?>
      <button class="btn btn-primary" type="submit">Filter</button>
      <a href="index.php" class="btn btn-secondary">Reset</a>
    </form>

    <!-- Add Enrollment button goes to the add form -->
    <a href="add.php" class="btn btn-primary">+ Add Enrollment</a>
  </div>

  <!-- Main data table: enrollment list -->
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Student</th>
        <th>Course</th>
        <th>Date</th>
        <th>Status</th>
        <th>Grade</th>
        <th style="text-align:right;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr>
        <td colspan="7" style="text-align:center;"><em>No records found.</em></td>
      </tr>
    <?php endif; ?>

    <?php
      // Row numbering should continue across pages (not restart at 1)
      $i = $offset + 1;
      foreach ($rows as $r):
    ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($r['StudentName']) ?></td>
        <td><?= htmlspecialchars($r['CourseCode'] . ' — ' . $r['CourseName']) ?></td>
        <td><?= htmlspecialchars($r['EnrollmentDate']) ?></td>
        <td><?= badge($r['Status']) ?></td>
        <td><?= $r['FinalGrade'] === null ? '—' : htmlspecialchars($r['FinalGrade']) ?></td>
        <td style="text-align:right;">
          <div class="actions">
            <!-- Edit button: opens the edit form for this enrollment -->
            <a href="edit.php?id=<?= (int)$r['EnrollmentID'] ?>" class="btn btn-secondary">Edit</a>
            <!-- Delete form: uses POST and a confirm dialog -->
            <form action="delete.php" method="post"
                  onsubmit="return confirm('Delete this record?');">
              <input type="hidden" name="id" value="<?= (int)$r['EnrollmentID'] ?>">
              <button class="btn btn-danger" type="submit">Delete</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Pagination controls: Previous, page numbers, Next -->
  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a class="page-link" href="<?= htmlspecialchars(page_link($page - 1, $keyword, $status, $showRecent)) ?>">Previous</a>
      <?php endif; ?>

      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a
          class="page-link <?= $p === $page ? 'active' : '' ?>"
          href="<?= htmlspecialchars(page_link($p, $keyword, $status, $showRecent)) ?>"
        >
          <?= $p ?>
        </a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a class="page-link" href="<?= htmlspecialchars(page_link($page + 1, $keyword, $status, $showRecent)) ?>">Next</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>

<script>
// Auto-hide flash after 6 seconds
setTimeout(() => {
    document.querySelectorAll('.flash').forEach(el => {
        el.style.transition = "opacity 0.8s";
        el.style.opacity = "0";
        setTimeout(() => el.remove(), 800);
    });
}, 6000);
</script>

</body>
</html>
