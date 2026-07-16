-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 18, 2026 at 10:57 AM
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
-- Database: `swarnawahini_scheduler_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `ad_placements`
--

CREATE TABLE `ad_placements` (
  `id` int(11) NOT NULL,
  `placement_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ad_placements`
--

INSERT INTO `ad_placements` (`id`, `placement_name`) VALUES
(1, 'Mid role'),
(4, 'Crowlers'),
(6, 'End role'),
(7, 'Lcrolers');

-- --------------------------------------------------------

--
-- Table structure for table `agencies`
--

CREATE TABLE `agencies` (
  `id` int(11) NOT NULL,
  `agency_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agencies`
--

INSERT INTO `agencies` (`id`, `agency_name`) VALUES
(1, 'Ad craft'),
(2, 'Ad agency'),
(3, 'Ad world');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `agency_id` int(11) NOT NULL,
  `client_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `agency_id`, `client_name`) VALUES
(1, 3, 'Kandos'),
(2, 3, 'Asus'),
(3, 1, 'Munchie');

-- --------------------------------------------------------

--
-- Table structure for table `content_items`
--

CREATE TABLE `content_items` (
  `id` int(11) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `type` enum('Teledrama','Program','News') DEFAULT NULL,
  `series_id` int(11) DEFAULT NULL,
  `episode_number` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_items`
--

INSERT INTO `content_items` (`id`, `name`, `type`, `series_id`, `episode_number`) VALUES
(1, 'Jahutaa', 'Teledrama', NULL, NULL),
(2, 'Sinto', 'Program', NULL, NULL),
(4, 'News 1', 'News', NULL, NULL),
(6, 'Natath ayek sura mathin', 'Teledrama', NULL, NULL),
(7, 'Hapan padura', 'Program', NULL, NULL),
(8, 'Bolt Anayak', 'Teledrama', NULL, NULL),
(9, 'Rata watee', 'Program', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `rate_card_id` int(11) NOT NULL,
  `total_capacity` int(11) DEFAULT 0,
  `reserved_qty` int(11) NOT NULL DEFAULT 0,
  `used_qty` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `rate_card_id`, `total_capacity`, `reserved_qty`, `used_qty`) VALUES
(19, 15, 100, 0, -4),
(20, 16, 100, 0, 0),
(21, 17, 100, 0, 11),
(22, 18, 100, 0, 2),
(23, 19, 100, 0, 0),
(24, 20, 100, 0, 0),
(25, 21, 100, 0, 0),
(26, 22, 100, 0, 0),
(27, 23, 100, 0, 0),
(28, 24, 100, 0, 0),
(29, 25, 150, 0, 100),
(30, 26, 100, 0, 0),
(31, 27, 100, 0, 1),
(32, 28, 50, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `media_attachments`
--

CREATE TABLE `media_attachments` (
  `id` int(11) NOT NULL,
  `schedule_item_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_reference` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media_attachments`
--

INSERT INTO `media_attachments` (`id`, `schedule_item_id`, `file_path`, `file_reference`, `uploaded_at`) VALUES
(61, 63, '/uploads/mock_video_1.mp4', NULL, '2026-06-18 05:05:02'),
(62, 64, '/uploads/mock_video_1.mp4', NULL, '2026-06-18 05:05:02'),
(63, 65, '/uploads/mock_video_1.mp4', NULL, '2026-06-18 05:05:02'),
(64, 66, '/uploads/mock_video_1.mp4', NULL, '2026-06-18 05:05:02'),
(65, 67, '/uploads/mock_video_1.mp4', NULL, '2026-06-18 05:05:02'),
(66, 68, '/uploads/mock_video_1.mp4', NULL, '2026-06-18 05:16:24'),
(67, 69, '/uploads/mock_video_1.mp4', NULL, '2026-06-18 05:16:24'),
(68, 70, '/uploads/mock_video_1.mp4', NULL, '2026-06-18 05:16:24'),
(69, 71, '/uploads/mock_video_1.mp4', NULL, '2026-06-18 05:16:24'),
(70, 72, '/uploads/mock_video_1.mp4', NULL, '2026-06-18 05:16:24'),
(73, 75, '../uploads/1781763873_images-2026-04-07T090616.952.jpg', 'gd', '2026-06-18 06:24:33'),
(74, 75, '../uploads/1781763873_images-3.png', 'dfgg', '2026-06-18 06:24:33'),
(75, 76, '../uploads/1781769466_inve.jpg', 'fsdfsdf', '2026-06-18 07:57:46'),
(76, 76, '../uploads/1781769466_m1_.jpg', 'jhghj', '2026-06-18 07:57:46'),
(77, 77, '../uploads/1781769466_Registration_eService_-_RAMIS.pdf', '', '2026-06-18 07:57:46'),
(78, 89, '../uploads/1781771470_create.php', 'grdgf', '2026-06-18 08:31:10'),
(79, 89, '../uploads/1781771470_manage.php', 'dgfsfdsf', '2026-06-18 08:31:10'),
(80, 90, '../uploads/1781771470_update_item.php', 'ghfh', '2026-06-18 08:31:10');

-- --------------------------------------------------------

--
-- Table structure for table `media_formats`
--

CREATE TABLE `media_formats` (
  `id` int(11) NOT NULL,
  `format_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media_formats`
--

INSERT INTO `media_formats` (`id`, `format_name`) VALUES
(1, 'Full Video'),
(2, 'Clip'),
(5, 'Test'),
(6, 'Recap');

-- --------------------------------------------------------

--
-- Table structure for table `platforms`
--

CREATE TABLE `platforms` (
  `id` int(11) NOT NULL,
  `platform_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `platforms`
--

INSERT INTO `platforms` (`id`, `platform_name`) VALUES
(1, 'Youtube'),
(2, 'TikTok'),
(3, 'Facebook');

-- --------------------------------------------------------

--
-- Table structure for table `rate_cards`
--

CREATE TABLE `rate_cards` (
  `id` int(11) NOT NULL,
  `category` enum('Drama','Program','News') DEFAULT NULL,
  `platform_id` int(11) DEFAULT NULL,
  `placement_id` int(11) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT NULL,
  `content_item_id` int(11) DEFAULT NULL,
  `media_format_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rate_cards`
--

INSERT INTO `rate_cards` (`id`, `category`, `platform_id`, `placement_id`, `rate`, `content_item_id`, `media_format_id`) VALUES
(15, NULL, 3, 4, 3753.00, 6, 2),
(16, NULL, 2, 7, 4386.00, 4, 5),
(17, NULL, 3, 4, 3289.00, 6, 1),
(18, NULL, 3, 1, 4954.00, 8, 5),
(19, NULL, 1, 6, 2291.00, 2, 1),
(20, NULL, 1, 1, 1319.00, 9, 5),
(21, NULL, 1, 7, 4392.00, 2, 6),
(22, NULL, 2, 7, 737.00, 9, 6),
(23, NULL, 2, 7, 4670.00, 7, 2),
(24, NULL, 2, 1, 3584.00, 7, 6),
(25, NULL, 1, 7, 4425.00, 8, 6),
(26, NULL, 2, 1, 3265.00, 9, 6),
(27, NULL, 2, 6, 1836.00, 4, 1),
(28, NULL, 2, 4, 2000.00, 6, 6);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'Admin'),
(2, 'Scheduler'),
(3, 'Marketing Officer'),
(4, 'Editor');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `agency_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `schedule_name` varchar(150) NOT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `budget_allocated` decimal(15,2) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `assigned_team` varchar(50) DEFAULT 'Content Editor Team',
  `final_cost` decimal(10,2) DEFAULT 0.00,
  `days_run` int(11) DEFAULT 0,
  `total_days` int(11) DEFAULT 0,
  `remaining_budget` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `agency_id`, `client_id`, `schedule_name`, `reference_no`, `start_date`, `end_date`, `budget_allocated`, `created_by`, `status`, `assigned_team`, `final_cost`, `days_run`, `total_days`, `remaining_budget`) VALUES
(10, 1, 3, 'vxcvxv', NULL, '2026-06-21', '2026-06-26', 423432.00, 5, 'Active', 'Content Editor Team', 0.00, 0, 0, 0.00),
(11, 1, 3, 'Chocalate busicutt', 'sw-01', '2026-06-22', '2026-06-19', 50000.00, 5, 'Stopped', 'Content Editor Team', 50000.00, 0, 0, 0.00),
(12, 3, 2, 'Laptop', 'sw342', '2026-06-23', '2026-06-27', 20000.00, 5, 'Stopped', 'News Team', 20000.00, 0, 0, 0.00),
(14, 1, 3, 'cream cracker', 'fdgdfg', '2026-06-22', '2026-06-27', 2000.00, 5, 'Stopped', 'Content Editor Team', 1666.67, 0, 0, 0.00),
(15, 3, 2, 'marie', 'vbfg', '2026-06-22', '2026-06-27', 6000.00, 5, 'Stopped', 'Content Editor Team', 5000.00, 0, 0, 0.00),
(30, 3, 1, 'nn', 'n', '2026-06-22', '2026-06-26', 1000.00, 5, 'Pending Approval', 'Content Editor Team', 0.00, 0, 0, 0.00),
(33, 1, 3, 'bbbb', 'bbb', '2026-06-22', '2026-06-26', 10000.00, 2, 'Stopped', 'News Team', 10000.00, 0, 0, 0.00),
(43, 3, 1, 'Test', 'test', '2026-06-18', '2026-06-27', 20000.00, 5, 'Stopped', 'News Team', 2000.00, 1, 10, 18000.00),
(59, 1, 2, 'Mega Fest 36', 'TEST-363', '2026-06-20', '2026-06-30', 50000.00, NULL, 'Stopped', 'News Team', 9090.91, 2, 11, 40909.09),
(60, 1, 2, 'Brand Blast 34', 'TEST-356', '2026-06-20', '2026-06-30', 50000.00, NULL, 'Stopped', 'News Team', 9090.91, 2, 11, 40909.09),
(61, 3, 2, 'Mega Blast 77', 'TEST-330', '2026-06-20', '2026-06-30', 50000.00, NULL, 'Stopped', 'News Team', 9090.91, 2, 11, 40909.09),
(62, 3, 1, 'Brand Blast 59', 'TEST-568', '2026-06-20', '2026-06-30', 50000.00, NULL, 'Stopped', 'News Team', 9090.91, 2, 11, 40909.09),
(63, 2, 2, 'Mega Blast 71', 'TEST-333', '2026-06-20', '2026-06-30', 50000.00, NULL, 'Stopped', 'News Team', 9090.91, 2, 11, 40909.09),
(64, 1, 2, 'Flash Launch 11', 'TEST-907', '2026-06-20', '2026-06-30', 50000.00, NULL, 'Active', 'News Team', 0.00, 0, 0, 0.00),
(65, 3, 2, 'Digital Fest 86', 'TEST-261', '2026-06-20', '2026-06-30', 50000.00, NULL, 'Active', 'News Team', 0.00, 0, 0, 0.00),
(66, 1, 1, 'Mega Launch 32', 'TEST-967', '2026-06-20', '2026-06-30', 50000.00, NULL, 'Stopped', 'News Team', 9090.91, 2, 11, 40909.09),
(67, 3, 1, 'Summer Promo 24', 'TEST-519', '2026-06-20', '2026-06-30', 50000.00, NULL, 'Stopped', 'News Team', 9090.91, 2, 11, 40909.09),
(68, 2, 3, 'Flash Campaign 44', 'TEST-433', '2026-06-20', '2026-06-30', 50000.00, NULL, 'Stopped', 'News Team', 9090.91, 2, 11, 40909.09),
(70, 3, 1, 'fdvsd', 'gddfg', '2026-06-18', '2026-06-29', 324325.00, 5, 'Stopped', 'News Team', 27027.08, 1, 12, 297297.92),
(71, 1, 3, 'bbbbbbb', 'dsfdsf', '2026-06-18', '2026-06-24', 436546.00, 5, 'Active', 'Content Editor Team', 0.00, 0, 0, 0.00),
(72, 1, 3, 'fsdfd', 'hgfjghj', '2026-06-18', '2026-06-30', 546456.00, 5, 'Active', 'Content Editor Team', 0.00, 0, 0, 0.00),
(75, 3, 1, 'uyu', 'tryr', '2026-06-18', '2026-06-27', 43545.00, 5, 'Active', 'Content Editor Team', 0.00, 0, 0, 0.00),
(76, 3, 1, 'uyu', 'tryr', '2026-06-18', '2026-06-27', 43545.00, 5, 'Active', 'Content Editor Team', 0.00, 0, 0, 0.00),
(77, 1, 3, 'mbvn', 'hgjh', '2026-06-18', '2026-06-30', 4654646.00, 5, 'Active', 'News Team', 0.00, 0, 0, 0.00),
(78, 1, 3, 'mbvn', 'hgjh', '2026-06-18', '2026-06-30', 4654646.00, 5, 'Active', 'News Team', 0.00, 0, 0, 0.00),
(79, 3, 1, 'nn', 'fsd', '2026-06-18', '2026-06-30', 4535.00, 5, 'Active', 'News Team', 0.00, 0, 0, 0.00),
(80, 3, 1, 'nn', 'fsd', '2026-06-18', '2026-06-30', 4535.00, 5, 'Active', 'News Team', 0.00, 0, 0, 0.00),
(81, 3, 1, 'nn', 'fsd', '2026-06-18', '2026-06-30', 4535.00, 5, 'Active', 'News Team', 0.00, 0, 0, 0.00),
(82, 3, 1, 'nn', 'fsd', '2026-06-18', '2026-06-30', 4535.00, 5, 'Active', 'News Team', 0.00, 0, 0, 0.00),
(83, 1, 3, 'fdsgfsdf', '675', '2026-06-18', '2026-06-23', 35445.00, 5, 'Active', 'Content Editor Team', 0.00, 0, 0, 0.00),
(84, 3, 2, 'gnd', 'ertet', '2026-06-18', '2026-06-30', 34235.00, 5, 'Active', 'News Team', 0.00, 0, 0, 0.00),
(85, 3, 2, 'gnd', 'ertet', '2026-06-18', '2026-06-30', 34235.00, 5, 'Active', 'News Team', 0.00, 0, 0, 0.00),
(86, 1, 3, 'Revello', 'gff', '2026-06-23', '2026-06-26', 67576.00, 5, 'Active', 'News Team', 0.00, 0, 0, 0.00),
(87, 3, 1, 'Kitkat', 'rwer', '2026-06-22', '2026-06-30', 5000.00, 5, 'Active', 'News Team', 0.00, 0, 0, 0.00),
(88, 3, 1, 'dfdsf', '', '2026-06-18', '2026-06-30', 455.00, 5, 'Pending Approval', 'Content Editor Team', 0.00, 0, 0, 0.00),
(89, 1, 1, 'Automated Test 1781772857', 'AUTO-1781772857', '2026-06-01', '2026-06-30', 9999999.00, 5, 'Active', 'News Team', 0.00, 0, 0, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `schedule_items`
--

CREATE TABLE `schedule_items` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `content_item_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL,
  `placement_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `media_file` varchar(255) DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_items`
--

INSERT INTO `schedule_items` (`id`, `schedule_id`, `content_item_id`, `platform_id`, `placement_id`, `quantity`, `media_file`, `cost`) VALUES
(5, 10, 7, 3, 4, 1, NULL, NULL),
(6, 11, 7, 3, 4, 1, NULL, NULL),
(8, 14, 6, 1, 1, 2, NULL, 2000.00),
(9, 15, 6, 2, 6, 1, NULL, 3000.00),
(24, 30, 6, 2, 6, 1, NULL, 3000.00),
(26, 33, 7, 3, 6, 2, NULL, 8000.00),
(36, 43, 6, 2, 1, 3, NULL, 12000.00),
(37, 43, 6, 1, 1, 1, NULL, 1000.00),
(63, 59, 8, 3, 1, 1, NULL, 4954.00),
(64, 60, 9, 1, 1, 1, NULL, 1319.00),
(65, 61, 2, 1, 7, 1, NULL, 4392.00),
(66, 62, 2, 1, 7, 1, NULL, 4392.00),
(67, 63, 9, 1, 1, 1, NULL, 1319.00),
(68, 64, 4, 2, 6, 1, NULL, 1836.00),
(69, 65, 8, 3, 1, 1, NULL, 4954.00),
(70, 66, 7, 2, 7, 5, NULL, 23350.00),
(71, 67, 8, 1, 7, 1, NULL, 4425.00),
(72, 68, 9, 2, 1, 1, NULL, 3265.00),
(75, 70, 6, 2, 4, 1, NULL, 2000.00),
(76, 71, 8, 1, 7, 1, NULL, 4425.00),
(77, 71, 6, 3, 4, 1, NULL, 3289.00),
(78, 75, 8, 1, 7, 1, NULL, 4425.00),
(79, 76, 8, 1, 7, 1, NULL, 4425.00),
(80, 77, 8, 1, 7, 1, NULL, 4425.00),
(81, 78, 8, 1, 7, 1, NULL, 4425.00),
(82, 79, 8, 1, 7, 1, NULL, 4425.00),
(83, 80, 8, 1, 7, 1, NULL, 4425.00),
(84, 81, 8, 1, 7, 1, NULL, 4425.00),
(85, 82, 8, 1, 7, 1, NULL, 4425.00),
(86, 83, 8, 1, 7, 1, NULL, 4425.00),
(87, 84, 8, 1, 7, 1, NULL, 4425.00),
(88, 85, 8, 1, 7, 1, NULL, 4425.00),
(89, 86, 8, 1, 7, 1, NULL, 4425.00),
(90, 86, 6, 3, 4, 1, NULL, 3753.00),
(91, 87, 8, 1, 7, 87, NULL, 384975.00),
(92, 88, 8, 3, 1, 1, NULL, 4954.00),
(93, 89, 6, 3, 4, 1, NULL, 3753.00);

-- --------------------------------------------------------

--
-- Table structure for table `teledrama_series`
--

CREATE TABLE `teledrama_series` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role_id`) VALUES
(2, 'Admin User', 'admin@swarnawahini.lk', '$2y$10$SdQFQYEifXcNIdHH3CB4f.0ic9TgGwrRRhm9M6JiWYzZMOGmJ9qTC', 1),
(5, 'Rumesh', 'rumesh@swarnawahini.lk', '$2y$10$e.r1WcPsHNYXeL4QD9vdxe5N/j0wCMMbl8hoLAuJAu/RLogbACGtq', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ad_placements`
--
ALTER TABLE `ad_placements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `agencies`
--
ALTER TABLE `agencies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agency_id` (`agency_id`);

--
-- Indexes for table `content_items`
--
ALTER TABLE `content_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `series_id` (`series_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rate_card_id` (`rate_card_id`);

--
-- Indexes for table `media_attachments`
--
ALTER TABLE `media_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedule_item_id` (`schedule_item_id`);

--
-- Indexes for table `media_formats`
--
ALTER TABLE `media_formats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `platforms`
--
ALTER TABLE `platforms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rate_cards`
--
ALTER TABLE `rate_cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `platform_id` (`platform_id`),
  ADD KEY `placement_id` (`placement_id`),
  ADD KEY `content_item_id` (`content_item_id`),
  ADD KEY `fk_media_format` (`media_format_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agency_id` (`agency_id`);

--
-- Indexes for table `schedule_items`
--
ALTER TABLE `schedule_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `content_item_id` (`content_item_id`),
  ADD KEY `platform_id` (`platform_id`),
  ADD KEY `placement_id` (`placement_id`);

--
-- Indexes for table `teledrama_series`
--
ALTER TABLE `teledrama_series`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ad_placements`
--
ALTER TABLE `ad_placements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `agencies`
--
ALTER TABLE `agencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `content_items`
--
ALTER TABLE `content_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `media_attachments`
--
ALTER TABLE `media_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `media_formats`
--
ALTER TABLE `media_formats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `platforms`
--
ALTER TABLE `platforms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rate_cards`
--
ALTER TABLE `rate_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `schedule_items`
--
ALTER TABLE `schedule_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `teledrama_series`
--
ALTER TABLE `teledrama_series`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_items`
--
ALTER TABLE `content_items`
  ADD CONSTRAINT `content_items_ibfk_1` FOREIGN KEY (`series_id`) REFERENCES `teledrama_series` (`id`);

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`rate_card_id`) REFERENCES `rate_cards` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `media_attachments`
--
ALTER TABLE `media_attachments`
  ADD CONSTRAINT `media_attachments_ibfk_1` FOREIGN KEY (`schedule_item_id`) REFERENCES `schedule_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rate_cards`
--
ALTER TABLE `rate_cards`
  ADD CONSTRAINT `fk_media_format` FOREIGN KEY (`media_format_id`) REFERENCES `media_formats` (`id`),
  ADD CONSTRAINT `rate_cards_ibfk_1` FOREIGN KEY (`platform_id`) REFERENCES `platforms` (`id`),
  ADD CONSTRAINT `rate_cards_ibfk_2` FOREIGN KEY (`placement_id`) REFERENCES `ad_placements` (`id`),
  ADD CONSTRAINT `rate_cards_ibfk_3` FOREIGN KEY (`content_item_id`) REFERENCES `content_items` (`id`);

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`);

--
-- Constraints for table `schedule_items`
--
ALTER TABLE `schedule_items`
  ADD CONSTRAINT `schedule_items_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_items_ibfk_2` FOREIGN KEY (`content_item_id`) REFERENCES `content_items` (`id`),
  ADD CONSTRAINT `schedule_items_ibfk_3` FOREIGN KEY (`platform_id`) REFERENCES `platforms` (`id`),
  ADD CONSTRAINT `schedule_items_ibfk_4` FOREIGN KEY (`placement_id`) REFERENCES `ad_placements` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
