<?php
/**
 * VG Events Loop Template
 * Renders a single event with layout matching the Elementor design.
 */
if (!defined('ABSPATH')) exit;

global $post;
$datum     = get_post_meta($post->ID, 'datum', true);
$tyd       = get_post_meta($post->ID, 'tyd', true);
$venue     = get_post_meta($post->ID, 'venue', true);
$lees_meer = get_post_meta($post->ID, 'lees_meer_link', true);
$day   = $datum ? date_i18n('j', strtotime($datum)) : '';
$month = $datum ? date_i18n('M', strtotime($datum)) : '';
$vg_events_debug = isset($vg_events_debug) ? (bool) $vg_events_debug : false;
?>
<div class="vg-loop-item">
    <div class="vg-grid">
        <div class="vg-date-block">
            <div class="vg-datekal">
                <span class="vg-day"><?php echo esc_html($day); ?></span>
                <span class="vg-month"><?php echo esc_html($month); ?></span>
            </div>
        </div>
        <div class="vg-event-info">
            <span class="vg-post-type"><?php echo do_shortcode('[post_type_label]'); ?></span>
            <h3 class="vg-event-title"><?php the_title(); ?></h3>
            <div class="vg-event-meta">
                <?php if ($datum): ?>
                    <span class="vg-meta"><i class="fa fa-calendar"></i> <?php echo esc_html(date_i18n('j F Y', strtotime($datum))); ?></span>
                <?php endif; ?>
                <?php if ($tyd): ?>
                    <span class="vg-meta"><i class="fa fa-clock"></i> <?php echo esc_html($tyd); ?></span>
                <?php endif; ?>
                <?php if ($venue): ?>
                    <span class="vg-meta"><i class="fa fa-map-marker-alt"></i> <?php echo esc_html($venue); ?></span>
                <?php endif; ?>
            </div>
            <div class="vg-event-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20); ?></div>
        </div>
        <div class="vg-event-actions">
            <a class="vg-btn" href="<?php echo $lees_meer ? esc_url($lees_meer) : esc_url(get_permalink()); ?>">Mêêr oor</a>
        </div>
    </div>
    <div class="vg-divider"></div>
    <?php if ($vg_events_debug): ?>
    <pre class="vg-debug-data">
        <?php echo esc_html( print_r([
            'post_id' => $post->ID,
            'post_type' => $post->post_type,
            'datum' => $datum,
            'tyd' => $tyd,
            'venue' => $venue,
            'lees_meer_link' => $lees_meer,
        ], true) ); ?>
    </pre>
    <?php endif; ?>
</div>
