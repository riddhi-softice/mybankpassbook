-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2025 at 09:29 AM
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
-- Table structure for table `state_wise_bank_holiday`
--

CREATE TABLE `state_wise_bank_holiday` (
  `holiday_id` int(11) NOT NULL,
  `holiday_reason` varchar(150) DEFAULT NULL,
  `day` varchar(100) DEFAULT NULL,
  `date` varchar(255) DEFAULT NULL,
  `states` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `state_wise_bank_holiday`
--

INSERT INTO `state_wise_bank_holiday` (`holiday_id`, `holiday_reason`, `day`, `date`, `states`, `created_at`, `updated_at`) VALUES
(77, 'May Day', 'Thursday', '01 May 2025', 'Assam, Bihar, Goa, Gujarat, Karnataka, Kerala, Manipur, Tamil Nadu, Telangana, Tripura, West Bengal, Jammu and Kashmir, Delhi', '2025-04-15 17:38:20', '2025-04-15 17:38:20'),
(78, 'Maharashtra Day', 'Thursday', '01 May 2025', 'Maharashtra', '2025-04-15 17:38:20', '2025-04-15 17:38:20'),
(79, 'Guru Rabindranath Jayanti', 'Thursday', '08 May 2025', 'West Bengal, Tripura, Jammu and Kashmir, Delhi', '2025-04-15 17:38:20', '2025-04-15 17:38:20'),
(80, 'Buddha Purnima', 'Monday', '12 May 2025', 'Arunachal Pradesh, Chhattisgarh, Gujarat, Jharkhand, Madhya Pradesh, Maharashtra, Manipur, Mizoram, Odisha, Sikkim, Tamil Nadu, Tripura, Uttarakhand, West Bengal', '2025-04-15 17:38:20', '2025-04-15 17:38:20'),
(81, 'State Day', 'Friday', '16 May 2025', 'Sikkim', '2025-04-15 17:38:20', '2025-04-15 17:38:20'),
(82, 'Buddha Poornima', 'Friday', '23 May 2025', 'Assam, Mizoram, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi', '2025-04-15 17:38:20', '2025-04-15 17:38:20'),
(83, 'Kazi Nazrul Islam Jayanti', 'Monday', '26 May 2025', 'Tripura', '2025-04-15 17:38:20', '2025-04-15 17:38:20'),
(84, 'Maharana Pratap Jayanti', 'Thursday', '29 May 2025', 'Haryana, Himachal Pradesh, Rajasthan', '2025-04-15 17:38:20', '2025-04-15 17:38:20'),
(85, 'Bakrid / Eid al Adha', 'Sunday', '07 June 2025', 'Andhra Pradesh, Arunachal Pradesh, Bihar, Chhattisgarh, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Karnataka, Madhya Pradesh, Maharashtra, Manipur, Meghalaya, Mizoram, Nagaland, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura', '2025-04-15 17:49:52', '2025-04-15 17:49:52'),
(86, 'Sant Guru Kabir Jayanti', 'Wednesday', '11 June 2025', 'Chattisgarh, Haryana, Punjab, Himachal Pradesh', '2025-04-15 17:49:52', '2025-04-15 17:49:52'),
(87, 'Pahili Raja', 'Saturday', '14 June 2025', 'Odisha, Punjab', '2025-04-15 17:49:52', '2025-04-15 17:49:52'),
(88, 'Yma Day', 'Sunday', '15 June 2025', 'Mizoram', '2025-04-15 17:49:52', '2025-04-15 17:49:52'),
(89, 'Pahili Raja', 'Sunday', '15 June 2025', 'Odisha, Punjab', '2025-04-15 17:49:52', '2025-04-15 17:49:52'),
(90, 'Raja Sankranti', 'Monday', '16 June 2025', 'Odisha, Sikkim', '2025-04-15 17:49:52', '2025-04-15 17:49:52'),
(91, 'Eid-Ul Zuha', 'Tuesday', '17 June 2025', 'Assam, Chattisgarh, Goa, Gujarat, Mizoram, Punjab, Tripura, Tamil Nadu, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi', '2025-04-15 17:49:52', '2025-04-15 17:49:52'),
(92, 'Eid al-Adha', 'Tuesday', '24 June 2025', 'Kerala', '2025-04-15 17:49:52', '2025-04-15 17:49:52'),
(93, 'Ratha Yatra', 'Friday', '27 June 2025', 'Manipur, Odisha, Punjab, Sikkim', '2025-04-15 17:49:52', '2025-04-15 17:49:52'),
(94, 'Remna Ni', 'Monday', '30 June 2025', 'Mizoram', '2025-04-15 17:49:52', '2025-04-15 17:49:52'),
(95, 'Behdingkhlam', 'Thursday', '03 July 2025', 'Meghalaya', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(96, 'Kharchi Puja', 'Thursday', '03 July 2025', 'Tripura', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(97, 'MHIP day', 'Sunday', '06 July 2025', 'Mizoram', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(98, 'Muharram', 'Sunday', '06 July 2025', 'Andhra Pradesh, Himachal Pradesh, Jharkhand, Karnataka, Kerala, Madhya Pradesh, Maharashtra, Manipur, Mizoram, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(99, 'Ratha Yatra', 'Tuesday', '08 July 2025', 'Manipur', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(100, 'Jhulan Purnima', 'Tuesday', '08 July 2025', 'Punjab', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(101, 'Bhanu Jayanti', 'Sunday', '13 July 2025', 'Sikkim', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(102, 'U Tirot Sing Day', 'Thursday', '17 July 2025', 'Meghalaya', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(103, 'Muharram', 'Thursday', '17 July 2025', 'Rajasthan, West Bengal, Jammu and Kashmir, Delhi', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(104, 'Ker Puja', 'Saturday', '19 July 2025', 'Tripura', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(105, 'Bonalu', 'Monday', '21 July 2025', 'Telangana', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(106, 'Karkidaka Vavu', 'Friday', '25 July 2025', 'Kerala', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(107, 'Haryali Teej', 'Sunday', '27 July 2025', 'Haryana', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(108, 'Bonalu', 'Tuesday', '29 July 2025', 'Telangana', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(109, 'Shaheed Udham Singh Martyrdom Day', 'Thursday', '31 July 2025', 'Haryana', '2025-04-15 17:52:15', '2025-04-15 17:52:15'),
(110, 'Ker Puja', 'Sunday', '03 August 2025', 'Tripura', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(111, 'Tendong Lho Rum Faat', 'Friday', '08 August 2025', 'Sikkim, Odisha', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(112, 'Raksha Bandhan', 'Saturday', '09 August 2025', 'Chattisgarh, Haryana, Madhya Pradesh, Rajasthan, Uttarakhand, Uttar Pradesh', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(113, 'Patriots Day', 'Wednesday', '13 August 2025', 'Manipur', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(114, 'Independence Day', 'Friday', '15 August 2025', 'Andhra Pradesh, Arunachal Pradesh, Assam, Bihar, Chattisgarh, Goa, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Karnataka, Kerala, Madhya Pradesh, Maharashtra, Manipur, Meghalaya, Mizoram, Nagaland, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura, Uttarakhand, Uttar Pradesh, West Bengal, Jammu and Kashmir, Delhi', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(115, 'Parsi New Year (Shahenshahi)', 'Friday', '15 August 2025', 'Maharashtra', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(116, 'Janmashtami', 'Saturday', '16 August 2025', 'Andhra Pradesh, Bihar, Chattisgarh, Goa, Haryana, Himachal Pradesh, Jharkhand, Madhya Pradesh, Manipur, Meghalaya, Mizoram, Nagaland, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(117, 'Parsi New Year', 'Saturday', '16 August 2025', 'Gujarat, Maharashtra', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(118, 'Rakshabandhan', 'Tuesday', '19 August 2025', 'Gujarat, Himachal Pradesh, Rajasthan, Uttar Pradesh, Uttarakhand', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(119, 'Shri Krishna Astami', 'Tuesday', '26 August 2025', 'Gujarat, Sikkim, Rajasthan, Uttar Pradesh, Uttarakhand', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(120, 'Hartalika Teej', 'Tuesday', '26 August 2025', 'Chattisgarh, Sikkim', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(121, 'Ganesh Chaturthi', 'Tuesday', '26 August 2025', 'Karnataka, Kerala', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(122, 'Ganesh Chaturthi', 'Wednesday', '27 August 2025', 'Andhra Pradesh, Goa, Gujarat, Maharashtra, Odisha, Punjab, Sikkim, Tamil Nadu, Telangana', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(123, 'Ganesh Chaturthi', 'Thursday', '28 August 2025', 'Goa, Gujarat', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(124, 'Nuakhai', 'Thursday', '28 August 2025', 'Odisha, Punjab, Sikkim', '2025-04-15 17:58:48', '2025-04-15 17:58:48'),
(125, 'Teja Dashmi', 'Tuesday', '02 September 2024', 'Rajasthan', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(126, 'Tithi of Srimanta Shankardev', 'Thursday', '04 September 2024', 'Assam', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(127, 'Tithi of Srimanta Shankardev', 'Friday', '05 September 2024', 'Assam', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(128, 'Eid e Milad', 'Friday', '05 September 2025', 'Andhra Pradesh, Haryana, Jharkhand, Karnataka, Madhya Pradesh, Kerala, Maharashtra, Mizoram, Nagaland, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura, Uttarakhand', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(129, 'Vinayak Chaturthi', 'Sunday', '07 September 2025', 'Goa, Maharashtra, Odisha, Tamil Nadu, Telangana', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(130, 'Indra Jatra', 'Sunday', '07 September 2025', 'Sikkim', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(131, 'Ganesh Chaturthi', 'Monday', '08 September 2025', 'Goa', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(132, 'Ram Dev Jayanti/ Teja Dashmi', 'Saturday', '13 September 2025', 'Rajasthan', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(133, 'Karma Puja', 'Sunday', '14 September 2025', 'Jharkhand', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(134, 'Milad-Un-Nabi', 'Tuesday', '16 September 2025', 'Andhra Pradesh, Manipur', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(135, 'Id-E-Milad', 'Tuesday', '16 September 2025', 'Gujarat, Uttarakhand, Uttar Pradesh', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(136, 'Onam', 'Tuesday', '16 September 2025', 'Kerala', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(137, 'Mahalaya Amavasye', 'Sunday', '21 September 2025', 'Karnataka, Kerala, Odisha, Punjab, Sikkim, Tripura, West Bengal', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(138, 'Maharaja Agrasen Jayanti', 'Monday', '22 September 2025', 'Haryana', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(139, 'Ghatasthapana', 'Monday', '22 September 2025', 'Rajasthan', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(140, 'First Day of Bathukamma', 'Monday', '22 September 2025', 'Telangana', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(141, 'Shaheedi Diwas', 'Tuesday', '23 September 2025', 'Haryana', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(142, 'Heroes\' Martyrdom Day', 'Tuesday', '23 September 2025', 'Haryana', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(143, 'Maha Saptami', 'Monday', '29 September 2025', 'Assam, Odisha, Punjab, Sikkim, Tripura, West Bengal', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(144, 'Maha Ashtami', 'Tuesday', '30 September 2025', 'Andhra Pradesh, Assam, Jharkhand, Manipur, Odisha, Punjab, Rajasthan, Sikkim, Telangana, Tripura', '2025-04-16 09:09:37', '2025-04-16 09:09:37'),
(145, 'Maha Navami', 'Wednesday', '01 October 2025', 'Bihar, Jharkhand, Karnataka, Kerala, Meghalaya, Nagaland, Odisha, Sikkim, Tamil Nadu, Telangana, Tripura, West Bengal', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(146, 'Mahatma Gandhi Jayanthi', 'Thursday', '02 October 2025', 'Andhra Pradesh, Arunachal Pradesh, Assam, Bihar, Chattisgarh, Goa, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Karnataka, Kerala, Madhya Pradesh, Maharashtra, Manipur, Meghalaya, Mizoram, Nagaland, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura, Uttarakhand, Uttar Pradesh, West Bengal, Jammu and Kashmir, Delhi', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(147, 'Mahalaya', 'Thursday', '02 October 2025', 'West Bengal', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(148, 'Vijaya Dashami', 'Thursday', '02 October 2025', 'Bihar, Karnataka, Kerala, Maharashtra, Meghalaya', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(149, 'Maharaja Agrasen Jayanti', 'Friday', '03 October 2025', 'Haryana', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(150, 'Ghatasthapana', 'Friday', '03 October 2025', 'Rajasthan', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(151, 'Lakshmi Puja', 'Monday', '06 October 2025', 'Odisha, Punjab, Sikkim, Tripura, West Bengal', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(152, 'Maharishi Valmiki Jayanti', 'Tuesday', '07 October 2025', 'Haryana, Himachal Pradesh, Karnataka, Kerala, Madhya Pradesh, Punjab', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(153, 'Maha Saptami', 'Friday', '10 October 2025', 'Assam, Tripura, West Bengal, Jammu and Kashmir, Delhi', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(154, 'Durga Puja', 'Saturday', '11 October 2025', 'Manipur, Sikkim', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(155, 'Dussera (Maha Ashtami)', 'Saturday', '11 October 2025', 'Assam, Rajasthan', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(156, 'Ayudha Pooja', 'Saturday', '11 October 2025', 'Tamil Nadu', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(157, 'Vijaya Dashami', 'Saturday', '11 October 2025', 'Gujarat', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(158, 'Wangala Festival', 'Saturday', '11 October 2025', 'Meghalaya', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(159, 'Maha Navami', 'Saturday', '11 October 2025', 'Tamil Nadu', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(160, 'Durga Puja', 'Sunday', '12 October 2025', 'Assam, Bihar, West Bengal', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(161, 'Dussehra/Vijaya Dashmi', 'Sunday', '12 October 2025', 'Chattisgarh, Goa, Gujarat, Madhya Pradesh, Uttar Pradesh, Rajasthan, Tamil Nadu', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(162, 'Vijaya Dashami', 'Sunday', '12 October 2025', 'Chattisgarh, Goa', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(163, 'Janmostav of Srimanta Shankardev', 'Monday', '13 October 2025', 'Assam', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(164, 'Vijaya Dashami', 'Monday', '13 October 2025', 'Goa, Tamil Nadu, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(165, 'Laxmi Puja', 'Thursday', '16 October 2025', 'Tripura, West Bengal, Jammu and Kashmir, Delhi', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(166, 'Maharishi Valmiki Jayanti', 'Friday', '17 October 2025', 'Himachal Pradesh, Karnataka', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(167, 'Kati Bihu', 'Friday', '17 October 2025', 'Assam', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(168, 'Karva Chauth', 'Monday', '20 October 2025', 'Himachal Pradesh', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(169, 'Diwali', 'Monday', '20 October 2025', 'Arunachal Pradesh, Assam, Bihar, Chattisgarh, Gujarat, Karnataka, Maharashtra, Meghalaya, Nagaland', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(170, 'Naraka Chaturdasi', 'Monday', '20 October 2025', 'Karnataka, Kerala', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(171, 'Diwali', 'Monday', '20 October 2025', 'Kerala', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(172, 'Diwali', 'Tuesday', '21 October 2025', 'Andhra Pradesh, Bihar, Goa, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Karnataka, Madhya Pradesh, Maharashtra, Manipur, Mizoram, Nagaland, Odisha, Punjab, Sikkim, Tamil Nadu, Telangana, Tripura', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(173, 'Diwali', 'Wednesday', '22 October 2025', 'Haryana, Karnataka, Maharashtra, Rajasthan, Uttarakhand, Uttar Pradesh', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(174, 'Vikram Samvat New Year', 'Wednesday', '22 October 2025', 'Gujarat', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(175, 'Bhai Dooj', 'Thursday', '23 October 2025', 'Gujarat, Sikkim, Uttarakhand, Uttar Pradesh', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(176, 'Ningol Chakkouba', 'Friday', '24 October 2025', 'Manipur', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(177, 'Chhath Puja', 'Monday', '27 October 2025', 'Bihar, Jharkhand', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(178, 'Chhath Puja', 'Tuesday', '28 October 2025', 'Bihar', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(179, 'Diwali', 'Friday', '31 October 2025', 'Assam, Goa, Punjab, Uttarakhand, Uttar Pradesh, West Bengal, Jammu and Kashmir, Delhi', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(180, 'Sardar Vallabhbhai Patel\'s Birthday', 'Friday', '31 October 2025', 'Gujarat', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(181, 'Narak Chaturdasi', 'Friday', '31 October 2025', 'Odisha', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(182, 'Kali Puja', 'Friday', '31 October 2025', 'West Bengal', '2025-04-16 09:16:04', '2025-04-16 09:16:04'),
(183, 'Kannada Rajyothsava', 'Saturday', '01 November 2025', 'Karnataka, Kerala', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(184, 'Diwali', 'Saturday', '01 November 2025', 'Assam, Sikkim, Rajasthan, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(185, 'Haryana Day', 'Saturday', '01 November 2025', 'Haryana', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(186, 'Diwali Amavasaya (Laxmi Pujan)', 'Saturday', '01 November 2025', 'Maharashtra', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(187, 'Kut', 'Saturday', '01 November 2025', 'Manipur', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(188, 'Vikram Savant, New Year Day', 'Sunday', '02 November 2025', 'Gujarat', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(189, 'Diwali (Bali Pratipada)', 'Sunday', '02 November 2025', 'Maharashtra', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(190, 'Gowardhan Puja', 'Sunday', '02 November 2025', 'Uttarakhand, Uttar Pradesh', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(191, 'Ningol Chakkouba', 'Monday', '03 November 2025', 'Manipur', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(192, 'Diwali', 'Monday', '03 November 2025', 'Sikkim', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(193, 'Bhai Duj', 'Monday', '03 November 2025', 'Uttar Pradesh, Rajasthan, Uttarakhand', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(194, 'Guru Nanak Jayanti', 'Wednesday', '05 November 2025', 'Arunachal Pradesh, Chattisgarh, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Madhya Pradesh, Maharashtra, Manipur, Mizoram, Nagaland, Punjab, Rajasthan, Tamil Nadu, Tripura, Uttarakhand, Uttar Pradesh', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(195, 'Karthika Purnima', 'Wednesday', '05 November 2025', 'Odisha, Punjab, Sikkim', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(196, 'Chhat Puja', 'Friday', '07 November 2025', 'Assam, West Bengal', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(197, 'Wangala Festival', 'Friday', '07 November 2025', 'Meghalaya', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(198, 'Chhat Puja', 'Saturday', '08 November 2025', 'Bihar', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(199, 'Kanakadasa Jayanti', 'Saturday', '08 November 2025', 'Karnataka, Kerala', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(200, 'Lhabab Duchen', 'Tuesday', '11 November 2025', 'Sikkim', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(201, 'Egas bagval', 'Wednesday', '12 November 2025', 'Uttarakhand', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(202, 'Guru Nanak Jayanti', 'Saturday', '15 November 2025', 'Assam, Rajasthan, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(203, 'Garia Puja', 'Thursday', '20 November 2025', 'Tripura', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(204, 'Seng Kut Snem', 'Sunday', '23 November 2025', 'Meghalaya', '2025-04-16 09:18:33', '2025-04-16 09:18:33'),
(205, 'Indigenous Faith Day', 'Monday', '01 December 2025', 'Arunachal Pradesh', '2025-04-16 09:20:44', '2025-04-16 09:20:44'),
(206, 'Feast of St. Francis Xavier', 'Wednesday', '03 December 2025', 'Goa', '2025-04-16 09:20:44', '2025-04-16 09:20:44'),
(207, 'Pa Togan Nengminja', 'Friday', '12 December 2025', 'Meghalaya', '2025-04-16 09:20:44', '2025-04-16 09:20:44'),
(208, 'Guru Ghasidas Jayanti', 'Thursday', '18 December 2025', 'Chattisgarh', '2025-04-16 09:20:44', '2025-04-16 09:20:44'),
(209, 'Death Anniversary of U SoSo Tham', 'Thursday', '18 December 2025', 'Meghalaya', '2025-04-16 09:20:44', '2025-04-16 09:20:44'),
(210, 'Goa Liberation Day', 'Friday', '19 December 2025', 'Goa', '2025-04-16 09:20:44', '2025-04-16 09:20:44'),
(211, 'Christmas', 'Wednesday', '24 December 2025', 'Meghalaya, Mizoram', '2025-04-16 09:20:44', '2025-04-16 09:20:44'),
(212, 'Christmas', 'Thursday', '25 December 2025', 'Andhra Pradesh, Arunachal Pradesh, Assam, Bihar, Chattisgarh, Goa, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Karnataka, Kerala, Madhya Pradesh, Maharashtra, Manipur, Meghalaya, Mizoram, Nagaland, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura, Uttarakhand, Uttar Pradesh, West Bengal, Jammu and Kashmir, Delhi', '2025-04-16 09:20:44', '2025-04-16 09:20:44'),
(213, 'Christmas', 'Friday', '26 December 2025', 'Meghalaya, Mizoram, Telangana', '2025-04-16 09:20:44', '2025-04-16 09:20:44'),
(214, 'Shaheed Udham Singh Jayanti', 'Friday', '26 December 2025', 'Haryana', '2025-04-16 09:20:44', '2025-04-16 09:20:44'),
(215, 'Guru Gobind Singh Jayanti', 'Saturday', '27 December 2025', 'Haryana, Punjab, Himachal Pradesh', '2025-04-16 09:20:44', '2025-04-16 09:20:44'),
(216, 'U Kiang Nangbah', 'Tuesday', '30 December 2025', 'Meghalaya', '2025-04-16 09:20:44', '2025-04-16 09:20:44'),
(217, 'Tamu Losar', 'Wednesday', '30 December 2025', 'Sikkim', '2025-04-16 09:20:44', '2025-04-16 09:20:44'),
(218, 'New Year\'s Day', 'Wednesday', '31 December 2025', 'Mizoram, Manipur', '2025-04-16 09:20:44', '2025-04-16 09:20:44'),
(230, 'Vasant Panchami', 'Monday', '03 February 2025', 'Haryana, Odisha, Punjab, Sikkim, Tamil Nadu, Tripura, West Bengal', '2025-04-16 12:46:54', '2025-04-16 12:46:54'),
(231, 'Losar', 'Monday', '10 February 2025', 'Sikkim', '2025-04-16 12:46:54', '2025-04-16 12:46:54'),
(232, 'Guru Ravidas Jayanti', 'Wednesday', '12 February 2025', 'Haryana, Himachal Pradesh, Madhya Pradesh, Mizoram, Punjab', '2025-04-16 12:46:54', '2025-04-16 12:46:54'),
(233, 'Vasanta Panchami', 'Friday', '14 February 2025', 'Odisha, West Bengal, Jammu and Kashmir, Delhi', '2025-04-16 12:46:54', '2025-04-16 12:46:54'),
(234, 'Saraswati Puja', 'Friday', '14 February 2025', 'Tripura, West Bengal', '2025-04-16 12:46:54', '2025-04-16 12:46:54'),
(235, 'Holi', 'Friday', '14 February 2025', 'Meghalaya, Nagaland', '2025-04-16 12:46:54', '2025-04-16 12:46:54'),
(236, 'Shab-E-Barat', 'Friday', '14 February 2025', 'Chattisgarh', '2025-04-16 12:46:54', '2025-04-16 12:46:54'),
(237, 'Lui-Ngai-Ni', 'Saturday', '15 February 2025', 'Manipur', '2025-04-16 12:46:54', '2025-04-16 12:46:54'),
(238, 'Chhatrapati Shivaji Maharaj Jayanti', 'Wednesday', '19 February 2025', 'Maharashtra', '2025-04-16 12:46:54', '2025-04-16 12:46:54'),
(239, 'Statehood Day', 'Thursday', '20 February 2025', 'Arunachal Pradesh', '2025-04-16 12:46:54', '2025-04-16 12:46:54'),
(240, 'State Day', 'Thursday', '20 February 2025', 'Mizoram', '2025-04-16 12:46:54', '2025-04-16 12:46:54'),
(241, 'Maha Shivaratri', 'Tuesday', '25 February 2025', 'Karnataka, Kerala', '2025-04-16 12:46:54', '2025-04-16 12:46:54'),
(242, 'Maha Shivaratri', 'Wednesday', '26 February 2025', 'Goa, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Karnataka, Madhya Pradesh', '2025-04-16 12:46:54', '2025-04-16 12:46:54'),
(266, 'Chapchar Kut', 'Saturday', '01 March 2025', 'Mizoram', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(267, 'Panchayati Raj Divas', 'Wednesday', '05 March 2025', 'Odisha, Punjab, Sikkim', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(268, 'Chapchar Kut', 'Friday', '07 March 2025', 'Mizoram', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(269, 'Mahashivratri', 'Saturday', '08 March 2025', 'Andhra Pradesh, Bihar, Uttar Pradesh, Rajasthan, Uttarakhand', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(270, 'Holi', 'Friday', '14 March 2025', 'Arunachal Pradesh, Bihar, Chhattisgarh, Goa, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Madhya Pradesh, Maharashtra, Meghalaya, Mizoram, Nagaland, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(271, 'Yaosang', 'Friday', '14 March 2025', 'Manipur', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(272, 'Yaosang 2nd Day', 'Friday', '14 March 2025', 'Manipur', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(273, 'Doljatra', 'Friday', '14 March 2025', 'West Bengal', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(274, 'Bihar Day', 'Saturday', '22 March 2025', 'Bihar', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(275, 'Shaheed Bhagat Singh\'s Martyrdom Day', 'Sunday', '23 March 2025', 'Haryana', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(276, 'Dol Jatra', 'Tuesday', '25 March 2025', 'Assam, West Bengal, Jammu and Kashmir, Delhi', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(277, 'Dhulandi', 'Tuesday', '25 March 2025', 'Rajasthan', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(278, 'Holi', 'Tuesday', '25 March 2025', 'Andhra Pradesh, Assam, Goa, Rajasthan, Uttar Pradesh, Uttarakhand', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(279, 'Holi', 'Wednesday', '26 March 2025', 'Bihar, Odisha', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(280, 'Yaosang 2nd Day', 'Wednesday', '26 March 2025', 'Manipur', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(281, 'Holi', 'Thursday', '27 March 2025', 'Bihar', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(282, 'Jamat-Ul-Vida', 'Friday', '28 March 2025', 'Chhattisgarh', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(283, 'Good Friday', 'Saturday', '29 March 2025', 'Assam, Bihar, Goa, Jharkhand, Madhya Pradesh, Manipur, Mizoram, Nagaland, Sikkim, Tamil Nadu, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(284, 'Ugadi', 'Sunday', '30 March 2025', 'Gujarat, Karnataka, Rajasthan, Telangana', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(285, 'Gudi Padwa', 'Sunday', '30 March 2025', 'Gujarat, Maharashtra', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(286, 'Telugu New Year', 'Sunday', '30 March 2025', 'Tamil Nadu', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(287, 'Easter', 'Sunday', '30 March 2025', 'Kerala', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(288, 'Idul Fitr', 'Monday', '31 March 2025', 'Multiple states across India', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(289, 'Odisha Day', 'Tuesday', '01 April 2025', 'Odisha, Punjab', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(290, 'Sarhul', 'Tuesday', '01 April 2025', 'Jharkhand', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(291, 'Idul Fitr Holiday', 'Tuesday', '01 April 2025', 'Telangana', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(292, 'Babu Jag Jivan Ram\'s Birthday', 'Saturday', '05 April 2025', 'Telangana, Andhra Pradesh', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(293, 'Ram Navami', 'Sunday', '06 April 2025', 'Andhra Pradesh, Bihar, Chhattisgarh, Gujarat, Haryana, Himachal Pradesh, Madhya Pradesh, Maharashtra, Manipur, Odisha, Punjab, Rajasthan, Sikkim, Telangana, Uttarakhand, Uttar Pradesh', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(294, 'Ugadi Festival', 'Wednesday', '09 April 2025', 'Telangana, Andhra Pradesh, Goa', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(295, 'Gudi Padwa', 'Wednesday', '09 April 2025', 'Maharashtra', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(296, 'Telugu New Year\'s Day', 'Wednesday', '09 April 2025', 'Tamil Nadu', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(297, 'Mahavir Jayanti', 'Thursday', '10 April 2025', 'Chhattisgarh, Haryana, Jharkhand, Karnataka, Kerala, Madhya Pradesh, Maharashtra, Mizoram, Punjab, Rajasthan, Tamil Nadu', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(298, 'Idul Fitr', 'Thursday', '10 April 2025', 'Assam, Rajasthan, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(299, 'Id-Ul-Fitr', 'Friday', '11 April 2025', 'Goa, Telangana, Tripura, Uttar Pradesh, West Bengal, Jammu and Kashmir, Delhi', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(300, 'Khutub-E-Ramzan', 'Friday', '11 April 2025', 'Karnataka', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(301, 'Bohag Bihu', 'Sunday', '13 April 2025', 'Assam', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(302, 'Cheiraoba', 'Sunday', '13 April 2025', 'Manipur', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(303, 'Maha Vishuba Sankranti', 'Sunday', '13 April 2025', 'Odisha, Punjab', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(304, 'Rongali Bihu', 'Monday', '14 April 2025', 'Assam', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(305, 'Dr B R Ambedkar Jayanti', 'Monday', '14 April 2025', 'Andhra Pradesh, Bihar, Goa, Jharkhand, Maharashtra, Sikkim, Tamil Nadu, Telangana, Uttarakhand, Uttar Pradesh, Chhattisgarh, Gujarat, Haryana, Himachal Pradesh, Karnataka, Madhya Pradesh, Odisha, Punjab, Rajasthan', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(306, 'Bihu', 'Monday', '14 April 2025', 'Arunachal Pradesh', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(307, 'Cheiraoba', 'Monday', '14 April 2025', 'Manipur', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(308, 'Vaisakhi', 'Monday', '14 April 2025', 'Punjab, Haryana, Himachal Pradesh', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(309, 'Puthandu', 'Monday', '14 April 2025', 'Tamil Nadu', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(310, 'Biju Festival', 'Monday', '14 April 2025', 'Tripura', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(311, 'Bohag Bihu', 'Monday', '14 April 2025', 'Assam', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(312, 'Biju Festival', 'Tuesday', '15 April 2025', 'Tripura', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(313, 'Himachal Day', 'Tuesday', '15 April 2025', 'Himachal Pradesh', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(314, 'Bohag Bihu', 'Tuesday', '15 April 2025', 'Assam', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(315, 'Ramzan Holiday', 'Wednesday', '16 April 2025', 'Tripura', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(316, 'Bohag Bihu', 'Wednesday', '16 April 2025', 'Assam', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(317, 'Basava Jayanti', 'Wednesday', '30 April 2025', 'Karnataka, Kerala', '2025-04-16 12:50:35', '2025-04-16 12:50:35'),
(318, 'New Year', 'Wednesday', '01 January 2025', 'Arunachal Pradesh, Assam, Goa, Manipur, Meghalaya, Mizoram, Nagaland, Odisha, Rajasthan, Sikkim, Tamil Nadu, Telangana', '2025-04-16 12:51:44', '2025-04-16 12:51:44'),
(319, 'New Year Holiday', 'Thursday', '02 January 2025', 'Mizoram', '2025-04-16 12:51:44', '2025-04-16 12:51:44'),
(320, 'Mannam Jayanthi', 'Thursday', '02 January 2025', 'Kerala', '2025-04-16 12:51:44', '2025-04-16 12:51:44'),
(321, 'Guru Gobind Singh Jayanti', 'Monday', '06 January 2025', 'Chandigarh', '2025-04-16 12:51:44', '2025-04-16 12:51:44'),
(322, 'Missionary Day', 'Saturday', '11 January 2025', 'Mizoram', '2025-04-16 12:51:44', '2025-04-16 12:51:44'),
(323, 'Imoinu Iratpa', 'Saturday', '11 January 2025', 'Manipur', '2025-04-16 12:51:44', '2025-04-16 12:51:44'),
(324, 'Makar Sankranti / Pongal / Magh Bihu', 'Tuesday', '14 January 2025', 'Andhra Pradesh, Gujarat, Karnataka, Odisha, Tamil Nadu, Telangana, West Bengal', '2025-04-16 12:51:44', '2025-04-16 12:51:44'),
(325, 'Thiruvalluvar Day', 'Wednesday', '15 January 2025', 'Tamil Nadu', '2025-04-16 12:51:44', '2025-04-16 12:51:44'),
(326, 'Uzhavar Thirunal', 'Thursday', '16 January 2025', 'Tamil Nadu', '2025-04-16 12:51:44', '2025-04-16 12:51:44'),
(327, 'Netaji Subhas Chandra Bose Jayanti', 'Thursday', '23 January 2025', 'Odisha, Tripura, West Bengal', '2025-04-16 12:51:44', '2025-04-16 12:51:44'),
(328, 'Republic Day', 'Sunday', '26 January 2025', 'All States', '2025-04-16 12:51:44', '2025-04-16 12:51:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `state_wise_bank_holiday`
--
ALTER TABLE `state_wise_bank_holiday`
  ADD PRIMARY KEY (`holiday_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `state_wise_bank_holiday`
--
ALTER TABLE `state_wise_bank_holiday`
  MODIFY `holiday_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=329;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
