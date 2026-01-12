=== Tomatillo Media Studio ===
Contributors: tomatillodesign, chrislb
Tags: media, optimization, avif, webp, image compression
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive WordPress media solution featuring automatic AVIF/WebP optimization, a beautiful modern media library interface, and seamless Block Editor integration.

== Description ==

Tomatillo Media Studio revolutionizes WordPress media management by combining next-generation image optimization with a stunning, user-friendly interface. Say goodbye to bloated image files and hello to lightning-fast page loads!

= 🚀 Key Features =

**Next-Generation Image Optimization**
* Automatic AVIF & WebP conversion for all uploaded images
* Up to 70% smaller file sizes compared to JPEG
* Smart quality control with adjustable settings
* Batch processing for existing media libraries
* Seamless frontend delivery to all browsers
* Original files preserved for compatibility

**Enhanced Media Library**
* Beautiful masonry grid with high-resolution thumbnails
* Smart organization by file type (Images, Videos, Audio, Documents)
* Bulk operations: delete multiple files or download as ZIP
* Real-time search across filenames, titles, and metadata
* Drag & drop upload with visual progress tracking
* Responsive 1-8 column layout

**Block Editor Integration**
* Custom media picker replaces default WordPress library
* Native Gutenberg support with React components
* Infinite scroll for large media libraries
* ACF (Advanced Custom Fields) compatible

**Developer-Friendly**
* Modular architecture: enable/disable modules independently
* Comprehensive debug logging (1000-entry rotating buffer)
* Real-time performance statistics
* Extensive hooks and filters for customization

= 💡 Perfect For =

* Photographers and visual artists
* E-commerce sites with many product images
* News and magazine websites
* Marketing agencies
* Anyone serious about website performance

= 📊 Performance Benefits =

* **AVIF**: 50-70% smaller than JPEG
* **WebP**: 25-35% smaller than JPEG
* **Faster Load Times**: Significantly reduced page weight
* **Better SEO**: Improved Core Web Vitals scores
* **Reduced Bandwidth**: Lower hosting costs

== Installation ==

1. Upload the `tomatillo-media-studio` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Media Studio** in your admin menu
4. Configure your settings and enable desired modules
5. Upload an image to see automatic optimization in action!

= Minimum Requirements =

* WordPress 6.4 or higher
* PHP 8.1 or higher
* PHP GD extension with AVIF support OR Imagick
* 128MB PHP memory limit (256MB+ recommended)

== Frequently Asked Questions ==

= Will this work with my existing images? =

Yes! Use the batch processing tool in **Media Studio > Tools > Optimization** to convert your entire existing media library.

= Do I need to install additional software? =

No external software is required. The plugin uses PHP's built-in GD or Imagick libraries. Most modern hosting environments support AVIF by default.

= Will old browsers still see images? =

Absolutely! The plugin automatically serves WebP to browsers that don't support AVIF, and falls back to the original JPEG/PNG for older browsers. No one is left out.

= Does this work with page builders? =

Yes! The plugin works seamlessly with Elementor, Beaver Builder, Divi, and other popular page builders. It also integrates beautifully with the Block Editor (Gutenberg).

= Can I adjust the image quality? =

Yes! Navigate to **Media Studio > Settings** and adjust the AVIF and WebP quality settings (1-100). Default is 80 for AVIF and 85 for WebP.

= Will this slow down my uploads? =

Initial upload processing takes a few extra seconds per image, but the time is well worth the bandwidth savings. For large batches, use the background batch processor.

= Is my original image deleted? =

By default, no. Original files are preserved. You can optionally disable this in settings if you're confident in the optimized versions.

= Does this work with ACF (Advanced Custom Fields)? =

Yes! The custom media picker works seamlessly with ACF image fields.

= How much storage space will I need? =

Optimized versions typically use 30% less space than originals. However, since we keep originals by default, you'll need about 130% of your current media library size.

== Screenshots ==

1. Beautiful masonry grid media library with high-resolution thumbnails
2. Batch optimization dashboard with real-time progress
3. Settings page with modular controls
4. Bulk select mode with ZIP download
5. Custom media picker in Block Editor
6. Debug logging interface

== Changelog ==

= 1.0.1 =
* Improved logging system with visual grouping
* Fixed admin menu spacing issue
* Updated requirements to WordPress 6.4+ and PHP 8.1+
* Removed unnecessary debug notices
* Enhanced Block Editor modal - images-only by default
* Fixed infinite scroll filtering bug
* Added professional gray color scheme to stats cards
* Improved bandwidth savings calculation accuracy
* Added utility folder for development files
* Updated readme documentation

= 1.0.0 =
* Initial release
* Automatic AVIF/WebP conversion
* Enhanced media library interface
* Block Editor integration
* Batch processing tools
* Debug logging system
* ACF compatibility

== Upgrade Notice ==

= 1.0.1 =
Minor improvements to logging, UI polish, and bug fixes. Safe to upgrade.

= 1.0.0 =
Initial release of Tomatillo Media Studio!

== Technical Details ==

= Image Optimization Pipeline =

1. Image uploaded → WordPress processes it
2. Plugin hooks into `wp_generate_attachment_metadata`
3. Original is converted to AVIF (lossy, highest compression)
4. Original is converted to WebP (lossy, fallback format)
5. Metadata stored in database for quick lookups
6. Frontend swap intercepts image URLs and serves optimized versions

= Frontend Delivery =

The plugin uses HTML5 `<picture>` elements with `<source>` tags for format selection. Browsers automatically select the best supported format (AVIF > WebP > Original). No JavaScript required!

= Performance Optimizations =

* Background preloading: Media library data loads in background on all admin pages
* Infinite scroll: Renders 50 items at a time, loads more on scroll
* Pre-calculated layouts: Masonry positions calculated server-side to prevent layout shift
* Debounced search: Search queries are debounced to reduce server load

== Support ==

For support, please visit [Tomatillo Design](https://tomatillodesign.com) or open an issue on [GitHub](https://github.com/tomatillodesign/tomatillo-design-media-studio).

== Privacy ==

This plugin does not collect, store, or transmit any user data. All optimization happens on your server. No external API calls are made.

