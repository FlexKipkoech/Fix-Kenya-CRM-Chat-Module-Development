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
    // Activation logic if needed
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
            'href'     => admin_url('slack_chat'),
            'icon'     => 'fa fa-comments',
            'position' => 40,
        ]);
    }
}
