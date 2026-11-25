-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2026 at 06:24 AM
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
-- Database: `aquasync`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `pump_logs`
-- (See below for the actual view)
--
CREATE TABLE `pump_logs` (
`log_id` int(11)
,`action` varchar(5)
,`water_level` int(11)
,`description` varchar(100)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL,
  `min_threshold` int(11) NOT NULL DEFAULT 60,
  `max_threshold` int(11) NOT NULL DEFAULT 95
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_id`, `min_threshold`, `max_threshold`) VALUES
(1, 20, 90);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `role` varchar(20) NOT NULL,
  `first_name` varchar(25) NOT NULL,
  `last_name` varchar(25) NOT NULL,
  `email` varchar(40) NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role`, `first_name`, `last_name`, `email`, `username`, `password`, `created_at`) VALUES
(1, 'master', 'Ezekiel', 'Reyes', 'ezekielreyes@gmail.com', 'admin', '$2y$10$A6TgOkK1M673BgRNSvk9seFlTMqFsqPByoMUC8bxHQLLkZP0czx8O', '2026-11-03 12:00:06'),
(2, 'admin', 'John Paul', 'Rosales', 'jpgads@gmail.com', 'jpgods', '$2y$10$4a1ZzXGk69qSZ4cyPA94Be/TzDJIIbUm2uI6bkmN294i4E0GsdiGC', '2026-11-25 03:18:33');

-- --------------------------------------------------------

--
-- Table structure for table `water_levels`
--

CREATE TABLE `water_levels` (
  `level_id` int(11) NOT NULL,
  `water_level` int(11) NOT NULL,
  `min_threshold` int(11) NOT NULL,
  `max_threshold` int(11) NOT NULL,
  `action` varchar(5) NOT NULL,
  `description` varchar(100) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `water_levels`
--

INSERT INTO `water_levels` (`level_id`, `water_level`, `min_threshold`, `max_threshold`, `action`, `description`, `recorded_at`) VALUES
(1, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:09'),
(2, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:12'),
(3, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:15'),
(4, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:18'),
(5, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:22'),
(6, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:25'),
(7, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:28'),
(8, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:31'),
(9, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:34'),
(10, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:37'),
(11, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:40'),
(12, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:43'),
(13, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:46'),
(14, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:49'),
(15, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:52'),
(16, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:55'),
(17, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:46:59'),
(18, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:02'),
(19, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:05'),
(20, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:08'),
(21, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:11'),
(22, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:14'),
(23, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:17'),
(24, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:20'),
(25, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:23'),
(26, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:26'),
(27, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:29'),
(28, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:32'),
(29, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:35'),
(30, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:39'),
(31, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:42'),
(32, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:45'),
(33, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:48'),
(34, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:51'),
(35, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:54'),
(36, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:47:57'),
(37, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:00'),
(38, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:03'),
(39, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:06'),
(40, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:09'),
(41, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:12'),
(42, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:16'),
(43, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:19'),
(44, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:22'),
(45, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:25'),
(46, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:28'),
(47, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:31'),
(48, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:34'),
(49, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:37'),
(50, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:40'),
(51, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:43'),
(52, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:46'),
(53, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:49'),
(54, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:53'),
(55, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:56'),
(56, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:48:59'),
(57, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:02'),
(58, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:05'),
(59, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:08'),
(60, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:11'),
(61, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:14'),
(62, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:17'),
(63, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:20'),
(64, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:23'),
(65, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:26'),
(66, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:30'),
(67, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:33'),
(68, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:36'),
(69, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:39'),
(70, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:42'),
(71, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:45'),
(72, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:48'),
(73, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:51'),
(74, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:54'),
(75, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:49:57'),
(76, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:00'),
(77, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:03'),
(78, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:06'),
(79, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:09'),
(80, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:13'),
(81, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:16'),
(82, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:19'),
(83, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:22'),
(84, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:25'),
(85, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:28'),
(86, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:31'),
(87, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:34'),
(88, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:37'),
(89, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:40'),
(90, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:43'),
(91, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:46'),
(92, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:49'),
(93, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:53'),
(94, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:56'),
(95, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:50:59'),
(96, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:02'),
(97, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:05'),
(98, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:08'),
(99, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:11'),
(100, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:14'),
(101, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:17'),
(102, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:20'),
(103, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:23'),
(104, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:27'),
(105, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:30'),
(106, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:33'),
(107, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:36'),
(108, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:39'),
(109, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:42'),
(110, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:45'),
(111, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:48'),
(112, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:51'),
(113, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:54'),
(114, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:51:57'),
(115, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:00'),
(116, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:03'),
(117, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:07'),
(118, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:10'),
(119, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:13'),
(120, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:16'),
(121, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:19'),
(122, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:22'),
(123, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:25'),
(124, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:28'),
(125, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:31'),
(126, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:34'),
(127, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:37'),
(128, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:40'),
(129, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:43'),
(130, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:47'),
(131, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:50'),
(132, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:53'),
(133, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:56'),
(134, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:52:59'),
(135, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:53:02'),
(136, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:53:05'),
(137, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:53:08'),
(138, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:53:11'),
(139, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:53:14'),
(140, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:53:17'),
(141, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:53:21'),
(142, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:53:24'),
(143, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:53:27'),
(144, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:53:30'),
(145, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:53:33'),
(146, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:53:36'),
(147, 100, 20, 90, 'OFF', 'Target level achieved', '2026-11-19 13:53:39'),
(148, 30, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:06:48'),
(149, 31, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:06:51'),
(150, 36, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:06:54'),
(151, 39, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:06:57'),
(152, 42, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:07:00'),
(153, 36, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:07:04'),
(154, 33, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:07:07'),
(155, 31, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:07:10'),
(156, 96, 20, 90, 'OFF', 'Target level achieved', '2026-11-21 16:09:05'),
(157, 35, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:08'),
(158, 48, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:11'),
(159, 44, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:14'),
(160, 33, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:17'),
(161, 32, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:20'),
(162, 37, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:23'),
(163, 43, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:26'),
(164, 36, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:29'),
(165, 31, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:32'),
(166, 33, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:35'),
(167, 32, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:39'),
(168, 35, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:42'),
(169, 36, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:45'),
(170, 34, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:48'),
(171, 43, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:51'),
(172, 31, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:54'),
(173, 35, 20, 90, 'OFF', 'Normal water level', '2026-11-21 16:09:57');

-- --------------------------------------------------------

--
-- Structure for view `pump_logs`
--
DROP TABLE IF EXISTS `pump_logs`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `pump_logs`  AS SELECT `water_levels`.`level_id` AS `log_id`, `water_levels`.`action` AS `action`, `water_levels`.`water_level` AS `water_level`, `water_levels`.`description` AS `description`, `water_levels`.`recorded_at` AS `created_at` FROM `water_levels` ORDER BY `water_levels`.`level_id` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `water_levels`
--
ALTER TABLE `water_levels`
  ADD PRIMARY KEY (`level_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `water_levels`
--
ALTER TABLE `water_levels`
  MODIFY `level_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=174;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
