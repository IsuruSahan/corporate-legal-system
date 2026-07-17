-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 16, 2026 at 08:13 AM
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
(3, 'P1', 'Program', NULL, NULL),
(4, 'News 1', 'News', NULL, NULL),
(5, 'T2', 'Teledrama', NULL, NULL),
(6, 'Natath ayek sura mathin', 'Teledrama', NULL, NULL),
(7, 'Hapan padura', 'Program', NULL, NULL),
(8, 'Bolt Anayak', 'Teledrama', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `episodes`
--

CREATE TABLE `episodes` (
  `id` int(11) NOT NULL,
  `content_item_id` int(11) DEFAULT NULL,
  `episode_number` int(11) DEFAULT NULL,
  `episode_title` varchar(255) DEFAULT NULL,
  `upload_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `episodes`
--

INSERT INTO `episodes` (`id`, `content_item_id`, `episode_number`, `episode_title`, `upload_date`) VALUES
(1, 3, 2, 'title 1', '2026-06-11'),
(2, 4, 0, 'N/A', '2026-06-25'),
(4, 7, 2, 'Titile test', '2026-06-19'),
(5, 6, 5, 'ada kotasaa', '2026-06-26'),
(6, 6, 6, 'heta kotasa', '2026-06-27'),
(7, 7, 3, 'harshana dissanayaka', '2026-06-25');

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
(8, 7, 8, 0, 8),
(10, 11, 10, 0, 0);

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
(2, NULL, 1, 1, 1000.00, 4, 1),
(4, NULL, 1, 1, 2000.00, 1, 1),
(6, NULL, 3, 6, 4000.00, 7, 1),
(7, NULL, 1, 1, 1000.00, 6, 1),
(8, NULL, 2, 6, 3000.00, 6, 1),
(9, NULL, 1, 4, 2000.00, 1, 5),
(10, NULL, 2, 1, 4000.00, 6, 2),
(11, NULL, 3, 4, 1500.00, 8, 6);

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
  `assigned_team` varchar(50) DEFAULT 'Content Editor Team'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `agency_id`, `client_id`, `schedule_name`, `reference_no`, `start_date`, `end_date`, `budget_allocated`, `created_by`, `status`, `assigned_team`) VALUES
(10, 1, 3, 'vxcvxv', NULL, '2026-06-21', '2026-06-26', 423432.00, 5, 'Active', 'Content Editor Team'),
(11, 1, 3, 'Chocalate busicutt', 'sw-01', '2026-06-22', '2026-06-19', 50000.00, 5, 'Active', 'Content Editor Team'),
(12, 3, 2, 'Laptop', 'sw342', '2026-06-23', '2026-06-27', 20000.00, 5, 'Active', 'News Team'),
(14, 1, 3, 'cream cracker', 'fdgdfg', '2026-06-22', '2026-06-27', 2000.00, 5, 'Active', 'Content Editor Team'),
(15, 3, 2, 'marie', 'vbfg', '2026-06-22', '2026-06-27', 6000.00, 5, 'Active', 'Content Editor Team'),
(30, 3, 1, 'nn', 'n', '2026-06-22', '2026-06-26', 1000.00, 5, 'Pending Approval', 'Content Editor Team'),
(33, 1, 3, 'bbbb', 'bbb', '2026-06-22', '2026-06-26', 10000.00, 2, 'Active', 'News Team'),
(35, 1, 3, 'nnn', 'nnn', '2026-06-22', '2026-06-19', 20000.00, 2, 'Active', 'News Team'),
(36, 3, 1, 'nnnnnnn', 'nnnnnnnn', '2026-06-22', '2026-06-27', 20000.00, 2, 'Active', 'News Team'),
(37, 1, 3, 'jjjjj', 'nnnnnnnn', '2026-06-22', '2026-06-26', 200000000.00, 2, 'Active', 'News Team');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_items`
--

CREATE TABLE `schedule_items` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `episode_id` int(11) DEFAULT NULL,
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

INSERT INTO `schedule_items` (`id`, `schedule_id`, `episode_id`, `content_item_id`, `platform_id`, `placement_id`, `quantity`, `media_file`, `cost`) VALUES
(5, 10, 4, 7, 3, 4, 1, NULL, NULL),
(6, 11, 4, 7, 3, 4, 1, NULL, NULL),
(8, 14, 5, 6, 1, 1, 2, NULL, 2000.00),
(9, 15, 6, 6, 2, 6, 1, NULL, 3000.00),
(24, 30, 6, 6, 2, 6, 1, NULL, 3000.00),
(26, 33, 4, 7, 3, 6, 2, NULL, 8000.00),
(27, 35, 6, 6, 2, 1, 2, NULL, 8000.00),
(28, 36, 6, 6, 1, 1, 2, NULL, 2000.00),
(29, 37, 6, 6, 1, 1, 4, NULL, 4000.00);

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
-- Indexes for table `episodes`
--
ALTER TABLE `episodes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_item_id` (`content_item_id`);

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
  ADD KEY `placement_id` (`placement_id`),
  ADD KEY `episode_id` (`episode_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `episodes`
--
ALTER TABLE `episodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `media_attachments`
--
ALTER TABLE `media_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `schedule_items`
--
ALTER TABLE `schedule_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

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
-- Constraints for table `episodes`
--
ALTER TABLE `episodes`
  ADD CONSTRAINT `episodes_ibfk_1` FOREIGN KEY (`content_item_id`) REFERENCES `content_items` (`id`);

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
