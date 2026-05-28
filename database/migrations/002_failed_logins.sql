USE `incident_system`;

CREATE TABLE IF NOT EXISTS `failed_logins` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(150) NOT NULL,
  `attempts` INT NOT NULL DEFAULT 0,
  `last_attempt` TIMESTAMP NULL DEFAULT NULL,
  `locked_until` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_failed_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
