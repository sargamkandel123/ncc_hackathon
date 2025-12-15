<?php
 include 'config.php';
session_start();
$action = $_GET['del'];


if($action == 'post'){
    $id = $_GET['id'];
    $sql = "DELETE FROM problem_posts WHERE id = ".$id;

    if($_SESSION['user_id'] == $_GET['userid']){
        $res = mysqli_query($conn, $sql);
        if($res){
            header('location: index.php');
        }
    }
}

if($action == 'cmt'){
     $id = $_GET['id'];
    $sql = "DELETE FROM problem_comments WHERE id = ".$id;

    if($_SESSION['user_id'] == $_GET['userid']){
        $res = mysqli_query($conn, $sql);
        if($res){
            header('location: index.php');
        }
    }
}
?>