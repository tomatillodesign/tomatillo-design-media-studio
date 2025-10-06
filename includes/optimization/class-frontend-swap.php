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
    private $debug_swaps = 0;
    
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
        // Ensure all common image render paths are covered
        add_filter('wp_get_attachment_image_attributes', array($this, 'filter_image_attributes'), 10, 3);
        add_filter('wp_calculate_image_srcset', array($this, 'filter_srcset'), 10, 5);
        add_action('wp_head', array($this, 'add_preload_hints'));
        // Output-buffer catch-all layer
        add_action('template_redirect', array($this, 'enable_output_buffer'), 1);
    }
    
    /**
     * Initialize frontend optimization
     */
    public function init() {
        $settings = $this->get_settings();
        // Respect Image Processing Engine setting
        if (!$settings || !$settings->is_image_engine_enabled()) {
            $this->log('Frontend swap disabled (optimization not enabled)');
            return;
        }
        
        // Log initialization
        if ($settings->is_debug_mode()) {
            $this->log('Frontend swap initialized');
        }
    }

    /**
     * Start output buffering to rewrite remaining <img> tags across full HTML
     */
    public function enable_output_buffer() {
        if (is_admin() || is_feed() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }
        $settings = $this->get_settings();
        if (!$settings || !$settings->is_image_engine_enabled()) {
            return;
        }
        if (ob_get_level() > 0) {
            // Layer our callback on top
            ob_start(array($this, 'rewrite_final_html'));
        } else {
            ob_start(array($this, 'rewrite_final_html'));
        }
        $this->log('Output buffer rewrite enabled');
    }

    /**
     * Callback to rewrite final HTML output
     */
    public function rewrite_final_html($html) {
        if (stripos($html, '<img') === false) {
            return $html;
        }
        $swaps = 0;
        $pattern = '/<img([^>]*?)src=["\']([^"\']*?)["\']([^>]*?)>/i';
        $html = preg_replace_callback($pattern, function($matches) use (&$swaps) {
            $before_src = $matches[1];
            $src = $matches[2];
            $after_src = $matches[3];
            // If the src is already AVIF/WebP, keep it and mark as already optimized (avoid confusing skip message)
            if (preg_match('/\.(avif|webp)(?:\?.*)?$/i', $src)) {
                if ($this->is_debug_on_page()) {
                    return "<!-- Tomatillo OB pass: already-optimized src ${src} -->" . $matches[0];
                }
                return $matches[0];
            }
            $attachment_id = $this->get_attachment_id_from_url($src);
            if (!$attachment_id && preg_match('/data-id=["\'](\d+)["\']/', $matches[0], $m)) {
                $attachment_id = intval($m[1]);
            }
            if (!$attachment_id) {
                // Try class="wp-image-123"
                $classAttr = $this->extract_attribute($matches[0], 'class');
                if ($classAttr && preg_match('/wp-image-(\d+)/', $classAttr, $cm)) {
                    $attachment_id = intval($cm[1]);
                }
            }
            if (!$attachment_id) {
                if ($this->is_debug_on_page()) {
                    return "<!-- Tomatillo OB skip: no-id for src ${src} -->" . $matches[0];
                }
                return $matches[0];
            }
            if (!$this->should_optimize_image($attachment_id)) {
                if ($this->is_debug_on_page()) {
                    return "<!-- Tomatillo OB skip: ineligible id=${attachment_id} -->" . $matches[0];
                }
                return $matches[0];
            }
            $full_tag = $matches[0];
            $srcset = $this->extract_attribute($full_tag, 'srcset');
            $sizes = $this->extract_attribute($full_tag, 'sizes');
            $alt   = $this->extract_attribute($full_tag, 'alt');
            $class = $this->extract_attribute($full_tag, 'class');
            $width = $this->extract_attribute($full_tag, 'width');
            $height= $this->extract_attribute($full_tag, 'height');
            $img_attr = array();
            if ($alt !== null) $img_attr['alt'] = $alt;
            if ($class !== null) $img_attr['class'] = $class;
            if ($width !== null) $img_attr['width'] = $width;
            if ($height !== null) $img_attr['height'] = $height;
            if ($sizes !== null) $img_attr['sizes'] = $sizes;
            if ($srcset !== null) $img_attr['srcset'] = $srcset;
            $picture = $this->generate_picture_element($attachment_id, 'full', $img_attr, $srcset, $sizes);
            if (!$picture) {
                if ($this->is_debug_on_page()) {
                    return "<!-- Tomatillo OB skip: picture-gen-failed id=${attachment_id} -->" . $matches[0];
                }
                return $matches[0];
            }
            if ($this->is_debug_on_page()) {
                $picture = "<!-- Tomatillo OB swap {$attachment_id} -->" . $picture . "<!-- /Tomatillo -->";
            }
            $swaps++;
            return $picture;
        }, $html);
        if ($this->is_debug_on_page()) {
            $html = "<!-- Tomatillo OB processed; swaps={$swaps} -->" . $html;
        }
        if ($swaps > 0) {
            $this->log("Output buffer swaps performed: {$swaps}");
        }
        return $html;
    }
    
    /**
     * Optimize attachment image source
     */
    public function optimize_attachment_src($image, $attachment_id, $size, $icon) {
        if (!$image || $icon || !$this->should_optimize_image($attachment_id)) {
            $this->log("optimize_attachment_src: skipping (icon or not eligible) id=$attachment_id");
            return $image;
        }
        
        $optimized_url = $this->get_optimized_image_url($image[0], $attachment_id);
        if ($optimized_url) {
            $image[0] = $optimized_url;
            $this->log("optimize_attachment_src: swapped src to optimized for id=$attachment_id");
        }
        
        return $image;
    }
    
    /**
     * Optimize attachment image HTML
     */
    public function optimize_attachment_image($html, $attachment_id, $size, $icon, $attr) {
        if (!$html || $icon || !$this->should_optimize_image($attachment_id)) {
            $this->log("optimize_attachment_image: leaving original markup (icon or not eligible) id=$attachment_id");
            return $html;
        }
        
        $this->log("optimize_attachment_image: generating <picture> for id=$attachment_id size=$size");
        return $this->generate_picture_element($attachment_id, $size, $attr);
    }
    
    /**
     * Optimize images in content
     */
    public function optimize_content_images($content) {
        if (!$this->get_settings() || !$this->get_settings()->is_optimization_enabled()) {
            return $content;
        }
        
        // Skip in admin, feeds, or REST responses
        if (is_admin() || is_feed()) {
            return $content;
        }
        
        // Find all img tags in content
        $pattern = '/<img([^>]*?)src=["\']([^"\']*?)["\']([^>]*?)>/i';
        $before = $content;
        $this->debug_swaps = 0;
        $content = preg_replace_callback($pattern, array($this, 'optimize_content_image'), $content);
        if ($before !== $content) {
            $this->log('optimize_content_images: content images processed and swapped');
        } else {
            $this->log('optimize_content_images: no changes to content');
        }
        if ($this->is_debug_on_page()) {
            $content = "<!-- Tomatillo content processed; swaps={$this->debug_swaps} -->" . $content . "<!-- /Tomatillo content -->";
        }
        
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
        if (!$attachment_id) {
            // Try class="wp-image-123"
            $classAttr = $this->extract_attribute($matches[0], 'class');
            if ($classAttr && preg_match('/wp-image-(\d+)/', $classAttr, $cm)) {
                $attachment_id = intval($cm[1]);
                $this->log('optimize_content_image: using wp-image class fallback id=' . $attachment_id);
            }
        }
        // Fallback: try data-id attribute from the tag itself
        if (!$attachment_id && preg_match('/data-id=["\'](\d+)["\']/', $matches[0], $m)) {
            $attachment_id = intval($m[1]);
            $this->log('optimize_content_image: using data-id fallback id=' . $attachment_id);
        }
        if (!$attachment_id || !$this->should_optimize_image($attachment_id)) {
            $this->log('optimize_content_image: no attachment id or not eligible for src=' . $src);
            return $matches[0];
        }
        
        // Extract optional srcset and sizes
        $full_tag = $matches[0];
        $srcset = $this->extract_attribute($full_tag, 'srcset');
        $sizes = $this->extract_attribute($full_tag, 'sizes');
        $alt   = $this->extract_attribute($full_tag, 'alt');
        $class = $this->extract_attribute($full_tag, 'class');
        $width = $this->extract_attribute($full_tag, 'width');
        $height= $this->extract_attribute($full_tag, 'height');
        
        // Build attributes array for fallback <img>
        $img_attr = array();
        if ($alt !== null) $img_attr['alt'] = $alt;
        if ($class !== null) $img_attr['class'] = $class;
        if ($width !== null) $img_attr['width'] = $width;
        if ($height !== null) $img_attr['height'] = $height;
        if ($sizes !== null) $img_attr['sizes'] = $sizes;
        if ($srcset !== null) $img_attr['srcset'] = $srcset; // keep original as fallback
        
        // Generate picture element, attempting to transform srcset to optimized where files exist
        $this->log("optimize_content_image: building <picture> for attachment id=$attachment_id");
        $picture_html = $this->generate_picture_element($attachment_id, 'full', $img_attr, $srcset, $sizes);
        if ($this->is_debug()) {
            $picture_html = "<!-- Tomatillo swapped attachment {$attachment_id} -->" . $picture_html . "<!-- /Tomatillo -->";
        }
        $this->debug_swaps++;
        
        return $picture_html;
    }
    
    /**
     * Generate picture element with optimized sources
     */
    private function generate_picture_element($attachment_id, $size, $attr, $original_srcset = null, $sizes = null) {
        $original_url = wp_get_attachment_image_url($attachment_id, $size);
        if (!$original_url) {
            return wp_get_attachment_image($attachment_id, $size, false, $attr);
        }
        
        // Get optimization data
        $optimization_data = $this->get_optimization_data($attachment_id);
        if (!$optimization_data) {
            // Fallback A: meta URLs created during optimization
            $avif_meta = get_post_meta($attachment_id, '_tomatillo_avif_url', true);
            $webp_meta = get_post_meta($attachment_id, '_tomatillo_webp_url', true);
            $upload = wp_upload_dir();
            if ($avif_meta || $webp_meta) {
                $optimization_data = (object) array(
                    'avif_path' => $avif_meta ? str_replace($upload['baseurl'], $upload['basedir'], $avif_meta) : null,
                    'webp_path' => $webp_meta ? str_replace($upload['baseurl'], $upload['basedir'], $webp_meta) : null,
                );
            } else {
                // Fallback B: derive filesystem neighbors (support -scaled.avif/webp)
                $file_path = get_attached_file($attachment_id);
                if ($file_path) {
                    $pi = pathinfo($file_path);
                    $base = $pi['dirname'] . '/' . $pi['filename'];
                    $candidates = array(
                        $base . '.avif',
                        $base . '.webp',
                        $base . '-scaled.avif',
                        $base . '-scaled.webp',
                    );
                    $avif_path = null; $webp_path = null;
                    foreach ($candidates as $cand) {
                        if (file_exists($cand)) {
                            if (substr($cand, -5) === '.avif') $avif_path = $avif_path ?: $cand;
                            if (substr($cand, -5) === '.webp') $webp_path = $webp_path ?: $cand;
                        }
                    }
                    if ($avif_path || $webp_path) {
                        $optimization_data = (object) array(
                            'avif_path' => $avif_path,
                            'webp_path' => $webp_path,
                        );
                    } else {
                        $this->log("generate_picture_element: no optimization data or files for id=$attachment_id");
                        return wp_get_attachment_image($attachment_id, $size, false, $attr);
                    }
                } else {
                    $this->log("generate_picture_element: missing file path for id=$attachment_id");
                    return wp_get_attachment_image($attachment_id, $size, false, $attr);
                }
            }
        }
        
        // Build picture element
        $picture_html = '<picture>';
        
        // Attempt to transform srcset to AVIF/WebP variants if files exist for each candidate
        $avif_srcset = $this->build_optimized_srcset($original_srcset, 'avif');
        $webp_srcset = $this->build_optimized_srcset($original_srcset, 'webp');
        
        // Add AVIF source if available
        if (($optimization_data->avif_path && file_exists($optimization_data->avif_path)) || $avif_srcset) {
            $srcset_str = $avif_srcset ?: $this->get_optimized_url($original_url, $optimization_data->avif_path);
            $picture_html .= '<source ' . ($sizes ? 'sizes="' . esc_attr($sizes) . '" ' : '') . 'srcset="' . esc_attr($srcset_str) . '" type="image/avif">';
            $this->log("generate_picture_element: added AVIF source for id=$attachment_id");
        }
        
        // Add WebP source if available
        if (($optimization_data->webp_path && file_exists($optimization_data->webp_path)) || $webp_srcset) {
            $srcset_str = $webp_srcset ?: $this->get_optimized_url($original_url, $optimization_data->webp_path);
            $picture_html .= '<source ' . ($sizes ? 'sizes="' . esc_attr($sizes) . '" ' : '') . 'srcset="' . esc_attr($srcset_str) . '" type="image/webp">';
            $this->log("generate_picture_element: added WebP source for id=$attachment_id");
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

    private function is_debug() {
        if (!function_exists('tomatillo_media_studio')) return false;
        $plugin = tomatillo_media_studio();
        return ($plugin && $plugin->settings && $plugin->settings->is_debug_mode());
    }

    private function is_debug_on_page() {
        return $this->is_debug() || isset($_GET['tomatillo_debug']);
    }

    /**
     * Filter attachment image attributes (handles src and srcset outside the_content)
     */
    public function filter_image_attributes($attr, $attachment, $size) {
        if (!$this->should_optimize_image($attachment->ID)) {
            $this->log("filter_image_attributes: not eligible id={$attachment->ID}");
            return $attr;
        }
        
        // Replace src to best optimized
        if (!empty($attr['src'])) {
            $attr['src'] = $this->get_optimized_image_url($attr['src'], $attachment->ID);
        }
        
        // Transform srcset candidates when possible
        if (!empty($attr['srcset'])) {
            $preferred = $this->browser_supports_avif() ? 'avif' : ($this->browser_supports_webp() ? 'webp' : null);
            if ($preferred) {
                $optimized = $this->build_optimized_srcset($attr['srcset'], $preferred, $attachment->ID);
                if ($optimized) {
                    $attr['srcset'] = $optimized;
                    $this->log("filter_image_attributes: transformed srcset for id={$attachment->ID} to $preferred");
                }
            }
        }
        
        return $attr;
    }

    /**
     * Filter calculated srcset array to point to AVIF/WebP files when present
     */
    public function filter_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
        $preferred = $this->browser_supports_avif() ? 'avif' : ($this->browser_supports_webp() ? 'webp' : null);
        if (!$preferred || empty($sources) || !$this->should_optimize_image($attachment_id)) {
            return $sources;
        }
        
        $upload_dir = wp_upload_dir();
        foreach ($sources as $width => $source) {
            $optimized_url = $this->derive_variant_url($source['url'], $preferred, $upload_dir);
            if ($optimized_url) {
                $sources[$width]['url'] = $optimized_url;
                $sources[$width]['type'] = 'image/' . $preferred;
            }
        }
        $this->log("filter_srcset: rewrote srcset candidates for id=$attachment_id format=$preferred");
        return $sources;
    }

    /**
     * Build optimized srcset string from an existing srcset list
     */
    private function build_optimized_srcset($srcset, $format, $attachment_id = null) {
        if (!$srcset) return '';
        $upload_dir = wp_upload_dir();
        $candidates = array_map('trim', explode(',', $srcset));
        $out = array();
        foreach ($candidates as $candidate) {
            if ($candidate === '') continue;
            // Candidate like: url 1024w
            $parts = preg_split('/\s+/', $candidate);
            $url = $parts[0];
            $descriptor = isset($parts[1]) ? ' ' . $parts[1] : '';
            $optimized_url = $this->derive_variant_url($url, $format, $upload_dir);
            if ($optimized_url) {
                $out[] = $optimized_url . $descriptor;
            }
        }
        return implode(', ', $out);
    }

    /**
     * Derive a variant url (avif/webp) next to an existing file if it exists
     */
    private function derive_variant_url($url, $format, $upload_dir) {
        $path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
        $path_info = pathinfo($path);
        if (empty($path_info['dirname']) || empty($path_info['filename'])) return '';
        $variant_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.' . $format;
        if (file_exists($variant_path)) {
            return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $variant_path);
        }
        return '';
    }

    /**
     * Extract attribute from an HTML tag (simple, safe for img tag usage here)
     */
    private function extract_attribute($tag, $attr) {
        if (preg_match('/' . preg_quote($attr, '/') . '=["\']([^"\']*)["\']/', $tag, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Lightweight logger honoring debug mode
     */
    private function log($message) {
        if (!function_exists('tomatillo_media_studio')) return;
        $plugin = tomatillo_media_studio();
        if (!$plugin || !$plugin->settings || !$plugin->settings->is_debug_mode()) return;
        if ($plugin->core) {
            $plugin->core->log($message, 'info');
        } else {
            error_log('[Tomatillo Frontend Swap] ' . $message);
        }
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
        if (empty($url)) return 0;
        
        // Normalize URL: strip querystrings, decode, and remove WP size and -scaled suffixes
        $path = parse_url($url, PHP_URL_PATH);
        $path = urldecode($path);
        $filename = basename($path);
        // Remove -123x456 size suffix and -scaled
        $normalized = preg_replace('/-\d+x\d+(?=\.[a-zA-Z]+$)/', '', $filename);
        $normalized = str_replace('-scaled', '', $normalized);
        
        // First try exact LIKE on normalized
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
            '%' . $wpdb->esc_like($normalized)
        ));
        if ($attachment_id) return (int) $attachment_id;
        
        // Fallback: try original filename
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
            '%' . $wpdb->esc_like($filename)
        ));
        if ($attachment_id) return (int) $attachment_id;
        
        // Fallback: parse wp-image-123 class if present in global post content context (handled elsewhere), so return 0 here
        return 0;
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
