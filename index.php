<?php
session_start();
include 'config.php';
include "noti.php";


$message = '';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $desc = filter_input(INPUT_POST, 'desc', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
    $userid = $_SESSION['user_id'];
    $lon = filter_input(INPUT_POST, 'lon', FILTER_VALIDATE_FLOAT);
    $lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
    $level = filter_input(INPUT_POST, 'level', FILTER_SANITIZE_STRING);
    $fileName = $_FILES['file']['name'];
    $fileTmp = $_FILES['file']['tmp_name'];
    $uploadDir = "Uploads/";

    if (empty($title) || empty($desc) || empty($category) || empty($location) || empty($level) || empty($fileName) || $lat === false || $lon === false) {
        $message = '<div class="alert alert-danger">All fields are required and must be valid.</div>';
    } else {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['file']['type'], $allowedTypes)) {
            $message = '<div class="alert alert-danger">Only JPEG, PNG, or GIF files are allowed.</div>';
        } else {
            $date = new DateTime('now', new DateTimeZone('Asia/Kathmandu')); // Adjust timezone for +0545
            $weekStart = $date->modify('monday this week')->format('Y-m-d 00:00:00');
            $weekEnd = $date->modify('sunday this week')->format('Y-m-d 23:59:59');

            // Check the number of posts by the user in the current week
            $sqlCount = "SELECT COUNT(*) as post_count 
                         FROM problem_posts 
                         WHERE user_id = ? 
                         AND created_at BETWEEN ? AND ?";
            $stmtCount = mysqli_prepare($conn, $sqlCount);
            if (!$stmtCount) {
                $message = '<div class="alert alert-danger">Database error: Unable to prepare query.</div>';
            } else {
                mysqli_stmt_bind_param($stmtCount, "iss", $userid, $weekStart, $weekEnd);
                mysqli_stmt_execute($stmtCount);
                $resultCount = mysqli_stmt_get_result($stmtCount);
                $rowCount = mysqli_fetch_assoc($resultCount);
                $postCount = $rowCount['post_count'];
                mysqli_stmt_close($stmtCount);

                // Check if the user has reached the weekly post limit
                if ($postCount >= 2) {
                    $message = '<script>alert("You can only post 2 problems per week.")</script> ' . (new DateTime('next monday', new DateTimeZone('Asia/Kathmandu')))->format('Y-m-d') . '.</div>';
                } else {
                    // Create upload directory if it doesn't exist
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    // Generate unique file name to avoid overwrites
                    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                    $uniqueFileName = uniqid('img_') . '.' . $fileExt;
                    $filePath = $uploadDir . $uniqueFileName;

                    // Handle file upload
                    if (move_uploaded_file($fileTmp, $filePath)) {
                        // Insert post using prepared statement
                        $sql = "INSERT INTO problem_posts 
                                (user_id, title, description, category, photo_url, location_name, latitude, longitude, status, priority, views_count, likes_count, created_at, updated_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, 0, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
                        $stmt = mysqli_prepare($conn, $sql);
                        if (!$stmt) {
                            $message = '<div class="alert alert-danger">Database error: Unable to prepare query.</div>';
                        } else {
                            mysqli_stmt_bind_param($stmt, "isssssdss", $userid, $title, $desc, $category, $uniqueFileName, $location, $lat, $lon, $level);
                            $success = mysqli_stmt_execute($stmt);
                            if ($success) {
                                $message = '<div class="alert alert-success">✅ Post added successfully.</div>';
                            } else {
                                $message = '<div class="alert alert-danger">❌ Database insert failed: ' . mysqli_stmt_error($stmt) . '</div>';
                            }
                            mysqli_stmt_close($stmt);
                        }
                    } else {
                        $message = '<div class="alert alert-danger">❌ Failed to upload file.</div>';
                    }
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aawaz - Community Issues</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        /* background: linear-gradient(135deg, #e5e7eb 0%, #ffffff 100%); */
        min-height: 100vh;
            background: linear-gradient(45deg, #d4d4d4, transparent);
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

    .main-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 2rem;
    }

    .content {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(12px);
        border-radius: 20px;
        padding: 2rem;
        /* box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08); */
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .search-container {
        margin-bottom: 2rem;
    }

    .search-box {
        position: relative;
        max-width: 600px;
        margin: 0 auto;
    }

    .search-input {
        width: 100%;
        padding: 1rem 1.5rem 1rem 3rem;
        border: 2px solid transparent;
        /* background: linear-gradient(145deg, #ffffff, #f1f5f9); */
        border-radius: 50px;
        font-size: 1rem;
        outline: none;
        transition: all 0.3s ease;
        box-shadow:
            inset 0 2px 4px rgba(0, 0, 0, 0.05),
            0 4px 16px rgba(0, 0, 0, 0.05);
        /* margin: 0 -40px 00; */
    }

    .search-input:focus {
        border-color: #4f46e5;
        background: white;
        box-shadow:
            inset 0 2px 4px rgba(0, 0, 0, 0.05),
            0 8px 24px rgba(79, 70, 229, 0.2);
        transform: translateY(-2px);
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1.1rem;
    }

    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }

    .section-title {
        font-size: 1.8rem;
        font-weight: bold;
        color: #1e293b;
    }

    .section-subtitle {
        color: #64748b;
        font-size: 0.9rem;
        margin-top: 0.25rem;
    }

    .issue-card {
        background: linear-gradient(145deg, #ffffff, #fafbfc);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        border: 1px solid rgba(226, 232, 240, 0.5);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    .issue-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #4f46e5, #7c3aed);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.3s ease;
    }

    .issue-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
    }

    .issue-card:hover::before {
        transform: scaleX(1);
    }

    .issue-header {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .issue-image {
        width: 80px;
        height: 80px;
        background: linear-gradient(145deg, #f1f5f9, #e2e8f0);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #94a3b8;
        flex-shrink: 0;
        flex: 0 0 30%;
        max-width: 30%;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
    }

    .netflix-image {
        background: linear-gradient(145deg, #000, #333);
        color: #e50914;
        font-weight: bold;
        font-size: 0.7rem;
    }

    .issue-content {
        flex: 1;
    }

    .issue-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .issue-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
        color: #64748b;
    }

    .issue-meta span {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .issue-description {
        color: #475569;
        line-height: 1.6;
        margin-bottom: 1rem;
    }

    .issue-stats {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        font-size: 0.875rem;
    }

    .stat {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #64748b;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        margin-left: auto;
    }

    .status-progress {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .status-reported {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .category-tag {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 1rem;
        font-weight: 500;
    }

    .infrastructure-tag {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .sidebar {

        backdrop-filter: blur(12px);
        border-radius: 20px;
        padding: 1.5rem;
        width: 450px;
        height: fit-content;
    }

    .sidebar-section {
        margin-bottom: 2rem;
    }

    .sidebar-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .contributor-list {
        list-style: none;
    }

    .contributor-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        background: linear-gradient(145deg, #ffffff, #f1f5f9);
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .contributor-item:hover {
        transform: translateX(4px);
        background: linear-gradient(145deg, #e5e7eb, #d1d5db);
    }

    .contributor-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .contributor-avatar {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.75rem;
        font-weight: bold;
    }

    .issue-count {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .report-btn {
        width: 100%;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        border: none;
        padding: 1rem;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(79, 70, 229, 0.3);
    }

    .report-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(79, 70, 229, 0.4);
    }

    .timestamp {
        color: #94a3b8;
        font-size: 0.8rem;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
        z-index: 1000;
        animation: fadeIn 0.3s ease;
    }

    .modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .modal-content {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        max-width: 600px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease;
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .modal-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: #1e293b;
    }

    .modal-subtitle {
        color: #64748b;
        font-size: 0.9rem;
        margin-top: 0.25rem;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #94a3b8;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .close-btn:hover {
        background: #f1f5f9;
        color: #64748b;
        transform: rotate(90deg);
    }

    .form-section {
        margin-bottom: 2rem;
    }

    .form-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .form-section-subtitle {
        color: #64748b;
        font-size: 0.85rem;
        margin-bottom: 1rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .form-label .required {
        color: #ef4444;
    }

    .form-input {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #fafbfc;
    }

    .form-input:focus {
        outline: none;
        border-color: #4f46e5;
        background: white;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .form-textarea {
        min-height: 100px;
        resize: vertical;
    }

    .form-select {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1rem;
        background: #fafbfc;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .form-select:focus {
        outline: none;
        border-color: #4f46e5;
        background: white;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .photo-upload {
        border: 3px dashed #d1d5db;
        border-radius: 12px;
        padding: 3rem 2rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        background: #fafbfc;
    }

    .photo-upload:hover {
        border-color: #4f46e5;
        background: #f8faff;
    }

    .photo-upload.dragover {
        border-color: #4f46e5;
        background: #f0f7ff;
        transform: scale(1.02);
    }

    .photo-upload-icon {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }

    .photo-upload-text {
        color: #64748b;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .photo-upload-subtext {
        color: #94a3b8;
        font-size: 0.85rem;
    }

    .location-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .map-container {
        grid-column: 1 / -1;
        height: 280px;
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .map-container:hover {
        border-color: #4f46e5;
        box-shadow: 0 8px 24px rgba(79, 70, 229, 0.15);
    }

    .map-wrapper {
        height: 100%;
        width: 100%;
    }

    #locationMap {
        width: 100%;
        height: 100%;
    }

    .map-controls {
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        padding: 1rem;
        border-radius: 0 0 16px 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border-top: 1px solid #e2e8f0;
    }

    .coordinates-display {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 0.75rem;
    }

    .coordinate-item {
        background: linear-gradient(145deg, #ffffff, #f1f5f9);
        padding: 0.75rem;
        border-radius: 8px;
        border-left: 3px solid #4f46e5;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .coordinate-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
    }

    .coordinate-label {
        font-size: 0.85rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .coordinate-value {
        font-family: 'Courier New', monospace;
        font-size: 0.95rem;
        color: #1e293b;
        font-weight: 600;
    }

    .map-instructions {
        color: #6b7280;
        font-size: 0.85rem;
        text-align: center;
        padding: 0.75rem;
        background: rgba(79, 70, 229, 0.05);
        border-radius: 8px;
        border: 1px solid rgba(79, 70, 229, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .map-instructions i {
        color: #4f46e5;
        font-size: 1rem;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 2px solid #f1f5f9;
    }

    .btn {
        flex: 1;
        padding: 0.875rem 1.5rem;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        box-shadow: 0 4px 20px rgba(79, 70, 229, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(79, 70, 229, 0.4);
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #64748b;
        border: 2px solid #e2e8f0;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
        color: #475569;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(50px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @media (max-width: 1024px) {
        .main-container {
            grid-template-columns: 1fr;
        }

        .nav-links {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .nav-container {
            padding: 0 1rem;
        }

        .main-container {
            padding: 1rem;
        }

        .issue-header {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .issue-image {
            width: 60px;
            height: 60px;
        }
    }

    .like-btn {
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 0.5rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        user-select: none;
    }

    .like-btn:hover {
        background: rgba(239, 68, 68, 0.1);
        transform: scale(1.05);
    }

    .like-btn i {
        transition: all 0.3s ease;
        font-size: 1.1rem;
    }

    .like-btn i.liked {
        color: #ef4444;
        animation: likeAnimation 0.6s ease;
    }

    .like-btn:hover i {
        transform: scale(1.2);
    }

    .like-btn.loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .like-count {
        font-weight: 600;
        color: #64748b;
        transition: color 0.3s ease;
    }

    .like-btn:hover .like-count {
        color: #ef4444;
    }

    .like-btn.liked .like-count {
        color: #ef4444;
    }

    @keyframes likeAnimation {
        0% {
            transform: scale(1);
        }

        15% {
            transform: scale(1.3);
        }

        30% {
            transform: scale(0.9);
        }

        45% {
            transform: scale(1.1);
        }

        60% {
            transform: scale(0.95);
        }

        75% {
            transform: scale(1.05);
        }

        100% {
            transform: scale(1);
        }
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
        }
    }

    .like-btn.pulse {
        animation: pulse 0.3s ease;
    }

    .like-btn::before {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.8rem;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
        z-index: 1000;
    }

    .like-btn:hover::before {
        opacity: 1;
    }

    .post-popup {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(8px);
        z-index: 2000;
        animation: fadeIn 0.3s ease;
        overflow-y: auto;
        padding: 2rem 1rem;
    }

    .post-popup.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .post-popup-content {
        background: white;
        border-radius: 20px;
        max-width: 900px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
        animation: slideUpScale 0.4s ease;
        position: relative;
    }

    .post-popup-header {
        position: sticky;
        top: 0;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #e2e8f0;
        border-radius: 20px 20px 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        z-index: 10;
    }

    .post-popup-close {
        background: none;
        border: none;
        font-size: 1.8rem;
        color: #94a3b8;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 50%;
        transition: all 0.3s ease;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .post-popup-close:hover {
        background: #f1f5f9;
        color: #64748b;
        transform: rotate(90deg) scale(1.1);
    }

    .post-popup-body {
        padding: 0;
    }

    .post-image-container {
        position: relative;
        width: 100%;
        max-height: 400px;
        overflow: hidden;
        background: #f8fafc;
    }

    .post-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .post-image-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
        color: white;
        padding: 2rem;
    }

    .post-content-section {
        padding: 2rem;
    }

    .post-title-section {
        margin-bottom: 1.5rem;
    }

    .post-popup-title {
        font-size: 2rem;
        font-weight: bold;
        color: #1e293b;
        margin-bottom: 0.5rem;
        line-height: 1.2;
    }

    .post-category-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .post-meta-section {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
        padding: 1.5rem;
        background: linear-gradient(145deg, #f8fafc, #f1f5f9);
        border-radius: 16px;
        border: 1px solid #e2e8f0;
    }

    .post-meta-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .post-meta-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .post-meta-content h4 {
        font-size: 0.85rem;
        font-weight: 600;
        color: #64748b;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .post-meta-content p {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0.25rem 0 0 0;
    }

    .post-description-section {
        margin-bottom: 2rem;
    }

    .post-description-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .post-description-content {
        font-size: 1.1rem;
        line-height: 1.7;
        color: #475569;
        background: linear-gradient(145deg, #f8fafc, #f1f5f9);
        padding: 1.5rem;
        border-radius: 12px;
        border-left: 4px solid #4f46e5;
    }

    .post-stats-section {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.5rem;
        background: linear-gradient(145deg, #f8fafc, #f1f5f9);
        border-radius: 16px;
        margin-bottom: 1.5rem;
    }

    .post-stats-left {
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .post-stat-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .post-stat-item:hover {
        background: rgba(79, 70, 229, 0.1);
        transform: translateY(-2px);
    }

    .post-stat-item i {
        font-size: 1.2rem;
        color: #4f46e5;
    }

    .post-stat-count {
        font-weight: 600;
        color: #1e293b;
    }

    .post-status-badge {
        padding: 0.5rem 1.5rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .status-reported {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .status-progress {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .status-resolved {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .post-actions-section {
        padding: 1.5rem 2rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 1rem;
        border-radius: 0 0 20px 20px;
    }

    .post-action-btn {
        flex: 1;
        padding: 0.875rem 1.5rem;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-like {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .btn-share {
        background: linear-gradient(135deg, #06b6d4, #0891b2);
        color: white;
    }

    .btn-report {
        background: linear-gradient(135deg, #64748b, #475569);
        color: white;
    }

    .post-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    .post-location-section {
        margin-bottom: 2rem;
    }

    .post-location-map {
        width: 100%;
        height: 280px;
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .post-location-map:hover {
        border-color: #4f46e5;
        box-shadow: 0 8px 24px rgba(79, 70, 229, 0.15);
    }

    .post-timestamp {
        text-align: center;
        color: #94a3b8;
        font-size: 0.9rem;
        margin-bottom: 1rem;
        padding: 0 2rem;
    }

    @keyframes slideUpScale {
        from {
            opacity: 0;
            transform: translateY(50px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .comment-section {
        width: 100%;
        padding: 20px;
    }

    .comment-section .fields {
        padding: 10px 0 0 0;
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
    }

    .input:nth-child(1) {
        width: 85%;
    }

    .input:nth-child(2) {
        width: 15%;
    }

    #ipt {
        height: 50px;
        width: 100%;
        padding: 8px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    #ipt:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    #btn {
        height: 50px;
        width: 100%;
        padding: 16px;
        color: white;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    #btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(79, 70, 229, 0.3);
    }

    #comments-container {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 0 0 10px 0;
        overflow-y: auto;
        height: 200px;
    }

    .cmt {
        background: linear-gradient(145deg, #ffffff, #f1f5f9);
        border-radius: 8px;
        padding: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .cmt .user {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user .name {
        font-size: 16px;
        font-weight: bold;
        color: #1e293b;
    }

    .user img {
        height: 40px;
        width: 40px;
        border-radius: 50%;
    }

    .vote-btn {
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        user-select: none;
        border: 2px solid #e2e8f0;
        background: linear-gradient(145deg, #ffffff, #f1f5f9);
    }

    .vote-btn:hover {
        background: rgba(79, 70, 229, 0.1);
        transform: scale(1.05);
        border-color: #4f46e5;
    }

    .vote-text {
        font-size: 1rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .vote-text.voted {
        color: #ef4444;
        animation: voteAnimation 0.6s ease;
    }

    .vote-btn:hover .vote-text {
        transform: scale(1.1);
    }

    .vote-btn.loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .vote-count {
        font-weight: 600;
        color: #64748b;
        transition: color 0.3s ease;
    }

    .vote-btn:hover .vote-count {
        color: #4f46e5;
    }

    .vote-btn.voted .vote-count {
        color: #4f46e5;
    }

    @keyframes voteAnimation {
        0% {
            transform: scale(1);
        }

        15% {
            transform: scale(1.3);
        }

        30% {
            transform: scale(0.9);
        }

        45% {
            transform: scale(1.1);
        }

        60% {
            transform: scale(0.95);
        }

        75% {
            transform: scale(1.05);
        }

        100% {
            transform: scale(1);
        }
    }

    .vote-btn::before {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.8rem;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
        z-index: 1000;
    }

    .vote-btn:hover::before {
        opacity: 1;
    }

    .btn-vote {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
    }

    .btn-vote.voted {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    #trustMember {
        position: absolute;
        right: 22px;
        padding: 4px 10px;
        background: #09a709;
        color: white;
        border-radius: 8px;
        font-size: 14px;
        font-weight: bold;

    }

    .notification-wrapper {
        position: relative;
    }

    .notification-btn {
        position: relative;
        background: none;
        border: none;
        font-size: 22px;
        cursor: pointer;
    }

    .notification-btn .badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: red;
        color: white;
        font-size: 12px;
        padding: 2px 6px;
        border-radius: 50%;
    }

    .notification-popup {
        display: none;
        position: absolute;
        top: 40px;
        right: 0;
        width: 250px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 10px;
        z-index: 100;
    }

    .notification-popup h4 {
        margin: 0 0 10px;
        font-size: 16px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
    }

    .notification-popup ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .notification-popup ul li {
        padding: 8px;
        font-size: 14px;
        border-bottom: 1px solid #eee;
    }

    .notification-popup ul li:last-child {
        border-bottom: none;
    }

    .notification-popup.show {
        display: block;
    }

    .unvote-btn {
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        user-select: none;
        border: 2px solid #e2e8f0;
        background: linear-gradient(145deg, #ffffff, #f1f5f9);
    }

    .unvote-btn:hover {
        background: rgba(239, 68, 68, 0.1);
        transform: scale(1.05);
        border-color: #ef4444;
    }

    .unvote-text {
        font-size: 1rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .unvote-text.unvoted {
        color: #ef4444;
        animation: voteAnimation 0.6s ease;
    }

    .unvote-btn:hover .unvote-text {
        transform: scale(1.1);
    }

    .unvote-btn.loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .unvote-count {
        font-weight: 600;
        color: #64748b;
        transition: color 0.3s ease;
    }

    .unvote-btn:hover .unvote-count {
        color: #ef4444;
    }

    .unvote-btn.unvoted .unvote-count {
        color: #ffffffff;
    }

    #untrustMember {
        position: absolute;
        right: 20px;
        padding: 4px 10px;
        background: #dc2626;
        color: white;
        border-radius: 8px;
        font-size: 14px;
        font-weight: bold;
    }

    .btn-unvote {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .btn-unvote.unvoted {
        background: linear-gradient(135deg, #64748b, #475569);
    }

    @media (max-width: 1200px) {
        .main-container {
            grid-template-columns: 1fr;
            padding: 1rem;
        }

        .sidebar {
            width: 100%;
            max-width: none;
        }

        .navbar-nav {
            gap: 1rem;
        }

        .nav-link {
            padding: 0.5rem;
            font-size: 0.8rem;
        }
    }

    @media (max-width: 768px) {
        .navbar {
            padding: 0 1rem;
            flex-wrap: wrap;
        }

        .navbar-brand {
            font-size: 1.2rem;
        }

        .navbar-nav {
            display: none;
            /* Hide navbar links on mobile, consider a hamburger menu */
        }

        .content {
            padding: 1rem;
            border-radius: 12px;
        }

        .section-title {
            font-size: 1.5rem;
        }

        .section-subtitle {
            font-size: 0.8rem;
        }

        .issue-card {
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .issue-header {
            flex-direction: column;
            align-items: stretch;
            text-align: left;
        }

        .issue-image {
            width: 100%;
            max-width: 100%;
            height: 150px;
            margin-bottom: 1rem;
        }

        .issue-title {
            font-size: 1.1rem;
        }

        .issue-meta {
            flex-direction: column;
            gap: 0.5rem;
        }

        .issue-stats {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .status-badge {
            font-size: 0.7rem;
        }

        .category-tag {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
        }

        .sidebar {
            padding: 1rem;
            border-radius: 12px;
        }

        .sidebar-title {
            font-size: 1rem;
        }

        .report-btn {
            font-size: 0.9rem;
            padding: 0.75rem;
        }

        .modal-content {
            width: 95%;
            padding: 1rem;
            max-height: 85vh;
        }

        .modal-title {
            font-size: 1.2rem;
        }

        .modal-subtitle {
            font-size: 0.8rem;
        }

        .form-section-title {
            font-size: 1rem;
        }

        .form-section-subtitle {
            font-size: 0.75rem;
        }

        .form-input,
        .form-select,
        .form-textarea {
            font-size: 0.9rem;
            padding: 0.5rem;
        }

        .photo-upload {
            padding: 1.5rem;
        }

        .photo-upload-icon {
            font-size: 2rem;
        }

        .photo-upload-text {
            font-size: 0.9rem;
        }

        .photo-upload-subtext {
            font-size: 0.75rem;
        }

        .location-section {
            grid-template-columns: 1fr;
        }

        .map-container {
            height: 200px;
        }

        .post-popup-content {
            width: 95%;
            max-height: 90vh;
            padding: 1rem;
        }

        .post-popup-title {
            font-size: 1.5rem;
        }

        .post-category-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.75rem;
        }

        .post-meta-section {
            grid-template-columns: 1fr;
            padding: 1rem;
        }

        .post-meta-item {
            gap: 0.5rem;
        }

        .post-meta-icon {
            width: 32px;
            height: 32px;
            font-size: 1rem;
        }

        .post-meta-content h4 {
            font-size: 0.8rem;
        }

        .post-meta-content p {
            font-size: 0.9rem;
        }

        .post-description-title {
            font-size: 1rem;
        }

        .post-description-content {
            font-size: 0.95rem;
            padding: 1rem;
        }

        .post-stats-section {
            flex-direction: column;
            gap: 1rem;
            padding: 1rem;
        }

        .post-stats-left {
            flex-direction: column;
            gap: 0.5rem;
        }

        .post-stat-item {
            padding: 0.5rem;
        }

        .post-stat-count {
            font-size: 0.9rem;
        }

        .post-status-badge {
            font-size: 0.8rem;
            padding: 0.3rem 1rem;
        }

        .post-actions-section {
            flex-direction: column;
            gap: 0.5rem;
            padding: 1rem;
        }

        .post-action-btn {
            font-size: 0.9rem;
            padding: 0.75rem;
        }

        .post-location-map {
            height: 200px;
        }

        .comment-section .fields {
            flex-direction: column;
            gap: 0.5rem;
        }

        .input:nth-child(1),
        .input:nth-child(2) {
            width: 100%;
        }

        #ipt,
        #btn {
            height: 40px;
            font-size: 0.9rem;
        }

        .cmt {
            padding: 0.75rem;
        }

        .user .name {
            font-size: 0.9rem;
        }

        .user img {
            width: 32px;
            height: 32px;
        }

        .vote-btn,
        .unvote-btn {
            padding: 0.5rem;
            font-size: 0.9rem;
        }

        .vote-text,
        .unvote-text {
            font-size: 0.9rem;
        }

        .vote-count,
        .unvote-count {
            font-size: 0.9rem;
        }
    }

    @media (max-width: 480px) {
        body {
            font-size: 14px;
        }

        .navbar {
            height: auto;
            padding: 0.5rem 0.75rem;
        }

        .navbar-brand {
            font-size: 1rem;
        }

        .content {
            padding: 0.75rem;
        }

        .search-box {
            max-width: 100%;
        }

        .search-input {
            font-size: 0.85rem;
            padding: 0.5rem 1rem 0.5rem 2rem;
        }

        .search-icon {
            font-size: 0.9rem;
            left: 0.5rem;
        }

        .section-title {
            font-size: 1.2rem;
        }

        .section-subtitle {
            font-size: 0.75rem;
        }

        .issue-card {
            padding: 0.75rem;
        }

        .issue-image {
            height: 120px;
        }

        .issue-title {
            font-size: 1rem;
        }

        .issue-description {
            font-size: 0.85rem;
        }

        .issue-meta {
            font-size: 0.8rem;
        }

        .issue-stats {
            font-size: 0.8rem;
        }

        .status-badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.5rem;
        }

        .category-tag {
            font-size: 0.65rem;
            padding: 0.15rem 0.5rem;
        }

        .sidebar {
            padding: 0.75rem;
        }

        .report-btn {
            font-size: 0.85rem;
            padding: 0.5rem;
        }

        .modal-content {
            width: 100%;
            margin: 1rem;
            padding: 0.75rem;
        }

        .modal-title {
            font-size: 1rem;
        }

        .close-btn {
            font-size: 1.2rem;
        }

        .form-section {
            padding: 0.75rem;
        }

        .form-label {
            font-size: 0.9rem;
        }

        .form-input,
        .form-select,
        .form-textarea {
            font-size: 0.85rem;
        }

        .photo-upload {
            padding: 1rem;
        }

        .photo-upload-icon {
            font-size: 1.5rem;
        }

        .photo-upload-text {
            font-size: 0.85rem;
        }

        .photo-upload-subtext {
            font-size: 0.7rem;
        }

        .map-container {
            height: 150px;
        }

        .coordinate-item {
            padding: 0.5rem;
        }

        .coordinate-label {
            font-size: 0.75rem;
        }

        .coordinate-value {
            font-size: 0.85rem;
        }

        .map-instructions {
            font-size: 0.75rem;
        }

        .form-actions {
            flex-direction: column;
            gap: 0.5rem;
            padding: 0.75rem;
        }

        .btn {
            font-size: 0.85rem;
            padding: 0.5rem;
        }

        .post-popup-content {
            width: 100%;
            margin: 1rem;
        }

        .post-popup-header {
            padding: 1rem;
        }

        .post-popup-close {
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
        }

        .post-image-container {
            max-height: 200px;
        }

        .post-image-overlay {
            padding: 1rem;
        }

        .post-content-section {
            padding: 1rem;
        }

        .post-timestamp {
            font-size: 0.8rem;
            padding: 0 1rem;
        }

        .comment-section {
            padding: 0.75rem;
        }

        #ipt,
        #btn {
            height: 35px;
            font-size: 0.85rem;
        }

        .cmt {
            padding: 0.5rem;
        }

        .user .name {
            font-size: 0.85rem;
        }

        .user img {
            width: 28px;
            height: 28px;
        }

        .vote-btn,
        .unvote-btn {
            padding: 0.4rem;
        }

        .vote-text,
        .unvote-text {
            font-size: 0.85rem;
        }

        .vote-count,
        .unvote-count {
            font-size: 0.85rem;
        }

        #trustMember,
        #untrustMember {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            right: 0.5rem;
        }

        .notification-popup {
            width: 200px;
            top: 35px;
            right: -10px;
        }

        .notification-popup h4 {
            font-size: 0.9rem;
        }

        .notification-popup ul li {
            font-size: 0.8rem;
            padding: 0.5rem;
        }

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

    .shape-1 {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        top: 10%;
        left: 10%;
        animation: float1 12s ease-in-out infinite;
    }

    .shape-2 {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        top: 20%;
        right: 15%;
        animation: float2 8s ease-in-out infinite;
    }

    .shape-3 {
        width: 150px;
        height: 150px;
        border-radius: 30px;
        bottom: 15%;
        left: 15%;
        animation: float3 15s ease-in-out infinite;
    }

    .logout-btn {
  position: fixed;
  bottom: 40px;
  right: 40px;
  background: #dc3545; /* red */
  color: white;
  padding: 20px 26px;
  border-radius: 50%;
  text-align: center;
  font-size: 20px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
  transition: background 0.3s, transform 0.2s;
}

.logout-btn:hover {
  background: #c82333;
  transform: scale(1.2);
  color: white;
}
    .shape-4 {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        bottom: 25%;
        right: 20%;
        animation: float4 10s ease-in-out infinite;
    }

    .shape-5 {
        width: 100px;
        height: 100px;
        border-radius: 15px;
        top: 50%;
        left: 5%;
        animation: float5 14s ease-in-out infinite;
    }

    .shape-6 {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        top: 60%;
        right: 10%;
        animation: float6 11s ease-in-out infinite;
    }

    .shape-7 {
        width: 70px;
        height: 70px;
        border-radius: 25px;
        top: 80%;
        left: 50%;
        animation: float7 9s ease-in-out infinite;
    }

    @keyframes float1 {

        0%,
        100% {
            transform: translate(0, 0) rotate(0deg);
        }

        25% {
            transform: translate(20px, -30px) rotate(90deg);
        }

        50% {
            transform: translate(-10px, -20px) rotate(180deg);
        }

        75% {
            transform: translate(-25px, 15px) rotate(270deg);
        }
    }

    @keyframes float2 {

        0%,
        100% {
            transform: translate(0, 0) rotate(0deg);
        }

        33% {
            transform: translate(-30px, 20px) rotate(120deg);
        }

        66% {
            transform: translate(25px, -15px) rotate(240deg);
        }
    }

    @keyframes float3 {

        0%,
        100% {
            transform: translate(0, 0) rotate(0deg);
        }

        20% {
            transform: translate(15px, -25px) rotate(72deg);
        }

        40% {
            transform: translate(-20px, -10px) rotate(144deg);
        }

        60% {
            transform: translate(-15px, 20px) rotate(216deg);
        }

        80% {
            transform: translate(30px, 10px) rotate(288deg);
        }
    }

    @keyframes float4 {

        0%,
        100% {
            transform: translate(0, 0) rotate(0deg);
        }

        50% {
            transform: translate(-20px, -30px) rotate(180deg);
        }
    }

    @keyframes float5 {

        0%,
        100% {
            transform: translate(0, 0) rotate(0deg);
        }

        25% {
            transform: translate(-15px, 25px) rotate(90deg);
        }

        50% {
            transform: translate(20px, 15px) rotate(180deg);
        }

        75% {
            transform: translate(10px, -20px) rotate(270deg);
        }
    }

    @keyframes float6 {

        0%,
        100% {
            transform: translate(0, 0) rotate(0deg);
        }

        30% {
            transform: translate(25px, -20px) rotate(108deg);
        }

        60% {
            transform: translate(-15px, 25px) rotate(216deg);
        }
    }

    @keyframes float7 {

        0%,
        100% {
            transform: translate(0, 0) rotate(0deg);
        }

        40% {
            transform: translate(-25px, -15px) rotate(144deg);
        }

        80% {
            transform: translate(20px, -25px) rotate(288deg);
        }
    }

    .user{
        background: rgb(5, 113, 202);
        color: white;
        padding: 10px 20px;
        font-weight: 500;
        border-radius: 6px;
    }
    </style>
</head>

<body>
    <div class="floating-shapes">
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
        <div class="floating-shape shape-4"></div>
        <div class="floating-shape shape-5"></div>
        <div class="floating-shape shape-6"></div>
        <div class="floating-shape shape-7"></div>
    </div>
    <div id="postPopup" class="post-popup">
        <div class="post-popup-content">
            <div class="post-popup-header"
                style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">
                <div>
                    <h3 style="margin: 0; color: #1e293b; font-size: 1.2rem;">Issue Details</h3>
                    <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.9rem;">समस्याको विवरण</p>
                </div>
                <button class="post-popup-close" onclick="closePostPopup()"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            <div class="post-popup-body" style="padding: 1.5rem 0;">
                <div class="post-image-container" style="position: relative; margin-bottom: 1.5rem;">
                    <img id="popupPostImage" class="post-image" src="" alt="Issue Image"
                        style="width: 100%; height: 300px; object-fit: cover; border-radius: 8px;">
                    <div class="post-image-overlay"
                        style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0, 0, 0, 0.5); color: white; padding: 1rem;">
                        <div id="popupImageLocation" style="font-size: 1.1rem; font-weight: 500;">
                            <i class="fas fa-map-marker-alt" style="margin-right: 0.5rem;"></i>
                            Location will appear here
                        </div>
                    </div>
                </div>
                <div class="post-content-section">
                    <div class="post-title-section" style="margin-bottom: 1.5rem;">
                        <div id="popupPostCategory" class="post-category-badge"
                            style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-tag" style="margin-right: 0.5rem;"></i>
                            <span>Category</span>
                        </div>
                        <h2 id="popupPostTitle" class="post-popup-title"
                            style="margin: 0; font-size: 1.5rem; color: #1e293b;">Issue Title</h2>
                    </div>
                    <div class="post-meta-section"
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div class="post-meta-item" style="display: flex; align-items: center;">
                            <div class="post-meta-icon" style="margin-right: 0.75rem;">
                                <i class="fas fa-user" style="font-size: 1.2rem; "></i>
                            </div>
                            <div class="post-meta-content">
                                <h4 style="margin: 0; font-size: 0.9rem; color: #64748b;">Reported By</h4>
                                <p id="popupReportedBy" style="margin: 0; font-size: 1rem; color: #1e293b;">Reporter
                                    Name</p>
                            </div>
                        </div>
                        <div class="post-meta-item" style="display: flex; align-items: center;">
                            <div class="post-meta-icon" style="margin-right: 0.75rem;">
                                <i class="fas fa-calendar" style="font-size: 1.2rem;"></i>
                            </div>
                            <div class="post-meta-content">
                                <h4 style="margin: 0; font-size: 0.9rem; color: #64748b;">Date Reported</h4>
                                <p id="popupDateReported" style="margin: 0; font-size: 1rem; color: #1e293b;">Date</p>
                            </div>
                        </div>
                        <div class="post-meta-item" style="display: flex; align-items: center;">
                            <div class="post-meta-icon" style="margin-right: 0.75rem;">
                                <i class="fas fa-flag" style="font-size: 1.2rem;"></i>
                            </div>
                            <div class="post-meta-content">
                                <h4 style="margin: 0; font-size: 0.9rem; color: #64748b;">Priority</h4>
                                <p id="popupPriority" style="margin: 0; font-size: 1rem; color: #1e293b;">Medium</p>
                            </div>
                        </div>
                    </div>
                    <div class="post-description-section" style="margin-bottom: 1.5rem;">
                        <h3 class="post-description-title"
                            style="font-size: 1.1rem; margin: 0 0 0.5rem 0; color: #1e293b;">
                            <i class="fas fa-align-left" style="margin-right: 0.5rem;"></i>
                            Description
                        </h3>
                        <div id="popupPostDescription" class="post-description-content" style="color: #475569;">
                            Issue description will appear here...
                        </div>
                    </div>
                    <div class="post-compliction-section" style="margin-bottom: 1.5rem;">
                        <h3 class="post-description-title"
                            style="font-size: 1.1rem; margin: 0 0 0.5rem 0; color: #1e293b;">
                            <i class="fas fa-align-left" style="margin-right: 0.5rem;"></i>
                            Completion message
                        </h3>
                        <div id="popupPostComplition" class="post-complition-content" style="color: #475569;">
                            Issue complition message will appear here...
                        </div>
                    </div>
                    <div class="post-stats-section"
                        style="display: none; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <div class="post-stats-section"
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <div class="post-stats-left" style="display: flex; gap: 1rem;">
                                <div class="post-stat-item vote-btn-popup" id="popupVoteBtn"
                                    style="display: flex; align-items: center; cursor: pointer;">
                                    <span id="popupVoteText" style="margin-right: 0.5rem;">Vote</span>
                                    <span id="popupVoteCount" class="post-stat-count">0</span>
                                </div>
                                <div class="post-stat-item unvote-btn-popup" id="popupUnvoteBtn"
                                    style="display: flex; align-items: center; cursor: pointer;">
                                    <span id="popupUnvoteText" style="margin-right: 0.5rem;">Unvote</span>
                                    <span id="popupUnvoteCount" class="post-stat-count">0</span>
                                </div>
                            </div>
                            <div id="popupPostStatus" class="post-status-badge status-reported"
                                style="padding: 0.25rem 0.75rem; border-radius: 9999px;">
                                Reported
                            </div>
                        </div>
                    </div>
                    <div class="post-location-section" style="margin-bottom: 1.5rem;">
                        <h3 class="post-description-title"
                            style="font-size: 1.1rem; margin: 0 0 0.5rem 0; color: #1e293b;">
                            <i class="fas fa-map-marker-alt" style="margin-right: 0.5rem;"></i>
                            Location
                        </h3>
                        <div class="post-location-map" id="mapShower"
                            style="height: 250px; background: #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                            <div style="text-align: center;">
                                <i class="fas fa-map"
                                    style="font-size: 2rem; margin-bottom: 0.5rem; color: #6366f1;"></i>
                                <div>Click to view location on map</div>
                                <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 0.25rem;">
                                    Coordinates: <span id="popupCoordinates">34.0, 32.0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="post-timestamp" style="font-size: 0.9rem; color: #64748b; margin-bottom: 1rem;">
                    <i class="fas fa-clock" style="margin-right: 0.5rem;"></i>
                    Last updated: <span id="popupLastUpdated">Time</span>
                </div>
                <hr>
                <div class="comment-section" style="margin-top: 1rem;">
                    <div id="comments-container">
                        <div class="cmt" style="margin-bottom: 1rem;">
                            <div class="user" style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                <div class="img" style="margin-right: 0.75rem;"><img
                                        src="Uploads/530129767_1109227174544310_4965397869680858578_n.jpg"
                                        alt="user photo" style="width: 40px; height: 40px; border-radius: 50%;"></div>
                                <div class="name" style="font-weight: 500;">Sargam Kandel</div>
                            </div>
                            <div class="comment" style="color: #475569;">
                                Yes I have also face similar issue.
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="fields" style="display: flex; gap: 0.5rem;">
                        <div class="input" style="flex: 1;">
                            <input type="text" id="ipt" placeholder="Leave a comment..."
                                style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px;">
                        </div>
                        <div class="input">
                            <button type="submit" id="btn"
                                style="padding: 0.5rem 1rem; background: #6366f1; color: white; border: none; border-radius: 4px; cursor: pointer;">Post</button>
                        </div>
                    </div>
                </div>
                <div class="post-actions-section" style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button class="post-action-btn btn-vote" id="popupVoteActionBtn"
                        style="padding: 0.5rem 1rem; background: #6366f1; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        <span id="voteBtnText">Vote</span>
                    </button>
                    <button class="post-action-btn btn-unvote" id="popupUnvoteActionBtn"
                        style="padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        <span id="unvoteBtnText">Unvote</span>
                    </button>
                    <button class="post-action-btn btn-share" onclick="sharePost()"
                        style="padding: 0.5rem 1rem; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-share-alt"></i>
                        Share Issue
                    </button>
                    <button class="post-action-btn btn-report" onclick="reportPost()"
                        style="padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-flag"></i>
                        Report Inappropriate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <nav class="navbar">
        <div class="navbar-brand"> 📢आवाज</div>
        <ul class="navbar-nav">
            <li><a href="index.php" class="nav-link active">🏠 Home</a></li>
            <li><a href="map.php" class="nav-link">📈 Map</a></li>
            <li><a href="notice.php" class="nav-link">🔔 Notices</a></li>
            <li><a href="admin.php" class="nav-link">⚙️ Admin</a></li>
            <li><a href="campaign.php" class="nav-link">📢 Campaigns</a></li>
            <li><div class="user">
            <?php echo $_SESSION['user_name']; ?>
        </div></li>
            <li class="notification-item" style="display: none;">
                <div class="notification-wrapper">
                    <button class="notification-btn" id="notificationToggle">
                        🔔
                        <span class="badge">3</span>
                    </button>
                    <div class="notification-popup" id="notificationPopup">
                        <h4>Notifications</h4>
                        <ul>
                            <?php
        displayNotification();
       ?>
                        </ul>
                    </div>
                </div>
            </li>
        </ul>
    </nav>

    <div class="main-container"
        style="display: grid; grid-template-columns: 3fr 1fr; gap: 1.5rem; padding: 1.5rem; max-width: 1500px; margin: 0 auto;">

        <main class="content">

            <div class="search-container" style="margin-bottom: 1.5rem;">
                <div class="search-box" style="position: relative;">
                    <i class="fas fa-search search-icon"
                        style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #64748b;"></i>
                    <input type="text" class="search-input"
                        placeholder="Search issues by title, location, or category..."
                        style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1px solid #e2e8f0; border-radius: 4px;">
                </div>
            </div>
            <div class="section-header" style="margin-bottom: 1.5rem;">
                <div>
                    <h1 class="section-title" style="font-size: 1.8rem; margin: 0; color: #1e293b;">Community Issues
                    </h1>
                    <p class="section-subtitle" style="margin: 0.25rem 0 0 0; color: #64748b;">सामुदायिक समस्याहरू</p>
                </div>
            </div>


            <div class="tabs"
                style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid #e2e8f0;">
                <button class="tab-btn <?php echo $tab === 'all' ? 'active' : ''; ?>" onclick="changeTab('all')"
                    style="padding: 0.75rem 1.5rem; font-size: 1rem; font-weight: 500; color: <?php echo $tab === 'all' ? '#4f46e5' : '#64748b'; ?>; background: <?php echo $tab === 'all' ? 'linear-gradient(145deg, #e0e7ff, #c7d2fe)' : 'transparent'; ?>; border: none; border-radius: 8px 8px 0 0; cursor: pointer; transition: all 0.3s ease;">All
                    Posts</button>
                <button class="tab-btn <?php echo $tab === 'completed' ? 'active' : ''; ?>"
                    onclick="changeTab('completed')"
                    style="padding: 0.75rem 1.5rem; font-size: 1rem; font-weight: 500; color: <?php echo $tab === 'completed' ? '#4f46e5' : '#64748b'; ?>; background: <?php echo $tab === 'completed' ? 'linear-gradient(145deg, #e0e7ff, #c7d2fe)' : 'transparent'; ?>; border: none; border-radius: 8px 8px 0 0; cursor: pointer; transition: all 0.3s ease;">Completed
                    Posts</button>
                <button class="tab-btn <?php echo $tab === 'working' ? 'active' : ''; ?>" onclick="changeTab('working')"
                    style="padding: 0.75rem 1.5rem; font-size: 1rem; font-weight: 500; color: <?php echo $tab === 'working' ? '#4f46e5' : '#64748b'; ?>; background: <?php echo $tab === 'working' ? 'linear-gradient(145deg, #e0e7ff, #c7d2fe)' : 'transparent'; ?>; border: none; border-radius: 8px 8px 0 0; cursor: pointer; transition: all 0.3s ease;">Working
                    Posts</button>
            </div>
            <?php
// Fetch the logged-in user's coordinates from the users table
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if ($user_id === 0) {
    error_log("No user_id found in session");
    $user_lat = 27.7172; // Fallback: Kathmandu coordinates
    $user_lon = 85.3240;
} else {
    $user_sql = "SELECT lat, lon FROM users WHERE id = ?";
    $user_stmt = mysqli_prepare($conn, $user_sql);
    if ($user_stmt === false) {
        error_log("User query preparation failed: " . mysqli_error($conn));
        die("Error preparing user query: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    if (!mysqli_stmt_execute($user_stmt)) {
        error_log("User query execution failed: " . mysqli_stmt_error($user_stmt));
        die("Error executing user query: " . mysqli_stmt_error($user_stmt));
    }
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user_row = mysqli_fetch_assoc($user_result);
    $user_lat = $user_row && is_numeric($user_row['lat']) ? floatval($user_row['lat']) : 27.7172;
    $user_lon = $user_row && is_numeric($user_row['lon']) ? floatval($user_row['lon']) : 85.3240;
    mysqli_stmt_close($user_stmt);
}

// Debug: Log user coordinates
error_log("User Coordinates: Lat=$user_lat, Lon=$user_lon");

// Determine tab filter
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';
$status_filter = '';
if ($tab === 'completed') {
    $status_filter = "AND p.status = 'completed'";
} elseif ($tab === 'working') {
    $status_filter = "AND p.status = 'working'";
}

$sql = "SELECT p.id, p.user_id, p.title, p.description, p.completion_message, p.category, p.photo_url, p.location_name, p.latitude, p.longitude, p.status, p.priority, p.views_count, p.created_at, p.updated_at, u.first_name, u.last_name,
        (SELECT COUNT(*) FROM problem_likes WHERE problem_id = p.id) as likes_count,
        (SELECT COUNT(*) FROM problem_dislikes WHERE problem_id = p.id) as dislikes_count,
        (SELECT COUNT(*) FROM problem_likes pl JOIN users ut ON pl.user_id = ut.id WHERE pl.problem_id = p.id AND ut.trust_member = 'yes') as trusted_likes_count,
        CASE WHEN pl.id IS NOT NULL THEN 1 ELSE 0 END as user_voted,
        CASE WHEN pd.id IS NOT NULL THEN 1 ELSE 0 END as user_unvoted,
        (6371 * acos(cos(radians(?)) * cos(radians(p.latitude)) * cos(radians(p.longitude) - radians(?)) + sin(radians(?)) * sin(radians(p.latitude)))) AS distance
        FROM problem_posts p 
        JOIN users u ON p.user_id = u.id 
        LEFT JOIN problem_likes pl ON p.id = pl.problem_id AND pl.user_id = ?
        LEFT JOIN problem_dislikes pd ON p.id = pd.problem_id AND pd.user_id = ?
        WHERE 1=1 $status_filter
        ORDER BY distance ASC, trusted_likes_count DESC, likes_count DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt === false) {
    die('Prepare failed: ' . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "dddii", $user_lat, $user_lon, $user_lat, $user_id, $user_id);
if (!mysqli_stmt_execute($stmt)) {
    die('Execute failed: ' . mysqli_stmt_error($stmt));
}
$result = mysqli_stmt_get_result($stmt);
if ($result === false) {
    die('Get result failed: ' . mysqli_stmt_error($stmt));
}
?>

<?php
$sidebar_sql = "SELECT p.id, p.title, p.created_at,
        (6371 * acos(cos(radians(?)) * cos(radians(p.latitude)) * cos(radians(p.longitude) - radians(?)) + sin(radians(?)) * sin(radians(p.latitude)))) AS distance
        FROM problem_posts p
        WHERE (6371 * acos(cos(radians(?)) * cos(radians(p.latitude)) * cos(radians(p.longitude) - radians(?)) + sin(radians(?)) * sin(radians(p.latitude)))) < 10
        ORDER BY distance ASC, p.created_at DESC
        LIMIT 5";
$sidebar_stmt = mysqli_prepare($conn, $sidebar_sql);
mysqli_stmt_bind_param($sidebar_stmt, "dddddd", $user_lat, $user_lon, $user_lat, $user_lat, $user_lon, $user_lat);
mysqli_stmt_execute($sidebar_stmt);
$sidebar_result = mysqli_stmt_get_result($sidebar_stmt);
?>

<?php if (mysqli_num_rows($result) > 0): ?>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="issue-card" data-latitude="<?php echo htmlspecialchars($row['latitude']); ?>"
             data-longitude="<?php echo htmlspecialchars($row['longitude']); ?>"
             style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); margin-bottom: 1.5rem; overflow: hidden;">
            <?php
            $pId = $row['id'];
            
            // Check for likes from trusted members with matching trust_to category
            $sqlCheck = "SELECT COUNT(*) as count 
                         FROM problem_likes pl 
                         JOIN users u ON pl.user_id = u.id 
                         JOIN problem_posts pp ON pl.problem_id = pp.id 
                         WHERE pl.problem_id = $pId 
                         AND u.trust_member = 'yes' 
                         AND u.trust_to = pp.category";
            $resCheck = mysqli_query($conn, $sqlCheck);
            $rowCheck = mysqli_fetch_assoc($resCheck);
            
            if($rowCheck['count'] > 0){
                echo "<span id='trustMember'>Voted By Trusted Member</span>";
            }

            // Check for dislikes from trusted members with matching trust_to category
            $sqlCheckD = "SELECT COUNT(*) as count 
                          FROM problem_dislikes pd 
                          JOIN users u ON pd.user_id = u.id 
                          JOIN problem_posts pp ON pd.problem_id = pp.id 
                          WHERE pd.problem_id = $pId 
                          AND u.trust_member = 'yes' 
                          AND u.trust_to = pp.category";
            $resCheckD = mysqli_query($conn, $sqlCheckD);
            $rowCheckD = mysqli_fetch_assoc($resCheckD);
            
            if($rowCheckD['count'] > 0){
                echo "<span id='untrustMember'>Unvoted By Trusted Member</span>";
            }
            ?>
                <div class="category-tag <?php echo strtolower($row['category']); ?>-tag"
                    style="position: absolute; top: 1rem; left: 1rem; padding: 0.3rem 0.75rem; border-radius: 9999px; font-size: 1rem;">
                    <?php echo ucfirst($row['category']); ?>
                </div>
                <div class="issue-header" style="display: flex;">
                    <div class="issue-image" style="flex: 0 0 30%; max-width: 30%;">
                        <img src="Uploads/<?php echo htmlspecialchars($row['photo_url']); ?>" alt="Issue Image"
                            style="max-width:100%; max-height:100%; object-fit:cover;">
                    </div>
                    <div class="issue-content" style="flex: 1; padding: 1rem;">
                        <h3 class="issue-title" style="margin: 0 0 0.5rem 0; font-size: 1.2rem; color: #1e293b;">
                            <?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="issue-meta"
                            style="display: flex; gap: 1rem; margin-bottom: 0.5rem; color: #64748b; font-size: 0.9rem;">
                            <span><i class="fas fa-map-marker-alt" style="margin-right: 0.25rem;"></i>
                                <?php echo htmlspecialchars($row['location_name']); ?></span>
                            <span><i class="fas fa-user" style="margin-right: 0.25rem;"></i> Reported by
                                <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></span>
                        </div>
                        <p class="issue-description" style="color: #475569; margin: 0 0 0.5rem 0;">
                            <?php echo htmlspecialchars($row['description']); ?>
                        </p>
                        <p class="issue-complition" style="color: #475569; margin: 0 0 0.5rem 0;">
                            <?php echo htmlspecialchars($row['completion_message']); ?>
                        </p>
                        <div class="issue-stats"
                            style="display: flex; align-items: center; gap: 1rem; font-size: 0.9rem;">
                            <div class="stat vote-btn" data-post-id="<?php echo $row['id']; ?>"
                                data-voted="<?php echo $row['user_voted']; ?>" style="cursor: pointer;">
                                <span class="vote-text"><?php echo $row['user_voted'] ? 'Voted' : 'Vote'; ?></span>
                                <span class="vote-count"><?php echo $row['likes_count']; ?></span>
                            </div>
                            <div class="stat unvote-btn" data-post-id="<?php echo $row['id']; ?>"
                                data-unvoted="<?php echo $row['user_unvoted']; ?>" style="cursor: pointer;">
                                <span
                                    class="unvote-text"><?php echo $row['user_unvoted'] ? 'Unvoted' : 'Unvote'; ?></span>
                                <span class="unvote-count"><?php echo $row['dislikes_count']; ?></span>
                            </div>
                            <div class="status-badge status-<?php echo strtolower($row['status']); ?>"
                                style="padding: 0.25rem 0.75rem; border-radius: 9999px;">
                                <?php echo ucfirst($row['status']); ?>
                            </div>
                            <div class="timestamp" style="color: #64748b;"><?php echo ($row['created_at']); ?></div>
                            <?php if ($_SESSION['user_id'] == $row['user_id']): ?>
                            <div class="stat">
                                <a href="delete.php?del=post&id=<?php echo $row['id']; ?>&userid=<?php echo $row['user_id']; ?>"
                                    style="color: red; text-decoration: none;">
                                    <i class="fas fa-trash" style="cursor: pointer;"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <h2 style="text-align: center;">No issues reported yet.</h2>
            <?php endif; ?>
        </main>
        <aside class="sidebar">
            <div class="sidebar-section"
                style="background: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                <h3 class="sidebar-title" style="font-size: 1.2rem; margin: 0 0 1rem 0; color: #1e293b;">
                    <i class="fas fa-bolt" style="margin-right: 0.5rem;"></i>
                    Quick Actions
                </h3>
                <button class="report-btn"
                    style="width: 100%; padding: 0.75rem; background: #6366f1; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem;">
                    <i class="fas fa-plus" style="margin-right: 0.5rem;"></i> Report an Issue
                </button>
            </div>
            <div class="sidebar-section"
                style="background: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                <h3 class="sidebar-title" style="font-size: 1.2rem; margin: 0 0 1rem 0; color: #1e293b;">
                    <i class="fas fa-bell" style="margin-right: 0.5rem;"></i>
                    Nearby Issue Notifications
                </h3>
                <ul class="nearby-issues-list" style="list-style: none; padding: 0;">
                    <?php if (mysqli_num_rows($sidebar_result) > 0): ?>
                    <?php while ($sidebar_row = mysqli_fetch_assoc($sidebar_result)): ?>
                    <li class="nearby-issue-item"
                        style="padding: 0.75rem; margin-bottom: 0.5rem; background: linear-gradient(145deg, #ffffff, #f1f5f9); border-radius: 8px; transition: all 0.3s ease; display: flex; justify-content: space-between; align-items: center;">
                        <div style="width: 80%"><?php
                            
                            
                            $pId = $sidebar_row['id']; 
                            $sql = "SELECT * from problem_posts WHERE id = $pId";
                            $res = mysqli_query($conn, $sql);
                            $fetch = mysqli_fetch_assoc($res);

                            $lat = $fetch['latitude'];
                            $lng = $fetch['longitude'];


                            
                            
                            ?>
                            <a style="color: blue; text-decoration: none; font-weight: 500;" href="map.php?lng=<?php echo $lng; ?>&lat=<?php echo $lat; ?>" onclick="openPostPopupFromId(<?php echo $sidebar_row['id']; ?>)"
                                style="text-decoration: none; color: #1e293b; font-weight: 500;"><?php echo htmlspecialchars($sidebar_row['title']); ?></a>
                            <span
                                style="display: block; color: #64748b; font-size: 0.8rem;"><?php echo date('M d, Y', strtotime($sidebar_row['created_at'])); ?></span>
                        </div>
                        <span
                            style="color: #4f46e5; font-weight: bold;"><?php echo round($sidebar_row['distance'], 2); ?>
                            km</span>
                    </li>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <li style="text-align: center; color: #64748b;">No nearby issues found.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </aside>
    </div>

    <div id="reportModal" class="modal">
        <div class="modal-content"
            style="background: white; border-radius: 8px; max-width: 600px; width: 90%; margin: 2rem auto;">
            <div class="modal-header"
                style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid #e2e8f0;">
                <div>
                    <h2 class="modal-title" style="margin: 0; font-size: 1.5rem; color: #1e293b;">Report an Issue</h2>
                    <p class="modal-subtitle" style="margin: 0.25rem 0 0 0; color: #64748b;">समस्या रिपोर्ट गर्नुहोस्
                    </p>
                </div>
                <button class="close-btn" onclick="closeModal()"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            <form id="issueForm" method="post" enctype="multipart/form-data">
                <div class="form-section" style="padding: 1.5rem;">
                    <h3 class="form-section-title" style="font-size: 1.1rem; margin: 0 0 0.5rem 0; color: #1e293b;">Add
                        Photo</h3>
                    <p class="form-section-subtitle" style="margin: 0 0 1rem 0; color: #64748b;">तस्बिर थप्नुहोस्</p>
                    <div class="photo-upload" onclick="document.getElementById('photoInput').click()"
                        style="border: 2px dashed #e2e8f0; border-radius: 8px; padding: 2rem; text-align: center; cursor: pointer;">
                        <div class="photo-upload-icon" style="margin-bottom: 0.5rem;">
                            <i class="fas fa-camera" style="font-size: 2rem; color: #6366f1;"></i>
                        </div>
                        <div class="photo-upload-text" style="color: #1e293b;">Drag and drop a photo here, or click to
                            select</div>
                        <div class="photo-upload-subtext"
                            style="color: #64748b; font-size: 0.9rem; margin-top: 0.25rem;">PNG, JPG up to 10MB</div>
                    </div>
                    <input type="file" id="photoInput" name="file" accept="image/*" style="display: none;">
                </div>
                <div class="form-section" style="padding: 0 1.5rem 1.5rem 1.5rem;">
                    <h3 class="form-section-title" style="font-size: 1.1rem; margin: 0 0 0.5rem 0; color: #1e293b;">
                        Issue Details</h3>
                    <p class="form-section-subtitle" style="margin: 0 0 1rem 0; color: #64748b;">समस्याको विवरण</p>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" for="issueTitle"
                            style="display: block; margin-bottom: 0.25rem; color: #1e293b;">
                            Issue Title <span class="required" style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="issueTitle" name="title" class="form-input"
                            placeholder="Brief description of the issue" required
                            style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" for="category"
                            style="display: block; margin-bottom: 0.25rem; color: #1e293b;">
                            Category <span class="required" style="color: #ef4444;">*</span>
                        </label>
                        <select id="category" name="category" class="form-select" required
                            style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px;">
                            <option value="">Select a category</option>
                            <option value="Roads">Roads & Transportation</option>
                            <option value="Infrastructure">Infrastructure</option>
                            <option value="Water">Water & Sanitation</option>
                            <option value="Electricity">Electricity</option>
                            <option value="Waste">Waste Management</option>
                            <option value="Safety">Public Safety</option>
                            <option value="Environment">Environment</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" for="category"
                            style="display: block; margin-bottom: 0.25rem; color: #1e293b;">
                            Priority <span class="required" style="color: #ef4444;">*</span>
                        </label>
                        <select id="category" name="level" class="form-select" required
                            style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px;">
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" style="display: block; margin-bottom: 0.25rem; color: #1e293b;">
                            Location <span class="required" style="color: #ef4444;">*</span>
                        </label>
                        <div class="map-container">
                            <div class="map-wrapper" style="margin-bottom: 1rem;">
                                <div id="locationMap" style="height: 300px; border-radius: 8px;"></div>
                            </div>
                            <div class="map-controls">
                                <div class="coordinates-display"
                                    style="display: flex; gap: 1rem; margin-bottom: 0.5rem;">
                                    <div class="coordinate-item">
                                        <div class="coordinate-label" style="color: #64748b; font-size: 0.9rem;">
                                            Latitude</div>
                                        <div class="coordinate-value" id="latValue" style="color: #1e293b;">Click on map
                                            to select</div>
                                    </div>
                                    <div class="coordinate-item">
                                        <div class="coordinate-label" style="color: #64748b; font-size: 0.9rem;">
                                            Longitude</div>
                                        <div class="coordinate-value" id="lngValue" style="color: #1e293b;">Click on map
                                            to select</div>
                                    </div>
                                </div>
                                <div class="map-instructions" style="color: #64748b; font-size: 0.9rem;">
                                    <i class="fas fa-map-marker-alt" style="margin-right: 0.5rem;"></i>
                                    Click anywhere on the map to select the issue location
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="latitude" name="lat" value="" required>
                        <input type="hidden" id="longitude" name="lon" value="" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" for="issueTitle"
                            style="display: block; margin-bottom: 0.25rem; color: #1e293b;">
                            Area <span class="required" style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="issueTitle" name="location" class="form-input"
                            placeholder="Exact Location Of Problem" required
                            style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" for="description"
                            style="display: block; margin-bottom: 0.25rem; color: #1e293b;">
                            Description <span class="required" style="color: #ef4444;">*</span>
                        </label>
                        <textarea name="desc" id="description" class="form-input form-textarea"
                            placeholder="Provide detailed information about the issue..." required
                            style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px; min-height: 100px;"></textarea>
                    </div>
                </div>
                <div class="form-actions"
                    style="padding: 1rem; border-top: 1px solid #e2e8f0; display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()"
                        style="padding: 0.5rem 1rem; background: #e2e8f0; color: #1e293b; border: none; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-times" style="margin-right: 0.5rem;"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 0.5rem 1rem; background: #6366f1; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-paper-plane" style="margin-right: 0.5rem;"></i>
                        Submit Report
                    </button>
                </div>
            </form>
        </div>
    </div>
<a href="logout.php"  class="logout-btn" title="Logout">
  <i class="fas fa-sign-out-alt"></i>
</a>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA04g5uPfBSXraUtweYOmYfrTwI9dQK7S8&callback=initMap">
    </script>
    <script>
    let postId = 0;
    let map;
    let marker;
    let currentPostData = null;

    function changeTab(tab) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.location = url.toString();
    }

    function openModal() {
        document.getElementById('reportModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('reportModal').classList.remove('active');
        document.body.style.overflow = 'auto';
        document.getElementById('issueForm').reset();
        document.querySelector('.photo-upload').innerHTML = `
        <div class="photo-upload-icon" style="margin-bottom: 0.5rem;">
            <i class="fas fa-camera" style="font-size: 2rem; color: #6366f1;"></i>
        </div>
        <div class="photo-upload-text" style="color: #1e293b;">Drag and drop a photo here, or click to select</div>
        <div class="photo-upload-subtext" style="color: #64748b; font-size: 0.9rem; margin-top: 0.25rem;">PNG, JPG up to 10MB</div>
    `;
    }

    function initMap() {
        const defaultLocation = {
            lat: 27.7172,
            lng: 85.3240
        };
        map = new google.maps.Map(document.getElementById("locationMap"), {
            zoom: 13,
            center: defaultLocation,
            mapTypeControl: true,
            streetViewControl: true,
            fullscreenControl: true,
            zoomControl: true
        });

        map.addListener("click", (event) => {
            setMarker(event.latLng);
        });

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    map.setCenter(userLocation);
                    setMarker(new google.maps.LatLng(userLocation.lat, userLocation.lng));
                },
                () => console.error("Geolocation failed, using default location")
            );
        }
    }

    function setMarker(location) {
        if (marker) marker.setMap(null);
        marker = new google.maps.Marker({
            position: location,
            map: map,
            animation: google.maps.Animation.DROP
        });
        const lat = location.lat();
        const lng = location.lng();
        document.getElementById('latValue').textContent = lat.toFixed(6);
        document.getElementById('lngValue').textContent = lng.toFixed(6);
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
    }

    function myCustomMap(lat, lng) {
        const location = {
            lat: parseFloat(lat),
            lng: parseFloat(lng)
        };
        const map = new google.maps.Map(document.getElementById("mapShower"), {
            zoom: 17,
            center: location,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
            zoomControl: true
        });
        new google.maps.Marker({
            position: location,
            map: map,
            title: "Issue Location"
        });
    }

    function showToast(message, type = 'info') {
        document.querySelectorAll('.toast').forEach(toast => toast.remove());
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}" style="margin-right: 0.5rem;"></i>
        <span>${message}</span>
    `;
        toast.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; border-radius: 6px; color: white;
        font-weight: 500; z-index: 10000; display: flex; align-items: center; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        animation: slideIn 0.3s ease-out; max-width: 400px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
    `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    function extractPostData(cardElement) {
        try {
            const title = cardElement.querySelector('.issue-title')?.textContent || 'No Title';
            const description = cardElement.querySelector('.issue-description')?.textContent || 'No Description';
            const complition_message = cardElement.querySelector('.issue-complition')?.textContent || 'Not completed';
            const category = cardElement.querySelector('.category-tag')?.textContent?.trim() || 'Other';
            const location = cardElement.querySelector('.issue-meta span:first-child')?.textContent?.replace(/.*\s/,
                '') || 'Unknown Location';
            const reportedBy = cardElement.querySelector('.issue-meta span:nth-child(2)')?.textContent?.replace(
                'Reported by ', '') || 'Unknown';
            const timestamp = cardElement.querySelector('.timestamp')?.textContent || 'Unknown';
            const status = cardElement.querySelector('.status-badge')?.textContent?.trim() || 'Unknown';
            const votesCount = cardElement.querySelector('.vote-count')?.textContent || '0';
            const unvotesCount = cardElement.querySelector('.unvote-count')?.textContent || '0';
            const isVoted = cardElement.querySelector('.vote-btn')?.classList.contains('voted') || false;
            const isUnvoted = cardElement.querySelector('.unvote-btn')?.classList.contains('unvoted') || false;
            postId = cardElement.querySelector('.vote-btn')?.dataset?.postId || '';
            const imageSrc = cardElement.querySelector('.issue-image img')?.src || '';
            const lat = cardElement.dataset.latitude || '27.7172';
            const lng = cardElement.dataset.longitude || '85.3240';

            return {
                id: postId,
                title,
                description,
                complition_message,
                category,
                location,
                reportedBy,
                timestamp,
                status,
                votesCount: parseInt(votesCount),
                unvotesCount: parseInt(unvotesCount),
                isVoted,
                isUnvoted,
                imageSrc,
                priority: 'Medium',
                coordinates: `${lat}, ${lng}`,
                latitude: lat,
                longitude: lng
            };
        } catch (error) {
            console.error('Error extracting post data:', error);
            return null;
        }
    }

    function openPostPopup(postData) {
        currentPostData = postData;
        document.getElementById('popupPostImage').src = postData.imageSrc ||
            'https://via.placeholder.com/800x400?text=No+Image';
        document.getElementById('popupPostTitle').textContent = postData.title;
        document.getElementById('popupPostDescription').textContent = postData.description;
        document.getElementById('popupPostComplition').textContent = postData.complition_message;
        document.getElementById('popupPostCategory').innerHTML =
            `<i class="fas fa-tag" style="margin-right: 0.5rem;"></i><span>${postData.category}</span>`;
        document.getElementById('popupImageLocation').innerHTML =
            `<i class="fas fa-map-marker-alt" style="margin-right: 0.5rem;"></i>${postData.location}`;
        document.getElementById('popupReportedBy').textContent = postData.reportedBy;
        document.getElementById('popupDateReported').textContent = new Date(postData.timestamp).toLocaleDateString(
            'en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        document.getElementById('popupPriority').textContent = postData.priority;
        document.getElementById('popupVoteCount').textContent = postData.votesCount;
        document.getElementById('popupLastUpdated').textContent = postData.timestamp;
        document.getElementById('popupCoordinates').textContent = postData.coordinates;
        document.getElementById('popupUnvoteCount').textContent = postData.unvotesCount;

        const statusBadge = document.getElementById('popupPostStatus');
        statusBadge.textContent = postData.status;
        statusBadge.className = `post-status-badge status-${postData.status.toLowerCase()}`;

        const popupVoteBtn = document.getElementById('popupVoteBtn');
        const voteActionBtn = document.getElementById('popupVoteActionBtn');
        const popupUnvoteBtn = document.getElementById('popupUnvoteBtn');
        const unvoteActionBtn = document.getElementById('popupUnvoteActionBtn');

        popupVoteBtn.classList.toggle('voted', postData.isVoted);
        voteActionBtn.classList.toggle('voted', postData.isVoted);
        popupUnvoteBtn.classList.toggle('unvoted', postData.isUnvoted);
        unvoteActionBtn.classList.toggle('unvoted', postData.isUnvoted);

        document.getElementById('postPopup').classList.add('active');
        document.body.style.overflow = 'hidden';
        myCustomMap(postData.latitude, postData.longitude);
    }

    function closePostPopup() {
        document.getElementById('postPopup').classList.remove('active');
        document.body.style.overflow = 'auto';
        currentPostData = null;
        document.getElementById('mapShower').innerHTML = `
        <div style="text-align: center;">
            <i class="fas fa-map" style="font-size: 2rem; margin-bottom: 0.5rem; color: #6366f1;"></i>
            <div>Click to view location on map</div>
            <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 0.25rem;">
                Coordinates: <span id="popupCoordinates">${currentPostData ? currentPostData.coordinates : '34.0, 32.0'}</span>
            </div>
        </div>
    `;
    }

    function handleVoteAction(buttonEl, action) {
        if (buttonEl.classList.contains('loading')) return;
        const postId = buttonEl.dataset.postId;
        buttonEl.classList.add('loading');
        buttonEl.style.opacity = '0.6';
        buttonEl.style.cursor = 'not-allowed';

        const basePath = '/ncc_hacks/';
        fetch(`${basePath}check_distance.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id_post=${postId}`
            })
            .then(response => response.text())
            .then(resFcd => {
                if (resFcd.trim() === "1") {
                    showToast("You are out of the region! You must be within 1 km radius to vote on this issue.",
                        "error");
                    buttonEl.classList.remove('loading');
                    buttonEl.style.opacity = '1';
                    buttonEl.style.cursor = 'pointer';
                    return;
                }
                if (resFcd.trim() === "0") {
                    processVoteAction(buttonEl, action, postId);
                } else {
                console.log(resFcd)
                    showToast('Unable to verify your location. Please try again.', 'error');
                    buttonEl.classList.remove('loading');
                    buttonEl.style.opacity = '1';
                    buttonEl.style.cursor = 'pointer';
                }
            })
            .catch(error => {
                console.error('Fetch error in check_distance:', error);
                showToast('Failed to check your location. Please check your internet connection.', 'error');
                buttonEl.classList.remove('loading');
                buttonEl.style.opacity = '1';
                buttonEl.style.cursor = 'pointer';
            });
    }

    function processVoteAction(buttonEl, action, postId) {
        if (buttonEl.classList.contains('loading')) return;

        const countSpan = buttonEl.querySelector(`.${action}-count`);
        if (!countSpan) {
            console.error(`Count span for ${action} not found for post ID: ${postId}`);
            showToast('Error: Unable to update count. Please try again.', 'error');
            return;
        }

        const isAlreadyActioned = buttonEl.classList.contains(action === 'vote' ? 'voted' : 'unvoted');
        if (isAlreadyActioned && action === 'vote' && buttonEl.classList.contains('voted')) {
            showToast('You have already voted for this post.', 'error');
            return;
        }
        if (isAlreadyActioned && action === 'unvote' && buttonEl.classList.contains('unvoted')) {
            showToast('You have already unvoted this post.', 'error');
            return;
        }

        buttonEl.classList.add('loading');
        buttonEl.style.opacity = '0.6';
        buttonEl.style.cursor = 'not-allowed';

        const currentCount = parseInt(countSpan.textContent) || 0;
        const newCount = isAlreadyActioned ? currentCount - 1 : currentCount + 1;
        buttonEl.classList.toggle(action === 'vote' ? 'voted' : 'unvoted', !isAlreadyActioned);
        updateButtonStyle(buttonEl, !isAlreadyActioned, action);
        countSpan.textContent = Math.max(0, newCount);

        const oppositeButton = buttonEl.parentElement.querySelector(action === 'vote' ? '.unvote-btn' : '.vote-btn');
        if (oppositeButton && oppositeButton.classList.contains(action === 'vote' ? 'unvoted' : 'voted')) {
            oppositeButton.classList.remove(action === 'vote' ? 'unvoted' : 'voted');
            updateButtonStyle(oppositeButton, false, action === 'vote' ? 'unvote' : 'vote');
            const oppositeCountSpan = oppositeButton.querySelector(action === 'vote' ? '.unvote-count' : '.vote-count');
            if (oppositeCountSpan) {
                oppositeCountSpan.textContent = Math.max(0, parseInt(oppositeCountSpan.textContent) - 1 || 0);
            }
        }

        const basePath = '/ncc_hacks/';
        fetch(`${basePath}${action === 'vote' ? 'like_handler.php' : 'unlike_handler.php'}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `post_id=${postId}&action=${isAlreadyActioned ? 'remove' : 'add'}`
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    countSpan.textContent = action === 'vote' ? data.likes_count : data.unlikes_count;
                    updateButtonState(buttonEl, data.action === (action === 'vote' ? 'liked' : 'unliked'), action);
                    if (oppositeButton) {
                        const oppositeCountSpan = oppositeButton.querySelector(action === 'vote' ? '.unvote-count' :
                            '.vote-count');
                        if (oppositeCountSpan) {
                            oppositeCountSpan.textContent = action === 'vote' ? data.unlikes_count : data
                                .likes_count;
                        }
                    }
                    updateMainCardAction(postId, action, data.action === (action === 'vote' ? 'liked' : 'unliked'),
                        action === 'vote' ? data.likes_count : data.unlikes_count);
                    showToast(data.action === (action === 'vote' ? 'liked' : 'unliked') ?
                        `${action.charAt(0).toUpperCase() + action.slice(1)} added successfully!` :
                        `${action.charAt(0).toUpperCase() + action.slice(1)} removed successfully!`, 'success');
                } else {
                    countSpan.textContent = currentCount;
                    buttonEl.classList.toggle(action === 'vote' ? 'voted' : 'unvoted');
                    updateButtonStyle(buttonEl, isAlreadyActioned, action);
                    showToast(data.message || 'Error occurred while processing your request', 'error');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                countSpan.textContent = currentCount;
                buttonEl.classList.toggle(action === 'vote' ? 'voted' : 'unvoted');
                updateButtonStyle(buttonEl, isAlreadyActioned, action);
                showToast(`Network error: ${error.message}`, 'error');
            })
            .finally(() => {
                buttonEl.classList.remove('loading');
                buttonEl.style.opacity = '1';
                buttonEl.style.cursor = 'pointer';
            });
    }

    function processPopupAction(action, popupBtn, actionBtn, countElement) {
        if (!countElement || !currentPostData) {
            console.error(`Count element or post data for ${action} not found`);
            showToast('Error: Unable to update count. Please try again.', 'error');
            return;
        }

        const isCurrentlyActioned = popupBtn.classList.contains(action === 'vote' ? 'voted' : 'unvoted');
        if (isCurrentlyActioned && action === 'vote' && popupBtn.classList.contains('voted')) {
            showToast('You have already voted for this post.', 'error');
            return;
        }
        if (isCurrentlyActioned && action === 'unvote' && popupBtn.classList.contains('unvoted')) {
            showToast('You have already unvoted this post.', 'error');
            return;
        }

        const currentCount = parseInt(countElement.textContent) || 0;
        const newCount = isCurrentlyActioned ? currentCount - 1 : currentCount + 1;

        popupBtn.classList.toggle(action === 'vote' ? 'voted' : 'unvoted', !isCurrentlyActioned);
        actionBtn.classList.toggle(action === 'vote' ? 'voted' : 'unvoted', !isCurrentlyActioned);
        countElement.textContent = Math.max(0, newCount);

        const oppositeBtn = document.getElementById(action === 'vote' ? 'popupUnvoteBtn' : 'popupVoteBtn');
        const oppositeActionBtn = document.getElementById(action === 'vote' ? 'popupUnvoteActionBtn' :
            'popupVoteActionBtn');
        const oppositeCount = document.getElementById(action === 'vote' ? 'popupUnvoteCount' : 'popupVoteCount');

        if (oppositeBtn.classList.contains(action === 'vote' ? 'unvoted' : 'voted')) {
            oppositeBtn.classList.remove(action === 'vote' ? 'unvoted' : 'voted');
            oppositeActionBtn.classList.remove(action === 'vote' ? 'unvoted' : 'voted');
            if (oppositeCount) {
                oppositeCount.textContent = Math.max(0, parseInt(oppositeCount.textContent) - 1 || 0);
            }
        }

        const basePath = '/ncc_hacks/';
        fetch(`${basePath}${action === 'vote' ? 'like_handler.php' : 'unlike_handler.php'}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `post_id=${currentPostData.id}&action=${isCurrentlyActioned ? 'remove' : 'add'}`
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    countElement.textContent = action === 'vote' ? data.likes_count : data.unlikes_count;
                    popupBtn.classList.toggle(action === 'vote' ? 'voted' : 'unvoted', data.action === (action ===
                        'vote' ? 'liked' : 'unliked'));
                    actionBtn.classList.toggle(action === 'vote' ? 'voted' : 'unvoted', data.action === (action ===
                        'vote' ? 'liked' : 'unliked'));
                    updateMainCardAction(currentPostData.id, action, data.action === (action === 'vote' ? 'liked' :
                        'unliked'), action === 'vote' ? data.likes_count : data.unlikes_count);
                    if (oppositeCount) {
                        oppositeCount.textContent = action === 'vote' ? data.unlikes_count : data.likes_count;
                    }
                    showToast(data.action === (action === 'vote' ? 'liked' : 'unliked') ?
                        `${action.charAt(0).toUpperCase() + action.slice(1)} added successfully!` :
                        `${action.charAt(0).toUpperCase() + action.slice(1)} removed successfully!`, 'success');
                } else {
                    countElement.textContent = currentCount;
                    popupBtn.classList.toggle(action === 'vote' ? 'voted' : 'unvoted');
                    actionBtn.classList.toggle(action === 'vote' ? 'voted' : 'unvoted');
                    showToast(data.message || 'Error occurred while processing your request', 'error');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                countElement.textContent = currentCount;
                popupBtn.classList.toggle(action === 'vote' ? 'voted' : 'unvoted');
                actionBtn.classList.toggle(action === 'vote' ? 'voted' : 'unvoted');
                showToast(`Network error: ${error.message}`, 'error');
            })
            .finally(() => {
                popupBtn.classList.remove('loading');
                actionBtn.classList.remove('loading');
                popupBtn.style.opacity = '1';
                actionBtn.style.opacity = '1';
            });
    }

    function handleVoteAction(buttonEl, action) {
        if (buttonEl.classList.contains('loading')) return;
        const postId = buttonEl.dataset.postId;

        const basePath = '/ncc_hacks';
        fetch(`${basePath}check_distance.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id_post=${postId}`
            })
            .then(response => response.text())
            .then(resFcd => {
                if (resFcd.trim() === "1") {
                    showToast("You are out of the region! You must be within 2 km radius to vote on this issue.",
                        "error");
                    return;
                }
                if (resFcd.trim() === "0") {
                    processVoteAction(buttonEl, action, postId);
                } else {
                    showToast('Unable to verify your location. Please try again.', 'error');
                }
            })
            .catch(error => {
                console.error('Fetch error in check_distance:', error);
                showToast('Failed to check your location. Please check your internet connection.', 'error');
            });
    }

    function handlePopupAction(action) {
        if (!currentPostData) return;
        const popupBtn = document.getElementById(`popup${action.charAt(0).toUpperCase() + action.slice(1)}Btn`);
        const actionBtn = document.getElementById(`popup${action.charAt(0).toUpperCase() + action.slice(1)}ActionBtn`);
        const countElement = document.getElementById(`popup${action.charAt(0).toUpperCase() + action.slice(1)}Count`);

        if (popupBtn.classList.contains('loading')) return;
        popupBtn.classList.add('loading');
        actionBtn.classList.add('loading');
        popupBtn.style.opacity = '0.6';
        actionBtn.style.opacity = '0.6';

        const basePath = '/ncc_hacks/';
        fetch(`${basePath}check_distance.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id_post=${currentPostData.id}`
            })
            .then(response => response.text())
            .then(resFcd => {
                if (resFcd.trim() === "1") {
                    showToast("You are out of the region! You must be within 1 km radius to vote on this issue.",
                        "error");
                    popupBtn.classList.remove('loading');
                    actionBtn.classList.remove('loading');
                    popupBtn.style.opacity = '1';
                    actionBtn.style.opacity = '1';
                    return;
                }
                if (resFcd.trim() === "0") {
                    processPopupAction(action, popupBtn, actionBtn, countElement);
                } else {
                    showToast('Unable to verify your location. Please try again.', 'error');
                    popupBtn.classList.remove('loading');
                    actionBtn.classList.remove('loading');
                    popupBtn.style.opacity = '1';
                    actionBtn.style.opacity = '1';
                }
            })
            .catch(error => {
                console.error('Fetch error in check_distance:', error);
                showToast('Failed to check your location. Please check your internet connection.', 'error');
                popupBtn.classList.remove('loading');
                actionBtn.classList.remove('loading');
                popupBtn.style.opacity = '1';
                actionBtn.style.opacity = '1';
            });
    }

    function updateMainCardAction(postId, action, isActioned, newCount) {
        const mainBtn = document.querySelector(`[data-post-id="${postId}"].${action}-btn`);
        if (mainBtn) {
            const countSpan = mainBtn.querySelector(`.${action}-count`);
            mainBtn.classList.toggle(action === 'vote' ? 'voted' : 'unvoted', isActioned);
            updateButtonStyle(mainBtn, isActioned, action);
            if (countSpan) countSpan.textContent = newCount;

            const oppositeBtn = document.querySelector(
                `[data-post-id="${postId}"].${action === 'vote' ? 'unvote' : 'vote'}-btn`);
            if (oppositeBtn && oppositeBtn.classList.contains(action === 'vote' ? 'unvoted' : 'voted')) {
                oppositeBtn.classList.remove(action === 'vote' ? 'unvoted' : 'voted');
                updateButtonStyle(oppositeBtn, false, action === 'vote' ? 'unvote' : 'vote');
                const oppositeCountSpan = oppositeBtn.querySelector(action === 'vote' ? '.unvote-count' :
                '.vote-count');
                if (oppositeCountSpan) {
                    oppositeCountSpan.textContent = action === 'vote' ? currentPostData.unvotesCount : currentPostData
                        .votesCount;
                }
            }
        }
    }

    function updateButtonStyle(button, isActive, action) {
        button.style.background = isActive ? (action === 'vote' ? '#10b981' : '#ef4444') : 'white';
        button.style.color = isActive ? 'white' : (action === 'vote' ? '#10b981' : '#ef4444');
    }

    function updateButtonState(button, isActive, action) {
        button.classList.toggle(action === 'vote' ? 'voted' : 'unvoted', isActive);
        updateButtonStyle(button, isActive, action);
    }

    document.getElementById('issueForm').addEventListener('submit', (e) => {
        const latValue = document.getElementById('latitude').value;
        const lngValue = document.getElementById('longitude').value;
        if (!latValue || !lngValue) {
            showToast('Please select a location on the map before submitting.', 'error');
            e.preventDefault();
        }
    });

    document.querySelector('.photo-upload').addEventListener('dragover', (e) => {
        e.preventDefault();
        e.currentTarget.classList.add('dragover');
    });

    document.querySelector('.photo-upload').addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.currentTarget.classList.remove('dragover');
    });

    document.querySelector('.photo-upload').addEventListener('drop', (e) => {
        e.preventDefault();
        e.currentTarget.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('photoInput').files = files;
            const event = new Event('change', {
                bubbles: true
            });
            document.getElementById('photoInput').dispatchEvent(event);
        }
    });

    document.getElementById('photoInput').addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                document.querySelector('.photo-upload').innerHTML = `
                <div style="text-align: center;">
                    <img src="${e.target.result}" style="max-width: 100%; max-height: 150px; border-radius: 8px; margin-bottom: 0.5rem;">
                    <div style="color: #10b981;">
                        <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i> Photo uploaded successfully
                    </div>
                    <div style="color: #64748b; font-size: 0.9rem;">
                        Click to change photo
                    </div>
                </div>
            `;
            };
            reader.readAsDataURL(file);
        }
    });

    document.querySelector('.report-btn').addEventListener('click', openModal);

    document.querySelector('.search-input').addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('.issue-card').forEach(card => {
            const title = card.querySelector('.issue-title').textContent.toLowerCase();
            const description = card.querySelector('.issue-description').textContent.toLowerCase();
            const location = card.querySelector('.issue-meta').textContent.toLowerCase();
            card.style.display = (title.includes(searchTerm) || description.includes(searchTerm) ||
                location.includes(searchTerm)) ? 'block' : 'none';
        });
    });

    document.getElementById('reportModal').addEventListener('click', (e) => {
        if (e.target === e.currentTarget) closeModal();
    });

    document.getElementById('postPopup').addEventListener('click', (e) => {
        if (e.target === e.currentTarget) closePostPopup();
    });

    document.getElementById('mapShower').addEventListener('click', () => {
        if (currentPostData) {
            const coords = currentPostData.coordinates.split(', ');
            window.open(`https://www.google.com/maps?q=${coords[0]},${coords[1]}`, '_blank');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (document.getElementById('reportModal').classList.contains('active')) closeModal();
            if (document.getElementById('postPopup').classList.contains('active')) closePostPopup();
        }
    });

    document.getElementById('notificationToggle').addEventListener('click', () => {
        document.getElementById('notificationPopup').classList.toggle('show');
    });

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('popupVoteBtn').addEventListener('click', () => handlePopupAction('vote'));
        document.getElementById('popupUnvoteBtn').addEventListener('click', () => handlePopupAction('unvote'));
        document.getElementById('popupVoteActionBtn').addEventListener('click', () => handlePopupAction(
            'vote'));
        document.getElementById('popupUnvoteActionBtn').addEventListener('click', () => handlePopupAction(
            'unvote'));
        document.querySelectorAll('.issue-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('.vote-btn, .unvote-btn, .stat a')) return;
                const postData = extractPostData(card);
                if (postData) openPostPopup(postData);
            });
        });
        document.querySelectorAll('.vote-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                handleVoteAction(button, 'vote');
            });
        });
        document.querySelectorAll('.unvote-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                handleVoteAction(button, 'unvote');
            });
        });
    });

    function sharePost() {
        if (!currentPostData) return;
        if (navigator.share) {
            navigator.share({
                title: currentPostData.title,
                text: `Check out this community issue: ${currentPostData.title}`,
                url: window.location.href
            });
        } else {
            navigator.clipboard.writeText(
                    `Check out this community issue: "${currentPostData.title}" at ${currentPostData.location}`)
                .then(() => showToast('Share link copied to clipboard!', 'success'));
        }
    }

    function reportPost() {
        if (!currentPostData) return;
        const reasons = ['Inappropriate content', 'Spam', 'Fake/Misleading information', 'Offensive language',
            'Copyright violation', 'Other'
        ];
        const reason = prompt(
            `Why are you reporting this post?\n\n${reasons.map((r, i) => `${i+1}. ${r}`).join('\n')}\n\nEnter the number (1-${reasons.length}):`
        );
        if (reason && reason >= 1 && reason <= reasons.length) {
            showToast(`Post reported for: ${reasons[reason-1]}`, 'success');
            console.log(`Reported post ${currentPostData.id} for: ${reasons[reason-1]}`);
        }
    }

    setInterval(() => {
        if (postId) {
            fetch(`get_comments.php?problem_id=${postId}`)
                .then(response => response.text())
                .then(data => document.getElementById('comments-container').innerHTML = data)
                .catch(error => console.error('Error loading comments:', error));
        }
    }, 1000);

    document.getElementById('btn').addEventListener('click', () => {
        const comment = document.getElementById('ipt').value.trim();
        if (!comment) {
            showToast('Please enter a comment', 'error');
            return;
        }
        fetch('save_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `problem_id=${postId}&comment=${encodeURIComponent(comment)}`
            })
            .then(response => response.text())
            .then(() => {
                document.getElementById('ipt').value = '';
                if (postId) {
                    fetch(`get_comments.php?problem_id=${postId}`)
                        .then(response => response.text())
                        .then(data => document.getElementById('comments-container').innerHTML = data);
                }
            })
            .catch(error => console.error('Error saving comment:', error));
    });
    </script>
</body>

</html>