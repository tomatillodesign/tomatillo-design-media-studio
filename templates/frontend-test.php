<?php
/**
 * Frontend Optimization Test Page
 * 
 * Demonstrates how optimized images are served on the frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = tomatillo_media_studio();
$frontend_swap = $plugin->frontend_swap;

// Get recent optimized images for testing
$recent_images = get_posts(array(
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'numberposts' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
));

// Get optimization stats
$stats = ($plugin->core) ? $plugin->core->get_optimization_stats() : array();
?>

<div class="wrap">
    <h1><?php _e('Frontend Optimization Test', 'tomatillo-media-studio'); ?></h1>
    
    <!-- How It Works -->
    <div class="card">
        <h2><?php _e('How Frontend Optimization Works', 'tomatillo-media-studio'); ?></h2>
        <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 15px 0;">
            <h3><?php _e('Browser Detection & Format Serving', 'tomatillo-media-studio'); ?></h3>
            <ol>
                <li><strong><?php _e('AVIF Detection', 'tomatillo-media-studio'); ?></strong> - Modern browsers (Chrome 85+, Firefox 93+)</li>
                <li><strong><?php _e('WebP Detection', 'tomatillo-media-studio'); ?></strong> - Broader support (Chrome 23+, Firefox 65+)</li>
                <li><strong><?php _e('Original Fallback', 'tomatillo-media-studio'); ?></strong> - Universal support (JPEG/PNG)</li>
            </ol>
            
            <h3><?php _e('Picture Element Generation', 'tomatillo-media-studio'); ?></h3>
            <pre style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto;"><code>&lt;picture&gt;
    &lt;source srcset="image.avif" type="image/avif"&gt;
    &lt;source srcset="image.webp" type="image/webp"&gt;
    &lt;img src="image.jpg" alt="Description"&gt;
&lt;/picture&gt;</code></pre>
            
            <h3><?php _e('Smart Detection Logic', 'tomatillo-media-studio'); ?></h3>
            <ul>
                <li><?php _e('Server-side: Check if optimized files exist', 'tomatillo-media-studio'); ?></li>
                <li><?php _e('Client-side: Browser automatically picks best format', 'tomatillo-media-studio'); ?></li>
                <li><?php _e('Fallback: Original file if optimized versions fail', 'tomatillo-media-studio'); ?></li>
            </ul>
        </div>
    </div>
    
    <!-- Current Browser Support -->
    <div class="card">
        <h2><?php _e('Your Browser Support', 'tomatillo-media-studio'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong><?php _e('AVIF Support', 'tomatillo-media-studio'); ?></strong></td>
                    <td id="avif-support">
                        <span style="color: orange;">⏳ <?php _e('Checking...', 'tomatillo-media-studio'); ?></span>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('WebP Support', 'tomatillo-media-studio'); ?></strong></td>
                    <td id="webp-support">
                        <span style="color: orange;">⏳ <?php _e('Checking...', 'tomatillo-media-studio'); ?></span>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('HTTP Accept Header', 'tomatillo-media-studio'); ?></strong></td>
                    <td><code><?php echo esc_html($_SERVER['HTTP_ACCEPT'] ?? 'Not available'); ?></code></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Test Images -->
    <div class="card">
        <h2><?php _e('Test Images', 'tomatillo-media-studio'); ?></h2>
        <p><?php _e('These images will be automatically optimized if conversion data exists:', 'tomatillo-media-studio'); ?></p>
        
        <?php if (empty($recent_images)): ?>
            <p><?php _e('No images found. Please upload some images first.', 'tomatillo-media-studio'); ?></p>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">
                <?php foreach ($recent_images as $image): ?>
                    <?php
                    $image_url = wp_get_attachment_url($image->ID);
                    $image_meta = wp_get_attachment_metadata($image->ID);
                    $file_size = file_exists(get_attached_file($image->ID)) ? filesize(get_attached_file($image->ID)) : 0;
                    ?>
                    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
                        <h4 style="margin: 0 0 10px 0;"><?php echo esc_html($image->post_title); ?></h4>
                        
                        <!-- Original Image -->
                        <div style="margin-bottom: 15px;">
                            <h5><?php _e('Original Image', 'tomatillo-media-studio'); ?></h5>
                            <?php echo wp_get_attachment_image($image->ID, 'medium'); ?>
                            <p style="font-size: 12px; color: #666; margin: 5px 0;">
                                <?php echo isset($image_meta['width']) ? $image_meta['width'] . '×' . $image_meta['height'] : 'Unknown'; ?> | 
                                <?php echo size_format($file_size); ?>
                            </p>
                        </div>
                        
                        <!-- Optimized Image (if available) -->
                        <?php if ($frontend_swap): ?>
                            <div>
                                <h5><?php _e('Optimized Image (Auto-detected)', 'tomatillo-media-studio'); ?></h5>
                                <div id="optimized-image-<?php echo $image->ID; ?>">
                                    <span style="color: orange;">⏳ <?php _e('Loading optimized version...', 'tomatillo-media-studio'); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Optimization Stats -->
    <div class="card">
        <h2><?php _e('Optimization Statistics', 'tomatillo-media-studio'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong><?php _e('Total Optimized Images', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo number_format($stats['total_conversions']); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('AVIF Conversions', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo number_format($stats['avif_conversions']); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('WebP Conversions', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo number_format($stats['webp_conversions']); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Total Space Saved', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo size_format($stats['total_space_saved']); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Average Savings', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo round($stats['average_savings'], 1); ?>%</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <h2><?php _e('Quick Actions', 'tomatillo-media-studio'); ?></h2>
        <p>
            <a href="<?php echo admin_url('admin.php?page=tomatillo-media-studio-test'); ?>" class="button button-secondary">
                <?php _e('Test Conversion', 'tomatillo-media-studio'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=tomatillo-media-studio-optimization'); ?>" class="button button-secondary">
                <?php _e('Optimization Dashboard', 'tomatillo-media-studio'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=tomatillo-media-studio-tools'); ?>" class="button button-secondary">
                <?php _e('Tools & Logs', 'tomatillo-media-studio'); ?>
            </a>
        </p>
    </div>
</div>

<script>
// Browser support detection
document.addEventListener('DOMContentLoaded', function() {
    // Test AVIF support
    const avifCanvas = document.createElement('canvas');
    avifCanvas.width = 1;
    avifCanvas.height = 1;
    
    const avifDataURL = avifCanvas.toDataURL('image/avif');
    const avifSupported = avifDataURL.indexOf('data:image/avif') === 0;
    
    document.getElementById('avif-support').innerHTML = avifSupported 
        ? '<span style="color: green;">✓ ' + '<?php _e('Supported', 'tomatillo-media-studio'); ?>' + '</span>'
        : '<span style="color: red;">✗ ' + '<?php _e('Not Supported', 'tomatillo-media-studio'); ?>' + '</span>';
    
    // Test WebP support
    const webpCanvas = document.createElement('canvas');
    webpCanvas.width = 1;
    webpCanvas.height = 1;
    
    const webpDataURL = webpCanvas.toDataURL('image/webp');
    const webpSupported = webpDataURL.indexOf('data:image/webp') === 0;
    
    document.getElementById('webp-support').innerHTML = webpSupported 
        ? '<span style="color: green;">✓ ' + '<?php _e('Supported', 'tomatillo-media-studio'); ?>' + '</span>'
        : '<span style="color: red;">✗ ' + '<?php _e('Not Supported', 'tomatillo-media-studio'); ?>' + '</span>';
    
    // Load optimized images
    <?php if (!empty($recent_images)): ?>
        <?php foreach ($recent_images as $image): ?>
            loadOptimizedImage(<?php echo $image->ID; ?>);
        <?php endforeach; ?>
    <?php endif; ?>
});

function loadOptimizedImage(attachmentId) {
    // This would normally be handled by the frontend swap class
    // For demo purposes, we'll show the original image
    const container = document.getElementById('optimized-image-' + attachmentId);
    if (container) {
        container.innerHTML = '<span style="color: green;">✓ <?php _e('Optimized version would be served here', 'tomatillo-media-studio'); ?></span>';
    }
}
</script>
