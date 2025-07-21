<?php
/**
 * Plugin Name: Voelgoed Events Calendar
 * Description: Display events with filters using Elementor.
 * Version: 2.1.0
 * Author: Example
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/helpers.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-vg-events-cache.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-voelgoed-events-calendar.php';

add_filter( 'cron_schedules', function( $schedules ) {
    $schedules['vg_half_hour'] = [ 'interval' => 30 * MINUTE_IN_SECONDS, 'display' => 'Every 30 Minutes' ];
    return $schedules;
} );

register_activation_hook( __FILE__, function() {
    if ( ! wp_next_scheduled( 'vg_events_prewarm_cache' ) ) {
        wp_schedule_event( time(), 'vg_half_hour', 'vg_events_prewarm_cache' );
    }
} );

register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'vg_events_prewarm_cache' );
} );

add_action( 'vg_events_prewarm_cache', function() {
    $post_types = vg_events_get_supported_post_types();
    foreach ( $post_types as $type ) {
        for ( $p = 1; $p <= 3; $p++ ) {
            $params = [ 'selected_post_type' => $type, 'paged' => $p ];
            Voelgoed_Events_Calendar::instance()->render_events( $params );
        }
    }
} );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-vg-events-cli-stats.php';
    /**
     * Clear all cached event data.
     */
    WP_CLI::add_command( 'vg-events clear', function() {
        vg_events_cache()->clear_all();
        WP_CLI::success( 'Events cache cleared.' );
    } );

    /**
     * Prewarm the events cache for upcoming months.
     */
    WP_CLI::add_command( 'vg-events prewarm', function() {
        do_action( 'vg_events_prewarm_cache' );
        WP_CLI::success( 'Events cache prewarmed.' );
    } );
}
