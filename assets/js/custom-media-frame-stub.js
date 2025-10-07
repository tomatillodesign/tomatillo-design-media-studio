/**
 * Tomatillo Custom Media Frame - Basic Stub Version
 * This version will show a CLEAR difference from the default WordPress interface
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
        console.log('Tomatillo Media Studio: Initializing BASIC STUB custom media frame');
        initializeCustomMediaFrame();
    });

    function initializeCustomMediaFrame() {
        
        try {
            console.log('Starting BASIC STUB custom media frame initialization...');
            
            /**
             * Basic Stub Media Frame Manager
             */
            window.TomatilloMediaFrame = {
                
                /**
                 * Open our custom media frame
                 */
                open: function(options) {
                    console.log('BASIC STUB TomatilloMediaFrame.open called with options:', options);
                    
                    options = options || {};
                    
                    // Create a completely custom modal instead of using wp.media
                    console.log('Creating BASIC STUB custom modal...');
                    
                    // Create our own modal HTML
                    var modalHtml = `
                        <div id="tomatillo-custom-modal" style="
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background: rgba(0,0,0,0.8);
                            z-index: 999999;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">
                            <div style="
                                background: white;
                                border-radius: 12px;
                                width: 80%;
                                max-width: 800px;
                                height: 80%;
                                max-height: 600px;
                                display: flex;
                                flex-direction: column;
                                box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                            ">
                                <!-- Header -->
                                <div style="
                                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    color: white;
                                    padding: 20px;
                                    border-radius: 12px 12px 0 0;
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                ">
                                    <h2 style="margin: 0; font-weight: 600;">${options.title || 'CUSTOM MEDIA FRAME'}</h2>
                                    <button id="tomatillo-close-modal" style="
                                        background: none;
                                        border: none;
                                        color: white;
                                        font-size: 24px;
                                        cursor: pointer;
                                        padding: 0;
                                        width: 30px;
                                        height: 30px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                    ">×</button>
                                </div>
                                
                                <!-- Content -->
                                <div style="
                                    flex: 1;
                                    padding: 20px;
                                    overflow-y: auto;
                                    background: #f8f9fa;
                                ">
                                    <div style="
                                        background: white;
                                        border-radius: 8px;
                                        padding: 30px;
                                        text-align: center;
                                        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                                    ">
                                        <h3 style="color: #667eea; margin-bottom: 20px;">🎉 CUSTOM MEDIA FRAME IS WORKING! 🎉</h3>
                                        <p style="color: #666; margin-bottom: 20px;">
                                            This is our custom media frame interface. It's completely different from the default WordPress media library.
                                        </p>
                                        <div style="
                                            background: #e3f2fd;
                                            border: 2px solid #2196f3;
                                            border-radius: 8px;
                                            padding: 20px;
                                            margin: 20px 0;
                                        ">
                                            <h4 style="color: #1976d2; margin-top: 0;">Custom Features:</h4>
                                            <ul style="text-align: left; color: #1976d2;">
                                                <li>✅ Custom modal design</li>
                                                <li>✅ Gradient header</li>
                                                <li>✅ Custom styling</li>
                                                <li>✅ Our own interface</li>
                                            </ul>
                                        </div>
                                        <button id="tomatillo-select-demo" style="
                                            background: #667eea;
                                            color: white;
                                            border: none;
                                            padding: 12px 24px;
                                            border-radius: 6px;
                                            font-weight: 600;
                                            cursor: pointer;
                                            margin: 10px;
                                        ">Select Demo Image</button>
                                        <button id="tomatillo-cancel-demo" style="
                                            background: #6c757d;
                                            color: white;
                                            border: none;
                                            padding: 12px 24px;
                                            border-radius: 6px;
                                            font-weight: 600;
                                            cursor: pointer;
                                            margin: 10px;
                                        ">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Add modal to page
                    $('body').append(modalHtml);
                    console.log('BASIC STUB custom modal added to page');
                    
                    // Handle close button
                    $('#tomatillo-close-modal').on('click', function() {
                        console.log('Close button clicked');
                        $('#tomatillo-custom-modal').remove();
                    });
                    
                    // Handle cancel button
                    $('#tomatillo-cancel-demo').on('click', function() {
                        console.log('Cancel button clicked');
                        $('#tomatillo-custom-modal').remove();
                    });
                    
                    // Handle select button
                    $('#tomatillo-select-demo').on('click', function() {
                        console.log('Select button clicked');
                        
                        // Create a demo selection
                        var demoSelection = [{
                            id: 999,
                            title: 'Demo Custom Image',
                            filename: 'custom-demo-image.jpg',
                            url: 'https://via.placeholder.com/300x200/667eea/ffffff?text=CUSTOM+IMAGE',
                            sizes: {
                                thumbnail: {
                                    url: 'https://via.placeholder.com/150x100/667eea/ffffff?text=CUSTOM'
                                }
                            },
                            filesizeHumanReadable: '25KB',
                            type: 'image'
                        }];
                        
                        console.log('Demo selection created:', demoSelection);
                        
                        // Call the onSelect callback
                        if (options.onSelect) {
                            options.onSelect(demoSelection);
                        }
                        
                        // Close modal
                        $('#tomatillo-custom-modal').remove();
                    });
                    
                    // Handle clicking outside modal
                    $('#tomatillo-custom-modal').on('click', function(e) {
                        if (e.target.id === 'tomatillo-custom-modal') {
                            console.log('Clicked outside modal');
                            $('#tomatillo-custom-modal').remove();
                        }
                    });
                    
                    console.log('BASIC STUB custom media frame opened');
                    
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
                    console.log('BASIC STUB TomatilloMediaFrame.init called');
                    
                    // Intercept classic editor calls
                    this.interceptClassicEditor();
                    
                    console.log('BASIC STUB Tomatillo Media Frame initialized');
                }
            };

            console.log('BASIC STUB TomatilloMediaFrame created successfully');
            
        } catch (error) {
            console.error('Error creating BASIC STUB TomatilloMediaFrame:', error);
            console.error('Stack trace:', error.stack);
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('DOM ready, initializing BASIC STUB TomatilloMediaFrame');
        TomatilloMediaFrame.init();
    });

})(jQuery);
