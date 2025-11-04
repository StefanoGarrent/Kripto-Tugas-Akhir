-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2025 at 08:32 PM
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
-- Database: `kriptografi`
--

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `nama` varchar(50) NOT NULL,
  `email` varchar(30) NOT NULL,
  `password` varchar(300) NOT NULL,
  `id_login` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`nama`, `email`, `password`, `id_login`) VALUES
('AdminTest', 'admin1@example.com', '202cb962ac59075b964b07152d234b70', 7),
('Al Ilham Daffa', 'stefano.garrentk@gmail.com', '202cb962ac59075b964b07152d234b70', 8);

-- --------------------------------------------------------

--
-- Table structure for table `pesan_rahasia`
--

CREATE TABLE `pesan_rahasia` (
  `id_pesan` int(11) NOT NULL,
  `id_login` int(11) NOT NULL,
  `isi_pesan_terenkripsi` text NOT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesan_rahasia`
--

INSERT INTO `pesan_rahasia` (`id_pesan`, `id_login`, `isi_pesan_terenkripsi`, `dibuat_pada`) VALUES
(6, 7, 'y4J7GrZoI40CzWlDq+RcU1YCvhfgsDJiBGb00ChK1w4Us2TKz/vu9KfZx9IYWLzAqhei55HZMg3Jrjsf76CXJQ==', '2025-11-04 19:17:18'),
(7, 8, 'wpkvHtpha/fL5csQAwav8Q==', '2025-11-04 19:23:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id_login`);

--
-- Indexes for table `pesan_rahasia`
--
ALTER TABLE `pesan_rahasia`
  ADD PRIMARY KEY (`id_pesan`),
  ADD KEY `id_login` (`id_login`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id_login` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pesan_rahasia`
--
ALTER TABLE `pesan_rahasia`
  MODIFY `id_pesan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pesan_rahasia`
--
ALTER TABLE `pesan_rahasia`
  ADD CONSTRAINT `pesan_rahasia_ibfk_1` FOREIGN KEY (`id_login`) REFERENCES `login` (`id_login`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
