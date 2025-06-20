<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../admin_sign_in.php");
    exit();
}

include '../../db_connection/connection.php';
$conn = OpenConnection();
if (!$conn) {
    $_SESSION['error'] = "Database connection failed: " . mysqli_connect_error();
    header("Location: admin_dashboard.php?tab=pending");
    exit();
}

date_default_timezone_set('Asia/Manila');

if (isset($_GET['job_id'])) {
    $job_id = intval($_GET['job_id']);
    $created_at = date('Y-m-d H:i:s');

    $query = "UPDATE `jobs` SET `status` = 'approved', `created_at` = ? WHERE `job_id` = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        $_SESSION['error'] = "Prepare failed: " . $conn->error;
        header("Location: admin_dashboard.php?tab=pending");
        exit();
    }
    $stmt->bind_param("si", $created_at, $job_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Job approved successfully.";
    } else {
        $_SESSION['error'] = "Failed to approve the job. Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    $_SESSION['error'] = "Invalid job ID.";
}

$conn->close();
header("Location: admin_dashboard.php?tab=pending");
exit();
?>
