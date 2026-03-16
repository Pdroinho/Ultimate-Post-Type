<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Text_Editor_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'upt_text_editor'; }
    public function get_title() { return 'Texto Dinâmico upt'; }
    public function get_icon() { return 'eicon-text-area'; }
    public function get_categories() { return [ 'upt' ]; }

    private function get_upt_text_fields() {
        $options = ['' => '— Selecione o Campo —'];
        if ( ! class_exists( 'UPT_Schema_Store' ) ) return $options;
        
        $schemas = UPT_Schema_Store::get_schemas();
        if ( empty( $schemas ) ) return $options;

        foreach ( $schemas as $slug => $data ) {
            if ( isset( $data['fields'] ) && is_array( $data['fields'] ) ) {
                $term = get_term_by( 'slug', $slug, 'catalog_schema' );
                $name = $term ? $term->name : $slug;
                foreach ( $data['fields'] as $field ) {
                    if ( in_array( $field['type'], [ 'wysiwyg', 'textarea', 'text', 'core_content', 'core_title', 'blog_post' ] ) ) {
                        $options[ $field['id'] ] = $name . ' - ' . $field['label'];
                    }
                }
            }
        }
        return $options;
    }

    protected function _register_controls() {
        $this->start_controls_section( 'section_content', [ 'label' => 'Conteúdo' ] );

        $this->add_control(
            'field_key',
            [
                'label' => 'Campo do upt',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_upt_text_fields(),
            ]
        );

        $this->add_control(
            'render_mode',
            [
                'label' => 'Modo de Exibição',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'plain',
                'options' => [
                    'full' => 'HTML Completo (Original)',
                    'plain' => 'Texto Puro (Remove HTML/Estilos)',
                    'excerpt' => 'Resumo por Caracteres (Corte PHP)',
                ],
                'description' => 'Define como o conteúdo é limpo antes de exibir.',
            ]
        );

        $this->add_control(
            'excerpt_length',
            [
                'label' => 'Limite de Caracteres',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 10,
                'max' => 500,
                'default' => 100,
                'condition' => [ 'render_mode' => 'excerpt' ],
            ]
        );

        $this->add_control(
            'excerpt_suffix',
            [
                'label' => 'Sufixo',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '...',
                'condition' => [ 'render_mode' => 'excerpt' ],
            ]
        );

        $this->add_control(
            'keep_line_breaks',
            [
                'label' => 'Manter Quebras de Linha?',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'condition' => [ 'render_mode' => 'plain' ],
            ]
        );

        // LIMITADOR DE LINHAS VISUAL (CSS)
        $this->add_control(
            'enable_line_clamp',
            [
                'label' => 'Limitar por Linhas (CSS)',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Sim',
                'label_off' => 'Não',
                'return_value' => 'yes',
                'default' => 'no',
                'separator' => 'before',
                'description' => 'Corta o texto visualmente após X linhas e adiciona "..." no final.',
            ]
        );

        $this->add_responsive_control(
            'line_clamp_count',
            [
                'label' => 'Número de Linhas',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 20,
                'default' => 3,
                'condition' => [ 'enable_line_clamp' => 'yes' ],
                'selectors' => [
                    '{{WRAPPER}} .upt-text-content' => 'display: -webkit-box; -webkit-line-clamp: {{VALUE}}; -webkit-box-orient: vertical; overflow: hidden;',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section( 'section_style', [ 'label' => 'Estilo', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );

        $this->add_responsive_control(
            'align',
            [
                'label' => 'Alinhamento',
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [ 'title' => 'Esquerda', 'icon' => 'eicon-text-align-left' ],
                    'center' => [ 'title' => 'Centro', 'icon' => 'eicon-text-align-center' ],
                    'right' => [ 'title' => 'Direita', 'icon' => 'eicon-text-align-right' ],
                    'justify' => [ 'title' => 'Justificado', 'icon' => 'eicon-text-align-justify' ],
                ],
                'selectors' => [ '{{WRAPPER}} .upt-text-content' => 'text-align: {{VALUE}};' ],
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => 'Cor do Texto',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [ '{{WRAPPER}} .upt-text-content' => 'color: {{VALUE}};' ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'typography',
                'selector' => '{{WRAPPER}} .upt-text-content',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $field_key = $settings['field_key'];
        $post_id = get_the_ID();

        if ( empty( $field_key ) || ! $post_id ) return;

        $content = '';
        
        if ( $field_key === 'core_content' ) {
            $content = get_the_content( null, false, $post_id );
        } elseif ( $field_key === 'core_title' ) {
            $content = get_the_title( $post_id );
        } else {
            $schema_slug = explode('_', $field_key)[0]; 
            $schemas = UPT_Schema_Store::get_schemas();
            $field_type = '';
            
            if (isset($schemas[$schema_slug]['fields'])) {
                foreach($schemas[$schema_slug]['fields'] as $f) {
                    if($f['id'] === $field_key) {
                        $field_type = $f['type'];
                        break;
                    }
                }
            }

            if ($field_type === 'blog_post') {
                // CORREÇÃO: Busca o resumo nativo do WordPress.
                // Se não existir resumo manual, usa o conteúdo como fallback.
                $post = get_post($post_id);
                if ($post) {
                    $content = $post->post_excerpt;
                    if ( empty($content) ) {
                        $content = $post->post_content;
                    }
                }
            } else {
                // Campos normais (meta fields)
                $content = get_post_meta( $post_id, $field_key, true );
            }
        }

        if ( empty( $content ) ) return;

        $final_text = '';

        switch ( $settings['render_mode'] ) {
            case 'full':
                // Renderiza HTML
                $final_text = do_shortcode( wpautop( $content ) );
                break;

            case 'plain':
                // Remove tags HTML
                $text = wp_strip_all_tags( $content );
                if ( $settings['keep_line_breaks'] === 'yes' ) {
                    $final_text = nl2br( esc_html( $text ) );
                } else {
                    $final_text = esc_html( $text );
                }
                break;

            case 'excerpt':
                // Corta caracteres manualmente
                $text = wp_strip_all_tags( $content );
                $limit = absint( $settings['excerpt_length'] );
                if ( mb_strlen( $text ) > $limit ) {
                    $final_text = mb_substr( $text, 0, $limit ) . $settings['excerpt_suffix'];
                } else {
                    $final_text = $text;
                }
                $final_text = esc_html( $final_text );
                break;
        }

        ?>
        <div class="upt-text-content">
            <?php echo $final_text; ?>
        </div>
        <?php
    }
}
