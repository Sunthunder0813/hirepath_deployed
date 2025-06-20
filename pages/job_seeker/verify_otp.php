<?php
session_start();
header('Content-Type: application/json');
if (
    isset($_POST['otp_code'], $_POST['otp_email']) &&
    isset($_SESSION['otp_code'], $_SESSION['otp_email'], $_SESSION['otp_time'])
) {
    $code = trim($_POST['otp_code']);
    $email = trim($_POST['otp_email']);
    if (
        $_SESSION['otp_email'] === $email &&
        $_SESSION['otp_code'] == $code &&
        (time() - $_SESSION['otp_time']) <= 600
    ) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid or expired OTP.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid or expired OTP.']);
}
?>