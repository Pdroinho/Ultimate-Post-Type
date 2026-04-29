<?php
if (!defined('ABSPATH'))
    exit;

class UPT_Listing_Widget extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'upt_listing_grid';
    }
    public function get_title()
    {
        return 'Grade do Catálogo';
    }
    public function get_icon()
    {
        return 'eicon-posts-grid';
    }

    public function get_categories()
    {
        return ['upt'];
    }

    public function get_script_depends()
    {
        return ['upt-main-js'];
    }

    public function get_style_depends()
    {
        return ['upt-style'];
    }

    private function get_all_custom_fields_options($for_inserter = false)
    {
        $options = [];
        $native_types = ['core_title', 'core_content', 'core_featured_image', 'taxonomy'];
        $schemas = UPT_Schema_Store::get_schemas();
        if (empty($schemas))
            return $options;
        foreach ($schemas as $slug => $data) {
            if (isset($data['fields']) && is_array($data['fields'])) {
                $term = get_term_by('slug', $slug, 'catalog_schema');
                $name = $term ? $term->name : $slug;
                foreach ($data['fields'] as $field) {
                    if (in_array($field['type'], $native_types))
                        continue;
                    $key = $for_inserter ? '[' . $field['id'] . ']' : $field['id'];
                    $options[$key] = $name . ' - ' . $field['label'];
                }
            }
        }
        return $options;
    }

    private function get_select_fields_options()
    {
        $options = ['' => '— Nenhum —'];
        $schemas = UPT_Schema_Store::get_schemas();
        if (empty($schemas)) return $options;
        foreach ($schemas as $slug => $data) {
            if (isset($data['fields']) && is_array($data['fields'])) {
                $term = get_term_by('slug', $slug, 'catalog_schema');
                $name = $term ? $term->name : $slug;
                foreach ($data['fields'] as $field) {
                    if ($field['type'] === 'select' && empty($field['multiple']) && !empty($field['options'])) {
                        $options[$field['id']] = $name . ' - ' . $field['label'];
                    }
                }
            }
        }
        return $options;
    }

    private function get_meta_filter_field_options()
    {
        $options = ['' => '— Selecione —'];
        $schemas = UPT_Schema_Store::get_schemas();
        if (empty($schemas)) return $options;
        foreach ($schemas as $slug => $data) {
            if (isset($data['fields']) && is_array($data['fields'])) {
                foreach ($data['fields'] as $field) {
                    if ($field['type'] === 'select' && empty($field['multiple']) && !empty($field['options'])) {
                        $raw_opts = $field['options'];
                        $opt_list = is_string($raw_opts) ? explode('|', $raw_opts) : (is_array($raw_opts) ? $raw_opts : []);
                        foreach ($opt_list as $opt) {
                            $opt = trim($opt);
                            if ($opt !== '') {
                                $options[$opt] = $opt;
                            }
                        }
                    }
                }
            }
        }
        return $options;
    }

    private function get_all_loop_templates()
    {
        $query = new WP_Query([
            'post_type' => 'elementor_library',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'tax_query' => [
                [
                    'taxonomy' => 'elementor_library_type',
                    'field' => 'slug',
                    'terms' => ['loop', 'loop-item']
                ]
            ]
        ]);
        $options = ['' => '— Layout Manual (Abaixo) —'];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $options[get_the_ID()] = get_the_title();
            }
        }
        wp_reset_postdata();
        return $options;
    }

    /**
     * Descobre quais esquemas estão em uso na consulta para garantir a ordem manual.
     */
    private function get_schema_slugs_for_query($settings)
    {
        $slugs = [];

        if (!empty($settings['schema_filter']) && is_array($settings['schema_filter'])) {
            foreach ($settings['schema_filter'] as $schema_slug) {
                $schema_slug = sanitize_title($schema_slug);
                if ($schema_slug) {
                    $slugs[] = $schema_slug;
                }
            }
        }
        elseif (isset($_GET['upt_category']) && absint($_GET['upt_category'])) {
            $cat_id = absint($_GET['upt_category']);
            $term = get_term($cat_id, 'catalog_category');

            if ($term && !is_wp_error($term)) {
                $ancestors = get_ancestors($cat_id, 'catalog_category');
                $top_id = $term->parent && !empty($ancestors) ? end($ancestors) : $term->term_id;
                $top_term = get_term($top_id, 'catalog_category');

                if ($top_term && !is_wp_error($top_term)) {
                    $slugs[] = $top_term->slug;
                }
            }
        }
        else {
            $all_schemas = get_terms([
                'taxonomy' => 'catalog_schema',
                'hide_empty' => false,
                'fields' => 'slugs',
            ]);

            if (!is_wp_error($all_schemas) && count($all_schemas) === 1) {
                $single = reset($all_schemas);
                if ($single) {
                    $slugs[] = $single;
                }
            }
        }

        return array_values(array_unique($slugs));
    }

    /**
     * Garante que itens de um esquema possuam a meta de ordem manual.
     */
    private function ensure_manual_order_for_schema($schema_slug)
    {
        $schema_slug = sanitize_title($schema_slug);
        if (!$schema_slug) {
            return;
        }

        $existing_query = new WP_Query([
            'post_type' => 'catalog_item',
            'post_status' => ['publish', 'pending', 'draft'],
            'posts_per_page' => 1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'catalog_schema',
                    'field' => 'slug',
                    'terms' => $schema_slug,
                ],
            ],
            'meta_key' => 'upt_manual_order',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
        ]);

        $max_order = 0;
        if ($existing_query->have_posts()) {
            $max_order = (int)get_post_meta($existing_query->posts[0], 'upt_manual_order', true);
        }
        wp_reset_postdata();

        $missing_query = new WP_Query([
            'post_type' => 'catalog_item',
            'post_status' => ['publish', 'pending', 'draft'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'catalog_schema',
                    'field' => 'slug',
                    'terms' => $schema_slug,
                ],
            ],
            'meta_query' => [
                [
                    'key' => 'upt_manual_order',
                    'compare' => 'NOT EXISTS',
                ],
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if ($missing_query->have_posts()) {
            $position = $max_order;
            foreach ($missing_query->posts as $post_id_missing) {
                $position++;
                update_post_meta($post_id_missing, 'upt_manual_order', $position);
            }
        }
        wp_reset_postdata();
    }

    private static function extract_link_by_class($links, $class_fragment)
    {
        if (empty($links) || !is_array($links)) {
            return '';
        }

        foreach ($links as $link) {
            if (strpos($link, $class_fragment) !== false) {
                return $link;
            }
        }

        return '';
    }

    private static function extract_href_from_link($link)
    {
        if (preg_match('/href="([^"]+)"/', $link, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private static function build_nav_link($link, $label, $class_name)
    {
        $href = self::extract_href_from_link($link);

        if ($href) {
            return '<a class="page-numbers ' . esc_attr($class_name) . '" href="' . esc_url($href) . '">' . esc_html($label) . '</a>';
        }

        return '<span class="page-numbers ' . esc_attr($class_name) . ' disabled">' . esc_html($label) . '</span>';
    }

    public static function build_pagination_markup($links, $pagination_type, $current_page, $total_pages, $options = [])
    {
        if (empty($links) || !is_array($links)) {
            return '';
        }

        $type = $pagination_type ? $pagination_type : 'numbers';

        $show_arrows = true;
        if (isset($options['show_arrows'])) {
            $show_arrows = (bool)$options['show_arrows'];
        }

        $infinite_trigger = isset($options['infinite_trigger']) ? $options['infinite_trigger'] : 'button';
        $load_more_text = isset($options['load_more_text']) && $options['load_more_text'] !== '' ? $options['load_more_text'] : __('Carregar mais', 'upt');

        if ($type === 'infinite') {
            if ($current_page >= $total_pages) {
                return '';
            }

            $next_page = $current_page + 1;
            $next_link = self::extract_link_by_class($links, 'next');
            $next_href = self::extract_href_from_link($next_link);
            $common_attrs = ' data-next-page="' . esc_attr($next_page) . '" data-total-pages="' . esc_attr($total_pages) . '" data-trigger="' . esc_attr($infinite_trigger) . '"';
            if ($next_href) {
                $common_attrs .= ' data-next-url="' . esc_url($next_href) . '"';
            }

            if ($infinite_trigger === 'scroll') {
                return '<div class="upt-pagination-infinite" data-trigger="scroll"><div class="upt-load-more-sentinel"' . $common_attrs . '></div></div>';
            }

            return '<div class="upt-pagination-infinite" data-trigger="button"><button type="button" class="upt-load-more"' . $common_attrs . '>' . esc_html($load_more_text) . '</button></div>';
        }

        if ($type === 'arrows' || $type === 'prev_next') {
            $prev_label = $type === 'prev_next' ? __('Anterior', 'upt') : '‹';
            $next_label = $type === 'prev_next' ? __('Próximo', 'upt') : '›';

            $prev_link = self::extract_link_by_class($links, 'prev');
            $next_link = self::extract_link_by_class($links, 'next');

            $prev_html = self::build_nav_link($prev_link, $prev_label, 'prev');
            $next_html = self::build_nav_link($next_link, $next_label, 'next');

            return '<div class="upt-pagination-compact">' . $prev_html . $next_html . '</div>';
        }

        if (!$show_arrows) {
            $links = array_filter($links, function ($link) {
                return (strpos($link, 'prev') === false && strpos($link, 'next') === false);
            });
        }

        return '<div class="upt-pagination-numbers">' . implode('', $links) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    protected function _register_controls()
    {
        $this->start_controls_section('section_query', ['label' => 'Fonte dos Itens']);

        $schemas = get_terms(['taxonomy' => 'catalog_schema', 'hide_empty' => false]);
        $schema_options = [];
        if (!is_wp_error($schemas)) {
            foreach ($schemas as $schema) {
                $schema_options[$schema->slug] = $schema->name;
            }
        }


        // Tambm permite exibir posts nativos do WordPress (post_type = 'post').
        // Mantm a lista existente de esquemas do upt e apenas adiciona esta opo.
        $schema_options['wp_post'] = 'Post (WordPress)';
        $this->add_control('schema_filter', [
            'label' => 'Filtrar por Esquema(s)',
            'type' => \Elementor\Controls_Manager::SELECT2,
            'multiple' => true,
            'options' => $schema_options,
            'label_block' => true,
            'description' => 'Deixe em branco para mostrar itens de todos os esquemas. Se selecionar "Post (WordPress)", o grid ser carregado a partir dos posts nativos (e os demais esquemas sero ignorados).',
        ]);

        $this->add_control(
            'enable_pagination',
        [
            'label' => 'Ativar Paginação',
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => 'Sim',
            'label_off' => 'Não',
            'return_value' => 'yes',
            'default' => 'no',
            'separator' => 'before',
        ]
        );

        $this->add_control(
            'pagination_type',
        [
            'label' => 'Tipo de Paginação',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'numbers' => 'Números (padrão)',
                'arrows' => 'Setas',
                'prev_next' => 'Anterior / Próximo',
                'infinite' => 'Carregar mais (infinito)',
            ],
            'default' => 'numbers',
            'condition' => ['enable_pagination' => 'yes'],
            'description' => 'Escolha o estilo de paginação. "Carregar mais" usa botão e paginação incremental.',
        ]
        );

        $this->add_control(
            'pagination_numbers_show_arrows',
        [
            'label' => 'Mostrar setas junto aos números',
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => 'Sim',
            'label_off' => 'Não',
            'return_value' => 'yes',
            'default' => 'yes',
            'condition' => ['enable_pagination' => 'yes', 'pagination_type' => 'numbers'],
            'description' => 'Desative se quiser apenas os números sem setas.',
        ]
        );

        $this->add_control(
            'pagination_infinite_trigger',
        [
            'label' => 'Modo do carregamento infinito',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'button' => 'Botão "Carregar mais"',
                'scroll' => 'Scroll automático (quando aparece na tela)',
            ],
            'default' => 'button',
            'condition' => ['enable_pagination' => 'yes', 'pagination_type' => 'infinite'],
            'description' => 'Escolha entre clicar no botão ou carregar automaticamente ao rolar.',
        ]
        );

        $this->add_control(
            'pagination_infinite_button_text',
        [
            'label' => 'Texto do botão (infinito)',
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Carregar mais',
            'condition' => ['enable_pagination' => 'yes', 'pagination_type' => 'infinite'],
        ]
        );

        $this->add_control('posts_per_page', [
            'label' => 'Itens por página',
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 6,
            'description' => 'Se a paginação estiver desativada, este será o número máximo de itens exibidos.'
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_builtin_filter', ['label' => 'Filtros Embutidos']);

        $this->add_control('enable_builtin_filter', [
            'label' => 'Ativar Filtros Embutidos',
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => 'Sim',
            'label_off' => 'Não',
            'return_value' => 'yes',
            'default' => 'no',
            'description' => 'Mostra uma barra de filtros de categorias acima da grade automaticamente.',
        ]);

        $this->add_control('builtin_filter_all_text', [
            'label' => 'Texto para "Todas as Categorias"',
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Todas',
            'condition' => ['enable_builtin_filter' => 'yes'],
        ]);

        $this->add_control('builtin_filter_hide_empty', [
            'label' => 'Ocultar Categorias Vazias',
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => 'Sim',
            'label_off' => 'Não',
            'return_value' => 'yes',
            'default' => 'yes',
            'condition' => ['enable_builtin_filter' => 'yes'],
        ]);

        $this->add_control('builtin_filter_show_subcategories', [
            'label' => 'Exibir Subcategorias',
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => 'Sim',
            'label_off' => 'Não',
            'return_value' => 'yes',
            'default' => 'no',
            'description' => 'Se ativado, inclui as subcategorias na lista de filtros.',
            'condition' => ['enable_builtin_filter' => 'yes'],
        ]);

        $this->add_responsive_control('builtin_filter_align', [
            'label' => 'Alinhamento dos Filtros',
            'type' => \Elementor\Controls_Manager::CHOOSE,
            'options' => [
                'flex-start' => ['title' => 'Esquerda', 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => 'Centro', 'icon' => 'eicon-text-align-center'],
                'flex-end' => ['title' => 'Direita', 'icon' => 'eicon-text-align-right'],
                'justified' => ['title' => 'Justificada', 'icon' => 'eicon-text-align-justify'],
            ],
            'default' => 'center',
            'condition' => ['enable_builtin_filter' => 'yes'],
        ]);

        $this->add_control('builtin_meta_filter_field', [
            'label' => 'Filtro por Campo (ex: Status do Imóvel)',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => $this->get_select_fields_options(),
            'description' => 'Exibe um dropdown para filtrar por um campo do tipo select (ex: Venda, Aluguel).',
            'condition' => ['enable_builtin_filter' => 'yes'],
        ]);

        $this->add_control('builtin_meta_filter_label', [
            'label' => 'Rótulo do Filtro por Campo',
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Status:',
            'condition' => ['enable_builtin_filter' => 'yes', 'builtin_meta_filter_field!' => ''],
        ]);

        $this->add_control('builtin_meta_filter_all_text', [
            'label' => 'Texto "Todos" do Filtro por Campo',
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Todos',
            'condition' => ['enable_builtin_filter' => 'yes', 'builtin_meta_filter_field!' => ''],
        ]);

        $this->add_control('builtin_meta_filter_mode', [
            'label' => 'Modo do Filtro por Campo',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'interactive' => 'Interativo (mostra pills)',
                'fixed' => 'Fixo (oculto)',
            ],
            'default' => 'interactive',
            'description' => '"Interativo" mostra as pills na barra de filtros. "Fixo" aplica o filtro automaticamente sem mostrar ao usuário.',
            'condition' => ['enable_builtin_filter' => 'yes', 'builtin_meta_filter_field!' => ''],
        ]);

        $this->add_control('builtin_meta_filter_fixed_value', [
            'label' => 'Valor Fixo do Filtro',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => $this->get_meta_filter_field_options(),
            'description' => 'Se o modo for "Fixo", aplica este valor automaticamente na query.',
            'condition' => ['enable_builtin_filter' => 'yes', 'builtin_meta_filter_field!' => '', 'builtin_meta_filter_mode' => 'fixed'],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_layout', ['label' => 'Layout']);

        $this->add_control('template_id', [
            'label' => 'Template do Card (Loop Item)',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => $this->get_all_loop_templates(),
            'description' => 'Selecione um template de Loop para renderizar os itens. Se um for selecionado, as configurações da "Estrutura do Card" serão ignoradas.',
        ]);

        $this->add_responsive_control('columns', [
            'label' => 'Colunas',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '3',
            'tablet_default' => '2',
            'mobile_default' => '1',
            'options' => [
                '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6',
            ],
            'selectors' => [
                '{{WRAPPER}} .elementor-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
            ],
        ]);

        $this->add_responsive_control('column_gap', [
            'label' => 'Espaçamento da Grade',
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range' => ['px' => ['min' => 0, 'max' => 100]],
            'default' => ['unit' => 'px', 'size' => 20],
            'selectors' => [
                '{{WRAPPER}} .elementor-grid' => 'gap: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('equal_height', [
            'label' => 'Forçar altura igual',
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => 'Sim',
            'label_off' => 'Não',
            'return_value' => 'yes',
            'default' => 'no',
            'description' => 'Ativa preenchimento flex para que todos os cards da grade tenham a mesma altura.',
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_card_elements', ['label' => 'Estrutura do Card (Layout Manual)', 'condition' => ['template_id' => '']]);
        $this->add_control('card_wrapper_link', ['label' => 'Card Inteiro Clicável?', 'type' => \Elementor\Controls_Manager::SWITCHER, 'label_on' => 'Sim', 'label_off' => 'Não', 'return_value' => 'yes', 'default' => 'no', 'description' => 'Se ativado, links individuais na imagem e título serão removidos.']);
        $repeater = new \Elementor\Repeater();
        $repeater->add_control('element', ['label' => 'Elemento', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'title', 'options' => ['image' => 'Imagem Destacada', 'title' => 'Título', 'category' => 'Categoria', 'individual_meta' => 'Campo Customizado Individual', 'button' => 'Botão', ], ]);
        $repeater->add_control('meta_key', ['label' => 'Campo', 'type' => \Elementor\Controls_Manager::SELECT, 'options' => $this->get_all_custom_fields_options(), 'condition' => ['element' => 'individual_meta']]);
        $repeater->add_control('meta_prefix', ['label' => 'Prefixo', 'type' => \Elementor\Controls_Manager::TEXT, 'condition' => ['element' => 'individual_meta']]);
        $repeater->add_control('meta_suffix', ['label' => 'Sufixo', 'type' => \Elementor\Controls_Manager::TEXT, 'condition' => ['element' => 'individual_meta']]);
        $repeater->add_control('button_text', ['label' => 'Texto do Botão', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Ver Detalhes', 'condition' => ['element' => 'button']]);
        $repeater->add_control('button_action', ['label' => 'Ação do Botão', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'link', 'options' => ['link' => 'Link para o post', 'whatsapp' => 'Enviar Mensagem (WhatsApp)'], 'condition' => ['element' => 'button']]);
        $repeater->add_control('whatsapp_number', ['label' => 'Número do WhatsApp (com DDI)', 'type' => \Elementor\Controls_Manager::TEXT, 'placeholder' => '5579999999999', 'condition' => ['element' => 'button', 'button_action' => 'whatsapp']]);
        $repeater->add_control('whatsapp_message', ['label' => 'Mensagem', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Olá, tenho interesse no item:', 'description' => 'O nome do produto será adicionado automaticamente no final.', 'condition' => ['element' => 'button', 'button_action' => 'whatsapp']]);
        $repeater->add_control('style_heading', ['label' => 'Estilo Individual', 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before', 'condition' => ['element' => ['individual_meta', 'category']]]);
        $repeater->add_control('meta_color', ['label' => 'Cor', 'type' => \Elementor\Controls_Manager::COLOR, 'condition' => ['element' => ['individual_meta', 'category']]]);
        $repeater->add_responsive_control('meta_align', ['label' => 'Alinhamento', 'type' => \Elementor\Controls_Manager::CHOOSE, 'options' => ['left' => ['title' => 'Esquerda', 'icon' => 'eicon-text-align-left'], 'center' => ['title' => 'Centro', 'icon' => 'eicon-text-align-center'], 'right' => ['title' => 'Direita', 'icon' => 'eicon-text-align-right']], 'condition' => ['element' => ['individual_meta', 'category']]]);
        $repeater->add_control('meta_font_family', ['label' => 'Família da Fonte', 'type' => \Elementor\Controls_Manager::FONT, 'default' => '', 'condition' => ['element' => ['individual_meta', 'category']]]);
        $repeater->add_responsive_control('meta_font_size', ['label' => 'Tamanho da Fonte', 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => ['px', 'em', 'rem'], 'range' => ['px' => ['min' => 8, 'max' => 100]], 'condition' => ['element' => ['individual_meta', 'category']]]);
        $repeater->add_control('meta_font_weight', ['label' => 'Peso da Fonte', 'type' => \Elementor\Controls_Manager::SELECT, 'options' => ['' => 'Padrão', 'normal' => 'Normal', 'bold' => 'Negrito', '100' => '100', '200' => '200', '300' => '300', '400' => '400', '500' => '500', '600' => '600', '700' => '700', '800' => '800', '900' => '900'], 'condition' => ['element' => ['individual_meta', 'category']]]);
        $repeater->add_control('meta_text_transform', ['label' => 'Transformação', 'type' => \Elementor\Controls_Manager::SELECT, 'options' => ['' => 'Padrão', 'uppercase' => 'MAIÚSCULAS', 'lowercase' => 'minúsculas', 'capitalize' => 'Capitalizadas'], 'condition' => ['element' => ['individual_meta', 'category']]]);
        $this->add_control('card_structure', ['label' => 'Estrutura do Card', 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'default' => [['element' => 'image'], ['element' => 'title'], ['element' => 'button']], 'title_field' => '{{{ element.replace("_", " ").toUpperCase() }}}']);
        $this->end_controls_section();

        $this->start_controls_section('section_style_card', ['label' => 'Estilo do Card', 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('card_bg_color', ['label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-card' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'card_border', 'selector' => '{{WRAPPER}} .upt-card']);
        $this->add_responsive_control('card_border_radius', ['label' => 'Arredondamento', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'selectors' => ['{{WRAPPER}} .upt-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_responsive_control('card_padding', ['label' => 'Padding Interno', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'selectors' => ['{{WRAPPER}} .upt-card-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'card_box_shadow', 'selector' => '{{WRAPPER}} .upt-card']);
        $this->add_responsive_control('inner_element_spacing', ['label' => 'Espaçamento entre Elementos', 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .upt-element-wrapper:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}};'], 'separator' => 'before', 'condition' => ['template_id' => '']]);
        $this->end_controls_section();

        $this->start_controls_section('section_style_image', ['label' => 'Estilo da Imagem', 'tab' => \Elementor\Controls_Manager::TAB_STYLE, 'condition' => ['template_id' => '']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'image_border', 'selector' => '{{WRAPPER}} .upt-card-thumbnail img']);
        $this->add_responsive_control('image_border_radius', ['label' => 'Arredondamento', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'selectors' => ['{{WRAPPER}} .upt-card-thumbnail img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('image_aspect_ratio', ['label' => 'Proporção da Imagem', 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0.1, 'max' => 2, 'step' => 0.01]], 'description' => 'Ex: 1 = Quadrado, 1.77 = 16:9, 0.75 = 3:4.', 'selectors' => ['{{WRAPPER}} .upt-card-thumbnail img' => 'aspect-ratio: {{SIZE}}; width: 100%;']]);
        $this->add_control('image_object_fit', ['label' => 'Enquadramento', 'type' => \Elementor\Controls_Manager::SELECT, 'options' => ['cover' => 'Preencher (Cover)', 'contain' => 'Conter (Contain)'], 'default' => 'cover', 'selectors' => ['{{WRAPPER}} .upt-card-thumbnail img' => 'object-fit: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Css_Filter::get_type(), ['name' => 'image_css_filters', 'selector' => '{{WRAPPER}} .upt-card-thumbnail img']);
        $this->end_controls_section();

        $this->start_controls_section('section_style_title', ['label' => 'Estilo do Título', 'tab' => \Elementor\Controls_Manager::TAB_STYLE, 'condition' => ['template_id' => '']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'title_typography', 'selector' => '{{WRAPPER}} .upt-card-title']);
        $this->add_control('title_color', ['label' => 'Cor', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-card-title, {{WRAPPER}} .upt-card-title a' => 'color: {{VALUE}};']]);
        $this->add_responsive_control('title_align', ['label' => 'Alinhamento', 'type' => \Elementor\Controls_Manager::CHOOSE, 'options' => ['left' => ['title' => 'Esquerda', 'icon' => 'eicon-text-align-left'], 'center' => ['title' => 'Centro', 'icon' => 'eicon-text-align-center'], 'right' => ['title' => 'Direita', 'icon' => 'eicon-text-align-right']], 'selectors' => ['{{WRAPPER}} .upt-card-title' => 'text-align: {{VALUE}};']]);
        $this->end_controls_section();

        $this->start_controls_section('section_style_meta', ['label' => 'Estilo dos Campos (Global)', 'tab' => \Elementor\Controls_Manager::TAB_STYLE, 'condition' => ['template_id' => '']]);
        $this->add_control('meta_font_family_global', ['label' => 'Família da Fonte', 'type' => \Elementor\Controls_Manager::FONT, 'selectors' => ['{{WRAPPER}} .upt-meta-item' => 'font-family: "{{VALUE}}";']]);
        $this->add_control('meta_color_global', ['label' => 'Cor (Global)', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-meta-item' => 'color: {{VALUE}};']]);
        $this->add_responsive_control('meta_align_global', ['label' => 'Alinhamento (Global)', 'type' => \Elementor\Controls_Manager::CHOOSE, 'options' => ['left' => ['title' => 'Esquerda', 'icon' => 'eicon-text-align-left'], 'center' => ['title' => 'Centro', 'icon' => 'eicon-text-align-center'], 'right' => ['title' => 'Direita', 'icon' => 'eicon-text-align-right']], 'selectors' => ['{{WRAPPER}} .upt-meta-item' => 'text-align: {{VALUE}};']]);
        $this->end_controls_section();

        $this->start_controls_section('section_style_button', ['label' => 'Estilo do Botão', 'tab' => \Elementor\Controls_Manager::TAB_STYLE, 'condition' => ['template_id' => '']]);
        $this->add_responsive_control('button_align', ['label' => 'Alinhamento', 'type' => \Elementor\Controls_Manager::CHOOSE, 'options' => ['left' => ['title' => 'Esquerda', 'icon' => 'eicon-text-align-left'], 'center' => ['title' => 'Centro', 'icon' => 'eicon-text-align-center'], 'right' => ['title' => 'Direita', 'icon' => 'eicon-text-align-right'], 'justify' => ['title' => 'Esticar', 'icon' => 'eicon-align-stretch-h']], 'prefix_class' => 'upt-button-align%s-']);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'button_typography', 'selector' => '{{WRAPPER}} .upt-card-button']);
        $this->add_responsive_control('button_padding', ['label' => 'Padding', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .upt-card-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('button_transition', ['label' => 'Duração da Transição (Hover)', 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['ms' => ['min' => 0, 'max' => 2000, 'step' => 50]], 'default' => ['unit' => 'ms', 'size' => 300], 'selectors' => ['{{WRAPPER}} .upt-card-button' => 'transition: all {{SIZE}}ms ease;']]);
        $this->start_controls_tabs('button_tabs');
        $this->start_controls_tab('button_normal', ['label' => 'Normal']);
        $this->add_control('button_color', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-card-button' => 'color: {{VALUE}};']]);
        $this->add_control('button_bg_color', ['label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-card-button' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'button_border', 'selector' => '{{WRAPPER}} .upt-card-button']);
        $this->add_control('button_border_radius', ['label' => 'Arredondamento', 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 100]], 'selectors' => ['{{WRAPPER}} .upt-card-button' => 'border-radius: {{SIZE}}px;']]);
        $this->end_controls_tab();
        $this->start_controls_tab('button_hover', ['label' => 'Hover']);
        $this->add_control('button_color_hover', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-card-button:hover' => 'color: {{VALUE}};']]);
        $this->add_control('button_bg_color_hover', ['label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-card-button:hover' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'button_border_hover', 'selector' => '{{WRAPPER}} .upt-card-button:hover']);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_pagination',
        [
            'label' => 'Estilo da Paginação',
            'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => [
                'enable_pagination' => 'yes',
            ],
        ]
        );
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'pagination_typography', 'selector' => '{{WRAPPER}} .upt-pagination-wrapper .page-numbers']);
        $this->add_responsive_control('pagination_gap', ['label' => 'Espaçamento', 'type' => \Elementor\Controls_Manager::SLIDER, 'selectors' => ['{{WRAPPER}} .upt-pagination-wrapper' => 'gap: {{SIZE}}{{UNIT}};']]);
        $this->add_responsive_control('pagination_border_radius', ['label' => 'Arredondamento da Borda', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'selectors' => ['{{WRAPPER}} .upt-pagination-wrapper .page-numbers' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);

        $this->start_controls_tabs('pagination_tabs_style');

        $this->start_controls_tab('pagination_tab_normal', ['label' => 'Normal']);
        $this->add_control('pagination_text_color', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-pagination-wrapper .page-numbers' => 'color: {{VALUE}};']]);
        $this->add_control('pagination_bg_color', ['label' => 'Cor do Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-pagination-wrapper .page-numbers' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'pagination_border', 'selector' => '{{WRAPPER}} .upt-pagination-wrapper .page-numbers']);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'pagination_box_shadow', 'selector' => '{{WRAPPER}} .upt-pagination-wrapper .page-numbers']);
        $this->end_controls_tab();

        $this->start_controls_tab('pagination_tab_hover', ['label' => 'Hover']);
        $this->add_control('pagination_hover_text_color', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-pagination-wrapper .page-numbers:not(.current):hover' => 'color: {{VALUE}};']]);
        $this->add_control('pagination_hover_bg_color', ['label' => 'Cor do Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-pagination-wrapper .page-numbers:not(.current):hover' => 'background-color: {{VALUE}};']]);
        $this->add_control(
            'pagination_hover_border_color_custom',
        [
            'label' => 'Cor da Borda (Hover - Números)',
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .upt-pagination-wrapper .page-numbers:not(.current):not(.prev):not(.next):hover' => 'border-color: {{VALUE}};',
            ],
        ]
        );
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'pagination_hover_border', 'selector' => '{{WRAPPER}} .upt-pagination-wrapper .page-numbers:not(.current):hover']);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'pagination_box_shadow_hover', 'selector' => '{{WRAPPER}} .upt-pagination-wrapper .page-numbers:not(.current):hover']);
        $this->end_controls_tab();

        $this->start_controls_tab('pagination_tab_active', ['label' => 'Ativo']);
        $this->add_control('pagination_active_text_color', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-pagination-wrapper .page-numbers.current' => 'color: {{VALUE}};']]);
        $this->add_control('pagination_active_bg_color', ['label' => 'Cor do Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-pagination-wrapper .page-numbers.current' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'pagination_active_border', 'selector' => '{{WRAPPER}} .upt-pagination-wrapper .page-numbers.current']);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'pagination_box_shadow_active', 'selector' => '{{WRAPPER}} .upt-pagination-wrapper .page-numbers.current']);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'pagination_infinite_heading',
        [
            'label' => 'Botão de carregamento (Infinito)',
            'type' => \Elementor\Controls_Manager::HEADING,
            'condition' => ['enable_pagination' => 'yes', 'pagination_type' => 'infinite', 'pagination_infinite_trigger' => 'button'],
            'separator' => 'before',
        ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
        [
            'name' => 'pagination_infinite_button_typo',
            'selector' => '{{WRAPPER}} .upt-pagination-infinite .upt-load-more',
            'condition' => ['enable_pagination' => 'yes', 'pagination_type' => 'infinite', 'pagination_infinite_trigger' => 'button'],
        ]
        );

        $this->add_responsive_control(
            'pagination_infinite_button_padding',
        [
            'label' => 'Padding do botão',
            'type' => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'selectors' => [
                '{{WRAPPER}} .upt-pagination-infinite .upt-load-more' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
            'condition' => ['enable_pagination' => 'yes', 'pagination_type' => 'infinite', 'pagination_infinite_trigger' => 'button'],
        ]
        );

        $this->add_control(
            'pagination_infinite_button_color',
        [
            'label' => 'Cor do texto (botão)',
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .upt-pagination-infinite .upt-load-more' => 'color: {{VALUE}};'],
            'condition' => ['enable_pagination' => 'yes', 'pagination_type' => 'infinite', 'pagination_infinite_trigger' => 'button'],
        ]
        );

        $this->add_control(
            'pagination_infinite_button_bg',
        [
            'label' => 'Cor de fundo (botão)',
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .upt-pagination-infinite .upt-load-more' => 'background-color: {{VALUE}};'],
            'condition' => ['enable_pagination' => 'yes', 'pagination_type' => 'infinite', 'pagination_infinite_trigger' => 'button'],
        ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
        [
            'name' => 'pagination_infinite_button_border',
            'selector' => '{{WRAPPER}} .upt-pagination-infinite .upt-load-more',
            'condition' => ['enable_pagination' => 'yes', 'pagination_type' => 'infinite', 'pagination_infinite_trigger' => 'button'],
        ]
        );

        $this->add_responsive_control(
            'pagination_infinite_button_radius',
        [
            'label' => 'Arredondamento (botão)',
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 0, 'max' => 100]],
            'selectors' => ['{{WRAPPER}} .upt-pagination-infinite .upt-load-more' => 'border-radius: {{SIZE}}px;'],
            'condition' => ['enable_pagination' => 'yes', 'pagination_type' => 'infinite', 'pagination_infinite_trigger' => 'button'],
        ]
        );

        $this->add_control(
            'pagination_infinite_button_color_hover',
        [
            'label' => 'Cor do texto (hover)',
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .upt-pagination-infinite .upt-load-more:hover' => 'color: {{VALUE}};'],
            'condition' => ['enable_pagination' => 'yes', 'pagination_type' => 'infinite', 'pagination_infinite_trigger' => 'button'],
        ]
        );

        $this->add_control(
            'pagination_infinite_button_bg_hover',
        [
            'label' => 'Cor de fundo (hover)',
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .upt-pagination-infinite .upt-load-more:hover' => 'background-color: {{VALUE}};'],
            'condition' => ['enable_pagination' => 'yes', 'pagination_type' => 'infinite', 'pagination_infinite_trigger' => 'button'],
        ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
        [
            'name' => 'pagination_infinite_button_shadow',
            'selector' => '{{WRAPPER}} .upt-pagination-infinite .upt-load-more',
            'condition' => ['enable_pagination' => 'yes', 'pagination_type' => 'infinite', 'pagination_infinite_trigger' => 'button'],
        ]
        );
        $this->end_controls_section();

        // ===================================================
        // PAINEL DO UTILIZADOR – Personalização
        // ===================================================
        $this->start_controls_section('section_dashboard', [
            'label' => '🎨 Painel do Utilizador',
        ]);

        $this->add_control('dashboard_heading_info', [
            'label' => '',
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw' => '<div style="background:#f0f4ff;border-left:3px solid #6366f1;padding:10px 12px;border-radius:4px;font-size:13px;line-height:1.5;color:#374151;">
                Estas opções personalizam o layout do painel frontal (widget <strong>Painel</strong> / <code>painel</code>).
            </div>',
            'separator' => 'before',
        ]);

        $this->add_control('dashboard_logo_image', [
            'label' => 'Logo do Painel',
            'type' => \Elementor\Controls_Manager::MEDIA,
            'description' => 'Substitui a inicial do site por uma imagem/logo na barra lateral. Deixe em branco para usar a inicial.',
            'separator' => 'before',
        ]);

        $this->add_control('dashboard_logo_width', [
            'label' => 'Largura do Logo',
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => ['px' => ['min' => 20, 'max' => 200]],
            'default' => ['size' => 32, 'unit' => 'px'],
            'condition' => ['dashboard_logo_image[url]!' => ''],
        ]);

        $this->add_control('dashboard_sidebar_color', [
            'label' => 'Cor de Fundo da Barra Lateral',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#111827',
            'separator' => 'before',
        ]);

        $this->add_control('dashboard_primary_color', [
            'label' => 'Cor de Destaque (Primária)',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#6366f1',
            'description' => 'Usada nos botões, aba ativa, badge, sombra do botão principal.',
        ]);

        $this->add_control('dashboard_header_bg_color', [
            'label' => 'Cor do Cabeçalho',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#ffffff',
            'separator' => 'before',
        ]);

        $this->add_control('dashboard_body_bg_color', [
            'label' => 'Cor de Fundo do Conteúdo',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#f1f5f9',
        ]);

        $this->add_control('dashboard_card_bg_color', [
            'label' => 'Cor de Fundo dos Cards',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#ffffff',
        ]);

        $this->add_control('dashboard_add_button_text', [
            'label' => 'Texto do Botão "Adicionar"',
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Adicionar Novo Item',
            'separator' => 'before',
        ]);

        $this->add_control('dashboard_sidebar_width', [
            'label' => 'Largura da Barra Lateral',
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => ['px' => ['min' => 160, 'max' => 400]],
            'default' => ['size' => 240, 'unit' => 'px'],
            'separator' => 'before',
        ]);

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $template_id = !empty($settings['template_id']) ? (int)$settings['template_id'] : 0;

        if (class_exists('\Elementor\Plugin')) {
            \Elementor\Plugin::instance()->frontend->enqueue_styles();
        }

        if ($template_id) {
            if (class_exists('\Elementor\Core\Files\CSS\Post')) {
                \Elementor\Core\Files\CSS\Post::create($template_id)->enqueue();
            }
            elseif (class_exists('\Elementor\Plugin')) {
                \Elementor\Plugin::instance()->frontend->enqueue_styles();
            }
        }

        $all_schemas_definitions = UPT_Schema_Store::get_schemas();

        $this->add_render_attribute('_wrapper', 'class', 'upt-listing-wrapper');
        $equal_height_enabled = (isset($settings['equal_height']) && $settings['equal_height'] === 'yes');

        if ($equal_height_enabled) {
            $this->add_render_attribute('_wrapper', 'class', 'upt-listing-wrapper--equal-height');

            $grid_id = 'upt-grid-' . $this->get_id();
            $equal_height_css = '
                /* 1. Força o grid nativo (fallback caso o CSS global do Elementor não carregue) */
                #' . $grid_id . '.upt-grid-equal-height { 
                    display: grid;
                    grid-auto-rows: 1fr;
                }

                /* 2. Célula da grid em coluna, ocupando a altura da linha */
                #' . $grid_id . '.upt-grid-equal-height .elementor-grid-item { 
                    display: flex !important;
                    flex-direction: column !important;
                    height: 100% !important;
                }

                /* 3. Wrapper do post (Elementor loop) cresce e vira flex coluna */
                #' . $grid_id . '.upt-grid-equal-height .elementor-grid-item .elementor.e-loop-item { 
                    flex: 1 1 auto !important;
                    height: 100% !important;
                    width: 100% !important;
                    display: flex !important;
                    flex-direction: column !important;
                }

                /* 4. Container do card (neto) preenche tudo - e-con, section ou elementor-element */
                #' . $grid_id . '.upt-grid-equal-height .elementor-grid-item .elementor.e-loop-item > .e-con,
                #' . $grid_id . '.upt-grid-equal-height .elementor-grid-item .elementor.e-loop-item > .elementor-section,
                #' . $grid_id . '.upt-grid-equal-height .elementor-grid-item .elementor.e-loop-item > .elementor-element {
                    flex: 1 1 auto !important;
                    height: auto !important;
                    min-height: 100% !important;
                    display: flex !important;
                    flex-direction: column !important;
                }

                /* 5. Evita colapso interno em flex aninhado */
                #' . $grid_id . '.upt-grid-equal-height .elementor-grid-item * { min-height: 0; }

                /* 6. Cartão manual/link wrapper ainda estica no modo manual */
                #' . $grid_id . '.upt-grid-equal-height .upt-card-link-wrapper,
                #' . $grid_id . '.upt-grid-equal-height .elementor-grid-item > .upt-card,
                #' . $grid_id . '.upt-grid-equal-height .elementor-grid-item .upt-card-link-wrapper > .upt-card {
                    flex: 1 1 auto !important;
                    height: 100% !important;
                    display: flex !important;
                    flex-direction: column !important;
                }

                /* 7. Conteúdo interno do cartão manual segue o stretch */
                #' . $grid_id . '.upt-grid-equal-height .upt-card-content {
                    flex: 1 1 auto !important;
                    display: flex !important;
                    flex-direction: column !important;
                }

                /* 8. Empurra o último elemento para o fundo (botão) */
                #' . $grid_id . '.upt-grid-equal-height .upt-card-content .upt-element-wrapper:last-child {
                    margin-top: auto !important;
                }
            ';

            wp_add_inline_style('upt-style', $equal_height_css);
        }
        $pagination_type = isset($settings['pagination_type']) ? $settings['pagination_type'] : 'numbers';
        $pagination_trigger = isset($settings['pagination_infinite_trigger']) ? $settings['pagination_infinite_trigger'] : 'button';
        $show_arrows = isset($settings['pagination_numbers_show_arrows']) ? $settings['pagination_numbers_show_arrows'] === 'yes' : true;
        $load_more_text = isset($settings['pagination_infinite_button_text']) ? $settings['pagination_infinite_button_text'] : __('Carregar mais', 'upt');
        $ppp_data = $settings['enable_pagination'] === 'yes' ? $settings['posts_per_page'] : -1;
        $this->add_render_attribute('_wrapper', 'data-posts-per-page', $ppp_data);
        $this->add_render_attribute('_wrapper', 'data-pagination-type', esc_attr($pagination_type));
        $this->add_render_attribute('_wrapper', 'data-infinite-trigger', esc_attr($pagination_trigger));
?>
        <div <?php echo $this->get_render_attribute_string('_wrapper'); ?>>
            <style>
                .upt-button-align-left .upt-button-wrapper { text-align: left; }
                .upt-button-align-center .upt-button-wrapper { text-align: center; }
                .upt-button-align-right .upt-button-wrapper { text-align: right; }
                .upt-button-align-justify .upt-button-wrapper { display: flex; }
                .upt-button-align-justify .upt-card-button { flex-grow: 1; text-align: center; }
            </style>
            <?php if ($equal_height_enabled && !empty($equal_height_css)): ?>
                <style><?php echo $equal_height_css; ?></style>
            <?php
        endif; ?>
            <?php
        $paged = (get_query_var('paged')) ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 1);

        $ppp = $settings['enable_pagination'] === 'yes' ? $settings['posts_per_page'] : -1;
        if ($settings['enable_pagination'] !== 'yes' && $settings['posts_per_page'] > 0) {
            $ppp = $settings['posts_per_page'];
        }
        $selected_schemas = (!empty($settings['schema_filter']) && is_array($settings['schema_filter'])) ? $settings['schema_filter'] : [];
        $is_wp_posts_mode = in_array('wp_post', $selected_schemas, true);
        if ($is_wp_posts_mode) {
            // Se "Post (WordPress)" estiver selecionado, prioriza posts nativos e ignora outros esquemas.
            $selected_schemas = ['wp_post'];
        }

        $args = [
            'post_type' => $is_wp_posts_mode ? 'post' : 'catalog_item',
            'post_status' => 'publish',
            'posts_per_page' => $ppp,
            'paged' => $paged,
        ];

        $search_term = isset($_GET['s_upt']) ? sanitize_text_field($_GET['s_upt']) : '';
        $search_target_raw = isset($_GET['upt_target']) ? sanitize_text_field($_GET['upt_target']) : '';
        $search_targets = [];
        if (!empty($search_target_raw)) {
            $parts = explode(',', $search_target_raw);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p === '') {
                    continue;
                }
                $search_targets[] = $p;
            }
        }
        $tax_query = [];
        if (!$is_wp_posts_mode && !empty($selected_schemas)) {
            $tax_query[] = [
                'taxonomy' => 'catalog_schema',
                'field' => 'slug',
                'terms' => $selected_schemas,
            ];
        }
        if (isset($_GET['upt_category']) && !empty($_GET['upt_category'])) {
            $selected_cat_id = absint($_GET['upt_category']);
            $cat_taxonomy = $is_wp_posts_mode ? 'category' : 'catalog_category';

            // Filtra por categoria incluindo automaticamente todos os descendentes.
            // IMPORTANTE: cobre o caso em que os itens estão apenas nas subcategorias da categoria escolhida.
            $tax_query[] = [
                'taxonomy' => $cat_taxonomy,
                'field' => 'term_id',
                'terms' => [$selected_cat_id],
                'include_children' => true,
                'operator' => 'IN',
            ];
        }


        if (count($tax_query) > 1) {
            $tax_query['relation'] = 'AND';
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        if (isset($_GET['upt_meta_filter']) && $_GET['upt_meta_filter'] !== '' && isset($_GET['upt_meta_key']) && $_GET['upt_meta_key'] !== '') {
            $meta_key = sanitize_text_field($_GET['upt_meta_key']);
            $meta_val = sanitize_text_field($_GET['upt_meta_filter']);
            $args['meta_query'] = [
                [
                    'key' => $meta_key,
                    'value' => $meta_val,
                    'compare' => '=',
                ],
            ];
        } else {
            $fixed_field_id = isset($settings['builtin_meta_filter_field']) ? $settings['builtin_meta_filter_field'] : '';
            $fixed_mode = isset($settings['builtin_meta_filter_mode']) ? $settings['builtin_meta_filter_mode'] : 'interactive';
            $fixed_value = isset($settings['builtin_meta_filter_fixed_value']) ? $settings['builtin_meta_filter_fixed_value'] : '';
            if ($fixed_mode === 'fixed' && !empty($fixed_field_id) && !empty($fixed_value)) {
                $args['meta_query'] = [
                    [
                        'key' => $fixed_field_id,
                        'value' => $fixed_value,
                        'compare' => '=',
                    ],
                ];
            }
        }

        $use_targeted_search = (!empty($search_term) && !empty($search_targets) && class_exists('UPT_Ajax') && method_exists('UPT_Ajax', 'get_ids_for_targeted_search'));

        $schema_slugs_for_order = $is_wp_posts_mode ? [] : $this->get_schema_slugs_for_query($settings);

        if (!empty($schema_slugs_for_order)) {
            foreach ($schema_slugs_for_order as $schema_slug_order) {
                $this->ensure_manual_order_for_schema($schema_slug_order);
            }

            $args['meta_key'] = 'upt_manual_order';
            $args['orderby'] = [
                'meta_value_num' => 'ASC',
                'date' => 'DESC',
            ];
        }

        if ($use_targeted_search) {
            $matching_ids = [];
            foreach ($search_targets as $target) {
                $matching_ids = array_merge($matching_ids, UPT_Ajax::get_ids_for_targeted_search($search_term, $target, $tax_query));
            }
            $matching_ids = array_values(array_unique($matching_ids));

            if (!empty($matching_ids)) {
                $args['post__in'] = $matching_ids;
                $args['orderby'] = 'post__in';
            }
            else {
                // Fallback: switch to default search when no targeted hits.
                $use_targeted_search = false;
            }

            if ($use_targeted_search) {
                $query = new WP_Query($args);
            }
        }
        else {
            if (!empty($search_term)) {
                $args['s'] = $search_term;
            }

            $search_extended_closure = function ($search, $wp_query) {
                global $wpdb;

                if (empty($wp_query->get('s'))) {
                    return $search;
                }

                $s = $wp_query->get('s');
                $like_term = '%' . $wpdb->esc_like($s) . '%';

                return $wpdb->prepare(" AND ({$wpdb->posts}.post_title LIKE %s)", $like_term);
            };

            if (!empty($search_term)) {
                add_filter('posts_search', $search_extended_closure, 10, 2);
            }

            $query = new WP_Query($args);

            if (!empty($search_term)) {
                remove_filter('posts_search', $search_extended_closure, 10);
            }
        }

        if ($query->have_posts()) {
            $schema_filter_json = !empty($selected_schemas) ? json_encode($selected_schemas) : '[]';

            // Built-in Filter Rendering
            if (isset($settings['enable_builtin_filter']) && $settings['enable_builtin_filter'] === 'yes') {
                $hide_empty_builtin = isset($settings['builtin_filter_hide_empty']) && $settings['builtin_filter_hide_empty'] === 'yes';
                $show_subs_builtin = isset($settings['builtin_filter_show_subcategories']) && $settings['builtin_filter_show_subcategories'] === 'yes';
                $all_text_builtin = !empty($settings['builtin_filter_all_text']) ? $settings['builtin_filter_all_text'] : 'Todas';
                $current_cat_builtin = isset($_GET['upt_category']) ? (int)$_GET['upt_category'] : 0;

                $builtin_terms = [];
                // Se $is_wp_posts_mode = true, usamos categorias do WordPress, senão catalog_category
                $tax_for_builtin = $is_wp_posts_mode ? 'category' : 'catalog_category';

                if (!$is_wp_posts_mode && !empty($selected_schemas)) {
                    // Collect categories belonging to the selected schemas
                    foreach ($selected_schemas as $schema_slug) {
                        $schema_term = get_term_by('slug', $schema_slug, $tax_for_builtin);
                        if ($schema_term && !is_wp_error($schema_term)) {
                            $cats = get_terms([
                                'taxonomy' => $tax_for_builtin,
                                'hide_empty' => $hide_empty_builtin,
                                'parent' => $schema_term->term_id,
                                'orderby' => 'name',
                                'order' => 'ASC'
                            ]);
                            if (!is_wp_error($cats) && !empty($cats)) {
                                foreach ($cats as $cat) {
                                    $cat->_upt_level = 0;
                                    $builtin_terms[] = $cat;
                                    if ($show_subs_builtin) {
                                        $subs = get_terms([
                                            'taxonomy' => $tax_for_builtin,
                                            'hide_empty' => $hide_empty_builtin,
                                            'parent' => $cat->term_id,
                                            'orderby' => 'name',
                                            'order' => 'ASC'
                                        ]);
                                        if (!is_wp_error($subs) && !empty($subs)) {
                                            foreach ($subs as $sub) {
                                                $sub->_upt_level = 1;
                                                $builtin_terms[] = $sub;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                else {
                    // Global collection if no specific schemas or if WP Posts
                    $cats = get_terms([
                        'taxonomy' => $tax_for_builtin,
                        'hide_empty' => $hide_empty_builtin,
                        'parent' => 0,
                        'orderby' => 'name',
                        'order' => 'ASC'
                    ]);
                    if (!is_wp_error($cats) && !empty($cats)) {
                        // Filter out terms that act as schema parents (if it's not WP Posts mode)
                        $exclude_ids = [];
                        if (!$is_wp_posts_mode && class_exists('UPT_Schema_Store')) {
                            $schemas = UPT_Schema_Store::get_schemas();
                            foreach ($schemas as $slug => $data) {
                                $pt = get_term_by('slug', $slug, $tax_for_builtin);
                                if ($pt && !is_wp_error($pt)) {
                                    $exclude_ids[] = $pt->term_id;
                                }
                            }
                        }
                        foreach ($cats as $cat) {
                            if (!in_array((int)$cat->term_id, $exclude_ids, true)) {
                                $cat->_upt_level = 0;
                                $builtin_terms[] = $cat;
                                if ($show_subs_builtin) {
                                    $subs = get_terms([
                                        'taxonomy' => $tax_for_builtin,
                                        'hide_empty' => $hide_empty_builtin,
                                        'parent' => $cat->term_id,
                                        'orderby' => 'name',
                                        'order' => 'ASC'
                                    ]);
                                    if (!is_wp_error($subs) && !empty($subs)) {
                                        foreach ($subs as $sub) {
                                            $sub->_upt_level = 1;
                                            $builtin_terms[] = $sub;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($builtin_terms)) {
                    // Render Filter Bar
                    $grid_id = 'upt-grid-' . $this->get_id();
                    $align_builtin = isset($settings['builtin_filter_align']) ? $settings['builtin_filter_align'] : 'center';

                    echo '<div class="upt-category-filter-wrapper upt-builtin-filter-container" ' .
                        'id="upt-filter-builtin-' . esc_attr($this->get_id()) . '" ' .
                        'data-filter-role="grid" ' .
                        'data-target-id="' . esc_attr($grid_id) . '" ' .
                        'data-template-id="' . esc_attr($template_id) . '" ' .
                        'data-behavior="ajax" ' .
                        'data-default-term-id="0" ' .
                        'style="justify-content: ' . esc_attr($align_builtin) . ';">' .
                        '<ul class="upt-category-filter-list upt-builtin-filter-list" data-scroll-enabled="1">';

                    echo '<li><a href="#" class="upt-category-filter-item upt-builtin-pill ' . ($current_cat_builtin === 0 ? 'active' : '') . '" data-term-id="0">' . esc_html($all_text_builtin) . '</a></li>';
                    foreach ($builtin_terms as $t) {
                        $level = isset($t->_upt_level) ? (int)$t->_upt_level : 0;
                        $label = $level > 0 ? '— ' . $t->name : $t->name;
                        $is_active = ((int)$t->term_id === $current_cat_builtin);
                        echo '<li><a href="#" class="upt-category-filter-item upt-builtin-pill ' . ($is_active ? 'active' : '') . '" data-term-id="' . esc_attr($t->term_id) . '">' . esc_html($label) . '</a></li>';
                    }

                    $meta_field_id = isset($settings['builtin_meta_filter_field']) ? $settings['builtin_meta_filter_field'] : '';
                    $meta_filter_mode = isset($settings['builtin_meta_filter_mode']) ? $settings['builtin_meta_filter_mode'] : 'interactive';

                    if (!empty($meta_field_id) && $meta_filter_mode === 'interactive') {
                        $meta_field_options = [];
                        $schemas = UPT_Schema_Store::get_schemas();
                        if (!empty($schemas)) {
                            foreach ($schemas as $schema_data) {
                                if (isset($schema_data['fields']) && is_array($schema_data['fields'])) {
                                    foreach ($schema_data['fields'] as $sf) {
                                        if (isset($sf['id']) && $sf['id'] === $meta_field_id && !empty($sf['options'])) {
                                            $raw_opts = $sf['options'];
                                            $opt_list = is_string($raw_opts) ? explode('|', $raw_opts) : (is_array($raw_opts) ? $raw_opts : []);
                                            foreach ($opt_list as $opt) {
                                                $opt = trim($opt);
                                                if ($opt !== '') {
                                                    $meta_field_options[$opt] = $opt;
                                                }
                                            }
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }

                        if (!empty($meta_field_options)) {
                            $meta_filter_label = !empty($settings['builtin_meta_filter_label']) ? $settings['builtin_meta_filter_label'] : '';
                            $meta_filter_all = !empty($settings['builtin_meta_filter_all_text']) ? $settings['builtin_meta_filter_all_text'] : 'Todos';
                            $current_meta_val = isset($_GET['upt_meta_filter']) ? sanitize_text_field($_GET['upt_meta_filter']) : '';
                            $grid_id_for_meta = 'upt-grid-' . $this->get_id();

                            echo '<li class="upt-meta-filter-separator" aria-hidden="true"></li>';
                            echo '<li class="upt-meta-filter-group-label">' . esc_html($meta_filter_label) . '</li>';
                            echo '<li><a href="#" class="upt-meta-filter-pill upt-builtin-pill ' . ($current_meta_val === '' ? 'active' : '') . '" data-meta-field="' . esc_attr($meta_field_id) . '" data-meta-value="" data-grid-id="' . esc_attr($grid_id_for_meta) . '">' . esc_html($meta_filter_all) . '</a></li>';
                            foreach ($meta_field_options as $opt_val => $opt_label) {
                                $is_active = ($current_meta_val === $opt_val);
                                echo '<li><a href="#" class="upt-meta-filter-pill upt-builtin-pill ' . ($is_active ? 'active' : '') . '" data-meta-field="' . esc_attr($meta_field_id) . '" data-meta-value="' . esc_attr($opt_val) . '" data-grid-id="' . esc_attr($grid_id_for_meta) . '">' . esc_html($opt_label) . '</a></li>';
                            }
                        }
                    }

                    echo '</ul></div>';
                }
            }
            // End Built-in Meta Filter Rendering

            $grid_classes = 'elementor-loop-container elementor-grid';
            $columns_desktop = isset($settings['columns']) && $settings['columns'] ? (int)$settings['columns'] : 3;
            $columns_tablet = isset($settings['columns_tablet']) && $settings['columns_tablet'] ? (int)$settings['columns_tablet'] : $columns_desktop;
            $columns_mobile = isset($settings['columns_mobile']) && $settings['columns_mobile'] ? (int)$settings['columns_mobile'] : 1;

            // Replica as classes de grid responsivo do Elementor nativo
            $grid_classes .= ' elementor-grid-' . $columns_desktop;
            $grid_classes .= ' elementor-grid-tablet-' . $columns_tablet;
            $grid_classes .= ' elementor-grid-mobile-' . $columns_mobile;

            $grid_style = '';
            if ($equal_height_enabled) {
                $grid_style = 'grid-auto-rows:1fr;';
            }
            if ($equal_height_enabled) {
                $grid_classes .= ' upt-grid-equal-height';
            }

            echo '<div class="' . esc_attr($grid_classes) . '" id="upt-grid-' . esc_attr($this->get_id()) . '" data-schema-filter="' . esc_attr($schema_filter_json) . '" data-template-id="' . esc_attr($template_id) . '"' . ($grid_style ? ' style="' . esc_attr($grid_style) . '"' : '') . '>';

            while ($query->have_posts()) {
                $query->the_post();
                // IMPORTANT:
                // Elementor (loop/grid) pode renderizar itens como <article class="... e-loop-item ...">.
                // Neste widget, precisamos manter a mesma estrutura usada no AJAX (get_builder_content)
                // para garantir que o CSS do template seja aplicado já no carregamento inicial.
                echo '<div class="elementor-grid-item">';
                if (!empty($template_id)) {
                    // Mesmo método usado no AJAX (UPT_Ajax::get_items_list_html).
                    echo \Elementor\Plugin::instance()->frontend->get_builder_content($template_id, true);
                }
                else {
                    $post_id = get_the_ID();
                    $post_schemas = get_the_terms($post_id, 'catalog_schema');
                    $schema_slug = !empty($post_schemas) ? $post_schemas[0]->slug : '';
                    $is_card_linked = ($settings['card_wrapper_link'] === 'yes');

                    if ($is_card_linked) {
                        echo '<a href="' . get_permalink() . '" class="upt-card-link-wrapper">';
                    }
?>
                        <div class="upt-card">
                            <div class="upt-card-content">
                                <?php
                    if (!empty($settings['card_structure'])) {
                        foreach ($settings['card_structure'] as $item) {
                            $style = '';
                            $element_type = $item['element'];
                            if ($element_type === 'individual_meta' || $element_type === 'category') {
                                if (!empty($item['meta_color']))
                                    $style .= 'color: ' . esc_attr($item['meta_color']) . ' !important;';
                                if (!empty($item['meta_font_weight']) && $item['meta_font_weight'] !== '')
                                    $style .= 'font-weight: ' . esc_attr($item['meta_font_weight']) . ' !important;';
                                if (!empty($item['meta_text_transform']) && $item['meta_text_transform'] !== '')
                                    $style .= 'text-transform: ' . esc_attr($item['meta_text_transform']) . ' !important;';
                                if (!empty($item['meta_font_size']['size']))
                                    $style .= 'font-size: ' . esc_attr($item['meta_font_size']['size']) . esc_attr($item['meta_font_size']['unit']) . ' !important;';
                            }
                            echo '<div class="upt-element-wrapper">';
                            switch ($element_type) {
                                case 'image':
                                    // 1) Se houver imagem nativa (thumbnail), usa normalmente.
                                    if (has_post_thumbnail()) {
                                        $image_html = get_the_post_thumbnail($post_id, 'medium_large');
                                        echo $is_card_linked
                                            ? '<a href="' . get_permalink() . '" class="upt-card-thumbnail">' . $image_html . '</a>'
                                            : '<div class="upt-card-thumbnail">' . $image_html . '</div>';
                                    }
                                    else {
                                        // 2) Se NÃO houver campo nativo de imagem no schema,
                                        //    mas houver pelo menos um campo de imagem personalizado,
                                        //    usa esse campo como visualização do card.
                                        $fallback_image_id = 0;
                                        if (!empty($schema_slug) && isset($all_schemas_definitions[$schema_slug]['fields'])) {
                                            $fields = $all_schemas_definitions[$schema_slug]['fields'];
                                            $has_core_featured = false;
                                            $first_image_field_id = '';
                                            foreach ($fields as $field_def) {
                                                if (empty($field_def['type']) || empty($field_def['id'])) {
                                                    continue;
                                                }
                                                if ($field_def['type'] === 'core_featured_image') {
                                                    $has_core_featured = true;
                                                    break;
                                                }
                                                if ($first_image_field_id === '' && $field_def['type'] === 'image') {
                                                    $first_image_field_id = $field_def['id'];
                                                }
                                            }
                                            if (!$has_core_featured && $first_image_field_id !== '') {
                                                $meta_val = get_post_meta($post_id, $first_image_field_id, true);
                                                $fallback_image_id = is_array($meta_val) ? 0 : absint($meta_val);
                                            }
                                        }

                                        if ($fallback_image_id > 0) {
                                            $image_html = wp_get_attachment_image($fallback_image_id, 'medium_large');
                                            if ($image_html) {
                                                echo $is_card_linked
                                                    ? '<a href="' . get_permalink() . '" class="upt-card-thumbnail">' . $image_html . '</a>'
                                                    : '<div class="upt-card-thumbnail">' . $image_html . '</div>';
                                            }
                                        }
                                    }
                                    break;
                                case 'title':
                                    $title_html = get_the_title();
                                    echo '<h3 class="upt-card-title">';
                                    echo $is_card_linked ? $title_html : '<a href="' . get_permalink() . '">' . $title_html . '</a>';
                                    echo '</h3>';
                                    break;
                                case 'button':
                                    $button_text = !empty($item['button_text']) ? $item['button_text'] : 'Ver Detalhes';
                                    $href = get_permalink();
                                    if (isset($item['button_action']) && $item['button_action'] === 'whatsapp' && !empty($item['whatsapp_number'])) {
                                        $message = !empty($item['whatsapp_message']) ? $item['whatsapp_message'] : 'Olá, tenho interesse no item:';
                                        $full_message = $message . ' ' . get_the_title();
                                        $href = 'https://wa.me/' . preg_replace('/\D/', '', $item['whatsapp_number']) . '?text=' . rawurlencode($full_message);
                                    }
                                    echo '<div class="upt-button-wrapper"><a href="' . esc_url($href) . '" class="upt-card-button" ' . (isset($item['button_action']) && $item['button_action'] === 'whatsapp' ? 'target="_blank"' : '') . '>' . esc_html($button_text) . '</a></div>';
                                    break;
                                case 'category':
                                    if (get_post_type($post_id) === 'post') {
                                        $cats = get_the_category($post_id);
                                        if (!empty($cats) && !is_wp_error($cats)) {
                                            echo '<div class="upt-meta-item upt-category-list" style="' . $style . '">' . esc_html(implode(', ', wp_list_pluck($cats, 'name'))) . '</div>';
                                        }
                                    }
                                    else {
                                        $terms = get_the_terms($post_id, 'catalog_category');
                                        if (!empty($terms))
                                            echo '<div class="upt-meta-item upt-category-list" style="' . $style . '">' . esc_html(implode(', ', wp_list_pluck($terms, 'name'))) . '</div>';
                                    }
                                    break;
                                case 'individual_meta':
                                    if (isset($item['meta_key'])) {
                                        $field_key = $item['meta_key'];
                                        $value = get_post_meta($post_id, $field_key, true);
                                        if (!empty($value)) {
                                            $field_def = null;
                                            if (isset($all_schemas_definitions[$schema_slug]['fields'])) {
                                                foreach ($all_schemas_definitions[$schema_slug]['fields'] as $field) {
                                                    if ($field['id'] === $field_key) {
                                                        $field_def = $field;
                                                        break;
                                                    }
                                                }
                                            }
                                            if (isset($field_def['type']) && $field_def['type'] === 'gallery') {
                                                echo '<div class="upt-meta-item upt-gallery-display" style="' . $style . '">';
                                                $image_ids = explode(',', $value);
                                                foreach ($image_ids as $image_id) {
                                                    echo wp_get_attachment_image($image_id, 'thumbnail', false, ['style' => 'width: 50px; height: 50px; margin-right: 5px; border-radius: 4px;']);
                                                }
                                                echo '</div>';
                                            }
                                            else {
                                                echo '<div class="upt-meta-item" style="' . $style . '">';
                                                if (!empty($item['meta_prefix']))
                                                    echo '<span class="meta-prefix">' . esc_html($item['meta_prefix']) . '</span>';
                                                echo esc_html($value);
                                                if (!empty($item['meta_suffix']))
                                                    echo '<span class="meta-suffix">' . esc_html($item['meta_suffix']) . '</span>';
                                                echo '</div>';
                                            }
                                        }
                                    }
                                    break;
                            }
                            echo '</div>';
                        }
                    }
?>
                            </div>
                        </div>
                        <?php
                    if ($is_card_linked) {
                        echo '</a>';
                    }
                }
                echo '</div>';
            }
            echo '</div>';
        }
        else {
            echo '<p>Nenhum item encontrado.</p>';
        }

        if ($settings['enable_pagination'] === 'yes' && $query->max_num_pages > 1) {
            $pagination_add_args = [];
            if (!empty($search_term)) {
                $pagination_add_args['s_upt'] = $search_term;
            }
            if (!empty($search_target_raw)) {
                $pagination_add_args['upt_target'] = $search_target_raw;
            }
            if (isset($_GET['upt_category']) && !empty($_GET['upt_category'])) {
                $pagination_add_args['upt_category'] = absint($_GET['upt_category']);
            }
            if (isset($_GET['upt_meta_filter']) && $_GET['upt_meta_filter'] !== '') {
                $pagination_add_args['upt_meta_filter'] = sanitize_text_field($_GET['upt_meta_filter']);
            }
            if (isset($_GET['upt_meta_key']) && $_GET['upt_meta_key'] !== '') {
                $pagination_add_args['upt_meta_key'] = sanitize_text_field($_GET['upt_meta_key']);
            }

            if (empty($pagination_add_args['upt_meta_key']) && empty($pagination_add_args['upt_meta_filter'])) {
                $pag_fixed_field = isset($settings['builtin_meta_filter_field']) ? $settings['builtin_meta_filter_field'] : '';
                $pag_fixed_mode = isset($settings['builtin_meta_filter_mode']) ? $settings['builtin_meta_filter_mode'] : 'interactive';
                $pag_fixed_value = isset($settings['builtin_meta_filter_fixed_value']) ? $settings['builtin_meta_filter_fixed_value'] : '';
                if ($pag_fixed_mode === 'fixed' && !empty($pag_fixed_field) && !empty($pag_fixed_value)) {
                    $pagination_add_args['upt_meta_key'] = $pag_fixed_field;
                    $pagination_add_args['upt_meta_filter'] = $pag_fixed_value;
                }
            }

            $pagination_links = paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'total' => $query->max_num_pages,
                'current' => max(1, $paged),
                'prev_text' => '«',
                'next_text' => '»',
                'add_args' => $pagination_add_args,
                'type' => 'array',
            ]);

            $pagination_type = isset($settings['pagination_type']) ? $settings['pagination_type'] : 'numbers';
            $pagination_options = [
                'show_arrows' => $show_arrows,
                'infinite_trigger' => $pagination_trigger,
                'load_more_text' => $load_more_text,
            ];
            $pagination_markup = self::build_pagination_markup($pagination_links, $pagination_type, max(1, $paged), (int)$query->max_num_pages, $pagination_options);

            if (!empty($pagination_markup)) {
                echo '<div class="upt-pagination-wrapper" data-pagination-type="' . esc_attr($pagination_type) . '" data-current-page="' . esc_attr(max(1, $paged)) . '" data-total-pages="' . esc_attr((int)$query->max_num_pages) . '" data-infinite-trigger="' . esc_attr($pagination_trigger) . '">';
                echo $pagination_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '</div>';
            }
        }

        wp_reset_postdata();
?>
        </div>
        <?php
    }
}
