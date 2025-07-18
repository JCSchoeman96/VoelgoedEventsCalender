<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Voelgoed_Events_Calendar {
    private static $instance = null;
    private $version = '1.5.0';
    private $option_template = 'vg_events_template_id';
    private $option_datepicker = 'vg_events_datepicker';
    private $option_post_types = 'vg_events_post_types';
    private $option_debug = 'vg_events_debug';
    private $default_post_types = [
        'funksie',
        'eksterne-funksie',
        'feeste-markte',
        'uitdaging',
        'webinar',
        'reisklub-toer',
        'sport-gholfdae',
        'lootjies-kompetisies'
    ];
    private $post_types = [];
    private $debug = false;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->post_types = get_option( $this->option_post_types, $this->default_post_types );
        $this->debug      = (bool) get_option( $this->option_debug, 0 );

        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);
        add_shortcode('custom_loop_code_sidebar', [$this, 'shortcode']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_head', [$this, 'preload_flatpickr_assets']);
        register_block_type(__DIR__ . '/../assets/js/blocks.js', [
            'render_callback' => [$this, 'shortcode'],
        ]);
    }

    public function maybe_enqueue_assets() {
        if (is_singular()) {
            global $post;
            if (has_shortcode($post->post_content, 'custom_loop_code_sidebar') || has_block('vg-events/calendar', $post)) {
                $this->enqueue_assets();
                return;
            }
            if (function_exists('elementor_theme_do_location') && elementor_theme_do_location('single')) {
                ob_start();
                the_content();
                $content = ob_get_clean();
                if (has_shortcode($content, 'custom_loop_code_sidebar')) {
                    $this->enqueue_assets();
                }
            }
        }
    }

    public function enqueue_assets() {
        $enable_datepicker = get_option( $this->option_datepicker, 1 );
        wp_enqueue_style( 'vg-events-calendar', plugins_url( '../assets/css/events-calendar.css', __FILE__ ), [], $this->version );
        if ( did_action( 'elementor/loaded' ) ) {
            Elementor\Plugin::instance()->frontend->enqueue_styles();
        }
        wp_register_script(
            'vg-events-calendar',
            plugins_url( '../assets/js/events-calendar.js', __FILE__ ),
            [],
            $this->version,
            true
        );
        $data = [
            'rest_url'   => esc_url_raw(rest_url('vg-events/v1/events')),
            'post_types' => $this->post_types,
            'labels'     => vg_events_get_post_type_labels(),
            'template_id'=> intval(get_option($this->option_template, 38859)),
            'useDatepicker' => (bool) $enable_datepicker,
            'flatpickr_js' => 'https://cdn.jsdelivr.net/npm/flatpickr',
            'flatpickr_css' => 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
            'towns' => $this->get_towns(),
            'months' => $this->get_months(),
            'nonce'  => wp_create_nonce('wp_rest'),
            'debug'  => $this->debug,
        ];
        wp_add_inline_script('vg-events-calendar', 'var vgEvents = ' . wp_json_encode($data) . ';', 'before');
        wp_enqueue_script('vg-events-calendar');
    }

    public function shortcode() {
        ob_start();
        include plugin_dir_path(__FILE__) . '../templates/shortcode.php';
        return ob_get_clean();
    }

    public function rest_load(WP_REST_Request $request) {
        $params = $request->get_params();
        $cache_key = 'vg_events_results_' . md5( serialize( $params ) );
        if ( ! $request->get_param( 'cache_bust' ) ) {
            $cached = get_transient( $cache_key );
            if ( false === $cached ) {
                $cached = wp_cache_get( $cache_key );
            }
            if ( false !== $cached ) {
                return rest_ensure_response( apply_filters( 'vg_events_rest_response', $cached ) );
            }
        }

        $response = $this->get_events_response( $params );
        set_transient( $cache_key, $response, 300 );
        wp_cache_set( $cache_key, $response, '', 300 );
        return rest_ensure_response( apply_filters( 'vg_events_rest_response', $response ) );
    }

    private function get_events_response( $params ) {
        $post_types         = isset($params['post_types']) ? (array) $params['post_types'] : $this->post_types;
        $selected_post_type = isset($params['selected_post_type']) ? sanitize_text_field($params['selected_post_type']) : '';
        $start_date         = isset($params['start_date']) ? sanitize_text_field($params['start_date']) : '';
        $end_date           = isset($params['end_date']) ? sanitize_text_field($params['end_date']) : '';
        $search             = isset($params['search']) ? sanitize_text_field($params['search']) : '';
        $month              = isset($params['month']) ? sanitize_text_field($params['month']) : '';
        $town               = isset($params['town']) ? sanitize_text_field($params['town']) : '';
        $template_id        = isset($params['template_id']) ? intval($params['template_id']) : 0;
        $paged              = isset($params['paged']) ? intval($params['paged']) : 1;
        $posts_per_page     = 5;

        $today = date('Ymd');
        $args = [
            'post_type'      => !empty($selected_post_type) ? $selected_post_type : $post_types,
            'posts_per_page' => -1,
            'meta_query'     => []
        ];


        if (empty($selected_post_type) && empty($start_date) && empty($end_date) && empty($month) && empty($search) && empty($town)) {
            $args['meta_query'][] = [
                'key'     => 'datum',
                'type'    => 'DATE',
                'value'   => $today,
                'compare' => '>=',
            ];
        }

        if (!empty($search)) {
            $args['s'] = $search;
            if (empty($start_date) && empty($end_date)) {
                $args['meta_query'][] = [
                    'key'     => 'datum',
                    'type'    => 'DATE',
                    'value'   => $today,
                    'compare' => '>=',
                ];
            }
        }

        if (!empty($selected_post_type) && empty($start_date) && empty($end_date)) {
            $args['meta_query'][] = [
                'key'     => 'datum',
                'type'    => 'DATE',
                'value'   => $today,
                'compare' => '>=',
            ];
        }

        if (!empty($town)) {
            $args['meta_query'][] = [
                'key'     => 'dorpstad',
                'value'   => $town,
                'compare' => '=',
            ];
        }

        if (!empty($month)) {
            $cy = date('Y');
            $cm = date('m');
            $yr = ((int) $month < (int) $cm) ? $cy + 1 : $cy;
            $ms = "$yr-$month-01";
            $me = date('Y-m-t', strtotime($ms));
            if ($yr == $cy && $month == $cm) {
                $ms = $today;
            }
            $args['meta_query'][] = [
                'key'     => 'datum',
                'type'    => 'DATE',
                'value'   => [$ms, $me],
                'compare' => 'BETWEEN',
            ];
        }

        if (!empty($start_date) || !empty($end_date)) {
            $args['meta_query'][] = [
                'key'     => 'datum',
                'type'    => 'DATE',
                'value'   => [$start_date, $end_date],
                'compare' => 'BETWEEN',
            ];
        }

        $args  = apply_filters( 'vg_events_filter_args', $args, $params );
        $query = new WP_Query($args);
        $all_posts = $query->posts;
        $sorted_posts = $this->sort_posts_by_datum($all_posts);
        $total_posts = count($sorted_posts);
        $total_pages = ceil($total_posts / $posts_per_page);
        if ($paged < 1) {
            $paged = 1;
        }
        if ($paged > $total_pages && $total_pages > 0) {
            $paged = $total_pages;
        }
        $start = ($paged - 1) * $posts_per_page;
        $current_page_posts = array_slice($sorted_posts, $start, $posts_per_page);

        if ( ! did_action( 'elementor/loaded' ) ) {
            error_log( '[VG Events] Elementor not loaded when rendering events.' );
        }

        $template_errors = [];
        $debug_items     = [];

        ob_start();
        if (!empty($current_page_posts)) {
            global $post;
            foreach ($current_page_posts as $post) {
                setup_postdata($post);
                $html = did_action( 'elementor/loaded' ) ? Elementor\Plugin::instance()->frontend->get_builder_content_for_display($template_id) : '';
                $rendered = ! empty( trim( $html ) );
                if ( ! $rendered ) {
                    $template_errors[] = $post->ID;
                    error_log('[VG Events] Template render issue for post ID: ' . $post->ID);
                    $html = '<div class="vg-fallback-loop-item"><h3>' . get_the_title() . '</h3><p>' . esc_html( get_post_meta(get_the_ID(), 'datum', true) ) . '</p><p>' . esc_html( wp_trim_words(get_the_excerpt(), 20) ) . '</p></div>';
                }

                echo '<div class="vg-loop-item">';
                if ( $this->debug ) {
                    echo '<div class="vg-debug-label">[DEBUG]</div>';
                    echo '<div class="vg-debug">';
                    echo 'Post ID: ' . $post->ID . '<br />';
                    echo 'Post Type: ' . esc_html( $post->post_type ) . '<br />';
                    echo 'Datum: ' . esc_html( get_post_meta( $post->ID, 'datum', true ) ) . '<br />';
                    echo '</div>';
                    echo '<pre class="vg-template-html">' . esc_html( $html ) . '</pre>';
                }
                echo $html;
                if ( $this->debug ) {
                    echo '<style>.vg-loop-item{border:1px solid #ddd;padding:10px;margin-bottom:15px;background:#fff;} .vg-debug-label{color:red;font-weight:bold;margin-bottom:5px;} .vg-template-html{background:#f9f9f9;padding:10px;border:1px solid #ccc;white-space:pre-wrap;} .vg-fallback-loop-item{background:#f9f9f9;padding:10px;}</style>';
                }
                echo '</div>';

                if ( $this->debug ) {
                    $debug_items[] = [
                        'post_id'           => $post->ID,
                        'template_rendered' => $rendered,
                    ];
                }
            }
            wp_reset_postdata();
        } else {
            echo 'Geen events gevind nie.';
        }
        $content = ob_get_clean();

        $response = [
            'content'      => $content,
            'total_pages'  => $total_pages,
            'current_page' => $paged,
        ];
        if ( $this->debug ) {
            $response['debug'] = [
                'args'        => $args,
                'params'      => $params,
                'total_posts' => $total_posts,
                'template_errors' => $template_errors,
                'items'       => $debug_items,
            ];
        }
        return $response;
    }

    public function register_rest_routes() {
        register_rest_route('vg-events/v1', '/events', [
            'methods'  => 'GET',
            'callback' => [$this, 'rest_load'],
            'permission_callback' => [$this, 'rest_permission_check'],
        ]);
    }

    public function admin_menu() {
        add_options_page('VG Events Calendar', 'VG Events Calendar', 'manage_options', 'vg-events-calendar', [$this, 'settings_page']);
    }

    public function register_settings() {
        register_setting( 'vg_events', $this->option_template, [ 'type' => 'integer', 'default' => 38859 ] );
        register_setting( 'vg_events', $this->option_datepicker, [ 'type' => 'boolean', 'default' => 1 ] );
        register_setting( 'vg_events', $this->option_post_types, [
            'type'              => 'array',
            'sanitize_callback' => function( $value ) { return array_map( 'sanitize_text_field', (array) $value ); },
            'default'           => $this->default_post_types,
        ] );
        register_setting( 'vg_events', $this->option_debug, [ 'type' => 'boolean', 'default' => 0 ] );
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Voelgoed Events Calendar</h1>
            <form method="post" action="options.php">
                <?php settings_fields('vg_events'); ?>

                <h2>Display Settings</h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr($this->option_template); ?>">Template ID</label></th>
                        <td>
                            <input name="<?php echo esc_attr($this->option_template); ?>" type="number" value="<?php echo esc_attr(get_option($this->option_template, 38859)); ?>" class="regular-text">
                            <p class="description">Elementor template used for each event.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Datepicker Fallback</th>
                        <td>
                            <input name="<?php echo esc_attr($this->option_datepicker); ?>" type="checkbox" value="1" <?php checked(get_option($this->option_datepicker, 1), 1); ?>>
                            <p class="description">Load JS datepicker when browsers lack native support.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Debug Mode</th>
                        <td>
                            <input name="<?php echo esc_attr($this->option_debug); ?>" type="checkbox" value="1" <?php checked(get_option($this->option_debug, 0), 1); ?>>
                            <p class="description">Output query parameters and counts for troubleshooting.</p>
                        </td>
                    </tr>
                </table>

                <h2>Enabled Post Types</h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Post Types</th>
                        <td>
                            <?php $saved = (array) get_option( $this->option_post_types, $this->default_post_types ); ?>
                            <?php foreach ( $this->default_post_types as $pt ) : ?>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( $this->option_post_types ); ?>[]" value="<?php echo esc_attr( $pt ); ?>" <?php checked( in_array( $pt, $saved, true ) ); ?> /> <?php echo esc_html( $pt ); ?>
                                </label><br />
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>

                <h2>Detected Towns (Read-only)</h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Towns</th>
                        <td>
                            <textarea readonly rows="5" style="width:100%"><?php echo esc_textarea( implode( ", ", vg_events_get_towns() ) ); ?></textarea>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function get_towns() {
        return vg_events_get_towns();
    }

    private function get_months() {
        return vg_events_get_months();
    }

    public function render_events( $params = [] ) {
        $data = $this->get_events_response( $params );
        return $data['content'];
    }


    public function preload_flatpickr_assets() {
        if ( get_option( $this->option_datepicker, 1 ) ) {
            echo '<link rel="preload" href="https://cdn.jsdelivr.net/npm/flatpickr" as="script">\n';
            echo '<link rel="preload" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" as="style">\n';
        }
    }

    public function rest_permission_check() {
        return current_user_can( 'read' ) || is_user_logged_in() || check_ajax_referer( 'wp_rest', '_wpnonce', false );
    }

    private function sort_posts_by_datum($posts) {
        if (empty($posts)) {
            return $posts;
        }
        usort($posts, function ($a, $b) {
            $da = $this->get_clean_datum($a->ID);
            $db = $this->get_clean_datum($b->ID);
            return $da <=> $db;
        });
        return $posts;
    }

    private function get_clean_datum($post_id) {
        $datum = get_post_meta($post_id, 'datum', true);
        if ( is_array( $datum ) ) {
            $datum = reset( $datum );
        }
        $datum = preg_replace( '/[^0-9]/', '', (string) $datum );
        return (int) $datum;
    }
}

Voelgoed_Events_Calendar::instance();
