<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Gallery_Field_Tag extends \Elementor\Core\DynamicTags\Data_Tag {

    public function get_name() { return 'upt-gallery-field'; }
    public function get_title() { return 'Campo de Galeria do Catálogo'; }
    public function get_group() { return 'upt'; }
    public function get_categories() { return [ \Elementor\Modules\DynamicTags\Module::GALLERY_CATEGORY ]; }

    protected function _register_controls() {
        $this->add_control(
            'field_key',
            [
                'label' => 'Campo',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_gallery_fields_options(),
            ]
        );
    }

    public function get_value(array $options = []) {
        $field_key = $this->get_settings('field_key');
        if (empty($field_key)) {
            return [];
        }

        $ids_string = get_post_meta(get_the_ID(), $field_key, true);
        $ids_string = trim($ids_string, " \t\n\r\0\x0B,");
        
        if (empty($ids_string)) {
            return [];
        }

        $ids = explode(',', $ids_string);
        $ids = array_filter(array_map('intval', $ids));
        
        if (empty($ids)) {
            return [];
        }

        $gallery = [];
        foreach ($ids as $id) {
            $gallery[] = ['id' => $id];
        }
        return $gallery;
    }

    private function get_gallery_fields_options() {
        $options = ['' => '— Selecione —'];
        $schemas = UPT_Schema_Store::get_schemas();
        if (empty($schemas)) return $options;

        foreach ($schemas as $slug => $data) {
            if (isset($data['fields']) && is_array($data['fields'])) {
                $term = get_term_by('slug', $slug, 'catalog_schema');
                $name = $term ? $term->name : $slug;
                foreach ($data['fields'] as $field) {
                    if ($field['type'] === 'gallery') {
                        $options[$field['id']] = $name . ' - ' . $field['label'];
                    }
                }
            }
        }
        return $options;
    }
}
