USE `incident_system`;

ALTER TABLE `audit_log`
  ADD COLUMN `incident_number` VARCHAR(30) NULL AFTER `incident_id`,
  ADD COLUMN `metadata` JSON NULL AFTER `new_value`;

ALTER TABLE `incident_attachments`
  ADD COLUMN `mime_type` VARCHAR(100) NULL AFTER `file_type`,
  ADD COLUMN `file_size` INT NULL AFTER `mime_type`;

ALTER TABLE `incidents`
  ADD KEY `idx_incidents_status_reported_at` (`status`, `reported_at`),
  ADD KEY `idx_incidents_threat_level` (`threat_level`),
  ADD KEY `idx_incidents_reporting_unit` (`reporting_unit`);

ALTER TABLE `incident_attachments`
  ADD KEY `idx_attachments_uploaded_at` (`uploaded_at`),
  ADD KEY `idx_attachments_file_type` (`file_type`);

ALTER TABLE `failed_logins`
  ADD KEY `idx_failed_logins_locked_until` (`locked_until`);