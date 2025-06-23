<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['pending' => [], 'accepted' => [], 'rejected' => []]);
    exit();
}

$user_id = $_SESSION['user_id'];
include '../../db_connection/connection.php';
$conn = OpenConnection();

$query = "SELECT a.*, j.title AS job_title, u.username AS applicant_name, a.resume_link
          FROM `applications` a
          JOIN `jobs` j ON a.job_id = j.job_id
          JOIN `users` u ON a.job_seeker_id = u.user_id
          WHERE j.employer_id = ?
          ORDER BY a.applied_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$pending = [];
$accepted = [];
$rejected = [];

while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'pending' || $row['status'] === 'reviewed') {
        $pending[] = $row;
    } elseif ($row['status'] === 'accepted') {
        $accepted[] = $row;
    } elseif ($row['status'] === 'rejected') {
        $rejected[] = $row;
    }
}

echo json_encode([
    'pending' => $pending,
    'accepted' => $accepted,
    'rejected' => $rejected
]);
