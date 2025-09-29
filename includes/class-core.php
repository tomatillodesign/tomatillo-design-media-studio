<?php
/**
 * Core plugin functionality
 * 
 * Handles main plugin operations and module coordination
 */

if (!defined('ABSPATH')) {
    exit;
}

class Tomatillo_Media_Core {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('wp_ajax_tomatillo_get_media_stats', array($this, 'ajax_get_media_stats'));
        add_action('wp_ajax_tomatillo_get_optimization_stats', array($this, 'ajax_get_optimization_stats'));
    }
    
    /**
     * Initialize core functionality
     */
    public function init() {
        // Log initialization
        $this->log('Tomatillo Media Studio Core initialized', 'info');
        
        // Check system requirements
        $this->check_requirements();
        
        // Initialize modules
        $this->init_modules();
    }
    
    /**
     * Check system requirements
     */
    private function check_requirements() {
        $requirements = array();
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $requirements[] = __('PHP 7.4 or higher is required', 'tomatillo-media-studio');
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.8', '<')) {
            $requirements[] = __('WordPress 5.8 or higher is required', 'tomatillo-media-studio');
        }
        
        // Check for required PHP extensions
        if (!extension_loaded('gd') && !extension_loaded('imagick')) {
            $requirements[] = __('GD or Imagick extension is required for image processing', 'tomatillo-media-studio');
        }
        
        // Check for AVIF support
        if (function_exists('tomatillo_media_studio')) {
            $plugin = tomatillo_media_studio();
            if ($plugin && isset($plugin->settings) && $plugin->settings->is_optimization_enabled()) {
                if (!function_exists('imageavif') && !class_exists('Imagick')) {
                    $requirements[] = __('AVIF support requires GD with AVIF support or Imagick', 'tomatillo-media-studio');
                }
            }
        }
        
        if (!empty($requirements)) {
            add_action('admin_notices', function() use ($requirements) {
                echo '<div class="notice notice-error"><p><strong>' . __('Media Studio:', 'tomatillo-media-studio') . '</strong> ' . implode(', ', $requirements) . '</p></div>';
            });
        }
    }
    
    /**
     * Initialize modules
     */
    private function init_modules() {
        // Only initialize if plugin is fully loaded
        if (!function_exists('tomatillo_media_studio')) {
            return;
        }
        
        $plugin = tomatillo_media_studio();
        
        // Initialize optimization module
        if ($plugin && isset($plugin->optimization) && $plugin->optimization) {
            $plugin->optimization->init();
        }
        
        // Initialize media library module
        if ($plugin && isset($plugin->media_library) && $plugin->media_library) {
            $plugin->media_library->init();
        }
        
        // Initialize admin module
        if ($plugin && isset($plugin->admin) && $plugin->admin) {
            $plugin->admin->init();
        }
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        // Show activation notice
        if (get_transient('tomatillo_media_studio_activated')) {
            delete_transient('tomatillo_media_studio_activated');
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>' . __('Media Studio', 'tomatillo-media-studio') . '</strong> ' . __('has been activated successfully!', 'tomatillo-media-studio') . '</p>';
            echo '<p><a href="' . admin_url('admin.php?page=tomatillo-media-studio-settings') . '" class="button button-primary">' . __('Go to Settings', 'tomatillo-media-studio') . '</a></p>';
            echo '</div>';
        }
        
        // Show module status notices
        $this->show_module_notices();
    }
    
    /**
     * Show module status notices
     */
    private function show_module_notices() {
        if (!function_exists('tomatillo_media_studio')) {
            return;
        }
        
        $plugin = tomatillo_media_studio();
        if (!$plugin || !isset($plugin->settings)) {
            return;
        }
        
        $settings = $plugin->settings;
        
        if (!$settings->is_optimization_enabled() && !$settings->is_media_library_enabled()) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>' . __('Media Studio:', 'tomatillo-media-studio') . '</strong> ' . __('Both modules are disabled. Please enable at least one module in the settings.', 'tomatillo-media-studio') . '</p>';
            echo '<p><a href="' . admin_url('admin.php?page=tomatillo-media-studio-settings') . '" class="button button-secondary">' . __('Go to Settings', 'tomatillo-media-studio') . '</a></p>';
            echo '</div>';
        }
    }
    
    /**
     * AJAX handler for getting media statistics
     */
    public function ajax_get_media_stats() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tomatillo_media_studio')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $stats = $this->get_media_stats();
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX handler for getting optimization statistics
     */
    public function ajax_get_optimization_stats() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tomatillo_media_studio')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $stats = $this->get_optimization_stats();
        wp_send_json_success($stats);
    }
    
    /**
     * Get media statistics
     */
    public function get_media_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total media count
        $stats['total_media'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment'");
        
        // Images count
        $stats['total_images'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
        ");
        
        // Documents count
        $stats['total_documents'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type NOT LIKE 'image/%'
        ");
        
        // Total file size
        $stats['total_size'] = $wpdb->get_var("
            SELECT SUM(meta_value) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_wp_attachment_metadata'
        ");
        
        // Recent uploads (last 30 days)
        $stats['recent_uploads'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        return $stats;
    }
    
    /**
     * Get optimization statistics
     */
    public function get_optimization_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
        
        // Initialize with default values
        $stats = array(
            'total_conversions' => 0,
            'avif_conversions' => 0,
            'webp_conversions' => 0,
            'total_space_saved' => 0,
            'average_savings' => 0,
            'pending_optimizations' => 0
        );
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
        
        if (!$table_exists) {
            return $stats;
        }
        
        // Total optimized (count by checking actual files)
        $total_images = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type IN ('image/jpeg', 'image/png')
        ");
        
        $optimized_count = 0;
        if ($total_images > 0) {
            $images = $wpdb->get_results("
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_type = 'attachment' 
                AND post_mime_type IN ('image/jpeg', 'image/png')
            ");
            
            foreach ($images as $image) {
                if ($this->is_image_optimized($image->ID)) {
                    $optimized_count++;
                }
            }
        }
        
        $stats['total_conversions'] = $optimized_count;
        
        // AVIF conversions
        $stats['avif_conversions'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE avif_path IS NOT NULL");
        
        // WebP conversions
        $stats['webp_conversions'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE webp_path IS NOT NULL");
        
        // Calculate space saved by checking actual file sizes
        $total_space_saved = 0;
        if ($total_images > 0) {
            $images = $wpdb->get_results("
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_type = 'attachment' 
                AND post_mime_type IN ('image/jpeg', 'image/png')
            ");
            
            foreach ($images as $image) {
                $savings = $this->calculate_image_savings($image->ID);
                if ($savings > 0) {
                    $total_space_saved += $savings;
                }
            }
        }
        
        $stats['total_space_saved'] = $total_space_saved;
        
        // Calculate average savings
        if ($stats['total_conversions'] > 0) {
            $total_original_size = 0;
            $images = $wpdb->get_results("
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_type = 'attachment' 
                AND post_mime_type IN ('image/jpeg', 'image/png')
            ");
            
            foreach ($images as $image) {
                $file_path = get_attached_file($image->ID);
                if ($file_path && file_exists($file_path)) {
                    $total_original_size += filesize($file_path);
                }
            }
            
            if ($total_original_size > 0) {
                $stats['average_savings'] = ($stats['total_space_saved'] / $total_original_size) * 100;
            }
        }
        
        // Pending optimizations
        $stats['pending_optimizations'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'pending'");
        
        return $stats;
    }
    
    /**
     * Get plugin information
     */
    public function get_plugin_info() {
        return array(
            'name' => 'Tomatillo Media Studio',
            'version' => TOMATILLO_MEDIA_STUDIO_VERSION,
            'author' => 'Chris Liu-Beers, Tomatillo Design',
            'description' => __('A comprehensive WordPress media solution featuring automatic AVIF/WebP optimization and a beautiful, modern media library interface.', 'tomatillo-media-studio'),
            'url' => 'https://github.com/tomatillodesign/tomatillo-design-media-studio',
        );
    }
    
    /**
     * Log debug information
     */
    public function log($message, $level = 'info') {
        if (!function_exists('tomatillo_media_studio')) {
            return;
        }
        
        $plugin = tomatillo_media_studio();
        if (!$plugin || !isset($plugin->settings) || !$plugin->settings->is_debug_mode()) {
            return;
        }
        
        $log_entry = sprintf(
            '[%s] [%s] %s',
            current_time('Y-m-d H:i:s'),
            strtoupper($level),
            $message
        );
        
        // Log to WordPress error log
        error_log($log_entry);
        
        // Also log to our custom log file
        $this->write_to_log_file($log_entry);
    }
    
    /**
     * Write to custom log file
     */
    private function write_to_log_file($message) {
        $log_file = WP_CONTENT_DIR . '/tomatillo-media-studio.log';
        
        // Ensure log file doesn't get too large (max 1MB)
        if (file_exists($log_file) && filesize($log_file) > 1048576) {
            // Keep only last 500 lines
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_slice($lines, -500);
            file_put_contents($log_file, implode("\n", $lines) . "\n");
        }
        
        file_put_contents($log_file, $message . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get plugin logs
     */
    public function get_plugin_logs() {
        $log_file = WP_CONTENT_DIR . '/tomatillo-media-studio.log';
        
        if (!file_exists($log_file)) {
            return array();
        }
        
        $logs = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Return last 100 log entries
        return array_slice($logs, -100);
    }
    
    /**
     * Clear plugin logs
     */
    public function clear_plugin_logs() {
        $log_file = WP_CONTENT_DIR . '/tomatillo-media-studio.log';
        
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
        }
    }
    
    /**
     * Get system information
     */
    public function get_system_info() {
        return array(
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'gd_loaded' => extension_loaded('gd'),
            'gd_avif' => function_exists('imageavif'),
            'gd_webp' => function_exists('imagewebp'),
            'imagick_loaded' => class_exists('Imagick'),
            'plugin_version' => TOMATILLO_MEDIA_STUDIO_VERSION,
            'plugin_dir' => TOMATILLO_MEDIA_STUDIO_DIR,
        );
    }
    
    /**
     * Get count of unoptimized images
     */
    public function get_unoptimized_images_count() {
        global $wpdb;
        
        // Get all image attachments
        $images = $wpdb->get_results("
            SELECT ID, post_title, post_mime_type 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type IN ('image/jpeg', 'image/png')
        ");
        
        if (empty($images)) {
            return 0;
        }
        
        $unoptimized_count = 0;
        
        foreach ($images as $image) {
            if (!$this->is_image_optimized($image->ID)) {
                $unoptimized_count++;
            }
        }
        
        return $unoptimized_count;
    }
    
    /**
     * Check if an image is already optimized by looking for AVIF/WebP files
     */
    public function is_image_optimized($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        
        if (!$file_path || !file_exists($file_path)) {
            return false;
        }
        
        $path_info = pathinfo($file_path);
        $dir = $path_info['dirname'];
        $filename = $path_info['filename'];
        
        // Check for AVIF file
        $avif_path = $dir . '/' . $filename . '.avif';
        if (file_exists($avif_path)) {
            return true;
        }
        
        // Check for WebP file
        $webp_path = $dir . '/' . $filename . '.webp';
        if (file_exists($webp_path)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Calculate space saved for a specific image
     */
    public function calculate_image_savings($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        
        if (!$file_path || !file_exists($file_path)) {
            return 0;
        }
        
        $original_size = filesize($file_path);
        $path_info = pathinfo($file_path);
        $dir = $path_info['dirname'];
        $filename = $path_info['filename'];
        
        $smallest_size = $original_size;
        
        // Check AVIF file
        $avif_path = $dir . '/' . $filename . '.avif';
        if (file_exists($avif_path)) {
            $avif_size = filesize($avif_path);
            $smallest_size = min($smallest_size, $avif_size);
        }
        
        // Check WebP file
        $webp_path = $dir . '/' . $filename . '.webp';
        if (file_exists($webp_path)) {
            $webp_size = filesize($webp_path);
            $smallest_size = min($smallest_size, $webp_size);
        }
        
        return max(0, $original_size - $smallest_size);
    }
    
    /**
     * Get list of unoptimized images
     */
    public function get_unoptimized_images($limit = 100) {
        global $wpdb;
        
        // Get all image attachments
        $images = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_title, post_mime_type 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type IN ('image/jpeg', 'image/png')
            ORDER BY post_date DESC
            LIMIT %d
        ", $limit));
        
        if (empty($images)) {
            return array();
        }
        
        $unoptimized_images = array();
        
        foreach ($images as $image) {
            if (!$this->is_image_optimized($image->ID)) {
                $unoptimized_images[] = $image;
            }
        }
        
        return $unoptimized_images;
    }
    
    /**
     * Estimate optimization time
     */
    public function estimate_optimization_time($image_count) {
        $time_per_image = 2; // seconds per image (conservative estimate)
        $total_seconds = $image_count * $time_per_image;
        
        if ($total_seconds < 60) {
            return sprintf(__('%d seconds', 'tomatillo-media-studio'), $total_seconds);
        } elseif ($total_seconds < 3600) {
            return sprintf(__('%d minutes', 'tomatillo-media-studio'), round($total_seconds / 60));
        } else {
            return sprintf(__('%d hours', 'tomatillo-media-studio'), round($total_seconds / 3600));
        }
    }
    
    /**
     * Get recent optimizations
     */
    public function get_recent_optimizations($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$table_name} 
            ORDER BY optimization_date DESC 
            LIMIT %d
        ", $limit));
    }
    
    /**
     * Start bulk optimization
     */
    public function start_bulk_optimization() {
        // This will be implemented with background processing
        // For now, just log the action
        $this->log('Bulk optimization started', 'info');
    }
}
