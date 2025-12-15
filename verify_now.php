<?Php
include 'config.php';
 $id = $_GET['idd'];
 $sql = "UPDATE users SET verified = 'yes'";
 $res = mysqli_query($conn, $sql);

 header("location: dashboard.php");

?>