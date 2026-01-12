/**
 * Background Media Loader for Tomatillo Media Inserter
 * Preloads media data in background for instant inserter performance
 */

(function($) {
    'use strict';

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

    // Global cache for preloaded media
    var preloadedMedia = {
        items: [],
        optimizationData: [],
        loaded: false,
        loading: false,
        error: false
    };

    /**
     * Initialize background loading
     */
    function initBackgroundLoader() {
        debugLog('🚀 Initializing background media loader');
        
        // Check if background loading is enabled
        if (!window.tomatilloSettings || !window.tomatilloSettings.background_load_enabled) {
            debugLog('🚀 Background loading disabled in settings');
            return;
        }

        // Wait for page to be fully loaded
        $(document).ready(function() {
            // Wait for WordPress admin to be fully loaded
            $(window).on('load', function() {
                // Additional delay to ensure all admin scripts are done
                setTimeout(function() {
                    debugLog('🚀 WordPress admin fully loaded, starting background media loading...');
                    loadMediaInBackground();
                }, 500);
            });
        });
    }

    /**
     * Load media data in background
     */
    function loadMediaInBackground() {
        if (preloadedMedia.loading || preloadedMedia.loaded) {
            debugLog('🚀 Background loading already in progress or completed');
            return;
        }

        var startTime = performance.now();
        debugLog('🚀 Starting background media loading...');
        preloadedMedia.loading = true;

        // Use infinite_scroll_batch as the default preload count for consistency
        var preloadCount = window.tomatilloSettings ? (window.tomatilloSettings.preload_count || window.tomatilloSettings.infinite_scroll_batch || 100) : 100;
        debugLog('🚀 Preloading', preloadCount, 'media items in background');
        
        // Use WordPress AJAX to fetch media
        var data = {
            action: 'query-attachments',
            query: {
                posts_per_page: preloadCount,
                post_status: 'inherit'
                // Remove post_mime_type restriction to load all file types
            }
        };

        $.post(ajaxurl, data)
            .done(function(response) {
                debugLog('🚀 Background media loaded successfully:', response);
                
                var mediaItems = [];
                if (response && Array.isArray(response)) {
                    mediaItems = response;
                } else if (response && response.data && Array.isArray(response.data)) {
                    mediaItems = response.data;
                } else if (response && response.attachments && Array.isArray(response.attachments)) {
                    mediaItems = response.attachments;
                }

                if (mediaItems.length > 0) {
                    // Check for duplicates in preloaded data
                    var allIds = mediaItems.map(function(item) { return item.id; });
                    var uniqueIds = [...new Set(allIds)];
                    if (allIds.length !== uniqueIds.length) {
                        debugWarn('🚨 Duplicates detected in preloaded data:', allIds.length, '→', uniqueIds.length);
                        
                        // Remove duplicates
                        var seenIds = new Set();
                        mediaItems = mediaItems.filter(function(item) {
                            if (!seenIds.has(item.id)) {
                                seenIds.add(item.id);
                                return true;
                            }
                            return false;
                        });
                        debugLog('🔧 Deduplicated preloaded data:', allIds.length, '→', mediaItems.length);
                    }
                    
                    var loadTime = performance.now() - startTime;
                    preloadedMedia.items = mediaItems;
                    preloadedMedia.loaded = true;
                    preloadedMedia.loading = false;
                    
                    debugLog('🚀 Preloaded', mediaItems.length, 'media items in', loadTime.toFixed(2), 'ms');
                    debugLog('🚀 Media inserter will now open INSTANTLY!');
                    
                    // Load optimization data in background
                    loadOptimizationDataInBackground(mediaItems);
                } else {
                    debugLog('🚀 No media items found for preloading');
                    preloadedMedia.loading = false;
                }
            })
            .fail(function(xhr, status, error) {
                debugError('🚀 Background media loading failed:', error);
                preloadedMedia.error = true;
                preloadedMedia.loading = false;
            });
    }

    /**
     * Load optimization data in background
     */
    function loadOptimizationDataInBackground(mediaItems) {
        debugLog('🚀 Loading optimization data for', mediaItems.length, 'items');
        
        var optimizationPromises = mediaItems.map(function(item) {
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: ajaxurl,
                    type: 'GET',
                    data: {
                        action: 'tomatillo_get_image_data',
                        image_id: item.id,
                        nonce: window.tomatillo_nonce || 'test'
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            resolve(null);
                        }
                    },
                    error: function() {
                        resolve(null);
                    }
                });
            });
        });

        Promise.all(optimizationPromises).then(function(optimizationDataArray) {
            preloadedMedia.optimizationData = optimizationDataArray;
            debugLog('🚀 Preloaded optimization data for', optimizationDataArray.length, 'items');
        }).catch(function(error) {
            debugError('🚀 Optimization data loading failed:', error);
        });
    }

    /**
     * Get preloaded media data
     */
    function getPreloadedMedia() {
        return preloadedMedia;
    }

    /**
     * Check if media is preloaded
     */
    function isMediaPreloaded() {
        return preloadedMedia.loaded && preloadedMedia.items.length > 0;
    }

    /**
     * Clear preloaded data (for testing)
     */
    function clearPreloadedMedia() {
        preloadedMedia = {
            items: [],
            optimizationData: [],
            loaded: false,
            loading: false,
            error: false
        };
        debugLog('🚀 Cleared preloaded media data');
    }

    // Expose functions globally
    window.TomatilloBackgroundLoader = {
        init: initBackgroundLoader,
        getPreloadedMedia: getPreloadedMedia,
        isMediaPreloaded: isMediaPreloaded,
        clearPreloadedMedia: clearPreloadedMedia
    };

    // Auto-initialize
    initBackgroundLoader();

})(jQuery);
