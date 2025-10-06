<?php
/**
 * Comprehensive Tools page with detailed reporting and analytics
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = tomatillo_media_studio();
$settings = $plugin->settings;
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

// Get comprehensive data
$stats = ($plugin->core) ? $plugin->core->get_optimization_stats() : array();
$unoptimized_count = ($plugin->core) ? $plugin->core->get_unoptimized_images_count() : 0;
$media_stats = ($plugin->core) ? $plugin->core->get_media_stats() : array();

// Calculate additional metrics with safe defaults
$total_images = isset($media_stats['total_images']) ? max(0, intval($media_stats['total_images'])) : 0;
$total_size = isset($media_stats['total_size']) ? max(0, intval($media_stats['total_size'])) : 0;
$space_saved = isset($stats['total_space_saved']) ? max(0, intval($stats['total_space_saved'])) : 0;
$optimized_count = isset($stats['total_conversions']) ? max(0, intval($stats['total_conversions'])) : 0;

// Calculate bandwidth savings with user-configurable settings
$estimated_monthly_views = get_option('tomatillo_monthly_pageviews', 1000); // Default to 1K views
$cost_per_gb = get_option('tomatillo_cost_per_gb', 0.08); // Default $0.08/GB (Standard CDN)

// Calculate bandwidth savings per page view (in bytes)
$bandwidth_saved_per_view = $space_saved;
$monthly_bandwidth_saved_bytes = $bandwidth_saved_per_view * $estimated_monthly_views;
$monthly_bandwidth_saved_gb = $monthly_bandwidth_saved_bytes / (1024 * 1024 * 1024); // Convert to GB
$monthly_cost_savings = $monthly_bandwidth_saved_gb * $cost_per_gb;

// Calculate memory usage
$memory_usage = memory_get_usage(true);
$memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
$memory_percentage = ($memory_usage / $memory_limit) * 100;
?>

<div class="wrap">
    <h1>Media Studio Tools</h1>
    
    <!-- Comprehensive tab navigation -->
    <nav class="nav-tab-wrapper">
        <a href="?page=tomatillo-media-studio-tools&tab=overview" class="nav-tab <?php echo $current_tab === 'overview' ? 'nav-tab-active' : ''; ?>">Overview</a>
        <a href="?page=tomatillo-media-studio-tools&tab=optimization" class="nav-tab <?php echo $current_tab === 'optimization' ? 'nav-tab-active' : ''; ?>">Optimization</a>
        <a href="?page=tomatillo-media-studio-tools&tab=analytics" class="nav-tab <?php echo $current_tab === 'analytics' ? 'nav-tab-active' : ''; ?>">Analytics</a>
        <a href="?page=tomatillo-media-studio-tools&tab=system" class="nav-tab <?php echo $current_tab === 'system' ? 'nav-tab-active' : ''; ?>">System Info</a>
        <a href="?page=tomatillo-media-studio-tools&tab=logs" class="nav-tab <?php echo $current_tab === 'logs' ? 'nav-tab-active' : ''; ?>">Logs & Debug</a>
    </nav>
    
    <div style="margin-top: 20px;">
        <?php if ($current_tab === 'overview'): ?>
            <!-- Overview Dashboard -->
            <div class="card">
                <h2>Optimization Overview</h2>
                
                <!-- Key Metrics Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                    <div style="text-align: center; padding: 25px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px;">
                        <h3 style="margin: 0; font-size: 2.5em; font-weight: bold;"><?php echo number_format($optimized_count); ?></h3>
                        <p style="margin: 10px 0 0 0; font-size: 1.1em;">Images Optimized</p>
                        <p style="margin: 5px 0 0 0; opacity: 0.8; font-size: 0.9em;"><?php echo $total_images > 0 ? round(($optimized_count / $total_images) * 100, 1) : 0; ?>% of total</p>
                    </div>
                    
                    <div style="text-align: center; padding: 25px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 12px;">
                        <h3 style="margin: 0; font-size: 2.5em; font-weight: bold;"><?php echo size_format($space_saved); ?></h3>
                        <p style="margin: 10px 0 0 0; font-size: 1.1em;">Space Saved</p>
                        <p style="margin: 5px 0 0 0; opacity: 0.8; font-size: 0.9em;"><?php echo $total_size > 0 ? round(($space_saved / $total_size) * 100, 1) : 0; ?>% reduction</p>
                    </div>
                    
                    <div style="text-align: center; padding: 25px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border-radius: 12px;">
                        <h3 style="margin: 0; font-size: 2.5em; font-weight: bold;"><?php echo size_format($monthly_bandwidth_saved_bytes); ?></h3>
                        <p style="margin: 10px 0 0 0; font-size: 1.1em;">Monthly Bandwidth Saved</p>
                        <p style="margin: 5px 0 0 0; opacity: 0.8; font-size: 0.9em;"><?php echo number_format($estimated_monthly_views); ?> page views</p>
                    </div>
                    
                    <div style="text-align: center; padding: 25px; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; border-radius: 12px;">
                        <h3 style="margin: 0; font-size: 2.5em; font-weight: bold;"><?php echo $unoptimized_count; ?></h3>
                        <p style="margin: 10px 0 0 0; font-size: 1.1em;">Pending Optimization</p>
                        <p style="margin: 5px 0 0 0; opacity: 0.8; font-size: 0.9em;"><?php echo $unoptimized_count > 0 ? 'Ready to process' : 'All optimized'; ?></p>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <h3>Quick Actions</h3>
                <div style="display: flex; gap: 15px; margin: 20px 0;">
                    <?php if ($unoptimized_count > 0): ?>
                        <form method="post" style="margin: 0;">
                            <?php wp_nonce_field('tomatillo_bulk_optimize'); ?>
                            <input type="hidden" name="start_bulk_optimization" value="1">
                            <button type="submit" class="button button-primary button-large">Start Bulk Optimization</button>
                        </form>
                    <?php endif; ?>
                    <a href="?page=tomatillo-media-studio-tools&tab=analytics" class="button button-secondary">View Analytics</a>
                    <a href="?page=tomatillo-media-studio-settings" class="button button-secondary">Settings</a>
                </div>
            </div>
            
        <?php elseif ($current_tab === 'system'): ?>
            <!-- System Information -->
            <div class="card">
                <h2>System Information</h2>
                
                <!-- Memory Usage -->
                <h3>Memory Usage</h3>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span><strong>Current Usage:</strong> <?php echo size_format($memory_usage); ?></span>
                        <span><strong>Limit:</strong> <?php echo size_format($memory_limit); ?></span>
                    </div>
                    <div style="background: #e9ecef; height: 20px; border-radius: 10px; overflow: hidden;">
                        <div style="background: <?php echo $memory_percentage > 80 ? '#dc3545' : ($memory_percentage > 60 ? '#ffc107' : '#28a745'); ?>; height: 100%; width: <?php echo min($memory_percentage, 100); ?>%; transition: width 0.3s ease;"></div>
                    </div>
                    <p style="margin: 10px 0 0 0; text-align: center; font-size: 0.9em; color: #666;">
                        <?php echo round($memory_percentage, 1); ?>% used
                    </p>
                </div>
                
                <!-- System Details -->
                <h3>System Details</h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>WordPress Version</strong></td>
                            <td><?php echo get_bloginfo('version'); ?></td>
                            <td>Latest: <?php echo version_compare(get_bloginfo('version'), '6.0', '>=') ? '✓ Modern' : '⚠ Consider updating'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>PHP Version</strong></td>
                            <td><?php echo PHP_VERSION; ?></td>
                            <td><?php echo version_compare(PHP_VERSION, '8.0', '>=') ? '✓ Modern' : '⚠ Consider updating'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Memory Limit</strong></td>
                            <td><?php echo ini_get('memory_limit'); ?></td>
                            <td><?php echo $memory_limit >= 256 * 1024 * 1024 ? '✓ Sufficient' : '⚠ Consider increasing'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Max Execution Time</strong></td>
                            <td><?php echo ini_get('max_execution_time'); ?>s</td>
                            <td><?php echo ini_get('max_execution_time') >= 30 ? '✓ Sufficient' : '⚠ Consider increasing'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Upload Max Filesize</strong></td>
                            <td><?php echo ini_get('upload_max_filesize'); ?></td>
                            <td><?php echo wp_convert_hr_to_bytes(ini_get('upload_max_filesize')) >= 10 * 1024 * 1024 ? '✓ Sufficient' : '⚠ Consider increasing'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>GD Extension</strong></td>
                            <td><?php echo extension_loaded('gd') ? '✓ Available' : '✗ Not Available'; ?></td>
                            <td><?php echo extension_loaded('gd') ? 'Required for image processing' : 'Install GD extension'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>AVIF Support</strong></td>
                            <td><?php echo function_exists('imageavif') ? '✓ Available' : '✗ Not Available'; ?></td>
                            <td><?php echo function_exists('imageavif') ? 'Best compression format' : 'Update PHP/GD for AVIF support'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>WebP Support</strong></td>
                            <td><?php echo function_exists('imagewebp') ? '✓ Available' : '✗ Not Available'; ?></td>
                            <td><?php echo function_exists('imagewebp') ? 'Good compression format' : 'Update PHP/GD for WebP support'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Imagick Extension</strong></td>
                            <td><?php echo class_exists('Imagick') ? '✓ Available' : '✗ Not Available'; ?></td>
                            <td><?php echo class_exists('Imagick') ? 'Alternative image processor' : 'Optional, GD is sufficient'; ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Plugin Information -->
                <h3>Plugin Information</h3>
                <table class="widefat">
                    <tbody>
                        <tr><td><strong>Plugin Version</strong></td><td>1.0.0</td></tr>
                        <tr><td><strong>Plugin Directory</strong></td><td><?php echo TOMATILLO_MEDIA_STUDIO_DIR; ?></td></tr>
                        <tr><td><strong>Plugin URL</strong></td><td><?php echo TOMATILLO_MEDIA_STUDIO_URL; ?></td></tr>
                        <tr><td><strong>Last Updated</strong></td><td><?php echo date('Y-m-d H:i:s', filemtime(TOMATILLO_MEDIA_STUDIO_FILE)); ?></td></tr>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($current_tab === 'analytics'): ?>
            <!-- Analytics and Reporting -->
            <div class="card">
                <h2>Performance Analytics</h2>
                
                <!-- Configuration Controls -->
                <h3>Bandwidth Calculator</h3>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <form method="post" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                        <?php wp_nonce_field('tomatillo_update_calculator'); ?>
                        <div>
                            <label for="monthly_pageviews"><strong>Monthly Page Views:</strong></label>
                            <input type="number" id="monthly_pageviews" name="monthly_pageviews" value="<?php echo esc_attr($estimated_monthly_views); ?>" min="1" max="1000000" style="width: 100%; padding: 8px; margin-top: 5px;">
                        </div>
                        <div>
                            <button type="submit" name="update_calculator" class="button button-primary" style="width: 100%;">Update Calculator</button>
                        </div>
                    </form>
                    <p style="margin-top: 10px; font-size: 0.9em; color: #666;">Using Standard CDN pricing: $0.08/GB</p>
                </div>
                
                <!-- Savings Breakdown -->
                <h3>Savings Breakdown</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <h4 style="margin: 0; color: #28a745;">Total Space Saved</h4>
                        <p style="margin: 10px 0 0 0; font-size: 1.5em; font-weight: bold;"><?php echo size_format($space_saved); ?></p>
                        <p style="margin: 5px 0 0 0; font-size: 0.8em; color: #666;">Per optimized image</p>
                    </div>
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <h4 style="margin: 0; color: #007bff;">Average Savings</h4>
                        <p style="margin: 10px 0 0 0; font-size: 1.5em; font-weight: bold;"><?php echo isset($stats['average_savings']) ? round($stats['average_savings'], 1) : 0; ?>%</p>
                        <p style="margin: 5px 0 0 0; font-size: 0.8em; color: #666;">Size reduction</p>
                    </div>
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <h4 style="margin: 0; color: #6f42c1;">Monthly Bandwidth Saved</h4>
                        <p style="margin: 10px 0 0 0; font-size: 1.5em; font-weight: bold;"><?php echo size_format($monthly_bandwidth_saved_bytes); ?></p>
                        <p style="margin: 5px 0 0 0; font-size: 0.8em; color: #666;"><?php echo number_format($estimated_monthly_views); ?> page views</p>
                    </div>
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <h4 style="margin: 0; color: #fd7e14;">Monthly Cost Savings</h4>
                        <p style="margin: 10px 0 0 0; font-size: 1.5em; font-weight: bold;">$<?php echo number_format($monthly_cost_savings, 2); ?></p>
                        <p style="margin: 5px 0 0 0; font-size: 0.8em; color: #666;">$0.08/GB</p>
                    </div>
                </div>
                
                <!-- Format Breakdown -->
                <h3>Format Performance</h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Format</th>
                            <th>Images</th>
                            <th>Space Saved</th>
                            <th>Avg. Savings</th>
                            <th>Browser Support</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>AVIF</strong></td>
                            <td><?php echo isset($stats['avif_conversions']) ? number_format($stats['avif_conversions']) : '0'; ?></td>
                            <td><?php echo isset($stats['avif_space_saved']) ? size_format($stats['avif_space_saved']) : '0 B'; ?></td>
                            <td><?php echo isset($stats['avif_conversions']) && $stats['avif_conversions'] > 0 ? round(($stats['avif_space_saved'] / $stats['avif_conversions']) / 1024, 1) . ' KB avg' : 'N/A'; ?></td>
                            <td>Modern browsers (Chrome 85+, Firefox 93+)</td>
                        </tr>
                        <tr>
                            <td><strong>WebP</strong></td>
                            <td><?php echo isset($stats['webp_conversions']) ? number_format($stats['webp_conversions']) : '0'; ?></td>
                            <td><?php echo isset($stats['webp_space_saved']) ? size_format($stats['webp_space_saved']) : '0 B'; ?></td>
                            <td><?php echo isset($stats['webp_conversions']) && $stats['webp_conversions'] > 0 ? round(($stats['webp_space_saved'] / $stats['webp_conversions']) / 1024, 1) . ' KB avg' : 'N/A'; ?></td>
                            <td>Wide support (Chrome 23+, Firefox 65+)</td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Performance Impact -->
                <h3>Performance Impact</h3>
                <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h4 style="margin-top: 0;">Estimated Performance Improvements</h4>
                    <ul style="margin: 10px 0;">
                        <li><strong>Page Load Speed:</strong> <?php echo ($space_saved > 0 && $total_size > 0) ? round(($space_saved / $total_size) * 100, 1) : 0; ?>% faster loading</li>
                        <li><strong>Bandwidth Usage:</strong> <?php echo size_format($monthly_bandwidth_saved_bytes); ?> less data transfer per month</li>
                        <li><strong>Server Storage:</strong> <?php echo size_format($space_saved); ?> less disk space used</li>
                        <li><strong>User Experience:</strong> Faster image loading, better mobile performance</li>
                    </ul>
                </div>
            </div>
            
        <?php elseif ($current_tab === 'optimization'): ?>
            <!-- Bulk Operations Action Panel -->
            <div class="card" style="max-width: none; margin-bottom: 30px;">
                <h2 style="margin-top: 0; color: #007cba; display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-performance"></span>
                    Bulk Image Optimization
                </h2>
                
                <!-- Current Status Overview -->
                <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 25px; border-radius: 12px; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: #1d2327;">Library Status</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div style="text-align: center; padding: 15px; background: rgba(255,255,255,0.8); border-radius: 8px;">
                            <div style="font-size: 2em; font-weight: bold; color: #007cba;"><?php echo number_format($total_images); ?></div>
                            <div style="font-size: 0.9em; color: #666; margin-top: 5px;">Total Images</div>
                        </div>
                        <div style="text-align: center; padding: 15px; background: rgba(255,255,255,0.8); border-radius: 8px;">
                            <div style="font-size: 2em; font-weight: bold; color: #28a745;"><?php echo number_format($optimized_count); ?></div>
                            <div style="font-size: 0.9em; color: #666; margin-top: 5px;">Optimized</div>
                        </div>
                        <div style="text-align: center; padding: 15px; background: rgba(255,255,255,0.8); border-radius: 8px;">
                            <div style="font-size: 2em; font-weight: bold; color: #ffc107;"><?php echo number_format($unoptimized_count); ?></div>
                            <div style="font-size: 0.9em; color: #666; margin-top: 5px;">Pending</div>
                        </div>
                        <div style="text-align: center; padding: 15px; background: rgba(255,255,255,0.8); border-radius: 8px;">
                            <div style="font-size: 2em; font-weight: bold; color: #6f42c1;"><?php echo $total_images > 0 ? round(($optimized_count / $total_images) * 100, 1) : 0; ?>%</div>
                            <div style="font-size: 0.9em; color: #666; margin-top: 5px;">Success Rate</div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <?php if ($unoptimized_count > 0): ?>
                    <div style="background: #fff3cd; border: 2px solid #ffeaa7; padding: 25px; border-radius: 12px; margin: 20px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <div>
                                <h4 style="margin: 0; color: #856404;">Ready to Optimize</h4>
                                <p style="margin: 5px 0 0 0; color: #856404;">
                                    <strong><?php echo number_format($unoptimized_count); ?></strong> images ready for optimization
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.9em; color: #856404;">
                                    <strong>Estimated time:</strong> <?php echo ($plugin->core) ? $plugin->core->estimate_optimization_time($unoptimized_count) : 'Unknown'; ?>
                                </div>
                                <div style="font-size: 0.9em; color: #856404;">
                                    <strong>Estimated savings:</strong> <?php echo ($unoptimized_count > 0 && $optimized_count > 0) ? size_format($unoptimized_count * ($space_saved / $optimized_count)) : '0 B'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <button id="start-bulk-optimization" class="button button-primary button-large" style="padding: 12px 24px; font-size: 16px;">
                                <span class="dashicons dashicons-controls-play" style="margin-right: 8px;"></span>
                                Start Bulk Optimization
                            </button>
                            <button id="preview-bulk-optimization" class="button button-secondary" style="padding: 12px 20px;">
                                <span class="dashicons dashicons-visibility" style="margin-right: 8px;"></span>
                                Preview First 10 Images
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="background: #d1edff; border: 2px solid #74c0fc; padding: 25px; border-radius: 12px; margin: 20px 0;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <span class="dashicons dashicons-yes-alt" style="font-size: 2em; color: #28a745;"></span>
                            <div>
                                <h4 style="margin: 0; color: #004085;">All Images Optimized!</h4>
                                <p style="margin: 5px 0 0 0; color: #004085;">Your media library is fully optimized and ready to go.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Bulk Operations Progress Panel -->
            <div id="bulk-progress-panel" style="display: none; background: #fff; border: 2px solid #007cba; border-radius: 12px; padding: 30px; margin: 20px 0; box-shadow: 0 4px 12px rgba(0, 124, 186, 0.15); max-width: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="margin: 0; color: #007cba; display: flex; align-items: center; gap: 10px; font-size: 1.5em;">
                        <span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span>
                        Bulk Optimization in Progress
                    </h3>
                    <button id="cancel-bulk-optimization" class="button button-secondary" style="display: none; padding: 8px 16px;">Cancel</button>
                </div>
                
                <!-- Progress Bar -->
                <div style="margin-bottom: 25px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span id="progress-text" style="font-weight: 600; color: #1d2327;">Preparing optimization...</span>
                        <span id="progress-percentage" style="font-weight: bold; color: #007cba; font-size: 1.1em;">0%</span>
                    </div>
                    <div style="background: #e1e1e1; height: 16px; border-radius: 8px; overflow: hidden;">
                        <div id="progress-bar" style="background: linear-gradient(90deg, #007cba 0%, #00a0d2 100%); height: 100%; width: 0%; transition: width 0.3s ease; border-radius: 8px;"></div>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 20px; margin-bottom: 25px;">
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px; border-left: 4px solid #28a745;">
                        <div style="font-size: 2em; font-weight: bold; color: #28a745;" id="processed-count">0</div>
                        <div style="font-size: 0.9em; color: #666; margin-top: 5px;">Processed</div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px; border-left: 4px solid #007cba;">
                        <div style="font-size: 2em; font-weight: bold; color: #007cba;" id="success-count">0</div>
                        <div style="font-size: 0.9em; color: #666; margin-top: 5px;">Successful</div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px; border-left: 4px solid #ffc107;">
                        <div style="font-size: 2em; font-weight: bold; color: #ffc107;" id="skipped-count">0</div>
                        <div style="font-size: 0.9em; color: #666; margin-top: 5px;">Skipped</div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px; border-left: 4px solid #dc3545;">
                        <div style="font-size: 2em; font-weight: bold; color: #dc3545;" id="error-count">0</div>
                        <div style="font-size: 0.9em; color: #666; margin-top: 5px;">Failed</div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px; border-left: 4px solid #6f42c1;">
                        <div style="font-size: 2em; font-weight: bold; color: #6f42c1;" id="space-saved-count">0 B</div>
                        <div style="font-size: 0.9em; color: #666; margin-top: 5px;">Space Saved</div>
                    </div>
                </div>
                
                <!-- Current Image Status -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #007cba;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong id="current-image-name" style="font-size: 1.1em; color: #1d2327;">Preparing...</strong>
                            <div style="font-size: 0.9em; color: #666; margin-top: 5px;" id="current-image-status">Initializing bulk optimization</div>
                        </div>
                        <div style="text-align: right;">
                            <div id="current-image-size" style="font-size: 0.9em; color: #666;">-</div>
                            <div id="current-image-savings" style="font-size: 0.9em; color: #28a745; font-weight: 600;">-</div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity Log -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    <h4 style="margin: 0 0 15px 0; font-size: 1.1em; color: #1d2327;">Recent Activity</h4>
                    <div id="activity-log" style="max-height: 250px; overflow-y: auto; font-family: monospace; font-size: 13px; line-height: 1.5;">
                        <div style="color: #666;">Waiting for optimization to start...</div>
                    </div>
                </div>
            </div>
            
            <!-- Optimization Management -->
            <div class="card" style="max-width: none;">
                <h2>Optimization Management</h2>
                
                <!-- Settings Overview -->
                <h3>Current Settings</h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Setting</th>
                            <th>Value</th>
                            <th>Impact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Auto-Convert New Uploads</strong></td>
                            <td><?php echo $settings->is_optimization_enabled() ? '✓ Enabled' : '✗ Disabled'; ?></td>
                            <td><?php echo $settings->is_optimization_enabled() ? 'New uploads automatically optimized' : 'Manual optimization required'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>AVIF Conversion</strong></td>
                            <td><?php echo $settings->is_avif_enabled() ? '✓ Enabled (' . $settings->get_avif_quality() . '% quality)' : '✗ Disabled'; ?></td>
                            <td><?php echo $settings->is_avif_enabled() ? 'Best compression, modern browsers' : 'Missing 50%+ savings potential'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>WebP Conversion</strong></td>
                            <td><?php echo $settings->is_webp_enabled() ? '✓ Enabled (' . $settings->get_webp_quality() . '% quality)' : '✗ Disabled'; ?></td>
                            <td><?php echo $settings->is_webp_enabled() ? 'Good compression, wide support' : 'Missing 25%+ savings potential'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Minimum Savings Threshold</strong></td>
                            <td><?php echo $settings->get_min_savings_threshold(); ?>%</td>
                            <td>Only converts if savings exceed this threshold</td>
                        </tr>
                        <tr>
                            <td><strong>Skip Small Images</strong></td>
                            <td><?php echo $settings->should_skip_small_images() ? 'Yes (' . size_format($settings->get_min_image_size()) . ')' : 'No'; ?></td>
                            <td>Prevents processing tiny images</td>
                        </tr>
                        <tr>
                            <td><strong>Maximum Image Dimensions</strong></td>
                            <td><?php echo $settings->get_max_image_dimensions(); ?>px</td>
                            <td>Limits processing to reasonable sizes</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($current_tab === 'logs'): ?>
            <div class="card">
                <h2>Debug Controls</h2>
                
                <form method="post" style="margin-bottom: 20px;">
                    <?php wp_nonce_field('tomatillo_toggle_debug'); ?>
                    <p>
                        <label>
                            <input type="checkbox" name="debug_mode" value="1" <?php checked($settings->is_debug_mode()); ?>>
                            Enable debug mode
                        </label>
                    </p>
                    <p class="submit">
                        <input type="submit" name="toggle_debug" class="button button-secondary" value="Update Debug Mode">
                    </p>
                </form>
                
                <form method="post" style="margin-bottom: 20px;">
                    <?php wp_nonce_field('tomatillo_clear_logs'); ?>
                    <p class="submit">
                        <input type="submit" name="clear_logs" class="button button-secondary" value="Clear Plugin Logs">
                    </p>
                </form>
                
                <h3>Plugin Logs</h3>
                <?php 
                $logs = array();
                if ($plugin && isset($plugin->core) && method_exists($plugin->core, 'get_plugin_logs')) {
                    try {
                        $all_logs = $plugin->core->get_plugin_logs();
                        
                        // Filter logs to show only recent and important ones
                        $logs = array_filter($all_logs, function($log) {
                            if (!is_array($log) || !isset($log['level']) || !isset($log['message'])) {
                                return false;
                            }
                            
                            // Always show errors and warnings
                            if (in_array($log['level'], array('error', 'warning'))) {
                                return true;
                            }
                            
                            // For info messages, only show important ones
                            if ($log['level'] === 'info') {
                                $important_keywords = array('optimization', 'conversion', 'error', 'failed', 'success', 'bulk', 'test');
                                foreach ($important_keywords as $keyword) {
                                    if (stripos($log['message'], $keyword) !== false) {
                                        return true;
                                    }
                                }
                            }
                            
                            return false;
                        });
                        
                        // Limit to last 20 important logs
                        $logs = array_slice($logs, -20);
                        
                    } catch (Exception $e) {
                        echo '<div class="notice notice-error"><p>Error loading logs: ' . esc_html($e->getMessage()) . '</p></div>';
                    } catch (Error $e) {
                        echo '<div class="notice notice-error"><p>Fatal error loading logs: ' . esc_html($e->getMessage()) . '</p></div>';
                    }
                } else {
                    echo '<div class="notice notice-warning"><p>Core module not available for logging.</p></div>';
                }
                
                if (empty($logs)): ?>
                    <p>No important logs found. Only errors, warnings, and optimization-related events are shown.</p>
                <?php else: ?>
                    <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                        <?php foreach ($logs as $log): ?>
                            <?php if (is_array($log) && isset($log['timestamp']) && isset($log['level']) && isset($log['message'])): ?>
                                <div style="margin-bottom: 5px; padding: 5px; background: #fff; border-left: 3px solid #<?php echo $log['level'] === 'error' ? 'd63638' : ($log['level'] === 'warning' ? 'dba617' : '00a32a'); ?>;">
                                    <strong>[<?php echo esc_html($log['timestamp']); ?>]</strong> 
                                    <span style="color: #<?php echo $log['level'] === 'error' ? 'd63638' : ($log['level'] === 'warning' ? 'dba617' : '00a32a'); ?>;"><?php echo strtoupper($log['level']); ?></span>
                                    <?php echo esc_html($log['message']); ?>
                                </div>
                            <?php else: ?>
                                <div style="margin-bottom: 5px; padding: 5px; background: #fff; border-left: 3px solid #666;">
                                    <strong>[<?php echo date('Y-m-d H:i:s'); ?>]</strong> 
                                    <span style="color: #666;">INFO</span>
                                    <?php echo esc_html(is_string($log) ? $log : print_r($log, true)); ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    max-width: 100%;
}

.card h2 {
    margin-top: 0;
    color: #1d2327;
}

.card h3 {
    color: #1d2327;
    border-bottom: 1px solid #e1e1e1;
    padding-bottom: 10px;
}

.widefat th {
    background: #f6f7f7;
    font-weight: 600;
}

.widefat td {
    vertical-align: top;
}

.widefat tr:nth-child(even) {
    background: #f9f9f9;
}

/* Bulk Operations Styles */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.bulk-processing {
    animation: pulse 2s infinite;
}

.activity-log-entry {
    margin-bottom: 5px;
    padding: 3px 0;
    border-bottom: 1px solid #e1e1e1;
}

.activity-log-entry:last-child {
    border-bottom: none;
}

.activity-log-success {
    color: #28a745;
}

.activity-log-error {
    color: #dc3545;
}

.activity-log-warning {
    color: #ffc107;
}

.activity-log-info {
    color: #007cba;
}

/* Progress Panel Animations */
#bulk-progress-panel {
    transition: all 0.3s ease;
}

#bulk-progress-panel.show {
    display: block !important;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Status indicators */
.status-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-indicator.success {
    background: #28a745;
}

.status-indicator.error {
    background: #dc3545;
}

.status-indicator.warning {
    background: #ffc107;
}

.status-indicator.info {
    background: #007cba;
}
</style>

<script>
jQuery(document).ready(function($) {
    let bulkOptimizationInProgress = false;
    let currentBatch = 0;
    let totalImages = 0;
    let processedImages = 0;
    let successCount = 0;
    let skippedCount = 0;
    let errorCount = 0;
    let totalSpaceSaved = 0;
    
    // Initialize bulk optimization functionality
    initBulkOptimization();
    
    function initBulkOptimization() {
        // Start bulk optimization button
        $('#start-bulk-optimization').on('click', function() {
            startBulkOptimization();
        });
        
        // Preview bulk optimization button
        $('#preview-bulk-optimization').on('click', function() {
            previewBulkOptimization();
        });
        
        // Cancel bulk optimization button
        $('#cancel-bulk-optimization').on('click', function() {
            cancelBulkOptimization();
        });
    }
    
    function startBulkOptimization() {
        if (bulkOptimizationInProgress) {
            return;
        }
        
        bulkOptimizationInProgress = true;
        
        // Show progress panel
        $('#bulk-progress-panel').addClass('show').show();
        
        // Reset counters
        currentBatch = 0;
        processedImages = 0;
        successCount = 0;
        skippedCount = 0;
        errorCount = 0;
        totalSpaceSaved = 0;
        
        // Update UI
        updateProgressUI();
        addActivityLog('Starting bulk optimization...', 'info');
        
        // Get total images count
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tomatillo_get_unoptimized_count',
                nonce: '<?php echo wp_create_nonce('tomatillo_bulk_optimize'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    totalImages = response.data.count;
                    addActivityLog(`Found ${totalImages} images to optimize`, 'info');
                    processNextBatch();
                } else {
                    addActivityLog('Failed to get image count: ' + response.data, 'error');
                    stopBulkOptimization();
                }
            },
            error: function() {
                addActivityLog('Error getting image count', 'error');
                stopBulkOptimization();
            }
        });
    }
    
    function processNextBatch() {
        if (!bulkOptimizationInProgress) {
            return;
        }
        
        currentBatch++;
        addActivityLog(`Processing batch ${currentBatch}...`, 'info');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tomatillo_process_bulk_batch',
                batch: currentBatch,
                nonce: '<?php echo wp_create_nonce('tomatillo_bulk_optimize'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    handleBatchResponse(response.data);
                } else {
                    addActivityLog('Batch processing failed: ' + response.data, 'error');
                    errorCount++;
                }
                
                // Continue with next batch or finish
                if (processedImages < totalImages && bulkOptimizationInProgress) {
                    setTimeout(processNextBatch, 1000); // 1 second delay between batches
                } else {
                    finishBulkOptimization();
                }
            },
            error: function() {
                addActivityLog('Network error during batch processing', 'error');
                errorCount++;
                
                if (processedImages < totalImages && bulkOptimizationInProgress) {
                    setTimeout(processNextBatch, 2000); // Longer delay on error
                } else {
                    finishBulkOptimization();
                }
            }
        });
    }
    
    function handleBatchResponse(batchData) {
        if (batchData.images && batchData.images.length > 0) {
            batchData.images.forEach(function(image) {
                processedImages++;
                
                if (image.success) {
                    // Check if it's a skip or actual success using the skipped flag
                    if (image.skipped) {
                        skippedCount++;
                        let reason = image.error || 'Skipped';
                        addActivityLog(`⚠ ${image.filename}: ${reason}`, 'warning');
                        
                        // Update current image display
                        $('#current-image-name').text(image.filename);
                        $('#current-image-status').text('Skipped: ' + reason);
                        $('#current-image-size').text(formatFileSize(image.original_size));
                        $('#current-image-savings').text('Skipped');
                    } else {
                        successCount++;
                        totalSpaceSaved += image.space_saved || 0;
                        
                        let savingsText = image.space_saved ? formatFileSize(image.space_saved) : '';
                        let savingsPercent = image.savings_percent ? ` (${image.savings_percent}%)` : '';
                        
                        addActivityLog(`✓ ${image.filename}: ${savingsText}${savingsPercent}`, 'success');
                        
                        // Update current image display
                        $('#current-image-name').text(image.filename);
                        $('#current-image-status').text('Optimized successfully');
                        $('#current-image-size').text(formatFileSize(image.original_size));
                        $('#current-image-savings').text(savingsText + savingsPercent);
                    }
                } else {
                    errorCount++;
                    let reason = image.error || 'Unknown error';
                    addActivityLog(`✗ ${image.filename}: ${reason}`, 'error');
                    
                    // Update current image display
                    $('#current-image-name').text(image.filename);
                    $('#current-image-status').text('Failed: ' + reason);
                    $('#current-image-size').text(formatFileSize(image.original_size));
                    $('#current-image-savings').text('Failed');
                }
                
                updateProgressUI();
            });
        }
    }
    
    function finishBulkOptimization() {
        bulkOptimizationInProgress = false;
        
        // Update final status
        $('#progress-text').text('Optimization completed');
        $('#current-image-name').text('All images processed');
        $('#current-image-status').text('Bulk optimization finished');
        
        // Show completion message
        let message = `Bulk optimization completed! Processed ${processedImages} images: ${successCount} successful, ${skippedCount} skipped, ${errorCount} failed.`;
        if (totalSpaceSaved > 0) {
            message += ` Total space saved: ${formatFileSize(totalSpaceSaved)}.`;
        }
        
        addActivityLog(message, successCount > errorCount ? 'success' : 'warning');
        
        // Hide cancel button, show completion
        $('#cancel-bulk-optimization').hide();
        
        // Add completion notice
        setTimeout(function() {
            showCompletionNotice();
        }, 2000);
    }
    
    function stopBulkOptimization() {
        bulkOptimizationInProgress = false;
        $('#progress-text').text('Optimization stopped');
        addActivityLog('Bulk optimization stopped by user', 'warning');
        $('#cancel-bulk-optimization').hide();
    }
    
    function cancelBulkOptimization() {
        if (confirm('Are you sure you want to cancel the bulk optimization?')) {
            stopBulkOptimization();
        }
    }
    
    function previewBulkOptimization() {
        addActivityLog('Loading preview of first 10 images...', 'info');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tomatillo_preview_bulk_optimization',
                nonce: '<?php echo wp_create_nonce('tomatillo_bulk_optimize'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showPreviewModal(response.data);
                } else {
                    addActivityLog('Failed to load preview: ' + response.data, 'error');
                }
            },
            error: function() {
                addActivityLog('Error loading preview', 'error');
            }
        });
    }
    
    function showPreviewModal(previewData) {
        let modalHtml = `
            <div id="preview-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; padding: 30px; border-radius: 8px; max-width: 600px; max-height: 80%; overflow-y: auto;">
                    <h3>Preview: First 10 Images</h3>
                    <div style="margin: 20px 0;">
        `;
        
        previewData.images.forEach(function(image) {
            modalHtml += `
                <div style="padding: 10px; border-bottom: 1px solid #eee;">
                    <strong>${image.filename}</strong><br>
                    <small>Size: ${formatFileSize(image.size)} | Type: ${image.type}</small>
                </div>
            `;
        });
        
        modalHtml += `
                    </div>
                    <div style="text-align: right;">
                        <button class="button button-secondary" onclick="jQuery('#preview-modal').remove();">Close</button>
                        <button class="button button-primary" onclick="jQuery('#preview-modal').remove(); startBulkOptimization();" style="margin-left: 10px;">Start Optimization</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
    }
    
    function updateProgressUI() {
        // Fix progress bar calculation - cap at 100%
        let percentage = totalImages > 0 ? Math.min(100, Math.round((processedImages / totalImages) * 100)) : 0;
        
        $('#progress-percentage').text(percentage + '%');
        $('#progress-bar').css('width', percentage + '%');
        $('#processed-count').text(processedImages);
        $('#success-count').text(successCount);
        $('#skipped-count').text(skippedCount);
        $('#error-count').text(errorCount);
        $('#space-saved-count').text(formatFileSize(totalSpaceSaved));
        
        if (processedImages < totalImages) {
            $('#progress-text').text(`Processing image ${processedImages + 1} of ${totalImages}...`);
        }
    }
    
    function addActivityLog(message, type) {
        let timestamp = new Date().toLocaleTimeString();
        let logEntry = `
            <div class="activity-log-entry activity-log-${type}">
                <span class="status-indicator ${type}"></span>
                [${timestamp}] ${message}
            </div>
        `;
        
        $('#activity-log').append(logEntry);
        
        // Auto-scroll to bottom
        let logContainer = $('#activity-log')[0];
        logContainer.scrollTop = logContainer.scrollHeight;
        
        // Keep only last 50 entries
        let entries = $('#activity-log .activity-log-entry');
        if (entries.length > 50) {
            entries.slice(0, entries.length - 50).remove();
        }
    }
    
    function showCompletionNotice() {
        let noticeClass = successCount > errorCount ? 'notice-success' : 'notice-warning';
        let noticeHtml = `
            <div class="notice ${noticeClass} is-dismissible" style="margin: 20px 0;">
                <p><strong>Bulk Optimization Complete!</strong> 
                Processed ${processedImages} images: ${successCount} successful, ${skippedCount} skipped, ${errorCount} failed.
                ${totalSpaceSaved > 0 ? ` Total space saved: ${formatFileSize(totalSpaceSaved)}.` : ''}</p>
            </div>
        `;
        
        $('.wrap h1').after(noticeHtml);
        
        // Auto-dismiss after 10 seconds
        setTimeout(function() {
            $('.notice').fadeOut();
        }, 10000);
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
</script>