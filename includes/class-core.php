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
    
    private $initialized = false;
    
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
        add_action('wp_ajax_tomatillo_load_more_images', array($this, 'ajax_load_more_images'));
        add_action('wp_ajax_tomatillo_optimize_image', array($this, 'ajax_optimize_image'));
        add_action('wp_ajax_tomatillo_upload_files', array($this, 'ajax_upload_files'));
        add_action('wp_ajax_tomatillo_upload_single_file', array($this, 'ajax_upload_single_file'));
        add_action('wp_ajax_tomatillo_debug_image', array($this, 'ajax_debug_image'));
        add_action('wp_ajax_tomatillo_fix_image_database', array($this, 'ajax_fix_image_database'));
        add_action('wp_ajax_tomatillo_trigger_scheduled_conversion', array($this, 'ajax_trigger_scheduled_conversion'));
        add_action('wp_ajax_tomatillo_get_image_data', array($this, 'ajax_get_image_data'));
        add_action('wp_ajax_tomatillo_save_image_metadata', array($this, 'ajax_save_image_metadata'));
        add_action('wp_ajax_tomatillo_delete_image', array($this, 'ajax_delete_image'));
        add_action('wp_ajax_tomatillo_delete_images', array($this, 'ajax_delete_images'));
        add_action('wp_ajax_tomatillo_download_file', array($this, 'ajax_download_file'));
        
        // Scheduled conversion hook
        add_action('tomatillo_auto_convert_image', array($this, 'handle_scheduled_conversion'));
    }
    
    /**
     * Initialize core functionality
     */
    public function init() {
        // Only log initialization once
        if (!$this->initialized) {
            $this->log('info', 'Media Studio Core initialized');
            $this->initialized = true;
        }
        
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
        
        // Total file size (calculate by checking actual files)
        $total_size = 0;
        $images = $wpdb->get_results("
            SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
        ");
        
        foreach ($images as $image) {
            $file_path = get_attached_file($image->ID);
            if ($file_path && file_exists($file_path)) {
                $total_size += filesize($file_path);
            }
        }
        
        $stats['total_size'] = $total_size;
        
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
            'avif_space_saved' => 0,
            'webp_space_saved' => 0,
            'total_space_saved' => 0,
            'average_savings' => 0,
            'pending_optimizations' => 0
        );
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
        
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
        
        // AVIF and WebP conversions (count by checking actual files with proper base filename)
        $avif_count = 0;
        $webp_count = 0;
        $avif_space_saved = 0;
        $webp_space_saved = 0;
        
        if ($total_images > 0) {
            $images = $wpdb->get_results("
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_type = 'attachment' 
                AND post_mime_type IN ('image/jpeg', 'image/png')
            ");
            
            foreach ($images as $image) {
                $file_path = get_attached_file($image->ID);
                if ($file_path && file_exists($file_path)) {
                    // Use the same base filename logic as the optimizer
                    $path_info = pathinfo($file_path);
                    $base_filename = preg_replace('/-\d+x\d+$/', '', str_replace('-scaled', '', $path_info['filename']));
                    $directory = $path_info['dirname'];
                    
                    $avif_path = $directory . '/' . $base_filename . '.avif';
                    $webp_path = $directory . '/' . $base_filename . '.webp';
                    
                    if (file_exists($avif_path)) {
                        $avif_count++;
                        // Calculate space saved for AVIF (compare against WordPress scaled version)
                        $original_size = filesize($file_path);
                        $avif_size = filesize($avif_path);
                        if ($original_size > $avif_size) {
                            $avif_space_saved += ($original_size - $avif_size);
                        }
                    }
                    if (file_exists($webp_path)) {
                        $webp_count++;
                        // Calculate space saved for WebP (compare against WordPress scaled version)
                        $original_size = filesize($file_path);
                        $webp_size = filesize($webp_path);
                        if ($original_size > $webp_size) {
                            $webp_space_saved += ($original_size - $webp_size);
                        }
                    }
                }
            }
        }
        
        $stats['avif_conversions'] = $avif_count;
        $stats['webp_conversions'] = $webp_count;
        $stats['avif_space_saved'] = $avif_space_saved;
        $stats['webp_space_saved'] = $webp_space_saved;
        
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
        // Only log important events, not routine operations
        $important_levels = array('error', 'warning');
        $important_messages = array('optimization', 'conversion', 'error', 'failed', 'success');
        
        // Skip routine info messages unless they contain important keywords
        if ($level === 'info' && !in_array($level, $important_levels)) {
            $is_important = false;
            foreach ($important_messages as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    $is_important = true;
                    break;
                }
            }
            if (!$is_important) {
                return; // Skip routine info messages
            }
        }
        
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
        
        try {
            // Ensure log file doesn't get too large (max 1MB)
            if (file_exists($log_file) && filesize($log_file) > 1048576) {
                // Keep only last 500 lines
                $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines !== false) {
                    $lines = array_slice($lines, -500);
                    file_put_contents($log_file, implode("\n", $lines) . "\n");
                }
            }
            
            // Write the log entry
            $result = file_put_contents($log_file, $message . "\n", FILE_APPEND | LOCK_EX);
            
            if ($result === false) {
                error_log('Tomatillo Media Studio: Failed to write to log file');
            }
            
        } catch (Exception $e) {
            error_log('Tomatillo Media Studio: Log file error - ' . $e->getMessage());
        }
    }
    
    /**
     * Get plugin logs
     */
    public function get_plugin_logs() {
        $log_file = WP_CONTENT_DIR . '/tomatillo-media-studio.log';
        
        if (!file_exists($log_file)) {
            // Create a test log entry if no log file exists
            $this->log('info', 'Plugin logs initialized - no existing log file found');
            return array();
        }
        
        try {
            $logs = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            if ($logs === false) {
                return array();
            }
            
            $logs = array_slice($logs, -100); // Get last 100 entries
            $parsed_logs = array();
            
            foreach ($logs as $log_line) {
                // Parse log format: [timestamp] LEVEL message
                if (preg_match('/^\[([^\]]+)\]\s+(\w+)\s+(.+)$/', $log_line, $matches)) {
                    $parsed_logs[] = array(
                        'timestamp' => $matches[1],
                        'level' => strtolower($matches[2]),
                        'message' => $matches[3]
                    );
                } else {
                    // Fallback for malformed log entries
                    $parsed_logs[] = array(
                        'timestamp' => date('Y-m-d H:i:s'),
                        'level' => 'info',
                        'message' => $log_line
                    );
                }
            }
            
            // Reverse array to show newest first
            return array_reverse($parsed_logs);
            
        } catch (Exception $e) {
            error_log('Tomatillo Media Studio: Error reading log file - ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Clear plugin logs
     */
    public function clear_plugin_logs() {
        $log_file = WP_CONTENT_DIR . '/tomatillo-media-studio.log';
        
        try {
            if (file_exists($log_file)) {
                $result = file_put_contents($log_file, '');
                if ($result === false) {
                    error_log('Tomatillo Media Studio: Failed to clear log file');
                }
            }
        } catch (Exception $e) {
            error_log('Tomatillo Media Studio: Error clearing log file - ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for loading more images
     */
    public function ajax_load_more_images() {
        // Verify nonce
        if (!wp_verify_nonce($_GET['nonce'], 'tomatillo_load_more_images')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $page = intval($_GET['page']);
        $images_per_page = 20;
        $offset = ($page - 1) * $images_per_page;
        
        // Get images
        $images = get_posts(array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => $images_per_page,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_wp_attachment_metadata',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        $has_more = count($images) === $images_per_page;
        $html = '';
        
        if (!empty($images)) {
            $plugin = tomatillo_media_studio();
            $settings = $plugin->settings;
            
            foreach ($images as $image) {
                $image_url = wp_get_attachment_image_url($image->ID, 'large');
                $image_alt = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
                $image_title = $image->post_title ?: $image->post_name;
                $image_date = $image->post_date;
                $file_size = filesize(get_attached_file($image->ID));
                $file_size_formatted = size_format($file_size);
                
                // Check if optimized
                $is_optimized = $this->is_image_optimized($image->ID);
                
                $html .= '<div class="gallery-item" data-id="' . $image->ID . '">';
                $html .= '<div class="image-container">';
                $html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '" loading="lazy" class="gallery-image">';
                $html .= '<div class="image-overlay">';
                $html .= '<div class="overlay-content">';
                $html .= '<div class="image-info">';
                $html .= '<h4 class="image-title">' . esc_html($image_title) . '</h4>';
                $html .= '<p class="image-meta">' . date('M j, Y', strtotime($image_date)) . ' • ' . $file_size_formatted . '</p>';
                $html .= '</div>';
                $html .= '<div class="image-actions">';
                $html .= '<button class="action-btn view-btn" title="View"><span>👁️</span></button>';
                $html .= '<button class="action-btn edit-btn" title="Edit"><span>✏️</span></button>';
                $html .= '<button class="action-btn download-btn" title="Download"><span>⬇️</span></button>';
                if (!$is_optimized && $settings->is_optimization_enabled()) {
                    $html .= '<button class="action-btn optimize-btn" title="Optimize"><span>⚡</span></button>';
                }
                $html .= '</div>';
                $html .= '</div>';
                if ($is_optimized) {
                    $html .= '<div class="optimization-badge"><span class="badge optimized">✓ Optimized</span></div>';
                } elseif ($settings->is_optimization_enabled()) {
                    $html .= '<div class="optimization-badge"><span class="badge pending">⚡ Optimize</span></div>';
                }
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }
        
        wp_send_json_success(array(
            'html' => $html,
            'has_more' => $has_more
        ));
    }
    
    /**
     * AJAX handler for optimizing a single image
     */
    public function ajax_optimize_image() {
        // Get the plugin instance for logging
        $plugin = tomatillo_media_studio();
        if (!$plugin) {
            wp_send_json_error('Plugin not available');
        }
        
        // Log optimization attempt
        $plugin->core->log('info', 'Image optimization attempt started');
        
        // Verify nonce
        if (!wp_verify_nonce($_GET['nonce'], 'tomatillo_optimize_image')) {
            $plugin->core->log('warning', 'Invalid nonce for optimization request');
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('upload_files')) {
            $plugin->core->log('warning', 'Insufficient permissions for optimization request');
            wp_send_json_error('Insufficient permissions');
        }
        
        $image_id = intval($_GET['image_id']);
        $plugin->core->log('info', "Starting optimization for image ID: {$image_id}");
        
        if (!$image_id) {
            $plugin->core->log('warning', 'Invalid image ID provided for optimization');
            wp_send_json_error('Invalid image ID');
        }
        
        // Check if image exists
        $image = get_post($image_id);
        if (!$image || $image->post_type !== 'attachment') {
            $plugin->core->log('warning', "Image not found or not an attachment: {$image_id}");
            wp_send_json_error('Image not found');
        }
        
        // Get image filename for logging
        $file_path = get_attached_file($image_id);
        $filename = $file_path ? basename($file_path) : 'Unknown';
        $plugin->core->log('info', "Optimizing image: {$filename} (ID: {$image_id})");
        
        // Get the optimizer
        if (!$plugin->optimization) {
            $plugin->core->log('error', 'Optimization module not available');
            wp_send_json_error('Optimization module not available');
        }
        
        // Optimize the image
        $result = $plugin->optimization->convert_image($image_id);
        
        // Always store the result in database (success, skipped, or failed)
        $plugin->optimization->store_conversion_result($result);
        
        if ($result && $result['success']) {
            $savings = isset($result['savings']) ? $result['savings'] : 0;
            $plugin->core->log('info', "✅ Image optimization successful: {$filename} (ID: {$image_id}) - Savings: {$savings}%");
            wp_send_json_success(array(
                'message' => 'Image optimized successfully',
                'savings' => $savings
            ));
        } else {
            $error_message = $result['error'] ?? $result['message'] ?? 'Failed to optimize image';
            
            // Check if it's a threshold issue (not a real failure)
            if (strpos($error_message, 'well-optimized') !== false || strpos($error_message, 'additional savings') !== false) {
                $plugin->core->log('info', "⚠️ Image optimization skipped (threshold): {$filename} (ID: {$image_id}) - {$error_message}");
            } else {
                $plugin->core->log('warning', "❌ Image optimization failed: {$filename} (ID: {$image_id}) - {$error_message}");
            }
            
            wp_send_json_error($error_message);
        }
    }
    
    /**
     * AJAX handler for uploading images
     */
    public function ajax_upload_files() {
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!isset($_FILES['files'])) {
            wp_send_json_error('No files uploaded');
        }
        
        $uploaded_files = array();
        $files = $_FILES['files'];
        
        // Handle multiple files
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                $file = array(
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                );
                
                $upload_result = wp_handle_upload($file, array('test_form' => false));
                
                if ($upload_result && !isset($upload_result['error'])) {
                    $attachment = array(
                        'post_mime_type' => $upload_result['type'],
                        'post_title' => sanitize_file_name(pathinfo($upload_result['file'], PATHINFO_FILENAME)),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    
                    $attachment_id = wp_insert_attachment($attachment, $upload_result['file']);
                    
                    if (!is_wp_error($attachment_id)) {
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_result['file']);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);
                        
                        // Trigger automatic conversion for AJAX uploads
                        $this->trigger_auto_conversion($attachment_id);
                        
                        $uploaded_files[] = $attachment_id;
                    }
                }
            }
        }
        
        wp_send_json_success(array(
            'message' => count($uploaded_files) . ' images uploaded successfully',
            'uploaded_count' => count($uploaded_files)
        ));
    }
    
    /**
     * AJAX handler for uploading a single file with progress tracking
     */
    public function ajax_upload_single_file() {
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!isset($_FILES['file'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $file = $_FILES['file'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('File upload error: ' . $file['error']);
        }
        
        // Check file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/avif');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error('Invalid file type. Only images are allowed.');
        }
        
        // Check file size (20MB limit)
        if ($file['size'] > 20 * 1024 * 1024) {
            wp_send_json_error('File too large. Maximum size is 20MB.');
        }
        
        try {
            // Handle upload
            $upload_result = wp_handle_upload($file, array('test_form' => false));
            
            if ($upload_result && !isset($upload_result['error'])) {
                // Create attachment
                $attachment = array(
                    'post_mime_type' => $upload_result['type'],
                    'post_title' => sanitize_file_name(pathinfo($upload_result['file'], PATHINFO_FILENAME)),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                
                $attachment_id = wp_insert_attachment($attachment, $upload_result['file']);
                
                if (!is_wp_error($attachment_id)) {
                    // Generate metadata
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_result['file']);
                    wp_update_attachment_metadata($attachment_id, $attachment_data);
                    
                    // Trigger automatic conversion
                    $this->trigger_auto_conversion($attachment_id);
                    
                    // Get file info for response
                    $file_size = filesize($upload_result['file']);
                    $file_size_formatted = size_format($file_size);
                    
                    wp_send_json_success(array(
                        'attachment_id' => $attachment_id,
                        'filename' => basename($upload_result['file']),
                        'file_size' => $file_size,
                        'file_size_formatted' => $file_size_formatted,
                        'message' => 'File uploaded successfully'
                    ));
                } else {
                    wp_send_json_error('Failed to create attachment: ' . $attachment_id->get_error_message());
                }
            } else {
                wp_send_json_error('Upload failed: ' . ($upload_result['error'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            wp_send_json_error('Upload exception: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for debugging image optimization
     */
    public function ajax_debug_image() {
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $image_id = intval($_GET['image_id']);
        if (!$image_id) {
            wp_send_json_error('Invalid image ID');
        }
        
        $debug_info = array(
            'image_id' => $image_id,
            'checks' => array(),
            'files' => array(),
            'database' => array(),
            'settings' => array()
        );
        
        // Check if image exists
        $image = get_post($image_id);
        if (!$image || $image->post_type !== 'attachment') {
            $debug_info['checks']['image_exists'] = false;
            wp_send_json_success($debug_info);
        }
        $debug_info['checks']['image_exists'] = true;
        
        // Get file path
        $file_path = get_attached_file($image_id);
        $debug_info['files']['original_path'] = $file_path;
        $debug_info['files']['original_exists'] = file_exists($file_path);
        
        if ($file_path) {
            $debug_info['files']['original_size'] = file_exists($file_path) ? filesize($file_path) : 0;
            
            // Check for AVIF/WebP files
            $path_info = pathinfo($file_path);
            $dir = $path_info['dirname'];
            $filename = $path_info['filename'];
            $base_filename = preg_replace('/-\d+x\d+$/', '', str_replace('-scaled', '', $filename));
            
            $avif_path = $dir . '/' . $base_filename . '.avif';
            $webp_path = $dir . '/' . $base_filename . '.webp';
            
            $debug_info['files']['avif_path'] = $avif_path;
            $debug_info['files']['avif_exists'] = file_exists($avif_path);
            $debug_info['files']['avif_size'] = file_exists($avif_path) ? filesize($avif_path) : 0;
            
            $debug_info['files']['webp_path'] = $webp_path;
            $debug_info['files']['webp_exists'] = file_exists($webp_path);
            $debug_info['files']['webp_size'] = file_exists($webp_path) ? filesize($webp_path) : 0;
        }
        
        // Check database records
        global $wpdb;
        $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
        
        $optimization_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE attachment_id = %d",
            $image_id
        ));
        
        $debug_info['database']['has_record'] = !empty($optimization_data);
        if ($optimization_data) {
            $debug_info['database']['status'] = $optimization_data->status;
            $debug_info['database']['avif_path'] = $optimization_data->avif_path;
            $debug_info['database']['webp_path'] = $optimization_data->webp_path;
            $debug_info['database']['avif_size'] = $optimization_data->avif_size;
            $debug_info['database']['webp_size'] = $optimization_data->webp_size;
        }
        
        // Check settings
        $plugin = tomatillo_media_studio();
        if ($plugin && $plugin->settings) {
            $settings = $plugin->settings;
            $debug_info['settings']['avif_enabled'] = $settings->is_avif_enabled();
            $debug_info['settings']['webp_enabled'] = $settings->is_webp_enabled();
            $debug_info['settings']['optimization_enabled'] = $settings->is_optimization_enabled();
            $debug_info['settings']['avif_quality'] = $settings->get_avif_quality();
            $debug_info['settings']['webp_quality'] = $settings->get_webp_quality();
        }
        
        // Check if image is considered optimized
        $debug_info['checks']['is_optimized'] = $this->is_image_optimized($image_id);
        $debug_info['checks']['checked_base'] = isset($base_filename) ? $base_filename : null;
        
        wp_send_json_success($debug_info);
    }
    
    /**
     * AJAX handler for fixing image database records
     */
    public function ajax_fix_image_database() {
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $image_id = intval($_GET['image_id']);
        if (!$image_id) {
            wp_send_json_error('Invalid image ID');
        }
        
        // Check if image exists
        $image = get_post($image_id);
        if (!$image || $image->post_type !== 'attachment') {
            wp_send_json_error('Image not found');
        }
        
        // Get file path
        $file_path = get_attached_file($image_id);
        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error('Original file not found');
        }
        
        $original_size = filesize($file_path);
        $path_info = pathinfo($file_path);
        $dir = $path_info['dirname'];
        $filename = $path_info['filename'];
        $base_filename = preg_replace('/-\d+x\d+$/', '', str_replace('-scaled', '', $filename));
        // Normalize to base filename (strip -scaled and -WxH suffixes)
        $base_filename = preg_replace('/-\d+x\d+$/', '', str_replace('-scaled', '', $filename));
        // Normalize to base filename (no -scaled or -WxH suffix)
        $base_filename = preg_replace('/-\d+x\d+$/', '', str_replace('-scaled', '', $filename));
        
        // Check for AVIF/WebP files (based on base filename)
        $avif_path = $dir . '/' . $base_filename . '.avif';
        $webp_path = $dir . '/' . $base_filename . '.webp';
        
        $avif_exists = file_exists($avif_path);
        $webp_exists = file_exists($webp_path);
        
        if (!$avif_exists && !$webp_exists) {
            wp_send_json_error('No optimized files found');
        }
        
        // Create database record
        global $wpdb;
        $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
        
        $data = array(
            'attachment_id' => $image_id,
            'original_format' => strtolower($path_info['extension']),
            'original_size' => $original_size,
            'avif_path' => $avif_exists ? $avif_path : null,
            'webp_path' => $webp_exists ? $webp_path : null,
            'avif_size' => $avif_exists ? filesize($avif_path) : null,
            'webp_size' => $webp_exists ? filesize($webp_path) : null,
            'status' => 'completed'
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result === false) {
            wp_send_json_error('Failed to insert database record');
        }
        
        wp_send_json_success(array(
            'message' => 'Database record created successfully',
            'avif_exists' => $avif_exists,
            'webp_exists' => $webp_exists
        ));
    }
    
    /**
     * AJAX handler for triggering scheduled conversion manually
     */
    public function ajax_trigger_scheduled_conversion() {
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $image_id = intval($_GET['image_id']);
        if (!$image_id) {
            wp_send_json_error('Invalid image ID');
        }
        
        // Get plugin instance
        $plugin = tomatillo_media_studio();
        if (!$plugin || !$plugin->optimization) {
            wp_send_json_error('Plugin or optimization module not available');
        }
        
        // Trigger the scheduled conversion manually
        $this->log('info', "Manually triggering scheduled conversion for image ID: {$image_id}");
        $plugin->optimization->process_scheduled_conversion($image_id);
        
        wp_send_json_success(array(
            'message' => 'Scheduled conversion triggered successfully',
            'image_id' => $image_id
        ));
    }
    
    /**
     * AJAX handler for getting image data
     */
    public function ajax_get_image_data() {
        // Verify nonce
        if (!wp_verify_nonce($_GET['nonce'], 'tomatillo_get_image_data')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $image_id = intval($_GET['image_id']);
        
        if (!$image_id) {
            wp_send_json_error('Invalid image ID');
        }
        
        $image = get_post($image_id);
        if (!$image || $image->post_type !== 'attachment') {
            wp_send_json_error('Image not found');
        }
        
        $metadata = wp_get_attachment_metadata($image_id);
        $file_path = get_attached_file($image_id);
        $file_size = $file_path ? size_format(filesize($file_path)) : 'Unknown';
        $filename = $file_path ? basename($file_path) : 'Unknown';
        
        // Check for optimized versions using the database
        $avif_url = $this->get_optimized_image_url($image_id, 'avif');
        $webp_url = $this->get_optimized_image_url($image_id, 'webp');

        // If DB has no paths yet, derive from filesystem using base filename
        if (!$avif_url || !$webp_url) {
            $pi = pathinfo($file_path);
            $dir = $pi['dirname'];
            $base = preg_replace('/-\d+x\d+$/', '', str_replace('-scaled', '', $pi['filename']));
            $upload_dir = wp_upload_dir();
            if (!$avif_url) {
                $avif_path = $dir . '/' . $base . '.avif';
                if (file_exists($avif_path)) {
                    $avif_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $avif_path);
                }
            }
            if (!$webp_url) {
                $webp_path = $dir . '/' . $base . '.webp';
                if (file_exists($webp_path)) {
                    $webp_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_path);
                }
            }
        }
        $is_optimized = $this->is_image_optimized($image_id);
        $space_saved = 0;
        
        // Get the best optimized image URL for display
        $best_image_url = $this->get_best_optimized_image_url($image_id, 'large');
        
        // Calculate space saved from database and get optimized file sizes
        $avif_file_size = 'Unknown';
        $webp_file_size = 'Unknown';
        $smallest_file_size = $file_size; // Default to original
        
        if ($is_optimized) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
            $optimization_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE attachment_id = %d AND status = 'completed'",
                $image_id
            ));
            
            if ($optimization_data) {
                $original_size = $optimization_data->original_size;
                
                // Get AVIF file size
                if ($optimization_data->avif_size > 0) {
                    $avif_file_size = size_format($optimization_data->avif_size);
                }
                
                // Get WebP file size
                if ($optimization_data->webp_size > 0) {
                    $webp_file_size = size_format($optimization_data->webp_size);
                }
                
                // Get smallest optimized size
                $smallest_optimized = min(
                    $optimization_data->avif_size ?: PHP_INT_MAX,
                    $optimization_data->webp_size ?: PHP_INT_MAX
                );
                if ($smallest_optimized < PHP_INT_MAX) {
                    $smallest_file_size = size_format($smallest_optimized);
                }
                
                $space_saved = max(0, $original_size - $smallest_optimized);
            }
        }
        
        // Get uploader info
        $uploader = get_userdata($image->post_author);
        $uploader_name = $uploader ? $uploader->display_name : 'Unknown';
        
        // Get the true original URL using WordPress function
        $original_url = wp_get_original_image_url($image_id);
        
        // Debug: Log what we're getting
        error_log("DEBUG: wp_get_original_image_url($image_id) returned: " . ($original_url ?: 'false'));
        
        if (!$original_url) {
            // Fallback to regular URL if original not available
            $original_url = wp_get_attachment_url($image_id);
            error_log("DEBUG: Fallback wp_get_attachment_url($image_id) returned: " . $original_url);
        }
        
        // Ensure we have the true original by removing any -scaled suffix
        if (strpos($original_url, '-scaled.') !== false) {
            $original_url = str_replace('-scaled.', '.', $original_url);
            error_log("DEBUG: Removed -scaled suffix, final URL: " . $original_url);
        }
        
        error_log("DEBUG: Final original_url being sent to frontend: " . $original_url);
        
        // Get thumbnail URL for PDFs and other files that might have thumbnails
        $thumbnail_url = wp_get_attachment_image_url($image_id, 'full');
        
        $data = array(
            'id' => $image_id,
            'title' => $image->post_title,
            'filename' => $filename,
            'alt_text' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
            'caption' => $image->post_excerpt,
            'description' => $image->post_content,
            'mime_type' => $image->post_mime_type,
            'dimensions' => isset($metadata['width'], $metadata['height']) ? $metadata['width'] . 'x' . $metadata['height'] : 'Unknown',
            'file_size' => $file_size,
            'date' => date('M j, Y', strtotime($image->post_date)),
            'uploader' => $uploader_name,
            'url' => $original_url,
            'thumbnail_url' => $thumbnail_url, // Thumbnail URL for PDFs and other files
            'best_image_url' => $best_image_url, // Smallest optimized image for display
            'avif_url' => $avif_url,
            'webp_url' => $webp_url,
            'avif_file_size' => $avif_file_size, // Actual AVIF file size
            'webp_file_size' => $webp_file_size, // Actual WebP file size
            'smallest_file_size' => $smallest_file_size, // Smallest optimized file size
            'space_saved' => size_format($space_saved),
            'is_optimized' => $is_optimized
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler for saving image metadata
     */
    public function ajax_save_image_metadata() {
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $image_id = intval($_POST['image_id']);
        
        if (!$image_id) {
            wp_send_json_error('Invalid image ID');
        }
        
        // Update post data
        $post_data = array(
            'ID' => $image_id,
            'post_title' => sanitize_text_field($_POST['title']),
            'post_excerpt' => sanitize_textarea_field($_POST['caption']),
            'post_content' => sanitize_textarea_field($_POST['description'])
        );
        
        $result = wp_update_post($post_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error('Failed to update post data');
        }
        
        // Update alt text
        update_post_meta($image_id, '_wp_attachment_image_alt', sanitize_text_field($_POST['alt_text']));
        
        wp_send_json_success('Metadata saved successfully');
    }
    
    /**
     * Delete optimized files (AVIF/WebP) for an attachment
     */
    private function delete_optimized_files($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return;
        }
        
        // Get base filename (strip -scaled and -WxH suffixes)
        $path_info = pathinfo($file_path);
        $base_filename = preg_replace('/-\d+x\d+$/', '', str_replace('-scaled', '', $path_info['filename']));
        $directory = $path_info['dirname'];
        
        // Delete AVIF file
        $avif_path = $directory . '/' . $base_filename . '.avif';
        if (file_exists($avif_path)) {
            unlink($avif_path);
            $this->log('info', "Deleted AVIF file: {$avif_path}");
        }
        
        // Delete WebP file
        $webp_path = $directory . '/' . $base_filename . '.webp';
        if (file_exists($webp_path)) {
            unlink($webp_path);
            $this->log('info', "Deleted WebP file: {$webp_path}");
        }
        
        // Delete database record
        global $wpdb;
        $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
        $wpdb->delete($table_name, array('attachment_id' => $attachment_id));
        
        // Delete convenience meta
        delete_post_meta($attachment_id, '_tomatillo_avif_url');
        delete_post_meta($attachment_id, '_tomatillo_webp_url');
    }
    
    /**
     * AJAX handler for deleting image
     */
    public function ajax_delete_image() {
        // Check permissions
        if (!current_user_can('delete_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $image_id = intval($_POST['image_id']);
        
        if (!$image_id) {
            wp_send_json_error('Invalid image ID');
        }
        
        // Delete optimized files before deleting the attachment
        $this->delete_optimized_files($image_id);
        
        $result = wp_delete_attachment($image_id, true);
        
        if (!$result) {
            wp_send_json_error('Failed to delete image');
        }
        
        wp_send_json_success('Image deleted successfully');
    }
    
    /**
     * AJAX handler for bulk deleting images
     */
    public function ajax_delete_images() {
        // Check permissions
        if (!current_user_can('delete_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tomatillo_delete_images')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $image_ids = json_decode(stripslashes($_POST['image_ids']), true);
        
        if (!is_array($image_ids) || empty($image_ids)) {
            wp_send_json_error('Invalid image IDs');
        }
        
        $deleted_count = 0;
        $errors = array();
        
        foreach ($image_ids as $image_id) {
            $image_id = intval($image_id);
            
            if (!$image_id) {
                $errors[] = "Invalid image ID: $image_id";
                continue;
            }
            
            // Check if image exists
            $attachment = get_post($image_id);
            if (!$attachment || $attachment->post_type !== 'attachment') {
                $errors[] = "Image not found: $image_id";
                continue;
            }
            
            // Delete optimized files before deleting the attachment
            $this->delete_optimized_files($image_id);
            
            $result = wp_delete_attachment($image_id, true);
            
            if ($result) {
                $deleted_count++;
                $this->log('info', "Bulk delete: Successfully deleted image ID $image_id");
            } else {
                $errors[] = "Failed to delete image: $image_id";
                $this->log('warning', "Bulk delete: Failed to delete image ID $image_id");
            }
        }
        
        if ($deleted_count > 0) {
            $message = "Successfully deleted $deleted_count image(s)";
            if (!empty($errors)) {
                $message .= ". " . count($errors) . " error(s) occurred.";
            }
            wp_send_json_success($message);
        } else {
            wp_send_json_error('No images were deleted. ' . implode(', ', $errors));
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
        // Normalize to base filename
        $base_filename = preg_replace('/-\d+x\d+$/', '', str_replace('-scaled', '', $filename));
        
        // Check for AVIF file
        $avif_path = $dir . '/' . $base_filename . '.avif';
        if (file_exists($avif_path)) {
            return true;
        }
        
        // Check for WebP file
        $webp_path = $dir . '/' . $base_filename . '.webp';
        if (file_exists($webp_path)) {
            return true;
        }
        
        // Debug: log paths checked when not optimized
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Tomatillo] is_image_optimized false for ID ' . $attachment_id . ' | checked: ' . $avif_path . ' and ' . $webp_path);
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
        $base_filename = preg_replace('/-\d+x\d+$/', '', str_replace('-scaled', '', $filename));
        
        $smallest_size = $original_size;
        
        // Check AVIF file
        $avif_path = $dir . '/' . $base_filename . '.avif';
        if (file_exists($avif_path)) {
            $avif_size = filesize($avif_path);
            $smallest_size = min($smallest_size, $avif_size);
        }
        
        // Check WebP file
        $webp_path = $dir . '/' . $base_filename . '.webp';
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
    
    /**
     * AJAX handler for downloading files
     */
    public function ajax_download_file() {
        // Verify nonce
        if (!wp_verify_nonce($_GET['nonce'], 'tomatillo_download_file')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_die('Insufficient permissions');
        }
        
        $file_id = intval($_GET['file_id']);
        if (!$file_id) {
            wp_die('Invalid file ID');
        }
        
        // Get file path
        $file_path = get_attached_file($file_id);
        if (!$file_path || !file_exists($file_path)) {
            wp_die('File not found');
        }
        
        // Get file info
        $file_name = basename($file_path);
        $file_type = get_post_mime_type($file_id);
        
        // Set headers for download
        header('Content-Type: ' . $file_type);
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Output file
        readfile($file_path);
        exit;
    }
    
    /**
     * Handle scheduled image conversion
     */
    public function handle_scheduled_conversion($attachment_id) {
        // Get plugin instance
        $plugin = tomatillo_media_studio();
        if (!$plugin || !$plugin->optimization) {
            return;
        }
        
        // Process the conversion
        $plugin->optimization->process_scheduled_conversion($attachment_id);
    }
    
    /**
     * Get optimized image URL for a specific format
     */
    public function get_optimized_image_url($attachment_id, $format) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
        
        // Get optimization data
        $optimization_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE attachment_id = %d AND status = 'completed'",
            $attachment_id
        ));
        
        if (!$optimization_data) {
            return null;
        }
        
        // Get the optimized file path based on format
        $optimized_path = null;
        if ($format === 'avif' && $optimization_data->avif_path) {
            $optimized_path = $optimization_data->avif_path;
        } elseif ($format === 'webp' && $optimization_data->webp_path) {
            $optimized_path = $optimization_data->webp_path;
        }
        
        if (!$optimized_path || !file_exists($optimized_path)) {
            return null;
        }
        
        // Convert file path to URL
        $upload_dir = wp_upload_dir();
        $optimized_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $optimized_path);
        
        return $optimized_url;
    }
    
    /**
     * Get the smallest optimized image URL available (AVIF → WebP → scaled original)
     */
    public function get_best_optimized_image_url($attachment_id, $size = 'large') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
        
        // Get optimization data
        $optimization_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE attachment_id = %d AND status = 'completed'",
            $attachment_id
        ));
        
        if (!$optimization_data) {
            // No optimization data, return WordPress scaled image
            return wp_get_attachment_image_url($attachment_id, $size);
        }
        
        $upload_dir = wp_upload_dir();
        $best_url = null;
        $smallest_size = PHP_INT_MAX;
        
        // Check AVIF (best compression)
        if ($optimization_data->avif_path && file_exists($optimization_data->avif_path)) {
            $avif_size = $optimization_data->avif_size ?: filesize($optimization_data->avif_path);
            if ($avif_size < $smallest_size) {
                $smallest_size = $avif_size;
                $best_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $optimization_data->avif_path);
            }
        }
        
        // Check WebP (good compression)
        if ($optimization_data->webp_path && file_exists($optimization_data->webp_path)) {
            $webp_size = $optimization_data->webp_size ?: filesize($optimization_data->webp_path);
            if ($webp_size < $smallest_size) {
                $smallest_size = $webp_size;
                $best_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $optimization_data->webp_path);
            }
        }
        
        // If we found an optimized version, return it
        if ($best_url) {
            return $best_url;
        }
        
        // Fallback to WordPress scaled image
        return wp_get_attachment_image_url($attachment_id, $size);
    }
    
    /**
     * Trigger automatic conversion for an attachment
     */
    public function trigger_auto_conversion($attachment_id) {
        // Get plugin instance
        $plugin = tomatillo_media_studio();
        if (!$plugin || !$plugin->optimization) {
            return;
        }
        
        // Log for debugging
        $this->log('info', "Triggering immediate conversion for attachment ID: {$attachment_id}");
        
        // Process conversion immediately instead of scheduling
        if ($plugin && $plugin->optimization) {
            $plugin->optimization->process_immediate_conversion($attachment_id);
        }
    }
    
    /**
     * Get optimization data for an image
     */
    public function get_optimization_data($attachment_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE attachment_id = %d AND status = 'completed'",
            $attachment_id
        ));
        
        if (!$data) {
            return null;
        }
        
        // Convert to array and add URLs
        $result = (array) $data;
        
        // Add URLs if files exist
        if (isset($data->avif_size) && $data->avif_size > 0) {
            $result['avif_url'] = $this->get_optimized_image_url($attachment_id, 'avif');
        }
        
        if (isset($data->webp_size) && $data->webp_size > 0) {
            $result['webp_url'] = $this->get_optimized_image_url($attachment_id, 'webp');
        }
        
        if (isset($data->scaled_size) && $data->scaled_size > 0) {
            $result['scaled_url'] = $this->get_optimized_image_url($attachment_id, 'scaled');
        }
        
        return $result;
    }
    
    /**
     * Optimize a single image (used by bulk operations)
     */
    public function optimize_image($attachment_id) {
        // Get plugin instance
        $plugin = tomatillo_media_studio();
        if (!$plugin || !$plugin->optimization) {
            return array(
                'success' => false,
                'error' => 'Optimization module not available'
            );
        }
        
        try {
            // Use the optimization module to convert the image
            $result = $plugin->optimization->convert_image($attachment_id);
            
            if ($result && isset($result['success']) && $result['success']) {
                // Calculate space saved
                $space_saved = $this->calculate_image_savings($attachment_id);
                $original_size = filesize(get_attached_file($attachment_id));
                $savings_percent = $original_size > 0 ? round(($space_saved / $original_size) * 100, 1) : 0;
                
                return array(
                    'success' => true,
                    'space_saved' => $space_saved,
                    'savings_percent' => $savings_percent,
                    'message' => $result['message'] ?? 'Image optimized successfully'
                );
            } else {
                return array(
                    'success' => false,
                    'error' => $result['error'] ?? $result['message'] ?? 'Optimization failed'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            );
        } catch (Error $e) {
            return array(
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            );
        }
    }
}
