<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$queries = [
    // chat_channels table
    'CREATE TABLE IF NOT EXISTS `' . db_prefix() . 'chat_channels` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT NULL,
        `is_private` TINYINT(1) NOT NULL DEFAULT 0,
        `created_by` INT(11) NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    // chat_messages table
    'CREATE TABLE IF NOT EXISTS `' . db_prefix() . 'chat_messages` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `channel_id` INT(11) NOT NULL,
        `user_id` INT(11) NOT NULL,
        `message` TEXT NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `channel_id` (`channel_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    // chat_members table
    'CREATE TABLE IF NOT EXISTS `' . db_prefix() . 'chat_members` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `channel_id` INT(11) NOT NULL,
        `user_id` INT(11) NOT NULL,
        `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `channel_id` (`channel_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    // chat_files table for file attachments
    'CREATE TABLE IF NOT EXISTS `' . db_prefix() . 'chat_files` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `message_id` INT(11) NOT NULL,
        `filename` VARCHAR(255) NOT NULL,
        `filepath` VARCHAR(500) NOT NULL,
        `filesize` INT(11) NOT NULL,
        `filetype` VARCHAR(100) NOT NULL,
        `uploaded_by` INT(11) NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `message_id` (`message_id`),
        KEY `uploaded_by` (`uploaded_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    // chat_reactions table for emoji reactions
    'CREATE TABLE IF NOT EXISTS `' . db_prefix() . 'chat_reactions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `message_id` INT(11) NOT NULL,
        `user_id` INT(11) NOT NULL,
        `emoji` VARCHAR(10) NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `message_id` (`message_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    // chat_threads table for message threading
    'CREATE TABLE IF NOT EXISTS `' . db_prefix() . 'chat_threads` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `parent_message_id` INT(11) NOT NULL,
        `message_id` INT(11) NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `parent_message_id` (`parent_message_id`),
        KEY `message_id` (`message_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;',
    // chat_typing table for typing indicators
    'CREATE TABLE IF NOT EXISTS `' . db_prefix() . 'chat_typing` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `channel_id` INT(11) NOT NULL,
        `user_id` INT(11) NOT NULL,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_channel_user` (`channel_id`, `user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
];

foreach ($queries as $sql) {
    if (!$CI->db->query($sql)) {
        log_message('error', 'Slack Chat Module: Failed to execute query: ' . $sql);
        return false;
    }
}

return true;
