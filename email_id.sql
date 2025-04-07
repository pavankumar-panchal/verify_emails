-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 04, 2025 at 12:55 PM
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
-- Table structure for table `emails`
--

CREATE TABLE `emails` (
  `id` int(11) NOT NULL,
  `raw_emailid` varchar(255) NOT NULL,
  `sp_account` varchar(100) NOT NULL,
  `sp_domain` varchar(100) NOT NULL,
  `domain_verified` tinyint(1) DEFAULT 0,
  `domain_status` tinyint(1) DEFAULT 0,
  `validation_response` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emails`
--

INSERT INTO `emails` (`id`, `raw_emailid`, `sp_account`, `sp_domain`, `domain_verified`, `domain_status`, `validation_response`) VALUES
(14, 'abcd@gmail.com', 'abcd', 'gmail.com', 1, 0, 'SMTP failed (108.177.121.18)'),
(15, 'abcd@vsnl.net', 'abcd', 'vsnl.net', 1, 0, 'SMTP failed (3.33.130.190)'),
(16, 'abcd@dsfa.com', 'abcd', 'dsfa.com', 1, 0, 'No MX records found');

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

--
-- Dumping data for table `exclude_domains`
--

INSERT INTO `exclude_domains` (`id`, `domain`, `ip_address`) VALUES
(1, 'gmail.com', '108.177.121.18\n'),
(2, 'yahoo.com', '98.137.11.163'),
(3, 'outlook.com', '52.96.222.194'),
(4, 'hotmail.com', '204.79.197.212'),
(5, 'aol.com', '13.248.158.7'),
(6, 'icloud.com', '17.253.144.10'),
(7, 'live.com', '204.79.197.212'),
(8, 'msn.com', '204.79.197.219'),
(9, 'yahoo.co.in', '13.248.158.7'),
(10, 'yahoo.in', '76.223.84.192'),
(11, 'rediffmail.com', '202.137.235.71'),
(12, 'ymail.com', '13.248.158.7'),
(13, 'rocketmail.com', '13.248.158.7'),
(14, 'vsnl.com', '104.21.33.183'),
(15, 'vsnl.net', '3.33.130.190');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `emails`
--
ALTER TABLE `emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `exclude_accounts`
--
ALTER TABLE `exclude_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exclude_domains`
--
ALTER TABLE `exclude_domains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
