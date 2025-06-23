<?php
require '../../imports/src/Exception.php';
require '../../imports/src/PHPMailer.php';
require '../../imports/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $activation_token, $username, &$error = null) {
    $mail = new PHPMailer(true);
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Use Gmail's SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'labacelemschool@gmail.com'; // Your Gmail address
        $mail->Password = 'hszkwyrssrcagdda'; // Your Gmail password or app password
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Email settings
        $mail->setFrom('labacelemschool@gmail.com', 'Hire Path System');
        $mail->addAddress($to);
        $mail->Subject = $subject;

        // Build the activation link here
        $activation_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/activate.php?token=" . $activation_token;

        // Build the activation email body here
        $body = "<h2>Welcome, $username!</h2>
            <p>Thank you for registering at Hire Path. Please click the link below to activate your account:</p>
            <p><a href='$activation_link'>$activation_link</a></p>
            <p>If you did not register, please ignore this email.</p>";

        $mail->Body = $body;
        $mail->isHTML(true);

        $mail->send();
        return true;
    } catch (Exception $e) {
        $error = "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}
?>