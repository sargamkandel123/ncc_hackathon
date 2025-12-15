<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in'])) {
   header('Location: login.php');
   exit();
}
if($_SESSION['user_type'] == "normal"){
    header('location: index.php');
}

$admin_id = $_SESSION['user_id'];
$sqlD = "SELECT * FROM users WHERE id = $admin_id";
$resD = mysqli_query($conn, $sqlD);
$f = mysqli_fetch_assoc($resD);
$admin_department = $f['admin_con'];

if($_SESSION['user_type'] == 'admin' && $admin_department == 'all'){
    header('location: dashboard.php');
}

if ($_POST && isset($_POST['add_trusted_user'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $count_sql = "SELECT COUNT(*) as count FROM users WHERE admin_con = '$admin_department' AND trust_member = 'yes'";
    $count_result = mysqli_query($conn, $count_sql);
    $count = mysqli_fetch_assoc($count_result)['count'];
    
    if ($count >= 5) {
        $error_message = "Maximum of 5 trusted users allowed per department!";
    } else {
        $user_check_sql = "SELECT * FROM users WHERE id = '$user_id' AND user_type = 'normal'";
        $user_check_result = mysqli_query($conn, $user_check_sql);
        
        if (mysqli_num_rows($user_check_result) > 0) {
            $user = mysqli_fetch_assoc($user_check_result);
            if ($user['trust_member'] == 'yes') {
                $error_message = "User is already a trusted member!";
            } else {
                $update_sql = "UPDATE users SET trust_member = 'yes' WHERE id = '$user_id'";
                if (mysqli_query($conn, $update_sql)) {
                    $success_message = "User added as trusted member successfully!";
                } else {
                    $error_message = "Error adding trusted user: " . mysqli_error($conn);
                }
            }
        } else {
            $error_message = "User not found or invalid!";
        }
    }
}

if ($_POST && isset($_POST['remove_trusted_user'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['remove_user_id']);
    $remove_sql = "UPDATE users SET trust_member = 'no' WHERE id = '$user_id' AND admin_con = '$admin_department'";
    if (mysqli_query($conn, $remove_sql)) {
        $success_message = "Trusted user removed successfully!";
    } else {
        $error_message = "Error removing trusted user: " . mysqli_error($conn);
    }
}

if ($_POST && isset($_POST['verify_kyc'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $verify_sql = "UPDATE users SET verified = 'yes' WHERE id = '$user_id' AND admin_con = '$admin_department'";
    if (mysqli_query($conn, $verify_sql)) {
        $success_message = "User KYC verified successfully!";
    } else {
        $error_message = "Error verifying KYC: " . mysqli_error($conn);
    }
}

if ($_POST && isset($_POST['create_notice'])) {
    $title = mysqli_real_escape_string($conn, $_POST['notice_title']);
    $category = mysqli_real_escape_string($conn, $_POST['notice_category']);
    $priority = mysqli_real_escape_string($conn, $_POST['notice_priority']);
    $content = mysqli_real_escape_string($conn, $_POST['notice_content']);
    
    if (!empty($title) && !empty($content)) {
        $insert_notice_sql = "INSERT INTO notices (title, category, priority, content, admin_id, status, depart, created_at) 
                             VALUES ('$title', '$category', '$priority', '$content', '$admin_id', 'active', '$admin_department', NOW())";
        if (mysqli_query($conn, $insert_notice_sql)) {
            $success_message = "Notice created successfully!";
        } else {
            $error_message = "Error creating notice: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Title and content are required!";
    }
}

if ($_POST && isset($_POST['update_notice_status'])) {
    $notice_id = mysqli_real_escape_string($conn, $_POST['notice_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['notice_status']);
    $update_notice_sql = "UPDATE notices SET status = '$new_status', updated_at = NOW() WHERE id = '$notice_id' AND admin_id = '$admin_id'";
    if (mysqli_query($conn, $update_notice_sql)) {
        $success_message = "Notice status updated successfully!";
    } else {
        $error_message = "Error updating notice status: " . mysqli_error($conn);
    }
}

if ($_POST && isset($_POST['update_status'])) {
    $problem_id = mysqli_real_escape_string($conn, $_POST['problem_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $completion_message = isset($_POST['completion_message']) ? mysqli_real_escape_string($conn, $_POST['completion_message']) : '';
    if (empty($new_status)) {
        $error_message = "Status cannot be empty!";
    } else {
        $update_sql = "UPDATE problem_posts SET 
                       status = '$new_status',
                       completion_message = '$completion_message',
                       updated_at = NOW()
                       WHERE id = '$problem_id'";
        if (mysqli_query($conn, $update_sql)) {
            $success_message = "Problem status updated successfully to: " . $new_status;
        } else {
            $error_message = "Error updating status: " . mysqli_error($conn);
        }
    }
}

$category_filter = $admin_department !== 'general_admin' ? "WHERE category = '$admin_department'" : "";
$stats_sql = "SELECT 
               COUNT(*) as total_problems,
               SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as pending_problems,
               SUM(CASE WHEN status = 'working' THEN 1 ELSE 0 END) as working_problems,
               SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_problems
             FROM problem_posts $category_filter";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$problems_sql = "SELECT p.*, u.first_name, u.last_name 
                FROM problem_posts p 
                LEFT JOIN users u ON p.user_id = u.id 
                $category_filter 
                ORDER BY p.created_at DESC 
                LIMIT $limit OFFSET $offset";
$problems_result = mysqli_query($conn, $problems_sql);

$count_sql = "SELECT COUNT(*) as total FROM problem_posts $category_filter";
$count_result = mysqli_query($conn, $count_sql);
$total_problems = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_problems / $limit);

$notices_sql = "SELECT * FROM notices WHERE admin_id = '$admin_id' ORDER BY created_at DESC";
$notices_result = mysqli_query($conn, $notices_sql);

$admin_sql = "SELECT first_name, last_name FROM users WHERE id = $admin_id";
$admin_result = mysqli_query($conn, $admin_sql);
$admin_info = mysqli_fetch_assoc($admin_result);

$notice_stats_sql = "SELECT 
                     COUNT(*) as total_notices,
                     SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_notices,
                     SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_notices
                     FROM notices WHERE admin_id = '$admin_id'";
$notice_stats_result = mysqli_query($conn, $notice_stats_sql);
$notice_stats = mysqli_fetch_assoc($notice_stats_result);

$unverified_users_sql = "SELECT id, first_name, last_name, email, phone, created_at, image, kyc_front, kyc_back 
                        FROM users 
                        WHERE admin_con = '$admin_department' AND verified = 'no' AND user_type = 'normal'
                        ORDER BY first_name ASC";
$unverified_users_result = mysqli_query($conn, $unverified_users_sql);

$trusted_users_sql = "SELECT id, first_name, last_name, email, phone, created_at 
                      FROM users 
                      WHERE admin_con = '$admin_department' AND trust_member = 'yes' AND user_type = 'normal'
                      ORDER BY first_name ASC";
$trusted_users_result = mysqli_query($conn, $trusted_users_sql);

$available_users_sql = "SELECT id, first_name, last_name, email 
                        FROM users 
                        WHERE user_type = 'normal' AND trust_member = 'no' AND verified = 'yes'
                        ORDER BY first_name ASC";
$available_users_result = mysqli_query($conn, $available_users_sql);

$trusted_count_sql = "SELECT COUNT(*) as count FROM users WHERE admin_con = '$admin_department' AND trust_member = 'yes'";
$trusted_count_result = mysqli_query($conn, $trusted_count_sql);
$trusted_count = mysqli_fetch_assoc($trusted_count_result)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Admin Dashboard - <?php echo ucfirst(str_replace('_', ' ', $admin_department)); ?></title>
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
      <style>
       * {
           margin: 0;
           padding: 0;
           box-sizing: border-box;
       }

       body {
           font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
           background: #f8fafc;
           color: #334155;
       }

        .navbar {
        background: white;
        border-bottom: 1px solid #e2e8f0;
        padding: 0 2rem;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .navbar-brand {
        font-size: 1.5rem;
        font-weight: bold;
        color: #4f46e5;
    }

    .navbar-nav {
        display: flex;
        list-style: none;
        gap: 2rem;
        align-items: center;
    }

    .nav-link {
        text-decoration: none;
        color: #64748b;
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .nav-link:hover {
        color: #334155;
        background-color: #f1f5f9;
    }

    .nav-link.active {
        color: #4f46e5;
        background-color: #eef2ff;
    }

       .admin-info {
           display: flex;
           align-items: center;
           gap: 1rem;
       }

       .admin-info span {
           opacity: 0.9;
       }

       .logout-btn {
           background: rgba(255,255,255,0.2);
           border: none;
           color: white;
           padding: 0.5rem 1rem;
           border-radius: 6px;
           cursor: pointer;
           transition: all 0.2s;
           text-decoration: none;
       }

       .logout-btn:hover {
           background: rgba(255,255,255,0.3);
       }

       .container {
           max-width: 1200px;
           margin: 2rem auto;
           padding: 0 1rem;
       }

       .page-header {
           background: white;
           padding: 2rem;
           border-radius: 12px;
           box-shadow: 0 2px 10px rgba(0,0,0,0.1);
           margin-bottom: 2rem;
       }

       .page-title {
           font-size: 2rem;
           font-weight: bold;
           color: #1e293b;
           margin-bottom: 0.5rem;
       }

       .page-subtitle {
           color: #64748b;
           font-size: 1.1rem;
       }

       /* Tab System */
       .tab-container {
           background: white;
           border-radius: 12px;
           box-shadow: 0 2px 10px rgba(0,0,0,0.1);
           overflow: hidden;
           margin-bottom: 2rem;
       }

       .tab-nav {
           display: flex;
           background: #f8fafc;
           border-bottom: 1px solid #e2e8f0;
       }

       .tab-btn {
           flex: 1;
           padding: 1rem 2rem;
           background: none;
           border: none;
           cursor: pointer;
           font-size: 1rem;
           font-weight: 600;
           color: #64748b;
           transition: all 0.2s;
           display: flex;
           align-items: center;
           justify-content: center;
           gap: 0.5rem;
       }

       .tab-btn.active {
           background: white;
           color: #3b82f6;
           border-bottom: 2px solid #3b82f6;
       }

       .tab-btn:hover:not(.active) {
           background: #f1f5f9;
           color: #1e293b;
       }

       .tab-content {
           display: none;
           padding: 2rem;
       }

       .tab-content.active {
           display: block;
       }

       .stats-grid {
           display: grid;
           grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
           gap: 1.5rem;
           margin-bottom: 2rem;
       }

       .stat-card {
           background: white;
           padding: 1.5rem;
           border-radius: 12px;
           box-shadow: 0 2px 10px rgba(0,0,0,0.1);
           text-align: center;
           transition: transform 0.2s;
       }

       .stat-card:hover {
           transform: translateY(-2px);
       }

       .stat-icon {
           width: 50px;
           height: 50px;
           margin: 0 auto 1rem;
           border-radius: 50%;
           display: flex;
           align-items: center;
           justify-content: center;
           font-size: 1.25rem;
           color: white;
       }

       .stat-icon.total { background: #3b82f6; }
       .stat-icon.pending { background: #f59e0b; }
       .stat-icon.working { background: #8b5cf6; }
       .stat-icon.completed { background: #10b981; }
       .stat-icon.active { background: #10b981; }
       .stat-icon.inactive { background: #ef4444; }
       .stat-icon.trusted { background: #06b6d4; }

       .stat-number {
           font-size: 2rem;
           font-weight: bold;
           color: #1e293b;
           margin-bottom: 0.5rem;
       }

       .stat-label {
           color: #64748b;
           font-size: 0.875rem;
           text-transform: uppercase;
           letter-spacing: 0.5px;
       }

       /* Form Styles */
       .form-grid {
           display: grid;
           grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
           gap: 1.5rem;
           margin-bottom: 2rem;
       }

       .form-group {
           margin-bottom: 1.5rem;
       }

       .form-label {
           display: block;
           margin-bottom: 0.5rem;
           font-weight: 600;
           color: #374151;
       }

       .form-input, .form-select, .form-textarea {
           width: 100%;
           padding: 0.75rem;
           border: 1px solid #d1d5db;
           border-radius: 6px;
           font-size: 1rem;
           transition: border-color 0.2s;
       }

       .form-input:focus, .form-select:focus, .form-textarea:focus {
           outline: none;
           border-color: #3b82f6;
           box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
       }

       .form-textarea {
           resize: vertical;
           min-height: 120px;
       }

       /* Tables */
       .table-container {
           background: white;
           border-radius: 12px;
           box-shadow: 0 2px 10px rgba(0,0,0,0.1);
           overflow: hidden;
       }

       .table {
           width: 100%;
           border-collapse: collapse;
       }

       .table th {
           background: #f1f5f9;
           padding: 1rem;
           text-align: left;
           font-weight: 600;
           color: #475569;
           font-size: 0.875rem;
           text-transform: uppercase;
           letter-spacing: 0.5px;
       }

       .table td {
           padding: 1rem;
           border-top: 1px solid #e2e8f0;
           vertical-align: top;
       }

       /* Badges */
       .badge {
           display: inline-block;
           padding: 0.25rem 0.75rem;
           border-radius: 20px;
           font-size: 0.75rem;
           font-weight: 600;
           text-transform: uppercase;
           letter-spacing: 0.5px;
       }

       .badge-water { background: #dbeafe; color: #1d4ed8; }
       .badge-infrastructure { background: #dcfce7; color: #166534; }
       .badge-safety { background: #fee2e2; color: #dc2626; }
       .badge-environmental { background: #fef3c7; color: #d97706; }
       .badge-other { background: #f3e8ff; color: #7c3aed; }

       .badge-active { background: #dcfce7; color: #166534; }
       .badge-inactive { background: #fee2e2; color: #dc2626; }
       .badge-working { background: #ddd6fe; color: #7c3aed; }
       .badge-completed { background: #dcfce7; color: #166534; }
       .badge-rejected { background: #fee2e2; color: #dc2626; }

       .badge-high { background: #fee2e2; color: #dc2626; }
       .badge-medium { background: #fef3c7; color: #d97706; }
       .badge-low { background: #dcfce7; color: #166534; }

       .badge-trusted { background: #e0f2fe; color: #0891b2; }

       /* Buttons */
       .btn {
           padding: 0.5rem 1rem;
           border: none;
           border-radius: 6px;
           cursor: pointer;
           font-size: 0.875rem;
           font-weight: 500;
           transition: all 0.2s;
           text-decoration: none;
           display: inline-flex;
           align-items: center;
           gap: 0.5rem;
       }

       .btn-primary {
           background: #3b82f6;
           color: white;
       }

       .btn-primary:hover {
           background: #2563eb;
       }

       .btn-success {
           background: #10b981;
           color: white;
       }

       .btn-success:hover {
           background: #059669;
       }

       .btn-danger {
           background: #ef4444;
           color: white;
       }

       .btn-danger:hover {
           background: #dc2626;
       }

       .btn-secondary {
           background: #64748b;
           color: white;
       }

       .btn-secondary:hover {
           background: #475569;
       }

       .btn-warning {
           background: #f59e0b;
           color: white;
       }

       .btn-warning:hover {
           background: #d97706;
       }

       /* Modals */
       .modal {
           display: none;
           position: fixed;
           z-index: 1000;
           left: 0;
           top: 0;
           width: 100%;
           height: 100%;
           background-color: rgba(0,0,0,0.5);
           backdrop-filter: blur(5px);
       }

       .modal-content {
           background: white;
           margin: 5% auto;
           padding: 2rem;
           border-radius: 12px;
           width: 90%;
           max-width: 600px;
           box-shadow: 0 20px 40px rgba(0,0,0,0.1);
           max-height: 80vh;
           overflow-y: auto;
       }

       .modal-header {
           display: flex;
           justify-content: space-between;
           align-items: center;
           margin-bottom: 2rem;
           padding-bottom: 1rem;
           border-bottom: 1px solid #e2e8f0;
       }

       .modal-title {
           font-size: 1.5rem;
           font-weight: bold;
           color: #1e293b;
       }

       .close-btn {
           background: none;
           border: none;
           font-size: 1.5rem;
           cursor: pointer;
           color: #64748b;
       }

       .alert {
           padding: 1rem;
           border-radius: 6px;
           margin-bottom: 1rem;
       }

       .alert-success {
           background: #dcfce7;
           color: #166534;
           border: 1px solid #bbf7d0;
       }

       .alert-error {
           background: #fee2e2;
           color: #dc2626;
           border: 1px solid #fecaca;
       }

       .alert-info {
           background: #dbeafe;
           color: #1d4ed8;
           border: 1px solid #bfdbfe;
       }

       .action-buttons {
           display: flex;
           flex-wrap: wrap;
           gap: 0.5rem;
       }

       .trusted-user-card {
           background: #f8fafc;
           border: 1px solid #e2e8f0;
           border-radius: 8px;
           padding: 1rem;
           margin-bottom: 1rem;
           display: flex;
           justify-content: space-between;
           align-items: center;
       }

       .user-info {
           flex: 1;
       }

       .user-name {
           font-weight: 600;
           color: #1e293b;
           margin-bottom: 0.25rem;
       }

       .user-details {
           font-size: 0.875rem;
           color: #64748b;
       }

       @media (max-width: 768px) {
           .container {
               padding: 0 0.5rem;
           }
           
           .tab-nav {
               flex-direction: column;
           }
           
           .stats-grid {
               grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
               gap: 1rem;
           }
           
           .form-grid {
               grid-template-columns: 1fr;
           }

           .trusted-user-card {
               flex-direction: column;
               align-items: flex-start;
               gap: 1rem;
           }
       }
   </style>
</head>
<body>
   <nav class="navbar">
        <div class="navbar-brand"> 📢आवाज</div>
        <ul class="navbar-nav">
            <li><a href="index.php" class="nav-link">🏠 Home</a></li>
            <li><a href="map.php" class="nav-link">📈 Map</a></li>
            <li><a href="notice.php" class="nav-link">🔔 Notices</a></li>
            <li><a href="admin.php" class="nav-link active">⚙️ Admin</a></li>
            <li><a href="campaign.php" class="nav-link">📢 Campaigns</a></li>
        </ul>
    </nav>

   <div class="container">  
       <?php if (isset($success_message)): ?>
           <div class="alert alert-success">
               <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
           </div>
       <?php endif; ?>

       <?php if (isset($error_message)): ?>
           <div class="alert alert-error">
               <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
           </div>
       <?php endif; ?>

       <div class="page-header">
           <h1 class="page-title">Department Dashboard</h1>
           <p class="page-subtitle"><?php echo ucfirst(str_replace('_', ' ', $admin_department)); ?> - Manage problems, notices, and trusted users in your department</p>
       </div>

       <div class="tab-container">
           <div class="tab-nav">
               <button class="tab-btn active" onclick="showTab('dashboard')">
                   <i class="fas fa-tachometer-alt"></i> Dashboard
               </button>
               <button class="tab-btn" onclick="showTab('problems')">
                   <i class="fas fa-exclamation-triangle"></i> Problems
               </button>
               <button class="tab-btn" onclick="showTab('notices')">
                   <i class="fas fa-bullhorn"></i> Notices
               </button>
               <button class="tab-btn" onclick="showTab('trusted-users')">
                   <i class="fas fa-users"></i> Trusted Users
               </button>
           </div>

           <div id="dashboard" class="tab-content active">
               <h3 style="margin-bottom: 1.5rem; color: #1e293b;">
                   <i class="fas fa-chart-bar"></i> Statistics Overview
               </h3>
               <div style="margin-bottom: 2rem;">
                   <h4 style="margin-bottom: 1rem; color: #475569;">Problem Reports</h4>
                   <div class="stats-grid">
                       <div class="stat-card">
                           <div class="stat-icon total">
                               <i class="fas fa-clipboard-list"></i>
                           </div>
                           <div class="stat-number"><?php echo $stats['total_problems']; ?></div>
                           <div class="stat-label">Total Problems</div>
                       </div>
                       <div class="stat-card">
                           <div class="stat-icon pending">
                               <i class="fas fa-clock"></i>
                           </div>
                           <div class="stat-number"><?php echo $stats['pending_problems']; ?></div>
                           <div class="stat-label">Pending</div>
                       </div>
                       <div class="stat-card">
                           <div class="stat-icon working">
                               <i class="fas fa-cogs"></i>
                           </div>
                           <div class="stat-number"><?php echo $stats['working_problems']; ?></div>
                           <div class="stat-label">In Progress</div>
                       </div>
                       <div class="stat-card">
                           <div class="stat-icon completed">
                               <i class="fas fa-check-circle"></i>
                           </div>
                           <div class="stat-number"><?php echo $stats['completed_problems']; ?></div>
                           <div class="stat-label">Completed</div>
                       </div>
                   </div>
               </div>
               <div style="margin-bottom: 2rem;">
                   <h4 style="margin-bottom: 1rem; color: #475569;">Notices</h4>
                   <div class="stats-grid">
                       <div class="stat-card">
                           <div class="stat-icon total">
                               <i class="fas fa-bullhorn"></i>
                           </div>
                           <div class="stat-number"><?php echo $notice_stats['total_notices'] ?? 0; ?></div>
                           <div class="stat-label">Total Notices</div>
                       </div>
                       <div class="stat-card">
                           <div class="stat-icon active">
                               <i class="fas fa-eye"></i>
                           </div>
                           <div class="stat-number"><?php echo $notice_stats['active_notices'] ?? 0; ?></div>
                           <div class="stat-label">Active</div>
                       </div>
                       <div class="stat-card">
                           <div class="stat-icon inactive">
                               <i class="fas fa-eye-slash"></i>
                           </div>
                           <div class="stat-number"><?php echo $notice_stats['inactive_notices'] ?? 0; ?></div>
                           <div class="stat-label">Inactive</div>
                       </div>
                       <div class="stat-card">
                           <div class="stat-icon trusted">
                               <i class="fas fa-user-check"></i>
                           </div>
                           <div class="stat-number"><?php echo $trusted_count; ?></div>
                           <div class="stat-label">Trusted Users</div>
                       </div>
                   </div>
               </div>
           </div>

           <div id="problems" class="tab-content">
               <div class="table-container">
                   <table class="table">
                       <thead>
                           <tr>
                               <th>Problem Details</th>
                               <th>Category</th>
                               <th>Reporter</th>
                               <th>Location</th>
                               <th>Status</th>
                               <th>Date</th>
                               <th>Actions</th>
                           </tr>
                       </thead>
                       <tbody>
                           <?php 
                           mysqli_data_seek($problems_result, 0);
                           while ($problem = mysqli_fetch_assoc($problems_result)): 
                           ?>
                           <tr>
                               <td>
                                   <div style="font-weight: 600; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($problem['title']); ?></div>
                                   <div style="color: #64748b; font-size: 0.875rem;">
                                       <?php echo htmlspecialchars(substr($problem['description'], 0, 100)) . (strlen($problem['description']) > 100 ? '...' : ''); ?>
                                   </div>
                                   <?php if ($problem['completion_message']): ?>
                                   <div style="margin-top: 0.5rem; padding: 0.5rem; background: #f0f9ff; border-radius: 4px; font-size: 0.875rem;">
                                       <strong>Completion Note:</strong> <?php echo htmlspecialchars($problem['completion_message']); ?>
                                   </div>
                                   <?php endif; ?>
                               </td>
                               <td>
                                   <span class="badge badge-<?php echo strtolower($problem['category'] ?? 'other'); ?>">
                                       <?php echo htmlspecialchars($problem['category'] ?? 'Other'); ?>
                                   </span>
                               </td>
                               <td>
                                   <?php echo htmlspecialchars(($problem['first_name'] ?? 'Unknown') . ' ' . ($problem['last_name'] ?? 'User')); ?>
                               </td>
                               <td><?php echo htmlspecialchars($problem['location_name'] ?? 'N/A'); ?></td>
                               <td>
                                   <span class="badge badge-<?php echo strtolower($problem['status']); ?>">
                                       <?php echo htmlspecialchars(ucfirst($problem['status'])); ?>
                                   </span>
                               </td>
                               <td><?php echo date('M j, Y', strtotime($problem['created_at'])); ?></td>
                               <td>
                                   <div class="action-buttons">
                                       <button class="btn btn-primary" onclick="openStatusModal(<?php echo $problem['id']; ?>, '<?php echo $problem['status']; ?>', '<?php echo htmlspecialchars($problem['title'], ENT_QUOTES); ?>')">
                                           <i class="fas fa-edit"></i> Update
                                       </button>
                                       <?php if ($problem['photo_url']): ?>
                                       <a href="Uploads/<?php echo $problem['photo_url']; ?>" target="_blank" class="btn btn-secondary">
                                           <i class="fas fa-image"></i> View
                                       </a>
                                       <a href="map.php?lng=<?php echo $problem['longitude']; ?>&lat=<?php echo $problem['latitude']; ?>" target="_blank" class="btn btn-secondary">
                                           <i class="fas fa-map-marker-alt"></i> Location
                                       </a>
                                       <?php endif; ?>
                                   </div>
                               </td>
                           </tr>
                           <?php endwhile; ?>
                       </tbody>
                   </table>
               </div>
           </div>

           <div id="notices" class="tab-content">
               <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem;">
                   <h3 style="margin-bottom: 1.5rem; color: #1e293b;">
                       <i class="fas fa-plus-circle"></i> Create New Notice
                   </h3>
                   <form method="POST" action="">
                       <div class="form-grid">
                           <div class="form-group">
                               <label for="notice_title" class="form-label">Notice Title</label>
                               <input type="text" id="notice_title" name="notice_title" class="form-input" required>
                           </div>
                           <div class="form-group">
                               <label for="notice_category" class="form-label">Category</label>
                               <select id="notice_category" name="notice_category" class="form-select" required>
                                   <option value="<?php echo $admin_department; ?>"><?php echo ucfirst(str_replace('_', ' ', $admin_department)); ?></option>
                               </select>
                           </div>
                           <div class="form-group">
                               <label for="notice_priority" class="form-label">Priority</label>
                               <select id="notice_priority" name="notice_priority" class="form-select" required>
                                   <option value="low">Low</option>
                                   <option value="medium" selected>Medium</option>
                                   <option value="high">High</option>
                               </select>
                           </div>
                       </div>
                       <div class="form-group">
                           <label for="notice_content" class="form-label">Notice Content</label>
                           <textarea id="notice_content" name="notice_content" class="form-textarea" rows="4" required placeholder="Enter the detailed content of the notice..."></textarea>
                       </div>
                       <button type="submit" name="create_notice" class="btn btn-primary">
                           <i class="fas fa-bullhorn"></i> Create Notice
                       </button>
                   </form>
               </div>
               <div class="table-container">
                   <div style="background: #f8fafc; padding: 1.5rem 2rem; border-bottom: 1px solid #e2e8f0;">
                       <h3 style="color: #1e293b; margin: 0;">
                           <i class="fas fa-list"></i> Posted Notices
                       </h3>
                   </div>
                   <table class="table">
                       <thead>
                           <tr>
                               <th>Title</th>
                               <th>Category</th>
                               <th>Priority</th>
                               <th>Status</th>
                               <th>Views</th>
                               <th>Created</th>
                               <th>Actions</th>
                           </tr>
                       </thead>
                       <tbody>
                           <?php if (mysqli_num_rows($notices_result) > 0): ?>
                               <?php while ($notice = mysqli_fetch_assoc($notices_result)): ?>
                               <tr>
                                   <td>
                                       <div style="font-weight: 600; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($notice['title']); ?></div>
                                       <div style="color: #64748b; font-size: 0.875rem;">
                                           <?php echo htmlspecialchars(substr($notice['content'], 0, 80)) . (strlen($notice['content']) > 80 ? '...' : ''); ?>
                                       </div>
                                   </td>
                                   <td>
                                       <span class="badge badge-<?php echo strtolower($notice['category'] ?? 'other'); ?>">
                                           <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $notice['category']))); ?>
                                       </span>
                                   </td>
                                   <td>
                                       <span class="badge badge-<?php echo strtolower($notice['priority']); ?>">
                                           <?php echo htmlspecialchars(ucfirst($notice['priority'])); ?>
                                       </span>
                                   </td>
                                   <td>
                                       <span class="badge badge-<?php echo strtolower($notice['status']); ?>">
                                           <?php echo htmlspecialchars(ucfirst($notice['status'])); ?>
                                       </span>
                                   </td>
                                   <td><?php echo $notice['views'] ?? 0; ?></td>
                                   <td><?php echo date('M j, Y', strtotime($notice['created_at'])); ?></td>
                                   <td>
                                       <div class="action-buttons">
                                           <button class="btn btn-primary" onclick="viewNoticeDetails(<?php echo $notice['id']; ?>, '<?php echo htmlspecialchars($notice['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($notice['content'], ENT_QUOTES); ?>', '<?php echo $notice['priority']; ?>', '<?php echo $notice['status']; ?>')">
                                               <i class="fas fa-eye"></i> View
                                           </button>
                                           <button class="btn btn-secondary" onclick="openNoticeStatusModal(<?php echo $notice['id']; ?>, '<?php echo $notice['status']; ?>', '<?php echo htmlspecialchars($notice['title'], ENT_QUOTES); ?>')">
                                               <i class="fas fa-edit"></i> Status
                                           </button>
                                       </div>
                                   </td>
                               </tr>
                               <?php endwhile; ?>
                           <?php else: ?>
                               <tr>
                                   <td colspan="7" style="text-align: center; padding: 2rem; color: #64748b;">
                                       <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                       No notices posted yet. Create your first notice above.
                                   </td>
                               </tr>
                           <?php endif; ?>
                       </tbody>
                   </table>
               </div>
           </div>

           <div id="trusted-users" class="tab-content">
               <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem;">
                   <h3 style="margin-bottom: 1.5rem; color: #1e293b;">
                       <i class="fas fa-user-plus"></i> Add Trusted User
                   </h3>
                   <div class="alert alert-info">
                       <i class="fas fa-info-circle"></i> 
                       You can add up to 5 trusted users for your department. Currently you have <strong><?php echo $trusted_count; ?>/5</strong> trusted users.
                   </div>
                   <?php if ($trusted_count < 5): ?>
                   <form method="POST" action="">
                       <div class="form-group">
                           <label for="user_id" class="form-label">Select User to Add as Trusted Member</label>
                           <select id="user_id" name="user_id" class="form-select" required>
                               <option value="">-- Select User --</option>
                               <?php while ($user = mysqli_fetch_assoc($available_users_result)): ?>
                               <option value="<?php echo $user['id']; ?>">
                                   <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')'); ?>
                               </option>
                               <?php endwhile; ?>
                           </select>
                       </div>
                       <button type="submit" name="add_trusted_user" class="btn btn-success">
                           <i class="fas fa-user-check"></i> Add as Trusted User
                       </button>
                   </form>
                   <?php else: ?>
                   <div class="alert alert-info">
                       <i class="fas fa-exclamation-circle"></i> 
                       You have reached the maximum limit of 5 trusted users. Remove a user to add a new one.
                   </div>
                   <?php endif; ?>
               </div>

               <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                   <h3 style="margin-bottom: 1.5rem; color: #1e293b;">
                       <i class="fas fa-users"></i> Unverified Users
                   </h3>
                   <?php if (mysqli_num_rows($unverified_users_result) > 0): ?>
                       <?php while ($unverified_user = mysqli_fetch_assoc($unverified_users_result)): ?>
                       <div class="trusted-user-card">
                           <div class="user-info">
                               <div class="user-name">
                                   <i class="fas fa-user" style="color: #64748b; margin-right: 0.5rem;"></i>
                                   <?php echo htmlspecialchars($unverified_user['first_name'] . ' ' . $unverified_user['last_name']); ?>
                               </div>
                               <div class="user-details">
                                   <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($unverified_user['email']); ?>
                                   <?php if ($unverified_user['phone']): ?>
                                   | <i class="fas fa-phone"></i> <?php echo htmlspecialchars($unverified_user['phone']); ?>
                                   <?php endif; ?>
                                   | <i class="fas fa-calendar"></i> Registered: <?php echo date('M j, Y', strtotime($unverified_user['created_at'])); ?>
                               </div>
                           </div>
                           <div class="action-buttons">
                               <button class="btn btn-primary" onclick="openKycModal(<?php echo $unverified_user['id']; ?>, '<?php echo htmlspecialchars($unverified_user['first_name'] . ' ' . $unverified_user['last_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($unverified_user['image'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($unverified_user['kyc_front'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($unverified_user['kyc_back'], ENT_QUOTES); ?>')">
                                   <i class="fas fa-id-card"></i> Check KYC
                               </button>
                           </div>
                       </div>
                       <?php endwhile; ?>
                   <?php else: ?>
                       <div style="text-align: center; padding: 2rem; color: #64748b;">
                           <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                           <h4 style="margin-bottom: 0.5rem;">No Unverified Users</h4>
                           <p>All users in your department are verified.</p>
                       </div>
                   <?php endif; ?>
               </div>
               <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 2rem;">
                   <h3 style="margin-bottom: 1.5rem; color: #1e293b;">
                       <i class="fas fa-user-check"></i> Current Trusted Users (<?php echo $trusted_count; ?>/5)
                   </h3>
                   <?php if (mysqli_num_rows($trusted_users_result) > 0): ?>
                       <?php while ($trusted_user = mysqli_fetch_assoc($trusted_users_result)): ?>
                       <div class="trusted-user-card">
                           <div class="user-info">
                               <div class="user-name">
                                   <i class="fas fa-user-check" style="color: #10b981; margin-right: 0.5rem;"></i>
                                   <?php echo htmlspecialchars($trusted_user['first_name'] . ' ' . $trusted_user['last_name']); ?>
                               </div>
                               <div class="user-details">
                                   <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($trusted_user['email']); ?>
                                   <?php if ($trusted_user['phone']): ?>
                                   | <i class="fas fa-phone"></i> <?php echo htmlspecialchars($trusted_user['phone']); ?>
                                   <?php endif; ?>
                                   | <i class="fas fa-calendar"></i> Added: <?php echo date('M j, Y', strtotime($trusted_user['created_at'])); ?>
                               </div>
                           </div>
                           <div class="action-buttons">
                               <button class="btn btn-danger" onclick="removeTrustedUser(<?php echo $trusted_user['id']; ?>, '<?php echo htmlspecialchars($trusted_user['first_name'] . ' ' . $trusted_user['last_name'], ENT_QUOTES); ?>')">
                                   <i class="fas fa-user-times"></i> Remove
                               </button>
                           </div>
                       </div>
                       <?php endwhile; ?>
                   <?php else: ?>
                       <div style="text-align: center; padding: 2rem; color: #64748b;">
                           <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                           <h4 style="margin-bottom: 0.5rem;">No Trusted Users Yet</h4>
                           <p>Add trusted users to help manage your department's activities.</p>
                       </div>
                   <?php endif; ?>
               </div>
           </div>
       </div>
   </div>

   <div id="statusModal" class="modal">
       <div class="modal-content">
           <div class="modal-header">
               <h2 class="modal-title">Update Problem Status</h2>
               <button class="close-btn" onclick="closeStatusModal()">&times;</button>
           </div>
           <form method="POST" action="">
               <input type="hidden" id="problemId" name="problem_id" value="">
               <div class="form-group">
                   <label class="form-label">Problem Title:</label>
                   <p id="problemTitle" style="font-weight: 500; color: #374151; background: #f9fafb; padding: 0.75rem; border-radius: 6px;"></p>
               </div>
               <div class="form-group">
                   <label for="status" class="form-label">New Status:</label>
                   <select id="status" name="status" class="form-select" required onchange="toggleCompletionMessage()">
                       <option value="active">Active (Pending)</option>
                       <option value="working">Working (In Progress)</option>
                       <option value="completed">Completed</option>
                       <option value="rejected">Rejected</option>
                   </select>
               </div>
               <div class="form-group" id="completionMessageGroup" style="display: none;">
                   <label for="completion_message" class="form-label">Completion Message:</label>
                   <textarea id="completion_message" name="completion_message" class="form-textarea" rows="3" placeholder="Please provide details about how this problem was resolved..."></textarea>
                   <small style="color: #64748b; font-size: 0.875rem;">This message will be visible to users and other admins.</small>
               </div>
               <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                   <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                   <button type="submit" name="update_status" class="btn btn-success">
                       <i class="fas fa-save"></i> Update Status
                   </button>
               </div>
           </form>
       </div>
   </div>

   <div id="noticeStatusModal" class="modal">
       <div class="modal-content">
           <div class="modal-header">
               <h2 class="modal-title">Update Notice Status</h2>
               <button class="close-btn" onclick="closeNoticeStatusModal()">&times;</button>
           </div>
           <form method="POST" action="">
               <input type="hidden" id="noticeId" name="notice_id" value="">
               <div class="form-group">
                   <label class="form-label">Notice Title:</label>
                   <p id="noticeTitle" style="font-weight: 500; color: #374151; background: #f9fafb; padding: 0.75rem; border-radius: 6px;"></p>
               </div>
               <div class="form-group">
                   <label for="notice_status" class="form-label">Status:</label>
                   <select id="notice_status" name="notice_status" class="form-select" required>
                       <option value="active">Active (Visible to users)</option>
                       <option value="inactive">Inactive (Hidden from users)</option>
                   </select>
               </div>
               <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                   <button type="button" class="btn btn-secondary" onclick="closeNoticeStatusModal()">Cancel</button>
                   <button type="submit" name="update_notice_status" class="btn btn-success">
                       <i class="fas fa-save"></i> Update Status
                   </button>
               </div>
           </form>
       </div>
   </div>

   <div id="noticeDetailsModal" class="modal">
       <div class="modal-content">
           <div class="modal-header">
               <h2 class="modal-title">Notice Details</h2>
               <button class="close-btn" onclick="closeNoticeDetailsModal()">&times;</button>
           </div>
           <div class="form-group">
               <label class="form-label">Title:</label>
               <p id="detailNoticeTitle" style="font-weight: 600; font-size: 1.125rem; color: #1e293b; margin-bottom: 1rem;"></p>
           </div>
           <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
               <div>
                   <label class="form-label" style="margin-bottom: 0.25rem;">Priority:</label>
                   <span id="detailNoticePriority" class="badge"></span>
               </div>
               <div>
                   <label class="form-label" style="margin-bottom: 0.25rem;">Status:</label>
                   <span id="detailNoticeStatus" class="badge"></span>
               </div>
           </div>
           <div class="form-group">
               <label class="form-label">Content:</label>
               <div id="detailNoticeContent" style="background: #f9fafb; padding: 1rem; border-radius: 6px; line-height: 1.6; white-space: pre-wrap;"></div>
           </div>
           <div style="display: flex; justify-content: flex-end;">
               <button type="button" class="btn btn-secondary" onclick="closeNoticeDetailsModal()">Close</button>
           </div>
       </div>
   </div>

   <div id="removeTrustedUserModal" class="modal">
       <div class="modal-content">
           <div class="modal-header">
               <h2 class="modal-title">Remove Trusted User</h2>
               <button class="close-btn" onclick="closeRemoveTrustedUserModal()">&times;</button>
           </div>
           <form method="POST" action="">
               <input type="hidden" id="removeUserId" name="remove_user_id" value="">
               <div class="form-group">
                   <p style="margin-bottom: 1rem; color: #374151;">
                       Are you sure you want to remove <strong id="removeUserName"></strong> as a trusted user?
                   </p>
                   <div class="alert alert-info">
                       <i class="fas fa-info-circle"></i> 
                       This user will lose their trusted status and any special privileges associated with it.
                   </div>
               </div>
               <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                   <button type="button" class="btn btn-secondary" onclick="closeRemoveTrustedUserModal()">Cancel</button>
                   <button type="submit" name="remove_trusted_user" class="btn btn-danger">
                       <i class="fas fa-user-times"></i> Remove User
                   </button>
               </div>
           </form>
       </div>
   </div>

   <div id="kycModal" class="modal">
       <div class="modal-content">
           <div class="modal-header">
               <h2 class="modal-title">Verify KYC</h2>
               <button class="close-btn" onclick="closeKycModal()">&times;</button>
           </div>
           <form method="POST" action="">
               <input type="hidden" id="kycUserId" name="user_id" value="">
               <div class="form-group">
                   <label class="form-label">User Name:</label>
                   <p id="kycUserName" style="font-weight: 500; color: #374151; background: #f9fafb; padding: 0.75rem; border-radius: 6px;"></p>
               </div>
               <div class="form-group">
                   <label class="form-label">Profile Picture:</label>
                   <img id="kycProfileImage" src="" alt="Profile Picture" style="width: 100%; max-width: 300px; border-radius: 6px; margin-bottom: 1rem;">
               </div>
               <div class="form-group">
                   <label class="form-label">Citizenship - Front:</label>
                   <img id="kycFrontImage" src="" alt="Citizenship Front" style="width: 100%; max-width: 300px; border-radius: 6px; margin-bottom: 1rem;">
               </div>
               <div class="form-group">
                   <label class="form-label">Citizenship - Back:</label>
                   <img id="kycBackImage" src="" alt="Citizenship Back" style="width: 100%; max-width: 300px; border-radius: 6px; margin-bottom: 1rem;">
               </div>
               <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                   <button type="button" class="btn btn-secondary" onclick="closeKycModal()">Cancel</button>
                   <button type="submit" name="verify_kyc" class="btn btn-success">
                       <i class="fas fa-check"></i> Accept KYC
                   </button>
               </div>
           </form>
       </div>
   </div>

   <script>
       function showTab(tabName) {
           const tabContents = document.querySelectorAll('.tab-content');
           tabContents.forEach(content => content.classList.remove('active'));
           const tabButtons = document.querySelectorAll('.tab-btn');
           tabButtons.forEach(btn => btn.classList.remove('active'));
           document.getElementById(tabName).classList.add('active');
           event.target.classList.add('active');
       }

       function openStatusModal(problemId, currentStatus, problemTitle) {
           document.getElementById('problemId').value = problemId;
           document.getElementById('problemTitle').textContent = problemTitle;
           document.getElementById('status').value = currentStatus;
           document.getElementById('statusModal').style.display = 'block';
           toggleCompletionMessage();
       }

       function closeStatusModal() {
           document.getElementById('statusModal').style.display = 'none';
       }

       function toggleCompletionMessage() {
           const status = document.getElementById('status').value;
           const completionGroup = document.getElementById('completionMessageGroup');
           const completionMessage = document.getElementById('completion_message');
           if (status === 'completed') {
               completionGroup.style.display = 'block';
               completionMessage.required = true;
           } else {
               completionGroup.style.display = 'none';
               completionMessage.required = false;
               completionMessage.value = '';
           }
       }

       function openNoticeStatusModal(noticeId, currentStatus, noticeTitle) {
           document.getElementById('noticeId').value = noticeId;
           document.getElementById('noticeTitle').textContent = noticeTitle;
           document.getElementById('notice_status').value = currentStatus;
           document.getElementById('noticeStatusModal').style.display = 'block';
       }

       function closeNoticeStatusModal() {
           document.getElementById('noticeStatusModal').style.display = 'none';
       }

       function viewNoticeDetails(noticeId, title, content, priority, status) {
           document.getElementById('detailNoticeTitle').textContent = title;
           document.getElementById('detailNoticeContent').textContent = content;
           const priorityBadge = document.getElementById('detailNoticePriority');
           priorityBadge.textContent = priority.charAt(0).toUpperCase() + priority.slice(1);
           priorityBadge.className = `badge badge-${priority}`;
           const statusBadge = document.getElementById('detailNoticeStatus');
           statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
           statusBadge.className = `badge badge-${status}`;
           document.getElementById('noticeDetailsModal').style.display = 'block';
       }

       function closeNoticeDetailsModal() {
           document.getElementById('noticeDetailsModal').style.display = 'none';
       }

       function removeTrustedUser(userId, userName) {
           document.getElementById('removeUserId').value = userId;
           document.getElementById('removeUserName').textContent = userName;
           document.getElementById('removeTrustedUserModal').style.display = 'block';
       }

       function closeRemoveTrustedUserModal() {
           document.getElementById('removeTrustedUserModal').style.display = 'none';
       }

       function openKycModal(userId, userName, profileImage, kycFront, kycBack) {
           document.getElementById('kycUserId').value = userId;
           document.getElementById('kycUserName').textContent = userName;
           document.getElementById('kycProfileImage').src = 'Uploads/' + profileImage;
           document.getElementById('kycFrontImage').src = 'Uploads/' + kycFront;
           document.getElementById('kycBackImage').src = 'Uploads/' + kycBack;
           document.getElementById('kycModal').style.display = 'block';
       }

       function closeKycModal() {
           document.getElementById('kycModal').style.display = 'none';
       }

       window.onclick = function(event) {
           const modals = ['statusModal', 'noticeStatusModal', 'noticeDetailsModal', 'removeTrustedUserModal', 'kycModal'];
           modals.forEach(modalId => {
               const modal = document.getElementById(modalId);
               if (event.target == modal) {
                   modal.style.display = 'none';
               }
           });
       }

       document.addEventListener('keydown', function(event) {
           if (event.key === 'Escape') {
               const modals = ['statusModal', 'noticeStatusModal', 'noticeDetailsModal', 'removeTrustedUserModal', 'kycModal'];
               modals.forEach(modalId => {
                   document.getElementById(modalId).style.display = 'none';
               });
           }
       });

       document.addEventListener('DOMContentLoaded', function() {
           const textareas = document.querySelectorAll('.form-textarea');
           textareas.forEach(textarea => {
               textarea.addEventListener('input', function() {
                   this.style.height = 'auto';
                   this.style.height = (this.scrollHeight) + 'px';
               });
           });
       });
   </script>
</body>
</html>