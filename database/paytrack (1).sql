-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 09, 2026 at 04:30 PM
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
-- Database: `paytrack`
--

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `recipient_email` varchar(150) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `type` enum('account_created','accounting_account_created','new_student_accounting_alert','tuition_assessed','payment_confirmation','payment_reminder','refund_approved','refund_rejected','other') NOT NULL,
  `related_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`id`, `recipient_email`, `subject`, `type`, `related_id`, `status`, `error_message`, `sent_at`) VALUES
(1, 'asdfadf@gmail.com', 'PayTrack Student Account Credentials', 'other', NULL, 'sent', 'Sent via simulated local mailer', '2026-09-19 20:09:24'),
(2, 'asdfadfa!@gmail.com', 'PayTrack — Student Registration Notice for Kurt', 'other', NULL, 'sent', 'Sent via simulated local mailer', '2026-09-19 20:09:24'),
(3, 'asdfadf@gmail.com', 'Payment Receipt: OR-2026-88981', 'other', NULL, 'sent', 'Sent via simulated local mailer', '2026-09-19 20:13:16'),
(4, 'asdfadfa!@gmail.com', 'Payment Confirmation: OR-2026-88981', 'other', NULL, 'sent', 'Sent via simulated local mailer', '2026-09-19 20:13:16'),
(5, 'test.student@example.com', 'PayTrack System Verification: 2026-09-19 14:20:46', 'account_created', 999, 'sent', 'Simulated: To send live emails, configure real SMTP credentials in config/config.php', '2026-09-19 20:20:46'),
(6, 'ncstenrollment@gmail.com', 'PayTrack Live SMTP Test — Success!', 'account_created', 999, 'failed', 'SMTP Error: Could not connect to SMTP host. Connection failed. stream_socket_enable_crypto(): SSL operation failed with code 1. OpenSSL Error messages:\nerror:0A000086:SSL routines::certificate verify failed SMTP server error: QUIT command failed Detail: `\Zb??????#r5?X??ۨ>?O ?xa3!????G??\0R?Ľ????a??d??Ŧ???LE(6?Q?A;?>O???`? ?j,?Z?^is?C\\?(=?+\0E??i|?fw}M??\"?6?/c|??5?JA\0#???w?\\??i؈D(Ǎs???]8BR??΅6C?p?%?', '2026-09-19 20:28:55'),
(7, 'ncstenrollment@gmail.com', 'PayTrack Live SMTP Test — Success!', 'account_created', 999, 'sent', NULL, '2026-09-19 20:29:19'),
(8, 'demesakurtdaryl@gmail.com', 'PayTrack Student Account Credentials', 'account_created', 3, 'sent', NULL, '2026-09-19 20:32:07'),
(9, 'asdfadfa!@gmail.com', 'PayTrack — Student Registration Notice for kurtzz', 'account_created', 3, 'sent', NULL, '2026-09-19 20:32:13'),
(10, 'test.student.679@example.com', 'PayTrack — Student Portal Account Created (2024-81830)', 'account_created', 5, 'sent', NULL, '2026-09-21 12:59:31'),
(11, 'test.student.679@example.com', 'PayTrack — Tuition Assessment: S.Y. 2024-2025 - 1st Semester Tuition', 'tuition_assessed', 7, 'sent', NULL, '2026-09-21 15:14:47'),
(12, 'test.parent.170@example.com', 'PayTrack — Tuition Assessment for Maria Santos', 'tuition_assessed', 7, 'sent', NULL, '2026-09-21 15:14:52'),
(13, 'test.student.679@example.com', 'PayTrack — Tuition Assessment: S.Y. 2024-2025 - 1st Semester Tuition', 'tuition_assessed', 8, 'sent', NULL, '2026-09-21 15:15:03'),
(14, 'test.parent.170@example.com', 'PayTrack — Tuition Assessment for Maria Santos', 'tuition_assessed', 8, 'sent', NULL, '2026-09-21 15:15:07'),
(15, 'test.student.679@example.com', 'PayTrack — Tuition Assessment: S.Y. 2024-2025 - 1st Semester Tuition', 'tuition_assessed', 9, 'sent', NULL, '2026-09-21 15:15:12'),
(16, 'test.parent.170@example.com', 'PayTrack — Tuition Assessment for Maria Santos', 'tuition_assessed', 9, 'sent', NULL, '2026-09-21 15:15:17'),
(17, 'test.student.679@example.com', 'PayTrack — Tuition Assessment: S.Y. 2024-2025 - 1st Semester Tuition', 'tuition_assessed', 10, 'sent', NULL, '2026-09-21 15:15:21'),
(18, 'test.parent.170@example.com', 'PayTrack — Tuition Assessment for Maria Santos', 'tuition_assessed', 10, 'sent', NULL, '2026-09-21 15:15:26'),
(19, 'test.student.679@example.com', 'PayTrack — Tuition Assessment: S.Y. 2024-2025 - 1st Semester Tuition', 'tuition_assessed', 11, 'sent', NULL, '2026-09-21 15:15:30'),
(20, 'test.parent.170@example.com', 'PayTrack — Tuition Assessment for Maria Santos', 'tuition_assessed', 11, 'sent', NULL, '2026-09-21 15:15:35'),
(21, 'test.student.679@example.com', 'PayTrack — Tuition Assessment: S.Y. 2024-2025 - 1st Semester Tuition', 'tuition_assessed', 12, 'sent', NULL, '2026-09-21 15:15:40'),
(22, 'test.parent.170@example.com', 'PayTrack — Tuition Assessment for Maria Santos', 'tuition_assessed', 12, 'sent', NULL, '2026-09-21 15:15:44'),
(23, 'test.student.679@example.com', 'PayTrack — Tuition Assessment: S.Y. 2024-2025 - 1st Semester Tuition', 'tuition_assessed', 13, 'sent', NULL, '2026-09-21 15:15:48'),
(24, 'test.parent.170@example.com', 'PayTrack — Tuition Assessment for Maria Santos', 'tuition_assessed', 13, 'sent', NULL, '2026-09-21 15:15:53'),
(25, 'test.student.679@example.com', 'PayTrack — Tuition Assessment: S.Y. 2024-2025 - 1st Semester Tuition', 'tuition_assessed', 14, 'sent', NULL, '2026-09-21 15:15:57'),
(26, 'test.parent.170@example.com', 'PayTrack — Tuition Assessment for Maria Santos', 'tuition_assessed', 14, 'sent', NULL, '2026-09-21 15:16:01'),
(27, 'kurtxz74@gmail.com', 'PayTrack — Student Portal Account Created (2023-00002)', 'account_created', 6, 'sent', NULL, '2026-09-22 12:16:23'),
(28, 'kurtxz74@gmail.com', 'PayTrack — Account Access for Cristina Aquino', 'account_created', 6, 'sent', NULL, '2026-09-22 12:16:27'),
(29, 'accounting@paytrack.edu.ph', 'PayTrack Notice: New Student Enrolled (2023-00002) — Assessment Required', 'new_student_accounting_alert', 6, 'sent', NULL, '2026-09-22 12:16:31'),
(30, 'marks000211@gmail.com', 'PayTrack — Accounting Staff Account Created', 'accounting_account_created', NULL, 'sent', NULL, '2026-09-22 23:03:40'),
(31, 'obias6569@gmail.com', 'PayTrack — Accounting Staff Account Created', 'accounting_account_created', NULL, 'sent', NULL, '2026-09-22 23:04:54'),
(32, 'demesakurtdaryl@gmail.com', 'PayTrack — Tuition Assessment: S.Y. 2024-2025 - 1st Semester Tuition', 'tuition_assessed', 15, 'sent', NULL, '2026-09-22 23:18:51'),
(33, 'asdfadfa!@gmail.com', 'PayTrack — Tuition Assessment for kurtzz De Mesa', 'tuition_assessed', 15, 'sent', NULL, '2026-09-22 23:18:57'),
(34, 'asdfadf@gmail.com', 'Payment Receipt: OR-2026-53447', 'payment_confirmation', 2, 'sent', NULL, '2026-09-23 13:39:24'),
(35, 'asdfadfa!@gmail.com', 'Payment Confirmation: OR-2026-53447', 'payment_confirmation', 2, 'sent', NULL, '2026-09-23 13:39:29'),
(36, 'asdfadf@gmail.com', 'Payment Receipt: OR-2026-01097', 'payment_confirmation', 2, 'sent', NULL, '2026-09-23 15:09:56'),
(37, 'asdfadfa!@gmail.com', 'Payment Confirmation: OR-2026-01097', 'payment_confirmation', 2, 'sent', NULL, '2026-09-23 15:09:58'),
(38, 'demesakurtdaryl@gmail.com', 'Payment Receipt: OR-2026-84806', 'payment_confirmation', 15, 'sent', NULL, '2026-10-01 00:02:07'),
(39, 'asdfadfa!@gmail.com', 'Payment Confirmation: OR-2026-84806', 'payment_confirmation', 15, 'sent', NULL, '2026-10-01 00:02:08'),
(40, 'test.student.679@example.com', 'PayTrack — Tuition Assessment: S.Y. 2024-2025 - 1st Semester Tuition', 'tuition_assessed', 14, 'sent', NULL, '2026-10-01 00:43:17'),
(41, 'test.parent.170@example.com', 'PayTrack — Tuition Assessment for Maria Santos', 'tuition_assessed', 14, 'sent', NULL, '2026-10-01 00:43:18'),
(42, 'admin@paytrack.edu.ph', 'Fee Category Approval Requested: adsfasdfa', '', NULL, 'sent', NULL, '2026-10-01 23:04:43'),
(43, 'admin@paytrack.edu.ph', 'Fee Category Approval Requested: adsfasdfa', '', NULL, 'sent', NULL, '2026-10-01 23:04:48'),
(44, 'admin@paytrack.edu.ph', 'Fee Category Approval Requested: adsfasdfa', '', NULL, 'sent', NULL, '2026-10-01 23:04:51'),
(45, 'admin@paytrack.edu.ph', 'Fee Category Approval Requested: adsfasdfa', '', NULL, 'sent', NULL, '2026-10-01 23:04:55'),
(46, 'admin@paytrack.edu.ph', 'Fee Category Approval Requested: adsfasdfa', '', NULL, 'sent', NULL, '2026-10-01 23:04:59'),
(47, 'admin@paytrack.edu.ph', 'Fee Category Approval Requested: adsfasdfa', '', NULL, 'sent', NULL, '2026-10-01 23:05:03'),
(48, 'accounting@paytrack.edu.ph', 'Disapproved: Fee Category Request \'adsfasdfa\'', '', NULL, 'sent', NULL, '2026-10-01 23:07:55'),
(49, 'admin@paytrack.edu.ph', 'Fee Category Approval Requested: asdfasdfa', '', NULL, 'sent', NULL, '2026-10-01 23:21:34'),
(50, 'accounting@paytrack.edu.ph', 'Approved: Fee Category Request \'asdfasdfa\'', '', NULL, 'sent', NULL, '2026-10-01 23:21:49'),
(51, 'admin@paytrack.edu.ph', 'Fee Category Approval Requested: Kurtz', '', NULL, 'sent', NULL, '2026-10-01 23:41:11'),
(52, 'accounting@paytrack.edu.ph', 'Approved: Fee Category Request \'Kurtz\'', '', NULL, 'sent', NULL, '2026-10-01 23:53:17'),
(53, 'asdfadf@gmail.com', 'Payment Receipt: OR-2026-01979', 'payment_confirmation', 2, 'sent', NULL, '2026-10-08 22:29:42'),
(54, 'asdfadfa!@gmail.com', 'Payment Confirmation: OR-2026-01979', 'payment_confirmation', 2, 'sent', NULL, '2026-10-08 22:29:44'),
(55, 'asdfadf@gmail.com', 'Payment Receipt: OR-2026-68398', 'payment_confirmation', 2, 'sent', NULL, '2026-10-08 22:51:06'),
(56, 'asdfadfa!@gmail.com', 'Payment Confirmation: OR-2026-68398', 'payment_confirmation', 2, 'sent', NULL, '2026-10-08 22:51:08'),
(57, 'kurtxz74@gmail.com', 'PayTrack — Refund Approved: REF-2026-50351 (₱1,500.00)', 'refund_approved', 1, 'sent', NULL, '2026-10-08 23:25:35'),
(58, 'kurtxz74@gmail.com', 'PayTrack Notice — Refund Approved for Cristina Aquino (REF-2026-50351)', 'refund_approved', 1, 'sent', NULL, '2026-10-08 23:25:38'),
(59, 'kurtxz74@gmail.com', 'PayTrack — Refund Request Update: REF-2026-82107', 'refund_rejected', 2, 'sent', NULL, '2026-10-08 23:25:40'),
(60, 'kurtxz74@gmail.com', 'PayTrack Notice — Refund Request Update for Cristina Aquino (REF-2026-82107)', 'refund_rejected', 2, 'sent', NULL, '2026-10-08 23:25:42'),
(61, 'kurtxz74@gmail.com', 'PayTrack — Refund Approved: REF-2026-71103 (₱1,500.00)', 'refund_approved', 3, 'sent', NULL, '2026-10-08 23:26:15'),
(62, 'kurtxz74@gmail.com', 'PayTrack Notice — Refund Approved for Cristina Aquino (REF-2026-71103)', 'refund_approved', 3, 'sent', NULL, '2026-10-08 23:26:17'),
(63, 'kurtxz74@gmail.com', 'PayTrack — Refund Request Update: REF-2026-18753', 'refund_rejected', 4, 'sent', NULL, '2026-10-08 23:26:20'),
(64, 'kurtxz74@gmail.com', 'PayTrack Notice — Refund Request Update for Cristina Aquino (REF-2026-18753)', 'refund_rejected', 4, 'sent', NULL, '2026-10-08 23:26:23'),
(65, 'accounting@paytrack.edu.ph', 'PayTrack Notice: Refund Request from Kurt De Mesa (₱3,000.00)', 'other', 5, 'sent', NULL, '2026-10-09 00:26:04'),
(66, 'ncstenrollment@gmail.com', 'PayTrack System Test: 2026-10-08 18:37:10', 'other', NULL, 'sent', NULL, '2026-10-09 00:37:13'),
(67, 'accounting@paytrack.edu.ph', 'PayTrack Notice: Refund Request from Kurt De Mesa (₱12,000.00)', 'other', 6, 'sent', NULL, '2026-10-09 00:52:40'),
(68, 'asdfadf@gmail.com', 'PayTrack — Refund Approved: REF-2026-87724 (₱12,000.00)', 'refund_approved', 6, 'sent', NULL, '2026-10-09 01:02:18'),
(69, 'asdfadfa!@gmail.com', 'PayTrack Notice — Refund Approved for Kurt De Mesa (REF-2026-87724)', 'refund_approved', 6, 'sent', NULL, '2026-10-09 01:02:21'),
(70, 'asdfadf@gmail.com', 'PayTrack — Refund Request Update: REF-2026-54328', 'refund_rejected', 5, 'sent', NULL, '2026-10-09 01:02:46'),
(71, 'asdfadfa!@gmail.com', 'PayTrack Notice — Refund Request Update for Kurt De Mesa (REF-2026-54328)', 'refund_rejected', 5, 'sent', NULL, '2026-10-09 01:02:48'),
(72, 'asdfadf@gmail.com', 'Payment Receipt: OR-2026-40565', 'payment_confirmation', 2, 'sent', NULL, '2026-10-09 01:03:46'),
(73, 'asdfadfa!@gmail.com', 'Payment Confirmation: OR-2026-40565', 'payment_confirmation', 2, 'sent', NULL, '2026-10-09 01:03:50'),
(74, 'accounting@paytrack.edu.ph', 'PayTrack Notice: Refund Request from Kurt De Mesa (₱5,000.00)', 'other', 7, 'sent', NULL, '2026-10-09 01:04:48'),
(75, 'asdfadf@gmail.com', 'PayTrack — Refund Approved: REF-2026-99353 (₱5,000.00)', 'refund_approved', 7, 'sent', NULL, '2026-10-09 01:05:16'),
(76, 'asdfadfa!@gmail.com', 'PayTrack Notice — Refund Approved for Kurt De Mesa (REF-2026-99353)', 'refund_approved', 7, 'sent', NULL, '2026-10-09 01:05:18');

-- --------------------------------------------------------

--
-- Table structure for table `fee_categories`
--

CREATE TABLE `fee_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `default_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_variable` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 99,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `approval_status` enum('approved','pending','rejected') NOT NULL DEFAULT 'approved',
  `requested_by` int(10) UNSIGNED DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `requested_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_categories`
--

INSERT INTO `fee_categories` (`id`, `name`, `code`, `default_amount`, `is_variable`, `sort_order`, `is_active`, `approval_status`, `requested_by`, `rejection_reason`, `requested_at`, `reviewed_at`, `reviewed_by`, `created_at`) VALUES
(1, 'Registration & Matriculation Fee', 'reg', 600.00, 0, 1, 1, 'approved', NULL, NULL, NULL, NULL, NULL, '2026-09-19 20:08:42'),
(2, 'LMS & E-Learning Platform Fee', 'lms', 500.00, 0, 2, 1, 'approved', NULL, NULL, NULL, NULL, NULL, '2026-09-19 20:08:42'),
(3, 'Laboratory & Computer Lab Fee', 'lab', 1200.00, 0, 3, 1, 'approved', NULL, NULL, NULL, NULL, NULL, '2026-09-19 20:08:42'),
(4, 'Library & Learning Media Fee', 'lib', 450.00, 0, 4, 1, 'approved', NULL, NULL, NULL, NULL, NULL, '2026-09-19 20:08:42'),
(5, 'Medical & Dental Clinic Fee', 'med', 300.00, 0, 5, 1, 'approved', NULL, NULL, NULL, NULL, NULL, '2026-09-19 20:08:42'),
(6, 'Student Activities & Athletic Fee', 'ath', 350.00, 0, 6, 1, 'approved', NULL, NULL, NULL, NULL, NULL, '2026-09-19 20:08:42'),
(7, 'Miscellaneous & Development Fee', 'misc', 1500.00, 0, 7, 1, 'approved', NULL, NULL, NULL, NULL, NULL, '2026-09-19 20:08:42'),
(8, 'Subject Units & Academic Tuition', 'subject', 0.00, 1, 8, 1, 'approved', NULL, NULL, NULL, NULL, NULL, '2026-09-19 20:08:42'),
(16, 'adsfasdfa', 'adsfasdfa_1790867099', 0.00, 1, 10, 0, 'rejected', 5, 'asdasdasd', '2026-10-01 23:04:59', '2026-10-01 23:07:52', 1, '2026-10-01 23:04:59'),
(19, 'Kurtz', 'kurtz', 220.00, 0, 10, 1, 'approved', 5, NULL, '2026-10-01 23:41:08', '2026-10-01 23:53:14', 1, '2026-10-01 23:41:08'),
(20, 'OMR / Exam Scoring & Educational Research', 'omr', 139.20, 0, 9, 1, 'approved', NULL, NULL, NULL, NULL, NULL, '2026-10-09 15:02:40'),
(21, 'NSTP / ROTC Component Fee', 'nstp', 1500.00, 0, 10, 1, 'approved', NULL, NULL, NULL, NULL, NULL, '2026-10-09 15:02:40');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `tuition_fee_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `or_number` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','gcash','maya','bank_transfer','online','card','other') NOT NULL DEFAULT 'online',
  `notes` text DEFAULT NULL,
  `paid_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `tuition_fee_id`, `student_id`, `or_number`, `amount`, `payment_method`, `notes`, `paid_at`, `created_at`) VALUES
(1, 1, 1, 'OR-2024-00001', 5000.00, 'online', 'First installment payment via portal', '2024-09-01 10:30:00', '2026-09-19 20:08:42'),
(2, 2, 2, 'OR-2026-88981', 5000.00, 'online', 'Payment via Student Portal', '2026-09-19 20:13:16', '2026-09-19 20:13:16'),
(3, 2, 2, 'OR-2026-53447', 1000.00, 'gcash', 'Payment via Student Portal', '2026-09-23 13:39:19', '2026-09-23 13:39:19'),
(4, 2, 2, 'OR-2026-01097', 5000.00, 'online', 'Payment via Student Portal', '2026-09-23 15:09:51', '2026-09-23 15:09:51'),
(6, 2, 2, 'OR-2026-01979', 1000.00, 'online', 'Payment via Student Portal', '2026-10-08 22:29:37', '2026-10-08 22:29:37'),
(7, 2, 2, 'OR-2026-68398', 3000.00, 'gcash', 'Payment via Student Portal', '2026-10-08 22:51:02', '2026-10-08 22:51:02'),
(8, 16, 6, 'OR-2026-77109', 5000.00, 'cash', 'Test payment for refund verification', '2026-10-08 23:25:30', '2026-10-08 23:25:30'),
(9, 2, 2, 'OR-2026-40565', 5000.00, 'online', 'Payment via Student Portal', '2026-10-09 01:03:41', '2026-10-09 01:03:41');

-- --------------------------------------------------------

--
-- Table structure for table `refund_requests`
--

CREATE TABLE `refund_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `refund_number` varchar(50) NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `tuition_fee_id` int(10) UNSIGNED NOT NULL,
  `payment_id` int(10) UNSIGNED DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `refund_requests`
--

INSERT INTO `refund_requests` (`id`, `refund_number`, `student_id`, `tuition_fee_id`, `payment_id`, `amount`, `reason`, `status`, `reviewed_by`, `review_notes`, `rejection_reason`, `requested_at`, `reviewed_at`) VALUES
(1, 'REF-2026-50351', 6, 16, NULL, 1500.00, 'Overpayment error due to dropped laboratory class units.', 'approved', 5, 'Approved by Accounting Head. Cashier OTC disbursement authorized.', NULL, '2026-10-08 23:25:30', '2026-10-08 23:25:30'),
(2, 'REF-2026-82107', 6, 16, NULL, 500.00, 'Requesting partial refund for uniform expense.', 'rejected', 5, NULL, 'Uniform fees are non-refundable after distribution.', '2026-10-08 23:25:38', '2026-10-08 23:25:38'),
(3, 'REF-2026-71103', 6, 16, NULL, 1500.00, 'Overpayment error due to dropped laboratory class units.', 'approved', 5, 'Approved by Accounting Head. Cashier OTC disbursement authorized.', NULL, '2026-10-08 23:26:10', '2026-10-08 23:26:10'),
(4, 'REF-2026-18753', 6, 16, NULL, 500.00, 'Requesting partial refund for uniform expense.', 'rejected', 5, NULL, 'Uniform fees are non-refundable after distribution.', '2026-10-08 23:26:17', '2026-10-08 23:26:17'),
(5, 'REF-2026-54328', 2, 2, 7, 3000.00, 'asdfasdfadsf', 'rejected', 5, NULL, 'asdasda', '2026-10-09 00:25:59', '2026-10-09 01:02:42'),
(6, 'REF-2026-87724', 2, 2, 7, 12000.00, 'adasdasdadasd', 'approved', 5, '', NULL, '2026-10-09 00:52:35', '2026-10-09 01:02:13'),
(7, 'REF-2026-99353', 2, 2, 9, 5000.00, 'asdfasdfasdfasd', 'approved', 5, '', NULL, '2026-10-09 01:04:44', '2026-10-09 01:05:11');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `student_id` varchar(30) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `parent_name` varchar(160) DEFAULT NULL,
  `parent_email` varchar(150) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `student_id`, `first_name`, `last_name`, `middle_name`, `email`, `grade_level`, `school_year`, `parent_name`, `parent_email`, `contact_number`, `address`, `created_at`, `updated_at`) VALUES
(1, 2, '2023-53512', 'Juan', 'Dela Cruz', 'Santos', 'juan.delacruz@email.com', 'Grade 11 - STEM', '2024-2025', 'Maria Dela Cruz', 'maria.delacruz@email.com', '09171234567', NULL, '2026-09-19 20:08:42', '2026-09-19 20:08:42'),
(2, 3, '2023-00000', 'Kurt', 'De Mesa', NULL, 'asdfadf@gmail.com', 'BSIT-41A2', '2024-2025', 'asfasdfa', 'asdfadfa!@gmail.com', '09090900909', NULL, '2026-09-19 20:09:24', '2026-09-19 20:09:24'),
(3, 4, '2023-00001', 'kurtzz', 'De Mesa', NULL, 'demesakurtdaryl@gmail.com', 'BSIT-41A2', '2024-2025', 'asfasdfa@gmail.com', 'asdfadfa!@gmail.com', '09090900909', NULL, '2026-09-19 20:32:01', '2026-09-19 20:32:01'),
(4, 6, '2024-87858', 'Maria', 'Santos', NULL, 'test.student.651@example.com', 'Grade 12 - ABM A1', '2024-2025', NULL, 'test.parent.182@example.com', NULL, NULL, '2026-09-21 12:59:12', '2026-09-21 13:03:46'),
(5, 7, '2024-81830', 'Maria', 'Santos', NULL, 'test.student.679@example.com', 'Grade 12 - ABM A1', '2024-2025', NULL, 'test.parent.170@example.com', NULL, NULL, '2026-09-21 12:59:24', '2026-09-21 13:02:44'),
(6, 8, '2023-00002', 'Cristina', 'Aquino', '', 'kurtxz74@gmail.com', 'BSIE 11A1', '2026-2027', 'she', 'kurtxz74@gmail.com', '09090900909', NULL, '2026-09-22 12:16:17', '2026-09-22 12:16:17');

-- --------------------------------------------------------

--
-- Table structure for table `tuition_fees`
--

CREATE TABLE `tuition_fees` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `semester` varchar(30) NOT NULL DEFAULT '1st Semester',
  `description` varchar(200) NOT NULL,
  `enrolled_subjects` text DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tuition_fees`
--

INSERT INTO `tuition_fees` (`id`, `student_id`, `school_year`, `semester`, `description`, `enrolled_subjects`, `total_amount`, `amount_paid`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '2024-2025', '1st Semester', 'S.Y. 2024-2025 - 1st Semester Tuition', NULL, 21000.00, 5000.00, '2024-10-31', 'partial', '2026-09-19 20:08:42', '2026-09-19 20:08:42'),
(2, 2, '2024-2025', '1st Semester', 'S.Y. 2024-2025 - 1st Semester Tuition', NULL, 28000.00, 3000.00, '2026-10-19', 'partial', '2026-09-19 20:09:24', '2026-10-09 01:05:11'),
(3, 3, '2024-2025', '1st Semester', 'S.Y. 2024-2025 - 1st Semester Tuition', NULL, 29000.00, 0.00, '2026-10-19', 'unpaid', '2026-09-19 20:32:01', '2026-09-19 20:32:01'),
(6, 4, '2024-2025', '1st Semester', 'S.Y. 2024-2025 - 1st Semester Tuition', NULL, 21150.00, 0.00, NULL, 'unpaid', '2026-09-21 13:03:46', '2026-09-21 13:03:46'),
(14, 5, '2024-2025', '1st Semester', 'S.Y. 2024-2025 - 1st Semester Tuition', NULL, 21050.00, 0.00, '2026-10-21', 'unpaid', '2026-09-21 15:15:53', '2026-10-01 00:43:13'),
(16, 6, '2026-2027', '1st Semester', 'S.Y. 2026-2027 - 1st Semester Tuition', NULL, 15000.00, 2000.00, NULL, 'partial', '2026-10-08 23:25:30', '2026-10-08 23:26:10');

-- --------------------------------------------------------

--
-- Table structure for table `tuition_fee_items`
--

CREATE TABLE `tuition_fee_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `tuition_fee_id` int(10) UNSIGNED NOT NULL,
  `fee_category_id` int(10) UNSIGNED NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tuition_fee_items`
--

INSERT INTO `tuition_fee_items` (`id`, `tuition_fee_id`, `fee_category_id`, `category_name`, `amount`) VALUES
(1, 1, 1, 'Registration & Matriculation Fee', 650.00),
(2, 1, 2, 'LMS & E-Learning Platform Fee', 500.00),
(3, 1, 3, 'Laboratory & Computer Lab Fee', 1200.00),
(4, 1, 4, 'Library & Learning Media Fee', 450.00),
(5, 1, 5, 'Medical & Dental Clinic Fee', 300.00),
(6, 1, 6, 'Student Activities & Athletic Fee', 350.00),
(7, 1, 7, 'Miscellaneous & Development Fee', 1500.00),
(8, 1, 8, 'Subject Units & Academic Tuition', 16050.00),
(9, 2, 1, 'Registration & Matriculation Fee', 650.00),
(10, 2, 2, 'LMS & E-Learning Platform Fee', 500.00),
(11, 2, 3, 'Laboratory & Computer Lab Fee', 1200.00),
(12, 2, 4, 'Library & Learning Media Fee', 450.00),
(13, 2, 5, 'Medical & Dental Clinic Fee', 300.00),
(14, 2, 6, 'Student Activities & Athletic Fee', 350.00),
(15, 2, 7, 'Miscellaneous & Development Fee', 1500.00),
(16, 2, 8, 'Subject Units & Academic Tuition', 23050.00),
(17, 3, 1, 'Registration & Matriculation Fee', 650.00),
(18, 3, 2, 'LMS & E-Learning Platform Fee', 500.00),
(19, 3, 3, 'Laboratory & Computer Lab Fee', 1200.00),
(20, 3, 4, 'Library & Learning Media Fee', 450.00),
(21, 3, 5, 'Medical & Dental Clinic Fee', 300.00),
(22, 3, 6, 'Student Activities & Athletic Fee', 350.00),
(23, 3, 7, 'Miscellaneous & Development Fee', 1500.00),
(24, 3, 8, 'Subject Units & Academic Tuition', 24050.00),
(33, 6, 1, 'Registration & Matriculation Fee', 650.00),
(34, 6, 2, 'LMS & E-Learning Platform Fee', 500.00),
(35, 6, 3, 'Laboratory & Computer Lab Fee', 1200.00),
(36, 6, 4, 'Library & Learning Media Fee', 450.00),
(37, 6, 6, 'Student Activities & Athletic Fee', 350.00),
(38, 6, 7, 'Miscellaneous & Development Fee', 1500.00),
(39, 6, 8, 'Subject Units & Academic Tuition', 16500.00),
(112, 14, 1, 'Registration & Matriculation Fee', 600.00),
(113, 14, 2, 'LMS & E-Learning Platform Fee', 500.00),
(114, 14, 3, 'Laboratory & Computer Lab Fee', 1200.00),
(115, 14, 4, 'Library & Learning Media Fee', 450.00),
(116, 14, 5, 'Medical & Dental Clinic Fee', 300.00),
(117, 14, 6, 'Student Activities & Athletic Fee', 350.00),
(118, 14, 7, 'Miscellaneous & Development Fee', 1500.00),
(119, 14, 8, 'Subject Units & Academic Tuition', 16150.00),
(120, 16, 1, 'Registration & Matriculation Fee', 600.00),
(121, 16, 2, 'LMS & E-Learning Platform Fee', 500.00),
(122, 16, 3, 'Laboratory & Computer Lab Fee', 1200.00),
(123, 16, 4, 'Library & Learning Media Fee', 450.00),
(124, 16, 5, 'Medical & Dental Clinic Fee', 300.00),
(125, 16, 6, 'Student Activities & Athletic Fee', 350.00),
(126, 16, 7, 'Miscellaneous & Development Fee', 1500.00),
(127, 16, 8, 'Subject Units & Academic Tuition', 9880.00),
(128, 16, 19, 'Kurtz', 220.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','accounting','student') NOT NULL DEFAULT 'student',
  `is_first_login` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `last_active_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `name`, `email`, `password_hash`, `role`, `is_first_login`, `status`, `last_login_at`, `last_active_at`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'System Administrator', 'admin@paytrack.edu.ph', '$2y$10$jJBca9GtHWFBVFnZkqsIduuOLLnX.4fX2ZcTTijQ4ljIiNHmtVauW', 'admin', 0, 'active', '2026-10-09 14:34:19', '2026-10-09 16:36:40', '2026-09-19 20:08:42', '2026-10-09 16:36:40'),
(2, '2023-53512', NULL, NULL, '$2y$10$9kCOdWJNylQsuxeo.VBb9OxBN7TCfgsF03uXEtq3xSWgJFihb1GpW', 'student', 1, 'active', '2026-10-01 00:12:22', '2026-10-01 00:41:46', '2026-09-19 20:08:42', '2026-10-01 00:41:46'),
(3, '2023-00000', NULL, NULL, '$2y$10$Dn.TfocXOHWr4N1jqxXFjOCHpoYT3nh/vNSPD/icEL3l.cILTodY.', 'student', 1, 'active', '2026-10-09 16:37:16', '2026-10-09 16:41:04', '2026-09-19 20:09:24', '2026-10-09 16:41:04'),
(4, '2023-00001', NULL, NULL, '$2y$10$XS.SoBn83zz1fdvTGF8IP.H8aBMM.72dVoXpFbSe.Jdoowj/oz4i6', 'student', 1, 'active', '2026-09-22 23:19:17', '2026-09-22 23:32:45', '2026-09-19 20:32:01', '2026-09-22 23:32:45'),
(5, 'accounting', 'Accounting Office', 'accounting@paytrack.edu.ph', '$2y$10$tKaPbI0XR9KGwUHTDh0L3eIiAuo1Iz17JEvRkzcCQ8GA3XFjl2LAi', 'accounting', 0, 'active', '2026-10-09 22:28:34', '2026-10-09 22:30:10', '2026-09-21 12:02:29', '2026-10-09 22:30:10'),
(6, '2024-87858', 'Maria Santos', 'test.student.651@example.com', '$2y$10$kHgkAUQYvhvyUz6F6tA9oOWGZYNTSlDlJDfX.Z786kudVf9r0.WrS', 'student', 1, 'active', NULL, NULL, '2026-09-21 12:59:12', '2026-09-21 12:59:12'),
(7, '2024-81830', 'Maria Santos', 'test.student.679@example.com', '$2y$10$5n4SdGhvX6tbF7d8VU8bK.3iwAYmhbwqm/ZGHDvcZwdJpAqXjO9pC', 'student', 1, 'active', NULL, NULL, '2026-09-21 12:59:24', '2026-09-21 12:59:24'),
(8, '2023-00002', 'Cristina Aquino', 'kurtxz74@gmail.com', '$2y$10$KBC6Hzh1HL3VHrg5D7Ik2eTM92SsrCsQUnZIBd9KSDJ0WOj07cyJ.', 'student', 1, 'active', '2026-09-22 12:26:45', '2026-09-22 12:39:41', '2026-09-22 12:16:17', '2026-09-22 12:39:41'),
(9, 'accounting3', 'Fred', 'marks000211@gmail.com', '$2y$10$Cyr1Cj8stXi6kJ2/Bzyp7OZEq.KsMSXAV579nEw5iELgfP0RfhEiC', 'accounting', 0, 'active', NULL, NULL, '2026-09-22 23:03:35', '2026-09-22 23:03:35'),
(10, 'accounting5', 'Fred Obias', 'obias6569@gmail.com', '$2y$10$W4jKIPfpCBdzgJ5DlFTZNuv6Z4eiAFPtBzWOZQpbSYv68juewQgi6', 'accounting', 0, 'active', NULL, NULL, '2026-09-22 23:04:49', '2026-09-22 23:04:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_categories`
--
ALTER TABLE `fee_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `or_number` (`or_number`),
  ADD KEY `fk_payments_fee` (`tuition_fee_id`),
  ADD KEY `idx_payments_student_id` (`student_id`),
  ADD KEY `idx_payments_or` (`or_number`);

--
-- Indexes for table `refund_requests`
--
ALTER TABLE `refund_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `refund_number` (`refund_number`),
  ADD KEY `fk_refunds_payment` (`payment_id`),
  ADD KEY `fk_refunds_reviewer` (`reviewed_by`),
  ADD KEY `idx_refunds_student_id` (`student_id`),
  ADD KEY `idx_refunds_fee_id` (`tuition_fee_id`),
  ADD KEY `idx_refunds_status` (`status`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `fk_students_user` (`user_id`),
  ADD KEY `idx_students_student_id` (`student_id`);

--
-- Indexes for table `tuition_fees`
--
ALTER TABLE `tuition_fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fees_student_id` (`student_id`);

--
-- Indexes for table `tuition_fee_items`
--
ALTER TABLE `tuition_fee_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_items_category` (`fee_category_id`),
  ADD KEY `idx_items_fee_id` (`tuition_fee_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `fee_categories`
--
ALTER TABLE `fee_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `refund_requests`
--
ALTER TABLE `refund_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tuition_fees`
--
ALTER TABLE `tuition_fees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tuition_fee_items`
--
ALTER TABLE `tuition_fee_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_fee` FOREIGN KEY (`tuition_fee_id`) REFERENCES `tuition_fees` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `refund_requests`
--
ALTER TABLE `refund_requests`
  ADD CONSTRAINT `fk_refunds_fee` FOREIGN KEY (`tuition_fee_id`) REFERENCES `tuition_fees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_refunds_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_refunds_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_refunds_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tuition_fees`
--
ALTER TABLE `tuition_fees`
  ADD CONSTRAINT `fk_fees_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tuition_fee_items`
--
ALTER TABLE `tuition_fee_items`
  ADD CONSTRAINT `fk_items_category` FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_fee` FOREIGN KEY (`tuition_fee_id`) REFERENCES `tuition_fees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
