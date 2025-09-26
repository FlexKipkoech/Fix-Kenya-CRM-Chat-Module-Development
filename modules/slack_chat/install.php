<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

$CI = &get_instance();

// Create chat_channels table
$CI->db->query('CREATE TABLE IF NOT EXISTS `'.db_prefix().'chat_channels` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `is_private` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT(11) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

// Create chat_messages table
$CI->db->query('CREATE TABLE IF NOT EXISTS `'.db_prefix().'chat_messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `channel_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `channel_id` (`channel_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

// Create chat_members table
$CI->db->query('CREATE TABLE IF NOT EXISTS `'.db_prefix().'chat_members` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `channel_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `channel_id` (`channel_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
