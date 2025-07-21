<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VG_Events_Cache {
    /** @var string */
    protected $group = 'vg_events';

    /** @var bool */
    protected $persistent;

    public function __construct() {
        $this->persistent = wp_using_ext_object_cache();
    }

    /**
     * Store a value in cache.
     *
     * @param string $key Cache key.
     * @param mixed  $data Data to store.
     * @param int    $ttl  Time to live in seconds.
     */
    public function set( $key, $data, $ttl = 0 ) {
        wp_cache_set( $key, $data, $this->group, $ttl );
        if ( ! $this->persistent ) {
            set_transient( $key, $data, $ttl );
        }
    }

    /**
     * Retrieve a cached value.
     *
     * @param string $key Cache key.
     * @return mixed
     */
    public function get( $key ) {
        $data = wp_cache_get( $key, $this->group );
        if ( false === $data && ! $this->persistent ) {
            $data = get_transient( $key );
        }
        return $data;
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

    /**
     * Retrieve cache statistics.
     *
     * @return array
     */
    public function stats() {
        global $wpdb;
        $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_vg_events_%' ) );
        $oldest = $wpdb->get_var( $wpdb->prepare( "SELECT MIN(option_value) FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_vg_events_%' ) );
        $oldest_ts = $oldest ? date_i18n( 'Y-m-d H:i:s', (int) $oldest ) : '';
        $cache = $GLOBALS['wp_object_cache'];
        $hits   = isset( $cache->cache_hits ) ? (int) $cache->cache_hits : 0;
        $misses = isset( $cache->cache_misses ) ? (int) $cache->cache_misses : 0;
        $ratio  = ( $hits + $misses ) ? round( $hits / ( $hits + $misses ) * 100, 2 ) : 0;

        return [
            'count'  => $count,
            'oldest' => $oldest_ts,
            'hit_ratio' => $ratio,
        ];
    }
}

function vg_events_cache() {
    static $instance = null;
    if ( null === $instance ) {
        $instance = new VG_Events_Cache();
    }
    return $instance;
}
