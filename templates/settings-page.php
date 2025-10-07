<?php
/**
 * Settings page template
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = tomatillo_media_studio()->settings;
$stats = tomatillo_media_studio()->core->get_media_stats();
?>

<div class="wrap tomatillo-media-studio-settings">
    <h1><?php _e('Tomatillo Media Studio Settings', 'tomatillo-media-studio'); ?></h1>
    
    <div class="tomatillo-settings-header">
        <div class="tomatillo-stats-grid">
            <div class="tomatillo-stat-card">
                <h3><?php echo number_format($stats['total_media']); ?></h3>
                <p><?php _e('Total Media Files', 'tomatillo-media-studio'); ?></p>
            </div>
            <div class="tomatillo-stat-card">
                <h3><?php echo number_format($stats['total_images']); ?></h3>
                <p><?php _e('Images', 'tomatillo-media-studio'); ?></p>
            </div>
            <div class="tomatillo-stat-card">
                <h3><?php echo size_format($stats['total_size']); ?></h3>
                <p><?php _e('Total Size', 'tomatillo-media-studio'); ?></p>
            </div>
            <div class="tomatillo-stat-card">
                <h3><?php echo number_format($stats['recent_uploads']); ?></h3>
                <p><?php _e('Recent Uploads (30 days)', 'tomatillo-media-studio'); ?></p>
            </div>
        </div>
    </div>

    <form method="post" action="options.php" id="tomatillo-settings-form">
        <?php settings_fields('tomatillo_media_studio_settings'); ?>
        
        <div class="tomatillo-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#module-control" class="nav-tab nav-tab-active"><?php _e('Module Control', 'tomatillo-media-studio'); ?></a>
                <a href="#optimization" class="nav-tab"><?php _e('Optimization', 'tomatillo-media-studio'); ?></a>
                <a href="#advanced" class="nav-tab"><?php _e('Advanced', 'tomatillo-media-studio'); ?></a>
            </nav>
            
            <!-- Module Control Tab -->
            <div id="module-control" class="tab-content active">
                <h2><?php _e('Module Control', 'tomatillo-media-studio'); ?></h2>
                <p class="description"><?php _e('Enable or disable major plugin modules. Disabling a module will hide its features and reduce plugin overhead.', 'tomatillo-media-studio'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Image Processing Engine', 'tomatillo-media-studio'); ?></th>
                        <td>
                            <label class="tomatillo-toggle-label">
                                <div class="tomatillo-toggle">
                                    <input type="checkbox" name="tomatillo_media_studio_settings[enable_optimization]" value="1" <?php checked($settings->is_optimization_enabled()); ?> />
                                    <span class="tomatillo-toggle-slider"></span>
                                </div>
                                <?php _e('Enable automatic AVIF/WebP conversion and optimization', 'tomatillo-media-studio'); ?>
                            </label>
                            <p class="description"><?php _e('Automatically converts uploaded images to modern formats for better performance.', 'tomatillo-media-studio'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Enhanced Gallery Interface', 'tomatillo-media-studio'); ?></th>
                        <td>
                            <label class="tomatillo-toggle-label">
                                <div class="tomatillo-toggle">
                                    <input type="checkbox" name="tomatillo_media_studio_settings[enable_media_library]" value="1" <?php checked($settings->is_media_library_enabled()); ?> />
                                    <span class="tomatillo-toggle-slider"></span>
                                </div>
                                <?php _e('Enable enhanced media library interface', 'tomatillo-media-studio'); ?>
                            </label>
                            <p class="description"><?php _e('Provides a beautiful, modern interface for managing your media files.', 'tomatillo-media-studio'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Optimization Tab -->
            <div id="optimization" class="tab-content">
                <h2><?php _e('Optimization Settings', 'tomatillo-media-studio'); ?></h2>
                <p class="description"><?php _e('Configure image optimization settings for automatic conversion.', 'tomatillo-media-studio'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Auto-Convert New Uploads', 'tomatillo-media-studio'); ?></th>
                        <td>
                            <label class="tomatillo-toggle-label">
                                <div class="tomatillo-toggle">
                                    <input type="checkbox" name="tomatillo_media_studio_settings[auto_convert]" value="1" <?php checked($settings->is_auto_convert_enabled()); ?> />
                                    <span class="tomatillo-toggle-slider"></span>
                                </div>
                                <?php _e('Automatically optimize images when uploaded', 'tomatillo-media-studio'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('AVIF Quality', 'tomatillo-media-studio'); ?></th>
                        <td>
                            <input type="range" name="tomatillo_media_studio_settings[avif_quality]" min="1" max="100" value="<?php echo esc_attr($settings->get_avif_quality()); ?>" class="quality-slider" />
                            <span class="quality-value"><?php echo esc_html($settings->get_avif_quality()); ?>%</span>
                            <p class="description"><?php _e('Higher values produce better quality but larger file sizes.', 'tomatillo-media-studio'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('WebP Quality', 'tomatillo-media-studio'); ?></th>
                        <td>
                            <input type="range" name="tomatillo_media_studio_settings[webp_quality]" min="1" max="100" value="<?php echo esc_attr($settings->get_webp_quality()); ?>" class="quality-slider" />
                            <span class="quality-value"><?php echo esc_html($settings->get_webp_quality()); ?>%</span>
                            <p class="description"><?php _e('Higher values produce better quality but larger file sizes.', 'tomatillo-media-studio'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Batch Size', 'tomatillo-media-studio'); ?></th>
                        <td>
                            <input type="number" name="tomatillo_media_studio_settings[batch_size]" min="1" max="50" value="<?php echo esc_attr($settings->get_batch_size()); ?>" />
                            <p class="description"><?php _e('Number of images to process in each batch during bulk operations.', 'tomatillo-media-studio'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Minimum Savings Threshold', 'tomatillo-media-studio'); ?></th>
                        <td>
                            <input type="range" name="tomatillo_media_studio_settings[min_savings_threshold]" min="1" max="90" value="<?php echo esc_attr($settings->get_min_savings_threshold()); ?>" class="quality-slider" />
                            <span class="quality-value"><?php echo esc_html($settings->get_min_savings_threshold()); ?>%</span>
                            <p class="description"><?php _e('Only optimize images if they achieve at least this percentage of size reduction.', 'tomatillo-media-studio'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Skip Small Images', 'tomatillo-media-studio'); ?></th>
                        <td>
                            <label class="tomatillo-toggle-label">
                                <div class="tomatillo-toggle">
                                    <input type="checkbox" name="tomatillo_media_studio_settings[skip_small_images]" value="1" <?php checked($settings->should_skip_small_images()); ?> />
                                    <span class="tomatillo-toggle-slider"></span>
                                </div>
                                <?php _e('Skip images smaller than 50KB', 'tomatillo-media-studio'); ?>
                            </label>
                            <p class="description"><?php _e('Small images may not benefit significantly from optimization.', 'tomatillo-media-studio'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Preserve Originals', 'tomatillo-media-studio'); ?></th>
                        <td>
                            <label class="tomatillo-toggle-label">
                                <div class="tomatillo-toggle">
                                    <input type="checkbox" name="tomatillo_media_studio_settings[preserve_originals]" value="1" <?php checked($settings->get('preserve_originals')); ?> />
                                    <span class="tomatillo-toggle-slider"></span>
                                </div>
                                <?php _e('Keep original files after optimization', 'tomatillo-media-studio'); ?>
                            </label>
                            <p class="description"><?php _e('Recommended for safety, but increases storage usage.', 'tomatillo-media-studio'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Advanced Tab -->
            <div id="advanced" class="tab-content">
                <h2><?php _e('Advanced Settings', 'tomatillo-media-studio'); ?></h2>
                <p class="description"><?php _e('Advanced configuration options for power users.', 'tomatillo-media-studio'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Debug Mode', 'tomatillo-media-studio'); ?></th>
                        <td>
                            <label class="tomatillo-toggle-label">
                                <div class="tomatillo-toggle">
                                    <input type="checkbox" name="tomatillo_media_studio_settings[debug_mode]" value="1" <?php checked($settings->is_debug_mode()); ?> />
                                    <span class="tomatillo-toggle-slider"></span>
                                </div>
                                <?php _e('Enable debug logging', 'tomatillo-media-studio'); ?>
                            </label>
                            <p class="description"><?php _e('Logs detailed information to help troubleshoot issues.', 'tomatillo-media-studio'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Enable AVIF Conversion', 'tomatillo-media-studio'); ?></th>
                        <td>
                            <label class="tomatillo-toggle-label">
                                <div class="tomatillo-toggle">
                                    <input type="checkbox" name="tomatillo_media_studio_settings[enable_avif]" value="1" <?php checked($settings->is_avif_enabled()); ?> />
                                    <span class="tomatillo-toggle-slider"></span>
                                </div>
                                <?php _e('Convert images to AVIF format for maximum compression', 'tomatillo-media-studio'); ?>
                            </label>
                            <p class="description"><?php _e('AVIF provides the best compression but has limited browser support.', 'tomatillo-media-studio'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Enable WebP Conversion', 'tomatillo-media-studio'); ?></th>
                        <td>
                            <label class="tomatillo-toggle-label">
                                <div class="tomatillo-toggle">
                                    <input type="checkbox" name="tomatillo_media_studio_settings[enable_webp]" value="1" <?php checked($settings->is_webp_enabled()); ?> />
                                    <span class="tomatillo-toggle-slider"></span>
                                </div>
                                <?php _e('Convert images to WebP format for better compression', 'tomatillo-media-studio'); ?>
                            </label>
                            <p class="description"><?php _e('WebP provides good compression with wide browser support.', 'tomatillo-media-studio'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Maximum Image Dimensions', 'tomatillo-media-studio'); ?></th>
                        <td>
                            <input type="number" name="tomatillo_media_studio_settings[max_image_dimensions]" min="1000" max="8000" value="<?php echo esc_attr($settings->get_max_image_dimensions()); ?>" />
                            <p class="description"><?php _e('Maximum width or height for images to be processed (pixels).', 'tomatillo-media-studio'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Conversion Timeout', 'tomatillo-media-studio'); ?></th>
                        <td>
                            <input type="number" name="tomatillo_media_studio_settings[conversion_timeout]" min="5" max="300" value="<?php echo esc_attr($settings->get_conversion_timeout()); ?>" />
                            <p class="description"><?php _e('Maximum seconds allowed per image conversion.', 'tomatillo-media-studio'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php submit_button(__('Save Settings', 'tomatillo-media-studio')); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Remove active classes
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        
        // Add active class to clicked tab
        $(this).addClass('nav-tab-active');
        
        // Show corresponding content
        var target = $(this).attr('href');
        $(target).addClass('active');
    });
    
    // Quality slider updates
    $('.quality-slider').on('input', function() {
        $(this).next('.quality-value').text($(this).val() + '%');
    });
});
</script>
