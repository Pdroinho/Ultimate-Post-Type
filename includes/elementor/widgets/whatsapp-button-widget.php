<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_WhatsApp_Button_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'upt_whatsapp_button'; }
    public function get_title() { return 'Botão WhatsApp upt'; }
    public function get_icon() { return 'eicon-button'; }
    public function get_categories() { return [ 'upt' ]; }

    private function get_all_custom_fields_options($for_inserter = false) {
        $options = [];
        if (!$for_inserter) {
            $options[''] = '— Selecione —';
        }
        $options['item_title'] = 'Título do Item';
        $options['item_url'] = 'URL do Item';
        
        $schemas = UPT_Schema_Store::get_schemas();
        if (empty($schemas)) return $options;

        foreach ($schemas as $slug => $data) {
            if (isset($data['fields']) && is_array($data['fields'])) {
                $term = get_term_by('slug', $slug, 'catalog_schema');
                $name = $term ? $term->name : $slug;
                foreach ($data['fields'] as $field) {
                    $key = $for_inserter ? '[' . $field['id'] . ']' : $field['id'];
                    $options[$key] = $name . ' - ' . $field['label'];
                }
            }
        }
        return $options;
    }

    protected function _register_controls() {
        $this->start_controls_section('section_content', ['label' => 'Conteúdo do Botão']);

        $this->add_control('button_text', ['label' => 'Texto do Botão', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Contactar via WhatsApp', 'dynamic' => ['active' => true]]);
        $this->add_control('whatsapp_number', ['label' => 'Número do WhatsApp (com DDI)', 'type' => \Elementor\Controls_Manager::TEXT, 'placeholder' => '5579999999999', 'dynamic' => ['active' => true]]);
        $this->add_control('whatsapp_message', ['label' => 'Mensagem', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Olá, tenho interesse no item: [item_title]', 'description' => 'Use os placeholders abaixo para montar uma mensagem dinâmica.', 'rows' => 4, 'dynamic' => ['active' => true]]);
        
        $this->add_control( 'placeholder_inserter', [
            'label' => 'Inserir Placeholder', 'type' => \Elementor\Controls_Manager::SELECT, 'options' => $this->get_all_custom_fields_options(true), 'default' => '',
            'description' => 'Selecione um campo para inserir o seu placeholder na mensagem.'
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_style_button', ['label' => 'Estilo do Botão', 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('align', ['label' => 'Alinhamento', 'type' => \Elementor\Controls_Manager::CHOOSE, 'options' => ['left' => ['title' => 'Esquerda', 'icon' => 'eicon-text-align-left'], 'center' => ['title' => 'Centro', 'icon' => 'eicon-text-align-center'], 'right' => ['title' => 'Direita', 'icon' => 'eicon-text-align-right'], 'justify' => ['title' => 'Justificado', 'icon' => 'eicon-align-stretch-h']], 'prefix_class' => 'elementor-align%s-', 'default' => '']);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'typography', 'selector' => '{{WRAPPER}} .elementor-button']);
        $this->add_control('button_transition', ['label' => 'Duração da Transição', 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['ms' => ['min' => 0, 'max' => 3000, 'step' => 100]], 'selectors' => ['{{WRAPPER}} .elementor-button' => 'transition-duration: {{SIZE}}ms;']]);
        $this->start_controls_tabs('button_tabs');
        $this->start_controls_tab('button_normal', ['label' => 'Normal']);
        $this->add_control('button_text_color', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .elementor-button' => 'color: {{VALUE}};']]);
        $this->add_control('button_background_color', ['label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .elementor-button' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'border', 'selector' => '{{WRAPPER}} .elementor-button']);
        $this->add_control('border_radius', ['label' => 'Arredondamento', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'selectors' => ['{{WRAPPER}} .elementor-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'button_box_shadow', 'selector' => '{{WRAPPER}} .elementor-button']);
        $this->end_controls_tab();
        $this->start_controls_tab('button_hover', ['label' => 'Hover']);
        $this->add_control('hover_color', ['label' => 'Cor do Texto', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .elementor-button:hover' => 'color: {{VALUE}};']]);
        $this->add_control('button_background_hover_color', ['label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .elementor-button:hover' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'border_hover', 'selector' => '{{WRAPPER}} .elementor-button:hover']);
        $this->add_control('border_radius_hover', ['label' => 'Arredondamento', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'selectors' => ['{{WRAPPER}} .elementor-button:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('hover_animation', ['label' => 'Animação no Hover', 'type' => \Elementor\Controls_Manager::HOVER_ANIMATION]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_responsive_control('text_padding', ['label' => 'Padding', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em', '%'], 'selectors' => ['{{WRAPPER}} .elementor-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'], 'separator' => 'before']);
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        if (empty($settings['whatsapp_number'])) return;

        $this->add_render_attribute('wrapper', 'class', 'elementor-button-wrapper');
        $this->add_render_attribute('button', 'class', 'elementor-button');
        $this->add_render_attribute('button', 'class', 'elementor-size-sm');

        $raw_message = $settings['whatsapp_message'];
        
        $final_message = preg_replace_callback('/\[(.*?)\]/', function($matches) {
            $field_key = $matches[1];
            $post_id = get_the_ID();
            if ($field_key === 'item_title') return get_the_title($post_id);
            if ($field_key === 'item_url') return get_permalink($post_id);
            $value = get_post_meta($post_id, $field_key, true);
            return $value ? $value : '';
        }, $raw_message);
        
        $href = 'https://wa.me/' . preg_replace('/\D/', '', $settings['whatsapp_number']) . '?text=' . rawurlencode($final_message);
        $this->add_render_attribute('button', 'href', esc_url($href));
        $this->add_render_attribute('button', 'target', '_blank');

        if (!empty($settings['hover_animation'])) {
            $this->add_render_attribute('button', 'class', 'elementor-animation-' . $settings['hover_animation']);
        }
        ?>
        <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
            <a <?php echo $this->get_render_attribute_string('button'); ?>>
                <span class="elementor-button-content-wrapper">
                    <span class="elementor-button-text"><?php echo $settings['button_text']; ?></span>
                </span>
            </a>
        </div>
        <?php
    }
}
