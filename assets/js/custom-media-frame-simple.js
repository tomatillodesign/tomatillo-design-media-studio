/**
 * Tomatillo Custom Media Frame - Simplified Test Version
 * This version starts with basic WordPress media frame functionality
 */

(function($) {
    'use strict';

    // Wait for wp.media to be available
    function waitForWpMedia(callback) {
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
        console.log('Tomatillo Media Studio: Initializing simplified media frame');
        initializeCustomMediaFrame();
    });

    function initializeCustomMediaFrame() {
        
        /**
         * Simplified Tomatillo Media Frame Manager
         */
        window.TomatilloMediaFrame = {
            
            /**
             * Open our custom media frame (simplified version)
             */
            open: function(options) {
                console.log('TomatilloMediaFrame.open called with options:', options);
                
                options = options || {};
                
                // For now, just use the default WordPress media frame
                // This will help us verify the basic integration works
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
             * Intercept wp.media.editor.open calls
             */
            interceptClassicEditor: function() {
                console.log('Intercepting wp.media.editor.open');
                var originalOpen = wp.media.editor.open;
                
                wp.media.editor.open = function(id, options) {
                    console.log('wp.media.editor.open intercepted');
                    // Use our custom frame instead
                    return TomatilloMediaFrame.open(options);
                };
            },
            
            /**
             * Initialize the custom media frame system
             */
            init: function() {
                console.log('TomatilloMediaFrame.init called');
                
                // Intercept classic editor calls
                this.interceptClassicEditor();
                
                console.log('Tomatillo Media Frame initialized');
            }
        };

        // Initialize when DOM is ready
        $(document).ready(function() {
            console.log('DOM ready, initializing TomatilloMediaFrame');
            TomatilloMediaFrame.init();
        });
    }

})(jQuery);
