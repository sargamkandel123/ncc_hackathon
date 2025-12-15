<?php
session_start();
// if(!$_SESSION['islogged_in']){
//     header("location: login.php");
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Aawaz</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4e73df;
            --secondary: #858796;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --dark: #5a5c69;
            --light: #f8f9fc;
            --white: #ffffff;
            --sidebar-bg: #4e73df;
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            outline: none;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
            color: #5a5c69;
            overflow-x: hidden;
        }

        /* Layout Structure */
        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
            transition: all 0.3s;
        }

        .sidebar-brand {
            padding: 20px;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-brand i { margin-right: 10px; }

        .sidebar-menu {
            padding: 20px 0;
            list-style: none;
        }

        .sidebar-menu li {
            padding: 15px 20px;
            cursor: pointer;
            transition: 0.3s;
            border-left: 4px solid transparent;
        }

        .sidebar-menu li:hover, .sidebar-menu li.active {
            background: rgba(255,255,255,0.1);
            border-left: 4px solid #fff;
        }

        .sidebar-menu li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }

        .sidebar-menu li i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-panel {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
        }

        /* Top Header */
        .top-header {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 99;
        }

        .header-title h2 {
            font-size: 1.2rem;
            color: #444;
            font-weight: 600;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e3e6f0;
        }

        /* Content Container */
        .content-container {
            padding: 30px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            border-left: 5px solid transparent;
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.water { border-color: #36b9cc; }
        .stat-card.roads { border-color: #e74a3b; }
        .stat-card.electricity { border-color: #f6c23e; }
        .stat-card.infrastructure { border-color: #4e73df; }
        .stat-card.users-card { border-color: #1cc88a; }
        .stat-card.total-card { border-color: #858796; }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #444;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #888;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stat-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            opacity: 0.1;
        }

        /* Section Layout */
        .dashboard-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            padding: 25px;
        }

        .card-header {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 1.1rem;
            color: #4e73df;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* Buttons Grid */
        .category-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .category-btn {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: #fff;
            border: 2px solid #f3f4f6;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .category-btn:hover {
            border-color: #4e73df;
            background: #f8f9fc;
        }

        .category-btn span:first-child {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
        }
        
        .category-btn span:last-child {
            font-size: 0.85rem;
            color: #888;
        }

        /* Quick Stats List */
        .quick-stat-item {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .quick-stat-item:last-child { border-bottom: none; }

        .quick-stat-item span { color: #666; }
        .quick-stat-item strong { color: #333; }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            text-align: left;
            padding: 15px;
            background-color: #f8f9fc;
            color: #4e73df;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 2px solid #e3e6f0;
        }

        .users-table td {
            padding: 15px;
            border-bottom: 1px solid #e3e6f0;
            font-size: 0.9rem;
            color: #555;
            vertical-align: middle;
        }

        .users-table tr:hover {
            background-color: #fafafa;
        }

        /* Badges */
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-admin { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }
        .badge-user { background: rgba(78, 115, 223, 0.1); color: #4e73df; }
        .badge-active { background: rgba(28, 200, 138, 0.1); color: #1cc88a; }
        .badge-inactive { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }

        /* Actions */
        .btn-action {
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: 0.2s;
            margin-right: 5px;
            font-family: 'Poppins', sans-serif;
        }

        /* Specific styling for the generated buttons */
        /* Delete button generic style */
        .delete-btn {
            background: #e74a3b;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: 0.2s;
        }
        .delete-btn:hover { background: #c0392b; }

        /* Since PHP generates an <a> wrapping the Accept KYC button using the same class, 
           we use CSS specificity to style the 'Accept' button green */
        td a .delete-btn {
            background: #1cc88a !important;
        }
        td a .delete-btn:hover {
            background: #17a673 !important;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 70%;
            max-width: 800px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: slideDown 0.3s ease-out;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #e3e6f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 { font-size: 1.25rem; color: #333; }
        
        .modal-body {
            padding: 30px;
            overflow-y: auto;
        }

        .close {
            font-size: 24px;
            color: #aaa;
            cursor: pointer;
            transition: 0.2s;
        }
        .close:hover { color: #333; }

        /* Problem Items inside Modal */
        .problem-item {
            background: white;
            border: 1px solid #e3e6f0;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            border-left: 4px solid #4e73df;
        }

        .problem-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .problem-header h4 { font-size: 1.1rem; color: #333; }

        .problem-stats {
            margin-top: 15px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            font-size: 0.85rem;
            color: #858796;
        }
        
        .problem-stats span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); width: 0; }
            .main-panel { margin-left: 0; }
            .content-container { padding: 15px; }
            .dashboard-sections { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php
include 'config.php';
    // Get problem counts by category
    $categoryStats = [];
    $categories = ['Water', 'Roads', 'Electricity', 'Infrastructure'];
    
    foreach ($categories as $category) {
        $sql = "SELECT COUNT(*) as count FROM problem_posts WHERE category = '$category'";
        $result = $conn->query($sql);
        $categoryStats[$category] = $result->fetch_assoc()['count'];
    }

    // Get total counts
    $totalProblems = array_sum($categoryStats);
    $totalUsersResult = $conn->query("SELECT COUNT(*) as count FROM users");
    $totalUsers = $totalUsersResult->fetch_assoc()['count'];

    // Get active and resolved problems
    $activeProblemsResult = $conn->query("SELECT COUNT(*) as count FROM problem_posts WHERE status = 'active'");
    $activeProblems = $activeProblemsResult->fetch_assoc()['count'];

    $resolvedProblemsResult = $conn->query("SELECT COUNT(*) as count FROM problem_posts WHERE status != 'active'");
    $resolvedProblems = $resolvedProblemsResult->fetch_assoc()['count'];

    // Get average likes
    $avgLikesResult = $conn->query("SELECT AVG(likes_count) as avg_likes FROM problem_posts WHERE likes_count > 0");
    $avgLikes = $avgLikesResult->fetch_assoc()['avg_likes'] ?: 0;

    // Get most active location
    $activeLocationResult = $conn->query("SELECT location_name, COUNT(*) as count FROM problem_posts GROUP BY location_name ORDER BY count DESC LIMIT 1");
    $activeLocation = $activeLocationResult->fetch_assoc()['location_name'] ?: 'N/A';

    // Get all users
    $usersResult = $conn->query("SELECT id, first_name, last_name, email, user_type, created_at, status FROM users WHERE user_type != 'admin' ORDER BY created_at DESC");
    $users = [];
    while ($row = $usersResult->fetch_assoc()) {
        $users[] = $row;
    }
    ?>

    <div class="wrapper">
        <nav class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-bullhorn"></i> Aawaz
            </div>
            <ul class="sidebar-menu">
                <li class="active"><a href="#"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="user_management.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <div class="main-panel">
            <header class="top-header">
                <div class="header-title">
                    <h2>Overview</h2>
                </div>
                <div class="user-profile">
                    <span>Admin</span>
                    <img src="https://ui-avatars.com/api/?name=Admin+User&background=4e73df&color=fff" alt="Admin">
                </div>
            </header>

            <div class="content-container">
                <div class="stats-grid">
                    <div class="stat-card water">
                        <h3><?php echo $categoryStats['Water']; ?></h3>
                        <p>Water Issues</p>
                        <i class="fas fa-tint stat-icon water"></i>
                    </div>
                    <div class="stat-card roads">
                        <h3><?php echo $categoryStats['Roads']; ?></h3>
                        <p>Road Issues</p>
                        <i class="fas fa-road stat-icon roads"></i>
                    </div>
                    <div class="stat-card electricity">
                        <h3><?php echo $categoryStats['Electricity']; ?></h3>
                        <p>Electricity</p>
                        <i class="fas fa-bolt stat-icon electricity"></i>
                    </div>
                    <div class="stat-card infrastructure">
                        <h3><?php echo $categoryStats['Infrastructure']; ?></h3>
                        <p>Infrastructure</p>
                        <i class="fas fa-building stat-icon infrastructure"></i>
                    </div>
                    <div class="stat-card users-card">
                        <h3><?php echo $totalUsers; ?></h3>
                        <p>Total Users</p>
                        <i class="fas fa-users stat-icon users"></i>
                    </div>
                    <div class="stat-card total-card">
                        <h3><?php echo $totalProblems; ?></h3>
                        <p>Total Reports</p>
                        <i class="fas fa-chart-bar stat-icon total"></i>
                    </div>
                </div>

                <div class="dashboard-sections">
                    <div class="card">
                        <div class="card-header">
                            <h2>Department Details</h2>
                        </div>
                        <div class="category-grid">
                            <button class="category-btn" onclick="openModal('Water')">
                                <span><i class="fas fa-tint" style="color:#36b9cc;"></i> Water</span>
                                <span><?php echo $categoryStats['Water']; ?> Issues</span>
                            </button>
                            <button class="category-btn" onclick="openModal('Roads')">
                                <span><i class="fas fa-road" style="color:#e74a3b;"></i> Roads</span>
                                <span><?php echo $categoryStats['Roads']; ?> Issues</span>
                            </button>
                            <button class="category-btn" onclick="openModal('Electricity')">
                                <span><i class="fas fa-bolt" style="color:#f6c23e;"></i> Electricity</span>
                                <span><?php echo $categoryStats['Electricity']; ?> Issues</span>
                            </button>
                            <button class="category-btn" onclick="openModal('Infrastructure')">
                                <span><i class="fas fa-building" style="color:#4e73df;"></i> Infrastructure</span>
                                <span><?php echo $categoryStats['Infrastructure']; ?> Issues</span>
                            </button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2>System Analytics</h2>
                        </div>
                        <div class="quick-stats-list">
                            <div class="quick-stat-item">
                                <span><i class="fas fa-clock"></i> Active Problems</span>
                                <strong><?php echo $activeProblems; ?></strong>
                            </div>
                            <div class="quick-stat-item">
                                <span><i class="fas fa-check-circle"></i> Resolved Problems</span>
                                <strong><?php echo $resolvedProblems; ?></strong>
                            </div>
                            <div class="quick-stat-item">
                                <span><i class="fas fa-thumbs-up"></i> Avg. Likes / Post</span>
                                <strong><?php echo number_format($avgLikes, 1); ?></strong>
                            </div>
                            <div class="quick-stat-item">
                                <span><i class="fas fa-map-marker-alt"></i> Hotspot Location</span>
                                <strong><?php echo $activeLocation; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div id="problemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Department Problems</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalContent" class="modal-body">
                </div>
        </div>
    </div>

    <script>
        // Open modal for category problems
        function openModal(category) {
            const modal = document.getElementById('problemModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalContent = document.getElementById('modalContent');

            modalTitle.innerHTML = `<i class="fas fa-folder-open"></i> ${category} Department Problems`;
            modalContent.innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading data...</p></div>';
            modal.style.display = 'block';

            // Fetch problems for this category via AJAX
            fetch('get_problems.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'category=' + encodeURIComponent(category)
            })
            .then(response => response.json())
            .then(data => {
                let content = '';
                
                if (data.problems.length === 0) {
                    content = '<div style="text-align:center; padding:30px; color:#888;"><i class="fas fa-check-circle fa-3x" style="color:#1cc88a; margin-bottom:10px;"></i><p>No problems reported in this category.</p></div>';
                } else {
                    data.problems.forEach(problem => {
                        content += `
                            <div class="problem-item">
                                <div class="problem-header">
                                    <h4>${problem.title}</h4>
                                    <span style="background: ${getPriorityColor(problem.priority)}; color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; text-transform:uppercase; font-weight:600;">${problem.priority}</span>
                                </div>
                                <p style="color:#555; line-height:1.6;">${problem.description}</p>
                                <div class="problem-stats">
                                    <span><i class="fas fa-map-marker-alt"></i> ${problem.location_name}</span>
                                    <span><i class="fas fa-eye"></i> ${problem.views_count}</span>
                                    <span><i class="fas fa-thumbs-up"></i> ${problem.likes_count}</span>
                                    <span><i class="fas fa-comment"></i> ${problem.comments_count}</span>
                                    <span><i class="fas fa-calendar-alt"></i> ${problem.created_at}</span>
                                    <span><i class="fas fa-user"></i> ${problem.user_name}</span>
                                    <span><i class="fas fa-info-circle"></i> ${problem.status}</span>
                                </div>
                            </div>
                        `;
                    });
                }
                
                modalContent.innerHTML = content;
            })
            .catch(error => {
                modalContent.innerHTML = '<p style="color:red; text-align:center;">Error loading problems. Please try again.</p>';
                console.error('Error:', error);
            });
        }

        // Close modal
        function closeModal() {
            document.getElementById('problemModal').style.display = 'none';
        }

        // Delete user function
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                fetch('delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'user_id=' + userId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error deleting user: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error deleting user. Please try again.');
                    console.error('Error:', error);
                });
            }
        }

        // Helper function for priority colors
        function getPriorityColor(priority) {
            switch (priority.toLowerCase()) {
                case 'high': return '#e74a3b'; // Red
                case 'medium': return '#f6c23e'; // Yellow
                case 'low': return '#1cc88a'; // Green
                default: return '#858796'; // Grey
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('problemModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>