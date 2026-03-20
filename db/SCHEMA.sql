/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table migrations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table agents
# ------------------------------------------------------------

DROP TABLE IF EXISTS `agents`;

CREATE TABLE `agents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `is_global` tinyint(1) NOT NULL DEFAULT 0,
  `atype` enum('local','remote') DEFAULT 'remote',
  `url` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `comment` text DEFAULT NULL,
  `last_check` datetime DEFAULT NULL,
  `last_success` datetime DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `protected` tinyint(4) NOT NULL DEFAULT 0,
  `version` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `atenants` (`t_id`),
  CONSTRAINT `atenants` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table certificates
# ------------------------------------------------------------

DROP TABLE IF EXISTS `certificates`;

CREATE TABLE `certificates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `z_id` int(11) unsigned NOT NULL,
  `t_id` int(11) unsigned NOT NULL,
  `serial` varchar(255) NOT NULL DEFAULT '',
  `certificate` text NOT NULL,
  `pkey_id` int(11) unsigned DEFAULT NULL,
  `chain` text DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_zone_serial` (`z_id`,`serial`),
  KEY `serial` (`serial`),
  KEY `c_tenants` (`t_id`),
  KEY `c_pkey` (`pkey_id`),
  CONSTRAINT `c_pkey` FOREIGN KEY (`pkey_id`) REFERENCES `pkey` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `c_tenants` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `c_zones` FOREIGN KEY (`z_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table config
# ------------------------------------------------------------

DROP TABLE IF EXISTS `config`;

CREATE TABLE `config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(128) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `s_tenants` (`t_id`),
  CONSTRAINT `s_tenants` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cron
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cron`;

CREATE TABLE `cron` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `minute` varchar(6) NOT NULL DEFAULT '*',
  `hour` varchar(6) NOT NULL DEFAULT '*',
  `day` varchar(6) NOT NULL DEFAULT '*',
  `month` varchar(6) NOT NULL DEFAULT '*',
  `weekday` varchar(6) NOT NULL DEFAULT '*',
  `script` varchar(255) NOT NULL DEFAULT '',
  `last_executed` timestamp NULL DEFAULT NULL,
  `force` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `cront_tenant` (`t_id`),
  CONSTRAINT `cront_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table hosts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `hosts`;

CREATE TABLE `hosts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `z_id` int(11) unsigned NOT NULL,
  `c_id` int(11) unsigned DEFAULT NULL,
  `c_id_old` int(11) unsigned DEFAULT NULL,
  `pg_id` int(11) unsigned NOT NULL DEFAULT 1,
  `ignore` tinyint(1) NOT NULL DEFAULT 0,
  `mute` tinyint(1) NOT NULL DEFAULT 0,
  `hostname` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `ip` varchar(15) CHARACTER SET utf8 DEFAULT NULL,
  `port` int(5) DEFAULT NULL,
  `h_recipients` text CHARACTER SET utf8 DEFAULT NULL,
  `last_check` timestamp NULL DEFAULT NULL,
  `last_change` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `zone_hostname` (`z_id`,`hostname`),
  KEY `h_domain` (`z_id`),
  KEY `h_cert` (`c_id`),
  CONSTRAINT `h_cert` FOREIGN KEY (`c_id`) REFERENCES `certificates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `h_domain` FOREIGN KEY (`z_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_slovenian_ci;



# Dump of table pkey
# ------------------------------------------------------------

DROP TABLE IF EXISTS `pkey`;

CREATE TABLE `pkey` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table ssl_port_groups
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ssl_port_groups`;

CREATE TABLE `ssl_port_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `name` varchar(64) DEFAULT NULL,
  `ports` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pg_tenant` (`t_id`),
  CONSTRAINT `pg_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table tenants
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tenants`;

CREATE TABLE `tenants` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `href` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `admin` tinyint(1) NOT NULL DEFAULT 0,
  `recipients` text DEFAULT NULL,
  `mail_style` enum('table','list') DEFAULT 'list',
  `remove_orphaned` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(2) DEFAULT 99,
  `log_retention` int(4) NOT NULL DEFAULT 30,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Default tenant
# ------------------------------------------------------------
INSERT INTO `tenants` (`id`, `name`, `href`, `description`, `active`, `admin`, `recipients`, `mail_style`, `remove_orphaned`, `order`, `log_retention`)
VALUES
  (1, 'Administrators', 'admin', 'Administrator tenant', 1, 1, NULL, 'list', 1, 1, 30);


# Dump of table domains
# ------------------------------------------------------------

DROP TABLE IF EXISTS `domains`;

CREATE TABLE `domains` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL DEFAULT 1,
  `name` varchar(32) DEFAULT NULL,
  `type` enum('AD','local') DEFAULT 'local',
  `account_suffix` varchar(256) DEFAULT '@domain.local',
  `base_dn` varchar(256) DEFAULT 'CN=Users,CN=Company,DC=domain,DC=local',
  `domain_controllers` varchar(256) DEFAULT 'dc1.domain.local;dc2.domain.local',
  `use_ssl` tinyint(1) DEFAULT 0,
  `use_tls` tinyint(1) DEFAULT 0,
  `port` int(5) DEFAULT 389,
  `adminUsername` varchar(255) DEFAULT NULL,
  `adminPassword` varchar(256) DEFAULT NULL,
  `autocreateGroup` varchar(255) DEFAULT NULL,
  `active` set('Yes','No') DEFAULT 'No',
  PRIMARY KEY (`id`),
  KEY `t_id` (`t_id`),
  CONSTRAINT `fk_domains_t_id` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


# Default local domain
# ------------------------------------------------------------
INSERT INTO `domains` (`id`, `t_id`, `name`, `type`, `account_suffix`, `base_dn`, `domain_controllers`, `use_ssl`, `use_tls`, `port`, `adminUsername`, `adminPassword`, `autocreateGroup`, `active`)
VALUES
  (1, 1, 'local', 'local', '@local', '', '', 0, 0, 0, NULL, NULL, NULL, 'Yes');



# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(128) DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `permission` int(1) NOT NULL DEFAULT 0,
  `days` int(4) NOT NULL DEFAULT 30,
  `days_expired` int(4) NOT NULL DEFAULT 30,
  `notif_id` int(11) unsigned DEFAULT 0,
  `changePass` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `u_tenants` (`t_id`),
  CONSTRAINT `u_tenants` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Default user
# ------------------------------------------------------------
INSERT INTO `users` (`id`, `t_id`, `email`, `password`, `name`, `permission`, `days`, `days_expired`, `notif_id`)
VALUES
  (1, 1, 'admin', 'c7ad44cbad762a5da0a452f9e854fdc1e0e7a52a38015f23f3eab1d80b931dd472634dfac71cd34ebc35d16ab7fb8a90c81f975113d6c7538dc69dd8de9077ec', 'Administrator', 3, 30, 30, 0);



# Dump of table zones
# ------------------------------------------------------------

DROP TABLE IF EXISTS `zones`;

CREATE TABLE `zones` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `aname` varchar(255) NOT NULL DEFAULT '',
  `ignore` tinyint(1) NOT NULL DEFAULT 0,
  `type` enum('local','axfr') NOT NULL DEFAULT 'local',
  `agent_id` int(11) unsigned DEFAULT 1,
  `description` text DEFAULT NULL,
  `dns` varchar(255) DEFAULT NULL,
  `tsig_name` varchar(255) DEFAULT NULL,
  `tsig` varchar(255) DEFAULT NULL,
  `record_types` varchar(255) DEFAULT NULL,
  `delete_records` tinyint(1) NOT NULL DEFAULT 0,
  `check_ip` tinyint(1) NOT NULL DEFAULT 0,
  `is_domain` tinyint(1) NOT NULL DEFAULT 1,
  `regex_include` text DEFAULT NULL,
  `regex_exclude` text DEFAULT NULL,
  `private_zone_uid` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `t_id` (`t_id`,`name`),
  KEY `name` (`name`),
  KEY `d_agent` (`agent_id`),
  KEY `fk_zone_private_uid` (`private_zone_uid`),
  CONSTRAINT `d_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `d_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_zone_private_uid` FOREIGN KEY (`private_zone_uid`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table ignored_issuers
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ignored_issuers`;

CREATE TABLE `ignored_issuers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `ski` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sku_tid` (`t_id`,`ski`),
  CONSTRAINT `tid` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table logs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `logs`;

CREATE TABLE `logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `object` varchar(32) NOT NULL,
  `object_id` int(11) unsigned DEFAULT NULL,
  `object_t_id` int(11) unsigned DEFAULT NULL,
  `object_u_id` int(11) unsigned DEFAULT NULL,
  `action` varchar(32) NOT NULL,
  `public` tinyint(1) NOT NULL DEFAULT 0,
  `text` text DEFAULT NULL,
  `json_object_old` text DEFAULT NULL,
  `json_object_new` text DEFAULT NULL,
  `is_revertable` tinyint(1) NOT NULL DEFAULT 0,
  `date` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `object_id` (`object_id`),
  KEY `object_t_id` (`object_t_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
