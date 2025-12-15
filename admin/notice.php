<?php
include_once __DIR__ . '/../config.php';
session_start();
$admin_id = $_SESSION['user_id'];
$admin_department = $_SESSION['admin_con'];

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

if ($_POST && isset($_POST['delete_notice'])) {
    $notice_id = mysqli_real_escape_string($conn, $_POST['notice_id']);
    $delete_notice_sql = "DELETE FROM notices WHERE id = '$notice_id' AND admin_id = '$admin_id'";
    if (mysqli_query($conn, $delete_notice_sql)) {
        $success_message = "Notice deleted successfully!";
    } else {
        $error_message = "Error deleting notice: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aawaz Admin - Department Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #1a1a1a;
            color: #fff;
            padding: 20px 0;
            position: fixed;
            height: 100%;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #333;
        }
        .sidebar-header .user {
            font-size: 14px;
        }
        .sidebar-header .online {
            color: #28a745;
            font-size: 12px;
        }
        .sidebar-menu {
            margin-top: 30px;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #ccc;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar-menu a i {
            margin-right: 12px;
            font-size: 18px;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: #2361e8;
            color: #fff;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }
        .header {
            background-color: #fff;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }
        .header p {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        /* Tabs */
        .tabs {
                display: none;
    background-color: #fff;
    border-radius: 12px 12px 0 0;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 0;
        }
        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            font-size: 15px;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
        }
        .tab.active {
            background-color: #2361e8;
            color: #fff;
        }
        .tab:hover:not(.active) {
            background-color: #f0f4ff;
        }

        /* Content Area */
        .content-area {
            background-color: #fff;
            border-radius: 0 0 12px 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        /* Create Notice Card */
        .create-notice {
            margin-bottom: 40px;
        }
        .create-notice h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #444;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .full-width {
            grid-column: span 3;
        }
        .btn-create {
            background-color: #2361e8;
            color: #fff;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-create:hover {
            background-color: #1e52c7;
        }

        /* Table */
        h2.table-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead th {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            color: #555;
        }
        tbody td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        tbody tr:hover {
            background-color: #f8fbff;
        }
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-expired { background-color: #f8d7da; color: #721c24; }
        .priority-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .priority-low { background-color: #e7f3ff; color: #0c5db8; }
        .priority-medium { background-color: #fff3cd; color: #856404; }
        .priority-high { background-color: #f8d7da; color: #721c24; }
        .actions button {
            padding: 6px 12px;
            margin-right: 8px;
            border: none;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            background-color: #e9ecef;
            color: #495057;
            transition: all 0.3s;
        }
        .actions button:hover {
            background-color: #2361e8;
            color: #fff;
        }

        @media (max-width: 992px) {
            .form-grid {
                grid-template-columns: 1fr 1fr;
            }
            .full-width {
                grid-column: span 2;
            }
        }
        @media (max-width: 768px) {
            .sidebar { position: relative; width: 100%; height: auto; }
            .main-content { margin-left: 0; }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .full-width {
                grid-column: span 1;
            }
            .tabs {
                flex-wrap: wrap;
            }
            .tab {
                flex: none;
                width: 50%;
            }
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
       .badge-expired { background: #fee2e2; color: #dc2626; }

       .badge-high { background: #fee2e2; color: #dc2626; }
       .badge-medium { background: #fef3c7; color: #d97706; }
       .badge-low { background: #dcfce7; color: #166534; }

       .badge-trusted { background: #e0f2fe; color: #0891b2; }

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

       .form-label {
           font-weight: 500;
           color: #374151;
           margin-bottom: 0.5rem;
           display: block;
       }

       .form-select, .form-textarea {
           width: 100%;
           padding: 0.75rem;
           border: 1px solid #d1d5db;
           border-radius: 6px;
           font-size: 1rem;
           background: white;
       }

       .form-textarea {
           resize: vertical;
           font-family: inherit;
       }

    </style>
</head>
<body>

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
                <label for="notice_status" class="form-label">New Status:</label>
                <select id="notice_status" name="notice_status" class="form-select" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="expired">Expired</option>
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

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="user">Admin User <span class="online">● Online</span></div>
            </div>
            <nav class="sidebar-menu">
                <a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="report.php"><i class="fas fa-file-alt"></i> Reports</a>
                    <li><a href="trust_user.php"><i class="fas fa-map-marker-alt"></i>Users Management</a></li>
                <a href="notice.php" class="active"><i class="fas fa-bell"></i> Notices</a>
            </nav>
        </aside>
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

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Department Dashboard</h1>
                <p>Manage notices in your department</p>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <div class="tab">Dashboard</div>
                <div class="tab">Problems</div>
                <div class="tab active">Notices</div>
                <div class="tab">Trusted Users</div>
            </div>
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

            <!-- Content Area -->
            <div class="content-area">
                <!-- Create New Notice -->
                <div class="create-notice">
                    <h2>Create New Notice</h2>
                <form action="" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Notice Title</label>
                            <input type="text" name="notice_title" placeholder="Enter notice title">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="notice_category">
                                <option>Roads</option>
                                <option>Waste</option>
                                <option>Water</option>
                                <option>Safety</option>
                                <option>General</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Priority</label>
                            <select name="notice_priority">
                                <option>Low</option>
                                <option>Medium</option>
                                <option>High</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label>Notice Content</label>
                            <textarea name="notice_content" placeholder="Write the notice content here..."></textarea>
                        </div>
                    </div>
                    <button class="btn-create" name="create_notice">
                        <i class="fas fa-plus" style="margin-right: 8px;"></i>
                        Create Notice
                    </button>
                    </form>
                </div>

                <!-- Posted Notices Table -->
                <h2 class="table-title">Posted Notices</h2>
                <div class="table-container">
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
                           <?php 
                           $notices_sql = "SELECT * FROM notices WHERE admin_id = '$admin_id' ORDER BY created_at DESC";
$notices_result = mysqli_query($conn, $notices_sql);
                           if (mysqli_num_rows($notices_result) > 0): ?>
                               <?php while ($notice = mysqli_fetch_assoc($notices_result)): ?>
                               <tr>
                                   <td>
                                       <div style="font-weight: 600; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($notice['title']); ?></div>
                                       <div style="color: #64748b; font-size: 0.875rem;">
                                           <?php echo htmlspecialchars(substr($notice['content'], 0, 80)) . (strlen($notice['content']) > 80 ? '...' : ''); ?>
                                       </div>
                                   </td>
                                   <td>
                                       <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $notice['category'] ?? 'other')); ?>">
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
                                           <form id="deleteForm_<?php echo $notice['id']; ?>" method="POST" style="display: inline-block;">
                                               <input type="hidden" name="notice_id" value="<?php echo $notice['id']; ?>">
                                               <input type="hidden" name="delete_notice" value="1">
                                               <button type="button" class="btn btn-danger" onclick="if(confirm('Are you sure you want to delete this notice? This action cannot be undone.')) { document.getElementById('deleteForm_<?php echo $notice['id']; ?>').submit(); }">
                                                   <i class="fas fa-trash"></i> Delete
                                               </button>
                                           </form>
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
        </main>
    </div>

    <script>
    
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
           priorityBadge.className = `badge badge-${priority.toLowerCase()}`;
           const statusBadge = document.getElementById('detailNoticeStatus');
           statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
           statusBadge.className = `badge badge-${status.toLowerCase()}`;
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

    </script>
</body>
</html>