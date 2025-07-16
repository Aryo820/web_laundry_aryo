-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 26 Jun 2025 pada 03.47
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laundry_1`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `customer`
--

CREATE TABLE `customer` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(50) NOT NULL,
  `phone` varchar(13) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `customer`
--

INSERT INTO `customer` (`id`, `customer_name`, `phone`, `address`, `created_at`, `update_at`, `deleted_at`) VALUES
(1, 'Park Ji-hyo', '01032831223', 'Busan No.10', '2025-06-24 14:31:58', NULL, '0000-00-00 00:00:00'),
(2, 'Dahyun', '01288756326', 'Seoul No.17', '2025-06-26 01:36:23', NULL, '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `level`
--

CREATE TABLE `level` (
  `id` int(11) NOT NULL,
  `level_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `level`
--

INSERT INTO `level` (`id`, `level_name`, `created_at`, `update_at`, `deleted_at`) VALUES
(1, 'Administrator', '2025-06-22 14:27:20', '2025-06-22 14:28:58', '0000-00-00 00:00:00'),
(2, 'Operator', '2025-06-24 14:24:30', NULL, '2025-06-24 16:24:05'),
(3, 'Leader', '2025-06-24 14:24:59', NULL, '2025-06-24 16:24:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `setting`
--

CREATE TABLE `setting` (
  `id` int(11) NOT NULL,
  `address` varchar(100) NOT NULL,
  `email` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `setting`
--

INSERT INTO `setting` (`id`, `address`, `email`, `name`, `phone`, `create_at`, `update_at`) VALUES
(2, 'Manchester United', 'ferguson@gmail.com', 'Sir Alex Ferguson', '0823713423', '2025-06-26 01:45:39', '2025-06-26 01:46:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `trans_laundry_pickup`
--

CREATE TABLE `trans_laundry_pickup` (
  `id` int(11) NOT NULL,
  `id_order` int(11) NOT NULL,
  `id_customer` int(11) NOT NULL,
  `pickup_date` datetime NOT NULL,
  `notes` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `trans_laundry_pickup`
--

INSERT INTO `trans_laundry_pickup` (`id`, `id_order`, `id_customer`, `pickup_date`, `notes`, `created_at`, `update_at`) VALUES
(1, 1, 1, '2025-06-26 03:40:24', '', '2025-06-26 01:40:24', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `trans_order`
--

CREATE TABLE `trans_order` (
  `id` int(11) NOT NULL,
  `id_customer` int(11) NOT NULL,
  `order_code` varchar(50) NOT NULL,
  `order_date` date NOT NULL,
  `order_end_date` date NOT NULL,
  `order_status` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime NOT NULL,
  `order_pay` int(15) NOT NULL,
  `order_change` int(15) NOT NULL,
  `total` int(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `trans_order`
--

INSERT INTO `trans_order` (`id`, `id_customer`, `order_code`, `order_date`, `order_end_date`, `order_status`, `created_at`, `update_at`, `deleted_at`, `order_pay`, `order_change`, `total`) VALUES
(1, 1, 'TR-250625-001', '2025-06-25', '2025-06-27', 1, '2025-06-25 08:29:19', '2025-06-26 01:40:24', '0000-00-00 00:00:00', 20000, 0, 20000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `trans_order_detail`
--

CREATE TABLE `trans_order_detail` (
  `id` int(11) NOT NULL,
  `id_order` int(11) NOT NULL,
  `id_service` int(11) NOT NULL,
  `qty` int(10) NOT NULL,
  `subtotal` double(10,2) NOT NULL,
  `notes` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `trans_order_detail`
--

INSERT INTO `trans_order_detail` (`id`, `id_order`, `id_service`, `qty`, `subtotal`, `notes`, `created_at`, `update_at`) VALUES
(1, 1, 1, 1000, 20000.00, '', '2025-06-25 08:29:19', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `type_of_service`
--

CREATE TABLE `type_of_service` (
  `id` int(11) NOT NULL,
  `service_name` varchar(50) NOT NULL,
  `price` int(5) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `type_of_service`
--

INSERT INTO `type_of_service` (`id`, `service_name`, `price`, `description`, `created_at`, `update_at`, `deleted_at`) VALUES
(1, 'Cuci Dan Setrika (Reguler)', 20000, 'Reguler 3 Hari Selesai', '2025-06-24 14:35:04', '2025-06-25 08:10:42', '0000-00-00 00:00:00'),
(2, 'Cuci Dan Setrika (Express)', 25000, 'Express 1 Hari Selesai', '2025-06-25 07:15:42', '2025-06-25 08:10:52', '0000-00-00 00:00:00'),
(3, 'Hanya Cuci (Reguler)', 10000, 'Reguler 3 Hari Selesai', '2025-06-25 07:48:52', '2025-06-25 08:11:03', '0000-00-00 00:00:00'),
(4, 'Hanya Cuci (Express)', 15000, 'Express 1 Hari Selesai', '2025-06-25 07:50:17', '2025-06-25 08:11:18', '0000-00-00 00:00:00'),
(5, 'Hanya Setrika (Reguler)', 10000, 'Reguler 3 Hari Selesai', '2025-06-25 08:05:54', '2025-06-25 08:11:31', '0000-00-00 00:00:00'),
(6, 'Hanya Setrika (Express)', 15000, 'Express 1 Hari Selesai', '2025-06-25 08:06:12', '2025-06-25 08:11:47', '0000-00-00 00:00:00'),
(7, 'Selimut, Karpet, Mantel, Sprei (Only Express)', 35000, 'Express 1 Hari Selesai', '2025-06-25 08:08:59', '2025-06-25 08:12:20', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `id_level` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id`, `id_level`, `name`, `email`, `password`, `created_at`, `update_at`) VALUES
(1, 1, 'Bruno', 'bruno@gmail.com', '8cb2237d0679ca88db6464eac60da96345513964', '2025-06-22 01:47:18', '2025-06-24 14:26:27'),
(2, 2, 'Onana', 'onana48@gmail.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', '2025-06-24 14:27:08', NULL),
(3, 3, 'Sir Alex Ferguson', 'ferguson@gmail.com', '20eabe5d64b0e216796e834f52d61fd0b70332fc', '2025-06-24 14:27:46', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `level`
--
ALTER TABLE `level`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `trans_laundry_pickup`
--
ALTER TABLE `trans_laundry_pickup`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `trans_order`
--
ALTER TABLE `trans_order`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `trans_order_detail`
--
ALTER TABLE `trans_order_detail`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `type_of_service`
--
ALTER TABLE `type_of_service`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `customer`
--
ALTER TABLE `customer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `level`
--
ALTER TABLE `level`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `setting`
--
ALTER TABLE `setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `trans_laundry_pickup`
--
ALTER TABLE `trans_laundry_pickup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `trans_order`
--
ALTER TABLE `trans_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `trans_order_detail`
--
ALTER TABLE `trans_order_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `type_of_service`
--
ALTER TABLE `type_of_service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
