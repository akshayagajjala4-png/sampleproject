-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 27, 2026 at 08:13 AM
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
-- Database: `mindmerge_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Late') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `date`, `status`, `created_at`) VALUES
(1, 1, '2026-05-26', 'Late', '2026-05-26 15:33:29'),
(2, 2, '2026-05-26', 'Present', '2026-05-26 15:33:29'),
(3, 6, '2026-05-26', 'Present', '2026-05-26 15:33:29'),
(4, 5, '2026-05-26', 'Present', '2026-05-26 15:33:29'),
(5, 3, '2026-05-26', 'Present', '2026-05-26 15:33:29'),
(6, 4, '2026-05-26', 'Present', '2026-05-26 15:33:29'),
(7, 1, '2026-05-25', 'Present', '2026-05-26 15:34:44'),
(8, 2, '2026-05-25', 'Present', '2026-05-26 15:34:44'),
(9, 6, '2026-05-25', 'Absent', '2026-05-26 15:34:44'),
(10, 5, '2026-05-25', 'Present', '2026-05-26 15:34:44'),
(11, 3, '2026-05-25', 'Present', '2026-05-26 15:34:44'),
(12, 4, '2026-05-25', 'Present', '2026-05-26 15:34:44');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `section` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `section`) VALUES
(1, '8th', 'A'),
(2, '8th', 'B'),
(3, '9th', 'A'),
(4, '9th', 'B'),
(5, '10th', 'A'),
(6, '10th', 'B');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'Admin'),
(2, 'Teacher'),
(3, 'Student'),
(4, 'Parent');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `class` varchar(50) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `parent_contact` varchar(15) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `name`, `class`, `section`, `dob`, `address`, `parent_contact`, `photo`, `created_at`) VALUES
(1, 'STU001', 'Arjun Kumar', '10th', 'A', '2008-05-15', 'Hyderabad', '9876541111', NULL, '2026-05-26 10:24:22'),
(2, 'STU002', 'Priya Sharma', '10th', 'B', '2008-07-20', 'Hyderabad', '9876542222', NULL, '2026-05-26 10:24:22'),
(3, 'STU003', 'Ravi Teja', '9th', 'A', '2009-03-10', 'Secunderabad', '9876543333', NULL, '2026-05-26 10:24:22'),
(4, 'STU004', 'Sneha Reddy', '9th', 'B', '2009-11-25', 'Warangal', '9876544444', NULL, '2026-05-26 10:24:22'),
(5, 'STU005', 'Kiran Babu', '8th', 'A', '2010-01-30', 'Nizamabad', '9876545555', NULL, '2026-05-26 10:24:22'),
(6, 'STU111', 'Vicky', '11th', 'A', '2002-03-22', 'Kadapa', '9704991278', NULL, '2026-05-26 10:44:42');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `contact` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `name`, `qualification`, `subject`, `salary`, `contact`, `created_at`) VALUES
(1, 'Rahul Sharma', 'M.Sc Mathematics', 'Mathematics', 45000.00, '9876543210', '2026-05-26 10:24:22'),
(2, 'Priya Reddy', 'M.A English', 'English', 42000.00, '9876543211', '2026-05-26 10:24:22'),
(3, 'Suresh Kumar', 'M.Sc Physics', 'Physics', 44000.00, '9876543212', '2026-05-26 10:24:22'),
(4, 'Anita Singh', 'M.Sc Chemistry', 'Chemistry', 43000.00, '9876543213', '2026-05-26 10:24:22'),
(5, 'Vijay Prasad', 'M.A History', 'History', 40000.00, '9876543214', '2026-05-26 10:24:22'),
(6, 'Raju Reddy', 'M.sc Mathematics', 'Mathematics', 500000.00, '9807678563', '2026-05-26 11:10:25');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `class_name` varchar(20) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `teacher_name` varchar(100) NOT NULL,
  `day` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `schedule_type` enum('class','teacher','exam') DEFAULT 'class',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role_id`, `created_at`) VALUES
(1, 'Admin User', 'admin@mindmerge.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '2026-05-26 06:41:08'),
(2, 'Akshaya', 'akshayagajjala.works@gmail.com', '$2y$10$XxdjJXp4N1QAiwkELA6H6eWpMTF.Y.QkSuMeZUNRoC1V9B4X3kH6i', 3, '2026-05-26 09:47:21'),
(4, 'Rahul', 'rahul@mindmerge.com', '$2y$10$pjxpc3jzoepJyJHia69aL.eRQRyu/tnbzU3mWH7rmwISFAVrgzIc2', 2, '2026-05-26 10:08:41'),
(5, 'Jenny', 'jenny@mindmerge.com', '$2y$10$YPFHTkOluNKt/39rwx8hcOq0IHXw/3eIzHcVWpPZGkc5Szu9ntiWu', 3, '2026-05-26 10:09:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
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
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
