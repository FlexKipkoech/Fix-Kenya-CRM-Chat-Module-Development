<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Module Name: Slack Chat
 * Description: Real-time chat system
 * Version: 1.0.0
 * Author: Flex Kipkoech
 */

// Register activation and deactivation hooks
register_activation_hook('slack_chat', 'slack_chat_module_activate');
register_deactivation_hook('slack_chat', 'slack_chat_module_deactivate');

function slack_chat_module_activate() {
    // Attempt to run install script to create necessary tables on activation
    $CI = &get_instance();
    $install_file = __DIR__ . '/install.php';
    if (file_exists($install_file)) {
        try {
            // include will execute the install.php which returns true on success
            $result = include $install_file;
            if ($result !== true) {
                log_message('error', 'Slack Chat Module: install.php did not return true during activation.');
            }
        } catch (Throwable $e) {
            log_message('error', 'Slack Chat Module: Exception while running install.php: ' . $e->getMessage());
        }
    } else {
        log_message('error', 'Slack Chat Module: install.php not found during activation.');
    }
}

function slack_chat_module_deactivate() {
    // Deactivation logic if needed
}

// Add Chat to admin menu
hooks()->add_action('admin_init', 'slack_chat_module_init_menu');

function slack_chat_module_init_menu() {
    $CI = &get_instance();
    if (is_admin()) {
        $CI->app_menu->add_sidebar_menu_item('slack_chat', [
            'name'     => _l('Chat'),
            'href'     => admin_url('slack_chat/chat'), // direct to chat view
            'icon'     => 'fa fa-comments',
            'position' => 40,
        ]);
    }
}
