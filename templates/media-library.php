<?php
/**
 * Modern Media Studio Gallery
 * Google Photos-style masonry layout with infinite scroll
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = tomatillo_media_studio();
$settings = $plugin->settings;

// Get media stats
$media_stats = ($plugin->core) ? $plugin->core->get_media_stats() : array();
$total_images = isset($media_stats['total_images']) ? $media_stats['total_images'] : 0;

// Pagination settings
$images_per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $images_per_page;

// Get images (newest first)
$images = get_posts(array(
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'post_status' => 'inherit',
    'posts_per_page' => $images_per_page,
    'offset' => $offset,
    'orderby' => 'date',
    'order' => 'DESC',
    'meta_query' => array(
        array(
            'key' => '_wp_attachment_metadata',
            'compare' => 'EXISTS'
        )
    )
));

$has_more = count($images) === $images_per_page;
?>

<div class="media-studio-gallery">
    <!-- Header -->
    <div class="gallery-header">
        <div class="header-content">
            <h1 class="gallery-title">Media Studio</h1>
            <div class="gallery-stats">
                <span class="stat">
                    <strong><?php echo number_format($total_images); ?></strong>
                    <span class="stat-label">Images</span>
                </span>
            </div>
        </div>
        
        <!-- Drag & Drop Target -->
        <div class="drag-drop-target" id="drag-drop-target">
            <div class="drag-drop-content">
                <div class="drag-drop-icon">📤</div>
                <div class="drag-drop-text">
                    <strong>Drop images here</strong>
                    <span>or click to browse</span>
                </div>
            </div>
            <input type="file" id="media-upload" multiple accept="image/*" style="display: none;">
        </div>
    </div>
    
    <!-- Drag & Drop Overlay -->
    <div class="drag-drop-overlay" id="drag-drop-overlay">
        <div class="drag-drop-message">
            <div class="drag-drop-icon-large">📤</div>
            <h3>Drop images to upload</h3>
            <p>Release to add <?php echo '<span id="file-count">0</span>'; ?> images to your library</p>
        </div>
    </div>

    <!-- Gallery Grid -->
    <div class="gallery-container">
        <div class="masonry-grid" id="masonry-grid">
            <?php if (empty($images)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📷</div>
                    <h3>No images yet</h3>
                    <p>Drag and drop images above or click the upload area to get started</p>
                </div>
            <?php else: ?>
                <?php foreach ($images as $image): ?>
                    <?php
                    $image_url = wp_get_attachment_image_url($image->ID, 'large');
                    $image_alt = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
                    $image_title = $image->post_title ?: $image->post_name;
                    $image_date = $image->post_date;
                    $file_size = filesize(get_attached_file($image->ID));
                    $file_size_formatted = size_format($file_size);
                    
                    // Check if optimized
                    $is_optimized = ($plugin->core) ? $plugin->core->is_image_optimized($image->ID) : false;
                    ?>
                    
                    <div class="gallery-item" data-id="<?php echo $image->ID; ?>">
                        <div class="image-container">
                            <img 
                                src="<?php echo esc_url($image_url); ?>" 
                                alt="<?php echo esc_attr($image_alt); ?>"
                                loading="lazy"
                                class="gallery-image"
                            >
                            
                            <!-- Overlay -->
                            <div class="image-overlay">
                                <div class="overlay-content">
                                    <div class="image-info">
                                        <h4 class="image-title"><?php echo esc_html($image_title); ?></h4>
                                        <p class="image-meta">
                                            <?php echo date('M j, Y', strtotime($image_date)); ?> • 
                                            <?php echo $file_size_formatted; ?>
                                        </p>
                                    </div>
                                    
                                    <div class="image-actions">
                                        <button class="action-btn view-btn" title="View">
                                            <span>👁️</span>
                                        </button>
                                        <button class="action-btn edit-btn" title="Edit">
                                            <span>✏️</span>
                                        </button>
                                        <button class="action-btn download-btn" title="Download">
                                            <span>⬇️</span>
                                        </button>
                                        <?php if (!$is_optimized && $settings->is_optimization_enabled()): ?>
                                            <button class="action-btn optimize-btn" title="Optimize">
                                                <span>⚡</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Optimization Status -->
                                <?php if ($is_optimized): ?>
                                    <div class="optimization-badge">
                                        <span class="badge optimized">✓ Optimized</span>
                                    </div>
                                <?php elseif ($settings->is_optimization_enabled()): ?>
                                    <div class="optimization-badge">
                                        <span class="badge pending">⚡ Optimize</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Loading Indicator -->
        <?php if ($has_more): ?>
            <div class="loading-indicator" id="loading-indicator">
                <div class="spinner"></div>
                <span>Loading more images...</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Image Modal -->
<div class="image-modal" id="image-modal">
    <div class="modal-backdrop" onclick="closeModal()"></div>
    
    <!-- External Navigation -->
    <button class="external-nav-btn prev-btn" onclick="navigateImage(-1)">
        <span class="nav-icon">←</span>
    </button>
    <button class="external-nav-btn next-btn" onclick="navigateImage(1)">
        <span class="nav-icon">→</span>
    </button>
    
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modal-image-title">Image Details</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <div class="modal-image-section">
                <img id="modal-image" src="" alt="" class="modal-image" title="Copy Original URL" onclick="copyImageUrl()">
            </div>
            
            <div class="modal-details-section">
                <form id="image-form" class="image-form">
                    <div class="form-group">
                        <label for="image-title">Title</label>
                        <input type="text" id="image-title" name="title" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="image-alt">Alt Text</label>
                        <input type="text" id="image-alt" name="alt_text" class="form-input" placeholder="Describe this image for accessibility">
                    </div>
                    
                    <div class="form-group">
                        <label for="image-caption">Caption</label>
                        <textarea id="image-caption" name="caption" class="form-textarea" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image-description">Description</label>
                        <textarea id="image-description" name="description" class="form-textarea" rows="4"></textarea>
                    </div>
                    
                    <div class="image-metadata">
                        <h4>Image Information</h4>
                        <div class="metadata-badges">
                            <div class="badge-group">
                                <span class="dashicons dashicons-admin-post badge-icon"></span>
                                <span class="badge-label">ID:</span>
                                <span class="badge-text" id="modal-image-id">-</span>
                            </div>
                            <div class="badge-group">
                                <span class="dashicons dashicons-media-document badge-icon"></span>
                                <span class="badge-label">Type:</span>
                                <span class="badge-text" id="modal-image-type">-</span>
                            </div>
                            <div class="badge-group">
                                <span class="dashicons dashicons-screenoptions badge-icon"></span>
                                <span class="badge-label">Dimensions:</span>
                                <span class="badge-text" id="modal-image-size">-</span>
                            </div>
                            <div class="badge-group">
                                <span class="dashicons dashicons-database badge-icon"></span>
                                <span class="badge-label">File Size:</span>
                                <span class="badge-text" id="modal-image-file">-</span>
                            </div>
                            <div class="badge-group">
                                <span class="dashicons dashicons-calendar-alt badge-icon"></span>
                                <span class="badge-label">Uploaded:</span>
                                <span class="badge-text" id="modal-image-date-user">-</span>
                            </div>
                            <div class="badge-group url-copy" onclick="copyToClipboard(document.getElementById('modal-image-url').textContent)">
                                <span class="dashicons dashicons-admin-links badge-icon"></span>
                                <span class="badge-label">Original URL</span>
                                <span class="badge-text url-preview" id="modal-image-url-preview">Click to copy</span>
                                <span class="dashicons dashicons-clipboard copy-hint"></span>
                            </div>
                            <div class="badge-group url-copy" id="avif-url-badge" onclick="copyToClipboard(document.getElementById('modal-avif-url').textContent)" style="display: none;">
                                <span class="dashicons dashicons-performance badge-icon avif-icon"></span>
                                <span class="badge-label">AVIF URL</span>
                                <span class="badge-text url-preview" id="modal-avif-url-preview">Click to copy</span>
                                <span class="dashicons dashicons-clipboard copy-hint"></span>
                            </div>
                            <div class="badge-group url-copy" id="webp-url-badge" onclick="copyToClipboard(document.getElementById('modal-webp-url').textContent)" style="display: none;">
                                <span class="dashicons dashicons-performance badge-icon webp-icon"></span>
                                <span class="badge-label">WebP URL</span>
                                <span class="badge-text url-preview" id="modal-webp-url-preview">Click to copy</span>
                                <span class="dashicons dashicons-clipboard copy-hint"></span>
                            </div>
                            <div class="badge-group" id="savings-badge" style="display: none;">
                                <span class="dashicons dashicons-chart-line badge-icon"></span>
                                <span class="badge-label">Space Saved:</span>
                                <span class="badge-text" id="modal-space-saved">0 B</span>
                            </div>
                        </div>
                        
                        <!-- Hidden elements for copy functionality -->
                        <span id="modal-image-url" style="display: none;">-</span>
                        <span id="modal-avif-url" style="display: none;">-</span>
                        <span id="modal-webp-url" style="display: none;">-</span>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="modal-footer">
            <div class="modal-actions">
                <button type="button" class="btn btn-optimize" id="optimize-btn" onclick="optimizeImageModal()" style="display: none;">Optimize Image</button>
                <button type="button" class="btn btn-secondary" onclick="copyImageUrl()">Copy Original URL</button>
                <button type="button" class="btn btn-secondary" onclick="downloadImage()">Download Original</button>
                <button type="button" class="btn btn-danger" onclick="deleteImage()">Delete File</button>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn btn-primary" onclick="saveImageMetadata()">Save Metadata</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification will be created dynamically -->

<style>
/* Modern Gallery Styles */
.media-studio-gallery {
    background: #f8f9fa;
    min-height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header */
.gallery-header {
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    padding: 1.5rem 0;
    margin-bottom: 2rem;
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.gallery-title {
    font-size: 1.75rem;
    font-weight: 600;
    margin: 0;
    color: #1f2937;
}

.gallery-stats {
    display: flex;
    gap: 2rem;
}

.stat {
    text-align: center;
}

.stat strong {
    display: block;
    font-size: 1.25rem;
    font-weight: 600;
    color: #374151;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
}

/* Drag & Drop Target */
.drag-drop-target {
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 1.5rem 2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 200px;
}

.drag-drop-target:hover {
    border-color: #3b82f6;
    background: #eff6ff;
}

.drag-drop-target.drag-over {
    border-color: #3b82f6;
    background: #dbeafe;
    transform: scale(1.02);
}

.drag-drop-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.drag-drop-icon {
    font-size: 1.5rem;
}

.drag-drop-text {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.drag-drop-text strong {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.drag-drop-text span {
    font-size: 0.75rem;
    color: #6b7280;
}

/* Drag & Drop Overlay */
.drag-drop-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(59, 130, 246, 0.1);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
}

.drag-drop-overlay.active {
    display: flex;
}

.drag-drop-message {
    background: white;
    border-radius: 16px;
    padding: 3rem;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border: 2px solid #3b82f6;
}

.drag-drop-icon-large {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.drag-drop-message h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
}

.drag-drop-message p {
    font-size: 1rem;
    color: #6b7280;
    margin: 0;
}

/* Gallery Container - WordPress Admin Style */
.gallery-container {
    max-width: none;
    margin: 0;
    padding: 0 20px;
}

/* Google Photos Style Masonry */
.masonry-grid {
    column-count: 3;
    column-gap: 0.5rem;
    padding-bottom: 2rem;
}

@media (max-width: 1200px) {
    .masonry-grid {
        column-count: 3;
        column-gap: 0.5rem;
    }
}

@media (max-width: 768px) {
    .masonry-grid {
        column-count: 2;
        column-gap: 0.5rem;
    }
}

@media (max-width: 480px) {
    .masonry-grid {
        column-count: 2;
        column-gap: 0.5rem;
    }
}

/* Gallery Items - Google Photos Style */
.gallery-item {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease;
    cursor: pointer;
    position: relative;
    break-inside: avoid;
    margin-bottom: 0.5rem;
    display: inline-block;
    width: 100%;
}

.gallery-item:hover {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
}

.image-container {
    position: relative;
    overflow: hidden;
    width: 100%;
}

/* Google Photos style images */
.gallery-image {
    width: 100%;
    height: auto;
    display: block;
    transition: filter 0.3s ease;
    border-radius: 8px;
}

.gallery-item:hover .gallery-image {
    filter: brightness(1.05);
}

/* Image Overlay - Google Photos Style */
.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, 0.6) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 0.75rem;
    border-radius: 8px;
}

.gallery-item:hover .image-overlay {
    opacity: 1;
}

.overlay-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    height: 100%;
}

.image-info {
    color: white;
    flex: 1;
}

.image-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    line-height: 1.3;
}

.image-meta {
    font-size: 0.9rem;
    opacity: 0.9;
    margin: 0;
}

.image-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.action-btn {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    color: #333;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.action-btn:hover {
    background: rgba(255, 255, 255, 1);
    transform: scale(1.05);
}

/* Optimization Badge */
.optimization-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
}

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    backdrop-filter: blur(10px);
}

.badge.optimized {
    background: #10b981;
    color: white;
}

.badge.pending {
    background: #f59e0b;
    color: white;
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.5rem;
    margin: 0 0 0.5rem 0;
    color: #374151;
}

.empty-state p {
    font-size: 1rem;
    margin: 0 0 2rem 0;
}

/* Loading Indicator */
.loading-indicator {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.spinner {
    width: 24px;
    height: 24px;
    border: 3px solid #e5e7eb;
    border-top: 3px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .gallery-header {
        padding: 1.5rem 0;
    }
    
    .header-content {
        flex-direction: column;
        gap: 1.5rem;
        text-align: center;
    }
    
    .gallery-title {
        font-size: 2rem;
    }
    
    .gallery-stats {
        gap: 1.5rem;
    }
    
    
    .gallery-container {
        padding: 0 15px;
    }
}

@media (max-width: 480px) {
    .gallery-title {
        font-size: 1.5rem;
    }
    
    .gallery-title .icon {
        font-size: 2rem;
    }
}

/* Modal Styles */
.image-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: none;
}

.image-modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
}

.modal-content {
    position: relative;
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    width: 80vw;
    max-width: 1400px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #1f2937;
    flex: 1;
    text-align: center;
    padding-right: 3rem; /* Space for close button */
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #6b7280;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
    position: absolute;
    right: 1.5rem;
    top: 1.5rem;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.modal-body {
    display: flex;
    flex: 1;
    overflow: hidden;
}

.modal-image-section {
    flex: 1.2; /* Make image section wider */
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background: radial-gradient(circle at center, #f5f5f5 0%, #dddddd 100%); /* Radial gradient background */
    position: relative;
    border-radius: 0 0 0 12px;
}

/* Horizontal image styles */
.modal-image-section.horizontal {
    flex: 1.5; /* Even wider for horizontal images */
}

.modal-details-section.horizontal {
    flex: 0.8; /* Narrower for horizontal images */
}

.modal-image {
    max-width: 100%;
    max-height: 600px;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    cursor: pointer;
    transition: all 0.3s ease;
}

.modal-image:hover {
    filter: brightness(1.05);
}

/* External Navigation */
.external-nav-btn {
    position: fixed;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.5rem;
    transition: all 0.3s ease;
    z-index: 10001;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
}

.external-nav-btn:hover {
    background: rgba(0, 0, 0, 0.9);
    transform: translateY(-50%) scale(1.1);
}

.external-nav-btn.prev-btn {
    left: 2rem;
}

.external-nav-btn.next-btn {
    right: 2rem;
}

.nav-icon {
    font-size: 1.5rem;
    font-weight: bold;
}

/* Toast Notification */
.toast-notification {
    position: fixed;
    bottom: 2rem;
    left: 2rem;
    background: #374151;
    color: white;
    padding: 0.875rem 1.25rem;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    z-index: 10003;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
    max-width: 300px;
    border: 1px solid #4b5563;
}

.toast-notification.show {
    opacity: 1;
    transform: translateY(0);
}

.toast-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.toast-icon {
    font-size: 1rem;
    font-weight: bold;
}

.toast-message {
    font-size: 0.8rem;
    font-weight: 500;
}

.modal-details-section {
    flex: 1;
    padding: 2rem;
    overflow-y: auto;
}

.image-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.form-input,
.form-textarea {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 0.75rem;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
}

.image-metadata {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.image-metadata h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
    margin: 0 0 1rem 0;
}

.metadata-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.badge-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
    transition: all 0.2s ease;
}

/* Subtle URL Copy Badges */
.badge-group.url-copy {
    cursor: pointer;
    background: #f8fafc;
    border-color: #e2e8f0;
    position: relative;
}

.badge-group.url-copy:hover {
    background: #f1f5f9;
    border-color: #3b82f6;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.badge-group.url-copy .badge-icon.avif-icon {
    color: #10b981;
}

.badge-group.url-copy .badge-icon.webp-icon {
    color: #f59e0b;
}

.badge-group.url-copy .url-preview {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.7rem;
    color: #6b7280;
    background: #ffffff;
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.copy-hint {
    font-size: 12px;
    color: #9ca3af;
    opacity: 0.6;
    transition: all 0.2s ease;
}

.badge-group.url-copy:hover .copy-hint {
    color: #3b82f6;
    opacity: 1;
}

/* Regular badge styling */
.badge-icon {
    font-size: 16px;
    opacity: 0.8;
    color: #6b7280;
}

.badge-label {
    color: #6b7280;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-text {
    color: #374151;
    font-weight: 500;
    white-space: nowrap;
}


.modal-footer {
    padding: 1.5rem 2rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
}

.modal-actions {
    display: flex;
    gap: 0.75rem;
}

.modal-buttons {
    display: flex;
    gap: 0.75rem;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.btn-primary {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.btn-primary:hover {
    background: #2563eb;
    border-color: #2563eb;
}

.btn-secondary {
    background: white;
    color: #374151;
    border-color: #d1d5db;
}

.btn-secondary:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.btn-danger {
    background: #ef4444;
    color: white;
    border-color: #ef4444;
}

.btn-danger:hover {
    background: #dc2626;
    border-color: #dc2626;
}

.btn-optimize {
    background: #f59e0b;
    color: white;
    border-color: #f59e0b;
}

.btn-optimize:hover {
    background: #d97706;
    border-color: #d97706;
}

/* Responsive Modal */
@media (max-width: 768px) {
    .modal-content {
        width: 95vw;
        max-height: 95vh;
    }
    
    .modal-body {
        flex-direction: column;
    }
    
    .modal-image-section {
        padding: 1rem;
    }
    
    .modal-image {
        max-height: 300px;
    }
    
    .modal-details-section {
        padding: 1rem;
    }
    
    .modal-footer {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .modal-actions,
    .modal-buttons {
        justify-content: center;
    }
    
    .external-nav-btn {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
    
    .external-nav-btn.prev-btn {
        left: 1rem;
    }
    
    .external-nav-btn.next-btn {
        right: 1rem;
    }
    
    .toast-notification {
        bottom: 1rem;
        left: 1rem;
        right: 1rem;
        max-width: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let loading = false;
    let currentPage = 1;
    let hasMore = <?php echo $has_more ? 'true' : 'false'; ?>;
    let currentImageId = null;
    let allImages = [];
    
    // Initialize
    initializeDragDrop();
    initializeInfiniteScroll();
    initializeImageHandlers();
    loadAllImages();
    
    function initializeDragDrop() {
        const dragDropTarget = document.getElementById('drag-drop-target');
        const dragDropOverlay = document.getElementById('drag-drop-overlay');
        const fileInput = document.getElementById('media-upload');
        
        // Click to browse
        dragDropTarget.addEventListener('click', () => {
            fileInput.click();
        });
        
        // File input change
        fileInput.addEventListener('change', handleFileUpload);
        
        // Drag and drop events
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            document.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            document.addEventListener(eventName, () => {
                dragDropOverlay.classList.add('active');
                document.getElementById('file-count').textContent = '0';
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            document.addEventListener(eventName, () => {
                dragDropOverlay.classList.remove('active');
            }, false);
        });
        
        document.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const files = e.dataTransfer.files;
            document.getElementById('file-count').textContent = files.length;
            handleFiles(files);
        }
        
        function handleFileUpload(e) {
            const files = e.target.files;
            handleFiles(files);
        }
        
        function handleFiles(files) {
            const formData = new FormData();
            
            Array.from(files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    formData.append('files[]', file);
                }
            });
            
            if (formData.getAll('files[]').length === 0) {
                alert('Please select only image files.');
                return;
            }
            
            // Upload files
            uploadFiles(formData);
        }
        
        function uploadFiles(formData) {
            const overlay = document.getElementById('drag-drop-overlay');
            overlay.querySelector('h3').textContent = 'Uploading images...';
            overlay.querySelector('p').textContent = 'Please wait while your images are uploaded';
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=tomatillo_upload_images', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show new images
                    window.location.reload();
                } else {
                    alert('Upload failed: ' + (data.data || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                alert('Upload failed. Please try again.');
            })
            .finally(() => {
                overlay.classList.remove('active');
            });
        }
    }
    
    function initializeInfiniteScroll() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && hasMore) {
                    loadMoreImages();
                }
            });
        });
        
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) {
            observer.observe(loadingIndicator);
        }
    }
    
    function loadMoreImages() {
        if (loading || !hasMore) return;
        
        loading = true;
        currentPage++;
        
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.style.display = 'flex';
        }
        
        fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=tomatillo_load_more_images&page=${currentPage}&nonce=<?php echo wp_create_nonce('tomatillo_load_more_images'); ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.html) {
                    const grid = document.getElementById('masonry-grid');
                    grid.insertAdjacentHTML('beforeend', data.data.html);
                    hasMore = data.data.has_more;
                    
                    if (!hasMore && loadingIndicator) {
                        loadingIndicator.style.display = 'none';
                    }
                    
                    // Update allImages array
                    loadAllImages();
                } else {
                    hasMore = false;
                    if (loadingIndicator) {
                        loadingIndicator.style.display = 'none';
                    }
                }
                loading = false;
            })
            .catch(error => {
                console.error('Error loading more images:', error);
                loading = false;
                hasMore = false;
                if (loadingIndicator) {
                    loadingIndicator.style.display = 'none';
                }
            });
    }
    
    function loadAllImages() {
        allImages = Array.from(document.querySelectorAll('.gallery-item')).map(item => item.dataset.id);
    }
    
    function initializeImageHandlers() {
        document.addEventListener('click', function(e) {
            const galleryItem = e.target.closest('.gallery-item');
            if (!galleryItem) return;
            
            const imageId = galleryItem.dataset.id;
            const actionBtn = e.target.closest('.action-btn');
            
            if (actionBtn) {
                e.preventDefault();
                e.stopPropagation();
                
                if (actionBtn.classList.contains('view-btn')) {
                    openModal(imageId);
                } else if (actionBtn.classList.contains('edit-btn')) {
                    openModal(imageId);
                } else if (actionBtn.classList.contains('download-btn')) {
                    downloadImage(imageId);
                } else if (actionBtn.classList.contains('optimize-btn')) {
                    optimizeImage(imageId);
                }
            } else {
                // Click on image - open modal
                openModal(imageId);
            }
        });
    }
    
    function openModal(imageId) {
        currentImageId = imageId;
        const modal = document.getElementById('image-modal');
        
        // Load image data
        loadImageData(imageId).then(data => {
            populateModal(data);
            modal.classList.add('active');
            
            // Add keyboard event listeners
            document.addEventListener('keydown', handleKeyboard);
        });
    }
    
    function loadImageData(imageId) {
        return fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=tomatillo_get_image_data&image_id=${imageId}&nonce=<?php echo wp_create_nonce('tomatillo_get_image_data'); ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    return data.data;
                } else {
                    throw new Error(data.data || 'Failed to load image data');
                }
            });
    }
    
    function populateModal(data) {
        // Store data globally for image click
        currentImageData = data;
        
        // Image - use original URL for display (not optimized version)
        document.getElementById('modal-image').src = data.url;
        document.getElementById('modal-image').alt = data.alt_text || '';
        
        // Modal title (use uploaded filename or title)
        const modalTitle = document.getElementById('modal-image-title');
        modalTitle.textContent = data.title || data.filename || 'Untitled Image';
        
        // Form fields
        document.getElementById('image-title').value = data.title || '';
        document.getElementById('image-alt').value = data.alt_text || '';
        document.getElementById('image-caption').value = data.caption || '';
        document.getElementById('image-description').value = data.description || '';
        
        // Basic metadata
        document.getElementById('modal-image-id').textContent = data.id;
        document.getElementById('modal-image-type').textContent = data.mime_type;
        document.getElementById('modal-image-size').textContent = data.dimensions;
        document.getElementById('modal-image-file').textContent = data.file_size;
        document.getElementById('modal-image-date-user').textContent = `${data.date} by ${data.uploader || 'Unknown'}`;
        document.getElementById('modal-image-url').textContent = data.url;
        
        // URL copy badges - show/hide and populate previews
        const avifUrlBadge = document.getElementById('avif-url-badge');
        const webpUrlBadge = document.getElementById('webp-url-badge');
        
        // Original URL
        document.getElementById('modal-image-url').textContent = data.url;
        document.getElementById('modal-image-url-preview').textContent = data.url.split('/').pop() || 'Click to copy';
        
        // AVIF URL
        if (data.avif_url) {
            avifUrlBadge.style.display = 'flex';
            document.getElementById('modal-avif-url').textContent = data.avif_url;
            document.getElementById('modal-avif-url-preview').textContent = data.avif_url.split('/').pop() || 'Click to copy';
        } else {
            avifUrlBadge.style.display = 'none';
        }
        
        // WebP URL
        if (data.webp_url) {
            webpUrlBadge.style.display = 'flex';
            document.getElementById('modal-webp-url').textContent = data.webp_url;
            document.getElementById('modal-webp-url-preview').textContent = data.webp_url.split('/').pop() || 'Click to copy';
        } else {
            webpUrlBadge.style.display = 'none';
        }
        
        // Space saved badge
        const savingsBadge = document.getElementById('savings-badge');
        if (data.space_saved && data.space_saved !== '0 B') {
            savingsBadge.style.display = 'flex';
            document.getElementById('modal-space-saved').textContent = data.space_saved;
        } else {
            savingsBadge.style.display = 'none';
        }
        
        // Detect image orientation and apply appropriate styles
        const modalImage = document.getElementById('modal-image');
        const imageSection = document.querySelector('.modal-image-section');
        const detailsSection = document.querySelector('.modal-details-section');
        
        modalImage.onload = function() {
            const isHorizontal = this.naturalWidth > this.naturalHeight;
            
            if (isHorizontal) {
                imageSection.classList.add('horizontal');
                detailsSection.classList.add('horizontal');
            } else {
                imageSection.classList.remove('horizontal');
                detailsSection.classList.remove('horizontal');
            }
        };
        
        // Show/hide optimize button based on optimization status
        const optimizeBtn = document.getElementById('optimize-btn');
        if (data.is_optimized) {
            optimizeBtn.style.display = 'none';
        } else {
            optimizeBtn.style.display = 'inline-block';
        }
        
        // Ensure button is enabled and reset to original text when populating modal
        if (optimizeBtn) {
            optimizeBtn.disabled = false;
            optimizeBtn.textContent = 'Optimize Image';
            optimizeBtn.setAttribute('onclick', 'optimizeImageModal()');
        }
    }
    
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('URL copied to clipboard!');
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showToast('URL copied to clipboard!');
        });
    }
    
    function closeModal() {
        const modal = document.getElementById('image-modal');
        modal.classList.remove('active');
        currentImageId = null;
        
        // Remove keyboard event listeners
        document.removeEventListener('keydown', handleKeyboard);
    }
    
    function handleKeyboard(e) {
        if (!currentImageId) return;
        
        switch(e.key) {
            case 'Escape':
                e.preventDefault();
                closeModal();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                navigateImage(-1);
                break;
            case 'ArrowRight':
                e.preventDefault();
                navigateImage(1);
                break;
        }
    }
    
    function showToast(message, type = 'success') {
        // Remove any existing toast first
        const existingToast = document.getElementById('toast-notification');
        if (existingToast) {
            existingToast.remove();
        }
        
        // Create new toast element
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.id = 'toast-notification';
        
        const toastContent = document.createElement('div');
        toastContent.className = 'toast-content';
        
        const toastIcon = document.createElement('span');
        toastIcon.className = 'toast-icon';
        
        const toastMessage = document.createElement('span');
        toastMessage.className = 'toast-message';
        toastMessage.textContent = message;
        
        toastContent.appendChild(toastIcon);
        toastContent.appendChild(toastMessage);
        toast.appendChild(toastContent);
        
        // Set styling based on type
        if (type === 'success') {
            toast.style.background = '#374151';
            toast.style.borderColor = '#4b5563';
            toastIcon.textContent = '✓';
        } else if (type === 'error') {
            toast.style.background = '#dc2626';
            toast.style.borderColor = '#ef4444';
            toastIcon.textContent = '✕';
        } else if (type === 'warning') {
            toast.style.background = '#f59e0b';
            toast.style.borderColor = '#d97706';
            toastIcon.textContent = '⚠';
        }
        
        // Add to DOM
        document.body.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Remove from DOM after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300); // Wait for fade-out animation
        }, 3000);
    }
    
    function navigateImage(direction) {
        if (!currentImageId) return;
        
        const currentIndex = allImages.indexOf(currentImageId);
        let newIndex = currentIndex + direction;
        
        if (newIndex < 0) newIndex = allImages.length - 1;
        if (newIndex >= allImages.length) newIndex = 0;
        
        const newImageId = allImages[newIndex];
        openModal(newImageId);
    }
    
    function saveImageMetadata() {
        if (!currentImageId) return;
        
        const formData = new FormData();
        formData.append('image_id', currentImageId);
        formData.append('title', document.getElementById('image-title').value);
        formData.append('alt_text', document.getElementById('image-alt').value);
        formData.append('caption', document.getElementById('image-caption').value);
        formData.append('description', document.getElementById('image-description').value);
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=tomatillo_save_image_metadata', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Metadata saved successfully!');
                // Update modal title if title was changed
                const newTitle = document.getElementById('image-title').value;
                if (newTitle) {
                    document.getElementById('modal-image-title').textContent = newTitle;
                }
            } else {
                showToast('Failed to save metadata: ' + (data.data || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Save error:', error);
            showToast('Failed to save metadata. Please try again.', 'error');
        });
    }
    
    // Global variable to store current image data
    let currentImageData = null;
    
    function copyImageUrlFromData() {
        if (currentImageData && currentImageData.url) {
            const formattedUrl = `@${currentImageData.url}`;
            console.log('DEBUG: Using data.url directly:', currentImageData.url);
            console.log('DEBUG: Formatted URL to copy:', formattedUrl);
            navigator.clipboard.writeText(formattedUrl).then(() => {
                showToast('Original URL copied to clipboard!');
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = formattedUrl;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast('Original URL copied to clipboard!');
            });
        } else {
            console.error('DEBUG: No currentImageData available');
            showToast('Error: No image data available', 'error');
        }
    }
    
    function copyImageUrl() {
        const originalUrl = document.getElementById('modal-image-url').textContent;
        console.log('DEBUG: modal-image-url contains:', originalUrl);
        
        // Remove any existing @ prefix and copy the clean URL
        const cleanUrl = originalUrl.startsWith('@') ? originalUrl.substring(1) : originalUrl;
        
        console.log('DEBUG: Clean URL to copy:', cleanUrl);
        
        navigator.clipboard.writeText(cleanUrl).then(() => {
            showToast('Original URL copied to clipboard!');
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = cleanUrl;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showToast('Original URL copied to clipboard!');
        });
    }
    
    function downloadImage() {
        // Use the original URL, not the optimized version
        const originalUrl = document.getElementById('modal-image-url').textContent;
        const link = document.createElement('a');
        link.href = originalUrl;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    function optimizeImageModal() {
        if (!currentImageId) return;
        
        const optimizeBtn = document.getElementById('optimize-btn');
        const originalText = optimizeBtn.textContent;
        
        // Show loading state
        optimizeBtn.textContent = 'Optimizing...';
        optimizeBtn.disabled = true;
        
        fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=tomatillo_optimize_image&image_id=${currentImageId}&nonce=<?php echo wp_create_nonce('tomatillo_optimize_image'); ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Image optimized successfully!');
                    // Hide the optimize button since image is now optimized
                    optimizeBtn.style.display = 'none';
                    // Reload the image data to update the modal with new optimization info
                    loadImageData(currentImageId).then(populateModal);
                } else {
                    // Check if it's a savings threshold issue
                    if (data.data && data.data.includes('well-optimized') && data.data.includes('additional savings')) {
                        // Extract savings percentage from the message
                        const savingsMatch = data.data.match(/(\d+)%/);
                        const savings = savingsMatch ? savingsMatch[1] : 'unknown';
                        
                        showToast(`This image is already well-optimized! Only ${savings}% additional savings possible, which is below the minimum threshold. No optimization needed.`, 'warning');
                    } else {
                        showToast('Failed to optimize image: ' + (data.data || 'Unknown error'), 'error');
                    }
                }
            })
            .catch(error => {
                showToast('Error optimizing image: ' + error.message, 'error');
            })
            .finally(() => {
                // Restore button state - get fresh reference to ensure we're updating the right button
                const currentOptimizeBtn = document.getElementById('optimize-btn');
                if (currentOptimizeBtn) {
                    currentOptimizeBtn.textContent = 'Optimize Image';
                    currentOptimizeBtn.disabled = false;
                }
            });
    }
    
    function deleteImage() {
        if (!currentImageId) return;
        
        if (!confirm('Are you sure you want to delete this image? This action cannot be undone.')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('image_id', currentImageId);
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=tomatillo_delete_image', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove image from gallery
                const galleryItem = document.querySelector(`[data-id="${currentImageId}"]`);
                if (galleryItem) {
                    galleryItem.remove();
                }
                closeModal();
                alert('Image deleted successfully!');
            } else {
                alert('Failed to delete image: ' + (data.data || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            alert('Failed to delete image. Please try again.');
        });
    }
    
    function optimizeImage(imageId) {
        const optimizeBtn = document.querySelector(`[data-id="${imageId}"] .optimize-btn`);
        if (optimizeBtn) {
            optimizeBtn.innerHTML = '<span>⏳</span>';
            optimizeBtn.disabled = true;
        }
        
        fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=tomatillo_optimize_image&image_id=${imageId}&nonce=<?php echo wp_create_nonce('tomatillo_optimize_image'); ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.querySelector(`[data-id="${imageId}"] .optimization-badge`);
                    if (badge) {
                        badge.innerHTML = '<span class="badge optimized">✓ Optimized</span>';
                    }
                    
                    if (optimizeBtn) {
                        optimizeBtn.style.display = 'none';
                    }
                } else {
                    alert('Failed to optimize image: ' + (data.data || 'Unknown error'));
                    if (optimizeBtn) {
                        optimizeBtn.innerHTML = '<span>⚡</span>';
                        optimizeBtn.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error optimizing image:', error);
                alert('Failed to optimize image');
                if (optimizeBtn) {
                    optimizeBtn.innerHTML = '<span>⚡</span>';
                    optimizeBtn.disabled = false;
                }
            });
    }
    
    // Global functions for modal buttons
    window.closeModal = closeModal;
    window.navigateImage = navigateImage;
    window.saveImageMetadata = saveImageMetadata;
    window.copyImageUrl = copyImageUrl;
    window.downloadImage = downloadImage;
    window.deleteImage = deleteImage;
    window.optimizeImageModal = optimizeImageModal;
});
</script>
