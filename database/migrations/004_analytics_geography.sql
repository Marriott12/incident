USE `incident_system`;

-- Add geographic hierarchy fields for analytics drill-down
ALTER TABLE `incidents`
  ADD COLUMN `province` VARCHAR(100) NULL AFTER `ao_polygon`,
  ADD COLUMN `district` VARCHAR(100) NULL AFTER `province`;

-- Index province and district for GROUP BY performance
ALTER TABLE `incidents`
  ADD KEY `idx_incidents_province` (`province`),
  ADD KEY `idx_incidents_district` (`district`);
