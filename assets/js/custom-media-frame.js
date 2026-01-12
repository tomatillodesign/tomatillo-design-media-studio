/**
 * Tomatillo Custom Media Frame
 * Option 2: Custom frame using core internals
 * Replaces the middle pane (grid + controls) while keeping core selection/toolbar/sidebar
 */

(function($) {
    'use strict';

    // Wait for wp.media to be available
    function waitForWpMedia(callback) {
        if (typeof wp !== 'undefined' && wp.media && wp.media.view) {
            callback();
        } else {
            setTimeout(function() {
                waitForWpMedia(callback);
            }, 100);
        }
    }

    // Initialize when wp.media is ready
    waitForWpMedia(function() {
        console.log('Tomatillo Media Studio: wp.media is ready, initializing custom frame');
        initializeCustomMediaFrame();
    });

    function initializeCustomMediaFrame() {

    /**
     * Custom Attachments Browser View
     * Replaces the default grid with our masonry layout
     */
    wp.media.view.TomatilloAttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
        
        className: 'tomatillo-attachments-browser',
        
        template: wp.template('tomatillo-attachments-browser'),
        
        events: {
            'click .tomatillo-media-item': 'toggleSelection',
            'input .tomatillo-search-input': 'handleSearch',
            'change .tomatillo-filter-select': 'handleFilter',
            'scroll .tomatillo-attachments-container': 'handleScroll'
        },
        
        initialize: function(options) {
            this.options = options || {};
            this.selection = options.selection;
            this.frame = options.frame;
            this.multiple = options.multiple || false;
            this.allowedTypes = options.allowedTypes || [];
            
            // Call parent initialize
            wp.media.view.AttachmentsBrowser.prototype.initialize.apply(this, arguments);
            
            // Initialize our custom features
            this.initMasonry();
            this.initInfiniteScroll();
            this.bindEvents();
        },
        
        render: function() {
            // Call parent render
            wp.media.view.AttachmentsBrowser.prototype.render.apply(this, arguments);
            
            // Apply our custom styling and layout
            this.applyCustomLayout();
            
            return this;
        },
        
        /**
         * Initialize masonry layout
         */
        initMasonry: function() {
            var self = this;
            
            // Wait for attachments to be rendered
            this.collection.on('reset', function() {
                setTimeout(function() {
                    self.layoutMasonry();
                }, 100);
            });
        },
        
        /**
         * Apply masonry layout to attachments
         */
        layoutMasonry: function() {
            var $container = this.$('.tomatillo-attachments-container');
            var $items = this.$('.tomatillo-media-item');
            
            if ($items.length === 0) return;
            
            // Simple masonry implementation
            var containerWidth = $container.width();
            var itemWidth = 200; // Fixed width for now
            var columns = Math.floor(containerWidth / itemWidth);
            var columnHeights = new Array(columns).fill(0);
            
            $items.each(function() {
                var $item = $(this);
                var shortestColumn = columnHeights.indexOf(Math.min(...columnHeights));
                
                $item.css({
                    position: 'absolute',
                    left: shortestColumn * itemWidth,
                    top: columnHeights[shortestColumn],
                    width: itemWidth
                });
                
                columnHeights[shortestColumn] += $item.outerHeight();
            });
            
            // Set container height
            $container.css('height', Math.max(...columnHeights));
        },
        
        /**
         * Initialize infinite scroll
         */
        initInfiniteScroll: function() {
            var self = this;
            var isLoading = false;
            
            this.$('.tomatillo-attachments-container').on('scroll', function() {
                var $container = $(this);
                var scrollTop = $container.scrollTop();
                var scrollHeight = $container[0].scrollHeight;
                var containerHeight = $container.height();
                
                // Load more when near bottom
                if (scrollTop + containerHeight >= scrollHeight - 100 && !isLoading) {
                    if (self.collection.hasMore()) {
                        isLoading = true;
                        self.collection.more().done(function() {
                            isLoading = false;
                        });
                    }
                }
            });
        },
        
        /**
         * Bind custom events
         */
        bindEvents: function() {
            var self = this;
            
            // Listen to selection changes
            this.selection.on('add remove reset', function() {
                self.updateSelectionUI();
            });
            
            // Listen to collection changes
            this.collection.on('add remove reset', function() {
                self.layoutMasonry();
            });
        },
        
        /**
         * Toggle selection of a media item
         */
        toggleSelection: function(e) {
            e.preventDefault();
            
            var $item = $(e.currentTarget);
            var attachmentId = parseInt($item.data('attachment-id'));
            var attachment = this.collection.get(attachmentId);
            
            if (!attachment) return;
            
            if (this.selection.get(attachmentId)) {
                this.selection.remove(attachment);
            } else {
                if (!this.multiple) {
                    this.selection.reset();
                }
                this.selection.add(attachment);
            }
        },
        
        /**
         * Handle search input
         */
        handleSearch: function(e) {
            var query = $(e.target).val();
            
            // Debounce search
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(function() {
                this.collection.props.set('search', query);
                this.collection.fetch();
            }.bind(this), 300);
        },
        
        /**
         * Handle filter changes
         */
        handleFilter: function(e) {
            var filter = $(e.target).val();
            
            if (filter === 'all') {
                this.collection.props.unset('type');
            } else {
                this.collection.props.set('type', filter);
            }
            
            this.collection.fetch();
        },
        
        /**
         * Update selection UI
         */
        updateSelectionUI: function() {
            var self = this;
            
            this.$('.tomatillo-media-item').each(function() {
                var $item = $(this);
                var attachmentId = parseInt($item.data('attachment-id'));
                
                if (self.selection.get(attachmentId)) {
                    $item.addClass('selected');
                } else {
                    $item.removeClass('selected');
                }
            });
        },
        
        /**
         * Apply custom layout and styling
         */
        applyCustomLayout: function() {
            var $container = this.$('.tomatillo-attachments-container');
            
            // Add custom classes
            $container.addClass('tomatillo-masonry-grid');
            
            // Add custom CSS if not already added
            if (!$('#tomatillo-media-frame-styles').length) {
                $('head').append(`
                    <style id="tomatillo-media-frame-styles">
                        .tomatillo-attachments-browser {
                            height: 100%;
                            display: flex;
                            flex-direction: column;
                        }
                        
                        .tomatillo-attachments-header {
                            padding: 15px;
                            border-bottom: 1px solid #ddd;
                            background: #f9f9f9;
                        }
                        
                        .tomatillo-attachments-container {
                            flex: 1;
                            overflow-y: auto;
                            padding: 15px;
                            position: relative;
                        }
                        
                        .tomatillo-media-item {
                            cursor: pointer;
                            border: 2px solid transparent;
                            border-radius: 8px;
                            overflow: hidden;
                            transition: all 0.2s ease;
                            background: white;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        }
                        
                        .tomatillo-media-item:hover {
                            border-color: #0073aa;
                            transform: translateY(-2px);
                            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
                        }
                        
                        .tomatillo-media-item.selected {
                            border-color: #0073aa;
                            background: #f0f8ff;
                        }
                        
                        .tomatillo-media-thumbnail {
                            width: 100%;
                            height: 150px;
                            object-fit: cover;
                            background: #f5f5f5;
                        }
                        
                        .tomatillo-media-info {
                            padding: 10px;
                            font-size: 12px;
                        }
                        
                        .tomatillo-media-title {
                            font-weight: 600;
                            margin-bottom: 4px;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }
                        
                        .tomatillo-media-meta {
                            color: #666;
                            font-size: 11px;
                        }
                        
                        .tomatillo-search-filter {
                            display: flex;
                            gap: 10px;
                            margin-bottom: 15px;
                        }
                        
                        .tomatillo-search-input {
                            flex: 1;
                            padding: 8px 12px;
                            border: 1px solid #ddd;
                            border-radius: 4px;
                        }
                        
                        .tomatillo-filter-select {
                            padding: 8px 12px;
                            border: 1px solid #ddd;
                            border-radius: 4px;
                            background: white;
                        }
                    </style>
                `);
            }
        }
    });

    /**
     * Custom Media Frame
     * Extends the default MediaFrame.Select but uses our custom browser
     */
    wp.media.view.TomatilloMediaFrame = wp.media.view.MediaFrame.Select.extend({
        
        initialize: function(options) {
            // Call parent initialize
            wp.media.view.MediaFrame.Select.prototype.initialize.apply(this, arguments);
            
            // Store original options
            this.tomatilloOptions = options || {};
        },
        
        /**
         * Override the browse state to use our custom browser
         */
        createStates: function() {
            var options = this.options;
            
            // Create the library state with our custom browser
            this.states.add([
                new wp.media.controller.Library({
                    id: 'tomatillo-library',
                    title: options.title || 'Select Media',
                    priority: 20,
                    filterable: 'all',
                    library: wp.media.query(options.library || {}),
                    multiple: options.multiple || false,
                    editable: true,
                    allowLocalEdits: true
                })
            ]);
        },
        
        /**
         * Override content rendering to use our custom browser
         */
        createContent: function() {
            var self = this;
            
            // Call parent createContent
            wp.media.view.MediaFrame.Select.prototype.createContent.apply(this, arguments);
            
            // Replace the default browser with our custom one
            this.on('content:render:browse', function() {
                var state = this.state();
                var browser = new wp.media.view.TomatilloAttachmentsBrowser({
                    controller: this,
                    collection: state.get('library'),
                    selection: state.get('selection'),
                    frame: this,
                    multiple: this.tomatilloOptions.multiple || false,
                    allowedTypes: this.tomatilloOptions.allowedTypes || []
                });
                
                // Replace the content
                this.content.set(browser);
            });
        }
    });

    /**
     * Main Tomatillo Media Frame Manager
     */
    window.TomatilloMediaFrame = {
        
        /**
         * Open our custom media frame
         */
        open: function(options) {
            options = options || {};
            
            // Create our custom frame
            var frame = new wp.media.view.TomatilloMediaFrame({
                title: options.title || 'Select Media',
                button: options.button || { text: 'Select' },
                multiple: options.multiple || false,
                library: options.library || {},
                allowedTypes: options.allowedTypes || []
            });
            
            // Handle selection
            frame.on('select', function() {
                var selection = frame.state().get('selection').toJSON();
                
                if (options.onSelect) {
                    options.onSelect(selection);
                }
            });
            
            // Open the frame
            frame.open();
            
            return frame;
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
            
            console.log('Tomatillo Media Frame initialized');
        }
    };

        // Initialize when DOM is ready
        $(document).ready(function() {
            TomatilloMediaFrame.init();
        });
    }

})(jQuery);
