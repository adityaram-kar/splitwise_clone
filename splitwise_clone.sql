-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 15, 2025 at 02:42 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `splitwise_clone`
--

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_by` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `expense_date` date DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `group_id`, `amount`, `paid_by`, `description`, `expense_date`, `created_at`) VALUES
(2, 3, 200.00, 2, 'spend in costco', '2025-10-28', '2025-10-27 23:18:48'),
(3, 3, 200.00, 2, 'spend in costco', '2025-10-28', '2025-10-28 00:51:00'),
(4, 3, 300.00, 2, 'spend in sams', '2025-10-28', '2025-10-28 01:23:45'),
(5, 5, 2000.00, 3, 'mci', '2025-10-29', '2025-10-29 19:33:25'),
(6, 5, 100.00, 3, 'mci', '2025-10-29', '2025-10-29 21:17:35'),
(7, 3, 100.00, 3, 'iotel', '2025-10-29', '2025-10-29 21:18:10'),
(8, 5, 5000.00, 3, 'trip to st', '2025-10-30', '2025-10-30 04:21:09');

-- --------------------------------------------------------

--
-- Table structure for table `expense_shares`
--

CREATE TABLE `expense_shares` (
  `id` int(11) NOT NULL,
  `expense_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `share_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_shares`
--

INSERT INTO `expense_shares` (`id`, `expense_id`, `user_id`, `share_amount`) VALUES
(3, 2, 1, 100.00),
(4, 2, 2, 100.00),
(5, 3, 1, 100.00),
(6, 3, 2, 100.00),
(7, 4, 1, 150.00),
(8, 4, 2, 150.00),
(9, 5, 2, 1000.00),
(10, 5, 3, 1000.00),
(11, 6, 2, 50.00),
(12, 6, 3, 50.00),
(13, 7, 2, 33.33),
(14, 7, 3, 33.33),
(15, 7, 1, 33.33),
(16, 8, 2, 2500.00),
(17, 8, 3, 2500.00);

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `group_name`, `created_by`, `created_at`) VALUES
(3, 'trip', 1, '2025-10-27 21:17:45'),
(4, 'walmart', 2, '2025-10-28 00:10:51'),
(5, 'king', 2, '2025-10-29 18:57:12');

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`id`, `group_id`, `user_id`, `joined_at`) VALUES
(2, 3, 1, '2025-10-27 21:17:45'),
(3, 3, 2, '2025-10-27 21:18:50'),
(5, 4, 2, '2025-10-28 00:10:51'),
(6, 5, 2, '2025-10-29 18:57:12'),
(7, 5, 3, '2025-10-29 19:32:01'),
(8, 3, 3, '2025-10-29 19:42:08'),
(11, 3, 5, '2025-10-30 16:03:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `created_at`, `profile_pic`) VALUES
(1, 'Sahith', 'Bodipudi', 'sahith9441@gmail.com', '$2y$10$a1bIXq/KPUTXzbBDWFOEr.vDKrIJreesCZELIWt1ybYclHErAzkPe', 'user', '2025-10-27 21:07:12', NULL),
(2, 'aditya', 'ram', 'adityaram@gmaoil.com', '$2y$10$Os2tmj.7lT3X21n8H4KJHeAhIdGEcakT40oyOKrHJfzAxi1O9vUWq', 'user', '2025-10-27 21:18:22', 'avatars/avatar3.jpg'),
(3, 'Sahith', 'Bodipudi', 'chowardysahith@gmail.com', '$2y$10$59SrQb.qRKwiNUebHKibguGZvz33vDcORyfUz9.LwAYdK.vqOtD7i', 'user', '2025-10-29 19:31:46', NULL),
(4, 'Admin', 'User', 'admin@example.com', '$2y$10$C0d39j2vAE9XQsE2jGUfBOIyQ.1VdK.4.yG6I1tpnXK54eBNPki7u', 'admin', '2025-10-29 22:01:04', NULL),
(5, 'muni', 'bodipudi', 'muni@gmail.com', '$2y$10$3tqeWqB7nWb4NwjrND/xoeIsOe2rdsNaOSo/URjV1pm554CyrgERC', 'user', '2025-10-30 15:19:14', 'avatars/avatar1.jpg'),
(6, 'nanitha', 'Bodipudi', 'nanitha@gmail.com', '$2y$10$OiYgINckseN5rgfrJKTBW.s563VBGei66/2MdLftR/O0dlCH/RS.K', 'user', '2025-10-30 18:02:55', 'avatars/avatar1.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `paid_by` (`paid_by`);

--
-- Indexes for table `expense_shares`
--
ALTER TABLE `expense_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expense_id` (`expense_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_membership` (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `expense_shares`
--
ALTER TABLE `expense_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expense_shares`
--
ALTER TABLE `expense_shares`
  ADD CONSTRAINT `expense_shares_ibfk_1` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expense_shares_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
