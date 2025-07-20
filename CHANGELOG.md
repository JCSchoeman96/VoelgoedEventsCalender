# Changelog

## 1.0.0
- Initial object-oriented plugin structure.
- Moved CSS and JS to dedicated files.
- Added nonce protection and vanilla JS.

## 1.1.0
- Added admin settings page for template ID and datepicker toggle.
- Switched AJAX to REST API with transient caching.
- Conditional asset loading and Flatpickr integration.
- Added Gutenberg block and deep-linking via URL params.

## 1.2.0
- Load assets only when shortcode or block is used.
- Added helper functions with filters for towns and months.
- Dynamic Flatpickr loading with native date input fallback.
- Settings page now includes post type selection and datepicker toggle.

## 1.3.0
- Switched to wp_cache_* for Redis-compatible caching.
- REST responses cached with object cache and cache_bust support.
- Added cache clearing on event save.
- Server-side render of first page for better page caching.
- Preload Flatpickr assets when enabled.
- Improved Elementor detection and added REST nonce security.

## 1.4.0
- Refactored JS into modular class with debounce and spinner.
- Auto-caching for towns/months with transient invalidation.
- Added dynamic CPT filtering and label exposure.
- Enhanced admin settings UI and grouped controls.
- Prepared plugin for Redis object caching and future metadata extensions.

## 1.4.1
- Fixed initial event sorting and future-date filtering.
- Improved Elementor loop rendering and added optional debug mode.

## 1.5.0
- Added vg_events_debug option and frontend debug output per loop item.
- Console logging now shows REST params and template render issues.
- Injects fallback styles and markup when Elementor templates fail.

## 1.6.0
- Replaced Elementor template rendering with custom `vg-events-loop.php`.
- Added fallback styles matching Elementor design.
- Debug mode now logs REST requests/responses and shows raw event meta.
- Removed Elementor dependency for improved performance.

## 1.6.1
- Inline critical loop CSS and add lazy-loaded thumbnails.
- Added transient caching for rendered loops and debug panel UI.
- Support `?vg_debug=1` to enable debug regardless of option.
- Output JSON-LD event data and improved accessibility markup.
- Enqueue new minified CSS file and responsive grid tweaks.

## 1.7.0
- Consolidated JSON-LD output into a single `@graph` block with additional
  fields like `endDate`, `description`, `image` and `url`.
- Added `vg_events_schema_event` filter to customize schema data.
- Cached loops now include the schema markup for better SEO.

## 1.8.1
- Added persistent WP_Query caching with object cache and cache headers.
- Included organizer, performer and offers in JSON-LD data.
- REST responses now expose cache hit status when debug mode is enabled.
- Bumped plugin version to 1.8.1.

## 4.1.0
- OPcache preloading for core plugin files.
- Switched caching to persistent Redis/object cache with `vg_events` group.
- Added cron job to prewarm cached event loops for six months ahead.
- Implemented fine-grained cache invalidation hooks.
- JSON-LD and HTML now pre-generated and served from cache.
- Deferred script loading and REST cache-control headers.
