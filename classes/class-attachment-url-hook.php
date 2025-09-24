<?php

/**
 * Attachment URL Hook tracking method
 * Intercepts wp_get_attachment_url to redirect through tracking endpoint
 */
class Tracker_Attachment_URL_Hook extends Tracker_Base {

    /**
     * Initialize the attachment URL tracking method
     */
    protected function init_tracking() {
        // Hook into wp_get_attachment_url to redirect through tracking endpoint
        add_filter('wp_get_attachment_url', array($this, 'trackable_attachment_url'), 10, 2);
    }

    /**
     * Modify attachment URLs to go through tracking endpoint
     */
    public function trackable_attachment_url($url, $attachment_id) {
        // Only modify URLs for actual attachments
        if (!$attachment_id || !get_post($attachment_id)) {
            return $url;
        }
        
        // Create a tracking URL that points to our download endpoint
        return home_url('/download-file/?id=' . $attachment_id);
    }

}

// Initialize the class
new Tracker_Attachment_URL_Hook();
