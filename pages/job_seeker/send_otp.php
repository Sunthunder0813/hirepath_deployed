<?php
require '../../imports/src/Exception.php';
require '../../imports/src/PHPMailer.php';
require '../../imports/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $body, $username, &$error = null) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'labacelemschool@gmail.com';
        $mail->Password = 'hszkwyrssrcagdda';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('labacelemschool@gmail.com', 'Hire Path System');
        $mail->addAddress($to);
        $mail->Subject = $subject;

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <h2 style='color: #007bff;'>Dear $username,</h2>
                <p>We have received a request to reset your password. To proceed, please use the following One-Time Password (OTP):</p>
                <div style='text-align: center; margin: 20px 0;'>
                    <span style='display: inline-block; font-size: 24px; font-weight: bold; color: #d9534f; padding: 10px 20px; border: 2px dashed #d9534f; border-radius: 8px;'>
                        Your OTP code is: $body
                    </span>
                </div>
                <p style='font-size: 14px; color: #555;'>Please note that this OTP is valid for a limited time and should not be shared with anyone.</p>
                <p>If you did not request this, please contact our support team immediately.</p>
                <p style='margin-top: 20px;'>Best regards,<br><strong>Hire Path Team</strong></p>
                <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                <p style='font-size: 12px; color: #999;'>This is an automated email. Please do not reply to this message.</p>
            </div>
        ";
        $mail->isHTML(true);

        $mail->send();
        return true;
    } catch (Exception $e) {
        $error = "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    include '../../db_connection/connection.php';
    $email = trim($_POST['email']);
    $conn = OpenConnection();
    $response = ['success' => false];

    if (!$conn) {
        $response['error'] = "Database connection failed.";
    } else {
        $stmt = $conn->prepare("SELECT username FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($username);
                $stmt->fetch();
                $otp = rand(100000, 999999);
                session_start();
                $_SESSION['otp_email'] = $email;
                $_SESSION['otp_code'] = $otp;
                $_SESSION['otp_time'] = time();
                $subject = "Your Hire Path OTP Code";
                $error = null;
                if (sendEmail($email, $subject, $otp, $username, $error)) {
                    $response['success'] = true;
                } else {
                    $response['error'] = $error ?: "Failed to send OTP email.";
                }
            } else {
                $response['error'] = "Email not found.";
            }
            $stmt->close();
        } else {
            $response['error'] = "Database error.";
        }
        CloseConnection($conn);
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
