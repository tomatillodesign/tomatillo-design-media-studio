<?php
/**
 * Optimization Dashboard
 * 
 * Provides bulk optimization tools for existing images with 100% backward compatibility
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = tomatillo_media_studio();
$settings = $plugin->settings;
$stats = ($plugin->core) ? $plugin->core->get_optimization_stats() : array();
$optimizer = $plugin->optimization;

// Handle bulk optimization
if (isset($_POST['start_bulk_optimization']) && wp_verify_nonce($_POST['_wpnonce'], 'tomatillo_bulk_optimize')) {
    if ($plugin->core) {
        $plugin->core->start_bulk_optimization();
    }
    echo '<div class="notice notice-success"><p>' . __('Bulk optimization started! Check the progress below.', 'tomatillo-media-studio') . '</p></div>';
}

// Get unoptimized images count
$unoptimized_count = ($plugin->core) ? $plugin->core->get_unoptimized_images_count() : 0;
?>

<div class="wrap">
    <h1><?php _e('Image Optimization Dashboard', 'tomatillo-media-studio'); ?></h1>
    
    <!-- Overview Stats -->
    <div class="card">
        <h2><?php _e('Optimization Overview', 'tomatillo-media-studio'); ?></h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
            <div style="text-align: center; padding: 20px; background: #f9f9f9; border-radius: 8px;">
                <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo number_format(isset($stats['total_conversions']) ? $stats['total_conversions'] : 0); ?></h3>
                <p style="margin: 5px 0 0 0;"><?php _e('Images Optimized', 'tomatillo-media-studio'); ?></p>
            </div>
            <div style="text-align: center; padding: 20px; background: #f9f9f9; border-radius: 8px;">
                <h3 style="margin: 0; font-size: 2em; color: #00a32a;"><?php echo size_format(isset($stats['total_space_saved']) ? $stats['total_space_saved'] : 0); ?></h3>
                <p style="margin: 5px 0 0 0;"><?php _e('Space Saved', 'tomatillo-media-studio'); ?></p>
            </div>
            <div style="text-align: center; padding: 20px; background: #f9f9f9; border-radius: 8px;">
                <h3 style="margin: 0; font-size: 2em; color: #d63638;"><?php echo number_format($unoptimized_count); ?></h3>
                <p style="margin: 5px 0 0 0;"><?php _e('Pending Optimization', 'tomatillo-media-studio'); ?></p>
            </div>
            <div style="text-align: center; padding: 20px; background: #f9f9f9; border-radius: 8px;">
                <h3 style="margin: 0; font-size: 2em; color: #8c8f94;"><?php echo round(isset($stats['average_savings']) ? $stats['average_savings'] : 0, 1); ?>%</h3>
                <p style="margin: 5px 0 0 0;"><?php _e('Average Savings', 'tomatillo-media-studio'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Bulk Optimization -->
    <div class="card">
        <h2><?php _e('Bulk Optimization', 'tomatillo-media-studio'); ?></h2>
        <p><?php _e('Optimize all existing images in your media library. This process runs in the background and is 100% backward compatible.', 'tomatillo-media-studio'); ?></p>
        
        <?php if ($unoptimized_count > 0): ?>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin: 15px 0;">
                <h4 style="margin: 0 0 10px 0;"><?php _e('Ready to Optimize', 'tomatillo-media-studio'); ?></h4>
                <p style="margin: 0;">
                    <?php printf(__('Found %d images ready for optimization. Estimated time: %s', 'tomatillo-media-studio'), 
                        $unoptimized_count, 
                        ($plugin->core) ? $plugin->core->estimate_optimization_time($unoptimized_count) : 'Unknown'
                    ); ?>
                </p>
            </div>
            
            <form method="post" style="margin: 20px 0;">
                <?php wp_nonce_field('tomatillo_bulk_optimize'); ?>
                <input type="hidden" name="start_bulk_optimization" value="1">
                <button type="submit" class="button button-primary button-large" onclick="return confirm('<?php _e('Start bulk optimization? This will process all unoptimized images in the background.', 'tomatillo-media-studio'); ?>')">
                    <?php _e('Start Bulk Optimization', 'tomatillo-media-studio'); ?>
                </button>
            </form>
        <?php else: ?>
            <div style="background: #d1edff; border: 1px solid #72aee6; padding: 15px; border-radius: 4px; margin: 15px 0;">
                <h4 style="margin: 0 0 10px 0; color: #0073aa;"><?php _e('All Images Optimized!', 'tomatillo-media-studio'); ?></h4>
                <p style="margin: 0;"><?php _e('All images in your media library have been optimized. New uploads will be automatically optimized.', 'tomatillo-media-studio'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Optimization Settings -->
    <div class="card">
        <h2><?php _e('Current Settings', 'tomatillo-media-studio'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong><?php _e('Auto-Convert New Uploads', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo $settings->is_auto_convert_enabled() ? '<span style="color: green;">✓ ' . __('Enabled', 'tomatillo-media-studio') . '</span>' : '<span style="color: red;">✗ ' . __('Disabled', 'tomatillo-media-studio') . '</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('AVIF Conversion', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo $settings->is_avif_enabled() ? '<span style="color: green;">✓ ' . __('Enabled', 'tomatillo-media-studio') . '</span> (' . $settings->get_avif_quality() . '% quality)' : '<span style="color: red;">✗ ' . __('Disabled', 'tomatillo-media-studio') . '</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('WebP Conversion', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo $settings->is_webp_enabled() ? '<span style="color: green;">✓ ' . __('Enabled', 'tomatillo-media-studio') . '</span> (' . $settings->get_webp_quality() . '% quality)' : '<span style="color: red;">✗ ' . __('Disabled', 'tomatillo-media-studio') . '</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Minimum Savings Threshold', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo $settings->get_min_savings_threshold(); ?>%</td>
                </tr>
                <tr>
                    <td><strong><?php _e('Skip Small Images', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo $settings->should_skip_small_images() ? __('Yes', 'tomatillo-media-studio') . ' (' . size_format($settings->get_min_image_size()) . ')' : __('No', 'tomatillo-media-studio'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Maximum Image Dimensions', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo $settings->get_max_image_dimensions(); ?>px</td>
                </tr>
            </tbody>
        </table>
        <p style="margin-top: 15px;">
            <a href="<?php echo admin_url('admin.php?page=tomatillo-media-studio-settings'); ?>" class="button button-secondary">
                <?php _e('Edit Settings', 'tomatillo-media-studio'); ?>
            </a>
        </p>
    </div>
    
    <!-- System Capabilities -->
    <div class="card">
        <h2><?php _e('System Capabilities', 'tomatillo-media-studio'); ?></h2>
        <?php if ($optimizer): ?>
            <?php $capabilities = $optimizer->get_capabilities(); ?>
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
        <?php else: ?>
            <p><?php _e('Optimization module not available.', 'tomatillo-media-studio'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Recent Optimizations -->
    <div class="card">
        <h2><?php _e('Recent Optimizations', 'tomatillo-media-studio'); ?></h2>
        <?php 
        $recent_optimizations = ($plugin->core) ? $plugin->core->get_recent_optimizations(10) : array();
        if (empty($recent_optimizations)): ?>
            <p><?php _e('No recent optimizations found.', 'tomatillo-media-studio'); ?></p>
        <?php else: ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Image', 'tomatillo-media-studio'); ?></th>
                        <th><?php _e('Original Size', 'tomatillo-media-studio'); ?></th>
                        <th><?php _e('AVIF Size', 'tomatillo-media-studio'); ?></th>
                        <th><?php _e('WebP Size', 'tomatillo-media-studio'); ?></th>
                        <th><?php _e('Savings', 'tomatillo-media-studio'); ?></th>
                        <th><?php _e('Date', 'tomatillo-media-studio'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_optimizations as $optimization): ?>
                        <tr>
                            <td>
                                <?php 
                                $image_url = wp_get_attachment_url($optimization->attachment_id);
                                if ($image_url): ?>
                                    <img src="<?php echo esc_url($image_url); ?>" style="width: 50px; height: 50px; object-fit: cover;" alt="">
                                <?php else: ?>
                                    <?php echo __('Image not found', 'tomatillo-media-studio'); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo size_format($optimization->original_size); ?></td>
                            <td><?php echo $optimization->avif_size ? size_format($optimization->avif_size) : '-'; ?></td>
                            <td><?php echo $optimization->webp_size ? size_format($optimization->webp_size) : '-'; ?></td>
                            <td>
                                <?php 
                                $best_savings = max(
                                    $optimization->avif_size ? (($optimization->original_size - $optimization->avif_size) / $optimization->original_size) * 100 : 0,
                                    $optimization->webp_size ? (($optimization->original_size - $optimization->webp_size) / $optimization->original_size) * 100 : 0
                                );
                                echo round($best_savings, 1) . '%';
                                ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($optimization->optimization_date)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <h2><?php _e('Quick Actions', 'tomatillo-media-studio'); ?></h2>
        <p>
            <a href="<?php echo admin_url('admin.php?page=tomatillo-media-studio-test'); ?>" class="button button-secondary">
                <?php _e('Test Optimization', 'tomatillo-media-studio'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=tomatillo-media-studio-tools'); ?>" class="button button-secondary">
                <?php _e('Tools & Logs', 'tomatillo-media-studio'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=tomatillo-media-studio-settings'); ?>" class="button button-secondary">
                <?php _e('Settings', 'tomatillo-media-studio'); ?>
            </a>
        </p>
    </div>
</div>
