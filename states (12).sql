-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2025 at 05:58 AM
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
(1, 'Andhra Pradesh', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(2, 'Arunachal Pradesh', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(3, 'Assam', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(4, 'Bihar', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(5, 'Chattisgarh', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(6, 'Goa', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(7, 'Gujarat', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(8, 'Haryana', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(9, 'Himachal Pradesh', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(10, 'Jharkhand', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(11, 'Karnataka', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(12, 'Kerala', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(13, 'Madhya Pradesh', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(14, 'Maharashtra', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(15, 'Manipur', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(16, 'Meghalaya', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(17, 'Mizoram', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(18, 'Nagaland', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(19, 'Odisha', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(20, 'Punjab', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(21, 'Rajasthan', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(22, 'Sikkim', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(23, 'Tamil Nadu', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(24, 'Telangana', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(25, 'Tripura', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(26, 'Uttarakhand', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(27, 'Uttar Pradesh', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(28, 'West Bengal', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(29, 'Jammu and Kashmir', '2025-04-16 09:25:54', '2025-04-16 09:25:54'),
(30, 'Delhi', '2025-04-16 09:25:54', '2025-04-16 09:25:54');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
