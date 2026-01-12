/**
 * Tomatillo React Media Upload Component
 * 
 * This React component wraps our custom media frame to work with Gutenberg's MediaUpload
 * Uses the editor.MediaUpload filter to globally replace the default media inserter
 */

console.log('🚀 Tomatillo React Media Upload: Script loaded!');
console.log('📁 Tomatillo: Script location:', window.location.href);
console.log('🪟 Tomatillo: Window context:', window === window.top ? 'parent' : 'iframe');

// Simple test to see if script is working
window.TomatilloReactTest = 'Script loaded successfully';

// Log to parent window for easy debugging
try {
    window.top.console.log('🚀 Tomatillo: React script loaded!');
    window.top.console.log('📁 Tomatillo: In context:', window === window.top ? 'parent' : 'iframe');
} catch(e) {
    console.log('Tomatillo: Could not log to parent window');
}

(function() {
    'use strict';

    // Wait for WordPress dependencies to be available
    function waitForDependencies(callback) {
        console.log('Tomatillo React: Checking for WordPress dependencies...');
        console.log('wp:', typeof wp);
        console.log('wp.hooks:', typeof wp !== 'undefined' && wp.hooks);
        console.log('wp.components:', typeof wp !== 'undefined' && wp.components);
        console.log('wp.element:', typeof wp !== 'undefined' && wp.element);
        console.log('wp.blockEditor:', typeof wp !== 'undefined' && wp.blockEditor);
        
        // For now, just check if wp and wp.hooks are available
        // We'll handle missing components gracefully
        if (typeof wp !== 'undefined' && wp.hooks) {
            console.log('Tomatillo React: Basic WordPress dependencies ready');
            callback();
        } else {
            console.log('Tomatillo React: Waiting for WordPress dependencies...');
            setTimeout(function() {
                waitForDependencies(callback);
            }, 100);
        }
    }

    // Initialize when dependencies are ready
    waitForDependencies(function() {
        console.log('Tomatillo React Media Upload: Initializing');
        try {
            initializeReactMediaUpload();
        } catch (error) {
            console.error('Tomatillo React: Error initializing:', error);
            console.error('Tomatillo React: Stack trace:', error.stack);
        }
    });

    function initializeReactMediaUpload() {
        console.log('Tomatillo React: Initializing React Media Upload component');
        
        try {
            // Check if we have the required WordPress components
            if (!wp.hooks) {
                console.error('Tomatillo React: wp.hooks not available');
                return;
            }
            
            const { addFilter } = wp.hooks;
        
        // Handle missing components gracefully
        const createElement = wp.element ? wp.element.createElement : function(tag, props, children) {
            // Fallback for missing wp.element
            console.warn('Tomatillo React: wp.element not available, using fallback');
            return { tag: tag, props: props, children: children };
        };
        
        const Button = wp.components ? wp.components.Button : function(props) {
            // Fallback button
            return { tag: 'button', props: props };
        };
        
        const __ = wp.i18n ? wp.i18n.__ : function(text) { return text; };

        /**
         * Custom Media Upload Component
         * This replaces the default MediaUpload component globally
         */
        function TomatilloMediaUpload(props) {
            const {
                allowedTypes = [],
                multiple = false,
                value = null,
                onSelect = () => {},
                render = null,
                title = __('Select Media', 'tomatillo-media-studio'),
                button = { text: __('Select', 'tomatillo-media-studio') }
            } = props;

            // Convert allowedTypes array to string format for our custom frame
            const typeFilter = allowedTypes.length > 0 ? allowedTypes : [];

            // Handle opening our custom media frame
            const handleOpenMediaFrame = () => {
                console.log('Opening Tomatillo custom media frame from React component');
                console.log('Props received:', { title, multiple, allowedTypes: typeFilter, value });
                
                // Ensure our custom media frame is available
                if (typeof TomatilloMediaFrame === 'undefined') {
                    console.error('TomatilloMediaFrame not available');
                    return;
                }

                // Open our custom media frame
                TomatilloMediaFrame.open({
                    title: title,
                    multiple: multiple,
                    allowedTypes: typeFilter,
                    library: {
                        type: typeFilter.length === 1 ? typeFilter[0] : undefined
                    },
                    onSelect: function(selection) {
                        console.log('Media selected from custom frame:', selection);
                        
                        // Convert selection to format expected by blocks
                        if (multiple) {
                            // For multiple selection, return array
                            const mediaArray = selection.map(convertAttachmentToMediaObject);
                            onSelect(mediaArray);
                        } else {
                            // For single selection, return single object
                            const mediaObject = selection.length > 0 ? convertAttachmentToMediaObject(selection[0]) : null;
                            onSelect(mediaObject);
                        }
                    }
                });
            };

            // Convert our attachment format to WordPress media object format
            function convertAttachmentToMediaObject(attachment) {
                return {
                    id: attachment.id,
                    url: attachment.url,
                    alt: attachment.alt || '',
                    title: attachment.title || attachment.filename || '',
                    caption: attachment.caption || '',
                    description: attachment.description || '',
                    filename: attachment.filename || '',
                    filesizeHumanReadable: attachment.filesizeHumanReadable || '',
                    width: attachment.width || 0,
                    height: attachment.height || 0,
                    type: attachment.type || 'image',
                    subtype: attachment.subtype || '',
                    mime: attachment.mime || '',
                    sizes: attachment.sizes || {},
                    icon: attachment.icon || attachment.url
                };
            }

            // If custom render function provided, use it
            if (render && typeof render === 'function') {
                return render({ open: handleOpenMediaFrame });
            }

            // Default render - simple button
            return createElement(Button, {
                onClick: handleOpenMediaFrame,
                variant: 'primary'
            }, button.text);
        }

        /**
         * Global MediaUpload Override
         * This replaces the default MediaUpload component everywhere in Gutenberg
         */
        function replaceMediaUpload() {
            console.log('Tomatillo React: Replacing default MediaUpload with Tomatillo custom version');
            return TomatilloMediaUpload;
        }

        // Apply the filter to override MediaUpload globally
        console.log('Tomatillo React: Applying editor.MediaUpload filter...');
        addFilter('editor.MediaUpload', 'tomatillo-media-studio/replace-media-upload', replaceMediaUpload);

        console.log('Tomatillo React Media Upload: Global override applied successfully');
        
        // Log to parent window for easy debugging
        try {
            window.top.console.log('🎯 Tomatillo: MediaUpload filter applied successfully!');
            window.top.console.log('🎯 Tomatillo: Your custom media inserter is now active!');
        } catch(e) {
            console.log('Tomatillo: Could not log to parent window');
        }

        /**
         * Also provide a direct component for manual use
         */
        window.TomatilloMediaUpload = TomatilloMediaUpload;

        console.log('Tomatillo React Media Upload: Initialization complete');
        
        } catch (error) {
            console.error('Tomatillo React: Error in initializeReactMediaUpload:', error);
            console.error('Tomatillo React: Stack trace:', error.stack);
        }
    }

})();
