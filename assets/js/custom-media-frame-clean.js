/**
 * Tomatillo Custom Media Frame - Clean Version
 * Clean markup, wider images (300px min), proper masonry layout
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
        console.log('Tomatillo Media Studio: Initializing CLEAN custom media frame');
        initializeCustomMediaFrame();
    });

    function initializeCustomMediaFrame() {
        
        try {
            console.log('Starting CLEAN custom media frame initialization...');
            
            /**
             * Clean Media Frame Manager
             */
            window.TomatilloMediaFrame = {
                
                /**
                 * Open our custom media frame
                 */
                open: function(options) {
                    console.log('CLEAN TomatilloMediaFrame.open called with options:', options);
                    
                    options = options || {};
                    
                    // Create clean modal HTML
                    var modalHtml = createModalHTML(options);
                    
                    // Add modal to page
                    $('body').append(modalHtml);
                    
                    // Add responsive CSS
                    addResponsiveCSS();
                    
                    console.log('CLEAN custom modal added to page');
                    
                    // Initialize the media grid
                    initializeMediaGrid(options);
                    
                    // Handle events
                    setupEventHandlers(options);
                    
                    console.log('CLEAN custom media frame opened');
                    
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
                    console.log('CLEAN TomatilloMediaFrame.init called');
                    
                    // Intercept classic editor calls
                    this.interceptClassicEditor();
                    
                    console.log('CLEAN Tomatillo Media Frame initialized');
                }
            };

            console.log('CLEAN TomatilloMediaFrame created successfully');
            
        } catch (error) {
            console.error('Error creating CLEAN TomatilloMediaFrame:', error);
            console.error('Stack trace:', error.stack);
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
                        <input type="text" id="tomatillo-search" placeholder="Search media..." class="tomatillo-search">
                        <select id="tomatillo-filter" class="tomatillo-filter">
                            <option value="all">All Types</option>
                            <option value="image">Images</option>
                            <option value="video">Videos</option>
                            <option value="audio">Audio</option>
                            <option value="application">Documents</option>
                        </select>
                    </div>
                    
                    <!-- Media Grid Container -->
                    <div class="tomatillo-grid-container">
                        <div id="tomatillo-media-grid" class="tomatillo-masonry-grid">
                            <div class="tomatillo-loading">Loading media...</div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="tomatillo-footer">
                        <div id="tomatillo-selection-count" class="tomatillo-selection-count">No items selected</div>
                        <div class="tomatillo-actions">
                            <button id="tomatillo-cancel" class="tomatillo-btn tomatillo-btn-cancel">Cancel</button>
                            <button id="tomatillo-select" class="tomatillo-btn tomatillo-btn-select" disabled>Select</button>
                        </div>
                    </div>
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
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                
                .tomatillo-search {
                    flex: 1;
                    padding: 10px 15px;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    font-size: 14px;
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
                    columns: 4;
                    column-gap: 20px;
                    padding: 0;
                }
                
                .tomatillo-media-item {
                    break-inside: avoid;
                    margin-bottom: 20px;
                    background: white;
                    border-radius: 6px;
                    overflow: hidden;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
                    position: relative;
                    min-width: 360px;
                    border: 2px solid transparent;
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
                    opacity: 0.33;
                }
                
                .tomatillo-single-selection .tomatillo-media-item.selected {
                    opacity: 1;
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
                
                .tomatillo-selection-count {
                    color: #666;
                    font-size: 14px;
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
                }
                
                .tomatillo-btn-select {
                    background: #667eea;
                    color: white;
                    opacity: 0.5;
                }
                
                .tomatillo-btn-select:not(:disabled) {
                    opacity: 1;
                }
                
                .tomatillo-loading {
                    text-align: center;
                    padding: 40px;
                    color: #666;
                    font-size: 16px;
                }
                
                /* Responsive */
                @media (max-width: 1600px) {
                    .tomatillo-masonry-grid { columns: 4 !important; }
                }
                @media (max-width: 1400px) {
                    .tomatillo-masonry-grid { columns: 3 !important; }
                }
                @media (max-width: 1200px) {
                    .tomatillo-masonry-grid { columns: 3 !important; }
                }
                @media (max-width: 1000px) {
                    .tomatillo-masonry-grid { columns: 2 !important; }
                }
                @media (max-width: 800px) {
                    .tomatillo-masonry-grid { columns: 1 !important; }
                }
            </style>
        `);
    }

    /**
     * Initialize the media grid with real WordPress media
     */
    function initializeMediaGrid(options) {
        console.log('Initializing media grid...');
        
        // Use WordPress AJAX to fetch media directly
        var data = {
            action: 'query-attachments',
            query: {
                post_mime_type: 'image',
                posts_per_page: 50,
                post_status: 'inherit'
            }
        };
        
        console.log('Fetching media with data:', data);
        
        // Make AJAX request to WordPress
        $.post(ajaxurl, data)
            .done(function(response) {
                console.log('Raw WordPress response:', response);
                console.log('Response type:', typeof response);
                console.log('Response length:', response ? response.length : 'undefined');
                
                // Handle different response formats
                var mediaItems = [];
                if (Array.isArray(response)) {
                    mediaItems = response;
                } else if (response && response.data && Array.isArray(response.data)) {
                    mediaItems = response.data;
                } else if (response && response.attachments && Array.isArray(response.attachments)) {
                    mediaItems = response.attachments;
                } else {
                    console.error('Unexpected response format:', response);
                    mediaItems = [];
                }
                
                console.log('Media fetched successfully, count:', mediaItems.length);
                renderMediaGrid(mediaItems, options);
            })
            .fail(function(xhr, status, error) {
                console.error('Error fetching media:', error);
                console.error('Response:', xhr.responseText);
                // Fallback to empty grid
                $('#tomatillo-media-grid').html(`
                    <div class="tomatillo-loading" style="color: #dc3545;">
                        Failed to load media. Please try again.
                    </div>
                `);
            });
    }

    /**
     * Get HI-RES image with AVIF/WEBP preference
     */
    function getHiResImage(item) {
        var sizes = item.sizes || {};
        var originalUrl = item.url;
        
        // Priority order: AVIF > WEBP > Large > Medium > Full > Original
        var preferredSizes = [
            'avif-large', 'avif-medium', 'avif-full',
            'webp-large', 'webp-medium', 'webp-full',
            'large', 'medium-large', 'medium', 'full'
        ];
        
        // Check for AVIF/WEBP versions first
        for (var i = 0; i < preferredSizes.length; i++) {
            var sizeName = preferredSizes[i];
            if (sizes[sizeName] && sizes[sizeName].url) {
                console.log('Using HI-RES image:', sizeName, sizes[sizeName].url);
                return {
                    url: sizes[sizeName].url,
                    width: sizes[sizeName].width,
                    height: sizes[sizeName].height,
                    format: sizeName.includes('avif') ? 'AVIF' : sizeName.includes('webp') ? 'WEBP' : 'JPEG'
                };
            }
        }
        
        // Fallback to original
        console.log('Using original image:', originalUrl);
        return {
            url: originalUrl,
            width: item.width,
            height: item.height,
            format: 'Original'
        };
    }

    /**
     * Render the media grid with real WordPress media
     */
    function renderMediaGrid(mediaItems, options) {
        console.log('Rendering media grid with', mediaItems ? mediaItems.length : 'undefined', 'items');
        
        // Safety check
        if (!Array.isArray(mediaItems)) {
            console.error('mediaItems is not an array:', mediaItems);
            $('#tomatillo-media-grid').html(`
                <div class="tomatillo-loading" style="color: #dc3545;">
                    Error: Invalid media data format
                </div>
            `);
            return;
        }
        
        var gridHtml = '';
        
        mediaItems.forEach(function(item) {
            // Get HI-RES image with AVIF/WEBP preference
            var hiResImage = getHiResImage(item);
            
            var filename = item.filename || item.title || 'Unknown';
            var filesize = item.filesizeHumanReadable || 'Unknown size';
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
                        <div class="details">${filesize} • ${hiResImage.format}</div>
                    </div>
                </div>
            `;
        });
        
        $('#tomatillo-media-grid').html(gridHtml);
        
        // Add hover effects
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
                }
                $(this).find('.tomatillo-hover-info').css('opacity', '0');
            }
        );
        
        console.log('Media grid rendered successfully');
    }

    /**
     * Setup event handlers for the modal
     */
    function setupEventHandlers(options) {
        var selectedItems = [];
        
        // Handle close button
        $('#tomatillo-close-modal').on('click', function() {
            console.log('Close button clicked');
            $('#tomatillo-custom-modal').remove();
        });
        
        // Handle cancel button
        $('#tomatillo-cancel').on('click', function() {
            console.log('Cancel button clicked');
            $('#tomatillo-custom-modal').remove();
        });
        
        // Handle media item selection
        $(document).on('click', '.tomatillo-media-item', function() {
            var itemId = $(this).data('id');
            var $item = $(this);
            
            if (options.multiple) {
                // Multiple selection
                $('#tomatillo-media-grid').removeClass('tomatillo-single-selection');
                if ($item.hasClass('selected')) {
                    $item.removeClass('selected');
                    selectedItems = selectedItems.filter(id => id !== itemId);
                } else {
                    $item.addClass('selected');
                    selectedItems.push(itemId);
                }
            } else {
                // Single selection - add dimming class and clear all selections
                $('#tomatillo-media-grid').addClass('tomatillo-single-selection');
                $('.tomatillo-media-item').removeClass('selected');
                $item.addClass('selected');
                selectedItems = [itemId];
            }
            
            // Update selection count and button state
            updateSelectionUI(selectedItems.length, options);
        });
        
        // Handle select button
        $('#tomatillo-select').on('click', function() {
            if (selectedItems.length > 0) {
                console.log('Select button clicked, selected items:', selectedItems);
                
                // Create selection data
                var selection = selectedItems.map(function(id) {
                    // Find the media item
                    var query = wp.media.query();
                    var item = query.get(id);
                    return item ? item.toJSON() : { id: id };
                });
                
                console.log('Selection created:', selection);
                
                // Call the onSelect callback
                if (options.onSelect) {
                    options.onSelect(selection);
                }
                
                // Close modal
                $('#tomatillo-custom-modal').remove();
            }
        });
        
        // Handle clicking outside modal
        $('#tomatillo-custom-modal').on('click', function(e) {
            if (e.target.id === 'tomatillo-custom-modal') {
                console.log('Clicked outside modal');
                $('#tomatillo-custom-modal').remove();
            }
        });
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
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('DOM ready, initializing CLEAN TomatilloMediaFrame');
        TomatilloMediaFrame.init();
    });

})(jQuery);
