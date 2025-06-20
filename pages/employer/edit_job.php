<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['username'])) {
    header("Location: employee_sign_in.php");
    exit();
}
include '../../db_connection/connection.php';
$conn = OpenConnection();

$user_id = $_SESSION['user_id'];
$job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $job_id) {
    
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    $category = isset($_POST['other_category']) && $_POST['other_category'] !== '' ? trim($_POST['other_category']) : trim($_POST['category']);
    $salary = trim($_POST['salary']);
    $location = trim($_POST['location']);
    $company_name = trim($_POST['company_name']);
    $skills = trim($_POST['skills']);
    $education = trim($_POST['education']);

    $stmt = $conn->prepare("UPDATE jobs SET title=?, description=?, category=?, salary=?, location=?, company_name=?, skills=?, education=? WHERE job_id=? AND employer_id=?");
    $stmt->bind_param(
        "ssssssssii",
        $title,
        $description,
        $category,
        $salary,
        $location,
        $company_name,
        $skills,
        $education,
        $job_id,
        $user_id
    );
    $stmt->execute();
    $stmt->close();
    $conn->close();
    header("Location: view_jobs.php?updated=1");
    exit();
}


$stmt = $conn->prepare("SELECT * FROM jobs WHERE job_id=? AND employer_id=?");
$stmt->bind_param("ii", $job_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();
$stmt->close();

if (!$job) {
    $conn->close();
    header("Location: view_jobs.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Job</title>
    <link rel="stylesheet" href="css/view_applications.css">
    <style>
        .edit-job-container {
            max-width: 500px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 10px rgba(0,0,0,0.08);
            padding: 30px 30px 20px 30px;
        }
        .edit-job-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #144272;
        }
        .edit-job-container label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .edit-job-container input,
        .edit-job-container select {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: inherit;
        }
        .edit-job-container .btn {
            width: 100%;
            background: #007bff;
            color: #fff;
            border: none;
            padding: 10px 0;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .edit-job-container .btn:hover {
            background: #0056b3;
        }
        .edit-job-container .cancel-link {
            display: block;
            text-align: center;
            margin-top: 12px;
            color: #888;
            text-decoration: underline;
        }
        .salary-error {
            color: red;
            display: none;
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
</head>
<body>
    
    <div id="popupNotification" class="popup-notification">
        <span id="popupMessage"></span>
    </div>
    <div class="edit-job-container">
        <h2>Edit Job</h2>
        <form method="post" onsubmit="return validateSalaryEdit();">
            <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job['job_id']); ?>">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($job['title']); ?>" required>

            <label for="description">Description</label>
            <input type="text" name="description" id="description" value="<?php echo htmlspecialchars($job['description']); ?>">

            <label for="category">Category</label>
            <input type="text" name="category" id="category" value="<?php echo htmlspecialchars($job['category']); ?>">

            <label for="salary_display">Salary</label>
            <input type="text" id="salary_display" placeholder="₱0.00" autocomplete="off" required
                oninput="formatSalaryEditInput()" onfocus="salaryEditFocus(true)" onblur="salaryEditFocus(false)">
            <input type="number" name="salary" id="salary" value="<?php echo htmlspecialchars($job['salary']); ?>" min="0" max="100000000" step="0.01" style="display:none;" required>
            <span id="salary_edit_error" class="salary-error">Please enter a valid Salary between 0 and 100,000,000.</span>

            <label for="location">Location</label>
            <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($job['location']); ?>">

            <label for="company_name">Company Name</label>
            <input type="text" name="company_name" id="company_name" value="<?php echo htmlspecialchars($job['company_name']); ?>">

            <label for="skills">Skills</label>
            <input type="text" name="skills" id="skills" value="<?php echo htmlspecialchars($job['skills']); ?>">

            <label for="education">Education</label>
            <input type="text" name="education" id="education" value="<?php echo htmlspecialchars($job['education']); ?>">

            <button type="submit" class="btn">Update Job</button>
        </form>
        <a href="view_jobs.php" class="cancel-link">Cancel</a>
    </div>
    <script>
    function formatSalaryEditInput(isBlur) {
        var displayInput = document.getElementById("salary_display");
        var hiddenInput = document.getElementById("salary");
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
        validateSalaryEdit();
    }

    function salaryEditFocus(isFocused) {
        var displayInput = document.getElementById("salary_display");
        var salaryInput = document.getElementById("salary");
        var salaryError = document.getElementById("salary_edit_error");
        if (!isFocused) {
            salaryError.style.display = "none";
            formatSalaryEditInput(true);
        } else {
            formatSalaryEditInput(false);
            if (!salaryInput.checkValidity()) {
                salaryError.style.display = "inline";
            }
        }
    }

    function validateSalaryEdit() {
        var salaryInput = document.getElementById("salary");
        var salaryError = document.getElementById("salary_edit_error");
        var value = parseFloat(salaryInput.value);

        if (salaryInput === document.activeElement && (isNaN(value) || value < 0 || value > 100000000)) {
            salaryError.style.display = "inline";
            salaryInput.setCustomValidity("Salary must be a positive number not exceeding 100,000,000.");
            return false;
        } else {
            salaryError.style.display = "none";
            salaryInput.setCustomValidity("");
            return true;
        }
    }

    function validateSalaryEditOnSubmit() {
        formatSalaryEditInput(true);
        return validateSalaryEdit();
    }

    
    window.addEventListener('DOMContentLoaded', function() {
        var salary = document.getElementById('salary').value;
        var displayInput = document.getElementById('salary_display');
        if (salary !== '' && !isNaN(salary)) {
            let num = Math.min(parseFloat(salary), 100000000);
            displayInput.value = '₱' + Number(num).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        } else {
            displayInput.value = '';
        }
    });

    
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!validateSalaryEditOnSubmit()) {
            e.preventDefault();
        }
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
        if (params.get('updated') === '1') {
            showPopup('Job updated successfully!', 'success');
        }
    })();
    </script>
</body>
</html>
