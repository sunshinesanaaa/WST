-- =====================================================================
--  Style Haven Salon — Style Sync
--  IT 004 Web Systems and Technologies | Project Part 4 | Group 3
--  ---------------------------------------------------------------------
--  Database : stylehaven_db
--  Engine   : MySQL / MariaDB (XAMPP, WAMP, MAMP, Laragon)
--  Charset  : utf8mb4 (supports the ₱ sign and Filipino names)
--
--  HOW TO IMPORT IN phpMyAdmin
--    1. Start Apache and MySQL in the XAMPP control panel.
--    2. Open http://localhost/phpmyadmin
--    3. Click the "Import" tab  ->  "Choose file"  ->  select this file
--       (database/stylehaven.sql)  ->  press "Import".
--    The script creates the database, all six tables and the demo records.
-- =====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";

-- ---------------------------------------------------------------------
--  Database
-- ---------------------------------------------------------------------
DROP DATABASE IF EXISTS `stylehaven_db`;
CREATE DATABASE `stylehaven_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `stylehaven_db`;

-- ---------------------------------------------------------------------
--  Table 1 — users            (User Maintenance / log-in accounts)
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `user_id`       INT(11)      NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'bcrypt hash produced by password_hash()',
  `full_name`     VARCHAR(120) NOT NULL,
  `email`         VARCHAR(120) NOT NULL,
  `contact_no`    VARCHAR(30)  DEFAULT NULL,
  `role`          ENUM('Administrator','Front Desk','Stylist','Customer') NOT NULL DEFAULT 'Customer',
  `status`        ENUM('Active','Inactive')                               NOT NULL DEFAULT 'Active',
  `last_login`    DATETIME     DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Table 2 — services         (File Maintenance — service menu)
-- ---------------------------------------------------------------------
CREATE TABLE `services` (
  `service_id`   INT(11)       NOT NULL AUTO_INCREMENT,
  `service_code` VARCHAR(20)   NOT NULL,
  `service_name` VARCHAR(120)  NOT NULL,
  `category`     ENUM('Hair','Nails','Skin','Makeup','Packages') NOT NULL DEFAULT 'Hair',
  `price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `duration_min` INT(11)       NOT NULL DEFAULT 30 COMMENT 'service length in minutes',
  `description`  VARCHAR(255)  DEFAULT NULL,
  `status`       ENUM('Available','Unavailable') NOT NULL DEFAULT 'Available',
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_id`),
  UNIQUE KEY `uq_services_code` (`service_code`),
  KEY `idx_services_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Table 3 — products         (File Maintenance — retail product line)
-- ---------------------------------------------------------------------
CREATE TABLE `products` (
  `product_id`   INT(11)       NOT NULL AUTO_INCREMENT,
  `product_code` VARCHAR(20)   NOT NULL,
  `product_name` VARCHAR(120)  NOT NULL,
  `brand`        VARCHAR(80)   DEFAULT NULL,
  `category`     ENUM('Hair Care','Nail Care','Skin Care','Makeup','Tools') NOT NULL DEFAULT 'Hair Care',
  `price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock_qty`    INT(11)       NOT NULL DEFAULT 0,
  `description`  VARCHAR(255)  DEFAULT NULL,
  `status`       ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `uq_products_code` (`product_code`),
  KEY `idx_products_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Table 4 — stylists         (File Maintenance — staff / stylist file)
-- ---------------------------------------------------------------------
CREATE TABLE `stylists` (
  `stylist_id`       INT(11)      NOT NULL AUTO_INCREMENT,
  `stylist_code`     VARCHAR(20)  NOT NULL,
  `full_name`        VARCHAR(120) NOT NULL,
  `specialization`   ENUM('Hair','Nails','Skin','Makeup','Barber & Grooming','All-Around') NOT NULL DEFAULT 'Hair',
  `contact_no`       VARCHAR(30)  DEFAULT NULL,
  `years_experience` INT(11)      NOT NULL DEFAULT 0,
  `status`           ENUM('Available','On Leave','Inactive') NOT NULL DEFAULT 'Available',
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stylist_id`),
  UNIQUE KEY `uq_stylists_code` (`stylist_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Table 5 — appointments     (records managed by the front desk)
--    Two foreign keys link this child table to the services and
--    stylists master files: a service or stylist that is still used
--    by an appointment cannot be deleted (ON DELETE RESTRICT).
-- ---------------------------------------------------------------------
CREATE TABLE `appointments` (
  `appointment_id` INT(11)     NOT NULL AUTO_INCREMENT,
  `ref_no`         VARCHAR(12) NOT NULL,
  `customer_name`  VARCHAR(120) NOT NULL,
  `contact_no`     VARCHAR(30)  DEFAULT NULL,
  `email`          VARCHAR(120) DEFAULT NULL,
  `service_id`     INT(11)     NOT NULL,
  `stylist_id`     INT(11)     NOT NULL,
  `appt_date`      DATE        NOT NULL,
  `appt_time`      TIME        NOT NULL,
  `status`         ENUM('Pending','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `notes`          VARCHAR(255) DEFAULT NULL,
  `created_at`     DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME    DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`appointment_id`),
  UNIQUE KEY `uq_appointments_ref` (`ref_no`),
  -- Double booking is checked by the application (a cancelled slot must be
  -- released immediately), so this is a lookup index rather than a UNIQUE key.
  KEY `idx_appointments_slot` (`stylist_id`, `appt_date`, `appt_time`),
  KEY `idx_appointments_date` (`appt_date`),
  KEY `idx_appointments_service` (`service_id`),
  CONSTRAINT `fk_appt_service` FOREIGN KEY (`service_id`)
     REFERENCES `services` (`service_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_appt_stylist` FOREIGN KEY (`stylist_id`)
     REFERENCES `stylists` (`stylist_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Table 6 — activity_log     (audit trail of every add / edit / delete)
-- ---------------------------------------------------------------------
CREATE TABLE `activity_log` (
  `log_id`     INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)      DEFAULT NULL,
  `username`   VARCHAR(50)  DEFAULT NULL COMMENT 'kept even if the account is deleted',
  `action`     ENUM('INSERT','UPDATE','DELETE','LOGIN','LOGOUT') NOT NULL,
  `table_name` VARCHAR(40)  NOT NULL,
  `record_id`  INT(11)      DEFAULT NULL,
  `details`    VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_log_created` (`created_at`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`)
     REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  DEMO DATA
--  Passwords are stored as bcrypt hashes. Default log-in accounts:
--     admin      / stylehaven2026   (Administrator)
--     frontdesk  / salon123         (Front Desk)
--     ana.stylist/ stylist123       (Stylist)
--     mariel     / customer123      (Customer)
-- =====================================================================
INSERT INTO `users`
  (`username`, `password_hash`, `full_name`, `email`, `contact_no`, `role`, `status`) VALUES
('admin',       '$2y$10$RB2Zec15xYtKsGNNJ/sXW.tbI76aDNDevMxN0VQzW9ln66d9uO0ua', 'Renzo V. Racasa',     'admin@stylehavensalon.ph',      '0917 100 0001', 'Administrator', 'Active'),
('frontdesk',   '$2y$10$nbqRuwyBMAt79UKEYrJuC.P0iX/OKC/rdPT8qzP7XLlwrbwQf8P8.', 'Enrico Jose F. Abeson','frontdesk@stylehavensalon.ph',  '0917 100 0002', 'Front Desk',    'Active'),
('ana.stylist', '$2y$10$zkIIWJWx2DNi6FslPElOKOr4ewrEb4y07gslYO7bi6HBCF2S6CFg6', 'Ana Villanueva',      'ana@stylehavensalon.ph',        '0917 100 0003', 'Stylist',       'Active'),
('jacob.staff', '$2y$10$zkIIWJWx2DNi6FslPElOKOr4ewrEb4y07gslYO7bi6HBCF2S6CFg6', 'Jacob C. Guariña',    'jacob@stylehavensalon.ph',      '0917 100 0004', 'Stylist',       'Active'),
('mariel',      '$2y$10$gvEzz3RZyYBrPu.3o1YQ3uJJB.malZBJpVuNuq96TXTcVjm/YQfka', 'Mariel Bautista',     'mariel@example.com',            '0917 555 2210', 'Customer',      'Active'),
('kevin',       '$2y$10$gvEzz3RZyYBrPu.3o1YQ3uJJB.malZBJpVuNuq96TXTcVjm/YQfka', 'Kevin Ramos',         'kevin@example.com',             '0918 442 7781', 'Customer',      'Inactive');

INSERT INTO `services`
  (`service_code`, `service_name`, `category`, `price`, `duration_min`, `description`, `status`) VALUES
('SV-HC01', 'Signature Haircut',      'Hair',     350.00,  45, 'Consultation, wash, cut and blow-dry finish.',   'Available'),
('SV-HC02', 'Hair Color (Global)',    'Hair',    1500.00, 120, 'Ammonia-free full-head colour, custom blended.', 'Available'),
('SV-HC03', 'Keratin Treatment',      'Hair',    2500.00, 180, 'Smoothing treatment for frizz control.',         'Available'),
('SV-NL01', 'Gel Polish',             'Nails',    500.00,  60, 'Long-wear gel colour for hands or feet.',        'Available'),
('SV-NL02', 'Classic Manicure',       'Nails',    200.00,  40, 'Shape, cuticle care and hand massage.',          'Available'),
('SV-SK01', 'Classic Facial',         'Skin',     700.00,  60, 'Cleanse, steam, extraction and calming mask.',   'Available'),
('SV-SK02', 'Diamond Peel',           'Skin',     900.00,  60, 'Exfoliating treatment for brighter skin.',       'Available'),
('SV-MU01', 'Party Makeup',           'Makeup',   800.00,  60, 'Event-ready makeover with skin prep and lashes.', 'Available'),
('SV-MU02', 'Bridal Makeup',          'Makeup',  3500.00, 150, 'Long-wear bridal look with a trial session.',    'Available'),
('SV-PK01', 'Bridal Package',         'Packages',5000.00, 240, 'Bridal hair and makeup plus one companion.',     'Available'),
('SV-PK02', 'Pamper Package',         'Packages',2000.00, 180, 'Hair spa, facial and manicure in one visit.',     'Unavailable');

INSERT INTO `products`
  (`product_code`, `product_name`, `brand`, `category`, `price`, `stock_qty`, `description`, `status`) VALUES
('PR-001', 'Argan Repair Shampoo 500 ml', 'Style Haven Pro', 'Hair Care',  649.00, 24, 'Sulphate-free shampoo for coloured hair.', 'Active'),
('PR-002', 'Argan Repair Conditioner 500 ml','Style Haven Pro','Hair Care', 649.00, 18, 'Deep-conditioning partner of the repair shampoo.', 'Active'),
('PR-003', 'Heat Shield Mist 150 ml',     'Style Haven Pro', 'Hair Care',  399.00, 30, 'Thermal protection up to 220 degrees C.', 'Active'),
('PR-004', 'Gel Top Coat 15 ml',          'Luxe Nails',      'Nail Care',  320.00, 40, 'High-shine no-wipe gel top coat.',        'Active'),
('PR-005', 'Cuticle Oil Pen',             'Luxe Nails',      'Nail Care',  180.00, 55, 'Jojoba-based cuticle oil in a pen applicator.', 'Active'),
('PR-006', 'Vitamin C Brightening Serum', 'Derma Glow',      'Skin Care',  899.00, 12, '10% vitamin C serum for dull skin.',      'Active'),
('PR-007', 'Hydrating Sheet Mask',        'Derma Glow',      'Skin Care',   95.00, 80, 'Single-use mask with hyaluronic acid.',   'Active'),
('PR-008', 'Matte Lip Crayon',            'Style Haven Cosmetics','Makeup', 450.00, 26, 'Transfer-resistant matte lip colour.',    'Active'),
('PR-009', 'Ceramic Round Brush 43 mm',   'Salon Basics',    'Tools',      520.00,  9, 'Ionic ceramic barrel for smooth blow-dries.', 'Active'),
('PR-010', 'Nail Art Brush Set',          'Salon Basics',    'Tools',      380.00,  0, 'Five-piece detailing brush set.',         'Inactive');

INSERT INTO `stylists`
  (`stylist_code`, `full_name`, `specialization`, `contact_no`, `years_experience`, `status`) VALUES
('ST-001', 'Ana Villanueva',  'Hair',              '0917 200 0001', 9, 'Available'),
('ST-002', 'Ben Cruz',        'Barber & Grooming', '0917 200 0002', 7, 'Available'),
('ST-003', 'Clara Domingo',   'Nails',             '0917 200 0003', 6, 'Available'),
('ST-004', 'Dana Reyes',      'Skin',              '0917 200 0004', 5, 'Available'),
('ST-005', 'Elle Santos',     'Makeup',            '0917 200 0005', 8, 'On Leave');

INSERT INTO `appointments`
  (`ref_no`, `customer_name`, `contact_no`, `email`, `service_id`, `stylist_id`, `appt_date`, `appt_time`, `status`, `notes`) VALUES
('SH-0001', 'Mariel Bautista', '0917 555 2210', 'mariel@example.com', 2, 1, CURDATE() + INTERVAL 1 DAY, '10:00:00', 'Confirmed', 'Warm chestnut tone'),
('SH-0002', 'Kevin Ramos',     '0918 442 7781', 'kevin@example.com',  1, 2, CURDATE() + INTERVAL 1 DAY, '13:30:00', 'Pending',   'Fade plus beard trim'),
('SH-0003', 'Sofia Lim',       '0920 331 9902', 'sofia@example.com',  4, 3, CURDATE() + INTERVAL 2 DAY, '15:00:00', 'Confirmed', 'Nude French tips'),
('SH-0004', 'Elena Cruz',      '0919 220 1145', 'elena@example.com', 10, 4, CURDATE(),                  '09:30:00', 'Completed', 'Bridal package trial'),
('SH-0005', 'Nadine Bautista', '0916 774 3321', 'nadine@example.com', 7, 1, CURDATE(),                 '11:00:00', 'Cancelled', 'Client rescheduled');

-- The audit trail starts empty; every add, edit and delete made through
-- the admin pages is appended here automatically.
