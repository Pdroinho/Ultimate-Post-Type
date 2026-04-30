jQuery(document).ready(function ($) {

    /* ========================================================================
     * SECTION 1: UTILITIES
     * Alert config, debug logging, AJAX helpers, char counters, confirm
     * dialog, notifications, URL query params, slugify
     * ======================================================================== */

    // =========================
    // upt Alert Badge
    // =========================
    function uptGetAlertConfig() {
        var $wrap = $('.upt-dashboard-wrapper, .upt-alert-config').first();
        if (!$wrap.length) { return { enabled: false }; }
        var dur = parseFloat($wrap.data('upt-alert-duration'));
        if (isNaN(dur)) { dur = 4; }
        return {
            enabled: $wrap.data('upt-alert-enabled') == 1,

            onCreate: $wrap.data('upt-alert-create') == 1,
            onEdit: $wrap.data('upt-alert-edit') == 1,
            onDelete: $wrap.data('upt-alert-delete') == 1,
            onDraft: $wrap.data('upt-alert-draft') == 1,
            onLogin: $wrap.data('upt-alert-login') == 1,

            onSchemaQty: $wrap.data('upt-alert-schema-qty') == 1,

            onMediaDeleted: $wrap.data('upt-alert-media-deleted') == 1,
            onMediaUploaded: $wrap.data('upt-alert-media-uploaded') == 1,
            onMediaMoved: $wrap.data('upt-alert-media-moved') == 1,

            onCategoryCreated: $wrap.data('upt-alert-category-created') == 1,
            onCategoryRenamed: $wrap.data('upt-alert-category-renamed') == 1,
            onCategoryDeleted: $wrap.data('upt-alert-category-deleted') == 1,

            durationSec: dur
        };
    }
        // Debug helper for item-limit visibility issues
        function uptDebugLogLimits(message, payload) {
            if (!window.uptDebugLimits) { return; }
            try {
                console.log('[upt][limit]', message, payload || {});
            } catch (e) {}
        }



    function uptDebugLog(label, data) {
        try {
            if (window.console && console.log) {
                return;
            }
        } catch (e) { }
    }

    function uptGetAjaxConfig() {
        try {
            var cfg = (typeof upt_ajax !== 'undefined') ? upt_ajax : null;
            var ajaxUrl = cfg && cfg.ajax_url ? cfg.ajax_url : ((typeof window.ajaxurl === 'string') ? window.ajaxurl : '/wp-admin/admin-ajax.php');
            var adminUrl = cfg && cfg.admin_url ? cfg.admin_url : ajaxUrl.replace('admin-ajax.php', '');
            return { ajax_url: ajaxUrl, admin_url: adminUrl, nonce: (cfg && cfg.nonce) ? cfg.nonce : '' };
        } catch (e) {
            return { ajax_url: '/wp-admin/admin-ajax.php', admin_url: '/wp-admin/', nonce: '' };
        }
    }

    // Evita ReferenceError cedo no carregamento caso upt_ajax não venha localizado
    if (typeof window.upt_ajax === 'undefined') {
        window.upt_ajax = uptGetAjaxConfig();
    }

    function uptEnsureAlertHost() {
        var $host = $('body').children('.upt-alert-host').first();
        if ($host.length) { return $host; }

        $host = $('<div class="upt-alert-host" aria-live="polite" aria-relevant="additions text"></div>');
        $('body').append($host);
        return $host;
    }

    function uptCharCounterLen(value) {
        try {
            return String(value || '').length;
        } catch (e) {
            return 0;
        }
    }

    function uptEnsureCharCounter(fieldEl) {
        var $field = fieldEl && fieldEl.jquery ? fieldEl : $(fieldEl);
        if (!$field.length) {
            return;
        }

        var max = parseInt($field.attr('maxlength'), 10);
        if (!max || max <= 0) {
            return;
        }

        var type = ($field.attr('type') || '').toLowerCase();
        if (type === 'hidden' || type === 'checkbox' || type === 'radio' || type === 'file') {
            return;
        }

        if ($field.data('uptCharCounterInit')) {
            return;
        }

        var $counter = $('<div class="upt-char-counter" aria-hidden="true"></div>');
        $field.after($counter);
        $field.data('uptCharCounterInit', true);
        $field.data('uptCharCounterEl', $counter);
        uptUpdateCharCounter($field);
    }

    function uptUpdateCharCounter(fieldEl) {
        var $field = fieldEl && fieldEl.jquery ? fieldEl : $(fieldEl);
        if (!$field.length) {
            return;
        }

        var max = parseInt($field.attr('maxlength'), 10);
        if (!max || max <= 0) {
            return;
        }

        var $counter = $field.data('uptCharCounterEl');
        if (!$counter || !$counter.length) {
            $counter = $field.nextAll('.upt-char-counter').first();
            if ($counter.length) {
                $field.data('uptCharCounterEl', $counter);
            }
        }

        if (!$counter || !$counter.length) {
            return;
        }

        var len = uptCharCounterLen($field.val());
        $counter.text(String(len) + '/' + String(max));
    }

    function uptInitCharCounters(root) {
        var $root = root && root.jquery ? root : $(root || document);
        $root.find('input[maxlength], textarea[maxlength]').each(function () {
            uptEnsureCharCounter(this);
        });
    }

    $(document).on('focus input', 'input[maxlength], textarea[maxlength]', function () {
        uptEnsureCharCounter(this);
        uptUpdateCharCounter(this);
    });

    uptInitCharCounters(document);

    try {
        if (window.MutationObserver) {
            var uptCharCounterObserver = new MutationObserver(function (mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    var m = mutations[i];
                    if (!m.addedNodes || !m.addedNodes.length) {
                        continue;
                    }
                    for (var j = 0; j < m.addedNodes.length; j++) {
                        var node = m.addedNodes[j];
                        if (!node || node.nodeType !== 1) {
                            continue;
                        }
                        if (node.matches && (node.matches('input[maxlength]') || node.matches('textarea[maxlength]'))) {
                            uptEnsureCharCounter(node);
                            continue;
                        }
                        var $node = $(node);
                        if ($node.find) {
                            uptInitCharCounters($node);
                        }
                    }
                }
            });
            uptCharCounterObserver.observe(document.body, { childList: true, subtree: true });
        }
    } catch (e) { }

    function uptConfirmDialog(message, onConfirm, options) {
        options = options || {};
        // Simple confirm modal (evita prompt nativo do navegador)
        var $existing = $('#upt-confirm-overlay');
        if ($existing.length) { $existing.remove(); }

        var $overlay = $('<div id="upt-confirm-overlay" class="upt-confirm-overlay" role="dialog" aria-modal="true"></div>');
        var $panel = $('<div class="upt-confirm-panel"></div>');
        var $msg = $('<p class="upt-confirm-message"></p>').text(message || 'Confirmar ação?');
        var $actions = $('<div class="upt-confirm-actions"></div>');
        var $cancel = $('<button type="button" class="upt-confirm-cancel">Cancelar</button>');
        var $ok = $('<button type="button" class="upt-confirm-ok">Excluir</button>');

        $actions.append($cancel, $ok);

        $panel.append($msg);

        if (options.extraContent) {
            $panel.append(options.extraContent);
        }

        $panel.append($actions);
        $overlay.append($panel);
        $('body').append($overlay);

        function close() { $overlay.remove(); $(document).off('keydown.uptConfirm'); }

        $cancel.on('click', function () { close(); });
        $overlay.on('click', function (e) {
            if (e.target === this) { close(); }
        });
        $(document).on('keydown.uptConfirm', function (e) {
            if (e.key === 'Escape') { close(); }
        });

        $ok.on('click', function () {
            var confirmData = (typeof options.onBeforeConfirm === 'function') ? options.onBeforeConfirm() : undefined;
            close();
            try { if (typeof onConfirm === 'function') { onConfirm(confirmData); } } catch (e) { }
        });
    }



    function uptShowAlertBadge(message) {
        var cfg = uptGetAlertConfig();
        if (!cfg || !cfg.enabled) { return; }

        var $host = uptEnsureAlertHost();

        var $badge = $('<div class="upt-alert-badge" role="status"></div>');
        var $p = $('<p></p>').text(message || 'Ação realizada.');
        var $btn = $('<button type="button" class="upt-alert-close" aria-label="Fechar aviso"></button>');

        $btn.html(
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">' +
            '<path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
            '<path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
            '</svg>'
        );

        $btn.on('click', function () {
            $badge.addClass('is-hiding');
            setTimeout(function () { $badge.remove(); }, 250);
        });

        $badge.append($p).append($btn);
        $host.append($badge);

        // stack: limita a 5 badges visíveis
        var maxStack = 5;
        var $all = $host.find('.upt-alert-badge');
        if ($all.length > maxStack) {
            $all.first().remove();
        }

        setTimeout(function () { $badge.addClass('is-visible'); }, 10);

        // auto-hide (0 = não auto-fecha)
        var durMs = Math.max(0, (cfg.durationSec || 0) * 1000);
        if (durMs > 0) {
            setTimeout(function () {
                if ($badge && $badge.length) {
                    $badge.addClass('is-hiding');
                    setTimeout(function () { $badge.remove(); }, 250);
                }
            }, durMs);
        }
    }

    function uptNotify(type, message) {
        var cfg = uptGetAlertConfig();
        if (!cfg || !cfg.enabled) { return; }

        if (type === 'create' && !cfg.onCreate) { return; }
        if (type === 'edit' && !cfg.onEdit) { return; }
        if (type === 'delete' && !cfg.onDelete) { return; }
        if (type === 'draft' && !cfg.onDraft) { return; }
        if (type === 'login' && !cfg.onLogin) { return; }

        if (type === 'schema_qty' && !cfg.onSchemaQty) { return; }

        if (type === 'media_deleted' && !cfg.onMediaDeleted) { return; }
        if (type === 'media_uploaded' && !cfg.onMediaUploaded) { return; }
        if (type === 'media_moved' && !cfg.onMediaMoved) { return; }

        if (type === 'category_created' && !cfg.onCategoryCreated) { return; }
        if (type === 'category_renamed' && !cfg.onCategoryRenamed) { return; }
        if (type === 'category_deleted' && !cfg.onCategoryDeleted) { return; }

        uptShowAlertBadge(message);
    }

    function uptReadQueryParam(name) {
        try { return (new URLSearchParams(window.location.search)).get(name); }
        catch (e) { return null; }
    }

    function uptRemoveQueryParam(name) {
        try {
            var url = new URL(window.location.href);
            url.searchParams.delete(name);
            window.history.replaceState({}, document.title, url.toString());
        } catch (e) { }
    }

    function uptSlugify(value) {
        if (value === null || typeof value === 'undefined') {
            return '';
        }
        try {
            return value.toString()
                .normalize('NFD')
                .replace(/\p{Diacritic}/gu, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        } catch (e) {
            return (value + '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    }

    // Expor helpers para outros scripts (ex.: galeria admin)
    window.uptShowAlertBadge = uptShowAlertBadge;
    window.uptGetAlertConfig = uptGetAlertConfig;
    window.uptNotify = uptNotify;
    window.uptConfirmDialog = uptConfirmDialog;


    (function () {
        var v = uptReadQueryParam('upt_alert');
        if (v === 'login_success') {
            uptNotify('login', 'Login realizado com sucesso!');
            uptRemoveQueryParam('upt_alert');
        }
    })();

    /* ========================================================================
     * SECTION 2: INPUT VALIDATION & SANITIZATION
     * Search debounce, number/price input sanitization
     * ======================================================================== */

    var uptSearchTimeout;
    var uptSearchDelay = 250; // debounce curto para buscas
    var uptActiveRequests = {}; // controla requisições simultâneas por grid

    var uptCurrentDraftContext = null;

    // Restrições de entrada para campos numéricos e de preço
    function uptSanitizeNumberValue(value) {
        return value.replace(/[^\d]/g, '');
    }

    function uptSanitizePriceValue(value) {
        return value.replace(/[^0-9.,]/g, '');
    }

    $(document).on('input', '.upt-number-input', function () {
        var val = $(this).val() || '';
        var clean = uptSanitizeNumberValue(val);
        if (val !== clean) {
            $(this).val(clean);
        }
    });

    $(document).on('input', '.upt-price-input', function () {
        var val = $(this).val() || '';
        var clean = uptSanitizePriceValue(val);
        if (val !== clean) {
            $(this).val(clean);
        }
    });



    /* ========================================================================
     * SECTION 3: UI FIXES & MEDIA VALIDATION
     * CSS hard fixes, modal focus trap, media required tooltips, WYSIWG
     * ======================================================================== */

    // 1. CORREÇÃO DE INTERFACE E EDITOR
    // ==============================================================
    $(document).off('focusin.modal focusin.bs.modal');
    $(document).on('focusin', function (e) {
        if ($(e.target).closest(".upt-editor-wrapper").length) return;
        if ($(e.target).closest(".tox-tinymce, .tox-tinymce-aux").length) e.stopImmediatePropagation();
    });

    var cssFix = `<style id="upt-hard-fixes">
        .tox-tinymce-aux { z-index: 99999999 !important; position: fixed !important; }
        #upt-gallery-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.75); z-index: 2147483647; align-items: center; justify-content: center; }
        #upt-gallery-modal-content { width: 90%; height: 90%; background: #fff; position: relative; border-radius: 8px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        #upt-gallery-close-btn { position: absolute; top: 0; right: 0; width: 40px; height: 40px; background: #d63638; color: #fff; text-align: center; line-height: 40px; font-size: 20px; cursor: pointer; z-index: 100; }
        body.fc-modal-open { overflow: hidden !important; }
    
        .upt-help-link {
            margin-left: 12px;
            font-size: 14px;
            text-decoration: underline;
            cursor: pointer;
        }
        .upt-help-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 2147483647;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .upt-help-modal.is-open {
            display: flex;
        }
        .upt-help-modal-inner {
            background: #000;
            max-width: 960px;
            width: 100%;
            max-height: 90vh;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 18px 40px rgba(0,0,0,0.4);
        }
        .upt-help-modal-inner iframe,
        .upt-help-modal-inner video {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
        }
        .upt-help-close {
            position: absolute;
            top: 8px;
            right: 12px;
            background: transparent;
            border: 0;
            color: #fff;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
            z-index: 2;
        }
        .upt-help-modal-overlay {
            position: absolute;
            inset: 0;
            background: transparent;
        }
</style>`;
    if ($('#upt-hard-fixes').length === 0) $('head').append(cssFix);

    if ($('#upt-wysiwyg-required-fix').length === 0) {
        $('head').append('<style id="upt-wysiwyg-required-fix">.upt-editor-wrapper{position:relative;}.upt-editor-content{position:relative;z-index:2;}.upt-wysiwyg-hidden-native{position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;pointer-events:none;resize:none;z-index:1;}</style>');
    }


    if ($('#upt-media-required-style').length === 0) {
        $('head').append('<style id="upt-media-required-style">.upt-media-field-has-error{position:relative;} .upt-required-tooltip{position:absolute;left:0;bottom:-26px;background:#000;color:#fff;padding:6px 10px;border-radius:4px;font-size:12px;white-space:normal;max-width:260px;z-index:99999;box-shadow:0 2px 6px rgba(0,0,0,0.3);}</style>');
    }

    function uptShowMediaRequiredTooltips(mediaFields, fallbackMessage) {
        $('.upt-required-tooltip').remove();
        $('.upt-media-field-has-error').removeClass('upt-media-field-has-error');

        if (!Array.isArray(mediaFields) || !mediaFields.length) {
            if (fallbackMessage) {
                alert('Erro ao salvar: ' + (fallbackMessage || 'Ocorreu um erro desconhecido.'));
            }
            return;
        }

        var firstOffsetTop = null;

        mediaFields.forEach(function (field) {
            var fieldId = field.id || field.field_id || '';
            var label = field.label || '';
            if (!fieldId) return;

            var $hidden = $('[name="' + fieldId + '"]').first();
            if (!$hidden.length) {
                // Suporte ao padrão name="upt_fields[field_id]"
                $hidden = $('[name="upt_fields[' + fieldId + ']"]').first();
            }
            if (!$hidden.length) return;

            var $fieldWrapper = $hidden.closest('.upt-image-upload-wrapper, .upt-video-upload-wrapper, .upt-gallery-wrapper, p');
            if (!$fieldWrapper.length) {
                $fieldWrapper = $hidden.parent();
            }

            $fieldWrapper.addClass('upt-media-field-has-error');

            var text = label ? 'Preencha o campo "' + label + '".' : (fallbackMessage || 'Preencha os campos obrigatórios.');
            var $tooltip = $('<div class="upt-required-tooltip"></div>').text(text);

            $fieldWrapper.append($tooltip);

            if (firstOffsetTop === null) {
                firstOffsetTop = $fieldWrapper.offset().top;
            }
        });

        // Remove tooltips ao clicar em qualquer lugar da tela,
        // imitando o comportamento dos tooltips nativos do navegador.
        $(document)
            .off('click.uptMediaTooltipDismiss')
            .on('click.uptMediaTooltipDismiss', function (e) {
                $('.upt-media-field-has-error').removeClass('upt-media-field-has-error');
                $('.upt-required-tooltip').remove();
                $(document).off('click.uptMediaTooltipDismiss');
            });

        if (firstOffsetTop !== null) {
            var $firstErrorWrapper = $('.upt-media-field-has-error').first();
            if ($firstErrorWrapper.length) {
                var element = $firstErrorWrapper.get(0);
                if (element && typeof element.scrollIntoView === 'function') {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    $('html, body').animate({ scrollTop: firstOffsetTop - 120 }, 400);
                }

                var $focusTarget = $firstErrorWrapper.find('input, select, textarea, button').first();
                if ($focusTarget.length) {
                    $focusTarget.trigger('focus');
                }
            } else {
                $('html, body').animate({ scrollTop: firstOffsetTop - 120 }, 400);
            }
        }
    }



    function uptValidateMediaRequiredClientSide($form) {
        // Limpa estados anteriores
        $('.upt-required-tooltip').remove();
        $('.upt-media-field-has-error').removeClass('upt-media-field-has-error');

        var hasError = false;
        var mediaFields = [];

        $form.find('.upt-image-upload-wrapper, .upt-video-upload-wrapper, .upt-gallery-wrapper').each(function () {
            var $wrapper = $(this);
            var $input = $wrapper.find('.upt-image-id-input, .upt-video-id-input, .gallery-ids-input').first();
            if (!$input.length) {
                return;
            }

            var isRequired = $input.prop('required');

            if (!isRequired) {
                var inputId = $input.attr('id') || '';
                if (inputId) {
                    var $label = $form.find('label[for="' + inputId + '"]').first();
                    if ($label.length && $label.text().indexOf('*') !== -1) {
                        isRequired = true;
                    }
                }
            }

            if (!isRequired) {
                return;
            }

            var value = ($input.val() || '').toString().trim();
            if (!value) {
                hasError = true;

                var fieldName = $input.attr('name') || '';
                var labelText = '';

                var inputId2 = $input.attr('id') || '';
                if (inputId2) {
                    var $label2 = $form.find('label[for="' + inputId2 + '"]').first();
                    if ($label2.length) {
                        labelText = $.trim(
                            $label2.text()
                                .replace('*', '')
                                .replace(':', '')
                        );
                    }
                }

                mediaFields.push({
                    id: fieldName,
                    label: labelText
                });
            }
        });

        if (hasError) {
            uptShowMediaRequiredTooltips(mediaFields, '');
            return false;
        }

        return true;
    }

    $(document).on('change', '.upt-image-id-input, .upt-video-id-input, .gallery-ids-input', function () {
        var $fieldWrapper = $(this).closest('.upt-image-upload-wrapper, .upt-video-upload-wrapper, .upt-gallery-wrapper, p');
        $fieldWrapper.removeClass('upt-media-field-has-error');
        $fieldWrapper.find('.upt-required-tooltip').remove();
    })

    /* ========================================================================
     * SECTION 4: WYSIWYG EDITOR
     * Simple rich text editor with toolbar, source toggle, block select
     * ======================================================================== */

    var currentEditorInstance = null;
    var savedRange = null;

    function inituptSimpleEditor(root) {
        var $root = (root && root.jquery) ? root : $(root || document);
        $root.find('.upt-submit-form .upt-wysiwyg-textarea').each(function () {
            var $textarea = $(this);
            if ($textarea.closest('.upt-editor-wrapper').length > 0) return;

            var $wrapper = $('<div class="upt-editor-wrapper"></div>');
            var $toolbar = $('<div class="upt-editor-toolbar"></div>');
            var $visualEditor = $('<div class="upt-editor-content" contenteditable="true" placeholder="Escreva aqui..."></div>');
            var $codeEditor = $textarea.clone().addClass('upt-editor-code').show().removeAttr('id');
            $textarea.addClass('upt-wysiwyg-hidden-native');

            var icons = {
                bold: '<svg viewBox="0 0 24 24"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path></svg>',
                italic: '<svg viewBox="0 0 24 24"><line x1="19" y1="4" x2="10" y2="4"></line><line x1="14" y1="20" x2="5" y2="20"></line><line x1="15" y1="4" x2="9" y2="20"></line></svg>',
                underline: '<svg viewBox="0 0 24 24"><path d="M6 3v7a6 6 0 0 0 6 6 6 6 0 0 0 6-6V3"></path><line x1="4" y1="21" x2="20" y2="21"></line></svg>',
                ul: '<svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>',
                ol: '<svg viewBox="0 0 24 24"><line x1="10" y1="6" x2="21" y2="6"></line><line x1="10" y1="12" x2="21" y2="12"></line><line x1="10" y1="18" x2="21" y2="18"></line><path d="M4 6h1v4"></path><path d="M4 10h2"></path><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"></path></svg>',
                left: '<svg viewBox="0 0 24 24"><line x1="17" y1="10" x2="3" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="17" y1="18" x2="3" y2="18"></line></svg>',
                center: '<svg viewBox="0 0 24 24"><line x1="18" y1="10" x2="6" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="18" y1="18" x2="6" y2="18"></line></svg>',
                right: '<svg viewBox="0 0 24 24"><line x1="21" y1="10" x2="7" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="21" y1="18" x2="7" y2="18"></line></svg>',
                link: '<svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>',
                image: '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>',
                code: '<svg viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>',
                chevronUp: '<svg viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"></polyline></svg>',
                chevronDown: '<svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>'
            };

            var fontMap = [10, 13, 16, 18, 24, 32, 48];
            var currentFontIndex = 2;
            var $fontDisplay = null;

            function toggleSourceMode($visualEditor, $codeEditor, $btn) {
                if (!$visualEditor || !$codeEditor) { return; }
                var isSource = $codeEditor.is(':visible');
                if (isSource) {
                    // Volta para o modo visual
                    var html = $codeEditor.val();
                    $visualEditor.html(html);
                    $visualEditor.show();
                    $codeEditor.hide();
                    if ($btn && $btn.length) { $btn.removeClass('active-mode'); }
                } else {
                    // Vai para o modo HTML
                    var currentHtml = $visualEditor.html();
                    $codeEditor.val(currentHtml);
                    $codeEditor.show();
                    $visualEditor.hide();
                    if ($btn && $btn.length) { $btn.addClass('active-mode'); }
                }
            }

            var $blockSelect = null;

            function uptSyncBlockSelectClass($select) {
                if (!$select || !$select.length) { return; }
                var val = String($select.val() || '').toLowerCase();
                if (!val) { val = 'p'; }
                if (['p','h1','h2','h3','h4','h5','h6','pre'].indexOf(val) === -1) {
                    val = 'p';
                }

                try {
                    $select.removeClass(function (idx, className) {
                        return (String(className || '').match(/\bupt-editor-select--\S+/g) || []).join(' ');
                    });
                } catch (e) {}
                $select.addClass('upt-editor-select--' + val);
            }

            var controls = [
                { type: 'select', width: '110px', options: [{ val: 'p', text: 'Normal' }, { val: 'h1', text: 'Título 1' }, { val: 'h2', text: 'Título 2' }, { val: 'h3', text: 'Título 3' }, { val: 'h4', text: 'Título 4' }, { val: 'h5', text: 'Título 5' }, { val: 'h6', text: 'Título 6' }, { val: 'pre', text: 'Código' }], command: 'formatBlock' },
                { type: 'separator' },
                { type: 'stepper' },
                { type: 'separator' },
                { label: icons.bold, title: 'Negrito', command: 'bold' },
                { label: icons.italic, title: 'Itálico', command: 'italic' },
                { label: icons.underline, title: 'Sublinhado', command: 'underline' },
                { type: 'separator' },
                { label: icons.ul, title: 'Lista', command: 'insertUnorderedList' },
                { label: icons.ol, title: 'Numérica', command: 'insertOrderedList' },
                { type: 'separator' },
                { label: icons.left, title: 'Esq.', command: 'justifyLeft' },
                { label: icons.center, title: 'Cen.', command: 'justifyCenter' },
                { label: icons.right, title: 'Dir.', command: 'justifyRight' },
                { type: 'separator' },
                { label: icons.link, title: 'Link', custom: 'link' },
                { label: icons.image, title: 'Mídia', custom: 'media' },
                { type: 'separator' },
                { label: icons.code, title: 'HTML', custom: 'source' }
            ];

            controls.forEach(function (ctrl) {
                if (ctrl.type === 'separator') {
                    $toolbar.append('<div class="upt-editor-separator"></div>');
                } else if (ctrl.type === 'select') {
                    var $wrap = $('<div class="upt-select-wrapper"></div>');
                    var $sel = $('<select class="upt-editor-select"></select>');
                    if (ctrl.width) $sel.css('min-width', ctrl.width);
                    ctrl.options.forEach(function (o) { $sel.append('<option value="' + o.val + '">' + o.text + '</option>'); });
                    if (ctrl.command === 'formatBlock') {
                        $blockSelect = $sel;
                        uptSyncBlockSelectClass($sel);
                    }

                    $sel.on('change', function () {
                        document.execCommand(ctrl.command, false, $(this).val());
                        $visualEditor.focus();
                        if (ctrl.command === 'fontSize') $(this).val('3');
                        if (ctrl.command === 'formatBlock') uptSyncBlockSelectClass($(this));
                    });
                    $wrap.append($sel); $toolbar.append($wrap);
                } else if (ctrl.type === 'stepper') {
                    var $stepper = $('<div class="upt-fontsize-stepper" title="Tamanho da Fonte"></div>');
                    var $display = $('<input type="text" class="upt-fontsize-display" value="16" readonly>');
                    $fontDisplay = $display;
                    var $btns = $('<div class="upt-fontsize-controls"></div>');
                    var $up = $('<button type="button" class="upt-fontsize-btn">' + icons.chevronUp + '</button>');
                    var $down = $('<button type="button" class="upt-fontsize-btn">' + icons.chevronDown + '</button>');
                    function updateFont(newIndex) {
                        if (newIndex < 0) newIndex = 0; if (newIndex > 6) newIndex = 6;
                        currentFontIndex = newIndex; $display.val(fontMap[currentFontIndex]);
                        document.execCommand('fontSize', false, currentFontIndex + 1); $visualEditor.focus();
                    }
                    $up.on('click', function (e) { e.preventDefault(); updateFont(currentFontIndex + 1); });
                    $down.on('click', function (e) { e.preventDefault(); updateFont(currentFontIndex - 1); });
                    $btns.append($up).append($down); $stepper.append($display).append($btns); $toolbar.append($stepper);
                } else {
                    // Adicionamos o 'data-command' aqui para poder verificar o estado depois
                    var $btn = $('<button type="button" class="upt-editor-btn" title="' + (ctrl.title || '') + '" data-command="' + (ctrl.command || '') + '">' + ctrl.label + '</button>');
                    $btn.on('click', function (e) {
                        e.preventDefault();
                        if (ctrl.custom === 'source') {
                            toggleSourceMode($visualEditor, $codeEditor, $btn);
                        } else if (ctrl.custom === 'link') {
                            var url = prompt('URL:', 'https://'); if (url) document.execCommand('createLink', false, url);
                        } else if (ctrl.custom === 'media') {
                            var sel = window.getSelection(); savedRange = (sel.getRangeAt && sel.rangeCount) ? sel.getRangeAt(0) : null;
                            currentEditorInstance = $visualEditor;
                            openuptGallery($wrapper, 'editor');
                        } else {
                            document.execCommand(ctrl.command, false, null);
                            // Não alternamos classe aqui manualmente, deixamos o 'checkActiveState' cuidar disso
                        }
                        if (ctrl.custom !== 'source' && ctrl.custom !== 'media') $visualEditor.focus();
                    });
                    $toolbar.append($btn);
                }
            });

            $textarea.wrap($wrapper);
            $wrapper = $textarea.parent();
            $wrapper.append($toolbar).append($visualEditor).append($codeEditor);
            $visualEditor.html($textarea.val()); $codeEditor.val($textarea.val());

            // Sincronização
            $visualEditor.on('input blur keyup', function () { var v = $(this).html(); $textarea.val(v); $codeEditor.val(v); });
            $codeEditor.on('input blur keyup', function () { var v = $(this).val(); $textarea.val(v); $visualEditor.html(v); });

            // --- VERIFICADOR DE ESTADO DOS BOTÕES (NOVO) ---
            // Isso garante que Lista, Negrito, etc. acendam/apaguem corretamente
            var checkActiveState = function () {
                $toolbar.find('button[data-command]').each(function () {
                    var cmd = $(this).data('command');
                    if (!cmd) return;
                    try {
                        // Pergunta ao navegador se o comando está ativo no cursor atual
                        if (document.queryCommandState(cmd)) {
                            $(this).addClass('active-mode');
                        } else {
                            $(this).removeClass('active-mode');
                        }
                    } catch (e) { }
                });
            };

            var updateFontDisplayFromBlock = function (block) {
                if (!$fontDisplay || !$fontDisplay.length || !block) { return; }
                try {
                    var size = window.getComputedStyle(block).fontSize;
                    if (size) {
                        var px = parseFloat(size);
                        if (!isNaN(px) && isFinite(px)) {
                            $fontDisplay.val(Math.round(px));
                            // Ajusta o índice do stepper para o valor mais próximo
                            var closestIdx = 0;
                            var closestDiff = Infinity;
                            for (var i = 0; i < fontMap.length; i++) {
                                var diff = Math.abs(fontMap[i] - px);
                                if (diff < closestDiff) {
                                    closestDiff = diff;
                                    closestIdx = i;
                                }
                            }
                            currentFontIndex = closestIdx;
                        }
                    }
                } catch (e) { }
            };

            var syncBlockSelect = function () {
                if (!$blockSelect || !$blockSelect.length) { return; }
                try {
                    var sel = window.getSelection();
                    if (!sel || sel.rangeCount === 0) { return; }
                    var node = sel.anchorNode || sel.focusNode;
                    if (!node) { return; }
                    if (node.nodeType === 3) { node = node.parentNode; }
                    if (!node || !node.closest) { return; }
                    var block = node.closest('p,h1,h2,h3,h4,h5,h6,pre,div');
                    var tag = (block && block.tagName) ? block.tagName.toLowerCase() : 'p';
                    if (tag === 'div') { tag = 'p'; }
                    if ($blockSelect.val() !== tag) {
                        $blockSelect.val(tag);
                    }
                    uptSyncBlockSelectClass($blockSelect);
                    updateFontDisplayFromBlock(block);
                } catch (e) { }
            };

            // Executa verificação ao clicar ou digitar
            $visualEditor.on('mouseup keyup click input blur', function () {
                checkActiveState();
                syncBlockSelect();
            });
            $toolbar.on('click', 'button', function () { setTimeout(function(){ checkActiveState(); syncBlockSelect(); }, 10); });
            $visualEditor.on('focus', syncBlockSelect);
        });
    }


    /* ========================================================================
     * SECTION 5: DRAFT SYSTEM
     * Draft context, collect/apply/save/clear drafts, media preview refresh
     * ======================================================================== */

    function uptGetDraftContextFromForm($form) {
        if (!$form || !$form.length) { return null; }
        var schemaSlug = $.trim($form.find('input[name="schema_slug"]').val() || '');
        var itemId = parseInt($form.find('input[name="item_id"]').val() || 0, 10) || 0;
        if (!schemaSlug) { return null; }
            var nonce = $form.find('input[name="upt_submit_nonce"]').val() || '';
            return { schema_slug: schemaSlug, item_id: itemId, nonce: nonce };
    }

    function uptCollectDraftFields($form) {
        var data = {};
        if (!$form || !$form.length) { return data; }

        $form.find('input, textarea, select').each(function () {
            var $field = $(this);
            var name = $field.attr('name');

            if (!name) { return; }
            if (name === 'upt_submit_nonce' || name === 'schema_slug' || name === 'item_id') { return; }

            var type = ($field.attr('type') || '').toLowerCase();

            if ((type === 'checkbox' || type === 'radio') && !$field.is(':checked')) {
                return;
            }

            var value = $field.val();

            // Normaliza arrays (ex: campos com [])
            if (name.slice(-2) === '[]') {
                if (!data[name]) { data[name] = []; }
                if ($.isArray(value)) {
                    data[name] = data[name].concat(value);
                } else if (value !== null && value !== undefined && value !== '') {
                    data[name].push(value);
                }
            } else {
                data[name] = value;
            }
        });

        return data;
    }

    function uptApplyDraftFields($form, fields) {
        if (!$form || !$form.length || !fields) { return; }

        $.each(fields, function (name, value) {
            var $fields = $form.find('[name="' + name + '"]');
            if (!$fields.length) { return; }

            var isArray = $.isArray(value);

            $fields.each(function () {
                var $field = $(this);
                var type = ($field.attr('type') || '').toLowerCase();

                if (type === 'checkbox' || type === 'radio') {
                    var fieldVal = $field.val();
                    var shouldCheck = isArray ? (value.indexOf(fieldVal) !== -1) : (String(value) === String(fieldVal));
                    $field.prop('checked', !!shouldCheck);
                } else if ($field.is('select')) {
                    $field.val(value).trigger('change');
                } else {
                    if (isArray) {
                        $field.val(value.length ? value[0] : '');
                    } else {
                        $field.val(value);
                    }
                }
            });
        });

        // Após aplicar os valores nos campos nativos, sincroniza os editores WYSIWYG
        // para garantir que o conteúdo de rascunho apareça também na interface visual.
        $form.find('.upt-wysiwyg-textarea').each(function () {
            var $textarea = jQuery(this);
            var currentValue = $textarea.val();
            var $wrapper = $textarea.next('.upt-editor-wrapper');

            if (!$wrapper.length) {
                $wrapper = $textarea.closest('.upt-editor-wrapper');
            }
            if (!$wrapper.length) { return; }

            var $visual = $wrapper.find('.upt-editor-content').first();
            var $codeEditor = $wrapper.find('.upt-editor-code').first();

            if ($visual.length) {
                $visual.html(currentValue);
            }
            if ($codeEditor.length) {
                $codeEditor.val(currentValue);
            }
        });
    }

    function uptSaveDraftForForm($form) {
        var ctx = uptGetDraftContextFromForm($form);
        if (!ctx || !ctx.nonce) { return; }

        var fields = uptCollectDraftFields($form);
        // Se não houver nenhum valor, não precisa salvar
        var hasAnyValue = false;
        $.each(fields, function (key, value) {
            if (value !== null && value !== undefined && String(value) !== '') {
                hasAnyValue = true;
                return false;
            }
        });
        if (!hasAnyValue) { return; }
        $.ajax({
            url: upt_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'upt_save_draft',
                upt_submit_nonce: ctx.nonce,
                schema_slug: ctx.schema_slug,
                item_id: ctx.item_id,
                fields: fields
            },
            success: function (resp) {
                if (resp && resp.success) {
                    uptNotify('draft', (resp.data && resp.data.message) ? resp.data.message : 'Rascunho salvo!');
                }
            }
        });
    }

    function uptExtractFilename(url) {
        if (!url) { return ''; }
        try {
            var clean = String(url).split('?')[0].split('#')[0];
            var parts = clean.split('/');
            return parts.pop() || '';
        } catch (e) { return ''; }
    }

    function uptEnsureTitleHasExtension(title, url) {
        title = title || '';
        url = url || '';

        var filename = uptExtractFilename(url);
        var ext = '';

        if (filename && filename.indexOf('.') !== -1) {
            ext = filename.split('.').pop();
        }

        if (!ext && title.indexOf('.') !== -1) {
            ext = title.split('.').pop();
        }

        if (!title && filename) {
            return filename;
        }

        if (ext) {
            var suffix = '.' + String(ext).toLowerCase();
            var lowerTitle = title.toLowerCase();
            if (lowerTitle.slice(-suffix.length) !== suffix) {
                return title + suffix;
            }
        }

        return title;
    }
    function uptRefreshMediaPreviews($form) {
        if (!$form || !$form.length || typeof upt_ajax === 'undefined') { return; }

        var idsMap = {};
        var ids = [];

        function collectId(rawId) {
            if (!rawId) { return; }
            var id = $.trim(String(rawId));
            if (!id) { return; }
            if (!idsMap[id]) {
                idsMap[id] = true;
                ids.push(id);
            }
        }

        // Coleta IDs de imagens únicas
        $form.find('.upt-image-upload-wrapper .upt-image-id-input').each(function () {
            collectId($(this).val());
        });

        // Coleta IDs de vídeos únicos
        $form.find('.upt-video-upload-wrapper .upt-video-id-input').each(function () {
            collectId($(this).val());
        });

        // Coleta IDs de galerias (múltiplos IDs separados por vírgula)
        $form.find('.upt-gallery-wrapper .gallery-ids-input').each(function () {
            var raw = $(this).val() || '';
            if (!raw) { return; }
            raw.split(',').forEach(function (part) {
                collectId(part);
            });
        });

        if (!ids.length) { return; }

        $.ajax({
            url: upt_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'upt_get_media_by_ids',
                nonce: upt_ajax.nonce,
                ids: ids
            },
            success: function (response) {
                if (!response || !response.success || !response.data || !response.data.items) {
                    return;
                }
                var items = response.data.items;
                var map = {};
                $.each(items, function (_, item) {
                    map[String(item.id)] = item;
                });

                // Atualiza previews de imagem única
                $form.find('.upt-image-upload-wrapper').each(function () {
                    var wrapper = $(this);
                    var input = wrapper.find('.upt-image-id-input');
                    if (!input.length) { return; }
                    var id = $.trim(input.val() || '');
                    if (!id || !map[id]) { return; }

                    var data = map[id];
                    var previewWrapper = wrapper.find('.image-preview-wrapper');
                    var removeButton = wrapper.find('.upt-remove-image');

                    var fullUrl = data.full_url || data.thumbnail_url || '';
                    var thumbUrl = data.thumbnail_url || '';
                    var isVideo = /\.(mp4|webm|ogg|mov|m4v)$/i.test(fullUrl) || (data.type === 'video');
                                    var isPdf = (data.type === 'pdf') || (/\.pdf$/i.test(fullUrl));
                    var overlaySrc = (typeof upt_ajax !== 'undefined' && upt_ajax.transparent_png)
                        ? upt_ajax.transparent_png
                        : thumbUrl;

                    var thumbHtml;
                    if (isVideo) {
                        thumbHtml =
                            '<div class="upt-video-thumb">' +
                            '<video muted playsinline preload="metadata" src="' + fullUrl + '#t=1"></video>' +
                            '<img class="upt-video-overlay" src="' + overlaySrc + '" alt="">' +
                            '</div>';
                    } else {
                        thumbHtml = '<img src="' + thumbUrl + '">';
                    }

                    previewWrapper.html(thumbHtml);

                    var mediaTitle = uptEnsureTitleHasExtension(
                        data && (data.filename || data.name || ''),
                        fullUrl || thumbUrl
                    );
                    if (mediaTitle) {
                        var $titleBadge = $('<div class="upt-media-title"></div>')
                            .text(mediaTitle)
                            .attr('title', mediaTitle);
                        previewWrapper.append($titleBadge);
                    }

                    if (removeButton.length) {
                        removeButton.removeClass('hidden');
                    }
                });

                // Atualiza previews de vídeo único
                $form.find('.upt-video-upload-wrapper').each(function () {
                    var wrapper = $(this);
                    var input = wrapper.find('.upt-video-id-input');
                    if (!input.length) { return; }
                    var id = $.trim(input.val() || '');
                    if (!id || !map[id]) { return; }

                    var data = map[id];
                    var previewWrapper = wrapper.find('.video-preview-wrapper');
                    var removeButton = wrapper.find('.upt-remove-video');

                    var fullUrl = data.full_url || data.thumbnail_url || '';
                    var overlaySrc = (typeof upt_ajax !== 'undefined' && upt_ajax.transparent_png)
                        ? upt_ajax.transparent_png
                        : (data.thumbnail_url || '');
                    var thumbHtml =
                        '<div class="upt-video-thumb">' +
                        '<video muted playsinline preload="metadata" src="' + fullUrl + '#t=1"></video>' +
                        '<img class="upt-video-overlay" src="' + overlaySrc + '" alt="">' +
                        '</div>';

                    previewWrapper.html(thumbHtml);

                    var mediaTitle = uptEnsureTitleHasExtension(
                        data && (data.filename || data.name || ''),
                        fullUrl
                    );
                    if (mediaTitle) {
                        var $titleBadge = $('<div class="upt-media-title"></div>')
                            .text(mediaTitle)
                            .attr('title', mediaTitle);
                        previewWrapper.append($titleBadge);
                    }

                    if (removeButton.length) {
                        removeButton.removeClass('hidden');
                    }
                });

                // Atualiza previews de galerias
                $form.find('.upt-gallery-wrapper').each(function () {
                    var wrapper = $(this);
                    var input = wrapper.find('.gallery-ids-input');
                    var previews = wrapper.find('.gallery-previews');
                    if (!input.length || !previews.length) { return; }

                    var raw = (input.val() || '').split(',').filter(Boolean);
                    if (!raw.length) { return; }

                    previews.empty();

                    raw.forEach(function (id) {
                        id = String(id);
                        var data = map[id];
                        if (!data) { return; }

                        var fullUrl = data.full_url || data.thumbnail_url || '';
                        var thumbUrl = data.thumbnail_url || '';
                        var isVideo = /\.(mp4|webm|ogg|mov|m4v)$/i.test(fullUrl) || (data.type === 'video');
                                    var isPdf = (data.type === 'pdf') || (/\.pdf$/i.test(fullUrl));
                        var overlaySrc = (typeof upt_ajax !== 'undefined' && upt_ajax.transparent_png)
                            ? upt_ajax.transparent_png
                            : thumbUrl;

                        var thumbHtml;
                        if (isVideo) {
                            var poster = thumbUrl || overlaySrc || fullUrl;
                            thumbHtml =
                                '<div class="upt-video-thumb">' +
                                '<video muted playsinline preload="metadata" src="' + fullUrl + '#t=1"' + (poster ? ' poster="' + poster + '"' : '') + '></video>' +
                                '<img class="upt-video-overlay" src="' + overlaySrc + '" alt="">' +
                                '</div>';
                        } else {
                            thumbHtml = '<img src="' + thumbUrl + '">';
                        }

                        var itemClass = 'gallery-preview-item' + (isVideo ? ' gallery-video-item' : '');

                        previews.append(
                            '<div class="' + itemClass + '" data-id="' + id + '">' +
                            thumbHtml +
                            '<a href="#" class="remove-image">×</a>' +
                            '</div>'
                        );
                    });
                });
            }
        });
    }


    function uptClearDraftForContext(ctx) {
        if (!ctx || !ctx.nonce) { return; }
        $.ajax({
            url: upt_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'upt_clear_draft',
                upt_submit_nonce: ctx.nonce,
                schema_slug: ctx.schema_slug,
                item_id: ctx.item_id
            }
        });
    }

    function uptInitDraftForForm($form) {
        var ctx = uptGetDraftContextFromForm($form);
        if (!ctx) { return; }

        uptCurrentDraftContext = ctx;

        // Carrega rascunho existente, se houver
            $.ajax({
                url: upt_ajax.ajax_url,
                type: 'POST',
            dataType: 'json',
            data: {
                action: 'upt_get_draft',
                upt_submit_nonce: ctx.nonce,
                schema_slug: ctx.schema_slug,
                item_id: ctx.item_id
            },
            success: function (response) {
                if (response && response.success && response.data && response.data.has_draft && response.data.fields) {
                    uptApplyDraftFields($form, response.data.fields);
                    uptRefreshMediaPreviews($form);
                }
            }
        });

        // Salva rascunho ao fechar o modal
        $('body').off('click.uptDraftClose');
        $('body').on('click.uptDraftClose', '#upt-modal-close, #upt-modal-wrapper', function (e) {
            if (e.target !== this) { return; }
            uptSaveDraftForForm($form);
        });
    }

    // Limpa rascunho após salvar o item
    $(document).on('upt_items_list_updated', function () {
        if (uptCurrentDraftContext) {
            uptClearDraftForContext(uptCurrentDraftContext);
            uptCurrentDraftContext = null;
        }
    });


    // Reconstrói o mapa de seleção global a partir dos campos já preenchidos,
    // garantindo persistência mesmo após recarregar a página ou acessar em outro navegador.
    // Rebuild map once media helpers are available (moved near definition below)

    /* ========================================================================
     * SECTION 6: LISTING & SEARCH
     * AJAX search, pagination, load more, grid layout, item rendering
     * ======================================================================== */

    function performuptSearch(triggerElement, page, options) {
        options = options || {};
        var appendMode = options.append === true;
        var loadingButton = options.loadingButton || null;
        var onComplete = typeof options.onComplete === 'function' ? options.onComplete : null;
        var forceClear = options.forceClear === true;
        var useStoredSearch = options.useStoredSearch === true; // usado pela paginação para reutilizar termo/targets atuais
        clearTimeout(uptSearchTimeout);
        page = page || 1;

        uptSearchTimeout = setTimeout(function () {
            if (!triggerElement || !triggerElement.length) {
                return;
            }

            var targetID = triggerElement.data('target-id');
            if (!targetID) return;

            var searchWrapper = $('.upt-search-wrapper[data-target-id="' + targetID + '"]').first();
            var categoryWrapper = $('.upt-category-filter-wrapper[data-target-id="' + targetID + '"]').not('[data-filter-role="parent"]').first();

            var searchTerm = searchWrapper.length ? searchWrapper.find('.upt-search-input').val() : '';
            var searchTargets = [];
            if (searchWrapper.length) {
                try {
                    var t = searchWrapper.data('targets');
                    if (Array.isArray(t)) {
                        searchTargets = t;
                    } else if (typeof t === 'string' && t.length) {
                        searchTargets = t.split(',').map(function (s) { return (s || '').trim(); }).filter(Boolean);
                    }
                    if (!searchTargets.length) {
                        var hiddenVal = searchWrapper.find('input[name="upt_target"]').val();
                        if (hiddenVal) {
                            searchTargets = hiddenVal.split(',').map(function (s) { return (s || '').trim(); }).filter(Boolean);
                        }
                    }
                } catch (e) {}
            }

            var listingWrapper = $('#' + targetID).closest('.upt-listing-wrapper');
            if (!listingWrapper.length) {
                listingWrapper = $('#' + targetID).parent();
            }
            if (!listingWrapper.length && categoryWrapper.length) {
                // Grid ID pode estar errado ou vazio; tenta usar o wrapper mais próximo do filtro.
                listingWrapper = categoryWrapper.closest('.upt-listing-wrapper');
            }
            if (!listingWrapper.length && searchWrapper.length) {
                listingWrapper = searchWrapper.closest('.upt-listing-wrapper');
            }
            if (!listingWrapper.length) return;

            // Se o filtro não foi encontrado pelo target, tenta localizar dentro do listing.
            if (!categoryWrapper.length && listingWrapper.length) {
                categoryWrapper = listingWrapper.find('.upt-category-filter-wrapper').not('[data-filter-role="parent"]').first();
            }

            // Se o wrapper de busca não foi encontrado pelo target, tenta localizar dentro do listing.
            if (!searchWrapper.length && listingWrapper.length) {
                searchWrapper = listingWrapper.find('.upt-search-wrapper').first();
                if (searchWrapper.length) {
                    searchTerm = searchWrapper.find('.upt-search-input').val() || '';
                    isSearchCleared = (typeof searchTerm === 'string' && searchTerm.trim() === '');
                    searchTargets = [];
                    try {
                        var t2 = searchWrapper.data('targets');
                        if (Array.isArray(t2)) {
                            searchTargets = t2;
                        } else if (typeof t2 === 'string' && t2.length) {
                            searchTargets = t2.split(',').map(function (s) { return (s || '').trim(); }).filter(Boolean);
                        }
                        if (!searchTargets.length) {
                            var hiddenVal2 = searchWrapper.find('input[name="upt_target"]').val();
                            if (hiddenVal2) {
                                searchTargets = hiddenVal2.split(',').map(function (s) { return (s || '').trim(); }).filter(Boolean);
                            }
                        }
                    } catch (e) {}
                }
            }

            var explicitClear = forceClear || (searchWrapper.length && searchWrapper.find('.upt-search-input').length && (searchWrapper.find('.upt-search-input').val() === ''));
            var searchInputEmpty = searchWrapper.length && $.trim(searchWrapper.find('.upt-search-input').val() || '') === '';

            // Quando o usuário limpou o campo (forceClear), não reutiliza termo/targets anteriores.
            if (explicitClear) {
                listingWrapper.removeAttr('data-upt-last-term');
                listingWrapper.removeAttr('data-upt-last-targets');
                searchTargets = [];
            }

            // Fallback para termo/targets armazenados apenas quando solicitado (paginação) e não for clear.
            if (useStoredSearch && (!searchTerm || !searchTerm.length) && !explicitClear) {
                var lastTerm = listingWrapper.attr('data-upt-last-term') || '';
                if (lastTerm) { searchTerm = lastTerm; }
            }
            if (useStoredSearch && (!searchTargets || !searchTargets.length) && !explicitClear) {
                var lastTargets = listingWrapper.attr('data-upt-last-targets') || '';
                if (lastTargets) {
                    searchTargets = lastTargets.split(',').map(function (s) { return (s || '').trim(); }).filter(Boolean);
                }
            }

            var searchSlug = uptSlugify(searchTerm || '');
            var termId = '0';
            if (categoryWrapper.length) {
                var dropdown = categoryWrapper.find('.upt-category-filter-dropdown');
                if (dropdown.length) {
                    termId = dropdown.val();
                    if (termId === '-1' || termId === '' || termId === null) {
                        termId = '0';
                    }
                } else {
                    var activeListItem = categoryWrapper.find('.upt-category-filter-item.active');
                    termId = activeListItem.length ? activeListItem.data('term-id') : '0';
                }
            }

            // Fallback: se estiver vazio/0 mas há um valor persistido, usa-o.
            if ((!termId || termId === '0') && listingWrapper.length) {
                var storedTerm = listingWrapper.attr('data-upt-active-term');
                if (storedTerm !== undefined && storedTerm !== null && storedTerm !== '') {
                    termId = String(storedTerm);
                }
            }

            // Sempre persiste o termId resolvido para que a próxima chamada (ex.: paginação) o reutilize.
            if (listingWrapper.length) {
                listingWrapper.attr('data-upt-active-term', String(termId || '0'));
            }

            var metaFilterKey = '';
            var metaFilterVal = '';
            var metaPill = listingWrapper.find('.upt-meta-filter-pill.active').first();
            if (metaPill.length) {
                metaFilterKey = metaPill.data('meta-field') || '';
                metaFilterVal = metaPill.data('meta-value') || '';
            } else {
                metaFilterKey = listingWrapper.attr('data-upt-active-meta-key') || '';
                metaFilterVal = listingWrapper.attr('data-upt-active-meta-val') || '';
            }
            if (listingWrapper.length) {
                listingWrapper.attr('data-upt-active-meta-key', metaFilterKey);
                listingWrapper.attr('data-upt-active-meta-val', metaFilterVal);
            }

            var loopContainer = listingWrapper.find('.elementor-loop-container');
            if (!loopContainer.length) {
                // Fallback: usa o próprio targetID ou o wrapper se a classe não estiver presente.
                loopContainer = $('#' + targetID);
                if (!loopContainer.length) {
                    loopContainer = listingWrapper;
                }
            }
            var paginationContainer = listingWrapper.find('.upt-pagination-wrapper');
            var postsPerPage = listingWrapper.data('posts-per-page');
            var schemaFilter = loopContainer.data('schema-filter');
            var templateId = loopContainer.data('template-id');
            var paginationType = (listingWrapper.data('pagination-type') || 'numbers').toString();
            var infiniteTrigger = (listingWrapper.data('infinite-trigger') || 'button').toString();
            var isInfinite = paginationType === 'infinite';

            if (appendMode && !isInfinite) {
                appendMode = false;
            }

            if (templateId === undefined || templateId === '') {
                templateId = triggerElement.data('template-id');
            }

            if (!templateId) {
                // Fallback: quando não há template (layout manual), recarrega a página com os filtros na URL.
                try {
                    var currentUrl = window.location.href.split('?')[0];
                    var params = new URLSearchParams(window.location.search);

                    params.delete('paged');
                    params.delete('page');

                    if (searchTerm && searchTerm.length) {
                        params.set('s_upt', searchTerm);
                    } else {
                        params.delete('s_upt');
                    }

                    if (Array.isArray(searchTargets) && searchTargets.length) {
                        params.set('upt_target', searchTargets.join(','));
                    } else {
                        params.delete('upt_target');
                    }

                    if (termId && termId !== '0') {
                        params.set('upt_category', termId);
                    } else {
                        params.delete('upt_category');
                    }

                    if (metaFilterKey && metaFilterVal) {
                        params.set('upt_meta_key', metaFilterKey);
                        params.set('upt_meta_filter', metaFilterVal);
                    } else {
                        params.delete('upt_meta_key');
                        params.delete('upt_meta_filter');
                    }

                    var qs = params.toString();
                    var newUrl = qs ? currentUrl + '?' + qs : currentUrl;
                    window.location.href = newUrl;
                } catch (err) {
                    console.error('upt Error: Template ID not found and fallback navigation failed.', err);
                }
                return;
            }

            // Se já houver uma requisição em andamento para este grid, aborta para usar o valor mais recente digitado.
            if (uptActiveRequests[targetID] && uptActiveRequests[targetID].readyState !== 4) {
                try { uptActiveRequests[targetID].abort(); } catch (abortErr) {}
            }

            if (listingWrapper.length) {
                listingWrapper.attr('data-upt-loading', '1');
            }

            var currentRequest = $.ajax({
                url: upt_ajax.ajax_url,
                type: 'post',
                data: {
                    action: 'upt_live_search',
                    nonce: upt_ajax.nonce,
                    query: searchTerm,
                    term_id: termId,
                    template_id: templateId,
                    schema_slugs: schemaFilter,
                    paged: page,
                    posts_per_page: postsPerPage,
                    pagination_type: paginationType,
                    infinite_trigger: infiniteTrigger,
                    show_arrows: listingWrapper.data('show-arrows'),
                    load_more_text: listingWrapper.data('load-more-text'),
                    search_targets: searchTargets,
                    search_slug: searchSlug,
                    meta_filter_key: metaFilterKey,
                    meta_filter_val: metaFilterVal
                },
                beforeSend: function () {
                    listingWrapper.css('opacity', 0.5);
                },
                success: function (response) {
                    if (response.success) {
                        var html = response.data.html || '';
                        if (appendMode && isInfinite) {
                            if (html.trim() !== '') {
                                loopContainer.append(html);
                            }
                        } else if (html.trim() !== '') {
                            loopContainer.html(html);
                        } else {
                            var emptyMessage = '<p style="width: 100%; text-align: center; padding: 20px;">Nenhum item encontrado.</p>';
                            loopContainer.html(emptyMessage);
                        }
                        if (paginationContainer.length) {
                            paginationContainer.html(response.data.pagination || '');
                        }
                        listingWrapper.attr('data-upt-last-term', searchTerm || '');
                        listingWrapper.attr('data-upt-last-targets', (searchTargets || []).join(','));
                        // Rebind infinite scroll sentinel if needed
                        inituptInfinite(listingWrapper);
                    } else {
                        loopContainer.html('<p>Ocorreu um erro ao carregar os itens.</p>')
                    }
                },
                error: function (jqXHR, textStatus) {
                    if (textStatus === 'abort') { return; }
                    console.error('upt Error: AJAX request failed.');
                },
                complete: function () {
                    listingWrapper.css('opacity', 1);
                    if (loadingButton && loadingButton.length) {
                        loadingButton.prop('disabled', false).removeClass('is-loading');
                    }
                    if (listingWrapper.length) {
                        listingWrapper.removeAttr('data-upt-loading');
                    }
                    if (onComplete) { onComplete(); }
                    delete uptActiveRequests[targetID];
                }
            });

            uptActiveRequests[targetID] = currentRequest;
        }, uptSearchDelay);
    }

    $('body').on('click', '.upt-search-button', function (e) {
        var wrapper = $(this).closest('.upt-search-wrapper');
        if (wrapper.data('behavior') === 'ajax') {
            e.preventDefault(); // Previne o submit do form em modo AJAX
            performuptSearch(wrapper, 1);
        }
        // Se for 'link', o HTML já cuida do submit do form, então não fazemos nada.
    });

    $('body').on('keyup', '.upt-search-input', function (e) {
        var wrapper = $(this).closest('.upt-search-wrapper');
        if (wrapper.data('behavior') === 'ajax') {
            var isEmpty = $.trim($(this).val() || '') === '';
            performuptSearch(wrapper, 1, { forceClear: isEmpty });
        }
    });

    $('body').on('change', '.upt-search-input', function (e) {
        var wrapper = $(this).closest('.upt-search-wrapper');
        if (wrapper.data('behavior') === 'ajax') {
            var isEmpty = $.trim($(this).val() || '') === '';
            performuptSearch(wrapper, 1, { forceClear: isEmpty });
        }
    });

    $('body').on('click', '.upt-category-filter-item', function (e) {
        var clickedItem = $(this);
        var wrapper = clickedItem.closest('.upt-category-filter-wrapper');
        var behavior = wrapper.data('behavior');
        var role = String(wrapper.data('filter-role') || 'grid');

        if (behavior === 'link') {
            return;
        }

        e.preventDefault();
        wrapper.find('.upt-category-filter-item').removeClass('active');
        clickedItem.addClass('active');

if (role === 'parent') {
    uptHandleParentSelection(wrapper, String(clickedItem.data('term-id') || '0'));
    return;
}
// Persiste term id selecionado no wrapper da listagem para uso posterior (ex.: paginação).
        var listingWrapper = $('#' + wrapper.data('target-id')).closest('.upt-listing-wrapper');
        if (!listingWrapper.length) {
            listingWrapper = wrapper.closest('.upt-listing-wrapper');
        }
        if (listingWrapper.length) {
            listingWrapper.attr('data-upt-active-term', String(clickedItem.data('term-id') || '0'));
        }

        performuptSearch(wrapper, 1);
    });

    $('body').on('click', '.upt-meta-filter-pill', function (e) {
        e.preventDefault();
        var $pill = $(this);
        var gridId = $pill.data('grid-id');
        if (!gridId) return;

        $pill.closest('.upt-builtin-filter-list').find('.upt-meta-filter-pill').removeClass('active');
        $pill.addClass('active');

        var listingWrapper = $('#' + gridId).closest('.upt-listing-wrapper');
        if (!listingWrapper.length) {
            listingWrapper = $('#' + gridId).parent();
        }
        if (!listingWrapper.length) return;
        listingWrapper.attr('data-upt-active-meta-key', $pill.data('meta-field') || '');
        listingWrapper.attr('data-upt-active-meta-val', $pill.data('meta-value') || '');

        var triggerEl = listingWrapper.find('.upt-category-filter-wrapper').first();
        if (!triggerEl.length) {
            triggerEl = listingWrapper.find('.upt-search-wrapper').first();
        }
        if (!triggerEl.length) {
            triggerEl = listingWrapper;
        }
        performuptSearch(triggerEl, 1);
    });

    /* ========================================================================
     * SECTION 7: CATEGORY FILTERS
     * URL filter sync, parent/child category cascade, filter click handlers
     * ======================================================================== */

    function setActiveFiltersFromURL() {
        var params = new URLSearchParams(window.location.search);
        var catIdFromUrl = params.get('upt_category');
        var searchTerm = params.get('s_upt');

        $('.upt-category-filter-wrapper').each(function () {
            var $wrapper = $(this);
            var behavior = $wrapper.data('behavior');
            var role = String($wrapper.data('filter-role') || 'grid');
            var defaultTermId = String($wrapper.data('default-term-id') || '0');
            var activeId = catIdFromUrl !== null ? String(catIdFromUrl) : defaultTermId;

            var $dropdown = $wrapper.find('.upt-category-filter-dropdown');
            if ($dropdown.length) {
                if (!$dropdown.find('option[value="' + activeId + '"]').length) {
                    activeId = '0';
                }
                $dropdown.val(activeId);
            } else {
                $wrapper.find('.upt-category-filter-item').removeClass('active');
                var $targetItem = $wrapper.find('.upt-category-filter-item[data-term-id="' + activeId + '"]');
                if ($targetItem.length) {
                    $targetItem.addClass('active');
                }
            }

            if (role !== 'parent' && behavior === 'ajax' && catIdFromUrl === null && defaultTermId !== '0') {
                performuptSearch($wrapper, 1);
            }
        });

        if (searchTerm) {
            $('.upt-search-input').val(searchTerm);
        }

        var metaFilterFromUrl = params.get('upt_meta_filter');
        var metaKeyFromUrl = params.get('upt_meta_key');
        if (metaFilterFromUrl && metaKeyFromUrl) {
            $('.upt-meta-filter-pill').each(function () {
                var $pill = $(this);
                if ($pill.data('meta-field') === metaKeyFromUrl && String($pill.data('meta-value') || '') === metaFilterFromUrl) {
                    $pill.closest('.upt-builtin-filter-list').find('.upt-meta-filter-pill').removeClass('active');
                    $pill.addClass('active');
                }
            });
        }
    }
    

    // --- Parent/Child Category Filter (Pai -> Filho -> Grade) ---
    function uptNormalizeId(id) {
        id = (id || '').toString().trim();
        if (!id) return '';
        if (id.charAt(0) === '#') id = id.slice(1);
        return id;
    }

    function uptFindFilterById(id) {
        id = uptNormalizeId(id);
        if (!id) return $();
        return $('#' + id);
    }

    function uptGetSelectedTermId($wrapper) {
        if (!$wrapper || !$wrapper.length) return '0';
        var $dropdown = $wrapper.find('.upt-category-filter-dropdown').first();
        if ($dropdown.length) {
            var v = String($dropdown.val() || '0');
            if (v === '-1') v = '0';
            return v;
        }
        var $active = $wrapper.find('.upt-category-filter-item.active').first();
        if ($active.length) {
            return String($active.data('term-id') || '0');
        }
        return '0';
    }

    function uptSetChildEmptyState($child, message) {
        message = (message || String($child.data('child-empty-text') || 'Selecione uma categoria acima')).toString();
        var $dropdown = $child.find('.upt-category-filter-dropdown').first();
        if ($dropdown.length) {
            $dropdown.empty().append($('<option/>', { value: '0', text: message }));
            $dropdown.val('0');
            return;
        }
        var $list = $child.find('.upt-category-filter-list').first();
        if ($list.length) {
            $list.empty().append('<li><span class="upt-category-filter-empty">' + $('<div/>').text(message).html() + '</span></li>');
        }
    }

    function uptBuildChildUI($child, parentId, items) {
        parentId = String(parentId || '0');
        var showAll = String($child.data('child-show-all') || '0') === '1';
        var autoFirst = String($child.data('child-auto-first') || '0') === '1';

        var urlParams = new URLSearchParams(window.location.search);
        var urlCat = urlParams.get('upt_category');
        urlCat = (urlCat === null ? '' : String(urlCat));

        var desiredActive = '';
        if (urlCat) {
            desiredActive = urlCat;
        }

        var $dropdown = $child.find('.upt-category-filter-dropdown').first();
        var $list = $child.find('.upt-category-filter-list').first();

        if ($dropdown.length) {
            $dropdown.empty();

            if (showAll) {
                $dropdown.append($('<option/>', { value: parentId, text: 'Todas' }));
            }

            if (items && items.length) {
                items.forEach(function (it) {
                    $dropdown.append($('<option/>', { value: String(it.term_id), text: String(it.name) }));
                });
            } else {
                if (!showAll) {
                    $dropdown.append($('<option/>', { value: '0', text: 'Nenhuma subcategoria encontrada' }));
                }
            }

            // Resolve seleção
            var finalValue = '0';
            if (desiredActive && $dropdown.find('option[value="' + desiredActive + '"]').length) {
                finalValue = desiredActive;
            } else if (autoFirst && items && items.length) {
                finalValue = String(items[0].term_id);
            } else if (showAll) {
                finalValue = parentId;
            } else if (items && items.length) {
                finalValue = String(items[0].term_id);
            }

            $dropdown.val(finalValue);

            // Sincroniza UI custom se existir
            if (typeof uptSyncCategoryDropdownUI === 'function') {
                uptSyncCategoryDropdownUI($child);
            }

            // Se for um valor válido, filtra a grade
            if (finalValue !== '0') {
                // Persiste no listing wrapper
                var listingWrapper = $('#' + $child.data('target-id')).closest('.upt-listing-wrapper');
                if (!listingWrapper.length) listingWrapper = $child.closest('.upt-listing-wrapper');
                if (listingWrapper.length) listingWrapper.attr('data-upt-active-term', finalValue);

                performuptSearch($child, 1);
            }
            return;
        }

        if ($list.length) {
            $list.empty();

            if (showAll) {
                $list.append('<li><a href="#" class="upt-category-filter-item" data-term-id="' + parentId + '">Todas</a></li>');
            }

            if (items && items.length) {
                items.forEach(function (it) {
                    $list.append('<li><a href="#" class="upt-category-filter-item" data-term-id="' + String(it.term_id) + '">' + $('<div/>').text(String(it.name)).html() + '</a></li>');
                });
            } else {
                if (!showAll) {
                    $list.append('<li><span class="upt-category-filter-empty">Nenhuma subcategoria encontrada</span></li>');
                }
            }

            // Define ativo
            var $toActivate = $();
            if (desiredActive) {
                $toActivate = $list.find('.upt-category-filter-item[data-term-id="' + desiredActive + '"]').first();
            }
            if (!$toActivate.length) {
                if (autoFirst && items && items.length) {
                    $toActivate = $list.find('.upt-category-filter-item[data-term-id="' + String(items[0].term_id) + '"]').first();
                } else if (showAll) {
                    $toActivate = $list.find('.upt-category-filter-item[data-term-id="' + parentId + '"]').first();
                } else if (items && items.length) {
                    $toActivate = $list.find('.upt-category-filter-item[data-term-id="' + String(items[0].term_id) + '"]').first();
                }
            }
            $list.find('.upt-category-filter-item').removeClass('active');
            if ($toActivate.length) {
                $toActivate.addClass('active');
                // Persiste e filtra
                var listingWrapper2 = $('#' + $child.data('target-id')).closest('.upt-listing-wrapper');
                if (!listingWrapper2.length) listingWrapper2 = $child.closest('.upt-listing-wrapper');
                if (listingWrapper2.length) listingWrapper2.attr('data-upt-active-term', String($toActivate.data('term-id') || '0'));

                performuptSearch($child, 1);
            }

            // Aplica layout justificado (se ativo) após reconstruir a lista do filtro filho
            uptApplyJustifiedLayout($child);
        }
    }

    function uptPopulateChildCategories($child, parentId) {
        parentId = String(parentId || '0');
        if (!parentId || parentId === '0' || parentId === '-1') {
            uptSetChildEmptyState($child);
            return;
        }

        $.ajax({
            url: (upt_ajax && upt_ajax.ajax_url) ? upt_ajax.ajax_url : ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'upt_public_get_child_categories',
                nonce: (upt_ajax && upt_ajax.nonce) ? upt_ajax.nonce : '',
                parent_id: parentId
            }
        }).done(function (resp) {
            if (!resp || resp.success !== true || !resp.data || !resp.data.items) {
                uptSetChildEmptyState($child, 'Nenhuma subcategoria encontrada');
                return;
            }
            uptBuildChildUI($child, parentId, resp.data.items || []);
        }).fail(function () {
            uptSetChildEmptyState($child, 'Falha ao carregar subcategorias');
        });
    }

    function uptHandleParentSelection($parentWrapper, parentId) {
        parentId = String(parentId || '0');
        if (parentId === '-1') parentId = '0';

        // Persiste seleção do pai no listing wrapper
        var $listing = $parentWrapper.closest('.upt-listing-wrapper');
        if ($listing.length) {
            $listing.attr('data-upt-parent-term', parentId);
        }

        // 1) Se foi definido um filho manualmente, tenta usar.
        var targetChildId = uptNormalizeId(String($parentWrapper.data('target-filter-id') || ''));
        var $children = $();
        if (targetChildId) {
            $children = uptFindFilterById(targetChildId);
        }

        // 2) Caso não exista (ou esteja vazio), auto-detecta filhos dentro do mesmo listing wrapper.
        if (!$children.length) {
            if ($listing.length) {
                $children = $listing.find('.upt-category-filter-wrapper[data-filter-role="child"]');
            } else {
                // fallback global
                $children = $('.upt-category-filter-wrapper[data-filter-role="child"]');
            }
        }

        if (!$children.length) return;

        $children.each(function () {
            uptPopulateChildCategories($(this), parentId);
        });
    }

function uptInitParentChildFilters() {
    $('.upt-category-filter-wrapper[data-filter-role="child"]').each(function () {
        var $child = $(this);
        var parentIdAttr = String($child.data('parent-filter-id') || '');
        var $parent = uptFindFilterById(parentIdAttr);
        if (!$parent.length) {
            var $listing = $child.closest('.upt-listing-wrapper');
            if ($listing.length) {
                $parent = $listing.find('.upt-category-filter-wrapper[data-filter-role="parent"]').first();
            }
        }
        if (!$parent.length) {
            uptSetChildEmptyState($child);
            return;
        }
        var parentSelected = uptGetSelectedTermId($parent);
        uptPopulateChildCategories($child, parentSelected);
    });
}

setActiveFiltersFromURL();
    uptInitParentChildFilters();

    /* ========================================================================
     * SECTION 8: JUSTIFIED FILTER LAYOUT
     * Balanced two-row layout, viewport resize, category dropdown UI
     * ======================================================================== */

    // --- Alinhamento Justificado (Filtro de Categoria) ---
    // Objetivo: quando "Justificada" estiver ativo, equilibrar os itens em 2 linhas (sem deixar 1 item sozinho) e preencher a largura disponível.
    function uptGetViewportWidth() {
        return window.innerWidth || document.documentElement.clientWidth || 1200;
    }

    function uptGetCurrentFilterAlign($wrapper) {
        var w = uptGetViewportWidth();
        var desktop = String($wrapper.data('filter-align') || '');
        var tablet = String($wrapper.data('filter-align-tablet') || '');
        var mobile = String($wrapper.data('filter-align-mobile') || '');

        // Breakpoints padrão do Elementor (aprox.)
        if (w <= 767) {
            return mobile || tablet || desktop;
        }
        if (w <= 1024) {
            return tablet || desktop;
        }
        return desktop;
    }

    function uptCollectFilterItems($wrapper) {
        var items = [];
        $wrapper.find('.upt-category-filter-item').each(function () {
            var $a = $(this);
            var termId = String($a.data('term-id') || '0');
            var text = String($a.text() || '').trim();
            var href = $a.attr('href') || '#';
            items.push({
                termId: termId,
                text: text,
                href: href,
                active: $a.hasClass('active')
            });
        });
        return items;
    }

    function uptRenderFilterListNormal($list, items) {
        $list.empty();
        items.forEach(function (it) {
            var $a = $('<a/>', {
                href: it.href,
                'class': 'upt-category-filter-item' + (it.active ? ' active' : ''),
                'data-term-id': it.termId,
                text: it.text
            });
            $list.append($('<li/>').append($a));
        });
    }

    function uptRenderFilterListJustifiedTwoRows($list, items) {
        var n = items.length;
        var topCount = Math.ceil(n / 2);
        var bottomCount = n - topCount;

        // Nunca deixe 1 item sozinho (caso raro com 3 itens).
        if (bottomCount === 1 || n <= 3) {
            $list.addClass('upt-justified-one-row');
            return;
        }

        var topItems = items.slice(0, topCount);
        var bottomItems = items.slice(topCount);

        $list.empty();

        function buildRow(rowItems) {
            var $rowUL = $('<ul/>', { 'class': 'upt-justified-row-list' });
            rowItems.forEach(function (it) {
                var $a = $('<a/>', {
                    href: it.href,
                    'class': 'upt-category-filter-item' + (it.active ? ' active' : ''),
                    'data-term-id': it.termId,
                    text: it.text
                });
                $rowUL.append($('<li/>').append($a));
            });
            return $rowUL;
        }

        $list.append(
            $('<li/>', { 'class': 'upt-justified-row' }).append(buildRow(topItems))
        );
        $list.append(
            $('<li/>', { 'class': 'upt-justified-row' }).append(buildRow(bottomItems))
        );
    }

    function uptApplyJustifiedLayout($wrapper) {
        if (!$wrapper || !$wrapper.length) return;

        // Só aplica para layout "lista"
        if ($wrapper.find('.upt-category-filter-dropdown').length) {
            return;
        }

        var align = String(uptGetCurrentFilterAlign($wrapper) || '');
        var $list = $wrapper.find('.upt-category-filter-list').first();
        if (!$list.length) return;

        // Incompatível com rolagem horizontal (nowrap/scroll)
        if ($list.data('scroll-enabled')) {
            var hadJustifiedScroll = $list.hasClass('upt-justified-one-row') || $list.hasClass('upt-justified-two-rows') || $list.find('.upt-justified-row-list').length;
            $list.removeClass('upt-justified-one-row upt-justified-two-rows');
            if (hadJustifiedScroll) {
                var itemsRestoreScroll = uptCollectFilterItems($wrapper);
                if (itemsRestoreScroll && itemsRestoreScroll.length) {
                    uptRenderFilterListNormal($list, itemsRestoreScroll);
                }
            }
            return;
        }

        // Se não for justificado, garante que não fique com classes/resíduos
        if (align !== 'justified') {
            var hadJustified = $list.hasClass('upt-justified-one-row') || $list.hasClass('upt-justified-two-rows') || $list.find('.upt-justified-row-list').length;
            $list.removeClass('upt-justified-one-row upt-justified-two-rows');
            if (hadJustified) {
                var itemsRestore = uptCollectFilterItems($wrapper);
                if (itemsRestore && itemsRestore.length) {
                    uptRenderFilterListNormal($list, itemsRestore);
                }
            }
            return;
        }

        var items = uptCollectFilterItems($wrapper);
        if (!items || items.length < 2) {
            $list.removeClass('upt-justified-one-row upt-justified-two-rows');
            return;
        }

        // Captura gap atual definido via Elementor (para usar nas 2 linhas)
        try {
            var gap = window.getComputedStyle($list.get(0)).gap || '10px';
            $wrapper.get(0).style.setProperty('--upt-filter-gap', gap);
        } catch (e) {}

        // 1) Primeiro garante estrutura "normal" para medir quebra de linha
        $list.removeClass('upt-justified-one-row upt-justified-two-rows');
        uptRenderFilterListNormal($list, items);

        // Força reflow
        if ($list.get(0)) { void $list.get(0).offsetWidth; }

        var $anchors = $list.find('.upt-category-filter-item');
        if (!$anchors.length) return;

        var firstTop = $anchors.get(0).offsetTop;
        var wrapped = false;
        $anchors.each(function () {
            if (this.offsetTop !== firstTop) {
                wrapped = true;
                return false;
            }
        });

        if (!wrapped || items.length <= 3) {
            // 1 linha, preenchendo o espaço disponível
            $list.addClass('upt-justified-one-row');
            return;
        }

        // 2 linhas equilibradas
        $list.addClass('upt-justified-two-rows');
        uptRenderFilterListJustifiedTwoRows($list, items);
    }

    function uptApplyJustifiedCategoryFilters() {
        $('.upt-category-filter-wrapper').each(function () {
            uptApplyJustifiedLayout($(this));
        });
    }

    // Aplica no carregamento e no resize
    uptApplyJustifiedCategoryFilters();
    var uptJustifiedResizeTimer = null;
    $(window).on('resize.uptJustified', function () {
        clearTimeout(uptJustifiedResizeTimer);
        uptJustifiedResizeTimer = setTimeout(function () {
            uptApplyJustifiedCategoryFilters();
        }, 120);
    });


    // Dropdown custom (UI) para o filtro de categorias.
    // Mantém o <select> como fonte de verdade (compatível com Elementor e modo link).
    function uptSyncCategoryDropdownUI($wrapper) {
        var $select = $wrapper.find('.upt-category-filter-dropdown').first();
        var $ui = $wrapper.find('.upt-category-dropdown-ui').first();
        if (!$select.length || !$ui.length) { return; }

        var val = String($select.val() || '0');
        var $opt = $select.find('option[value="' + val + '"]').first();
        if (!$opt.length) { $opt = $select.find('option[value="0"]').first(); val = '0'; }

        $ui.find('.upt-category-dropdown-trigger-text').text($opt.text());
        $ui.find('.upt-category-dropdown-option').removeClass('is-active');
        $ui.find('.upt-category-dropdown-option[data-value="' + val + '"]').addClass('is-active');
    }

    function uptCloseAllCategoryDropdowns() {
        $('.upt-category-dropdown-ui').removeClass('is-open');
    }

    // Inicializa labels no carregamento
    $('.upt-category-filter-wrapper').each(function () {
        uptSyncCategoryDropdownUI($(this));
    });

    $('body').on('click', '.upt-category-dropdown-trigger', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $ui = $(this).closest('.upt-category-dropdown-ui');
        var isOpen = $ui.hasClass('is-open');
        uptCloseAllCategoryDropdowns();
        if (!isOpen) {
            $ui.addClass('is-open');
        }
    });

    $('body').on('click', '.upt-category-dropdown-option', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var value = String($btn.data('value'));
        var $wrapper = $btn.closest('.upt-category-filter-wrapper');
        var $select = $wrapper.find('.upt-category-filter-dropdown').first();
        if (!$select.length) { return; }

        $select.val(value).trigger('change');
        uptSyncCategoryDropdownUI($wrapper);
        uptCloseAllCategoryDropdowns();
    });

    $(document).on('click', function () {
        uptCloseAllCategoryDropdowns();
    });

    // Quando o select muda (AJAX/link), sincroniza a UI
    $('body').on('change.uptDropdownUISync', '.upt-category-filter-dropdown', function () {
        uptSyncCategoryDropdownUI($(this).closest('.upt-category-filter-wrapper'));
    });

    $('body').on('wheel', '.upt-category-filter-list', function (e) {
        var $list = $(this);
        if (!$list.data('scroll-enabled')) {
            return;
        }

        var overflowX = $list.css('overflow-x');
        if (overflowX !== 'auto' && overflowX !== 'scroll') {
            return;
        }

        if (this.scrollWidth <= this.clientWidth) {
            return;
        }

        e.preventDefault();
        this.scrollLeft += e.originalEvent.deltaY;
    });

    $('body').on('change', '.upt-category-filter-dropdown', function () {
        var wrapper = $(this).closest('.upt-category-filter-wrapper');
        var behavior = wrapper.data('behavior');
        var role = String(wrapper.data('filter-role') || 'grid');

        if (behavior === 'link') {
            var catId = $(this).val();
            if (catId === '-1') {
                catId = '0';
            }
            var currentUrl = window.location.href.split('?')[0];
            if (catId && catId !== '0') {
                window.location.href = currentUrl + '?upt_category=' + catId;
            } else {
                window.location.href = currentUrl;
            }
            return;
        }

        if (role === 'parent') {
            var parentId = String($(this).val() || '0');
            if (parentId === '-1') { parentId = '0'; }
            uptHandleParentSelection(wrapper, parentId);
            return;
        }

        // Persiste term id selecionado no wrapper da listagem para uso posterior (ex.: paginação).
        var listingWrapper = $('#' + wrapper.data('target-id')).closest('.upt-listing-wrapper');
        if (!listingWrapper.length) {
            listingWrapper = wrapper.closest('.upt-listing-wrapper');
        }
        if (listingWrapper.length) {
            listingWrapper.attr('data-upt-active-term', String($(this).val() || '0'));
        }

        if (wrapper.data('behavior') === 'ajax') {
            var isEmpty = $.trim($(this).val() || '') === '';
            performuptSearch(wrapper, 1, { forceClear: isEmpty });
        } else {
            performuptSearch(wrapper, 1);
        }
    });
    $('body').on('click', '.upt-listing-wrapper .upt-pagination-wrapper .page-numbers', function (e) {
        e.preventDefault();
        var link = $(this);
        if (link.hasClass('current') || link.hasClass('dots')) {
            return;
        }

        var page = 1;
        var href = link.attr('href');
        var pageMatch = href.match(/\/page\/(\d+)/) || href.match(/[?&]paged=(\d+)/);
        if (pageMatch) {
            page = parseInt(pageMatch[1], 10);
        }

        // Extract search params from pagination link to keep context intact.
        var urlParams = {};
        try {
            var u = new URL(href, window.location.origin);
            urlParams.searchTerm = u.searchParams.get('s_upt') || '';
            var tParam = u.searchParams.get('upt_target') || '';
            if (tParam) {
                urlParams.searchTargets = tParam.split(',').map(function (s) { return (s || '').trim(); }).filter(Boolean);
            } else {
                urlParams.searchTargets = [];
            }
            urlParams.termId = u.searchParams.get('upt_category') || '';
        } catch (e) {}

        var listingWrapper = link.closest('.upt-listing-wrapper');
        var grid = listingWrapper.find('.elementor-loop-container');
        var gridId = grid.attr('id');
        var templateId = grid.data('template-id');

        if (!gridId) return;

        var triggerElement = $('.upt-search-wrapper[data-target-id="' + gridId + '"], .upt-category-filter-wrapper[data-target-id="' + gridId + '"]').first();

        if (!triggerElement.length) {
            triggerElement = $('<div />').attr({
                'data-target-id': gridId,
                'data-template-id': templateId
            });
        }

        // Persist extracted params so performuptSearch can reuse them.
        if (urlParams.searchTerm) {
            listingWrapper.attr('data-upt-last-term', urlParams.searchTerm);
        }
        if (Array.isArray(urlParams.searchTargets)) {
            listingWrapper.attr('data-upt-last-targets', urlParams.searchTargets.join(','));
        }

        // Captura a categoria ativa no momento da paginação e persiste no wrapper.
        var activeTerm = '0';
        if (urlParams.termId) {
            activeTerm = String(urlParams.termId);
        } else {
            var catWrapper = listingWrapper.find('.upt-category-filter-wrapper').first();
            if (catWrapper.length) {
                var dd = catWrapper.find('.upt-category-filter-dropdown');
                if (dd.length) {
                    activeTerm = String(dd.val() || '0');
                } else {
                    var activeItem = catWrapper.find('.upt-category-filter-item.active').first();
                    if (activeItem.length) {
                        activeTerm = String(activeItem.data('term-id') || '0');
                    }
                }
            }
        }
        listingWrapper.attr('data-upt-active-term', activeTerm);

        // Se houver campo de busca no wrapper, mantém o valor visível para evitar que pareça resetar.
        var paginationSearchWrapper = listingWrapper.find('.upt-search-wrapper').first();
        if (paginationSearchWrapper.length && typeof urlParams.searchTerm !== 'undefined') {
            paginationSearchWrapper.find('.upt-search-input').val(urlParams.searchTerm);
        }

        performuptSearch(triggerElement, page, { useStoredSearch: true });

        $('html, body').animate({
            scrollTop: listingWrapper.offset().top - 50
        }, 500);
    });

    function getuptTriggerElement(listingWrapper) {
        var grid = listingWrapper.find('.elementor-loop-container');
        var gridId = grid.attr('id');
        var templateId = grid.data('template-id');
        var triggerElement = $('.upt-search-wrapper[data-target-id="' + gridId + '"], .upt-category-filter-wrapper[data-target-id="' + gridId + '"]').first();

        if (!triggerElement.length) {
            triggerElement = $('<div />').attr({
                'data-target-id': gridId,
                'data-template-id': templateId
            });
        }
        return triggerElement;
    }

    $('body').on('click', '.upt-listing-wrapper .upt-load-more', function (e) {
        e.preventDefault();
        var $btn = $(this);
        if ($btn.prop('disabled')) { return; }

        var listingWrapper = $btn.closest('.upt-listing-wrapper');
        var nextPage = parseInt($btn.data('next-page'), 10) || 2;

        var triggerElement = getuptTriggerElement(listingWrapper);
        if (!triggerElement || !triggerElement.length) { return; }

        $btn.prop('disabled', true).addClass('is-loading');
        performuptSearch(triggerElement, nextPage, { append: true, loadingButton: $btn, useStoredSearch: true });
    });

    function inituptInfinite(listingWrapper) {
        listingWrapper = listingWrapper || $('.upt-listing-wrapper');
        if (!listingWrapper || !listingWrapper.length) { return; }

        listingWrapper.each(function () {
            var $wrap = $(this);
            var type = ($wrap.data('pagination-type') || '').toString();
            if (type !== 'infinite') { return; }

            var triggerType = ($wrap.data('infinite-trigger') || 'button').toString();

            if (triggerType !== 'scroll') { return; }

            var $sentinel = $wrap.find('.upt-load-more-sentinel').first();
            if (!$sentinel.length) { return; }

            if ($sentinel.data('observer-attached')) {
                return;
            }

            var isLoading = false;
            var loadNext = function () {
                if (isLoading) { return; }
                var nextPage = parseInt($sentinel.data('next-page'), 10) || 0;
                var totalPages = parseInt($sentinel.data('total-pages'), 10) || 0;
                if (!nextPage || !totalPages || nextPage > totalPages) {
                    return;
                }

                var triggerElement = getuptTriggerElement($wrap);
                if (!triggerElement.length) { return; }

                isLoading = true;
                performuptSearch(triggerElement, nextPage, {
                    append: true,
                    onComplete: function () { isLoading = false; }
                });
            };

            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            loadNext();
                        }
                    });
                }, { rootMargin: '200px' });

                observer.observe($sentinel.get(0));
                $sentinel.data('observer-attached', true);
            } else {
                // Fallback: simple scroll handler
                var onScroll = function () {
                    if (isLoading) { return; }
                    var rect = $sentinel.get(0).getBoundingClientRect();
                    if (rect.top <= window.innerHeight + 200) {
                        loadNext();
                    }
                };
                $(window).on('scroll.uptInfinite', onScroll);
                $sentinel.data('observer-attached', true);
            }
        });
    }

    /* ========================================================================
     * SECTION 9: INFINITE SCROLL & CATEGORY BUTTONS
     * Per-category add buttons, infinite scroll initialization
     * ======================================================================== */

    // Initialize on load
    inituptInfinite($('.upt-listing-wrapper'));


    /* ========================================================================
     * SECTION 10: MODAL
     * Open/close modal with form content, AJAX item loading
     * ======================================================================== */

    // Disponível globalmente para qualquer cenário de dashboard/modal
    function closeModal() {
        if (uptModalRequest && uptModalRequest.readyState !== 4) {
            try {
                uptModalRequest.abort();
            } catch (e) { }
        }
        uptModalRequestSeq += 1;
        $('.open-add-modal').prop('disabled', false).removeClass('is-loading');
        $('html, body').removeClass('modal-open');
        $('#upt-modal-wrapper').fadeOut(200);
        setTimeout(function () {
            $('#upt-modal-content').html('');
        }, 300);
    }

    var uptModalRequest = null;
    var uptModalRequestSeq = 0;
    var uptLastModalPayload = null;

    function openModalWithForm(data) {
        $('html, body').addClass('modal-open');
        var contentDiv = $('#upt-modal-content');
        var wrapper = $('#upt-modal-wrapper');

        uptLastModalPayload = data;
        uptModalRequestSeq += 1;
        var reqSeq = uptModalRequestSeq;

        if (uptModalRequest && uptModalRequest.readyState !== 4) {
            try {
                uptModalRequest.abort();
            } catch (e) { }
        }

        wrapper.stop(true, true).css({ display: 'flex' }).show();
        contentDiv.attr('aria-busy', 'true').html('<p style="text-align:center;">Carregando...</p>');

        var $openAddBtn = $('.open-add-modal');
        $openAddBtn.prop('disabled', true).addClass('is-loading');

        uptModalRequest = $.ajax({
            url: upt_ajax.ajax_url,
            type: 'POST',
            timeout: 25000,
            data: data,
            success: function (response) {
                if (reqSeq !== uptModalRequestSeq) {
                    return;
                }
                if (response.success && response.data && response.data.html) {
                    var html = response.data.html;
                    setTimeout(function () {
                        contentDiv.html(html);
                        contentDiv.attr('aria-busy', 'false');

                        var loadedForm = contentDiv.find('#upt-new-item-form');
                        if (loadedForm.length) {
                            try {
                                uptInitDraftForForm(loadedForm);
                            } catch (e) {
                                if (window.console && console.warn) {
                                    console.warn('upt: erro ao inicializar rascunho do formulário', e);
                                }
                            }
                        }

                        setTimeout(function () {
                            try {
                                inituptSimpleEditor(contentDiv);
                            } catch (e) {
                                if (window.console && console.warn) {
                                    console.warn('upt: erro ao inicializar editor WYSIWYG', e);
                                }
                            }
                        }, 0);
                    }, 0);

                } else {
                    if (response.data && (response.data.code === 'invalid_nonce' || response.data.code === 'not_logged_in')) {
                        alert('Sua sessão parece ter expirado. A página será recarregada para corrigir o problema.');
                        location.reload();
                    } else {
                        contentDiv.attr('aria-busy', 'false').html('<p>Ocorreu um erro ao carregar o formulário.</p>');
                    }
                }
            },
            error: function (_xhr, status) {
                if (reqSeq !== uptModalRequestSeq) {
                    return;
                }
                if (status === 'timeout') {
                    contentDiv
                        .attr('aria-busy', 'false')
                        .html('<p style="text-align:center;">Demorou mais do que o esperado.</p><p style="text-align:center;"><button type="button" class="button button-primary upt-modal-retry">Tentar novamente</button></p>');
                } else if (status === 'abort') {
                    contentDiv.attr('aria-busy', 'false').html('<p style="text-align:center;">Carregamento cancelado.</p>');
                } else {
                    contentDiv.attr('aria-busy', 'false').html('<p>Ocorreu um erro de comunicação ao carregar.</p>');
                }
            },
            complete: function () {
                if (reqSeq !== uptModalRequestSeq) {
                    return;
                }
                $openAddBtn.prop('disabled', false).removeClass('is-loading');
            }
        });
    }

    $('body').on('click', '.upt-modal-retry', function (e) {
        e.preventDefault();
        if (uptLastModalPayload) {
            openModalWithForm(uptLastModalPayload);
        }
    });


    /* ========================================================================
     * SECTION 11: DASHBOARD INIT
     * Tab system, tab shadows, filter change handler, dashboard infinite scroll
     * ======================================================================== */

    if ($('.upt-dashboard').length || $('#upt-modal-content').length) {

        var toggleAddButtonVisibility = function (targetPaneId) {
            var $headerActions = $('.upt-dashboard-header .dashboard-header-actions');
            if (!$headerActions.length) {
                return;
            }
            if (targetPaneId === '#tab-4gt-submissions' || targetPaneId === '#tab-upt-dashboard') {
                $headerActions.hide();
            } else {
                $headerActions.show();
            }
        };


        $('body').on('click', '.upt-tabs-nav a', function (e) {
            e.preventDefault();
            var $this = $(this);
            var targetPaneId = $this.attr('href');

            $('.upt-tabs-nav a').removeClass('active');
            $this.addClass('active');

            $('.upt-tab-pane').removeClass('active');
            $(targetPaneId).addClass('active');

            toggleAddButtonVisibility(targetPaneId);

            setTimeout(function () {
                initDashboardInfinite();
            }, 50);
        });


        // Suporte a scroll horizontal com a roda do mouse nas abas (layout horizontal_scroll)
        // e sombras laterais dinâmicas indicando que há mais conteúdo
        $('.upt-tabs-wrapper.upt-tabs-layout-horizontal_scroll').each(function () {
            var $wrapper = $(this);
            var $nav = $wrapper.find('.upt-tabs-nav').first();
            if (!$nav.length) {
                return;
            }

            function updateTabShadowVerticalBounds() {
                var navEl = $nav[0];
                if (!navEl || !$wrapper[0]) {
                    return;
                }
                var navRect = navEl.getBoundingClientRect();
                var wrapperRect = $wrapper[0].getBoundingClientRect();
                var topOffset = navRect.top - wrapperRect.top;

                $wrapper[0].style.setProperty('--upt-tabs-shadow-top', topOffset + 'px');
                $wrapper[0].style.setProperty('--upt-tabs-shadow-height', navRect.height + 'px');
            }

            function updateTabShadows() {
                var navEl = $nav[0];
                if (!navEl) {
                    return;
                }
                var scrollLeft = navEl.scrollLeft;
                var maxScroll = navEl.scrollWidth - navEl.clientWidth;

                if (scrollLeft > 0) {
                    $wrapper.addClass('has-left-shadow');
                } else {
                    $wrapper.removeClass('has-left-shadow');
                }

                if (scrollLeft < maxScroll - 1) {
                    $wrapper.addClass('has-right-shadow');
                } else {
                    $wrapper.removeClass('has-right-shadow');
                }
            }

            // Converte o scroll vertical do mouse em scroll horizontal nas abas
            var navEl = $nav[0];
            navEl.addEventListener('wheel', function (e) {
                if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                    navEl.scrollLeft += e.deltaY;
                    e.preventDefault();
                }
            }, { passive: false });

            $nav.on('scroll', updateTabShadows);
            $(window).on('resize', function () {
                updateTabShadowVerticalBounds();
                updateTabShadows();
            });

            // Estado inicial
            updateTabShadowVerticalBounds();
            updateTabShadows();
        });
        var initialPaneId = $('.upt-tabs-nav a.active').attr('href');
        if (initialPaneId) {
            toggleAddButtonVisibility(initialPaneId);
        }

        initDashboardInfinite();


        var filterTimeoutDashboard;
        function handleFiltersChangeDashboard(page, options) {
            options = options || {};
            var appendMode = options.append === true;
            var delay = typeof options.delay === 'number' ? options.delay : 500;

            clearTimeout(filterTimeoutDashboard);
            filterTimeoutDashboard = setTimeout(function () {
                var activeTab = (options.tab && options.tab.length) ? options.tab : $('.upt-tab-pane.active');
                if (!activeTab.length) return;

                var container = $('.upt-items-container');
                var grid = activeTab.find('.upt-items-grid');
                var paginationWrapper = activeTab.find('.upt-pagination-wrapper');
                var searchTerm = activeTab.find('.upt-search-filter').val();
                var categoryId = activeTab.find('.upt-category-filter').val();
                var schemaSlug = activeTab.attr('id').replace('tab-', '');
                var showAll = container.data('show-all') === 'yes';
                var templateId = container.data('template-id');
                var cardVariant = container.data('card-variant') || 'legacy';
                var perPage = container.data('pagination') === 'yes' ? container.data('per-page') : -1;
                var paginationType = (container.data('pagination-type') || 'numbers').toString();
                var infiniteTrigger = (container.data('pagination-infinite-trigger') || 'scroll').toString();
                var currentPage = page || 1;

                grid.css('opacity', 0.5);
                paginationWrapper.css('opacity', 0.5);

                $.ajax({
                    url: upt_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'upt_filter_items',
                        nonce: upt_ajax.nonce,
                        search_term: searchTerm,
                        category_id: categoryId,
                        schema_slug: schemaSlug,
                        show_all: showAll,
                        template_id: templateId,
                        card_variant: cardVariant,
                        per_page: perPage,
                        paged: currentPage,
                        pagination_type: paginationType,
                        pagination_infinite_trigger: infiniteTrigger
                    },
                    success: function (response) {
                        if (response.success) {
                            if (!appendMode) {
                                grid.find('.upt-media-slider').each(function(){
                                    var t = $(this).data('fcSliderTimer');
                                    if (t) { clearInterval(t); }
                                });
                                grid.html(response.data.html);
                            } else {
                                if (response.data && response.data.html && String(response.data.html).trim() !== '') {
                                    grid.append(response.data.html);
                                }
                            }
                            paginationWrapper.html((response.data && response.data.pagination_html) ? response.data.pagination_html : '');
                            $(document).trigger('upt_items_list_updated');
                            initDashboardInfinite();
                        } else {
                            grid.html('<p>Ocorreu um erro ao filtrar os itens.</p>');
                        }
                    },
                    error: function () {
                        grid.html('<p>Ocorreu um erro de comunicação.</p>');
                    },
                    complete: function () {
                        grid.css('opacity', 1);
                        paginationWrapper.css('opacity', 1);
                        container.removeAttr('data-upt-dashboard-loading');
                    }
                });
            }, delay);
        }

        function initDashboardInfinite() {
            var $container = $('.upt-items-container');
            if (!$container.length) { return; }

            if ($container.data('pagination') !== 'yes') { return; }

            var type = ($container.data('pagination-type') || '').toString();
            if (type !== 'infinite') { return; }

            var triggerType = ($container.data('pagination-infinite-trigger') || 'scroll').toString();
            if (triggerType !== 'scroll') { return; }

            var $activeTab = $('.upt-tab-pane.active');
            if (!$activeTab.length) { return; }

            var $sentinel = $activeTab.find('.upt-pagination-wrapper .upt-load-more-sentinel').first();
            if (!$sentinel.length) { return; }

            if ($sentinel.data('observer-attached')) {
                return;
            }

            var loadNext = function () {
                if ($container.attr('data-upt-dashboard-loading') === '1') { return; }

                var nextPage = parseInt($sentinel.data('next-page'), 10) || 0;
                var totalPages = parseInt($sentinel.data('total-pages'), 10) || 0;
                if (!nextPage || !totalPages || nextPage > totalPages) {
                    return;
                }

                $container.attr('data-upt-dashboard-loading', '1');
                handleFiltersChangeDashboard(nextPage, { append: true, delay: 0, tab: $activeTab });
            };

            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            loadNext();
                        }
                    });
                }, { rootMargin: '200px' });

                observer.observe($sentinel.get(0));
                $sentinel.data('observer-attached', true);
            } else {
                var onScroll = function () {
                    var rect = $sentinel.get(0).getBoundingClientRect();
                    if (rect.top <= window.innerHeight + 200) {
                        loadNext();
                    }
                };
                $(window).on('scroll.uptDashboardInfinite', onScroll);
                $sentinel.data('observer-attached', true);
            }
        }


        // Slider simples para mídias de galeria nos cards (fade entre imagens)
        function uptInitMediaSliders(ctx) {
            var $ctx = ctx ? $(ctx) : $(document);

            $ctx.find('.upt-media-slider').each(function () {
                var $slider = $(this);
                var $slides = $slider.find('.upt-media-slide');

                if (!$slides.length) { return; }

                // Evita múltiplos timers na mesma instância
                var existing = $slider.data('fcSliderTimer');
                if (existing) { clearInterval(existing); }

                if ($slides.length === 1) {
                    $slides.addClass('is-active').css('opacity', 1);
                    return;
                }

                var interval = parseInt($slider.data('interval'), 10);
                if (!interval || isNaN(interval)) { interval = 3600; }

                // Confia no CSS para opacidade; limpa qualquer inline antigo
                $slides.removeClass('is-active').css('opacity', '');
                var idx = 0;
                $slides.eq(idx).addClass('is-active').css('opacity', 1);

                var timer = setInterval(function () {
                    var next = (idx + 1) % $slides.length;
                    $slides.eq(idx).removeClass('is-active').css('opacity', '');
                    $slides.eq(next).addClass('is-active').css('opacity', 1);
                    idx = next;
                }, interval);

                $slider.data('fcSliderTimer', timer);
            });
        }

        // Garante sliders ativos no carregamento inicial
        uptInitMediaSliders(document);

        // Reativa sliders sempre que a lista for atualizada
        $(document).on('upt_items_list_updated', function () {
            uptInitMediaSliders(document);
        });


        $('body').on('click', '.upt-toggle-pagination', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var container = $('.upt-items-container');
            if (!container.length) return;

            var isPaginated = container.data('pagination') === 'yes';

            if (isPaginated) {
                container.data('pagination', 'no');
                $btn.addClass('upt-toggle-pagination-active');
                $btn.text('Mostrar paginado');
            } else {
                container.data('pagination', 'yes');
                $btn.removeClass('upt-toggle-pagination-active');
                $btn.text('Mostrar todos');
            }

            handleFiltersChangeDashboard(1);
        });

        $('body').on('keyup', '.upt-search-filter', function () { handleFiltersChangeDashboard(1); });
        $('body').on('change', '.upt-category-filter', function () { handleFiltersChangeDashboard(1); });

        // Custom category filter dropdown (allows styling "Sub" badge)
        function uptInitCustomCategoryFilter($root) {
            $root = $root && $root.length ? $root : $(document);
            $root.find('.upt-category-filter-wrap').each(function () {
                var $wrap = $(this);
                var $native = $wrap.find('select.upt-category-filter-native').first();
                var $custom = $wrap.find('.upt-category-filter-custom').first();
                var $trigger = $custom.find('.upt-category-filter-trigger').first();
                var $menu = $custom.find('.upt-category-filter-menu').first();

                if (!$native.length || !$custom.length || !$trigger.length || !$menu.length) return;
                if ($wrap.data('fcCustomInit')) return;
                $wrap.data('fcCustomInit', 1);

                function closeMenu() {
                    $menu.prop('hidden', true);
                    $trigger.attr('aria-expanded', 'false');
                }
                function openMenu() {
                    $menu.prop('hidden', false);
                    $trigger.attr('aria-expanded', 'true');
                }
                function syncTriggerFromNative() {
                    var val = String($native.val() || '');
                    var $opt = $menu.find('.upt-cat-option[data-value="' + val.replace(/"/g,'&quot;') + '"]').first();
                    var label = $opt.length ? $.trim($opt.text()) : $.trim($native.find('option:selected').text());
                    if (!label) label = 'Todas as categorias';
                    // Ensure trigger has a text span + caret element (so we can control spacing/padding reliably)
                    if (!$trigger.find('.upt-category-filter-trigger-text').length) {
                        $trigger.empty()
                            .append('<span class="upt-category-filter-trigger-text"></span>')
                            .append('<span class="upt-category-filter-trigger-caret" aria-hidden="true"></span>');
                    }
                    $trigger.find('.upt-category-filter-trigger-text').text(label);
                    adjustWidths();
                }

                function adjustWidths() {
                    // Desktop only: keep width based on the longest label so nothing wraps.
                    if (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) {
                        $trigger.css({ width: '', minWidth: '' });
                        $menu.css({ minWidth: '' });
                        return;
                    }

                    // Measure text width using an offscreen span with the same typography.
                    var $measure = $wrap.data('fcMeasureEl');
                    if (!$measure || !$measure.length) {
                        $measure = $('<span />')
                            .css({
                                position: 'absolute',
                                left: '-9999px',
                                top: '-9999px',
                                visibility: 'hidden',
                                whiteSpace: 'nowrap',
                                fontFamily: $trigger.css('font-family'),
                                fontSize: $trigger.css('font-size'),
                                fontWeight: $trigger.css('font-weight')
                            })
                            .appendTo(document.body);
                        $wrap.data('fcMeasureEl', $measure);
                    }

                    var maxTextW = 0;
                    $menu.find('.upt-cat-option').each(function () {
                        var t = $.trim($(this).text());
                        if (!t) return;
                        $measure.text(t);
                        var w = $measure[0].getBoundingClientRect().width;
                        if (w > maxTextW) maxTextW = w;
                    });

                    // Fallback to current trigger label.
                    if (!maxTextW) {
                        $measure.text($.trim($trigger.text()) || 'Todas as categorias');
                        maxTextW = $measure[0].getBoundingClientRect().width;
                    }

                    // Add padding + caret space + gap. Keep caret beside the text (no wrapping).
                    var pl = parseFloat($trigger.css('padding-left')) || 0;
                    var pr = parseFloat($trigger.css('padding-right')) || 0;
                    var gap = parseFloat($trigger.css('gap')) || 18;
                    // caret is drawn with borders; reserve a bit more than its box.
                    var caretW = 16;
                    // Add a little breathing room so caret never touches long labels.
                    var extra = pl + pr + gap + caretW + 24;
                    var targetW = Math.ceil(maxTextW + extra);

                    // Cap to viewport to avoid overflow.
                    var cap = Math.floor(window.innerWidth * 0.9);
                    if (targetW > cap) targetW = cap;

                    $trigger.css({ width: targetW + 'px', minWidth: targetW + 'px' });
                    $menu.css({ minWidth: targetW + 'px' });
                }

                $trigger.on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if ($menu.prop('hidden')) { openMenu(); } else { closeMenu(); }
                });

                $menu.on('click', '.upt-cat-option', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var v = $(this).data('value');
                    $native.val(v).trigger('change');
                    syncTriggerFromNative();
                    closeMenu();
                });

                $(document).on('click', function () {
                    closeMenu();
                });

                $native.on('change', function () {
                    syncTriggerFromNative();
                });

                // initial
                syncTriggerFromNative();
                adjustWidths();
            });
        }

    /* ========================================================================
     * SECTION 12: FORM SUBMISSION & INLINE CATEGORIES
     * Submit forms, new-item forms, inline category actions, rename options
     * ======================================================================== */

        uptInitCustomCategoryFilter($(document));
        $(document).on('upt_items_list_updated', function(){ uptInitCustomCategoryFilter($(document)); });



        $('body').on('click', '.upt-dashboard .upt-pagination-wrapper .page-numbers', function (e) {
            e.preventDefault();

            if ($(this).hasClass('current') || $(this).hasClass('dots')) {
                return;
            }

            var href = $(this).attr('href');
            var page = 1;
            var pagedMatch = href.match(/[?&]paged=(\d+)/);
            var pageMatch = href.match(/\/page\/(\d+)/);

            if (pagedMatch) {
                page = parseInt(pagedMatch[1], 10);
            } else if (pageMatch) {
                page = parseInt(pageMatch[1], 10);
            }

            handleFiltersChangeDashboard(page);
        });

        $('body').on('click', '.open-add-modal', function (e) {
            e.preventDefault();

            var $trigger = $(this);
            if ($trigger.hasClass('is-loading')) {
                return;
            }

            var activeTab = $('.upt-tab-pane.active');
            var schemaSlug = activeTab.length ? activeTab.attr('id').replace('tab-', '') : '';
            var rawMaxPerCat = activeTab.length ? activeTab.data('items-max-per-cat') : null;
            var rawMaxPerCatAttr = activeTab.length ? activeTab.attr('data-items-max-per-cat') : null;
            var rawPerCat = activeTab.length ? activeTab.data('items-per-cat') : null;
            var rawPerCatAttr = activeTab.length ? activeTab.attr('data-items-per-cat') : null;

            var limitMaxPerCat = normalizeTruth(rawMaxPerCat) || normalizeTruth(rawMaxPerCatAttr);
            var limitMinPerCat = normalizeTruth(rawPerCat) || normalizeTruth(rawPerCatAttr);
            var forcePerCategoryMode = limitMaxPerCat || limitMinPerCat;

            uptDebugLogLimits('openAddClick', {
                tab: activeTab.attr('id'),
                schemaSlug: schemaSlug,
                rawMaxPerCat: rawMaxPerCat,
                rawMaxPerCatAttr: rawMaxPerCatAttr,
                rawPerCat: rawPerCat,
                rawPerCatAttr: rawPerCatAttr,
                forcePerCategoryMode: forcePerCategoryMode,
                itemsLimit: activeTab.data('items-limit')
            });

            // Garante que o botão esteja visível quando modo por categoria estiver ativo (remove inline display:none)
            if (forcePerCategoryMode) {
                $('.open-add-modal').show().removeClass('upt-button-disabled').css('display', '');
            }

            // Se o esquema tiver limite e já estiver cheio, não abre o modal
            if (activeTab.length && !forcePerCategoryMode) {
                var limit = parseInt(activeTab.data('items-limit'), 10) || 0;
                if (limit) {
                    var grid = activeTab.find('.upt-items-grid').first();
                    if (grid.length) {
                        var cards = grid.find('.upt-item-card');

                        if (cards.length === 0) {
                            cards = grid.children().filter(function () {
                                var $el = jQuery(this);
                                if (this.nodeType !== 1) return false;
                                if ($el.is('script, style')) return false;
                                if ($el.hasClass('no-items-message')) return false;
                                return $el.is(':visible');
                            });
                        }

                        if (cards.length >= limit) {
                            // botão já deve estar oculto, mas garantimos que nada aconteça aqui
                            return;
                        }
                    }
                }
            }

            openModalWithForm({
                action: 'upt_get_form',
                nonce: upt_ajax.nonce,
                schema: schemaSlug
            });
        });

        $('body').on('click', '.open-manage-categories-modal', function (e) {
            e.preventDefault();
            var activeTab = $('.upt-tab-pane.active');
            var schemaSlug = activeTab.length ? activeTab.attr('id').replace('tab-', '') : '';

            if (!schemaSlug) {
                alert('Por favor, selecione uma aba de esquema primeiro.');
                return;
            }

            openModalWithForm({
                action: 'upt_get_category_manager',
                nonce: upt_ajax.nonce,
                schema_slug: schemaSlug
            });
        });

        $('body').on('click', '.delete-category-from-manager', function (e) {
            e.preventDefault();
            var button = $(this);
            var termId = button.data('term-id');
            var termName = button.data('term-name');
            var listItem = button.closest('li');
            var activeTab = $('.upt-tab-pane.active');
            var schemaSlug = activeTab.length ? activeTab.attr('id').replace('tab-', '') : '';

            uptConfirmDialog('Tem certeza que deseja excluir a categoria "' + termName + '" permanentemente?', function () {
                listItem.css('opacity', 0.5);

                $.ajax({
                    url: upt_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'upt_delete_category',
                        nonce: upt_ajax.nonce,
                        term_id: termId,
                        schema_slug: schemaSlug
                    },
                    success: function (response) {
                        if (response.success) {
                            listItem.fadeOut(300, function () { $(this).remove(); });
                            uptNotify('category_deleted', 'Categoria apagada.');
                            if (response.data.filter_html) {
                                activeTab.find('.filter-item.filter-category').html(response.data.filter_html);
                            }
                        } else {
                            alert('Erro ao excluir: ' + (response.data.message || 'Erro desconhecido.'));
                            listItem.css('opacity', 1);
                        }
                    },
                    error: function () {
                        alert('Erro de comunicação.');
                        listItem.css('opacity', 1);
                    }
                });
            });
        });

        $('body').on('submit', '#upt-schema-selector-form', function (e) {
            e.preventDefault();
            var selectedSchema = $('#schema-selector-dropdown').val();
            var contentDiv = $('#upt-modal-content');

            contentDiv.html('<p style="text-align:center;">Carregando...</p>');

            $.ajax({
                url: upt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'upt_get_form',
                    nonce: upt_ajax.nonce,
                    schema: selectedSchema
                },
                success: function (response) {
                    if (response.success) {
                        contentDiv.html(response.data.html);
                    } else {
                        contentDiv.html('<p>Ocorreu um erro ao carregar.</p>');
                    }
                },
                error: function () {
                    contentDiv.html('<p>Ocorreu um erro de comunicação ao carregar.</p>');
                }
            });
        });

        $('body').on('click', '.open-edit-modal', function (e) {
            e.preventDefault();
            openModalWithForm({
                action: 'upt_get_form',
                nonce: upt_ajax.nonce,
                item_id: $(this).data('item-id')
            });
        });

        $('body').on('click', '#upt-modal-close, #upt-modal-wrapper', function (e) {
            if (e.target !== this) return;
            e.preventDefault();
            closeModal();
        });

        $('body').on('submit', '#upt-new-item-form', function (e) {
            e.preventDefault();
            var form = $(this);
            var submitButton = form.find('input[type="submit"]');
            var originalButtonText = submitButton.val();
            var container = $('.upt-items-container');
            var showAll = container.data('show-all') === 'yes';
            var templateId = container.data('template-id');
            var cardVariant = container.data('card-variant') || 'legacy';
            var activeTab = $('.upt-tab-pane.active');
            var activeSchemaSlug = activeTab.length ? activeTab.attr('id').replace('tab-', '') : '';
            var activeCategoryId = activeTab.find('.upt-category-filter').val() || '0';
            var currentPage = activeTab.find('.page-numbers.current').text() || 1;
            var perPage = container.data('pagination') === 'yes' ? container.data('per-page') : -1;

            // Validação: subcategoria obrigatória (quando habilitado)
            try {
                var $subWrapReq = form.find('.upt-subcategory-wrapper[data-subcat-required="1"]');
                if ($subWrapReq.length) {
                    $subWrapReq.each(function () {
                        var $wrap = $(this);
                        var $child = $wrap.find('#upt-category-child');
                        if (!$child.length) return;
                        // só valida quando o campo está ativo/visível
                        if ($child.is(':disabled') || !$wrap.is(':visible')) return;
                        // valida apenas quando existem opções reais
                        if ($child.find('option').length <= 1) return;
                        if (!($child.val() || '')) {
                            throw new Error('subcat_required');
                        }
                    });
                }
            } catch (err) {
                alert('Selecione uma subcategoria para continuar.');
                var $first = form.find('.upt-subcategory-wrapper[data-subcat-required="1"] #upt-category-child').first();
                if ($first.length) { $first.focus(); }
                return;
            }

            if (!uptValidateMediaRequiredClientSide(form)) {
                return;
            }

            submitButton.val('Salvando...').prop('disabled', true);

            var currentItemId = parseInt(form.find('input[name="item_id"]').val() || '0', 10);
            var isEditAction = currentItemId && currentItemId > 0;


            var formData = new FormData(this);
            formData.append('action', 'upt_save_item');
            formData.append('nonce', upt_ajax.nonce);
            formData.append('show_all', showAll);
            formData.append('template_id', templateId);
            formData.append('card_variant', cardVariant);
            formData.append('active_schema_slug', activeSchemaSlug);
            formData.append('paged', currentPage);
            formData.append('per_page', perPage);
            formData.append('active_category_id', activeCategoryId);

            $.ajax({
                url: upt_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        // Não usamos o HTML retornado aqui: sempre refazemos a listagem via filtro
                        handleFiltersChangeDashboard(parseInt(currentPage, 10) || 1);
                        uptNotify(isEditAction ? 'edit' : 'create', isEditAction ? 'Item editado!' : 'Item criado!');
                        closeModal();
                    } else {
                        var message = (response.data && response.data.message) ? response.data.message : '';
                        var mediaData = response.data || {};
                        // Se for erro de limite de itens do esquema
                        if (message && (message.indexOf('limite máximo') !== -1 || message.indexOf('limite mínimo') !== -1 || message.indexOf('limite minimo') !== -1)) {
                            uptNotify('schema_qty', message);
                            jQuery(document).trigger('upt_items_list_updated');
                            closeModal();
                        } else if (mediaData.media_required && Array.isArray(mediaData.media_fields)) {
                            uptShowMediaRequiredTooltips(mediaData.media_fields, message);
                        } else {
                            alert('Erro ao salvar: ' + (message || 'Ocorreu um erro desconhecido.'));
                        }
                    }
                },
                error: function () {
                    alert('Ocorreu um erro de comunicação. Tente novamente.');
                },
                complete: function () {
                    submitButton.val(originalButtonText).prop('disabled', false);
                }
            });
        });

        $('body').on('click', '.delete-item-ajax', function (e) {
            e.preventDefault();

            var link = $(this);
            var itemId = link.data('item-id');
            var card = link.closest('.upt-item-card, .elementor-widget-container');
            var container = $('.upt-items-container');
            var showAll = container.data('show-all') === 'yes';
            var templateId = container.data('template-id');
            var cardVariant = container.data('card-variant') || 'legacy';
            var activeTab = $('.upt-tab-pane.active');
            var activeSchemaSlug = activeTab.length ? activeTab.attr('id').replace('tab-', '') : '';
            var activeCategoryId = activeTab.find('.upt-category-filter').val() || '0';
            var currentPage = activeTab.find('.page-numbers.current').text() || 1;
            var perPage = container.data('pagination') === 'yes' ? container.data('per-page') : -1;

            var confirmMsg = 'Tem certeza que deseja excluir este item permanentemente?';

            uptDebugLog('delete-item: click', {
                itemId: itemId,
                cardFound: !!card.length,
                ajax_url: uptGetAjaxConfig().ajax_url,
                showAll: showAll,
                templateId: templateId,
                activeSchemaSlug: activeSchemaSlug,
                currentPage: currentPage,
                perPage: perPage
            });

            uptConfirmDialog(confirmMsg, function () {
                card.css('opacity', 0.5);

                var ajaxPayload = {
                    action: 'upt_delete_item',
                    nonce: upt_ajax.nonce,
                    item_id: itemId,
                    show_all: showAll,
                    template_id: templateId,
                    card_variant: cardVariant,
                    active_schema_slug: activeSchemaSlug,
                    paged: currentPage,
                    per_page: perPage,
                    active_category_id: activeCategoryId
                };

                uptDebugLog('delete-item: ajax start', {
                    url: upt_ajax.ajax_url,
                    payload: ajaxPayload
                });

                $.ajax({
                    url: upt_ajax.ajax_url,
                    type: 'POST',
                    data: ajaxPayload,
                    success: function (response) {
                        uptDebugLog('delete-item: ajax success', response);
                        if (response.success) {
                            uptNotify('delete', 'Item apagado!');
                            card.fadeOut(400, function () {
                                // Sempre refaz a listagem via filtro (fonte única de verdade)
                                handleFiltersChangeDashboard(parseInt(currentPage, 10) || 1);
                            });
                        } else {
                            alert('Erro ao excluir: ' + (response.data.message || 'Ocorreu um erro desconhecido.'));
                            card.css('opacity', 1);
                        }
                    },
                    error: function (xhr, status, err) {
                        uptDebugLog('delete-item: ajax error', { status: status, err: err, responseText: xhr && xhr.responseText });
                        alert('Ocorreu um erro de comunicação. Tente novamente.');
                        card.css('opacity', 1);
                    }
                });

                // Fallback timer to log if the request takes too long
                setTimeout(function () {
                    if (card && card.length && card.css('opacity') === '0.5') {
                        uptDebugLog('delete-item: ajax still pending after 5s', ajaxPayload);
                    }
                }, 5000);
            });

        });

            function uptBindInlineCategoryActions() {
                try {
                    if (window.uptInlineCategoryBound) {
                        return;
                    }
                    window.uptInlineCategoryBound = true;
                } catch (err) {
                    console.error('[upt][add-cat] bind error', err);
                }

                function uptEnsureCategoryChecklistState(checklist) {
                    var $checklist = checklist && checklist.jquery ? checklist : $(checklist);
                    if (!$checklist || !$checklist.length) {
                        return;
                    }

                    var hasAny = $checklist.find('input.upt-cat-checkbox').length > 0;
                    var $empty = $checklist.find('.upt-taxonomy-empty');
                    if (hasAny) {
                        $empty.remove();
                        return;
                    }
                    if (!$empty.length) {
                        $checklist.append('<span class="upt-taxonomy-empty">Nenhuma categoria encontrada para este esquema.</span>');
                    }
                }

                $('.upt-taxonomy-checklist[data-upt-taxonomy="catalog_category"], #upt-taxonomy-checklist').each(function () {
                    uptEnsureCategoryChecklistState($(this));
                });

                $('body').on('click', '#add-new-cat-button', function (e) {
                    e.preventDefault();
                    var $wrapper = $(this).closest('.taxonomy-actions-wrapper');
                    $(this).hide();
                    $wrapper.find('#new-cat-area').slideDown();
                });
                $('body').on('click', '#cancel-new-cat', function (e) {
                    e.preventDefault();
                    var $wrapper = $(this).closest('.taxonomy-actions-wrapper');
                    $wrapper.find('#new-cat-area').slideUp();
                    $wrapper.find('#add-new-cat-button').show();
                    $wrapper.find('#new-cat-name').val('');
                    $wrapper.find('#new-cat-create-subcats').prop('checked', false);
                    $wrapper.find('#new-cat-subcats').val('').hide();
                });

                $('body').on('change', '#new-cat-create-subcats', function () {
                    var $wrapper = $(this).closest('.taxonomy-actions-wrapper');
                    var $textarea = $wrapper.find('#new-cat-subcats');
                    if ($(this).is(':checked')) {
                        $textarea.slideDown();
                    } else {
                        $textarea.slideUp();
                    }
                });

                $('body').on('click', '#save-new-cat', function (e) {
                    e.preventDefault();
                    var button = $(this);
                    var originalButtonText = button.text();
                    var $wrapper = button.closest('.taxonomy-actions-wrapper');
                    var newCatName = $wrapper.find('#new-cat-name').val().trim();
                    var statusDiv = $wrapper.find('#new-cat-status');
                    var parentId = $wrapper.find('#add-new-cat-button').data('parent-id');

                    var createSubcats = $wrapper.find('#new-cat-create-subcats').is(':checked');
                    var subcatsRaw = createSubcats ? ($wrapper.find('#new-cat-subcats').val() || '') : '';

                    if (newCatName === '') { statusDiv.text('Por favor, digite um nome.').css('color', 'red'); return; }
                    button.text('Salvando...').prop('disabled', true);
                    statusDiv.text('').css('color', 'inherit');
                    $.ajax({
                        url: upt_ajax.ajax_url, type: 'POST',
                        data: {
                            action: 'upt_add_category',
                            nonce: upt_ajax.nonce,
                            new_cat_name: newCatName,
                            parent_id: parentId,
                            create_subcategories: createSubcats ? 1 : 0,
                            subcategories: subcatsRaw
                        },
                        success: function (response) {
                            if (response.success) {
                                var targetChecklistId = button.data('target-checklist');
                                var targetEl = $('#' + targetChecklistId);

                                var termId = response.data.term_id;
                                var termName = response.data.name;

                                uptNotify('category_created', 'Categoria criada: ' + termName);

                                if (targetEl.length > 0 && targetEl.is('select')) {
                                    // Taxonomia single (dropdown)
                                    var newOption = new Option(termName, termId, true, true);
                                    targetEl.append(newOption).val(String(termId)).trigger('change');
                                } else if (targetEl.length > 0) {
                                    // Taxonomia multiple (checklist)
                                    targetEl.find('.upt-taxonomy-empty').remove();

                                    var checkboxId = 'cat-checkbox-' + termId;

                                    var newCheckboxHtml =
                                        '<input type="checkbox" class="upt-cat-checkbox" name="categoria-do-item[]" id="' + checkboxId + '" value="' + termId + '" checked>' +
                                        '<label for="' + checkboxId + '">' +
                                        '<span class="pill-checkbox-icon"></span>' +
                                        '<span class="pill-text">' + termName + '</span>' +
                                        ' <a href="#" class="remove-pill remove-cat-pill" title="Excluir Categoria" data-term-id="' + termId + '" data-term-name="' + termName + '"><span class="pill-delete-icon"></span></a>' +
                                        '</label>';

                                    targetEl.append(newCheckboxHtml);
                                    uptEnsureCategoryChecklistState(targetEl);
                                } else {
                                    // Fallback legado
                                    var catDropdown = $('#categoria-do-item');
                                    if (catDropdown.length) {
                                        var fallbackOption = new Option(termName, termId, true, true);
                                        catDropdown.append(fallbackOption).val(String(termId)).trigger('change');
                                    }
                                }

                                $wrapper.find('#new-cat-area').slideUp();
                                $wrapper.find('#add-new-cat-button').show();
                                $wrapper.find('#new-cat-name').val('');
                                $wrapper.find('#new-cat-create-subcats').prop('checked', false);
                                $wrapper.find('#new-cat-subcats').val('').hide();
                            } else {
                                statusDiv.text(response.data.message).css('color', 'red');
                            }
                        },
                        error: function (xhr, status) {
                            statusDiv.text('Erro de comunicação.').css('color', 'red');
                        },
                        complete: function () { button.text(originalButtonText).prop('disabled', false); }
                    });
                });

                $('body').on('click', '.add-new-select-option', function (e) {
                    e.preventDefault();
                    var fieldId = $(this).data('field-id');
                    $(this).hide();
                    $('#new-option-area-' + fieldId).slideDown();
                });
                $('body').on('click', '.cancel-new-select-option', function (e) {
                    e.preventDefault();
                    var fieldId = $(this).data('field-id');
                    $('#new-option-area-' + fieldId).slideUp();
                    $('.add-new-select-option[data-field-id="' + fieldId + '"]').show();
                    $('#new-option-name-' + fieldId).val('');
                });
                $('body').on('click', '.save-new-select-option', function (e) {
                    e.preventDefault();
                    var button = $(this);
                    var originalButtonText = button.text();
                    var fieldId = button.data('field-id');
                    var schemaSlug = button.data('schema-slug');
                    var newOptionName = $('#new-option-name-' + fieldId).val().trim();
                    var statusDiv = $('#new-option-status-' + fieldId);

                    if (newOptionName === '') {
                        statusDiv.text('Por favor, digite um nome.').css('color', 'red');
                        return;
                    }

                    button.text('Salvando...').prop('disabled', true);
                    statusDiv.text('').css('color', 'inherit');

                    $.ajax({
                        url: upt_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'upt_add_schema_option',
                            nonce: upt_ajax.nonce,
                            schema_slug: schemaSlug,
                            field_id: fieldId,
                            new_option_name: newOptionName
                        },
                        success: function (response) {
                            if (response.success) {
                                var optionList = $('#' + fieldId);

                                if (optionList.is('select') && !optionList.is('[multiple]')) {
                                    optionList.append(new Option(response.data.name, response.data.name, true, true)).trigger('change');
                                } else {
                                    if (optionList.find('label').length === 0 && optionList.text().includes('Nenhuma')) {
                                        optionList.html('');
                                    }
                                    var checkboxId = 'select-checkbox-' + fieldId + '-' + response.data.name.toLowerCase().replace(/\s+/g, '-');
                                    var newPillHtml = '<input type="checkbox" class="upt-cat-checkbox" name="' + fieldId + '[]" id="' + checkboxId + '" value="' + response.data.name + '" checked>' +
                                        '<label for="' + checkboxId + '"><span class="pill-checkbox-icon"></span><span class="pill-text">' + response.data.name + '</span> <a href="#" class="remove-pill remove-option-pill" title="Excluir Opção" data-option-value="' + response.data.name + '" data-field-id="' + fieldId + '" data-schema-slug="' + schemaSlug + '"><span class="pill-delete-icon"></span></a></label>';
                                    optionList.append(newPillHtml);
                                }

                                $('#new-option-area-' + fieldId).slideUp();
                                $('.add-new-select-option[data-field-id="' + fieldId + '"]').show();
                                $('#new-option-name-' + fieldId).val('');
                            } else {
                                statusDiv.text(response.data.message).css('color', 'red');
                            }
                        },
                        error: function () {
                            statusDiv.text('Erro de comunicação.').css('color', 'red');
                        },
                        complete: function () {
                            button.text(originalButtonText).prop('disabled', false);
                        }
                    });
                });

                // Renomear opção (select)
                $('body').on('click', '.rename-select-option', function (e) {
                    e.preventDefault();
                    var btn = $(this);
                    var fieldId = btn.data('field-id');
                    var schemaSlug = btn.data('schema-slug');
                    var $mainSelect = $('#' + fieldId);

                    if (!$mainSelect.length) {
                        return;
                    }

                    // Fecha área de adicionar, se aberta
                    $('#new-option-area-' + fieldId).slideUp();
                    $('.add-new-select-option[data-field-id="' + fieldId + '"]').show();

                    // Renomear sempre parte do item atualmente selecionado no select principal
                    var currentVal = '';
                    if ($mainSelect.is('select')) {
                        currentVal = ($mainSelect.val() || '').trim();
                    }

                    var $status = $('#rename-option-status-' + fieldId);
                    $status.text('');

                    if (!currentVal) {
                        $status.text('Selecione um item para renomear.').css('color', 'red');
                        $('#rename-option-area-' + fieldId).slideDown();
                        $('#rename-option-old-' + fieldId).val('');
                        $('#rename-option-name-' + fieldId).val('');
                        return;
                    }

                    $('#rename-option-old-' + fieldId).val(currentVal);
                    $('#rename-option-name-' + fieldId).val(currentVal);
                    $('#rename-option-area-' + fieldId).slideDown();
                });

                $('body').on('click', '.rename-select-option-cancel', function (e) {
                    e.preventDefault();
                    var fieldId = $(this).data('field-id');
                    $('#rename-option-area-' + fieldId).slideUp();
                    $('#rename-option-name-' + fieldId).val('');
                    $('#rename-option-old-' + fieldId).val('');
                    $('#rename-option-status-' + fieldId).text('');
                });

                $('body').on('click', '.rename-select-option-save', function (e) {
                    e.preventDefault();
                    var button = $(this);
                    var originalButtonText = button.text();
                    var fieldId = button.data('field-id');
                    var schemaSlug = button.data('schema-slug');
                    var oldName = $('#rename-option-old-' + fieldId).val().trim();
                    var newName = $('#rename-option-name-' + fieldId).val().trim();
                    var statusDiv = $('#rename-option-status-' + fieldId);

                    if (!oldName || !newName) {
                        statusDiv.text('Preencha o nome.').css('color', 'red');
                        return;
                    }
                    if (oldName === newName) {
                        statusDiv.text('Nenhuma alteração.').css('color', '#555');
                        return;
                    }

                    button.text('Salvando...').prop('disabled', true);
                    statusDiv.text('');

                    $.ajax({
                        url: upt_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'upt_rename_schema_option',
                            nonce: upt_ajax.nonce,
                            schema_slug: schemaSlug,
                            field_id: fieldId,
                            old_option_name: oldName,
                            new_option_name: newName
                        },
                        success: function (response) {
                            if (response.success) {
                                var $select = $('#' + fieldId);
                                if ($select.is('select')) {
                                    $select.find('option').each(function(){
                                        if ($(this).val() === oldName) {
                                            $(this).val(newName).text(newName);
                                        }
                                    });
                                    $select.val(newName).trigger('change');
                                }

                                // múltiplo (pills)
                                var $checklist = $('#' + fieldId);
                                if ($checklist.length && !$checklist.is('select')) {
                                    $checklist.find('input[type="checkbox"]').each(function(){
                                        if ($(this).val() === oldName) {
                                            $(this).val(newName);
                                            var id = $(this).attr('id');
                                            var $lbl = $checklist.find('label[for="' + id + '"]');
                                            $lbl.find('.pill-text').text(newName);
                                            $lbl.find('.remove-option-pill').attr('data-option-value', newName);
                                        }
                                    });
                                }

                                $('#rename-option-area-' + fieldId).slideUp();
                                $('#rename-option-name-' + fieldId).val('');
                                $('#rename-option-old-' + fieldId).val('');
                            } else {
                                statusDiv.text((response.data && response.data.message) ? response.data.message : 'Erro ao renomear.').css('color', 'red');
                            }
                        },
                        error: function () {
                            statusDiv.text('Erro de comunicação.').css('color', 'red');
                        },
                        complete: function () {
                            button.text(originalButtonText).prop('disabled', false);
                        }
                    });
                });

                // Excluir opção (select)
                $('body').on('click', '.delete-select-option', function (e) {
                    e.preventDefault();
                    var btn = $(this);
                    var fieldId = btn.data('field-id');
                    var schemaSlug = btn.data('schema-slug');
                    var $select = $('#' + fieldId);
                    if (!$select.length || !$select.is('select')) {
                        return;
                    }

                    var currentVal = ($select.val() || '');
                    if (!currentVal) {
                        uptToast('Selecione uma opção para excluir.', 'warning');
                        return;
                    }

                    uptConfirmDialog('Tem certeza que deseja excluir a opção "' + currentVal + '"?', function(){
                        $.ajax({
                            url: upt_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'upt_delete_schema_option',
                                nonce: upt_ajax.nonce,
                                schema_slug: schemaSlug,
                                field_id: fieldId,
                                option_value: currentVal
                            },
                            success: function (response) {
                                if (response.success) {
                                    $select.find('option').filter(function(){ return $(this).val() === currentVal; }).remove();
                                    $select.val('').trigger('change');
                                } else {
                                    uptToast((response.data && response.data.message) ? response.data.message : 'Erro ao excluir.', 'error');
                                }
                            },
                            error: function () {
                                uptToast('Erro de comunicação.', 'error');
                            }
                        });
                    });
                });

                $('body').on('click', '.remove-cat-pill', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var button = $(this);
                    var termId = button.data('term-id');
                    var termName = button.data('term-name');
                    var schemaSlug = $('input[name="schema_slug"]').val();

                    uptConfirmDialog('Tem certeza que deseja excluir a categoria "' + termName + '" permanentemente?', function () {
                        $.ajax({
                            url: upt_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'upt_delete_category',
                                nonce: upt_ajax.nonce,
                                term_id: termId,
                                schema_slug: schemaSlug
                            },
                            success: function (response) {
                                if (response.success) {
                                    var checkbox = button.closest('label').prev('input.upt-cat-checkbox');
                                    var checklist = button.closest('.upt-taxonomy-checklist');
                                    button.closest('label').remove();
                                    checkbox.remove();
                                    uptEnsureCategoryChecklistState(checklist);
                                    var activeTab = $('.upt-tab-pane.active');
                                    if (activeTab.length && response.data.filter_html) {
                                        activeTab.find('.filter-item.filter-category').html(response.data.filter_html);
                                    }
                                    uptShowAlertBadge('Categoria "' + termName + '" excluída com sucesso.');
                                } else {
                                    alert('Erro ao excluir: ' + (response.data ? response.data.message : 'Erro desconhecido.'));
                                }
                            },
                            error: function () { alert('Erro de comunicação ao tentar excluir a categoria.'); }
                        });
                    });
                });

                $('body').on('click', '.remove-option-pill', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var button = $(this);
                    var optionValue = button.data('option-value');

                    uptConfirmDialog('Tem certeza que deseja excluir a opção "' + optionValue + '" permanentemente? Esta ação não pode ser desfeita.', function () {
                        $.ajax({
                            url: upt_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'upt_delete_schema_option',
                                nonce: upt_ajax.nonce,
                                schema_slug: button.data('schema-slug'),
                                field_id: button.data('field-id'),
                                option_value: optionValue
                            },
                            success: function (response) {
                                if (response.success) {
                                    var checkbox = button.closest('label').prev('input.upt-cat-checkbox');
                                    button.closest('label').remove();
                                    checkbox.remove();
                                    uptShowAlertBadge('Opção "' + optionValue + '" excluída com sucesso.');
                                } else {
                                    alert('Erro ao excluir: ' + (response.data ? response.data.message : 'Erro desconhecido.'));
                                }
                            },
                            error: function () { alert('Erro de comunicação ao tentar excluir a opção.'); }
                        });
                    });
                });

                $('body').on('click', '#rename-cat-button', function (e) {
                    e.preventDefault();

                    // Escopa ao campo de taxonomia correto (evita conflito quando existem múltiplos campos com IDs repetidos)
                    var $row = $(this).closest('.upt-field-row');
                    var catDropdown = $row.find('#upt-category-parent');
                    if (!catDropdown.length || !catDropdown.is('select')) {
                        catDropdown = $row.find('#categoria-do-item');
                    }

                    var termId = parseInt(catDropdown.val(), 10);
                    if (!termId || termId <= 0) {
                        alert('Por favor, selecione uma categoria para renomear.');
                        return;
                    }

                    var $area = $row.find('.upt-rename-cat-area');
                    if (!$area.length) {
                        // Fallback (não deveria acontecer)
                        var currentNameFallback = catDropdown.find('option:selected').text();
                        var newNameFallback = prompt('Digite o novo nome para a categoria:', currentNameFallback);
                        if (newNameFallback === null) { return; }
                        newNameFallback = (newNameFallback || '').trim();
                        if (!newNameFallback || newNameFallback === currentNameFallback) { return; }

                        $.ajax({
                            url: upt_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'upt_rename_category',
                                nonce: upt_ajax.nonce,
                                term_id: termId,
                                new_name: newNameFallback
                            },
                            success: function (response) {
                                if (response.success) {
                                    var updatedName = response.data.name || newNameFallback;
                                    catDropdown.find('option[value="' + termId + '"]').text(updatedName);
                                    var activeTab = $('.upt-tab-pane.active');
                                    if (activeTab.length && response.data.filter_html) {
                                        activeTab.find('.filter-item.filter-category').html(response.data.filter_html);
                                    }
                                    uptNotify('category_renamed', (response.data && response.data.message) ? response.data.message : ('Categoria renomeada: ' + updatedName));
                                } else {
                                    alert('Erro ao renomear: ' + (response.data.message || 'Erro desconhecido.'));
                                }
                            },
                            error: function () {
                                alert('Erro de comunicação ao tentar renomear a categoria.');
                            }
                        });
                        return;
                    }

                    // Fecha outras áreas do mesmo bloco (ex: adicionar categoria)
                    $row.find('#new-cat-area:visible').hide();
                    $row.find('.upt-subcat-area:visible').hide();

                    var currentName = catDropdown.find('option:selected').text();

                    $area.find('.upt-rename-cat-term-id').val(termId);
                    $area.find('.upt-rename-cat-name').val(currentName);
                    $area.find('.upt-rename-cat-status').text('');

                    $area.stop(true, true).slideDown(180, function () {
                        $area.find('.upt-rename-cat-name').trigger('focus').select();
                    });
                });

                $('body').on('click', '.upt-rename-cat-cancel', function (e) {
                    e.preventDefault();
                    var $row = $(this).closest('.upt-field-row');
                    var $area = $(this).closest('.upt-rename-cat-area');
                    if (!$area.length) {
                        $area = $row.find('.upt-rename-cat-area');
                    }
                    $area.stop(true, true).slideUp(150);
                });

                $('body').on('click', '.upt-rename-cat-save', function (e) {
                    e.preventDefault();

                    var $area = $(this).closest('.upt-rename-cat-area');
                    var $row = $(this).closest('.upt-field-row');

                    var catDropdown = $row.find('#upt-category-parent');
                    if (!catDropdown.length || !catDropdown.is('select')) {
                        catDropdown = $row.find('#categoria-do-item');
                    }

                    var termId = parseInt($area.find('.upt-rename-cat-term-id').val(), 10);
                    var newName = ($area.find('.upt-rename-cat-name').val() || '').trim();

                    var $status = $area.find('.upt-rename-cat-status');
                    $status.text('');

                    if (!termId || termId <= 0) {
                        $status.text('Selecione uma categoria para renomear.');
                        return;
                    }
                    if (!newName) {
                        $status.text('Informe o novo nome da categoria.');
                        return;
                    }

                    var currentName = catDropdown.find('option[value="' + termId + '"]').text();
                    if (newName === currentName) {
                        $area.stop(true, true).slideUp(150);
                        return;
                    }

                    $(this).prop('disabled', true);

                    $.ajax({
                        url: upt_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'upt_rename_category',
                            nonce: upt_ajax.nonce,
                            term_id: termId,
                            new_name: newName
                        },
                        success: function (response) {
                            if (response.success) {
                                var updatedName = response.data.name || newName;
                                catDropdown.find('option[value="' + termId + '"]').text(updatedName);

                                // Atualiza filtro de categorias (dropdown) no painel se retornado.
                                var activeTab = $('.upt-tab-pane.active');
                                if (activeTab.length && response.data.filter_html) {
                                    activeTab.find('.filter-item.filter-category').html(response.data.filter_html);
                                }

                                $area.stop(true, true).slideUp(150);
                                uptNotify('category_renamed', (response.data && response.data.message) ? response.data.message : ('Categoria renomeada: ' + updatedName));
                            } else {
                                $status.text((response.data && response.data.message) ? response.data.message : 'Erro ao renomear categoria.');
                            }
                        },
                        error: function () {
                            $status.text('Erro de comunicação ao tentar renomear a categoria.');
                        },
                        complete: function () {
                            $area.find('.upt-rename-cat-save').prop('disabled', false);
                        }
                    });
                });

                $('body').on('click', '#delete-cat-button', function (e) {
                    e.preventDefault();
                    // Escopa ao campo de taxonomia correto (evita conflito quando existem múltiplos campos com IDs repetidos)
                    var $row = $(this).closest('.upt-field-row');
                    var catDropdown = $row.find('#upt-category-parent');
                    if (!catDropdown.length || !catDropdown.is('select')) {
                        catDropdown = $row.find('#categoria-do-item');
                    }
                    var termId = catDropdown.val();
                    var catName = catDropdown.find('option:selected').text();

                    if (!termId || termId <= 0) {
                        alert('Por favor, selecione uma categoria para excluir.');
                        return;
                    }
                    uptConfirmDialog('Tem certeza que deseja excluir a categoria "' + catName + '"? Esta ação não pode ser desfeita.', function () {
                        $.ajax({
                            url: upt_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'upt_delete_category',
                                nonce: upt_ajax.nonce,
                                term_id: termId
                            },
                            success: function (response) {
                                if (response.success) {
                                    catDropdown.find('option[value="' + termId + '"]').remove();
                                    catDropdown.val(0).trigger('change');
                                    if (window.uptNotify) { window.uptNotify('category_deleted', response.data && response.data.message ? response.data.message : 'Categoria apagada.'); }
                                } else {
                                    alert('Erro: ' + (response.data && response.data.message ? response.data.message : 'Erro ao excluir categoria.'));
                                }
                            },
                            error: function () {
                                alert('Erro de comunicação ao tentar excluir a categoria.');
                            }
                        });
                    });
                });

                // ===== Subcategorias (campo dependente) =====

                function uptSyncFinalCategoryValue($context) {
                    var $ctx = $context && $context.length ? $context : $(document);
                    var $parent = $ctx.find('#upt-category-parent');
                    var $child = $ctx.find('#upt-category-child');
                    var $final = $ctx.find('#categoria-do-item');
                    if (!$final.length) return;
                    var childVal = $child.length ? ($child.val() || '') : '';
                    var parentVal = $parent.length ? ($parent.val() || '') : '';
                    $final.val(childVal ? childVal : parentVal);
                }

                function uptToggleSubcatUI($wrapper) {
                    var $parent = $wrapper.find('#upt-category-parent');
                    var hasParent = $parent.length && ($parent.val() || '') !== '';
                    var $subWrap = $wrapper.find('.upt-subcategory-wrapper');
                    var $actions = $wrapper.find('.upt-subcategory-actions');
                    var $child = $wrapper.find('#upt-category-child');

                    if (!hasParent) {
                        $subWrap.slideUp(150);
                        $actions.hide();
                        // limpa subcat
                        $child.val('').prop('disabled', true);
                        uptSyncFinalCategoryValue($wrapper);
                        return;
                    }

                    // com categoria selecionada, reativa o campo
                    if ($child.length) {
                        $child.prop('disabled', false);
                    }

                    if ($subWrap.length) {
                        if ($subWrap.is(':hidden')) {
                            $subWrap.slideDown(180);
                        }
                    }
                    if ($actions.length) {
                        $actions.show();
                    }
                }

                function uptLoadSubcategories($wrapper, parentId, preselectId) {
                    var $child = $wrapper.find('#upt-category-child');
                    if (!$child.length) return;

                    $child.prop('disabled', true);
                    $.ajax({
                        url: upt_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'upt_get_child_categories',
                            nonce: upt_ajax.nonce,
                            parent_id: parentId
                        },
                        success: function (resp) {
                            if (!resp || !resp.success) {
                                $child.html('<option value="">— Selecione —</option>');
                                return;
                            }
                            var items = (resp.data && resp.data.items) ? resp.data.items : [];
                            var html = '<option value="">— Selecione —</option>';
                            for (var i = 0; i < items.length; i++) {
                                var it = items[i];
                                var sel = (preselectId && String(preselectId) === String(it.term_id)) ? ' selected' : '';
                                html += '<option value="' + it.term_id + '"' + sel + '>' + it.name + '</option>';
                            }
                            $child.html(html);
                        },
                        complete: function () {
                            $child.prop('disabled', false);
                            uptToggleSubcatUI($wrapper);
                            uptSyncFinalCategoryValue($wrapper);
                        }
                    });
                }

                // Inicializa para formulários já renderizados (inclui injeções via AJAX)
                try {
                    $('.upt-category-parent').each(function () {
                        var $row = $(this).closest('.upt-field-row');
                        var parentVal = $(this).val();
                        if (parentVal) {
                            var childVal = $row.find('#upt-category-child').val();
                            uptLoadSubcategories($row, parentVal, childVal);
                        }
                        uptToggleSubcatUI($row);
                        uptSyncFinalCategoryValue($row);
                    });
                } catch (e) {
                    // noop
                }

                $('body').on('change', '#upt-category-parent', function () {
                    var $wrapper = $(this).closest('.upt-field-row');
                    var parentId = $(this).val();
                    // reset child
                    $wrapper.find('#upt-category-child').val('');
                    uptSyncFinalCategoryValue($wrapper);
                    if (!parentId) {
                        uptToggleSubcatUI($wrapper);
                        return;
                    }
                    uptLoadSubcategories($wrapper, parentId, null);
                });

                $('body').on('change', '#upt-category-child', function () {
                    var $wrapper = $(this).closest('.upt-field-row');
                    uptSyncFinalCategoryValue($wrapper);
                });

                // Abrir área criar subcategoria
                $('body').on('click', '.upt-subcat-add', function (e) {
                    e.preventDefault();
                    var $row = $(this).closest('.upt-field-row');
                    var $wrapper = $row.find('.upt-subcategory-wrapper');
                    var parentId = $row.find('#upt-category-parent').val();
                    if (!parentId) { alert('Selecione uma categoria primeiro.'); return; }

                    var $area = $wrapper.find('.upt-subcat-area');
                    $area.find('.upt-subcat-mode').val('create');
                    $area.find('.upt-subcat-term-id').val('0');
                    $area.find('.upt-subcat-name').val('');
                    $area.find('.upt-subcat-parent').val(String(parentId));
                    $area.slideDown(180);
                });

                // Editar subcategoria (renomear/mover)
                $('body').on('click', '.upt-subcat-edit', function (e) {
                    e.preventDefault();
                    var $row = $(this).closest('.upt-field-row');
                    var $wrapper = $row.find('.upt-subcategory-wrapper');
                    var $child = $row.find('#upt-category-child');
                    var termId = parseInt($child.val(), 10);
                    if (!termId || termId <= 0) { alert('Selecione uma subcategoria para editar.'); return; }

                    var currentName = $child.find('option:selected').text();
                    var parentId = $row.find('#upt-category-parent').val();

                    var $area = $wrapper.find('.upt-subcat-area');
                    $area.find('.upt-subcat-mode').val('edit');
                    $area.find('.upt-subcat-term-id').val(String(termId));
                    $area.find('.upt-subcat-name').val(currentName);
                    $area.find('.upt-subcat-parent').val(String(parentId));
                    $area.slideDown(180);
                });

                // Excluir subcategoria
                $('body').on('click', '.upt-subcat-delete', function (e) {
                    e.preventDefault();
                    var $row = $(this).closest('.upt-field-row');
                    var $wrapper = $row.find('.upt-subcategory-wrapper');
                    var $child = $row.find('#upt-category-child');
                    var termId = parseInt($child.val(), 10);
                    if (!termId || termId <= 0) { alert('Selecione uma subcategoria para excluir.'); return; }
                    var name = $child.find('option:selected').text();
                    uptConfirmDialog('Tem certeza que deseja excluir a subcategoria "' + name + '"? Esta ação não pode ser desfeita.', function () {
                        $.ajax({
                            url: upt_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'upt_delete_category',
                                nonce: upt_ajax.nonce,
                                term_id: termId
                            },
                            success: function (resp) {
                                if (resp && resp.success) {
                                    $child.find('option[value="' + termId + '"]').remove();
                                    $child.val('');
                                    uptSyncFinalCategoryValue($row);
                                    if (window.uptNotify) { window.uptNotify('subcategory_deleted', 'Subcategoria excluída.'); }
                                } else {
                                    alert('Erro ao excluir: ' + (resp && resp.data && resp.data.message ? resp.data.message : 'Erro desconhecido.'));
                                }
                            },
                            error: function () { alert('Erro de comunicação ao tentar excluir.'); }
                        });
                    });
                });

                // Cancelar área subcat
                $('body').on('click', '.upt-subcat-cancel', function (e) {
                    e.preventDefault();
                    var $area = $(this).closest('.upt-subcat-area');
                    $area.slideUp(180);
                    $area.find('.upt-subcat-status').text('');
                });

                // Salvar subcat (create/edit)
                $('body').on('click', '.upt-subcat-save', function (e) {
                    e.preventDefault();
                    var $area = $(this).closest('.upt-subcat-area');
                    var $row = $area.closest('.upt-field-row');
                    var mode = $area.find('.upt-subcat-mode').val();
                    var name = ($area.find('.upt-subcat-name').val() || '').trim();
                    var newParentId = $area.find('.upt-subcat-parent').val();
                    var $status = $area.find('.upt-subcat-status');

                    if (!name) { $status.text('Digite um nome.').css('color', 'red'); return; }
                    if (!newParentId) { $status.text('Selecione a categoria pai.').css('color', 'red'); return; }

                    $status.text('').css('color', 'inherit');

                    if (mode === 'create') {
                        $.ajax({
                            url: upt_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'upt_add_category',
                                nonce: upt_ajax.nonce,
                                new_cat_name: name,
                                parent_id: newParentId
                            },
                            success: function (resp) {
                                if (resp && resp.success) {
                                    var termId = resp.data.term_id;
                                    var termName = resp.data.name;
                                    var $child = $row.find('#upt-category-child');
                                    // garante que estamos no parent certo
                                    if (String($row.find('#upt-category-parent').val()) !== String(newParentId)) {
                                        $row.find('#upt-category-parent').val(String(newParentId)).trigger('change');
                                    }
                                    $child.append(new Option(termName, termId, true, true)).val(String(termId));
                                    uptSyncFinalCategoryValue($row);
                                    $area.slideUp(180);
                                    if (window.uptNotify) { window.uptNotify('subcategory_created', 'Subcategoria criada: ' + termName); }
                                } else {
                                    $status.text(resp && resp.data && resp.data.message ? resp.data.message : 'Erro ao salvar.').css('color', 'red');
                                }
                            },
                            error: function () {
                                $status.text('Erro de comunicação.').css('color', 'red');
                            }
                        });
                        return;
                    }

                    // edit
                    var termIdEdit = parseInt($area.find('.upt-subcat-term-id').val(), 10);
                    if (!termIdEdit || termIdEdit <= 0) { $status.text('Subcategoria inválida.').css('color', 'red'); return; }

                    $.ajax({
                        url: upt_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'upt_update_category',
                            nonce: upt_ajax.nonce,
                            term_id: termIdEdit,
                            new_name: name,
                            new_parent_id: newParentId
                        },
                        success: function (resp) {
                            if (resp && resp.success) {
                                var updatedName = resp.data.name;
                                var updatedParent = resp.data.parent;
                                // Se mudou de categoria pai, recarrega lista
                                if (String($row.find('#upt-category-parent').val()) !== String(updatedParent)) {
                                    $row.find('#upt-category-parent').val(String(updatedParent));
                                    uptLoadSubcategories($row, updatedParent, termIdEdit);
                                } else {
                                    var $child = $row.find('#upt-category-child');
                                    $child.find('option[value="' + termIdEdit + '"]').text(updatedName);
                                    $child.val(String(termIdEdit));
                                    uptSyncFinalCategoryValue($row);
                                }
                                $area.slideUp(180);
                                if (window.uptNotify) { window.uptNotify('subcategory_updated', resp.data.message || 'Subcategoria atualizada.'); }
                            } else {
                                $status.text(resp && resp.data && resp.data.message ? resp.data.message : 'Erro ao atualizar.').css('color', 'red');
                            }
                        },
                        error: function () {
                            $status.text('Erro de comunicação.').css('color', 'red');
                        }
                    });
                });
            }

            uptBindInlineCategoryActions();

            var currentMediaWrapper;
            var currentFieldType;
            var currentFieldKey;

            // Armazena a seleção do painel de mídia enquanto o painel existir (por campo)
            window.uptGallerySelectionMap = window.uptGallerySelectionMap || {};

    /* ========================================================================
     * SECTION 13: GALLERY MODAL
     * Gallery selection map, iframe injection, pagination vars, media
     * select/remove, font asset injection into iframe
     * ======================================================================== */

            function uptIsImageOrVideo(media) {
                // Filtro desativado: retorna sempre true para não bloquear nenhum tipo de mídia.
                return true;
            }

            function uptUpdateSelectionMapFromWrapper(wrapper, remainingIds) {
                if (!wrapper || !wrapper.length) { return; }
                var fieldKey = wrapper.data('uptFieldKey');
                if (!fieldKey) { return; }

                window.uptGallerySelectionMap = window.uptGallerySelectionMap || {};
                var normalized = (remainingIds || []).map(function (id) { return String(id); }).filter(Boolean);

                if (normalized.length) {
                    window.uptGallerySelectionMap[fieldKey] = normalized;
                } else {
                    delete window.uptGallerySelectionMap[fieldKey];
                }
            }


            function uptComputeFieldKey(wrapper, type) {
                if (!wrapper || !wrapper.length) { return null; }

                var existingKey = wrapper.data('uptFieldKey');
                if (existingKey) {
                    return existingKey;
                }

                var fieldInput = null;
                if (type === 'gallery') {
                    fieldInput = wrapper.find('.gallery-ids-input').first();
                } else if (type === 'image') {
                    fieldInput = wrapper.find('.upt-image-id-input').first();
                } else if (type === 'video') {
                    fieldInput = wrapper.find('.upt-video-id-input').first();
                }

                var fieldKey = null;
                if (fieldInput && fieldInput.length) {
                    fieldKey = fieldInput.attr('name') || fieldInput.attr('id');
                }

                if (!fieldKey) {
                    var itemWrapper = wrapper.closest('[data-item-id]');
                    if (itemWrapper.length) {
                        fieldKey = String(itemWrapper.data('item-id') || '') + ':' + type;
                    }
                }

                if (!fieldKey) {
                    return null;
                }

                wrapper.data('uptFieldKey', fieldKey);
                return fieldKey;
            }

            function uptRebuildSelectionMapFromFields(root) {
                var $root = root && root.length ? root : $(document);
                window.uptGallerySelectionMap = window.uptGallerySelectionMap || {};

                // Campos de galeria (múltiplos IDs)
                $root.find('.upt-gallery-wrapper').each(function () {
                    var wrapper = $(this);
                    var input = wrapper.find('.gallery-ids-input').first();
                    if (!input.length) { return; }
                    var raw = (input.val() || '').split(',').filter(Boolean);
                    if (!raw.length) { return; }

                    var fieldKey = uptComputeFieldKey(wrapper, 'gallery');
                    if (!fieldKey) { return; }

                    window.uptGallerySelectionMap[fieldKey] = raw.map(function (id) { return String(id); });
                });

                // Campo de imagem única
                $root.find('.upt-image-upload-wrapper').each(function () {
                    var wrapper = $(this);
                    var input = wrapper.find('.upt-image-id-input').first();
                    if (!input.length) { return; }
                    var val = $.trim(input.val() || '');
                    if (!val) { return; }

                    var fieldKey = uptComputeFieldKey(wrapper, 'image');
                    if (!fieldKey) { return; }

                    window.uptGallerySelectionMap[fieldKey] = [String(val)];
                });

                // Campo de vídeo único
                $root.find('.upt-video-upload-wrapper').each(function () {
                    var wrapper = $(this);
                    var input = wrapper.find('.upt-video-id-input').first();
                    if (!input.length) { return; }
                    var val = $.trim(input.val() || '');
                    if (!val) { return; }

                    var fieldKey = uptComputeFieldKey(wrapper, 'video');
                    if (!fieldKey) { return; }

                    window.uptGallerySelectionMap[fieldKey] = [String(val)];
                });
            }

            // Expõe para evitar ReferenceError em chamadas antecipadas
            window.uptRebuildSelectionMapFromFields = uptRebuildSelectionMapFromFields;

            function uptTryRebuildSelection(attemptsLeft) {
                try {
                    if (typeof uptRebuildSelectionMapFromFields === 'function') {
                        uptRebuildSelectionMapFromFields($(document));
                        return;
                    }
                    if (attemptsLeft > 0) {
                        setTimeout(function () { uptTryRebuildSelection(attemptsLeft - 1); }, 150);
                    } else if (console && console.warn) {
                        console.warn('upt: uptRebuildSelectionMapFromFields indisponível no carregamento inicial');
                    }
                } catch (e) {
                    console && console.warn && console.warn('upt: erro ao reconstruir seleção de mídia', e);
                }
            }

            setTimeout(function () { uptTryRebuildSelection(6); }, 0);

            window.uptGalleryPersistentSelection = window.uptGalleryPersistentSelection || [];

            function uptEnsureGalleryModalShell() {
                var $galleryModal = $('#upt-gallery-modal');
                if ($galleryModal.length) {
                    return $galleryModal;
                }
                $('body').append('<div id="upt-gallery-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999999; padding: 5vh 5vw;"><div id="upt-gallery-modal-content" style="width:100%; height:100%; background:white;"><iframe src="about:blank" style="width:100%; height:100%; border:0;"></iframe></div></div>');
                return $('#upt-gallery-modal');
            }

            function uptInjectFontAssetsIntoIframe(iframeEl) {
                try {
                    if (!iframeEl || !iframeEl.contentDocument) {
                        return;
                    }
                    var iframeDoc = iframeEl.contentDocument;
                    if (!iframeDoc.head || !document.head) {
                        return;
                    }

                    function ensureLink(rel, href, id) {
                        if (!href) return;
                        var selector = 'link[rel="' + rel + '"][href="' + href.replace(/"/g, '\\"') + '"]';
                        if (iframeDoc.head.querySelector(selector)) {
                            return;
                        }
                        var link = iframeDoc.createElement('link');
                        link.rel = rel;
                        link.href = href;
                        if (id) link.id = id;
                        iframeDoc.head.appendChild(link);
                    }

                    function ensureStyle(id, cssText) {
                        if (!cssText) return;
                        if (id && iframeDoc.getElementById(id)) {
                            return;
                        }
                        if (!id) {
                            var existing = Array.prototype.slice.call(iframeDoc.head.querySelectorAll('style'))
                                .some(function (s) { return (s.textContent || '') === cssText; });
                            if (existing) return;
                        }
                        var st = iframeDoc.createElement('style');
                        if (id) st.id = id;
                        st.textContent = cssText;
                        iframeDoc.head.appendChild(st);
                    }

                    var headLinks = Array.prototype.slice.call(document.head.querySelectorAll('link'));
                    headLinks.forEach(function (l) {
                        var rel = (l.getAttribute('rel') || '').toLowerCase();
                        var href = l.getAttribute('href') || '';
                        var id = l.getAttribute('id') || '';

                        if (!href) return;

                        var isFontPreconnect = rel === 'preconnect' && /fonts\.(gstatic|googleapis)\.com/i.test(href);
                        var isFontStylesheet = rel === 'stylesheet' && (/fonts\.googleapis\.com/i.test(href) || /google-fonts/i.test(id) || (/elementor/i.test(id) && /font/i.test(id)));

                        if (isFontPreconnect) {
                            ensureLink('preconnect', href, id);
                        } else if (isFontStylesheet) {
                            ensureLink('stylesheet', href, id);
                        }
                    });

                    var headStyles = Array.prototype.slice.call(document.head.querySelectorAll('style'));
                    headStyles.forEach(function (s) {
                        var id = s.getAttribute('id') || '';
                        var cssText = s.textContent || '';
                        if (!cssText) return;

                        var looksLikeFontFace = /@font-face\b/i.test(cssText);
                        var isElementorFontStyle = /elementor/i.test(id) && /font/i.test(id);
                        if (looksLikeFontFace || isElementorFontStyle) {
                            ensureStyle(id || null, cssText);
                        }
                    });
                } catch (e) { }
            }

            setTimeout(function () {
                try {
                    uptEnsureGalleryModalShell();
                } catch (e) { }
            }, 0);

            function uptBuildGalleryPaginationVars(wrapper) {
                try {
                    var $wrapper = wrapper && wrapper.jquery ? wrapper : $(wrapper);
                    if (!$wrapper || !$wrapper.length) {
                        return null;
                    }

                    var $container = $wrapper.closest('.upt-items-container');
                    if (!$container.length) {
                        $container = $('.upt-items-container').first();
                    }
                    if (!$container.length) {
                        return null;
                    }

                    var vars = {};

                    function pushVar(name, value) {
                        if (!name) return;
                        if (typeof value !== 'string') return;
                        var v = value.trim();
                        if (!v) return;
                        vars[name] = v;
                    }

                    function paddingString(style) {
                        return [style.paddingTop, style.paddingRight, style.paddingBottom, style.paddingLeft].join(' ');
                    }

                    function borderString(style) {
                        return [style.borderTopWidth, style.borderTopStyle, style.borderTopColor].join(' ');
                    }

                    function applyBlockVars(prefix, el) {
                        if (!el) return;
                        var cs = getComputedStyle(el);
                        pushVar(prefix + '-color', cs.color);
                        pushVar(prefix + '-background', cs.background);
                        pushVar(prefix + '-border', borderString(cs));
                        pushVar(prefix + '-radius', cs.borderRadius);
                        pushVar(prefix + '-padding', paddingString(cs));
                        pushVar(prefix + '-shadow', cs.boxShadow);
                        pushVar(prefix + '-font-family', cs.fontFamily);
                        pushVar(prefix + '-font-size', cs.fontSize);
                        pushVar(prefix + '-font-weight', cs.fontWeight);
                        pushVar(prefix + '-line-height', cs.lineHeight);
                        pushVar(prefix + '-letter-spacing', cs.letterSpacing);
                        pushVar(prefix + '-text-transform', cs.textTransform);
                    }

                    function collectThemeVars(sourceEl) {
                        if (!sourceEl) return;
                        var cs = getComputedStyle(sourceEl);
                        var themeVars = [
                            '--fc-font-family',
                            '--fc-text-dark',
                            '--fc-text-light',
                            '--fc-border-color',
                            '--fc-container-bg',
                            '--fc-body-bg',
                            '--fc-border-radius',
                            '--fc-box-shadow',
                            '--fc-primary-color'
                        ];
                        themeVars.forEach(function (name) {
                            pushVar(name, cs.getPropertyValue(name));
                        });
                    }

                    var $themeRoot = $container.closest('.upt-preset-hostinger');
                    if (!$themeRoot.length) {
                        $themeRoot = $('.upt-preset-hostinger').first();
                    }
                    collectThemeVars($themeRoot.length ? $themeRoot[0] : document.documentElement);

                    var $probe = $container.find('.upt-gallery-pagination-style-probe').first();
                    var $pagination = $probe.length ? $probe.find('.upt-gallery-pagination').first() : $();

                    if ($pagination.length) {
                        var pStyle = getComputedStyle($pagination[0]);
                        pushVar('--fc-gp-container-justify', pStyle.justifyContent);
                        pushVar('--fc-gp-container-gap', pStyle.gap);
                        pushVar('--fc-gp-container-padding', paddingString(pStyle));
                        pushVar('--fc-gp-container-margin-top', pStyle.marginTop);
                        pushVar('--fc-gp-container-background', pStyle.background);
                        pushVar('--fc-gp-container-border', borderString(pStyle));
                        pushVar('--fc-gp-container-radius', pStyle.borderRadius);
                        pushVar('--fc-gp-container-shadow', pStyle.boxShadow);
                    }

                    var $note = $probe.find('.upt-gallery-pagination-note').first();
                    if ($note.length) {
                        var nStyle = getComputedStyle($note[0]);
                        pushVar('--fc-gp-note-color', nStyle.color);
                        pushVar('--fc-gp-note-font-family', nStyle.fontFamily);
                        pushVar('--fc-gp-note-font-size', nStyle.fontSize);
                        pushVar('--fc-gp-note-font-weight', nStyle.fontWeight);
                        pushVar('--fc-gp-note-line-height', nStyle.lineHeight);
                        pushVar('--fc-gp-note-letter-spacing', nStyle.letterSpacing);
                        pushVar('--fc-gp-note-text-transform', nStyle.textTransform);
                    }

                    var $dots = $probe.find('.upt-gallery-page-dots').first();
                    if ($dots.length) {
                        var dStyle = getComputedStyle($dots[0]);
                        pushVar('--fc-gp-dots-color', dStyle.color);
                    }

                    var $numNormal = $probe.find('.upt-gallery-page-number').not('.is-hover').not('.is-current').first();
                    var $numHover = $probe.find('.upt-gallery-page-number.is-hover').first();
                    var $numActive = $probe.find('.upt-gallery-page-number.is-current').first();
                    if ($numNormal.length) applyBlockVars('--fc-gp-number', $numNormal[0]);
                    if ($numHover.length) applyBlockVars('--fc-gp-number-hover', $numHover[0]);
                    if ($numActive.length) applyBlockVars('--fc-gp-number-active', $numActive[0]);

					var $navNormal = $probe.find('.upt-gallery-page-prev').not('.is-hover').first();
					if (!$navNormal.length) {
						$navNormal = $probe.find('.upt-gallery-page-next').not('.is-hover').first();
					}
					var $navHover = $probe.find('.upt-gallery-page-prev.is-hover').first();
					if (!$navHover.length) {
						$navHover = $probe.find('.upt-gallery-page-next.is-hover').first();
					}
					if ($navNormal.length) applyBlockVars('--fc-gp-nav', $navNormal[0]);
					if ($navHover.length) applyBlockVars('--fc-gp-nav-hover', $navHover[0]);

                    var $lmNormal = $probe.find('.upt-gallery-load-more-btn').not('.is-hover').first();
                    var $lmHover = $probe.find('.upt-gallery-load-more-btn.is-hover').first();
                    if ($lmNormal.length) applyBlockVars('--fc-gp-loadmore', $lmNormal[0]);
                    if ($lmHover.length) applyBlockVars('--fc-gp-loadmore-hover', $lmHover[0]);

                    if (!Object.keys(vars).length) {
                        return null;
                    }
                    return vars;
                } catch (e) {
                    return null;
                }
            }

            window.uptBuildGalleryPaginationVars = uptBuildGalleryPaginationVars;

            function openuptGallery(wrapper, type) {
                

                currentMediaWrapper = wrapper;
                currentFieldType = type;
                var fieldKey = uptComputeFieldKey(wrapper, type);
                currentFieldKey = fieldKey;
                var ids = [];
                if (type === 'gallery') {
                    var input = wrapper.find('.gallery-ids-input').first();
                    if (input.length) {
                        ids = (input.val() || '').split(',').filter(Boolean);
                    }
                } else if (type === 'image') {
                    var imgInput = wrapper.find('.upt-image-id-input').first();
                    if (imgInput.length && imgInput.val()) {
                        ids = [String(imgInput.val())];
                    }
                } else if (type === 'video') {
                    var vidInput = wrapper.find('.upt-video-id-input').first();
                    if (vidInput.length && vidInput.val()) {
                        ids = [String(vidInput.val())];
                    }
                } else if (type === 'pdf') {
                    var pdfInput = wrapper.find('.upt-pdf-id-input').first();
                    if (pdfInput.length && pdfInput.val()) {
                        ids = [String(pdfInput.val())];
                    }
                }
                uptUpdateSelectionMapFromWrapper(wrapper, ids);


                var dashboardContainer = wrapper.closest('.upt-preset-hostinger');
                var accentColor = '';

                if (dashboardContainer.length) {
                    var computedStyle = getComputedStyle(dashboardContainer[0]);
                    accentColor = computedStyle.getPropertyValue('--fc-primary-color').trim();
                }

                var ajaxCfg = uptGetAjaxConfig();
                var iframeSrc = ajaxCfg.admin_url + 'admin.php?page=upt_gallery&noheader=true';
                if (type === 'gallery') {
                    iframeSrc += '&selection=multiple';
                } else if (type === 'image' || type === 'video' || type === 'pdf') {
                    iframeSrc += '&media_type=' + encodeURIComponent(type);
                }

                var $itemsContainer = wrapper.closest('.upt-items-container');
                if (!$itemsContainer.length) {
                    $itemsContainer = $('.upt-items-container').first();
                }

                if ($itemsContainer.length && String($itemsContainer.data('gallery-pagination') || '') === 'yes') {
                    var galleryPerPage = parseInt($itemsContainer.data('gallery-per-page'), 10);
                    var galleryPaginationType = String($itemsContainer.data('gallery-pagination-type') || '');
                    var galleryInfiniteTrigger = String($itemsContainer.data('gallery-pagination-infinite-trigger') || '');
                    var galleryNumbersNav = String($itemsContainer.data('gallery-pagination-numbers-nav') || '');
                    var galleryLoadMoreLabel = String($itemsContainer.data('gallery-pagination-load-more-label') || '');

                    iframeSrc += '&gallery_pagination=yes';
                    if (galleryPerPage && galleryPerPage > 0) {
                        iframeSrc += '&gallery_per_page=' + encodeURIComponent(galleryPerPage);
                    }
                    if (galleryPaginationType) {
                        iframeSrc += '&gallery_pagination_type=' + encodeURIComponent(galleryPaginationType);
                    }
                    if (galleryNumbersNav) {
                        iframeSrc += '&gallery_pagination_numbers_nav=' + encodeURIComponent(galleryNumbersNav);
                    }
                    if (galleryInfiniteTrigger) {
                        iframeSrc += '&gallery_pagination_infinite_trigger=' + encodeURIComponent(galleryInfiniteTrigger);
                    }
                    if (galleryLoadMoreLabel) {
                        iframeSrc += '&gallery_pagination_load_more_label=' + encodeURIComponent(galleryLoadMoreLabel);
                    }
                }
                if (accentColor) {
                    iframeSrc += '&accent_color=' + encodeURIComponent(accentColor);
                }

                var galleryVars = uptBuildGalleryPaginationVars(wrapper);
                if (galleryVars) {
                    iframeSrc += '&fc_gallery_vars=' + encodeURIComponent(JSON.stringify(galleryVars));
                }

                uptDebugLog('media-modal: open request', {
                    fieldKey: fieldKey,
                    fieldType: type,
                    ajax_url: ajaxCfg.ajax_url,
                    admin_url: ajaxCfg.admin_url,
                    iframeSrc: iframeSrc,
                    accentColor: accentColor
                });

                var $galleryModal = uptEnsureGalleryModalShell();
                setTimeout(function () {
                    try {
                        $galleryModal.find('iframe').attr('src', iframeSrc);
                    } catch (e) { }
                }, 0);

                var $iframe = $('#upt-gallery-modal iframe');
                $iframe.off('load.uptSelection').on('load.uptSelection', function () {
                    var ids = [];

                    try {
                        uptInjectFontAssetsIntoIframe(this);
                    } catch (e) { }

                    if (currentMediaWrapper && currentFieldType === 'gallery') {
                        var input = currentMediaWrapper.find('.gallery-ids-input').first();
                        if (input.length) {
                            ids = (input.val() || '').split(',').filter(Boolean);
                        }
                    } else if (currentMediaWrapper && currentFieldType === 'image') {
                        var imgInput = currentMediaWrapper.find('.upt-image-id-input').first();
                        if (imgInput.length && imgInput.val()) {
                            ids = [String(imgInput.val())];
                        }
                    } else if (currentMediaWrapper && currentFieldType === 'video') {
                        var vidInput = currentMediaWrapper.find('.upt-video-id-input').first();
                        if (vidInput.length && vidInput.val()) {
                            ids = [String(vidInput.val())];
                        }
                    } else if (currentMediaWrapper && currentFieldType === 'pdf') {
                        var pdfInput = currentMediaWrapper.find('.upt-pdf-id-input').first();
                        if (pdfInput.length && pdfInput.val()) {
                            ids = [String(pdfInput.val())];
                        }
                    }

                    if (ids && ids.length) {
                        try {
                            this.contentWindow.postMessage({
                                uptRestoreSelection: ids
                            }, '*');
                        } catch (e) { }
                    }
                });
                // Ensure modal uses flex layout and becomes visible (preserve centering)
                var $gm = $galleryModal;
                $gm.stop(true, true).css({ display: 'flex', opacity: 1 }).show();
                $('html, body').addClass('modal-open');
            }

            window.addEventListener('message', function (event) {
                if (!event.data) {
                    return;
                }
                if (event.data.uptGallerySelection) {
                    var mediaData = event.data.uptGallerySelection || [];

                    // Atualiza a seleção persistente apenas quando o usuário clica em "Usar"
                    if (currentFieldKey) {
                        window.uptGallerySelectionMap = window.uptGallerySelectionMap || {};
                        window.uptGallerySelectionMap[currentFieldKey] = (mediaData || []).map(function (item) {
                            return String(item.id);
                        });
                        window.uptGalleryPersistentSelection = window.uptGallerySelectionMap[currentFieldKey];
                    } else {
                        window.uptGalleryPersistentSelection = (mediaData || []).map(function (item) {
                            return String(item.id);
                        });
                    }

                    if (currentMediaWrapper && currentFieldType === 'editor' && typeof currentEditorInstance !== 'undefined' && currentEditorInstance) {
                        var html = '';
                        var media = mediaData || [];
                        media.forEach(function (m) {
                            var src = m.full_url || m.thumbnail_url;
                            var isVideo = m.type === 'video';
                            if (!isVideo && src) {
                                var ext = src.split('.').pop().toLowerCase();
                                if (['mp4', 'webm', 'ogg', 'mov', 'm4v'].indexOf(ext) !== -1) {
                                    isVideo = true;
                                }
                            }
                            if (isVideo) {
                                html += '<p><video controls src="' + src + '" style="max-width:100%; height:auto;"></video></p><p><br></p>';
                            } else {
                                html += '<img src="' + src + '" alt="' + (m.alt || '') + '" />';
                            }
                        });
                        currentEditorInstance.focus();
                        if (savedRange) {
                            var sel = window.getSelection();
                            if (sel && sel.removeAllRanges) {
                                sel.removeAllRanges();
                                sel.addRange(savedRange);
                            }
                            document.execCommand('insertHTML', false, html);
                        } else {
                            currentEditorInstance.append(html);
                        }
                        currentEditorInstance.trigger('input');
                    }

                    if (currentMediaWrapper) {
                        if (currentFieldType === 'gallery') {
                            var previews = currentMediaWrapper.find('.gallery-previews');
                            var input = currentMediaWrapper.find('.gallery-ids-input');
                            var currentIds = input.val() ? input.val().split(',').filter(Boolean) : [];

                            mediaData.forEach(function (data) {
                                if (currentIds.indexOf(String(data.id)) === -1) {
                                    currentIds.push(data.id);

                                    var fullUrl = (data.full_url || data.thumbnail_url || '');
                                    var thumbUrl = data.thumbnail_url || '';
                                    var isVideo = /\.(mp4|webm|ogg|mov|m4v)$/i.test(fullUrl) || (data.type === 'video');
                                    var isPdf = (data.type === 'pdf') || (/\.pdf$/i.test(fullUrl));
                                    var overlaySrc = (typeof upt_ajax !== 'undefined' && upt_ajax.transparent_png)
                                        ? upt_ajax.transparent_png
                                        : thumbUrl;

                                    var thumbHtml;
                                    if (isVideo) {
                                        thumbHtml =
                                            '<div class="upt-video-thumb">' +
                                            '<video muted playsinline preload="metadata" src="' + fullUrl + '#t=1"></video>' +
                                            '<img class="upt-video-overlay" src="' + overlaySrc + '" alt="">' +
                                            '</div>';
                                    } else if (isPdf) {
                                        thumbHtml = '<img src="' + (thumbUrl || fullUrl) + '">';
                                    } else {
                                        thumbHtml = '<img src="' + thumbUrl + '">';
                                    }

                                    previews.append(
                                        '<div class="gallery-preview-item" data-id="' + data.id + '" data-type="' + (data.type || (isPdf ? 'pdf' : (isVideo ? 'video' : 'image'))) + '" data-full-url="' + (fullUrl || '') + '">' +
                                        thumbHtml +
                                        '<a href="#" class="remove-image">×</a>' +
                                        '</div>'
                                    );
                                }
                            });
                            input.val(currentIds.join(',')).trigger('change');
                        } else if (currentFieldType === 'image') {
                            var singleImageData = mediaData[0];
                            if (singleImageData) {
                                var imageIdInput = currentMediaWrapper.find('.upt-image-id-input');
                                var previewWrapper = currentMediaWrapper.find('.image-preview-wrapper');
                                var removeButton = currentMediaWrapper.find('.upt-remove-image');

                                imageIdInput.val(singleImageData.id);

                                // Se existir um hidden de featured_image_id (campo nativo) DENTRO deste wrapper,
                                // mantém sincronizado apenas para o campo de capa (core_featured_image).
                                var featuredInput = currentMediaWrapper.find('input[name="featured_image_id"]');
                                if (featuredInput.length) {
                                    featuredInput.val(singleImageData.id);
                                }

                                var imageTitle = uptEnsureTitleHasExtension(
                                    singleImageData && (singleImageData.filename || singleImageData.name || ''),
                                    singleImageData.full_url || singleImageData.thumbnail_url
                                );

                                previewWrapper.html('<img src="' + singleImageData.thumbnail_url + '">');
                                if (imageTitle) {
                                    $('<div class="upt-media-title"></div>')
                                        .text(imageTitle)
                                        .attr('title', imageTitle)
                                        .appendTo(previewWrapper);
                                }
                                removeButton.removeClass('hidden');
                            }

                        } else if (currentFieldType === 'video') {
                            var singleVideoData = mediaData[0];
                            if (singleVideoData) {
                                var videoIdInput = currentMediaWrapper.find('.upt-video-id-input');
                                var previewWrapper = currentMediaWrapper.find('.video-preview-wrapper');
                                var removeButton = currentMediaWrapper.find('.upt-remove-video');

                                videoIdInput.val(singleVideoData.id);
                                var fullUrl = (singleVideoData.full_url || singleVideoData.thumbnail_url || '');
                                var overlaySrc = (typeof upt_ajax !== 'undefined' && upt_ajax.transparent_png)
                                    ? upt_ajax.transparent_png
                                    : (singleVideoData.thumbnail_url || '');
                                var thumbHtml =
                                    '<div class="upt-video-thumb">' +
                                    '<video muted playsinline preload="metadata" src="' + fullUrl + '#t=1"></video>' +
                                    '<img class="upt-video-overlay" src="' + overlaySrc + '" alt="">' +
                                    '</div>';
                                var videoTitle = uptEnsureTitleHasExtension(
                                    singleVideoData && (singleVideoData.filename || singleVideoData.name || ''),
                                    fullUrl
                                );
                                previewWrapper.html(thumbHtml);
                                if (videoTitle) {
                                    $('<div class="upt-media-title"></div>')
                                        .text(videoTitle)
                                        .attr('title', videoTitle)
                                        .appendTo(previewWrapper);
                                }
                                removeButton.removeClass('hidden');
                            }
                        }
                        else if (currentFieldType === 'pdf') {
                            var singlePdfData = mediaData[0];
                            if (singlePdfData) {
                                var pdfIdInput = currentMediaWrapper.find('.upt-pdf-id-input');
                                var previewWrapper = currentMediaWrapper.find('.pdf-preview-wrapper');
                                var removeButton = currentMediaWrapper.find('.upt-remove-pdf');

                                var pdfFallback = (typeof upt_ajax !== 'undefined' && upt_ajax.pdf_placeholder) ? upt_ajax.pdf_placeholder : '';

                                pdfIdInput.val(singlePdfData.id);
                                var fullUrl = (singlePdfData.full_url || '');
                                var iconUrl = (singlePdfData.thumbnail_url || pdfFallback || '');
                                var pdfOnError = pdfFallback ? ' onerror="this.onerror=null;this.src=\'' + pdfFallback + '\';"' : '';
                                var mediaTitle = uptEnsureTitleHasExtension(
                                    singlePdfData && (singlePdfData.filename || singlePdfData.name || ''),
                                    fullUrl || iconUrl
                                );
                                var thumbHtml =
                                    '<div class="upt-pdf-thumb" data-type="pdf" data-full-url="' + fullUrl + '">' +
                                        '<img src="' + iconUrl + '" alt=""' + pdfOnError + '>' +
                                    '</div>';
                                previewWrapper.html(thumbHtml);
                                if (mediaTitle) {
                                    $('<div class="upt-media-title"></div>')
                                        .text(mediaTitle)
                                        .attr('title', mediaTitle)
                                        .appendTo(previewWrapper.find('.upt-pdf-thumb'));
                                }
                                removeButton.removeClass('hidden');
                            }
                        }
                    }
                    $('#upt-gallery-modal').fadeOut(200);
                    $('html, body').removeClass('modal-open');
                } else if (event.data.uptGalleryClose) {
                    $('#upt-gallery-modal').fadeOut(200);
                    $('html, body').removeClass('modal-open');
                }
            });

            $('body').on('click', '#upt-gallery-modal', function (e) {
                if (e.target === this) {
                    $('#upt-gallery-modal').fadeOut(200);
                    $('html, body').removeClass('modal-open');
                }
            });

            $('body').on('click', '.upt-add-image', function (e) {
                e.preventDefault();
                
                var wrapper = $(this).closest('.upt-image-upload-wrapper');
                var ajaxCfg = uptGetAjaxConfig();
                var fieldKey = uptComputeFieldKey(wrapper, 'image');
                var itemId = wrapper.closest('[data-item-id]').data('item-id') || null;
                uptDebugLog('media-button: add-image click', {
                    ajaxCfg: ajaxCfg,
                    wrapperFound: !!wrapper.length,
                    fieldKey: fieldKey,
                    itemId: itemId,
                    currentSelection: (window.uptGallerySelectionMap && fieldKey) ? (window.uptGallerySelectionMap[fieldKey] || null) : null
                });
                
                openuptGallery(wrapper, 'image');
            });

            $('body').on('click', '.upt-add-video', function (e) {
                e.preventDefault();
                var wrapper = $(this).closest('.upt-video-upload-wrapper');
                uptDebugLog('media-button: add-video click', {
                    ajaxCfg: uptGetAjaxConfig(),
                    wrapperFound: !!wrapper.length
                });
                openuptGallery(wrapper, 'video');
            });

            $('body').on('click', '.add-gallery-images', function (e) {
                e.preventDefault();
                var wrapper = $(this).closest('.upt-gallery-wrapper');
                uptDebugLog('media-button: add-gallery click', {
                    ajaxCfg: uptGetAjaxConfig(),
                    wrapperFound: !!wrapper.length
                });
                openuptGallery(wrapper, 'gallery');
            });

            $('body').on('click', '.upt-remove-image', function (e) {
                e.preventDefault();
                var button = $(this);
                var wrapper = button.closest('.upt-image-upload-wrapper');
                var imageIdInput = wrapper.find('.upt-image-id-input');
                var previewWrapper = wrapper.find('.image-preview-wrapper');
                imageIdInput.val('');
                previewWrapper.html('');
                button.addClass('hidden');

                // Atualiza o mapa de seleção persistente para este campo
                uptUpdateSelectionMapFromWrapper(wrapper, []);
            });

            $('body').on('click', '.upt-remove-video', function (e) {
                e.preventDefault();
                var button = $(this);
                var wrapper = button.closest('.upt-video-upload-wrapper');
                var videoIdInput = wrapper.find('.upt-video-id-input');
                var previewWrapper = wrapper.find('.video-preview-wrapper');
                videoIdInput.val('');
                previewWrapper.html('');
                button.addClass('hidden');

                // Atualiza o mapa de seleção persistente para este campo
                uptUpdateSelectionMapFromWrapper(wrapper, []);
            });

            $('body').on('click', '.gallery-preview-item .remove-image', function (e) {
                e.preventDefault();
                var item = $(this).closest('.gallery-preview-item');
                var wrapper = item.closest('.upt-gallery-wrapper');
                var input = wrapper.find('.gallery-ids-input');
                var idToRemove = item.data('id').toString();
                item.remove();
                var currentIds = input.val().split(',');
                var newIds = currentIds.filter(function (id) { return id !== idToRemove; });
                input.val(newIds.join(',')).trigger('change');

                // Atualiza o mapa de seleção persistente removendo apenas o ID excluído
                uptUpdateSelectionMapFromWrapper(wrapper, newIds);
            });
            $('body').on('click', '.upt-pdf-thumb', function(e) {
                e.preventDefault();
                var url = $(this).data('full-url') || '';
                uptOpenPdfPreview(url);
            });

            $('body').on('click', '.gallery-preview-item', function(e) {
                if ($(e.target).closest('.remove-image').length) return;
                var type = $(this).data('type') || '';
                if (type === 'pdf') {
                    var url = $(this).data('full-url') || '';
                    uptOpenPdfPreview(url);
                }
            });



    }

    /* ========================================================================
     * SECTION 14: PER-CATEGORY BUTTONS, TAB COUNTERS, SORTABLE CARDS
     * Item limit per schema, add button visibility, tab counters with AJAX,
     * drag-and-drop card sorting
     * ======================================================================== */

    // Controle de limite de itens por esquema
    
    function normalizeTruth(val) {
        if (typeof val === 'string') {
            val = val.trim().toLowerCase();
        }
        return val === true || val === 'true' || val === 'yes' || val === 'sim' || val === 1 || val === '1';
    }

    function uptEnsurePerCategoryButtonVisibility() {
        var $activeTab = $('.upt-tab-pane.active');
        if (!$activeTab.length) { return; }

        var rawMaxPerCat = $activeTab.data('items-max-per-cat');
        var rawMaxPerCatAttr = $activeTab.attr('data-items-max-per-cat');
        var rawPerCat = $activeTab.data('items-per-cat');
        var rawPerCatAttr = $activeTab.attr('data-items-per-cat');

        var limitMaxPerCat = normalizeTruth(rawMaxPerCat) || normalizeTruth(rawMaxPerCatAttr);
        var limitMinPerCat = normalizeTruth(rawPerCat) || normalizeTruth(rawPerCatAttr);
        var forcePerCategoryMode = limitMaxPerCat || limitMinPerCat;

        uptDebugLogLimits('ensurePerCategoryVisibility', {
            tab: $activeTab.attr('id'),
            rawMaxPerCat: rawMaxPerCat,
            rawMaxPerCatAttr: rawMaxPerCatAttr,
            rawPerCat: rawPerCat,
            rawPerCatAttr: rawPerCatAttr,
            limitMaxPerCat: limitMaxPerCat,
            limitMinPerCat: limitMinPerCat,
            forcePerCategoryMode: forcePerCategoryMode
        });

        if (forcePerCategoryMode) {
            $('.open-add-modal').removeClass('upt-button-disabled').css('display', '').show();
            uptDebugLogLimits('ensurePerCategoryVisibility -> force show');
        }
    }

function uptUpdateAddButtonVisibility() {
                var $activeTab = $('.upt-tab-pane.active');
                var $addButton = $('.open-add-modal');
                if (!$activeTab.length || !$addButton.length) {
                    return;
                }

                var rawMaxPerCat = $activeTab.data('items-max-per-cat');
                var rawMaxPerCatAttr = $activeTab.attr('data-items-max-per-cat');
                var rawPerCat = $activeTab.data('items-per-cat');
                var rawPerCatAttr = $activeTab.attr('data-items-per-cat');

                var limitMaxPerCat = normalizeTruth(rawMaxPerCat) || normalizeTruth(rawMaxPerCatAttr);
                var limitMinPerCat = normalizeTruth(rawPerCat) || normalizeTruth(rawPerCatAttr);
                var forcePerCategoryMode = limitMaxPerCat || limitMinPerCat;

                // Modo por categoria: nunca esconder o botão, ignorando limites globais
                if (forcePerCategoryMode) {
                    $addButton.show().removeClass('upt-button-disabled').css('display', '');
                    uptDebugLogLimits('updateAddButton -> force per category show', {
                        tab: $activeTab.attr('id'),
                        limitMaxPerCat: limitMaxPerCat,
                        limitMinPerCat: limitMinPerCat
                    });
                    return;
                }

                var limit = parseInt($activeTab.data('items-limit'), 10);
                if (!limit || isNaN(limit)) {
                    $addButton.show().removeClass('upt-button-disabled').css('display', '');
                    uptDebugLogLimits('updateAddButton -> no global limit, show', {
                        tab: $activeTab.attr('id'),
                        limit: limit
                    });
                    return;
                }

                var $grid = $activeTab.find('.upt-items-grid').first();
                if (!$grid.length) {
                    $addButton.show().removeClass('upt-button-disabled');
                    uptDebugLogLimits('updateAddButton -> no grid, show', {
                        tab: $activeTab.attr('id'),
                        limit: limit
                    });
                    return;
                }

                // Primeiro tenta contar cards padrão
                var $cards = $grid.find('.upt-item-card');

                // Se não houver cards padrão (caso de template Elementor),
                // conta filhos de primeiro nível que sejam elementos visíveis
                // e que não sejam a mensagem de "sem itens".
                if ($cards.length === 0) {
                    $cards = $grid.children().filter(function () {
                        var $el = jQuery(this);
                        if (this.nodeType !== 1) return false; // apenas nós de elemento
                        if ($el.is('script, style')) return false;
                        if ($el.hasClass('no-items-message')) return false;
                        return $el.is(':visible');
                    });
                }

                var count = $cards.length;

                if (count >= limit) {
                    $addButton.hide().addClass('upt-button-disabled');
                    uptDebugLogLimits('updateAddButton -> hide (reached limit)', {
                        tab: $activeTab.attr('id'),
                        limit: limit,
                        count: count
                    });
                } else {
                    $addButton.show().removeClass('upt-button-disabled');
                    uptDebugLogLimits('updateAddButton -> show (under limit)', {
                        tab: $activeTab.attr('id'),
                        limit: limit,
                        count: count
                    });
                }
            }
            


    // Atualiza ao carregar
    uptEnsurePerCategoryButtonVisibility();
    uptUpdateAddButtonVisibility();

        // Atualiza ao trocar de aba
        $('body').on('click', '.upt-tabs-nav a', function () {
            setTimeout(function() {
                uptEnsurePerCategoryButtonVisibility();
                uptUpdateAddButtonVisibility();
            }, 50);
        });

        var uptCountersRequest = null;

        function uptRefreshTabCounters() {
            var $nav = $('.upt-tabs-nav');
            if (!$nav.length || typeof upt_ajax === 'undefined') { return; }

            // Respeita a configuração: se o contador estiver desabilitado, não cria badges.
            if (!$nav.find('.upt-tab-counter').length) { return; }

            var $container = $('.upt-items-container');
            if (!$container.length) { return; }

            var showAll = $container.data('show-all') === 'yes';

            if (uptCountersRequest && uptCountersRequest.readyState !== 4) {
                try { uptCountersRequest.abort(); } catch (e) { }
            }

            uptCountersRequest = $.ajax({
                url: upt_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'upt_get_schema_counts',
                    nonce: upt_ajax.nonce,
                    show_all: showAll ? 'yes' : 'no'
                },
                success: function (response) {
                    if (!response || !response.success || !response.data) { return; }

                    var counts = response.data.schemas || {};
                    var formsCount = response.data.forms;

                    Object.keys(counts).forEach(function (slug) {
                        var $link = $('.upt-tabs-nav a[data-schema="' + slug + '"]');
                        if (!$link.length) { return; }

                        var $badge = $link.find('.upt-tab-counter');
                        if (!$badge.length) {
                            $badge = $('<span class="upt-tab-counter"></span>');
                            $link.append($badge);
                        }

                        $badge.text(counts[slug]);
                    });

                    if (typeof formsCount === 'number') {
                        var $formsLink = $('.upt-tab-4gt a');
                        if ($formsLink.length) {
                            var $formsBadge = $formsLink.find('.upt-tab-counter');
                            if (!$formsBadge.length) {
                                $formsBadge = $('<span class="upt-tab-counter" aria-label="Total de envios de formulários"></span>');
                                $formsLink.append($formsBadge);
                            }
                            $formsBadge.text(formsCount);
                        }
                    }
                }
            });
        }

        // Atualiza após ajax de recarregar itens
        $(document).on('upt_items_list_updated', function () {
            uptEnsurePerCategoryButtonVisibility();
            uptUpdateAddButtonVisibility();
            uptRefreshTabCounters();
            uptInitCardSorting();
        });


        try {
            inituptSimpleEditor();
        } catch (e) {
            if (window.console && console.warn) {
                console.warn('upt: erro ao inicializar editor WYSIWYG na carga inicial', e);
            }
        }

        // ===========================
        // Ordenação por arraste (grid)
        // ===========================
        var uptSortableLoader = null;

        function uptEnsureSortable() {
            if (window.Sortable && typeof window.Sortable.create === 'function') {
                return $.Deferred().resolve(window.Sortable).promise();
            }

            if (uptSortableLoader) { return uptSortableLoader; }

            var deferred = $.Deferred();
            var src = (window.upt_ajax && upt_ajax.sortable_js) ? upt_ajax.sortable_js : 'https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js';
            var script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = function () { deferred.resolve(window.Sortable); };
            script.onerror = function () { deferred.reject(new Error('sortable_load_failed')); };
            document.head.appendChild(script);

            uptSortableLoader = deferred.promise();
            return uptSortableLoader;
        }

        function uptPersistGridOrder($grid) {
            var schemaSlug = $grid.data('schema');
            if (!schemaSlug) { return; }

            var ids = [];
            $grid.find('.upt-item-card[data-item-id]').each(function () {
                var id = parseInt($(this).data('item-id'), 10);
                if (id) { ids.push(id); }
            });

            if (!ids.length) { return; }

            $.post(uptGetAjaxConfig().ajax_url, {
                action: 'upt_reorder_items',
                nonce: uptGetAjaxConfig().nonce,
                schema_slug: schemaSlug,
                order: ids
            }).done(function () {
                $(document).trigger('upt_items_list_updated');
            }).fail(function () {
                window.alert('Erro ao salvar a nova ordem.');
            });
        }

        function uptInjectDragStyles() {
            if (document.getElementById('upt-drag-styles')) { return; }

            var style = document.createElement('style');
            style.id = 'upt-drag-styles';
            style.textContent = [
                '.sortable-ghost { opacity: 0 !important; }',
                '.sortable-drag { cursor: grabbing !important; opacity: 1 !important; background: #fff !important; transform: scale(1.02); box-shadow: 0 15px 30px rgba(0,0,0,0.30); z-index: 9999 !important; }',
                '.upt-sortable-ghost { opacity: 0 !important; }',
                '.upt-sortable-drag { cursor: grabbing !important; opacity: 1 !important; background: #fff !important; transform: scale(1.02); box-shadow: 0 15px 30px rgba(0,0,0,0.30); z-index: 9999 !important; }'
            ].join('\n');
            document.head.appendChild(style);
        }

        function uptInitCardSorting() {
            var $grids = $('.upt-items-grid[data-allow-reorder="yes"]');
            if (!$grids.length) { return; }

            uptInjectDragStyles();

            function initFallback($grid) {
                var schemaSlug = $grid.data('schema');
                if (!schemaSlug) { return; }

                var draggingCard = null;

                $grid.off('.fcSort');
                $grid.find('.upt-item-card').off('.fcSort').each(function () {
                    var $card = $(this);
                    $card.attr('draggable', 'true');

                    $card.on('dragstart.fcSort', function (e) {
                        draggingCard = $card;
                        if (e.originalEvent && e.originalEvent.dataTransfer) {
                            e.originalEvent.dataTransfer.effectAllowed = 'move';
                            e.originalEvent.dataTransfer.setData('text/plain', $card.data('item-id'));
                        }
                        $card.addClass('upt-card-dragging');
                    });

                    $card.on('dragend.fcSort', function () {
                        draggingCard = null;
                        $grid.find('.upt-card-drop-target').removeClass('upt-card-drop-target');
                        $grid.find('.upt-card-dragging').removeClass('upt-card-dragging');
                    });
                });

                $grid.on('dragover.fcSort', function (e) {
                    if (!draggingCard) { return; }
                    e.preventDefault();
                });

                $grid.on('dragover.fcSort', '.upt-item-card', function (e) {
                    if (!draggingCard) { return; }
                    e.preventDefault();
                    if (e.originalEvent && e.originalEvent.dataTransfer) {
                        e.originalEvent.dataTransfer.dropEffect = 'move';
                    }
                    $(this).addClass('upt-card-drop-target');
                });

                $grid.on('dragleave.fcSort', '.upt-item-card', function () {
                    $(this).removeClass('upt-card-drop-target');
                });

                $grid.on('drop.fcSort', '.upt-item-card', function (e) {
                    if (!draggingCard) { return; }
                    e.preventDefault();

                    var $target = $(this);
                    $grid.find('.upt-card-drop-target').removeClass('upt-card-drop-target');

                    if ($target[0] === draggingCard[0]) { return; }

                    $target.before(draggingCard);
                    uptPersistGridOrder($grid);
                    draggingCard = null;
                });
            }

            uptEnsureSortable()
                .done(function (Sortable) {
                    if (!Sortable || typeof Sortable.create !== 'function') {
                        $grids.each(function () { initFallback($(this)); });
                        return;
                    }

                    $grids.each(function () {
                        var $grid = $(this);
                        var schemaSlug = $grid.data('schema');
                        if (!schemaSlug) { return; }

                        var existing = $grid.data('fcSortable');
                        if (existing && typeof existing.destroy === 'function') {
                            existing.destroy();
                        }

                        // Remove listeners do fallback antes de criar nova instância
                        $grid.off('.fcSort');
                        $grid.find('.upt-item-card').off('.fcSort').removeAttr('draggable');

                        var sortable = Sortable.create($grid.get(0), {
                            animation: 350,
                            draggable: '.upt-item-card',
                            ghostClass: 'sortable-ghost',
                            dragClass: 'sortable-drag',
                            chosenClass: 'sortable-drag',
                            // Exige um press breve em toques móveis para evitar conflito com scroll
                            delay: 280,
                            delayOnTouchOnly: true,
                            touchStartThreshold: 8,
                            fallbackTolerance: 8,
                            invertSwap: true,
                            swapThreshold: 0.5,
                            invertedSwapThreshold: 0.6,
                            direction: 'vertical',
                            onEnd: function (evt) {
                                uptPersistGridOrder($(evt.to));
                            }
                        });

                        $grid.data('fcSortable', sortable);
                    });
                })
                .fail(function () {
                    uptSortableLoader = null;
                    // Fallback para navegadores que bloqueiam CDN ou scripts externos
                    $grids.each(function () { initFallback($(this)); });
                });
        }

        // Inicializa ao carregar
        uptInitCardSorting();
    /* ========================================================================
     * SECTION 15: HELP MODAL & MEDIA TITLE BADGE
     * Video help modal, media title badge overlay
     * ======================================================================== */

        // =======================
        // Ajuda: modal de vídeo
        // =======================
        $(document).on('click', '.upt-help-link', function (e) {
            uptDebugLog('help-link-click', {
                hasModal: $('#upt-help-modal').length,
                hasVideo: $('#upt-help-modal').find('video').length,
                hasIframe: $('#upt-help-modal').find('iframe').length
            });

            e.preventDefault();
            var $modal = $('#upt-help-modal');
            if (!$modal.length) {
                return;
            }
            $('body').addClass('fc-modal-open');
            $modal.addClass('is-open');
        });

        $(document).on('click', '.upt-help-close, #upt-help-modal .upt-help-modal-overlay', function (e) {
            e.preventDefault();
            var $modal = $('#upt-help-modal');
            if (!$modal.length) {
                return;
            }

            $modal.removeClass('is-open');
            $('body').removeClass('fc-modal-open');

            var $iframe = $modal.find('iframe').first();
            var $video = $modal.find('video').first();

            uptDebugLog('help-modal-close', {
                hasIframe: $iframe.length,
                hasVideo: $video.length
            });

            if ($iframe.length) {
                // Reset src para parar o vídeo (YouTube, Vimeo, etc.)
                var src = $iframe.attr('src');
                $iframe.attr('src', src);
            } else if ($video.length && $video.get(0).pause) {
                $video.get(0).pause();
                $video.get(0).currentTime = 0;
            }
        });
        // Logs de inicialização do vídeo de ajuda
        (function () {
            var $helpModal = $('#upt-help-modal');
            var $helpLink = $('.upt-help-link');

            uptDebugLog('help-modal-init', {
                hasHelpLink: $helpLink.length,
                hasHelpModal: $helpModal.length
            });

            if ($helpModal.length) {
                var $video = $helpModal.find('video').first();
                var $iframe = $helpModal.find('iframe').first();
                var hasVideo = $video.length && !!$video.attr('src');
                var hasIframe = $iframe.length && !!$iframe.attr('src');

                uptDebugLog('help-modal-assets', {
                    hasVideo: hasVideo,
                    hasIframe: hasIframe,
                    videoSrc: hasVideo ? $video.attr('src') : null,
                    iframeSrc: hasIframe ? $iframe.attr('src') : null
                });

                if (!hasVideo && !hasIframe) {
                    if ($helpLink.length) {
                        $helpLink.hide();
                    }
                    // Mantemos o modal na DOM, mas oculto, por segurança.
                    $helpModal.removeClass('is-open');
                } else if ($helpLink.length) {
                    $helpLink.show();
                }
            } else if ($helpLink.length) {
                // Sem modal presente, escondemos o link para evitar cliques inúteis.
                $helpLink.hide();
            }
        })();




        /* === upt 43.0.5: Media Title Badge & Reliable Modal Close (additive, non-destructive) === */
        function fcGetFileNameFromUrl(u) {
            try {
                if (!u) return '';
                var q = String(u).split('?')[0];
                var parts = q.split('/');
                return parts[parts.length - 1] || '';
            } catch (e) { return ''; }
        }
        function fcEnsureBadge($el, title) {
            if (!title) return;
            // Prefer to show a single badge per preview container
            var $container = $el.closest('.upt-media-preview, .gallery-preview-item, .upt-media-field, .upt-media-wrapper');
            if (!$container.length) $container = $el;
            if (!$container.find('.upt-media-title').length) {
                $('<div class="upt-media-title" />').text(title).attr('title', title).appendTo($container);
            }
        }
        function fcScanAndBadge(root) {
            var $root = $(root || document);
            // Single previews
            $root.find('.upt-media-preview img, .upt-media-preview video').each(function () {
                var src = this.currentSrc || this.src || (this.tagName === 'VIDEO' ? $(this).attr('src') : '');
                var title = fcGetFileNameFromUrl(src);
                fcEnsureBadge($(this), title);
            });
            // Gallery previews
            $root.find('.gallery-preview-item').each(function () {
                var $img = $(this).find('img, video').first();
                var src = $img.prop('currentSrc') || $img.attr('src') || '';
                var title = fcGetFileNameFromUrl(src);
                fcEnsureBadge($(this), title);
            });
        }
        // Initial
        fcScanAndBadge(document);
        // Observe modal/content changes to keep badges
        try {
            var target = document.getElementById('upt-modal-content') || document.body;
            var fcBadgeQueue = [];
            var fcBadgeScheduled = false;

            function fcScheduleBadgeScan() {
                if (fcBadgeScheduled) {
                    return;
                }
                fcBadgeScheduled = true;
                setTimeout(function () {
                    fcBadgeScheduled = false;
                    if (!fcBadgeQueue.length) {
                        return;
                    }
                    var batch = fcBadgeQueue.slice(0);
                    fcBadgeQueue.length = 0;
                    fcScanAndBadge(batch);
                }, 0);
            }

            var mo = new MutationObserver(function (muts) {
                for (var i = 0; i < muts.length; i++) {
                    var m = muts[i];
                    if (m.addedNodes && m.addedNodes.length) {
                        for (var j = 0; j < m.addedNodes.length; j++) {
                            fcBadgeQueue.push(m.addedNodes[j]);
                        }
                    }
                }
                fcScheduleBadgeScan();
            });
            mo.observe(target, { childList: true, subtree: true });
        } catch (e) { }
        // Ensure modal close after successful save (in case upstream code changes)
        $(document).on('upt_items_list_updated', function () {
            try {
                // closeModal may exist in upstream; fallback to minimal close
                if (typeof closeModal === 'function') { closeModal(); }
                else {
                    $('html, body').removeClass('modal-open');
                    $('#upt-modal-wrapper').fadeOut(200);
                    setTimeout(function () { $('#upt-modal-content').empty(); }, 300);
                }
            } catch (e) { }
        });
        /* === end upt 43.0.5 additive === */
        /* ========================================================================
     * SECTION 16: GALLERY FALLBACK
     * Standalone gallery/media init for pages without dashboard context.
     * Opens gallery modal, handles media selection (image/video/pdf/gallery)
     * ======================================================================== */

    /* === Fallback media init (ensures gallery opens even if dashboard init didn't run) === */
        (function(){
            if (window.uptFallbackMediaInitDone) return; window.uptFallbackMediaInitDone = true;

            var fcCurrWrapper = null;
            var fcCurrType = null;

            function fcGetAccentColor(wrapper){
                try{
                    var dashboardContainer = wrapper && wrapper.closest('.upt-preset-hostinger');
                    if (dashboardContainer && dashboardContainer.length){
                        var computedStyle = getComputedStyle(dashboardContainer[0]);
                        return computedStyle.getPropertyValue('--fc-primary-color').trim();
                    }
                }catch(e){}
                return '';
            }

            function fcOpenGalleryFallback(wrapper, type){
                fcCurrWrapper = wrapper;
                fcCurrType = type;

                try {
                    if (typeof currentMediaWrapper !== 'undefined') {
                        currentMediaWrapper = wrapper;
                        currentFieldType = type;
                        if (typeof uptComputeFieldKey === 'function') {
                            currentFieldKey = uptComputeFieldKey(wrapper, type);
                        } else {
                            currentFieldKey = null;
                        }

                        if (typeof uptUpdateSelectionMapFromWrapper === 'function') {
                            var ids = [];
                            if (type === 'gallery') {
                                var inputG = wrapper.find('.gallery-ids-input').first();
                                if (inputG.length) ids = (inputG.val() || '').split(',').filter(Boolean);
                            } else if (type === 'image') {
                                var inputI = wrapper.find('.upt-image-id-input').first();
                                if (inputI.length && inputI.val()) ids = [String(inputI.val())];
                            } else if (type === 'video') {
                                var inputV = wrapper.find('.upt-video-id-input').first();
                                if (inputV.length && inputV.val()) ids = [String(inputV.val())];
                            } else if (type === 'pdf') {
                                var inputP = wrapper.find('.upt-pdf-id-input').first();
                                if (inputP.length && inputP.val()) ids = [String(inputP.val())];
                            }
                            uptUpdateSelectionMapFromWrapper(wrapper, ids);
                        }
                    }
                } catch(e) {}

                var ajaxCfg = (typeof uptGetAjaxConfig === 'function') ? uptGetAjaxConfig() : (window.upt_ajax || {});
                var iframeSrc = (ajaxCfg.admin_url || '') + 'admin.php?page=upt_gallery&noheader=true';
                if (type === 'gallery') iframeSrc += '&selection=multiple';
                else if (type === 'image' || type === 'video' || type === 'pdf') iframeSrc += '&media_type=' + encodeURIComponent(type);

                var $itemsContainer = wrapper && wrapper.closest('.upt-items-container');
                if ((!$itemsContainer || !$itemsContainer.length) && typeof $ !== 'undefined') {
                    $itemsContainer = $('.upt-items-container').first();
                }

                if ($itemsContainer && $itemsContainer.length && String($itemsContainer.data('gallery-pagination') || '') === 'yes') {
                    var galleryPerPage = parseInt($itemsContainer.data('gallery-per-page'), 10);
                    var galleryPaginationType = String($itemsContainer.data('gallery-pagination-type') || '');
                    var galleryInfiniteTrigger = String($itemsContainer.data('gallery-pagination-infinite-trigger') || '');
                    var galleryNumbersNav = String($itemsContainer.data('gallery-pagination-numbers-nav') || '');
                    var galleryLoadMoreLabel = String($itemsContainer.data('gallery-pagination-load-more-label') || '');

                    iframeSrc += '&gallery_pagination=yes';
                    if (galleryPerPage && galleryPerPage > 0) {
                        iframeSrc += '&gallery_per_page=' + encodeURIComponent(galleryPerPage);
                    }
                    if (galleryPaginationType) {
                        iframeSrc += '&gallery_pagination_type=' + encodeURIComponent(galleryPaginationType);
                    }
                    if (galleryNumbersNav) {
                        iframeSrc += '&gallery_pagination_numbers_nav=' + encodeURIComponent(galleryNumbersNav);
                    }
                    if (galleryInfiniteTrigger) {
                        iframeSrc += '&gallery_pagination_infinite_trigger=' + encodeURIComponent(galleryInfiniteTrigger);
                    }
                    if (galleryLoadMoreLabel) {
                        iframeSrc += '&gallery_pagination_load_more_label=' + encodeURIComponent(galleryLoadMoreLabel);
                    }
                }
                var accent = fcGetAccentColor(wrapper);
                if (accent) iframeSrc += '&accent_color=' + encodeURIComponent(accent);

                if (typeof window.uptBuildGalleryPaginationVars === 'function') {
                    var vars = window.uptBuildGalleryPaginationVars(wrapper);
                    if (vars) iframeSrc += '&fc_gallery_vars=' + encodeURIComponent(JSON.stringify(vars));
                }

                var $gm = $('#upt-gallery-modal');
                if ($gm.length === 0){
                    $('body').append('<div id="upt-gallery-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999999; padding: 5vh 5vw;"><div id="upt-gallery-modal-content" style="width:100%; height:100%; background:white;"><iframe src="' + iframeSrc + '" style="width:100%; height:100%; border:0;"></iframe></div></div>');
                    $gm = $('#upt-gallery-modal');
                } else {
                    $gm.find('iframe').attr('src', iframeSrc);
                }

                $gm.css({ display: 'flex', opacity: 0 }).animate({ opacity: 1 }, 200);
                $('html, body').addClass('modal-open');
            }

            window.addEventListener('message', function(event){
                if (!event || !event.data) return;
                if (event.data.uptGallerySelection){
                    var mediaData = event.data.uptGallerySelection || [];
                    if (fcCurrWrapper && fcCurrType === 'image'){
                        var single = mediaData[0];
                        if (single){
                            var input = fcCurrWrapper.find('.upt-image-id-input');
                            var preview = fcCurrWrapper.find('.image-preview-wrapper');
                            var removeBtn = fcCurrWrapper.find('.upt-remove-image');
                            input.val(single.id);
                            preview.html('<img src="' + (single.thumbnail_url || '') + '">');
                            removeBtn.removeClass('hidden');
                        }
                    } else if (fcCurrWrapper && fcCurrType === 'video'){
                        var singleV = mediaData[0];
                        if (singleV){
                            var inputV = fcCurrWrapper.find('.upt-video-id-input');
                            var previewV = fcCurrWrapper.find('.video-preview-wrapper');
                            var removeBtnV = fcCurrWrapper.find('.upt-remove-video');
                            inputV.val(singleV.id);
                            var fullUrl = (singleV.full_url || singleV.thumbnail_url || '');
                            var overlay = (typeof upt_ajax !== 'undefined' && upt_ajax.transparent_png) ? upt_ajax.transparent_png : (singleV.thumbnail_url || '');
                            var thumb = '<div class="upt-video-thumb"><video muted playsinline preload="metadata" src="' + fullUrl + '#t=1"></video><img class="upt-video-overlay" src="' + overlay + '" alt=""></div>';
                            previewV.html(thumb);
                            removeBtnV.removeClass('hidden');
                        }
                    }
                    else if (fcCurrWrapper && fcCurrType === 'pdf'){
                        var singleP = mediaData[0];
                        if (singleP){
                            var inputP = fcCurrWrapper.find('.upt-pdf-id-input');
                            var previewP = fcCurrWrapper.find('.pdf-preview-wrapper');
                            var removeBtnP = fcCurrWrapper.find('.upt-remove-pdf');
                            inputP.val(singleP.id);
                            var fullUrlP = (singleP.full_url || '');
                            var iconP = (singleP.thumbnail_url || '');
                            previewP.html('<div class="upt-pdf-thumb" data-type="pdf" data-full-url="' + fullUrlP + '"><img src="' + iconP + '" alt=""></div>');
                            removeBtnP.removeClass('hidden');
                        }
                    } else if (fcCurrWrapper && fcCurrType === 'gallery'){
                        var previews = fcCurrWrapper.find('.gallery-previews');
                        var inputG = fcCurrWrapper.find('.gallery-ids-input');
                        var current = inputG.val() ? inputG.val().split(',').filter(Boolean) : [];
                        mediaData.forEach(function(d){
                            if (current.indexOf(String(d.id)) === -1){
                                current.push(String(d.id));
                                var thumb = d.thumbnail_url || '';
                                var fullUrl = d.full_url || '';
                                var type = d.type || '';
                                var isVideo = (type === 'video') || /\.(mp4|webm|ogg|mov|m4v)$/i.test(fullUrl);
                                var isPdf = (type === 'pdf') || /\.pdf$/i.test(fullUrl);
                                var html = '';

                                if (isVideo) {
                                    var overlay = (typeof upt_ajax !== 'undefined' && upt_ajax.transparent_png) ? upt_ajax.transparent_png : (thumb || '');
                                    html = '<div class="upt-video-thumb"><video muted playsinline preload="metadata" src="' + fullUrl + '#t=1"></video><img class="upt-video-overlay" src="' + overlay + '" alt=""></div>';
                                } else {
                                    html = '<img src="' + (thumb || fullUrl) + '">';
                                }

                                previews.append('<div class="gallery-preview-item" data-id="' + d.id + '" data-type="' + (type || (isPdf ? 'pdf' : (isVideo ? 'video' : 'image'))) + '" data-full-url="' + (fullUrl || '') + '">' + html + '<a href="#" class="remove-image">×</a></div>');
                            }
                        });
                        inputG.val(current.join(',')).trigger('change');
                    }

                    $('#upt-gallery-modal').fadeOut(200);
                    $('html, body').removeClass('modal-open');
                } else if (event.data.uptGalleryClose){
                    $('#upt-gallery-modal').fadeOut(200);
                    $('html, body').removeClass('modal-open');
                }
            });

            // Delegated handlers
            $('body').on('click', '.upt-add-image', function(e){ e.preventDefault(); var wrapper = $(this).closest('.upt-image-upload-wrapper'); fcOpenGalleryFallback(wrapper,'image'); });
            $('body').on('click', '.upt-add-video', function(e){ e.preventDefault(); var wrapper = $(this).closest('.upt-video-upload-wrapper'); fcOpenGalleryFallback(wrapper,'video'); });
            $('body').on('click', '.upt-add-pdf', function(e){ e.preventDefault(); var wrapper = $(this).closest('.upt-pdf-upload-wrapper'); fcOpenGalleryFallback(wrapper,'pdf'); });
            $('body').on('click', '.add-gallery-images', function(e){ e.preventDefault(); var wrapper = $(this).closest('.upt-gallery-wrapper'); fcOpenGalleryFallback(wrapper,'gallery'); });

            $('body').on('click', '#upt-gallery-modal', function(e){ if (e.target === this) { $('#upt-gallery-modal').fadeOut(200); $('html, body').removeClass('modal-open'); } });
            $('body').on('click', '.upt-remove-image', function(e){ e.preventDefault(); var btn=$(this); var wrapper=btn.closest('.upt-image-upload-wrapper'); wrapper.find('.upt-image-id-input').val(''); wrapper.find('.image-preview-wrapper').html(''); btn.addClass('hidden'); });
            $('body').on('click', '.upt-remove-video', function(e){ e.preventDefault(); var btn=$(this); var wrapper=btn.closest('.upt-video-upload-wrapper'); wrapper.find('.upt-video-id-input').val(''); wrapper.find('.video-preview-wrapper').html(''); btn.addClass('hidden'); });
            $('body').on('click', '.upt-remove-pdf', function(e){ e.preventDefault(); var btn=$(this); var wrapper=btn.closest('.upt-pdf-upload-wrapper'); wrapper.find('.upt-pdf-id-input').val(''); wrapper.find('.pdf-preview-wrapper').html(''); btn.addClass('hidden'); });
            $('body').on('click', '.gallery-preview-item .remove-image', function(e){ e.preventDefault(); var item=$(this).closest('.gallery-preview-item'); var wrapper=item.closest('.upt-gallery-wrapper'); var input=wrapper.find('.gallery-ids-input'); var idToRemove=item.data('id').toString(); item.remove(); var currentIds=input.val().split(','); var newIds=currentIds.filter(function(id){ return id !== idToRemove; }); input.val(newIds.join(',')).trigger('change'); });
        })();

    /* ========================================================================
     * SECTION 17: BULK DELETE
     * Bulk selection mode, select all (with AJAX for all pages), confirm
     * dialog, bulk delete via AJAX, UI reset
     * ======================================================================== */

        // -----------------------------
        // Bulk delete (dashboard)
        // -----------------------------
        function uptGetBulkItemIdFromCard($card) {
            var $del = $card.find('.delete-item-ajax').first();
            var id = $del.length ? parseInt($del.data('item-id'), 10) : 0;
            return isNaN(id) ? 0 : id;
        }

        
        function uptGetBulkScope() {
            var $activeTab = $('.upt-tab-pane.active');
            if ($activeTab.length) { return $activeTab; }
            var $container = $('.upt-items-container');
            if ($container.length) { return $container; }
            return $('body');
        }

        function uptEnsureBulkCheckboxes() {
            var $scope = uptGetBulkScope();
            if (!$scope.length) return;

            $scope.find('.upt-item-card').each(function () {
                var $card = $(this);
                if ($card.find('.upt-bulk-checkbox').length) return;

                var itemId = uptGetBulkItemIdFromCard($card);
                if (!itemId) return;

                var $label = $('<label/>', { 'class': 'upt-bulk-checkbox' });
                var $cb = $('<input/>', { 'type': 'checkbox', 'class': 'upt-bulk-select', 'value': itemId });
                $label.append($cb);
                $label.append($('<span/>').text('Selecionar'));
                $card.prepend($label);
            });
        }

function uptSetBulkMode(isActive) {
            var $body = $('body');
            if (isActive) {
                $body.addClass('upt-bulk-select-mode');
                uptEnsureBulkCheckboxes();
                uptUpdateBulkDeleteCount();
            } else {
                $body.removeClass('upt-bulk-select-mode');
                $('.upt-bulk-select').prop('checked', false);
                uptUpdateBulkDeleteCount();
            }
        
        // When bulk mode is active: clicking anywhere on the card toggles the checkbox
        $('body').on('click', '.upt-items-container .upt-item-card', function (e) {
            var $body = $('body');
            if (!$body.hasClass('upt-bulk-select-mode')) return;

            // Ignore clicks on controls/actions (they should keep their normal behavior)
            if ($(e.target).closest('.upt-bulk-checkbox, .upt-item-actions, .upt-card-actions, .upt-item-card-actions, .upt-card-footer, .upt-item-footer, button, a, input, select, textarea').length) {
                return;
            }

            var $card = $(this);
            // Ensure checkbox exists
            if (!$card.find('.upt-bulk-select').length) {
                uptEnsureBulkCheckboxes();
            }

            var $cb = $card.find('.upt-bulk-select').first();
            if (!$cb.length) return;

            e.preventDefault();
            e.stopPropagation();

            $cb.prop('checked', !$cb.prop('checked')).trigger('change');
        });

}

        function uptUpdateBulkDeleteCount() {
            var $btn = $('.upt-bulk-delete-confirm');
            if (!$btn.length) return;
            var baseLabel = $btn.data('base-label') || 'Excluir';
            var $scope = uptGetBulkScope();
            var n = $scope.find('.upt-bulk-select:checked').length;
            $btn.text(baseLabel + ' ' + n);
        }

        // Update count when checkboxes change
        $('body').on('change', '.upt-bulk-select', function () {
            if ($('body').hasClass('upt-bulk-select-mode')) {
                uptUpdateBulkDeleteCount();
            }
        });

        // Keep checkboxes after list reloads
        $(document).on('upt_items_list_updated', function () {
            if ($('body').hasClass('upt-bulk-select-mode')) {
                uptEnsureBulkCheckboxes();
            }
        });

        function uptResetBulkDeleteUI() {
            var $toggle = $('.upt-bulk-delete-toggle');
            var $group = $('.upt-bulk-delete-group');
            $toggle.show();
            $group.hide();
            // clear selections
            $('.upt-bulk-select').prop('checked', false);
            uptSetBulkMode(false);
        }

        // Enter bulk delete mode
        $('body').on('click', '.upt-bulk-delete-toggle', function (e) {
            e.preventDefault();
            $('.upt-bulk-delete-toggle').hide();
            $('.upt-bulk-delete-group').css('display', 'inline-flex');
            uptSetBulkMode(true);
        });

        // Select all (current view + all pages via AJAX)
        $('body').on('click', '.upt-bulk-delete-selectall', function (e) {
            e.preventDefault();
            if (!$('body').hasClass('upt-bulk-select-mode')) return;

            var $btn = $(this);
            var $scope = uptGetBulkScope();
            var $visibleBoxes = $scope.find('.upt-bulk-select');
            var allChecked = true;
            $visibleBoxes.each(function () {
                if (!$(this).prop('checked')) { allChecked = false; return false; }
            });

            if (allChecked) {
                $visibleBoxes.prop('checked', false).trigger('change');
                $scope.find('.upt-bulk-select-extra').remove();
                return;
            }

            $visibleBoxes.prop('checked', true).trigger('change');

            var schema = $scope.data('schema') || $scope.find('[data-schema]').data('schema') || '';
            if (!schema) {
                var $schemaTab = $scope.closest('.upt-tab-pane').find('.upt-filter-schema-btn.active');
                if ($schemaTab.length) schema = $schemaTab.data('schema') || '';
            }

            if (!schema) return;

            $btn.prop('disabled', true).text('Carregando...');

            var ajaxUrl = (typeof upt_ajax !== 'undefined') ? upt_ajax.ajax_url : '/wp-admin/admin-ajax.php';
            var nonce = (typeof upt_ajax !== 'undefined') ? upt_ajax.nonce : '';

            $.post(ajaxUrl, {
                action: 'upt_get_all_item_ids',
                nonce: nonce,
                schema: schema
            }, function(resp) {
                $btn.prop('disabled', false).text('Tudo');
                if (!resp.success || !resp.data || !resp.data.ids || !resp.data.ids.length) return;

                var $container = $scope.find('.upt-bulk-extra-container');
                if (!$container.length) {
                    $container = $('<div class="upt-bulk-extra-container" style="display:none;"></div>');
                    $scope.append($container);
                }
                $container.empty();

                var existingIds = [];
                $scope.find('.upt-bulk-select').each(function() {
                    existingIds.push(parseInt($(this).val(), 10));
                });

                var addedCount = 0;
                resp.data.ids.forEach(function(id) {
                    if (existingIds.indexOf(id) === -1) {
                        $container.append(
                            $('<input type="checkbox" class="upt-bulk-select upt-bulk-select-extra" checked="checked">')
                            .val(id)
                        );
                        addedCount++;
                    }
                });

                if (addedCount > 0) {
                    uptUpdateBulkDeleteCount();
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Tudo');
            });
        });

        // Cancel bulk delete mode
        $('body').on('click', '.upt-bulk-delete-cancel', function (e) {
            e.preventDefault();
            uptResetBulkDeleteUI();
                            if (typeof uptRefreshTabCounters === 'function') { uptRefreshTabCounters(); }
        });

        // Confirm bulk deletion
        $('body').on('click', '.upt-bulk-delete-confirm', function (e) {
            e.preventDefault();

            var $confirmBtn = $(this);
            var ids = [];
            var $scope = uptGetBulkScope();
            $scope.find('.upt-bulk-select:checked').each(function () {
                var v = parseInt($(this).val(), 10);
                if (!isNaN(v) && v) ids.push(v);
            });

            if (!ids.length) {
                uptNotify('warning', 'Selecione ao menos 1 item para excluir.');
                return;
            }

            uptConfirmDialog('Tem certeza que deseja excluir os itens selecionados permanentemente?', function () {
                $confirmBtn.prop('disabled', true);
                $('.upt-bulk-delete-cancel').prop('disabled', true);

                $.ajax({
                    url: upt_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'upt_bulk_delete_items',
                        nonce: upt_ajax.nonce,
                        item_ids: ids
                    },
                    success: function (response) {
                        if (response && response.success) {
                            uptNotify('delete', 'Itens apagados!');
                            ids.forEach(function (id) {
                                var $target = $('.delete-item-ajax[data-item-id="' + id + '"]').closest('.upt-item-card');
                                if ($target.length) $target.fadeOut(250, function(){ $(this).remove(); });
                            });
                            uptResetBulkDeleteUI();
                        } else {
                            var msg = (response && response.data && response.data.message) ? response.data.message : 'Falha ao excluir itens.';
                            uptNotify('error', msg);
                        }
                    },
                    error: function () {
                        uptNotify('error', 'Erro de rede ao excluir itens.');
                    },
                    complete: function () {
                        $confirmBtn.prop('disabled', false);
                        $('.upt-bulk-delete-cancel').prop('disabled', false);
                    }
                });
            });
        });


    });
