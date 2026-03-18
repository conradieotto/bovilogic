-- BoviLogic Seed Data (Demo)
-- Run after schema.sql
-- Default admin: admin@bovilogic.co.za / Admin@1234

INSERT INTO `users` (`uuid`, `name`, `email`, `password`, `role`) VALUES
  (UUID(), 'Super Admin', 'admin@bovilogic.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');
-- Password above = "password" (bcrypt). Change immediately after first login.

-- Demo farm
INSERT INTO `farms` (`uuid`, `name`, `location`, `created_by`) VALUES
  (UUID(), 'Demo Farm', 'Free State, South Africa', 1);

-- Demo camps
INSERT INTO `camps` (`uuid`, `farm_id`, `name`, `size_ha`, `created_by`) VALUES
  (UUID(), 1, 'Camp A',  45.0, 1),
  (UUID(), 1, 'Camp B',  60.5, 1),
  (UUID(), 1, 'Camp C',  38.0, 1);

-- Demo herd
INSERT INTO `herds` (`uuid`, `farm_id`, `camp_id`, `name`, `color`, `created_by`) VALUES
  (UUID(), 1, 1, 'Main Herd', '#2E7D32', 1);
