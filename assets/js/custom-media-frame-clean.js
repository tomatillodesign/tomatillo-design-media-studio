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
                    console.log('🚀 Setting up event handlers for modal');
                    setupEventHandlers(options);
                    
                    // Add window resize handler for masonry
                    $(window).off('resize.tomatillo-masonry').on('resize.tomatillo-masonry', function() {
                        setTimeout(function() {
                            waitForImagesAndLayout();
                        }, 150);
                    });
                    
                    // Note: Auto-selection is now handled in real-time during upload
                    
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
                        <div id="tomatillo-selection-count" class="tomatillo-selection-count">No items selected</div>
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
                
                .tomatillo-selection-count {
                    color: #666;
                    font-size: 14px;
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
        
        console.log('🔍 DEBUG: Current column heights from DOM:', columnHeights);
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
        console.log('🔍 DEBUG: preCalculateMasonryPositions called with', mediaItems.length, 'items');
        console.log('🔍 DEBUG: optimizationDataArray length:', optimizationDataArray ? optimizationDataArray.length : 'null');
        console.log('🔍 DEBUG: startColumnHeights:', startColumnHeights);
        
        if (mediaItems.length === 0) {
            console.log('🔍 DEBUG: No items to calculate positions for');
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
        
        console.log('🔍 DEBUG: Grid element exists:', !!gridElement);
        console.log('🔍 DEBUG: Grid element offsetWidth:', gridElement ? gridElement.offsetWidth : 'N/A');
        console.log('🔍 DEBUG: Modal element exists:', !!modalElement);
        console.log('🔍 DEBUG: Modal element offsetWidth:', modalElement ? modalElement.offsetWidth : 'N/A');
        console.log('🔍 DEBUG: Using container width:', containerWidth, 'px');
        
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
        
        console.log('🔍 DEBUG: Container width:', containerWidth, 'Columns:', columns, 'Column width:', columnWidth);
        
        // Initialize column heights
        var columnHeights;
        if (startColumnHeights && Array.isArray(startColumnHeights)) {
            columnHeights = startColumnHeights.slice(); // Copy the array
            console.log('🔍 DEBUG: Using provided startColumnHeights:', columnHeights);
        } else {
            columnHeights = new Array(columns).fill(0);
            console.log('🔍 DEBUG: Starting with fresh column heights:', columnHeights);
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
                console.error('🚨 INVALID DIMENSIONS for item', index, 'ID:', item.id, 'Width:', width, 'Height:', height);
                console.error('🚨 HiRes image:', hiResImage);
                console.error('🚨 Item:', item);
                console.error('🚨 Optimization data:', optimizationData);
                // Use fallback dimensions
                width = width || 400;
                height = height || 300;
            }
            
            console.log('🔍 DEBUG: Item', index, 'ID:', item.id, 'Dimensions:', width, 'x', height);
            
            // Calculate aspect ratio and scaled dimensions
            var aspectRatio = height / width;
            var scaledWidth = columnWidth;
            var scaledHeight = columnWidth * aspectRatio;
            
            console.log('🔍 DEBUG: Aspect ratio:', aspectRatio, 'Scaled:', scaledWidth, 'x', scaledHeight);
            
            // Find shortest column
            var shortestColumnIndex = columnHeights.indexOf(Math.min.apply(Math, columnHeights));
            
            // Calculate position
            var left = shortestColumnIndex * (columnWidth + gap);
            var top = columnHeights[shortestColumnIndex];
            
            console.log('🔍 DEBUG: Column heights:', columnHeights, 'Shortest:', shortestColumnIndex);
            console.log('🔍 DEBUG: Position:', left, ',', top);
            
            positions.push({
                left: left,
                top: top,
                width: scaledWidth,
                height: scaledHeight
            });
            
            // Update column height
            columnHeights[shortestColumnIndex] += scaledHeight + gap;
            
            console.log('🔍 DEBUG: Updated column heights:', columnHeights);
        });
        
        console.log('🎨 Pre-calculated masonry positions for', positions.length, 'items');
        console.log('🔍 DEBUG: Final positions:', positions);
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
        
        console.log('🎨 Masonry layout applied:', items.length, 'items in', columns, 'columns');
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
        console.log('Initializing media grid...');
        
        // Clear any existing selections to start fresh
        selectedItems = [];
        $('.tomatillo-media-item').removeClass('selected');
        $('#tomatillo-media-grid').removeClass('tomatillo-single-selection');
        
        // Check if we have preloaded media
        console.log('🔍 Checking for preloaded media...');
        console.log('🔍 window.TomatilloBackgroundLoader exists:', !!window.TomatilloBackgroundLoader);
        
        if (window.TomatilloBackgroundLoader && window.TomatilloBackgroundLoader.isMediaPreloaded()) {
            var preloadStartTime = performance.now();
            console.log('🚀 Using preloaded media data - INSTANT LOADING!');
            var preloadedData = window.TomatilloBackgroundLoader.getPreloadedMedia();
            console.log('🔍 Preloaded data:', preloadedData);
            currentMediaItems = preloadedData.items;
            currentOptimizationData = preloadedData.optimizationData;
            
            // Debug: Check what types we actually have
            console.log('🔍 DEBUG: All media item types:', currentMediaItems.map(function(item) {
                return {id: item.id, type: item.type, mime: item.mime, filename: item.filename};
            }));
            
            // Debug: Check for duplicates in source data
            var allIds = currentMediaItems.map(function(item) { return item.id; });
            var uniqueIds = [...new Set(allIds)];
            if (allIds.length !== uniqueIds.length) {
                console.error('🚨 DUPLICATES IN SOURCE DATA!', allIds.length, 'items,', uniqueIds.length, 'unique');
                var duplicates = allIds.filter(function(id, index) { return allIds.indexOf(id) !== index; });
                console.error('🚨 Duplicate IDs in source:', duplicates);
                
                // Remove duplicates from source data
                console.log('🔧 Removing duplicates from source data...');
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
                
                console.log('🔧 Deduplicated:', currentMediaItems.length, '→', deduplicatedItems.length);
                currentMediaItems = deduplicatedItems;
                currentOptimizationData = deduplicatedOptimizationData;
            }
            
            // Filter to images only by default
            var imageItems = currentMediaItems.filter(function(item) {
                var isImage = item.type === 'image' || item.mime && item.mime.startsWith('image/');
                console.log('🔍 DEBUG: Item', item.id, 'type:', item.type, 'mime:', item.mime, 'isImage:', isImage);
                return isImage;
            });
            
            var imageOptimizationData = currentOptimizationData.filter(function(data, index) {
                var item = currentMediaItems[index];
                return item.type === 'image' || item.mime && item.mime.startsWith('image/');
            });
            
            console.log('🔍 Filtered to images only:', imageItems.length, 'of', currentMediaItems.length);
            
            // Render immediately with filtered image data
            renderMediaGridWithOptimization(imageItems, imageOptimizationData, options, true);
            
            // Initialize rendered count
            renderedItemsCount = imageItems.length;
            
            // Setup infinite scroll after rendering
            setupInfiniteScroll(options);
            
            // Start loading more images in background for infinite scroll
            loadMoreImagesInBackground(options);
            
            // Test: Auto-trigger infinite scroll after 2 seconds to see if it works
            setTimeout(function() {
                console.log('🧪 TEST: Auto-triggering infinite scroll after 2 seconds');
                console.log('🧪 Current media items:', currentMediaItems.length);
                console.log('🧪 Rendered items:', renderedItemsCount);
                if (currentMediaItems.length > renderedItemsCount) {
                    console.log('🧪 Triggering loadMoreImages...');
                    loadMoreImages(renderedItemsCount, 100, options);
                }
            }, 2000);
            
            var preloadEndTime = performance.now();
            console.log('🚀 Modal opened in', (preloadEndTime - preloadStartTime).toFixed(2), 'ms using preloaded data!');
            console.log('🚀 Total media items available:', currentMediaItems.length);
            console.log('🚀 Rendered items count:', renderedItemsCount);
            return;
        }
        
        console.log('🚀 No preloaded data available, falling back to server fetch');
        console.log('🔍 Background loader status:', window.TomatilloBackgroundLoader ? 'exists' : 'missing');
        if (window.TomatilloBackgroundLoader) {
            console.log('🔍 isMediaPreloaded():', window.TomatilloBackgroundLoader.isMediaPreloaded());
        }
        
        // Fallback to regular loading
        loadMediaFromServer(options);
    }
    
    /**
     * Load media from server (fallback method)
     */
    function loadMediaFromServer(options) {
        // Use WordPress AJAX to fetch media directly
        var data = {
            action: 'query-attachments',
            query: {
                posts_per_page: 50,
                post_status: 'inherit'
                // Remove post_mime_type restriction to load all file types
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
                
                // Store media items globally immediately
                currentMediaItems = mediaItems;
                console.log('📦 Stored mediaItems globally:', currentMediaItems.length);
                
                // Debug: Check what types we actually have
                console.log('🔍 DEBUG: All media item types:', currentMediaItems.map(function(item) {
                    return {id: item.id, type: item.type, mime: item.mime, filename: item.filename};
                }));
                
                // Debug: Check for duplicates in source data
                var allIds = currentMediaItems.map(function(item) { return item.id; });
                var uniqueIds = [...new Set(allIds)];
                if (allIds.length !== uniqueIds.length) {
                    console.error('🚨 DUPLICATES IN SOURCE DATA!', allIds.length, 'items,', uniqueIds.length, 'unique');
                    var duplicates = allIds.filter(function(id, index) { return allIds.indexOf(id) !== index; });
                    console.error('🚨 Duplicate IDs in source:', duplicates);
                    
                    // Remove duplicates from source data
                    console.log('🔧 Removing duplicates from source data...');
                    var seenIds = new Set();
                    var deduplicatedItems = [];
                    
                    currentMediaItems.forEach(function(item) {
                        if (!seenIds.has(item.id)) {
                            seenIds.add(item.id);
                            deduplicatedItems.push(item);
                        }
                    });
                    
                    console.log('🔧 Deduplicated:', currentMediaItems.length, '→', deduplicatedItems.length);
                    currentMediaItems = deduplicatedItems;
                }
                
                // Filter to images only by default
                var imageItems = currentMediaItems.filter(function(item) {
                    var isImage = item.type === 'image' || item.mime && item.mime.startsWith('image/');
                    console.log('🔍 DEBUG: Item', item.id, 'type:', item.type, 'mime:', item.mime, 'isImage:', isImage);
                    return isImage;
                });
                
                console.log('🔍 Filtered to images only:', imageItems.length, 'of', currentMediaItems.length);
                
                renderMediaGrid(imageItems, options);
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
        // Debug: Check optimization data availability
        if (!optimizationData) {
            console.log('❌ No optimization data for item', item.id, '- using fallback');
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
        // Store media items globally for access in event handlers
        currentMediaItems = mediaItems;
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
        console.log('🔍 Fetching optimization data for', mediaItems.length, 'items');
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
        
        console.log('Media grid rendered successfully');
        
        // Setup infinite scroll if enabled
        setupInfiniteScroll(options);
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
        // Store media items globally for access in event handlers
        currentMediaItems = mediaItems;
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
    }

    /**
     * Setup event handlers for the modal
     */
    function setupEventHandlers(options) {
        
        // Handle close button
        $('#tomatillo-close-modal').off('click').on('click', function() {
            console.log('Close button clicked');
            cleanupModal();
        });
        
        // Handle cancel button
        $('#tomatillo-cancel').off('click').on('click', function() {
            console.log('Cancel button clicked');
            cleanupModal();
        });
        
        // Handle media item selection
        $(document).off('click.tomatillo-media').on('click.tomatillo-media', '.tomatillo-media-item', function() {
            console.log('🎯 Media item clicked - checking if selection works');
            var itemId = $(this).data('id');
            var $item = $(this);
            console.log('🎯 Item ID:', itemId, 'Item element:', $item);
            console.log('🎯 Current selectedItems before:', selectedItems);
            console.log('🎯 Options multiple:', options.multiple);
            
            if (options.multiple) {
                // Multiple selection
                console.log('Multiple selection mode');
                $('#tomatillo-media-grid').removeClass('tomatillo-single-selection');
                
                if ($item.hasClass('selected')) {
                    console.log('Deselecting item in multiple mode');
                    $item.removeClass('selected').css('border-color', 'transparent');
                    selectedItems = selectedItems.filter(id => id !== itemId);
                } else {
                    console.log('Selecting item in multiple mode');
                    $item.addClass('selected');
                    selectedItems.push(itemId);
                }
            } else {
                // Single selection - check if clicking the same item again
                if ($item.hasClass('selected')) {
                    // Deselect - remove dimming and clear selection
                    console.log('Deselecting item in single mode');
                    $('#tomatillo-media-grid').removeClass('tomatillo-single-selection');
                    $item.removeClass('selected').css('border-color', 'transparent');
                    selectedItems = [];
                } else {
                    // Select - add dimming class and clear all other selections
                    console.log('Selecting item in single mode');
                    $('#tomatillo-media-grid').addClass('tomatillo-single-selection');
                    $('.tomatillo-media-item').removeClass('selected').css('border-color', 'transparent');
                    $item.addClass('selected');
                    selectedItems = [itemId];
                }
            }
            
            // Update selection count and button state
            updateSelectionUI(selectedItems.length, options);
            
            // Debug: Log current selection state
            console.log('🎯 Selection state after click:');
            console.log('🎯 selectedItems array:', selectedItems);
            console.log('🎯 Items with selected class:', $('.tomatillo-media-item.selected').length);
            console.log('🎯 Selected item IDs:', $('.tomatillo-media-item.selected').map(function() { return $(this).data('id'); }).get());
        });
        
        // Handle select button
        $('#tomatillo-select').on('click', function() {
            if (selectedItems.length > 0) {
                console.log('Select button clicked, selected items:', selectedItems);
                console.log('Current media items available:', currentMediaItems.length);
                
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
                
                console.log('Selection created:', selection);
                
                // Call the onSelect callback
                if (options.onSelect) {
                    options.onSelect(selection);
                }
                
                // Close modal
                cleanupModal();
            }
        });
        
        // Handle clicking outside modal
        $('#tomatillo-custom-modal').off('click').on('click', function(e) {
            if (e.target.id === 'tomatillo-custom-modal') {
                console.log('Clicked outside modal');
                cleanupModal();
            }
        });
        
        // Handle ESC key to close modal
        $(document).off('keydown.tomatillo-modal').on('keydown.tomatillo-modal', function(e) {
            if (e.key === 'Escape' && $('#tomatillo-custom-modal').length > 0) {
                console.log('ESC key pressed - closing modal');
                cleanupModal();
            }
        });
        
        // Handle filter dropdown
        $('#tomatillo-filter').off('change').on('change', function() {
            var filterValue = $(this).val();
            console.log('🔍 Filter changed to:', filterValue);
            console.log('🔍 Current media items:', currentMediaItems);
            console.log('🔍 Current media items types:', currentMediaItems.map(function(item) { return item.type; }));
            
            // Filter items based on type
            var filteredItems = currentMediaItems.filter(function(item) {
                console.log('🔍 Checking item:', item.id, 'type:', item.type, 'matches filter:', filterValue);
                if (filterValue === 'all') return true;
                return item.type === filterValue;
            });
            
            // Get corresponding optimization data
            var filteredOptimizationData = currentOptimizationData.filter(function(data, index) {
                if (filterValue === 'all') return true;
                return currentMediaItems[index].type === filterValue;
            });
            
            console.log('🔍 Filtered items:', filteredItems.length, 'of', currentMediaItems.length);
            console.log('🔍 Filtered optimization data:', filteredOptimizationData.length);
            console.log('🔍 Filtered items details:', filteredItems);
            
            // Debug: Check for duplicates in filtered items
            var itemIds = filteredItems.map(function(item) { return item.id; });
            var uniqueIds = [...new Set(itemIds)];
            if (itemIds.length !== uniqueIds.length) {
                console.error('🚨 DUPLICATE ITEMS DETECTED!', itemIds.length, 'items,', uniqueIds.length, 'unique');
                console.error('🚨 Duplicate IDs:', itemIds.filter(function(id, index) { return itemIds.indexOf(id) !== index; }));
            }
            
            // Clear search when filter changes
            $('#tomatillo-search').val('');
            $('#tomatillo-clear-search').hide();
            
            // Re-render with filtered items
            renderMediaGridWithOptimization(filteredItems, filteredOptimizationData, options, true);
            
            // Reset rendered count for infinite scroll
            renderedItemsCount = filteredItems.length;
        });
        
        // Handle search input
        $('#tomatillo-search').off('input').on('input', function() {
            var searchQuery = $(this).val().toLowerCase().trim();
            console.log('Search query:', searchQuery);
            
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
                console.log('🔍 DEBUG: Current filter:', currentFilter);
                
                var filteredItems = currentMediaItems.filter(function(item) {
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
                
                console.log('Search results:', filteredItems.length, 'of', currentMediaItems.length);
                
                // Re-render with filtered items
                renderMediaGridWithOptimization(filteredItems, filteredOptimizationData, options, false);
            }
        });
        
        // Handle clear search button
        $('#tomatillo-clear-search').off('click').on('click', function() {
            console.log('Clear search clicked');
            $('#tomatillo-search').val('').trigger('input'); // Trigger input event to update UI
        });
        
        // Handle upload button click
        $('#tomatillo-upload-btn').off('click').on('click', function() {
            console.log('Upload button clicked');
            $('#tomatillo-file-input').click();
        });
        
        // Handle file input change
        $('#tomatillo-file-input').off('change').on('change', function() {
            var files = this.files;
            if (files && files.length > 0) {
                console.log('Files selected for upload:', files.length);
                uploadFiles(Array.from(files), options);
            }
        });
        
        // Handle drag and drop
        var $gridContainer = $('.tomatillo-grid-container');
        var $dragOverlay = $('#tomatillo-drag-drop-overlay');
        var dragCounter = 0;
        
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) {
            document.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });
        
        // Handle drag enter - only show overlay once
        document.addEventListener('dragenter', function(e) {
            dragCounter++;
            if (dragCounter === 1) {
                $dragOverlay.addClass('active');
                $('#tomatillo-file-count').text('0');
            }
        });
        
        // Handle drag over - keep overlay visible
        document.addEventListener('dragover', function(e) {
            // Keep overlay visible, don't toggle
        });
        
        // Handle drag leave - only hide overlay when completely leaving
        document.addEventListener('dragleave', function(e) {
            dragCounter--;
            if (dragCounter === 0) {
                $dragOverlay.removeClass('active');
            }
        });
        
        // Handle drop - hide overlay and process files
        document.addEventListener('drop', function(e) {
            dragCounter = 0;
            $dragOverlay.removeClass('active');
            
            var files = e.dataTransfer.files;
            $('#tomatillo-file-count').text(files.length);
            if (files && files.length > 0) {
                console.log('Files dropped for upload:', files.length);
                uploadFiles(Array.from(files), options);
            }
        });
    }

    /**
     * Upload files with progress tracking
     */
    function uploadFiles(files, options) {
        console.log('Starting upload of', files.length, 'files');
        
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
                        console.error('Invalid response:', currentXhr.responseText);
                    }
                } else {
                    statusElement.text('Failed: HTTP ' + currentXhr.status);
                    statusElement.removeClass('uploading').addClass('error');
                    progressBar.css('width', '100%');
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
                statusElement.text('Failed: Network error');
                statusElement.removeClass('uploading').addClass('error');
                progressBar.css('width', '100%');
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
    }

    /**
     * Add newly uploaded items to the beginning of the grid in real-time
     */
    function addNewItemsToGrid(newlyUploadedIds, options) {
        console.log('Adding', newlyUploadedIds.length, 'newly uploaded items to grid');
        
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
                console.log('Fetched', newItems.length, 'new items:', newItems);
                
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
                        console.log('🔄 Forcing complete masonry re-layout after adding new items');
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
                            console.log('🔄 Waiting for', newImages.length, 'new images to load');
                            var loadedCount = 0;
                            newImages.each(function() {
                                var img = this;
                                if (img.complete) {
                                    loadedCount++;
                                } else {
                                    img.onload = function() {
                                        loadedCount++;
                                        if (loadedCount === newImages.length) {
                                            console.log('🔄 All new images loaded, triggering masonry layout');
                                            setTimeout(layoutMasonry, 50);
                                        }
                                    };
                                }
                            });
                            
                            // If all images are already loaded
                            if (loadedCount === newImages.length) {
                                console.log('🔄 All images already loaded, triggering masonry layout');
                                setTimeout(layoutMasonry, 50);
                            }
                        } else {
                            // No new images, trigger layout immediately
                            console.log('🔄 No new images, triggering masonry layout immediately');
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
                console.error('Error fetching new items:', error);
            });
    }

    /**
     * Auto-select newly uploaded items
     */
    function autoSelectNewlyUploadedItems(newlyUploadedIds, options) {
        console.log('Auto-selecting newly uploaded items:', newlyUploadedIds);
        
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
        
        console.log('Auto-selection complete. Selected items:', selectedItems);
    }

    /**
     * Load more images in background after modal opens
     */
    function loadMoreImagesInBackground(options) {
        var batchSize = window.tomatilloSettings ? window.tomatilloSettings.infinite_scroll_batch : 100;
        var currentOffset = currentMediaItems.length;
        
        console.log('🔄 Starting background loading of more media items from offset:', currentOffset);
        console.log('🔄 Current media items length:', currentMediaItems.length);
        console.log('🔄 Batch size:', batchSize);
        
        var data = {
            action: 'query-attachments',
            query: {
                posts_per_page: batchSize,
                post_status: 'inherit',
                offset: currentOffset
                // Remove post_mime_type restriction to load all file types
            }
        };

        $.post(ajaxurl, data)
            .done(function(response) {
                var newItems = [];
                if (Array.isArray(response)) {
                    newItems = response;
                } else if (response && response.data && Array.isArray(response.data)) {
                    newItems = response.data;
                } else if (response && response.attachments && Array.isArray(response.attachments)) {
                    newItems = response.attachments;
                }

                if (newItems.length > 0) {
                    console.log('🔄 Background loaded', newItems.length, 'more images');
                    console.log('🔄 New items IDs:', newItems.map(function(item) { return item.id; }));
                    
                    // Check for duplicates before adding
                    var existingIds = new Set(currentMediaItems.map(function(item) { return item.id; }));
                    var uniqueNewItems = newItems.filter(function(item) {
                        return !existingIds.has(item.id);
                    });
                    
                    if (uniqueNewItems.length > 0) {
                        console.log('🔄 Adding', uniqueNewItems.length, 'unique new items (filtered out', newItems.length - uniqueNewItems.length, 'duplicates)');
                        
                        // Add to current media items
                        currentMediaItems = currentMediaItems.concat(uniqueNewItems);
                        
                        // Load optimization data for new items
                        loadOptimizationDataForNewItems(uniqueNewItems);
                    } else {
                        console.log('🔄 All new items were duplicates, skipping');
                    }
                } else {
                    console.log('🔄 No more images available for background loading');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('🔄 Background loading failed:', error);
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
            console.log('🔄 Background loaded optimization data for', newOptimizationData.length, 'new items');
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

        console.log('🔄 Setting up infinite scroll with batch size:', batchSize, 'rendered items:', renderedItemsCount);

        // Add scroll listener to modal content
        $('#tomatillo-modal-content').off('scroll.infinite').on('scroll.infinite', function() {
            var $this = $(this);
            var scrollTop = $this.scrollTop();
            var scrollHeight = $this[0].scrollHeight;
            var clientHeight = $this[0].clientHeight;

            // Check if scrolled to bottom (with some buffer)
            if (scrollTop + clientHeight >= scrollHeight - 100 && !isLoading) {
                console.log('🔄 Scrolled to bottom, loading more images...');
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
        console.log('🔄 Infinite scroll triggered - rendering preloaded images from offset:', offset);
        console.log('🔄 Options multiple:', options.multiple);

        // Calculate how many items to render
        var itemsToRender = Math.min(batchSize, currentMediaItems.length - offset);
        
        if (itemsToRender > 0) {
            var newItems = currentMediaItems.slice(offset, offset + itemsToRender);
            var newOptimizationData = currentOptimizationData.slice(offset, offset + itemsToRender);
            
            console.log('🔄 Rendering', newItems.length, 'preloaded images');
            
            // Render additional items using preloaded optimization data
            renderAdditionalItemsWithOptimization(newItems, newOptimizationData, options);
            
            // Update rendered count
            renderedItemsCount += newItems.length;
            console.log('🔄 Total rendered items:', renderedItemsCount);
        } else {
            console.log('🔄 No more preloaded images to render');
        }
        
        isLoading = false;
    }

    /**
     * Render additional items for infinite scroll using preloaded optimization data
     */
    function renderAdditionalItemsWithOptimization(newItems, newOptimizationData, options) {
        console.log('🔍 DEBUG: renderAdditionalItemsWithOptimization called with', newItems.length, 'new items');
        
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
            
            console.log('🔍 DEBUG: Additional item', index, 'ID:', item.id, 'Position:', position);
            
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
        
        console.log('🔍 DEBUG: Updated container height from', currentHeight, 'to', newHeight);
        console.log('🎨 Additional items rendered with pre-calculated positions - NO LAYOUT SHIFT!');
        
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
        
        console.log('🔄 Rendered', newItems.length, 'additional items with preloaded optimization data');
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
        
        console.log('🔄 Rendered', newItems.length, 'additional items');
    }

    /**
     * Clean up modal and reset state
     */
    function cleanupModal() {
        console.log('🧹 Cleaning up modal');
        console.log('🧹 Current selectedItems:', selectedItems);
        console.log('🧹 Current mediaItems length:', currentMediaItems.length);
        
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
        
        console.log('🧹 Modal cleanup complete');
        console.log('🧹 Reset selectedItems:', selectedItems);
        console.log('🧹 Reset mediaItems length:', currentMediaItems.length);
    }

    /**
     * Render media grid with pre-filtered optimization data (for search)
     */
    function renderMediaGridWithOptimization(mediaItems, optimizationDataArray, options, skipImageWait) {
        console.log('Rendering filtered media grid with', mediaItems.length, 'items');
        
        // Determine layout type based on content
        var hasImages = mediaItems.some(function(item) { return item.type === 'image'; });
        var hasNonImages = mediaItems.some(function(item) { return item.type !== 'image'; });
        
        // Use grid layout if there are any non-image files (mixed content)
        var layoutType = (hasImages && hasNonImages) ? 'grid' : (hasImages ? 'masonry' : 'grid');
        console.log('🔍 DEBUG: Layout type:', layoutType, '(hasImages:', hasImages, ', hasNonImages:', hasNonImages, ')');
        
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
            
            console.log('🔍 DEBUG: Rendering item', index, 'ID:', item.id);
            console.log('🔍 DEBUG: Position:', position);
            console.log('🔍 DEBUG: HiRes image:', hiResImage);
            
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
            
            console.log('🔍 DEBUG: Generated HTML for item', index, ':', itemHtml);
            gridHtml += itemHtml;
        });
        
        console.log('🔍 DEBUG: About to insert HTML into grid');
        console.log('🔍 DEBUG: Grid element:', $('#tomatillo-media-grid'));
        console.log('🔍 DEBUG: HTML length:', gridHtml.length);
        
        $('#tomatillo-media-grid').html(gridHtml);
        
        console.log('🔍 DEBUG: HTML inserted, checking DOM elements');
        var insertedItems = $('.tomatillo-media-item');
        console.log('🔍 DEBUG: Inserted items count:', insertedItems.length);
        
        insertedItems.each(function(index) {
            var $item = $(this);
            var computedStyle = window.getComputedStyle(this);
            var rect = this.getBoundingClientRect();
            console.log('🔍 DEBUG: Item', index, 'ID:', $item.data('id'));
            console.log('🔍 DEBUG: Computed position:', computedStyle.position);
            console.log('🔍 DEBUG: Computed left:', computedStyle.left);
            console.log('🔍 DEBUG: Computed top:', computedStyle.top);
            console.log('🔍 DEBUG: Computed width:', computedStyle.width);
            console.log('🔍 DEBUG: Computed height:', computedStyle.height);
            console.log('🔍 DEBUG: Bounding rect:', rect.left, rect.top, rect.width, rect.height);
            
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
                    console.error('🚨 OVERLAPPING DETECTED! Item', index, 'overlaps with item', index - 1);
                    console.error('🚨 Current rect:', currentRect);
                    console.error('🚨 Previous rect:', prevRect);
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
            console.log('🔍 DEBUG: Setting masonry container height to:', maxHeight + 'px');
            $('#tomatillo-media-grid').css('height', maxHeight + 'px');
        } else {
            // For grid layout, let it size naturally
            console.log('🔍 DEBUG: Grid layout - letting container size naturally');
            $('#tomatillo-media-grid').css('height', 'auto');
        }
        
        console.log('🎨 Pre-calculated masonry layout applied - NO LAYOUT SHIFT!');
        console.log('🔍 DEBUG: Final container height:', $('#tomatillo-media-grid').css('height'));
        console.log('Filtered media grid rendered successfully');
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
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('DOM ready, initializing CLEAN TomatilloMediaFrame');
        TomatilloMediaFrame.init();
    });

})(jQuery);
