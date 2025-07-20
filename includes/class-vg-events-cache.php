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
        wp_cache_delete( $key, $this->group );
    }
}

function vg_events_cache() {
    static $instance = null;
    if ( null === $instance ) {
        $instance = new VG_Events_Cache();
    }
    return $instance;
}
