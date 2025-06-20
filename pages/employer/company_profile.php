<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: employee_sign_in.php");
    exit();
}

$username = htmlspecialchars($_SESSION['username']);

include '../../db_connection/connection.php'; 
$conn = OpenConnection();

$query = "SELECT company_name, company_tagline, company_image, company_description, company_cover FROM `users` WHERE `username` = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$companyDetails = $result->fetch_assoc();

$company_image = file_exists($companyDetails['company_image']) ? $companyDetails['company_image'] : '../../static/img/company_img/default.jpg';
$company_cover = file_exists($companyDetails['company_cover']) ? $companyDetails['company_cover'] : '../../static/img/company_cover/default.jpg';

$force_assign = isset($_GET['assign_company']) && empty($companyDetails['company_name']);

$jobs = [];
$job_query = "SELECT `job_id`, `employer_id`, `title`, `description`, `category`, `salary`, `location`, `status`, `created_at`, `company_name`, `skills`, `education` FROM `jobs` WHERE `company_name` = ? AND `status` = 'approved' ORDER BY `created_at` DESC";
$job_stmt = $conn->prepare($job_query);
$job_stmt->bind_param("s", $username);
$job_stmt->execute();
$job_result = $job_stmt->get_result();
while ($row = $job_result->fetch_assoc()) {
    $jobs[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile</title>
    <link rel="stylesheet" href="../../static/css/company_profile.css">
    <link rel="shortcut icon" href="../../static/img/icon/favicon.png" type="image/x-icon">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: 0;
        }

        .profile-flip-wrapper {
            perspective: 2000px;
            width: 100%;
            max-width: 1400px;
            display: flex;
            justify-content: center;
            align-items: stretch;
            position: relative;
            margin-left: auto;
            margin-right: auto;
        }
        .profile-flip-inner {
            position: relative;
            width: 100%;
            max-width: 1400px;
            height: 100%;
            transition: transform 0.5s cubic-bezier(.77,0,.18,1);
            transform-style: preserve-3d;
            will-change: transform;
            left: 50%;
            transform: translateX(-50%);
        }
        .profile-flip-inner.flipped {
            transform: translateX(-50%) rotateY(180deg);
        }
        .profile-front,
        .profile-back {
            position: absolute;
            top: 0; left: 0; width: 100%;
            height: 750px;
            backface-visibility: hidden;
            max-width: 1400px;
            width: 100%;
            margin: 36px auto;
            background: linear-gradient(120deg, #0A2647 0%, #26d0ce 100%);
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.10);
            overflow: hidden;
            flex: 1;
            padding: 0;
            color: #fff;
            min-height: 450px;
            display: flex;
            flex-direction: row;
            align-items: stretch;
        }
        .profile-front {
            z-index: 2;
        }
        .profile-back {
            background: #0A2647;
            background: linear-gradient(120deg, #26d0ce 0%, #0A2647 100%);
            transform: rotateY(180deg);
            z-index: 1;
            justify-content: flex-start;
            display: flex;
            align-items: stretch;
            padding: 0;
        }
        .company-description-section {
            display: flex;
            flex-direction: row;
            width: 100%;
            height: 100%;
            padding: 0;
        }
        .company-desc-left {
            background: #16243a; 
            color: #fff;
            flex: 1.1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-top-left-radius: 22px;
            border-bottom-left-radius: 22px;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            min-width: 320px;
            max-width: 480px;
            padding: 0 0 0 0;
        }
        .company-desc-left,
        .company-desc-left * {
            color: #fff !important;
        }
        .company-desc-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .company-desc-logo img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            margin-bottom: 24px;
            margin-top: 0;
            border-radius: 18px;
            background: #f8f9fa;
            box-shadow: 0 2px 18px rgba(38,208,206,0.10);
        }
        .company-desc-acronym {
            font-size: 2em;
            color: #0A2647;
            font-weight: 700;
            letter-spacing: 0.18em;
            margin-bottom: 8px;
            margin-top: 0;
            font-family: 'Poppins', Arial, sans-serif;
        }
        .company-desc-title {
            font-size: 1.15em;
            color: #26d0ce;
            font-weight: 500;
            letter-spacing: 0.12em;
            margin-bottom: 0;
            margin-top: 0;
            font-family: 'Poppins', Arial, sans-serif;
            text-align: center;
            word-break: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            max-width: 320px;
            margin-left: auto;
            margin-right: auto;
            display: block;
        }
        .company-desc-title .company-name-word {
            display: inline-block;
            margin: 0 8px 2px 0;
            white-space: pre-line;
        }
        @media (max-width: 600px) {
            .company-desc-title {
                font-size: 1em;
                max-width: 98vw;
            }
            .company-desc-title .company-name-word {
                margin: 0 3px 2px 0;
            }
        }
        .company-desc-right {
            flex: 2; 
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: stretch;
            padding: 0;
            height: 100%;
        }
        .company-description-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            width: 100%;
            background: transparent;
            box-shadow: none;
            border-radius: 0;
            padding: 0;
            min-height: 100%;
        }
        .company-description-title {
            color: #26d0ce;
            font-size: 2.6em;
            font-weight: 900;
            letter-spacing: 2px;
            text-shadow: 0 2px 12px rgba(0,0,0,0.10);
            text-transform: uppercase;
            font-family: 'Poppins', Arial, sans-serif;
            margin-bottom: 32px;
            margin-top: 0;
            text-align: center;
        }
        .company-description-content {
            background: none;
            color: #0A2647;
            font-size: 1.25em;
            border-radius: 0;
            padding: 0;
            min-height: unset;
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            line-height: 1.8;
            letter-spacing: 0.3px;
            font-family: 'Poppins', Arial, sans-serif;
            word-break: break-word;
            overflow-wrap: break-word;
            text-align: center;
            box-shadow: none;
        }
        @media (max-width: 1100px) {
            .company-description-title {
                font-size: 2em;
            }
            .company-description-content {
                font-size: 1.1em;
                max-width: 95vw;
            }
        }
        @media (max-width: 800px) {
            .company-desc-right {
                padding: 0;
            }
            .company-description-title {
                font-size: 1.3em;
            }
        }
        @media (max-width: 1500px) {
            .profile-flip-wrapper, .profile-flip-inner {
                max-width: 100vw;
            }
        }
        #profilePrevBtn, #profileNextBtn {
            z-index: 5002 !important;
        }

        .profile-flip-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 5002 !important;
            width: 56px;
            height: 56px;
            border: none;
            border-radius: 50%;
            background: linear-gradient(120deg, #26d0ce 0%, #0A2647 100%);
            color: #fff;
            font-size: 2.2em;
            font-weight: bold;
            box-shadow: 0 4px 24px rgba(38,208,206,0.18), 0 2px 8px rgba(0,0,0,0.10);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.15s;
            outline: none;
            border: 3px solid #fff;
            opacity: 0.95;
        }
        .profile-flip-btn:hover, .profile-flip-btn:focus {
            background: linear-gradient(120deg, #0A2647 0%, #26d0ce 100%);
            color: #26d0ce;
            box-shadow: 0 8px 32px rgba(38,208,206,0.28), 0 4px 16px rgba(0,0,0,0.13);
            transform: translateY(-50%) scale(1.08);
            border-color: #26d0ce;
        }
        #profilePrevBtn::before {
            content: '';
            display: inline-block;
            width: 0;
            height: 0;
            border-top: 14px solid transparent;
            border-bottom: 14px solid transparent;
            border-right: 18px solid #fff;
            margin-right: 2px;
        }
        #profilePrevBtn {
            left: calc(45% - 740px);
            background: linear-gradient(120deg, #0A2647 0%, #26d0ce 100%);
        }
        #profilePrevBtn:hover::before, #profilePrevBtn:focus::before {
            border-right-color: #26d0ce;
        }
        #profilePrevBtn span, #profileNextBtn span {
            display: none;
        }
        #profileNextBtn::before {
            content: '';
            display: inline-block;
            width: 0;
            height: 0;
            border-top: 14px solid transparent;
            border-bottom: 14px solid transparent;
            border-left: 18px solid #fff;
            margin-left: 2px;
        }
        #profileNextBtn {
            right: calc(45% - 740px);
            background: linear-gradient(120deg, #0A2647 0%, #26d0ce 100%);
        }
        #profileNextBtn:hover::before, #profileNextBtn:focus::before {
            border-left-color: #26d0ce;
        }

        .profile-header {
            width: 100%;
            display: flex;
            align-items: center;
            position: relative;
            min-height: 320px;
            border-radius: 18px 18px 0 0;
            overflow: hidden;
            padding: 0;
        }

        .company-image-wrapper {
            position: absolute;
            top: -100px;
            right: -180px;
            width: 750px;
            height: 750px;
            border-radius:50%;
            box-shadow: 0 8px 32px rgba(0,0,0,0.10);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            z-index: 20;
        }

        .company-image-wrapper img {
            width: 700px;
            height: 700px;
            border-radius:50%;
            object-fit: cover;
            border: none;
            box-shadow: none;
            background: transparent;
        }


        .header-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 40px 40px 48px;
            z-index: 2;
            position: relative;
        }

        .company-label {
            font-size: 1.5em;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            position: absolute;
            left: 0;
            top: 0;
            background: none;
            box-shadow: none;
            z-index: 10;
            padding:16px 0 0 16px;
        }
        .company-label img {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            object-fit: contain;
            background: transparent;    
            user-select: none;    
            pointer-events: none;   
        }
        .company-label span {
            margin-left: 8px;
            font-size: 1.5em;
            font-weight: bold;
            font-family: 'Poppins', Arial, sans-serif;
            color: #26d0ce;
            letter-spacing: 1px;    
            background: none;
            box-shadow: none;
            padding: 0;
            border-radius: 0;
            display: inline-block;
            vertical-align: middle;
            user-select: none;     
        }

        .company-tagline {
            display: inline-block;
            margin-left: 18px;
            font-size: 1em;
            font-weight: 400;
            font-style: italic;
            background: rgba(0,0,0,0.18);
            padding: 3px 14px;
            border-radius: 16px;
            letter-spacing: 1px;
            vertical-align: middle;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
            max-width: 320px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
            user-select: none; 
        }

        .company-profile-title-border {
            width: 1400px;
            height: 220px;
            max-width: 100%;
            margin: 50px 0 80px 0;
            padding: 0;
            background: rgba(255,255,255,0.07);
            box-shadow: 0 2px 32px rgba(0,0,0,0.09);
            display: flex;
            align-items: center;
            padding-left: 120px;
            position: relative;
            z-index: 2;
            left: -100px;
            right: 0;
            border-radius: 0 32px 32px 0;
            overflow: hidden;
        }

        @media (max-width: 1000px) {
            .company-profile-title-border {
                width: 100%;
                height: auto;
                min-height: 120px;
                padding-left: 18px;
                border-left-width: 8px;
                border-radius: 0 18px 18px 0;
                margin-bottom: 24px;
            }
        }

        .company-profile-title {
            margin: 0;
            line-height: 1.1;
            word-break: break-word;
            white-space: normal;
            overflow-wrap: break-word;
            width: 100%;
            max-width: 600px; 
            display: block;
            
        }

        .company-main-title {
            font-size: clamp(1.5em, 5vw, 3em); 
            font-weight: 900;
            letter-spacing: 6px;
            color: #fff;
            text-shadow: 0 2px 12px rgba(0,0,0,0.13);
            word-break: break-word;
            white-space: normal;
            overflow-wrap: break-word;
            display: block;
            max-width: 100%;
            line-height: 1.1;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: uppercase;
        }

        @media (max-width: 1200px) {
            .company-profile-title {
                max-width: 400px;
            }
        }
        @media (max-width: 900px) {
            .company-profile-title {
                max-width: 100%;
            }
        }

        .edit-profile-btn {
            position: absolute;
            top: 10px;   
            right: 10px;  
            width: 38px;
            height: 38px;
            background-color: #ffcc00;
            background-image: url('../../static/img/icon/edit.png');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 24px 24px;
            border: 1.5px solid #fff;
            border-radius: 50%;
            cursor: pointer;
            transition: transform 0.2s ease, background-color 0.3s ease;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            z-index: 4000;
            pointer-events: auto !important;
        }

        .edit-profile-btn:focus {
            outline: none;
        }

        .profile-content {
            padding: 30px;
            text-align: center;
        }

        .profile-content h2 {
            font-size: 1.8em;
            margin-bottom: 15px;
            color: #333;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .profile-content p {
            font-size: 1.2em;
            color: #555;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        footer {
            text-align: center;
            padding: 10px 0;
            background: #333;
            color: white;
            margin-top: auto;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(30, 41, 59, 0.75); 
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1100;
        }

        .blur-overlay {
            filter: blur(4px);
            user-select: none;
        }

        @media (max-width: 768px) {
            .form-group {
                flex-direction: column;
            }
        }

        .jobs-section {
            margin: 40px auto 0 auto;
            max-width: 1100px;
            background: #f7f7fa;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            padding: 30px 30px 10px 30px;
        }
        .jobs-section h2 {
            text-align: left;
            color: #222;
            font-size: 1.6em;
            margin-bottom: 18px;
            font-weight: 600;
            letter-spacing: 1px;
        }
        .job-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .job-list li {
            background: #fff;
            border-radius: 8px;
            margin-bottom: 18px;
            padding: 18px 22px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .job-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #007bff;
        }
        .job-meta {
            font-size: 0.98em;
            color: #555;
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }
        .job-date {
            font-size: 0.92em;
            color: #888;
        }
        @media (max-width: 768px) {
            .jobs-section {
                padding: 18px 6px 6px 6px;
            }
            .job-list li {
                padding: 12px 8px;
            }
        }

        .company-tagline-large {
            margin-top: 0;
            font-size: 1.8em;
            font-weight: 400;
            font-style: italic;
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            padding: 10px 32px;
            border-radius: 22px;
            display: block;
            max-width: 700px;
            letter-spacing: 1.2px;
            word-break: break-word;
            white-space: normal;
            overflow-wrap: break-word;
            text-transform: uppercase;
        }

        .edit-mode-input {
            display: block;
            width: 100%;
            font-size: 2em;
            font-family: 'Poppins', Arial, sans-serif;
            font-weight: 700;
            color: #222;
            border-radius: 18px;
            padding: 24px 28px;
            margin: 10px 0 0 0;
            box-shadow: 0 2px 18px rgba(38,208,206,0.10);
            outline: none;
            background: rgba(255,255,255,0.18);
            border: 2.5px solid #26d0ce;
            transition: background 0.2s, box-shadow 0.2s, border 0.2s;
            text-transform: uppercase;
        }
        .edit-mode-input:focus {
            border-color: #0A2647;
            background: rgba(255,255,255,0.28);
            box-shadow: 0 0 0 4px #26d0ce33;
        }
        .edit-mode-tagline {
            font-size: 1.3em;
            font-style: italic;
            font-weight: 500;
            color: #444;
            background: rgba(255,255,255,0.13);
            padding-top: 32px;
            padding-bottom: 32px;
            padding-left: 28px;
            padding-right: 28px;
            width: 60%;
            max-width: 600px;
            min-width: 220px;
            margin-left: 25px;
            margin-right: 0;
            margin-top: 10px;
            margin-bottom: 0;
            box-sizing: border-box;
            text-align: left;
            border-radius: 18px;
            border: 2.5px solid #26d0ce;
            display: block;
            min-height: 110px;
            height: 140px;
            resize: vertical;
            white-space: pre-wrap;
            word-break: break-word;
            overflow-wrap: break-word;
            text-transform: uppercase;
        }
        .edit-mode-textarea {
            width: 100%;
            max-width: 900px;
            min-width: 220px;
            min-height: 180px;
            height: 220px;
            font-size: 1.2em;
            font-family: 'Poppins', Arial, sans-serif;
            border-radius: 18px;
            border: 2.5px solid #26d0ce;
            background: rgba(255,255,255,0.13);
            color: #222;
            padding: 32px;
            margin-top: 0;
            margin-bottom: 0;
            box-sizing: border-box;
            resize: vertical;
            outline: none;
            transition: border 0.2s, background 0.2s;
        }
        .edit-mode-textarea:focus {
            border-color: #0A2647;
            background: rgba(255,255,255,0.18);
        }
        .company-profile-title,
        .company-tagline-large {
            margin-left: 0;
            margin-right: 0;
        }
        @media (max-width: 800px) {
            .edit-mode-input,
            .edit-mode-tagline,
            .edit-mode-wide {
                max-width: 98vw;
                width: 98vw;
            }
        }

        .img-edit-hover {
            transition: box-shadow 0.2s, filter 0.2s;
            cursor: pointer !important;
            box-shadow: 0 0 0 0 #26d0ce;
            position: relative;
        }
        .img-edit-hover:hover {
            box-shadow: 0 0 0 4px #26d0ce, 0 2px 16px rgba(38,208,206,0.18);
            filter: brightness(1.08) saturate(1.2);
            outline: none;
        }
        .img-edit-hover:hover::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(38,208,206,0.18);
            border-radius: 50%;
            z-index: 2;
            pointer-events: none;
        }
        .img-edit-hover:hover::before {
            content: '';
            position: absolute;
            left: 50%; top: 50%;
            width: 38px; height: 38px;
            background: url('../../static/img/icon/edit.png') no-repeat center center;
            background-size: 28px 28px;
            transform: translate(-50%, -50%);
            opacity: 0.85;
            z-index: 3;
            pointer-events: none;
        }

        .force-assign-indicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            z-index: 5000;
            background: #fff3cd;
            color: #856404;
            font-size: 1.15em;
            font-weight: bold;
            text-align: center;
            padding: 18px 10px 16px 10px;
            box-shadow: 0 4px 24px rgba(38, 208, 206, 0.13);
            letter-spacing: 1px;
            border-bottom: 3px solid #ffeeba;
            border-top: 0;
            pointer-events: none;
            font-family: 'Poppins', Arial, sans-serif;
        }
        .force-assign-indicator svg {
            display: inline-block;
            vertical-align: middle;
            margin-right: 10px;
        }

        .edit-btn-pos {
            position: absolute;
            top: 18px;
            right: 18px;
            z-index: 30;
        }

        .edit-mode-indicator {
            display: none;
            position: absolute;
            top: 18px;
            left: 50%;
            transform: translateX(-50%);
            background: #ffcc00;
            color: #222 !important; 
            font-weight: bold;
            padding: 6px 22px;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
            z-index: 31;
            letter-spacing: 1px;
        }

        .acronym-label {
            margin-left: 12px;
            font-size: 1.2em;
            font-weight: bold;
            font-family: 'Poppins',Arial,sans-serif;
            color: #26d0ce;
            letter-spacing: 0.2em;
            background: none;
            box-shadow: none;
            padding: 0;
            border-radius: 0;
            display: inline-block;
            vertical-align: middle;
            user-select: none;
        }

        .company-img-cursor {
            cursor: pointer;
        }

        .company-cover-cursor {
            cursor: pointer;
        }

        .edit-mode-input-align {
            text-align: left;
        }

        .overview-top {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
            height: 100%;
            padding: 48px 48px 0 48px; 
        }
        .overview-label-horizontal {
            font-size: 2.6em;
            font-weight: 900;
            color: #26ffe6;
            letter-spacing: 0.18em;
            margin-bottom: 24px;
            text-shadow:
                0 0 8px #26ffe6,
                0 0 16px #26d0ce,
                0 0 32px #0A2647,
                0 2px 12px rgba(0,0,0,0.10);
            text-transform: uppercase;
            font-family: 'Poppins', Arial, sans-serif;
            text-align: center;
            width: 100%;
            white-space: nowrap;
        }
        .overview-content {
            color: #fff;
            font-size: 1.35em;
            margin-top: 0;
            line-height: 1.8;
            letter-spacing: 0.3px;
            font-family: 'Poppins', Arial, sans-serif;
            word-break: break-word;
            overflow-wrap: break-word;
            text-align: left;
            max-width: 100%; 
            width: 100%;
            flex: 1 1 auto;
            display: block;
            box-sizing: border-box;
            overflow-y: auto;
            max-height: calc(100vh - 100px); 
            scrollbar-width: thin;
            scrollbar-color: #26d0ce #0A2647;
        }
        .overview-content::-webkit-scrollbar {
            width: 10px;
            background: #0A2647;
            border-radius: 8px;
        }
        .overview-content::-webkit-scrollbar-thumb {
            background: linear-gradient(120deg, #26d0ce 0%, #0A2647 100%);
            border-radius: 8px;
            min-height: 40px;
        }
        .overview-content::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(120deg, #0A2647 0%, #26d0ce 100%);
        }
        @media (max-width: 1100px) {
            .overview-top {
                padding: 18px 12px 0 12px;
            }
            .overview-label-horizontal {
                font-size: 1.5em;
                margin-bottom: 12px;
            }
            .overview-content {
                font-size: 1.1em;
            }
        }
        @media (max-width: 800px) {
            .overview-top {
                padding: 8px 4px 0 4px;
            }
            .overview-label-horizontal {
                font-size: 1.1em;
            }
        }

        .edit-mode-active .company-main-title,
        .edit-mode-active .company-tagline-large,
        .edit-mode-active .overview-content,
        .edit-mode-active .company-desc-title,
        .edit-mode-active .company-desc-acronym {
            color: #fff !important;
        }
        
        @media (max-width: 900px) {
            .profile-front:not(.edit-mode-active) {
                flex-direction: column !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 8px 0 !important;
                border-radius: 8px !important;
                align-items: stretch !important;
            }
            .profile-header {
                flex-direction: column !important;
                min-height: unset !important;
                padding: 0 !important;
            }
            .company-image-wrapper {
                position: static !important;
                width: 100vw !important;
                height: 120px !important;
                border-radius: 0 !important;
                margin: 0 auto 8px auto !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
            }
            .company-image-wrapper img {
                width: 100vw !important;
                height: 120px !important;
                border-radius: 0 !important;
                object-fit: cover !important;
            }
            .header-content {
                padding: 8px 2vw 8px 2vw !important;
            }
            .company-profile-title-border {
                width: 100vw !important;
                min-height: 40px !important;
                padding-left: 0 !important;
                left: 0 !important;
                border-radius: 0 0 8px 8px !important;
                margin: 0 0 8px 0 !important;
            }
            .company-profile-title {
                max-width: 95vw !important;
            }
            .company-main-title {
                font-size: 1em !important;
                letter-spacing: 1px !important;
            }
            .company-tagline-large {
                font-size: 0.9em !important;
                padding: 4px 4px !important;
                max-width: 95vw !important;
            }
        }
        @media (max-width: 600px) {
            .company-image-wrapper {
                height: 60px !important;
            }
            .company-image-wrapper img {
                height: 60px !important;
            }
            .company-profile-title {
                max-width: 90vw !important;
            }
            .company-main-title {
                font-size: 0.8em !important;
            }
            .company-tagline-large {
                font-size: 0.7em !important;
                padding: 2px 4px !important;
                max-width: 90vw !important;
            }
        }
        @media (max-width: 400px) {
            .company-main-title,
            .company-profile-title,
            .company-tagline-large {
                font-size: 0.7em !important;
                max-width: 98vw !important;
            }
            .company-desc-title {
                font-size: 0.7em !important;
            }
        }
        
        @media (max-width: 600px) {
            #profilePrevBtn, #profileNextBtn {
                display: none !important;
            }
        }
        
        @media (max-width: 600px) {
            nav.navbar {
                flex-direction: column;
                padding: 4px 0 !important;
            }
            nav.navbar .logo {
                font-size: 1em !important;
                padding: 2px 0 !important;
            }
            nav.navbar .nav-links {
                flex-direction: column;
                gap: 2px !important;
            }
            nav.navbar .nav-links li {
                margin: 0 !important;
            }
            footer.footer {
                font-size: 0.8em !important;
                padding: 4px 0 !important;
            }
        }
        
        html, body {
            max-width: 100vw;
            overflow-x: hidden;
        }
        .required-exclaim {
            display: none !important;
        }
        .required-label {
            display: none !important;
        }
        .edit-shortcut-indicator {
            display: block;
            position: absolute;
            top: 52px;
            right: 18px;
            background: #fffbe7;
            color: #0A2647;
            font-size: 0.98em;
            font-weight: 500;
            border-radius: 12px;
            padding: 6px 16px;
            box-shadow: 0 2px 8px rgba(38,208,206,0.10);
            z-index: 4100;
            border: 1.5px solid #ffcc00;
            letter-spacing: 0.5px;
            opacity: 0;
            transition: opacity 0.7s;
        }
        .edit-shortcut-indicator.visible {
            opacity: 0.95;
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
    </style>
    
</head>
<body>
<nav class="navbar<?php if ($force_assign) echo ' blur-overlay'; ?>">
    <a href="Employee_dashboard.php" class="logo">Employee Portal</a>
    <ul class="nav-links">
        <li><a href="post_job.php" <?php if ($force_assign) echo 'class="disabled-link" tabindex="-1" aria-disabled="true"'; ?>>Post Job</a></li>
        <li>
            <div class="applications-container">
                <a href="view_applications.php" <?php if ($force_assign) echo 'class="disabled-link" tabindex="-1" aria-disabled="true"'; ?>>Applications</a>
                <span id="navbar-badge" class="nav-badge" style="display: none;">0</span>
            </div>
        </li>
        <li><a href="view_jobs.php">View Jobs</a></li>  
        <li><a href="company_profile.php" class="active">Company Profile</a></li>
        <li><a href="../../logout.php" <?php if ($force_assign) echo 'class="disabled-link" tabindex="-1" aria-disabled="true"'; ?>>Logout</a></li>
    </ul>
</nav>

<?php if ($force_assign): ?>
    <div id="forceAssignIndicator" class="force-assign-indicator">
        <span>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#856404"><circle cx="12" cy="12" r="10" fill="#ffeeba"/><path d="M12 7v5m0 4h.01" stroke="#856404" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        You must assign your company profile before using the portal. Please complete your company profile below to continue.
    </div>
<?php endif; ?>

<button id="profilePrevBtn" class="profile-flip-btn"></button>
<div class="profile-flip-wrapper">
    <div class="profile-flip-inner" id="profileFlipInner">
        <div class="profile-front" id="profileContainer">
            <div class="profile-header">
                <button class="edit-profile-btn edit-btn-pos" id="editModeBtn"
                    title="Edit Company Profile (Ctrl+Alt+E)"
                    onclick="enableEditMode(event)">
                </button>
                <div id="editModeIndicator" class="edit-mode-indicator">
                    Edit Mode
                </div>
                <div id="editShortcutIndicator" class="edit-shortcut-indicator">
                    Press <b>Ctrl+Alt+E</b> to edit, <b>Ctrl+Alt+S</b> to save
                </div>
                <div class="company-label">
                    <div style="position:relative;display:inline-block;">
                        <img id="companyImage" src="<?php echo $company_image ?: '../../static/img/company_img/default.jpg'; ?>" alt="Company Logo" draggable="false" class="company-img-cursor">
                        <input type="file" id="companyImageInput" name="company_image" accept="image/*" style="display:none;">
                    </div>
                    <?php
                    $company_name = $companyDetails['company_name'] ?? '';
                    $words = preg_split('/\s+/', trim($company_name));
                    $acronym = '';
                    foreach ($words as $w) {
                        if ($w !== '' && isset($w[0]) && ctype_alpha($w[0])) {
                            $acronym .= strtoupper($w[0]) . '.';
                        }
                    }
                    $acronym = rtrim($acronym, '.'); 
                    ?>
                    <span class="acronym-label" id="acronymLabel">
                        <?php echo htmlspecialchars($acronym); ?>
                    </span>
                </div>
                <div class="company-image-wrapper company-image-large">
                    <img id="companyCover" src="<?php echo $company_cover ?: '../../static/img/company_cover/default.jpg'; ?>" alt="Company Cover" class="company-cover-cursor">
                    <input type="file" id="companyCoverInput" name="company_cover" accept="image/*" style="display:none;">
                </div>
                <div class="header-content">
                    <div class="company-profile-title-border">
                        <div class="company-profile-title">
                            <span class="company-main-title" id="companyNameLabel">
                                <?php echo htmlspecialchars($companyDetails['company_name']) ?: 'Enter Company Name'; ?>
                            </span>
                            <input type="text" id="companyNameInput" 
                                value="<?php echo htmlspecialchars($companyDetails['company_name']); ?>"
                                class="edit-mode-input edit-mode-wide edit-mode-input-align"
                                style="display:none;"
                                placeholder="Enter Company Name"
                            >
                        </div>
                    </div>
                    <div class="company-tagline-large<?php echo empty($companyDetails['company_tagline']) ? ' company-tagline-label-empty' : ''; ?>" id="companyTaglineLabel">
                        <?php echo htmlspecialchars($companyDetails['company_tagline']) ?: 'Enter Company Tagline'; ?>
                    </div>
                    <textarea id="companyTaglineInput"
                        class="edit-mode-input edit-mode-tagline edit-mode-wide edit-mode-input-align"
                        style="display:none;"
                        placeholder="Enter Company Tagline"><?php echo htmlspecialchars($companyDetails['company_tagline']); ?></textarea>
                </div>
            </div>
        </div>
        <div class="profile-back" id="profileBackContainer">
            <div class="company-description-section">
                <div class="company-desc-left">
                    <div id="editModeIndicatorBack" class="edit-mode-indicator" style="display:none;">
                        Edit Mode
                    </div>
                    <div class="company-desc-logo">
                        <img id="descCompanyImage" src="<?php echo $company_image ?: '../../static/img/company_img/default.jpg'; ?>" alt="Company Logo">
                        <div class="company-desc-acronym" id="descCompanyAcronym">
                            <?php
                            $company_name = $companyDetails['company_name'] ?? '';
                            $words = preg_split('/\s+/', trim($company_name));
                            $acronym = '';
                            foreach ($words as $w) {
                                if ($w !== '' && isset($w[0]) && ctype_alpha($w[0])) {
                                    $acronym .= strtoupper($w[0]);
                                }
                            }
                            echo htmlspecialchars($acronym);
                            ?>
                        </div>
                        <div class="company-desc-title" id="descCompanyTitle">
                            <?php
                            $company_name = $companyDetails['company_name'] ?? '';
                            $words = preg_split('/\s+/', trim($company_name));
                            foreach ($words as $w) {
                                if ($w !== '') {
                                    echo '<span class="company-name-word">' . htmlspecialchars($w) . '</span>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="company-desc-right">
                    <div class="overview-top">
                        <div class="overview-label-horizontal">
                            O&nbsp;V&nbsp;E&nbsp;R&nbsp;V&nbsp;I&nbsp;E&nbsp;W
                        </div>
                        <div id="companyDescriptionDisplay" class="overview-content" style="display: block;">
                            <?php echo nl2br(htmlspecialchars($companyDetails['company_description'] ?? 'No overview provided.')); ?>
                        </div>
                        <textarea id="companyDescriptionInput"
                            class="edit-mode-textarea"
                            style="display:none;"
                            placeholder="Enter Company Overview"><?php echo htmlspecialchars($companyDetails['company_description']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<button id="profileNextBtn" class="profile-flip-btn"></button>
<script src="../../static/js/get_pending_count.js" defer></script>
<script>

document.addEventListener('DOMContentLoaded', function() {
    var shortcutDiv = document.getElementById('editShortcutIndicator');
    if (shortcutDiv) {
        shortcutDiv.innerHTML = 'Press <b>Ctrl+Alt+E</b> to edit, <b>Ctrl+Alt+S</b> to save';
        shortcutDiv.classList.remove('visible');
        let fadeInterval = setInterval(function() {
            shortcutDiv.classList.add('visible');
            setTimeout(function() {
                shortcutDiv.classList.remove('visible');
            }, 2000); 
        }, 5000);
        
        setTimeout(function() {
            shortcutDiv.classList.add('visible');
            setTimeout(function() {
                shortcutDiv.classList.remove('visible');
            }, 2000);
        }, 200);
        
        window.addEventListener('beforeunload', function() {
            clearInterval(fadeInterval);
        });
    }
});

function enableEditMode(e) {
    document.getElementById('editModeIndicator').style.display = 'block';
    var editModeIndicatorBack = document.getElementById('editModeIndicatorBack');
    if (editModeIndicatorBack) editModeIndicatorBack.style.display = 'block';

    
    var shortcutDiv = document.getElementById('editShortcutIndicator');
    if (shortcutDiv) {
        shortcutDiv.innerHTML = 'Press <b>Ctrl+Alt+S</b> to save changes';
        shortcutDiv.classList.remove('visible');
        let fadeInterval = setInterval(function() {
            shortcutDiv.classList.add('visible');
            setTimeout(function() {
                shortcutDiv.classList.remove('visible');
            }, 2000);
        }, 5000);
        setTimeout(function() {
            shortcutDiv.classList.add('visible');
            setTimeout(function() {
                shortcutDiv.classList.remove('visible');
            }, 2000);
        }, 200);
        window.addEventListener('beforeunload', function() {
            clearInterval(fadeInterval);
        });
    }

    var container = document.getElementById('profileContainer');
    if (container.classList.contains('blur-overlay')) {
        container.classList.remove('blur-overlay');
    }

    var editBtn = document.getElementById('editModeBtn');
    editBtn.innerHTML = '<img src="../../static/img/icon/save.png" alt="Save" style="width:24px;height:24px;vertical-align:middle;">';
    editBtn.title = 'Save Changes (Ctrl+Alt+S)';
    editBtn.onclick = function() { saveProfileChanges(); };
    editBtn.style.backgroundImage = 'none';
    editBtn.style.backgroundColor = '#22c55e';

    document.getElementById('companyImage').style.pointerEvents = 'auto';
    document.getElementById('companyImage').onclick = function() {
        document.getElementById('companyImageInput').click();
    };
    document.getElementById('companyImageInput').onchange = function(e) {
        if (e.target.files && e.target.files[0]) {
            var reader = new FileReader();
            reader.onload = function(ev) {
                document.getElementById('companyImage').src = ev.target.result;
                document.getElementById('descCompanyImage').src = ev.target.result;
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    };
    document.getElementById('companyImage').classList.add('img-edit-hover');

    document.getElementById('companyCover').style.pointerEvents = 'auto';
    document.getElementById('companyCover').onclick = function() {
        document.getElementById('companyCoverInput').click();
    };
    document.getElementById('companyCoverInput').onchange = function(e) {
        if (e.target.files && e.target.files[0]) {
            var reader = new FileReader();
            reader.onload = function(ev) {
                document.getElementById('companyCover').src = ev.target.result;
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    };
    document.getElementById('companyCover').classList.add('img-edit-hover');

    var nameInput = document.getElementById('companyNameInput');
    var taglineInput = document.getElementById('companyTaglineInput');
    var descInput = document.getElementById('companyDescriptionInput');

    document.getElementById('companyNameLabel').style.display = 'none';
    nameInput.style.display = 'block';
    nameInput.oninput = function(e) {
        document.getElementById('companyNameLabel').textContent = e.target.value;
        updateAcronymLabelRealtime();
        updateDescLeftRealtime();
    };

    var taglineLabel = document.getElementById('companyTaglineLabel');
    if (taglineLabel && taglineInput) {
        taglineLabel.style.display = 'none';
        taglineInput.style.display = 'block';
        taglineInput.value = taglineLabel.textContent.trim();
        taglineInput.oninput = function(e) {
            taglineLabel.textContent = e.target.value;
        };
    }

    var descDisplay = document.getElementById('companyDescriptionDisplay');
    if (descDisplay && descInput) {
        descDisplay.style.display = 'none';
        descInput.style.display = 'block';
        descInput.value = descDisplay.innerText.trim();
        descInput.oninput = function(e) {
            descDisplay.innerText = e.target.value;
        };
    }
}


document.addEventListener('DOMContentLoaded', function() {
    var shortcutDiv = document.getElementById('editShortcutIndicator');
    if (shortcutDiv) {
        shortcutDiv.innerHTML = 'Press <b>Ctrl+Alt+E</b> to edit, <b>Ctrl+Alt+S</b> to save';
        shortcutDiv.classList.remove('visible');
        let fadeInterval = setInterval(function() {
            shortcutDiv.classList.add('visible');
            setTimeout(function() {
                shortcutDiv.classList.remove('visible');
            }, 2000); 
        }, 2000);
        
        setTimeout(function() {
            shortcutDiv.classList.add('visible');
            setTimeout(function() {
                shortcutDiv.classList.remove('visible');
            }, 2000);
        }, 200);
        
        window.addEventListener('beforeunload', function() {
            clearInterval(fadeInterval);
        });
    }
});

function saveProfileChanges() {
    var formData = new FormData();

    var companyName = document.getElementById('companyNameInput').value;
    var companyTagline = document.getElementById('companyTaglineInput') ? document.getElementById('companyTaglineInput').value : '';
    var companyImageFile = document.getElementById('companyImageInput').files[0];
    var companyCoverFile = document.getElementById('companyCoverInput').files[0];

    formData.append('company_name', companyName);
    formData.append('company_tagline', companyTagline);
    if (companyImageFile) formData.append('company_image', companyImageFile);
    if (companyCoverFile) formData.append('company_cover', companyCoverFile);

    var descInput = document.getElementById('companyDescriptionInput');
    if (descInput && descInput.style.display !== 'none') {
        formData.append('company_description', descInput.value);
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'save_company_profile.php', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            location.reload();
        } else {
            alert('Failed to save changes.');
        }
    };
    xhr.onerror = function() {
        alert('AJAX request failed.');
    };
    xhr.send(formData);
}

document.addEventListener('DOMContentLoaded', function() {
    var flipInner = document.getElementById('profileFlipInner');
    var btnPrev = document.getElementById('profilePrevBtn');
    var btnNext = document.getElementById('profileNextBtn');

    btnNext.addEventListener('click', function() {
        flipInner.classList.add('flipped');
        btnNext.style.display = 'none';
        btnPrev.style.display = '';
    });
    btnPrev.addEventListener('click', function() {
        flipInner.classList.remove('flipped');
        btnPrev.style.display = 'none';
        btnNext.style.display = '';
    });

    flipInner.classList.remove('flipped');
    btnPrev.style.display = 'none'; 
    btnNext.style.display = '';    

    document.addEventListener('keydown', function(e) {
        var active = document.activeElement;
        if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA')) return;

        if ((e.key === 'ArrowRight' || e.key === 'Right') && btnNext.style.display !== 'none') {
            btnNext.click();
        }
        if ((e.key === 'ArrowLeft' || e.key === 'Left') && btnPrev.style.display !== 'none') {
            btnPrev.click();
        }
    });
});

document.addEventListener('keydown', function(e) {
    var active = document.activeElement;
    if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA')) return;

    if (e.ctrlKey && e.altKey && (e.key === 'E' || e.key === 'e')) {
        e.preventDefault();
        var editBtn = document.getElementById('editModeBtn');
        if (editBtn && editBtn.title === 'Edit Company Profile (Ctrl+Alt+E)') {
            editBtn.click();
        }
    }
    if (e.ctrlKey && e.altKey && (e.key === 'S' || e.key === 's')) {
        e.preventDefault();
        var editBtn = document.getElementById('editModeBtn');
        if (editBtn && editBtn.title === 'Save Changes (Ctrl+Alt+S)') {
            editBtn.click();
        }
    }
});

function updateDescLeftRealtime() {
    var imgFront = document.getElementById('companyImage');
    var imgBack = document.getElementById('descCompanyImage');
    if (imgFront && imgBack) {
        imgBack.src = imgFront.src;
    }
    var nameInput = document.getElementById('companyNameInput');
    var titleBack = document.getElementById('descCompanyTitle');
    var acronymBack = document.getElementById('descCompanyAcronym');
    if (nameInput && titleBack && acronymBack) {
        var name = nameInput.value.trim();
        var words = name.split(/\s+/);
        var html = '';
        for (var i = 0; i < words.length; i++) {
            if (words[i]) {
                html += '<span class="company-name-word">' + words[i] + '</span>';
            }
        }
        titleBack.innerHTML = html;
        var acronym = '';
        for (var i = 0; i < words.length; i++) {
            if (words[i] && /^[A-Za-z]/.test(words[i])) {
                acronym += words[i][0].toUpperCase() + '.';
            }
        }
        acronymBack.textContent = acronym.replace(/\.$/, '');
    }
}

function updateAcronymLabelRealtime() {
    var nameInput = document.getElementById('companyNameInput');
    var acronymLabel = document.getElementById('acronymLabel');
    if (nameInput && acronymLabel) {
        var name = nameInput.value.trim();
        var words = name.split(/\s+/);
        var acronym = '';
        for (var i = 0; i < words.length; i++) {
            if (words[i] && /^[A-Za-z]/.test(words[i])) {
                acronym += words[i][0].toUpperCase() + '.';
            }
        }
        acronym = acronym.replace(/\.$/, ''); 
        acronymLabel.textContent = acronym;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var nameInput = document.getElementById('companyNameInput');
    if (nameInput) nameInput.addEventListener('input', updateAcronymLabelRealtime);
    updateAcronymLabelRealtime();
});

document.addEventListener('DOMContentLoaded', function() {
    var nameInput = document.getElementById('companyNameInput');
    var imgInput = document.getElementById('companyImageInput');
    if (nameInput) nameInput.addEventListener('input', updateDescLeftRealtime);
    if (imgInput) imgInput.addEventListener('change', function() {
        setTimeout(updateDescLeftRealtime, 100);
    });
    updateDescLeftRealtime();
});

window.onload = function() {
    <?php if ($force_assign): ?>
        enableEditMode();
        document.querySelectorAll('.navbar').forEach(function(nav) {
            nav.classList.add('blur-overlay');
        });
        document.querySelectorAll('.nav-links a:not(.active)').forEach(function(link) {
            link.classList.add('disabled-link');
            link.setAttribute('tabindex', '-1');
            link.setAttribute('aria-disabled', 'true');
        });
        window.onbeforeunload = function() {
            return "Please complete your company profile before leaving this page.";
        };
        setTimeout(function() {
            var input = document.getElementById('companyNameInput');
            if (input) input.focus();
        }, 300);
    <?php endif; ?>
};
</script>

<footer class="footer">
    <p>&copy; <?php echo date("Y"); ?> JobPortal. All rights reserved.</p>
</footer>
</body>
</html>