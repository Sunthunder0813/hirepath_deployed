<?php
session_start();


if (!isset($_SESSION['username'])) {
    header("Location: employee_sign_in.php");
    exit();
}

$username = htmlspecialchars($_SESSION['username']);


include '../../db_connection/connection.php'; 
$conn = OpenConnection();

$query = "SELECT company_name FROM `users` WHERE `username` = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$userDetails = $result->fetch_assoc();
$company_name = $userDetails['company_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../../static/img/icon/favicon.png" type="image/x-icon">
    <title>Post a Job</title>
    <link rel="stylesheet" href="../../static/css/post_job.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .form-container {
            width: 90%;
            max-width: 800px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-container h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: none;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 10px;
            background: #0A2647;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background:rgb(3, 18, 36);
        }

        footer {
            text-align: center;
            padding: 10px 0;
            background: #333;
            color: white;
        }

        .company-name-link {
            display: block;
            color: #0072ff;
            text-decoration: none;
            font-weight: bold;
            margin-top: 5px;
            transition: color 0.3s ease;
        }

        .company-name-link:hover {
            color: #0056b3;
            text-decoration: underline;
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
    <script src="../../static/js/get_pending_count.js" defer></script>
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
        <div class="form-container">
            <h1>Post a New Job</h1>
            <form action="save_job.php" method="POST">
                <div class="form-grid">
                    
                    <div>
                        
                        <div class="form-group">
                            <label for="job_title">Job Title:</label>
                            <input type="text" id="job_title" name="job_title" required>
                        </div>
                        <div class="form-group">
                            <label for="job_category">Category:</label>
                            <select id="job_category" name="job_category" required onchange="toggleOtherCategory(); updateFinalCategory();" required>
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
                            <input type="text" id="other_category" name="other_category" placeholder="Enter category" style="display: none; margin-top: 10px;" oninput="updateFinalCategory()">
                        </div>
                        <div class="form-group">
    <label for="job_salary_display">Salary:</label>
    <input type="text" id="job_salary_display" placeholder="₱0.00" autocomplete="off" required
        oninput="formatSalaryInput()" onfocus="salaryFocus(true)" onblur="salaryFocus(false)">
    <input type="number" id="job_salary" name="job_salary" required min="0" max="100000000" step="0.01" style="display:none;">
    <span id="salary_error" style="color: red; display: none;">Please enter a valid Salary between 0 and 100,000,000.</span>
</div>
                        <div class="form-group">
                            <label for="region">Region:</label>
                            <select id="region" name="region" required onchange="updateCities()" required>
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
    <label for="job_city">City:</label>
    <select id="job_city" name="job_city" required required>
        <option value="">Select a City</option>
        
    </select>
</div>
<input type="hidden" id="job_location" name="job_location" required>
                        <div class="form-group">
                            <label for="job_skills">Skills:</label>
                            <input type="text" id="job_skills" name="job_skills" required required>
                        </div>
                        <div class="form-group">
                            <label for="job_education">Education:</label>
                            <input type="text" id="job_education" name="job_education" required required>
                        </div>
                        <div class="form-group">
                            <label for="company_name">Company Name:</label>
                            <a href="company_profile.php" class="company-name-link"><?php echo htmlspecialchars($company_name); ?></a>
                        </div>
                        
                    </div>

                    
                    <div>
                        <div class="form-group">
                            <label for="job_description">Job Description:</label>
                            <textarea id="job_description" name="job_description" rows="30" required required></textarea>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="final_category" name="final_category" required>
                <button type="submit" class="btn">Post Job</button>
            </form>
        </div>
    </div>

    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> JobPortal. All rights reserved.</p>
    </footer>
    <script>
function toggleOtherCategory() {
    var jobCategory = document.getElementById("job_category");
    var otherCategory = document.getElementById("other_category");

    if (jobCategory.value === "Others") {
        otherCategory.style.display = "block";
        otherCategory.setAttribute("required", "true");
    } else {
        otherCategory.style.display = "none";
        otherCategory.removeAttribute("required");
        otherCategory.value = ""; 
    }
    updateFinalCategory();
}

function updateFinalCategory() {
    var jobCategory = document.getElementById("job_category");
    var otherCategory = document.getElementById("other_category");
    var finalCategory = document.getElementById("final_category");
    if (jobCategory.value === "Others" && otherCategory.value.trim() !== "") {
        finalCategory.value = otherCategory.value.trim();
    } else {
        finalCategory.value = jobCategory.value;
    }
}


document.querySelector('form').addEventListener('submit', function(e) {
    updateFinalCategory();
});

function formatSalaryInput(isBlur) {
    var displayInput = document.getElementById("job_salary_display");
    var hiddenInput = document.getElementById("job_salary");
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
    validateSalary();
}

function salaryFocus(isFocused) {
    var displayInput = document.getElementById("job_salary_display");
    var salaryInput = document.getElementById("job_salary");
    var salaryError = document.getElementById("salary_error");
    if (!isFocused) {
        salaryError.style.display = "none";
        
        formatSalaryInput(true);
    } else {
        
        formatSalaryInput(false);
        if (!salaryInput.checkValidity()) {
            salaryError.style.display = "inline";
        }
    }
}

function validateSalary() {
    var salaryInput = document.getElementById("job_salary");
    var salaryError = document.getElementById("salary_error");
    var value = parseFloat(salaryInput.value);

    if (salaryInput === document.activeElement && (isNaN(value) || value < 0 || value > 100000000)) {
        salaryError.style.display = "inline";
        salaryInput.setCustomValidity("Salary must be a positive number not exceeding 100,000,000.");
    } else {
        salaryError.style.display = "none";
        salaryInput.setCustomValidity("");
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

function updateCities() {
    const regionSelect = document.getElementById("region");
    const citySelect = document.getElementById("job_city");
    const jobLocationInput = document.getElementById("job_location");
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

    
    const updateLocation = () => {
        const selectedCity = citySelect.value;
        jobLocationInput.value = selectedRegion + (selectedCity ? `, ${selectedCity}` : "");
    };
    citySelect.removeEventListener("change", updateLocation); 
    citySelect.addEventListener("change", updateLocation);
    updateLocation(); 
}


window.addEventListener('DOMContentLoaded', function() {
    updateCities();
    
    formatSalaryInput(true);
    updateFinalCategory();
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
            if (params.get('success') === '1') {
                showPopup('Job posted successfully!', 'success');
            }
            if (params.get('error') === '1') {
                showPopup('Failed to post job. Please try again.', 'error');
            }
        })();
    </script>
</body>
</html>
