<div class="upt-submit-form">
    <a href="#" id="upt-modal-close" class="upt-modal-close-button">&times;</a>
    <?php
    $current_user = wp_get_current_user();
    $schema_slug = '';
    
    $is_editing = get_query_var('is_editing', false);
    $item_id = get_query_var('item_id', 0);
    $schema_slug_from_ajax = get_query_var('schema_slug', '');
    $post_to_edit = null;

    if ($is_editing) {
        $post_to_edit = get_post($item_id);
        if (!$post_to_edit || $post_to_edit->post_type !== 'catalog_item' || ($post_to_edit->post_author != get_current_user_id() && !current_user_can('manage_options'))) {
            wp_die('Você não tem permissão para editar este item.');
        }
        $terms = get_the_terms($post_to_edit, 'catalog_schema');
        if ($terms && !is_wp_error($terms)) {
            $schema_slug = $terms[0]->slug;
        }
    } else {
        $schema_slug = $schema_slug_from_ajax;
    }
    ?>

    <h2>Olá, <?php echo esc_html($current_user->display_name); ?>!</h2>
    
    <?php
    if (empty($schema_slug)) :
        $schemas = get_terms(['taxonomy' => 'catalog_schema', 'hide_empty' => false]);
        
        if (count($schemas) === 1) {
            $schema_slug = $schemas[0]->slug;
        } else {
    ?>
        <h3 class="upt-modal-title">O que você deseja cadastrar?</h3>
        <form id="upt-schema-selector-form">
            <select name="schema" id="schema-selector-dropdown">
                <?php foreach ($schemas as $schema) : ?>
                    <option value="<?php echo esc_attr($schema->slug); ?>"><?php echo esc_html($schema->name); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" value="Continuar" class="button button-primary">
        </form>
        <?php
        }
    endif;
    
    if (!empty($schema_slug)):
        $fields = UPT_Schema_Store::get_fields_for_schema($schema_slug);
        $schema_term = get_term_by('slug', $schema_slug, 'catalog_schema');

        if ( ! function_exists( 'upt_title_with_extension' ) ) {
            function upt_title_with_extension( $title, $attachment_id ) {
                $title = is_string( $title ) ? $title : '';
                $attachment_id = absint( $attachment_id );

                $file_path = $attachment_id ? get_attached_file( $attachment_id ) : '';
                $url_path  = '';

                if ( ! $file_path && $attachment_id ) {
                    $url = wp_get_attachment_url( $attachment_id );
                    if ( $url ) {
                        $url_path = parse_url( $url, PHP_URL_PATH );
                    }
                }

                $path_to_use = $file_path ? $file_path : $url_path;
                $ext         = $path_to_use ? pathinfo( $path_to_use, PATHINFO_EXTENSION ) : '';
                $filename    = $path_to_use ? pathinfo( $path_to_use, PATHINFO_BASENAME ) : '';

                if ( ! $title && $filename ) {
                    return $filename;
                }

                if ( $ext ) {
                    $ext = strtolower( $ext );
                    $suffix = '.' . $ext;
                    $title_lower = strtolower( $title );
                    if ( substr( $title_lower, -strlen( $suffix ) ) !== $suffix ) {
                        return $title . $suffix;
                    }
                }

                return $title;
            }
        }
    ?>
        <h3 class="upt-modal-title"><?php echo $is_editing ? 'Editando o item:' : 'Cadastrar um novo item:'; ?> <?php echo esc_html($schema_term->name); ?></h3>
        
        <form id="upt-new-item-form" method="post" action="" enctype="multipart/form-data">
            <?php foreach ($fields as $field) :
                $req_attr = $field['required'] ? 'required' : '';
                $req_label = $field['required'] ? '*' : '';
                $field_id_attr = esc_attr($field['id']);
                $field_name = esc_attr($field['id']);
                $value = $is_editing ? get_post_meta($item_id, $field_id_attr, true) : '';
            ?>
                <div class="upt-field-row">
                    <label for="<?php echo $field_id_attr; ?>"><?php echo esc_html($field['label']) . $req_label; ?></label>
                    <?php 
                    switch ($field['type']) {
                        case 'core_title':
                            $title_value = $is_editing ? $post_to_edit->post_title : '';
                            $max_length = isset($field['max_length']) ? absint($field['max_length']) : 0;
                            $maxlength_attr = $max_length > 0 ? ' maxlength="' . $max_length . '"' : '';
                            echo '<input type="text" name="item_title" id="item_title" value="' . esc_attr($title_value) . '" ' . $req_attr . $maxlength_attr . '>';
                            break;
                        case 'core_content':
                            $content_value = $is_editing ? $post_to_edit->post_content : '';
                            $max_length = isset($field['max_length']) ? absint($field['max_length']) : 0;
                            $rows = isset($field['rows']) && absint($field['rows']) > 0 ? absint($field['rows']) : 5;
                            $maxlength_attr = $max_length > 0 ? ' maxlength="' . $max_length . '"' : '';
                            echo '<textarea name="item_content" id="item_content" rows="' . $rows . '" ' . $req_attr . $maxlength_attr . '>' . esc_textarea($content_value) . '</textarea>';
                            break;
case 'blog_post':
                            $content_value = $is_editing ? $post_to_edit->post_content : '';
                            $excerpt_value = $is_editing ? $post_to_edit->post_excerpt : '';

                            $excerpt_is_required = ! empty( $field['excerpt_required'] );
                            $excerpt_req_attr = $excerpt_is_required ? 'required' : '';
                            $excerpt_req_label = $excerpt_is_required ? '*' : '';
                            $excerpt_max_length = isset( $field['excerpt_max_length'] ) ? absint( $field['excerpt_max_length'] ) : 0;
                            $excerpt_maxlength_attr = $excerpt_max_length > 0 ? ' maxlength="' . $excerpt_max_length . '"' : '';

                            echo '<textarea name="' . $field_name . '_content" id="' . $field_id_attr . '_content" class="upt-wysiwyg-textarea" rows="12" ' . $req_attr . '>' . esc_textarea($content_value) . '</textarea>';
                            
                            // Campo de Resumo (Excerpt)
                            echo '<div style="margin-top: 20px;">';
                            echo '<label for="' . $field_id_attr . '_excerpt" style="font-size: 13px; margin-bottom: 5px; display:block;">Resumo do Post' . $excerpt_req_label . ( $excerpt_is_required ? '' : ' (Opcional)' ) . '</label>';
                            echo '<textarea name="' . $field_name . '_excerpt" id="' . $field_id_attr . '_excerpt" rows="3" ' . $excerpt_req_attr . $excerpt_maxlength_attr . '>' . esc_textarea($excerpt_value) . '</textarea>';
                            echo '</div>';
                            break;
                        case 'core_featured_image':
                            $featured_image_id    = $is_editing ? get_post_thumbnail_id( $item_id ) : 0;
                            $featured_image_title = '';

                            if ( $featured_image_id ) {
                                $featured_image_post = get_post( $featured_image_id );
                                if ( $featured_image_post && ! is_wp_error( $featured_image_post ) ) {
                                    $featured_image_title = $featured_image_post->post_title;
                                }
                            }

                            $featured_image_title = upt_title_with_extension( $featured_image_title, $featured_image_id );

                            echo '<div class="upt-image-upload-wrapper">';
                                echo '<div class="image-preview-wrapper" id="preview-wrapper-' . $field_id_attr . '">';
                                    if ( $featured_image_id ) {
                                        echo get_the_post_thumbnail( $item_id, 'thumbnail' );
                                        if ( $featured_image_title ) {
                                            echo '<div class="upt-media-title" title="' . esc_attr( $featured_image_title ) . '">';
                                                echo esc_html( $featured_image_title );
                                            echo '</div>';
                                        }
                                    }
                                echo '</div>';

                                echo '<div class="image-buttons">';
                                    echo '<a href="#" class="button upt-add-image">Escolher Imagem</a>';
                                    echo '<a href="#" class="button-link-delete upt-remove-image' . ( ! $featured_image_id ? ' hidden' : '' ) . '">Remover</a>';
                                echo '</div>';

                                echo '<input type="hidden" name="' . esc_attr( $field_name ) . '" id="' . $field_id_attr . '" class="upt-image-id-input" value="' . esc_attr( $featured_image_id ) . '" ' . $req_attr . '>';
                            echo '<input type="hidden" name="featured_image_id" value="' . esc_attr( $featured_image_id ) . '">';
                            echo '</div>';
                            break;
                        case 'select':
                            if (isset($field['options'])) {
                                if (is_array($field['options'])) {
                                    $opt_strings = [];
                                    foreach ($field['options'] as $opt) {
                                        if (is_array($opt)) {
                                            $opt_strings[] = isset($opt['label']) ? $opt['label'] : (isset($opt['value']) ? $opt['value'] : '');
                                        } else {
                                            $opt_strings[] = (string)$opt;
                                        }
                                    }
                                    $options = $opt_strings;
                                } else {
                                    $options = explode('|', $field['options']);
                                }
                                $is_multiple = !empty($field['multiple']);
                                $allow_new = !empty($field['allow_new']);
                                $allow_rename_option = !empty($field['allow_rename_option']);
                                $allow_delete_option = !empty($field['allow_delete_option']);

                                if ($is_multiple) {
                                    echo '<div class="upt-taxonomy-checklist" id="' . $field_id_attr . '">';
                                    $saved_values = is_array($value) ? $value : [];
                                    foreach ($options as $option) {
                                        $option = trim($option);
                                        if (empty($option)) continue;
                                        $checked = in_array($option, $saved_values) ? 'checked' : '';
                                        $checkbox_id = 'select-checkbox-' . esc_attr($field_id_attr) . '-' . sanitize_title($option);
                                        echo '<input type="checkbox" class="upt-cat-checkbox" name="' . esc_attr($field_name) . '[]" id="' . $checkbox_id . '" value="' . esc_attr($option) . '" ' . $checked . '>';
                                        echo '<label for="' . $checkbox_id . '"><span class="pill-checkbox-icon"></span><span class="pill-text">' . esc_html($option) . '</span>';
                                        if ($allow_new) {
                                            echo ' <a href="#" class="remove-pill remove-option-pill" title="Excluir Opção" data-option-value="' . esc_attr($option) . '" data-field-id="' . esc_attr($field_id_attr) . '" data-schema-slug="' . esc_attr($schema_slug) . '"><span class="pill-delete-icon"></span></a>';
                                        }
                                        echo '</label>';
                                    }
                                    echo '</div>';
                                } else {
                                    echo '<select name="' . esc_attr($field_name) . '" id="' . $field_id_attr . '" ' . $req_attr . '>';
                                    echo '<option value="">— Selecione —</option>';
                                    foreach ($options as $option) {
                                        $option = trim($option);
                                        if (empty($option)) continue;
                                        echo '<option value="' . esc_attr($option) . '" ' . selected($value, $option, false) . '>' . esc_html($option) . '</option>';
                                    }
                                    echo '</select>';
                                }
                                
                                if ($allow_new) {
                                    echo '<div class="taxonomy-actions-wrapper" style="margin-top: 8px;">';
                                    echo ' <a href="#" class="add-new-select-option" data-field-id="' . $field_id_attr . '">Adicionar</a>';
                                    if ( $allow_rename_option ) {
                                        echo ' <a href="#" class="rename-select-option" data-field-id="' . $field_id_attr . '" data-schema-slug="' . esc_attr($schema_slug) . '">Renomear</a>';
                                    }
                                    if ( $allow_delete_option ) {
                                        echo ' <a href="#" class="delete-select-option" data-field-id="' . $field_id_attr . '" data-schema-slug="' . esc_attr($schema_slug) . '">Excluir</a>';
                                    }
                                    echo '</div>';

                                    // Adicionar
                                    echo '<div id="new-option-area-' . $field_id_attr . '" class="upt-new-item-area" style="display:none;">';
                                    echo '<input type="text" id="new-option-name-' . $field_id_attr . '" placeholder="Nome da nova opção">';
                                    echo '<button type="button" class="save-new-select-option button button-small" data-field-id="' . $field_id_attr . '" data-schema-slug="' . esc_attr($schema_slug) . '">Salvar Opção</button>';
                                    echo '<a href="#" class="cancel-new-select-option cancel-new-item-link" data-field-id="' . $field_id_attr . '">Cancelar</a>';
                                    echo '<div id="new-option-status-' . $field_id_attr . '" class="new-item-status"></div>';
                                    echo '</div>';

                                    // Renomear
                                    if ( $allow_rename_option ) {
                                        echo '<div id="rename-option-area-' . $field_id_attr . '" class="upt-new-item-area" style="display:none;">';
                                        echo '<label class="upt-inline-label" for="rename-option-name-' . $field_id_attr . '">Item de seleção</label>';
                                        echo '<input type="hidden" id="rename-option-old-' . $field_id_attr . '" value="">';
                                        echo '<input type="text" id="rename-option-name-' . $field_id_attr . '" class="upt-white-field" placeholder="Nome do item">';
                                        echo '<button type="button" class="rename-select-option-save button button-small" data-field-id="' . $field_id_attr . '" data-schema-slug="' . esc_attr($schema_slug) . '">Salvar</button>';
                                        echo '<a href="#" class="rename-select-option-cancel cancel-new-item-link" data-field-id="' . $field_id_attr . '">Cancelar</a>';
                                        echo '<div id="rename-option-status-' . $field_id_attr . '" class="new-item-status"></div>';
                                        echo '</div>';
                                    }
                                }
                            }
                            break;
                        case 'relationship':
                            $schema_to_filter = isset($field['schema_filter']) ? $field['schema_filter'] : 'all';
                            $query_args = [
                                'post_type' => 'catalog_item',
                                'posts_per_page' => -1,
                                'post__not_in' => ($is_editing ? [$item_id] : []),
                                'orderby' => 'title',
                                'order' => 'ASC',
                            ];

                            if ($schema_to_filter !== 'all') {
                                $query_args['tax_query'] = [
                                    [
                                        'taxonomy' => 'catalog_schema',
                                        'field'    => 'slug',
                                        'terms'    => $schema_to_filter,
                                    ],
                                ];
                            }

                            $related_posts_query = new WP_Query($query_args);
                            echo '<select name="' . $field_name . '" id="' . $field_id_attr . '" ' . $req_attr . '>';
                            echo '<option value="">— Selecione um item —</option>';
                            if ($related_posts_query->have_posts()) {
                                while($related_posts_query->have_posts()) {
                                    $related_posts_query->the_post();
                                    echo '<option value="' . get_the_ID() . '" ' . selected($value, get_the_ID(), false) . '>' . get_the_title() . '</option>';
                                }
                            }
                            wp_reset_postdata();
                            echo '</select>';
                            break;
                        case 'textarea':
                            $max_length = isset($field['max_length']) ? absint($field['max_length']) : 0;
                            $rows = isset($field['rows']) && absint($field['rows']) > 0 ? absint($field['rows']) : 3;
                            $maxlength_attr = $max_length > 0 ? ' maxlength="' . $max_length . '"' : '';
                            echo '<textarea name="' . $field_name . '" id="' . $field_id_attr . '" rows="' . $rows . '" ' . $req_attr . $maxlength_attr . '>' . esc_textarea($value) . '</textarea>';
                            break;
                        case 'list':
                            $max_length = isset($field['max_length']) ? absint($field['max_length']) : 0;
                            $rows = isset($field['rows']) && absint($field['rows']) > 0 ? absint($field['rows']) : 4;
                            $maxlength_attr = $max_length > 0 ? ' maxlength="' . $max_length . '"' : '';
                            if (is_array($value)) {
                                $value = implode("\n", $value);
                            }
                            echo '<textarea name="' . $field_name . '" id="' . $field_id_attr . '" rows="' . $rows . '" placeholder="Um item por linha" ' . $req_attr . $maxlength_attr . '>' . esc_textarea($value) . '</textarea>';
                            break;
case 'wysiwyg':
                            $rows = isset($field['rows']) && absint($field['rows']) > 0 ? absint($field['rows']) : 10;
                            echo '<textarea name="' . $field_name . '" id="' . $field_id_attr . '" class="upt-wysiwyg-textarea" rows="' . $rows . '" ' . $req_attr . '>' . esc_textarea($value) . '</textarea>';
                            break;
                        

case 'url':
    $max_length = isset($field['max_length']) ? absint($field['max_length']) : 0;
    $maxlength_attr = $max_length > 0 ? ' maxlength="' . $max_length . '"' : '';
    // Campo de URL: usa type="url" para validação nativa + pattern mínimo (domínio com ponto)
    $pattern_attr = ' pattern="https?://.+\..+"';
    echo '<input type="url" name="' . $field_name . '" id="' . $field_id_attr . '" class="upt-url-input" inputmode="url" value="' . esc_attr($value) . '" ' . $req_attr . $maxlength_attr . $pattern_attr . '>';
    break;

                        case 'number':
                        case 'price':
                            $input_type = 'text';
                            $extra_class = ($field['type'] === 'price') ? 'upt-price-input' : 'upt-number-input';
                            $extra_attrs = ($field['type'] === 'price')
                                ? ' inputmode="decimal" pattern="[0-9.,]*"'
                                : ' inputmode="numeric" pattern="[0-9]*"';
                            echo '<input type="' . $input_type . '" name="' . $field_name . '" id="' . $field_id_attr . '" class="' . esc_attr($extra_class) . '" value="' . esc_attr($value) . '" ' . $req_attr . $extra_attrs . '>';
                            break;

                        case 'unit_measure':
                            if (isset($field['unit_options'])) {
                                $unit_options = explode('|', $field['unit_options']);
                                echo '<div class="upt-unit-measure-wrapper">';
                                echo '<input type="text" name="' . $field_name . '" id="' . $field_id_attr . '" class="upt-number-input" value="' . esc_attr($value) . '" inputmode="numeric" pattern="[0-9]*" ' . $req_attr . ' style="width: 60%; display: inline-block;">';
                                echo '<select name="' . $field_name . '_unit" id="' . $field_id_attr . '_unit" class="upt-unit-select" style="width: 38%; display: inline-block;">';
                                echo '<option value="">Selecione</option>';
                                foreach ($unit_options as $option) {
                                    $option = trim($option);
                                    if (empty($option)) continue;
                                    $unit_value = get_post_meta($is_editing ? $item_id : 0, $field_name . '_unit', true);
                                    echo '<option value="' . esc_attr($option) . '" ' . selected($unit_value, $option, false) . '>' . esc_html($option) . '</option>';
                                }
                                echo '</select>';
                                echo '</div>';
                            }
                            break;

                        case 'date': echo '<input type="date" name="' . $field_name . '" id="' . $field_id_attr . '" value="' . esc_attr($value) . '" ' . $req_attr . '>'; break;
                        case 'time': echo '<input type="time" name="' . $field_name . '" id="' . $field_id_attr . '" value="' . esc_attr($value) . '" ' . $req_attr . '>'; break;
                        case 'video':
                            $video_id    = absint( $value );
                            $thumb_url   = '';
                            $video_title = '';

                            if ( $video_id ) {
                                $thumb_url = wp_get_attachment_thumb_url( $video_id );
                                if ( ! $thumb_url ) {
                                    $thumb_url = wp_mime_type_icon( get_post_mime_type( $video_id ) );
                                }

                                $video_post = get_post( $video_id );
                                if ( $video_post && ! is_wp_error( $video_post ) ) {
                                    $video_title = $video_post->post_title;
                                }
                            }

                            $video_title = upt_title_with_extension( $video_title, $video_id );

                            echo '<div class="upt-video-upload-wrapper">';
                                echo '<div class="video-preview-wrapper" id="preview-wrapper-' . $field_id_attr . '">';
                                    if ( $thumb_url ) {
                                        echo '<img src="' . esc_url( $thumb_url ) . '" alt="" />';
                                        if ( $video_title ) {
                                            echo '<div class="upt-media-title" title="' . esc_attr( $video_title ) . '">';
                                                echo esc_html( $video_title );
                                            echo '</div>';
                                        }
                                    }
                                echo '</div>';

                                echo '<div class="image-buttons">';
                                    echo '<a href="#" class="button upt-add-video">Escolher Vídeo</a>';
                                    echo '<a href="#" class="button-link-delete upt-remove-video' . ( ! $value ? ' hidden' : '' ) . '">Remover</a>';
                                echo '</div>';

                                echo '<input type="hidden" name="' . esc_attr( $field_name ) . '" id="' . $field_id_attr . '" class="upt-video-id-input" value="' . esc_attr( $value ) . '" ' . $req_attr . '>';
                            echo '</div>';
                            
                        case 'pdf':
                            $pdf_id    = absint( $value );
                            $thumb_url = '';
                            $pdf_title = '';
                            $pdf_url   = '';

                            if ( $pdf_id ) {
                                $pdf_url = wp_get_attachment_url( $pdf_id );
                                $thumb_url = wp_mime_type_icon( 'application/pdf' );

                                $pdf_post = get_post( $pdf_id );
                                if ( $pdf_post && ! is_wp_error( $pdf_post ) ) {
                                    $pdf_title = $pdf_post->post_title;
                                }
                            }

                            $pdf_title = upt_title_with_extension( $pdf_title, $pdf_id );

                            echo '<div class="upt-pdf-upload-wrapper">';
                                echo '<div class="pdf-preview-wrapper" id="preview-wrapper-' . $field_id_attr . '">';
                                    if ( $pdf_id ) {
                                        $thumb_url = wp_get_attachment_image_url( $pdf_id, 'medium' );

                                        if ( ! $thumb_url ) {
                                            $thumb_url = wp_get_attachment_thumb_url( $pdf_id );
                                        }

                                        if ( ! $thumb_url ) {
                                            $thumb_url = wp_mime_type_icon( 'application/pdf' );
                                        }

                                        echo '<div class="upt-pdf-thumb" data-type="pdf" data-full-url="' . esc_url( $pdf_url ) . '">';
                                            echo '<img src="' . esc_url( $thumb_url ) . '" alt="" />';
                                            if ( $pdf_title ) {
                                                echo '<div class="upt-media-title" title="' . esc_attr( $pdf_title ) . '">';
                                                    echo esc_html( $pdf_title );
                                                echo '</div>';
                                            }
                                        echo '</div>';
                                    }
                                echo '</div>';

                                echo '<div class="image-buttons">';
                                    echo '<a href="#" class="button upt-add-pdf">Escolher PDF</a>';
                                    echo '<a href="#" class="button-link-delete upt-remove-pdf' . ( ! $value ? ' hidden' : '' ) . '">Remover</a>';
                                echo '</div>';

                                echo '<input type="hidden" name="' . esc_attr( $field_name ) . '" id="' . $field_id_attr . '" class="upt-pdf-id-input" value="' . esc_attr( $value ) . '" ' . $req_attr . '>';
                            echo '</div>';
                            break;

break;
                        case 'image':
                            $image_id    = absint( $value );
                            $image_title = '';

                            if ( $image_id ) {
                                $image_post = get_post( $image_id );
                                if ( $image_post && ! is_wp_error( $image_post ) ) {
                                    $image_title = $image_post->post_title;
                                }
                            }

                            $image_title = upt_title_with_extension( $image_title, $image_id );

                            echo '<div class="upt-image-upload-wrapper">';
                                echo '<div class="image-preview-wrapper" id="preview-wrapper-' . $field_id_attr . '">';
                                    if ( $image_id ) {
                                        echo wp_get_attachment_image( $image_id, 'thumbnail' );
                                        if ( $image_title ) {
                                            echo '<div class="upt-media-title" title="' . esc_attr( $image_title ) . '">';
                                                echo esc_html( $image_title );
                                            echo '</div>';
                                        }
                                    }
                                echo '</div>';

                                echo '<div class="image-buttons">';
                                    echo '<a href="#" class="button upt-add-image">Escolher Imagem</a>';
                                    echo '<a href="#" class="button-link-delete upt-remove-image' . ( ! $value ? ' hidden' : '' ) . '">Remover</a>';
                                echo '</div>';

                                echo '<input type="hidden" name="' . esc_attr( $field_name ) . '" id="' . $field_id_attr . '" class="upt-image-id-input" value="' . esc_attr( $value ) . '">';
                            echo '</div>';
                            break;

                        case 'taxonomy':
                            $is_multiple = !empty($field['multiple']);
                            $taxonomy = 'catalog_category';
                            $allow_new_category = isset($field['allow_new_category']) ? !empty($field['allow_new_category']) : true;
                            $enable_subcategories = ! empty( $field['enable_subcategories'] );
                            
                            $parent_cat_term = get_term_by('slug', $schema_slug, $taxonomy);

                            if ( !$parent_cat_term || is_wp_error($parent_cat_term) ) {
                                $schema_term_obj = get_term_by('slug', $schema_slug, 'catalog_schema');
                                if ( $schema_term_obj ) {
                                    $new_term_result = wp_insert_term($schema_term_obj->name, $taxonomy, ['slug' => $schema_slug]);
                                    if (!is_wp_error($new_term_result)) {
                                        $parent_cat_id = $new_term_result['term_id'];
                                    } else {
                                        $parent_cat_id = 0;
                                    }
                                } else {
                                    $parent_cat_id = 0;
                                }
                            } else {
                                $parent_cat_id = $parent_cat_term->term_id;
                            }

                            if ($is_multiple) {
                                echo '<div class="upt-taxonomy-checklist" id="' . $field_id_attr . '" data-upt-taxonomy="' . esc_attr( $taxonomy ) . '">';
                                $selected_terms = $is_editing ? wp_get_object_terms($item_id, $taxonomy, ['fields' => 'ids']) : [];
                                if (is_wp_error($selected_terms)) $selected_terms = [];
                                
                                $all_terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => 0, 'parent' => $parent_cat_id]);
                                
                                if (!empty($all_terms) && !is_wp_error($all_terms)) {
                                    foreach($all_terms as $term) {
                                        $checked = in_array($term->term_id, $selected_terms) ? 'checked' : '';
                                        $checkbox_id = 'cat-checkbox-' . esc_attr($term->term_id);
                                        
                                        echo '<input type="checkbox" class="upt-cat-checkbox" name="categoria-do-item[]" id="' . $checkbox_id . '" value="' . esc_attr($term->term_id) . '" ' . $checked . '>';
                                        echo '<label for="' . $checkbox_id . '"><span class="pill-checkbox-icon"></span><span class="pill-text">' . esc_html($term->name) . '</span>';
                                        if ( $allow_new_category ) {
                                            echo ' <a href="#" class="remove-pill remove-cat-pill" title="Excluir Categoria" data-term-id="' . esc_attr($term->term_id) . '" data-term-name="' . esc_attr($term->name) . '"><span class="pill-delete-icon"></span></a>';
                                        }
                                        echo '</label>';
                                    }
                                } else {
                                    echo '<span class="upt-taxonomy-empty">Nenhuma categoria encontrada para este esquema.</span>';
                                }
                                echo '</div>';

                            } else {
                                // Quando subcategorias dependentes estão habilitadas, precisamos empilhar (Categoria acima, Subcategoria abaixo)
                                // ocupando 100% da largura.
                                $taxonomy_wrapper_class = 'taxonomy-field-wrapper';
                                if ( ! empty( $enable_subcategories ) ) {
                                    $taxonomy_wrapper_class .= ' taxonomy-field-wrapper--subcats';
                                }
                                echo '<div class="' . esc_attr( $taxonomy_wrapper_class ) . '">';

                                $terms = $is_editing ? wp_get_object_terms($item_id, $taxonomy, ['fields' => 'ids']) : [];
                                $selected_term_id = !is_wp_error($terms) && !empty($terms) ? absint($terms[0]) : 0;

                                $selected_parent_id = 0;
                                $selected_child_id  = 0;

                                if ( $selected_term_id ) {
                                    $selected_term_obj = get_term( $selected_term_id, $taxonomy );
                                    if ( $selected_term_obj && ! is_wp_error( $selected_term_obj ) ) {
                                        // Se for filho de uma categoria do esquema, tratamos como subcategoria.
                                        if ( $enable_subcategories && (int) $selected_term_obj->parent > 0 && (int) $selected_term_obj->parent !== (int) $parent_cat_id ) {
                                            $selected_child_id  = (int) $selected_term_obj->term_id;
                                            $selected_parent_id = (int) $selected_term_obj->parent;
                                        } else {
                                            $selected_parent_id = (int) $selected_term_obj->term_id;
                                        }
                                    }
                                }

                                if ( $enable_subcategories ) {
                                    // Categoria (nível 1) - SOMENTE filhos diretos da raiz do esquema.
                                    // Não usar wp_dropdown_categories com hierarchical/child_of para não misturar subcategorias.
                                    $top_terms = get_terms([
                                        'taxonomy'   => $taxonomy,
                                        'hide_empty' => 0,
                                        'parent'     => $parent_cat_id,
                                        'orderby'    => 'name',
                                        'order'      => 'ASC',
                                    ]);
                                    if ( is_wp_error( $top_terms ) ) {
                                        $top_terms = [];
                                    }

                                    $required_attr = ! empty( $field['required'] ) ? ' required' : '';
                                    echo '<select name="upt-category-parent" id="upt-category-parent" class="upt-category-parent"' . $required_attr . '>';
                                    echo '<option value="">— Selecione a categoria —</option>';
                                    if ( ! empty( $top_terms ) ) {
                                        foreach ( $top_terms as $t ) {
                                            $sel = selected( $selected_parent_id, (int) $t->term_id, false );
                                            echo '<option value="' . esc_attr( $t->term_id ) . '" ' . $sel . '>' . esc_html( $t->name ) . '</option>';
                                        }
                                    }
                                    echo '</select>';

                                    // Botões de ação da CATEGORIA (Pai) - Movidos para cá (Acima das subcategorias)
                                    echo '<div class="taxonomy-actions-wrapper" style="margin-top: 8px;">';
                                    if ( $allow_new_category ) {
                                        echo ' <a href="#" id="add-new-cat-button" data-field-id="' . esc_attr($field_id_attr) . '" data-parent-id="' . esc_attr($parent_cat_id) . '" style="margin-right:5px;">Adicionar</a>';
                                    }
                                    echo ' <a href="#" id="rename-cat-button" title="Renomear categoria selecionada" style="margin-right:5px;">Renomear</a>';
                                    if ( $allow_new_category ) {
                                        echo ' <a href="#" id="delete-cat-button" title="Excluir categoria selecionada" style="margin-right:5px;">Excluir</a>';
                                    }

                                    if ( $allow_new_category ) {
                                        echo '<div id="new-cat-area" class="upt-new-item-area" style="display:none;">';
                                        echo '<label class="upt-new-cat-label">Nova categoria</label>';
                                        echo '<input type="text" id="new-cat-name" placeholder="Nome da nova categoria">';
                                        echo '<label class="upt-subcats-toggle" style="display:block; margin-top:8px;">';
                                        echo '<input type="checkbox" id="new-cat-create-subcats" value="1"> Criar subcategorias</label>';
                                        echo '<textarea id="new-cat-subcats" rows="4" placeholder="Subcategorias (uma por linha)" style="width:100%; margin-top:6px; display:none;"></textarea>';
                                        $target_for_new_cat = 'upt-category-parent';
                                        echo '<div class="upt-new-cat-actions">';
                                        echo '<button type="button" id="save-new-cat" class="button button-small" data-target-checklist="' . esc_attr( $target_for_new_cat ) . '">Salvar Categoria</button>';
                                        echo '<a href="#" id="cancel-new-cat" class="cancel-new-item-link">Cancelar</a>';
                                        echo '</div>';
                                        echo '<div id="new-cat-status" class="new-item-status"></div>';
                                        echo '</div>';
                                    }
                                    
                                    // Área para renomear categoria (sem prompt nativo)
                                    echo '<div class="upt-rename-cat-area upt-new-item-area" style="display:none; margin-top:8px;">';
                                    echo '<input type="hidden" class="upt-rename-cat-term-id" value="0">';
                                    echo '<label class="upt-subcat-field-label">Categoria</label>';
                                    echo '<input type="text" class="upt-rename-cat-name" placeholder="Novo nome da categoria">';
                                    echo '<button type="button" class="button button-small upt-rename-cat-save">Salvar</button>';
                                    echo '<a href="#" class="cancel-new-item-link upt-rename-cat-cancel">Cancelar</a>';
                                    echo '<div class="new-item-status upt-rename-cat-status"></div>';
                                    echo '</div>';

echo '</div>';

                                    // Campo final (hidden) que o back-end já entende.
                                    echo '<input type="hidden" name="categoria-do-item" id="categoria-do-item" value="' . esc_attr( $selected_child_id ? $selected_child_id : $selected_parent_id ) . '">';

                                    // Subcategorias
                                    $subcats = [];
                                    if ( $selected_parent_id ) {
                                        $subcats = get_terms([
                                            'taxonomy' => $taxonomy,
                                            'hide_empty' => 0,
                                            'parent' => $selected_parent_id,
                                            'orderby' => 'name',
                                            'order' => 'ASC',
                                        ]);
                                        if ( is_wp_error( $subcats ) ) {
                                            $subcats = [];
                                        }
                                    }

                                    $subcat_label = 'Subcategoria';
                                    if ( isset( $field['subcategories_label'] ) && $field['subcategories_label'] !== '' ) {
                                        $subcat_label = (string) $field['subcategories_label'];
                                    }

                                    $subcat_required = ! empty( $field['subcategories_required'] );
                                    $subcat_required_attr = $subcat_required ? ' required' : '';

                                    $subcat_wrapper_style = $selected_parent_id ? '' : 'display:none;';
                                    // Campo 2 (subcategorias) deve aparecer ABAIXO do campo de categoria.
                                    echo '<div class="upt-subcategory-wrapper" style="' . esc_attr( $subcat_wrapper_style ) . '; margin-top:10px;" data-schema-root="' . esc_attr( $parent_cat_id ) . '" data-subcat-required="' . ( $subcat_required ? '1' : '0' ) . '">';
                                    echo '<label class="upt-subcategory-label">' . esc_html( $subcat_label ) . '</label>';
                                    echo '<select id="upt-category-child" name="upt-category-child" class="upt-category-child" style="width:100%;"' . $subcat_required_attr . '>';
                                    echo '<option value="">— Selecione —</option>';
                                    if ( ! empty( $subcats ) ) {
                                        foreach ( $subcats as $sub ) {
                                            $sel = selected( $selected_child_id, (int) $sub->term_id, false );
                                            echo '<option value="' . esc_attr( $sub->term_id ) . '" ' . $sel . '>' . esc_html( $sub->name ) . '</option>';
                                        }
                                    }
                                    echo '</select>';

                                    // Ações do campo de subcategorias (devem aparecer abaixo do campo 2)
                                    echo '<div class="upt-subcategory-actions" style="margin-top:8px; display:none;">';
                                    if ( $allow_new_category ) {
                                        echo ' <a href="#" class="upt-subcat-add" style="margin-right:5px;">Adicionar</a>';
                                    }
                                    echo ' <a href="#" class="upt-subcat-edit" style="margin-right:5px;">Editar</a>';
                                    if ( $allow_new_category ) {
                                        echo ' <a href="#" class="upt-subcat-delete" style="margin-right:5px;">Excluir</a>';
                                    }
                                    echo '</div>';

                                    // Área animada para criar/editar subcategorias (mesmo padrão do "Adicionar opção")
                                    echo '<div class="upt-subcat-area upt-new-item-area" style="display:none; margin-top:8px;">';
                                    echo '<input type="hidden" class="upt-subcat-mode" value="create">';
                                    echo '<input type="hidden" class="upt-subcat-term-id" value="0">';
                                    echo '<label class="upt-subcat-field-label">Subcategoria</label>';
                                    echo '<input type="text" class="upt-subcat-name" placeholder="Nome da subcategoria">';
                                    echo '<label class="upt-subcat-field-label upt-subcat-field-label--parent">Categoria vinculada</label>';
                                    echo '<select class="upt-subcat-parent" style="width:100%;">';
                                    echo '<option value="">— Categoria pai —</option>';
                                    $top_terms_for_move = get_terms([
                                        'taxonomy' => $taxonomy,
                                        'hide_empty' => 0,
                                        'parent' => $parent_cat_id,
                                        'orderby' => 'name',
                                        'order' => 'ASC',
                                    ]);
                                    if ( ! is_wp_error( $top_terms_for_move ) && ! empty( $top_terms_for_move ) ) {
                                        foreach ( $top_terms_for_move as $t ) {
                                            echo '<option value="' . esc_attr( $t->term_id ) . '">' . esc_html( $t->name ) . '</option>';
                                        }
                                    }
                                    echo '</select>';
                                    echo '<button type="button" class="button button-small upt-subcat-save">Salvar</button>';
                                    echo '<a href="#" class="cancel-new-item-link upt-subcat-cancel">Cancelar</a>';
                                    echo '<div class="new-item-status upt-subcat-status"></div>';
                                    echo '</div>';

                                    echo '</div>';
                                } else {
                                    // Comportamento antigo (1 dropdown hierárquico)
                                    $dropdown_args = [
                                        'taxonomy' => $taxonomy,
                                        'name' => 'categoria-do-item',
                                        'id' => 'categoria-do-item',
                                        'show_option_none' => '— Selecione —',
                                        'option_none_value' => '',
                                        'hierarchical' => true,
                                        'hide_empty' => 0,
                                        'orderby' => 'name',
                                        'order' => 'ASC',
                                        'selected' => $selected_term_id,
                                        'child_of' => $parent_cat_id,
                                        'echo' => 0,
                                    ];
                                    $dropdown_html = wp_dropdown_categories( $dropdown_args );
                                    if ( ! empty( $field['required'] ) ) {
                                        $dropdown_html = preg_replace(
                                            '/<select\\b([^>]*)>/',
                                            '<select$1 required>',
                                            $dropdown_html,
                                            1
                                        );
                                    }
                                    echo $dropdown_html;
                                }

                                echo '</div>';
                            }

                            // Exibir botões para o comportamento antigo ou taxonomia múltipla (onde não há sub-categorias dependentes habilitadas)
                            if ( empty($enable_subcategories) || $is_multiple ) {
                                echo '<div class="taxonomy-actions-wrapper" style="margin-top: 8px;">';
                                if ( $allow_new_category ) {
                                    echo ' <a href="#" id="add-new-cat-button" data-field-id="' . esc_attr($field_id_attr) . '" data-parent-id="' . esc_attr($parent_cat_id) . '" style="margin-right:5px;">Adicionar</a>';
                                }
                                if (!$is_multiple) {
                                    echo ' <a href="#" id="rename-cat-button" title="Renomear categoria selecionada" style="margin-right:5px;">Renomear</a>';
                                    if ( $allow_new_category ) {
                                        echo ' <a href="#" id="delete-cat-button" title="Excluir categoria selecionada" style="margin-right:5px;">Excluir</a>';
                                    }
                                }

                                if ( $allow_new_category ) {
                                    echo '<div id="new-cat-area" class="upt-new-item-area" style="display:none;">';
                                    echo '<input type="text" id="new-cat-name" placeholder="Nome da nova categoria">';
                                    echo '<label class="upt-subcats-toggle" style="display:block; margin-top:8px;">';
                                    echo '<input type="checkbox" id="new-cat-create-subcats" value="1"> Criar subcategorias</label>';
                                    echo '<textarea id="new-cat-subcats" rows="4" placeholder="Subcategorias (uma por linha)" style="width:100%; margin-top:6px; display:none;"></textarea>';
                                    $target_for_new_cat = $field_id_attr;
                                    echo '<button type="button" id="save-new-cat" class="button button-small" data-target-checklist="' . esc_attr( $target_for_new_cat ) . '">Salvar Categoria</button>';
                                    echo '<a href="#" id="cancel-new-cat" class="cancel-new-item-link">Cancelar</a>';
                                    echo '<div id="new-cat-status" class="new-item-status"></div>';
                                    echo '</div>';
                                }
                                
                                    // Área para renomear categoria (sem prompt nativo)
                                    echo '<div class="upt-rename-cat-area upt-new-item-area" style="display:none; margin-top:8px;">';
                                    echo '<input type="hidden" class="upt-rename-cat-term-id" value="0">';
                                    echo '<label class="upt-subcat-field-label">Categoria</label>';
                                    echo '<input type="text" class="upt-rename-cat-name" placeholder="Novo nome da categoria">';
                                    echo '<button type="button" class="button button-small upt-rename-cat-save">Salvar</button>';
                                    echo '<a href="#" class="cancel-new-item-link upt-rename-cat-cancel">Cancelar</a>';
                                    echo '<div class="new-item-status upt-rename-cat-status"></div>';
                                    echo '</div>';

echo '</div>';
                            }
                            break;
                        case 'gallery': ?>
                            <div class="upt-gallery-wrapper">
                                <div class="gallery-previews">
                                    <?php
                                    if ( $value ) {
                                        $ids = array_filter( array_map( 'absint', explode( ',', $value ) ) );
                                        foreach ( $ids as $id ) {
                                            $mime = get_post_mime_type( $id );
                                            $full = wp_get_attachment_url( $id );
                                            $type = ( $mime === 'application/pdf' ) ? 'pdf' : ( ( strpos( $mime, 'video' ) !== false ) ? 'video' : 'image' );

                                            if ( 'image' === $type ) {
                                                $thumb = wp_get_attachment_image_url( $id, 'thumbnail' );
                                            } else {
                                                $thumb = wp_mime_type_icon( $mime );
                                            }

                                            if ( ! $thumb ) {
                                                continue;
                                            }

                                            echo '<div class="gallery-preview-item" data-id="' . esc_attr( $id ) . '" data-type="' . esc_attr( $type ) . '" data-full-url="' . esc_url( $full ) . '">';
                                            echo '<img src="' . esc_url( $thumb ) . '" alt="" loading="lazy">';
                                            echo '<a href="#" class="remove-image">×</a>';
                                            echo '</div>';
                                        }
                                    }
                                ?>
                                </div>
                                <a href="#" class="button add-gallery-images">Adicionar/Alterar Mídias</a>
                                <input type="hidden" name="<?php echo $field_name; ?>" class="gallery-ids-input" value="<?php echo esc_attr($value); ?>" <?php echo $req_attr; ?>>
                            </div>
                            <?php break;
                        default:
                            $max_length = isset($field['max_length']) ? absint($field['max_length']) : 0;
                            $maxlength_attr = $max_length > 0 ? ' maxlength="' . $max_length . '"' : '';
                            echo '<input type="text" name="' . $field_name . '" id="' . $field_id_attr . '" value="' . esc_attr($value) . '" ' . $req_attr . $maxlength_attr . '>';
                            break;
                    } 
                    
                    if ( ! empty( $field['hint'] ) ) {
                        echo '<p class="upt-field-hint">' . esc_html( $field['hint'] ) . '</p>';
                    }
                    ?>
                </div>
            <?php endforeach; ?>
            <?php if ( $is_editing && $post_to_edit ) : ?>
                <?php $current_status = get_post_status( $post_to_edit ); ?>
                <div class="upt-field-row">
                    <label for="upt_item_status"><strong>Status do item</strong></label>
                    <select name="item_status" id="upt_item_status">
                        <option value="publish" <?php selected( $current_status, 'publish' ); ?>>Publicado (aparece no site)</option>
                        <option value="draft" <?php selected( $current_status, 'draft' ); ?>>Arquivado (não aparece no site)</option>
                    </select>
                </div>
            <?php endif; ?>
            <p style="margin-top: 16px;">
                <?php wp_nonce_field('upt_new_item', 'upt_submit_nonce'); ?>
                <input type="hidden" name="schema_slug" value="<?php echo esc_attr($schema_slug); ?>" />
                <?php if ($is_editing) : ?><input type="hidden" name="item_id" value="<?php echo $item_id; ?>" /><?php endif; ?>
                <input type="submit" class="button button-primary" value="<?php echo $is_editing ? 'Atualizar Item' : 'Enviar Item'; ?>" />
            </p>
        </form>
    <?php endif; ?>
</div>
