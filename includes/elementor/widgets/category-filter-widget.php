<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Category_Filter_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'upt_category_filter'; }
    public function get_title() { return 'Filtro de Categorias upt'; }
    public function get_icon() { return 'eicon-filter'; }
    public function get_categories() { return [ 'upt' ]; }

    public function get_script_depends() {
        return [ 'upt-main-js' ];
    }

    public function get_style_depends() {
        return [ 'upt-style' ];
    }

    private function get_detected_listing_targets() {
        $options = ['' => '— Selecione —'];

        if ( ! class_exists( '\Elementor\Plugin' ) ) {
            return $options;
        }

        $post_id = get_the_ID();
        if ( isset( $_REQUEST['editor_post_id'] ) ) {
            $post_id = absint( $_REQUEST['editor_post_id'] );
        } elseif ( isset( $_REQUEST['post_id'] ) ) {
            $post_id = absint( $_REQUEST['post_id'] );
        } elseif ( isset( $_REQUEST['post'] ) ) {
            $post_id = absint( $_REQUEST['post'] );
        }

        if ( ! $post_id ) {
            return $options;
        }

        $document = \Elementor\Plugin::instance()->documents->get( $post_id );
        if ( ! $document && method_exists( \Elementor\Plugin::instance()->documents, 'get_current' ) ) {
            $document = \Elementor\Plugin::instance()->documents->get_current();
        }

        if ( ! $document ) {
            return $options;
        }

        $elements = $document->get_elements_data();
        $found = [];
        $this->walk_elements_for_listings( $elements, $found );

        if ( empty( $found ) ) {
            return $options;
        }

        foreach ( $found as $id => $label ) {
            $options[ $id ] = $label;
        }

        unset( $options[ 'upt-filter-' . $this->get_id() ] );

    return $options;
    }

    
private function get_detected_filter_targets() {
    $options = ['' => '— Selecione —'];

    if ( ! class_exists( '\Elementor\Plugin' ) ) {
        return $options;
    }

    $post_id = get_the_ID();
    if ( isset( $_REQUEST['editor_post_id'] ) ) {
        $post_id = absint( $_REQUEST['editor_post_id'] );
    } elseif ( isset( $_REQUEST['post_id'] ) ) {
        $post_id = absint( $_REQUEST['post_id'] );
    } elseif ( isset( $_REQUEST['post'] ) ) {
        $post_id = absint( $_REQUEST['post'] );
    }

    if ( ! $post_id ) {
        return $options;
    }

    $document = \Elementor\Plugin::instance()->documents->get( $post_id );
    if ( ! $document && method_exists( \Elementor\Plugin::instance()->documents, 'get_current' ) ) {
        $document = \Elementor\Plugin::instance()->documents->get_current();
    }

    if ( ! $document ) {
        return $options;
    }

    $elements = $document->get_elements_data();
    $found = [];
    $this->walk_elements_for_filters( $elements, $found );

    if ( empty( $found ) ) {
        return $options;
    }

    foreach ( $found as $id => $label ) {
        $options[ $id ] = $label;
    }

    return $options;
}

private function walk_elements_for_filters( $elements, &$found ) {
    if ( empty( $elements ) || ! is_array( $elements ) ) {
        return;
    }

    foreach ( $elements as $element ) {
        if ( isset( $element['elType'] ) && $element['elType'] === 'widget' && isset( $element['widgetType'] ) && $element['widgetType'] === 'upt_category_filter' ) {
            $element_id = isset( $element['id'] ) ? $element['id'] : '';
            $css_id     = '';

            if ( isset( $element['settings']['_element_id'] ) && $element['settings']['_element_id'] ) {
                $css_id = $element['settings']['_element_id'];
            } elseif ( isset( $element['settings']['element_id'] ) && $element['settings']['element_id'] ) {
                $css_id = $element['settings']['element_id'];
            }

            $target_id = $css_id ? $css_id : 'upt-filter-' . $element_id;
            $label     = 'Filtro #' . $element_id . ' (' . $target_id . ')';

            $found[ $target_id ] = $label;
        }

        if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
            $this->walk_elements_for_filters( $element['elements'], $found );
        }
    }
}

private function walk_elements_for_listings( $elements, &$found ) {
        if ( empty( $elements ) || ! is_array( $elements ) ) {
            return;
        }

        foreach ( $elements as $element ) {
            if ( isset( $element['elType'] ) && $element['elType'] === 'widget' && isset( $element['widgetType'] ) && $element['widgetType'] === 'upt_listing_grid' ) {
                $element_id = isset( $element['id'] ) ? $element['id'] : '';
                $css_id     = '';

                if ( isset( $element['settings']['_element_id'] ) && $element['settings']['_element_id'] ) {
                    $css_id = $element['settings']['_element_id'];
                } elseif ( isset( $element['settings']['element_id'] ) && $element['settings']['element_id'] ) {
                    $css_id = $element['settings']['element_id'];
                }

                $target_id = $css_id ? $css_id : 'upt-grid-' . $element_id;
                $label     = 'Grade #' . $element_id . ' (' . $target_id . ')';

                $found[ $target_id ] = $label;
            }

            if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
                $this->walk_elements_for_listings( $element['elements'], $found );
            }
        }
    }

    private function get_all_categories_options() {
        $options = [ '0' => 'Todas' ];
        $terms = get_terms( [
            'taxonomy'   => 'catalog_category',
            'hide_empty' => false,
        ] );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return $options;
        }

        foreach ( $terms as $term ) {
            $options[ (string) $term->term_id ] = $term->name;
        }

        return $options;
    }

    private function get_all_loop_templates() {
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
        $options = ['' => '— Selecione —'];
        if ($query->have_posts()) {
            while($query->have_posts()) {
                $query->the_post();
                $options[get_the_ID()] = get_the_title();
            }
        }
        wp_reset_postdata();
        return $options;
    }

    private function get_all_schemas() {
        $options = [];
        $terms = get_terms(['taxonomy' => 'catalog_schema', 'hide_empty' => false]);
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $options[$term->slug] = $term->name;
            }
        }
        return $options;
    }

    protected function _register_controls() {
        $this->start_controls_section('section_config', ['label' => 'Configuração do Filtro']);
$this->add_control('filter_role', [
    'label' => 'Função do Filtro',
    'type' => \Elementor\Controls_Manager::SELECT,
    'default' => 'grid',
    'options' => [
        'grid'   => 'Filtrar Grade (Padrão)',
        'parent' => 'Filtro Pai (Controla Filtro Filho)',
        'child'  => 'Filtro Filho (Subcategorias + Filtra Grade)',
    ],
]);


        $this->add_control('target_grid_auto', [
                'label' => 'Grade do Catálogo Detectada',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_detected_listing_targets(),
                'description' => 'Lista automática dos widgets "Grade do Catálogo" encontrados na página. Se vazio, use o campo abaixo para informar o ID manualmente.',
            'condition' => [ 'filter_role!' => 'parent' ],

            ]);

        $this->add_control('target_id', [
            'label' => 'ID de CSS do Grid Alvo',
                'type' => \Elementor\Controls_Manager::TEXT,
            'description' => 'Insira o ID (manual) do widget "Grade do Catálogo" que você quer filtrar. Mantém a alternativa caso a detecção automática não encontre o grid.',
            'condition' => [ 'filter_role!' => 'parent' ],

        ]);

$this->add_control('target_filter_auto', [
    'label' => 'Filtro Filho Detectado',
    'type' => \Elementor\Controls_Manager::SELECT,
    'options' => $this->get_detected_filter_targets(),
    'description' => 'Usado quando a função for "Filtro Pai". Se vazio, informe o ID manualmente abaixo.',
    'condition' => [ 'filter_role' => 'parent' ],
]);

$this->add_control('target_filter_id', [
    'label' => 'ID de CSS do Filtro Filho (Manual)',
    'type' => \Elementor\Controls_Manager::TEXT,
    'placeholder' => 'ex: upt-filter-abc123',
    'condition' => [ 'filter_role' => 'parent' ],
]);

$this->add_control('parent_filter_auto', [
    'label' => 'Filtro Pai Detectado',
    'type' => \Elementor\Controls_Manager::SELECT,
    'options' => $this->get_detected_filter_targets(),
    'description' => 'Usado quando a função for "Filtro Filho". Se vazio, informe o ID manualmente abaixo.',
    'condition' => [ 'filter_role' => 'child' ],
]);

$this->add_control('parent_filter_id', [
    'label' => 'ID de CSS do Filtro Pai (Manual)',
    'type' => \Elementor\Controls_Manager::TEXT,
    'placeholder' => 'ex: upt-filter-xyz789',
    'condition' => [ 'filter_role' => 'child' ],
]);

$this->add_control('child_show_all_option', [
    'label' => 'Filho: Exibir opção "Todas" (categoria pai)',
    'type' => \Elementor\Controls_Manager::SWITCHER,
    'label_on' => 'Sim',
    'label_off' => 'Não',
    'return_value' => 'yes',
    'default' => 'yes',
    'condition' => [ 'filter_role' => 'child' ],
]);

$this->add_control('child_auto_select_first', [
    'label' => 'Filho: Selecionar 1ª subcategoria automaticamente',
    'type' => \Elementor\Controls_Manager::SWITCHER,
    'label_on' => 'Sim',
    'label_off' => 'Não',
    'return_value' => 'yes',
    'default' => 'no',
    'condition' => [ 'filter_role' => 'child' ],
]);

$this->add_control('child_empty_text', [
    'label' => 'Filho: Texto quando não há categoria pai selecionada',
    'type' => \Elementor\Controls_Manager::TEXT,
    'default' => 'Selecione uma categoria acima',
    'condition' => [ 'filter_role' => 'child' ],
]);


        $this->add_control('template_id', [
            'label' => 'Template do Card (Loop Item)',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => $this->get_all_loop_templates(),
            'description' => 'Selecione o template que o grid alvo usa para renderizar os itens.',
            'separator' => 'before',
        ]);
        
        $this->add_control('filter_behavior', [
            'label' => 'Comportamento do Filtro',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'ajax',
            'options' => [
                'ajax' => 'Filtro dinâmico (AJAX)',
                'link' => 'Recarregar página (Link)',
            ],
            'description' => '"Link" recarrega a página com a categoria na URL, útil para compartilhamento.',
            'separator' => 'before',
        ]);

        $this->add_control('schema_source', [
            'label' => 'Filtrar Categorias por Esquema(s)',
            'type' => \Elementor\Controls_Manager::SELECT2,
            'multiple' => true,
            'options' => $this->get_all_schemas(),
            'label_block' => true,
            'description' => 'Selecione um ou mais esquemas para exibir apenas as categorias deles. Se vazio, todas as categorias de nível superior serão exibidas.',
            'separator' => 'before',
        ]);

        $this->add_control('filter_layout', [
            'label' => 'Estilo do Filtro',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'list',
            'options' => [
                'list' => 'Lista',
                'dropdown' => 'Dropdown',
            ],
            'separator' => 'before',
        ]);
        
        $this->add_control('hide_empty_cats', [
            'label' => 'Ocultar Categorias Vazias',
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => 'Sim',
            'label_off' => 'Não',
            'return_value' => 'yes',
            'default' => 'no',
        ]);

        $this->add_control('show_subcategories', [
            'label' => 'Exibir Subcategorias',
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => 'Sim',
            'label_off' => 'Não',
            'return_value' => 'yes',
            // Padrão: mostrar apenas as categorias (filhas diretas do esquema).
            'default' => 'no',
            'description' => 'Se desativado, apenas as categorias de nível superior ou descendentes diretos dos esquemas selecionados serão exibidas.',
        ]);
        
        $this->add_control('default_category', [
            'label' => 'Categoria Inicial Ativa',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => $this->get_all_categories_options(),
            'default' => '0',
            'description' => 'Defina qual categoria já aparece selecionada (inclui a opção "Todas" se existir).',
        ]);

        $this->add_control('all_text', [
            'label' => 'Texto para "Todas as Categorias"',
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Todas',
        ]);
        
        $this->end_controls_section();

        $this->start_controls_section('section_style_filter', ['label' => 'Estilo dos Itens', 'tab' => \Elementor\Controls_Manager::TAB_STYLE, 'condition' => ['filter_layout' => 'list']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'filter_typography', 'selector' => '{{WRAPPER}} .upt-category-filter-item']);
        $this->add_responsive_control('filter_padding', ['label' => 'Padding', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'selectors' => ['{{WRAPPER}} .upt-category-filter-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_responsive_control('filter_gap', ['label' => 'Espaçamento entre Itens', 'type' => \Elementor\Controls_Manager::SLIDER, 'selectors' => ['{{WRAPPER}} .upt-category-filter-list' => 'gap: {{SIZE}}{{UNIT}};']]);
        $this->add_responsive_control('filter_alignment', [
            'label' => 'Alinhamento',
            'type' => \Elementor\Controls_Manager::CHOOSE,
            'options' => [
                'flex-start' => ['title' => 'Esquerda', 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => 'Centro', 'icon' => 'eicon-text-align-center'],
                'flex-end' => ['title' => 'Direita', 'icon' => 'eicon-text-align-right'],
                'justified' => ['title' => 'Justificada', 'icon' => 'eicon-text-align-justify'],
            ],
            'selectors' => ['{{WRAPPER}} .upt-category-filter-list' => 'justify-content: {{VALUE}};'],
            'selectors_dictionary' => [
                'flex-start' => 'flex-start',
                'center' => 'center',
                'flex-end' => 'flex-end',
                'justified' => 'space-between',
            ],
        ]);
        $this->add_responsive_control('list_scroll_mode', [
            'label' => 'Rolagem Horizontal',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'off' => 'Desativado',
                'on'  => 'Ativar',
            ],
            'default' => 'off',
            'selectors' => [
                '{{WRAPPER}} .upt-category-filter-list' => '{{VALUE}}',
            ],
            'selectors_dictionary' => [
                'off' => 'flex-wrap: wrap; overflow-x: visible;',
                'on'  => 'flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; scrollbar-width: thin;',
            ],
            'description' => 'Permite rolar horizontalmente as categorias (sem precisar segurar Ctrl/Shift). Configure por dispositivo.',
        ]);
        $this->add_control('filter_border_radius', ['label' => 'Arredondamento', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'selectors' => ['{{WRAPPER}} .upt-category-filter-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        
        $this->start_controls_tabs('filter_tabs_style');

        $this->start_controls_tab('filter_tab_normal', ['label' => 'Normal']);
        $this->add_control('filter_text_color', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-category-filter-item' => 'color: {{VALUE}};']]);
        $this->add_control('filter_bg_color', ['label' => 'Cor do Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-category-filter-item' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'filter_border', 'selector' => '{{WRAPPER}} .upt-category-filter-item']);
        $this->end_controls_tab();

        $this->start_controls_tab('filter_tab_hover', ['label' => 'Hover']);
        $this->add_control('filter_hover_text_color', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-category-filter-item:hover' => 'color: {{VALUE}};']]);
        $this->add_control('filter_hover_bg_color', ['label' => 'Cor do Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-category-filter-item:hover' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'filter_hover_border', 'selector' => '{{WRAPPER}} .upt-category-filter-item:hover']);
        $this->end_controls_tab();

        $this->start_controls_tab('filter_tab_active', ['label' => 'Ativo']);
        $this->add_control('filter_active_text_color', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-category-filter-item.active' => 'color: {{VALUE}};']]);
        $this->add_control('filter_active_bg_color', ['label' => 'Cor do Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-category-filter-item.active' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'filter_active_border', 'selector' => '{{WRAPPER}} .upt-category-filter-item.active']);
        $this->end_controls_tab();

        $this->end_controls_tabs();
        $this->end_controls_section();

        $this->start_controls_section('section_style_dropdown', ['label' => 'Estilo do Dropdown', 'tab' => \Elementor\Controls_Manager::TAB_STYLE, 'condition' => ['filter_layout' => 'dropdown']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'dropdown_typography', 'selector' => '{{WRAPPER}} .upt-category-filter-dropdown']);
        $this->add_responsive_control('dropdown_padding', ['label' => 'Padding', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'selectors' => ['{{WRAPPER}} .upt-category-filter-dropdown' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('dropdown_border_radius', ['label' => 'Arredondamento', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'selectors' => ['{{WRAPPER}} .upt-category-filter-dropdown' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('dropdown_arrow_color', ['label' => 'Cor da Seta', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-dropdown-wrapper::after' => 'color: {{VALUE}};']]);

        $this->start_controls_tabs('dropdown_tabs_style');
        
        $this->start_controls_tab('dropdown_tab_normal', ['label' => 'Normal']);
        $this->add_control('dropdown_text_color', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-category-filter-dropdown' => 'color: {{VALUE}};']]);
        $this->add_control('dropdown_bg_color', ['label' => 'Cor do Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-category-filter-dropdown' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'dropdown_border', 'selector' => '{{WRAPPER}} .upt-category-filter-dropdown']);
        $this->end_controls_tab();

        $this->start_controls_tab('dropdown_tab_focus', ['label' => 'Foco/Hover']);
        $this->add_control('dropdown_focus_text_color', ['label' => 'Cor do Texto (Foco)', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-category-filter-dropdown:focus, {{WRAPPER}} .upt-category-filter-dropdown:hover' => 'color: {{VALUE}};']]);
        $this->add_control('dropdown_focus_bg_color', ['label' => 'Cor de Fundo (Foco)', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-category-filter-dropdown:focus, {{WRAPPER}} .upt-category-filter-dropdown:hover' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'dropdown_focus_border', 'label' => 'Borda (Foco)', 'selector' => '{{WRAPPER}} .upt-category-filter-dropdown:focus, {{WRAPPER}} .upt-category-filter-dropdown:hover']);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'dropdown_focus_shadow', 'label' => 'Sombra (Foco)', 'selector' => '{{WRAPPER}} .upt-category-filter-dropdown:focus']);
        $this->end_controls_tab();

        $this->end_controls_tabs();
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        // Só renderiza o filtro se pelo menos um dos esquemas selecionados tiver campo de categoria (type=taxonomy).
        $schema_definitions = class_exists('UPT_Schema_Store') ? UPT_Schema_Store::get_schemas() : [];
        $selected = isset($settings['schema_source']) ? $settings['schema_source'] : [];
        $has_taxonomy_field = false;
        if (is_array($selected) && !empty($selected) && !in_array('Todas', $selected, true)) {
            foreach ($selected as $slug) {
                if (isset($schema_definitions[$slug]['fields']) && is_array($schema_definitions[$slug]['fields'])) {
                    foreach ($schema_definitions[$slug]['fields'] as $f) {
                        if (isset($f['type']) && $f['type'] === 'taxonomy') {
                            $has_taxonomy_field = true;
                            break 2;
                        }
                    }
                }
            }
        } else {
            // 'Todas' ou vazio: mostra apenas se existir algum esquema com campo taxonomy.
            foreach ($schema_definitions as $sc) {
                if (isset($sc['fields']) && is_array($sc['fields'])) {
                    foreach ($sc['fields'] as $f) {
                        if (isset($f['type']) && $f['type'] === 'taxonomy') {
                            $has_taxonomy_field = true;
                            break 2;
                        }
                    }
                }
            }
        }
        if (!$has_taxonomy_field) {
            return;
        }
        $hide_empty = ($settings['hide_empty_cats'] === 'yes');
        $filter_role = isset($settings['filter_role']) ? $settings['filter_role'] : 'grid';
        $show_subcategories = ($settings['show_subcategories'] === 'yes');
        if ($filter_role === 'parent') {
            $show_subcategories = false;
        }
        $schema_source = $settings['schema_source'];
        $taxonomy = 'catalog_category';
        $behavior = $settings['filter_behavior'];

        // Filtro Filho: as opções (subcategorias) são carregadas via JS conforme a seleção do Filtro Pai.
        // Portanto, não renderizamos lista completa de categorias aqui.
        $is_child_filter = ( $filter_role === 'child' );


        // 1) Coleta de termos: por padrão o filtro deve mostrar CATEGORIAS (filhas diretas do ESQUEMA).
        //    Se "Exibir Subcategorias" estiver ativo, listamos categorias + suas subcategorias.
        //    Isso evita o cenário onde apenas subcategorias aparecem.

        $terms = [];

        if ( ! $is_child_filter ) {
        // Sempre buscamos com hide_empty=false e aplicamos o "ocultar vazios" manualmente,
        // porque o count nativo do WP não considera itens em subcategorias.
        $get_children_terms = function( $parent_id ) use ( $taxonomy ) {
            $args = [
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
                'parent'     => (int) $parent_id,
            ];
            $t = get_terms( $args );
            return ( is_wp_error( $t ) || empty( $t ) ) ? [] : $t;
        };

        // Determina se a categoria deve ser considerada "vazia" levando em conta descendentes.
        $term_has_items_in_tree = function( $term_id ) use ( $taxonomy ) {
            static $cache = [];
            $term_id = (int) $term_id;
            if ( $term_id <= 0 ) {
                return false;
            }
            if ( array_key_exists( $term_id, $cache ) ) {
                return (bool) $cache[ $term_id ];
            }

            $term = get_term( $term_id, $taxonomy );
            if ( ! $term || is_wp_error( $term ) ) {
                $cache[ $term_id ] = false;
                return false;
            }

            // Se a própria categoria tem itens, ok.
            if ( (int) $term->count > 0 ) {
                $cache[ $term_id ] = true;
                return true;
            }

            // Verifica descendentes (qualquer nível).
            $children = get_term_children( $term_id, $taxonomy );
            if ( is_wp_error( $children ) || empty( $children ) ) {
                $cache[ $term_id ] = false;
                return false;
            }

            foreach ( $children as $child_id ) {
                $child = get_term( (int) $child_id, $taxonomy );
                if ( $child && ! is_wp_error( $child ) && (int) $child->count > 0 ) {
                    $cache[ $term_id ] = true;
                    return true;
                }
            }

            $cache[ $term_id ] = false;
            return false;
        };

        if ( ! empty( $schema_source ) && is_array( $schema_source ) ) {
            foreach ( $schema_source as $schema_slug ) {
                $schema_term = get_term_by( 'slug', $schema_slug, $taxonomy );
                if ( ! $schema_term || is_wp_error( $schema_term ) ) {
                    continue;
                }

                // Sempre começa pelas categorias (filhas diretas do esquema)
                $cats = $get_children_terms( $schema_term->term_id );
                foreach ( $cats as $cat ) {
                    if ( $hide_empty && ! $term_has_items_in_tree( $cat->term_id ) ) {
                        continue;
                    }
                    $cat->_upt_level = 0;
                    $terms[] = $cat;

                    if ( $show_subcategories ) {
                        $subs = $get_children_terms( $cat->term_id );
                        foreach ( $subs as $sub ) {
                            if ( $hide_empty && ! $term_has_items_in_tree( $sub->term_id ) ) {
                                continue;
                            }
                            $sub->_upt_level = 1;
                            $terms[] = $sub;
                        }
                    }
                }
            }
        } else {
            // Sem esquema selecionado: mostrar apenas as categorias de nível superior (parent=0), excluindo os termos que representam esquemas.
            $args = [
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
                'parent'     => 0,
            ];
            $t = get_terms( $args );
            $terms = ( is_wp_error( $t ) || empty( $t ) ) ? [] : $t;
            $terms = array_values( array_filter( $terms, function( $term_obj ) use ( $hide_empty, $term_has_items_in_tree ) {
                if ( ! $hide_empty ) {
                    return true;
                }
                return $term_has_items_in_tree( $term_obj->term_id );
            } ) );
            foreach ( $terms as $term_obj ) {
                $term_obj->_upt_level = 0;
            }
        }

        }

        // SEMPRE excluir os IDs das categorias pai (que correspondem aos esquemas)
        $exclude_ids = [];
        foreach ($schema_definitions as $slug => $sc_data) {
            $pc = get_term_by('slug', $slug, $taxonomy);
            if ($pc && !is_wp_error($pc)) {
                $exclude_ids[] = $pc->term_id;
            }
        }
        if (!empty($exclude_ids) && !empty($terms)) {
            // Filtra localmente pois agora montamos $terms manualmente (para garantir ordem e níveis).
            $terms = array_values(array_filter($terms, function($term) use ($exclude_ids) {
                return !in_array((int)$term->term_id, $exclude_ids, true);
            }));
        }
        $target_filter_id = '';
        $parent_filter_id = '';
        if ($filter_role === 'parent') {
            $target_filter_id = !empty($settings['target_filter_auto']) ? $settings['target_filter_auto'] : (isset($settings['target_filter_id']) ? $settings['target_filter_id'] : '');
        } elseif ($filter_role === 'child') {
            $parent_filter_id = !empty($settings['parent_filter_auto']) ? $settings['parent_filter_auto'] : (isset($settings['parent_filter_id']) ? $settings['parent_filter_id'] : '');
        }

$target_id = !empty($settings['target_grid_auto']) ? $settings['target_grid_auto'] : $settings['target_id'];
        $default_category = isset($settings['default_category']) ? (int) $settings['default_category'] : 0;
        if ($default_category < 0) {
            $default_category = 0;
        }

        $current_selected = isset($_GET['upt_category']) ? (int) $_GET['upt_category'] : $default_category;
        if ($current_selected < 0) {
            $current_selected = 0;
        }

        // $terms já foi montado acima para respeitar: (a) categorias por padrão, (b) subcategorias opcionais.

        $base_url = remove_query_arg('upt_category');
        $all_link = $behavior === 'link' ? $base_url : '#';

        $scroll_enabled = (
            (isset($settings['list_scroll_mode']) && $settings['list_scroll_mode'] === 'on') ||
            (isset($settings['list_scroll_mode_tablet']) && $settings['list_scroll_mode_tablet'] === 'on') ||
            (isset($settings['list_scroll_mode_mobile']) && $settings['list_scroll_mode_mobile'] === 'on')
        );

        static $base_filter_style_added = false;
        if (!$base_filter_style_added) {
            $base_filter_style_added = true;
            $inline_css = '.upt-category-filter-list{list-style:none;margin:0;padding:0;display:flex;align-items:center;gap:10px;}';
            $inline_css .= '.upt-category-filter-item{display:inline-flex;align-items:center;white-space:nowrap;}';
            $inline_css .= '.upt-category-filter-list.upt-justified-one-row{width:100%;justify-content:space-between;}';
            $inline_css .= '.upt-category-filter-list.upt-justified-one-row>li{flex:1 1 0;display:flex;}';
            $inline_css .= '.upt-category-filter-list.upt-justified-one-row>li>a.upt-category-filter-item{width:100%;justify-content:center;}';
            $inline_css .= '.upt-category-filter-list.upt-justified-two-rows{width:100%;flex-direction:column;align-items:stretch;gap:var(--upt-filter-gap,10px);}';
            $inline_css .= '.upt-category-filter-list.upt-justified-two-rows>li.upt-justified-row{width:100%;padding:0;margin:0;list-style:none;}';
            $inline_css .= '.upt-category-filter-list.upt-justified-two-rows .upt-justified-row-list{list-style:none;margin:0;padding:0;display:flex;align-items:center;gap:var(--upt-filter-gap,10px);width:100%;}';
            $inline_css .= '.upt-category-filter-list.upt-justified-two-rows .upt-justified-row-list>li{flex:1 1 0;display:flex;}';
            $inline_css .= '.upt-category-filter-list.upt-justified-two-rows .upt-justified-row-list>li>a.upt-category-filter-item{width:100%;justify-content:center;}';

            $inline_css .= '.upt-category-filter-list::-webkit-scrollbar{height:6px;}';
            $inline_css .= '.upt-category-filter-list::-webkit-scrollbar-thumb{background:rgba(0,0,0,0.25);border-radius:10px;}';
            wp_add_inline_style('upt-style', $inline_css);
        }
        ?>
        <div class="upt-category-filter-wrapper"
             id="<?php echo esc_attr('upt-filter-' . $this->get_id()); ?>"
             data-filter-role="<?php echo esc_attr($filter_role); ?>"
             data-filter-align="<?php echo esc_attr( isset($settings['filter_alignment']) ? $settings['filter_alignment'] : '' ); ?>"
             data-filter-align-tablet="<?php echo esc_attr( isset($settings['filter_alignment_tablet']) ? $settings['filter_alignment_tablet'] : '' ); ?>"
             data-filter-align-mobile="<?php echo esc_attr( isset($settings['filter_alignment_mobile']) ? $settings['filter_alignment_mobile'] : '' ); ?>"
             <?php if ($filter_role !== 'parent') : ?>
             data-target-id="<?php echo esc_attr($target_id); ?>"
             <?php endif; ?>
             data-template-id="<?php echo esc_attr($settings['template_id']); ?>"
             data-behavior="<?php echo esc_attr($behavior); ?>"
             data-default-term-id="<?php echo esc_attr($default_category); ?>"
             <?php if ($filter_role === 'parent') : ?>
             data-target-filter-id="<?php echo esc_attr($target_filter_id); ?>"
             <?php endif; ?>
             <?php if ($filter_role === 'child') : ?>
             data-parent-filter-id="<?php echo esc_attr($parent_filter_id); ?>"
             data-child-show-all="<?php echo esc_attr( (isset($settings['child_show_all_option']) && $settings['child_show_all_option'] === 'yes') ? '1' : '0' ); ?>"
             data-child-auto-first="<?php echo esc_attr( (isset($settings['child_auto_select_first']) && $settings['child_auto_select_first'] === 'yes') ? '1' : '0' ); ?>"
             data-child-empty-text="<?php echo esc_attr( isset($settings['child_empty_text']) ? $settings['child_empty_text'] : '' ); ?>"
             <?php endif; ?>
             >

            <?php if ( $filter_role === 'child' ) : ?>
            <?php if ( 'dropdown' === $settings['filter_layout'] ) : ?>
                <div class="upt-dropdown-wrapper">
                    <select
                        name="upt-category-filter"
                        id="upt-category-filter"
                        class="upt-category-filter upt-category-filter-dropdown"
                    >
                        <option value="0" selected="selected"><?php echo esc_html( isset($settings['child_empty_text']) ? $settings['child_empty_text'] : 'Selecione uma categoria acima' ); ?></option>
                    </select>
                </div>
            <?php else : ?>
                <ul class="upt-category-filter-list"<?php echo $scroll_enabled ? ' data-scroll-enabled="1"' : ''; ?>>
                    <li><span class="upt-category-filter-empty"><?php echo esc_html( isset($settings['child_empty_text']) ? $settings['child_empty_text'] : 'Selecione uma categoria acima' ); ?></span></li>
                </ul>
            <?php endif; ?>
        <?php else : ?>
            <?php if ('dropdown' === $settings['filter_layout']) : ?>
                <div class="upt-dropdown-wrapper">
                    <select
                        name="upt-category-filter"
                        id="upt-category-filter"
                        class="upt-category-filter upt-category-filter-dropdown"
                    >
                        <option value="0" <?php selected($current_selected, 0); ?>><?php echo esc_html($settings['all_text']); ?></option>
                        <?php foreach ($terms as $term): ?>
                            <?php
                                $level = isset($term->_upt_level) ? (int)$term->_upt_level : 0;
                                $label = $level > 0 ? '— ' . $term->name : $term->name;
                            ?>
                            <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($current_selected, (int) $term->term_id); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else : ?>
                <ul class="upt-category-filter-list"<?php echo $scroll_enabled ? ' data-scroll-enabled="1"' : ''; ?>>
                    <li><a href="<?php echo esc_url($all_link); ?>" class="upt-category-filter-item<?php echo $current_selected === 0 ? ' active' : ''; ?>" data-term-id="0"><?php echo esc_html($settings['all_text']); ?></a></li>
                    <?php foreach ($terms as $term): 
                        $level = isset($term->_upt_level) ? (int)$term->_upt_level : 0;
                        $label = $level > 0 ? '— ' . $term->name : $term->name;
                        $term_link = $behavior === 'link' ? add_query_arg('upt_category', $term->term_id, $base_url) : '#';
                        $is_active = ((int) $term->term_id === $current_selected);
                    ?>
                        <li><a href="<?php echo esc_url($term_link); ?>" class="upt-category-filter-item<?php echo $is_active ? ' active' : ''; ?>" data-term-id="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($label); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>

        </div>
        <?php
    }
}
