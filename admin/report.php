<?php
session_start();
include_once __DIR__ . '/../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin' || !isset($_SESSION['admin_con'])) {
    header('Location: login.php');
    exit;
}

$admin_name = $_SESSION['user_name'];
$admin_cat = $_SESSION['admin_con']; // Using admin_con as the category/department they handle

// Handle status update
if (isset($_POST['update_status'])) {
    $report_id = intval($_POST['report_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $sql = "UPDATE problem_posts SET status = ? WHERE id = ? AND category = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sis', $new_status, $report_id, $admin_cat);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['message'] = "Status updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update status.";
    }
    mysqli_stmt_close($stmt);
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit;
}

// Handle filters from GET
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_range = $_GET['date_range'] ?? '';

// Build base WHERE clause for category restriction
$base_where = "WHERE category = '" . mysqli_real_escape_string($conn, $admin_cat) . "'";

// Add search filter
if ($search) {
    $escaped_search = mysqli_real_escape_string($conn, $search);
    $base_where .= " AND (title LIKE '%$escaped_search%' OR description LIKE '%$escaped_search%' OR location_name LIKE '%$escaped_search%')";
}

// Add status filter
if ($status_filter && $status_filter !== 'Status') {
    $escaped_status = mysqli_real_escape_string($conn, $status_filter);
    $base_where .= " AND status = '$escaped_status'";
}

// Handle date range filter
if ($date_range !== 'Date Range') {
    if ($date_range === 'Last 7 days') {
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $base_where .= " AND created_at >= '$start_date'";
    } elseif ($date_range === 'Last 30 days') {
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $base_where .= " AND created_at >= '$start_date'";
    } elseif ($date_range === 'This Year') {
        $start_date = date('Y-01-01');
        $base_where .= " AND created_at >= '$start_date'";
    }
}

// Function to map category to department name
function getDepartmentName($category) {
    switch ($category) {
        case 'Roads':
            return 'Public Works';
        case 'Waste':
            return 'Municipal';
        case 'Water':
            return 'Water Supply';
        case 'Safety':
            return 'Public Works';
        default:
            return 'N/A';
    }
}

// Fetch summary counts (overall for admin's category, ignoring other filters for summary)
$cat_where = "WHERE category = '" . mysqli_real_escape_string($conn, $admin_cat) . "'";
$total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM problem_posts $cat_where");
$total = mysqli_fetch_assoc($total_result)['total'] ?? 0;

$pending_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM problem_posts $cat_where AND status = 'Pending'");
$pending = mysqli_fetch_assoc($pending_result)['count'] ?? 0;

$inprogress_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM problem_posts $cat_where AND status = 'In Progress'");
$inprogress = mysqli_fetch_assoc($inprogress_result)['count'] ?? 0;

$resolved_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM problem_posts $cat_where AND status = 'Resolved'");
$resolved = mysqli_fetch_assoc($resolved_result)['count'] ?? 0;

// Fetch reports for table (with filters)
$reports_sql = "SELECT * FROM problem_posts $base_where ORDER BY created_at DESC";
$reports_result = mysqli_query($conn, $reports_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aawaz Admin - Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
       /* --- Global Reset & Typography --- */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: 'Poppins', sans-serif;
    background-color: #f0f2f5; /* Lighter background for a cleaner look */
    color: #333;
    line-height: 1.6;
}

/* --- Layout Container --- */
.container {
    display: flex;
    min-height: 100vh;
}

/* --- Sidebar --- */
.sidebar {
    width: 250px;
    background-color: #1c2a41; /* Darker, more professional blue/grey */
    color: #fff;
    padding: 20px 0;
    position: fixed;
    height: 100%;
    overflow-y: auto;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}
.sidebar-header {
    padding: 0 20px 20px;
    border-bottom: 1px solid #33445c; /* Subtler divider */
    margin-bottom: 20px;
    font-size: 1.2rem;
    font-weight: 600;
}
.sidebar .user {
    color: #e0e0e0;
    margin-top: 5px;
}
.sidebar .online {
    color: #4caf50;
    font-size: 0.8rem;
    margin-left: 5px;
}
.sidebar-menu {
    margin-top: 10px;
}
.sidebar-menu a {
    display: flex;
    align-items: center;
    padding: 14px 20px;
    color: #b0c4de; /* Lighter text color for contrast */
    text-decoration: none;
    transition: all 0.3s ease-in-out;
    border-left: 4px solid transparent; /* Space for active indicator */
}
.sidebar-menu a i {
    margin-right: 15px;
    font-size: 18px;
    width: 20px; /* fixed width for icons */
}
.sidebar-menu a:hover {
    background-color: #2b405a; /* Slightly lighter hover */
    color: #fff;
    border-left-color: #55b6ff;
}
.sidebar-menu a.active {
    background-color: #2361e8; /* Primary blue for active state */
    color: #fff;
    border-left-color: #fff; /* Solid white active indicator */
    font-weight: 500;
}

/* --- Main Content Area --- */
.main-content {
    margin-left: 250px;
    flex: 1;
    padding: 30px; /* More padding */
    transition: margin-left 0.3s;
}

/* --- Header Bar --- */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #fff;
    padding: 15px 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); /* Stronger shadow */
    position: sticky; /* Keep header visible */
    top: 0;
    z-index: 100;
}
.header h1 {
    font-size: 26px; /* Larger title */
    font-weight: 700;
    color: #2c3e50;
}
.header .user-info {
    display: flex;
    align-items: center;
    gap: 20px;
}
.header .notifications {
    position: relative;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: background-color 0.2s;
}
.header .notifications:hover {
    background-color: #f0f2f5;
}
.header .notifications .badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #ff5722; /* Attention-grabbing red/orange */
    color: #fff;
    font-size: 10px;
    width: 18px;
    height: 18px;
    line-height: 18px;
    text-align: center;
    border-radius: 50%;
    border: 2px solid #fff;
    font-weight: 700;
}
.header .user-info > span {
    font-weight: 500;
    color: #555;
    cursor: pointer;
}

/* --- Messages --- */
.message {
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
}
.message.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.message.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* --- Summary Cards --- */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px; /* Increased gap */
    margin-bottom: 30px;
}
.card {
    background-color: #fff;
    border-radius: 15px; /* More rounded corners */
    padding: 30px;
    text-align: left; /* Align text left */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s, box-shadow 0.3s;
    border-left: 5px solid #2361e8; /* Left border highlight */
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.card .icon {
    font-size: 32px;
    color: #2361e8;
    margin-bottom: 10px;
    background-color: #e3f2fd; /* Light background for icon */
    padding: 10px;
    border-radius: 8px;
    display: inline-block;
}

/* Specific card colors */
.summary-cards .card:nth-child(2) { border-left-color: #ffb300; } /* Pending - Amber */
.summary-cards .card:nth-child(2) .icon { color: #ffb300; background-color: #fffde7; }
.summary-cards .card:nth-child(3) { border-left-color: #00bcd4; } /* In Progress - Cyan */
.summary-cards .card:nth-child(3) .icon { color: #00bcd4; background-color: #e0f7fa; }
.summary-cards .card:nth-child(4) { border-left-color: #4caf50; } /* Resolved - Green */
.summary-cards .card:nth-child(4) .icon { color: #4caf50; background-color: #e8f5e9; }


.card .number {
    font-size: 36px; /* Larger number */
    font-weight: 700;
    color: #1a1a1a;
}
.card .label {
    font-size: 15px;
    color: #666;
    margin-top: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* --- Filters and Table Layout --- */
.flex {
    display: flex;
    gap: 30px; /* Increased gap */
    align-items: flex-start; /* Align filters to the top */
}
.flex form.filters {
    width: 300px; /* Fixed width for filter sidebar */
    flex-shrink: 0;
    background-color: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column; /* Stack filter elements vertically */
    gap: 20px; /* Spacing between filter elements */
}
.flex .table-container {
    flex-grow: 1; /* Allow table to take remaining space */
}

.filters .search-box {
    width: 100%;
    position: relative;
}
.filters input[type="text"], .filters select {
    width: 100%;
    padding: 12px 18px 12px 18px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    background-color: #f7f7f7;
    transition: border-color 0.3s;
    appearance: none; /* Remove default select styling */
    -webkit-appearance: none;
    cursor: pointer;
}
.filters input[type="text"]:focus, .filters select:focus {
    border-color: #2361e8;
    outline: none;
    background-color: #fff;
}
.filters .search-box i {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    right: 15px;
    color: #a0a0a0;
}
.filters button[type="submit"] {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    background: #2361e8;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.1s;
    margin-top: 10px;
}
.filters button[type="submit"]:hover {
    background: #1b4fb5;
    transform: translateY(-1px);
}


/* --- Reports Table --- */
.table-container {
    background-color: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}
table {
    width: 100%;
    border-collapse: collapse;
}
thead th {
    background-color: #f8f9fa;
    padding: 18px 20px; /* Increased padding */
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    color: #555;
    border-bottom: 2px solid #e0e0e0;
}
tbody td {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    vertical-align: middle;
}
tbody tr:hover {
    background-color: #f5f8ff; /* Light blue hover */
}

/* Status Badges */
.status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.status-pending { background-color: #fff3e0; color: #ff9800; } /* Light orange, Amber text */
.status-inprogress { background-color: #e3f2fd; color: #2196f3; } /* Light blue, Blue text */
.status-resolved { background-color: #e8f5e9; color: #4caf50; } /* Light green, Green text */

/* Actions Buttons */
.actions button {
    padding: 8px 14px; /* Increased padding */
    margin-right: 8px;
    border: none;
    border-radius: 20px;
    font-size: 13px;
    cursor: pointer;
    background-color: #f0f2f5;
    color: #495057;
    transition: background-color 0.3s, color 0.3s;
    font-weight: 500;
}
.actions button:hover {
    background-color: #2361e8;
    color: #fff;
    box-shadow: 0 2px 5px rgba(35, 97, 232, 0.4);
}
.actions a button {
    text-decoration: none;
}

/* --- Modal Styles --- */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}
.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: none;
    border-radius: 10px;
    width: 80%;
    max-width: 400px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    position: relative;
}
.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    position: absolute;
    right: 15px;
    top: 10px;
}
.close:hover {
    color: #000;
}
.modal h2 {
    margin-bottom: 20px;
    color: #333;
}
.modal form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.modal label {
    font-weight: 500;
    color: #555;
}
.modal select, .modal button {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}
.modal button[type="submit"] {
    background-color: #2361e8;
    color: white;
    border: none;
    cursor: pointer;
    font-weight: 600;
}
.modal button[type="submit"]:hover {
    background-color: #1b4fb5;
}

/* --- Responsive Adjustments --- */
@media (max-width: 1200px) {
    .summary-cards { grid-template-columns: repeat(2, 1fr); }
    .flex { flex-direction: column; }
    .flex form.filters { width: 100%; flex-direction: row; gap: 15px; flex-wrap: wrap; }
    .flex form.filters > div, .flex form.filters > select, .flex form.filters > button { width: auto; flex-grow: 1; min-width: 150px; }
}
@media (max-width: 768px) {
    .sidebar { position: relative; width: 100%; height: auto; }
    .main-content { margin-left: 0; padding: 15px; }
    .header { padding: 15px 20px; }
    .summary-cards { grid-template-columns: 1fr; }
    table, thead, tbody, th, td, tr { display: block; }
    thead tr { position: absolute; top: -9999px; left: -9999px; } /* Hide table header */
    tbody tr { border: 1px solid #ccc; margin-bottom: 10px; border-radius: 8px; }
    tbody td { 
        border: none;
        position: relative;
        padding-left: 50%;
        text-align: right;
    }
    tbody td:before { 
        content: attr(data-label);
        position: absolute;
        left: 10px;
        width: 45%;
        padding-right: 10px;
        white-space: nowrap;
        text-align: left;
        font-weight: 600;
        color: #333;
    }
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
}
/* Re-adjust the flex layout for mobile filter buttons */
@media (max-width: 600px) {
    .flex form.filters { flex-direction: column; }
    .flex form.filters > div, .flex form.filters > select, .flex form.filters > button { width: 100%; }
}
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="user"><?php echo htmlspecialchars($admin_name); ?> <span class="online">● Online</span></div>
            </div>
            <nav class="sidebar-menu">
                <ul>
                    <li><a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="report.php" class="active"><i class="fas fa-file-alt"></i>Reports</a></li>
                   <li><a href="trust_user.php"><i class="fas fa-map-marker-alt"></i>Users Management</a></li>
                    <li><a href="notice.php"><i class="fas fa-bell"></i>Notices</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Reports - <?php echo htmlspecialchars($admin_cat); ?></h1>
                <div class="user-info">
                    <div class="notifications">
                        <i class="fas fa-bell" style="font-size:20px;color:#666;"></i>
                        <span class="badge">3</span>
                    </div>
                    <div class="notifications">
                        <i class="fas fa-envelope" style="font-size:20px;color:#666;"></i>
                        <span class="badge">2</span>
                    </div>
                    <span><?php echo htmlspecialchars($admin_name); ?> ▼</span>
                </div>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="message success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="message error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="card">
                    <div class="icon"><i class="fas fa-file-alt"></i></div>
                    <div class="number"><?php echo $total; ?></div>
                    <div class="label">Total Reports</div>
                </div>
                <div class="card">
                    <div class="icon"><i class="fas fa-clock"></i></div>
                    <div class="number"><?php echo $pending; ?></div>
                    <div class="label">Pending</div>
                </div>
                <div class="card">
                    <div class="icon"><i class="fas fa-tools"></i></div>
                    <div class="number"><?php echo $inprogress; ?></div>
                    <div class="label">In Progress</div>
                </div>
                <div class="card">
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <div class="number"><?php echo $resolved; ?></div>
                    <div class="label">Resolved</div>
                </div>
            </div>
<div class="flex">
            <!-- Filters -->
            <form method="GET" class="filters">
                <div class="search-box">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search reports...">
                    <i class="fas fa-search"></i>
                </div>
                <select name="status">
                    <option value="" <?php if($status_filter === '') echo 'selected'; ?>>Status</option>
                    <option value="Pending" <?php if($status_filter === 'Pending') echo 'selected'; ?>>Pending</option>
                    <option value="In Progress" <?php if($status_filter === 'In Progress') echo 'selected'; ?>>In Progress</option>
                    <option value="Resolved" <?php if($status_filter === 'Resolved') echo 'selected'; ?>>Resolved</option>
                </select>
                <!-- Category filter removed as view is restricted to own category/department -->
                <select name="date_range">
                    <option value="" <?php if($date_range === '') echo 'selected'; ?>>Date Range</option>
                    <option value="Last 7 days" <?php if($date_range === 'Last 7 days') echo 'selected'; ?>>Last 7 days</option>
                    <option value="Last 30 days" <?php if($date_range === 'Last 30 days') echo 'selected'; ?>>Last 30 days</option>
                    <option value="This Year" <?php if($date_range === 'This Year') echo 'selected'; ?>>This Year</option>
                </select>
                <button type="submit" style="padding: 12px 20px; border: 1px solid #ddd; border-radius: 8px; background: #2361e8; color: white; cursor: pointer;">Apply Filters</button>
            </form>

            <!-- Reports Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Department</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($reports_result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($reports_result)): ?>
                                <?php 
                                $status_class = 'status-' . strtolower(str_replace(' ', '-', $row['status']));
                                $status_display = $row['status'];
                                $date_formatted = date('Y-m-d', strtotime($row['created_at']));
                                $dept_name = getDepartmentName($row['category']);
                                $actions = '<a href="../map.php?lng='.$row['latitude'].'&lat='.$row['longitude'].'"><button>View</button></a>';
                                if ($row['status'] === 'Pending') {
                                    $actions .= '<button onclick="verifyReport(' . $row['id'] . ')">Verify</button>';
                                }
                                $actions .= ' <button onclick="openStatusModal(' . $row['id'] . ', \'' . addslashes($row['status']) . '\')">Update Status</button>';
                                ?>
                                <tr>
                                    <td data-label="ID">#<?php echo $row['id']; ?></td>
                                    <td data-label="Title"><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td data-label="Category"><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td data-label="Location"><?php echo htmlspecialchars($row['location_name']); ?></td>
                                    <td data-label="Date"><?php echo $date_formatted; ?></td>
                                    <td data-label="Status"><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_display; ?></span></td>
                                    <td data-label="Department"><?php echo $dept_name; ?></td>
                                    <td data-label="Actions" class="actions"><?php echo $actions; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 20px;">No reports found for <?php echo htmlspecialchars($admin_cat); ?> category.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div></div>

            <!-- Status Update Modal -->
            <div id="statusModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Update Status</h2>
                    <form method="POST">
                        <input type="hidden" name="report_id" id="report_id">
                        <input type="hidden" name="update_status" value="1">
                        <label>Current Status: <span id="current_status"></span></label>
                        <label>New Status:</label>
                        <select name="status" id="new_status">
                            <option value="Pending">Pending (Active)</option>
                            <option value="In Progress">In Progress (Working)</option>
                            <option value="Resolved">Resolved (Completed)</option>
                        </select>
                        <button type="submit">Update Status</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Placeholder JS functions for actions (implement as needed, e.g., modals or redirects)
        function viewReport(id) { alert('Viewing report ' + id); /* or window.location = 'view.php?id=' + id; */ }
        function verifyReport(id) { alert('Verifying report ' + id); }
        function changeStatus(id) { alert('Changing status for report ' + id); }
        function assignReport(id) { alert('Assigning report ' + id); }

        // Modal functionality
        function openStatusModal(id, current) {
            document.getElementById('report_id').value = id;
            document.getElementById('current_status').textContent = current;
            document.getElementById('new_status').value = current;
            document.getElementById('statusModal').style.display = 'block';
        }

        var modal = document.getElementById('statusModal');
        var span = document.getElementsByClassName('close')[0];
        span.onclick = function() {
            modal.style.display = 'none';
        };
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        };
    </script>
</body>
</html>