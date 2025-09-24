<?php 

class Tracker_JS_Tracking {
    
    /**
     * Constructor - Initialize the JS tracking method
     */
    public function __construct() {
        add_action('wp_head', [$this, 'add_event_listener_to_files']);
        add_action('wp_ajax_increment_download_count', [$this, 'ajax_increment_download_count']);
        add_action('wp_ajax_nopriv_increment_download_count', [$this, 'ajax_increment_download_count']);
    }


    public function add_event_listener_to_files(){
        ?>
        <script>
            jQuery(document).ready(function($){
               const regex = /\.pdf$/;
               $('a').each(function(){
                if(regex.test($(this).attr('href'))){
                    $(this).on('click', function(){
                        var url = $(this).attr('href');
                        trackDownload(url);
                    });
                }
               });

               function trackDownload(url){
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'increment_download_count',
                        url: url,
                        nonce: '<?php echo wp_create_nonce('tracker_nonce'); ?>'
                    },
                });
               }
            });
        </script>

        <?php
    }
    

    public function find_attachement_by_url($url){
        // Parse the URL to get the file path
        $parsed_url = parse_url($url);
        $file_path = isset($parsed_url['path']) ? basename($parsed_url['path']) : '';
        
        if (empty($file_path)) {
            return false;
        }
        
        // First try: Search by filename in post_title
        $attachment = get_posts([
            'post_type' => 'attachment',
            'post_title' => $file_path,
            'posts_per_page' => 1
        ]);
        
        if (!empty($attachment)) {
            return $attachment[0]->ID;
        }
        
        // Second try: Search by filename in post_name (slug)
        $attachment = get_posts([
            'post_type' => 'attachment',
            'name' => sanitize_title($file_path),
            'posts_per_page' => 1
        ]);
        
        if (!empty($attachment)) {
            return $attachment[0]->ID;
        }
        
        // Third try: Search by _wp_attached_file meta
        $attachment = get_posts([
            'post_type' => 'attachment',
            'meta_query' => [
                [
                    'key' => '_wp_attached_file',
                    'value' => $file_path,
                    'compare' => 'LIKE'
                ]
            ],
            'posts_per_page' => 1
        ]);
        
        if (!empty($attachment)) {
            return $attachment[0]->ID;
        }
        
        // Fourth try: Search all attachments and compare URLs
        $all_attachments = get_posts([
            'post_type' => 'attachment',
            'posts_per_page' => -1
        ]);
        
        foreach ($all_attachments as $att) {
            $attachment_url = wp_get_attachment_url($att->ID);
            if ($attachment_url === $url) {
                return $att->ID;
            }
        }
        
        return false;
    }

    public function increment_download_count($url){
        $attachment_id = $this->find_attachement_by_url($url);

        if($attachment_id){
            // Simple count increment for attachment files
            $download_count = get_post_meta($attachment_id, 'download_count', true);
            $download_count = $download_count ? intval($download_count) : 0;
            $download_count++;
            update_post_meta($attachment_id, 'download_count', $download_count);
            return true;
        }

        // Fallback: Store download count in options for external files
        $external_downloads = get_option('tracker_external_downloads', []);
        
        if (!isset($external_downloads[$url])) {
            $external_downloads[$url] = 0;
        }
        
        $external_downloads[$url]++;
        update_option('tracker_external_downloads', $external_downloads);
        
        return true;
    }

    public function ajax_increment_download_count(){
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'tracker_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check if URL is provided
        if (empty($_POST['url'])) {
            wp_send_json_error('URL is required');
            return;
        }
        
        $url = sanitize_url($_POST['url']);
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error('Invalid URL format');
            return;
        }
        
        $success = $this->increment_download_count($url);
        
        if(!$success){
            wp_send_json_error('Failed to increment download count');
            return;
        }

        wp_send_json_success(['message' => 'Download count incremented']);
    }


}

new Tracker_JS_Tracking();

