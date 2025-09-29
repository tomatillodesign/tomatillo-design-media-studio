<?php
/**
 * Test interface for image optimization
 * 
 * This template provides a simple interface for testing the conversion system
 */

if (!defined('ABSPATH')) {
    exit;
}

$optimizer = tomatillo_media_studio()->optimization;
$capabilities = $optimizer ? $optimizer->get_capabilities() : array();
$stats = $optimizer ? $optimizer->get_conversion_stats() : array();

// Get recent images for testing
$recent_images = get_posts(array(
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'numberposts' => 10,
    'orderby' => 'date',
    'order' => 'DESC'
));
?>

<div class="wrap">
    <h1><?php _e('Image Optimization Test', 'tomatillo-media-studio'); ?></h1>
    
    <?php if (!$optimizer): ?>
        <div class="notice notice-error">
            <p><?php _e('Optimization module is not enabled. Please enable it in the settings.', 'tomatillo-media-studio'); ?></p>
        </div>
    <?php else: ?>
        
        <!-- System Capabilities -->
        <div class="card">
            <h2><?php _e('System Capabilities', 'tomatillo-media-studio'); ?></h2>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong><?php _e('GD AVIF Support', 'tomatillo-media-studio'); ?></strong></td>
                        <td><?php echo $capabilities['gd_avif'] ? '<span style="color: green;">✓ ' . __('Available', 'tomatillo-media-studio') . '</span>' : '<span style="color: red;">✗ ' . __('Not Available', 'tomatillo-media-studio') . '</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('GD WebP Support', 'tomatillo-media-studio'); ?></strong></td>
                        <td><?php echo $capabilities['gd_webp'] ? '<span style="color: green;">✓ ' . __('Available', 'tomatillo-media-studio') . '</span>' : '<span style="color: red;">✗ ' . __('Not Available', 'tomatillo-media-studio') . '</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Imagick Support', 'tomatillo-media-studio'); ?></strong></td>
                        <td><?php echo $capabilities['imagick'] ? '<span style="color: green;">✓ ' . __('Available', 'tomatillo-media-studio') . '</span>' : '<span style="color: red;">✗ ' . __('Not Available', 'tomatillo-media-studio') . '</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Memory Limit', 'tomatillo-media-studio'); ?></strong></td>
                        <td><?php echo size_format($capabilities['memory_limit']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Current Settings -->
        <div class="card">
            <h2><?php _e('Current Settings', 'tomatillo-media-studio'); ?></h2>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong><?php _e('AVIF Quality', 'tomatillo-media-studio'); ?></strong></td>
                        <td><?php echo tomatillo_media_studio()->settings->get_avif_quality(); ?>%</td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('WebP Quality', 'tomatillo-media-studio'); ?></strong></td>
                        <td><?php echo tomatillo_media_studio()->settings->get_webp_quality(); ?>%</td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Minimum Savings Threshold', 'tomatillo-media-studio'); ?></strong></td>
                        <td><?php echo tomatillo_media_studio()->settings->get_min_savings_threshold(); ?>%</td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Skip Small Images', 'tomatillo-media-studio'); ?></strong></td>
                        <td><?php echo tomatillo_media_studio()->settings->should_skip_small_images() ? __('Yes', 'tomatillo-media-studio') : __('No', 'tomatillo-media-studio'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Minimum Image Size', 'tomatillo-media-studio'); ?></strong></td>
                        <td><?php echo size_format(tomatillo_media_studio()->settings->get_min_image_size()); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Conversion Statistics -->
        <div class="card">
            <h2><?php _e('Conversion Statistics', 'tomatillo-media-studio'); ?></h2>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong><?php _e('Total Conversions', 'tomatillo-media-studio'); ?></strong></td>
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
        
        <!-- Test Conversion -->
        <div class="card">
            <h2><?php _e('Test Image Conversion', 'tomatillo-media-studio'); ?></h2>
            <p><?php _e('Select an image below to test the conversion system:', 'tomatillo-media-studio'); ?></p>
            
            <?php if (empty($recent_images)): ?>
                <p><?php _e('No images found. Please upload some images first.', 'tomatillo-media-studio'); ?></p>
            <?php else: ?>
                <div class="image-test-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php foreach ($recent_images as $image): ?>
                        <?php
                        $image_url = wp_get_attachment_url($image->ID);
                        $image_meta = wp_get_attachment_metadata($image->ID);
                        $file_size = file_exists(get_attached_file($image->ID)) ? filesize(get_attached_file($image->ID)) : 0;
                        ?>
                        <div class="image-test-item" style="border: 1px solid #ddd; padding: 10px; text-align: center;">
                            <img src="<?php echo esc_url($image_url); ?>" 
                                 style="max-width: 100%; height: 120px; object-fit: cover; margin-bottom: 10px;"
                                 alt="<?php echo esc_attr($image->post_title); ?>">
                            <h4 style="margin: 5px 0;"><?php echo esc_html($image->post_title); ?></h4>
                            <p style="margin: 5px 0; font-size: 12px; color: #666;">
                                <?php echo isset($image_meta['width']) ? $image_meta['width'] . '×' . $image_meta['height'] : 'Unknown'; ?><br>
                                <?php echo size_format($file_size); ?>
                            </p>
                            <button type="button" 
                                    class="button button-primary test-conversion" 
                                    data-attachment-id="<?php echo $image->ID; ?>"
                                    data-image-title="<?php echo esc_attr($image->post_title); ?>">
                                <?php _e('Test Conversion', 'tomatillo-media-studio'); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Conversion Results -->
        <div id="conversion-results" class="card" style="display: none;">
            <h2><?php _e('Conversion Results', 'tomatillo-media-studio'); ?></h2>
            <div id="results-content"></div>
        </div>
        
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('.test-conversion').on('click', function() {
        var button = $(this);
        var attachmentId = button.data('attachment-id');
        var imageTitle = button.data('image-title');
        
        // Disable button and show loading
        button.prop('disabled', true).text('<?php _e('Converting...', 'tomatillo-media-studio'); ?>');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tomatillo_test_conversion',
                attachment_id: attachmentId,
                nonce: '<?php echo wp_create_nonce('tomatillo_media_studio'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var result = response.data;
                    var html = '<h3>' + imageTitle + '</h3>';
                    html += '<table class="widefat">';
                    html += '<tr><td><strong><?php _e('Original Size', 'tomatillo-media-studio'); ?></strong></td><td>' + formatBytes(result.original_size) + '</td></tr>';
                    
                    if (result.avif_path) {
                        html += '<tr><td><strong><?php _e('AVIF Size', 'tomatillo-media-studio'); ?></strong></td><td>' + formatBytes(result.avif_size) + ' (' + Math.round(result.avif_savings) + '% savings)</td></tr>';
                    }
                    
                    if (result.webp_path) {
                        html += '<tr><td><strong><?php _e('WebP Size', 'tomatillo-media-studio'); ?></strong></td><td>' + formatBytes(result.webp_size) + ' (' + Math.round(result.webp_savings) + '% savings)</td></tr>';
                    }
                    
                    html += '<tr><td><strong><?php _e('Status', 'tomatillo-media-studio'); ?></strong></td><td>' + result.message + '</td></tr>';
                    html += '</table>';
                    
                    $('#results-content').html(html);
                    $('#conversion-results').show();
                } else {
                    alert('<?php _e('Conversion failed:', 'tomatillo-media-studio'); ?> ' + response.data);
                }
            },
            error: function() {
                alert('<?php _e('An error occurred during conversion.', 'tomatillo-media-studio'); ?>');
            },
            complete: function() {
                // Re-enable button
                button.prop('disabled', false).text('<?php _e('Test Conversion', 'tomatillo-media-studio'); ?>');
            }
        });
    });
    
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
</script>
