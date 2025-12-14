<?php


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
