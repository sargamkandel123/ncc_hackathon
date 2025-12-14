<?php
session_start();

include 'config.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php"); // Redirect to login page if not authenticated
    exit;
}

// Get user details from session (assuming login sets them as provided)
$user_id = $_SESSION['user_id'] ?? null;
$user_email = $_SESSION['user_email'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'User';
$user_type = $_SESSION['user_type'] ?? 'user';
$admin_con = $_SESSION['admin_con'] ?? 0; // Assuming 1 for admin

// Get query parameters for filtering
$category = $_GET['category'] ?? '';
$priority = $_GET['priority'] ?? '';
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Build SQL query
$sql = "SELECT id, title, category, priority, content, admin_id, views, status, depart, created_at, updated_at 
        FROM notices 
        WHERE 1=1";
$params = [];
$types = "";
$bind_params = [];

if ($category) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}
if ($priority) {
    $sql .= " AND priority = ?";
    $params[] = $priority;
    $types .= "s";
}
if ($search) {
    $sql .= " AND (title LIKE ? OR content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}
if ($status) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC LIMIT 50"; // Limit for performance

$notices = [];
$error = null;

if ($stmt = $conn->prepare($sql)) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Format created_at (current date: Dec 15, 2025)
        $createdDate = new DateTime($row['created_at']);
        $formattedDate = $createdDate->format('M d, Y');
        $row['created_at'] = $formattedDate;
        $row['fullContent'] = $row['content']; // Full content as is
        $row['department'] = $row['depart'] ?? ''; // Department if exists
        $notices[] = $row;
    }
    $stmt->close();
} else {
    $error = "Query preparation failed: " . $conn->error;
}

$conn->close();

// Helper functions
function getCategoryIcon($category) {
    $icons = ['announcement' => 'bullhorn', 'event' => 'calendar-alt', 'alert' => 'exclamation-triangle', 'service' => 'cogs'];
    return $icons[$category] ?? 'question-circle';
}

function getPriorityIcon($priority) {
    $icons = ['high' => 'exclamation-triangle', 'medium' => 'star', 'low' => 'check-circle'];
    return $icons[$priority] ?? 'circle';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Board - Secure PHP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
        }
        .notice-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        .notice-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-radial-gradient(circle, transparent 0%, rgba(255,255,255,0.1) 10%, transparent 20%);
            animation: rotate 20s linear infinite;
        }
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .notice-header h1 {
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .user-info {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: white;
        }
        .notice-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            background: white;
            position: relative;
        }
        .notice-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
        }
        .notice-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        .priority-badge {
            font-size: 0.75em;
            font-weight: 600;
            padding: 0.5em 1em;
            border-radius: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .priority-high { background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; }
        .priority-medium { background: linear-gradient(135deg, #ffd93d, #f6d365); color: #333; }
        .priority-low { background: linear-gradient(135deg, #26de81, #20bf6b); color: white; }
        .category-badge {
            font-size: 0.75em;
            font-weight: 500;
            padding: 0.4em 0.8em;
            border-radius: 20px;
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .notice-content {
            max-height: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            color: #555;
            line-height: 1.5;
        }
        .view-count {
            font-size: 0.85em;
            color: #6c757d;
            font-weight: 500;
        }
        .status-inactive {
            opacity: 0.7;
            background: #f8f9fa !important;
        }
        .status-inactive::before {
            background: #6c757d;
        }
        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            backdrop-filter: blur(10px);
        }
        .search-box {
            position: relative;
            border-radius: 50px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            z-index: 2;
        }
        .search-box input {
            padding: 12px 20px 12px 50px;
            border: none;
            background: #f8f9fa;
            width: 100%;
        }
        .search-box input:focus {
            background: white;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
        }
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
        }
        .btn-read-more {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 0.6em 1.5em;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-read-more:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .fade-in {
            animation: fadeInUp 0.8s ease forwards;
            opacity: 0;
            transform: translateY(30px);
        }
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .card-wrapper {
            animation-delay: calc(var(--i) * 0.1s);
        }
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
        .error-msg {
            text-align: center;
            color: #dc3545;
            padding: 2rem;
        }
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .notice-header { padding: 2rem 0; }
            .filter-section { padding: 1.5rem; }
            .user-info { position: static; text-align: center; margin-top: 1rem; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="notice-header text-center position-relative">
            <div class="container">
                <h1 class="display-3 fw-bold mb-2 animate__animated animate__bounceIn"><i class="fas fa-bullhorn me-4"></i>Notice Board</h1>
                <p class="lead mb-0 animate__animated animate__fadeIn" style="animation-delay: 0.3s;">Stay Informed • Stay Connected • Stay Ahead</p>
                <div class="user-info">
                    <i class="fas fa-user me-1"></i>Welcome, <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_type); ?>)
                    <?php if ($admin_con): ?><i class="fas fa-crown ms-2" title="Admin"></i><?php endif; ?>
                    <a href="logout.php" class="ms-3 btn btn-sm btn-light">Logout</a>
                </div>
            </div>
        </div>

        <!-- Filters and Search Form -->
        <div class="container">
            <div class="filter-section animate__animated animate__fadeIn">
                <form method="GET" class="row align-items-center g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted">Filter by Category:</label>
                        <select name="category" class="form-select rounded-pill shadow-sm" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <option value="announcement" <?php echo ($category === 'announcement') ? 'selected' : ''; ?>>Announcement</option>
                            <option value="event" <?php echo ($category === 'event') ? 'selected' : ''; ?>>Event</option>
                            <option value="alert" <?php echo ($category === 'alert') ? 'selected' : ''; ?>>Alert</option>
                            <option value="service" <?php echo ($category === 'service') ? 'selected' : ''; ?>>Service</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted">Filter by Priority:</label>
                        <select name="priority" class="form-select rounded-pill shadow-sm" onchange="this.form.submit()">
                            <option value="">All Priorities</option>
                            <option value="high" <?php echo ($priority === 'high') ? 'selected' : ''; ?>>High</option>
                            <option value="medium" <?php echo ($priority === 'medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="low" <?php echo ($priority === 'low') ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted">Status:</label>
                        <select name="status" class="form-select rounded-pill shadow-sm" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted d-none d-md-block">Search:</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search notices by keyword..." oninput="debounceSubmit(this.form, 500)">
                        </div>
                    </div>
                </form>
            </div>

            <!-- Notices List -->
            <div class="row" id="noticesContainer">
                <?php if ($error): ?>
                    <div class="col-12"><div class="alert alert-danger error-msg"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div></div>
                <?php elseif (empty($notices)): ?>
                    <div class="col-12"><div class="text-center py-5"><i class="fas fa-inbox fa-3x text-muted mb-3"></i><h5 class="text-muted">No notices found.</h5></div></div>
                <?php else: ?>
                    <?php foreach ($notices as $index => $notice): ?>
                        <div class="col-lg-6 col-xl-3 mb-4 card-wrapper fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                            <div class="card notice-card h-100 <?php echo ($notice['status'] !== 'active') ? 'status-inactive' : ''; ?>">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <span class="category-badge"><i class="fas fa-<?php echo getCategoryIcon($notice['category']); ?> me-1"></i><?php echo ucfirst($notice['category']); ?></span>
                                        <span class="priority-badge priority-<?php echo $notice['priority']; ?>"><i class="fas fa-<?php echo getPriorityIcon($notice['priority']); ?> me-1"></i><?php echo ucfirst($notice['priority']); ?></span>
                                    </div>
                                    <h5 class="card-title fw-bold mb-3 text-dark"><?php echo htmlspecialchars($notice['title']); ?></h5>
                                    <p class="card-text notice-content"><?php echo htmlspecialchars(substr($notice['content'], 0, 150)) . '...'; ?></p>
                                    <?php if ($notice['department']): ?>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($notice['department']); ?></small>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center mt-4">
                                        <small class="view-count"><i class="fas fa-eye me-2"></i><?php echo number_format($notice['views']); ?> views</small>
                                        <small class="text-muted fw-medium"><?php echo $notice['created_at']; ?></small>
                                    </div>
                                    <button class="btn btn-read-more text-white mt-3 w-100 <?php echo ($notice['status'] !== 'active') ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                                            <?php echo ($notice['status'] !== 'active') ? 'disabled' : ''; ?> 
                                            onclick="<?php echo ($notice['status'] === 'active') ? "viewNotice({$notice['id']}, '" . addslashes($notice['fullContent']) . "', '" . addslashes($notice['category']) . "', '" . addslashes($notice['priority']) . "', " . $notice['views'] . ", '" . addslashes($notice['created_at']) . "', '" . addslashes($notice['department']) . "')" : ''; ?>">
                                        <i class="fas fa-arrow-right me-2"></i><?php echo ($notice['status'] === 'active') ? 'Read Full Notice' : 'Archived'; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination (Placeholder; implement if needed) -->
            <nav aria-label="Notice pagination" class="mt-5 animate__animated animate__fadeIn" style="animation-delay: 0.5s;">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled"><a class="page-link rounded-pill shadow-sm" href="#"><i class="fas fa-chevron-left me-1"></i>Previous</a></li>
                    <li class="page-item active"><a class="page-link rounded-pill shadow-sm" href="#">1</a></li>
                    <li class="page-item"><a class="page-link rounded-pill shadow-sm" href="#">2</a></li>
                    <li class="page-item"><a class="page-link rounded-pill shadow-sm" href="#">Next<i class="fas fa-chevron-right ms-1"></i></a></li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Modal for Full Notice -->
    <div class="modal fade" id="noticeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle"><i class="fas fa-info-circle me-2"></i></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Full content will be loaded via JS -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary rounded-pill" id="modalAction"><i class="fas fa-download me-1"></i>Download PDF</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modal = new bootstrap.Modal(document.getElementById('noticeModal'));

        // Debounce for search submit
        function debounceSubmit(form, wait) {
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(() => form.submit(), wait);
        }

        // View notice function (data passed from PHP onclick)
        function viewNotice(id, fullContent, category, priority, views, createdAt, department) {
            // Simulate views increment (could POST to PHP endpoint)
            views++;

            document.getElementById('modalTitle').innerHTML = `<i class="fas fa-${getCategoryIcon(category)} me-2"></i>${category.toUpperCase()} Notice`;
            let modalBody = `
                <div class="priority-badge priority-${priority} mb-3 d-inline-block"><i class="fas fa-${getPriorityIcon(priority)} me-1"></i>${capitalize(priority)} Priority</div>
                <p class="text-muted mb-0"><i class="fas fa-calendar me-2"></i>${createdAt} • <i class="fas fa-eye me-2"></i>${views.toLocaleString()} views</p>
            `;
            if (department) {
                modalBody += `<p class="text-muted mb-0"><i class="fas fa-building me-2"></i>Department: ${department}</p>`;
            }
            modalBody += `
                <hr class="my-3">
                <div class="fs-5 lh-lg">${fullContent.replace(/\n/g, '<br>')}</div>
            `;
            document.getElementById('modalBody').innerHTML = modalBody;

            document.getElementById('modalAction').onclick = () => {
                const win = window.open('', '_blank');
                win.document.write(`<html><body><pre>${fullContent}</pre><p>ID: ${id} | Views: ${views}</p></body></html>`);
                win.document.close();
            };
            modal.show();
        }

        function getCategoryIcon(category) {
            const icons = { announcement: 'bullhorn', event: 'calendar-alt', alert: 'exclamation-triangle', service: 'cogs' };
            return icons[category] || 'question-circle';
        }

        function getPriorityIcon(priority) {
            const icons = { high: 'exclamation-triangle', medium: 'star', low: 'check-circle' };
            return icons[priority] || 'circle';
        }

        function capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
    </script>
</body>
</html>