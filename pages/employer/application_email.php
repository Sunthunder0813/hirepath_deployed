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

        
        if (is_array($body)) {
            if (!empty($body['rejected'])) {
                $mail->Body = "
                    <div style='font-family:sans-serif;max-width:420px;margin:auto;background:#fff;border-radius:6px;border:1px solid #eee;padding:28px 20px;'>
                        <div style='background:#ffdddd;color:#a94442;padding:10px 16px;border-radius:4px;margin-bottom:18px;text-align:center;font-weight:bold;border:1px solid #f5c6cb;'>
                            Application Rejected
                        </div>
                        <h2 style='color:#222;font-size:20px;margin-bottom:14px;text-align:center;'>Application Update</h2>
                        <p style='font-size:15px;color:#222;margin-bottom:14px;text-align:center;'>
                            Dear <strong>" . htmlspecialchars(isset($body['username']) ? $body['username'] : $username) . "</strong>,
                        </p>
                        <p style='font-size:14px;color:#444;margin-bottom:16px;text-align:center;'>
                            Thank you for applying to <strong>" . htmlspecialchars($body['company_name']) . "</strong> for the <strong>" . htmlspecialchars($body['job_title']) . "</strong> position.
                        </p>
                        <p style='font-size:14px;color:#666;margin-bottom:18px;text-align:center;'>
                            We regret to inform you that your application was not selected.
                        </p>
                        <p style='font-size:13px;color:#888;text-align:center;margin-top:24px;'>- " . htmlspecialchars($body['company_name']) . " Team</p>
                        <hr style='border:none;border-top:1px solid #eee;margin:22px 0 10px 0;'>
                        <p style='font-size:11px;color:#bbb;text-align:center;'>Automated email from Hire Path</p>
                    </div>
                ";
            } elseif (!empty($body['reviewed'])) {
                $mail->Body = "
                    <div style='font-family:sans-serif;max-width:420px;margin:auto;background:#fff;border-radius:6px;border:1px solid #eee;padding:28px 20px;'>
                        <div style='background:#e7f3fe;color:#31708f;padding:10px 16px;border-radius:4px;margin-bottom:18px;text-align:center;font-weight:bold;border:1px solid #bce8f1;'>
                            Application Under Review
                        </div>
                        <h2 style='color:#222;font-size:20px;margin-bottom:14px;text-align:center;'>Application Status</h2>
                        <p style='font-size:15px;color:#222;margin-bottom:14px;text-align:center;'>
                            Dear <strong>" . htmlspecialchars(isset($body['username']) ? $body['username'] : $username) . "</strong>,
                        </p>
                        <p style='font-size:14px;color:#444;margin-bottom:16px;text-align:center;'>
                            Your application for <strong>" . htmlspecialchars($body['job_title']) . "</strong> at <strong>" . htmlspecialchars($body['company_name']) . "</strong> is under review.
                        </p>
                        <p style='font-size:14px;color:#666;margin-bottom:18px;text-align:center;'>
                            We will notify you once a decision is made.
                        </p>
                        <p style='font-size:13px;color:#888;text-align:center;margin-top:24px;'>- " . htmlspecialchars($body['company_name']) . " Team</p>
                        <hr style='border:none;border-top:1px solid #eee;margin:22px 0 10px 0;'>
                        <p style='font-size:11px;color:#bbb;text-align:center;'>Automated email from Hire Path</p>
                    </div>
                ";
            } else {
                $mail->Body = "
                    <div style='font-family:sans-serif;max-width:420px;margin:auto;background:#fff;border-radius:6px;border:1px solid #eee;padding:28px 20px;'>
                        <div style='background:#d4edda;color:#155724;padding:10px 16px;border-radius:4px;margin-bottom:18px;text-align:center;font-weight:bold;border:1px solid #c3e6cb;'>
                            Application Approved
                        </div>
                        <h2 style='color:#222;font-size:20px;margin-bottom:14px;text-align:center;'>Congratulations!</h2>
                        <p style='font-size:15px;color:#222;margin-bottom:14px;text-align:center;'>
                            Dear <strong>" . htmlspecialchars(isset($body['username']) ? $body['username'] : $username) . "</strong>,
                        </p>
                        <p style='font-size:14px;color:#444;margin-bottom:16px;text-align:center;'>
                            Your application for <strong>" . htmlspecialchars($body['job_title']) . "</strong> at <strong>" . htmlspecialchars($body['company_name']) . "</strong> has been approved.
                        </p>
                        <p style='font-size:14px;color:#666;margin-bottom:18px;text-align:center;'>
                            We will contact you soon with next steps.
                        </p>
                        <p style='font-size:13px;color:#888;text-align:center;margin-top:24px;'>- " . htmlspecialchars($body['company_name']) . " Team</p>
                        <hr style='border:none;border-top:1px solid #eee;margin:22px 0 10px 0;'>
                        <p style='font-size:11px;color:#bbb;text-align:center;'>Automated email from Hire Path</p>
                    </div>
                ";
            }
        } elseif (strip_tags($body) !== $body) {
            $mail->Body = $body;
        } else {
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
        }
        $mail->isHTML(true);

        $mail->send();
        return true;
    } catch (Exception $e) {
        $error = "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}