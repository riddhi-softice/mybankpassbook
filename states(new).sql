-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2025 at 07:20 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sft2018_mybankpassbook`
--

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

CREATE TABLE `states` (
  `id` int(11) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `states`
--

INSERT INTO `states` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Andaman & Nicobar', '2025-04-16 16:30:52', '2025-04-16 16:37:43'),
(2, 'Andhra Pradesh', '2025-04-16 16:30:52', '2025-04-16 16:37:43'),
(3, 'Arunachal Pradesh', '2025-04-16 16:30:52', '2025-04-16 16:37:43'),
(4, 'Assam', '2025-04-16 16:30:52', '2025-04-16 16:37:43'),
(5, 'Bihar', '2025-04-16 16:30:52', '2025-04-16 16:37:43'),
(6, 'Chandigarh', '2025-04-16 16:30:52', '2025-04-16 16:37:43'),
(7, 'Chhattisgarh', '2025-04-16 16:30:52', '2025-04-16 16:37:43'),
(8, 'Dadra and Nagar Haveli', '2025-04-16 16:30:52', '2025-04-16 16:37:43'),
(9, 'Daman and Diu', '2025-04-16 16:35:23', '2025-04-16 16:35:23'),
(10, 'Delhi', '2025-04-16 16:41:28', '2025-04-16 16:41:28'),
(11, 'Goa', '2025-04-16 16:44:13', '2025-04-16 16:44:13'),
(12, 'Gujarat', '2025-04-16 16:45:57', '2025-04-16 16:45:57'),
(13, 'Haryana', '2025-04-16 16:48:16', '2025-04-16 16:48:16'),
(14, 'Himachal Pradesh', '2025-04-16 16:50:17', '2025-04-16 16:50:17'),
(15, 'Jammu and kashmir', '2025-04-16 17:03:19', '2025-04-16 17:03:19'),
(16, 'Jharkhand', '2025-04-16 17:15:59', '2025-04-16 17:15:59'),
(17, 'Karnataka', '2025-04-16 17:21:57', '2025-04-16 17:21:57'),
(18, 'Kerala', '2025-04-16 17:26:30', '2025-04-16 17:26:30'),
(19, 'Lakshadweep', '2025-04-16 17:42:11', '2025-04-16 17:42:11'),
(20, 'Madhya Pradesh', '2025-04-16 17:43:46', '2025-04-16 17:43:46'),
(21, 'Maharashtra', '2025-04-16 17:48:24', '2025-04-16 17:50:22'),
(22, 'Manipur', '2025-04-16 17:54:15', '2025-04-16 17:54:15'),
(23, 'Meghalaya', '2025-04-16 17:55:38', '2025-04-16 17:55:38'),
(24, 'Mizoram', '2025-04-16 17:58:06', '2025-04-16 17:58:06'),
(25, 'Nagaland', '2025-04-16 17:59:59', '2025-04-16 17:59:59'),
(26, 'Odisha', '2025-04-16 18:03:24', '2025-04-16 18:03:24'),
(27, 'Puducherry', '2025-04-17 09:30:44', '2025-04-17 09:30:44'),
(28, 'Punjab', '2025-04-17 09:33:12', '2025-04-17 09:33:12'),
(29, 'Rajasthan', '2025-04-17 09:34:58', '2025-04-17 09:34:58'),
(30, 'Sikkim', '2025-04-17 09:39:20', '2025-04-17 09:39:20'),
(31, 'Tamil Nadu', '2025-04-17 09:48:34', '2025-04-17 09:48:34'),
(32, 'Telangana', '2025-04-17 09:51:18', '2025-04-17 09:51:18'),
(33, 'Tripura', '2025-04-17 10:00:54', '2025-04-17 10:00:54'),
(34, 'Uttar Pradesh', '2025-04-17 10:02:26', '2025-04-17 10:02:26'),
(35, 'Uttarakhand', '2025-04-17 10:06:46', '2025-04-17 10:06:46'),
(36, 'West Bengal', '2025-04-17 10:12:23', '2025-04-17 10:12:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `states`
--
ALTER TABLE `states`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `states`
--
ALTER TABLE `states`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
