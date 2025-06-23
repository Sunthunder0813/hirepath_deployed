<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: employee_sign_in.php");
    exit();
}


include '../../db_connection/connection.php';
$conn = OpenConnection();


if (isset($_GET['application_id'])) {
    $application_id = intval($_GET['application_id']);

    
    $query = "UPDATE `applications` SET `status` = 'accepted' WHERE `application_id` = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();

    
    $stmt = $conn->prepare("SELECT u.email, u.username, j.title, e.company_name, e.company_cover FROM applications a JOIN users u ON a.job_seeker_id = u.user_id JOIN jobs j ON a.job_id = j.job_id JOIN users e ON j.employer_id = e.user_id WHERE a.application_id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $stmt->bind_result($email, $username, $job_title, $company_name, $company_cover);
    $stmt->fetch();
    $stmt->close();

    
    $company_img_url = '';
    if (!empty($company_cover)) {
        
        if (strpos($company_cover, 'http') === 0) {
            $company_img_url = $company_cover;
        } else {
            $company_img_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/Hirepath/$company_cover";
        }
    }

    
    require_once 'application_email.php';
    $subject = $company_name;
    $body = [
        'username' => $username,
        'job_title' => $job_title,
        'company_name' => $company_name,
        'company_img_url' => $company_img_url
    ];
    sendEmail($email, $subject, $body, $username);

    
    // Get job_id for the approved application
    $job_id_query = "SELECT job_id FROM applications WHERE application_id = ?";
    $stmt = $conn->prepare($job_id_query);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $stmt->bind_result($job_id);
    $stmt->fetch();
    $stmt->close();

    if ($job_id) {
        // Reject all other applications for this specific job (job_id)
        $other_apps_query = "SELECT a.application_id, u.email, u.username FROM applications a JOIN users u ON a.job_seeker_id = u.user_id WHERE a.job_id = ? AND a.application_id != ? AND a.status != 'rejected'";
        $stmt = $conn->prepare($other_apps_query);
        $stmt->bind_param("ii", $job_id, $application_id);
        $stmt->execute();
        $stmt->bind_result($other_app_id, $other_email, $other_username);

        $rejected_applicants = [];
        while ($stmt->fetch()) {
            $rejected_applicants[] = [
                'application_id' => $other_app_id,
                'email' => $other_email,
                'username' => $other_username
            ];
        }
        $stmt->close();

        // Update status to 'rejected' for other applications
        if (!empty($rejected_applicants)) {
            $reject_query = "UPDATE applications SET status = 'rejected' WHERE application_id = ?";
            $stmt = $conn->prepare($reject_query);
            foreach ($rejected_applicants as $app) {
                $stmt->bind_param("i", $app['application_id']);
                $stmt->execute();

                // Send rejection email
                require_once 'application_email.php';
                $reject_body = [
                    'username' => $app['username'],
                    'job_title' => $job_title,
                    'company_name' => $company_name,
                    'rejected' => true
                ];
                sendEmail($app['email'], $company_name, $reject_body, $app['username']);
            }
            $stmt->close();
        }

        // Set job as inactive
        $update_job_query = "UPDATE jobs SET status = 'inactive' WHERE job_id = ?";
        $stmt = $conn->prepare($update_job_query);
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: view_applications.php?message=Application accepted and email sent successfully");
    exit();
} else {
    echo "Invalid request.";
}
?>
