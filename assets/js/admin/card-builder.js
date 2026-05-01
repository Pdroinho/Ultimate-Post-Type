(function() {
    'use strict';

    var ajaxUrl = (typeof upt_ajax !== 'undefined') ? upt_ajax.ajax_url : '/wp-admin/admin-ajax.php';
    var nonce = (typeof upt_ajax !== 'undefined') ? upt_ajax.nonce : '';

    // ===== CRON Settings =====
    (function() {
        var $saveBtn = jQuery('#upt-cron-save');
        if (!$saveBtn.length) return;

        $saveBtn.on('click', function() {
            var $btn = jQuery(this);
            $btn.prop('disabled', true).text('Salvando...');
            jQuery.post(ajaxUrl, {
                action: 'upt_save_cron_config',
                nonce: nonce,
                url: jQuery('#upt-cron-url').val(),
                schema: jQuery('#upt-cron-schema').val(),
                frequency: jQuery('#upt-cron-freq').val()
            }, function(resp) {
                $btn.prop('disabled', false);
                if (resp.success) {
                    location.reload();
                } else {
                    window.uptToast('Erro: ' + (resp.data && resp.data.message ? resp.data.message : 'desconhecido'), 'error');
                    $btn.text('Salvar e Ativar');
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Salvar e Ativar');
                window.uptToast('Erro de conexão.', 'error');
            });
        });

        jQuery('#upt-cron-test').on('click', function() {
            var $btn = jQuery(this);
            $btn.prop('disabled', true).text('Importando...');
            jQuery.post(ajaxUrl, {
                action: 'upt_test_cron_import',
                nonce: nonce
            }, function(resp) {
                $btn.prop('disabled', false).text('Testar Agora');
                if (resp.success) {
                    var data = resp.data || {};
                    var msg = data.message || 'Teste concluído!';
                    if (data.stats) {
                        msg += '\n\nTotal: ' + (data.stats.total || 0);
                        msg += '\nImportados: ' + (data.stats.imported || 0);
                        msg += '\nErros: ' + (data.stats.errors || 0);
                    }
                    if (data.last_run) {
                        msg += '\n\nÚltima execução: ' + data.last_run;
                    }
                    window.uptToast(msg, 'success');
                    location.reload();
                } else {
                    window.uptToast('Erro: ' + (resp.data && resp.data.message ? resp.data.message : 'desconhecido'), 'error');
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Testar Agora');
                window.uptToast('Erro de conexão. O servidor pode ter atingido o tempo limite (timeout). Tente novamente.', 'error');
            });
        });
    })();

    // ===== Card Settings + Builder =====
    (function() {
        var $saveBtn = jQuery('#upt-save-card-settings');
        if (!$saveBtn.length) return;

        var $savedMsg = jQuery('#upt-card-settings-saved');
        var $errorMsg = jQuery('#upt-card-settings-error');
        var savedTimer = null;

        var $dashPreview = jQuery('#upt-card-preview');

        jQuery('.upt-cards-settings__checkbox').on('change', function() {
            var target = jQuery(this).data('preview-target');
            if (target) {
                var $field = $dashPreview.find('[data-preview="' + target + '"]');
                if ($field.length) {
                    $field.css('display', this.checked ? ($field.hasClass('upt-preview-field--badge') ? 'inline-flex' : 'block') : 'none');
                }
            }
        });

        // --- Card Builder ---
        var $builderList = jQuery('#upt-card-builder-list');
        var $editor = jQuery('#upt-builder-editor');
        var $sitePreviewBody = jQuery('#upt-site-preview-body');
        var editingIndex = -1;

        $builderList.sortable({
            handle: '.upt-builder-item__handle',
            placeholder: 'upt-builder-item ui-sortable-placeholder',
            tolerance: 'pointer',
            cursor: 'grabbing',
            opacity: 0.85,
            over: function() { $builderList.addClass('ui-sortable-over'); },
            out: function() { $builderList.removeClass('ui-sortable-over'); },
            update: function() {
                $builderList.find('.upt-builder-item').each(function(i) {
                    jQuery(this).attr('data-index', i);
                });
                refreshSitePreview();
            }
        });

        $builderList.on('click', '.upt-builder-toggle', function(e) {
            e.stopPropagation();
            var $item = jQuery(this).closest('.upt-builder-item');
            var isHidden = $item.hasClass('upt-builder-item--hidden');
            $item.toggleClass('upt-builder-item--hidden', !isHidden);
            $item.find('.upt-builder-visibility-input').val(isHidden ? '1' : '0');
            jQuery(this).attr('title', isHidden ? 'Ocultar' : 'Mostrar')
                       .attr('aria-label', isHidden ? 'Ocultar elemento' : 'Mostrar elemento')
                       .text(isHidden ? '\uD83D\uDC41\uFE0F' : '\uD83D\uDC41\uFE0F\u200D\uD83D\uDDE8\uFE0F');
            refreshSitePreview();
        });

        $builderList.on('click', '.upt-builder-edit', function(e) {
            e.stopPropagation();
            var $item = jQuery(this).closest('.upt-builder-item');
            var idx = $item.index();

            $builderList.find('.upt-builder-item--editing').removeClass('upt-builder-item--editing');
            $item.addClass('upt-builder-item--editing');

            editingIndex = idx;
            var color = $item.find('.upt-builder-color-input').val() || '#111827';
            var prefix = $item.find('.upt-builder-prefix-input').val();
            var suffix = $item.find('.upt-builder-suffix-input').val();
            var fontSize = $item.find('.upt-builder-fontsize-input').val();
            var fontWeight = $item.find('.upt-builder-fontweight-input').val();
            var label = $item.find('.upt-builder-item__label').text();

            jQuery('#upt-editor-color').val(color);
            jQuery('#upt-editor-prefix').val(prefix);
            jQuery('#upt-editor-suffix').val(suffix);
            jQuery('#upt-editor-fontsize').val(fontSize);
            jQuery('#upt-editor-fontweight').val(fontWeight);
            $editor.find('.upt-builder-editor__title').text('Editar: ' + label);
            $editor.slideDown(200);
        });

        $editor.on('click', '.upt-builder-editor__close', function() {
            closeEditor();
        });

        jQuery('#upt-editor-apply').on('click', function() {
            if (editingIndex < 0) return;
            var $item = $builderList.find('.upt-builder-item').eq(editingIndex);
            if (!$item.length) return;

            $item.find('.upt-builder-color-input').val(jQuery('#upt-editor-color').val());
            $item.find('.upt-builder-prefix-input').val(jQuery('#upt-editor-prefix').val());
            $item.find('.upt-builder-suffix-input').val(jQuery('#upt-editor-suffix').val());
            $item.find('.upt-builder-fontsize-input').val(jQuery('#upt-editor-fontsize').val());
            $item.find('.upt-builder-fontweight-input').val(jQuery('#upt-editor-fontweight').val());

            $item.removeClass('upt-builder-item--editing');
            refreshSitePreview();
            closeEditor();
        });

        function closeEditor() {
            editingIndex = -1;
            $builderList.find('.upt-builder-item--editing').removeClass('upt-builder-item--editing');
            $editor.slideUp(150);
        }

        jQuery('#upt-builder-add-btn').on('click', function() {
            var $select = jQuery('#upt-builder-add-select');
            var fieldId = $select.val();
            if (!fieldId) return;

            var $opt = $select.find('option:selected');
            var icon = $opt.data('icon') || '\uD83D\uDCCE';
            var label = $opt.text().replace(/^[^\s]+\s/, '');

            var html = '<div class="upt-builder-item upt-builder-item--custom" data-index="' + $builderList.children().length + '" role="listitem">';
            html += '<span class="upt-builder-item__handle" aria-label="Arrastar para reordenar">\u2630</span>';
            html += '<span class="upt-builder-item__icon" aria-hidden="true">' + icon + '</span>';
            html += '<span class="upt-builder-item__label">' + label + '</span>';
            html += '<input type="hidden" name="upt_builder_id[]" value="' + fieldId + '">';
            html += '<input type="hidden" name="upt_builder_visible[]" value="1" class="upt-builder-visibility-input">';
            html += '<input type="hidden" name="upt_builder_color[]" value="" class="upt-builder-color-input">';
            html += '<input type="hidden" name="upt_builder_prefix[]" value="" class="upt-builder-prefix-input">';
            html += '<input type="hidden" name="upt_builder_suffix[]" value="" class="upt-builder-suffix-input">';
            html += '<input type="hidden" name="upt_builder_fontSize[]" value="" class="upt-builder-fontsize-input">';
            html += '<input type="hidden" name="upt_builder_fontWeight[]" value="" class="upt-builder-fontweight-input">';
            html += '<div class="upt-builder-item__actions">';
            html += '<button type="button" class="upt-builder-toggle" title="Ocultar" aria-label="Ocultar elemento">\uD83D\uDC41\uFE0F</button>';
            html += '<button type="button" class="upt-builder-edit" title="Editar" aria-label="Editar elemento">\u270F\uFE0F</button>';
            html += '<button type="button" class="upt-builder-item__remove" title="Remover" aria-label="Remover elemento">\u2715</button>';
            html += '</div></div>';

            $builderList.append(html);
            $opt.remove();
            $select.val('');
            refreshSitePreview();
        });

        $builderList.on('click', '.upt-builder-item__remove', function(e) {
            e.stopPropagation();
            var $item = jQuery(this).closest('.upt-builder-item');
            var fieldId = $item.find('input[name="upt_builder_id[]"]').val();
            var icon = $item.find('.upt-builder-item__icon').text();
            var label = $item.find('.upt-builder-item__label').text();

            $item.fadeOut(150, function() {
                jQuery(this).remove();
                $builderList.find('.upt-builder-item').each(function(i) {
                    jQuery(this).attr('data-index', i);
                });
                refreshSitePreview();
            });

            var $select = jQuery('#upt-builder-add-select');
            $select.append('<option value="' + fieldId + '" data-icon="' + icon + '">' + icon + ' ' + label + '</option>');
        });

        function getBuilderData() {
            var items = [];
            $builderList.find('.upt-builder-item').each(function() {
                var $el = jQuery(this);
                items.push({
                    id: $el.find('input[name="upt_builder_id[]"]').val(),
                    visible: $el.find('.upt-builder-visibility-input').val() === '1',
                    color: $el.find('.upt-builder-color-input').val(),
                    prefix: $el.find('.upt-builder-prefix-input').val(),
                    suffix: $el.find('.upt-builder-suffix-input').val(),
                    fontSize: $el.find('.upt-builder-fontsize-input').val(),
                    fontWeight: $el.find('.upt-builder-fontweight-input').val()
                });
            });
            return items;
        }

        function refreshSitePreview() {
            var data = getBuilderData();
            var html = '';
            for (var i = 0; i < data.length; i++) {
                var el = data[i];
                var style = '';
                if (el.color) style += 'color:' + el.color + ';';
                if (el.fontSize) style += 'font-size:' + parseInt(el.fontSize) + 'px;';
                if (el.fontWeight) style += 'font-weight:' + parseInt(el.fontWeight) + ';';
                var display = el.visible ? '' : 'display:none;';

                if (el.id === 'image') {
                    html += '<div class="upt-builder-preview-img" data-builder-preview="' + el.id + '" style="' + display + '"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>';
                } else if (el.id === 'button') {
                    var btnText = el.prefix || 'Ver Detalhes';
                    var btnColor = el.color || '#6366f1';
                    html += '<div data-builder-preview="' + el.id + '" style="' + display + 'margin-top:8px;"><span style="display:inline-block;padding:6px 16px;border-radius:6px;background:' + btnColor + ';color:#fff;font-size:13px;font-weight:600;">' + btnText + '</span></div>';
                } else if (el.id === 'title') {
                    html += '<span class="upt-builder-preview-el" data-builder-preview="' + el.id + '" style="' + display + style + '">' + (el.prefix || '') + 'Apartamento 3 Quartos' + (el.suffix || '') + '</span>';
                } else if (el.id === 'price') {
                    html += '<span class="upt-builder-preview-el" data-builder-preview="' + el.id + '" style="' + display + style + '">' + (el.prefix || '') + '450.000' + (el.suffix || '') + '</span>';
                } else if (el.id === 'status') {
                    html += '<span class="upt-builder-preview-el" data-builder-preview="' + el.id + '" style="' + display + 'display:inline-flex;padding:2px 8px;border-radius:4px;font-size:11px;background:#eff6ff;color:#2563eb;' + style + '">' + (el.prefix || '') + 'Publicado' + (el.suffix || '') + '</span>';
                } else if (el.id === 'category') {
                    html += '<span class="upt-builder-preview-el" data-builder-preview="' + el.id + '" style="' + display + 'display:inline-flex;padding:2px 8px;border-radius:4px;font-size:11px;background:#fef3c7;color:#92400e;' + style + '">' + (el.prefix || '') + 'Apartamento' + (el.suffix || '') + '</span>';
                } else {
                    var sample = el.id.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
                    html += '<span class="upt-builder-preview-el" data-builder-preview="' + el.id + '" style="' + display + style + '">' + (el.prefix || '') + sample + (el.suffix || '') + '</span>';
                }
            }
            $sitePreviewBody.html(html);
        }

        $saveBtn.on('click', function() {
            var dashFields = [];
            jQuery('[name="upt_card_dashboard_fields[]"]:checked').each(function() {
                dashFields.push(jQuery(this).val());
            });

            var builderData = getBuilderData();

            $savedMsg.removeClass('is-visible');
            $errorMsg.removeClass('is-visible').text('');
            $saveBtn.prop('disabled', true).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:upt-spin 1s linear infinite" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Salvando...');

            jQuery.post(ajaxUrl, {
                action: 'upt_save_card_settings',
                nonce: nonce,
                dashboard_fields: dashFields,
                builder_data: JSON.stringify(builderData)
            }, function(resp) {
                $saveBtn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Salvar Configura\u00E7\u00F5es');
                if (resp.success) {
                    $savedMsg.addClass('is-visible');
                    if (savedTimer) clearTimeout(savedTimer);
                    savedTimer = setTimeout(function() {
                        $savedMsg.removeClass('is-visible');
                    }, 3000);
                } else {
                    $errorMsg.text('Erro: ' + (resp.data && resp.data.message ? resp.data.message : 'n\u00E3o foi poss\u00EDvel salvar.')).addClass('is-visible');
                }
            }).fail(function() {
                $saveBtn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Salvar Configura\u00E7\u00F5es');
                $errorMsg.text('Erro de conex\u00E3o. Verifique sua internet e tente novamente.').addClass('is-visible');
            });
        });
    })();
})();
