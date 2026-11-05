-- =====================================================================
-- Hotel Event Management System - Database Schema
-- Compatible with MySQL 5.7+ / MySQL 8.x / MariaDB (XAMPP Default)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `hotel_event_db` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `hotel_event_db`;

-- Drop existing tables if re-importing (in correct foreign key order)
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `event_packages`;
DROP TABLE IF EXISTS `function_halls`;
DROP TABLE IF EXISTS `staff`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `users`;

-- ---------------------------------------------------------------------
-- 1. USERS TABLE
-- Handles authentication & role-based access for all system actors
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'staff', 'customer') NOT NULL DEFAULT 'customer',
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. CUSTOMERS TABLE
-- Extended customer contact and profile data
-- ---------------------------------------------------------------------
CREATE TABLE `customers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(25) NOT NULL,
    `alternate_phone` VARCHAR(25) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(60) NULL,
    `id_number` VARCHAR(50) NULL COMMENT 'National ID or Passport number',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. STAFF TABLE
-- Extended staff member and banquet manager profiles
-- ---------------------------------------------------------------------
CREATE TABLE `staff` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `employee_code` VARCHAR(25) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `designation` VARCHAR(80) NOT NULL,
    `phone` VARCHAR(25) NOT NULL,
    `department` VARCHAR(60) NOT NULL DEFAULT 'Banquet Operations',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. FUNCTION HALLS TABLE
-- Banquet halls, ballrooms, and event spaces
-- ---------------------------------------------------------------------
CREATE TABLE `function_halls` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `hall_name` VARCHAR(120) NOT NULL,
    `hall_code` VARCHAR(25) NOT NULL UNIQUE,
    `capacity_min` INT UNSIGNED NOT NULL DEFAULT 30,
    `capacity_max` INT UNSIGNED NOT NULL DEFAULT 500,
    `price_per_hour` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `base_price_half_day` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `base_price_full_day` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `dimensions` VARCHAR(80) NULL COMMENT 'e.g., 100 x 60 ft (6,000 sq ft)',
    `description` TEXT NOT NULL,
    `amenities` TEXT NULL COMMENT 'Comma-separated or bullet list of features',
    `image_path` VARCHAR(255) NULL DEFAULT 'assets/images/halls/default_hall.jpg',
    `status` ENUM('active', 'maintenance', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_halls_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. EVENT PACKAGES TABLE
-- Catering, decor, and audio-visual service packages
-- ---------------------------------------------------------------------
CREATE TABLE `event_packages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `package_name` VARCHAR(120) NOT NULL,
    `package_code` VARCHAR(25) NOT NULL UNIQUE,
    `category` ENUM('wedding', 'corporate', 'birthday', 'anniversary', 'special') NOT NULL,
    `price_per_person` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `base_package_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `description` TEXT NOT NULL,
    `menu_details` TEXT NULL,
    `inclusions` TEXT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_packages_category_status` (`category`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. BOOKINGS TABLE
-- Core reservation entity tracking dates, times, pricing, and approval workflow
-- ---------------------------------------------------------------------
CREATE TABLE `bookings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_reference` VARCHAR(30) NOT NULL UNIQUE,
    `customer_id` INT UNSIGNED NOT NULL,
    `hall_id` INT UNSIGNED NOT NULL,
    `package_id` INT UNSIGNED NOT NULL,
    `event_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `guest_count` INT UNSIGNED NOT NULL,
    `event_type` VARCHAR(60) NOT NULL,
    `special_requests` TEXT NULL,
    
    -- Financial values
    `hall_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `package_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `additional_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `advance_required` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `advance_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `balance_due` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    
    -- Status progression
    `booking_status` ENUM('pending', 'approved', 'confirmed', 'rejected', 'cancelled', 'completed') 
        NOT NULL DEFAULT 'pending',
    `payment_status` ENUM('unpaid', 'partially_paid', 'fully_paid', 'refunded') 
        NOT NULL DEFAULT 'unpaid',
        
    -- Management auditing
    `reviewed_by` INT UNSIGNED NULL,
    `review_notes` TEXT NULL,
    `rejection_reason` TEXT NULL,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) 
        REFERENCES `customers` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_bookings_hall` FOREIGN KEY (`hall_id`) 
        REFERENCES `function_halls` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_bookings_package` FOREIGN KEY (`package_id`) 
        REFERENCES `event_packages` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_bookings_reviewer` FOREIGN KEY (`reviewed_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL,
        
    INDEX `idx_hall_date_status` (`hall_id`, `event_date`, `booking_status`),
    INDEX `idx_customer_date` (`customer_id`, `event_date`),
    INDEX `idx_booking_status` (`booking_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. PAYMENTS TABLE
-- Transaction records for advance deposits, balance settlements, etc.
-- ---------------------------------------------------------------------
CREATE TABLE `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `receipt_number` VARCHAR(35) NOT NULL UNIQUE,
    `booking_id` INT UNSIGNED NOT NULL,
    `payment_type` ENUM('advance', 'balance_settlement', 'installment', 'full_payment', 'cancellation_fee') 
        NOT NULL DEFAULT 'advance',
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_method` ENUM('cash', 'bank_transfer', 'credit_card', 'debit_card', 'cheque') NOT NULL,
    `transaction_reference` VARCHAR(100) NULL,
    `payment_date` DATE NOT NULL,
    `notes` TEXT NULL,
    `received_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) 
        REFERENCES `bookings` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_payments_staff` FOREIGN KEY (`received_by`) 
        REFERENCES `users` (`id`) ON DELETE RESTRICT,
        
    INDEX `idx_payments_booking` (`booking_id`),
    INDEX `idx_payments_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. SYSTEM SETTINGS TABLE
-- Master hotel settings, contact details, tax & deposit rates
-- ---------------------------------------------------------------------
CREATE TABLE `system_settings` (
    `id` INT UNSIGNED PRIMARY KEY DEFAULT 1,
    `hotel_name` VARCHAR(120) NOT NULL DEFAULT 'The Grand Horizon Hotel & Banquet',
    `hotel_tagline` VARCHAR(200) NOT NULL DEFAULT 'Exquisite Venues & Unforgettable Celebrations',
    `address` TEXT NOT NULL,
    `city_state_zip` VARCHAR(120) NOT NULL DEFAULT 'Oceanfront Avenue, Suite 100, Metropolis',
    `phone` VARCHAR(30) NOT NULL DEFAULT '+1 (555) 789-2000',
    `email` VARCHAR(100) NOT NULL DEFAULT 'events@grandhorizonhotel.com',
    `website` VARCHAR(100) NOT NULL DEFAULT 'www.grandhorizonhotel.com',
    `currency_symbol` VARCHAR(10) NOT NULL DEFAULT 'LKR',
    `tax_percentage` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    `advance_percentage` DECIMAL(5,2) NOT NULL DEFAULT 30.00,
    `cancellation_policy` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
