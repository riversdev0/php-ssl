ALTER TABLE `ignored_issuers`
  ADD COLUMN IF NOT EXISTS `update` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `expired` tinyint(1) NOT NULL DEFAULT 0;

-- Preserve existing behavior: all existing ignored issuers continue to suppress change notifications
UPDATE `ignored_issuers` SET `update` = 1 WHERE `update` = 0;
