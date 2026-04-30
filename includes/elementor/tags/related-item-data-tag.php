<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Related_Item_Data_Tag extends \Elementor\Core\DynamicTags\Data_Tag {

    public function get_name() { return 'upt-related-data'; }
    public function get_title() { return 'Dado de Item Relacionado'; }
    public function get_group() { return 'upt'; }
    public function get_categories() { 
        return [ 
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY,
        ]; 
    }

    private function get_relationship_fields_options() {
        $options = ['' => '— Selecione —'];
        $schemas = UPT_Schema_Store::get_schemas();
        if (empty($schemas)) return $options;

        foreach ($schemas as $slug => $data) {
            if (isset($data['fields']) && is_array($data['fields'])) {
                $term = get_term_by('slug', $slug, 'catalog_schema');
                $name = $term ? $term->name : $slug;
                foreach ($data['fields'] as $field) {
                    if ($field['type'] === 'relationship') {
                        $options[$field['id']] = $name . ' - ' . $field['label'];
                    }
                }
            }
        }
        return $options;
    }

    private function get_all_fields_as_options() {
        $options = [
            'post_title' => 'Título do Item',
            'post_url' => 'Link do Item',
            'featured_image_url' => 'URL da Imagem Destacada',
        ];
        
        $schemas = UPT_Schema_Store::get_schemas();
        if (empty($schemas)) return $options;

        foreach ($schemas as $slug => $data) {
            if (isset($data['fields']) && is_array($data['fields'])) {
                $term = get_term_by('slug', $slug, 'catalog_schema');
                $name = $term ? $term->name : $slug;
                foreach ($data['fields'] as $field) {
                    $options[$field['id']] = $name . ' - ' . $field['label'];
                }
            }
        }
        return $options;
    }

    protected function _register_controls() {
        $this->add_control(
            'relationship_field',
            [
                'label' => 'Campo de Relação (Origem)',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_relationship_fields_options(),
            ]
        );
        $this->add_control(
            'data_to_show',
            [
                'label' => 'Dado a Exibir',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_all_fields_as_options(),
            ]
        );
    }

    public function get_value(array $options = []) {
        $relationship_field_key = $this->get_settings('relationship_field');
        $data_key = $this->get_settings('data_to_show');

        if (empty($relationship_field_key) || empty($data_key)) {
            return '';
        }

        $related_post_id = get_post_meta(get_the_ID(), $relationship_field_key, true);

        if (empty($related_post_id) || !is_numeric($related_post_id)) {
            return '';
        }
        
        switch ($data_key) {
            case 'post_title':
                return get_the_title($related_post_id);
            case 'post_url':
                return get_permalink($related_post_id);
            case 'featured_image_url':
                return get_the_post_thumbnail_url($related_post_id);
            default:
                return get_post_meta($related_post_id, $data_key, true);
        }
    }
}
