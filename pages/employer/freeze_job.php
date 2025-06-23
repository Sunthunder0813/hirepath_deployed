<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['job_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$job_id = intval($_POST['job_id']);
$employer_id = $_SESSION['user_id'];

include '../../db_connection/connection.php';
$conn = OpenConnection();

// Only allow the employer to freeze their own job
$stmt = $conn->prepare("UPDATE jobs SET status = 'freeze' WHERE job_id = ? AND employer_id = ?");
$stmt->bind_param("ii", $job_id, $employer_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed']);
}

$stmt->close();
$conn->close();
