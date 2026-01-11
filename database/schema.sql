-- Career Path Institute LMS Database Schema
-- Created: 2026-01-11
-- Complete Learning Management System Database
-- PRODUCTION-READY VERSION WITH SECURITY ENHANCEMENTS

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: career_path_lms
CREATE DATABASE IF NOT EXISTS `career_path_lms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `career_path_lms`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` enum('admin','instructor','student') NOT NULL DEFAULT 'student',
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_active` (`is_active`),
  KEY `idx_email_verified` (`email_verified`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_password_reset` (`password_reset_token`, `password_reset_expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `email_otps`
-- --------------------------------------------------------

CREATE TABLE `email_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `purpose` enum('registration','password_reset','email_change') NOT NULL DEFAULT 'registration',
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_otp` (`email`, `otp_code`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_purpose` (`purpose`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `categories`
-- --------------------------------------------------------

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_active` (`is_active`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_sort_order` (`sort_order`),
  FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `courses`
-- --------------------------------------------------------

CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `video_preview` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `level` enum('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  `language` varchar(50) NOT NULL DEFAULT 'English',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_price` decimal(10,2) DEFAULT NULL,
  `duration_hours` int(11) DEFAULT NULL,
  `total_lessons` int(11) NOT NULL DEFAULT 0,
  `total_quizzes` int(11) NOT NULL DEFAULT 0,
  `total_students` int(11) NOT NULL DEFAULT 0,
  `average_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `total_reviews` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `requirements` text DEFAULT NULL,
  `what_you_learn` text DEFAULT NULL,
  `target_audience` text DEFAULT NULL,
  `certificate_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_category` (`category_id`),
  KEY `idx_instructor` (`instructor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_published` (`is_published`),
  KEY `idx_price` (`price`),
  KEY `idx_level` (`level`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `instructor_id` int(11) NOT NULL,
  `level` enum('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  `language` varchar(50) NOT NULL DEFAULT 'English',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_price` decimal(10,2) DEFAULT NULL,
  `duration_hours` int(11) DEFAULT NULL,
  `total_lessons` int(11) DEFAULT 0,
  `requirements` text DEFAULT NULL,
  `what_you_learn` text DEFAULT NULL,
  `target_audience` text DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `enrollment_limit` int(11) DEFAULT NULL,
  `certificate_available` tinyint(1) NOT NULL DEFAULT 1,
  `rating_avg` decimal(3,2) DEFAULT 0.00,
  `rating_count` int(11) DEFAULT 0,
  `enrollment_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_category` (`category_id`),
  KEY `idx_instructor` (`instructor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`is_featured`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `course_sections`
-- --------------------------------------------------------

CREATE TABLE `course_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_course` (`course_id`),
  KEY `idx_sort` (`sort_order`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `lessons`
-- --------------------------------------------------------

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `video_duration` int(11) DEFAULT NULL,
  `lesson_type` enum('video','text','quiz','assignment','file') NOT NULL DEFAULT 'video',
  `file_path` varchar(255) DEFAULT NULL,
  `is_preview` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_course` (`course_id`),
  KEY `idx_section` (`section_id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_sort` (`sort_order`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`section_id`) REFERENCES `course_sections`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `enrollments`
-- --------------------------------------------------------

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completion_date` timestamp NULL DEFAULT NULL,
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','completed','suspended','cancelled') NOT NULL DEFAULT 'active',
  `payment_status` enum('free','paid','pending','failed') NOT NULL DEFAULT 'free',
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `certificate_issued` tinyint(1) NOT NULL DEFAULT 0,
  `certificate_id` varchar(255) DEFAULT NULL,
  `last_accessed` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`user_id`, `course_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_course` (`course_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `lesson_progress`
-- --------------------------------------------------------

CREATE TABLE `lesson_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completion_date` timestamp NULL DEFAULT NULL,
  `watch_time` int(11) DEFAULT 0,
  `last_position` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_progress` (`user_id`, `lesson_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_lesson` (`lesson_id`),
  KEY `idx_course` (`course_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `quizzes`
-- --------------------------------------------------------

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `time_limit` int(11) DEFAULT NULL,
  `max_attempts` int(11) DEFAULT 1,
  `passing_score` decimal(5,2) NOT NULL DEFAULT 70.00,
  `randomize_questions` tinyint(1) NOT NULL DEFAULT 0,
  `show_results` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_course` (`course_id`),
  KEY `idx_lesson` (`lesson_id`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `quiz_questions`
-- --------------------------------------------------------

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','fill_blank','essay') NOT NULL DEFAULT 'multiple_choice',
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `explanation` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_quiz` (`quiz_id`),
  KEY `idx_sort` (`sort_order`),
  FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `quiz_options`
-- --------------------------------------------------------

CREATE TABLE `quiz_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_question` (`question_id`),
  KEY `idx_sort` (`sort_order`),
  FOREIGN KEY (`question_id`) REFERENCES `quiz_questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `quiz_attempts`
-- --------------------------------------------------------

CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `score` decimal(5,2) DEFAULT NULL,
  `total_points` decimal(5,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `is_passed` tinyint(1) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `time_taken` int(11) DEFAULT NULL,
  `status` enum('in_progress','completed','abandoned') NOT NULL DEFAULT 'in_progress',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_quiz` (`quiz_id`),
  KEY `idx_attempt` (`attempt_number`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `quiz_answers`
-- --------------------------------------------------------

CREATE TABLE `quiz_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_id` int(11) DEFAULT NULL,
  `answer_text` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `points_earned` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attempt` (`attempt_id`),
  KEY `idx_question` (`question_id`),
  KEY `idx_option` (`option_id`),
  FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`question_id`) REFERENCES `quiz_questions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`option_id`) REFERENCES `quiz_options`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `assignments`
-- --------------------------------------------------------

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `instructions` text DEFAULT NULL,
  `max_points` decimal(5,2) NOT NULL DEFAULT 100.00,
  `due_date` timestamp NULL DEFAULT NULL,
  `submission_type` enum('text','file','both') NOT NULL DEFAULT 'text',
  `allowed_file_types` varchar(255) DEFAULT NULL,
  `max_file_size` int(11) DEFAULT 5242880,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_course` (`course_id`),
  KEY `idx_lesson` (`lesson_id`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `assignment_submissions`
-- --------------------------------------------------------

CREATE TABLE `assignment_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `submission_text` longtext DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('submitted','graded','returned') NOT NULL DEFAULT 'submitted',
  `score` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `graded_by` int(11) DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_submission` (`assignment_id`, `user_id`),
  KEY `idx_assignment` (`assignment_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_grader` (`graded_by`),
  FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`graded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `certificates`
-- --------------------------------------------------------

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `certificate_number` varchar(255) NOT NULL UNIQUE,
  `issued_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completion_date` timestamp NOT NULL,
  `final_score` decimal(5,2) DEFAULT NULL,
  `certificate_url` varchar(500) DEFAULT NULL,
  `is_valid` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_course` (`course_id`),
  KEY `idx_certificate_number` (`certificate_number`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `course_reviews`
-- --------------------------------------------------------

CREATE TABLE `course_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `review` text DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_review` (`course_id`, `user_id`),
  KEY `idx_course` (`course_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_rating` (`rating`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `discussions`
-- --------------------------------------------------------

CREATE TABLE `discussions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `reply_count` int(11) NOT NULL DEFAULT 0,
  `last_reply_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_course` (`course_id`),
  KEY `idx_lesson` (`lesson_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_pinned` (`is_pinned`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `discussion_replies`
-- --------------------------------------------------------

CREATE TABLE `discussion_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `discussion_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_instructor_reply` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_discussion` (`discussion_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_parent` (`parent_id`),
  FOREIGN KEY (`discussion_id`) REFERENCES `discussions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `discussion_replies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `coupons`
-- --------------------------------------------------------

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL UNIQUE,
  `description` varchar(255) DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `minimum_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `valid_from` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `valid_until` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_code` (`code`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payments`
-- --------------------------------------------------------

CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `coupon_id` int(11) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_course` (`course_id`),
  KEY `idx_enrollment` (`enrollment_id`),
  KEY `idx_coupon` (`coupon_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `system_settings`
-- --------------------------------------------------------

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) NOT NULL UNIQUE,
  `setting_value` longtext DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_key` (`setting_key`),
  KEY `idx_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `activity_logs`
-- --------------------------------------------------------

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert default admin user
-- --------------------------------------------------------

INSERT INTO `users` (`role`, `full_name`, `email`, `password`, `is_active`, `email_verified`) VALUES
('admin', 'System Administrator', 'admin@careerpath.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1);

-- --------------------------------------------------------
-- Insert default categories
-- --------------------------------------------------------

INSERT INTO `categories` (`name`, `slug`, `description`, `is_active`, `sort_order`) VALUES
('Technology', 'technology', 'Programming, Web Development, Mobile Development', 1, 1),
('Business', 'business', 'Management, Marketing, Finance, Entrepreneurship', 1, 2),
('Design', 'design', 'Graphic Design, UI/UX, Web Design', 1, 3),
('Personal Development', 'personal-development', 'Leadership, Communication, Productivity', 1, 4),
('Health & Wellness', 'health-wellness', 'Fitness, Nutrition, Mental Health', 1, 5);

-- --------------------------------------------------------
-- Insert default system settings
-- --------------------------------------------------------

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_public`) VALUES
('site_name', 'Career Path Institute', 'string', 'Website name', 1),
('site_description', 'Learn new skills and advance your career', 'string', 'Website description', 1),
('site_email', 'info@careerpath.com', 'string', 'Contact email', 1),
('currency', 'USD', 'string', 'Default currency', 1),
('timezone', 'UTC', 'string', 'Default timezone', 0),
('max_file_upload_size', '10485760', 'number', 'Maximum file upload size in bytes', 0),
('email_verification_required', '1', 'boolean', 'Require email verification for new users', 0),
('course_auto_approval', '0', 'boolean', 'Auto approve new courses', 0),
('certificate_enabled', '1', 'boolean', 'Enable certificate generation', 1);

COMMIT;

-- --------------------------------------------------------
-- Create indexes for better performance
-- --------------------------------------------------------

-- Additional indexes for common queries
CREATE INDEX idx_courses_status_featured ON courses(status, is_featured);
CREATE INDEX idx_enrollments_user_status ON enrollments(user_id, status);
CREATE INDEX idx_lesson_progress_user_course ON lesson_progress(user_id, course_id);
CREATE INDEX idx_quiz_attempts_user_quiz ON quiz_attempts(user_id, quiz_id);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX idx_payments_status_date ON payments(status, payment_date);
CREATE INDEX idx_activity_logs_user_created ON activity_logs(user_id, created_at);

-- --------------------------------------------------------
-- Create views for common queries
-- --------------------------------------------------------

-- View for course statistics
CREATE VIEW course_stats AS
SELECT 
    c.id,
    c.title,
    c.status,
    COUNT(DISTINCT e.id) as total_enrollments,
    COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.id END) as completed_enrollments,
    AVG(cr.rating) as avg_rating,
    COUNT(DISTINCT cr.id) as total_reviews,
    c.created_at
FROM courses c
LEFT JOIN enrollments e ON c.id = e.course_id
LEFT JOIN course_reviews cr ON c.id = cr.course_id AND cr.is_approved = 1
GROUP BY c.id;

-- View for user progress
CREATE VIEW user_progress AS
SELECT 
    u.id as user_id,
    u.full_name,
    COUNT(DISTINCT e.id) as enrolled_courses,
    COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.id END) as completed_courses,
    COUNT(DISTINCT cert.id) as certificates_earned,
    AVG(e.progress_percentage) as avg_progress
FROM users u
LEFT JOIN enrollments e ON u.id = e.user_id
LEFT JOIN certificates cert ON u.id = cert.user_id
WHERE u.role = 'student'
GROUP BY u.id;

-- View for instructor statistics
CREATE VIEW instructor_stats AS
SELECT 
    u.id as instructor_id,
    u.full_name,
    COUNT(DISTINCT c.id) as total_courses,
    COUNT(DISTINCT CASE WHEN c.status = 'published' THEN c.id END) as published_courses,
    COUNT(DISTINCT e.id) as total_students,
    AVG(cr.rating) as avg_rating
FROM users u
LEFT JOIN courses c ON u.id = c.instructor_id
LEFT JOIN enrollments e ON c.id = e.course_id
LEFT JOIN course_reviews cr ON c.id = cr.course_id AND cr.is_approved = 1
WHERE u.role = 'instructor'
GROUP BY u.id;

-- --------------------------------------------------------
-- Create triggers for automatic updates
-- --------------------------------------------------------

DELIMITER $$

-- Trigger to update course lesson count
CREATE TRIGGER update_course_lesson_count 
AFTER INSERT ON lessons 
FOR EACH ROW 
BEGIN
    UPDATE courses 
    SET total_lessons = (
        SELECT COUNT(*) FROM lessons WHERE course_id = NEW.course_id
    ) 
    WHERE id = NEW.course_id;
END$$

-- Trigger to update course rating
CREATE TRIGGER update_course_rating 
AFTER INSERT ON course_reviews 
FOR EACH ROW 
BEGIN
    UPDATE courses 
    SET 
        rating_avg = (
            SELECT AVG(rating) FROM course_reviews 
            WHERE course_id = NEW.course_id AND is_approved = 1
        ),
        rating_count = (
            SELECT COUNT(*) FROM course_reviews 
            WHERE course_id = NEW.course_id AND is_approved = 1
        )
    WHERE id = NEW.course_id;
END$$

-- Trigger to update enrollment count
CREATE TRIGGER update_enrollment_count 
AFTER INSERT ON enrollments 
FOR EACH ROW 
BEGIN
    UPDATE courses 
    SET enrollment_count = (
        SELECT COUNT(*) FROM enrollments WHERE course_id = NEW.course_id
    ) 
    WHERE id = NEW.course_id;
END$$

-- Trigger to update discussion reply count
CREATE TRIGGER update_discussion_reply_count 
AFTER INSERT ON discussion_replies 
FOR EACH ROW 
BEGIN
    UPDATE discussions 
    SET 
        reply_count = (
            SELECT COUNT(*) FROM discussion_replies WHERE discussion_id = NEW.discussion_id
        ),
        last_reply_at = NOW()
    WHERE id = NEW.discussion_id;
END$$

DELIMITER ;

-- --------------------------------------------------------
-- End of schema
-- --------------------------------------------------------

-- ========================================
-- ADVANCED FEATURES - BLOG SYSTEM
-- ========================================

CREATE TABLE `blogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `content` longtext NOT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(500) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `views_count` int(11) DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_blog_published` (`is_published`),
  KEY `idx_blog_featured` (`is_featured`),
  KEY `idx_blog_author` (`author_id`),
  KEY `idx_blog_category` (`category_id`),
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blog_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL UNIQUE,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blog_tag_relations` (
  `blog_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`blog_id`, `tag_id`),
  FOREIGN KEY (`blog_id`) REFERENCES `blogs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `blog_tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- CURRENT AFFAIRS SYSTEM
-- ========================================

CREATE TABLE `current_affairs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `date` date NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `importance_level` enum('low','medium','high','critical') DEFAULT 'medium',
  `source` varchar(255) DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `is_premium` tinyint(1) DEFAULT 0,
  `price` decimal(10,2) DEFAULT 0.00,
  `views_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ca_date` (`date`),
  KEY `idx_ca_category` (`category`),
  KEY `idx_ca_premium` (`is_premium`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- LIVE CLASSES SYSTEM
-- ========================================

CREATE TABLE `live_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructor_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `scheduled_at` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `meeting_url` varchar(500) DEFAULT NULL,
  `meeting_id` varchar(100) DEFAULT NULL,
  `meeting_password` varchar(100) DEFAULT NULL,
  `max_participants` int(11) DEFAULT 100,
  `status` enum('scheduled','live','completed','cancelled') DEFAULT 'scheduled',
  `recording_url` varchar(500) DEFAULT NULL,
  `is_premium` tinyint(1) DEFAULT 0,
  `price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_live_scheduled` (`scheduled_at`),
  KEY `idx_live_status` (`status`),
  KEY `idx_live_instructor` (`instructor_id`),
  KEY `idx_live_course` (`course_id`),
  FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `live_class_enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `live_class_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `attended` tinyint(1) DEFAULT 0,
  `attendance_duration` int(11) DEFAULT 0,
  `payment_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`user_id`, `live_class_id`),
  KEY `idx_live_enrollment_user` (`user_id`),
  KEY `idx_live_enrollment_class` (`live_class_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`live_class_id`) REFERENCES `live_classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- MATERIALS SYSTEM
-- ========================================

CREATE TABLE `materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('pdf','doc','video','audio','image','link','zip','ppt') NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `download_url` varchar(500) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `is_premium` tinyint(1) DEFAULT 0,
  `price` decimal(10,2) DEFAULT 0.00,
  `download_count` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_material_course` (`course_id`),
  KEY `idx_material_lesson` (`lesson_id`),
  KEY `idx_material_type` (`type`),
  KEY `idx_material_creator` (`created_by`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- ENHANCED TEST/QUIZ SYSTEM
-- ========================================

CREATE TABLE `tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `type` enum('practice','mock','live','assessment') DEFAULT 'practice',
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `duration_minutes` int(11) NOT NULL DEFAULT 60,
  `total_marks` int(11) NOT NULL DEFAULT 100,
  `passing_marks` int(11) NOT NULL DEFAULT 40,
  `negative_marking` tinyint(1) DEFAULT 0,
  `negative_marks_per_question` decimal(3,2) DEFAULT 0.25,
  `instructions` text DEFAULT NULL,
  `is_premium` tinyint(1) DEFAULT 0,
  `price` decimal(10,2) DEFAULT 0.00,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `max_attempts` int(11) DEFAULT 1,
  `show_results` tinyint(1) DEFAULT 1,
  `show_correct_answers` tinyint(1) DEFAULT 1,
  `randomize_questions` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_test_course` (`course_id`),
  KEY `idx_test_category` (`category_id`),
  KEY `idx_test_type` (`type`),
  KEY `idx_test_active` (`is_active`),
  KEY `idx_test_creator` (`created_by`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `test_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('mcq','multiple_select','true_false','fill_blank','essay') DEFAULT 'mcq',
  `options` json DEFAULT NULL,
  `correct_answer` json DEFAULT NULL,
  `explanation` text DEFAULT NULL,
  `marks` int(11) DEFAULT 1,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `order_index` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_question_test` (`test_id`),
  KEY `idx_question_order` (`order_index`),
  FOREIGN KEY (`test_id`) REFERENCES `tests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `test_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `attempt_number` int(11) DEFAULT 1,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `time_taken` int(11) DEFAULT NULL,
  `total_questions` int(11) DEFAULT 0,
  `attempted_questions` int(11) DEFAULT 0,
  `correct_answers` int(11) DEFAULT 0,
  `wrong_answers` int(11) DEFAULT 0,
  `marks_obtained` decimal(6,2) DEFAULT 0.00,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `status` enum('in_progress','completed','abandoned') DEFAULT 'in_progress',
  `answers` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attempt_user` (`user_id`),
  KEY `idx_attempt_test` (`test_id`),
  KEY `idx_attempt_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`test_id`) REFERENCES `tests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- VIDEO ANALYTICS SYSTEM
-- ========================================

CREATE TABLE `video_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `video_duration` int(11) DEFAULT NULL,
  `watched_duration` int(11) DEFAULT 0,
  `watch_percentage` decimal(5,2) DEFAULT 0.00,
  `last_position` int(11) DEFAULT 0,
  `completed` tinyint(1) DEFAULT 0,
  `watch_sessions` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_lesson` (`user_id`, `lesson_id`),
  KEY `idx_video_user` (`user_id`),
  KEY `idx_video_lesson` (`lesson_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- NOTIFICATIONS SYSTEM
-- ========================================

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error','course','payment','test','live_class','blog') DEFAULT 'info',
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notification_user` (`user_id`),
  KEY `idx_notification_read` (`is_read`),
  KEY `idx_notification_type` (`type`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- DISCUSSION/FORUM SYSTEM
-- ========================================
-- BOOKMARKS/FAVORITES SYSTEM
-- ========================================

CREATE TABLE `bookmarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `bookmarkable_type` enum('course','lesson','blog','material','test','current_affair') NOT NULL,
  `bookmarkable_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_bookmark` (`user_id`, `bookmarkable_type`, `bookmarkable_id`),
  KEY `idx_bookmark_user` (`user_id`),
  KEY `idx_bookmark_type` (`bookmarkable_type`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SAMPLE DATA FOR NEW FEATURES
-- ========================================

-- Sample blog tags
INSERT INTO `blog_tags` (`name`, `slug`) VALUES
('Technology', 'technology'),
('Programming', 'programming'),
('Career', 'career'),
('Education', 'education'),
('Tips', 'tips'),
('Web Development', 'web-development'),
('Mobile Development', 'mobile-development'),
('Data Science', 'data-science'),
('AI/ML', 'ai-ml'),
('Cybersecurity', 'cybersecurity');

-- Sample current affairs
INSERT INTO `current_affairs` (`title`, `content`, `date`, `category`, `importance_level`, `is_premium`) VALUES
('Technology Updates 2026', 'Latest technology trends and updates for the year 2026. This includes AI advancements, new programming languages, and emerging technologies.', CURDATE(), 'Technology', 'high', 0),
('Economic Policy Changes', 'Recent changes in economic policies affecting the education sector and job market.', CURDATE() - INTERVAL 1 DAY, 'Economics', 'medium', 1),
('Education Reforms', 'New education reforms and their impact on students and institutions.', CURDATE() - INTERVAL 2 DAY, 'Education', 'high', 0);

-- Sample test categories (avoiding duplicates)
INSERT IGNORE INTO `categories` (`name`, `slug`, `description`, `is_active`, `sort_order`) VALUES
('Current Affairs', 'current-affairs', 'Daily current affairs and news updates', 1, 6),
('Test Preparation', 'test-preparation', 'Competitive exam preparation', 1, 7),
('Technical Skills', 'technical-skills', 'Programming and technical assessments', 1, 8),
('Aptitude', 'aptitude', 'Logical reasoning and aptitude tests', 1, 9);

-- Sample Tests Data
INSERT INTO `tests` (`title`, `description`, `category_id`, `type`, `difficulty_level`, `duration_minutes`, `total_marks`, `passing_marks`, `instructions`, `is_premium`, `price`, `max_attempts`, `show_results`, `show_correct_answers`, `randomize_questions`, `is_active`, `created_by`) VALUES
('General Knowledge Mock Test', 'Comprehensive general knowledge test covering various topics', 6, 'mock', 'intermediate', 60, 100, 40, 'Read all questions carefully. Each question carries 1 mark. No negative marking.', 0, 0.00, 3, 1, 1, 1, 1, 1),
('Current Affairs Weekly Test', 'Weekly current affairs test covering recent events', 6, 'practice', 'beginner', 30, 50, 20, 'Test your knowledge of recent current events.', 1, 99.00, 2, 1, 1, 0, 1, 1),
('Programming Aptitude Test', 'Test your programming logic and problem-solving skills', 8, 'assessment', 'advanced', 90, 150, 75, 'Advanced programming concepts and logical reasoning.', 1, 199.00, 1, 1, 0, 1, 1, 1),
('Daily Current Affairs Quiz', 'Quick daily quiz on current events', 6, 'practice', 'beginner', 15, 25, 10, 'Daily practice quiz for current affairs preparation.', 0, 0.00, 5, 1, 1, 0, 1, 1),
('Technical Skills Assessment', 'Comprehensive technical skills evaluation', 8, 'assessment', 'intermediate', 120, 200, 100, 'Complete technical assessment covering multiple domains.', 1, 299.00, 2, 1, 1, 1, 1, 1);

-- Sample Test Questions
INSERT INTO `test_questions` (`test_id`, `question_text`, `question_type`, `options`, `correct_answer`, `explanation`, `marks`, `difficulty`, `order_index`) VALUES
(1, 'What is the capital of India?', 'mcq', '["New Delhi", "Mumbai", "Kolkata", "Chennai"]', '["New Delhi"]', 'New Delhi is the capital city of India.', 1, 'easy', 1),
(1, 'Who is known as the Father of the Nation in India?', 'mcq', '["Mahatma Gandhi", "Jawaharlal Nehru", "Subhas Chandra Bose", "Bhagat Singh"]', '["Mahatma Gandhi"]', 'Mahatma Gandhi is widely regarded as the Father of the Nation in India.', 1, 'easy', 2),
(1, 'The Earth revolves around the Sun. True or False?', 'true_false', '["True", "False"]', '["True"]', 'The Earth orbits around the Sun in an elliptical path.', 1, 'easy', 3),
(2, 'Who is the current Prime Minister of India (as of 2026)?', 'mcq', '["Narendra Modi", "Rahul Gandhi", "Amit Shah", "Yogi Adityanath"]', '["Narendra Modi"]', 'Narendra Modi has been serving as Prime Minister since 2014.', 2, 'medium', 1),
(2, 'Which country hosted the 2024 Olympics?', 'mcq', '["France", "Japan", "USA", "Australia"]', '["France"]', 'Paris, France hosted the 2024 Summer Olympics.', 2, 'medium', 2),
(4, 'What does AI stand for?', 'mcq', '["Artificial Intelligence", "Automated Integration", "Advanced Interface", "Algorithmic Implementation"]', '["Artificial Intelligence"]', 'AI stands for Artificial Intelligence.', 1, 'easy', 1),
(4, 'Which programming language is known for web development?', 'mcq', '["JavaScript", "Assembly", "COBOL", "Fortran"]', '["JavaScript"]', 'JavaScript is widely used for web development.', 1, 'easy', 2);

-- Sample Materials Data
INSERT INTO `materials` (`title`, `description`, `type`, `file_path`, `course_id`, `is_premium`, `price`, `created_by`) VALUES
('Current Affairs Handbook 2026', 'Comprehensive handbook covering all major events of 2026', 'pdf', '/materials/current-affairs-2026.pdf', NULL, 1, 149.00, 1),
('Programming Interview Questions', 'Collection of most asked programming interview questions', 'pdf', '/materials/programming-interview.pdf', NULL, 1, 99.00, 1),
('General Knowledge Notes', 'Important general knowledge notes for competitive exams', 'pdf', '/materials/gk-notes.pdf', NULL, 0, 0.00, 1),
('Mock Test Solutions', 'Detailed solutions for all mock tests', 'pdf', '/materials/mock-solutions.pdf', NULL, 1, 79.00, 1),
('Study Plan Template', 'Effective study plan template for exam preparation', 'doc', '/materials/study-plan.doc', NULL, 0, 0.00, 1);

-- Sample Live Classes Data
INSERT INTO `live_classes` (`title`, `description`, `instructor_id`, `scheduled_at`, `duration_minutes`, `meeting_url`, `status`, `is_premium`, `price`) VALUES
('Current Affairs Discussion', 'Weekly discussion on important current events', 1, DATE_ADD(NOW(), INTERVAL 2 DAY), 60, 'https://meet.example.com/current-affairs', 'scheduled', 1, 199.00),
('Programming Concepts Live', 'Live session on advanced programming concepts', 1, DATE_ADD(NOW(), INTERVAL 5 DAY), 90, 'https://meet.example.com/programming', 'scheduled', 1, 299.00),
('Mock Test Discussion', 'Discussion and analysis of recent mock tests', 1, DATE_ADD(NOW(), INTERVAL 1 DAY), 45, 'https://meet.example.com/mock-test', 'scheduled', 0, 0.00),
('Career Guidance Session', 'Career guidance and counseling session', 1, DATE_ADD(NOW(), INTERVAL 7 DAY), 60, 'https://meet.example.com/career', 'scheduled', 1, 149.00);

-- Sample Blog Posts
INSERT INTO `blogs` (`title`, `slug`, `content`, `excerpt`, `author_id`, `category_id`, `is_published`, `is_featured`) VALUES
('Top 10 Current Affairs Topics for 2026', 'top-10-current-affairs-2026', 'Here are the most important current affairs topics you should focus on for competitive exams in 2026...', 'Essential current affairs topics for exam preparation', 1, 6, 1, 1),
('How to Prepare for Technical Interviews', 'technical-interview-preparation', 'Technical interviews can be challenging. Here is a comprehensive guide to help you prepare effectively...', 'Complete guide for technical interview preparation', 1, 8, 1, 0),
('Study Tips for Competitive Exams', 'study-tips-competitive-exams', 'Effective study strategies that can help you excel in competitive examinations...', 'Proven study strategies for competitive exam success', 1, 7, 1, 1),
('Understanding Current Economic Policies', 'current-economic-policies', 'An analysis of recent economic policy changes and their implications...', 'Analysis of recent economic policy developments', 1, 6, 1, 0);

-- Enhanced Current Affairs with more variety
INSERT INTO `current_affairs` (`title`, `content`, `date`, `category`, `importance_level`, `source`, `tags`, `is_premium`, `price`) VALUES
('Budget 2026 Key Highlights', 'The Union Budget 2026 presented several key announcements including increased allocation for education sector, new tax slabs, and infrastructure development initiatives. The budget focuses on digital transformation and sustainable development goals.', CURDATE(), 'Economics', 'critical', 'Ministry of Finance', '["budget", "economics", "policy", "taxation"]', 1, 49.00),
('Space Mission Success', 'India successfully launched its latest satellite mission, marking another milestone in space technology. The mission aims to enhance communication capabilities and weather forecasting systems.', CURDATE() - INTERVAL 1 DAY, 'Science & Technology', 'high', 'ISRO', '["space", "technology", "satellite", "isro"]', 0, 0.00),
('New Education Policy Updates', 'Recent updates to the National Education Policy include new guidelines for online learning, skill development programs, and research initiatives in higher education institutions.', CURDATE() - INTERVAL 2 DAY, 'Education', 'high', 'Ministry of Education', '["education", "policy", "skills", "research"]', 1, 29.00),
('Climate Change Summit Outcomes', 'The recent international climate summit concluded with new commitments for carbon neutrality and renewable energy adoption. India announced ambitious targets for solar energy expansion.', CURDATE() - INTERVAL 3 DAY, 'Environment', 'critical', 'UN Climate Summit', '["climate", "environment", "renewable", "sustainability"]', 0, 0.00),
('Digital Payment Trends', 'Latest statistics show significant growth in digital payment adoption across rural and urban areas. New security measures and user-friendly interfaces are driving this growth.', CURDATE() - INTERVAL 4 DAY, 'Technology', 'medium', 'RBI Report', '["digital", "payments", "fintech", "banking"]', 1, 19.00),
('Healthcare Infrastructure Development', 'Government announces major healthcare infrastructure projects including new medical colleges, hospitals, and telemedicine initiatives to improve healthcare accessibility.', CURDATE() - INTERVAL 5 DAY, 'Healthcare', 'high', 'Ministry of Health', '["healthcare", "infrastructure", "medical", "telemedicine"]', 0, 0.00),
('Agricultural Technology Innovations', 'New agricultural technologies including drone-based monitoring, precision farming, and AI-powered crop management systems are being implemented across various states.', CURDATE() - INTERVAL 6 DAY, 'Agriculture', 'medium', 'Ministry of Agriculture', '["agriculture", "technology", "farming", "innovation"]', 1, 39.00);

-- Sample Courses Data
INSERT INTO `courses` (`title`, `slug`, `description`, `short_description`, `category_id`, `instructor_id`, `level`, `price`, `discount_price`, `duration_hours`, `status`, `is_featured`) VALUES
('Complete Web Development Bootcamp', 'complete-web-development-bootcamp', 'Learn HTML, CSS, JavaScript, React, Node.js, and more in this comprehensive web development course. Build real-world projects and get job-ready skills.', 'Master web development from scratch with hands-on projects', 1, 1, 'beginner', 299.00, 199.00, 40, 'published', 1),
('Digital Marketing Mastery', 'digital-marketing-mastery', 'Complete digital marketing course covering SEO, social media marketing, Google Ads, email marketing, and analytics. Perfect for beginners and professionals.', 'Learn all aspects of digital marketing and grow your business', 2, 1, 'intermediate', 199.00, NULL, 25, 'published', 1),
('UI/UX Design Fundamentals', 'ui-ux-design-fundamentals', 'Learn the principles of user interface and user experience design. Master Figma, create wireframes, prototypes, and design beautiful user interfaces.', 'Design beautiful and user-friendly interfaces', 3, 1, 'beginner', 149.00, 99.00, 20, 'published', 0),
('Python Programming for Beginners', 'python-programming-beginners', 'Start your programming journey with Python. Learn syntax, data structures, algorithms, and build practical projects including web scraping and automation.', 'Learn Python programming from zero to hero', 1, 1, 'beginner', 0.00, NULL, 30, 'published', 1),
('Advanced JavaScript Concepts', 'advanced-javascript-concepts', 'Deep dive into advanced JavaScript topics including closures, prototypes, async programming, ES6+ features, and modern development patterns.', 'Master advanced JavaScript for professional development', 1, 1, 'advanced', 249.00, NULL, 35, 'published', 0),
('Business Strategy and Leadership', 'business-strategy-leadership', 'Develop strategic thinking and leadership skills. Learn how to create business strategies, lead teams, and drive organizational growth.', 'Build essential business and leadership skills', 2, 1, 'intermediate', 179.00, 129.00, 18, 'published', 0);

-- Sample Course Sections
INSERT INTO `course_sections` (`course_id`, `title`, `description`, `sort_order`) VALUES
(1, 'HTML & CSS Fundamentals', 'Learn the building blocks of web development', 1),
(1, 'JavaScript Essentials', 'Master JavaScript programming concepts', 2),
(1, 'React Development', 'Build modern web applications with React', 3),
(1, 'Backend Development', 'Server-side programming with Node.js', 4),
(2, 'Digital Marketing Basics', 'Introduction to digital marketing concepts', 1),
(2, 'SEO & Content Marketing', 'Search engine optimization and content strategy', 2),
(2, 'Social Media Marketing', 'Leverage social platforms for business growth', 3),
(3, 'Design Principles', 'Fundamental principles of good design', 1),
(3, 'User Research', 'Understanding your users and their needs', 2),
(3, 'Prototyping & Testing', 'Create and test your design ideas', 3);

-- Sample Lessons
INSERT INTO `lessons` (`course_id`, `section_id`, `title`, `slug`, `content`, `lesson_type`, `is_preview`, `sort_order`) VALUES
(1, 1, 'Introduction to HTML', 'introduction-to-html', 'Learn the basics of HTML markup language and how to structure web pages.', 'video', 1, 1),
(1, 1, 'CSS Styling Basics', 'css-styling-basics', 'Understand how to style HTML elements with CSS properties and selectors.', 'video', 1, 2),
(1, 1, 'Responsive Web Design', 'responsive-web-design', 'Create websites that work on all devices using responsive design techniques.', 'video', 0, 3),
(1, 2, 'JavaScript Variables and Functions', 'javascript-variables-functions', 'Learn about JavaScript variables, data types, and how to create functions.', 'video', 1, 1),
(1, 2, 'DOM Manipulation', 'dom-manipulation', 'Interact with web page elements using JavaScript DOM methods.', 'video', 0, 2),
(2, 1, 'What is Digital Marketing?', 'what-is-digital-marketing', 'Overview of digital marketing channels and strategies.', 'video', 1, 1),
(2, 1, 'Setting Marketing Goals', 'setting-marketing-goals', 'How to set SMART goals for your marketing campaigns.', 'text', 1, 2),
(3, 1, 'Color Theory and Typography', 'color-theory-typography', 'Understanding color psychology and choosing the right fonts.', 'video', 1, 1),
(4, NULL, 'Python Installation and Setup', 'python-installation-setup', 'Get Python installed and set up your development environment.', 'video', 1, 1),
(4, NULL, 'Variables and Data Types', 'variables-data-types', 'Learn about Python variables and different data types.', 'video', 1, 2);

-- Sample Coupons for Testing
INSERT INTO `coupons` (`code`, `description`, `discount_type`, `discount_value`, `minimum_amount`, `usage_limit`, `valid_from`, `valid_until`, `is_active`) VALUES
('WELCOME50', 'Welcome discount for new users', 'percentage', 50.00, 100.00, 100, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1),
('STUDENT20', 'Student discount on all premium content', 'percentage', 20.00, 50.00, 500, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY), 1),
('PREMIUM100', 'Fixed discount on premium tests', 'fixed', 100.00, 200.00, 50, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY), 1),
('EARLYBIRD', 'Early bird discount', 'percentage', 30.00, 150.00, 200, NOW(), DATE_ADD(NOW(), INTERVAL 45 DAY), 1);

COMMIT;

-- --------------------------------------------------------
-- Table structure for table `enrollments`
-- --------------------------------------------------------

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completion_date` timestamp NULL DEFAULT NULL,
  `status` enum('active','completed','suspended','cancelled') NOT NULL DEFAULT 'active',
  `payment_status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `last_accessed` timestamp NULL DEFAULT NULL,
  `certificate_issued` tinyint(1) NOT NULL DEFAULT 0,
  `certificate_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_course` (`user_id`, `course_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_course` (`course_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_enrollment_date` (`enrollment_date`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payments`
-- --------------------------------------------------------

CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `payment_method` enum('razorpay','paypal','bank_transfer','cash') NOT NULL DEFAULT 'razorpay',
  `amount` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'INR',
  `status` enum('pending','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `coupon_id` int(11) DEFAULT NULL,
  `coupon_code` varchar(50) DEFAULT NULL,
  `transaction_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `refund_reason` varchar(255) DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_course` (`course_id`),
  KEY `idx_enrollment` (`enrollment_id`),
  KEY `idx_status` (`status`),
  KEY `idx_razorpay_order` (`razorpay_order_id`),
  KEY `idx_razorpay_payment` (`razorpay_payment_id`),
  KEY `idx_coupon` (`coupon_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `coupons`
-- --------------------------------------------------------

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL UNIQUE,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `minimum_amount` decimal(10,2) DEFAULT NULL,
  `maximum_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `user_limit` int(11) DEFAULT NULL,
  `applicable_courses` text DEFAULT NULL,
  `applicable_categories` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_code` (`code`),
  KEY `idx_active` (`is_active`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_created_by` (`created_by`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `tests`
-- --------------------------------------------------------

CREATE TABLE `tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `difficulty_level` enum('easy','medium','hard') NOT NULL DEFAULT 'medium',
  `total_questions` int(11) NOT NULL DEFAULT 0,
  `duration_minutes` int(11) NOT NULL DEFAULT 60,
  `passing_score` decimal(5,2) NOT NULL DEFAULT 60.00,
  `max_attempts` int(11) DEFAULT NULL,
  `is_free` tinyint(1) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `instructions` text DEFAULT NULL,
  `show_results_immediately` tinyint(1) NOT NULL DEFAULT 1,
  `show_correct_answers` tinyint(1) NOT NULL DEFAULT 1,
  `randomize_questions` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_category` (`category_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_free` (`is_free`),
  KEY `idx_difficulty` (`difficulty_level`),
  KEY `idx_created_by` (`created_by`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `test_questions`
-- --------------------------------------------------------

CREATE TABLE `test_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','single_choice','true_false','fill_blank') NOT NULL DEFAULT 'single_choice',
  `options` json DEFAULT NULL,
  `correct_answer` text NOT NULL,
  `explanation` text DEFAULT NULL,
  `marks` decimal(5,2) NOT NULL DEFAULT 1.00,
  `negative_marks` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_test` (`test_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`),
  FOREIGN KEY (`test_id`) REFERENCES `tests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `test_attempts`
-- --------------------------------------------------------

CREATE TABLE `test_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `total_questions` int(11) NOT NULL DEFAULT 0,
  `attempted_questions` int(11) NOT NULL DEFAULT 0,
  `correct_answers` int(11) NOT NULL DEFAULT 0,
  `wrong_answers` int(11) NOT NULL DEFAULT 0,
  `skipped_questions` int(11) NOT NULL DEFAULT 0,
  `total_marks` decimal(8,2) NOT NULL DEFAULT 0.00,
  `obtained_marks` decimal(8,2) NOT NULL DEFAULT 0.00,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('in_progress','completed','abandoned','expired') NOT NULL DEFAULT 'in_progress',
  `is_passed` tinyint(1) NOT NULL DEFAULT 0,
  `answers` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_test` (`test_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_completed_at` (`completed_at`),
  KEY `idx_user_test` (`user_id`, `test_id`),
  FOREIGN KEY (`test_id`) REFERENCES `tests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `materials`
-- --------------------------------------------------------

CREATE TABLE `materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `material_type` enum('pdf','video','audio','document','link') NOT NULL DEFAULT 'pdf',
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `external_url` varchar(500) DEFAULT NULL,
  `is_free` tinyint(1) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `tags` varchar(500) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_category` (`category_id`),
  KEY `idx_type` (`material_type`),
  KEY `idx_active` (`is_active`),
  KEY `idx_free` (`is_free`),
  KEY `idx_created_by` (`created_by`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `blogs`
-- --------------------------------------------------------

CREATE TABLE `blogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `excerpt` varchar(500) DEFAULT NULL,
  `content` longtext NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `comment_count` int(11) NOT NULL DEFAULT 0,
  `tags` varchar(500) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_category` (`category_id`),
  KEY `idx_author` (`author_id`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_published_at` (`published_at`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `current_affairs`
-- --------------------------------------------------------

CREATE TABLE `current_affairs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `content` longtext NOT NULL,
  `summary` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `source_url` varchar(500) DEFAULT NULL,
  `source_name` varchar(255) DEFAULT NULL,
  `importance_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `tags` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `published_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_category` (`category_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_importance` (`importance_level`),
  KEY `idx_published_date` (`published_date`),
  KEY `idx_created_by` (`created_by`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `live_classes`
-- --------------------------------------------------------

CREATE TABLE `live_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructor_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `scheduled_at` timestamp NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 60,
  `meeting_url` varchar(500) DEFAULT NULL,
  `meeting_id` varchar(100) DEFAULT NULL,
  `meeting_password` varchar(100) DEFAULT NULL,
  `platform` enum('zoom','google_meet','teams','youtube') NOT NULL DEFAULT 'zoom',
  `max_participants` int(11) DEFAULT NULL,
  `is_free` tinyint(1) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('scheduled','live','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `recording_url` varchar(500) DEFAULT NULL,
  `materials` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_instructor` (`instructor_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_scheduled_at` (`scheduled_at`),
  KEY `idx_status` (`status`),
  KEY `idx_active` (`is_active`),
  KEY `idx_free` (`is_free`),
  FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `live_class_enrollments`
-- --------------------------------------------------------

CREATE TABLE `live_class_enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `live_class_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `attended` tinyint(1) NOT NULL DEFAULT 0,
  `attendance_duration` int(11) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'completed',
  `payment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_class_user` (`live_class_id`, `user_id`),
  KEY `idx_live_class` (`live_class_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_payment` (`payment_id`),
  FOREIGN KEY (`live_class_id`) REFERENCES `live_classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `audit_logs`
-- --------------------------------------------------------

CREATE TABLE `audit_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table` (`table_name`),
  KEY `idx_record` (`record_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert default admin user
-- --------------------------------------------------------

INSERT INTO `users` (
  `full_name`, `email`, `password`, `role`, `is_active`, `email_verified`, `created_at`
) VALUES (
  'System Administrator', 
  'admin@careerpathinstitute.com', 
  '$2y$12$LQv3c1yqBwlVHpPjreubu.CQKePK16OYBlqfgEXXvdtCLNyG2Mt7W', -- password: admin123!@#
  'admin', 
  1, 
  1, 
  NOW()
);

-- --------------------------------------------------------
-- Insert default categories
-- --------------------------------------------------------

INSERT INTO `categories` (`name`, `slug`, `description`, `is_active`, `sort_order`) VALUES
('Government Jobs', 'government-jobs', 'Preparation for various government job examinations', 1, 1),
('Banking', 'banking', 'Banking sector examination preparation', 1, 2),
('SSC', 'ssc', 'Staff Selection Commission examinations', 1, 3),
('Railway', 'railway', 'Railway recruitment board examinations', 1, 4),
('UPSC', 'upsc', 'Union Public Service Commission examinations', 1, 5),
('State PSC', 'state-psc', 'State Public Service Commission examinations', 1, 6),
('Current Affairs', 'current-affairs', 'Latest current affairs and general knowledge', 1, 7),
('General Knowledge', 'general-knowledge', 'General knowledge and awareness', 1, 8);

COMMIT;

-- --------------------------------------------------------
-- Create triggers for audit logging
-- --------------------------------------------------------

DELIMITER $$

CREATE TRIGGER `users_audit_insert` AFTER INSERT ON `users`
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values, ip_address, created_at)
    VALUES (NEW.id, 'INSERT', 'users', NEW.id, JSON_OBJECT(
        'full_name', NEW.full_name,
        'email', NEW.email,
        'role', NEW.role,
        'is_active', NEW.is_active
    ), @user_ip, NOW());
END$$

CREATE TRIGGER `users_audit_update` AFTER UPDATE ON `users`
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, created_at)
    VALUES (NEW.id, 'UPDATE', 'users', NEW.id, JSON_OBJECT(
        'full_name', OLD.full_name,
        'email', OLD.email,
        'role', OLD.role,
        'is_active', OLD.is_active
    ), JSON_OBJECT(
        'full_name', NEW.full_name,
        'email', NEW.email,
        'role', NEW.role,
        'is_active', NEW.is_active
    ), @user_ip, NOW());
END$$

CREATE TRIGGER `payments_audit_insert` AFTER INSERT ON `payments`
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values, ip_address, created_at)
    VALUES (NEW.user_id, 'INSERT', 'payments', NEW.id, JSON_OBJECT(
        'amount', NEW.amount,
        'final_amount', NEW.final_amount,
        'status', NEW.status,
        'payment_method', NEW.payment_method
    ), @user_ip, NOW());
END$$

CREATE TRIGGER `payments_audit_update` AFTER UPDATE ON `payments`
FOR EACH ROW BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, created_at)
    VALUES (NEW.user_id, 'UPDATE', 'payments', NEW.id, JSON_OBJECT(
        'status', OLD.status,
        'razorpay_payment_id', OLD.razorpay_payment_id
    ), JSON_OBJECT(
        'status', NEW.status,
        'razorpay_payment_id', NEW.razorpay_payment_id
    ), @user_ip, NOW());
END$$

DELIMITER ;