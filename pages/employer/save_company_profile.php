<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: employee_sign_in.php");
    exit();
}

include '../../db_connection/connection.php'; 
$conn = OpenConnection();

$username = $_SESSION['username'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = isset($_POST['company_name']) ? mb_strtoupper($_POST['company_name']) : '';
    $company_tagline = isset($_POST['company_tagline']) ? mb_strtoupper($_POST['company_tagline']) : '';
    $company_description = htmlspecialchars($_POST['company_description']);
    $company_image = null;
    $company_cover = null;

    $query = "SELECT company_image, company_cover FROM `users` WHERE `username` = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentData = $result->fetch_assoc();
    $current_image = $currentData['company_image'];
    $current_cover = $currentData['company_cover'];

    if (isset($_FILES['company_image']) && $_FILES['company_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../static/img/company_img/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); 
        }
        $company_image = $upload_dir . basename($_FILES['company_image']['name']);
        
        if ($current_image && $current_image !== '../../static/img/company_img/default.jpg' && file_exists($current_image)) {
            unlink($current_image);
        }

        if (move_uploaded_file($_FILES['company_image']['tmp_name'], $company_image)) {
            $query = "UPDATE `users` SET `company_image` = ? WHERE `username` = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $company_image, $username);
            $stmt->execute();
        } else {
            $company_image = $current_image; 
        }
    } else {
        $company_image = $current_image;
    }

    if (isset($_FILES['company_cover']) && $_FILES['company_cover']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../static/img/company_cover/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); 
        }
        $company_cover = $upload_dir . basename($_FILES['company_cover']['name']);
        
        if ($current_cover && $current_cover !== '../../static/img/company_cover/default.jpg' && file_exists($current_cover)) {
            unlink($current_cover);
        }

        if (move_uploaded_file($_FILES['company_cover']['tmp_name'], $company_cover)) {
            $query = "UPDATE `users` SET `company_cover` = ? WHERE `username` = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $company_cover, $username);
            $stmt->execute();
        } else {
            $company_cover = $current_cover; 
        }
    } else {
        $company_cover = $current_cover;
    }

    $sql = "UPDATE `users` 
            SET `company_name` = ?, 
                `company_tagline` = ?,
                `company_image` = ?,
                `company_description` = ?,
                `company_cover` = ?
            WHERE `username` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $company_name, $company_tagline, $company_image, $company_description, $company_cover, $username);

    if ($stmt->execute()) {
        header("Location: company_profile.php?success=1");
        exit();
    } else {
        header("Location: edit_company_profile.php?error=1");
        exit();
    }
}
?>
