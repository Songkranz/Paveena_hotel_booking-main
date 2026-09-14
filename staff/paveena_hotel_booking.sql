-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 14, 2026 at 04:18 PM
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
-- Database: `paveena_hotel_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `checkin_date` date NOT NULL,
  `checkout_date` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','checked_in','checked_out','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `room_id`, `checkin_date`, `checkout_date`, `total_price`, `status`, `created_at`) VALUES
(1, 1, 3, '2026-09-12', '2026-09-14', 1600.00, 'checked_out', '2026-09-09 15:35:50'),
(2, 1, 1, '2026-09-12', '2026-09-15', 1500.00, 'checked_out', '2026-09-09 16:22:23'),
(3, 1, 3, '2026-09-11', '2026-09-01', -8000.00, 'pending', '2026-09-09 17:18:28'),
(4, 3, 6, '2026-09-13', '2026-09-16', 2650.00, 'checked_out', '2026-09-12 17:53:54'),
(5, 3, 6, '2026-09-23', '2026-09-25', 1000.00, 'cancelled', '2026-09-12 17:57:35'),
(6, 3, 6, '2026-09-16', '2026-09-19', 2150.00, 'checked_out', '2026-09-13 08:40:44'),
(7, 3, 7, '2026-09-16', '2026-09-29', 7150.00, 'checked_out', '2026-09-13 08:56:24'),
(8, 3, 3, '2026-09-16', '2026-09-18', 2250.00, 'checked_out', '2026-09-14 12:11:26'),
(9, 3, 1, '2026-09-17', '2026-09-21', 2650.00, 'pending', '2026-09-14 12:23:40'),
(10, 3, 8, '2026-09-16', '2026-09-29', 20150.00, 'checked_out', '2026-09-14 13:35:39');

-- --------------------------------------------------------

--
-- Table structure for table `booking_services`
--

CREATE TABLE `booking_services` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_services`
--

INSERT INTO `booking_services` (`id`, `booking_id`, `service_name`, `price`) VALUES
(1, 4, 'เตียงเสริม', 300.00),
(2, 4, 'อาหารเช้า (ต่อคืน)', 150.00),
(3, 4, 'รับส่งสนามบิน', 500.00),
(4, 4, 'เช็คเอาต์ล่าช้า', 200.00),
(5, 6, 'เตียงเสริม', 300.00),
(6, 6, 'อาหารเช้า (ต่อคืน)', 150.00),
(7, 6, 'เช็คเอาต์ล่าช้า', 200.00),
(8, 7, 'เตียงเสริม', 300.00),
(9, 7, 'อาหารเช้า (ต่อคืน)', 150.00),
(10, 7, 'เช็คเอาต์ล่าช้า', 200.00),
(11, 8, 'เตียงเสริม', 300.00),
(12, 8, 'อาหารเช้า (ต่อคืน)', 150.00),
(13, 8, 'เช็คเอาต์ล่าช้า', 200.00),
(14, 9, 'เตียงเสริม', 300.00),
(15, 9, 'อาหารเช้า (ต่อคืน)', 150.00),
(16, 9, 'เช็คเอาต์ล่าช้า', 200.00),
(17, 10, 'เตียงเสริม', 300.00),
(18, 10, 'อาหารเช้า (ต่อคืน)', 150.00),
(19, 10, 'เช็คเอาต์ล่าช้า', 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `slip_image` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `amount`, `slip_image`, `payment_status`, `verified_by`, `paid_at`) VALUES
(1, 1, 1600.00, 'slip_1_1788968157.jpg', 'verified', 2, '2026-09-09 15:35:57'),
(2, 2, 1500.00, 'slip_2_1788970952.jpg', 'verified', 1, '2026-09-09 16:22:32'),
(3, 4, 2650.00, 'slip_4_1789235640.png', 'verified', 2, '2026-09-12 17:54:00'),
(4, 5, 1000.00, 'slip_5_1789235860.png', 'rejected', NULL, '2026-09-12 17:57:40'),
(5, 6, 2150.00, 'slip_6_1789288851.jpg', 'verified', 1, '2026-09-13 08:40:51'),
(6, 7, 7150.00, 'slip_7_1789289796.jpg', 'verified', 1, '2026-09-13 08:56:36'),
(7, 8, 2250.00, 'slip_8_1789387896.jpg', 'verified', 1, '2026-09-14 12:11:36'),
(8, 10, 20150.00, 'slip_10_1789392945.jpg', 'verified', 2, '2026-09-14 13:35:45');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `status` enum('available','occupied','maintenance') NOT NULL DEFAULT 'available',
  `image` varchar(255) DEFAULT NULL,
  `image_2` varchar(255) DEFAULT NULL,
  `image_3` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type_id`, `status`, `image`, `image_2`, `image_3`) VALUES
(1, '101', 1, 'available', NULL, NULL, NULL),
(2, '102', 1, 'available', NULL, NULL, NULL),
(3, '201', 2, 'available', NULL, NULL, NULL),
(4, '202', 2, 'available', NULL, NULL, NULL),
(6, '3242', 1, 'available', 'room_6_1789286662.jpg', NULL, NULL),
(7, '4356', 1, 'available', 'room_7_1_1789287060.jpg', 'room_7_2_1789287060.jpg', 'room_7_3_1789287060.webp'),
(8, '103', 3, 'available', 'room_8_1_1789392740.jpg', 'room_8_2_1789392740.jpg', 'room_8_3_1789392740.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `max_guests` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`id`, `type_name`, `price_per_night`, `description`, `max_guests`) VALUES
(1, 'ห้องเดี่ยว', 500.00, 'ห้องพักสำหรับ 1 ท่าน เตียงเดี่ยว', 1),
(2, 'ห้องคู่', 800.00, 'ห้องพักสำหรับ 2 ท่าน เตียงคู่หรือเตียงเดี่ยว 2 เตียง', 2),
(3, 'ห้องครอบครัว', 1500.00, 'ห้องพักสำหรับครอบครัว 4 ท่าน', 4);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `role` enum('customer','staff','admin') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `role`, `created_at`) VALUES
(1, 'จิะพงษ์', 'jirapong1212549@gmail.com', '$2y$10$8MjlFw5RiiJKNnvuFB0LGO/gyy7jX4idESg4v1gQ0Km/AixildRPi', '080 068 9800', 'admin', '2026-09-09 11:23:12'),
(2, 'Bata tester', 'staff@test.com', '$2y$10$/6yjKeqxmuilbUtXX/MBnOFKCpyjwwNaQW5gemJFPE3WUCUUCoCaS', NULL, 'staff', '2026-09-09 15:32:11'),
(3, 'SOngkrnz', '1212@gmail.com', '$2y$10$47/0hJgXB8jxXiG3kXT6keuKMTweCkXXu.JH6AhlA4soKmBv7A5XC', '191', 'customer', '2026-09-12 17:52:45'),
(4, 'SOngkrnz', 'gggg@gmail.com', '$2y$10$X5o4OM8FG9jnqgtdsMl9F.FLI7E7smpSB8BPNmD2w3dV7Z7K4SOE.', '', 'staff', '2026-09-14 13:50:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `booking_services`
--
ALTER TABLE `booking_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `room_type_id` (`room_type_id`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `booking_services`
--
ALTER TABLE `booking_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

--
-- Constraints for table `booking_services`
--
ALTER TABLE `booking_services`
  ADD CONSTRAINT `booking_services_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
