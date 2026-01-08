-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 01, 2026 at 08:41 AM
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
-- Database: `drivenow`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` varchar(20) DEFAULT NULL,
  `booking_datetime` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `return_date` date DEFAULT NULL,
  `return_datetime` datetime DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `total_price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `vehicle_id`, `booking_date`, `booking_time`, `booking_datetime`, `created_at`, `return_date`, `return_datetime`, `status`, `total_price`) VALUES
(1, 2, 1, '2025-12-18', NULL, NULL, '2025-12-18 13:46:57', NULL, NULL, 'pending', 0.00),
(2, 2, 1, '2025-12-18', NULL, NULL, '2025-12-18 14:20:34', NULL, NULL, 'pending', 0.00),
(3, 2, 2, '0000-00-00', NULL, '2025-12-18 01:03:00', '2025-12-18 15:27:00', NULL, NULL, 'pending', 0.00),
(4, 2, 7, '0000-00-00', NULL, '2025-12-20 11:00:00', '2025-12-19 04:43:17', NULL, NULL, 'pending', 0.00),
(6, 8, 4, '0000-00-00', NULL, '2025-12-19 21:00:00', '2025-12-19 09:51:35', NULL, NULL, 'pending', 0.00),
(7, 1, 1, '2026-01-01', NULL, NULL, '2026-01-01 07:09:31', '2026-01-03', NULL, 'confirmed', 5000.00),
(8, 5, 6, '2026-01-01', NULL, NULL, '2026-01-01 07:26:38', '2026-01-05', '2026-01-05 23:59:59', 'confirmed', 2500.00);

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sender` enum('user','admin') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `user_id`, `sender`, `message`, `is_read`, `created_at`) VALUES
(1, 5, 'user', 'hello', 1, '2026-01-01 07:38:26'),
(2, 5, 'admin', 'yes', 1, '2026-01-01 07:38:55'),
(3, 5, 'admin', 'what ahppened', 1, '2026-01-01 07:39:02'),
(4, 5, 'admin', 'ok se', 1, '2026-01-01 07:39:12'),
(5, 5, 'user', 'sir', 1, '2026-01-01 07:39:58'),
(6, 5, 'admin', 'yes', 1, '2026-01-01 07:40:35'),
(7, 5, 'admin', 'sure', 1, '2026-01-01 07:40:38'),
(8, 5, 'admin', 'hu', 1, '2026-01-01 07:40:43'),
(9, 5, 'admin', 'hello', 1, '2026-01-01 07:40:46');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_verified` tinyint(1) DEFAULT 0,
  `id_document_path` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `user_type`, `created_at`, `is_verified`, `id_document_path`, `profile_picture`) VALUES
(1, 'Admin User', 'admin@example.com', '1234567890', 'admin123', 'admin', '2025-12-18 13:41:09', 0, NULL, NULL),
(2, 'Harsh Vaidya', 'harshrvaidya@gmail.com', '9930632883', '1234', 'customer', '2025-12-18 13:44:00', 0, NULL, NULL),
(5, 'Yuvraj', 'yuvrajyadav617@gmail.com', '', '1234', 'customer', '2025-12-19 08:58:02', 1, 'uploads/users_id/user_5_1767247723.jpeg', 'u_5_1767248782.jpeg'),
(6, 'ritik', 'ritikguru41@gmail.com', '', '1234', 'customer', '2025-12-19 09:03:16', 0, NULL, NULL),
(7, 'yuvi', 'gokugodui@gmail.com', '', '1234', 'customer', '2025-12-19 09:17:05', 0, NULL, NULL),
(8, 'piyush', 'piyushyadav2915@gmail.com', '', '1234', 'customer', '2025-12-19 09:50:58', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` varchar(100) NOT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'available',
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `location` varchar(150) DEFAULT 'Main Branch',
  `transmission` enum('Auto','Manual') DEFAULT 'Auto',
  `fuel_type` enum('Petrol','Diesel','Electric','Hybrid') DEFAULT 'Petrol',
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `name`, `type`, `price_per_day`, `status`, `image`, `created_at`, `location`, `transmission`, `fuel_type`, `lat`, `lng`) VALUES
(1, 'Activa', 'Scooter', 100.00, 'booked', 'Activa.jpg', '2025-12-18 13:46:40', 'Main Branch', 'Auto', 'Petrol', NULL, NULL),
(2, 'Mercedez benz', 'Car', 7000.00, 'booked', '1766071587_mbenz.jpg', '2025-12-18 15:26:27', 'Main Branch', 'Auto', 'Petrol', NULL, NULL),
(3, 'Santro', 'Car', 200.00, 'available', '1766072999_santro.png', '2025-12-18 15:49:59', 'Main Branch', 'Auto', 'Petrol', NULL, NULL),
(4, 'Tata Punch', 'Car', 2000.00, 'booked', '1766074120_Screenshot_2025-12-18_213827.png', '2025-12-18 16:08:40', 'Main Branch', 'Auto', 'Petrol', NULL, NULL),
(5, 'Renault Triber', 'Multi-Purpose Vehicle (MPV)', 700.00, 'available', 'Screenshot 2025-12-18 213957.png', '2025-12-18 16:10:01', 'Main Branch', 'Auto', 'Petrol', NULL, NULL),
(6, 'Ola S1 Pro', 'Scooter', 500.00, 'available', '1766074306_Screenshot_2025-12-18_214112.png', '2025-12-18 16:11:46', 'Main Branch', 'Auto', 'Petrol', NULL, NULL),
(7, 'Mahindra XUV700', 'SUV', 1200.00, 'booked', '1766074598_Screenshot_2025-12-18_214540.png', '2025-12-18 16:16:38', 'Main Branch', 'Auto', 'Petrol', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_features`
--

CREATE TABLE `vehicle_features` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `feature_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicle_features`
--
ALTER TABLE `vehicle_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `vehicle_features`
--
ALTER TABLE `vehicle_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicle_features`
--
ALTER TABLE `vehicle_features`
  ADD CONSTRAINT `vehicle_features_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
