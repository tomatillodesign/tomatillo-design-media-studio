<?php
/**
 * Settings management class
 * 
 * Handles all plugin settings with macro controls for enabling/disabling modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class Tomatillo_Media_Settings {
    
    /**
     * Settings option name
     */
    const OPTION_NAME = 'tomatillo_media_studio_settings';
    
    /**
     * Default settings
     */
    private $defaults = array(
        // Module Control
        'enable_optimization' => true,
        'enable_media_library' => true,
        
        // Optimization Settings
        'avif_quality' => 80,
        'webp_quality' => 85,
        'auto_convert' => true,
        'batch_size' => 10,
        'preserve_originals' => true,
        
        // Media Library Settings
        'thumbnail_size' => 'large',
        'enable_bulk_ops' => true,
        'enable_advanced_search' => true,
        'show_file_sizes' => true,
        'show_optimization_status' => true,
        
        // Advanced Settings
        'debug_mode' => false,
        'cache_thumbnails' => true,
        'lazy_load_images' => true,
    );
    
    /**
     * Current settings
     */
    private $settings = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_settings();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_tomatillo_save_settings', array($this, 'ajax_save_settings'));
    }
    
    /**
     * Load settings from database
     */
    private function load_settings() {
        $saved_settings = get_option(self::OPTION_NAME, array());
        $this->settings = wp_parse_args($saved_settings, $this->defaults);
    }
    
    /**
     * Register settings with WordPress
     */
    public function register_settings() {
        register_setting(
            'tomatillo_media_studio_settings',
            self::OPTION_NAME,
            array(
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => $this->defaults
            )
        );
    }
    
    /**
     * Get a setting value
     */
    public function get($key, $default = null) {
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        }
        
        if ($default !== null) {
            return $default;
        }
        
        if (isset($this->defaults[$key])) {
            return $this->defaults[$key];
        }
        
        return null;
    }
    
    /**
     * Set a setting value
     */
    public function set($key, $value) {
        $this->settings[$key] = $value;
        return $this;
    }
    
    /**
     * Save settings to database
     */
    public function save() {
        return update_option(self::OPTION_NAME, $this->settings);
    }
    
    /**
     * Get all settings
     */
    public function get_all() {
        return $this->settings;
    }
    
    /**
     * Reset to defaults
     */
    public function reset_to_defaults() {
        $this->settings = $this->defaults;
        return $this->save();
    }
    
    /**
     * Check if optimization module is enabled
     */
    public function is_optimization_enabled() {
        return (bool) $this->get('enable_optimization');
    }
    
    /**
     * Check if media library module is enabled
     */
    public function is_media_library_enabled() {
        return (bool) $this->get('enable_media_library');
    }
    
    /**
     * Check if auto-conversion is enabled
     */
    public function is_auto_convert_enabled() {
        return (bool) $this->get('auto_convert');
    }
    
    /**
     * Get AVIF quality setting
     */
    public function get_avif_quality() {
        return (int) $this->get('avif_quality');
    }
    
    /**
     * Get WebP quality setting
     */
    public function get_webp_quality() {
        return (int) $this->get('webp_quality');
    }
    
    /**
     * Get batch size for processing
     */
    public function get_batch_size() {
        return (int) $this->get('batch_size');
    }
    
    /**
     * Get thumbnail size setting
     */
    public function get_thumbnail_size() {
        return $this->get('thumbnail_size');
    }
    
    /**
     * Check if bulk operations are enabled
     */
    public function is_bulk_ops_enabled() {
        return (bool) $this->get('enable_bulk_ops');
    }
    
    /**
     * Check if advanced search is enabled
     */
    public function is_advanced_search_enabled() {
        return (bool) $this->get('enable_advanced_search');
    }
    
    /**
     * Check if debug mode is enabled
     */
    public function is_debug_mode() {
        return (bool) $this->get('debug_mode');
    }
    
    /**
     * Sanitize settings before saving
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Module Control
        $sanitized['enable_optimization'] = isset($input['enable_optimization']) ? (bool) $input['enable_optimization'] : false;
        $sanitized['enable_media_library'] = isset($input['enable_media_library']) ? (bool) $input['enable_media_library'] : false;
        
        // Optimization Settings
        $sanitized['avif_quality'] = isset($input['avif_quality']) ? max(1, min(100, (int) $input['avif_quality'])) : 80;
        $sanitized['webp_quality'] = isset($input['webp_quality']) ? max(1, min(100, (int) $input['webp_quality'])) : 85;
        $sanitized['auto_convert'] = isset($input['auto_convert']) ? (bool) $input['auto_convert'] : false;
        $sanitized['batch_size'] = isset($input['batch_size']) ? max(1, min(50, (int) $input['batch_size'])) : 10;
        $sanitized['preserve_originals'] = isset($input['preserve_originals']) ? (bool) $input['preserve_originals'] : true;
        
        // Media Library Settings
        $allowed_sizes = array('thumbnail', 'medium', 'large', 'full');
        $sanitized['thumbnail_size'] = isset($input['thumbnail_size']) && in_array($input['thumbnail_size'], $allowed_sizes) 
            ? $input['thumbnail_size'] : 'large';
        $sanitized['enable_bulk_ops'] = isset($input['enable_bulk_ops']) ? (bool) $input['enable_bulk_ops'] : false;
        $sanitized['enable_advanced_search'] = isset($input['enable_advanced_search']) ? (bool) $input['enable_advanced_search'] : false;
        $sanitized['show_file_sizes'] = isset($input['show_file_sizes']) ? (bool) $input['show_file_sizes'] : false;
        $sanitized['show_optimization_status'] = isset($input['show_optimization_status']) ? (bool) $input['show_optimization_status'] : false;
        
        // Advanced Settings
        $sanitized['debug_mode'] = isset($input['debug_mode']) ? (bool) $input['debug_mode'] : false;
        $sanitized['cache_thumbnails'] = isset($input['cache_thumbnails']) ? (bool) $input['cache_thumbnails'] : true;
        $sanitized['lazy_load_images'] = isset($input['lazy_load_images']) ? (bool) $input['lazy_load_images'] : true;
        
        return $sanitized;
    }
    
    /**
     * AJAX handler for saving settings
     */
    public function ajax_save_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tomatillo_media_settings')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Sanitize and save settings
        $sanitized = $this->sanitize_settings($_POST['settings']);
        $this->settings = wp_parse_args($sanitized, $this->settings);
        
        if ($this->save()) {
            wp_send_json_success('Settings saved successfully');
        } else {
            wp_send_json_error('Failed to save settings');
        }
    }
    
    /**
     * Get settings for JavaScript
     */
    public function get_js_settings() {
        return array(
            'enable_optimization' => $this->is_optimization_enabled(),
            'enable_media_library' => $this->is_media_library_enabled(),
            'avif_quality' => $this->get_avif_quality(),
            'webp_quality' => $this->get_webp_quality(),
            'auto_convert' => $this->is_auto_convert_enabled(),
            'batch_size' => $this->get_batch_size(),
            'thumbnail_size' => $this->get_thumbnail_size(),
            'enable_bulk_ops' => $this->is_bulk_ops_enabled(),
            'enable_advanced_search' => $this->is_advanced_search_enabled(),
            'debug_mode' => $this->is_debug_mode(),
        );
    }
}
