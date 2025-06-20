<?php
session_start();
include '../../db_connection/connection.php';

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'], $_POST['password'])) {
    $conn = OpenConnection();
    if ($conn) {
        $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE email=?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($user_id, $hashed_password);
                $stmt->fetch();
                if (password_verify($password, $hashed_password)) {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['email'] = $email;
                    header("Location: ../../index.php?login=1");
                    exit;
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
            $stmt->close();
        } else {
            $error = "Database error.";
        }
        CloseConnection($conn);
    } else {
        $error = "Database connection failed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../../static/img//icon/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../../static/css/sign_up.css">
    <title>Sign In - Job Portal</title>
    <style>
        .right_section .container {
            max-height: 520px;
            height: auto;
        }
        .password_toggle_icon {
            display: flex !important;
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
        .popup-notification .close-btn {
            display: none;
        }
        @media (max-width: 900px) {
            .left_section {
                display: none;
            }
            .right_section {
                flex: 1 1 100%;
            }
            .modal-content {
                min-width: 0;
                max-width: 100%;
                padding: 18px 4vw 12px 4vw;
            }
        }
    </style>
</head>
<body>
    <div id="popupNotification" class="popup-notification">
        <span id="popupMessage"></span>
    </div>
    <div class="right_section">
        <div class="container">
            <h2 id="form-title">Sign In</h2>
            <form id="signInForm" action="sign_in.php" method="POST">
                <div class="form_group">
                    <input type="email" name="email" placeholder="Your Email" required>
                </div>
                <div class="form_group">
                    <div class="input_wrapper">
                        <input type="password" id="password" name="password" placeholder="Password" required>
                        <span id="togglePassword" class="password_toggle_icon">
                            <img id="passwordToggleImage" src="../../static/img/icon/hidden.png" alt="Toggle Password" draggable="false">
                        </span>
                    </div>
                </div>
                <button type="submit">Sign In</button>
            </form>
            <p class="footer">Don't have an account? <a href="sign_up.php">Sign Up</a></p>
        </div>
    </div>
    <div class="left_section">
        <img src="../../static/img/icon/logo_job.png" alt="Hire Path Logo">
    </div>
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

        <?php if (isset($error)): ?>
            showPopup(<?php echo json_encode($error); ?>, 'error');
        <?php endif; ?>

        function setupPasswordToggle(inputId, toggleId, imgId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById(toggleId);
            const img = document.getElementById(imgId);
            if (toggle && input && img) {
                toggle.addEventListener('click', function () {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    img.src = type === 'password'
                        ? '../../static/img/icon/hidden.png'
                        : '../../static/img/icon/visible.png';
                });
            }
        }
        setupPasswordToggle('password', 'togglePassword', 'passwordToggleImage');
    </script>
</body>
</html>
