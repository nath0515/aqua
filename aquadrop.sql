-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 11, 2025 at 10:07 AM
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
-- Database: `aquadrop`
--
CREATE DATABASE IF NOT EXISTS `aquadrop` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `aquadrop`;

-- --------------------------------------------------------

--
-- Table structure for table `expense`
--

CREATE TABLE IF NOT EXISTS `expense` (
  `expense_id` int(11) NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expensetype_id` int(11) NOT NULL,
  `comment` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  PRIMARY KEY (`expense_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense`
--

INSERT INTO `expense` (`expense_id`, `date`, `expensetype_id`, `comment`, `amount`) VALUES
(1, '2025-04-11 03:36:52', 2, 'hahahah', 1000),
(2, '2025-04-12 03:36:52', 2, 'hahahah', 1000);

-- --------------------------------------------------------

--
-- Table structure for table `expensetype`
--

CREATE TABLE IF NOT EXISTS `expensetype` (
  `expensetype_id` int(11) NOT NULL AUTO_INCREMENT,
  `expensetype_name` varchar(255) NOT NULL,
  PRIMARY KEY (`expensetype_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expensetype`
--

INSERT INTO `expensetype` (`expensetype_id`, `expensetype_name`) VALUES
(1, 'Utility'),
(2, 'Salary'),
(3, 'Maintenance'),
(4, 'Supplies');

-- --------------------------------------------------------

--
-- Table structure for table `orderitems`
--

CREATE TABLE IF NOT EXISTS `orderitems` (
  `orderitems_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `with_container` tinyint(1) NOT NULL,
  PRIMARY KEY (`orderitems_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orderitems`
--

INSERT INTO `orderitems` (`orderitems_id`, `order_id`, `product_id`, `quantity`, `with_container`) VALUES
(1, 1, 4, 2, 1),
(2, 3, 4, 2, 0),
(3, 2, 4, 3, 0);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `amount` decimal(10,2) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `rider` varchar(255) NOT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `date`, `amount`, `user_id`, `status_id`, `rider`) VALUES
(1, '2025-04-10 00:00:49', 580.00, 6, 4, 'sylwen'),
(2, '2025-04-09 23:51:15', 60.00, 6, 1, 'Sylwen'),
(3, '2025-04-11 01:07:34', 40.00, 6, 4, 'sylwen'),
(4, '2025-04-12 01:24:16', 100.00, 6, 4, 'co sam'),
(5, '2025-04-12 01:25:09', 120.00, 6, 4, 'jo');

-- --------------------------------------------------------

--
-- Table structure for table `orderstatus`
--

CREATE TABLE IF NOT EXISTS `orderstatus` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `status_name` varchar(255) NOT NULL,
  PRIMARY KEY (`status_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orderstatus`
--

INSERT INTO `orderstatus` (`status_id`, `status_name`) VALUES
(1, 'Pending'),
(2, 'Accepted'),
(3, 'Delivering'),
(4, 'Delivered'),
(5, 'Completed'),
(6, 'Cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `product_photo` varchar(255) NOT NULL,
  `water_price` decimal(10,2) NOT NULL,
  `container_price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `product_photo`, `water_price`, `container_price`, `stock`, `created_at`) VALUES
(1, 'asd1', 'uploads/1743760742_11.jpg', 20.00, 250.00, 1, '2025-04-02 23:23:45'),
(4, 'holy waters', 'uploads/1743637424_holy water.jpg', 20.00, 250.00, 9, '2025-04-02 23:43:44');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE IF NOT EXISTS `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(255) NOT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(0, 'Unverified'),
(1, 'Admin'),
(2, 'User'),
(3, 'Rider');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `verification_token` varchar(255) NOT NULL,
  `fp_token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `role_id`, `verification_token`, `fp_token`, `created_at`) VALUES
(1, 'admin', '$2y$10$dmrDRhlrOVl20jUHROWhpOD.Sp.4SBAhyXANe/SDdTNpNaTo.tyjW', 'admin@gmail.com', 0, '', '', '2025-04-01 14:45:31'),
(4, '', '$2y$10$AnvUGIFX8QG7FGxNTY.Pd.n/8l/0c1DiWQxpqyMtN3M9GmO/Zw6Na', 'nathaniel.advento151@gmail.com', 0, '82f727b4c18a684ee4032b249ce1c57b', '89e65d4fe83717bc7c9b8f30fb714f65', '2025-04-01 17:35:13'),
(6, 'exy', '$2y$10$bJ1FaEB8pEt2LweYfuv4cOT/n.kLmkb.yr6SbRyIPZXMK27uBma4.', 'xqlfernando@gmail.com', 2, '', '89e65d4fe83717bc7c9b8f30fb714f65', '2025-04-01 18:06:55'),
(7, 'nath', '$2y$10$DWDcaNLMlr.Rl4nWQDP/z..KPIx/cBXrvV1UgYag4/xPaT8PjjyUu', 'nathaniel.advento15@gmail.com', 2, '', '', '2025-04-09 15:25:18');

-- --------------------------------------------------------

--
-- Table structure for table `user_details`
--

CREATE TABLE IF NOT EXISTS `user_details` (
  `ud_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact_number` varchar(255) NOT NULL,
  PRIMARY KEY (`ud_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_details`
--

INSERT INTO `user_details` (`ud_id`, `user_id`, `firstname`, `lastname`, `address`, `contact_number`) VALUES
(4, 6, 'Exequiel', 'Fernando', 'Jan lang', '09123456789'),
(5, 7, 'nath', 'advento', 'sitio basag', '09129281119');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
