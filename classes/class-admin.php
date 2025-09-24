<?php

/**
 * Admin class for the tracker plugin
 */
class Tracker_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // Add media library hooks
        add_filter('manage_media_columns', array($this, 'add_download_count_column'));
        add_action('manage_media_custom_column', array($this, 'populate_download_count_column'), 10, 2);
        add_action('admin_head-upload.php', array($this, 'add_media_library_styles'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'Tracker Settings',
            'Tracker',
            'manage_options',
            'tracker-settings',
            array($this, 'admin_page')
        );
    }

    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('tracker_settings', 'tracker_tracking_method');
        
        add_settings_section(
            'tracker_section',
            'Tracking Method Settings',
            array($this, 'settings_section_callback'),
            'tracker-settings'
        );

        add_settings_field(
            'tracker_tracking_method',
            'Select Tracking Method',
            array($this, 'tracking_method_callback'),
            'tracker-settings',
            'tracker_section'
        );
        
        // Hook to flush rewrite rules when tracking method changes
        add_action('update_option_tracker_tracking_method', array($this, 'flush_rewrite_rules_on_method_change'), 10, 2);
    }

    /**
     * Flush rewrite rules when tracking method changes
     */
    public function flush_rewrite_rules_on_method_change($old_value, $new_value) {
        if ($old_value !== $new_value) {
            flush_rewrite_rules();
            error_log('Tracker: Rewrite rules flushed due to tracking method change from ' . $old_value . ' to ' . $new_value);
        }
    }

    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>Choose your preferred tracking method:</p>';
    }

    /**
     * Tracking method radio buttons callback
     */
    public function tracking_method_callback() {
        $options = get_option('tracker_tracking_method', 'js_tracking');
        
        $methods = array(
            'js_tracking' => 'JS tracking',
            'content_buffer' => 'The content buffer',
            'attachment_url_hook' => 'wp_get_attachment_url hook'
        );

        foreach ($methods as $value => $label) {
            $checked = checked($options, $value, false);
            echo '<p>';
            echo '<input type="radio" id="tracker_method_' . esc_attr($value) . '" name="tracker_tracking_method" value="' . esc_attr($value) . '" ' . $checked . '>';
            echo '<label for="tracker_method_' . esc_attr($value) . '">' . esc_html($label) . '</label>';
            echo '</p>';
        }
    }

    /**
     * Admin page content
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Tracker Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('tracker_settings');
                do_settings_sections('tracker-settings');
                submit_button();
                ?>
            </form>
            
            <div class="tracker-info">
                <h3>Current Settings</h3>
                <p><strong>Selected Method:</strong> 
                <?php
                $current_method = get_option('tracker_tracking_method', 'js_tracking');
                $methods = array(
                    'js_tracking' => 'JS tracking',
                    'content_buffer' => 'The content buffer',
                    'attachment_url_hook' => 'wp_get_attachment_url hook'
                );
                echo esc_html($methods[$current_method]);
                ?>
                </p>
            </div>
        </div>
        
        <style>
        .tracker-info {
            margin-top: 30px;
            padding: 20px;
            background: #f1f1f1;
            border-left: 4px solid #0073aa;
        }
        .tracker-info h3 {
            margin-top: 0;
        }
        </style>
        <?php
    }

    /**
     * Add download count column to media library
     */
    public function add_download_count_column($columns) {
        $columns['download_count'] = __('Download Count', 'tracker');
        return $columns;
    }

    /**
     * Populate the download count column with data
     */
    public function populate_download_count_column($column_name, $attachment_id) {
        if ($column_name === 'download_count') {
            $download_count = get_post_meta($attachment_id, 'download_count', true);
            $count = $download_count ? intval($download_count) : 0;
            echo '<span class="download-count-badge">' . esc_html($count) . '</span>';
        }
    }

    /**
     * Add CSS styles for the download count column
     */
    public function add_media_library_styles() {
        ?>
        <style>
        .download-count-badge {
            display: inline-block;
            padding: 4px 8px;
            background-color: #0073aa;
            color: white;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            min-width: 20px;
            text-align: center;
        }
        
        .download-count-badge:empty {
            background-color: #ccc;
        }
        
        .wp-list-table .column-download_count {
            width: 120px;
        }
        
        .wp-list-table .column-download_count .download-count-badge {
            margin-left: 5px;
        }
        </style>
        <?php
    }
}

// Initialize the admin class
new Tracker_Admin();