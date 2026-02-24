-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 24 Feb 2026 pada 22.55
-- Versi server: 10.11.15-MariaDB-cll-lve-log
-- Versi PHP: 8.4.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `xreiins1_clinic`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nama_lengkap` varchar(150) DEFAULT NULL,
  `role` enum('admin','perawat','dokter') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `nama_lengkap`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$FdkpgPjWjw07dXa3PFnbueg0jA.9vz2bmkgJiP3eWI7SFjQ/JBTje', 'Admin Klinik', 'admin', '2025-12-03 23:55:40'),
(2, 'perawat', '$2y$10$DsTydEaBTh3m3xgTJxb8HOj6ddQHQC7PlOtkEEF5B9rV/pyqKskhW', 'Perawat Klinik', 'perawat', '2025-12-03 23:55:40'),
(3, 'dokter', '$2y$10$Vvq9Wc4RGaxG3T1eJpx4Se3OgOi1qnyOGjP7qieDX5G72mueqjTOy', 'Dokter Klinik', 'dokter', '2025-12-03 23:55:40'),
(4, 'perawat1', '$2y$10$HGcVabqpu84l6WKbCf/Wiur0dQnLV4xLaYMwHtmgZIbmfB5vXVMMW', 'zah yovi', 'perawat', '2025-12-03 23:57:32');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
