-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2025 at 08:55 AM
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
-- Database: `inn_nexus`
--

-- --------------------------------------------------------

--
-- Table structure for table `billing_transactions`
--

CREATE TABLE `billing_transactions` (
  `id` int(11) NOT NULL,
  `reservation_id` varchar(50) DEFAULT NULL,
  `transaction_type` enum('Room Charge','Service','Payment','Refund') DEFAULT 'Room Charge',
  `amount` decimal(10,2) NOT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL COMMENT 'Money the customer given to pay',
  `balance` decimal(10,2) DEFAULT NULL COMMENT 'Amount to be paid by the payment_amount',
  `change` decimal(10,2) DEFAULT NULL COMMENT 'payment_amount - balance (calculated)',
  `payment_method` enum('Cash','Card','GCash','Bank Transfer') DEFAULT 'Cash',
  `status` enum('Pending','Paid','Failed','Refunded') DEFAULT 'Pending',
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `billing_transactions`
--

INSERT INTO `billing_transactions` (`id`, `reservation_id`, `transaction_type`, `amount`, `payment_amount`, `balance`, `change`, `payment_method`, `status`, `transaction_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Room Charge', 555.00, 555.00, 0.00, 0.00, 'Card', 'Paid', '2025-10-15 04:33:09', 'Room charge for stay', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(2, NULL, 'Room Charge', 840.00, NULL, 840.00, NULL, 'Cash', 'Pending', '2025-10-15 04:33:09', 'Pending payment for room', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(3, NULL, 'Room Charge', 495.00, 495.00, 0.00, 0.00, 'GCash', 'Paid', '2025-10-15 04:33:09', 'Mobile payment for room', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(4, NULL, 'Room Charge', 900.00, 900.00, 0.00, 0.00, 'Bank Transfer', 'Paid', '2025-10-15 04:33:09', 'Bank transfer payment', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(5, NULL, 'Room Charge', 975.00, NULL, 975.00, NULL, 'Card', 'Pending', '2025-10-15 04:33:09', 'Credit card payment pending', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(6, NULL, 'Room Charge', 1220.00, 1220.00, 0.00, 0.00, 'Card', 'Paid', '2025-10-15 04:33:09', 'Room charge settled', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(7, NULL, 'Room Charge', 680.00, NULL, 680.00, NULL, 'Cash', 'Pending', '2025-10-15 04:33:09', 'Cash payment pending', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(8, NULL, 'Room Charge', 2100.00, 2100.00, 0.00, 0.00, 'Bank Transfer', 'Paid', '2025-10-15 04:33:09', 'Full payment received', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(9, 'RSV-TEST-001', 'Room Charge', 150.00, 150.00, 0.00, 0.00, 'Cash', 'Paid', '2025-10-14 16:00:00', 'Room charge for reservation RSV-TEST-001', '2025-10-15 04:33:10', '2025-10-15 04:33:10'),
(10, 'RSV-TEST-002', 'Room Charge', 200.00, 200.00, 0.00, 0.00, 'Card', 'Paid', '2025-10-14 16:00:00', 'Room charge for reservation RSV-TEST-002', '2025-10-15 04:33:10', '2025-10-15 04:33:10'),
(11, 'RSV-TEST-002', 'Service', 50.00, 50.00, 0.00, 0.00, 'Card', 'Paid', '2025-10-14 16:00:00', 'Additional service for reservation RSV-TEST-002', '2025-10-15 04:33:10', '2025-10-15 04:33:10'),
(12, 'RSV-TEST-003', 'Room Charge', 175.00, 175.00, 0.00, 0.00, 'Cash', 'Paid', '2025-10-14 16:00:00', 'Room charge for reservation RSV-TEST-003', '2025-10-15 04:33:10', '2025-10-15 04:33:10'),
(13, 'RSV-TEST-001', 'Payment', 1200.00, 1200.00, 1200.00, 0.00, 'Card', 'Paid', '2025-10-15 04:34:57', '', '2025-10-15 04:34:57', '2025-10-15 04:34:57'),
(14, 'RES-68EF2493947D3', 'Payment', 2800.00, 2800.00, 2800.00, 0.00, 'Card', 'Paid', '2025-10-15 04:37:29', 'test', '2025-10-15 04:37:29', '2025-10-15 04:37:29'),
(15, 'RES-68EF26593FED3', 'Payment', 3000.00, 400000.00, 3000.00, 397000.00, 'Cash', 'Paid', '2025-10-15 04:43:33', '', '2025-10-15 04:43:33', '2025-10-15 04:43:33'),
(16, 'RES-68EF268F543DC', 'Payment', 2000.00, 2000.00, 2000.00, 0.00, 'Bank Transfer', 'Paid', '2025-10-15 04:59:10', '', '2025-10-15 04:59:10', '2025-10-15 04:59:10'),
(17, 'RES-68EF2C59CEBC4', 'Payment', 10000.00, 10000.00, 10000.00, 0.00, 'Cash', 'Paid', '2025-10-15 05:11:19', '', '2025-10-15 05:11:19', '2025-10-15 05:11:19'),
(18, 'RES-68EF67FE86C05', 'Payment', 7000.00, 99999999.99, 7000.00, 99999999.99, 'Bank Transfer', 'Paid', '2025-10-15 09:25:01', '', '2025-10-15 09:25:01', '2025-10-15 09:25:01'),
(19, 'RES-68EF80F28B142', 'Payment', 2000.00, 2000.00, 2000.00, 0.00, 'Cash', 'Paid', '2025-10-15 13:51:59', '', '2025-10-15 13:51:59', '2025-10-15 13:51:59'),
(20, 'RES-68EF7E7E8E583', 'Payment', 2200.00, 2200.00, 2200.00, 0.00, 'GCash', 'Paid', '2025-10-15 13:53:24', '', '2025-10-15 13:53:24', '2025-10-15 13:53:24'),
(21, 'EVT-68F045580D6BE', 'Room Charge', 5000.00, 5000.00, 5000.00, 0.00, 'Cash', 'Pending', '2025-10-16 01:07:36', 'Event: Test Integration Event - Test Organizer', '2025-10-16 01:07:36', '2025-10-16 01:19:45'),
(22, 'EVT-68F045580E996', 'Room Charge', 5000.00, 5000.00, 5000.00, 0.00, 'Cash', 'Pending', '2025-10-16 01:07:36', 'Event: Test Integration Event - Test Organizer', '2025-10-16 01:07:36', '2025-10-16 01:19:45'),
(23, 'EVT-68F04B38DEB59', 'Room Charge', 0.00, 0.00, 0.00, 0.00, 'Cash', 'Pending', '2025-10-16 01:32:40', 'Event: bday - Nabunturan', '2025-10-16 01:32:40', '2025-10-16 01:32:40'),
(24, 'EVT-68F04B38DEB59', 'Payment', 4500.00, 4500.00, 4500.00, 0.00, 'Bank Transfer', 'Paid', '2025-10-16 04:53:50', '', '2025-10-16 04:53:50', '2025-10-16 04:53:50'),
(25, 'EVT-68F3B5E6B8C28', 'Room Charge', 10000.00, 10000.00, 10000.00, 0.00, 'Cash', 'Pending', '2025-10-18 15:44:38', 'Event: Hackaton - Sol', '2025-10-18 15:44:38', '2025-10-18 15:44:38'),
(26, 'RES-68F3E3CCE7DAB', 'Payment', 1200.00, 10250.00, 1200.00, 9050.00, 'Cash', 'Paid', '2025-10-19 10:56:40', '', '2025-10-19 10:56:40', '2025-10-19 10:56:40'),
(27, 'EVT-68F3B5E6B8C28', 'Payment', 10000.00, 10000.00, 10000.00, 0.00, 'Bank Transfer', 'Paid', '2025-10-19 10:57:03', '', '2025-10-19 10:57:03', '2025-10-19 10:57:03'),
(28, 'EVT-68F045580D6BE', 'Payment', 5000.00, 5000.00, 5000.00, 0.00, 'Cash', 'Paid', '2025-10-19 10:57:18', '', '2025-10-19 10:57:18', '2025-10-19 10:57:18'),
(29, 'EVT-68F045580E996', 'Payment', 5000.00, 5000.00, 5000.00, 0.00, 'Cash', 'Paid', '2025-10-19 10:57:25', '', '2025-10-19 10:57:25', '2025-10-19 10:57:25'),
(30, 'RES-68F01013A48A4', 'Payment', 2200.00, 9000.00, 2200.00, 6800.00, 'Cash', 'Paid', '2025-10-19 10:57:59', '', '2025-10-19 10:57:59', '2025-10-19 10:57:59'),
(31, 'RES-68EFFE6C45BB8', 'Payment', 3500.00, 9999.00, 3500.00, 6499.00, 'Cash', 'Paid', '2025-10-19 10:58:17', '', '2025-10-19 10:58:17', '2025-10-19 10:58:17'),
(32, 'EVT-68EFE78A7EE5E', 'Payment', 3000.00, 9999.00, 3000.00, 6999.00, 'Cash', 'Paid', '2025-10-19 10:58:24', '', '2025-10-19 10:58:24', '2025-10-19 10:58:24'),
(33, 'EVT-68EFE78A815F2', 'Payment', 3000.00, 9999.00, 3000.00, 6999.00, 'Cash', 'Paid', '2025-10-19 10:58:30', '', '2025-10-19 10:58:30', '2025-10-19 10:58:30'),
(34, 'RES-68EF2DF4A8CC2', 'Payment', 7000.00, 99999.00, 7000.00, 92999.00, 'Cash', 'Paid', '2025-10-19 10:58:35', '', '2025-10-19 10:58:35', '2025-10-19 10:58:35'),
(35, 'EVT-68F4CBF4C3B00', 'Room Charge', 6000.00, 6000.00, 6000.00, 0.00, 'Cash', 'Pending', '2025-10-19 11:31:00', 'Event: kantahan - Caagoy & Sol', '2025-10-19 11:31:00', '2025-10-19 11:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `channels`
--

CREATE TABLE `channels` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(200) NOT NULL,
  `type` enum('OTA','GDS','Direct','Wholesale','Corporate') DEFAULT 'OTA',
  `api_endpoint` varchar(500) DEFAULT NULL,
  `api_key` varchar(500) DEFAULT NULL,
  `username` varchar(200) DEFAULT NULL,
  `password` varchar(500) DEFAULT NULL,
  `status` enum('Active','Inactive','Maintenance','Error') DEFAULT 'Active',
  `commission_rate` decimal(5,2) DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'PHP',
  `timezone` varchar(50) DEFAULT 'Asia/Manila',
  `contact_person` varchar(200) DEFAULT NULL,
  `contact_email` varchar(200) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `last_sync` timestamp NULL DEFAULT NULL,
  `sync_status` enum('Success','Failed','In Progress','Pending') DEFAULT 'Pending',
  `sync_errors` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `channels`
--

INSERT INTO `channels` (`id`, `name`, `display_name`, `type`, `api_endpoint`, `api_key`, `username`, `password`, `status`, `commission_rate`, `currency`, `timezone`, `contact_person`, `contact_email`, `contact_phone`, `notes`, `last_sync`, `sync_status`, `sync_errors`, `created_at`, `updated_at`) VALUES
(1, 'booking_com', 'Booking.com', 'OTA', NULL, NULL, NULL, NULL, 'Active', 15.00, 'PHP', 'Asia/Manila', 'John Smith', 'john@booking.com', NULL, 'Primary OTA partner', NULL, 'Pending', NULL, '2025-10-16 06:05:12', '2025-10-16 06:05:12'),
(2, 'expedia', 'Expedia', 'OTA', NULL, NULL, NULL, NULL, 'Active', 12.00, 'PHP', 'Asia/Manila', 'Sarah Johnson', 'sarah@expedia.com', NULL, 'Secondary OTA partner', NULL, 'Pending', NULL, '2025-10-16 06:05:12', '2025-10-16 06:05:12'),
(3, 'agoda', 'Agoda', 'OTA', NULL, NULL, NULL, NULL, 'Active', 10.00, 'PHP', 'Asia/Manila', 'Mike Chen', 'mike@agoda.com', NULL, 'Regional OTA partner', NULL, 'Pending', NULL, '2025-10-16 06:05:12', '2025-10-16 06:05:12'),
(4, 'direct_booking', 'Direct Booking', 'Direct', NULL, NULL, NULL, NULL, 'Active', 0.00, 'PHP', 'Asia/Manila', 'Hotel Staff', 'reservations@hotel.com', NULL, 'Direct bookings from hotel website', NULL, 'Pending', NULL, '2025-10-16 06:05:12', '2025-10-16 06:05:12'),
(5, 'corporate', 'Corporate Bookings', 'Corporate', NULL, NULL, NULL, NULL, 'Active', 5.00, 'PHP', 'Asia/Manila', 'Corporate Sales', 'corporate@hotel.com', NULL, 'Corporate rate agreements', NULL, 'Pending', NULL, '2025-10-16 06:05:12', '2025-10-16 06:05:12');

-- --------------------------------------------------------

--
-- Table structure for table `channel_availability`
--

CREATE TABLE `channel_availability` (
  `id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `available_rooms` int(11) DEFAULT 0,
  `total_rooms` int(11) DEFAULT 0,
  `closed_to_arrival` tinyint(1) DEFAULT 0,
  `closed_to_departure` tinyint(1) DEFAULT 0,
  `minimum_stay` int(11) DEFAULT 1,
  `maximum_stay` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `channel_rates`
--

CREATE TABLE `channel_rates` (
  `id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `rate_type` enum('Base','Weekend','Holiday','Seasonal','Corporate') DEFAULT 'Base',
  `rate` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'PHP',
  `valid_from` date NOT NULL,
  `valid_to` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `channel_room_mappings`
--

CREATE TABLE `channel_room_mappings` (
  `id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `channel_room_id` varchar(100) NOT NULL,
  `local_room_id` int(11) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `channel_sync_logs`
--

CREATE TABLE `channel_sync_logs` (
  `id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `sync_type` enum('Rates','Availability','Bookings','Full') NOT NULL,
  `status` enum('Success','Failed','Partial') NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `records_processed` int(11) DEFAULT 0,
  `records_successful` int(11) DEFAULT 0,
  `records_failed` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `sync_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sync_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_campaigns`
--

CREATE TABLE `email_campaigns` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `recipient_type` enum('all_guests','loyalty_members','recent_guests','custom_list') NOT NULL,
  `status` enum('draft','scheduled','sent','cancelled') DEFAULT 'draft',
  `scheduled_date` datetime DEFAULT NULL,
  `sent_date` datetime DEFAULT NULL,
  `sent_count` int(11) DEFAULT 0,
  `open_count` int(11) DEFAULT 0,
  `click_count` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verification_tokens`
--

CREATE TABLE `email_verification_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `organizer_name` varchar(255) NOT NULL,
  `organizer_contact` varchar(255) DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `attendees_expected` int(11) DEFAULT 0,
  `setup_type` enum('Conference','Banquet','Theater','Classroom','U-Shape','Other') DEFAULT 'Conference',
  `room_blocks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`room_blocks`)),
  `price_estimate` decimal(10,2) DEFAULT 0.00,
  `status` enum('Pending','Ongoing','Cancelled') DEFAULT 'Pending',
  `created_by` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `organizer_name`, `organizer_contact`, `start_datetime`, `end_datetime`, `attendees_expected`, `setup_type`, `room_blocks`, `price_estimate`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Sol&amp;amp;amp;amp;#039;s 21st bday', '', 'Nabunturan', '09773670557', '2025-10-16 01:18:00', '2025-10-18 01:20:00', 26, 'Conference', '[\"11\"]', 69000.00, '', 'system', '2025-10-16 01:19:00', '2025-10-16 02:34:43'),
(2, 'Emman21st bday', 'azsxdfcgvbhjkml,', 'Nabunturan', '09773670557', '2025-10-16 01:21:00', '2025-10-17 01:21:00', 25, 'Conference', '[\"7\"]', 69000.00, '', 'System', '2025-10-16 01:21:57', '2025-10-16 02:34:33'),
(3, 'Test Event', 'Test event', 'Test Organizer', '123456789', '2025-10-20 10:00:00', '2025-10-20 12:00:00', 10, 'Banquet', '[\"3\",\"4\"]', 1000.00, '', 'System', '2025-10-16 01:54:43', '2025-10-16 02:34:08'),
(4, 'Test Event 2', 'Test event with Theater setup', 'Test Organizer', '123456789', '2025-10-21 10:00:00', '2025-10-21 12:00:00', 15, 'Theater', '[\"3\"]', 2000.00, '', 'System', '2025-10-16 01:55:38', '2025-10-16 02:24:47'),
(5, 'sol and caagoy&amp;amp;#039;s wedding aniversarry', 'versusan', 'Nabunturan', '09773670557', '2025-10-17 02:01:00', '2025-10-18 02:02:00', 2, 'Banquet', '[\"9\"]', 69000.00, '', 'System', '2025-10-16 02:02:50', '2025-10-16 02:34:22'),
(6, 'Test Status', 'Testing status and room blocks', 'Test Organizer', '123456789', '2025-10-22 10:00:00', '2025-10-22 12:00:00', 20, 'Conference', '[\"26\"]', 3000.00, '', 'System', '2025-10-16 02:26:25', '2025-10-16 02:41:08'),
(7, 'Test Status', 'Testing status and room blocks', 'Test Organizer', '123456789', '2025-10-22 10:00:00', '2025-10-22 12:00:00', 20, 'Conference', '[\"1\",\"2\"]', 3000.00, '', 'System', '2025-10-16 02:26:45', '2025-10-16 02:26:45'),
(8, 'Test Status', 'Testing status and room blocks', 'Test Organizer', '123456789', '2025-10-22 10:00:00', '2025-10-22 12:00:00', 20, 'Conference', '[\"23\"]', 3000.00, '', 'System', '2025-10-16 02:27:22', '2025-10-16 02:30:09'),
(9, 'Status Test', 'Testing status', 'Test', '123', '2025-10-23 10:00:00', '2025-10-23 12:00:00', 5, 'Theater', '[23]', 1000.00, 'Pending', 'System', '2025-10-16 02:27:56', '2025-10-16 06:56:06'),
(10, 'Status Test69', 'Testing Pending status', 'Test', '123', '2025-10-24 10:00:00', '2025-10-24 12:00:00', 5, 'Conference', '[]', 0.00, '', 'System', '2025-10-16 02:38:26', '2025-10-19 17:11:47'),
(11, 'Status Test Approved', 'Testing Approved status', 'Test', '123', '2025-10-25 10:00:00', '2025-10-25 12:00:00', 5, 'Conference', '[\"\"]', 1000.00, 'Cancelled', 'System', '2025-10-16 02:39:33', '2025-10-16 03:03:41'),
(12, 'Status Test Cancelled', 'Testing Cancelled status', 'Test', '123', '2025-10-26 10:00:00', '2025-10-26 12:00:00', 5, 'Conference', '[\"\"]', 1000.00, 'Cancelled', 'System', '2025-10-16 02:39:46', '2025-10-16 02:39:46'),
(13, 'cosplay con', '', 'naruto', '09560344827', '2025-10-20 05:03:00', '2025-10-23 03:01:00', 36, 'Theater', '[\"4\",\"6\"]', 50000.00, 'Pending', 'System', '2025-10-16 03:02:22', '2025-10-16 03:02:50'),
(14, 'brainrot', '', 'tralalelo tralala', '123456789', '2025-10-16 04:54:00', '2025-10-19 04:54:00', 45, 'Conference', '[\"14\",\"17\",\"19\"]', 89800.00, 'Pending', 'System', '2025-10-16 04:54:59', '2025-10-16 05:18:58'),
(15, 'test test', '', 'Nabunturan', '96374185', '2025-10-16 05:40:00', '2025-10-17 05:40:00', 3, 'Classroom', '[4]', 0.00, 'Pending', NULL, '2025-10-16 05:40:54', '2025-10-16 05:40:54'),
(16, 'testing', 'asdas', 'joseph', '123456789', '2025-10-16 06:17:00', '2025-10-16 10:21:00', 1, 'Classroom', '[3]', 990.00, 'Pending', NULL, '2025-10-16 06:17:51', '2025-10-16 06:17:51'),
(17, 'testing', '', 'otep', '123456789', '2025-10-16 06:28:00', '2025-10-17 06:28:00', 2, 'U-Shape', '[26]', 982.00, 'Pending', NULL, '2025-10-16 06:29:17', '2025-10-16 06:29:17'),
(18, 'testtt', 'fsada', 'Test', '04325', '2025-10-16 06:57:00', '2025-10-17 06:57:00', 4, 'Banquet', '[23]', 546.00, 'Pending', NULL, '2025-10-16 06:57:40', '2025-10-16 06:57:40'),
(19, 'spyxfam', 'family vac', 'loid', '093686216', '2025-10-16 07:08:00', '2025-10-17 07:08:00', 3, 'Banquet', '[12]', 789.00, '', NULL, '2025-10-16 07:08:38', '2025-10-16 07:08:38'),
(20, 'try talaga', 'wsd', 'ako na eto na', '09773670557', '2025-10-16 07:36:00', '2025-10-17 07:37:00', 3, 'Theater', '[\"1\"]', 456.00, 'Pending', NULL, '2025-10-16 07:37:36', '2025-10-16 07:37:36'),
(21, 'Test Integration Event', 'Testing event integration with billing and front desk', 'Test Organizer', '123-456-7890', '2025-10-16 14:00:00', '2025-10-16 18:00:00', 50, 'Conference', '[1,2]', 5000.00, '', 'test@example.com', '2025-10-16 09:07:36', '2025-10-16 09:07:36'),
(22, 'solos', '', 'naruto', '09560344827', '2025-10-16 09:25:00', '2025-10-17 09:25:00', 8, 'Banquet', '[]', 4600.00, '', NULL, '2025-10-16 09:25:45', '2025-10-16 09:28:32'),
(23, 'bday', '', 'Nabunturan', '96341252', '2025-10-16 09:32:00', '2025-10-17 09:32:00', 5, 'Banquet', '[\"24\"]', 4500.00, 'Pending', NULL, '2025-10-16 09:32:40', '2025-10-16 12:53:15'),
(24, 'Test Event', 'Test event description', 'Test Organizer', '123456789', '2025-10-20 10:00:00', '2025-10-20 12:00:00', 10, 'Conference', '[1,2]', 1000.00, 'Pending', 'test_user', '2025-10-16 14:58:22', '2025-10-16 14:58:22'),
(25, 'Hackaton', '', 'Sol', '098885161', '2025-10-18 23:44:00', '2025-10-19 23:44:00', 123, 'Classroom', '[\"30\"]', 10000.00, 'Ongoing', NULL, '2025-10-18 23:44:38', '2025-10-19 02:56:11'),
(26, 'Kantahan', '', 'Caagoy & Sol', '0966565512', '2025-10-19 19:22:00', '2025-10-20 19:22:00', 45, 'Other', '[]', 6000.00, 'Pending', NULL, '2025-10-19 19:23:10', '2025-10-19 19:24:09'),
(27, 'kantahan', '', 'Caagoy & Sol', '096341252', '2025-10-19 19:30:00', '2025-10-20 19:30:00', 45, 'Other', '[\"26\"]', 6000.00, 'Pending', NULL, '2025-10-19 19:31:00', '2025-10-19 19:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `event_reservations`
--

CREATE TABLE `event_reservations` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `reservation_id` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_reservations`
--

INSERT INTO `event_reservations` (`id`, `event_id`, `reservation_id`, `created_at`) VALUES
(1, 8, 'EVT-68EFE78A7EE5E', '2025-10-15 18:27:22'),
(2, 8, 'EVT-68EFE78A815F2', '2025-10-15 18:27:22'),
(3, 21, 'EVT-68F045580D6BE', '2025-10-16 01:07:36'),
(4, 21, 'EVT-68F045580E996', '2025-10-16 01:07:36'),
(5, 23, 'EVT-68F04B38DEB59', '2025-10-16 01:32:40'),
(6, 25, 'EVT-68F3B5E6B8C28', '2025-10-18 15:44:38'),
(7, 27, 'EVT-68F4CBF4C3B00', '2025-10-19 11:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `event_services`
--

CREATE TABLE `event_services` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `qty` int(11) DEFAULT 1,
  `price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guests`
--

CREATE TABLE `guests` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `id_type` enum('Passport','Driver License','National ID') DEFAULT 'National ID',
  `id_number` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guests`
--

INSERT INTO `guests` (`id`, `first_name`, `last_name`, `email`, `phone`, `address`, `city`, `country`, `id_type`, `id_number`, `date_of_birth`, `nationality`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Sarah', 'Johnson', 'sarah.johnson@email.com', '+1-555-0101', '123 Oak Street', 'New York', 'United States', 'Driver License', 'DL123456789', '1985-03-15', 'American', 'VIP customer, prefers quiet rooms', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(2, 'Michael', 'Chen', 'm.chen@techcorp.com', '+1-555-0102', '456 Pine Avenue', 'San Francisco', 'United States', 'Passport', 'P123456789', '1990-07-22', 'American', 'Business traveler, frequent guest', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(3, 'Emma', 'Williams', 'emma.w@email.com', '+44-20-7946-0103', '789 Elm Road', 'London', 'United Kingdom', 'Passport', 'P987654321', '1988-11-08', 'British', 'Family vacation, two children', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(4, 'David', 'Brown', 'd.brown@consulting.com', '+1-555-0104', '321 Maple Drive', 'Chicago', 'United States', 'National ID', 'SSN123456789', '1975-01-30', 'American', 'Conference attendee', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(5, 'Lisa', 'Anderson', 'lisa.anderson@email.com', '+46-8-525-0105', '654 Cedar Lane', 'Stockholm', 'Sweden', 'Passport', 'P555666777', '1982-05-18', 'Swedish', 'Honeymoon trip', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(6, 'James', 'Wilson', 'james.w@freelance.com', '+61-2-9374-0106', '987 Birch Boulevard', 'Sydney', 'Australia', 'Driver License', 'DL987654321', '1992-09-12', 'Australian', 'Digital nomad, long-term stay', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(7, 'Maria', 'Garcia', 'maria.garcia@email.com', '+34-91-445-0107', '147 Palm Street', 'Madrid', 'Spain', 'National ID', 'DNI12345678', '1978-12-03', 'Spanish', 'Cultural tour visitor', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(8, 'Robert', 'Taylor', 'r.taylor@engineering.com', '+1-555-0108', '258 Willow Way', 'Seattle', 'United States', 'Passport', 'P456789123', '1980-04-25', 'American', 'Tech conference speaker', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(9, 'Anna', 'Novak', 'anna.novak@email.cz', '+420-224-0109', '369 Spruce Street', 'Prague', 'Czech Republic', 'Passport', 'P789123456', '1995-08-14', 'Czech', 'Student traveler, budget conscious', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(10, 'Pierre', 'Dubois', 'pierre.dubois@business.fr', '+33-1-4276-0110', '741 Fir Avenue', 'Paris', 'France', 'National ID', 'IDF123456789', '1970-06-20', 'French', 'Wine tour, anniversary celebration', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(11, 'Yuki', 'Tanaka', 'yuki.tanaka@email.jp', '+81-3-3570-0111', '852 Cherry Blossom St', 'Tokyo', 'Japan', 'Passport', 'P321654987', '1987-02-28', 'Japanese', 'Business meeting in the city', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(12, 'Ahmed', 'Al-Rashid', 'ahmed.rashid@email.ae', '+971-4-331-0112', '963 Desert Palm Ave', 'Dubai', 'United Arab Emirates', 'Passport', 'P654987321', '1983-10-10', 'Emirati', 'Luxury shopping trip', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(13, 'Sofia', 'Rodriguez', 'sofia.r@travelblog.com', '+52-55-5208-0113', '159 Cactus Road', 'Mexico City', 'Mexico', 'Passport', 'P147258369', '1991-12-05', 'Mexican', 'Travel blogger, social media influencer', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(14, 'Thomas', 'Müller', 'thomas.mueller@email.de', '+49-30-2098-0114', '357 Linden Street', 'Berlin', 'Germany', 'National ID', 'IDG987654321', '1976-07-15', 'German', 'Music festival attendee', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(15, 'Isabella', 'Costa', 'isabella.costa@email.br', '+55-11-3069-0115', '468 Carnival Square', 'Rio de Janeiro', 'Brazil', 'Passport', 'P963852741', '1989-03-22', 'Brazilian', 'Carnival season visitor', '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(16, 'Ben', 'Ten', 'ten@gmail.com', '0977777777', 'omnitrix', 'planet namek', 'USA', 'National ID', '123456789', '2004-06-19', 'American', '', '2025-10-15 06:16:24', '2025-10-15 06:16:24'),
(17, 'Niel', 'Bryan', 'niel@gmail.com', '0999999', 'vicas', 'Caloocan City', 'Philippines', 'National ID', '12345678', '2007-10-05', 'Filipino', '', '2025-10-15 09:23:10', '2025-10-15 09:23:10'),
(18, 'Event', 'Test Organizer', 'event-test@hotel.com', '123-456-7890', 'Test Venue', 'Hotel', 'Philippines', '', 'EVT-21', '1990-01-01', 'Filipino', NULL, '2025-10-16 01:07:36', '2025-10-16 01:07:36'),
(19, 'Event', 'Nabunturan', 'event-nabunturan@hotel.com', '96341252', 'Event Venue', 'Hotel', 'Philippines', '', 'EVT-23', '1990-01-01', 'Filipino', NULL, '2025-10-16 01:32:40', '2025-10-16 01:32:40'),
(20, 'Event', 'Sol', 'event-sol@hotel.com', '098885161', 'Event Venue', 'Hotel', 'Philippines', '', 'EVT-25', '1990-01-01', 'Filipino', NULL, '2025-10-18 15:44:38', '2025-10-18 15:44:38'),
(21, 'Event', 'Caagoy & Sol', 'event-caagoy-&-sol@hotel.com', '0966565512', 'Event Venue', 'Hotel', 'Philippines', '', 'EVT-26', '1990-01-01', 'Filipino', NULL, '2025-10-19 11:23:10', '2025-10-19 11:23:10');

-- --------------------------------------------------------

--
-- Table structure for table `guest_loyalty_memberships`
--

CREATE TABLE `guest_loyalty_memberships` (
  `id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `loyalty_program_id` int(11) NOT NULL,
  `membership_number` varchar(50) NOT NULL,
  `points_balance` decimal(10,2) DEFAULT 0.00,
  `tier_level` enum('bronze','silver','gold','platinum') DEFAULT 'bronze',
  `enrolled_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping_tasks`
--

CREATE TABLE `housekeeping_tasks` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `task_type` enum('cleaning','deep_clean','maintenance','inspection') DEFAULT 'cleaning',
  `status` enum('pending','in-progress','completed','maintenance') DEFAULT 'pending',
  `priority` enum('normal','high','urgent') DEFAULT 'normal',
  `assigned_to` varchar(100) DEFAULT NULL,
  `guest_name` varchar(200) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `housekeeping_tasks`
--

INSERT INTO `housekeeping_tasks` (`id`, `room_id`, `room_number`, `task_type`, `status`, `priority`, `assigned_to`, `guest_name`, `notes`, `started_at`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 1, '101', 'cleaning', 'completed', 'normal', NULL, NULL, NULL, '2025-10-15 05:12:07', '2025-10-15 05:12:32', '2025-10-15 04:33:09', '2025-10-15 05:12:32'),
(2, 2, '102', 'cleaning', 'completed', 'normal', NULL, NULL, NULL, '2025-10-15 05:12:09', '2025-10-15 05:12:24', '2025-10-15 04:33:09', '2025-10-15 05:12:24'),
(3, 3, '103', 'cleaning', 'completed', 'normal', NULL, NULL, NULL, '2025-10-15 05:12:13', '2025-10-15 05:12:25', '2025-10-15 04:33:09', '2025-10-15 05:12:25'),
(4, 9, '201', 'cleaning', 'completed', 'high', NULL, NULL, NULL, '2025-10-15 05:12:05', '2025-10-15 05:12:26', '2025-10-15 04:33:09', '2025-10-15 05:12:26'),
(5, 11, '203', 'cleaning', 'completed', 'high', NULL, NULL, NULL, '2025-10-15 05:12:34', '2025-10-15 05:14:41', '2025-10-15 04:33:09', '2025-10-15 05:14:41'),
(6, 12, '204', 'cleaning', 'completed', 'normal', NULL, NULL, NULL, '2025-10-15 05:14:47', '2025-10-15 10:51:56', '2025-10-15 04:33:09', '2025-10-15 10:51:56'),
(7, 18, '304', 'cleaning', 'completed', 'normal', NULL, NULL, NULL, '2025-10-15 10:51:48', '2025-10-15 10:51:57', '2025-10-15 04:33:09', '2025-10-15 10:51:57'),
(8, 20, '306', 'maintenance', 'completed', 'urgent', NULL, NULL, NULL, '2025-10-15 05:11:37', '2025-10-15 05:12:28', '2025-10-15 04:33:09', '2025-10-15 05:12:28'),
(9, 24, '404', 'cleaning', 'completed', 'normal', NULL, NULL, NULL, '2025-10-15 10:51:46', '2025-10-15 10:51:57', '2025-10-15 04:33:09', '2025-10-15 10:51:57'),
(10, 29, '504', 'maintenance', 'completed', 'urgent', NULL, NULL, NULL, '2025-10-15 05:11:57', '2025-10-15 05:12:20', '2025-10-15 04:33:09', '2025-10-15 05:12:20'),
(16, 9, '201', 'cleaning', 'completed', 'high', NULL, NULL, 'Automated task created from room status change', '2025-10-15 05:14:39', '2025-10-15 05:14:44', '2025-10-15 05:12:05', '2025-10-15 05:14:44'),
(17, 1, '101', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-15 05:12:10', '2025-10-15 05:12:22', '2025-10-15 05:12:07', '2025-10-15 05:12:22'),
(18, 2, '102', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-15 10:51:47', '2025-10-15 10:51:56', '2025-10-15 05:12:09', '2025-10-15 10:51:56'),
(19, 1, '101', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-15 05:14:45', '2025-10-15 10:51:56', '2025-10-15 05:12:43', '2025-10-15 10:51:56'),
(20, 2, '102', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-15 05:14:43', '2025-10-15 10:51:55', '2025-10-15 05:12:45', '2025-10-15 10:51:55'),
(21, 19, '305', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-15 10:51:45', '2025-10-15 10:51:55', '2025-10-15 05:12:48', '2025-10-15 10:51:55'),
(22, 14, '206', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-15 10:51:45', '2025-10-15 10:51:55', '2025-10-15 05:12:50', '2025-10-15 10:51:55'),
(23, 9, '201', 'cleaning', 'completed', 'high', NULL, NULL, 'Automated task created from room status change', '2025-10-15 09:27:34', '2025-10-15 10:51:50', '2025-10-15 05:14:39', '2025-10-15 10:51:50'),
(24, 12, '204', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-15 09:34:44', '2025-10-15 10:51:55', '2025-10-15 05:14:47', '2025-10-15 10:51:55'),
(25, 9, '201', 'cleaning', 'completed', 'high', NULL, NULL, 'Automated task created from room status change', '2025-10-15 10:51:44', '2025-10-15 10:51:49', '2025-10-15 09:27:34', '2025-10-15 10:51:49'),
(26, 25, '405', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-15 09:35:42', '2025-10-15 09:36:44', '2025-10-15 09:35:22', '2025-10-15 09:36:44'),
(27, 30, '505', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-15 13:57:59', '2025-10-15 19:39:26', '2025-10-15 13:57:51', '2025-10-15 19:39:26'),
(28, 1, '101', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-18 15:34:02', '2025-10-19 10:15:52', '2025-10-15 23:08:51', '2025-10-19 10:15:52'),
(29, 2, '102', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-18 15:34:03', '2025-10-19 10:15:51', '2025-10-15 23:08:56', '2025-10-19 10:15:51'),
(30, 15, '301', 'cleaning', 'completed', 'high', NULL, NULL, 'Automated task created from room status change', '2025-10-18 14:26:30', '2025-10-18 15:34:11', '2025-10-16 01:28:44', '2025-10-18 15:34:11'),
(31, 28, '503', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-18 15:34:04', '2025-10-19 10:15:49', '2025-10-16 01:28:48', '2025-10-19 10:15:49'),
(32, 29, '504', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-19 10:15:24', '2025-10-19 10:15:49', '2025-10-16 01:28:52', '2025-10-19 10:15:49'),
(33, 3, '103', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-18 15:34:06', '2025-10-18 15:34:12', '2025-10-16 05:46:30', '2025-10-18 15:34:12'),
(34, 1, '101', 'cleaning', 'completed', 'normal', NULL, 'Event: Event', 'Event Reserved', '2025-10-19 10:15:26', '2025-10-19 10:15:50', '2025-10-16 05:48:39', '2025-10-19 10:15:50'),
(35, 2, '102', 'cleaning', 'completed', 'normal', NULL, 'Event: Event', '', '2025-10-19 10:15:25', '2025-10-19 10:15:49', '2025-10-16 05:48:39', '2025-10-19 10:15:49'),
(36, 3, '103', 'cleaning', 'completed', 'normal', NULL, NULL, '', '2025-10-16 05:59:21', '2025-10-18 15:34:13', '2025-10-16 05:48:39', '2025-10-18 15:34:13'),
(37, 9, '201', 'cleaning', 'completed', 'high', NULL, NULL, NULL, '2025-10-16 05:59:05', '2025-10-16 05:59:23', '2025-10-16 05:48:39', '2025-10-16 05:59:23'),
(38, 12, '204', 'cleaning', 'completed', 'normal', NULL, NULL, NULL, '2025-10-19 10:15:24', '2025-10-19 10:15:47', '2025-10-16 05:48:39', '2025-10-19 10:15:47'),
(39, 15, '301', 'cleaning', 'completed', 'high', NULL, NULL, NULL, '2025-10-19 02:37:51', '2025-10-19 10:15:39', '2025-10-16 05:48:39', '2025-10-19 10:15:39'),
(40, 29, '504', 'cleaning', 'completed', 'normal', NULL, NULL, NULL, '2025-10-19 10:15:36', '2025-10-19 10:15:48', '2025-10-16 05:48:39', '2025-10-19 10:15:48'),
(41, 3, '103', 'maintenance', 'completed', 'urgent', NULL, NULL, '', '2025-10-16 05:57:41', '2025-10-16 05:59:07', '2025-10-16 05:50:28', '2025-10-16 05:59:07'),
(42, 9, '201', 'cleaning', 'completed', 'high', NULL, NULL, 'Automated task created from room status change', '2025-10-16 07:53:19', '2025-10-19 10:15:39', '2025-10-16 05:59:05', '2025-10-19 10:15:39'),
(43, 3, '103', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-19 10:15:26', '2025-10-19 10:15:45', '2025-10-16 05:59:21', '2025-10-19 10:15:45'),
(44, 1, '101', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-19 10:15:27', '2025-10-19 10:15:45', '2025-10-16 07:32:39', '2025-10-19 10:15:45'),
(45, 9, '201', 'cleaning', 'completed', 'high', NULL, NULL, 'Automated task created from room status change', '2025-10-18 15:33:56', '2025-10-19 10:15:38', '2025-10-16 07:53:19', '2025-10-19 10:15:38'),
(46, 1, '101', 'cleaning', 'completed', 'normal', NULL, 'Test Guest', 'Test housekeeping task', '2025-10-19 10:15:27', '2025-10-19 10:15:44', '2025-10-16 07:59:43', '2025-10-19 10:15:44'),
(47, 2, '102', 'cleaning', 'completed', 'normal', NULL, 'Event: Event', 'Automated task created from room status change', '2025-10-18 15:34:21', '2025-10-19 10:15:43', '2025-10-18 15:34:03', '2025-10-19 10:15:43'),
(48, 28, '503', 'cleaning', 'completed', 'normal', NULL, 'Event: Event', 'Automated task created from room status change', '2025-10-19 10:15:28', '2025-10-19 10:15:43', '2025-10-18 15:34:04', '2025-10-19 10:15:43'),
(49, 17, '303', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-19 10:15:28', '2025-10-19 10:15:41', '2025-10-18 19:37:44', '2025-10-19 10:15:41'),
(50, 3, '103', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-19 10:15:29', '2025-10-19 10:15:40', '2025-10-18 19:38:06', '2025-10-19 10:15:40'),
(51, 15, '301', 'cleaning', 'completed', 'high', NULL, NULL, 'Automated task created from room status change', '2025-10-19 10:15:34', '2025-10-19 10:15:37', '2025-10-19 02:37:51', '2025-10-19 10:15:37'),
(52, 30, '505', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-19 10:15:30', '2025-10-19 10:15:40', '2025-10-19 10:15:16', '2025-10-19 10:15:40'),
(53, 12, '204', 'cleaning', 'completed', 'normal', NULL, NULL, 'Automated task created from room status change', '2025-10-19 10:15:32', '2025-10-19 10:15:39', '2025-10-19 10:15:24', '2025-10-19 10:15:40');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_category_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_categories`
--

INSERT INTO `inventory_categories` (`id`, `name`, `description`, `parent_category_id`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Food & Beverages', 'Food items, beverages, and related products', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
(2, 'Cleaning Supplies', 'Cleaning products and maintenance supplies', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
(3, 'Linens & Towels', 'Bed linens, towels, and related textile items', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
(4, 'Room Amenities', 'Guest room supplies and amenities', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
(5, 'Office Supplies', 'Office and administrative supplies', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
(6, 'Electronics', 'Electronic equipment and accessories', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
(7, 'Furniture', 'Furniture and fixtures', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
(8, 'Kitchen Equipment', 'Kitchen appliances and equipment', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
(9, 'Maintenance', 'Maintenance and repair supplies', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
(10, 'Miscellaneous', 'Other inventory items not categorized elsewhere', NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23');

-- --------------------------------------------------------

--
-- Stand-in structure for view `inventory_category_summary`
-- (See below for the actual view)
--
CREATE TABLE `inventory_category_summary` (
`category_id` int(11)
,`category_name` varchar(100)
,`total_items` bigint(21)
,`total_stock` decimal(32,0)
,`total_value` decimal(42,2)
,`low_stock_items` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `unit_of_measure` enum('pieces','kg','liters','boxes','packets','bottles','sets') DEFAULT 'pieces',
  `minimum_stock_level` int(11) DEFAULT 0,
  `maximum_stock_level` int(11) DEFAULT NULL,
  `reorder_point` int(11) DEFAULT 0,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `is_perishable` tinyint(1) DEFAULT 0,
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `name`, `description`, `category_id`, `supplier_id`, `sku`, `barcode`, `unit_of_measure`, `minimum_stock_level`, `maximum_stock_level`, `reorder_point`, `unit_cost`, `selling_price`, `location`, `is_perishable`, `expiry_date`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'White Bath Towels', 'Standard white bath towels for guest rooms', 3, 3, 'TOWEL-WHITE-001', NULL, 'pieces', 50, 200, 75, 8.50, 12.75, 'Linen Storage Room A', 0, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 06:47:40'),
(2, 'Shampoo Bottles', 'Individual shampoo bottles for guest rooms', 4, 4, 'SHAMPOO-001', NULL, 'bottles', 100, 500, 150, 1.25, 1.88, 'Amenities Storage B', 0, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 06:47:40'),
(3, 'Coffee Packets', 'Individual coffee packets for guest rooms', 1, 1, 'COFFEE-001', NULL, 'packets', 200, 1000, 300, 0.75, 1.13, 'Kitchen Storage C', 0, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 06:47:40'),
(4, 'All-Purpose Cleaner', 'Multi-surface cleaning solution', 2, 2, 'CLEANER-ALL-001', NULL, 'bottles', 20, 100, 30, 12.50, 18.75, 'Maintenance Storage D', 0, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 06:47:40');

-- --------------------------------------------------------

--
-- Stand-in structure for view `inventory_low_stock_alerts`
-- (See below for the actual view)
--
CREATE TABLE `inventory_low_stock_alerts` (
`id` int(11)
,`name` varchar(255)
,`sku` varchar(100)
,`minimum_stock_level` int(11)
,`current_stock` int(11)
,`available_stock` int(11)
,`category_name` varchar(100)
,`location` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_purchase_orders`
--

CREATE TABLE `inventory_purchase_orders` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `status` enum('draft','sent','confirmed','delivered','cancelled') DEFAULT 'draft',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_purchase_order_items`
--

CREATE TABLE `inventory_purchase_order_items` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `received_quantity` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_stock`
--

CREATE TABLE `inventory_stock` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `reserved_stock` int(11) NOT NULL DEFAULT 0,
  `available_stock` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_stock`
--

INSERT INTO `inventory_stock` (`id`, `item_id`, `current_stock`, `reserved_stock`, `available_stock`, `last_updated`, `updated_by`) VALUES
(1, 1, 150, 0, 150, '2025-10-16 05:43:23', NULL),
(2, 2, 300, 0, 300, '2025-10-16 05:43:23', NULL),
(3, 3, 500, 0, 500, '2025-10-16 05:43:23', NULL),
(4, 4, 50, 0, 50, '2025-10-16 05:43:23', NULL);

--
-- Triggers `inventory_stock`
--
DELIMITER $$
CREATE TRIGGER `update_available_stock` BEFORE UPDATE ON `inventory_stock` FOR EACH ROW BEGIN
    SET NEW.available_stock = NEW.current_stock - NEW.reserved_stock;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_stock_history`
--

CREATE TABLE `inventory_stock_history` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `operation_type` enum('stock_in','stock_out','adjustment','reservation','release','expiry','damage') NOT NULL,
  `quantity` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `reference_type` enum('purchase_order','sale','adjustment','reservation','transfer','expiry','damage') DEFAULT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_suppliers`
--

CREATE TABLE `inventory_suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_suppliers`
--

INSERT INTO `inventory_suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `city`, `state`, `zip_code`, `country`, `website`, `notes`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Fresh Foods Supplier', 'John Smith', 'john@freshfoods.com', '+1-555-0123', '123 Market Street', 'Springfield', 'IL', '62701', 'USA', NULL, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
(2, 'Cleaning Supplies Co', 'Sarah Johnson', 'sarah@cleaningsupplies.com', '+1-555-0124', '456 Industrial Blvd', 'Springfield', 'IL', '62702', 'USA', NULL, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
(3, 'Linen & Textile Ltd', 'Mike Davis', 'mike@linentextile.com', '+1-555-0125', '789 Textile Ave', 'Springfield', 'IL', '62703', 'USA', NULL, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
(4, 'Hotel Amenities Inc', 'Lisa Wilson', 'lisa@hotelamenities.com', '+1-555-0126', '321 Guest Lane', 'Springfield', 'IL', '62704', 'USA', NULL, NULL, 1, NULL, '2025-10-16 05:43:23', '2025-10-16 05:43:23'),
(5, 'joseph lopez', 'dfghjkl', 'josephlopez102004@gmail.com', '09777456465', 'ninang vir', 'Caloocan City', 'phil', '1414', 'Philippines', '', '', 1, 4, '2025-10-16 06:19:59', '2025-10-16 06:19:59');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_programs`
--

CREATE TABLE `loyalty_programs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `points_per_stay` decimal(10,2) DEFAULT 100.00,
  `points_per_dollar` decimal(10,2) DEFAULT 1.00,
  `minimum_points_redeem` int(11) DEFAULT 100,
  `is_active` tinyint(1) DEFAULT 1,
  `enrollment_auto` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_rewards`
--

CREATE TABLE `loyalty_rewards` (
  `id` int(11) NOT NULL,
  `loyalty_program_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `reward_type` enum('discount','free_night','upgrade','amenity','points_bonus') NOT NULL,
  `points_required` int(11) NOT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `discount_percentage` decimal(5,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `usage_limit` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_transactions`
--

CREATE TABLE `loyalty_transactions` (
  `id` int(11) NOT NULL,
  `guest_loyalty_id` int(11) NOT NULL,
  `transaction_type` enum('earn','redeem','expire','adjust') NOT NULL,
  `points_amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketing_campaigns`
--

CREATE TABLE `marketing_campaigns` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `campaign_type` enum('email','social_media','advertising','promotion','loyalty','seasonal') NOT NULL,
  `target_audience` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `status` enum('draft','active','paused','completed','cancelled') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `marketing_campaigns`
--

INSERT INTO `marketing_campaigns` (`id`, `name`, `description`, `campaign_type`, `target_audience`, `start_date`, `end_date`, `budget`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'test 1', 'testing', 'social_media', NULL, '0000-00-00', NULL, 5000.00, 'draft', 1, '2025-10-16 05:20:12', '2025-10-16 05:20:12');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotional_offers`
--

CREATE TABLE `promotional_offers` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `offer_type` enum('percentage_discount','fixed_amount_discount','free_nights','upgrade','package_deal') NOT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `discount_percentage` decimal(5,2) DEFAULT NULL,
  `min_stay_nights` int(11) DEFAULT 1,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `applicable_room_types` text DEFAULT NULL,
  `applicable_rate_plans` text DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `valid_from` date NOT NULL,
  `valid_until` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotion_usage`
--

CREATE TABLE `promotion_usage` (
  `id` int(11) NOT NULL,
  `promotional_offer_id` int(11) NOT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `usage_date` date NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL,
  `identifier` varchar(128) NOT NULL,
  `action` varchar(50) NOT NULL,
  `attempts` int(11) DEFAULT 1,
  `window_start` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` varchar(50) NOT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `check_in_date` datetime NOT NULL,
  `check_out_date` datetime NOT NULL,
  `status` enum('Pending','Checked In','Checked Out','Cancelled') DEFAULT 'Pending',
  `payment_status` enum('PENDING','DOWNPAYMENT','FULLY PAID','CANCELLED') DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `guest_id`, `room_id`, `check_in_date`, `check_out_date`, `status`, `payment_status`, `created_at`, `updated_at`) VALUES
('EVT-68EFE78A7EE5E', NULL, 1, '2025-10-22 10:00:00', '2025-10-22 12:00:00', 'Checked Out', 'FULLY PAID', '2025-10-15 18:27:22', '2025-10-19 10:58:24'),
('EVT-68EFE78A815F2', NULL, 2, '2025-10-22 10:00:00', '2025-10-22 12:00:00', 'Checked Out', 'FULLY PAID', '2025-10-15 18:27:22', '2025-10-19 10:58:30'),
('EVT-68F045580D6BE', 18, 1, '2025-10-16 14:00:00', '2025-10-16 18:00:00', 'Checked Out', 'FULLY PAID', '2025-10-16 01:07:36', '2025-10-19 10:57:18'),
('EVT-68F045580E996', 18, 2, '2025-10-16 14:00:00', '2025-10-16 18:00:00', 'Checked Out', 'FULLY PAID', '2025-10-16 01:07:36', '2025-10-19 10:57:25'),
('EVT-68F04B38DEB59', 19, 28, '2025-10-16 09:32:00', '2025-10-17 09:32:00', 'Checked Out', 'FULLY PAID', '2025-10-16 01:32:40', '2025-10-19 10:15:05'),
('EVT-68F3B5E6B8C28', 20, 30, '2025-10-19 10:00:00', '2025-10-19 18:00:00', 'Checked Out', 'FULLY PAID', '2025-10-18 15:44:38', '2025-10-19 10:57:03'),
('EVT-68F4CBF4C3B00', 21, 26, '2025-10-19 19:30:00', '2025-10-20 19:30:00', 'Pending', 'PENDING', '2025-10-19 11:31:00', '2025-10-19 11:31:00'),
('RES-68EF2493947D3', 3, 19, '2025-10-15 12:35:00', '2025-10-16 12:35:00', 'Checked Out', 'FULLY PAID', '2025-10-15 04:35:31', '2025-10-15 05:12:48'),
('RES-68EF26593FED3', 12, 14, '2025-10-15 12:42:00', '2025-10-16 12:42:00', 'Checked Out', 'FULLY PAID', '2025-10-15 04:43:05', '2025-10-15 05:12:50'),
('RES-68EF268F543DC', 9, 25, '2025-10-15 12:43:00', '2025-10-16 12:43:00', 'Checked Out', 'FULLY PAID', '2025-10-15 04:43:59', '2025-10-15 09:35:22'),
('RES-68EF2C59CEBC4', 2, 30, '2025-10-15 13:08:00', '2025-10-16 13:08:00', 'Checked Out', 'FULLY PAID', '2025-10-15 05:08:41', '2025-10-15 13:57:51'),
('RES-68EF2DF4A8CC2', 12, 29, '2025-10-15 13:15:00', '2025-10-16 13:15:00', 'Checked Out', 'FULLY PAID', '2025-10-15 05:15:32', '2025-10-19 10:58:35'),
('RES-68EF67FE86C05', 17, 28, '2025-10-15 19:22:00', '2025-10-23 17:20:00', 'Checked Out', 'FULLY PAID', '2025-10-15 09:23:10', '2025-10-16 01:28:48'),
('RES-68EF7E7E8E583', 12, 18, '2025-10-15 18:59:00', '2025-10-16 18:59:00', 'Pending', 'FULLY PAID', '2025-10-15 10:59:10', '2025-10-15 13:53:24'),
('RES-68EF80F28B142', 11, 25, '2025-10-15 19:09:00', '2025-10-16 19:09:00', 'Pending', 'FULLY PAID', '2025-10-15 11:09:38', '2025-10-15 13:51:59'),
('RES-68EFFE6C45BB8', 11, 15, '2025-10-16 04:04:00', '2025-10-17 04:04:00', 'Checked Out', 'FULLY PAID', '2025-10-15 20:05:00', '2025-10-19 10:58:17'),
('RES-68F01013A48A4', 12, 17, '2025-10-16 05:20:00', '2025-10-17 05:20:00', 'Checked Out', 'FULLY PAID', '2025-10-15 21:20:19', '2025-10-19 10:57:59'),
('RES-68F3E3CCE7DAB', 2, 3, '2025-10-19 03:00:00', '2025-10-20 03:00:00', 'Checked Out', 'FULLY PAID', '2025-10-18 19:00:28', '2025-10-19 10:56:40'),
('RES-68F63829E8292', 12, 2, '2025-10-20 21:24:00', '2025-10-21 21:24:00', 'Pending', 'PENDING', '2025-10-20 13:24:57', '2025-10-20 13:24:57'),
('RES-68F7A50E0D4A7', 9, 2, '2025-10-21 23:20:00', '2025-10-22 23:20:00', 'Pending', 'PENDING', '2025-10-21 15:21:50', '2025-10-21 15:21:50'),
('RSV-TEST-001', 1, 1, '2025-10-13 00:00:00', '2025-10-15 00:00:00', 'Checked Out', 'FULLY PAID', '2025-10-15 04:33:10', '2025-10-15 05:12:43'),
('RSV-TEST-002', 2, 2, '2025-10-14 00:00:00', '2025-10-15 00:00:00', 'Checked Out', 'FULLY PAID', '2025-10-15 04:33:10', '2025-10-15 05:12:45'),
('RSV-TEST-003', 3, 3, '2025-10-12 00:00:00', '2025-10-15 00:00:00', 'Checked Out', 'FULLY PAID', '2025-10-15 04:33:10', '2025-10-15 04:36:31');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type` enum('Single','Double','Deluxe','Suite') DEFAULT 'Single',
  `floor_number` int(11) DEFAULT 1,
  `status` enum('Vacant','Occupied','Cleaning','Maintenance','Reserved','Event Ongoing') DEFAULT 'Vacant',
  `max_guests` int(11) DEFAULT 2,
  `rate` decimal(10,2) DEFAULT 1500.00,
  `amenities` text DEFAULT NULL,
  `last_cleaned` timestamp NULL DEFAULT NULL,
  `housekeeping_status` enum('clean','dirty','cleaning','inspected') DEFAULT 'clean',
  `maintenance_notes` text DEFAULT NULL,
  `guest_name` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type`, `floor_number`, `status`, `max_guests`, `rate`, `amenities`, `last_cleaned`, `housekeeping_status`, `maintenance_notes`, `guest_name`, `created_at`, `updated_at`) VALUES
(1, '101', 'Single', 1, 'Reserved', 1, 1200.00, 'WiFi, TV, Mini Fridge', NULL, 'clean', 'Event Reserved', NULL, '2025-10-15 04:33:09', '2025-10-19 11:09:38'),
(2, '102', 'Single', 1, 'Vacant', 1, 1200.00, 'WiFi, TV, Mini Fridge', NULL, 'clean', '', NULL, '2025-10-15 04:33:09', '2025-10-19 10:15:43'),
(3, '103', 'Single', 1, 'Vacant', 1, 1200.00, 'WiFi, TV, Mini Fridge', NULL, 'clean', '', NULL, '2025-10-15 04:33:09', '2025-10-19 10:15:40'),
(4, '104', 'Double', 1, 'Reserved', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker', NULL, 'clean', '', NULL, '2025-10-15 04:33:09', '2025-10-15 23:49:46'),
(5, '105', 'Double', 1, 'Occupied', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(6, '106', 'Double', 1, 'Reserved', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker', NULL, 'clean', '', NULL, '2025-10-15 04:33:09', '2025-10-15 23:54:36'),
(7, '107', '', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, 2 Single Beds', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(8, '108', '', 1, 'Reserved', 2, 1800.00, 'WiFi, TV, Mini Fridge, 2 Single Beds', NULL, 'clean', 'Test update', NULL, '2025-10-15 04:33:09', '2025-10-15 05:14:12'),
(9, '201', 'Deluxe', 2, 'Vacant', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-19 10:15:38'),
(10, '202', 'Deluxe', 2, 'Occupied', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(11, '203', 'Deluxe', 2, 'Vacant', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-15 05:14:41'),
(12, '204', '', 2, 'Vacant', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-19 10:15:39'),
(13, '205', '', 2, 'Occupied', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(14, '206', '', 2, 'Vacant', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-15 10:51:55'),
(15, '301', 'Suite', 3, 'Vacant', 2, 3500.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Living Room', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-19 10:15:37'),
(16, '302', 'Suite', 3, 'Occupied', 2, 3500.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Living Room', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(17, '303', '', 3, 'Vacant', 3, 2200.00, 'WiFi, TV, Mini Fridge, 3 Single Beds', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-19 10:15:41'),
(18, '304', '', 3, 'Reserved', 3, 2200.00, 'WiFi, TV, Mini Fridge, 3 Single Beds', NULL, 'clean', NULL, 'Ahmed Al-Rashid', '2025-10-15 04:33:09', '2025-10-15 11:05:11'),
(19, '305', '', 3, 'Vacant', 4, 2800.00, 'WiFi, TV, Mini Fridge, 4 Single Beds', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-15 10:51:55'),
(20, '306', '', 3, 'Vacant', 4, 2800.00, 'WiFi, TV, Mini Fridge, 4 Single Beds', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-15 05:12:28'),
(21, '401', '', 4, 'Vacant', 3, 4000.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Work Desk, Living Room', NULL, 'clean', '', NULL, '2025-10-15 04:33:09', '2025-10-19 11:09:38'),
(22, '402', '', 4, 'Occupied', 3, 4000.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Work Desk, Living Room', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(23, '403', '', 4, 'Vacant', 5, 4500.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Kitchen, Living Room', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(24, '404', '', 4, 'Vacant', 5, 4500.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Kitchen, Living Room', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-15 10:51:57'),
(25, '405', '', 4, 'Reserved', 2, 2000.00, 'WiFi, TV, Mini Fridge, Wheelchair Access, Roll-in Shower', NULL, 'clean', NULL, 'Yuki Tanaka', '2025-10-15 04:33:09', '2025-10-15 11:10:04'),
(26, '501', '', 5, 'Reserved', 4, 6000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Balcony, 2 Bedrooms, Living Room', NULL, 'clean', 'Event Reserved', 'Event: kantahan - Caagoy & Sol', '2025-10-15 04:33:09', '2025-10-19 11:31:00'),
(27, '502', '', 5, 'Occupied', 4, 6000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Balcony, 2 Bedrooms, Living Room', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-15 04:33:09'),
(28, '503', '', 5, 'Reserved', 6, 7000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Terrace, 3 Bedrooms, Dining Area', NULL, 'clean', 'Event Reserved', NULL, '2025-10-15 04:33:09', '2025-10-19 11:09:38'),
(29, '504', '', 5, 'Vacant', 6, 7000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Terrace, 3 Bedrooms, Dining Area', NULL, 'clean', NULL, NULL, '2025-10-15 04:33:09', '2025-10-19 10:15:48'),
(30, '505', '', 5, 'Reserved', 8, 10000.00, 'WiFi, Smart TV, Full Kitchen, 2 Jacuzzis, Private Terrace, 4 Bedrooms, Cinema Room, Butler Service', NULL, 'clean', 'Event Reserved', NULL, '2025-10-15 04:33:09', '2025-10-19 11:09:38');

--
-- Triggers `rooms`
--
DELIMITER $$
CREATE TRIGGER `after_room_status_update` AFTER UPDATE ON `rooms` FOR EACH ROW BEGIN
    -- When room status changes to Cleaning, create a housekeeping task
    IF NEW.status = 'Cleaning' AND OLD.status != 'Cleaning' THEN
        INSERT INTO housekeeping_tasks (room_id, room_number, task_type, status, priority, guest_name, notes)
        VALUES (
            NEW.id,
            NEW.room_number,
            'cleaning',
            'pending',
            CASE 
                WHEN NEW.room_type = 'Suite' OR NEW.room_type = 'Deluxe' THEN 'high'
                ELSE 'normal'
            END,
            NEW.guest_name,
            'Automated task created from room status change'
        );
    END IF;
    
    -- When room status changes to Maintenance, create a maintenance task
    IF NEW.status = 'Maintenance' AND OLD.status != 'Maintenance' THEN
        INSERT INTO housekeeping_tasks (room_id, room_number, task_type, status, priority, guest_name, notes)
        VALUES (
            NEW.id,
            NEW.room_number,
            'maintenance',
            'maintenance',
            'urgent',
            NEW.guest_name,
            NEW.maintenance_notes
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `room_status_logs`
--

CREATE TABLE `room_status_logs` (
  `id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_status_logs`
--

INSERT INTO `room_status_logs` (`id`, `room_number`, `previous_status`, `new_status`, `changed_by`, `change_reason`, `changed_at`) VALUES
(1, '305', 'Vacant', 'Occupied', 'Front Desk', 'Guest checked in', '2025-10-15 04:35:57'),
(2, '103', 'Cleaning', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-15 04:36:31'),
(3, '405', 'Vacant', 'Reserved', 'System', 'Auto-reserved for today\'s arrival', '2025-10-15 04:44:03'),
(4, '505', 'Vacant', 'Reserved', 'System', 'Auto-reserved for today\'s arrival', '2025-10-15 05:09:12'),
(5, '505', 'Reserved', 'Occupied', 'Front Desk', 'Guest checked in', '2025-10-15 05:11:03'),
(6, '504', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 05:12:20'),
(7, '101', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 05:12:22'),
(8, '102', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 05:12:24'),
(9, '103', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 05:12:25'),
(10, '201', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 05:12:26'),
(11, '306', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 05:12:28'),
(12, '101', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 05:12:32'),
(13, '101', 'Vacant', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-15 05:12:43'),
(14, '102', 'Vacant', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-15 05:12:45'),
(15, '305', 'Occupied', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-15 05:12:48'),
(16, '206', 'Vacant', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-15 05:12:50'),
(17, '108', 'Vacant', 'Reserved', 'API', 'Test update', '2025-10-15 05:14:12'),
(18, '203', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 05:14:41'),
(19, '201', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 05:14:44'),
(20, '504', 'Vacant', 'Reserved', 'System', 'Auto-reserved for today\'s arrival', '2025-10-15 05:15:45'),
(21, '504', 'Reserved', 'Occupied', 'Front Desk', 'Guest checked in', '2025-10-15 09:26:33'),
(22, '405', 'Reserved', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-15 09:35:22'),
(23, '405', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 09:36:44'),
(24, '201', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 10:51:49'),
(25, '201', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 10:51:50'),
(26, '204', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 10:51:55'),
(27, '206', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 10:51:55'),
(28, '305', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 10:51:55'),
(29, '102', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 10:51:55'),
(30, '101', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 10:51:56'),
(31, '102', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 10:51:56'),
(32, '204', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 10:51:56'),
(33, '304', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 10:51:57'),
(34, '404', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 10:51:57'),
(35, '304', 'Vacant', 'Reserved', 'System', 'Auto-reserved for today\'s arrival', '2025-10-15 11:05:11'),
(36, '405', 'Vacant', 'Reserved', 'System', 'Auto-reserved for today\'s arrival', '2025-10-15 11:10:04'),
(37, '505', 'Occupied', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-15 13:57:51'),
(38, '505', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-15 19:39:26'),
(39, '301', 'Vacant', 'Reserved', 'System', 'Auto-reserved for today\'s arrival', '2025-10-15 20:05:10'),
(40, '301', 'Reserved', 'Occupied', 'Front Desk', 'Guest checked in', '2025-10-15 20:05:45'),
(41, '401', 'Vacant', 'Event Reserved', 'API', '', '2025-10-15 20:46:14'),
(42, '303', 'Vacant', 'Reserved', 'System', 'Auto-reserved for today\'s arrival', '2025-10-15 21:31:24'),
(43, '103', 'Vacant', 'Reserved', 'API', '', '2025-10-15 22:30:49'),
(44, '101', 'Reserved', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-15 23:08:51'),
(45, '102', 'Reserved', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-15 23:08:56'),
(46, '106', 'Vacant', 'Reserved', 'API', '', '2025-10-15 23:38:37'),
(47, '106', 'Reserved', 'Event Ongoing', 'API', '', '2025-10-15 23:42:03'),
(48, '104', 'Vacant', 'Reserved', 'API', '', '2025-10-15 23:49:46'),
(49, '106', '', 'Reserved', 'API', '', '2025-10-15 23:54:36'),
(50, '102', '', 'Reserved', 'API', 'Event Ongoing', '2025-10-16 00:02:17'),
(51, '102', 'Reserved', 'Event Ongoing', 'API', '', '2025-10-16 00:02:43'),
(52, '102', 'Event Ongoing', 'Reserved', 'System', 'Auto-reserved for today\'s arrival', '2025-10-16 01:07:36'),
(53, '101', 'Reserved', 'Event Ongoing', 'Front Desk', 'Event checked in', '2025-10-16 01:26:09'),
(54, '102', 'Reserved', 'Event Ongoing', 'Front Desk', 'Event checked in', '2025-10-16 01:26:30'),
(55, '301', 'Occupied', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-16 01:28:44'),
(56, '503', 'Vacant', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-16 01:28:48'),
(57, '504', 'Occupied', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-16 01:28:52'),
(58, '503', 'Reserved', 'Event Ongoing', 'Front Desk', 'Event checked in', '2025-10-16 05:46:15'),
(59, '103', 'Reserved', 'Cleaning', 'API', '', '2025-10-16 05:46:31'),
(60, '103', 'Cleaning', 'Maintenance', 'API', '', '2025-10-16 05:50:28'),
(61, '103', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-16 05:59:07'),
(62, '201', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-16 05:59:23'),
(63, '303', 'Reserved', 'Occupied', 'Front Desk', 'Guest checked in', '2025-10-16 07:08:59'),
(64, '101', 'Event Ongoing', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-16 07:32:39'),
(65, '301', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-18 15:34:11'),
(66, '103', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-18 15:34:12'),
(67, '103', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-18 15:34:13'),
(68, '505', 'Reserved', 'Event Ongoing', 'Front Desk', 'Event checked in', '2025-10-18 18:56:11'),
(69, '103', 'Vacant', 'Occupied', 'Front Desk', 'Guest checked in', '2025-10-18 19:00:34'),
(70, '303', 'Occupied', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-18 19:37:44'),
(71, '103', 'Occupied', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-18 19:38:06'),
(72, '102', 'Cleaning', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-19 10:14:48'),
(73, '503', 'Cleaning', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-19 10:15:05'),
(74, '505', 'Event Ongoing', 'Cleaning', 'Front Desk', 'Guest checked out', '2025-10-19 10:15:16'),
(75, '301', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:37'),
(76, '201', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:38'),
(77, '201', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:39'),
(78, '301', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:39'),
(79, '204', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:39'),
(80, '204', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:40'),
(81, '505', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:40'),
(82, '103', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:40'),
(83, '303', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:41'),
(84, '303', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:41'),
(85, '503', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:43'),
(86, '102', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:43'),
(87, '101', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:44'),
(88, '101', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:45'),
(89, '103', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:45'),
(90, '204', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:47'),
(91, '504', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:48'),
(92, '504', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:49'),
(93, '503', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:49'),
(94, '102', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:49'),
(95, '101', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:50'),
(96, '102', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:51'),
(97, '101', 'Cleaning/Maintenance', 'Vacant', 'System', 'Housekeeping completed', '2025-10-19 10:15:52');

-- --------------------------------------------------------

--
-- Table structure for table `security_events`
--

CREATE TABLE `security_events` (
  `id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `description` text NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','receptionist','staff','manager') DEFAULT 'receptionist',
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verification_token` varchar(64) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password_reset_token` varchar(64) DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `totp_secret` varchar(255) DEFAULT NULL,
  `totp_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `is_active`, `email_verified`, `email_verification_token`, `email_verified_at`, `password_reset_token`, `password_reset_expires`, `last_login_at`, `last_login_ip`, `failed_login_attempts`, `locked_until`, `totp_secret`, `totp_enabled`, `two_factor_secret`, `two_factor_enabled`, `created_at`, `updated_at`) VALUES
(1, 'test@example.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, 'JBSWY3DPEHPK3PXP', 1, '2025-10-15 04:31:49', '2025-10-15 09:04:17'),
(2, 'admin@gmail.com', '$2y$12$QGvY1M62jrWf3ho8a91cSOSsJVCX1piEjLO12U9XAJTunegEl7yIW', 'admin', 1, 1, NULL, NULL, NULL, NULL, '2025-10-15 09:17:20', '::1', 0, NULL, NULL, 0, NULL, 0, '2025-10-15 09:07:42', '2025-10-15 09:17:20'),
(4, 'josephlopez102004@gmail.com', '$2y$12$6BJtZMCcXPtN6XqjDVdmruov4qjFeNPcO9laSyE9/FxhOFR0H.2VC', 'admin', 1, 1, NULL, NULL, NULL, NULL, '2025-10-21 15:19:32', '::1', 0, NULL, NULL, 0, NULL, 0, '2025-10-15 09:18:54', '2025-10-21 15:19:32');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `inventory_category_summary`
--
DROP TABLE IF EXISTS `inventory_category_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `inventory_category_summary`  AS SELECT `c`.`id` AS `category_id`, `c`.`name` AS `category_name`, count(`i`.`id`) AS `total_items`, sum(`s`.`current_stock`) AS `total_stock`, sum(`s`.`current_stock` * `i`.`unit_cost`) AS `total_value`, count(case when `s`.`current_stock` <= `i`.`minimum_stock_level` then 1 end) AS `low_stock_items` FROM ((`inventory_categories` `c` left join `inventory_items` `i` on(`c`.`id` = `i`.`category_id` and `i`.`is_active` = 1)) left join `inventory_stock` `s` on(`i`.`id` = `s`.`item_id`)) WHERE `c`.`is_active` = 1 GROUP BY `c`.`id`, `c`.`name` ;

-- --------------------------------------------------------

--
-- Structure for view `inventory_low_stock_alerts`
--
DROP TABLE IF EXISTS `inventory_low_stock_alerts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `inventory_low_stock_alerts`  AS SELECT `i`.`id` AS `id`, `i`.`name` AS `name`, `i`.`sku` AS `sku`, `i`.`minimum_stock_level` AS `minimum_stock_level`, `s`.`current_stock` AS `current_stock`, `s`.`available_stock` AS `available_stock`, `c`.`name` AS `category_name`, `i`.`location` AS `location` FROM ((`inventory_items` `i` join `inventory_stock` `s` on(`i`.`id` = `s`.`item_id`)) join `inventory_categories` `c` on(`i`.`category_id` = `c`.`id`)) WHERE `i`.`is_active` = 1 AND `s`.`current_stock` <= `i`.`minimum_stock_level` ORDER BY `s`.`current_stock` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `billing_transactions`
--
ALTER TABLE `billing_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reservation_id` (`reservation_id`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `channels`
--
ALTER TABLE `channels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_last_sync` (`last_sync`);

--
-- Indexes for table `channel_availability`
--
ALTER TABLE `channel_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_channel_room_date` (`channel_id`,`room_id`,`date`),
  ADD KEY `idx_channel_id` (`channel_id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `channel_rates`
--
ALTER TABLE `channel_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_channel_id` (`channel_id`),
  ADD KEY `idx_room_type` (`room_type`),
  ADD KEY `idx_rate_type` (`rate_type`),
  ADD KEY `idx_valid_dates` (`valid_from`,`valid_to`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `channel_room_mappings`
--
ALTER TABLE `channel_room_mappings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_channel_id` (`channel_id`),
  ADD KEY `idx_channel_room_id` (`channel_room_id`),
  ADD KEY `idx_local_room_id` (`local_room_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `channel_sync_logs`
--
ALTER TABLE `channel_sync_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_channel_id` (`channel_id`),
  ADD KEY `idx_sync_type` (`sync_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_started_at` (`started_at`);

--
-- Indexes for table `email_campaigns`
--
ALTER TABLE `email_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled_date` (`scheduled_date`),
  ADD KEY `idx_recipient_type` (`recipient_type`);

--
-- Indexes for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_events_time` (`start_datetime`,`end_datetime`),
  ADD KEY `idx_events_status` (`status`);

--
-- Indexes for table `event_reservations`
--
ALTER TABLE `event_reservations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_event_res` (`event_id`,`reservation_id`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_reservation_id` (`reservation_id`);

--
-- Indexes for table `event_services`
--
ALTER TABLE `event_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_id` (`event_id`);

--
-- Indexes for table `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_last_name` (`last_name`);

--
-- Indexes for table `guest_loyalty_memberships`
--
ALTER TABLE `guest_loyalty_memberships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `membership_number` (`membership_number`),
  ADD KEY `loyalty_program_id` (`loyalty_program_id`),
  ADD KEY `idx_guest_id` (`guest_id`),
  ADD KEY `idx_membership_number` (`membership_number`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `housekeeping_tasks`
--
ALTER TABLE `housekeeping_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_room_number` (`room_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_parent_category` (`parent_category_id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_supplier` (`supplier_id`),
  ADD KEY `idx_sku` (`sku`),
  ADD KEY `idx_barcode` (`barcode`),
  ADD KEY `idx_unit_of_measure` (`unit_of_measure`),
  ADD KEY `idx_location` (`location`),
  ADD KEY `idx_is_perishable` (`is_perishable`),
  ADD KEY `idx_expiry_date` (`expiry_date`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `inventory_purchase_orders`
--
ALTER TABLE `inventory_purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `idx_po_number` (`po_number`),
  ADD KEY `idx_supplier` (`supplier_id`),
  ADD KEY `idx_order_date` (`order_date`),
  ADD KEY `idx_expected_delivery` (`expected_delivery_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `inventory_purchase_order_items`
--
ALTER TABLE `inventory_purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_purchase_order` (`purchase_order_id`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_received_quantity` (`received_quantity`);

--
-- Indexes for table `inventory_stock`
--
ALTER TABLE `inventory_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_item_stock` (`item_id`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_current_stock` (`current_stock`),
  ADD KEY `idx_available_stock` (`available_stock`),
  ADD KEY `idx_last_updated` (`last_updated`),
  ADD KEY `idx_updated_by` (`updated_by`);

--
-- Indexes for table `inventory_stock_history`
--
ALTER TABLE `inventory_stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_operation_type` (`operation_type`),
  ADD KEY `idx_reference_id` (`reference_id`),
  ADD KEY `idx_reference_type` (`reference_type`),
  ADD KEY `idx_performed_by` (`performed_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `inventory_suppliers`
--
ALTER TABLE `inventory_suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `loyalty_programs`
--
ALTER TABLE `loyalty_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `loyalty_rewards`
--
ALTER TABLE `loyalty_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loyalty_program` (`loyalty_program_id`),
  ADD KEY `idx_points_required` (`points_required`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_guest_loyalty` (`guest_loyalty_id`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_campaign_type` (`campaign_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_end_date` (`end_date`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `promotional_offers`
--
ALTER TABLE `promotional_offers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_valid_dates` (`valid_from`,`valid_until`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `promotion_usage`
--
ALTER TABLE `promotion_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_promotional_offer` (`promotional_offer_id`),
  ADD KEY `idx_guest_id` (`guest_id`),
  ADD KEY `idx_reservation_id` (`reservation_id`),
  ADD KEY `idx_usage_date` (`usage_date`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_identifier_action` (`identifier`,`action`),
  ADD KEY `idx_window_start` (`window_start`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_guest_id` (`guest_id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_check_in_date` (`check_in_date`),
  ADD KEY `idx_check_out_date` (`check_out_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `idx_room_number` (`room_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_floor_number` (`floor_number`),
  ADD KEY `idx_room_type` (`room_type`),
  ADD KEY `idx_housekeeping_status` (`housekeeping_status`);

--
-- Indexes for table `room_status_logs`
--
ALTER TABLE `room_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_room_number` (`room_number`),
  ADD KEY `idx_changed_at` (`changed_at`);

--
-- Indexes for table `security_events`
--
ALTER TABLE `security_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_email_verified_at` (`email_verified_at`),
  ADD KEY `idx_last_login` (`last_login_at`),
  ADD KEY `idx_failed_attempts` (`failed_login_attempts`),
  ADD KEY `idx_locked_until` (`locked_until`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `billing_transactions`
--
ALTER TABLE `billing_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `channels`
--
ALTER TABLE `channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `channel_availability`
--
ALTER TABLE `channel_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `channel_rates`
--
ALTER TABLE `channel_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `channel_room_mappings`
--
ALTER TABLE `channel_room_mappings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `channel_sync_logs`
--
ALTER TABLE `channel_sync_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_campaigns`
--
ALTER TABLE `email_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `event_reservations`
--
ALTER TABLE `event_reservations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `event_services`
--
ALTER TABLE `event_services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `guest_loyalty_memberships`
--
ALTER TABLE `guest_loyalty_memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `housekeeping_tasks`
--
ALTER TABLE `housekeeping_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory_purchase_orders`
--
ALTER TABLE `inventory_purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_purchase_order_items`
--
ALTER TABLE `inventory_purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_stock`
--
ALTER TABLE `inventory_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory_stock_history`
--
ALTER TABLE `inventory_stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_suppliers`
--
ALTER TABLE `inventory_suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `loyalty_programs`
--
ALTER TABLE `loyalty_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loyalty_rewards`
--
ALTER TABLE `loyalty_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotional_offers`
--
ALTER TABLE `promotional_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotion_usage`
--
ALTER TABLE `promotion_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `room_status_logs`
--
ALTER TABLE `room_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `security_events`
--
ALTER TABLE `security_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `billing_transactions`
--
ALTER TABLE `billing_transactions`
  ADD CONSTRAINT `billing_transactions_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `channel_availability`
--
ALTER TABLE `channel_availability`
  ADD CONSTRAINT `channel_availability_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `channel_availability_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `channel_rates`
--
ALTER TABLE `channel_rates`
  ADD CONSTRAINT `channel_rates_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `channel_room_mappings`
--
ALTER TABLE `channel_room_mappings`
  ADD CONSTRAINT `channel_room_mappings_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `channel_room_mappings_ibfk_2` FOREIGN KEY (`local_room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `channel_sync_logs`
--
ALTER TABLE `channel_sync_logs`
  ADD CONSTRAINT `channel_sync_logs_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_campaigns`
--
ALTER TABLE `email_campaigns`
  ADD CONSTRAINT `email_campaigns_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD CONSTRAINT `email_verification_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_reservations`
--
ALTER TABLE `event_reservations`
  ADD CONSTRAINT `fk_er_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_er_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_services`
--
ALTER TABLE `event_services`
  ADD CONSTRAINT `fk_es_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `guest_loyalty_memberships`
--
ALTER TABLE `guest_loyalty_memberships`
  ADD CONSTRAINT `guest_loyalty_memberships_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `guest_loyalty_memberships_ibfk_2` FOREIGN KEY (`loyalty_program_id`) REFERENCES `loyalty_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `housekeeping_tasks`
--
ALTER TABLE `housekeeping_tasks`
  ADD CONSTRAINT `housekeeping_tasks_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD CONSTRAINT `inventory_categories_ibfk_1` FOREIGN KEY (`parent_category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_categories_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`),
  ADD CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `inventory_suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_items_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_purchase_orders`
--
ALTER TABLE `inventory_purchase_orders`
  ADD CONSTRAINT `inventory_purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `inventory_suppliers` (`id`),
  ADD CONSTRAINT `inventory_purchase_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_purchase_order_items`
--
ALTER TABLE `inventory_purchase_order_items`
  ADD CONSTRAINT `inventory_purchase_order_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `inventory_purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_purchase_order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_stock`
--
ALTER TABLE `inventory_stock`
  ADD CONSTRAINT `inventory_stock_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_stock_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_stock_history`
--
ALTER TABLE `inventory_stock_history`
  ADD CONSTRAINT `inventory_stock_history_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_stock_history_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_suppliers`
--
ALTER TABLE `inventory_suppliers`
  ADD CONSTRAINT `inventory_suppliers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loyalty_programs`
--
ALTER TABLE `loyalty_programs`
  ADD CONSTRAINT `loyalty_programs_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loyalty_rewards`
--
ALTER TABLE `loyalty_rewards`
  ADD CONSTRAINT `loyalty_rewards_ibfk_1` FOREIGN KEY (`loyalty_program_id`) REFERENCES `loyalty_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD CONSTRAINT `loyalty_transactions_ibfk_1` FOREIGN KEY (`guest_loyalty_id`) REFERENCES `guest_loyalty_memberships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  ADD CONSTRAINT `marketing_campaigns_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `promotional_offers`
--
ALTER TABLE `promotional_offers`
  ADD CONSTRAINT `promotional_offers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `promotion_usage`
--
ALTER TABLE `promotion_usage`
  ADD CONSTRAINT `promotion_usage_ibfk_1` FOREIGN KEY (`promotional_offer_id`) REFERENCES `promotional_offers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_usage_ibfk_2` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `security_events`
--
ALTER TABLE `security_events`
  ADD CONSTRAINT `security_events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD CONSTRAINT `security_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
