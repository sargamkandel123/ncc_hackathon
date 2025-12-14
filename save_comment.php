<?php
include 'config.php';
session_start();
if(isset($_POST['problem_id'],$_POST['comment'])){
    $problem_id = intval($_POST['problem_id']);
    $user_id = $_SESSION['user_id'];
    $comment = $conn->real_escape_string($_POST['comment']);

    $sql = "INSERT INTO problem_comments (problem_id, user_id, comment, created_at, updated_at)
            VALUES ('$problem_id', '$user_id', '$comment', NOW(), NOW())";
    $conn->query($sql);
}
?>