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
