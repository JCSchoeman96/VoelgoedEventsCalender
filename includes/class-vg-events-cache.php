<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VG_Events_Cache {
    /** @var string */
    protected $group = 'vg_events';

    /**
     * Store a value in cache.
     *
     * @param string $key Cache key.
     * @param mixed  $data Data to store.
     * @param int    $ttl  Time to live in seconds.
     */
    public function set( $key, $data, $ttl = 0 ) {
        wp_cache_set( $key, $data, $this->group, $ttl );
    }

    /**
     * Retrieve a cached value.
     *
     * @param string $key Cache key.
     * @return mixed
     */
    public function get( $key ) {
        return wp_cache_get( $key, $this->group );
    }

    /**
     * Invalidate cache entries for a prefix.
     *
     * @param string $name Cache name suffix.
     */
    public function invalidate( $name ) {
        $key = 'vg_events_cached_' . sanitize_key( $name );
        delete_transient( $key );
        delete_site_transient( $key );
        wp_cache_delete( $key, $this->group );
    }

    /**
     * Delete a specific cache key.
     *
     * @param string $key Cache key to delete.
     */
    public function delete( $key ) {
        wp_cache_delete( $key, $this->group );
        delete_transient( $key );
        delete_site_transient( $key );
    }

    /**
     * Invalidate cache entries by prefix.
     *
     * @param string $prefix Prefix for cache keys.
     */
    public function invalidate_prefix( $prefix ) {
        global $wpdb;
        $like = $wpdb->esc_like( 'vg_events_' . $prefix );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_' . $like . '%' ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_site_transient_' . $like . '%' ) );
        wp_cache_flush();
    }

    /**
     * Clear all plugin caches.
     */
    public function clear_all() {
        $this->invalidate_prefix( '' );
    }
}

function vg_events_cache() {
    static $instance = null;
    if ( null === $instance ) {
        $instance = new VG_Events_Cache();
    }
    return $instance;
}
