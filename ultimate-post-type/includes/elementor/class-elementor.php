<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Elementor {

    public static function init() {
        add_action( 'elementor/elements/categories_registered', [ self::class, 'add_widget_category' ] );
        add_action( 'elementor/widgets/register', [ self::class, 'register_widgets' ] );
        add_action( 'elementor/dynamic_tags/register', [ self::class, 'register_dynamic_tags' ] );
        add_action( 'elementor/editor/after_enqueue_scripts', [ self::class, 'enqueue_editor_scripts' ] );
        
        // Registro do Script Frontend (Galeria V6)
        add_action( 'elementor/frontend/after_enqueue_scripts', [ self::class, 'register_frontend_scripts' ] );
    }

    public static function enqueue_editor_scripts() {
        wp_enqueue_script(
            'upt-editor-js',
            plugin_dir_url( __FILE__ ) . '../../assets/js/editor.js',
            [ 'elementor-editor' ],
            filemtime( plugin_dir_path( __FILE__ ) . '../../assets/js/editor.js' ),
            true
        );
    }

    // --- FUNÇÃO DE REGISTRO DE SCRIPTS (CORRIGIDA) ---
    public static function register_frontend_scripts() {
        // Usa caminho relativo, pois a constante UPT_PLUGIN_URL não existe no ultimate-post-type.php
        $js_url = plugin_dir_url( __FILE__ ) . '../../assets/js/upt-gallery.js'; 

        wp_register_script(
            'upt-gallery-js', // O nome usado no get_script_depends() do widget
            $js_url,
            [ 'elementor-frontend', 'swiper' ], // Dependências
            '6.0.0', // Versão
            true // Carregar no footer
        );
    }

    public static function add_widget_category( $elements_manager ) {
        $elements_manager->add_category(
            'upt',
            [
                'title' => __( 'upt', 'upt' ),
                'icon' => 'eicon-code',
            ]
        );
    }

    public static function register_widgets( $widgets_manager ) {
        require_once UPT_PLUGIN_DIR . 'includes/elementor/widgets/listing-widget.php';
        $widgets_manager->register( new \UPT_Listing_Widget() );

        require_once UPT_PLUGIN_DIR . 'includes/elementor/widgets/dashboard-widget.php';
        $widgets_manager->register( new \UPT_Dashboard_Widget() );
        
        require_once UPT_PLUGIN_DIR . 'includes/elementor/widgets/whatsapp-button-widget.php';
        $widgets_manager->register( new \UPT_WhatsApp_Button_Widget() );

        require_once UPT_PLUGIN_DIR . 'includes/elementor/widgets/search-widget.php';
        $widgets_manager->register( new \UPT_Search_Widget() );

        require_once UPT_PLUGIN_DIR . 'includes/elementor/widgets/category-filter-widget.php';
        $widgets_manager->register( new \UPT_Category_Filter_Widget() );

        require_once UPT_PLUGIN_DIR . 'includes/elementor/widgets/dashboard-actions-widget.php';
        $widgets_manager->register( new \UPT_Dashboard_Actions_Widget() );
    
        require_once UPT_PLUGIN_DIR . 'includes/elementor/widgets/video-widget.php';
        $widgets_manager->register( new \UPT_Video_Widget() );

        require_once UPT_PLUGIN_DIR . 'includes/elementor/widgets/text-editor-widget.php';
        $widgets_manager->register( new \UPT_Text_Editor_Widget() );
        
        // Galeria V6
        require_once UPT_PLUGIN_DIR . 'includes/elementor/widgets/product-gallery-widget.php';
        $widgets_manager->register( new \UPT_Product_Gallery_Widget() );
    }

    public static function register_dynamic_tags( $dynamic_tags_manager ) {     
        $dynamic_tags_manager->register_group(
            'upt',
            [ 'title' => 'upt' ]
        );

        require_once UPT_PLUGIN_DIR . 'includes/elementor/tags/custom-field-tag.php';
        $dynamic_tags_manager->register( new \UPT_Custom_Field_Tag() );

        require_once UPT_PLUGIN_DIR . 'includes/elementor/tags/gallery-field-tag.php';
        $dynamic_tags_manager->register( new \UPT_Gallery_Field_Tag() );

        require_once UPT_PLUGIN_DIR . 'includes/elementor/tags/video-field-tag.php';
        $dynamic_tags_manager->register( new \UPT_Video_Field_Tag() );

        require_once UPT_PLUGIN_DIR . 'includes/elementor/tags/pdf-field-tag.php';
        $dynamic_tags_manager->register( new \UPT_PDF_Field_Tag() );

        require_once UPT_PLUGIN_DIR . 'includes/elementor/tags/related-item-data-tag.php';
        $dynamic_tags_manager->register( new \UPT_Related_Item_Data_Tag() );

        require_once UPT_PLUGIN_DIR . 'includes/elementor/tags/dashboard-data-tag.php';
        $dynamic_tags_manager->register( new \UPT_Dashboard_Data_Tag() );
    }
}
