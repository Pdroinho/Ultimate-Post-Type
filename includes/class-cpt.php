<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_CPT {
    public static function init() {
        add_action( 'init', [ self::class, 'register_cpt' ] );
    }
    public static function register_cpt() {
        $labels = [
            'name' => 'Itens de Catálogo',
            'singular_name' => 'Item de Catálogo',
            'menu_name' => 'Catálogo',
            'all_items' => 'Todos os Itens',
            'add_new' => 'Adicionar Novo'
        ];
        $args = [
            'label' => 'Item de Catálogo',
            'labels' => $labels,
            'supports' => [ 'title', 'editor', 'thumbnail', 'custom-fields', 'author', 'page-attributes' ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'has_archive' => true,
            'rewrite' => ['slug' => 'catalogo'],
            'capability_type' => 'post'
        ];
        register_post_type('catalog_item', $args);
    }
}
