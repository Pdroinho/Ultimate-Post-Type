<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Auth {
    public static function init() {
        add_action( 'init', [ self::class, 'handle_login' ] );
    }

    public static function handle_login() {
        if ( isset( $_POST['upt_login_nonce'] ) && wp_verify_nonce( $_POST['upt_login_nonce'], 'upt_login' ) ) {
            $creds = [
                'user_login'    => sanitize_text_field( $_POST['log'] ),
                'user_password' => $_POST['pwd'],
                'remember'      => isset( $_POST['rememberme'] ),
            ];
            
            $user = wp_signon( $creds, '' );

            if ( is_wp_error( $user ) ) {
                $login_url = home_url( $_POST['redirect_to'] );
                $login_url = add_query_arg( 'login_error', '1', $login_url );
                wp_redirect( $login_url );
                exit;
            } else {
                wp_set_current_user( $user->ID );
                
                $redirect_url = home_url( $_POST['redirect_to'] );
                // Adiciona um parâmetro único para evitar o cache do navegador
                $redirect_url = add_query_arg( 'loggedin', time(), $redirect_url );
                $redirect_url = add_query_arg( 'upt_alert', 'login_success', $redirect_url );

                wp_redirect( $redirect_url );
                exit;
            }
        }
    }

    public static function get_template_part( $slug ) {
        $template = UPT_PLUGIN_DIR . "templates/{$slug}.php";
        if ( file_exists( $template ) ) {
            ob_start();
            include $template;
            return ob_get_clean();
        }
        return '';
    }
}
