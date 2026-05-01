<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Product_Gallery_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'upt_product_gallery'; }
    public function get_title() { return 'upt - Galeria V12 (Corrigida)'; }
    public function get_icon() { return 'eicon-gallery-grid'; }
    public function get_categories() { return [ 'upt-widgets' ]; }

    public function get_script_depends() { return [ 'swiper', 'upt-gallery-js' ]; }

    protected function register_controls() {
        
        // ==========================================
        // 1. ABA CONTEÚDO: LAYOUT
        // ==========================================
        $this->start_controls_section( 'section_layout', [ 'label' => 'Layout e Estrutura' ] );

        $this->add_responsive_control(
            'main_height',
            [
                'label' => 'Altura Imagem Principal',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh' ],
                'range' => [ 'px' => [ 'min' => 100, 'max' => 1000 ] ],
                'default' => [ 'unit' => 'px', 'size' => 500 ],
                'selectors' => [ 
                    '{{WRAPPER}} .fc-gallery-img-main' => 'height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .fc-loading-placeholder' => 'height: {{SIZE}}{{UNIT}};'
                ],
            ]
        );

        $this->add_control(
            'object_fit',
            [
                'label' => 'Ajuste da Imagem',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [ 'cover' => 'Preencher (Corta)', 'contain' => 'Conter (Inteira)' ],
                'default' => 'cover',
                'selectors' => [ '{{WRAPPER}} .fc-gallery-img-main' => 'object-fit: {{VALUE}};' ],
            ]
        );

        // --- DIVISOR VISUAL ---
        $this->add_control(
            'hr_thumbs',
            [ 'type' => \Elementor\Controls_Manager::DIVIDER ]
        );

        $this->add_control(
            'heading_thumbs',
            [ 'label' => 'Configuração das Miniaturas', 'type' => \Elementor\Controls_Manager::HEADING ]
        );

        // Botão que faltava e causou o sumiço das opções
        $this->add_control(
            'hide_thumbs',
            [
                'label' => 'Ocultar Miniaturas?',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => '', // Padrão é mostrar
                'return_value' => 'yes',
            ]
        );

        $this->add_responsive_control(
            'thumbs_per_view',
            [
                'label' => 'Quantidade por linha',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 2, 'max' => 10, 'default' => 5,
                'tablet_default' => 4,
                'mobile_default' => 3,
                'condition' => [ 'hide_thumbs!' => 'yes' ],
            ]
        );

        // --- ESPAÇAMENTOS ---
        
        $this->add_responsive_control(
            'main_thumbs_gap',
            [
                'label' => 'Distância Imagem ↔ Miniaturas',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
                'default' => [ 'size' => 15 ],
                'selectors' => [ '{{WRAPPER}} .fc-thumbs-container' => 'margin-top: {{SIZE}}px;' ],
                'condition' => [ 'hide_thumbs!' => 'yes' ],
            ]
        );

        $this->add_control(
            'thumbs_gap',
            [
                'label' => 'Espaço entre Miniaturas (Gap)',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 10,
                'min' => 0,
                'max' => 50,
                'description' => 'Espaço lateral entre cada item.',
                'condition' => [ 'hide_thumbs!' => 'yes' ],
            ]
        );

        $this->end_controls_section();

        // ==========================================
        // 2. ABA ESTILO: IMAGEM PRINCIPAL
        // ==========================================
        $this->start_controls_section( 'section_style_main', [ 'label' => 'Imagem Principal', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );

        $this->add_responsive_control(
            'main_border_radius',
            [
                'label' => 'Arredondamento',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [ '{{WRAPPER}} .fc-gallery-img-main' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [ 'name' => 'main_border', 'selector' => '{{WRAPPER}} .fc-gallery-img-main' ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [ 'name' => 'main_box_shadow', 'selector' => '{{WRAPPER}} .fc-gallery-img-main' ]
        );

        $this->end_controls_section();

        // ==========================================
        // 3. ABA ESTILO: MINIATURAS (RESTAURADA)
        // ==========================================
        $this->start_controls_section( 
            'section_style_thumbs', 
            [ 
                'label' => 'Miniaturas', 
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [ 'hide_thumbs!' => 'yes' ] // Agora funciona pois o botão existe
            ] 
        );

        $this->add_responsive_control(
            'thumb_height',
            [
                'label' => 'Altura',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'default' => [ 'size' => 80 ],
                'selectors' => [ '{{WRAPPER}} .fc-gallery-img-thumb' => 'height: {{SIZE}}px;' ],
            ]
        );

        $this->add_responsive_control(
            'thumb_border_radius',
            [
                'label' => 'Arredondamento',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [ '{{WRAPPER}} .fc-gallery-img-thumb' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
            ]
        );

        $this->start_controls_tabs( 'tabs_thumbs_style' );

        // --- NORMAL ---
        $this->start_controls_tab( 'tab_thumb_normal', [ 'label' => 'Normal' ] );

        $this->add_control(
            'thumb_opacity',
            [ 'label' => 'Opacidade', 'type' => \Elementor\Controls_Manager::SLIDER, 'default' => [ 'size' => 0.6 ], 'selectors' => [ '{{WRAPPER}} .fc-gallery-img-thumb' => 'opacity: {{SIZE}};' ] ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [ 'name' => 'thumb_border', 'selector' => '{{WRAPPER}} .fc-gallery-img-thumb' ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [ 'name' => 'thumb_shadow', 'selector' => '{{WRAPPER}} .fc-gallery-img-thumb' ]
        );

        $this->end_controls_tab();

        // --- HOVER ---
        $this->start_controls_tab( 'tab_thumb_hover', [ 'label' => 'Hover' ] );

        $this->add_control(
            'thumb_opacity_hover',
            [ 'label' => 'Opacidade', 'type' => \Elementor\Controls_Manager::SLIDER, 'default' => [ 'size' => 1 ], 'selectors' => [ '{{WRAPPER}} .fc-gallery-img-thumb:hover' => 'opacity: {{SIZE}};' ] ]
        );

        $this->add_control(
            'thumb_border_color_hover',
            [ 'label' => 'Cor da Borda', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .fc-gallery-img-thumb:hover' => 'border-color: {{VALUE}};' ] ]
        );

        $this->end_controls_tab();

        // --- ATIVO ---
        $this->start_controls_tab( 'tab_thumb_active', [ 'label' => 'Ativo' ] );

        $this->add_control(
            'thumb_opacity_active',
            [ 'label' => 'Opacidade', 'type' => \Elementor\Controls_Manager::SLIDER, 'default' => [ 'size' => 1 ], 'selectors' => [ '{{WRAPPER}} .swiper-slide-thumb-active .fc-gallery-img-thumb' => 'opacity: {{SIZE}};' ] ]
        );

        $this->add_control(
            'thumb_border_color_active',
            [ 'label' => 'Cor da Borda', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#000000', 'selectors' => [ '{{WRAPPER}} .swiper-slide-thumb-active .fc-gallery-img-thumb' => 'border-color: {{VALUE}};' ] ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [ 'name' => 'thumb_shadow_active', 'selector' => '{{WRAPPER}} .swiper-slide-thumb-active .fc-gallery-img-thumb' ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();

        // ==========================================
        // 4. ABA ESTILO: NAVEGAÇÃO
        // ==========================================
        $this->start_controls_section( 'section_style_nav', [ 'label' => 'Setas de Navegação', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );

        $this->add_control( 'nav_show', [ 'label' => 'Mostrar Setas?', 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes' ] );

        $this->add_control(
            'nav_position_type',
            [
                'label' => 'Posição',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [ 'inside' => 'Dentro', 'custom' => 'Personalizado (Offset)' ],
                'default' => 'inside',
                'condition' => [ 'nav_show' => 'yes' ],
            ]
        );

        $this->add_responsive_control(
            'nav_offset_x',
            [
                'label' => 'Afastamento Horizontal',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [ 'px' => [ 'min' => -100, 'max' => 100 ] ],
                'condition' => [ 'nav_show' => 'yes', 'nav_position_type' => 'custom' ],
                'selectors' => [ 
                    '{{WRAPPER}} .fc-button-prev' => 'left: {{SIZE}}px;',
                    '{{WRAPPER}} .fc-button-next' => 'right: {{SIZE}}px;',
                ],
            ]
        );

        $this->add_control(
            'nav_size',
            [ 'label' => 'Tamanho Box', 'type' => \Elementor\Controls_Manager::SLIDER, 'default' => [ 'size' => 40 ], 'selectors' => [ '{{WRAPPER}} .fc-gallery-nav' => 'width: {{SIZE}}px; height: {{SIZE}}px;' ], 'condition' => [ 'nav_show' => 'yes' ] ]
        );

        $this->add_control(
            'nav_icon_size',
            [ 'label' => 'Tamanho Ícone', 'type' => \Elementor\Controls_Manager::SLIDER, 'selectors' => [ '{{WRAPPER}} .fc-gallery-nav i' => 'font-size: {{SIZE}}px;' ], 'condition' => [ 'nav_show' => 'yes' ] ]
        );
        
        $this->add_control(
            'nav_radius',
            [ 'label' => 'Arredondamento', 'type' => \Elementor\Controls_Manager::SLIDER, 'selectors' => [ '{{WRAPPER}} .fc-gallery-nav' => 'border-radius: {{SIZE}}%;' ], 'condition' => [ 'nav_show' => 'yes' ] ]
        );

        $this->start_controls_tabs( 'tabs_nav_colors' );

        $this->start_controls_tab( 'tab_nav_normal', [ 'label' => 'Normal', 'condition' => [ 'nav_show' => 'yes' ] ] );
        
        $this->add_control(
            'nav_color',
            [ 'label' => 'Cor Ícone', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFF', 'selectors' => [ '{{WRAPPER}} .fc-gallery-nav' => 'color: {{VALUE}};' ], 'condition' => [ 'nav_show' => 'yes' ] ]
        );
        
        $this->add_control(
            'nav_bg',
            [ 'label' => 'Cor Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => 'rgba(0,0,0,0.5)', 'selectors' => [ '{{WRAPPER}} .fc-gallery-nav' => 'background-color: {{VALUE}};' ], 'condition' => [ 'nav_show' => 'yes' ] ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab( 'tab_nav_hover', [ 'label' => 'Hover', 'condition' => [ 'nav_show' => 'yes' ] ] );

        $this->add_control(
            'nav_color_h',
            [ 'label' => 'Cor Ícone', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .fc-gallery-nav:hover' => 'color: {{VALUE}};' ], 'condition' => [ 'nav_show' => 'yes' ] ]
        );
        
        $this->add_control(
            'nav_bg_h',
            [ 'label' => 'Cor Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .fc-gallery-nav:hover' => 'background-color: {{VALUE}};' ], 'condition' => [ 'nav_show' => 'yes' ] ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $post_id = get_the_ID();
        $images = [];

        // Lógica de imagens
        if ( has_post_thumbnail( $post_id ) ) $images[] = get_post_thumbnail_id( $post_id );
        
        $terms = get_the_terms( $post_id, 'catalog_schema' );
        if ( $terms && ! is_wp_error( $terms ) && class_exists( 'UPT_Schema_Store' ) ) {
            $fields = UPT_Schema_Store::get_fields_for_schema( $terms[0]->slug );
            foreach ( $fields as $field ) {
                if ( isset($field['type']) && $field['type'] === 'gallery' ) {
                    $val = get_post_meta( $post_id, $field['id'], true );
                    if ( $val ) $images = array_merge( $images, ( is_array($val) ? $val : explode(',',$val) ) );
                }
            }
        }

        $images = array_unique( array_filter( $images ) );
        $count = count( $images );

        if ( $count === 0 ) return;

        $uid = $this->get_id();
        $config = [
            'thumbs_desktop' => $settings['thumbs_per_view'] ?? 5,
            'thumbs_tablet' => $settings['thumbs_per_view_tablet'] ?? 4,
            'thumbs_mobile' => $settings['thumbs_per_view_mobile'] ?? 3,
            'gap' => $settings['thumbs_gap'] ?? 10
        ];

        $wrapper_class = 'fc-gallery-wrapper is-loading';
        if ( $count === 1 ) $wrapper_class .= ' fc-single-image-mode';
        if ( $settings['nav_position_type'] === 'inside' ) $wrapper_class .= ' fc-nav-inside';
        
        $show_thumbs = ( $count > 1 && $settings['hide_thumbs'] !== 'yes' );

        ?>
        <div class="<?php echo esc_attr( $wrapper_class ); ?>" data-config="<?php echo esc_attr( json_encode($config) ); ?>">
            
            <div class="fc-loading-placeholder"></div>

            <div class="fc-gallery-inner">
                <div class="fc-main-container">
                    <div class="swiper fc-main-swiper" id="fc-main-<?php echo $uid; ?>">
                        <div class="swiper-wrapper">
                            <?php foreach ( $images as $img_id ) : 
                                $full = wp_get_attachment_image_url( $img_id, 'full' );
                                $large = wp_get_attachment_image_url( $img_id, 'large' );
                                if(!$large) continue;
                            ?>
                                <div class="swiper-slide">
                                    <a href="<?php echo esc_url($full); ?>" data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="<?php echo $uid; ?>">
                                        <img src="<?php echo esc_url($large); ?>" class="fc-gallery-img-main" alt="Imagem">
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php if ( $count > 1 && $settings['nav_show'] === 'yes' ): ?>
                        <div class="fc-gallery-nav fc-button-prev" aria-label="Anterior" role="button" tabindex="0"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M14.7 5.3a1 1 0 0 1 0 1.4L10.4 11l4.3 4.3a1 1 0 1 1-1.4 1.4l-5-5a1 1 0 0 1 0-1.4l5-5a1 1 0 0 1 1.4 0Z"/></svg></div>
                        <div class="fc-gallery-nav fc-button-next" aria-label="Próximo" role="button" tabindex="0"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M9.3 5.3a1 1 0 0 0 0 1.4l4.3 4.3-4.3 4.3a1 1 0 1 0 1.4 1.4l5-5a1 1 0 0 0 0-1.4l-5-5a1 1 0 0 0-1.4 0Z"/></svg></div>
                    <?php endif; ?>
                </div>

                <?php if ( $show_thumbs ): ?>
                <div class="fc-thumbs-container">
                    <div class="swiper fc-thumbs-swiper" id="fc-thumbs-<?php echo $uid; ?>">
                        <div class="swiper-wrapper">
                            <?php foreach ( $images as $img_id ) : 
                                $thumb = wp_get_attachment_image_url( $img_id, 'medium' );
                                if(!$thumb) continue;
                            ?>
                                <div class="swiper-slide">
                                    <img src="<?php echo esc_url($thumb); ?>" class="fc-gallery-img-thumb" alt="Thumb">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <style>
            .fc-gallery-wrapper { position: relative; }
            .fc-gallery-inner { opacity: 0; transition: opacity 0.5s; }
            .fc-gallery-wrapper.is-loaded .fc-gallery-inner { opacity: 1; }
            
            .fc-loading-placeholder { background: #eee; width: 100%; border-radius: 4px; display: none; }
            .fc-gallery-wrapper.is-loading .fc-loading-placeholder { display: block; height: 500px; }

            .fc-main-container { position: relative; width: 100%; }
            .fc-gallery-img-main { display: block; width: 100%; cursor: zoom-in; }
            
            .fc-gallery-img-thumb { 
                display: block; width: 100%; object-fit: cover; cursor: pointer; 
                border-style: solid; border-width: 2px; border-color: transparent; 
                box-sizing: border-box; transition: all 0.3s ease;
            }

            .fc-gallery-nav {
                position: absolute; top: 50%; transform: translateY(-50%);
                z-index: 10; cursor: pointer; display: flex; align-items: center; justify-content: center;
                transition: all 0.3s;
            }
            .fc-nav-inside .fc-button-prev { left: 10px; }
            .fc-nav-inside .fc-button-next { right: 10px; }
        </style>
        <?php
    }
}
