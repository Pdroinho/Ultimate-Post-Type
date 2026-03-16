<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_PDF_Field_Tag extends \Elementor\Core\DynamicTags\Data_Tag {

    public function get_name() {
        return 'upt_pdf_field';
    }

    public function get_title() {
        return __( 'PDF Field', 'upt' );
    }

    public function get_group() {
        return 'upt';
    }

    public function get_categories() {
        return [ \Elementor\Modules\DynamicTags\Module::URL_CATEGORY ];
    }

    protected function register_controls() {
        $schemas = UPT_Schema_Store::get_schemas();
        $options = [];

        // O store salva os schemas como array associativo: [slug => ['fields' => [...]]]
        // (não como lista com 'slug' e 'name').
        foreach ( $schemas as $schema_slug => $schema_data ) {
            $fields = isset( $schema_data['fields'] ) && is_array( $schema_data['fields'] ) ? $schema_data['fields'] : [];

            // Nome amigável: tenta taxonomia 'catalog_schema', senão usa o slug.
            $term = get_term_by( 'slug', $schema_slug, 'catalog_schema' );
            $schema_name = $term ? $term->name : $schema_slug;

            foreach ( $fields as $field ) {
                if ( isset( $field['type'] ) && $field['type'] === 'pdf' ) {
                    $key = $schema_slug . '::' . $field['id'];
                    $options[ $key ] = $schema_name . ' - ' . ( $field['label'] ?? $field['id'] );
                }
            }
        }

        $this->add_control(
            'field',
            [
                'label' => __( 'Select PDF Field', 'upt' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $options,
            ]
        );
    }

    public function get_value( array $options = [] ) {
        $field_key = $this->get_settings( 'field' );
        if ( empty( $field_key ) ) {
            return '';
        }

        list( $schema_slug, $field_id ) = explode( '::', $field_key );
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return '';
        }

        $pdf_id = absint( get_post_meta( $post_id, $field_id, true ) );
        if ( ! $pdf_id ) {
            return '';
        }

        return wp_get_attachment_url( $pdf_id );
    }

    public function render() {
        echo esc_url( $this->get_value() );
    }
}
