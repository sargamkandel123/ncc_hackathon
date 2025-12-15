<style>
    #smth{
        padding: 25px 30px;
        font-size: 20px;
        color: white;
        background: red;
        border-radius: 10px;

        text-decoration: none;
        position: absolute;
        bottom: 10% ;
        right: 40%;
    }
</style>
<?php
include 'config.php';

$sql = "SELECT * FROM users WHERE id=".$_GET['id'];
$res = mysqli_query($conn, $sql);

$fetch = mysqli_fetch_assoc($res);

$i1 = $fetch['kyc_back'];
$i2 = $fetch['kyc_front'];
$i3 = $fetch['image'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VERIDY KFC</title>
    <style>
       /* General Page Styling */
body {
    height: 100vh;
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #333;
}

/* Main Container */
#hi {
    width: 90%;
    max-width: 1000px;
    display: flex;
    justify-content: space-around;
    align-items: flex-start;
    gap: 30px;
    flex-wrap: wrap;
    padding: 40px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

/* Each Image Card */
#hi div {
    text-align: center;
    background: #f9f9f9;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

#hi div:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
}

/* Image Styling */
img {
    height: 220px;
    width: 280px;
    object-fit: cover;
    border-radius: 12px;
    border: 3px solid #eee;
}

/* Labels under images */
p {
    margin-top: 12px;
    font-size: 16px;
    font-weight: 600;
    color: #444;
}

/* Verify Button */
#smth {
    padding: 15px 35px;
    font-size: 18px;
    color: white;
    background: linear-gradient(135deg, #ff512f, #dd2476);
    border-radius: 12px;
    text-decoration: none;
    font-weight: bold;
    position: absolute;
    bottom: 5%;
    right: 50%;
    transform: translateX(50%);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
    transition: background 0.3s ease, transform 0.2s ease;
}

#smth:hover {
    background: linear-gradient(135deg, #dd2476, #ff512f);
    transform: translateX(50%) scale(1.08);
}

    </style>
</head>
<body>
    <div id="hi">
       <div>
    <img src="uploads/<?php echo $i1?>" alt="">
    <p>Citizenship Front</p></div> 
    <br>
    <div>
    <img src="uploads/<?php echo $i2?>" alt="">
    <p>Citizenship Back</p>
</div>
    <br>
    <div>
    <img src="uploads/electric.png">
    <p>User Face</p>
</div>
    </div>


    <a id="smth" href="verify_now.php?idd=<?php echo $_GET['id']; ?>">Verify</a>
</body>
</html>