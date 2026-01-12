/**
 * Tomatillo Custom Media Frame - Clean Version
 * Clean markup, wider images (300px min), proper masonry layout
 */

(function($) {
    'use strict';

// Global variables
var selectedItems = [];
var currentMediaItems = [];
var currentOptimizationData = [];
var renderedItemsCount = 0; // Track how many items have been rendered
var isLoading = false; // Track if infinite scroll is currently loading
var dragDropHandlers = null; // Store drag-drop handlers for cleanup
var lastUploadTime = 0; // Track last upload to prevent duplicates
var uploadDebounceDelay = 500; // Milliseconds to wait between uploads
var hasMoreItemsOnServer = true; // Track if there are more items available on server

// Debug mode: Check if debugging is enabled via settings
var tomatilloDebugMode = (window.tomatilloSettings && window.tomatilloSettings.debug_mode) || false;

// Conditional logging helper
function debugLog() {
    if (tomatilloDebugMode && console && console.log) {
        console.log.apply(console, arguments);
    }
}

// Conditional error logging helper
function debugError() {
    if (tomatilloDebugMode && console && console.error) {
        console.error.apply(console, arguments);
    }
}

// Conditional warning logging helper
function debugWarn() {
    if (tomatilloDebugMode && console && console.warn) {
        console.warn.apply(console, arguments);
    }
}

    // Wait for wp.media to be available
    function waitForWpMedia(callback) {
        if (typeof wp !== 'undefined' && wp.media && wp.media.view) {
            debugLog('wp.media is ready');
            callback();
        } else {
            debugLog('Waiting for wp.media...');
            setTimeout(function() {
                waitForWpMedia(callback);
            }, 100);
        }
    }

    // Initialize when wp.media is ready
    waitForWpMedia(function() {
        debugLog('Tomatillo Media Studio: Initializing CLEAN custom media frame');
        initializeCustomMediaFrame();
    });

    function initializeCustomMediaFrame() {
        
        try {
            debugLog('Starting CLEAN custom media frame initialization...');
            
            /**
             * Clean Media Frame Manager
             */
            window.TomatilloMediaFrame = {
                
                /**
                 * Open our custom media frame
                 */
                open: function(options) {
                    debugLog('CLEAN TomatilloMediaFrame.open called with options:', options);
                    
                    options = options || {};
                    
                    // Create clean modal HTML
                    var modalHtml = createModalHTML(options);
                    
                    // Add modal to page
                    $('body').append(modalHtml);
                    
                    // Add responsive CSS
                    addResponsiveCSS();
                    
                    debugLog('CLEAN custom modal added to page');
                    
                    // CRITICAL: Set default filter to 'image' BEFORE initializing grid
                    // This ensures files are hidden by default
                    setTimeout(function() {
                        $('#tomatillo-filter').val('image');
                        debugLog('✅ Default filter explicitly set to: image');
                        
                        // Initialize the media grid AFTER filter is set
                        initializeMediaGrid(options);
                        
                        // Handle events
                        debugLog('🚀 Setting up event handlers for modal');
                        setupEventHandlers(options);
                        
                        // Add window resize handler for masonry
                        $(window).off('resize.tomatillo-masonry').on('resize.tomatillo-masonry', function() {
                            setTimeout(function() {
                                waitForImagesAndLayout();
                            }, 150);
                        });
                        
                        // Note: Auto-selection is now handled in real-time during upload
                        
                        debugLog('CLEAN custom media frame opened');
                    }, 10);
                    
                },
                
                /**
                 * Intercept wp.media.editor.open calls
                 */
                interceptClassicEditor: function() {
                    var originalOpen = wp.media.editor.open;
                    
                    wp.media.editor.open = function(id, options) {
                        // Use our custom frame instead
                        return TomatilloMediaFrame.open(options);
                    };
                },
                
                /**
                 * Initialize the custom media frame system
                 */
                init: function() {
                    // Intercept classic editor calls
                    this.interceptClassicEditor();
                    
                    // Also intercept wp.media() calls (ACF might use this)
                    this.interceptWpMedia();
                    
                    // Intercept ACF's specific media popup function
                    this.interceptACFMediaPopup();
                    
                    // Listen for ACF remove button clicks to ensure proper state management
                    this.setupACFRemoveListener();
                    
                    // Upgrade existing ACF image previews on page load
                    this.upgradeACFPreviewsOnLoad();
                },
                
                /**
                 * Setup listener for ACF remove button clicks
                 */
                setupACFRemoveListener: function() {
                    // Use event delegation to catch remove button clicks
                    $(document).on('click', '.acf-icon.-cancel', function(e) {
                        // Find the field wrapper
                        var $fieldWrapper = $(this).closest('.acf-field-image, .acf-field, [data-key][data-type="image"]');
                        if ($fieldWrapper.length) {
                            // Ensure the field is properly reset
                            TomatilloMediaFrame.resetACFField($fieldWrapper);
                        }
                    });
                },
                
                /**
                 * Reset ACF field to empty state
                 */
                resetACFField: function($fieldWrapper) {
                    try {
                        // Clear the hidden input value
                        var $hiddenInput = $fieldWrapper.find('input[type="hidden"]');
                        if ($hiddenInput.length) {
                            $hiddenInput.val('');
                        }
                        
                        // Hide the preview container
                        var $previewContainer = $fieldWrapper.find('.show-if-value');
                        if ($previewContainer.length) {
                            $previewContainer.hide();
                        }
                        
                        // Show the "Add Image" button
                        var $addButton = $fieldWrapper.find('.hide-if-value');
                        if ($addButton.length) {
                            $addButton.show();
                        }
                        
                        // Update field state classes
                        var $uploader = $fieldWrapper.find('.acf-image-uploader');
                        if ($uploader.length) {
                            $uploader.removeClass('has-value');
                            $uploader.addClass('no-value');
                        }
                        
                        // Try to use ACF's own field reset if available
                        var acfField = $uploader.data('acf-field');
                        if (acfField && acfField.val) {
                            acfField.val('');
                        }
                        
                        // Trigger change events
                        this.triggerACFChange($fieldWrapper);
                    } catch (error) {
                        // Silently handle errors
                    }
                },
                
                /**
                 * Upgrade existing ACF image previews on page load
                 */
                upgradeACFPreviewsOnLoad: function() {
                    // Wait a bit for ACF to fully initialize
                    setTimeout(function() {
                        // Find all ACF image previews
                        var $acfImages = $('.acf-image-uploader .show-if-value img');
                        
                        if ($acfImages.length === 0) {
                            $acfImages = $('.acf-field-image img, [data-type="image"] img');
                        }
                        
                        $acfImages.each(function(index) {
                            var $img = $(this);
                            var currentSrc = $img.attr('src');
                            
                            if (currentSrc && currentSrc.includes('-')) {
                                // Extract attachment ID from the field
                                var $fieldWrapper = $img.closest('.acf-field-image, .acf-field, [data-key][data-type="image"]');
                                var $hiddenInput = $fieldWrapper.find('input[type="hidden"]');
                                var attachmentId = $hiddenInput.val();
                                
                                if (attachmentId) {
                                    // Try AJAX approach first
                                    TomatilloMediaFrame.getHighResImageForACF(attachmentId, function(highResUrl) {
                                        if (highResUrl && highResUrl !== currentSrc) {
                                            // Update the image source
                                            $img.attr('src', highResUrl);
                                            
                                            // Apply high-quality styling
                                            $img.css({
                                                'max-width': '300px',
                                                'max-height': '300px',
                                                'width': 'auto',
                                                'height': 'auto',
                                                'object-fit': 'contain',
                                                'image-rendering': 'high-quality'
                                            });
                                        } else {
                                            // Fallback: Direct URL cleanup
                                            var cleanUrl = TomatilloMediaFrame.removeImageSizeSuffix(currentSrc);
                                            if (cleanUrl !== currentSrc) {
                                                $img.attr('src', cleanUrl);
                                                
                                                // Apply high-quality styling
                                                $img.css({
                                                    'max-width': '300px',
                                                    'max-height': '300px',
                                                    'width': 'auto',
                                                    'height': 'auto',
                                                    'object-fit': 'contain',
                                                    'image-rendering': 'high-quality'
                                                });
                                            }
                                        }
                                    });
                                } else {
                                    // Fallback: Direct URL cleanup without attachment ID
                                    var cleanUrl = TomatilloMediaFrame.removeImageSizeSuffix(currentSrc);
                                    if (cleanUrl !== currentSrc) {
                                        $img.attr('src', cleanUrl);
                                        
                                        // Apply high-quality styling
                                        $img.css({
                                            'max-width': '300px',
                                            'max-height': '300px',
                                            'width': 'auto',
                                            'height': 'auto',
                                            'object-fit': 'contain',
                                            'image-rendering': 'high-quality'
                                        });
                                    }
                                }
                            }
                        });
                    }, 1000); // Wait 1 second for ACF to initialize
                },
                
                /**
                 * Get high-resolution image URL for ACF preview
                 */
                getHighResImageForACF: function(attachmentId, callback) {
                    debugLog('🔍 ACF Bridge: Getting high-res image for attachment:', attachmentId);
                    debugLog('🔍 ACF Bridge: AJAX URL:', ajaxurl || '/wp-admin/admin-ajax.php');
                    debugLog('🔍 ACF Bridge: Nonce:', tomatillo_nonce || 'test');
                    
                    // Use WordPress AJAX to get image data
                    $.ajax({
                        url: ajaxurl || '/wp-admin/admin-ajax.php',
                        type: 'POST',
                        data: {
                            action: 'tomatillo_get_image_data',
                            image_id: attachmentId,
                            nonce: tomatillo_nonce || 'test'
                        },
                        success: function(response) {
                            debugLog('🔍 ACF Bridge: AJAX success for attachment', attachmentId);
                            debugLog('🔍 ACF Bridge: Full response:', response);
                            
                            if (response.success && response.data) {
                                var data = response.data;
                                debugLog('🔍 ACF Bridge: Response data:', data);
                                
                                var bestUrl = null;
                                
                                // Priority: AVIF → WebP → Scaled → Large → Original
                                if (data.avif_url) {
                                    bestUrl = data.avif_url;
                                    debugLog('🔍 ACF Bridge: Using AVIF URL:', bestUrl);
                                } else if (data.webp_url) {
                                    bestUrl = data.webp_url;
                                    debugLog('🔍 ACF Bridge: Using WebP URL:', bestUrl);
                                } else if (data.scaled_url) {
                                    bestUrl = data.scaled_url;
                                    debugLog('🔍 ACF Bridge: Using scaled URL:', bestUrl);
                                } else if (data.large_url) {
                                    bestUrl = data.large_url;
                                    debugLog('🔍 ACF Bridge: Using large URL:', bestUrl);
                                } else {
                                    // Fallback: remove size suffix from current URL
                                    bestUrl = TomatilloMediaFrame.removeImageSizeSuffix(data.url || '');
                                    debugLog('🔍 ACF Bridge: Using fallback URL:', bestUrl);
                                }
                                
                                debugLog('🔍 ACF Bridge: Final best URL:', bestUrl);
                                callback(bestUrl);
                            } else {
                                debugLog('🔍 ACF Bridge: AJAX response not successful or no data');
                                debugLog('🔍 ACF Bridge: Response success:', response.success);
                                debugLog('🔍 ACF Bridge: Response data:', response.data);
                                callback(null);
                            }
                        },
                        error: function(xhr, status, error) {
                            debugError('🔍 ACF Bridge: AJAX error for attachment', attachmentId);
                            debugError('🔍 ACF Bridge: Status:', status);
                            debugError('🔍 ACF Bridge: Error:', error);
                            debugError('🔍 ACF Bridge: XHR:', xhr);
                            callback(null);
                        }
                    });
                },
                
                /**
                 * Remove WordPress image size suffix from URL
                 */
                removeImageSizeSuffix: function(url) {
                    if (!url) return url;
                    
                    // Remove WordPress size suffixes like -300x200, -1024x683, etc.
                    var cleanUrl = url.replace(/-\d+x\d+(?=\.[^.]+$)/, '');
                    
                    // Also remove -scaled suffix if present
                    cleanUrl = cleanUrl.replace(/-scaled(?=\.[^.]+$)/, '');
                    
                    debugLog('ACF Bridge: Cleaned URL:', url, '→', cleanUrl);
                    return cleanUrl;
                },
                
                /**
                 * Intercept wp.media() calls (ACF might use this)
                 */
                interceptWpMedia: function() {
                    debugLog('Intercepting wp.media() calls');
                    var originalWpMedia = wp.media;
                    
                    wp.media = function(options) {
                        debugLog('wp.media() intercepted');
                        debugLog('ACF/wp.media Call - Options:', options);
                        debugLog('Stack trace:', new Error().stack);
                        
                        // Check if this looks like a media picker call
                        if (options && (options.title || options.button || options.multiple !== undefined)) {
                            debugLog('This looks like a media picker - using custom frame');
                            return TomatilloMediaFrame.open(options);
                        }
                        
                        // Otherwise, use original wp.media
                        return originalWpMedia.call(this, options);
                    };
                    
                    // Preserve all wp.media properties and methods
                    Object.keys(originalWpMedia).forEach(function(key) {
                        if (typeof originalWpMedia[key] === 'function') {
                            wp.media[key] = originalWpMedia[key];
                        } else {
                            wp.media[key] = originalWpMedia[key];
                        }
                    });
                },
                
                /**
                 * Intercept ACF's specific media popup function
                 */
                interceptACFMediaPopup: function() {
                    var retryCount = 0;
                    var maxRetries = 50; // 5 seconds max
                    
                    // Wait for ACF to be available
                    var checkACF = function() {
                        if (typeof acf !== 'undefined' && acf.newMediaPopup) {
                            // ACF found, intercept silently
                            var originalACFMediaPopup = acf.newMediaPopup;
                            
                            acf.newMediaPopup = function(options) {
                                // Find ACF field by looking at the current active element
                                var $activeElement = $(document.activeElement);
                                
                                var $fallbackField = $activeElement.closest('.acf-field-image, .acf-field, [data-key][data-type="image"], [data-key][data-type="gallery"]');
                                if ($fallbackField.length) {
                                    // Detect field type to determine selection mode
                                    var fieldType = TomatilloMediaFrame.detectACFFieldType($fallbackField);
                                    var isMultiple = (fieldType === 'gallery');
                                    
                                    var modifiedOptions = Object.assign({}, options, {
                                        multiple: isMultiple,
                                        onSelect: function(selection) {
                                            if (fieldType === 'gallery') {
                                                // Handle gallery field - convert each selection item to ACF format
                                                var acfAttachments = selection.map(function(item) {
                                                    return TomatilloMediaFrame.normalizeToACFAttachment([item], $fallbackField);
                                                });
                                                
                                                // Use the enhanced gallery handler if available
                                                if (window.ACFGalleryHandler && typeof window.ACFGalleryHandler.setGalleryIds === 'function') {
                                                    var field = acf.getField($fallbackField);
                                                    if (field) {
                                                        window.ACFGalleryHandler.setGalleryIds(field, acfAttachments.map(function(att) { return att.id; }));
                                                    } else {
                                                        TomatilloMediaFrame.setACFGalleryValue($fallbackField, acfAttachments);
                                                        TomatilloMediaFrame.triggerACFChange($fallbackField);
                                                    }
                                                } else {
                                                    TomatilloMediaFrame.setACFGalleryValue($fallbackField, acfAttachments);
                                                    TomatilloMediaFrame.triggerACFChange($fallbackField);
                                                }
                                            } else {
                                                // Handle image field - single selection
                                                var acfAttachment = TomatilloMediaFrame.normalizeToACFAttachment(selection, $fallbackField);
                                                TomatilloMediaFrame.setACFFieldValue($fallbackField, acfAttachment);
                                                TomatilloMediaFrame.triggerACFChange($fallbackField);
                                            }
                                        }
                                    });
                                    
                                    return TomatilloMediaFrame.open(modifiedOptions);
                                } else {
                                    // Final fallback to original callback approach
                                    var originalCallback = options.select || options.callback || options.onSelect;
                                    var modifiedOptions = Object.assign({}, options, {
                                        onSelect: function(selection) {
                                            if (typeof originalCallback === 'function') {
                                                var acfSelection = selection.map(function(item) {
                                                    return {
                                                        id: item.id,
                                                        url: item.url,
                                                        title: item.title,
                                                        filename: item.filename,
                                                        alt: item.alt,
                                                        description: item.description,
                                                        caption: item.caption,
                                                        mime: item.mime,
                                                        subtype: item.subtype,
                                                        icon: item.icon,
                                                        sizes: item.sizes,
                                                        thumbnail: item.thumbnail,
                                                        width: item.width,
                                                        height: item.height
                                                    };
                                                });
                                                originalCallback(acfSelection);
                                            }
                                        }
                                    });
                                    return TomatilloMediaFrame.open(modifiedOptions);
                                }
                            };
                        } else {
                            // Retry with limit
                            retryCount++;
                            if (retryCount < maxRetries) {
                                setTimeout(checkACF, 100);
                            }
                            // Silently stop trying after max retries - ACF may not be installed
                        }
                    };
                    
                    checkACF();
                },
                
                /**
                 * Find ACF field instance from options or DOM context
                 */
                findACFFieldInstance: function(options) {
                    // Try to find field instance from options context
                    if (options && options.field) {
                        return options.field;
                    }
                    
                    // Try to find field instance from DOM context
                    if (options && options.context) {
                        var $context = $(options.context);
                        
                        // Look for the field wrapper in the context
                        var $fieldWrapper = $context.closest('[data-key][data-type="image"]');
                        if ($fieldWrapper.length) {
                            return $fieldWrapper;
                        }
                        
                        // If no field wrapper found, try to find it by looking for ACF image field elements
                        var $acfImageField = $context.closest('.acf-field-image');
                        if ($acfImageField.length) {
                            return $acfImageField;
                        }
                        
                        // Try to find any ACF field wrapper
                        var $acfField = $context.closest('.acf-field');
                        if ($acfField.length) {
                            return $acfField;
                        }
                    }
                    
                    return null;
                },
                
                /**
                 * Detect ACF field type (single image vs gallery)
                 */
                detectACFFieldType: function(fieldInstance) {
                    var $fieldWrapper = $(fieldInstance);
                    var fieldType = 'image'; // Default to single image
                    
                    // Check data-type attribute
                    var dataType = $fieldWrapper.attr('data-type');
                    if (dataType) {
                        fieldType = dataType;
                    }
                    
                    // Check for gallery-specific classes or attributes
                    if ($fieldWrapper.hasClass('acf-field-gallery') || 
                        $fieldWrapper.find('.acf-gallery').length > 0 ||
                        dataType === 'gallery') {
                        fieldType = 'gallery';
                    }
                    
                    // Check for image-specific classes
                    if ($fieldWrapper.hasClass('acf-field-image') || 
                        $fieldWrapper.find('.acf-image-uploader').length > 0 ||
                        dataType === 'image') {
                        fieldType = 'image';
                    }
                    
                    return fieldType;
                },
                
                /**
                 * Normalize media selection to ACF attachment format with high-res optimization
                 */
                normalizeToACFAttachment: function(selection, fieldInstance) {
                    // Handle single vs multiple
                    var media = Array.isArray(selection) ? selection[0] : selection;
                    
                    var attachment = {
                        id: media.id || media.ID,
                        url: media.url || media.source_url,
                        alt: media.alt || media.alt_text || '',
                        width: media.width || (media.media_details && media.media_details.width),
                        height: media.height || (media.media_details && media.media_details.height),
                        title: media.title || media.filename || '',
                        filename: media.filename || '',
                        mime: media.mime || '',
                        sizes: media.sizes || {}
                    };
                    
                    // Get the best high-resolution image URL for ACF preview
                    attachment.thumbnail = this.getBestACFPreviewUrl(attachment);
                    
                    return attachment;
                },
                
                /**
                 * Get the best high-resolution image URL for ACF preview
                 */
                getBestACFPreviewUrl: function(attachment) {
                    // Priority order: AVIF → WebP → Scaled → Large → Medium → Original
                    var sizes = attachment.sizes || {};
                    var bestUrl = null;
                    var maxWidth = 0;
                    
                    // 1. Check for AVIF versions (best compression, modern browsers)
                    for (var sizeName in sizes) {
                        if (sizeName.includes('avif') && sizes[sizeName].url) {
                            var width = sizes[sizeName].width || 0;
                            if (width > maxWidth && width <= 1200) { // Cap at reasonable size for admin
                                maxWidth = width;
                                bestUrl = sizes[sizeName].url;
                            }
                        }
                    }
                    
                    // 2. Check for WebP versions (good compression, wide support)
                    if (!bestUrl) {
                        maxWidth = 0;
                        for (var sizeName in sizes) {
                            if (sizeName.includes('webp') && sizes[sizeName].url) {
                                var width = sizes[sizeName].width || 0;
                                if (width > maxWidth && width <= 1200) {
                                    maxWidth = width;
                                    bestUrl = sizes[sizeName].url;
                                }
                            }
                        }
                    }
                    
                    // 3. Check for scaled versions (WordPress's high-res fallback)
                    if (!bestUrl && sizes.scaled && sizes.scaled.url) {
                        bestUrl = sizes.scaled.url;
                    }
                    
                    // 4. Check for large size
                    if (!bestUrl && sizes.large && sizes.large.url) {
                        bestUrl = sizes.large.url;
                    }
                    
                    // 5. Check for medium size
                    if (!bestUrl && sizes.medium && sizes.medium.url) {
                        bestUrl = sizes.medium.url;
                    }
                    
                    // 6. Fallback to original URL
                    if (!bestUrl) {
                        bestUrl = attachment.url;
                    }
                    
                    return bestUrl;
                },
                
                /**
                 * Set ACF field value by working with ACF's field management
                 */
                setACFFieldValue: function(fieldInstance, attachment) {
                    try {
                        // fieldInstance should now be a jQuery object
                        var $fieldWrapper = $(fieldInstance);
                        
                        if ($fieldWrapper.length === 0) {
                            return;
                        }
                        
                        // Set the hidden input value (this is what ACF actually stores)
                        var $hiddenInput = $fieldWrapper.find('input[type="hidden"]');
                        if ($hiddenInput.length) {
                            $hiddenInput.val(attachment.id);
                        }
                        
                        // Instead of directly manipulating DOM, trigger ACF's own field update
                        // This ensures ACF handles all the state management properly
                        var $uploader = $fieldWrapper.find('.acf-image-uploader');
                        if ($uploader.length) {
                            // Trigger ACF's internal field update by simulating the selection
                            var acfField = $uploader.data('acf-field');
                            if (acfField && acfField.val) {
                                // Use ACF's own val() method to set the value
                                acfField.val(attachment.id);
                            } else {
                                // Fallback: manually update the preview but let ACF handle the rest
                                this.updateACFPreview($fieldWrapper, attachment);
                            }
                        } else {
                            this.updateACFPreview($fieldWrapper, attachment);
                        }
                        
                    } catch (error) {
                        // Silently handle errors - ACF field may not be properly initialized
                    }
                },
                
                /**
                 * Update ACF preview manually (fallback method) with high-res optimization
                 */
                updateACFPreview: function($fieldWrapper, attachment) {
                    // Update the preview image - ACF uses .show-if-value img
                    var $previewImg = $fieldWrapper.find('.show-if-value img');
                    if ($previewImg.length) {
                        // Set the high-resolution image URL
                        $previewImg.attr('src', attachment.thumbnail);
                        $previewImg.attr('alt', attachment.alt);
                        
                        // Add CSS to ensure the image displays at high quality but restricted size
                        $previewImg.css({
                            'max-width': '300px',
                            'max-height': '300px',
                            'width': 'auto',
                            'height': 'auto',
                            'object-fit': 'contain',
                            'image-rendering': 'high-quality'
                        });
                    }
                    
                    // Show the preview container - ACF uses .show-if-value
                    var $previewContainer = $fieldWrapper.find('.show-if-value');
                    if ($previewContainer.length) {
                        $previewContainer.show();
                    }
                    
                    // Hide the "Add Image" button - ACF uses .hide-if-value
                    var $addButton = $fieldWrapper.find('.hide-if-value');
                    if ($addButton.length) {
                        $addButton.hide();
                    }
                    
                    // Update field state classes - ACF uses .acf-image-uploader.has-value
                    var $uploader = $fieldWrapper.find('.acf-image-uploader');
                    if ($uploader.length) {
                        $uploader.addClass('has-value');
                        $uploader.removeClass('no-value');
                    }
                },
                
                /**
                 * Set ACF gallery field value using the same pattern as single image
                 */
                setACFGalleryValue: function($fieldWrapper, attachments) {
                    debugLog('🎯 GALLERY UPDATE: Starting gallery update for', attachments.length, 'attachments');

                    try {
                        // =================== ACF GALLERY DEBUGGING ===================
                        debugLog('🔍 ANALYZING ACF GALLERY STRUCTURE');
                        var $galleryContainer = $fieldWrapper.find('.acf-gallery');
                        if (!$galleryContainer.length) {
                            debugLog('❌ GALLERY UPDATE: No gallery container found');
                            return;
                        }

                        debugLog('✅ GALLERY UPDATE: Found gallery container');

                        // Extract attachment IDs
                        var attachmentIds = attachments.map(function(attachment) {
                            return attachment.id;
                        });
                        debugLog('🎯 GALLERY UPDATE: Attachment IDs:', attachmentIds);

                        // =================== STEP 1: ANALYZE CURRENT STATE ===================
                        var $hiddenInput = $galleryContainer.find('input[type="hidden"]');
                        debugLog('🔍 Hidden input analysis:');
                        debugLog('  - Found:', $hiddenInput.length);
                        debugLog('  - Name:', $hiddenInput.attr('name'));
                        debugLog('  - Current value:', $hiddenInput.val());

                        var $attachmentsContainer = $galleryContainer.find('.acf-gallery-attachments');
                        var existingAttachments = $attachmentsContainer.find('.acf-gallery-attachment:not(.-icon)');
                        debugLog('🔍 Existing attachments:', existingAttachments.length);

                        // Try to get ACF field object for analysis
                        var fieldKey = $fieldWrapper.attr('data-key');
                        if (fieldKey && typeof acf !== 'undefined' && acf.getField) {
                            try {
                                var field = acf.getField(fieldKey);
                                if (field) {
                                    debugLog('🔍 ACF Field Model Analysis:');
                                    debugLog('  - Field key:', field.get('key'));
                                    debugLog('  - Field name:', field.get('name'));
                                    debugLog('  - Field type:', field.get('type'));
                                    debugLog('  - Current model value:', field.val());
                                    debugLog('  - Model value type:', typeof field.val());
                                } else {
                                    debugLog('❌ Could not get ACF field object');
                                }
                            } catch(e) {
                                debugLog('❌ Error accessing ACF field model:', e);
                            }
                        }

                        // =================== STEP 2: SET HIDDEN INPUT ===================
                        if ($hiddenInput.length) {
                            var idsString = attachmentIds.join(',');
                            $hiddenInput.val(idsString);
                            debugLog('✅ GALLERY UPDATE: Set hidden input to:', idsString);
                        } else {
                            debugLog('❌ GALLERY UPDATE: No hidden input found');
                            return;
                        }

                        // =================== STEP 3: CREATE VISUAL THUMBNAILS ===================
                        if ($attachmentsContainer.length) {
                            debugLog('✅ GALLERY UPDATE: Found attachments container');

                            // Clear existing attachments (keep placeholder)
                            $attachmentsContainer.find('.acf-gallery-attachment:not(.-icon)').remove();

                            // Create thumbnails for each attachment
                            attachments.forEach(function(attachment, index) {
                                debugLog('🎯 GALLERY UPDATE: Creating thumbnail for attachment:', attachment.id);

                                // Get the best image URL
                                var imageUrl = TomatilloMediaFrame.getBestACFPreviewUrl(attachment);

                                // Create the attachment HTML (matching ACF's exact format)
                                var attachmentHtml = `
                                    <div class="acf-gallery-attachment" data-id="${attachment.id}">
                                        <input type="hidden" value="${attachment.id}" name="acf-gallery-attachment-${attachment.id}">
                                        <div class="margin" title="${attachment.title || attachment.filename || ''}">
                                            <div class="thumbnail">
                                                <img src="${imageUrl}" alt="${attachment.alt || ''}" title="${attachment.title || attachment.filename || ''}" style="max-width: 100%; max-height: 100%; object-fit: cover; image-rendering: -webkit-optimize-contrast;">
                                            </div>
                                        </div>
                                        <div class="actions">
                                            <a href="#" class="acf-icon -cancel dark acf-gallery-remove" data-id="${attachment.id}"></a>
                                        </div>
                                    </div>
                                `;

                                // Add to container
                                $attachmentsContainer.append(attachmentHtml);
                            });

                            debugLog('✅ GALLERY UPDATE: Visual thumbnails created');
                        }

                        // =================== STEP 4: MULTIPLE PERSISTENCE METHODS ===================
                        debugLog('🎯 GALLERY UPDATE: Attempting multiple persistence methods');

                        // Method 1: Direct hidden input change
                        $hiddenInput.trigger('change');
                        $hiddenInput.trigger('input');
                        $hiddenInput.trigger('blur');
                        debugLog('✅ Method 1: Hidden input change events');

                        // Method 2: Field wrapper change
                        $fieldWrapper.trigger('change');
                        debugLog('✅ Method 2: Field wrapper change event');

                        // Method 3: Try ACF field model if available
                        if (fieldKey && typeof acf !== 'undefined' && acf.getField) {
                            try {
                                var field = acf.getField(fieldKey);
                                if (field) {
                                    debugLog('🔍 ACF Gallery Model Details:');
                                    debugLog('  - Field object:', field);
                                    debugLog('  - Available methods:', Object.getOwnPropertyNames(Object.getPrototypeOf(field)));
                                    debugLog('  - Field data:', field.get ? field.get('data') : 'no get method');

                                    var beforeVal = field.val();
                                    debugLog('  - Before setting:', beforeVal, typeof beforeVal);

                                    field.val(attachmentIds);
                                    var afterVal = field.val();
                                    debugLog('  - After setting:', afterVal, typeof afterVal);

                                    if (afterVal && Array.isArray(afterVal) && afterVal.length === attachmentIds.length) {
                                        debugLog('✅ Method 3: ACF field model updated successfully');
                                    } else {
                                        debugLog('❌ Method 3: ACF field model may not be working correctly');
                                        debugLog('  - Expected:', attachmentIds);
                                        debugLog('  - Got:', afterVal);
                                    }

                                    field.$el.trigger('change');
                                }
                            } catch(e) {
                                debugLog('❌ Method 3: ACF field model failed:', e);
                            }
                        }

                        // Method 4: Form-level change for WordPress recognition
                        var $form = $hiddenInput.closest('form');
                        if ($form.length) {
                            $form.trigger('change');
                            debugLog('✅ Method 4: Form change triggered');
                        }

                        // Method 5: Block Editor Data Update (CRITICAL FIX)
                        if (window.wp && wp.data && wp.data.select && wp.data.dispatch) {
                            try {
                                var blockEditor = wp.data.select('core/block-editor');
                                var editorDispatch = wp.data.dispatch('core/block-editor');

                                if (blockEditor && blockEditor.getBlocks && editorDispatch && editorDispatch.updateBlockAttributes) {
                                    var blocks = blockEditor.getBlocks();
                                    debugLog('🔍 BLOCK EDITOR GALLERY UPDATE:');

                                    if (blocks && blocks.length > 0) {
                                        var blockUpdated = false;

                                        blocks.forEach(function(block, index) {
                                            // Look for blocks that might contain our gallery field
                                            if (block.name && (block.name.includes('acf/') || block.name.includes('yakstretch') || block.name.includes('yak'))) {
                                                debugLog('  - Checking block ' + index + ':', block.name);

                                                if (block.attributes && block.attributes.data) {
                                                    var blockData = block.attributes.data;
                                                    debugLog('    - Block data keys:', Object.keys(blockData));
                                                    debugLog('    - Field key to find:', fieldKey);
                                                    
                                                    // Use the actual ACF field name from the model; fallback to canonical 'gallery'
                                                    var acfFieldModel = (typeof acf !== 'undefined' && acf.getField) ? acf.getField(fieldKey) : null;
                                                    var fieldName = (acfFieldModel && acfFieldModel.get) ? acfFieldModel.get('name') : 'gallery';
                                                    debugLog('    - Field name to use:', fieldName);
                                                    debugLog('    - Checking block data for:', fieldName);

                                                    // Check if this block contains our gallery field
                                                    // ACF may store under field name OR field key depending on config
                                                    var hasByName = blockData.hasOwnProperty(fieldName);
                                                    var hasByKey  = blockData.hasOwnProperty(fieldKey);
                                                    if (hasByName || hasByKey) {
                                                        debugLog('    ✅ Found gallery field in block via', hasByName ? 'name' : 'key');

                                                        // Update the block data with our gallery IDs (write both for safety)
                                                        var updatedData = Object.assign({}, blockData);
                                                        updatedData[fieldName] = attachmentIds;
                                                        updatedData[fieldKey]  = attachmentIds;
                                                        // Maintain underscore pointer if missing
                                                        var underscorePtr = '_' + fieldName;
                                                        if (!updatedData.hasOwnProperty(underscorePtr)) {
                                                            updatedData[underscorePtr] = fieldKey;
                                                            debugLog('    - Set underscore pointer', underscorePtr, '→', fieldKey);
                                                        }

                                                        debugLog('    - Before update (name):', blockData[fieldName]);
                                                        debugLog('    - Before update (key) :', blockData[fieldKey]);
                                                        debugLog('    - Setting to:', attachmentIds);

                                                        // Update the block attributes
                                                        var updatedAttributes = Object.assign({}, block.attributes);
                                                        updatedAttributes.data = updatedData;

                                                        editorDispatch.updateBlockAttributes(block.clientId, updatedAttributes);
                                                        blockUpdated = true;

                                                        debugLog('    ✅ Block updated with gallery data:', attachmentIds);

                                                        // Verify the update
                                                        setTimeout(function() {
                                                            var verifyBlocks = blockEditor.getBlocks();
                                                            var verifyBlock = verifyBlocks.find(function(b) { return b.clientId === block.clientId; });
                                                            if (verifyBlock && verifyBlock.attributes && verifyBlock.attributes.data) {
                                                                debugLog('    🔍 Verification - Block data after update (name):', verifyBlock.attributes.data[fieldName]);
                                                                debugLog('    🔍 Verification - Block data after update (key) :', verifyBlock.attributes.data[fieldKey]);
                                                                var ptr = '_' + fieldName;
                                                                debugLog('    🔍 Verification - Underscore pointer:', verifyBlock.attributes.data[ptr]);
                                                            }
                                                        }, 50);

                                                    } else {
                                                        debugLog('    ❌ Gallery field not found in this block');
                                                    }
                                                } else {
                                                    debugLog('    ❌ Block has no data attribute');
                                                }
                                            }
                                        });

                                        if (blockUpdated) {
                                            debugLog('✅ Method 5: Block data updated successfully');
                                            wp.data.dispatch('core/editor').editPost({}); // Trigger editor update
                                        } else {
                                            debugLog('❌ Method 5: No suitable block found for gallery update');
                                        }

                                    } else {
                                        debugLog('❌ Method 5: No blocks found');
                                    }
                                } else {
                                    debugLog('❌ Method 5: Block editor APIs not available');
                                }

                            } catch(e) {
                                debugLog('❌ Method 5: Block editor update failed:', e);
                            }
                        }

                        // Method 6: ACF-specific serialization check
                        debugLog('🎯 Method 6: Checking ACF serialization');
                        if (typeof acf !== 'undefined') {
                            try {
                                // Check if ACF has serialization methods
                                debugLog('🔍 ACF Methods:', Object.getOwnPropertyNames(acf).filter(name => name.includes('serial')));
                                debugLog('🔍 ACF Actions:', Object.getOwnPropertyNames(acf).filter(name => name.includes('save')));
                            } catch(e) {
                                debugLog('❌ ACF serialization check failed:', e);
                            }
                        }

                        // =================== STEP 5: VERIFICATION ===================
                        setTimeout(function() {
                            debugLog('🔍 VERIFICATION RESULTS:');
                            debugLog('  - Hidden input value:', $hiddenInput.val());
                            debugLog('  - Expected value:', idsString);
                            debugLog('  - Match:', $hiddenInput.val() === idsString ? '✅' : '❌');

                            if (fieldKey && typeof acf !== 'undefined' && acf.getField) {
                                try {
                                    var field = acf.getField(fieldKey);
                                    if (field) {
                                        debugLog('  - Model value:', field.val());
                                        debugLog('  - Model match:', JSON.stringify(field.val()) === JSON.stringify(attachmentIds) ? '✅' : '❌');
                                    }
                                } catch(e) {
                                    debugLog('  - Model verification failed:', e);
                                }
                            }

                            debugLog('🎯 GALLERY UPDATE: Gallery update completed successfully');

                            // Final debugging: Set up form submission monitoring
                            debugLog('🔍 Setting up form submission monitoring...');
                            setTimeout(function() {
                                var $form = $hiddenInput.closest('form');
                                if ($form.length) {
                                    $form.off('submit.gallery-debug').on('submit.gallery-debug', function(e) {
                                        debugLog('🚨 FORM SUBMISSION INTERCEPTED');
                                        debugLog('Form data being submitted:');

                                        // Get all form data
                                        var formData = new FormData(this);
                                        var dataObject = {};
                                        for (var pair of formData.entries()) {
                                            if (pair[0].includes('gallery') || pair[0].includes('yakstretch')) {
                                                dataObject[pair[0]] = pair[1];
                                            }
                                        }
                                        debugLog('Gallery-related form fields:', dataObject);

                                        // Also check the hidden input at submission time
                                        debugLog('Hidden input at submission:', $hiddenInput.val());
                                        debugLog('🚨 FORM SUBMISSION DATA CAPTURED');
                                    });

                                    debugLog('✅ Form submission monitoring enabled');
                                } else {
                                    debugLog('❌ Could not find form for submission monitoring');
                                }
                            }, 1000); // Wait 1 second to ensure form is ready

                        }, 100);
                        
                    } catch (error) {
                        debugError('❌ GALLERY UPDATE: Error setting gallery value:', error);
                    }
                },
                
                /**
                 * Trigger ACF change events properly
                 */
                triggerACFChange: function(fieldInstance) {
                    debugLog('ACF Bridge: Triggering change events');
                    
                    try {
                        // Find the field wrapper and input
                        var $fieldWrapper = $(fieldInstance).closest('[data-key][data-type="image"]');
                        if (!$fieldWrapper.length) {
                            $fieldWrapper = $(fieldInstance);
                        }
                        
                        var $input = $fieldWrapper.find('input[type="hidden"]');
                        if ($input && $input.length) {
                            // Dispatch input and change events
                            $input.trigger('input');
                            $input.trigger('change');
                            debugLog('ACF Bridge: Triggered input/change events on input:', $input);
                        } else {
                            debugLog('ACF Bridge: No input found for change events');
                        }
                        
                        // Try to trigger ACF's own field change event
                        var $uploader = $fieldWrapper.find('.acf-image-uploader');
                        if ($uploader.length) {
                            var acfField = $uploader.data('acf-field');
                            if (acfField && acfField.trigger) {
                                acfField.trigger('change');
                                debugLog('ACF Bridge: Triggered ACF field change event');
                            }
                        }
                        
                        // Trigger ACF action if available
                        if (typeof acf !== 'undefined' && acf.doAction) {
                            acf.doAction('change', fieldInstance);
                            debugLog('ACF Bridge: Triggered ACF change action');
                        }
                        
                        // Also trigger change on the field wrapper
                        $fieldWrapper.trigger('change');
                        debugLog('ACF Bridge: Triggered change on field wrapper');
                        
                    } catch (error) {
                        debugError('ACF Bridge: Error triggering change events:', error);
                    }
                }
            };

            debugLog('CLEAN TomatilloMediaFrame created successfully');
            
        } catch (error) {
            debugError('Error creating CLEAN TomatilloMediaFrame:', error);
            debugError('Stack trace:', error.stack);
        }
    }

    /**
     * Create clean modal HTML
     */
    function createModalHTML(options) {
        return `
            <div id="tomatillo-custom-modal" class="tomatillo-modal">
                <div class="tomatillo-modal-content">
                    <!-- Header -->
                    <div class="tomatillo-header">
                        <h2>${options.title || 'Select Media'}</h2>
                        <button id="tomatillo-close-modal" class="tomatillo-close-btn">×</button>
                    </div>
                    
                    <!-- Search and Filter Bar -->
                    <div class="tomatillo-controls">
                        <div class="tomatillo-search-container">
                            <input type="text" id="tomatillo-search" placeholder="Search media..." class="tomatillo-search">
                            <button id="tomatillo-clear-search" class="tomatillo-clear-search" style="display: none;">×</button>
                        </div>
                        <select id="tomatillo-filter" class="tomatillo-filter">
                            <option value="image">Images</option>
                            <option value="all">All Types</option>
                            <option value="video">Videos</option>
                            <option value="audio">Audio</option>
                            <option value="application">Documents</option>
                        </select>
                        <button id="tomatillo-upload-btn" class="tomatillo-upload-btn">Upload Files</button>
                        <input type="file" id="tomatillo-file-input" multiple accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar" style="display: none;">
                    </div>
                    
                <!-- Media Grid Container -->
                <div class="tomatillo-grid-container">
                    <div id="tomatillo-media-grid" class="tomatillo-masonry-grid">
                        <div class="tomatillo-loading">Loading media...</div>
                    </div>
                </div>
                
                <!-- Drag & Drop Overlay -->
                <div class="tomatillo-drag-drop-overlay" id="tomatillo-drag-drop-overlay">
                    <div class="tomatillo-drag-drop-message">
                        <div class="tomatillo-drag-drop-icon-large">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7,10 12,15 17,10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                        </div>
                        <h3>Drop files to upload</h3>
                        <p>Release to add <span id="tomatillo-file-count">0</span> files to your library</p>
                    </div>
                </div>
                    
                    <!-- Footer -->
                    <div class="tomatillo-footer">
                        <div class="tomatillo-footer-left">
                            <div id="tomatillo-selection-count" class="tomatillo-selection-count">No items selected</div>
                            <button id="tomatillo-load-more" class="tomatillo-btn tomatillo-btn-load-more" style="display: none; margin-left: 16px;">
                                Load More Images
                            </button>
                        </div>
                        <div class="tomatillo-actions">
                            <button id="tomatillo-cancel" class="tomatillo-btn tomatillo-btn-cancel">Cancel</button>
                            <button id="tomatillo-select" class="tomatillo-btn tomatillo-btn-select" disabled>Select</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Upload Progress Overlay -->
            <div class="tomatillo-upload-progress-overlay" id="tomatillo-upload-progress-overlay">
                <div class="tomatillo-upload-progress-modal">
                    <div class="tomatillo-upload-header">
                        <h3>Uploading Files</h3>
                        <button class="tomatillo-upload-cancel-btn" id="tomatillo-upload-cancel-btn">Cancel</button>
                    </div>
                    <div class="tomatillo-upload-progress-container">
                        <div class="tomatillo-upload-progress-bar">
                            <div class="tomatillo-upload-progress-fill" id="tomatillo-upload-progress-fill"></div>
                        </div>
                        <div class="tomatillo-upload-status" id="tomatillo-upload-status">Preparing upload...</div>
                    </div>
                    <div class="tomatillo-upload-files-list" id="tomatillo-upload-files-list"></div>
                </div>
            </div>
        `;
    }

    /**
     * Add responsive CSS
     */
    function addResponsiveCSS() {
        $('head').append(`
            <style>
                .tomatillo-modal {
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
                }
                
                .tomatillo-modal-content {
                    background: white;
                    border-radius: 8px;
                    width: 95vw;
                    height: 95vh;
                    max-height: 900px;
                    display: flex;
                    flex-direction: column;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                }
                
                .tomatillo-header {
                    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
                    color: white;
                    padding: 20px;
                    border-radius: 8px 8px 0 0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .tomatillo-header h2 {
                    margin: 0;
                    font-weight: 600;
                    color: white;
                }
                
                .tomatillo-close-btn {
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
                }
                
                .tomatillo-controls {
                    padding: 20px;
                    background: #f8f9fa;
                    border-bottom: 1px solid #e9ecef;
                    display: flex;
                    gap: 15px;
                    align-items: center;
                }
                
                .tomatillo-search-container {
                    flex: 1;
                    position: relative;
                    display: flex;
                    align-items: center;
                }
                
                .tomatillo-search {
                    flex: 1;
                    padding: 10px 15px;
                    padding-right: 40px; /* Make room for clear button */
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 14px;
                }
                
                .tomatillo-clear-search {
                    position: absolute;
                    right: 8px;
                    background: none;
                    border: none;
                    color: #666;
                    font-size: 18px;
                    cursor: pointer;
                    padding: 4px;
                    width: 24px;
                    height: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    transition: all 0.2s ease;
                }
                
                .tomatillo-clear-search:hover {
                    background: #e9ecef;
                    color: #333;
                }
                
                .tomatillo-filter {
                    padding: 10px 15px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    background: white;
                    font-size: 14px;
                }
                
                .tomatillo-grid-container {
                    flex: 1;
                    padding: 20px;
                    overflow-y: auto;
                    background: #f8f9fa;
                }
                
                .tomatillo-masonry-grid {
                    position: relative;
                    width: 100%;
                    padding: 0;
                }
                
                .tomatillo-grid-layout {
                    display: flex;
                    flex-wrap: wrap;
                    justify-content: flex-start;
                    align-items: flex-start;
                    gap: 16px;
                    padding: 20px;
                }
                
                .tomatillo-media-item {
                    background: white;
                    border-radius: 6px;
                    overflow: hidden;
                    cursor: pointer;
                    transition: transform 0.3s ease, box-shadow 0.3s ease;
                    min-width: 300px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
                    border: 2px solid transparent;
                }
                
                .tomatillo-masonry-grid .tomatillo-media-item {
                    position: absolute;
                }
                
                .tomatillo-grid-layout .tomatillo-media-item {
                    position: relative;
                    flex: 0 0 auto;
                }
                
                .tomatillo-media-item.selected {
                    border-color: #28a745;
                    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
                }
                
                .tomatillo-media-item.selected::after {
                    content: '✓';
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    background: #28a745;
                    color: white;
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-size: 16px;
                    z-index: 10;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                }
                
                /* Dim unselected images in single selection mode */
                .tomatillo-single-selection .tomatillo-media-item:not(.selected) {
                    opacity: 0.33 !important;
                }
                
                .tomatillo-single-selection .tomatillo-media-item.selected {
                    opacity: 1 !important;
                }
                
                .tomatillo-media-item img {
                    width: 100%;
                    height: auto;
                    display: block;
                }
                
                .tomatillo-hover-info {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: linear-gradient(to bottom, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 50%, transparent 100%);
                    color: white;
                    padding: 15px;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                    display: flex;
                    flex-direction: column;
                    justify-content: flex-start;
                }
                
                .tomatillo-hover-info .filename {
                    font-weight: 600;
                    font-size: 14px;
                    margin-bottom: 8px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                
                .tomatillo-hover-info .dimensions {
                    font-size: 12px;
                    opacity: 0.9;
                    margin-bottom: 4px;
                }
                
                .tomatillo-hover-info .details {
                    font-size: 11px;
                    opacity: 0.8;
                }
                
                .tomatillo-footer {
                    padding: 20px;
                    background: #f8f9fa;
                    border-top: 1px solid #e9ecef;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-radius: 0 0 8px 8px;
                }
                
                .tomatillo-footer-left {
                    display: flex;
                    align-items: center;
                }
                
                .tomatillo-selection-count {
                    color: #666;
                    font-size: 14px;
                }
                
                .tomatillo-btn-load-more {
                    background: #0073aa;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 13px;
                    font-weight: 500;
                    transition: background 0.2s;
                }
                
                .tomatillo-btn-load-more:hover {
                    background: #005177;
                }
                
                .tomatillo-btn-load-more:disabled {
                    background: #ccc;
                    cursor: not-allowed;
                    opacity: 0.6;
                }
                
                .tomatillo-no-results {
                    text-align: center;
                    padding: 60px 20px;
                    color: #666;
                    font-size: 16px;
                    font-style: italic;
                }
                
                .tomatillo-actions {
                    display: flex;
                    gap: 10px;
                }
                
                .tomatillo-btn {
                    border: none;
                    padding: 10px 20px;
                    border-radius: 6px;
                    font-weight: 600;
                    cursor: pointer;
                }
                
                .tomatillo-btn-cancel {
                    background: #6c757d;
                    color: white;
                    transition: all 0.3s ease;
                }
                
                .tomatillo-btn-cancel:hover {
                    background: #5a6268;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
                }
                
                .tomatillo-btn-select {
                    background: #667eea;
                    color: white;
                    opacity: 0.5;
                    transition: all 0.3s ease;
                }
                
                .tomatillo-btn-select:not(:disabled) {
                    opacity: 1;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                    transform: translateY(-1px);
                }
                
                .tomatillo-btn-select:not(:disabled):hover {
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
                    transform: translateY(-2px);
                }
                
                .tomatillo-btn-select:focus {
                    outline: 2px solid #0073aa;
                    outline-offset: 2px;
                    box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.3);
                }
                
                .tomatillo-loading {
                    text-align: center;
                    padding: 40px;
                    color: #666;
                    font-size: 16px;
                }
                
                /* Responsive */
                @media (max-width: 1200px) {
                    .tomatillo-media-item { min-width: 280px; }
                }
                
                @media (max-width: 768px) {
                    .tomatillo-media-item { min-width: 250px; }
                }
                
                @media (max-width: 600px) {
                    .tomatillo-media-item { min-width: 200px; }
                }
                
                @media (max-width: 480px) {
                    .tomatillo-media-item { min-width: 180px; }
                }
                
    /* Upload functionality styles */
    .tomatillo-upload-btn {
        background: #3b82f6;
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
    }
    
    .tomatillo-upload-btn:hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
    }
    
    /* Drag & Drop Overlay */
    .tomatillo-drag-drop-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(59, 130, 246, 0.1);
        backdrop-filter: blur(4px);
        z-index: 1000000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    
    .tomatillo-drag-drop-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    
    .tomatillo-drag-drop-message {
        background: white;
        border-radius: 16px;
        padding: 3rem;
        text-align: center;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border: 2px solid #3b82f6;
    }
    
    .tomatillo-drag-drop-icon-large {
        color: #3b82f6;
        margin-bottom: 1rem;
    }
    
    .tomatillo-drag-drop-message h3 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 0.5rem 0;
    }
    
    .tomatillo-drag-drop-message p {
        font-size: 1rem;
        color: #6b7280;
        margin: 0;
    }
    
    /* Upload Progress Overlay */
    .tomatillo-upload-progress-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(8px);
        z-index: 9999999;
        display: none;
        align-items: center;
        justify-content: center;
    }
    
    .tomatillo-upload-progress-overlay.active {
        display: flex;
        pointer-events: auto;
    }
    
    .tomatillo-upload-progress-overlay {
        pointer-events: none;
    }
    
    .tomatillo-upload-progress-modal {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        min-width: 400px;
        max-width: 600px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        pointer-events: auto;
        position: relative;
        z-index: 10000000;
    }
    
    .tomatillo-upload-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .tomatillo-upload-header h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }
    
    .tomatillo-upload-cancel-btn {
        background: #ef4444;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    
    .tomatillo-upload-cancel-btn:hover {
        background: #dc2626;
    }
    
    .tomatillo-upload-progress-container {
        margin-bottom: 1.5rem;
    }
    
    .tomatillo-upload-progress-bar {
        width: 100%;
        height: 8px;
        background: #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }
    
    .tomatillo-upload-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
        border-radius: 4px;
        transition: width 0.3s ease;
        width: 0%;
    }
    
    .tomatillo-upload-progress-fill.processing {
        animation: pulse 1.5s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    .tomatillo-upload-status {
        font-size: 0.875rem;
        color: #6b7280;
        text-align: center;
    }
    
    .tomatillo-upload-files-list {
        max-height: 200px;
        overflow-y: auto;
        border-top: 1px solid #e5e7eb;
        padding-top: 1rem;
    }
    
    .tomatillo-upload-file-item {
        display: flex;
        flex-direction: column;
        padding: 15px;
        border-bottom: 1px solid #e1e1e1;
        background: #fafafa;
        margin-bottom: 8px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .tomatillo-upload-file-item.completed {
        background: #f8f9fa;
        opacity: 0.8;
        border-left: 3px solid #28a745;
    }
    
    .tomatillo-upload-file-item.uploading {
        background: #e3f2fd !important;
        border-left: 3px solid #007cba;
        box-shadow: 0 2px 8px rgba(0, 124, 186, 0.2);
        transform: scale(1.02);
        border-radius: 8px !important;
    }
    
    .tomatillo-upload-file-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .tomatillo-upload-file-name {
        font-size: 0.875rem;
        color: #374151;
        font-weight: 500;
        margin-bottom: 8px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .tomatillo-upload-file-status {
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 12px;
        font-weight: 500;
        min-width: 60px;
        text-align: center;
        margin-bottom: 8px;
        align-self: flex-start;
    }
    
    .tomatillo-upload-file-status.pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .tomatillo-upload-file-status.uploading {
        background: #cce5ff;
        color: #004085;
        animation: pulse 1.5s ease-in-out infinite;
    }
    
    .tomatillo-upload-file-status.success {
        background: #d1edff;
        color: #004085;
    }
    
    .tomatillo-upload-file-status.error {
        background: #f8d7da;
        color: #721c24;
    }
    
    .tomatillo-upload-file-progress {
        width: 100%;
    }
    
    .tomatillo-upload-file-progress-bar {
        width: 100%;
        height: 4px;
        background: #e1e1e1;
        border-radius: 2px;
        overflow: hidden;
    }
    
    .tomatillo-upload-file-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #007cba 0%, #00a0d2 100%);
        border-radius: 2px;
        transition: width 0.3s ease;
        width: 0%;
    }
    
    /* Disable main modal interactions during upload */
    .tomatillo-modal.uploading-active {
        pointer-events: none;
        opacity: 0.7;
    }
    
    .tomatillo-modal.uploading-active .tomatillo-modal-content {
        pointer-events: none;
    }
                
                @media (max-width: 320px) {
                    .tomatillo-media-item { min-width: 160px; }
                }
            </style>
        `);
    }

    /**
     * Get current column heights from existing DOM elements
     */
    function getCurrentColumnHeights(columns, columnWidth, gap) {
        var columnHeights = new Array(columns).fill(0);
        
        $('.tomatillo-media-item').each(function() {
            var $item = $(this);
            var itemLeft = parseInt($item.css('left')) || 0;
            var itemTop = parseInt($item.css('top')) || 0;
            var itemHeight = this.offsetHeight || 0;
            
            // Calculate which column this item is in
            var columnIndex = Math.round(itemLeft / (columnWidth + gap));
            columnIndex = Math.max(0, Math.min(columns - 1, columnIndex)); // Clamp to valid range
            
            // Update column height
            var itemBottom = itemTop + itemHeight + gap;
            columnHeights[columnIndex] = Math.max(columnHeights[columnIndex], itemBottom);
        });
        
        debugLog('🔍 DEBUG: Current column heights from DOM:', columnHeights);
        return columnHeights;
    }

    /**
     * Get file icon based on file type
     */
    function getFileIcon(fileType) {
        var icons = {
            'audio': '🎵',
            'video': '🎬',
            'application': '📄',
            'text': '📝',
            'image': '🖼️'
        };
        
        // Check for specific subtypes
        if (fileType === 'audio') return '🎵';
        if (fileType === 'video') return '🎬';
        if (fileType === 'application') return '📄';
        if (fileType === 'text') return '📝';
        
        // Default fallback
        return '📄';
    }
    
    /**
     * Format file size in human readable format
     */
    function formatFileSize(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        
        var sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(1024));
        
        if (i >= sizes.length) i = sizes.length - 1;
        
        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * Pre-calculate masonry positions using known dimensions
     */
    function preCalculateMasonryPositions(mediaItems, optimizationDataArray, startColumnHeights) {
        debugLog('🔍 DEBUG: preCalculateMasonryPositions called with', mediaItems.length, 'items');
        debugLog('🔍 DEBUG: optimizationDataArray length:', optimizationDataArray ? optimizationDataArray.length : 'null');
        debugLog('🔍 DEBUG: startColumnHeights:', startColumnHeights);
        
        if (mediaItems.length === 0) {
            debugLog('🔍 DEBUG: No items to calculate positions for');
            return [];
        }
        
        // Get actual container width from DOM or modal
        var gridElement = document.getElementById('tomatillo-media-grid');
        var modalElement = document.getElementById('tomatillo-modal');
        var containerWidth = 1200; // fallback
        
        if (gridElement && gridElement.offsetWidth > 0) {
            containerWidth = gridElement.offsetWidth;
        } else if (modalElement) {
            // Calculate from modal width minus padding
            var modalWidth = modalElement.offsetWidth;
            var modalPadding = 40; // approximate padding
            containerWidth = modalWidth - modalPadding;
        }
        
        var gap = 16;
        
        debugLog('🔍 DEBUG: Grid element exists:', !!gridElement);
        debugLog('🔍 DEBUG: Grid element offsetWidth:', gridElement ? gridElement.offsetWidth : 'N/A');
        debugLog('🔍 DEBUG: Modal element exists:', !!modalElement);
        debugLog('🔍 DEBUG: Modal element offsetWidth:', modalElement ? modalElement.offsetWidth : 'N/A');
        debugLog('🔍 DEBUG: Using container width:', containerWidth, 'px');
        
        // Calculate number of columns
        var columns = 4; // Increased from 3 to 4 for better modal filling
        if (containerWidth < 768) {
            columns = 2;
        } else if (containerWidth < 1200) {
            columns = 3;
        } else if (containerWidth < 1600) {
            columns = 4;
        } else {
            columns = 5; // Even more columns for very wide screens
        }
        
        // Calculate column width
        var columnWidth = (containerWidth - (gap * (columns - 1))) / columns;
        
        debugLog('🔍 DEBUG: Container width:', containerWidth, 'Columns:', columns, 'Column width:', columnWidth);
        
        // Initialize column heights
        var columnHeights;
        if (startColumnHeights && Array.isArray(startColumnHeights)) {
            columnHeights = startColumnHeights.slice(); // Copy the array
            debugLog('🔍 DEBUG: Using provided startColumnHeights:', columnHeights);
        } else {
            columnHeights = new Array(columns).fill(0);
            debugLog('🔍 DEBUG: Starting with fresh column heights:', columnHeights);
        }
        
        var positions = [];
        
        // Pre-calculate positions for each item
        mediaItems.forEach(function(item, index) {
            var optimizationData = optimizationDataArray[index];
            
            // Get hiRes image to get correct dimensions
            var hiResImage = getHiResImageWithOptimization(item, optimizationData);
            
            // Use dimensions from hiRes image (which has correct dimensions)
            var width = hiResImage.width;
            var height = hiResImage.height;
            
            // Validate dimensions
            if (!width || !height || width <= 0 || height <= 0) {
                debugError('🚨 INVALID DIMENSIONS for item', index, 'ID:', item.id, 'Width:', width, 'Height:', height);
                debugError('🚨 HiRes image:', hiResImage);
                debugError('🚨 Item:', item);
                debugError('🚨 Optimization data:', optimizationData);
                // Use fallback dimensions
                width = width || 400;
                height = height || 300;
            }
            
            debugLog('🔍 DEBUG: Item', index, 'ID:', item.id, 'Dimensions:', width, 'x', height);
            
            // Calculate aspect ratio and scaled dimensions
            var aspectRatio = height / width;
            var scaledWidth = columnWidth;
            var scaledHeight = columnWidth * aspectRatio;
            
            debugLog('🔍 DEBUG: Aspect ratio:', aspectRatio, 'Scaled:', scaledWidth, 'x', scaledHeight);
            
            // Find shortest column
            var shortestColumnIndex = columnHeights.indexOf(Math.min.apply(Math, columnHeights));
            
            // Calculate position
            var left = shortestColumnIndex * (columnWidth + gap);
            var top = columnHeights[shortestColumnIndex];
            
            debugLog('🔍 DEBUG: Column heights:', columnHeights, 'Shortest:', shortestColumnIndex);
            debugLog('🔍 DEBUG: Position:', left, ',', top);
            
            positions.push({
                left: left,
                top: top,
                width: scaledWidth,
                height: scaledHeight
            });
            
            // Update column height
            columnHeights[shortestColumnIndex] += scaledHeight + gap;
            
            debugLog('🔍 DEBUG: Updated column heights:', columnHeights);
        });
        
        debugLog('🎨 Pre-calculated masonry positions for', positions.length, 'items');
        debugLog('🔍 DEBUG: Final positions:', positions);
        return positions;
    }

    /**
     * Layout masonry grid using absolute positioning (same as main Gallery)
     */
    function layoutMasonry() {
        var grid = document.getElementById('tomatillo-media-grid');
        if (!grid) return;
        
        var items = Array.from(grid.children).filter(function(child) {
            return child.style.display !== 'none';
        });
        
        if (items.length === 0) return;
        
        // Get container width
        var containerWidth = grid.offsetWidth;
        var gap = 16; // Increased gap for better spacing
        
        // Calculate number of columns based on screen size
        var columns = 4; // Increased from 3 to 4 for better modal filling
        if (containerWidth < 768) {
            columns = 2;
        } else if (containerWidth < 1200) {
            columns = 3;
        } else if (containerWidth < 1600) {
            columns = 4;
        } else {
            columns = 5; // Even more columns for very wide screens
        }
        
        // Calculate column width
        var columnWidth = (containerWidth - (gap * (columns - 1))) / columns;
        
        // Initialize column heights
        var columnHeights = new Array(columns).fill(0);
        
        // Position each item
        items.forEach(function(item, index) {
            // Reset any previous positioning
            item.style.position = 'absolute';
            item.style.left = '0px';
            item.style.top = '0px';
            
            // Set max width to column width, but let height adjust naturally
            item.style.maxWidth = columnWidth + 'px';
            item.style.width = 'auto';
            
            // Find the shortest column
            var shortestColumnIndex = columnHeights.indexOf(Math.min.apply(Math, columnHeights));
            
            // Position the item
            var left = shortestColumnIndex * (columnWidth + gap);
            var top = columnHeights[shortestColumnIndex];
            
            item.style.left = left + 'px';
            item.style.top = top + 'px';
            
            // Update column height
            columnHeights[shortestColumnIndex] += item.offsetHeight + gap;
        });
        
        // Set container height
        grid.style.height = Math.max.apply(Math, columnHeights) + 'px';
        
        debugLog('🎨 Masonry layout applied:', items.length, 'items in', columns, 'columns');
    }

    /**
     * Wait for images to load before applying masonry layout
     */
    function waitForImagesAndLayout() {
        var grid = document.getElementById('tomatillo-media-grid');
        if (!grid) return;
        
        var images = Array.from(grid.querySelectorAll('img'));
        var loadedImages = images.filter(function(img) {
            return img.complete;
        });
        
        if (loadedImages.length === images.length) {
            layoutMasonry();
        } else {
            // Wait for remaining images
            images.forEach(function(img) {
                if (!img.complete) {
                    img.onload = function() {
                        if (Array.from(grid.querySelectorAll('img')).every(function(i) {
                            return i.complete;
                        })) {
                            setTimeout(layoutMasonry, 50);
                        }
                    };
                }
            });
        }
    }

    /**
     * Initialize the media grid with real WordPress media
     */
    function initializeMediaGrid(options) {
        debugLog('Initializing media grid...');
        
        // Clear any existing selections to start fresh
        selectedItems = [];
        $('.tomatillo-media-item').removeClass('selected');
        $('#tomatillo-media-grid').removeClass('tomatillo-single-selection');
        
        // Reset the server flag to true (assume there might be more until proven otherwise)
        hasMoreItemsOnServer = true;
        
        // Check if we have preloaded media
        debugLog('🔍 Checking for preloaded media...');
        debugLog('🔍 window.TomatilloBackgroundLoader exists:', !!window.TomatilloBackgroundLoader);
        
        if (window.TomatilloBackgroundLoader && window.TomatilloBackgroundLoader.isMediaPreloaded()) {
            var preloadStartTime = performance.now();
            debugLog('🚀 Using preloaded media data - INSTANT LOADING!');
            var preloadedData = window.TomatilloBackgroundLoader.getPreloadedMedia();
            debugLog('🔍 Preloaded data:', preloadedData);
            currentMediaItems = preloadedData.items;
            currentOptimizationData = preloadedData.optimizationData;
            
            // Debug: Check what types we actually have
            debugLog('🔍 DEBUG: All media item types:', currentMediaItems.map(function(item) {
                return {id: item.id, type: item.type, mime: item.mime, filename: item.filename};
            }));
            
            // Debug: Check for duplicates in source data
            var allIds = currentMediaItems.map(function(item) { return item.id; });
            var uniqueIds = [...new Set(allIds)];
            if (allIds.length !== uniqueIds.length) {
                debugError('🚨 DUPLICATES IN SOURCE DATA!', allIds.length, 'items,', uniqueIds.length, 'unique');
                var duplicates = allIds.filter(function(id, index) { return allIds.indexOf(id) !== index; });
                debugError('🚨 Duplicate IDs in source:', duplicates);
                
                // Remove duplicates from source data
                debugLog('🔧 Removing duplicates from source data...');
                var seenIds = new Set();
                var deduplicatedItems = [];
                var deduplicatedOptimizationData = [];
                
                currentMediaItems.forEach(function(item, index) {
                    if (!seenIds.has(item.id)) {
                        seenIds.add(item.id);
                        deduplicatedItems.push(item);
                        if (currentOptimizationData[index]) {
                            deduplicatedOptimizationData.push(currentOptimizationData[index]);
                        }
                    }
                });
                
                debugLog('🔧 Deduplicated:', currentMediaItems.length, '→', deduplicatedItems.length);
                currentMediaItems = deduplicatedItems;
                currentOptimizationData = deduplicatedOptimizationData;
            }
            
            // Apply current filter (defaults to "image" if not set)
            var currentFilter = $('#tomatillo-filter').val() || 'image';
            debugLog('🔍 Applying initial filter:', currentFilter);
            
            var filteredItems = currentMediaItems.filter(function(item) {
                if (currentFilter === 'all') return true;
                
                if (currentFilter === 'image') {
                    var isImage = item.type === 'image' || (item.mime && item.mime.startsWith('image/'));
                    debugLog('🔍 DEBUG: Item', item.id, 'type:', item.type, 'mime:', item.mime, 'isImage:', isImage);
                    return isImage;
                } else if (currentFilter === 'video') {
                    return item.type === 'video' || (item.mime && item.mime.startsWith('video/'));
                } else if (currentFilter === 'audio') {
                    return item.type === 'audio' || (item.mime && item.mime.startsWith('audio/'));
                } else if (currentFilter === 'application') {
                    return item.type === 'application' || (item.mime && item.mime.startsWith('application/'));
                }
                
                return item.type === currentFilter;
            });
            
            var filteredOptimizationData = currentOptimizationData.filter(function(data, index) {
                if (currentFilter === 'all') return true;
                var item = currentMediaItems[index];
                
                if (currentFilter === 'image') {
                    return item.type === 'image' || (item.mime && item.mime.startsWith('image/'));
                } else if (currentFilter === 'video') {
                    return item.type === 'video' || (item.mime && item.mime.startsWith('video/'));
                } else if (currentFilter === 'audio') {
                    return item.type === 'audio' || (item.mime && item.mime.startsWith('audio/'));
                } else if (currentFilter === 'application') {
                    return item.type === 'application' || (item.mime && item.mime.startsWith('application/'));
                }
                
                return item.type === currentFilter;
            });
            
            debugLog('🔍 Filtered to', currentFilter + ':', filteredItems.length, 'of', currentMediaItems.length);
            
            // Render only initial batch for better performance and to enable infinite scroll
            var initialBatchSize = window.tomatilloSettings ? (window.tomatilloSettings.infinite_scroll_batch || 100) : 100;
            var initialItems = filteredItems.slice(0, initialBatchSize);
            var initialOptimizationData = filteredOptimizationData.slice(0, initialBatchSize);
            
            debugLog('');
            debugLog('┌─────────────────────────────────────────────┐');
            debugLog('│ 🎨 INITIAL RENDER                          │');
            debugLog('├─────────────────────────────────────────────┤');
            debugLog('│ Batch size:             ', initialBatchSize.toString().padEnd(20), '│');
            debugLog('│ Items to render:        ', initialItems.length.toString().padEnd(20), '│');
            debugLog('│ Total available:        ', filteredItems.length.toString().padEnd(20), '│');
            debugLog('│ More on server?:        ', (hasMoreItemsOnServer ? 'YES' : 'NO').padEnd(20), '│');
            debugLog('└─────────────────────────────────────────────┘');
            debugLog('');
            
            // Render immediately with initial batch
            renderMediaGridWithOptimization(initialItems, initialOptimizationData, options, true);
            
            // Initialize rendered count to actual rendered items, not total
            renderedItemsCount = initialItems.length;
            
            debugLog('✅ Initial render complete:', renderedItemsCount, 'items displayed');
            
            // Show/hide "Load More" button based on whether there are more items
            updateLoadMoreButton(filteredItems.length, renderedItemsCount, hasMoreItemsOnServer);
            
            // Setup infinite scroll after rendering
            setupInfiniteScroll(options);
            
            // Start loading more images in background for infinite scroll
            loadMoreImagesInBackground(options);
            
            // Trigger immediate check for infinite scroll (in case initial batch doesn't fill screen)
            setTimeout(function() {
                debugLog('🧪 Checking if more items need to load immediately...');
                debugLog('🧪 Total filtered items:', filteredItems.length);
                debugLog('🧪 Rendered items:', renderedItemsCount);
                
                // Check if modal content is not scrollable and we have more items
                var $gridContainer = $('.tomatillo-grid-container');
                if ($gridContainer.length > 0 && $gridContainer[0]) {
                    var scrollHeight = $gridContainer[0].scrollHeight;
                    var clientHeight = $gridContainer[0].clientHeight;
                    var hasScroll = scrollHeight > clientHeight;
                    
                    debugLog('🧪 Modal scrollable?', hasScroll, '(scrollHeight:', scrollHeight, 'clientHeight:', clientHeight, ')');
                    
                    // If not scrollable and we have more items, enable load more button
                    if (!hasScroll && filteredItems.length > renderedItemsCount) {
                        debugLog('🧪 Modal not scrollable - Load More button visible for remaining items');
                    }
                }
            }, 100);
            
            var preloadEndTime = performance.now();
            debugLog('🚀 Modal opened in', (preloadEndTime - preloadStartTime).toFixed(2), 'ms using preloaded data!');
            debugLog('🚀 Total media items available:', currentMediaItems.length);
            debugLog('🚀 Rendered items count:', renderedItemsCount);
            return;
        }
        
        debugLog('🚀 No preloaded data available, falling back to server fetch');
        debugLog('🔍 Background loader status:', window.TomatilloBackgroundLoader ? 'exists' : 'missing');
        if (window.TomatilloBackgroundLoader) {
            debugLog('🔍 isMediaPreloaded():', window.TomatilloBackgroundLoader.isMediaPreloaded());
        }
        
        // Fallback to regular loading
        loadMediaFromServer(options);
    }
    
    /**
     * Load media from server (fallback method)
     */
    function loadMediaFromServer(options) {
        // Use WordPress AJAX to fetch media directly
        // Match the preload count to ensure consistency
        var initialBatchSize = window.tomatilloSettings ? (window.tomatilloSettings.infinite_scroll_batch || 100) : 100;
        
        var data = {
            action: 'query-attachments',
            query: {
                posts_per_page: initialBatchSize,
                post_status: 'inherit'
                // Remove post_mime_type restriction to load all file types
            }
        };
        
        debugLog('Fetching media with data:', data);
        
        // Make AJAX request to WordPress
        $.post(ajaxurl, data)
            .done(function(response) {
                debugLog('Raw WordPress response:', response);
                debugLog('Response type:', typeof response);
                debugLog('Response length:', response ? response.length : 'undefined');
                
                // Handle different response formats
                var mediaItems = [];
                if (Array.isArray(response)) {
                    mediaItems = response;
                } else if (response && response.data && Array.isArray(response.data)) {
                    mediaItems = response.data;
                } else if (response && response.attachments && Array.isArray(response.attachments)) {
                    mediaItems = response.attachments;
                } else {
                    debugError('Unexpected response format:', response);
                    mediaItems = [];
                }
                
                debugLog('Media fetched successfully, count:', mediaItems.length);
                
                // Store media items globally immediately
                currentMediaItems = mediaItems;
                debugLog('📦 Stored mediaItems globally:', currentMediaItems.length);
                
                // Debug: Check what types we actually have
                debugLog('🔍 DEBUG: All media item types:', currentMediaItems.map(function(item) {
                    return {id: item.id, type: item.type, mime: item.mime, filename: item.filename};
                }));
                
                // Debug: Check for duplicates in source data
                var allIds = currentMediaItems.map(function(item) { return item.id; });
                var uniqueIds = [...new Set(allIds)];
                if (allIds.length !== uniqueIds.length) {
                    debugError('🚨 DUPLICATES IN SOURCE DATA!', allIds.length, 'items,', uniqueIds.length, 'unique');
                    var duplicates = allIds.filter(function(id, index) { return allIds.indexOf(id) !== index; });
                    debugError('🚨 Duplicate IDs in source:', duplicates);
                    
                    // Remove duplicates from source data
                    debugLog('🔧 Removing duplicates from source data...');
                    var seenIds = new Set();
                    var deduplicatedItems = [];
                    
                    currentMediaItems.forEach(function(item) {
                        if (!seenIds.has(item.id)) {
                            seenIds.add(item.id);
                            deduplicatedItems.push(item);
                        }
                    });
                    
                    debugLog('🔧 Deduplicated:', currentMediaItems.length, '→', deduplicatedItems.length);
                    currentMediaItems = deduplicatedItems;
                }
                
                // Apply current filter (defaults to "image" if not set)
                var currentFilter = $('#tomatillo-filter').val() || 'image';
                debugLog('🔍 Applying initial filter:', currentFilter);
                
                var filteredItems = currentMediaItems.filter(function(item) {
                    if (currentFilter === 'all') return true;
                    
                    if (currentFilter === 'image') {
                        var isImage = item.type === 'image' || item.mime && item.mime.startsWith('image/');
                        debugLog('🔍 DEBUG: Item', item.id, 'type:', item.type, 'mime:', item.mime, 'isImage:', isImage);
                        return isImage;
                    } else if (currentFilter === 'video') {
                        return item.type === 'video' || item.mime && item.mime.startsWith('video/');
                    } else if (currentFilter === 'audio') {
                        return item.type === 'audio' || item.mime && item.mime.startsWith('audio/');
                    } else if (currentFilter === 'application') {
                        return item.type === 'application' || item.mime && item.mime.startsWith('application/');
                    }
                    
                    return item.type === currentFilter;
                });
                
                debugLog('🔍 Filtered to', currentFilter + ':', filteredItems.length, 'of', currentMediaItems.length);
                
                renderMediaGrid(filteredItems, options);
            })
            .fail(function(xhr, status, error) {
                debugError('Error fetching media:', error);
                debugError('Response:', xhr.responseText);
                // Fallback to empty grid
                $('#tomatillo-media-grid').html(`
                    <div class="tomatillo-loading" style="color: #dc3545;">
                        Failed to load media. Please try again.
                    </div>
                `);
            });
    }

    /**
     * Get optimization data for an image from Media Studio database
     */
    function getOptimizationData(imageId) {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: ajaxurl,
                type: 'GET',
                data: {
                    action: 'tomatillo_get_image_data',
                    image_id: imageId,
                    nonce: tomatillo_nonce || 'test'
                },
                success: function(response) {
                    debugLog('📥 AJAX response for image', imageId, ':', response);
                    debugLog('📥 AJAX response.data keys:', response.data ? Object.keys(response.data) : 'no data');
                    debugLog('📥 AJAX response.data.avif_url:', response.data ? response.data.avif_url : 'no avif_url');
                    debugLog('📥 AJAX response.data.webp_url:', response.data ? response.data.webp_url : 'no webp_url');
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        debugLog('❌ AJAX failed for image', imageId, ':', response.data);
                        reject(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    debugLog('💥 AJAX error for image', imageId, ':', error);
                    debugLog('💥 XHR response:', xhr.responseText);
                    reject(error);
                }
            });
        });
    }

    /**
     * Get HI-RES image using Media Studio optimization data
     */
    function getHiResImageWithOptimization(item, optimizationData) {
        // Debug: Check optimization data availability
        if (!optimizationData) {
            debugLog('❌ No optimization data for item', item.id, '- using fallback');
            return getHiResImage(item);
        }
        
        // Check for AVIF first (smallest file size)
        if (optimizationData.avif_url) {
            return {
                url: optimizationData.avif_url,
                width: item.width,
                height: item.height,
                format: 'AVIF',
                sizeName: 'avif',
                filesize: optimizationData.avif_file_size || 'Unknown size'
            };
        }
        
        // Check for WebP second
        if (optimizationData.webp_url) {
            return {
                url: optimizationData.webp_url,
                width: item.width,
                height: item.height,
                format: 'WebP',
                sizeName: 'webp',
                filesize: optimizationData.webp_file_size || 'Unknown size'
            };
        }
        
        // Fall back to regular method
        return getHiResImage(item);
    }
    
    /**
     * Format bytes to human readable format
     */
    function formatBytes(bytes) {
        if (!bytes || bytes === 0) return 'Unknown size';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    /**
     * Get HI-RES image using Media Studio logic (AVIF/WebP priority)
     */
    function getHiResImage(item) {
        debugLog('🚀 getHiResImage called for item:', item.id);
        // Use Media Studio's get_best_optimized_image_url logic
        // For now, use WordPress scaled as fallback, but prioritize AVIF/WebP when available
        
        debugLog('=== getHiResImage DEBUG ===');
        debugLog('Item object keys:', Object.keys(item));
        debugLog('Item filesizeHumanReadable:', item.filesizeHumanReadable);
        debugLog('Item filesizeInBytes:', item.filesizeInBytes);
        debugLog('Sizes object:', item.sizes);
        debugLog('Sizes keys:', Object.keys(item.sizes || {}));
        debugLog('===========================');
        
        var sizes = item.sizes || {};
        var originalUrl = item.url;
        
        // Check for AVIF/WebP in sizes first (Media Studio might have added these)
        var avifUrl = null;
        var webpUrl = null;
        
        // Look for AVIF/WebP versions in sizes
        for (var sizeName in sizes) {
            debugLog('Checking size:', sizeName, sizes[sizeName]);
            if (sizeName.includes('avif') && sizes[sizeName].url) {
                avifUrl = sizes[sizeName].url;
                debugLog('Found AVIF in sizes:', sizeName, avifUrl);
            }
            if (sizeName.includes('webp') && sizes[sizeName].url) {
                webpUrl = sizes[sizeName].url;
                debugLog('Found WebP in sizes:', sizeName, webpUrl);
            }
        }
        
        debugLog('AVIF URL found:', avifUrl);
        debugLog('WebP URL found:', webpUrl);
        
        // Priority: AVIF > WebP > Scaled > Original
        if (avifUrl) {
            debugLog('Using AVIF image:', avifUrl);
            // Find the AVIF size object to get its dimensions
            var avifSize = null;
            for (var sizeName in sizes) {
                if (sizeName.includes('avif') && sizes[sizeName].url === avifUrl) {
                    avifSize = sizes[sizeName];
                    debugLog('AVIF size object:', avifSize);
            debugLog('AVIF size object keys:', Object.keys(avifSize || {}));
            debugLog('AVIF filesizeHumanReadable:', avifSize ? avifSize.filesizeHumanReadable : 'not found');
                    break;
                }
            }
            return {
                url: avifUrl,
                width: avifSize ? avifSize.width : item.width,
                height: avifSize ? avifSize.height : item.height,
                format: 'AVIF',
                sizeName: avifSize ? Object.keys(sizes).find(name => name.includes('avif')) : 'avif',
                filesize: avifSize ? avifSize.filesizeHumanReadable : item.filesizeHumanReadable || 'Unknown size'
            };
        }
        
        if (webpUrl) {
            debugLog('Using WebP image:', webpUrl);
            // Find the WebP size object to get its dimensions
            var webpSize = null;
            for (var sizeName in sizes) {
                if (sizeName.includes('webp') && sizes[sizeName].url === webpUrl) {
                    webpSize = sizes[sizeName];
                    debugLog('WebP size object:', webpSize);
            debugLog('WebP size object keys:', Object.keys(webpSize || {}));
            debugLog('WebP filesizeHumanReadable:', webpSize ? webpSize.filesizeHumanReadable : 'not found');
                    break;
                }
            }
            return {
                url: webpUrl,
                width: webpSize ? webpSize.width : item.width,
                height: webpSize ? webpSize.height : item.height,
                format: 'WebP',
                sizeName: webpSize ? Object.keys(sizes).find(name => name.includes('webp')) : 'webp',
                filesize: webpSize ? webpSize.filesizeHumanReadable : item.filesizeHumanReadable || 'Unknown size'
            };
        }
        
        // Use scaled version if available
        if (sizes.scaled && sizes.scaled.url) {
            debugLog('Using scaled image:', sizes.scaled.url);
            return {
                url: sizes.scaled.url,
                width: sizes.scaled.width,
                height: sizes.scaled.height,
                format: 'JPEG',
                sizeName: 'scaled',
                filesize: sizes.scaled.filesizeHumanReadable || item.filesizeHumanReadable || 'Unknown size'
            };
        }
        
        // Fallback to original
        debugLog('Using original image:', originalUrl);
        debugLog('Full size object:', sizes.full);
        return {
            url: originalUrl,
            width: item.width,
            height: item.height,
            format: 'JPEG',
            sizeName: 'scaled',
            filesize: sizes.full ? sizes.full.filesizeHumanReadable || item.filesizeHumanReadable : item.filesizeHumanReadable || 'Unknown size'
        };
    }
    
    /**
     * Clean filename by removing -scaled suffix and other WordPress suffixes
     */
    function cleanFilename(filename) {
        if (!filename) return 'Unknown';
        debugLog('cleanFilename input:', filename);
        
        // Remove WordPress suffixes: -scaled, -1024x683, etc.
        var cleaned = filename
            .replace(/-scaled\.(jpg|jpeg|png|gif|webp|avif)$/i, '')  // Remove -scaled.ext
            .replace(/-\d+x\d+\.(jpg|jpeg|png|gif|webp|avif)$/i, '') // Remove -1024x683.ext
            .replace(/\.(jpg|jpeg|png|gif|webp|avif)$/i, '');        // Remove any remaining .ext
            
        debugLog('cleanFilename output:', cleaned);
        return cleaned;
    }

    /**
     * Render the media grid with real WordPress media
     */
    function renderMediaGrid(mediaItems, options) {
        // Store media items globally for access in event handlers
        currentMediaItems = mediaItems;
        debugLog('Rendering media grid with', mediaItems ? mediaItems.length : 'undefined', 'items');
        
        // Safety check
        if (!Array.isArray(mediaItems)) {
            debugError('mediaItems is not an array:', mediaItems);
            $('#tomatillo-media-grid').html(`
                <div class="tomatillo-loading" style="color: #dc3545;">
                    Error: Invalid media data format
                </div>
            `);
            return;
        }
        
        var gridHtml = '';
        
        // Fetch optimization data for all images first
        debugLog('🔍 Fetching optimization data for', mediaItems.length, 'items');
        var optimizationPromises = mediaItems.map(function(item) {
            return getOptimizationData(item.id).then(function(data) {
                return data;
            }).catch(function(error) {
                return null; // Return null if no optimization data
            });
        });
        
        Promise.all(optimizationPromises).then(function(optimizationDataArray) {
            // Store optimization data globally for search functionality
            currentOptimizationData = optimizationDataArray;
            
            // Pre-calculate masonry positions for instant layout
            var preCalculatedPositions = preCalculateMasonryPositions(mediaItems, optimizationDataArray);
            
            var gridHtml = '';
            
            mediaItems.forEach(function(item, index) {
                var optimizationData = optimizationDataArray[index];
                debugLog('🖼️ Processing item', item.id, 'with optimization data:', optimizationData);
                var hiResImage = getHiResImageWithOptimization(item, optimizationData);
                debugLog('🖼️ Final hiResImage for item', item.id, ':', hiResImage);
            
            var originalFilename = item.filename || item.title || 'Unknown';
            var filename = cleanFilename(originalFilename);
            debugLog('=== FILENAME DEBUG ===');
            debugLog('Item object:', item);
            debugLog('item.filename:', item.filename);
            debugLog('item.title:', item.title);
            debugLog('Original filename:', originalFilename);
            debugLog('Cleaned filename:', filename);
            debugLog('========================');
            var type = item.type || 'unknown';
            
            // Calculate aspect ratio for masonry
            var aspectRatio = hiResImage.width && hiResImage.height ? 
                hiResImage.height / hiResImage.width : 1;
            var isVertical = aspectRatio > 1.2;
            var isHorizontal = aspectRatio < 0.8;
            var orientation = isVertical ? 'Portrait' : isHorizontal ? 'Landscape' : 'Square';
            
            gridHtml += `
                <div class="tomatillo-media-item" data-id="${item.id}">
                    ${type === 'image' ? 
                        `<img src="${hiResImage.url}" alt="${filename}" loading="lazy">` :
                        `<div style="width: 100%; height: 200px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #666;">📄</div>`
                    }
                    
                    <div class="tomatillo-hover-info">
                        <div class="filename">${filename}</div>
                        <div class="dimensions">${orientation} • ${hiResImage.width}×${hiResImage.height}</div>
                        <div class="details">${hiResImage.filesize} • ${hiResImage.format}</div>
                    </div>
                </div>
            `;
        });
        
        $('#tomatillo-media-grid').html(gridHtml);
        
        // Add hover effects (only for masonry layout - images)
        if (layoutType === 'masonry') {
            // Remove any existing hover effects first
            $('.tomatillo-media-item').off('mouseenter mouseleave');
            
            $('.tomatillo-media-item').hover(
                function() {
                    if (!$(this).hasClass('selected')) {
                        $(this).css({
                            'transform': 'translateY(-2px)',
                            'box-shadow': '0 4px 12px rgba(0,0,0,0.12)'
                        });
                    }
                    $(this).find('.tomatillo-hover-info').css('opacity', '1');
                },
                function() {
                    if (!$(this).hasClass('selected')) {
                        $(this).css({
                            'transform': 'translateY(0)',
                            'box-shadow': '0 1px 3px rgba(0,0,0,0.08)'
                        });
                    } else {
                        // For selected items, maintain selection styles
                        $(this).css({
                            'transform': 'translateY(0)',
                            'box-shadow': '0 4px 12px rgba(40, 167, 69, 0.3)'
                        });
                    }
                    $(this).find('.tomatillo-hover-info').css('opacity', '0');
                }
            );
        }
        
        debugLog('Media grid rendered successfully');
        
        // Setup infinite scroll if enabled
        setupInfiniteScroll(options);
        }).catch(function(error) {
            debugError('Error fetching optimization data:', error);
            // Fallback to rendering without optimization data
            renderMediaGridFallback(mediaItems, options);
        });
    }
    
    /**
     * Fallback rendering without optimization data
     */
    function renderMediaGridFallback(mediaItems, options) {
        // Store media items globally for access in event handlers
        currentMediaItems = mediaItems;
        var gridHtml = '';
        
        mediaItems.forEach(function(item) {
            // Get HI-RES image using Media Studio logic
            var hiResImage = getHiResImage(item);
            
            var originalFilename = item.filename || item.title || 'Unknown';
            var filename = cleanFilename(originalFilename);
            debugLog('Original filename:', originalFilename, 'Cleaned:', filename);
            var type = item.type || 'unknown';
            
            // Calculate aspect ratio for masonry
            var aspectRatio = hiResImage.width && hiResImage.height ? 
                hiResImage.height / hiResImage.width : 1;
            var isVertical = aspectRatio > 1.2;
            var isHorizontal = aspectRatio < 0.8;
            var orientation = isVertical ? 'Portrait' : isHorizontal ? 'Landscape' : 'Square';
            
            gridHtml += `
                <div class="tomatillo-media-item" data-id="${item.id}">
                    ${type === 'image' ? 
                        `<img src="${hiResImage.url}" alt="${filename}" loading="lazy">` :
                        `<div style="width: 100%; height: 200px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #666;">📄</div>`
                    }
                    
                    <div class="tomatillo-hover-info">
                        <div class="filename">${filename}</div>
                        <div class="dimensions">${orientation} • ${hiResImage.width}×${hiResImage.height}</div>
                        <div class="details">${hiResImage.filesize} • ${hiResImage.format}</div>
                    </div>
                </div>
            `;
        });
        
        $('#tomatillo-media-grid').html(gridHtml);
        debugLog('Media grid rendered successfully (fallback)');
    }

    /**
     * Setup event handlers for the modal
     */
    function setupEventHandlers(options) {
        
        // Handle close button
        $('#tomatillo-close-modal').off('click').on('click', function() {
            debugLog('Close button clicked');
            cleanupModal();
        });
        
        // Handle cancel button
        $('#tomatillo-cancel').off('click').on('click', function() {
            debugLog('Cancel button clicked');
            cleanupModal();
        });
        
        // Handle media item selection
        $(document).off('click.tomatillo-media').on('click.tomatillo-media', '.tomatillo-media-item', function() {
            debugLog('🎯 Media item clicked - checking if selection works');
            var itemId = $(this).data('id');
            var $item = $(this);
            debugLog('🎯 Item ID:', itemId, 'Item element:', $item);
            debugLog('🎯 Current selectedItems before:', selectedItems);
            debugLog('🎯 Options multiple:', options.multiple);
            
            if (options.multiple) {
                // Multiple selection
                debugLog('Multiple selection mode');
                $('#tomatillo-media-grid').removeClass('tomatillo-single-selection');
                
                if ($item.hasClass('selected')) {
                    debugLog('Deselecting item in multiple mode');
                    $item.removeClass('selected').css('border-color', 'transparent');
                    selectedItems = selectedItems.filter(id => id !== itemId);
                } else {
                    debugLog('Selecting item in multiple mode');
                    $item.addClass('selected');
                    selectedItems.push(itemId);
                }
            } else {
                // Single selection - check if clicking the same item again
                if ($item.hasClass('selected')) {
                    // Deselect - remove dimming and clear selection
                    debugLog('Deselecting item in single mode');
                    $('#tomatillo-media-grid').removeClass('tomatillo-single-selection');
                    $item.removeClass('selected').css('border-color', 'transparent');
                    selectedItems = [];
                } else {
                    // Select - add dimming class and clear all other selections
                    debugLog('Selecting item in single mode');
                    $('#tomatillo-media-grid').addClass('tomatillo-single-selection');
                    $('.tomatillo-media-item').removeClass('selected').css('border-color', 'transparent');
                    $item.addClass('selected');
                    selectedItems = [itemId];
                }
            }
            
            // Update selection count and button state
            updateSelectionUI(selectedItems.length, options);
            
            // Debug: Log current selection state
            debugLog('🎯 Selection state after click:');
            debugLog('🎯 selectedItems array:', selectedItems);
            debugLog('🎯 Items with selected class:', $('.tomatillo-media-item.selected').length);
            debugLog('🎯 Selected item IDs:', $('.tomatillo-media-item.selected').map(function() { return $(this).data('id'); }).get());
        });
        
        // Function to handle select action (used by both click and keyboard)
        function handleSelectAction() {
            if (selectedItems.length > 0) {
                debugLog('Select action triggered, selected items:', selectedItems);
                debugLog('Current media items available:', currentMediaItems.length);
                
                // Create selection data from our media items
                var selection = selectedItems.map(function(id) {
                    // Find the media item from our fetched data
                    var item = currentMediaItems.find(function(item) {
                        return item.id == id;
                    });
                    
                    if (item) {
                        // Return WordPress-compatible format
                        return {
                            id: item.id,
                            url: item.url,
                            title: item.title || item.filename,
                            filename: item.filename,
                            alt: item.alt || item.filename,
                            description: item.description || '',
                            caption: item.caption || '',
                            mime: item.mime,
                            subtype: item.subtype,
                            icon: item.icon,
                            sizes: item.sizes || {},
                            thumbnail: item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url,
                            width: item.width,
                            height: item.height
                        };
                    } else {
                        return { id: id };
                    }
                });
                
                debugLog('Selection created:', selection);
                
                // Call the onSelect callback
                if (options.onSelect) {
                    options.onSelect(selection);
                }
                
                // Close modal
                cleanupModal();
            }
        }

        // Handle select button click
        $('#tomatillo-select').on('click', handleSelectAction);
        
        // Handle ENTER key on SELECT button
        $('#tomatillo-select').on('keydown', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                handleSelectAction();
            }
        });
        
        // Handle Load More button click
        $('#tomatillo-load-more').off('click').on('click', function() {
            debugLog('');
            debugLog('═══════════════════════════════════════════════════════');
            debugLog('🖱️  LOAD MORE BUTTON CLICKED');
            debugLog('═══════════════════════════════════════════════════════');
            
            var $button = $(this);
            
            // Disable button while loading
            $button.prop('disabled', true).text('Loading...');
            debugLog('📦 Button disabled, fetching data...');
            
            // Get current filter
            var currentFilter = $('#tomatillo-filter').val() || 'image';
            debugLog('📦 Current filter:', currentFilter);
            
            // Filter all items based on current filter
            var filteredItems = currentMediaItems.filter(function(item) {
                if (currentFilter === 'all') return true;
                if (currentFilter === 'image') {
                    return item.type === 'image' || (item.mime && item.mime.startsWith('image/'));
                } else if (currentFilter === 'video') {
                    return item.type === 'video' || (item.mime && item.mime.startsWith('video/'));
                } else if (currentFilter === 'audio') {
                    return item.type === 'audio' || (item.mime && item.mime.startsWith('audio/'));
                } else if (currentFilter === 'application') {
                    return item.type === 'application' || (item.mime && item.mime.startsWith('application/'));
                }
                return item.type === currentFilter;
            });
            
            var remainingRendered = filteredItems.length - renderedItemsCount;
            
            debugLog('┌─────────────────────────────────────────────┐');
            debugLog('│ 📊 CURRENT STATE                           │');
            debugLog('├─────────────────────────────────────────────┤');
            debugLog('│ Items in memory:        ', currentMediaItems.length.toString().padEnd(20), '│');
            debugLog('│ Filtered items:         ', filteredItems.length.toString().padEnd(20), '│');
            debugLog('│ Already rendered:       ', renderedItemsCount.toString().padEnd(20), '│');
            debugLog('│ Remaining to render:    ', remainingRendered.toString().padEnd(20), '│');
            debugLog('│ More on server?:        ', (hasMoreItemsOnServer ? 'YES' : 'NO').padEnd(20), '│');
            debugLog('└─────────────────────────────────────────────┘');
            
            // Check if we need to fetch more from server first
            if (remainingRendered === 0 && hasMoreItemsOnServer) {
                debugLog('');
                debugLog('🌐 FETCHING ALL REMAINING FROM SERVER');
                debugLog('───────────────────────────────────────────────────────');
                
                // Get array of already loaded IDs to exclude
                var loadedIds = currentMediaItems.map(function(item) { return item.id; });
                
                debugLog('📡 Requesting: ALL remaining items');
                debugLog('📡 Excluding IDs:', loadedIds.slice(0, 5).join(', '), loadedIds.length > 5 ? '... (' + loadedIds.length + ' total)' : '');
                
                var data = {
                    action: 'query-attachments',
                    query: {
                        posts_per_page: -1, // -1 means ALL items
                        post_status: 'inherit',
                        post__not_in: loadedIds // Exclude already loaded items
                    }
                };
                
                $.post(ajaxurl, data)
                    .done(function(response) {
                        debugLog('✅ Server response received');
                        
                        var newItems = [];
                        if (Array.isArray(response)) {
                            newItems = response;
                        } else if (response && response.data && Array.isArray(response.data)) {
                            newItems = response.data;
                        } else if (response && response.attachments && Array.isArray(response.attachments)) {
                            newItems = response.attachments;
                        }
                        
                        debugLog('📦 Server returned:', newItems.length, 'items');
                        
                        if (newItems.length > 0) {
                            debugLog('📦 Item IDs:', newItems.map(function(item) { return item.id; }).slice(0, 10).join(', '), newItems.length > 10 ? '...' : '');
                            
                            // Check for duplicates
                            var existingIds = new Set(currentMediaItems.map(function(item) { return item.id; }));
                            var uniqueNewItems = newItems.filter(function(item) {
                                return !existingIds.has(item.id);
                            });
                            
                            debugLog('🔍 Deduplication: found', uniqueNewItems.length, 'unique items (', (newItems.length - uniqueNewItems.length), 'duplicates removed)');
                            
                            if (uniqueNewItems.length > 0) {
                                // Add to current media items
                                var beforeCount = currentMediaItems.length;
                                currentMediaItems = currentMediaItems.concat(uniqueNewItems);
                                debugLog('✅ Added to memory: before =', beforeCount, ', after =', currentMediaItems.length);
                                
                                // Load optimization data
                                loadOptimizationDataForNewItems(uniqueNewItems);
                                
                                // Now render the newly fetched items
                                var updatedFilteredItems = currentMediaItems.filter(function(item) {
                                    if (currentFilter === 'all') return true;
                                    if (currentFilter === 'image') {
                                        return item.type === 'image' || (item.mime && item.mime.startsWith('image/'));
                                    } else if (currentFilter === 'video') {
                                        return item.type === 'video' || (item.mime && item.mime.startsWith('video/'));
                                    } else if (currentFilter === 'audio') {
                                        return item.type === 'audio' || (item.mime && item.mime.startsWith('audio/'));
                                    } else if (currentFilter === 'application') {
                                        return item.type === 'application' || (item.mime && item.mime.startsWith('application/'));
                                    }
                                    return item.type === currentFilter;
                                });
                                
                                var newRemainingCount = updatedFilteredItems.length - renderedItemsCount;
                                debugLog('🎨 Ready to render:', newRemainingCount, 'items');
                                
                                if (newRemainingCount > 0) {
                                    debugLog('🎨 Calling loadMoreImages() to render...');
                                    loadMoreImages(renderedItemsCount, newRemainingCount, options);
                                } else {
                                    debugLog('⚠️  No new items match current filter');
                                }
                                
                                // Since we requested ALL (-1), we've now loaded everything from server
                                hasMoreItemsOnServer = false;
                                debugLog('🏁 ALL ITEMS LOADED FROM SERVER');
                            } else {
                                debugLog('⚠️  All fetched items were duplicates');
                                hasMoreItemsOnServer = false;
                            }
                        } else {
                            hasMoreItemsOnServer = false;
                            debugLog('🏁 NO MORE ITEMS ON SERVER');
                        }
                        
                        // Re-enable button
                        $button.prop('disabled', false);
                        debugLog('📦 Button re-enabled');
                        
                        // Update button state
                        var finalFilteredItems = currentMediaItems.filter(function(item) {
                            if (currentFilter === 'all') return true;
                            if (currentFilter === 'image') {
                                return item.type === 'image' || (item.mime && item.mime.startsWith('image/'));
                            } else if (currentFilter === 'video') {
                                return item.type === 'video' || (item.mime && item.mime.startsWith('video/'));
                            } else if (currentFilter === 'audio') {
                                return item.type === 'audio' || (item.mime && item.mime.startsWith('audio/'));
                            } else if (currentFilter === 'application') {
                                return item.type === 'application' || (item.mime && item.mime.startsWith('application/'));
                            }
                            return item.type === currentFilter;
                        });
                        updateLoadMoreButton(finalFilteredItems.length, renderedItemsCount, hasMoreItemsOnServer);
                        debugLog('═══════════════════════════════════════════════════════');
                        debugLog('');
                    })
                    .fail(function(xhr, status, error) {
                        debugError('❌ AJAX FAILED:', error);
                        debugError('   Status:', status);
                        debugError('   XHR:', xhr);
                        $button.prop('disabled', false).text('Load More Images');
                        debugLog('═══════════════════════════════════════════════════════');
                        debugLog('');
                    });
            } else if (remainingRendered > 0) {
                // We have items in memory, just render them ALL
                debugLog('');
                debugLog('🎨 RENDERING ALL FROM MEMORY');
                debugLog('───────────────────────────────────────────────────────');
                debugLog('📦 Rendering ALL', remainingRendered, 'items already in memory');
                
                loadMoreImages(renderedItemsCount, remainingRendered, options);
                
                // Re-enable button after a short delay
                setTimeout(function() {
                    $button.prop('disabled', false);
                    debugLog('📦 Button re-enabled');
                    updateLoadMoreButton(filteredItems.length, renderedItemsCount, hasMoreItemsOnServer);
                    debugLog('═══════════════════════════════════════════════════════');
                    debugLog('');
                }, 500);
            } else {
                // Nothing left to load
                debugLog('');
                debugLog('🏁 NOTHING TO LOAD');
                debugLog('───────────────────────────────────────────────────────');
                debugLog('⚠️  No items in memory and no more on server');
                $button.prop('disabled', false).hide();
                debugLog('═══════════════════════════════════════════════════════');
                debugLog('');
            }
        });
        
        // Handle clicking outside modal
        $('#tomatillo-custom-modal').off('click').on('click', function(e) {
            if (e.target.id === 'tomatillo-custom-modal') {
                debugLog('Clicked outside modal');
                cleanupModal();
            }
        });
        
        // Handle ESC key to close modal
        $(document).off('keydown.tomatillo-modal').on('keydown.tomatillo-modal', function(e) {
            if (e.key === 'Escape' && $('#tomatillo-custom-modal').length > 0) {
                debugLog('ESC key pressed - closing modal');
                cleanupModal();
            }
        });
        
        // Handle filter dropdown
        $('#tomatillo-filter').off('change').on('change', function() {
            var filterValue = $(this).val();
            debugLog('🔍 Filter changed to:', filterValue);
            debugLog('🔍 Current media items:', currentMediaItems);
            debugLog('🔍 Current media items types:', currentMediaItems.map(function(item) { return item.type; }));
            
            // Filter items based on type
            var filteredItems = currentMediaItems.filter(function(item) {
                debugLog('🔍 Checking item:', item.id, 'filename:', item.filename, 'type:', item.type, 'mime:', item.mime, 'matches filter:', filterValue);
                if (filterValue === 'all') return true;
                
                // More robust type checking
                if (filterValue === 'image') {
                    var isImage = item.type === 'image' || (item.mime && item.mime.startsWith('image/'));
                    debugLog('🔍 Image check for', item.filename, ':', item.type === 'image', '||', (item.mime && item.mime.startsWith('image/')), '=', isImage);
                    return isImage;
                } else if (filterValue === 'video') {
                    return item.type === 'video' || (item.mime && item.mime.startsWith('video/'));
                } else if (filterValue === 'audio') {
                    return item.type === 'audio' || (item.mime && item.mime.startsWith('audio/'));
                } else if (filterValue === 'application') {
                    return item.type === 'application' || (item.mime && item.mime.startsWith('application/'));
                }
                
                return item.type === filterValue;
            });
            
            // Get corresponding optimization data
            var filteredOptimizationData = currentOptimizationData.filter(function(data, index) {
                if (filterValue === 'all') return true;
                var item = currentMediaItems[index];
                
                // Use same robust type checking as above
                if (filterValue === 'image') {
                    return item.type === 'image' || (item.mime && item.mime.startsWith('image/'));
                } else if (filterValue === 'video') {
                    return item.type === 'video' || (item.mime && item.mime.startsWith('video/'));
                } else if (filterValue === 'audio') {
                    return item.type === 'audio' || (item.mime && item.mime.startsWith('audio/'));
                } else if (filterValue === 'application') {
                    return item.type === 'application' || (item.mime && item.mime.startsWith('application/'));
                }
                
                return item.type === filterValue;
            });
            
            debugLog('🔍 Filtered items:', filteredItems.length, 'of', currentMediaItems.length);
            debugLog('🔍 Filtered optimization data:', filteredOptimizationData.length);
            debugLog('🔍 Filtered items details:', filteredItems);
            
            // Debug: Check for duplicates in filtered items
            var itemIds = filteredItems.map(function(item) { return item.id; });
            var uniqueIds = [...new Set(itemIds)];
            if (itemIds.length !== uniqueIds.length) {
                debugError('🚨 DUPLICATE ITEMS DETECTED!', itemIds.length, 'items,', uniqueIds.length, 'unique');
                debugError('🚨 Duplicate IDs:', itemIds.filter(function(id, index) { return itemIds.indexOf(id) !== index; }));
            }
            
            // Clear search when filter changes
            $('#tomatillo-search').val('');
            $('#tomatillo-clear-search').hide();
            
            // Render only initial batch, not all items
            var initialBatchSize = window.tomatilloSettings ? (window.tomatilloSettings.infinite_scroll_batch || 100) : 100;
            var initialItems = filteredItems.slice(0, initialBatchSize);
            var initialOptimizationData = filteredOptimizationData.slice(0, initialBatchSize);
            
            // Re-render with initial batch of filtered items
            renderMediaGridWithOptimization(initialItems, initialOptimizationData, options, true);
            
            // Reset rendered count to actual rendered items
            renderedItemsCount = initialItems.length;
            
            // Update Load More button
            updateLoadMoreButton(filteredItems.length, renderedItemsCount, hasMoreItemsOnServer);
        });
        
        // Handle search input
        $('#tomatillo-search').off('input').on('input', function() {
            var searchQuery = $(this).val().toLowerCase().trim();
            debugLog('Search query:', searchQuery);
            
            // Show/hide clear button based on search content
            if (searchQuery === '') {
                $('#tomatillo-clear-search').hide();
            } else {
                $('#tomatillo-clear-search').show();
            }
            
            if (searchQuery === '') {
                // Show all items if search is empty - use the same method as initial load
                if (window.TomatilloBackgroundLoader && window.TomatilloBackgroundLoader.isMediaPreloaded()) {
                    // Use preloaded data with pre-calculated positions
                    renderMediaGridWithOptimization(currentMediaItems, currentOptimizationData, options, true);
                    // Reset rendered count for infinite scroll
                    renderedItemsCount = currentMediaItems.length;
                } else {
                    // Fallback to regular rendering
                    renderMediaGrid(currentMediaItems, options);
                }
            } else {
                // Filter items based on search query AND current filter
                var currentFilter = $('#tomatillo-filter').val();
                debugLog('🔍 DEBUG: Current filter:', currentFilter);
                
                var filteredItems = currentMediaItems.filter(function(item) {
                    // First apply type filter with robust checking
                    if (currentFilter !== 'all') {
                        var matchesType = false;
                        if (currentFilter === 'image') {
                            matchesType = item.type === 'image' || (item.mime && item.mime.startsWith('image/'));
                        } else if (currentFilter === 'video') {
                            matchesType = item.type === 'video' || (item.mime && item.mime.startsWith('video/'));
                        } else if (currentFilter === 'audio') {
                            matchesType = item.type === 'audio' || (item.mime && item.mime.startsWith('audio/'));
                        } else if (currentFilter === 'application') {
                            matchesType = item.type === 'application' || (item.mime && item.mime.startsWith('application/'));
                        } else {
                            matchesType = item.type === currentFilter;
                        }
                        
                        if (!matchesType) {
                            return false;
                        }
                    }
                    
                    // Then apply search filter
                    var searchableText = [
                        item.filename || '',
                        item.title || '',
                        item.caption || '',
                        item.description || '',
                        item.alt || '',
                        item.name || '',
                        item.slug || ''
                    ].join(' ').toLowerCase();
                    
                    return searchableText.includes(searchQuery);
                });
                
                // Get corresponding optimization data
                var filteredOptimizationData = currentOptimizationData.filter(function(data, index) {
                    var item = currentMediaItems[index];
                    
                    // First apply type filter
                    if (currentFilter !== 'all' && item.type !== currentFilter) {
                        return false;
                    }
                    
                    // Then apply search filter
                    var searchableText = [
                        item.filename || '',
                        item.title || '',
                        item.caption || '',
                        item.description || '',
                        item.alt || '',
                        item.name || '',
                        item.slug || ''
                    ].join(' ').toLowerCase();
                    
                    return searchableText.includes(searchQuery);
                });
                
                debugLog('Search results:', filteredItems.length, 'of', currentMediaItems.length);
                
                // Re-render with filtered items
                renderMediaGridWithOptimization(filteredItems, filteredOptimizationData, options, false);
            }
        });
        
        // Handle clear search button
        $('#tomatillo-clear-search').off('click').on('click', function() {
            debugLog('Clear search clicked');
            $('#tomatillo-search').val('').trigger('input'); // Trigger input event to update UI
        });
        
        // Handle upload button click
        $('#tomatillo-upload-btn').off('click').on('click', function() {
            debugLog('Upload button clicked');
            $('#tomatillo-file-input').click();
        });
        
        // Handle file input change
        $('#tomatillo-file-input').off('change').on('change', function() {
            var files = this.files;
            if (files && files.length > 0) {
                debugLog('Files selected for upload:', files.length);
                uploadFiles(Array.from(files), options);
            }
        });
        
        // Handle drag and drop with proper cleanup support
        setupDragDropHandlers(options);
    }
    
    /**
     * Setup drag and drop handlers with proper cleanup support
     */
    function setupDragDropHandlers(options) {
        // Clean up any existing handlers first
        cleanupDragDropHandlers();
        
        var $gridContainer = $('.tomatillo-grid-container');
        var $dragOverlay = $('#tomatillo-drag-drop-overlay');
        var dragCounter = 0;
        
        // Create named handler functions so they can be removed later
        var preventDefaultHandler = function(e) {
            e.preventDefault();
            e.stopPropagation();
        };
        
        var dragEnterHandler = function(e) {
            preventDefaultHandler(e);
            dragCounter++;
            if (dragCounter === 1) {
                $dragOverlay.addClass('active');
                $('#tomatillo-file-count').text('0');
            }
        };
        
        var dragOverHandler = function(e) {
            preventDefaultHandler(e);
            // Keep overlay visible, don't toggle
        };
        
        var dragLeaveHandler = function(e) {
            preventDefaultHandler(e);
            dragCounter--;
            if (dragCounter === 0) {
                $dragOverlay.removeClass('active');
            }
        };
        
        var dropHandler = function(e) {
            preventDefaultHandler(e);
            dragCounter = 0;
            $dragOverlay.removeClass('active');
            
            var files = e.dataTransfer.files;
            $('#tomatillo-file-count').text(files.length);
            if (files && files.length > 0) {
                debugLog('Files dropped for upload:', files.length);
                
                // Debounce: Prevent duplicate uploads within 500ms
                var now = Date.now();
                if (now - lastUploadTime < uploadDebounceDelay) {
                    debugLog('⚠️ Upload blocked: Too soon after last upload (debounced)');
                    return;
                }
                lastUploadTime = now;
                
                uploadFiles(Array.from(files), options);
            }
        };
        
        // Attach handlers to document
        document.addEventListener('dragenter', dragEnterHandler, false);
        document.addEventListener('dragover', dragOverHandler, false);
        document.addEventListener('dragleave', dragLeaveHandler, false);
        document.addEventListener('drop', dropHandler, false);
        
        // Store handlers for cleanup
        dragDropHandlers = {
            dragenter: dragEnterHandler,
            dragover: dragOverHandler,
            dragleave: dragLeaveHandler,
            drop: dropHandler
        };
        
        debugLog('✅ Drag-drop handlers attached with debouncing');
    }
    
    /**
     * Clean up drag and drop handlers
     */
    function cleanupDragDropHandlers() {
        if (dragDropHandlers) {
            debugLog('🧹 Removing drag-drop event handlers');
            document.removeEventListener('dragenter', dragDropHandlers.dragenter, false);
            document.removeEventListener('dragover', dragDropHandlers.dragover, false);
            document.removeEventListener('dragleave', dragDropHandlers.dragleave, false);
            document.removeEventListener('drop', dragDropHandlers.drop, false);
            dragDropHandlers = null;
        }
    }

    /**
     * Upload files with progress tracking
     */
    function uploadFiles(files, options) {
        debugLog('Starting upload of', files.length, 'files');
        
        var $progressOverlay = $('#tomatillo-upload-progress-overlay');
        var $progressFill = $('#tomatillo-upload-progress-fill');
        var $uploadStatus = $('#tomatillo-upload-status');
        var $filesList = $('#tomatillo-upload-files-list');
        var $cancelBtn = $('#tomatillo-upload-cancel-btn');
        
        // Show progress overlay and disable main modal
        $progressOverlay.addClass('active');
        $('.tomatillo-modal').addClass('uploading-active');
        $uploadStatus.text('Preparing upload...');
        $progressFill.css('width', '0%');
        
        var uploadedCount = 0;
        var totalFiles = files.length;
        var successfulCount = 0;
        var failedCount = 0;
        var currentXhr = null;
        var newlyUploadedIds = [];
        
        // Initialize file list
        $filesList.empty();
        files.forEach(function(file, index) {
            var fileItem = $('<div class="tomatillo-upload-file-item">' +
                '<div class="tomatillo-upload-file-name">' + file.name + '</div>' +
                '<div class="tomatillo-upload-file-status pending">Pending</div>' +
                '<div class="tomatillo-upload-file-progress">' +
                    '<div class="tomatillo-upload-file-progress-bar">' +
                        '<div class="tomatillo-upload-file-progress-fill" style="width: 0%"></div>' +
                    '</div>' +
                '</div>' +
            '</div>');
            $filesList.append(fileItem);
        });
        
        // Update status
        $uploadStatus.text('Preparing to upload ' + totalFiles + ' files...');
        
        // Set up cancel functionality
        $cancelBtn.off('click').on('click', function() {
            if (currentXhr) {
                currentXhr.abort();
            }
            $progressOverlay.removeClass('active');
            $('.tomatillo-modal').removeClass('uploading-active');
        });
        
        // Upload files one by one
        uploadNextFile(0);
        
        function uploadNextFile(index) {
            if (index >= files.length) {
                // All files processed
                var successRate = totalFiles > 0 ? Math.round((successfulCount / totalFiles) * 100) : 0;
                $uploadStatus.text('Upload complete! ' + successfulCount + '/' + totalFiles + ' files successful (' + successRate + '%)');
                $progressFill.css('width', '100%');
                
                // Add new items to grid in real-time and auto-select them
                setTimeout(function() {
                    addNewItemsToGrid(newlyUploadedIds, options);
                    $progressOverlay.removeClass('active');
                    $('.tomatillo-modal').removeClass('uploading-active');
                }, 1000);
                return;
            }
            
            var file = files[index];
            var fileItem = $filesList.children().eq(index);
            var statusElement = fileItem.find('.tomatillo-upload-file-status');
            var progressBar = fileItem.find('.tomatillo-upload-file-progress-fill');
            
            // Update status to uploading
            statusElement.text('Uploading...');
            statusElement.removeClass('pending').addClass('uploading');
            fileItem.addClass('uploading');
            $uploadStatus.text('Uploading file ' + (index + 1) + ' of ' + totalFiles + ': ' + file.name);
            
            // Create FormData for single file
            var formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'tomatillo_upload_single_file');
            
            // Create XMLHttpRequest for this file
            currentXhr = new XMLHttpRequest();
            
            // Track upload progress for this file
            currentXhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    var filePercentComplete = (e.loaded / e.total) * 100;
                    var overallProgress = ((index + (filePercentComplete / 100)) / totalFiles) * 100;
                    $progressFill.css('width', overallProgress + '%');
                }
            });
            
            // Handle response
            currentXhr.addEventListener('load', function() {
                if (currentXhr.status === 200) {
                    try {
                        var response = JSON.parse(currentXhr.responseText);
                        if (response.success) {
                            // Success
                            statusElement.text('Success');
                            statusElement.removeClass('uploading').addClass('success');
                            progressBar.css('width', '100%');
                            fileItem.removeClass('uploading').addClass('completed');
                            successfulCount++;
                            
                            // Store the uploaded attachment ID for auto-selection
                            if (response.data && response.data.attachment_id) {
                                newlyUploadedIds.push(response.data.attachment_id);
                            }
                            
                            // Add file size info
                            if (response.data && response.data.file_size_formatted) {
                                statusElement.text('Success (' + response.data.file_size_formatted + ')');
                            }
                        } else {
                            // Failed
                            statusElement.text('Failed');
                            statusElement.removeClass('uploading').addClass('error');
                            progressBar.css('width', '100%');
                            failedCount++;
                            
                            // Add error details
                            if (response.data) {
                                statusElement.text('Failed: ' + response.data);
                            }
                        }
                    } catch (e) {
                        statusElement.text('Failed: Parse error');
                        statusElement.removeClass('uploading').addClass('error');
                        progressBar.css('width', '100%');
                        failedCount++;
                        debugError('Invalid response:', currentXhr.responseText);
                    }
                } else {
                    statusElement.text('Failed: HTTP ' + currentXhr.status);
                    statusElement.removeClass('uploading').addClass('error');
                    progressBar.css('width', '100%');
                    failedCount++;
                    debugError('Upload failed with status:', currentXhr.status);
                }
                
                uploadedCount++;
                
                // Upload next file
                setTimeout(function() {
                    uploadNextFile(index + 1);
                }, 100);
            });
            
            // Handle error
            currentXhr.addEventListener('error', function() {
                statusElement.text('Failed: Network error');
                statusElement.removeClass('uploading').addClass('error');
                progressBar.css('width', '100%');
                failedCount++;
                uploadedCount++;
                debugError('Upload error for file:', file.name);
                
                // Upload next file
                setTimeout(function() {
                    uploadNextFile(index + 1);
                }, 100);
            });
            
            // Send request
            currentXhr.open('POST', ajaxurl || '/wp-admin/admin-ajax.php');
            currentXhr.send(formData);
        }
    }

    /**
     * Add newly uploaded items to the beginning of the grid in real-time
     */
    function addNewItemsToGrid(newlyUploadedIds, options) {
        debugLog('Adding', newlyUploadedIds.length, 'newly uploaded items to grid');
        
        if (!newlyUploadedIds || newlyUploadedIds.length === 0) {
            return;
        }
        
        // Fetch the new items from WordPress
        var data = {
            action: 'query-attachments',
            query: {
                post__in: newlyUploadedIds,
                posts_per_page: newlyUploadedIds.length,
                post_status: 'inherit'
            }
        };
        
        $.post(ajaxurl, data)
            .done(function(response) {
                var newItems = Array.isArray(response) ? response : (response.data || []);
                debugLog('Fetched', newItems.length, 'new items:', newItems);
                
                if (newItems.length > 0) {
                    // Add new items to the beginning of currentMediaItems
                    currentMediaItems = newItems.concat(currentMediaItems);
                    
                    // Create HTML for new items
                    var newItemsHtml = '';
                    newItems.forEach(function(item) {
                        var hiResImage = getHiResImage(item);
                        var originalFilename = item.filename || item.title || 'Unknown';
                        var cleanedFilename = cleanFilename(originalFilename);
                        var type = item.type || 'unknown';
                        
                        // Calculate aspect ratio for masonry
                        var aspectRatio = hiResImage.width && hiResImage.height ? 
                            hiResImage.height / hiResImage.width : 1;
                        var isVertical = aspectRatio > 1.2;
                        var isHorizontal = aspectRatio < 0.8;
                        var orientation = isVertical ? 'Portrait' : isHorizontal ? 'Landscape' : 'Square';
                        
                        // Format file size
                        var fileSize = item.filesizeInBytes || 0;
                        var fileSizeFormatted = formatFileSize(fileSize);
                        
                        // Get file extension
                        var fileExtension = originalFilename.split('.').pop() || 'unknown';
                        
                        newItemsHtml += `
                            <div class="tomatillo-media-item" data-id="${item.id}" data-title="${item.title || ''}" data-alt="${item.alt || ''}" data-caption="${item.caption || ''}" data-description="${item.description || ''}" data-filename="${cleanedFilename}" data-type="${type}">
                                ${type === 'image' ? `
                                    <img src="${hiResImage.url}" alt="${item.alt || ''}" loading="lazy" class="tomatillo-media-image">
                                ` : `
                                    <div class="tomatillo-file-preview">
                                        <div class="tomatillo-file-icon">📄</div>
                                    </div>
                                `}
                                <div class="tomatillo-hover-info">
                                    <div class="filename">${cleanedFilename}</div>
                                    <div class="dimensions">${orientation} • ${hiResImage.width || '?'}×${hiResImage.height || '?'}</div>
                                    <div class="details">${fileSizeFormatted} • ${fileExtension.toUpperCase()}</div>
                                </div>
                            </div>
                        `;
                    });
                    
                    // Prepend new items to the grid
                    var $grid = $('#tomatillo-media-grid');
                    var $existingItems = $grid.find('.tomatillo-media-item');
                    
                    if ($existingItems.length > 0) {
                        // Insert before the first existing item
                        $existingItems.first().before(newItemsHtml);
                    } else {
                        // If no existing items, just add to the grid
                        $grid.html(newItemsHtml);
                    }
                    
                    // Force complete masonry re-layout
                    setTimeout(function() {
                        debugLog('🔄 Forcing complete masonry re-layout after adding new items');
                        // Clear any existing positioning to force recalculation
                        $grid.find('.tomatillo-media-item').css({
                            'position': '',
                            'left': '',
                            'top': '',
                            'transform': ''
                        });
                        
                        // Wait for new images to load, then trigger layout
                        var newImages = $grid.find('.tomatillo-media-item:not([style*="position"]) img');
                        if (newImages.length > 0) {
                            debugLog('🔄 Waiting for', newImages.length, 'new images to load');
                            var loadedCount = 0;
                            newImages.each(function() {
                                var img = this;
                                if (img.complete) {
                                    loadedCount++;
                                } else {
                                    img.onload = function() {
                                        loadedCount++;
                                        if (loadedCount === newImages.length) {
                                            debugLog('🔄 All new images loaded, triggering masonry layout');
                                            setTimeout(layoutMasonry, 50);
                                        }
                                    };
                                }
                            });
                            
                            // If all images are already loaded
                            if (loadedCount === newImages.length) {
                                debugLog('🔄 All images already loaded, triggering masonry layout');
                                setTimeout(layoutMasonry, 50);
                            }
                        } else {
                            // No new images, trigger layout immediately
                            debugLog('🔄 No new images, triggering masonry layout immediately');
                            setTimeout(layoutMasonry, 50);
                        }
                    }, 150);
                    
                    // Auto-select the new items after a short delay to ensure DOM is ready
                    setTimeout(function() {
                        autoSelectNewlyUploadedItems(newlyUploadedIds, options);
                    }, 200);
                }
            })
            .fail(function(xhr, status, error) {
                debugError('Error fetching new items:', error);
            });
    }

    /**
     * Auto-select newly uploaded items
     */
    function autoSelectNewlyUploadedItems(newlyUploadedIds, options) {
        debugLog('Auto-selecting newly uploaded items:', newlyUploadedIds);
        
        newlyUploadedIds.forEach(function(attachmentId) {
            var $item = $('.tomatillo-media-item[data-id="' + attachmentId + '"]');
            if ($item.length && !$item.hasClass('selected')) {
                if (!options.multiple) {
                    // Clear all selections in single mode
                    $('.tomatillo-media-item').removeClass('selected');
                    selectedItems = [];
                }
                $item.addClass('selected');
                selectedItems.push(attachmentId);
            }
        });
        
        // Update selection count and button state
        updateSelectionUI(selectedItems.length, options);
        
        debugLog('Auto-selection complete. Selected items:', selectedItems);
    }

    /**
     * Load more images in background after modal opens
     */
    function loadMoreImagesInBackground(options) {
        // Get array of already loaded IDs to exclude
        var loadedIds = currentMediaItems.map(function(item) { return item.id; });
        
        debugLog('');
        debugLog('═══════════════════════════════════════════════════════');
        debugLog('🔄 BACKGROUND LOADER STARTED');
        debugLog('═══════════════════════════════════════════════════════');
        debugLog('📡 Fetching: ALL remaining items');
        debugLog('📡 Items already in memory:', currentMediaItems.length);
        debugLog('📡 Excluding IDs:', loadedIds.slice(0, 5).join(', '), loadedIds.length > 5 ? '... (' + loadedIds.length + ' total)' : '');
        
        var data = {
            action: 'query-attachments',
            query: {
                posts_per_page: -1, // -1 = ALL remaining items
                post_status: 'inherit',
                post__not_in: loadedIds // Exclude already loaded items
                // Remove post_mime_type restriction to load all file types
            }
        };

        $.post(ajaxurl, data)
            .done(function(response) {
                debugLog('✅ Background loader: Server response received');
                
                var newItems = [];
                if (Array.isArray(response)) {
                    newItems = response;
                } else if (response && response.data && Array.isArray(response.data)) {
                    newItems = response.data;
                } else if (response && response.attachments && Array.isArray(response.attachments)) {
                    newItems = response.attachments;
                }

                debugLog('📦 Background loader: Server returned', newItems.length, 'items');

                if (newItems.length > 0) {
                    debugLog('📦 Item IDs:', newItems.map(function(item) { return item.id; }).slice(0, 10).join(', '), newItems.length > 10 ? '...' : '');
                    
                    // Check for duplicates before adding
                    var existingIds = new Set(currentMediaItems.map(function(item) { return item.id; }));
                    var uniqueNewItems = newItems.filter(function(item) {
                        return !existingIds.has(item.id);
                    });
                    
                    debugLog('🔍 Deduplication: found', uniqueNewItems.length, 'unique items (', (newItems.length - uniqueNewItems.length), 'duplicates removed)');
                    
                    if (uniqueNewItems.length > 0) {
                        var beforeCount = currentMediaItems.length;
                        
                        // Add to current media items
                        currentMediaItems = currentMediaItems.concat(uniqueNewItems);
                        
                        debugLog('✅ Added to memory: before =', beforeCount, ', after =', currentMediaItems.length);
                        
                        // Load optimization data for new items
                        loadOptimizationDataForNewItems(uniqueNewItems);
                        
                        // Since we requested ALL (-1), we've now loaded everything
                        hasMoreItemsOnServer = false;
                        debugLog('🏁 ALL ITEMS LOADED FROM SERVER');
                    } else {
                        debugLog('⚠️  All new items were duplicates, skipping');
                        hasMoreItemsOnServer = false;
                    }
                } else {
                    debugLog('🏁 NO MORE ITEMS available for background loading');
                    hasMoreItemsOnServer = false;
                }
                
                // Update Load More button visibility based on new total
                var currentFilter = $('#tomatillo-filter').val() || 'image';
                var filteredItems = currentMediaItems.filter(function(item) {
                    if (currentFilter === 'all') return true;
                    if (currentFilter === 'image') {
                        return item.type === 'image' || (item.mime && item.mime.startsWith('image/'));
                    } else if (currentFilter === 'video') {
                        return item.type === 'video' || (item.mime && item.mime.startsWith('video/'));
                    } else if (currentFilter === 'audio') {
                        return item.type === 'audio' || (item.mime && item.mime.startsWith('audio/'));
                    } else if (currentFilter === 'application') {
                        return item.type === 'application' || (item.mime && item.mime.startsWith('application/'));
                    }
                    return item.type === currentFilter;
                });
                updateLoadMoreButton(filteredItems.length, renderedItemsCount, hasMoreItemsOnServer);
                debugLog('═══════════════════════════════════════════════════════');
                debugLog('');
            })
            .fail(function(xhr, status, error) {
                debugError('❌ Background loading FAILED:', error);
                debugError('   Status:', status);
                debugLog('═══════════════════════════════════════════════════════');
                debugLog('');
            });
    }

    /**
     * Load optimization data for new items
     */
    function loadOptimizationDataForNewItems(newItems) {
        var optimizationPromises = newItems.map(function(item) {
            return getOptimizationData(item.id).then(function(data) {
                return data;
            }).catch(function(error) {
                return null;
            });
        });

        Promise.all(optimizationPromises).then(function(newOptimizationData) {
            // Add to current optimization data
            currentOptimizationData = currentOptimizationData.concat(newOptimizationData);
            debugLog('🔄 Background loaded optimization data for', newOptimizationData.length, 'new items');
        });
    }

    /**
     * Setup infinite scroll for media grid
     */
    function setupInfiniteScroll(options) {
        // Check if infinite scroll is enabled
        if (!window.tomatilloSettings || !window.tomatilloSettings.infinite_scroll_batch) {
            return;
        }

        var batchSize = window.tomatilloSettings.infinite_scroll_batch;

        debugLog('🔄 Setting up infinite scroll with batch size:', batchSize, 'rendered items:', renderedItemsCount);

        // Add scroll listener to modal content
        $('#tomatillo-modal-content').off('scroll.infinite').on('scroll.infinite', function() {
            var $this = $(this);
            var scrollTop = $this.scrollTop();
            var scrollHeight = $this[0].scrollHeight;
            var clientHeight = $this[0].clientHeight;

            // Check if scrolled to bottom (with some buffer)
            if (scrollTop + clientHeight >= scrollHeight - 100 && !isLoading) {
                debugLog('🔄 Scrolled to bottom, loading more images...');
                loadMoreImages(renderedItemsCount, batchSize, options);
            }
        });
    }

    /**
     * Load more images for infinite scroll (now uses preloaded data)
     */
    function loadMoreImages(offset, batchSize, options) {
        if (isLoading) return;
        
        isLoading = true;
        debugLog('🔄 Infinite scroll triggered - rendering preloaded images from offset:', offset);
        debugLog('🔄 Options multiple:', options.multiple);

        // IMPORTANT: Apply current filter before loading more items
        var currentFilter = $('#tomatillo-filter').val() || 'image';
        debugLog('🔍 Current filter for infinite scroll:', currentFilter);
        
        // Filter currentMediaItems based on current filter
        var filteredItems = currentMediaItems.filter(function(item) {
            if (currentFilter === 'all') return true;
            
            if (currentFilter === 'image') {
                return item.type === 'image' || (item.mime && item.mime.startsWith('image/'));
            } else if (currentFilter === 'video') {
                return item.type === 'video' || (item.mime && item.mime.startsWith('video/'));
            } else if (currentFilter === 'audio') {
                return item.type === 'audio' || (item.mime && item.mime.startsWith('audio/'));
            } else if (currentFilter === 'application') {
                return item.type === 'application' || (item.mime && item.mime.startsWith('application/'));
            }
            
            return item.type === currentFilter;
        });
        
        // Get corresponding optimization data for filtered items
        var filteredOptimizationData = [];
        filteredItems.forEach(function(filteredItem) {
            var originalIndex = currentMediaItems.findIndex(function(item) {
                return item.id === filteredItem.id;
            });
            if (originalIndex >= 0 && currentOptimizationData[originalIndex]) {
                filteredOptimizationData.push(currentOptimizationData[originalIndex]);
            } else {
                filteredOptimizationData.push(null);
            }
        });
        
        debugLog('🔍 Filtered items:', filteredItems.length, 'of', currentMediaItems.length);

        // Calculate how many items to render from FILTERED items
        var itemsToRender = Math.min(batchSize, filteredItems.length - offset);
        
        if (itemsToRender > 0) {
            var newItems = filteredItems.slice(offset, offset + itemsToRender);
            var newOptimizationData = filteredOptimizationData.slice(offset, offset + itemsToRender);
            
            debugLog('🔄 Rendering', newItems.length, 'filtered preloaded images');
            
            // Render additional items using preloaded optimization data
            renderAdditionalItemsWithOptimization(newItems, newOptimizationData, options);
            
            // Update rendered count
            renderedItemsCount += newItems.length;
            debugLog('🔄 Total rendered items:', renderedItemsCount);
            
            // Update Load More button
            updateLoadMoreButton(filteredItems.length, renderedItemsCount, hasMoreItemsOnServer);
        } else {
            debugLog('🔄 No more filtered images to render');
        }
        
        isLoading = false;
    }

    /**
     * Render additional items for infinite scroll using preloaded optimization data
     */
    function renderAdditionalItemsWithOptimization(newItems, newOptimizationData, options) {
        debugLog('🔍 DEBUG: renderAdditionalItemsWithOptimization called with', newItems.length, 'new items');
        
        // Get current column heights from existing DOM elements
        var containerWidth = 1200; // fallback
        var gridElement = document.getElementById('tomatillo-media-grid');
        var modalElement = document.getElementById('tomatillo-modal');
        
        if (gridElement && gridElement.offsetWidth > 0) {
            containerWidth = gridElement.offsetWidth;
        } else if (modalElement) {
            var modalWidth = modalElement.offsetWidth;
            var modalPadding = 40;
            containerWidth = modalWidth - modalPadding;
        }
        
        var gap = 16;
        var columns = 4;
        if (containerWidth < 768) {
            columns = 2;
        } else if (containerWidth < 1200) {
            columns = 3;
        } else if (containerWidth < 1600) {
            columns = 4;
        } else {
            columns = 5;
        }
        
        var columnWidth = (containerWidth - (gap * (columns - 1))) / columns;
        
        // Get current column heights from existing items
        var currentColumnHeights = getCurrentColumnHeights(columns, columnWidth, gap);
        
        // Pre-calculate positions for the new items using current column heights
        var newPositions = preCalculateMasonryPositions(newItems, newOptimizationData, currentColumnHeights);
        
        var additionalHtml = '';
        
        newItems.forEach(function(item, index) {
            var optimizationData = newOptimizationData[index];
            var hiResImage = getHiResImageWithOptimization(item, optimizationData);
            var position = newPositions[index];
            
            debugLog('🔍 DEBUG: Additional item', index, 'ID:', item.id, 'Position:', position);
            
            var originalFilename = item.filename || item.title || 'Unknown';
            var filename = cleanFilename(originalFilename);
            
            var orientation = item.width > item.height ? 'Landscape' : 'Portrait';
            
            additionalHtml += `
                <div class="tomatillo-media-item" data-id="${item.id}" style="position: absolute; left: ${position.left}px; top: ${position.top}px; width: ${position.width}px;">
                    ${item.type === 'image' ? 
                        `<img src="${hiResImage.url}" alt="${filename}" loading="lazy" style="width: 100%; height: auto;">` :
                        `<div style="width: 100%; height: 200px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #666;">📄</div>`
                    }
                    
                    <div class="tomatillo-hover-info">
                        <div class="filename">${filename}</div>
                        <div class="dimensions">${orientation} • ${hiResImage.width}×${hiResImage.height}</div>
                        <div class="details">${hiResImage.filesize} • ${hiResImage.format}</div>
                    </div>
                </div>
            `;
        });
        
        // Append to existing grid
        $('#tomatillo-media-grid').append(additionalHtml);
        
        // Update container height based on new positions
        var maxHeight = Math.max.apply(Math, newPositions.map(function(pos) {
            return pos.top + pos.height;
        }));
        var currentHeight = parseInt($('#tomatillo-media-grid').css('height')) || 0;
        var newHeight = Math.max(currentHeight, maxHeight);
        $('#tomatillo-media-grid').css('height', newHeight + 'px');
        
        debugLog('🔍 DEBUG: Updated container height from', currentHeight, 'to', newHeight);
        debugLog('🎨 Additional items rendered with pre-calculated positions - NO LAYOUT SHIFT!');
        
        // Re-setup hover effects for new items
        $('.tomatillo-media-item').off('mouseenter mouseleave').hover(
            function() {
                if (!$(this).hasClass('selected')) {
                    $(this).css({
                        'transform': 'translateY(-2px)',
                        'box-shadow': '0 4px 12px rgba(0,0,0,0.12)'
                    });
                }
                $(this).find('.tomatillo-hover-info').css('opacity', '1');
            },
            function() {
                if (!$(this).hasClass('selected')) {
                    $(this).css({
                        'transform': 'translateY(0)',
                        'box-shadow': '0 1px 3px rgba(0,0,0,0.08)'
                    });
                } else {
                    $(this).css({
                        'transform': 'translateY(0)',
                        'box-shadow': '0 4px 12px rgba(40, 167, 69, 0.3)',
                        'border-color': '#28a745'
                    });
                }
                $(this).find('.tomatillo-hover-info').css('opacity', '0');
            }
        );
        
        debugLog('🔄 Rendered', newItems.length, 'additional items with preloaded optimization data');
    }

    /**
     * Render additional items for infinite scroll
     */
    function renderAdditionalItems(newItems, options) {
        var additionalHtml = '';
        
        newItems.forEach(function(item) {
            // Get HI-RES image using Media Studio logic
            var hiResImage = getHiResImage(item);
            
            var originalFilename = item.filename || item.title || 'Unknown';
            var filename = cleanFilename(originalFilename);
            
            var orientation = item.width > item.height ? 'Landscape' : 'Portrait';
            
            additionalHtml += `
                <div class="tomatillo-media-item" data-id="${item.id}">
                    ${item.type === 'image' ? 
                        `<img src="${hiResImage.url}" alt="${filename}" loading="lazy">` :
                        `<div style="width: 100%; height: 200px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #666;">📄</div>`
                    }
                    
                    <div class="tomatillo-hover-info">
                        <div class="filename">${filename}</div>
                        <div class="dimensions">${orientation} • ${hiResImage.width}×${hiResImage.height}</div>
                        <div class="details">${hiResImage.filesize} • ${hiResImage.format}</div>
                    </div>
                </div>
            `;
        });
        
        // Append to existing grid
        $('#tomatillo-media-grid').append(additionalHtml);
        
        // Re-setup hover effects for new items
        $('.tomatillo-media-item').off('mouseenter mouseleave').hover(
            function() {
                if (!$(this).hasClass('selected')) {
                    $(this).css({
                        'transform': 'translateY(-2px)',
                        'box-shadow': '0 4px 12px rgba(0,0,0,0.12)'
                    });
                }
                $(this).find('.tomatillo-hover-info').css('opacity', '1');
            },
            function() {
                if (!$(this).hasClass('selected')) {
                    $(this).css({
                        'transform': 'translateY(0)',
                        'box-shadow': '0 1px 3px rgba(0,0,0,0.08)'
                    });
                } else {
                    $(this).css({
                        'transform': 'translateY(0)',
                        'box-shadow': '0 4px 12px rgba(40, 167, 69, 0.3)',
                        'border-color': '#28a745'
                    });
                }
                $(this).find('.tomatillo-hover-info').css('opacity', '0');
            }
        );
        
        debugLog('🔄 Rendered', newItems.length, 'additional items');
    }

    /**
     * Clean up modal and reset state
     */
    function cleanupModal() {
        debugLog('🧹 Cleaning up modal');
        debugLog('🧹 Current selectedItems:', selectedItems);
        debugLog('🧹 Current mediaItems length:', currentMediaItems.length);
        
        // Remove modal from DOM
        $('#tomatillo-custom-modal').remove();
        
        // Reset global state
        selectedItems = [];
        currentMediaItems = [];
        currentOptimizationData = [];
        renderedItemsCount = 0;
        isLoading = false;
        
            // Remove namespaced event handlers
            $(document).off('keydown.tomatillo-modal');
            $(document).off('click.tomatillo-media');
            $(window).off('resize.tomatillo-masonry');
            
            // Remove infinite scroll event handler
            $('#tomatillo-modal-content').off('scroll.infinite');
            
            // Remove drag-drop handlers
            cleanupDragDropHandlers();
        
        debugLog('🧹 Modal cleanup complete');
        debugLog('🧹 Reset selectedItems:', selectedItems);
        debugLog('🧹 Reset mediaItems length:', currentMediaItems.length);
    }

    /**
     * Render media grid with pre-filtered optimization data (for search)
     */
    function renderMediaGridWithOptimization(mediaItems, optimizationDataArray, options, skipImageWait) {
        debugLog('Rendering filtered media grid with', mediaItems.length, 'items');
        
        // Determine layout type based on content
        var hasImages = mediaItems.some(function(item) { return item.type === 'image'; });
        var hasNonImages = mediaItems.some(function(item) { return item.type !== 'image'; });
        
        // Use grid layout if there are any non-image files (mixed content)
        var layoutType = (hasImages && hasNonImages) ? 'grid' : (hasImages ? 'masonry' : 'grid');
        debugLog('🔍 DEBUG: Layout type:', layoutType, '(hasImages:', hasImages, ', hasNonImages:', hasNonImages, ')');
        
        // Update grid container class
        var $grid = $('#tomatillo-media-grid');
        $grid.removeClass('tomatillo-masonry-grid tomatillo-grid-layout');
        $grid.addClass(layoutType === 'masonry' ? 'tomatillo-masonry-grid' : 'tomatillo-grid-layout');
        
        // Pre-calculate masonry positions for instant layout (only for masonry)
        var preCalculatedPositions = layoutType === 'masonry' ? preCalculateMasonryPositions(mediaItems, optimizationDataArray) : [];
        
        var gridHtml = '';
        
        mediaItems.forEach(function(item, index) {
            var optimizationData = optimizationDataArray[index];
            var hiResImage = getHiResImageWithOptimization(item, optimizationData);
            var position = preCalculatedPositions[index];
            
            debugLog('🔍 DEBUG: Rendering item', index, 'ID:', item.id);
            debugLog('🔍 DEBUG: Position:', position);
            debugLog('🔍 DEBUG: HiRes image:', hiResImage);
            
            var originalFilename = item.filename || item.title || 'Unknown';
            var filename = cleanFilename(originalFilename);
            
            var orientation = item.width > item.height ? 'Landscape' : 'Portrait';
            
            // Generate different HTML based on layout type and file type
            var itemStyle, itemContent;
            
            if (layoutType === 'masonry') {
                // Masonry layout - only for pure image content
                itemStyle = `position: absolute; left: ${position.left}px; top: ${position.top}px; width: ${position.width}px;`;
                itemContent = `<img src="${hiResImage.url}" alt="${filename}" loading="lazy" style="width: 100%; height: auto;">`;
            } else {
                // Grid layout - for mixed content or non-image files
                itemStyle = `width: 200px; height: 200px; margin: 8px;`;
                
                if (item.type === 'image') {
                    // Images in grid layout - use thumbnail with overlay info
                    itemContent = `
                        <div style="width: 100%; height: 100%; position: relative; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; overflow: hidden;">
                            <img src="${hiResImage.url}" alt="${filename}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); padding: 8px; color: white;">
                                <div style="font-size: 11px; font-weight: 600; margin-bottom: 2px;">${filename}</div>
                                <div style="font-size: 9px; opacity: 0.9;">${orientation} • ${hiResImage.width}×${hiResImage.height}</div>
                                <div style="font-size: 9px; opacity: 0.8;">${hiResImage.filesize} • ${hiResImage.format}</div>
                            </div>
                        </div>
                    `;
                } else {
                    // Non-image files - use file tile format
                    var fileIcon = getFileIcon(item.type);
                    var fileSize = formatFileSize(item.filesizeInBytes || 0);
                    
                    itemContent = `
                        <div style="width: 100%; height: 100%; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 12px; text-align: center;">
                            <div style="font-size: 32px; margin-bottom: 8px;">${fileIcon}</div>
                            <div style="font-size: 12px; font-weight: 600; color: #333; margin-bottom: 4px; word-break: break-all; line-height: 1.2;">${filename}</div>
                            <div style="font-size: 10px; color: #666; text-transform: uppercase; margin-bottom: 2px;">${item.type}</div>
                            <div style="font-size: 10px; color: #888;">${fileSize}</div>
                        </div>
                    `;
                }
            }
            
            var itemHtml = `
                <div class="tomatillo-media-item" data-id="${item.id}" style="${itemStyle}">
                    ${itemContent}
                    
                    ${layoutType === 'masonry' ? `
                        <div class="tomatillo-hover-info">
                            <div class="filename">${filename}</div>
                            <div class="dimensions">${orientation} • ${hiResImage.width}×${hiResImage.height}</div>
                            <div class="details">${hiResImage.filesize} • ${hiResImage.format}</div>
                        </div>
                    ` : ''}
                </div>
            `;
            
            debugLog('🔍 DEBUG: Generated HTML for item', index, ':', itemHtml);
            gridHtml += itemHtml;
        });
        
        debugLog('🔍 DEBUG: About to insert HTML into grid');
        debugLog('🔍 DEBUG: Grid element:', $('#tomatillo-media-grid'));
        debugLog('🔍 DEBUG: HTML length:', gridHtml.length);
        
        $('#tomatillo-media-grid').html(gridHtml);
        
        debugLog('🔍 DEBUG: HTML inserted, checking DOM elements');
        var insertedItems = $('.tomatillo-media-item');
        debugLog('🔍 DEBUG: Inserted items count:', insertedItems.length);
        
        insertedItems.each(function(index) {
            var $item = $(this);
            var computedStyle = window.getComputedStyle(this);
            var rect = this.getBoundingClientRect();
            debugLog('🔍 DEBUG: Item', index, 'ID:', $item.data('id'));
            debugLog('🔍 DEBUG: Computed position:', computedStyle.position);
            debugLog('🔍 DEBUG: Computed left:', computedStyle.left);
            debugLog('🔍 DEBUG: Computed top:', computedStyle.top);
            debugLog('🔍 DEBUG: Computed width:', computedStyle.width);
            debugLog('🔍 DEBUG: Computed height:', computedStyle.height);
            debugLog('🔍 DEBUG: Bounding rect:', rect.left, rect.top, rect.width, rect.height);
            
            // Check for overlapping
            if (index > 0) {
                var prevItem = insertedItems.eq(index - 1)[0];
                var prevRect = prevItem.getBoundingClientRect();
                var currentRect = this.getBoundingClientRect();
                
                // Check if items are overlapping
                var overlapping = !(currentRect.left >= prevRect.right || 
                                 currentRect.right <= prevRect.left || 
                                 currentRect.top >= prevRect.bottom || 
                                 currentRect.bottom <= prevRect.top);
                
                if (overlapping) {
                    debugError('🚨 OVERLAPPING DETECTED! Item', index, 'overlaps with item', index - 1);
                    debugError('🚨 Current rect:', currentRect);
                    debugError('🚨 Previous rect:', prevRect);
                }
            }
        });
        
        // Add hover effects (only for masonry layout - images)
        if (layoutType === 'masonry') {
            // Remove any existing hover effects first
            $('.tomatillo-media-item').off('mouseenter mouseleave');
            
            $('.tomatillo-media-item').hover(
                function() {
                    if (!$(this).hasClass('selected')) {
                        $(this).css({
                            'transform': 'translateY(-2px)',
                            'box-shadow': '0 4px 12px rgba(0,0,0,0.12)'
                        });
                    }
                    $(this).find('.tomatillo-hover-info').css('opacity', '1');
                },
                function() {
                    if (!$(this).hasClass('selected')) {
                        $(this).css({
                            'transform': 'translateY(0)',
                            'box-shadow': '0 1px 3px rgba(0,0,0,0.08)'
                        });
                    } else {
                        // For selected items, maintain selection styles
                        $(this).css({
                            'transform': 'translateY(0)',
                            'box-shadow': '0 4px 12px rgba(40, 167, 69, 0.3)'
                        });
                    }
                    $(this).find('.tomatillo-hover-info').css('opacity', '0');
                }
            );
        }
        
        // Set container height based on layout type
        if (layoutType === 'masonry') {
            // For masonry, set height based on pre-calculated positions
            var maxHeight = Math.max.apply(Math, preCalculatedPositions.map(function(pos) {
                return pos.top + pos.height;
            }));
            debugLog('🔍 DEBUG: Setting masonry container height to:', maxHeight + 'px');
            $('#tomatillo-media-grid').css('height', maxHeight + 'px');
        } else {
            // For grid layout, let it size naturally
            debugLog('🔍 DEBUG: Grid layout - letting container size naturally');
            $('#tomatillo-media-grid').css('height', 'auto');
        }
        
        debugLog('🎨 Pre-calculated masonry layout applied - NO LAYOUT SHIFT!');
        debugLog('🔍 DEBUG: Final container height:', $('#tomatillo-media-grid').css('height'));
        debugLog('Filtered media grid rendered successfully');
    }

    /**
     * Search through all fields of a media item
     */
    function searchInMediaItem(item, searchQuery) {
        if (!item || !searchQuery) return false;
        
        // Fields to search through
        var searchFields = [
            item.filename || '',
            item.title || '',
            item.caption || '',
            item.description || '',
            item.alt || '',
            item.name || '',
            item.slug || ''
        ];
        
        // Search through all fields
        for (var i = 0; i < searchFields.length; i++) {
            if (searchFields[i].toLowerCase().includes(searchQuery)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Update selection UI
     */
    function updateSelectionUI(count, options) {
        var $count = $('#tomatillo-selection-count');
        var $button = $('#tomatillo-select');
        
        if (count === 0) {
            $count.text('No items selected');
            $button.prop('disabled', true);
        } else {
            $count.text(count + ' item' + (count > 1 ? 's' : '') + ' selected');
            $button.prop('disabled', false);
            
            // Focus the SELECT button when items are selected for keyboard accessibility
            setTimeout(function() {
                $button.focus();
            }, 100);
        }
    }
    
    /**
     * Update Load More button visibility and text
     */
    function updateLoadMoreButton(totalFiltered, currentRendered, moreOnServer) {
        var $button = $('#tomatillo-load-more');
        var remainingRendered = totalFiltered - currentRendered;
        
        // Show button if there are unrendered items OR if there might be more on server
        var shouldShow = remainingRendered > 0 || (moreOnServer !== false && hasMoreItemsOnServer);
        
        debugLog('┌─────────────────────────────────────────────┐');
        debugLog('│ 📦 LOAD MORE BUTTON UPDATE                 │');
        debugLog('├─────────────────────────────────────────────┤');
        debugLog('│ Total in memory:        ', currentMediaItems.length.toString().padEnd(20), '│');
        debugLog('│ Total filtered:         ', totalFiltered.toString().padEnd(20), '│');
        debugLog('│ Currently rendered:     ', currentRendered.toString().padEnd(20), '│');
        debugLog('│ Remaining to render:    ', remainingRendered.toString().padEnd(20), '│');
        debugLog('│ More on server?:        ', (hasMoreItemsOnServer ? 'YES' : 'NO').padEnd(20), '│');
        debugLog('│ Should show button?:    ', (shouldShow ? 'YES' : 'NO').padEnd(20), '│');
        debugLog('└─────────────────────────────────────────────┘');
        
        if (shouldShow) {
            if (remainingRendered > 0) {
                var buttonText = 'Load All ' + remainingRendered + ' Remaining Image' + (remainingRendered > 1 ? 's' : '');
                debugLog('📦 ✅ Button VISIBLE: "' + buttonText + '"');
                $button.text(buttonText).show();
            } else {
                // We have more on server but haven't loaded them yet
                debugLog('📦 ✅ Button VISIBLE: "Load All Remaining Images" (need to fetch from server)');
                $button.text('Load All Remaining Images').show();
            }
        } else {
            debugLog('📦 ❌ Button HIDDEN (all items loaded)');
            $button.hide();
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        debugLog('DOM ready, initializing CLEAN TomatilloMediaFrame');
        debugLog('✅ Tomatillo Media Frame: ACF Gallery Handler should be available as ACFGalleryHandler');
        TomatilloMediaFrame.init();
    });

})(jQuery);
