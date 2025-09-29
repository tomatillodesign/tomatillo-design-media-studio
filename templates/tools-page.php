<?php
/**
 * Tools page for Tomatillo Media Studio
 * 
 * Provides debugging tools, log management, and system diagnostics
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = tomatillo_media_studio();
$settings = $plugin->settings;

// Get system information and logs
$system_info = ($plugin->core) ? $plugin->core->get_system_info() : array();
$plugin_logs = ($plugin->core) ? $plugin->core->get_plugin_logs() : array();
?>

<div class="wrap">
    <h1><?php _e('Media Studio - Tools', 'tomatillo-media-studio'); ?></h1>
    
    <!-- Debug Controls -->
    <div class="card">
        <h2><?php _e('Debug Controls', 'tomatillo-media-studio'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Debug Mode', 'tomatillo-media-studio'); ?></th>
                <td>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('tomatillo_toggle_debug'); ?>
                        <input type="hidden" name="toggle_debug" value="1">
                        <button type="submit" class="button <?php echo $settings->is_debug_mode() ? 'button-secondary' : 'button-primary'; ?>">
                            <?php echo $settings->is_debug_mode() ? __('Disable Debug Mode', 'tomatillo-media-studio') : __('Enable Debug Mode', 'tomatillo-media-studio'); ?>
                        </button>
                    </form>
                    <p class="description">
                        <?php if ($settings->is_debug_mode()): ?>
                            <span style="color: green;">✓ <?php _e('Debug mode is currently enabled', 'tomatillo-media-studio'); ?></span>
                        <?php else: ?>
                            <span style="color: orange;">⚠ <?php _e('Debug mode is currently disabled', 'tomatillo-media-studio'); ?></span>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Plugin Logs', 'tomatillo-media-studio'); ?></th>
                <td>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('tomatillo_clear_logs'); ?>
                        <input type="hidden" name="clear_logs" value="1">
                        <button type="submit" class="button button-secondary" onclick="return confirm('<?php _e('Are you sure you want to clear all plugin logs?', 'tomatillo-media-studio'); ?>')">
                            <?php _e('Clear All Logs', 'tomatillo-media-studio'); ?>
                        </button>
                    </form>
                    <p class="description"><?php _e('Clear all Tomatillo Media Studio debug logs', 'tomatillo-media-studio'); ?></p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- System Information -->
    <div class="card">
        <h2><?php _e('System Information', 'tomatillo-media-studio'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong><?php _e('WordPress Version', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('PHP Version', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Memory Limit', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo ini_get('memory_limit'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Max Execution Time', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo ini_get('max_execution_time'); ?>s</td>
                </tr>
                <tr>
                    <td><strong><?php _e('Upload Max Filesize', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo ini_get('upload_max_filesize'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Post Max Size', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo ini_get('post_max_size'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('GD Extension', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo extension_loaded('gd') ? '<span style="color: green;">✓ ' . __('Available', 'tomatillo-media-studio') . '</span>' : '<span style="color: red;">✗ ' . __('Not Available', 'tomatillo-media-studio') . '</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('GD AVIF Support', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo function_exists('imageavif') ? '<span style="color: green;">✓ ' . __('Available', 'tomatillo-media-studio') . '</span>' : '<span style="color: red;">✗ ' . __('Not Available', 'tomatillo-media-studio') . '</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('GD WebP Support', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo function_exists('imagewebp') ? '<span style="color: green;">✓ ' . __('Available', 'tomatillo-media-studio') . '</span>' : '<span style="color: red;">✗ ' . __('Not Available', 'tomatillo-media-studio') . '</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Imagick Extension', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo class_exists('Imagick') ? '<span style="color: green;">✓ ' . __('Available', 'tomatillo-media-studio') . '</span>' : '<span style="color: red;">✗ ' . __('Not Available', 'tomatillo-media-studio') . '</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Plugin Version', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo TOMATILLO_MEDIA_STUDIO_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Plugin Directory', 'tomatillo-media-studio'); ?></strong></td>
                    <td><code><?php echo TOMATILLO_MEDIA_STUDIO_DIR; ?></code></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Plugin Status -->
    <div class="card">
        <h2><?php _e('Plugin Status', 'tomatillo-media-studio'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong><?php _e('Optimization Module', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo $settings->is_optimization_enabled() ? '<span style="color: green;">✓ ' . __('Enabled', 'tomatillo-media-studio') . '</span>' : '<span style="color: red;">✗ ' . __('Disabled', 'tomatillo-media-studio') . '</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Media Library Module', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo $settings->is_media_library_enabled() ? '<span style="color: green;">✓ ' . __('Enabled', 'tomatillo-media-studio') . '</span>' : '<span style="color: red;">✗ ' . __('Disabled', 'tomatillo-media-studio') . '</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Auto Convert', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo $settings->is_auto_convert_enabled() ? '<span style="color: green;">✓ ' . __('Enabled', 'tomatillo-media-studio') . '</span>' : '<span style="color: red;">✗ ' . __('Disabled', 'tomatillo-media-studio') . '</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('AVIF Quality', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo $settings->get_avif_quality(); ?>%</td>
                </tr>
                <tr>
                    <td><strong><?php _e('WebP Quality', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo $settings->get_webp_quality(); ?>%</td>
                </tr>
                <tr>
                    <td><strong><?php _e('Minimum Savings Threshold', 'tomatillo-media-studio'); ?></strong></td>
                    <td><?php echo $settings->get_min_savings_threshold(); ?>%</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Plugin Logs -->
    <div class="card">
        <h2><?php _e('Plugin Logs', 'tomatillo-media-studio'); ?></h2>
        <?php if (empty($plugin_logs)): ?>
            <p><?php _e('No plugin logs found.', 'tomatillo-media-studio'); ?></p>
        <?php else: ?>
            <div style="max-height: 400px; overflow-y: auto; background: #f1f1f1; padding: 10px; border: 1px solid #ddd; font-family: monospace; font-size: 12px;">
                <?php foreach ($plugin_logs as $log_entry): ?>
                    <div style="margin-bottom: 5px; padding: 2px 0; border-bottom: 1px solid #e0e0e0;">
                        <?php echo esc_html($log_entry); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <h2><?php _e('Quick Actions', 'tomatillo-media-studio'); ?></h2>
        <p>
            <a href="<?php echo admin_url('admin.php?page=tomatillo-media-studio-settings'); ?>" class="button button-primary">
                <?php _e('Go to Settings', 'tomatillo-media-studio'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=tomatillo-media-studio-test'); ?>" class="button button-secondary">
                <?php _e('Test Optimization', 'tomatillo-media-studio'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=tomatillo-media-studio-optimization'); ?>" class="button button-secondary">
                <?php _e('Optimization Dashboard', 'tomatillo-media-studio'); ?>
            </a>
        </p>
    </div>
</div>
