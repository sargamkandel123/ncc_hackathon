<?php
 include 'config.php';
session_start();
 if(isset($_GET['problem_id'])){
    $problem_id = intval($_GET['problem_id']);
    $sql = "SELECT * FROM problem_comments WHERE problem_id = '$problem_id' ORDER BY id DESC";
    $result = $conn->query($sql);

    if(mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){
        $user_id = $row['user_id'];
        $sql1 = "SELECT * FROM users WHERE id = $user_id";
        $res1 = mysqli_query($conn, $sql1);

        $fetch = mysqli_fetch_assoc($res1);
        echo '<div class="cmt">
                        <div class="user">
                            <div class="img"><img src="uploads/'.$fetch['image'].'" alt="user photo"></div>
                            <div class="name">'.$fetch['first_name'].'</div>';
                            if($_SESSION['user_id'] == $row['user_id']){
                            echo '<a href="delete.php?id='.$row['id'].'&del=cmt&userid='.$row['user_id'].'"><div class="delete"><i class="fas fa-trash" style="color: red; cursor: pointer"></i></div></a>';
                            }
                            echo'
                        </div>
                        <div class="comment">
                            '.htmlspecialchars($row['comment']).'
                        </div>
                    </div>';
    }
}
else{
    echo '<h2 style="text-align: center; margin: 80px 0 0 0;">No comments Yet</h2>';
}
}
?>