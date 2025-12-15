<?php
// config.php content (REQUIRED FOR CODE TO RUN)
/*
$servername = "127.0.0.1"; // As per your screenshot
$username = "your_db_user";
$password = "your_db_password";
$dbname = "aawaz2.0"; // As per your screenshot

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
*/

include 'config.php';

// --- User ID to fetch (Assuming we are viewing user 1's profile) ---
$user_id = $_GET['id']; 

// --- 1. Fetch User Data from 'users' table ---
$user_sql = "SELECT id, first_name, last_name, image, email, phone, trust_member, verified, status, created_at, lon, lat 
             FROM users 
             WHERE id = $user_id";

$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    die("User not found.");
}

$full_name = $user['first_name'] . ' ' . $user['last_name'];
$email = $user['email'];
$phone = $user['phone'];
$status_class = ($user['status'] === 'active') ? 'status-active' : 'status-inactive';
$status_text = ($user['status'] === 'active') ? 'Active User' : 'Inactive User';
$verified_class = ($user['verified'] === 'true' || $user['verified'] === 'yes') ? 'status-verified' : 'status-pending';
$verified_text = ($user['verified'] === 'true' || $user['verified'] === 'yes') ? 'Verified' : 'Unverified';
$trust_member = ($user['trust_member'] === 'yes') ? 'yes' : 'No';
$joined_date = date('Y-m-d', strtotime($user['created_at']));
$location = "Latitude: " . $user['lat'] . ", Longitude: " . $user['lon'];


// --- 2. Fetch Posts Data from 'problem_posts' table ---
$posts_sql = "SELECT title, description, category, priority, status, views_count, likes_count, comments_count, photo_url
              FROM problem_posts 
              WHERE user_id = $user_id 
              ORDER BY created_at DESC";

$posts_result = mysqli_query($conn, $posts_sql);
$posts = [];
$status_counts = ['all' => 0, 'active' => 0, 'working' => 0, 'completing' => 0];

while ($row = mysqli_fetch_assoc($posts_result)) {
    $posts[] = $row;
    $status_counts['all']++;
    $status_counts[$row['status']] = isset($status_counts[$row['status']]) ? $status_counts[$row['status']] + 1 : 1;
}

// Close connection
mysqli_close($conn);

// Helper function for priority badge styling
function get_priority_details($priority) {
    switch (strtolower($priority)) {
        case 'low': return ['class' => 'priority-low', 'text' => 'Low Priority'];
        case 'medium': return ['class' => 'priority-medium', 'text' => 'Medium Priority'];
        case 'high': return ['class' => 'priority-high', 'text' => 'High Priority'];
        case 'critical': return ['class' => 'priority-critical', 'text' => 'Critical Priority'];
        default: return ['class' => 'priority-low', 'text' => 'Low Priority'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - <?php echo $full_name; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #4444e8; /* Vibrant Indigo Theme */
            --secondary-color: #f0f0ff;
            --text-color-dark: #2c3e50;
            --text-color-medium: #7f8c8d;
            --background-white: #ffffff;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--secondary-color);
            color: var(--text-color-dark);
            line-height: 1.6;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            gap: 30px;
        }
        .profile-column {
            flex: 1;
            min-width: 350px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        .posts-column {
            flex: 2;
        }
        /* Shared Card Styles */
        .card {
            background: var(--background-white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            transition: box-shadow 0.3s ease;
        }
        /* Profile Header Card */
        .profile-header {
            text-align: center;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            padding: 3px;
            margin-bottom: 15px;
        }
        .profile-info h1 {
            color: var(--primary-color);
            margin-bottom: 5px;
            font-size: 2em;
        }
        .profile-info p {
            color: var(--text-color-medium);
            margin-bottom: 5px;
            font-size: 0.95em;
        }
        .status-badges {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 20px;
            color: white;
            font-size: 0.85em;
            font-weight: 600;
        }
        .status-badge i {
            margin-right: 5px;
        }
        .status-active { background: #2ecc71; }
        .status-verified { background: #f39c12; }
        .status-inactive, .status-pending { background: #95a5a6; } /* Grey for inactive/pending */

        /* Profile Details Card */
        .section-title {
            font-size: 1.5em;
            color: var(--text-color-dark);
            margin-bottom: 25px;
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
        }
        .section-title i {
            margin-right: 10px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        .detail-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed #ecf0f1;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            width: 150px;
            flex-shrink: 0;
        }
        .detail-label i {
            margin-right: 8px;
            font-size: 1.1em;
            color: var(--primary-color);
        }
        .detail-value {
            color: var(--text-color-dark);
            flex-grow: 1;
        }

        /* Posts Section */
        .posts-section {
            padding: 30px;
        }
        .filters {
            display: flex;
            justify-content: flex-start;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 10px 20px;
            border: 2px solid var(--primary-color);
            border-radius: 25px;
            background: var(--background-white);
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        .filter-btn.active,
        .filter-btn:hover {
            background: var(--primary-color);
            color: white;
        }
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }
        .post-card {
            border: 1px solid #e0e0e0;
            padding: 0;
            overflow: hidden;
            background: #f7f7f7; /* Slightly distinct background for posts */
        }
        .post-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-hover-shadow);
        }
        .post-content {
            padding: 20px;
        }
        .post-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
        }
        .post-title {
            font-size: 1.4em;
            font-weight: 700;
            color: var(--text-color-dark);
            margin-bottom: 8px;
        }
        .post-desc {
            color: var(--text-color-medium);
            margin-bottom: 15px;
            font-size: 0.9em;
            height: 40px;
            overflow: hidden;
        }
        .post-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.85em;
            color: var(--text-color-medium);
        }
        .post-meta i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        .priority-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
            display: inline-block;
        }
        .priority-low { background: #d4edda; color: #155724; }
        .priority-medium { background: #fff3cd; color: #856404; }
        .priority-high { background: #f8d7da; color: #721c24; }
        .priority-critical { background: #dc3545; color: white; }
        .post-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
            font-size: 0.9em;
            color: var(--text-color-medium);
        }
        .stat-number {
            font-weight: bold;
            color: var(--primary-color);
            display: block;
            font-size: 1.1em;
        }
        .stat-label {
            font-size: 0.85em;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .container {
                flex-direction: column;
            }
            .profile-column, .posts-column {
                min-width: 100%;
            }
            .filters {
                justify-content: center;
            }
            .posts-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-column">
            <div class="profile-header card">
                <img src="uploads/<?php echo $user['image']; ?>" alt="Profile Avatar" class="profile-avatar">
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($full_name); ?></h1>
                    <div class="status-badges">
                        <span class="status-badge <?php echo $status_class; ?>"><i class="fas fa-circle-check"></i> <?php echo $status_text; ?></span>
                        <span class="status-badge <?php echo $verified_class; ?>"><i class="fas fa-shield-alt"></i> <?php echo $verified_text; ?></span>
                    </div>
                </div>
            </div>

            <div class="profile-details card">
                <h2 class="section-title"><i class="fas fa-user-gear"></i> Account Information</h2>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($email); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-phone"></i> Phone</span>
                        <span class="detail-value"><?php echo htmlspecialchars($phone); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-map-marker-alt"></i> Location</span>
                        <span class="detail-value"><?php echo $location; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-handshake"></i> Trust Member</span>
                        <span class="detail-value"><?php echo $trust_member; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label"><i class="fas fa-calendar-alt"></i> Joined</span>
                        <span class="detail-value"><?php echo $joined_date; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="posts-column card posts-section">
            <h2 class="section-title"><i class="fas fa-list-check"></i> My Problem Posts (<?php echo $status_counts['all']; ?>)</h2>
            <div class="filters">
                <button class="filter-btn active" data-filter="all">All (<?php echo $status_counts['all']; ?>)</button>
                <button class="filter-btn" data-filter="active">Active (<?php echo $status_counts['active']; ?>)</button>
                <button class="filter-btn" data-filter="working">Working (<?php echo $status_counts['working']; ?>)</button>
                <button class="filter-btn" data-filter="completing">Completing (<?php echo $status_counts['completing']; ?>)</button>
            </div>
            <div class="posts-grid" id="postsGrid">
                
                <?php foreach ($posts as $post): ?>
                    <?php 
                        $priority_details = get_priority_details($post['priority']);
                        $image_src = $post['photo_url'];
                    ?>
                    <div class="post-card card" data-status="<?php echo htmlspecialchars($post['status']); ?>">
                        <img src="uploads/<?php echo htmlspecialchars($image_src); ?>" alt="Post Photo" class="post-image">
                        <div class="post-content">
                            <div class="post-meta">
                                <span><i class="fas fa-tools"></i> <?php echo htmlspecialchars($post['category']); ?></span>
                                <span class="priority-badge <?php echo $priority_details['class']; ?>"><?php echo $priority_details['text']; ?></span>
                            </div>
                            <div class="post-title"><?php echo htmlspecialchars($post['title']); ?></div>
                            <div class="post-desc"><?php echo htmlspecialchars(substr($post['description'], 0, 100)) . '...'; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
            <?php if (empty($posts)): ?>
                <p style="text-align: center; color: var(--text-color-medium); margin-top: 50px;">
                    <i class="fas fa-inbox" style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                    This user has not submitted any problem posts yet.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Filter functionality for posts (Updated to work with dynamically generated content)
        const filterBtns = document.querySelectorAll('.filter-btn');
        const postsGrid = document.getElementById('postsGrid');
        
        // This query needs to be inside the script if posts are rendered after page load, but here they are server-rendered.
        const posts = postsGrid.querySelectorAll('.post-card');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Remove active class from all buttons
                filterBtns.forEach(b => b.classList.remove('active'));
                // Add active to clicked
                btn.classList.add('active');

                const filterValue = btn.getAttribute('data-filter');
                posts.forEach(post => {
                    const postStatus = post.getAttribute('data-status');
                    
                    // Check if the filter is 'all' or matches the post's status
                    if (filterValue === 'all' || postStatus === filterValue) {
                        post.style.display = 'block';
                    } else {
                        post.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>