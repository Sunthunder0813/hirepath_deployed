-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 23, 2025 at 11:57 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `job_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `job_seeker_id` int(11) NOT NULL,
  `resume_link` varchar(255) NOT NULL,
  `status` enum('pending','reviewed','accepted','rejected') DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `job_id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected','inactive','freeze') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `company_name` varchar(255) NOT NULL,
  `skills` varchar(100) DEFAULT NULL,
  `education` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_type` enum('admin','client') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `company_name` varchar(255) DEFAULT NULL,
  `company_tagline` varchar(255) DEFAULT NULL,
  `company_image` varchar(255) DEFAULT NULL,
  `company_description` text DEFAULT NULL,
  `company_cover` varchar(255) DEFAULT NULL,
  `status` enum('active','blocked','processing') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `user_type`, `created_at`, `company_name`, `company_tagline`, `company_image`, `company_description`, `company_cover`, `status`) VALUES
(1, 'ADMIN', '123', 'admin@example.com', 'admin', '2025-03-24 01:40:57', NULL, NULL, NULL, NULL, NULL, 'active'),
(30, 'SANTANDER', '$2y$10$MFMh0G.5tDyL7Whu9V7kMOWjahFoq7yxcqPG57ja0P3Jz4cT6Qcou', 'santanderjoseph13@gmail.com', 'client', '2025-06-16 23:12:08', 'SANTANDER CORP', 'JOIN OUR TEAM: YOUR FUTURE STARTS HERE - EXPLORE EXCITING CAREER OPPORTUNITIES WITH US!', '../../static/img/company_img/Screenshot 2025-06-20 223648.png', 'At Santander Corp, we are committed to fostering a dynamic and inclusive work environment that empowers our employees to thrive. We believe that our people are our greatest asset, and we strive to attract top talent who share our vision of delivering exceptional financial services. With a focus on innovation and customer satisfaction, we offer a range of career opportunities across various fields, from finance and technology to customer service and operations. Our comprehensive training programs and professional development initiatives ensure that every team member has the tools and support they need to succeed.\r\n\r\nAs we continue to grow and adapt in an ever-changing financial landscape, we invite passionate individuals to join us on this exciting journey. At Santander Corp, you will not only contribute to our mission of helping customers achieve their financial goals but also be part of a collaborative culture that values diversity and creativity. Together, we can make a meaningful impact in the lives of our customers and communities. Explore our current job openings and take the first step towards a rewarding career with us!', '../../static/img/company_cover/kk.webp', 'active'),
(31, 'DAFALLA', '$2y$10$bdN3esVa/BPUVzVWYVqmr.iWMiakfLPa3R2qSkF7R8N0YafNSXl0i', 'armandodafalla726@gmail.com', 'client', '2025-06-20 14:47:55', NULL, NULL, NULL, NULL, NULL, 'active'),
(32, 'JACOB', '$2y$10$uq8g2MOtyX240XNPFYOuJuq4ttWMQLjMqkUsO9XrDOjijXwz3KO/a', 'jacobperdiguers@gmail.com', 'client', '2025-06-20 15:09:21', NULL, NULL, NULL, NULL, NULL, 'active'),
(33, 'MATUTINA', '$2y$10$DbhU1Iy95TmQczNmr9mOhuJ2h.jv7SuMLYcrJnU77Dq14CXtzIDke', 'johmarkmatutina63@gmail.com', 'client', '2025-06-20 15:31:53', NULL, NULL, NULL, NULL, NULL, 'active'),
(34, 'IVAN', '$2y$10$/BDe3dhpVyfeuv37lY.a/O9H3Be4q4qyxLCdvHJc7m8a5fWAI6v0q', 'ivanmendoza.an19@gmail.com', 'client', '2025-06-20 15:33:12', NULL, NULL, NULL, NULL, NULL, 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `job_seeker_id` (`job_seeker_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `employer_id` (`employer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`job_seeker_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
