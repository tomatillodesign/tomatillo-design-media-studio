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
$total_files = isset($media_stats['total_documents']) ? $media_stats['total_documents'] : 0;

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

// Get non-image files (newest first)
$files = get_posts(array(
    'post_type' => 'attachment',
    'post_mime_type' => array(
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'application/rtf',
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        'audio/mp4',
        'audio/aac',
        'video/mp4',
        'video/avi',
        'video/mov',
        'video/wmv',
        'video/webm',
        'video/ogg'
    ),
    'post_status' => 'inherit',
    'posts_per_page' => $images_per_page,
    'offset' => $offset,
    'orderby' => 'date',
    'order' => 'DESC'
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
                <?php if ($total_files > 0): ?>
                <span class="stat">
                    <strong><?php echo number_format($total_files); ?></strong>
                    <span class="stat-label">Files</span>
                </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Drag & Drop Target -->
        <div class="drag-drop-target" id="drag-drop-target">
            <div class="drag-drop-content">
                <div class="drag-drop-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7,10 12,15 17,10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                </div>
                <div class="drag-drop-text">
                    <strong>Drop files here</strong>
                    <span>or click to browse</span>
                </div>
            </div>
            <input type="file" id="media-upload" multiple style="display: none;">
        </div>
    </div>
    
    <!-- Drag & Drop Overlay -->
    <div class="drag-drop-overlay" id="drag-drop-overlay">
        <div class="drag-drop-message">
            <div class="drag-drop-icon-large">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7,10 12,15 17,10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
            </div>
            <h3>Drop files to upload</h3>
            <p>Release to add <?php echo '<span id="file-count">0</span>'; ?> files to your library</p>
        </div>
    </div>

    <!-- Upload Progress Overlay -->
    <div class="upload-progress-overlay" id="upload-progress-overlay">
        <div class="upload-progress-modal">
            <div class="upload-header">
                <h3>Uploading Files</h3>
                <button class="upload-cancel-btn" id="upload-cancel-btn">Cancel</button>
            </div>
            <div class="upload-progress-container">
                <div class="upload-progress-bar">
                    <div class="upload-progress-fill" id="upload-progress-fill"></div>
                </div>
                <div class="upload-status" id="upload-status">Preparing upload...</div>
            </div>
            <div class="upload-files-list" id="upload-files-list"></div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="action-bar">
        <div class="action-bar-content">
            <!-- Tabs/Filter -->
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="images">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21,15 16,10 5,21"></polyline>
                    </svg>
                    Images
                </button>
                <button class="filter-tab" data-filter="files">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14,2 14,8 20,8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10,9 9,9 8,9"></polyline>
                    </svg>
                    Files
                </button>
            </div>

            <!-- Search -->
            <div class="search-container">
                <div class="search-input-wrapper">
                    <input type="text" id="gallery-search" placeholder="Search by title, alt text, ID, etc..." class="search-input">
                    <button class="search-clear" id="search-clear" style="display: none;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <div class="bulk-actions-panel" id="bulk-actions-panel">
                    <span class="bulk-count" id="bulk-count">0 selected</span>
                    <button class="bulk-action-btn bulk-delete" id="bulk-delete" disabled>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3,6 5,6 21,6"></polyline>
                            <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
                        </svg>
                        Delete Selected
                    </button>
                    <button class="bulk-action-btn bulk-cancel" id="bulk-cancel">
                        Cancel
                    </button>
                </div>
                <button class="bulk-select-btn" id="bulk-select-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <path d="M9 9h6v6H9z"></path>
                    </svg>
                    Bulk Select Mode
                </button>
            </div>
        </div>
    </div>

    <!-- Gallery Grid -->
    <div class="gallery-container">
        <!-- Images Grid -->
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
                    // Use the smallest optimized image available (AVIF → WebP → scaled original)
                    $image_url = ($plugin->core) ? $plugin->core->get_best_optimized_image_url($image->ID, 'large') : wp_get_attachment_image_url($image->ID, 'large');
                    $image_alt = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
                    $image_title = $image->post_title ?: $image->post_name;
                    $image_date = $image->post_date;
                    
                    // Get optimization data to find smallest file size
                    $optimization_data = ($plugin->core) ? $plugin->core->get_optimization_data($image->ID) : null;
                    $smallest_size = filesize(get_attached_file($image->ID)); // Default to original
                    $smallest_format = 'Original';
                    
                    if ($optimization_data) {
                        // Check AVIF
                        if (!empty($optimization_data['avif_url']) && file_exists(str_replace(home_url('/'), ABSPATH, $optimization_data['avif_url']))) {
                            $avif_size = filesize(str_replace(home_url('/'), ABSPATH, $optimization_data['avif_url']));
                            if ($avif_size < $smallest_size) {
                                $smallest_size = $avif_size;
                                $smallest_format = 'AVIF';
                            }
                        }
                        
                        // Check WebP
                        if (!empty($optimization_data['webp_url']) && file_exists(str_replace(home_url('/'), ABSPATH, $optimization_data['webp_url']))) {
                            $webp_size = filesize(str_replace(home_url('/'), ABSPATH, $optimization_data['webp_url']));
                            if ($webp_size < $smallest_size) {
                                $smallest_size = $webp_size;
                                $smallest_format = 'WebP';
                            }
                        }
                        
                        // Check scaled original
                        if (!empty($optimization_data['scaled_url']) && file_exists(str_replace(home_url('/'), ABSPATH, $optimization_data['scaled_url']))) {
                            $scaled_size = filesize(str_replace(home_url('/'), ABSPATH, $optimization_data['scaled_url']));
                            if ($scaled_size < $smallest_size) {
                                $smallest_size = $scaled_size;
                                $smallest_format = 'Scaled';
                            }
                        }
                    }
                    
                    $file_size_formatted = size_format($smallest_size);
                    
                    // Check if optimized
                    $is_optimized = ($plugin->core) ? $plugin->core->is_image_optimized($image->ID) : false;
                    ?>
                    
                    <div class="gallery-item" 
                         data-id="<?php echo $image->ID; ?>"
                         data-title="<?php echo esc_attr($image_title); ?>"
                         data-alt="<?php echo esc_attr($image_alt); ?>"
                         data-caption="<?php echo esc_attr($image->post_excerpt); ?>"
                         data-description="<?php echo esc_attr($image->post_content); ?>"
                         data-filename="<?php echo esc_attr(basename(get_attached_file($image->ID))); ?>">
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
                                            <?php 
                                            $uploader = get_userdata($image->post_author);
                                            $uploader_name = $uploader ? $uploader->display_name : 'Unknown';
                                            echo date('M j, Y', strtotime($image_date)) . ' • ' . $file_size_formatted . ' • Uploaded by: ' . esc_html($uploader_name); 
                                            ?>
                                        </p>
                                    </div>
                                    
                                </div>
                                
                                <!-- Hover Info Overlay - Only show if there's content -->
                                <?php
                                // Get optimized file info
                                $optimized_info = '';
                                if ($is_optimized && $plugin->core) {
                                    $avif_url = $plugin->core->get_optimized_image_url($image->ID, 'avif');
                                    $webp_url = $plugin->core->get_optimized_image_url($image->ID, 'webp');
                                    $scaled_url = wp_get_attachment_image_url($image->ID, 'medium');
                                    
                                    // Find smallest optimized size
                                    $smallest_size = '';
                                    $smallest_url = '';
                                    
                                    if ($avif_url) {
                                        $avif_size = filesize(str_replace(home_url('/'), ABSPATH, $avif_url));
                                        if (!$smallest_size || $avif_size < $smallest_size) {
                                            $smallest_size = $avif_size;
                                            $smallest_url = $avif_url;
                                            $format = 'AVIF';
                                        }
                                    }
                                    
                                    if ($webp_url) {
                                        $webp_size = filesize(str_replace(home_url('/'), ABSPATH, $webp_url));
                                        if (!$smallest_size || $webp_size < $smallest_size) {
                                            $smallest_size = $webp_size;
                                            $smallest_url = $webp_url;
                                            $format = 'WebP';
                                        }
                                    }
                                    
                                    if ($scaled_url) {
                                        $scaled_size = filesize(str_replace(home_url('/'), ABSPATH, $scaled_url));
                                        if (!$smallest_size || $scaled_size < $smallest_size) {
                                            $smallest_size = $scaled_size;
                                            $smallest_url = $scaled_url;
                                            $format = 'Scaled';
                                        }
                                    }
                                    
                                    if ($smallest_size) {
                                        $optimized_info = $format . ' • ' . size_format($smallest_size);
                                    }
                                }
                                
                                // Get attachment info
                                $attachments = get_posts(array(
                                    'post_type' => 'any',
                                    'meta_query' => array(
                                        array(
                                            'key' => '_thumbnail_id',
                                            'value' => $image->ID,
                                            'compare' => '='
                                        )
                                    ),
                                    'posts_per_page' => 1
                                ));
                                
                                $attachment_info = '';
                                if (!empty($attachments)) {
                                    $attached_post = $attachments[0];
                                    $attachment_info = 'Attached to: ' . $attached_post->post_title;
                                }
                                
                                // Only show hover overlay if there's actual content
                                if ($attachment_info):
                                ?>
                                <div class="hover-info-overlay">
                                    <div class="hover-info-content">
                                        <?php if ($attachment_info): ?>
                                            <div class="hover-info-item">
                                                <span class="hover-info-label"><?php echo esc_html($attachment_info); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
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
        
        <!-- Files Grid -->
        <div class="files-grid" id="files-grid" style="display: none;">
            <?php if (empty($files)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📄</div>
                    <h3>No files yet</h3>
                    <p>Drag and drop files above or click the upload area to get started</p>
                </div>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                    <?php
                    $file_url = wp_get_attachment_url($file->ID);
                    $file_title = $file->post_title ?: $file->post_name;
                    $file_date = $file->post_date;
                    $file_size = filesize(get_attached_file($file->ID));
                    $file_size_formatted = size_format($file_size);
                    $file_type = get_post_mime_type($file->ID);
                    
                    // Get file thumbnail or icon based on type
                    $file_thumbnail = '';
                    $file_icon_class = 'file-icon-default';
                    
                    if (strpos($file_type, 'pdf') !== false) {
                        // Try to get PDF thumbnail first
                        $pdf_thumbnail = wp_get_attachment_image_url($file->ID, 'medium');
                        if ($pdf_thumbnail) {
                            $file_thumbnail = $pdf_thumbnail;
                            $file_icon_class = 'file-icon-pdf-thumbnail'; // Special class for PDFs with thumbnails
                        } else {
                            $file_icon_class = 'file-icon-pdf';
                        }
                    } elseif (strpos($file_type, 'word') !== false) {
                        $file_icon_class = 'file-icon-word';
                    } elseif (strpos($file_type, 'excel') !== false) {
                        $file_icon_class = 'file-icon-excel';
                    } elseif (strpos($file_type, 'powerpoint') !== false) {
                        $file_icon_class = 'file-icon-powerpoint';
                    } elseif (strpos($file_type, 'text') !== false) {
                        $file_icon_class = 'file-icon-text';
                    } elseif (strpos($file_type, 'zip') !== false) {
                        $file_icon_class = 'file-icon-zip';
                    } elseif (strpos($file_type, 'audio') !== false) {
                        $file_icon_class = 'file-icon-audio';
                    } elseif (strpos($file_type, 'video') !== false) {
                        $file_icon_class = 'file-icon-video';
                    }
                    ?>
                    
                    <div class="file-item" 
                         data-id="<?php echo $file->ID; ?>"
                         data-title="<?php echo esc_attr($file_title); ?>"
                         data-alt="<?php echo esc_attr($file->post_excerpt); ?>"
                         data-caption="<?php echo esc_attr($file->post_excerpt); ?>"
                         data-description="<?php echo esc_attr($file->post_content); ?>"
                         data-filename="<?php echo esc_attr(basename(get_attached_file($file->ID))); ?>">
                        <div class="file-container">
                            <div class="file-icon <?php echo $file_icon_class; ?>">
                                <?php if ($file_thumbnail): ?>
                                    <img src="<?php echo esc_url($file_thumbnail); ?>" alt="<?php echo esc_attr($file_title); ?>" class="file-thumbnail">
                                <?php endif; ?>
                            </div>
                            
                            <!-- File Info -->
                            <div class="file-info">
                                <h4 class="file-title"><?php echo esc_html($file_title); ?></h4>
                                <p class="file-meta">
                                    <?php 
                                    $uploader = get_userdata($file->post_author);
                                    $uploader_name = $uploader ? $uploader->display_name : 'Unknown';
                                    echo date('M j, Y', strtotime($file_date)) . ' • ' . $file_size_formatted . ' • Uploaded by: ' . esc_html($uploader_name); 
                                    ?>
                                </p>
                                <p class="file-type">
                                    <span class="file-type-pill">
                                        <span class="dashicons file-type-icon file-type-<?php echo strtolower(pathinfo(get_attached_file($file->ID), PATHINFO_EXTENSION)); ?>"></span>
                                        <?php echo strtoupper(pathinfo(get_attached_file($file->ID), PATHINFO_EXTENSION)); ?>
                                    </span>
                                </p>
                            </div>
                            
                            <!-- File Actions -->
                            <div class="file-actions">
                                <button class="action-btn copy-url-btn" title="Copy File URL" onclick="copyFileUrl(<?php echo $file->ID; ?>, '<?php echo esc_url($file_url); ?>')">
                                    <span class="dashicons dashicons-admin-links"></span>
                                </button>
                                <button class="action-btn open-file-btn" title="Open File" onclick="window.open('<?php echo esc_url($file_url); ?>', '_blank')">
                                    <span class="dashicons dashicons-external"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
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
    margin: 0;
    padding: 0;
    margin-left: -20px;
}

/* Header */
.gallery-header {
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    padding: 1.5rem 0;
    margin-bottom: 2rem;
}

.header-content {
    max-width: 100%;
    margin: 0;
    padding: 0 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
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
    padding: 1.5rem 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 200px;
    margin: 0 1rem;
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
    color: #6b7280;
    transition: color 0.3s ease;
}

.drag-drop-target:hover .drag-drop-icon {
    color: #3b82f6;
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
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.drag-drop-overlay.active {
    opacity: 1;
    visibility: visible;
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
    color: #3b82f6;
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

/* Upload Progress Overlay */
.upload-progress-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    z-index: 10002;
    display: none;
    align-items: center;
    justify-content: center;
}

.upload-progress-overlay.active {
    display: flex;
}

.upload-progress-modal {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    min-width: 400px;
    max-width: 600px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.upload-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.upload-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.upload-cancel-btn {
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

.upload-cancel-btn:hover {
    background: #dc2626;
}

.upload-progress-container {
    margin-bottom: 1.5rem;
}

.upload-progress-bar {
    width: 100%;
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.75rem;
}

.upload-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    border-radius: 4px;
    transition: width 0.3s ease;
    width: 0%;
}

.upload-progress-fill.processing {
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.upload-status {
    font-size: 0.875rem;
    color: #6b7280;
    text-align: center;
}

.upload-files-list {
    max-height: 200px;
    overflow-y: auto;
    border-top: 1px solid #e5e7eb;
    padding-top: 1rem;
}

.upload-file-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.upload-file-item:last-child {
    border-bottom: none;
}

.upload-file-name {
    font-size: 0.875rem;
    color: #374151;
    flex: 1;
    margin-right: 1rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.upload-file-status {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-weight: 500;
}

.upload-file-status.pending {
    background: #fef3c7;
    color: #92400e;
}

.upload-file-status.uploading {
    background: #dbeafe;
    color: #1e40af;
}

.upload-file-status.success {
    background: #d1fae5;
    color: #065f46;
}

.upload-file-status.error {
    background: #fee2e2;
    color: #991b1b;
}

/* Gallery Container - Contained Width with Padding */
.gallery-container {
    max-width: calc(100vw - 2rem);
    margin: 0 auto;
    padding: 0 1rem;
    width: 100%;
    box-sizing: border-box;
}

/* Google Photos Style Masonry */
.masonry-grid {
    position: relative;
    padding-bottom: 2rem;
}

/* Gallery Items - Google Photos Style */
.gallery-item {
    position: absolute;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease;
    cursor: pointer;
    position: relative;
    max-width: 300px;
    break-inside: avoid;
    margin-bottom: 0.25rem;
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
    max-width: 100%;
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
    background: rgba(0, 0, 0, 0.8);
}

/* Hide action buttons on hover */

/* Hover Info Overlay */
.hover-info-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    padding: 1rem;
}

.gallery-item:hover .hover-info-overlay {
    opacity: 1;
}

.hover-info-content {
    background: rgba(0, 0, 0, 0.9);
    border-radius: 8px;
    padding: 1rem;
    color: white;
    text-align: center;
    max-width: 90%;
}

.hover-info-item {
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.hover-info-item:last-child {
    margin-bottom: 0;
}

.hover-info-label {
    font-weight: 600;
    color: #e5e7eb;
}

.hover-info-value {
    color: white;
    margin-left: 0.5rem;
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
        padding: 0 1rem;
        max-width: 100%;
        margin: 0;
        margin-bottom: 1rem;
    }
    
    .gallery-title {
        font-size: 2rem;
    }
    
    .gallery-stats {
        gap: 1.5rem;
    }
    
    
    .gallery-container {
        max-width: calc(100vw - 2rem);
        margin: 0 auto;
        padding: 0 1rem;
        width: 100%;
        box-sizing: border-box;
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

/* Files Grid Styles */
.files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    padding: 0;
    align-items: start; /* Ensure items align to start */
}

.file-item {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    cursor: pointer;
    overflow: visible; /* Changed from hidden to visible */
    height: auto; /* Ensure height is auto */
}

.file-item:hover {
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08); /* More subtle shadow */
}

.file-item.selected {
    border: 2px solid #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.file-container {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    height: auto; /* Changed from 100% to auto */
    min-height: 320px; /* Reduced from 380px to minimize whitespace */
}

.file-icon {
    text-align: center;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 120px; /* Increased from 80px */
    background: #f8f9fa;
    border-radius: 8px;
    border: 2px dashed #e5e7eb;
    position: relative;
    overflow: hidden;
}

.file-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 6px;
}

/* File Type Icons - Using Dashicons */
.file-icon-default::before {
    font-family: dashicons;
    content: "\f123"; /* dashicons-media-document */
    font-size: 3rem;
    color: #6b7280;
}

.file-icon-pdf::before {
    font-family: dashicons;
    content: "\f123"; /* dashicons-media-document */
    font-size: 3rem;
    color: #dc2626; /* Red for PDF */
}

.file-icon-pdf-thumbnail::before {
    display: none; /* Hide icon when thumbnail is present */
}

.file-icon-word::before {
    font-family: dashicons;
    content: "\f123"; /* dashicons-media-document */
    font-size: 3rem;
    color: #2563eb; /* Blue for Word */
}

.file-icon-excel::before {
    font-family: dashicons;
    content: "\f123"; /* dashicons-media-document */
    font-size: 3rem;
    color: #16a34a; /* Green for Excel */
}

.file-icon-powerpoint::before {
    font-family: dashicons;
    content: "\f123"; /* dashicons-media-document */
    font-size: 3rem;
    color: #ea580c; /* Orange for PowerPoint */
}

.file-icon-text::before {
    font-family: dashicons;
    content: "\f123"; /* dashicons-media-document */
    font-size: 3rem;
    color: #6b7280; /* Gray for text */
}

.file-icon-zip::before {
    font-family: dashicons;
    content: "\f123"; /* dashicons-media-document */
    font-size: 3rem;
    color: #7c3aed; /* Purple for ZIP */
}

.file-icon-audio::before {
    font-family: dashicons;
    content: "\f127"; /* dashicons-format-audio */
    font-size: 3rem;
    color: #059669; /* Green for audio */
}

.file-icon-video::before {
    font-family: dashicons;
    content: "\f126"; /* dashicons-format-video */
    font-size: 3rem;
    color: #dc2626; /* Red for video */
}

.file-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.file-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.file-meta {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.4;
}

.file-type {
    font-size: 0.75rem;
    color: #9ca3af;
    margin: 0;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.file-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
}

.file-actions .action-btn {
    flex: 1;
    padding: 0.5rem;
    background: #f8f9fa;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    box-shadow: none; /* Remove shadows */
}

.file-actions .action-btn:hover {
    background: #f1f3f4; /* More subtle background change */
    border-color: #d1d5db;
}

.file-actions .copy-url-btn:hover {
    background: #f0f4ff; /* Subtle blue tint */
    color: #3b82f6;
}

.file-actions .open-file-btn:hover {
    background: #f0fdf4; /* Subtle green tint */
    color: #22c55e;
}

.file-actions .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* File Type Pills */
.file-type-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: #f3f4f6;
    color: #6b7280;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.file-type-icon {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

/* File Type Icon Colors */
.file-type-icon.file-icon-pdf::before {
    content: "\f123"; /* dashicons-media-document */
    color: #dc2626; /* Red for PDF */
}

.file-type-icon.file-icon-word::before {
    content: "\f123"; /* dashicons-media-document */
    color: #2563eb; /* Blue for Word */
}

.file-type-icon.file-icon-excel::before {
    content: "\f123"; /* dashicons-media-document */
    color: #16a34a; /* Green for Excel */
}

.file-type-icon.file-icon-powerpoint::before {
    content: "\f123"; /* dashicons-media-document */
    color: #ea580c; /* Orange for PowerPoint */
}

.file-type-icon.file-icon-text::before {
    content: "\f123"; /* dashicons-media-document */
    color: #6b7280; /* Gray for text */
}

.file-type-icon.file-icon-zip::before {
    content: "\f123"; /* dashicons-media-document */
    color: #7c3aed; /* Purple for ZIP */
}

.file-type-icon.file-icon-audio::before {
    content: "\f127"; /* dashicons-format-audio */
    color: #059669; /* Green for audio */
}

.file-type-icon.file-icon-video::before {
    content: "\f126"; /* dashicons-format-video */
    color: #dc2626; /* Red for video */
}

.file-type-icon.file-icon-default::before {
    content: "\f123"; /* dashicons-media-document */
    color: #6b7280; /* Gray for default */
}

/* File Type Specific Icons for Pills */
.file-type-icon.file-type-pdf::before {
    content: "\f123"; /* dashicons-media-document */
    color: #dc2626; /* Red for PDF */
}

.file-type-icon.file-type-doc::before,
.file-type-icon.file-type-docx::before {
    content: "\f123"; /* dashicons-media-document */
    color: #2563eb; /* Blue for Word */
}

.file-type-icon.file-type-xls::before,
.file-type-icon.file-type-xlsx::before {
    content: "\f123"; /* dashicons-media-document */
    color: #16a34a; /* Green for Excel */
}

.file-type-icon.file-type-ppt::before,
.file-type-icon.file-type-pptx::before {
    content: "\f123"; /* dashicons-media-document */
    color: #ea580c; /* Orange for PowerPoint */
}

.file-type-icon.file-type-txt::before {
    content: "\f123"; /* dashicons-media-document */
    color: #6b7280; /* Gray for text */
}

.file-type-icon.file-type-zip::before {
    content: "\f123"; /* dashicons-media-document */
    color: #7c3aed; /* Purple for ZIP */
}

.file-type-icon.file-type-mp3::before,
.file-type-icon.file-type-wav::before,
.file-type-icon.file-type-ogg::before {
    content: "\f127"; /* dashicons-format-audio */
    color: #059669; /* Green for audio */
}

.file-type-icon.file-type-mp4::before,
.file-type-icon.file-type-avi::before,
.file-type-icon.file-type-mov::before {
    content: "\f126"; /* dashicons-format-video */
    color: #dc2626; /* Red for video */
}

/* Action Bar Styles */
.action-bar {
    background: white;
    border-bottom: 1px solid #e5e7eb;
    padding: 1rem 0;
    margin: 0 1rem 1rem 1rem;
    border-radius: 3px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: var(--wp-admin-bar-height, 32px); /* Account for WordPress admin bar height */
    z-index: 100;
}

.action-bar-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    max-width: 100%;
    margin: 0 auto;
    padding: 0 1rem;
}

.action-bar-content > *:first-child {
    margin-right: auto;
}

.action-bar-content > *:last-child {
    margin-left: auto;
}

/* Filter Tabs */
.filter-tabs {
    display: flex;
    gap: 0.25rem;
    background: #f3f4f6;
    border-radius: 8px;
    padding: 0.25rem;
}

.filter-tab {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: none;
    background: transparent;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-tab:hover {
    color: #374151;
    background: rgba(255, 255, 255, 0.5);
}

.filter-tab.active {
    background: white;
    color: #3b82f6;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filter-tab svg {
    flex-shrink: 0;
}

/* Search Container */
.search-container {
    flex: 1;
    max-width: none;
    margin: 0 1rem;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
    background: white;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-clear {
    position: absolute;
    right: 0.5rem;
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: color 0.2s ease, background 0.2s ease;
}

.search-clear:hover {
    color: #6b7280;
    background: #f3f4f6;
}

/* Bulk Actions */
.bulk-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-left: auto;
}

.bulk-select-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s ease;
}

.bulk-select-btn:hover {
    border-color: #9ca3af;
    background: #f9fafb;
}

.bulk-select-btn.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.bulk-actions-panel {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease;
    transform: translateY(-10px);
}

.bulk-actions-panel.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.bulk-count {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

.bulk-action-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.bulk-delete {
    background: #ef4444;
    color: white;
}

.bulk-delete:hover:not(:disabled) {
    background: #dc2626;
}

.bulk-delete:disabled {
    background: #d1d5db;
    color: #9ca3af;
    cursor: not-allowed;
}

.bulk-cancel {
    background: white;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

.bulk-cancel:hover {
    background: #f9fafb;
    color: #374151;
}

/* Bulk Select Mode Styles */
.bulk-mode .gallery-item {
    cursor: pointer;
    position: relative;
}

.bulk-mode .gallery-item:hover {
    transform: none;
}

.bulk-mode .gallery-item:hover .gallery-image {
    filter: brightness(0.9);
}

.bulk-mode .image-overlay {
    display: none !important;
}

.bulk-mode .gallery-item::before {
    content: '';
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    width: 20px;
    height: 20px;
    border: 2px solid white;
    border-radius: 4px;
    background: rgba(0, 0, 0, 0.3);
    z-index: 10;
    transition: all 0.2s ease;
    pointer-events: none;
}

.bulk-mode .gallery-item:hover::before {
    background: rgba(0, 0, 0, 0.5);
}

.bulk-mode .gallery-item.selected::before {
    background: #3b82f6;
    border-color: #3b82f6;
}

.bulk-mode .gallery-item.selected::after {
    content: '✓';
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    font-weight: bold;
    z-index: 11;
    pointer-events: none;
}

.bulk-mode .gallery-item.selected {
    box-shadow: 0 0 0 2px #3b82f6;
}

/* Responsive Design */
@media (max-width: 768px) {
    .action-bar-content {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }
    
    .filter-tabs {
        justify-content: center;
    }
    
    .search-container {
        max-width: none;
        margin: 0;
    }
    
    .bulk-actions {
        justify-content: center;
    }
    
    .bulk-actions-panel {
        flex-wrap: wrap;
        justify-content: center;
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
    initializeLayoutContainment();
    initializeActionBar();
    initializeAdminBarHeight();
    initializeMasonry();
    
    function initializeDragDrop() {
        const dragDropTarget = document.getElementById('drag-drop-target');
        const dragDropOverlay = document.getElementById('drag-drop-overlay');
        const fileInput = document.getElementById('media-upload');
        let dragCounter = 0;
        
        // Click to browse
        dragDropTarget.addEventListener('click', () => {
            fileInput.click();
        });
        
        // File input change
        fileInput.addEventListener('change', handleFileUpload);
        
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            document.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Handle drag enter - only show overlay once
        document.addEventListener('dragenter', (e) => {
            dragCounter++;
            if (dragCounter === 1) {
                dragDropOverlay.classList.add('active');
                document.getElementById('file-count').textContent = '0';
            }
        });
        
        // Handle drag over - keep overlay visible
        document.addEventListener('dragover', (e) => {
            // Keep overlay visible, don't toggle
        });
        
        // Handle drag leave - only hide overlay when completely leaving
        document.addEventListener('dragleave', (e) => {
            dragCounter--;
            if (dragCounter === 0) {
                dragDropOverlay.classList.remove('active');
            }
        });
        
        // Handle drop - hide overlay and process files
        document.addEventListener('drop', (e) => {
            dragCounter = 0;
            dragDropOverlay.classList.remove('active');
            handleDrop(e);
        });
        
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
            
            // WordPress supported file types (all types)
            const validFileTypes = [
                // Images
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 
                'image/svg+xml', 'image/bmp', 'image/tiff', 'image/tif', 'image/ico', 'image/x-icon',
                // Documents
                'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain', 'text/csv', 'application/rtf',
                // Archives
                'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
                // Audio
                'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4', 'audio/aac',
                // Video
                'video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/webm', 'video/ogg'
            ];
            
            Array.from(files).forEach(file => {
                // Check MIME type, file extension, or if it starts with common prefixes
                const isValidType = validFileTypes.includes(file.type.toLowerCase()) || 
                                  file.type.startsWith('image/') ||
                                  file.type.startsWith('application/') ||
                                  file.type.startsWith('text/') ||
                                  file.type.startsWith('audio/') ||
                                  file.type.startsWith('video/') ||
                                  /\.(jpg|jpeg|png|gif|webp|avif|svg|bmp|tiff|tif|ico|pdf|doc|docx|xls|xlsx|ppt|pptx|txt|csv|rtf|zip|rar|7z|mp3|wav|ogg|mp4|avi|mov|wmv)$/i.test(file.name);
                
                if (isValidType) {
                    formData.append('files[]', file);
                }
            });
            
            if (formData.getAll('files[]').length === 0) {
                alert('Please select valid files (images, documents, PDFs, archives, audio, video).');
                return;
            }
            
            // Upload files
            uploadFiles(formData);
        }
        
        function uploadFiles(formData) {
            const dragOverlay = document.getElementById('drag-drop-overlay');
            const progressOverlay = document.getElementById('upload-progress-overlay');
            const progressFill = document.getElementById('upload-progress-fill');
            const uploadStatus = document.getElementById('upload-status');
            const filesList = document.getElementById('upload-files-list');
            
            // Hide drag overlay, show progress overlay
            dragOverlay.classList.remove('active');
            progressOverlay.classList.add('active');
            
            // Get file list for progress tracking
            const files = Array.from(formData.getAll('files[]'));
            let uploadedCount = 0;
            let totalFiles = files.length;
            
            // Initialize file list
            filesList.innerHTML = '';
            files.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'upload-file-item';
                fileItem.innerHTML = `
                    <div class="upload-file-name">${file.name}</div>
                    <div class="upload-file-status pending">Pending</div>
                `;
                filesList.appendChild(fileItem);
            });
            
            // Update status
            uploadStatus.textContent = `Uploading 0 of ${totalFiles} files...`;
            
            // Create XMLHttpRequest for progress tracking
            const xhr = new XMLHttpRequest();
            
            // Track upload progress
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressFill.style.width = percentComplete + '%';
                    
                    // Update status during upload
                    if (percentComplete < 100) {
                        uploadStatus.textContent = `Uploading files... ${Math.round(percentComplete)}%`;
                    } else {
                        uploadStatus.textContent = `Processing files on server...`;
                        progressFill.classList.add('processing');
                    }
                }
            });
            
            // Handle response
            xhr.addEventListener('load', () => {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            // Update all files as successful
                            const fileItems = filesList.querySelectorAll('.upload-file-item');
                            fileItems.forEach(item => {
                                const status = item.querySelector('.upload-file-status');
                                status.textContent = 'Success';
                                status.className = 'upload-file-status success';
                            });
                            
                            uploadStatus.textContent = `Successfully uploaded ${totalFiles} files!`;
                            progressFill.style.width = '100%';
                            progressFill.classList.remove('processing');
                            
                            // Reload after a short delay
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            throw new Error(data.data || 'Upload failed');
                        }
                    } catch (error) {
                        console.error('Upload error:', error);
                        uploadStatus.textContent = 'Upload failed: ' + error.message;
                        progressFill.classList.remove('processing');
                        
                        // Mark all files as failed
                        const fileItems = filesList.querySelectorAll('.upload-file-item');
                        fileItems.forEach(item => {
                            const status = item.querySelector('.upload-file-status');
                            status.textContent = 'Failed';
                            status.className = 'upload-file-status error';
                        });
                    }
                } else {
                    uploadStatus.textContent = 'Upload failed: Server error';
                    progressFill.classList.remove('processing');
                    
                    // Mark all files as failed
                    const fileItems = filesList.querySelectorAll('.upload-file-item');
                    fileItems.forEach(item => {
                        const status = item.querySelector('.upload-file-status');
                        status.textContent = 'Failed';
                        status.className = 'upload-file-status error';
                    });
                }
            });
            
            // Handle errors
            xhr.addEventListener('error', () => {
                uploadStatus.textContent = 'Upload failed: Network error';
                progressFill.classList.remove('processing');
                
                // Mark all files as failed
                const fileItems = filesList.querySelectorAll('.upload-file-item');
                fileItems.forEach(item => {
                    const status = item.querySelector('.upload-file-status');
                    status.textContent = 'Failed';
                    status.className = 'upload-file-status error';
                });
            });
            
            // Set up cancel functionality
            const cancelBtn = document.getElementById('upload-cancel-btn');
            cancelBtn.addEventListener('click', () => {
                xhr.abort();
                progressOverlay.classList.remove('active');
            });
            
            // Start upload
                xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>?action=tomatillo_upload_files');
            xhr.send(formData);
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
    
    // No need for loadAllImages() - we read directly from DOM data attributes!
    
    function initializeMasonry() {
        const grid = document.getElementById('masonry-grid');
        if (!grid) return;
        
        let resizeTimeout;
        
        function layoutMasonry() {
            const items = Array.from(grid.children);
            if (items.length === 0) return;
            
            // Get container width
            const containerWidth = grid.offsetWidth;
            const gap = 8; // 0.5rem
            
            // Calculate number of columns based on screen size
            let columns = 3;
            if (containerWidth < 768) {
                columns = 2;
            } else if (containerWidth < 1200) {
                columns = 3;
            } else {
                columns = 3;
            }
            
            // Calculate column width
            const columnWidth = (containerWidth - (gap * (columns - 1))) / columns;
            
            // Initialize column heights
            const columnHeights = new Array(columns).fill(0);
            
            // Position each item
            items.forEach((item, index) => {
                // Reset any previous positioning
                item.style.position = 'absolute';
                item.style.left = '0px';
                item.style.top = '0px';
                
                // Set max width to column width, but let height adjust naturally
                item.style.maxWidth = columnWidth + 'px';
                item.style.width = 'auto';
                
                // Find the shortest column
                const shortestColumnIndex = columnHeights.indexOf(Math.min(...columnHeights));
                
                // Position the item
                const left = shortestColumnIndex * (columnWidth + gap);
                const top = columnHeights[shortestColumnIndex];
                
                item.style.left = left + 'px';
                item.style.top = top + 'px';
                
                // Update column height
                columnHeights[shortestColumnIndex] += item.offsetHeight + gap;
            });
            
            // Set container height
            grid.style.height = Math.max(...columnHeights) + 'px';
        }
        
        // Wait for images to load before layout
        function waitForImagesAndLayout() {
            const images = Array.from(grid.querySelectorAll('img'));
            const loadedImages = images.filter(img => img.complete);
            
            if (loadedImages.length === images.length) {
                layoutMasonry();
            } else {
                // Wait for remaining images
                images.forEach(img => {
                    if (!img.complete) {
                        img.onload = () => {
                            if (Array.from(grid.querySelectorAll('img')).every(i => i.complete)) {
                                setTimeout(layoutMasonry, 50);
                            }
                        };
                    }
                });
            }
        }
        
        // Initial layout
        setTimeout(waitForImagesAndLayout, 100);
        
        // Layout on resize (with debounce)
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                waitForImagesAndLayout();
            }, 150);
        });
        
        // Layout when new items are added
        const observer = new MutationObserver(() => {
            setTimeout(waitForImagesAndLayout, 100);
        });
        observer.observe(grid, { childList: true });
    }
    
    function initializeImageHandlers() {
        document.addEventListener('click', function(e) {
            const galleryItem = e.target.closest('.gallery-item, .file-item');
            if (!galleryItem) return;
            
            // Check if we're in bulk mode - if so, don't handle normal clicks
            if (document.body.classList.contains('bulk-mode')) {
                return;
            }
            
            const imageId = galleryItem.dataset.id;
            
            // Click on image - open modal
            openModal(imageId);
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
        
        // Image - use optimized image for display
        document.getElementById('modal-image').src = data.best_image_url || data.url;
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
    
    function copyFileUrl(fileId, fileUrl) {
        // Copy URL to clipboard
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(fileUrl).then(function() {
                showToast('File URL copied to clipboard!', 'success');
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                fallbackCopyTextToClipboard(fileUrl);
            });
        } else {
            fallbackCopyTextToClipboard(fileUrl);
        }
    }
    
    // Expose copyFileUrl to global scope
    window.copyFileUrl = copyFileUrl;
    
    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showToast('File URL copied to clipboard!', 'success');
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
            showToast('Failed to copy URL', 'error');
        }
        
        document.body.removeChild(textArea);
    }
    
    function downloadFile(fileId) {
        // Create a temporary link to trigger download
        const link = document.createElement('a');
        link.href = `<?php echo admin_url('admin-ajax.php'); ?>?action=tomatillo_download_file&file_id=${fileId}&nonce=<?php echo wp_create_nonce('tomatillo_download_file'); ?>`;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
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
    // Layout containment function
    function initializeLayoutContainment() {
        const galleryContainer = document.querySelector('.gallery-container');
        if (!galleryContainer) return;
        
        function updateContainerWidth() {
            const viewportWidth = window.innerWidth;
            const availableWidth = viewportWidth - 40; // Account for WordPress admin sidebar and margins
            
            // Set max-width to prevent bleeding
            galleryContainer.style.maxWidth = Math.min(availableWidth, viewportWidth - 40) + 'px';
            galleryContainer.style.marginLeft = 'auto';
            galleryContainer.style.marginRight = 'auto';
        }
        
        // Update on load and resize
        updateContainerWidth();
        window.addEventListener('resize', updateContainerWidth);
    }
    
    function initializeActionBar() {
        // Filter tabs functionality
        const filterTabs = document.querySelectorAll('.filter-tab');
        filterTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                filterTabs.forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                filterImages(filter);
            });
        });
        
        // Search functionality
        const searchInput = document.getElementById('gallery-search');
        const searchClear = document.getElementById('search-clear');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Show/hide clear button
            searchClear.style.display = query ? 'block' : 'none';
            
            // Debounce search
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
        
        searchClear.addEventListener('click', function() {
            searchInput.value = '';
            this.style.display = 'none';
            performSearch('');
        });
        
        // Bulk select functionality
        const bulkSelectBtn = document.getElementById('bulk-select-btn');
        const bulkActionsPanel = document.getElementById('bulk-actions-panel');
        const bulkCount = document.getElementById('bulk-count');
        const bulkDeleteBtn = document.getElementById('bulk-delete');
        const bulkCancelBtn = document.getElementById('bulk-cancel');
        
        let bulkMode = false;
        let selectedItems = new Set();
        
        bulkSelectBtn.addEventListener('click', function() {
            bulkMode = !bulkMode;
            
            if (bulkMode) {
                this.classList.add('active');
                this.textContent = 'Exit Bulk Mode';
                bulkActionsPanel.classList.add('active');
                document.body.classList.add('bulk-mode');
                
                // Disable file action buttons
                document.querySelectorAll('.file-actions .action-btn').forEach(btn => {
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                    btn.style.cursor = 'not-allowed';
                });
            } else {
                exitBulkMode();
            }
        });
        
        bulkCancelBtn.addEventListener('click', exitBulkMode);
        
        bulkDeleteBtn.addEventListener('click', function() {
            if (selectedItems.size === 0) return;
            
            if (confirm(`Are you sure you want to delete ${selectedItems.size} file(s)? This action cannot be undone.`)) {
                deleteSelectedImages();
            }
        });
        
        function exitBulkMode() {
            bulkMode = false;
            bulkSelectBtn.classList.remove('active');
            bulkSelectBtn.textContent = 'Bulk Select Mode';
            bulkActionsPanel.classList.remove('active');
            document.body.classList.remove('bulk-mode');
            
            // Re-enable file action buttons
            document.querySelectorAll('.file-actions .action-btn').forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
            });
            
            // Clear all visual selections - more robust approach
            selectedItems.clear();
            
            // Clear gallery items
            document.querySelectorAll('.gallery-item.selected').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Clear file items - ensure all are cleared
            document.querySelectorAll('.file-item.selected').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Force update the bulk count
            updateBulkCount();
            
            // Additional safety: remove any remaining selected classes
            setTimeout(() => {
                document.querySelectorAll('.gallery-item.selected, .file-item.selected').forEach(item => {
                    item.classList.remove('selected');
                });
            }, 100);
        }
        
        // Add click handlers to gallery items for bulk selection
        // This handler runs first and intercepts clicks when in bulk mode
        document.addEventListener('click', function(e) {
            if (!bulkMode) return;
            
            const galleryItem = e.target.closest('.gallery-item, .file-item');
            if (!galleryItem) return;
            
            // In bulk mode, prevent modal opening and handle selection
            e.preventDefault();
            e.stopPropagation();
            
            const itemId = galleryItem.dataset.id;
            
            if (selectedItems.has(itemId)) {
                selectedItems.delete(itemId);
                galleryItem.classList.remove('selected');
            } else {
                selectedItems.add(itemId);
                galleryItem.classList.add('selected');
            }
            
            updateBulkCount();
        }, true); // Use capture phase to run before other handlers
        
        function updateBulkCount() {
            const count = selectedItems.size;
            bulkCount.textContent = `${count} selected`;
            bulkDeleteBtn.disabled = count === 0;
        }
        
        function filterImages(filter) {
            const imagesGrid = document.getElementById('masonry-grid');
            const filesGrid = document.getElementById('files-grid');
            
            if (filter === 'images') {
                imagesGrid.style.display = 'block';
                filesGrid.style.display = 'none';
            } else if (filter === 'files') {
                imagesGrid.style.display = 'none';
                filesGrid.style.display = 'grid';
            }
        }
        
        function performSearch(query) {
            if (!query.trim()) {
                // Show all items - FAST!
                document.querySelectorAll('.gallery-item, .file-item').forEach(item => {
                    item.style.display = 'block';
                });
                return;
            }
            
            // Pure JS search - no arrays, no loops, just direct DOM manipulation
            const searchTerms = query.toLowerCase().split(' ').filter(term => term.length > 0);
            
            // Search through both images and files
            document.querySelectorAll('.gallery-item, .file-item').forEach(item => {
                // Build searchable text directly from data attributes - BLAZING FAST!
                const searchableText = [
                    item.dataset.title || '',
                    item.dataset.alt || '',
                    item.dataset.id || '',
                    item.dataset.caption || '',
                    item.dataset.description || '',
                    item.dataset.filename || '',
                    item.querySelector('.image-meta, .file-meta')?.textContent || ''
                ].join(' ').toLowerCase();
                
                // Check if all search terms match
                const matches = searchTerms.every(term => searchableText.includes(term));
                item.style.display = matches ? 'block' : 'none';
            });
        }
        
        function deleteSelectedImages() {
            const imageIds = Array.from(selectedItems);
            
            // Show loading state
            bulkDeleteBtn.disabled = true;
            bulkDeleteBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Deleting...';
            
            // Send AJAX request to delete images
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'tomatillo_delete_images',
                    image_ids: JSON.stringify(imageIds),
                    nonce: '<?php echo wp_create_nonce('tomatillo_delete_images'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove deleted items from DOM
                    imageIds.forEach(id => {
                        const item = document.querySelector(`[data-id="${id}"]`);
                        if (item) {
                            item.remove();
                        }
                    });
                    
                    // Show success message
                    showToast('Successfully deleted ' + imageIds.length + ' image(s)', 'success');
                    
                    // Exit bulk mode
                    exitBulkMode();
                } else {
                    showToast(data.data || 'Failed to delete images', 'error');
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                showToast('Network error occurred while deleting images', 'error');
            })
            .finally(() => {
                // Reset button state
                bulkDeleteBtn.disabled = false;
                bulkDeleteBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3,6 5,6 21,6"></polyline><path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path></svg> Delete Selected';
            });
        }
    }
    
    function initializeAdminBarHeight() {
        // Set CSS custom property for WordPress admin bar height
        function updateAdminBarHeight() {
            const adminBar = document.getElementById('wpadminbar');
            const adminBarHeight = adminBar ? adminBar.offsetHeight : 0;
            document.documentElement.style.setProperty('--wp-admin-bar-height', adminBarHeight + 'px');
        }
        
        // Update on load and resize
        updateAdminBarHeight();
        window.addEventListener('resize', updateAdminBarHeight);
        
        // Also update when admin bar might change (e.g., user login/logout)
        const observer = new MutationObserver(updateAdminBarHeight);
        observer.observe(document.body, { childList: true, subtree: true });
    }
    
    window.copyImageUrl = copyImageUrl;
    window.downloadImage = downloadImage;
    window.deleteImage = deleteImage;
    window.optimizeImageModal = optimizeImageModal;
});
</script>
