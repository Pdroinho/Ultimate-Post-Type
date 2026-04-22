<?php
if (!defined('ABSPATH'))
    exit;

class UPT_Imobiliaria_Importer
{
    private static $fields_map = [
        'CodigoImovel'      => ['label' => 'Código do Imóvel',       'type' => 'text'],
        'TipoImovel'        => ['label' => 'Tipo de Imóvel',        'type' => 'text'],
        'SubTipoImovel'     => ['label' => 'Subtipo',               'type' => 'text'],
        'CategoriaImovel'   => ['label' => 'Categoria',             'type' => 'text'],
        'UF'                => ['label' => 'UF',                    'type' => 'text'],
        'Cidade'            => ['label' => 'Cidade',                'type' => 'text'],
        'Bairro'            => ['label' => 'Bairro',                'type' => 'text'],
        'Endereco'          => ['label' => 'Endereço',              'type' => 'text'],
        'Numero'            => ['label' => 'Número',                'type' => 'text'],
        'Complemento'       => ['label' => 'Complemento',           'type' => 'text'],
        'CEP'               => ['label' => 'CEP',                   'type' => 'text'],
        'PrecoVenda'        => ['label' => 'Preço de Venda',        'type' => 'number'],
        'PrecoCondominio'   => ['label' => 'Preço do Condomínio',   'type' => 'number'],
        'ValorIPTU'         => ['label' => 'Valor do IPTU',         'type' => 'number'],
        'UnidadeMetrica'    => ['label' => 'Unidade de Medida',     'type' => 'text'],
        'AreaUtil'          => ['label' => 'Área Útil',             'type' => 'number'],
        'AreaTotal'         => ['label' => 'Área Total',            'type' => 'number'],
        'Metragem'          => ['label' => 'Metragem',              'type' => 'number'],
        'QtdDormitorios'    => ['label' => 'Quartos',               'type' => 'number'],
        'QtdSuites'         => ['label' => 'Suítes',                'type' => 'number'],
        'QtdBanheiros'      => ['label' => 'Banheiros',             'type' => 'number'],
        'QtdVagas'          => ['label' => 'Vagas',                 'type' => 'number'],
        'AnoConstrucao'     => ['label' => 'Ano de Construção',     'type' => 'number'],
        'latitude'          => ['label' => 'Latitude',              'type' => 'text'],
        'longitude'         => ['label' => 'Longitude',             'type' => 'text'],
    ];

    private static $skip_tags = [
        'Fotos', 'Foto', 'NomeArquivo', 'URLArquivo', 'Principal',
        'Alterada', 'Caracteristicas', 'Modelo', 'VisualizarMapa',
        'DivulgarEndereco',
    ];

    private static function get_temp_dir()
    {
        $dir = WP_CONTENT_DIR . '/uploads/upt_imob_temp';
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
        return $dir;
    }

    private static function get_state_transient($session_id)
    {
        $key = 'upt_imob_' . $session_id;
        $data = get_transient($key);
        if (!is_array($data)) {
            $data = [
                'imported'    => 0,
                'skipped'     => 0,
                'photos'      => 0,
                'error_count' => 0,
                'last_error'  => '',
                'status'      => 'idle',
            ];
        }
        return $data;
    }

    private static function set_state_transient($session_id, $data)
    {
        $key = 'upt_imob_' . $session_id;
        set_transient($key, $data, 12 * HOUR_IN_SECONDS);
    }

    private static function delete_state_transient($session_id)
    {
        delete_transient('upt_imob_' . $session_id);
    }

    private static function get_session_file_path($session_id)
    {
        return self::get_temp_dir() . '/' . $session_id . '.xml';
    }

    public static function prepare_upload($file_path, $schema_slug, $schema_label, $custom_fields_mode)
    {
        if (!class_exists('XMLReader')) {
            return new WP_Error('no_xmlreader', 'A extensão XMLReader do PHP não está disponível.');
        }

        $session_id = wp_generate_uuid4();
        $dest = self::get_session_file_path($session_id);

        if (!move_uploaded_file($file_path, $dest)) {
            if (!copy($file_path, $dest)) {
                return new WP_Error('upload_fail', 'Não foi possível salvar o arquivo XML.');
            }
        }

        if (!class_exists('UPT_Schema_Store')) {
            @unlink($dest);
            return new WP_Error('no_schema_store', 'UPT_Schema_Store indisponível.');
        }

        $term = get_term_by('slug', $schema_slug, 'catalog_schema');
        if (!$term || is_wp_error($term)) {
            $result = wp_insert_term($schema_label, 'catalog_schema', ['slug' => $schema_slug]);
            if (is_wp_error($result)) {
                @unlink($dest);
                return new WP_Error('schema_error', 'Erro ao criar esquema: ' . $result->get_error_message());
            }
        }

        self::ensure_schema_fields($schema_slug, $schema_label, $custom_fields_mode);

        self::set_state_transient($session_id, [
            'imported'    => 0,
            'skipped'     => 0,
            'photos'      => 0,
            'error_count' => 0,
            'last_error'  => '',
            'status'      => 'ready',
            'schema_slug' => $schema_slug,
            'total'       => 0,
        ]);

        return $session_id;
    }

    public static function ajax_count($session_id)
    {
        $file_path = self::get_session_file_path($session_id);
        if (!file_exists($file_path)) {
            return new WP_Error('file_missing', 'Arquivo não encontrado. Faça upload novamente.');
        }

        set_time_limit(120);
        ini_set('memory_limit', '256M');

        $count = 0;
        $reader = new XMLReader();
        if (!$reader->open($file_path)) {
            return new WP_Error('xml_open', 'Não foi possível abrir o XML.');
        }

        while (@$reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'Imovel') {
                $count++;
            }
        }
        $reader->close();

        $state = self::get_state_transient($session_id);
        $state['total'] = $count;
        $state['status'] = 'counted';
        self::set_state_transient($session_id, $state);

        return ['total' => $count];
    }

    public static function ajax_process_batch($session_id, $offset, $limit)
    {
        $file_path = self::get_session_file_path($session_id);
        if (!file_exists($file_path)) {
            return new WP_Error('file_missing', 'Arquivo não encontrado.');
        }

        set_time_limit(0);
        ini_set('memory_limit', '512M');
        ini_set('default_socket_timeout', 30);

        if (!function_exists('media_sideload_image')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $state = self::get_state_transient($session_id);
        $schema_slug = isset($state['schema_slug']) ? $state['schema_slug'] : '';
        if ($schema_slug === '') {
            return new WP_Error('no_schema', 'Sessão inválida.');
        }

        $schema_fields = UPT_Schema_Store::get_fields_for_schema($schema_slug);
        if (empty($schema_fields)) {
            $all_schemas = UPT_Schema_Store::get_schemas();
            if (isset($all_schemas[$schema_slug]['fields'])) {
                $schema_fields = $all_schemas[$schema_slug]['fields'];
            }
        }

        $gallery_field_id = $schema_slug . '_' . sanitize_title('Galeria de Fotos');

        $reader = new XMLReader();
        if (!$reader->open($file_path)) {
            return new WP_Error('xml_open', 'Não foi possível abrir o XML.');
        }

        $current_item = null;
        $current_tag = '';
        $in_fotos = false;
        $in_foto = false;
        $current_foto = null;

        $global_index = 0;
        $processed = 0;
        $batch_imported = 0;
        $batch_skipped = 0;
        $batch_photos = 0;
        $batch_errors = 0;
        $last_error = '';

        while (@$reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                $tag_name = $reader->name;

                if ($tag_name === 'Imovel') {
                    if ($current_item !== null && $global_index >= $offset && $processed < $limit) {
                        $result = self::process_item($current_item, $schema_slug, $schema_fields, $gallery_field_id);
                        if (is_wp_error($result)) {
                            $batch_errors++;
                            $last_error = $result->get_error_message();
                            $batch_skipped++;
                        } else {
                            $batch_imported++;
                            if (isset($result['_photos'])) {
                                $batch_photos += $result['_photos'];
                            }
                        }
                        $processed++;
                    }

                    $current_item = ['fotos' => [], 'fields' => []];
                    $current_tag = '';
                    $in_fotos = false;
                    $in_foto = false;
                    $current_foto = null;
                    $global_index++;
                    continue;
                }

                if ($current_item === null || $global_index - 1 < $offset) {
                    $current_tag = '';
                    continue;
                }

                if ($tag_name === 'Fotos') {
                    $in_fotos = true;
                    continue;
                }
                if ($in_fotos && $tag_name === 'Foto') {
                    $in_foto = true;
                    $current_foto = [];
                    continue;
                }
                if ($in_foto) {
                    $current_tag = $tag_name;
                    continue;
                }
                if ($tag_name === 'Observacao') {
                    $current_tag = $tag_name;
                    continue;
                }

                $current_tag = in_array($tag_name, self::$skip_tags, true) ? '' : $tag_name;
            }
            elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                $tag_name = $reader->name;

                if ($tag_name === 'Imovel') {
                    if ($current_item !== null && $global_index - 1 >= $offset && $processed < $limit) {
                        $result = self::process_item($current_item, $schema_slug, $schema_fields, $gallery_field_id);
                        if (is_wp_error($result)) {
                            $batch_errors++;
                            $last_error = $result->get_error_message();
                            $batch_skipped++;
                        } else {
                            $batch_imported++;
                            if (isset($result['_photos'])) {
                                $batch_photos += $result['_photos'];
                            }
                        }
                        $processed++;
                    }

                    $current_item = null;
                    $current_tag = '';
                    $in_fotos = false;
                    $in_foto = false;
                    $current_foto = null;

                    if ($processed >= $limit) {
                        break;
                    }
                    continue;
                }

                if ($tag_name === 'Fotos') {
                    $in_fotos = false;
                    continue;
                }
                if ($tag_name === 'Foto') {
                    if ($current_foto !== null && $current_item !== null && !empty($current_foto['url'])) {
                        $current_item['fotos'][] = $current_foto;
                    }
                    $in_foto = false;
                    $current_foto = null;
                    continue;
                }

                $current_tag = '';
            }
            elseif (($reader->nodeType === XMLReader::TEXT || $reader->nodeType === XMLReader::CDATA) && $current_item !== null) {
                $value = $reader->value;

                if ($in_foto && $current_foto !== null) {
                    if ($current_tag === 'URLArquivo') {
                        $current_foto['url'] = trim($value);
                    } elseif ($current_tag === 'Principal') {
                        $current_foto['principal'] = (trim($value) === '1');
                    } elseif ($current_tag === 'NomeArquivo') {
                        $current_foto['filename'] = trim($value);
                    }
                    continue;
                }

                if ($current_tag === 'Observacao') {
                    $current_item['observacao'] = isset($current_item['observacao']) ? $current_item['observacao'] . $value : $value;
                    continue;
                }

                if ($current_tag === 'TituloImovel') {
                    $current_item['title'] = trim($value);
                    continue;
                }

                if ($current_tag !== '' && !in_array($current_tag, self::$skip_tags, true)) {
                    $trimmed = trim($value);
                    if ($trimmed !== '' && $trimmed !== '0') {
                        $current_item['fields'][$current_tag] = $trimmed;
                    }
                }
            }
        }

        $reader->close();
        wp_cache_flush();

        $is_finished = ($processed < $limit) || ($global_index <= 0);

        $state['imported'] += $batch_imported;
        $state['skipped'] += $batch_skipped;
        $state['photos'] += $batch_photos;
        $state['error_count'] += $batch_errors;
        $state['last_error'] = $last_error;

        if ($is_finished) {
            $state['status'] = 'finished';
            @unlink($file_path);

            if (class_exists('UPT_Cache')) {
                UPT_Cache::purge_all('importacao_imobiliaria_batch');
            }
        } else {
            $state['status'] = 'processing';
        }

        self::set_state_transient($session_id, $state);

        return [
            'processed'  => $processed,
            'imported'   => $batch_imported,
            'skipped'    => $batch_skipped,
            'photos'     => $batch_photos,
            'errors'     => $batch_errors,
            'last_error' => $last_error,
            'next_offset'=> $offset + $processed,
            'is_finished'=> $is_finished,
            'total'      => $state['total'],
            'global_index' => $global_index,
        ];
    }

    public static function ajax_get_status($session_id)
    {
        return self::get_state_transient($session_id);
    }

    public static function ajax_cancel($session_id)
    {
        $file_path = self::get_session_file_path($session_id);
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
        self::delete_state_transient($session_id);
        return ['cancelled' => true];
    }

    public static function ensure_schema_fields($schema_slug, $schema_label, $custom_fields_mode)
    {
        if (!class_exists('UPT_Schema_Store')) {
            return;
        }

        $all_schemas = UPT_Schema_Store::get_schemas();

        if (!isset($all_schemas[$schema_slug]) || !is_array($all_schemas[$schema_slug])) {
            $all_schemas[$schema_slug] = [];
        }

        $all_schemas[$schema_slug]['label'] = $schema_label;
        $all_schemas[$schema_slug]['items_limit'] = 0;

        if ($custom_fields_mode !== 'existing' || empty($all_schemas[$schema_slug]['fields'])) {
            $new_fields = [];

            foreach (self::$fields_map as $tag => $def) {
                $new_fields[] = [
                    'id'       => $schema_slug . '_' . sanitize_title($def['label']),
                    'type'     => $def['type'],
                    'label'    => $def['label'],
                    'required' => false,
                ];
            }

            $new_fields[] = [
                'id'       => $schema_slug . '_' . sanitize_title('Observação'),
                'type'     => 'wysiwyg',
                'label'    => 'Observação',
                'required' => false,
            ];

            $new_fields[] = [
                'id'       => $schema_slug . '_' . sanitize_title('Galeria de Fotos'),
                'type'     => 'gallery',
                'label'    => 'Galeria de Fotos',
                'required' => false,
            ];

            $existing_ids = [];
            if (!empty($all_schemas[$schema_slug]['fields'])) {
                foreach ($all_schemas[$schema_slug]['fields'] as $ef) {
                    if (isset($ef['id'])) {
                        $existing_ids[$ef['id']] = true;
                    }
                }
            }

            foreach ($new_fields as $nf) {
                if (!isset($existing_ids[$nf['id']])) {
                    $all_schemas[$schema_slug]['fields'][] = $nf;
                }
            }
        }

        UPT_Schema_Store::save_schemas($all_schemas);
    }

    private static function process_item($item, $schema_slug, $schema_fields, $gallery_field_id)
    {
        $title = isset($item['title']) ? trim($item['title']) : '';
        if ($title === '') {
            return new WP_Error('empty_title', 'Item sem título.');
        }

        $observacao = isset($item['observacao']) ? trim($item['observacao']) : '';
        $observacao = wp_kses_post($observacao);

        $fields = isset($item['fields']) ? $item['fields'] : [];
        $fotos = isset($item['fotos']) ? $item['fotos'] : [];
        $codigo = isset($fields['CodigoImovel']) ? $fields['CodigoImovel'] : '';

        $existing_id = 0;
        if ($codigo !== '') {
            $existing_id = self::find_existing_item_by_codigo($codigo, $schema_slug);
        }

        $post_data = [
            'post_title'   => $title,
            'post_content' => $observacao,
            'post_status'  => 'publish',
            'post_type'    => 'catalog_item',
        ];

        if ($existing_id > 0) {
            $post_data['ID'] = $existing_id;
            $post_id = wp_update_post($post_data, true);
        } else {
            $post_id = wp_insert_post($post_data, true);
        }

        if (is_wp_error($post_id) || !$post_id) {
            return new WP_Error('post_error', 'Erro ao salvar: ' . (is_wp_error($post_id) ? $post_id->get_error_message() : 'desconhecido'));
        }

        wp_set_object_terms($post_id, $schema_slug, 'catalog_schema', false);

        $result = ['_photos' => 0];

        $has_gallery_field = false;
        foreach ($schema_fields as $sf) {
            if (isset($sf['id']) && $sf['id'] === $gallery_field_id) {
                $has_gallery_field = true;
                break;
            }
        }

        if ($has_gallery_field && !empty($fotos)) {
            $gallery_ids = self::download_fotos($fotos, $post_id);

            if (!empty($gallery_ids)) {
                update_post_meta($post_id, $gallery_field_id, implode(',', $gallery_ids));
                $result['_photos'] = count($gallery_ids);

                set_post_thumbnail($post_id, $gallery_ids[0]);
            }
        } elseif (!empty($fotos)) {
            $first_foto = null;
            foreach ($fotos as $foto) {
                if (!empty($foto['principal'])) {
                    $first_foto = $foto;
                    break;
                }
            }
            if (!$first_foto && !empty($fotos[0])) {
                $first_foto = $fotos[0];
            }

            if ($first_foto && !empty($first_foto['url'])) {
                $thumb_id = self::sideload_image($first_foto['url'], $post_id);
                if ($thumb_id) {
                    set_post_thumbnail($post_id, $thumb_id);
                    $result['_photos'] = 1;
                }
            }
        }

        foreach ($fields as $xml_tag => $value) {
            if (!isset(self::$fields_map[$xml_tag])) {
                continue;
            }

            $def = self::$fields_map[$xml_tag];
            $field_id = $schema_slug . '_' . sanitize_title($def['label']);

            $found = false;
            foreach ($schema_fields as $sf) {
                if (isset($sf['id']) && $sf['id'] === $field_id) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                continue;
            }

            if ($def['type'] === 'number') {
                $value = preg_replace('/[^0-9.,\-]/', '', $value);
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
                $value = floatval($value);
            } else {
                $value = sanitize_text_field($value);
            }

            update_post_meta($post_id, $field_id, $value);
        }

        if ($codigo !== '') {
            update_post_meta($post_id, '_upt_codigo_imovel', $codigo);
        }

        return $result;
    }

    private static function find_existing_item_by_codigo($codigo, $schema_slug)
    {
        $query = new WP_Query([
            'post_type'      => 'catalog_item',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'meta_key'       => '_upt_codigo_imovel',
            'meta_value'     => $codigo,
            'tax_query'      => [
                [
                    'taxonomy' => 'catalog_schema',
                    'field'    => 'slug',
                    'terms'    => $schema_slug,
                ],
            ],
        ]);

        if ($query->have_posts()) {
            return $query->posts[0]->ID;
        }

        return 0;
    }

    private static function download_fotos($fotos, $post_id)
    {
        $ids = [];
        $max_photos = 50;
        $count = 0;

        foreach ($fotos as $foto) {
            if ($count >= $max_photos) {
                break;
            }

            $url = isset($foto['url']) ? trim($foto['url']) : '';
            if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $attachment_id = self::sideload_image($url, $post_id);
            if ($attachment_id) {
                $ids[] = $attachment_id;
                $count++;
            }
        }

        return $ids;
    }

    private static function sideload_image($url, $post_id)
    {
        $parsed = wp_parse_url($url);
        $host = isset($parsed['host']) ? $parsed['host'] : '';
        if ($host && !in_array($host, ['localhost', '127.0.0.1', '::1'], true) && filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return 0;
        }

        if (!function_exists('media_sideload_image')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $filename = isset($parsed['path']) ? basename($parsed['path']) : 'imagem.jpg';
        $filename = sanitize_file_name($filename);

        $tmp = download_url($url, 30);

        if (is_wp_error($tmp)) {
            return 0;
        }

        $file_array = [
            'name'     => $filename,
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload($file_array, $post_id, null);

        if (is_wp_error($attachment_id)) {
            if (file_exists($tmp)) {
                @unlink($tmp);
            }
            return 0;
        }

        return (int)$attachment_id;
    }
}
