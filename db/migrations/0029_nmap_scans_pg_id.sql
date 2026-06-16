ALTER TABLE `nmap_scans`
    DROP  COLUMN `ports`,
    ADD   COLUMN `pg_id` INT DEFAULT NULL AFTER `prefix`;
