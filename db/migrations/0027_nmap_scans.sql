CREATE TABLE `nmap_scans` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `tenant_id`   INT(11)      NOT NULL,
  `zone_id`     INT(11)      NOT NULL,
  `user_id`     INT(11)      NOT NULL,
  `prefix`      VARCHAR(50)  NOT NULL,
  `ports`       VARCHAR(500) NOT NULL,
  `ptr_lookup`  TINYINT(1)   NOT NULL DEFAULT 0,
  `status`      ENUM('Requested','Scanning','Completed','Error') NOT NULL DEFAULT 'Requested',
  `hosts_found` INT(11)      NOT NULL DEFAULT 0,
  `hosts_added` INT(11)      NOT NULL DEFAULT 0,
  `requested`   DATETIME     DEFAULT NULL,
  `completed`   DATETIME     DEFAULT NULL,
  `error_msg`   TEXT         DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_status` (`tenant_id`, `status`),
  KEY `idx_zone_id`       (`zone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
