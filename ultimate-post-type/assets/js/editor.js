jQuery(document).ready(function($) {

    var insertPlaceholder = function(event) {
        var widgetEditor = $(event.target).closest('.elementor-widget-content');
        var select = $(event.target);
        var textarea = widgetEditor.find('textarea[data-setting="whatsapp_message"]');
        var placeholder = select.val();

        if (placeholder && textarea.length) {
            var cursorPos = textarea.prop('selectionStart');
            var text = textarea.val();
            var newText = text.substring(0, cursorPos) + placeholder + text.substring(cursorPos);
            
            textarea.val(newText).trigger('input');
            
            select.val('');
            select.find('option:first').prop('selected', true);
        }
    };

    elementor.hooks.addAction('panel/open_editor/widget/upt_listing_grid', function(panel, model, view) {
        panel.$el.on('change', 'select[data-setting="placeholder_inserter"]', insertPlaceholder);
    });
    
    elementor.hooks.addAction('panel/open_editor/widget/upt_whatsapp_button', function(panel, model, view) {
        panel.$el.on('change', 'select[data-setting="placeholder_inserter"]', insertPlaceholder);
    });


    // Atualiza dinamicamente os selects de detecção (grades e filtros) no painel do widget upt Category Filter.
    function uptGetPreviewRoot() {
        var $iframe = jQuery('#elementor-preview-iframe');
        if ($iframe.length && $iframe[0].contentDocument) {
            return jQuery($iframe[0].contentDocument);
        }
        if (window.elementor && elementor.$previewContents) {
            return elementor.$previewContents;
        }
        return null;
    }

    function uptCollectDetectedTargets(excludeId) {
        excludeId = (excludeId || '').toString();
        var $doc = uptGetPreviewRoot();
        var filters = [];
        var grids = [];

        if (!$doc) {
            return { filters: filters, grids: grids };
        }

        $doc.find('.upt-category-filter-wrapper[id]').each(function () {
            var $el = jQuery(this);
            var id = ($el.attr('id') || '').toString();
            if (!id || id === excludeId) return;

            var role = ($el.data('filter-role') || '').toString();
            var roleLabel = role ? (' ' + role) : '';
            filters.push({ id: id, label: 'Filtro' + roleLabel + ' (' + id + ')' });
        });

        $doc.find('[id^="upt-grid-"]').each(function () {
            var $el = jQuery(this);
            var id = ($el.attr('id') || '').toString();
            if (!id) return;
            grids.push({ id: id, label: 'Grade (' + id + ')' });
        });

        // Ordena para estabilidade
        filters.sort(function (a, b) { return a.label.localeCompare(b.label); });
        grids.sort(function (a, b) { return a.label.localeCompare(b.label); });

        return { filters: filters, grids: grids };
    }

    function uptSetSelectOptions($select, items) {
        if (!$select || !$select.length) return;
        var currentVal = ($select.val() || '').toString();

        $select.empty();
        $select.append(jQuery('<option/>', { value: '', text: '— Selecione —' }));
        (items || []).forEach(function (it) {
            $select.append(jQuery('<option/>', { value: it.id, text: it.label }));
        });

        // tenta manter valor
        if (currentVal && $select.find('option[value="' + currentVal + '"]').length) {
            $select.val(currentVal);
        } else {
            $select.val('');
        }

        // dispara input para o Elementor sincronizar o setting
        $select.trigger('input');
    }

    var uptLastCategoryFilterPanel = null;
    var uptLastCategoryFilterModelId = null;

    function uptRefreshCategoryFilterPanel(panel, model) {
        try {
            var widgetId = model && model.get ? model.get('id') : '';
            var excludeId = widgetId ? ('upt-filter-' + widgetId) : '';
            var detected = uptCollectDetectedTargets(excludeId);

            var $panel = panel && panel.$el ? panel.$el : jQuery('.elementor-panel');
            if (!$panel || !$panel.length) return;

            uptSetSelectOptions($panel.find('select[data-setting="target_grid_auto"]'), detected.grids);
            uptSetSelectOptions($panel.find('select[data-setting="target_filter_auto"]'), detected.filters);
            uptSetSelectOptions($panel.find('select[data-setting="parent_filter_auto"]'), detected.filters);

            uptLastCategoryFilterPanel = $panel;
            uptLastCategoryFilterModelId = widgetId || null;
        } catch (e) {}
    }

    elementor.hooks.addAction('panel/open_editor/widget/upt_category_filter', function(panel, model, view) {
        // Atualiza imediatamente ao abrir o painel
        uptRefreshCategoryFilterPanel(panel, model);

        // Atualiza quando o preview recarrega (ex.: ao adicionar widgets)
        elementor.channels.data.off('upt:refreshTargets');
        elementor.channels.data.on('upt:refreshTargets', function() {
            uptRefreshCategoryFilterPanel(panel, model);
        });
    });

    // Dispara refresh quando um elemento é adicionado/removido no documento (deve atualizar os selects sem recarregar a página)
    if (elementor && elementor.channels && elementor.channels.data) {
        var debounced = null;
        var triggerRefresh = function () {
            if (debounced) clearTimeout(debounced);
            debounced = setTimeout(function () {
                if (uptLastCategoryFilterPanel && uptLastCategoryFilterModelId) {
                    elementor.channels.data.trigger('upt:refreshTargets');
                }
            }, 250);
        };

        elementor.channels.data.on('element:after:add', triggerRefresh);
        elementor.channels.data.on('element:after:remove', triggerRefresh);
    }

});
