<?php
session_start();


if (!isset($_SESSION['username'])) {
    header("Location: employee_sign_in.php");
    exit();
}

$username = htmlspecialchars($_SESSION['username']);

include '../../db_connection/connection.php';

$has_company = false;
$conn = OpenConnection(); 
$stmt = $conn->prepare("SELECT company_name FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($company_name);
if ($stmt->fetch() && !empty($company_name)) {
    $has_company = true;
}
$stmt->close();

if (!$has_company) {
    header("Location: company_profile.php?assign_company=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../../static/img/icon/favicon.png" type="image/x-icon">
    <title>Employer Dashboard</title>

    <script src="../../static/js/get_pending_count.js" defer></script>
    <style>
        
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background-color: #f8f9fa;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    margin: 0;
    padding: 0;
}

.container {
    width: 90%;
    max-width: 1200px;
    margin: auto;
    flex: 1;
}

h1 {
    text-align: center;
    color: #333;
    margin-bottom: 20px;
}

.header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.application-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 15px;
    padding: 0;
    list-style: none;
}

.application-card {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    gap: 10px;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.application-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.application-card.selected {
    border: 2px solid #007bff;
}

.application-card strong {
    font-size: 16px;
    color: #333;
}

.application-card p {
    margin: 0;
    font-size: 14px;
    color: #666;
}

.application-card .date {
    font-size: 12px;
    color: #999;
}

.btn {
    text-decoration: none;
    padding: 8px 12px;
    background: #144272;
    color: white;
    border-radius: 5px;
    text-align: center;
    font-size: 14px;
    display: inline-block;
    transition: background 0.3s ease;
}

.btn:hover {
    background: #0056b3;
}

.no-applications {
    text-align: center;
    color: #888;
    font-style: italic;
}

.tabs {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

.tab {
    position: relative;
    padding: 10px 20px;
    cursor: pointer;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 5px 5px 0 0;
    margin-right: 5px;
    transition: background 0.3s ease;
}

.tab.active {
    background: #007bff;
    color: white;
    border-bottom: none;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}


.navbar {
    background: #0A2647;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.logo {
    font-size: 24px;
    font-weight: bold;
    color: white;
    text-decoration: none;
    transition: color 0.3s ease;
}

.logo:hover {
    color: #00c6ff;
}

.nav-links {
    list-style: none;
    display: flex;
    gap: 15px;
    align-items: center;
}

.nav-links a {
    color: white;
    text-decoration: none;
    font-size: 16px;
    padding: 8px 15px;
    border-radius: 5px;
    transition: background 0.3s ease;
    font-weight: bold;
    display: inline-flex;
    align-items: center;
    position: relative;
}


.navbar {
    background: #0A2647;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.logo {
    font-size: 24px;
    font-weight: bold;
    color: white;
    text-decoration: none;
    transition: color 0.3s ease;
}

.logo:hover {
    color: #00c6ff;
}

.nav-links {
    list-style: none;
    display: flex;
    gap: 15px;
    align-items: center;
}

.nav-links a {
    color: white;
    text-decoration: none;
    font-size: 16px;
    padding: 8px 15px;
    border-radius: 5px;
    transition: background 0.3s ease;
    font-weight: bold;
    display: inline-flex;
    align-items: center;
    position: relative;
}

        .applications-container {
            position: relative; 
        }

        .nav-badge {
            position: absolute;
            top: -5px; 
            right: -5px; 
            background: #dc3545;
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 50%;
            display: inline-block;
        }

        footer {
            text-align: center;
            padding: 10px 0;
            background: #333;
            color: white;
            margin-top: auto;
        }

        footer p {
            margin: 0;
        }

        .dashboard-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
        }

        .dashboard-card {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            width: 300px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .dashboard-card h2 {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 10px;
        }

        .dashboard-card p {
            font-size: 1em;
            color: #555;
            margin-bottom: 20px;
        }

        .dashboard-card a {
            text-decoration: none;
            color: white;
            background: #144272;
            padding: 10px 20px;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .dashboard-card a:hover {
            background: #0056b3;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 900px) {
            .dashboard-links {
                flex-direction: column;
                align-items: center;
                gap: 24px;
            }
            .dashboard-card {
                width: 90%;
                max-width: 400px;
            }
        }
        @media (max-width: 600px) {
            .dashboard-links {
                gap: 16px;
                margin: 18px 0;
            }
            .dashboard-card {
                width: 98%;
                max-width: 98vw;
                padding: 14px 6px;
            }
            .dashboard-card h2 {
                font-size: 1.1em;
            }
            .dashboard-card p {
                font-size: 0.95em;
            }
        }

        .disabled-link {
            pointer-events: none;
            background: #ccc !important;
            color: #888 !important;
            cursor: not-allowed;
        }

        .warning-message {
            color: #dc3545;
            background: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }   

        /* Popup Notification */
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

    <nav class="navbar">
        <a href="Employee_dashboard.php" class="logo">Employee Portal</a>
        <ul class="nav-links">
            <li><a href="post_job.php">Post Job</a></li>
            <li>
                <div class="applications-container">
                    <a href="view_applications.php">Applications</a>
                    <span id="navbar-badge" class="nav-badge" style="display: inline-block;">0</span>
                </div>
            </li>
            <li><a href="view_jobs.php">View Jobs</a></li>
            <li><a href="company_profile.php">Company Profile</a></li>
            <li><a href="../../logout.php">Logout</a></li>
        </ul>
    </nav>
    <div class="container" style="overflow-y: auto; max-height: calc(100vh - 110px); scrollbar-width: none; -ms-overflow-style: none;">
        <style>
            .container::-webkit-scrollbar {
                display: none;
            }
        </style>
        <?php if (!$has_company): ?>
            <div class="warning-message">
                You must assign your company profile before posting a job.
            </div>
        <?php endif; ?>

        
        <div class="analytics-section" style="max-width:1400px;margin:48px auto 0 auto;background:#fff;padding:40px 32px 32px 32px;border-radius:16px;box-shadow:0 6px 18px rgba(20,66,114,0.10);">
            <h2 style="text-align:center;color:#144272;margin-bottom:32px;font-size:2em;letter-spacing:1px;">Job Application Analytics</h2>
            <form method="get" style="text-align:right;max-width:400px;margin:0 0 18px auto;">
                <?php
                    
                    $yearStmt = $conn->prepare("
                        SELECT DISTINCT YEAR(a.applied_at) as year
                        FROM applications a
                        JOIN jobs j ON a.job_id = j.job_id
                        WHERE j.company_name = ?
                        ORDER BY year DESC
                    ");
                    $yearStmt->bind_param("s", $company_name);
                    $yearStmt->execute();
                    $yearResult = $yearStmt->get_result();
                    $years = [];
                    while ($row = $yearResult->fetch_assoc()) {
                        if ($row['year']) $years[] = intval($row['year']);
                    }
                    $yearStmt->close();
                    if (empty($years)) $years[] = date('Y');
                    $selectedYear = isset($_GET['year']) ? $_GET['year'] : $years[0];
                ?>
                <label for="year" style="font-weight:600;color:#205295;">Year:</label>
                <select name="year" id="year" onchange="this.form.submit()" style="padding:4px 10px;border-radius:5px;border:1px solid #b7e4c7;font-size:1em;">
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php if ($selectedYear == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php
            
            $appStats = [];
            $statuses = ['accepted', 'rejected', 'pending'];
            foreach ($statuses as $status) {
                $appStats[$status] = [];
            }

            $months = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthKey = sprintf('%s-%02d', $selectedYear, $m);
                $months[] = $monthKey;
            }
            $stmt = $conn->prepare("
                SELECT DATE_FORMAT(a.applied_at, '%Y-%m') as month, a.status, COUNT(*) as count
                FROM applications a
                JOIN jobs j ON a.job_id = j.job_id
                WHERE j.company_name = ? AND YEAR(a.applied_at) = ?
                GROUP BY month, a.status
                ORDER BY month ASC
            ");
            $stmt->bind_param("si", $company_name, $selectedYear);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $month = $row['month'];
                $status = strtolower($row['status']);
                $count = $row['count'];
                if (in_array($status, $statuses)) {
                    $appStats[$status][$month] = $count;
                }
            }
            $stmt->close();

            $labels = [];
            $acceptedData = [];
            $rejectedData = [];
            $pendingData = [];
            foreach ($months as $month) {
                $labels[] = date('M Y', strtotime($month . '-01'));
                $acceptedData[] = isset($appStats['accepted'][$month]) ? $appStats['accepted'][$month] : 0;
                $rejectedData[] = isset($appStats['rejected'][$month]) ? $appStats['rejected'][$month] : 0;
                $pendingData[] = isset($appStats['pending'][$month]) ? $appStats['pending'][$month] : 0;
            }
            $maxValue = max(array_merge($acceptedData, $rejectedData, $pendingData, [1])); // avoid division by zero
            ?>
            <?php if (array_sum($acceptedData) + array_sum($rejectedData) + array_sum($pendingData) > 0): ?>
            <style>
                .bar-chart-modern {
                    width: 100%;
                    overflow-x: auto;
                    padding-bottom: 18px;
                }
                .bar-chart-modern-inner {
                    display: flex;
                    align-items: flex-end;
                    justify-content: flex-start;
                    gap: 32px;
                    min-width: 1100px;
                    height: 320px;
                    border-left: 2px solid #144272;
                    border-bottom: 2px solid #144272;
                    position: relative;
                    background: linear-gradient(180deg, #f8f9fa 80%, #e9f5ff 100%);
                }
                .bar-group-modern {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    width: 60px;
                    position: relative;
                }
                .bars-stack {
                    display: flex;
                    flex-direction: row;
                    align-items: flex-end;
                    gap: 8px;
                    height: 220px;
                    margin-bottom: 8px;
                }
                .bar-modern {
                    width: 18px;
                    border-radius: 8px 8px 0 0;
                    transition: height 0.4s;
                    box-shadow: 0 4px 16px rgba(20,66,114,0.10);
                    position: relative;
                    display: flex;
                    align-items: flex-end;
                    justify-content: center;
                }
                .bar-accepted {
                    background: linear-gradient(180deg, #205295 0%, #43e97b 100%);
                    border: 2px solid #144272;
                }
                .bar-rejected {
                    background: linear-gradient(180deg, #dc3545 0%, #ffb3b3 100%);
                    border: 2px solid #dc3545;
                }
                .bar-pending {
                    background: linear-gradient(180deg, #ffc107 0%, #ffe066 100%);
                    border: 2px solid #ffc107;
                }
                .bar-modern-value {
                    position: absolute;
                    top: -28px;
                    left: 50%;
                    transform: translateX(-50%);
                    font-size: 1em;
                    color: #144272;
                    font-weight: 700;
                    background: #fff;
                    padding: 3px 8px;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(20,66,114,0.08);
                    min-width: 18px;
                    text-align: center;
                    z-index: 2;
                    border: 1px solid #b7e4c7;
                }
                .bar-label-x-modern {
                    margin-top: 12px;
                    font-size: 1em;
                    color: #205295;
                    text-align: center;
                    width: 100%;
                    word-break: break-word;
                    font-weight: 600;
                    letter-spacing: 0.5px;
                }
                .bar-legend-modern {
                    display: flex;
                    gap: 36px;
                    margin: 24px 0 18px 0;
                    justify-content: center;
                }
                .bar-legend-modern span {
                    display: inline-flex;
                    align-items: center;
                    gap: 10px;
                    font-size: 1.08em;
                    color: #144272;
                    font-weight: 500;
                }
                .bar-legend-modern .bar {
                    width: 28px;
                    height: 16px;
                    border-radius: 6px;
                    border: 2px solid #144272;
                }
                .bar-legend-modern .bar-accepted { border-color: #205295; }
                .bar-legend-modern .bar-rejected { border-color: #dc3545; }
                .bar-legend-modern .bar-pending { border-color: #ffc107; }
                .bar-chart-modern .y-axis-labels {
                    position: absolute;
                    left: -48px;
                    bottom: 0;
                    display: flex;
                    flex-direction: column;
                    justify-content: flex-end;
                    height: 100%;
                    width: 44px;
                    font-size: 1em;
                    color: #205295;
                    z-index: 1;
                }
                .bar-chart-modern .y-axis-label {
                    flex: 1;
                    text-align: right;
                    padding-right: 10px;
                    border: none;
                    font-weight: 500;
                }
                .bar-chart-modern .y-axis-label:not(:last-child) {
                    border-bottom: 1px dashed #b7e4c7;
                }
                @media (max-width: 1100px) {
                    .bar-chart-modern-inner { min-width: 900px; gap: 18px; }
                    .bar-group-modern { width: 38px; }
                    .bar-label-x-modern { font-size: 0.95em; }
                }
                @media (max-width: 900px) {
                    .bar-chart-modern-inner { min-width: 700px; gap: 10px; }
                    .bar-group-modern { width: 28px; }
                    .bar-label-x-modern { font-size: 0.85em; }
                }
                @media (max-width: 700px) {
                    .bar-chart-modern-inner { min-width: 500px; gap: 6px; }
                    .bar-group-modern { width: 22px; }
                    .bar-label-x-modern { font-size: 0.75em; }
                }
            </style>
            <div class="bar-legend-modern">
                <span><span class="bar bar-accepted"></span>Accepted</span>
                <span><span class="bar bar-rejected"></span>Rejected</span>
                <span><span class="bar bar-pending"></span>Pending</span>
            </div>
            <div class="bar-chart-modern" style="position:relative;">
                <div class="bar-chart-modern-inner">
                    
                    <div class="y-axis-labels">
                        <?php
                        $ticks = 5;
                        for ($i = $ticks; $i >= 0; $i--) {
                            $val = round($maxValue * $i / $ticks);
                            echo '<div class="y-axis-label" style="height:' . (100/$ticks) . '%;">' . $val . '</div>';
                        }
                        ?>
                    </div>
                    <?php foreach ($labels as $i => $label): ?>
                    <div class="bar-group-modern">
                        <div class="bars-stack">
                            <div class="bar-modern bar-accepted" style="height:<?php echo ($acceptedData[$i]/$maxValue*200); ?>px">
                                <?php if ($acceptedData[$i] > 0): ?>
                                    <span class="bar-modern-value"><?php echo $acceptedData[$i]; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="bar-modern bar-rejected" style="height:<?php echo ($rejectedData[$i]/$maxValue*150); ?>px">
                                <?php if ($rejectedData[$i] > 0): ?>
                                    <span class="bar-modern-value"><?php echo $rejectedData[$i]; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="bar-modern bar-pending" style="height:<?php echo ($pendingData[$i]/$maxValue*100); ?>px">
                                <?php if ($pendingData[$i] > 0): ?>
                                    <span class="bar-modern-value"><?php echo $pendingData[$i]; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="bar-label-x-modern"><?php echo htmlspecialchars($label); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="margin-top:32px;text-align:center;">
                <p style="color:#205295;font-size:1.1em;">
                    Visualize your application status trends
                    <?php
                        
                        echo "for $selectedYear";
                    ?>.
                </p>
            </div>
            <?php else: ?>
                <p style="text-align:center;color:#888;">No analytics data available yet.</p>
            <?php endif; ?>
        </div>
        

        
        <div class="analytics-section" style="max-width:1400px;margin:40px auto 0 auto;background:#fff;padding:32px 32px 28px 32px;border-radius:16px;box-shadow:0 6px 18px rgba(0,0,0,0.10);overflow:hidden;">
            <h2 style="text-align:center;color:#144272;margin-bottom:18px;font-size:1.3em;">Approved Job Listings</h2>
            <?php
            $stmt = $conn->prepare("SELECT job_id, title FROM jobs WHERE company_name = ? AND status = 'approved' ORDER BY created_at DESC");
            $stmt->bind_param("s", $company_name);
            $stmt->execute();
            $result = $stmt->get_result();
            $approvedJobs = [];
            while ($row = $result->fetch_assoc()) {
                $approvedJobs[] = $row;
            }
            $hasApproved = count($approvedJobs) > 0;
            ?>
            <style>
                .approved-jobs-cards-alt {
                    display: grid;
                    grid-template-columns: repeat(5, minmax(180px, 1fr));
                    gap: 18px;
                    margin-top: 18px;
                    justify-content: flex-start;
                }
                .approved-jobs-cards-alt.center {
                    justify-content: center;
                }
                .approved-job-card-alt {
                    background: linear-gradient(135deg, #e9f5ff 60%, #f8fff8 100%);
                    border-radius: 18px;
                    box-shadow: 0 4px 16px rgba(20,66,114,0.10);
                    padding: 18px 22px;
                    display: flex;
                    align-items: center;
                    min-width: 0;
                    max-width: 100%;
                    border: 1.5px solid #b7e4c7;
                    transition: box-shadow 0.2s, transform 0.2s, border 0.2s;
                    margin-bottom: 8px;
                    position: relative;
                    font-size: 1.07em;
                    font-weight: 600;
                    color: #205295;
                    letter-spacing: 0.2px;
                }
                .approved-job-card-alt:hover {
                    box-shadow: 0 8px 28px rgba(20,66,114,0.16);
                    border: 1.5px solid #28a745;
                    transform: translateY(-4px) scale(1.04);
                    background: linear-gradient(135deg, #e0ffe0 60%, #f8fff8 100%);
                }
                .approved-job-icon-alt {
                    margin-right: 10px;
                    font-size: 1.3em;
                    color: #28a745;
                    opacity: 0.7;
                    flex-shrink: 0;
                }
                @media (max-width: 1200px) {
                    .analytics-section { max-width: 98vw; }
                    .approved-jobs-cards-alt { grid-template-columns: repeat(3, minmax(180px, 1fr)); }
                }
                @media (max-width: 900px) {
                    .analytics-section { max-width: 99vw; }
                    .approved-jobs-cards-alt { grid-template-columns: repeat(2, minmax(180px, 1fr)); }
                }
                @media (max-width: 700px) {
                    .approved-jobs-cards-alt { grid-template-columns: 1fr; gap: 10px; }
                    .approved-job-card-alt { min-width: 95vw; max-width: 98vw; }
                }
            </style>
            <?php if ($hasApproved): ?>
            <div class="approved-jobs-cards-alt<?php echo (count($approvedJobs) === 1) ? ' center' : ''; ?>">
            <?php foreach ($approvedJobs as $row): ?>
                <div class="approved-job-card-alt">
                    <span class="approved-job-icon-alt">&#10004;</span>
                    <span><?php echo htmlspecialchars($row['title']); ?></span>
                </div>
            <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p style="text-align:center;color:#888;">No approved job listings yet.</p>
            <?php endif; ?>
        </div>
        

    </div>
            <div class="approved-jobs-cards">
            <?php while ($row = $result->fetch_assoc()): $hasApproved = true; ?>
                <div class="approved-job-card">
                    <span class="approved-job-icon">&#10003;</span>
                    <div class="approved-job-title"><?php echo htmlspecialchars($row['title']); ?></div>
                    <div class="approved-job-meta">
                        <strong>Category:</strong> <?php echo htmlspecialchars($row['category']); ?>
                    </div>
                    <div class="approved-job-meta">
                        <strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?>
                    </div>
                    <div class="approved-job-meta">
                        <strong>Salary:</strong> ₱<?php echo number_format($row['salary'], 2); ?>
                    </div>
                    <div class="approved-job-date">
                        <?php echo htmlspecialchars(date('M d, Y', strtotime($row['created_at']))); ?>
                    </div>
                    <div class="approved-job-status">
                        <?php echo ucfirst($row['status']); ?>
                    </div>
                </div>
            <?php endwhile; ?>
            </div>
            <?php if (!$hasApproved): ?>
                <p style="text-align:center;color:#888;">No approved job listings yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> JobPortal. All rights reserved.</p>
    </footer>
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
            if (params.get('login') === '1') {
                showPopup('Login successful!', 'success');
            }
            if (params.get('success') === '1') {
                showPopup('Job posted successfully, Wait for Admin to Approve!', 'success');
            }
        })();
    </script>
</body>
</html>
