<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Shortcodes {

    public static function init() {
        add_shortcode( 'upt_dashboard', [ self::class, 'render_dashboard' ] );
    }

    public static function render_dashboard( $atts = [] ) {
        $settings = [
            'alert_badge_enabled'       => 'yes',
            'alert_on_create'           => 'yes',
            'alert_on_edit'             => 'yes',
            'alert_on_delete'           => 'yes',
            'alert_on_draft'            => 'yes',
            'alert_on_login'            => 'yes',
            'alert_on_schema_qty'       => 'yes',
            'alert_on_media_deleted'    => 'yes',
            'alert_on_media_uploaded'   => 'yes',
            'alert_on_media_moved'      => 'yes',
            'alert_on_category_created' => 'yes',
            'alert_on_category_renamed' => 'yes',
            'alert_on_category_deleted' => 'yes',
            'alert_badge_default_text'  => 'Você tem novas notificações do upt',
            'alert_badge_duration'      => 4,
        ];

        wp_enqueue_media();

        set_query_var( 'widget_settings', $settings );

        ob_start();
        echo '<div class="upt-dashboard-wrapper"'
            . ' data-upt-alert-enabled="1"'
            . ' data-upt-alert-create="1"'
            . ' data-upt-alert-edit="1"'
            . ' data-upt-alert-delete="1"'
            . ' data-upt-alert-draft="1"'
            . ' data-upt-alert-login="1"'
            . ' data-upt-alert-schema-qty="1"'
            . ' data-upt-alert-media-deleted="1"'
            . ' data-upt-alert-media-uploaded="1"'
            . ' data-upt-alert-media-moved="1"'
            . ' data-upt-alert-category-created="1"'
            . ' data-upt-alert-category-renamed="1"'
            . ' data-upt-alert-category-deleted="1"'
            . ' data-upt-alert-text="' . esc_attr( $settings['alert_badge_default_text'] ) . '"'
            . ' data-upt-alert-duration="' . esc_attr( (float) $settings['alert_badge_duration'] ) . '"'
            . '>';
            echo '<div class="upt-alert-host" aria-live="polite" aria-atomic="true"></div>';
            echo '<div class="upt-preset-hostinger">';
                if ( ! is_user_logged_in() ) {
                    include UPT_PLUGIN_DIR . 'templates/front-form-login.php';
                } else {
                    include UPT_PLUGIN_DIR . 'templates/dashboard-main.php';
                }
            echo '</div>';
        echo '</div>';
        return ob_get_clean();
    }

    public static function maybe_create_dashboard_page() {
        $existing = get_page_by_title( 'Painel', OBJECT, 'page' );
        if ( $existing && isset( $existing->ID ) && $existing->post_status !== 'trash' ) {
            return;
        }

        $page_data = [
            'post_title'   => 'Painel',
            'post_name'    => sanitize_title( 'Painel' ),
            'post_content' => '[upt_dashboard]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => get_current_user_id() ?: 1,
        ];

        wp_insert_post( $page_data );
    }
}
