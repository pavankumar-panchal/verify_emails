-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 07, 2025 at 02:07 PM
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
-- Database: `email_id`
--

-- --------------------------------------------------------

--
-- Table structure for table `campaign_master`
--

CREATE TABLE `campaign_master` (
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `description` varchar(150) NOT NULL,
  `mail_subject` varchar(200) NOT NULL,
  `mail_body` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_status`
--

CREATE TABLE `campaign_status` (
  `id` int(11) NOT NULL,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `total_emails` int(11) DEFAULT 0,
  `pending_emails` int(11) DEFAULT 0,
  `sent_emails` int(11) DEFAULT 0,
  `failed_emails` int(11) DEFAULT 0,
  `status` enum('pending','running','paused','completed','failed') DEFAULT 'pending',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emails`
--

CREATE TABLE `emails` (
  `id` int(11) NOT NULL,
  `raw_emailid` varchar(255) DEFAULT NULL,
  `sp_account` varchar(100) NOT NULL,
  `sp_domain` varchar(100) NOT NULL,
  `domain_verified` tinyint(1) DEFAULT 0,
  `domain_status` tinyint(1) DEFAULT 0,
  `validation_response` text DEFAULT NULL,
  `domain_processed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exclude_accounts`
--

CREATE TABLE `exclude_accounts` (
  `id` int(11) NOT NULL,
  `account` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exclude_domains`
--

CREATE TABLE `exclude_domains` (
  `id` int(11) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mail_blaster`
--

CREATE TABLE `mail_blaster` (
  `id` int(11) NOT NULL,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `to_mail` varchar(255) DEFAULT NULL,
  `smtpid` int(11) NOT NULL,
  `delivery_date` date NOT NULL,
  `delivery_time` time NOT NULL,
  `status` enum('pending','success','failed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `attempt_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sending_logs`
--

CREATE TABLE `sending_logs` (
  `id` bigint(20) NOT NULL,
  `campaign_id` bigint(20) UNSIGNED NOT NULL,
  `email_id` int(11) NOT NULL,
  `smtp_id` int(11) NOT NULL,
  `status` enum('success','failed') NOT NULL,
  `error_message` text DEFAULT NULL,
  `smtp_response` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `smtp_servers`
--

CREATE TABLE `smtp_servers` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `host` varchar(100) NOT NULL,
  `port` int(11) NOT NULL,
  `encryption` varchar(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `daily_limit` int(11) DEFAULT 500,
  `hourly_limit` int(11) DEFAULT 100,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `smtp_servers`
--

INSERT INTO `smtp_servers` (`id`, `name`, `host`, `port`, `encryption`, `email`, `password`, `daily_limit`, `hourly_limit`, `is_active`, `created_at`) VALUES
(1, 'SMTP1', 'relyonsoft.info', 465, 'ssl', 'pavan@relyonsoft.info', '&0b1Qg31v', 500, 100, 1, '2025-04-16 03:20:13'),
(2, 'SMTP2', 'relyonsoft.tech', 465, 'ssl', 'pavan@relyonsoft.tech', '&0b1Qg31v', 500, 100, 1, '2025-04-16 03:20:13'),
(3, 'SMTP3', 'relyonmail.xyz', 465, 'ssl', 'pavan@relyonmail.xyz', '&0b1Qg31v', 500, 100, 1, '2025-04-16 03:20:13'),
(4, 'SMTP4', 'payrollsoft.in ', 465, 'ssl', 'pavan@payrollsoft.in', '&0b1Qg31v', 500, 100, 1, '2025-04-16 03:20:13'),
(5, 'SMTP5', 'payrollsoft.in ', 465, 'ssl', 'pavan@payrollsoft.in', '&0b1Qg31v', 5000, 1000, 1, '2025-04-16 03:20:13');

-- --------------------------------------------------------

--
-- Table structure for table `smtp_usage`
--

CREATE TABLE `smtp_usage` (
  `id` int(11) NOT NULL,
  `smtp_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `timestamp` datetime NOT NULL,
  `emails_sent` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `campaign_master`
--
ALTER TABLE `campaign_master`
  ADD PRIMARY KEY (`campaign_id`);

--
-- Indexes for table `campaign_status`
--
ALTER TABLE `campaign_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `campaign_id` (`campaign_id`);

--
-- Indexes for table `emails`
--
ALTER TABLE `emails`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `raw_emailid` (`raw_emailid`);

--
-- Indexes for table `exclude_accounts`
--
ALTER TABLE `exclude_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account` (`account`);

--
-- Indexes for table `exclude_domains`
--
ALTER TABLE `exclude_domains`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `domain` (`domain`);

--
-- Indexes for table `mail_blaster`
--
ALTER TABLE `mail_blaster`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `smtpid` (`smtpid`);

--
-- Indexes for table `sending_logs`
--
ALTER TABLE `sending_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_campaign_email` (`campaign_id`,`email_id`),
  ADD KEY `email_id` (`email_id`),
  ADD KEY `smtp_id` (`smtp_id`);

--
-- Indexes for table `smtp_servers`
--
ALTER TABLE `smtp_servers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `smtp_usage`
--
ALTER TABLE `smtp_usage`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `campaign_master`
--
ALTER TABLE `campaign_master`
  MODIFY `campaign_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_status`
--
ALTER TABLE `campaign_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emails`
--
ALTER TABLE `emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exclude_accounts`
--
ALTER TABLE `exclude_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exclude_domains`
--
ALTER TABLE `exclude_domains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mail_blaster`
--
ALTER TABLE `mail_blaster`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sending_logs`
--
ALTER TABLE `sending_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `smtp_servers`
--
ALTER TABLE `smtp_servers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `smtp_usage`
--
ALTER TABLE `smtp_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `campaign_status`
--
ALTER TABLE `campaign_status`
  ADD CONSTRAINT `campaign_status_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaign_master` (`campaign_id`);

--
-- Constraints for table `sending_logs`
--
ALTER TABLE `sending_logs`
  ADD CONSTRAINT `sending_logs_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaign_master` (`campaign_id`),
  ADD CONSTRAINT `sending_logs_ibfk_2` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`),
  ADD CONSTRAINT `sending_logs_ibfk_3` FOREIGN KEY (`smtp_id`) REFERENCES `smtp_servers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
