<?php

/**
 * Content Buffer tracking method
 * Intercepts the_post content to replace PDF links with tracking endpoints
 */
class Tracker_Content_Buffer extends Tracker_Base {

    /**
     * Initialize the content buffer tracking method
     */
    protected function init_tracking() {
        // Hook into the_post to modify content
        add_filter('the_post', array($this, 'filter_post_content'), 10, 1);
        
        // Also hook into the_content for additional content filtering
        add_filter('the_content', array($this, 'filter_content'), 10, 1);
    }

    /**
     * Filter post content to replace PDF links
     */
    public function filter_post_content($post) {
        if (!$post || !isset($post->post_content)) {
            return $post;
        }
        
        // Replace PDF links in post content
        $post->post_content = $this->replace_pdf_links($post->post_content);
        
        return $post;
    }

    /**
     * Filter content to replace PDF links
     */
    public function filter_content($content) {
        if (empty($content)) {
            return $content;
        }
        
        return $this->replace_pdf_links($content);
    }

    /**
     * Replace PDF links with tracking endpoints
     */
    private function replace_pdf_links($content) {
        
        // Pattern to match PDF links (both direct links and attachment links)
        $patterns = array(
            // Direct PDF links
            '/(<a[^>]+href=["\'])([^"\']*\.pdf)(["\'][^>]*>)/i',
            // WordPress attachment links  
            '/(<a[^>]+href=["\'])([^"\']*attachment_id=(\d+))([^"\']*)(["\'][^>]*>)/i',
        );
        
        foreach ($patterns as $pattern) {
            $content = preg_replace_callback($pattern, array($this, 'replace_pdf_link_callback'), $content);
        }
        
        
        return $content;
    }

    /**
     * Callback function to replace PDF links
     */
    private function replace_pdf_link_callback($matches) {
        
        if (count($matches) === 4) {
            // Direct PDF link pattern
            $before = $matches[1];
            $pdf_url = $matches[2];
            $after = $matches[3];
            
            
            // Check if it's actually a PDF file
            if (strtolower(substr($pdf_url, -4)) === '.pdf') {
                // Try to find the attachment ID from the URL
                $attachment_id = $this->get_attachment_id_from_url($pdf_url);
                
                
                if ($attachment_id) {
                    $tracking_url = home_url('/download-file/?id=' . $attachment_id);
                    return $before . $tracking_url . $after;
                }
            }
        } elseif (count($matches) === 6) {
            // WordPress attachment link pattern
            $before = $matches[1];
            $attachment_id = $matches[3];
            $after = $matches[5];
            
            
            // Check if this attachment is a PDF
            $attachment = get_post($attachment_id);
            if ($attachment && $attachment->post_type === 'attachment') {
                $file_path = get_attached_file($attachment_id);
                if ($file_path && strtolower(substr($file_path, -4)) === '.pdf') {
                    $tracking_url = home_url('/download-file/?id=' . $attachment_id);
                    return $before . $tracking_url . $after;
                }
            }
        }
        
        // Return original if no replacement needed
        return $matches[0];
    }

}

// Initialize the class
new Tracker_Content_Buffer();
