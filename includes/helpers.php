<?php
/**
 * Helper functions for Voelgoed Events Calendar.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get list of towns.
 *
 * @return array
 */
function voelgoed_events_get_towns() {
    global $wpdb;
    $results = $wpdb->get_col( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key='dorpstad' AND meta_value<>'' ORDER BY meta_value ASC" );
    $towns = array_values( array_filter( $results ) );
    /**
     * Filter the list of towns.
     *
     * @param array $towns Array of town names.
     */
    return apply_filters( 'voelgoed_events_towns', $towns );
}

/**
 * Get list of months based on event meta.
 *
 * @return array
 */
function voelgoed_events_get_months() {
    global $wpdb;
    $dates = $wpdb->get_col( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key='datum' AND meta_value<>''" );
    $months = [];
    foreach ( $dates as $d ) {
        $m = date( 'm', strtotime( $d ) );
        $months[ $m ] = $m;
    }
    ksort( $months );
    $months = array_keys( $months );
    /**
     * Filter the list of months.
     *
     * @param array $months Array of month numbers.
     */
    return apply_filters( 'voelgoed_events_months', $months );
}
