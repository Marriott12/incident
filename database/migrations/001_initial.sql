-- Initial migration for Incident System
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `incident_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `incident_system`;

-- users
CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `rank` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('commanding_officer','incident_officer','hq_readonly','admin') NOT NULL DEFAULT 'incident_officer',
  `unit` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- incidents
CREATE TABLE `incidents` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `incident_number` VARCHAR(30) NOT NULL,
  `reported_at` DATETIME NOT NULL,
  `type` ENUM('criminal','political','military','health','infrastructure','other') NOT NULL,
  `reliability` ENUM('unknown','low','medium','high') NOT NULL DEFAULT 'unknown',
  `reporting_unit` VARCHAR(100) NULL,
  `commanding_officer` VARCHAR(100) NULL,
  `shift` ENUM('day','night') DEFAULT 'day',
  `comms_channels` TEXT NULL,
  `liaison_notes` TEXT NULL,
  `narrative` LONGTEXT NULL,
  `personnel_count_military` INT DEFAULT 0,
  `personnel_count_police` INT DEFAULT 0,
  `personnel_count_civilians` INT DEFAULT 0,
  `personnel_count_adversaries` INT DEFAULT 0,
  `civilian_impact` TEXT NULL,
  `environmental_conditions` TEXT NULL,
  `threat_level` ENUM('low','moderate','high','critical') DEFAULT 'low',
  `escalation_measures` TEXT NULL,
  `weapons_hazmat_present` TINYINT(1) DEFAULT 0,
  `weapons_hazmat_details` TEXT NULL,
  `patterns_forecast` TEXT NULL,
  `military_actions` TEXT NULL,
  `support_actions` TEXT NULL,
  `intelligence_gathered` TEXT NULL,
  `resources_utilized` TEXT NULL,
  `immediate_outcome` TEXT NULL,
  `casualties_count` INT DEFAULT 0,
  `damages_description` TEXT NULL,
  `followup_actions` TEXT NULL,
  `followup_officer` VARCHAR(100) NULL,
  `followup_unit` VARCHAR(100) NULL,
  `report_completed_by` INT NOT NULL,
  `reviewed_by` INT NULL,
  `grid_reference` VARCHAR(20) NULL,
  `latitude` DECIMAL(10,8) NULL,
  `longitude` DECIMAL(11,8) NULL,
  `ao_sector` VARCHAR(50) NULL,
  `ao_polygon` JSON NULL,
  `status` ENUM('open','contained','closed','under_review') DEFAULT 'open',
  `confidentiality_level` ENUM('restricted','confidential','secret') DEFAULT 'restricted',
  `roe_compliance_notes` TEXT NULL,
  `human_rights_notes` TEXT NULL,
  `submitted_to_hq_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_incident_number` (`incident_number`),
  KEY `idx_reported_at` (`reported_at`),
  KEY `idx_incidents_status_reported_at` (`status`, `reported_at`),
  KEY `idx_incidents_threat_level` (`threat_level`),
  KEY `idx_incidents_reporting_unit` (`reporting_unit`),
  CONSTRAINT `fk_incidents_report_completed_by` FOREIGN KEY (`report_completed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_incidents_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- incident attachments
CREATE TABLE `incident_attachments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `incident_id` INT NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_type` ENUM('photo','map','witness_statement','intel_report','other') DEFAULT 'other',
  `mime_type` VARCHAR(100) NULL,
  `file_size` INT NULL,
  `uploaded_by` INT NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_incident_id` (`incident_id`),
  KEY `idx_attachments_uploaded_at` (`uploaded_at`),
  KEY `idx_attachments_file_type` (`file_type`),
  CONSTRAINT `fk_attachments_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attachments_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- evidence chain of custody
CREATE TABLE `evidence_chain_of_custody` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `incident_id` INT NOT NULL,
  `item_description` TEXT NOT NULL,
  `seized_by` INT NULL,
  `seized_at` DATETIME NOT NULL,
  `signature_hash` VARCHAR(64) NOT NULL,
  `custody_notes` TEXT NULL,
  `current_location` VARCHAR(150) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_evidence_incident` (`incident_id`),
  CONSTRAINT `fk_evidence_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_evidence_user` FOREIGN KEY (`seized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- audit log
CREATE TABLE `audit_log` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NULL,
  `incident_id` INT NULL,
  `incident_number` VARCHAR(30) NULL,
  `action` VARCHAR(100) NOT NULL,
  `table_affected` VARCHAR(50) NULL,
  `old_value` JSON NULL,
  `new_value` JSON NULL,
  `metadata` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_incident` (`incident_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_audit_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AO sectors (GeoJSON store)
CREATE TABLE `ao_sectors` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `geojson` JSON NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- sample seed placeholders can be added later
