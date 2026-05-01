<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Premium_Card_Actions {

    public static function register() {
        add_action( 'wp_ajax_upt_toggle_favorite', [ __CLASS__, 'toggle_favorite' ] );
        add_action( 'wp_ajax_upt_duplicate_item', [ __CLASS__, 'duplicate_item' ] );
        add_action( 'wp_ajax_upt_toggle_item_status', [ __CLASS__, 'toggle_item_status' ] );
    }

    public static function toggle_favorite() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) { wp_send_json_error( [ 'message' => 'Nonce inválido.' ] ); }
        if ( ! is_user_logged_in() ) { wp_send_json_error( [ 'message' => 'Você precisa estar logado.' ] ); }

        $post_id = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
        if ( ! $post_id ) { wp_send_json_error( [ 'message' => 'ID inválido.' ] ); }

        $post = get_post( $post_id );
        if ( ! $post || ( $post->post_author != get_current_user_id() && ! current_user_can( 'manage_options' ) ) ) {
            wp_send_json_error( [ 'message' => 'Permissão negada.' ] );
        }

        $user_id = get_current_user_id();
        $fav_key = 'upt_favorites';
        $favs = get_user_meta( $user_id, $fav_key, true );
        if ( ! is_array( $favs ) ) { $favs = []; }

        $idx = array_search( $post_id, $favs, true );
        if ( $idx !== false ) {
            unset( $favs[ $idx ] );
            $favs = array_values( $favs );
            $favorited = false;
        } else {
            $favs[] = $post_id;
            $favorited = true;
        }

        update_user_meta( $user_id, $fav_key, $favs );
        wp_send_json_success( [ 'favorited' => $favorited ] );
    }

    public static function duplicate_item() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) { wp_send_json_error( [ 'message' => 'Nonce inválido.' ] ); }
        if ( ! is_user_logged_in() ) { wp_send_json_error( [ 'message' => 'Você precisa estar logado.' ] ); }

        $post_id = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
        if ( ! $post_id ) { wp_send_json_error( [ 'message' => 'ID inválido.' ] ); }

        $post = get_post( $post_id );
        if ( ! $post || ( $post->post_author != get_current_user_id() && ! current_user_can( 'manage_options' ) ) ) {
            wp_send_json_error( [ 'message' => 'Permissão negada.' ] );
        }

        $new_post = [
            'post_title'  => 'Cópia de ' . $post->post_title,
            'post_status' => 'draft',
            'post_type'   => $post->post_type,
            'post_author' => get_current_user_id(),
        ];

        $new_id = wp_insert_post( $new_post );
        if ( is_wp_error( $new_id ) ) {
            wp_send_json_error( [ 'message' => 'Falha ao duplicar item.' ] );
        }

        $meta_keys = get_post_custom_keys( $post_id );
        if ( ! empty( $meta_keys ) ) {
            foreach ( $meta_keys as $key ) {
                if ( 0 === strpos( $key, '_' ) ) { continue; }
                $values = get_post_custom_values( $key, $post_id );
                if ( ! empty( $values ) ) {
                    foreach ( $values as $value ) {
                        add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
                    }
                }
            }
        }

        $taxonomies = get_object_taxonomies( $post->post_type );
        if ( ! empty( $taxonomies ) ) {
            foreach ( $taxonomies as $tax ) {
                $terms = wp_get_object_terms( $post_id, $tax, [ 'fields' => 'ids' ] );
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                    wp_set_object_terms( $new_id, $terms, $tax );
                }
            }
        }

        $thumb_id = get_post_thumbnail_id( $post_id );
        if ( $thumb_id ) {
            set_post_thumbnail( $new_id, $thumb_id );
        }

        wp_send_json_success( [ 'message' => 'Item duplicado com sucesso!', 'new_id' => $new_id ] );
    }

    public static function toggle_item_status() {
        if ( ! check_ajax_referer( 'upt_ajax_nonce', 'nonce', false ) ) { wp_send_json_error( [ 'message' => 'Nonce inválido.' ] ); }
        if ( ! is_user_logged_in() ) { wp_send_json_error( [ 'message' => 'Você precisa estar logado.' ] ); }

        $post_id = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
        $new_status = isset( $_POST['new_status'] ) ? sanitize_key( $_POST['new_status'] ) : '';
        if ( ! $post_id || ! $new_status ) { wp_send_json_error( [ 'message' => 'Parâmetros inválidos.' ] ); }

        $post = get_post( $post_id );
        if ( ! $post || ( $post->post_author != get_current_user_id() && ! current_user_can( 'manage_options' ) ) ) {
            wp_send_json_error( [ 'message' => 'Permissão negada.' ] );
        }

        $allowed = [ 'publish', 'draft', 'pending', 'private' ];
        if ( ! in_array( $new_status, $allowed, true ) ) {
            wp_send_json_error( [ 'message' => 'Status inválido.' ] );
        }

        wp_update_post( [
            'ID'          => $post_id,
            'post_status' => $new_status,
        ] );

        $status_obj = get_post_status_object( $new_status );
        $label = $status_obj && ! empty( $status_obj->label ) ? $status_obj->label : $new_status;

        wp_send_json_success( [ 'message' => 'Status alterado para ' . $label . '.', 'new_status' => $new_status ] );
    }
}
