ALTER TABLE `testssl`
  ADD COLUMN `notify_email` varchar(255) DEFAULT NULL AFTER `hash`;
