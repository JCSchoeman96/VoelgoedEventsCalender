<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Voelgoed_Events_Calendar {
    private static $instance = null;
    private $version = '1.0.0';
    private $post_types = [
        'funksie',
        'eksterne-funksie',
        'feeste-markte',
        'uitdaging',
        'webinar',
        'reisklub-toer',
        'sport-gholfdae',
        'lootjies-kompetisies'
    ];

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('custom_loop_code_sidebar', [$this, 'shortcode']);
        add_action('wp_ajax_load_elementor_loop_content', [$this, 'ajax_load']);
        add_action('wp_ajax_nopriv_load_elementor_loop_content', [$this, 'ajax_load']);
    }

    public function enqueue_assets() {
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        wp_enqueue_style('vg-events-calendar', plugins_url('../assets/css/events-calendar.css', __FILE__), [], $this->version);
        wp_enqueue_script('jquery-ui-datepicker');
        wp_register_script(
            'vg-events-calendar',
            plugins_url('../assets/js/events-calendar.js', __FILE__),
            ['jquery', 'jquery-ui-datepicker'],
            $this->version,
            true
        );
        $data = [
            'ajax_url'   => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('vg_events'),
            'post_types' => $this->post_types,
            'template_id'=> 38859,
        ];
        wp_add_inline_script('vg-events-calendar', 'var vgEvents = ' . wp_json_encode($data) . ';', 'before');
        wp_enqueue_script('vg-events-calendar');
    }

    public function shortcode() {
        ob_start();
        include plugin_dir_path(__FILE__) . '../templates/shortcode.php';
        return ob_get_clean();
    }

    public function ajax_load() {
        check_ajax_referer('vg_events', 'nonce');

        $post_types         = isset($_POST['post_types']) ? (array) json_decode(stripslashes($_POST['post_types']), true) : $this->post_types;
        $selected_post_type = isset($_POST['selected_post_type']) ? sanitize_text_field($_POST['selected_post_type']) : '';
        $start_date         = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date           = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $search             = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $month              = isset($_POST['month']) ? sanitize_text_field($_POST['month']) : '';
        $town               = isset($_POST['town']) ? sanitize_text_field($_POST['town']) : '';
        $template_id        = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $paged              = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
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

        wp_send_json_success([
            'content'      => $content,
            'total_pages'  => $total_pages,
            'current_page' => $paged,
        ]);
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
