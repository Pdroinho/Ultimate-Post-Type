<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Video_Field_Tag extends \Elementor\Core\DynamicTags\Data_Tag {

    public function get_name() { return 'upt-video-field'; }
    public function get_title() { return 'Campo de Vídeo do Catálogo'; }
    public function get_group() { return 'upt'; }
    public function get_categories() { return [ \Elementor\Modules\DynamicTags\Module::URL_CATEGORY ]; }

    protected function _register_controls() {
        $this->add_control(
            'field_key',
            [
                'label' => 'Campo',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_video_fields_options(),
            ]
        );
    }

    public function get_value(array $options = []) {
        $field_key = $this->get_settings('field_key');
        if (empty($field_key)) {
            return '';
        }
        $video_id = get_post_meta(get_the_ID(), $field_key, true);
        return wp_get_attachment_url($video_id);
    }

    private function get_video_fields_options() {
        $options = ['' => '— Selecione —'];
        $schemas = UPT_Schema_Store::get_schemas();
        if (empty($schemas)) return $options;

        foreach ($schemas as $slug => $data) {
            if (isset($data['fields']) && is_array($data['fields'])) {
                $term = get_term_by('slug', $slug, 'catalog_schema');
                $name = $term ? $term->name : $slug;
                foreach ($data['fields'] as $field) {
                    if ($field['type'] === 'video') {
                        $options[$field['id']] = $name . ' - ' . $field['label'];
                    }
                }
            }
        }
        return $options;
    }
}
