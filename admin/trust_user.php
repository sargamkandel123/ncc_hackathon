<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Assume config.php sets up $conn using mysqli_connect
include_once __DIR__ . '/../config.php'; 

if (!isset($_SESSION['logged_in']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header("location: login.php");
    exit;
}

$admin_con = $_SESSION['admin_con'];
$max_trusted_users = 5;
$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action']) && $_POST['action'] === 'add_user' && isset($_POST['user_email'])) {
        $user_email_to_add = mysqli_real_escape_string($conn, trim($_POST['user_email']));
        $admin_con_safe = mysqli_real_escape_string($conn, $admin_con);

        $count_sql = "SELECT COUNT(*) FROM users WHERE trust_member = 'yes' AND trust_to = '{$admin_con_safe}'";
        $count_result = mysqli_query($conn, $count_sql);
        $current_count_check = mysqli_fetch_row($count_result)[0];

        if ($current_count_check >= $max_trusted_users) {
            $message = "Error: Limit of {$max_trusted_users} trusted users reached.";
            $message_type = 'danger';
        } else {
            $find_sql = "SELECT id, first_name, last_name, trust_member, trust_to FROM users WHERE email = '{$user_email_to_add}'";
            $find_result = mysqli_query($conn, $find_sql);
            $target_user = mysqli_fetch_assoc($find_result);

            if (!$target_user) {
                $message = "Error: User with email '{$user_email_to_add}' not found.";
                $message_type = 'danger';
            } elseif ($target_user['trust_member'] === 'yes') {
                if ($target_user['trust_to'] === $admin_con) {
                    $message = "Warning: User is already a trusted member for your department.";
                    $message_type = 'warning';
                } else {
                    $message = "Error: User is already trusted by the **{$target_user['trust_to']}** department and cannot be added.";
                    $message_type = 'danger';
                }
            } else {
                $update_sql = "UPDATE users SET trust_member = 'yes', trust_to = '{$admin_con_safe}' WHERE id = {$target_user['id']}";
                if (mysqli_query($conn, $update_sql)) {
                    $message = "Success: " . htmlspecialchars($target_user['first_name'] . " " . $target_user['last_name']) . " added as a trusted user for **{$admin_con}**.";
                    $message_type = 'success';
                } else {
                    $message = "Database Error: " . mysqli_error($conn);
                    $message_type = 'danger';
                }
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'remove_user' && isset($_POST['user_id'])) {
        $user_id_to_remove = (int)$_POST['user_id'];
        $admin_con_safe = mysqli_real_escape_string($conn, $admin_con);

        $remove_sql = "UPDATE users SET trust_member = 'No', trust_to = NULL WHERE id = {$user_id_to_remove} AND trust_to = '{$admin_con_safe}'";
        
        if (mysqli_query($conn, $remove_sql)) {
            if (mysqli_affected_rows($conn) > 0) {
                $message = "Success: Trusted user (ID: {$user_id_to_remove}) has been successfully removed.";
                $message_type = 'success';
            } else {
                $message = "Error: User not found or you do not have permission to remove this user.";
                $message_type = 'danger';
            }
        } else {
            $message = "Database Error: " . mysqli_error($conn);
            $message_type = 'danger';
        }
    }
}

$trusted_users = [];
$current_count = 0;

$admin_con_safe = mysqli_real_escape_string($conn, $admin_con);
$sql = "SELECT id, first_name, last_name, email, user_type FROM users WHERE trust_member = 'yes' AND trust_to = '{$admin_con_safe}' ORDER BY last_name ASC";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $trusted_users[] = $row;
    }
    $current_count = count($trusted_users);
} else {
    $message = "Database Query Error: " . mysqli_error($conn);
    $message_type = 'danger';
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trusted User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #FFC107;
            --background-color: #f8f9fa;
            --surface-color: #ffffff;
            --text-color: #333;
            --border-radius: 8px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 220px;
            background-color: #2c3e50;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            color: #ecf0f1;
            display: flex;
            flex-direction: column;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 5px;
            color: #bdc3c7;
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: background-color 0.2s, color 0.2s;
        }

        .nav-link i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        .nav-link:hover {
            background-color: #34495e;
            color: white;
        }

        .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }

        .submenu {
            list-style: none;
            padding: 0;
            margin-left: 20px;
            border-left: 3px solid rgba(255, 255, 255, 0.1);
        }

        .submenu a {
            padding: 8px 15px;
            padding-left: 25px;
            font-size: 0.9em;
            color: #bdc3c7;
            display: block;
            text-decoration: none;
        }

        .submenu a.active {
            color: var(--primary-color);
            background: rgba(255, 255, 255, 0.05);
        }

        .main-content {
            flex-grow: 1;
            padding: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .title h1 {
            margin: 0;
            font-size: 2rem;
            color: #2c3e50;
        }

        .title p {
            margin: 5px 0 0;
            color: #7f8c8d;
        }

        .status-card {
            background-color: var(--surface-color);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border-left: 5px solid var(--primary-color);
            max-width: 300px;
            margin-bottom: 40px;
        }

        .status-card h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .count-display {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .text-danger {
            color: #e74c3c !important;
        }

        .management-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 40px;
        }

        .add-user-card, .table-card {
            background-color: var(--surface-color);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .add-user-card h3 {
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 15px;
            margin-top: 0;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .form-add-user {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .input-text {
            flex-grow: 1;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: var(--border-radius);
            transition: border-color 0.2s;
        }

        .input-text:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .btn-primary:hover {
            background-color: #43A047;
        }
        
        .btn-primary:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .user-table th, .user-table td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }

        .user-table th {
            background-color: #f7f7f7;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .user-table td {
            font-size: 0.95rem;
        }

        .btn-remove {
            background-color: #e74c3c;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.2s;
        }

        .btn-remove:hover {
            background-color: #c0392b;
        }

        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success { background-color: #D4EDDA; color: #155724; border: 1px solid #C3E6CB; }
        .alert-danger { background-color: #F8D7DA; color: #721C24; border: 1px solid #F5C6CB; }
        .alert-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">Admin Platform</div>
        
        <a href="admin.php" class="nav-link">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        
        <a href="report.php" class="nav-link">
            <i class="fas fa-chart-line"></i> Reports
        </a>
        
        <div class="nav-link active">
            <i class="fas fa-users"></i> User Management
        </div>
        
        <ul class="submenu">
            <li><a href="#" class="active"><i class="fas fa-user-shield"></i> Trusted Members</a></li>
        </ul>
        
        <a href="notice.php" class="nav-link">
            <i class="fas fa-bullhorn"></i> Notices
        </a>
        
        <a href="logout.php" class="nav-link" style="margin-top: auto;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <div class="main-content">
        
        <div class="header">
            <div class="title">
                <h1>Trusted User Management</h1>
                <p>Manage the <span><?php echo $max_trusted_users; ?></span> designated trusted users for the <span style="font-weight: 600;"><?php echo htmlspecialchars($admin_con); ?></span> department.</p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="status-card">
            <h3>Trusted User Slots</h3>
            <p class="count-display">
                <span class="<?php echo ($current_count == $max_trusted_users) ? 'text-danger' : 'text-success'; ?>">
                    <?php echo $current_count; ?>
                </span> / <span><?php echo $max_trusted_users; ?></span>
            </p>
            <p class="remaining-slots"><?php echo $max_trusted_users - $current_count; ?> slots remaining.</p>
        </div>

        <div class="management-section">
            
            <div class="add-user-card">
                <h3><i class="fas fa-user-plus"></i> Add New Trusted User</h3>
                <form class="form-add-user" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="action" value="add_user">
                    <input type="email" name="user_email" class="input-text" placeholder="Enter user's email to add" required 
                           <?php echo ($current_count >= $max_trusted_users) ? 'disabled' : ''; ?>>
                    <button type="submit" class="btn-primary" 
                            <?php echo ($current_count >= $max_trusted_users) ? 'disabled' : ''; ?>>
                        Add User
                    </button>
                </form>
            </div>

            <div class="table-card">
                <h3><i class="fas fa-list-alt"></i> Current Trusted Users (<?php echo htmlspecialchars($admin_con); ?>)</h3>
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>User Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trusted_users)): ?>
                            <tr><td colspan="5" style="text-align: center;">No trusted users currently assigned.</td></tr>
                        <?php else: ?>
                            <?php $i = 1; foreach ($trusted_users as $user): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . " " . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                                    <td>
                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return confirm('Are you sure you want to remove <?php echo htmlspecialchars($user['first_name']); ?>?');" style="display: inline;">
                                            <input type="hidden" name="action" value="remove_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn-remove">
                                                <i class="fas fa-minus-circle"></i> Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</body>
</html>