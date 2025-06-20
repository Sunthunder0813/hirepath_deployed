<?php
header('Content-Type: application/json');
include '../../db_connection/connection.php';

$conn = OpenConnection();

$username = isset($_GET['username']) ? trim($_GET['username']) : '';

if ($username === '') {
    echo json_encode(['error' => 'No username provided']);
    exit;
}

$stmt = $conn->prepare("SELECT username, email FROM users WHERE username = ? AND user_type = 'employer' LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($found_username, $email);

if ($stmt->fetch()) {
    echo json_encode([
        'username' => $found_username,
        'email' => $email
    ]);
} else {
    echo json_encode(['error' => 'User not found']);
}

$stmt->close();
$conn->close();
