-- BoviLogic Database Schema
-- MySQL 5.7+ compatible
-- Run this once on your cPanel MySQL database

SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- ─── USERS ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`         CHAR(36) NOT NULL UNIQUE,
  `name`         VARCHAR(100) NOT NULL,
  `email`        VARCHAR(150) NOT NULL UNIQUE,
  `password`     VARCHAR(255) NOT NULL,
  `role`         ENUM('super_admin','view_user') NOT NULL DEFAULT 'view_user',
  `language`     CHAR(2) NOT NULL DEFAULT 'en',
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `last_login`   DATETIME DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── FARMS ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `farms` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`         CHAR(36) NOT NULL UNIQUE,
  `name`         VARCHAR(100) NOT NULL,
  `location`     VARCHAR(200) DEFAULT NULL,
  `notes`        TEXT DEFAULT NULL,
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `created_by`   INT UNSIGNED NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CAMPS ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `camps` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`         CHAR(36) NOT NULL UNIQUE,
  `farm_id`      INT UNSIGNED NOT NULL,
  `name`         VARCHAR(100) NOT NULL,
  `size_ha`      DECIMAL(10,2) DEFAULT NULL,
  `notes`        TEXT DEFAULT NULL,
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `created_by`   INT UNSIGNED NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`farm_id`) REFERENCES `farms`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── HERDS ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `herds` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`             CHAR(36) NOT NULL UNIQUE,
  `farm_id`          INT UNSIGNED NOT NULL,
  `camp_id`          INT UNSIGNED DEFAULT NULL,
  `name`             VARCHAR(100) NOT NULL,
  `color`            VARCHAR(20) DEFAULT '#4CAF50',
  `breeding_bull_id` INT UNSIGNED DEFAULT NULL,
  `breeding_start`   DATE DEFAULT NULL,
  `breeding_end`     DATE DEFAULT NULL,
  `notes`            TEXT DEFAULT NULL,
  `is_active`        TINYINT(1) NOT NULL DEFAULT 1,
  `created_by`       INT UNSIGNED NOT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`farm_id`) REFERENCES `farms`(`id`),
  FOREIGN KEY (`camp_id`) REFERENCES `camps`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ANIMALS ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `animals` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`             CHAR(36) NOT NULL UNIQUE,
  `ear_tag`          VARCHAR(30) NOT NULL,
  `rfid`             VARCHAR(30) DEFAULT NULL,
  `breed`            VARCHAR(50) DEFAULT NULL,
  `sex`              ENUM('male','female') NOT NULL,
  `dob`              DATE DEFAULT NULL,
  `farm_id`          INT UNSIGNED DEFAULT NULL,
  `herd_id`          INT UNSIGNED DEFAULT NULL,
  `mother_id`        INT UNSIGNED DEFAULT NULL,
  `father_id`        INT UNSIGNED DEFAULT NULL,
  `category`         ENUM('breeding_bull','cow','calf','open_heifer','heifer','weaner','steer','ox') NOT NULL DEFAULT 'calf',
  `breeding_status`  ENUM('open','pregnant','calved') DEFAULT 'open',
  `animal_status`    ENUM('active','sold','dead') NOT NULL DEFAULT 'active',
  `last_calving_date` DATE DEFAULT NULL,
  `avg_calf_interval` DECIMAL(6,2) DEFAULT NULL,
  `comments`         TEXT DEFAULT NULL,
  `created_by`       INT UNSIGNED NOT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`farm_id`) REFERENCES `farms`(`id`),
  FOREIGN KEY (`herd_id`) REFERENCES `herds`(`id`),
  FOREIGN KEY (`mother_id`) REFERENCES `animals`(`id`),
  FOREIGN KEY (`father_id`) REFERENCES `animals`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
  UNIQUE KEY `ear_tag_unique` (`ear_tag`),
  INDEX `idx_animal_status` (`animal_status`),
  INDEX `idx_animal_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add breeding_bull FK after animals table exists
ALTER TABLE `herds` ADD CONSTRAINT `fk_herds_bull` FOREIGN KEY (`breeding_bull_id`) REFERENCES `animals`(`id`);

-- ─── WEIGHTS ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `weights` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`         CHAR(36) NOT NULL UNIQUE,
  `animal_id`    INT UNSIGNED NOT NULL,
  `weight_kg`    DECIMAL(7,2) NOT NULL,
  `weigh_date`   DATE NOT NULL,
  `notes`        TEXT DEFAULT NULL,
  `created_by`   INT UNSIGNED NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`animal_id`) REFERENCES `animals`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
  INDEX `idx_weight_animal` (`animal_id`, `weigh_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── HERD VACCINATION PROGRAMS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `vaccinations` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`            CHAR(36) NOT NULL UNIQUE,
  `herd_id`         INT UNSIGNED DEFAULT NULL,
  `animal_id`       INT UNSIGNED DEFAULT NULL,
  `product`         VARCHAR(100) NOT NULL,
  `dosage`          VARCHAR(50) DEFAULT NULL,
  `due_date`        DATE NOT NULL,
  `completed`       TINYINT(1) NOT NULL DEFAULT 0,
  `completion_date` DATE DEFAULT NULL,
  `notes`           TEXT DEFAULT NULL,
  `created_by`      INT UNSIGNED NOT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`herd_id`) REFERENCES `herds`(`id`),
  FOREIGN KEY (`animal_id`) REFERENCES `animals`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
  INDEX `idx_vacc_due` (`due_date`, `completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── TREATMENTS ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `treatments` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`         CHAR(36) NOT NULL UNIQUE,
  `herd_id`      INT UNSIGNED DEFAULT NULL,
  `animal_id`    INT UNSIGNED DEFAULT NULL,
  `product`      VARCHAR(100) NOT NULL,
  `dosage`       VARCHAR(50) DEFAULT NULL,
  `treat_date`   DATE NOT NULL,
  `notes`        TEXT DEFAULT NULL,
  `created_by`   INT UNSIGNED NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`herd_id`) REFERENCES `herds`(`id`),
  FOREIGN KEY (`animal_id`) REFERENCES `animals`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── EVENTS ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `events` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`         CHAR(36) NOT NULL UNIQUE,
  `herd_id`      INT UNSIGNED DEFAULT NULL,
  `animal_id`    INT UNSIGNED DEFAULT NULL,
  `event_type`   VARCHAR(50) NOT NULL,
  `event_date`   DATE NOT NULL,
  `notes`        TEXT DEFAULT NULL,
  `created_by`   INT UNSIGNED NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`herd_id`) REFERENCES `herds`(`id`),
  FOREIGN KEY (`animal_id`) REFERENCES `animals`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── SALES ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sales` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`         CHAR(36) NOT NULL UNIQUE,
  `animal_id`    INT UNSIGNED NOT NULL,
  `sale_date`    DATE NOT NULL,
  `price`        DECIMAL(12,2) NOT NULL DEFAULT 0,
  `buyer`        VARCHAR(100) DEFAULT NULL,
  `weight_kg`    DECIMAL(7,2) DEFAULT NULL,
  `reason`       VARCHAR(200) DEFAULT NULL,
  `notes`        TEXT DEFAULT NULL,
  `created_by`   INT UNSIGNED NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`animal_id`) REFERENCES `animals`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── PURCHASES ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `purchases` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`         CHAR(36) NOT NULL UNIQUE,
  `animal_id`    INT UNSIGNED NOT NULL,
  `purchase_date` DATE NOT NULL,
  `price`        DECIMAL(12,2) NOT NULL DEFAULT 0,
  `seller`       VARCHAR(100) DEFAULT NULL,
  `notes`        TEXT DEFAULT NULL,
  `created_by`   INT UNSIGNED NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`animal_id`) REFERENCES `animals`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── MORTALITY ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `mortality` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`         CHAR(36) NOT NULL UNIQUE,
  `animal_id`    INT UNSIGNED NOT NULL,
  `death_date`   DATE NOT NULL,
  `cause`        VARCHAR(200) DEFAULT NULL,
  `notes`        TEXT DEFAULT NULL,
  `created_by`   INT UNSIGNED NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`animal_id`) REFERENCES `animals`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CALVING HISTORY ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `calving` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`         CHAR(36) NOT NULL UNIQUE,
  `dam_id`       INT UNSIGNED NOT NULL,
  `calf_id`      INT UNSIGNED DEFAULT NULL,
  `calving_date` DATE NOT NULL,
  `notes`        TEXT DEFAULT NULL,
  `created_by`   INT UNSIGNED NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`dam_id`) REFERENCES `animals`(`id`),
  FOREIGN KEY (`calf_id`) REFERENCES `animals`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
  INDEX `idx_calving_dam` (`dam_id`, `calving_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── HERD MOVEMENTS ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `herd_movements` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`           CHAR(36) NOT NULL UNIQUE,
  `herd_id`        INT UNSIGNED NOT NULL,
  `from_camp_id`   INT UNSIGNED DEFAULT NULL,
  `to_camp_id`     INT UNSIGNED NOT NULL,
  `move_date`      DATE NOT NULL,
  `end_date`       DATE DEFAULT NULL,
  `notes`          TEXT DEFAULT NULL,
  `created_by`     INT UNSIGNED NOT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`herd_id`) REFERENCES `herds`(`id`),
  FOREIGN KEY (`from_camp_id`) REFERENCES `camps`(`id`),
  FOREIGN KEY (`to_camp_id`) REFERENCES `camps`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ACTIVITY LOG ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED DEFAULT NULL,
  `entity_type`  VARCHAR(30) NOT NULL,
  `entity_id`    INT UNSIGNED DEFAULT NULL,
  `action`       VARCHAR(20) NOT NULL,
  `description`  VARCHAR(255) DEFAULT NULL,
  `ip_address`   VARCHAR(45) DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  INDEX `idx_activity_entity` (`entity_type`, `entity_id`),
  INDEX `idx_activity_user` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── SYNC QUEUE ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sync_queue` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_uuid`  CHAR(36) NOT NULL,
  `entity_type`  VARCHAR(30) NOT NULL,
  `method`       ENUM('POST','PUT','DELETE') NOT NULL,
  `payload`      JSON NOT NULL,
  `status`       ENUM('pending','processing','synced','failed') NOT NULL DEFAULT 'pending',
  `attempts`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `error_msg`    TEXT DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `synced_at`    DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── SETTINGS ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key`  VARCHAR(100) NOT NULL UNIQUE,
  `setting_val`  TEXT DEFAULT NULL,
  `updated_by`   INT UNSIGNED DEFAULT NULL,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default settings
INSERT INTO `settings` (`setting_key`, `setting_val`) VALUES
  ('app_name',        'BoviLogic'),
  ('default_language','en'),
  ('weight_unit',     'kg'),
  ('date_format',     'Y-m-d'),
  ('timezone',        'Africa/Johannesburg');

SET FOREIGN_KEY_CHECKS = 1;
