<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Voelgoed_Events_Calendar {
    private static $instance = null;
    private $version = '1.3.0';
    private $option_template = 'vg_events_template_id';
    private $option_datepicker = 'vg_events_datepicker';
    private $option_post_types = 'vg_events_post_types';
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

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->post_types = get_option( $this->option_post_types, $this->default_post_types );

        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);
        add_shortcode('custom_loop_code_sidebar', [$this, 'shortcode']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('save_post', [$this, 'clear_cache'], 10, 3);
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
            'template_id'=> intval(get_option($this->option_template, 38859)),
            'useDatepicker' => (bool) $enable_datepicker,
            'flatpickr_js' => 'https://cdn.jsdelivr.net/npm/flatpickr',
            'flatpickr_css' => 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
            'towns' => $this->get_towns(),
            'months' => $this->get_months(),
            'nonce'  => wp_create_nonce('wp_rest'),
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
        $cache_key = 'vg_events_' . md5( serialize( $params ) );
        if ( ! $request->get_param( 'cache_bust' ) ) {
            $cached = wp_cache_get( $cache_key, 'vg_events' );
            if ( $cached ) {
                return rest_ensure_response( $cached );
            }
        }

        $response = $this->get_events_response( $params );
        wp_cache_set( $cache_key, $response, 'vg_events', 300 );
        return rest_ensure_response( $response );
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

        ob_start();
        if (!empty($current_page_posts)) {
            global $post;
            foreach ($current_page_posts as $post) {
                setup_postdata($post);
                echo Elementor\Plugin::instance()->frontend->get_builder_content_for_display($template_id);
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
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Voelgoed Events Calendar</h1>
            <form method="post" action="options.php">
                <?php settings_fields('vg_events'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr($this->option_template); ?>">Default Template ID</label></th>
                        <td><input name="<?php echo esc_attr($this->option_template); ?>" type="number" value="<?php echo esc_attr(get_option($this->option_template, 38859)); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Enabled Post Types</th>
                        <td>
                            <?php $saved = (array) get_option( $this->option_post_types, $this->default_post_types ); ?>
                            <?php foreach ( $this->default_post_types as $pt ) : ?>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( $this->option_post_types ); ?>[]" value="<?php echo esc_attr( $pt ); ?>" <?php checked( in_array( $pt, $saved, true ) ); ?> /> <?php echo esc_html( $pt ); ?>
                                </label><br />
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable JS Datepicker Fallback</th>
                        <td><input name="<?php echo esc_attr($this->option_datepicker); ?>" type="checkbox" value="1" <?php checked(get_option($this->option_datepicker, 1), 1); ?>></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function get_towns() {
        return voelgoed_events_get_towns();
    }

    private function get_months() {
        return voelgoed_events_get_months();
    }

    public function render_events( $params = [] ) {
        $data = $this->get_events_response( $params );
        return $data['content'];
    }

    public function clear_cache( $post_id, $post, $update ) {
        if ( ! $post || ! in_array( $post->post_type, $this->post_types, true ) ) {
            return;
        }
        wp_cache_delete( 'vg_towns', 'vg_events' );
        wp_cache_delete( 'vg_months', 'vg_events' );
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
            return intval($da) - intval($db);
        });
        return $posts;
    }

    private function get_clean_datum($post_id) {
        $datum = get_post_meta($post_id, 'datum', true);
        return is_array($datum) ? $datum[0] : $datum;
    }
}

Voelgoed_Events_Calendar::instance();
