<?php
/**
 * Test page for React Media Upload integration
 * This page tests the global MediaUpload override in Gutenberg
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = tomatillo_media_studio();
?>

<div class="wrap">
    <h1><?php _e('React Media Upload Test', 'tomatillo-media-studio'); ?></h1>
    
    <div class="tomatillo-test-container">
        <h2><?php _e('Test Global MediaUpload Override', 'tomatillo-media-studio'); ?></h2>
        
        <div class="tomatillo-test-section">
            <h3><?php _e('Instructions', 'tomatillo-media-studio'); ?></h3>
            <ol>
                <li>Go to <strong>Posts → Add New</strong> or <strong>Pages → Add New</strong></li>
                <li>Add any block that uses media (Image, Gallery, Cover, etc.)</li>
                <li>Click the "Select Image" or "Upload" button</li>
                <li>You should see our custom Tomatillo media inserter instead of the default WordPress one</li>
            </ol>
        </div>
        
        <div class="tomatillo-test-section">
            <h3><?php _e('Test Blocks', 'tomatillo-media-studio'); ?></h3>
            <p>Try these blocks to test the integration:</p>
            <ul>
                <li><strong>Image Block</strong> - Single image selection</li>
                <li><strong>Gallery Block</strong> - Multiple image selection</li>
                <li><strong>Cover Block</strong> - Background image selection</li>
                <li><strong>Media & Text Block</strong> - Image selection</li>
                <li><strong>File Block</strong> - Document selection</li>
            </ul>
        </div>
        
        <div class="tomatillo-test-section">
            <h3><?php _e('ACF Integration Test', 'tomatillo-media-studio'); ?></h3>
            <p>If you have Advanced Custom Fields (ACF) installed:</p>
            <ul>
                <li>Create a field group with Image, Gallery, or File fields</li>
                <li>Add the field group to a post or page</li>
                <li>Edit the post/page and try selecting media in ACF fields</li>
                <li>Our custom inserter should appear</li>
            </ul>
        </div>
        
        <div class="tomatillo-test-section">
            <h3><?php _e('Kadence Blocks Test', 'tomatillo-media-studio'); ?></h3>
            <p>If you have Kadence Blocks installed:</p>
            <ul>
                <li>Add Kadence blocks that use media (Image, Gallery, etc.)</li>
                <li>Try selecting media in Kadence blocks</li>
                <li>Our custom inserter should appear</li>
            </ul>
        </div>
        
        <div class="tomatillo-test-section">
            <h3><?php _e('Debug Information', 'tomatillo-media-studio'); ?></h3>
            <div id="debug-info">
                <p><strong>Plugin Version:</strong> <?php echo TOMATILLO_MEDIA_STUDIO_VERSION; ?></p>
                <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
                <p><strong>Gutenberg Active:</strong> <?php echo function_exists('register_block_type') ? 'Yes' : 'No'; ?></p>
                <p><strong>ACF Active:</strong> <?php echo function_exists('acf_get_field_groups') ? 'Yes' : 'No'; ?></p>
                <p><strong>Kadence Blocks Active:</strong> <?php echo function_exists('kadence_blocks_init') ? 'Yes' : 'No'; ?></p>
            </div>
            
            <h4><?php _e('React Component Status', 'tomatillo-media-studio'); ?></h4>
            <div id="react-status">
                <p><strong>React Component Loaded:</strong> <span id="react-loaded">Checking...</span></p>
                <p><strong>MediaUpload Override Applied:</strong> <span id="override-applied">Checking...</span></p>
                <p><strong>TomatilloMediaFrame Available:</strong> <span id="tomatillo-frame">Checking...</span></p>
            </div>
        </div>
        
        <div class="tomatillo-test-section">
            <h3><?php _e('Quick Links', 'tomatillo-media-studio'); ?></h3>
            <p>
                <a href="<?php echo admin_url('post-new.php'); ?>" class="button button-primary"><?php _e('Add New Post', 'tomatillo-media-studio'); ?></a>
                <a href="<?php echo admin_url('post-new.php?post_type=page'); ?>" class="button button-primary"><?php _e('Add New Page', 'tomatillo-media-studio'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=tomatillo-media-studio-test'); ?>" class="button"><?php _e('Test Custom Frame', 'tomatillo-media-studio'); ?></a>
            </p>
        </div>
    </div>
</div>

<style>
.tomatillo-test-container {
    max-width: 800px;
    margin: 20px 0;
}

.tomatillo-test-section {
    margin: 30px 0;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
}

.tomatillo-test-section h3 {
    margin-top: 0;
    color: #333;
}

.tomatillo-test-section ul {
    margin: 10px 0;
    padding-left: 20px;
}

.tomatillo-test-section li {
    margin: 5px 0;
}

#debug-info {
    background: white;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: monospace;
}

#debug-info p {
    margin: 5px 0;
}

#react-status {
    background: white;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: monospace;
    margin-top: 10px;
}

#react-status p {
    margin: 5px 0;
}

#react-status span {
    font-weight: bold;
}

#react-status .success {
    color: green;
}

#react-status .error {
    color: red;
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    // Check React component status
    function checkReactStatus() {
        console.log('Checking React component status...');
        
        // Check if TomatilloMediaFrame is available
        if (typeof TomatilloMediaFrame !== 'undefined') {
            $('#tomatillo-frame').html('<span class="success">✓ Yes</span>');
        } else {
            $('#tomatillo-frame').html('<span class="error">✗ No</span>');
        }
        
        // Check if our React component is available
        if (typeof TomatilloMediaUpload !== 'undefined') {
            $('#react-loaded').html('<span class="success">✓ Yes</span>');
        } else if (typeof TomatilloReactTest !== 'undefined') {
            $('#react-loaded').html('<span class="success">✓ Script loaded, component not ready</span>');
        } else {
            $('#react-loaded').html('<span class="error">✗ No</span>');
        }
        
        // Check if wp.hooks is available and has our filter
        if (typeof wp !== 'undefined' && wp.hooks) {
            // Check if our filter is registered
            var filters = wp.hooks.filters || {};
            if (filters['editor.MediaUpload']) {
                $('#override-applied').html('<span class="success">✓ Yes</span>');
            } else {
                $('#override-applied').html('<span class="error">✗ No</span>');
            }
        } else {
            $('#override-applied').html('<span class="error">✗ wp.hooks not available</span>');
        }
    }
    
    // Check immediately
    checkReactStatus();
    
    // Check again after a delay to catch late-loading scripts
    setTimeout(checkReactStatus, 2000);
    
    // Also check when window loads
    $(window).on('load', checkReactStatus);
});
</script>

<?php
// Direct script inclusion for testing - this works!
wp_enqueue_media();
wp_enqueue_script(
    'tomatillo-custom-media-frame',
    TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'js/custom-media-frame-clean.js',
    array('jquery', 'wp-media'),
    TOMATILLO_MEDIA_STUDIO_VERSION,
    true
);

wp_enqueue_script(
    'tomatillo-react-media-upload',
    TOMATILLO_MEDIA_STUDIO_ASSETS_URL . 'js/tomatillo-react-media-upload.js',
    array(), // No dependencies for testing
    TOMATILLO_MEDIA_STUDIO_VERSION,
    true
);

// Include the template for our custom media frame
include TOMATILLO_MEDIA_STUDIO_DIR . 'templates/custom-media-frame-template.php';

// Localize script with AJAX URL for uploads
wp_localize_script('tomatillo-react-media-upload', 'ajaxurl', admin_url('admin-ajax.php'));
?>
