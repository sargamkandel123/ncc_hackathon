<?php
include 'config.php';
session_start();
$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['user_name'];

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['action']) && $_POST['action'] == 'create_campaign') {
        $title = mysqli_real_escape_string($conn, trim($_POST['title']));
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $duration_days = (int)$_POST['duration_days'];
        $target_volunteers = !empty($_POST['target_volunteers']) ? (int)$_POST['target_volunteers'] : NULL;
        $location = mysqli_real_escape_string($conn, trim($_POST['location']));
        
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+$duration_days days"));
        
        if (!empty($title) && !empty($description) && $duration_days > 0) {
            if ($target_volunteers !== NULL) {
                $sql = "INSERT INTO campaigns (title, description, creator_id, creator_name, duration_days, target_volunteers, location, start_date, end_date) 
                        VALUES ('$title', '$description', $current_user_id, '$current_user_name', $duration_days, $target_volunteers, '$location', '$start_date', '$end_date')";
            } else {
                $sql = "INSERT INTO campaigns (title, description, creator_id, creator_name, duration_days, location, start_date, end_date) 
                        VALUES ('$title', '$description', $current_user_id, '$current_user_name', $duration_days, '$location', '$start_date', '$end_date')";
            }
            
            if (mysqli_query($conn, $sql)) {
                $message = "Campaign created successfully!";
                $message_type = "success";
            } else {
                $message = "Error creating campaign: " . mysqli_error($conn);
                $message_type = "error";
            }
        } else {
            $message = "Please fill all required fields";
            $message_type = "error";
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'join_campaign') {
        $campaign_id = (int)$_POST['campaign_id'];
        
        $check_sql = "SELECT id FROM campaign_volunteers WHERE campaign_id = $campaign_id AND volunteer_id = $current_user_id";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) == 0) {
            $join_sql = "INSERT INTO campaign_volunteers (campaign_id, volunteer_id, volunteer_name) VALUES ($campaign_id, $current_user_id, '$current_user_name')";
            
            if (mysqli_query($conn, $join_sql)) {
                $message = "Successfully joined the campaign!";
                $message_type = "success";
            } else {
                $message = "Error joining campaign";
                $message_type = "error";
            }
        } else {
            $message = "You have already joined this campaign";
            $message_type = "error";
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'leave_campaign') {
        $campaign_id = (int)$_POST['campaign_id'];
        
        $leave_sql = "DELETE FROM campaign_volunteers WHERE campaign_id = $campaign_id AND volunteer_id = $current_user_id";
        
        if (mysqli_query($conn, $leave_sql)) {
            $message = "Successfully left the campaign!";
            $message_type = "success";
        } else {
            $message = "Error leaving campaign";
            $message_type = "error";
        }
    }
}

function getCampaignVolunteers($campaign_id) {
    global $conn;
    $sql = "SELECT cv.*, u.email, u.phone 
            FROM campaign_volunteers cv 
            LEFT JOIN users u ON cv.volunteer_id = u.id 
            WHERE cv.campaign_id = $campaign_id 
            ORDER BY cv.joined_at DESC";
    return mysqli_query($conn, $sql);
}

if (isset($_GET['action']) && $_GET['action'] == 'get_volunteers' && isset($_GET['campaign_id'])) {
    $campaign_id = (int)$_GET['campaign_id'];
    
    $campaign_sql = "SELECT * FROM campaigns WHERE id = $campaign_id AND creator_id = $current_user_id";
    $campaign_result = mysqli_query($conn, $campaign_sql);
    
    if (mysqli_num_rows($campaign_result) > 0) {
        $campaign = mysqli_fetch_assoc($campaign_result);
        $volunteers = getCampaignVolunteers($campaign_id);
        
        header('Content-Type: application/json');
        
        $volunteer_list = [];
        while ($volunteer = mysqli_fetch_assoc($volunteers)) {
            $volunteer_list[] = [
                'id' => $volunteer['volunteer_id'],
                'name' => $volunteer['volunteer_name'],
                'joined_at' => $volunteer['joined_at'],
                'email' => $volunteer['email'] ?? 'Not provided',
                'phone' => $volunteer['phone'] ?? 'Not provided'
            ];
        }
        
        echo json_encode([
            'status' => 'success',
            'campaign' => $campaign,
            'volunteers' => $volunteer_list,
            'total_volunteers' => count($volunteer_list)
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Campaign not found or unauthorized']);
        exit;
    }
}

function getUserCampaigns($user_id) {
    global $conn;
    $sql = "SELECT c.*, COUNT(cv.volunteer_id) as volunteer_count 
            FROM campaigns c 
            LEFT JOIN campaign_volunteers cv ON c.id = cv.campaign_id 
            WHERE c.creator_id = $user_id 
            GROUP BY c.id 
            ORDER BY c.created_at DESC";
    return mysqli_query($conn, $sql);
}

function getOtherCampaigns($user_id) {
    global $conn;
    $sql = "SELECT c.*, COUNT(cv.volunteer_id) as volunteer_count 
            FROM campaigns c 
            LEFT JOIN campaign_volunteers cv ON c.id = cv.campaign_id 
            WHERE c.creator_id != $user_id 
            AND c.id NOT IN (
                SELECT campaign_id FROM campaign_volunteers WHERE volunteer_id = $user_id
            )
            AND CURDATE() <= c.end_date
            GROUP BY c.id 
            ORDER BY c.created_at DESC";
    return mysqli_query($conn, $sql);
}

function getJoinedCampaigns($user_id) {
    global $conn;
    $sql = "SELECT c.*, COUNT(cv.volunteer_id) as volunteer_count, cv.joined_at 
            FROM campaigns c 
            LEFT JOIN campaign_volunteers cv ON c.id = cv.campaign_id 
            INNER JOIN campaign_volunteers user_vol ON c.id = user_vol.campaign_id 
            WHERE user_vol.volunteer_id = $user_id 
            AND c.creator_id != $user_id
            GROUP BY c.id 
            ORDER BY cv.joined_at DESC";
    return mysqli_query($conn, $sql);
}

function getDaysRemaining($end_date) {
    $today = new DateTime();
    $end = new DateTime($end_date);
    $diff = $today->diff($end);
    return $diff->days;
}

$my_campaigns = getUserCampaigns($current_user_id);
$joined_campaigns = getJoinedCampaigns($current_user_id);
$other_campaigns = getOtherCampaigns($current_user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaigns - Aawaz</title>
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%);
    min-height: 100vh;
    padding: 2rem;
    position: relative;
    overflow-x: hidden;
    color: #374151;
}

body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(200, 200, 200, 0.2) 0%, transparent 50%),
        radial-gradient(circle at 40% 80%, rgba(240, 240, 240, 0.4) 0%, transparent 50%);
    pointer-events: none;
}

.floating-shapes {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    pointer-events: none;
    z-index: 0;
}

.floating-shape {
    position: absolute;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.03), inset 0 1px 0 rgba(255, 255, 255, 0.3);
}

.shape-1 { width: 120px; height: 120px; border-radius: 50%; top: 10%; left: 10%; animation: float1 12s ease-in-out infinite; }
.shape-2 { width: 80px; height: 80px; border-radius: 20px; top: 20%; right: 15%; animation: float2 8s ease-in-out infinite; }
.shape-3 { width: 150px; height: 150px; border-radius: 30px; bottom: 15%; left: 15%; animation: float3 15s ease-in-out infinite; }
.shape-4 { width: 60px; height: 60px; border-radius: 50%; bottom: 25%; right: 20%; animation: float4 10s ease-in-out infinite; }
.shape-5 { width: 100px; height: 100px; border-radius: 15px; top: 50%; left: 5%; animation: float5 14s ease-in-out infinite; }
.shape-6 { width: 90px; height: 90px; border-radius: 50%; top: 60%; right: 10%; animation: float6 11s ease-in-out infinite; }
.shape-7 { width: 70px; height: 70px; border-radius: 25px; top: 80%; left: 50%; animation: float7 9s ease-in-out infinite; }

@keyframes float1 { 0%, 100% { transform: translate(0, 0) rotate(0deg); } 25% { transform: translate(20px, -30px) rotate(90deg); } 50% { transform: translate(-10px, -20px) rotate(180deg); } 75% { transform: translate(-25px, 15px) rotate(270deg); } }
@keyframes float2 { 0%, 100% { transform: translate(0, 0) rotate(0deg); } 33% { transform: translate(-30px, 20px) rotate(120deg); } 66% { transform: translate(25px, -15px) rotate(240deg); } }
@keyframes float3 { 0%, 100% { transform: translate(0, 0) rotate(0deg); } 20% { transform: translate(15px, -25px) rotate(72deg); } 40% { transform: translate(-20px, -10px) rotate(144deg); } 60% { transform: translate(-15px, 20px) rotate(216deg); } 80% { transform: translate(30px, 10px) rotate(288deg); } }
@keyframes float4 { 0%, 100% { transform: translate(0, 0) rotate(0deg); } 50% { transform: translate(-20px, -30px) rotate(180deg); } }
@keyframes float5 { 0%, 100% { transform: translate(0, 0) rotate(0deg); } 25% { transform: translate(-15px, 25px) rotate(90deg); } 50% { transform: translate(20px, 15px) rotate(180deg); } 75% { transform: translate(10px, -20px) rotate(270deg); } }
@keyframes float6 { 0%, 100% { transform: translate(0, 0) rotate(0deg); } 30% { transform: translate(25px, -20px) rotate(108deg); } 60% { transform: translate(-15px, 25px) rotate(216deg); } }
@keyframes float7 { 0%, 100% { transform: translate(0, 0) rotate(0deg); } 40% { transform: translate(-25px, -15px) rotate(144deg); } 80% { transform: translate(20px, -25px) rotate(288deg); } }

.navbar {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    padding: 1.5rem 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 18px;
    margin: 1rem;
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.navbar-brand {
    font-size: 1.75rem;
    font-weight: bold;
    color: #374151;
    text-shadow: 0 2px 15px rgba(55, 65, 81, 0.1);
}

.navbar-nav {
    display: flex;
    list-style: none;
    gap: 1.5rem;
    align-items: center;
}

.nav-link {
    text-decoration: none;
    color: #6b7280;
    font-size: 0.95rem;
    padding: 0.75rem 1.25rem;
    border-radius: 18px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.nav-link:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #374151;
    transform: translateY(-2px);
}

.nav-link.active {
    background: rgba(255, 255, 255, 0.18);
    color: #374151;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03), inset 0 1px 0 rgba(255, 255, 255, 0.4);
}

.container {
    max-width: 1400px;
    margin: 3rem auto;
    padding: 0 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 3rem;
    position: relative;
    z-index: 1;
}

.message {
    padding: 1rem;
    border-radius: 16px;
    font-size: 0.9rem;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    position: relative;
    z-index: 2;
}

.message.success {
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
    border: 1px solid rgba(34, 197, 94, 0.2);
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.1);
}

.message.error {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.2);
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.1);
}

.page-header {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    padding: 3rem;
    border-radius: 28px;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.08), 0 10px 30px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.6), inset 0 -1px 0 rgba(0, 0, 0, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.4);
    position: relative;
    z-index: 2;
    animation: float 6s ease-in-out infinite;
}

.page-title {
    font-size: 2rem;
    font-weight: bold;
    color: #374151;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 15px rgba(55, 65, 81, 0.1);
}

.page-subtitle {
    color: #6b7280;
    font-size: 1rem;
    opacity: 0.8;
}

.section {
    margin-bottom: 3rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    text-shadow: 0 2px 15px rgba(55, 65, 81, 0.1);
}

.btn {
    padding: 1rem 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 18px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    color: #374151;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.4);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn:hover {
    transform: translateY(-3px);
    background: rgba(255, 255, 255, 0.18);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.6);
    border-color: rgba(255, 255, 255, 0.4);
}

.btn:active {
    transform: translateY(-1px);
}

.btn-primary {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.btn-primary:hover {
    background: rgba(59, 130, 246, 0.25);
}

.btn-outline {
    background: rgba(255, 255, 255, 0.08);
    color: #6b7280;
    border-color: rgba(255, 255, 255, 0.3);
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #374151;
}

.btn-success {
    background: rgba(34, 197, 94, 0.15);
    color: #16a34a;
}

.btn-success:hover {
    background: rgba(34, 197, 94, 0.25);
}

.btn-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #dc2626;
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.25);
}

.campaigns-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
}

.campaign-card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    border-radius: 28px;
    padding: 2rem;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.08), 0 10px 30px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.6), inset 0 -1px 0 rgba(0, 0, 0, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.4);
    transition: all 0.3s ease;
}

.campaign-card:hover {
    transform: translateY(-3px);
    background: rgba(255, 255, 255, 0.18);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.6);
}

.campaign-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.campaign-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.25rem;
}

.campaign-creator {
    font-size: 0.875rem;
    color: #6b7280;
    opacity: 0.8;
}

.campaign-description {
    color: #6b7280;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
    line-height: 1.5;
    opacity: 0.9;
}

.campaign-stats {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.stat {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    padding: 0.75rem 1rem;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    transition: all 0.3s ease;
}

.stat:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.12);
}

.stat-value {
    font-weight: 600;
    color: #374151;
}

.stat-label {
    color: #6b7280;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.8;
}

.campaign-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: flex-end;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    border-radius: 28px;
    border: 1px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.08), 0 10px 30px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.6);
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    z-index: 1000;
}

.modal-content {
    background: rgba(238, 235, 235, 1);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    margin: 5% auto;
    padding: 3rem;
    border-radius: 28px;
    width: 90%;
    max-width: 800px;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.08), 0 10px 30px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.4);
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
    z-index: 2;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.modal-title {
    font-size: 1.75rem;
    font-weight: bold;
    color: #374151;
    text-shadow: 0 2px 15px rgba(55, 65, 81, 0.1);
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.3s ease;
}

.close-btn:hover {
    color: #374151;
    transform: scale(1.1);
}

.volunteer-list {
    margin-top: 1.5rem;
}

.volunteer-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 16px;
    margin-bottom: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    transition: all 0.3s ease;
}

.volunteer-item:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.12);
}

.volunteer-info {
    flex: 1;
}

.volunteer-name {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.volunteer-details {
    font-size: 0.875rem;
    color: #6b7280;
    opacity: 0.8;
    line-height: 1.5;
}

.volunteer-joined {
    font-size: 0.875rem;
    color: #16a34a;
    font-weight: 500;
    margin-top: 0.5rem;
}

.campaign-summary {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    padding: 1.5rem;
    border-radius: 16px;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
}

.summary-title {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
    font-size: 1.25rem;
}

.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.summary-stat {
    text-align: center;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    padding: 1rem;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.summary-stat:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.12);
}

.summary-stat-value {
    font-size: 1.75rem;
    font-weight: bold;
    color: #374151;
}

.summary-stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.8;
}

.no-volunteers {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.loading {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

.form-group {
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 2;
}

.form-label {
    display: block;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.form-input {
    width: 100%;
    padding: 1rem 1.25rem;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 18px;
    font-size: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    color: #374151;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03), inset 0 1px 0 rgba(255, 255, 255, 0.4);
}

.form-input:focus {
    outline: none;
    border-color: rgba(255, 255, 255, 0.5);
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.6), 0 0 0 4px rgba(107, 114, 128, 0.1);
    transform: translateY(-2px);
}

.form-input::placeholder {
    color: #9ca3af;
}

textarea.form-input {
    resize: vertical;
    min-height: 120px;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

.reminder-message {
    background: rgba(251, 191, 36, 0.1);
    color: #d97706;
    border: 1px solid rgba(251, 191, 36, 0.2);
    box-shadow: 0 4px 15px rgba(251, 191, 36, 0.1);
}

.map-iframe {
    width: 100%;
    height: 450px;
    border: 0;
    border-radius: 16px;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

@media (max-width: 768px) {
    body { padding: 1rem; }
    .container { padding: 0 0.5rem; }
    .navbar { padding: 1rem; margin: 0.5rem; }
    .campaigns-grid { grid-template-columns: 1fr; }
    .campaign-stats { flex-wrap: wrap; gap: 1rem; }
    .campaign-actions { flex-direction: column; gap: 1rem; }
    .btn { justify-content: center; width: 100%; }
    .volunteer-item { flex-direction: column; align-items: flex-start; gap: 1rem; }
    .summary-stats { grid-template-columns: 1fr 1fr; }
    .modal-content { padding: 2rem; }
}

@media (max-width: 480px) {
    .page-title { font-size: 1.5rem; }
    .page-subtitle { font-size: 0.9rem; }
    .section-title { font-size: 1.25rem; }
    .campaign-title { font-size: 1.125rem; }
    .map-iframe { height: 300px; }
}

 .navbar {
      background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 0 2rem;
    height: 64px;
    display: flex
;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    position: relative;
    top: -42px;
    width: 100%;
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
    </style>
</head>
<body>
   <nav class="navbar">
        <div class="navbar-brand"> 📢आवाज</div>
        <ul class="navbar-nav">
            <li><a href="index.php" class="nav-link">🏠 Home</a></li>
            <li><a href="map.php" class="nav-link">📈 Map</a></li>
            <li><a href="notice.php" class="nav-link">🔔 Notices</a></li>
            <li><a href="admin.php" class="nav-link">⚙️ Admin</a></li>
            <li><a href="campaign.php" class="nav-link" active>📢 Campaigns</a></li>
        </ul>
    </nav>
    <div class="container">
        <!-- Messages -->
        <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Campaign Management</h1>
            <p class="page-subtitle">Create, manage, and join campaigns to make a difference in your community</p>
        </div>

        <!-- My Campaigns Section -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">My Campaigns</h2>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    ➕ Create Campaign
                </button>
            </div>

            <div class="campaigns-grid">
                <?php if (mysqli_num_rows($my_campaigns) > 0): ?>
                    <?php while ($campaign = mysqli_fetch_assoc($my_campaigns)): ?>
                    <div class="campaign-card">
                        <div class="campaign-header">
                            <div>
                                <h3 class="campaign-title"><?php echo htmlspecialchars($campaign['title']); ?></h3>
                                <p class="campaign-creator">Created by you</p>
                            </div>
                        </div>
                        <p class="campaign-description">
                            <?php echo htmlspecialchars($campaign['description']); ?>
                        </p>
                        <div class="campaign-stats">
                            <div class="stat">
                                <span class="stat-value"><?php echo $campaign['volunteer_count']; ?></span>
                                <span class="stat-label">Volunteers</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?php echo getDaysRemaining($campaign['end_date']); ?></span>
                                <span class="stat-label">Days left</span>
                            </div>
                            <?php if ($campaign['target_volunteers']): ?>
                            <div class="stat">
                                <span class="stat-value"><?php echo $campaign['target_volunteers']; ?></span>
                                <span class="stat-label">Target</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="campaign-actions">
                            <button class="btn btn-outline">✏️ Edit</button>
                            <button class="btn btn-primary" onclick="showVolunteers(<?php echo $campaign['id']; ?>)">👥 Manage</button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No campaigns yet</h3>
                        <p>Create your first campaign to get started!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Joined Campaigns Section -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Joined Campaigns</h2>
                <span class="btn btn-outline" style="cursor: default;">👥 <?php echo mysqli_num_rows($joined_campaigns); ?> Campaigns</span>
            </div>

            <div class="campaigns-grid">
                <?php if (mysqli_num_rows($joined_campaigns) > 0): ?>
                    <?php while ($campaign = mysqli_fetch_assoc($joined_campaigns)): ?>
                    <div class="campaign-card">
                        <div class="campaign-header">
                            <div>
                                <h3 class="campaign-title"><?php echo htmlspecialchars($campaign['title']); ?></h3>
                                <p class="campaign-creator">by <?php echo htmlspecialchars($campaign['creator_name']); ?></p>
                            </div>
                            <span class="campaign-status" style="background-color: #dcfce7; color: #166534; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">Joined</span>
                        </div>
                        <p class="campaign-description">
                            <?php echo htmlspecialchars($campaign['description']); ?>
                        </p>
                        <div class="campaign-stats">
                            <div class="stat">
                                <span class="stat-value"><?php echo $campaign['volunteer_count']; ?></span>
                                <span class="stat-label">Volunteers</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?php echo getDaysRemaining($campaign['end_date']); ?></span>
                                <span class="stat-label">Days left</span>
                            </div>
                            <?php if ($campaign['target_volunteers']): ?>
                            <div class="stat">
                                <span class="stat-value"><?php echo $campaign['target_volunteers']; ?></span>
                                <span class="stat-label">Target</span>
                            </div>
                            <?php endif; ?>
                            <?php if ($campaign['location']): ?>
                            <div class="stat">
                                <span class="stat-value">📍</span>
                                <span class="stat-label"><?php echo htmlspecialchars($campaign['location']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="campaign-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="leave_campaign">
                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                <button type="submit" class="btn btn-outline" style="color: #dc2626; border-color: #dc2626;" 
                                        onclick="return confirm('Are you sure you want to leave this campaign?')">
                                    🚪 Leave Campaign
                                </button>
                            </form>
                            <button class="btn btn-primary">📋 View Details</button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No joined campaigns</h3>
                        <p>Join campaigns below to contribute to your community!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Available Campaigns Section -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Available Campaigns</h2>
                <button class="btn btn-outline">🔍 Filter Campaigns</button>
            </div>

            <div class="campaigns-grid">
                <?php if (mysqli_num_rows($other_campaigns) > 0): ?>
                    <?php while ($campaign = mysqli_fetch_assoc($other_campaigns)): ?>
                    <div class="campaign-card">
                        <div class="campaign-header">
                            <div>
                                <h3 class="campaign-title"><?php echo htmlspecialchars($campaign['title']); ?></h3>
                                <p class="campaign-creator">by <?php echo htmlspecialchars($campaign['creator_name']); ?></p>
                            </div>
                        </div>
                        <p class="campaign-description">
                            <?php echo htmlspecialchars($campaign['description']); ?>
                        </p>
                        <div class="campaign-stats">
                            <div class="stat">
                                <span class="stat-value"><?php echo $campaign['volunteer_count']; ?></span>
                                <span class="stat-label">Volunteers</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?php echo getDaysRemaining($campaign['end_date']); ?></span>
                                <span class="stat-label">Days left</span>
                            </div>
                            <?php if ($campaign['target_volunteers']): ?>
                            <div class="stat">
                                <span class="stat-value"><?php echo $campaign['target_volunteers']; ?></span>
                                <span class="stat-label">Target</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="campaign-actions">
                            <form method="POST" style="display: inline;" class="join-campaign-form">
                                <input type="hidden" name="action" value="join_campaign">
                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                <button type="submit" class="btn btn-success join-campaign-btn" data-campaign-title="<?php echo htmlspecialchars($campaign['title']); ?>">
                                    🤝 Join as Volunteer
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No campaigns available</h3>
                        <p>All available campaigns have been joined or check back later for new campaigns!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Volunteer Management Modal -->
    <div id="volunteerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Campaign Volunteers</h3>
                <button class="close-btn" onclick="closeVolunteerModal()">&times;</button>
            </div>
            <div id="volunteerModalContent">
                <div class="loading">Loading volunteers...</div>
            </div>
        </div>
    </div>

    <!-- Create Campaign Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Create New Campaign</h3>
                <button class="close-btn" onclick="closeCreateModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_campaign">
                
                <div class="form-group">
                    <label class="form-label">Campaign Title *</label>
                    <input type="text" name="title" class="form-input" required placeholder="Enter campaign title">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-input" required placeholder="Describe your campaign goals and activities"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Campaign Starting after?</label>
                    <input type="number" name="duration_days" class="form-input" required min="1" placeholder="From today how much day is remainign for campaign?">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Target Volunteers</label>
                    <input type="number" name="target_volunteers" class="form-input" min="1" placeholder="How many volunteers do you need?">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-input" placeholder="Where will this campaign take place?">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeCreateModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Campaign</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').style.display = 'block';
        }

        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }

        function showVolunteers(campaignId) {
            const modal = document.getElementById('volunteerModal');
            const content = document.getElementById('volunteerModalContent');
            
            modal.style.display = 'block';
            content.innerHTML = '<div class="loading">Loading volunteers...</div>';
            
            fetch(`?action=get_volunteers&campaign_id=${campaignId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        displayVolunteers(data);
                    } else {
                        content.innerHTML = `<div class="no-volunteers"><h3>Error</h3><p>${data.message}</p></div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = '<div class="no-volunteers"><h3>Error</h3><p>Failed to load volunteer data</p></div>';
                });
        }

        function displayVolunteers(data) {
            const content = document.getElementById('volunteerModalContent');
            const campaign = data.campaign;
            const volunteers = data.volunteers;
            
            let html = `
                <div class="campaign-summary">
                    <div class="summary-title">${campaign.title}</div>
                    <p style="color: #64748b; margin-bottom: 0.75rem;">${campaign.description}</p>
                    <div class="summary-stats">
                        <div class="summary-stat">
                            <div class="summary-stat-value">${data.total_volunteers}</div>
                            <div class="summary-stat-label">Total Volunteers</div>
                        </div>
                        <div class="summary-stat">
                            <div class="summary-stat-value">${campaign.target_volunteers || 'No Limit'}</div>
                            <div class="summary-stat-label">Target</div>
                        </div>
                        <div class="summary-stat">
                            <div class="summary-stat-value">${campaign.duration_days}</div>
                            <div class="summary-stat-label">Duration (Days)</div>
                        </div>
                    </div>
                </div>
            `;
            
            if (volunteers.length > 0) {
                html += '<div class="volunteer-list">';
                volunteers.forEach(volunteer => {
                    const joinedDate = new Date(volunteer.joined_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                    
                    html += `
                        <div class="volunteer-item">
                            <div class="volunteer-info">
                                <div class="volunteer-name">👤 ${volunteer.name}</div>
                                <div class="volunteer-details">
                                    📧 ${volunteer.email}<br>
                                    📱 ${volunteer.phone}
                                </div>
                                <div class="volunteer-joined">✅ Joined on ${joinedDate}</div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            } else {
                html += `
                    <div class="no-volunteers">
                        <h3>No Volunteers Yet</h3>
                        <p>No one has joined this campaign yet. Share it to get more volunteers!</p>
                    </div>
                `;
            }
            
            content.innerHTML = html;
        }

        function closeVolunteerModal() {
            document.getElementById('volunteerModal').style.display = 'none';
        }

        window.addEventListener('click', function(e) {
            const createModal = document.getElementById('createModal');
            const volunteerModal = document.getElementById('volunteerModal');
            
            if (e.target === createModal) {
                closeCreateModal();
            }
            if (e.target === volunteerModal) {
                closeVolunteerModal();
            }
        });

        const messages = document.querySelectorAll('.message');
        messages.forEach(function(message) {
            if (message.classList.contains('success')) {
                setTimeout(function() {
                    message.style.display = 'none';
                }, 5000);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.join-campaign-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const campaignTitle = this.dataset.campaignTitle;
                    const confirmJoin = confirm(`Are you sure you want to join the campaign "${campaignTitle}" as a volunteer?`);
                    if (confirmJoin) {
                        this.closest('form').submit();
                    }
                });
            });
        });

        
    </script>
    <script type="text/javascript">
        var gk_isXlsx = false;
        var gk_xlsxFileLookup = {};
        var gk_fileData = {};
        function filledCell(cell) {
          return cell !== '' && cell != null;
        }
        function loadFileData(filename) {
        if (gk_isXlsx && gk_xlsxFileLookup[filename]) {
            try {
                var workbook = XLSX.read(gk_fileData[filename], { type: 'base64' });
                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];

                var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, blankrows: false, defval: '' });
                var filteredData = jsonData.filter(row => row.some(filledCell));

                var headerRowIndex = filteredData.findIndex((row, index) =>
                  row.filter(filledCell).length >= filteredData[index + 1]?.filter(filledCell).length
                );
                if (headerRowIndex === -1 || headerRowIndex > 25) {
                  headerRowIndex = 0;
                }

                var csv = XLSX.utils.aoa_to_sheet(filteredData.slice(headerRowIndex)); 
                csv = XLSX.utils.sheet_to_csv(csv, { header: 1 });
                return csv;
            } catch (e) {
                console.error(e);
                return "";
            }
        }
        return gk_fileData[filename] || "";
        }
        </script>
    <div class="floating-shapes">
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
        <div class="floating-shape shape-4"></div>
        <div class="floating-shape shape-5"></div>
        <div class="floating-shape shape-6"></div>
        <div class="floating-shape shape-7"></div>
    </div>
</body>
</html>

<?php
mysqli_close($conn);
?>