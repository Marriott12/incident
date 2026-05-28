-- Add workflow states and review fields for incidents; add formations/province to users and extend role enum
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `formation` VARCHAR(100) NULL AFTER `unit`,
  ADD COLUMN IF NOT EXISTS `province` VARCHAR(100) NULL AFTER `formation`;

-- Extend role enum to include new workflow roles (g_staff, formation_commander)
ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('admin','g_staff','formation_commander','cpo','incident_officer','hq_readonly','army_hq') NOT NULL DEFAULT 'incident_officer';

-- Add incident workflow columns
ALTER TABLE `incidents`
  ADD COLUMN IF NOT EXISTS `formation` VARCHAR(100) NULL AFTER `report_completed_by`,
  ADD COLUMN IF NOT EXISTS `g_staff_comment` TEXT NULL AFTER `district`,
  ADD COLUMN IF NOT EXISTS `g_staff_reviewed_by` INT NULL AFTER `g_staff_comment`,
  ADD COLUMN IF NOT EXISTS `g_staff_reviewed_at` DATETIME NULL AFTER `g_staff_reviewed_by`,
  ADD COLUMN IF NOT EXISTS `formation_comment` TEXT NULL AFTER `g_staff_reviewed_at`,
  ADD COLUMN IF NOT EXISTS `formation_reviewed_by` INT NULL AFTER `formation_comment`,
  ADD COLUMN IF NOT EXISTS `formation_reviewed_at` DATETIME NULL AFTER `formation_reviewed_by`,
  ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL AFTER `formation_reviewed_at`;

-- Modify status enum to include workflow states
ALTER TABLE `incidents`
  MODIFY COLUMN `status` ENUM('open','g_staff_review','formation_review','approved','rejected','contained','closed','under_review') DEFAULT 'open';

-- Optional: add foreign keys for review by columns (skip if users table uses different constraints)
ALTER TABLE `incidents`
  ADD CONSTRAINT IF NOT EXISTS `fk_incidents_gstaff_reviewed_by` FOREIGN KEY (`g_staff_reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT IF NOT EXISTS `fk_incidents_formation_reviewed_by` FOREIGN KEY (`formation_reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Indexes to help queries
ALTER TABLE `incidents`
  ADD INDEX IF NOT EXISTS `idx_incidents_formation` (`formation`),
  ADD INDEX IF NOT EXISTS `idx_incidents_gstaff_reviewed_at` (`g_staff_reviewed_at`),
  ADD INDEX IF NOT EXISTS `idx_incidents_formation_reviewed_at` (`formation_reviewed_at`),
  ADD INDEX IF NOT EXISTS `idx_incidents_approved_at` (`approved_at`);
