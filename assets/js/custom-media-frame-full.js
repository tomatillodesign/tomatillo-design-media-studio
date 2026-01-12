/**
 * Tomatillo Custom Media Frame - Full Version
 * Complete custom media inserter with masonry grid and modern interface
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
        console.log('Tomatillo Media Studio: Initializing full custom media frame');
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
                'scroll .tomatillo-attachments-container': 'handleScroll',
                'click .tomatillo-upload-btn': 'openFileDialog',
                'change .tomatillo-file-input': 'handleFileUpload',
                'dragover .tomatillo-attachments-container': 'handleDragOver',
                'dragleave .tomatillo-attachments-container': 'handleDragLeave',
                'drop .tomatillo-attachments-container': 'handleDrop'
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
                console.log('TomatilloAttachmentsBrowser render called');
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
                
                // Initialize upload functionality
                this.initUploadFunctionality();
                
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
                                margin-bottom: 10px;
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
                            
                            .tomatillo-upload-btn {
                                background: #0073aa;
                                color: white;
                                border: none;
                                padding: 8px 16px;
                                border-radius: 4px;
                                cursor: pointer;
                                font-size: 14px;
                                margin-left: 10px;
                            }
                            
                            .tomatillo-upload-btn:hover {
                                background: #005a87;
                            }
                            
                            .tomatillo-file-input {
                                display: none;
                            }
                            
                            .tomatillo-drag-overlay {
                                position: absolute;
                                top: 0;
                                left: 0;
                                right: 0;
                                bottom: 0;
                                background: rgba(0, 115, 170, 0.1);
                                border: 2px dashed #0073aa;
                                display: none;
                                align-items: center;
                                justify-content: center;
                                z-index: 1000;
                                pointer-events: none;
                            }
                            
                            .tomatillo-drag-overlay.active {
                                display: flex;
                            }
                            
                            .tomatillo-drag-message {
                                text-align: center;
                                color: #0073aa;
                                font-size: 18px;
                                font-weight: 600;
                            }
                            
                            .tomatillo-upload-progress {
                                position: fixed;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%);
                                background: white;
                                border: 1px solid #ddd;
                                border-radius: 8px;
                                padding: 20px;
                                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                                z-index: 10000;
                                min-width: 300px;
                                display: none;
                            }
                            
                            .tomatillo-upload-progress.active {
                                display: block;
                            }
                            
                            .tomatillo-progress-bar {
                                width: 100%;
                                height: 20px;
                                background: #f0f0f0;
                                border-radius: 10px;
                                overflow: hidden;
                                margin: 10px 0;
                            }
                            
                            .tomatillo-progress-fill {
                                height: 100%;
                                background: #0073aa;
                                width: 0%;
                                transition: width 0.3s ease;
                            }
                            
                            .tomatillo-upload-status {
                                text-align: center;
                                font-size: 14px;
                                color: #666;
                                margin-bottom: 10px;
                            }
                        </style>
                    `);
                }
            },
            
            /**
             * Initialize upload functionality
             */
            initUploadFunctionality: function() {
                console.log('Initializing upload functionality...');
                var self = this;
                var $container = this.$('.tomatillo-attachments-container');
                
                // Add drag overlay
                if (!$container.find('.tomatillo-drag-overlay').length) {
                    $container.append(`
                        <div class="tomatillo-drag-overlay">
                            <div class="tomatillo-drag-message">
                                Drop files here to upload
                            </div>
                        </div>
                    `);
                }
                
                // Add upload progress modal
                if (!$('body').find('.tomatillo-upload-progress').length) {
                    $('body').append(`
                        <div class="tomatillo-upload-progress">
                            <div class="tomatillo-upload-status">Preparing upload...</div>
                            <div class="tomatillo-progress-bar">
                                <div class="tomatillo-progress-fill"></div>
                            </div>
                            <div style="text-align: center;">
                                <button class="tomatillo-cancel-upload" style="background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Cancel</button>
                            </div>
                        </div>
                    `);
                }
            },
            
            /**
             * Open file dialog
             */
            openFileDialog: function(e) {
                e.preventDefault();
                this.$('.tomatillo-file-input').click();
            },
            
            /**
             * Handle file upload from file input
             */
            handleFileUpload: function(e) {
                var files = e.target.files;
                if (files && files.length > 0) {
                    this.uploadFiles(Array.from(files));
                }
            },
            
            /**
             * Handle drag over
             */
            handleDragOver: function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.$('.tomatillo-drag-overlay').addClass('active');
            },
            
            /**
             * Handle drag leave
             */
            handleDragLeave: function(e) {
                e.preventDefault();
                e.stopPropagation();
                // Only hide if we're leaving the container entirely
                if (!$(e.currentTarget).find(e.relatedTarget).length) {
                    this.$('.tomatillo-drag-overlay').removeClass('active');
                }
            },
            
            /**
             * Handle drop
             */
            handleDrop: function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.$('.tomatillo-drag-overlay').removeClass('active');
                
                var files = e.originalEvent.dataTransfer.files;
                if (files && files.length > 0) {
                    this.uploadFiles(Array.from(files));
                }
            },
            
            /**
             * Upload files with progress tracking
             */
            uploadFiles: function(files) {
                var self = this;
                var $progressModal = $('.tomatillo-upload-progress');
                var $progressFill = $('.tomatillo-progress-fill');
                var $uploadStatus = $('.tomatillo-upload-status');
                var $cancelBtn = $('.tomatillo-cancel-upload');
                
                // Show progress modal
                $progressModal.addClass('active');
                $uploadStatus.text('Preparing upload...');
                $progressFill.css('width', '0%');
                
                var uploadedCount = 0;
                var totalFiles = files.length;
                var successfulCount = 0;
                var failedCount = 0;
                var currentXhr = null;
                
                // Set up cancel functionality
                $cancelBtn.off('click').on('click', function() {
                    if (currentXhr) {
                        currentXhr.abort();
                    }
                    $progressModal.removeClass('active');
                });
                
                // Upload files one by one
                uploadNextFile(0);
                
                function uploadNextFile(index) {
                    if (index >= files.length) {
                        // All files processed
                        var successRate = totalFiles > 0 ? Math.round((successfulCount / totalFiles) * 100) : 0;
                        $uploadStatus.text('Upload complete! ' + successfulCount + '/' + totalFiles + ' files successful (' + successRate + '%)');
                        $progressFill.css('width', '100%');
                        
                        // Refresh the collection and close modal after delay
                        setTimeout(function() {
                            self.refreshCollection();
                            $progressModal.removeClass('active');
                        }, 2000);
                        return;
                    }
                    
                    var file = files[index];
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
                                    successfulCount++;
                                    // Store the uploaded attachment ID for auto-selection
                                    if (response.data && response.data.attachment_id) {
                                        self.newlyUploadedIds = self.newlyUploadedIds || [];
                                        self.newlyUploadedIds.push(response.data.attachment_id);
                                    }
                                } else {
                                    failedCount++;
                                    console.error('Upload failed:', response.data);
                                }
                            } catch (e) {
                                failedCount++;
                                console.error('Invalid response:', currentXhr.responseText);
                            }
                        } else {
                            failedCount++;
                            console.error('Upload failed with status:', currentXhr.status);
                        }
                        
                        uploadedCount++;
                        
                        // Upload next file
                        setTimeout(function() {
                            uploadNextFile(index + 1);
                        }, 100);
                    });
                    
                    // Handle error
                    currentXhr.addEventListener('error', function() {
                        failedCount++;
                        uploadedCount++;
                        console.error('Upload error for file:', file.name);
                        
                        // Upload next file
                        setTimeout(function() {
                            uploadNextFile(index + 1);
                        }, 100);
                    });
                    
                    // Send request
                    currentXhr.open('POST', ajaxurl || '/wp-admin/admin-ajax.php');
                    currentXhr.send(formData);
                }
            },
            
            /**
             * Refresh the collection and auto-select newly uploaded items
             */
            refreshCollection: function() {
                var self = this;
                
                // Refresh the collection
                this.collection.fetch().done(function() {
                    // Auto-select newly uploaded items
                    if (self.newlyUploadedIds && self.newlyUploadedIds.length > 0) {
                        self.newlyUploadedIds.forEach(function(attachmentId) {
                            var attachment = self.collection.get(attachmentId);
                            if (attachment && !self.selection.get(attachmentId)) {
                                if (!self.multiple) {
                                    self.selection.reset();
                                }
                                self.selection.add(attachment);
                            }
                        });
                        
                        // Clear the newly uploaded IDs
                        self.newlyUploadedIds = [];
                        
                        // Update UI
                        self.updateSelectionUI();
                    }
                    
                    // Re-layout masonry
                    setTimeout(function() {
                        self.layoutMasonry();
                    }, 100);
                });
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
                console.log('TomatilloMediaFrame.open called with options:', options);
                
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

        // Initialize when DOM is ready
        $(document).ready(function() {
            console.log('DOM ready, initializing TomatilloMediaFrame');
            TomatilloMediaFrame.init();
        });
    }

})(jQuery);
