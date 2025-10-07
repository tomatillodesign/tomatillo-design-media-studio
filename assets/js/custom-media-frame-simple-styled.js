/**
 * Tomatillo Custom Media Frame - Simple Working Version
 * Uses default WordPress frame but with custom styling and behavior
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
        console.log('Tomatillo Media Studio: Initializing simple working custom media frame');
        initializeCustomMediaFrame();
    });

    function initializeCustomMediaFrame() {
        
        try {
            console.log('Starting simple custom media frame initialization...');
            
            /**
             * Main Tomatillo Media Frame Manager
             */
            window.TomatilloMediaFrame = {
                
                /**
                 * Open our custom media frame
                 */
                open: function(options) {
                    console.log('TomatilloMediaFrame.open called with options:', options);
                    
                    options = options || {};
                    
                    // Create WordPress media frame with custom styling
                    var frame = wp.media({
                        title: options.title || 'Select Media',
                        button: options.button || { text: 'Select' },
                        multiple: options.multiple || false,
                        library: options.library || {}
                    });
                    
                    // Add custom styling when frame opens
                    frame.on('open', function() {
                        console.log('Frame opened, applying custom styling');
                        applyCustomStyling();
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
                    console.log('Custom media frame opened');
                    
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

            console.log('TomatilloMediaFrame created successfully');
            
        } catch (error) {
            console.error('Error creating TomatilloMediaFrame:', error);
            console.error('Stack trace:', error.stack);
        }
    }

    /**
     * Apply custom styling to the media frame
     */
    function applyCustomStyling() {
        // Add custom CSS if not already added
        if (!$('#tomatillo-media-frame-styles').length) {
            $('head').append(`
                <style id="tomatillo-media-frame-styles">
                    /* Custom styling for media frame */
                    .media-modal {
                        border-radius: 12px !important;
                        box-shadow: 0 20px 40px rgba(0,0,0,0.3) !important;
                    }
                    
                    .media-modal .media-frame {
                        border-radius: 12px !important;
                    }
                    
                    .media-frame-title {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                        color: white !important;
                        padding: 20px !important;
                        border-radius: 12px 12px 0 0 !important;
                    }
                    
                    .media-frame-title h1 {
                        color: white !important;
                        font-weight: 600 !important;
                        margin: 0 !important;
                    }
                    
                    /* Custom styling for attachments grid */
                    .attachments-browser {
                        padding: 20px !important;
                    }
                    
                    .attachments {
                        display: grid !important;
                        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)) !important;
                        gap: 15px !important;
                        padding: 0 !important;
                    }
                    
                    .attachment {
                        border-radius: 8px !important;
                        overflow: hidden !important;
                        transition: all 0.3s ease !important;
                        cursor: pointer !important;
                        border: 2px solid transparent !important;
                        background: white !important;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
                    }
                    
                    .attachment:hover {
                        transform: translateY(-4px) !important;
                        box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
                        border-color: #667eea !important;
                    }
                    
                    .attachment.selected {
                        border-color: #667eea !important;
                        background: #f0f8ff !important;
                    }
                    
                    .attachment-preview {
                        border-radius: 6px !important;
                        overflow: hidden !important;
                    }
                    
                    .attachment-preview img {
                        width: 100% !important;
                        height: 150px !important;
                        object-fit: cover !important;
                    }
                    
                    .attachment-info {
                        padding: 12px !important;
                        background: white !important;
                    }
                    
                    .attachment-filename {
                        font-weight: 600 !important;
                        color: #333 !important;
                        margin-bottom: 4px !important;
                        font-size: 12px !important;
                        white-space: nowrap !important;
                        overflow: hidden !important;
                        text-overflow: ellipsis !important;
                    }
                    
                    .attachment-meta {
                        color: #666 !important;
                        font-size: 11px !important;
                    }
                    
                    /* Custom toolbar styling */
                    .media-toolbar {
                        background: #f8f9fa !important;
                        border-top: 1px solid #e9ecef !important;
                        padding: 15px 20px !important;
                    }
                    
                    .media-toolbar .button {
                        background: #667eea !important;
                        border-color: #667eea !important;
                        color: white !important;
                        border-radius: 6px !important;
                        padding: 8px 16px !important;
                        font-weight: 600 !important;
                    }
                    
                    .media-toolbar .button:hover {
                        background: #5a6fd8 !important;
                        border-color: #5a6fd8 !important;
                    }
                    
                    /* Custom search styling */
                    .media-frame .search-form {
                        margin-bottom: 20px !important;
                    }
                    
                    .media-frame .search-form input {
                        border-radius: 8px !important;
                        border: 2px solid #e9ecef !important;
                        padding: 12px 16px !important;
                        font-size: 14px !important;
                        transition: border-color 0.3s ease !important;
                    }
                    
                    .media-frame .search-form input:focus {
                        border-color: #667eea !important;
                        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
                    }
                </style>
            `);
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('DOM ready, initializing TomatilloMediaFrame');
        TomatilloMediaFrame.init();
    });

})(jQuery);
