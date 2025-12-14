<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo 0;
    exit;
}

if (!isset($_POST['id_post']) || !is_numeric($_POST['id_post'])) {
    echo 0; 
    exit;
}

$postId = intval($_POST['id_post']);
$current_user = intval($_SESSION['user_id']);

$sqlU = "SELECT lon, lat FROM users WHERE id = $current_user";
$resU = mysqli_query($conn, $sqlU);
$fetchU = mysqli_fetch_assoc($resU);
$lon1 = $fetchU['lon'];
$lat1 = $fetchU['lat'];

$sqlP = "SELECT longitude, latitude FROM problem_posts WHERE id = $postId";
$resP = mysqli_query($conn, $sqlP);
$fetchP = mysqli_fetch_assoc($resP);
$lon2 = $fetchP['longitude'];
$lat2 = $fetchP['latitude'];

function check_distance($lon1, $lat1, $lon2, $lat2) {
    $earthRadius = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;

    return $distance;
}

$distance = check_distance($lon1, $lat1, $lon2, $lat2);

    // echo $distance;
if ($distance > 2) {
    echo 1;
} else {
    echo 0;
}
?>
