<?php

namespace {

class WP_Error {
    public function get_error_message() {
        return '';
    }
}

function is_wp_error( $thing ) {
    return $thing instanceof WP_Error;
}

class wpdb {
    public $posts = 'wp_posts';
    public $postmeta = 'wp_postmeta';
    public $term_relationships = 'wp_term_relationships';
    public $term_taxonomy = 'wp_term_taxonomy';
    public $terms = 'wp_terms';

    public function prepare( $query, ...$args ) {
        return $query;
    }

    public function esc_like( $text ) {
        return $text;
    }

    public function query( $query ) {
        return 0;
    }
}

class WP_Query {
    public $posts = [];
    public $found_posts = 0;
    public $max_num_pages = 0;

    public function __construct( $args = [] ) {
    }

    public function have_posts() {
        return false;
    }

    public function the_post() {
        return null;
    }

    public function get( $key ) {
        return null;
    }
}

function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
    return null;
}

function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
    return null;
}

function remove_filter( $hook_name, $callback, $priority = 10 ) {
    return true;
}

function apply_filters( $hook_name, $value, ...$args ) {
    return $value;
}

function do_action( $hook_name, ...$args ) {
    return null;
}

function check_ajax_referer( $action = -1, $query_arg = false, $die = true ) {
    return true;
}

function wp_send_json_success( $data = null, $status_code = null ) {
    return null;
}

function wp_send_json_error( $data = null, $status_code = null ) {
    return null;
}

function __( $text, $domain = null ) {
    return is_string( $text ) ? $text : '';
}

function wp_die( $message = '', $title = '', $args = [] ) {
    return null;
}

function wp_parse_args( $args, $defaults = [] ) {
    if ( is_array( $args ) ) {
        return array_merge( $defaults, $args );
    }
    return $defaults;
}

function sanitize_text_field( $str ) {
    return is_string( $str ) ? $str : '';
}

function sanitize_textarea_field( $str ) {
    return is_string( $str ) ? $str : '';
}

function sanitize_title( $title ) {
    return is_string( $title ) ? $title : '';
}

function sanitize_key( $key ) {
    return is_string( $key ) ? $key : '';
}

function sanitize_html_class( $class, $fallback = '' ) {
    return is_string( $class ) ? $class : $fallback;
}

function wp_unslash( $value ) {
    return $value;
}

function esc_html( $text ) {
    return is_string( $text ) ? $text : '';
}

function esc_attr( $text ) {
    return is_string( $text ) ? $text : '';
}

function esc_url( $url ) {
    return is_string( $url ) ? $url : '';
}

function esc_url_raw( $url ) {
    return is_string( $url ) ? $url : '';
}

function wp_strip_all_tags( $text, $remove_breaks = false ) {
    return is_string( $text ) ? $text : '';
}

function remove_accents( $string ) {
    return is_string( $string ) ? $string : '';
}

function absint( $maybeint ) {
    return (int) abs( (int) $maybeint );
}

function is_user_logged_in() {
    return true;
}

function current_user_can( $capability, ...$args ) {
    return true;
}

function get_current_user_id() {
    return 0;
}

function post_type_exists( $post_type ) {
    return true;
}

function taxonomy_exists( $taxonomy ) {
    return true;
}

function term_exists( $term, $taxonomy = '', $parent_term = null ) {
    return false;
}

function get_ancestors( $object_id = 0, $object_type = '', $resource_type = '' ) {
    return [];
}

function get_terms( $args = [], $deprecated = '' ) {
    return [];
}

function get_term( $term, $taxonomy = '', $output = 'OBJECT', $filter = 'raw' ) {
    return (object) [
        'term_id' => 0,
        'name'    => '',
        'slug'    => '',
        'count'   => 0,
        'parent'  => 0,
    ];
}

function get_term_by( $field, $value, $taxonomy = '', $output = 'OBJECT', $filter = 'raw' ) {
    return (object) [
        'term_id' => 0,
        'name'    => '',
        'slug'    => '',
        'count'   => 0,
        'parent'  => 0,
    ];
}

function get_term_field( $field, $term, $taxonomy = '', $context = 'display' ) {
    return '';
}

function wp_dropdown_categories( $args = '' ) {
    return '';
}

if ( ! function_exists( 'get_post' ) ) {
    function get_post( $post = null, $output = 'OBJECT', $filter = 'raw' ) {
        return null;
    }
}

if ( ! function_exists( 'get_post_meta' ) ) {
    function get_post_meta( $post_id, $key = '', $single = false ) {
        return $single ? '' : [];
    }
}

if ( ! function_exists( 'update_post_meta' ) ) {
    function update_post_meta( $post_id, $meta_key, $meta_value, $prev_value = '' ) {
        return true;
    }
}

if ( ! function_exists( 'delete_post_meta' ) ) {
    function delete_post_meta( $post_id, $meta_key, $meta_value = '' ) {
        return true;
    }
}

if ( ! function_exists( 'wp_insert_post' ) ) {
    function wp_insert_post( $postarr, $wp_error = false, $fire_after_hooks = true ) {
        return 0;
    }
}

if ( ! function_exists( 'wp_update_post' ) ) {
    function wp_update_post( $postarr = [], $wp_error = false, $fire_after_hooks = true ) {
        return 0;
    }
}

if ( ! function_exists( 'wp_delete_post' ) ) {
    function wp_delete_post( $post_id, $force_delete = false ) {
        return null;
    }
}

if ( ! function_exists( 'get_post_field' ) ) {
    function get_post_field( $field, $post = null, $context = 'display' ) {
        return '';
    }
}

if ( ! function_exists( 'get_the_ID' ) ) {
    function get_the_ID() {
        return 0;
    }
}

if ( ! function_exists( 'get_the_title' ) ) {
    function get_the_title( $post = 0 ) {
        return '';
    }
}

if ( ! function_exists( 'the_title' ) ) {
    function the_title( $before = '', $after = '', $echo = true ) {
        return '';
    }
}

if ( ! function_exists( 'get_post_type' ) ) {
    function get_post_type( $post = null ) {
        return '';
    }
}

if ( ! function_exists( 'get_post_status' ) ) {
    function get_post_status( $post = null ) {
        return '';
    }
}

if ( ! function_exists( 'get_post_status_object' ) ) {
    function get_post_status_object( $post_status ) {
        return (object) [ 'label' => '' ];
    }
}

if ( ! function_exists( 'get_the_terms' ) ) {
    function get_the_terms( $post, $taxonomy ) {
        return [];
    }
}

if ( ! function_exists( 'wp_set_object_terms' ) ) {
    function wp_set_object_terms( $object_id, $terms, $taxonomy, $append = false ) {
        return [];
    }
}

if ( ! function_exists( 'wp_remove_object_terms' ) ) {
    function wp_remove_object_terms( $object_id, $terms, $taxonomy ) {
        return true;
    }
}

if ( ! function_exists( 'wp_delete_term' ) ) {
    function wp_delete_term( $term, $taxonomy, $args = [] ) {
        return new WP_Error();
    }
}

if ( ! function_exists( 'wp_insert_term' ) ) {
    function wp_insert_term( $term, $taxonomy, $args = [] ) {
        return new WP_Error();
    }
}

if ( ! function_exists( 'wp_update_term' ) ) {
    function wp_update_term( $term_id, $taxonomy, $args = [] ) {
        return new WP_Error();
    }
}

if ( ! function_exists( 'has_post_thumbnail' ) ) {
    function has_post_thumbnail( $post = null ) {
        return false;
    }
}

if ( ! function_exists( 'get_post_thumbnail_id' ) ) {
    function get_post_thumbnail_id( $post = null ) {
        return 0;
    }
}

if ( ! function_exists( 'set_post_thumbnail' ) ) {
    function set_post_thumbnail( $post, $thumbnail_id ) {
        return true;
    }
}

if ( ! function_exists( 'delete_post_thumbnail' ) ) {
    function delete_post_thumbnail( $post ) {
        return true;
    }
}

if ( ! function_exists( 'the_post_thumbnail' ) ) {
    function the_post_thumbnail( $size = 'post-thumbnail', $attr = '' ) {
        return null;
    }
}

if ( ! function_exists( 'get_the_post_thumbnail_url' ) ) {
    function get_the_post_thumbnail_url( $post = null, $size = 'post-thumbnail' ) {
        return '';
    }
}

if ( ! function_exists( 'wp_get_attachment_image' ) ) {
    function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = '' ) {
        return '';
    }
}

if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
    function wp_get_attachment_image_url( $attachment_id, $size = 'thumbnail', $icon = false ) {
        return '';
    }
}

if ( ! function_exists( 'wp_get_attachment_thumb_url' ) ) {
    function wp_get_attachment_thumb_url( $attachment_id ) {
        return '';
    }
}

if ( ! function_exists( 'wp_get_attachment_url' ) ) {
    function wp_get_attachment_url( $attachment_id ) {
        return '';
    }
}

if ( ! function_exists( 'wp_delete_attachment' ) ) {
    function wp_delete_attachment( $post_id, $force_delete = false ) {
        return new WP_Error();
    }
}

if ( ! function_exists( 'wp_mime_type_icon' ) ) {
    function wp_mime_type_icon( $mime = '' ) {
        return '';
    }
}

if ( ! function_exists( 'get_post_mime_type' ) ) {
    function get_post_mime_type( $post = null ) {
        return '';
    }
}

if ( ! function_exists( 'get_attached_file' ) ) {
    function get_attached_file( $attachment_id, $unfiltered = false ) {
        return '';
    }
}

if ( ! function_exists( 'size_format' ) ) {
    function size_format( $bytes, $decimals = 0 ) {
        return '';
    }
}

if ( ! function_exists( 'paginate_links' ) ) {
    function paginate_links( $args = '' ) {
        return '';
    }
}

function get_query_var( $query_var, $default_value = '' ) {
    return $default_value;
}

function wp_add_inline_style( $handle, $data ) {
    return true;
}

function get_permalink( $post = 0, $leavename = false ) {
    return '';
}

function get_the_post_thumbnail( $post = null, $size = 'post-thumbnail', $attr = '' ) {
    return '';
}

function wp_list_pluck( $list, $field, $index_key = null ) {
    $result = [];

    if ( ! is_array( $list ) ) {
        return $result;
    }

    foreach ( $list as $item ) {
        if ( is_object( $item ) && isset( $item->{$field} ) ) {
            $value = $item->{$field};
        } elseif ( is_array( $item ) && array_key_exists( $field, $item ) ) {
            $value = $item[ $field ];
        } else {
            continue;
        }

        if ( $index_key !== null ) {
            if ( is_object( $item ) && isset( $item->{$index_key} ) ) {
                $result[ $item->{$index_key} ] = $value;
            } elseif ( is_array( $item ) && array_key_exists( $index_key, $item ) ) {
                $result[ $item[ $index_key ] ] = $value;
            }
        } else {
            $result[] = $value;
        }
    }

    return $result;
}

if ( ! function_exists( 'add_query_arg' ) ) {
    function add_query_arg( ...$args ) {
        return '';
    }
}

if ( ! function_exists( 'set_query_var' ) ) {
    function set_query_var( $query_var, $value ) {
        return null;
    }
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) {
        return '';
    }
}

if ( ! function_exists( 'get_user_meta' ) ) {
    function get_user_meta( $user_id, $key = '', $single = false ) {
        return $single ? '' : [];
    }
}

if ( ! function_exists( 'update_user_meta' ) ) {
    function update_user_meta( $user_id, $meta_key, $meta_value, $prev_value = '' ) {
        return true;
    }
}

if ( ! function_exists( 'delete_user_meta' ) ) {
    function delete_user_meta( $user_id, $meta_key, $meta_value = '' ) {
        return true;
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type, $gmt = 0 ) {
        return '';
    }
}

if ( ! function_exists( 'wp_kses_post' ) ) {
    function wp_kses_post( $data ) {
        return is_string( $data ) ? $data : '';
    }
}

if ( ! function_exists( 'wp_reset_postdata' ) ) {
    function wp_reset_postdata() {
        return null;
    }
}

}

namespace Elementor {
    class Widget_Base {
        public function get_name() { return ''; }
        public function get_title() { return ''; }
        public function get_icon() { return ''; }
        public function get_categories() { return []; }
        public function get_script_depends() { return []; }
        public function get_style_depends() { return []; }
        public function start_controls_section( $id, $args = [] ) { return null; }
        public function end_controls_section() { return null; }
        public function add_control( $id, $args = [] ) { return null; }
        public function add_responsive_control( $id, $args = [] ) { return null; }
        public function add_group_control( $group, $args = [] ) { return null; }
        public function start_controls_tabs( $id ) { return null; }
        public function end_controls_tabs() { return null; }
        public function start_controls_tab( $id, $args = [] ) { return null; }
        public function end_controls_tab() { return null; }
        public function get_settings_for_display() { return []; }
        public function add_render_attribute( $element, $key = null, $value = null, $overwrite = false ) { return null; }
        public function get_render_attribute_string( $element ) { return ''; }
        public function get_id() { return '0'; }
    }

    class Controls_Manager {
        public const TEXT = 'text';
        public const TEXTAREA = 'textarea';
        public const SELECT = 'select';
        public const NUMBER = 'number';
        public const SWITCHER = 'switcher';
        public const COLOR = 'color';
        public const SLIDER = 'slider';
        public const CHOOSE = 'choose';
        public const DIMENSIONS = 'dimensions';
        public const REPEATER = 'repeater';
        public const HEADING = 'heading';
        public const SELECT2 = 'select2';
        public const WYSIWYG = 'wysiwyg';
        public const MEDIA = 'media';
        public const FONT = 'font';
        public const URL = 'url';
        public const RAW_HTML = 'raw_html';
        public const HIDDEN = 'hidden';
        public const TAB_STYLE = 'style';
    }

    class Repeater {
        public function add_control( $id, $args = [] ) { return null; }
        public function add_responsive_control( $id, $args = [] ) { return null; }
        public function get_controls() { return []; }
    }

    class Group_Control_Border {
        public static function get_type() { return 'border'; }
    }

    class Group_Control_Box_Shadow {
        public static function get_type() { return 'box-shadow'; }
    }

    class Group_Control_Typography {
        public static function get_type() { return 'typography'; }
    }

    class Group_Control_Css_Filter {
        public static function get_type() { return 'css-filter'; }
    }

    class Plugin {
        public $frontend;
        public $documents;

        private static $instance;

        public static function instance() {
            if ( ! self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function __construct() {
            $this->frontend = new Frontend();
            $this->documents = new Documents_Manager();
        }
    }

    class Frontend {
        public function enqueue_styles() { return null; }
        public function get_builder_content( $post_id, $with_css = false ) { return ''; }
    }

    class Documents_Manager {
        public function get( $post_id ) { return new Document(); }
        public function get_current() { return new Document(); }
    }

    class Document {
        public function get_elements_data() { return []; }
    }
}

namespace Elementor\Core\Files\CSS {
    class Post {
        public static function create( $post_id ) { return new self(); }
        public function enqueue() { return null; }
    }
}
