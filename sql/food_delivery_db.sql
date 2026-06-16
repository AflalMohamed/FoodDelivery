-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 16, 2026 at 02:41 PM
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
-- Database: `food_delivery_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `id` int(11) NOT NULL,
  `hotel_name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `delivery_fee` decimal(10,2) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `rider_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','accepted','assigned','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  `whatsapp_notified` tinyint(1) DEFAULT 0,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_time` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expiry`, `created_at`) VALUES
(5, 'aflalmuhammathu@gmail.com', 'c92b69fa7fb5e42a905f86c180fe86bd2c8179e0bf5c2c9d6f894a0446ecd980', '2026-04-24 09:01:07', '2026-04-24 06:01:07');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `hotel_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'Rice',
  `food_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `food_image` varchar(255) DEFAULT 'default_food.jpg',
  `is_available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `hotel_id`, `vendor_id`, `category`, `food_name`, `price`, `description`, `food_image`, `is_available`) VALUES
(3, NULL, 3, 'Biriyani', 'Beef Mandhi', 8500.00, '', 'prod_1776613778.webp', 1),
(4, NULL, 3, 'Biriyani', 'Chicken Mandhi', 6500.00, '', 'prod_1776613804.webp', 1),
(5, NULL, 4, 'Fast Food', 'Koththu', 900.00, '', 'prod_1776613888.jfif', 1),
(8, NULL, NULL, 'Fast Food', 'Burger', 200.00, '', 'prod_1776672975.webp', 1),
(10, NULL, NULL, 'Drinks', 'Pepsi', 300.00, 'Cool', 'prod_1776673840.webp', 1);

-- --------------------------------------------------------

--
-- Table structure for table `riders`
--

CREATE TABLE `riders` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `vehicle_no` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL CHECK (`id` = 1),
  `site_name` varchar(100) DEFAULT 'My Delivery Site',
  `site_logo` varchar(255) DEFAULT 'logo.png',
  `header_text` varchar(255) DEFAULT NULL,
  `footer_text` varchar(255) DEFAULT NULL,
  `admin_whatsapp` varchar(20) DEFAULT NULL,
  `admin_address` varchar(255) DEFAULT NULL,
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `email_contact` varchar(100) DEFAULT NULL,
  `bg_image` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delivery_fee_local` decimal(10,2) DEFAULT 100.00,
  `delivery_fee_outside` decimal(10,2) DEFAULT 150.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `site_name`, `site_logo`, `header_text`, `footer_text`, `admin_whatsapp`, `admin_address`, `delivery_fee`, `email_contact`, `bg_image`, `updated_at`, `delivery_fee_local`, `delivery_fee_outside`) VALUES
(1, 'FCA Food Delivery', 'logo_1776589133.png', 'Welcome to our Food Gallery', '© 2026 Delivery Service. All Rights Reserved.', '94786347437', 'Eravur', 0.00, NULL, NULL, '2026-04-19 08:58:53', 100.00, 150.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `role` enum('admin','rider','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verification_code` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `status` enum('active','deactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `address`, `password`, `google_id`, `role`, `created_at`, `verification_code`, `is_verified`, `status`) VALUES
(10, 'Mohamed Aflal', 'mohamedaflal154@gmail.com', '0786347437', NULL, '$2y$10$9Kn3W.dFQCZ81pWhxOzfwunlhEy.rSP9l9ozsN/XNFrVSmMPDxJLu', '112815844583861170347', 'admin', '2026-04-20 04:37:27', NULL, 1, 'active'),
(14, 'Mohamed Aflal', 'shadowaflal@gmail.com', '0786347437', NULL, '$2y$10$donJ096x0e.sV8gedGsKyuWvdyL76Bb/zUTS6Ot7p.R8RHgkjM1l.', '100610098463364222793', 'rider', '2026-04-20 05:26:33', NULL, 1, 'active'),
(15, 'Mohamed Aflal', 'aflalmuhammathu@gmail.com', '0786347437', NULL, '$2y$10$VW.dkOiesg.eTkqXutl3.Ofb9E2Tk0k9BXMQ58tf0MxdyDvK/lmoe', NULL, '', '2026-04-20 06:00:34', '28477dda9b58ab34b114e2cf57310b9a', 1, 'active'),
(17, 'Firsath', 'mohamedrizxtar@gmail.com', '0772039389', NULL, '$2y$10$oPXxzNLChXRCUPRN3I8cDOlJMEwRrq54EqLmLw/AXA4ckHivYSL0W', NULL, 'rider', '2026-04-20 14:01:38', '849f937747aee4a7364374ee9ce70a7b', 1, 'active'),
(18, 'Administrator', 'admin@gmail.com', '0771234567', 'Admin Address', '$2a$12$Q6gHVDy.IuOvtHCuvCgLv.vPxZJHa9WZH8KzETGXnwYJkQBjl43fi', NULL, 'admin', '2026-06-16 12:38:08', NULL, 1, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` int(11) NOT NULL,
  `shop_name` varchar(150) NOT NULL,
  `shop_type` enum('hotel','market','shop') NOT NULL,
  `shop_image` varchar(255) DEFAULT 'default_shop.jpg',
  `shop_address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `shop_name`, `shop_type`, `shop_image`, `shop_address`, `is_active`, `created_at`) VALUES
(3, 'Meet and Eat', 'hotel', 'shop_1776613493.jpg', 'Eravur ,Main Street', 1, '2026-04-19 15:44:53'),
(4, 'Bismillah Hotel', 'hotel', 'shop_1776613641.webp', 'Eravur,Main Street', 1, '2026-04-19 15:47:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `rider_id` (`rider_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `riders`
--
ALTER TABLE `riders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `riders`
--
ALTER TABLE `riders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`rider_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
