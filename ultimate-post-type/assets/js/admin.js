jQuery(document).ready(function($) {
    function toggleFieldOptions() {
        var fieldType = $('#field_type').val();
        var optionsField = $('.field-options-wrapper');
        var relationshipField = $('.field-relationship-wrapper');
        var timeField = $('.field-time-wrapper');
        var unitMeasureField = $('.field-unit-measure-wrapper');
        var multipleField = $('.field-multiple-wrapper');
        var taxonomyField = $('.field-taxonomy-wrapper');
        var maxLengthField = $('.field-maxlength-wrapper');
        var rowsField = $('.field-rows-wrapper');
        var blogPostField = $('.field-blog-post-wrapper');

        // Esconde todos por padrão
        optionsField.hide();
        relationshipField.hide();
        timeField.hide();
        unitMeasureField.hide();
        multipleField.hide();
        taxonomyField.hide();
        maxLengthField.hide();
        rowsField.hide();
        blogPostField.hide();

        if (fieldType === 'select') {
            optionsField.show();
        } else if (fieldType === 'relationship') {
            relationshipField.show();
        } else if (fieldType === 'time') {
            timeField.show();
        } else if (fieldType === 'unit_measure') {
            unitMeasureField.show();
        } else if (fieldType === 'taxonomy') {
            multipleField.show();
            taxonomyField.show();
        }

        // Campos de texto (nativos e customizados)
        if (fieldType === 'core_title' || fieldType === 'core_content' || fieldType === 'textarea' || fieldType === 'text' || fieldType === 'url' || fieldType === 'list') {
            maxLengthField.show();
        }

        // Campos de múltiplas linhas
        if (fieldType === 'core_content' || fieldType === 'textarea' || fieldType === 'list') {
            rowsField.show();
        }

        if (fieldType === 'blog_post') {
            blogPostField.show();
        }
    }

    // Executa quando a página carrega
    toggleFieldOptions();

    // Executa quando o tipo de campo muda
    $('body').on('change', '#field_type', toggleFieldOptions);

    // Reordenar campos dos esquemas (drag & drop)
    var $fieldsTable = $('.upt-fields-table tbody');
    if ($fieldsTable.length && typeof $.fn.sortable === 'function') {
        $fieldsTable.sortable({
            axis: 'y',
            cursor: 'move',
            handle: 'td:first-child',
            placeholder: 'upt-sortable-placeholder',
            update: function () {
                var order = [];
                $fieldsTable.find('tr.upt-field-row').each(function () {
                    var id = $(this).data('field-id');
                    if (id) {
                        order.push(id);
                    }
                });

                var $table = $('.upt-fields-table').first();
                var schemaSlug = $table.data('schema');
                var nonce = $table.data('nonce');

                if (!schemaSlug || !order.length) {
                    return;
                }

                $.post(ajaxurl, {
                    action: 'upt_reorder_fields',
                    schema_slug: schemaSlug,
                    order: order,
                    nonce: nonce
                });
            }
        }).disableSelection();
    }

    // Reordenar itens dos esquemas (drag & drop)
    var $itemsOrderTable = $('.upt-items-table tbody');
    if ($itemsOrderTable.length && typeof $.fn.sortable === 'function') {
        $itemsOrderTable.sortable({
            axis: 'y',
            cursor: 'move',
            handle: '.upt-drag-handle',
            placeholder: 'upt-sortable-placeholder',
            update: function () {
                var order = [];
                $itemsOrderTable.find('tr.upt-item-row').each(function () {
                    var id = $(this).data('item-id');
                    if (id) {
                        order.push(id);
                    }
                });

                var $table = $('.upt-items-table').first();
                var schemaSlug = $table.data('schema');
                var nonce = $table.data('nonce');

                if (!schemaSlug || !order.length) {
                    return;
                }

                $.post(ajaxurl, {
                    action: 'upt_reorder_items',
                    schema_slug: schemaSlug,
                    order: order,
                    nonce: nonce
                }).fail(function() {
                    window.alert('Erro ao salvar a nova ordem dos itens.');
                });
            }
        }).disableSelection();
    }



    // Renomear esquema a partir do ícone de lápis (AJAX)
    $('.upt-schema-list').on('click', '.upt-schema-rename', function(e) {
        e.preventDefault();

        var $btn       = $(this);
        var schemaSlug = $btn.data('schema-slug');
        var schemaName = $btn.data('schema-name');
        var nonce      = $('#upt_rename_schema_nonce').val();

        if (!schemaSlug || !nonce) {
            return;
        }

        var newName = window.prompt('Novo nome para o esquema:', schemaName || '');
        if (!newName) {
            return;
        }

        newName = newName.trim();
        if (!newName || newName === schemaName) {
            return;
        }

        $.post(ajaxurl, {
            action: 'upt_rename_schema',
            schema_slug: schemaSlug,
            schema_new_name: newName,
            _wpnonce: nonce
        }).done(function(response) {
            if (!response || !response.success || !response.data) {
                var msg = (response && response.data && response.data.message) ? response.data.message : 'Erro ao renomear o esquema.';
                window.alert(msg);
                return;
            }

            var data         = response.data;
            var newSlug      = data.new_slug || schemaSlug;
            var newNameFinal = data.new_name || newName;

            // Atualiza texto do link do esquema
            var $li    = $btn.closest('li');
            var $link  = $li.find('a').first();
            $link.text(newNameFinal);

            // Atualiza dados do botão e link de excluir
            $btn.data('schema-slug', newSlug);
            $btn.data('schema-name', newNameFinal);

            if (data.delete_url) {
                var $deleteLink = $li.find('a.delete-link');
                if ($deleteLink.length) {
                    $deleteLink.attr('href', data.delete_url);
                }
            }

            // Atualiza o título "Campos para"
            var $header = $('.upt-builder__main h2').first();
            if ($header.length) {
                $header.text('Campos para "' + newNameFinal + '"');
            }

            // Atualiza o parâmetro ?schema= na URL, sem recarregar
            if (window.history && window.history.replaceState) {
                try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('schema', newSlug);
                    window.history.replaceState({}, '', url.toString());
                } catch (err) {
                    // Silenciosamente ignora se URL() não estiver disponível
                }
            }

        }).fail(function() {
            window.alert('Erro de comunicação ao renomear o esquema.');
        });
    });


});


(function($){
    $(function(){
        var $body = $('body');
        var isuptList = $body.hasClass('post-type-catalog_item') || $body.hasClass('taxonomy-catalog_category') || $body.hasClass('taxonomy-catalog_schema');
        if (!isuptList) return;

        var $navs = $('.tablenav');
        if (!$navs.length) return;

        var url = new URL(window.location.href);
        var isShowAll = url.searchParams.get('upt_show_all') === '1';

        function buildUrl(showAll){
            var u = new URL(window.location.href);
            if (showAll){
                u.searchParams.set('upt_show_all','1');
            } else {
                u.searchParams.delete('upt_show_all');
            }
            return u.toString();
        }

        $navs.each(function(){
            var $nav = $(this);
            var $pages = $nav.find('.tablenav-pages');
            if (!$pages.length) return;

            // avoid duplicate
            if ($nav.find('.upt-show-all-toggle').length) return;

            var label = isShowAll ? 'Voltar à paginação' : 'Mostrar todos';
            var targetUrl = buildUrl(!isShowAll);

            var $btn = $('<a/>', {
                'class': 'button upt-show-all-toggle',
                'href': targetUrl,
                'text': label
            });

            // Insert before pagination
            $pages.prepend($btn);
        });
    });
})(jQuery);
