ALTER TABLE `nmap_scans`
    ADD COLUMN `notify_email` VARCHAR(255) DEFAULT NULL AFTER `ptr_lookup`;
