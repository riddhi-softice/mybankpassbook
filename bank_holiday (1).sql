-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2025 at 07:21 AM
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
-- Table structure for table `bank_holiday`
--

CREATE TABLE `bank_holiday` (
  `holiday_id` int(11) NOT NULL,
  `holiday_reason` varchar(150) DEFAULT NULL,
  `day` varchar(100) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_holiday`
--

INSERT INTO `bank_holiday` (`holiday_id`, `holiday_reason`, `day`, `date`, `created_at`, `updated_at`) VALUES
(1, '2nd Saturday', 'Saturday', '2025-01-11', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(2, '4th Saturday', 'Saturday', '2025-01-25', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(3, 'Republic Day', 'Sunday', '2025-01-26', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(4, '2nd Saturday', 'Saturday', '2025-02-08', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(5, '4th Saturday', 'Saturday', '2025-02-22', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(6, 'Maha Shivaratri', 'Wednesday', '2025-02-26', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(7, '2nd Saturday', 'Saturday', '2025-03-08', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(8, 'Holi', 'Friday', '2025-03-14', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(9, '4th Saturday', 'Saturday', '2025-03-22', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(10, 'Ugadi', 'Sunday', '2025-03-30', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(11, '2nd Saturday', 'Saturday', '2025-04-12', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(12, 'Vaisakhi', 'Sunday', '2025-04-13', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(13, 'Ambedkar Jayanti', 'Monday', '2025-04-14', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(14, 'Good Friday', 'Friday', '2025-04-18', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(15, '4th Saturday', 'Saturday', '2025-04-26', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(16, 'May Day', 'Thursday', '2025-05-01', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(17, '2nd Saturday', 'Saturday', '2025-05-10', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(18, '4th Saturday', 'Saturday', '2025-05-24', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(19, 'Bakrid/Eid al-Adha', 'Friday', '2025-06-06', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(20, '2nd Saturday', 'Saturday', '2025-06-14', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(21, '4th Saturday', 'Saturday', '2025-06-28', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(22, '2nd Saturday', 'Saturday', '2025-07-12', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(23, '4th Saturday', 'Saturday', '2025-07-26', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(24, '2nd Saturday', 'Saturday', '2025-08-09', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(25, 'Independence Day', 'Friday', '2025-08-15', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(26, 'Janmashtami', 'Friday', '2025-08-15', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(27, '4th Saturday', 'Saturday', '2025-08-23', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(28, '2nd Saturday', 'Saturday', '2025-09-13', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(29, '4th Saturday', 'Saturday', '2025-09-27', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(30, 'Gandhi Jayanti', 'Thursday', '2025-10-02', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(31, '2nd Saturday', 'Saturday', '2025-10-11', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(32, 'Diwali', 'Monday', '2025-10-20', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(33, '4th Saturday', 'Saturday', '2025-10-25', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(34, '2nd Saturday', 'Saturday', '2025-11-08', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(35, '4th Saturday', 'Saturday', '2025-11-22', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(36, '2nd Saturday', 'Saturday', '2025-12-13', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(37, 'Christmas Day', 'Thursday', '2025-12-25', '2025-04-15 11:55:22', '2025-04-15 11:55:22'),
(38, '4th Saturday', 'Saturday', '2025-12-27', '2025-04-15 11:55:22', '2025-04-15 11:55:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bank_holiday`
--
ALTER TABLE `bank_holiday`
  ADD PRIMARY KEY (`holiday_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bank_holiday`
--
ALTER TABLE `bank_holiday`
  MODIFY `holiday_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
