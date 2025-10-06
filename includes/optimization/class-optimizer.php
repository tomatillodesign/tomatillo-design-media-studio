<?php
/**
 * Core image optimization engine
 * 
 * Handles AVIF/WebP conversion with configurable quality and savings thresholds
 */

if (!defined('ABSPATH')) {
    exit;
}

class Tomatillo_Optimizer {
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Supported image formats for conversion
     */
    private $supported_formats = array('image/jpeg', 'image/png');
    
    /**
     * Conversion results
     */
    private $conversion_results = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Get settings instance safely
     */
    private function get_settings() {
        if (!$this->settings && function_exists('tomatillo_media_studio')) {
            $plugin = tomatillo_media_studio();
            if ($plugin && isset($plugin->settings)) {
                $this->settings = $plugin->settings;
            }
        }
        return $this->settings;
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_tomatillo_test_conversion', array($this, 'ajax_test_conversion'));
        add_action('wp_ajax_tomatillo_convert_image', array($this, 'ajax_convert_image'));
        
        // Automatic conversion hooks
        add_action('add_attachment', array($this, 'auto_convert_on_upload'));
        add_action('wp_handle_upload', array($this, 'auto_convert_on_upload_handler'), 10, 2);
    }
    
    /**
     * Initialize optimizer
     */
    public function init() {
        // Log initialization
        $settings = $this->get_settings();
        if ($settings && $settings->is_debug_mode()) {
            error_log('Tomatillo Optimizer initialized');
        }
        
        // Check system capabilities
        $this->check_capabilities();
    }
    
    /**
     * Check system capabilities for image conversion
     */
    private function check_capabilities() {
        $capabilities = array(
            'gd_avif' => function_exists('imageavif'),
            'gd_webp' => function_exists('imagewebp'),
            'imagick' => class_exists('Imagick'),
            'memory_limit' => $this->get_memory_limit(),
        );
        
        // Log capabilities for debugging
        $settings = $this->get_settings();
        if ($settings && $settings->is_debug_mode()) {
            error_log('Tomatillo Optimizer Capabilities: ' . print_r($capabilities, true));
        }
        
        return $capabilities;
    }
    
    /**
     * Get PHP memory limit in bytes
     */
    private function get_memory_limit() {
        $limit = ini_get('memory_limit');
        if ($limit == -1) {
            return PHP_INT_MAX;
        }
        return wp_convert_hr_to_bytes($limit);
    }
    
    /**
     * Check if image should be processed
     */
    public function should_process_image($attachment_id) {
        $settings = $this->get_settings();
        if (!$settings) {
            return false;
        }
        
        // Check if optimization is enabled
        if (!$settings->is_optimization_enabled()) {
            return false;
        }
        
        // Get attachment info
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return false;
        }
        
        // Check file size
        $file_size = filesize($file_path);
        if ($settings->should_skip_small_images() && $file_size < $settings->get_min_image_size()) {
            return false;
        }
        
        // Check image dimensions
        $image_info = getimagesize($file_path);
        if (!$image_info) {
            return false;
        }
        
        $max_dimensions = $settings->get_max_image_dimensions();
        if ($image_info[0] > $max_dimensions || $image_info[1] > $max_dimensions) {
            return false;
        }
        
        // Check MIME type
        $mime_type = get_post_mime_type($attachment_id);
        if (!in_array($mime_type, $this->supported_formats)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Convert image to AVIF/WebP formats
     */
    public function convert_image($attachment_id) {
        // Get plugin instance for logging
        $plugin = tomatillo_media_studio();
        if (!$plugin) {
            return array(
                'success' => false,
                'message' => 'Plugin not available',
                'attachment_id' => $attachment_id
            );
        }
        
        $plugin->core->log('info', "convert_image called for attachment ID: {$attachment_id}");
        
        if (!$this->should_process_image($attachment_id)) {
            $plugin->core->log('warning', "Image does not meet processing criteria for ID: {$attachment_id}");
            return array(
                'success' => false,
                'message' => 'Image does not meet processing criteria',
                'attachment_id' => $attachment_id
            );
        }
        
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            $plugin->core->log('error', "File path not found or file does not exist for ID: {$attachment_id} - Path: {$file_path}");
            return array(
                'success' => false,
                'message' => 'Image file not found',
                'attachment_id' => $attachment_id
            );
        }
        
        $original_size = filesize($file_path);
        $plugin->core->log('info', "Original file size: {$original_size} bytes for ID: {$attachment_id}");
        
        $results = array(
            'attachment_id' => $attachment_id,
            'original_path' => $file_path,
            'original_size' => $original_size,
            'avif_path' => null,
            'webp_path' => null,
            'avif_size' => null,
            'webp_size' => null,
            'avif_savings' => 0,
            'webp_savings' => 0,
            'success' => false,
            'message' => ''
        );
        
        $settings = $this->get_settings();
        if (!$settings) {
            $plugin->core->log('error', 'Settings not available for optimization');
            $results['message'] = 'Settings not available';
            return $results;
        }
        
        $avif_enabled = $settings->is_avif_enabled() ? 'yes' : 'no';
        $webp_enabled = $settings->is_webp_enabled() ? 'yes' : 'no';
        $plugin->core->log('info', "Settings loaded - AVIF enabled: {$avif_enabled}, WebP enabled: {$webp_enabled}");
        
        try {
            // Set timeout for conversion
            set_time_limit($settings->get_conversion_timeout());
            
            // Convert to AVIF if enabled
            if ($settings->is_avif_enabled()) {
                $plugin->core->log('info', "🔄 Converting to AVIF for ID: {$attachment_id}");
                $avif_result = $this->convert_to_avif($file_path, $attachment_id);
                if ($avif_result['success']) {
                    $plugin->core->log('info', "✅ AVIF conversion successful: " . round($this->calculate_savings_percentage($original_size, $avif_result['size'])) . "% savings");
                    $results['avif_path'] = $avif_result['path'];
                    $results['avif_size'] = $avif_result['size'];
                    $results['avif_savings'] = $this->calculate_savings_percentage($original_size, $avif_result['size']);
                    // Save convenience meta for fast frontend lookups
                    update_post_meta($attachment_id, '_tomatillo_avif_url', $this->path_to_url($avif_result['path']));
                } else {
                    $plugin->core->log('warning', "❌ AVIF conversion failed");
                }
            }
            
            // Convert to WebP if enabled
            if ($settings->is_webp_enabled()) {
                $plugin->core->log('info', "🔄 Converting to WebP for ID: {$attachment_id}");
                $webp_result = $this->convert_to_webp($file_path, $attachment_id);
                if ($webp_result['success']) {
                    $plugin->core->log('info', "✅ WebP conversion successful: " . round($this->calculate_savings_percentage($original_size, $webp_result['size'])) . "% savings");
                    $results['webp_path'] = $webp_result['path'];
                    $results['webp_size'] = $webp_result['size'];
                    $results['webp_savings'] = $this->calculate_savings_percentage($original_size, $webp_result['size']);
                    // Save convenience meta for fast frontend lookups
                    update_post_meta($attachment_id, '_tomatillo_webp_url', $this->path_to_url($webp_result['path']));
                } else {
                    $plugin->core->log('warning', "❌ WebP conversion failed");
                }
            }
            
            // Check if conversion meets minimum savings threshold
            $best_savings = max($results['avif_savings'], $results['webp_savings']);
            $min_threshold = $settings->get_min_savings_threshold();
            
            $plugin->core->log('info', "Best savings: {$best_savings}%, Min threshold: {$min_threshold}%");
            
            if ($best_savings < $min_threshold) {
                $plugin->core->log('info', "⚠️ Savings below threshold ({$best_savings}% < {$min_threshold}%), but keeping files");
                // Don't clean up files - keep them for future use
                // Just mark as skipped due to threshold
                $results['success'] = true; // Still mark as successful
                $results['skipped'] = true; // But mark as skipped
                $results['message'] = sprintf(
                    'Image optimized but below threshold. AVIF %d%% savings, WebP %d%% savings (minimum %d%% required)',
                    round($results['avif_savings']),
                    round($results['webp_savings']),
                    $min_threshold
                );
                $plugin->core->log('info', "📊 Threshold decision: {$results['message']}");
            } else {
                $results['success'] = true;
                $results['message'] = sprintf(
                    'Successfully converted: AVIF %d%% savings, WebP %d%% savings',
                    round($results['avif_savings']),
                    round($results['webp_savings'])
                );
                $plugin->core->log('info', "✅ {$results['message']}");
            }
            
        } catch (Exception $e) {
            $plugin->core->log('error', "Exception during conversion: {$e->getMessage()}");
            $results['message'] = 'Conversion failed: ' . $e->getMessage();
            
            // Clean up any partial files
            if ($results['avif_path'] && file_exists($results['avif_path'])) {
                unlink($results['avif_path']);
            }
            if ($results['webp_path'] && file_exists($results['webp_path'])) {
                unlink($results['webp_path']);
            }
        }
        
        return $results;
    }
    
    /**
     * Convert image to AVIF format
     */
    private function convert_to_avif($file_path, $attachment_id) {
        $result = array('success' => false, 'path' => null, 'size' => null);
        
        // Try GD first (PHP 8.1+)
        if (function_exists('imageavif')) {
            $result = $this->convert_with_gd($file_path, 'avif', $attachment_id);
        }
        // Fallback to Imagick
        elseif (class_exists('Imagick')) {
            $result = $this->convert_with_imagick($file_path, 'avif', $attachment_id);
        }
        
        return $result;
    }
    
    /**
     * Convert image to WebP format
     */
    private function convert_to_webp($file_path, $attachment_id) {
        $result = array('success' => false, 'path' => null, 'size' => null);
        
        // Try GD first
        if (function_exists('imagewebp')) {
            $result = $this->convert_with_gd($file_path, 'webp', $attachment_id);
        }
        // Fallback to Imagick
        elseif (class_exists('Imagick')) {
            $result = $this->convert_with_imagick($file_path, 'webp', $attachment_id);
        }
        
        return $result;
    }
    
    /**
     * Convert image using GD extension
     */
    private function convert_with_gd($file_path, $format, $attachment_id) {
        $result = array('success' => false, 'path' => null, 'size' => null);
        
        try {
            // Load image based on original format
            $image_info = getimagesize($file_path);
            $mime_type = $image_info['mime'];
            
            switch ($mime_type) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($file_path);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($file_path);
                    break;
                default:
                    return $result;
            }
            
            if (!$source) {
                return $result;
            }
            
            // Generate output path
            $output_path = $this->generate_output_path($file_path, $format);
            
            // Set quality
            $settings = $this->get_settings();
            $quality = $format === 'avif' ? $settings->get_avif_quality() : $settings->get_webp_quality();
            
            // Convert and save
            $success = false;
            if ($format === 'avif') {
                $success = imageavif($source, $output_path, $quality);
            } elseif ($format === 'webp') {
                $success = imagewebp($source, $output_path, $quality);
            }
            
            if ($success && file_exists($output_path)) {
                $result['success'] = true;
                $result['path'] = $output_path;
                $result['size'] = filesize($output_path);
            }
            
            imagedestroy($source);
            
        } catch (Exception $e) {
            $settings = $this->get_settings();
            if ($settings && $settings->is_debug_mode()) {
                error_log('GD Conversion Error: ' . $e->getMessage());
            }
        }
        
        return $result;
    }
    
    /**
     * Convert image using Imagick
     */
    private function convert_with_imagick($file_path, $format, $attachment_id) {
        $result = array('success' => false, 'path' => null, 'size' => null);
        
        try {
            $imagick = new Imagick($file_path);
            
            // Set quality
            $settings = $this->get_settings();
            $quality = $format === 'avif' ? $settings->get_avif_quality() : $settings->get_webp_quality();
            $imagick->setImageCompressionQuality($quality);
            
            // Generate output path
            $output_path = $this->generate_output_path($file_path, $format);
            
            // Convert format
            if ($format === 'avif') {
                $imagick->setImageFormat('avif');
            } elseif ($format === 'webp') {
                $imagick->setImageFormat('webp');
            }
            
            // Write image
            $success = $imagick->writeImage($output_path);
            
            if ($success && file_exists($output_path)) {
                $result['success'] = true;
                $result['path'] = $output_path;
                $result['size'] = filesize($output_path);
            }
            
            $imagick->destroy();
            
        } catch (Exception $e) {
            $settings = $this->get_settings();
            if ($settings && $settings->is_debug_mode()) {
                error_log('Imagick Conversion Error: ' . $e->getMessage());
            }
        }
        
        return $result;
    }
    
    /**
     * Generate output path for converted image
     */
    private function generate_output_path($original_path, $format) {
        $path_info = pathinfo($original_path);
        $directory = $path_info['dirname'];
        $filename = $path_info['filename'];
        
        return $directory . '/' . $filename . '.' . $format;
    }

    /**
     * Convert absolute path within uploads to public URL
     */
    private function path_to_url($path) {
        $upload_dir = wp_upload_dir();
        if (strpos($path, $upload_dir['basedir']) === 0) {
            return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $path);
        }
        return $path; // fallback
    }
    
    /**
     * Calculate savings percentage
     */
    private function calculate_savings_percentage($original_size, $converted_size) {
        if ($original_size <= 0) {
            return 0;
        }
        
        $savings = (($original_size - $converted_size) / $original_size) * 100;
        return max(0, $savings);
    }
    
    /**
     * AJAX handler for testing conversion
     */
    public function ajax_test_conversion() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tomatillo_media_studio')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $attachment_id = intval($_POST['attachment_id']);
        if (!$attachment_id) {
            wp_send_json_error('Invalid attachment ID');
        }
        
        $result = $this->convert_image($attachment_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler for manual conversion
     */
    public function ajax_convert_image() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tomatillo_media_studio')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $attachment_id = intval($_POST['attachment_id']);
        if (!$attachment_id) {
            wp_send_json_error('Invalid attachment ID');
        }
        
        $result = $this->convert_image($attachment_id);
        
        // Store result in database if successful
        if ($result['success']) {
            $this->store_conversion_result($result);
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Store conversion result in database
     */
    private function store_conversion_result($result) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
        
        // Debug: Log the result data
        $plugin = tomatillo_media_studio();
        if ($plugin && $plugin->core) {
            $plugin->core->log('info', "Storing conversion result: " . json_encode($result));
        }
        
        $data = array(
            'attachment_id' => $result['attachment_id'],
            'original_format' => pathinfo($result['original_path'], PATHINFO_EXTENSION),
            'avif_path' => $result['avif_path'],
            'webp_path' => $result['webp_path'],
            'original_size' => $result['original_size'],
            'avif_size' => $result['avif_size'],
            'webp_size' => $result['webp_size'],
            'status' => 'completed'
        );
        
        $insert_result = $wpdb->insert($table_name, $data);
        
        // Debug: Log the insert result
        if ($plugin && $plugin->core) {
            if ($insert_result === false) {
                $plugin->core->log('error', "Database insert failed: " . $wpdb->last_error);
            } else {
                $plugin->core->log('info', "Database insert successful, ID: " . $wpdb->insert_id);
            }
        }
    }
    
    /**
     * Get system capabilities for display
     */
    public function get_capabilities() {
        return $this->check_capabilities();
    }
    
    /**
     * Get conversion statistics
     */
    public function get_conversion_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
        
        $stats = array(
            'total_conversions' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}"),
            'avif_conversions' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE avif_path IS NOT NULL"),
            'webp_conversions' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE webp_path IS NOT NULL"),
            'total_space_saved' => $wpdb->get_var("
                SELECT SUM(original_size - LEAST(COALESCE(avif_size, original_size), COALESCE(webp_size, original_size))) 
                FROM {$table_name}
            "),
            'average_savings' => 0
        );
        
        // Calculate average savings
        if ($stats['total_conversions'] > 0) {
            $total_original = $wpdb->get_var("SELECT SUM(original_size) FROM {$table_name}");
            if ($total_original > 0) {
                $stats['average_savings'] = (($total_original - $stats['total_space_saved']) / $total_original) * 100;
            }
        }
        
        return $stats;
    }
    
    /**
     * Automatically convert image when uploaded via WordPress media library
     */
    public function auto_convert_on_upload($attachment_id) {
        $settings = $this->get_settings();
        if (!$settings || !$settings->is_optimization_enabled()) {
            return;
        }
        
        // Check if auto-convert is enabled
        if (!$settings->get('auto_convert', true)) {
            return;
        }
        
        // Only process images
        $mime_type = get_post_mime_type($attachment_id);
        if (!in_array($mime_type, $this->supported_formats)) {
            return;
        }
        
        // Log for debugging
        $plugin = tomatillo_media_studio();
        if ($plugin && $plugin->core) {
            $plugin->core->log('info', "Auto-convert triggered for attachment ID: {$attachment_id}, MIME: {$mime_type}");
        }
        
        // Process conversion immediately instead of scheduling
        $this->process_immediate_conversion($attachment_id);
    }
    
    /**
     * Handle upload processing for drag and drop uploads
     */
    public function auto_convert_on_upload_handler($upload, $context) {
        // Log for debugging
        $plugin = tomatillo_media_studio();
        if ($plugin && $plugin->core) {
            $plugin->core->log('info', "Upload handler called with context: {$context}");
        }
        
        // Only process if this is a media library upload
        if ($context !== 'upload') {
            return $upload;
        }
        
        $settings = $this->get_settings();
        if (!$settings || !$settings->is_optimization_enabled()) {
            return $upload;
        }
        
        // Check if auto-convert is enabled
        if (!$settings->get('auto_convert', true)) {
            return $upload;
        }
        
        // Only process images
        if (!isset($upload['type']) || !in_array($upload['type'], $this->supported_formats)) {
            return $upload;
        }
        
        // Log for debugging
        if ($plugin && $plugin->core) {
            $plugin->core->log('info', "Processing upload: {$upload['type']}");
        }
        
        // Store upload info for immediate processing
        if (!isset($upload['error']) && isset($upload['file'])) {
            // Process conversion immediately after WordPress processes the attachment
            add_action('wp_generate_attachment_metadata', array($this, 'process_immediate_upload_conversion'), 10, 2);
        }
        
        return $upload;
    }
    
    /**
     * Schedule conversion for uploaded files
     */
    public function schedule_upload_conversion($metadata, $attachment_id) {
        // Remove the hook to prevent multiple calls
        remove_action('wp_generate_attachment_metadata', array($this, 'schedule_upload_conversion'));
        
        // Log for debugging
        $plugin = tomatillo_media_studio();
        if ($plugin && $plugin->core) {
            $plugin->core->log('info', "Scheduling upload conversion for attachment ID: {$attachment_id}");
        }
        
        // Schedule conversion
        wp_schedule_single_event(time() + 2, 'tomatillo_auto_convert_image', array($attachment_id));
        
        return $metadata;
    }
    
    /**
     * Process immediate conversion for uploaded files
     */
    public function process_immediate_upload_conversion($metadata, $attachment_id) {
        // Remove the hook to prevent multiple calls
        remove_action('wp_generate_attachment_metadata', array($this, 'process_immediate_upload_conversion'));
        
        // Log for debugging
        $plugin = tomatillo_media_studio();
        if ($plugin && $plugin->core) {
            $plugin->core->log('info', "Processing immediate upload conversion for attachment ID: {$attachment_id}");
        }
        
        // Process conversion immediately
        $this->process_immediate_conversion($attachment_id);
        
        return $metadata;
    }
    
    /**
     * Process conversion immediately (replaces scheduled conversion)
     */
    public function process_immediate_conversion($attachment_id) {
        $settings = $this->get_settings();
        if (!$settings || !$settings->is_optimization_enabled()) {
            return;
        }
        
        // Get plugin instance for logging
        $plugin = tomatillo_media_studio();
        if (!$plugin) {
            return;
        }
        
        $plugin->core->log('info', "🔄 Processing immediate conversion for attachment ID: {$attachment_id}");
        
        // Convert the image
        $result = $this->convert_image($attachment_id);
        
        $plugin->core->log('info', "Conversion result: " . json_encode($result));
        
        if ($result['success']) {
            // Store result in database
            $this->store_conversion_result($result);
            $plugin->core->log('info', "✅ Immediate conversion completed for ID: {$attachment_id}");
        } else {
            $plugin->core->log('warning', "❌ Immediate conversion failed for ID: {$attachment_id} - {$result['message']}");
        }
    }
    
    /**
     * Process scheduled conversion
     */
    public function process_scheduled_conversion($attachment_id) {
        $settings = $this->get_settings();
        if (!$settings || !$settings->is_optimization_enabled()) {
            return;
        }
        
        // Get plugin instance for logging
        $plugin = tomatillo_media_studio();
        if (!$plugin) {
            return;
        }
        
        $plugin->core->log('info', "🔄 Processing scheduled conversion for attachment ID: {$attachment_id}");
        
        // Convert the image
        $result = $this->convert_image($attachment_id);
        
        $plugin->core->log('info', "Conversion result: " . json_encode($result));
        
        if ($result['success']) {
            // Store result in database
            $this->store_conversion_result($result);
            $plugin->core->log('info', "✅ Auto-conversion completed for ID: {$attachment_id}");
        } else {
            $plugin->core->log('warning', "❌ Auto-conversion failed for ID: {$attachment_id} - {$result['message']}");
        }
    }
}
