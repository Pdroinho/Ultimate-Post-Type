<?php
if (!defined('ABSPATH'))
    exit;
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
                        $schemas_terms = get_terms([
                            'taxonomy' => 'catalog_schema',
                            'hide_empty' => false,
                        ]);
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

            <div class="upt-import-stepper" style="display:flex;gap:0;margin-bottom:20px;">
                <div class="upt-step upt-step--active" data-step="1" style="flex:1;text-align:center;padding:10px 8px;font-size:13px;font-weight:600;border-bottom:3px solid #6366f1;color:#6366f1;">1. Arquivo</div>
                <div class="upt-step" data-step="2" style="flex:1;text-align:center;padding:10px 8px;font-size:13px;font-weight:600;border-bottom:3px solid #e2e8f0;color:#94a3b8;">2. Configurar</div>
                <div class="upt-step" data-step="3" style="flex:1;text-align:center;padding:10px 8px;font-size:13px;font-weight:600;border-bottom:3px solid #e2e8f0;color:#94a3b8;">3. Importar</div>
            </div>

            <div id="upt-imob-validation-msg" class="upt-import-msg" style="display:none;margin-bottom:16px;"></div>

            <div id="upt-imob-upload-section">

                <div id="upt-imob-dropzone" class="upt-import-dropzone" style="border:2px dashed #cbd5e1;border-radius:10px;padding:36px 20px;text-align:center;cursor:pointer;transition:border-color .2s,background .2s;margin-bottom:16px;">
                    <div style="font-size:36px;margin-bottom:8px;color:#94a3b8;">📂</div>
                    <p style="margin:0 0 4px;font-weight:600;color:#334155;">Arraste o arquivo XML aqui</p>
                    <p style="margin:0;font-size:13px;color:#64748b;">ou clique para selecionar</p>
                    <input type="file" id="upt-imob-xml-file" accept=".xml,text/xml,application/xml" style="display:none;">
                </div>

                <div id="upt-imob-file-info" style="display:none;padding:10px 14px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;margin-bottom:16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <strong id="upt-imob-file-name" style="color:#0369a1;"></strong>
                            <span id="upt-imob-file-size" style="color:#64748b;font-size:12px;margin-left:8px;"></span>
                        </div>
                        <button type="button" id="upt-imob-file-remove" style="background:none;border:none;cursor:pointer;color:#dc2626;font-size:16px;" title="Remover arquivo">✕</button>
                    </div>
                </div>

                <div id="upt-imob-schema-section" style="display:none;margin-bottom:16px;">
                    <div class="form-field">
                        <label>Esquema de destino:</label>
                        <select id="upt-imob-schema-mode">
                            <option value="new">Criar um novo esquema</option>
                            <option value="existing">Usar um esquema existente</option>
                        </select>
                    </div>

                    <div id="upt-imob-new-schema-field" class="form-field">
                        <label for="upt-imob-schema-name">Nome do novo esquema:</label>
                        <input type="text" id="upt-imob-schema-name" value="Imóveis" placeholder="Ex.: Imóveis à Venda" />
                    </div>

                    <div id="upt-imob-existing-schema-field" class="form-field" style="display:none;">
                        <label for="upt-imob-schema-existing">Esquema existente:</label>
                        <select id="upt-imob-schema-existing">
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
                </div>

                <p>
                    <button type="button" class="button button-primary" id="upt-imob-start-btn" style="display:none;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
                        Iniciar Importação
                    </button>
                </p>
            </div>

            <div id="upt-imob-progress-section" style="display:none;">
                <div class="upt-imob-progress-bar-wrap" style="background:#e2e8f0;border-radius:8px;overflow:hidden;height:28px;position:relative;margin:16px 0 10px;">
                    <div id="upt-imob-progress-bar" class="upt-import-progress__bar-track" style="background:#6366f1;height:100%;width:0%;transition:width 0.4s ease;border-radius:8px;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    <span id="upt-imob-progress-text" style="position:absolute;top:0;left:0;right:0;text-align:center;line-height:28px;color:#fff;font-size:13px;font-weight:600;text-shadow:0 1px 2px rgba(0,0,0,0.2);">0%</span>
                </div>
                <div id="upt-imob-stats" style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:12px;font-size:13px;color:#475569;">
                    <span>Total: <strong id="upt-imob-stat-total">—</strong></span>
                    <span>Importados: <strong id="upt-imob-stat-imported" style="color:#16a34a;">0</strong></span>
                    <span>Fotos: <strong id="upt-imob-stat-photos" style="color:#2563eb;">0</strong></span>
                    <span>Erros: <strong id="upt-imob-stat-errors" style="color:#dc2626;">0</strong></span>
                </div>
                <div id="upt-imob-status-msg" class="upt-import-msg upt-import-msg--info" style="padding:10px 14px;border-radius:6px;background:#f1f5f9;margin-bottom:12px;font-size:13px;">
                    <span class="upt-import-msg__icon" aria-hidden="true">⏳</span>
                    <span class="upt-import-msg__text">Preparando...</span>
                </div>
                <p>
                    <button type="button" class="button" id="upt-imob-cancel-btn" style="color:#dc2626;border-color:#dc2626;">Cancelar Importação</button>
                </p>
            </div>

            <div id="upt-imob-done-section" style="display:none;">
                <div style="padding:16px;border-radius:8px;background:#f0fdf4;border:1px solid #bbf7d0;margin:12px 0;">
                    <p style="margin:0 0 6px;font-weight:600;color:#16a34a;">✅ Importação concluída com sucesso!</p>
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
<pre><code>&lt;upt_export generated_at="2025-01-01 12:00:00" site_url="https://seusite.com"&gt;
  &lt;schemas&gt;
    &lt;schema slug="slug_do_esquema"&gt;
      &lt;label&gt;Nome do esquema&lt;/label&gt;
    &lt;/schema&gt;
  &lt;/schemas&gt;
  &lt;items&gt;
    &lt;item&gt;
      &lt;id&gt;123&lt;/id&gt;
      &lt;title&gt;Nome do item&lt;/title&gt;
      &lt;schema_slug&gt;slug_do_esquema&lt;/schema_slug&gt;
      &lt;content&gt;...&lt;/content&gt;
      &lt;excerpt&gt;...&lt;/excerpt&gt;
      &lt;status&gt;publish&lt;/status&gt;
      &lt;categories&gt;
        &lt;category&gt;
          &lt;name&gt;Nome da categoria&lt;/name&gt;
          &lt;slug&gt;categoria-slug&lt;/slug&gt;
        &lt;/category&gt;
      &lt;/categories&gt;
      &lt;featured_image&gt;
        &lt;id&gt;123&lt;/id&gt;
        &lt;url&gt;https://seusite.com/wp-content/uploads/...&lt;/url&gt;
      &lt;/featured_image&gt;
      &lt;fields&gt;
        &lt;field id="id_do_campo_1"&gt;valor&lt;/field&gt;
        &lt;field id="id_do_campo_2"&gt;outro valor&lt;/field&gt;
      &lt;/fields&gt;
    &lt;/item&gt;
  &lt;/items&gt;
&lt;/upt_export&gt;</code></pre>
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
                    } catch (e) {}

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
    <?php endif; ?>
</div>
