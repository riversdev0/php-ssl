-- Add force column to cron table for forced execution on next run.
ALTER TABLE `cron` ADD COLUMN IF NOT EXISTS `force` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
