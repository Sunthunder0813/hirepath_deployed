<?php
session_start();


if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../admin_sign_in.php");
    exit();
}


include '../../db_connection/connection.php';
$conn = OpenConnection();


if (isset($_GET['job_id'])) {
    $job_id = intval($_GET['job_id']);

    
    $query = "UPDATE `jobs` SET `status` = 'rejected' WHERE `job_id` = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $job_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Job rejected successfully.";
    } else {
        $_SESSION['error'] = "Failed to reject the job.";
    }

    $stmt->close();
} else {
    $_SESSION['error'] = "Invalid job ID.";
}

$conn->close();
header("Location: admin_dashboard.php?tab=pending");
exit();
