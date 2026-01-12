/**
 * Tomatillo Custom Media Frame - Minimal Test Version
 * Just to verify the script loads and executes
 */

console.log('Tomatillo custom media frame script loaded');

(function($) {
    'use strict';

    console.log('Inside jQuery wrapper');

    // Wait for wp.media to be available
    function waitForWpMedia(callback) {
        console.log('Checking for wp.media...');
        if (typeof wp !== 'undefined' && wp.media && wp.media.view) {
            console.log('wp.media is ready');
            callback();
        } else {
            console.log('Waiting for wp.media...');
            setTimeout(function() {
                waitForWpMedia(callback);
            }, 100);
        }
    }

    // Initialize when wp.media is ready
    waitForWpMedia(function() {
        console.log('Tomatillo Media Studio: Initializing minimal test');
        initializeCustomMediaFrame();
    });

    function initializeCustomMediaFrame() {
        console.log('Inside initializeCustomMediaFrame');
        
        try {
            console.log('Creating TomatilloMediaFrame...');
            
            /**
             * Minimal Tomatillo Media Frame Manager
             */
            window.TomatilloMediaFrame = {
                
                /**
                 * Open our custom media frame
                 */
                open: function(options) {
                    console.log('TomatilloMediaFrame.open called');
                    
                    options = options || {};
                    
                    // For now, just use the default WordPress media frame
                    var frame = wp.media({
                        title: options.title || 'Select Media',
                        button: options.button || { text: 'Select' },
                        multiple: options.multiple || false,
                        library: options.library || {}
                    });
                    
                    // Handle selection
                    frame.on('select', function() {
                        console.log('Media frame selection made');
                        var selection = frame.state().get('selection').toJSON();
                        console.log('Selection:', selection);
                        
                        if (options.onSelect) {
                            options.onSelect(selection);
                        }
                    });
                    
                    // Open the frame
                    frame.open();
                    console.log('Media frame opened');
                    
                    return frame;
                },
                
                /**
                 * Initialize the custom media frame system
                 */
                init: function() {
                    console.log('TomatilloMediaFrame.init called');
                    console.log('Tomatillo Media Frame initialized');
                }
            };

            console.log('TomatilloMediaFrame created successfully');
            
        } catch (error) {
            console.error('Error creating TomatilloMediaFrame:', error);
            console.error('Stack trace:', error.stack);
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('DOM ready, calling TomatilloMediaFrame.init');
        if (window.TomatilloMediaFrame) {
            window.TomatilloMediaFrame.init();
        } else {
            console.error('TomatilloMediaFrame not available in DOM ready');
        }
    });

})(jQuery);

console.log('Tomatillo custom media frame script finished loading');
