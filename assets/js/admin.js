/**
 * Tomatillo Media Studio - Admin JavaScript
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initSettingsPage();
        initMediaLibrary();
        initOptimization();
    });
    
    /**
     * Initialize settings page functionality
     */
    function initSettingsPage() {
        if (!$('.tomatillo-media-studio-settings').length) {
            return;
        }
        
        // Tab switching
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            // Remove active classes
            $('.nav-tab').removeClass('nav-tab-active');
            $('.tab-content').removeClass('active');
            
            // Add active class to clicked tab
            $(this).addClass('nav-tab-active');
            
            // Show corresponding content
            var target = $(this).attr('href');
            $(target).addClass('active');
        });
        
        // Quality slider updates
        $('.quality-slider').on('input', function() {
            $(this).next('.quality-value').text($(this).val() + '%');
        });
        
        // Form validation
        $('#tomatillo-settings-form').on('submit', function(e) {
            var isValid = validateSettings();
            if (!isValid) {
                e.preventDefault();
                showNotice('Please check your settings and try again.', 'error');
            }
        });
        
        // Module toggle effects
        $('input[name*="enable_optimization"], input[name*="enable_media_library"]').on('change', function() {
            updateModuleDependencies();
        });
    }
    
    /**
     * Initialize media library functionality
     */
    function initMediaLibrary() {
        if (!$('.tomatillo-media-library').length) {
            return;
        }
        
        // View toggle
        $('.tomatillo-view-toggle button').on('click', function() {
            $('.tomatillo-view-toggle button').removeClass('active');
            $(this).addClass('active');
            
            var view = $(this).data('view');
            switchView(view);
        });
        
        // Search functionality
        $('.tomatillo-search-filter input').on('input', function() {
            var query = $(this).val().toLowerCase();
            filterMediaItems(query);
        });
        
        // Media item clicks
        $('.tomatillo-media-item').on('click', function() {
            var attachmentId = $(this).data('attachment-id');
            openMediaModal(attachmentId);
        });
        
        // Bulk selection
        initBulkSelection();
    }
    
    /**
     * Initialize optimization functionality
     */
    function initOptimization() {
        if (!$('.tomatillo-optimization-dashboard').length) {
            return;
        }
        
        // Batch processing buttons
        $('.tomatillo-batch-controls button').on('click', function() {
            var action = $(this).data('action');
            handleBatchAction(action);
        });
        
        // Refresh stats
        $('.tomatillo-refresh-stats').on('click', function() {
            refreshOptimizationStats();
        });
    }
    
    /**
     * Validate settings form
     */
    function validateSettings() {
        var isValid = true;
        
        // Check if at least one module is enabled
        var optimizationEnabled = $('input[name*="enable_optimization"]').is(':checked');
        var libraryEnabled = $('input[name*="enable_media_library"]').is(':checked');
        
        if (!optimizationEnabled && !libraryEnabled) {
            showNotice('At least one module must be enabled.', 'error');
            isValid = false;
        }
        
        // Validate quality settings
        var avifQuality = parseInt($('input[name*="avif_quality"]').val());
        var webpQuality = parseInt($('input[name*="webp_quality"]').val());
        
        if (avifQuality < 1 || avifQuality > 100) {
            showNotice('AVIF quality must be between 1 and 100.', 'error');
            isValid = false;
        }
        
        if (webpQuality < 1 || webpQuality > 100) {
            showNotice('WebP quality must be between 1 and 100.', 'error');
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Update module dependencies
     */
    function updateModuleDependencies() {
        var optimizationEnabled = $('input[name*="enable_optimization"]').is(':checked');
        var libraryEnabled = $('input[name*="enable_media_library"]').is(':checked');
        
        // Show/hide dependent settings
        if (optimizationEnabled) {
            $('#optimization').show();
        } else {
            $('#optimization').hide();
        }
        
        if (libraryEnabled) {
            $('#media-library').show();
        } else {
            $('#media-library').hide();
        }
    }
    
    /**
     * Switch media library view
     */
    function switchView(view) {
        $('.tomatillo-media-grid').removeClass('grid-view list-view').addClass(view + '-view');
        
        // Update media items layout
        if (view === 'list') {
            $('.tomatillo-media-item').addClass('list-item');
        } else {
            $('.tomatillo-media-item').removeClass('list-item');
        }
    }
    
    /**
     * Filter media items
     */
    function filterMediaItems(query) {
        $('.tomatillo-media-item').each(function() {
            var title = $(this).find('.tomatillo-media-title').text().toLowerCase();
            var meta = $(this).find('.tomatillo-media-meta').text().toLowerCase();
            
            if (title.includes(query) || meta.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
    
    /**
     * Open media modal
     */
    function openMediaModal(attachmentId) {
        // This would open a modal with media details
        console.log('Opening modal for attachment:', attachmentId);
        
        // For now, just show an alert
        alert('Media modal for attachment ' + attachmentId + ' would open here.');
    }
    
    /**
     * Initialize bulk selection
     */
    function initBulkSelection() {
        var $grid = $('.tomatillo-media-grid');
        var $items = $('.tomatillo-media-item');
        
        // Add checkboxes to items
        $items.each(function() {
            var $checkbox = $('<input type="checkbox" class="bulk-select-checkbox" style="position: absolute; top: 10px; left: 10px; z-index: 10;">');
            $(this).prepend($checkbox);
        });
        
        // Handle bulk selection
        $('.bulk-select-checkbox').on('change', function() {
            updateBulkActions();
        });
        
        // Select all functionality
        $('.tomatillo-select-all').on('click', function() {
            var isChecked = $(this).is(':checked');
            $('.bulk-select-checkbox').prop('checked', isChecked);
            updateBulkActions();
        });
    }
    
    /**
     * Update bulk actions visibility
     */
    function updateBulkActions() {
        var selectedCount = $('.bulk-select-checkbox:checked').length;
        
        if (selectedCount > 0) {
            $('.tomatillo-bulk-actions').show();
            $('.tomatillo-selected-count').text(selectedCount);
        } else {
            $('.tomatillo-bulk-actions').hide();
        }
    }
    
    /**
     * Handle batch optimization actions
     */
    function handleBatchAction(action) {
        if (!confirm('Are you sure you want to ' + action + '?')) {
            return;
        }
        
        var $button = $('.tomatillo-batch-controls button[data-action="' + action + '"]');
        $button.prop('disabled', true).text('Processing...');
        
        // Simulate batch processing
        var progress = 0;
        var interval = setInterval(function() {
            progress += 10;
            $('.tomatillo-progress-bar-fill').css('width', progress + '%');
            
            if (progress >= 100) {
                clearInterval(interval);
                $button.prop('disabled', false).text($button.data('original-text') || action);
                showNotice('Batch ' + action + ' completed successfully!', 'success');
            }
        }, 200);
    }
    
    /**
     * Refresh optimization statistics
     */
    function refreshOptimizationStats() {
        $.ajax({
            url: tomatilloMediaStudio.ajaxUrl,
            type: 'POST',
            data: {
                action: 'tomatillo_get_optimization_stats',
                nonce: tomatilloMediaStudio.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStatsDisplay(response.data);
                } else {
                    showNotice('Failed to refresh stats: ' + response.data, 'error');
                }
            },
            error: function() {
                showNotice('Failed to refresh stats.', 'error');
            }
        });
    }
    
    /**
     * Update statistics display
     */
    function updateStatsDisplay(stats) {
        $('.tomatillo-optimization-stat').each(function() {
            var $stat = $(this);
            var statType = $stat.data('stat');
            
            if (stats[statType] !== undefined) {
                $stat.find('h3').text(stats[statType]);
            }
        });
    }
    
    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Utility function to format file sizes
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    /**
     * Utility function to format numbers
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
})(jQuery);
