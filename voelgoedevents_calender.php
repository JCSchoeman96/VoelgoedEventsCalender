// Ensure Elementor is active
if (!did_action('elementor/loaded')) {
    return;
}

function elementor_loop_ajax_sidebar_html($atts, $post_types) {
    $output = '<div class="flex-container">';
    
    // Sidebar with Filters and Pagination
    $output .= '<div class="sidebar">';
    $output .= '<div class="filters">'; // Wrapper for filters only
    $output .= '<h2 style="color:#6B6E76;">EVENTS</h2>';
    $output .= '<h5 style="color:#6B6E76;">NAAM VAN EVENT</h5>';
    $output .= '<input type="text" id="search-bar" placeholder="Naam">';

    // Custom display names for post types
    $custom_post_type_names = array(
        'funksie' => 'Funksies, vroue-oggende …',
        'eksterne-funksie' => 'Vertonings, produksies …',
        'uitdaging' => 'Werkswinkels, praatjies …',
        'reisklub-toer' => 'Reise, toere …',
        'webinar' => 'Aanlyn, uitsendings …',
		'feeste-markte' => 'Feeste, markte ...',
		'sport-gholfdae' => 'Sport, gholfdae ...',
		'lootjies-kompetisies' => 'Lootjies, kompetisies …'
    );

    $output .= '<h5 style="color:#6B6E76;"><br><br>TIPE EVENT</h5>';
    $output .= '<ul id="post-type-filters">';

    foreach ($post_types as $type) {
        $display_name = isset($custom_post_type_names[$type]) ? $custom_post_type_names[$type] : ucfirst(str_replace('-', ' ', $type));

        // Add Voelgoed Events tab with subcategory dropdown
        if ($type === 'funksie') {
            $output .= sprintf(
                '<li class="post-type-filter %s" data-post-type="%s">%s</li>',
                esc_attr($type),
                esc_attr($type),
                esc_html($display_name)
            );
        } else {
            // Regular category item for other post types
            $output .= sprintf(
               '<li class="post-type-filter %s" data-post-type="%s">%s</li>',
                esc_attr($type),
                esc_attr($type),
                esc_html($display_name)
            );
        }
    }

    $output .= '</ul>';

    // Additional Filters
    $output .= '<h5 style="color:#6B6E76;"><br><br>DORP</h5>';
    $output .= '<select id="town-filter"><option value="">Dorp</option>';
    $towns = array('Bloemfontein', 'Centurion', 'Kaapstad', 'Kemptonpark', 'Klerksdorp', 'Krugersdorp', 'Pretoria', 'Vanderbijlpark', 'Vereeniging');
    foreach ($towns as $town) {
        $output .= '<option value="' . esc_attr($town) . '">' . esc_html($town) . '</option>';
    }
    $output .= '</select>';

    $output .= '<h5 style="color:#6B6E76;"><br><br>MAAND</h5><select id="month-filter"><option value="">Maand</option>';
    $months = array(
        '01' => 'Januarie', '02' => 'Februarie', '03' => 'Maart', '04' => 'April',
        '05' => 'Mei', '06' => 'Junie', '07' => 'Julie', '08' => 'Augustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    );
    foreach ($months as $key => $month) {
        $output .= '<option value="' . esc_attr($key) . '">' . esc_html($month) . '</option>';
    }
    $output .= '</select>';

    $output .= '<h5 style="color:#6B6E76;"><br><br>TYDPERK</h5><input type="text" id="start-date" placeholder="Begindatum">';
    $output .= '<input type="text" id="end-date" placeholder="Einddatum">';
	$output .= '<button id="reset-filters">Herstel Filters</button>';
	$output .= '<div id="filter-reset-msg" style="display:none; color: green; margin-top: 10px; font-weight: bold;">Filters herstel ✅</div>';


    $output .= '</div>'; // End filters

    // Pagination beneath Filters, but within Sidebar
	$output .= '</div>'; // End sidebar

	$output .= '<div class="content-and-pagination">';
	$output .= '<div id="elementor-loop-content" class="content"></div>';
	$output .= '<div class="pagination-wrapper">
					  <div id="pagination-controls" class="pagination-controls">
						  <button id="prev-page" disabled>Vorige</button>
						  <span id="page-info"></span>
						  <button id="next-page">Volgende</button>
					  </div>
				   </div>';
	$output .= '</div>'; // End flex-container (opened at start of function)

	
    return $output;
}

function elementor_loop_ajax_sidebar_shortcode($atts) {
    $post_types = array('funksie', 'eksterne-funksie', 'feeste-markte', 'uitdaging', 'webinar', 'reisklub-toer', 'sport-gholfdae', 'lootjies-kompetisies');
    $html = elementor_loop_ajax_sidebar_html($atts, $post_types);

    // Enqueue jQuery UI datepicker and custom CSS for layout
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

    // Include inline styles and JavaScript for responsive functionality
    $html .= elementor_loop_ajax_sidebar_css() . elementor_loop_ajax_sidebar_js();

    return $html;
}
add_shortcode('custom_loop_code_sidebar', 'elementor_loop_ajax_sidebar_shortcode');

function elementor_loop_ajax_sidebar_css() {
    return '<style>
		:root {
		  --filter-font-size: 0.9rem;
		  --filter-font-family: "Lato", sans-serif;
		  --filter-padding: 0.5rem;
		  --filter-border-radius: 0.375rem;
		  --filter-bg: #f2f2f2;
		  --filter-color: #67768E;
		  --filter-border: none;
		}

        .flex-container {
            display: flex;
            flex-wrap: nowrap;
            gap: 20px;
        }
        .sidebar {
            flex: 0 1 330px;
            max-width: 330px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
			position: relative; /* Allow positioning adjustments */
        }
		
        .filters {
            flex: 1;
        }
		
        .pagination-wrapper {
            text-align: center;
            padding-top: 10px;
        }
		
        .pagination-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }
		
        #page-info {
            font-size: 16px;
            font-weight: 400;
        }
		
        #prev-page, #next-page {
            padding: 7px 10px;
            background-color: #ff3800;
            color: white;
            border: var(--filter-border);
            cursor: pointer;
            font-weight: bold;
			border-radius: var(--filter-border-radius);
        }
		
        #prev-page:disabled, #next-page:disabled {
            cursor: not-allowed;
        }
		
		 /* Customize h2 and h5 color in the sidebar */
        .sidebar h2,
		.sidebar h5 {
            color: #6b6e76;
        }
		
		.filters input[type="text"],
		.filters select {
		  font-size: var(--filter-font-size);
		  font-family: var(--filter-font-family);
		  padding: var(--filter-padding);
		  border-radius: var(--filter-border-radius);
		  background: var(--filter-bg);
		  color: var(--filter-color);
		  border: var(--filter-border);
		  width: 100%;
		  box-sizing: border-box;
		}
		
		#start-date.hasDatepicker, #end-date.hasDatepicker {
			font-size: var(--filter-font-size);
			font-family: var(--filter-font-family);
			padding: var(--filter-padding);
			border-radius: var(--filter-border-radius);
			background: var(--filter-bg);
			color: var(--filter-color) !important;
			border: var(--filter-border);
			width: 100%;
			box-sizing: border-box;
		}


		.filters input[type="text"]:focus,
		.filters select:focus {
		  outline: none;
		  box-shadow: 0 0 0 2px #ff3800;
		}

        .content-and-pagination {
			flex: 1;
			display: flex;
			flex-direction: column;
		}
		
		@media (max-width: 768px) {
			.flex-container {
				flex-direction: column;
			}

			.sidebar {
				order: 1;
				width: 100%;
				max-width: 100%;
			}

			.content-and-pagination {
				order: 2;
			}

			.pagination-wrapper {
				order: 3;
				margin-bottom: 2rem;
			}
	}	
		#reset-filters {
			margin-top: 1rem;
			background-color: #ccc;
			border: var(--filter-border);
			padding: 8px 12px;
			font-weight: bold;
			color: #333;
			cursor: pointer;
			font-size: 0.9rem;
			border-radius: var(--filter-border-radius);
			transition: background 0.2s ease;
		}

		#reset-filters:hover {
			background-color: #aaa;
		}
		#filter-reset-msg {
			font-size: 0.9rem;
			background-color: #e6f9e6;
			border: 1px solid #6bb26b;
			padding: 8px 12px;
			border-radius: var(--filter-border-radius);
			color: #317b31;
		}

    </style>';
}

function elementor_loop_ajax_sidebar_js() {
    $post_types_js = json_encode(array('post_types' => array('funksie', 'eksterne-funksie', 'uitdaging', 'reisklub-toer', 'webinar', 'sport-gholfdae', 'feeste-markte', 'lootjies-kompetisies')));

    return '<script>
        document.addEventListener("DOMContentLoaded", function() {
            let currentPage = 1;
            let totalPages = 1; // Updated after content loads

            // Initialize Datepicker on Start and End Date Fields
            jQuery("#start-date, #end-date").datepicker({
                dateFormat: "yy-mm-dd", // Adjust format to match your date meta field
                changeMonth: true,
                changeYear: true,
                showButtonPanel: true
			});
			
            // Function to load content via AJAX
            function loadContent(postType = "", startDate = "", endDate = "", search = "", month = "", town = "", page = 1) {
                jQuery("#elementor-loop-content").html("<p>Loading...</p>");
                jQuery.ajax({
                    url: "' . admin_url('admin-ajax.php') . '",
                    type: "POST",
                    data: {
                        action: "load_elementor_loop_content",
                        post_types: ' . $post_types_js . '.post_types,
                        selected_post_type: postType,
                        start_date: startDate,
                        end_date: endDate,
                        search: search,
                        month: month,
                        town: town,
                        template_id: 38859,
                        paged: page
                    },
                    success: function(response) {
                        if (response.success) {
                            jQuery("#elementor-loop-content").html(response.data.content);
                            totalPages = response.data.total_pages;
                            currentPage = response.data.current_page;
                            updatePaginationControls();
                        } else {
                            jQuery("#elementor-loop-content").html("<p>No posts found.</p>");
                        }
                    },
                    error: function() {
                        jQuery("#elementor-loop-content").html("<p>Error loading content. Please try again.</p>");
                    }
                });
            }

            // Event listeners for pagination buttons
			jQuery("#prev-page").on("click", function() {
			  if (currentPage > 1) {
				currentPage--;
				// grab whatever filters are currently selected:
				const postType  = jQuery("#post-type-filters .post-type-filter.active").data("post-type") || "";
				const startDate = jQuery("#start-date").val();
				const endDate   = jQuery("#end-date").val();
				const search    = jQuery("#search-bar").val();
				const month     = jQuery("#month-filter").val();
				const town      = jQuery("#town-filter").val();
				loadContent(postType, startDate, endDate, search, month, town, currentPage);
			  }
			});

			jQuery("#next-page").on("click", function() {
			  if (currentPage < totalPages) {
				currentPage++;
				const postType  = jQuery("#post-type-filters .post-type-filter.active").data("post-type") || "";
				const startDate = jQuery("#start-date").val();
				const endDate   = jQuery("#end-date").val();
				const search    = jQuery("#search-bar").val();
				const month     = jQuery("#month-filter").val();
				const town      = jQuery("#town-filter").val();
				loadContent(postType, startDate, endDate, search, month, town, currentPage);
			  }
			});


            // Update pagination controls based on the current page
            function updatePaginationControls() {
                jQuery("#page-info").text(`${currentPage} van ${totalPages}`);
                jQuery("#prev-page").prop("disabled", currentPage === 1);
                jQuery("#next-page").prop("disabled", currentPage === totalPages);
            }

            // Load initial content
            loadContent();

            // Event listeners for sidebar filters
            jQuery("#post-type-filters .post-type-filter").on("click", function() {
                currentPage = 1; // Reset to the first page on filter change
                const postType = jQuery(this).data("post-type");

                const startDate = jQuery("#start-date").val();
                const endDate = jQuery("#end-date").val();
                const search = jQuery("#search-bar").val();
                const month = jQuery("#month-filter").val();
                const town = jQuery("#town-filter").val();

                // Highlight the selected filter
                jQuery("#post-type-filters .post-type-filter").removeClass("active");
                jQuery(this).addClass("active");

                // Load content with new filter parameters
                loadContent(postType, startDate, endDate, search, month, town, "", currentPage);
            });

            // Additional event listeners for other filters
            jQuery("#start-date, #end-date, #search-bar, #month-filter, #town-filter").on("change", function() {
                currentPage = 1; // Reset to the first page on filter change
                const postType = jQuery("#post-type-filters .post-type-filter.active").data("post-type") || "";
                const startDate = jQuery("#start-date").val();
                const endDate = jQuery("#end-date").val();
                const search = jQuery("#search-bar").val();
                const month = jQuery("#month-filter").val();
                const town = jQuery("#town-filter").val();

                // Load content with updated filter parameters
                loadContent(postType, startDate, endDate, search, month, town, currentPage);
            });
			
			// Reset filters button
			jQuery("#reset-filters").on("click", function() {
				// Clear all filter inputs
				jQuery("#search-bar").val(\'\');
				jQuery("#town-filter").val(\'\');
				jQuery("#month-filter").val(\'\');
				jQuery("#start-date").val(\'\');
				jQuery("#end-date").val(\'\');
				
				// Remove active post type
				jQuery("#post-type-filters .post-type-filter").removeClass("active");
				
				// Reload default content
				currentPage = 1;
				loadContent(); // default params = no filters
				
				// UX improvement: show confirmation message
    			const msg = jQuery("#filter-reset-msg");
    			msg.stop(true, true).fadeIn(200);

    			setTimeout(function () { 
					msg.fadeOut(400);
    			}, 2500);
			});
        });
    </script>';
}

//Get Clean Datum
function get_clean_datum($post_id) {
    $datum = get_post_meta($post_id, 'datum', true);
    return is_array($datum) ? $datum[0] : $datum;
}

// 1) Helper: sort an array of WP_Post objects by your 'datum' meta
function sort_posts_by_datum($posts) {
    if (empty($posts)) return $posts;

    usort($posts, function($a, $b) {
        $da = get_clean_datum($a->ID);
        $db = get_clean_datum($b->ID);
        return intval($da) - intval($db);
    });

    return $posts;
}

// 2) Hook it up for both logged-in and guest AJAX calls
add_action( 'wp_ajax_load_elementor_loop_content',     'load_elementor_loop_content' );
add_action( 'wp_ajax_nopriv_load_elementor_loop_content', 'load_elementor_loop_content' );

// 3) The complete AJAX loader
function load_elementor_loop_content() {
    // -- Gather & sanitize all inputs --
    $post_types         = isset($_POST['post_types'])        ? (array) $_POST['post_types']        : array();
    $selected_post_type = isset($_POST['selected_post_type'])? sanitize_text_field($_POST['selected_post_type']) : '';
    $start_date         = isset($_POST['start_date'])        ? sanitize_text_field($_POST['start_date'])       : '';
    $end_date           = isset($_POST['end_date'])          ? sanitize_text_field($_POST['end_date'])         : '';
    $search             = isset($_POST['search'])            ? sanitize_text_field($_POST['search'])           : '';
    $month              = isset($_POST['month'])             ? sanitize_text_field($_POST['month'])            : '';
    $town               = isset($_POST['town'])              ? sanitize_text_field($_POST['town'])             : '';
    $template_id        = isset($_POST['template_id'])       ? intval($_POST['template_id'])                  : 38859;
    $paged              = isset($_POST['paged'])             ? intval($_POST['paged'])                        : 1;
    $posts_per_page     = 5;

    // -- Build the WP_Query arguments but WITHOUT pagination to get ALL matching posts --
    $today = date('Ymd');
    $args = array(
        'post_type'      => ! empty($selected_post_type) ? $selected_post_type : $post_types,
        'posts_per_page' => -1, // Get ALL posts matching our criteria
        'meta_query'     => array(),
        // We'll do our own sorting, so we don't need these
        // 'orderby'        => 'meta_value',
        // 'meta_key'       => 'datum',
        // 'order'          => 'ASC',
    );

    // always exclude past unless date/month filters are used
    if (
		empty($selected_post_type) &&
		empty($start_date) &&
		empty($end_date) &&
		empty($month) &&
		empty($search) &&
		empty($town)
	) {
		$args['meta_query'][] = array(
			'key'     => 'datum',
			'type'    => 'DATE',
			'value'   => $today,
			'compare' => '>=',
		);
	}


    // text search + ensure future‐only if no explicit dates
    if ( ! empty($search) ) {
        $args['s'] = $search;
        if ( empty($start_date) && empty($end_date) ) {
            $args['meta_query'][] = array(
                'key'     => 'datum',
                'type'    => 'DATE',
                'value'   => $today,
                'compare' => '>=',
            );
        }
    }

    // filter by one post type also implies future
    if ( ! empty($selected_post_type) && empty($start_date) && empty($end_date) ) {
        $args['meta_query'][] = array(
            'key'     => 'datum',
            'type'    => 'DATE',
            'value'   => $today,
            'compare' => '>=',
        );
    }

    // town filter
    if ( ! empty($town) ) {
        $args['meta_query'][] = array(
            'key'     => 'dorpstad',
            'value'   => $town,
            'compare' => '=',
        );
    }

    // month filter, rolling to next year if needed
    if ( ! empty($month) ) {
        $cy  = date('Y');
        $cm  = date('m');
        $yr  = ((int)$month < (int)$cm) ? $cy + 1 : $cy;
        $ms  = "$yr-$month-01";
        $me  = date('Y-m-t', strtotime($ms));
        if ( $yr == $cy && $month == $cm ) {
            $ms = $today;
        }
        $args['meta_query'][] = array(
            'key'     => 'datum',
            'type'    => 'DATE',
            'value'   => array( $ms, $me ),
            'compare' => 'BETWEEN',
        );
    }

    // explicit date‐range override
    if ( ! empty($start_date) || ! empty($end_date) ) {
        $args['meta_query'][] = array(
            'key'     => 'datum',
            'type'    => 'DATE',
            'value'   => array( $start_date, $end_date ),
            'compare' => 'BETWEEN',
        );
    }

    // -- Execute the query to get ALL matching posts --
    $query = new WP_Query( $args );
    $all_posts = $query->posts;
    
    // -- Sort ALL posts by datum --
    $sorted_posts = sort_posts_by_datum( $all_posts );
    
    // -- Now manually handle pagination --
    $total_posts = count($sorted_posts);
    $total_pages = ceil($total_posts / $posts_per_page);
    
    // Make sure paged is valid
    if ($paged < 1) $paged = 1;
    if ($paged > $total_pages && $total_pages > 0) $paged = $total_pages;
    
    // Calculate slice for current page
    $start = ($paged - 1) * $posts_per_page;
    $current_page_posts = array_slice($sorted_posts, $start, $posts_per_page);

    // -- Render the sorted and paginated posts via Elementor template --
    ob_start();
    if ( ! empty( $current_page_posts ) ) {
        global $post;
        foreach ( $current_page_posts as $post ) {
            setup_postdata( $post );
            echo \Elementor\Plugin::instance()
                     ->frontend
                     ->get_builder_content_for_display( $template_id );
        }
        wp_reset_postdata();
    } else {
        echo 'Geen events gevind nie.';
    }
    $content = ob_get_clean();

    // -- Return JSON payload --
    wp_send_json_success( array(
        'content'      => $content,
        'total_pages'  => $total_pages,
        'current_page' => $paged,
    ) );
}

function get_custom_post_type_label($post_type) {
    $labels = array(
        'funksie' => 'Funksies, vroue-oggende …',
        'eksterne-funksie' => 'Vertonings, produksies …',
        'uitdaging' => 'Werkswinkels, praatjies …',
        'reisklub-toer' => 'Reise, toere …',
        'webinar' => 'Aanlyn, uitsendings …',
        'feeste-markte' => 'Feeste, markte ...',
        'sport-gholfdae' => 'Sport, gholfdae ...',
		'lootjies-kompetisies' => 'Lootjies, kompetisies …',
    );

    return isset($labels[$post_type]) ? $labels[$post_type] : ucfirst(str_replace('-', ' ', $post_type));
}

function shortcode_post_type_label() {
    global $post;

    if (!isset($post->ID)) {
        return '';
    }

    $post_type = get_post_type($post->ID);
    return get_custom_post_type_label($post_type);
}
add_shortcode('post_type_label', 'shortcode_post_type_label');

add_action('wp_ajax_load_elementor_loop_content', 'load_elementor_loop_content');
add_action('wp_ajax_nopriv_load_elementor_loop_content', 'load_elementor_loop_content');

