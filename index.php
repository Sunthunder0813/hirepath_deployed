<?php
session_start();
include 'db_connection/connection.php'; 

$conn = OpenConnection(); 
$conn->set_charset('utf8mb4');

$username = '';

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

$selectedJobId = isset($_GET['job_id']) ? intval($_GET['job_id']) : null;

$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$salaryFilter = isset($_GET['salary']) ? $_GET['salary'] : '';
$locationFilter = isset($_GET['location']) ? $_GET['location'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT job_id, title, description, category, salary, location, created_at, company_name, skills, education, employer_id
        FROM jobs 
        WHERE status = 'approved'";

$conditions = [];
$params = [];
$types = '';

if (!empty($categoryFilter)) {
    $conditions[] = "category = ?";
    $params[] = $categoryFilter;
    $types .= 's';
}

if (!empty($salaryFilter)) {
    $salaryRange = explode('-', $salaryFilter);
    if (count($salaryRange) === 2) {
        $conditions[] = "salary BETWEEN ? AND ?";
        $params[] = (int)$salaryRange[0];
        $params[] = (int)$salaryRange[1];
        $types .= 'ii';
    }
}

if (!empty($locationFilter)) {
    $city = trim(explode(',', $locationFilter)[0]);
    $conditions[] = "location LIKE ?";
    $params[] = "%{$city}%";
    $types .= 's';
}

if (!empty($searchQuery)) {
    $conditions[] = "(title LIKE ? OR education LIKE ? OR skills LIKE ? OR description LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
    $types .= 'ssss';
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(' AND ', $conditions);
}

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query failed: " . $conn->error);
}

$isInitialLogin = false;
if (isset($_SESSION['initial_login']) && $_SESSION['initial_login'] === true) {
    $isInitialLogin = true;
    unset($_SESSION['initial_login']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="static/img/icon/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="static/css/index.css">
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
    </style>
    <title>HirePath</title>
</head>
<body>
    <div id="popupNotification" class="popup-notification">
        <span id="popupMessage"></span>
    </div>
    <header>
    <nav>
    <p class="logo">
        <a href="index.php">
            <img src="static/img/icon/logo.png" alt="Hire Path Logo">
        </a>
    </p>
        <ul class="nav_links" id="nav_links" aria-label="Main navigation">
            <li><a href="pages/employer/employee_sign_in.php">Post a Job</a></li>
            <?php if (!empty($username)): ?>
                <li><a href="pages/job_seeker/application.php">Application</a></li>
            <?php endif; ?>
            <?php if (!empty($username)): ?>
                <li class="profile_dropdown">
                <a tabindex="0" aria-haspopup="true" aria-expanded="false"><?php echo htmlspecialchars($email); ?> <span style="font-size: 1em;">&#9660;</span></a>
                <ul class="dropdown_menu">
                    <li><a href="pages/job_seeker/jobseeker_changepass.php" class="sign_out_button">Change Password</a></li>
                    <li><a href="logout.php" class="sign_out_button">Sign Out</a></li>
                </ul>
            </li>
            <?php else: ?>
                <li><a href="pages/job_seeker/sign_in.php">Sign In</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="head_container">
        <div class="search_bar">
            <form class="search_bar_row" id="main_search_form" autocomplete="off" onsubmit="applyFilters();return false;">
                <div class="search_input_wrapper" style="position:relative;">
                    <input type="text" id="search_input" name="search" placeholder="Search" value="<?php echo htmlspecialchars($searchQuery); ?>" autocomplete="off" />
                </div>
                <div class="search_divider"></div>
                <?php
                    $filterApplied = !empty($categoryFilter) || !empty($salaryFilter) || !empty($locationFilter);
                ?>
                <button
                    type="button"
                    class="location_btn<?php echo $filterApplied ? ' filter-applied' : ''; ?>"
                    onclick="openFilterModal()"
                    aria-label="Open filters"
                    style="<?php
                        if ($filterApplied) {
                            echo 'background:#e6faea; color:#28a745; font-weight:600;';
                        }
                    ?>"
                >
                    <span style="<?php if($filterApplied) echo 'color:#28a745;'; ?>">
                        <?php echo $filterApplied ? 'Filters Applied' : 'Filter'; ?>
                    </span>
                </button>
            </form>
            <div id="filter_modal" tabindex="-1" aria-modal="true" role="dialog" style="display:none;">
                <div class="filter_modal_card">
                    <button type="button" class="filter_modal_close_x" aria-label="Close" onclick="closeFilterModal()">&times;</button>
                    <h3>Filter Jobs</h3>
                    <form onsubmit="applyFilters();return false;">
                        <label for="filter_category">Category</label>
                        <select id="filter_category" name="category">
                            <option value="" disabled selected <?php echo $categoryFilter === '' ? 'selected' : ''; ?>></option>
                            <option value="IT" <?php echo $categoryFilter === 'IT' ? 'selected' : ''; ?>>IT & Software</option>
                            <option value="Finance" <?php echo $categoryFilter === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                            <option value="Healthcare" <?php echo $categoryFilter === 'Healthcare' ? 'selected' : ''; ?>>Healthcare</option>
                            <option value="Education" <?php echo $categoryFilter === 'Education' ? 'selected' : ''; ?>>Education</option>
                            <option value="Engineering" <?php echo $categoryFilter === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                            <option value="Sales" <?php echo $categoryFilter === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                            <option value="Marketing" <?php echo $categoryFilter === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                            <option value="Customer Service" <?php echo $categoryFilter === 'Customer Service' ? 'selected' : ''; ?>>Customer Service</option>
                            <option value="Human Resources" <?php echo $categoryFilter === 'Human Resources' ? 'selected' : ''; ?>>Human Resources</option>
                            <option value="Construction" <?php echo $categoryFilter === 'Construction' ? 'selected' : ''; ?>>Construction</option>
                            <option value="Transportation" <?php echo $categoryFilter === 'Transportation' ? 'selected' : ''; ?>>Transportation</option>
                            <option value="Legal" <?php echo $categoryFilter === 'Legal' ? 'selected' : ''; ?>>Legal</option>
                            <option value="Arts & Design" <?php echo $categoryFilter === 'Arts & Design' ? 'selected' : ''; ?>>Arts & Design</option>
                            <option value="Hospitality" <?php echo $categoryFilter === 'Hospitality' ? 'selected' : ''; ?>>Hospitality</option>
                            <option value="Manufacturing" <?php echo $categoryFilter === 'Manufacturing' ? 'selected' : ''; ?>>Manufacturing</option>
                        </select>

                        <label for="filter_salary">Salary</label>
                        <select id="filter_salary" name="salary">
                            <option value=""disabled selected></option>
                            <option value="0-20000" <?php echo $salaryFilter === '0-20000' ? 'selected' : ''; ?>>₱0 - ₱20,000</option>
                            <option value="20001-50000" <?php echo $salaryFilter === '20001-50000' ? 'selected' : ''; ?>>₱20,001 - ₱50,000</option>
                            <option value="50001-100000" <?php echo $salaryFilter === '50001-100000' ? 'selected' : ''; ?>>₱50,001 - ₱100,000</option>
                            <option value="100001-150000" <?php echo $salaryFilter === '100001-150000' ? 'selected' : ''; ?>>₱100,001 - ₱150,000</option>
                            <option value="150001-200000" <?php echo $salaryFilter === '150001-200000' ? 'selected' : ''; ?>>₱150,001 - ₱200,000</option>
                        </select>

                        <label for="region_select">Region</label>
                        <select id="region_select" onchange="populateCities()">
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

                        <label for="city_select">City</label>
                        <select id="city_select" style="display: flex;">
                        </select>

                        <div class="button_container">
                            <button type="button" onclick="applyFilters()" class="filter-apply-btn">
                                Apply Filters
                            </button>
                            <button type="button" onclick="removeFilters()" class="filter-remove-btn">
                                Remove Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
    <main class="container">
        <div class="job_container">
            <div class="job_listings">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="job"
                            data_id="<?php echo htmlspecialchars($row['job_id']); ?>"
                            data_job_id="<?php echo (int)$row['job_id']; ?>"
                            data_employer_id="<?php echo (int)$row['employer_id']; ?>"
                            data_created_at="<?php echo htmlspecialchars($row['created_at']); ?>"
                            data_title="<?php echo htmlspecialchars(html_entity_decode($row['title'])); ?>"
                            data_category="<?php echo htmlspecialchars(html_entity_decode($row['category'])); ?>"
                            data_salary="₱<?php echo htmlspecialchars(number_format($row['salary'], 2)); ?>"
                            data_location="<?php echo htmlspecialchars(html_entity_decode($row['location'])); ?>"
                            data_company_name="<?php echo htmlspecialchars(html_entity_decode($row['company_name'])); ?>"
                            data_skills="<?php echo htmlspecialchars(html_entity_decode($row['skills'])); ?>"
                            data_education="<?php echo htmlspecialchars(html_entity_decode($row['education'])); ?>"
                            data_description="<?php echo htmlspecialchars(html_entity_decode($row['description'])); ?>"
                            <?php if ($selectedJobId && $selectedJobId == $row['job_id']) echo 'data-autoselect="1"'; ?>
                        >
                            <h2><?php echo htmlspecialchars($row['title']); ?></h2>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars(html_entity_decode($row['category'])); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars(html_entity_decode($row['location'])); ?></p>
                            <p id="job_posted<?php echo (int)$row['job_id']; ?>" class="job_posted"></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No job postings available at the moment.</p>
                <?php endif; ?>
            </div>
            
            <div class="job_details">
                <div class="loading">
                    <div class="bouncing_loader">
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                </div>
                <div class="job_title_container" id="job_title_container" data_job_id_now="">
                    <h2 id="job_title">Select a job to see details</h2>
                    <p class="job_mini_details">
                        <a id="job_company" href="javascript:void(0);" onclick="checkLoginAndViewCompany()">Company Name</a> | 
                        <span id="job_location">Location</span> | 
                        <span id="job_category">Category</span>
                    </p>
                    <button class="apply_button" onclick="checkLoginAndApply()">Apply Now</button>
                </div>
                <hr class="divider">
                
                <div class="job_info">
                    <p>
                        <strong><img src="static/img/icon/salary.png" alt="Salary Icon" style="width: 16px; height: 16px;"> Salary:</strong> 
                        <span id="job_salary">N/A</span>
                    </p>
                </div>
                <div class="job_info">
                    <p>
                        <strong><img src="static/img/icon/skill.png" alt="Skills Icon" style="width: 16px; height: 16px;"> Skills:</strong>
                        <span id="job_skills">N/A</span>
                    </p>
                </div>
                <div class="job_info">  
                    <p>
                        <strong><img src="static/img/icon/education.png" alt="Education Icon" style="width: 16px; height: 16px;"> Education:</strong> 
                        <span id="job_education">N/A</span>
                    </p>
                </div>
                <div class="job_info_description">
                    <h3>Full Job Description:</h3>
                    <div id="job_description" class="job_description_container" data-description="Please select a job to see the description."></div>
                    <p class="job_posted_info"><em>Active since: <span id="job_posted"></span></em></p>
                </div>
            </div>
        </div>
    </main>
<script>


function checkLoginAndApply() {
    <?php if (!isset($_SESSION['user_id'])): ?>
        alert("You need to log in to apply for a job. Redirecting to the sign-in page...");
        window.open('pages/job_seeker/sign_in.php', '_blank');
    <?php else: ?>
        const jobTitleContainer = document.getElementById('job_title_container');
        const jobId = jobTitleContainer.getAttribute('data_job_id_now');

        if (!jobId || jobId === "null" || jobId === "") {
            alert("Please select a job before applying.");
        } else {
            window.location.href = 'pages/job_seeker/upload_resume.php?job_id=' + encodeURIComponent(jobId);
        }
    <?php endif; ?>
}
function checkLoginAndViewCompany(employerId) {
    <?php if (!isset($_SESSION['user_id'])): ?>
        alert("You need to log in to view the company profile. Redirecting to the sign-in page...");
        window.open('pages/job_seeker/sign_in.php', '_self');
    <?php else: ?>
        window.location.href = `pages/job_seeker/view_company_profile.php?company_id=${encodeURIComponent(employerId)}`;
    <?php endif; ?>
}



document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.loading').style.display = 'block';

    document.querySelectorAll('.job').forEach(function(jobElement) {
        const createdAt = jobElement.getAttribute('data_created_at');
        const jobPostedElement = jobElement.querySelector('.job_posted');
        jobPostedElement.innerText = timeSince(createdAt);
    });

    document.querySelector('.loading').style.display = 'none';

    document.querySelectorAll('.job_listings .job').forEach(function(jobDiv) {
        jobDiv.onclick = null;

        jobDiv.addEventListener('click', function(e) {
            const jobId = this.getAttribute('data_job_id');
            const employerId = this.getAttribute('data_employer_id');
            if (window.innerWidth <= 768) {
                window.open(`job_details.php?job_id=${encodeURIComponent(jobId)}&employer_id=${encodeURIComponent(employerId)}`, '_self');
            } else {
                showDetails(
                    parseInt(jobId),
                    parseInt(employerId),
                    this
                );
            }
        });
    });

    var autoSelected = false;
    <?php if ($selectedJobId): ?>
    setTimeout(function() {
        document.querySelectorAll('.job_listings .job').forEach(function(jobDiv) {
            if (jobDiv.getAttribute('data_job_id') == '<?php echo $selectedJobId; ?>') {
                jobDiv.scrollIntoView({behavior: "smooth", block: "center"});
                jobDiv.click();
                autoSelected = true;
            }
        });
    }, 1300);
    <?php elseif ($isInitialLogin): ?>
    setTimeout(function() {
        document.querySelectorAll('.job_listings .job').forEach(function(jobDiv) {
            if (jobDiv.getAttribute('data_job_id') == '<?php echo $selectedJobId; ?>') {
                jobDiv.scrollIntoView({behavior: "smooth", block: "center"});
                jobDiv.click();
                autoSelected = true;
            }
        });
    }, 1300);
    <?php else: ?>
    setTimeout(function() {
        var firstJob = document.querySelector('.job_listings .job');
        if (firstJob) {
            firstJob.scrollIntoView({behavior: "smooth", block: "center"});
            firstJob.click();
            autoSelected = true;
        }
    }, 1300);
    <?php endif; ?>
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
        if (params.get('logout') === '1') {
            showPopup('You have been logged out successfully.', 'success');
        }
        if (params.get('login') === '1') {
            showPopup('Login successful!', 'success');
        }
    })();
</script>
<script>
    function timeSince(date) {
    if (!date) return "Unknown date";

    let safeDate = date.replace(' ', 'T');
    const parsedDate = new Date(safeDate);

    if (isNaN(parsedDate.getTime())) {
        safeDate = safeDate.replace(/-/g, '/');
        const fallbackDate = new Date(safeDate);
        if (isNaN(fallbackDate.getTime())) {
            console.error("Invalid date format:", date);
            return "Invalid date";
        }
        return timeSince(fallbackDate.toISOString());
    }

    const now = new Date();
    const seconds = Math.floor((now - parsedDate) / 1000);

    if (seconds < 0) {
        return "just now";
    }

    let interval = Math.floor(seconds / 31536000);
    if (interval >= 1) return interval + " year" + (interval === 1 ? "" : "s") + " ago";

    interval = Math.floor(seconds / 2592000);
    if (interval >= 1) return interval + " month" + (interval === 1 ? "" : "s") + " ago";

    interval = Math.floor(seconds / 86400);
    if (interval >= 1) return interval + " day" + (interval === 1 ? "" : "s") + " ago";

    interval = Math.floor(seconds / 3600);
    if (interval >= 1) return interval + " hour" + (interval === 1 ? "" : "s") + " ago";

    interval = Math.floor(seconds / 60);
    if (interval >= 1) return interval + " minute" + (interval === 1 ? "" : "s") + " ago";

    return seconds + " second" + (seconds === 1 ? "" : "s") + " ago";
}

window.onload = function() {
    document.querySelector('.loading').style.display = 'block';

    document.querySelectorAll('.job').forEach(function(jobElement) {
        const createdAt = jobElement.getAttribute('data_created_at'); 
        const jobPostedElement = jobElement.querySelector('.job_posted'); 
        jobPostedElement.innerText = timeSince(createdAt); 
    });

    document.querySelector('.loading').style.display = 'none';
};

function showDetails(jobId, employerId, element) {
    document.querySelectorAll('.job').forEach(job => {
        job.classList.remove('active');
    });

    element.classList.add('active');

    document.querySelector('.loading').style.display = 'block';

    setTimeout(() => {
        const title = element.getAttribute('data_title');
        const category = element.getAttribute('data_category');
        const salary = element.getAttribute('data_salary');
        const location = element.getAttribute('data_location');
        const createdAt = element.getAttribute('data_created_at');
        const company_name = element.getAttribute('data_company_name');
        const skills = element.getAttribute('data_skills');
        const education = element.getAttribute('data_education');
        const description = element.getAttribute('data_description');

        if (document.getElementById('job_title')) document.getElementById('job_title').innerText = title;
        if (document.getElementById('job_category')) document.getElementById('job_category').innerText = category;
        if (document.getElementById('job_salary')) document.getElementById('job_salary').innerText = salary;
        if (document.getElementById('job_location')) document.getElementById('job_location').innerText = location;
        if (document.getElementById('job_description')) document.getElementById('job_description').innerHTML = description;
        if (document.getElementById('job_company')) document.getElementById('job_company').innerText = company_name;
        if (document.getElementById('job_skills')) document.getElementById('job_skills').innerText = skills;
        if (document.getElementById('job_education')) document.getElementById('job_education').innerText = education;

        if (document.getElementById('job_title_container')) {
            document.getElementById('job_title_container').setAttribute('data_job_id_now', jobId);
        }

        const companyLink = document.getElementById('job_company');
        if (companyLink) {
            companyLink.setAttribute('onclick', `checkLoginAndViewCompany(${employerId})`);
        }

        if (document.getElementById('job_posted')) {
            document.getElementById('job_posted').innerText = timeSince(createdAt);
        }
        if (document.querySelector('.job_details')) {
            document.querySelector('.job_details').style.display = 'block';
        }

        document.querySelector('.loading').style.display = 'none';
    }, 100); 
}

function toggleMenu() {
    const navLinks = document.querySelector('.nav_links');
    navLinks.classList.toggle('show');
    if (navLinks.classList.contains('show')) {
        navLinks.style.zIndex = '1100'; 
    } else {
        navLinks.style.zIndex = '';
    }
}

const regions = {
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
        "Maguindanao", "Sulu", "Tawi-Tawi"
    ]
};


function populateCities() {
    const regionSelect = document.getElementById('region_select');
    const citySelect = document.getElementById('city_select');
    const selectedRegion = regionSelect.value;
    
    citySelect.innerHTML = ''; 

    if (selectedRegion && regions[selectedRegion]) {
        regions[selectedRegion].forEach(city => {
            const option = document.createElement('option');
            option.value = city;
            option.textContent = city;
            citySelect.appendChild(option);
        });
        citySelect.style.display = 'block';
    } else {
        citySelect.style.display = 'none';
    }
}

function openFilterModal() {
    document.getElementById('filter_modal').style.display = 'block';
}

function closeFilterModal() {
    document.getElementById('filter_modal').style.display = 'none';
}

function removeFilters() {
    window.location.href = 'index.php';
}

document.addEventListener('DOMContentLoaded', function() {
    const filterModal = document.getElementById('filter_modal');
    if (filterModal) {
        filterModal.addEventListener('mousedown', function(e) {
            if (e.target === filterModal) {
                closeFilterModal();
            }
        });
        document.addEventListener('keydown', function(e) {
            if (filterModal.style.display === 'block' && e.key === 'Escape') {
                closeFilterModal();
            }
        });
    }
});

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

    const locationFilter = "<?php echo $locationFilter; ?>";
    const citySelect = document.getElementById('city_select');
    const regionSelect = document.getElementById('region_select');

    if (locationFilter) {
        
        if (citySelect && regionSelect) {
            const cityOption = Array.from(citySelect.options).find(option => option.value === locationFilter);
            if (cityOption) {
                citySelect.value = locationFilter;
                citySelect.style.display = 'block';
            } else {
                regionSelect.value = locationFilter;
            }
        }
    }
});

function applyFilters() {
    const search = document.getElementById('search_input').value;
    const category = document.getElementById('filter_category').value;
    const salary = document.getElementById('filter_salary').value;
    const location = document.getElementById('city_select').value || document.getElementById('region_select').value;

    let queryParams = [];
    if (search) queryParams.push(`search=${encodeURIComponent(search)}`);
    if (category) queryParams.push(`category=${encodeURIComponent(category)}`);
    if (salary) queryParams.push(`salary=${encodeURIComponent(salary)}`);
    if (location) queryParams.push(`location=${encodeURIComponent(location)}`);

    const queryString = queryParams.length > 0 ? `?${queryParams.join('&')}` : '';
    window.location.href = `index.php${queryString}`;
}

document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('search_input');
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyFilters();
            }
        });
    }
});

</script>
</body>
</html>
