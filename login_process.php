<?php
// session_start();
// require_once 'config.php';

// if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//     $email = trim($_POST['email']);
//     $password = $_POST['password'];
//     $remember = isset($_POST['remember']);

//     if (empty($email) || empty($password)) {
//         $_SESSION['error_message'] = "Email and password are required";
//         header("Location: login.php");
//         exit();
//     }

//     $sql = "SELECT id, first_name, last_name, email, password, user_type, status, admin_con verified FROM users WHERE email = '$email' LIMIT 1";
//     $result = mysqli_query($conn, $sql);
//     $user = mysqli_fetch_assoc($result);
// password_hash($password, PASSWORD_DEFAULT);
//     if ($user && password_verify($password, $user['password'])) {
//         if ($user['verified'] !== 'yes') {
//             $_SESSION['error_message'] = "Your account is not verified. Please complete verification.";
//             header("Location: login.php");
//             exit();
//         }

//         $_SESSION['user_id'] = $user['id'];
//         $_SESSION['user_email'] = $user['email'];
//         $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
//         $_SESSION['user_type'] = $user['user_type'];
//         $_SESSION['logged_in'] = true;
//         $_SESSION['admin_con'] = $user['admin_con'];

//         if ($remember) {
//             $cookie_name = "remember_user";
//             $cookie_value = base64_encode($user['email']);
//             setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");
//         }

//         $update_sql = "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = '{$user['id']}'";
//         mysqli_query($conn, $update_sql);

//         if ($user['user_type'] == 'admin') {
//             $_SESSION['success_message'] = "Welcome back, Admin!";
//             header("Location: admin.php");
//         } else {
//             $_SESSION['success_message'] = "Login successful!";
//             header("Location: index.php");
//         }
//         exit();
//     } else {
//         $_SESSION['error_message'] = "Invalid email or password";
//         header("Location: login.php");
//         exit();
//     }
// } else {
//     $_SESSION['error_message'] = "Invalid access method";
//     header("Location: login.php");
//     exit();
// }

session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Check empty fields
    if (empty($email) || empty($password)) {
        $_SESSION['error_message'] = "Email and password are required.";
        header("Location: login.php");
        exit();
    }

    // Fetch user using prepared statement
    $stmt = $conn->prepare("SELECT id, first_name, last_name,user_type, email, password, admin_con FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Verify user + password
    if ($user && password_verify($password, $user['password'])) {

        // Store session values
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . " " . $user['last_name'];
        $_SESSION['logged_in'] = true;
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['admin_con'] = $user['admin_con'];

        // Redirect based on admin_con column
        if ($user['admin_con'] === 'all') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit();

    } else {
        $_SESSION['error_message'] = "Invalid email or password.";
        header("Location: login.php");
        exit();
    }

} else {
    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: login.php");
    exit();
}
?>

?>
