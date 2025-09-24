<?php

/**
 * Plugin Name: tracker
 * Description: A simple tracking plugin with configurable tracking methods
 * Version: 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include the admin class
require_once plugin_dir_path(__FILE__) . 'classes/class-admin.php';

$method = get_option('tracker_tracking_method', 'js_tracking');

if($method == 'js_tracking'){
    require_once plugin_dir_path(__FILE__) . 'classes/class-js-tracking.php';
}

if($method == 'content_buffer'){
    require_once plugin_dir_path(__FILE__) . 'classes/class-base-tracker.php';
    require_once plugin_dir_path(__FILE__) . 'classes/class-content-buffer.php';
}   

if($method == 'attachment_url_hook'){
    require_once plugin_dir_path(__FILE__) . 'classes/class-base-tracker.php';
    require_once plugin_dir_path(__FILE__) . 'classes/class-attachment-url-hook.php';
}