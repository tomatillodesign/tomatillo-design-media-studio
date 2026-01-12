<?php
/**
 * Simple test to verify plugin loads without errors
 */

// Simulate WordPress environment
if (!defined('ABSPATH')) {
    define('ABSPATH', '/fake/path/');
}

// Mock WordPress functions
function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'http://example.com/wp-content/plugins/test/'; }
function add_action($hook, $callback) { return true; }
function register_activation_hook($file, $callback) { return true; }
function register_deactivation_hook($file, $callback) { return true; }
function load_plugin_textdomain($domain, $path, $dir) { return true; }
function plugin_basename($file) { return basename($file); }
function flush_rewrite_rules() { return true; }
function dbDelta($sql) { return true; }
function add_option($name, $value) { return true; }
function wp_next_scheduled($hook) { return false; }
function wp_schedule_event($time, $recurrence, $hook) { return true; }
function wp_clear_scheduled_hook($hook) { return true; }

// Test plugin loading
echo "Testing plugin loading...\n";

try {
    // Include the main plugin file
    include_once 'tomatillo-media-studio.php';
    echo "✓ Plugin loaded successfully\n";
    
    // Test if the main function exists
    if (function_exists('tomatillo_media_studio')) {
        echo "✓ Main function exists\n";
        
        // Test getting instance
        $plugin = tomatillo_media_studio();
        if ($plugin) {
            echo "✓ Plugin instance created\n";
            
            // Test if settings are loaded
            if (isset($plugin->settings) && $plugin->settings) {
                echo "✓ Settings loaded\n";
            } else {
                echo "✗ Settings not loaded\n";
            }
            
            // Test if optimization module is loaded
            if (isset($plugin->optimization) && $plugin->optimization) {
                echo "✓ Optimization module loaded\n";
            } else {
                echo "✗ Optimization module not loaded\n";
            }
            
        } else {
            echo "✗ Plugin instance creation failed\n";
        }
    } else {
        echo "✗ Main function not found\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error loading plugin: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "✗ Fatal error loading plugin: " . $e->getMessage() . "\n";
}

echo "Test complete.\n";
