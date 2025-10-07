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
        
        // Check for media library redirect early to avoid headers already sent error
        add_action('admin_init', array($this, 'check_media_library_redirect'), 1);
        
        // AJAX handlers for bulk operations
        add_action('wp_ajax_tomatillo_get_unoptimized_count', array($this, 'ajax_get_unoptimized_count'));
        add_action('wp_ajax_tomatillo_process_bulk_batch', array($this, 'ajax_process_bulk_batch'));
        add_action('wp_ajax_tomatillo_preview_bulk_optimization', array($this, 'ajax_preview_bulk_optimization'));
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
        
        // Test page for custom media frame (admin only)
        add_submenu_page(
            'tomatillo-media-studio-library',
            __('Test Media Frame', 'tomatillo-media-studio'),
            __('Test Media Frame', 'tomatillo-media-studio'),
            'manage_options', // Admin only
            'tomatillo-media-studio-test',
            array($this, 'test_media_frame_page')
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
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('tomatillo-admin', 'tomatilloMediaStudio', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tomatillo_media_settings')
        ));
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
     * Check for media library redirect early to avoid headers already sent error
     */
    public function check_media_library_redirect() {
        // Only check if we're on our media library page
        if (!isset($_GET['page']) || $_GET['page'] !== 'tomatillo-media-studio-library') {
            return;
        }
        
        // Redirect to default WordPress Media Library when enhanced interface is disabled
        if (!tomatillo_media_studio()->settings->is_media_library_enabled()) {
            wp_redirect(admin_url('upload.php'));
            exit;
        }
    }
    
    /**
     * Media library page
     */
    public function media_library_page() {
        // Redirect should have already happened in check_media_library_redirect()
        // This method should only be called when enhanced interface is enabled
        include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/media-library.php';
    }
    
    /**
     * Test media frame page
     */
    public function test_media_frame_page() {
        $plugin = tomatillo_media_studio();
        
        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'tomatillo-media-studio'));
        }
        
        // Include the test template
        include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/test-custom-media-frame.php';
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
            
            if ($monthly_pageviews > 0) {
                update_option('tomatillo_monthly_pageviews', $monthly_pageviews);
                echo '<div class="notice notice-success"><p>' . __('Calculator settings updated successfully!', 'tomatillo-media-studio') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Please enter a valid number of monthly page views.', 'tomatillo-media-studio') . '</p></div>';
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
        // When enhanced interface is disabled, keep the default WordPress Media Library visible
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
    
    /**
     * AJAX handler: Get unoptimized images count
     */
    public function ajax_get_unoptimized_count() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tomatillo_bulk_optimize')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $plugin = tomatillo_media_studio();
        $count = 0;
        
        if ($plugin->core) {
            $count = $plugin->core->get_unoptimized_images_count();
        }
        
        wp_send_json_success(array('count' => $count));
    }
    
    /**
     * AJAX handler: Process bulk optimization batch
     */
    public function ajax_process_bulk_batch() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tomatillo_bulk_optimize')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $batch = intval($_POST['batch']);
        $batch_size = 5; // Process 5 images per batch
        $offset = ($batch - 1) * $batch_size;
        
        $plugin = tomatillo_media_studio();
        $results = array();
        
        if ($plugin->core) {
            // Get unoptimized images for this batch
            $images = get_posts(array(
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'post_status' => 'inherit',
                'posts_per_page' => $batch_size,
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
            
            foreach ($images as $image) {
                $result = $this->process_single_image($image, $plugin);
                $results[] = $result;
            }
        }
        
        wp_send_json_success(array('images' => $results));
    }
    
    /**
     * AJAX handler: Preview bulk optimization
     */
    public function ajax_preview_bulk_optimization() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tomatillo_bulk_optimize')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get first 10 unoptimized images
        $images = get_posts(array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_wp_attachment_metadata',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        $preview_data = array();
        foreach ($images as $image) {
            $file_path = get_attached_file($image->ID);
            $file_size = file_exists($file_path) ? filesize($file_path) : 0;
            $mime_type = get_post_mime_type($image->ID);
            
            $preview_data[] = array(
                'filename' => basename($file_path),
                'size' => $file_size,
                'type' => $mime_type,
                'id' => $image->ID
            );
        }
        
        wp_send_json_success(array('images' => $preview_data));
    }
    
    /**
     * Process a single image for optimization
     */
    private function process_single_image($image, $plugin) {
        $file_path = get_attached_file($image->ID);
        $filename = basename($file_path);
        $original_size = file_exists($file_path) ? filesize($file_path) : 0;
        
        $result = array(
            'filename' => $filename,
            'original_size' => $original_size,
            'success' => false,
            'space_saved' => 0,
            'savings_percent' => 0,
            'error' => null
        );
        
        try {
            // Check if image is already optimized
            if ($plugin->core && $plugin->core->is_image_optimized($image->ID)) {
                $result['success'] = true;
                $result['skipped'] = true;
                $result['error'] = 'Already optimized';
                return $result;
            }
            
            // Check file size threshold
            $settings = $plugin->settings;
            if ($settings->should_skip_small_images() && $original_size < $settings->get_min_image_size()) {
                $result['success'] = true;
                $result['skipped'] = true;
                $result['error'] = 'Skipped - too small (' . size_format($original_size) . ')';
                return $result;
            }
            
            // Check image dimensions
            $metadata = wp_get_attachment_metadata($image->ID);
            if ($metadata && isset($metadata['width']) && isset($metadata['height'])) {
                $max_dimensions = $settings->get_max_image_dimensions();
                if ($metadata['width'] > $max_dimensions || $metadata['height'] > $max_dimensions) {
                    $result['success'] = true;
                    $result['skipped'] = true;
                    $result['error'] = 'Skipped - too large (' . $metadata['width'] . 'x' . $metadata['height'] . ')';
                    return $result;
                }
            }
            
            // Attempt optimization
            if ($plugin->core) {
                $optimization_result = $plugin->core->optimize_image($image->ID);
                
                if ($optimization_result && isset($optimization_result['success']) && $optimization_result['success']) {
                    $result['success'] = true;
                    $result['space_saved'] = $optimization_result['space_saved'] ?? 0;
                    $result['savings_percent'] = $optimization_result['savings_percent'] ?? 0;
                } else {
                    $result['error'] = $optimization_result['error'] ?? 'Optimization failed';
                }
            } else {
                $result['error'] = 'Core module not available';
            }
            
        } catch (Exception $e) {
            $result['error'] = 'Exception: ' . $e->getMessage();
        } catch (Error $e) {
            $result['error'] = 'Error: ' . $e->getMessage();
        }
        
        return $result;
    }
}
