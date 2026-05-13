
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `t_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `certificate` text DEFAULT NULL,
  `pkey_id` int(11) unsigned DEFAULT NULL,
  `parent_ca_id` int(11) DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  `ski` varchar(255) DEFAULT NULL,
  `source` enum('manual','auto') DEFAULT 'manual',
  `ignore_updates` tinyint(1) NOT NULL DEFAULT 0,
  `ignore_expiry` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `t_id` (`t_id`),
  KEY `pkey_id` (`pkey_id`),
  KEY `parent_ca_id` (`parent_ca_id`),
  KEY `cas_ski_tid` (`ski`,`t_id`),
  CONSTRAINT `cas_parent_fk` FOREIGN KEY (`parent_ca_id`) REFERENCES `cas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cas_pkey_fk` FOREIGN KEY (`pkey_id`) REFERENCES `pkey` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `is_manual` tinyint(1) NOT NULL DEFAULT 0,
  `aki` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_zone_serial` (`z_id`,`serial`),
  KEY `serial` (`serial`),
  KEY `c_tenants` (`t_id`),
  KEY `c_pkey` (`pkey_id`),
  KEY `cert_aki` (`aki`),
  CONSTRAINT `c_pkey` FOREIGN KEY (`pkey_id`) REFERENCES `pkey` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `c_tenants` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `c_zones` FOREIGN KEY (`z_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13819 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(128) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `s_tenants` (`t_id`),
  CONSTRAINT `s_tenants` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cron`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `force` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Force execution on next cron run',
  PRIMARY KEY (`id`),
  KEY `cront_tenant` (`t_id`),
  CONSTRAINT `cront_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `csr_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `csr_templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `key_algo` enum('RSA','EC') NOT NULL DEFAULT 'RSA',
  `key_size` int(5) NOT NULL DEFAULT 2048,
  `country` varchar(2) DEFAULT NULL,
  `state` varchar(128) DEFAULT NULL,
  `locality` varchar(128) DEFAULT NULL,
  `org` varchar(256) DEFAULT NULL,
  `ou` varchar(256) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `key_usage` text DEFAULT NULL,
  `ext_key_usage` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `csr_tpl_tenant` (`t_id`),
  CONSTRAINT `csr_tpl_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `csrs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `csrs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `cn` varchar(255) NOT NULL DEFAULT '',
  `sans` text DEFAULT NULL,
  `key_algo` enum('RSA','EC') NOT NULL DEFAULT 'RSA',
  `key_size` int(5) NOT NULL DEFAULT 2048,
  `country` varchar(2) DEFAULT NULL,
  `state` varchar(128) DEFAULT NULL,
  `locality` varchar(128) DEFAULT NULL,
  `org` varchar(256) DEFAULT NULL,
  `ou` varchar(256) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('pending','submitted','signed') NOT NULL DEFAULT 'pending',
  `source` enum('internal','external') NOT NULL DEFAULT 'internal',
  `csr_pem` text DEFAULT NULL,
  `extensions` text DEFAULT NULL,
  `pkey_id` int(11) unsigned DEFAULT NULL,
  `cert_id` int(11) unsigned DEFAULT NULL,
  `renewed_by` int(11) unsigned DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `csrs_tenant` (`t_id`),
  KEY `csrs_pkey` (`pkey_id`),
  KEY `csrs_cert` (`cert_id`),
  KEY `csr_renewed_by` (`renewed_by`),
  CONSTRAINT `csr_renewed_by` FOREIGN KEY (`renewed_by`) REFERENCES `csrs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `csrs_cert` FOREIGN KEY (`cert_id`) REFERENCES `certificates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `csrs_pkey` FOREIGN KEY (`pkey_id`) REFERENCES `pkey` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `csrs_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`phpssladmin`@`127.0.0.1`*/ /*!50003 TRIGGER csrs_cert_nulled BEFORE UPDATE ON csrs FOR EACH ROW BEGIN IF NEW.cert_id IS NULL AND OLD.cert_id IS NOT NULL AND NEW.status = 'signed' THEN SET NEW.status = 'pending'; END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  CONSTRAINT `fk_domains_t_id` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `tls_version` varchar(32) COLLATE utf8_slovenian_ci DEFAULT NULL,
  `h_recipients` text CHARACTER SET utf8 DEFAULT NULL,
  `last_check` timestamp NULL DEFAULT NULL,
  `last_success` timestamp NULL DEFAULT NULL,
  `last_change` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `h_domain` (`z_id`),
  KEY `h_cert` (`c_id`),
  KEY `zone_hostname` (`z_id`,`hostname`,`pg_id`),
  CONSTRAINT `h_cert` FOREIGN KEY (`c_id`) REFERENCES `certificates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `h_domain` FOREIGN KEY (`z_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=86372 DEFAULT CHARSET=utf8 COLLATE=utf8_slovenian_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2830 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `passkeys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `passkeys` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `credential_id` varchar(512) NOT NULL,
  `public_key` text NOT NULL,
  `sign_count` int(11) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credential_id` (`credential_id`(255)),
  KEY `passkeys_user_id` (`user_id`),
  CONSTRAINT `passkeys_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pkey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pkey` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `private_key_enc` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ssl_port_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ssl_port_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `name` varchar(64) DEFAULT NULL,
  `ports` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pg_tenant` (`t_id`),
  CONSTRAINT `pg_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `lang_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenants_lang_id` (`lang_id`),
  CONSTRAINT `tenants_lang_id` FOREIGN KEY (`lang_id`) REFERENCES `translations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `translations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'English name, e.g. Slovenian',
  `native_name` varchar(100) NOT NULL COMMENT 'Native name, e.g. Slovenščina',
  `locale_code` varchar(30) NOT NULL COMMENT 'gettext locale, e.g. sl_SI.UTF-8',
  `lang_code` varchar(5) NOT NULL COMMENT 'ISO 639-1, e.g. sl',
  `flag` varchar(10) DEFAULT NULL COMMENT 'Emoji flag',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locale_code` (`locale_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='Available UI translation languages';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(128) DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `permission` int(1) NOT NULL DEFAULT 0,
  `days` int(4) NOT NULL DEFAULT 20,
  `days_expired` int(4) NOT NULL DEFAULT 10,
  `notif_id` int(11) unsigned DEFAULT 0,
  `changePass` tinyint(1) NOT NULL DEFAULT 0,
  `disabled` tinyint(1) NOT NULL DEFAULT 0,
  `force_passkey` tinyint(1) NOT NULL DEFAULT 0,
  `lang_id` int(11) unsigned DEFAULT NULL,
  `test` varchar(50) DEFAULT NULL,
  `create_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `u_tenants` (`t_id`),
  KEY `users_lang_id` (`lang_id`),
  CONSTRAINT `u_tenants` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_lang_id` FOREIGN KEY (`lang_id`) REFERENCES `translations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

