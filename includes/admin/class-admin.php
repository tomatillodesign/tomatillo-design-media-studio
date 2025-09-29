<?php
/**
 * Admin interface management
 * 
 * Handles admin pages, menus, and admin-specific functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Tomatillo_Media_Admin {
    
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    /**
     * Initialize admin functionality
     */
    public function init() {
        // Admin-specific initialization
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings
        $this->register_settings();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main plugin page
        add_menu_page(
            __('Media Studio', 'tomatillo-media-studio'),
            __('Media Studio', 'tomatillo-media-studio'),
            'manage_options',
            'tomatillo-media-studio',
            array($this, 'settings_page'),
            'dashicons-images-alt2',
            30
        );
        
        // Settings submenu
        add_submenu_page(
            'tomatillo-media-studio',
            __('Settings', 'tomatillo-media-studio'),
            __('Settings', 'tomatillo-media-studio'),
            'manage_options',
            'tomatillo-media-studio',
            array($this, 'settings_page')
        );
        
        // Media Library submenu (if enabled)
        if (tomatillo_media_studio()->settings->is_media_library_enabled()) {
            add_submenu_page(
                'tomatillo-media-studio',
                __('Media Library', 'tomatillo-media-studio'),
                __('Media Library', 'tomatillo-media-studio'),
                'upload_files',
                'tomatillo-media-library',
                array($this, 'media_library_page')
            );
        }
        
        // Optimization Dashboard submenu (if enabled)
        if (tomatillo_media_studio()->settings->is_optimization_enabled()) {
            add_submenu_page(
                'tomatillo-media-studio',
                __('Optimization', 'tomatillo-media-studio'),
                __('Optimization', 'tomatillo-media-studio'),
                'manage_options',
                'tomatillo-optimization',
                array($this, 'optimization_page')
            );
        }
        
        // Tools submenu
        add_submenu_page(
            'tomatillo-media-studio',
            __('Tools', 'tomatillo-media-studio'),
            __('Tools', 'tomatillo-media-studio'),
            'manage_options',
            'tomatillo-tools',
            array($this, 'tools_page')
        );
    }
    
    /**
     * Register settings
     */
    private function register_settings() {
        // Settings are registered in the Settings class
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'tomatillo') === false) {
            return;
        }
        
        // Enqueue common admin assets
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Enqueue our admin assets
        wp_enqueue_style(
            'tomatillo-admin',
            TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'css/admin.css',
            array(),
            TOMATILLO_MEDIA_STUDIO_VERSION
        );
        
        wp_enqueue_script(
            'tomatillo-admin',
            TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'js/admin.js',
            array('jquery', 'wp-color-picker'),
            TOMATILLO_MEDIA_STUDIO_VERSION,
            true
        );
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $settings = tomatillo_media_studio()->settings;
        $stats = tomatillo_media_studio()->core->get_media_stats();
        
        include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/settings-page.php';
    }
    
    /**
     * Media library page
     */
    public function media_library_page() {
        if (!tomatillo_media_studio()->settings->is_media_library_enabled()) {
            wp_die(__('Media Library module is disabled.', 'tomatillo-media-studio'));
        }
        
        include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/media-library.php';
    }
    
    /**
     * Optimization page
     */
    public function optimization_page() {
        if (!tomatillo_media_studio()->settings->is_optimization_enabled()) {
            wp_die(__('Optimization module is disabled.', 'tomatillo-media-studio'));
        }
        
        $stats = tomatillo_media_studio()->core->get_optimization_stats();
        
        include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/optimization-dashboard.php';
    }
    
    /**
     * Tools page
     */
    public function tools_page() {
        include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/tools-page.php';
    }
    
    /**
     * Get admin page URL
     */
    public function get_admin_url($page = 'tomatillo-media-studio') {
        return admin_url('admin.php?page=' . $page);
    }
    
    /**
     * Add admin notice
     */
    public function add_notice($message, $type = 'info', $dismissible = true) {
        $class = 'notice notice-' . $type;
        if ($dismissible) {
            $class .= ' is-dismissible';
        }
        
        echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($message) . '</p></div>';
    }
    
    /**
     * Check if current page is plugin page
     */
    public function is_plugin_page() {
        $screen = get_current_screen();
        return strpos($screen->id, 'tomatillo') !== false;
    }
}
