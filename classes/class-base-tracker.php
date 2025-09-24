<?php

/**
 * Base Tracker class
 * Contains common functionality for all tracking methods
 */
abstract class Tracker_Base {

    /**
     * Constructor
     */
    public function __construct() {
        // Register the download endpoint
        add_action('init', array($this, 'register_download_endpoint'));
        
        // Handle the download request
        add_action('template_redirect', array($this, 'handle_download_request'));
        
        // Flush rewrite rules on activation
        register_activation_hook(plugin_dir_path(__FILE__) . '../tracker.php', array($this, 'flush_rewrite_rules'));
        
        // Initialize the specific tracking method
        $this->init_tracking();
    }

    /**
     * Initialize the specific tracking method
     * Must be implemented by child classes
     */
    abstract protected function init_tracking();

    /**
     * Register the download endpoint
     */
    public function register_download_endpoint() {
        add_rewrite_endpoint('download-file', EP_ROOT);
    }

    /**
     * Handle the download request
     */
    public function handle_download_request() {
        global $wp_query;
        
        // Check if this is our download endpoint
        if (!isset($wp_query->query_vars['download-file']) || empty($wp_query->query_vars['download-file'])) {
            // Fallback to check $_GET for direct access
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                return;
            }
            $attachment_id = (int) $_GET['id'];
        } else {
            // Get ID from query vars or $_GET
            $id = isset($_GET['id']) ? $_GET['id'] : $wp_query->query_vars['download-file'];
            if (!is_numeric($id)) {
                return;
            }
            $attachment_id = (int) $id;
        }
        
        // Verify the attachment exists
        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            status_header(404);
            exit;
        }

        // Get the actual file path
        $file_path = get_attached_file($attachment_id);
        
        
        // Verify file exists
        if (!$file_path || !file_exists($file_path)) {
            status_header(404);
            exit;
        }

        // Track the download
        $this->track_download($attachment_id);

        // Serve the file
        $this->serve_file($file_path);
    }

    /**
     * Serve the file to the user
     */
    protected function serve_file($file_path) {
        // Get file info
        $filename = basename($file_path);
        $mime_type = wp_check_filetype($filename)['type'];
        
        // If we can't determine MIME type, try to get it from the file
        if (!$mime_type) {
            $mime_type = mime_content_type($file_path);
        }
        
        // Set headers for download
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        
        // Try to use X-Sendfile or X-Accel-Redirect for better performance
        if (function_exists('apache_get_modules') && in_array('mod_xsendfile', apache_get_modules())) {
            // Apache X-Sendfile
            header('X-Sendfile: ' . $file_path);
        } elseif (isset($_SERVER['HTTP_X_ACCEL_REDIRECT'])) {
            // Nginx X-Accel-Redirect
            header('X-Accel-Redirect: ' . $file_path);
        } else {
            // Fallback: PHP serve (slower for large files)
            readfile($file_path);
        }
        
        exit;
    }

    /**
     * Track the download
     * Simple count increment only
     */
    protected function track_download($attachment_id) {
        // Increment download count
        $download_count = get_post_meta($attachment_id, 'download_count', true);
        $download_count = $download_count ? intval($download_count) : 0;
        $download_count++;
        update_post_meta($attachment_id, 'download_count', $download_count);
    }


    /**
     * Flush rewrite rules on activation
     */
    public function flush_rewrite_rules() {
        $this->register_download_endpoint();
        flush_rewrite_rules();
    }

    /**
     * Get attachment ID from URL
     */
    protected function get_attachment_id_from_url($url) {
        // Try to extract attachment ID from WordPress URLs
        if (preg_match('/attachment_id=(\d+)/', $url, $matches)) {
            return intval($matches[1]);
        }
        
        // Try to find attachment by URL
        $attachment_id = attachment_url_to_postid($url);
        
        if ($attachment_id) {
            return $attachment_id;
        }
        
        // Try to find by filename
        $filename = basename(parse_url($url, PHP_URL_PATH));
        if ($filename) {
            global $wpdb;
            $attachment_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_title = %s",
                $filename
            ));
            
            if ($attachment_id) {
                return intval($attachment_id);
            }
        }
        
        return false;
    }
}
