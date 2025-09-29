# Tomatillo Media Studio

A comprehensive WordPress media solution featuring automatic AVIF/WebP optimization and a beautiful, modern media library interface.

## 🚀 Features

### 🖼️ Image Optimization
- **Automatic AVIF/WebP Conversion**: Converts uploaded images to modern formats for better performance
- **Quality Control**: Adjustable quality settings for both AVIF and WebP formats
- **Batch Processing**: Process existing images in your media library
- **Space Savings**: Significant reduction in file sizes and bandwidth usage
- **Fallback Support**: Graceful degradation for browsers that don't support modern formats

### 🎨 Modern Media Library
- **High-Resolution Thumbnails**: No more fuzzy 150px thumbnails - see your images in full quality
- **Intelligent Organization**: Smart categorization by file type (Images, Documents, Videos)
- **Bulk Operations**: Select multiple files for batch actions
- **Advanced Search**: Real-time search across titles, alt text, and metadata
- **One-Click Actions**: Copy URLs, download files, delete items with single clicks
- **Performance Indicators**: See file sizes, optimization status, and format information

### ⚙️ Flexible Configuration
- **Modular Design**: Enable/disable entire modules based on your needs
- **Granular Settings**: Fine-tune optimization and interface preferences
- **Debug Mode**: Detailed logging for troubleshooting
- **Performance Monitoring**: Track optimization statistics and savings

## 📋 Requirements

- **WordPress**: 5.8 or higher
- **PHP**: 7.4 or higher
- **Extensions**: GD with AVIF support OR Imagick
- **Browser Support**: Modern browsers for AVIF/WebP display

## 🛠️ Installation

1. Upload the plugin files to `/wp-content/plugins/tomatillo-media-studio/`
2. Activate the plugin through the WordPress admin
3. Navigate to **Media Studio** in your admin menu
4. Configure your settings and enable desired modules

## ⚙️ Configuration

### Module Control
- **Image Optimization**: Enable/disable automatic AVIF/WebP conversion
- **Modern Media Library**: Enable/disable enhanced media library interface

### Optimization Settings
- **Auto-Convert**: Automatically optimize new uploads
- **AVIF Quality**: Adjust quality (1-100) for AVIF conversion
- **WebP Quality**: Adjust quality (1-100) for WebP conversion
- **Batch Size**: Number of images to process in each batch
- **Preserve Originals**: Keep original files after optimization

### Media Library Settings
- **Thumbnail Size**: Choose thumbnail size for grid display
- **Bulk Operations**: Enable multi-select functionality
- **Advanced Search**: Enable enhanced search and filtering
- **File Sizes**: Display file size information
- **Optimization Status**: Show optimization indicators

## 🎯 Usage

### Image Optimization
1. Enable the optimization module in settings
2. Configure quality settings for AVIF and WebP
3. Upload images - they'll be automatically optimized
4. Use the Optimization dashboard to process existing images

### Modern Media Library
1. Enable the media library module in settings
2. Navigate to **Media Studio > Media Library**
3. Browse your media with high-resolution thumbnails
4. Use bulk operations for multiple files
5. Search and filter your media collection

## 🔧 Development

### File Structure
```
tomatillo-media-studio/
├── tomatillo-media-studio.php          # Main plugin file
├── includes/
│   ├── class-core.php                  # Core functionality
│   ├── class-settings.php              # Settings management
│   ├── optimization/                   # Image optimization module
│   ├── media-library/                  # Media library module
│   ├── admin/                          # Admin interface
│   └── assets/                         # Asset management
├── assets/
│   ├── css/                           # Stylesheets
│   └── js/                            # JavaScript files
├── templates/                          # PHP templates
└── languages/                          # Translation files
```

### Hooks and Filters

#### Actions
- `tomatillo_media_optimized`: Fired when an image is optimized
- `tomatillo_media_batch_complete`: Fired when batch processing completes
- `tomatillo_media_library_loaded`: Fired when media library loads

#### Filters
- `tomatillo_optimization_quality`: Modify optimization quality settings
- `tomatillo_media_library_thumbnail_size`: Modify thumbnail size
- `tomatillo_optimization_formats`: Modify supported formats

## 📊 Performance

### Optimization Benefits
- **AVIF**: Up to 50% smaller than JPEG
- **WebP**: Up to 25% smaller than JPEG
- **Bandwidth Savings**: Significant reduction in data transfer
- **Loading Speed**: Faster page load times

### System Impact
- **Minimal Overhead**: Only loads when needed
- **Efficient Processing**: Background batch processing
- **Smart Caching**: Cached thumbnails and metadata
- **Memory Optimized**: Efficient memory usage during processing

## 🐛 Troubleshooting

### Common Issues

**Images not converting to AVIF/WebP**
- Check PHP extensions (GD with AVIF support or Imagick)
- Verify file permissions
- Check debug mode for detailed logs

**Media library not loading**
- Ensure media library module is enabled
- Check browser console for JavaScript errors
- Verify WordPress version compatibility

**Performance issues**
- Reduce batch size in settings
- Enable lazy loading
- Check server resources

### Debug Mode
Enable debug mode in settings to get detailed logging information in your error logs.

## 🤝 Contributing

This plugin is developed for internal use. For questions or issues, please contact the development team.

## 📄 License

GPL v2 or later - see LICENSE file for details.

## 👨‍💻 Credits

**Developed by**: Chris Liu-Beers, Tomatillo Design  
**Version**: 1.0.0  
**Last Updated**: 2025

---

**Tomatillo Media Studio** - Revolutionizing WordPress media management with modern optimization and beautiful interfaces.
