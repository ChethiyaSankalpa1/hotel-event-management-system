-- =====================================================================
-- Hotel Event Management System - Realistic Seed Data
-- =====================================================================

USE `hotel_event_db`;

-- ---------------------------------------------------------------------
-- 1. SYSTEM SETTINGS
-- ---------------------------------------------------------------------
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
    'LKR',
    10.00,
    30.00,
    'Cancellations made 14 days or more prior to the event date will receive a 75% refund of the advance deposit. Cancellations within 14 days of the event date forfeit the advance deposit.'
) ON DUPLICATE KEY UPDATE `hotel_name` = VALUES(`hotel_name`);

-- ---------------------------------------------------------------------
-- 2. USERS
-- Password default for all demo accounts:
-- admin@grandhorizon.com   => admin123
-- staff@grandhorizon.com   => staff123
-- coordinator@grandhorizon.com => staff123
-- customer@gmail.com       => customer123
-- sarah.jenkins@outlook.com => customer123
-- ---------------------------------------------------------------------
INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `status`, `created_at`) VALUES
(1, 'admin@grandhorizon.com', '$2y$10$dY9fGv4F08Z27g7uYl209.kU8KzZ7o8t8w9p2l0l8w12345678901', 'admin', 'active', NOW()),
(2, 'staff@grandhorizon.com', '$2y$10$dY9fGv4F08Z27g7uYl209.kU8KzZ7o8t8w9p2l0l8w12345678901', 'staff', 'active', NOW()),
(3, 'coordinator@grandhorizon.com', '$2y$10$dY9fGv4F08Z27g7uYl209.kU8KzZ7o8t8w9p2l0l8w12345678901', 'staff', 'active', NOW()),
(4, 'customer@gmail.com', '$2y$10$dY9fGv4F08Z27g7uYl209.kU8KzZ7o8t8w9p2l0l8w12345678901', 'customer', 'active', NOW()),
(5, 'sarah.jenkins@outlook.com', '$2y$10$dY9fGv4F08Z27g7uYl209.kU8KzZ7o8t8w9p2l0l8w12345678901', 'customer', 'active', NOW()),
(6, 'david.miller@apextech.com', '$2y$10$dY9fGv4F08Z27g7uYl209.kU8KzZ7o8t8w9p2l0l8w12345678901', 'customer', 'active', NOW())
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- ---------------------------------------------------------------------
-- 3. STAFF PROFILES
-- ---------------------------------------------------------------------
INSERT INTO `staff` (`id`, `user_id`, `employee_code`, `full_name`, `designation`, `phone`, `department`) VALUES
(1, 2, 'EMP-1001', 'Alexander Wright', 'Banquet & Events Director', '+1 (555) 789-2101', 'Banquet Operations'),
(2, 3, 'EMP-1002', 'Eleanor Vance', 'Senior Event Coordinator', '+1 (555) 789-2102', 'Guest Relations')
ON DUPLICATE KEY UPDATE `employee_code` = VALUES(`employee_code`);

-- ---------------------------------------------------------------------
-- 4. CUSTOMER PROFILES
-- ---------------------------------------------------------------------
INSERT INTO `customers` (`id`, `user_id`, `full_name`, `phone`, `alternate_phone`, `address`, `city`, `id_number`) VALUES
(1, 4, 'Michael Henderson', '+1 (555) 234-5678', '+1 (555) 234-5679', '452 Lexington Crest', 'Metropolis', 'ID-8849201'),
(2, 5, 'Sarah Jenkins', '+1 (555) 345-6789', NULL, '18 Riverside Boulevard', 'Metropolis', 'ID-7738192'),
(3, 6, 'David Miller', '+1 (555) 456-7890', '+1 (555) 456-7899', 'Apex Tech Towers, 14th Floor', 'New York', 'CORP-TX-9901')
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`);

-- ---------------------------------------------------------------------
-- 5. FUNCTION HALLS
-- ---------------------------------------------------------------------
INSERT INTO `function_halls` (
    `id`, `hall_name`, `hall_code`, `capacity_min`, `capacity_max`, 
    `price_per_hour`, `base_price_half_day`, `base_price_full_day`, 
    `dimensions`, `description`, `amenities`, `image_path`, `status`
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
    'Our flagship ballroom featuring hand-crafted crystal chandeliers, mahogany wainscoting, a modular theatrical stage, dedicated VIP dressing suites, and a soaring 24-foot ceiling. Ideal for royal weddings, gala dinners, and landmark summits.',
    'Crystal Chandeliers, Elevated Stage, LED Uplighting, Built-in PA System, Bridal Dressing Suite, Dedicated Restrooms, Valet Parking Access, Dance Floor',
    'assets/images/halls/imperial_ballroom.jpg',
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
    'A sophisticated and acoustically treated space tailored for corporate conferences, annual general meetings, product launches, and private banquets. Equipped with dual 4K laser projectors, wireless mic systems, and ergonomic seating.',
    'Dual 4K Laser Projectors, High-speed Wi-Fi 6, Wireless Podium Mics, Acoustic Wall Paneling, Video Conferencing Rig, Breakout Foyer',
    'assets/images/halls/sapphire_hall.jpg',
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
    'An enchanting glass-enclosed pavilion flooded with natural sunlight and ambient evening garden views. Perfect for engagement ceremonies, milestone anniversaries, and high-profile social galas.',
    'Floor-to-Ceiling Glass Walls, Ambient Garden Illumination, Climate Control, Direct Garden Access, Built-in Bar Counter, Acoustic Sound Reinforcement',
    'assets/images/halls/royal_orchid.jpg',
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
    'A picturesque outdoor venue surrounded by tropical palms, illuminated water fountains, and custom canopy marquees. The premier destination for sunset cocktail parties, relaxed birthday celebrations, and open-air receptions.',
    'Bespoke Canopy Marquee, Fairy String Lighting, Outdoor Sound System, Dedicated Live Cooking Stations, Fountain Water Feature, Restroom Trailers',
    'assets/images/halls/garden_terrace.jpg',
    'active'
)
ON DUPLICATE KEY UPDATE `hall_code` = VALUES(`hall_code`);

-- ---------------------------------------------------------------------
-- 6. EVENT PACKAGES
-- ---------------------------------------------------------------------
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
    'Our most comprehensive wedding package designed for regal elegance. Includes a 5-course gourmet dinner, champagne toast, full floral styling, stage backdrop, and multi-tier celebratory cake.',
    '5-Course Gourmet Buffet or Plated Service: 3 Appetizers, 2 Soups, 4 Entrees (Prime Rib, Pan-Seared Salmon, Herb Chicken, Truffle Risotto), Artisanal Dessert Spread, Late-night Savory Snacks',
    'Champagne Welcome Toast, 4-Tier Thematic Wedding Cake, Floral Stage Backdrop & Centerpieces, Red Carpet Entrance, Bridal Suite Refreshments, Dedicated Master of Ceremonies Audio System',
    'active'
),
(
    2,
    'Executive Corporate Summit',
    'PKG-CORP-02',
    'corporate',
    50.00,
    950.00,
    'A seamless package designed for corporate efficiency. Includes welcome breakfast, continuous artisanal coffee and tea service, executive luncheon buffet, and high-end AV technicians on standby.',
    'Continental Breakfast (Pastries, Parfaits, Fresh Fruits), Mid-morning Refreshment, 3-Course Executive Hot Lunch Buffet, Afternoon Tea with Macarons and Gourmet Canapes',
    'Dual 4K Projectors with HDMI/Wireless Connect, Laser Pointers, Executive Notepads and Pens, Digital Podium, Standby AV Technician, High-Speed Dedicated Network Access',
    'active'
),
(
    3,
    'Milestone Birthday & Anniversary Extravaganza',
    'PKG-BDAY-03',
    'birthday',
    58.00,
    700.00,
    'Celebrate life milestones in style with vibrant party decor, themed buffet menu, signature mocktail stations, DJ lighting rig, and personalized celebratory cake.',
    'Vibrant International Buffet: Sliders, Gourmet Pizzas, Pasta Stations, Grilled Skewers, Salad Bar, Deluxe Ice Cream and Donut Wall, Signature Punch Bowl',
    'Themed Balloon Arch and Photo Booth with Props, Custom 2-Tier Birthday/Anniversary Cake, Party DJ Lighting System, Background Playlist Integration',
    'active'
),
(
    4,
    'Silver Jubilee & Heritage Banquet',
    'PKG-ANNIV-04',
    'anniversary',
    72.00,
    1100.00,
    'Sophisticated and intimate celebration package with fine wine pairing, candlelit centerpiece decor, string quartet acoustics, and customized memorial video projection.',
    '4-Course Silver Plated Dinner: Smoked Duck Breast, Lobster Bisque, Filet Mignon or Chilean Sea Bass, Chocolate Lava Cake with Berry Coulis',
    'Custom Memory Wall Projection, Crystal Candelabra Centerpieces, Personalized Keepsake Menus, Soft Jazz/Classical Background Audio, Complimentary Suite for Honorees',
    'active'
),
(
    5,
    'Cocktail Soirée & Networking Banquet',
    'PKG-SPEC-05',
    'special',
    65.00,
    1200.00,
    'A lively stand-up networking format featuring roving gourmet hors d\'oeuvres, live mixology station, high-top bistro seating, and ambient lounge lighting.',
    'Pass-around Hors d\'oeuvres (12 Varieties including Wagyu Beef Tataki, Tuna Tartare, Truffle Arancini, Mini Crab Cakes), Artisan Charcuterie & Cheese Display',
    'Cocktail High-Top Tables with Silk Sashes, Ambient Lounge Uplighting, Dedicated Bartender Service Station, DJ Sound Console, VIP Lounge Furniture Set',
    'active'
)
ON DUPLICATE KEY UPDATE `package_code` = VALUES(`package_code`);

-- ---------------------------------------------------------------------
-- 7. BOOKINGS
-- ---------------------------------------------------------------------
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
    1, -- Michael Henderson
    1, -- The Grand Imperial Ballroom
    1, -- Royal Platinum Wedding Gala
    DATE_ADD(CURRENT_DATE(), INTERVAL 25 DAY),
    '16:00:00',
    '23:00:00',
    250,
    'Wedding Reception',
    'Ivory and champagne gold floral theme. Bride requests access to suite at 14:00.',
    2450.00,
    23050.00, -- (85 * 250) + 1800 base
    500.00,   -- Extra sound setup
    2600.00,  -- 10% tax
    28600.00,
    8580.00,  -- 30% advance
    8580.00,  -- Paid
    20020.00, -- Remaining balance
    'confirmed',
    'partially_paid',
    2, -- Alexander Wright
    'Client provided wedding schedule. Advance deposit verified via bank wire.',
    NOW()
),
(
    2,
    'EVT-2026-0002',
    3, -- David Miller (Apex Tech)
    2, -- Sapphire Conference Hall
    2, -- Executive Corporate Summit
    DATE_ADD(CURRENT_DATE(), INTERVAL 14 DAY),
    '08:30:00',
    '17:30:00',
    120,
    'Corporate Annual Conference',
    'Requires 3 handheld mics, 2 lavalier mics, and high-bandwidth livestream connectivity.',
    1800.00,
    6950.00, -- (50 * 120) + 950 base
    250.00,  -- Tech support fee
    900.00,  -- 10% tax
    9900.00,
    2970.00,
    9900.00, -- Full payment settled
    0.00,    -- Balance zero
    'confirmed',
    'fully_paid',
    2,
    'Corporate purchase order approved. All accounts settled in full.',
    NOW()
),
(
    3,
    'EVT-2026-0003',
    2, -- Sarah Jenkins
    3, -- Royal Orchid Pavilion
    3, -- Milestone Birthday & Anniversary
    DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY),
    '18:00:00',
    '23:00:00',
    80,
    '50th Birthday Celebration',
    'Gluten-free menu options requested for 8 guests. Custom photo wall near entrance.',
    1300.00,
    5340.00, -- (58 * 80) + 700 base
    100.00,
    674.00,
    7414.00,
    2224.20,
    0.00,
    7414.00,
    'approved',
    'unpaid',
    3, -- Eleanor Vance
    'Availability confirmed and hall assigned. Waiting for customer to remit advance deposit.',
    NOW()
),
(
    4,
    'EVT-2026-0004',
    1, -- Michael Henderson
    4, -- Palm Garden Terrace & Lawn
    5, -- Cocktail Soirée
    DATE_ADD(CURRENT_DATE(), INTERVAL 35 DAY),
    '17:00:00',
    '22:00:00',
    60,
    'Private Anniversary Cocktail Gathering',
    'Soft jazz acoustic duo setup. Request fire pit activation if weather permits.',
    800.00,
    5100.00, -- (65 * 60) + 1200 base
    0.00,
    590.00,
    6490.00,
    1947.00,
    0.00,
    6490.00,
    'pending',
    'unpaid',
    NULL,
    NULL,
    NOW()
),
(
    5,
    'EVT-2026-0005',
    2, -- Sarah Jenkins
    2, -- Sapphire Conference Hall
    2, -- Corporate Summit
    DATE_SUB(CURRENT_DATE(), INTERVAL 12 DAY),
    '09:00:00',
    '15:00:00',
    50,
    'Quarterly Strategy Workshop',
    'Standard setup completed.',
    1200.00,
    3450.00,
    0.00,
    465.00,
    5115.00,
    1534.50,
    5115.00,
    0.00,
    'completed',
    'fully_paid',
    2,
    'Event executed successfully. Client satisfaction rating 5/5.',
    DATE_SUB(NOW(), INTERVAL 25 DAY)
)
ON DUPLICATE KEY UPDATE `booking_reference` = VALUES(`booking_reference`);

-- ---------------------------------------------------------------------
-- 8. PAYMENTS
-- ---------------------------------------------------------------------
INSERT INTO `payments` (
    `id`, `receipt_number`, `booking_id`, `payment_type`, `amount`, 
    `payment_method`, `transaction_reference`, `payment_date`, `notes`, 
    `received_by`, `created_at`
) VALUES
(
    1,
    'RCPT-2026-0001',
    1, -- EVT-2026-0001
    'advance',
    8580.00,
    'bank_transfer',
    'WIRE-CHASE-992810',
    DATE_SUB(CURRENT_DATE(), INTERVAL 2 DAY),
    '30% Advance reservation deposit received via wire transfer.',
    2, -- Alexander Wright
    NOW()
),
(
    2,
    'RCPT-2026-0002',
    2, -- EVT-2026-0002
    'advance',
    2970.00,
    'bank_transfer',
    'CORP-PO-88271',
    DATE_SUB(CURRENT_DATE(), INTERVAL 5 DAY),
    'Advance payment confirmed under corporate master billing.',
    2,
    NOW()
),
(
    3,
    'RCPT-2026-0003',
    2, -- EVT-2026-0002
    'balance_settlement',
    6930.00,
    'credit_card',
    'AUTH-CC-492019',
    DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY),
    'Remaining balance settled in full via Corporate Amex.',
    2,
    NOW()
),
(
    4,
    'RCPT-2026-0004',
    5, -- EVT-2026-0005 (Completed)
    'full_payment',
    5115.00,
    'bank_transfer',
    'TXN-90281-HSBC',
    DATE_SUB(CURRENT_DATE(), INTERVAL 15 DAY),
    'Full settlement for Strategy Workshop.',
    2,
    DATE_SUB(NOW(), INTERVAL 15 DAY)
)
ON DUPLICATE KEY UPDATE `receipt_number` = VALUES(`receipt_number`);
