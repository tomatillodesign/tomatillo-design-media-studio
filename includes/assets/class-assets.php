<?php
/**
 * Assets management class
 * 
 * Handles loading of CSS, JavaScript, and other assets
 */

if (!defined('ABSPATH')) {
    exit;
}

class Tomatillo_Media_Assets {
    
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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (!$this->is_plugin_page($hook)) {
            return;
        }
        
        // Enqueue admin CSS
        wp_enqueue_style(
            'tomatillo-media-studio-admin',
            TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'css/admin.css',
            array(),
            TOMATILLO_MEDIA_STUDIO_VERSION
        );
        
        // Enqueue admin JavaScript
        wp_enqueue_script(
            'tomatillo-media-studio-admin',
            TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'js/admin.js',
            array('jquery', 'wp-util'),
            TOMATILLO_MEDIA_STUDIO_VERSION,
            true
        );
        
        // Localize script with settings
        wp_localize_script('tomatillo-media-studio-admin', 'tomatilloMediaStudio', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tomatillo_media_studio'),
            'settings' => tomatillo_media_studio()->settings->get_js_settings(),
            'strings' => array(
                'loading' => __('Loading...', 'tomatillo-media-studio'),
                'error' => __('An error occurred', 'tomatillo-media-studio'),
                'success' => __('Success!', 'tomatillo-media-studio'),
                'confirmDelete' => __('Are you sure you want to delete this item?', 'tomatillo-media-studio'),
            )
        ));
        
        // Enqueue media library assets if enabled
        if (tomatillo_media_studio()->settings->is_media_library_enabled()) {
            $this->enqueue_media_library_assets();
        }
        
        // Enqueue optimization assets if enabled
        if (tomatillo_media_studio()->settings->is_optimization_enabled()) {
            $this->enqueue_optimization_assets();
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only load if optimization is enabled
        if (!tomatillo_media_studio()->settings->is_optimization_enabled()) {
            return;
        }
        
        // Only enqueue if the file exists to avoid 404s
        if ($this->asset_exists('js/frontend.js')) {
            wp_enqueue_script(
                'tomatillo-media-studio-frontend',
                TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'js/frontend.js',
                array(),
                TOMATILLO_MEDIA_STUDIO_VERSION,
                true
            );
        }
    }
    
    /**
     * Enqueue media library specific assets
     */
    private function enqueue_media_library_assets() {
        // Media library CSS
        wp_enqueue_style(
            'tomatillo-media-library',
            TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'css/media-library.css',
            array(),
            TOMATILLO_MEDIA_STUDIO_VERSION
        );
        
        // Media library JavaScript
        wp_enqueue_script(
            'tomatillo-media-library',
            TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'js/media-library.js',
            array('jquery', 'wp-util'),
            TOMATILLO_MEDIA_STUDIO_VERSION,
            true
        );
        
        // Component scripts
        wp_enqueue_script(
            'tomatillo-thumbnail-grid',
            TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'js/components/thumbnail-grid.js',
            array('tomatillo-media-library'),
            TOMATILLO_MEDIA_STUDIO_VERSION,
            true
        );
        
        wp_enqueue_script(
            'tomatillo-bulk-actions',
            TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'js/components/bulk-actions.js',
            array('tomatillo-media-library'),
            TOMATILLO_MEDIA_STUDIO_VERSION,
            true
        );
        
        wp_enqueue_script(
            'tomatillo-search-filter',
            TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'js/components/search-filter.js',
            array('tomatillo-media-library'),
            TOMATILLO_MEDIA_STUDIO_VERSION,
            true
        );
    }
    
    /**
     * Enqueue optimization specific assets
     */
    private function enqueue_optimization_assets() {
        // Optimization CSS
        wp_enqueue_style(
            'tomatillo-optimization',
            TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'css/optimization.css',
            array(),
            TOMATILLO_MEDIA_STUDIO_VERSION
        );
        
        // Optimization JavaScript
        wp_enqueue_script(
            'tomatillo-optimization',
            TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'js/optimization.js',
            array('jquery', 'wp-util'),
            TOMATILLO_MEDIA_STUDIO_VERSION,
            true
        );
    }
    
    /**
     * Check if current page is a plugin page
     */
    private function is_plugin_page($hook) {
        $plugin_pages = array(
            'media_page_tomatillo-media-studio',
            'toplevel_page_tomatillo-media-studio',
            'media_page_tomatillo-media-library',
        );
        
        return in_array($hook, $plugin_pages);
    }
    
    /**
     * Get asset URL
     */
    public function get_asset_url($path) {
        return TOMATILLO_MEDIA_STUDIO_ASSETS_URL . ltrim($path, '/');
    }
    
    /**
     * Get asset path
     */
    public function get_asset_path($path) {
        return TOMATILLO_MEDIA_STUDIO_DIR . 'assets/' . ltrim($path, '/');
    }
    
    /**
     * Check if asset exists
     */
    public function asset_exists($path) {
        return file_exists($this->get_asset_path($path));
    }
}
