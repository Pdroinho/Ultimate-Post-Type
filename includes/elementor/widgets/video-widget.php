<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Video_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'upt_video'; }
    public function get_title() { return 'Vídeo upt Pro'; }
    public function get_icon() { return 'eicon-play'; }
    public function get_categories() { return [ 'upt' ]; }

    public function get_script_depends() { return [ 'upt-main-js' ]; }

    private function get_upt_video_fields() {
        $options = ['' => '— Selecione o Campo —'];
        if ( ! class_exists( 'UPT_Schema_Store' ) ) return $options;
        
        $schemas = UPT_Schema_Store::get_schemas();
        if ( empty( $schemas ) ) return $options;

        foreach ( $schemas as $slug => $data ) {
            if ( isset( $data['fields'] ) && is_array( $data['fields'] ) ) {
                $term = get_term_by( 'slug', $slug, 'catalog_schema' );
                $name = $term ? $term->name : $slug;
                foreach ( $data['fields'] as $field ) {
                    if ( in_array( $field['type'], [ 'video', 'url', 'text' ] ) ) {
                        $options[ $field['id'] ] = $name . ' - ' . $field['label'];
                    }
                }
            }
        }
        return $options;
    }

    protected function _register_controls() {
        // --- CONTEÚDO ---
        $this->start_controls_section( 'section_video_content', [ 'label' => 'Vídeo' ] );

        $this->add_control(
            'source_type',
            [
                'label' => 'Origem',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'upt',
                'options' => [
                    'upt' => 'Campo Dinâmico (upt)',
                    'manual'   => 'Manual (Link/Upload)',
                ],
            ]
        );

        $this->add_control(
            'upt_field',
            [
                'label' => 'Campo do Vídeo',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_upt_video_fields(),
                'condition' => [ 'source_type' => 'upt' ],
            ]
        );

        $this->add_control(
            'manual_type',
            [
                'label' => 'Tipo Manual',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'file',
                'options' => [
                    'file' => 'Arquivo (Upload)',
                    'url'  => 'Link Externo',
                ],
                'condition' => [ 'source_type' => 'manual' ],
            ]
        );

        $this->add_control(
            'manual_file',
            [
                'label' => 'Arquivo',
                'type' => \Elementor\Controls_Manager::MEDIA,
                'media_type' => 'video',
                'dynamic' => [ 'active' => true ],
                'condition' => [ 'source_type' => 'manual', 'manual_type' => 'file' ],
            ]
        );

        $this->add_control(
            'manual_url',
            [
                'label' => 'Link',
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => 'https://...',
                'dynamic' => [ 'active' => true ],
                'condition' => [ 'source_type' => 'manual', 'manual_type' => 'url' ],
            ]
        );

        $this->end_controls_section();

        // --- OPÇÕES / LIGHTBOX ---
        $this->start_controls_section( 'section_video_options', [ 'label' => 'Opções & Lightbox' ] );

        $this->add_control(
            'enable_lightbox',
            [
                'label' => 'Abrir em Lightbox?',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sim',
                'label_off' => 'Não',
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'video_autoplay',
            [
                'label' => 'Autoplay',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'description' => 'No Lightbox, o vídeo tocará automaticamente ao abrir.',
            ]
        );

        $this->add_control(
            'video_mute',
            [
                'label' => 'Mudo',
                'type' => \Elementor\Controls_Manager::SWITCHER,
            ]
        );

        $this->add_control(
            'video_loop',
            [
                'label' => 'Loop',
                'type' => \Elementor\Controls_Manager::SWITCHER,
            ]
        );

        $this->add_control(
            'video_controls',
            [
                'label' => 'Controles do Player',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // --- CAPA / POSTER ---
        $this->start_controls_section( 'section_overlay_content', [ 'label' => 'Capa e Botão' ] );

        $this->add_control(
            'custom_poster',
            [
                'label' => 'Imagem de Capa',
                'type' => \Elementor\Controls_Manager::MEDIA,
                'dynamic' => [ 'active' => true ],
                'description' => 'Se vazio, tentará usar a imagem destacada do post.',
            ]
        );

        $this->add_control(
            'fallback_image',
            [
                'label' => 'Imagem de Fallback',
                'type' => \Elementor\Controls_Manager::MEDIA,
                'description' => 'Será usada caso a Capa e a Imagem Destacada não existam.',
            ]
        );

        $this->add_control(
            'overlay_color',
            [
                'label' => 'Cor da Sobreposição (Overlay)',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => 'rgba(0,0,0,0.3)',
                'selectors' => [ '{{WRAPPER}} .fc-video-overlay' => 'background-color: {{VALUE}};' ],
            ]
        );

        $this->add_control(
            'show_play_icon',
            [
                'label' => 'Mostrar Botão de Play',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'play_icon',
            [
                'label' => 'Ícone',
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [ 'value' => 'lucide-play', 'library' => 'lucide' ],
                'condition' => [ 'show_play_icon' => 'yes' ],
            ]
        );

        $this->end_controls_section();

        // --- ESTILO: CONTAINER ---
        $this->start_controls_section( 'section_style_container', [ 'label' => 'Estilo do Container', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );

        $this->add_responsive_control(
            'aspect_ratio',
            [
                'label' => 'Proporção',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [ '169' => '16:9', '43' => '4:3', '11' => '1:1', '916' => '9:16 (Stories)', 'custom' => 'Livre (Ignorado se Altura definida)' ],
                'default' => '169',
                'description' => 'Define a proporção automática. Se você definir uma Altura abaixo, esta proporção será ignorada.',
            ]
        );

        $this->add_responsive_control(
            'custom_height',
            [
                'label' => 'Altura',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%', 'vh', 'vw', 'em' ],
                'range' => [
                    'px' => [ 'min' => 50, 'max' => 1200 ],
                    '%' => [ 'min' => 0, 'max' => 100 ],
                    'vh' => [ 'min' => 0, 'max' => 100 ],
                    'vw' => [ 'min' => 0, 'max' => 100 ],
                    'em' => [ 'min' => 0, 'max' => 100 ],
                ],
                'selectors' => [ 
                    '{{WRAPPER}} .fc-video-container' => 'height: {{SIZE}}{{UNIT}}; padding-bottom: 0 !important;', 
                ],
                'description' => 'Ao definir uma altura, a proporção acima é anulada.',
            ]
        );

        $this->add_control( 'object_fit', [
            'label' => 'Ajuste da Imagem/Vídeo',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [ 'cover' => 'Cobrir (Cover)', 'contain' => 'Conter (Contain)' ],
            'default' => 'cover',
            'selectors' => [ 
                '{{WRAPPER}} video' => 'object-fit: {{VALUE}};',
                '{{WRAPPER}} .fc-video-poster' => 'object-fit: {{VALUE}};',
            ],
        ]);

        $this->add_control( 'border_radius', [
            'label' => 'Arredondamento',
            'type' => \Elementor\Controls_Manager::DIMENSIONS,
            'selectors' => [ '{{WRAPPER}} .fc-video-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ]);

        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [
            'name' => 'box_shadow',
            'selector' => '{{WRAPPER}} .fc-video-container',
        ]);

        $this->end_controls_section();

        // --- ESTILO: BOTÃO DE PLAY ---
        $this->start_controls_section( 'section_style_play', [ 'label' => 'Botão de Play', 'tab' => \Elementor\Controls_Manager::TAB_STYLE, 'condition' => ['show_play_icon' => 'yes'] ] );

        $this->add_control( 'play_color', [
            'label' => 'Cor do Ícone',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .fc-play-icon i' => 'color: {{VALUE}};', '{{WRAPPER}} .fc-play-icon svg' => 'fill: {{VALUE}};' ],
        ]);

        $this->add_control( 'play_bg', [
            'label' => 'Cor de Fundo',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => 'rgba(0,0,0,0.6)',
            'selectors' => [ '{{WRAPPER}} .fc-play-btn-wrap' => 'background-color: {{VALUE}};' ],
        ]);

        $this->add_responsive_control( 'play_size', [
            'label' => 'Tamanho do Botão',
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => [ 'px' => [ 'min' => 30, 'max' => 150 ] ],
            'default' => [ 'unit' => 'px', 'size' => 70 ],
            'selectors' => [ '{{WRAPPER}} .fc-play-btn-wrap' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ],
        ]);

        $this->add_control( 'play_radius', [
            'label' => 'Arredondamento',
            'type' => \Elementor\Controls_Manager::SLIDER,
            'default' => [ 'unit' => '%', 'size' => 50 ],
            'selectors' => [ '{{WRAPPER}} .fc-play-btn-wrap' => 'border-radius: {{SIZE}}%;' ],
        ]);

        $this->add_control( 'hover_animation', [
            'label' => 'Animação Hover',
            'type' => \Elementor\Controls_Manager::HOVER_ANIMATION,
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $post_id  = get_the_ID();

        $video_url = '';
        if ( $settings['source_type'] === 'manual' ) {
            if ( $settings['manual_type'] === 'file' && ! empty( $settings['manual_file']['url'] ) ) {
                $video_url = $settings['manual_file']['url'];
            } elseif ( $settings['manual_type'] === 'url' ) {
                $video_url = $settings['manual_url'];
            }
        } else {
            $field_key = $settings['upt_field'];
            if ( ! empty( $field_key ) && $post_id ) {
                $val = get_post_meta( $post_id, $field_key, true );
                if ( is_numeric( $val ) ) {
                    $video_url = wp_get_attachment_url( $val );
                } else {
                    $video_url = $val;
                }
            }
        }

        // Detect provider (YouTube vs arquivo normal)
        $provider    = 'html5';
        $youtube_id  = '';
        if ( ! empty( $video_url ) ) {
            if ( preg_match( '~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|v/|shorts/))([A-Za-z0-9_-]{6,})~', $video_url, $m ) ) {
                $provider   = 'youtube';
                $youtube_id = $m[1];
            }
        }

        $poster_url = '';
        if ( ! empty( $settings['custom_poster']['url'] ) ) {
            $poster_url = $settings['custom_poster']['url'];
        } elseif ( $post_id && has_post_thumbnail( $post_id ) ) {
            $poster_url = get_the_post_thumbnail_url( $post_id, 'large' );
        } elseif ( ! empty( $settings['fallback_image']['url'] ) ) {
            $poster_url = $settings['fallback_image']['url'];
        }

        // Se for YouTube e ainda não tiver poster definido, usa a thumb padrão do YouTube
        if ( 'youtube' === $provider && empty( $poster_url ) && ! empty( $youtube_id ) ) {
            $poster_url = 'https://img.youtube.com/vi/' . $youtube_id . '/hqdefault.jpg';
        }

        $is_lightbox = $settings['enable_lightbox'] === 'yes';
        $ratio_class = 'fc-ratio-' . $settings['aspect_ratio'];
        $uid = $this->get_id();
        
        $video_attrs = 'playsinline';
        if ( $settings['video_loop'] === 'yes' ) $video_attrs .= ' loop';
        if ( $settings['video_mute'] === 'yes' ) $video_attrs .= ' muted';
        if ( $settings['video_controls'] === 'yes' && !$is_lightbox ) $video_attrs .= ' controls';
        if ( $settings['video_autoplay'] === 'yes' && !$is_lightbox ) $video_attrs .= ' autoplay muted';

        $play_html = '';
        if ( $settings['show_play_icon'] === 'yes' ) {
            $anim_class = $settings['hover_animation'] ? 'elementor-animation-' . $settings['hover_animation'] : '';
            ob_start();
            \Elementor\Icons_Manager::render_icon( $settings['play_icon'], [ 'aria-hidden' => 'true' ] );
            $icon = ob_get_clean();
            $play_html = '<div class="fc-play-btn-wrap ' . esc_attr($anim_class) . '"><span class="fc-play-icon">' . $icon . '</span></div>';
        }

        if ( empty($video_url) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<div style="background:#eee; padding:20px; text-align:center; border:1px dashed #ccc;">Selecione um vídeo ou campo válido.</div>';
            }
            return;
        }

        ?>
        
        <div class="fc-video-container <?php echo esc_attr($ratio_class); ?> <?php echo $is_lightbox ? 'fc-lightbox-trigger' : ''; ?>" 
             data-video-url="<?php echo esc_url( $video_url ); ?>"
             data-uid="<?php echo esc_attr( $uid ); ?>"
             data-provider="<?php echo esc_attr( $provider ); ?>"
             <?php if ( 'youtube' === $provider && ! empty( $youtube_id ) ) : ?>
             data-youtube-id="<?php echo esc_attr( $youtube_id ); ?>"
             <?php endif; ?>
        >
            
            <?php if ( $is_lightbox || !empty($poster_url) || $settings['video_autoplay'] !== 'yes' ): ?>
                <div class="fc-poster-wrapper">
                    <?php if ( !empty($poster_url) ): ?>
                        <img src="<?php echo esc_url($poster_url); ?>" class="fc-video-poster" alt="Video Cover">
                    <?php else: ?>
                        <div class="fc-no-poster" style="background:#000; width:100%; height:100%;"></div>
                    <?php endif; ?>
                    
                    <div class="fc-video-overlay"></div>
                    <?php echo $play_html; ?>
                </div>
            <?php endif; ?>

            <?php if ( ! $is_lightbox && 'youtube' !== $provider ): ?>
                <video class="fc-main-video" src="<?php echo esc_url( $video_url ); ?>" <?php echo $video_attrs; ?>></video>
            <?php endif; ?>

        </div>

        <style>
            .fc-video-container { position: relative; width: 100%; overflow: hidden; background-color: #000; cursor: <?php echo $is_lightbox ? 'pointer' : 'default'; ?>; }
            .fc-ratio-169 { padding-bottom: 56.25%; height: 0; }
            .fc-ratio-43 { padding-bottom: 75%; height: 0; }
            .fc-ratio-11 { padding-bottom: 100%; height: 0; }
            .fc-ratio-916 { padding-bottom: 177.77%; height: 0; }
            .fc-ratio-custom { height: auto; }
            .fc-video-container video, .fc-video-container iframe, .fc-video-container .fc-poster-wrapper, .fc-video-container .fc-video-poster, .fc-video-container .fc-video-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }
            .fc-video-container video, .fc-video-container iframe { z-index: 1; }
            .fc-video-container .fc-poster-wrapper { z-index: 2; display: flex; justify-content: center; align-items: center; transition: opacity 0.3s ease; }
            .fc-video-container.is-playing .fc-poster-wrapper { opacity: 0; pointer-events: none; }
            .fc-play-btn-wrap { position: relative; z-index: 5; display: flex; justify-content: center; align-items: center; transition: transform 0.3s ease, background-color 0.3s; }
            .fc-play-icon svg { width: 40%; height: 40%; }
            .fc-play-icon i { font-size: 30px; }

            /* Lightbox CSS */
            
.fc-video-lightbox-modal {
    position: fixed;
    inset: 0;
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    box-sizing: border-box;
    background: rgba(0,0,0,0.65);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.25s ease-in-out;
}
.fc-video-lightbox-modal.active {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}
.fc-lightbox-backdrop {
    position: absolute;
    inset: 0;
    background: transparent;
}
.fc-lightbox-content {
    position: relative;
    z-index: 10;
    background: #fff;
    border-radius: 12px;
    padding: 0;
    max-width: 960px;
    width: 100%;
    box-shadow: 0 8px 28px rgba(0,0,0,0.25);
}
.fc-lightbox-content iframe {
    width: 100%;
    height: auto;
    aspect-ratio: 16/9;
    display: block;
    border-radius: 12px;
}

            
.fc-video-lightbox-modal {
    position: fixed;
    inset: 0;
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    box-sizing: border-box;
    background: rgba(0,0,0,0.65);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.25s ease-in-out;
}
.fc-video-lightbox-modal.active {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}
.fc-lightbox-backdrop {
    position: absolute;
    inset: 0;
    background: transparent;
}
.fc-lightbox-content {
    position: relative;
    z-index: 10;
    background: #fff;
    border-radius: 12px;
    padding: 0;
    max-width: 960px;
    width: 100%;
    box-shadow: 0 8px 28px rgba(0,0,0,0.25);
}
.fc-lightbox-content iframe {
    width: 100%;
    height: auto;
    aspect-ratio: 16/9;
    display: block;
    border-radius: 12px;
}

            .fc-lightbox-content video { width: 100%; height: 100%; display: block; object-fit: contain; }
            
            /* Botão X */
            .fc-lightbox-close { position: absolute; top: 20px; right: 20px; width: 44px; height: 44px; background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(4px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 50%; color: #fff; font-size: 22px; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 20; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
            .fc-lightbox-close::before { content: '\00D7'; font-family: sans-serif; font-weight: 300; line-height: 1; padding-bottom: 2px; }
            .fc-lightbox-close:hover { background: #fff; color: #000; transform: rotate(90deg) scale(1.1); border-color: #fff; }
        </style>

        <script>
        (function($) {
            var WidgetuptVideoHandler = function($scope, $) {
                var container = $scope.find('.fc-video-container');
                var uid = container.data('uid');
                var videoUrl = container.data('video-url');
                var isLightbox = container.hasClass('fc-lightbox-trigger');
                var provider   = container.data('provider') || 'html5';
                var youtubeId  = container.data('youtube-id') || null;

                var lb_autoplay = <?php echo $settings['video_autoplay'] === 'yes' ? 'true' : 'false'; ?>;
                var lb_controls = <?php echo $settings['video_controls'] === 'yes' ? 'true' : 'false'; ?>;
                var lb_mute = <?php echo $settings['video_mute'] === 'yes' ? 'true' : 'false'; ?>;
                var lb_loop = <?php echo $settings['video_loop'] === 'yes' ? 'true' : 'false'; ?>;

                if (isLightbox) {
                    container.off('click').on('click', function(e) {
                        e.preventDefault();
                        if(!videoUrl) return;

                        var modalId = 'fc-lb-' + uid;
                        if ($('#' + modalId).length > 0) return;

                        var attrs = 'playsinline';
                        if(lb_autoplay) attrs += ' autoplay';
                        if(lb_controls) attrs += ' controls';
                        if(lb_mute) attrs += ' muted';
                        if(lb_loop) attrs += ' loop';

                        var modalHTML;

                        if (provider === 'youtube' && youtubeId) {
                            var params = [];
                            if (lb_autoplay) params.push('autoplay=1');
                            if (!lb_controls) params.push('controls=0');
                            if (lb_mute) params.push('mute=1');
                            var query = params.length ? '?' + params.join('&') : '';
                            var src   = 'https://www.youtube.com/embed/' + youtubeId + query;

                            modalHTML = `
                                <div class="fc-video-lightbox-modal" id="${modalId}">
                                    <div class="fc-lightbox-backdrop"></div>
                                    <div class="fc-lightbox-content">
                                        <div class="fc-lightbox-close" title="Fechar"></div>
                                        <iframe src="${src}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                    </div>
                                </div>
                            `;
                        } else {
                            modalHTML = `
                                <div class="fc-video-lightbox-modal" id="${modalId}">
                                    <div class="fc-lightbox-backdrop"></div>
                                    <div class="fc-lightbox-content">
                                        <div class="fc-lightbox-close" title="Fechar"></div>
                                        <video src="${videoUrl}" ${attrs}></video>
                                    </div>
                                </div>
                            `;
                        }

                        $('body').append(modalHTML);
                        var $modal = $('#' + modalId);
                        
                        requestAnimationFrame(function() {
                            $modal.addClass('active');
                        });

                        var closeLB = function() {
                            $modal.removeClass('active');

                            var vid = $modal.find('video')[0];
                            if (vid) {
                                vid.pause();
                                vid.src = "";
                                vid.load();
                            }

                            var iframe = $modal.find('iframe')[0];
                            if (iframe) {
                                iframe.src = '';
                            }

                            setTimeout(function(){ $modal.remove(); }, 400);
                            $(document).off('keyup.' + modalId);
                        };

                        $modal.find('.fc-lightbox-backdrop, .fc-lightbox-close').on('click', closeLB);
                        
                        $(document).on('keyup.' + modalId, function(e) {
                            if(e.key === "Escape") closeLB();
                        });
                    });
                } else {
                    // Comportamento inline (sem lightbox)
                    container.off('click').on('click', function() {
                        var $this = $(this);

                        if (provider === 'youtube' && youtubeId) {
                            var existingIframe = $this.find('iframe');
                            if (!existingIframe.length) {
                                var params = ['autoplay=1'];
                                if (!lb_controls) params.push('controls=0');
                                if (lb_mute) params.push('mute=1');
                                var query = params.length ? '?' + params.join('&') : '';
                                var src   = 'https://www.youtube.com/embed/' + youtubeId + query;

                                var iframe = $('<iframe>', {
                                    src: src,
                                    frameborder: 0,
                                    allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture',
                                    allowfullscreen: 'allowfullscreen'
                                });

                                $this.append(iframe);
                                $this.addClass('is-playing');
                            } else {
                                // Se clicar novamente, remove o iframe para "parar" o vídeo
                                var ifr = existingIframe.get(0);
                                ifr.src = '';
                                existingIframe.remove();
                                $this.removeClass('is-playing');
                            }
                        } else {
                            var vid = $this.find('video').get(0);
                            if(!vid) return;

                            if(vid.paused) {
                                vid.play();
                                $this.addClass('is-playing');
                            } else {
                                vid.pause();
                                if(!vid.controls) { 
                                    $this.removeClass('is-playing'); 
                                }
                            }
                        }
                    });

                    var vidElement = container.find('video');
                    if(vidElement.length) {
                        vidElement.on('ended', function(){
                            container.removeClass('is-playing');
                        });
                    }

      }
            };

            $(window).on('elementor/frontend/init', function () {
                elementorFrontend.hooks.addAction('frontend/element_ready/upt_video.default', WidgetuptVideoHandler);
            });
        })(jQuery);
        </script>

        <?php
    }
}
