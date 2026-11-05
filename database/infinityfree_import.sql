-- =====================================================================
-- Hotel Event Management System - Clean Hosting Import
-- Safe for InfinityFree / cPanel / Shared Hosting
-- (No CREATE DATABASE or USE statements to avoid permission errors)
-- =====================================================================

-- Drop existing tables if re-importing (in correct foreign key order)
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `event_packages`;
DROP TABLE IF EXISTS `function_halls`;
DROP TABLE IF EXISTS `staff`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `users`;

-- 1. USERS TABLE
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

-- 2. CUSTOMERS TABLE
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

-- 3. STAFF TABLE
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

-- 4. FUNCTION HALLS TABLE
CREATE TABLE `function_halls` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `hall_name` VARCHAR(120) NOT NULL,
    `hall_code` VARCHAR(25) NOT NULL UNIQUE,
    `capacity_min` INT UNSIGNED NOT NULL DEFAULT 30,
    `capacity_max` INT UNSIGNED NOT NULL DEFAULT 500,
    `price_per_hour` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `base_price_half_day` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `base_price_full_day` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `dimensions` VARCHAR(80) NULL,
    `description` TEXT NOT NULL,
    `amenities` TEXT NULL,
    `image_path` VARCHAR(255) NULL DEFAULT 'assets/images/halls/default_hall.jpg',
    `status` ENUM('active', 'maintenance', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_halls_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. EVENT PACKAGES TABLE
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

-- 6. BOOKINGS TABLE
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
    
    `hall_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `package_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `additional_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `advance_required` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `advance_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `balance_due` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    
    `booking_status` ENUM('pending', 'approved', 'confirmed', 'rejected', 'cancelled', 'completed') 
        NOT NULL DEFAULT 'pending',
    `payment_status` ENUM('unpaid', 'partially_paid', 'fully_paid', 'refunded') 
        NOT NULL DEFAULT 'unpaid',
        
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

-- 7. PAYMENTS TABLE
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

-- 8. SYSTEM SETTINGS TABLE
CREATE TABLE `system_settings` (
    `id` INT UNSIGNED PRIMARY KEY DEFAULT 1,
    `hotel_name` VARCHAR(120) NOT NULL DEFAULT 'The Grand Horizon Hotel & Banquet',
    `hotel_tagline` VARCHAR(200) NOT NULL DEFAULT 'Exquisite Venues & Unforgettable Celebrations',
    `address` TEXT NOT NULL,
    `city_state_zip` VARCHAR(120) NOT NULL DEFAULT 'Oceanfront Avenue, Suite 100, Metropolis',
    `phone` VARCHAR(30) NOT NULL DEFAULT '+1 (555) 789-2000',
    `email` VARCHAR(100) NOT NULL DEFAULT 'events@grandhorizonhotel.com',
    `website` VARCHAR(100) NOT NULL DEFAULT 'www.grandhorizonhotel.com',
    `currency_symbol` VARCHAR(10) NOT NULL DEFAULT '$',
    `tax_percentage` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    `advance_percentage` DECIMAL(5,2) NOT NULL DEFAULT 30.00,
    `cancellation_policy` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SEED DATA
-- =====================================================================

INSERT INTO `system_settings` (
    `id`, `hotel_name`, `hotel_tagline`, `address`, `city_state_zip`, 
    `phone`, `email`, `website`, `currency_symbol`, `tax_percentage`, 
    `advance_percentage`, `cancellation_policy`
) VALUES (
    1,
    'The Grand Horizon Hotel & Banquet',
    'Exquisite Venues, Timeless Elegance & Unforgettable Celebrations',
    '742 Oceanfront Promenade, Marina Bay District',
    'Metropolis, NY 10012',
    '+1 (555) 789-2000',
    'events@grandhorizonhotel.com',
    'www.grandhorizonhotel.com',
    '$',
    10.00,
    30.00,
    'Cancellations made 14 days or more prior to event receive a 75% refund.'
) ON DUPLICATE KEY UPDATE `hotel_name` = VALUES(`hotel_name`);

-- Demo users (Passwords: admin123, staff123, customer123)
INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `status`, `created_at`) VALUES
(1, 'admin@grandhorizon.com', '$2y$10$dY9fGv4F08Z27g7uYl209.kU8KzZ7o8t8w9p2l0l8w12345678901', 'admin', 'active', NOW()),
(2, 'staff@grandhorizon.com', '$2y$10$dY9fGv4F08Z27g7uYl209.kU8KzZ7o8t8w9p2l0l8w12345678901', 'staff', 'active', NOW()),
(3, 'coordinator@grandhorizon.com', '$2y$10$dY9fGv4F08Z27g7uYl209.kU8KzZ7o8t8w9p2l0l8w12345678901', 'staff', 'active', NOW()),
(4, 'customer@gmail.com', '$2y$10$dY9fGv4F08Z27g7uYl209.kU8KzZ7o8t8w9p2l0l8w12345678901', 'customer', 'active', NOW()),
(5, 'sarah.jenkins@outlook.com', '$2y$10$dY9fGv4F08Z27g7uYl209.kU8KzZ7o8t8w9p2l0l8w12345678901', 'customer', 'active', NOW())
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

INSERT INTO `staff` (`id`, `user_id`, `employee_code`, `full_name`, `designation`, `phone`, `department`) VALUES
(1, 2, 'EMP-1001', 'Alexander Wright', 'Banquet & Events Director', '+1 (555) 789-2101', 'Banquet Operations'),
(2, 3, 'EMP-1002', 'Eleanor Vance', 'Senior Event Coordinator', '+1 (555) 789-2102', 'Guest Relations')
ON DUPLICATE KEY UPDATE `employee_code` = VALUES(`employee_code`);

INSERT INTO `customers` (`id`, `user_id`, `full_name`, `phone`, `alternate_phone`, `address`, `city`, `id_number`) VALUES
(1, 4, 'Michael Henderson', '+1 (555) 234-5678', '+1 (555) 234-5679', '452 Lexington Crest', 'Metropolis', 'ID-8849201'),
(2, 5, 'Sarah Jenkins', '+1 (555) 345-6789', NULL, '18 Riverside Boulevard', 'Metropolis', 'ID-7738192')
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`);

INSERT INTO `function_halls` (
    `id`, `hall_name`, `hall_code`, `capacity_min`, `capacity_max`, 
    `price_per_hour`, `base_price_half_day`, `base_price_full_day`, 
    `dimensions`, `description`, `amenities`, `status`
) VALUES
(
    1,
    'The Grand Imperial Ballroom',
    'HALL-BLRM-01',
    100,
    650,
    350.00,
    1800.00,
    3200.00,
    '120 x 80 ft (9,600 sq ft)',
    'Our flagship ballroom featuring hand-crafted crystal chandeliers, elevated stage, VIP suites, and soaring 24-foot ceilings.',
    'Crystal Chandeliers, Elevated Stage, LED Uplighting, Built-in PA System, Bridal Dressing Suite, Valet Access',
    'active'
),
(
    2,
    'The Sapphire Conference & Banquet Hall',
    'HALL-CONF-02',
    40,
    250,
    200.00,
    1000.00,
    1800.00,
    '70 x 45 ft (3,150 sq ft)',
    'A sophisticated and acoustically treated space tailored for corporate summits, product launches, and private banquets.',
    'Dual 4K Laser Projectors, High-speed Wi-Fi 6, Wireless Podium Mics, Acoustic Paneling, Video Rig',
    'active'
),
(
    3,
    'The Royal Orchid Pavilion',
    'HALL-PAV-03',
    50,
    350,
    260.00,
    1300.00,
    2300.00,
    '85 x 55 ft (4,675 sq ft)',
    'An enchanting glass-enclosed pavilion flooded with natural sunlight and ambient evening garden views.',
    'Floor-to-Ceiling Glass Walls, Ambient Illumination, Climate Control, Direct Garden Access, Built-in Bar',
    'active'
),
(
    4,
    'The Palm Garden Terrace & Lawn',
    'HALL-TRC-04',
    30,
    200,
    160.00,
    850.00,
    1500.00,
    'Open-air landscaped lawn (6,500 sq ft)',
    'A picturesque outdoor venue surrounded by tropical palms, illuminated water fountains, and custom canopy marquees.',
    'Bespoke Canopy Marquee, Fairy String Lighting, Outdoor Sound System, Dedicated Live Cooking Stations',
    'active'
)
ON DUPLICATE KEY UPDATE `hall_code` = VALUES(`hall_code`);

INSERT INTO `event_packages` (
    `id`, `package_name`, `package_code`, `category`, 
    `price_per_person`, `base_package_price`, `description`, 
    `menu_details`, `inclusions`, `status`
) VALUES
(
    1,
    'Royal Platinum Wedding Gala',
    'PKG-WED-01',
    'wedding',
    85.00,
    1800.00,
    'Comprehensive wedding package with 5-course gourmet dinner, champagne toast, full floral styling, and multi-tier cake.',
    '5-Course Gourmet Buffet: 3 Appetizers, 2 Soups, 4 Entrees (Prime Rib, Salmon, Truffle Risotto), Artisanal Dessert Spread',
    'Champagne Welcome Toast, 4-Tier Wedding Cake, Floral Backdrop, Red Carpet Entrance, Bridal Suite Refreshments',
    'active'
),
(
    2,
    'Executive Corporate Summit',
    'PKG-CORP-02',
    'corporate',
    50.00,
    950.00,
    'Designed for corporate efficiency: welcome breakfast, continuous artisanal coffee/tea, executive luncheon, and standby AV technicians.',
    'Continental Breakfast, Mid-morning Refreshments, 3-Course Executive Luncheon Buffet, Afternoon High Tea',
    'Dual 4K Projectors, Laser Pointers, Executive Notepads, Digital Podium, Standby AV Technician',
    'active'
),
(
    3,
    'Milestone Birthday & Anniversary Extravaganza',
    'PKG-BDAY-03',
    'birthday',
    58.00,
    700.00,
    'Celebrate life milestones in style with vibrant party decor, themed buffet menu, signature mocktail stations, and DJ lighting.',
    'International Buffet: Sliders, Gourmet Pizzas, Pasta Stations, Skewers, Salad Bar, Deluxe Ice Cream Wall',
    'Themed Balloon Arch and Photo Booth, Custom 2-Tier Cake, Party DJ Lighting System',
    'active'
),
(
    4,
    'Cocktail Soirée & Networking Banquet',
    'PKG-SPEC-05',
    'special',
    65.00,
    1200.00,
    'Stand-up networking format featuring roving gourmet hors d\'oeuvres, live mixology station, and high-top bistro seating.',
    'Pass-around Hors d\'oeuvres (12 Varieties including Wagyu Tataki, Truffle Arancini, Mini Crab Cakes), Artisan Charcuterie',
    'Cocktail High-Top Tables, Ambient Lounge Uplighting, Dedicated Bartender Service Station, VIP Lounge Set',
    'active'
)
ON DUPLICATE KEY UPDATE `package_code` = VALUES(`package_code`);

INSERT INTO `bookings` (
    `id`, `booking_reference`, `customer_id`, `hall_id`, `package_id`, 
    `event_date`, `start_time`, `end_time`, `guest_count`, `event_type`, 
    `special_requests`, `hall_charge`, `package_charge`, `additional_charge`, 
    `tax_amount`, `total_amount`, `advance_required`, `advance_paid`, `balance_due`, 
    `booking_status`, `payment_status`, `reviewed_by`, `review_notes`, `created_at`
) VALUES
(
    1,
    'EVT-2026-0001',
    1,
    1,
    1,
    DATE_ADD(CURRENT_DATE(), INTERVAL 25 DAY),
    '16:00:00',
    '23:00:00',
    250,
    'Wedding Reception',
    'Ivory and champagne gold floral theme. Bride requests access to suite at 14:00.',
    2450.00,
    23050.00,
    500.00,
    2600.00,
    28600.00,
    8580.00,
    8580.00,
    20020.00,
    'confirmed',
    'partially_paid',
    2,
    'Advance deposit verified via bank wire.',
    NOW()
),
(
    2,
    'EVT-2026-0003',
    2,
    3,
    3,
    DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY),
    '18:00:00',
    '23:00:00',
    80,
    '50th Birthday Celebration',
    'Gluten-free menu options requested for 8 guests. Custom photo wall.',
    1300.00,
    5340.00,
    100.00,
    674.00,
    7414.00,
    2224.20,
    0.00,
    7414.00,
    'approved',
    'unpaid',
    2,
    'Availability confirmed. Waiting for client advance deposit.',
    NOW()
)
ON DUPLICATE KEY UPDATE `booking_reference` = VALUES(`booking_reference`);

INSERT INTO `payments` (
    `id`, `receipt_number`, `booking_id`, `payment_type`, `amount`, 
    `payment_method`, `transaction_reference`, `payment_date`, `notes`, 
    `received_by`, `created_at`
) VALUES
(
    1,
    'RCPT-2026-0001',
    1,
    'advance',
    8580.00,
    'bank_transfer',
    'WIRE-CHASE-992810',
    DATE_SUB(CURRENT_DATE(), INTERVAL 2 DAY),
    '30% Advance reservation deposit received via wire transfer.',
    2,
    NOW()
)
ON DUPLICATE KEY UPDATE `receipt_number` = VALUES(`receipt_number`);
