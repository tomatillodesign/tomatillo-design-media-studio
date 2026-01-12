/**
 * Tomatillo Custom Media Frame - Working Version
 * Simplified but functional custom media inserter
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
        console.log('Tomatillo Media Studio: Initializing working custom media frame');
        initializeCustomMediaFrame();
    });

    function initializeCustomMediaFrame() {
        
        try {
            console.log('Starting custom media frame initialization...');
            
            /**
             * Custom Attachments Browser View
             * Simple version that works
             */
            console.log('Creating TomatilloAttachmentsBrowser...');
            wp.media.view.TomatilloAttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
            
            className: 'tomatillo-attachments-browser',
            
            template: wp.template('tomatillo-attachments-browser'),
            
            events: {
                'click .tomatillo-media-item': 'toggleSelection',
                'input .tomatillo-search-input': 'handleSearch',
                'change .tomatillo-filter-select': 'handleFilter'
            },
            
            initialize: function(options) {
                this.options = options || {};
                this.selection = options.selection;
                this.frame = options.frame;
                this.multiple = options.multiple || false;
                this.allowedTypes = options.allowedTypes || [];
                
                // Call parent initialize
                wp.media.view.AttachmentsBrowser.prototype.initialize.apply(this, arguments);
                
                // Bind events
                this.bindEvents();
            },
            
            render: function() {
                // Call parent render
                wp.media.view.AttachmentsBrowser.prototype.render.apply(this, arguments);
                
                // Apply our custom styling
                this.applyCustomLayout();
                
                return this;
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
                this.collection.on('reset', function() {
                    self.layoutGrid();
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
             * Simple grid layout
             */
            layoutGrid: function() {
                var $items = this.$('.tomatillo-media-item');
                $items.css({
                    'position': 'static',
                    'width': '200px',
                    'display': 'inline-block',
                    'vertical-align': 'top',
                    'margin': '5px'
                });
            },
            
            /**
             * Apply custom layout and styling
             */
            applyCustomLayout: function() {
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
                            }
                            
                            .tomatillo-media-item {
                                cursor: pointer;
                                border: 2px solid transparent;
                                border-radius: 8px;
                                overflow: hidden;
                                transition: all 0.2s ease;
                                background: white;
                                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                                margin: 5px;
                                display: inline-block;
                                vertical-align: top;
                                width: 200px;
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
                            
                            .tomatillo-file-icon {
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 48px;
                                color: #666;
                                height: 150px;
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
            console.log('TomatilloAttachmentsBrowser created successfully');
            
            /**
             * Custom Media Frame
             * Simple version that works
             */
            console.log('Creating TomatilloMediaFrame...');
            wp.media.view.TomatilloMediaFrame = wp.media.view.MediaFrame.Select.extend({
            
            initialize: function(options) {
                // Call parent initialize
                wp.media.view.MediaFrame.Select.prototype.initialize.apply(this, arguments);
                
                // Store original options
                this.tomatilloOptions = options || {};
            },
            
            /**
             * Override content rendering to use our custom browser
             */
            createContent: function() {
                var self = this;
                
                console.log('createContent called');
                
                // Call parent createContent
                wp.media.view.MediaFrame.Select.prototype.createContent.apply(this, arguments);
                
                console.log('Parent createContent called, setting up content replacement');
                
                // Replace the default browser with our custom one
                this.on('content:render:browse', function() {
                    console.log('content:render:browse event fired');
                    var state = this.state();
                    console.log('Creating custom browser with state:', state);
                    
                    var browser = new wp.media.view.TomatilloAttachmentsBrowser({
                        controller: this,
                        collection: state.get('library'),
                        selection: state.get('selection'),
                        frame: this,
                        multiple: this.tomatilloOptions.multiple || false,
                        allowedTypes: this.tomatilloOptions.allowedTypes || []
                    });
                    
                    console.log('Custom browser created, replacing content');
                    
                    // Replace the content
                    this.content.set(browser);
                    
                    console.log('Content replaced with custom browser');
                });
                
                // Also try to replace content immediately after creation
                this.on('open', function() {
                    console.log('Frame opened, attempting immediate content replacement');
                    setTimeout(function() {
                        var state = self.state();
                        if (state && state.get('library')) {
                            console.log('Attempting immediate browser replacement');
                            var browser = new wp.media.view.TomatilloAttachmentsBrowser({
                                controller: self,
                                collection: state.get('library'),
                                selection: state.get('selection'),
                                frame: self,
                                multiple: self.tomatilloOptions.multiple || false,
                                allowedTypes: self.tomatilloOptions.allowedTypes || []
                            });
                            
                            self.content.set(browser);
                            console.log('Immediate content replacement completed');
                        }
                    }, 100);
                });
            }
            });
            console.log('TomatilloMediaFrame created successfully');
            
            /**
             * Main Tomatillo Media Frame Manager
             */
            console.log('Creating main TomatilloMediaFrame manager...');
            window.TomatilloMediaFrame = {
            
            /**
             * Open our custom media frame
             */
            open: function(options) {
                console.log('TomatilloMediaFrame.open called with options:', options);
                
                options = options || {};
                
                console.log('Creating custom frame...');
                try {
                    // Create our custom frame
                    var frame = new wp.media.view.TomatilloMediaFrame({
                        title: options.title || 'Select Media',
                        button: options.button || { text: 'Select' },
                        multiple: options.multiple || false,
                        library: options.library || {},
                        allowedTypes: options.allowedTypes || []
                    });
                    console.log('Custom frame created successfully');
                    
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
                    console.log('Opening custom frame...');
                    frame.open();
                    console.log('Custom media frame opened');
                    
                    return frame;
                } catch (error) {
                    console.error('Error creating custom frame:', error);
                    console.error('Stack trace:', error.stack);
                    
                    // Fallback to default WordPress frame
                    console.log('Falling back to default WordPress frame');
                    var frame = wp.media({
                        title: options.title || 'Select Media',
                        button: options.button || { text: 'Select' },
                        multiple: options.multiple || false,
                        library: options.library || {}
                    });
                    
                    frame.on('select', function() {
                        var selection = frame.state().get('selection').toJSON();
                        if (options.onSelect) {
                            options.onSelect(selection);
                        }
                    });
                    
                    frame.open();
                    return frame;
                }
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
        
        } catch (error) {
            console.error('Error initializing custom media frame:', error);
            console.error('Stack trace:', error.stack);
        }
    }

})(jQuery);
