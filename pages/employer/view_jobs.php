<?php
session_start();


if (!isset($_SESSION['user_id'], $_SESSION['username'])) {
    header("Location: employee_sign_in.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);


include '../../db_connection/connection.php';
$conn = OpenConnection();


$query = "SELECT * FROM jobs WHERE employer_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$jobs = [];
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

$pendingJobs = [];
$approvedJobs = [];
$rejectedJobs = [];
$inactiveJobs = [];
$freezeJobs = [];

foreach ($jobs as $job) {
    if ($job['status'] === 'pending' || $job['status'] === 'reviewed') {
        $pendingJobs[] = $job;
    } elseif ($job['status'] === 'approved' || $job['status'] === 'active') {
        $approvedJobs[] = $job;
    } elseif ($job['status'] === 'rejected') {
        $rejectedJobs[] = $job;
    } elseif ($job['status'] === 'inactive') {
        $inactiveJobs[] = $job;
    } elseif ($job['status'] === 'freeze') {
        $freezeJobs[] = $job;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs</title>
    <link rel="stylesheet" href="../../static/css/view_applications.css">
    <link rel="shortcut icon" href="../../static/img/icon/favicon.png" type="image/x-icon">
    <script src="../../static/js/view_application.js" defer></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7f9;
            overflow: hidden;
        }
        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            position: relative;
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
            grid-template-columns: repeat(3, 1fr); 
            gap: 15px;
            padding: 0;
            list-style: none;
            max-height: 60vh;
            overflow-y: auto;
            justify-items: center; 
        }
        .application-card {
            width: 100%; 
            max-width: 340px; 
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
        .job-title {
            font-size: 18px;
            font-weight: bold;
            color: #144272;
            margin-bottom: 5px;
        }
        .job-meta {
            font-size: 13px;
            color: #555;
            margin-bottom: 3px;
        }
        .job-status {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .job-status.pending { color: #ffc107; }
        .job-status.approved { color: #28a745; }
        .job-status.rejected { color: #dc3545; }
        .btn {
            text-decoration: none;
            padding: 8px 12px;
            background: #343a40; 
            color: white;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
            display: inline-block;
            transition: background 0.3s ease;
        }
        .btn:hover {
            background: #23272b; 
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
            background: #144272;
            color: white;
            border-bottom: none;
        }
        .tab.disabled {
            pointer-events: none;
            opacity: 0.5;
            background: #e9ecef !important;
            color: #aaa !important;
            cursor: not-allowed;
        }
        .tab-badge {
            position: absolute;
            top: 0px;
            right: 0px;
            background: #dc3545;
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .applications-container {
            position: relative;
        }
        .nav-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: #fff;
            font-size: 12px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
            margin-top: 18px;
        }
        footer {
            text-align: center;
            padding: 10px 0;    
            background: #333;
            color: white;
            margin-top: auto;
        }
        .tools-dropdown {
            position: relative;
            display: inline-block;
        }
        .tools-btn {
            cursor: pointer;
            border: none;
            outline: none;
            min-width: 140px;
            text-align: center;
            justify-content: center; 
            display: flex;           
            align-items: center;     
        }
        .tools-dropdown .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #fff;
            min-width: 140px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
            z-index: 1;
            border-radius: 6px;
            overflow: hidden;
        }
        .tools-dropdown .dropdown-content a {
            color: #333;
            padding: 12px 18px;
            text-decoration: none;
            display: block;
            font-size: 15px;
            transition: background 0.2s;
            text-align: center;
        }
        .tools-dropdown .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        .tools-dropdown:hover .dropdown-content {
            display: block;
        }
        .select-checkbox {
            display: none !important;
        }
        .application-card.deleting {
            background: #fff0f0;
            border: 2px solid #dc3545;
            box-shadow: 0 0 0 2px #dc354533;
            position: relative;
            cursor: pointer;
        }
        .application-card.deleting::before {
            content: '';
            position: absolute;
            left: 0; top: 0; right: 0; bottom: 0;
            background: rgba(220,53,69,0.10);
            border-radius: 8px;
            pointer-events: none;
        }
        .application-card.deleting.selected {
            background: #dc3545 !important;
            color: #fff;
        }
        .application-card.deleting.selected .job-title,
        .application-card.deleting.selected .job-meta,
        .application-card.deleting.selected .job-status,
        .application-card.deleting.selected .date {
            color: #fff !important;
            opacity: 1;
        }
        .application-card.deleting .job-title,
        .application-card.deleting .job-meta,
        .application-card.deleting .job-status,
        .application-card.deleting .date {
            opacity: 0.7;
        }
        .delete-bar {
            position: absolute;
            left: 50%;
            bottom: 10px;
            transform: translateX(-50%);
            display: none;
            justify-content: center;
            align-items: center;
            gap: 10px;
            z-index: 10;
            animation: fadeInDeleteBar 0.25s;
        }
        @keyframes fadeInDeleteBar {
            from { opacity: 0; transform: translateX(-50%) translateY(20px);}
            to { opacity: 1; transform: translateX(-50%) translateY(0);}
        }
        .delete-bar .btn {
            background: #dc3545;
        }
        .delete-bar .btn:disabled {
            background: #aaa;
            cursor: not-allowed;
        }
        .delete-bar .btn,
        .delete-bar #cancel-delete-btn {
            min-width: 120px;
            font-weight: 500;
        }
        .deleting-mode .delete-bar {
            display: flex !important;
        }
        .edit-bar {
            position: absolute;
            left: 50%;
            bottom: 10px;
            transform: translateX(-50%);
            display: none;
            justify-content: center;
            align-items: center;
            gap: 10px;
            z-index: 20;
            animation: fadeInDeleteBar 0.25s;
        }
        .edit-bar #cancel-edit-btn {
            min-width: 120px;
            font-weight: 500;
        }
        .deleting-mode .delete-bar,
        .editing-mode .edit-bar {
            display: flex !important;
        }
        .application-card.editing {
            background: #e3f0ff;
            border: 2px solid #007bff;
            box-shadow: 0 0 0 2px #007bff33;
            position: relative;
            cursor: pointer;
        }
        .application-card.editing.selected {
            background: #007bff !important;
            color: #fff;
        }
        .application-card.editing.selected .job-title,
        .application-card.editing.selected .job-meta,
        .application-card.editing.selected .job-status,
        .application-card.editing.selected .date {
            color: #fff !important;
            opacity: 1;
        }
        .application-card.editing .job-title,
        .application-card.editing .job-meta,
        .application-card.editing .job-status,
        .application-card.editing .date {
            opacity: 0.7;
        }
        .modal-backdrop {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0; top: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.25);
        }
        .modal-backdrop.active {
            display: block;
        }
        .modal-edit-job {
            display: none;
            position: fixed;
            z-index: 1002;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }
        .modal-edit-job.active {
            display: block;
        }
        .modal-edit-job .form-container {
            width: 70vw;
            max-width: 80vw;
            min-width: 600px;
            padding: 32px 40px 24px 40px;
            background: #f8f9fa;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(20,66,114,0.18);
            border: 2px solid #144272;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-edit-job .form-container h1 {
            text-align: center;
            margin-bottom: 24px;
            color: #144272;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .modal-edit-job .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            align-items: start;
        }
        .modal-edit-job .form-group {
            margin-bottom: 16px;
        }
        .modal-edit-job label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #144272;
        }
        .modal-edit-job input,
        .modal-edit-job select,
        .modal-edit-job textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #b5c9e2;
            border-radius: 8px;
            font-size: 15px;
            background: #fff;
            margin-bottom: 0;
        }
        .modal-edit-job textarea {
            min-height: 120px;
            max-height: 220px;
            resize: vertical;
        }
        .modal-edit-job .btn {
            width: 100%;
            background: #144272;
            color: #fff;
            border: none;
            padding: 12px 0;
            border-radius: 5px;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 1px;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 18px;
        }
        .modal-edit-job .btn:hover {
            background: #0056b3;
        }
        .modal-edit-job .cancel-link {
            display: none;
        }
        .modal-edit-job .close-modal-x {
            position: absolute;
            top: 18px;
            right: 32px;
            font-size: 2rem;
            color: #888;
            background: none;
            border: none;
            cursor: pointer;
            z-index: 10;
            font-weight: 700;
            line-height: 1;
            transition: color 0.2s;
        }
        .modal-edit-job .close-modal-x:hover {
            color: #dc3545;
        }
        .modal-edit-job #modal-edit-company-name {
            padding: 10px 12px;
            border: 2px solid #b5c9e2;
            border-radius: 8px;
            font-size: 15px;
            background: #e9f1fa;
            color: #144272;
            font-weight: 500;
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
        .freeze-select-indicator {
            display: none;
            text-align: center;
            background: #007bff;
            color: #fff;
            font-weight: bold;
            padding: 10px 0;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 1.1em;
            letter-spacing: 1px;
        }
        .freeze-mode .freeze-select-indicator {
            display: block;
        }
        .approve-select-indicator {
            display: none;
            text-align: center;
            background: #28a745;
            color: #fff;
            font-weight: bold;
            padding: 10px 0;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 1.1em;
            letter-spacing: 1px;
        }
        .approve-mode .approve-select-indicator {
            display: block;
        }
        .freeze-cancel-bar,
        .approve-cancel-bar {
            display: none;
            position: absolute;
            left: 50%;
            bottom: 10px;
            transform: translateX(-50%);
            z-index: 1002;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.12);
            padding: 0;
            min-width: 120px;
            width: 140px;
        }
        .freeze-mode .freeze-cancel-bar,
        .approve-mode .approve-cancel-bar {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .freeze-cancel-btn,
        .approve-cancel-btn {
            width: 100%;
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 0;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            letter-spacing: 1px;
        }
        .freeze-cancel-btn:hover,
        .approve-cancel-btn:hover {
            background: #b52a37;
        }
        .freeze-cancel-bar,
        .approve-cancel-bar {
            flex-direction: column;
            align-items: stretch;
        }
        /* ...existing code... */
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');

            tabs.forEach((tab, index) => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(tc => tc.classList.remove('active'));

                    tab.classList.add('active');
                    tabContents[index].classList.add('active');
                });
            });

            
            tabs[0].classList.add('active');
            tabContents[0].classList.add('active');

            
            function updatePendingCount() {
                fetch('get_pending_count.php')
                    .then(response => response.json())
                    .then(data => { // <-- FIXED: add parentheses around data
                        const badge = document.getElementById('pending-badge');
                        badge.textContent = data.count;
                        badge.style.display = data.count > 0 ? 'inline-block' : 'none';
                    })
                    .catch(error => console.error('Error fetching pending count:', error));
            }

            updatePendingCount();
            setInterval(updatePendingCount, 5000); 

            
            function updateNavbarCount() {
                fetch('get_pending_count.php')
                    .then(response => response.json())
                    .then(data => {
                        const navbarBadge = document.getElementById('navbar-badge');
                        navbarBadge.textContent = data.count;
                        navbarBadge.style.display = data.count > 0 ? 'inline-block' : 'none';
                    })
                    .catch(error => console.error('Error fetching navbar count:', error));
            }

            updateNavbarCount();
            setInterval(updateNavbarCount, 5000); 

            // --- REMOVE EDIT FUNCTIONALITY BELOW ---
            // Remove all edit-related JS logic
            // (toolsEditLink, editBar, editing, modalEditJob, modalEditForm, modalEditCancel, modalBackdrop, etc.)

            // --- Freeze Hiring logic ---
            let freezeMode = false;
            let approveMode = false;
            let selectedCard = null;
            let selectedJobId = null;

            const freezeIndicator = document.getElementById('freezeSelectIndicator');
            const freezeCancelBar = document.getElementById('freezeCancelBar');
            const freezeCancelBtn = document.getElementById('freezeCancelBtn');

            const approveIndicator = document.getElementById('approveSelectIndicator');
            const approveCancelBar = document.getElementById('approveCancelBar');
            const approveCancelBtn = document.getElementById('approveCancelBtn');

            // Helper to enable/disable Pending/Rejected/Displayed tabs
            function setTabsDisabled(disabled, mode) {
                // Tab order: Displayed, Inactive, Freeze, Pending, Rejected
                if (tabs.length >= 5) {
                    // Always clear all disables first
                    tabs.forEach(t => t.classList.remove('disabled'));
                    if (disabled) {
                        if (mode === 'freeze') {
                            // Disable Freeze, Pending, Rejected
                            tabs[2].classList.add('disabled'); // Freeze
                            tabs[3].classList.add('disabled'); // Pending
                            tabs[4].classList.add('disabled'); // Rejected
                        } else if (mode === 'approve') {
                            // Disable Displayed, Pending, Rejected
                            tabs[0].classList.add('disabled'); // Displayed
                            tabs[3].classList.add('disabled'); // Pending
                            tabs[4].classList.add('disabled'); // Rejected
                        }
                    }
                }
            }

            // Freeze Hiring click handler (enable freeze mode)
            const freezeLink = document.getElementById('freeze-hiring-link');
            if (freezeLink) {
                freezeLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    freezeMode = true;
                    approveMode = false;
                    selectedCard = null;
                    selectedJobId = null;
                    document.body.classList.add('freeze-mode');
                    document.body.classList.remove('approve-mode');
                    document.querySelectorAll('.application-card').forEach(c => c.classList.remove('selected'));
                    if (freezeIndicator) freezeIndicator.style.display = 'block';
                    if (freezeCancelBar) freezeCancelBar.style.display = 'flex';
                    if (approveIndicator) approveIndicator.style.display = 'none';
                    if (approveCancelBar) approveCancelBar.style.display = 'none';
                    setTabsDisabled(true, 'freeze');
                    showPopup('Select a job card to freeze.', 'success');
                });
            }

            // Approve Job click handler (enable approve mode)
            const approveLink = document.getElementById('approve-job-link');
            if (approveLink) {
                approveLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    approveMode = true;
                    freezeMode = false;
                    selectedCard = null;
                    selectedJobId = null;
                    document.body.classList.add('approve-mode');
                    document.body.classList.remove('freeze-mode');
                    document.querySelectorAll('.application-card').forEach(c => c.classList.remove('selected'));
                    if (approveIndicator) approveIndicator.style.display = 'block';
                    if (approveCancelBar) approveCancelBar.style.display = 'flex';
                    if (freezeIndicator) freezeIndicator.style.display = 'none';
                    if (freezeCancelBar) freezeCancelBar.style.display = 'none';
                    setTabsDisabled(true, 'approve');
                    showPopup('Select a job card to approve.', 'success');
                });
            }

            // Cancel freeze mode
            if (freezeCancelBtn) {
                freezeCancelBtn.addEventListener('click', function() {
                    freezeMode = false;
                    selectedCard = null;
                    selectedJobId = null;
                    document.body.classList.remove('freeze-mode');
                    document.querySelectorAll('.application-card').forEach(c => c.classList.remove('selected'));
                    if (freezeIndicator) freezeIndicator.style.display = 'none';
                    if (freezeCancelBar) freezeCancelBar.style.display = 'none';
                    setTabsDisabled(false, 'freeze');
                });
            }

            // Cancel approve mode
            if (approveCancelBtn) {
                approveCancelBtn.addEventListener('click', function() {
                    approveMode = false;
                    selectedCard = null;
                    selectedJobId = null;
                    document.body.classList.remove('approve-mode');
                    document.querySelectorAll('.application-card').forEach(c => c.classList.remove('selected'));
                    if (approveIndicator) approveIndicator.style.display = 'none';
                    if (approveCancelBar) approveCancelBar.style.display = 'none';
                    setTabsDisabled(false, 'approve');
                });
            }

            // Add click event to application cards for selection (only in freeze or approve mode)
            document.querySelectorAll('.application-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    if (!freezeMode && !approveMode) return;
                    document.querySelectorAll('.application-card').forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    selectedCard = card;
                    const title = card.querySelector('.job-title')?.textContent.trim();
                    const date = card.querySelector('.date')?.textContent.replace('Posted:','').trim();
                    if (window.allJobs) {
                        const job = window.allJobs.find(j =>
                            j.title === title && j.created_at === date
                        );
                        selectedJobId = job ? job.job_id : null;
                        var status = job ? job.status : '';
                    }
                    // Restrict freeze/approve for pending/rejected
                    if ((freezeMode || approveMode) && (status === 'pending' || status === 'reviewed' || status === 'rejected')) {
                        showPopup('You cannot freeze or approve jobs that are Pending or Rejected.', 'error');
                        card.classList.remove('selected');
                        return;
                    }
                    // Freeze logic
                    if (freezeMode && selectedJobId) {
                        if (!confirm('Are you sure you want to freeze hiring for this job?')) {
                            card.classList.remove('selected');
                            freezeMode = false;
                            selectedJobId = null;
                            document.body.classList.remove('freeze-mode');
                            if (freezeIndicator) freezeIndicator.style.display = 'none';
                            if (freezeCancelBar) freezeCancelBar.style.display = 'none';
                            return;
                        }
                        fetch('freeze_job.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'job_id=' + encodeURIComponent(selectedJobId)
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showPopup('Job has been frozen (hidden from index).', 'success');
                                setTimeout(() => window.location.reload(), 1200);
                            } else {
                                showPopup('Failed to freeze job.', 'error');
                            }
                        })
                        .catch(() => showPopup('Error freezing job.', 'error'));
                        freezeMode = false;
                        document.body.classList.remove('freeze-mode');
                        if (freezeIndicator) freezeIndicator.style.display = 'none';
                        if (freezeCancelBar) freezeCancelBar.style.display = 'none';
                    }
                    // Approve logic (only for freeze or inactive)
                    if (approveMode && selectedJobId && (status === 'freeze' || status === 'inactive')) {
                        if (!confirm('Are you sure you want to approve this job?')) {
                            card.classList.remove('selected');
                            approveMode = false;
                            selectedJobId = null;
                            document.body.classList.remove('approve-mode');
                            if (approveIndicator) approveIndicator.style.display = 'none';
                            if (approveCancelBar) approveCancelBar.style.display = 'none';
                            return;
                        }
                        fetch('approve_job.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'job_id=' + encodeURIComponent(selectedJobId)
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showPopup('Job has been approved.', 'success');
                                setTimeout(() => window.location.reload(), 1200);
                            } else {
                                showPopup('Failed to approve job.', 'error');
                            }
                        })
                        .catch(() => showPopup('Error approving job.', 'error'));
                        approveMode = false;
                        document.body.classList.remove('approve-mode');
                        if (approveIndicator) approveIndicator.style.display = 'none';
                        if (approveCancelBar) approveCancelBar.style.display = 'none';
                    }
                    // Prevent approve for pending/rejected (already handled above)
                });
            });
        });

        
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
            if (params.get('deleted') === '1') {
                showPopup('Job(s) deleted successfully!', 'success');
            }
            if (params.get('deleted') === '0') {
                showPopup('No jobs were deleted.', 'error');
            }
            if (params.get('updated') === '1') {
                showPopup('Job updated successfully!', 'success');
            }
        })();
    </script>
    <?php
    
    echo "<script>window.allJobs = " . json_encode($jobs, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) . ";</script>";
    ?>
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
                    <span id="navbar-badge" class="nav-badge" style="display: none;">0</span>
                </div>
            </li>
            <li><a href="view_jobs.php">View Jobs</a></li>  
            <li><a href="company_profile.php">Company Profile</a></li>
            <li><a href="../../logout.php">Logout</a></li>
        </ul>
    </nav>
    <div class="container" style="position:relative;">
        <div class="header-actions">
            <h1 style="flex:1;text-align:center;margin-left:-40px;">Job Listing</h1>
        </div>
        <div style="display: flex; align-items: flex-end; justify-content: space-between;">
            <div>
                <div class="tabs" style="margin-bottom: 0;">
                    <div class="tab">Displayed Jobs
                        <?php if (count($approvedJobs) > 0): ?>
                            <span class="tab-badge"><?php echo count($approvedJobs); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="tab">Inactive Jobs
                        <?php if (count($inactiveJobs) > 0): ?>
                            <span class="tab-badge"><?php echo count($inactiveJobs); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="tab">Freeze Hiring
                        <?php if (count($freezeJobs) > 0): ?>
                            <span class="tab-badge"><?php echo count($freezeJobs); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="tab">Pending Jobs
                        <?php if (count($pendingJobs) > 0): ?>
                            <span class="tab-badge"><?php echo count($pendingJobs); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="tab">Rejected Jobs
                        <?php if (count($rejectedJobs) > 0): ?>
                            <span class="tab-badge"><?php echo count($rejectedJobs); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="tools-dropdown">
                <button class="btn tools-btn" style="background:#343a40; height:40px; display:flex; align-items:center; position:relative;" type="button" tabindex="-1">
                    Manage &#x25BC;
                </button>
                <div class="dropdown-content">
                    <a href="javascript:void(0);" id="freeze-hiring-link" style="color:#007bff;">Freeze Hiring</a>
                    <a href="javascript:void(0);" id="approve-job-link" style="color:#28a745;">Display Job</a>
                </div>
            </div>
        </div>
        <div id="freezeSelectIndicator" class="freeze-select-indicator">
            Freeze Mode: Please select a job card to freeze hiring.
        </div>
        <div id="approveSelectIndicator" class="approve-select-indicator">
            Approve Mode: Please select a Freeze or Inactive job to approve.
        </div>
        <!-- Remove edit-form and edit-bar -->
        <div class="tab-content active">
            <?php if (count($approvedJobs) === 0): ?>
                <div class="no-applications">No displayed jobs found.</div>
            <?php else: ?>
                <ul class="application-list">
                    <?php foreach ($approvedJobs as $job): ?>
                        <li class="application-card">
                            <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                            <?php if (!empty($job['location'])): ?>
                                <div class="job-meta">Location: <?php echo htmlspecialchars($job['location']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($job['type'])): ?>
                                <div class="job-meta">Type: <?php echo htmlspecialchars($job['type']); ?></div>
                            <?php endif; ?>
                            <div class="job-status approved">Status: <?php echo ucfirst(htmlspecialchars($job['status'])); ?></div>
                            <div class="date">Posted: <?php echo htmlspecialchars($job['created_at']); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="tab-content">
            <?php if (count($inactiveJobs) === 0): ?>
                <div class="no-applications">No inactive jobs found.</div>
            <?php else: ?>
                <ul class="application-list">
                    <?php foreach ($inactiveJobs as $job): ?>
                        <li class="application-card">
                            <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                            <?php if (!empty($job['location'])): ?>
                                <div class="job-meta">Location: <?php echo htmlspecialchars($job['location']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($job['type'])): ?>
                                <div class="job-meta">Type: <?php echo htmlspecialchars($job['type']); ?></div>
                            <?php endif; ?>
                            <div class="job-status" style="color:#888;">Status: Inactive</div>
                            <div class="date">Posted: <?php echo htmlspecialchars($job['created_at']); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="tab-content">
            <?php if (count($freezeJobs) === 0): ?>
                <div class="no-applications">No free hiring jobs found.</div>
            <?php else: ?>
                <ul class="application-list">
                    <?php foreach ($freezeJobs as $job): ?>
                        <li class="application-card">
                            <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                            <?php if (!empty($job['location'])): ?>
                                <div class="job-meta">Location: <?php echo htmlspecialchars($job['location']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($job['type'])): ?>
                                <div class="job-meta">Type: <?php echo htmlspecialchars($job['type']); ?></div>
                            <?php endif; ?>
                            <div class="job-status" style="color:#007bff;">Status: Freeze</div>
                            <div class="date">Posted: <?php echo htmlspecialchars($job['created_at']); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="tab-content">
            <?php if (count($pendingJobs) === 0): ?>
                <div class="no-applications">No pending jobs found.</div>
            <?php else: ?>
                <ul class="application-list">
                    <?php foreach ($pendingJobs as $job): ?>
                        <li class="application-card">
                            <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                            <?php if (!empty($job['location'])): ?>
                                <div class="job-meta">Location: <?php echo htmlspecialchars($job['location']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($job['type'])): ?>
                                <div class="job-meta">Type: <?php echo htmlspecialchars($job['type']); ?></div>
                            <?php endif; ?>
                            <div class="job-status pending">Status: <?php echo ucfirst(htmlspecialchars($job['status'])); ?></div>
                            <div class="date">Posted: <?php echo htmlspecialchars($job['created_at']); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="tab-content">
            <?php if (count($rejectedJobs) === 0): ?>
                <div class="no-applications">No rejected jobs found.</div>
            <?php else: ?>
                <ul class="application-list">
                    <?php foreach ($rejectedJobs as $job): ?>
                        <li class="application-card">
                            <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                            <?php if (!empty($job['location'])): ?>
                                <div class="job-meta">Location: <?php echo htmlspecialchars($job['location']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($job['type'])): ?>
                                <div class="job-meta">Type: <?php echo htmlspecialchars($job['type']); ?></div>
                            <?php endif; ?>
                            <div class="job-status rejected">Status: <?php echo ucfirst(htmlspecialchars($job['status'])); ?></div>
                            <div class="date">Posted: <?php echo htmlspecialchars($job['created_at']); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div id="freezeCancelBar" class="freeze-cancel-bar">
            <button type="button" id="freezeCancelBtn" class="freeze-cancel-btn">
                Cancel
            </button>
        </div>
        <div id="approveCancelBar" class="approve-cancel-bar">
            <button type="button" id="approveCancelBtn" class="approve-cancel-btn">
                Cancel
            </button>
        </div>
    </div>
    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> JobPortal. All rights reserved.</p>
    </footer>
</body>
</html>