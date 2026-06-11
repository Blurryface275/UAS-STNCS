-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 11, 2026 at 11:32 AM
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
-- Database: `sistem_presensi`
--

-- --------------------------------------------------------

--
-- Table structure for table `kehadirans`
--

CREATE TABLE `kehadirans` (
  `id` int(11) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `clock_in` datetime DEFAULT NULL,
  `clock_out` datetime DEFAULT NULL,
  `users_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kehadirans`
--

INSERT INTO `kehadirans` (`id`, `tanggal`, `clock_in`, `clock_out`, `users_id`) VALUES
(1, '2026-06-01', '2026-06-01 08:00:00', '2026-06-01 17:00:00', 1),
(2, '2026-06-02', '2026-06-02 08:05:00', '2026-06-02 17:10:00', 2),
(3, '2026-06-03', '2026-06-03 07:58:00', '2026-06-03 17:00:00', 3),
(4, '2026-06-04', '2026-06-04 08:15:00', '2026-06-04 17:20:00', 4),
(5, '2026-06-05', '2026-06-05 08:00:00', '2026-06-05 16:55:00', 5),
(6, '2026-06-06', '2026-06-06 08:10:00', '2026-06-06 17:30:00', 6),
(7, '2026-06-07', '2026-06-07 08:00:00', '2026-06-07 17:00:00', 7),
(8, '2026-06-08', '2026-06-08 08:20:00', '2026-06-08 17:15:00', 8),
(9, '2026-06-09', '2026-06-09 08:00:00', '2026-06-09 17:00:00', 9),
(10, '2026-06-10', '2026-06-10 08:30:00', '2026-06-10 17:40:00', 10);

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `aktivitas` varchar(255) DEFAULT NULL,
  `deskripsi` varchar(500) DEFAULT NULL,
  `durasi_jam` decimal(4,2) DEFAULT NULL,
  `users_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `tanggal`, `aktivitas`, `deskripsi`, `durasi_jam`, `users_id`) VALUES
(1, '2026-06-01', 'Meeting', 'Meeting proyek', 2.50, 1),
(2, '2026-06-02', 'Coding', 'Mengerjakan backend', 5.00, 2),
(3, '2026-06-03', 'Testing', 'Pengujian sistem', 3.00, 3),
(4, '2026-06-04', 'Deploy', 'Deploy aplikasi', 1.50, 4),
(5, '2026-06-05', 'Desain', 'Desain UI', 4.00, 5),
(6, '2026-06-06', 'Analisis', 'Analisis kebutuhan', 2.00, 6),
(7, '2026-06-07', 'Dokumentasi', 'Buat dokumentasi', 3.50, 7),
(8, '2026-06-08', 'Presentasi', 'Presentasi hasil', 2.25, 8),
(9, '2026-06-09', 'Debugging', 'Perbaikan bug', 5.50, 9),
(10, '2026-06-10', 'Review', 'Code review', 2.75, 10);

-- --------------------------------------------------------

--
-- Table structure for table `tipe_users`
--

CREATE TABLE `tipe_users` (
  `id` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tipe_users`
--

INSERT INTO `tipe_users` (`id`, `nama`) VALUES
(1, 'Admin'),
(2, 'Manager'),
(3, 'Karyawan'),
(4, 'Supervisor'),
(5, 'HR'),
(6, 'Finance'),
(7, 'Intern'),
(8, 'Operator'),
(9, 'Guest'),
(10, 'Direktur');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `divisi` enum('IT','HR','Finance','Marketing','Operasional') NOT NULL,
  `jabatan` enum('Staff','Supervisor','Manager','Direktur') NOT NULL,
  `status` enum('Aktif','Nonaktif') DEFAULT 'Aktif',
  `tipe_users_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `divisi`, `jabatan`, `status`, `tipe_users_id`) VALUES
(1, 'Andi', 'andi@mail.com', '123456', 'IT', 'Manager', 'Aktif', 1),
(2, 'Budi', 'budi@mail.com', '123456', 'HR', 'Supervisor', 'Aktif', 2),
(3, 'Citra', 'citra@mail.com', '123456', 'Finance', 'Staff', 'Aktif', 3),
(4, 'Doni', 'doni@mail.com', '123456', 'Marketing', 'Staff', 'Aktif', 4),
(5, 'Eka', 'eka@mail.com', '123456', 'Operasional', 'Manager', 'Aktif', 5),
(6, 'Fajar', 'fajar@mail.com', '123456', 'IT', 'Staff', 'Aktif', 6),
(7, 'Gilang', 'gilang@mail.com', '123456', 'HR', 'Supervisor', 'Aktif', 7),
(8, 'Hana', 'hana@mail.com', '123456', 'Finance', 'Manager', 'Aktif', 8),
(9, 'Indra', 'indra@mail.com', '123456', 'Marketing', 'Staff', 'Nonaktif', 9),
(10, 'Joko', 'joko@mail.com', '123456', 'Operasional', 'Direktur', 'Aktif', 10);

-- --------------------------------------------------------

--
-- Table structure for table `verifications`
--

CREATE TABLE `verifications` (
  `id` int(11) NOT NULL,
  `status` enum('Pending','Disetujui','Ditolak') DEFAULT NULL,
  `catatan` varchar(500) DEFAULT NULL,
  `tanggal_approval` datetime DEFAULT NULL,
  `tasks_idtasks` int(11) DEFAULT NULL,
  `users_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verifications`
--

INSERT INTO `verifications` (`id`, `status`, `catatan`, `tanggal_approval`, `tasks_idtasks`, `users_id`) VALUES
(1, 'Disetujui', 'Sudah sesuai', '2026-06-01 10:00:00', 1, 2),
(2, 'Pending', 'Menunggu review', NULL, 2, 3),
(3, 'Ditolak', 'Perlu revisi', '2026-06-03 15:00:00', 3, 4),
(4, 'Disetujui', 'Bagus', '2026-06-04 13:00:00', 4, 5),
(5, 'Pending', 'Belum dicek', NULL, 5, 6),
(6, 'Disetujui', 'Lanjutkan', '2026-06-06 09:30:00', 6, 7),
(7, 'Ditolak', 'Kurang lengkap', '2026-06-07 11:20:00', 7, 8),
(8, 'Disetujui', 'OK', '2026-06-08 14:00:00', 8, 9),
(9, 'Pending', 'Antrian approval', NULL, 9, 10),
(10, 'Disetujui', 'Final approved', '2026-06-10 16:00:00', 10, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kehadirans`
--
ALTER TABLE `kehadirans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `users_id` (`users_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `users_id` (`users_id`);

--
-- Indexes for table `tipe_users`
--
ALTER TABLE `tipe_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `tipe_users_id` (`tipe_users_id`);

--
-- Indexes for table `verifications`
--
ALTER TABLE `verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tasks_idtasks` (`tasks_idtasks`),
  ADD KEY `users_id` (`users_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kehadirans`
--
ALTER TABLE `kehadirans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tipe_users`
--
ALTER TABLE `tipe_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `verifications`
--
ALTER TABLE `verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kehadirans`
--
ALTER TABLE `kehadirans`
  ADD CONSTRAINT `kehadirans_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`tipe_users_id`) REFERENCES `tipe_users` (`id`);

--
-- Constraints for table `verifications`
--
ALTER TABLE `verifications`
  ADD CONSTRAINT `verifications_ibfk_1` FOREIGN KEY (`tasks_idtasks`) REFERENCES `tasks` (`id`),
  ADD CONSTRAINT `verifications_ibfk_2` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
