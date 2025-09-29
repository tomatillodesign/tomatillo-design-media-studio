<?php
/**
 * Tools page template with tabs for all admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = tomatillo_media_studio();
$settings = $plugin->settings;
$stats = ($plugin->core) ? $plugin->core->get_optimization_stats() : array();
$unoptimized_count = ($plugin->core) ? $plugin->core->get_unoptimized_images_count() : 0;

// Debug information
if (current_user_can('manage_options')) {
    echo '<!-- Debug Info: Plugin loaded: ' . ($plugin ? 'Yes' : 'No') . ' -->';
    echo '<!-- Debug Info: Settings loaded: ' . ($settings ? 'Yes' : 'No') . ' -->';
    echo '<!-- Debug Info: Core loaded: ' . ($plugin && $plugin->core ? 'Yes' : 'No') . ' -->';
    echo '<!-- Debug Info: Stats: ' . print_r($stats, true) . ' -->';
    echo '<!-- Debug Info: Unoptimized count: ' . $unoptimized_count . ' -->';
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'optimization';

// Available tabs
$tabs = array(
    'optimization' => __('Optimization', 'tomatillo-media-studio'),
    'testing' => __('Testing', 'tomatillo-media-studio'),
    'logs' => __('Logs & Debug', 'tomatillo-media-studio'),
    'system' => __('System Info', 'tomatillo-media-studio')
);
?>

<div class="wrap">
    <h1><?php _e('Media Studio Tools', 'tomatillo-media-studio'); ?></h1>
    
    <!-- Test content to verify template is loading -->
    <div class="notice notice-info">
        <p><strong>Template is loading!</strong> Plugin: <?php echo $plugin ? 'Loaded' : 'Not loaded'; ?>, Settings: <?php echo $settings ? 'Loaded' : 'Not loaded'; ?>, Core: <?php echo ($plugin && $plugin->core) ? 'Loaded' : 'Not loaded'; ?></p>
    </div>
    
    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_key => $tab_label): ?>
            <a href="<?php echo admin_url('admin.php?page=tomatillo-media-studio-tools&tab=' . $tab_key); ?>" 
               class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_label); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <!-- Tab Content -->
    <div class="tab-content">
        <?php if ($current_tab === 'system'): ?>
            <!-- System Info Tab -->
            <div class="card">
                <h2>System Information</h2>
                
                <!-- Debug info -->
                <div class="notice notice-success">
                    <p><strong>System tab is working!</strong></p>
                    <p>System info method exists: <?php echo method_exists($plugin->core, 'get_system_info') ? 'Yes' : 'No'; ?></p>
                    <p>System info result: <?php 
                        $system_info = ($plugin->core) ? $plugin->core->get_system_info() : array();
                        echo is_array($system_info) ? count($system_info) . ' items' : 'Not array';
                        if (!empty($system_info)) {
                            echo '<br>Sample: ' . print_r(array_slice($system_info, 0, 3, true), true);
                        }
                    ?></p>
                </div>
                
                <!-- BASIC TEST - Just plain HTML -->
                <h3>BASIC TEST</h3>
                <p>This is plain HTML text.</p>
                <p>Another paragraph.</p>
                
                <!-- Test if PHP works -->
                <p>WordPress Version: <?php echo get_bloginfo('version'); ?></p>
                
                <!-- Test if table works -->
                <table border="1">
                    <tr><td>Test</td><td>Value</td></tr>
                </table>
                
                <p>END OF TEST</p>
            </div>
        <?php else: ?>
            <!-- Other tabs placeholder -->
            <div class="card">
                <h2><?php echo esc_html($tabs[$current_tab]); ?></h2>
                <p>This tab is under construction.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.tab-content {
    margin-top: 20px;
}

.card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.card h2 {
    margin-top: 0;
}
</style>