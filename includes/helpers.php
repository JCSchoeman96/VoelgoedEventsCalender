<?php
/**
 * Helper functions for Voelgoed Events Calendar.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Generate cache key for the vg_events cache group.
 *
 * @param string $type    Cache type.
 * @param array  $params  Optional parameters used to build the key.
 *
 * @return string
 */
function vg_events_get_cache_key( $type, $params = [] ) {
    return 'vg_events_' . $type . '_' . md5( serialize( $params ) );
}

/**
 * Get list of towns.
 *
 * @return array
 */
function vg_events_get_towns() {
    $override = function_exists( 'get_field' ) ? get_field( 'override_town_list', 'option' ) : [];
    if ( ! empty( $override ) ) {
        return (array) $override;
    }

    $cached = get_transient( 'vg_events_cached_towns' );
    if ( false === $cached ) {
        $cached = wp_cache_get( 'vg_events_cached_towns', 'vg_events' );
    }
    if ( false === $cached ) {
        global $wpdb;
        $sql     = "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value<>'' ORDER BY meta_value ASC";
        $results = $wpdb->get_col( $wpdb->prepare( $sql, 'dorpstad' ) );
        $cached  = array_values( array_filter( $results ) );
        set_transient( 'vg_events_cached_towns', $cached, 12 * HOUR_IN_SECONDS );
        wp_cache_set( 'vg_events_cached_towns', $cached, 'vg_events', 12 * HOUR_IN_SECONDS );
    }

    /**
     * Filter the list of towns for the sidebar dropdown.
     *
     * @param array $cached Array of town names.
     */
    return apply_filters( 'vg_events_sidebar_town_list', $cached );
}

/**
 * Get list of months based on event meta.
 *
 * @return array
 */
function vg_events_get_months() {
    $cached = get_transient( 'vg_events_cached_months' );
    if ( false === $cached ) {
        $cached = wp_cache_get( 'vg_events_cached_months', 'vg_events' );
    }
    if ( false === $cached ) {
        global $wpdb;
        $sql    = "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value<>''";
        $dates  = $wpdb->get_col( $wpdb->prepare( $sql, 'datum' ) );
        $months = [];
        foreach ( $dates as $d ) {
            $m            = date( 'm', strtotime( $d ) );
            $months[ $m ] = $m;
        }
        ksort( $months );
        $cached = array_keys( $months );
        set_transient( 'vg_events_cached_months', $cached, 12 * HOUR_IN_SECONDS );
        wp_cache_set( 'vg_events_cached_months', $cached, 'vg_events', 12 * HOUR_IN_SECONDS );
    }

    /**
     * Filter the list of months for the sidebar dropdown.
     *
     * @param array $cached Array of month numbers.
     */
    return apply_filters( 'vg_events_sidebar_month_list', $cached );
}

/**
 * Get supported post types from plugin settings.
 *
 * @return array
 */
function vg_events_get_supported_post_types() {
    $defaults = [
        'funksie',
        'eksterne-funksie',
        'feeste-markte',
        'uitdaging',
        'webinar',
        'reisklub-toer',
        'sport-gholfdae',
        'lootjies-kompetisies',
    ];

    $saved = get_option( 'vg_events_post_types', $defaults );
    return apply_filters( 'vg_events_supported_post_types', (array) $saved );
}

/**
 * Retrieve associative array of post type slugs to labels.
 *
 * @return array
 */
function vg_events_get_post_type_labels() {
    $labels = [];
    foreach ( vg_events_get_supported_post_types() as $pt ) {
        $obj = get_post_type_object( $pt );
        if ( $obj && ! empty( $obj->labels->singular_name ) ) {
            $labels[ $pt ] = $obj->labels->singular_name;
        } else {
            $labels[ $pt ] = ucfirst( str_replace( '-', ' ', $pt ) );
        }
    }

    return apply_filters( 'vg_events_post_type_labels', $labels );
}

/**
 * Locate a template with theme override support.
 *
 * @param string $file Template file name.
 * @return string
 */
function vg_events_template_path( $file ) {
    $located = locate_template( $file );
    if ( ! $located ) {
        $located = plugin_dir_path( __FILE__ ) . '../templates/' . $file;
    }
    return apply_filters( 'vg_events_template_path', $located, $file );
}

/**
 * Clear cached town and month lists when supported posts are saved.
 */
function vg_events_clear_cache_on_save( $post_id, $post ) {
    if ( ! $post || ! in_array( $post->post_type, vg_events_get_supported_post_types(), true ) ) {
        return;
    }
    vg_events_cache()->invalidate( 'towns' );
    vg_events_cache()->invalidate( 'months' );
    global $wpdb;
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_vg_events_%' ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_site_transient_vg_events_%' ) );
    do_action( 'vg_events_cache_invalidated', $post_id, $post );
}

add_action( 'save_post', 'vg_events_clear_cache_on_save', 10, 2 );

/**
 * Invalidate cached data when events are modified.
 */
function vg_events_invalidate_cache() {
    global $wpdb;
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_vg_events_%' ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_site_transient_vg_events_%' ) );
    vg_events_cache()->invalidate( 'towns' );
    vg_events_cache()->invalidate( 'months' );
    wp_cache_flush();
    do_action( 'vg_events_cache_invalidated', null, null );
}

add_action( 'save_post', 'vg_events_invalidate_cache' );
add_action( 'deleted_post', 'vg_events_invalidate_cache' );
add_action( 'edited_term', 'vg_events_invalidate_cache' );
add_action( 'created_term', 'vg_events_invalidate_cache' );
add_action( 'delete_term', 'vg_events_invalidate_cache' );
