<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Search_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'upt_search_widget'; }
    public function get_title() { return 'Pesquisa AJAX upt'; }
    public function get_icon() { return 'eicon-search'; }
    public function get_categories() { return [ 'upt' ]; }

    public function get_script_depends() {
        return [ 'upt-main-js' ];
    }
    
    public function get_style_depends() {
        return [ 'upt-style' ];
    }

    private function get_detected_listing_targets() {
        $options = ['' => '— Selecione —'];

        if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
            return $options;
        }

        $document = \Elementor\Plugin::instance()->documents->get( get_the_ID() );
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

        return $options;
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

    protected function _register_controls() {
        $this->start_controls_section('section_config', ['label' => 'Configuração da Pesquisa']);
        
        $this->add_control('target_grid_auto', [
            'label' => 'Grade do Catálogo Detectada',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => $this->get_detected_listing_targets(),
            'description' => 'Lista automática dos widgets "Grade do Catálogo" encontrados na página. Se vazio, use o campo abaixo para informar o ID manualmente.',
        ]);

        $this->add_control('target_id', [
            'label' => 'ID de CSS do Grid Alvo',
            'type' => \Elementor\Controls_Manager::TEXT,
            'description' => 'Insira o ID (manual) do widget "Grade do Catálogo" que você quer filtrar. Mantém a alternativa caso a detecção automática não encontre o grid.',
        ]);

        $this->add_control('template_id', [
            'label' => 'Template do Card (Loop Item)',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => $this->get_all_loop_templates(),
            'description' => 'Selecione o template que o grid alvo usa para renderizar os itens.',
            'separator' => 'before',
        ]);

        $this->add_control('search_target', [
            'label' => 'Alvo da Busca (texto ou meta:sku, taxonomy:catalog_category)',
            'type' => \Elementor\Controls_Manager::TEXT,
            'label_block' => true,
            'dynamic' => [ 'active' => true ],
            'description' => 'Informe um ou mais alvos separados por vírgula. Ex.: title, meta:sku, taxonomy:catalog_category. Deixe vazio para buscar em tudo.',
        ]);
        
        $this->add_control('filter_behavior', [
            'label' => 'Comportamento da Pesquisa',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'ajax',
            'options' => [
                'ajax' => 'Pesquisa dinâmica (AJAX)',
                'link' => 'Recarregar página (Link)',
            ],
            'description' => '"Link" recarrega a página com o termo de busca na URL.',
            'separator' => 'before',
        ]);
        
        $this->end_controls_section();

        $this->start_controls_section('section_button_content', ['label' => 'Conteúdo do Botão']);
        $this->add_control('button_text', ['label' => 'Texto do Botão', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('selected_icon', ['label' => 'Ícone do Botão', 'type' => \Elementor\Controls_Manager::ICONS, 'default' => ['value' => 'lucide-search', 'library' => 'lucide']]);
        $this->end_controls_section();

        $this->start_controls_section('section_input_style', ['label' => 'Campo de Busca', 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('placeholder_text', ['label' => 'Texto do Placeholder', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Pesquisar itens...']);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'input_typography', 'selector' => '{{WRAPPER}} .upt-search-input']);
        $this->add_control('input_text_color', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-search-input' => 'color: {{VALUE}};']]);
        $this->add_control('placeholder_color', ['label' => 'Cor do Placeholder', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-search-input::placeholder' => 'color: {{VALUE}};']]);
        $this->add_control('input_background_color', ['label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-search-input' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'input_border', 'selector' => '{{WRAPPER}} .upt-search-input']);
        $this->add_control('input_border_radius', ['label' => 'Arredondamento', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'selectors' => ['{{WRAPPER}} .upt-search-input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_responsive_control('input_padding', ['label' => 'Padding', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'selectors' => ['{{WRAPPER}} .upt-search-input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->end_controls_section();
        
        $this->start_controls_section('section_button_style', ['label' => 'Botão de Busca','tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'button_typography', 'selector' => '{{WRAPPER}} .upt-search-button span']);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'button_border', 'selector' => '{{WRAPPER}} .upt-search-button']);
        $this->add_control('button_border_radius', ['label' => 'Arredondamento', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'selectors' => ['{{WRAPPER}} .upt-search-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_responsive_control('button_padding', ['label' => 'Padding', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'selectors' => ['{{WRAPPER}} .upt-search-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('button_icon_size', ['label' => 'Tamanho do Ícone', 'type' => \Elementor\Controls_Manager::SLIDER, 'selectors' => ['{{WRAPPER}} .upt-search-button i' => 'font-size: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .upt-search-button svg' => 'width: {{SIZE}}{{UNIT}};']]);
        $this->start_controls_tabs('button_tabs_style');
        $this->start_controls_tab('button_tab_normal', ['label' => 'Normal']);
        $this->add_control('button_text_color', ['label' => 'Cor Texto/Ícone', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-search-button' => 'color: {{VALUE}}; fill: {{VALUE}};']]);
        $this->add_control('button_bg_color', ['label' => 'Cor do Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-search-button' => 'background-color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->start_controls_tab('button_tab_hover', ['label' => 'Hover']);
        $this->add_control('button_hover_text_color', ['label' => 'Cor Texto/Ícone', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-search-button:hover' => 'color: {{VALUE}}; fill: {{VALUE}};']]);
        $this->add_control('button_hover_bg_color', ['label' => 'Cor do Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-search-button:hover' => 'background-color: {{VALUE}};']]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $behavior = $settings['filter_behavior'];
        $form_tag = $behavior === 'link' ? 'form' : 'div';
        $targets_values = [];
        if (!empty($settings['search_target'])) {
            $raw_targets = explode(',', $settings['search_target']);
            foreach ($raw_targets as $rt) {
                $rt = trim($rt);
                if ($rt === '') { continue; }
                $targets_values[] = sanitize_text_field($rt);
            }
        }
           $resolved_target_id = $settings['target_id'];
           if ( empty( $resolved_target_id ) && ! empty( $settings['target_grid_auto'] ) ) {
              $resolved_target_id = $settings['target_grid_auto'];
           }

           ?>
        <div class="upt-search-wrapper" 
               data-target-id="<?php echo esc_attr($resolved_target_id); ?>"
             data-template-id="<?php echo esc_attr($settings['template_id']); ?>"
             data-behavior="<?php echo esc_attr($behavior); ?>"
             data-targets='<?php echo wp_json_encode($targets_values); ?>'>
             <<?php echo $form_tag; ?> class="upt-search-container" action="<?php echo esc_url(home_url(add_query_arg(null, null))); ?>" method="get">
                <input type="search" name="s_upt" class="upt-search-input" placeholder="<?php echo esc_attr($settings['placeholder_text']); ?>">
                <?php if (!empty($targets_values)) : ?>
                    <input type="hidden" name="upt_target" value="<?php echo esc_attr(implode(',', $targets_values)); ?>">
                <?php endif; ?>
                <button type="<?php echo $behavior === 'link' ? 'submit' : 'button'; ?>" class="upt-search-button">
                    <?php if (!empty($settings['selected_icon']['value'])) { \Elementor\Icons_Manager::render_icon($settings['selected_icon'], ['aria-hidden' => 'true']); } ?>
                    <?php if (!empty($settings['button_text'])) { echo '<span>' . esc_html($settings['button_text']) . '</span>'; } ?>
                </button>
             </<?php echo $form_tag; ?>>
        </div>
        <?php
    }
}
