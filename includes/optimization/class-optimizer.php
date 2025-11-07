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
        
        if (!$this->should_process_image($attachment_id)) {
            // Don't log - this happens often during WordPress's multi-stage upload process
            return array(
                'success' => false,
                'message' => 'Image does not meet processing criteria',
                'attachment_id' => $attachment_id
            );
        }
        
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            Tomatillo_Media_Logger::error("File path not found or file does not exist", array(
                'attachment_id' => $attachment_id,
                'file_path' => $file_path
            ));
            return array(
                'success' => false,
                'message' => 'Image file not found',
                'attachment_id' => $attachment_id
            );
        }
        // Use the WordPress-scaled version for optimization (this is what users see)
        $source_path = $file_path; // Use the scaled version, not the true original
        
        $original_size = filesize($source_path);
        
        // Log the start of processing with clear summary
        $filename = basename($source_path);
        Tomatillo_Media_Logger::info("📸 Starting image optimization", array(
            'attachment_id' => $attachment_id,
            'filename' => $filename,
            'original_size' => round($original_size / 1024, 2) . ' KB',
            'action' => 'optimization_start'
        ));
        
        $results = array(
            'attachment_id' => $attachment_id,
            'original_path' => $source_path,
            'original_size' => $original_size,
            'avif_path' => null,
            'webp_path' => null,
            'avif_size' => null,
            'webp_size' => null,
            'avif_savings' => 0,
            'webp_savings' => 0,
            'success' => false,
            'message' => '',
            'operation_status' => 'pending'
        );
        
        $settings = $this->get_settings();
        if (!$settings) {
            Tomatillo_Media_Logger::error('Settings not available for optimization', array(
                'attachment_id' => $attachment_id
            ));
            $results['message'] = 'Settings not available';
            return $results;
        }
        
        $avif_enabled = $settings->is_avif_enabled();
        $webp_enabled = $settings->is_webp_enabled();
        
        try {
            // Set timeout for conversion
            set_time_limit($settings->get_conversion_timeout());
            
            // Convert to AVIF if enabled
            if ($settings->is_avif_enabled()) {
                $avif_result = $this->convert_to_avif($source_path, $attachment_id);
                if ($avif_result['success']) {
                    $savings = round($this->calculate_savings_percentage($original_size, $avif_result['size']));
                    $results['avif_path'] = $avif_result['path'];
                    $results['avif_size'] = $avif_result['size'];
                    $results['avif_savings'] = $this->calculate_savings_percentage($original_size, $avif_result['size']);
                    // Save convenience meta for fast frontend lookups
                    update_post_meta($attachment_id, '_tomatillo_avif_url', $this->path_to_url($avif_result['path']));
                }
            }
            
            // Convert to WebP if enabled
            if ($settings->is_webp_enabled()) {
                $webp_result = $this->convert_to_webp($source_path, $attachment_id);
                if ($webp_result['success']) {
                    $results['webp_path'] = $webp_result['path'];
                    $results['webp_size'] = $webp_result['size'];
                    $results['webp_savings'] = $this->calculate_savings_percentage($original_size, $webp_result['size']);
                    // Save convenience meta for fast frontend lookups
                    update_post_meta($attachment_id, '_tomatillo_webp_url', $this->path_to_url($webp_result['path']));
                }
            }
            
            // Check if conversion meets minimum savings threshold
            $best_savings = max($results['avif_savings'], $results['webp_savings']);
            $min_threshold = $settings->get_min_savings_threshold();
            
            if ($best_savings < $min_threshold) {
                // Clean up files that don't meet threshold
                if ($results['avif_path'] && file_exists($results['avif_path'])) {
                    unlink($results['avif_path']);
                }
                if ($results['webp_path'] && file_exists($results['webp_path'])) {
                    unlink($results['webp_path']);
                }
                $results['success'] = false;
                $results['skipped'] = true;
                $results['operation_status'] = 'skipped';
                $results['message'] = sprintf(
                    'Image optimization skipped: AVIF %d%% savings, WebP %d%% savings (minimum %d%% required)',
                    round($results['avif_savings']),
                    round($results['webp_savings']),
                    $min_threshold
                );
                Tomatillo_Media_Logger::info("⚠️ Optimization skipped - below threshold", array(
                    'attachment_id' => $attachment_id,
                    'filename' => $filename,
                    'best_savings' => round($best_savings) . '%',
                    'min_threshold' => $min_threshold . '%'
                ));
            } else {
                $results['success'] = true;
                $results['operation_status'] = 'completed';
                $results['message'] = sprintf(
                    'Successfully converted: AVIF %d%% savings, WebP %d%% savings',
                    round($results['avif_savings']),
                    round($results['webp_savings'])
                );
                Tomatillo_Media_Logger::info("✅ Optimization completed successfully", array(
                    'attachment_id' => $attachment_id,
                    'filename' => $filename,
                    'avif_savings' => round($results['avif_savings']) . '%',
                    'webp_savings' => round($results['webp_savings']) . '%',
                    'formats' => ($avif_enabled ? 'AVIF' : '') . ($avif_enabled && $webp_enabled ? ' + ' : '') . ($webp_enabled ? 'WebP' : '')
                ));
            }
            
        } catch (Exception $e) {
            Tomatillo_Media_Logger::error("Exception during conversion", array(
                'attachment_id' => $attachment_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            $results['message'] = 'Conversion failed: ' . $e->getMessage();
            $results['operation_status'] = 'failed';
            
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
        
        $settings = $this->get_settings();
        $quality = $settings ? $settings->get_avif_quality() : 50;
        
        // Try GD first (PHP 8.1+)
        if (function_exists('imageavif')) {
            error_log("DEBUG: Using GD for AVIF conversion with quality: {$quality}");
            $result = $this->convert_with_gd($file_path, 'avif', $attachment_id);
        }
        // Fallback to Imagick
        elseif (class_exists('Imagick')) {
            error_log("DEBUG: Using Imagick for AVIF conversion with quality: {$quality}");
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
            
            // AVIF-specific optimizations
            if ($format === 'avif') {
                // Use lossless compression for better quality/size ratio
                $imagick->setImageCompression(Imagick::COMPRESSION_LOSSLESS);
                // Strip metadata to reduce file size
                $imagick->stripImage();
                // Optimize for web delivery
                $imagick->setImageFormat('avif');
                // Additional AVIF optimization
                $imagick->setOption('avif:quality', $quality);
                $imagick->setOption('avif:speed', '6'); // Speed 6 = good compression
            } elseif ($format === 'webp') {
                $imagick->setImageFormat('webp');
                // WebP-specific optimizations
                $imagick->setOption('webp:quality', $quality);
                $imagick->setOption('webp:method', '6'); // Method 6 = good compression
            }
            
            // Generate output path
            $output_path = $this->generate_output_path($file_path, $format);
            
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
        
        // Ensure optimized filenames are based on the true original, not scaled or subsize variants
        // Strip WordPress "-scaled" suffix
        $filename = str_replace('-scaled', '', $filename);
        // Strip size suffix like "-1024x768"
        $filename = preg_replace('/-\d+x\d+$/', '', $filename);
        
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
     * Get the best source image path (prefer the true original over scaled/subsizes)
     */
    private function get_best_source_path($attachment_id, $default_path) {
        // Try WordPress original-image metadata
        $original = wp_get_original_image_path($attachment_id);
        if ($original && file_exists($original)) {
            return $original;
        }
        
        // Fallback: strip -scaled and -WxH suffixes from current path
        $pi = pathinfo($default_path);
        $candidate = $pi['dirname'] . '/' . preg_replace('/-\d+x\d+$/', '', str_replace('-scaled', '', $pi['filename'])) . '.' . $pi['extension'];
        if ($candidate && file_exists($candidate)) {
            return $candidate;
        }
        
        return $default_path;
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
        
        // Always store result in database so Has DB Record reflects immediately
        $this->store_conversion_result($result);
        
        wp_send_json_success($result);
    }
    
    /**
     * Store conversion result in database
     */
    public function store_conversion_result($result) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
        
        $data = array(
            'attachment_id' => $result['attachment_id'],
            'original_format' => pathinfo($result['original_path'], PATHINFO_EXTENSION),
            'avif_path' => $result['avif_path'],
            'webp_path' => $result['webp_path'],
            'original_size' => $result['original_size'],
            'avif_size' => $result['avif_size'],
            'webp_size' => $result['webp_size'],
            'status' => $result['operation_status']
        );
        
        // Upsert by attachment_id: try update first, insert if not exists
        $update_result = $wpdb->update(
            $table_name,
            $data,
            array('attachment_id' => $result['attachment_id'])
        );
        if ($update_result === false || $update_result === 0) {
            $insert_result = $wpdb->insert($table_name, $data);
        } else {
            $insert_result = $update_result; // number of rows updated
        }
        
        // Log database errors only
        if ($insert_result === false) {
            Tomatillo_Media_Logger::error("Database operation failed", array(
                'attachment_id' => $result['attachment_id'],
                'error' => $wpdb->last_error
            ));
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
        
        // Process conversion immediately (logging happens inside convert_image)
        $this->process_immediate_conversion($attachment_id);
    }
    
    /**
     * Handle upload processing for drag and drop uploads
     */
    public function auto_convert_on_upload_handler($upload, $context) {
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
        Tomatillo_Media_Logger::info("Scheduling upload conversion", array(
            'attachment_id' => $attachment_id,
            'action' => 'schedule_conversion'
        ));
        
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
        
        // Process conversion immediately (logging happens inside convert_image)
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
        
        // Convert the image (logging happens inside convert_image)
        $result = $this->convert_image($attachment_id);
        
        if ($result['success']) {
            // Store result in database
            $this->store_conversion_result($result);
        }
        // Failures are already logged inside convert_image
    }
    
    /**
     * Process scheduled conversion
     */
    public function process_scheduled_conversion($attachment_id) {
        $settings = $this->get_settings();
        if (!$settings || !$settings->is_optimization_enabled()) {
            return;
        }
        
        // Convert the image (logging happens inside convert_image)
        $result = $this->convert_image($attachment_id);
        
        if ($result['success']) {
            // Store result in database
            $this->store_conversion_result($result);
        }
        // Failures are already logged inside convert_image
    }
}
