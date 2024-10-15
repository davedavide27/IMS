-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2024 at 01:04 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ajms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(30) NOT NULL,
  `name` text NOT NULL,
  `description` text DEFAULT NULL,
  `delete_flag` tinyint(1) DEFAULT 0,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_entries`
--

CREATE TABLE `inventory_entries` (
  `id` int(30) NOT NULL,
  `product_id` int(30) NOT NULL,
  `entry_code` varchar(100) DEFAULT NULL,
  `entry_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `user_id` int(30) DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_entries`
--

INSERT INTO `inventory_entries` (`id`, `product_id`, `entry_code`, `entry_date`, `description`, `user_id`, `date_created`, `date_updated`, `quantity`) VALUES
(190, 1, '1', '2024-10-15', 'asda', 1, '2024-10-15 18:15:45', '2024-10-15 18:15:45', 12345),
(191, 2, '2', '2024-10-15', 'aws', 1, '2024-10-15 18:15:52', '2024-10-15 18:15:52', 123),
(192, 1, '3', '2024-10-15', 'as', 1, '2024-10-15 18:15:58', '2024-10-15 18:15:58', 453);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `inventory_id` int(30) NOT NULL,
  `product_id` int(30) NOT NULL,
  `category_id` int(30) DEFAULT NULL,
  `quantity` float NOT NULL,
  `price` float DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(30) NOT NULL,
  `name` text NOT NULL,
  `description` text DEFAULT NULL,
  `delete_flag` tinyint(1) DEFAULT 0,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `purchase_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `delete_flag`, `date_created`, `date_updated`, `purchase_price`, `selling_price`) VALUES
(1, 'Product A', 'High-quality product A with excellent features.', 0, '2024-10-08 16:07:29', '2024-10-10 15:54:09', '100.00', '150.00'),
(2, 'Product B', 'Affordable product B suitable for everyday use.', 0, '2024-10-08 16:07:29', '2024-10-10 16:14:45', '50.00', '80.00'),
(3, 'Product C', 'Premium product C designed for performance.', 0, '2024-10-08 16:07:29', '2024-10-10 16:14:43', '200.00', '250.00'),
(4, 'Product D', 'Eco-friendly product D that helps save the planet.', 0, '2024-10-08 16:07:29', '2024-10-10 16:14:41', '30.00', '60.00'),
(5, 'Product E', 'Stylish product E with innovative design.', 0, '2024-10-08 16:07:29', '2024-10-10 16:14:39', '120.00', '180.00'),
(6, 'Product F', 'Durable product F built to last for years.', 0, '2024-10-08 16:07:29', '2024-10-10 16:14:35', '70.00', '100.00'),
(7, 'Product G', 'High-quality Product G designed for durability.', 0, '2024-10-10 16:36:28', '2024-10-10 16:36:28', '15.00', '20.00'),
(8, 'Product H', 'Affordable Product H for everyday use.', 0, '2024-10-10 16:36:28', '2024-10-10 16:36:28', '10.00', '15.00'),
(9, 'Product I', 'Premium Product I with advanced features.', 0, '2024-10-10 16:36:28', '2024-10-10 16:36:28', '30.00', '40.00'),
(10, 'Product J', 'Eco-friendly Product J made from recycled materials.', 0, '2024-10-10 16:36:28', '2024-10-10 16:42:14', '25.00', '32.00'),
(11, 'Product K', 'Compact Product K for small spaces.', 0, '2024-10-10 16:36:28', '2024-10-10 16:36:28', '12.00', '18.00'),
(12, 'Product L', 'Stylish Product L with modern design.', 0, '2024-10-10 16:36:28', '2024-10-10 16:36:28', '22.00', '28.00'),
(13, 'Product M', 'Heavy-duty Product M built to last.', 0, '2024-10-10 16:36:28', '2024-10-10 16:36:28', '50.00', '60.00'),
(14, 'Product N', 'Lightweight Product N for easy transport.', 0, '2024-10-10 16:36:28', '2024-10-10 16:36:28', '18.00', '25.00'),
(15, 'Product O', 'Waterproof Product O for outdoor use.', 0, '2024-10-10 16:36:28', '2024-10-10 16:36:28', '35.00', '45.00'),
(16, 'Product P', 'Energy-efficient Product P to reduce power consumption.', 0, '2024-10-10 16:36:28', '2024-10-10 16:36:28', '40.00', '52.00'),
(17, 'Product Q', 'Innovative Product Q with smart technology.', 0, '2024-10-10 16:36:28', '2024-10-10 16:36:28', '60.00', '75.00'),
(18, 'Product R', 'Multi-functional Product R for versatile use.', 0, '2024-10-10 16:36:28', '2024-10-10 16:36:28', '28.00', '35.00'),
(19, 'Product S', 'Ergonomic Product S for user comfort.', 0, '2024-10-10 16:36:28', '2024-10-10 16:36:28', '20.00', '27.00'),
(20, 'Product T', 'Compact and lightweight Product T.', 0, '2024-10-10 16:36:28', '2024-10-10 16:36:28', '14.00', '20.00'),
(21, 'Product U', 'High-performance Product U for professionals.', 0, '2024-10-10 16:36:28', '2024-10-10 16:36:28', '55.00', '70.00'),
(22, 'Product V', 'Eco-conscious Product V using sustainable materials.', 0, '2024-10-10 16:36:28', '2024-10-10 16:42:16', '32.00', '42.00'),
(23, 'Inventory Management System', 'Hallow', 0, '2024-10-10 17:06:20', '2024-10-10 17:28:22', '2000.00', '3000.00'),
(24, 'Inventory Management System', 'Ssytem ni', 0, '2024-10-10 17:07:18', '2024-10-10 17:28:46', '120000.00', '200000.00'),
(25, 'Inventory Management System', 'System', 0, '2024-10-10 17:08:16', '2024-10-10 17:28:45', '20000.00', '40000.00'),
(26, 'Inventory Management System', 'asd', 0, '2024-10-10 17:08:49', '2024-10-10 17:28:44', '300000.00', '400000.00'),
(27, 'asdasdsa', 'asdasd', 0, '2024-10-10 17:09:44', '2024-10-10 17:28:42', '323.00', '242.00'),
(28, 'Inventory Management System', 'asdasd', 1, '2024-10-10 17:11:14', '2024-10-15 08:13:22', '132.00', '1222.00'),
(29, 'Inventory Management System', 'asd', 0, '2024-10-10 17:12:48', '2024-10-10 17:28:35', '30000.00', '40000.00'),
(30, '3241', 'asdasdasd', 0, '2024-10-10 17:14:04', '2024-10-13 09:17:40', '32323.00', '424242.00'),
(32, '4221', 'asdasdasd', 0, '2024-10-10 17:14:55', '2024-10-10 17:28:30', '345234.00', '242321.00'),
(33, 'Product 1021', 'awawawaw', 0, '2024-10-10 17:15:13', '2024-10-13 08:49:23', '500.00', '1000.00'),
(34, 'asasdasddas', 'dfsdfsdfgsdf', 0, '2024-10-10 17:15:53', '2024-10-10 17:28:26', '242.00', '1213.00'),
(35, 'Pencil', 'Ohw Yeyh', 0, '2024-10-10 17:16:19', '2024-10-10 18:00:06', '30000.00', '40000.00'),
(36, 'Product 0', 'dsfsdfsdfsdfddfsasadfdafadfsasdfadfdfaadfadfdfasfdfsdfdfadfdfasssssssssssssssssssssssssssssss', 0, '2024-10-10 17:25:09', '2024-10-10 17:25:09', '3000.00', '4000.00');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `purchase_date` date NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sales_code` varchar(100) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stocks`
--

CREATE TABLE `stocks` (
  `id` int(30) NOT NULL,
  `product_id` int(30) NOT NULL,
  `available_stocks` int(30) NOT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stocks`
--

INSERT INTO `stocks` (`id`, `product_id`, `available_stocks`, `date_created`, `date_updated`) VALUES
(19, 1, 12798, '2024-10-15 18:15:45', '2024-10-15 18:44:20'),
(20, 2, 123, '2024-10-15 18:15:52', '2024-10-15 18:44:17');

-- --------------------------------------------------------

--
-- Table structure for table `system_info`
--

CREATE TABLE `system_info` (
  `id` int(30) NOT NULL,
  `meta_field` text NOT NULL,
  `meta_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_info`
--

INSERT INTO `system_info` (`id`, `meta_field`, `meta_value`) VALUES
(1, 'name', 'AnyCleaners Inventory System'),
(6, 'short_name', 'AnyCleaners - IMS'),
(11, 'logo', 'uploads/logo-1728989190.png'),
(13, 'user_avatar', 'uploads/user_avatar.jpg'),
(14, 'cover', 'uploads/cover-1643680511.png'),
(15, 'content', 'Array'),
(16, 'email', 'info@xyzcompany.com'),
(17, 'contact', '09854698789 / 78945632'),
(18, 'from_time', '11:00'),
(19, 'to_time', '21:30'),
(20, 'address', 'XYZ Street, There City, Here, 2306');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(50) NOT NULL,
  `firstname` varchar(250) NOT NULL,
  `middlename` text DEFAULT NULL,
  `lastname` varchar(250) NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `avatar` text DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 0,
  `status` int(1) NOT NULL DEFAULT 1 COMMENT '0=not verified, 1 = verified',
  `date_added` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `middlename`, `lastname`, `username`, `password`, `avatar`, `last_login`, `type`, `status`, `date_added`, `date_updated`) VALUES
(1, 'Dave Noel', NULL, 'Davide', 'davedavide', '482c811da5d5b4bc6d497ffa98491e38', 'uploads/avatar-1.png?v=1728990142', NULL, 1, 1, '2021-01-20 14:02:37', '2024-10-15 19:02:22'),
(2, 'Claire', NULL, 'Blake', 'cblake', '5f4dcc3b5aa765d61d8327deb882cf99', 'uploads/avatar-5.png?v=1643704129', NULL, 2, 1, '2022-02-01 16:28:49', '2024-10-08 19:57:18'),
(7, 'Ryan', NULL, 'Carrilio', 'ryry', '5f4dcc3b5aa765d61d8327deb882cf99', 'uploads/avatar-7.png?v=1728990226', NULL, 2, 1, '2024-10-13 08:24:59', '2024-10-15 19:03:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_entries`
--
ALTER TABLE `inventory_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`inventory_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `stocks`
--
ALTER TABLE `stocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `system_info`
--
ALTER TABLE `system_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_entries`
--
ALTER TABLE `inventory_entries`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `system_info`
--
ALTER TABLE `system_info`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_entries`
--
ALTER TABLE `inventory_entries`
  ADD CONSTRAINT `inventory_entries_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory_entries` (`id`),
  ADD CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `inventory_items_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `stocks`
--
ALTER TABLE `stocks`
  ADD CONSTRAINT `stocks_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
