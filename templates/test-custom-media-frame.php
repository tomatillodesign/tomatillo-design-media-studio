<?php
/**
 * Test page for custom media frame
 * This page allows testing the custom media inserter
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = tomatillo_media_studio();
?>

<div class="wrap">
    <h1><?php _e('Custom Media Frame Test', 'tomatillo-media-studio'); ?></h1>
    
    <div class="tomatillo-test-container">
        <h2><?php _e('Test Custom Media Inserter', 'tomatillo-media-studio'); ?></h2>
        
        <div class="tomatillo-test-section">
            <h3><?php _e('Single Image Selection', 'tomatillo-media-studio'); ?></h3>
            <button type="button" class="button button-primary" id="test-single-image">
                <?php _e('Select Single Image', 'tomatillo-media-studio'); ?>
            </button>
            <div id="single-image-result" class="tomatillo-result"></div>
        </div>
        
        <div class="tomatillo-test-section">
            <h3><?php _e('Multiple Images Selection', 'tomatillo-media-studio'); ?></h3>
            <button type="button" class="button button-primary" id="test-multiple-images">
                <?php _e('Select Multiple Images', 'tomatillo-media-studio'); ?>
            </button>
            <div id="multiple-images-result" class="tomatillo-result"></div>
        </div>
        
        <div class="tomatillo-test-section">
            <h3><?php _e('All Media Types', 'tomatillo-media-studio'); ?></h3>
            <button type="button" class="button button-primary" id="test-all-media">
                <?php _e('Select Any Media', 'tomatillo-media-studio'); ?>
            </button>
            <div id="all-media-result" class="tomatillo-result"></div>
        </div>
        
        <div class="tomatillo-test-section">
            <h3><?php _e('Images Only', 'tomatillo-media-studio'); ?></h3>
            <button type="button" class="button button-primary" id="test-images-only">
                <?php _e('Select Images Only', 'tomatillo-media-studio'); ?>
            </button>
            <div id="images-only-result" class="tomatillo-result"></div>
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

.tomatillo-result {
    margin-top: 15px;
    padding: 15px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-height: 50px;
}

.tomatillo-result img {
    max-width: 150px;
    height: auto;
    margin: 5px;
    border-radius: 4px;
}

.tomatillo-result .media-item {
    display: inline-block;
    margin: 5px;
    padding: 10px;
    background: #f0f0f0;
    border-radius: 4px;
    text-align: center;
}

.tomatillo-result .media-item img {
    max-width: 100px;
    height: auto;
}

.tomatillo-result .media-item .filename {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    // Debug: Check what's available
    console.log('jQuery available:', typeof $ !== 'undefined');
    console.log('wp available:', typeof wp !== 'undefined');
    console.log('wp.media available:', typeof wp !== 'undefined' && wp.media);
    console.log('TomatilloMediaFrame available:', typeof TomatilloMediaFrame !== 'undefined');
    
    // Check if our script is loaded
    console.log('Scripts loaded:', wp.scripts ? Object.keys(wp.scripts.registered) : 'wp.scripts not available');
    
    // Check if our specific script is loaded
    if (wp.scripts && wp.scripts.registered['tomatillo-custom-media-frame']) {
        console.log('Our script is registered:', wp.scripts.registered['tomatillo-custom-media-frame']);
    } else {
        console.log('Our script is NOT registered');
    }
    
    // Create fallback media frame if our script didn't load
    if (typeof TomatilloMediaFrame === 'undefined') {
        console.log('Creating fallback TomatilloMediaFrame');
        window.TomatilloMediaFrame = {
            open: function(options) {
                console.log('Fallback media frame opened');
                var frame = wp.media({
                    title: options.title || 'Select Media',
                    button: options.button || { text: 'Select' },
                    multiple: options.multiple || false,
                    library: options.library || {}
                });
                
                frame.on('select', function() {
                    var selection = frame.state().get('selection').toJSON();
                    if (options.onSelect) {
                        options.onSelect(selection);
                    }
                });
                
                frame.open();
                return frame;
            },
            init: function() {
                console.log('Fallback media frame initialized');
            }
        };
    }
    
    // Test single image selection
    $('#test-single-image').on('click', function() {
        console.log('Single image button clicked');
        
        // Wait a moment for our script to load if it hasn't yet
        setTimeout(function() {
            if (typeof TomatilloMediaFrame === 'undefined') {
                console.log('TomatilloMediaFrame not ready, using fallback');
                // Use fallback
                if (window.TomatilloMediaFrame && window.TomatilloMediaFrame.open) {
                    window.TomatilloMediaFrame.open({
                        title: 'Select Single Image (Fallback)',
                        multiple: false,
                        allowedTypes: ['image'],
                        onSelect: function(selection) {
                            var result = $('#single-image-result');
                            result.html('<h4>Selected Image (Fallback):</h4>');
                            
                            if (selection && selection.length > 0) {
                                var attachment = selection[0];
                                result.append(
                                    '<div class="media-item">' +
                                    '<img src="' + attachment.sizes.thumbnail.url + '" alt="' + attachment.alt + '">' +
                                    '<div class="filename">' + attachment.filename + '</div>' +
                                    '<div class="details">ID: ' + attachment.id + ', Size: ' + attachment.filesizeHumanReadable + '</div>' +
                                    '</div>'
                                );
                            } else {
                                result.append('<p>No image selected.</p>');
                            }
                        }
                    });
                } else {
                    alert('TomatilloMediaFrame not available. Make sure the custom media frame is loaded.');
                }
                return;
            }
            
            console.log('Using main TomatilloMediaFrame');
            TomatilloMediaFrame.open({
                title: 'Select Single Image (Custom Frame)',
                multiple: false,
                allowedTypes: ['image'],
                onSelect: function(selection) {
                    var result = $('#single-image-result');
                    result.html('<h4>Selected Image (Custom Frame):</h4>');
                    
                    if (selection && selection.length > 0) {
                        var attachment = selection[0];
                        result.append(
                            '<div class="media-item">' +
                            '<img src="' + attachment.sizes.thumbnail.url + '" alt="' + attachment.alt + '">' +
                            '<div class="filename">' + attachment.filename + '</div>' +
                            '<div class="details">ID: ' + attachment.id + ', Size: ' + attachment.filesizeHumanReadable + '</div>' +
                            '</div>'
                        );
                    } else {
                        result.append('<p>No image selected.</p>');
                    }
                }
            });
        }, 100);
    });
    
    // Test multiple images selection
    $('#test-multiple-images').on('click', function() {
        if (typeof TomatilloMediaFrame === 'undefined') {
            alert('TomatilloMediaFrame not available. Make sure the custom media frame is loaded.');
            return;
        }
        
        TomatilloMediaFrame.open({
            title: 'Select Multiple Images',
            multiple: true,
            allowedTypes: ['image'],
            onSelect: function(selection) {
                var result = $('#multiple-images-result');
                result.html('<h4>Selected Images (' + selection.length + '):</h4>');
                
                if (selection && selection.length > 0) {
                    selection.forEach(function(attachment) {
                        result.append(
                            '<div class="media-item">' +
                            '<img src="' + attachment.sizes.thumbnail.url + '" alt="' + attachment.alt + '">' +
                            '<div class="filename">' + attachment.filename + '</div>' +
                            '</div>'
                        );
                    });
                } else {
                    result.append('<p>No images selected.</p>');
                }
            }
        });
    });
    
    // Test all media types
    $('#test-all-media').on('click', function() {
        if (typeof TomatilloMediaFrame === 'undefined') {
            alert('TomatilloMediaFrame not available. Make sure the custom media frame is loaded.');
            return;
        }
        
        TomatilloMediaFrame.open({
            title: 'Select Any Media',
            multiple: true,
            allowedTypes: [],
            onSelect: function(selection) {
                var result = $('#all-media-result');
                result.html('<h4>Selected Media (' + selection.length + '):</h4>');
                
                if (selection && selection.length > 0) {
                    selection.forEach(function(attachment) {
                        var thumbnail = attachment.sizes && attachment.sizes.thumbnail ? 
                            attachment.sizes.thumbnail.url : 
                            attachment.icon;
                        
                        result.append(
                            '<div class="media-item">' +
                            '<img src="' + thumbnail + '" alt="' + attachment.alt + '">' +
                            '<div class="filename">' + attachment.filename + '</div>' +
                            '<div class="details">Type: ' + attachment.type + '</div>' +
                            '</div>'
                        );
                    });
                } else {
                    result.append('<p>No media selected.</p>');
                }
            }
        });
    });
    
    // Test images only
    $('#test-images-only').on('click', function() {
        if (typeof TomatilloMediaFrame === 'undefined') {
            alert('TomatilloMediaFrame not available. Make sure the custom media frame is loaded.');
            return;
        }
        
        TomatilloMediaFrame.open({
            title: 'Select Images Only',
            multiple: true,
            allowedTypes: ['image'],
            library: {
                type: 'image'
            },
            onSelect: function(selection) {
                var result = $('#images-only-result');
                result.html('<h4>Selected Images (' + selection.length + '):</h4>');
                
                if (selection && selection.length > 0) {
                    selection.forEach(function(attachment) {
                        result.append(
                            '<div class="media-item">' +
                            '<img src="' + attachment.sizes.thumbnail.url + '" alt="' + attachment.alt + '">' +
                            '<div class="filename">' + attachment.filename + '</div>' +
                            '<div class="details">Dimensions: ' + attachment.width + 'x' + attachment.height + '</div>' +
                            '</div>'
                        );
                    });
                } else {
                    result.append('<p>No images selected.</p>');
                }
            }
        });
    });
});
</script>
