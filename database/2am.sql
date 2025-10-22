-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2025 at 08:05 PM
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
-- Database: `hmscore1`
--

-- --------------------------------------------------------

--
-- Table structure for table `campaign_performance`
--

CREATE TABLE `campaign_performance` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `performance_date` date NOT NULL,
  `impressions` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `leads` int(11) DEFAULT 0,
  `conversions` int(11) DEFAULT 0,
  `revenue` decimal(10,2) DEFAULT 0.00,
  `spend` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `channels`
--

CREATE TABLE `channels` (
  `id` int(11) NOT NULL,
  `channel_name` varchar(100) NOT NULL,
  `channel_type` enum('OTA','Direct','GDS','Wholesale','Corporate') NOT NULL DEFAULT 'OTA',
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 0.00,
  `base_url` varchar(255) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive','Pending','Disabled') NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `channels`
--

INSERT INTO `channels` (`id`, `channel_name`, `channel_type`, `contact_email`, `contact_phone`, `commission_rate`, `base_url`, `api_key`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Booking.com', 'OTA', 'support@booking.com', '+1-415-555-0100', 15.00, NULL, NULL, 'Active', 'Major OTA platform with global reach', '2025-10-20 12:28:53', '2025-10-20 12:28:53'),
(2, 'Expedia', 'OTA', 'partner@expedia.com', '+1-425-555-0200', 18.00, NULL, NULL, 'Active', 'Leading online travel agency', '2025-10-20 12:28:53', '2025-10-20 12:28:53'),
(3, 'Airbnb', 'OTA', 'host@airbnb.com', '+1-415-555-0300', 12.00, NULL, NULL, 'Pending', 'Vacation rental platform', '2025-10-20 12:28:53', '2025-10-20 12:28:53'),
(5, 'Agoda', 'OTA', 'partners@agoda.com', '+66-2-555-0500', 14.00, NULL, NULL, 'Active', 'Asian OTA platform', '2025-10-20 12:28:53', '2025-10-20 12:28:53'),
(6, 'Priceline', 'OTA', 'api@priceline.com', '+1-203-555-0600', 16.00, NULL, NULL, 'Active', 'Express deals specialist', '2025-10-20 12:28:53', '2025-10-20 12:28:53'),
(7, 'Hotels.com', 'OTA', 'affiliates@hotels.com', '+1-469-555-0700', 17.00, NULL, NULL, 'Active', 'Expedia Group brand', '2025-10-20 12:28:53', '2025-10-20 12:28:53'),
(8, 'TripAdvisor', 'OTA', 'business@tripadvisor.com', '+1-617-555-0800', 13.00, NULL, NULL, 'Active', 'Review and booking platform', '2025-10-20 12:28:53', '2025-10-20 12:28:53'),
(9, 'Kayak', 'OTA', 'metasearch@kayak.com', '+1-203-555-0900', 11.00, NULL, NULL, 'Active', 'Metasearch engine', '2025-10-20 12:28:53', '2025-10-20 12:28:53'),
(10, 'Travelocity', 'OTA', 'partners@travelocity.com', '+1-682-555-1000', 15.00, NULL, NULL, 'Active', 'Sabre Holdings brand', '2025-10-20 12:28:53', '2025-10-20 12:28:53'),
(11, 'Orbitzz', 'OTA', 'api@orbitz.com', '+1-312-555-1100', 16.00, NULL, NULL, 'Active', 'Travel booking website', '2025-10-20 12:28:53', '2025-10-20 15:04:03'),
(12, 'Hostelworld', 'OTA', 'partners@hostelworld.com', '+353-1-555-1200', 10.00, NULL, NULL, 'Active', 'Budget accommodation specialist', '2025-10-20 12:28:53', '2025-10-20 12:28:53');

-- --------------------------------------------------------

--
-- Table structure for table `channel_bookings`
--

CREATE TABLE `channel_bookings` (
  `id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `booking_reference` varchar(100) NOT NULL,
  `guest_name` varchar(255) NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `commission_amount` decimal(10,2) DEFAULT 0.00,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `booking_status` enum('Confirmed','Cancelled','No-show','Completed') DEFAULT 'Confirmed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `channel_bookings`
--

INSERT INTO `channel_bookings` (`id`, `channel_id`, `booking_reference`, `guest_name`, `check_in_date`, `check_out_date`, `total_amount`, `commission_amount`, `booking_date`, `booking_status`) VALUES
(1, 1, 'BK001', 'John Smith', '2024-01-15', '2024-01-17', 150.00, 0.00, '2025-10-20 12:28:53', 'Completed'),
(2, 1, 'BK002', 'Jane Doe', '2024-01-16', '2024-01-18', 200.00, 0.00, '2025-10-20 12:28:53', 'Completed'),
(3, 1, 'BK003', 'Bob Johnson', '2024-01-17', '2024-01-19', 175.00, 0.00, '2025-10-20 12:28:53', 'Confirmed'),
(4, 1, 'BK004', 'Alice Brown', '2024-01-18', '2024-01-20', 220.00, 0.00, '2025-10-20 12:28:53', 'Completed'),
(5, 1, 'BK005', 'Charlie Wilson', '2024-01-19', '2024-01-21', 190.00, 0.00, '2025-10-20 12:28:53', 'Confirmed'),
(6, 2, 'EXP001', 'David Lee', '2024-01-20', '2024-01-22', 180.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(7, 2, 'EXP002', 'Emma Davis', '2024-01-21', '2024-01-23', 165.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(8, 2, 'EXP003', 'Frank Miller', '2024-01-22', '2024-01-24', 195.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(9, 2, 'EXP004', 'Grace Taylor', '2024-01-23', '2024-01-25', 210.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(10, 3, 'AB001', 'Henry Clark', '2024-01-25', '2024-01-27', 120.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(11, 3, 'AB002', 'Iris Rodriguez', '2024-01-26', '2024-01-28', 135.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(12, 3, 'AB003', 'Jack Martinez', '2024-01-27', '2024-01-29', 145.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(17, 1, 'BK006', 'Oliver Garcia', '2024-02-01', '2024-02-03', 155.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(18, 1, 'BK007', 'Pamela Harris', '2024-02-02', '2024-02-04', 185.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(19, 1, 'BK008', 'Quincy Lewis', '2024-02-03', '2024-02-05', 165.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(20, 1, 'BK009', 'Rachel Martin', '2024-02-04', '2024-02-06', 195.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(21, 1, 'BK010', 'Samuel Nelson', '2024-02-05', '2024-02-07', 175.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(22, 2, 'EXP005', 'Tina Perez', '2024-02-06', '2024-02-08', 190.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(23, 2, 'EXP006', 'Ulysses Quinn', '2024-02-07', '2024-02-09', 205.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(24, 2, 'EXP007', 'Victoria Reed', '2024-02-08', '2024-02-10', 180.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(25, 2, 'EXP008', 'William Scott', '2024-02-09', '2024-02-11', 195.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(26, 2, 'EXP009', 'Xavier Turner', '2024-02-10', '2024-02-12', 175.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(27, 3, 'AB004', 'Yvonne Underwood', '2024-02-11', '2024-02-13', 125.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(28, 3, 'AB005', 'Zachary Vaughn', '2024-02-12', '2024-02-14', 140.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(29, 3, 'AB006', 'Amanda Walker', '2024-02-13', '2024-02-15', 130.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(35, 5, 'AGO001', 'George Diaz', '2024-02-19', '2024-02-21', 140.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(36, 5, 'AGO002', 'Helen Evans', '2024-02-20', '2024-02-22', 155.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(37, 5, 'AGO003', 'Ian Foster', '2024-02-21', '2024-02-23', 170.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(38, 5, 'AGO004', 'Jessica Gray', '2024-02-22', '2024-02-24', 160.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(39, 5, 'AGO005', 'Keith Hill', '2024-02-23', '2024-02-25', 175.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(40, 6, 'PRC001', 'Linda Ingram', '2024-02-24', '2024-02-26', 185.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(41, 6, 'PRC002', 'Mark Johnson', '2024-02-25', '2024-02-27', 195.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(42, 6, 'PRC003', 'Nina Kelly', '2024-02-26', '2024-02-28', 180.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(43, 7, 'HTC001', 'Oscar Lopez', '2024-02-27', '2024-03-01', 200.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(44, 7, 'HTC002', 'Paula Morris', '2024-02-28', '2024-03-02', 190.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(45, 8, 'TA001', 'Quinn Nelson', '2024-03-01', '2024-03-03', 165.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(46, 9, 'KAY001', 'Rose Owens', '2024-03-02', '2024-03-04', 175.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(47, 10, 'TVO001', 'Steve Parker', '2024-03-03', '2024-03-05', 185.00, 0.00, '2025-10-20 12:28:54', 'Completed'),
(48, 11, 'ORB001', 'Tara Quinn', '2024-03-04', '2024-03-06', 170.00, 0.00, '2025-10-20 12:28:54', 'Confirmed'),
(49, 12, 'HST001', 'Uma Roberts', '2024-03-05', '2024-03-07', 150.00, 0.00, '2025-10-20 12:28:54', 'Completed');

-- --------------------------------------------------------

--
-- Table structure for table `event_billing`
--

CREATE TABLE `event_billing` (
  `id` int(11) NOT NULL,
  `transaction_type` enum('Event Charge','Venue Charge','Refund') DEFAULT 'Event Charge',
  `reservation_id` int(11) DEFAULT NULL,
  `venue_id` int(11) DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('Cash','Card','GCash','Bank Transfer') DEFAULT 'Cash',
  `billing_status` enum('Pending','Paid','Failed','Refunded') DEFAULT 'Pending',
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_billing`
--

INSERT INTO `event_billing` (`id`, `transaction_type`, `reservation_id`, `venue_id`, `payment_amount`, `balance`, `payment_method`, `billing_status`, `transaction_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Event Charge', 1, 1, 900.00, 850.00, 'Card', 'Paid', '2025-10-20 20:10:25', 'hh', '2025-10-20 20:10:25', '2025-10-20 20:10:25');

-- --------------------------------------------------------

--
-- Table structure for table `event_reservation`
--

CREATE TABLE `event_reservation` (
  `id` int(11) NOT NULL,
  `event_title` varchar(100) NOT NULL,
  `event_organizer` varchar(100) NOT NULL,
  `event_organizer_contact` varchar(100) NOT NULL,
  `event_expected_attendees` int(11) NOT NULL,
  `event_description` text DEFAULT NULL,
  `event_venue_id` int(11) DEFAULT NULL,
  `event_status` enum('Pending','Checked In','Checked Out','Cancelled','Archived') DEFAULT 'Pending',
  `event_checkin` datetime DEFAULT NULL,
  `event_checkout` datetime DEFAULT NULL,
  `event_hour_count` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_reservation`
--

INSERT INTO `event_reservation` (`id`, `event_title`, `event_organizer`, `event_organizer_contact`, `event_expected_attendees`, `event_description`, `event_venue_id`, `event_status`, `event_checkin`, `event_checkout`, `event_hour_count`, `created_at`, `updated_at`) VALUES
(1, 'erm sasa', 'neee', 'dasdsds', 2, NULL, 1, 'Archived', '2025-10-21 03:40:00', '2025-10-21 20:40:00', 17, '2025-10-20 19:40:49', '2025-10-20 19:44:04'),
(2, 'sdass', 'sdasdas', '2323', 20, 'ss', 1, 'Archived', '2025-10-31 03:45:00', '2025-10-31 06:45:00', 3, '2025-10-20 19:46:03', '2025-10-22 10:32:09');

-- --------------------------------------------------------

--
-- Table structure for table `event_venues`
--

CREATE TABLE `event_venues` (
  `id` int(11) NOT NULL,
  `venue_name` varchar(100) NOT NULL,
  `venue_address` varchar(255) NOT NULL,
  `venue_capacity` int(11) NOT NULL,
  `venue_rate` decimal(10,2) DEFAULT NULL,
  `venue_description` text DEFAULT NULL,
  `venue_status` enum('Available','Booked','Maintenance') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_venues`
--

INSERT INTO `event_venues` (`id`, `venue_name`, `venue_address`, `venue_capacity`, `venue_rate`, `venue_description`, `venue_status`, `created_at`, `updated_at`) VALUES
(1, 'Ben House', '#25 Kalayaan B, Batasan Hills, QC', 20, 50.00, 'my haus\r\n', 'Available', '2025-10-20 19:01:14', '2025-10-20 19:01:14');

-- --------------------------------------------------------

--
-- Table structure for table `guests`
--

CREATE TABLE `guests` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `id_type` enum('Passport','Driver License','National ID') NOT NULL DEFAULT 'National ID',
  `id_number` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `guest_status` enum('Active','Archived') DEFAULT 'Active',
  `loyalty_status` enum('Regular','VIP') DEFAULT 'Regular',
  `stay_count` int(11) DEFAULT 0,
  `total_spend` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guests`
--

INSERT INTO `guests` (`id`, `first_name`, `last_name`, `email`, `phone`, `address`, `city`, `country`, `id_type`, `id_number`, `date_of_birth`, `nationality`, `notes`, `guest_status`, `loyalty_status`, `stay_count`, `total_spend`, `created_at`, `updated_at`) VALUES
(2, 'Jane', 'Smith', 'jane.smith@example.com', '0987654321', '456 Elm St', 'Othertown', 'Canada', 'Driver License', 'CD789012', '1985-05-15', 'Canadian', 'Sample guest data', 'Active', '', 5, 0.00, '2025-10-20 21:41:31', '2025-10-20 22:06:08'),
(3, 'Bob', 'Johnson', 'bob.johnson@example.com', '1122334455', '789 Oak St', 'Thirdtown', 'UK', 'National ID', 'ID123456', '1978-10-20', 'British', 'Sample guest data', 'Active', 'Regular', 0, 0.00, '2025-10-20 21:41:31', '2025-10-20 21:41:31'),
(4, 'Alice', 'Williams', 'alice.williams@example.com', '2233445566', '101 Pine St', 'Fourthtown', 'Australia', 'Passport', 'AE123456', '1992-08-25', 'Australian', 'Sample guest data', 'Active', 'Regular', 0, 0.00, '2025-10-20 21:41:31', '2025-10-20 21:41:31'),
(5, 'David', 'Brown', 'david.brown@example.com', '3344556677', '202 Maple St', 'Fifthtown', 'Germany', 'Driver License', 'DB123456', '1988-03-10', 'German', 'Sample guest data', 'Active', 'Regular', 0, 0.00, '2025-10-20 21:41:31', '2025-10-20 21:41:31'),
(6, 'Emily', 'Davis', 'emily.davis@example.com', '4455667788', '303 Birch St', 'Sixthtown', 'France', 'Passport', 'FD123456', '1995-06-12', 'French', 'Sample guest data', 'Active', 'Regular', 0, 0.00, '2025-10-20 21:41:31', '2025-10-20 21:41:31'),
(7, 'Michael', 'Wilson', 'michael.wilson@example.com', '5566778899', '404 Willow St', 'Seventhtown', 'Italy', 'Driver License', 'DW123456', '1982-11-25', 'Italian', 'Sample guest data', 'Active', 'Regular', 1, 0.00, '2025-10-20 21:41:31', '2025-10-21 00:24:10'),
(8, 'Sarah', 'Taylor', 'sarah.taylor@example.com', '6677889900', '505 Cedar St', 'Eighthtown', 'Spain', 'Passport', 'ST123456', '1990-02-18', 'Spanish', 'Sample guest data', 'Archived', 'Regular', 0, 0.00, '2025-10-20 21:41:31', '2025-10-21 21:23:18'),
(9, 'William', 'Anderson', 'william.anderson@example.com', '7788990011', '606 Pine St', 'Ninethtown', 'Japan', 'Driver License', 'WA123456', '1987-07-22', 'Japanese', 'Sample guest data', 'Active', 'Regular', 0, 0.00, '2025-10-20 21:41:31', '2025-10-20 21:41:31'),
(10, 'Olivia', 'Green', 'olivia.green@example.com', '8899001122', '707 Oak St', 'Tenth town', 'China', 'Passport', 'OG123456', '1993-04-28', 'Chinese', 'Sample guest data', 'Archived', 'Regular', 0, 0.00, '2025-10-20 21:41:31', '2025-10-20 21:42:04'),
(11, 'Jason', 'Benemerito', 'jasonbenemerito@gmail.com', '09284213364', NULL, NULL, NULL, 'Driver License', '123123', '2025-10-21', NULL, NULL, 'Active', 'Regular', 0, 0.00, '2025-10-20 21:44:56', '2025-10-20 21:44:56'),
(40, 'sdssd', 'asdsad', 'mikumokun27@gmail.com', NULL, NULL, NULL, NULL, 'National ID', '34234', '2025-10-22', NULL, NULL, 'Active', 'Regular', 0, 0.00, '2025-10-22 11:03:00', '2025-10-22 11:03:00'),
(41, 'Jasonsdd', 'Benemeritosdss', 'jasonbenemerito@gmail.com', NULL, NULL, NULL, NULL, 'National ID', 'dsdasds', '2025-11-08', NULL, NULL, 'Active', 'Regular', 0, 0.00, '2025-10-22 17:30:28', '2025-10-22 17:30:28');

-- --------------------------------------------------------

--
-- Table structure for table `housekeepers`
--

CREATE TABLE `housekeepers` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `hire_date` date NOT NULL,
  `status` enum('Active','Inactive','On Leave') DEFAULT 'Active',
  `specialty` varchar(100) DEFAULT NULL,
  `shift_preference` enum('Morning','Afternoon','Evening','Night','Flexible') DEFAULT 'Flexible',
  `max_rooms_per_day` int(11) DEFAULT 10,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `housekeepers`
--

INSERT INTO `housekeepers` (`id`, `first_name`, `last_name`, `employee_id`, `phone`, `email`, `hire_date`, `status`, `specialty`, `shift_preference`, `max_rooms_per_day`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Jason', 'Benemerito', '6969', '09284213364', 'jasonbenemerito@gmail.com', '2025-10-15', 'Active', 'Deep \'CLEANING\'', 'Morning', 10, NULL, '2025-10-20 14:10:11', '2025-10-20 14:10:11'),
(2, 'Maria', 'Clara', '222', '1232123', 'test@example.com', '2025-10-22', 'Active', 'Mopping', 'Evening', 10, NULL, '2025-10-20 20:30:28', '2025-10-20 20:30:28');

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping`
--

CREATE TABLE `housekeeping` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `housekeeper_id` int(11) DEFAULT NULL,
  `task_type` enum('Regular Cleaning','Deep Cleaning','Maintenance','Inspection','Emergency') DEFAULT 'Regular Cleaning',
  `priority` enum('Low','Normal','High','Urgent') DEFAULT 'Normal',
  `status` enum('Pending','In Progress','Completed','Cancelled','Skipped') DEFAULT 'Pending',
  `scheduled_date` date NOT NULL,
  `scheduled_time` time DEFAULT NULL,
  `actual_start_time` datetime DEFAULT NULL,
  `actual_end_time` datetime DEFAULT NULL,
  `estimated_duration_minutes` int(11) DEFAULT 60,
  `actual_duration_minutes` int(11) DEFAULT NULL,
  `cleaning_supplies_used` text DEFAULT NULL,
  `issues_found` text DEFAULT NULL,
  `maintenance_required` tinyint(1) DEFAULT 0,
  `guest_feedback` text DEFAULT NULL,
  `supervisor_notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `housekeeping`
--

INSERT INTO `housekeeping` (`id`, `room_id`, `housekeeper_id`, `task_type`, `priority`, `status`, `scheduled_date`, `scheduled_time`, `actual_start_time`, `actual_end_time`, `estimated_duration_minutes`, `actual_duration_minutes`, `cleaning_supplies_used`, `issues_found`, `maintenance_required`, `guest_feedback`, `supervisor_notes`, `created_by`, `created_at`, `updated_at`) VALUES
(12, 3, 1, 'Regular Cleaning', 'Low', 'Completed', '2025-10-20', '09:00:00', NULL, '2025-10-21 05:24:16', 60, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-10-20 21:24:11', '2025-10-20 21:24:16'),
(13, 24, NULL, 'Regular Cleaning', 'Normal', 'Completed', '2025-10-22', '09:00:00', NULL, '2025-10-22 21:12:04', 60, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-10-22 07:51:27', '2025-10-22 13:12:04'),
(16, 23, NULL, 'Regular Cleaning', 'Normal', 'Completed', '2025-10-22', '09:00:00', NULL, '2025-10-22 21:04:26', 60, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-10-22 07:51:33', '2025-10-22 13:04:26'),
(17, 23, NULL, 'Regular Cleaning', 'Normal', 'Completed', '2025-10-22', '09:00:00', NULL, '2025-10-22 21:04:23', 60, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-10-22 07:51:33', '2025-10-22 13:04:23'),
(18, 14, NULL, 'Regular Cleaning', 'Normal', 'Completed', '2025-10-22', '09:00:00', NULL, '2025-10-22 21:04:14', 60, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-10-22 07:51:38', '2025-10-22 13:04:14'),
(19, 9, NULL, 'Regular Cleaning', 'Normal', 'Completed', '2025-10-22', '09:00:00', NULL, '2025-10-22 21:04:17', 60, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-10-22 07:51:52', '2025-10-22 13:04:17'),
(20, 18, NULL, 'Regular Cleaning', 'Normal', 'Completed', '2025-10-22', '09:00:00', NULL, '2025-10-22 21:04:20', 60, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-10-22 07:51:53', '2025-10-22 13:04:20'),
(21, 3, 1, 'Regular Cleaning', 'Low', 'Completed', '2025-10-22', '09:00:00', NULL, '2025-10-22 21:12:22', 60, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-10-22 13:09:42', '2025-10-22 13:12:22'),
(27, 3, 1, 'Regular Cleaning', 'Low', 'Completed', '2025-10-22', '09:00:00', NULL, '2025-10-22 21:41:54', 60, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-10-22 13:35:18', '2025-10-22 13:41:54'),
(28, 4, 1, 'Regular Cleaning', 'Low', 'Completed', '2025-10-22', '09:00:00', NULL, '2025-10-22 21:42:13', 60, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-10-22 13:42:08', '2025-10-22 13:42:13'),
(29, 8, 1, 'Regular Cleaning', 'Low', 'In Progress', '2025-10-22', '09:00:00', NULL, NULL, 60, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-10-22 13:42:19', '2025-10-22 13:42:19');

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping_supplies`
--

CREATE TABLE `housekeeping_supplies` (
  `id` int(11) NOT NULL,
  `supply_name` varchar(100) NOT NULL,
  `category` enum('Cleaning','Maintenance','Linens','Amenities','Equipment') NOT NULL,
  `current_stock` decimal(10,2) DEFAULT 0.00,
  `unit_of_measure` enum('pieces','liters','kg','boxes','sets') DEFAULT 'pieces',
  `minimum_stock_level` decimal(10,2) DEFAULT 10.00,
  `supplier` varchar(100) DEFAULT NULL,
  `last_restock_date` date DEFAULT NULL,
  `cost_per_unit` decimal(8,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `housekeeping_supplies`
--

INSERT INTO `housekeeping_supplies` (`id`, `supply_name`, `category`, `current_stock`, `unit_of_measure`, `minimum_stock_level`, `supplier`, `last_restock_date`, `cost_per_unit`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'All-Purpose Cleaner', 'Cleaning', 25.50, 'liters', 10.00, 'CleanCo Supplies', NULL, 15.50, 'Multi-surface cleaner for general use', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(2, 'Glass Cleaner', 'Cleaning', 15.20, 'liters', 8.00, 'CleanCo Supplies', NULL, 12.75, 'Streak-free glass and mirror cleaner', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(3, 'Disinfectant Spray', 'Cleaning', 12.80, 'liters', 5.00, 'CleanCo Supplies', NULL, 18.90, 'Hospital-grade disinfectant', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(4, 'Toilet Bowl Cleaner', 'Cleaning', 18.00, 'liters', 6.00, 'CleanCo Supplies', NULL, 14.25, 'Concentrated toilet cleaner', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(5, 'Floor Cleaner', 'Cleaning', 8.50, 'liters', 12.00, 'CleanCo Supplies', NULL, 22.00, 'Heavy-duty floor cleaning solution', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(6, 'Carpet Shampoo', 'Cleaning', 6.20, 'liters', 4.00, 'CleanCo Supplies', NULL, 28.50, 'Professional carpet cleaning solution', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(7, 'Vacuum Cleaner Bags', 'Equipment', 30.00, 'pieces', 10.00, 'Maintenance Plus', NULL, 8.75, 'HEPA filter replacement bags', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(8, 'Light Bulbs (LED)', 'Equipment', 45.00, 'pieces', 20.00, 'Maintenance Plus', NULL, 12.00, 'Energy-efficient LED bulbs', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(9, 'Air Freshener Refills', 'Equipment', 22.00, 'pieces', 15.00, 'Maintenance Plus', NULL, 9.50, 'Room freshener cartridges', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(10, 'Batteries (AA)', 'Equipment', 80.00, 'pieces', 50.00, 'Maintenance Plus', NULL, 0.85, 'Alkaline batteries for remotes', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(11, 'Extension Cords', 'Equipment', 12.00, 'pieces', 5.00, 'Maintenance Plus', NULL, 25.00, 'Heavy-duty extension cords', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(12, 'Bed Sheets (Queen)', 'Linens', 45.00, 'pieces', 20.00, 'Linen World', NULL, 35.00, 'High-thread count cotton sheets', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(13, 'Pillow Cases', 'Linens', 60.00, 'pieces', 25.00, 'Linen World', NULL, 8.50, 'Standard pillow cases', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(14, 'Towels (Bath)', 'Linens', 80.00, 'pieces', 30.00, 'Linen World', NULL, 12.75, 'Absorbent cotton bath towels', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(15, 'Towels (Hand)', 'Linens', 95.00, 'pieces', 40.00, 'Linen World', NULL, 6.25, 'Small hand towels for bathrooms', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(16, 'Towels (Face)', 'Linens', 110.00, 'pieces', 45.00, 'Linen World', NULL, 4.50, 'Soft facial towels', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(17, 'Blankets', 'Linens', 25.00, 'pieces', 10.00, 'Linen World', NULL, 45.00, 'Thermal blankets for cold weather', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(18, 'Toilet Paper', 'Amenities', 200.00, 'pieces', 50.00, 'Hotel Essentials', NULL, 2.25, 'Premium 2-ply toilet paper', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(19, 'Tissue Boxes', 'Amenities', 75.00, 'pieces', 30.00, 'Hotel Essentials', NULL, 3.50, 'Facial tissue boxes', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(20, 'Shampoo (Individual)', 'Amenities', 180.00, 'pieces', 60.00, 'Hotel Essentials', NULL, 1.85, 'Travel-size shampoo bottles', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(21, 'Conditioner (Individual)', 'Amenities', 165.00, 'pieces', 60.00, 'Hotel Essentials', NULL, 1.95, 'Travel-size conditioner', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(22, 'Body Wash (Individual)', 'Amenities', 155.00, 'pieces', 60.00, 'Hotel Essentials', NULL, 2.10, 'Travel-size body wash', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(23, 'Lotion (Individual)', 'Amenities', 140.00, 'pieces', 50.00, 'Hotel Essentials', NULL, 2.25, 'Travel-size hand lotion', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(24, 'Coffee Pods (Regular)', 'Equipment', 85.00, 'pieces', 40.00, 'Beverage Distributors', NULL, 0.65, 'Premium coffee pods', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(25, 'Coffee Pods (Decaf)', 'Equipment', 55.00, 'pieces', 25.00, 'Beverage Distributors', NULL, 0.70, 'Decaffeinated coffee pods', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(26, 'Tea Bags', 'Equipment', 120.00, 'pieces', 50.00, 'Beverage Distributors', NULL, 0.35, 'Assorted tea bag selection', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(27, 'Sugar Packets', 'Equipment', 300.00, 'pieces', 100.00, 'Beverage Distributors', NULL, 0.15, 'Individual sugar packets', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(28, 'Coffee Creamer', 'Equipment', 45.00, 'pieces', 20.00, 'Beverage Distributors', NULL, 0.85, 'Individual creamer cups', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(29, 'Hand Sanitizer', 'Cleaning', 3.20, 'liters', 8.00, 'CleanCo Supplies', NULL, 22.00, 'Alcohol-based hand sanitizer', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(30, 'Laundry Detergent', 'Cleaning', 2.50, 'kg', 10.00, 'CleanCo Supplies', NULL, 18.50, 'Professional laundry detergent', '2025-10-20 12:29:00', '2025-10-20 12:29:00'),
(31, 'Out of Stock Item', 'Equipment', 6.00, 'pieces', 5.00, 'Test Supplier', NULL, 50.00, 'This item is out of stock for testing', '2025-10-20 12:29:00', '2025-10-20 14:11:22');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `movement_type` enum('IN','OUT') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_movements`
--

INSERT INTO `inventory_movements` (`id`, `item_id`, `movement_type`, `quantity`, `reason`, `movement_date`, `user_id`, `reference_id`, `created_at`) VALUES
(1, 1, 'OUT', 1, 'Stock adjustment', '2025-10-21 19:50:41', 1, 'ADJUSTMENT', '2025-10-21 19:50:41');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_description` text DEFAULT NULL,
  `item_category` varchar(100) DEFAULT NULL,
  `unit_of_measure` varchar(50) DEFAULT 'pcs',
  `current_stock` int(11) DEFAULT 0,
  `minimum_stock` int(11) DEFAULT 0,
  `maximum_stock` int(11) DEFAULT 0,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `item_status` enum('Active','Inactive','Discontinued') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `item_name`, `item_description`, `item_category`, `unit_of_measure`, `current_stock`, `minimum_stock`, `maximum_stock`, `unit_cost`, `unit_price`, `supplier_id`, `item_status`, `created_at`, `updated_at`) VALUES
(1, 'cat food', 'test!', 'Food & Beverage', 'kg', 4, 2, 10, 20.00, 25.00, 1, 'Active', '2025-10-21 19:50:20', '2025-10-21 19:50:41');

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
  `leads_generated` int(11) DEFAULT 0,
  `conversions` int(11) DEFAULT 0,
  `revenue_generated` decimal(10,2) DEFAULT 0.00,
  `roi_percentage` decimal(5,2) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marketing_campaigns`
--

INSERT INTO `marketing_campaigns` (`id`, `name`, `description`, `campaign_type`, `target_audience`, `start_date`, `end_date`, `budget`, `status`, `leads_generated`, `conversions`, `revenue_generated`, `roi_percentage`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'RESERVE TO US', 'discounted upon registration', 'email', NULL, '2025-10-22', '2025-10-23', 5000.00, 'active', 0, 0, 0.00, NULL, 1, '2025-10-22 14:11:11', '2025-10-22 14:11:11');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotional_offers`
--

INSERT INTO `promotional_offers` (`id`, `code`, `name`, `description`, `offer_type`, `discount_value`, `discount_percentage`, `min_stay_nights`, `max_discount_amount`, `applicable_room_types`, `applicable_rate_plans`, `usage_limit`, `usage_count`, `valid_from`, `valid_until`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '343423', 'discount coupon 1', 'test', 'percentage_discount', NULL, 10.00, 1, NULL, 'Suite', NULL, 20, 0, '2025-10-23', '2025-10-29', 1, '2025-10-22 14:22:22', '2025-10-22 14:22:22'),
(8, '343242', 'dasds', NULL, 'fixed_amount_discount', 600.00, NULL, 1, NULL, 'Double', NULL, NULL, 0, '2025-10-23', '2025-11-05', 1, '2025-10-22 14:25:18', '2025-10-22 14:25:18');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `reservation_type` enum('Room','Event') NOT NULL,
  `reservation_date` datetime NOT NULL,
  `reservation_hour_count` int(11) NOT NULL,
  `check_in_date` datetime NOT NULL,
  `check_out_date` datetime NOT NULL,
  `reservation_status` enum('Pending','Checked In','Checked Out','Cancelled','Archived') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `guest_id`, `room_id`, `reservation_type`, `reservation_date`, `reservation_hour_count`, `check_in_date`, `check_out_date`, `reservation_status`, `created_at`, `updated_at`) VALUES
(7, 7, 1, 'Room', '2025-10-20 23:42:47', 8, '2025-10-21 05:42:00', '2025-10-21 13:42:00', 'Archived', '2025-10-20 21:42:47', '2025-10-21 00:24:14'),
(10, 2, 13, 'Room', '2025-10-21 00:10:33', 16, '2025-10-21 06:10:00', '2025-10-21 22:10:00', 'Checked In', '2025-10-20 22:10:33', '2025-10-21 02:55:26'),
(11, 2, 13, 'Room', '2025-10-21 00:14:26', 16, '2025-10-30 06:14:00', '2025-10-30 22:14:00', 'Pending', '2025-10-20 22:14:26', '2025-10-20 22:14:26'),
(12, 4, 12, 'Room', '2025-10-21 22:07:00', 16, '2025-10-22 04:06:00', '2025-10-22 20:06:00', 'Pending', '2025-10-21 20:07:00', '2025-10-21 20:07:00'),
(14, NULL, 13, 'Room', '2025-10-21 22:44:57', 16, '2025-10-28 04:44:00', '2025-10-28 20:44:00', 'Pending', '2025-10-21 20:44:57', '2025-10-21 20:44:57'),
(15, 11, 1, 'Room', '2025-10-22 04:50:43', 8, '2025-10-30 10:50:00', '2025-10-30 18:50:00', 'Pending', '2025-10-22 02:50:43', '2025-10-22 02:50:43'),
(16, 11, 10, 'Room', '2025-10-22 12:25:51', 8, '2025-10-30 18:25:00', '2025-10-31 02:25:00', 'Pending', '2025-10-22 10:25:51', '2025-10-22 10:25:51'),
(17, 4, 9, 'Room', '2025-10-22 12:58:42', 16, '2025-10-23 18:58:00', '2025-10-24 10:58:00', 'Pending', '2025-10-22 10:58:42', '2025-10-22 10:58:42'),
(18, 40, 10, 'Room', '2025-10-22 13:03:00', 8, '2025-10-22 13:03:00', '2025-10-22 21:03:00', 'Checked In', '2025-10-22 11:03:00', '2025-10-22 11:03:00'),
(19, 4, 10, 'Room', '2025-10-22 19:14:55', 16, '2025-11-05 01:14:00', '2025-11-05 17:14:00', 'Pending', '2025-10-22 17:14:55', '2025-10-22 17:14:55'),
(20, 41, 3, 'Room', '2025-10-22 19:30:28', 8, '2025-10-22 19:30:28', '2025-10-23 03:30:28', 'Checked In', '2025-10-22 17:30:28', '2025-10-22 17:30:28');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type` enum('Single','Double','Deluxe','Suite') DEFAULT 'Single',
  `room_floor` varchar(10) NOT NULL,
  `room_status` enum('Vacant','Occupied','Maintenance','Cleaning') DEFAULT 'Vacant',
  `room_rate` decimal(10,2) GENERATED ALWAYS AS (case `room_type` when 'Single' then 1500.00 when 'Double' then 2500.00 when 'Deluxe' then 3500.00 when 'Suite' then 4500.00 else 0 end) VIRTUAL,
  `room_max_guests` int(11) GENERATED ALWAYS AS (case `room_type` when 'Single' then 1 when 'Double' then 2 when 'Deluxe' then 3 when 'Suite' then 4 else 0 end) VIRTUAL,
  `room_amenities` text GENERATED ALWAYS AS (case `room_type` when 'Single' then 'Bathroom' when 'Double' then 'TV, Bathroom' when 'Deluxe' then 'TV, Air Conditioning, Bathroom' when 'Suite' then 'TV, Air Conditioning, Bathroom, Kitchen' else '' end) VIRTUAL,
  `room_last_cleaned` timestamp NULL DEFAULT NULL,
  `room_maintenance_notes` text DEFAULT NULL,
  `room_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `room_updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type`, `room_floor`, `room_status`, `room_last_cleaned`, `room_maintenance_notes`, `room_created_at`, `room_updated_at`) VALUES
(1, '101', 'Single', '1', 'Vacant', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(2, '102', 'Double', '1', 'Occupied', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(3, '103', 'Deluxe', '2', 'Occupied', '2025-10-22 13:41:54', NULL, '2025-10-22 13:33:53', '2025-10-22 17:30:28'),
(4, '104', 'Suite', '2', 'Vacant', '2025-10-22 13:42:13', NULL, '2025-10-22 13:33:53', '2025-10-22 13:42:13'),
(5, '105', 'Single', '3', 'Vacant', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(6, '201', 'Single', '2', 'Vacant', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(7, '202', 'Double', '2', 'Occupied', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(8, '203', 'Deluxe', '2', 'Cleaning', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:42:19'),
(9, '204', 'Suite', '2', 'Maintenance', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(10, '205', 'Single', '2', '', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 17:14:55'),
(11, '301', 'Single', '3', 'Vacant', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(12, '302', 'Double', '3', 'Occupied', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(13, '303', 'Deluxe', '3', 'Maintenance', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(14, '304', 'Suite', '3', 'Maintenance', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(15, '305', 'Single', '3', 'Vacant', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(16, '401', 'Single', '4', 'Vacant', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(17, '402', 'Double', '4', 'Occupied', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(18, '403', 'Deluxe', '4', 'Maintenance', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(19, '404', 'Suite', '4', 'Maintenance', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(20, '405', 'Single', '4', 'Vacant', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(21, '501', 'Single', '5', 'Vacant', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(22, '502', 'Double', '5', 'Occupied', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(23, '503', 'Deluxe', '5', 'Maintenance', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(24, '504', 'Suite', '5', 'Maintenance', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53'),
(25, '505', 'Single', '5', 'Vacant', NULL, NULL, '2025-10-22 13:33:53', '2025-10-22 13:33:53');

-- --------------------------------------------------------

--
-- Table structure for table `room_billing`
--

CREATE TABLE `room_billing` (
  `id` int(11) NOT NULL,
  `transaction_type` enum('Room Charge','Event Charge','Refund') DEFAULT 'Room Charge',
  `reservation_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('Cash','Card','GCash','Bank Transfer') DEFAULT 'Cash',
  `billing_status` enum('Pending','Paid','Failed','Refunded') DEFAULT 'Pending',
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_billing`
--

INSERT INTO `room_billing` (`id`, `transaction_type`, `reservation_id`, `room_id`, `payment_amount`, `balance`, `payment_method`, `billing_status`, `transaction_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Room Charge', 19, 10, 4000.00, 3000.00, 'Cash', 'Paid', '2025-10-22 17:38:24', 'test invoice', '2025-10-22 17:38:24', '2025-10-22 17:38:24'),
(2, 'Room Charge', 20, 3, 4000.00, 3500.00, 'Cash', 'Paid', '2025-10-22 17:40:46', NULL, '2025-10-22 17:40:46', '2025-10-22 17:40:46'),
(3, 'Room Charge', 7, 1, 2000.00, 1500.00, 'Cash', 'Paid', '2025-10-22 17:45:48', NULL, '2025-10-22 17:45:48', '2025-10-22 17:45:48'),
(4, 'Room Charge', 12, 12, 6000.00, 5000.00, 'Cash', 'Paid', '2025-10-22 17:46:18', NULL, '2025-10-22 17:46:18', '2025-10-22 17:46:18');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `supplier_status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `supplier_name`, `contact_person`, `email`, `phone`, `address`, `city`, `state`, `postal_code`, `country`, `payment_terms`, `supplier_status`, `created_at`, `updated_at`) VALUES
(1, 'Jason Corp.', 'Jason Benemerito', 'jasonbenemerito@gmail.com', '09284213364', 'Batasan Hills', 'Quezon City', 'Batangas', '332', 'Philippines', 'COD', 'Active', '2025-10-21 18:52:24', '2025-10-21 18:52:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, '$2y$10$pPV6rVpDUOHZS85S44LjBuW8xHlgHQpW.rprE1mDGnLtHtL0lWfgO', 'jasonbenemerito@gmail.com', NULL, NULL, '2025-10-20 15:28:14', '2025-10-20 15:28:14'),
(3, NULL, '$2y$10$XIenBCPecGoJ3FcbszJpH.g0I6bWOL574fjEOmS1z0Hbw1jOIQDJG', 'mikumokun27@gmail.com', NULL, NULL, '2025-10-20 15:34:26', '2025-10-20 15:34:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `campaign_performance`
--
ALTER TABLE `campaign_performance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_campaign_date` (`campaign_id`,`performance_date`),
  ADD KEY `idx_campaign_id` (`campaign_id`),
  ADD KEY `idx_performance_date` (`performance_date`);

--
-- Indexes for table `channels`
--
ALTER TABLE `channels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_channel_name` (`channel_name`),
  ADD KEY `idx_channel_type` (`channel_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `channel_bookings`
--
ALTER TABLE `channel_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_channel_id` (`channel_id`),
  ADD KEY `idx_booking_reference` (`booking_reference`),
  ADD KEY `idx_check_in_date` (`check_in_date`),
  ADD KEY `idx_booking_date` (`booking_date`);

--
-- Indexes for table `event_billing`
--
ALTER TABLE `event_billing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reservation_id` (`reservation_id`),
  ADD KEY `idx_venue_id` (`venue_id`),
  ADD KEY `idx_billing_status` (`billing_status`),
  ADD KEY `idx_transaction_date` (`transaction_date`);

--
-- Indexes for table `event_reservation`
--
ALTER TABLE `event_reservation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_venue_id` (`event_venue_id`);

--
-- Indexes for table `event_venues`
--
ALTER TABLE `event_venues`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_last_name` (`last_name`),
  ADD KEY `idx_guest_status` (`guest_status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `housekeepers`
--
ALTER TABLE `housekeepers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_shift_preference` (`shift_preference`),
  ADD KEY `idx_specialty` (`specialty`);

--
-- Indexes for table `housekeeping`
--
ALTER TABLE `housekeeping`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_housekeeper_id` (`housekeeper_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled_date` (`scheduled_date`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_task_type` (`task_type`),
  ADD KEY `idx_maintenance_required` (`maintenance_required`);

--
-- Indexes for table `housekeeping_supplies`
--
ALTER TABLE `housekeeping_supplies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_supply_name` (`supply_name`),
  ADD KEY `idx_minimum_stock` (`minimum_stock_level`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_movement_date` (`movement_date`),
  ADD KEY `idx_movement_type` (`movement_type`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_name` (`item_name`),
  ADD KEY `idx_item_category` (`item_category`),
  ADD KEY `idx_item_status` (`item_status`),
  ADD KEY `fk_supplier_id` (`supplier_id`);

--
-- Indexes for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campaign_type` (`campaign_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_end_date` (`end_date`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `promotional_offers`
--
ALTER TABLE `promotional_offers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_valid_dates` (`valid_from`,`valid_until`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_offer_type` (`offer_type`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_guest_id` (`guest_id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_reservation_date` (`reservation_date`),
  ADD KEY `idx_check_in_date` (`check_in_date`),
  ADD KEY `idx_check_out_date` (`check_out_date`),
  ADD KEY `idx_reservation_status` (`reservation_status`),
  ADD KEY `idx_reservation_type` (`reservation_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_room_number` (`room_number`),
  ADD KEY `idx_room_created_at` (`room_created_at`);

--
-- Indexes for table `room_billing`
--
ALTER TABLE `room_billing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reservation_id` (`reservation_id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_billing_status` (`billing_status`),
  ADD KEY `idx_transaction_date` (`transaction_date`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_supplier_name` (`supplier_name`),
  ADD KEY `idx_supplier_status` (`supplier_status`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `campaign_performance`
--
ALTER TABLE `campaign_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `channels`
--
ALTER TABLE `channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `channel_bookings`
--
ALTER TABLE `channel_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `event_billing`
--
ALTER TABLE `event_billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `event_reservation`
--
ALTER TABLE `event_reservation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `event_venues`
--
ALTER TABLE `event_venues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `housekeepers`
--
ALTER TABLE `housekeepers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `housekeeping`
--
ALTER TABLE `housekeeping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `housekeeping_supplies`
--
ALTER TABLE `housekeeping_supplies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `promotional_offers`
--
ALTER TABLE `promotional_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `room_billing`
--
ALTER TABLE `room_billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `campaign_performance`
--
ALTER TABLE `campaign_performance`
  ADD CONSTRAINT `campaign_performance_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `channel_bookings`
--
ALTER TABLE `channel_bookings`
  ADD CONSTRAINT `channel_bookings_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_billing`
--
ALTER TABLE `event_billing`
  ADD CONSTRAINT `event_billing_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `event_reservation` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `event_billing_ibfk_2` FOREIGN KEY (`venue_id`) REFERENCES `event_venues` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_reservation`
--
ALTER TABLE `event_reservation`
  ADD CONSTRAINT `event_reservation_ibfk_1` FOREIGN KEY (`event_venue_id`) REFERENCES `event_venues` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `housekeeping`
--
ALTER TABLE `housekeeping`
  ADD CONSTRAINT `housekeeping_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `housekeeping_ibfk_2` FOREIGN KEY (`housekeeper_id`) REFERENCES `housekeepers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `inventory_movements_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `fk_supplier_id` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `room_billing`
--
ALTER TABLE `room_billing`
  ADD CONSTRAINT `room_billing_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `room_billing_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
