# Tomatillo Media Studio

A comprehensive WordPress media solution featuring automatic AVIF/WebP optimization, a beautiful modern media library interface, and seamless Block Editor integration.

## 🚀 Features

### 🖼️ Next-Generation Image Optimization
- **Automatic AVIF/WebP Conversion**: All uploaded images are automatically converted to modern formats (AVIF + WebP fallback)
- **Smart Quality Control**: Adjustable quality settings (default: 80 for AVIF, 85 for WebP)
- **Batch Processing**: Optimize your existing media library with one click
- **Bandwidth Savings**: Reduce file sizes by up to 50% compared to JPEG
- **Seamless Frontend Delivery**: Automatically serves optimized formats to supporting browsers
- **Original Preservation**: Original files are preserved for compatibility

### 🎨 Enhanced Media Library
- **Beautiful Masonry Grid**: High-resolution media display with intelligent column layout
- **Smart Organization**: Filter by Images, Videos, Audio, or Documents
- **Bulk Operations**: Select multiple files for batch delete or ZIP download
- **Real-Time Search**: Search across filenames, titles, alt text, and metadata
- **Drag & Drop Upload**: Upload files with visual feedback and progress tracking
- **Responsive Design**: Adaptive layout from 1-8 columns based on screen size
- **Quick Actions**: Copy URLs, view details, download files with single clicks

### 🎛️ Block Editor Integration
- **Custom Media Picker**: Beautiful modal replaces the default WordPress media library in Gutenberg
- **React Integration**: Native Block Editor support with custom media upload component
- **Infinite Scroll**: Smooth loading of large media libraries
- **ACF Compatible**: Works seamlessly with Advanced Custom Fields image fields

### ⚙️ Flexible Configuration
- **Modular Architecture**: Enable/disable optimization and media library modules independently
- **Fine-Tuned Settings**: Control quality, batch sizes, column counts, and more
- **Debug Mode**: Comprehensive logging system with 1000-entry rotating buffer
- **Performance Stats**: Real-time monitoring of optimization savings and bandwidth reduction

## 📋 Requirements

- **WordPress**: 6.4 or higher
- **PHP**: 8.1 or higher
- **PHP Extensions**: GD with AVIF support OR Imagick
- **Browser Support**: Modern browsers (Chrome 85+, Firefox 93+, Safari 16+)

## 🛠️ Installation

1. Upload the plugin files to `/wp-content/plugins/tomatillo-media-studio/`
2. Activate the plugin through the WordPress admin
3. Navigate to **Media Studio** in your admin menu
4. Configure your settings and enable desired modules

## ⚙️ Configuration

Navigate to **Media Studio > Settings** to configure the plugin.

### Module Control
- **Image Optimization**: Enable/disable the entire optimization module
- **Enhanced Gallery Interface**: Enable/disable the modern media library
- **Show 'View Files' Menu Link**: Add a quick "View Files" link to the admin menu

### Optimization Settings
- **Auto-Convert on Upload**: Automatically optimize images when uploaded (recommended)
- **AVIF Quality**: 1-100, default 80 (lower = smaller files, slight quality loss)
- **WebP Quality**: 1-100, default 85 (fallback for browsers without AVIF support)
- **Preserve Original Files**: Keep the original uploaded file (recommended for compatibility)

### Media Library Settings  
- **Column Count**: Adjust the number of columns in the grid view (1-8, default 4)
- **Infinite Scroll**: Smooth loading of large libraries (50 items per batch)
- **Bulk Select Mode**: Enable multi-select with delete and ZIP download options

### Debug & Monitoring
- **Debug Mode**: Enable detailed logging (stores last 1000 entries)
- **Performance Stats**: View total library size, optimization savings, and bandwidth reduction

## 🎯 Usage

### Automatic Image Optimization
1. Go to **Media Studio > Settings**
2. Enable "Image Optimization" module
3. Enable "Auto-Convert on Upload"
4. Upload any image - it's automatically converted to AVIF + WebP!
5. Your frontend will automatically serve the optimized format

### Batch Process Existing Images
1. Go to **Media Studio > Tools > Optimization**
2. Click "Start Optimization" to process your entire media library
3. Monitor progress in real-time
4. View statistics: images optimized, space saved, bandwidth reduced

### Using the Enhanced Media Library
1. Go to **Media Studio** (main menu)
2. Browse images in beautiful high-resolution grid
3. Toggle "Bulk Select Mode" to select multiple files
4. Download selected files as ZIP or delete them
5. Use the column slider to adjust grid density
6. Search by filename, title, or metadata

## 🔧 Developer Information

### File Structure
```
tomatillo-media-studio/
├── tomatillo-media-studio.php          # Main plugin file
├── includes/
│   ├── class-core.php                  # Core plugin functionality
│   ├── class-settings.php              # Settings management
│   ├── class-logger.php                # Debug logging system
│   ├── admin/
│   │   └── class-admin.php            # Admin interface & menus
│   ├── assets/
│   │   └── class-assets.php           # Asset management
│   └── optimization/
│       ├── class-optimizer.php         # Image optimization engine
│       └── class-frontend-swap.php     # Frontend format delivery
├── assets/
│   ├── css/
│   │   └── admin.css                  # Admin styles
│   └── js/
│       ├── admin.js                    # Admin functionality
│       ├── background-loader.js        # Media preloading
│       ├── custom-media-frame-clean.js # Custom media picker
│       ├── tomatillo-react-media-upload.js # Block Editor integration
│       └── acf-gallery-handler.js      # ACF compatibility
├── templates/
│   ├── media-library.php              # Main media library view
│   ├── settings-page.php              # Settings interface
│   ├── tools-page.php                 # Tools & logs interface
│   └── optimization-dashboard.php      # Batch optimization UI
└── utilities/                          # Development utilities
```

### Technical Details

#### Image Optimization Pipeline
1. Image uploaded → WordPress processes it
2. Plugin hooks into `wp_generate_attachment_metadata`
3. Original is converted to AVIF (lossy, highest compression)
4. Original is converted to WebP (lossy, fallback format)
5. Metadata stored in database for quick lookups
6. Frontend swap intercepts image URLs and serves optimized versions

#### Frontend Delivery
- Uses `<picture>` element with `<source>` tags for format selection
- Browser automatically selects best supported format (AVIF > WebP > JPG)
- No JavaScript required for format detection
- Falls back gracefully on older browsers

#### Performance Optimizations
- **Background Preloading**: Media library data loads in background on all admin pages
- **Infinite Scroll**: Renders 50 items at a time, loads more on scroll
- **Pre-calculated Layouts**: Masonry positions calculated server-side to prevent layout shift
- **Debounced Search**: Search queries are debounced to reduce server load

## 📊 Performance Impact

### File Size Reductions (Typical)
- **AVIF**: 50-70% smaller than JPEG
- **WebP**: 25-35% smaller than JPEG
- **Combined**: Saves significant bandwidth and storage

### System Requirements
- **Minimum**: 128MB PHP memory, GD library
- **Recommended**: 256MB+ PHP memory, Imagick for better quality
- **Storage**: Additional space for optimized versions (~30% of original library size)

## 🐛 Troubleshooting

### Images Not Optimizing
1. **Check PHP Extensions**: Run phpinfo() and look for GD or Imagick with AVIF support
2. **Check File Permissions**: Ensure WordPress can write to `/wp-content/uploads/`
3. **Enable Debug Mode**: Go to Tools > Logs & Debug, check for error messages
4. **Test Single Image**: Upload one small image and check logs

### Media Library Not Loading
1. **Check Browser Console**: Look for JavaScript errors
2. **Disable Other Plugins**: Test for conflicts
3. **Check WordPress Version**: Requires 6.4+
4. **Clear Browser Cache**: Hard refresh (Cmd/Ctrl + Shift + R)

### Performance Issues
1. **Reduce Batch Size**: Lower the number of images processed at once
2. **Increase PHP Memory**: Add `define('WP_MEMORY_LIMIT', '256M');` to wp-config.php
3. **Use Better Hosting**: Ensure adequate server resources
4. **Optimize Database**: Large media libraries benefit from database optimization

### Debug Mode
Navigate to **Media Studio > Tools > Logs & Debug** to:
- Enable/disable logging
- View the last 1000 log entries
- Copy logs for support requests
- Monitor optimization processes in real-time

## 📄 License

GPL v2 or later. See [LICENSE](LICENSE) for details.

## 👨‍💻 Credits

**Developed by**: Chris Liu-Beers  
**Company**: [Tomatillo Design](https://tomatillodesign.com)  
**Version**: 1.0.3  
**Last Updated**: January 2025

---

**Tomatillo Media Studio** - Modern WordPress media management with next-generation optimization.
