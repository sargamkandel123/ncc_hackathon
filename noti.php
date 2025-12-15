<?php
function checkTheDistance($lon1, $lat1, $lon2, $lat2){
    $lon1 = deg2rad($lon1);
    $lat1 = deg2rad($lat1);
    $lon2 = deg2rad($lon2);
    $lat2 = deg2rad($lat2);

    $dLon = $lon2 - $lon1;
    $dLat = $lat2 - $lat1;
    $a = sin($dLat/2) * sin($dLat/2) +
         cos($lat1) * cos($lat2) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    $earthRadius = 6371; 
    $distance = $earthRadius * $c;

    return $distance; 
}
$current_id = $_SESSION['user_id'];

function displayNotification(){
    global $conn;
    $current_id = $_SESSION['user_id'];

    $sql = "SELECT * FROM users WHERE id = $current_id";
    $res = mysqli_query($conn, $sql);
    $fetch = mysqli_fetch_assoc($res);

    $lon1 = $fetch['lon'];
    $lat1 = $fetch['lat'];

    $sql1 = "SELECT * FROM problem_posts ORDER BY created_at DESC";
    $res1 = mysqli_query($conn, $sql1);

    while($fetch1 = mysqli_fetch_assoc($res1)){
        $lon2 = $fetch1['longitude'];
        $lat2 = $fetch1['latitude'];

        $distance = checkTheDistance($lon1, $lat1, $lon2, $lat2);

        if($distance <= 1){ 
            echo "🚨 Problem nearby: " . $fetch1['title'] . " (".round($distance,2)." km away)<br>";
        }
    }
}
?>
