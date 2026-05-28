USE `incident_system`;

ALTER TABLE `incidents`
  MODIFY COLUMN `type` ENUM('criminal','political','military','health','infrastructure','other') NOT NULL,
  ADD COLUMN `reliability` ENUM('unknown','low','medium','high') NOT NULL DEFAULT 'unknown' AFTER `type`;
