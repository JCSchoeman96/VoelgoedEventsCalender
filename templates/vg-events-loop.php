<?php
/**
 * VG Events Loop Template
 * Renders a single event with layout matching the Elementor design.
 */
if (!defined('ABSPATH')) exit;

$meta      = get_post_meta( $post->ID, '', true );
$datum     = isset( $meta['datum'] ) ? $meta['datum'] : '';
$tyd       = isset( $meta['tyd'] ) ? $meta['tyd'] : '';
$venue     = isset( $meta['venue'] ) ? $meta['venue'] : '';
$lees_meer = isset( $meta['lees_meer_link'] ) ? $meta['lees_meer_link'] : '';
$dt        = $datum ? new DateTime( $datum ) : false;
$day       = $dt ? wp_date( 'j', $dt->getTimestamp() ) : '';
$month     = $dt ? wp_date( 'M', $dt->getTimestamp() ) : '';
$iso_date  = $dt ? $dt->format( 'c' ) : '';
$vg_events_debug = isset($vg_events_debug) ? (bool) $vg_events_debug : false;
?>
<style>
.vg-loop-item{padding:15px 0;font-family:'Lato',sans-serif;}
.vg-grid{display:grid;grid-template-columns:70px 1fr auto;gap:15px;align-items:center;}
</style>
<article class="vg-loop-item" itemscope itemtype="https://schema.org/Event">
    <div class="vg-grid">
        <div class="vg-date-block">
            <div class="vg-datekal">
                <span class="vg-day"><?php echo esc_html($day); ?></span>
                <span class="vg-month"><?php echo esc_html($month); ?></span>
            </div>
        </div>
        <?php if ( has_post_thumbnail() ) : ?>
            <div class="vg-thumb">
                <?php the_post_thumbnail( 'medium', [ 'loading' => 'lazy' ] ); ?>
            </div>
        <?php endif; ?>
        <div class="vg-event-info" itemprop="name">
            <span class="vg-post-type"><?php echo do_shortcode('[post_type_label]'); ?></span>
            <h3 class="vg-event-title"><?php the_title(); ?></h3>
            <div class="vg-event-meta">
                <time class="vg-meta" datetime="<?php echo esc_attr($iso_date); ?>" itemprop="startDate">
                    <i class="fa fa-calendar"></i> <?php echo $datum ? esc_html( wp_date( 'j F Y', $dt->getTimestamp() ) ) : 'Datum TBA'; ?>
                </time>
                <?php if ($tyd): ?>
                    <span class="vg-meta"><i class="fa fa-clock"></i> <?php echo esc_html($tyd); ?></span>
                <?php else: ?>
                    <span class="vg-meta">Tyd TBA</span>
                <?php endif; ?>
                <?php if ($venue): ?>
                    <span class="vg-meta"><i class="fa fa-map-marker-alt"></i> <?php echo esc_html($venue); ?></span>
                <?php else: ?>
                    <span class="vg-meta">No Venue Available</span>
                <?php endif; ?>
                <meta itemprop="location" content="<?php echo esc_attr( $venue ); ?>" />
            </div>
            <div class="vg-event-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20); ?></div>
        </div>
        <div class="vg-event-actions">
            <a class="vg-btn" href="<?php echo $lees_meer ? esc_url($lees_meer) : esc_url(get_permalink()); ?>">Mêêr oor</a>
        </div>
    </div>
    <div class="vg-divider"></div>
    <?php if ($vg_events_debug): ?>
    <div class="vg-debug-panel vg-debug-data" id="vg-events-debug-item-<?php echo esc_attr( $post->ID ); ?>">
        <?php echo esc_html( print_r([
            'post_id' => $post->ID,
            'post_type' => $post->post_type,
            'datum' => $datum,
            'tyd' => $tyd,
            'venue' => $venue,
            'lees_meer_link' => $lees_meer,
        ], true) ); ?>
    </div>
    <?php endif; ?>
    <script type="application/ld+json">
    <?php echo wp_json_encode([
        '@context' => 'https://schema.org',
        '@type'    => 'Event',
        'name'     => get_the_title(),
        'startDate'=> $iso_date,
        'location' => [ '@type' => 'Place', 'name' => $venue ],
    ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE ); ?>
    </script>
</article>
