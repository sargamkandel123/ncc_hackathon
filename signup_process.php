<?php
session_start();
require_once 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $lat = $_POST['lat'];
    $lon = $_POST['lon'];

    // Profile image upload
    $profileImageName = $_FILES['profile_picture']['name'];
    $profileImageTmp = $_FILES['profile_picture']['tmp_name'];
    $kyc1Data = $_POST['kyc1'];
    $kyc2Data = $_POST['kyc2'];

    $uploadDir = "Uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Handle profile image
    $profileImagePath = $uploadDir . basename($profileImageName);
    $uploadSuccess = move_uploaded_file($profileImageTmp, $profileImagePath);

    // Handle KYC images (base64 data)
    $kycFrontName = '';
    $kycBackName = '';
    if (!empty($kyc1Data)) {
        $kycFrontName = 'kyc_front_' . time() . '_' . uniqid() . '.jpg';
        $kycFrontPath = $uploadDir . $kycFrontName;
        $kyc1Data = str_replace('data:image/jpeg;base64,', '', $kyc1Data);
        $kyc1Data = str_replace(' ', '+', $kyc1Data);
        $kyc1Binary = base64_decode($kyc1Data);
        if ($kyc1Binary !== false) {
            $uploadSuccess = $uploadSuccess && file_put_contents($kycFrontPath, $kyc1Binary);
        } else {
            $uploadSuccess = false;
        }
    }
    if (!empty($kyc2Data)) {
        $kycBackName = 'kyc_back_' . time() . '_' . uniqid() . '.jpg';
        $kycBackPath = $uploadDir . $kycBackName;
        $kyc2Data = str_replace('data:image/jpeg;base64,', '', $kyc2Data);
        $kyc2Data = str_replace(' ', '+', $kyc2Data);
        $kyc2Binary = base64_decode($kyc2Data);
        if ($kyc2Binary !== false) {
            $uploadSuccess = $uploadSuccess && file_put_contents($kycBackPath, $kyc2Binary);
        } else {
            $uploadSuccess = false;
        }
    }

    $errors = [];

    // Basic validations
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($password) || strlen($password) < 6) $errors[] = "Password must be at least 6 characters long";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (empty($profileImageName)) $errors[] = "Profile picture is required";
    if (empty($kyc1Data)) $errors[] = "KYC front image is required";
    if (empty($kyc2Data)) $errors[] = "KYC back image is required";
    if (!$uploadSuccess) $errors[] = "File upload failed";

    if (empty($errors)) {
        $check_email = "SELECT id FROM users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $check_email);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $errors[] = "Email already exists";
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (first_name, last_name, email, phone, password, image, user_type, lon, lat, kyc_front, kyc_back) 
                VALUES (?, ?, ?, ?, ?, ?, 'normal', ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssdsss", $first_name, $last_name, $email, $phone, $hashed_password, $profileImageName, $lon, $lat, $kycFrontName, $kycBackName);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Account created successfully! Please login.";
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }

    if (!empty($errors)) {
        $_SESSION['error_message'] = implode("<br>", $errors);
        header("Location: signup.php");
        exit();
    }
}
?>