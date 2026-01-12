<?php
/**
 * Test template to isolate the issue
 */

if (!defined('ABSPATH')) {
    exit;
}

echo '<div class="wrap">';
echo '<h1>TEST PAGE</h1>';
echo '<p>This is a test paragraph.</p>';
echo '<p>WordPress Version: ' . get_bloginfo('version') . '</p>';
echo '<table border="1"><tr><td>Test</td><td>Value</td></tr></table>';
echo '<p>END OF TEST</p>';
echo '</div>';
