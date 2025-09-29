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
        // Main Media Studio page (first position)
        add_menu_page(
            __('Media Studio', 'tomatillo-media-studio'),
            __('Media Studio', 'tomatillo-media-studio'),
            'upload_files', // Allow editors to access media studio
            'tomatillo-media-studio-library',
            array($this, 'media_library_page'),
            'dashicons-images-alt2',
            30
        );
        
        // Settings page (admin only, second position)
        add_submenu_page(
            'tomatillo-media-studio-library',
            __('Settings', 'tomatillo-media-studio'),
            __('Settings', 'tomatillo-media-studio'),
            'manage_options', // Admin only
            'tomatillo-media-studio-settings',
            array($this, 'settings_page')
        );
        
        // Tools page (admin only, consolidated functionality)
        add_submenu_page(
            'tomatillo-media-studio-library',
            __('Tools', 'tomatillo-media-studio'),
            __('Tools', 'tomatillo-media-studio'),
            'manage_options', // Admin only
            'tomatillo-media-studio-tools',
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
        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'tomatillo-media-studio'));
        }
        
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
     * Tools page
     */
    public function tools_page() {
        $plugin = tomatillo_media_studio();
        
        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'tomatillo-media-studio'));
        }
        
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'optimization';
        
        // Handle form submissions
        $this->handle_tools_form_submissions($plugin, $current_tab);
        
        // Include the tools template
        include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/tools-page.php';
    }
    
    /**
     * Handle form submissions for tools page
     */
    private function handle_tools_form_submissions($plugin, $current_tab) {
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
        
        // Handle bulk optimization
        if (isset($_POST['start_bulk_optimization']) && wp_verify_nonce($_POST['_wpnonce'], 'tomatillo_bulk_optimize')) {
            if ($plugin->core) {
                $plugin->core->start_bulk_optimization();
            }
            echo '<div class="notice notice-success"><p>' . __('Bulk optimization started! Check the progress below.', 'tomatillo-media-studio') . '</p></div>';
        }
        
        // Handle calculator updates
        if (isset($_POST['update_calculator']) && wp_verify_nonce($_POST['_wpnonce'], 'tomatillo_update_calculator')) {
            $monthly_pageviews = intval($_POST['monthly_pageviews']);
            $cost_per_gb = floatval($_POST['cost_per_gb']);
            
            // Handle custom cost input
            if (isset($_POST['custom_cost']) && $_POST['cost_per_gb'] === 'custom') {
                $cost_per_gb = floatval($_POST['custom_cost']);
            }
            
            if ($monthly_pageviews > 0 && $cost_per_gb >= 0) {
                update_option('tomatillo_monthly_pageviews', $monthly_pageviews);
                update_option('tomatillo_cost_per_gb', $cost_per_gb);
                echo '<div class="notice notice-success"><p>' . __('Calculator settings updated successfully!', 'tomatillo-media-studio') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Please enter valid values for page views and cost per GB.', 'tomatillo-media-studio') . '</p></div>';
            }
        }
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
