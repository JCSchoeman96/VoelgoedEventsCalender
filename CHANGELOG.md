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
