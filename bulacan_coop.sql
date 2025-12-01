-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2025 at 06:49 AM
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
-- Database: `bulacan_coop`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `a_username` varchar(50) NOT NULL,
  `a_password_hash` varchar(255) NOT NULL,
  `a_fullname` varchar(150) DEFAULT NULL,
  `a_role` enum('SuperAdmin','Manager','Staff') DEFAULT 'Staff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `a_username`, `a_password_hash`, `a_fullname`, `a_role`) VALUES
(1, 'admin', '$2y$10$dnoKhhy0zpsdCjKuCENLAuGrLpnzqZ1ljO2EG2e00EqvCmJNLDVNO', 'Super Admin', 'SuperAdmin');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `client_id` int(11) NOT NULL,
  `member_id` varchar(20) NOT NULL COMMENT 'Unique identifier for login (username)',
  `c_firstname` varchar(100) NOT NULL,
  `c_lastname` varchar(100) NOT NULL,
  `c_email` varchar(100) DEFAULT NULL,
  `c_phone` varchar(20) NOT NULL,
  `c_address` text DEFAULT NULL,
  `c_branch` enum('malolos','hagonoy','calumpit','balagtas','marilao','staMaria','plaridel') NOT NULL,
  `c_password_hash` varchar(255) NOT NULL,
  `c_status` enum('Active','Suspended','Deactivated') DEFAULT 'Active',
  `loan_count` int(11) NOT NULL DEFAULT 0,
  `date_joined` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `collectors`
--

CREATE TABLE `collectors` (
  `collector_id` int(11) NOT NULL,
  `col_username` varchar(50) NOT NULL,
  `col_password_hash` varchar(255) NOT NULL,
  `col_fullname` varchar(150) NOT NULL,
  `col_branch` enum('malolos','hagonoy','calumpit','balagtas','marilao','staMaria','plaridel') NOT NULL,
  `col_status` enum('Active','Inactive') DEFAULT 'Active',
  `date_registered` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `loan_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `loan_amount` decimal(10,2) NOT NULL,
  `processing_fee` decimal(10,2) DEFAULT 200.00,
  `net_amount` decimal(10,2) DEFAULT 0.00,
  `interest_rate` decimal(5,2) NOT NULL DEFAULT 15.00,
  `term_days` int(11) NOT NULL DEFAULT 100,
  `daily_payment` decimal(10,2) NOT NULL,
  `total_balance` decimal(10,2) NOT NULL,
  `current_balance` decimal(10,2) NOT NULL,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `next_payment_date` date DEFAULT NULL,
  `loan_status` enum('Pending','Active','Paid','Declined','Overdue') DEFAULT 'Pending',
  `days_paid` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `collector_id` int(11) DEFAULT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(50) DEFAULT 'Cash',
  `payment_type` enum('daily','partial','full') DEFAULT 'daily'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `a_username` (`a_username`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`),
  ADD UNIQUE KEY `member_id` (`member_id`),
  ADD UNIQUE KEY `c_email` (`c_email`);

--
-- Indexes for table `collectors`
--
ALTER TABLE `collectors`
  ADD PRIMARY KEY (`collector_id`),
  ADD UNIQUE KEY `col_username` (`col_username`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`loan_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `collector_id` (`collector_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `collectors`
--
ALTER TABLE `collectors`
  MODIFY `collector_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `loan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`loan_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`),
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`collector_id`) REFERENCES `collectors` (`collector_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
