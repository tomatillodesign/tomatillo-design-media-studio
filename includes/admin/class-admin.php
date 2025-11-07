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
        
        // Enqueue block editor assets for React Media Upload override
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        
        // Check for media library redirect early to avoid headers already sent error
        add_action('admin_init', array($this, 'check_media_library_redirect'), 1);
        
        // AJAX handlers for bulk operations
        add_action('wp_ajax_tomatillo_get_unoptimized_count', array($this, 'ajax_get_unoptimized_count'));
        add_action('wp_ajax_tomatillo_process_bulk_batch', array($this, 'ajax_process_bulk_batch'));
        add_action('wp_ajax_tomatillo_preview_bulk_optimization', array($this, 'ajax_preview_bulk_optimization'));
        
        // AJAX handler for column count setting
        add_action('wp_ajax_tomatillo_save_column_count', array($this, 'ajax_save_column_count'));
        
        // AJAX handler for bulk download
        add_action('wp_ajax_tomatillo_bulk_download', array($this, 'ajax_bulk_download'));
        
        // AJAX handlers for logging
        add_action('wp_ajax_tomatillo_update_debug_mode', array($this, 'ajax_update_debug_mode'));
        add_action('wp_ajax_tomatillo_clear_logs', array($this, 'ajax_clear_logs'));
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
            'none', // Using Font Awesome icon via CSS
            30
        );
        
        // Settings page (admin only)
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
        
        // Test page for React integration (admin only)
        add_submenu_page(
            'tomatillo-media-studio-library',
            __('Test React Integration', 'tomatillo-media-studio'),
            __('Test React Integration', 'tomatillo-media-studio'),
            'manage_options', // Admin only
            'tomatillo-media-studio-react-test',
            array($this, 'test_react_integration_page')
        );
        
        // Conditionally add "View Files" submenu link if setting is enabled
        // This runs at the END after all submenu items are added
        $plugin = tomatillo_media_studio();
        $show_files_link = $plugin->settings && $plugin->settings->get('show_files_menu_link');
        
        if ($show_files_link) {
            global $submenu;
            // WordPress auto-creates first submenu item matching parent, so we insert at position 1 (second in list)
            if (isset($submenu['tomatillo-media-studio-library'])) {
                array_splice($submenu['tomatillo-media-studio-library'], 1, 0, array(
                    array(
                        __('View Files', 'tomatillo-media-studio'),
                        'upload_files',
                        'admin.php?page=tomatillo-media-studio-library#files'
                    )
                ));
            }
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
        // Load background loader on all admin pages
        if (is_admin()) {
            $settings = tomatillo_media_studio()->settings;
            
            // Enqueue background loader if enabled
            if ($settings->get_background_load_enabled()) {
                wp_enqueue_script(
                    'tomatillo-background-loader',
                    TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'js/background-loader.js',
                    array('jquery'),
                    TOMATILLO_MEDIA_STUDIO_VERSION,
                    true
                );
                
                // Localize with settings
                wp_localize_script('tomatillo-background-loader', 'tomatilloSettings', $settings->get_js_settings());
                wp_localize_script('tomatillo-background-loader', 'tomatillo_nonce', wp_create_nonce('tomatillo_get_image_data'));
            }
        }
        
        // Enqueue React Media Upload override for block editor
        $this->enqueue_block_editor_assets();
        
        // Add custom menu icon styling (Font Awesome 6 Duotone light fa-images)
        // This needs to load on ALL admin pages so the icon shows in the menu
        $menu_icon_css = "
            #adminmenu #toplevel_page_tomatillo-media-studio-library {
                margin-top: 0 !important;
            }
            #adminmenu #toplevel_page_tomatillo-media-studio-library .wp-menu-image:before {
                content: '\\f302';
                font-family: 'Font Awesome 6 Duotone';
                font-weight: 300;
                speak: never;
                font-style: normal;
                font-variant: normal;
                text-rendering: auto;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
                font-size: 18px;
                --fa-primary-color: #ffffff;
                --fa-primary-opacity: 1;
                --fa-secondary-color: #ffffff;
                --fa-secondary-opacity: 0.4;
            }
            #adminmenu #toplevel_page_tomatillo-media-studio-library .wp-menu-image {
                background: none !important;
            }
            /* Ensure icon displays properly */
            #adminmenu #toplevel_page_tomatillo-media-studio-library .wp-menu-image img {
                display: none;
            }
            /* Active/hover state */
            #adminmenu #toplevel_page_tomatillo-media-studio-library:hover .wp-menu-image:before,
            #adminmenu #toplevel_page_tomatillo-media-studio-library.current .wp-menu-image:before {
                --fa-primary-opacity: 1;
                --fa-secondary-opacity: 0.6;
            }
        ";
        wp_add_inline_style('admin-bar', $menu_icon_css);
        
        // Only load other assets on our plugin pages
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
        
        // Enqueue custom media frame for test page
        if ($hook === 'tomatillo-media-studio_page_tomatillo-media-studio-test') {
            wp_enqueue_media();
            wp_enqueue_script(
                'tomatillo-custom-media-frame',
                TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'js/custom-media-frame-clean.js',
                array('jquery', 'wp-media'),
                TOMATILLO_MEDIA_STUDIO_VERSION,
                true
            );
            
            // Include the template
            include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/custom-media-frame-template.php';
            
            // Localize script with AJAX URL for uploads
            wp_localize_script('tomatillo-custom-media-frame', 'ajaxurl', admin_url('admin-ajax.php'));
        }
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('tomatillo-admin', 'tomatilloMediaStudio', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tomatillo_media_settings')
        ));
    }
    
    /**
     * Enqueue block editor assets
     * This loads our React Media Upload override in the Gutenberg editor iframe
     */
    public function enqueue_block_editor_assets() {
        // Log that this method was called
        error_log('Tomatillo: enqueue_block_editor_assets called');
        
        $rel = 'js/tomatillo-react-media-upload.js';
        $path = TOMATILLO_MEDIA_STUDIO_DIR . 'assets/' . $rel;
        $url = TOMATILLO_MEDIA_STUDIO_ASSETS_URL . $rel;

        error_log('Tomatillo: Checking file at: ' . $path);
        error_log('Tomatillo: Script URL: ' . $url);

        // 1) Smoke-test the file actually exists
        if (!file_exists($path)) {
            error_log('Tomatillo: ERROR - File does not exist at: ' . $path);
            // Cheap breadcrumb in the parent console (visible from iframe via window.top)
            wp_add_inline_script(
                'wp-block-editor',
                'try{ window.top.console.error("Tomatillo: JS missing at ' . esc_js($path) . '"); }catch(e){}'
            );
            return;
        }

        error_log('Tomatillo: File exists, proceeding with enqueue');

        // 2) Register + enqueue inside the editor iframe
        wp_enqueue_script(
            'tomatillo-react-media-upload',
            $url,
            array('wp-element', 'wp-components', 'wp-hooks', 'wp-block-editor', 'wp-data', 'wp-i18n'),
            filemtime($path),
            true // footer of the iframe
        );

        // 3) Inline breadcrumb so you *know* we're in the right place
        wp_add_inline_script(
            'tomatillo-react-media-upload',
            '
            console.log("🚀 Tomatillo React Media Upload: Script loaded! (iframe)");
            console.log("📁 Tomatillo: File path checked:", "' . esc_js($path) . '");
            console.log("🌐 Tomatillo: Script URL:", "' . esc_js($url) . '");
            console.log("⏰ Tomatillo: File modified:", "' . filemtime($path) . '");
            
            // Also log to parent window for easy debugging
            try {
                window.top.console.log("🚀 Tomatillo: React script loaded in iframe!");
                window.top.console.log("📁 Tomatillo: File exists at:", "' . esc_js($path) . '");
            } catch(e) {
                console.log("Tomatillo: Could not log to parent window");
            }
            '
        );

        // 4) Also enqueue our custom media frame for the React wrapper to use
        wp_enqueue_media();
        wp_enqueue_script(
            'tomatillo-custom-media-frame',
            TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'js/custom-media-frame-clean.js',
            array('jquery', 'wp-media'),
            TOMATILLO_MEDIA_STUDIO_VERSION,
            true
        );

        // Include the template for our custom media frame
        include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/custom-media-frame-template.php';

        // Localize script with AJAX URL for uploads
        wp_localize_script('tomatillo-react-media-upload', 'ajaxurl', admin_url('admin-ajax.php'));
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
     * Test React integration page
     */
    public function test_react_integration_page() {
        $plugin = tomatillo_media_studio();
        
        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'tomatillo-media-studio'));
        }
        
        // Include the React integration test template
        include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/test-react-integration.php';
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
    
    /**
     * AJAX handler: Save column count setting
     */
    public function ajax_save_column_count() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tomatillo_column_count')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Validate column count
        $column_count = isset($_POST['column_count']) ? intval($_POST['column_count']) : 4;
        
        // Ensure column count is within valid range (1-8)
        if ($column_count < 1) {
            $column_count = 1;
        } elseif ($column_count > 8) {
            $column_count = 8;
        }
        
        // Save to database
        $updated = update_option('tomatillo_media_column_count', $column_count);
        
        if ($updated || get_option('tomatillo_media_column_count') == $column_count) {
            wp_send_json_success(array(
                'column_count' => $column_count,
                'message' => 'Column count saved successfully'
            ));
        } else {
            wp_send_json_error('Failed to save column count');
        }
    }
    
    /**
     * AJAX handler: Bulk download files as ZIP
     */
    public function ajax_bulk_download() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tomatillo_bulk_download')) {
            Tomatillo_Media_Logger::error('Bulk download: Invalid nonce', array('action' => 'bulk_download'));
            wp_die('Invalid nonce');
        }
        
        // Check user capabilities
        if (!current_user_can('upload_files')) {
            Tomatillo_Media_Logger::error('Bulk download: Insufficient permissions', array('action' => 'bulk_download'));
            wp_die('Insufficient permissions');
        }
        
        // Get file IDs
        $file_ids = isset($_POST['file_ids']) ? json_decode(stripslashes($_POST['file_ids']), true) : array();
        
        Tomatillo_Media_Logger::info('Bulk download started', array(
            'action' => 'bulk_download',
            'file_count' => count($file_ids)
        ));
        
        if (empty($file_ids) || !is_array($file_ids)) {
            wp_die('No files selected');
        }
        
        // Validate file IDs
        $file_ids = array_map('intval', $file_ids);
        $file_ids = array_filter($file_ids, function($id) {
            return $id > 0 && get_post_type($id) === 'attachment';
        });
        
        if (empty($file_ids)) {
            wp_die('No valid files selected');
        }
        
        // Create temporary ZIP file
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/tomatillo-temp';
        
        // Create temp directory if it doesn't exist
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        $zip_filename = 'media-files-' . date('Y-m-d-H-i-s') . '.zip';
        $zip_filepath = $temp_dir . '/' . $zip_filename;
        
        // Create ZIP archive
        $zip = new ZipArchive();
        if ($zip->open($zip_filepath, ZipArchive::CREATE) !== true) {
            wp_die('Failed to create ZIP archive');
        }
        
        // Add files to ZIP
        $added_count = 0;
        foreach ($file_ids as $file_id) {
            $file_path = get_attached_file($file_id);
            
            // For images, try to get the original unscaled version
            if ($file_path && file_exists($file_path)) {
                $use_path = $file_path;
                
                // Check if this is an image and if WordPress created a scaled version
                $mime_type = get_post_mime_type($file_id);
                if ($mime_type && strpos($mime_type, 'image/') === 0) {
                    // Check if current file is a scaled version (ends with -scaled)
                    if (preg_match('/-scaled\.(jpg|jpeg|png|gif|webp)$/i', $file_path)) {
                        // Try to find the original file
                        $original_path = preg_replace('/-scaled(\.(jpg|jpeg|png|gif|webp))$/i', '$1', $file_path);
                        
                        if (file_exists($original_path)) {
                            $use_path = $original_path;
                        }
                    }
                }
                
                $filename = basename($use_path);
                
                // Handle duplicate filenames by adding a counter
                $original_filename = $filename;
                $counter = 1;
                while ($zip->locateName($filename) !== false) {
                    $pathinfo = pathinfo($original_filename);
                    $filename = $pathinfo['filename'] . '-' . $counter . '.' . $pathinfo['extension'];
                    $counter++;
                }
                
                $zip->addFile($use_path, $filename);
                $added_count++;
            }
        }
        
        $zip->close();
        
        if ($added_count === 0) {
            @unlink($zip_filepath);
            Tomatillo_Media_Logger::warning('Bulk download: No valid files found', array('action' => 'bulk_download', 'file_ids' => count($file_ids)));
            wp_die('No valid files found to download');
        }
        
        Tomatillo_Media_Logger::info('Bulk download completed', array(
            'action' => 'bulk_download',
            'files_included' => $added_count,
            'zip_filename' => $zip_filename,
            'zip_size' => filesize($zip_filepath)
        ));
        
        // Send ZIP file to browser
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . filesize($zip_filepath));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($zip_filepath);
        
        // Clean up temporary file
        @unlink($zip_filepath);
        
        exit;
    }
    
    /**
     * AJAX handler: Update debug mode setting
     */
    public function ajax_update_debug_mode() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tomatillo_debug_mode')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get debug mode value
        $debug_mode = isset($_POST['debug_mode']) && $_POST['debug_mode'] === 'true';
        
        // Get current settings
        $plugin = tomatillo_media_studio();
        $settings = $plugin->settings->get_all();
        
        // Update debug mode
        $settings['debug_mode'] = $debug_mode;
        
        // Save settings
        update_option('tomatillo_media_studio_settings', $settings);
        
        // Log the change
        if ($debug_mode) {
            Tomatillo_Media_Logger::info('Debug mode enabled', array('action' => 'settings_change'));
        }
        
        wp_send_json_success(array(
            'debug_mode' => $debug_mode,
            'message' => $debug_mode ? 'Debug mode enabled' : 'Debug mode disabled'
        ));
    }
    
    /**
     * AJAX handler: Clear plugin logs
     */
    public function ajax_clear_logs() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tomatillo_clear_logs')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Clear logs
        Tomatillo_Media_Logger::clear_logs();
        
        wp_send_json_success(array(
            'message' => 'Logs cleared successfully'
        ));
    }
}
