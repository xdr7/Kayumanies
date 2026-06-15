-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 15, 2026 at 12:04 PM
-- Server version: 8.0.18
-- PHP Version: 7.3.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kayumanies1`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '1',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `notes`, `created_at`) VALUES
(6, 2, 2, 1, '', '2026-04-24 13:38:17');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Birthday Cake', 'birthday-cake', 'Kue ulang tahun spesial', NULL, 1, 4, '2026-04-24 06:01:22'),
(2, 'Wedding Cake', 'wedding-cake', 'Kue pernikahan elegan', NULL, 0, 2, '2026-04-24 06:01:22'),
(3, 'Cupcake', 'cupcake', 'Cupcake mini berbagai rasa', NULL, 1, 3, '2026-04-24 06:01:22'),
(4, 'Traditional Cake', 'traditional-cake', 'Kue tradisional Indonesia', NULL, 1, 1, '2026-04-24 06:01:22'),
(5, 'Pastry', 'pastry', 'Aneka pastry dan roti', NULL, 1, 5, '2026-04-24 06:01:22'),
(6, 'Dessert Box', 'dessert-box', 'Dessert box premium', NULL, 1, 6, '2026-04-24 06:01:22');

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `chats`
--

INSERT INTO `chats` (`id`, `sender_id`, `receiver_id`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 1, 'test', 1, '2026-04-27 03:33:03'),
(2, 4, 1, 'maaf', 1, '2026-04-27 03:34:10'),
(3, 4, 1, 'halo', 1, '2026-04-27 03:42:36'),
(4, 1, 4, 'baik', 1, '2026-04-27 05:58:10'),
(5, 4, 2, 'halo', 1, '2026-04-27 06:02:32'),
(6, 2, 4, 'baik ada yang bisa dibantu', 1, '2026-04-27 06:03:09'),
(7, 4, 2, 'saya ingin menanyakan pesanan saya', 1, '2026-04-27 06:03:32'),
(8, 4, 2, 'apakah pesanan saya sudah di kirimkan', 1, '2026-04-27 06:04:16'),
(9, 2, 4, 'bisa dikirimkan No invoice nya ka', 1, '2026-04-27 06:04:49'),
(10, 4, 2, '#KYM-20260425-61D45', 1, '2026-04-27 06:05:05'),
(11, 2, 4, 'berdasarkan invoice barang sudah dapat di ambil kembali kakak', 1, '2026-04-27 06:27:17');

-- --------------------------------------------------------

--
-- Table structure for table `hpp_calculations`
--

CREATE TABLE `hpp_calculations` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `material_cost` decimal(15,2) DEFAULT '0.00',
  `labor_cost` decimal(15,2) DEFAULT '0.00',
  `overhead_cost` decimal(15,2) DEFAULT '0.00',
  `packaging_cost` decimal(15,2) DEFAULT '0.00',
  `total_cost` decimal(15,2) DEFAULT '0.00',
  `quantity` int(11) DEFAULT '1',
  `hpp_per_unit` decimal(15,2) DEFAULT '0.00',
  `margin_percent` decimal(5,2) DEFAULT '30.00',
  `selling_price` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `hpp_calculations`
--

INSERT INTO `hpp_calculations` (`id`, `product_id`, `product_name`, `material_cost`, `labor_cost`, `overhead_cost`, `packaging_cost`, `total_cost`, `quantity`, `hpp_per_unit`, `margin_percent`, `selling_price`, `created_at`) VALUES
(1, 10, 'Bingka Kentang', '15000.00', '5000.00', '0.00', '800.00', '20800.00', 1, '20800.00', '100.00', '41600.00', '2026-04-27 04:17:35'),
(2, 10, 'Bingka Kentang', '15000.00', '5000.00', '0.00', '800.00', '20800.00', 1, '20800.00', '80.00', '37440.00', '2026-04-27 04:18:32'),
(3, 10, 'Bingka Kentang', '15000.00', '3000.00', '0.00', '800.00', '18800.00', 1, '18800.00', '80.00', '33840.00', '2026-04-27 04:19:33');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order','payment','system','promo') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT '0',
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `link`, `created_at`) VALUES
(1, 1, 'Pesanan Baru', 'Pesanan #KYM-20260424-D5898 berhasil dibuat', 'order', 1, 'orders.php?id=1', '2026-04-24 06:24:36'),
(2, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-24 06:41:02'),
(3, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-24 06:52:24'),
(4, 1, 'Status Pesanan Diupdate', 'Pesanan #KYM-20260424-D5898 status: PROCESSING', 'order', 1, 'order-detail.php?id=1', '2026-04-24 07:23:24'),
(5, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-24 07:27:23'),
(6, 2, 'Login Berhasil', 'Selamat datang kembali, Kasir 1!', 'system', 1, NULL, '2026-04-24 07:35:09'),
(7, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-24 07:46:10'),
(8, 1, 'Status Pesanan Diupdate', 'Pesanan #KYM-20260424-D5898 status: READY', 'order', 1, 'order-detail.php?id=1', '2026-04-24 08:26:56'),
(9, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-24 09:43:19'),
(10, 2, 'Login Berhasil', 'Selamat datang kembali, Kasir 1!', 'system', 1, NULL, '2026-04-24 12:00:27'),
(11, 2, 'Login Berhasil', 'Selamat datang kembali, Kasir 1!', 'system', 1, NULL, '2026-04-24 12:02:02'),
(12, 1, 'Pembayaran Dikonfirmasi ✅', 'Pembayaran #KYM-20260424-D5898 dikonfirmasi. Pesanan mulai diproses.', 'payment', 1, '../pembeli/orders.php?id=1', '2026-04-24 12:40:02'),
(13, 1, 'Pesanan Siap Diambil! 📦', 'Pesanan #KYM-20260424-D5898 siap diambil di toko.', 'order', 1, '../pembeli/orders.php?id=1', '2026-04-24 12:40:09'),
(14, 1, 'Pesanan Selesai ✅', 'Pesanan #KYM-20260424-D5898 telah selesai. Terima kasih telah berbelanja di Kayumanies! 🙏', 'order', 1, '../pembeli/orders.php?id=1', '2026-04-24 12:49:30'),
(15, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-24 13:46:59'),
(16, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-24 13:51:13'),
(17, 3, 'Login Berhasil', 'Selamat datang kembali, Pembeli Demo!', 'system', 1, NULL, '2026-04-24 13:51:44'),
(18, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-24 14:44:23'),
(19, 2, 'Login Berhasil', 'Selamat datang kembali, Kasir 1!', 'system', 1, NULL, '2026-04-24 14:55:30'),
(20, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-24 15:36:00'),
(21, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-25 05:28:48'),
(22, 3, 'Login Berhasil', 'Selamat datang kembali, Pembeli Demo!', 'system', 1, NULL, '2026-04-25 06:36:21'),
(23, 4, 'Pendaftaran Berhasil', 'Selamat datang di Kayumanies, Abdul Hamid Azhar! Silakan mulai berbelanja.', 'system', 1, NULL, '2026-04-25 06:59:19'),
(24, 4, 'Login Berhasil', 'Selamat datang kembali, Abdul Hamid Azhar!', 'system', 1, NULL, '2026-04-25 06:59:38'),
(25, 4, '✅ Pesanan Berhasil', 'Pesanan #KYM-20260425-3E114 berhasil dibuat. Total: Rp 361.900', 'order', 1, 'orders.php?id=2', '2026-04-25 07:18:14'),
(26, 1, '🛒 Pesanan Baru Masuk', 'Pesanan #KYM-20260425-3E114 dari Abdul Hamid Azhar. Total: Rp 361.900', 'order', 1, '../admin/orders.php', '2026-04-25 07:18:14'),
(27, 4, '✅ Pesanan Berhasil', 'Pesanan #KYM-20260425-76F85 berhasil dibuat. Total: Rp 110.000', 'order', 1, 'orders.php?id=3', '2026-04-25 07:30:40'),
(28, 1, '🛒 Pesanan Baru Masuk', 'Pesanan #KYM-20260425-76F85 dari Abdul Hamid Azhar. Total: Rp 110.000', 'order', 1, '../admin/orders.php', '2026-04-25 07:30:40'),
(29, 4, '✅ Pesanan Berhasil', 'Pesanan #KYM-20260425-35967 berhasil. Total: Rp 165.000', 'order', 1, 'orders.php?id=4', '2026-04-25 07:37:48'),
(30, 1, '🛒 Pesanan Baru', '#KYM-20260425-35967 dari Abdul Hamid Azhar', 'order', 1, '../admin/orders.php', '2026-04-25 07:37:48'),
(31, 2, 'Login Berhasil', 'Selamat datang kembali, Kasir 1!', 'system', 1, NULL, '2026-04-25 07:40:11'),
(32, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-25 08:01:26'),
(33, 4, 'Status Pesanan Diupdate', 'Pesanan #KYM-20260425-35967 status: PROCESSING', 'order', 1, 'order-detail.php?id=4', '2026-04-25 08:04:12'),
(34, 4, 'Status Pesanan Diupdate', 'Pesanan #KYM-20260425-76F85 status: PROCESSING', 'order', 1, 'order-detail.php?id=3', '2026-04-25 08:04:21'),
(35, 4, 'Status Pesanan Diupdate', 'Pesanan #KYM-20260425-35967 status: COMPLETED', 'order', 1, 'order-detail.php?id=4', '2026-04-25 08:04:23'),
(36, 4, 'Login Berhasil', 'Selamat datang kembali, Abdul Hamid Azhar!', 'system', 1, NULL, '2026-04-25 08:45:45'),
(37, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-25 08:57:14'),
(38, 3, 'Login Berhasil', 'Selamat datang kembali, Pembeli Demo!', 'system', 1, NULL, '2026-04-25 08:58:23'),
(39, 4, '✅ Pesanan Berhasil', 'Pesanan #KYM-20260425-53D20 berhasil. Total: Rp 3.298.900', 'order', 1, 'orders.php?id=5', '2026-04-25 09:17:11'),
(40, 1, '🛒 Pesanan Baru', '#KYM-20260425-53D20 dari Abdul Hamid Azhar', 'order', 1, '../admin/orders.php', '2026-04-25 09:17:11'),
(41, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-25 09:22:20'),
(42, 4, 'Status Pesanan Diupdate', 'Pesanan #KYM-20260425-53D20 status: COMPLETED', 'order', 1, 'order-detail.php?id=5', '2026-04-25 09:22:36'),
(43, 4, 'Status Pesanan Diupdate', 'Pesanan #KYM-20260425-53D20 status: COMPLETED', 'order', 1, 'order-detail.php?id=5', '2026-04-25 09:22:41'),
(44, 4, 'Status Pesanan Diupdate', 'Pesanan #KYM-20260425-53D20 status: COMPLETED', 'order', 1, 'order-detail.php?id=5', '2026-04-25 09:22:46'),
(45, 2, 'Login Berhasil', 'Selamat datang kembali, Kasir 1!', 'system', 1, NULL, '2026-04-25 09:25:24'),
(46, 4, 'Pembayaran Dikonfirmasi ✅', 'Pembayaran #KYM-20260425-3E114 sebesar Rp 361.900 dikonfirmasi. Pesanan mulai diproses.', 'payment', 1, '../pembeli/orders.php?id=2', '2026-04-25 09:25:27'),
(47, 4, 'Pembayaran Dikonfirmasi ✅', 'Pembayaran #KYM-20260425-53D20 sebesar Rp 3.298.900 dikonfirmasi. Pesanan mulai diproses.', 'payment', 1, '../pembeli/orders.php?id=5', '2026-04-25 09:25:45'),
(48, 4, 'Pembayaran Dikonfirmasi ✅', 'Pembayaran #KYM-20260425-35967 sebesar Rp 165.000 dikonfirmasi. Pesanan mulai diproses.', 'payment', 1, '../pembeli/orders.php?id=4', '2026-04-25 09:25:48'),
(49, 4, 'Pembayaran Dikonfirmasi ✅', 'Pembayaran #KYM-20260425-76F85 sebesar Rp 110.000 dikonfirmasi. Pesanan mulai diproses.', 'payment', 1, '../pembeli/orders.php?id=3', '2026-04-25 09:25:50'),
(50, 4, 'Login Berhasil', 'Selamat datang kembali, Abdul Hamid Azhar!', 'system', 1, NULL, '2026-04-25 09:26:09'),
(51, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-25 09:29:43'),
(52, 4, 'Status Pesanan Diupdate', 'Pesanan #KYM-20260425-53D20 status: COMPLETED', 'order', 1, 'order-detail.php?id=5', '2026-04-25 09:39:30'),
(53, 4, 'Status Pesanan Diupdate', 'Pesanan #KYM-20260425-35967 status: COMPLETED', 'order', 1, 'order-detail.php?id=4', '2026-04-25 09:39:33'),
(54, 4, 'Login Berhasil', 'Selamat datang kembali, Abdul Hamid Azhar!', 'system', 1, NULL, '2026-04-25 09:51:37'),
(55, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-25 09:56:02'),
(56, 4, 'Login Berhasil', 'Selamat datang kembali, Abdul Hamid Azhar!', 'system', 1, NULL, '2026-04-25 09:56:26'),
(57, 4, '✅ Pesanan Berhasil', 'Pesanan #KYM-20260425-9B121 berhasil. Total: Rp 328.900', 'order', 1, 'orders.php?id=6', '2026-04-25 10:05:34'),
(58, 1, '🛒 Pesanan Baru', '#KYM-20260425-9B121 dari Abdul Hamid Azhar', 'order', 1, '../admin/orders.php', '2026-04-25 10:05:34'),
(59, 3, '✅ Pesanan Berhasil', 'Pesanan #KYM-20260425-DAB20 berhasil. Total: Rp 115.500', 'order', 1, 'orders.php?id=7', '2026-04-25 10:05:57'),
(60, 1, '🛒 Pesanan Baru', '#KYM-20260425-DAB20 dari Pembeli Demo', 'order', 1, '../admin/orders.php', '2026-04-25 10:05:58'),
(61, 2, 'Login Berhasil', 'Selamat datang kembali, Kasir 1!', 'system', 1, NULL, '2026-04-25 10:06:52'),
(62, 4, 'Pembayaran Dikonfirmasi ✅', 'Pembayaran #KYM-20260425-9B121 sebesar Rp 328.900 dikonfirmasi. Pesanan mulai diproses.', 'payment', 1, '../pembeli/orders.php?id=6', '2026-04-25 10:07:09'),
(63, 3, 'Status Pesanan Diupdate', 'Pesanan #KYM-20260425-DAB20 status: PROCESSING', 'order', 1, 'order-detail.php?id=7', '2026-04-25 10:07:15'),
(64, 4, 'Pesanan Siap Diambil! 📦', 'Pesanan #KYM-20260425-9B121 sudah siap! Silakan ambil di toko Kayumanies.', 'order', 1, '../pembeli/orders.php?id=6', '2026-04-25 10:07:15'),
(65, 4, 'Login Berhasil', 'Selamat datang kembali, Abdul Hamid Azhar!', 'system', 1, NULL, '2026-04-25 10:07:30'),
(66, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-25 10:46:13'),
(67, 4, 'Login Berhasil', 'Selamat datang kembali, Abdul Hamid Azhar!', 'system', 1, NULL, '2026-04-25 11:14:58'),
(68, 4, '✅ Pesanan Berhasil', 'Pesanan #KYM-20260425-61D45 berhasil. Total: Rp 77.000', 'order', 1, 'orders.php?id=8', '2026-04-25 11:16:15'),
(69, 1, '🛒 Pesanan Baru', '#KYM-20260425-61D45 dari Abdul Hamid Azhar', 'order', 1, '../admin/orders.php', '2026-04-25 11:16:15'),
(70, 2, 'Login Berhasil', 'Selamat datang kembali, Kasir 1!', 'system', 1, NULL, '2026-04-25 11:16:31'),
(71, 4, 'Pembayaran Dikonfirmasi ✅', 'Pembayaran #KYM-20260425-61D45 sebesar Rp 77.000 dikonfirmasi. Pesanan mulai diproses.', 'payment', 1, '../pembeli/orders.php?id=8', '2026-04-25 11:16:38'),
(72, 4, 'Login Berhasil', 'Selamat datang kembali, Abdul Hamid Azhar!', 'system', 1, NULL, '2026-04-25 11:17:21'),
(73, 2, 'Login Berhasil', 'Selamat datang kembali, Kasir 1!', 'system', 1, NULL, '2026-04-25 11:19:06'),
(74, 4, 'Pesanan Siap Diambil! 📦', 'Pesanan #KYM-20260425-61D45 sudah siap! Silakan ambil di toko Kayumanies.', 'order', 1, '../pembeli/orders.php?id=8', '2026-04-25 12:36:28'),
(75, 4, 'Pesanan Siap Diambil! 📦', 'Pesanan #KYM-20260425-76F85 sudah siap! Silakan ambil di toko Kayumanies.', 'order', 1, '../pembeli/orders.php?id=3', '2026-04-25 12:36:45'),
(76, 4, 'Pesanan Selesai ✅', 'Pesanan #KYM-20260425-76F85 telah selesai. Terima kasih telah berbelanja di Kayumanies! 🙏', 'order', 1, '../pembeli/orders.php?id=3', '2026-04-25 12:36:51'),
(77, 4, 'Pesanan Selesai ✅', 'Pesanan #KYM-20260425-9B121 telah selesai. Terima kasih telah berbelanja di Kayumanies! 🙏', 'order', 1, '../pembeli/orders.php?id=6', '2026-04-25 12:37:07'),
(78, 4, 'Pesanan Dibatalkan ❌', 'Pesanan #KYM-20260425-3E114 telah dibatalkan. Silakan hubungi kami jika ada pertanyaan.', 'order', 1, '../pembeli/orders.php?id=2', '2026-04-25 12:38:34'),
(79, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 1, NULL, '2026-04-25 12:50:33'),
(80, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 0, NULL, '2026-04-27 03:32:06'),
(81, 4, 'Login Berhasil', 'Selamat datang kembali, Abdul Hamid Azhar!', 'system', 0, NULL, '2026-04-27 03:32:56'),
(82, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 0, NULL, '2026-04-27 03:33:31'),
(83, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 0, NULL, '2026-04-27 03:39:51'),
(84, 4, 'Login Berhasil', 'Selamat datang kembali, Abdul Hamid Azhar!', 'system', 0, NULL, '2026-04-27 03:40:44'),
(85, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 0, NULL, '2026-04-27 05:54:44'),
(86, 2, 'Login Berhasil', 'Selamat datang kembali, Kasir 1!', 'system', 0, NULL, '2026-04-27 06:02:10'),
(87, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 0, NULL, '2026-04-27 08:44:17'),
(88, 1, 'Login Berhasil', 'Selamat datang kembali, Administrator!', 'system', 0, NULL, '2026-04-29 08:59:12');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cashier_id` int(11) DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `promo_code` varchar(50) DEFAULT NULL,
  `final_amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','transfer','qris','bank','ewallet') DEFAULT 'cash',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `order_status` enum('pending','processing','ready','completed','cancelled') DEFAULT 'pending',
  `pickup_date` date DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `notes` text,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `cashier_id`, `total_amount`, `discount_amount`, `promo_code`, `final_amount`, `payment_method`, `payment_status`, `order_status`, `pickup_date`, `pickup_time`, `notes`, `customer_name`, `customer_phone`, `created_at`) VALUES
(1, 'KYM-20260424-D5898', 1, 2, '10199200.00', '0.00', '', '10199200.00', 'cash', 'paid', 'completed', '2026-04-24', '10:00:00', '-', 'asmaul', '0812452535', '2026-04-24 06:24:36'),
(2, 'KYM-20260425-3E114', 4, 2, '361900.00', '0.00', '', '361900.00', 'cash', 'paid', 'cancelled', '2026-04-26', '09:30:00', '', 'Abdul Hamid Azhar', '082626262526', '2026-04-25 07:18:14'),
(3, 'KYM-20260425-76F85', 4, 2, '110000.00', '0.00', '', '110000.00', 'cash', 'paid', 'completed', '2026-04-26', '11:30:00', 'tolong nanti saya ambil dirumah ya', 'Abdul Hamid Azhar', '082626262526', '2026-04-25 07:30:40'),
(4, 'KYM-20260425-35967', 4, 2, '165000.00', '0.00', '', '165000.00', 'cash', 'paid', 'completed', '2026-04-26', '10:30:00', 'Tolong dibungkus rapi ya', 'Abdul Hamid Azhar', '082626262526', '2026-04-25 07:37:48'),
(5, 'KYM-20260425-53D20', 4, 2, '3298900.00', '0.00', '', '3298900.00', 'cash', 'paid', 'completed', '2026-04-27', '09:30:00', 'hhh', 'Abdul Hamid Azhar', '082626262526', '2026-04-25 09:17:11'),
(6, 'KYM-20260425-9B121', 4, 2, '328900.00', '0.00', '', '328900.00', 'cash', 'paid', 'completed', '2026-04-30', '10:30:00', '', 'Abdul Hamid Azhar', '082626262526', '2026-04-25 10:05:34'),
(7, 'KYM-20260425-DAB20', 3, NULL, '115500.00', '0.00', '', '115500.00', 'cash', 'pending', 'processing', '2026-04-26', '20:00:00', '', 'Pembeli Demo', '087654321', '2026-04-25 10:05:57'),
(8, 'KYM-20260425-61D45', 4, 2, '77000.00', '0.00', '', '77000.00', 'cash', 'paid', 'ready', '2026-04-28', '15:00:00', 'saya akan mengambil di toko', 'Abdul Hamid Azhar', '082626262526', '2026-04-25 11:16:15');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`, `notes`) VALUES
(1, 1, 2, 'Vanilla Birthday Cake', '275000.00', 1, '275000.00', ''),
(2, 1, 3, 'Elegant Wedding Cake', '2999000.00', 3, '8997000.00', ''),
(3, 2, 1, 'Chocolate Birthday Cake', '299000.00', 1, '299000.00', ''),
(4, 2, 7, 'Croissant Butter', '30000.00', 1, '30000.00', ''),
(5, 3, NULL, 'bingka kentang', '25000.00', 4, '100000.00', ''),
(6, 4, NULL, 'bingka kentang', '25000.00', 6, '150000.00', ''),
(7, 5, 3, 'Elegant Wedding Cake', '2999000.00', 1, '2999000.00', ''),
(8, 6, 1, 'Chocolate Birthday Cake', '299000.00', 1, '299000.00', ''),
(9, 7, 10, 'Bingka Kentang', '35000.00', 2, '70000.00', ''),
(10, 7, 12, 'Bingka Gula Merah', '35000.00', 1, '35000.00', ''),
(11, 8, 10, 'Bingka Kentang', '35000.00', 1, '35000.00', ''),
(12, 8, 12, 'Bingka Gula Merah', '35000.00', 1, '35000.00', '');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','transfer','qris','bank','ewallet') NOT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `amount`, `payment_method`, `payment_proof`, `payment_status`, `verified_by`, `payment_date`, `verified_at`, `notes`) VALUES
(1, 1, '10199200.00', 'cash', NULL, 'verified', 2, '2026-04-24 12:40:02', '2026-04-24 12:40:02', NULL),
(2, 4, '165000.00', 'qris', 'proof-4-1777102668.png', 'pending', NULL, '2026-04-25 07:37:48', NULL, NULL),
(3, 5, '3298900.00', 'qris', 'proof-5-1777108631.png', 'pending', NULL, '2026-04-25 09:17:11', NULL, NULL),
(4, 2, '361900.00', 'cash', NULL, 'verified', 2, '2026-04-25 09:25:27', '2026-04-25 09:25:27', NULL),
(5, 5, '3298900.00', 'cash', NULL, 'verified', 2, '2026-04-25 09:25:45', '2026-04-25 09:25:45', NULL),
(6, 4, '165000.00', 'cash', NULL, 'verified', 2, '2026-04-25 09:25:48', '2026-04-25 09:25:48', NULL),
(7, 3, '110000.00', 'cash', NULL, 'verified', 2, '2026-04-25 09:25:50', '2026-04-25 09:25:50', NULL),
(8, 6, '328900.00', 'qris', 'proof-6-1777111534.png', 'pending', NULL, '2026-04-25 10:05:34', NULL, NULL),
(9, 6, '328900.00', 'cash', NULL, 'verified', 2, '2026-04-25 10:07:09', '2026-04-25 10:07:09', NULL),
(10, 8, '77000.00', 'transfer', 'proof-8-1777115775.png', 'pending', NULL, '2026-04-25 11:16:15', NULL, NULL),
(11, 8, '77000.00', 'cash', NULL, 'verified', 2, '2026-04-25 11:16:38', '2026-04-25 11:16:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('bank','qris','cash','ewallet') NOT NULL DEFAULT 'bank',
  `account_number` varchar(50) DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `qris_image` varchar(255) DEFAULT NULL,
  `instructions` text,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `type`, `account_number`, `account_name`, `bank_name`, `qris_image`, `instructions`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'BCA', 'bank', '1234567890', 'Kayumanies Cake Shop', 'BCA', NULL, 'Transfer ke rekening BCA di atas. Upload bukti transfer untuk verifikasi.', 1, 1, '2026-04-24 13:43:26'),
(2, 'Mandiri', 'bank', '0987654321', 'Kayumanies Cake Shop', 'Mandiri', NULL, 'Transfer ke rekening Mandiri di atas. Upload bukti transfer untuk verifikasi.', 1, 2, '2026-04-24 13:43:26'),
(3, 'QRIS', 'qris', '', '', '', 'qris-1777038531.png', 'Scan QRIS code di bawah untuk pembayaran.', 1, 3, '2026-04-24 13:43:26'),
(4, 'Cash / Tunai', 'cash', NULL, NULL, NULL, NULL, 'Bayar langsung di toko saat pengambilan.', 1, 4, '2026-04-24 13:43:26');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text,
  `price` decimal(15,2) NOT NULL,
  `discount_price` decimal(15,2) DEFAULT NULL,
  `stock` int(11) DEFAULT '0',
  `weight` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT 'default-cake.jpg',
  `is_featured` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `price`, `discount_price`, `stock`, `weight`, `image`, `is_featured`, `is_active`, `created_at`) VALUES
(1, 1, 'Chocolate Birthday Cake', 'chocolate-birthday-cake', '\"Chocolate Birthday Cake (Classic Series)\r\nRayakan hari istimewa dengan kelezatan kue cokelat buatan rumah kami!\r\n\r\n    - Varian Rasa: Full Chocolate / Choco Cheese / Choco Strawberry.\r\n    - Ukuran: Tersedia diameter 16cm, 20cm, dan 24cm.\r\n    - Kualitas: Bahan premium, tanpa pengawet, dan dibuat fresh sesuai pesanan (Pre-Order).\r\n    - Bonus: Gratis lilin dan pisau kue. Bisa kustom tulisan di atas kue!', '350000.00', '299000.00', 9, '1.5 kg', 'product-1777020395-69eb2deba31fd.jpg', 0, 1, '2026-04-24 06:01:23'),
(2, 1, 'Vanilla Birthday Cake', 'vanilla-birthday-cake', '\"Classic Vanilla Birthday Cake (Signature Series)\r\nHadirkan keceriaan di hari spesial dengan kelembutan vanila klasik yang tak lekang oleh waktu!\r\n\r\n   - Varian Rasa: Vanilla Buttercream / Vanilla Fruit (dengan potongan buah segar).\r\n   - Tekstur: Sponge cake yang ringan, lembap, dan wangi vanila premium.\r\n   - Kualitas: Menggunakan ekstrak vanila asli, bahan pilihan, dan dijamin freshly baked.\r\n   - Bonus: Sudah termasuk pisau kue dan lilin cantik. Bisa request tulisan!', '275000.00', NULL, 14, '1.5 kg', 'product-1777020466-69eb2e3208a87.jpg', 1, 1, '2026-04-24 06:01:23'),
(3, 2, 'Elegant Wedding Cake', 'elegant-wedding-cake', 'Kue pernikahan 3 tingkat', '3500000.00', '2999000.00', 1, '5 kg', 'product-1777020521-69eb2e692c6de.jpg', 1, 1, '2026-04-24 06:01:23'),
(4, 3, 'Red Velvet Cupcake', 'red-velvet-cupcake', 'Cupcake red velvet premium', '25000.00', NULL, 50, '100g', 'product-1777020556-69eb2e8c22474.jpeg', 1, 1, '2026-04-24 06:01:23'),
(5, 3, 'Chocolate Cupcake', 'chocolate-cupcake', 'Cupcake coklat Belgia', '20000.00', '18000.00', 40, '100g', 'product-1777020589-69eb2eadb8257.jpg', 0, 0, '2026-04-24 06:01:23'),
(6, 4, 'Lapis Legit', 'lapis-legit', 'Kue lapis legit rempah pilihan', '150000.00', NULL, 20, '500g', 'product-1777020621-69eb2ecd3257e.jpg', 1, 1, '2026-04-24 06:01:23'),
(7, 5, 'Croissant Butter', 'croissant-butter', 'Croissant butter import', '35000.00', '30000.00', 30, '100g', 'product-1777020652-69eb2eec8284d.webp', 1, 1, '2026-04-24 06:01:23'),
(8, 6, 'Dessert Box Chocolate', 'dessert-box-chocolate', 'Dessert box coklat premium', '85000.00', NULL, 25, '500g', 'product-1777020690-69eb2f1295eed.jpg', 1, 1, '2026-04-24 06:01:23'),
(10, 4, 'Bingka Kentang', 'bingka-kentang', 'Bingka Kentang Asli', '35000.00', NULL, 49, '', 'product-1777108160-69ec84c0a107a.png', 1, 1, '2026-04-25 09:09:20'),
(12, 0, '', '', '', '0.00', NULL, 0, '', 'default-cake.jpg', 0, 0, '2026-04-25 09:57:23');

-- --------------------------------------------------------

--
-- Table structure for table `promos`
--

CREATE TABLE `promos` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(15,2) NOT NULL,
  `min_purchase` decimal(15,2) DEFAULT '0.00',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `promos`
--

INSERT INTO `promos` (`id`, `code`, `name`, `description`, `discount_type`, `discount_value`, `min_purchase`, `start_date`, `end_date`, `usage_limit`, `usage_count`, `is_active`, `created_at`) VALUES
(1, 'WELCOME10', 'Welcome 10%', 'Diskon 10% pertama', 'percentage', '10.00', '100000.00', '2024-01-01 00:00:00', '2025-12-31 23:59:59', NULL, 0, 1, '2026-04-24 06:01:23'),
(2, 'FLAT50', 'Flat 50rb', 'Potongan Rp 50.000', 'fixed', '50000.00', '300000.00', '2024-01-01 00:00:00', '2025-12-31 23:59:59', NULL, 0, 0, '2026-04-24 06:01:23');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text,
  `is_approved` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `product_id`, `order_id`, `rating`, `comment`, `is_approved`, `created_at`) VALUES
(1, 4, 3, NULL, 5, 'enak banget', 1, '2026-04-25 09:51:51');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`) VALUES
(1, 'store_name', 'Kayumanies Cake Shop', '2026-04-24 06:01:23'),
(2, 'store_phone', '08123456789', '2026-04-24 06:01:23'),
(3, 'store_email', 'info@kayumanies.com', '2026-04-24 06:01:23'),
(4, 'store_address', 'Jl. Kayu Manis No. 123, Jakarta', '2026-04-24 06:01:23'),
(5, 'tax_percentage', '10', '2026-04-24 06:01:23'),
(6, 'currency', 'IDR', '2026-04-24 06:01:23'),
(7, 'opening_hours', '08:00 - 21:00', '2026-04-24 06:01:23'),
(8, 'store_logo', 'logo.jpg', '2026-04-25 12:47:13'),
(9, 'footer_description', 'Toko kue premium dengan berbagai pilihan kue lezat untuk segala kesempatan spesial Anda.', '2026-04-25 12:47:13'),
(10, 'footer_copyright', 'Made with ❤️', '2026-04-25 12:47:13'),
(11, 'social_instagram', '#', '2026-04-25 12:47:13'),
(12, 'social_facebook', '#', '2026-04-25 12:47:13'),
(13, 'social_whatsapp', '#', '2026-04-25 12:47:13'),
(14, 'store_favicon', 'favicon.ico', '2026-04-25 12:47:13'),
(16, 'store_description', '', '2026-04-25 13:11:57'),
(17, 'min_order', '50000', '2026-04-25 13:11:57'),
(18, 'hero_badge', 'Premium Quality Cake', '2026-04-25 13:44:54'),
(19, 'hero_title', 'Kue Lezat untuk <span class=\"highlight\">Momen Istimewa</span>', '2026-04-25 13:44:54'),
(20, 'hero_desc', 'Nikmati kelezatan kue premium buatan tangan dengan bahan berkualitas terbaik. Setiap gigitan adalah kebahagiaan.', '2026-04-25 13:44:54'),
(21, 'hero_image', 'hero.jpg', '2026-04-25 13:44:54'),
(22, 'hero_stat1_num', '1000+', '2026-04-25 13:44:54'),
(23, 'hero_stat1_label', 'Pelanggan Puas', '2026-04-25 13:44:54'),
(24, 'hero_stat2_num', '50+', '2026-04-25 13:44:54'),
(25, 'hero_stat2_label', 'Varian Kue', '2026-04-25 13:44:54'),
(26, 'hero_stat3_num', '⭐4.9', '2026-04-25 13:44:54'),
(27, 'hero_stat3_label', 'Rating', '2026-04-25 13:44:54'),
(28, 'theme_primary', '#8B4513', '2026-04-27 08:39:14'),
(29, 'theme_primary_dark', '#6B3410', '2026-04-27 08:39:14'),
(30, 'theme_primary_light', '#A0522D', '2026-04-27 08:39:14'),
(31, 'theme_gold', '#FFD700', '2026-04-27 08:39:14'),
(32, 'theme_accent', '#FF6B6B', '2026-04-27 08:39:14'),
(33, 'theme_bg_warm', '#FFF8F0', '2026-04-27 08:39:14'),
(34, 'theme_bg_cream', '#FFF5E6', '2026-04-27 08:39:14'),
(35, 'theme_text_dark', '#2C1810', '2026-04-27 08:39:14'),
(36, 'theme_text_gray', '#666', '2026-04-27 08:39:14'),
(37, 'theme_footer_bg', '#2C1810', '2026-04-27 08:39:14'),
(38, 'theme_radius', '16', '2026-04-27 08:39:14'),
(39, 'theme_font', 'Segoe UI', '2026-04-27 08:39:14'),
(40, 'theme_footer_text', '#ffffff', '2026-04-27 08:50:13'),
(41, 'theme_footer_link', 'rgba(255,255,255,0.7)', '2026-04-27 08:50:13'),
(42, 'theme_footer_social_bg', 'rgba(255,255,255,0.1)', '2026-04-27 08:50:13'),
(43, 'theme_footer_border', 'rgba(255,255,255,0.1)', '2026-04-27 08:50:13'),
(44, 'theme_footer_copyright', 'rgba(255,255,255,0.5)', '2026-04-27 08:50:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `role` enum('admin','kasir','pembeli') NOT NULL DEFAULT 'pembeli',
  `avatar` varchar(255) DEFAULT 'default.jpg',
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `address`, `role`, `avatar`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'admin', 'admin@kayumanies.com', '$2y$10$/nQrfXERCI02Ns30qrK0k.cCujboSiBgINeqnIFFDPa5uaY0ZG3y.', 'Administrator', NULL, NULL, 'admin', 'default.jpg', 1, '2026-04-29 16:59:12', '2026-04-24 06:01:22'),
(2, 'kasir1', 'kasir1@kayumanies.com', '$2y$10$k1PjncBndxtheAbJVBwstulZBy/yFV0xgutH749nh3.tZjHl07OIm', 'Kasir 1', '', '', 'kasir', 'default.jpg', 1, '2026-04-27 14:02:10', '2026-04-24 06:01:22'),
(3, 'pembeli1', 'pembeli1@gmail.com', '$2y$10$bfNW0xpYVX29o.xPFDK26ekftFJf4Gq.ho.O2OM2qm4ZtuUubEbUi', 'Pembeli Demo', '', '', 'pembeli', 'default.jpg', 1, '2026-04-25 16:58:23', '2026-04-24 06:01:22'),
(4, 'hamid', 'hamid@gmail.com', '$2y$10$FjO9Cxh18Q3cqS4PC8jKuuNK7CExBJxT7ABfCPP0.ALA0nFAtDmzG', 'Abdul Hamid Azhar', '082626262526', 'Jl. Perniagaan', 'pembeli', 'default.jpg', 1, '2026-04-27 11:40:44', '2026-04-25 06:59:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart` (`user_id`,`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `hpp_calculations`
--
ALTER TABLE `hpp_calculations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `promos`
--
ALTER TABLE `promos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `hpp_calculations`
--
ALTER TABLE `hpp_calculations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `promos`
--
ALTER TABLE `promos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
