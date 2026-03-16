<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Dashboard_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'upt_dashboard';
    }

    private function render_preview_component( $component, $settings ) {
        echo '<div class="upt-preset-hostinger">';
        switch ( $component ) {
            case 'modal_product':
                $cols = isset( $settings['modal_product_columns'] ) ? max( 1, (int) $settings['modal_product_columns'] ) : 2;
                $gap  = isset( $settings['modal_product_gap']['size'] ) ? (float) $settings['modal_product_gap']['size'] : 14;
                $maxw = isset( $settings['modal_product_max_width']['size'] ) ? (float) $settings['modal_product_max_width']['size'] : 840;
                $maxw_unit = isset( $settings['modal_product_max_width']['unit'] ) ? $settings['modal_product_max_width']['unit'] : 'px';

                echo '<div id="upt-modal-wrapper" class="upt-modal-wrapper" style="display:block; position:relative;">
                        <div id="upt-modal-content" class="upt-modal-content" style="position:relative; max-width:' . esc_attr( $maxw ) . esc_attr( $maxw_unit ) . '; margin:0 auto; background: var(--fc-container-bg, #fff); padding:30px; border-radius: var(--fc-border-radius, 8px); box-shadow: 0 10px 40px rgba(0,0,0,0.12);">
                            <a href="#" id="upt-modal-close" class="upt-modal-close-button" style="position:absolute; top:12px; right:12px; font-size:28px; text-decoration:none;">&times;</a>
                            <h3 class="upt-modal-title">Pré-visualização — Modal de Produto</h3>
                            <p style="margin:12px 0 16px; color:#475569;">Edite a grade abaixo para definir o layout do modal de produto.</p>
                            <form class="upt-submit-form" style="display:grid; grid-template-columns:repeat(' . esc_attr( $cols ) . ', 1fr); gap:' . esc_attr( $gap ) . 'px; align-items:start;">
                                <label style="display:flex; flex-direction:column; gap:6px;">Nome do Produto
                                    <input type="text" placeholder="Ex.: Camera Mirrorless" />
                                </label>
                                <label style="display:flex; flex-direction:column; gap:6px; grid-column: span ' . esc_attr( max(1, $cols - 1) ) . ';">Descrição
                                    <textarea rows="4" placeholder="Adicione uma descrição..."></textarea>
                                </label>
                                <label style="display:flex; flex-direction:column; gap:6px;">Categoria
                                    <select><option>Escolha uma categoria</option></select>
                                </label>
                                <div style="display:flex; gap:10px; align-items:center;">
                                    <a href="#" class="button button-primary">Salvar</a>
                                    <a href="#" class="button button-secondary">Cancelar</a>
                                </div>
                            </form>
                        </div>
                    </div>';
                break;
            case 'alert_badge':
                $badge_text = ( isset( $settings['alert_badge_default_text'] ) && $settings['alert_badge_default_text'] !== '' )
                    ? $settings['alert_badge_default_text']
                    : 'Você tem novas notificações do upt';

                echo '<div style="padding:20px;">
                        <div class="upt-alert-badge" style="display:inline-flex; align-items:center; gap:10px; padding:12px 16px; background: var(--fc-body-bg, #f9fafb); border:1px solid var(--fc-border-color, #e2e8f0); border-radius: var(--fc-border-radius, 8px);">
                            <button type="button" aria-label="Abrir notificações" style="border:none; background:transparent; cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
                                <span class="upt-alert-icon" style="width:12px; height:12px; border-radius:50%; background: var(--fc-primary-color, #1a73e8);"></span>
                                <span class="upt-alert-text">' . esc_html( $badge_text ) . '</span>
                            </button>
                            <span class="upt-alert-count" style="min-width:26px; text-align:center; padding:4px 8px; border-radius:999px; background: var(--fc-primary-color, #1a73e8); color:#fff; font-weight:600;">3</span>
                        </div>
                    </div>';
                break;
            case 'item_card':
                $card_bg        = $settings['item_card_preview_bg'] ?? '#ffffff';
                $card_border    = $settings['item_card_preview_border'] ?? '#e2e8f0';
                $card_radius    = $settings['item_card_preview_radius']['size'] ?? 10;
                $card_padding   = $settings['item_card_preview_padding']['size'] ?? 16;
                $title_color    = $settings['item_card_preview_title_color'] ?? '#0f172a';
                $meta_color     = $settings['item_card_preview_meta_color'] ?? '#475569';
                $tag_bg         = $settings['item_card_preview_tag_bg'] ?? '#eef2ff';
                $tag_color      = $settings['item_card_preview_tag_color'] ?? '#4338ca';
                $btn_bg         = $settings['item_card_preview_btn_bg'] ?? '#1a73e8';
                $btn_color      = $settings['item_card_preview_btn_color'] ?? '#ffffff';

                echo '<div style="padding:16px;">
                    <div class="upt-item-card-preview" style="background:' . esc_attr( $card_bg ) . '; border:1px solid ' . esc_attr( $card_border ) . '; border-radius:' . esc_attr( $card_radius ) . 'px; padding:' . esc_attr( $card_padding ) . 'px; display:grid; grid-template-columns:120px 1fr; gap:16px; align-items:start; box-shadow:0 6px 20px rgba(15,23,42,0.06);">
                        <div style="width:120px; height:120px; background:#f1f5f9; border-radius:' . esc_attr( $card_radius ) . 'px;"></div>
                        <div style="display:grid; gap:10px;">
                            <div style="display:flex; gap:8px; align-items:center;">
                                <span style="background:' . esc_attr( $tag_bg ) . '; color:' . esc_attr( $tag_color ) . '; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:600;">Em Destaque</span>
                                <span style="color:' . esc_attr( $meta_color ) . '; font-size:12px;">Atualizado há 2h</span>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <span style="font-weight:700; color:' . esc_attr( $title_color ) . '; font-size:18px; line-height:1.3;">Título do Item Personalizável</span>
                                <span style="color:' . esc_attr( $meta_color ) . '; font-size:14px;">Resumo curto do item para visualizar espaçamento e tipografia.</span>
                            </div>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <button style="background:' . esc_attr( $btn_bg ) . '; color:' . esc_attr( $btn_color ) . '; border:none; border-radius:8px; padding:10px 14px; cursor:pointer; font-weight:600;">Editar</button>
                                <button style="background:transparent; color:' . esc_attr( $meta_color ) . '; border:1px solid ' . esc_attr( $card_border ) . '; border-radius:8px; padding:10px 14px; cursor:pointer;">Ver</button>
                            </div>
                        </div>
                    </div>
                </div>';
                break;
            case 'dashboard':
            default:
                echo '<div class="upt-dashboard-preview-note" style="padding:16px; border:1px dashed #d7dce2; border-radius:8px; background:#f8fafc;">Pré-visualização padrão do painel.</div>';
                break;
        }
        echo '</div>';
    }

    public function get_title() {
        return 'Painel upt';
    }

    public function get_icon() {
        return 'eicon-user-circle-o';
    }

    public function get_categories() {
        return [ 'upt' ];
    }

    public function get_script_depends() {
        return [ 'upt-main-js' ];
    }

    public function get_style_depends() {
        return [ 'upt-style' ];
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
        $options = ['0' => '— Padrão do Painel —'];
        if ($query->have_posts()) {
            while($query->have_posts()) {
                $query->the_post();
                $options[get_the_ID()] = get_the_title();
            }
        }
        wp_reset_postdata();
        return $options;
    }

    private function get_catalog_schema_term_options() {
        $options = [];

        if ( ! taxonomy_exists( 'catalog_schema' ) ) {
            return $options;
        }

        $terms = get_terms(
            [
                'taxonomy'   => 'catalog_schema',
                'hide_empty' => false,
            ]
        );

        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return $options;
        }

        foreach ( $terms as $term ) {
            if ( empty( $term->slug ) ) {
                continue;
            }
            $options[ (string) $term->slug ] = (string) $term->name;
        }

        return $options;
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Conteúdo',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );


        $this->add_control('dashboard_preset', [
            'label'       => 'Layout do Painel',
            'type'        => \Elementor\Controls_Manager::SELECT,
            'options'     => [
                'hostinger' => '🏠 Clássico (Largura máxima)',
            ],
            'default'     => 'hostinger',
            'description' => 'Escolha o layout geral do painel.',
        ]);

        $this->add_control('heading_normal_brand', [
            'label' => 'Marca (Modo Normal)',
            'type' => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('normal_brand_mode', [
            'label'   => 'Marca do Cabeçalho',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'logo',
            'options' => [
                'logo' => 'Logo do Cliente',
                'text' => 'Texto do Cliente',
                'none' => 'Nenhuma',
            ],
        ]);

        $this->add_control('normal_logo_image', [
            'label'       => 'Logo do Cliente',
            'type'        => \Elementor\Controls_Manager::MEDIA,
            'condition'   => [ 'normal_brand_mode' => 'logo' ],
        ]);

        $this->add_control('normal_logo_width', [
            'label'      => 'Largura do Logo',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 20, 'max' => 240]],
            'default'    => ['size' => 120, 'unit' => 'px'],
            'condition'  => [ 'normal_brand_mode' => 'logo' ],
        ]);

        $this->add_control('normal_brand_text', [
            'label'     => 'Texto do Cliente',
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '',
            'condition' => [ 'normal_brand_mode' => 'text' ],
        ]);

        $this->add_control(
            'preview_component',
            [
                'label' => 'Pré-visualização no Editor',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'dashboard'     => 'Painel (padrão)',
                    'modal_product' => 'Modal de Produto',
                    'alert_badge'   => 'Badge de Aviso',
                    'item_card'     => 'Card de Item',
                ],
                'default' => 'dashboard',
                'description' => 'Escolha qual componente do upt visualizar dentro do editor do Elementor. Não afeta o front-end; serve apenas para edição.',
            ]
        );

        $this->add_control(
            'enable_dashboard',
            [
                'label' => 'Ativar dashboard',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Ativo',
                'label_off' => 'Inativo',
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => 'Ative ou desative a exibição do painel de rastreamento.',
            ]
        );

$this->add_control(
            'item_card_template_id',
            [
                'label' => 'Template do Card do Item (Loop)',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_all_loop_templates(),
                'default' => '0',
                'description' => 'Selecione um template de Loop para renderizar os cards dos itens. Se "Padrão" for selecionado, o layout será o nativo do painel.',
            ]
        );

        $this->add_control(
            'item_card_variant',
            [
                'label' => 'Layout nativo do card',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'modern' => 'Novo card upt',
                    'legacy' => 'Padrão (legado)',
                ],
                'default' => 'modern',
                'description' => 'Escolha o layout nativo quando não houver template do Loop selecionado.',
                'condition' => [
                    'item_card_template_id' => '0',
                ],
            ]
        );



        // Configurações do cabeçalho do formulário de login
        $this->add_control(
            'login_header_type',
            [
                'label' => 'Cabeçalho do Login',
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'text' => [
                        'title' => 'Texto',
                        'icon'  => 'eicon-editor-bold',
                    ],
                    'image' => [
                        'title' => 'Imagem',
                        'icon'  => 'eicon-image-bold',
                    ],
                    'none' => [
                        'title' => 'Nenhum',
                        'icon'  => 'eicon-ban',
                    ],
                ],
                'default' => 'text',
                'toggle' => false,
                'description' => 'Defina se o topo do formulário de login usará texto, imagem ou ficará oculto.',
            ]
        );

        $this->add_control(
            'login_header_text',
            [
                'label' => 'Texto do Cabeçalho',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Entrar',
                'placeholder' => 'Digite o texto do cabeçalho do login',
                'condition' => [
                    'login_header_type' => 'text',
                ],
            ]
        );

        $this->add_control(
            'login_header_image',
            [
                'label' => 'Imagem do Cabeçalho',
                'type' => \Elementor\Controls_Manager::MEDIA,
                'condition' => [
                    'login_header_type' => 'image',
                ],
            ]
        );

        $this->add_responsive_control(
            'login_header_image_width',
            [
                'label' => 'Largura da Imagem',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ '%', 'px', 'vw' ],
                'range' => [
                    '%' => [
                        'min' => 5,
                        'max' => 100,
                    ],
                    'px' => [
                        'min' => 40,
                        'max' => 600,
                    ],
                    'vw' => [
                        'min' => 5,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .upt-login-form .form-logo img' => 'width: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'login_header_type' => 'image',
                ],
            ]
        );
        $this->add_control(
            'show_all_items',
            [
                'label' => 'Mostrar Itens de Todos os Usuários?',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sim',
                'label_off' => 'Não',
                'return_value' => 'yes',
                'default' => 'no',
                'description' => 'Se "Sim", administradores verão todos os itens. Outros usuários continuarão vendo apenas os seus.',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'hide_admin_bar',
            [
                'label' => 'Ocultar Barra de Admin do WordPress?',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sim',
                'label_off' => 'Não',
                'return_value' => 'yes',
                'default' => 'no',
                'description' => 'Ative para esconder a barra preta do topo do site nesta página.',
            ]
        );

        $this->add_control(
            'enabled_filters',
            [
                'label' => 'Filtros a Exibir',
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => [
                    'search' => 'Pesquisa',
                    'category' => 'Categoria',
                ],
                'default' => ['search', 'category'],

'separator' => 'before',
            ]
        );

        
        $this->add_control(
            'panel_category_show_subcategories',
            [
                'label' => 'Filtro de Categoria: Exibir Subcategorias',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sim',
                'label_off' => 'Não',
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => 'No painel upt, controla se o filtro de categorias mostra subcategorias (hierárquico) ou apenas as categorias diretas do esquema.',
            ]
        );

        $this->add_control(
            'panel_category_show_sub_badge',
            [
                'label' => 'Filtro de Categoria: Mostrar badge de Sub',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sim',
                'label_off' => 'Não',
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => 'Exibe um badge ao lado do filtro indicando que subcategorias estão habilitadas.',
            ]
        );

        $this->add_control(
            'panel_category_sub_badge_text',
            [
                'label' => 'Texto do badge (Sub)',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Sub',
                'placeholder' => 'Sub',
                'description' => 'Texto do badge exibido ao lado do filtro de categorias quando subcategorias estão habilitadas.',
            ]
        );

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
            'items_per_page',
            [
                'label' => 'Itens por Página',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'default' => 9,
                'condition' => [
                    'enable_pagination' => 'yes',
                ],
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
                'default' => 'infinite',
                'condition' => [
                    'enable_pagination' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_schema_counters',
            [
                'label' => 'Mostrar Contador de Itens por Esquema',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sim',
                'label_off' => 'Não',
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => 'Exibe um contador de itens ao lado do nome de cada aba de esquema.',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'tabs_media_section',
            [
                'label' => 'Ícones/Imagens das Abas',
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'tabs_media_dashboard_type',
            [
                'label' => 'Dashboard (tipo)',
                'type'  => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'none'  => 'Nenhum',
                    'icon'  => 'Ícone',
                    'image' => 'Imagem',
                ],
                'default' => 'none',
            ]
        );

        $this->add_control(
            'tabs_media_dashboard_icon',
            [
                'label' => 'Dashboard (ícone)',
                'type'  => \Elementor\Controls_Manager::ICONS,
                'condition' => [
                    'tabs_media_dashboard_type' => 'icon',
                ],
            ]
        );

        $this->add_control(
            'tabs_media_dashboard_image',
            [
                'label' => 'Dashboard (imagem)',
                'type'  => \Elementor\Controls_Manager::MEDIA,
                'condition' => [
                    'tabs_media_dashboard_type' => 'image',
                ],
            ]
        );

        $this->add_control(
            'tabs_media_forms_type',
            [
                'label' => 'Formulários (tipo)',
                'type'  => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'none'  => 'Nenhum',
                    'icon'  => 'Ícone',
                    'image' => 'Imagem',
                ],
                'default' => 'none',
            ]
        );

        $this->add_control(
            'tabs_media_forms_icon',
            [
                'label' => 'Formulários (ícone)',
                'type'  => \Elementor\Controls_Manager::ICONS,
                'condition' => [
                    'tabs_media_forms_type' => 'icon',
                ],
            ]
        );

        $this->add_control(
            'tabs_media_forms_image',
            [
                'label' => 'Formulários (imagem)',
                'type'  => \Elementor\Controls_Manager::MEDIA,
                'condition' => [
                    'tabs_media_forms_type' => 'image',
                ],
            ]
        );

        $schema_options = $this->get_catalog_schema_term_options();
        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'schema_slug',
            [
                'label'   => 'Esquema',
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => $schema_options,
            ]
        );

        $repeater->add_control(
            'media_type',
            [
                'label' => 'Tipo',
                'type'  => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'none'  => 'Nenhum',
                    'icon'  => 'Ícone',
                    'image' => 'Imagem',
                ],
                'default' => 'none',
            ]
        );

        $repeater->add_control(
            'icon',
            [
                'label' => 'Ícone',
                'type'  => \Elementor\Controls_Manager::ICONS,
                'condition' => [
                    'media_type' => 'icon',
                ],
            ]
        );

        $repeater->add_control(
            'image',
            [
                'label' => 'Imagem',
                'type'  => \Elementor\Controls_Manager::MEDIA,
                'condition' => [
                    'media_type' => 'image',
                ],
            ]
        );

        $this->add_control(
            'tabs_schema_media',
            [
                'label'  => 'Esquemas (ícones/imagens)',
                'type'   => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'title_field' => '{{{ schema_slug }}}',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'gallery_pagination_section',
            [
                'label' => 'Paginação da Galeria',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'enable_gallery_pagination',
            [
                'label' => 'Ativar Paginação da Galeria',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sim',
                'label_off' => 'Não',
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'gallery_items_per_page',
            [
                'label' => 'Mídias por Página',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'default' => 30,
                'condition' => [
                    'enable_gallery_pagination' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'gallery_pagination_type',
            [
                'label' => 'Tipo de Paginação',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'numbers' => 'Números (padrão)',
                    'arrows' => 'Setas',
                    'prev_next' => 'Anterior / Próximo',
                    'infinite' => 'Carregar mais (infinito)',
                ],
                'default' => 'infinite',
                'condition' => [
                    'enable_gallery_pagination' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'gallery_pagination_numbers_nav',
            [
                'label' => 'Mostrar Anterior / Próximo no modo Números',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sim',
                'label_off' => 'Não',
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'enable_gallery_pagination' => 'yes',
                    'gallery_pagination_type' => 'numbers',
                ],
            ]
        );

        $this->add_control(
            'gallery_pagination_infinite_trigger',
            [
                'label' => 'Gatilho (modo infinito)',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'scroll' => 'Scroll',
                    'button' => 'Botão',
                    'both' => 'Scroll + Botão',
                ],
                'default' => 'scroll',
                'condition' => [
                    'enable_gallery_pagination' => 'yes',
                    'gallery_pagination_type' => 'infinite',
                ],
            ]
        );

        $this->add_responsive_control(
            'tabs_gap',
            [
                'label' => 'Espaçamento do Ícone',
                'type'  => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range' => [
                    'px' => [ 'min' => 0, 'max' => 40 ],
                    'em' => [ 'min' => 0, 'max' => 4 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .upt-tabs-nav a' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'gallery_pagination_load_more_label',
            [
                'label' => 'Texto do Botão',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Carregar mais',
                'condition' => [
                    'enable_gallery_pagination' => 'yes',
                    'gallery_pagination_type' => 'infinite',
                    'gallery_pagination_infinite_trigger' => [ 'button', 'both' ],
                ],
            ]
        );

        $this->end_controls_section();

        
// --- Alerta Visual (Badge) ---
$this->start_controls_section(
    'section_alert_badge',
    [
        'label' => 'Badge de Alerta',
        'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
    ]
);

$this->add_control(
    'alert_badge_enabled',
    [
        'label'        => 'Ativar Badge de Alerta',
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => 'Sim',
        'label_off'    => 'Não',
        'return_value' => 'yes',
        'default'      => 'yes',
    ]
);

$this->add_control(
    'alert_badge_default_text',
    [
        'label'       => 'Texto padrão do badge',
        'type'        => \Elementor\Controls_Manager::TEXT,
        'default'     => 'Você tem novas notificações do upt',
        'placeholder' => 'Digite o texto do aviso',
        'condition'   => [ 'alert_badge_enabled' => 'yes' ],
    ]
);

$this->add_control(
    'alert_badge_duration',
    [
        'label' => 'Tempo de Exibição (segundos)',
        'type'  => \Elementor\Controls_Manager::NUMBER,
        'min'   => 0,
        'step'  => 0.5,
        'default' => 4,
        'description' => '0 = não auto-fecha',
        'condition' => [ 'alert_badge_enabled' => 'yes' ],
    ]
);

$this->add_control(
    'alert_on_create',
    [
        'label'        => 'Aviso ao Criar Item',
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => 'Sim',
        'label_off'    => 'Não',
        'return_value' => 'yes',
        'default'      => 'yes',
        'condition'    => [ 'alert_badge_enabled' => 'yes' ],
    ]
);

$this->add_control(
    'alert_on_edit',
    [
        'label'        => 'Aviso ao Editar Item',
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => 'Sim',
        'label_off'    => 'Não',
        'return_value' => 'yes',
        'default'      => 'yes',
        'condition'    => [ 'alert_badge_enabled' => 'yes' ],
    ]
);

$this->add_control(
    'alert_on_delete',
    [
        'label'        => 'Aviso ao Apagar Item',
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => 'Sim',
        'label_off'    => 'Não',
        'return_value' => 'yes',
        'default'      => 'yes',
        'condition'    => [ 'alert_badge_enabled' => 'yes' ],
    ]
);

$this->add_control(
    'alert_on_draft',
    [
        'label'        => 'Aviso ao Salvar Rascunho',
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => 'Sim',
        'label_off'    => 'Não',
        'return_value' => 'yes',
        'default'      => 'yes',
        'condition'    => [ 'alert_badge_enabled' => 'yes' ],
    ]
);

$this->add_control(
    'alert_on_login',
    [
        'label'        => 'Aviso em Login Bem-sucedido',
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => 'Sim',
        'label_off'    => 'Não',
        'return_value' => 'yes',
        'default'      => 'yes',
        'condition'    => [ 'alert_badge_enabled' => 'yes' ],
    ]
);


$this->add_control(
    'alert_on_schema_qty',
    [
        'label'        => 'Aviso de Quantidade Mín/Máx do Esquema',
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => 'Sim',
        'label_off'    => 'Não',
        'return_value' => 'yes',
        'default'      => 'yes',
        'condition'    => [ 'alert_badge_enabled' => 'yes' ],
    ]
);

$this->add_control(
    'alert_on_media_deleted',
    [
        'label'        => 'Aviso de Mídia Apagada',
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => 'Sim',
        'label_off'    => 'Não',
        'return_value' => 'yes',
        'default'      => 'yes',
        'condition'    => [ 'alert_badge_enabled' => 'yes' ],
    ]
);

$this->add_control(
    'alert_on_media_uploaded',
    [
        'label'        => 'Aviso de Mídia Enviada',
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => 'Sim',
        'label_off'    => 'Não',
        'return_value' => 'yes',
        'default'      => 'yes',
        'condition'    => [ 'alert_badge_enabled' => 'yes' ],
    ]
);

$this->add_control(
    'alert_on_media_moved',
    [
        'label'        => 'Aviso de Mídia Movida',
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => 'Sim',
        'label_off'    => 'Não',
        'return_value' => 'yes',
        'default'      => 'yes',
        'condition'    => [ 'alert_badge_enabled' => 'yes' ],
    ]
);

$this->add_control(
    'alert_on_category_created',
    [
        'label'        => 'Aviso de Categoria Criada',
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => 'Sim',
        'label_off'    => 'Não',
        'return_value' => 'yes',
        'default'      => 'yes',
        'condition'    => [ 'alert_badge_enabled' => 'yes' ],
    ]
);

$this->add_control(
    'alert_on_category_renamed',
    [
        'label'        => 'Aviso de Categoria Renomeada',
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => 'Sim',
        'label_off'    => 'Não',
        'return_value' => 'yes',
        'default'      => 'yes',
        'condition'    => [ 'alert_badge_enabled' => 'yes' ],
    ]
);

$this->add_control(
    'alert_on_category_deleted',
    [
        'label'        => 'Aviso de Categoria Apagada',
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => 'Sim',
        'label_off'    => 'Não',
        'return_value' => 'yes',
        'default'      => 'yes',
        'condition'    => [ 'alert_badge_enabled' => 'yes' ],
    ]
);
$this->end_controls_section();

$this->start_controls_section(
            'section_style_general',
            [
                'label' => 'Estilo Geral',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'accent_color',
            [
                'label' => 'Cor Principal (Destaque)',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#673DE6',
                'selectors' => [
                    '{{WRAPPER}} .upt-preset-hostinger' => '--fc-primary-color: {{VALUE}};',
                    '#upt-modal-content' => '--fc-primary-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'saas_primary_color',
            [
                'label' => 'Cor Principal (SaaS)',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#6366f1',
                'selectors' => [
                    '{{WRAPPER}} .upt-preset-saas' => '--saas-primary: {{VALUE}} !important;',
                    '#upt-modal-content' => '--saas-primary: {{VALUE}} !important;',
                ],
                'condition' => [ 'dashboard_preset' => 'saas' ],
            ]
        );

        $this->add_control(
            'main_font_family',
            [
                'label' => 'Fonte Principal',
                'type' => \Elementor\Controls_Manager::FONT,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .upt-preset-hostinger' => '--fc-font-family: "{{VALUE}}", sans-serif;',
                    '#upt-modal-content' => '--fc-font-family: "{{VALUE}}", sans-serif;',
                ],
            ]
        );

        $this->end_controls_section();

        // =====================================================
        // VISUAL DO PAINEL — Design & Personalização SaaS
        // =====================================================
        $this->start_controls_section('section_saas_design', [
            'label' => '🎨 Visual do Painel (SaaS)',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => [
                'dashboard_preset' => 'saas',
            ],
        ]);

        $this->add_control('saas_brand_mode', [
            'label'   => 'Marca da Sidebar',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'logo',
            'options' => [
                'logo' => 'Logo do Cliente',
                'text' => 'Texto do Cliente',
            ],
        ]);

        $this->add_control('saas_logo_image', [
            'label'       => 'Logo da Barra Lateral',
            'type'        => \Elementor\Controls_Manager::MEDIA,
            'description' => 'Substitui o placeholder pela logo do cliente.',
            'condition'   => [ 'saas_brand_mode' => 'logo' ],
        ]);

        $this->add_control('saas_logo_width', [
            'label'      => 'Largura do Logo',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 20, 'max' => 200]],
            'default'    => ['size' => 100, 'unit' => 'px'],
            'condition'  => [ 'saas_brand_mode' => 'logo' ],
        ]);

        $this->add_control('saas_brand_text', [
            'label'     => 'Texto do Cliente',
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '',
            'condition' => [ 'saas_brand_mode' => 'text' ],
        ]);

        $this->add_control('saas_sidebar_bg', [
            'label'     => 'Fundo da Sidebar',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#111827',
            'separator' => 'before',
        ]);

        $this->add_control('saas_header_bg', [
            'label'     => 'Fundo do Cabeçalho',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#ffffff',
        ]);

        $this->add_control('saas_body_bg', [
            'label'   => 'Fundo do Conteúdo',
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => '#f1f5f9',
        ]);

        $this->add_control('saas_card_bg', [
            'label'   => 'Fundo dos Cards',
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => '#ffffff',
        ]);

        $this->add_control('saas_sidebar_width', [
            'label'      => 'Largura da Sidebar',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 140, 'max' => 420]],
            'default'    => ['size' => 240, 'unit' => 'px'],
            'separator'  => 'before',
        ]);

        $this->add_control('saas_border_radius', [
            'label'      => 'Arredondamento (px)',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 8, 'unit' => 'px'],
        ]);

        $this->add_control('saas_add_btn_label', [
            'label'     => 'Texto do Botão Adicionar',
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'Adicionar Novo Item',
            'separator' => 'before',
        ]);

        $this->end_controls_section();

        // Estilo do Modal de Produto (Preview)
        $this->start_controls_section(
            'section_style_modal_product',
            [
                'label' => 'Modal de Produto',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [ 'preview_component' => 'modal_product' ],
            ]
        );

        $this->add_responsive_control(
            'modal_product_max_width',
            [
                'label' => 'Largura Máxima',
                'type'  => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => 480, 'max' => 1400 ],
                    '%'  => [ 'min' => 40, 'max' => 100 ],
                ],
                'default' => [ 'size' => 840, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} #upt-modal-content' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'modal_product_columns',
            [
                'label' => 'Colunas do Formulário',
                'type'  => \Elementor\Controls_Manager::SELECT,
                'options' => [ '1' => '1', '2' => '2', '3' => '3' ],
                'default' => '2',
                'selectors' => [
                    '{{WRAPPER}} .upt-submit-form' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ],
            ]
        );

        $this->add_responsive_control(
            'modal_product_gap',
            [
                'label' => 'Espaçamento Interno',
                'type'  => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
                'default' => [ 'size' => 14, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-submit-form' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Seção de Estilo do Formulário de Login
        $this->start_controls_section(
            'section_style_login_form',
            [
                'label' => 'Formulário de Login',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control('heading_login_container', ['label' => 'Contêiner', 'type' => \Elementor\Controls_Manager::HEADING]);
        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), ['name' => 'login_container_background', 'selector' => '{{WRAPPER}} .upt-login-form form']);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'login_container_border', 'selector' => '{{WRAPPER}} .upt-login-form form']);
        $this->add_responsive_control('login_container_padding', ['label' => 'Padding', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%', 'em'], 'selectors' => ['{{WRAPPER}} .upt-login-form form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('login_container_border_radius', ['label' => 'Arredondamento', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'selectors' => ['{{WRAPPER}} .upt-login-form form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'login_container_box_shadow', 'selector' => '{{WRAPPER}} .upt-login-form form']);

        $this->add_control('heading_login_title', ['label' => 'Título', 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('login_title_color', ['label' => 'Cor', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-login-form .form-title' => 'color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'login_title_typography', 'selector' => '{{WRAPPER}} .upt-login-form .form-title']);
        
        $this->add_control('heading_login_labels', ['label' => 'Rótulos (Labels)', 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('login_labels_color', ['label' => 'Cor', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-login-form label' => 'color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'login_labels_typography', 'selector' => '{{WRAPPER}} .upt-login-form label']);
        
        $this->add_control('heading_login_fields', ['label' => 'Campos de Texto', 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'login_fields_typography', 'selector' => '{{WRAPPER}} .upt-login-form .input']);
        $this->start_controls_tabs('tabs_login_fields_style');
        $this->start_controls_tab('tab_login_fields_normal', ['label' => 'Normal']);
        $this->add_control('login_fields_color', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-login-form .input' => 'color: {{VALUE}};' ]]);
        $this->add_control('login_fields_bg_color', ['label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-login-form .input' => 'background-color: {{VALUE}};' ]]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'login_fields_border', 'selector' => '{{WRAPPER}} .upt-login-form .input']);
        $this->end_controls_tab();
        $this->start_controls_tab('tab_login_fields_focus', ['label' => 'Foco']);
        $this->add_control('login_fields_color_focus', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-login-form .input:focus' => 'color: {{VALUE}};' ]]);
        $this->add_control('login_fields_bg_color_focus', ['label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-login-form .input:focus' => 'background-color: {{VALUE}};' ]]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'login_fields_border_focus', 'selector' => '{{WRAPPER}} .upt-login-form .input:focus']);
        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control('heading_login_toggle', ['label' => 'Interruptor "Manter Conectado"', 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('login_toggle_text_color', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .remember-me-toggle .toggle-label' => 'color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'login_toggle_typography', 'label' => 'Tipografia do Texto', 'selector' => '{{WRAPPER}} .remember-me-toggle .toggle-label']);
        $this->add_control('login_toggle_track_off_color', ['label' => 'Cor do Fundo (Desligado)', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .remember-me-toggle .toggle-switch' => 'background-color: {{VALUE}};']]);
        $this->add_control('login_toggle_track_on_color', ['label' => 'Cor do Fundo (Ligado)', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .remember-me-toggle input:checked + .toggle-switch' => 'background-color: {{VALUE}};']]);
        $this->add_control('login_toggle_handle_color', ['label' => 'Cor do Círculo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .remember-me-toggle .toggle-switch::before' => 'background-color: {{VALUE}};']]);

        $this->add_control('heading_login_bottom_text', ['label' => 'Texto "Acesso Restrito"', 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('login_bottom_text_color', ['label' => 'Cor', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-login-form .restricted-access' => 'color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'login_bottom_text_typography', 'selector' => '{{WRAPPER}} .upt-login-form .restricted-access']);

        $this->end_controls_section();

        // Seção de Estilo do Plano de Fundo Principal
        $this->start_controls_section(
            'section_style_background',
            [
                'label' => 'Plano de Fundo (Geral)',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'main_background',
                'label' => 'Plano de Fundo Principal',
                'types' => [ 'classic', 'gradient' ],
                'selector' => '{{WRAPPER}} .upt-dashboard-wrapper',
            ]
        );

        $this->add_control(
            'background_overlay_heading',
            [
                'label' => 'Sobreposição de Fundo',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'background_overlay_color',
            [
                'label' => 'Cor da Sobreposição',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .upt-dashboard-wrapper::before' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'style_section_main_cards',
            [
                'label' => 'Layout e Cartões Principais',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'heading_grid',
            [
                'label' => 'Grade de Itens',
                'type' => \Elementor\Controls_Manager::HEADING,
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => 'Colunas',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6',
                ],
                 'selectors' => [
                    '{{WRAPPER}} .upt-items-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ],
            ]
        );

        $this->add_responsive_control(
            'grid_gap',
            [
                'label' => 'Espaçamento da Grade',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => ['px' => ['min' => 0, 'max' => 100]],
                 'selectors' => [
                    '{{WRAPPER}} .upt-items-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'heading_header_card',
            [
                'label' => 'Cartão do Cabeçalho',
                'type' => \Elementor\Controls_Manager::HEADING,
                 'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'header_card_background',
                'label' => 'Plano de Fundo do Cabeçalho',
                'types' => [ 'classic', 'gradient' ],
                'selector' => '{{WRAPPER}} .upt-dashboard-header',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'header_card_box_shadow',
                'label' => 'Sombra do Cabeçalho',
                'selector' => '{{WRAPPER}} .upt-dashboard-header',
            ]
        );

        $this->add_control(
            'heading_welcome_text',
            [
                'label' => 'Texto de Boas-vindas',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'welcome_text_h2_color',
            [
                'label' => 'Cor (Principal)',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => ['{{WRAPPER}} .welcome-text h2' => 'color: {{VALUE}};']
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'welcome_text_h2_typography',
                'selector' => '{{WRAPPER}} .welcome-text h2'
            ]
        );

        $this->add_control(
            'welcome_text_p_color',
            [
                'label' => 'Cor (Subtexto)',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => ['{{WRAPPER}} .welcome-text p' => 'color: {{VALUE}};']
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'welcome_text_p_typography',
                'selector' => '{{WRAPPER}} .welcome-text p'
            ]
        );
        
        $this->add_control(
            'heading_logout_link',
            [
                'label' => 'Link de Sair',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'logout_link_typography',
                'selector' => '{{WRAPPER}} .logout-link',
            ]
        );

        $this->start_controls_tabs('tabs_logout_link_style');

        $this->start_controls_tab(
            'tab_logout_link_normal',
            [
                'label' => 'Normal',
            ]
        );
        $this->add_control(
            'logout_link_color',
            [
                'label' => 'Cor do Texto',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .logout-link' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_logout_link_hover',
            [
                'label' => 'Hover',
            ]
        );
        $this->add_control(
            'logout_link_color_hover',
            [
                'label' => 'Cor do Texto',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .logout-link:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_tab();
        $this->end_controls_tabs();



        // Help link style
        $this->add_control(
            'heading_help_link',
            [
                'label' => 'Link de Ajuda',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'help_link_typography',
                'selector' => '{{WRAPPER}} .upt-help-link',
            ]
        );

        $this->start_controls_tabs('tabs_help_link_style');

        $this->start_controls_tab(
            'tab_help_link_normal',
            [
                'label' => 'Normal',
            ]
        );
        $this->add_control(
            'help_link_color',
            [
                'label' => 'Cor do Texto',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .upt-help-link' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_help_link_hover',
            [
                'label' => 'Hover',
            ]
        );
        $this->add_control(
            'help_link_color_hover',
            [
                'label' => 'Cor do Texto',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .upt-help-link:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_tab();
        $this->end_controls_tabs();


               $this->add_control(
            'heading_items_container_card',
            [
                'label' => 'Cartão do Contêiner de Itens',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'items_container_background',
                'label' => 'Plano de Fundo do Contêiner',
                'types' => [ 'classic', 'gradient' ],
                'selector' => '{{WRAPPER}} .upt-items-container',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'items_container_box_shadow',
                'label' => 'Sombra do Contêiner',
                'selector' => '{{WRAPPER}} .upt-items-container',
            ]
        );

        $this->add_control(
            'heading_shared_styles',
            [
                'label' => 'Estilos Comuns (Ambos os Cartões)',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'header_padding',
            [
                'label' => 'Padding do Cabeçalho',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-dashboard-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'items_container_padding',
            [
                'label' => 'Padding do Contêiner de Itens',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-items-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'cards_border_radius',
            [
                'label' => 'Arredondamento dos Cantos',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [ 'px' => ['min' => 0, 'max' => 100] ],
                'selectors' => [
                    '{{WRAPPER}} .upt-dashboard-header, {{WRAPPER}} .upt-items-container' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'cards_border',
                'label' => 'Borda dos Cartões Principais',
                'selector' => '{{WRAPPER}} .upt-dashboard-header, {{WRAPPER}} .upt-items-container',
            ]
        );
        
        $this->add_responsive_control(
            'header_bottom_spacing',
            [
                'label' => 'Espaçamento Abaixo do Cabeçalho',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range' => ['px' => ['min' => 0, 'max' => 100]],
                'selectors' => [
                    '{{WRAPPER}} .upt-dashboard-header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        $this->start_controls_section(
            'section_style_tabs',
            [
                'label' => 'Abas de Navegação',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'tabs_layout',
            [
                'label' => 'Layout das Abas',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'horizontal_wrap',
                'options' => [
                    'horizontal_wrap'   => 'Horizontal (quebra em múltiplas linhas)',
                    'horizontal_scroll' => 'Horizontal com scroll',
                    'vertical_left'     => 'Vertical à esquerda (lista)',
                ],
            ]
        );

        $this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'tabs_typography',
				'selector' => '{{WRAPPER}} .upt-tabs-nav a',
			]
		);
        
        $this->add_responsive_control(
            'tabs_padding',
            [
                'label' => 'Padding',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-tabs-nav a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('tabs_style');

        $this->start_controls_tab('tab_normal', [ 'label' => 'Normal' ]);
        $this->add_control('tab_color', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-tabs-nav a' => 'color: {{VALUE}};' ]]);
        $this->add_control('tab_background', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-tabs-nav a' => 'background-color: {{VALUE}};' ]]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [ 'name' => 'tab_border', 'selector' => '{{WRAPPER}} .upt-tabs-nav a' ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_hover', [ 'label' => 'Hover' ]);
        $this->add_control('tab_color_hover', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-tabs-nav a:hover' => 'color: {{VALUE}};' ]]);
        $this->add_control('tab_background_hover', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-tabs-nav a:hover' => 'background-color: {{VALUE}};' ]]);
        $this->add_control('tab_border_hover', [ 'label' => 'Cor da Borda', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-tabs-nav a:hover' => 'border-color: {{VALUE}};' ]]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_active', [ 'label' => 'Ativo' ]);
        $this->add_control('tab_color_active', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-tabs-nav a.active' => 'color: {{VALUE}};' ]]);
        $this->add_control('tab_background_active', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-tabs-nav a.active' => 'background-color: {{VALUE}};' ]]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [ 'name' => 'tab_border_active', 'selector' => '{{WRAPPER}} .upt-tabs-nav a.active' ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();
        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_filters',
            [
                'label' => 'Filtros',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'filters_typography',
				'selector' => '{{WRAPPER}} .upt-filters input, {{WRAPPER}} .upt-filters select',
			]
		);
        $this->add_responsive_control(
            'filters_padding',
            [
                'label' => 'Padding Interno',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors' => [ '{{WRAPPER}} .upt-filters input, {{WRAPPER}} .upt-filters select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
            ]
        );
        $this->add_control(
            'filters_border_radius',
            [
                'label' => 'Arredondamento',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'selectors' => [ '{{WRAPPER}} .upt-filters input, {{WRAPPER}} .upt-filters select' => 'border-radius: {{SIZE}}{{UNIT}};' ],
            ]
        );
        $this->start_controls_tabs('tabs_filters_style');
        $this->start_controls_tab('tab_filters_normal', [ 'label' => 'Normal' ]);
        $this->add_control('filters_color', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-filters input, {{WRAPPER}} .upt-filters select' => 'color: {{VALUE}};' ]]);
        $this->add_control('filters_background', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-filters input, {{WRAPPER}} .upt-filters select' => 'background-color: {{VALUE}};' ]]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [ 'name' => 'filters_border', 'selector' => '{{WRAPPER}} .upt-filters input, {{WRAPPER}} .upt-filters select' ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [ 'name' => 'filters_box_shadow', 'selector' => '{{WRAPPER}} .upt-filters input, {{WRAPPER}} .upt-filters select' ]);
        $this->end_controls_tab();
        $this->start_controls_tab('tab_filters_focus', [ 'label' => 'Foco' ]);
        $this->add_control('filters_color_focus', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-filters input:focus, {{WRAPPER}} .upt-filters select:focus' => 'color: {{VALUE}};' ]]);
        $this->add_control('filters_background_focus', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-filters input:focus, {{WRAPPER}} .upt-filters select:focus' => 'background-color: {{VALUE}};' ]]);
        $this->add_control('filters_border_color_focus', [ 'label' => 'Cor da Borda', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-filters input:focus, {{WRAPPER}} .upt-filters select:focus' => 'border-color: {{VALUE}};' ]]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [ 'name' => 'filters_box_shadow_focus', 'selector' => '{{WRAPPER}} .upt-filters input:focus, {{WRAPPER}} .upt-filters select:focus' ]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_items_pagination',
            [
                'label' => 'Paginação de Itens',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'items_pagination_heading_wrapper',
            [
                'label' => 'Contêiner',
                'type' => \Elementor\Controls_Manager::HEADING,
            ]
        );

        $this->add_responsive_control(
            'items_pagination_alignment',
            [
                'label' => 'Alinhamento',
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [ 'title' => 'Esquerda', 'icon' => 'eicon-text-align-left' ],
                    'center' => [ 'title' => 'Centro', 'icon' => 'eicon-text-align-center' ],
                    'flex-end' => [ 'title' => 'Direita', 'icon' => 'eicon-text-align-right' ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .upt-pagination-wrapper' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'items_pagination_gap',
            [
                'label' => 'Espaçamento',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range' => [
                    'px' => [ 'min' => 0, 'max' => 60 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .upt-pagination-wrapper' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'items_pagination_padding',
            [
                'label' => 'Padding',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-pagination-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'items_pagination_margin_top',
            [
                'label' => 'Margem Superior',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range' => [
                    'px' => [ 'min' => -60, 'max' => 120 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .upt-pagination-wrapper' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'items_pagination_background',
                'selector' => '{{WRAPPER}} .upt-pagination-wrapper',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'items_pagination_border',
                'selector' => '{{WRAPPER}} .upt-pagination-wrapper',
            ]
        );

        $this->add_control(
            'items_pagination_border_radius',
            [
                'label' => 'Arredondamento',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-pagination-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'items_pagination_box_shadow',
                'selector' => '{{WRAPPER}} .upt-pagination-wrapper',
            ]
        );

        $this->add_control(
            'items_pagination_heading_numbers',
            [
                'label' => 'Números / Links',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'items_pagination_numbers_typography',
                'selector' => '{{WRAPPER}} .upt-pagination-wrapper .page-numbers',
            ]
        );

        $this->add_responsive_control(
            'items_pagination_numbers_padding',
            [
                'label' => 'Padding',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-pagination-wrapper .page-numbers' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'items_pagination_numbers_radius',
            [
                'label' => 'Arredondamento',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => 0, 'max' => 60 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .upt-pagination-wrapper .page-numbers' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'items_pagination_numbers_border',
                'selector' => '{{WRAPPER}} .upt-pagination-wrapper .page-numbers',
            ]
        );

        $this->start_controls_tabs('tabs_items_pagination_numbers_style');

        $this->start_controls_tab('tab_items_pagination_numbers_normal', [ 'label' => 'Normal' ]);
        $this->add_control('items_pagination_numbers_text_color', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-pagination-wrapper .page-numbers' => 'color: {{VALUE}};' ] ]);
        $this->add_control('items_pagination_numbers_bg_color', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-pagination-wrapper .page-numbers' => 'background-color: {{VALUE}};' ] ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_items_pagination_numbers_hover', [ 'label' => 'Hover' ]);
        $this->add_control('items_pagination_numbers_text_color_hover', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-pagination-wrapper .page-numbers:hover' => 'color: {{VALUE}};' ] ]);
        $this->add_control('items_pagination_numbers_bg_color_hover', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-pagination-wrapper .page-numbers:hover' => 'background-color: {{VALUE}};' ] ]);
        $this->add_control('items_pagination_numbers_border_color_hover', [ 'label' => 'Cor da Borda', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-pagination-wrapper .page-numbers:hover' => 'border-color: {{VALUE}};' ] ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_items_pagination_numbers_active', [ 'label' => 'Ativo' ]);
        $this->add_control('items_pagination_numbers_text_color_active', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-pagination-wrapper .page-numbers.current' => 'color: {{VALUE}};' ] ]);
        $this->add_control('items_pagination_numbers_bg_color_active', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-pagination-wrapper .page-numbers.current' => 'background-color: {{VALUE}};' ] ]);
        $this->add_control('items_pagination_numbers_border_color_active', [ 'label' => 'Cor da Borda', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-pagination-wrapper .page-numbers.current' => 'border-color: {{VALUE}};' ] ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'items_pagination_heading_load_more',
            [
                'label' => 'Botão "Carregar mais"',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'items_pagination_load_more_typography',
                'selector' => '{{WRAPPER}} .upt-pagination-wrapper .upt-load-more',
            ]
        );

        $this->add_responsive_control(
            'items_pagination_load_more_padding',
            [
                'label' => 'Padding',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-pagination-wrapper .upt-load-more' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'items_pagination_load_more_radius',
            [
                'label' => 'Arredondamento',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => 0, 'max' => 60 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .upt-pagination-wrapper .upt-load-more' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'items_pagination_load_more_border',
                'selector' => '{{WRAPPER}} .upt-pagination-wrapper .upt-load-more',
            ]
        );

        $this->start_controls_tabs('tabs_items_pagination_load_more_style');

        $this->start_controls_tab('tab_items_pagination_load_more_normal', [ 'label' => 'Normal' ]);
        $this->add_control('items_pagination_load_more_text_color', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-pagination-wrapper .upt-load-more' => 'color: {{VALUE}};' ] ]);
        $this->add_control('items_pagination_load_more_bg_color', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-pagination-wrapper .upt-load-more' => 'background-color: {{VALUE}};' ] ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_items_pagination_load_more_hover', [ 'label' => 'Hover' ]);
        $this->add_control('items_pagination_load_more_text_color_hover', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-pagination-wrapper .upt-load-more:hover' => 'color: {{VALUE}};' ] ]);
        $this->add_control('items_pagination_load_more_bg_color_hover', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-pagination-wrapper .upt-load-more:hover' => 'background-color: {{VALUE}};' ] ]);
        $this->add_control('items_pagination_load_more_border_color_hover', [ 'label' => 'Cor da Borda', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-pagination-wrapper .upt-load-more:hover' => 'border-color: {{VALUE}};' ] ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'tabs_media_heading',
            [
                'label' => 'Ícones/Imagens',
                'type'  => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'tabs_media_icon_size',
            [
                'label' => 'Tamanho do Ícone',
                'type'  => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range' => [
                    'px' => [ 'min' => 8, 'max' => 48 ],
                    'em' => [ 'min' => 0.5, 'max' => 3 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .upt-tabs-nav .upt-tab-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .upt-tabs-nav .upt-tab-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_media_icon_color',
            [
                'label' => 'Cor do Ícone',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .upt-tabs-nav .upt-tab-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .upt-tabs-nav .upt-tab-icon svg' => 'fill: currentColor; stroke: currentColor;',
                ],
            ]
        );

        $this->add_responsive_control(
            'tabs_media_image_size',
            [
                'label' => 'Tamanho da Imagem',
                'type'  => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range' => [
                    'px' => [ 'min' => 10, 'max' => 64 ],
                    'em' => [ 'min' => 0.5, 'max' => 4 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .upt-tabs-nav .upt-tab-image' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_gallery_pagination',
            [
                'label' => 'Paginação da Galeria',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'gallery_pagination_heading_wrapper',
            [
                'label' => 'Contêiner',
                'type' => \Elementor\Controls_Manager::HEADING,
            ]
        );

        $this->add_responsive_control(
            'gallery_pagination_alignment',
            [
                'label' => 'Alinhamento',
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [ 'title' => 'Esquerda', 'icon' => 'eicon-text-align-left' ],
                    'center' => [ 'title' => 'Centro', 'icon' => 'eicon-text-align-center' ],
                    'flex-end' => [ 'title' => 'Direita', 'icon' => 'eicon-text-align-right' ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-pagination' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'gallery_pagination_gap',
            [
                'label' => 'Espaçamento',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range' => [
                    'px' => [ 'min' => 0, 'max' => 60 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-pagination' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'gallery_pagination_padding',
            [
                'label' => 'Padding',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-pagination' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'gallery_pagination_margin_top',
            [
                'label' => 'Margem Superior',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem' ],
                'range' => [
                    'px' => [ 'min' => -60, 'max' => 120 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-pagination' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'gallery_pagination_background',
                'selector' => '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-pagination',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'gallery_pagination_border',
                'selector' => '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-pagination',
            ]
        );

        $this->add_control(
            'gallery_pagination_border_radius',
            [
                'label' => 'Arredondamento',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-pagination' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'gallery_pagination_box_shadow',
                'selector' => '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-pagination',
            ]
        );

        $this->add_control(
            'gallery_pagination_heading_note',
            [
                'label' => 'Texto (Página X de Y)',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'gallery_pagination_note_typography',
                'selector' => '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-pagination-note',
            ]
        );

        $this->add_control(
            'gallery_pagination_note_color',
            [
                'label' => 'Cor',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-pagination-note' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'gallery_pagination_heading_numbers',
            [
                'label' => 'Números',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'gallery_pagination_numbers_typography',
                'selector' => '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-number',
            ]
        );

        $this->add_responsive_control(
            'gallery_pagination_numbers_padding',
            [
                'label' => 'Padding',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-number' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'gallery_pagination_numbers_radius',
            [
                'label' => 'Arredondamento',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => 0, 'max' => 60 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-number' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'gallery_pagination_numbers_border',
                'selector' => '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-number',
            ]
        );

        $this->start_controls_tabs('tabs_gallery_pagination_numbers_style');

        $this->start_controls_tab('tab_gallery_pagination_numbers_normal', [ 'label' => 'Normal' ]);
        $this->add_control('gallery_pagination_numbers_text_color', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-number' => 'color: {{VALUE}};' ] ]);
        $this->add_control('gallery_pagination_numbers_bg_color', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-number' => 'background-color: {{VALUE}};' ] ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_gallery_pagination_numbers_hover', [ 'label' => 'Hover' ]);
        $this->add_control('gallery_pagination_numbers_text_color_hover', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-number.is-hover' => 'color: {{VALUE}};' ] ]);
        $this->add_control('gallery_pagination_numbers_bg_color_hover', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-number.is-hover' => 'background-color: {{VALUE}};' ] ]);
        $this->add_control('gallery_pagination_numbers_border_color_hover', [ 'label' => 'Cor da Borda', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-number.is-hover' => 'border-color: {{VALUE}};' ] ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_gallery_pagination_numbers_active', [ 'label' => 'Ativo' ]);
        $this->add_control('gallery_pagination_numbers_text_color_active', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-number.is-current' => 'color: {{VALUE}};' ] ]);
        $this->add_control('gallery_pagination_numbers_bg_color_active', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-number.is-current' => 'background-color: {{VALUE}};' ] ]);
        $this->add_control('gallery_pagination_numbers_border_color_active', [ 'label' => 'Cor da Borda', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-number.is-current' => 'border-color: {{VALUE}};' ] ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'gallery_pagination_heading_nav',
            [
                'label' => 'Botões Anterior / Próximo',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'gallery_pagination_nav_typography',
                'selector' => '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-prev, {{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-next',
            ]
        );

        $this->add_responsive_control(
            'gallery_pagination_nav_padding',
            [
                'label' => 'Padding',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-prev, {{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-next' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'gallery_pagination_nav_radius',
            [
                'label' => 'Arredondamento',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => 0, 'max' => 60 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-prev, {{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-next' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'gallery_pagination_nav_border',
                'selector' => '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-prev, {{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-next',
            ]
        );

        $this->start_controls_tabs('tabs_gallery_pagination_nav_style');

        $this->start_controls_tab('tab_gallery_pagination_nav_normal', [ 'label' => 'Normal' ]);
        $this->add_control('gallery_pagination_nav_text_color', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-prev, {{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-next' => 'color: {{VALUE}};' ] ]);
        $this->add_control('gallery_pagination_nav_bg_color', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-prev, {{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-next' => 'background-color: {{VALUE}};' ] ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_gallery_pagination_nav_hover', [ 'label' => 'Hover' ]);
		$this->add_control('gallery_pagination_nav_text_color_hover', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-prev.is-hover, {{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-next.is-hover' => 'color: {{VALUE}};' ] ]);
		$this->add_control('gallery_pagination_nav_bg_color_hover', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-prev.is-hover, {{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-next.is-hover' => 'background-color: {{VALUE}};' ] ]);
		$this->add_control('gallery_pagination_nav_border_color_hover', [ 'label' => 'Cor da Borda', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-prev.is-hover, {{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-next.is-hover' => 'border-color: {{VALUE}};' ] ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'gallery_pagination_heading_dots',
            [
                'label' => 'Reticências',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'gallery_pagination_dots_color',
            [
                'label' => 'Cor',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-page-dots' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'gallery_pagination_heading_load_more',
            [
                'label' => 'Botão "Carregar mais"',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'gallery_pagination_load_more_typography',
                'selector' => '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-load-more-btn',
            ]
        );

        $this->add_responsive_control(
            'gallery_pagination_load_more_padding',
            [
                'label' => 'Padding',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-load-more-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'gallery_pagination_load_more_radius',
            [
                'label' => 'Arredondamento',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => 0, 'max' => 60 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-load-more-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'gallery_pagination_load_more_border',
                'selector' => '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-load-more-btn',
            ]
        );

        $this->start_controls_tabs('tabs_gallery_pagination_load_more_style');

        $this->start_controls_tab('tab_gallery_pagination_load_more_normal', [ 'label' => 'Normal' ]);
        $this->add_control('gallery_pagination_load_more_text_color', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-load-more-btn:not(.is-hover)' => 'color: {{VALUE}};' ] ]);
        $this->add_control('gallery_pagination_load_more_bg_color', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-load-more-btn:not(.is-hover)' => 'background-color: {{VALUE}};' ] ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_gallery_pagination_load_more_hover', [ 'label' => 'Hover' ]);
        $this->add_control('gallery_pagination_load_more_text_color_hover', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-load-more-btn.is-hover' => 'color: {{VALUE}};' ] ]);
        $this->add_control('gallery_pagination_load_more_bg_color_hover', [ 'label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-load-more-btn.is-hover' => 'background-color: {{VALUE}};' ] ]);
        $this->add_control('gallery_pagination_load_more_border_color_hover', [ 'label' => 'Cor da Borda', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-gallery-pagination-style-probe .upt-gallery-load-more-btn.is-hover' => 'border-color: {{VALUE}};' ] ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // Estilo do Card de Item (Preview)
        $this->start_controls_section(
            'section_style_item_card_preview',
            [
                'label' => 'Card de Item (Preview)',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [ 'preview_component' => 'item_card' ],
            ]
        );

        $this->add_control(
            'item_card_preview_bg',
            [
                'label' => 'Cor de Fundo',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .upt-item-card-preview' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'item_card_preview_border',
            [
                'label' => 'Cor da Borda',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#e2e8f0',
                'selectors' => [
                    '{{WRAPPER}} .upt-item-card-preview' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'item_card_preview_radius',
            [
                'label' => 'Arredondamento',
                'type'  => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [ 'px' => [ 'min' => 0, 'max' => 32 ] ],
                'default' => [ 'size' => 10, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-item-card-preview' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_card_preview_padding',
            [
                'label' => 'Padding Interno',
                'type'  => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [ 'px' => [ 'min' => 8, 'max' => 40 ] ],
                'default' => [ 'size' => 16, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-item-card-preview' => 'padding: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_card_preview_title_color',
            [
                'label' => 'Cor do Título',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#0f172a',
                'selectors' => [
                    '{{WRAPPER}} .upt-item-card-preview .card-title, {{WRAPPER}} .upt-item-card-preview span[style*="font-weight:700"]' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'item_card_preview_meta_color',
            [
                'label' => 'Cor dos Metadados',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#475569',
                'selectors' => [
                    '{{WRAPPER}} .upt-item-card-preview .card-meta, {{WRAPPER}} .upt-item-card-preview .card-date, {{WRAPPER}} .upt-item-card-preview p, {{WRAPPER}} .upt-item-card-preview span[style*="font-size:14px"]' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'item_card_preview_tag_bg',
            [
                'label' => 'Fundo das Tags',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#eef2ff',
                'selectors' => [
                    '{{WRAPPER}} .upt-item-card-preview .tag' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'item_card_preview_tag_color',
            [
                'label' => 'Cor das Tags',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#4338ca',
                'selectors' => [
                    '{{WRAPPER}} .upt-item-card-preview .tag' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'item_card_preview_btn_bg',
            [
                'label' => 'Fundo do Botão',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#1a73e8',
                'selectors' => [
                    '{{WRAPPER}} .upt-item-card-preview button' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'item_card_preview_btn_color',
            [
                'label' => 'Cor do Botão',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .upt-item-card-preview button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Seção de Estilo dos Cards dos Itens
        $this->start_controls_section(
            'section_style_item_cards',
            [
                'label' => 'Cards dos Itens (Padrão)',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'item_card_template_id' => '0',
                ],
            ]
        );

        $this->add_control(
			'item_card_transition',
			[
				'label' => 'Duração da Transição (Hover)',
				'type' => \Elementor\Controls_Manager::SLIDER,
				'range' => [
					'ms' => [
						'min' => 0,
						'max' => 3000,
						'step' => 50,
					],
				],
                'default' => [
					'unit' => 'ms',
					'size' => 200,
				],
				'selectors' => [
					'{{WRAPPER}} .upt-item-card' => 'transition: background-color {{SIZE}}ms, border-color {{SIZE}}ms, box-shadow {{SIZE}}ms;',
				],
			]
		);

        $this->start_controls_tabs('item_card_style_tabs');

        $this->start_controls_tab('item_card_normal_tab', ['label' => 'Normal']);
        $this->add_control('item_card_bg_color', ['label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-item-card' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'item_card_border', 'selector' => '{{WRAPPER}} .upt-item-card']);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'item_card_box_shadow', 'selector' => '{{WRAPPER}} .upt-item-card']);
        $this->end_controls_tab();

        $this->start_controls_tab('item_card_hover_tab', ['label' => 'Hover']);
        $this->add_control('item_card_bg_color_hover', ['label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-item-card:hover' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'item_card_border_hover', 'selector' => '{{WRAPPER}} .upt-item-card:hover']);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'item_card_box_shadow_hover', 'selector' => '{{WRAPPER}} .upt-item-card:hover']);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('item_card_padding', ['label' => 'Padding Interno', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%', 'em'], 'selectors' => ['{{WRAPPER}} .upt-item-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'], 'separator' => 'before']);
        $this->add_responsive_control('item_card_border_radius', ['label' => 'Arredondamento', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'selectors' => ['{{WRAPPER}} .upt-item-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        
        $this->end_controls_section();

        // Seção de Estilo do Conteúdo dos Cards
        $this->start_controls_section(
            'section_style_item_card_content',
            [
                'label' => 'Conteúdo dos Cards (Padrão)',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'item_card_template_id' => '0',
                ],
            ]
        );
        $this->add_control('heading_item_title', ['label' => 'Título do Item', 'type' => \Elementor\Controls_Manager::HEADING]);
        $this->add_control('item_title_color', ['label' => 'Cor do Título', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-item-card .card-title' => 'color: {{VALUE}};']]);
        
        $this->add_control(
            'title_line_clamp',
            [
                'label' => 'Limitar Linhas do Título',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'step' => 1,
                'description' => 'Especifique o número máximo de linhas para o título. Deixe em branco para não limitar.',
                'selectors' => [
                    '{{WRAPPER}} .upt-item-card .card-title' => 'display: -webkit-box; -webkit-line-clamp: {{VALUE}}; -webkit-box-orient: vertical; overflow: hidden;',
                ],
                'condition' => [
                    'item_card_template_id' => '0',
                ],
            ]
        );

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'item_title_typography', 'selector' => '{{WRAPPER}} .upt-item-card .card-title', 'separator' => 'before']);
        
        $this->add_control('heading_item_meta', ['label' => 'Status do Item', 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('item_meta_color', ['label' => 'Cor do Status', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-item-card .card-meta' => 'color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'item_meta_typography', 'selector' => '{{WRAPPER}} .upt-item-card .card-meta']);
        $this->end_controls_section();


        $this->start_controls_section(
            'style_section_buttons',
            [
                'label' => 'Botão Principal (Adicionar Item)',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'label' => 'Tipografia do Botão',
                'selector' => '{{WRAPPER}} .upt-preset-hostinger .button',
            ]
        );
        
        $this->add_responsive_control(
            'button_padding',
            [
                'label' => 'Padding do Botão',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-preset-hostinger .button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label' => 'Arredondamento do Botão',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-preset-hostinger .button, {{WRAPPER}} .upt-preset-hostinger input[type="submit"]' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs( 'button_style_tabs' );

        $this->start_controls_tab( 'button_normal_tab', [ 'label' => 'Normal', ] );
        $this->add_control( 'button_text_color', [ 'label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-preset-hostinger .button' => 'color: {{VALUE}};', ], ] );
        $this->add_group_control( \Elementor\Group_Control_Border::get_type(), [ 'name' => 'button_border', 'selector' => '{{WRAPPER}} .upt-preset-hostinger .button', ] );
        $this->end_controls_tab();

        $this->start_controls_tab( 'button_hover_tab', [ 'label' => 'Hover', ] );
        $this->add_control( 'button_text_color_hover', [ 'label' => 'Cor do Texto (Hover)', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => [ '{{WRAPPER}} .upt-preset-hostinger .button:hover' => 'color: {{VALUE}};', ], ] );
        $this->add_group_control( \Elementor\Group_Control_Border::get_type(), [ 'name' => 'button_border_hover', 'selector' => '{{WRAPPER}} .upt-preset-hostinger .button:hover', ] );
        $this->end_controls_tab();

        $this->end_controls_tabs();
        $this->end_controls_section();
        // --- Estilo: Badge de Alerta ---
        $this->start_controls_section(
            'section_alert_badge_style',
            [
                'label' => 'Badge de Alerta',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [ 'alert_badge_enabled' => 'yes' ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'alert_badge_typography',
                'label'    => 'Tipografia',
                'selector' => '{{WRAPPER}} .upt-alert-badge p',
            ]
        );

        $this->add_control(
            'alert_badge_text_color',
            [
                'label' => 'Cor do Texto',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .upt-alert-badge p' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'alert_badge_bg',
            [
                'label' => 'Background',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .upt-alert-badge' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'alert_badge_border',
                'label'    => 'Borda',
                'selector' => '{{WRAPPER}} .upt-alert-badge',
            ]
        );

        $this->add_responsive_control(
            'alert_badge_radius',
            [
                'label' => 'Arredondamento',
                'type'  => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-alert-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'alert_badge_padding',
            [
                'label' => 'Padding',
                'type'  => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', 'rem' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-alert-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'alert_badge_shadow',
                'label'    => 'Sombra',
                'selector' => '{{WRAPPER}} .upt-alert-badge',
            ]
        );

        $this->add_control(
            'alert_button_heading',
            [
                'label' => 'Botão Fechar',
                'type'  => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'alert_button_bg',
            [
                'label' => 'Background',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .upt-alert-badge button' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'alert_button_bg_hover',
            [
                'label' => 'Background (Hover)',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .upt-alert-badge button:hover' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'alert_button_icon_color',
            [
                'label' => 'Cor do Ícone',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .upt-alert-badge button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'alert_button_icon_color_hover',
            [
                'label' => 'Cor do Ícone (Hover)',
                'type'  => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .upt-alert-badge button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();



    }
    protected function render() {
        $settings = $this->get_settings_for_display();
        if (isset($settings['dashboard_preset']) && $settings['dashboard_preset'] === 'saas') {
            $settings['dashboard_preset'] = 'hostinger';
        }
        $preview_component = isset( $settings['preview_component'] ) ? $settings['preview_component'] : 'dashboard';

        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() && $preview_component !== 'dashboard' ) {
            $this->render_preview_component( $preview_component, $settings );
            return;
        }

        $template_id = isset($settings['item_card_template_id']) ? absint($settings['item_card_template_id']) : 0;
        if ( $template_id > 0 && class_exists('\Elementor\Core\Files\CSS\Post') ) {
            $css_file = new \Elementor\Core\Files\CSS\Post( $template_id );
            $css_file->enqueue();
        }

        if ( 'yes' === $settings['hide_admin_bar'] && ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            add_filter( 'show_admin_bar', '__return_false' );
            echo '<style>html { margin-top: 0 !important; }</style>';
        }
        
        wp_enqueue_media();

        $dashboard_preset = isset( $settings['dashboard_preset'] ) ? $settings['dashboard_preset'] : 'saas';
        $use_saas = ( $dashboard_preset === 'saas' );

        $data_attrs = ' data-upt-preset="' . esc_attr( $dashboard_preset ) . '"'
            . ' data-upt-alert-enabled="' . esc_attr( ( 'yes' === ( $settings['alert_badge_enabled'] ?? 'yes' ) ) ? '1' : '0' ) . '"'
            . ' data-upt-alert-create="' . esc_attr( ( 'yes' === ( $settings['alert_on_create'] ?? 'yes' ) ) ? '1' : '0' ) . '"'
            . ' data-upt-alert-edit="' . esc_attr( ( 'yes' === ( $settings['alert_on_edit'] ?? 'yes' ) ) ? '1' : '0' ) . '"'
            . ' data-upt-alert-delete="' . esc_attr( ( 'yes' === ( $settings['alert_on_delete'] ?? 'yes' ) ) ? '1' : '0' ) . '"'
            . ' data-upt-alert-draft="' . esc_attr( ( 'yes' === ( $settings['alert_on_draft'] ?? 'yes' ) ) ? '1' : '0' ) . '"'
            . ' data-upt-alert-login="' . esc_attr( ( 'yes' === ( $settings['alert_on_login'] ?? 'yes' ) ) ? '1' : '0' ) . '"'
            . ' data-upt-alert-schema-qty="' . esc_attr( ( 'yes' === ( $settings['alert_on_schema_qty'] ?? 'yes' ) ) ? '1' : '0' ) . '"'
            . ' data-upt-alert-media-deleted="' . esc_attr( ( 'yes' === ( $settings['alert_on_media_deleted'] ?? 'yes' ) ) ? '1' : '0' ) . '"'
            . ' data-upt-alert-media-uploaded="' . esc_attr( ( 'yes' === ( $settings['alert_on_media_uploaded'] ?? 'yes' ) ) ? '1' : '0' ) . '"'
            . ' data-upt-alert-media-moved="' . esc_attr( ( 'yes' === ( $settings['alert_on_media_moved'] ?? 'yes' ) ) ? '1' : '0' ) . '"'
            . ' data-upt-alert-category-created="' . esc_attr( ( 'yes' === ( $settings['alert_on_category_created'] ?? 'yes' ) ) ? '1' : '0' ) . '"'
            . ' data-upt-alert-category-renamed="' . esc_attr( ( 'yes' === ( $settings['alert_on_category_renamed'] ?? 'yes' ) ) ? '1' : '0' ) . '"'
            . ' data-upt-alert-category-deleted="' . esc_attr( ( 'yes' === ( $settings['alert_on_category_deleted'] ?? 'yes' ) ) ? '1' : '0' ) . '"'
            . ' data-upt-alert-text="' . esc_attr( $settings['alert_badge_default_text'] ?? 'Você tem novas notificações do upt' ) . '"'
            . ' data-upt-alert-duration="' . esc_attr( isset($settings['alert_badge_duration']) ? (float) $settings['alert_badge_duration'] : 4 ) . '"';

        if ( $use_saas ) {
            echo '<div class="upt-dashboard-wrapper upt-dashboard-wrapper--saas"' . $data_attrs . '>';
                echo '<div class="upt-alert-host" aria-live="polite" aria-atomic="true"></div>';
                if ( ! is_user_logged_in() ) {
                    set_query_var('widget_settings', $settings);
                    include UPT_PLUGIN_DIR . 'templates/front-form-login.php';
                } else {
                    set_query_var('widget_settings', $settings);
                    include UPT_PLUGIN_DIR . 'templates/dashboard-main.php';
                }
            echo '</div>';
        } else {
            echo '<div class="upt-dashboard-wrapper"' . $data_attrs . '>';
                echo '<div class="upt-alert-host" aria-live="polite" aria-atomic="true"></div>';
                echo '<div class="upt-preset-hostinger">';
                    if ( ! is_user_logged_in() ) {
                        set_query_var('widget_settings', $settings);
                        include UPT_PLUGIN_DIR . 'templates/front-form-login.php';
                    } else {
                        set_query_var('widget_settings', $settings);
                        include UPT_PLUGIN_DIR . 'templates/dashboard-main.php';
                    }
                echo '</div>';
            echo '</div>';
        }
    }
}
