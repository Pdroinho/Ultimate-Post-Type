<?php
if (!defined('ABSPATH'))
    exit;



// Walker customizado para exibir hierarquia no dropdown (categorias pai/filhas) com indentação visível.
if (!class_exists('UPT_Category_Dropdown_Walker') && class_exists('Walker_CategoryDropdown')) {
    class UPT_Category_Dropdown_Walker extends Walker_CategoryDropdown
    {
        public function start_el(&$output, $category, $depth = 0, $args = array(), $id = 0)
        {
            $pad = str_repeat("\xC2\xA0\xC2\xA0", (int)$depth); // 2 NBSP por nível

            $cat_name = apply_filters('list_cats', $category->name, $category);
            $cat_name = $pad . $cat_name;

            $value_field = isset($args['value_field']) && $args['value_field'] ? $args['value_field'] : 'term_id';
            $value = isset($category->{ $value_field}) ? $category->{ $value_field} : $category->term_id;

            $output .= "\t<option class=\"level-$depth\" value=\"" . esc_attr($value) . "\"";

            if ((string)$value === (string)$args['selected']) {
                $output .= ' selected=\"selected\"';
            }

            $output .= '>';
            $output .= esc_html($cat_name);
            $output .= "</option>\n";
        }
    }
}
class UPT_Admin
{

    public static function init()
    {
        add_action('admin_menu', [self::class , 'add_admin_menu']);
        add_action('admin_init', [self::class , 'handle_schema_actions']);
        add_action('admin_init', [self::class , 'handle_import_actions']);
        add_action('admin_init', [self::class , 'handle_export_actions']);
        add_action('admin_init', [self::class , 'handle_category_md_actions']);
        add_action('admin_init', [self::class , 'handle_media_import_actions']);
        add_action('pre_get_posts', [self::class , 'maybe_disable_admin_pagination']);
        add_filter('edit_catalog_category_per_page', [self::class , 'maybe_terms_per_page']);
        add_filter('edit_catalog_schema_per_page', [self::class , 'maybe_terms_per_page']);
        add_action('admin_enqueue_scripts', [self::class , 'enqueue_admin_scripts']);
        add_filter('admin_body_class', [self::class , 'add_admin_body_class']);

        // UX: Criar subcategorias em lote ao criar uma categoria (taxonomia catalog_category)
        add_action('catalog_category_add_form_fields', [self::class , 'render_category_subcategories_fields']);
        add_action('created_catalog_category', [self::class , 'handle_created_catalog_category'], 10, 2);

        // Botão "Visualizar todos" nas listagens do wp-admin (itens e taxonomias)
        add_action('manage_edit-catalog_category_top', [self::class , 'render_show_all_button_taxonomy']);
        add_action('manage_edit-catalog_schema_top', [self::class , 'render_show_all_button_taxonomy']);
        add_action('manage_posts_extra_tablenav', [self::class , 'render_show_all_button_posts'], 10, 1);

        // AJAX para renomear esquemas no builder
        add_action('wp_ajax_upt_rename_schema', [self::class , 'ajax_rename_schema']);
    }

    /**
     * Renderiza um botão para exibir TODOS os termos (sem paginação) na tela de taxonomias do upt.
     * Usa o parâmetro upt_show_all=1, já tratado em maybe_terms_per_page().
     */
    public static function render_show_all_button_taxonomy()
    {
        if (!is_admin()) {
            return;
        }

        if (empty($_GET['taxonomy'])) {
            return;
        }

        $taxonomy = sanitize_key(wp_unslash($_GET['taxonomy']));
        if (!in_array($taxonomy, ['catalog_category', 'catalog_schema'], true)) {
            return;
        }

        // Permite para quem pode gerenciar termos desta taxonomia (nao apenas administradores)
        $tax_obj = get_taxonomy($taxonomy);
        $cap = ($tax_obj && !empty($tax_obj->cap->manage_terms)) ? $tax_obj->cap->manage_terms : 'manage_categories';
        if (!current_user_can($cap)) {
            return;
        }

        $is_show_all = !empty($_GET['upt_show_all']);

        $base_url = remove_query_arg(['upt_show_all']);
        $url = $is_show_all ? $base_url : add_query_arg(['upt_show_all' => 1], $base_url);

        $label = $is_show_all ? 'Voltar a paginacao' : 'Visualizar todos';
        $class = $is_show_all ? 'button' : 'button button-primary';

        echo '<div class="alignleft actions" style="margin-left:8px;">';
        echo '<a href="' . esc_url($url) . '" class="' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        echo '</div>';
    }

    /**
     * Renderiza um botão para exibir TODOS os posts (sem paginação) na listagem de itens do catálogo.
     * Usa o parâmetro upt_show_all=1, já tratado em maybe_disable_admin_pagination().
     */
    public static function render_show_all_button_posts($which)
    {
        if ($which !== 'top') {
            return;
        }
        if (!is_admin()) {
            return;
        }

        if (empty($_GET['post_type']) || sanitize_key(wp_unslash($_GET['post_type'])) !== 'catalog_item') {
            return;
        }

        // Permite para quem pode editar este tipo de post (nao apenas administradores)
        $pto = get_post_type_object('catalog_item');
        $cap = ($pto && !empty($pto->cap->edit_posts)) ? $pto->cap->edit_posts : 'edit_posts';
        if (!current_user_can($cap)) {
            return;
        }

        $is_show_all = !empty($_GET['upt_show_all']);
        $base_url = remove_query_arg(['upt_show_all']);
        $url = $is_show_all ? $base_url : add_query_arg(['upt_show_all' => 1], $base_url);

        $label = $is_show_all ? 'Voltar a paginacao' : 'Visualizar todos';
        $class = $is_show_all ? 'button' : 'button button-primary';

        echo '<div class="alignleft actions" style="margin-left:8px;">';
        echo '<a href="' . esc_url($url) . '" class="' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        echo '</div>';
    }
    public static function add_admin_body_class($classes)
    {
        if (isset($_GET['page']) && $_GET['page'] === 'upt_gallery' && isset($_GET['noheader'])) {
            $classes .= ' upt-gallery-modal-mode';
        }
        return $classes;
    }

    public static function enqueue_admin_scripts($hook)
    {
        $schema_builder_hook = 'toplevel_page_upt_schema_builder';
        $importer_hook = 'catalogo_page_upt_importer';

        // Tela nativa de categorias do upt (taxonomia catalog_category)
        if ($hook === 'edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'catalog_category') {
            wp_enqueue_script(
                'upt-taxonomy-categories-js',
                plugin_dir_url(__FILE__) . '../assets/js/taxonomy-categories.js',
            ['jquery'],
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/taxonomy-categories.js'),
                true
            );
        }

        // Metabox de categorias no editor do item (catalog_item)
        // A criação rápida de categorias acontece aqui ("Adicionar nova categoria").
        // Inserimos o toggle/textarea de subcategorias para que o POST do AJAX inclua esses campos.
        if (($hook === 'post.php' || $hook === 'post-new.php')
        && ((isset($_GET['post_type']) && $_GET['post_type'] === 'catalog_item')
        || (isset($_GET['post']) && get_post_type(absint($_GET['post'])) === 'catalog_item'))
        ) {
            wp_enqueue_script(
                'upt-taxonomy-categories-metabox-js',
                plugin_dir_url(__FILE__) . '../assets/js/taxonomy-categories-metabox.js',
            ['jquery'],
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/taxonomy-categories-metabox.js'),
                true
            );
        }

        $css_base = plugin_dir_url(__FILE__) . '../assets/css/admin/';
        $css_path = plugin_dir_path(__FILE__) . '../assets/css/admin/';

        if ($hook === $schema_builder_hook) {
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('upt-admin-js', plugin_dir_url(__FILE__) . '../assets/js/admin.js', ['jquery'], filemtime(plugin_dir_path(__FILE__) . '../assets/js/admin.js'), true);
            wp_enqueue_style('upt-admin-variables', $css_base . 'variables.css', [], filemtime($css_path . 'variables.css'));
            wp_enqueue_style('upt-admin-base', $css_base . 'admin-base.css', ['upt-admin-variables'], filemtime($css_path . 'admin-base.css'));
            wp_enqueue_style('upt-admin-editor', $css_base . 'editor.css', ['upt-admin-variables'], filemtime($css_path . 'editor.css'));
        }

        if ($hook === $importer_hook) {
            wp_enqueue_style('upt-admin-variables', $css_base . 'variables.css', [], filemtime($css_path . 'variables.css'));
            wp_enqueue_style('upt-admin-base', $css_base . 'admin-base.css', ['upt-admin-variables'], filemtime($css_path . 'admin-base.css'));
            wp_enqueue_style('upt-admin-import-wizard', $css_base . 'import-wizard.css', ['upt-admin-variables'], filemtime($css_path . 'import-wizard.css'));
        }

        if ($hook === $importer_hook) {
            add_action('admin_footer', function () {
                ?>
                <script>
                jQuery(function($) {
                    var $mode = $('#imob_schema_mode');
                    var $newField = $('#imob_new_schema_field');
                    var $existingField = $('#imob_existing_schema_field');
                    var $uploadSection = $('#upt-imob-upload-section');
                    var $progressSection = $('#upt-imob-progress-section');
                    var $doneSection = $('#upt-imob-done-section');
                    var $startBtn = $('#upt-imob-start-btn');
                    var $cancelBtn = $('#upt-imob-cancel-btn');
                    var $newBtn = $('#upt-imob-new-btn');
                    var nonce = $('#upt-imob-nonce').val();

                    var session_id = '';
                    var total_items = 0;
                    var processed_total = 0;
                    var imported_total = 0;
                    var photos_total = 0;
                    var errors_total = 0;
                    var current_offset = 0;
                    var is_running = false;
                    var batch_size = 5;

                    if ($mode.length) {
                        $mode.on('change', function() {
                            if ($(this).val() === 'existing') {
                                $newField.hide();
                                $existingField.show();
                            } else {
                                $newField.show();
                                $existingField.hide();
                            }
                        });
                    }

                    $startBtn.on('click', function(e) {
                        e.preventDefault();
                        var fileInput = $('#imob_xml_file')[0];
                        if (!fileInput.files || fileInput.files.length === 0) {
                            alert('Selecione um arquivo XML.');
                            return;
                        }
                        $startBtn.prop('disabled', true).text('Enviando...');
                        uploadAndStart(fileInput.files[0]);
                    });

                    $cancelBtn.on('click', function() {
                        if (!confirm('Deseja realmente cancelar a importação?')) return;
                        is_running = false;
                        $.post(ajaxurl, {
                            action: 'upt_imob_cancel',
                            nonce: nonce,
                            session_id: session_id
                        });
                        $progressSection.hide();
                        $uploadSection.show();
                        $startBtn.prop('disabled', false).text('Enviar e Iniciar Importação');
                    });

                    $newBtn.on('click', function() {
                        $doneSection.hide();
                        $uploadSection.show();
                        $startBtn.prop('disabled', false).text('Enviar e Iniciar Importação');
                        $('#imob_xml_file').val('');
                    });

                    function uploadAndStart(file) {
                        var formData = new FormData();
                        formData.append('imob_xml_file', file);
                        formData.append('action', 'upt_imob_upload');
                        formData.append('nonce', nonce);
                        formData.append('imob_schema_mode', $('#imob_schema_mode').val());
                        formData.append('imob_schema_name', $('#imob_schema_name').val());
                        formData.append('imob_schema_existing', $('#imob_schema_existing').val());

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            timeout: 300000,
                            success: function(resp) {
                                $startBtn.prop('disabled', false).text('Enviar e Iniciar Importação');
                                if (resp.success && resp.data && resp.data.session_id) {
                                    session_id = resp.data.session_id;
                                    showProgress();
                                    countAndProcess();
                                } else {
                                    alert('Erro: ' + (resp.data && resp.data.message ? resp.data.message : 'Não foi possível iniciar a importação.'));
                                }
                            },
                            error: function(jqXHR) {
                                $startBtn.prop('disabled', false).text('Enviar e Iniciar Importação');
                                alert('Erro ao enviar o arquivo. Tente novamente.');
                            }
                        });
                    }

                    function showProgress() {
                        $uploadSection.hide();
                        $doneSection.hide();
                        $progressSection.show();
                        processed_total = 0;
                        imported_total = 0;
                        photos_total = 0;
                        errors_total = 0;
                        current_offset = 0;
                        updateStats();
                    }

                    function updateStats() {
                        var pct = total_items > 0 ? Math.round((processed_total / total_items) * 100) : 0;
                        $('#upt-imob-progress-bar').css('width', pct + '%');
                        $('#upt-imob-progress-text').text(pct + '%');
                        $('#upt-imob-stat-total').text(total_items || '—');
                        $('#upt-imob-stat-processed').text(processed_total);
                        $('#upt-imob-stat-imported').text(imported_total);
                        $('#upt-imob-stat-photos').text(photos_total);
                        $('#upt-imob-stat-errors').text(errors_total);
                    }

                    function countAndProcess() {
                        setStatus('Contando imóveis no XML...');
                        $.post(ajaxurl, {
                            action: 'upt_imob_count',
                            nonce: nonce,
                            session_id: session_id
                        }, function(resp) {
                            if (!resp.success) {
                                setStatus('Erro: ' + (resp.data && resp.data.message ? resp.data.message : 'desconhecido'), true);
                                return;
                            }
                            total_items = resp.data.total;
                            updateStats();
                            processNextBatch();
                        });
                    }

                    function processNextBatch() {
                        if (!is_running) return;
                        setStatus('Processando lote ' + (current_offset + 1) + '-' + Math.min(current_offset + batch_size, total_items) + ' de ' + total_items + '...');

                        $.post(ajaxurl, {
                            action: 'upt_imob_batch',
                            nonce: nonce,
                            session_id: session_id,
                            offset: current_offset,
                            limit: batch_size
                        }, function(resp) {
                            if (!is_running) return;

                            if (!resp.success) {
                                setStatus('Erro no lote: ' + (resp.data && resp.data.message ? resp.data.message : 'desconhecido') + '. Tentando continuar...', true);
                                current_offset += batch_size;
                                updateStats();
                                setTimeout(processNextBatch, 2000);
                                return;
                            }

                            var d = resp.data;
                            processed_total += d.processed;
                            imported_total += d.imported;
                            photos_total += d.photos;
                            errors_total += d.errors;
                            current_offset = d.next_offset;
                            updateStats();

                            if (d.last_error) {
                                setStatus('Último erro: ' + d.last_error + '. Continuando...', true);
                            } else {
                                setStatus('Processados ' + processed_total + ' de ' + total_items + '. Fotos baixadas: ' + photos_total + '.');
                            }

                            if (d.is_finished) {
                                finishImport();
                            } else {
                                setTimeout(processNextBatch, 500);
                            }
                        }).fail(function() {
                            if (!is_running) return;
                            setStatus('Erro de conexão. Tentando continuar em 3s...', true);
                            setTimeout(processNextBatch, 3000);
                        });
                    }

                    function finishImport() {
                        is_running = false;
                        $progressSection.hide();
                        $doneSection.show();
                        $('#upt-imob-done-stats').text(
                            'Importados: ' + imported_total + ' imóveis | Ignorados: ' + errors_total + ' | Fotos: ' + photos_total
                        );
                    }

                    function setStatus(msg, isError) {
                        var $el = $('#upt-imob-status-msg');
                        $el.text(msg);
                        if (isError) {
                            $el.css({'background': '#fef2f2', 'color': '#991b1b'});
                        } else {
                            $el.css({'background': '#f1f5f9', 'color': '#475569'});
                        }
                    }
                });
                </script>
                <?php
            });
        }

        if (isset($_GET['page']) && $_GET['page'] === 'upt_gallery') {
            wp_enqueue_media();
            wp_enqueue_style('upt-gallery-style', plugin_dir_url(__FILE__) . '../assets/css/gallery.css', [], filemtime(plugin_dir_path(__FILE__) . '../assets/css/gallery.css'));
            wp_enqueue_style('upt-admin-variables', $css_base . 'variables.css', [], filemtime($css_path . 'variables.css'));
            wp_enqueue_style('upt-admin-components', $css_base . 'components.css', ['upt-admin-variables'], filemtime($css_path . 'components.css'));
            wp_enqueue_style('upt-admin-form-fixes', $css_base . 'form-fixes.css', ['upt-admin-variables'], filemtime($css_path . 'form-fixes.css'));
            wp_enqueue_script('upt-front-js', plugin_dir_url(__FILE__) . '../assets/js/front.js', ['jquery'], filemtime(plugin_dir_path(__FILE__) . '../assets/js/front.js'), true);
            wp_enqueue_script('upt-gallery-js', plugin_dir_url(__FILE__) . '../assets/js/gallery.js', ['jquery', 'upt-front-js'], filemtime(plugin_dir_path(__FILE__) . '../assets/js/gallery.js'), true);

            add_action('plugins_loaded', 'upt');
            /**
             * Permitir envio de SVG.
             * Obs: a sanitização do SVG deve ser tratada no servidor se necessário.
             */add_filter('upload_mimes', function ($mimes) {
                $mimes['svg'] = 'image/svg+xml';
                $mimes['svgz'] = 'image/svg+xml';
                return $mimes;
            });

            add_filter('wp_handle_upload', function ($upload) {
                if (isset($upload['type']) && $upload['type'] === 'image/svg+xml' && isset($upload['file']) && file_exists($upload['file'])) {
                    $content = file_get_contents($upload['file']);
                    if ($content !== false) {
                        $dangerous_patterns = [
                            '/<script[\s>]/i',
                            '/\bon\w+\s*=\s*["\']?\s*(?:javascript|data|vbscript)/i',
                            '/<iframe[\s>]/i',
                            '/<embed[\s>]/i',
                            '/<object[\s>]/i',
                            '/<applet[\s>]/i',
                            '/<form[\s>]/i',
                            '/<input[\s>]/i',
                            '/<button[\s>]/i',
                            '/<textarea[\s>]/i',
                            '/<select[\s>]/i',
                            '/xlink:href\s*=\s*["\']?\s*(?:javascript|data):/i',
                            '/<foreignObject/i',
                            '/<set[\s>]/i',
                            '/<use[\s>]/i',
                            '/<animate[\s>]/i',
                            '/<animateTransform/i',
                        ];
                        foreach ($dangerous_patterns as $pattern) {
                            if (preg_match($pattern, $content)) {
                                @unlink($upload['file']);
                                $upload['error'] = 'O arquivo SVG contém conteúdo não permitido. Scripts e elementos interativos são bloqueados por segurança.';
                                return $upload;
                            }
                        }
                        $sanitized = preg_replace('/<\?xml[^>]*\?>/i', '', $content);
                        $sanitized = preg_replace('/<!--.*?-->/s', '', $sanitized);
                        $sanitized = preg_replace('/<(!DOCTYPE)[^>]*>/i', '', $sanitized);
                        $sanitized = preg_replace('/\son\w+\s*=\s*["\'][^"\']*["\']/i', '', $sanitized);
                        file_put_contents($upload['file'], $sanitized);
                    }
                }
                return $upload;
            });

        }
    }

    public static function add_admin_menu()
    {
        $upt_menu_icon = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a7aaad" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7l2-4h14l2 4"/><path d="M3 7v14h18V7"/><path d="M9 21v-8h6v8"/></svg>');
        add_menu_page(
            'upt',
            'Catálogo',
            'manage_options',
            'upt_schema_builder',
        [self::class , 'create_admin_page_schema_builder'],
            $upt_menu_icon,
            20
        );
        add_submenu_page(
            'upt_schema_builder',
            'Construtor de Esquemas',
            'Construtor',
            'manage_options',
            'upt_schema_builder',
        [self::class , 'create_admin_page_schema_builder']
        );
        add_submenu_page(
            'upt_schema_builder',
            'Todos os Itens',
            'Todos os Itens',
            'manage_options',
            'edit.php?post_type=catalog_item'
        );
        add_submenu_page(
            'upt_schema_builder',
            'Categorias',
            'Categorias',
            'manage_options',
            'edit-tags.php?taxonomy=catalog_category&post_type=catalog_item'
        );
        add_submenu_page(
            'upt_schema_builder',
            'Galeria upt',
            'Galeria',
            'upload_files',
            'upt_gallery',
        [self::class , 'create_admin_page_gallery']
        );
        add_submenu_page(
            'upt_schema_builder',
            'Importar/Exportar',
            'Importar/Exportar',
            'manage_options',
            'upt_importer',
        [self::class , 'create_admin_page_importer']
        );

        add_submenu_page(
            'upt_schema_builder',
            'Configurações do Catálogo',
            'Configurações',
            'manage_options',
            'upt_settings',
        [self::class , 'create_admin_page_settings']
        );

        add_submenu_page(
            'upt_schema_builder',
            'Ajuda',
            'Ajuda',
            'manage_options',
            'upt_help',
        [self::class , 'create_admin_page_help']
        );
        add_submenu_page(
            'upt_schema_builder',
            'Sobre o upt',
            'Sobre',
            'manage_options',
            'upt_about',
        [self::class , 'create_admin_page_about']
        );

        add_submenu_page(
            'upt_schema_builder',
            'Mídias não usadas',
            'Mídias não usadas',
            'manage_options',
            'upt_unused_media',
        [self::class , 'create_admin_page_unused_media']
        );
    }


    public static function create_admin_page_settings()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $message = '';
        $current_quality = (int)get_option('upt_webp_quality', 80);
        $current_enabled = get_option('upt_webp_enabled', '1') === '1';
        $current_only_jpg = get_option('upt_webp_only_jpeg', '0') === '1';

        if (
        isset($_POST['upt_settings_nonce'])
        && wp_verify_nonce(wp_unslash($_POST['upt_settings_nonce']), 'upt_save_settings')
        ) {
            $enable_input = isset($_POST['upt_webp_enabled']) ? '1' : '0';
            $only_jpg_input = isset($_POST['upt_webp_only_jpeg']) ? '1' : '0';
            $quality_input = isset($_POST['upt_webp_quality']) ? absint($_POST['upt_webp_quality']) : 80;

            if ($quality_input < 1) {
                $quality_input = 1;
            }

            if ($quality_input > 100) {
                $quality_input = 100;
            }

            update_option('upt_webp_enabled', $enable_input);
            update_option('upt_webp_only_jpeg', $only_jpg_input);
            update_option('upt_webp_quality', $quality_input);

            $current_enabled = $enable_input === '1';
            $current_only_jpg = $only_jpg_input === '1';
            $current_quality = $quality_input;
            $message = __('Configurações salvas com sucesso.', 'upt');
        }

?>
        <div class="wrap upt-wrap">
            <h1><?php esc_html_e('Configurações do Catálogo', 'upt'); ?></h1>

            <?php if ($message): ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div>
            <?php
        endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('upt_save_settings', 'upt_settings_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Converter uploads para WebP', 'upt'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="upt_webp_enabled" value="1" <?php checked($current_enabled); ?> />
                                <?php esc_html_e('Ativar conversão automática após o upload', 'upt'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Desative se quiser manter os arquivos originais sem conversão.', 'upt'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Qualidade da conversão para WebP', 'upt'); ?></th>
                        <td>
                            <input
                                type="number"
                                min="50"
                                max="100"
                                step="1"
                                name="upt_webp_quality"
                                id="upt_webp_quality"
                                value="<?php echo esc_attr($current_quality); ?>"
                                style="width: 90px;"
                            />
                            <p class="description">
                                <?php esc_html_e('Valores maiores preservam mais qualidade e geram arquivos maiores. Recomendado entre 75 e 95.', 'upt'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Formatos convertidos', 'upt'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="upt_webp_only_jpeg" value="1" <?php checked($current_only_jpg); ?> />
                                <?php esc_html_e('Converter apenas JPEG (pular PNG/GIF)', 'upt'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Útil para evitar perda de qualidade em logos/ícones com transparência. Deixe desmarcado para converter também PNG e GIF.', 'upt'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <p class="description">
                <?php esc_html_e('Ajuste é aplicado apenas a novas imagens enviadas após salvar. Mídias existentes não são recriadas automaticamente.', 'upt'); ?>
            </p>
        </div>
        <?php
    }


    public static function create_admin_page_help()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Garantir que o media modal esteja disponível para seleção de vídeos.
        if (function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }

        if (
        isset($_POST['upt_help_nonce'])
        && wp_verify_nonce(wp_unslash($_POST['upt_help_nonce']), 'upt_save_help')
        ) {


            // Vídeo hospedado no WordPress (ID do anexo)
            $video_file_id = (isset($_POST['upt_help_video_file_id']))
                ? absint($_POST['upt_help_video_file_id'])
                : 0;
            // Salva apenas o ID do vídeo de ajuda
            update_option('upt_help_video_file_id', $video_file_id);
            UPT_Cache::purge_all();

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Configurações salvas com sucesso.', 'upt') . '</p></div>';
        }

        $video_file_id = (int)get_option('upt_help_video_file_id', 0);

        $video_file_url = $video_file_id ? wp_get_attachment_url($video_file_id) : '';

?>
        <div class="wrap upt-wrap">
            <h1><?php esc_html_e('Ajuda do upt', 'upt'); ?></h1>
            <p><?php esc_html_e('Defina aqui o vídeo de ajuda que será exibido para os usuários no painel do upt.', 'upt'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('upt_save_help', 'upt_help_nonce'); ?>
                <table class="form-table" role="presentation">

                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Vídeo hospedado no WordPress', 'upt'); ?>
                        </th>
                        <td>
                            <input
                                type="hidden"
                                id="upt_help_video_file_id"
                                name="upt_help_video_file_id"
                                value="<?php echo esc_attr($video_file_id); ?>"
                            />
                            <div id="upt_help_video_file_preview">
                                <?php if ($video_file_url): ?>
                                    <p><?php echo esc_html(basename($video_file_url)); ?></p>
                                <?php
        else: ?>
                                    <p class="description"><?php esc_html_e('Nenhum vídeo selecionado.', 'upt'); ?></p>
                                <?php
        endif; ?>
                            </div>
                            <p>
                                <button type="button" class="button" id="upt_help_video_file_select">
                                    <?php esc_html_e('Selecionar vídeo', 'upt'); ?>
                                </button>
                                <button type="button" class="button" id="upt_help_video_file_remove">
                                    <?php esc_html_e('Remover vídeo', 'upt'); ?>
                                </button>
                            </p>
                            <p class="description">
                                <?php esc_html_e('Use esta opção para escolher um arquivo de vídeo enviado para a biblioteca de mídia do WordPress.', 'upt'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>

        <script>
        jQuery(function($){
            var frame;

            $('#upt_help_video_file_select').on('click', function(e){
                e.preventDefault();

                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: '<?php echo esc_js(__('Selecionar vídeo de ajuda', 'upt')); ?>',
                    library: { type: 'video' },
                    button: { text: '<?php echo esc_js(__('Usar este vídeo', 'upt')); ?>' },
                    multiple: false
                });

                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#upt_help_video_file_id').val(attachment.id);
                    $('#upt_help_video_file_preview').html('<p>' + attachment.filename + '</p>');
                });

                frame.open();
            });

            $('#upt_help_video_file_remove').on('click', function(e){
                e.preventDefault();
                $('#upt_help_video_file_id').val('');
                $('#upt_help_video_file_preview').html('<p class="description"><?php echo esc_js(__('Nenhum vídeo selecionado.', 'upt')); ?></p>');
            });
        });
        </script>
        <?php
    }

    private static function render_schema_items_order($schema_slug)
    {
        $schema_term = get_term_by('slug', $schema_slug, 'catalog_schema');
        $schema_name = $schema_term && !is_wp_error($schema_term) ? $schema_term->name : $schema_slug;

        $base_query = [
            'post_type' => 'catalog_item',
            'post_status' => ['publish', 'pending', 'draft'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'catalog_schema',
                    'field' => 'slug',
                    'terms' => $schema_slug,
                ],
            ],
        ];

        $ordered_query = new WP_Query(array_merge($base_query, [
            'meta_key' => 'upt_manual_order',
            'orderby' => [
                'meta_value_num' => 'ASC',
                'date' => 'DESC',
            ],
        ]));

        $item_ids = $ordered_query->posts;
        wp_reset_postdata();

        if (empty($item_ids)) {
            echo '<hr />';
            echo '<h3>Ordem dos itens do esquema</h3>';
            echo '<p>Nenhum item foi criado para este esquema ainda.</p>';
            return;
        }

        // Normaliza a meta de ordem sem alterar a data de modificação.
        $position = 1;
        foreach ($item_ids as $item_id) {
            $current_position = (int)get_post_meta($item_id, 'upt_manual_order', true);
            if ($current_position !== $position) {
                update_post_meta($item_id, 'upt_manual_order', $position);
            }
            $position++;
        }

        $items = get_posts([
            'post_type' => 'catalog_item',
            'post_status' => ['publish', 'pending', 'draft'],
            'posts_per_page' => -1,
            'post__in' => $item_ids,
            'orderby' => 'post__in',
        ]);

        $nonce = wp_create_nonce('upt_ajax_nonce');

        echo '<hr />';
        echo '<h3>Ordem dos itens para "' . esc_html($schema_name) . '"</h3>';
        echo '<p class="description">Arraste para reordenar. A nova ordem é usada nas listagens e no Elementor.</p>';
        echo '<table class="upt-table upt-items-table" data-schema="' . esc_attr($schema_slug) . '" data-nonce="' . esc_attr($nonce) . '">';
        echo '<thead><tr><th style="width:46px;"></th><th>Título</th><th>Status</th><th>Autor</th><th>Modificado</th></tr></thead>';
        echo '<tbody>';

        foreach ($items as $item) {
            $status_obj = get_post_status_object($item->post_status);
            $status_lbl = $status_obj ? $status_obj->label : $item->post_status;
            $author = get_user_by('id', $item->post_author);
            $author_lbl = $author ? $author->display_name : '-';
            echo '<tr class="upt-item-row" data-item-id="' . esc_attr($item->ID) . '">';
            echo '<td class="upt-drag-handle" title="Arraste para reordenar">⋮⋮</td>';
            echo '<td>' . esc_html(get_the_title($item)) . '</td>';
            echo '<td>' . esc_html($status_lbl) . '</td>';
            echo '<td>' . esc_html($author_lbl) . '</td>';
            echo '<td>' . esc_html(get_the_modified_date('d/m/Y H:i', $item)) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }


    public static function create_admin_page_gallery()
    {
        if (isset($_GET['upt_export_media'])) {
            self::handle_upt_media_export();
            return;
        }

        $is_modal_mode = isset($_GET['noheader']);

        if ($is_modal_mode) {
            $css_path = plugin_dir_path(__FILE__) . '../assets/css/gallery.css';
            $js_path = plugin_dir_path(__FILE__) . '../assets/js/gallery.js';
            $gallery_css_url = plugin_dir_url(__FILE__) . '../assets/css/gallery.css';
            $gallery_js_url = plugin_dir_url(__FILE__) . '../assets/js/gallery.js';
            $css_version = file_exists($css_path) ? filemtime($css_path) : '1.0';
            $js_version = file_exists($js_path) ? filemtime($js_path) : '1.0';

            $inline_styles = '';
            $accent_color = isset($_GET['accent_color']) ? sanitize_text_field(wp_unslash($_GET['accent_color'])) : '';

            if ($accent_color) {
                $inline_styles .= '<style>:root {';
                if ($accent_color) {
                    if (preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $accent_color) || strpos($accent_color, 'rgb') === 0) {
                        $inline_styles .= '--fc-primary-color: ' . $accent_color . ' !important;';
                    }
                }
                $inline_styles .= '}</style>';
            }
?>
            <!DOCTYPE html>
            <html lang="pt-BR" style="background: transparent !important;">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <link rel="stylesheet" href="<?php echo esc_url($gallery_css_url . '?ver=' . $css_version); ?>">
                <?php echo $inline_styles; ?>
            </head>
            <body class="upt-gallery-modal-mode">
            <?php
        }
?>
        <div class="wrap upt-gallery-wrap">
            <div class="upt-alert-config" data-upt-alert-enabled="1" data-upt-alert-media-deleted="1" data-upt-alert-media-uploaded="1" data-upt-alert-media-moved="1" data-upt-alert-duration="3"></div>
            <?php if ($is_modal_mode): ?>
                <button id="upt-gallery-close" class="button-icon-only">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg>
                </button>
            <?php
        else: ?>
                <h1>Galeria upt</h1>
            <?php
        endif; ?>
            
            <div class="upt-gallery-layout">
                <div class="gallery-overlay"></div>
                <aside class="gallery-sidebar">
                    <div class="gallery-sidebar-header">
                        <h2>Pastas</h2>
                        <button id="close-sidebar-button" class="button-icon-only">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg>
                        </button>
                    </div>
                    <div class="gallery-sidebar-content">
                        <ul id="upt-folder-list" class="gallery-folder-list"></ul>
                    </div>
                    <div class="gallery-sidebar-footer">
                        <select id="new-folder-parent" aria-label="Pasta superior" class="upt-new-folder-parent">
                            <option value="0">Criar em: Raiz</option>
                        </select>
                        <input type="text" id="new-folder-name" placeholder="Nome da nova pasta...">
                        <div class="upt-folder-actions-row">
                            <button id="create-folder-button" class="button">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10,4L12,6H20A2,2 0 0,1 22,8V18A2,2 0 0,1 20,20H4A2,2 0 0,1 2,18V6A2,2 0 0,1 4,4H10M15,11V14H12V16H15V19H17V16H20V14H17V11H15Z" /></svg>
                                <span>Criar Pasta</span>
                            </button>

                            <button id="delete-folders-bulk-button" class="button is-destructive upt-bulk-delete-folders-button" title="Apagar múltiplas pastas" aria-label="Apagar múltiplas pastas">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,4H15.5L14.79,3.29C14.61,3.11 14.35,3 14.09,3H9.91C9.65,3 9.39,3.11 9.21,3.29L8.5,4H5C4.45,4 4,4.45 4,5V6C4,6.55 4.45,7 5,7H19C19.55,7 20,6.55 20,6V5C20,4.45 19.55,4 19,4M6,19C6,20.11 6.89,21 8,21H16C17.11,21 18,20.11 18,19V7H6V19Z" /></svg>
                                <span>Apagar Pastas</span>
                            </button>
                        </div>

                        <div id="create-folder-status"></div>
                    </div>
                </aside>

                <main class="gallery-main">
                    <div class="gallery-main-header">
                        <div class="header-row-1">
                            <button id="toggle-sidebar-button" class="button-icon-only">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3,6H21V8H3V6M3,11H21V13H3V11M3,16H21V18H3V16Z" /></svg>
                            </button>
                            <div class="header-title-wrap">
                                <h2 id="upt-current-folder">Todas as Mídias</h2>
                                <nav id="upt-breadcrumb" class="gallery-breadcrumb" aria-label="Caminho da pasta"></nav>
                            </div>
                        </div>
                        <div class="header-row-2">
                            <div class="gallery-select-actions">
                                <a href="#" id="select-all-button">Selecionar todas</a>
                                <a href="#" id="deselect-all-button">Limpar seleção</a>
                            </div>

                            <div class="gallery-header-actions">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=upt_gallery&upt_export_media=1')); ?>"
                                   id="upt-export-media-button"
                                   class="button button-secondary"
                                   title="Exportar mídia" aria-label="Exportar mídia">
                                    <svg class="upt-export-icon" style="transform: rotate(180deg);" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9,16V10H5L12,3L19,10H15V16H9M5,20V18H19V20H5Z" /></svg>
                                    <span class="gallery-action-text">Exportar mídia</span>
                                </a>

                                <div id="gallery-actions" class="gallery-main-actions" style="display: none;">
                                    <div id="move-to-folder-wrapper" style="display: none;">
                                        <select id="move-to-folder-select">
                                            <option value="">Mover para...</option>
                                        </select>
                                    </div>
                                    <?php if (!$is_modal_mode): ?>
                                    <button id="remove-from-folder-button" class="button button-secondary" style="display: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"><path d="M19.46 10.73L18.05 9.32L16 11.37L13.95 9.32L12.54 10.73L14.59 12.78L12.54 14.83L13.95 16.24L16 14.19L18.05 16.24L19.46 14.83L17.41 12.78L19.46 10.73Z" /></svg>
                                        <span>Remover</span>
                                    </button>
                                    
                                    <?php
        endif; ?>
<button id="delete-image-button" class="button is-destructive" title="Excluir" aria-label="Excluir">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,4H15.5L14.79,3.29C14.61,3.11 14.35,3 14.09,3H9.91C9.65,3 9.39,3.11 9.21,3.29L8.5,4H5C4.45,4 4,4.45 4,5V6C4,6.55 4.45,7 5,7H19C19.55,7 20,6.55 20,6V5C20,4.45 19.55,4 19,4M6,19C6,20.11 6.89,21 8,21H16C17.11,21 18,20.11 18,19V7H6V19Z" /></svg>
                                        <span>Excluir</span>
                                    </button>
                                    <?php if ($is_modal_mode): ?>
                                    
                                    <div class="upt-use-btn-group" role="group" aria-label="Ações de seleção">
                                        <button id="use-image-button" class="button button-primary" title="Usar" aria-label="Usar">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" /></svg>
                                        <span>Usar Mídia(s)</span>
                                    </button>
                                    
                                        <button id="upt-clear-selection-button" class="button button-secondary upt-clear-selection-button" title="Remover seleção" aria-label="Remover seleção" type="button">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.29 19.71 2.88 18.29 9.17 12 2.88 5.71 4.29 4.29 10.59 10.6l6.3-6.31z"/></svg>
                                        </button>
                                    </div>
                                    <?php
        endif; ?>
                                </div>

                                <div id="upt-upload-container">
                                    <label for="upt-uploader" class="button button-primary upt-upload-button" title="Enviar mídia" aria-label="Enviar mídia">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9,16V10H5L12,3L19,10H15V16H9M5,20V18H19V20H5Z" /></svg>
                                        <span class="upt-upload-button-main-text">Adicionar mídia</span>
                                        <span class="upt-upload-button-subtext">Clique para cancelar</span>
                                    </label>
                                    <input type="file" id="upt-uploader" multiple style="display: none;" accept="image/*,video/*,application/pdf">
                                    <div id="upt-upload-progress-wrapper" class="upt-progress-wrapper" style="display: none; margin-top: 6px;">
                                        <div class="upt-progress-bar" style="position:relative;width:100%;height:6px;border-radius:999px;background:#e5e7eb;overflow:hidden;">
                                            <div class="upt-progress-bar-fill" style="position:absolute;left:0;top:0;bottom:0;width:0;border-radius:inherit;background:var(--fc-primary-color,#1d4ed8);"></div>
                                        </div>
                                        <span class="upt-progress-label" style="display:block;margin-top:4px;font-size:11px;color:#4b5563;">Enviando...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="gallery-main-content">
                        <div id="upt-image-grid" class="gallery-image-grid"></div>
                        <div id="upt-gallery-pagination" class="upt-gallery-pagination" style="display:none;"></div>
                    </div>
                </main>
            </div>
        </div>
        <?php
        if ($is_modal_mode) {
            wp_enqueue_media();
?>
            <script src="<?php echo esc_url(includes_url('js/jquery/jquery.js')); ?>"></script>
            <script>
                var upt_gallery = {
                    "ajax_url": "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
                    "upload_url": "<?php echo esc_url(admin_url('async-upload.php')); ?>",
                    "nonce": "<?php echo esc_js(wp_create_nonce('upt_ajax_nonce')); ?>",
                    "upload_nonce": "<?php echo esc_js(wp_create_nonce('media-form')); ?>",
                    "transparent_png": "<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/img/1x1.png'); ?>",
                    "pdf_placeholder": "<?php echo esc_url(wp_mime_type_icon('application/pdf')); ?>"
                };
            </script>
            <?php do_action('admin_footer'); ?>
            <script src="<?php echo esc_url($gallery_js_url . '?ver=' . $js_version); ?>"></script>
            </body>
            </html>
            <?php
            exit;
        }
        else {
?>
            <script>
                var upt_gallery = window.upt_gallery || {
                    "ajax_url": "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
                    "upload_url": "<?php echo esc_url(admin_url('async-upload.php')); ?>",
                    "nonce": "<?php echo esc_js(wp_create_nonce('upt_ajax_nonce')); ?>",
                    "upload_nonce": "<?php echo esc_js(wp_create_nonce('media-form')); ?>",
                    "transparent_png": "<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/img/1x1.png'); ?>",
                    "pdf_placeholder": "<?php echo esc_url(wp_mime_type_icon('application/pdf')); ?>"
                };
            </script>
            <?php
        }
    }
    public static function handle_upt_media_export()
    {
        if (!current_user_can('upload_files')) {
            wp_die(esc_html__('Você não tem permissão para exportar mídia.', 'upt'));
        }

        // ID único (single) ou lista de IDs (múltiplos) enviados pela galeria
        $single_id = isset($_GET['single_media_id']) ? absint($_GET['single_media_id']) : 0;
        $ids_param = isset($_GET['media_ids']) ? sanitize_text_field(wp_unslash($_GET['media_ids'])) : '';
        $selected_ids = [];

        if ('' !== $ids_param) {
            $parts = array_filter(array_map('absint', explode(',', $ids_param)));
            if (!empty($parts)) {
                $selected_ids = array_values(array_unique($parts));
            }
        }

        // Caso 1: apenas um ID enviado -> baixa arquivo individual (sem ZIP)
        if ($single_id && empty($selected_ids)) {
            $file_path = get_attached_file($single_id);
            if (!$file_path || !file_exists($file_path)) {
                wp_die(esc_html__('Arquivo de mídia não encontrado para download.', 'upt'));
            }

            $filename = basename($file_path);
            $mime_type = function_exists('mime_content_type') ? mime_content_type($file_path) : 'application/octet-stream';

            if (function_exists('ob_get_level')) {
                while (ob_get_level()) {
                    ob_end_clean();
                }
            }

            nocache_headers();
            header('Content-Type: ' . $mime_type);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($file_path));
            header('Pragma: public');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');

            readfile($file_path);
            exit;
        }

        // Daqui em diante, sempre usamos ZIP (seleção múltipla ou exportação completa).
        if (!class_exists('ZipArchive')) {
            wp_die(esc_html__('A extensão ZipArchive não está disponível no servidor.', 'upt'));
        }

        // Limpa qualquer saída anterior para evitar corromper o ZIP.
        if (function_exists('ob_get_level')) {
            while (ob_get_level()) {
                ob_end_clean();
            }
        }

        $zip = new ZipArchive();
        $tmp_file = wp_tempnam('upt-media-export.zip');

        if (!$tmp_file || true !== $zip->open($tmp_file, ZipArchive::OVERWRITE)) {
            wp_die(esc_html__('Não foi possível criar o arquivo de exportação.', 'upt'));
        }

        $added_any = false;

        // Caso 2: IDs selecionados informados -> apenas esses são incluídos no ZIP.
        if (!empty($selected_ids)) {
            foreach ($selected_ids as $attachment_id) {
                $file_path = get_attached_file($attachment_id);
                if ($file_path && file_exists($file_path)) {
                    $local_name = basename($file_path);
                    $zip->addFile($file_path, $local_name);
                    $added_any = true;
                }
            }
        }
        else {
            // Caso 3: exportação completa (comportamento anterior).
            // Garante que a classe de pastas de mídia esteja carregada.
            if (class_exists('UPT_Media_Folders')) {
                $terms = get_terms(
                [
                    'taxonomy' => UPT_Media_Folders::TAXONOMY,
                    'hide_empty' => false,
                ]
                );

                if (!is_wp_error($terms) && !empty($terms)) {
                    foreach ($terms as $term) {
                        $folder_name = sanitize_title($term->name);
                        $folder_dir = $folder_name ? $folder_name : 'pasta-' . $term->term_id;

                        // Cria a pasta no ZIP.
                        $zip->addEmptyDir(trailingslashit($folder_dir));

                        // Busca anexos associados a essa pasta.
                        $attachments = get_posts(
                        [
                            'post_type' => 'attachment',
                            'post_status' => 'inherit',
                            'posts_per_page' => -1,
                            'post_mime_type' => ['image', 'video', 'application/pdf'],
                            'tax_query' => [
                                [
                                    'taxonomy' => UPT_Media_Folders::TAXONOMY,
                                    'field' => 'term_id',
                                    'terms' => $term->term_id,
                                ],
                            ],
                        ]
                        );

                        if (!empty($attachments) && !is_wp_error($attachments)) {
                            foreach ($attachments as $attachment) {
                                $file_path = get_attached_file($attachment->ID);
                                if ($file_path && file_exists($file_path)) {
                                    $local_name = trailingslashit($folder_dir) . basename($file_path);
                                    $zip->addFile($file_path, $local_name);
                                    $added_any = true;
                                }
                            }
                        }
                    }
                }
            }

            // Anexos sem pasta (sem taxonomia).
            $uncategorized_attachments = get_posts(
            [
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'posts_per_page' => -1,
                'post_mime_type' => ['image', 'video', 'application/pdf'],
                'tax_query' => [
                    [
                        'taxonomy' => class_exists('UPT_Media_Folders') ?UPT_Media_Folders::TAXONOMY : '',
                        'operator' => 'NOT EXISTS',
                    ],
                ],
            ]
            );

            if (!empty($uncategorized_attachments) && !is_wp_error($uncategorized_attachments)) {
                $zip->addEmptyDir('sem-pasta/');

                foreach ($uncategorized_attachments as $attachment) {
                    $file_path = get_attached_file($attachment->ID);
                    if ($file_path && file_exists($file_path)) {
                        $local_name = 'sem-pasta/' . basename($file_path);
                        $zip->addFile($file_path, $local_name);
                        $added_any = true;
                    }
                }
            }
        }

        $zip->close();

        if (!$added_any || !file_exists($tmp_file)) {
            @unlink($tmp_file);
            wp_die(esc_html__('Nenhum arquivo de mídia encontrado para exportar.', 'upt'));
        }

        $filename = 'upt-media-' . gmdate('Ymd-His') . '.zip';

        nocache_headers();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp_file));
        header('Pragma: public');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        readfile($tmp_file);
        @unlink($tmp_file);
        exit;
    }
    public static function create_admin_page_importer()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
?>
        <div class="wrap upt-wrap upt-importer">
            <h1>Importar e Exportar Itens</h1>
            <?php settings_errors(); ?>

            <style>
            .upt-importer .upt-card { margin-bottom: 24px; }
            .upt-importer .upt-card:last-of-type { margin-bottom: 0; }
            </style>

            <div class="upt-card">
                <div class="upt-card__header"><h2>Importar dados (XML / JSON / WXR)</h2></div>
                <div class="upt-card__body">
                                        <p>Envie o arquivo XML exportado do próprio upt ou JSON. O conteúdo será lido e exibido dinamicamente para que você possa mapear os dados para os campos do esquema.</p>
                    <p style="color:#dc2626;font-weight:600;"><strong>Atenção:</strong> Para importar XML de imobiliárias (OKE, Zap, Viva Real, etc.), use a seção <strong>"Importar XML de Imobiliária"</strong> abaixo.</p>
                    <form method="post" action="" enctype="multipart/form-data">
                        <?php wp_nonce_field('upt_import_nonce'); ?>
                        <input type="hidden" name="action" value="upt_import_data">
                                                <input type="hidden" name="import_format" value="xml">
                        
                        <div class="form-field">
                            <label>Esquema de destino:</label>
                            <p style="margin: 4px 0 8px;">Escolha se os itens importados devem ser associados a um esquema existente ou a um novo esquema.</p>
                            <label style="display:block;margin-bottom:4px;">
                                <input type="radio" name="import_schema_mode" value="keep" checked>
                                Manter informações de esquema do arquivo (quando existirem)
                            </label>
                            <label style="display:block;margin-bottom:4px;">
                                <input type="radio" name="import_schema_mode" value="existing">
                                Associar a um esquema existente
                            </label>
                            <label style="display:block;margin-bottom:4px;">
                                <input type="radio" name="import_schema_mode" value="new">
                                Criar um novo esquema
                            </label>
                        </div>
                        <div class="form-field">
                            <label for="import_schema_existing">Esquema existente:</label>
                            <select name="import_schema_existing" id="import_schema_existing">
                                <option value="">— Selecione um esquema —</option>
                                <?php
        $schemas_terms = get_terms(
        [
            'taxonomy' => 'catalog_schema',
            'hide_empty' => false,
        ]
        );
        if (!is_wp_error($schemas_terms) && !empty($schemas_terms)) {
            foreach ($schemas_terms as $term) {
                printf(
                    '<option value="%s">%s</option>',
                    esc_attr($term->slug),
                    esc_html($term->name)
                );
            }
        }
?>
                            </select>
                            <p class="description">Use esta opção quando desejar importar os itens para um esquema já configurado.</p>
                        </div>
                        <div class="form-field">
                            <label for="import_schema_new_name">Novo esquema:</label>
                            <input type="text" name="import_schema_new_name" id="import_schema_new_name" placeholder="Ex.: Blog / Artigos" />
                            <p class="description">Informe o nome do novo esquema para criar automaticamente e associar todos os itens importados.</p>
                        </div>
                        
<div id="upt-wp-preview" class="notice" style="display:none;margin-top:16px;"></div>

<div id="upt-wp-mapping" style="display:none;">
<div class="form-field">
                            <label>Mapeamento de dados do WordPress para campos do esquema</label>
                            <p class="description">Escolha como cada informação básica do post WordPress será armazenada no esquema. Deixe em branco para não criar campo para aquele dado.</p>
                            <table class="widefat striped" style="max-width: 640px;">
                                <thead>
                                    <tr>
                                        <th style="width: 35%;">Dado do WordPress</th>
                                        <th>Tipo de campo a criar no esquema</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Título</strong></td>
                                        <td>
                                            <select name="wp_field_type_title">
                                                <option value="">— Não criar campo —</option>
                                                <optgroup label="Campos Nativos">
                                                    <option value="core_title" selected="selected">Título Principal (Nativo)</option>
                                                </optgroup>
                                                <optgroup label="Campos Customizados">
                                                    <option value="text">Texto (uma linha)</option>
                                                    <option value="textarea">Texto (múltiplas linhas)</option>
                                                    <option value="wysiwyg">Editor de Texto (WYSIWYG)</option>
                                                </optgroup>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Link</strong></td>
                                        <td>
                                            <select name="wp_field_type_link">
                                                <option value="">— Não criar campo —</option>
                                                <optgroup label="Campos Customizados">
                                                    <option value="url" selected="selected">URL</option>
                                                    <option value="text">Texto (uma linha)</option>
                                                </optgroup>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Post ID</strong></td>
                                        <td>
                                            <select name="wp_field_type_post_id">
                                                <option value="">— Não criar campo —</option>
                                                <optgroup label="Campos Customizados">
                                                    <option value="number" selected="selected">Número</option>
                                                    <option value="text">Texto (uma linha)</option>
                                                </optgroup>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Data</strong></td>
                                        <td>
                                            <select name="wp_field_type_date">
                                                <option value="">— Não criar campo —</option>
                                                <optgroup label="Campos Customizados">
                                                    <option value="text" selected="selected">Texto (uma linha)</option>
                                                    <option value="time">Tempo (Hora)</option>
                                                    <option value="date">Data</option>
                                                </optgroup>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Categoria</strong></td>
                                        <td>
                                            <select name="wp_field_type_category">
                                                <option value="">— Não criar campo —</option>
                                                <optgroup label="Campos Customizados">
                                                    <option value="taxonomy" selected="selected">Seleção de Categoria</option>
                                                    <option value="text">Texto (uma linha)</option>
                                                    <option value="select">Caixa de Seleção</option>
                                                </optgroup>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status</strong></td>
                                        <td>
                                            <select name="wp_field_type_status">
                                                <option value="">— Não criar campo —</option>
                                                <optgroup label="Campos Customizados">
                                                    <option value="text" selected="selected">Texto (uma linha)</option>
                                                    <option value="select">Caixa de Seleção</option>
                                                </optgroup>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Conteúdo (resumo)</strong></td>
                                        <td>
                                            <select name="wp_field_type_excerpt">
                                                <option value="">— Não criar campo —</option>
                                                <optgroup label="Campos Customizados">
                                                    <option value="textarea" selected="selected">Texto (múltiplas linhas)</option>
                                                    <option value="wysiwyg">Editor de Texto (WYSIWYG)</option>
                                                </optgroup>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Conteúdo completo</strong></td>
                                        <td>
                                            <select name="wp_field_type_content">
                                                <option value="">— Não criar campo —</option>
                                                <optgroup label="Campos Nativos">
                                                    <option value="core_content">Conteúdo Principal / Descrição (Nativo)</option>
                                                    <option value="blog_post" selected="selected">Postagem Completa (Conteúdo + Resumo)</option>
                                                </optgroup>
                                                <optgroup label="Campos Customizados">
                                                    <option value="wysiwyg">Editor de Texto (WYSIWYG)</option>
                                                    <option value="textarea">Texto (múltiplas linhas)</option>
                                                </optgroup>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="description" style="margin-top:8px;">Você poderá alterar ou remover esses campos depois na tela de edição do esquema.</p>
                        </div>
</div>



<div class="form-field">
                            <label>Campos do arquivo:</label>
                            <p style="margin: 4px 0 8px;">Ao importar, você pode escolher usar os campos presentes no XML (criando campos extras quando necessário) ou manter apenas os campos já existentes/configurados no upt.</p>
                            <label style="display:block;margin-bottom:4px;">
                                <input type="radio" name="import_fields_mode" id="import_fields_mode_use" value="use_file" checked>
                                Usar campos do arquivo (pode criar campos extras)
                            </label>
                            <label style="display:block;margin-bottom:4px;">
                                <input type="radio" name="import_fields_mode" id="import_fields_mode_upt" value="upt_only">
                                Manter apenas campos do upt (ignorar extras do arquivo)
                            </label>
                        </div>

                        <div class="form-field">
                            <label for="import_json_file">Selecione o arquivo:</label>
                            <input type="file" name="import_json_file" id="import_json_file" accept=".json,application/json,.xml,text/xml,application/xml" required>
                        </div>
                        <?php submit_button('Importar Arquivo'); ?>
                    </form>
                </div>
            </div>

            <div class="upt-card" style="margin-top: 24px;">
                <div class="upt-card__header"><h2>Importar XML de Imobiliária (OKE / Zap / Viva Real)</h2></div>
                <div class="upt-card__body">
                    <p>Importe imóveis de um arquivo XML no formato de portais imobiliários. As imagens são baixadas automaticamente das URLs contidas no XML. O processamento é feito em lotes via AJAX, ideal para arquivos grandes com milhares de imóveis.</p>

                    <div id="upt-imob-upload-section">
                        <form id="upt-imob-upload-form" enctype="multipart/form-data">
                            <input type="hidden" id="upt-imob-nonce" value="<?php echo esc_attr(wp_create_nonce('upt_ajax_nonce')); ?>" />

                            <div class="form-field">
                                <label>Nome do Esquema:</label>
                                <p class="description">Os imóveis serão importados sob este esquema. Se o esquema já existir, os campos serão reutilizados.</p>
                                <select name="imob_schema_mode" id="imob_schema_mode">
                                    <option value="new">Criar um novo esquema</option>
                                    <option value="existing">Usar um esquema existente</option>
                                </select>
                            </div>

                            <div class="form-field" id="imob_new_schema_field">
                                <label for="imob_schema_name">Nome do novo esquema:</label>
                                <input type="text" name="imob_schema_name" id="imob_schema_name" value="Imóveis" placeholder="Ex.: Imóveis à Venda" />
                            </div>

                            <div class="form-field" id="imob_existing_schema_field" style="display:none;">
                                <label for="imob_schema_existing">Esquema existente:</label>
                                <select name="imob_schema_existing" id="imob_schema_existing">
                                    <option value="">— Selecione um esquema —</option>
                                    <?php
                                    $imob_schemas = get_terms([
                                        'taxonomy' => 'catalog_schema',
                                        'hide_empty' => false,
                                    ]);
                                    if (!is_wp_error($imob_schemas) && !empty($imob_schemas)) {
                                        foreach ($imob_schemas as $s) {
                                            printf('<option value="%s">%s</option>', esc_attr($s->slug), esc_html($s->name));
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-field">
                                <label for="imob_xml_file">Selecione o arquivo XML:</label>
                                <input type="file" name="imob_xml_file" id="imob_xml_file" accept=".xml,text/xml,application/xml" required>
                                <p class="description">Formato esperado: tags &lt;Imovel&gt; contendo dados como &lt;TituloImovel&gt;, &lt;PrecoVenda&gt;, &lt;Fotos&gt;, etc.</p>
                            </div>

                            <p>
                                <button type="submit" class="button button-primary" id="upt-imob-start-btn">Enviar e Iniciar Importação</button>
                            </p>
                        </form>
                    </div>

                    <div id="upt-imob-progress-section" style="display:none;">
                        <div class="upt-imob-progress-bar-wrap" style="background:#e2e8f0;border-radius:8px;overflow:hidden;height:28px;position:relative;margin:16px 0 10px;">
                            <div id="upt-imob-progress-bar" style="background:#6366f1;height:100%;width:0%;transition:width 0.4s ease;border-radius:8px;"></div>
                            <span id="upt-imob-progress-text" style="position:absolute;top:0;left:0;right:0;text-align:center;line-height:28px;color:#fff;font-size:13px;font-weight:600;text-shadow:0 1px 2px rgba(0,0,0,0.2);">0%</span>
                        </div>
                        <div id="upt-imob-stats" style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:12px;font-size:13px;color:#475569;">
                            <span>Total: <strong id="upt-imob-stat-total">—</strong></span>
                            <span>Processados: <strong id="upt-imob-stat-processed">0</strong></span>
                            <span>Importados: <strong id="upt-imob-stat-imported" style="color:#16a34a;">0</strong></span>
                            <span>Fotos: <strong id="upt-imob-stat-photos" style="color:#2563eb;">0</strong></span>
                            <span>Erros: <strong id="upt-imob-stat-errors" style="color:#dc2626;">0</strong></span>
                        </div>
                        <div id="upt-imob-status-msg" style="padding:10px 14px;border-radius:6px;background:#f1f5f9;margin-bottom:12px;font-size:13px;">Preparando...</div>
                        <p>
                            <button type="button" class="button" id="upt-imob-cancel-btn" style="color:#dc2626;border-color:#dc2626;">Cancelar Importação</button>
                        </p>
                    </div>

                    <div id="upt-imob-done-section" style="display:none;">
                        <div style="padding:16px;border-radius:8px;background:#f0fdf4;border:1px solid #bbf7d0;margin:12px 0;">
                            <p style="margin:0 0 6px;font-weight:600;color:#16a34a;">Importação concluída com sucesso!</p>
                            <p id="upt-imob-done-stats" style="margin:0;color:#475569;font-size:13px;"></p>
                        </div>
                        <p>
                            <button type="button" class="button" id="upt-imob-new-btn">Importar Outro XML</button>
                        </p>
                    </div>
                </div>
            </div>

            <div class="upt-card" style="margin-top: 24px;">
                <div class="upt-card__header"><h2>Exportar dados (XML)</h2></div>
                <div class="upt-card__body">
                                        <p>Exporte itens do seu catálogo para um arquivo XML estruturado, contendo esquemas, campos e valores de cada item. Você pode exportar todos os itens ou apenas os de um esquema específico.</p>
                    <form method="post" action="">
                        <?php wp_nonce_field('upt_export_nonce'); ?>
                        <input type="hidden" name="action" value="upt_export_data">
                                                <input type="hidden" name="export_format" value="xml">
                        <div class="form-field">
                            <label for="schema_to_export">Esquema para Exportar:</label>
                            <select name="schema_to_export" id="schema_to_export">
                                <option value="all">Todos os Esquemas</option>
                                <?php
        $schemas = get_terms([
            'taxonomy' => 'catalog_schema',
            'hide_empty' => false,
        ]);
        if (!is_wp_error($schemas)) {
            foreach ($schemas as $schema) {
                echo '<option value="' . esc_attr($schema->slug) . '">' . esc_html($schema->name) . '</option>';
            }
        }
?>
                            </select>
                        </div>
                        <?php submit_button('Exportar Itens'); ?>
                    </form>
                </div>
            </div>
            <div class="upt-card">
                <div class="upt-card__header"><h2>Importar mídia (ZIP)</h2></div>
                <div class="upt-card__body">
                    <p>Envie um arquivo ZIP contendo imagens, vídeos ou outros arquivos de mídia usados nos seus itens do upt. As pastas dentro do ZIP serão mantidas como pastas na galeria do upt.</p>
                    <form method="post" action="" enctype="multipart/form-data">
                        <?php wp_nonce_field('upt_import_media', 'upt_import_media_nonce'); ?>
                        <input type="hidden" name="action" value="upt_import_media">
                        <p>
                            <input type="file" name="upt_media_zip" accept=".zip" required>
                        </p>
                        <p class="description">O ZIP pode conter arquivos soltos ou organizados em pastas. Extensões suportadas: JPG, JPEG, PNG, GIF, WEBP, SVG, MP4, MOV, WEBM, OGG.</p>
                        <p>
                            <button type="submit" class="button button-primary">Importar ZIP de mídia</button>
                        </p>
                    </form>
                </div>
            </div>

            <div class="upt-card" style="margin-top: 24px;">
                <div class="upt-card__header"><h2>Importar e Exportar Categorias (MD)</h2></div>
                <div class="upt-card__body">
                    <p>Use um arquivo <strong>.md</strong> para importar ou exportar categorias e subcategorias do upt no formato abaixo:</p>
                    <pre style="background:#f6f7f7;border:1px solid #dcdcde;border-radius:8px;padding:12px;max-width:720px;">CATEGORIA
  SUB CATEGORIA
  SUB CATEGORIA
CATEGORIA
  SUB CATEGORIA
  SUB CATEGORIA</pre>

                    <div style="display:flex;gap:18px;flex-wrap:wrap;">
                        <div style="flex:1;min-width:320px;">
                            <h3 style="margin:0 0 10px;">Importar categorias (.md)</h3>
                            <form method="post" action="" enctype="multipart/form-data">
                                <?php wp_nonce_field('upt_categories_md_nonce'); ?>
                                <input type="hidden" name="action" value="upt_import_categories_md">
                                <p style="margin:10px 0 12px;">
                                    <label for="upt_categories_parent" style="display:block;margin:0 0 6px;font-weight:600;">Categoria pai (opcional)</label>
                                    <?php
        wp_dropdown_categories([
            'taxonomy' => 'catalog_category',
            'hide_empty' => false,
            'hierarchical' => true,
            'depth' => 0,
            'name' => 'upt_categories_parent',
            'id' => 'upt_categories_parent',
            'show_option_none' => '— Nenhuma (nível raiz) —',
            'option_none_value' => 0,
            'selected' => isset($_POST['upt_categories_parent']) ? (int)$_POST['upt_categories_parent'] : 0,
            'walker' => class_exists('UPT_Category_Dropdown_Walker') ? new UPT_Category_Dropdown_Walker() : '',
        ]);
?>
                                </p>
                                <p>
                                    <input type="file" name="upt_categories_md" accept=".md,text/markdown,text/plain" required>
                                </p>
                                <p class="description">A importação cria as categorias e subcategorias exatamente como no arquivo. Se já existir uma categoria com o mesmo nome no mesmo nível (mesmo pai), ela será reutilizada.</p>
                                <p>
                                    <button type="submit" class="button button-primary">Importar Categorias</button>
                                </p>
                            </form>
                        </div>

                        <div style="flex:1;min-width:320px;">
                            <h3 style="margin:0 0 10px;">Exportar categorias (.md)</h3>
                            <form method="post" action="">
                                <?php wp_nonce_field('upt_categories_md_nonce'); ?>
                                <input type="hidden" name="action" value="upt_export_categories_md">
                                <p class="description">Gera um arquivo .md contendo todas as categorias e subcategorias do upt, no mesmo formato aceito na importação.</p>
                                <p>
                                    <button type="submit" class="button">Exportar Categorias</button>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (apply_filters('upt_show_import_format_panel', false)): ?>
            <div class="upt-card upt-importer-instructions">
                <div class="upt-card__header">
                    <h2>Formato dos arquivos (Importação)</h2>
                </div>
                <div class="upt-card__body">
                    <p>O arquivo gerado na exportação possui um objeto/estrutura com esquemas e itens. Para reimportar, utilize o mesmo arquivo ou siga uma das estruturas abaixo:</p>
                    <div class="form-field" style="margin-bottom: 12px;">
                        <label for="upt-format-preview">Visualizar formato:</label>
                        <select id="upt-format-preview">
                            <option value="json" selected>JSON</option>
                            <option value="xml">XML</option>
                        </select>
                    </div>
                    <div id="upt-json-sample">
<pre><code>{
  "schemas": { ... },
  "items": [
    {
      "title": "Nome do item",
      "schema_slug": "slug_do_esquema",
      "content": "...",
      "excerpt": "...",
      "status": "publish",
      "categories": [
        { "name": "Nome da categoria", "slug": "categoria-slug" }
      ],
      "featured_image": {
        "id": 123,
        "url": "https://seusite.com/wp-content/uploads/..."
      },
      "fields": {
        "id_do_campo_1": "valor",
        "id_do_campo_2": "outro valor"
      }
    }
  ]
}</code></pre>
                    </div>
                    <div id="upt-xml-sample" style="display:none;">
<pre><code><upt_export generated_at="2025-01-01 12:00:00" site_url="https://seusite.com">
  <schemas>
    <schema slug="slug_do_esquema">
      <label>Nome do esquema</label>
    </schema>
  </schemas>
  <items>
    <item>
      <id>123</id>
      <title>Nome do item</title>
      <schema_slug>slug_do_esquema</schema_slug>
      <content>...</content>
      <excerpt>...</excerpt>
      <status>publish</status>
      <categories>
        <category>
          <name>Nome da categoria</name>
          <slug>categoria-slug</slug>
        </category>
      </categories>
      <featured_image>
        <id>123</id>
        <url>https://seusite.com/wp-content/uploads/...</url>
      </featured_image>
      <fields>
        <field id="id_do_campo_1">valor</field>
        <field id="id_do_campo_2">outro valor</field>
      </fields>
    </item>
  </items>
</upt_export>
</code></pre>
                    </div>
                    
                    <script>
                    (function() {
                        var fileInput  = document.getElementById('import_json_file');
                        var previewBox = document.getElementById('upt-wp-preview');
                        var mappingBox = document.getElementById('upt-wp-mapping');

                        function resetPreview() {
                            if (previewBox) {
                                previewBox.style.display = 'none';
                                previewBox.innerHTML = '';
                            }
                            if (mappingBox) {
                                mappingBox.style.display = 'none';
                            }
                        }

                        function handleFileChange() {
                            if (!fileInput || !fileInput.files.length) {
                                resetPreview();
                                return;
                            }

                            var file = fileInput.files[0];

                            // Pergunta (uma vez por seleção) como tratar campos extras do XML.
                            // OK = usar campos do arquivo (pode criar campos extras)
                            // Cancel = manter apenas campos do upt (ignora extras)
                            try {
                                var useFields = window.confirm('Ao importar este XML, você quer usar os campos presentes no arquivo (criando campos extras quando necessário)?\n\nOK = Usar campos do arquivo\nCancelar = Manter apenas campos do upt');
                                var radioUse = document.getElementById('import_fields_mode_use');
                                var radioFc  = document.getElementById('import_fields_mode_upt');
                                if (radioUse && radioFc) {
                                    if (useFields) {
                                        radioUse.checked = true;
                                    } else {
                                        radioFc.checked = true;
                                    }
                                }
                            } catch (e) { /* noop */ }

                            if (!file.name.match(/\.xml$/i)) {
                                resetPreview();
                                if (previewBox) {
                                    previewBox.style.display = 'block';
                                    previewBox.className = 'notice notice-error';
                                    previewBox.innerHTML = '<p>Envie um arquivo XML exportado do WordPress (.xml).</p>';
                                }
                                return;
                            }

                            var reader = new FileReader();
                            reader.onload = function(e) {
                                try {
                                    var parser = new DOMParser();
                                    var xml    = parser.parseFromString(e.target.result, 'text/xml');
                                    var items  = xml.getElementsByTagName('item');

                                    if (!items.length) {
                                        resetPreview();
                                        if (previewBox) {
                                            previewBox.style.display = 'block';
                                            previewBox.className = 'notice notice-warning';
                                            previewBox.innerHTML = '<p>Não foram encontrados itens no XML informado.</p>';
                                        }
                                        return;
                                    }

                                    var sampleItem = items[0];

                                    function extractText(nodeList) {
                                        if (!nodeList || !nodeList.length) return '';
                                        var out = [];
                                        for (var i = 0; i < nodeList.length; i++) {
                                            if (nodeList[i].textContent) {
                                                out.push(nodeList[i].textContent);
                                            }
                                        }
                                        return out.join(', ');
                                    }

                                    function pick(tag) {
                                        return extractText(sampleItem.getElementsByTagName(tag));
                                    }

                                    function pickWP(tag) {
                                        return extractText(sampleItem.getElementsByTagName('wp:' + tag));
                                    }

                                    var title    = pick('title');
                                    var link     = pick('link');
                                    var postId   = pickWP('post_id');
                                    var date     = pickWP('post_date');
                                    var status   = pickWP('status');
                                    var category = extractText(sampleItem.getElementsByTagName('category'));
                                    var excerpt  = extractText(sampleItem.getElementsByTagName('excerpt:encoded'));
                                    var content  = extractText(sampleItem.getElementsByTagName('content:encoded'));

                                    var totalItems = items.length;

                                    if (previewBox) {
                                        var html = '';
                                        html += '<p><strong>Pré-visualização do XML WordPress</strong></p>';
                                        html += '<p>Itens detectados: <strong>' + totalItems + '</strong></p>';
                                        html += '<p><em>Exemplo do primeiro item:</em></p>';
                                        html += '<ul>';
                                        html += '<li><strong>Título:</strong> ' + (title || '(vazio)') + '</li>';
                                        html += '<li><strong>Link:</strong> ' + (link || '(vazio)') + '</li>';
                                        html += '<li><strong>Post ID:</strong> ' + (postId || '(vazio)') + '</li>';
                                        html += '<li><strong>Data:</strong> ' + (date || '(vazio)') + '</li>';
                                        html += '<li><strong>Categoria:</strong> ' + (category || '(vazia)') + '</li>';
                                        html += '<li><strong>Resumo:</strong> ' + (excerpt || '(vazio)') + '</li>';
                                        html += '<li><strong>Conteúdo:</strong> ' + (content || '(vazio)') + '</li>';
                                        html += '</ul>';
                                        previewBox.className = 'notice notice-info';
                                        previewBox.innerHTML = html;
                                        previewBox.style.display = 'block';
                                    }

                                    if (mappingBox) {
                                        mappingBox.style.display = 'block';
                                    }
                                } catch (err) {
                                    resetPreview();
                                    if (previewBox) {
                                        previewBox.style.display = 'block';
                                        previewBox.className = 'notice notice-error';
                                        previewBox.innerHTML = '<p>Não foi possível ler o XML. Verifique o arquivo e tente novamente.</p>';
                                    }
                                }
                            };

                            reader.readAsText(file);
                        }

                        if (fileInput) {
                            fileInput.addEventListener('change', function() {
                                if (!fileInput.files.length) {
                                    resetPreview();
                                    return;
                                }
                                handleFileChange();
                            });
                        }
                    })();
                    </script>
                </div>
            </div>
            <?php
        endif; ?>
        </div>
        <?php
    }


    public static function create_admin_page_schema_builder()
    {
?>
        <div class="wrap upt-wrap">
            <h1>Construtor de Esquemas e Campos</h1>
            <?php
        if ($success_msg = get_transient('upt_settings_success')) {
            add_settings_error('upt_notices', 'schema_success', $success_msg, 'success');
            delete_transient('upt_settings_success');
        }
        settings_errors();
?>

            <div class="upt-builder">
                <div class="upt-builder__sidebar">
                    <div class="upt-card">
                        <div class="upt-card__header">
                            <h2>Esquemas</h2>
                        </div>
                        <?php self::render_schema_list(); ?>
                        <div class="upt-card__body">
                             <?php self::render_add_schema_form(); ?>
                        </div>
                    </div>
                </div>

                <div class="upt-builder__main">
                    <div class="upt-card">
                         <div class="upt-card__body">
                            <?php
        if (isset($_GET['schema']) && !empty($_GET['schema'])) {
            $schema_slug = sanitize_text_field($_GET['schema']);
            self::render_field_list($schema_slug);
            if (apply_filters('upt_show_schema_items_order_admin', false)) {
                self::render_schema_items_order($schema_slug);
            }
            self::render_add_field_form($schema_slug);
        }
        else {
            echo '<p>Por favor, selecione um esquema à esquerda para começar.</p>';
        }
?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }


    private static function render_schema_list()
    {
        $schemas = get_terms(['taxonomy' => 'catalog_schema', 'hide_empty' => false]);
        if (empty($schemas)) {
            echo '<div class="upt-card__body"><p>Nenhum esquema encontrado.</p></div>';
            return;
        }

        $rename_nonce = wp_create_nonce('upt_rename_schema_nonce');

        // Tela de confirmação de exclusão de esquema, com opções para os itens.
        $delete_confirm_id = isset($_GET['schema_delete_confirm']) ? absint($_GET['schema_delete_confirm']) : 0;
        if ($delete_confirm_id) {
            $schema_term = get_term($delete_confirm_id, 'catalog_schema');
            if ($schema_term && !is_wp_error($schema_term)) {
                $current_delete_slug = $schema_term->slug;

                $other_schemas = [];
                foreach ($schemas as $schema_obj) {
                    if ($schema_obj->slug !== $current_delete_slug) {
                        $other_schemas[] = $schema_obj;
                    }
                }

                echo '<div class="upt-card upt-card--danger" style="margin-bottom: 16px;">';
                echo '<div class="upt-card__header"><h3>' . esc_html__('Excluir esquema', 'upt') . '</h3></div>';
                echo '<div class="upt-card__body">';
                echo '<p>' . sprintf(
                    esc_html__('Você está prestes a excluir o esquema "%s". O que deseja fazer com os itens associados a ele?', 'upt'),
                    esc_html($schema_term->name)
                ) . '</p>';

                echo '<form method="post" action="">';
                echo '<input type="hidden" name="action" value="upt_delete_schema_do" />';
                wp_nonce_field('upt_delete_schema_do_nonce');
                echo '<input type="hidden" name="schema_id" value="' . esc_attr($schema_term->term_id) . '" />';

                echo '<p><label><input type="radio" name="delete_mode" value="keep" checked> ' .
                    esc_html__('Manter itens sem esquema (remover apenas a relação com este esquema).', 'upt') .
                    '</label></p>';

                if (!empty($other_schemas)) {
                    echo '<p><label><input type="radio" name="delete_mode" value="move"> ' .
                        esc_html__('Mover itens para outro esquema:', 'upt') .
                        '</label><br />';
                    echo '<select name="target_schema" style="min-width: 220px; margin-top: 4px;">';
                    foreach ($other_schemas as $schema_opt) {
                        echo '<option value="' . esc_attr($schema_opt->slug) . '">' . esc_html($schema_opt->name) . '</option>';
                    }
                    echo '</select>';
                    echo '</p>';
                }

                echo '<p><label><input type="radio" name="delete_mode" value="delete_items"> ' .
                    esc_html__('Apagar itens deste esquema (enviar para a lixeira).', 'upt') .
                    '</label></p>';

                submit_button(__('Confirmar exclusão do esquema', 'upt'), 'primary', 'submit', false);
                echo '&nbsp;<a href="' . esc_url(admin_url('admin.php?page=upt_schema_builder')) . '" class="button button-secondary">' .
                    esc_html__('Cancelar', 'upt') . '</a>';

                echo '</form>';
                echo '</div>';
                echo '</div>';
            }
        }

        echo '<ul class="upt-schema-list">';
        foreach ($schemas as $schema) {
            $url = admin_url('admin.php?page=upt_schema_builder&schema=' . $schema->slug);
            $is_current = isset($_GET['schema']) && $_GET['schema'] === $schema->slug;

            $delete_url = wp_nonce_url(
                admin_url('admin.php?page=upt_schema_builder&action=delete_schema&schema_id=' . $schema->term_id),
                'upt_delete_schema_nonce'
            );
?>
            <li class="<?php echo $is_current ? 'current' : ''; ?>">
                <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($schema->name); ?></a>
                <button type="button"
                        class="button-icon-only upt-schema-rename"
                        title="Renomear esquema"
                        data-schema-slug="<?php echo esc_attr($schema->slug); ?>"
                        data-schema-name="<?php echo esc_attr($schema->name); ?>">
                    <span class="upt-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z" />
                        </svg>
                    </span>
                </button>
                <a href="<?php echo esc_url($delete_url); ?>"
                   class="button-icon-only delete-link"
                   title="<?php esc_attr_e('Excluir este esquema', 'upt'); ?>">
                    <span class="upt-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true">
                            <path d="M3 6h18" />
                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                            <path d="M10 11v6" />
                            <path d="M14 11v6" />
                        </svg>
                    </span>
                </a>
            </li>
            <?php
        }
        echo '</ul>';
        echo '<input type="hidden" id="upt_rename_schema_nonce" value="' . esc_attr($rename_nonce) . '" />';
    }
    private static function render_add_schema_form()
    {
?>
        <h3>Adicionar Novo Esquema</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="upt_add_schema">
            <?php wp_nonce_field('upt_add_schema_nonce'); ?>
            <div class="form-field">
                <label for="schema_name">Nome</label>
                <input name="schema_name" id="schema_name" type="text" required>
            </div>
            <div class="form-field">
                <label for="schema_items_limit_mode">Limite de itens</label>
                <select name="schema_items_limit_mode" id="schema_items_limit_mode">
                    <option value="unlimited">Ilimitado</option>
                    <option value="limited">Limitado</option>
                </select>
                <p class="description">Escolha "Limitado" para definir um número máximo de itens permitidos para este esquema.</p>
            </div>
            <div class="form-field">
                <label for="schema_items_limit">Quantidade máxima de itens</label>
                <input name="schema_items_limit" id="schema_items_limit" type="number" min="1" step="1">
                <p class="description">Deixe vazio para não limitar. Só será usado quando o modo estiver em "Limitado".</p>
            </div>
            <?php submit_button('Adicionar Esquema'); ?>
        </form>
        <?php
    }

    private static function render_field_list($schema_slug)
    {
        $fields = UPT_Schema_Store::get_fields_for_schema($schema_slug);
        $schema_term = get_term_by('slug', $schema_slug, 'catalog_schema');

        $schema_name = '';
        if ($schema_term && !is_wp_error($schema_term)) {
            $schema_name = $schema_term->name;
        }

        echo '<h2>Campos para "' . esc_html($schema_name) . '"</h2>';
        // Configuração de limite (máximo) e mínimo de itens por esquema
        $all_schemas = UPT_Schema_Store::get_schemas();
        $current_limit = isset($all_schemas[$schema_slug]['items_limit']) ? absint($all_schemas[$schema_slug]['items_limit']) : 0;
        $current_min = isset($all_schemas[$schema_slug]['items_min']) ? absint($all_schemas[$schema_slug]['items_min']) : 0;
        $limit_min_per_category = !empty($all_schemas[$schema_slug]['items_limit_per_category']);
        $limit_max_per_category = isset($all_schemas[$schema_slug]['items_limit_max_per_category'])
            ? (bool)$all_schemas[$schema_slug]['items_limit_max_per_category']
            // Fallback mantém comportamento anterior para quem já ativou o flag antigo
             : $limit_min_per_category;
?>
        <form method="post" action="" style="margin: 8px 0 16px 0;">
            <input type="hidden" name="action" value="upt_save_schema_settings">
            <input type="hidden" name="schema_slug" value="<?php echo esc_attr($schema_slug); ?>">
            <?php wp_nonce_field('upt_save_schema_settings_nonce'); ?>
            <label for="schema_items_limit"><strong>Limite máximo de itens:</strong></label>
            <input name="schema_items_limit" id="schema_items_limit" type="number" min="0" step="1" value="<?php echo esc_attr($current_limit); ?>" style="width: 90px; margin-left: 6px;">
            <span class="description">0 ou vazio = ilimitado.</span>

            <span style="display:inline-block; width: 14px;"></span>

            <label for="schema_items_min"><strong>Mínimo de itens:</strong></label>
            <input name="schema_items_min" id="schema_items_min" type="number" min="0" step="1" value="<?php echo esc_attr($current_min); ?>" style="width: 90px; margin-left: 6px;">
            <span class="description">0 = sem mínimo.</span>

            <span style="display:inline-block; width: 14px;"></span>

            <label for="schema_items_limit_per_category"><strong>Mínimo por categoria:</strong></label>
            <input type="checkbox" name="schema_items_limit_per_category" id="schema_items_limit_per_category" value="1" <?php checked($limit_min_per_category); ?> style="margin-left: 6px;">
            <span class="description">Aplica o mínimo a cada categoria do esquema.</span>

            <span style="display:inline-block; width: 14px;"></span>

            <label for="schema_items_limit_max_per_category"><strong>Máximo por categoria:</strong></label>
            <input type="checkbox" name="schema_items_limit_max_per_category" id="schema_items_limit_max_per_category" value="1" <?php checked($limit_max_per_category); ?> style="margin-left: 6px;">
            <span class="description">Quando ativo, o limite máximo é contado por categoria (não pelo total do esquema).</span>
            <?php submit_button('Salvar limite', 'secondary', 'submit', false); ?>
        </form>
        <?php

        if (empty($fields)) {
            echo '<p>Nenhum campo adicionado ainda.</p>';
            return;
        }
?>
        <table class="upt-table upt-fields-table" data-schema="<?php echo esc_attr($schema_slug); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('upt_ajax_nonce')); ?>">
            <thead><tr><th>Rótulo</th><th>ID / Meta Key</th><th>Tipo</th><th>Obrigatório</th><th>Ações</th></tr></thead>
            <tbody>
                <?php foreach ($fields as $field): ?>
                    <tr class="upt-field-row" data-field-id="<?php echo esc_attr($field['id']); ?>">
                        <td><?php echo esc_html($field['label']); ?></td><td><code><?php echo esc_html($field['id']); ?></code></td><td><?php echo esc_html($field['type']); ?></td><td><?php echo $field['required'] ? 'Sim' : 'Não'; ?></td>
                        <td>
                            <?php
            $edit_url = admin_url('admin.php?page=upt_schema_builder&schema=' . $schema_slug . '&action=edit_field&field_id=' . $field['id']);
            $delete_url = wp_nonce_url(admin_url('admin.php?page=upt_schema_builder&schema=' . $schema_slug . '&action=delete_field&field_id=' . $field['id']), 'upt_delete_field_nonce');
?>
                            <a href="<?php echo esc_url($edit_url); ?>">Editar</a> | 
                            <a href="<?php echo esc_url($delete_url); ?>" class="delete-link" onclick="return confirm('Tem certeza?');">Excluir</a>
                        </td>
                    </tr>
                <?php
        endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private static function render_add_field_form($schema_slug)
    {
        $field_to_edit = null;
        $is_editing = false;
        if (isset($_GET['action']) && $_GET['action'] === 'edit_field' && isset($_GET['field_id'])) {
            $is_editing = true;
            $all_fields = UPT_Schema_Store::get_fields_for_schema($schema_slug);
            foreach ($all_fields as $field) {
                if ($field['id'] === $_GET['field_id']) {
                    $field_to_edit = $field;
                    break;
                }
            }
        }

        $label_val = $is_editing && isset($field_to_edit['label']) ? $field_to_edit['label'] : '';
        $hint_val = $is_editing && isset($field_to_edit['hint']) ? $field_to_edit['hint'] : '';
        $type_val = $is_editing && isset($field_to_edit['type']) ? $field_to_edit['type'] : '';
        $options_val = $is_editing && isset($field_to_edit['options']) ? str_replace('|', "\r\n", $field_to_edit['options']) : '';
        $time_format_val = $is_editing && isset($field_to_edit['time_format']) ? $field_to_edit['time_format'] : '';
        $unit_options_val = $is_editing && isset($field_to_edit['unit_options']) ? str_replace('|', "\r\n", $field_to_edit['unit_options']) : '';
        $schema_filter_val = $is_editing && isset($field_to_edit['schema_filter']) ? $field_to_edit['schema_filter'] : '';
        $required_checked = $is_editing && !empty($field_to_edit['required']) ? 'checked' : '';
        $multiple_checked = $is_editing && !empty($field_to_edit['multiple']) ? 'checked' : '';
        $allow_new_checked = $is_editing && !empty($field_to_edit['allow_new']) ? 'checked' : '';
        $allow_rename_option_checked = $is_editing && !empty($field_to_edit['allow_rename_option']) ? 'checked' : '';
        $allow_delete_option_checked = $is_editing && !empty($field_to_edit['allow_delete_option']) ? 'checked' : '';
        $allow_new_category_checked = 'checked';
        if ($is_editing) {
            if (isset($field_to_edit['allow_new_category'])) {
                $allow_new_category_checked = !empty($field_to_edit['allow_new_category']) ? 'checked' : '';
            }
        }

        // Subcategorias (campo taxonomy)
        $enable_subcategories_checked = '';
        if ($is_editing && isset($field_to_edit['enable_subcategories'])) {
            $enable_subcategories_checked = !empty($field_to_edit['enable_subcategories']) ? 'checked' : '';
        }

        $max_length_val = $is_editing && isset($field_to_edit['max_length']) ? intval($field_to_edit['max_length']) : '';
        $rows_val = $is_editing && isset($field_to_edit['rows']) ? intval($field_to_edit['rows']) : '';

        $blog_excerpt_required_checked = '';
        if ($is_editing && !empty($field_to_edit['excerpt_required'])) {
            $blog_excerpt_required_checked = 'checked';
        }
        $blog_excerpt_max_length_val = $is_editing && isset($field_to_edit['excerpt_max_length']) ? intval($field_to_edit['excerpt_max_length']) : '';

?>
        <hr><h3><?php echo $is_editing ? 'Editando Campo' : 'Adicionar Novo Campo'; ?></h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="upt_save_field">
            <input type="hidden" name="schema_slug" value="<?php echo esc_attr($schema_slug); ?>">
            <?php if ($is_editing): ?>
                <input type="hidden" name="original_field_id" value="<?php echo esc_attr($field_to_edit['id']); ?>">
            <?php
        endif; ?>
            <?php wp_nonce_field('upt_save_field_nonce'); ?>

            <div class="form-field"><label for="field_label">Rótulo do Campo</label><input name="field_label" id="field_label" type="text" value="<?php echo esc_attr($label_val); ?>" required></div>
            <div class="form-field">
                <label for="field_hint">Texto de Ajuda (Opcional)</label>
                <input name="field_hint" id="field_hint" type="text" value="<?php echo esc_attr($hint_val); ?>">
                <p class="description">Este texto aparecerá abaixo do campo no formulário para guiar o usuário.</p>
            </div>
            <div class="form-field">
                <label for="field_type">Tipo de Campo</label>
                <select name="field_type" id="field_type">
                    <optgroup label="Campos Nativos">
                        <option value="core_title" <?php selected($type_val, 'core_title'); ?>>Título Principal (Nativo)</option>
                        <option value="core_content" <?php selected($type_val, 'core_content'); ?>>Conteúdo Principal / Descrição (Nativo)</option>
                        <option value="core_featured_image" <?php selected($type_val, 'core_featured_image'); ?>>Imagem Destacada (Nativa)</option>
                        <option value="blog_post" <?php selected($type_val, 'blog_post'); ?>>Postagem Completa (Conteúdo + Resumo)</option>
                    </optgroup>
                    <optgroup label="Campos Customizados">
                        <option value="text" <?php selected($type_val, 'text'); ?>>Texto (uma linha)</option>
                        <option value="textarea" <?php selected($type_val, 'textarea'); ?>>Texto (múltiplas linhas)</option>
                        <option value="list" <?php selected($type_val, 'list'); ?>>Lista de Itens (uma por linha)</option>
                        <option value="wysiwyg" <?php selected($type_val, 'wysiwyg'); ?>>Editor de Texto (WYSIWYG)</option>
                        <option value="number" <?php selected($type_val, 'number'); ?>>Número</option>
                        <option value="price" <?php selected($type_val, 'price'); ?>>Preço (R$)</option>
                        <option value="url" <?php selected($type_val, 'url'); ?>>URL</option>
                        <option value="date" <?php selected($type_val, 'date'); ?>>Data</option>
                        <option value="time" <?php selected($type_val, 'time'); ?>>Tempo (Hora)</option>
                        <option value="image" <?php selected($type_val, 'image'); ?>>Imagem Única</option>
                        <option value="video" <?php selected($type_val, 'video'); ?>>Vídeo (Upload)</option>
                        <option value="pdf" <?php selected($type_val, 'pdf'); ?>>PDF (Upload)</option>
                        <option value="select" <?php selected($type_val, 'select'); ?>>Caixa de Seleção</option>
                        <option value="relationship" <?php selected($type_val, 'relationship'); ?>>Relação</option>
                        <option value="taxonomy" <?php selected($type_val, 'taxonomy'); ?>>Seleção de Categoria</option>
                        <option value="gallery" <?php selected($type_val, 'gallery'); ?>>Galeria de Mídias (Imagens, Vídeos, PDFs)</option>
                        <option value="unit_measure" <?php selected($type_val, 'unit_measure'); ?>>Unidade de Medida</option>
                    </optgroup>
                </select>
            </div>
            <div class="form-field field-options-wrapper" style="display:none;">
                <label for="field_options">Opções (uma por linha)</label>
                <textarea name="field_options" id="field_options" rows="5"><?php echo esc_textarea($options_val); ?></textarea>
                <label><input name="field_allow_new" type="checkbox" value="1" <?php echo $allow_new_checked; ?>> Permitir adição de novas opções no formulário?</label>
                <div class="upt-allow-new-suboptions" style="margin-top:6px; margin-left:18px;">
                    <label style="display:block; margin:4px 0;"><input name="field_allow_rename_option" type="checkbox" value="1" <?php echo $allow_rename_option_checked; ?>> Permitir renomear opções no formulário?</label>
                    <label style="display:block; margin:4px 0;"><input name="field_allow_delete_option" type="checkbox" value="1" <?php echo $allow_delete_option_checked; ?>> Permitir excluir opções no formulário?</label>
                </div>
            </div>
             <div class="form-field field-time-wrapper" style="display:none;">
                <label for="field_time_format">Formato da Hora</label>
                <select name="field_time_format" id="field_time_format">
                    <option value="24h" <?php selected($time_format_val, '24h'); ?>>24 Horas (ex: 14:30)</option>
                    <option value="12h" <?php selected($time_format_val, '12h'); ?>>12 Horas (ex: 02:30 PM)</option>
                </select>
            </div>
            <div class="form-field field-unit-measure-wrapper" style="display:none;">
                <label for="field_unit_options">Unidades de Medida (uma por linha)</label>
                <textarea name="field_unit_options" id="field_unit_options" rows="10" placeholder="Exemplo:
kg
g
m
m²
m³
km
cm
mm
L
mL
un
caixa
pacote"><?php echo esc_textarea(isset($unit_options_val) ? $unit_options_val : ''); ?></textarea>
                <p class="description">Adicione as unidades de medida disponíveis para seleção. Cada linha representa uma opção.</p>
            </div>
            <div class="form-field field-relationship-wrapper" style="display:none;">
                <label for="field_relationship_schema">Filtrar itens por Esquema</label>
                <select name="field_relationship_schema" id="field_relationship_schema">
                    <option value="all" <?php selected($schema_filter_val, 'all'); ?>>Todos os Esquemas</option>
                    <?php
        $schemas = get_terms(['taxonomy' => 'catalog_schema', 'hide_empty' => false]);
        if (!is_wp_error($schemas)) {
            foreach ($schemas as $schema) {
                echo '<option value="' . esc_attr($schema->slug) . '" ' . selected($schema_filter_val, $schema->slug, false) . '>' . esc_html($schema->name) . '</option>';
            }
        }
?>
                </select>
                <p class="description">Selecione um esquema para limitar os itens que podem ser relacionados.</p>
            </div>
            <div class="form-field"><label><input name="field_required" type="checkbox" value="1" <?php echo $required_checked; ?>> Obrigatório?</label></div>
            <div class="form-field field-multiple-wrapper" style="display:none;">
                <label><input name="field_multiple" type="checkbox" value="1" <?php echo $multiple_checked; ?>> Permitir múltiplas seleções?</label>
            </div>
            <div class="form-field field-taxonomy-wrapper" style="display:none;">
                <label><input name="field_allow_new_category" type="checkbox" value="1" <?php echo $allow_new_category_checked; ?>> Permitir adicionar novas categorias no formulário?</label>
                <p class="description">Mantém o botão de criar/gerenciar categorias visível para este campo.</p>

                <label style="display:block; margin-top:10px;"><input name="field_enable_subcategories" type="checkbox" value="1" <?php echo $enable_subcategories_checked; ?>> Habilitar subcategorias (campo dependente)</label>
                <p class="description">Quando ativo, ao selecionar uma categoria no formulário do upt, será exibido automaticamente um segundo campo de subcategorias (vinculadas à categoria selecionada).</p>

                <?php
        $subcategories_label_val = 'Subcategoria';
        if ($is_editing && isset($field_to_edit['subcategories_label']) && $field_to_edit['subcategories_label'] !== '') {
            $subcategories_label_val = (string)$field_to_edit['subcategories_label'];
        }

        $subcategories_required_checked = '';
        if ($is_editing && !empty($field_to_edit['subcategories_required'])) {
            $subcategories_required_checked = 'checked';
        }
?>
                <label for="field_subcategories_label" style="margin-top:10px; display:block;">Rótulo do campo de subcategorias</label>
                <input name="field_subcategories_label" id="field_subcategories_label" type="text" value="<?php echo esc_attr($subcategories_label_val); ?>" placeholder="Ex: Categoria" />
                <p class="description">Texto exibido acima do select de subcategorias no formulário do upt quando as subcategorias estiverem habilitadas.</p>

                <label style="display:block; margin-top:10px;"><input name="field_subcategories_required" type="checkbox" value="1" <?php echo $subcategories_required_checked; ?>> Subcategoria obrigatória?</label>
                <p class="description">Quando ativo, o usuário precisa selecionar uma subcategoria (quando existir) antes de salvar o item.</p>
            </div>

            <div class="form-field field-blog-post-wrapper" style="display:none;">
                <label><input name="field_excerpt_required" type="checkbox" value="1" <?php echo $blog_excerpt_required_checked; ?>> Resumo obrigatório?</label>
                <p class="description">Quando ativo, o usuário precisa preencher o resumo do post.</p>

                <label for="field_excerpt_max_length" style="margin-top: 10px; display:block;">Limite de caracteres do resumo</label>
                <input name="field_excerpt_max_length" id="field_excerpt_max_length" type="number" min="0" value="<?php echo esc_attr($blog_excerpt_max_length_val); ?>">
                <p class="description">Deixe vazio ou 0 para não limitar a quantidade de caracteres do resumo.</p>
            </div>
            
            <div class="form-field field-maxlength-wrapper">
                <label for="field_max_length">Limite de caracteres</label>
                <input name="field_max_length" id="field_max_length" type="number" min="0" value="<?php echo esc_attr($max_length_val); ?>">
                <p class="description">Deixe vazio ou 0 para não limitar a quantidade de caracteres.</p>
            </div>
            <div class="form-field field-rows-wrapper">
                <label for="field_rows">Linhas visíveis (para campos de múltiplas linhas)</label>
                <input name="field_rows" id="field_rows" type="number" min="0" value="<?php echo esc_attr($rows_val); ?>">
                <p class="description">Define quantas linhas serão exibidas por padrão nos campos de texto de múltiplas linhas.</p>
            </div>
<?php submit_button($is_editing ? 'Atualizar Campo' : 'Salvar Campo'); ?>
        </form>
        <?php
    }

    public static function handle_export_actions()
    {
        if (!isset($_POST['action']) || $_POST['action'] !== 'upt_export_data') {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'upt_export_nonce')) {
            return;
        }

        $export_schema = isset($_POST['schema_to_export']) ? sanitize_text_field($_POST['schema_to_export']) : 'all';
        $export_format = isset($_POST['export_format']) ? sanitize_text_field($_POST['export_format']) : 'json';

        $query_args = [
            'post_type' => 'catalog_item',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ];

        if ($export_schema !== 'all') {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => 'catalog_schema',
                    'field' => 'slug',
                    'terms' => $export_schema,
                ],
            ];
        }

        $all_schemas = class_exists('UPT_Schema_Store')
            ?UPT_Schema_Store::get_schemas()
            : [];

        $schemas_for_export = $all_schemas;

        if ($export_schema !== 'all') {
            $schemas_for_export = isset($all_schemas[$export_schema])
                ? [$export_schema => $all_schemas[$export_schema]]
                : [];
        }

        $items = [];
        $query = new WP_Query($query_args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                // Esquema do item
                $schema_terms = wp_get_post_terms($post_id, 'catalog_schema');
                $schema_slug = '';
                $schema_label = '';

                if (!is_wp_error($schema_terms) && !empty($schema_terms)) {
                    $schema_slug = $schema_terms[0]->slug;
                    if (isset($all_schemas[$schema_slug]['label'])) {
                        $schema_label = $all_schemas[$schema_slug]['label'];
                    }
                    elseif (isset($all_schemas[$schema_slug]['name'])) {
                        $schema_label = $all_schemas[$schema_slug]['name'];
                    }
                }

                // Categorias
                $category_terms = wp_get_post_terms($post_id, 'catalog_category');
                $categories = [];

                if (!is_wp_error($category_terms) && !empty($category_terms)) {
                    foreach ($category_terms as $term) {
                        $categories[] = [
                            'term_id' => (int)$term->term_id,
                            'slug' => $term->slug,
                            'name' => $term->name,
                            'parent' => (int)$term->parent,
                        ];
                    }
                }

                // Imagem destacada
                $thumb_id = get_post_thumbnail_id($post_id);
                $thumb_url = $thumb_id ? wp_get_attachment_url($thumb_id) : '';

                // Valores de campos
                $fields_values = [];

                if ($schema_slug && isset($all_schemas[$schema_slug]['fields']) && is_array($all_schemas[$schema_slug]['fields'])) {
                    foreach ($all_schemas[$schema_slug]['fields'] as $field_def) {
                        if (empty($field_def['id'])) {
                            continue;
                        }
                        $field_id = $field_def['id'];
                        $field_type = isset($field_def['type']) ? $field_def['type'] : '';

                        if (isset($field_def['type']) && in_array($field_def['type'], ['core_title', 'core_content', 'core_featured_image', 'taxonomy'], true)) {
                            continue;
                        }

                        $value = get_post_meta($post_id, $field_id, true);

                        // Para campos de imagem, exporta a URL (quando possível) em vez do ID bruto,
                        // facilitando a reanexação em outro site.
                        if ($field_type === 'image' && !empty($value) && is_numeric($value)) {
                            $maybe_url = wp_get_attachment_url((int)$value);
                            if ($maybe_url) {
                                $value = $maybe_url;
                            }
                        }

                        if ($value !== '' && $value !== null) {
                            $fields_values[$field_id] = $value;
                        }
                    }
                }
                else {
                    // Fallback: exporta todos os meta não protegidos
                    $raw_meta = get_post_meta($post_id);
                    foreach ($raw_meta as $meta_key => $values) {
                        if (strpos($meta_key, '_') === 0) {
                            continue;
                        }
                        if (isset($values[0])) {
                            $fields_values[$meta_key] = $values[0];
                        }
                    }
                }

                $item = [
                    'id' => (int)$post_id,
                    'title' => get_the_title($post_id),
                    'content' => get_post_field('post_content', $post_id),
                    'excerpt' => get_post_field('post_excerpt', $post_id),
                    'status' => get_post_status($post_id),
                    'slug' => get_post_field('post_name', $post_id),
                    'schema_slug' => $schema_slug,
                    'schema' => [
                        'slug' => $schema_slug,
                        'label' => $schema_label,
                    ],
                    'categories' => $categories,
                    'featured_image' => [
                        'id' => (int)$thumb_id,
                        'url' => $thumb_url,
                    ],
                    'fields' => $fields_values,
                ];

                // Mapa de mídias usadas neste item (imagem, vídeo, galeria).
                $media_map = self::build_media_map_for_export($schema_slug, $all_schemas, $fields_values, $thumb_id, $thumb_url);
                if (!empty($media_map)) {
                    $item['media_map'] = $media_map;
                }

                $items[] = $item;
            }
            wp_reset_postdata();
        }

        $export_data = [
            'generated_at' => current_time('mysql'),
            'site_url' => get_site_url(),
            'schemas' => $schemas_for_export,
            'items' => $items,
        ];

        if (function_exists('nocache_headers')) {
            nocache_headers();
        }

        if ($export_format === 'xml') {
            // Exporta em formato WordPress WXR compatível.
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->formatOutput = true;

            // Raiz <rss>.
            $rss = $dom->createElement('rss');
            $rss->setAttribute('version', '2.0');
            $rss->setAttribute('xmlns:excerpt', 'http://wordpress.org/export/1.2/excerpt/');
            $rss->setAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
            $rss->setAttribute('xmlns:wfw', 'http://wellformedweb.org/CommentAPI/');
            $rss->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
            $rss->setAttribute('xmlns:wp', 'http://wordpress.org/export/1.2/');
            $dom->appendChild($rss);

            // Canal.
            $channel = $dom->createElement('channel');
            $rss->appendChild($channel);

            // Título do site e metadados básicos.
            $site_title = get_bloginfo('name');
            $channel->appendChild($dom->createElement('title', $site_title));
            $channel->appendChild($dom->createElement('link', $export_data['site_url']));
            $channel->appendChild($dom->createElement('description', sprintf('Exportação do catálogo upt em %s', $export_data['generated_at'])));

            // Comment: generator info.
            $channel->appendChild($dom->createComment('Generated by upt as WordPress WXR compatible export.'));

            // Esquemas e campos exportados para recriar definições na importação.
            if (!empty($schemas_for_export)) {
                $schemas_node = $dom->createElement('upt_schemas');

                foreach ($schemas_for_export as $schema_slug => $schema_def) {
                    if (!$schema_slug) {
                        continue;
                    }

                    $schema_el = $dom->createElement('schema');
                    $schema_el->setAttribute('slug', $schema_slug);

                    if (isset($schema_def['label']) && $schema_def['label'] !== '') {
                        $schema_el->setAttribute('label', $schema_def['label']);
                    }
                    elseif (isset($schema_def['name']) && $schema_def['name'] !== '') {
                        $schema_el->setAttribute('label', $schema_def['name']);
                    }

                    if (isset($schema_def['items_limit'])) {
                        $schema_el->setAttribute('items_limit', (string)(int)$schema_def['items_limit']);
                    }

                    if (isset($schema_def['items_min'])) {
                        $schema_el->setAttribute('items_min', (string)(int)$schema_def['items_min']);
                    }

                    if (isset($schema_def['items_limit_per_category'])) {
                        $schema_el->setAttribute('items_limit_per_category', $schema_def['items_limit_per_category'] ? '1' : '0');
                    }

                    if (isset($schema_def['items_limit_max_per_category'])) {
                        $schema_el->setAttribute('items_limit_max_per_category', $schema_def['items_limit_max_per_category'] ? '1' : '0');
                    }

                    if (isset($schema_def['fields']) && is_array($schema_def['fields']) && !empty($schema_def['fields'])) {
                        $fields_el = $dom->createElement('fields');

                        foreach ($schema_def['fields'] as $field_def) {
                            if (!isset($field_def['id'])) {
                                continue;
                            }

                            $field_el = $dom->createElement('field');
                            $field_el->setAttribute('id', (string)$field_def['id']);

                            if (isset($field_def['type'])) {
                                $field_el->setAttribute('type', (string)$field_def['type']);
                            }
                            if (isset($field_def['required'])) {
                                $field_el->setAttribute('required', $field_def['required'] ? '1' : '0');
                            }
                            if (isset($field_def['multiple'])) {
                                $field_el->setAttribute('multiple', $field_def['multiple'] ? '1' : '0');
                            }
                            if (isset($field_def['allow_new'])) {
                                $field_el->setAttribute('allow_new', $field_def['allow_new'] ? '1' : '0');
                            }
                            if (isset($field_def['allow_rename_option'])) {
                                $field_el->setAttribute('allow_rename_option', $field_def['allow_rename_option'] ? '1' : '0');
                            }
                            if (isset($field_def['allow_delete_option'])) {
                                $field_el->setAttribute('allow_delete_option', $field_def['allow_delete_option'] ? '1' : '0');
                            }
                            if (isset($field_def['max_length'])) {
                                $field_el->setAttribute('max_length', (string)(int)$field_def['max_length']);
                            }
                            if (isset($field_def['rows'])) {
                                $field_el->setAttribute('rows', (string)(int)$field_def['rows']);
                            }

                            if (isset($field_def['label'])) {
                                $label_node = $dom->createElement('label');
                                $label_node->appendChild($dom->createCDATASection((string)$field_def['label']));
                                $field_el->appendChild($label_node);
                            }

                            if (isset($field_def['hint'])) {
                                $hint_node = $dom->createElement('hint');
                                $hint_node->appendChild($dom->createCDATASection((string)$field_def['hint']));
                                $field_el->appendChild($hint_node);
                            }

                            if (isset($field_def['options'])) {
                                $options_node = $dom->createElement('options');
                                $options_node->appendChild($dom->createCDATASection((string)$field_def['options']));
                                $field_el->appendChild($options_node);
                            }

                            if (isset($field_def['time_format'])) {
                                $tf_node = $dom->createElement('time_format', (string)$field_def['time_format']);
                                $field_el->appendChild($tf_node);
                            }

                            if (isset($field_def['schema_filter'])) {
                                $schema_filter_node = $dom->createElement('schema_filter', (string)$field_def['schema_filter']);
                                $field_el->appendChild($schema_filter_node);
                            }

                            $fields_el->appendChild($field_el);
                        }

                        $schema_el->appendChild($fields_el);
                    }

                    $schemas_node->appendChild($schema_el);
                }

                $channel->appendChild($schemas_node);
            }

            foreach ($export_data['items'] as $item) {
                $item_node = $dom->createElement('item');

                // Campos básicos.
                if (isset($item['title'])) {
                    $item_node->appendChild($dom->createElement('title', $item['title']));
                }

                // Link fictício baseado no slug.
                $permalink = '';
                if (!empty($item['slug'])) {
                    $permalink = trailingslashit($export_data['site_url']) . $item['slug'] . '/';
                }
                if ($permalink !== '') {
                    $item_node->appendChild($dom->createElement('link', $permalink));
                }

                // GUID.
                $guid_value = $permalink !== '' ? $permalink : (isset($item['id']) ? (string)$item['id'] : '');
                if ($guid_value !== '') {
                    $guid = $dom->createElement('guid', $guid_value);
                    $guid->setAttribute('isPermaLink', $permalink !== '' ? 'true' : 'false');
                    $item_node->appendChild($guid);
                }

                // Namespaced nodes: dc:creator, content:encoded, excerpt:encoded, wp:*.
                $dc_creator = $dom->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:creator', wp_get_current_user()->user_login);
                $item_node->appendChild($dc_creator);

                $content_value = isset($item['content']) ? (string)$item['content'] : '';
                $content_node = $dom->createElementNS('http://purl.org/rss/1.0/modules/content/', 'content:encoded');
                $content_node->appendChild($dom->createCDATASection($content_value));
                $item_node->appendChild($content_node);

                $excerpt_value = isset($item['excerpt']) ? (string)$item['excerpt'] : '';
                $excerpt_node = $dom->createElementNS('http://wordpress.org/export/1.2/excerpt/', 'excerpt:encoded');
                $excerpt_node->appendChild($dom->createCDATASection($excerpt_value));
                $item_node->appendChild($excerpt_node);

                // Namespace wp.
                $wp_post_id = isset($item['id']) ? (int)$item['id'] : 0;
                $wp_status = isset($item['status']) ? (string)$item['status'] : 'publish';
                $wp_post_type = (isset($item['schema_slug']) && $item['schema_slug'] !== '') ? $item['schema_slug'] : 'post';
                $wp_post_name = isset($item['slug']) ? (string)$item['slug'] : '';

                $ns_wp = 'http://wordpress.org/export/1.2/';

                $post_id_el = $dom->createElementNS($ns_wp, 'wp:post_id', (string)$wp_post_id);
                $item_node->appendChild($post_id_el);

                $post_date_el = $dom->createElementNS($ns_wp, 'wp:post_date', current_time('mysql'));
                $item_node->appendChild($post_date_el);
                $post_date_gmt_el = $dom->createElementNS($ns_wp, 'wp:post_date_gmt', get_gmt_from_date(current_time('mysql')));
                $item_node->appendChild($post_date_gmt_el);

                $status_el = $dom->createElementNS($ns_wp, 'wp:status', $wp_status);
                $item_node->appendChild($status_el);

                $post_name_el = $dom->createElementNS($ns_wp, 'wp:post_name', $wp_post_name);
                $item_node->appendChild($post_name_el);

                $post_type_el = $dom->createElementNS($ns_wp, 'wp:post_type', $wp_post_type);
                $item_node->appendChild($post_type_el);

                // Categorias: mapeia cada categoria como <category>.
                if (isset($item['categories']) && is_array($item['categories'])) {
                    foreach ($item['categories'] as $cat) {
                        $cat_el = $dom->createElement('category', isset($cat['name']) ? $cat['name'] : '');
                        if (isset($cat['slug']) && $cat['slug'] !== '') {
                            $cat_el->setAttribute('nicename', $cat['slug']);
                        }
                        $cat_el->setAttribute('domain', 'category');
                        $item_node->appendChild($cat_el);
                    }
                }

                // Meta: exporta campos extras como <wp:postmeta>.
                if (isset($item['fields']) && is_array($item['fields'])) {
                    foreach ($item['fields'] as $field_id => $value) {
                        $meta_el = $dom->createElementNS($ns_wp, 'wp:postmeta');
                        $meta_key = $dom->createElementNS($ns_wp, 'wp:meta_key', $field_id);
                        $meta_value = $dom->createElementNS($ns_wp, 'wp:meta_value');
                        $meta_value->appendChild($dom->createCDATASection((string)$value));
                        $meta_el->appendChild($meta_key);
                        $meta_el->appendChild($meta_value);
                        $item_node->appendChild($meta_el);
                    }
                }


                // Meta adicional: mapa de mídias usadas (para reimportação no upt).
                if (isset($item['media_map']) && !empty($item['media_map'])) {
                    $meta_el = $dom->createElementNS($ns_wp, 'wp:postmeta');
                    $meta_key = $dom->createElementNS($ns_wp, 'wp:meta_key', '_upt_media_map');
                    $meta_value = $dom->createElementNS($ns_wp, 'wp:meta_value');
                    $meta_value->appendChild($dom->createCDATASection(wp_json_encode($item['media_map'])));
                    $meta_el->appendChild($meta_key);
                    $meta_el->appendChild($meta_value);
                    $item_node->appendChild($meta_el);
                }

                $channel->appendChild($item_node);
            }

            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: attachment; filename="upt-export-' . date('Y-m-d') . '.xml"');
            echo $dom->saveXML();
            exit;
        }
        // Default: JSON
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="upt-export-' . date('Y-m-d') . '.json"');

        echo wp_json_encode($export_data);
        exit;
    }

    /**
     * Importa/Exporta categorias do upt (taxonomia catalog_category) usando um arquivo .md.
     * Formato:
     * CATEGORIA
     *   SUB CATEGORIA
     */
    public static function handle_category_md_actions()
    {
        if (!isset($_POST['action'])) {
            return;
        }

        $action = sanitize_text_field(wp_unslash($_POST['action']));
        if ($action !== 'upt_import_categories_md' && $action !== 'upt_export_categories_md') {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'upt_categories_md_nonce')) {
            return;
        }

        // EXPORT
        if ($action === 'upt_export_categories_md') {
            $lines = [];
            $parents = get_terms(
            [
                'taxonomy' => 'catalog_category',
                'hide_empty' => false,
                'parent' => 0,
                'orderby' => 'name',
                'order' => 'ASC',
            ]
            );

            if (is_wp_error($parents)) {
                wp_die(esc_html__('Erro ao carregar categorias.', 'upt'));
            }

            foreach ($parents as $parent) {
                $lines[] = $parent->name;

                $children = get_terms(
                [
                    'taxonomy' => 'catalog_category',
                    'hide_empty' => false,
                    'parent' => (int)$parent->term_id,
                    'orderby' => 'name',
                    'order' => 'ASC',
                ]
                );

                if (!is_wp_error($children) && !empty($children)) {
                    foreach ($children as $child) {
                        $lines[] = '  ' . $child->name;
                    }
                }
            }

            $md = implode("\n", $lines) . "\n";
            $filename = 'upt-categorias-' . gmdate('Ymd-His') . '.md';

            nocache_headers();
            header('Content-Type: text/markdown; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: public');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');
            echo $md; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }

        // IMPORT
        if (!isset($_FILES['upt_categories_md']) || $_FILES['upt_categories_md']['error'] !== UPLOAD_ERR_OK) {
            add_settings_error(
                'upt_notices',
                'import_cat_md_error',
                'Ocorreu um erro ou nenhum arquivo .md foi enviado.',
                'error'
            );
            return;
        }

        $file_path = $_FILES['upt_categories_md']['tmp_name'];
        $contents = file_get_contents($file_path);
        if (!$contents) {
            add_settings_error(
                'upt_notices',
                'import_cat_md_error',
                'Não foi possível ler o arquivo enviado.',
                'error'
            );
            return;
        }

        // Normaliza quebras e remove BOM.
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $contents = str_replace(["\r\n", "\r"], "\n", $contents);

        $lines = explode("\n", $contents);
        $root_parent_id = isset($_POST['upt_categories_parent']) ? (int)$_POST['upt_categories_parent'] : 0;
        if ($root_parent_id < 0) {
            $root_parent_id = 0;
        }
        $current_parent_id = 0;
        $created = 0;
        $reused = 0;

        foreach ($lines as $raw_line) {
            $line = rtrim((string)$raw_line);
            if ($line === '') {
                continue;
            }

            // Normaliza: aceita também sintaxe Markdown (## Categoria, - Subcategoria) e mantém o formato clássico (indentação).
            $is_child = false;
            $name = '';

            // Heading: ## Categoria (ou #, ### etc.)
            if (preg_match('/^\s*#{1,6}\s+(.+)$/', $line, $m1)) {
                $is_child = false;
                $name = trim((string)$m1[1]);
            }
            elseif (preg_match('/^\s*[-\*\+]\s+(.+)$/', $line, $m2)) {
                // Lista: - Subcategoria / * Subcategoria / + Subcategoria
                $is_child = true;
                $name = trim((string)$m2[1]);
            }
            else {
                // Formato clássico: indentação (qualquer whitespace no início) = subcategoria.
                $is_child = (bool)preg_match('/^\s+\S/', $line);
                $name = trim($line);
            }

            if ($name === '') {
                continue;
            }

            // Categoria (nível raiz do arquivo) -> cria como filha da categoria pai escolhida (se houver).
            if (!$is_child) {
                $exists = term_exists($name, 'catalog_category', (int)$root_parent_id);
                if ($exists && !is_wp_error($exists)) {
                    $current_parent_id = (int)(is_array($exists) ? $exists['term_id'] : $exists);
                    $reused++;
                }
                else {
                    $inserted = wp_insert_term($name, 'catalog_category', ['parent' => (int)$root_parent_id]);
                    if (!is_wp_error($inserted)) {
                        $current_parent_id = (int)$inserted['term_id'];
                        $created++;
                    }
                }
                continue;
            }

            // Subcategoria sem categoria atual: trata como categoria raiz do arquivo (sob $root_parent_id).
            if (!$current_parent_id) {
                $exists = term_exists($name, 'catalog_category', (int)$root_parent_id);
                if ($exists && !is_wp_error($exists)) {
                    $current_parent_id = (int)(is_array($exists) ? $exists['term_id'] : $exists);
                    $reused++;
                }
                else {
                    $inserted = wp_insert_term($name, 'catalog_category', ['parent' => (int)$root_parent_id]);
                    if (!is_wp_error($inserted)) {
                        $current_parent_id = (int)$inserted['term_id'];
                        $created++;
                    }
                }
                continue;
            }

            // Subcategoria normal (filha da categoria atual).
            $exists = term_exists($name, 'catalog_category', (int)$current_parent_id);
            if ($exists && !is_wp_error($exists)) {
                $reused++;
            }
            else {
                $inserted = wp_insert_term($name, 'catalog_category', ['parent' => (int)$current_parent_id]);
                if (!is_wp_error($inserted)) {
                    $created++;
                }
            }
        }

        add_settings_error(
            'upt_notices',
            'import_cat_md_success',
            sprintf('Importação concluída. Criadas: %d | Reutilizadas: %d', (int)$created, (int)$reused),
            'updated'
        );
    }




    /**
     * Tenta localizar um attachment existente pelo nome de arquivo.
     *
     * @param string $filename
     * @return int Attachment ID ou 0 se não encontrado.
     */
    private static function find_attachment_by_filename($filename)
    {
        if (empty($filename)) {
            return 0;
        }

        $filename = wp_basename($filename);
        $filename = urldecode($filename);
        $filename = trim($filename);

        // Sanitização adicional para importação: alguns exports/planilhas trazem espaços e vírgulas no endereço.
        // O WordPress tende a normalizar nomes de arquivo no upload, então geramos variações para comparar.
        $filename_no_space_comma = str_replace([' ', ','], '', $filename);
        $filename_space_to_dash = str_replace(',', '', str_replace(' ', '-', $filename));
        $filename_sanitized_wp = function_exists('sanitize_file_name') ? sanitize_file_name($filename) : '';

        // Normaliza casos comuns do WordPress (ex.: imagens redimensionadas "foto-150x150.jpg").
        $try_filenames = [$filename];
        if ($filename_no_space_comma && $filename_no_space_comma !== $filename) {
            $try_filenames[] = $filename_no_space_comma;
        }
        if ($filename_space_to_dash && $filename_space_to_dash !== $filename) {
            $try_filenames[] = $filename_space_to_dash;
        }
        if ($filename_sanitized_wp && $filename_sanitized_wp !== $filename) {
            $try_filenames[] = $filename_sanitized_wp;
        }
        if (preg_match('/-(\d+)x(\d+)(\.[A-Za-z0-9]+)$/', $filename)) {
            $try_filenames[] = preg_replace('/-(\d+)x(\d+)(\.[A-Za-z0-9]+)$/', '$3', $filename);
        }
        // Normaliza casos comuns do WordPress (ex.: arquivos "-scaled" gerados no upload).
        if (preg_match('/-scaled(\.[A-Za-z0-9]+)$/', $filename)) {
            $try_filenames[] = preg_replace('/-scaled(\.[A-Za-z0-9]+)$/', '$1', $filename);
        }

        // Tenta também variações em lowercase (alguns servidores/migrações alteram casing do nome do arquivo).
        $lower = strtolower($filename);
        if ($lower !== $filename) {
            $try_filenames[] = $lower;
            $lower_no_space_comma = str_replace([' ', ','], '', $lower);
            if ($lower_no_space_comma && $lower_no_space_comma !== $lower) {
                $try_filenames[] = $lower_no_space_comma;
            }
            $lower_space_to_dash = str_replace(',', '', str_replace(' ', '-', $lower));
            if ($lower_space_to_dash && $lower_space_to_dash !== $lower) {
                $try_filenames[] = $lower_space_to_dash;
            }
            if (preg_match('/-(\d+)x(\d+)(\.[A-Za-z0-9]+)$/', $lower)) {
                $try_filenames[] = preg_replace('/-(\d+)x(\d+)(\.[A-Za-z0-9]+)$/', '$3', $lower);
            }
            if (preg_match('/-scaled(\.[A-Za-z0-9]+)$/', $lower)) {
                $try_filenames[] = preg_replace('/-scaled(\.[A-Za-z0-9]+)$/', '$1', $lower);
            }
        }
        $try_filenames = array_values(array_unique(array_filter($try_filenames)));

        global $wpdb;

        // Procura pelo nome de arquivo em _wp_attached_file.
        $attachment_id = 0;
        foreach ($try_filenames as $fn) {
            $like = '%' . $wpdb->esc_like('/' . $fn);
            $attachment_id = (int)$wpdb->get_var(
                $wpdb->prepare(
                "SELECT p.ID
                     FROM {$wpdb->posts} p
                     INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
                     WHERE p.post_type = 'attachment'
                       AND m.meta_key = '_wp_attached_file'
                       AND m.meta_value LIKE %s
                     ORDER BY p.ID DESC
                     LIMIT 1",
                $like
            )
            );
            if ($attachment_id > 0) {
                break;
            }
        }

        if ($attachment_id > 0) {
            return $attachment_id;
        }

        // Fallback: procura em GUID (alguns sites mantêm o caminho completo no guid).
        foreach ($try_filenames as $fn) {
            $like_guid = '%' . $wpdb->esc_like($fn) . '%';
            $attachment_id = (int)$wpdb->get_var(
                $wpdb->prepare(
                "SELECT ID
                     FROM {$wpdb->posts}
                     WHERE post_type = 'attachment'
                       AND guid LIKE %s
                     ORDER BY ID DESC
                     LIMIT 1",
                $like_guid
            )
            );
            if ($attachment_id > 0) {
                return $attachment_id;
            }
        }

        // Fallback: procura pelo título aproximado do attachment.
        foreach ($try_filenames as $fn) {
            $title = pathinfo($fn, PATHINFO_FILENAME);
            if ($title) {
                $like_title = '%' . $wpdb->esc_like($title) . '%';
                $attachment_id = (int)$wpdb->get_var(
                    $wpdb->prepare(
                    "SELECT ID
                         FROM {$wpdb->posts}
                         WHERE post_type = 'attachment'
                           AND post_title LIKE %s
                         ORDER BY ID DESC
                         LIMIT 1",
                    $like_title
                )
                );
                if ($attachment_id > 0) {
                    break;
                }
            }
        }

        // Se não encontrou no banco, tenta localizar o arquivo fisicamente em uploads e registrar na mídia.
        $registered = self::maybe_register_existing_upload_as_attachment($try_filenames);
        if ($registered > 0) {
            return $registered;
        }

        return $attachment_id > 0 ? $attachment_id : 0;

    }

    /**
     * Tenta resolver um attachment a partir de uma URL (prioritário) e, opcionalmente, filename.
     * - Se a URL contiver '/wp-content/uploads/', extraímos o caminho relativo e buscamos por meta _wp_attached_file.
     * - Se não achar, tenta registrar o arquivo existente em uploads pelo caminho relativo.
     * - Por fim, cai no resolver por filename.
     */
    private static function find_attachment_by_url_or_filename($url, $filename_fallback = '')
    {
        $url = trim((string)$url);
        if ($url === '') {
            return $filename_fallback ?self::find_attachment_by_filename($filename_fallback) : 0;
        }

        $upload = wp_upload_dir();
        $basedir = isset($upload['basedir']) ? $upload['basedir'] : '';

        $path = wp_parse_url($url, PHP_URL_PATH);
        if ($path) {
            $path = urldecode($path);
            $marker = '/wp-content/uploads/';
            $pos = strpos($path, $marker);
            if ($pos !== false) {
                $rel = ltrim(substr($path, $pos + strlen($marker)), '/');
                if ($rel !== '') {
                    // 1) Busca exata por _wp_attached_file.
                    global $wpdb;
                    $found = (int)$wpdb->get_var(
                        $wpdb->prepare(
                        "SELECT p.ID
                             FROM {$wpdb->posts} p
                             INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
                             WHERE p.post_type = 'attachment'
                               AND m.meta_key = '_wp_attached_file'
                               AND m.meta_value = %s
                             ORDER BY p.ID DESC
                             LIMIT 1",
                        $rel
                    )
                    );
                    if ($found > 0) {
                        return $found;
                    }

                    // 2) Se o arquivo existe fisicamente, registra com o caminho relativo correto.
                    if ($basedir) {
                        $abs = trailingslashit($basedir) . $rel;
                        if (file_exists($abs)) {
                            $registered = self::register_attachment_from_existing_file($abs, $rel);
                            if ($registered > 0) {
                                return $registered;
                            }
                        }
                    }
                }
            }
        }

        // Fallback por filename.
        if ($filename_fallback) {
            return self::find_attachment_by_filename($filename_fallback);
        }
        if ($path) {
            return self::find_attachment_by_filename(basename($path));
        }
        return 0;
    }


    /**
     * Quando a imagem existe fisicamente em /uploads mas não está registrada como attachment,
     * tenta localizar pelo basename e registrar automaticamente.
     */
    private static function maybe_register_existing_upload_as_attachment($try_filenames)
    {
        if (empty($try_filenames) || !is_array($try_filenames)) {
            return 0;
        }

        $upload = wp_upload_dir();
        if (empty($upload['basedir'])) {
            return 0;
        }

        foreach ($try_filenames as $fn) {
            $fn = wp_basename($fn);
            if (!$fn)
                continue;

            $rel = self::scan_uploads_for_basename($fn, $upload['basedir']);
            if (!$rel) {
                continue;
            }

            $abs = trailingslashit($upload['basedir']) . ltrim($rel, '/');
            if (!file_exists($abs)) {
                continue;
            }

            $attachment_id = self::register_attachment_from_existing_file($abs, $rel);
            if ($attachment_id > 0) {
                return $attachment_id;
            }
        }

        return 0;
    }

    /**
     * Procura um arquivo por basename dentro de uploads (cacheado por request).
     * Retorna o caminho relativo (ex.: '2026/03/foto.webp' ou 'confeiteiria/foto.webp').
     */
    private static function scan_uploads_for_basename($basename, $basedir)
    {
        static $cache = null;

        $basename = wp_basename($basename);
        if (!$basename)
            return '';

        // Chave de comparação mais tolerante (remove espaços e vírgulas)
        $basename_norm = strtolower(str_replace([' ', ','], '', $basename));

        if ($cache === null) {
            $cache = [];
            try {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($basedir, FilesystemIterator::SKIP_DOTS)
                    );
                foreach ($it as $file) {
                    /** @var SplFileInfo $file */
                    if (!$file->isFile())
                        continue;
                    $bn = $file->getBasename();
                    // Cache por basename (primeira ocorrência)
                    if (!isset($cache[$bn])) {
                        $full = $file->getPathname();
                        $rel = ltrim(str_replace($basedir, '', $full), '/\\\\');
                        $cache[$bn] = $rel;
                    }

                    // Também salva por chave normalizada (remove espaços e vírgulas)
                    $bn_norm = strtolower(str_replace([' ', ','], '', $bn));
                    if ($bn_norm && !isset($cache['__norm__' . $bn_norm])) {
                        $full = $file->getPathname();
                        $rel = ltrim(str_replace($basedir, '', $full), '/\\\\');
                        $cache['__norm__' . $bn_norm] = $rel;
                    }
                }
            }
            catch (Exception $e) {
                return '';
            }
        }

        if (isset($cache[$basename])) {
            return $cache[$basename];
        }
        if ($basename_norm && isset($cache['__norm__' . $basename_norm])) {
            return $cache['__norm__' . $basename_norm];
        }
        return '';
    }

    /**
     * Registra um attachment a partir de um arquivo já existente em uploads.
     */
    private static function register_attachment_from_existing_file($abs_path, $rel_path)
    {
        if (empty($abs_path) || empty($rel_path))
            return 0;

        $type = wp_check_filetype(basename($abs_path), null);
        $mime = isset($type['type']) ? $type['type'] : 'image/jpeg';

        $attachment = [
            'post_mime_type' => $mime,
            'post_title' => sanitize_file_name(pathinfo($abs_path, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $abs_path);
        if (is_wp_error($attach_id) || !$attach_id) {
            return 0;
        }

        // Garante _wp_attached_file correto (relativo ao uploads basedir)
        update_post_meta($attach_id, '_wp_attached_file', ltrim($rel_path, '/\\\\'));

        // Gera metadados (thumbnails etc.) quando aplicável
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        $meta = wp_generate_attachment_metadata($attach_id, $abs_path);
        if (is_array($meta)) {
            wp_update_attachment_metadata($attach_id, $meta);
        }

        return (int)$attach_id;
    }

    /**
     * Monta um mapa de mídias usadas em um item exportado,
     * incluindo nome de arquivo e tipo de campo.
     */
    private static function build_media_map_for_export($schema_slug, $all_schemas, $fields_values, $thumb_id, $thumb_url)
    {
        $media_map = [
            'featured_image' => null,
            'fields' => [],
        ];

        // Featured image.
        if ($thumb_id || $thumb_url) {
            $filename = '';
            if ($thumb_id) {
                $file = get_attached_file($thumb_id);
                if ($file && file_exists($file)) {
                    $filename = basename($file);
                }
            }

            if (!$filename && $thumb_url) {
                $path = wp_parse_url($thumb_url, PHP_URL_PATH);
                if ($path) {
                    $filename = basename($path);
                }
            }

            $media_map['featured_image'] = [
                'id' => (int)$thumb_id,
                'url' => $thumb_url,
                'filename' => $filename,
                'type' => 'image',
            ];
        }

        // Campos de mídia por esquema (imagem, vídeo, galeria).
        if ($schema_slug && isset($all_schemas[$schema_slug]['fields']) && is_array($all_schemas[$schema_slug]['fields'])) {
            foreach ($all_schemas[$schema_slug]['fields'] as $field_def) {
                if (empty($field_def['id'])) {
                    continue;
                }

                $field_id = $field_def['id'];
                $field_type = isset($field_def['type']) ? $field_def['type'] : '';

                if (!in_array($field_type, ['image', 'video', 'gallery'], true)) {
                    continue;
                }

                if (!isset($fields_values[$field_id])) {
                    continue;
                }

                $raw_value = $fields_values[$field_id];
                $filenames = [];

                if ($field_type === 'gallery') {
                    $decoded = null;

                    if (is_string($raw_value)) {
                        $decoded = json_decode($raw_value, true);
                    }
                    elseif (is_array($raw_value)) {
                        $decoded = $raw_value;
                    }

                    if (is_array($decoded)) {
                        foreach ($decoded as $entry) {
                            if (is_numeric($entry)) {
                                $file = get_attached_file((int)$entry);
                                if ($file && file_exists($file)) {
                                    $filenames[] = basename($file);
                                }
                            }
                            elseif (is_string($entry)) {
                                $path = wp_parse_url($entry, PHP_URL_PATH);
                                if ($path) {
                                    $filenames[] = basename($path);
                                }
                            }
                        }
                    }
                    elseif (is_string($raw_value)) {
                        $parts = preg_split('/[,|;]/', $raw_value);
                        for ($i = 0; $i < count($parts); $i++) {
                            $p = trim($parts[$i]);
                            if ($p !== '') {
                                $filenames[] = basename($p);
                            }
                        }
                    }

                    $filenames = array_values(array_unique(array_filter($filenames)));
                    $filename = implode(',', $filenames);
                }
                else {
                    $filename = '';

                    if (is_numeric($raw_value)) {
                        $file = get_attached_file((int)$raw_value);
                        if ($file && file_exists($file)) {
                            $filename = basename($file);
                        }
                    }
                    elseif (is_string($raw_value)) {
                        $path = wp_parse_url($raw_value, PHP_URL_PATH);
                        if ($path) {
                            $filename = basename($path);
                        }
                    }
                }

                $media_map['fields'][$field_id] = [
                    'type' => $field_type,
                    'filename' => $filename,
                ];
            }
        }

        if (empty($media_map['featured_image']) && empty($media_map['fields'])) {
            return [];
        }

        return $media_map;
    }
    private static function process_image_field($source, $post_id)
    {
        if (empty($source))
            return null;
        if (is_numeric($source))
            return absint($source);
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            if (!function_exists('media_sideload_image')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
            }
            $attachment_id = media_handle_sideload(['name' => basename($source), 'tmp_name' => download_url($source)], $post_id, null);
            if (!is_wp_error($attachment_id))
                return $attachment_id;
        }
        return null;
    }


    /**
     * Converte o XML de importação no mesmo formato de array usado pelo JSON.
     *
     * Estrutura esperada:
     * <upt_export>
     *   <items>
     *     <item>
     *       <id>...</id>
     *       <title>...</title>
     *       <schema_slug>...</schema_slug>
     *       ...
     *       <categories>
     *         <category>
     *           <name>...</name>
     *           <slug>...</slug>
     *         </category>
     *       </categories>
     *       <featured_image>
     *         <id>...</id>
     *         <url>...</url>
     *       </featured_image>
     *       <fields>
     *         <field id="campo_1">Valor</field>
     *       </fields>
     *     </item>
     *   </items>
     * </upt_export>
     *
     * @param \SimpleXMLElement $xml
     * @return array
     */
    /**
     * Converte XML de importação em array interno do upt.
     *
     * Suporta:
     * - Formato XML próprio do upt (<upt_export>).
     * - Formato WordPress WXR (<rss><channel><item>...).
     *
     * @param \SimpleXMLElement $xml
     * @return array
     */

    /**
     * Repara problemas comuns em XML importado (ex.: & não escapado em URLs/links).
     *
     * @param string $contents
     * @return string
     */
    private static function repair_import_xml_string($contents)
    {
        if (!is_string($contents) || $contents === '') {
            return $contents;
        }

        // Remove BOM UTF-8, caso exista.
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

        // Remove caracteres inválidos para XML 1.0.
        // Mantém: tab (0x09), LF (0x0A), CR (0x0D) e 0x20..0xD7FF, 0xE000..0xFFFD.
        $contents = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $contents);

        // Escapa & que não são entidades válidas (amp, lt, gt, quot, apos, #...).
        $contents = preg_replace('/&(?!amp;|lt;|gt;|quot;|apos;|#\d+;|#x[0-9A-Fa-f]+;)/', '&amp;', $contents);

        return $contents;
    }


    /**
     * Lê o texto de um filho por "local-name" em qualquer namespace conhecido do documento.
     * Útil para WXR onde o prefixo pode variar ou estar ausente.
     *
     * @param \SimpleXMLElement $node
     * @param string $local_name
     * @return string
     */
    private static function sx_get_child_text_anyns($node, $local_name)
    {
        if (!($node instanceof \SimpleXMLElement)) {
            return '';
        }

        // 1) Sem namespace (filhos diretos).
        if (isset($node->{ $local_name})) {
            return (string)$node->{ $local_name};
        }

        // 2) Tenta por todos os namespaces/prefixos presentes.
        $nss = $node->getNamespaces(true);
        if (is_array($nss)) {
            foreach ($nss as $prefix => $uri) {
                if ($prefix === '') {
                    continue;
                }
                $children = $node->children($prefix, true);
                if (isset($children->{ $local_name})) {
                    return (string)$children->{ $local_name};
                }
            }
        }

        return '';
    }

    /**
     * Retorna o nó de filhos (SimpleXMLElement) para um prefixo conhecido quando existir.
     *
     * @param \SimpleXMLElement $node
     * @param string $prefix
     * @return \SimpleXMLElement|null
     */
    private static function sx_children_prefix($node, $prefix)
    {
        if (!($node instanceof \SimpleXMLElement)) {
            return null;
        }
        $children = $node->children($prefix, true);
        if ($children instanceof \SimpleXMLElement) {
            return $children;
        }
        return null;
    }
    private static function convert_import_xml_to_array($xml)
    {
        $items = [];
        $imported_schemas = [];

        // Captura definições de esquemas exportados pelo upt dentro do canal do WXR.
        if (isset($xml->channel) && isset($xml->channel->upt_schemas) && isset($xml->channel->upt_schemas->schema)) {
            foreach ($xml->channel->upt_schemas->schema as $schema_node) {
                $slug = isset($schema_node['slug']) ? sanitize_title((string)$schema_node['slug']) : '';
                if ('' === $slug) {
                    continue;
                }

                $schema_def = [
                    'label' => '',
                    'items_limit' => 0,
                    'items_min' => 0,
                    'items_limit_per_category' => false,
                    'items_limit_max_per_category' => false,
                    'fields' => [],
                ];

                if (isset($schema_node['label']) && (string)$schema_node['label'] !== '') {
                    $schema_def['label'] = (string)$schema_node['label'];
                }

                if (isset($schema_node['items_limit'])) {
                    $schema_def['items_limit'] = (int)$schema_node['items_limit'];
                }

                if (isset($schema_node['items_min'])) {
                    $schema_def['items_min'] = (int)$schema_node['items_min'];
                }

                if (isset($schema_node['items_limit_per_category'])) {
                    $schema_def['items_limit_per_category'] = ((string)$schema_node['items_limit_per_category'] === '1');
                }

                if (isset($schema_node['items_limit_max_per_category'])) {
                    $schema_def['items_limit_max_per_category'] = ((string)$schema_node['items_limit_max_per_category'] === '1');
                }

                if (isset($schema_node->fields) && isset($schema_node->fields->field)) {
                    foreach ($schema_node->fields->field as $field_node) {
                        $field_id = isset($field_node['id']) ? (string)$field_node['id'] : '';
                        if ('' === $field_id) {
                            continue;
                        }

                        $field_def = ['id' => $field_id];

                        $scalar_attrs = [
                            'type' => 'type',
                            'max_length' => 'max_length',
                            'rows' => 'rows',
                        ];

                        foreach ($scalar_attrs as $attr_key => $attr_name) {
                            if (isset($field_node[$attr_name])) {
                                $field_def[$attr_key] = (string)$field_node[$attr_name];
                            }
                        }

                        $bool_attrs = ['required', 'multiple', 'allow_new', 'allow_rename_option', 'allow_delete_option'];
                        foreach ($bool_attrs as $attr_name) {
                            if (isset($field_node[$attr_name])) {
                                $field_def[$attr_name] = ((string)$field_node[$attr_name] === '1');
                            }
                        }

                        if (isset($field_node->label)) {
                            $field_def['label'] = (string)$field_node->label;
                        }

                        if (isset($field_node->hint)) {
                            $field_def['hint'] = (string)$field_node->hint;
                        }

                        if (isset($field_node->options)) {
                            $field_def['options'] = (string)$field_node->options;
                        }

                        if (isset($field_node->time_format)) {
                            $field_def['time_format'] = (string)$field_node->time_format;
                        }

                        if (isset($field_node->schema_filter)) {
                            $field_def['schema_filter'] = (string)$field_node->schema_filter;
                        }

                        $schema_def['fields'][] = $field_def;
                    }
                }

                $imported_schemas[$slug] = $schema_def;
            }
        }

        // Detecta WXR do WordPress: raiz <rss> com <channel><item><wp:post_type>.
        $is_wp_wxr = false;
        if (isset($xml->channel) && isset($xml->channel->item)) {
            foreach ($xml->channel->item as $maybe_item) {
                // WXR geralmente usa <wp:post_type>, mas o prefixo pode variar/estar ausente dependendo do exportador.
                $maybe_post_type = self::sx_get_child_text_anyns($maybe_item, 'post_type');
                if ($maybe_post_type !== '') {
                    $is_wp_wxr = true;
                    break;
                }
            }

            // Fallback: alguns exports mantêm a estrutura WXR mas SimpleXML não expõe o post_type corretamente.
            if (!$is_wp_wxr) {
                $is_wp_wxr = true;
            }
        }

        if ($is_wp_wxr) {
            // Namespaces comuns do WXR.
            $ns_wp = 'wp';
            $ns_content = 'content';
            $ns_excerpt = 'excerpt';

            // 1) Pré-scan de anexos do WXR para conseguir resolver _thumbnail_id por filename/URL.
            // Mapa: [post_id => ['url' => ..., 'filename' => ...]]
            $wxr_attachments = [];
            foreach ($xml->channel->item as $scan_item) {
                $scan_wp = $scan_item->children($ns_wp, true);
                $scan_type = self::sx_get_child_text_anyns($scan_item, 'post_type');
                if ($scan_type !== 'attachment') {
                    continue;
                }
                $att_id = (int)self::sx_get_child_text_anyns($scan_item, 'post_id');
                $att_url = self::sx_get_child_text_anyns($scan_item, 'attachment_url');
                if ($att_id > 0 && $att_url !== '') {
                    $path = wp_parse_url($att_url, PHP_URL_PATH);
                    $wxr_attachments[$att_id] = [
                        'url' => $att_url,
                        'filename' => $path ? basename($path) : basename($att_url),
                    ];
                }
            }

            foreach ($xml->channel->item as $wp_item) {
                $wp = $wp_item->children($ns_wp, true);
                $content = $wp_item->children($ns_content, true);
                $excerpt = $wp_item->children($ns_excerpt, true);

                $post_type = self::sx_get_child_text_anyns($wp_item, 'post_type');
                if ($post_type === '') {
                    $post_type = 'post';
                }

                // Não importa anexos como itens do upt. Eles servem apenas para mapear mídias.
                if ($post_type === 'attachment') {
                    continue;
                }

                // Por padrão, consideramos todos os tipos de post. Se quiser limitar, ajuste aqui.
                $item = [];

                // ID do post.
                // ID original do post do WordPress (mantido apenas como referência, não para atualizar).
                $orig_id = (int)self::sx_get_child_text_anyns($wp_item, 'post_id');
                if ($orig_id > 0) {
                    $item['original_wp_id'] = $orig_id;
                }

                // Título.
                if (isset($wp_item->title)) {
                    $item['title'] = (string)$wp_item->title;
                }

                // Conteúdo (usa <content:encoded> quando existir).
                if (isset($content->encoded) && (string)$content->encoded !== '') {
                    $item['content'] = (string)$content->encoded;
                }
                elseif (isset($wp_item->description)) {
                    $item['content'] = (string)$wp_item->description;
                }

                // Excerpt (usa <excerpt:encoded>).
                if (isset($excerpt->encoded) && (string)$excerpt->encoded !== '') {
                    $item['excerpt'] = (string)$excerpt->encoded;
                }

                // Status.
                $status = self::sx_get_child_text_anyns($wp_item, 'status');
                $item['status'] = $status !== '' ? $status : 'publish';

                // Slug (post_name).
                $post_name = self::sx_get_child_text_anyns($wp_item, 'post_name');
                if ($post_name !== '') {
                    $item['slug'] = $post_name;
                }

                // Link do post (quando existir).
                if (isset($wp_item->link)) {
                    $item['link'] = (string)$wp_item->link;
                }

                // Datas principais.
                $pd = self::sx_get_child_text_anyns($wp_item, 'post_date');
                if ($pd !== '') {
                    $item['post_date'] = $pd;
                }
                $pdg = self::sx_get_child_text_anyns($wp_item, 'post_date_gmt');
                if ($pdg !== '') {
                    $item['post_date_gmt'] = $pdg;
                }

                // Schema: opcional. Podemos usar o post_type como slug de esquema.
                $schema_slug = $post_type ? $post_type : 'post';
                $item['schema_slug'] = $schema_slug;
                $item['schema'] = [
                    'slug' => $schema_slug,
                    'label' => ucfirst($schema_slug),
                ];

                // Categorias (usa <category> padrão do WXR).
                if (isset($wp_item->category)) {
                    $cats = [];
                    foreach ($wp_item->category as $cat_node) {
                        $cat = [];
                        $name = (string)$cat_node;
                        if ($name !== '') {
                            $cat['name'] = $name;
                        }
                        if (isset($cat_node['nicename']) && (string)$cat_node['nicename'] !== '') {
                            $cat['slug'] = (string)$cat_node['nicename'];
                        }
                        if (isset($cat_node['domain']) && (string)$cat_node['domain'] !== '') {
                            $cat['domain'] = (string)$cat_node['domain'];
                        }
                        if (!empty($cat)) {
                            $cats[] = $cat;
                        }
                    }
                    if (!empty($cats)) {
                        $item['categories'] = $cats;

                        // Também armazena uma lista simples de nomes de categorias (separados por vírgula) para mapeamento rápido em campos.
                        $names = [];
                        foreach ($cats as $c) {
                            if (isset($c['name']) && $c['name'] !== '') {
                                $names[] = $c['name'];
                            }
                        }
                        if (!empty($names)) {
                            $item['categories_names'] = implode(', ', $names);
                        }
                    }
                }

                // Featured image / meta como campos.
                $fields = [];
                $media_map = null;

                $postmeta_nodes = null;
                if (isset($wp->postmeta)) {
                    $postmeta_nodes = $wp->postmeta;
                }
                else {
                    $nss = $wp_item->getNamespaces(true);
                    if (is_array($nss)) {
                        foreach ($nss as $prefix => $uri) {
                            if ($prefix === '') {
                                continue;
                            }
                            $ch = $wp_item->children($prefix, true);
                            if (isset($ch->postmeta)) {
                                $postmeta_nodes = $ch->postmeta;
                                break;
                            }
                        }
                    }
                }

                if ($postmeta_nodes) {
                    foreach ($postmeta_nodes as $meta) {
                        $meta_key = isset($meta->meta_key) ? (string)$meta->meta_key : '';
                        $meta_value = isset($meta->meta_value) ? (string)$meta->meta_value : '';

                        if ($meta_key === '') {
                            continue;
                        }

                        // Meta especial: mapa de mídias exportado pelo upt.
                        if ($meta_key === '_upt_media_map') {
                            $decoded = json_decode($meta_value, true);
                            if (is_array($decoded)) {
                                $media_map = $decoded;
                            }
                            continue;
                        }

                        // Guarda todos os meta em fields.
                        $fields[$meta_key] = $meta_value;
                    }
                }

                // 2) Se houver thumbnail no WXR (_thumbnail_id), tenta resolver para filename e preencher media_map.
                // Isso permite vincular imagens já existentes na Mídia do WordPress durante a importação.
                if (isset($fields['_thumbnail_id']) && is_numeric($fields['_thumbnail_id'])) {
                    $thumb_src_id = (int)$fields['_thumbnail_id'];
                    if ($thumb_src_id > 0 && isset($wxr_attachments[$thumb_src_id])) {
                        if (!is_array($media_map)) {
                            $media_map = [];
                        }
                        if (!isset($media_map['featured_image']) || !is_array($media_map['featured_image'])) {
                            $media_map['featured_image'] = [];
                        }
                        $media_map['featured_image']['filename'] = $wxr_attachments[$thumb_src_id]['filename'];
                        $media_map['featured_image']['url'] = $wxr_attachments[$thumb_src_id]['url'];
                    }
                }

                if (!empty($fields)) {
                    $item['fields'] = $fields;
                }

                if (!empty($media_map)) {
                    $item['media_map'] = $media_map;
                }

                // Não tentamos mapear attachment separado aqui; se houver _thumbnail_id,
                // ele já estará em fields; o usuário pode usar isso no esquema se quiser.

                if (!empty($item)) {
                    $items[] = $item;
                }
            }

            return [
                'items' => $items,
                'schemas' => $imported_schemas,
            ];
        }

        // Formato antigo XML próprio do upt (back‑compat).
        if (isset($xml->items) && isset($xml->items->item)) {
            $xml_items = $xml->items->item;
        }
        elseif (isset($xml->item)) {
            $xml_items = $xml->item;
        }
        else {
            $xml_items = [];
        }

        foreach ($xml_items as $xml_item) {
            $item = [];

            // Campos simples
            $simple_keys = ['id', 'title', 'content', 'excerpt', 'status', 'slug', 'schema_slug'];
            foreach ($simple_keys as $key) {
                if (isset($xml_item->{ $key})) {
                    $item[$key] = (string)$xml_item->{ $key};
                }
            }

            // Schema info
            if (isset($xml_item->schema)) {
                $schema = [];
                foreach ($xml_item->schema->children() as $key => $value) {
                    $schema[$key] = (string)$value;
                }
                if (!empty($schema)) {
                    $item['schema'] = $schema;
                    if (empty($item['schema_slug']) && isset($schema['slug'])) {
                        $item['schema_slug'] = $schema['slug'];
                    }
                }
            }

            // Categorias
            if (isset($xml_item->categories) && isset($xml_item->categories->category)) {
                $cats = [];
                foreach ($xml_item->categories->category as $cat_node) {
                    $cat = [];
                    if (isset($cat_node->name)) {
                        $cat['name'] = (string)$cat_node->name;
                    }
                    if (isset($cat_node->slug)) {
                        $cat['slug'] = (string)$cat_node->slug;
                    }
                    if (isset($cat_node->term_id)) {
                        $cat['term_id'] = (int)$cat_node->term_id;
                    }
                    if (isset($cat_node->parent)) {
                        $cat['parent'] = (int)$cat_node->parent;
                    }
                    if (!empty($cat)) {
                        $cats[] = $cat;
                    }
                }
                if (!empty($cats)) {
                    $item['categories'] = $cats;
                }
            }

            // Imagem destacada
            if (isset($xml_item->featured_image)) {
                $fi = [];
                if (isset($xml_item->featured_image->id)) {
                    $fi['id'] = (int)$xml_item->featured_image->id;
                }
                if (isset($xml_item->featured_image->url)) {
                    $fi['url'] = (string)$xml_item->featured_image->url;
                }
                if (!empty($fi)) {
                    $item['featured_image'] = $fi;
                }
            }

            // Campos
            if (isset($xml_item->fields) && isset($xml_item->fields->field)) {
                $fields = [];
                foreach ($xml_item->fields->field as $field_node) {
                    $field_id = isset($field_node['id']) ? (string)$field_node['id'] : '';
                    if ($field_id === '') {
                        continue;
                    }
                    $fields[$field_id] = (string)$field_node;
                }
                if (!empty($fields)) {
                    $item['fields'] = $fields;
                }
            }

            if (!empty($item)) {
                $items[] = $item;
            }
        }

        return [
            'items' => $items,
            'schemas' => $imported_schemas,
        ];
    }


    /**
     * Mescla definições de esquemas importadas (JSON ou XML) no Schema Store,
     * garantindo que os termos existam e que campos sejam recriados.
     */
    private static function merge_imported_schemas($imported_schemas, $items, $schema_mode, $forced_schema_slug)
    {
        if (!class_exists('UPT_Schema_Store')) {
            return [];
        }

        if (empty($imported_schemas) || !is_array($imported_schemas)) {
            return UPT_Schema_Store::get_schemas();
        }

        $target_slugs = [];

        if ($forced_schema_slug) {
            $target_slugs[] = sanitize_title($forced_schema_slug);
        }
        else {
            foreach ($items as $item) {
                if (isset($item['schema_slug']) && $item['schema_slug'] !== '') {
                    $target_slugs[] = sanitize_title($item['schema_slug']);
                }
                elseif (isset($item['schema']['slug']) && $item['schema']['slug'] !== '') {
                    $target_slugs[] = sanitize_title($item['schema']['slug']);
                }
            }
        }

        $target_slugs = array_values(array_unique(array_filter($target_slugs)));
        $use_all = empty($target_slugs);

        $all_schemas = UPT_Schema_Store::get_schemas();
        $changed = false;

        foreach ($imported_schemas as $raw_slug => $schema_def) {
            $slug = sanitize_title($raw_slug);
            if ('' === $slug) {
                continue;
            }

            if (!$use_all && !in_array($slug, $target_slugs, true)) {
                continue;
            }

            $label = '';
            if (is_array($schema_def)) {
                if (isset($schema_def['label'])) {
                    $label = sanitize_text_field($schema_def['label']);
                }
                elseif (isset($schema_def['name'])) {
                    $label = sanitize_text_field($schema_def['name']);
                }
            }

            if ('' === $label) {
                $label = $slug;
            }

            $term = get_term_by('slug', $slug, 'catalog_schema');
            if (!$term || is_wp_error($term)) {
                $created = wp_insert_term($label, 'catalog_schema', ['slug' => $slug]);
                if (!is_wp_error($created) && isset($created['term_id'])) {
                    $term = get_term((int)$created['term_id'], 'catalog_schema');
                }
            }

            if (!isset($all_schemas[$slug]) || !is_array($all_schemas[$slug])) {
                $all_schemas[$slug] = [];
            }

            if ($label) {
                $all_schemas[$slug]['label'] = $label;
            }

            if (is_array($schema_def)) {
                if (isset($schema_def['items_limit'])) {
                    $all_schemas[$slug]['items_limit'] = (int)$schema_def['items_limit'];
                }

                if (isset($schema_def['items_min'])) {
                    $all_schemas[$slug]['items_min'] = (int)$schema_def['items_min'];
                }

                if (isset($schema_def['items_limit_per_category'])) {
                    $all_schemas[$slug]['items_limit_per_category'] = (bool)$schema_def['items_limit_per_category'];
                }

                if (isset($schema_def['items_limit_max_per_category'])) {
                    $all_schemas[$slug]['items_limit_max_per_category'] = (bool)$schema_def['items_limit_max_per_category'];
                }

                if (!isset($all_schemas[$slug]['fields']) || !is_array($all_schemas[$slug]['fields'])) {
                    $all_schemas[$slug]['fields'] = [];
                }

                $existing_by_id = [];
                foreach ($all_schemas[$slug]['fields'] as $idx => $field) {
                    if (isset($field['id'])) {
                        $existing_by_id[$field['id']] = $idx;
                    }
                }

                if (isset($schema_def['fields']) && is_array($schema_def['fields'])) {
                    foreach ($schema_def['fields'] as $field_def) {
                        if (!is_array($field_def) || !isset($field_def['id'])) {
                            continue;
                        }

                        $field_id = sanitize_text_field($field_def['id']);
                        if ('' === $field_id) {
                            continue;
                        }

                        $normalized = $field_def;
                        $normalized['id'] = $field_id;

                        foreach (['label', 'hint', 'type', 'options', 'time_format', 'schema_filter'] as $key) {
                            if (isset($normalized[$key])) {
                                $normalized[$key] = sanitize_text_field($normalized[$key]);
                            }
                        }

                        foreach (['required', 'multiple', 'allow_new'] as $bool_key) {
                            if (isset($normalized[$bool_key])) {
                                $normalized[$bool_key] = (bool)$normalized[$bool_key];
                            }
                        }

                        foreach (['max_length', 'rows'] as $int_key) {
                            if (isset($normalized[$int_key])) {
                                $normalized[$int_key] = (int)$normalized[$int_key];
                            }
                        }

                        if (isset($existing_by_id[$field_id])) {
                            $all_schemas[$slug]['fields'][$existing_by_id[$field_id]] = array_merge(
                                $all_schemas[$slug]['fields'][$existing_by_id[$field_id]],
                                $normalized
                            );
                        }
                        else {
                            $all_schemas[$slug]['fields'][] = $normalized;
                        }
                    }
                }
            }

            $changed = true;
        }

        if ($changed) {
            UPT_Schema_Store::save_schemas($all_schemas);
        }

        return $all_schemas;
    }



    /**
     * Cria dinamicamente campos básicos de posts do WordPress para um esquema,
     * com base nas escolhas do usuário na tela de importação.
     *
     * @param string $schema_slug
     * @return void
     */
    private static function maybe_create_wp_basic_fields_for_schema($schema_slug)
    {
        if (!class_exists('UPT_Schema_Store')) {
            return;
        }

        $all_schemas = UPT_Schema_Store::get_schemas();
        if (!isset($all_schemas[$schema_slug])) {
            $all_schemas[$schema_slug] = [];
        }
        if (!isset($all_schemas[$schema_slug]['fields']) || !is_array($all_schemas[$schema_slug]['fields'])) {
            $all_schemas[$schema_slug]['fields'] = [];
        }

        // Índice rápido por ID já existente para evitar duplicatas.
        $existing_ids = [];
        foreach ($all_schemas[$schema_slug]['fields'] as $field) {
            if (isset($field['id'])) {
                $existing_ids[] = $field['id'];
            }
        }

        // Configuração dos campos básicos (label + nome do campo de POST que define o tipo).
        // O campo só é criado quando o usuário seleciona explicitamente um tipo no formulário.
        $basic_fields = [
            'Título' => 'wp_field_type_title',
            'Link' => 'wp_field_type_link',
            'Post ID' => 'wp_field_type_post_id',
            'Data' => 'wp_field_type_date',
            'Categoria' => 'wp_field_type_category',
            'Status' => 'wp_field_type_status',
            'Conteúdo (resumo)' => 'wp_field_type_excerpt',
            'Conteúdo completo' => 'wp_field_type_content',
        ];

        foreach ($basic_fields as $label => $post_key) {
            $type = '';
            if (isset($_POST[$post_key]) && $_POST[$post_key] !== '') {
                $type = sanitize_text_field(wp_unslash($_POST[$post_key]));
            }

            if ($type === '') {
                continue;
            }

            // Gera o ID do campo usando a mesma lógica do builder de esquemas.
            $normalized_label = str_replace('²', '2', $label);
            $id = $schema_slug . '_' . sanitize_title($normalized_label);

            if (in_array($id, $existing_ids, true)) {
                continue;
            }

            $new_field_data = [
                'id' => $id,
                'label' => $label,
                'hint' => '',
                'type' => $type,
                'required' => false,
                'multiple' => false,
                'allow_new' => false,
                'allow_rename_option' => false,
                'allow_delete_option' => false,
                'allow_new_category' => true,
                'max_length' => 0,
                'rows' => in_array($type, ['textarea', 'wysiwyg'], true) ? 5 : 0,
            ];

            $all_schemas[$schema_slug]['fields'][] = $new_field_data;
        }

        UPT_Schema_Store::save_schemas($all_schemas);
    }

    /**
     * Atualiza automaticamente as opções de campos do tipo "select" de um esquema
     * com base nos valores já salvos nos itens desse esquema.
     *
     * @param string $schema_slug
     * @return void
     */

    /**
     * Remove campos básicos do WordPress (Título, Link, Post ID, Data, Categoria, Status, Conteúdo...) do esquema.
     * Usado no modo "Manter apenas campos do upt", para garantir que o esquema permaneça enxuto
     * e compatível com o modelo do upt, sem campos extras criados por importações anteriores.
     *
     * @param string $schema_slug
     * @return void
     */
    private static function prune_wp_basic_fields_for_schema($schema_slug)
    {
        if (!class_exists('UPT_Schema_Store')) {
            return;
        }

        $all_schemas = UPT_Schema_Store::get_schemas();
        if (!isset($all_schemas[$schema_slug]) || !isset($all_schemas[$schema_slug]['fields']) || !is_array($all_schemas[$schema_slug]['fields'])) {
            return;
        }

        $labels = [
            'Título',
            'Link',
            'Post ID',
            'Data',
            'Categoria',
            'Status',
            'Conteúdo (resumo)',
            'Conteúdo completo',
        ];

        $ids_to_remove = [];
        foreach ($labels as $label) {
            $normalized_label = str_replace('²', '2', $label);
            $ids_to_remove[] = $schema_slug . '_' . sanitize_title($normalized_label);
        }

        $before = $all_schemas[$schema_slug]['fields'];
        $after = [];

        foreach ($before as $field) {
            if (isset($field['id']) && in_array($field['id'], $ids_to_remove, true)) {
                continue;
            }
            $after[] = $field;
        }

        // Só salva se houve mudança.
        if (count($after) !== count($before)) {
            $all_schemas[$schema_slug]['fields'] = $after;
            UPT_Schema_Store::save_schemas($all_schemas);
        }
    }


    private static function update_select_field_options_from_items($schema_slug)
    {
        if (!class_exists('UPT_Schema_Store')) {
            return;
        }

        $schema_slug = sanitize_title($schema_slug);
        if ('' === $schema_slug) {
            return;
        }

        $all_schemas = UPT_Schema_Store::get_schemas();
        if (!isset($all_schemas[$schema_slug]['fields']) || !is_array($all_schemas[$schema_slug]['fields'])) {
            return;
        }

        // Descobre quais campos do esquema são do tipo "select".
        $select_field_ids = [];
        foreach ($all_schemas[$schema_slug]['fields'] as $field_def) {
            if (isset($field_def['id'], $field_def['type']) && $field_def['type'] === 'select') {
                $select_field_ids[] = $field_def['id'];
            }
        }

        if (empty($select_field_ids)) {
            return;
        }

        // Busca todos os itens do esquema para coletar os valores de meta.
        $meta_query = ['relation' => 'OR'];
        foreach ($select_field_ids as $fid) {
            $meta_query[] = [
                'key' => $fid,
                'compare' => 'EXISTS',
            ];
        }

        $query_args = [
            'post_type' => 'catalog_item',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'tax_query' => [
                [
                    'taxonomy' => 'catalog_schema',
                    'field' => 'slug',
                    'terms' => $schema_slug,
                ],
            ],
            'meta_query' => $meta_query,
            'fields' => 'ids',
        ];

        $items_q = new WP_Query($query_args);
        if (!$items_q->have_posts()) {
            wp_reset_postdata();
            return;
        }

        $collected = [];
        foreach ($select_field_ids as $fid) {
            $collected[$fid] = [];
        }

        foreach ($items_q->posts as $post_id) {
            foreach ($select_field_ids as $fid) {
                $raw = get_post_meta($post_id, $fid, true);
                if (!is_string($raw) || $raw === '') {
                    continue;
                }

                $parts = preg_split('/[,|;]/', $raw);
                foreach ($parts as $opt) {
                    $opt = trim(wp_strip_all_tags($opt));
                    if ($opt !== '') {
                        $collected[$fid][$opt] = true;
                    }
                }
            }
        }
        wp_reset_postdata();

        $has_changes = false;
        foreach ($all_schemas[$schema_slug]['fields'] as &$field_def) {
            if (!isset($field_def['id'], $field_def['type']) || $field_def['type'] !== 'select') {
                continue;
            }
            $fid = $field_def['id'];
            if (empty($collected[$fid]) || !is_array($collected[$fid])) {
                continue;
            }

            $options = array_keys($collected[$fid]);
            sort($options, SORT_NATURAL | SORT_FLAG_CASE);
            $field_def['options'] = implode('|', $options);
            $has_changes = true;
        }
        unset($field_def);

        if ($has_changes) {
            UPT_Schema_Store::save_schemas($all_schemas);
        }
    }



    /**
     * Importa mídias do upt a partir de um arquivo ZIP.
     */
    public static function handle_media_import_actions()
    {
        if (!isset($_POST['action']) || $_POST['action'] !== 'upt_import_media') {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('upt_import_media', 'upt_import_media_nonce');

        if (!isset($_FILES['upt_media_zip']) || empty($_FILES['upt_media_zip']['name'])) {
            add_settings_error('upt_gallery', 'upt_media_zip_missing', __('Nenhum arquivo ZIP de mídia foi enviado.', 'upt'), 'error');
            return;
        }

        $file = $_FILES['upt_media_zip'];

        if (!empty($file['error']) || !is_uploaded_file($file['tmp_name'])) {
            add_settings_error('upt_gallery', 'upt_media_zip_upload_error', __('Erro ao enviar o arquivo ZIP de mídia.', 'upt'), 'error');
            return;
        }

        if (!class_exists('ZipArchive')) {
            add_settings_error('upt_gallery', 'upt_media_zip_no_zip', __('A extensão ZipArchive não está disponível no servidor.', 'upt'), 'error');
            return;
        }

        $zip_path = $file['tmp_name'];

        $uploads = wp_upload_dir();
        $temp_dir = trailingslashit($uploads['basedir']) . 'upt-media-import-' . time();
        if (!wp_mkdir_p($temp_dir)) {
            add_settings_error('upt_gallery', 'upt_media_zip_mkdir_error', __('Não foi possível criar o diretório temporário de importação.', 'upt'), 'error');
            return;
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($zip_path)) {
            add_settings_error('upt_gallery', 'upt_media_zip_open_error', __('Não foi possível abrir o arquivo ZIP de mídia.', 'upt'), 'error');
            return;
        }

        $zip->extractTo($temp_dir);
        $zip->close();

        $imported_count = 0;

        $folder_taxonomy = defined('UPT_Media_Folders::TAXONOMY') ?UPT_Media_Folders::TAXONOMY : 'upt_media_folder';

        $dir_iterator = new RecursiveDirectoryIterator($temp_dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($dir_iterator);

        foreach ($iterator as $file_info) {
            if (!$file_info->isFile()) {
                continue;
            }

            $abs_path = $file_info->getPathname();
            $rel_path = ltrim(str_replace($temp_dir, '', $abs_path), DIRECTORY_SEPARATOR);

            $extension = strtolower(pathinfo($abs_path, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'mov', 'webm', 'ogg'];
            if (!in_array($extension, $allowed_ext, true)) {
                continue;
            }

            // Mantém o caminho relativo do ZIP dentro de uploads, evitando renomeações.
            // Aceita ZIPs com prefixos como "wp-content/uploads/" ou "uploads/".
            $norm_rel = str_replace('\\', '/', $rel_path);
            $norm_rel = ltrim($norm_rel, '/');
            $norm_rel = preg_replace('#^\./#', '', $norm_rel);
            $norm_rel = preg_replace('#^(wp-content/uploads/)+#', '', $norm_rel);
            $norm_rel = preg_replace('#^(uploads/)+#', '', $norm_rel);
            // Segurança básica contra path traversal.
            if (strpos($norm_rel, '..') !== false) {
                continue;
            }

            $dest_abs = trailingslashit($uploads['basedir']) . $norm_rel;
            $dest_dir = dirname($dest_abs);
            if (!wp_mkdir_p($dest_dir)) {
                continue;
            }

            if (!file_exists($dest_abs)) {
                // Copia preservando o nome.
                if (!@copy($abs_path, $dest_abs)) {
                    continue;
                }
            }

            // Garante um attachment registrado com _wp_attached_file = $norm_rel.
            global $wpdb;
            $attachment_id = (int)$wpdb->get_var(
                $wpdb->prepare(
                "SELECT p.ID
                     FROM {$wpdb->posts} p
                     INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
                     WHERE p.post_type = 'attachment'
                       AND m.meta_key = '_wp_attached_file'
                       AND m.meta_value = %s
                     ORDER BY p.ID DESC
                     LIMIT 1",
                $norm_rel
            )
            );

            if ($attachment_id <= 0) {
                $attachment_id = self::register_attachment_from_existing_file($dest_abs, $norm_rel);
            }

            if ($attachment_id <= 0) {
                continue;
            }

            $imported_count++;

            $folder_slug = '';
            $dir_name = trim(dirname($norm_rel), '/\\.');
            if ($dir_name && $dir_name !== '.') {
                $segments = preg_split('#[\\/]#', $dir_name);
                $folder_slug = sanitize_title(end($segments));
            }

            if ($folder_slug !== '') {
                $term = get_term_by('slug', $folder_slug, $folder_taxonomy);
                if (!$term) {
                    $inserted = wp_insert_term($folder_slug, $folder_taxonomy, [
                        'slug' => $folder_slug,
                        'name' => $folder_slug,
                    ]);
                    if (!is_wp_error($inserted) && isset($inserted['term_id'])) {
                        $term_id = (int)$inserted['term_id'];
                    }
                    elseif (is_array($inserted) && isset($inserted['term_id'])) {
                        $term_id = (int)$inserted['term_id'];
                    }
                    else {
                        $term_id = 0;
                    }
                }
                else {
                    $term_id = (int)$term->term_id;
                }

                if (!empty($term_id)) {
                    wp_set_object_terms($attachment_id, [$term_id], $folder_taxonomy, false);
                }
            }
        }

        if (is_dir($temp_dir)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp_dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $fs_item) {
                if ($fs_item->isDir()) {
                    rmdir($fs_item->getPathname());
                }
                else {
                    unlink($fs_item->getPathname());
                }
            }
            rmdir($temp_dir);
        }

        if ($imported_count > 0) {
            add_settings_error(
                'upt_gallery',
                'upt_media_zip_success',
                sprintf(
                __('Importação de mídia concluída: %d arquivo(s) importado(s) para a galeria do upt.', 'upt'),
                $imported_count
            ),
                'updated'
            );
        }
        else {
            add_settings_error(
                'upt_gallery',
                'upt_media_zip_empty',
                __('Nenhuma mídia válida foi encontrada no arquivo ZIP enviado.', 'upt'),
                'error'
            );
        }
    }

    public static function handle_import_actions()
    {
        if (!isset($_POST['action']) || $_POST['action'] !== 'upt_import_data') {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'upt_import_nonce')) {
            return;
        }

        $import_format = isset($_POST['import_format']) ? sanitize_text_field($_POST['import_format']) : 'json';

        if (!isset($_FILES['import_json_file']) || $_FILES['import_json_file']['error'] !== UPLOAD_ERR_OK) {
            add_settings_error(
                'upt_notices',
                'import_error',
                'Ocorreu um erro ou nenhum arquivo foi enviado.',
                'error'
            );
            return;
        }

        $file_path = $_FILES['import_json_file']['tmp_name'];
        $contents = file_get_contents($file_path);

        if (!$contents) {
            add_settings_error(
                'upt_notices',
                'import_error',
                'Não foi possível ler o arquivo enviado.',
                'error'
            );
            return;
        }

        $data = null;

        if ($import_format === 'xml') {
            if (!class_exists('SimpleXMLElement')) {
                add_settings_error(
                    'upt_notices',
                    'import_error',
                    'A extensão XML do PHP não está disponível neste servidor. Importe via JSON ou contate o administrador.',
                    'error'
                );
                return;
            }

            // Tenta reparar problemas comuns em XML (ex: & não escapado em URLs) para evitar erro de "XML inválido".
            $contents = self::repair_import_xml_string($contents);

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
            if (!$xml) {
                add_settings_error(
                    'upt_notices',
                    'import_error',
                    'O arquivo XML enviado não é válido.',
                    'error'
                );
                return;
            }

            $data = self::convert_import_xml_to_array($xml);
        }
        else {
            $data = json_decode($contents, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                add_settings_error(
                    'upt_notices',
                    'import_error',
                    'O arquivo JSON enviado não é válido.',
                    'error'
                );
                return;
            }
        }

        if (!is_array($data)) {
            add_settings_error(
                'upt_notices',
                'import_error',
                'O arquivo enviado não possui dados em um formato reconhecido.',
                'error'
            );
            return;
        }

        // Estrutura esperada: { "schemas": {...}, "items": [ ... ] } ou diretamente uma lista de itens
        if (isset($data['items']) && is_array($data['items'])) {
            $items = $data['items'];
        }
        elseif (is_array($data)) {
            $items = $data;
        }
        else {
            $items = [];
        }

        $imported_schemas = (isset($data['schemas']) && is_array($data['schemas'])) ? $data['schemas'] : [];

        // Modo de campos: usar campos do arquivo (pode criar campos extras) OU manter apenas campos do upt.
        $import_fields_mode = isset($_POST['import_fields_mode']) ? sanitize_text_field($_POST['import_fields_mode']) : 'use_file';

        if (empty($items)) {
            add_settings_error(
                'upt_notices',
                'import_error',
                'Nenhum item foi encontrado para importar.',
                'error'
            );
            return;
        }

        // Escolha de esquema durante a importação
        $schema_mode = isset($_POST['import_schema_mode']) ? sanitize_text_field($_POST['import_schema_mode']) : 'keep';
        $forced_schema_slug = '';
        $forced_schema_label = '';

        if ($schema_mode === 'existing') {
            $existing_slug = isset($_POST['import_schema_existing']) ? sanitize_title($_POST['import_schema_existing']) : '';
            if ($existing_slug) {
                $term = get_term_by('slug', $existing_slug, 'catalog_schema');
                if ($term && !is_wp_error($term)) {
                    $forced_schema_slug = $term->slug;
                    $forced_schema_label = $term->name;
                }
                else {
                    add_settings_error(
                        'upt_notices',
                        'import_schema_error',
                        'Erro ao localizar o esquema existente selecionado.',
                        'error'
                    );
                    return;
                }
            }
        }
        elseif ($schema_mode === 'new') {
            $schema_name = isset($_POST['import_schema_new_name']) ? sanitize_text_field($_POST['import_schema_new_name']) : '';
            if ($schema_name === '') {
                add_settings_error(
                    'upt_notices',
                    'import_schema_error',
                    'Informe um nome para o novo esquema.',
                    'error'
                );
                return;
            }

            $result = wp_insert_term($schema_name, 'catalog_schema');
            if (is_wp_error($result)) {
                add_settings_error(
                    'upt_notices',
                    'import_schema_error',
                    'Erro ao criar o novo esquema: ' . $result->get_error_message(),
                    'error'
                );
                return;
            }

            if (isset($result['term_id'])) {
                $term = get_term((int)$result['term_id'], 'catalog_schema');
                if ($term && !is_wp_error($term)) {
                    $forced_schema_slug = $term->slug;
                    $forced_schema_label = $term->name;

                    // Garante registro no Schema Store com limite padrão (ilimitado)
                    if (class_exists('UPT_Schema_Store')) {
                        $all_schemas = UPT_Schema_Store::get_schemas();
                        if (!isset($all_schemas[$term->slug])) {
                            $all_schemas[$term->slug] = [];
                        }
                        if (!isset($all_schemas[$term->slug]['items_limit'])) {
                            $all_schemas[$term->slug]['items_limit'] = 0;
                        }
                        UPT_Schema_Store::save_schemas($all_schemas);
                    }
                }
            }
        }

        // Se o usuário optou por NÃO criar campos extras, não mescla definições de campos vindas do arquivo.
        if ($import_fields_mode === 'upt_only') {
            // Garante que o esquema existente permaneça apenas com os campos do upt (remove campos básicos WP criados em tentativas anteriores).
            if (in_array($schema_mode, ['existing', 'new'], true) && !empty($forced_schema_slug)) {
                self::prune_wp_basic_fields_for_schema($forced_schema_slug);
            }
            $all_schemas_definitions = class_exists('UPT_Schema_Store') ?UPT_Schema_Store::get_schemas() : [];
        }
        else {
            $all_schemas_definitions = self::merge_imported_schemas($imported_schemas, $items, $schema_mode, $forced_schema_slug);
        }

        $imported_count = 0;
        $skipped_count = 0;

        // Cria automaticamente campos básicos para o esquema escolhido (novo ou existente),
        // com base nas opções informadas no formulário de importação.
        if (in_array($schema_mode, ['existing', 'new'], true) && !empty($forced_schema_slug)) {
            // Só cria campos básicos/mapeamento se o usuário permitiu criação de campos extras.
            if ($import_fields_mode !== 'upt_only') {
                self::maybe_create_wp_basic_fields_for_schema($forced_schema_slug);
                $all_schemas_definitions = UPT_Schema_Store::get_schemas();
            }
            else {
                $all_schemas_definitions = class_exists('UPT_Schema_Store') ?UPT_Schema_Store::get_schemas() : [];
            }
        }

        // Verifica se o esquema de destino possui ao menos um campo do tipo "taxonomy"
        // (Seleção de Categoria). Isso serve como gatilho para criar/atribuir categorias
        // do WordPress a partir dos dados importados.
        $has_schema_taxonomy_field = false;
        if (in_array($schema_mode, ['existing', 'new'], true)
        && !empty($forced_schema_slug)
        && isset($all_schemas_definitions[$forced_schema_slug]['fields'])
        && is_array($all_schemas_definitions[$forced_schema_slug]['fields'])
        ) {
            foreach ($all_schemas_definitions[$forced_schema_slug]['fields'] as $field_def) {
                if (isset($field_def['type']) && $field_def['type'] === 'taxonomy') {
                    $has_schema_taxonomy_field = true;
                    break;
                }
            }
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                $skipped_count++;
                continue;
            }

            // Título
            $title = '';
            if (isset($item['title'])) {
                $title = (string)$item['title'];
            }
            elseif (isset($item['post_title'])) {
                $title = (string)$item['post_title'];
            }
            $title = trim($title);

            if ($title === '') {
                $skipped_count++;
                continue;
            }

            // Slug
            $slug = '';
            if (isset($item['slug'])) {
                $slug = sanitize_title($item['slug']);
            }
            elseif (isset($item['post_name'])) {
                $slug = sanitize_title($item['post_name']);
            }

            // Conteúdo
            $content = '';
            if (isset($item['content'])) {
                $content = (string)$item['content'];
            }
            elseif (isset($item['post_content'])) {
                $content = (string)$item['post_content'];
            }

            // Excerpt
            $excerpt = '';
            if (isset($item['excerpt'])) {
                $excerpt = (string)$item['excerpt'];
            }
            elseif (isset($item['post_excerpt'])) {
                $excerpt = (string)$item['post_excerpt'];
            }

            // Status
            $status = 'publish';
            if (isset($item['status'])) {
                $status = (string)$item['status'];
            }
            elseif (isset($item['post_status'])) {
                $status = (string)$item['post_status'];
            }

            // Esquema
            $schema_slug = '';

            // Se o usuário escolheu um esquema específico na importação, força o slug para todos os itens
            if (in_array($schema_mode, ['existing', 'new'], true) && $forced_schema_slug) {
                $schema_slug = $forced_schema_slug;
            }
            else {
                if (isset($item['schema_slug'])) {
                    $schema_slug = sanitize_title($item['schema_slug']);
                }
                elseif (isset($item['schema']['slug'])) {
                    $schema_slug = sanitize_title($item['schema']['slug']);
                }
            }
            // ID para atualizar (opcional)
            $post_id = 0;
            if (isset($item['id'])) {
                $post_id = absint($item['id']);
            }
            elseif (isset($item['ID'])) {
                $post_id = absint($item['ID']);
            }

            $post_data = [
                'post_title' => $title,
                'post_content' => $content,
                'post_excerpt' => $excerpt,
                'post_status' => $status,
                'post_type' => 'catalog_item',
            ];

            if ($slug) {
                $post_data['post_name'] = $slug;
            }

            if ($post_id > 0) {
                $post_data['ID'] = $post_id;
                $post_id = wp_update_post($post_data, true);
            }
            else {
                $post_id = wp_insert_post($post_data, true);
            }

            if (is_wp_error($post_id) || !$post_id) {
                $skipped_count++;
                continue;
            }

            // Definir esquema como termo em catalog_schema
            if ($schema_slug) {
                wp_set_object_terms($post_id, $schema_slug, 'catalog_schema', false);
            }

            // Categorias
            $categories_raw = [];
            if (isset($item['categories']) && is_array($item['categories'])) {
                $categories_raw = $item['categories'];
            }

            if (!empty($categories_raw)) {
                // Se existir uma categoria-pai com o slug do esquema, preferimos usar como contexto.
                // Importante: NÃO criamos automaticamente essa categoria aqui, para evitar categorias soltas.
                $parent_id = 0;
                if ($schema_slug) {
                    $parent_term = get_term_by('slug', $schema_slug, 'catalog_category');
                    if ($parent_term && !is_wp_error($parent_term)) {
                        $parent_id = (int)$parent_term->term_id;
                    }
                }

                $term_ids_to_set = [];

                foreach ($categories_raw as $cat_item) {
                    $cat_name = '';
                    $cat_slug = '';

                    if (is_array($cat_item)) {
                        if (isset($cat_item['name'])) {
                            $cat_name = trim((string)$cat_item['name']);
                        }
                        if (isset($cat_item['slug'])) {
                            $cat_slug = sanitize_title($cat_item['slug']);
                        }
                    }
                    else {
                        $cat_name = trim((string)$cat_item);
                    }

                    if ($cat_name === '') {
                        continue;
                    }

                    if (!$cat_slug) {
                        $cat_slug = sanitize_title($cat_name);
                    }

                    $term_id = 0;

                    // 1) Preferência: existe no mesmo nível (mesmo parent).
                    if ($parent_id) {
                        $exists = term_exists($cat_name, 'catalog_category', $parent_id);
                        if (!$exists && $cat_slug) {
                            $exists = term_exists($cat_slug, 'catalog_category', $parent_id);
                        }
                        if (is_array($exists) && isset($exists['term_id'])) {
                            $term_id = (int)$exists['term_id'];
                        }
                        elseif (is_int($exists)) {
                            $term_id = (int)$exists;
                        }
                    }

                    // 2) Fallback: existe em outro nível (não mover parent; apenas vincular).
                    if (!$term_id) {
                        $global_term = get_term_by('slug', $cat_slug, 'catalog_category');
                        if ($global_term && !is_wp_error($global_term)) {
                            $term_id = (int)$global_term->term_id;
                        }
                        else {
                            $by_name = get_terms([
                                'taxonomy' => 'catalog_category',
                                'hide_empty' => false,
                                'name' => $cat_name,
                                'number' => 1,
                            ]);
                            if (is_array($by_name) && !empty($by_name) && !is_wp_error($by_name)) {
                                $term_id = (int)$by_name[0]->term_id;
                            }
                        }
                    }

                    // 3) Se não existir, cria (sob o parent do esquema quando houver; senão raiz).
                    if (!$term_id) {
                        $args = ['slug' => $cat_slug];
                        if ($parent_id) {
                            $args['parent'] = $parent_id;
                        }
                        $new_term = wp_insert_term($cat_name, 'catalog_category', $args);
                        if (is_wp_error($new_term)) {
                            continue;
                        }
                        $term_id = (int)$new_term['term_id'];
                    }

                    if ($term_id) {
                        $term_ids_to_set[] = $term_id;
                    }
                }

                if (!empty($term_ids_to_set)) {
                    // Atribui todas as categorias-filhas ao item.
                    wp_set_object_terms($post_id, $term_ids_to_set, 'catalog_category', false);
                }
            }

            // Imagem destacada: vincula exclusivamente pelo nome do arquivo presente no media_map.
            $thumb_id = 0;

            // 1) Preferência: resolver por URL completa (caminho relativo em uploads) quando disponível.
            $featured_url = '';
            $featured_fn = '';
            if (isset($item['media_map']['featured_image']['url'])) {
                $featured_url = (string)$item['media_map']['featured_image']['url'];
            }
            elseif (isset($item['featured_image']['url'])) {
                $featured_url = (string)$item['featured_image']['url'];
            }
            if (isset($item['media_map']['featured_image']['filename'])) {
                $featured_fn = trim((string)$item['media_map']['featured_image']['filename']);
            }

            if ($featured_url !== '') {
                $maybe_thumb = self::find_attachment_by_url_or_filename($featured_url, $featured_fn);
                if ($maybe_thumb > 0) {
                    $thumb_id = $maybe_thumb;
                }
            }

            // 2) Fallback: somente pelo filename.
            if ($thumb_id === 0 && $featured_fn !== '') {
                $maybe_thumb = self::find_attachment_by_filename($featured_fn);
                if ($maybe_thumb > 0) {
                    $thumb_id = $maybe_thumb;
                }
            }

            // Não usamos IDs importados; somente filename. Se não achar, deixa sem thumbnail.
            if ($thumb_id > 0) {
                set_post_thumbnail($post_id, $thumb_id);
            }

            // Valores de campos por esquema
            $fields_values = [];
            if (isset($item['fields']) && is_array($item['fields'])) {
                $fields_values = $item['fields'];
            }

            // Quando há um esquema definido vindo da importação de posts do WordPress,
            // mapeia alguns dados básicos (título, link, etc.) para campos do esquema,
            // usando o mesmo padrão de ID gerado em maybe_create_wp_basic_fields_for_schema().
            if ($schema_slug && in_array($schema_mode, ['existing', 'new'], true) && $import_fields_mode !== 'upt_only') {
                $wp_basic_labels = [
                    'Título' => 'title',
                    'Link' => 'link',
                    'Post ID' => 'original_wp_id',
                    'Data' => 'post_date',
                    'Categoria' => 'categories_names',
                    'Status' => 'status',
                    'Conteúdo (resumo)' => 'excerpt',
                    'Conteúdo completo' => 'content',
                ];

                foreach ($wp_basic_labels as $label => $source_key) {
                    if (!isset($item[$source_key]) || $item[$source_key] === '') {
                        continue;
                    }

                    $normalized_label = str_replace('²', '2', $label);
                    $field_id = $schema_slug . '_' . sanitize_title($normalized_label);

                    $fields_values[$field_id] = $item[$source_key];
                }
            }

            if ($schema_slug && isset($all_schemas_definitions[$schema_slug]['fields']) && is_array($all_schemas_definitions[$schema_slug]['fields'])) {
                $schema_fields = $all_schemas_definitions[$schema_slug]['fields'];
                $allowed_ids = wp_list_pluck($schema_fields, 'id');

                foreach ($fields_values as $field_id => $raw_value) {
                    if (!in_array($field_id, $allowed_ids, true)) {
                        continue;
                    }

                    $value = $raw_value;
                    $field_type = '';

                    // Descobre o tipo do campo a partir da definição do esquema.
                    if (isset($schema_fields) && is_array($schema_fields)) {
                        foreach ($schema_fields as $field_def) {
                            if (empty($field_def['id']) || $field_def['id'] !== $field_id) {
                                continue;
                            }
                            $field_type = isset($field_def['type']) ? $field_def['type'] : '';
                            break;
                        }
                    }

                    // Mapa de mídia por campo (imagem, vídeo, galeria), se disponível.
                    $media_field_info = [];
                    if (isset($item['media_map']['fields']) && is_array($item['media_map']['fields']) && isset($item['media_map']['fields'][$field_id])) {
                        $media_field_info = $item['media_map']['fields'][$field_id];
                    }

                    // Para campos de mídia, vincula exclusivamente pelo(s) nome(s) de arquivo do media_map.
                    if (in_array($field_type, ['image', 'video', 'gallery'], true) && !empty($media_field_info) && !empty($media_field_info['filename'])) {
                        if ($field_type === 'gallery') {
                            $filenames = explode(',', $media_field_info['filename']);
                            $filenames = array_map('trim', $filenames);
                            $ids = [];
                            foreach ($filenames as $fn) {
                                if ($fn === '') {
                                    continue;
                                }
                                $maybe_media_id = self::find_attachment_by_filename($fn);
                                if ($maybe_media_id > 0) {
                                    $ids[] = $maybe_media_id;
                                }
                            }
                            if (!empty($ids)) {
                                $value = $ids;
                            }
                        }
                        else {
                            $maybe_media_id = self::find_attachment_by_filename($media_field_info['filename']);
                            if ($maybe_media_id > 0) {
                                $value = $maybe_media_id;
                            }
                        }
                    }

                    // Não baixa mídia nem usa IDs importados; se não encontrar por filename, mantém o valor original.

                    if (is_array($raw_value) || is_object($raw_value)) {
                        $value = wp_json_encode($raw_value);
                    }

                    update_post_meta($post_id, $field_id, $value);
                }
            }
            else {
                foreach ($fields_values as $field_id => $raw_value) {
                    if (strpos($field_id, '_') === 0) {
                        continue;
                    }

                    $value = $raw_value;

                    // Tentativa simples para campos de imagem mesmo sem definição de esquema:
                    // se o valor for URL ou ID numérico, processa como imagem.
                    if (is_string($raw_value) && filter_var($raw_value, FILTER_VALIDATE_URL)) {
                        $maybe_id = self::process_image_field($raw_value, $post_id);
                        if ($maybe_id) {
                            $value = $maybe_id;
                        }
                    }
                    elseif (is_array($raw_value) || is_object($raw_value)) {
                        $value = wp_json_encode($raw_value);
                    }

                    update_post_meta($post_id, $field_id, $value);
                }
            }



            // Se o esquema possuir um campo do tipo "Seleção de Categoria" (taxonomy),
            // cria/atribui termos em "catalog_category" com base em 'categories_names'
            // vindas do XML do WordPress.
            if ($has_schema_taxonomy_field && !empty($schema_slug) && isset($item['categories_names']) && $item['categories_names'] !== '') {
                $raw_categories = $item['categories_names'];
                $names_list = [];

                if (is_string($raw_categories)) {
                    $parts = preg_split('/[,|;]/', $raw_categories);
                    foreach ($parts as $name_part) {
                        $name_part = trim(wp_strip_all_tags($name_part));
                        if ($name_part !== '') {
                            $names_list[] = $name_part;
                        }
                    }
                }
                elseif (is_array($raw_categories)) {
                    foreach ($raw_categories as $name_part) {
                        $name_part = trim(wp_strip_all_tags((string)$name_part));
                        if ($name_part !== '') {
                            $names_list[] = $name_part;
                        }
                    }
                }

                if (!empty($names_list)) {
                    $taxonomy = 'catalog_category';
                    $parent_cat_id = 0;
                    $schema_term = get_term_by('slug', $schema_slug, 'catalog_schema');
                    if ($schema_term && !is_wp_error($schema_term)) {
                        $existing_parent = get_term_by('slug', $schema_slug, $taxonomy);
                        if (!$existing_parent || is_wp_error($existing_parent)) {
                            $insert_parent = wp_insert_term(
                                $schema_term->name,
                                $taxonomy,
                            [
                                'slug' => $schema_slug,
                            ]
                            );
                            if (!is_wp_error($insert_parent) && isset($insert_parent['term_id'])) {
                                $parent_cat_id = (int)$insert_parent['term_id'];
                            }
                        }
                        else {
                            $parent_cat_id = (int)$existing_parent->term_id;
                        }
                    }

                    $term_ids = [];
                    foreach ($names_list as $cat_name) {
                        $cat_slug = sanitize_title($cat_name);
                        $term = get_term_by('slug', $cat_slug, $taxonomy);

                        if (!$term || is_wp_error($term)) {
                            $args = ['slug' => $cat_slug];
                            if ($parent_cat_id) {
                                $args['parent'] = $parent_cat_id;
                            }
                            $insert = wp_insert_term($cat_name, $taxonomy, $args);
                            if (!is_wp_error($insert) && isset($insert['term_id'])) {
                                $term_ids[] = (int)$insert['term_id'];
                            }
                        }
                        else {
                            $term_ids[] = (int)$term->term_id;
                        }
                    }

                    if (!empty($term_ids)) {
                        wp_set_object_terms($post_id, $term_ids, $taxonomy, false);
                    }
                }
            }

            $imported_count++;
        }



        // Após importar, se o usuário tiver escolhido um esquema específico
        // (novo ou existente), atualiza automaticamente as opções dos campos
        // de seleção com base nos valores presentes nos itens importados.
        if (in_array($schema_mode, ['existing', 'new'], true) && !empty($forced_schema_slug)) {
            self::update_select_field_options_from_items($forced_schema_slug);
        }
        $message = sprintf(
            'Importação concluída. %d itens criados/atualizados com sucesso, %d itens ignorados.',
            $imported_count,
            $skipped_count
        );
        add_settings_error(
            'upt_notices',
            'import_success',
            $message,
            'updated'
        );
    }


    public static function handle_schema_actions()
    {
        if (!current_user_can('manage_options'))
            return;

        $redirect_url = remove_query_arg(['action', 'schema_id', 'field_id', '_wpnonce']);

        if (isset($_POST['action']) && $_POST['action'] === 'upt_add_schema') {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'upt_add_schema_nonce')) {
                return;
            }

            $schema_name = sanitize_text_field($_POST['schema_name']);
            $items_limit_mode = isset($_POST['schema_items_limit_mode']) ? sanitize_text_field($_POST['schema_items_limit_mode']) : 'unlimited';
            $items_limit_value = isset($_POST['schema_items_limit']) ? absint($_POST['schema_items_limit']) : 0;
            $items_limit = ($items_limit_mode === 'limited' && $items_limit_value > 0) ? $items_limit_value : 0;

            if (!empty($schema_name)) {
                $result = wp_insert_term($schema_name, 'catalog_schema');

                if (is_wp_error($result)) {
                    add_settings_error('upt_notices', 'schema_error', 'Erro: ' . $result->get_error_message(), 'error');
                    return; // Retorna sem redirecionar para exibir o erro
                }
                else {
                    // Salva as configurações do esquema (como limite de itens) no Schema Store
                    if (isset($result['term_id'])) {
                        $term = get_term((int)$result['term_id'], 'catalog_schema');
                        if ($term && !is_wp_error($term)) {
                            $all_schemas = UPT_Schema_Store::get_schemas();
                            if (!isset($all_schemas[$term->slug])) {
                                $all_schemas[$term->slug] = [];
                            }
                            $all_schemas[$term->slug]['items_limit'] = $items_limit;
                            // Mínimo padrão: 0 (sem mínimo)
                            if (!isset($all_schemas[$term->slug]['items_min'])) {
                                $all_schemas[$term->slug]['items_min'] = 0;
                            }
                            if (!isset($all_schemas[$term->slug]['items_limit_per_category'])) {
                                $all_schemas[$term->slug]['items_limit_per_category'] = false;
                            }
                            if (!isset($all_schemas[$term->slug]['items_limit_max_per_category'])) {
                                $all_schemas[$term->slug]['items_limit_max_per_category'] = false;
                            }
                            UPT_Schema_Store::save_schemas($all_schemas);

                            $redirect_url = add_query_arg('schema', $term->slug, $redirect_url);
                        }
                    }

                    // Define transient para sucesso pois vamos redirecionar
                    set_transient('upt_settings_success', 'Esquema "' . $schema_name . '" criado com sucesso!', 30);
                }
            }
            else {
                add_settings_error('upt_notices', 'schema_error', 'Erro: O nome do esquema não pode ser vazio.', 'error');
                return; // Retorna sem redirecionar
            }

            wp_safe_redirect($redirect_url);
            exit;
        }

        elseif (isset($_POST['action']) && $_POST['action'] === 'upt_rename_schema') {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'upt_rename_schema_nonce')) {
                return;
            }

            $schema_slug = isset($_POST['schema_slug']) ? sanitize_title(sanitize_text_field(wp_unslash($_POST['schema_slug']))) : '';
            $new_schema_name = isset($_POST['schema_new_name']) ? sanitize_text_field(wp_unslash($_POST['schema_new_name'])) : '';

            if (empty($schema_slug) || empty($new_schema_name)) {
                add_settings_error('upt_notices', 'schema_error', 'Erro: Nome ou slug do esquema inválidos.', 'error');
                wp_safe_redirect(admin_url('admin.php?page=upt_schema_builder'));
                exit;
            }

            $term = get_term_by('slug', $schema_slug, 'catalog_schema');
            if (!$term || is_wp_error($term)) {
                add_settings_error('upt_notices', 'schema_error', 'Erro: Esquema não encontrado.', 'error');
                wp_safe_redirect(admin_url('admin.php?page=upt_schema_builder'));
                exit;
            }

            $old_slug = $term->slug;

            $update_result = wp_update_term(
                $term->term_id,
                'catalog_schema',
            [
                'name' => $new_schema_name,
                'slug' => sanitize_title($new_schema_name),
            ]
            );

            if (is_wp_error($update_result)) {
                add_settings_error('upt_notices', 'schema_error', 'Erro ao renomear o esquema: ' . $update_result->get_error_message(), 'error');
                wp_safe_redirect(admin_url('admin.php?page=upt_schema_builder&schema=' . $old_slug));
                exit;
            }

            $updated_term = get_term($update_result['term_id'], 'catalog_schema');
            $new_slug = $updated_term && !is_wp_error($updated_term) ? $updated_term->slug : $old_slug;

            $all_schemas = UPT_Schema_Store::get_schemas();
            if (isset($all_schemas[$old_slug])) {
                $schema_data = $all_schemas[$old_slug];
                unset($all_schemas[$old_slug]);

                // Atualiza IDs dos campos e informações relacionadas
                $old_prefix = $old_slug . '_';
                $new_prefix = $new_slug . '_';

                if (isset($schema_data['fields']) && is_array($schema_data['fields'])) {
                    global $wpdb;

                    foreach ($schema_data['fields'] as &$field) {
                        if (!isset($field['id'])) {
                            continue;
                        }

                        $old_id = $field['id'];

                        if (strpos($old_id, $old_prefix) === 0) {
                            $new_id = $new_prefix . substr($old_id, strlen($old_prefix));

                            // Atualiza as chaves meta dos itens existentes
                            $wpdb->query(
                                $wpdb->prepare(
                                "UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
                                $new_id,
                                $old_id
                            )
                            );

                            // Atualiza referências em dados do Elementor (_elementor_data)
                            $like = '%' . $wpdb->esc_like($old_id) . '%';
                            $meta_rows = $wpdb->get_results(
                                $wpdb->prepare(
                                "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key IN ('_elementor_data','_elementor_page_settings','_elementor_template_meta') AND meta_value LIKE %s",
                                $like
                            )
                            );

                            if ($meta_rows) {
                                foreach ($meta_rows as $row) {
                                    $new_value = str_replace($old_id, $new_id, $row->meta_value);
                                    if ($new_value !== $row->meta_value) {
                                        $wpdb->update(
                                            $wpdb->postmeta,
                                        ['meta_value' => $new_value],
                                        ['meta_id' => $row->meta_id],
                                        ['%s'],
                                        ['%d']
                                        );
                                    }
                                }
                            }

                            $field['id'] = $new_id;
                        }
                    }
                    unset($field);
                }

                $schema_data['label'] = $new_schema_name;
                $all_schemas[$new_slug] = $schema_data;

                UPT_Schema_Store::save_schemas($all_schemas);
            }

            add_settings_error('upt_notices', 'schema_renamed', 'Esquema renomeado com sucesso!', 'success');
            $redirect_after = admin_url('admin.php?page=upt_schema_builder&schema=' . $new_slug);
            wp_safe_redirect($redirect_after);
            exit;
        }

        
elseif (isset($_POST['action']) && $_POST['action'] === 'upt_save_schema_settings') {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'upt_save_schema_settings_nonce')) {
                return;
            }

            $schema_slug = sanitize_text_field($_POST['schema_slug']);
            $items_limit = isset($_POST['schema_items_limit']) ? absint($_POST['schema_items_limit']) : 0;
            $items_min = isset($_POST['schema_items_min']) ? absint($_POST['schema_items_min']) : 0;
            $limit_per_category = isset($_POST['schema_items_limit_per_category']) && '1' === $_POST['schema_items_limit_per_category'];
            $limit_max_per_category = isset($_POST['schema_items_limit_max_per_category']) && '1' === $_POST['schema_items_limit_max_per_category'];

            // Se existir limite máximo (> 0), o mínimo não pode ser maior que o máximo
            if ($items_limit > 0 && $items_min > $items_limit) {
                $items_min = $items_limit;
            }

            $all_schemas = UPT_Schema_Store::get_schemas();
            if (!isset($all_schemas[$schema_slug])) {
                $all_schemas[$schema_slug] = [];
            }
            $all_schemas[$schema_slug]['items_limit'] = $items_limit;
            $all_schemas[$schema_slug]['items_min'] = $items_min;
            $all_schemas[$schema_slug]['items_limit_per_category'] = $limit_per_category;
            $all_schemas[$schema_slug]['items_limit_max_per_category'] = $limit_max_per_category;
            if (UPT_Schema_Store::save_schemas($all_schemas)) {
                add_settings_error('upt_notices', 'schema_saved', 'Limite do esquema atualizado!', 'success');
            }
            else {
                add_settings_error('upt_notices', 'schema_error', 'Erro ao salvar o limite do esquema.', 'error');
            }

            wp_safe_redirect($redirect_url);
            exit;
        }
        elseif (isset($_POST['action']) && $_POST['action'] === 'upt_save_field') {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'upt_save_field_nonce'))
                return;

            $all_schemas = UPT_Schema_Store::get_schemas();
            $schema_slug = sanitize_text_field($_POST['schema_slug']);
            $label = sanitize_text_field($_POST['field_label']);
            $is_editing = isset($_POST['original_field_id']) && !empty($_POST['original_field_id']);
            $field_type = sanitize_text_field($_POST['field_type']);

            $id = $schema_slug . '_' . sanitize_title(str_replace('²', '2', $label));
            $original_field_id = isset($_POST['original_field_id']) ? sanitize_text_field($_POST['original_field_id']) : $id;


            $new_field_data = [
                'id' => $id,
                'label' => $label,
                'hint' => isset($_POST['field_hint']) ? sanitize_text_field($_POST['field_hint']) : '',
                'type' => $field_type,
                'required' => isset($_POST['field_required']),
                'multiple' => isset($_POST['field_multiple']),
                'allow_new' => isset($_POST['field_allow_new']),
                'allow_rename_option' => ($field_type === 'select') ? isset($_POST['field_allow_rename_option']) : false,
                'allow_delete_option' => ($field_type === 'select') ? isset($_POST['field_allow_delete_option']) : false,
                'allow_new_category' => ($field_type === 'taxonomy') ? isset($_POST['field_allow_new_category']) : false,
                'enable_subcategories' => ($field_type === 'taxonomy') ? isset($_POST['field_enable_subcategories']) : false,
                // Se verdadeiro, força seleção de subcategoria (quando existir) no formulário upt
                'subcategories_required' => ($field_type === 'taxonomy') ? isset($_POST['field_subcategories_required']) : false,
                // Rótulo do campo dependente (subcategorias) no formulário upt
                'subcategories_label' => ($field_type === 'taxonomy' && isset($_POST['field_subcategories_label']))
                ? sanitize_text_field($_POST['field_subcategories_label'])
                : 'Subcategoria',
                'max_length' => isset($_POST['field_max_length']) ? absint($_POST['field_max_length']) : 0,
                'rows' => isset($_POST['field_rows']) ? absint($_POST['field_rows']) : 0,
                'excerpt_required' => ('blog_post' === $field_type) ? isset($_POST['field_excerpt_required']) : false,
                'excerpt_max_length' => ('blog_post' === $field_type && isset($_POST['field_excerpt_max_length'])) ? absint($_POST['field_excerpt_max_length']) : 0
            ];

            if ($new_field_data['type'] === 'select' && isset($_POST['field_options'])) {
                $options = sanitize_textarea_field($_POST['field_options']);
                $new_field_data['options'] = str_replace("\r\n", "|", $options);
            }
            if ($new_field_data['type'] === 'time' && isset($_POST['field_time_format'])) {
                $new_field_data['time_format'] = sanitize_text_field($_POST['field_time_format']);
            }
            if ($new_field_data['type'] === 'unit_measure' && isset($_POST['field_unit_options'])) {
                $unit_options = sanitize_textarea_field($_POST['field_unit_options']);
                $new_field_data['unit_options'] = str_replace("\r\n", "|", $unit_options);
            }
            if ($new_field_data['type'] === 'relationship' && isset($_POST['field_relationship_schema'])) {
                $new_field_data['schema_filter'] = sanitize_text_field($_POST['field_relationship_schema']);
            }

            if ($is_editing) {
                $field_index = -1;
                if (isset($all_schemas[$schema_slug]['fields'])) {
                    foreach ($all_schemas[$schema_slug]['fields'] as $key => $field) {
                        if ($field['id'] === $original_field_id) {
                            $field_index = $key;
                            break;
                        }
                    }
                }
                if ($field_index !== -1) {
                    // Se o ID mudou, migra os metadados existentes dos itens desse esquema
                    if ($original_field_id !== $id) {
                        $posts_to_update = get_posts([
                            'post_type' => 'catalog_item',
                            'posts_per_page' => -1,
                            'post_status' => 'any',
                            'tax_query' => [
                                [
                                    'taxonomy' => 'catalog_schema',
                                    'field' => 'slug',
                                    'terms' => $schema_slug,
                                ],
                            ],
                        ]);

                        if ($posts_to_update) {
                            foreach ($posts_to_update as $post_obj) {
                                $old_value = get_post_meta($post_obj->ID, $original_field_id, true);
                                if ($old_value !== '' && $old_value !== null) {
                                    update_post_meta($post_obj->ID, $id, $old_value);
                                    delete_post_meta($post_obj->ID, $original_field_id);
                                }
                            }
                        }
                    }

                    $all_schemas[$schema_slug]['fields'][$field_index] = $new_field_data;
                    add_settings_error('upt_notices', 'schema_saved', 'Campo atualizado!', 'success');
                }
            }
            else {
                if (!isset($all_schemas[$schema_slug]['fields'])) {
                    $all_schemas[$schema_slug]['fields'] = [];
                }
                $all_schemas[$schema_slug]['fields'][] = $new_field_data;
                add_settings_error('upt_notices', 'schema_saved', 'Campo salvo!', 'success');
            }

            if (!UPT_Schema_Store::save_schemas($all_schemas)) {
                add_settings_error('upt_notices', 'schema_error', 'Erro ao salvar.', 'error');
            }
            wp_safe_redirect($redirect_url);
            exit;
        }

        elseif (isset($_POST['action']) && $_POST['action'] === 'upt_delete_schema_do') {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'upt_delete_schema_do_nonce')) {
                return;
            }

            $term_id = isset($_POST['schema_id']) ? absint($_POST['schema_id']) : 0;
            $mode = isset($_POST['delete_mode']) ? sanitize_text_field($_POST['delete_mode']) : 'keep';
            $target_schema_slug = isset($_POST['target_schema']) ? sanitize_text_field($_POST['target_schema']) : '';

            if (!$term_id) {
                add_settings_error('upt_notices', 'schema_error', 'Esquema inválido.', 'error');
                wp_safe_redirect(admin_url('admin.php?page=upt_schema_builder'));
                exit;
            }

            $term = get_term($term_id, 'catalog_schema');
            if (!$term || is_wp_error($term)) {
                add_settings_error('upt_notices', 'schema_error', 'Esquema não encontrado.', 'error');
                wp_safe_redirect(admin_url('admin.php?page=upt_schema_builder'));
                exit;
            }

            $schema_slug = $term->slug;

            // Busca todos os itens associados a este esquema.
            $items_q = new WP_Query(
            [
                'post_type' => 'catalog_item',
                'posts_per_page' => -1,
                'post_status' => 'any',
                'tax_query' => [
                    [
                        'taxonomy' => 'catalog_schema',
                        'field' => 'slug',
                        'terms' => $schema_slug,
                    ],
                ],
                'fields' => 'ids',
            ]
                );

            $item_ids = [];
            if ($items_q->have_posts()) {
                $item_ids = $items_q->posts;
            }
            wp_reset_postdata();

            $mode = in_array($mode, ['keep', 'move', 'delete_items'], true) ? $mode : 'keep';
            $items_message = '';

            if ('keep' === $mode) {
                if (!empty($item_ids)) {
                    foreach ($item_ids as $post_id) {
                        wp_remove_object_terms($post_id, $schema_slug, 'catalog_schema');
                    }
                }
                $items_message = 'Itens mantidos sem esquema.';
            }
            elseif ('move' === $mode) {
                $target_term = null;
                if ($target_schema_slug) {
                    $target_term = get_term_by('slug', $target_schema_slug, 'catalog_schema');
                }

                if (!$target_term || is_wp_error($target_term)) {
                    add_settings_error('upt_notices', 'schema_error', 'Esquema de destino inválido.', 'error');
                    wp_safe_redirect(admin_url('admin.php?page=upt_schema_builder&schema_delete_confirm=' . $term_id));
                    exit;
                }

                $target_slug = $target_term->slug;

                if (!empty($item_ids)) {
                    foreach ($item_ids as $post_id) {
                        // Define apenas o novo esquema para o item.
                        wp_set_object_terms($post_id, $target_slug, 'catalog_schema', false);
                    }
                }
                $items_message = 'Itens movidos para o esquema de destino.';
            }
            elseif ('delete_items' === $mode) {
                if (!empty($item_ids)) {
                    foreach ($item_ids as $post_id) {
                        wp_trash_post($post_id);
                    }
                }
                $items_message = 'Itens enviados para a lixeira.';
            }

            // Agora exclui o esquema em si.
            if (wp_delete_term($term_id, 'catalog_schema')) {
                $all_schemas = UPT_Schema_Store::get_schemas();
                if (isset($all_schemas[$schema_slug])) {
                    unset($all_schemas[$schema_slug]);
                    UPT_Schema_Store::save_schemas($all_schemas);
                }

                $full_message = 'Esquema excluído.';
                if ($items_message) {
                    $full_message .= ' ' . $items_message;
                }

                add_settings_error('upt_notices', 'schema_deleted', $full_message, 'success');
            }
            else {
                add_settings_error('upt_notices', 'schema_error', 'Erro ao excluir o esquema.', 'error');
            }

            wp_safe_redirect(admin_url('admin.php?page=upt_schema_builder'));
            exit;
        }

        elseif (isset($_GET['action']) && $_GET['action'] === 'delete_schema') {
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'upt_delete_schema_nonce')) {
                return;
            }

            $term_id = absint($_GET['schema_id']);
            $term = get_term($term_id, 'catalog_schema');

            if (!$term || is_wp_error($term)) {
                add_settings_error('upt_notices', 'schema_error', 'Esquema não encontrado.', 'error');
                wp_safe_redirect(admin_url('admin.php?page=upt_schema_builder'));
                exit;
            }

            // Redireciona para a tela de confirmação de exclusão com opções para os itens.
            $redirect_url = add_query_arg(
            [
                'page' => 'upt_schema_builder',
                'schema_delete_confirm' => $term_id,
            ],
                admin_url('admin.php')
            );

            wp_safe_redirect($redirect_url);
            exit;
        }
        elseif (isset($_GET['action']) && $_GET['action'] === 'delete_field') {
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'upt_delete_field_nonce'))
                return;
            $schema_slug = sanitize_text_field($_GET['schema']);
            $field_id = sanitize_text_field($_GET['field_id']);
            $all_schemas = UPT_Schema_Store::get_schemas();
            if (isset($all_schemas[$schema_slug]['fields'])) {
                $all_schemas[$schema_slug]['fields'] = array_values(array_filter($all_schemas[$schema_slug]['fields'], function ($f) use ($field_id) {
                    return $f['id'] !== $field_id;
                }));
                if (UPT_Schema_Store::save_schemas($all_schemas))
                    add_settings_error('upt_notices', 'schema_deleted', 'Campo excluído!', 'success');
                else
                    add_settings_error('upt_notices', 'schema_error', 'Erro ao excluir.', 'error');
            }
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    public static function ajax_rename_schema()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissão negada.']);
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'upt_rename_schema_nonce')) {
            wp_send_json_error(['message' => 'Falha de segurança. Recarregue a página.']);
        }

        $schema_slug = isset($_POST['schema_slug']) ? sanitize_title(sanitize_text_field(wp_unslash($_POST['schema_slug']))) : '';
        $new_schema_name = isset($_POST['schema_new_name']) ? sanitize_text_field(wp_unslash($_POST['schema_new_name'])) : '';

        if (empty($schema_slug) || empty($new_schema_name)) {
            wp_send_json_error(['message' => 'Nome ou slug do esquema inválidos.']);
        }

        $term = get_term_by('slug', $schema_slug, 'catalog_schema');
        if (!$term || is_wp_error($term)) {
            wp_send_json_error(['message' => 'Esquema não encontrado.']);
        }

        $old_slug = $term->slug;

        $update_result = wp_update_term(
            $term->term_id,
            'catalog_schema',
        [
            'name' => $new_schema_name,
            'slug' => sanitize_title($new_schema_name),
        ]
        );

        if (is_wp_error($update_result)) {
            wp_send_json_error(['message' => 'Erro ao renomear o esquema: ' . $update_result->get_error_message()]);
        }

        $updated_term = get_term($update_result['term_id'], 'catalog_schema');
        $new_slug = ($updated_term && !is_wp_error($updated_term)) ? $updated_term->slug : $old_slug;

        // Atualiza store de esquemas e os IDs de campos
        $all_schemas = UPT_Schema_Store::get_schemas();

        if (isset($all_schemas[$old_slug])) {
            $schema_data = $all_schemas[$old_slug];
            unset($all_schemas[$old_slug]);

            $old_prefix = $old_slug . '_';
            $new_prefix = $new_slug . '_';

            if (isset($schema_data['fields']) && is_array($schema_data['fields'])) {
                global $wpdb;

                foreach ($schema_data['fields'] as &$field) {
                    if (!isset($field['id'])) {
                        continue;
                    }

                    $old_id = $field['id'];

                    // Só mexe em campos que começam com o prefixo antigo
                    if (strpos($old_id, $old_prefix) === 0) {
                        $new_id = $new_prefix . substr($old_id, strlen($old_prefix));

                        // Atualiza as chaves meta dos itens existentes
                        $wpdb->query(
                            $wpdb->prepare(
                            "UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
                            $new_id,
                            $old_id
                        )
                        );

                        // Atualiza referências em dados do Elementor
                        $like = '%' . $wpdb->esc_like($old_id) . '%';
                        $meta_rows = $wpdb->get_results(
                            $wpdb->prepare(
                            "SELECT meta_id, meta_value
                             FROM {$wpdb->postmeta}
                             WHERE meta_key IN ('_elementor_data','_elementor_page_settings','_elementor_template_meta')
                               AND meta_value LIKE %s",
                            $like
                        )
                        );

                        if ($meta_rows) {
                            foreach ($meta_rows as $row) {
                                $new_value = str_replace($old_id, $new_id, $row->meta_value);
                                if ($new_value !== $row->meta_value) {
                                    $wpdb->update(
                                        $wpdb->postmeta,
                                    ['meta_value' => $new_value],
                                    ['meta_id' => $row->meta_id],
                                    ['%s'],
                                    ['%d']
                                    );
                                }
                            }
                        }

                        $field['id'] = $new_id;
                    }
                }
                unset($field);
            }

            $schema_data['label'] = $new_schema_name;
            $all_schemas[$new_slug] = $schema_data;

            UPT_Schema_Store::save_schemas($all_schemas);
        }

        $delete_url = '';
        if ($updated_term && !is_wp_error($updated_term)) {
            $delete_url = wp_nonce_url(
                admin_url('admin.php?page=upt_schema_builder&action=delete_schema&schema_id=' . $updated_term->term_id),
                'upt_delete_schema_nonce'
            );
        }

        wp_send_json_success(
        [
            'old_slug' => $old_slug,
            'new_slug' => $new_slug,
            'new_name' => $new_schema_name,
            'delete_url' => $delete_url,
        ]
        );
    }

    public static function create_admin_page_about()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
?>
        <div class="wrap upt-about">
            <h1><?php esc_html_e('Sobre o upt', 'upt'); ?></h1>
            <p>
                O upt é um construtor de catálogos e dashboards que combina esquemas personalizados,
                cards dinâmicos, biblioteca de mídia própria e integração com Elementor.
            </p>

            <h2>1. Builder de Esquemas</h2>
            <p>Permite criar, editar, duplicar e excluir esquemas que definem os campos de cada item do catálogo.</p>
            <ul>
                <li>Criação de esquemas com múltiplos tipos de campo.</li>
                <li>Exportar e importar esquemas em XML, incluindo metadados de mídia.</li>
                <li>Recriação de campos de mídia (imagem, vídeo, galeria) ao importar.</li>
            </ul>

            <h2>2. Campos disponíveis</h2>
            <p>Conjunto de tipos de campo usados para modelar os itens.</p>
            <ul>
                <li>Texto, textarea, número, URL, data.</li>
                <li>WYSIWYG e editor completo para conteúdo rico.</li>
                <li>Select, checkbox, switch.</li>
                <li>Cor, ícone.</li>
                <li>Imagem nativa, vídeo, galeria e repeater.</li>
            </ul>

            <h2>3. CRUD de Itens (Cards)</h2>
            <p>Gestão dos itens do catálogo baseados em um esquema.</p>
            <ul>
                <li>Criar, editar, clonar e excluir itens.</li>
                <li>Ordenação manual (drag-and-drop) e ordenação automática.</li>
                <li>Busca, filtros, paginação e visualização de campos de mídia.</li>
            </ul>

            <h2>4. Salvamento e carregamento</h2>
            <p>Persistência dos dados de forma rápida e confiável.</p>
            <ul>
                <li>Salvamento via AJAX sem recarregar a página.</li>
                <li>Atualização em tempo real do grid após salvar.</li>
                <li>Recuperação completa dos valores ao editar, incluindo WYSIWYG e editor completo.</li>
            </ul>

            <h2>5. Biblioteca de Mídias do upt</h2>
            <p>Camada própria de organização de arquivos para o catálogo.</p>
            <ul>
                <li>Upload de imagens e vídeos, com suporte a pastas.</li>
                <li>Importar ZIP de mídia mantendo a estrutura de pastas.</li>
                <li>Seleção simples ou múltipla (galeria) com badges de tipo/extensão.</li>
                <li>Exportar mídia em ZIP, por seleção ou completo.</li>
            </ul>

            <h2>6. Renderização no Elementor</h2>
            <p>Widgets para exibir os dados do upt no front-end.</p>
            <ul>
                <li>Widgets de listagem, dashboard e ações.</li>
                <li>Loop de itens com mapeamento de campos para HTML.</li>
                <li>Suporte a campos de mídia, como imagem, vídeo e galeria.</li>
            </ul>

            <h2>7. Sistema de versão interna</h2>
            <p>Controle de versão e regras de modificação do plugin.</p>
            <ul>
                <li>Arquivo de regras para IAs e automações (UPT_IA_RULES.md).</li>
                <li>Exibição da versão instalada no painel.</li>
            </ul>

            <h2>8. UI/UX do Dashboard</h2>
            <p>Interface voltada para leitura rápida e operação diária.</p>
            <ul>
                <li>Grid responsivo de cards e tabela com cabeçalho fixo.</li>
                <li>Zebra, máscara de telefone e micro animações.</li>
                <li>Modais para adicionar/editar, “Salvar e adicionar outro” e atalho Ctrl+Enter.</li>
                <li>Estados vazios, skeleton e toasts de feedback.</li>
            </ul>

            <h2>9. Exportação e importação avançada</h2>
            <p>Mecanismos para transportar estrutura e dados entre sites.</p>
            <ul>
                <li>Exportar esquemas com informação de mídia associada.</li>
                <li>Importar recriando campos e tentando manter IDs quando possível.</li>
                <li>Scan de ZIP de mídia para localizar arquivos usados pelos campos.</li>
            </ul>

            <h2>10. Funções automáticas internas</h2>
            <p>Camada técnica que mantém o plugin estável.</p>
            <ul>
                <li>Sanitização e normalização de dados.</li>
                <li>Tratamento de JSON e compatibilidade com multisite.</li>
                <li>Hooks e logging interno para debug controlado.</li>
            </ul>
        </div>
        <?php
    }


    public static function create_admin_page_unused_media()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;

        $state = get_option(
            'upt_unused_scan_state',
        [
            'last_post_id' => 0,
            'finished_posts' => false,
            'options_scanned' => false,
        ]
        );

        if (!is_array($state)) {
            $state = [
                'last_post_id' => 0,
                'finished_posts' => false,
            ];
        }

        $used_attachments = get_option('upt_used_attachments', []);
        if (!is_array($used_attachments)) {
            $used_attachments = [];
        }

        $message = '';
        $errors = [];

        // Reset manual do estado
        if (
        isset($_POST['upt_unused_reset'])
        && check_admin_referer('upt_unused_reset_action', 'upt_unused_reset_nonce')
        ) {
            delete_option('upt_unused_scan_state');
            delete_option('upt_used_attachments');
            $state = [
                'last_post_id' => 0,
                'finished_posts' => false,
                'options_scanned' => false,
            ];
            $used_attachments = [];
            $message = __('Estado de varredura resetado. Você pode iniciar um novo scan.', 'upt');
            $last_action = 'reset';
        }

        // Exclusão de mídias selecionadas
        if (
        isset($_POST['upt_unused_delete'])
        && !empty($_POST['upt_unused_ids'])
        && check_admin_referer('upt_unused_delete_action', 'upt_unused_delete_nonce')
        ) {
            $ids = array_map('absint', (array)$_POST['upt_unused_ids']);
            $deleted = 0;

            foreach ($ids as $att_id) {
                if (get_post_type($att_id) !== 'attachment') {
                    continue;
                }
                $res = wp_delete_attachment($att_id, true);
                if ($res) {
                    $deleted++;
                    unset($used_attachments[$att_id]);
                }
                else {
                    $errors[] = $att_id;
                }
            }

            if ($deleted > 0) {
                $message = sprintf(
                    _n('%d mídia foi apagada.', '%d mídias foram apagadas.', $deleted, 'upt'),
                    $deleted
                );
            }

            update_option('upt_used_attachments', $used_attachments, false);
        }

        $unused_attachments_found = [];
        $batch_type = '';
        $last_action = 'none';

        // =========================
        // Etapa 1: varrer conteúdo
        // =========================
        if (
        isset($_POST['upt_unused_scan_posts'])
        && check_admin_referer('upt_unused_scan_posts_action', 'upt_unused_scan_posts_nonce')
        ) {
            if (!$state['finished_posts']) {

                $limit = 30;

                $posts = $wpdb->get_results(
                    $wpdb->prepare(
                    "SELECT ID, post_content
                         FROM {$wpdb->posts}
                         WHERE post_status NOT IN ('trash','auto-draft','revision')
                           AND post_type <> 'attachment'
                           AND ID > %d
                         ORDER BY ID ASC
                         LIMIT %d",
                    (int)$state['last_post_id'],
                    (int)$limit
                )
                );

                if ($posts) {
                    $max_id = (int)$state['last_post_id'];

                    foreach ($posts as $p) {
                        $post_id = (int)$p->ID;
                        $content = (string)$p->post_content;

                        if ($post_id > $max_id) {
                            $max_id = $post_id;
                        }

                        // 1) imagem destacada
                        $thumb_id = (int)get_post_thumbnail_id($post_id);
                        if ($thumb_id) {
                            $used_attachments[$thumb_id] = true;
                        }

                        // 2) meta com possíveis IDs
                        $meta = get_post_meta($post_id);
                        if (!empty($meta)) {
                            foreach ($meta as $meta_vals) {
                                foreach ((array)$meta_vals as $meta_val) {
                                    if (is_string($meta_val)) {
                                        if (preg_match_all('/"(\d+)"/', $meta_val, $m_all)) {
                                            foreach ($m_all[1] as $id_str) {
                                                $mid = (int)$id_str;
                                                if ($mid > 0 && get_post_type($mid) === 'attachment') {
                                                    $used_attachments[$mid] = true;
                                                }
                                            }
                                        }

                                        if (preg_match_all('/\b(\d{2,})\b/', $meta_val, $m_num)) {
                                            foreach ($m_num[1] as $id_str) {
                                                $mid = (int)$id_str;
                                                if ($mid > 0 && get_post_type($mid) === 'attachment') {
                                                    $used_attachments[$mid] = true;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // 3) conteúdo (wp-image-ID, attachment_id, id=ID)
                        if ($content !== '') {
                            if (preg_match_all('/wp-image-(\d+)/', $content, $m_ids)) {
                                foreach ($m_ids[1] as $id_str) {
                                    $mid = (int)$id_str;
                                    if ($mid > 0 && get_post_type($mid) === 'attachment') {
                                        $used_attachments[$mid] = true;
                                    }
                                }
                            }

                            if (preg_match_all('/attachment_id="(\d+)"/', $content, $m_ids2)) {
                                foreach ($m_ids2[1] as $id_str) {
                                    $mid = (int)$id_str;
                                    if ($mid > 0 && get_post_type($mid) === 'attachment') {
                                        $used_attachments[$mid] = true;
                                    }
                                }
                            }

                            if (preg_match_all('/\bid=(\d+)/', $content, $m_ids3)) {
                                foreach ($m_ids3[1] as $id_str) {
                                    $mid = (int)$id_str;
                                    if ($mid > 0 && get_post_type($mid) === 'attachment') {
                                        $used_attachments[$mid] = true;
                                    }
                                }
                            }
                        }
                    }

                    $state['last_post_id'] = $max_id;
                    $message = sprintf(
                        __('Varredura de conteúdo avançou até o post ID %d.', 'upt'),
                        $state['last_post_id']
                    );
                }
                else {
                    $state['finished_posts'] = true;
                    $message = __('Varredura de conteúdo concluída. Agora é possível listar mídias potencialmente não usadas.', 'upt');

                    // Após concluir posts, faz uma varredura única em opções relevantes
                    if (empty($state['options_scanned'])) {
                        $options = $wpdb->get_results(
                            "SELECT option_value FROM {$wpdb->options}
                             WHERE option_name LIKE '%elementor%'
                                OR option_name LIKE '%upt%'
                                OR option_name LIKE 'theme_mods_%'"
                        );

                        if ($options) {
                            foreach ($options as $opt_row) {
                                $raw_val = $opt_row->option_value;
                                $maybe = maybe_unserialize($raw_val);

                                if (is_string($maybe)) {
                                    $str = $maybe;
                                }
                                else {
                                    $str = wp_json_encode($maybe);
                                }

                                if (is_string($str) && $str !== '') {
                                    if (preg_match_all('/"(\d+)"/', $str, $m_all)) {
                                        foreach ($m_all[1] as $id_str) {
                                            $mid = (int)$id_str;
                                            if ($mid > 0 && get_post_type($mid) === 'attachment') {
                                                $used_attachments[$mid] = true;
                                            }
                                        }
                                    }

                                    if (preg_match_all('/\b(\d{2,})\b/', $str, $m_num)) {
                                        foreach ($m_num[1] as $id_str) {
                                            $mid = (int)$id_str;
                                            if ($mid > 0 && get_post_type($mid) === 'attachment') {
                                                $used_attachments[$mid] = true;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $state['options_scanned'] = true;
                    }
                }

                update_option('upt_unused_scan_state', $state, false);
                update_option('upt_used_attachments', $used_attachments, false);
            }
            else {
                $message = __('Varredura de conteúdo já está marcada como concluída.', 'upt');
            }

            $batch_type = 'posts';
            $last_action = 'scan';
        }

        // =========================
        // Etapa 2: listar anexos
        // =========================
        $per_page = 200;
        $total_attachments = (int)$wpdb->get_var(
            "SELECT COUNT(ID) FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
               AND post_status = 'inherit'"
        );
        $total_pages = $total_attachments > 0 ? (int)ceil($total_attachments / $per_page) : 1;

        // Cálculo de totais de usadas / não usadas (quando já houve alguma varredura)
        $total_used = null;
        $total_unused = null;

        if ((int)$state['last_post_id'] > 0 || !empty($state['finished_posts']) || !empty($state['options_scanned'])) {
            $total_used = 0;
            $total_unused = 0;

            $all_atts = $wpdb->get_results(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_type = 'attachment'
                   AND post_status = 'inherit'"
            );

            if ($all_atts) {
                foreach ($all_atts as $row) {
                    $att_id = (int)$row->ID;

                    if (isset($used_attachments[$att_id]) && $used_attachments[$att_id]) {
                        $total_used++;
                    }
                    else {
                        $total_unused++;
                    }
                }
            }
        }

        $current_page = null;

        if (isset($_POST['upt_unused_media_page'])) {
            $current_page = (int)$_POST['upt_unused_media_page'];
        }
        else {
            // Recupera última página usada nesta tela, para manter seleção ao recarregar
            $saved_page = get_option('upt_unused_media_page_last', 1);
            $current_page = (int)$saved_page;
        }

        if ($current_page < 1) {
            $current_page = 1;
        }
        if ($current_page > $total_pages) {
            $current_page = $total_pages;
        }

        // Salva página atual para próximas requisições (inclusive quando outros formulários forem enviados)
        update_option('upt_unused_media_page_last', $current_page, false);


        if (
        isset($_POST['upt_unused_list_media'])
        && check_admin_referer('upt_unused_list_media_action', 'upt_unused_list_media_nonce')
        ) {

            if ((int)$state['last_post_id'] === 0 && empty($state['finished_posts'])) {
                $message = __('Antes de listar mídias potencialmente não usadas, execute pelo menos um lote de varredura de conteúdo.', 'upt');
            }
            else {

                $target_start = ($current_page - 1) * $per_page; // índice lógico de início dentro da lista de NÃO usadas
                $target_end = $target_start + $per_page;
                $unused_counter = 0;
                $collected = [];

                $scan_offset = 0;
                $chunk_size = 400;
                $safety = 0;

                while ($unused_counter < $target_end) {
                    $attachments_chunk = $wpdb->get_results(
                        $wpdb->prepare(
                        "SELECT ID FROM {$wpdb->posts}
                             WHERE post_type = 'attachment'
                               AND post_status = 'inherit'
                             ORDER BY ID ASC
                             LIMIT %d OFFSET %d",
                        (int)$chunk_size,
                        (int)$scan_offset
                    )
                    );

                    if (empty($attachments_chunk)) {
                        break;
                    }

                    foreach ($attachments_chunk as $att) {
                        $att_id = (int)$att->ID;

                        // se já marcado como usado via varreduras, pula
                        if (isset($used_attachments[$att_id]) && $used_attachments[$att_id]) {
                            continue;
                        }

                        // este attachment é considerado NÃO usado
                        if ($unused_counter >= $target_start && $unused_counter < $target_end) {
                            $collected[] = $att_id;
                        }
                        $unused_counter++;

                        if ($unused_counter >= $target_end) {
                            break;
                        }
                    }

                    $scan_offset += $chunk_size;
                    $safety++;

                    if ($safety > 500) {
                        break;
                    }
                }

                $unused_attachments_found = $collected;

                if (!empty($unused_attachments_found)) {
                    $message = sprintf(
                        __('Foram encontradas %d mídias potencialmente não utilizadas nesta página de resultados.', 'upt'),
                        count($unused_attachments_found)
                    );
                }
                else {
                    $message = __('Nenhuma mídia potencialmente não utilizada foi encontrada nesta página.', 'upt');
                }

                update_option('upt_used_attachments', $used_attachments, false);
            }

            $batch_type = 'media';
            $last_action = 'list';
        }


?>
        <div class="wrap upt-wrap">
            <h1><?php esc_html_e('Mídias não usadas', 'upt'); ?></h1>

            <?php if (!empty($message)): ?>
                <div class="notice notice-info"><p><?php echo esc_html($message); ?></p></div>
            <?php
        endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="notice notice-error"><p><?php esc_html_e('Algumas mídias não puderam ser apagadas.', 'upt'); ?></p></div>
            <?php
        endif; ?>

            <p><?php esc_html_e('Passo 1: faça varreduras parciais do conteúdo para identificar quais mídias estão sendo usadas em posts, páginas, produtos, modelos, etc.', 'upt'); ?></p>
            <p><?php esc_html_e('Passo 2: liste as mídias que nunca foram vistas em nenhum conteúdo e apague as que realmente não fazem mais sentido no site.', 'upt'); ?></p>

            <p>
                <strong><?php esc_html_e('Estado atual da varredura de conteúdo:', 'upt'); ?></strong><br />
                <?php
        if ($state['finished_posts']) {
            esc_html_e('Varredura de conteúdo concluída.', 'upt');
        }
        else {
            printf(
                esc_html__('Último post analisado: ID %d. Ainda há conteúdo a ser varrido.', 'upt'),
                (int)$state['last_post_id']
            );
        }
?>
            </p>

            <p>
                <strong><?php esc_html_e('Resumo das mídias:', 'upt'); ?></strong><br />
                <?php if ($total_attachments > 0): ?>
                    <?php
            printf(
                esc_html__('Total de mídias: %d.', 'upt'),
                (int)$total_attachments
            );
?>
                    <br />
                    <?php if ($total_used !== null && $total_unused !== null): ?>
                        <?php
                printf(
                    esc_html__('Total usadas: %d.', 'upt'),
                    (int)$total_used
                );
?>
                        <br />
                        <?php
                printf(
                    esc_html__('Total potencialmente não usadas: %d.', 'upt'),
                    (int)$total_unused
                );
?>
                    <?php
            else: ?>
                        <?php esc_html_e('Execute a varredura de conteúdo para calcular usadas e não usadas.', 'upt'); ?>
                    <?php
            endif; ?>
                <?php
        else: ?>
                    <?php esc_html_e('Nenhuma mídia encontrada na biblioteca.', 'upt'); ?>
                <?php
        endif; ?>
            </p>

            <form method="post" style="margin-bottom: 20px;" id="upt-scan-posts-form">
                <?php wp_nonce_field('upt_unused_scan_posts_action', 'upt_unused_scan_posts_nonce'); ?>
                <input type="hidden" name="upt_unused_scan_posts" value="1" />
                <p>
                    <button type="submit" class="button button-secondary" id="upt-scan-posts-once">
                        <?php esc_html_e('Avançar varredura de conteúdo (lote)', 'upt'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="upt-start-auto-scan">
                        <?php esc_html_e('Iniciar varredura automática', 'upt'); ?>
                    </button>
                    <button type="button" class="button" id="upt-stop-auto-scan">
                        <?php esc_html_e('Parar varredura automática', 'upt'); ?>
                    </button>
                </p>
            </form>

            <form method="post" style="margin-bottom: 20px;">
                <?php wp_nonce_field('upt_unused_list_media_action', 'upt_unused_list_media_nonce'); ?>
                <p>
                    <label for="upt_unused_media_page"><strong><?php esc_html_e('Página de anexos para analisar (cada página = 200 mídias):', 'upt'); ?></strong></label>
                    <select name="upt_unused_media_page" id="upt_unused_media_page">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <option value="<?php echo esc_attr($i); ?>" <?php selected($current_page, $i); ?>>
                                <?php echo esc_html($i); ?>
                            </option>
                        <?php
        endfor; ?>
                    </select>
                    <span class="description">
                        <?php
        printf(
            esc_html__('de %d páginas (%d mídias no total).', 'upt'),
            (int)$total_pages,
            (int)$total_attachments
        );
?>
                    </span>
                    <button type="submit" class="button button-secondary" name="upt_unused_list_media" value="1">
                        <?php esc_html_e('Listar mídias potencialmente não usadas desta página', 'upt'); ?>
                    </button>
                </p>
            </form>

            <form method="post" style="margin-bottom: 20px;">
                <?php wp_nonce_field('upt_unused_reset_action', 'upt_unused_reset_nonce'); ?>
                <p>
                    <button type="submit" class="button" name="upt_unused_reset" value="1" onclick="return confirm('<?php echo esc_js(__('Tem certeza que deseja resetar completamente o estado da varredura? Isso não apaga nenhuma mídia, apenas faz o índice de uso ser reconstruído do zero.', 'upt')); ?>');">
                        <?php esc_html_e('Resetar estado de varredura', 'upt'); ?>
                    </button>
                </p>
            </form>

            <?php if (!empty($unused_attachments_found)): ?>
                <hr />
                <h2><?php esc_html_e('Mídias potencialmente não usadas nesta página de anexos', 'upt'); ?></h2>

                <form method="post">
                    <?php wp_nonce_field('upt_unused_delete_action', 'upt_unused_delete_nonce'); ?>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width:40px;"><input type="checkbox" id="upt-select-all-unused" /></th>
                                <th><?php esc_html_e('Prévia', 'upt'); ?></th>
                                <th><?php esc_html_e('Arquivo', 'upt'); ?></th>
                                <th><?php esc_html_e('Data', 'upt'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unused_attachments_found as $att_id): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="upt_unused_ids[]" value="<?php echo esc_attr($att_id); ?>" />
                                    </td>
                                    <td>
                                        <?php echo wp_get_attachment_image($att_id, [80, 80], true); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(get_the_title($att_id)); ?><br />
                                        <code><?php echo esc_html(basename(get_attached_file($att_id))); ?></code>
                                    </td>
                                    <td>
                                        <?php echo esc_html(get_the_date('', $att_id)); ?>
                                    </td>
                                </tr>
                            <?php
            endforeach; ?>
                        </tbody>
                    </table>

                    <p>
                        <button type="submit" class="button button-primary" name="upt_unused_delete" value="1" onclick="return confirm('<?php echo esc_js(__('Tem certeza que deseja remover permanentemente as mídias selecionadas?', 'upt')); ?>');">
                            <?php esc_html_e('Apagar mídias selecionadas', 'upt'); ?>
                        </button>
                    </p>
                </form>
            <?php
        elseif ($batch_type === 'media'): ?>
                <hr />
                <p><?php esc_html_e('Nenhuma mídia potencialmente não utilizada foi encontrada nesta página de anexos.', 'upt'); ?></p>
            <?php
        endif; ?>
        </div>

        <script>
        (function($){
            var finished   = <?php echo $state['finished_posts'] ? 'true' : 'false'; ?>;
            var lastAction = '<?php echo esc_js($last_action); ?>';
            $(function(){
                var autoEnabled = false;
                if (window.localStorage) {
                    autoEnabled = localStorage.getItem('upt_unused_auto_scan') === '1';

                    // Se o usuário acabou de resetar, desliga auto-scan
                    if (lastAction === 'reset') {
                        localStorage.setItem('upt_unused_auto_scan', '0');
                        autoEnabled = false;
                    }
                }

                // Só continua auto-scan se o último passo foi um scan de conteúdo
                if (!finished && autoEnabled && lastAction === 'scan' && $('#upt-scan-posts-form').length) {
                    setTimeout(function(){
                        $('#upt-scan-posts-form').trigger('submit');
                    }, 1000);
                }

                $(document).on('click', '#upt-start-auto-scan', function(e){
                    e.preventDefault();
                    if (window.localStorage) {
                        localStorage.setItem('upt_unused_auto_scan', '1');
                    }
                    $('#upt-scan-posts-form').trigger('submit');
                });

                $(document).on('click', '#upt-stop-auto-scan', function(e){
                    e.preventDefault();
                    if (window.localStorage) {
                        localStorage.setItem('upt_unused_auto_scan', '0');
                    }
                    alert('<?php echo esc_js(__('Varredura automática parada. Você pode continuar manualmente se desejar.', 'upt')); ?>');
                });

                $(document).on('change', '#upt-select-all-unused', function(){
                    var checked = $(this).is(':checked');
                    $('input[name="upt_unused_ids[]"]').prop('checked', checked);
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Campos extras na tela de criação de categoria (Catálogo → Categorias)
     * Permite criar subcategorias em lote sem exigir que o usuário configure "Categoria Pai" manualmente.
     */
    public static function render_category_subcategories_fields($taxonomy)
    {
        // Apenas usuários com permissão (mesma permissão usada no menu Catálogo)
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_nonce_field('upt_create_subcategories', 'upt_create_subcategories_nonce');
?>
        <div class="form-field term-upt-create-subcategories-wrap">
            <label for="upt_create_subcategories">
                <input type="checkbox" name="upt_create_subcategories" id="upt_create_subcategories" value="1" />
                <?php echo esc_html__('Criar subcategorias', 'upt'); ?>
            </label>
            <p class="description">
                <?php echo esc_html__('Marque para cadastrar subcategorias junto com esta categoria (uma por linha).', 'upt'); ?>
            </p>
        </div>
        <div class="form-field term-upt-subcategories-list-wrap" style="display:none;">
            <label for="upt_subcategories_list"><?php echo esc_html__('Subcategorias (uma por linha)', 'upt'); ?></label>
            <textarea name="upt_subcategories_list" id="upt_subcategories_list" rows="6" cols="40" placeholder="Ex:\nTráfego Pago\nSEO\nSocial Media"></textarea>
        </div>
        <?php
    }

    /**
     * Após criar a categoria (termo), cria automaticamente as subcategorias informadas.
     * Mantém total compatibilidade: se o checkbox não estiver marcado, não faz nada.
     */
    public static function handle_created_catalog_category($term_id, $tt_id)
    {
        // Somente no fluxo do admin (criação de termo via tela de categorias)
        if (!is_admin()) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (empty($_POST['upt_create_subcategories'])) {
            return;
        }

        // Nonce: na tela "Catálogo → Categorias" usamos o nonce próprio.
        // No metabox do editor (criação rápida via AJAX), o WP envia o nonce de "add-tag".
        $upt_nonce_ok = false;
        if (!empty($_POST['upt_create_subcategories_nonce'])) {
            $upt_nonce_ok = wp_verify_nonce(wp_unslash($_POST['upt_create_subcategories_nonce']), 'upt_create_subcategories');
        }
        if (!$upt_nonce_ok) {
            // Fallback para AJAX do metabox
            if (defined('DOING_AJAX') && DOING_AJAX) {
                $ajax_ok = check_ajax_referer('add-tag', '_ajax_nonce-add-tag', false);
                if (!$ajax_ok) {
                    return;
                }
            }
            else {
                return;
            }
        }

        $raw_list = '';
        if (isset($_POST['upt_subcategories_list'])) {
            $raw_list = sanitize_textarea_field(wp_unslash($_POST['upt_subcategories_list']));
        }

        if (!$raw_list) {
            return;
        }

        $lines = preg_split("/\r\n|\n|\r/", $raw_list);
        if (!is_array($lines) || empty($lines)) {
            return;
        }

        foreach ($lines as $line) {
            $name = trim((string)$line);
            if ($name === '') {
                continue;
            }

            // Se já existir uma subcategoria com esse nome sob este pai, não duplicar.
            $exists = term_exists($name, 'catalog_category', (int)$term_id);
            if ($exists) {
                continue;
            }

            wp_insert_term(
                $name,
                'catalog_category',
            [
                'parent' => (int)$term_id,
            ]
            );
        }
    }



    /**
     * If upt_show_all=1 is present in URL, disable pagination for upt admin lists.
     */
    public static function maybe_disable_admin_pagination($query)
    {
        if (!is_admin() || !$query instanceof \WP_Query || !$query->is_main_query()) {
            return;
        }

        if (empty($_GET['upt_show_all'])) {
            return;
        }

        if (!function_exists('get_current_screen')) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        // Only apply on upt catalog items list in wp-admin.
        if (isset($screen->post_type) && $screen->post_type === 'catalog_item') {
            $query->set('posts_per_page', -1);
        }
    }

    /**
     * If upt_show_all=1, show all terms on taxonomy screens.
     */
    public static function maybe_terms_per_page($per_page)
    {
        if (empty($_GET['upt_show_all'])) {
            return $per_page;
        }
        return 999999;
    }

}
