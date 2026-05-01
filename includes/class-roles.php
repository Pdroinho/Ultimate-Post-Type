<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Roles {
    public static function add_roles() {
        add_role( 'catalog_client', 'Cliente do Catálogo', [ 'read' => true, 'upload_files' => true, 'edit_posts' => true, 'delete_posts' => true ] );
    }
    public static function remove_roles() {
        remove_role( 'catalog_client' );
    }
}
