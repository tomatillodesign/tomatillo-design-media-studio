<?php
/**
 * Frontend image optimization and delivery
 * 
 * Handles serving optimized images with proper fallbacks
 */

if (!defined('ABSPATH')) {
    exit;
}

class Tomatillo_Frontend_Swap {
    
    /**
     * Settings instance
     */
    private $settings;
    
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
        add_filter('wp_get_attachment_image_src', array($this, 'optimize_attachment_src'), 10, 4);
        add_filter('wp_get_attachment_image', array($this, 'optimize_attachment_image'), 10, 5);
        add_filter('the_content', array($this, 'optimize_content_images'), 20);
        add_action('wp_head', array($this, 'add_preload_hints'));
    }
    
    /**
     * Initialize frontend optimization
     */
    public function init() {
        $settings = $this->get_settings();
        if (!$settings || !$settings->is_optimization_enabled()) {
            return;
        }
        
        // Log initialization
        if ($settings->is_debug_mode()) {
            error_log('Tomatillo Frontend Swap initialized');
        }
    }
    
    /**
     * Optimize attachment image source
     */
    public function optimize_attachment_src($image, $attachment_id, $size, $icon) {
        if (!$image || $icon || !$this->should_optimize_image($attachment_id)) {
            return $image;
        }
        
        $optimized_url = $this->get_optimized_image_url($image[0], $attachment_id);
        if ($optimized_url) {
            $image[0] = $optimized_url;
        }
        
        return $image;
    }
    
    /**
     * Optimize attachment image HTML
     */
    public function optimize_attachment_image($html, $attachment_id, $size, $icon, $attr) {
        if (!$html || $icon || !$this->should_optimize_image($attachment_id)) {
            return $html;
        }
        
        return $this->generate_picture_element($attachment_id, $size, $attr);
    }
    
    /**
     * Optimize images in content
     */
    public function optimize_content_images($content) {
        if (!$this->get_settings() || !$this->get_settings()->is_optimization_enabled()) {
            return $content;
        }
        
        // Find all img tags in content
        $pattern = '/<img([^>]*?)src=["\']([^"\']*?)["\']([^>]*?)>/i';
        $content = preg_replace_callback($pattern, array($this, 'optimize_content_image'), $content);
        
        return $content;
    }
    
    /**
     * Optimize individual content image
     */
    private function optimize_content_image($matches) {
        $before_src = $matches[1];
        $src = $matches[2];
        $after_src = $matches[3];
        
        // Extract attachment ID from URL
        $attachment_id = $this->get_attachment_id_from_url($src);
        if (!$attachment_id || !$this->should_optimize_image($attachment_id)) {
            return $matches[0];
        }
        
        // Generate picture element
        $picture_html = $this->generate_picture_element($attachment_id, 'full', array());
        
        return $picture_html;
    }
    
    /**
     * Generate picture element with optimized sources
     */
    private function generate_picture_element($attachment_id, $size, $attr) {
        $original_url = wp_get_attachment_image_url($attachment_id, $size);
        if (!$original_url) {
            return wp_get_attachment_image($attachment_id, $size, false, $attr);
        }
        
        // Get optimization data
        $optimization_data = $this->get_optimization_data($attachment_id);
        if (!$optimization_data) {
            return wp_get_attachment_image($attachment_id, $size, false, $attr);
        }
        
        // Build picture element
        $picture_html = '<picture>';
        
        // Add AVIF source if available
        if ($optimization_data->avif_path && file_exists($optimization_data->avif_path)) {
            $avif_url = $this->get_optimized_url($original_url, $optimization_data->avif_path);
            $picture_html .= '<source srcset="' . esc_url($avif_url) . '" type="image/avif">';
        }
        
        // Add WebP source if available
        if ($optimization_data->webp_path && file_exists($optimization_data->webp_path)) {
            $webp_url = $this->get_optimized_url($original_url, $optimization_data->webp_path);
            $picture_html .= '<source srcset="' . esc_url($webp_url) . '" type="image/webp">';
        }
        
        // Add fallback img tag
        $img_attr = '';
        if (!empty($attr)) {
            foreach ($attr as $key => $value) {
                $img_attr .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
            }
        }
        
        $picture_html .= '<img src="' . esc_url($original_url) . '"' . $img_attr . '>';
        $picture_html .= '</picture>';
        
        return $picture_html;
    }
    
    /**
     * Get optimized image URL
     */
    private function get_optimized_image_url($original_url, $attachment_id) {
        $optimization_data = $this->get_optimization_data($attachment_id);
        if (!$optimization_data) {
            return $original_url;
        }
        
        // Check browser support and return best format
        if ($this->browser_supports_avif() && $optimization_data->avif_path && file_exists($optimization_data->avif_path)) {
            return $this->get_optimized_url($original_url, $optimization_data->avif_path);
        }
        
        if ($this->browser_supports_webp() && $optimization_data->webp_path && file_exists($optimization_data->webp_path)) {
            return $this->get_optimized_url($original_url, $optimization_data->webp_path);
        }
        
        return $original_url;
    }
    
    /**
     * Get optimization data for attachment
     */
    private function get_optimization_data($attachment_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE attachment_id = %d AND status = 'completed'",
            $attachment_id
        ));
    }
    
    /**
     * Get optimized URL from original URL and optimized path
     */
    private function get_optimized_url($original_url, $optimized_path) {
        $upload_dir = wp_upload_dir();
        $upload_url = $upload_dir['baseurl'];
        
        // Convert file path to URL
        $optimized_url = str_replace($upload_dir['basedir'], $upload_url, $optimized_path);
        
        return $optimized_url;
    }
    
    /**
     * Check if image should be optimized
     */
    private function should_optimize_image($attachment_id) {
        $settings = $this->get_settings();
        if (!$settings || !$settings->is_optimization_enabled()) {
            return false;
        }
        
        // Check if it's an image
        $mime_type = get_post_mime_type($attachment_id);
        if (!in_array($mime_type, array('image/jpeg', 'image/png'))) {
            return false;
        }
        
        // Check if optimization data exists
        $optimization_data = $this->get_optimization_data($attachment_id);
        return !empty($optimization_data);
    }
    
    /**
     * Check if browser supports AVIF
     */
    private function browser_supports_avif() {
        if (!isset($_SERVER['HTTP_ACCEPT'])) {
            return false;
        }
        
        return strpos($_SERVER['HTTP_ACCEPT'], 'image/avif') !== false;
    }
    
    /**
     * Check if browser supports WebP
     */
    private function browser_supports_webp() {
        if (!isset($_SERVER['HTTP_ACCEPT'])) {
            return false;
        }
        
        return strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
    }
    
    /**
     * Get attachment ID from URL
     */
    private function get_attachment_id_from_url($url) {
        global $wpdb;
        
        // Extract filename from URL
        $filename = basename(parse_url($url, PHP_URL_PATH));
        
        // Query database for attachment
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s",
            '%' . $filename
        ));
        
        return $attachment_id;
    }
    
    /**
     * Add preload hints for critical images
     */
    public function add_preload_hints() {
        if (!$this->get_settings() || !$this->get_settings()->is_optimization_enabled()) {
            return;
        }
        
        // Get featured image if it exists
        if (is_singular() && has_post_thumbnail()) {
            $attachment_id = get_post_thumbnail_id();
            $this->add_preload_hint($attachment_id);
        }
    }
    
    /**
     * Add preload hint for specific image
     */
    private function add_preload_hint($attachment_id) {
        $optimization_data = $this->get_optimization_data($attachment_id);
        if (!$optimization_data) {
            return;
        }
        
        $original_url = wp_get_attachment_url($attachment_id);
        
        // Preload AVIF if available and supported
        if ($this->browser_supports_avif() && $optimization_data->avif_path && file_exists($optimization_data->avif_path)) {
            $avif_url = $this->get_optimized_url($original_url, $optimization_data->avif_path);
            echo '<link rel="preload" as="image" href="' . esc_url($avif_url) . '" type="image/avif">' . "\n";
        }
        // Preload WebP if AVIF not available
        elseif ($this->browser_supports_webp() && $optimization_data->webp_path && file_exists($optimization_data->webp_path)) {
            $webp_url = $this->get_optimized_url($original_url, $optimization_data->webp_path);
            echo '<link rel="preload" as="image" href="' . esc_url($webp_url) . '" type="image/webp">' . "\n";
        }
    }
    
    /**
     * Get optimization statistics for frontend
     */
    public function get_frontend_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tomatillo_media_optimization';
        
        $stats = array(
            'total_optimized' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'completed'"),
            'avif_served' => 0,
            'webp_served' => 0,
            'original_served' => 0,
            'total_bandwidth_saved' => 0,
        );
        
        return $stats;
    }
}
