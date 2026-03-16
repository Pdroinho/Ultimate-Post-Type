<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Ajax {

    private static $old_category_names = [];

    public static function init() {
        add_action( 'wp_ajax_upt_get_form', [ self::class, 'get_form' ] );
        add_action( 'wp_ajax_upt_save_item', [ self::class, 'save_item' ] );
        add_action( 'wp_ajax_upt_save_draft', [ self::class, 'save_draft' ] );
        add_action( 'wp_ajax_upt_get_draft', [ self::class, 'get_draft' ] );
        add_action( 'wp_ajax_upt_clear_draft', [ self::class, 'clear_draft' ] );
        add_action( 'wp_ajax_upt_add_category', [ self::class, 'add_new_category' ] );
        add_action( 'wp_ajax_upt_get_child_categories', [ self::class, 'get_child_categories' ] );
        add_action( 'wp_ajax_upt_public_get_child_categories', [ self::class, 'public_get_child_categories' ] );
        add_action( 'wp_ajax_nopriv_upt_public_get_child_categories', [ self::class, 'public_get_child_categories' ] );
        add_action( 'wp_ajax_upt_update_category', [ self::class, 'update_category' ] );
        add_action( 'wp_ajax_upt_add_schema_option', [ self::class, 'add_schema_option' ] );
        add_action( 'wp_ajax_upt_delete_item', [ self::class, 'delete_item' ] );
        add_action( 'wp_ajax_upt_bulk_delete_items', [ self::class, 'bulk_delete_items' ] );
        add_action( 'wp_ajax_upt_get_schema_counts', [ self::class, 'get_schema_counts' ] );
        add_action( 'wp_ajax_upt_delete_category', [ self::class, 'delete_category' ] );
        add_action( 'wp_ajax_upt_delete_schema_option', [ self::class, 'delete_schema_option' ] );
        add_action( 'wp_ajax_upt_rename_schema_option', [ self::class, 'rename_schema_option' ] );
        add_action( 'wp_ajax_upt_filter_items', [ self::class, 'filter_items' ] );
        add_action( 'wp_ajax_upt_live_search', [ self::class, 'live_search' ] );
        add_action( 'wp_ajax_nopriv_upt_live_search', [ self::class, 'live_search' ] );
        add_action( 'wp_ajax_upt_create_media_folder', [ self::class, 'create_media_folder' ] );
        add_action( 'wp_ajax_upt_assign_to_folder', [ self::class, 'assign_to_folder' ] );
        add_action( 'wp_ajax_upt_remove_from_folder', [ self::class, 'remove_from_folder' ] );
        add_action( 'wp_ajax_upt_get_category_manager', [ self::class, 'get_category_manager' ] );
        add_action( 'wp_ajax_upt_delete_media_folder', [ self::class, 'delete_media_folder' ] );
        add_action( 'wp_ajax_upt_rename_media_folder', [ self::class, 'rename_media_folder' ] );
        add_action( 'wp_ajax_upt_move_media_folder', [ self::class, 'move_media_folder' ] );

        // Ações para a nova galeria customizada
        add_action( 'wp_ajax_upt_gallery_get_folders', [ self::class, 'gallery_get_folders' ] );
        add_action( 'wp_ajax_upt_gallery_get_images', [ self::class, 'gallery_get_images' ] );
        add_action( 'wp_ajax_upt_get_media_by_ids', [ self::class, 'get_media_by_ids' ] );
        add_action( 'wp_ajax_upt_gallery_delete_image', [ self::class, 'gallery_delete_image' ] );
        add_action( 'wp_ajax_upt_reorder_fields', [ self::class, 'reorder_fields' ] );
        add_action( 'wp_ajax_upt_reorder_items', [ self::class, 'reorder_items' ] );
        add_action( 'wp_ajax_upt_rename_category', [ self::class, 'rename_category' ] );

        add_action( 'edit_term', [ self::class, 'capture_category_old_name' ], 10, 3 );
        add_action( 'edited_catalog_category', [ self::class, 'sync_elementor_category_name' ], 10, 2 );
    }

    private static function normalize_schema_slugs( $schema_slugs ) {
        if ( is_string( $schema_slugs ) ) {
            $decoded = json_decode( $schema_slugs, true );
            if ( is_array( $decoded ) ) {
                $schema_slugs = $decoded;
            }
        }

        if ( ! is_array( $schema_slugs ) ) {
            return [];
        }

        $schema_slugs = array_map( 'sanitize_title', $schema_slugs );
        $schema_slugs = array_filter( $schema_slugs );

        return array_values( array_unique( $schema_slugs ) );
    }

    public static function normalize_search_slug( $value ) {
        if ( ! is_scalar( $value ) ) {
            return '';
        }

        $value = wp_strip_all_tags( (string) $value );
        $value = remove_accents( $value );
        $value = strtolower( $value );
        $value = preg_replace( '/[^a-z0-9]+/', '-', $value );
        $value = trim( $value, '-' );

        return $value ?: '';
    }

    private static function parse_search_target( $raw_target ) {
        $raw_target = sanitize_text_field( (string) $raw_target );

        $type = $raw_target;
        $key  = '';

        if ( strpos( $raw_target, ':' ) !== false ) {
            list( $type, $key ) = explode( ':', $raw_target, 2 );
        }

        $type = $type ?: '';
        $key  = $key ?: '';

        // If no key was provided, attempt to infer intent.
        if ( $key === '' ) {
            // If user provided a known taxonomy slug, treat it as taxonomy target.
            if ( $type && taxonomy_exists( $type ) ) {
                $key  = $type;
                $type = 'taxonomy';
            }
            // If it matches core fields, keep as-is.
            elseif ( in_array( $type, [ 'title', 'content', 'excerpt' ], true ) ) {
                // nothing to do
            }
            // Fallback: assume a meta key when no prefix is given.
            elseif ( $type ) {
                $key  = $type;
                $type = 'meta';
            }
        }

        return [ $type, $key ];
    }

    private static function value_matches_slug( $value, $search_slug, $search_term = '' ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $item ) {
                if ( self::value_matches_slug( $item, $search_slug, $search_term ) ) {
                    return true;
                }
            }
            return false;
        }

        $raw = wp_strip_all_tags( (string) $value );

        if ( $search_term !== '' && stripos( $raw, $search_term ) !== false ) {
            return true;
        }

        $value_slug = self::normalize_search_slug( $raw );
        return ( $search_slug !== '' && $value_slug !== '' && strpos( $value_slug, $search_slug ) !== false );
    }

    public static function get_ids_for_targeted_search( $search_term, $search_target, $tax_query = [] ) {
        $search_term   = sanitize_text_field( (string) $search_term );
        $search_target = sanitize_text_field( (string) $search_target );
        
        if ( $search_term === '' || $search_target === '' ) {
            return [];
        }

        $base_args = [
            'post_type'      => 'catalog_item',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        if ( ! empty( $tax_query ) ) {
            $base_args['tax_query'] = $tax_query;
        }

        $query_args = $base_args + [ 's' => $search_term ];

        $search_extended_closure = function( $search, $wp_query ) {
            global $wpdb;

            if ( empty( $wp_query->get( 's' ) ) ) {
                return $search;
            }

            $s         = $wp_query->get( 's' );
            $like_term = '%' . $wpdb->esc_like( $s ) . '%';

            return $wpdb->prepare( " AND ({$wpdb->posts}.post_title LIKE %s)", $like_term );
        };

        add_filter( 'posts_search', $search_extended_closure, 10, 2 );
        $ids_query = new WP_Query( $query_args );
        remove_filter( 'posts_search', $search_extended_closure, 10 );

        $matching_ids = isset( $ids_query->posts ) ? (array) $ids_query->posts : [];
        $matching_ids = array_map( 'intval', $matching_ids );
        $matching_ids = array_filter( $matching_ids );

        return array_values( array_unique( $matching_ids ) );
    }
    public static function gallery_get_images() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) || ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error();
        }

        $folder_id = isset( $_POST['folder_id'] ) ? absint( $_POST['folder_id'] ) : 0;
        $media_type = isset( $_POST['media_type'] ) ? sanitize_text_field( $_POST['media_type'] ) : '';
        $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 0;
        $page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        if ( $page < 1 ) {
            $page = 1;
        }
        $mime_filter = [ 'image', 'video', 'application/pdf' ];
        if ( in_array( $media_type, [ 'image', 'video', 'pdf' ], true ) ) {
            $mime_filter = ( 'pdf' === $media_type ) ? 'application/pdf' : $media_type;
        }



        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => ( $per_page > 0 ? $per_page : -1 ),
            'paged' => ( $per_page > 0 ? $page : 1 ),
            'orderby' => 'date',
            'order' => 'DESC',
            'post_mime_type' => $mime_filter,
        ];

        if ( $folder_id > 0 ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'media_folder',
                    'field' => 'term_id',
                    'terms' => $folder_id,
                    'include_children' => false,
                ],
            ];
        } else {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'media_folder',
                    'operator' => 'NOT EXISTS',
                ],
            ];
        }

        $query = new WP_Query($args);
        $media_items = [];

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $id = get_the_ID();
                $mime_type = get_post_mime_type($id);
                $is_video = strpos($mime_type, 'video') !== false;
                $is_pdf   = ($mime_type === 'application/pdf');
                $thumbnail_url = '';
                $is_fallback = false;

                if ($is_video) {
                    if (has_post_thumbnail($id)) {
                        $thumbnail_url = get_the_post_thumbnail_url($id, 'thumbnail');
                    }
                    if ( ! $thumbnail_url ) {
                        $thumbnail_url = wp_get_attachment_thumb_url($id);
                    }
                    if ( ! $thumbnail_url ) {
                        $thumbnail_url = wp_mime_type_icon($mime_type);
                        if (strpos($thumbnail_url, '/wp-includes/images/media/') !== false) {
                            $is_fallback = true;
                        }
                    }
                } elseif ($is_pdf) {
                    $thumbnail_url = wp_get_attachment_image_url( $id, 'medium' );

                    if ( ! $thumbnail_url ) {
                        $thumbnail_url = wp_get_attachment_thumb_url( $id );
                    }

                    if ( ! $thumbnail_url ) {
                        $thumbnail_url = wp_mime_type_icon('application/pdf');
                    }

                    if ( $thumbnail_url && strpos($thumbnail_url, '/wp-includes/images/media/') !== false ) {
                        $is_fallback = true;
                    }
                } else {
                    $thumbnail_url = wp_get_attachment_image_url( $id, 'thumbnail' );
                }

                $full_url = wp_get_attachment_url($id);

                if ( ! $thumbnail_url && $full_url && $mime_type === 'image/svg+xml' ) {
                    $thumbnail_url = $full_url;
                }

                $file_size_bytes = 0;
                $file_size_human = '';
                $file_path = get_attached_file( $id );
                if ( $file_path && file_exists( $file_path ) ) {
                    $file_size_bytes = filesize( $file_path );
                    if ( function_exists( 'size_format' ) ) {
                        $file_size_human = size_format( $file_size_bytes, 2 );
                    } else {
                        $file_size_human = $file_size_bytes . ' B';
                    }
                }

                $media_items[] = [
                    'id' => $id,
                    'type' => ($is_video ? 'video' : ($is_pdf ? 'pdf' : 'image')),
                    'mime_type' => $mime_type,
                    'thumbnail_url' => $thumbnail_url,
                    'full_url' => $full_url,
                    'name' => get_the_title($id),
                    'filename' => basename( $full_url ),
                    'file_size_bytes' => $file_size_bytes,
                    'file_size_human' => $file_size_human,
                    'is_fallback' => $is_fallback
                ];
            }
        }
        wp_reset_postdata();

        $pagination = [
            'enabled'      => ( $per_page > 0 ),
            'page'         => $per_page > 0 ? $page : 1,
            'per_page'     => $per_page > 0 ? $per_page : -1,
            'total_items'  => isset( $query->found_posts ) ? (int) $query->found_posts : count( $media_items ),
            'total_pages'  => isset( $query->max_num_pages ) ? (int) $query->max_num_pages : 1,
        ];

        wp_send_json_success( [
            'items' => $media_items,
            'pagination' => $pagination,
        ] );
    }

    public static function get_media_by_ids() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) || ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error();
        }

        $ids = isset( $_POST['ids'] ) ? (array) $_POST['ids'] : [];
        $ids = array_map( 'absint', $ids );
        $ids = array_filter( $ids );

        if ( empty( $ids ) ) {
            wp_send_json_success( [ 'items' => [] ] );
        }

        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'post_mime_type' => [ 'image', 'video', 'application/pdf' ],
            'post__in'       => $ids,
            'orderby'        => 'post__in',
        ];

        $query = new WP_Query( $args );
        $media_items = [];

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $id         = get_the_ID();
                $mime_type  = get_post_mime_type( $id );
                $is_video   = strpos( $mime_type, 'video' ) !== false;
                $is_pdf     = ( $mime_type === 'application/pdf' );
                $thumbnail_url = '';
                $is_fallback   = false;

                if ( $is_video ) {
                    if ( has_post_thumbnail( $id ) ) {
                        $thumbnail_url = get_the_post_thumbnail_url( $id, 'thumbnail' );
                    }
                    if ( ! $thumbnail_url ) {
                        $thumbnail_url = wp_get_attachment_thumb_url( $id );
                    }
                    if ( ! $thumbnail_url ) {
                        $thumbnail_url = wp_mime_type_icon( $mime_type );
                        if ( strpos( $thumbnail_url, '/wp-includes/images/media/' ) !== false ) {
                            $is_fallback = true;
                        }
                    }
                } elseif ( $is_pdf ) {
                    $thumbnail_url = wp_get_attachment_image_url( $id, 'medium' );

                    if ( ! $thumbnail_url ) {
                        $thumbnail_url = wp_get_attachment_thumb_url( $id );
                    }

                    if ( ! $thumbnail_url ) {
                        $thumbnail_url = wp_mime_type_icon( 'application/pdf' );
                    }

                    if ( $thumbnail_url && strpos( $thumbnail_url, '/wp-includes/images/media/' ) !== false ) {
                        $is_fallback = true;
                    }
                } else {
                    $thumbnail_url = wp_get_attachment_image_url( $id, 'thumbnail' );
                }

                $full_url = wp_get_attachment_url( $id );

                if ( ! $thumbnail_url && $full_url && $mime_type === 'image/svg+xml' ) {
                    $thumbnail_url = $full_url;
                }

                $media_items[] = [
                    'id'            => $id,
                    'type'          => ($is_video ? 'video' : ($is_pdf ? 'pdf' : 'image')),
                    'mime_type'     => $mime_type,
                    'thumbnail_url' => $thumbnail_url,
                    'full_url'      => $full_url,
                    'name'          => get_the_title( $id ),
                    'filename'      => basename( $full_url ),
                    'is_fallback'   => $is_fallback,
                ];
            }
            wp_reset_postdata();
        }

        wp_send_json_success( [ 'items' => $media_items ] );
    }

    public static function save_item() {
        if ( ! check_ajax_referer( 'upt_new_item', 'upt_submit_nonce', false ) ) { ob_clean(); wp_send_json_error(['message' => 'Falha de segurança.']); }
        if ( ! is_user_logged_in() ) { ob_clean(); wp_send_json_error(['message' => 'Você precisa estar logado.']); }
        
        $is_update = isset($_POST['item_id']) && !empty($_POST['item_id']);
        $post_id = $is_update ? absint($_POST['item_id']) : 0;

        // Impede criação de novos itens quando o esquema já atingiu o limite configurado
        if ( ! $is_update ) {
            $schema_slug = isset( $_POST['schema_slug'] ) ? sanitize_text_field( $_POST['schema_slug'] ) : '';
            $cat_ids     = isset( $_POST['categoria-do-item'] ) ? array_filter( array_map( 'absint', (array) $_POST['categoria-do-item'] ) ) : [];
            if ( $schema_slug ) {
                $schema_definitions = class_exists( 'UPT_Schema_Store' ) ? UPT_Schema_Store::get_schemas() : [];
                if ( isset( $schema_definitions[ $schema_slug ]['items_limit'] ) ) {
                    $items_limit = absint( $schema_definitions[ $schema_slug ]['items_limit'] );
                } else {
                    $items_limit = 0;
                }

                $limit_max_per_category = isset( $schema_definitions[ $schema_slug ]['items_limit_max_per_category'] )
                    ? (bool) $schema_definitions[ $schema_slug ]['items_limit_max_per_category']
                    : ! empty( $schema_definitions[ $schema_slug ]['items_limit_per_category'] );

                if ( $items_limit > 0 ) {
                    if ( $limit_max_per_category && ! empty( $cat_ids ) ) {
                        foreach ( $cat_ids as $cat_id ) {
                            $count_query = new \WP_Query( [
                                'post_type'      => 'catalog_item',
                                'post_status'    => [ 'publish', 'pending', 'draft' ],
                                'posts_per_page' => 1,
                                'fields'         => 'ids',
                                'tax_query'      => [
                                    [
                                        'taxonomy' => 'catalog_schema',
                                        'field'    => 'slug',
                                        'terms'    => $schema_slug,
                                    ],
                                    [
                                        'taxonomy' => 'catalog_category',
                                        'field'    => 'term_id',
                                        'terms'    => $cat_id,
                                    ],
                                ],
                                'author'         => get_current_user_id(),
                            ] );

                            if ( $count_query->found_posts >= $items_limit ) {
                                $cat_name = get_term_field( 'name', $cat_id, 'catalog_category' );
                                $cat_label = ( ! is_wp_error( $cat_name ) && $cat_name ) ? $cat_name : 'esta categoria';
                                ob_clean();
                                wp_send_json_error( [
                                    'message' => sprintf(
                                        'Você atingiu o limite máximo de %d itens para %s.',
                                        $items_limit,
                                        esc_html( $cat_label )
                                    ),
                                ] );
                            }
                        }
                    } else {
                        $count_query = new \WP_Query( [
                            'post_type'      => 'catalog_item',
                            'post_status'    => [ 'publish', 'pending', 'draft' ],
                            'posts_per_page' => -1,
                            'fields'         => 'ids',
                            'tax_query'      => [
                                [
                                    'taxonomy' => 'catalog_schema',
                                    'field'    => 'slug',
                                    'terms'    => $schema_slug,
                                ],
                            ],
                            'author'         => get_current_user_id(),
                        ] );

                        if ( $count_query->found_posts >= $items_limit ) {
                            ob_clean();
                            wp_send_json_error( [
                                'message' => sprintf(
                                    'Você atingiu o limite máximo de %d itens para este esquema.',
                                    $items_limit
                                ),
                            ] );
                        }
                    }
                }
            }
        }

        
        if ($is_update) {
            $post_to_edit = get_post($post_id);
            if (!$post_to_edit || ($post_to_edit->post_author != get_current_user_id() && !current_user_can('manage_options'))) {
                ob_clean(); 
                wp_send_json_error(['message' => 'Permissão negada.']); 
            }
        }

        $post_data = [];
        if ( isset( $_POST['item_title'] ) ) $post_data['post_title'] = sanitize_text_field( $_POST['item_title'] );
        if ( isset( $_POST['item_content'] ) ) $post_data['post_content'] = wp_kses_post( $_POST['item_content'] );

        // Mapeia campos do tipo "blog_post" (Conteúdo completo) para o conteúdo nativo do post
        $schema_slug = isset( $_POST['schema_slug'] ) ? sanitize_text_field( $_POST['schema_slug'] ) : '';
        if ( $schema_slug && class_exists( 'UPT_Schema_Store' ) ) {
            $blog_fields = UPT_Schema_Store::get_fields_for_schema( $schema_slug );
            if ( is_array( $blog_fields ) ) {
                foreach ( $blog_fields as $field ) {
                    if ( ! isset( $field['type'], $field['id'] ) ) {
                        continue;
                    }
                    if ( $field['type'] !== 'blog_post' ) {
                        continue;
                    }

                    $base_id = $field['id'];
                    $content_key = $base_id . '_content';
                    $excerpt_key = $base_id . '_excerpt';

                    if ( isset( $_POST[ $content_key ] ) ) {
                        $post_data['post_content'] = wp_kses_post( $_POST[ $content_key ] );
                    }
                    if ( isset( $_POST[ $excerpt_key ] ) ) {
                        $post_data['post_excerpt'] = sanitize_textarea_field( $_POST[ $excerpt_key ] );
                    }

                    $excerpt_is_required = ! empty( $field['excerpt_required'] );
                    $excerpt_max_length  = isset( $field['excerpt_max_length'] ) ? absint( $field['excerpt_max_length'] ) : 0;

                    $excerpt_value_for_check = isset( $post_data['post_excerpt'] ) ? trim( (string) $post_data['post_excerpt'] ) : '';

                    if ( $excerpt_is_required && $excerpt_value_for_check === '' ) {
                        ob_clean();
                        wp_send_json_error( [ 'message' => 'O resumo do post é obrigatório.' ] );
                    }

                    if ( $excerpt_max_length > 0 && $excerpt_value_for_check !== '' ) {
                        $len = function_exists( 'mb_strlen' ) ? mb_strlen( $excerpt_value_for_check ) : strlen( $excerpt_value_for_check );
                        if ( $len > $excerpt_max_length ) {
                            ob_clean();
                            wp_send_json_error( [ 'message' => 'O resumo do post excede o limite de ' . $excerpt_max_length . ' caracteres.' ] );
                        }
                    }
                    // Consideramos apenas o primeiro campo blog_post encontrado por esquema.
                    break;
                }
            }
        }

        // Atualiza status do item (publicado / arquivado) apenas na edição
        if ( $is_update && isset( $_POST['item_status'] ) ) {
            $allowed_status = [ 'publish', 'draft' ];
            $new_status     = sanitize_text_field( $_POST['item_status'] );
            if ( in_array( $new_status, $allowed_status, true ) ) {
                $post_data['post_status'] = $new_status;
            }
        }

        if ($is_update) {
            $post_data['ID'] = $post_id;
            wp_update_post( $post_data );
        } else {
            $post_data['post_status'] = 'publish';
            $post_data['post_type'] = 'catalog_item';
            $post_data['post_author'] = get_current_user_id();
            $post_id = wp_insert_post( $post_data );
        }

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            if ( isset( $_POST['featured_image_id'] ) ) {
                $image_id = absint( $_POST['featured_image_id'] );
                if ( $image_id > 0 ) {
                    set_post_thumbnail( $post_id, $image_id );
                } else {
                    delete_post_thumbnail( $post_id );
                }
            }
            $schema_slug = sanitize_text_field( $_POST['schema_slug'] );
            wp_set_object_terms( $post_id, $schema_slug, 'catalog_schema' );
            
            if (isset($_POST['categoria-do-item'])) {
                $cat_ids = array_map('absint', (array) $_POST['categoria-do-item']);
                wp_set_object_terms($post_id, $cat_ids, 'catalog_category');
            } else {
                wp_set_object_terms($post_id, null, 'catalog_category');
            }

            // Validação de campos obrigatórios de mídia (image, video, gallery)
            // baseada na definição do schema (field['required']) e nos valores enviados.
            $fields_to_save = UPT_Schema_Store::get_fields_for_schema( $schema_slug );

            if ( is_array( $fields_to_save ) && ! empty( $fields_to_save ) ) {
                $media_required_errors = array();
                $media_required_fields = array();

                foreach ( $fields_to_save as $field ) {
                    if ( empty( $field['required'] ) ) {
                        continue;
                    }
                    if ( empty( $field['type'] ) || empty( $field['id'] ) ) {
                        continue;
                    }

                    $field_type = $field['type'];
                    $field_id   = $field['id'];

                    if ( ! in_array( $field_type, array( 'image', 'video', 'pdf', 'gallery', 'core_featured_image' ), true ) ) {
                        continue;
                    }

                    $raw_value = isset( $_POST[ $field_id ] ) ? $_POST[ $field_id ] : '';

                    $is_empty = false;

                    if ( 'gallery' === $field_type ) {
                        if ( is_array( $raw_value ) ) {
                            $ids = array_filter( array_map( 'absint', $raw_value ) );
                            if ( empty( $ids ) ) {
                                $is_empty = true;
                            }
                        } else {
                            $raw_str = is_string( $raw_value ) ? trim( $raw_value ) : '';
                            if ( '' === $raw_str ) {
                                $is_empty = true;
                            } else {
                                $ids = array_filter(
                                    array_map(
                                        'absint',
                                        array_map( 'trim', explode( ',', $raw_str ) )
                                    )
                                );
                                if ( empty( $ids ) ) {
                                    $is_empty = true;
                                }
                            }
                        }
                    } else {
                        if ( is_array( $raw_value ) ) {
                            $id = 0;
                        } else {
                            $id = absint( $raw_value );
                        }
                        if ( $id <= 0 ) {
                            $is_empty = true;
                        }
                    }

                    if ( $is_empty ) {
                        $label = isset( $field['label'] ) ? $field['label'] : $field_id;
                        $media_required_errors[] = sprintf( 'O campo "%s" é obrigatório.', $label );
                        $media_required_fields[] = array(
                            'id'    => $field_id,
                            'label' => $label,
                            'type'  => $field_type,
                        );
                    }
                }

                if ( ! empty( $media_required_errors ) ) {
                    if ( ! $is_update && $post_id ) {
                        // Evita deixar rascunho órfão em caso de erro ao criar.
                        wp_delete_post( $post_id, true );
                    }
                    ob_clean();
                    wp_send_json_error(
                        array(
                            'message'        => implode( ' ', $media_required_errors ),
                            'media_required' => true,
                            'media_fields'   => $media_required_fields,
                        )
                    );
                }
            }



            
$fields_to_save = UPT_Schema_Store::get_fields_for_schema( $schema_slug );

            foreach ($fields_to_save as $field) {

                $field_id = $field['id'];
                if ( in_array($field['type'], ['core_title', 'core_content', 'core_featured_image', 'taxonomy']) ) continue;

                if (isset($_POST[$field_id])) {
                    $raw_value = $_POST[$field_id];
                    $sanitized_value = '';

                    if (is_array($raw_value)) {
                        $sanitized_value = array_map('sanitize_text_field', $raw_value);
                    } else {
                        switch ($field['type']) {
                            case 'textarea':
                                $sanitized_value = sanitize_textarea_field($raw_value);
                                break;
                            case 'wysiwyg':
                                $sanitized_value = wp_kses_post( $raw_value );
                                break;
                            case 'number':
                            case 'price': $sanitized_value = floatval($raw_value); break;
                            case 'url':
                                $raw_value = trim($raw_value);
                                $sanitized_value = $raw_value === '' ? '' : esc_url_raw($raw_value);
                                break;
                            case 'image':
                            case 'video':
                            case 'pdf':
                            case 'relationship': $sanitized_value = absint($raw_value); break;
                            case 'gallery':
                                $ids = explode(',', $raw_value);
                                $sanitized_ids = array_map('absint', $ids);
                                $sanitized_value = implode(',', array_filter($sanitized_ids));
                                break;
                            case 'list':
                                $lines = preg_split('/\r\n|\r|\n/', (string)$raw_value);
                                $lines = array_map('trim', $lines);
                                $lines = array_filter($lines, function($v){ return $v !== ''; });
                                $sanitized_value = array_values(array_map('sanitize_text_field', $lines));
                                break;
                            case 'unit_measure':
                                $sanitized_value = floatval($raw_value);
                                if (isset($_POST[$field_id . '_unit'])) {
                                    $sanitized_unit = sanitize_text_field($_POST[$field_id . '_unit']);
                                    update_post_meta($post_id, $field_id . '_unit', $sanitized_unit);
                                }
                                break;
                            default: $sanitized_value = sanitize_text_field($raw_value); break;
                        }
                    }
                    update_post_meta($post_id, $field_id, $sanitized_value);
                } else {
                    delete_post_meta($post_id, $field_id);
                }
            }
            
            $show_all = isset($_POST['show_all']) && in_array($_POST['show_all'], ['true', 'yes', '1'], true);
            $active_schema_slug = isset($_POST['active_schema_slug']) ? sanitize_text_field($_POST['active_schema_slug']) : '';
            $active_category_id = isset($_POST['active_category_id']) ? absint($_POST['active_category_id']) : 0;
            $table_html_args = [];
            $table_html_args['posts_per_page'] = isset($_POST['per_page']) ? intval($_POST['per_page']) : -1;
            $table_html_args['paged'] = isset($_POST['paged']) ? absint($_POST['paged']) : 1;

            if (!$show_all || !current_user_can('manage_options')) {
                $table_html_args['author'] = get_current_user_id();
            }
            if (isset($_POST['template_id']) && !empty($_POST['template_id'])) {
                $table_html_args['template_id'] = absint($_POST['template_id']);
            }
            $card_variant = isset($_POST['card_variant']) ? sanitize_key($_POST['card_variant']) : 'modern';
            if ( ! in_array( $card_variant, [ 'modern', 'legacy' ], true ) ) {
                $card_variant = 'modern';
            }
            $table_html_args['card_variant'] = $card_variant;
            if (!empty($active_schema_slug) || $active_category_id > 0) {
                $table_html_args['tax_query'] = ['relation' => 'AND'];
                if (!empty($active_schema_slug)) {
                    $table_html_args['tax_query'][] = [
                        'taxonomy' => 'catalog_schema',
                        'field'    => 'slug',
                        'terms'    => $active_schema_slug,
                    ];
                }
                if ($active_category_id > 0) {
                    $table_html_args['tax_query'][] = [
                        'taxonomy' => 'catalog_category',
                        'field'    => 'term_id',
                        'terms'    => $active_category_id,
                    ];
                }
            }
            $table_data = self::get_items_list_html($table_html_args);

            $parent_cat_term = get_term_by('slug', $active_schema_slug, 'catalog_category');
            $parent_cat_id = ($parent_cat_term && !is_wp_error($parent_cat_term)) ? $parent_cat_term->term_id : 0;
            $category_args = [
                'taxonomy'         => 'catalog_category',
                'name'             => 'upt-category-filter',
                'class'            => 'upt-category-filter',
                'show_option_none' => 'Todas as categorias',
                'hierarchical'     => true,
                'hide_empty'       => 0,
                'echo'             => 0,
                'child_of'         => $parent_cat_id,
                'selected'         => $active_category_id,
            ];
            $filter_html = wp_dropdown_categories($category_args);

            ob_clean();
            wp_send_json_success([
                'message' => 'Item salvo!', 
                'html' => $table_data['html'],
                'pagination_html' => $table_data['pagination_html'],
                'filter_html' => $filter_html,
            ]);
        } else {
            ob_clean();
            wp_send_json_error(['message' => 'Erro ao salvar o item.']);
        }
        wp_die();
    }
    
    public static function delete_media_folder() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Falha de segurança.' ] );
        }
        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( [ 'message' => 'Permissão negada.' ] );
        }

        $term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
        $delete_media = isset( $_POST['delete_media'] ) && absint( $_POST['delete_media'] ) === 1;
        $deleted_media_count = 0;

        if ( ! $term_id ) {
            wp_send_json_error( [ 'message' => 'ID da pasta não fornecido.' ] );
        }

        $term = get_term( $term_id, 'media_folder' );
        if ( ! $term || is_wp_error( $term ) ) {
            wp_send_json_error( [ 'message' => 'Pasta não encontrada.' ] );
        }

        if ( $delete_media ) {
            $media_query = new WP_Query( [
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'tax_query'      => [
                    [
                        'taxonomy'         => 'media_folder',
                        'field'            => 'term_id',
                        'terms'            => $term_id,
                        'include_children' => false,
                    ],
                ],
            ] );

            if ( is_wp_error( $media_query ) ) {
                wp_send_json_error( [ 'message' => 'Erro ao buscar mídias da pasta.' ] );
            }

            $attachment_ids = $media_query->posts;
            $deleted_media_count = count( $attachment_ids );

            foreach ( $attachment_ids as $attachment_id ) {
                $deleted = wp_delete_attachment( $attachment_id, true );

                if ( false === $deleted || is_wp_error( $deleted ) ) {
                    $error_message = is_wp_error( $deleted ) ? $deleted->get_error_message() : 'Erro ao excluir a mídia.';
                    wp_send_json_error( [ 'message' => sprintf( 'Falha ao excluir a mídia %d: %s', $attachment_id, $error_message ) ] );
                }
            }
        }

        $result = wp_delete_term( $term_id, 'media_folder' );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        } else {
            wp_send_json_success( [
                'deleted_media' => $deleted_media_count,
                'delete_media'  => $delete_media ? 1 : 0,
            ] );
        }
    }
    public static function rename_media_folder() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Falha de segurança.' ] );
        }
        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( [ 'message' => 'Permissão negada.' ] );
        }

        $term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
        $name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

        if ( ! $term_id || '' === $name ) {
            wp_send_json_error( [ 'message' => 'Dados inválidos para renomear a pasta.' ] );
        }

        $term = get_term( $term_id, 'media_folder' );
        if ( ! $term || is_wp_error( $term ) ) {
            wp_send_json_error( [ 'message' => 'Pasta não encontrada.' ] );
        }

        $result = wp_update_term(
            $term_id,
            'media_folder',
            [
                'name' => $name,
            ]
        );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        } else {
            $updated = get_term( $term_id, 'media_folder' );
            wp_send_json_success(
                [
                    'term_id' => $updated->term_id,
                    'name'    => $updated->name,
                    'slug'    => $updated->slug,
                ]
            );
        }
    }



    public static function get_category_manager() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) || ! is_user_logged_in() ) {
            wp_send_json_error();
        }

        $schema_slug = isset($_POST['schema_slug']) ? sanitize_text_field($_POST['schema_slug']) : '';
        if (empty($schema_slug)) {
            wp_send_json_error(['message' => 'Esquema não especificado.']);
        }

        $parent_term = get_term_by('slug', $schema_slug, 'catalog_category');
        if (!$parent_term || is_wp_error($parent_term)) {
            wp_send_json_error(['message' => 'Esquema inválido.']);
        }
        $parent_id = $parent_term->term_id;

        $terms = get_terms([
            'taxonomy' => 'catalog_category',
            'hide_empty' => false,
            'child_of' => $parent_id,
        ]);

        ob_start();
        ?>
        <a href="#" id="upt-modal-close" class="upt-modal-close-button">&times;</a>
        <h2>Gerenciar Categorias</h2>
        <h3 class="upt-modal-title">Esquema: <?php echo esc_html($parent_term->name); ?></h3>
        <p>Você pode excluir categorias que não possuem itens associados.</p>
        <?php if (!empty($terms) && !is_wp_error($terms)) : ?>
            <ul class="upt-category-manager-list">
                <?php foreach ($terms as $term) : ?>
                    <li data-term-id="<?php echo esc_attr($term->term_id); ?>">
                        <span class="term-name"><?php echo esc_html($term->name); ?></span>
                        <div style="display: flex; align-items: center;">
                            <span class="term-count"><?php echo esc_html($term->count); ?> item(ns)</span>
                            <span class="term-actions">
                                <?php if ($term->count == 0 || current_user_can('manage_options')) : ?>
                                <a href="#" class="delete-category-from-manager" data-term-id="<?php echo esc_attr($term->term_id); ?>" data-term-name="<?php echo esc_attr($term->name); ?>">Excluir</a>
                                <?php endif; ?>
                            </span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p>Nenhuma categoria encontrada para este esquema.</p>
        <?php endif; ?>
        <?php
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }

    public static function delete_schema_option() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) { wp_send_json_error( [ 'message' => 'Falha de segurança.' ] ); }
        if ( ! is_user_logged_in() || !current_user_can('upload_files') ) { wp_send_json_error( [ 'message' => 'Permissão negada.' ] ); }

        $schema_slug = isset($_POST['schema_slug']) ? sanitize_text_field($_POST['schema_slug']) : '';
        $field_id = isset($_POST['field_id']) ? sanitize_text_field($_POST['field_id']) : '';
        $option_to_delete = isset($_POST['option_value']) ? sanitize_text_field($_POST['option_value']) : '';

        if ( empty($schema_slug) || empty($field_id) || empty($option_to_delete) ) {
            wp_send_json_error( [ 'message' => 'Dados insuficientes.' ] );
        }

        $all_schemas = UPT_Schema_Store::get_schemas();

        if ( !isset($all_schemas[$schema_slug]) ) {
            wp_send_json_error( [ 'message' => 'Esquema não encontrado.' ] );
        }

        $field_found = false;
        foreach ($all_schemas[$schema_slug]['fields'] as $key => $field) {
            if ($field['id'] === $field_id) {
                $options = isset($field['options']) ? explode('|', $field['options']) : [];
                $new_options = array_diff($options, [$option_to_delete]);
                $all_schemas[$schema_slug]['fields'][$key]['options'] = implode('|', $new_options);
                $field_found = true;
                break;
            }
        }

        if (!$field_found) {
            wp_send_json_error( [ 'message' => 'Campo não encontrado.' ] );
        }

        if (UPT_Schema_Store::save_schemas($all_schemas)) {
            wp_send_json_success( [ 'message' => 'Opção excluída com sucesso.' ] );
        } else {
            wp_send_json_error( [ 'message' => 'Erro ao excluir a opção.' ] );
        }
    }

    public static function add_schema_option() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) { wp_send_json_error( [ 'message' => 'Falha de segurança.' ] ); }
        if ( ! is_user_logged_in() ) { wp_send_json_error( [ 'message' => 'Você precisa estar logado.' ] ); }
        
        $schema_slug = isset($_POST['schema_slug']) ? sanitize_text_field($_POST['schema_slug']) : '';
        $field_id = isset($_POST['field_id']) ? sanitize_text_field($_POST['field_id']) : '';
        $new_option = isset($_POST['new_option_name']) ? sanitize_text_field($_POST['new_option_name']) : '';

        if ( empty($schema_slug) || empty($field_id) || empty($new_option) ) {
            wp_send_json_error( [ 'message' => 'Dados insuficientes.' ] );
        }

        $all_schemas = UPT_Schema_Store::get_schemas();

        if ( !isset($all_schemas[$schema_slug]) ) {
            wp_send_json_error( [ 'message' => 'Esquema não encontrado.' ] );
        }

        $field_found = false;
        foreach ($all_schemas[$schema_slug]['fields'] as $key => $field) {
            if ($field['id'] === $field_id) {
                $options = isset($field['options']) ? explode('|', $field['options']) : [];
                if (in_array($new_option, $options)) {
                    wp_send_json_error( [ 'message' => 'Esta opção já existe.' ] );
                }
                $options[] = $new_option;
                $all_schemas[$schema_slug]['fields'][$key]['options'] = implode('|', $options);
                $field_found = true;
                break;
            }
        }

        if (!$field_found) {
            wp_send_json_error( [ 'message' => 'Campo não encontrado.' ] );
        }

        if (UPT_Schema_Store::save_schemas($all_schemas)) {
            wp_send_json_success( [ 'name' => $new_option ] );
        } else {
            wp_send_json_error( [ 'message' => 'Erro ao salvar a nova opção.' ] );
        }
    }



    public static function rename_schema_option() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) { wp_send_json_error( [ 'message' => 'Falha de segurança.' ] ); }
        if ( ! is_user_logged_in() || ! current_user_can('upload_files') ) { wp_send_json_error( [ 'message' => 'Permissão negada.' ] ); }

        $schema_slug = isset($_POST['schema_slug']) ? sanitize_text_field($_POST['schema_slug']) : '';
        $field_id = isset($_POST['field_id']) ? sanitize_text_field($_POST['field_id']) : '';
        $old_option = isset($_POST['old_option_name']) ? sanitize_text_field($_POST['old_option_name']) : '';
        $new_option = isset($_POST['new_option_name']) ? sanitize_text_field($_POST['new_option_name']) : '';

        if ( empty($schema_slug) || empty($field_id) || empty($old_option) || empty($new_option) ) {
            wp_send_json_error( [ 'message' => 'Dados insuficientes.' ] );
        }

        if ( $old_option === $new_option ) {
            wp_send_json_success( [ 'name' => $new_option ] );
        }

        $all_schemas = UPT_Schema_Store::get_schemas();
        if ( !isset($all_schemas[$schema_slug]) ) {
            wp_send_json_error( [ 'message' => 'Esquema não encontrado.' ] );
        }

        $field_found = false;
        foreach ($all_schemas[$schema_slug]['fields'] as $key => $field) {
            if ($field['id'] === $field_id) {
                $options = isset($field['options']) ? array_values(array_filter(array_map('trim', explode('|', $field['options'])))) : [];

                if (!in_array($old_option, $options, true)) {
                    wp_send_json_error( [ 'message' => 'Opção antiga não encontrada.' ] );
                }
                if (in_array($new_option, $options, true)) {
                    wp_send_json_error( [ 'message' => 'Já existe uma opção com esse nome.' ] );
                }

                foreach ($options as $i => $opt) {
                    if ($opt === $old_option) {
                        $options[$i] = $new_option;
                    }
                }
                $all_schemas[$schema_slug]['fields'][$key]['options'] = implode('|', $options);
                $field_found = true;
                break;
            }
        }

        if (!$field_found) {
            wp_send_json_error( [ 'message' => 'Campo não encontrado.' ] );
        }

        if (UPT_Schema_Store::save_schemas($all_schemas)) {
            wp_send_json_success( [ 'name' => $new_option ] );
        } else {
            wp_send_json_error( [ 'message' => 'Erro ao renomear a opção.' ] );
        }
    }

    public static function remove_from_folder() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) || ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( [ 'message' => 'Permissão negada.' ] );
        }

        $attachment_ids = isset( $_POST['attachment_ids'] ) ? array_map( 'absint', (array) $_POST['attachment_ids'] ) : [];
        $folder_id = isset( $_POST['folder_id'] ) ? absint( $_POST['folder_id'] ) : 0;
        $media_type = isset( $_POST['media_type'] ) ? sanitize_text_field( $_POST['media_type'] ) : '';
        $mime_filter = [ 'image', 'video', 'application/pdf' ];
        if ( in_array( $media_type, [ 'image', 'video', 'pdf' ], true ) ) {
            $mime_filter = ( 'pdf' === $media_type ) ? 'application/pdf' : $media_type;
        }



        if ( empty( $attachment_ids ) || $folder_id === 0 ) {
            wp_send_json_error( [ 'message' => 'Dados inválidos.' ] );
        }

        foreach ( $attachment_ids as $attachment_id ) {
            wp_remove_object_terms( $attachment_id, $folder_id, 'media_folder' );
        }

        wp_send_json_success();
    }
    
    public static function gallery_delete_image() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) || ! current_user_can( 'delete_posts' ) ) {
            wp_send_json_error(['message' => 'Permissão negada.']);
        }

        $image_ids = isset($_POST['image_id']) ? (array) $_POST['image_id'] : [];
        $image_ids = array_map('absint', $image_ids);

        if ( empty($image_ids) ) {
            wp_send_json_error(['message' => 'Nenhum ID de imagem válido fornecido.']);
        }
        
        foreach($image_ids as $image_id) {
            if ( get_post_type($image_id) === 'attachment' ) {
        $result = wp_delete_attachment($image_id, true);
                if ($result === false) {
                    wp_send_json_error(['message' => 'Não foi possível excluir uma ou mais imagens.']);
                }
            }
        }

        wp_send_json_success();
    }
    
    public static function gallery_get_folders() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) || ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error();
        }

        $folders = get_terms([
            'taxonomy' => 'media_folder',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if ( is_wp_error($folders) ) {
            wp_send_json_error();
        }

        wp_send_json_success( $folders );
    }

    public static function create_media_folder() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) || ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( [ 'message' => 'Permissão negada.' ] );
        }

        $folder_name = isset( $_POST['folder_name'] ) ? sanitize_text_field( $_POST['folder_name'] ) : '';
        $parent_id   = isset( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0;

        if ( $parent_id ) {
            $parent_term = get_term( $parent_id, 'media_folder' );
            if ( ! $parent_term || is_wp_error( $parent_term ) ) {
                wp_send_json_error( [ 'message' => 'Pasta superior inválida.' ] );
            }
        }

        if ( empty( $folder_name ) ) {
            wp_send_json_error( [ 'message' => 'O nome da pasta não pode ser vazio.' ] );
        }

        if ( term_exists( $folder_name, 'media_folder' ) ) {
            wp_send_json_error( [ 'message' => 'Uma pasta com este nome já existe.' ] );
        }

        $result = wp_insert_term( $folder_name, 'media_folder', [
            'parent' => $parent_id,
        ] );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        } else {
            $term = get_term( $result['term_id'], 'media_folder' );
            wp_send_json_success( [
                'term_id' => $term->term_id,
                'name'    => $term->name,
                'slug'    => $term->slug,
                'parent'  => (int) $term->parent,
            ] );
        }
    }

    public static function move_media_folder() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) || ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( [ 'message' => 'Permissão negada.' ] );
        }

        $term_id       = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
        $new_parent_id = isset( $_POST['new_parent_id'] ) ? absint( $_POST['new_parent_id'] ) : 0;

        if ( ! $term_id ) {
            wp_send_json_error( [ 'message' => 'Pasta inválida.' ] );
        }

        $term = get_term( $term_id, 'media_folder' );
        if ( ! $term || is_wp_error( $term ) ) {
            wp_send_json_error( [ 'message' => 'Pasta não encontrada.' ] );
        }

        if ( $new_parent_id ) {
            $parent_term = get_term( $new_parent_id, 'media_folder' );
            if ( ! $parent_term || is_wp_error( $parent_term ) ) {
                wp_send_json_error( [ 'message' => 'Pasta de destino inválida.' ] );
            }
        }

        // Prevent moving into itself or its descendants
        if ( $new_parent_id === $term_id ) {
            wp_send_json_error( [ 'message' => 'Não é possível mover uma pasta para dentro dela mesma.' ] );
        }

        $cursor = $new_parent_id;
        $guard  = 0;
        while ( $cursor && $guard < 200 ) {
            if ( $cursor === $term_id ) {
                wp_send_json_error( [ 'message' => 'Não é possível mover uma pasta para dentro de uma subpasta dela.' ] );
            }
            $t = get_term( $cursor, 'media_folder' );
            if ( ! $t || is_wp_error( $t ) ) {
                break;
            }
            $cursor = (int) $t->parent;
            $guard++;
        }

        $result = wp_update_term( $term_id, 'media_folder', [
            'parent' => $new_parent_id,
        ] );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        $updated = get_term( $term_id, 'media_folder' );
        wp_send_json_success( [
            'term_id' => (int) $updated->term_id,
            'name'    => $updated->name,
            'slug'    => $updated->slug,
            'parent'  => (int) $updated->parent,
        ] );
    }

    public static function assign_to_folder() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) || ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( [ 'message' => 'Permissão negada.' ] );
        }

        $attachment_ids = isset( $_POST['attachment_ids'] ) ? array_map( 'absint', (array) $_POST['attachment_ids'] ) : [];
        $folder_id = isset( $_POST['folder_id'] ) ? absint( $_POST['folder_id'] ) : 0;
        $media_type = isset( $_POST['media_type'] ) ? sanitize_text_field( $_POST['media_type'] ) : '';
        $mime_filter = [ 'image', 'video', 'application/pdf' ];
        if ( in_array( $media_type, [ 'image', 'video', 'pdf' ], true ) ) {
            $mime_filter = ( 'pdf' === $media_type ) ? 'application/pdf' : $media_type;
        }



        if ( empty( $attachment_ids ) ) {
            wp_send_json_error( [ 'message' => 'Nenhuma imagem selecionada.' ] );
        }

        foreach ( $attachment_ids as $attachment_id ) {
            wp_set_object_terms( $attachment_id, $folder_id, 'media_folder', false );
        }

        wp_send_json_success();
    }
    
    public static function live_search() {
        check_ajax_referer('upt_ajax_nonce', 'nonce');
    
        $search_term = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        $schema_slugs = isset($_POST['schema_slugs']) ? $_POST['schema_slugs'] : [];
        $schema_slugs = self::normalize_schema_slugs( $schema_slugs );
        // Se "Post (WordPress)" estiver selecionado, entra em modo WP posts (post_type = 'post') e ignora esquemas do upt.
        $is_wp_posts_mode = in_array( 'wp_post', $schema_slugs, true );
        if ( $is_wp_posts_mode ) {
            $schema_slugs = [ 'wp_post' ];
        }

        $paged = isset($_POST['paged']) ? absint($_POST['paged']) : 1;
        $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : -1;
        $pagination_type = isset($_POST['pagination_type']) ? sanitize_text_field($_POST['pagination_type']) : 'numbers';
        $pagination_trigger = isset($_POST['infinite_trigger']) ? sanitize_text_field($_POST['infinite_trigger']) : 'button';
        $show_arrows = isset($_POST['show_arrows']) ? (bool) $_POST['show_arrows'] : true;
        $load_more_text = isset($_POST['load_more_text']) ? sanitize_text_field($_POST['load_more_text']) : __( 'Carregar mais', 'upt' );
        $raw_targets = isset($_POST['search_targets']) ? $_POST['search_targets'] : [];
        if ( is_string( $raw_targets ) ) {
            $decoded = json_decode( $raw_targets, true );
            if ( is_array( $decoded ) ) {
                $raw_targets = $decoded;
            } else {
                $raw_targets = explode( ',', $raw_targets );
            }
        }
        $search_targets = [];
        if ( is_array( $raw_targets ) ) {
            foreach ( $raw_targets as $rt ) {
                if ( ! is_scalar( $rt ) ) { continue; }
                $rt = trim( (string) $rt );
                if ( $rt === '' ) { continue; }
                $search_targets[] = sanitize_text_field( $rt );
            }
        }

        $search_slug = isset($_POST['search_slug']) ? sanitize_text_field($_POST['search_slug']) : self::normalize_search_slug( $search_term );
        
        if ( ! $template_id ) {
            wp_send_json_error();
        }
                $tax_query = [];
        if ( ! empty( $term_id ) ) {
            $cat_taxonomy = $is_wp_posts_mode ? 'category' : 'catalog_category';

            // Filtra por categoria incluindo automaticamente todos os descendentes.
            // Isso garante que, ao clicar na categoria "pai", os itens das subcategorias também apareçam.
            $tax_query[] = [
                'taxonomy'         => $cat_taxonomy,
                'field'            => 'term_id',
                'terms'            => [ (int) $term_id ],
                'include_children' => true,
                'operator'         => 'IN',
            ];
        }

        if ( ! $is_wp_posts_mode && ! empty( $schema_slugs ) ) {
            $tax_query[] = [
                'taxonomy' => 'catalog_schema',
                'field'    => 'slug',
                'terms'    => $schema_slugs,
            ];
        }

        if ( count( $tax_query ) > 1 && ! isset( $tax_query['relation'] ) ) {
            $tax_query['relation'] = 'AND';
        }

        $use_targeted_search = ( ! $is_wp_posts_mode && $search_term !== '' && ! empty( $search_targets ) );

        $pagination_html = '';
        $query = null;
        $total_pages = 0;

        if ( $use_targeted_search ) {
            $matching_ids = [];
            foreach ( $search_targets as $target ) {
                $matching_ids = array_merge( $matching_ids, self::get_ids_for_targeted_search( $search_term, $target, $tax_query ) );
            }
            $matching_ids = array_values( array_unique( $matching_ids ) );

            // Fallback to general search if nothing matched to avoid empty results when a target is set.
            if ( ! empty( $matching_ids ) ) {
                $ppp          = ( $posts_per_page > 0 ) ? $posts_per_page : -1;

                $query_args = [
                    'post_type'      => $is_wp_posts_mode ? 'post' : 'catalog_item',
                    'post_status'    => 'publish',
                    'posts_per_page' => $ppp,
                    'paged'          => ( $ppp > 0 ) ? $paged : 1,
                    'post__in'       => $matching_ids,
                    'orderby'        => 'post__in',
                ];

                if ( ! empty( $tax_query ) ) {
                    $query_args['tax_query'] = $tax_query;
                }

                $query        = new WP_Query( $query_args );
                $total_pages  = ( $posts_per_page > 0 ) ? (int) ceil( count( $matching_ids ) / $posts_per_page ) : 1;
            } else {
                $use_targeted_search = false;
            }
        }

        if ( ! $use_targeted_search ) {
            $args = [
                'post_type'      => $is_wp_posts_mode ? 'post' : 'catalog_item',
                'post_status'    => 'publish',
                'posts_per_page' => $posts_per_page,
                'paged'          => $paged,
            ];

            if ( ! empty( $search_term ) ) {
                $args['s'] = $search_term;
            }

            if ( ! empty( $tax_query ) ) {
                $args['tax_query'] = $tax_query;
            }

            $search_extended_closure = function( $search, $wp_query ) {
                global $wpdb;
        
                if ( empty( $wp_query->get( 's' ) ) ) {
                    return $search;
                }
        
                $s         = $wp_query->get( 's' );
                $like_term = '%' . $wpdb->esc_like( $s ) . '%';
        
                return $wpdb->prepare( " AND ({$wpdb->posts}.post_title LIKE %s)", $like_term );
            };

            if ( ! empty( $search_term ) ) {
                add_filter( 'posts_search', $search_extended_closure, 10, 2 );
            }
            
            $query = new WP_Query( $args );

            if ( ! empty( $search_term ) ) {
                remove_filter( 'posts_search', $search_extended_closure, 10 );
            }

            $total_pages = (int) $query->max_num_pages;
        }
        
        ob_start();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                echo '<div class="elementor-grid-item">';
                echo \Elementor\Plugin::instance()->frontend->get_builder_content( $template_id, true );
                echo '</div>';
            }
        }
        $html = ob_get_clean();
        
        $add_args = [];
        if ( $search_term !== '' ) {
            $add_args['s_upt'] = $search_term;
        }
        if ( ! empty( $search_targets ) ) {
            $add_args['upt_target'] = implode( ',', $search_targets );
        }
        if ( ! empty( $term_id ) ) {
            $add_args['upt_category'] = $term_id;
        }

        if ( $posts_per_page > 0 && $total_pages > 1 ) {
            $pagination_links = paginate_links( [
                'base'      => add_query_arg( 'paged', '%#%' ),
                'format'    => '',
                'total'     => $total_pages,
                'current'   => $paged,
                'prev_text' => $show_arrows ? '«' : '',
                'next_text' => $show_arrows ? '»' : '',
                'add_args'  => $add_args,
                'type'      => 'array',
            ] );

            if ( ! empty( $pagination_links ) && class_exists( 'UPT_Listing_Widget' ) && method_exists( 'UPT_Listing_Widget', 'build_pagination_markup' ) ) {
                $pagination_html = UPT_Listing_Widget::build_pagination_markup( $pagination_links, $pagination_type, $paged, $total_pages, [
                    'show_arrows'      => $show_arrows,
                    'infinite_trigger' => $pagination_trigger,
                    'load_more_text'   => $load_more_text,
                ] );
            } else {
                $pagination_html = is_array( $pagination_links ) ? implode( '', $pagination_links ) : '';
            }
        }
        
        wp_reset_postdata();
        wp_send_json_success(['html' => $html, 'pagination' => $pagination_html]);
    }

    public static function get_items_list_html( $args = [] ) {
        $default_args = [
            'post_type' => 'catalog_item',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'pending', 'draft'],
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => true,
            'paged' => 1,
            'card_variant' => 'modern',
            'pagination_type' => 'numbers',
            'pagination_infinite_trigger' => 'scroll',
            'pagination_numbers_show_arrows' => true,
            'pagination_load_more_text' => '',
        ];
        
        $query_args = wp_parse_args( $args, $default_args );

        $listing_post_type = $query_args['post_type'] ?? 'catalog_item';
        $is_form_submission_listing = is_array( $listing_post_type )
            ? in_array( '4gt_form_submission', $listing_post_type, true )
            : ( (string) $listing_post_type === '4gt_form_submission' );

        $template_id = isset($query_args['template_id']) ? $query_args['template_id'] : 0;
        unset($query_args['template_id']);
        $card_variant = isset($query_args['card_variant']) ? $query_args['card_variant'] : 'modern';
        unset($query_args['card_variant']);
        $card_variant = in_array($card_variant, ['modern', 'legacy'], true) ? $card_variant : 'modern';

        $pagination_type = isset( $query_args['pagination_type'] ) ? sanitize_key( $query_args['pagination_type'] ) : 'numbers';
        unset( $query_args['pagination_type'] );
        if ( ! in_array( $pagination_type, [ 'numbers', 'arrows', 'prev_next', 'infinite' ], true ) ) {
            $pagination_type = 'numbers';
        }

        $pagination_infinite_trigger = isset( $query_args['pagination_infinite_trigger'] ) ? sanitize_key( $query_args['pagination_infinite_trigger'] ) : 'scroll';
        unset( $query_args['pagination_infinite_trigger'] );
        if ( ! in_array( $pagination_infinite_trigger, [ 'scroll', 'button' ], true ) ) {
            $pagination_infinite_trigger = 'scroll';
        }

        $pagination_numbers_show_arrows = true;
        if ( isset( $query_args['pagination_numbers_show_arrows'] ) ) {
            $pagination_numbers_show_arrows = (bool) $query_args['pagination_numbers_show_arrows'];
        }
        unset( $query_args['pagination_numbers_show_arrows'] );

        $pagination_load_more_text = isset( $query_args['pagination_load_more_text'] ) ? (string) $query_args['pagination_load_more_text'] : '';
        unset( $query_args['pagination_load_more_text'] );

        // Descobre se a consulta está filtrando um esquema específico.
        // Usado para aplicar a ordenação manual por esquema e para detectar campos de imagem.
        $schema_slug = '';
        if ( ! empty( $query_args['tax_query'] ) && is_array( $query_args['tax_query'] ) ) {
            foreach ( $query_args['tax_query'] as $tax_query ) {
                if ( isset( $tax_query['taxonomy'] ) && $tax_query['taxonomy'] === 'catalog_schema' ) {
                    $terms = isset( $tax_query['terms'] ) ? $tax_query['terms'] : '';
                    $field = isset( $tax_query['field'] ) ? $tax_query['field'] : 'slug';

                    if ( is_array( $terms ) ) {
                        $terms = reset( $terms );
                    }

                    if ( $terms ) {
                        if ( $field === 'term_id' ) {
                            $schema_term = get_term( (int) $terms, 'catalog_schema' );
                            if ( $schema_term && ! is_wp_error( $schema_term ) ) {
                                $schema_slug = $schema_term->slug;
                            }
                        } else {
                            $schema_slug = $terms;
                        }
                    }
                    break;
                }
            }
        }

        // Quando há um esquema, garante que todos os itens dele possuam a meta de ordem.
        // Em seguida, aplica a ordenação manual (meta upt_manual_order) com fallback pela data.
        if ( $schema_slug && self::is_manual_order_enabled( $schema_slug ) ) {
            self::ensure_manual_order_meta( $schema_slug );

            $query_args['meta_key'] = 'upt_manual_order';
            $query_args['orderby']  = [
                'meta_value_num' => 'DESC',
                'date'           => 'DESC',
            ];
        }

        $search_term = '';
        if ( isset( $query_args['s'] ) ) {
            $search_term = trim( (string) $query_args['s'] );
        }

        $search_extended_closure = null;
        if ( $search_term !== '' ) {
            $query_args['suppress_filters'] = false;

            $search_extended_closure = function( $search, $wp_query ) {
                global $wpdb;

                if ( empty( $wp_query->get( 's' ) ) ) {
                    return $search;
                }

                $s         = $wp_query->get( 's' );
                $like_term = '%' . $wpdb->esc_like( $s ) . '%';

                $search = $wpdb->prepare( " AND ({$wpdb->posts}.post_title LIKE %s)", $like_term );

                return $search;
            };

            add_filter( 'posts_search', $search_extended_closure, 10, 2 );
        }

        $items_query = new WP_Query($query_args);

        if ( $search_extended_closure ) {
            remove_filter( 'posts_search', $search_extended_closure, 10 );
        }

        // Se a página atual ficou além do total (ex.: apagou o único item da última página),
        // recua para a última página disponível para não retornar lista vazia sem necessidade.
        if (
            $query_args['posts_per_page'] > 0
            && $query_args['paged'] > 1
            && ! $items_query->have_posts()
            && $items_query->max_num_pages > 0
            && $items_query->max_num_pages < $query_args['paged']
        ) {
            $query_args['paged'] = (int) $items_query->max_num_pages;
            $items_query        = new WP_Query($query_args);
        }

        // Detecta se o esquema atual possui algum campo de imagem.
        // Se não houver campo de imagem no esquema, o card não exibirá thumbnail.
        $schema_has_image_field = true;
        $schema_fields_for_media = [];
        if ( class_exists('UPT_Schema_Store') ) {
            $schema_has_image_field = false;

            if ( $schema_slug ) {
                $fields = UPT_Schema_Store::get_fields_for_schema( $schema_slug );
                if ( ! empty( $fields ) && is_array( $fields ) ) {
                    $schema_fields_for_media = $fields;
                    foreach ( $fields as $field ) {
                        if ( ! empty( $field['type'] ) && in_array( $field['type'], [ 'image', 'gallery', 'core_featured_image' ], true ) ) {
                            $schema_has_image_field = true;
                            break;
                        }
                    }
                }
            }
        }


        ob_start();
        if ($items_query->have_posts()) :
            while ($items_query->have_posts()) : $items_query->the_post();
                if ( $template_id > 0 && class_exists('\Elementor\Plugin') ) {
                    echo \Elementor\Plugin::instance()->frontend->get_builder_content( $template_id, true );
                } else {
                    $post_id      = get_the_ID();
                    $post_status  = get_post_status( $post_id );
                    $status_obj   = get_post_status_object( $post_status );
                    $status_label = $status_obj && ! empty( $status_obj->label ) ? $status_obj->label : '';

                    $category_label = '';
                    $category_class = '';
                    $categories     = get_the_terms( $post_id, 'catalog_category' );
                    if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
                        $first_cat = $categories[0];

                        // Esconde a badge quando for a categoria fallback/default.
                        $fallback_slugs = [ 'sem-categoria', 'sem-categoria-2', 'uncategorized' ];
                        $fallback_names = [ 'Sem categoria', 'sem categoria', 'Uncategorized' ];

                        $slug_is_fallback = isset( $first_cat->slug ) && in_array( $first_cat->slug, $fallback_slugs, true );
                        $name_is_fallback = isset( $first_cat->name ) && in_array( $first_cat->name, $fallback_names, true );

                        if ( ! $slug_is_fallback && ! $name_is_fallback ) {
                            $category_label = $first_cat->name;
                            $category_class = sanitize_html_class( $first_cat->slug );
                        }
                    }

                    // Prioriza mídias: 1) thumb nativa; 2) imagem única; 3) galeria; 4) vídeo; 5) PDF.
                    $featured_id       = get_post_thumbnail_id( $post_id );
                    $unique_image_id   = 0;
                    $gallery_ids_list  = [];
                    $single_video_id   = 0;
                    $single_pdf_id     = 0;

                    if ( ! empty( $schema_fields_for_media ) ) {
                        foreach ( $schema_fields_for_media as $field ) {
                            $fid  = isset( $field['id'] ) ? $field['id'] : '';
                            $ftyp = isset( $field['type'] ) ? $field['type'] : '';
                            if ( ! $fid || ! $ftyp ) {
                                continue;
                            }

                            $meta_val = get_post_meta( $post_id, $fid, true );
                            if ( empty( $meta_val ) ) {
                                continue;
                            }

                            if ( 'image' === $ftyp && ! $unique_image_id ) {
                                $unique_image_id = absint( $meta_val );
                            }

                            if ( 'gallery' === $ftyp && empty( $gallery_ids_list ) ) {
                                $parts = is_array( $meta_val ) ? $meta_val : explode( ',', $meta_val );
                                $gallery_ids_list = array_filter( array_map( 'absint', $parts ) );
                            }

                            if ( 'video' === $ftyp && ! $single_video_id ) {
                                $single_video_id = absint( $meta_val );
                            }

                            if ( 'pdf' === $ftyp && ! $single_pdf_id ) {
                                $single_pdf_id = absint( $meta_val );
                            }
                        }
                    }

                    if ( $card_variant === 'modern' ) {
                        ?>
                        <div class="upt-item-card upt-item-card--modern" data-item-id="<?php echo esc_attr( $post_id ); ?>">
                            <?php if ( $schema_has_image_field ) : ?>
                            <div class="upt-item-card__media">
                                <?php
                                $rendered_media = false;

                                if ( $featured_id && has_post_thumbnail() ) {
                                    the_post_thumbnail( 'full' );
                                    $rendered_media = true;
                                } elseif ( $unique_image_id ) {
                                    echo wp_get_attachment_image( $unique_image_id, 'large' );
                                    $rendered_media = true;
                                } elseif ( ! empty( $gallery_ids_list ) ) {
                                    echo '<div class="upt-media-slider" data-interval="3600">';
                                        foreach ( $gallery_ids_list as $idx => $gid ) {
                                            $mime       = get_post_mime_type( $gid );
                                            $is_video   = $mime && strpos( $mime, 'video/' ) === 0;
                                            $active_class = $idx === 0 ? ' is-active' : '';

                                            if ( $is_video ) {
                                                $full_url = wp_get_attachment_url( $gid );
                                                $poster   = wp_get_attachment_thumb_url( $gid );
                                                if ( ! $poster ) {
                                                    $poster = wp_mime_type_icon( $mime );
                                                }
                                                $overlay  = plugin_dir_url( __FILE__ ) . '../assets/img/1x1.png';

                                                if ( $full_url ) {
                                                    echo '<div class="upt-media-slide upt-media-slide--video' . esc_attr( $active_class ) . '">';
                                                        echo '<div class="upt-video-thumb">';
                                                            echo '<video muted playsinline preload="metadata" src="' . esc_url( $full_url ) . '#t=1"' . ( $poster ? ' poster="' . esc_url( $poster ) . '"' : '' ) . '></video>';
                                                            echo '<img class="upt-video-overlay" src="' . esc_url( $overlay ) . '" alt="" loading="lazy" />';
                                                        echo '</div>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                $src = wp_get_attachment_image_url( $gid, 'large' );
                                                if ( ! $src ) { continue; }
                                                echo '<div class="upt-media-slide' . esc_attr( $active_class ) . '">';
                                                    echo '<img src="' . esc_url( $src ) . '" alt="" loading="lazy" />';
                                                echo '</div>';
                                            }
                                        }
                                    echo '</div>';
                                    $rendered_media = true;
                                } elseif ( $single_video_id ) {
                                    $thumb = wp_get_attachment_thumb_url( $single_video_id );
                                    if ( ! $thumb ) {
                                        $mime  = get_post_mime_type( $single_video_id );
                                        $thumb = wp_mime_type_icon( $mime ? $mime : 'video/mp4' );
                                    }
                                    if ( $thumb ) {
                                        echo '<img src="' . esc_url( $thumb ) . '" alt="" class="upt-media-fallback upt-media-fallback--video" />';
                                        $rendered_media = true;
                                    }
                                } elseif ( $single_pdf_id ) {
                                    $thumb = wp_get_attachment_image_url( $single_pdf_id, 'medium' );
                                    if ( ! $thumb ) {
                                        $thumb = wp_mime_type_icon( 'application/pdf' );
                                    }
                                    if ( $thumb ) {
                                        echo '<img src="' . esc_url( $thumb ) . '" alt="PDF" class="upt-media-fallback upt-media-fallback--pdf" />';
                                        $rendered_media = true;
                                    }
                                }

                                if ( ! $rendered_media ) {
                                    echo '<div class="no-thumb"></div>';
                                }
                                ?>
                            </div>
                            <?php endif; ?>

                            <div class="upt-item-card__body">
                                <div class="upt-item-card__status">
                                    <?php if ( $category_label ) : ?>
                                        <span class="upt-badge upt-badge--muted upt-badge--category-<?php echo esc_attr( $category_class ); ?>"><?php echo esc_html( $category_label ); ?></span>
                                    <?php endif; ?>
                                    <span class="upt-badge upt-badge--status upt-badge--status-<?php echo esc_attr( $post_status ); ?>"><?php echo esc_html( $status_label ); ?></span>
                                </div>

                                <h4 class="card-title" title="<?php echo esc_attr( get_the_title() ); ?>"><?php the_title(); ?></h4>

                                <div class="card-actions upt-card-actions--inline">
                                    <a href="#" class="upt-card-btn open-edit-modal" data-item-id="<?php echo esc_attr( $post_id ); ?>" title="Editar">
                                        <?php if ( ! $is_form_submission_listing ) : ?>
                                            <span class="upt-card-btn__label">Editar</span>
                                        <?php endif; ?>
                                    </a>
                                    <a href="#" class="upt-card-btn upt-card-btn--danger delete-item-ajax" data-item-id="<?php echo esc_attr( $post_id ); ?>" title="Excluir">
                                        <?php if ( ! $is_form_submission_listing ) : ?>
                                            <span class="upt-card-btn__label">Apagar</span>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="upt-item-card" data-item-id="<?php echo get_the_ID(); ?>">
                            <?php if ( $schema_has_image_field ) : ?>
                            <div class="card-thumbnail">
                                <?php 
                                if (has_post_thumbnail()) { 
                                    the_post_thumbnail('thumbnail'); 
                                } else { 
                                    echo '<div class="no-thumb"></div>'; 
                                } 
                                ?>
                            </div>
                            <?php endif; ?>
                            <div class="card-content">
                                <h4 class="card-title" title="<?php echo esc_attr( get_the_title() ); ?>"><?php the_title(); ?></h4>
                                <div class="card-meta">
                                    <span><?php echo get_post_status_object(get_post_status())->label; ?></span>
                                </div>
                            </div>
                            <div class="card-actions">
                                <a href="#" class="open-edit-modal" data-item-id="<?php echo get_the_ID(); ?>" title="Editar"></a>
                                <a href="#" class="delete-item-ajax" data-item-id="<?php echo get_the_ID(); ?>" title="Excluir"></a>
                            </div>
                        </div>
                        <?php
                    }
                }
            endwhile;
        else :
        ?>
            <p class="no-items-message">Nenhum item encontrado com os filtros selecionados.</p>
        <?php 
        endif;
        $html = ob_get_clean();

        $pagination_html = '';
        $total_pages = 1;
        $current_page = max( 1, (int) $query_args['paged'] );
        if ($query_args['posts_per_page'] > 0 && $items_query->max_num_pages > 1) {
            $total_pages = (int) $items_query->max_num_pages;
            $pagination_links = paginate_links([
                'total' => $total_pages,
                'current' => $current_page,
                'prev_text' => '«',
                'next_text' => '»',
                'type' => 'array',
            ]);

            if ( ! empty( $pagination_links ) && is_array( $pagination_links ) && class_exists( 'UPT_Listing_Widget' ) && method_exists( 'UPT_Listing_Widget', 'build_pagination_markup' ) ) {
                $pagination_html = UPT_Listing_Widget::build_pagination_markup( $pagination_links, $pagination_type, $current_page, $total_pages, [
                    'show_arrows'      => $pagination_numbers_show_arrows,
                    'infinite_trigger' => $pagination_infinite_trigger,
                    'load_more_text'   => $pagination_load_more_text,
                ] );
            } else {
                $pagination_html = is_array( $pagination_links ) ? implode( '', $pagination_links ) : '';
            }
        }

        $next_page = 0;
        if ( $total_pages > 1 && $current_page < $total_pages ) {
            $next_page = $current_page + 1;
        }
        
        wp_reset_postdata();

        return [
            'html' => $html,
            'pagination_html' => $pagination_html,
            'current_page' => $current_page,
            'total_pages' => $total_pages,
            'next_page' => $next_page,
        ];
    }

    public static function filter_items() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) {
            wp_send_json_error(['message' => 'Nonce inválido.']);
        }
    
        $args = [];
        $show_all = isset($_POST['show_all']) && in_array($_POST['show_all'], ['true', 'yes', '1'], true);

        if (!$show_all || !current_user_can('manage_options')) {
            $args['author'] = get_current_user_id();
        }

        if (isset($_POST['template_id']) && !empty($_POST['template_id'])) {
            $args['template_id'] = absint($_POST['template_id']);
        }

        if ( isset( $_POST['card_variant'] ) ) {
            $card_variant = sanitize_key( $_POST['card_variant'] );
            if ( in_array( $card_variant, [ 'modern', 'legacy' ], true ) ) {
                $args['card_variant'] = $card_variant;
            }
        }

        if (isset($_POST['paged']) && absint($_POST['paged']) > 0) {
            $args['paged'] = absint($_POST['paged']);
        }

        if (isset($_POST['per_page']) && intval($_POST['per_page']) !== 0) {
            $args['posts_per_page'] = intval($_POST['per_page']);
        }

        if ( isset( $_POST['pagination_type'] ) ) {
            $pagination_type = sanitize_key( $_POST['pagination_type'] );
            if ( in_array( $pagination_type, [ 'numbers', 'arrows', 'prev_next', 'infinite' ], true ) ) {
                $args['pagination_type'] = $pagination_type;
            }
        }

        if ( isset( $_POST['pagination_infinite_trigger'] ) ) {
            $trigger = sanitize_key( $_POST['pagination_infinite_trigger'] );
            if ( in_array( $trigger, [ 'scroll', 'button' ], true ) ) {
                $args['pagination_infinite_trigger'] = $trigger;
            }
        }
    
        if ( isset( $_POST['search_term'] ) && trim( $_POST['search_term'] ) !== '' ) {
            $args['s'] = sanitize_text_field( $_POST['search_term'] );
        }
    
        $tax_query = [];

        if ( isset( $_POST['category_id'] ) && $_POST['category_id'] > 0 ) {
            $tax_query[] = [
                'taxonomy' => 'catalog_category',
                'field'    => 'term_id',
                'terms'    => absint( $_POST['category_id'] ),
            ];
        }

        if ( isset( $_POST['schema_slug'] ) && !empty($_POST['schema_slug']) ) {
            $tax_query[] = [
                'taxonomy' => 'catalog_schema',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $_POST['schema_slug'] ),
            ];
        }

        if ( ! empty( $tax_query ) ) {
            if ( count( $tax_query ) > 1 ) {
                $tax_query['relation'] = 'AND';
            }
            $args['tax_query'] = $tax_query;
        }
    
        $filtered_data = self::get_items_list_html( $args );
    
        wp_send_json_success( $filtered_data );
    }

    public static function get_form() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) {
            wp_send_json_error( ['code' => 'invalid_nonce'] );
        }
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( ['code' => 'not_logged_in'] );
        }
        set_query_var( 'is_editing', isset($_POST['item_id']) && $_POST['item_id'] > 0 );
        set_query_var( 'item_id', isset($_POST['item_id']) ? absint($_POST['item_id']) : 0 );
        set_query_var( 'schema_slug', isset($_POST['schema']) ? sanitize_text_field($_POST['schema']) : '');
        ob_start();
        include UPT_PLUGIN_DIR . 'templates/front-form-submit.php';
        $html = ob_get_clean();
        wp_send_json_success( [ 'html' => $html ] );
        wp_die();
    }

    public static function add_new_category() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) { wp_send_json_error( [ 'message' => 'Falha de segurança.' ] ); }
        if ( ! is_user_logged_in() ) { wp_send_json_error( [ 'message' => 'Você precisa estar logado.' ] ); }
        if ( ! isset( $_POST['new_cat_name'] ) || empty( $_POST['new_cat_name'] ) ) { wp_send_json_error( [ 'message' => 'O nome da categoria não pode ser vazio.' ] ); }
        
        $new_cat_name = sanitize_text_field( $_POST['new_cat_name'] );
        $parent_id = isset( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0;

        $create_subcategories = ! empty( $_POST['create_subcategories'] );
        $subcategories_raw    = isset( $_POST['subcategories'] ) ? sanitize_textarea_field( wp_unslash( $_POST['subcategories'] ) ) : '';
        
        if ( term_exists( $new_cat_name, 'catalog_category', $parent_id ) ) { wp_send_json_error( [ 'message' => 'Esta categoria já existe dentro da categoria pai.' ] ); }
        
        $args = [];
        if ($parent_id > 0) {
            $args['parent'] = $parent_id;
        }

        $result = wp_insert_term( $new_cat_name, 'catalog_category', $args );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        } else {
            $term = get_term( $result['term_id'], 'catalog_category' );
            $created_subcategories = [];

            if ( $create_subcategories && $subcategories_raw ) {
                $lines = preg_split( '/\r\n|\r|\n/', $subcategories_raw );
                $lines = is_array( $lines ) ? $lines : [];

                foreach ( $lines as $line ) {
                    $name = trim( $line );
                    if ( '' === $name ) {
                        continue;
                    }

                    // Evita duplicadas no mesmo nível
                    if ( term_exists( $name, 'catalog_category', (int) $term->term_id ) ) {
                        continue;
                    }

                    $child_result = wp_insert_term(
                        $name,
                        'catalog_category',
                        [
                            'parent' => (int) $term->term_id,
                        ]
                    );

                    if ( is_wp_error( $child_result ) ) {
                        continue;
                    }

                    $child_term = get_term( $child_result['term_id'], 'catalog_category' );
                    if ( $child_term && ! is_wp_error( $child_term ) ) {
                        $created_subcategories[] = [
                            'term_id' => (int) $child_term->term_id,
                            'name'    => (string) $child_term->name,
                        ];
                    }
                }
            }

            wp_send_json_success(
                [
                    'term_id'       => $term->term_id,
                    'name'          => $term->name,
                    'subcategories' => $created_subcategories,
                ]
            );
        }
        wp_die();
    }

    /**
     * Retorna filhos diretos (subcategorias) de uma categoria.
     * Usado pelo formulário upt quando subcategorias estão habilitadas.
     */
    public static function get_child_categories() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Falha de segurança.' ] );
        }
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => 'Você precisa estar logado.' ] );
        }

        $parent_id = isset( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0;
        if ( ! $parent_id ) {
            wp_send_json_success( [ 'items' => [] ] );
        }

        $terms = get_terms([
            'taxonomy'   => 'catalog_category',
            'hide_empty' => 0,
            'parent'     => $parent_id,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if ( is_wp_error( $terms ) ) {
            wp_send_json_error( [ 'message' => $terms->get_error_message() ] );
        }

        $items = [];
        foreach ( $terms as $t ) {
            $items[] = [
                'term_id' => (int) $t->term_id,
                'name'    => (string) $t->name,
                'parent'  => (int) $t->parent,
            ];
        }

        wp_send_json_success( [ 'items' => $items ] );
    }

    public static function public_get_child_categories() {
    if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'Falha de segurança.' ] );
    }

    $parent_id = isset( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0;
    if ( ! $parent_id ) {
        wp_send_json_success( [ 'items' => [] ] );
    }

    $terms = get_terms([
        'taxonomy'   => 'catalog_category',
        'hide_empty' => 0,
        'parent'     => $parent_id,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    if ( is_wp_error( $terms ) ) {
        wp_send_json_error( [ 'message' => $terms->get_error_message() ] );
    }

    $items = [];
    foreach ( $terms as $t ) {
        $items[] = [
            'term_id' => (int) $t->term_id,
            'name'    => (string) $t->name,
            'parent'  => (int) $t->parent,
        ];
    }

    wp_send_json_success( [ 'items' => $items ] );
}


    /**
     * Atualiza categoria/subcategoria: renomear e/ou trocar parent.
     * - new_name opcional
     * - new_parent_id opcional
     */
    public static function update_category() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Falha de segurança.' ] );
        }
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => 'Você precisa estar logado.' ] );
        }

        $term_id       = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
        $new_name      = isset( $_POST['new_name'] ) ? sanitize_text_field( wp_unslash( $_POST['new_name'] ) ) : '';
        $new_parent_id = isset( $_POST['new_parent_id'] ) ? absint( $_POST['new_parent_id'] ) : 0;

        if ( ! $term_id ) {
            wp_send_json_error( [ 'message' => 'ID inválido.' ] );
        }

        $term = get_term( $term_id, 'catalog_category' );
        if ( ! $term || is_wp_error( $term ) ) {
            wp_send_json_error( [ 'message' => 'Categoria não encontrada.' ] );
        }

        if ( ! current_user_can( 'edit_term', $term_id ) ) {
            wp_send_json_error( [ 'message' => 'Sem permissão para editar.' ] );
        }

        $target_parent = $new_parent_id ? $new_parent_id : (int) $term->parent;

        // Nome: se vazio, mantém
        $final_name = $new_name !== '' ? $new_name : (string) $term->name;

        // Evita duplicadas no mesmo nível
        $maybe_existing = term_exists( $final_name, 'catalog_category', $target_parent );
        if ( $maybe_existing && ! is_wp_error( $maybe_existing ) ) {
            $existing_id = is_array( $maybe_existing ) ? (int) $maybe_existing['term_id'] : (int) $maybe_existing;
            if ( $existing_id !== $term_id ) {
                wp_send_json_error( [ 'message' => 'Já existe uma categoria com esse nome neste nível.' ] );
            }
        }

        $update_args = [
            'name'   => $final_name,
            'slug'   => sanitize_title( $final_name ),
            'parent' => $target_parent,
        ];

        $result = wp_update_term( $term_id, 'catalog_category', $update_args );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        $updated = get_term( $term_id, 'catalog_category' );
        wp_send_json_success([
            'term_id' => (int) $term_id,
            'name'    => (string) $updated->name,
            'parent'  => (int) $updated->parent,
            'message' => 'Categoria atualizada com sucesso.',
        ]);
    }

    
    public static function rename_category() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Falha de segurança.' ] );
        }
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => 'Você precisa estar logado.' ] );
        }

        $term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
        $new_name = isset( $_POST['new_name'] ) ? sanitize_text_field( wp_unslash( $_POST['new_name'] ) ) : '';

        if ( ! $term_id || '' === $new_name ) {
            wp_send_json_error( [ 'message' => 'Dados insuficientes.' ] );
        }

        $term = get_term( $term_id, 'catalog_category' );
        if ( ! $term || is_wp_error( $term ) ) {
            wp_send_json_error( [ 'message' => 'Categoria não encontrada.' ] );
        }
        // Impede nomes duplicados no mesmo nível, exceto o próprio termo
        $maybe_existing = term_exists( $new_name, 'catalog_category', $term->parent );
        if ( $maybe_existing && ! is_wp_error( $maybe_existing ) ) {
            $existing_id = is_array( $maybe_existing ) ? (int) $maybe_existing['term_id'] : (int) $maybe_existing;
            if ( $existing_id !== $term_id ) {
                wp_send_json_error( [ 'message' => 'Já existe uma categoria com esse nome neste nível.' ] );
            }
        }

        $old_name = $term->name;
        $result   = wp_update_term(
            $term_id,
            'catalog_category',
            array(
                'name' => $new_name,
                'slug' => sanitize_title( $new_name ),
            )
        );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        // Atualiza referências salvas em conteúdos do Elementor para refletir o novo nome.
        self::replace_elementor_category_name_references( $old_name, $new_name );

        // Reconstrói dropdown de filtro para o esquema correspondente.
        $filter_html = '';
        $parent_id   = (int) $term->parent;
        if ( $parent_id > 0 ) {
            $category_args = [
                'taxonomy'         => 'catalog_category',
                'name'             => 'upt-category-filter',
                'class'            => 'upt-category-filter',
                'show_option_none' => 'Todas as categorias',
                'hierarchical'     => true,
                'hide_empty'       => 0,
                'echo'             => 0,
                'child_of'         => $parent_id,
                'selected'         => $term_id,
            ];
            $filter_html = wp_dropdown_categories( $category_args );
        }

        wp_send_json_success(
            array(
                'term_id' => $term_id,
                'name'    => $new_name,
                'message' => 'Categoria renomeada com sucesso.',
                'filter_html' => $filter_html,
            )
        );
    }

    public static function capture_category_old_name( $term_id, $tt_id, $taxonomy ) {
        if ( $taxonomy !== 'catalog_category' ) {
            return;
        }

        $term = get_term( $term_id, 'catalog_category' );
        if ( $term && ! is_wp_error( $term ) ) {
            self::$old_category_names[ $term_id ] = $term->name;
        }
    }

    public static function sync_elementor_category_name( $term_id, $tt_id ) {
        if ( empty( self::$old_category_names[ $term_id ] ) ) {
            return;
        }

        $new_term = get_term( $term_id, 'catalog_category' );
        if ( ! $new_term || is_wp_error( $new_term ) ) {
            unset( self::$old_category_names[ $term_id ] );
            return;
        }

        $old_name = self::$old_category_names[ $term_id ];
        unset( self::$old_category_names[ $term_id ] );

        if ( $old_name !== $new_term->name ) {
            self::replace_elementor_category_name_references( $old_name, $new_term->name );
        }
    }

    /**
     * Substitui ocorrências do nome da categoria em metadados do Elementor.
     * Atualiza _elementor_data e _elementor_template_data para evitar tags dinâmicas quebradas.
     */
    private static function replace_elementor_category_name_references( $old_name, $new_name ) {
        global $wpdb;

        $old_name = (string) $old_name;
        $new_name = (string) $new_name;

        if ( '' === $old_name || $old_name === $new_name ) {
            return;
        }

        $like_fragment = '%' . $wpdb->esc_like( $old_name ) . '%';
        $meta_keys     = [ '_elementor_data', '_elementor_template_data' ];

        foreach ( $meta_keys as $meta_key ) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key = %s AND meta_value LIKE %s",
                    $old_name,
                    $new_name,
                    $meta_key,
                    $like_fragment
                )
            );
        }

        // Também substitui no post_content de templates do Elementor.
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_type IN ('elementor_library', 'elementor') AND post_content LIKE %s",
                $old_name,
                $new_name,
                $like_fragment
            )
        );
    }

public static function delete_category() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) { wp_send_json_error( [ 'message' => 'Falha de segurança.' ] ); }
        if ( ! is_user_logged_in() ) { wp_send_json_error( [ 'message' => 'Você precisa estar logado.' ] ); }
        $term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
        if ( ! $term_id ) { wp_send_json_error( [ 'message' => 'ID da categoria não fornecido.' ] ); }

        $term = get_term($term_id, 'catalog_category');
        if (!$term || is_wp_error($term)) {
            wp_send_json_error( [ 'message' => 'Categoria não encontrada.' ] );
        }
        if ($term->count > 0 && !current_user_can('manage_options')) {
            wp_send_json_error( [ 'message' => 'Você não pode excluir categorias que contêm itens.' ] );
        }
        if ( ! current_user_can( 'delete_term', $term_id ) ) { wp_send_json_error( [ 'message' => 'Você não tem permissão para excluir esta categoria.' ] ); }
        
        $result = wp_delete_term( $term_id, 'catalog_category' );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        } else {
            $schema_slug = isset($_POST['schema_slug']) ? sanitize_text_field($_POST['schema_slug']) : '';
            $filter_html = '';
            if ($schema_slug) {
                $parent_cat_term = get_term_by('slug', $schema_slug, 'catalog_category');
                $parent_cat_id = ($parent_cat_term && !is_wp_error($parent_cat_term)) ? $parent_cat_term->term_id : 0;
                $category_args = [
                    'taxonomy'         => 'catalog_category',
                    'name'             => 'upt-category-filter',
                    'class'            => 'upt-category-filter',
                    'show_option_none' => 'Todas as categorias',
                    'hierarchical'     => true,
                    'hide_empty'       => 0,
                    'echo'             => 0,
                    'child_of'         => $parent_cat_id,
                ];
                $filter_html = wp_dropdown_categories($category_args);
            }
            wp_send_json_success( [ 'message' => 'Categoria excluída com sucesso.', 'filter_html' => $filter_html ] );
        }
        wp_die();
    }



    protected static function get_draft_meta_key( $schema_slug, $item_id = 0 ) {
        $schema_slug = sanitize_key( $schema_slug );
        $base = 'upt_form_draft_' . $schema_slug;
        if ( $item_id ) {
            $base .= '_item_' . absint( $item_id );
        } else {
            $base .= '_new';
        }
        return $base;
    }

    public static function save_draft() {
        if ( ! check_ajax_referer( 'upt_new_item', 'upt_submit_nonce', false ) ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Falha de segurança.', 'code' => 'invalid_nonce' ] );
        }

        if ( ! is_user_logged_in() ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Você precisa estar logado.', 'code' => 'not_logged_in' ] );
        }

        $user_id    = get_current_user_id();
        $schema_slug = isset( $_POST['schema_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_slug'] ) ) : '';
        $item_id     = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
        $fields      = isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? (array) $_POST['fields'] : [];

        if ( ! $schema_slug ) {
            wp_send_json_error( [ 'message' => 'Schema inválido.' ] );
        }

        $clean_fields = [];
        foreach ( $fields as $key => $value ) {
            $k = sanitize_key( $key );

            // Mantém HTML completo para campos de texto (incluindo WYSIWYG),
            // usando a mesma lógica de segurança do WordPress para posts.
            if ( is_array( $value ) ) {
                $clean_value = [];
                foreach ( $value as $sub_value ) {
                    $clean_value[] = wp_kses_post( wp_unslash( $sub_value ) );
                }
                $clean_fields[ $k ] = $clean_value;
            } else {
                $clean_fields[ $k ] = wp_kses_post( wp_unslash( $value ) );
            }
        }

        $draft_data = [
            'schema_slug' => $schema_slug,
            'item_id'     => $item_id,
            'fields'      => $clean_fields,
            'updated_at'  => current_time( 'mysql' ),
        ];

        $meta_key = self::get_draft_meta_key( $schema_slug, $item_id );
        update_user_meta( $user_id, $meta_key, $draft_data );

        wp_send_json_success( [ 'message' => 'Rascunho salvo.' ] );
    }

    public static function get_draft() {
        if ( ! check_ajax_referer( 'upt_new_item', 'upt_submit_nonce', false ) ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Falha de segurança.', 'code' => 'invalid_nonce' ] );
        }

        if ( ! is_user_logged_in() ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Você precisa estar logado.', 'code' => 'not_logged_in' ] );
        }

        $user_id     = get_current_user_id();
        $schema_slug = isset( $_POST['schema_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_slug'] ) ) : '';
        $item_id     = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;

        if ( ! $schema_slug ) {
            wp_send_json_error( [ 'message' => 'Schema inválido.' ] );
        }

        $meta_key = self::get_draft_meta_key( $schema_slug, $item_id );
        $draft    = get_user_meta( $user_id, $meta_key, true );

        if ( ! $draft || ! is_array( $draft ) || empty( $draft['fields'] ) ) {
            wp_send_json_success( [
                'has_draft' => false,
            ] );
        }

        wp_send_json_success( [
            'has_draft' => true,
            'fields'    => isset( $draft['fields'] ) && is_array( $draft['fields'] ) ? $draft['fields'] : [],
        ] );
    }

    public static function clear_draft() {
        if ( ! check_ajax_referer( 'upt_new_item', 'upt_submit_nonce', false ) ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Falha de segurança.', 'code' => 'invalid_nonce' ] );
        }

        if ( ! is_user_logged_in() ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Você precisa estar logado.', 'code' => 'not_logged_in' ] );
        }

        $user_id     = get_current_user_id();
        $schema_slug = isset( $_POST['schema_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_slug'] ) ) : '';
        $item_id     = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;

        if ( ! $schema_slug ) {
            wp_send_json_error( [ 'message' => 'Schema inválido.' ] );
        }

        $meta_key = self::get_draft_meta_key( $schema_slug, $item_id );
        delete_user_meta( $user_id, $meta_key );

        wp_send_json_success( [ 'message' => 'Rascunho removido.' ] );
    }


    public static function delete_item() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) { ob_clean(); wp_send_json_error(['message' => 'Falha de segurança.']); }
        if ( ! is_user_logged_in() ) { ob_clean(); wp_send_json_error(['message' => 'Você precisa estar logado.']); }
        $post_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;
        if (!$post_id) { ob_clean(); wp_send_json_error(['message' => 'ID do item não fornecido.']); }
        
        $post = get_post($post_id);
        if (!$post || ($post->post_author != get_current_user_id() && !current_user_can('manage_options'))) {
            ob_clean(); 
            wp_send_json_error(['message' => 'Permissão negada.']); 
        }

        // Enforce mínimo de itens por esquema (items_min salvo no Schema Store)
        $schema_terms = get_the_terms( $post_id, 'catalog_schema' );
        if ( ! is_wp_error( $schema_terms ) && ! empty( $schema_terms ) ) {
            $schema_slug = isset( $schema_terms[0]->slug ) ? (string) $schema_terms[0]->slug : '';
            if ( $schema_slug ) {
                $all_schemas = UPT_Schema_Store::get_schemas();
                $items_min   = isset( $all_schemas[ $schema_slug ]['items_min'] ) ? absint( $all_schemas[ $schema_slug ]['items_min'] ) : 0;
                $limit_per_category = ! empty( $all_schemas[ $schema_slug ]['items_limit_per_category'] );
                if ( $items_min > 0 ) {
                    $category_terms = get_the_terms( $post_id, 'catalog_category' );
                    if ( $limit_per_category && ! is_wp_error( $category_terms ) && ! empty( $category_terms ) ) {
                        foreach ( $category_terms as $category_term ) {
                            $count_query = new WP_Query([
                                'post_type'      => 'catalog_item',
                                'post_status'    => [ 'publish', 'pending', 'draft', 'private', 'future' ],
                                'posts_per_page' => 1,
                                'paged'          => 1,
                                'tax_query'      => [
                                    [
                                        'taxonomy' => 'catalog_schema',
                                        'field'    => 'slug',
                                        'terms'    => $schema_slug,
                                    ],
                                    [
                                        'taxonomy' => 'catalog_category',
                                        'field'    => 'term_id',
                                        'terms'    => (int) $category_term->term_id,
                                    ],
                                ],
                            ]);
                            $current_count = isset( $count_query->found_posts ) ? (int) $count_query->found_posts : 0;
                            if ( $current_count <= $items_min ) {
                                ob_clean();
                                wp_send_json_error([
                                    'message' => sprintf(
                                        'Não é possível apagar. A categoria %s exige no mínimo %d item(ns). Crie um novo item antes de apagar outro.',
                                        esc_html( $category_term->name ),
                                        $items_min
                                    ),
                                    'code'    => 'min_items_reached',
                                ]);
                            }
                        }
                    } else {
                        $count_query = new WP_Query([
                            'post_type'      => 'catalog_item',
                            'post_status'    => [ 'publish', 'pending', 'draft', 'private', 'future' ],
                            'posts_per_page' => 1,
                            'paged'          => 1,
                            'tax_query'      => [[
                                'taxonomy' => 'catalog_schema',
                                'field'    => 'slug',
                                'terms'    => $schema_slug,
                            ]],
                        ]);
                        $current_count = isset( $count_query->found_posts ) ? (int) $count_query->found_posts : 0;
                        if ( $current_count <= $items_min ) {
                            ob_clean();
                            wp_send_json_error([
                                'message' => 'Não é possível apagar. Este esquema exige no mínimo ' . $items_min . ' item(ns). Crie um novo item antes de apagar outro.',
                                'code'    => 'min_items_reached',
                            ]);
                        }
                    }
                }
            }
        }

        if (wp_delete_post($post_id, true)) {
            $show_all = isset($_POST['show_all']) && in_array($_POST['show_all'], ['true', 'yes', '1'], true);
            $active_schema_slug = isset($_POST['active_schema_slug']) ? sanitize_text_field($_POST['active_schema_slug']) : '';
            $active_category_id = isset($_POST['active_category_id']) ? absint($_POST['active_category_id']) : 0;
            $table_html_args = [];
            $table_html_args['posts_per_page'] = isset($_POST['per_page']) ? intval($_POST['per_page']) : -1;
            $table_html_args['paged'] = isset($_POST['paged']) ? absint($_POST['paged']) : 1;

            if (!$show_all || !current_user_can('manage_options')) {
                $table_html_args['author'] = get_current_user_id();
            }
            if (isset($_POST['template_id']) && !empty($_POST['template_id'])) {
                $table_html_args['template_id'] = absint($_POST['template_id']);
            }
            $card_variant = isset($_POST['card_variant']) ? sanitize_key($_POST['card_variant']) : 'modern';
            if ( ! in_array( $card_variant, [ 'modern', 'legacy' ], true ) ) {
                $card_variant = 'modern';
            }
            $table_html_args['card_variant'] = $card_variant;
            if (!empty($active_schema_slug) || $active_category_id > 0) {
                $table_html_args['tax_query'] = ['relation' => 'AND'];
                if (!empty($active_schema_slug)) {
                    $table_html_args['tax_query'][] = [
                        'taxonomy' => 'catalog_schema',
                        'field'    => 'slug',
                        'terms'    => $active_schema_slug,
                    ];
                }
                if ($active_category_id > 0) {
                    $table_html_args['tax_query'][] = [
                        'taxonomy' => 'catalog_category',
                        'field'    => 'term_id',
                        'terms'    => $active_category_id,
                    ];
                }
            }
            $table_data = self::get_items_list_html($table_html_args);

            ob_clean();
            wp_send_json_success(['message' => 'Item excluído!', 'html' => $table_data['html'], 'pagination_html' => $table_data['pagination_html']]);
        } else {
            ob_clean();
            wp_send_json_error(['message' => 'Erro ao excluir o item.']);
        }
        wp_die();
    }
    public static function bulk_delete_items() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) { ob_clean(); wp_send_json_error(['message' => 'Falha de segurança.']); }
        if ( ! is_user_logged_in() ) { ob_clean(); wp_send_json_error(['message' => 'Você precisa estar logado.']); }

        $raw_ids = isset($_POST['item_ids']) ? (array) $_POST['item_ids'] : [];
        $item_ids = [];
        foreach ( $raw_ids as $rid ) {
            $rid = absint( $rid );
            if ( $rid ) { $item_ids[] = $rid; }
        }
        $item_ids = array_values( array_unique( $item_ids ) );

        if ( empty( $item_ids ) ) { ob_clean(); wp_send_json_error(['message' => 'Nenhum item selecionado.']); }

        $deleted = [];
        $errors  = [];

        foreach ( $item_ids as $post_id ) {
            $post = get_post( $post_id );
            if ( ! $post ) {
                $errors[ (string) $post_id ] = 'Item não encontrado.';
                continue;
            }

            if ( $post->post_author != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
                $errors[ (string) $post_id ] = 'Permissão negada.';
                continue;
            }

            $min_msg = '';
            if ( ! self::can_delete_item_enforcing_min( $post_id, $min_msg ) ) {
                $errors[ (string) $post_id ] = $min_msg ? $min_msg : 'Não é possível apagar este item.';
                continue;
            }

            $deleted_post = wp_delete_post( $post_id, true );
            if ( $deleted_post ) {
                $deleted[] = $post_id;
            } else {
                $errors[ (string) $post_id ] = 'Falha ao apagar o item.';
            }
        }

        ob_clean();
        wp_send_json_success([
            'deleted' => $deleted,
            'errors'  => $errors,
        ]);
    }

    private static function can_delete_item_enforcing_min( $post_id, &$message = '' ) {
        $message = '';

        // Enforce mínimo de itens por esquema (items_min salvo no Schema Store)
        $schema_terms = get_the_terms( $post_id, 'catalog_schema' );
        if ( is_wp_error( $schema_terms ) || empty( $schema_terms ) ) {
            return true;
        }

        $schema_slug = isset( $schema_terms[0]->slug ) ? (string) $schema_terms[0]->slug : '';
        if ( ! $schema_slug ) {
            return true;
        }

        $all_schemas = UPT_Schema_Store::get_schemas();
        $items_min   = isset( $all_schemas[ $schema_slug ]['items_min'] ) ? absint( $all_schemas[ $schema_slug ]['items_min'] ) : 0;
        $limit_per_category = ! empty( $all_schemas[ $schema_slug ]['items_limit_per_category'] );

        if ( $items_min <= 0 ) {
            return true;
        }

        $category_terms = get_the_terms( $post_id, 'catalog_category' );

        if ( $limit_per_category && ! is_wp_error( $category_terms ) && ! empty( $category_terms ) ) {
            foreach ( $category_terms as $category_term ) {
                $count_query = new WP_Query([
                    'post_type'      => 'catalog_item',
                    'post_status'    => [ 'publish', 'pending', 'draft', 'private', 'future' ],
                    'posts_per_page' => 1,
                    'paged'          => 1,
                    'tax_query'      => [
                        [
                            'taxonomy' => 'catalog_schema',
                            'field'    => 'slug',
                            'terms'    => $schema_slug,
                        ],
                        [
                            'taxonomy' => 'catalog_category',
                            'field'    => 'term_id',
                            'terms'    => $category_term->term_id,
                        ],
                    ],
                    'fields' => 'ids',
                ]);

                $current_count = isset( $count_query->found_posts ) ? absint( $count_query->found_posts ) : 0;

                if ( $current_count <= $items_min ) {
                    $message = 'Não é possível apagar. Este esquema exige no mínimo ' . $items_min . ' item(ns) por categoria. Crie um novo item antes de apagar outro.';
                    return false;
                }
            }
            return true;
        }

        // mínimo por esquema (total)
        $count_query = new WP_Query([
            'post_type'      => 'catalog_item',
            'post_status'    => [ 'publish', 'pending', 'draft', 'private', 'future' ],
            'posts_per_page' => 1,
            'paged'          => 1,
            'tax_query'      => [
                [
                    'taxonomy' => 'catalog_schema',
                    'field'    => 'slug',
                    'terms'    => $schema_slug,
                ],
            ],
            'fields' => 'ids',
        ]);

        $current_count = isset( $count_query->found_posts ) ? absint( $count_query->found_posts ) : 0;

        if ( $current_count <= $items_min ) {
            $message = 'Não é possível apagar. Este esquema exige no mínimo ' . $items_min . ' item(ns). Crie um novo item antes de apagar outro.';
            return false;
        }

        return true;
    }



    public static function get_schema_counts() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) { ob_clean(); wp_send_json_error( [ 'message' => 'Falha de segurança.' ] ); }
        if ( ! is_user_logged_in() ) { ob_clean(); wp_send_json_error( [ 'message' => 'Você precisa estar logado.' ] ); }

        $show_all = isset( $_POST['show_all'] ) && in_array( wp_unslash( $_POST['show_all'] ), [ 'true', 'yes', '1' ], true );

        $schemas = get_terms( [
            'taxonomy'   => 'catalog_schema',
            'hide_empty' => false,
        ] );

        if ( is_wp_error( $schemas ) ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Erro ao carregar schemas.' ] );
        }

        $schema_counts = [];
        $base_query    = [
            'post_type'              => 'catalog_item',
            'post_status'            => [ 'publish', 'pending', 'draft' ],
            'fields'                 => 'ids',
            'posts_per_page'         => 1,
            'no_found_rows'          => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ];

        if ( ! $show_all || ! current_user_can( 'manage_options' ) ) {
            $base_query['author'] = get_current_user_id();
        }

        if ( ! empty( $schemas ) ) {
            foreach ( $schemas as $schema ) {
                $args             = $base_query;
                $args['tax_query'] = [
                    [
                        'taxonomy' => 'catalog_schema',
                        'field'    => 'slug',
                        'terms'    => $schema->slug,
                    ],
                ];

                $count_query               = new WP_Query( $args );
                $schema_counts[ $schema->slug ] = (int) $count_query->found_posts;
            }
        }

        wp_reset_postdata();

        $forms_count = 0;
        if ( post_type_exists( '4gt_form_submission' ) ) {
            $forms_query_args = [
                'post_type'              => '4gt_form_submission',
                'post_status'            => [ 'publish', 'pending', 'draft' ],
                'fields'                 => 'ids',
                'posts_per_page'         => 1,
                'no_found_rows'          => false,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ];

            if ( ! $show_all || ! current_user_can( 'manage_options' ) ) {
                $forms_query_args['author'] = get_current_user_id();
            }

            $forms_query = new WP_Query( $forms_query_args );
            $forms_count = (int) $forms_query->found_posts;
            wp_reset_postdata();
        }

        ob_clean();
        wp_send_json_success(
            [
                'schemas' => $schema_counts,
                'forms'   => $forms_count,
            ]
        );
    }

    public static function reorder_fields() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Permissão negada.' ] );
        }

        $schema_slug = isset( $_POST['schema_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_slug'] ) ) : '';
        $order       = isset( $_POST['order'] ) ? (array) $_POST['order'] : [];

        if ( empty( $schema_slug ) || empty( $order ) ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Dados inválidos.' ] );
        }

        $order = array_map( 'sanitize_text_field', $order );

        $all_schemas = UPT_Schema_Store::get_schemas();

        if ( ! isset( $all_schemas[ $schema_slug ]['fields'] ) || ! is_array( $all_schemas[ $schema_slug ]['fields'] ) ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Esquema não encontrado.' ] );
        }

        $fields       = $all_schemas[ $schema_slug ]['fields'];
        $fields_by_id = [];

        foreach ( $fields as $field ) {
            if ( isset( $field['id'] ) ) {
                $fields_by_id[ $field['id'] ] = $field;
            }
        }

        $new_fields = [];

        foreach ( $order as $field_id ) {
            if ( isset( $fields_by_id[ $field_id ] ) ) {
                $new_fields[] = $fields_by_id[ $field_id ];
                unset( $fields_by_id[ $field_id ] );
            }
        }

        // Adiciona campos que, por algum motivo, não vieram no array de ordem
        if ( ! empty( $fields_by_id ) ) {
            foreach ( $fields as $field ) {
                if ( isset( $field['id'] ) && isset( $fields_by_id[ $field['id'] ] ) ) {
                    $new_fields[] = $field;
                }
            }
        }

        $all_schemas[ $schema_slug ]['fields'] = $new_fields;

        if ( ! UPT_Schema_Store::save_schemas( $all_schemas ) ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Erro ao salvar nova ordem.' ] );
        }

        ob_clean();
        wp_send_json_success( [ 'message' => 'Ordem atualizada.' ] );
    }

    public static function reorder_items() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Permissão negada.' ] );
        }

        $schema_slug = isset( $_POST['schema_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_slug'] ) ) : '';
        $order       = isset( $_POST['order'] ) ? (array) $_POST['order'] : [];

        $order = array_values( array_unique( array_filter( array_map( 'absint', $order ) ) ) );

        if ( empty( $schema_slug ) || empty( $order ) ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Dados inválidos.' ] );
        }

        $schema_term = get_term_by( 'slug', $schema_slug, 'catalog_schema' );
        if ( ! $schema_term || is_wp_error( $schema_term ) ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Esquema não encontrado.' ] );
        }

        $manual_order_enabled = self::is_manual_order_enabled( $schema_slug );

        if ( ! $manual_order_enabled ) {
            update_term_meta( (int) $schema_term->term_id, 'upt_manual_order_enabled', 1 );
            $manual_order_enabled = true;
        }

        if ( $manual_order_enabled ) {
            self::ensure_manual_order_meta( $schema_slug );
        }

        $schema_query_args = [
            'post_type'      => 'catalog_item',
            'post_status'    => [ 'publish', 'pending', 'draft' ],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => [
                [
                    'taxonomy' => 'catalog_schema',
                    'field'    => 'slug',
                    'terms'    => $schema_slug,
                ],
            ],
        ];

        if ( $manual_order_enabled ) {
            $schema_query_args['meta_key'] = 'upt_manual_order';
            $schema_query_args['orderby']  = [
                'meta_value_num' => 'ASC',
                'date'           => 'DESC',
            ];
        } else {
            $schema_query_args['orderby'] = 'date';
            $schema_query_args['order']   = 'DESC';
        }

        $schema_query = new WP_Query( $schema_query_args );

        $schema_item_ids = $schema_query->posts;
        wp_reset_postdata();

        if ( empty( $schema_item_ids ) ) {
            ob_clean();
            wp_send_json_error( [ 'message' => 'Nenhum item encontrado para este esquema.' ] );
        }

        foreach ( $order as $item_id ) {
            if ( ! in_array( $item_id, $schema_item_ids, true ) ) {
                ob_clean();
                wp_send_json_error( [ 'message' => 'O item informado não pertence a este esquema.' ] );
            }
        }

        $remaining_ids = array_values( array_diff( $schema_item_ids, $order ) );
        $final_ids     = array_merge( $order, $remaining_ids );

        $current_positions = [];
        foreach ( $schema_item_ids as $item_id ) {
            $current_positions[ $item_id ] = (int) get_post_meta( $item_id, 'upt_manual_order', true );
        }

        foreach ( $final_ids as $index => $item_id ) {
            $new_position = $index + 1;
            update_post_meta( $item_id, 'upt_manual_order', $new_position );

            $current_position = isset( $current_positions[ $item_id ] ) ? (int) $current_positions[ $item_id ] : 0;
            if ( $current_position !== $new_position ) {
                wp_update_post( [
                    'ID'         => $item_id,
                    'menu_order' => $new_position,
                ] );
            }
        }

        ob_clean();
        wp_send_json_success( [ 'message' => 'Ordem dos itens atualizada.' ] );
    }

    /**
     * Retorna se a ordenação manual está habilitada para um esquema.
     * A ordenação só deve ser ativada quando o usuário reordenar via drag & drop.
     */
    private static function is_manual_order_enabled( $schema_slug ) {
        $schema_slug = sanitize_title( $schema_slug );
        if ( ! $schema_slug ) {
            return false;
        }

        $schema_term = get_term_by( 'slug', $schema_slug, 'catalog_schema' );
        if ( ! $schema_term || is_wp_error( $schema_term ) ) {
            return false;
        }

        $enabled = get_term_meta( (int) $schema_term->term_id, 'upt_manual_order_enabled', true );
        return (string) $enabled === '1' || $enabled === 1 || $enabled === true;
    }

    /**
     * Garante que todos os itens de um esquema possuam a meta upt_manual_order.
     * Itens sem meta recebem uma ordem sequencial após a última posição existente,
     * baseada na data (mais recentes primeiro).
     */
    private static function ensure_manual_order_meta( $schema_slug ) {
        $schema_slug = sanitize_title( $schema_slug );
        if ( ! $schema_slug ) {
            return;
        }

        // Maior ordem já existente
        $existing_query = new WP_Query( [
            'post_type'      => 'catalog_item',
            'post_status'    => [ 'publish', 'pending', 'draft' ],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'tax_query'      => [
                [
                    'taxonomy' => 'catalog_schema',
                    'field'    => 'slug',
                    'terms'    => $schema_slug,
                ],
            ],
            'meta_key'       => 'upt_manual_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
        ] );

        $max_order = 0;
        if ( $existing_query->have_posts() ) {
            $max_order = (int) get_post_meta( $existing_query->posts[0], 'upt_manual_order', true );
        }
        wp_reset_postdata();

        // Itens sem meta de ordem
        $missing_query = new WP_Query( [
            'post_type'      => 'catalog_item',
            'post_status'    => [ 'publish', 'pending', 'draft' ],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => [
                [
                    'taxonomy' => 'catalog_schema',
                    'field'    => 'slug',
                    'terms'    => $schema_slug,
                ],
            ],
            'meta_query'     => [
                [
                    'key'     => 'upt_manual_order',
                    'compare' => 'NOT EXISTS',
                ],
            ],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );

        if ( $missing_query->have_posts() ) {
            $position = $max_order;
            foreach ( $missing_query->posts as $post_id ) {
                $position++;
                update_post_meta( $post_id, 'upt_manual_order', $position );
            }
        }
        wp_reset_postdata();
    }

}
