<?php
// Initialize session
session_start();

// NOTE: Uncomment these security checks in a production environment
// if(!isset($_SESSION['islogged_in']) || $_SESSION['user_type'] !== 'admin'){
//      header("location: login.php");
//      exit;
// }

// Include database connection
require_once 'config.php';

$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$messageType = '';
$userData = null;

// --- 1. Handle Verification/Rejection POST Request ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action']; // 'verify' or 'reject'
    $targetId = intval($_POST['user_id']);
    
    // Determine the new 'verified' status and update message
    $newStatus = ($action === 'verify') ? 'yes' : 'no';
    $logMessage = ($action === 'verify') ? 'KYC successfully VERIFIED.' : 'KYC REJECTED. Status set to pending (no).';

    if ($targetId > 0) {
        $stmt = $conn->prepare("UPDATE users SET verified = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $targetId);

        if ($stmt->execute()) {
            $message = $logMessage;
            $messageType = ($action === 'verify') ? 'success' : 'danger';
            // Important: Redirect to the user list after action to prevent re-submission
            // header("location: users.php?msg=" . urlencode($message) . "&type=" . $messageType);
            // exit;
        } else {
            $message = "Database error: " . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }
}

// --- 2. Fetch User Data ---
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone, kyc_front, kyc_back, verified FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $userData = $result->fetch_assoc();
    } else {
        $message = "User not found or ID is invalid.";
        $messageType = 'warning';
    }
    $stmt->close();
} else {
    $message = "No User ID provided in the URL.";
    $messageType = 'warning';
}

// --- Configuration for image paths ---
// IMPORTANT: Change 'uploads/kyc/' to the actual folder where your KYC images are stored.
$image_base_path = 'uploads/'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Verification - User #<?php echo $userId; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4e73df; --success: #1cc88a; --danger: #e74a3b; --warning: #f6c23e;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f3f4f6; color: #5a5c69; padding: 40px; }
        
        .container { 
            max-width: 1200px; margin: 0 auto; background: white; 
            padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
        }

        /* Header */
        .page-header { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e3e6f0;
        }
        .page-header h1 { 
            font-size: 1.8rem; color: var(--primary); font-weight: 700; 
        }
        .page-header a { 
            text-decoration: none; color: white; background: #858796; 
            padding: 10px 15px; border-radius: 6px; font-size: 0.9rem;
            transition: background 0.2s;
        }
        .page-header a:hover { background: #6e707e; }

        /* User Info Box */
        .user-info { 
            background: #f8f9fc; padding: 20px; border-radius: 8px; 
            margin-bottom: 30px; border-left: 5px solid var(--primary);
        }
        .user-info h2 { font-size: 1.4rem; margin-bottom: 10px; color: #333; }
        .user-info p { margin: 5px 0; font-size: 0.95rem; }
        .current-status { 
            font-weight: 600; padding: 3px 10px; border-radius: 4px; 
            display: inline-block; margin-left: 10px; 
        }
        .status-yes { background: rgba(28, 200, 138, 0.1); color: var(--success); }
        .status-no { background: rgba(246, 194, 62, 0.1); color: var(--warning); }

        /* KYC Documents Section */
        .kyc-docs { 
            display: grid; grid-template-columns: 1fr 1fr; gap: 30px; 
            margin-bottom: 30px;
        }
        .doc-viewer { 
            border: 1px solid #e3e6f0; border-radius: 8px; overflow: hidden; 
            text-align: center;
        }
        .doc-viewer h3 { 
            background: #f8f9fc; padding: 10px; font-size: 1.1rem; 
            color: var(--primary); margin: 0; border-bottom: 1px solid #e3e6f0;
        }
        .doc-content { padding: 15px; min-height: 250px; display: flex; justify-content: center; align-items: center; }
        .doc-image { 
            max-width: 100%; height: auto; border-radius: 6px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); cursor: zoom-in;
        }
        .no-doc { color: #858796; font-style: italic; }

        /* Action Buttons */
        .action-container { 
            padding: 20px; border-top: 1px solid #e3e6f0; 
            display: flex; justify-content: center; gap: 30px;
        }
        .btn { 
            padding: 12px 30px; border: none; border-radius: 8px; 
            font-size: 1rem; font-weight: 600; cursor: pointer; 
            transition: all 0.2s;
        }
        .btn-verify { background: var(--success); color: white; }
        .btn-verify:hover { background: #17a673; }
        .btn-reject { background: var(--danger); color: white; }
        .btn-reject:hover { background: #c5382e; }

        /* Alerts */
        .alert { 
            padding: 15px; margin-bottom: 20px; border-radius: 8px; 
            display: flex; align-items: center; font-weight: 500;
        }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert i { margin-right: 10px; font-size: 1.2rem; }
    </style>
</head>
<body>

<div class="container">

    <div class="page-header">
        <h1>KYC Verification: User #<?php echo $userId; ?></h1>
        <a href="user_management.php"><i class="fas fa-arrow-left"></i> Back to User List</a>
    </div>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fas fa-<?php echo ($messageType == 'success') ? 'check-circle' : (($messageType == 'danger') ? 'times-circle' : 'exclamation-triangle'); ?>"></i>
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <?php if ($userData): ?>
    
    <div class="user-info">
        <h2><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h2>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($userData['email']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($userData['phone']); ?></p>
        <p>
            <strong>Current Verification Status:</strong> 
            <span class="current-status status-<?php echo $userData['verified']; ?>">
                <?php echo ($userData['verified'] == 'yes') ? 'VERIFIED' : 'PENDING / REJECTED'; ?>
            </span>
        </p>
    </div>

    <div class="kyc-docs">
        
        <div class="doc-viewer">
            <h3><i class="fas fa-id-card"></i> Front Side Document</h3>
            <div class="doc-content">
                <?php 
                $frontPath = $image_base_path . htmlspecialchars($userData['kyc_front']);
                if (!empty($userData['kyc_front']) && file_exists($frontPath)): 
                ?>
                    <img src="<?php echo $frontPath; ?>" alt="KYC Front Document" class="doc-image" onclick="window.open(this.src)">
                <?php else: ?>
                    <p class="no-doc">Front document not uploaded or file not found at: <?php echo $frontPath; ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="doc-viewer">
            <h3><i class="fas fa-id-card-alt"></i> Back Side Document</h3>
            <div class="doc-content">
                <?php 
                $backPath = $image_base_path . htmlspecialchars($userData['kyc_back']);
                if (!empty($userData['kyc_back']) && file_exists($backPath)): 
                ?>
                    <img src="<?php echo $backPath; ?>" alt="KYC Back Document" class="doc-image" onclick="window.open(this.src)">
                <?php else: ?>
                    <p class="no-doc">Back document not uploaded or file not found at: <?php echo $backPath; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="action-container">
        <form method="POST">
            <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
            <input type="hidden" name="action" value="verify">
            <button type="submit" class="btn btn-verify" onclick="return confirm('Are you sure you want to VERIFY the KYC for this user?');">
                <i class="fas fa-check-circle"></i> VERIFY KYC
            </button>
        </form>

        <form method="POST">
            <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
            <input type="hidden" name="action" value="reject">
            <button type="submit" class="btn btn-reject" onclick="return confirm('Are you sure you want to REJECT the KYC? This sets status to PENDING.');">
                <i class="fas fa-times-circle"></i> REJECT KYC
            </button>
        </form>
    </div>

    <?php else: ?>
        <p style="text-align: center; padding: 50px;">Please return to the user list and select a valid user for KYC verification.</p>
    <?php endif; ?>

</div>

</body>
</html>