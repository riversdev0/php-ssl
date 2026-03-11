-- Add changePass flag to users table.
-- Forces a password change on the user's next login when set to 1.
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `changePass` tinyint(1) NOT NULL DEFAULT 0;
