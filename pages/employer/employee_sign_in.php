<?php
session_start();
include '../../db_connection/connection.php';

$conn = OpenConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check for status as well
    $stmt = $conn->prepare("SELECT user_id, password, status FROM users WHERE username = ? AND user_type = 'client'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $stored_password, $status);
        $stmt->fetch();
        if ($status === 'blocked') {
            $error = "Your account has been blocked. This may be due to a violation of our terms or other reasons.";
        } elseif ($status === 'active') {
            if (password_verify($password, $stored_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                header("Location: Employee_dashboard.php?login=1");
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password, or your account is not active.";
        }
    } else {
        $error = "Invalid username or password.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../../static/img/icon/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../../static/css/employee_sign_in.css">
    <title>Sign In - Employer Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            display: flex;
            height: 100vh;
            background-color: #0A2647;
        }
        .left_section {
            flex: 0.5; 
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .left_section img {
            max-width: 500px;   
            margin-bottom: 80px;
        }
        .left_section h1 {
            font-size: 24px;
            text-align: center;
        }
        .right_section {
            flex: 1.5; 
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: url('../../static/img/icon/login.jpg');
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
        }
        .container {
            background: rgba(255, 255, 255, 0.92);
            padding: 48px 36px 36px 36px;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(10, 38, 71, 0.18);
            width: 90%;
            height: 100%;
            max-width: 600px;
            max-height: 500px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative; 
        }
        h2 {
            color: #0A2647;
            margin-bottom: 28px;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-align: center;
        }
        .form_group {
            margin-bottom: 18px;
            width: 100%;
        }
        input {
            background: rgba(255, 255, 255, 0.98);
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #bfc9d1;
            border-radius: 7px;
            font-size: 1rem;
            margin-top: 4px;
            outline: none;
            transition: border 0.2s;
            box-shadow: 0 1px 2px rgba(10, 38, 71, 0.04);
        }
        input:focus {
            border: 1.5px solid #0A2647;
            background: #f7fbff;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, #0A2647 60%, #144272 100%);
            color: white;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(10, 38, 71, 0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        button:hover {
            background: linear-gradient(90deg, #144272 60%, #0A2647 100%);
            box-shadow: 0 4px 16px rgba(10, 38, 71, 0.13);
        }
        .footer {
            text-align: center;
            margin-top: 28px;
            margin-bottom: 0;
            width: 100%;
            position: static;
            background: none;
            box-shadow: none;
            font-size: 1rem;
            color: #333;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .popup-notification {
            position: fixed;
            bottom: 32px;
            right: 32px;
            min-width: 260px;
            max-width: 350px;
            padding: 18px 32px 18px 18px;
            border-radius: 8px;
            color: #fff;
            font-size: 1.1em;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s, transform 0.5s;
            box-shadow: 0 4px 16px rgba(0,0,0,0.13);
            text-align: center;
        }
        .popup-notification.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
        .popup-notification.success {
            background: #28a745;
        }
        .popup-notification.error {
            background: #dc3545;
        }
    </style>
</head>
<body>
    
    <div id="popupNotification" class="popup-notification">
        <span id="popupMessage"></span>
    </div>
    <div class="left_section">
        <img src="../../static/img/icon/logo_employ.png" alt="Hire Path Logo">
        
    </div>
    <div class="right_section">
        <div class="container">
            
            <h2 id="formHeader">Sign In</h2>
            <form id="signInForm" method="POST" action="employee_sign_in.php">
                <div class="form_group">
                    <input type="text" id="username" name="username" placeholder="Enter your Username" required>
                </div>
                <div class="form_group">
                    <div class="input_wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your Password" required />
                        <span id="togglePassword" class="password_toggle_icon">
                            <img id="passwordToggleImage" src="../../static/img/icon/hidden.png" alt="Toggle Password" draggable="false">
                        </span>
                    </div>
                </div>
                <button type="submit">Sign In</button>
            </form>
            <p class="footer">
                <span class="footer-content">
                    <span style="color:#0A2647;font-weight:600;">
                        Use your <em>Job Seeker</em> credentials to log in as Employer.
                    </span>
                </span>
            </p>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const togglePassword = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');
            const toggleImage = document.getElementById('passwordToggleImage');

            passwordField.addEventListener('focus', function () {
                if (passwordField.value.length > 0) {
                    togglePassword.classList.add('show');
                }
            });

            passwordField.addEventListener('input', function () {
                if (passwordField.value.length > 0) {
                    togglePassword.classList.add('show');
                } else {
                    togglePassword.classList.remove('show');
                }
            });

            passwordField.addEventListener('blur', function () {
                if (passwordField.value.length === 0) {
                    togglePassword.classList.remove('show');
                }
            });

            togglePassword.addEventListener("click", function () {
                if (passwordField.type === "password") {
                    passwordField.type = "text";
                    toggleImage.src = "../../static/img/icon/visible.png"; 
                } else {
                    passwordField.type = "password";
                    toggleImage.src = "../../static/img/icon/hidden.png"; 
                }
            });
        });
    </script>
    <script>
        
        function showPopup(message, type, redirectUrl = null) {
            const popup = document.getElementById('popupNotification');
            const msg = document.getElementById('popupMessage');
            popup.className = 'popup-notification ' + type;
            msg.textContent = message;
            popup.classList.add('show');
            setTimeout(() => {
                popup.classList.remove('show');
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
            }, 3000);
        }
        
        (function() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('reg') === '1') {
                showPopup('Registration successful! Please sign in.', 'success');
            }
        })();
        <?php if (!empty($error)): ?>
            showPopup(<?php echo json_encode($error); ?>, 'error');
        <?php endif; ?>
    </script>
</body>
</html>