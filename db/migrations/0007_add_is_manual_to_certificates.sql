-- Add is_manual column to certificates table to mark manually imported certificates.
-- Manual certificates are exempt from orphaned-certificate cleanup.
ALTER TABLE `certificates` ADD COLUMN IF NOT EXISTS `is_manual` tinyint(1) NOT NULL DEFAULT 0;
