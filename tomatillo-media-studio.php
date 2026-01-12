<?php
/**
 * Plugin Name:       Tomatillo Design ~ Media Studio
 * Plugin URI:        https://github.com/tomatillodesign/tomatillo-design-media-studio
 * Description:       A comprehensive WordPress media solution featuring automatic AVIF/WebP optimization and a beautiful, modern media library interface.
 * Version:           1.0.4
 * Author:            Chris Liu-Beers, Tomatillo Design
 * Author URI:        https://tomatillodesign.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tomatillo-media-studio
 * Domain Path:       /languages
 * Requires at least: 6.4
 * Tested up to:      6.7
 * Requires PHP:      8.1
 * Network:           false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('TOMATILLO_MEDIA_STUDIO_VERSION', '1.0.4');
define('TOMATILLO_MEDIA_STUDIO_FILE', __FILE__);
define('TOMATILLO_MEDIA_STUDIO_DIR', plugin_dir_path(__FILE__));
define('TOMATILLO_MEDIA_STUDIO_URL', plugin_dir_url(__FILE__));
define('TOMATILLO_MEDIA_STUDIO_ASSETS_URL', TOMATILLO_MEDIA_STUDIO_URL . 'assets/');
define('TOMATILLO_MEDIA_STUDIO_TEXT_DOMAIN', 'tomatillo-media-studio');

/**
 * Main plugin class
 */
class Tomatillo_Media_Studio {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Settings instance
     */
    public $settings = null;
    
    /**
     * Core functionality
     */
    public $core = null;
    
    /**
     * Optimization module
     */
    public $optimization = null;
    
    /**
     * Frontend optimization
     */
    public $frontend_swap = null;
    
    /**
     * Media library module
     */
    public $media_library = null;
    
    /**
     * Admin interface
     */
    public $admin = null;
    
    /**
     * Assets manager
     */
    public $assets = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_modules();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load plugin modules
     */
    private function load_modules() {
        // Load core classes
        require_once TOMATILLO_MEDIA_STUDIO_DIR . 'includes/class-core.php';
        require_once TOMATILLO_MEDIA_STUDIO_DIR . 'includes/class-settings.php';
        require_once TOMATILLO_MEDIA_STUDIO_DIR . 'includes/class-logger.php';
        require_once TOMATILLO_MEDIA_STUDIO_DIR . 'includes/assets/class-assets.php';
        
        // Initialize core components
        $this->settings = new Tomatillo_Media_Settings();
        $this->assets = new Tomatillo_Media_Assets();
        $this->core = new Tomatillo_Media_Core();
        
        // Load modules based on settings (only if they exist)
        if ($this->settings->is_optimization_enabled()) {
            $this->load_optimization_module();
        }
        
        if ($this->settings->is_media_library_enabled()) {
            $this->load_media_library_module();
        }
        
        // Always load admin interface
        $this->load_admin_module();
    }
    
    /**
     * Load optimization module
     */
    private function load_optimization_module() {
        $optimizer_file = TOMATILLO_MEDIA_STUDIO_DIR . 'includes/optimization/class-optimizer.php';
        $frontend_file = TOMATILLO_MEDIA_STUDIO_DIR . 'includes/optimization/class-frontend-swap.php';
        
        if (file_exists($optimizer_file)) {
            require_once $optimizer_file;
            $this->optimization = new Tomatillo_Optimizer();
        } else {
            // Log error for debugging
            error_log('Tomatillo Media Studio: Optimization module file not found: ' . $optimizer_file);
        }
        
        if (file_exists($frontend_file)) {
            require_once $frontend_file;
            $this->frontend_swap = new Tomatillo_Frontend_Swap();
        } else {
            // Log error for debugging
            error_log('Tomatillo Media Studio: Frontend swap file not found: ' . $frontend_file);
        }
    }
    
    /**
     * Load media library module
     */
    private function load_media_library_module() {
        // Media library module not yet implemented
        // This will be added in future development
        error_log('Tomatillo Media Studio: Media library module not yet implemented');
    }
    
    /**
     * Load admin module
     */
    private function load_admin_module() {
        $admin_file = TOMATILLO_MEDIA_STUDIO_DIR . 'includes/admin/class-admin.php';
        
        if (file_exists($admin_file)) {
            require_once $admin_file;
            $this->admin = new Tomatillo_Media_Admin();
        } else {
            error_log('Tomatillo Media Studio: Admin module file not found: ' . $admin_file);
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain(
            TOMATILLO_MEDIA_STUDIO_TEXT_DOMAIN,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
        
        // Initialize modules
        if ($this->core) {
            $this->core->init();
        }
        
        if ($this->optimization) {
            $this->optimization->init();
        }
        
        if ($this->media_library) {
            $this->media_library->init();
        }
        
        if ($this->admin) {
            $this->admin->init();
        }
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Admin-specific initialization
        if ($this->admin) {
            $this->admin->admin_init();
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule any necessary cron jobs
        $this->schedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron jobs
        $this->clear_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            attachment_id bigint(20) NOT NULL,
            original_format varchar(10) NOT NULL,
            avif_path varchar(255) DEFAULT NULL,
            webp_path varchar(255) DEFAULT NULL,
            original_size bigint(20) DEFAULT NULL,
            avif_size bigint(20) DEFAULT NULL,
            webp_size bigint(20) DEFAULT NULL,
            optimization_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'pending',
            PRIMARY KEY (id),
            KEY attachment_id (attachment_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'enable_optimization' => true,
            'enable_media_library' => true,
            'show_files_menu_link' => false,
            'avif_quality' => 80,
            'webp_quality' => 85,
            'auto_convert' => true,
            'min_savings_threshold' => 25,
            'skip_small_images' => true,
            'min_image_size' => 50000,
            'max_image_dimensions' => 4000,
            'enable_avif' => true,
            'enable_webp' => true,
            'conversion_timeout' => 30,
            'thumbnail_size' => 'large',
            'enable_bulk_ops' => true,
            'enable_advanced_search' => true,
        );
        
        add_option('tomatillo_media_studio_settings', $defaults);
        
        // Set default column count for media library
        add_option('tomatillo_media_column_count', 4);
    }
    
    /**
     * Schedule cron jobs
     */
    private function schedule_cron_jobs() {
        if (!wp_next_scheduled('tomatillo_media_cleanup')) {
            wp_schedule_event(time(), 'daily', 'tomatillo_media_cleanup');
        }
    }
    
    /**
     * Clear cron jobs
     */
    private function clear_cron_jobs() {
        wp_clear_scheduled_hook('tomatillo_media_cleanup');
    }
}

/**
 * Initialize the plugin
 */
function tomatillo_media_studio() {
    return Tomatillo_Media_Studio::get_instance();
}

// Start the plugin
tomatillo_media_studio();
