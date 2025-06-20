<?php
session_start();


if (!isset($_SESSION['username'])) {
    header("Location: employee_sign_in.php");
    exit();
}


include '../../db_connection/connection.php'; 
$conn = OpenConnection();


$username = $_SESSION['username'];


date_default_timezone_set('Asia/Manila');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $job_title = htmlspecialchars($_POST['job_title']);
    $job_description = htmlspecialchars($_POST['job_description']);
    $job_category = htmlspecialchars($_POST['final_category']); 
    $job_salary = htmlspecialchars($_POST['job_salary']);
    $job_location = htmlspecialchars($_POST['job_location']);
    $job_skills = htmlspecialchars($_POST['job_skills']);
    $job_education = htmlspecialchars($_POST['job_education']);
    $status = "pending"; 

    
    $query = "SELECT user_id, company_name FROM `users` WHERE `username` = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $employer = $result->fetch_assoc();
    $employer_id = $employer['user_id'];
    $company_name = $employer['company_name'];

    
    $created_at = date('Y-m-d H:i:s');

    
    $sql = "INSERT INTO `jobs` (`employer_id`, `title`, `description`, `category`, `salary`, `location`, `status`, `created_at`, `company_name`, `skills`, `education`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssdssssss", $employer_id, $job_title, $job_description, $job_category, $job_salary, $job_location, $status, $created_at, $company_name, $job_skills, $job_education);

    if ($stmt->execute()) {
        
        header("Location: Employee_dashboard.php?success=1");
        exit();
    } else {
        
        header("Location: post_job.php?error=1");
        exit();
    }
}
?>
