<?php
session_start();
include '../../db_connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: sign_in.php');
    exit();
}

$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

if ($job_id <= 0) {
    die("Invalid or missing job ID.");
}

$conn = OpenConnection(); 

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($username);
    $stmt->fetch();
    $stmt->close();
}

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($email);
    $stmt->fetch();
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ? AND job_seeker_id = ?");
$stmt->bind_param("ii", $job_id, $user_id);
$stmt->execute();
$stmt->bind_result($application_count);
$stmt->fetch();
$stmt->close();

if ($application_count > 0) {
    header("Location: application_status.php?status=failure&message=already_applied");
    exit();
}

$job_details = null;
if ($job_id > 0) {
    $stmt = $conn->prepare("SELECT title, description, salary, location, company_name, skills, education FROM jobs WHERE job_id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $stmt->bind_result($title, $description, $salary, $location, $company_name, $skills, $education);
    if ($stmt->fetch()) {
        $job_details = [
            'title' => $title,
            'description' => $description,
            'salary' => $salary,
            'location' => $location,
            'company_name' => $company_name,
            'skills' => $skills,
            'education' => $education
        ];
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../../static/img/icon/favicon.png" type="image/x-icon">
    <title>Upload Resume</title>
    <style>
        body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background-color: #f4f4f4;
    padding-top: 60px;
    color: #333;
}
nav {
background: #333;
position: fixed;
top: 0;
left: 0;
width: 100%;
display: flex;
justify-content: space-between;
align-items: center;
box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
z-index: 1000;
}

.logo a {
    display: flex;
    align-items: center;
    text-decoration: none;
    margin-left: 10px;
    color: white;
    font-size: 1.5em;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    letter-spacing: 1px;
    transition: color 0.3s ease;
}

.logo a img {
    display: block;
    height: 40px;
    margin-left: 10px;
    transition: transform 0.3s ease;
    object-fit: contain;
}

.logo a:hover {
color: #00c6ff;
}

.logo a:hover img {
    transform: scale(1.1);
}

.nav-links {
list-style: none;
display: flex;
align-items: center;
padding: 0;
margin: 0;
gap: 20px;
}

.nav-links li {
display: inline;
}

.nav-links a {
text-decoration: none;
color: white;
padding: 10px 15px;
border-radius: 5px;
transition: background 0.3s ease, transform 0.2s ease;
font-weight: bold;
}

.nav-links a:hover {
background: #555;
}

.sign-out-button {
padding: 10px 15px;
border-radius: 5px;
text-decoration: none;
font-weight: bold;
transition: background 0.3s ease, transform 0.2s ease;
}
.profile-dropdown {
position: relative;
}

.profile-dropdown > a {
display: inline-block;
padding: 10px 15px;
text-decoration: none;
color: white;
cursor: pointer;
}

.profile-dropdown .dropdown-menu {
display: none;
position: absolute;
top: 145%;
left: 0;
background-color: #ffffff;
box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
list-style: none;
padding: 10px 0;
margin: 0;
border-radius: 8px;
min-width: 100%;
z-index: 1000;
}

.profile-dropdown:hover .dropdown-menu,
.profile-dropdown.active .dropdown-menu {
display: block;
}
.dropdown-menu.show {
    display: block;
}

.profile-dropdown .dropdown-menu li {
border-bottom: 1px solid #f0f0f0;
text-align: center;
}

.profile-dropdown .dropdown-menu li a {
text-decoration: none;
color: #333;
font-size: 14px;
display: block;
transition: background 0.3s ease, color 0.3s ease;
text-align: center;
}

.profile-dropdown .dropdown-menu li a:hover {
background-color: #f8f9fa;
color: #007BFF;
}






.container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    max-width: 1400px;
    margin: 80px auto 0;
    background: #ffffff;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}
.left-section, .right-section {
    flex: 1;
    min-width: 400px;
    padding: 25px;
    border: 1px solid #ddd;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}
.left-section {
    max-width: 45%;
    height: 100%;
    overflow-y: auto;
}
.right-section {
    max-width: 50%;
    height: 100%;
    overflow-y: auto;
}
h2, h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-weight: 700;
    font-size: 1.8rem;
}
form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
label {
    font-weight: bold;
    color: #555;
    font-size: 1rem;
}
input[type="file"] {
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
    background-color: #f9f9f9;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
input[type="file"]:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
}
button {
    padding: 15px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: bold;
    transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
}
button:hover {
    background: #218838;
    transform: scale(1.05);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}
button#toggle-details {
    margin-top: 15px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: bold;
    transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
}
button#toggle-details:hover {
    background: #0056b3;
    transform: scale(1.05);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}
#preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin-top: 20px;
    border: 1px dashed #ccc;
    padding: 20px;
    border-radius: 8px;
    background-color: #f9f9f9;
}
#file-preview {
    width: 100%;
    height: 400px;
    border: none;
    margin-bottom: 10px;
}
.centered-message {
    text-align: center;
    margin: 0;
    padding: 10px;
    color: #555;
    font-size: 1rem;
}
.right-section div {
    margin-bottom: 20px;
}
.right-section div p {
    margin: 5px 0;
    font-size: 1rem;
}
.right-section div p strong {
    color: #2c3e50;
}
.right-section div.description-box {
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
    line-height: 1.6;
    font-size: 1rem;
}
#full-details {
    margin-top: 20px;
}
@media (max-width: 768px) {
    .container {
        flex-direction: column;
    }
    .left-section, .right-section {
        max-width: 100%;
    }
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
    </style>
</head>
<body>
<nav>
    <p class="logo">
        <a href="../../index.php">
            <img src="../../static/img/icon/logo.png" alt="Hire Path Logo">
        </a>
    </p>
    <ul class="nav-links">
        <li><a href="employee_sign_in.php">Post a Job</a></li>
        <?php if (!empty($username)): ?>
            <li><a href="application.php">Application</a></li>
        <?php endif; ?>
        <?php if (!empty($username)): ?>
            <li class="profile-dropdown">
            <a><?php echo htmlspecialchars($email); ?> <span style="font-size: 1em;">&#9660;</span></a>
                <ul class="dropdown-menu">
                    <li><a href="../../logout.php" class="sign-out-button">Sign Out</a></li>
                </ul>
            </li>
        <?php else: ?>
            <li><a href="sign_in.php">Sign In</a></li>
        <?php endif; ?>
    </ul>
</nav>
    <!-- Popup notification markup -->
    <div id="popupNotification" class="popup-notification">
        <span id="popupMessage"></span>
    </div>
    <div class="container">
        <div class="left-section">
            <h2>Upload Your Resume</h2>
            <form id="upload-form" action="handle_upload.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                <label for="resume">Select your resume (PDF, DOCX):</label>
                <input type="file" name="resume" id="resume" accept=".pdf,.docx" required>
                <button type="submit" aria-label="Submit your resume">Submit</button>
            </form>
            <div id="preview" style="display: none;">
                <h3>File Preview:</h3>
                <iframe id="file-preview"></iframe>
                <p id="file-message" class="centered-message"></p>
            </div>
        </div>
        <div class="right-section">
            <h3>Job Details</h3>
            <?php if ($job_details): ?>
                <div>
                    <h4><?php echo htmlspecialchars($job_details['title']); ?></h4>
                    <p><strong>Company:</strong> <?php echo htmlspecialchars($job_details['company_name']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($job_details['location']); ?></p>
                    <p><strong>Salary:</strong> ₱<?php echo htmlspecialchars(number_format($job_details['salary'], 2)); ?></p>
                    <p><strong>Skills Required:</strong> <?php echo htmlspecialchars($job_details['skills']); ?></p>
                    <p><strong>Education Required:</strong> <?php echo htmlspecialchars($job_details['education']); ?></p>
                    <button id="toggle-details" aria-label="Toggle full job details">Show Full Details</button>
                    <div id="full-details" style="display: none;">
                        <p><strong>Description:</strong></p>
                        <div class="description-box">
                            <?php echo nl2br(htmlspecialchars(html_entity_decode($job_details['description']))); ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p>No job details found.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function showPopup(message, type) {
            const popup = document.getElementById('popupNotification');
            const msg = document.getElementById('popupMessage');
            popup.className = 'popup-notification ' + type;
            msg.textContent = message;
            popup.classList.add('show');
            setTimeout(() => {
                popup.classList.remove('show');
            }, 3000);
        }

        document.getElementById('resume').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('preview');
            const filePreview = document.getElementById('file-preview');
            const fileMessage = document.getElementById('file-message');

            if (file) {
                // Check file extension and MIME type
                const allowedExtensions = ['pdf', 'docx'];
                const fileName = file.name;
                const fileExt = fileName.split('.').pop().toLowerCase();
                const allowedMimeTypes = [
                    'application/pdf',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];
                if (!allowedExtensions.includes(fileExt) || !allowedMimeTypes.includes(file.type)) {
                    showPopup('Invalid file type. Only PDF and DOCX files are allowed.', 'error');
                    event.target.value = '';
                    preview.style.display = 'none';
                    return;
                }

                const reader = new FileReader();

                reader.onload = function(e) {
                    const fileType = file.type;
                    if (fileType === 'application/pdf') {
                        filePreview.src = e.target.result; 
                        fileMessage.textContent = '';
                        preview.style.display = 'block'; 
                    } else if (fileType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                        filePreview.src = ''; 
                        fileMessage.textContent = 'This file will be converted to PDF for preview.'; 
                        preview.style.display = 'block'; 
                    } else {
                        filePreview.src = ''; 
                        fileMessage.textContent = 'Unsupported file type. Please upload a PDF or DOCX file.';
                        preview.style.display = 'block'; 
                    }
                };

                if (file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                    reader.readAsArrayBuffer(file);
                } else {
                    reader.readAsDataURL(file);
                }
            } else {
                preview.style.display = 'none'; 
            }
        });
    </script>
    <script>
        document.getElementById('toggle-details').addEventListener('click', function () {
            const fullDetails = document.getElementById('full-details');
            if (fullDetails.style.display === 'none') {
                fullDetails.style.display = 'block';
                this.textContent = 'Hide Full Details';
            } else {
                fullDetails.style.display = 'none';
                this.textContent = 'Show Full Details';
            }
        });
        document.addEventListener('DOMContentLoaded', () => {
    const profileDropdown = document.querySelector('.profile-dropdown');

    profileDropdown.addEventListener('click', (e) => {
        e.preventDefault();
        profileDropdown.classList.toggle('active');
    });

    document.addEventListener('click', (e) => {
        if (!profileDropdown.contains(e.target)) {
            profileDropdown.classList.remove('active');
        }
    });

    const dropdownMenu = document.querySelector('.profile-dropdown .dropdown-menu');
    dropdownMenu.addEventListener('click', (e) => {
        e.stopPropagation();
    });
});
    </script>
</body>
</html>