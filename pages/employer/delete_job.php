<?php
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['username'])) {
    header("Location: employee_sign_in.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_ids']) && is_array($_POST['job_ids'])) {
    require '../../db_connection/connection.php'; // Adjust the path as necessary
    $conn = OpenConnection();

    $user_id = $_SESSION['user_id'];
    $job_ids = array_map('intval', $_POST['job_ids']);

    
    $in = str_repeat('?,', count($job_ids) - 1) . '?';
    $sql = "DELETE FROM jobs WHERE job_id IN ($in) AND employer_id = ?";
    $stmt = $conn->prepare($sql);

    
    $types = str_repeat('i', count($job_ids)) . 'i';
    $params = array_merge($job_ids, [$user_id]);
    $stmt->bind_param($types, ...$params);

    $stmt->execute();

    $deleted = $stmt->affected_rows;

    $stmt->close();
    $conn->close();

    header("Location: view_jobs.php?deleted=" . ($deleted > 0 ? 1 : 0));
    exit();
} else {
    
    header("Location: view_jobs.php");
    exit();
}
