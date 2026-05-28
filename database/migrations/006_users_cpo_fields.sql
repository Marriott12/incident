USE `incident_system`;

-- Add formation and province to users and extend role enum to include cpo and army_hq
ALTER TABLE `users`
  ADD COLUMN `formation` VARCHAR(100) NULL AFTER `unit`,
  ADD COLUMN `province` VARCHAR(100) NULL AFTER `formation`;

-- Modify role enum to include new roles (if using MySQL enum, adjust accordingly)
ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('commanding_officer','incident_officer','hq_readonly','admin','cpo','army_hq') NOT NULL DEFAULT 'incident_officer';
