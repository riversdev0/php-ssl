-- Add private_zone_uid column to zones table for per-user private zones.
ALTER TABLE `zones` ADD COLUMN IF NOT EXISTS `private_zone_uid` int(11) unsigned DEFAULT NULL;
ALTER TABLE `zones` ADD KEY IF NOT EXISTS `fk_zone_private_uid` (`private_zone_uid`);
ALTER TABLE `zones` ADD CONSTRAINT `fk_zone_private_uid` FOREIGN KEY (`private_zone_uid`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
