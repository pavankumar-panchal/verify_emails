-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 16, 2025 at 05:12 PM
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
  `validation_response` text DEFAULT NULL,
  `domain_processed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emails`
--

INSERT INTO `emails` (`id`, `raw_emailid`, `sp_account`, `sp_domain`, `domain_verified`, `domain_status`, `validation_response`, `domain_processed`) VALUES
(1, '007_ikku@gmail.com', '007_ikku', 'gmail.com', 1, 0, 'Invalid response', 1),
(3, '00ar.khan@gmail.com', '00ar.khan', 'gmail.com', 1, 1, '173.194.202.27', 1),
(4, '0107sjain@gmail.com', '0107sjain', 'gmail.com', 1, 1, '142.251.10.27', 1),
(5, '0202santu@gmail.com', '0202santu', 'gmail.com', 1, 1, '173.194.202.27', 1),
(6, '03580.hr@fourpoints.com', '03580.hr', 'fourpoints.com', 1, 0, 'Invalid response', 1),
(7, '05rohitmishra@gmail.com', '05rohitmishra', 'gmail.com', 1, 1, '173.194.202.27', 1),
(8, '08pradeepyadavspn@gmail.com', '08pradeepyadavspn', 'gmail.com', 1, 1, '142.251.10.27', 1),
(9, '1008harsh1@gmail.com', '1008harsh1', 'gmail.com', 1, 1, '142.250.141.27', 1),
(10, '100plusproperties.hyd@gmail.com', '100plusproperties.hyd', 'gmail.com', 1, 1, '142.250.141.27', 1),
(11, '101a@a.com', '101a', 'a.com', 1, 0, 'Invalid responce', 0),
(12, '11@gmail.com', '11', 'gmail.com', 1, 0, 'Invalid response', 0),
(13, '111.svtax@gmail.com', '111.svtax', 'gmail.com', 1, 1, '142.251.10.26', 1),
(14, '1115091998rk@gmail.com', '1115091998rk', 'gmail.com', 1, 0, 'Invalid response', 1),
(15, '121@boond.com', '121', 'boond.com', 1, 0, 'Invalid response', 0),
(16, '123@gail.com', '123', 'gail.com', 1, 0, 'Invalid response', 0),
(17, '123_45@gmail.com', '123_45', 'gmail.com', 1, 0, 'Invalid response', 1),
(18, '12332@gmail.com', '12332', 'gmail.com', 1, 0, 'Invalid response', 0),
(19, '12345@mail.com', '12345', 'mail.com', 1, 0, 'Invalid response', 0),
(20, '123456@gmail.com', '123456', 'gmail.com', 1, 0, 'Invalid response', 0),
(21, '123gillu@gmail.com', '123gillu', 'gmail.com', 1, 1, '142.250.141.27', 1),
(22, '123snsarchitects@gmail.com', '123snsarchitects', 'gmail.com', 1, 1, '142.250.141.27', 1),
(23, '124jh@gmail.com', '124jh', 'gmail.com', 1, 0, 'Invalid response', 1),
(24, '125@gmail.com', '125', 'gmail.com', 1, 0, 'Invalid response', 0),
(25, '127.0.0.17@gmail.com', '127.0.0.17', 'gmail.com', 1, 1, '173.194.202.27', 1),
(26, 'panchalpavan800@gmail.com', 'panchalpavan800', 'gmail.com', 1, 1, '173.194.202.27', 1),
(27, 'panchalsafsdasdf@gmail.com', 'panchalsafsdasdf', 'gmail.com', 1, 0, 'Invalid response', 1),
(28, 'abcd%#@gmail.com', 'abcd%#', 'gmail.com', 1, 0, 'Invalid response', 0);

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `smtp_server_id` int(11) DEFAULT NULL,
  `sender_email` varchar(100) NOT NULL,
  `recipient_email` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `status` enum('sent','failed') NOT NULL,
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`id`, `smtp_server_id`, `sender_email`, `recipient_email`, `subject`, `status`, `error_message`, `sent_at`) VALUES
(1, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:54:23'),
(2, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:54:29'),
(3, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:56:05'),
(4, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:56:09'),
(5, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:56:14'),
(6, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:56:20'),
(7, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'hello', 'sent', NULL, '2025-04-16 08:56:25'),
(8, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:56:30'),
(9, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:56:35'),
(10, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:56:39'),
(11, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:56:44'),
(12, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'hello', 'sent', NULL, '2025-04-16 08:56:49'),
(13, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:56:53'),
(14, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:56:58'),
(15, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:57:03'),
(16, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'hello', 'sent', NULL, '2025-04-16 08:57:08'),
(17, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'hello', 'sent', NULL, '2025-04-16 08:57:12'),
(18, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:57:18'),
(19, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:57:23'),
(20, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'hello', 'sent', NULL, '2025-04-16 08:57:28'),
(21, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:57:32'),
(22, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:57:37'),
(23, 1, 'panchalpavan7090@gmail.com', '123snsarchitects@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:57:42'),
(24, 1, 'panchalpavan7090@gmail.com', '124jh@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:57:47'),
(25, 1, 'panchalpavan7090@gmail.com', '125@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:57:52'),
(26, 1, 'panchalpavan7090@gmail.com', '127.0.0.17@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:57:57'),
(27, 1, 'panchalpavan7090@gmail.com', 'panchalpavan800@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:58:01'),
(28, 1, 'panchalpavan7090@gmail.com', 'panchalsafsdasdf@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:58:06'),
(29, 1, 'panchalpavan7090@gmail.com', 'abcd%#@gmail.com', 'hello', 'sent', NULL, '2025-04-16 08:58:11'),
(30, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:00:04'),
(31, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:00:09'),
(32, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:00:14'),
(33, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:00:19'),
(34, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'working', 'sent', NULL, '2025-04-16 09:00:24'),
(35, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:00:29'),
(36, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:00:34'),
(37, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:00:38'),
(38, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:00:42'),
(39, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'working', 'sent', NULL, '2025-04-16 09:00:47'),
(40, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:00:52'),
(41, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:00:58'),
(42, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:01:02'),
(43, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'working', 'sent', NULL, '2025-04-16 09:01:07'),
(44, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'working', 'sent', NULL, '2025-04-16 09:01:11'),
(45, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:01:15'),
(46, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:01:20'),
(47, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'working', 'sent', NULL, '2025-04-16 09:01:24'),
(48, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:01:28'),
(49, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:01:34'),
(50, 1, 'panchalpavan7090@gmail.com', '123snsarchitects@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:01:38'),
(51, 1, 'panchalpavan7090@gmail.com', '124jh@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:01:43'),
(52, 1, 'panchalpavan7090@gmail.com', '125@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:01:47'),
(53, 1, 'panchalpavan7090@gmail.com', '127.0.0.17@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:01:51'),
(54, 1, 'panchalpavan7090@gmail.com', 'panchalpavan800@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:01:56'),
(55, 1, 'panchalpavan7090@gmail.com', 'panchalsafsdasdf@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:02:00'),
(56, 1, 'panchalpavan7090@gmail.com', 'abcd%#@gmail.com', 'working', 'sent', NULL, '2025-04-16 09:02:05'),
(57, 2, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'hjfgh', 'sent', NULL, '2025-04-16 09:08:33'),
(58, 2, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'hjfgh', 'sent', NULL, '2025-04-16 09:08:38'),
(59, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:10:34'),
(60, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:10:39'),
(61, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:10:43'),
(62, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:10:48'),
(63, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'dfgh', 'sent', NULL, '2025-04-16 09:10:53'),
(64, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:10:58'),
(65, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:11:04'),
(66, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:11:09'),
(67, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:11:13'),
(68, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'dfgh', 'sent', NULL, '2025-04-16 09:11:19'),
(69, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:11:24'),
(70, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:11:28'),
(71, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:11:33'),
(72, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'dfgh', 'sent', NULL, '2025-04-16 09:11:39'),
(73, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:11:44'),
(74, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:11:49'),
(75, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:11:54'),
(76, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:11:59'),
(77, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:12:04'),
(78, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:12:09'),
(79, 1, 'panchalpavan7090@gmail.com', '123snsarchitects@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:12:13'),
(80, 1, 'panchalpavan7090@gmail.com', '124jh@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:12:19'),
(81, 1, 'panchalpavan7090@gmail.com', '125@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:12:23'),
(82, 1, 'panchalpavan7090@gmail.com', '127.0.0.17@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:12:28'),
(83, 1, 'panchalpavan7090@gmail.com', 'panchalpavan800@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:12:33'),
(84, 1, 'panchalpavan7090@gmail.com', 'panchalsafsdasdf@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:12:38'),
(85, 1, 'panchalpavan7090@gmail.com', 'abcd%#@gmail.com', 'dfgh', 'sent', NULL, '2025-04-16 09:12:43'),
(86, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:18:52'),
(87, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:18:57'),
(88, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:19:02'),
(89, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:19:06'),
(90, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:19:11'),
(91, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:19:16'),
(92, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:19:20'),
(93, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:19:25'),
(94, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:19:29'),
(95, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:19:35'),
(96, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:19:39'),
(97, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:19:43'),
(98, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:19:48'),
(99, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:19:53'),
(100, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:19:57'),
(101, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:20:02'),
(102, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:20:07'),
(103, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:20:12'),
(104, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:20:17'),
(105, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:20:22'),
(106, 1, 'panchalpavan7090@gmail.com', '123snsarchitects@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:20:26'),
(107, 1, 'panchalpavan7090@gmail.com', '124jh@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:20:31'),
(108, 1, 'panchalpavan7090@gmail.com', '125@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:20:35'),
(109, 1, 'panchalpavan7090@gmail.com', '127.0.0.17@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:20:39'),
(110, 1, 'panchalpavan7090@gmail.com', 'panchalpavan800@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:20:43'),
(111, 1, 'panchalpavan7090@gmail.com', 'panchalsafsdasdf@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:20:48'),
(112, 1, 'panchalpavan7090@gmail.com', 'abcd%#@gmail.com', 'asdfasfd', 'sent', NULL, '2025-04-16 09:20:52'),
(113, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:22:56'),
(114, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:23:01'),
(115, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:23:05'),
(116, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:23:10'),
(117, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'working emails ', 'sent', NULL, '2025-04-16 09:23:20'),
(118, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:23:25'),
(119, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:23:30'),
(120, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:23:35'),
(121, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:23:40'),
(122, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'working emails ', 'sent', NULL, '2025-04-16 09:23:46'),
(123, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:23:50'),
(124, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:23:55'),
(125, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:24:00'),
(126, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'working emails ', 'sent', NULL, '2025-04-16 09:24:04'),
(127, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:24:10'),
(128, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:24:14'),
(129, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:24:19'),
(130, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:24:24'),
(131, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:24:29'),
(132, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:24:34'),
(133, 1, 'panchalpavan7090@gmail.com', '123snsarchitects@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:24:40'),
(134, 1, 'panchalpavan7090@gmail.com', '124jh@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:24:45'),
(135, 1, 'panchalpavan7090@gmail.com', '125@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:24:50'),
(136, 1, 'panchalpavan7090@gmail.com', '127.0.0.17@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:24:55'),
(137, 1, 'panchalpavan7090@gmail.com', 'panchalpavan800@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:25:00'),
(138, 1, 'panchalpavan7090@gmail.com', 'panchalsafsdasdf@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:25:04'),
(139, 1, 'panchalpavan7090@gmail.com', 'abcd%#@gmail.com', 'working emails ', 'sent', NULL, '2025-04-16 09:25:09'),
(140, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:28:04'),
(141, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:28:08'),
(142, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:28:13'),
(143, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:28:22'),
(144, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'asdf', 'sent', NULL, '2025-04-16 09:28:27'),
(145, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:28:31'),
(146, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:28:35'),
(147, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:28:39'),
(148, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:28:44'),
(149, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'asdf', 'sent', NULL, '2025-04-16 09:28:48'),
(150, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:28:52'),
(151, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:28:57'),
(152, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:29:01'),
(153, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'asdf', 'sent', NULL, '2025-04-16 09:29:05'),
(154, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'asdf', 'sent', NULL, '2025-04-16 09:29:10'),
(155, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:29:14'),
(156, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:29:19'),
(157, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'asdf', 'sent', NULL, '2025-04-16 09:29:22'),
(158, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:29:27'),
(159, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:29:31'),
(160, 1, 'panchalpavan7090@gmail.com', '123snsarchitects@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:29:35'),
(161, 1, 'panchalpavan7090@gmail.com', '124jh@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:29:40'),
(162, 1, 'panchalpavan7090@gmail.com', '125@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:29:44'),
(163, 1, 'panchalpavan7090@gmail.com', '127.0.0.17@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:29:48'),
(164, 1, 'panchalpavan7090@gmail.com', 'panchalpavan800@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:29:53'),
(165, 1, 'panchalpavan7090@gmail.com', 'panchalsafsdasdf@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:29:57'),
(166, 1, 'panchalpavan7090@gmail.com', 'abcd%#@gmail.com', 'asdf', 'sent', NULL, '2025-04-16 09:30:01'),
(167, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:01'),
(168, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:06'),
(169, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:09'),
(170, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:10'),
(171, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:12'),
(172, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:12'),
(173, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:13'),
(174, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:13'),
(175, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:14'),
(176, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:16'),
(177, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:16'),
(178, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:17'),
(179, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:17'),
(180, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'hii', 'sent', NULL, '2025-04-16 09:33:18'),
(181, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:20'),
(182, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:21'),
(183, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:21'),
(184, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:22'),
(185, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:23'),
(186, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:24'),
(187, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:25'),
(188, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:25'),
(189, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'hii', 'sent', NULL, '2025-04-16 09:33:26'),
(190, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:27'),
(191, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'hii', 'sent', NULL, '2025-04-16 09:33:29'),
(192, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'hii', 'sent', NULL, '2025-04-16 09:33:29'),
(193, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'hii', 'sent', NULL, '2025-04-16 09:33:29'),
(194, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:30'),
(195, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:31'),
(196, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:32'),
(197, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:33'),
(198, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:34'),
(199, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:34'),
(200, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:35'),
(201, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:36'),
(202, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:37'),
(203, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:38'),
(204, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:39'),
(205, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'hii', 'sent', NULL, '2025-04-16 09:33:40'),
(206, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:41'),
(207, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:42'),
(208, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:42'),
(209, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:43'),
(210, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:44'),
(211, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:45'),
(212, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:46'),
(213, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:46'),
(214, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'hii', 'sent', NULL, '2025-04-16 09:33:47'),
(215, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:48'),
(216, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'hii', 'sent', NULL, '2025-04-16 09:33:49'),
(217, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'hii', 'sent', NULL, '2025-04-16 09:33:50'),
(218, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'hii', 'sent', NULL, '2025-04-16 09:33:51'),
(219, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:51'),
(220, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:53'),
(221, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:54'),
(222, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:54'),
(223, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:55'),
(224, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:56'),
(225, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'hii', 'sent', NULL, '2025-04-16 09:33:57'),
(226, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:58'),
(227, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:33:59'),
(228, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:00'),
(229, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:00'),
(230, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:01'),
(231, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:02'),
(232, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:03'),
(233, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:04'),
(234, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'hii', 'sent', NULL, '2025-04-16 09:34:04'),
(235, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:05'),
(236, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'sdfgsd', 'sent', NULL, '2025-04-16 09:34:06'),
(237, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'hii', 'sent', NULL, '2025-04-16 09:34:07'),
(238, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'hii', 'sent', NULL, '2025-04-16 09:34:08'),
(239, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'hii', 'sent', NULL, '2025-04-16 09:34:08'),
(240, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:09'),
(241, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:10'),
(242, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'sdfgsd', 'sent', NULL, '2025-04-16 09:34:10'),
(243, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:12'),
(244, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:12'),
(245, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:13'),
(246, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:13'),
(247, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:14'),
(248, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'sdfgsd', 'sent', NULL, '2025-04-16 09:34:14'),
(249, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:16'),
(250, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:16'),
(251, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:17'),
(252, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:18'),
(253, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:19'),
(254, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'sdfgsd', 'sent', NULL, '2025-04-16 09:34:19'),
(255, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:20'),
(256, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:20'),
(257, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:22'),
(258, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:22'),
(259, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:23'),
(260, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'sdfgsd', 'sent', NULL, '2025-04-16 09:34:23'),
(261, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:24'),
(262, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:25'),
(263, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:26'),
(264, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:26'),
(265, 1, 'panchalpavan7090@gmail.com', '123snsarchitects@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:27'),
(266, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'sdfgsd', 'sent', NULL, '2025-04-16 09:34:28'),
(267, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:28'),
(268, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:29'),
(269, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:30'),
(270, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:31'),
(271, 1, 'panchalpavan7090@gmail.com', '124jh@gmail.com', 'hii', 'sent', NULL, '2025-04-16 09:34:31'),
(272, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'sdfgsd', 'sent', NULL, '2025-04-16 09:34:32'),
(273, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:09'),
(274, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:12'),
(275, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:13'),
(276, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:17'),
(277, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:17'),
(278, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:21'),
(279, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:21'),
(280, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:25'),
(281, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:25'),
(282, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:29'),
(283, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:30'),
(284, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:34'),
(285, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:35'),
(286, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:39'),
(287, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:40'),
(288, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:43'),
(289, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:44'),
(290, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:48'),
(291, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:49'),
(292, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:52'),
(293, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:53'),
(294, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:56'),
(295, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:06:57'),
(296, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:00'),
(297, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:01'),
(298, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:04'),
(299, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:05'),
(300, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:09'),
(301, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:09'),
(302, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:14'),
(303, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:14'),
(304, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:19'),
(305, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:19'),
(306, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:23'),
(307, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:24'),
(308, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:28'),
(309, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:28'),
(310, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:33'),
(311, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:33'),
(312, 1, 'panchalpavan7090@gmail.com', '123snsarchitects@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:37'),
(313, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:37'),
(314, 1, 'panchalpavan7090@gmail.com', '124jh@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:41'),
(315, 1, 'panchalpavan7090@gmail.com', '123snsarchitects@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:41'),
(316, 1, 'panchalpavan7090@gmail.com', '124jh@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:46'),
(317, 1, 'panchalpavan7090@gmail.com', '125@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:46'),
(318, 1, 'panchalpavan7090@gmail.com', '125@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:50'),
(319, 1, 'panchalpavan7090@gmail.com', '127.0.0.17@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:50'),
(320, 1, 'panchalpavan7090@gmail.com', '127.0.0.17@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:54'),
(321, 1, 'panchalpavan7090@gmail.com', 'panchalpavan800@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:54'),
(322, 1, 'panchalpavan7090@gmail.com', 'panchalpavan800@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:59'),
(323, 1, 'panchalpavan7090@gmail.com', 'panchalsafsdasdf@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:07:59'),
(324, 1, 'panchalpavan7090@gmail.com', 'panchalsafsdasdf@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:08:03'),
(325, 1, 'panchalpavan7090@gmail.com', 'abcd%#@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:08:03'),
(326, 1, 'panchalpavan7090@gmail.com', 'abcd%#@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 12:08:08'),
(327, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:26:11'),
(328, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:26:16'),
(329, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:26:21'),
(330, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:26:26'),
(331, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:26:31'),
(332, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:26:37'),
(333, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:26:42'),
(334, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:26:47'),
(335, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:26:51'),
(336, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:26:56'),
(337, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:01'),
(338, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:06'),
(339, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:11'),
(340, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:15'),
(341, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:20'),
(342, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:24'),
(343, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:28'),
(344, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:32'),
(345, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:36'),
(346, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:40'),
(347, 1, 'panchalpavan7090@gmail.com', '123snsarchitects@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:44'),
(348, 1, 'panchalpavan7090@gmail.com', '124jh@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:47'),
(349, 1, 'panchalpavan7090@gmail.com', '125@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:51'),
(350, 1, 'panchalpavan7090@gmail.com', '127.0.0.17@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:55'),
(351, 1, 'panchalpavan7090@gmail.com', 'panchalpavan800@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:27:59'),
(352, 1, 'panchalpavan7090@gmail.com', 'panchalsafsdasdf@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:28:04'),
(353, 1, 'panchalpavan7090@gmail.com', 'abcd%#@gmail.com', 'pavan@gmail.com', 'sent', NULL, '2025-04-16 12:28:09'),
(354, 1, 'panchalpavan7090@gmail.com', '007_ikku@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:08:34'),
(355, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:08:38'),
(356, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:08:43'),
(357, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:08:48'),
(358, 1, 'panchalpavan7090@gmail.com', '03580.hr@fourpoints.com', 'abcd', 'sent', NULL, '2025-04-16 13:08:53'),
(359, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:08:57'),
(360, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:09:01'),
(361, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:09:06'),
(362, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:09:10'),
(363, 1, 'panchalpavan7090@gmail.com', '101a@a.com', 'abcd', 'sent', NULL, '2025-04-16 13:09:15'),
(364, 1, 'panchalpavan7090@gmail.com', '11@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:09:19'),
(365, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:09:23'),
(366, 1, 'panchalpavan7090@gmail.com', '1115091998rk@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:09:28'),
(367, 1, 'panchalpavan7090@gmail.com', '121@boond.com', 'abcd', 'sent', NULL, '2025-04-16 13:09:32'),
(368, 1, 'panchalpavan7090@gmail.com', '123@gail.com', 'abcd', 'sent', NULL, '2025-04-16 13:09:36'),
(369, 1, 'panchalpavan7090@gmail.com', '123_45@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:09:40'),
(370, 1, 'panchalpavan7090@gmail.com', '12332@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:09:44'),
(371, 1, 'panchalpavan7090@gmail.com', '12345@mail.com', 'abcd', 'sent', NULL, '2025-04-16 13:09:48'),
(372, 1, 'panchalpavan7090@gmail.com', '123456@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:09:53'),
(373, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:09:57'),
(374, 1, 'panchalpavan7090@gmail.com', '123snsarchitects@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:10:01'),
(375, 1, 'panchalpavan7090@gmail.com', '124jh@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:10:05'),
(376, 1, 'panchalpavan7090@gmail.com', '125@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:10:09'),
(377, 1, 'panchalpavan7090@gmail.com', '127.0.0.17@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:10:13'),
(378, 1, 'panchalpavan7090@gmail.com', 'panchalpavan800@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:10:18'),
(379, 1, 'panchalpavan7090@gmail.com', 'panchalsafsdasdf@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:10:22'),
(380, 1, 'panchalpavan7090@gmail.com', 'abcd%#@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 13:10:26'),
(381, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'sadf', 'sent', NULL, '2025-04-16 13:11:14'),
(382, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'sadf', 'sent', NULL, '2025-04-16 13:11:18'),
(383, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'sadf', 'sent', NULL, '2025-04-16 13:11:22'),
(384, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'sadf', 'sent', NULL, '2025-04-16 13:11:27'),
(385, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'sadf', 'sent', NULL, '2025-04-16 13:11:31'),
(386, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'sadf', 'sent', NULL, '2025-04-16 13:11:35'),
(387, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'sadf', 'sent', NULL, '2025-04-16 13:11:39'),
(388, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'sadf', 'sent', NULL, '2025-04-16 13:11:44'),
(389, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'sadf', 'sent', NULL, '2025-04-16 13:11:48'),
(390, 1, 'panchalpavan7090@gmail.com', '123snsarchitects@gmail.com', 'sadf', 'sent', NULL, '2025-04-16 13:11:58'),
(391, 1, 'panchalpavan7090@gmail.com', '127.0.0.17@gmail.com', 'sadf', 'sent', NULL, '2025-04-16 13:12:02'),
(392, 1, 'panchalpavan7090@gmail.com', 'panchalpavan800@gmail.com', 'sadf', 'sent', NULL, '2025-04-16 13:12:06'),
(393, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'subject ', 'sent', NULL, '2025-04-16 13:29:56'),
(394, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'subject ', 'sent', NULL, '2025-04-16 13:30:01'),
(395, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'subject ', 'sent', NULL, '2025-04-16 13:30:06'),
(396, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'subject ', 'sent', NULL, '2025-04-16 13:30:10'),
(397, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'subject ', 'sent', NULL, '2025-04-16 13:30:15'),
(398, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'subject ', 'sent', NULL, '2025-04-16 13:30:20'),
(399, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'subject ', 'sent', NULL, '2025-04-16 13:30:25'),
(400, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'subject ', 'sent', NULL, '2025-04-16 13:30:31'),
(401, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'subject ', 'sent', NULL, '2025-04-16 13:30:35'),
(402, 1, 'panchalpavan7090@gmail.com', '123snsarchitects@gmail.com', 'subject ', 'sent', NULL, '2025-04-16 13:30:38'),
(403, 1, 'panchalpavan7090@gmail.com', '127.0.0.17@gmail.com', 'subject ', 'sent', NULL, '2025-04-16 13:30:42'),
(404, 1, 'panchalpavan7090@gmail.com', 'panchalpavan800@gmail.com', 'subject ', 'sent', NULL, '2025-04-16 13:30:47'),
(405, 1, 'panchalpavan7090@gmail.com', '00ar.khan@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 14:33:38'),
(406, 1, 'panchalpavan7090@gmail.com', '0107sjain@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 14:33:43'),
(407, 1, 'panchalpavan7090@gmail.com', '0202santu@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 14:33:48'),
(408, 1, 'panchalpavan7090@gmail.com', '05rohitmishra@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 14:33:52'),
(409, 1, 'panchalpavan7090@gmail.com', '08pradeepyadavspn@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 14:33:56'),
(410, 1, 'panchalpavan7090@gmail.com', '1008harsh1@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 14:34:01'),
(411, 1, 'panchalpavan7090@gmail.com', '100plusproperties.hyd@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 14:34:05'),
(412, 1, 'panchalpavan7090@gmail.com', '111.svtax@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 14:34:09'),
(413, 1, 'panchalpavan7090@gmail.com', '123gillu@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 14:34:14'),
(414, 1, 'panchalpavan7090@gmail.com', '123snsarchitects@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 14:34:18'),
(415, 1, 'panchalpavan7090@gmail.com', '127.0.0.17@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 14:34:22'),
(416, 1, 'panchalpavan7090@gmail.com', 'panchalpavan800@gmail.com', 'abcd', 'sent', NULL, '2025-04-16 14:34:27');

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
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `smtp_servers`
--

INSERT INTO `smtp_servers` (`id`, `name`, `host`, `port`, `encryption`, `email`, `password`, `daily_limit`, `is_active`, `created_at`) VALUES
(1, 'SMTP1', 'smtp.gmail.com', 587, 'tls', 'panchalpavan7090@gmail.com', 'fvof dhjk iekz lvny', 500, 1, '2025-04-16 08:50:13'),
(2, 'SMTP2', 'smtp.gmail.com', 587, 'tls', 'panchalpavan7090@gmail.com', 'fvof dhjk iekz lvny', 500, 1, '2025-04-16 08:50:13'),
(3, 'SMTP3', 'smtp.gmail.com', 587, 'tls', 'panchalpavan7090@gmail.com', 'fvof dhjk iekz lvny', 500, 1, '2025-04-16 08:50:13'),
(4, 'SMTP4', 'smtp.gmail.com', 587, 'tls', 'panchalpavan7090@gmail.com', 'fvof dhjk iekz lvny', 500, 1, '2025-04-16 08:50:13'),
(5, 'SMTP5', 'smtp.gmail.com', 587, 'tls', 'panchalpavan7090@gmail.com', 'fvof dhjk iekz lvny', 500, 1, '2025-04-16 08:50:13');

-- --------------------------------------------------------

--
-- Table structure for table `smtp_usage`
--

CREATE TABLE `smtp_usage` (
  `id` int(11) NOT NULL,
  `smtp_server_id` int(11) DEFAULT NULL,
  `usage_date` date NOT NULL,
  `usage_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `smtp_usage`
--

INSERT INTO `smtp_usage` (`id`, `smtp_server_id`, `usage_date`, `usage_count`) VALUES
(1, 1, '2025-04-16', 413),
(3, 2, '2025-04-16', 1);

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
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `smtp_server_id` (`smtp_server_id`);

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
-- Indexes for table `smtp_servers`
--
ALTER TABLE `smtp_servers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `smtp_usage`
--
ALTER TABLE `smtp_usage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `smtp_server_id` (`smtp_server_id`,`usage_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `emails`
--
ALTER TABLE `emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=417;

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

--
-- AUTO_INCREMENT for table `smtp_servers`
--
ALTER TABLE `smtp_servers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `smtp_usage`
--
ALTER TABLE `smtp_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `email_logs_ibfk_1` FOREIGN KEY (`smtp_server_id`) REFERENCES `smtp_servers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
