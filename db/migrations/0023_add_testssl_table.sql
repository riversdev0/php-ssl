-- Migration 0023: add testssl scan table

CREATE TABLE IF NOT EXISTS `testssl` (
  `id`            int(11) unsigned    NOT NULL AUTO_INCREMENT,
  `tenant_id`     int(11) unsigned    NOT NULL,
  `user_id`       int(11) unsigned    NOT NULL,
  `hostname`      varchar(255)        NOT NULL,
  `port`          int(5) unsigned     NOT NULL DEFAULT 443,
  `rating`        varchar(10)         DEFAULT NULL,
  `status`        enum('Requested','Scanning','Completed','Cancelled','Error') NOT NULL DEFAULT 'Requested',
  `requested`     datetime            NOT NULL DEFAULT current_timestamp(),
  `started`       datetime            DEFAULT NULL,
  `completed`     datetime            DEFAULT NULL,
  `hash`          varchar(64)         NOT NULL,
  `json_result`   longtext            DEFAULT NULL,
  `error_message` text                DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`),
  KEY `hostname` (`hostname`),
  KEY `tenant_id` (`tenant_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `testssl_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `testssl_user`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`)   ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
