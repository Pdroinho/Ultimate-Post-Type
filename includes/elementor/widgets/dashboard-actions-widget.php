<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Dashboard_Actions_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'upt_dashboard_actions'; }
    public function get_title() { return 'Ações do Item (Painel)'; }
    public function get_icon() { return 'eicon-handle'; }
    public function get_categories() { return [ 'upt' ]; }

    protected function _register_controls() {
        // --- Seção de Conteúdo para Botão de Edição ---
        $this->start_controls_section('section_edit_button', ['label' => 'Botão de Editar']);
        $this->add_control('show_edit', ['label' => 'Exibir Botão de Editar', 'type' => \Elementor\Controls_Manager::SWITCHER, 'label_on' => 'Sim', 'label_off' => 'Não', 'return_value' => 'yes', 'default' => 'yes']);
        $this->add_control('edit_text', ['label' => 'Texto', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '', 'placeholder' => 'Editar', 'condition' => ['show_edit' => 'yes']]);
        $this->add_control('edit_icon', ['label' => 'Ícone', 'type' => \Elementor\Controls_Manager::ICONS, 'default' => ['value' => 'lucide-pen', 'library' => 'lucide'], 'condition' => ['show_edit' => 'yes']]);
        $this->add_control('edit_icon_position', ['label' => 'Posição do Ícone', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'before', 'options' => ['before' => 'Antes', 'after' => 'Depois'], 'condition' => ['show_edit' => 'yes', 'edit_icon[value]!' => '', 'edit_text!' => '']]);
        $this->add_responsive_control('edit_icon_spacing', ['label' => 'Espaçamento do Ícone', 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 50]], 'selectors' => ['{{WRAPPER}} .upt-action-edit.icon-before' => 'gap: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .upt-action-edit.icon-after' => 'gap: {{SIZE}}{{UNIT}};'], 'condition' => ['show_edit' => 'yes', 'edit_icon[value]!' => '', 'edit_text!' => '']]);
        $this->end_controls_section();

        // --- Seção de Conteúdo para Botão de Exclusão ---
        $this->start_controls_section('section_delete_button', ['label' => 'Botão de Excluir']);
        $this->add_control('show_delete', ['label' => 'Exibir Botão de Excluir', 'type' => \Elementor\Controls_Manager::SWITCHER, 'label_on' => 'Sim', 'label_off' => 'Não', 'return_value' => 'yes', 'default' => 'yes']);
        $this->add_control('delete_text', ['label' => 'Texto', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '', 'placeholder' => 'Excluir', 'condition' => ['show_delete' => 'yes']]);
        $this->add_control('delete_icon', ['label' => 'Ícone', 'type' => \Elementor\Controls_Manager::ICONS, 'default' => ['value' => 'lucide-trash-2', 'library' => 'lucide'], 'condition' => ['show_delete' => 'yes']]);
        $this->add_control('delete_icon_position', ['label' => 'Posição do Ícone', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'before', 'options' => ['before' => 'Antes', 'after' => 'Depois'], 'condition' => ['show_delete' => 'yes', 'delete_icon[value]!' => '', 'delete_text!' => '']]);
        $this->add_responsive_control('delete_icon_spacing', ['label' => 'Espaçamento do Ícone', 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 50]], 'selectors' => ['{{WRAPPER}} .upt-action-delete.icon-before' => 'gap: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .upt-action-delete.icon-after' => 'gap: {{SIZE}}{{UNIT}};'], 'condition' => ['show_delete' => 'yes', 'delete_icon[value]!' => '', 'delete_text!' => '']]);
        $this->end_controls_section();

        // --- Seção de Estilo Global ---
        $this->start_controls_section('section_layout_style', ['label' => 'Layout', 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('align', ['label' => 'Alinhamento', 'type' => \Elementor\Controls_Manager::CHOOSE, 'options' => ['flex-start' => ['title' => 'Esquerda', 'icon' => 'eicon-text-align-left'], 'center' => ['title' => 'Centro', 'icon' => 'eicon-text-align-center'], 'flex-end' => ['title' => 'Direita', 'icon' => 'eicon-text-align-right'], 'space-between' => ['title' => 'Justificado', 'icon' => 'eicon-align-stretch-h']], 'selectors' => ['{{WRAPPER}} .card-actions' => 'justify-content: {{VALUE}};']]);
        $this->add_responsive_control('buttons_gap', ['label' => 'Espaçamento entre Botões', 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => ['px'], 'range' => ['px' => ['min' => 0, 'max' => 100]], 'selectors' => ['{{WRAPPER}} .card-actions' => 'gap: {{SIZE}}{{UNIT}};']]);
        $this->end_controls_section();

        // --- Seção de Estilo dos Botões ---
        $this->start_controls_section('section_buttons_style', ['label' => 'Estilo dos Botões', 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'button_typography', 'selector' => '{{WRAPPER}} .card-actions a']);
        $this->add_responsive_control('icon_size', ['label' => 'Tamanho do Ícone', 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => ['px', 'em'], 'range' => ['px' => ['min' => 6, 'max' => 50]], 'selectors' => ['{{WRAPPER}} .card-actions a i' => 'font-size: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .card-actions a svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};']]);
        $this->add_responsive_control('button_padding', ['label' => 'Padding', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .card-actions a:not(.icon-only)' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('button_border_radius', ['label' => 'Arredondamento da Borda', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'selectors' => ['{{WRAPPER}} .card-actions a:not(.icon-only)' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);

        // --- Abas de Estilo (Editar) ---
        $this->add_control('heading_edit_style', ['label' => 'Botão de Editar', 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->start_controls_tabs('tabs_edit_button_style');
        $this->start_controls_tab('tab_edit_normal', ['label' => 'Normal']);
        $this->add_control('edit_text_color', ['label' => 'Cor do Texto & Ícone', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#6A6A6A', 'selectors' => ['{{WRAPPER}} .upt-action-edit' => 'color: {{VALUE}}; fill: {{VALUE}};']]);
        $this->add_control('edit_bg_color', ['label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-action-edit' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'edit_border', 'selector' => '{{WRAPPER}} .upt-action-edit']);
        $this->end_controls_tab();
        $this->start_controls_tab('tab_edit_hover', ['label' => 'Hover']);
        $this->add_control('edit_text_color_hover', ['label' => 'Cor do Texto & Ícone', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-action-edit:hover' => 'color: {{VALUE}}; fill: {{VALUE}};']]);
        $this->add_control('edit_bg_color_hover', ['label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#f0f0f0', 'selectors' => ['{{WRAPPER}} .upt-action-edit:hover' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'edit_border_hover', 'selector' => '{{WRAPPER}} .upt-action-edit:hover']);
        $this->end_controls_tab();
        $this->end_controls_tabs();

        // --- Abas de Estilo (Excluir) ---
        $this->add_control('heading_delete_style', ['label' => 'Botão de Excluir', 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->start_controls_tabs('tabs_delete_button_style');
        $this->start_controls_tab('tab_delete_normal', ['label' => 'Normal']);
        $this->add_control('delete_text_color', ['label' => 'Cor do Texto & Ícone', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#a00', 'selectors' => ['{{WRAPPER}} .upt-action-delete' => 'color: {{VALUE}}; fill: {{VALUE}};']]);
        $this->add_control('delete_bg_color', ['label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-action-delete' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'delete_border', 'selector' => '{{WRAPPER}} .upt-action-delete']);
        $this->end_controls_tab();
        $this->start_controls_tab('tab_delete_hover', ['label' => 'Hover']);
        $this->add_control('delete_text_color_hover', ['label' => 'Cor do Texto & Ícone', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .upt-action-delete:hover' => 'color: {{VALUE}}; fill: {{VALUE}};']]);
        $this->add_control('delete_bg_color_hover', ['label' => 'Cor de Fundo', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#f0f0f0', 'selectors' => ['{{WRAPPER}} .upt-action-delete:hover' => 'background-color: {{VALUE}};']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'delete_border_hover', 'selector' => '{{WRAPPER}} .upt-action-delete:hover']);
        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $post_id = get_the_ID();
        if ( ! $post_id ) return;

        $this->add_render_attribute('edit_action', 'class', ['open-edit-modal', 'upt-action-edit', 'icon-' . $settings['edit_icon_position']]);
        if (empty($settings['edit_text'])) {
            $this->add_render_attribute('edit_action', 'class', 'icon-only');
        }
        $this->add_render_attribute('edit_action', 'href', '#');
        $this->add_render_attribute('edit_action', 'data-item-id', $post_id);

        $this->add_render_attribute('delete_action', 'class', ['delete-item-ajax', 'upt-action-delete', 'icon-' . $settings['delete_icon_position']]);
        if (empty($settings['delete_text'])) {
            $this->add_render_attribute('delete_action', 'class', 'icon-only');
        }
        $this->add_render_attribute('delete_action', 'href', '#');
        $this->add_render_attribute('delete_action', 'data-item-id', $post_id);
        ?>
        <div class="card-actions">
            <?php if ( 'yes' === $settings['show_edit'] ) : ?>
                <a <?php echo $this->get_render_attribute_string('edit_action'); ?>>
                    <?php if (!empty($settings['edit_icon']['value'])) { \Elementor\Icons_Manager::render_icon($settings['edit_icon'], ['aria-hidden' => 'true']); } ?>
                    <?php if (!empty($settings['edit_text'])) { echo '<span>' . esc_html($settings['edit_text']) . '</span>'; } ?>
                </a>
            <?php endif; ?>
            <?php if ( 'yes' === $settings['show_delete'] ) : ?>
                <a <?php echo $this->get_render_attribute_string('delete_action'); ?>>
                    <?php if (!empty($settings['delete_icon']['value'])) { \Elementor\Icons_Manager::render_icon($settings['delete_icon'], ['aria-hidden' => 'true']); } ?>
                    <?php if (!empty($settings['delete_text'])) { echo '<span>' . esc_html($settings['delete_text']) . '</span>'; } ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }
}
