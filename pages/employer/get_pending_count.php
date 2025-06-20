<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

include '../../db_connection/connection.php';
$conn = OpenConnection();

$user_id = $_SESSION['user_id'];
$query = "SELECT COUNT(*) AS count FROM `applications` a
          JOIN `jobs` j ON a.job_id = j.job_id
          WHERE j.employer_id = ? AND (a.status = 'pending' OR a.status = 'reviewed')";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['count' => $row['count']]);
$conn->close();
?>
