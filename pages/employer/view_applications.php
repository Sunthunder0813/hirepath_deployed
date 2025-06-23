<?php
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['username'])) {
    header("Location: employee_sign_in.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applications</title>
    <link rel="stylesheet" href="../../static/css/view_applications.css">
    <link rel="shortcut icon" href="../../static/img/icon/favicon.png" type="image/x-icon">
    
    <script src="../../static/js/get_pending_count.js" defer></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7f9;
        }
        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
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
            background: #007bff;
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
            background: #144272;
            color: white;
            border-bottom: none;
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
    color: white;
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
        }
        footer {
            text-align: center;
            padding: 10px 0;
            background: #333;
            color: white;
        }
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
                fetch('/Hirepath/static/js/get_pending_count.php')
                    .then(response => response.json())
                    .then(data => {
                        const badge = document.getElementById('pending-badge');
                        badge.textContent = data.count;
                        badge.style.display = data.count > 0 ? 'inline-block' : 'none';
                    })
                    .catch(error => console.error('Error fetching pending count:', error));
            }

            updatePendingCount();
            setInterval(updatePendingCount, 5000); 

            
            function updateNavbarCount() {
                fetch('/Hirepath/static/js/get_pending_count.php')
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
        });
    </script>
    <script>
        function timeAgo(dateString) {
    const now = new Date();
    const appliedDate = new Date(dateString);
    const seconds = Math.floor((now.getTime() - appliedDate.getTime()) / 1000); 
    const intervals = [
        { label: 'year', seconds: 31536000 },
        { label: 'month', seconds: 2592000 },
        { label: 'day', seconds: 86400 },
        { label: 'hour', seconds: 3600 },
        { label: 'minute', seconds: 60 },
        { label: 'second', seconds: 1 }
    ];

    for (const interval of intervals) {
        const count = Math.floor(seconds / interval.seconds);
        if (count > 0) {
            return `${count} ${interval.label}${count !== 1 ? 's' : ''} ago`;
        }
    }
    return 'just now';
}

function updateTimeAgo() {
    const dateElements = document.querySelectorAll('.date');
    dateElements.forEach(element => {
        const appliedDate = element.getAttribute('data-applied-date');
        element.textContent = timeAgo(appliedDate);
    });
}

function confirmAction(action, url) {
    if (confirm(`Are you sure you want to ${action} this application?`)) {
        window.location.href = url;
    }
}

function markResumeAsViewed(button, applicationId) {
    button.textContent = "Resume viewed";
    button.style.background = "#6c757d";
    localStorage.setItem(`resumeViewed_${applicationId}`, true);
}

function restoreResumeViewedState() {
    const buttons = document.querySelectorAll('.resume-viewed');
    buttons.forEach(button => {
        const applicationId = button.getAttribute('data-application-id');
        if (localStorage.getItem(`resumeViewed_${applicationId}`)) {
            button.textContent = "Resume viewed";
            button.style.background = "#6c757d";
        }
    });
}

        // --- AJAX fetching and rendering applications ---
        function renderApplications(applications, container, status) {
            container.innerHTML = '';
            if (applications.length === 0) {
                container.innerHTML = `<p class="no-applications">No ${status} applications found.</p>`;
                return;
            }
            const ul = document.createElement('ul');
            ul.className = 'application-list';
            applications.forEach(application => {
                const li = document.createElement('li');
                li.className = 'application-card';
                let statusColor = '#ffc107', statusText = 'Pending';
                if (status === 'accepted') {
                    statusColor = '#28a745'; statusText = 'Accepted';
                } else if (status === 'rejected') {
                    statusColor = '#dc3545'; statusText = 'Rejected';
                }
                li.innerHTML = `
                    <strong>
                        <span class="job-title">${application.job_title}</span>
                    </strong>
                    <p>Applicant: ${application.applicant_name}</p>
                    <p>Status: <span style="color: ${statusColor}; font-weight: bold;">${statusText}</span></p>
                    <span class="date" data-applied-date="${application.applied_at}"></span>
                    ${
                        status === 'pending'
                        ? `<div>
                            <a href="javascript:void(0);" onclick="confirmAction('approve', 'approve_application.php?application_id=${application.application_id}')" class="btn" style="background: #28a745;">Approve</a>
                            <a href="javascript:void(0);" onclick="confirmAction('reject', 'reject_application.php?application_id=${application.application_id}')" class="btn" style="background: #dc3545;">Reject</a>
                        </div>`
                        : ''
                    }
                    ${
                        application.resume_link
                        ? (
                            status === 'pending'
                            ? `<a href="view_resume.php?application_id=${application.application_id}" class="btn resume-viewed" style="background: #17a2b8;" target="_blank" onclick="markResumeAsViewed(this, '${application.application_id}')" data-application-id="${application.application_id}">View Resume</a>`
                            : `<a href="${application.resume_link}" class="btn resume-viewed" style="background: #6c757d;" target="_blank">Resume viewed</a>`
                        )
                        : `<p style="color: #888; font-size: 12px;">No resume uploaded.</p>`
                    }
                `;
                ul.appendChild(li);
            });
            container.appendChild(ul);
            updateTimeAgo();
            restoreResumeViewedState();
        }

        function fetchAndRenderApplications() {
            fetch('fetch_applications.php')
                .then(response => response.json())
                .then(data => {
                    renderApplications(data.pending, document.getElementById('pending-applications'), 'pending');
                    renderApplications(data.accepted, document.getElementById('accepted-applications'), 'accepted');
                    renderApplications(data.rejected, document.getElementById('rejected-applications'), 'rejected');
                })
                .catch(error => {
                    document.getElementById('pending-applications').innerHTML = '<p class="no-applications">Failed to load applications.</p>';
                    document.getElementById('accepted-applications').innerHTML = '';
                    document.getElementById('rejected-applications').innerHTML = '';
                });
        }

        document.addEventListener('DOMContentLoaded', () => {
            // ...existing code...
            fetchAndRenderApplications();
        });
    </script>
</head>
<body>
    <nav class="navbar">
        <a href="Employee_dashboard.php" class="logo">Employee Portal</a>
        <ul class="nav-links">
        <li><a href="post_job.php">Post Job</a></li>  
            <li >
                <div class="applications-container">
                    <a href="view_applications.php" >Applications</a>
                    <span id="navbar-badge" class="nav-badge" style="display: none;">0</span>
                </div>
            </li>
            
        <li><a href="view_jobs.php">View Jobs</a></li>  
            <li><a href="company_profile.php">Company Profile</a></li>
            <li><a href="../../logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="header-actions">
            <h1>Job Applications</h1>
        </div>

        <div class="tabs">
            <div class="tab">
                Pending Applications
                <span id="pending-badge" class="tab-badge" style="display: none;">0</span>
            </div>
            <div class="tab">Accepted Applications</div>
            <div class="tab">Rejected Applications</div>
        </div>

        <div class="tab-content">
            <div id="pending-applications"></div>
        </div>
        <div class="tab-content">
            <div id="accepted-applications"></div>
        </div>
        <div class="tab-content">
            <div id="rejected-applications"></div>
        </div>
    </div>
    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> JobPortal. All rights reserved.</p>
    </footer>
</body>
</html>