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
        add_action('admin_menu', array($this, 'hide_traditional_media_library'), 999);
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
        
        // Redirect traditional media library to our media studio
        $this->redirect_traditional_media_library();
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
            'tomatillo-media-studio-settings',
            array($this, 'settings_page'),
            'dashicons-images-alt2',
            30
        );
        
        // Settings submenu
        add_submenu_page(
            'tomatillo-media-studio-settings',
            __('Settings', 'tomatillo-media-studio'),
            __('Settings', 'tomatillo-media-studio'),
            'manage_options',
            'tomatillo-media-studio-settings',
            array($this, 'settings_page')
        );
        
        // Media Library submenu (if enabled)
        if (tomatillo_media_studio()->settings->is_media_library_enabled()) {
            add_submenu_page(
                'tomatillo-media-studio-settings',
                __('Media Library', 'tomatillo-media-studio'),
                __('Media Library', 'tomatillo-media-studio'),
                'upload_files',
                'tomatillo-media-studio-library',
                array($this, 'media_library_page')
            );
        }
        
        // Optimization Dashboard submenu (if enabled)
        if (tomatillo_media_studio()->settings->is_optimization_enabled()) {
            add_submenu_page(
                'tomatillo-media-studio-settings',
                __('Optimization', 'tomatillo-media-studio'),
                __('Optimization', 'tomatillo-media-studio'),
                'manage_options',
                'tomatillo-media-studio-optimization',
                array($this, 'optimization_page')
            );
        }
        
        // Tools submenu
        add_submenu_page(
            'tomatillo-media-studio-settings',
            __('Tools', 'tomatillo-media-studio'),
            __('Tools', 'tomatillo-media-studio'),
            'manage_options',
            'tomatillo-media-studio-tools',
            array($this, 'tools_page')
        );
        
        // Test Optimization submenu (if optimization enabled)
        if (tomatillo_media_studio()->settings->is_optimization_enabled()) {
            add_submenu_page(
                'tomatillo-media-studio-settings',
                __('Test Optimization', 'tomatillo-media-studio'),
                __('Test Optimization', 'tomatillo-media-studio'),
                'manage_options',
                'tomatillo-media-studio-test',
                array($this, 'test_optimization_page')
            );
            
            add_submenu_page(
                'tomatillo-media-studio-settings',
                __('Frontend Test', 'tomatillo-media-studio'),
                __('Frontend Test', 'tomatillo-media-studio'),
                'manage_options',
                'tomatillo-media-studio-frontend',
                array($this, 'frontend_test_page')
            );
        }
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
        $plugin = tomatillo_media_studio();
        $settings = $plugin->settings;
        $stats = ($plugin->core) ? $plugin->core->get_media_stats() : array();
        
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
        $plugin = tomatillo_media_studio();
        if (!$plugin->settings->is_optimization_enabled()) {
            wp_die(__('Optimization module is disabled.', 'tomatillo-media-studio'));
        }
        
        $stats = ($plugin->core) ? $plugin->core->get_optimization_stats() : array();
        
        include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/optimization-dashboard.php';
    }
    
    /**
     * Tools page
     */
    public function tools_page() {
        $plugin = tomatillo_media_studio();
        
        // Handle log clearing
        if (isset($_POST['clear_logs']) && wp_verify_nonce($_POST['_wpnonce'], 'tomatillo_clear_logs')) {
            if ($plugin->core) {
                $plugin->core->clear_plugin_logs();
            }
            echo '<div class="notice notice-success"><p>' . __('Plugin logs cleared successfully!', 'tomatillo-media-studio') . '</p></div>';
        }
        
        // Handle debug mode toggle
        if (isset($_POST['toggle_debug']) && wp_verify_nonce($_POST['_wpnonce'], 'tomatillo_toggle_debug')) {
            $current_debug = $plugin->settings->is_debug_mode();
            $plugin->settings->set('debug_mode', !$current_debug);
            $plugin->settings->save();
            
            $message = !$current_debug ? __('Debug mode enabled', 'tomatillo-media-studio') : __('Debug mode disabled', 'tomatillo-media-studio');
            echo '<div class="notice notice-success"><p>' . $message . '</p></div>';
        }
        
        include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/tools-page.php';
    }
    
    /**
     * Test optimization page
     */
    public function test_optimization_page() {
        $plugin = tomatillo_media_studio();
        if (!$plugin->settings->is_optimization_enabled()) {
            wp_die(__('Optimization module is disabled.', 'tomatillo-media-studio'));
        }
        
        include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/test-optimization.php';
    }
    
    /**
     * Frontend test page
     */
    public function frontend_test_page() {
        $plugin = tomatillo_media_studio();
        if (!$plugin->settings->is_optimization_enabled()) {
            wp_die(__('Optimization module is disabled.', 'tomatillo-media-studio'));
        }
        
        include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/frontend-test.php';
    }
    
    /**
     * Get admin page URL
     */
    public function get_admin_url($page = 'tomatillo-media-studio-settings') {
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
     * Hide traditional WordPress media library
     */
    public function hide_traditional_media_library() {
        // Only hide if media library module is enabled
        $plugin = tomatillo_media_studio();
        if ($plugin && $plugin->settings && $plugin->settings->is_media_library_enabled()) {
            // Remove the traditional Media menu
            remove_menu_page('upload.php');
            
            // Also remove the Media submenu from other locations
            remove_submenu_page('upload.php', 'upload.php');
        }
    }
    
    /**
     * Redirect traditional media library to our media studio
     */
    public function redirect_traditional_media_library() {
        // Only redirect if media library module is enabled
        $plugin = tomatillo_media_studio();
        if (!$plugin || !$plugin->settings || !$plugin->settings->is_media_library_enabled()) {
            return;
        }
        
        // Check if we're on the traditional media library page
        if (isset($_GET['page']) && $_GET['page'] === 'upload.php') {
            wp_redirect(admin_url('admin.php?page=tomatillo-media-studio-library'));
            exit;
        }
        
        // Also redirect direct access to upload.php
        $screen = get_current_screen();
        if ($screen && $screen->id === 'upload') {
            wp_redirect(admin_url('admin.php?page=tomatillo-media-studio-library'));
            exit;
        }
    }
    
    /**
     * Check if current page is plugin page
     */
    public function is_plugin_page() {
        $screen = get_current_screen();
        return strpos($screen->id, 'tomatillo') !== false;
    }
}
