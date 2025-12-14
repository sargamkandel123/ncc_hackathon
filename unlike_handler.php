<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$post_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Start transaction
    mysqli_begin_transaction($conn);

    // Check if user has already unvoted
    $check_unlike_sql = "SELECT id FROM problem_dislikes WHERE user_id = ? AND problem_id = ?";
    $check_unlike_stmt = mysqli_prepare($conn, $check_unlike_sql);
    mysqli_stmt_bind_param($check_unlike_stmt, 'ii', $user_id, $post_id);
    mysqli_stmt_execute($check_unlike_stmt);
    $unlike_result = mysqli_stmt_get_result($check_unlike_stmt);
    $has_unvoted = mysqli_num_rows($unlike_result) > 0;

    // Check if user has voted
    $check_like_sql = "SELECT id FROM problem_likes WHERE user_id = ? AND problem_id = ?";
    $check_like_stmt = mysqli_prepare($conn, $check_like_sql);
    mysqli_stmt_bind_param($check_like_stmt, 'ii', $user_id, $post_id);
    mysqli_stmt_execute($check_like_stmt);
    $like_result = mysqli_stmt_get_result($check_like_stmt);
    $has_voted = mysqli_num_rows($like_result) > 0;

    if ($action === 'add') {
        if ($has_unvoted) {
            echo json_encode(['success' => false, 'message' => 'You have already unvoted this post']);
            mysqli_rollback($conn);
            exit;
        }

        // Remove vote if exists
        if ($has_voted) {
            $delete_like_sql = "DELETE FROM problem_likes WHERE user_id = ? AND problem_id = ?";
            $delete_like_stmt = mysqli_prepare($conn, $delete_like_sql);
            mysqli_stmt_bind_param($delete_like_stmt, 'ii', $user_id, $post_id);
            mysqli_stmt_execute($delete_like_stmt);
        }

        // Add unvote
        $insert_unlike_sql = "INSERT INTO problem_dislikes (user_id, problem_id) VALUES (?, ?)";
        $insert_unlike_stmt = mysqli_prepare($conn, $insert_unlike_sql);
        mysqli_stmt_bind_param($insert_unlike_stmt, 'ii', $user_id, $post_id);
        mysqli_stmt_execute($insert_unlike_stmt);

        $action_result = 'unliked';
    } else if ($action === 'remove') {
        if (!$has_unvoted) {
            echo json_encode(['success' => false, 'message' => 'You have not unvoted this post']);
            mysqli_rollback($conn);
            exit;
        }

        // Remove unvote
        $delete_unlike_sql = "DELETE FROM problem_dislikes WHERE user_id = ? AND problem_id = ?";
        $delete_unlike_stmt = mysqli_prepare($conn, $delete_unlike_sql);
        mysqli_stmt_bind_param($delete_unlike_stmt, 'ii', $user_id, $post_id);
        mysqli_stmt_execute($delete_unlike_stmt);

        $action_result = 'liked';
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        mysqli_rollback($conn);
        exit;
    }

    // Get updated counts
    $likes_count_sql = "SELECT COUNT(*) as count FROM problem_likes WHERE problem_id = ?";
    $likes_count_stmt = mysqli_prepare($conn, $likes_count_sql);
    mysqli_stmt_bind_param($likes_count_stmt, 'i', $post_id);
    mysqli_stmt_execute($likes_count_stmt);
    $likes_count_result = mysqli_stmt_get_result($likes_count_stmt);
    $likes_count = mysqli_fetch_assoc($likes_count_result)['count'];

    $unlikes_count_sql = "SELECT COUNT(*) as count FROM problem_dislikes WHERE problem_id = ?";
    $unlikes_count_stmt = mysqli_prepare($conn, $unlikes_count_sql);
    mysqli_stmt_bind_param($unlikes_count_stmt, 'i', $post_id);
    mysqli_stmt_execute($unlikes_count_stmt);
    $unlikes_count_result = mysqli_stmt_get_result($unlikes_count_stmt);
    $unlikes_count = mysqli_fetch_assoc($unlikes_count_result)['count'];

    // Commit transaction
    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'action' => $action_result,
        'likes_count' => $likes_count,
        'unlikes_count' => $unlikes_count
    ]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>