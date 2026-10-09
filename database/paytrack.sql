-- ============================================================
--  PAYTRACK v2 — Complete Database Schema & Seed Data
--  Single-File 1-Click Import for phpMyAdmin / MySQL
--  Database: paytrack | Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS `paytrack`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `paytrack`;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Drop existing tables
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `refund_requests`;
DROP TABLE IF EXISTS `email_logs`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `tuition_fee_items`;
DROP TABLE IF EXISTS `tuition_fees`;
DROP TABLE IF EXISTS `fee_categories`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `users`;

-- ------------------------------------------------------------
-- 1. USERS TABLE
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `username`         VARCHAR(100)     NOT NULL UNIQUE,
  `name`             VARCHAR(100)     NULL,
  `email`            VARCHAR(150)     NULL,
  `password_hash`    VARCHAR(255)     NOT NULL,
  `role`             ENUM('admin','accounting','student') NOT NULL DEFAULT 'student',
  `is_first_login`   TINYINT(1)       NOT NULL DEFAULT 1,
  `status`           ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login_at`    DATETIME         NULL,
  `last_active_at`   DATETIME         NULL,
  `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. STUDENTS TABLE
-- ------------------------------------------------------------
CREATE TABLE `students` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED     NOT NULL,
  `student_id`       VARCHAR(30)      NOT NULL UNIQUE,
  `first_name`       VARCHAR(80)      NOT NULL,
  `last_name`        VARCHAR(80)      NOT NULL,
  `middle_name`      VARCHAR(80)      NULL,
  `email`            VARCHAR(150)     NOT NULL,
  `grade_level`      VARCHAR(50)      NULL,
  `school_year`      VARCHAR(20)      NULL,
  `parent_name`      VARCHAR(160)     NULL,
  `parent_email`     VARCHAR(150)     NULL,
  `contact_number`   VARCHAR(20)      NULL,
  `address`          TEXT             NULL,
  `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_students_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. FEE CATEGORIES TABLE
-- ------------------------------------------------------------
CREATE TABLE `fee_categories` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(100)     NOT NULL,
  `code`             VARCHAR(50)      NOT NULL UNIQUE,
  `default_amount`   DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
  `is_variable`      TINYINT(1)       NOT NULL DEFAULT 0,
  `sort_order`       INT              NOT NULL DEFAULT 99,
  `is_active`        TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. TUITION FEES TABLE
-- ------------------------------------------------------------
CREATE TABLE `tuition_fees` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `student_id`       INT UNSIGNED     NOT NULL,
  `school_year`      VARCHAR(20)      NOT NULL,
  `semester`         VARCHAR(30)      NOT NULL DEFAULT '1st Semester',
  `description`      VARCHAR(200)     NOT NULL,
  `total_amount`     DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
  `amount_paid`      DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
  `due_date`         DATE             NULL,
  `status`           ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_fees_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. TUITION FEE ITEMS TABLE
-- ------------------------------------------------------------
CREATE TABLE `tuition_fee_items` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `tuition_fee_id`   INT UNSIGNED     NOT NULL,
  `fee_category_id`  INT UNSIGNED     NOT NULL,
  `category_name`    VARCHAR(100)     NOT NULL,
  `amount`           DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_items_fee`
    FOREIGN KEY (`tuition_fee_id`) REFERENCES `tuition_fees` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_category`
    FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. PAYMENTS TABLE
-- ------------------------------------------------------------
CREATE TABLE `payments` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `tuition_fee_id`   INT UNSIGNED     NOT NULL,
  `student_id`       INT UNSIGNED     NOT NULL,
  `or_number`        VARCHAR(50)      NOT NULL UNIQUE,
  `amount`           DECIMAL(12,2)    NOT NULL,
  `payment_method`   ENUM('cash','gcash','maya','bank_transfer','online','card','other') NOT NULL DEFAULT 'online',
  `notes`            TEXT             NULL,
  `enrolled_subjects_snapshot` LONGTEXT NULL,
  `paid_at`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_payments_fee`
    FOREIGN KEY (`tuition_fee_id`) REFERENCES `tuition_fees` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. REFUND REQUESTS TABLE
-- ------------------------------------------------------------
CREATE TABLE `refund_requests` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `refund_number`    VARCHAR(50)      NOT NULL UNIQUE,
  `student_id`       INT UNSIGNED     NOT NULL,
  `tuition_fee_id`   INT UNSIGNED     NOT NULL,
  `payment_id`       INT UNSIGNED     NULL,
  `amount`           DECIMAL(12,2)    NOT NULL,
  `reason`           TEXT             NOT NULL,
  `status`           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by`      INT UNSIGNED     NULL,
  `review_notes`     TEXT             NULL,
  `rejection_reason` TEXT             NULL,
  `requested_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at`      DATETIME         NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_refunds_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_refunds_fee`
    FOREIGN KEY (`tuition_fee_id`) REFERENCES `tuition_fees` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_refunds_payment`
    FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_refunds_reviewer`
    FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. EMAIL LOGS TABLE
-- ------------------------------------------------------------
CREATE TABLE `email_logs` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `recipient_email`  VARCHAR(150)     NOT NULL,
  `subject`          VARCHAR(255)     NOT NULL,
  `type`             ENUM('account_created','accounting_account_created','new_student_accounting_alert','tuition_assessed','payment_confirmation','payment_reminder','refund_approved','refund_rejected','other') NOT NULL,
  `related_id`       INT UNSIGNED     NULL,
  `status`           ENUM('sent','failed') NOT NULL DEFAULT 'sent',
  `error_message`    TEXT             NULL,
  `sent_at`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for performance
CREATE INDEX `idx_students_student_id`  ON `students`          (`student_id`);
CREATE INDEX `idx_fees_student_id`      ON `tuition_fees`      (`student_id`);
CREATE INDEX `idx_items_fee_id`         ON `tuition_fee_items` (`tuition_fee_id`);
CREATE INDEX `idx_payments_student_id`  ON `payments`          (`student_id`);
CREATE INDEX `idx_payments_or`          ON `payments`          (`or_number`);
CREATE INDEX `idx_refunds_student_id`   ON `refund_requests`   (`student_id`);
CREATE INDEX `idx_refunds_fee_id`       ON `refund_requests`   (`tuition_fee_id`);
CREATE INDEX `idx_refunds_status`       ON `refund_requests`   (`status`);


-- ============================================================
--  DEFAULT SEED DATA
-- ============================================================

-- 1. Default Admin Account (Username: admin | Password: admin123)
INSERT INTO `users` (`id`, `username`, `name`, `email`, `password_hash`, `role`, `is_first_login`, `status`, `created_at`) VALUES
(1, 'admin', 'System Administrator', 'admin@paytrack.edu.ph', '$2y$10$jJBca9GtHWFBVFnZkqsIduuOLLnX.4fX2ZcTTijQ4ljIiNHmtVauW', 'admin', 0, 'active', NOW());

-- 2. Default Accounting Staff (Username: accounting | Password: accounting123)
INSERT INTO `users` (`id`, `username`, `name`, `email`, `password_hash`, `role`, `is_first_login`, `status`, `created_at`) VALUES
(2, 'accounting', 'Accounting Office', 'accounting@paytrack.edu.ph', '$2y$10$iI0T6iO6pZ6sB0T6x9zGquxMqv3l2Y4V5S/x7l4I7K4b.dF0H9.W.', 'accounting', 0, 'active', NOW());

-- 3. Default Student Account (Username: 2023-53512 | Password: DELACRUZ)
INSERT INTO `users` (`id`, `username`, `name`, `email`, `password_hash`, `role`, `is_first_login`, `status`, `created_at`) VALUES
(3, '2023-53512', 'Juan Dela Cruz', 'juan.delacruz@email.com', '$2y$10$9kCOdWJNylQsuxeo.VBb9OxBN7TCfgsF03uXEtq3xSWgJFihb1GpW', 'student', 1, 'active', NOW());

-- 4. Default Fee Categories (Fixed total: ₱4,950 | Variable: Subject Units)
INSERT INTO `fee_categories` (`id`, `name`, `code`, `default_amount`, `is_variable`, `sort_order`, `is_active`) VALUES
(1, 'Registration & Matriculation Fee', 'reg', 650.00, 0, 1, 1),
(2, 'LMS & E-Learning Platform Fee', 'lms', 500.00, 0, 2, 1),
(3, 'Laboratory & Computer Lab Fee', 'lab', 1200.00, 0, 3, 1),
(4, 'Library & Learning Media Fee', 'lib', 450.00, 0, 4, 1),
(5, 'Medical & Dental Clinic Fee', 'med', 300.00, 0, 5, 1),
(6, 'Student Activities & Athletic Fee', 'ath', 350.00, 0, 6, 1),
(7, 'Miscellaneous & Development Fee', 'misc', 1500.00, 0, 7, 1),
(8, 'Subject Units & Academic Tuition', 'subject', 0.00, 1, 8, 1);

-- 5. Sample Student Profile (Juan Dela Cruz)
INSERT INTO `students`
  (`id`, `user_id`, `student_id`, `first_name`, `last_name`, `middle_name`,
   `email`, `grade_level`, `school_year`,
   `parent_name`, `parent_email`, `contact_number`)
VALUES
  (1, 3, '2023-53512', 'Juan', 'Dela Cruz', 'Santos',
   'juan.delacruz@email.com', 'Grade 11 - STEM', '2024-2025',
   'Maria Dela Cruz', 'maria.delacruz@email.com', '09171234567');

-- 5. Sample Tuition Assessment Record (Total: ₱21,000 | Paid: ₱5,000 | Remaining: ₱16,000)
INSERT INTO `tuition_fees`
  (`id`, `student_id`, `school_year`, `semester`, `description`, `total_amount`, `amount_paid`, `due_date`, `status`)
VALUES
  (1, 1, '2024-2025', '1st Semester', 'S.Y. 2024-2025 - 1st Semester Tuition', 21000.00, 5000.00, '2024-10-31', 'partial');

-- 6. Tuition Fee Breakdown Items (Total: ₱21,000.00)
INSERT INTO `tuition_fee_items` (`tuition_fee_id`, `fee_category_id`, `category_name`, `amount`) VALUES
(1, 1, 'Registration & Matriculation Fee', 650.00),
(1, 2, 'LMS & E-Learning Platform Fee', 500.00),
(1, 3, 'Laboratory & Computer Lab Fee', 1200.00),
(1, 4, 'Library & Learning Media Fee', 450.00),
(1, 5, 'Medical & Dental Clinic Fee', 300.00),
(1, 6, 'Student Activities & Athletic Fee', 350.00),
(1, 7, 'Miscellaneous & Development Fee', 1500.00),
(1, 8, 'Subject Units & Academic Tuition', 16050.00);

-- 7. Sample Payment Record (OR-2024-00001: ₱5,000.00)
INSERT INTO `payments`
  (`id`, `tuition_fee_id`, `student_id`, `or_number`, `amount`, `payment_method`, `notes`, `paid_at`)
VALUES
  (1, 1, 1, 'OR-2024-00001', 5000.00, 'online', 'First installment payment via portal', '2024-09-01 10:30:00');

SET FOREIGN_KEY_CHECKS = 1;
