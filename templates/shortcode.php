<?php
$post_types = isset($this) ? $this->post_types : [];
?>
<div class="flex-container">
    <div class="sidebar">
        <div class="filters">
            <h2>EVENTS</h2>
            <h5>NAAM VAN EVENT</h5>
            <input type="text" id="search-bar" placeholder="Naam">
            <h5><br><br>TIPE EVENT</h5>
            <ul id="post-type-filters">
                <?php
                $custom_post_type_names = array(
                    'funksie'            => 'Funksies, vroue-oggende …',
                    'eksterne-funksie'    => 'Vertonings, produksies …',
                    'uitdaging'          => 'Werkswinkels, praatjies …',
                    'reisklub-toer'      => 'Reise, toere …',
                    'webinar'            => 'Aanlyn, uitsendings …',
                    'feeste-markte'      => 'Feeste, markte ...',
                    'sport-gholfdae'     => 'Sport, gholfdae ...',
                    'lootjies-kompetisies'=> 'Lootjies, kompetisies …',
                );
                foreach ($post_types as $type) {
                    $display_name = isset($custom_post_type_names[$type]) ? $custom_post_type_names[$type] : ucfirst(str_replace('-', ' ', $type));
                    echo '<li class="post-type-filter ' . esc_attr($type) . '" data-post-type="' . esc_attr($type) . '">' . esc_html($display_name) . '</li>';
                }
                ?>
            </ul>
            <h5><br><br>DORP</h5>
            <select id="town-filter">
                <option value="">Dorp</option>
            </select>
            <h5><br><br>MAAND</h5>
            <select id="month-filter">
                <option value="">Maand</option>
            </select>
            <h5><br><br>TYDPERK</h5>
            <input type="text" id="start-date" placeholder="Begindatum">
            <input type="text" id="end-date" placeholder="Einddatum">
            <button id="reset-filters">Herstel Filters</button>
            <div id="filter-reset-msg" style="display:none; color: green; margin-top: 10px; font-weight: bold;">Filters herstel ✅</div>
        </div>
    </div>
    <div class="content-and-pagination">
        <div id="elementor-loop-content" class="content">
            <?php
            $no_filters = empty($_GET['search']) && empty($_GET['month']) && empty($_GET['town']) && empty($_GET['start']) && empty($_GET['end']) && empty($_GET['selected_post_type']);
            if ( $no_filters && isset( $this ) ) {
                echo $this->render_events();
            }
            ?>
        </div>
        <div class="pagination-wrapper">
            <div id="pagination-controls" class="pagination-controls">
                <button id="prev-page" disabled>Vorige</button>
                <span id="page-info"></span>
                <button id="next-page">Volgende</button>
            </div>
        </div>
    </div>
</div>
