-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2026 at 10:51 AM
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
(1, 38, 90);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
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

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `username`, `password`, `created_at`) VALUES
(1, 'szickyel', 'reyes', 'ezekielreyes@gmail.com', 'admin', 'admin', '2026-11-03 12:00:06');

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
(111, 37, 38, 90, '', 'Level dropped to 37% (< min threshold 38%). Pump turned ON to refill up to 90%.', '2026-11-19 08:57:53');

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

-- --------------------------------------------------------

--
-- Structure for view `pump_logs`
--

CREATE OR REPLACE VIEW `pump_logs` AS
SELECT 
  `level_id` AS `log_id`,
  `action` AS `action`,
  `water_level` AS `water_level`,
  `description` AS `description`,
  `recorded_at` AS `created_at`
FROM `water_levels`
ORDER BY `level_id` DESC;

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
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `water_levels`
--
ALTER TABLE `water_levels`
  MODIFY `level_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
