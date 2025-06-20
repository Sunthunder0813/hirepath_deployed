<?php
session_start();
include '../../db_connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: sign_in.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    $job_seeker_id = $_SESSION['user_id'];
    $status = 'pending';
    $applied_at = date('Y-m-d H:i:s');

    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['resume']['tmp_name'];
        $file_name = basename($_FILES['resume']['name']);
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);

        $allowed_extensions = ['pdf', 'docx'];
        if (!in_array(strtolower($file_ext), $allowed_extensions)) {
            die("Invalid file type. Only PDF and DOCX files are allowed.");
        }

        $upload_dir = '../../job_seeker/resumes/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Avoid duplicating resumes by checking for existing filenames
        $base_name = pathinfo($file_name, PATHINFO_FILENAME);
        $unique_file_name = uniqid('resume_', true) . '.' . $file_ext;
        $file_path = $upload_dir . $unique_file_name;

        // Check for duplicate original filenames and append a suffix if needed
        $original_file_path = $upload_dir . $file_name;
        $counter = 1;
        $final_file_name = $file_name;
        while (file_exists($upload_dir . $final_file_name)) {
            $final_file_name = $base_name . '_' . $counter . '.' . $file_ext;
            $counter++;
        }
        $final_file_path = $upload_dir . $final_file_name;

        // Move uploaded file to the unique path (by original name with suffix if needed)
        if (move_uploaded_file($file_tmp, $final_file_path)) {
            $conn = OpenConnection();
            $stmt = $conn->prepare("INSERT INTO applications (job_id, job_seeker_id, resume_link, status, applied_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $job_id, $job_seeker_id, $final_file_path, $status, $applied_at);

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                header("Location: application_status.php?status=success");
                exit();
            } else {
                $stmt->close();
                $conn->close();
                header("Location: application_status.php?status=failure");
                exit();
            }
        } else {
            die("Failed to upload the file.");
        }
    } else {
        die("No file uploaded or an error occurred during the upload.");
    }
} else {
    die("Invalid request method.");
}
?>