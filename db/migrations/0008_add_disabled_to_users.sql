-- Add disabled flag to users table.
-- When set to 1, the user cannot log in and sees a "disabled" page instead.
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `disabled` tinyint(1) NOT NULL DEFAULT 0;
