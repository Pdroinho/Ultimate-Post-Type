<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UPT_Custom_Field_Tag extends \Elementor\Core\DynamicTags\Data_Tag {

    /**
     * Sufixos internos para expor variações "inteligentes" do campo de taxonomia.
     * - __upt_parent: retorna apenas o termo pai (Fornecedor)
     * - __upt_child: retorna apenas o termo filho (Sub Categoria)
     */
    private const TAX_PARENT_SUFFIX = '__upt_parent';
    private const TAX_CHILD_SUFFIX  = '__upt_child';

    public function get_name() { return 'upt-meta-field'; }
    public function get_title() { return 'Campo do Catálogo'; }
    public function get_group() { return 'upt'; }
    public function get_categories() {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::POST_META_CATEGORY,
        ];
    }

    protected function _register_controls() {
        $this->add_control(
            'field_key',
            [
                'label' => 'Campo',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_all_custom_fields_options(),
            ]
        );
    }

    public function get_value(array $options = []) {
        $field_id = $this->get_settings('field_key');
        if (empty($field_id)) {
            return '';
        }

        // Normaliza possíveis sufixos especiais (pai/filho) para buscar a definição do campo original.
        $requested_variant = null;
        $base_field_id = $field_id;
        if (substr($field_id, -strlen(self::TAX_PARENT_SUFFIX)) === self::TAX_PARENT_SUFFIX) {
            $requested_variant = 'parent';
            $base_field_id = substr($field_id, 0, -strlen(self::TAX_PARENT_SUFFIX));
        } elseif (substr($field_id, -strlen(self::TAX_CHILD_SUFFIX)) === self::TAX_CHILD_SUFFIX) {
            $requested_variant = 'child';
            $base_field_id = substr($field_id, 0, -strlen(self::TAX_CHILD_SUFFIX));
        }

        // --- INÍCIO DA MODIFICAÇÃO PARA PRÉ-VISUALIZAÇÃO ---
        // Aplica lógica de exemplo APENAS para o campo de seleção múltipla no editor
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            $schema_slug = explode('_', $base_field_id)[0];
            $field_def = $this->get_field_definition($schema_slug, $base_field_id);

            if ($field_def && $field_def['type'] === 'select' && !empty($field_def['multiple'])) {
                // Para os processos internos do Elementor, retorna um ARRAY de exemplo para evitar o erro fatal
                return ['Exemplo A', 'Exemplo B', 'Exemplo C'];
            }
            if ($field_def && $field_def['type'] === 'list') {
                return ['Item A', 'Item B', 'Item C'];
            }
            if ($field_def && $field_def['type'] === 'unit_measure') {
                return '85,50 m²';
            }
        }
        // --- FIM DA MODIFICAÇÃO ---

        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }

        $schema_slug = explode('_', $base_field_id)[0];
        $field_def = $this->get_field_definition($schema_slug, $base_field_id);
        $value = get_post_meta($post_id, $base_field_id, true);

        if ($field_def) {
            $field_type = $field_def['type'];

            if ($field_type === 'select' && !empty($field_def['multiple'])) {
                return json_encode((array)$value);
            }

            if ($field_type === 'list') {
                $list = is_array($value) ? $value : preg_split('/\r\n|\r|\n/', (string)$value);
                $list = array_map('trim', $list);
                $list = array_values(array_filter($list, function($v){ return $v !== ''; }));
                return json_encode($list);
            }

            if ($field_type === 'image' || $field_type === 'core_featured_image') {
                $image_id = ($field_type === 'core_featured_image') ? get_post_thumbnail_id($post_id) : $value;
                if (empty($image_id)) return '';
                return [
                    'id' => $image_id,
                    'url' => wp_get_attachment_url($image_id),
                ];
            }
        }
        
        return $this->get_formatted_value_for_render($field_def, $base_field_id, $post_id, $requested_variant);
    }

    public function render() {
        $field_id = $this->get_settings('field_key');
        if (empty($field_id)) {
            return;
        }

        $requested_variant = null;
        $base_field_id = $field_id;
        if (substr($field_id, -strlen(self::TAX_PARENT_SUFFIX)) === self::TAX_PARENT_SUFFIX) {
            $requested_variant = 'parent';
            $base_field_id = substr($field_id, 0, -strlen(self::TAX_PARENT_SUFFIX));
        } elseif (substr($field_id, -strlen(self::TAX_CHILD_SUFFIX)) === self::TAX_CHILD_SUFFIX) {
            $requested_variant = 'child';
            $base_field_id = substr($field_id, 0, -strlen(self::TAX_CHILD_SUFFIX));
        }

        // --- INÍCIO DA MODIFICAÇÃO PARA RENDERIZAÇÃO NO EDITOR ---
        // Aplica lógica de exemplo APENAS para o campo de seleção múltipla no editor
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            $post_id = get_the_ID();
            // Se não houver post de preview, não exibe nada para campos normais
            if ( ! $post_id ) {
                echo '';
                return;
            }
            $schema_slug = explode('_', $base_field_id)[0];
            $field_def = $this->get_field_definition($schema_slug, $base_field_id);

            if ($field_def && $field_def['type'] === 'select' && !empty($field_def['multiple'])) {
                 // Para o "Array to List" no editor, retornamos um JSON de exemplo
                 echo json_encode(['Exemplo A', 'Exemplo B', 'Exemplo C']);
                 return; // Sai da função aqui para este caso específico
            }
            if ($field_def && $field_def['type'] === 'list') {
                echo json_encode(['Item A', 'Item B', 'Item C']);
                return;
            }
            if ($field_def && $field_def['type'] === 'unit_measure') {
                echo '85,50 m²';
                return;
            }
        }
        // --- FIM DA MODIFICAÇÃO ---

        $post_id = get_the_ID();
        if (!$post_id) {
            return;
        }
        
        $schema_slug = explode('_', $base_field_id)[0];
        $field_def = $this->get_field_definition($schema_slug, $base_field_id);
        $value = $this->get_formatted_value_for_render($field_def, $base_field_id, $post_id, $requested_variant);

        echo wp_kses_post($value);
    }
    
    private function get_formatted_value_for_render($field_def, $key, $post_id, $requested_variant = null) {
        $value = get_post_meta($post_id, $key, true);

        if (!$field_def) {
            return is_array($value) ? implode(', ', $value) : $value;
        }

        switch ($field_def['type']) {
            case 'core_title':
                return get_the_title($post_id);
            case 'core_content':
                return apply_filters('the_content', get_the_content(null, false, $post_id));
            case 'core_featured_image':
                return get_the_post_thumbnail_url($post_id);
            case 'image':
                return wp_get_attachment_url($value);
            case 'video':
                return wp_get_attachment_url($value);
            case 'pdf':
                return wp_get_attachment_url($value);
            case 'taxonomy':
                $terms = get_the_terms($post_id, 'catalog_category');
                if (!$terms || is_wp_error($terms)) return '';

                // Helper: detect if a term has children (cached).
                $has_children = function($term_id) {
                    static $cache = [];
                    $term_id = (int)$term_id;
                    if (isset($cache[$term_id])) return $cache[$term_id];
                    $kids = get_terms([
                        'taxonomy'   => 'catalog_category',
                        'hide_empty' => false,
                        'parent'     => $term_id,
                        'number'     => 1,
                        'fields'     => 'ids',
                    ]);
                    $cache[$term_id] = (!is_wp_error($kids) && !empty($kids));
                    return $cache[$term_id];
                };

                // Index assigned terms by ID.
                $by_id = [];
                foreach ($terms as $t) {
                    if (isset($t->term_id)) {
                        $by_id[(int)$t->term_id] = $t;
                    }
                }

                // 1) Prefer strict hierarchy detection using parent/child relationship within the assigned terms.
                $cats = [];
                $subcats = [];
                foreach ($terms as $t) {
                    $tid = isset($t->term_id) ? (int)$t->term_id : 0;
                    $pid = isset($t->parent) ? (int)$t->parent : 0;
                    if ($tid && $pid && isset($by_id[$pid])) {
                        $subcats[$tid] = $t;
                        $cats[$pid] = $by_id[$pid];
                    }
                }

                // If we detected categories, prefer non-top-level ones (avoid schema-level terms when present).
                if (!empty($cats)) {
                    $cats_non_top = array_filter($cats, function($t){
                        return isset($t->parent) && (int)$t->parent !== 0;
                    });
                    if (!empty($cats_non_top)) {
                        $cats = $cats_non_top;
                    }
                }

                // 2) Fallback: infer categories and subcategories by whether the term has children.
                if (empty($cats)) {
                    foreach ($terms as $t) {
                        $tid = isset($t->term_id) ? (int)$t->term_id : 0;
                        $pid = isset($t->parent) ? (int)$t->parent : 0;
                        // Categories (Fornecedor) usually have children and are not top-level (they sit under a schema).
                        if ($tid && $pid !== 0 && $has_children($tid)) {
                            $cats[$tid] = $t;
                        }
                    }
                }
                if (empty($subcats)) {
                    foreach ($terms as $t) {
                        $tid = isset($t->term_id) ? (int)$t->term_id : 0;
                        $pid = isset($t->parent) ? (int)$t->parent : 0;
                        // Subcategories are usually leaf terms (no children) and not top-level.
                        if ($tid && $pid !== 0 && !$has_children($tid) && !isset($cats[$tid])) {
                            $subcats[$tid] = $t;
                        }
                    }
                }

                // 3) Final fallback: if categories are still empty but we have subcategories, derive category from subcategory parents.
                if (empty($cats) && !empty($subcats)) {
                    foreach ($subcats as $t) {
                        $pid = isset($t->parent) ? (int)$t->parent : 0;
                        if ($pid) {
                            $pt = get_term($pid, 'catalog_category');
                            if ($pt && !is_wp_error($pt)) {
                                $cats[(int)$pt->term_id] = $pt;
                            }
                        }
                    }
                }

                // Default behavior for "Fornecedor" field: base option returns Categoria (Fornecedor).
                if ($requested_variant === null) {
                    $label = (string)($field_def['label'] ?? '');
                    $label_norm = function_exists('mb_strtolower') ? mb_strtolower($label, 'UTF-8') : strtolower($label);
                    if (strpos($label_norm, 'fornecedor') !== false) {
                        return !empty($cats) ? implode(', ', wp_list_pluck($cats, 'name')) : '';
                    }
                }

                // Variants (legacy/explicit).
                if ($requested_variant === 'parent') {
                    return !empty($cats) ? implode(', ', wp_list_pluck($cats, 'name')) : '';
                }
                if ($requested_variant === 'child') {
                    return !empty($subcats) ? implode(', ', wp_list_pluck($subcats, 'name')) : '';
                }

                // Default: all assigned terms
                return implode(', ', wp_list_pluck($terms, 'name'));
            case 'price':
                return is_numeric($value) ? 'R$ ' . number_format_i18n((float)$value, 2) : '';
            case 'unit_measure':
                $unit = get_post_meta($post_id, $key . '_unit', true);
                if (is_numeric($value) && !empty($unit)) {
                    return number_format_i18n((float)$value, 2) . ' ' . esc_html($unit);
                }
                if (is_numeric($value)) {
                    return number_format_i18n((float)$value, 2);
                }
                if (!empty($unit)) {
                    return esc_html($unit);
                }
                return $value;
            case 'date':
                if (!empty($value)) {
                    try {
                        $date_obj = new DateTime($value);
                        return esc_html($date_obj->format('d/m/Y'));
                    } catch (Exception $e) {
                        return esc_html($value);
                    }
                }
                return '';
            case 'time':
                if (!empty($value)) {
                     try {
                        $date_obj = new DateTime($value);
                        $format = isset($field_def['time_format']) && $field_def['time_format'] === '12h' ? 'g:i A' : 'H:i';
                        return esc_html($date_obj->format($format));
                    } catch (Exception $e) {
                        return esc_html($value);
                    }
                }
                return '';
            case 'select':
                if (!empty($field_def['multiple'])) {
                    return json_encode(is_array($value) ? $value : []);
                }
                return is_array($value) ? implode(', ', $value) : $value;
            case 'list':
                $list = is_array($value) ? $value : preg_split('/\r\n|\r|\n/', (string)$value);
                $list = array_map('trim', $list);
                $list = array_values(array_filter($list, function($v){ return $v !== ''; }));
                return implode(', ', $list);
            default:
                $raw_value = is_array($value) ? implode(', ', $value) : $value;
                if (is_numeric($raw_value)) {
                    $label = isset($field_def['label']) ? $field_def['label'] : '';
                    $label_lower = function_exists('mb_strtolower') ? mb_strtolower($label, 'UTF-8') : strtolower($label);
                    $id_lower = strtolower($base_field_id);
                    if (
                        strpos($id_lower, 'preco') !== false ||
                        strpos($id_lower, 'preço') !== false ||
                        strpos($label_lower, 'preco') !== false ||
                        strpos($label_lower, 'preço') !== false ||
                        strpos($label_lower, 'valor') !== false ||
                        (isset($field_def['type']) && $field_def['type'] === 'price')
                    ) {
                        $val = (float)$raw_value;
                        if ($val <= 0) return '';
                        if ($val >= 1000000) {
                            return 'R$ ' . number_format($val, 2, ',', '.');
                        }
                        return 'R$ ' . number_format_i18n($val, 2);
                    }
                }
                return $raw_value;
        }
    }

    private function get_field_definition($schema_slug, $field_id) {
        $schemas = UPT_Schema_Store::get_schemas();
        if (isset($schemas[$schema_slug]['fields']) && is_array($schemas[$schema_slug]['fields'])) {
            foreach ($schemas[$schema_slug]['fields'] as $field) {
                if ($field['id'] === $field_id) {
                    return $field;
                }
            }
        }
        if (in_array($field_id, ['core_title', 'core_content', 'core_featured_image'])) {
            return ['type' => $field_id];
        }
        return null;
    }

    private function get_all_custom_fields_options() {
        $options = ['' => '— Selecione —'];
        $schemas = UPT_Schema_Store::get_schemas();
        if (empty($schemas)) return $options;

        foreach ($schemas as $slug => $data) {
            if (isset($data['fields']) && is_array($data['fields'])) {
                $term = get_term_by('slug', $slug, 'catalog_schema');
                $name = $term ? $term->name : $slug;
                foreach ($data['fields'] as $field) {
                    // Para campos de taxonomia usados como "Fornecedor", expõe 2 opções separadas:
                    // - Categoria (pai)
                    // - Sub Categoria (filho)
                    // E evita duplicar "Fornecedor" no seletor de tags dinâmicas.
                    if (!empty($field['type']) && $field['type'] === 'taxonomy') {
                        $label = (string)($field['label'] ?? '');
                        $label_norm = function_exists('mb_strtolower') ? mb_strtolower($label, 'UTF-8') : strtolower($label);
                        if (strpos($label_norm, 'fornecedor') !== false) {
                            // Base (sem sufixo): Categoria (pai)
                            $options[$field['id']] = $name . ' - Categoria';
                            // Variante: Sub Categoria (filho)
                            $options[$field['id'] . self::TAX_CHILD_SUFFIX]  = $name . ' - Sub Categoria';
                            continue;
                        }
                    }

                    $options[$field['id']] = $name . ' - ' . $field['label'];
                }
            }
        }
        return $options;
    }
}
