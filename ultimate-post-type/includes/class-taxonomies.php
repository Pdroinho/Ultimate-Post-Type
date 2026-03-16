<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe responsável por registrar as taxonomias customizadas do plugin.
 * Taxonomias são como as 'Categorias' ou 'Tags' do WordPress.
 */
class UPT_Taxonomies {

    public static function init() {
        add_action( 'init', [ self::class, 'register_taxonomies' ] );
        add_action( 'created_catalog_schema', [ self::class, 'create_parent_category_for_schema' ], 10, 1 );
        add_action( 'pre_delete_term', [ self::class, 'delete_parent_category_for_schema' ], 10, 2 );
    }

    public static function register_taxonomies() {
        /**
         * Registra a taxonomia 'catalog_category'
         * Esta é a taxonomia hierárquica, funcionando como as categorias padrão.
         */
        $cat_labels = [
            'name'              => _x( 'Categorias de Catálogo', 'taxonomy general name', 'upt' ),
            'singular_name'     => _x( 'Categoria', 'taxonomy singular name', 'upt' ),
            'search_items'      => __( 'Pesquisar Categorias', 'upt' ),
            'all_items'         => __( 'Todas as Categorias', 'upt' ),
            'parent_item'       => __( 'Categoria Pai', 'upt' ),
            'parent_item_colon' => __( 'Categoria Pai:', 'upt' ),
            'edit_item'         => __( 'Editar Categoria', 'upt' ),
            'update_item'       => __( 'Atualizar Categoria', 'upt' ),
            'add_new_item'      => __( 'Adicionar Midia Categoria', 'upt' ),
            'new_item_name'     => __( 'Nome da Nova Categoria', 'upt' ),
            'menu_name'         => __( 'Categorias', 'upt' ),
        ];
        register_taxonomy( 'catalog_category', [ 'catalog_item' ], [
            'hierarchical'      => true,
            'labels'            => $cat_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
            'rewrite'           => [ 'slug' => 'catalogo/categoria' ],
        ]);

        /**
         * Registra a taxonomia 'catalog_schema'
         * Esta é a taxonomia não hierárquica que usamos para definir o "tipo" de item (Imóvel, Curso, etc.).
         */
        $schema_labels = [
            'name'              => _x( 'Esquemas', 'taxonomy general name', 'upt' ),
            'singular_name'     => _x( 'Esquema', 'taxonomy singular name', 'upt' ),
            'search_items'      => __( 'Pesquisar Esquemas', 'upt' ),
            'all_items'         => __( 'Todos os Esquemas', 'upt' ),
            'edit_item'         => __( 'Editar Esquema', 'upt' ),
            'update_item'       => __( 'Atualizar Esquema', 'upt' ),
            'add_new_item'      => __( 'Adicionar Novo Esquema', 'upt' ),
            'new_item_name'     => __( 'Nome do Novo Esquema', 'upt' ),
            'menu_name'         => __( 'Esquemas', 'upt' ),
        ];
        register_taxonomy( 'catalog_schema', [ 'catalog_item' ], [
            'hierarchical'      => false,
            'labels'            => $schema_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
            'rewrite'           => [ 'slug' => 'catalogo/esquema' ],
        ]);
    }

    /**
     * Cria uma categoria pai correspondente quando um novo esquema é criado.
     */
    public static function create_parent_category_for_schema( $term_id ) {
        $schema_term = get_term( $term_id, 'catalog_schema' );
        if ( ! $schema_term || is_wp_error( $schema_term ) ) {
            return;
        }

        if ( ! term_exists( $schema_term->slug, 'catalog_category' ) ) {
            wp_insert_term(
                $schema_term->name,
                'catalog_category',
                [ 'slug' => $schema_term->slug ]
            );
        }
    }

    /**
     * Exclui a categoria pai correspondente quando um esquema é excluído.
     */
    public static function delete_parent_category_for_schema( $term_id, $taxonomy ) {
        if ( $taxonomy !== 'catalog_schema' ) {
            return;
        }

        $schema_term = get_term( $term_id, 'catalog_schema' );
        if ( $schema_term && ! is_wp_error( $schema_term ) ) {
            $parent_cat = get_term_by( 'slug', $schema_term->slug, 'catalog_category' );
            if ( $parent_cat ) {
                wp_delete_term( $parent_cat->term_id, 'catalog_category' );
            }
        }
    }
}
