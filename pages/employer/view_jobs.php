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

foreach ($jobs as $job) {
    if ($job['status'] === 'pending' || $job['status'] === 'reviewed') {
        $pendingJobs[] = $job;
    } elseif ($job['status'] === 'approved' || $job['status'] === 'active') {
        $approvedJobs[] = $job;
    } elseif ($job['status'] === 'rejected') {
        $rejectedJobs[] = $job;
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

            
            const toolsDeleteLink = document.getElementById('tools-delete-link');
            const deleteBar = document.getElementById('delete-bar');
            let selecting = false;

            
            const toolsEditLink = document.getElementById('tools-edit-link');
            const editBar = document.getElementById('edit-bar');
            const modalBackdrop = document.getElementById('modal-backdrop');
            const modalEditJob = document.getElementById('modal-edit-job');
            let editing = false;

            
            let selectedJobIds = new Set();

            function updateDeleteBtn() {
                document.getElementById('delete-selected-btn').disabled = selectedJobIds.size === 0;
                
                const countSpan = document.getElementById('delete-selected-count');
                if (countSpan) countSpan.textContent = `(${selectedJobIds.size})`;
            }

            function clearSelections() {
                selectedJobIds.clear();
                document.querySelectorAll('.application-card.deleting').forEach(card => card.classList.remove('selected'));
                updateDeleteBtn();
            }

            if (toolsDeleteLink) {
                toolsDeleteLink.addEventListener('click', () => {
                    
                    if (editing) {
                        editing = false;
                        document.body.classList.remove('editing-mode');
                        document.querySelectorAll('.application-card').forEach(card => card.classList.remove('editing', 'selected'));
                        if (editBar) editBar.style.display = 'none';
                    }
                    selecting = !selecting;
                    document.body.classList.toggle('deleting-mode', selecting);
                    clearSelections();
                    document.querySelectorAll('.application-card').forEach(card => {
                        if (selecting) card.classList.add('deleting');
                        else card.classList.remove('deleting');
                    });
                    if (deleteBar) deleteBar.style.display = selecting ? 'flex' : 'none';
                });
            }

            
            document.querySelectorAll('.application-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    if (!document.body.classList.contains('deleting-mode')) return;
                    e.preventDefault();
                    const checkbox = card.querySelector('.select-checkbox');
                    const jobId = checkbox ? checkbox.value : null;
                    if (!jobId) return;
                    if (card.classList.contains('selected')) {
                        card.classList.remove('selected');
                        selectedJobIds.delete(jobId);
                        if (checkbox) checkbox.checked = false;
                    } else {
                        card.classList.add('selected');
                        selectedJobIds.add(jobId);
                        if (checkbox) checkbox.checked = true;
                    }
                    updateDeleteBtn();
                });
            });

            
            const cancelBtn = document.getElementById('cancel-delete-btn');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => {
                    selecting = false;
                    document.body.classList.remove('deleting-mode');
                    clearSelections();
                    document.querySelectorAll('.application-card').forEach(card => card.classList.remove('deleting'));
                    if (deleteBar) deleteBar.style.display = 'none';
                });
            }

            
            const deleteForm = document.getElementById('delete-form');
            if (deleteForm) {
                deleteForm.addEventListener('submit', function(e) {
                    const count = selectedJobIds.size;
                    if (!confirm('Are you sure you want to delete the selected jobs? Total: ' + count + ' job(s)')) {
                        e.preventDefault();
                        return;
                    }
                    
                    document.querySelectorAll('.select-checkbox').forEach(cb => cb.checked = false);
                    
                    selectedJobIds.forEach(id => {
                        const cb = document.querySelector('.select-checkbox[value="' + id + '"]');
                        if (cb) cb.checked = true;
                    });
                });
            }

            
            document.querySelectorAll('.select-checkbox').forEach(cb => cb.style.display = 'none');
            if (deleteBar) deleteBar.style.display = 'none';

            if (toolsEditLink) {
                toolsEditLink.addEventListener('click', () => {
                    
                    if (selecting) {
                        selecting = false;
                        document.body.classList.remove('deleting-mode');
                        clearSelections();
                        document.querySelectorAll('.application-card').forEach(card => card.classList.remove('deleting'));
                        if (deleteBar) deleteBar.style.display = 'none';
                    }
                    editing = !editing;
                    document.body.classList.toggle('editing-mode', editing);
                    document.querySelectorAll('.application-card').forEach(card => {
                        if (editing) card.classList.add('editing');
                        else card.classList.remove('editing');
                        card.classList.remove('selected');
                    });
                    if (editBar) editBar.style.display = editing ? 'flex' : 'none';
                });
            }

            
            document.querySelectorAll('.application-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    if (!document.body.classList.contains('editing-mode')) return;
                    e.preventDefault();
                    
                    document.querySelectorAll('.application-card.editing').forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    
                    const checkbox = card.querySelector('.select-checkbox');
                    const jobId = checkbox ? checkbox.value : null;

                    
                    let jobData = null;
                    if (window.allJobs && Array.isArray(window.allJobs)) {
                        jobData = window.allJobs.find(j => String(j.job_id) === String(jobId));
                    }

                    
                    if (jobData) {
                        document.getElementById('modal-edit-job-id').value = jobData.job_id || '';
                        document.getElementById('modal-edit-title').value = jobData.title || '';
                        document.getElementById('modal-edit-description').value = jobData.description || '';
                        
                        const categorySelect = document.getElementById('modal-edit-category');
                        const otherCategoryInput = document.getElementById('modal-edit-other-category');
                        let found = false;
                        for (let i = 0; i < categorySelect.options.length; i++) {
                            if (categorySelect.options[i].value.trim().toLowerCase() === String(jobData.category || '').trim().toLowerCase()) {
                                categorySelect.value = categorySelect.options[i].value;
                                found = true;
                                break;
                            }
                        }
                        if (!found && jobData.category) {
                            categorySelect.value = "Others";
                            otherCategoryInput.value = jobData.category;
                        } else {
                            otherCategoryInput.value = "";
                        }
                        toggleOtherCategoryEdit();
                        
                        document.getElementById('modal-edit-salary').value = jobData.salary || '';
                        
                        if (jobData.location) {
                            let [region, city] = jobData.location.split(',').map(s => s.trim());
                            document.getElementById('modal-edit-region').value = region || '';
                            
                            setTimeout(() => {
                                updateCitiesEdit();
                                setTimeout(() => {
                                    document.getElementById('modal-edit-city').value = city || '';
                                }, 0);
                            }, 0);
                            document.getElementById('modal-edit-location').value = jobData.location;
                        } else {
                            document.getElementById('modal-edit-region').value = '';
                            updateCitiesEdit();
                            document.getElementById('modal-edit-city').value = '';
                            document.getElementById('modal-edit-location').value = '';
                        }
                        document.getElementById('modal-edit-company-name').textContent = jobData.company_name || '';
                        document.getElementById('modal-edit-company-name-hidden').value = jobData.company_name || '';
                        document.getElementById('modal-edit-skills').value = jobData.skills || '';
                        document.getElementById('modal-edit-education').value = jobData.education || '';
                    } else {
                        
                        document.getElementById('modal-edit-job-id').value = jobId || '';
                        document.getElementById('modal-edit-title').value = card.querySelector('.job-title').textContent.trim();
                        document.getElementById('modal-edit-description').value = '';
                        document.getElementById('modal-edit-category').value = '';
                        document.getElementById('modal-edit-other-category').value = '';
                        toggleOtherCategoryEdit();
                        document.getElementById('modal-edit-salary').value = '';
                        document.getElementById('modal-edit-region').value = '';
                        updateCitiesEdit();
                        document.getElementById('modal-edit-city').value = '';
                        document.getElementById('modal-edit-location').value = card.querySelector('.job-meta') ? card.querySelector('.job-meta').textContent.replace('Location: ','').trim() : '';
                        document.getElementById('modal-edit-company-name').textContent = '';
                        document.getElementById('modal-edit-company-name-hidden').value = '';
                        document.getElementById('modal-edit-skills').value = '';
                        document.getElementById('modal-edit-education').value = '';
                    }
                    
                    modalBackdrop.style.display = 'block';
                    modalBackdrop.classList.add('active');
                    modalEditJob.style.display = 'block';
                    modalEditJob.classList.add('active');
                    modalEditJob.scrollTop = 0;
                });
            });

            
            const cancelEditBtn = document.getElementById('cancel-edit-btn');
            if (cancelEditBtn) {
                cancelEditBtn.addEventListener('click', () => {
                    editing = false;
                    document.body.classList.remove('editing-mode');
                    document.querySelectorAll('.application-card').forEach(card => card.classList.remove('editing', 'selected'));
                    if (editBar) editBar.style.display = 'none';
                });
            }

            
            document.getElementById('modal-edit-cancel').onclick = function() {
                modalBackdrop.style.display = 'none';
                modalBackdrop.classList.remove('active');
                modalEditJob.style.display = 'none';
                modalEditJob.classList.remove('active');
                document.querySelectorAll('.application-card.editing').forEach(card => card.classList.remove('selected'));
            };
            modalBackdrop.onclick = function() {
                modalBackdrop.style.display = 'none';
                modalBackdrop.classList.remove('active');
                modalEditJob.style.display = 'none';
                modalEditJob.classList.remove('active');
                document.querySelectorAll('.application-card.editing').forEach(card => card.classList.remove('selected'));
            };

            
            document.getElementById('modal-edit-form').addEventListener('submit', function() {
                document.body.classList.remove('editing-mode');
                document.querySelectorAll('.application-card').forEach(card => card.classList.remove('editing', 'selected'));
            });
        });

        
        function toggleOtherCategoryEdit() {
            var jobCategory = document.getElementById("modal-edit-category");
            var otherCategory = document.getElementById("modal-edit-other-category");
            if (jobCategory.value === "Others") {
                otherCategory.style.display = "block";
                otherCategory.setAttribute("required", "true");
            } else {
                otherCategory.style.display = "none";
                otherCategory.removeAttribute("required");
                otherCategory.value = "";
            }
        }

        
        const citiesByRegion = {
            "NCR": [
                "Alabang", "Las Piñas", "Makati", "Malabon", "Manila", 
                "Mandaluyong", "Marikina", "Muntinlupa", "Navotas", 
                "Parañaque", "Pasig", "Pasay", "Quezon City", "San Juan", 
                "Taguig", "Valenzuela"
            ],
            "CAR": [
                "Apayao", "Baguio City", "Bontoc", "Bauang", "Itogon", 
                "Kalinga", "La Trinidad", "Mountain Province", "Tabuk", 
                "Tuba"
            ],
            "Region I": [
                "Alaminos City", "Baguio City", "Dagupan City", "Laoag City", 
                "Lingayen", "San Fernando City", "Urdaneta City", "Vigan City", 
                "Pangasinan", "Ilocos Norte", "Ilocos Sur", "La Union"
            ],
            "Region II": [
                "Aparri", "Cauayan City", "Ilagan City", "Isabela", 
                "Quirino", "Santiago City", "Tuguegarao City", "Nueva Vizcaya"
            ],
            "Region III": [
                "Angeles City", "Balanga City", "Bulacan", "Capas", 
                "Cavite", "Mabalacat", "Nueva Ecija", "Olongapo City", 
                "Pampanga", "San Fernando City", "Tarlac City"
            ],
            "Region IV-A": [
                "Antipolo City", "Batangas City", "Biñan", "Calamba City", 
                "Cavite City", "Dasmariñas", "Imus", "Laguna", 
                "Lucena City", "San Pablo City", "Santa Rosa", "Talisay"
            ],
            "Region IV-B": [
                "Boac", "Calapan City", "Mamburao", "Odiongan", 
                "Puerto Princesa City", "Roxas", "Romblon", "San Jose"
            ],
            "Region V": [
                "Albay", "Camarines Norte", "Camarines Sur", "Iriga City", 
                "Legazpi City", "Ligao City", "Masbate City", "Naga City", 
                "Sorsogon City", "Tabaco City"
            ],
            "Region VI": [
                "Bacolod City", "Binalbagan", "Iloilo City", "Kabankalan City", 
                "La Carlota", "Passi City", "Roxas City", "San Carlos City", 
                "Talisay City", "Negros Occidental", "Aklan", "Antique", 
                "Capiz", "Guimaras"
            ],
            "Region VII": [
                "Bohol", "Cebu City", "Dumaguete City", "Lapu-Lapu City", 
                "Mandaue City", "Toledo City", "Talisay City", "Carcar City", 
                "Siquijor"
            ],
            "Region VIII": [
                "Borongan City", "Butuan City", "Calbayog City", "Ormoc City", 
                "Samar", "Tacloban City", "Southern Leyte", "Leyte", 
                "Northern Samar"
            ],
            "Region IX": [
                "Dipolog City", "Dapitan City", "Pagadian City", "Zamboanga City", 
                "Zamboanga del Norte", "Zamboanga del Sur", "Zamboanga Sibugay"
            ],
            "Region X": [
                "Bukidnon", "Cagayan de Oro City", "El Salvador City", 
                "Gingoog City", "Iligan City", "Malaybalay City", 
                "Misamis Oriental", "Ozamiz City", "Camiguin"
            ],
            "Region XI": [
                "Compostela Valley", "Davao City", "Digos City", "Panabo City", 
                "Samal City", "Tagum City", "Davao del Norte", 
                "Davao del Sur", "Davao Occidental"
            ],
            "Region XII": [
                "General Santos City", "Kidapawan City", "Koronadal City", 
                "Sultan Kudarat", "Tacurong City", "South Cotabato", 
                "Cotabato City", "North Cotabato"
            ],
            "Region XIII": [
                "Agusan del Norte", "Agusan del Sur", "Butuan City", 
                "Bislig City", "Cabadbaran City", "Surigao City", 
                "Tandag City", "Samar", "Leyte"
            ],
            "BARMM": [
                "Bongao", "Cotabato City", "Lamitan City", "Marawi City", 
                "Maguindanao", "Sulu", "Tawi-Tawi"
            ]
        };

        function updateCitiesEdit() {
            const regionSelect = document.getElementById("modal-edit-region");
            const citySelect = document.getElementById("modal-edit-city");
            const locationInput = document.getElementById("modal-edit-location");
            const selectedRegion = regionSelect.value;

            citySelect.innerHTML = '<option value="">Select a City</option>';

            if (selectedRegion && citiesByRegion[selectedRegion]) {
                citiesByRegion[selectedRegion].forEach(city => {
                    const option = document.createElement("option");
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            }

            citySelect.addEventListener("change", () => {
                const selectedCity = citySelect.value;
                locationInput.value = selectedRegion + (selectedCity ? `, ${selectedCity}` : "");
            });
        }

        
        function formatSalaryModalInput(isBlur) {
            var displayInput = document.getElementById("modal-edit-salary-display");
            var hiddenInput = document.getElementById("modal-edit-salary");
            let value = displayInput.value.replace(/[^0-9.]/g, '');

            
            let parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
                parts = value.split('.');
            }

            
            if (parts.length === 2) {
                parts[1] = parts[1].slice(0,2);
                value = parts[0] + '.' + parts[1];
            }

            if (isBlur) {
                let num = value ? Math.min(parseFloat(value), 100000000) : '';
                if (num !== '' && !isNaN(num)) {
                    let formatted = Number(num).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    displayInput.value = '₱' + formatted;
                    hiddenInput.value = num;
                } else {
                    displayInput.value = '';
                    hiddenInput.value = '';
                }
            } else {
                displayInput.value = value ? '₱' + value : '';
                hiddenInput.value = value && !isNaN(value) ? Math.min(parseFloat(value), 100000000) : '';
            }
            validateSalaryModal();
        }

        function salaryModalFocus(isFocused) {
            var displayInput = document.getElementById("modal-edit-salary-display");
            var salaryInput = document.getElementById("modal-edit-salary");
            var salaryError = document.getElementById("modal-edit-salary-error");
            if (!isFocused) {
                salaryError.style.display = "none";
                formatSalaryModalInput(true);
            } else {
                formatSalaryModalInput(false);
                if (!salaryInput.checkValidity()) {
                    salaryError.style.display = "inline";
                }
            }
        }

        function validateSalaryModal() {
            var salaryInput = document.getElementById("modal-edit-salary");
            var salaryError = document.getElementById("modal-edit-salary-error");
            var value = parseFloat(salaryInput.value);

            if (salaryInput === document.activeElement && (isNaN(value) || value < 0 || value > 100000000)) {
                salaryError.style.display = "inline";
                salaryInput.setCustomValidity("Salary must be a positive number not exceeding 100,000,000.");
            } else {
                salaryError.style.display = "none";
                salaryInput.setCustomValidity("");
            }
        }

        
        function setModalSalaryDisplay(salary) {
            var displayInput = document.getElementById('modal-edit-salary-display');
            var hiddenInput = document.getElementById('modal-edit-salary');
            if (salary !== '' && !isNaN(salary)) {
                let num = Math.min(parseFloat(salary), 100000000);
                displayInput.value = '₱' + Number(num).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                hiddenInput.value = num;
            } else {
                displayInput.value = '';
                hiddenInput.value = '';
            }
        }

        
        document.addEventListener('DOMContentLoaded', () => {
            
            document.querySelectorAll('.application-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    
                    if (jobData) {
                        
                        setModalSalaryDisplay(jobData.salary || '');
                        
                    } else {
                        
                        setModalSalaryDisplay('');
                        
                    }
                    
                });
            });
            
            
            document.getElementById('modal-edit-form').addEventListener('submit', function(e) {
                formatSalaryModalInput(true);
                var salaryInput = document.getElementById("modal-edit-salary");
                var value = parseFloat(salaryInput.value);
                if (isNaN(value) || value < 0 || value > 100000000) {
                    e.preventDefault();
                    document.getElementById("modal-edit-salary-error").style.display = "inline";
                }
            });
        });

        
        window.addEventListener('DOMContentLoaded', function() {
            setModalSalaryDisplay(document.getElementById('modal-edit-salary').value);
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
    <div class="container">
        <div class="header-actions">
            <h1 style="flex:1;text-align:center;margin-left:-40px;">Job Listing</h1>
        </div>
        <div style="display: flex; align-items: flex-end; justify-content: space-between;">
            <div>
                <div class="tabs" style="margin-bottom: 0;">
                    <div class="tab active">Pending Jobs
                        <?php if (count($pendingJobs) > 0): ?>
                            <span class="tab-badge"><?php echo count($pendingJobs); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="tab">Approved Jobs
                        <?php if (count($approvedJobs) > 0): ?>
                            <span class="tab-badge"><?php echo count($approvedJobs); ?></span>
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
                    <a href="javascript:void(0);" id="tools-delete-link" style="color:#dc3545;">Delete</a>
                    <a href="javascript:void(0);" id="tools-edit-link" style="color:#007bff;">Edit/Update</a>
                </div>
            </div>
        </div>
        <form id="delete-form" action="delete_job.php" method="post">
            <div class="delete-bar" id="delete-bar">
                <button type="submit" class="btn" id="delete-selected-btn" disabled>
                    Delete Selected <span id="delete-selected-count">(0)</span>
                </button>
                <button type="button" class="btn" id="cancel-delete-btn" style="background:#6c757d;">Cancel</button>
            </div>
            <div class="edit-bar" id="edit-bar" style="display:none;">
                <button type="button" class="btn" id="cancel-edit-btn" style="background:#6c757d;">Cancel</button>
            </div>
            <div class="tab-content active">
                <?php if (count($pendingJobs) === 0): ?>
                    <div class="no-applications">No pending jobs found.</div>
                <?php else: ?>
                    <ul class="application-list">
                        <?php foreach ($pendingJobs as $job): ?>
                            <li class="application-card">
                                <input type="checkbox" name="job_ids[]" value="<?php echo htmlspecialchars($job['job_id']); ?>" class="select-checkbox" />
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
                <?php if (count($approvedJobs) === 0): ?>
                    <div class="no-applications">No approved jobs found.</div>
                <?php else: ?>
                    <ul class="application-list">
                        <?php foreach ($approvedJobs as $job): ?>
                            <li class="application-card">
                                <input type="checkbox" name="job_ids[]" value="<?php echo htmlspecialchars($job['job_id']); ?>" class="select-checkbox" />
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
                <?php if (count($rejectedJobs) === 0): ?>
                    <div class="no-applications">No rejected jobs found.</div>
                <?php else: ?>
                    <ul class="application-list">
                        <?php foreach ($rejectedJobs as $job): ?>
                            <li class="application-card">
                                <input type="checkbox" name="job_ids[]" value="<?php echo htmlspecialchars($job['job_id']); ?>" class="select-checkbox" />
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
        </form>
        <div class="modal-backdrop" id="modal-backdrop"></div>
        <div class="modal-edit-job" id="modal-edit-job">
            <div class="form-container">
                <button type="button" class="close-modal-x" id="modal-edit-cancel" title="Close">&times;</button>
                <h1>Edit Job</h1>
                <form method="post" action="edit_job.php" id="modal-edit-form">
                    <div class="form-grid">
                        
                        <div>
                            <div class="form-group">
                                <label for="modal-edit-title">Job Title</label>
                                <input type="text" name="title" id="modal-edit-title" required>
                            </div>
                            <div class="form-group">
                                <label for="modal-edit-category">Category</label>
                                <select id="modal-edit-category" name="category" onchange="toggleOtherCategoryEdit()" required>
                                    <option value="">Select a Category</option>
                                    <option value="IT ">IT And Software</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Healthcare">Healthcare</option>
                                    <option value="Education">Education</option>
                                    <option value="Engineering">Engineering</option>
                                    <option value="Sales">Sales</option>
                                    <option value="Customer Service">Customer Service</option>
                                    <option value="Human Resources">Human Resources</option>
                                    <option value="Legal">Legal</option>
                                    <option value="Manufacturing">Manufacturing</option>
                                    <option value="Hospitality & Tourism">Hospitality & Tourism</option>
                                    <option value="Construction">Construction</option>
                                    <option value="Transportation">Transportation</option>
                                    <option value="Arts & Design">Arts & Design</option>
                                    <option value="Retail">Retail</option>
                                    <option value="Others">Others</option>
                                </select>
                                <input type="text" id="modal-edit-other-category" name="other_category" placeholder="Enter category" style="display:none;margin-top:8px;" required>
                            </div>
                            <div class="form-group">
                                <label for="modal-edit-salary-display">Salary</label>
                                <input type="text" id="modal-edit-salary-display" placeholder="₱0.00" autocomplete="off" required
                                    oninput="formatSalaryModalInput()" onfocus="salaryModalFocus(true)" onblur="salaryModalFocus(false)">
                                <input type="number" name="salary" id="modal-edit-salary" min="0" max="100000000" step="0.01" style="display:none;" required>
                                <span id="modal-edit-salary-error" style="color: red; display: none;">Please enter a valid Salary between 0 and 100,000,000.</span>
                            </div>
                            <div class="form-group">
                                <label for="modal-edit-region">Region</label>
                                <select id="modal-edit-region" name="region" onchange="updateCitiesEdit()" required>
                                    <option value="">Select a Region</option>
                                    <option value="NCR">National Capital Region (NCR)</option>
                                    <option value="CAR">Cordillera Administrative Region (CAR)</option>
                                    <option value="Region I">Region I - Ilocos Region</option>
                                    <option value="Region II">Region II - Cagayan Valley</option>
                                    <option value="Region III">Region III - Central Luzon</option>
                                    <option value="Region IV-A">Region IV-A - CALABARZON</option>
                                    <option value="Region IV-B">Region IV-B - MIMAROPA</option>
                                    <option value="Region V">Region V - Bicol Region</option>
                                    <option value="Region VI">Region VI - Western Visayas</option>
                                    <option value="Region VII">Region VII - Central Visayas</option>
                                    <option value="Region VIII">Region VIII - Eastern Visayas</option>
                                    <option value="Region IX">Region IX - Zamboanga Peninsula</option>
                                    <option value="Region X">Region X - Northern Mindanao</option>
                                    <option value="Region XI">Region XI - Davao Region</option>
                                    <option value="Region XII">Region XII - SOCCSKSARGEN</option>
                                    <option value="Region XIII">Region XIII - Caraga</option>
                                    <option value="BARMM">Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="modal-edit-city">City</label>
                                <select id="modal-edit-city" name="city" required>
                                    <option value="">Select a City</option>
                                </select>
                            </div>
                            <input type="hidden" id="modal-edit-location" name="location">
                            <div class="form-group">
                                <label for="modal-edit-skills">Skills</label>
                                <input type="text" name="skills" id="modal-edit-skills" required>
                            </div>
                            <div class="form-group">
                                <label for="modal-edit-education">Education</label>
                                <input type="text" name="education" id="modal-edit-education" required>
                            </div>
                            <div class="form-group">
                                <label for="modal-edit-company-name">Company Name</label>
                                <div id="modal-edit-company-name"></div>
                                <input type="hidden" name="company_name" id="modal-edit-company-name-hidden">
                            </div>
                        </div>
                        
                        <div>
                            <div class="form-group" style="height:100%;">
                                <label for="modal-edit-description">Job Description</label>
                                <textarea name="description" id="modal-edit-description" rows="10" required></textarea>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="job_id" id="modal-edit-job-id">
                    <button type="submit" class="btn">Update Job</button>
                </form>
            </div>
        </div>
    </div>
    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> JobPortal. All rights reserved.</p>
    </footer>
</body>
</html>