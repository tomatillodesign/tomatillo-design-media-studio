/**
 * Tomatillo Custom Media Frame - Clean Version
 * Clean markup, wider images (300px min), proper masonry layout
 */

(function($) {
    'use strict';

    // Global variables
    var selectedItems = [];

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
     * Get optimization data for an image from Media Studio database
     */
    function getOptimizationData(imageId) {
        console.log('🌐 Making AJAX request for image', imageId);
        console.log('🌐 AJAX URL:', ajaxurl);
        console.log('🌐 Nonce:', tomatillo_nonce);
        
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
                    console.log('📥 AJAX response for image', imageId, ':', response);
                    console.log('📥 AJAX response.data keys:', response.data ? Object.keys(response.data) : 'no data');
                    console.log('📥 AJAX response.data.avif_url:', response.data ? response.data.avif_url : 'no avif_url');
                    console.log('📥 AJAX response.data.webp_url:', response.data ? response.data.webp_url : 'no webp_url');
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        console.log('❌ AJAX failed for image', imageId, ':', response.data);
                        reject(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('💥 AJAX error for image', imageId, ':', error);
                    console.log('💥 XHR response:', xhr.responseText);
                    reject(error);
                }
            });
        });
    }

    /**
     * Get HI-RES image using Media Studio optimization data
     */
    function getHiResImageWithOptimization(item, optimizationData) {
        console.log('🚀 getHiResImageWithOptimization called for item:', item.id);
        console.log('Optimization data:', optimizationData);
        console.log('Optimization data keys:', optimizationData ? Object.keys(optimizationData) : 'no data');
        console.log('Optimization data.avif_url:', optimizationData ? optimizationData.avif_url : 'no avif_url');
        console.log('Optimization data.webp_url:', optimizationData ? optimizationData.webp_url : 'no webp_url');
        console.log('Optimization data.file_size:', optimizationData ? optimizationData.file_size : 'no file_size');
        console.log('Optimization data.dimensions:', optimizationData ? optimizationData.dimensions : 'no dimensions');
        console.log('Optimization data.space_saved:', optimizationData ? optimizationData.space_saved : 'no space_saved');
        console.log('Optimization data.is_optimized:', optimizationData ? optimizationData.is_optimized : 'no is_optimized');
        console.log('Optimization data.avif_file_size:', optimizationData ? optimizationData.avif_file_size : 'no avif_file_size');
        console.log('Optimization data.webp_file_size:', optimizationData ? optimizationData.webp_file_size : 'no webp_file_size');
        console.log('Optimization data.smallest_file_size:', optimizationData ? optimizationData.smallest_file_size : 'no smallest_file_size');
        
        if (!optimizationData) {
            // No optimization data, fall back to regular method
            console.log('❌ No optimization data, falling back to regular method');
            return getHiResImage(item);
        }
        
        // Check for AVIF first (smallest file size)
        if (optimizationData.avif_url) {
            console.log('✅ Using AVIF image:', optimizationData.avif_url);
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
            console.log('✅ Using WebP image:', optimizationData.webp_url);
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
        console.log('❌ No AVIF/WebP found, falling back to regular method');
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
        console.log('🚀 getHiResImage called for item:', item.id);
        // Use Media Studio's get_best_optimized_image_url logic
        // For now, use WordPress scaled as fallback, but prioritize AVIF/WebP when available
        
        console.log('=== getHiResImage DEBUG ===');
        console.log('Item object keys:', Object.keys(item));
        console.log('Item filesizeHumanReadable:', item.filesizeHumanReadable);
        console.log('Item filesizeInBytes:', item.filesizeInBytes);
        console.log('Sizes object:', item.sizes);
        console.log('Sizes keys:', Object.keys(item.sizes || {}));
        console.log('===========================');
        
        var sizes = item.sizes || {};
        var originalUrl = item.url;
        
        // Check for AVIF/WebP in sizes first (Media Studio might have added these)
        var avifUrl = null;
        var webpUrl = null;
        
        // Look for AVIF/WebP versions in sizes
        for (var sizeName in sizes) {
            console.log('Checking size:', sizeName, sizes[sizeName]);
            if (sizeName.includes('avif') && sizes[sizeName].url) {
                avifUrl = sizes[sizeName].url;
                console.log('Found AVIF in sizes:', sizeName, avifUrl);
            }
            if (sizeName.includes('webp') && sizes[sizeName].url) {
                webpUrl = sizes[sizeName].url;
                console.log('Found WebP in sizes:', sizeName, webpUrl);
            }
        }
        
        console.log('AVIF URL found:', avifUrl);
        console.log('WebP URL found:', webpUrl);
        
        // Priority: AVIF > WebP > Scaled > Original
        if (avifUrl) {
            console.log('Using AVIF image:', avifUrl);
            // Find the AVIF size object to get its dimensions
            var avifSize = null;
            for (var sizeName in sizes) {
                if (sizeName.includes('avif') && sizes[sizeName].url === avifUrl) {
                    avifSize = sizes[sizeName];
                    console.log('AVIF size object:', avifSize);
            console.log('AVIF size object keys:', Object.keys(avifSize || {}));
            console.log('AVIF filesizeHumanReadable:', avifSize ? avifSize.filesizeHumanReadable : 'not found');
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
            console.log('Using WebP image:', webpUrl);
            // Find the WebP size object to get its dimensions
            var webpSize = null;
            for (var sizeName in sizes) {
                if (sizeName.includes('webp') && sizes[sizeName].url === webpUrl) {
                    webpSize = sizes[sizeName];
                    console.log('WebP size object:', webpSize);
            console.log('WebP size object keys:', Object.keys(webpSize || {}));
            console.log('WebP filesizeHumanReadable:', webpSize ? webpSize.filesizeHumanReadable : 'not found');
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
            console.log('Using scaled image:', sizes.scaled.url);
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
        console.log('Using original image:', originalUrl);
        console.log('Full size object:', sizes.full);
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
        console.log('cleanFilename input:', filename);
        
        // Remove WordPress suffixes: -scaled, -1024x683, etc.
        var cleaned = filename
            .replace(/-scaled\.(jpg|jpeg|png|gif|webp|avif)$/i, '')  // Remove -scaled.ext
            .replace(/-\d+x\d+\.(jpg|jpeg|png|gif|webp|avif)$/i, '') // Remove -1024x683.ext
            .replace(/\.(jpg|jpeg|png|gif|webp|avif)$/i, '');        // Remove any remaining .ext
            
        console.log('cleanFilename output:', cleaned);
        return cleaned;
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
        
        // Fetch optimization data for all images first
        console.log('🔍 Starting optimization data fetch for', mediaItems.length, 'items');
        var optimizationPromises = mediaItems.map(function(item) {
            console.log('📡 Fetching optimization data for item', item.id);
            return getOptimizationData(item.id).then(function(data) {
                console.log('✅ Got optimization data for item', item.id, ':', data);
                return data;
            }).catch(function(error) {
                console.log('❌ No optimization data for item', item.id, error);
                return null; // Return null if no optimization data
            });
        });
        
        Promise.all(optimizationPromises).then(function(optimizationDataArray) {
            console.log('🎯 All optimization data fetched:', optimizationDataArray);
            var gridHtml = '';
            
            mediaItems.forEach(function(item, index) {
                var optimizationData = optimizationDataArray[index];
                console.log('🖼️ Processing item', item.id, 'with optimization data:', optimizationData);
                var hiResImage = getHiResImageWithOptimization(item, optimizationData);
                console.log('🖼️ Final hiResImage for item', item.id, ':', hiResImage);
            
            var originalFilename = item.filename || item.title || 'Unknown';
            var filename = cleanFilename(originalFilename);
            console.log('=== FILENAME DEBUG ===');
            console.log('Item object:', item);
            console.log('item.filename:', item.filename);
            console.log('item.title:', item.title);
            console.log('Original filename:', originalFilename);
            console.log('Cleaned filename:', filename);
            console.log('========================');
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
                } else {
                    // For selected items, don't override the selection styles
                    $(this).css({
                        'transform': 'translateY(0)',
                        'box-shadow': '0 4px 12px rgba(40, 167, 69, 0.3)',
                        'border-color': '#28a745'
                    });
                }
                $(this).find('.tomatillo-hover-info').css('opacity', '0');
            }
        );
        
        console.log('Media grid rendered successfully');
        
        // Setup event handlers after rendering
        setupEventHandlers(options);
        }).catch(function(error) {
            console.error('Error fetching optimization data:', error);
            // Fallback to rendering without optimization data
            renderMediaGridFallback(mediaItems, options);
        });
    }
    
    /**
     * Fallback rendering without optimization data
     */
    function renderMediaGridFallback(mediaItems, options) {
        var gridHtml = '';
        
        mediaItems.forEach(function(item) {
            // Get HI-RES image using Media Studio logic
            var hiResImage = getHiResImage(item);
            
            var originalFilename = item.filename || item.title || 'Unknown';
            var filename = cleanFilename(originalFilename);
            console.log('Original filename:', originalFilename, 'Cleaned:', filename);
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
        console.log('Media grid rendered successfully (fallback)');
        
        // Setup event handlers after rendering
        setupEventHandlers(options);
    }

    /**
     * Setup event handlers for the modal
     */
    function setupEventHandlers(options) {
        
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
            console.log('🎯 Media item clicked!');
            console.log('🎯 Event target:', this);
            console.log('🎯 jQuery object:', $(this));
            var itemId = $(this).data('id');
            var $item = $(this);
            console.log('🎯 Item ID:', itemId);
            console.log('🎯 Options multiple:', options.multiple);
            console.log('🎯 Current selectedItems:', selectedItems);
            console.log('🎯 selectedItems array length:', selectedItems.length);
            console.log('🎯 selectedItems contents:', selectedItems);
            
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
                // Single selection - check if clicking the same item again
                if ($item.hasClass('selected')) {
                    console.log('🎯 Deselecting item');
                    // Deselect - remove dimming and clear selection
                    $('#tomatillo-media-grid').removeClass('tomatillo-single-selection');
                    $item.removeClass('selected');
                    selectedItems = [];
                    
                    // Reset styles to default
                    $item.css({
                        'transform': 'translateY(0)',
                        'box-shadow': '0 1px 3px rgba(0,0,0,0.08)',
                        'border-color': 'transparent'
                    });
                } else {
                    console.log('🎯 Selecting item');
                    // Select - add dimming class and clear all other selections
                    $('#tomatillo-media-grid').addClass('tomatillo-single-selection');
                    $('.tomatillo-media-item').removeClass('selected');
                    $item.addClass('selected');
                    selectedItems = [itemId];
                    
                    // Apply selection styles directly
                    $item.css({
                        'transform': 'translateY(0)',
                        'box-shadow': '0 4px 12px rgba(40, 167, 69, 0.3)',
                        'border-color': '#28a745'
                    });
                }
            }
            
            console.log('🎯 After selection logic, selectedItems:', selectedItems);
            console.log('🎯 After selection logic, length:', selectedItems.length);
            
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
        console.log('🎯 Updating selection UI, count:', count);
        console.log('🎯 Options:', options);
        var $count = $('#tomatillo-selection-count');
        var $button = $('#tomatillo-select');
        console.log('🎯 Count element:', $count);
        console.log('🎯 Button element:', $button);
        console.log('🎯 Count element length:', $count.length);
        console.log('🎯 Button element length:', $button.length);
        console.log('🎯 Count element text before:', $count.text());
        
        if (count === 0) {
            $count.text('No items selected');
            $button.prop('disabled', true);
        } else {
            $count.text(count + ' item' + (count > 1 ? 's' : '') + ' selected');
            $button.prop('disabled', false);
        }
        
        console.log('🎯 Count element text after:', $count.text());
        console.log('🎯 Count element is visible:', $count.is(':visible'));
        console.log('🎯 Count element offset:', $count.offset());
        console.log('🎯 Count element height:', $count.height());
        console.log('🎯 Count element width:', $count.width());
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('DOM ready, initializing CLEAN TomatilloMediaFrame');
        TomatilloMediaFrame.init();
    });

})(jQuery);
