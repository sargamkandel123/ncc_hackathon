<?php
include 'config.php';

if (isset($_POST['category'])) {
    $category = $conn->real_escape_string($_POST['category']);
    
    $sql = "SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) as user_name 
            FROM problem_posts p 
            LEFT JOIN users u ON p.user_id = u.id 
            WHERE p.category = '$category' 
            ORDER BY p.created_at DESC";
    
    $result = $conn->query($sql);
    $problems = [];
    
    while ($row = $result->fetch_assoc()) {
        $problems[] = $row;
    }
    
    echo json_encode(['problems' => $problems]);
}

$conn->close();
?>