<?php
session_start();

include '../../db_connection/connection.php';
$conn = OpenConnection();

if (!isset($_SESSION['admin_id'])) {
    echo "Access denied. Admins only.";
    exit();
}

if (!isset($_GET['company_id'])) {
    echo "Invalid company ID.";
    exit();
}

$company_id = intval($_GET['company_id']);

$company_query = "SELECT company_name, company_image, company_description, company_cover, company_tagline, user_id
                  FROM users
                  WHERE user_id = ?";
$company_stmt = $conn->prepare($company_query);
$company_stmt->bind_param("i", $company_id);
$company_stmt->execute();
$company_result = $company_stmt->get_result();
$company = $company_result->fetch_assoc();
$company_stmt->close();

if (!$company) {
    echo "Company not found.";
    exit();
}

$company_image = file_exists($company['company_image']) ? $company['company_image'] : '../../static/img/company_img/default.jpg';
$company_cover = (!empty($company['company_cover']) && file_exists($company['company_cover'])) ? $company['company_cover'] : '../../static/img/company_img/default_cover.jpg';

$jobs_query = "SELECT job_id, title, description, category, salary, location, created_at, status
               FROM jobs
               WHERE employer_id = ?
               ORDER BY created_at DESC";
$jobs_stmt = $conn->prepare($jobs_query);
$jobs_stmt->bind_param("i", $company_id);
$jobs_stmt->execute();
$jobs_result = $jobs_stmt->get_result();
$jobs = [];
while ($row = $jobs_result->fetch_assoc()) {
    if (strtolower($row['status']) === 'approved') {
        $jobs[] = $row;
    }
}
$jobs_stmt->close();

CloseConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Company Profile (Admin)</title>
    <link rel="shortcut icon" href="../../static/img/icon/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../../static/css/admin_dashboard.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f4f7f9 60%, #e3eafc 100%);
            min-height: 100vh;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background: #22223b;
            color: #fff;
            padding: 0 30px;
            display: flex;
            align-items: center;
            height: 70px; 
            min-height: 70px;
            justify-content: space-between;
        }
        .navbar .logo {
            font-size: 1.5em;
            font-weight: bold;
            color: #f2e9e4;
            text-decoration: none;
        }
        .nav-links {
            list-style: none;
            display: flex;
            gap: 25px;
            margin: 0;
            padding: 0;
        }
        .nav-links li a {
            color: #f2e9e4;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .nav-links li a:hover {
            color: #c9ada7;
        }
        .container {
            max-width: 1400px;
            margin: 40px auto 30px auto;
            padding: 40px 32px 32px 32px;
            background: linear-gradient(120deg, #fff 80%, #f4f7f9 100%);
            border-radius: 18px;
            border: 1.5px solid #e3eafc;
            box-shadow: 0 12px 36px rgba(34,34,59,0.12), 0 1.5px 6px rgba(34,34,59,0.04);
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            height: calc(100vh - 40px);
            transition: box-shadow 0.2s, border 0.2s;
        }
        .container:hover {
            box-shadow: 0 18px 48px rgba(34,34,59,0.17), 0 2px 8px rgba(34,34,59,0.07);
            border: 1.5px solid #c9ada7;
        }
        .profile_card_cover_section {
            width: 100%;
            height: 180px;
            overflow: hidden;
            border-radius: 12px 12px 0 0;
            background: #e3eafc;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .profile_card_cover {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .profile_card_inner_layout {
            display: flex;
            gap: 32px;
            margin-top: -60px;
            align-items: flex-start;
        }
        .profile_card_image_section {
            flex: 0 0 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 2;
        }
        .profile_card_logo {
            width: 120px;
            height: 120px;
            border-radius: 16px;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(34,34,59,0.07);
            background: #eee;
        }
        .profile_card_acronym {
            margin-top: 10px;
            font-size: 1.1em;
            color: #4a4e69;
            font-weight: 600;
            letter-spacing: 2px;
        }
        .profile_card_content_section {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .profile_card_name {
            font-size: 2em;
            font-weight: 800;
            color: #22223b;
            margin-bottom: 6px;
            background: rgba(255,255,255,0.75);
            padding: 10px 28px;
            border-radius: 14px;
            display: inline-block;
            box-shadow: 0 4px 24px 0 rgba(34,34,59,0.10), 0 1.5px 6px rgba(34,34,59,0.04);
            backdrop-filter: blur(2.5px);
            border: 1.5px solid #e3eafc;
            letter-spacing: 1.2px;
            transition: box-shadow 0.2s, border 0.2s;
        }
        .profile_card_name:hover {
            box-shadow: 0 8px 32px 0 rgba(34,34,59,0.18), 0 2px 8px rgba(34,34,59,0.07);
            border: 1.5px solid #c9ada7;
        }
        .profile_card_tagline {
            font-size: 1.15em;
            color: #4a4e69;
            margin-bottom: 4px;
            background: rgba(255,255,255,0.65);
            padding: 7px 20px;
            border-radius: 10px;
            display: inline-block;
            box-shadow: 0 2px 12px 0 rgba(34,34,59,0.08);
            backdrop-filter: blur(1.5px);
            border: 1px solid #e3eafc;
            font-style: italic;
            letter-spacing: 0.5px;
        }
        .profile_card_divider {
            height: 1px;
            background: #e0e0e0;
            margin: 10px 0 10px 0;
        }
        .company_tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .company_tab_btn {
            padding: 8px 18px;
            background: #f2e9e4;
            color: #22223b;
            border: 1px solid #c9ada7;
            border-radius: 6px 6px 0 0;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            outline: none;
            transition: background 0.2s, color 0.2s;
        }
        .company_tab_btn.active, .company_tab_btn:hover {
            background: #c9ada7;
            color: #fff;
        }
        .company_tab_content {
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 2px 8px rgba(34,34,59,0.07);
            padding: 18px 16px;
            min-height: 120px;
        }
        .overview_desc {
            font-size: 1.05em;
            color: #444;
        }
        .no_jobs_message {
            text-align: center;
            color: #888;
            font-style: italic;
            margin: 20px 0;
            font-size: 1.05em;
        }
        .company_jobs_list {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .company_jobs_list li {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(34,34,59,0.10);
            padding: 28px 20px 22px 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 18px;
            border-left: 5px solid #c9ada7;
            transition: box-shadow 0.2s, border-color 0.2s, transform 0.15s;
            min-height: 140px;
            position: relative;
        }
        .company_jobs_list li:hover {
            box-shadow: 0 8px 28px rgba(34,34,59,0.18);
            border-left: 5px solid #4a4e69;
            transform: translateY(-4px) scale(1.025);
        }
        .company_job_title {
            font-size: 1.18em;
            font-weight: 700;
            color: #22223b;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }
        .company_job_meta {
            display: flex;
            gap: 14px;
            align-items: center;
            font-size: 1em;
            color: #4a4e69;
            font-weight: 500;
        }
        .company_job_meta span {
            background: #f2e9e4;
            color: #4a4e69;
            border-radius: 6px;
            padding: 4px 12px;
            font-size: 0.97em;
            font-weight: 500;
            box-shadow: 0 1px 2px rgba(34,34,59,0.04);
        }
        .company-info-bar {
            
            width: 100%;
            z-index: 101;
            background: #e3eafc; 
            color: #22223b;
            padding: 22px 32px 14px 32px;
            border-radius: 18px 18px 0 0;
            box-shadow: 0 2px 12px rgba(34,34,59,0.10);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 18px;
        }
        .company-info-bar .company-name {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 4px;
            color: #22223b;
            line-height: 1.1;
        }
        .company-info-bar .company-tagline {
            font-size: 1.13rem;
            font-weight: 400;
            color: #4a4e69;
            margin-bottom: 0;
        }
        @media (max-width: 700px) {
            .company-info-bar {
                padding: 12px 10px 10px 10px;
            }
            .company-info-bar .company-name {
                font-size: 1.2rem;
            }
            .company-info-bar .company-tagline {
                font-size: 1rem;
            }
        }
        @media (max-width: 900px) {
            .company_jobs_list {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 600px) {
            .company_jobs_list {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 700px) {
            .company_jobs_list li { padding: 14px 8px; }
            .company_job_meta { flex-direction: column; gap: 7px; align-items: flex-start; }
        }
        @media (max-width: 900px) {
            .container { padding: 10px; }
            .profile_card_inner_layout { flex-direction: column; gap: 18px; margin-top: -40px; }
            .profile_card_image_section { align-items: flex-start; }
        }
        .footer {
            width: 100%;
            background: #22223b;
            color: #f2e9e4;
            text-align: center;
            padding: 16px 0 12px 0;
            font-size: 1em;
            position: fixed;
            left: 0;
            bottom: 0;
            z-index: 100;
        }
        @media (min-width: 0px) {
            body {
                padding-bottom: 60px;
            }
        }
        .joblisting_section {
            height: 100%;
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        .joblisting_scrollable {
            flex: 1 1 auto;
            min-height: 0;
            max-height: 100%;
            overflow-y: auto;
            padding-right: 4px;
            
            scrollbar-width: none; 
            -ms-overflow-style: none;  
        }
        .joblisting_scrollable::-webkit-scrollbar {
            width: 0;
            height: 0;
            display: none;
            background: transparent;
            -ms-overflow-style: none;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="admin_dashboard.php" class="logo">Admin Portal</a>
        <ul class="nav-links">
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="../../logout.php">Logout</a></li>
        </ul>
    </nav>
    <div class="container">
        <?php if (!empty($company_cover) && file_exists($company_cover)): ?>
            <div class="profile_card_cover_section">
                <img src="<?php echo htmlspecialchars($company_cover); ?>" alt="Company Cover" class="profile_card_cover">
            </div>
        <?php else: ?>
            <div class="profile_card_cover_section" style="background: #e3eafc;">
            </div>
        <?php endif; ?>
        <?php
        
        $company_name = $company['company_name'] ?? '';
        $words = preg_split('/\s+/', trim($company_name));
        $acronym = '';
        foreach ($words as $w) {
            if ($w !== '' && isset($w[0]) && ctype_alpha($w[0])) {
                $acronym .= strtoupper($w[0]) . '.';
            }
        }
        ?>
        <div class="profile_card_inner_layout">
            <div class="profile_card_image_section">
                <?php if (!empty($company_image) && file_exists($company_image)): ?>
                    <img src="<?php echo htmlspecialchars($company_image); ?>" alt="Company Logo" class="profile_card_logo">
                <?php else: ?>
                    <div class="profile_card_logo" style="background:#eee;display:flex;align-items:center;justify-content:center;font-size:2.5em;color:#bbb;">
                        <span>
                        <?php echo htmlspecialchars($acronym); ?>
                        </span>
                    </div>
                <?php endif; ?>
                <div class="profile_card_acronym"><?php echo htmlspecialchars($acronym); ?></div>
            </div>
            <div class="profile_card_content_section">
                <div class="profile_card_name"><?php echo htmlspecialchars($company['company_name']); ?></div>
                <?php if (!empty($company['company_tagline'])): ?>
                    <div class="profile_card_tagline"><?php echo htmlspecialchars($company['company_tagline']); ?></div>
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
                            <?php
                            if (!empty($company['company_description'])) {
                                echo nl2br(htmlspecialchars(html_entity_decode($company['company_description'])));
                            } else {
                                echo '<em>No company description available.</em>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div id="tabJobs" class="company_tab_content" style="display:none;">
                    <div class="joblisting_section">
                        <div class="joblisting_scrollable">
                            <?php if (count($jobs) === 0): ?>
                                <div class="no_jobs_message">No jobs posted by this company yet.</div>
                            <?php else: ?>
                                <ul class="company_jobs_list">
                                    <?php foreach ($jobs as $job): ?>
                                        <li>
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
    </div>
    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> JobPortal. All rights reserved.</p>
    </footer>
    <script>
    function showTab(tab) {
        document.getElementById('tabOverview').style.display = (tab === 'overview') ? '' : 'none';
        document.getElementById('tabJobs').style.display = (tab === 'jobs') ? '' : 'none';
        document.getElementById('tabOverviewBtn').classList.toggle('active', tab === 'overview');
        document.getElementById('tabJobsBtn').classList.toggle('active', tab === 'jobs');
    }
    </script>
</body>
</html>
</html>
