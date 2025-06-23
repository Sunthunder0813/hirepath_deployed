<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../../db_connection/connection.php';

// If your connection.php provides OpenConnection(), use it:
if (!isset($conn) || !$conn) {
    if (function_exists('OpenConnection')) {
        $conn = OpenConnection();
    }
}

if (!isset($conn) || !$conn) {
    echo "Database connection failed.";
    exit();
}

function renderMessage($title, $message, $buttonText = "Go to Sign In", $buttonLink = "../../sign_in.php", $icon = "🔒") {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Activation</title>
    <style>
        body {
            background: #f6f8fa;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 400px;
            margin: 80px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 40px 30px 30px 30px;
            text-align: center;
        }
        h2 {
            color: #2d7ff9;
            margin-bottom: 18px;
        }
        p {
            color: #444;
            margin-bottom: 30px;
        }
        a.button {
            display: inline-block;
            padding: 10px 28px;
            background: #2d7ff9;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        a.button:hover {
            background: #195bb5;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 18px;
            color: #2d7ff9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">{$icon}</div>
        <h2>{$title}</h2>
        <p>{$message}</p>
        <a class="button" href="{$buttonLink}">{$buttonText}</a>
    </div>
</body>
</html>
HTML;
}

if (!isset($_GET['email']) || !filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
    renderMessage(
        "Invalid Activation Link",
        "The activation link is invalid or incomplete.<br><br>
        <strong>Troubleshooting tips:</strong>
        <ul style='text-align:left;display:inline-block;'>
            <li>Make sure you clicked the entire link in your email. Sometimes, email apps (especially on mobile) may break the link into two lines. If so, copy and paste the full link into your browser's address bar.</li>
            <li>If you continue to have issues, try opening the email on a desktop browser.</li>
            <li>If you believe this is an error, please contact support.</li>
        </ul>",
        "Go to Sign In"
    );
    exit();
}

$email = $_GET['email'];

// Debug: Show received email
// echo "Received email: " . htmlspecialchars($email) . "<br>";

$stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE email = ?");
if (!$stmt) {
    renderMessage("Error", "Failed to prepare statement: " . $conn->error);
    exit();
}
$stmt->bind_param("s", $email);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    renderMessage("Account Activated", "Your account has been activated. You can now sign in.", "Sign In", "../../sign_in.php", "🔓");
} else {
    // Check if account is already active
    $check = $conn->prepare("SELECT status FROM users WHERE email = ?");
    if ($check) {
        $check->bind_param("s", $email);
        $check->execute();
        $check->bind_result($status);
        if ($check->fetch() && $status === 'active') {
            renderMessage("Already Activated", "Your account is already activated.", "Sign In", "sign_in.php", "🔓");
        } else {
            renderMessage("Activation Failed", "Invalid or expired activation link.");
        }
        $check->close();
    } else {
        renderMessage("Error", "Failed to prepare check statement: " . $conn->error);
    }
}

$stmt->close();
$conn->close();
?>
