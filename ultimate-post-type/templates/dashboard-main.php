<?php

$current_user = wp_get_current_user();
$settings = get_query_var('widget_settings', []);
$dashboard_enabled = !isset($settings['enable_dashboard']) || $settings['enable_dashboard'] === 'yes';
$dashboard_preset = isset($settings['dashboard_preset']) ? sanitize_key($settings['dashboard_preset']) : 'saas';
$dashboard_preset = in_array($dashboard_preset, ['saas', 'hostinger'], true) ? $dashboard_preset : 'saas';
$show_all = isset($settings['show_all_items']) && $settings['show_all_items'] === 'yes';
$template_id = isset($settings['item_card_template_id']) ? $settings['item_card_template_id'] : 0;
$card_variant = isset($settings['item_card_variant']) ? $settings['item_card_variant'] : 'modern';
$card_variant = in_array($card_variant, ['modern', 'legacy'], true) ? $card_variant : 'modern';
$enable_pagination = isset($settings['enable_pagination']) && $settings['enable_pagination'] === 'yes';
$items_per_page = $enable_pagination ? (isset($settings['items_per_page']) ? absint($settings['items_per_page']) : 9) : -1;
$pagination_type = 'infinite';
if (isset($settings['pagination_type'])) {
    $candidate = sanitize_key($settings['pagination_type']);
    if (in_array($candidate, ['numbers', 'arrows', 'prev_next', 'infinite'], true)) {
        $pagination_type = $candidate;
    }
}

$enable_gallery_pagination = isset($settings['enable_gallery_pagination']) && $settings['enable_gallery_pagination'] === 'yes';
$gallery_items_per_page = $enable_gallery_pagination ? (isset($settings['gallery_items_per_page']) ? absint($settings['gallery_items_per_page']) : 30) : -1;
$gallery_pagination_type = 'infinite';
if (isset($settings['gallery_pagination_type'])) {
    $candidate = sanitize_key($settings['gallery_pagination_type']);
    if (in_array($candidate, ['numbers', 'arrows', 'prev_next', 'infinite'], true)) {
        $gallery_pagination_type = $candidate;
    }
}
$gallery_numbers_nav = !isset($settings['gallery_pagination_numbers_nav']) || $settings['gallery_pagination_numbers_nav'] === 'yes';
$gallery_pagination_infinite_trigger = 'scroll';
if (isset($settings['gallery_pagination_infinite_trigger'])) {
    $candidate = sanitize_key($settings['gallery_pagination_infinite_trigger']);
    if (in_array($candidate, ['scroll', 'button', 'both'], true)) {
        $gallery_pagination_infinite_trigger = $candidate;
    }
}
$gallery_pagination_load_more_label = isset($settings['gallery_pagination_load_more_label']) && $settings['gallery_pagination_load_more_label'] !== ''
    ? (string)$settings['gallery_pagination_load_more_label']
    : 'Carregar mais';
$pagination_toggle_label = $enable_pagination ? 'Mostrar todos' : 'Mostrar paginado';
$schemas = get_terms(['taxonomy' => 'catalog_schema', 'hide_empty' => false]);
$schema_definitions = class_exists('UPT_Schema_Store') ?UPT_Schema_Store::get_schemas() : [];
$show_schema_counters = !isset($settings['show_schema_counters']) || $settings['show_schema_counters'] === 'yes';

// ---- SaaS Visual Settings ----
$saas_brand_mode = isset($settings['saas_brand_mode']) ? sanitize_key($settings['saas_brand_mode']) : 'logo';
if (!in_array($saas_brand_mode, ['logo', 'text'], true)) {
    $saas_brand_mode = 'logo';
}
$saas_logo_id = !empty($settings['saas_logo_image']['id']) ? absint($settings['saas_logo_image']['id']) : 0;
$saas_logo_url = !empty($settings['saas_logo_image']['url']) ? esc_url($settings['saas_logo_image']['url']) : '';
if (!$saas_logo_url && $saas_logo_id) {
    $saas_logo_url = wp_get_attachment_image_url($saas_logo_id, 'full');
    if ($saas_logo_url) {
        $saas_logo_url = esc_url($saas_logo_url);
    }
}
$saas_logo_width = !empty($settings['saas_logo_width']['size']) ? absint($settings['saas_logo_width']['size']) : 100;
$saas_brand_text = isset($settings['saas_brand_text']) ? sanitize_text_field($settings['saas_brand_text']) : '';
if ($saas_brand_text === '') {
    $saas_brand_text = get_bloginfo('name');
}
$normal_brand_mode = isset($settings['normal_brand_mode']) ? sanitize_key($settings['normal_brand_mode']) : 'none';
if (!in_array($normal_brand_mode, ['none', 'logo', 'text'], true)) {
    $normal_brand_mode = 'none';
}
$normal_logo_id = !empty($settings['normal_logo_image']['id']) ? absint($settings['normal_logo_image']['id']) : 0;
$normal_logo_url = !empty($settings['normal_logo_image']['url']) ? esc_url($settings['normal_logo_image']['url']) : '';
if (!$normal_logo_url && $normal_logo_id) {
    $normal_logo_url = wp_get_attachment_image_url($normal_logo_id, 'full');
    if ($normal_logo_url) {
        $normal_logo_url = esc_url($normal_logo_url);
    }
}
$normal_logo_width = !empty($settings['normal_logo_width']['size']) ? absint($settings['normal_logo_width']['size']) : 120;
$normal_brand_text = isset($settings['normal_brand_text']) ? sanitize_text_field($settings['normal_brand_text']) : '';
if ($normal_brand_text === '') {
    $normal_brand_text = get_bloginfo('name');
}
$saas_sidebar_bg = !empty($settings['saas_sidebar_bg']) ? sanitize_hex_color($settings['saas_sidebar_bg']) : '#111827';
$saas_primary = !empty($settings['saas_primary_color']) ? sanitize_hex_color($settings['saas_primary_color']) : '#6366f1';
$saas_header_bg = !empty($settings['saas_header_bg']) ? sanitize_hex_color($settings['saas_header_bg']) : '#ffffff';
$saas_body_bg = !empty($settings['saas_body_bg']) ? sanitize_hex_color($settings['saas_body_bg']) : '#f1f5f9';
$saas_card_bg = !empty($settings['saas_card_bg']) ? sanitize_hex_color($settings['saas_card_bg']) : '#ffffff';
$saas_sidebar_width = !empty($settings['saas_sidebar_width']['size']) ? absint($settings['saas_sidebar_width']['size']) : 240;
$saas_add_btn_label = !empty($settings['saas_add_btn_label']) ? sanitize_text_field($settings['saas_add_btn_label']) : 'Adicionar Novo Item';

// Fallbacks for empty sanitize_hex_color (returns null for invalid)
if (!$saas_sidebar_bg)
    $saas_sidebar_bg = '#111827';
if (!$saas_primary)
    $saas_primary = '#6366f1';
if (!$saas_header_bg)
    $saas_header_bg = '#ffffff';
if (!$saas_body_bg)
    $saas_body_bg = '#f1f5f9';
if (!$saas_card_bg)
    $saas_card_bg = '#ffffff';

$saas_radius = !empty($settings['saas_border_radius']['size']) ? absint($settings['saas_border_radius']['size']) : 8;


$tabs_schema_media_map = [];
$tabs_schema_media_rows = (isset($settings['tabs_schema_media']) && is_array($settings['tabs_schema_media']))
    ? $settings['tabs_schema_media']
    : [];
foreach ($tabs_schema_media_rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $slug = isset($row['schema_slug']) ? sanitize_key((string)$row['schema_slug']) : '';
    if ($slug === '') {
        continue;
    }
    $tabs_schema_media_map[$slug] = $row;
}

$upt_render_tab_media = function ($type, $icon, $image) {
    $type = is_string($type) ? sanitize_key($type) : 'none';

    if ($type === 'icon') {
        if (class_exists('\\Elementor\\Icons_Manager') && is_array($icon) && !empty($icon['value'])) {
            ob_start();
            \Elementor\Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']);
            $inner = trim((string)ob_get_clean());
            if ($inner !== '') {
                return '<span class="upt-tab-icon" aria-hidden="true">' . $inner . '</span>';
            }
        }
        return '';
    }

    if ($type === 'image') {
        $id = (is_array($image) && isset($image['id'])) ? absint($image['id']) : 0;
        $url = (is_array($image) && isset($image['url'])) ? (string)$image['url'] : '';

        if ($id > 0) {
            $img = wp_get_attachment_image(
                $id,
                'thumbnail',
                false,
            [
                'class' => 'upt-tab-image',
                'alt' => '',
                'aria-hidden' => 'true',
                'draggable' => 'false',
                'loading' => 'lazy',
            ]
            );
            if ($img) {
                return $img;
            }
        }

        $url = trim($url);
        if ($url !== '') {
            return '<img class="upt-tab-image" src="' . esc_url($url) . '" alt="" aria-hidden="true" draggable="false" loading="lazy" />';
        }
    }

    return '';
};

$upt_render_lucide_icon = function ($icon_name, $extra_class = '') {
    $icon_name = is_string($icon_name) ? trim($icon_name) : '';
    if ($icon_name === '') {
        return '';
    }

    $class = 'upt-icon';
    $extra_class = is_string($extra_class) ? trim($extra_class) : '';
    if ($extra_class !== '') {
        $class .= ' ' . $extra_class;
    }

    $fallback_svg = '';
    if ($icon_name === 'lucide-eye') {
        $fallback_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
    }
    elseif ($icon_name === 'lucide-trash-2') {
        $fallback_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>';
    }

    if (!class_exists('\\Elementor\\Icons_Manager')) {
        if ($fallback_svg === '') {
            return '';
        }
        return '<span class="' . esc_attr($class) . '" aria-hidden="true">' . $fallback_svg . '</span>';
    }

    ob_start();
    \Elementor\Icons_Manager::render_icon(
    [
        'value' => $icon_name,
        'library' => 'lucide',
    ],
    ['aria-hidden' => 'true']
    );
    $inner = trim((string)ob_get_clean());
    if ($inner === '') {
        if ($fallback_svg === '') {
            return '';
        }
        $inner = $fallback_svg;
    }

    return '<span class="' . esc_attr($class) . '" aria-hidden="true">' . $inner . '</span>';
};

$schema_items_counts = [];
$forms_items_count = 0;
if ($show_schema_counters && !empty($schemas) && !is_wp_error($schemas)) {
    foreach ($schemas as $schema) {
        $count_query_args = [
            'post_type' => 'catalog_item',
            'post_status' => ['publish', 'pending', 'draft'],
            'tax_query' => [
                [
                    'taxonomy' => 'catalog_schema',
                    'field' => 'slug',
                    'terms' => $schema->slug,
                ],
            ],
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ];

        if (!$show_all || !current_user_can('manage_options')) {
            $count_query_args['author'] = get_current_user_id();
        }

        $count_query = new WP_Query($count_query_args);
        $schema_items_counts[$schema->slug] = (int)$count_query->found_posts;
    }

    wp_reset_postdata();

    if (post_type_exists('4gt_form_submission')) {
        $forms_count_query_args = [
            'post_type' => '4gt_form_submission',
            'post_status' => ['publish', 'pending', 'draft'],
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ];

        if (!$show_all || !current_user_can('manage_options')) {
            $forms_count_query_args['author'] = get_current_user_id();
        }

        $forms_count_query = new WP_Query($forms_count_query_args);
        $forms_items_count = (int)$forms_count_query->found_posts;
        wp_reset_postdata();
    }
}


$upt_help_video = get_option('upt_help_video', ''); // atualmente não utilizado
$upt_help_video_file_id = (int)get_option('upt_help_video_file_id', 0);
$upt_help_video_file_url = '';

if ($upt_help_video_file_id > 0) {
    $upt_help_video_file_url = wp_get_attachment_url($upt_help_video_file_id);

    // Se o anexo não existir mais ou a URL estiver vazia, zera para não exibir botão/modal.
    if (!$upt_help_video_file_url) {
        $upt_help_video_file_id = 0;
        $upt_help_video_file_url = '';
    }
}

$upt_help_label = get_option('upt_help_label', '');
if ($upt_help_label === '') {
    $upt_help_label = __('Ajuda', 'upt');
}

// Apenas vídeos auto-hospedados são considerados.
// Se existir uma URL válida de arquivo, exibimos botão + modal.
$upt_has_help_video = !empty($upt_help_video_file_url);
$enabled_filters = isset($settings['enabled_filters']) && is_array($settings['enabled_filters'])
    ? $settings['enabled_filters']
    : ['search', 'schema', 'category'];

$show_search_filter = in_array('search', $enabled_filters, true);
$show_category_filter = in_array('category', $enabled_filters, true);

$upt_panel_show_subcategories = !isset($settings['panel_category_show_subcategories']) || $settings['panel_category_show_subcategories'] === 'yes';
$upt_panel_show_sub_badge = !isset($settings['panel_category_show_sub_badge']) || $settings['panel_category_show_sub_badge'] === 'yes';
$upt_panel_sub_badge_text = isset($settings['panel_category_sub_badge_text']) ? trim((string)$settings['panel_category_sub_badge_text']) : '';
if ($upt_panel_sub_badge_text === '') {
    $upt_panel_sub_badge_text = 'Sub';
}

$upt_dashboard_days = 90;
$upt_dashboard_today = current_time('Y-m-d');
$upt_dashboard_start = date(
    'Y-m-d',
    strtotime('-' . ($upt_dashboard_days - 1) . ' days', strtotime($upt_dashboard_today))
);

global $wpdb;

// Formulários (CPT 4gt_form_submission)
$upt_forms_daily_map = [];
$upt_forms_total = 0;

if (post_type_exists('4gt_form_submission')) {
    $rows = $wpdb->get_results(
        $wpdb->prepare(
        "SELECT DATE(post_date) AS day, COUNT(*) AS total
             FROM {$wpdb->posts}
             WHERE post_type = %s
               AND post_status = 'publish'
               AND post_date >= %s
             GROUP BY DATE(post_date)
             ORDER BY day ASC",
        '4gt_form_submission',
        $upt_dashboard_start
    ),
        ARRAY_A
    );

    if (!empty($rows)) {
        foreach ($rows as $row) {
            if (empty($row['day'])) {
                continue;
            }
            $upt_forms_daily_map[$row['day']] = (int)$row['total'];
        }
    }

    $upt_forms_total = (int)$wpdb->get_var(
        $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = %s
               AND post_status = 'publish'",
        '4gt_form_submission'
    )
    );
}

if (!function_exists('upt_get_click_stats_generic')) {
    function upt_get_click_stats_generic($short_table_name, $start_date, $days_window = 90)
    {
        global $wpdb;

        $stats = [
            'daily_map' => [],
            'total' => 0,
            'top_items' => [],
        ];

        if (empty($short_table_name)) {
            return $stats;
        }

        $table = $wpdb->prefix . $short_table_name;
        $found = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));

        if ($found !== $table) {
            return $stats;
        }

        // Detecta coluna de data/datetime automaticamente
        $columns = $wpdb->get_results("DESCRIBE {$table}", ARRAY_A);
        $date_column = '';
        $id_column = '';

        if (!empty($columns)) {
            foreach ($columns as $column) {
                $type = isset($column['Type']) ? strtolower($column['Type']) : '';
                $name = isset($column['Field']) ? $column['Field'] : '';

                if (!$date_column && (strpos($type, 'date') !== false || strpos($type, 'time') !== false)) {
                    $date_column = $name;
                }

                if (!$id_column && preg_match('/(post_id|item_id|button_id|image_id|rel_id)/', $name)) {
                    $id_column = $name;
                }
            }
        }

        // Total geral
        $stats['total'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table}");

        // Mapa diário
        if ($date_column) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                "SELECT DATE({$date_column}) AS day, COUNT(*) AS total
                     FROM {$table}
                     WHERE {$date_column} >= %s
                     GROUP BY DATE({$date_column})
                     ORDER BY day ASC",
                $start_date
            ),
                ARRAY_A
            );

            if (!empty($rows)) {
                foreach ($rows as $row) {
                    if (empty($row['day'])) {
                        continue;
                    }
                    $stats['daily_map'][$row['day']] = (int)$row['total'];
                }
            }
        }

        // Itens mais clicados
        if ($id_column) {
            $top_rows = $wpdb->get_results(
                "SELECT {$id_column} AS item_id, COUNT(*) AS total
                 FROM {$table}
                 GROUP BY {$id_column}
                 ORDER BY total DESC
                 LIMIT 5",
                ARRAY_A
            );

            if (!empty($top_rows)) {
                foreach ($top_rows as $row) {
                    $item_id = isset($row['item_id']) ? (int)$row['item_id'] : 0;
                    if (!$item_id) {
                        continue;
                    }
                    $title = get_the_title($item_id);
                    if (!$title) {
                        $title = sprintf('Item #%d', $item_id);
                    }

                    $stats['top_items'][] = [
                        'id' => $item_id,
                        'title' => $title,
                        'total' => (int)$row['total'],
                    ];
                }
            }
        }

        return $stats;
    }
}

if (!function_exists('upt_get_button_click_stats_cpt')) {
    /**
     * Estatísticas de cliques de botões 4GT baseadas no CPT 4gt_button_click.
     * Usa os próprios dados do WordPress (posts e postmeta) sem depender de tabelas customizadas.
     */
    function upt_get_button_click_stats_cpt($start_date, $days_window = 90)
    {
        global $wpdb;

        $stats = [
            'daily_map' => [],
            'total' => 0,
            'top_items' => [],
        ];

        if (!post_type_exists('4gt_button_click')) {
            return $stats;
        }

        // Total geral de cliques em botões
        $stats['total'] = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = '4gt_button_click'
               AND post_status = 'publish'"
        );

        // Mapa diário (post_date do CPT de clique)
        $rows = $wpdb->get_results(
            $wpdb->prepare(
            "SELECT DATE(post_date) AS day, COUNT(*) AS total
                 FROM {$wpdb->posts}
                 WHERE post_type = '4gt_button_click'
                   AND post_status = 'publish'
                   AND post_date >= %s
                 GROUP BY DATE(post_date)
                 ORDER BY day ASC",
            $start_date
        ),
            ARRAY_A
        );

        if (!empty($rows)) {
            foreach ($rows as $row) {
                if (empty($row['day'])) {
                    continue;
                }
                $stats['daily_map'][$row['day']] = (int)$row['total'];
            }
        }

        // Top itens agregados por botão (independente da página), para não perder cliques duplicados do mesmo botão
        $top_rows = $wpdb->get_results(
            $wpdb->prepare(
            "SELECT 
                                        COALESCE(pm_text.meta_value, '')   AS button_text,
                                        COALESCE(pm_link.meta_value, '')   AS button_link,
                                        COALESCE(pm_action.meta_value, '') AS action_type,
                                        MAX(pm_url.meta_value)             AS page_url,
                                        MAX(pm_title.meta_value)           AS page_title,
                                        MAX(pm_message.meta_value)         AS button_message,
                                        COUNT(*)                           AS total
                                 FROM {$wpdb->posts} p
                                 LEFT JOIN {$wpdb->postmeta} pm_url 
                                        ON pm_url.post_id = p.ID 
                                     AND pm_url.meta_key = '_page_url'
                                 LEFT JOIN {$wpdb->postmeta} pm_title 
                                        ON pm_title.post_id = p.ID 
                                     AND pm_title.meta_key = '_page_title'
                                 LEFT JOIN {$wpdb->postmeta} pm_text 
                                        ON pm_text.post_id = p.ID 
                                     AND pm_text.meta_key = '_button_text'
                                 LEFT JOIN {$wpdb->postmeta} pm_action 
                                        ON pm_action.post_id = p.ID 
                                     AND pm_action.meta_key = '_action_type'
                                 LEFT JOIN {$wpdb->postmeta} pm_link 
                                        ON pm_link.post_id = p.ID 
                                     AND pm_link.meta_key = '_button_link'
                                 LEFT JOIN {$wpdb->postmeta} pm_message 
                                        ON pm_message.post_id = p.ID 
                                     AND pm_message.meta_key = '_button_message'
                                 WHERE p.post_type = '4gt_button_click'
                                     AND p.post_status = 'publish'
                                     AND p.post_date >= %s
                                 GROUP BY COALESCE(pm_text.meta_value, ''), COALESCE(pm_link.meta_value, ''), COALESCE(pm_action.meta_value, '')
                                 ORDER BY total DESC
                                 LIMIT 25",
            $start_date
        ),
            ARRAY_A
        );

        if (!empty($top_rows)) {
            foreach ($top_rows as $row) {
                $page_url = isset($row['page_url']) ? $row['page_url'] : '';
                $page_title = isset($row['page_title']) ? $row['page_title'] : '';
                $button_text = isset($row['button_text']) ? $row['button_text'] : '';
                $action_type = isset($row['action_type']) ? $row['action_type'] : '';
                $button_link = isset($row['button_link']) ? $row['button_link'] : '';
                $button_message = isset($row['button_message']) ? $row['button_message'] : '';

                // Título de exibição: prioriza o texto do botão, depois a página ou link
                $display_title = $button_text;
                if (!$display_title && $page_title) {
                    $display_title = $page_title;
                }
                if (!$display_title && $page_url) {
                    $display_title = $page_url;
                }
                if (!$display_title && $button_link) {
                    $display_title = $button_link;
                }
                if (!$display_title) {
                    $display_title = __('Botão sem título', 'upt');
                }

                // Chave única apenas pelo conjunto de dados do botão, independente da página
                $item_hash = md5($button_text . '|' . $button_link . '|' . $action_type);

                $stats['top_items'][] = [
                    'id' => $item_hash,
                    'title' => $display_title,
                    'total' => (int)$row['total'],
                    'page_url' => $page_url,
                    'button_text' => $button_text,
                    'action_type' => $action_type,
                    'button_link' => $button_link,
                    'button_message' => $button_message,
                ];
            }
        }

        return $stats;
    }
}

if (!function_exists('upt_get_image_click_stats_cpt')) {
    /**
     * Estatísticas de cliques de imagens 4GT baseadas no CPT 4gt_image_click.
     * Estrutura semelhante aos cliques de botões 4GT.
     */
    function upt_get_image_click_stats_cpt($start_date, $days_window = 90)
    {
        global $wpdb;

        $stats = [
            'daily_map' => [],
            'total' => 0,
            'top_items' => [],
        ];

        if (!post_type_exists('4gt_image_click')) {
            return $stats;
        }

        // Total geral de cliques em imagens
        $stats['total'] = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = '4gt_image_click'
               AND post_status = 'publish'"
        );

        // Mapa diário (post_date do CPT de clique)
        $rows = $wpdb->get_results(
            $wpdb->prepare(
            "SELECT DATE(post_date) AS day, COUNT(*) AS total
                 FROM {$wpdb->posts}
                 WHERE post_type = '4gt_image_click'
                   AND post_status = 'publish'
                   AND post_date >= %s
                 GROUP BY DATE(post_date)
                 ORDER BY day ASC",
            $start_date
        ),
            ARRAY_A
        );

        if (!empty($rows)) {
            foreach ($rows as $row) {
                if (empty($row['day'])) {
                    continue;
                }
                $stats['daily_map'][$row['day']] = (int)$row['total'];
            }
        }

        // Período de análise (janela) para o TOP
        $window_start = date(
            'Y-m-d',
            strtotime($start_date . ' -' . absint($days_window) . ' days')
        );

        // Itens mais clicados (por página / imagem)
        $top_rows = $wpdb->get_results(
            $wpdb->prepare(
            "SELECT 
                    pm_url.meta_value        AS page_url,
                    MAX(pm_img_title.meta_value) AS image_title,
                    MAX(pm_title.meta_value) AS page_title,
                    MAX(pm_action.meta_value) AS action_type,
                    MAX(pm_link.meta_value)   AS button_link,
                    COUNT(*)                 AS total
                 FROM {$wpdb->posts} p
                 LEFT JOIN {$wpdb->postmeta} pm_url 
                    ON pm_url.post_id = p.ID 
                   AND pm_url.meta_key = '_page_url'
                 LEFT JOIN {$wpdb->postmeta} pm_title 
                    ON pm_title.post_id = p.ID 
                   AND pm_title.meta_key = '_page_title'
                 LEFT JOIN {$wpdb->postmeta} pm_img_title 
                    ON pm_img_title.post_id = p.ID 
                   AND pm_img_title.meta_key = '_button_text'
                 LEFT JOIN {$wpdb->postmeta} pm_action 
                    ON pm_action.post_id = p.ID 
                   AND pm_action.meta_key = '_action_type'
                 LEFT JOIN {$wpdb->postmeta} pm_link 
                    ON pm_link.post_id = p.ID 
                   AND pm_link.meta_key = '_button_link'
                 WHERE p.post_type = '4gt_image_click'
                   AND p.post_status = 'publish'
                   AND p.post_date >= %s
                 GROUP BY pm_url.meta_value, pm_img_title.meta_value
                 ORDER BY total DESC
                 LIMIT 10",
            $window_start
        ),
            ARRAY_A
        );

        if (!empty($top_rows)) {
            foreach ($top_rows as $row) {
                $page_url = isset($row['page_url']) ? $row['page_url'] : '';
                $page_title = isset($row['page_title']) ? $row['page_title'] : '';
                $image_title = isset($row['image_title']) ? $row['image_title'] : '';
                $action_type = isset($row['action_type']) ? $row['action_type'] : '';
                $button_link = isset($row['button_link']) ? $row['button_link'] : '';

                if (!$page_url && !$page_title && !$image_title) {
                    continue;
                }

                // Prioriza o título da imagem; se não houver, usa o título da página e, por fim, a própria URL.
                $display_title = $image_title ? $image_title : $page_title;
                if (!$display_title) {
                    $display_title = $page_url;
                }

                // Chave única por combinação de página + título da imagem
                $item_hash = md5($page_url . '|' . $image_title);

                $stats['top_items'][] = [
                    'id' => $item_hash,
                    'title' => $display_title,
                    'total' => (int)$row['total'],
                    'page_url' => $page_url,
                    'page_title' => $page_title,
                    'image_title' => $image_title,
                    'action_type' => $action_type,
                    'button_link' => $button_link,
                ];
            }
        }

        return $stats;
    }
}

// Cliques em botões (tabela opcional)
$upt_buttons_stats = upt_get_button_click_stats_cpt($upt_dashboard_start, $upt_dashboard_days);

// Cliques em imagens (tabela opcional)
$upt_images_stats = upt_get_image_click_stats_cpt($upt_dashboard_start, $upt_dashboard_days);

// Flags: quais recursos estão disponíveis
$upt_has_forms_cpt = post_type_exists('4gt_form_submission');
$upt_has_forms_cpt = post_type_exists('4gt_form_submission');
$upt_has_buttons_cpt = post_type_exists('4gt_button_click') && !empty($upt_buttons_stats['total']);
$upt_has_images_cpt = post_type_exists('4gt_image_click') && !empty($upt_images_stats['total']);
// Só exibe o dashboard se pelo menos um recurso existir
// e se o controle de dashboard do Elementor estiver ativo
$upt_has_dashboard = (
    $upt_has_forms_cpt
    || $upt_has_buttons_cpt
    || $upt_has_images_cpt);

if (!$dashboard_enabled) {
    $upt_has_dashboard = false;
}

// Construção de labels e séries contínuas
$upt_dashboard_labels = [];
$upt_forms_series = [];
$upt_buttons_series = [];
$upt_images_series = [];

$start_ts = strtotime($upt_dashboard_start);
$end_ts = strtotime($upt_dashboard_today);

for ($ts = $start_ts; $ts <= $end_ts; $ts = strtotime('+1 day', $ts)) {
    $day = date('Y-m-d', $ts);
    $upt_dashboard_labels[] = $day;
    $upt_forms_series[] = isset($upt_forms_daily_map[$day]) ? (int)$upt_forms_daily_map[$day] : 0;
    $upt_buttons_series[] = isset($upt_buttons_stats['daily_map'][$day]) ? (int)$upt_buttons_stats['daily_map'][$day] : 0;
    $upt_images_series[] = isset($upt_images_stats['daily_map'][$day]) ? (int)$upt_images_stats['daily_map'][$day] : 0;
}

$upt_dashboard_labels_json = wp_json_encode($upt_dashboard_labels);
$upt_forms_series_json = wp_json_encode($upt_forms_series);
$upt_buttons_series_json = wp_json_encode($upt_buttons_series);
$upt_images_series_json = wp_json_encode($upt_images_series);


$hide_admin_bar = isset($settings['hide_admin_bar']) && $settings['hide_admin_bar'] === 'yes';
?>

<?php if ($hide_admin_bar && is_user_logged_in() && !is_admin()): ?>
    <style id="upt-hide-admin-bar">
        html { margin-top: 0 !important; }
        #wpadminbar { display: none !important; }
    
/* Dashboard upt (resumo e gráficos) */
.upt-dashboard-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.upt-dashboard-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 16px 18px;
    box-shadow: 0 1px 4px rgba(15,23,42,0.06);
}

.upt-dashboard-card h3 {
    margin: 0 0 6px;
    font-size: 14px;
    font-weight: 600;
    color: #111827;
}

.upt-dashboard-number {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #111827;
}

.upt-dashboard-filters {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    width: fit-content;
}

.upt-dashboard-filters select {
    min-width: 160px;
    width: fit-content;
    padding: 12px 15px;
    border-radius: var(--fc-border-radius, 8px);
    background: var(--fc-body-bg, #f7f7f9);
    border: 1px solid var(--fc-border-color, #E7E7E7);
    font-size: 14px;
    color: var(--fc-text-dark, #231E39);
    font-family: var(--fc-font-family, "Roboto", sans-serif);
    cursor: pointer;
    display: inline-block;
}


.upt-dashboard-filters input[type="date"] {
    min-width: 160px;
    width: fit-content;
    padding: 12px 15px;
    border-radius: var(--fc-border-radius, 8px);
    background: var(--fc-body-bg, #f7f7f9);
    border: 1px solid var(--fc-border-color, #E7E7E7);
    font-size: 14px;
    color: var(--fc-text-dark, #231E39);
    font-family: var(--fc-font-family, "Roboto", sans-serif);
    cursor: pointer;
    display: inline-block;
}

.upt-dashboard-filters select:focus,
.upt-dashboard-filters select:focus-visible,
.upt-dashboard-filters input[type="date"]:focus,
.upt-dashboard-filters input[type="date"]:focus-visible {
    outline: none;
    border-color: var(
        --fc-primary-color,
        var(--e-global-color-primary, #FF3131)
    );
}

.upt-dashboard-filters #upt-dashboard-custom-dates {
    display: flex;
    align-items: center;
    gap: 8px;
}
.upt-dashboard-filters #upt-dashboard-custom-dates label {
    display: flex;
    flex-direction: column;
    font-size: 13px;
    color: #111827;
}

.upt-dashboard-filters select#upt-dashboard-range {
    margin-right:20px;
}

.fc-inline-label {
    font-size:14px;
    color: var(--fc-text-dark, #231E39);
    margin-right:4px;
    display:flex;
    align-items:center;
}
.upt-dashboard-filters input[type="date"] {
    min-width:160px;
    padding:12px 15px;
    border-radius: var(--fc-border-radius, 8px);
    background: var(--fc-body-bg, #f7f7f9);
    border: 1px solid var(--fc-border-color, #E7E7E7);
    font-size:14px;
    color: var(--fc-text-dark, #231E39);
    font-family: var(--fc-font-family, "Roboto", sans-serif);
}
.upt-dashboard-chart-wrapper {
    background: #ffffff;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 1px 4px rgba(15,23,42,0.06);
    margin-bottom: 20px;
}

.upt-dashboard-top-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
}

.upt-dashboard-top-group {
    background: #ffffff;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 1px 4px rgba(15,23,42,0.06);
}

.upt-dashboard-top-group h4 {
    margin: 0 0 10px;
    font-size: 14px;
    font-weight: 600;
}

.upt-dashboard-top-group ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.upt-dashboard-top-group li {
    display: block;
    font-size: 13px;
    padding: 6px 0;
    border-bottom: 1px dashed #e5e7eb;
}

.upt-dashboard-top-group li:last-child {
    border-bottom: none;
}

.upt-top-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}

.upt-top-item-title {
    flex: 1 1 auto;
    margin-right: 8px;
}

.upt-top-item-count {
    white-space: nowrap;
    font-weight: 600;
}

.upt-top-item-meta {
    margin-top: 2px;
    font-size: 11px;
    color: #6B7280;
    word-break: break-all;
}

.upt-top-item-url {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 11px;
}
.upt-detail-button {
    border: none;
    background: transparent;
    padding: 0;
    margin-left: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #6B7280;
    cursor: pointer;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    text-decoration: none;
}

.upt-detail-button:hover {
    background: transparent;
    color: #111827;
    text-decoration: underline;
    transform: none;
    box-shadow: none;
}


@media (max-width: 768px) {
    .upt-dashboard-summary {
        grid-template-columns: 1fr;
    }
}

</style>
<?php
endif; ?>

<!-- Theme isolation CSS for .upt-preset-saas -->
<style id="upt-saas-theme-reset">
/* ===== THEME ISOLATION: Neutralize WordPress theme styles inside the dashboard ===== */

/* Box sizing */
.upt-preset-saas,
.upt-preset-saas *,
.upt-preset-saas *::before,
.upt-preset-saas *::after {
    box-sizing: border-box !important;
}

/* Font */
.upt-preset-saas {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
    font-size: 14px !important;
    line-height: 1.5 !important;
    color: #374151 !important;
}

/* All text children */
.upt-preset-saas p,
.upt-preset-saas span,
.upt-preset-saas label,
.upt-preset-saas small {
    font-family: inherit !important;
    line-height: 1.5 !important;
}

/* Lists: remove theme borders, bullets, paddings */
.upt-preset-saas ul,
.upt-preset-saas ol {
    list-style: none !important;
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
    background: none !important;
    box-shadow: none !important;
}

.upt-preset-saas li {
    list-style: none !important;
    border: none !important;
    padding: 0 !important;
    margin: 0 !important;
    background: none !important;
}

/* Links: strip theme color/decoration/hover effects */
.upt-preset-saas a {
    text-decoration: none !important;
    box-shadow: none !important;
    outline: none !important;
    transition: none;
}

/* Headings: strip theme margins/fonts */
.upt-preset-saas h1,
.upt-preset-saas h2,
.upt-preset-saas h3,
.upt-preset-saas h4,
.upt-preset-saas h5,
.upt-preset-saas h6 {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
    line-height: 1.3 !important;
    font-weight: 600 !important;
    margin-top: 0 !important;
}

/* Buttons: strip WordPress button styles */
.upt-preset-saas button,
.upt-preset-saas .button,
.upt-preset-saas input[type="button"],
.upt-preset-saas input[type="submit"] {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
    font-size: 14px !important;
    line-height: 1 !important;
    cursor: pointer !important;
    vertical-align: middle !important;
}

/* Inputs */
.upt-preset-saas input,
.upt-preset-saas select,
.upt-preset-saas textarea {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
    font-size: 14px !important;
    line-height: 1.5 !important;
    box-shadow: none !important;
}

/* Images */
.upt-preset-saas img {
    max-width: 100%;
    height: auto;
    border: none !important;
    box-shadow: none !important;
}

/* Tables */
.upt-preset-saas table {
    border-collapse: collapse;
    border-spacing: 0;
}

/* ===== END THEME ISOLATION ===== */
</style>

<?php // --- SaaS custom properties (colors/dimensions from widget settings) ---
?>
<style id="upt-saas-custom-props">
.upt-preset-saas {
    --saas-sidebar-w: <?php echo esc_attr($saas_sidebar_width); ?>px !important;
    --saas-primary: <?php echo esc_attr($saas_primary); ?> !important;
    --saas-sidebar-bg: <?php echo esc_attr($saas_sidebar_bg); ?> !important;
    --saas-header-bg: <?php echo esc_attr($saas_header_bg); ?> !important;
    --saas-body-bg: <?php echo esc_attr($saas_body_bg); ?> !important;
    --saas-card-bg: <?php echo esc_attr($saas_card_bg); ?> !important;
    --saas-radius: <?php echo esc_attr($saas_radius); ?>px !important;
    grid-template-columns: var(--saas-sidebar-w) 1fr !important;
}

.upt-preset-saas .upt-saas-sidebar {
    background: var(--saas-sidebar-bg) !important;
}

.upt-preset-saas .upt-saas-brand {
    display: inline-flex !important;
    align-items: center !important;
    gap: 10px !important;
}

.upt-preset-saas .upt-saas-brand span {
    display: inline-flex !important;
    align-items: center !important;
}

.upt-preset-saas .upt-saas-logo {
    background: var(--saas-primary) !important;
}

.upt-preset-saas .upt-saas-nav li a.active,
.upt-preset-saas ul.upt-tabs-nav.upt-saas-nav > li > a.active {
    background: color-mix(in srgb, var(--saas-primary) 15%, transparent) !important;
    box-shadow: inset 3px 0 0 var(--saas-primary) !important;
    color: var(--saas-primary) !important;
}

.upt-preset-saas .upt-dashboard-header {
    background: var(--saas-header-bg) !important;
}

.upt-preset-saas .upt-saas-main {
    background: var(--saas-body-bg) !important;
}

.upt-preset-saas .upt-tabs-content,
.upt-preset-saas .dashboard-card {
    background: var(--saas-card-bg) !important;
    border-radius: var(--saas-radius) !important;
}

.upt-preset-saas .button-primary,
.upt-preset-saas .open-add-modal {
    background: var(--saas-primary) !important;
    border-color: var(--saas-primary) !important;
    border-radius: var(--saas-radius) !important;
}

.upt-preset-saas .dashboard-stat-card {
    border-radius: var(--saas-radius) !important;
}
</style>

<div class="upt-dashboard upt-preset-saas">

    <?php if (!empty($schemas) && !is_wp_error($schemas)): ?>
    <aside class="upt-saas-sidebar">
        <span class="upt-saas-brand">
            <?php
            $brand_mode = $normal_brand_mode !== 'none' ? $normal_brand_mode : $saas_brand_mode;
            $brand_logo_url = $normal_brand_mode !== 'none' ? $normal_logo_url : $saas_logo_url;
            $brand_logo_width = $normal_brand_mode !== 'none' ? $normal_logo_width : $saas_logo_width;
            $brand_text = $normal_brand_mode !== 'none' ? $normal_brand_text : $saas_brand_text;
            ?>
            <?php if ($brand_mode === 'logo' && $brand_logo_url): ?>
                <img src="<?php echo esc_url($brand_logo_url); ?>" alt="<?php echo esc_attr($brand_text); ?>" class="upt-saas-logo-img" style="width:<?php echo esc_attr($brand_logo_width); ?>px; height:auto; display:block; border-radius:0; box-shadow:none; border:none;">
            <?php else: ?>
                <span class="upt-saas-logo"><?php echo esc_html(mb_strtoupper(mb_substr($brand_text, 0, 1))); ?></span>
                <span><?php echo esc_html($brand_text); ?></span>
            <?php endif; ?>
        </span>

        <ul class="upt-tabs-nav upt-saas-nav">
            <?php $first = true;
    foreach ($schemas as $schema): ?>
                <?php
        // Garante que o slug usado no store aceite hífen ou underscore
        $schema_key = $schema->slug;
        if (!isset($schema_definitions[$schema_key])) {
            $alt_key = str_replace('-', '_', $schema_key);
            if (isset($schema_definitions[$alt_key])) {
                $schema_key = $alt_key;
            }
        }

        $items_limit = 0;
        if (isset($schema_definitions[$schema_key]['items_limit'])) {
            $items_limit = absint($schema_definitions[$schema_key]['items_limit']);
        }

        $schema_count = isset($schema_items_counts[$schema->slug]) ? (int)$schema_items_counts[$schema->slug] : 0;
?>

                <li>
                    <a href="#tab-<?php echo esc_attr($schema->slug); ?>"
                       class="<?php echo $first ? 'active' : ''; ?>"
                       data-schema="<?php echo esc_attr($schema->slug); ?>">
                        <?php
        $media_html = '';
        if (isset($tabs_schema_media_map[$schema->slug]) && is_array($tabs_schema_media_map[$schema->slug])) {
            $row = $tabs_schema_media_map[$schema->slug];
            $media_html = $upt_render_tab_media(
                $row['media_type'] ?? 'none',
                $row['icon'] ?? null,
                $row['image'] ?? null
            );
        }
        if ($media_html !== '') {
            echo $media_html;
        }
?>
                        <?php echo esc_html($schema->name); ?>
                        <?php if ($show_schema_counters): ?>
                            <span class="upt-tab-counter" aria-label="<?php echo esc_attr(sprintf('Total de itens em %s', $schema->name)); ?>">
                                <?php echo esc_html($schema_count); ?>
                            </span>
                        <?php
        endif; ?>
                    </a>
                </li>
            <?php $first = false;
    endforeach; ?>

            <?php if ($upt_has_forms_cpt): ?>
                <li class="upt-tab-4gt">
                    <a href="#tab-4gt-submissions"><?php
        $forms_media_html = $upt_render_tab_media(
            $settings['tabs_media_forms_type'] ?? 'none',
            $settings['tabs_media_forms_icon'] ?? null,
            $settings['tabs_media_forms_image'] ?? null
        );
        if ($forms_media_html !== '') {
            echo $forms_media_html;
        }
?>Formulários
                        <?php if ($show_schema_counters): ?>
                            <span class="upt-tab-counter" aria-label="Total de envios de formulários">
                                <?php echo esc_html($forms_items_count); ?>
                            </span>
                        <?php
        endif; ?>
                    </a>
                </li>
            <?php
    endif; ?>

            <?php if ($upt_has_dashboard): ?>
                <li class="upt-tab-dashboard">
                    <a href="#tab-upt-dashboard"><?php
        $dashboard_media_html = $upt_render_tab_media(
            $settings['tabs_media_dashboard_type'] ?? 'none',
            $settings['tabs_media_dashboard_icon'] ?? null,
            $settings['tabs_media_dashboard_image'] ?? null
        );
        if ($dashboard_media_html !== '') {
            echo $dashboard_media_html;
        }
?>Dashboard</a>
                </li>
            <?php
    endif; ?>

            <?php if (current_user_can('manage_options')): ?>
                <li class="upt-tab-imob-import">
                    <a href="#tab-imob-import">
                        <span class="upt-tab-icon" aria-hidden="true">📥</span>
                        Importar XML
                    </a>
                </li>
            <?php endif; ?>

            <?php if (current_user_can('manage_options')): ?>
                <li class="upt-tab-card-settings">
                    <a href="#tab-card-settings">
                        <span class="upt-tab-icon" aria-hidden="true">🎨</span>
                        Cards
                    </a>
                </li>
            <?php endif; ?>

        </ul>
    </aside>
    <?php
endif; ?>

    <main class="upt-saas-main">
        <div class="upt-dashboard-header">
            <div class="welcome-text">
                <?php if ($dashboard_preset !== 'saas' && $normal_brand_mode !== 'none') : ?>
                    <div class="upt-normal-brand">
                        <?php if ($normal_brand_mode === 'logo' && $normal_logo_url) : ?>
                            <img src="<?php echo esc_url($normal_logo_url); ?>" alt="<?php echo esc_attr($normal_brand_text); ?>" class="upt-normal-logo-img" style="width:<?php echo esc_attr($normal_logo_width); ?>px; height:auto; display:block;">
                        <?php else : ?>
                            <span class="upt-normal-logo-text"><?php echo esc_html($normal_brand_text); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <h2>Olá, <?php echo esc_html($current_user->display_name); ?>!</h2>
                <a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>" class="logout-link">Sair</a>
            </div>
            <div class="dashboard-header-actions">
                <a href="#" class="button upt-btn-secondary upt-toggle-pagination"><?php echo esc_html($pagination_toggle_label); ?></a>
                <!-- Bulk delete: toggle -> button group -->
                <a href="#" class="button upt-btn-danger upt-bulk-delete-toggle">Excluir</a>
                <span class="upt-bulk-delete-group" style="display:none">
                    <a href="#" class="button upt-btn-danger upt-bulk-delete-confirm" data-base-label="Excluir">Excluir 0</a>
                    <a href="#" class="button upt-bulk-delete-selectall" aria-label="Selecionar todos" title="Selecionar todos">Tudo</a>
                    <a href="#" class="button upt-btn-danger upt-bulk-delete-cancel" aria-label="Cancelar" title="Cancelar">✕</a>
                </span>
                <a href="#" class="button button-primary open-add-modal"><?php echo esc_html($saas_add_btn_label); ?></a>
            </div>
        </div>

        <div class="upt-dashboard-content">
            <div class="upt-items-container"
                 data-show-all="<?php echo $show_all ? 'yes' : 'no'; ?>"
                 data-pagination="<?php echo $enable_pagination ? 'yes' : 'no'; ?>"
                 data-pagination-type="<?php echo esc_attr($pagination_type); ?>"
                 data-pagination-infinite-trigger="scroll"
                 data-gallery-pagination="<?php echo $enable_gallery_pagination ? 'yes' : 'no'; ?>"
                 data-gallery-pagination-type="<?php echo esc_attr($gallery_pagination_type); ?>"
                 data-gallery-pagination-numbers-nav="<?php echo $gallery_numbers_nav ? 'yes' : 'no'; ?>"
                 data-gallery-pagination-infinite-trigger="<?php echo esc_attr($gallery_pagination_infinite_trigger); ?>"
                 data-gallery-pagination-load-more-label="<?php echo esc_attr($gallery_pagination_load_more_label); ?>"
                 data-gallery-per-page="<?php echo esc_attr($gallery_items_per_page); ?>"
                 data-template-id="<?php echo esc_attr($template_id); ?>"
                 data-card-variant="<?php echo esc_attr($card_variant); ?>"
                 data-per-page="<?php echo esc_attr($items_per_page); ?>">

                <div class="upt-gallery-pagination-style-probe" aria-hidden="true" style="position:absolute;left:-99999px;top:-99999px;width:1px;height:1px;overflow:hidden;opacity:0;pointer-events:none;">
                    <div class="upt-gallery-pagination">
                        <div class="upt-gallery-pagination-note">Página 1 de 10</div>
                        <div class="upt-gallery-pagination-nav">
                            <button type="button" class="button button-secondary upt-gallery-page-prev">Anterior</button>
                            <button type="button" class="button button-secondary upt-gallery-page-prev is-hover">Anterior</button>
                            <div class="upt-gallery-page-numbers">
                                <button type="button" class="button button-secondary upt-gallery-page-number">1</button>
                                <button type="button" class="button button-secondary upt-gallery-page-number is-hover">2</button>
                                <button type="button" class="button button-secondary upt-gallery-page-number is-current">3</button>
                                <span class="upt-gallery-page-dots">…</span>
                            </div>
                            <button type="button" class="button button-secondary upt-gallery-page-next">Próximo</button>
                            <button type="button" class="button button-secondary upt-gallery-page-next is-hover">Próximo</button>
                        </div>
                    </div>
                    <div class="upt-gallery-load-more">
                        <button type="button" class="button button-secondary upt-gallery-load-more-btn">Carregar mais</button>
                        <button type="button" class="button button-secondary upt-gallery-load-more-btn is-hover">Carregar mais</button>
                    </div>
                </div>

                <?php if (!empty($schemas) && !is_wp_error($schemas)): ?>
                    <div class="upt-tabs-wrapper upt-saas-tabs-wrapper">
                        <div class="upt-tabs-content">
                        
                        <!-- ================================ -->
                        <!-- TABS DE PRODUTOS (originais)     -->
                        <!-- ================================ -->
                        <?php $first = true;
    foreach ($schemas as $schema): ?>
                            <?php
        $schema_key = $schema->slug;
        if (!isset($schema_definitions[$schema_key])) {
            $alt_key = str_replace('-', '_', $schema_key);
            if (isset($schema_definitions[$alt_key])) {
                $schema_key = $alt_key;
            }
        }

        $items_limit = 0;
        if (isset($schema_definitions[$schema_key]['items_limit'])) {
            $items_limit = absint($schema_definitions[$schema_key]['items_limit']);
        }
        $items_limit_max_per_category = !empty($schema_definitions[$schema_key]['items_limit_max_per_category']);
        $items_limit_per_category = !empty($schema_definitions[$schema_key]['items_limit_per_category']);
        $schema_has_category_field = false;
        if (isset($schema_definitions[$schema_key]['fields']) && is_array($schema_definitions[$schema_key]['fields'])) {
            foreach ($schema_definitions[$schema_key]['fields'] as $fc_field) {
                if (isset($fc_field['type']) && $fc_field['type'] === 'taxonomy') {
                    $schema_has_category_field = true;
                    break;
                }
            }
        }
?>
                               <div id="tab-<?php echo esc_attr($schema->slug); ?>"
                                   class="upt-tab-pane <?php echo $first ? 'active' : ''; ?>"
                                   data-items-limit="<?php echo esc_attr($items_limit); ?>"
                                   data-items-max-per-cat="<?php echo $items_limit_max_per_category ? 'yes' : 'no'; ?>"
                                   data-items-per-cat="<?php echo $items_limit_per_category ? 'yes' : 'no'; ?>">

                                <?php if ($show_search_filter || $show_category_filter): ?>
                                <div class="upt-filters">
                                    <?php if ($show_search_filter): ?>
                                        <div class="filter-item filter-search">
                                            <input type="search" class="upt-search-filter" placeholder="Pesquisar em <?php echo esc_attr($schema->name); ?>...">
                                        </div>
                                    <?php
            endif; ?>

                                    <?php if ($show_category_filter && $schema_has_category_field): ?>
                                        <div class="filter-item filter-category">
                                            <?php
                $parent_cat_term = get_term_by('slug', $schema->slug, 'catalog_category');
                $parent_cat_id = ($parent_cat_term && !is_wp_error($parent_cat_term)) ? $parent_cat_term->term_id : 0;

                // Categoria filter: allow enabling/disabling subcategories.
                // When enabled, prepend a "Sub" badge text to subcategory options.
                if ($upt_panel_show_subcategories) {

                    if (!class_exists('UPT_Sub_Badge_Walker_CategoryDropdown')) {
                        class UPT_Sub_Badge_Walker_CategoryDropdown extends Walker_CategoryDropdown
                        {
                            public $badge_text = 'Sub';
                            public $show_badge = true;

                            public function start_el(&$output, $category, $depth = 0, $args = array(), $id = 0)
                            {
                                $pad = str_repeat('&nbsp;', $depth * 3);
                                $cat_name = apply_filters('list_cats', $category->name, $category);

                                if ($this->show_badge && $depth > 0) {
                                    $cat_name = $this->badge_text . ' ' . $cat_name;
                                }

                                $output .= "\t<option class=\"level-$depth\" value=\"" . esc_attr($category->term_id) . "\"";
                                if ((string)$category->term_id === (string)$args['selected']) {
                                    $output .= ' selected=\"selected\"';
                                }
                                $output .= '>' . $pad . esc_html($cat_name);
                                if (!empty($args['show_count'])) {
                                    $output .= '&nbsp;&nbsp;(' . number_format_i18n($category->count) . ')';
                                }
                                $output .= "</option>\n";
                            }
                        }
                    }

                    $walker = new UPT_Sub_Badge_Walker_CategoryDropdown();
                    $walker->badge_text = !empty($upt_panel_sub_badge_text) ? $upt_panel_sub_badge_text : 'Sub';
                    $walker->show_badge = (bool)$upt_panel_show_sub_badge;

                    $select_html = wp_dropdown_categories([
                        'taxonomy' => 'catalog_category',
                        'orderby' => 'name',
                        'order' => 'ASC',
                        'name' => 'upt-category-filter',
                        'class' => 'upt-category-filter upt-category-filter-native',
                        'show_option_none' => 'Todas as categorias',
                        'hierarchical' => true,
                        'hide_empty' => 0,
                        'echo' => 0,
                        'child_of' => $parent_cat_id,
                        'walker' => $walker,
                    ]);

                    // Custom dropdown to allow styling the "Sub" badge.
                    echo '<div class="upt-category-filter-wrap">';
                    echo $select_html;
                    echo '<div class="upt-category-filter-custom" data-badge-text="' . esc_attr($walker->badge_text) . '">';
                    echo '  <button type="button" class="upt-category-filter-trigger" aria-haspopup="listbox" aria-expanded="false">' . esc_html__('Todas as categorias', 'upt') . '</button>';
                    echo '  <div class="upt-category-filter-menu" role="listbox" tabindex="-1" hidden>';

                    $upt_terms = get_terms([
                        'taxonomy' => 'catalog_category',
                        'hide_empty' => 0,
                        'child_of' => $parent_cat_id,
                        'orderby' => 'name',
                        'order' => 'ASC',
                    ]);

                    // Flatten terms with depth for indentation.
                    $by_parent = [];
                    if (!is_wp_error($upt_terms) && !empty($upt_terms)) {
                        foreach ($upt_terms as $t) {
                            $p = (int)$t->parent;
                            if (!isset($by_parent[$p])) {
                                $by_parent[$p] = [];
                            }
                            $by_parent[$p][] = $t;
                        }
                    }

                    $stack = [[(int)$parent_cat_id, 0]];
                    // Always include "all" option first.
                    echo '    <div class="upt-cat-option" role="option" data-value="">' . esc_html__('Todas as categorias', 'upt') . '</div>';

                    $render_children = function ($parent, $depth) use (&$render_children, $by_parent, $walker) {
                        if (empty($by_parent[$parent])) {
                            return;
                        }
                        foreach ($by_parent[$parent] as $term) {
                            $name = $term->name;
                            $pad = str_repeat('&nbsp;', $depth * 4);
                            echo '<div class="upt-cat-option" role="option" data-value="' . esc_attr($term->term_id) . '" data-depth="' . esc_attr($depth) . '">';
                            echo $pad;
                            if ($walker->show_badge && $depth > 0) {
                                echo '<span class="upt-sub-badge">' . esc_html($walker->badge_text) . '</span> ';
                            }
                            echo '<span class="upt-cat-name">' . esc_html($name) . '</span>';
                            echo '</div>';
                            $render_children((int)$term->term_id, $depth + 1);
                        }
                    };

                    $render_children((int)$parent_cat_id, 0);

                    echo '  </div>';
                    echo '</div>';
                    echo '</div>';
                }
                else {
                    $terms = get_terms([
                        'taxonomy' => 'catalog_category',
                        'hide_empty' => 0,
                        'parent' => $parent_cat_id,
                        'orderby' => 'name',
                        'order' => 'ASC',
                    ]);

                    echo '<select name="upt-category-filter" class="upt-category-filter">';
                    echo '<option value="">' . esc_html__('Todas as categorias', 'upt') . '</option>';

                    if (!is_wp_error($terms) && !empty($terms)) {
                        foreach ($terms as $t) {
                            echo '<option value="' . esc_attr($t->term_id) . '">' . esc_html($t->name) . '</option>';
                        }
                    }
                    echo '</select>';
                }
?>
                                        </div>
                                    <?php
            endif; ?>
                                </div>
                                <?php
        endif; ?>

                                  <div class="upt-items-grid"
                                      data-schema="<?php echo esc_attr($schema->slug); ?>"
                                      data-allow-reorder="<?php echo current_user_can('manage_options') ? 'yes' : 'no'; ?>">
                                    <?php
        if (class_exists('UPT_Ajax')) {
            $query_args = [
                'posts_per_page' => $items_per_page,
                'pagination_type' => $pagination_type,
                'pagination_infinite_trigger' => 'scroll',
                'tax_query' => [
                    [
                        'taxonomy' => 'catalog_schema',
                        'field' => 'slug',
                        'terms' => $schema->slug,
                    ]
                ]
            ];

            if (!$show_all || !current_user_can('manage_options')) {
                $query_args['author'] = get_current_user_id();
            }

            if ($template_id) {
                $query_args['template_id'] = $template_id;
            }

            $query_args['card_variant'] = $card_variant;

            $initial_data = UPT_Ajax::get_items_list_html($query_args);
            echo $initial_data['html'];
        }
?>
                                </div>

                                <div class="upt-pagination-wrapper">
                                    <?php if (isset($initial_data['pagination_html']))
            echo $initial_data['pagination_html']; ?>
                                </div>
                            </div>
                        <?php $first = false;
    endforeach; ?>



                        <!-- ========================================== -->
                        <!--              ABA FORMULÁRIOS               -->
                        <!-- ========================================== -->
                        <?php if (post_type_exists('4gt_form_submission')): ?>
                            <div id="tab-4gt-submissions" class="upt-tab-pane">
                                <div class="upt-submissions-header">
                                    <h3>Envios de formulários</h3>
                                    <p>Aqui você vê os envios feitos pelos formulários 4GT-Form.</p>
                                </div>

                                <div class="upt-dashboard-filters upt-forms-filters">
                                    <label for="upt-forms-range">Período:</label>
<select id="upt-forms-range">
    <option value="7">Últimos 7 dias</option>
    <option value="30" selected>Últimos 30 dias</option>
    <option value="90">Últimos 90 dias</option>
    <option value="custom">Período personalizado</option>
</select>

<div id="upt-forms-custom-dates" style="display:none;">
    <label class="fc-inline-label" for="upt-forms-date-from">De:</label>
    <input type="date" id="upt-forms-date-from">
    <label class="fc-inline-label" for="upt-forms-date-to" style="margin-left:12px;">Até:</label>
    <input type="date" id="upt-forms-date-to">
</div>

                                </div>

                                <div class="upt-items-grid upt-submissions-grid">
                                    <?php
        $submissions_query = new WP_Query([
            'post_type' => '4gt_form_submission',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if ($submissions_query->have_posts()):
            while ($submissions_query->have_posts()):
                $submissions_query->the_post();

                $id = get_the_ID();
                $date = get_the_date('d/m/Y H:i');
                $timestamp = get_post_time('U');
                $title = get_the_title();
                $page_title = get_post_meta($id, '_page_title', true);
                $page_url = get_post_meta($id, '_page_url', true);
                $user_ip = get_post_meta($id, '_user_ip', true);
                $submission_html = get_post_meta($id, '_submission_html', true);
                $raw = get_post_meta($id, '_raw_fields', true) ?: [];

                // detectar áudio
                $raw_meta = get_post_meta($id, '_raw_fields', true);
                $raw = is_array($raw_meta) ? $raw_meta : (array)$raw_meta;

                // achata o array de campos brutos para evitar "Array to string conversion"
                $raw_flat = [];
                foreach ($raw as $value) {
                    if (is_array($value)) {
                        foreach ($value as $sub_value) {
                            if (is_scalar($sub_value)) {
                                $raw_flat[] = (string)$sub_value;
                            }
                        }
                    }
                    elseif (is_scalar($value)) {
                        $raw_flat[] = (string)$value;
                    }
                }

                $audio_source = wp_strip_all_tags(
                    $submission_html . ' ' . implode(' ', $raw_flat)
                );
                preg_match_all('#https?://[^\s"\']+\.(mp3|wav|ogg|m4a|webm)#i', $audio_source, $m);
                $audio_files = $m[0] ?? [];
?>

                                            <!-- CARD DE ENVIO -->
                                            <div class="upt-item-card upt-item-card--submission" data-submission-timestamp="<?php echo esc_attr($timestamp); ?>">

                                                <div class="card-content">
                                                    <h4 class="card-title"><?php echo esc_html($title); ?></h4>
                                                    <div class="card-meta">
                                                        <span><?php echo esc_html($date); ?></span>
                                                        <?php if ($page_title): ?>
                                                            <span> · <?php echo esc_html($page_title); ?></span>
                                                        <?php
                endif; ?>
                                                    </div>
                                                </div>

                                                <div class="card-actions upt-submission-actions">
                                                    <a href="#" class="upt-card-btn upt-submission-action upt-submission-action--view open-submission-modal" data-submission-id="<?php echo $id; ?>" title="Ver detalhes" aria-label="Ver detalhes">
                                                        <span class="upt-card-btn__icon" aria-hidden="true">
                                                            <?php echo $upt_render_lucide_icon('lucide-eye', 'upt-action-icon upt-action-icon--view'); ?>
                                                        </span>
                                                    </a>
                                                    <a href="<?php echo get_delete_post_link($id); ?>" class="upt-card-btn upt-card-btn--danger upt-submission-action upt-submission-action--delete" title="Excluir" aria-label="Excluir">
                                                    </a>
                                                </div>
                                            </div>


                                            <!-- DETALHES ESCONDIDOS PARA O MODAL -->
                                            <div id="upt-submission-details-<?php echo $id; ?>" style="display:none;">
                                                <div class="upt-submission-details-inner">

                                                    <h2>Detalhes do envio</h2>

                                                    <p><strong>Data:</strong> <?php echo esc_html($date); ?></p>

                                                    <?php if ($page_title || $page_url): ?>
                                                        <p>
                                                            <strong>Origem:</strong> <?php echo esc_html($page_title); ?>
                                                            <?php if ($page_url): ?>
                                                                · <a href="<?php echo esc_url($page_url); ?>" target="_blank">Ver página</a>
                                                            <?php
                    endif; ?>
                                                        </p>
                                                    <?php
                endif; ?>

                                                    <?php if ($user_ip): ?>
                                                        <p><strong>IP:</strong> <?php echo esc_html($user_ip); ?></p>
                                                    <?php
                endif; ?>




                                                    <h3>Conteúdo do formulário</h3>
                                                    <div class="upt-submission-html">
                                                        <?php
                // transforma links para arquivos de áudio em player + botão de download
                $display_html = $submission_html;

                if (!empty($display_html)) {
                    $display_html = preg_replace_callback(
                        '#<a[^>]+href=[\'"]([^\'"]+\.(?:mp3|wav|ogg|m4a|webm)[^\'"]*)[\'"][^>]*>.*?</a>#i',
                        function ($m) {
                        $url = esc_url($m[1]);
                        return '
    <div class="upt-audio-inline">
        <audio controls preload="metadata" src="' . $url . '"></audio>
        <a class="upt-audio-download button button-small" href="' . $url . '" download>Baixar áudio</a>
    </div>
';
                    },
                        $display_html
                    );
                }

                echo $display_html;
?>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php
            endwhile;
            wp_reset_postdata();
        else:
            echo '<p>Nenhum envio encontrado.</p>';
        endif;
?>
                                </div>
                            </div>
                        <?php
    endif; ?>
                        <!-- ========================================== -->
                        <!--               ABA DASHBOARD                -->
                        <!-- ========================================== -->
                        <?php if ($upt_has_dashboard): ?>
<div id="tab-upt-dashboard" class="upt-tab-pane">
                            <div class="upt-dashboard-summary">
                                <?php if ($upt_has_forms_cpt): ?>
                                <div class="upt-dashboard-card">
                                    <h3>Formulários recebidos</h3>
                                    <p class="upt-dashboard-number"><?php echo intval($upt_forms_total); ?></p>
                                </div>
                                <?php
        endif; ?>

                                <?php if ($upt_has_buttons_cpt): ?>
                                <div class="upt-dashboard-card">
                                    <h3>Cliques em botões</h3>
                                    <p class="upt-dashboard-number"><?php echo isset($upt_buttons_stats['total']) ? intval($upt_buttons_stats['total']) : 0; ?></p>
                                </div>
                                <?php
        endif; ?>

                                <?php if ($upt_has_images_cpt): ?>
                                <div class="upt-dashboard-card">
                                    <h3>Cliques em imagens</h3>
                                    <p class="upt-dashboard-number"><?php echo isset($upt_images_stats['total']) ? intval($upt_images_stats['total']) : 0; ?></p>
                                </div>
                                <?php
        endif; ?>
                            </div>

                            <div class="upt-dashboard-filters">
                                <label for="upt-dashboard-range">Período:</label>
<select id="upt-dashboard-range">
    <option value="7">Últimos 7 dias</option>
    <option value="30" selected>Últimos 30 dias</option>
    <option value="90">Últimos 90 dias</option>
    <option value="custom">Período personalizado</option>
</select>

<div id="upt-dashboard-custom-dates" style="display:none;">
    <label class="fc-inline-label" for="upt-dashboard-date-from">De:</label>
    <input type="date" id="upt-dashboard-date-from">
    <label class="fc-inline-label" for="upt-dashboard-date-to" style="margin-left:12px;">Até:</label>
    <input type="date" id="upt-dashboard-date-to">
</div>

                            </div>

                            <div class="upt-dashboard-chart-wrapper">
                                <canvas id="upt-dashboard-chart" height="160"></canvas>
                            </div>

                            <div class="upt-dashboard-top-items">
                                <?php if ($upt_has_buttons_cpt): ?>
<div class="upt-dashboard-top-group">
                                    <h4>Botões mais clicados</h4>
                                    <?php if (!empty($upt_buttons_stats['top_items'])): ?>
                                        <?php $upt_buttons_seen = []; ?>
                                        <?php $upt_button_detail_index = 0; ?>
                                        <ul>
                                            <?php foreach ($upt_buttons_stats['top_items'] as $item): ?>
                                                <?php
                    $item_id = isset($item['id']) ? (string)$item['id'] : '';
                    $item_key = $item_id !== '' ? $item_id : (isset($item['title']) ? (string)$item['title'] : '');
                    $button_text = isset($item['button_text']) ? $item['button_text'] : '';
                    $item_title = $button_text !== '' ? $button_text : (isset($item['title']) ? $item['title'] : '');
                    $item_url = isset($item['page_url']) ? $item['page_url'] : $item_id;
                    $action_type = isset($item['action_type']) ? $item['action_type'] : '';
                    $button_link = isset($item['button_link']) ? $item['button_link'] : '';
                    $button_message = isset($item['button_message']) ? $item['button_message'] : '';

                    if ($item_key === '') {
                        continue;
                    }
                    if (in_array($item_key, $upt_buttons_seen, true)) {
                        continue;
                    }
                    $upt_buttons_seen[] = $item_key;

                    $detail_id = 'upt-button-detail-' . $upt_button_detail_index;
                    $upt_button_detail_index++;
?>
                                                <li>
                                                    <div class="upt-top-item-row">
                                                        <span class="upt-top-item-title"><?php echo esc_html($item_title); ?></span>
                                                        <span class="upt-top-item-count"><?php echo intval($item['total']); ?> cliques</span>
                                                        <button type="button"
                                                                class="upt-detail-button upt-open-button-detail"
                                                                data-detail-id="<?php echo esc_attr($detail_id); ?>">
                                                            Ver detalhes
                                                        </button>
                                                    </div>
                                                    <div id="<?php echo esc_attr($detail_id); ?>" class="upt-button-detail" style="display:none;">
                                                        <h4><?php echo esc_html($item_title); ?></h4>
                                                        <?php if (!empty($button_text)): ?>
                                                            <p><strong>Texto do botão:</strong> <?php echo esc_html($button_text); ?></p>
                                                        <?php
                    endif; ?>
                                                        <?php if (!empty($action_type)): ?>
                                                            <p><strong>Tipo de ação:</strong> <?php echo esc_html(ucfirst($action_type)); ?></p>
                                                        <?php
                    endif; ?>
                                                        <?php if (!empty($button_link)): ?>
                                                            <p><strong>Link de destino (completo):</strong><br>
                                                                <code class="upt-top-item-url"><?php echo esc_html($button_link); ?></code>
                                                            </p>
                                                        <?php
                    endif; ?>
                                                        <?php if (!empty($item_url)): ?>
                                                            <p><strong>Página / URL de origem:</strong><br>
                                                                <code class="upt-top-item-url"><?php echo esc_html($item_url); ?></code>
                                                            </p>
                                                        <?php
                    endif; ?>
                                                        <?php if (!empty($button_message)): ?>
                                                            <p><strong>Mensagem formatada:</strong><br>
                                                                <?php echo nl2br(esc_html($button_message)); ?>
                                                            </p>
                                                        <?php
                    endif; ?>
                                                    </div>
                                                </li>
                                            <?php
                endforeach; ?>
                                        </ul>
                                    <?php
            else: ?>
                                        <p>Nenhum clique registrado ainda para botões.</p>
                                    <?php
            endif; ?>
                                </div>
<?php
        endif; ?>


                                <?php if ($upt_has_images_cpt): ?>
<div class="upt-dashboard-top-group">
                                    <h4>Imagens mais clicadas</h4>
                                    <?php if (!empty($upt_images_stats['top_items'])): ?>
                                        <?php $upt_images_seen = []; ?>
                                        <?php $upt_image_detail_index = 0; ?>
                                        <ul>
                                            <?php foreach ($upt_images_stats['top_items'] as $item): ?>
                                                <?php
                    $item_id = isset($item['id']) ? (string)$item['id'] : '';
                    $item_key = $item_id !== '' ? $item_id : (isset($item['title']) ? (string)$item['title'] : '');
                    $item_title = isset($item['title']) ? $item['title'] : '';
                    $page_url = isset($item['page_url']) ? $item['page_url'] : $item_id;
                    $action_type = isset($item['action_type']) ? $item['action_type'] : '';
                    $button_link = isset($item['button_link']) ? $item['button_link'] : '';

                    if ($item_key === '') {
                        continue;
                    }
                    if (in_array($item_key, $upt_images_seen, true)) {
                        continue;
                    }
                    $upt_images_seen[] = $item_key;

                    $detail_id = 'upt-image-detail-' . $upt_image_detail_index;
                    $upt_image_detail_index++;
?>
                                                <li>
                                                    <div class="upt-top-item-row">
                                                        <span class="upt-top-item-title"><?php echo esc_html($item_title); ?></span>
                                                        <span class="upt-top-item-count"><?php echo intval($item['total']); ?> cliques</span>
                                                        <button type="button"
                                                                class="upt-detail-button upt-open-button-detail"
                                                                data-detail-id="<?php echo esc_attr($detail_id); ?>">
                                                            Ver detalhes
                                                        </button>
                                                    </div>

                                                    <div id="<?php echo esc_attr($detail_id); ?>" class="upt-button-detail" style="display:none;">
                                                        <h4><?php echo esc_html($item_title); ?></h4>

                                                        <?php if (!empty($action_type)): ?>
                                                            <p><strong>Tipo de ação:</strong> <?php echo esc_html(ucfirst($action_type)); ?></p>
                                                        <?php
                    endif; ?>

                                                        <?php if (!empty($button_link)): ?>
                                                            <p><strong>Link de destino (completo):</strong><br>
                                                                <code class="upt-top-item-url"><?php echo esc_html($button_link); ?></code>
                                                            </p>
                                                        <?php
                    endif; ?>

                                                        <?php if (!empty($page_url)): ?>
                                                            <p><strong>Página / URL de origem:</strong><br>
                                                                <code class="upt-top-item-url"><?php echo esc_html($page_url); ?></code>
                                                            </p>
                                                        <?php
                    endif; ?>

                                                        <p><strong>Total de cliques no período:</strong>
                                                            <?php echo intval($item['total']); ?>
                                                        </p>
                                                    </div>
                                                </li>
                                            <?php
                endforeach; ?>
                                        </ul>
                                    <?php
            else: ?>
                                        <p>Nenhuma imagem clicada no período selecionado.</p>
                                    <?php
            endif; ?>
                                </div>
<?php
        endif; ?>

                            </div>
                        </div>
<?php
    endif; ?>

                    <?php if (current_user_can('manage_options')): ?>
                    <div id="tab-imob-import" class="upt-tab-pane">
                        <div style="max-width:600px;">
                            <h3 style="margin:0 0 4px;">Importar XML de Imobiliária</h3>
                            <p style="color:#6b7280;margin:0 0 20px;font-size:13px;">Importe imóveis de um XML no formato OKE / Zap / Viva Real. As imagens são baixadas automaticamente.</p>

                            <div id="upt-imob-upload-section">
                                <div style="margin-bottom:16px;">
                                    <label style="display:block;font-weight:600;margin-bottom:4px;">Esquema:</label>
                                    <select id="upt-imob-schema-mode" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
                                        <option value="new">Criar novo esquema</option>
                                        <option value="existing">Usar esquema existente</option>
                                    </select>
                                </div>
                                <div id="upt-imob-new-schema-field" style="margin-bottom:16px;">
                                    <label style="display:block;font-weight:600;margin-bottom:4px;">Nome do esquema:</label>
                                    <input type="text" id="upt-imob-schema-name" value="Imóveis" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box;">
                                </div>
                                <div id="upt-imob-existing-schema-field" style="margin-bottom:16px;display:none;">
                                    <label style="display:block;font-weight:600;margin-bottom:4px;">Esquema:</label>
                                    <select id="upt-imob-schema-existing" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
                                        <option value="">— Selecione —</option>
                                        <?php if (!empty($schemas) && !is_wp_error($schemas)): foreach ($schemas as $s): ?>
                                            <option value="<?php echo esc_attr($s->slug); ?>"><?php echo esc_html($s->name); ?></option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                </div>
                                <div style="margin-bottom:16px;">
                                    <label style="display:block;font-weight:600;margin-bottom:4px;">Arquivo XML:</label>
                                    <input type="file" id="upt-imob-xml-file" accept=".xml,text/xml,application/xml" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box;">
                                </div>
                                <button type="button" id="upt-imob-start-btn" class="button button-primary" style="width:100%;padding:10px;">Enviar e Iniciar Importação</button>
                            </div>

                            <div id="upt-imob-progress-section" style="display:none;">
                                <div style="background:#e2e8f0;border-radius:8px;overflow:hidden;height:28px;position:relative;margin:16px 0 10px;">
                                    <div id="upt-imob-progress-bar" style="background:#6366f1;height:100%;width:0%;transition:width 0.4s ease;border-radius:8px;"></div>
                                    <span id="upt-imob-progress-text" style="position:absolute;top:0;left:0;right:0;text-align:center;line-height:28px;color:#fff;font-size:13px;font-weight:600;text-shadow:0 1px 2px rgba(0,0,0,0.2);">0%</span>
                                </div>
                                <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:12px;font-size:13px;color:#475569;">
                                    <span>Total: <strong id="upt-imob-stat-total">—</strong></span>
                                    <span>Importados: <strong id="upt-imob-stat-imported" style="color:#16a34a;">0</strong></span>
                                    <span>Fotos: <strong id="upt-imob-stat-photos" style="color:#2563eb;">0</strong></span>
                                    <span>Erros: <strong id="upt-imob-stat-errors" style="color:#dc2626;">0</strong></span>
                                </div>
                                <div id="upt-imob-status-msg" style="padding:10px 14px;border-radius:6px;background:#f1f5f9;margin-bottom:12px;font-size:13px;">Preparando...</div>
                                <button type="button" id="upt-imob-cancel-btn" class="button" style="color:#dc2626;border-color:#dc2626;">Cancelar</button>
                            </div>

                            <div id="upt-imob-done-section" style="display:none;">
                                <div style="padding:16px;border-radius:8px;background:#f0fdf4;border:1px solid #bbf7d0;margin:12px 0;">
                                    <p style="margin:0 0 6px;font-weight:600;color:#16a34a;">Importação concluída!</p>
                                    <p id="upt-imob-done-stats" style="margin:0;color:#475569;font-size:13px;"></p>
                                </div>
                            <div style="margin-top:32px;padding-top:24px;border-top:1px solid #e5e7eb;">
                                <h4 style="margin:0 0 4px;font-size:15px;">Importação Automática (CRON)</h4>
                                <p style="color:#6b7280;font-size:12px;margin:0 0 16px;">Configure uma URL de webservice para importar automaticamente. Requer ping externo (VPS, cron-job.org) ou visitas regulares ao site.</p>

                                <?php
                                $upt_cron_config = get_option('upt_imob_cron_config', []);
                                $upt_cron_url = isset($upt_cron_config['url']) ? esc_url($upt_cron_config['url']) : '';
                                $upt_cron_schema = isset($upt_cron_config['schema']) ? esc_attr($upt_cron_config['schema']) : '';
                                $upt_cron_freq = isset($upt_cron_config['frequency']) ? esc_attr($upt_cron_config['frequency']) : 'sixhourly';
                                $upt_cron_active = isset($upt_cron_config['active']) ? (bool)$upt_cron_config['active'] : false;
                                $upt_cron_last = isset($upt_cron_config['last_run']) ? esc_html($upt_cron_config['last_run']) : 'Nunca';
                                $upt_cron_next = wp_next_scheduled('upt_imob_cron_import');
                                $upt_cron_next_fmt = $upt_cron_next ? esc_html(date('d/m/Y H:i', $upt_cron_next)) : 'Não agendado';
                                $upt_cron_stats = get_option('upt_imob_cron_stats', ['total' => 0, 'imported' => 0, 'errors' => 0]);
                                ?>

                                <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
                                    <div style="flex:1;min-width:250px;">
                                        <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">URL do Webservice:</label>
                                        <input type="url" id="upt-cron-url" value="<?php echo $upt_cron_url; ?>" placeholder="https://okeimoveis.com.br/gestao/webservices/..." style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box;font-size:13px;">
                                    </div>
                                    <div style="min-width:160px;">
                                        <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Esquema:</label>
                                        <select id="upt-cron-schema" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box;font-size:13px;">
                                            <?php if (!empty($schemas) && !is_wp_error($schemas)): foreach ($schemas as $s): ?>
                                                <option value="<?php echo esc_attr($s->slug); ?>" <?php selected($upt_cron_schema, $s->slug); ?>><?php echo esc_html($s->name); ?></option>
                                            <?php endforeach; endif; ?>
                                        </select>
                                    </div>
                                    <div style="min-width:160px;">
                                        <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Frequência:</label>
                                        <select id="upt-cron-freq" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box;font-size:13px;">
                                            <option value="hourly" <?php selected($upt_cron_freq, 'hourly'); ?>>A cada 1 hora</option>
                                            <option value="twicedaily" <?php selected($upt_cron_freq, 'twicedaily'); ?>>A cada 12 horas</option>
                                            <option value="sixhourly" <?php selected($upt_cron_freq, 'sixhourly'); ?>>A cada 6 horas</option>
                                            <option value="daily" <?php selected($upt_cron_freq, 'daily'); ?>>Diariamente</option>
                                        </select>
                                    </div>
                                </div>

                                <div style="display:flex;gap:8px;margin-bottom:16px;">
                                    <button type="button" id="upt-cron-save" class="button button-primary" style="font-size:13px;">Salvar e <?php echo $upt_cron_active ? 'Desativar' : 'Ativar'; ?></button>
                                    <button type="button" id="upt-cron-test" class="button" style="font-size:13px;">Testar Agora</button>
                                </div>

                                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;font-size:12px;color:#475569;">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 16px;">
                                        <span>Status: <strong id="upt-cron-status-label" style="color:<?php echo $upt_cron_active ? '#16a34a' : '#dc2626'; ?>;"><?php echo $upt_cron_active ? 'Ativo' : 'Inativo'; ?></strong></span>
                                        <span>Última execução: <strong><?php echo $upt_cron_last; ?></strong></span>
                                        <span>Próxima execução: <strong><?php echo $upt_cron_next_fmt; ?></strong></span>
                                        <span>Total importados: <strong><?php echo (int)$upt_cron_stats['imported']; ?></strong></span>
                                    </div>
                                </div>
                            </div>

                                <button type="button" id="upt-imob-new-btn" class="button">Importar Outro XML</button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (current_user_can('manage_options')): ?>
                    <div id="tab-card-settings" class="upt-tab-pane">
                        <div style="max-width:700px;">
                            <h3 style="margin:0 0 4px;">Aparência dos Cards</h3>
                            <p style="color:#6b7280;margin:0 0 24px;font-size:13px;">Escolha quais campos aparecem nos cards.</p>

                            <div style="margin-bottom:32px;">
                                <h4 style="margin:0 0 12px;font-size:15px;">Cards do Dashboard</h4>
                                <p style="color:#6b7280;font-size:12px;margin:0 0 8px;">Campos exibidos nos cards da aba Dashboard e Listagem.</p>
                                <?php
                                $upt_card_dashboard_fields = get_option('upt_card_dashboard_fields', ['title','price','status']);
                                $upt_dashboard_field_options = [
                                    'title'   => 'Título do imóvel',
                                    'price'   => 'Preço (venda ou aluguel)',
                                    'status'  => 'Badge de status (publicado/pendente)',
                                    'category'=> 'Badge de categoria',
                                ];
                                foreach ($upt_dashboard_field_options as $opt_val => $opt_label):
                                ?>
                                <label style="display:flex;align-items:center;gap:8px;margin-bottom:6px;cursor:pointer;font-size:14px;">
                                    <input type="checkbox" name="upt_card_dashboard_fields[]" value="<?php echo esc_attr($opt_val); ?>" <?php checked(in_array($opt_val, $upt_card_dashboard_fields)); ?>>
                                    <?php echo esc_html($opt_label); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>

                            <div style="margin-bottom:32px;">
                                <h4 style="margin:0 0 12px;font-size:15px;">Campos do Card do Site</h4>
                                <p style="color:#6b7280;font-size:12px;margin:0 0 8px;">Estes campos serão adicionados automaticamente ao card padrão do Listing Widget (Elementor) caso ele esteja com a estrutura padrão (não customizada).</p>
                                <?php
                                $upt_card_site_fields = get_option('upt_card_site_fields', []);
                                $all_schemas_defs = UPT_Schema_Store::get_schemas();
                                $upt_site_field_choices = [];
                                foreach ($all_schemas_defs as $sk => $sd) {
                                    if (!empty($sd['fields']) && is_array($sd['fields'])) {
                                        foreach ($sd['fields'] as $f) {
                                            if (empty($f['id']) || empty($f['label'])) continue;
                                            $upt_site_field_choices[$f['id']] = $f['label'] . ' (' . $sk . ')';
                                        }
                                    }
                                }
                                $upt_site_field_choices['core_title'] = 'Título';
                                $upt_site_field_choices['core_featured_image'] = 'Imagem Destacada';
                                $upt_site_field_choices['core_category'] = 'Categoria';
                                asort($upt_site_field_choices);
                                foreach ($upt_site_field_choices as $f_id => $f_label):
                                ?>
                                <label style="display:flex;align-items:center;gap:8px;margin-bottom:6px;cursor:pointer;font-size:14px;">
                                    <input type="checkbox" name="upt_card_site_fields[]" value="<?php echo esc_attr($f_id); ?>" <?php checked(in_array($f_id, $upt_card_site_fields)); ?>>
                                    <?php echo esc_html($f_label); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>

                            <button type="button" id="upt-save-card-settings" class="button button-primary">Salvar Configurações</button>
                            <span id="upt-card-settings-saved" style="margin-left:12px;color:#16a34a;font-weight:600;display:none;">Salvo!</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    </div>

                </div>
            <?php
else: ?>
                <h3>Os meus itens</h3>
                <p>Nenhum esquema criado ainda.</p>
            <?php
endif; ?>

        </div>
    </div>
    </main>
</div>


<!-- MODAL -->
<div id="upt-modal-wrapper">
    <div id="upt-modal-content">
        <a href="#" id="upt-modal-close" class="upt-modal-close-button">&times;</a>
        <p style="text-align:center;">Carregando...</p>
    </div>
</div>



<?php if (!empty($upt_has_help_video)): ?>
<!-- MODAL DE AJUDA -->
<div id="upt-help-modal" class="upt-help-modal" aria-hidden="true">
    <div class="upt-help-modal-overlay"></div>
    <div class="upt-help-modal-inner" role="dialog" aria-modal="true" aria-label="Vídeo de ajuda">
        <button type="button" class="upt-help-close" aria-label="<?php esc_attr_e('Fechar vídeo de ajuda', 'upt'); ?>">&times;</button>
        
        
        <div class="upt-help-video-container">
            <?php if (!empty($upt_help_video_file_url)): ?>
                <video
                    src="<?php echo esc_url($upt_help_video_file_url); ?>"
                    controls
                    playsinline
                ></video>
            <?php
    endif; ?>
        </div>


    </div>
</div>
<?php
endif; ?>


<!-- ========================= -->
<!--       CSS CUSTOMIZADO     -->
<!-- ========================= -->

<style>
/* Ícones (ver detalhes / excluir) */
.upt-submissions-grid .upt-action-icon svg,
.upt-submissions-grid .upt-action-icon i {
    width: 18px;
    height: 18px;
    font-size: 18px;
}

.upt-submissions-grid .upt-action-icon--view { color: #A6A6A6 !important; }
.upt-submissions-grid .upt-action-icon--delete { color: #e53935 !important; }

/* Player de áudio (seção Áudios anexados) */
.upt-submission-audio audio {
    width: 100%;
    max-width: 360px;
    margin: 6px 0;
}

/* Player + botão dentro da tabela do formulário */
.upt-submission-html .upt-audio-inline {
    display: flex;
    align-items: center;
    gap: 12px;
}

.upt-submission-html .upt-audio-inline audio {
    max-width: 260px;
    width: 100%;
}

.upt-submission-html .upt-audio-download {
    font-size: 13px;
}


/* Modal de ajuda – alinhado ao estilo dos cards do painel */
.upt-help-modal {
    position: fixed;
    inset: 0;
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: rgba(0,0,0,0.65);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.25s ease-in-out;
}

.upt-help-modal.is-open {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}

.upt-help-modal-overlay {
    position: absolute;
    inset: 0;
}

.upt-help-modal-inner {
    position: relative;
    z-index: 10;
    background: #ffffff;
    border-radius: 12px;
    max-width: 960px;
    width: 100%;
    box-shadow: 0 8px 28px rgba(0,0,0,0.25);
    padding: 0;
}

.upt-help-video-container {
    position: relative;
    width: 100%;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    border-radius: 12px;
}

.upt-help-video-container iframe,
.upt-help-video-container video {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: 0;
}

.upt-help-close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 32px;
    height: 32px;
    border-radius: 999px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.65);
    color: #ffffff;
    font-size: 20px;
    line-height: 1;
}

.upt-help-close:hover {
    background: rgba(0,0,0,0.85);
}
</style>


<!-- ========================= -->
<!--     JS DO MODAL + DASH    -->
<!-- ========================= -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
jQuery(function($) {

    $('#tab-4gt-submissions .open-submission-modal .upt-card-btn__label, #tab-4gt-submissions .upt-submission-action--delete .upt-card-btn__label').remove();

    $('body').on('click', '.open-submission-modal', function(e) {
        e.preventDefault();

        var id = $(this).data('submission-id');
        var $details = $('#upt-submission-details-' + id);

        if (!$details.length) return;

        $('#upt-modal-content').html(
            '<a href="#" id="upt-modal-close" class="upt-modal-close-button">&times;</a>' +
            $details.html()
        );

        $('#upt-modal-wrapper').fadeIn(200);
    });

    function uptConfirmDialog(message, onConfirm) {
        var $overlay = $('<div class="upt-confirm-overlay" role="dialog" aria-modal="true"></div>');
        var $panel = $('<div class="upt-confirm-panel"></div>');
        var $msg = $('<p class="upt-confirm-message"></p>').text(message || 'Confirmar ação?');
        var $actions = $('<div class="upt-confirm-actions"></div>');
        var $cancel = $('<button type="button" class="upt-confirm-cancel">Cancelar</button>');
        var $ok = $('<button type="button" class="upt-confirm-ok">Apagar</button>');

        $actions.append($cancel, $ok);
        $panel.append($msg, $actions);
        $overlay.append($panel);
        $('body').append($overlay);

        function close() {
            $(document).off('keydown.uptConfirm');
            $overlay.remove();
        }

        $overlay.on('click', function(ev) {
            if (ev.target === this) {
                close();
            }
        });

        $cancel.on('click', function() {
            close();
        });

        $ok.on('click', function() {
            close();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });

        $(document).on('keydown.uptConfirm', function(ev) {
            if (ev.key === 'Escape') {
                close();
            }
        });

        $cancel.trigger('focus');
    }

    $('body').on('click', '.upt-submission-action--delete', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        if (!url) return;
        uptConfirmDialog('Excluir este envio?', function() {
            window.location.href = url;
        });
    });

    // Detalhes de botões (Dashboard)
    $('body').on('click', '.upt-open-button-detail', function(e) {
        e.preventDefault();

        var detailId = $(this).data('detail-id');
        if (!detailId) return;

        var $details = $('#' + detailId);
        if (!$details.length) return;

        $('#upt-modal-content').html(
            '<a href="#" id="upt-modal-close" class="upt-modal-close-button">&times;</a>' +
            $details.html()
        );

        $('#upt-modal-wrapper').fadeIn(200);
    });


    // Dashboard upt - gráfico de formulários / cliques
    var $dashboardCanvas = $('#upt-dashboard-chart');
    var isMobile = window.innerWidth <= 768;
    if (isMobile) {
        $dashboardCanvas.parent().css('height', '320px');
    }
    if ($dashboardCanvas.length && typeof Chart !== 'undefined') {

        var uptDashboardData = {
            labels: <?php echo $upt_dashboard_labels_json; ?>,
            forms: <?php echo $upt_forms_series_json; ?>,
            buttons: <?php echo $upt_buttons_series_json; ?>,
            images: <?php echo $upt_images_series_json; ?>
        };

        var uptHasForms   = <?php echo $upt_has_forms_cpt ? 'true' : 'false'; ?>;
        var uptHasButtons = <?php echo $upt_has_buttons_cpt ? 'true' : 'false'; ?>;
        var uptHasImages  = <?php echo $upt_has_images_cpt ? 'true' : 'false'; ?>;

        function uptSliceDashboard(rangeDays) {
            var labels  = uptDashboardData.labels.slice();
            var forms   = uptDashboardData.forms.slice();
            var buttons = uptDashboardData.buttons.slice();
            var images  = uptDashboardData.images.slice();

            if (!rangeDays || rangeDays >= labels.length) {
                return {
                    labels: labels,
                    forms: forms,
                    buttons: buttons,
                    images: images
                };
            }

            var startIndex = Math.max(0, labels.length - rangeDays);

            return {
                labels: labels.slice(startIndex),
                forms: forms.slice(startIndex),
                buttons: buttons.slice(startIndex),
                images: images.slice(startIndex)
            };
        }


        function uptSliceDashboardCustom(startDateStr, endDateStr) {
            var labels  = uptDashboardData.labels.slice();
            var forms   = uptDashboardData.forms.slice();
            var buttons = uptDashboardData.buttons.slice();
            var images  = uptDashboardData.images.slice();

            if (!startDateStr && !endDateStr) {
                return {
                    labels: labels,
                    forms: forms,
                    buttons: buttons,
                    images: images
                };
            }

            var start = startDateStr ? new Date(startDateStr) : null;
            var end   = endDateStr ? new Date(endDateStr) : null;

            var outLabels  = [];
            var outForms   = [];
            var outButtons = [];
            var outImages  = [];

            for (var i = 0; i < labels.length; i++) {
                var current = new Date(labels[i]);
                if ((start === null || current >= start) && (end === null || current <= end)) {
                    outLabels.push(labels[i]);
                    outForms.push(forms[i]);
                    outButtons.push(buttons[i]);
                    outImages.push(images[i]);
                }
            }

            if (!outLabels.length) {
                return {
                    labels: labels,
                    forms: forms,
                    buttons: buttons,
                    images: images
                };
            }

            return {
                labels: outLabels,
                forms: outForms,
                buttons: outButtons,
                images: outImages
            };
        }
        var defaultRange = 30;
        var sliced = uptSliceDashboard(defaultRange);

                var datasets = [];

        if (uptHasForms) {
            datasets.push({
                        label: 'Formulários',
                        data: sliced.forms,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 2
                    });
        }

        if (uptHasButtons) {
            datasets.push({
                        label: 'Cliques em botões',
                        data: sliced.buttons,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 2
                    });
        }

        if (uptHasImages) {
            datasets.push({
                        label: 'Cliques em imagens',
                        data: sliced.images,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 2
                    });
        }


var dashboardChart = new Chart($dashboardCanvas, {
            type: 'line',
            data: {
                labels: sliced.labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        enabled: true
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: isMobile ? 6 : 10,
                            maxRotation: isMobile ? 0 : 45,
                            minRotation: isMobile ? 0 : 45,
                            font: {
                                size: isMobile ? 8 : 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        
        var $rangeSelect      = $('#upt-dashboard-range');
        var $customWrapper    = $('#upt-dashboard-custom-dates');
        var $dateFrom         = $('#upt-dashboard-date-from');
        var $dateTo           = $('#upt-dashboard-date-to');

        var $formsRangeSelect   = $('#upt-forms-range');
        var $formsCustomWrapper = $('#upt-forms-custom-dates');
        var $formsDateFrom      = $('#upt-forms-date-from');
        var $formsDateTo        = $('#upt-forms-date-to');
        var $submissionsGrid    = $('.upt-submissions-grid');

        // Aplica o mesmo filtro de período nos cards de envios de formulário
        function uptFilterSubmissionsByRange() {
            if (!$submissionsGrid.length) {
                return;
            }

            var val   = $formsRangeSelect.length ? $formsRangeSelect.val() : '30';
            var now   = new Date();
            var from  = null;
            var to    = null;
            var MS_PER_DAY = 24 * 60 * 60 * 1000;

            if (val === '7' || val === '30' || val === '90') {
                var days = parseInt(val, 10);
                // inclui hoje e os (days - 1) dias anteriores
                from = new Date(now.getTime() - (days - 1) * MS_PER_DAY);
                from.setHours(0, 0, 0, 0);
                to = new Date(now.getTime());
                to.setHours(23, 59, 59, 999);
            } else if (val === 'custom') {
                var fromStr = $formsDateFrom.val();
                var toStr   = $formsDateTo.val();

                if (fromStr) {
                    from = new Date(fromStr);
                    from.setHours(0, 0, 0, 0);
                }
                if (toStr) {
                    to = new Date(toStr);
                    to.setHours(23, 59, 59, 999);
                }
            }

            $submissionsGrid.find('.upt-item-card--submission').each(function() {
                var ts = parseInt($(this).data('submission-timestamp'), 10);

                if (!ts) {
                    $(this).show();
                    return;
                }

                var dt = new Date(ts * 1000);
                var visible = true;

                if (from && dt < from) {
                    visible = false;
                }
                if (to && dt > to) {
                    visible = false;
                }

                $(this).toggle(visible);
            });
        }

        function uptUpdateDashboardFromRange() {
            var val = $rangeSelect.val();
            var slicedData;

            if (val === 'custom') {
                var from = $dateFrom.val();
                var to   = $dateTo.val();
                slicedData = uptSliceDashboardCustom(from, to);
            } else {
                var range = parseInt(val, 10) || defaultRange;
                slicedData = uptSliceDashboard(range);
            }

            var newDatasets = [];

            if (uptHasForms) {
                newDatasets.push({
                    label: 'Formulários',
                    data: slicedData.forms,
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 2
                });
            }

            if (uptHasButtons) {
                newDatasets.push({
                    label: 'Cliques em botões',
                    data: slicedData.buttons,
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 2
                });
            }

            if (uptHasImages) {
                newDatasets.push({
                    label: 'Cliques em imagens',
                    data: slicedData.images,
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 2
                });
            }

            dashboardChart.data.labels   = slicedData.labels;
            dashboardChart.data.datasets = newDatasets;
            dashboardChart.update();
            uptFilterSubmissionsByRange();
        }

        $rangeSelect.on('change', function() {
            if ($rangeSelect.val() === 'custom') {
                $customWrapper.css('display', 'inline-flex');
            } else {
                $customWrapper.hide();
            }
            uptUpdateDashboardFromRange();
        });

        $dateFrom.on('change', uptUpdateDashboardFromRange);
        $dateTo.on('change', uptUpdateDashboardFromRange);

        // Controles específicos da aba de formulários
        if ($formsRangeSelect.length) {
            $formsRangeSelect.on('change', function() {
                if ($formsRangeSelect.val() === 'custom') {
                    $formsCustomWrapper.css('display', 'inline-flex');
                } else {
                    $formsCustomWrapper.css('display', 'none');
                }
                uptFilterSubmissionsByRange();
            });

            $formsDateFrom.on('change', uptFilterSubmissionsByRange);
            $formsDateTo.on('change', uptFilterSubmissionsByRange);
        }

        // aplica filtro inicial nos envios de formulário
        uptFilterSubmissionsByRange();
    }

    // ===== CRON Settings =====
    (function() {
        var $saveBtn = jQuery('#upt-cron-save');
        if (!$saveBtn.length) return;

        var ajaxUrl = (typeof upt_ajax !== 'undefined') ? upt_ajax.ajax_url : '/wp-admin/admin-ajax.php';
        var nonce = (typeof upt_ajax !== 'undefined') ? upt_ajax.nonce : '';

        $saveBtn.on('click', function() {
            var $btn = jQuery(this);
            $btn.prop('disabled', true).text('Salvando...');
            jQuery.post(ajaxUrl, {
                action: 'upt_save_cron_config',
                nonce: nonce,
                url: jQuery('#upt-cron-url').val(),
                schema: jQuery('#upt-cron-schema').val(),
                frequency: jQuery('#upt-cron-freq').val()
            }, function(resp) {
                $btn.prop('disabled', false);
                if (resp.success) {
                    location.reload();
                } else {
                    alert('Erro: ' + (resp.data && resp.data.message ? resp.data.message : 'desconhecido'));
                    $btn.text('Salvar e Ativar');
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Salvar e Ativar');
                alert('Erro de conexão.');
            });
        });

        jQuery('#upt-cron-test').on('click', function() {
            var $btn = jQuery(this);
            $btn.prop('disabled', true).text('Testando...');
            jQuery.post(ajaxUrl, {
                action: 'upt_test_cron_import',
                nonce: nonce
            }, function(resp) {
                $btn.prop('disabled', false).text('Testar Agora');
                if (resp.success) {
                    alert('Teste iniciado! Verifique o status em instantes.');
                    setTimeout(function() { location.reload(); }, 3000);
                } else {
                    alert('Erro: ' + (resp.data && resp.data.message ? resp.data.message : 'desconhecido'));
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Testar Agora');
                alert('Erro de conexão.');
            });
        });
    })();

    // ===== Card Settings =====
    (function() {
        var $saveBtn = jQuery('#upt-save-card-settings');
        if (!$saveBtn.length) return;

        var ajaxUrl = (typeof upt_ajax !== 'undefined') ? upt_ajax.ajax_url : '/wp-admin/admin-ajax.php';
        var nonce = (typeof upt_ajax !== 'undefined') ? upt_ajax.nonce : '';

        $saveBtn.on('click', function() {
            var dashFields = [];
            jQuery('[name="upt_card_dashboard_fields[]"]:checked').each(function() {
                dashFields.push(jQuery(this).val());
            });
            var siteFields = [];
            jQuery('[name="upt_card_site_fields[]"]:checked').each(function() {
                siteFields.push(jQuery(this).val());
            });

            $saveBtn.prop('disabled', true).text('Salvando...');

            jQuery.post(ajaxUrl, {
                action: 'upt_save_card_settings',
                nonce: nonce,
                dashboard_fields: dashFields,
                site_fields: siteFields
            }, function(resp) {
                $saveBtn.prop('disabled', false).text('Salvar Configurações');
                if (resp.success) {
                    jQuery('#upt-card-settings-saved').show().fadeOut(2000);
                } else {
                    alert('Erro: ' + (resp.data && resp.data.message ? resp.data.message : 'desconhecido'));
                }
            }).fail(function() {
                $saveBtn.prop('disabled', false).text('Salvar Configurações');
                alert('Erro de conexão.');
            });
        });
    })();

    // ===== Importador XML de Imobiliária =====
    (function() {
        var $startBtn = jQuery('#upt-imob-start-btn');
        if (!$startBtn.length) return;

        var ajaxUrl = (typeof upt_ajax !== 'undefined') ? upt_ajax.ajax_url : '/wp-admin/admin-ajax.php';
        var nonce = (typeof upt_ajax !== 'undefined') ? upt_ajax.nonce : '';

        var $mode = jQuery('#upt-imob-schema-mode');
        var $newField = jQuery('#upt-imob-new-schema-field');
        var $existingField = jQuery('#upt-imob-existing-schema-field');
        var $uploadSection = jQuery('#upt-imob-upload-section');
        var $progressSection = jQuery('#upt-imob-progress-section');
        var $doneSection = jQuery('#upt-imob-done-section');
        var $cancelBtn = jQuery('#upt-imob-cancel-btn');
        var $newBtn = jQuery('#upt-imob-new-btn');

        var session_id = '', total_items = 0, imported_total = 0, photos_total = 0, errors_total = 0, current_offset = 0, is_running = false, batch_size = 5;

        $mode.on('change', function() {
            if (jQuery(this).val() === 'existing') { $newField.hide(); $existingField.show(); }
            else { $newField.show(); $existingField.hide(); }
        });

        $startBtn.on('click', function() {
            var fileInput = jQuery('#upt-imob-xml-file')[0];
            if (!fileInput.files || fileInput.files.length === 0) { alert('Selecione um arquivo XML.'); return; }
            $startBtn.prop('disabled', true).text('Enviando...');
            uploadAndStart(fileInput.files[0]);
        });

        $cancelBtn.on('click', function() {
            if (!confirm('Cancelar importação?')) return;
            is_running = false;
            jQuery.post(ajaxUrl, { action: 'upt_imob_cancel', nonce: nonce, session_id: session_id });
            $progressSection.hide(); $uploadSection.show();
            $startBtn.prop('disabled', false).text('Enviar e Iniciar Importação');
        });

        $newBtn.on('click', function() {
            $doneSection.hide(); $uploadSection.show();
            $startBtn.prop('disabled', false).text('Enviar e Iniciar Importação');
            jQuery('#upt-imob-xml-file').val('');
        });

        function uploadAndStart(file) {
            var formData = new FormData();
            formData.append('imob_xml_file', file);
            formData.append('action', 'upt_imob_upload');
            formData.append('nonce', nonce);
            formData.append('imob_schema_mode', $mode.val());
            formData.append('imob_schema_name', jQuery('#upt-imob-schema-name').val());
            formData.append('imob_schema_existing', jQuery('#upt-imob-schema-existing').val());

            jQuery.ajax({
                url: ajaxUrl, type: 'POST', data: formData,
                processData: false, contentType: false, timeout: 300000,
                success: function(resp) {
                    $startBtn.prop('disabled', false).text('Enviar e Iniciar Importação');
                    if (resp.success && resp.data && resp.data.session_id) {
                        session_id = resp.data.session_id;
                        $uploadSection.hide(); $doneSection.hide(); $progressSection.show();
                        imported_total = 0; photos_total = 0; errors_total = 0; current_offset = 0; is_running = true;
                        updateStats();
                        countAndProcess();
                    } else { alert('Erro: ' + (resp.data && resp.data.message ? resp.data.message : 'Não foi possível iniciar.')); }
                },
                error: function() { $startBtn.prop('disabled', false).text('Enviar e Iniciar Importação'); alert('Erro ao enviar.'); }
            });
        }

        function updateStats() {
            var pct = total_items > 0 ? Math.round((current_offset / total_items) * 100) : 0;
            jQuery('#upt-imob-progress-bar').css('width', pct + '%');
            jQuery('#upt-imob-progress-text').text(pct + '%');
            jQuery('#upt-imob-stat-total').text(total_items || '—');
            jQuery('#upt-imob-stat-imported').text(imported_total);
            jQuery('#upt-imob-stat-photos').text(photos_total);
            jQuery('#upt-imob-stat-errors').text(errors_total);
        }

        function countAndProcess() {
            setStatus('Contando imóveis...');
            jQuery.post(ajaxUrl, { action: 'upt_imob_count', nonce: nonce, session_id: session_id }, function(resp) {
                if (!resp.success) { setStatus('Erro: ' + (resp.data && resp.data.message ? resp.data.message : '?'), true); return; }
                total_items = resp.data.total; updateStats(); processNextBatch();
            });
        }

        function processNextBatch() {
            if (!is_running) return;
            setStatus('Processando ' + (current_offset + 1) + '-' + Math.min(current_offset + batch_size, total_items) + ' de ' + total_items + '...');
            jQuery.post(ajaxUrl, { action: 'upt_imob_batch', nonce: nonce, session_id: session_id, offset: current_offset, limit: batch_size }, function(resp) {
                if (!is_running) return;
                if (!resp.success) {
                    setStatus('Erro: ' + (resp.data && resp.data.message ? resp.data.message : '?') + '. Continuando...', true);
                    current_offset += batch_size; updateStats(); setTimeout(processNextBatch, 2000); return;
                }
                var d = resp.data;
                imported_total += d.imported; photos_total += d.photos; errors_total += d.errors; current_offset = d.next_offset; updateStats();
                if (d.last_error) setStatus('Erro: ' + d.last_error + '. Continuando...', true);
                else setStatus('Processados ' + current_offset + ' de ' + total_items + '. Fotos: ' + photos_total + '.');
                if (d.is_finished) { is_running = false; $progressSection.hide(); $doneSection.show(); jQuery('#upt-imob-done-stats').text('Importados: ' + imported_total + ' | Erros: ' + errors_total + ' | Fotos: ' + photos_total); }
                else setTimeout(processNextBatch, 500);
            }).fail(function() { if (!is_running) return; setStatus('Erro de conexão. Tentando...', true); setTimeout(processNextBatch, 3000); });
        }

        function setStatus(msg, isError) {
            var $el = jQuery('#upt-imob-status-msg'); $el.text(msg);
            if (isError) $el.css({ background: '#fef2f2', color: '#991b1b' }); else $el.css({ background: '#f1f5f9', color: '#475569' });
        }
    })();

});
</script>
