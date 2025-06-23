<?php
session_start();

if (!isset($_SESSION['admin_id'], $_SESSION['admin_username'])) {
    header("Location: ../../admin_sign_in.php");
    exit();
}

$admin_username = htmlspecialchars($_SESSION['admin_username']);

include '../../db_connection/connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="shortcut icon" href="../../static/img/icon/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="../../static/css/admin_dashboard.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f4f7f9 60%, #e3eafc 100%);
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
            max-width: 1100px;
            margin: 40px auto 30px auto;
            padding: 40px 32px 32px 32px;
            background: linear-gradient(120deg, #fff 80%, #f4f7f9 100%);
            border-radius: 18px;
            border: 1.5px solid #e3eafc;
            box-shadow: 0 12px 36px rgba(34,34,59,0.12), 0 1.5px 6px rgba(34,34,59,0.04);
            transition: box-shadow 0.2s, border 0.2s;
        }
        .container:hover {
            box-shadow: 0 18px 48px rgba(34,34,59,0.17), 0 2px 8px rgba(34,34,59,0.07);
            border: 1.5px solid #c9ada7;
        }
        h1 {
            text-align: center;
            color: #22223b;
            margin-bottom: 32px;
            font-size: 2.1em;
            letter-spacing: 1px;
        }
        .job-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            justify-content: center;
            padding: 0;
            margin: 0;
        }
        .job-list:empty {
            display: block;
        }
        .job-card {
            display: flex;
            align-items: center;
            gap: 18px;
            background: #fff;
            border-radius: 10px;
            border-left: 5px solid #22223b;
            box-shadow: none;
            padding: 14px 16px;
            margin-bottom: 0;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s, transform 0.12s;
            max-width: 370px;
            width: 100%;
            box-sizing: border-box;
            cursor: pointer;
            position: relative;
        }
        .job-card:hover {
            border-left-color: #c9ada7;
            background: #f4f7f9;
            box-shadow: 0 4px 18px rgba(34,34,59,0.10);
            transform: translateY(-2px) scale(1.012);
        }
        .company-logo {
            width: 48px;
            height: 48px;
            border-radius: 6px;
            object-fit: cover;
            background: #eee;
            border: 1px solid #e0e0e0;
            flex-shrink: 0;
        }
        .job-card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .job-title {
            font-size: 1.05em;
            font-weight: 600;
            color: #22223b;
            margin-bottom: 0;
        }
        .company-name {
            font-size: 0.98em;
            color: #4a4e69;
        }
        .job-meta {
            font-size: 0.95em;
            color: #666;
        }
        .job-desc {
            font-size: 0.95em;
            color: #444;
            margin-bottom: 2px;
        }
        .job-actions {
            margin-top: 7px;
            display: flex;
            gap: 8px;
        }
        .btn {
            text-decoration: none;
            padding: 6px 14px;
            background: #007bff;
            color: white;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            outline: none;
            transition: background 0.2s;
            cursor: pointer;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-approve {
            background: #28a745;
        }
        .btn-approve:hover {
            background: #218838;
        }
        .btn-reject {
            background: #dc3545;
        }
        .btn-reject:hover {
            background: #c82333;
        }
        .btn-company {
            background: #f2e9e4;
            color: #22223b;
            border: 1px solid #c9ada7;
        }
        .btn-company:hover {
            background: #c9ada7;
            color: #fff;
        }   
        .no-jobs {
            text-align: center;
            color: #888;
            font-style: italic;
            margin-top: 40px;
            font-size: 1.1em;
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
        
        .modal-backdrop {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0; right: 0; bottom: 0;
            background: rgba(34,34,59,0.32);
            justify-content: center;
            align-items: center;
        }
        .modal-backdrop.active {
            display: flex;
        }
        .modal-content {
            background: linear-gradient(120deg, #fff 80%, #f4f7f9 100%);
            border-radius: 32px;
            max-width: 1400px;
            width: 99vw;
            min-width: 320px;
            padding: 64px 80px 40px 80px;
            box-shadow: 0 32px 100px rgba(34,34,59,0.28), 0 2px 20px rgba(34,34,59,0.17);
            position: relative;
            animation: modalIn 0.18s;
            border: 2.5px solid #e3eafc;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .modal-content-inner {
            display: flex;
            flex-direction: row;
            width: 100%;
            gap: 80px;
            margin-bottom: 0;
        }
        .modal-left {
            flex: 1.1 1 0;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            min-width: 300px;
            max-width: 600px;
            background: transparent;
            border-radius: 0;
            box-shadow: none;
            padding: 0 0 0 0;
        }
        .modal-logo {
            width: 140px;
            height: 140px;
            border-radius: 18px;
            object-fit: cover;
            background: #eee;
            border: 2px solid #e0e0e0;
            margin-bottom: 32px;
            box-shadow: 0 2px 12px rgba(34,34,59,0.13);
            align-self: flex-start;
        }
        .modal-details-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 14px;
            margin-bottom: 0;
        }
        .modal-details-table td {
            padding: 12px 14px 12px 0;
            font-size: 1.18em;
        }
        .modal-details-table td.label {
            color: #4a4e69;
            font-weight: 600;
            width: 140px;
            text-align: right;
            background: none;
            vertical-align: top;
        }
        .modal-details-table td.value {
            color: #22223b;
            background: #f4f7f9;
            border-radius: 9px;
            min-width: 140px;
            word-break: break-word;
        }
        .modal-company .btn-company {
            font-size: 1em;
            padding: 5px 13px;
            border-radius: 5px;
            border: 1px solid #c9ada7;
            background: #f2e9e4;
            color: #22223b;
            transition: background 0.18s, color 0.18s;
        }
        .modal-company .btn-company:hover {
            background: #c9ada7;
            color: #fff;
        }
        .modal-right {
            flex: 1.5 1 0;
            display: flex;
            flex-direction: column;
            min-width: 320px;
            max-width: 900px;
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 2px 16px rgba(34,34,59,0.09);
            padding: 38px 32px 24px 32px;
            position: relative;
        }
        .modal-job-title {
            font-size: 1.5em;
            font-weight: 700;
            color: #22223b;
            margin-bottom: 22px;
            text-align: center;
            letter-spacing: 0.5px;
        }
        .modal-desc-title {
            font-size: 1.18em;
            font-weight: 600;
            color: #22223b;
            margin-bottom: 12px;
            letter-spacing: 0.2px;
        }
        .modal-desc {
            font-size: 1.13em;
            color: #444;
            background: #f4f7f9;
            border-radius: 9px;
            padding: 22px 24px;
            width: 100%;
            box-sizing: border-box;
            min-height: 140px;
            text-align: left;
            margin-bottom: 0;
            margin-top: 0;
            box-shadow: 0 1px 8px rgba(34,34,59,0.08);
            white-space: pre-line;
            /* Add scroll for long descriptions */
            max-height: 440px;
            overflow-y: auto;
        }
        .modal-actions {
            margin-top: 40px;
            display: flex;
            gap: 28px;
            justify-content: center;
            width: 100%;
        }
        .modal-actions .btn {
            font-size: 1.13em;
            padding: 14px 44px;
            border-radius: 9px;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(34,34,59,0.09);
        }
        .modal-actions .btn-approve {
            background: #28a745;
        }
        .modal-actions .btn-approve:hover {
            background: #218838;
        }
        .modal-actions .btn-reject {
            background: #dc3545;
        }
        .modal-actions .btn-reject:hover {
            background: #c82333;
        }
        @media (max-width: 1600px) {
            .modal-content {
                max-width: 99vw;
                padding: 32px 4vw 24px 4vw;
            }
            .modal-content-inner {
                gap: 32px;
            }
        }
        @media (max-width: 1400px) {
            .modal-content {
                max-width: 99vw;
                padding: 32px 10vw 24px 10vw;
            }
            .modal-content-inner {
                gap: 24px;
            }
            .modal-left, .modal-right {
                max-width: 100%;
                min-width: 0;
            }
        }
        @media (max-width: 900px) {
            .modal-content-inner {
                flex-direction: column;
                gap: 18px;
            }
            .modal-left, .modal-right {
                max-width: 100%;
                min-width: 0;
                padding: 18px 8px 12px 8px;
            }
            .modal-logo {
                width: 80px;
                height: 80px;
            }
        }
        @media (max-width: 600px) {
            .modal-content {
                flex-direction: column;
                padding: 8px 1vw 8px 1vw;
            }
            .modal-content-inner {
                flex-direction: column;
                gap: 8px;
            }
            .modal-logo {
                width: 56px;
                height: 56px;
            }
        }
        .modal-close {
            position: absolute;
            top: 28px;
            right: 44px;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2em;
            color: #888;
            background: transparent;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            z-index: 2;
            box-shadow: 0 2px 8px rgba(34,34,59,0.06);
        }
        .modal-close:hover, .modal-close:focus {
            background: #f4f7f9;
            color: #dc3545;
            box-shadow: 0 4px 16px rgba(220,53,69,0.10);
            outline: none;
        }
        .modal-close:active {
            background: #e3eafc;
            color: #c82333;
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('jobModal');
        const modalBackdrop = document.getElementById('modalBackdrop');
        const closeBtn = document.getElementById('modalClose');
        const jobCards = document.querySelectorAll('.job-card');
        let currentJobId = null;

        jobCards.forEach(card => {
            card.addEventListener('click', function(e) {
                if (e.target.closest('.company-name a')) return;
                document.getElementById('modalLogo').src = card.dataset.logo;
                document.getElementById('modalCategoryValue').textContent = card.dataset.category;
                document.getElementById('modalCompanyValue').innerHTML = `<a href="view_job_details_admin.php?company_id=${card.dataset.employer}" class="btn-company" style="text-decoration:none;">${card.dataset.company}</a>`;
                document.getElementById('modalSalaryValue').textContent = card.dataset.salary ? card.dataset.salary : '-';
                document.getElementById('modalLocationValue').textContent = card.dataset.location || '-';
                document.getElementById('modalCreatedValue').textContent = card.dataset.created || '-';
                document.getElementById('modalSkillsValue').textContent = card.dataset.skills || '-';
                document.getElementById('modalEducationValue').textContent = card.dataset.education || '-';
                document.getElementById('modalDesc').textContent = card.dataset.desc || '';
                document.getElementById('modalJobTitle').textContent = card.dataset.title;
                currentJobId = card.getAttribute('data-job-id');
                modalBackdrop.classList.add('active');
            });
        });

        closeBtn.addEventListener('click', function() {
            modalBackdrop.classList.remove('active');
        });
        modalBackdrop.addEventListener('click', function(e) {
            if (e.target === modalBackdrop) modalBackdrop.classList.remove('active');
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') modalBackdrop.classList.remove('active');
        });

        document.getElementById('modalApproveBtn').onclick = function() {
            if (!currentJobId) return;
            window.location.href = 'approve_job.php?job_id=' + encodeURIComponent(currentJobId);
        };
        document.getElementById('modalRejectBtn').onclick = function() {
            if (!currentJobId) return;
            window.location.href = 'reject_job.php?job_id=' + encodeURIComponent(currentJobId);
        };

        
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

        <?php
        if (!empty($_SESSION['message'])) {
            $msg = addslashes($_SESSION['message']);
            echo "window.addEventListener('DOMContentLoaded',function(){showPopup('{$msg}','success');});";
            unset($_SESSION['message']);
        }
        if (!empty($_SESSION['error'])) {
            $msg = addslashes($_SESSION['error']);
            echo "window.addEventListener('DOMContentLoaded',function(){showPopup('{$msg}','error');});";
            unset($_SESSION['error']);
        }
        ?>
    });
    </script>
</head>
<body>
    <nav class="navbar">
        <a href="admin_dashboard.php" class="logo">Admin Portal</a>
        <ul class="nav-links">
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="admin_dashboard.php?tab=users">User Management</a></li>
            <li><a href="../../logout.php">Logout</a></li>
        </ul>
    </nav>
    <div class="container">
        <?php
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
        if ($tab === 'users') {
            include 'user_management.php';
        } else {
        ?>
        <h1>Pending Job Posts</h1>
        <div id="pendingJobsContainer">
            <p class="no-jobs">Loading pending job posts...</p>
        </div>
        <?php } ?>
    </div>
    
    <div class="modal-backdrop" id="modalBackdrop">
        <div class="modal-content" id="jobModal">
            <button class="modal-close" id="modalClose" title="Close">&times;</button>
            <div class="modal-content-inner">
                <div class="modal-left">
                    <img id="modalLogo" class="modal-logo" src="" alt="Company Logo">
                    <table class="modal-details-table">
                        <tr>
                            <td class="label">Category:</td>
                            <td class="value" id="modalCategoryValue"></td>
                        </tr>
                        <tr>
                            <td class="label">Company:</td>
                            <td class="value modal-company" id="modalCompanyValue"></td>
                        </tr>
                        <tr>
                            <td class="label">Salary:</td>
                            <td class="value" id="modalSalaryValue"></td>
                        </tr>
                        <tr>
                            <td class="label">Location:</td>
                            <td class="value" id="modalLocationValue"></td>
                        </tr>
                        <tr>
                            <td class="label">Posted:</td>
                            <td class="value" id="modalCreatedValue"></td>
                        </tr>
                        <tr>
                            <td class="label">Skills:</td>
                            <td class="value" id="modalSkillsValue"></td>
                        </tr>
                        <tr>
                            <td class="label">Education:</td>
                            <td class="value" id="modalEducationValue"></td>
                        </tr>
                    </table>
                </div>
                <div class="modal-right">
                    <div class="modal-job-title" id="modalJobTitle"></div>
                    <div class="modal-desc-title">Job Description</div>
                    <div class="modal-desc" id="modalDesc"></div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-approve" id="modalApproveBtn">Approve</button>
                <button class="btn btn-reject" id="modalRejectBtn">Reject</button>
            </div>
        </div>
    </div>
    
    <div id="popupNotification" class="popup-notification">
        <span id="popupMessage"></span>
    </div>
    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> JobPortal. All rights reserved.</p>
    </footer>
    <script>
    // ...existing modal JS...

    // AJAX fetching for pending jobs
    function fetchPendingJobs() {
        const container = document.getElementById('pendingJobsContainer');
        fetch('fetch_pending_jobs.php')
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
                // Re-attach modal event listeners for new job cards
                if (typeof window.attachJobCardListeners === 'function') {
                    window.attachJobCardListeners();
                }
            })
            .catch(() => {
                container.innerHTML = '<p class="no-jobs">Failed to load pending job posts.</p>';
            });
    }

    // Expose modal event listeners for re-attachment after AJAX
    window.attachJobCardListeners = function() {
        const modal = document.getElementById('jobModal');
        const modalBackdrop = document.getElementById('modalBackdrop');
        const closeBtn = document.getElementById('modalClose');
        const jobCards = document.querySelectorAll('.job-card');
        let currentJobId = null;

        jobCards.forEach(card => {
            card.addEventListener('click', function(e) {
                if (e.target.closest('.company-name a')) return;
                document.getElementById('modalLogo').src = card.dataset.logo;
                document.getElementById('modalCategoryValue').textContent = card.dataset.category;
                document.getElementById('modalCompanyValue').innerHTML = `<a href="view_job_details_admin.php?company_id=${card.dataset.employer}" class="btn-company" style="text-decoration:none;">${card.dataset.company}</a>`;
                document.getElementById('modalSalaryValue').textContent = card.dataset.salary ? card.dataset.salary : '-';
                document.getElementById('modalLocationValue').textContent = card.dataset.location || '-';
                document.getElementById('modalCreatedValue').textContent = card.dataset.created || '-';
                document.getElementById('modalSkillsValue').textContent = card.dataset.skills || '-';
                document.getElementById('modalEducationValue').textContent = card.dataset.education || '-';
                document.getElementById('modalDesc').textContent = card.dataset.desc || '';
                document.getElementById('modalJobTitle').textContent = card.dataset.title;
                currentJobId = card.getAttribute('data-job-id');
                modalBackdrop.classList.add('active');
            });
        });

        closeBtn.addEventListener('click', function() {
            modalBackdrop.classList.remove('active');
        });
        modalBackdrop.addEventListener('click', function(e) {
            if (e.target === modalBackdrop) modalBackdrop.classList.remove('active');
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') modalBackdrop.classList.remove('active');
        });

        document.getElementById('modalApproveBtn').onclick = function() {
            if (!currentJobId) return;
            window.location.href = 'approve_job.php?job_id=' + encodeURIComponent(currentJobId);
        };
        document.getElementById('modalRejectBtn').onclick = function() {
            if (!currentJobId) return;
            window.location.href = 'reject_job.php?job_id=' + encodeURIComponent(currentJobId);
        };
    };

    // Initial fetch and periodic refresh
    document.addEventListener('DOMContentLoaded', function() {
        fetchPendingJobs();
        window.attachJobCardListeners();
        setInterval(fetchPendingJobs, 10000); // Refresh every 10 seconds
    });

    // ...existing popup notification JS...
    </script>
</body>
</html>
