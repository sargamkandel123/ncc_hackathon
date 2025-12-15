<?php
// Initialize session
session_start();

// Security Check (Uncomment these in production)
// if(!isset($_SESSION['islogged_in']) || $_SESSION['user_type'] !== 'admin'){
//      header("location: login.php");
//      exit;
// }

// Include database connection
require_once 'config.php';

// --- Handle Form Submissions (POST) ---

$message = '';
$messageType = '';

// 1. Handle Delete User
if (isset($_POST['delete_user_id'])) {
    $userIdToDelete = intval($_POST['delete_user_id']);
    
    // Prevent deleting yourself (optional safety check, assumes admin ID is stored in session)
    // if ($userIdToDelete == $_SESSION['user_id']) {
    //      $message = "You cannot delete your own account."; $messageType = "danger";
    // } else {

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userIdToDelete);

    if ($stmt->execute()) {
        $message = "User deleted successfully.";
        $messageType = "success";
    } else {
        $message = "Error deleting user: " . $conn->error;
        $messageType = "danger";
    }
    $stmt->close();
    // } 
}

// 2. Handle Edit User update
if (isset($_POST['edit_user_submit'])) {
    $editId = intval($_POST['edit_user_id']);
    $editPhone = trim($_POST['phone']);
    $editStatus = $_POST['status'];
    $editType = $_POST['user_type'];
    $editVerified = $_POST['verified'];

    // Basic validation
    if (empty($editPhone)) {
        $message = "Phone number cannot be empty.";
        $messageType = "warning";
    } else {
        $sql = "UPDATE users SET phone = ?, status = ?, user_type = ?, verified = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $editPhone, $editStatus, $editType, $editVerified, $editId);

        if ($stmt->execute()) {
            $message = "User details updated successfully.";
            $messageType = "success";
        } else {
            $message = "Error updating user: " . $conn->error;
            $messageType = "danger";
        }
        $stmt->close();
    }
}


// --- Fetch Users Data for display ---
// We select relevant fields based on your DB image
$sqlStr = "SELECT id, first_name, last_name, email, phone, user_type, created_at, verified, status FROM users ORDER BY created_at DESC";
$result = $conn->query($sqlStr);
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4e73df; --secondary: #858796; --success: #1cc88a;
            --info: #36b9cc; --warning: #f6c23e; --danger: #e74a3b;
            --dark: #5a5c69; --light: #f8f9fc; --white: #ffffff;
            --sidebar-bg: #4e73df; --sidebar-width: 250px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; outline: none; }
        body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; color: #5a5c69; overflow-x: hidden; }
        .wrapper { display: flex; width: 100%; min-height: 100vh; }
        /* Sidebar */
        .sidebar { width: var(--sidebar-width); background: linear-gradient(180deg, #4e73df 10%, #224abe 100%); color: white; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; transition: all 0.3s; }
        .sidebar-brand { padding: 20px; font-size: 1.5rem; font-weight: 700; text-align: center; letter-spacing: 1px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand i { margin-right: 10px; }
        .sidebar-menu { padding: 20px 0; list-style: none; }
        .sidebar-menu li { padding: 15px 20px; cursor: pointer; transition: 0.3s; border-left: 4px solid transparent; }
        .sidebar-menu li:hover, .sidebar-menu li.active { background: rgba(255,255,255,0.1); border-left: 4px solid #fff; }
        .sidebar-menu li a { color: white; text-decoration: none; display: flex; align-items: center; font-size: 0.95rem; }
        .sidebar-menu li i { margin-right: 15px; width: 20px; text-align: center; }
        /* Main Panel & Header */
        .main-panel { flex: 1; margin-left: var(--sidebar-width); display: flex; flex-direction: column; }
        .top-header { background: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 15px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 99; }
        .header-title h2 { font-size: 1.2rem; color: #444; font-weight: 600; }
        .user-profile { display: flex; align-items: center; gap: 10px; }
        .user-profile img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e3e6f0; }
        .content-container { padding: 30px; }

        /* --- Page Specific Styles --- */
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 25px; margin-bottom: 30px; }
        .card-header { margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .card-header h2 { font-size: 1.1rem; color: #4e73df; font-weight: 700; text-transform: uppercase; }
        
        /* Table styling (reused) */
        .table-responsive { overflow-x: auto; }
        .users-table { width: 100%; border-collapse: collapse; }
        .users-table th { text-align: left; padding: 15px; background-color: #f8f9fc; color: #4e73df; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e3e6f0; }
        .users-table td { padding: 15px; border-bottom: 1px solid #e3e6f0; font-size: 0.9rem; color: #555; vertical-align: middle; }
        .users-table tr:hover { background-color: #fafafa; }

        /* Badges & Buttons */
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge-admin { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }
        .badge-normal { background: rgba(78, 115, 223, 0.1); color: #4e73df; }
        .badge-active { background: rgba(28, 200, 138, 0.1); color: #1cc88a; }
        .badge-inactive { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }
        .badge-verified { background: #1cc88a; color: white; padding: 2px 6px; font-size: 0.7rem; margin-left: 5px; border-radius: 4px;}
        .badge-not-verified { background: #858796; color: white; padding: 2px 6px; font-size: 0.7rem; margin-left: 5px; border-radius: 4px;}

        .btn-sm { padding: 6px 12px; border-radius: 4px; font-size: 0.8rem; cursor: pointer; transition: 0.2s; border: none; font-family: 'Poppins'; display: inline-flex; align-items: center; gap: 5px;}
        .btn-edit { background: #36b9cc; color: white; margin-right: 5px;}
        .btn-edit:hover { background: #2c9faf; }
        .btn-delete { background: #e74a3b; color: white; }
        .btn-delete:hover { background: #c0392b; }

        /* Alerts */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; align-items: center; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert i { margin-right: 10px; font-size: 1.2rem; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px); }
        .modal-content { background: white; margin: 5% auto; border-radius: 12px; width: 90%; max-width: 600px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); animation: slideDown 0.3s ease-out; }
        .modal-header { padding: 20px 30px; border-bottom: 1px solid #e3e6f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; color: #4e73df; }
        .close { font-size: 24px; color: #aaa; cursor: pointer; } .close:hover { color: #333; }
        .modal-body { padding: 30px; }
        
        /* Form Styles */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #e3e6f0; border-radius: 8px; font-family: 'Poppins'; transition: 0.3s; }
        .form-control:focus { border-color: #4e73df; box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1); }
        .btn-submit { width: 100%; padding: 12px; background: #4e73df; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: #224abe; }

        @media (max-width: 992px) { .sidebar { transform: translateX(-100%); width: 0; } .main-panel { margin-left: 0; } .content-container { padding: 15px; } }
    </style>
</head>
<body>
    <div class="wrapper">
        <nav class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-bullhorn"></i> Aawaz
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li class="active"><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <div class="main-panel">
            <header class="top-header">
                <div class="header-title">
                    <h2>User Management</h2>
                </div>
                <div class="user-profile">
                    <span>Admin</span>
                    <img src="https://ui-avatars.com/api/?name=Admin+User&background=4e73df&color=fff" alt="Admin">
                </div>
            </header>

            <div class="content-container">
                
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo ($messageType == 'success') ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h2>All Registered Users (<?php echo count($users); ?>)</h2>
                        </div>
                    <div class="table-responsive">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Email & Phone</th>
                                    <th>Role / Status</th>
                                    <th>Documents</th>
                                    <th>KYC</th>
                                    <th>Joined Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) > 0): ?>
                                    <?php foreach ($users as $user): 
                                        // Prepare user data for the edit modal using JSON encode
                                        $userDataJson = htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <tr>
                                        <td>#<?php echo $user['id']; ?></td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <div style="background:#e3e6f0; width:35px; height:35px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.9rem; font-weight:bold; color:#4e73df;">
                                                    <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight:600;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><i class="fas fa-envelope fa-xs" style="color:#ccc;"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                                            <div style="font-size:0.85rem; color:#888;"><i class="fas fa-phone fa-xs" style="color:#ccc;"></i> <?php echo htmlspecialchars($user['phone']); ?></div>
                                        </td>
                                        <td>
                                            <div style="margin-bottom:5px;">
                                                <span class="badge badge-<?php echo $user['user_type']; ?>">
                                                    <?php echo ucfirst($user['user_type']); ?>
                                                </span>
                                            </div>
                                            <span class="badge badge-<?php echo $user['status']; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                         <td>
                                            <div><i class="fas fa-document fa-xs" style="color:#ccc;"></i> <a href="view.php?id=<?php echo $user['id']; ?>">See</a></div>
                                        </td>
                                        <td>
                                             <?php if($user['verified'] === 'yes'): ?>
                                                <span class="badge-verified"><i class="fas fa-check"></i> Verified</span>
                                            <?php else: ?>
                                                <span class="badge-not-verified"><i class="fas fa-times"></i> Pending</span>
                                                <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div style="display:flex;">
                                                <button class="btn-sm btn-edit" onclick="openEditModal(<?php echo $userDataJson; ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this user?');">
                                                    <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn-sm btn-delete">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center; padding: 30px;">No users found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div> </div> </div> <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User Details</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" id="edit_user_id" name="edit_user_id">
                    
                    <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fc; border-radius: 8px;">
                        <h4 id="edit_user_fullname" style="margin-bottom:5px; color:#4e73df;"></h4>
                        <p id="edit_user_email" style="color:#888; font-size:0.9rem;"></p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_phone">Phone Number</label>
                        <input type="text" class="form-control" id="edit_phone" name="phone" required>
                    </div>

                    <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div>
                             <label class="form-label" for="edit_status">Account Status</label>
                             <select class="form-control" id="edit_status" name="status">
                                 <option value="active">Active</option>
                                 <option value="inactive">Inactive</option>
                             </select>
                        </div>
                        <div>
                             <label class="form-label" for="edit_user_type">User Type</label>
                             <select class="form-control" id="edit_user_type" name="user_type">
                                 <option value="normal">Normal</option>
                                 <option value="admin">Admin</option>
                             </select>
                        </div>
                        <div>
                             <label class="form-label" for="edit_verified">KYC Verified?</label>
                             <select class="form-control" id="edit_verified" name="verified">
                                 <option value="yes">Yes</option>
                                 <option value="no">No</option>
                             </select>
                        </div>
                    </div>

                    <button type="submit" name="edit_user_submit" class="btn-submit">
                        <i class="fas fa-save"></i> Update User
                    </button>
                </form>
            </div>
        </div>
    </div>


    <script>
        // Modal elements
        const editModal = document.getElementById('editUserModal');

        // Function to open Edit Modal and populate data
        function openEditModal(userData) {
            // Populate read-only data
            document.getElementById('edit_user_id').value = userData.id;
            document.getElementById('edit_user_fullname').textContent = userData.first_name + ' ' + userData.last_name;
            document.getElementById('edit_user_email').textContent = userData.email;
            
            // Populate editable forms
            document.getElementById('edit_phone').value = userData.phone;
            document.getElementById('edit_status').value = userData.status;
            document.getElementById('edit_user_type').value = userData.user_type;
            
            // Handle verified flag (assuming 'yes'/'no' in DB based on your schema image showing varchar)
            // If your DB uses 1/0 instead, change 'yes' to '1' and 'no' to '0' below.
            document.getElementById('edit_verified').value = userData.verified;

            // Show modal
            editModal.style.display = 'block';
        }

        // Function to close modal
        function closeEditModal() {
            editModal.style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === editModal) {
                closeEditModal();
            }
        }

        // Auto-hide alerts after 5 seconds
        const alertBox = document.querySelector('.alert');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 500);
            }, 5000);
        }
    </script>
</body>
</html>