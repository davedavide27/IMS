-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2024 at 04:42 AM
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
-- Table structure for table `inventory_entries`
--

CREATE TABLE `inventory_entries` (
  `id` int(30) NOT NULL,
  `product_id` int(30) NOT NULL,
  `entry_code` int(100) DEFAULT NULL,
  `gl_code` varchar(100) DEFAULT NULL,
  `entry_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `user_id` int(30) DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `quantity` int(11) NOT NULL DEFAULT 0,
  `status` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_entries`
--

INSERT INTO `inventory_entries` (`id`, `product_id`, `entry_code`, `gl_code`, `entry_date`, `description`, `remarks`, `user_id`, `date_created`, `date_updated`, `quantity`, `status`) VALUES
(238, 1, 1, '100 - 1002 - Cash in Bank - Unionbank Boni', '2024-11-26', 'Product test', 'PO1', 1, '2024-11-26 20:28:50', '2024-11-30 10:53:40', 100, 1),
(239, 2, 2, NULL, '2024-11-26', 'test product', 'PO1', 1, '2024-11-26 20:34:22', '2024-11-27 20:31:47', 100, 1),
(243, 1, 3, NULL, '2024-11-27', 'asd', 'asd', 11, '2024-11-27 12:36:50', '2024-11-27 13:37:03', 4444, 1),
(244, 2, 4, NULL, '2024-11-27', 'asd', 'PO1', 1, '2024-11-27 20:32:27', '2024-11-27 21:16:53', 12, 0),
(245, 1, 5, NULL, '2024-11-27', 'asdasd', 'PO1', 1, '2024-11-27 20:38:20', '2024-11-27 21:17:06', 11, 0),
(246, 1, 6, NULL, '2024-11-27', 'asd', 'PO1', 1, '2024-11-27 21:05:40', '2024-11-27 21:17:10', 12, 0),
(247, 1, 7, NULL, '2024-11-27', 'asdasd', 'PO3', 1, '2024-11-27 21:06:32', '2024-11-27 21:06:32', 12, 0),
(248, 1, 8, NULL, '2024-11-27', 'asdas', 'PO3', 7, '2024-11-27 21:07:00', '2024-11-27 21:07:00', 12, 0),
(249, 3, 9, NULL, '2024-11-27', 'oasdpad', 'PO4', 7, '2024-11-27 21:07:10', '2024-11-27 21:07:10', 12, 0),
(252, 1, 10, '100 - 1001 - Cash in Bank - PNB Rosario', '2024-11-30', 'asdas', 'asdasd', 1, '2024-11-30 10:40:53', '2024-11-30 10:53:32', 12, 0);

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
(27, 'Security Camera', 'Imagine a home security system that not only protects your property but also enhances your peace of mind wherever you are. Our Smart Home Security Camera offers exactly that with state-of-the-art features designed to give you 24/7 monitoring, crystal-clear video, and intuitive control through your smartphone or tablet.', 0, '2024-10-10 17:09:44', '2024-11-12 18:33:51', '1000.00', '5000.00'),
(28, 'Inventory Management System', 'asdasd', 0, '2024-10-10 17:11:14', '2024-10-15 19:27:58', '132.00', '1222.00'),
(29, 'Inventory Management System', 'asd', 0, '2024-10-10 17:12:48', '2024-10-10 17:28:35', '30000.00', '40000.00'),
(30, 'Disabled Product', 'asdasdasd', 1, '2024-10-10 17:14:04', '2024-11-27 19:53:01', '32323.00', '424242.00'),
(33, 'Product 1021', 'awawawaw', 0, '2024-10-10 17:15:13', '2024-10-13 08:49:23', '500.00', '1000.00'),
(34, 'product x', 'brand x', 0, '2024-10-10 17:15:53', '2024-11-03 09:38:59', '242.00', '1213.00'),
(35, 'Pencil', 'Ohw Yeyh', 0, '2024-10-10 17:16:19', '2024-11-05 16:15:57', '30000.00', '40000.00'),
(36, 'Product 0', 'dsfsdfsdfsdfddfsasadfdafadfsasdfadfdfaadfadfdfasfdfsdfdfadfdfasssssssssssssssssssssssssssssss', 0, '2024-10-10 17:25:09', '2024-10-10 17:25:09', '3000.00', '4000.00'),
(37, 'Expensive', 'Most expensive product', 0, '2024-10-15 19:29:15', '2024-10-15 19:29:47', '1000000.00', '4000000.00');

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
  `sales_code` int(100) DEFAULT NULL,
  `po_number` varchar(50) DEFAULT NULL,
  `gl_code` varchar(100) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `status` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `purchase_date`, `product_id`, `quantity`, `selling_price`, `user_id`, `date_created`, `date_updated`, `sales_code`, `po_number`, `gl_code`, `total_price`, `status`) VALUES
(78, '2024-11-26', 2, 20, '80.00', 1, '2024-11-26 20:35:06', '2024-11-28 09:09:21', 1, 'PO2', NULL, '1600.00', 1),
(80, '2024-11-27', 1, 11, '150.00', 7, '2024-11-27 13:45:30', '2024-11-27 13:45:35', 2, NULL, NULL, '1650.00', 1),
(81, '2024-11-27', 1, 12, '150.00', 2, '2024-11-27 20:30:14', '2024-11-27 20:31:55', 3, NULL, NULL, '1800.00', 1),
(82, '2024-11-27', 1, 1313, '150.00', 1, '2024-11-27 20:33:10', '2024-11-27 20:33:10', 4, NULL, NULL, '196950.00', 0),
(83, '2024-11-27', 1, 13, '150.00', 1, '2024-11-27 20:33:15', '2024-11-27 20:33:15', 5, NULL, NULL, '1950.00', 0),
(84, '2024-11-27', 1, 12, '150.00', 1, '2024-11-27 20:33:42', '2024-11-27 20:33:42', 6, NULL, NULL, '1800.00', 0),
(85, '2024-11-27', 2, 2, '80.00', 1, '2024-11-27 20:33:47', '2024-11-27 20:33:47', 7, NULL, NULL, '160.00', 0),
(86, '2024-11-27', 1, 22, '150.00', 1, '2024-11-27 20:33:55', '2024-11-27 20:33:55', 8, NULL, NULL, '3300.00', 0),
(87, '2024-11-27', 2, 22, '80.00', 1, '2024-11-27 20:33:59', '2024-11-27 20:33:59', 9, NULL, NULL, '1760.00', 0),
(88, '2024-11-27', 2, 2, '80.00', 1, '2024-11-27 20:34:06', '2024-11-27 20:35:21', 10, NULL, NULL, '160.00', 0),
(90, '2024-11-27', 1, 12, '150.00', 1, '2024-11-27 20:37:39', '2024-11-28 10:41:45', 11, NULL, NULL, '1800.00', 1),
(91, '2024-11-27', 1, 12, '150.00', 1, '2024-11-27 20:38:10', '2024-11-28 10:12:13', 12, NULL, NULL, '1800.00', 2),
(92, '2024-11-27', 1, 1212, '150.00', 1, '2024-11-27 20:57:52', '2024-11-28 10:12:10', 13, NULL, NULL, '181800.00', 1),
(94, '2024-11-28', 1, 123, '150.00', 12, '2024-11-28 09:13:58', '2024-11-28 09:13:58', 14, 'PO1', NULL, '18450.00', 0),
(95, '2024-11-30', 1, 12, '150.00', 1, '2024-11-30 10:58:59', '2024-11-30 10:58:59', 15, 'PO1', '100 - 1001 - Cash in Bank - PNB Rosario', '1800.00', 0);

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
(36, 1, 1849, '2024-11-05 17:00:14', '2024-11-30 10:58:59'),
(37, 2, 66, '2024-11-12 12:41:55', '2024-11-27 20:34:06'),
(38, 4, 12, '2024-11-26 22:05:37', '2024-11-27 21:07:30'),
(39, 3, 12, '2024-11-27 21:07:10', '2024-11-27 21:07:10');

-- --------------------------------------------------------

--
-- Table structure for table `stock_reports`
--

CREATE TABLE `stock_reports` (
  `id` int(11) NOT NULL,
  `report_datetime` datetime DEFAULT current_timestamp(),
  `product_id` int(30) NOT NULL,
  `stock_entries` int(11) DEFAULT 0,
  `available_stocks` int(30) DEFAULT 0,
  `stocks_sold` int(11) DEFAULT 0,
  `status` int(1) NOT NULL DEFAULT 1 COMMENT '0=Deleted, 1=Available',
  `entry_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=inventory, 1=sales'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_reports`
--

INSERT INTO `stock_reports` (`id`, `report_datetime`, `product_id`, `stock_entries`, `available_stocks`, `stocks_sold`, `status`, `entry_type`) VALUES
(78, '2024-11-26 20:35:06', 2, 0, 80, 20, 1, 0),
(79, '2024-11-27 11:06:30', 2, 0, 68, 12, 0, 0),
(80, '2024-11-27 13:45:30', 1, 0, 4533, 11, 1, 0),
(81, '2024-11-27 20:30:14', 1, 0, 4521, 12, 1, 0),
(82, '2024-11-27 20:33:10', 1, 0, 3208, 1313, 1, 0),
(83, '2024-11-27 20:33:15', 1, 0, 3195, 13, 1, 0),
(84, '2024-11-27 20:33:42', 1, 0, 3183, 12, 1, 0),
(85, '2024-11-27 20:33:47', 2, 0, 90, 2, 1, 0),
(86, '2024-11-27 20:33:55', 1, 0, 3161, 22, 1, 0),
(87, '2024-11-27 20:33:59', 2, 0, 68, 22, 1, 0),
(88, '2024-11-27 20:34:06', 2, 0, 66, 2, 1, 0),
(89, '2024-11-27 20:35:06', 1, 0, 3161, 0, 0, 0),
(90, '2024-11-27 20:37:39', 1, 0, 3149, 12, 1, 0),
(91, '2024-11-27 20:38:10', 1, 0, 3137, 12, 1, 0),
(92, '2024-11-27 20:57:52', 1, 0, 1936, 1212, 1, 0),
(93, '2024-11-27 22:08:57', 1, 0, 1862, 123, 0, 0),
(94, '2024-11-28 09:13:58', 1, 0, 1862, 123, 1, 0),
(95, '2024-11-30 10:58:59', 1, 0, 1849, 12, 1, 0),
(238, '2024-11-26 20:28:50', 1, 100, 100, 0, 1, 1),
(239, '2024-11-26 20:34:22', 2, 100, 100, 0, 1, 1),
(240, '2024-11-26 22:05:37', 4, 123, 123, 0, 0, 1),
(241, '2024-11-27 09:48:43', 2, 1234, 1314, 0, 0, 1),
(242, '2024-11-27 10:54:32', 2, 1213, 1293, 0, 0, 1),
(243, '2024-11-27 12:36:50', 1, 444, 544, 0, 1, 1),
(244, '2024-11-27 20:32:27', 2, 12, 92, 0, 1, 1),
(245, '2024-11-27 20:38:20', 1, 11, 3148, 0, 1, 1),
(246, '2024-11-27 21:05:40', 1, 12, 1948, 0, 1, 1),
(247, '2024-11-27 21:06:32', 1, 12, 1960, 0, 1, 1),
(248, '2024-11-27 21:07:00', 1, 12, 1972, 0, 1, 1),
(249, '2024-11-27 21:07:10', 3, 12, 12, 0, 1, 1),
(250, '2024-11-27 21:07:23', 1, 13, 1985, 0, 0, 1),
(251, '2024-11-27 21:07:30', 4, 12, 12, 0, 1, 1),
(252, '2024-11-30 10:40:53', 1, 12, 1861, 0, 1, 1);

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
(1, 'name', 'Anytime Cleaners Corporation Inventory Management System'),
(6, 'short_name', 'AIMS - PHP'),
(11, 'logo', 'uploads/logo-1732715337.png'),
(13, 'user_avatar', 'uploads/user_avatar.jpg'),
(14, 'cover', 'uploads/cover-1732933079.png'),
(15, 'content', 'Array'),
(16, 'email', 'anytimecleaners@gmail.com'),
(17, 'contact', '09854698789 / 78945632'),
(18, 'from_time', '11:00'),
(19, 'to_time', '21:30'),
(20, 'company', 'Anytime Cleaners Corporation');

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
(1, 'Dave Noel', NULL, 'Davide', 'davedavide', '81dc9bdb52d04dc20036dbd8313ed055', 'uploads/avatar-1.png?v=1728990142', NULL, 1, 1, '2021-01-20 14:02:37', '2024-11-01 15:47:46'),
(2, 'Claire', NULL, 'Blake', 'cblake', '827ccb0eea8a706c4c34a16891f84e7b', 'uploads/avatar-5.png?v=1643704129', NULL, 2, 1, '2022-02-01 16:28:49', '2024-11-26 21:45:02'),
(7, 'Dave', NULL, 'Davide', 'manager', '81dc9bdb52d04dc20036dbd8313ed055', 'uploads/avatar-7.png?v=1728990226', NULL, 3, 1, '2024-10-13 08:24:59', '2024-11-28 21:42:07'),
(11, 'asd', NULL, 'asd', 'asd123', '81dc9bdb52d04dc20036dbd8313ed055', NULL, NULL, 2, 1, '2024-11-26 21:35:18', NULL),
(12, 'Admin 2', NULL, 'Administrator', 'admin', '81dc9bdb52d04dc20036dbd8313ed055', NULL, NULL, 1, 1, '2024-11-27 12:55:24', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory_entries`
--
ALTER TABLE `inventory_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

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
-- Indexes for table `stock_reports`
--
ALTER TABLE `stock_reports`
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
-- AUTO_INCREMENT for table `inventory_entries`
--
ALTER TABLE `inventory_entries`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=253;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `stock_reports`
--
ALTER TABLE `stock_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=253;

--
-- AUTO_INCREMENT for table `system_info`
--
ALTER TABLE `system_info`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_entries`
--
ALTER TABLE `inventory_entries`
  ADD CONSTRAINT `inventory_entries_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

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

--
-- Constraints for table `stock_reports`
--
ALTER TABLE `stock_reports`
  ADD CONSTRAINT `stock_reports_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
