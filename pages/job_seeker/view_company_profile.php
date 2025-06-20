<?php
session_start();

include '../../db_connection/connection.php';
$conn = OpenConnection();

$username = '';
$email = '';

if (isset($_SESSION['user_id'])) {
    
    $user_id = intval($_SESSION['user_id']);
    $user_query = "SELECT username, email FROM users WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    if ($user_row = $user_result->fetch_assoc()) {
        $username = $user_row['username'];
        $email = $user_row['email'];
    }
    $user_stmt->close();

} elseif (isset($_SESSION['admin_id'])) {
    $username = 'Admin';
    $email = '';
}

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    echo "Access denied. Please log in.";
    exit();
}


if (!isset($_GET['company_id'])) {
    echo "Invalid company ID.";
    exit();
}

$company_id = intval($_GET['company_id']);

$query = "SELECT company_name, company_image, company_description, company_cover, company_tagline FROM `users` WHERE `user_id` = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$companyDetails = $result->fetch_assoc();

if (!$companyDetails) {
    echo "Company not found.";
    exit();
}

$company_image = file_exists($companyDetails['company_image']) ? $companyDetails['company_image'] : '../../static/img/company_img/default.jpg';

$company_cover = !empty($companyDetails['company_cover']) && file_exists($companyDetails['company_cover'])
    ? $companyDetails['company_cover']
    : '../../static/img/company_img/default_cover.jpg';

$jobs = [];
$job_query = "SELECT `job_id`, `title`, `description`, `category`, `salary`, `location`, `created_at` 
            FROM `jobs` 
            WHERE `company_name` = ? AND `employer_id` = ? 
            ORDER BY `created_at` DESC";
$job_stmt = $conn->prepare($job_query);
$job_stmt->bind_param("si", $companyDetails['company_name'], $company_id);
$job_stmt->execute();
$job_result = $job_stmt->get_result();
while ($row = $job_result->fetch_assoc()) {
    $jobs[] = $row;
}
$job_stmt->close();
CloseConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../../static/img//icon/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../../static/css/view_company_profile.css">
    <title>View Company Profile</title>
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
                    <li><a href="jobseeker_changepass.php" class="sign-out-button">Change Password</a></li>
                    <li><a href="../../logout.php" class="sign_out_button">Sign Out</a></li>
                </ul>
            </li>
        <?php else: ?>
            <li><a href="sign_in.php">Sign In</a></li>
        <?php endif; ?>
    </ul>
</nav>
<div class="profile_card_modern">
    <div class="profile_card_inner_layout">
        <!-- Add cover as background for the image section -->
        <div class="profile_card_image_section" style="position:relative; background: url('<?php echo htmlspecialchars($company_cover); ?>') center center/cover no-repeat; border-radius: 12px 0 0 12px;">
            <img src="<?php echo htmlspecialchars($company_image); ?>" alt="Company Logo" class="profile_card_logo" style="position:relative; z-index:2;">
            <?php
            $company_name = $companyDetails['company_name'] ?? '';
            $words = preg_split('/\s+/', trim($company_name));
            $acronym = '';
            foreach ($words as $w) {
                if ($w !== '' && isset($w[0]) && ctype_alpha($w[0])) {
                    $acronym .= strtoupper($w[0]) . '.';
                }
            }
            ?>
            <div class="profile_card_acronym"><?php echo htmlspecialchars($acronym); ?></div>
            <!-- Optionally, add a semi-transparent overlay for better contrast -->
            <div style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.2);z-index:1;border-radius:12px 0 0 12px;"></div>
        </div>
        <div class="profile_card_content_section">
            <div class="profile_card_name"><?php echo htmlspecialchars($companyDetails['company_name']); ?></div>
            <?php if (!empty($companyDetails['company_tagline'])): ?>
                <div class="profile_card_tagline"><?php echo htmlspecialchars($companyDetails['company_tagline']); ?></div>
            <?php endif; ?>
            <div class="profile_card_divider"></div>
            <div class="company_tabs">
                <button class="company_tab_btn active" id="tabOverviewBtn" onclick="showTab('overview')">Overview</button>
                <button class="company_tab_btn" id="tabJobsBtn" onclick="showTab('jobs')">
                    Job Listing (<?php echo count($jobs); ?>)
                </button>
            </div>
            <div id="tabOverview" class="company_tab_content">
                <div class="overview_section">
                    <div class="overview_desc">
                        <?php echo nl2br(htmlspecialchars(html_entity_decode(string: $companyDetails['company_description']))); ?>
                    </div>
                </div>
            </div>
            <div id="tabJobs" class="company_tab_content" style="display:none;">
                <div class="joblisting_section">
                    <?php if (count($jobs) === 0): ?>
                        <div class="no_jobs_message">No jobs posted by this company yet.</div>
                    <?php else: ?>
                        <ul class="company_jobs_list">
                            <?php foreach ($jobs as $job): ?>
                                <li onclick="window.location.href='../../index.php?job_id=<?php echo (int)$job['job_id']; ?>';" style="cursor:pointer;">
                                    <div class="company_job_title"><?php echo htmlspecialchars($job['title']); ?></div>
                                    <div class="company_job_meta">
                                        <span><?php echo htmlspecialchars($job['category']); ?></span>
                                        <span><?php echo htmlspecialchars($job['location']); ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
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

    function showTab(tab) {
        document.getElementById('tabOverview').style.display = (tab === 'overview') ? '' : 'none';
        document.getElementById('tabJobs').style.display = (tab === 'jobs') ? '' : 'none';
        document.getElementById('tabOverviewBtn').classList.toggle('active', tab === 'overview');
        document.getElementById('tabJobsBtn').classList.toggle('active', tab === 'jobs');
    }
</script>
</body>
</html>
