-- BoviLogic Migration 004 – 2FA + User Permissions
-- Run once in phpMyAdmin on bovilogicco_bovilogic

ALTER TABLE `users`
  ADD COLUMN `totp_secret`  VARCHAR(64)  DEFAULT NULL  AFTER `password`,
  ADD COLUMN `totp_enabled` TINYINT(1)   NOT NULL DEFAULT 0 AFTER `totp_secret`,
  ADD COLUMN `permissions`  TEXT         DEFAULT NULL  AFTER `language`;
