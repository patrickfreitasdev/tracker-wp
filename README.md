# WordPress Tracker Plugin

A flexible WordPress plugin that tracks file downloads with multiple tracking methods. Perfect for monitoring PDF downloads and other attachment files.

## Features

- **Multiple Tracking Methods**: Choose from three different tracking approaches
- **Download Analytics**: Track download counts for WordPress attachments
- **Media Library Integration**: View download counts directly in the WordPress media library
- **Admin Dashboard**: Easy configuration through WordPress admin settings
- **Secure Downloads**: Files are served through WordPress with proper security checks
- **Performance Optimized**: Uses X-Sendfile/X-Accel-Redirect for efficient file serving

## Installation

1. Upload the `tracker` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Tracker to configure your preferred tracking method

## Tracking Methods

The plugin offers three different tracking methods to suit various needs:

### 1. JS Tracking
- **How it works**: Uses JavaScript to detect PDF link clicks and track them via AJAX
- **Best for**: Sites where you want minimal server-side processing
- **Pros**: Lightweight, works with external files
- **Cons**: Requires JavaScript enabled, may miss some downloads

### 2. Content Buffer
- **How it works**: Intercepts post content and replaces PDF links with tracking endpoints
- **Best for**: Sites with content-heavy pages containing many PDF links
- **Pros**: Reliable tracking, works without JavaScript
- **Cons**: Processes content on every page load

### 3. Attachment URL Hook
- **How it works**: Intercepts `wp_get_attachment_url()` to redirect through tracking endpoint
- **Best for**: Sites that primarily use WordPress attachment URLs
- **Pros**: Most efficient, minimal overhead
- **Cons**: Only works with WordPress attachments

## Configuration

1. Navigate to **Settings > Tracker** in your WordPress admin
2. Select your preferred tracking method
3. Save changes

The plugin will automatically flush rewrite rules when you change tracking methods.

## Usage

### Automatic Tracking
Once activated and configured, the plugin automatically:
- Tracks downloads of PDF files and other attachments
- Increments download counters
- Serves files through secure endpoints

### Viewing Download Statistics
- Go to **Media > Library** in WordPress admin
- Download counts are displayed in a dedicated column
- Counts are shown as blue badges next to each file

### Download URLs
Files are served through tracking endpoints like:
```
https://yoursite.com/download-file/?id=123
```

## File Structure

```
tracker/
├── tracker.php                    # Main plugin file
├── classes/
│   ├── class-admin.php           # Admin interface and settings
│   ├── class-base-tracker.php    # Base class with common functionality
│   ├── class-content-buffer.php  # Content buffer tracking method
│   ├── class-js-tracking.php     # JavaScript tracking method
│   └── class-attachment-url-hook.php # URL hook tracking method
└── README.md                     # This file
```

## Technical Details

### Database Storage
- Download counts are stored as post meta (`download_count`)
- External file downloads are stored in WordPress options table
- No additional database tables required

### Security Features
- Nonce verification for AJAX requests
- File existence validation before serving
- Attachment ID validation
- Proper MIME type detection

### Performance Features
- X-Sendfile support for Apache
- X-Accel-Redirect support for Nginx
- Efficient file serving with proper headers
- Minimal database queries

## Requirements

- WordPress 4.0 or higher
- PHP 7.0 or higher
- jQuery (for JS tracking method)

## Browser Support

- All modern browsers
- JavaScript tracking method requires JavaScript enabled
- Other methods work without JavaScript

## Troubleshooting

### Downloads Not Being Tracked
1. Check that your selected tracking method is active
2. Verify that files are WordPress attachments (for URL hook method)
3. Ensure JavaScript is enabled (for JS tracking method)
4. Check WordPress error logs for any issues

### Rewrite Rules Issues
If download URLs return 404 errors:
1. Go to Settings > Permalinks
2. Click "Save Changes" to flush rewrite rules
3. Or change tracking method and change it back

### Performance Issues
- Use the "Attachment URL Hook" method for best performance
- Ensure your server supports X-Sendfile or X-Accel-Redirect
- Consider caching if using content buffer method

## Development

### Extending the Plugin
The plugin uses an abstract base class (`Tracker_Base`) that can be extended to create custom tracking methods:

```php
class Custom_Tracker extends Tracker_Base {
    protected function init_tracking() {
        // Your custom tracking logic
    }
}
```

### Hooks and Filters
- `tracker_tracking_method` - Filter the tracking method option
- `tracker_download_count` - Filter download count before saving
- `tracker_file_serve` - Filter file serving behavior

## Changelog

### Version 1.0.0
- Initial release
- Three tracking methods implemented
- Admin interface with settings
- Media library integration
- Download count tracking

## Support

For support, feature requests, or bug reports, please contact the plugin developer.

## License

This plugin is licensed under the GPL v2 or later.

---

**Note**: This plugin is designed for WordPress sites and requires proper WordPress installation and configuration to function correctly.
