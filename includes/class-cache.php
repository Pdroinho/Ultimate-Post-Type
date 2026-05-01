<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Cache {

    private static $did_purge = false;
    private static $log_message = '';

    public static function init() {
        add_action( 'shutdown', [ self::class, 'maybe_purge' ] );
        add_action( 'admin_footer', [ self::class, 'print_console_log' ] );
        add_action( 'wp_footer', [ self::class, 'print_console_log' ] );
    }

    public static function purge_all( $reason = '' ) {
        if ( self::$did_purge ) {
            return;
        }

        self::$did_purge = true;
        self::$log_message = $reason ? 'upt cache purged: ' . $reason : 'upt cache purged.';

        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
        }

        if ( class_exists( 'LiteSpeed_Cache_API' ) && method_exists( 'LiteSpeed_Cache_API', 'purge_all' ) ) {
            \LiteSpeed_Cache_API::purge_all();
            return;
        }

        if ( function_exists( 'litespeed_purge_all' ) ) {
            litespeed_purge_all();
            return;
        }

        if ( function_exists( 'do_action' ) ) {
            do_action( 'litespeed_purge_all' );
        }
    }

    public static function maybe_purge() {
        if ( self::$did_purge ) {
            return;
        }

        if ( ! self::is_upt_request() ) {
            return;
        }

        self::purge_all();
    }

    private static function is_upt_request() {
        $action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
        if ( strpos( $action, 'upt_' ) === 0 ) {
            return true;
        }

        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( strpos( $page, 'upt_' ) === 0 ) {
            return true;
        }

        $post_type = isset( $_REQUEST['post_type'] ) ? sanitize_key( wp_unslash( $_REQUEST['post_type'] ) ) : '';
        if ( $post_type === 'catalog_item' ) {
            return true;
        }

        $taxonomy = isset( $_REQUEST['taxonomy'] ) ? sanitize_key( wp_unslash( $_REQUEST['taxonomy'] ) ) : '';
        if ( in_array( $taxonomy, [ 'catalog_schema', 'catalog_category', 'media_folder' ], true ) ) {
            return true;
        }

        return false;
    }

    public static function print_console_log() {
        if ( wp_doing_ajax() || ! self::$did_purge || ( defined( 'WP_DEBUG' ) && ! WP_DEBUG ) ) {
            return;
        }

        $message = self::$log_message ? self::$log_message : 'upt cache purged.';
        echo '<script>console.log(' . wp_json_encode( $message ) . ');</script>';
    }
}
