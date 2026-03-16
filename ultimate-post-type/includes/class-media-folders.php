<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Media_Folders {

    public const TAXONOMY = 'media_folder';

    public static function init() {
        add_action( 'init', [ self::class, 'register_taxonomy' ] );
        
        // Hooks específicos para carregar os scripts
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_on_admin_page' ] );
        add_action( 'wp_enqueue_media', [ self::class, 'enqueue_for_media_modal' ] );

        // Garante que ao filtrar por uma pasta pai os anexos de pastas filhas também apareçam.
        add_filter( 'ajax_query_attachments_args', [ self::class, 'filter_attachments_by_folder' ] );

        add_filter( 'attachment_fields_to_edit', [ self::class, 'add_compat_field_data' ], 10, 2 );

        add_action( 'admin_footer', [ self::class, 'print_media_templates' ] );
        add_action( 'wp_footer', [ self::class, 'print_media_templates' ] );
    }

    public static function enqueue_on_admin_page($hook) {
        if ($hook === 'upload.php') {
            self::enqueue_assets();
        }
    }

    public static function enqueue_for_media_modal() {
        self::enqueue_assets();
    }
    
    public static function enqueue_assets() {
        // Previne o carregamento duplicado do script na mesma página
        if (wp_script_is('upt-media-folders', 'enqueued')) {
            return;
        }
        
        wp_enqueue_script(
            'upt-media-folders',
            plugin_dir_url( __FILE__ ) . '../assets/js/media-folders.js',
            [ 'media-views' ],
            filemtime( plugin_dir_path( __FILE__ ) . '../assets/js/media-folders.js' ),
            true
        );

        $folders = get_terms([
            'taxonomy' => self::TAXONOMY,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        $folder_data = [];
        if ( ! is_wp_error($folders) ) {
            foreach ($folders as $folder) {
                $folder_data[] = [
                    'term_id' => $folder->term_id,
                    'name' => $folder->name,
                    'slug' => $folder->slug,
                ];
            }
        }
        
        wp_localize_script('upt-media-folders', 'upt_media_folders', [
            'taxonomy' => self::TAXONOMY,
            'folders' => $folder_data,
            'nonce' => wp_create_nonce('upt_ajax_nonce'),
        ]);
        
        $custom_css = "
        .media-toolbar-secondary .attachment-filters.attachment-folder-filter { display: block; max-width: 180px; float: left; }
        .media-toolbar-secondary .upt-folder-management { float: left; display: flex; gap: 8px; align-items: center; margin-left: 10px; }
        .media-toolbar-secondary .new-folder-name-input { height: auto; }
        .media-frame-content .upt-folder-assign-setting select { width: 100%; }
        .media-frame-content .upt-folder-assign-setting .spinner { float: none; margin: 4px; visibility: hidden; }
        .media-modal-content .attachments-browser .media-toolbar{display:block;}
        ";
        wp_add_inline_style( 'media-views', $custom_css );
    }

    /**
     * Ajusta a query de anexos para incluir filhos quando um filtro de pasta é usado.
     */
    public static function filter_attachments_by_folder( $query ) {
        if ( empty( $query[ self::TAXONOMY ] ) ) {
            return $query;
        }

        $folder_slug = sanitize_title( $query[ self::TAXONOMY ] );
        unset( $query[ self::TAXONOMY ] );

        $tax_query = isset( $query['tax_query'] ) && is_array( $query['tax_query'] ) ? $query['tax_query'] : [];

        // Usa term_id para garantir include_children em hierarquia; cai para slug se não achar o termo.
        $term       = get_term_by( 'slug', $folder_slug, self::TAXONOMY );
        $field      = $term && ! is_wp_error( $term ) ? 'term_id' : 'slug';
        $term_value = $term && ! is_wp_error( $term ) ? (int) $term->term_id : $folder_slug;

        $tax_query[] = [
            'taxonomy'         => self::TAXONOMY,
            'field'            => $field,
            'terms'            => [ $term_value ],
            'include_children' => true,
            'operator'         => 'IN',
        ];

        $query['tax_query'] = $tax_query;

        return $query;
    }

    public static function add_compat_field_data($form_fields, $post) {
        $terms = wp_get_object_terms($post->ID, self::TAXONOMY, ['fields' => 'ids']);
        $form_fields['compat']['folder_id'] = !is_wp_error($terms) && !empty($terms) ? $terms[0] : 0;
        return $form_fields;
    }

    public static function print_media_templates() {
        global $pagenow;
        if ( !did_action('wp_enqueue_media') && $pagenow !== 'upload.php' ) {
            return;
        }
        ?>
        <script type="text/html" id="tmpl-upt-folder-manager">
            <div class="upt-folder-management">
                <input type="text" placeholder="Nome da Nova Pasta" class="new-folder-name-input" />
                <button class="button button-secondary new-folder-button">Criar</button>
            </div>
        </script>
        
        <script type="text/html" id="tmpl-upt-attachment-details">
            <label class="setting upt-folder-assign-setting">
                <span>Pasta</span>
                <select class="upt-folder-assign" data-setting="folder">
                    <option value="0">— Sem pasta —</option>
                    <# _.each(upt_media_folders.folders, function(folder) { #>
                        <option value="{{ folder.term_id }}" <# if (data.compat.folder_id == folder.term_id) { #> selected <# } #>>
                            {{ folder.name }}
                        </option>
                    <# }); #>
                </select>
                <span class="spinner upt-folder-assign-spinner"></span>
            </label>
        </script>
        <?php
    }

    public static function register_taxonomy() {
        $labels = [
            'name'              => _x( 'Pastas de Mídia', 'taxonomy general name', 'upt' ),
            'singular_name'     => _x( 'Pasta de Mídia', 'taxonomy singular name', 'upt' ),
            'search_items'      => __( 'Pesquisar Pastas', 'upt' ),
            'all_items'         => __( 'Todas as Pastas', 'upt' ),
            'parent_item'       => __( 'Pasta Superior', 'upt' ),
            'parent_item_colon' => __( 'Pasta Superior:', 'upt' ),
            'edit_item'         => __( 'Editar Pasta', 'upt' ),
            'update_item'       => __( 'Atualizar Pasta', 'upt' ),
            'add_new_item'      => __( 'Adicionar Midia Pasta', 'upt' ),
            'new_item_name'     => __( 'Nome da Nova Pasta', 'upt' ),
            'menu_name'         => __( 'Pastas', 'upt' ),
            'back_to_items'     => __( '← Voltar para as Pastas', 'upt' ),
        ];

        $args = [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'media-folder' ],
            'show_in_rest'      => true,
        ];

        register_taxonomy( self::TAXONOMY, 'attachment', $args );
    }
}
