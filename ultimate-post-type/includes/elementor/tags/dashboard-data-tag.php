<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Dashboard_Data_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() { return 'upt-dashboard-data'; }
    public function get_title() { return 'Dados do Item (Painel)'; }
    public function get_group() { return 'upt'; }
    public function get_categories() { return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ]; }

    protected function _register_controls() {
        $this->add_control(
            'data_type',
            [
                'label' => 'Dado',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'status',
                'options' => [
                    'status' => 'Status do Item',
                    'author' => 'Nome do Autor',
                    'id'     => 'ID do Item',
                ],
            ]
        );
    }

    public function render() {
        $data_type = $this->get_settings('data_type');
        $post_id = get_the_ID();

        if (empty($post_id)) {
            return;
        }

        switch ($data_type) {
            case 'status':
                $status_object = get_post_status_object(get_post_status($post_id));
                echo $status_object ? esc_html($status_object->label) : '';
                break;
            case 'author':
                echo esc_html(get_the_author_meta('display_name', get_post_field('post_author', $post_id)));
                break;
            case 'id':
                echo esc_html($post_id);
                break;
        }
    }
}
