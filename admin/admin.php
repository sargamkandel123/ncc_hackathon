<?php
session_start(); // Start the session at the very beginning

include_once __DIR__ . '/../config.php';
$userId = $_SESSION['user_id'];

$sql = "SELECT * FROM users WHERE id = $userId";
$res = mysqli_query($conn, $sql);

$fetch = mysqli_fetch_assoc($res);

$image = $fetch['image'];

// --- Authentication and Authorization Check ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Not logged in, redirect to login page
    header('Location: login.php'); // Replace with your login page
    exit;
}

if ($_SESSION['user_type'] !== 'admin') {
    // Not an admin, redirect to a different page or show error
    header('Location: unauthorized.php'); // Replace with an unauthorized access page
    exit;
}

// Get admin-specific session data
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'];
$admin_email = $_SESSION['user_email'];
$admin_con = $_SESSION['admin_con']; // e.g., 'Roads', 'Water', 'Waste', 'Safety'

// --- Initialize variables for dashboard data ---
$totalReports = 0;
$pendingIssues = 0;
$inProgressIssues = 0;
$resolvedIssues = 0;
$adminCategoryReportCount = 0; // For the admin's specific category
$totalComments = 0;
$totalLikes = 0;
$recentReports = [];
$issueTrendsLabels = [];
$issueTrendsData = [];

// Helper function to execute a simple count query
function getCount($conn, $query, $param_type = '', $param_value = null) {
    $count = 0;
    if ($stmt = mysqli_prepare($conn, $query)) {
        if ($param_type && $param_value !== null) {
            mysqli_stmt_bind_param($stmt, $param_type, $param_value);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $count);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    } else {
        error_log("MySQLi Count Query Prepare Error: " . mysqli_error($conn));
    }
    return $count;
}

try {
    // --- 1. Fetch Stats Cards Data ---
    // Total Reports
    $totalReports = getCount($conn, "SELECT COUNT(*) FROM problem_posts WHERE category = ?", 's', $admin_con);

    // Pending Issues
    $pendingIssues = getCount($conn, "SELECT COUNT(*) FROM problem_posts WHERE category = ? AND status = 'pending'", 's', $admin_con);

    // In Progress Issues
    $inProgressIssues = getCount($conn, "SELECT COUNT(*) FROM problem_posts WHERE category = ? AND status = 'in-progress'", 's', $admin_con);

    // Resolved Issues
    $resolvedIssues = getCount($conn, "SELECT COUNT(*) FROM problem_posts WHERE category = ? AND status = 'resolved'", 's', $admin_con);

    // --- 2. Fetch Reports by Category (for admin's specific category) ---
    $adminCategoryReportCount = $totalReports;

    // --- 3. Fetch Community Engagement Data ---
    // Total Comments for posts in this admin's category
    $totalComments = getCount($conn, "
        SELECT COUNT(pc.id)
        FROM problem_comments pc
        JOIN problem_posts pp ON pc.post_id = pp.id
        WHERE pp.category = ?
    ", 's', $admin_con);

    // Total Likes for posts in this admin's category
    $totalLikes = getCount($conn, "
        SELECT COUNT(pl.id)
        FROM problem_likes pl
        JOIN problem_posts pp ON pl.post_id = pp.id
        WHERE pp.category = ?
    ", 's', $admin_con);

    // --- 4. Fetch Recent Reports ---
    $query_recent_reports = "
        SELECT id, title, category, status
        FROM problem_posts
        WHERE category = ?
        ORDER BY created_at DESC
        LIMIT 5
    ";
    if ($stmt = mysqli_prepare($conn, $query_recent_reports)) {
        mysqli_stmt_bind_param($stmt, 's', $admin_con);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $recentReports[] = $row;
        }
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);
    } else {
        error_log("MySQLi Recent Reports Query Prepare Error: " . mysqli_error($conn));
    }

    // --- 5. Fetch Issue Trends Data (for Chart) ---
    // For demonstration with realistic dummy data, we'll override with hardcoded values
    // This simulates a natural trend: gradual increase with some fluctuation over the last 6 months (Jul-Dec 2025)
    $issueTrendsLabels = ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $issueTrendsData = [12, 18, 25, 20, 28, 35]; // Realistic dummy data: starting moderate, peak in Sep, dip in Oct, rising again

    /*
    // Original DB query (commented out for dummy data)
    $query_issue_trends = "
        SELECT
            DATE_FORMAT(created_at, '%b') AS month_label,
            COUNT(*) AS report_count
        FROM problem_posts
        WHERE category = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month_label, DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY DATE_FORMAT(created_at, '%Y-%m') ASC
    ";
    if ($stmt = mysqli_prepare($conn, $query_issue_trends)) {
        mysqli_stmt_bind_param($stmt, 's', $admin_con);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $trendData = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $trendData[] = $row;
        }
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);

        // Prepare data for Chart.js
        foreach ($trendData as $row) {
            $issueTrendsLabels[] = $row['month_label'];
            $issueTrendsData[] = $row['report_count'];
        }
    } else {
        error_log("MySQLi Issue Trends Query Prepare Error: " . mysqli_error($conn));
    }

    // If no data, provide default labels for better chart display
    if (empty($issueTrendsLabels)) {
        $issueTrendsLabels = ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $issueTrendsData = [12, 18, 25, 20, 28, 35];
    }
    */

} catch (Exception $e) {
    // Catch any general PHP errors
    error_log("General Error: " . $e->getMessage());
    die("An error occurred while fetching data.");
} finally {
    // Close the database connection
    if ($conn) {
        mysqli_close($conn);
    }
}

// Convert PHP arrays to JSON for JavaScript
$js_issueTrendsLabels = json_encode($issueTrendsLabels);
$js_issueTrendsData = json_encode($issueTrendsData);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aawaz - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* CSS Fixes and Styling for Attractiveness */
        :root {
            --primary-color: #2361e8; /* Blue */
            --secondary-color: #1a1a1a; /* Dark Sidebar */
            --success-color: #1cc88a; /* Green */
            --warning-color: #f6c23e; /* Yellow */
            --danger-color: #e74a3b; /* Red */
            --bg-light: #f4f7fa;
            --bg-card: #ffffff;
            --text-dark: #333;
            --text-secondary: #666;
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
       
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }

        /* --- Sidebar --- */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100%;
            background-color: var(--secondary-color);
            color: #fff;
            padding: 20px;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0,0,0,0.15);
        }
        .sidebar .user-profile {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar .user-profile img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            margin-right: 15px;
            border: 2px solid var(--primary-color);
        }
        .sidebar .user-profile .user-name {
            font-weight: 600;
            font-size: 1.1rem;
        }
        .sidebar .user-profile .user-status {
            font-size: 0.8rem;
            color: var(--success-color);
            display: flex;
            align-items: center;
        }
        .sidebar .user-profile .user-status i {
            margin-right: 5px;
            font-size: 0.5rem !important; /* Overriding default FontAwesome size */
        }
        .sidebar nav ul {
            list-style: none;
        }
        .sidebar nav ul li {
            margin-bottom: 5px;
        }
        .sidebar nav ul li a {
            display: flex;
            align-items: center;
            color: #ddd;
            text-decoration: none;
            padding: 12px 10px;
            border-radius: 8px;
            transition: background 0.3s, color 0.3s;
            font-weight: 500;
        }
        .sidebar nav ul li a:hover {
            background-color: #333;
            color: #fff;
        }
        .sidebar nav ul li a.active {
            background-color: var(--primary-color);
            color: #fff;
            box-shadow: 0 2px 10px rgba(35, 97, 232, 0.4);
        }
        .sidebar nav ul li a i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        /* --- Main Content --- */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* --- Header (Top Bar) --- */
        .header {
            background-color: var(--bg-card);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .header .page-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        .header .right-section {
            display: flex;
            align-items: center;
        }
        .header .icon {
            margin-left: 20px;
            position: relative;
            cursor: pointer;
            transition: color 0.2s;
        }
        .header .icon:hover i {
            color: var(--primary-color);
        }
        .header .icon i {
            font-size: 1.2rem;
            color: var(--text-secondary);
        }
        .header .icon .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: #fff;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 50%;
            font-weight: 600;
            line-height: 1;
        }
        .header .user-avatar {
            display: flex;
            align-items: center;
            margin-left: 30px;
            cursor: pointer;
            padding-left: 15px;
            border-left: 1px solid #eee;
        }
        .header .user-avatar img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        .header .user-avatar span {
            font-weight: 600;
            color: var(--text-dark);
        }

        /* --- Content Area --- */
        .content {
            padding: 30px;
            flex-grow: 1;
        }

        /* --- Stats Grid --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: var(--bg-card);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 5px solid; /* Use for status colors */
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        .stat-card .text-content {
            text-align: left;
        }
        .stat-card .icon-container {
            font-size: 2.5rem;
            padding: 10px;
            border-radius: 50%;
            opacity: 0.8;
        }
       
        /* Specific Card Styles */
        .stat-card:nth-child(1) { border-left-color: var(--primary-color); }
        .stat-card:nth-child(1) .icon-container { color: var(--primary-color); background-color: rgba(35, 97, 232, 0.1); }
       
        .stat-card:nth-child(2) { border-left-color: var(--warning-color); }
        .stat-card:nth-child(2) .icon-container { color: var(--warning-color); background-color: rgba(246, 194, 62, 0.1); }
       
        .stat-card:nth-child(3) { border-left-color: #36b9cc; } /* Info color */
        .stat-card:nth-child(3) .icon-container { color: #36b9cc; background-color: rgba(54, 185, 204, 0.1); }
       
        .stat-card:nth-child(4) { border-left-color: var(--success-color); }
        .stat-card:nth-child(4) .icon-container { color: var(--success-color); background-color: rgba(28, 200, 138, 0.1); }

        .stat-card .number {
            font-size: 2.2rem;
            font-weight: 700;
            line-height: 1;
        }
        .stat-card .label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 500;
            text-transform: uppercase;
            margin-top: 5px;
        }

        /* --- Widgets Grid --- */
        .widgets-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 2fr; /* Adjusted layout */
            gap: 25px;
            margin-bottom: 30px;
        }
        .widget {
            background-color: var(--bg-card);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .widget h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        /* Reports by Department Widget */
        .category-list {
            list-style: none;
            padding: 0;
        }
        .category-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 1rem;
            font-weight: 500;
        }
        .category-list li:last-child {
            border-bottom: none;
        }
        .category-list li span {
            font-weight: 700;
            color: var(--primary-color);
            background: rgba(35, 97, 232, 0.1);
            padding: 5px 10px;
            border-radius: 5px;
        }

        /* Community Engagement Widget */
        .engagement-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }
        .engagement-card {
            background-color: var(--bg-light);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .engagement-card i {
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        .engagement-card .number {
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .engagement-card .label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Recent Reports Widget */
        .recent-reports table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .recent-reports th {
            padding: 12px 10px;
            text-align: left;
            background-color: #f8f8f8;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
            border-bottom: 2px solid #eee;
        }
        .recent-reports td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        .recent-reports tbody tr:hover {
            background-color: #fcfcfc;
        }
        .recent-reports .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        /* Status Colors */
        .status.pending { background-color: rgba(246, 194, 62, 0.15); color: var(--warning-color); }
        .status.in-progress { background-color: rgba(54, 185, 204, 0.15); color: #36b9cc; }
        .status.resolved { background-color: rgba(28, 200, 138, 0.15); color: var(--success-color); }
        .status.rejected { background-color: rgba(231, 74, 59, 0.15); color: var(--danger-color); }

        /* --- Chart Container --- */
        .chart-container {
            background-color: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            grid-column: 1 / -1; /* Full width for chart */
            height: 250px; /* Fixed smaller height for the chart container */
            display: flex;
            flex-direction: column;
        }
        .chart-container h3 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 2px solid #eee;
            padding-bottom: 8px;
            flex-shrink: 0;
        }
        .chart-wrapper {
            flex: 1;
            position: relative;
            height: 200px; /* Smaller, viewable height for the chart */
        }

        /* --- Responsive Design --- */
        @media (max-width: 1200px) {
             .widgets-grid {
                grid-template-columns: 1fr 1fr; /* 2 columns on medium screens */
            }
            .recent-reports {
                grid-column: span 2; /* Recent reports takes full width */
            }
        }

        @media (max-width: 900px) {
            .widgets-grid {
                grid-template-columns: 1fr; /* 1 column on small screens */
            }
            .recent-reports {
                grid-column: span 1;
            }
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
            .chart-container {
                height: 220px;
            }
            .chart-wrapper {
                height: 170px;
            }
        }
       
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding-bottom: 10px;
            }
            .main-content {
                margin-left: 0;
            }
            .header {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }
            .header .page-title {
                width: 100%;
                text-align: center;
            }
            .header .right-section {
                justify-content: center;
                width: 100%;
            }
            .stats-grid, .widgets-grid {
                grid-template-columns: 1fr;
            }
            .stat-card {
                justify-content: space-around;
            }
            .chart-container {
                padding: 15px;
                height: 200px;
            }
            .chart-wrapper {
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="user-profile">
            <img src="4.jpg" alt="User Avatar"> <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($admin_name); ?></span>
                <span class="user-status"><i class="fas fa-circle" style="font-size: 0.6rem; margin-right: 5px;"></i>Online</span>
            </div>
        </div>
        <nav>
            <ul>
                <li><a href="admin.php" class="active"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
                <li><a href="report.php?category=<?= urlencode($admin_con); ?>"><i class="fas fa-file-alt"></i>Reports</a></li>
                    <li><a href="trust_user.php"><i class="fas fa-map-marker-alt"></i>Users Management</a></li>
                <li><a href="notice.php"><i class="fas fa-bell"></i>Notices</a></li>
            </ul>
        </nav>
    </aside>

    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Dashboard (<?= htmlspecialchars($admin_con); ?> Admin)</h1>
            <div class="right-section">
                <div class="icon">
                    <i class="fas fa-bell"></i>
                    <span class="badge">3</span>
                </div>
                <div class="icon">
                    <i class="fas fa-envelope"></i>
                    <span class="badge">2</span>
                </div>
                <div class="user-avatar">
                    <img src="4.jpg" alt="Avatar"> <span><?= htmlspecialchars($admin_name); ?></span>
                    <i class="fas fa-chevron-down" style="margin-left: 5px;"></i>
                </div>
            </div>
        </header>

        <section class="content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="text-content">
                        <div class="number"><?= $totalReports; ?></div>
                        <div class="label">Total Reports</div>
                    </div>
                    <div class="icon-container"><i class="fas fa-file-alt"></i></div>
                </div>
                <div class="stat-card">
                    <div class="text-content">
                        <div class="number"><?= $pendingIssues; ?></div>
                        <div class="label">Pending Issues</div>
                    </div>
                    <div class="icon-container"><i class="fas fa-hourglass-half"></i></div>
                </div>
                <div class="stat-card">
                    <div class="text-content">
                        <div class="number"><?= $inProgressIssues; ?></div>
                        <div class="label">In Progress</div>
                    </div>
                    <div class="icon-container"><i class="fas fa-tools"></i></div>
                </div>
                <div class="stat-card">
                    <div class="text-content">
                        <div class="number"><?= $resolvedIssues; ?></div>
                        <div class="label">Resolved Issues</div>
                    </div>
                    <div class="icon-container"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>

            <div class="widgets-grid">
                <div class="widget">
                    <h3>Reports by Department</h3>
                    <ul class="category-list">
                        <li><?= htmlspecialchars($admin_con); ?> <span><?= $adminCategoryReportCount; ?></span></li>
                    </ul>
                </div>

                <div class="widget">
                    <h3>Community Engagement</h3>
                    <div class="engagement-grid">
                        <div class="engagement-card">
                            <i class="fas fa-comment"></i>
                            <div class="number"><?= $totalComments; ?></div>
                            <div class="label">Total Comments</div>
                        </div>
                        <div class="engagement-card">
                            <i class="fas fa-thumbs-up"></i>
                            <div class="number"><?= $totalLikes; ?></div>
                            <div class="label">Total Upvotes</div>
                        </div>
                    </div>
                </div>

                <div class="widget recent-reports">
                    <h3>Recent Reports (<?= htmlspecialchars($admin_con); ?>)</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentReports)): ?>
                                <tr>
                                    <td colspan="4">No recent reports found for <?= htmlspecialchars($admin_con); ?>.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentReports as $report): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($report['id']); ?></td>
                                    <td><?= htmlspecialchars($report['title']); ?></td>
                                    <td><?= htmlspecialchars($report['category']); ?></td>
                                    <td><span class="status <?= htmlspecialchars(str_replace(' ', '-', $report['status'])); ?>"><?= htmlspecialchars($report['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="chart-container">
                <h3>Issue Trends (<?= htmlspecialchars($admin_con); ?>)</h3>
                <div class="chart-wrapper">
                    <canvas id="issueChart"></canvas>
                </div>
            </div>
        </section>
    </div>

    <script>
        // Chart JS for Issue Trends (Line Chart)
        const ctx = document.getElementById('issueChart').getContext('2d');
        const issueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= $js_issueTrendsLabels; ?>, // Dynamic labels from PHP
                datasets: [{
                    label: 'Reports',
                    data: <?= $js_issueTrendsData; ?>, // Dynamic data from PHP
                    borderColor: 'var(--primary-color)',
                    backgroundColor: 'rgba(35, 97, 232, 0.2)',
                    borderWidth: 3, // Increased border thickness
                    pointRadius: 5, // Increased point visibility
                    pointBackgroundColor: 'var(--primary-color)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#eee',
                            borderDash: [5, 5]
                        },
                        title: {
                            display: true,
                            text: 'Number of Reports',
                            font: {
                                size: 12
                            }
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: '#eee'
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 10 },
                        padding: 8
                    }
                }
            }
        });
    </script>
</body>
</html>