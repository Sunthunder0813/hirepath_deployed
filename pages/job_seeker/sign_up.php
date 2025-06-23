<?php
session_start();
include '../../db_connection/connection.php';
// Add this to include the email sender
include_once 'activate_account.php';

$register_success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $error = '';

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        
        $valid = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\W)(?=.*\d).{8,}$/'; 
        if (!preg_match($valid, $password)) {
            $error = "Password must be at least 8 characters, include 1 uppercase, 1 lowercase, 1 special character, and 1 number.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT); 
            $conn = OpenConnection();
            // Start transaction for safety
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $error = "Email is already registered.";
                    throw new Exception($error); 
                }

                $stmt->close(); 
                // Insert user with status 'processing' and empty company fields
                $stmt = $conn->prepare("INSERT INTO users (username, password, email, user_type, created_at, company_name, company_tagline, company_image, company_description, company_cover, status) VALUES (?, ?, ?, 'client', NOW(), NULL, NULL, NULL, NULL, NULL, 'processing')");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("sss", $username, $hashed_password, $email); 
                
                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;
                    $conn->commit(); 
                    // Send activation email
                    $subject = "Activate your Hire Path account";
                    $mail_error = '';
                    if (sendEmail($email, $subject, $username, $mail_error)) {
                        $register_success = true;
                    } else {
                        $error = "Account created, but failed to send activation email: $mail_error";
                    }
                } else {
                    $error = "Error creating account: " . $stmt->error;
                    throw new Exception($error); 
                }
            } catch (Exception $e) {
                $conn->rollback(); 
                $error = "An error occurred: " . $e->getMessage(); 
            } finally {
                if (isset($stmt) && $stmt) {
                    $stmt->close();
                }
                CloseConnection($conn);
            }
        }
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
    <style>
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
    </style>
    <title>Sign Up - Job Portal</title>
</head>
<body>
    <div id="popupNotification" class="popup-notification">
        <span id="popupMessage"></span>
    </div>
    <div class="right_section">
        <div class="container">
            <h2>Sign Up</h2>
            <form action="sign_up.php" method="POST">
                <div class="form_group">
                    <input type="text" name="username" placeholder="Register Username" required>
                </div>
                <div class="form_group">
                    <input type="email" name="email" placeholder="Register Email" required>
                </div>
                <div class="form_group">
                    <div class="input_wrapper">
                        <input type="password" id="password" name="password" placeholder="Register Password" required>
                        <span id="togglePassword" class="password_toggle_icon">
                            <img id="passwordToggleImage" src="../../static/img/icon/hidden.png" alt="Toggle Password" draggable="false">
                        </span>
                        <ul id="passwordChecklist" class="password-checklist">
                            <li id="password_length">At least 8 characters</li>
                            <li id="password_uppercase">At least 1 uppercase letter</li>
                            <li id="password_lowercase">At least 1 lowercase letter</li>
                            <li id="password_special">At least 1 special character</li>
                            <li id="password_number">At least 1 number</li>
                        </ul>
                    </div>
                </div>
                <div class="form_group">
                    <div class="input_wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                        <span id="toggleConfirmPassword" class="password_toggle_icon">
                            <img id="confirmPasswordToggleImage" src='../../static/img/icon/hidden.png' alt="Toggle Confirm Password" draggable="false">
                        </span>
                    </div>
                </div>
                <button type="submit">Register</button>
            </form>
            <p class="footer">Already have an account? <a href="sign_in.php">Sign In</a></p>
        </div>
    </div>
    <div class="left_section">
        <img src="../../static/img/icon/logo_job.png" alt="Hire Path Logo">
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirm_password');
            const passwordChecklist = document.getElementById('passwordChecklist');

            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const passwordToggleImage = document.getElementById('passwordToggleImage');
            const confirmPasswordToggleImage = document.getElementById('confirmPasswordToggleImage');

            function setupToggle(toggleElement, field, imageElement) {
                toggleElement.addEventListener('click', () => {
                    if (field.type === 'password') {
                        field.type = 'text';
                        imageElement.src = '../../static/img/icon/visible.png';
                    } else {
                        field.type = 'password';
                        imageElement.src = '../../static/img/icon/hidden.png';
                    }
                });
            }

            function toggleVisibility(field, toggleElement) {
                if (field.value.length > 0) {
                    toggleElement.style.display = 'flex';
                } else {
                    toggleElement.style.display = 'none';
                }
            }

            passwordField.addEventListener('input', () => {
                toggleVisibility(passwordField, togglePassword);
            });

            confirmPasswordField.addEventListener('input', () => {
                toggleVisibility(confirmPasswordField, toggleConfirmPassword);
            });

            toggleVisibility(passwordField, togglePassword);
            toggleVisibility(confirmPasswordField, toggleConfirmPassword);

            function validatePassword() {
                const password = passwordField.value;

                const lengthValid = password.length >= 8;
                const uppercaseValid = /[A-Z]/.test(password);
                const lowercaseValid = /[a-z]/.test(password);
                const specialValid = /\W/.test(password);
                const numberValid = /\d/.test(password);

                updateChecklistItem('password_length', lengthValid);
                updateChecklistItem('password_uppercase', uppercaseValid);
                updateChecklistItem('password_lowercase', lowercaseValid);
                updateChecklistItem('password_special', specialValid);
                updateChecklistItem('password_number', numberValid);
            }

            function updateChecklistItem(id, isValid) {
                const listItem = document.getElementById(id);
                if (isValid) {
                    listItem.classList.add('valid');
                } else {
                    listItem.classList.remove('valid');
                }
            }

            function checkPasswordMatch() {
                if (confirmPasswordField.value.length === 0) {
                    confirmPasswordField.classList.remove('error', 'valid');
                } else if (passwordField.value !== confirmPasswordField.value) {
                    confirmPasswordField.classList.add('error');
                    confirmPasswordField.classList.remove('valid');
                } else {
                    confirmPasswordField.classList.add('valid');
                    confirmPasswordField.classList.remove('error');
                }
            }

            passwordField.addEventListener('focus', () => {
                passwordChecklist.classList.add('visible');
            });

            passwordField.addEventListener('blur', () => {
                if (passwordField.value.length === 0) {
                    passwordChecklist.classList.remove('visible');
                }
            });
            passwordField.addEventListener('focus', () => {
                passwordChecklist.style.display = 'block';
                passwordChecklist.classList.add('visible');
            });

            passwordField.addEventListener('blur', () => {
                setTimeout(() => { 
                    if (!newPasswordField.matches(':focus') && newPasswordField.value.length === 0) {
                        passwordChecklist.style.display = 'none'; 
                        passwordChecklist.classList.remove('visible');
                    }
                }, 200);
            });

            passwordField.addEventListener('input', () => {
                passwordChecklist.style.display = 'block';
                passwordChecklist.classList.add('visible');
                validatePassword(); 
            });

            passwordField.addEventListener('input', validatePassword);
            confirmPasswordField.addEventListener('input', checkPasswordMatch);
            setupToggle(togglePassword, passwordField, passwordToggleImage);
            setupToggle(toggleConfirmPassword, confirmPasswordField, confirmPasswordToggleImage);
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

        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
            <?php if ($register_success): ?>
                showPopup('Registration successful! Please check your email to activate your account.', 'success', 'sign_in.php');
            <?php elseif (!empty($error)): ?>
                showPopup(<?php echo json_encode($error); ?>, 'error');
            <?php endif; ?>
        <?php endif; ?>
    </script>
</body>
</html>