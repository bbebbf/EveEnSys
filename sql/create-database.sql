-- ees_db.`user` definition
CREATE TABLE `user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_guid` varchar(8) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `user_is_active` bit(1) NOT NULL DEFAULT b'0',
  `user_role` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL,
  `user_passwd` varchar(100) NOT NULL,
  `user_last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_user_guid_IDX` (`user_guid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ees_db.event definition
CREATE TABLE `event` (
  `event_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_guid` varchar(8) NOT NULL,
  `creator_user_id` int(10) unsigned NOT NULL,
  `event_title` varchar(150) NOT NULL,
  `event_description` text DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `event_duration_hours` float DEFAULT NULL,
  `event_max_subscriber` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`event_id`),
  UNIQUE KEY `event_event_guid_IDX` (`event_guid`) USING BTREE,
  KEY `event_user_FK` (`creator_user_id`),
  CONSTRAINT `event_user_FK` FOREIGN KEY (`creator_user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ees_db.password_reset definition
CREATE TABLE `password_reset` (
  `reset_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `reset_token_hash` varchar(64) NOT NULL,
  `reset_expires_at` datetime NOT NULL,
  `reset_used` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `password_reset_token_UQ` (`reset_token_hash`),
  KEY `password_reset_user_FK` (`user_id`),
  CONSTRAINT `password_reset_user_FK` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ees_db.activation_token definition
CREATE TABLE `activation_token` (
  `token_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `token_expires_at` datetime NOT NULL,
  `token_used` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `activation_token_UQ` (`token_hash`),
  KEY `activation_token_user_FK` (`user_id`),
  CONSTRAINT `activation_token_user_FK` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ees_db.subscriber definition
CREATE TABLE `subscriber` (
  `subscriber_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subscriber_guid` varchar(8) NOT NULL,
  `event_id` int(10) unsigned NOT NULL,
  `creator_user_id` int(10) unsigned NOT NULL,
  `subscriber_is_creator` bit(1) NOT NULL DEFAULT b'1',
  `subscriber_name` varchar(100) DEFAULT NULL,
  `subscriber_enroll_timestamp` datetime NOT NULL,
  PRIMARY KEY (`subscriber_id`),
  UNIQUE KEY `subscriber_subscriber_guid_IDX` (`subscriber_guid`) USING BTREE,
  KEY `subscriber_user_FK` (`creator_user_id`),
  KEY `subscriber_event_FK` (`event_id`),
  CONSTRAINT `subscriber_event_FK` FOREIGN KEY (`event_id`) REFERENCES `event` (`event_id`),
  CONSTRAINT `subscriber_user_FK` FOREIGN KEY (`creator_user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
