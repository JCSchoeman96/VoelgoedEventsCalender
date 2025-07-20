<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

class VG_Events_CLI_Stats {
    /**
     * Display cache statistics.
     */
    public function __invoke() {
        $stats = vg_events_cache()->stats();
        WP_CLI::line( 'Cached keys: ' . $stats['count'] );
        WP_CLI::line( 'Oldest entry: ' . ( $stats['oldest'] ? $stats['oldest'] : 'N/A' ) );
        WP_CLI::line( 'Hit ratio: ' . $stats['hit_ratio'] . '%' );
    }
}

WP_CLI::add_command( 'vg-events stats', 'VG_Events_CLI_Stats' );
