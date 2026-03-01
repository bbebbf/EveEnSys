-- ees_db.`user` definition
CREATE TABLE `user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_guid` varchar(8) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `user_is_new` bit(1) NOT NULL DEFAULT b'1',
  `user_is_active` bit(1) NOT NULL DEFAULT b'0',
  `user_role` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL,
  `user_passwd` varchar(100) DEFAULT NULL,
  `user_last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_user_guid_IDX` (`user_guid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ees_db.event definition
CREATE TABLE `event` (
  `event_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_guid` varchar(8) NOT NULL,
  `creator_user_id` int(10) unsigned NOT NULL,
  `event_is_new` bit(1) NOT NULL DEFAULT b'1',
  `event_is_visible` bit(1) NOT NULL DEFAULT b'0',
  `event_title` varchar(150) NOT NULL,
  `event_description` text DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `event_location` varchar(150) DEFAULT NULL,
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

-- ees_db.oidc_provider definition
CREATE TABLE `oidc_provider` (
  `oidc_provider_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oidc_provider_key` varchar(20) NOT NULL,
  `oidc_provider_is_active` bit(1) NOT NULL DEFAULT b'1',
  `oidc_provider_label` varchar(80) NOT NULL,
  `oidc_provider_image_svg` text DEFAULT NULL,
  `oidc_provider_discovery_url` varchar(500) NOT NULL,
  `oidc_provider_client_id` varchar(255) NOT NULL,
  `oidc_provider_client_secret` varchar(255) NOT NULL,
  `oidc_provider_redirect_uri` varchar(500) NOT NULL,
  `oidc_provider_scopes` varchar(500) NOT NULL,
  PRIMARY KEY (`oidc_provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ees_db.oidc_identity definition
CREATE TABLE `oidc_identity` (
  `oidc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `oidc_provider_id` int(10) unsigned NOT NULL,
  `oidc_provider_sub` varchar(255) NOT NULL,
  `oidc_linked_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`oidc_id`),
  UNIQUE KEY `oidc_identity_provider_sub_UQ` (`oidc_provider_id`,`oidc_provider_sub`),
  KEY `oidc_identity_user_FK` (`user_id`),
  CONSTRAINT `oidc_identity_oidc_provider_FK` FOREIGN KEY (`oidc_provider_id`) REFERENCES `oidc_provider` (`oidc_provider_id`),
  CONSTRAINT `oidc_identity_user_FK` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
