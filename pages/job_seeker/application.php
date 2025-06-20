<?php
session_start();
include '../../db_connection/connection.php';

$conn = OpenConnection();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($username, $email);
    $stmt->fetch();
    $stmt->close();
}

$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
$query = "
    SELECT applications.application_id, applications.job_id, jobs.title AS job_title, 
    applications.resume_link, applications.status, applications.applied_at
    FROM applications
    LEFT JOIN jobs ON applications.job_id = jobs.job_id
    WHERE applications.job_seeker_id = ?";
if ($statusFilter) {
    $query .= " AND applications.status = ?";
}
$query .= " ORDER BY applications.applied_at DESC";
$stmt = $conn->prepare($query);
if ($statusFilter) {
    $stmt->bind_param("is", $user_id, $statusFilter);
} else {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$result = $stmt->get_result();

function getStatusClass($status) {
    $statusClass = 'unknown'; 
    if (strtolower($status) === 'accepted') {
        $statusClass = 'accepted';
    } elseif (strtolower($status) === 'pending') {
        $statusClass = 'pending';
    } elseif (strtolower($status) === 'rejected') {
        $statusClass = 'rejected';
    } elseif (strtolower($status) === 'reviewed') {
        $statusClass = 'reviewed';
    }
    return $statusClass;
}

function getStatusIcon($status, $directory = '../../static/img/icon') {
    $iconPath = $directory . '/unknown.png'; 
    if (strtolower($status) === 'accepted') {
        $iconPath = $directory . '/accepted.png';
    } elseif (strtolower($status) === 'pending') {
        $iconPath = $directory . '/pending.png';
    } elseif (strtolower($status) === 'rejected') {
        $iconPath = $directory . '/rejected.png';
    } elseif (strtolower($status) === 'reviewed') {
        $iconPath = $directory . '/reviewed.png';
    }
    return $iconPath;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../../static/img/icon/favicon.png" type="image/x-icon">
    <title>Job Application</title>
    <style>
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    background-color: #f4f4f4;
    padding-top: 60px; 
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

.nav_links {
list-style: none;
display: flex;
align-items: center;
padding: 0;
margin: 0;
gap: 20px;
}

.nav_links li {
display: inline;
}

.nav_links a {
text-decoration: none;
color: white;
padding: 10px 15px;
border-radius: 5px;
transition: background 0.3s ease, transform 0.2s ease;
font-weight: bold;
}

.nav_links a:hover {
background: #555;
}

.sign_out_button {
padding: 10px 15px;
border-radius: 5px;
text-decoration: none;
font-weight: bold;
transition: background 0.3s ease, transform 0.2s ease;
}
.profile_dropdown {
position: relative;
}

.profile_dropdown > a {
display: inline-block;
padding: 10px 15px;
text-decoration: none;
color: white;
cursor: pointer;
}

.profile_dropdown .dropdown_menu {
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

.profile_dropdown:hover .dropdown_menu,
.profile_dropdown.active .dropdown_menu {
display: block;
}

.profile_dropdown .dropdown_menu li {
border-bottom: 1px solid #f0f0f0;
text-align: center;
}

.profile_dropdown .dropdown_menu li a {
text-decoration: none;
color: #333;
font-size: 14px; 
display: block;
transition: background 0.3s ease, color 0.3s ease;
text-align: center; 
}

.profile_dropdown .dropdown_menu li a:hover {
background-color: #f8f9fa;
color: #007BFF;
}




.container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
}
.card_grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    padding: 10px;
}

.application_card {
    position: relative;
    background: #ffffff; 
    padding: 20px; 
    border-radius: 10px; 
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: column; 
    gap: 10px; 
}

.application_card .status_icon {
    position: absolute;
    top: 15px; 
    right: 15px; 
    width: 30px; 
    height: 30px;
}

.application_card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.application_card strong {
    font-size: 18px;
    color: #333;
    margin-bottom: 5px;
}

.application_card p {
    margin: 0;
    font-size: 14px;
    color: #555; 
}

.application_card .date {
    font-size: 13px; 
    color: #777; 
    margin-top: auto;
}

.application_card .btn {
    align-self: flex-start;
    margin-top: 10px; 

}
.status_accepted {
    color: #28a745;
    font-weight: bold;
}

.status_pending {
    color: #ffc107;
    font-weight: bold;
}

.status_rejected {
    color: #dc3545;
    font-weight: bold;
}


.btn {
    text-decoration: none;
    padding: 8px 12px;
    background: #007bff;
    color: white;
    border-radius: 5px;
    text-align: center;
    font-size: 14px;
    display: inline-block;
    transition: background 0.3s ease;
    margin-top: 10px;
}

.btn:hover {
    background: #0056b3;
}

.no_applications {
    text-align: center;
    color: #555;
    font-style: italic;
    font-size: 16px; 
    margin-top: 20px;
}

.no_applications a {
    color: #007bff; 
    text-decoration: none;
    font-weight: bold;
    transition: color 0.3s ease;
}

.no_applications a:hover {
    color: #0056b3; 
    text-decoration: underline;
}


.filter_container {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
}

.filter_container label {
    margin-right: 10px;
    font-weight: bold;
    font-size: 14px;
    color: #333;
}

.filter_container select {
    padding: 5px 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 5px;
    transition: border-color 0.3s ease;
}

.filter_container select:focus {
    border-color: #007bff;
    outline: none;
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
    <ul class="nav_links">
        <li><a href="../employer/employee_sign_in.php">Post a Job</a></li>
        <?php if (!empty($username)): ?>
            <li><a href="application.php">Application</a></li>
        <?php endif; ?>
        <?php if (!empty($username)): ?>
            <li class="profile_dropdown">
            <a><?php echo htmlspecialchars($email); ?> <span style="font-size: 1em;">&#9660;</span></a>
                <ul class="dropdown_menu">
                    <li><a href="jobseeker_changepass.php" class="sign_out_button">Change Password</a></li>
                    <li><a href="../../logout.php" class="sign_out_button">Sign Out</a></li>
                </ul>
            </li>
        <?php else: ?>
            <li><a href="sign_in.php">Sign In</a></li>
        <?php endif; ?>
    </ul>
</nav>
    <div class="container">
        <h1>Your Job Applications</h1>
        <div class="filter_container">
            <form method="GET" action="application.php" onsubmit="return false;">
                <label for="status_filter">Filter by Status:</label>
                <select id="status_filter" name="status">
                    <option value="">All</option>
                    <option value="accepted" <?php echo $statusFilter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                    <option value="reviewed" <?php echo $statusFilter === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </form>
        </div>
        <div class="card_grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="application_card">
                        <img src="<?php echo getStatusIcon($row['status'], '../../static/img/icon'); ?>" 
                            alt="<?php echo htmlspecialchars($row['status']); ?>" 
                            class="status_icon" 
                            title="Status: <?php echo ucfirst(htmlspecialchars($row['status'])); ?>">
                        <strong>
                            <span class="job_title">
                                <?php echo htmlspecialchars($row['job_title']); ?>
                            </span>
                        </strong>
                        <p>Status: 
                            <span class="<?php echo getStatusClass($row['status']); ?>">     
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </p>
                        <span class="date" data-applied-date="<?php echo htmlspecialchars(date('c', strtotime($row['applied_at']))); ?>">
                            Applied on: <?php echo htmlspecialchars(date('F j, Y', strtotime($row['applied_at']))); ?>
                        </span>
                        <?php if (!empty($row['resume_link'])): ?>
                            <a href="<?php echo htmlspecialchars($row['resume_link']); ?>" class="btn" target="_blank">View Resume</a>
                        <?php else: ?>
                            <p style="color: #888; font-size: 12px;">No resume uploaded.</p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no_applications">
                    No applications found. 
                    <a href="../../index.php">Browse Jobs</a>   
                </p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        
        document.addEventListener('DOMContentLoaded', () => {
            const statusFilter = document.getElementById('status_filter');
            const cardGrid = document.querySelector('.card_grid');

            statusFilter.addEventListener('change', () => {
                const selectedStatus = statusFilter.value;
                const xhr = new XMLHttpRequest();
                xhr.open('GET', `application.php?status=${encodeURIComponent(selectedStatus)}`, true);
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(xhr.responseText, 'text/html');
                        const newCardGrid = doc.querySelector('.card_grid');
                        cardGrid.innerHTML = newCardGrid.innerHTML;
                    }
                };
                xhr.send();
            });

            
            const profileDropdown = document.querySelector('.profile_dropdown');
            if (profileDropdown) {
                profileDropdown.addEventListener('click', (e) => {
                    e.preventDefault();
                    profileDropdown.classList.toggle('active');
                });

                document.addEventListener('click', (e) => {
                    if (!profileDropdown.contains(e.target)) {
                        profileDropdown.classList.remove('active');
                    }
                });

                
                const dropdownMenu = document.querySelector('.profile_dropdown .dropdown_menu');
                if (dropdownMenu) {
                    dropdownMenu.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }
            }
        });
    </script>
</body>
</html>