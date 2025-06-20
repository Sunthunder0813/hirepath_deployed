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

    
    $query = "UPDATE `applications` SET `status` = 'rejected' WHERE `application_id` = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $application_id);

    if ($stmt->execute()) {
        
        $stmt2 = $conn->prepare("SELECT u.email, u.username, j.title, e.company_name
            FROM applications a
            JOIN users u ON a.job_seeker_id = u.user_id
            JOIN jobs j ON a.job_id = j.job_id
            JOIN users e ON j.employer_id = e.user_id
            WHERE a.application_id = ?");
        $stmt2->bind_param("i", $application_id);
        $stmt2->execute();
        $stmt2->bind_result($email, $username, $job_title, $company_name);
        $stmt2->fetch();
        $stmt2->close();

        
        require_once 'application_email.php';
        $subject = $company_name;
        $body = [
            'username' => $username,
            'job_title' => $job_title,
            'company_name' => $company_name,
            'rejected' => true
        ];
        sendEmail($email, $subject, $body, $username);

        header("Location: view_applications.php?message=Application rejected successfully");
    } else {
        echo "Error rejecting application.";
    }
} else {
    echo "Invalid request.";
}
?>
