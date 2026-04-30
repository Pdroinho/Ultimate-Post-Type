(function() {
    'use strict';

    var $startBtn = jQuery('#upt-imob-start-btn');
    if (!$startBtn.length) return;

    var ajaxUrl = (typeof upt_ajax !== 'undefined') ? upt_ajax.ajax_url : '/wp-admin/admin-ajax.php';
    var nonce = (typeof upt_ajax !== 'undefined') ? upt_ajax.nonce : '';

    var $mode = jQuery('#upt-imob-schema-mode');
    var $newField = jQuery('#upt-imob-new-schema-field');
    var $existingField = jQuery('#upt-imob-existing-schema-field');
    var $uploadSection = jQuery('#upt-imob-upload-section');
    var $progressSection = jQuery('#upt-imob-progress-section');
    var $doneSection = jQuery('#upt-imob-done-section');
    var $cancelBtn = jQuery('#upt-imob-cancel-btn');
    var $newBtn = jQuery('#upt-imob-new-btn');
    var $dropzone = jQuery('#upt-imob-dropzone');
    var $fileInput = jQuery('#upt-imob-xml-file');
    var $fileInfo = jQuery('#upt-imob-file-info');
    var $fileName = jQuery('#upt-imob-file-name');
    var $fileSize = jQuery('#upt-imob-file-size');
    var $fileRemove = jQuery('#upt-imob-file-remove');
    var $validationMsg = jQuery('#upt-imob-validation-msg');
    var $schemaSection = jQuery('#upt-imob-schema-section');
    var $stepper = jQuery('.upt-import-stepper');

    var session_id = '', total_items = 0, imported_total = 0, photos_total = 0, errors_total = 0, current_offset = 0, is_running = false, batch_size = 5;
    var selectedFile = null;

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function setStep(stepNum) {
        $stepper.find('.upt-step').each(function() {
            var s = parseInt(jQuery(this).data('step'));
            jQuery(this).removeClass('upt-step--active upt-step--completed');
            if (s < stepNum) jQuery(this).addClass('upt-step--completed');
            else if (s === stepNum) jQuery(this).addClass('upt-step--active');
        });
    }

    function showValidation(msg, type) {
        $validationMsg.removeClass('upt-import-msg--error upt-import-msg--success upt-import-msg--warning upt-import-msg--info')
            .addClass('upt-import-msg--' + (type || 'error'))
            .html('<span class="upt-import-msg__icon" aria-hidden="true">' +
                (type === 'success' ? '✅' : type === 'warning' ? '⚠️' : type === 'info' ? 'ℹ️' : '❌') +
                '</span><span class="upt-import-msg__text">' + msg + '</span>')
            .show();
    }

    function hideValidation() {
        $validationMsg.hide();
    }

    function validateFile(file) {
        if (!file) return { valid: false, msg: 'Nenhum arquivo selecionado.' };
        var name = file.name || '';
        var ext = name.split('.').pop().toLowerCase();
        if (ext !== 'xml') {
            return { valid: false, msg: 'Formato inválido. Por favor, selecione um arquivo <strong>.xml</strong>. Arquivo selecionado: <strong>' + name + '</strong>' };
        }
        var maxSize = 50 * 1024 * 1024;
        if (file.size > maxSize) {
            return { valid: false, msg: 'O arquivo excede o limite de <strong>50 MB</strong>. Tamanho atual: <strong>' + formatFileSize(file.size) + '</strong>. Tente compactar ou dividir o XML.' };
        }
        if (file.size === 0) {
            return { valid: false, msg: 'O arquivo está <strong>vazio</strong> (0 bytes). Verifique se o XML foi exportado corretamente.' };
        }
        return { valid: true };
    }

    function onFileSelected(file) {
        var result = validateFile(file);
        if (!result.valid) {
            showValidation(result.msg, 'error');
            $dropzone.removeClass('upt-import-dropzone--has-file');
            selectedFile = null;
            $fileInfo.hide();
            $schemaSection.hide();
            $startBtn.hide();
            setStep(1);
            return;
        }
        hideValidation();
        selectedFile = file;
        $fileName.text(file.name);
        $fileSize.text(formatFileSize(file.size));
        $fileInfo.show();
        $dropzone.addClass('upt-import-dropzone--has-file');
        $schemaSection.show();
        $startBtn.show();
        setStep(2);
    }

    function resetUpload() {
        selectedFile = null;
        $fileInput.val('');
        $fileInfo.hide();
        $dropzone.removeClass('upt-import-dropzone--has-file');
        $schemaSection.hide();
        $startBtn.hide();
        hideValidation();
        setStep(1);
    }

    $dropzone.on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $dropzone.addClass('upt-import-dropzone--dragover');
    }).on('dragleave drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $dropzone.removeClass('upt-import-dropzone--dragover');
    }).on('drop', function(e) {
        var files = e.originalEvent.dataTransfer.files;
        if (files && files.length > 0) {
            onFileSelected(files[0]);
        }
    }).on('click', function() {
        $fileInput.trigger('click');
    }).on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $fileInput.trigger('click');
        }
    });

    $fileInput.on('change', function() {
        if (this.files && this.files.length > 0) {
            onFileSelected(this.files[0]);
        }
    });

    $fileRemove.on('click', function(e) {
        e.stopPropagation();
        resetUpload();
    });

    $mode.on('change', function() {
        if (jQuery(this).val() === 'existing') { $newField.hide(); $existingField.show(); }
        else { $newField.show(); $existingField.hide(); }
    });

    $startBtn.on('click', function() {
        if (!selectedFile) {
            showValidation('Selecione um arquivo XML antes de iniciar.', 'warning');
            return;
        }
        $startBtn.prop('disabled', true).html('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:upt-spin 1s linear infinite" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Enviando...');
        uploadAndStart(selectedFile);
    });

    $cancelBtn.on('click', function() {
        if (!confirm('Deseja realmente cancelar a importação? Os imóveis já importados não serão afetados.')) return;
        is_running = false;
        jQuery.post(ajaxUrl, { action: 'upt_imob_cancel', nonce: nonce, session_id: session_id });
        $progressSection.hide();
        $uploadSection.show();
        resetUpload();
        $startBtn.prop('disabled', false).html('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg> Iniciar Importação');
    });

    $newBtn.on('click', function() {
        $doneSection.hide();
        $uploadSection.show();
        resetUpload();
        $startBtn.prop('disabled', false).html('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg> Iniciar Importação');
    });

    function uploadAndStart(file) {
        var formData = new FormData();
        formData.append('imob_xml_file', file);
        formData.append('action', 'upt_imob_upload');
        formData.append('nonce', nonce);
        formData.append('imob_schema_mode', $mode.val());
        formData.append('imob_schema_name', jQuery('#upt-imob-schema-name').val());
        formData.append('imob_schema_existing', jQuery('#upt-imob-schema-existing').val());

        jQuery.ajax({
            url: ajaxUrl, type: 'POST', data: formData,
            processData: false, contentType: false, timeout: 300000,
            success: function(resp) {
                if (resp.success && resp.data && resp.data.session_id) {
                    session_id = resp.data.session_id;
                    $uploadSection.hide();
                    $doneSection.hide();
                    $progressSection.show();
                    setStep(3);
                    imported_total = 0; photos_total = 0; errors_total = 0; current_offset = 0; is_running = true;
                    updateStats();
                    countAndProcess();
                } else {
                    var errMsg = (resp.data && resp.data.message) ? resp.data.message : 'Não foi possível iniciar a importação.';
                    showValidation(errMsg + '<br><small style="color:#6b7280">Verifique se o arquivo XML está no formato correto (OKE, Zap ou Viva Real) e tente novamente.</small>', 'error');
                    $startBtn.prop('disabled', false).html('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg> Iniciar Importação');
                }
            },
            error: function(xhr, status) {
                var msg = 'Erro de conexão ao enviar o arquivo.';
                if (status === 'timeout') {
                    msg = 'O envio demorou demais (<strong>timeout</strong>). Tente com um arquivo menor ou verifique sua conexão.';
                }
                showValidation(msg, 'error');
                $startBtn.prop('disabled', false).html('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg> Iniciar Importação');
            }
        });
    }

    function updateStats() {
        var pct = total_items > 0 ? Math.round((current_offset / total_items) * 100) : 0;
        jQuery('#upt-imob-progress-bar').css('width', pct + '%');
        jQuery('#upt-imob-progress-text').text(pct + '%');
        var $track = jQuery('.upt-import-progress__bar-track');
        $track.attr('aria-valuenow', pct);
        jQuery('#upt-imob-stat-total').text(total_items || '—');
        jQuery('#upt-imob-stat-imported').text(imported_total);
        jQuery('#upt-imob-stat-photos').text(photos_total);
        jQuery('#upt-imob-stat-errors').text(errors_total);
    }

    function countAndProcess() {
        setStatus('Contando imóveis no XML...', 'info');
        jQuery.post(ajaxUrl, { action: 'upt_imob_count', nonce: nonce, session_id: session_id }, function(resp) {
            if (!resp.success) {
                setStatus('Erro ao contar: ' + (resp.data && resp.data.message ? resp.data.message : 'Erro desconhecido') + '. A importação será interrompida.', 'error');
                return;
            }
            total_items = resp.data.total;
            updateStats();
            setStatus(total_items + ' imóveis encontrados. Iniciando importação...', 'info');
            setTimeout(processNextBatch, 600);
        });
    }

    function processNextBatch() {
        if (!is_running) return;
        var batchEnd = Math.min(current_offset + batch_size, total_items);
        setStatus('Importando imóveis ' + (current_offset + 1) + ' a ' + batchEnd + ' de ' + total_items + '...', 'info');
        jQuery.post(ajaxUrl, { action: 'upt_imob_batch', nonce: nonce, session_id: session_id, offset: current_offset, limit: batch_size }, function(resp) {
            if (!is_running) return;
            if (!resp.success) {
                setStatus('Erro no lote: ' + (resp.data && resp.data.message ? resp.data.message : '?') + '. Pulando e continuando...', 'warning');
                current_offset += batch_size; updateStats(); setTimeout(processNextBatch, 2000); return;
            }
            var d = resp.data;
            imported_total += d.imported; photos_total += d.photos; errors_total += d.errors; current_offset = d.next_offset; updateStats();
            if (d.last_error) setStatus('Aviso: ' + d.last_error + '. Continuando...', 'warning');
            else setStatus('Processados ' + current_offset + ' de ' + total_items + ' imóveis. ' + photos_total + ' fotos baixadas.', 'info');
            if (d.is_finished) {
                is_running = false;
                $progressSection.hide();
                $doneSection.show();
                $stepper.find('.upt-step').addClass('upt-step--completed').removeClass('upt-step--active');
                jQuery('#upt-imob-done-stats').html(
                    '<strong style="color:#16a34a">' + imported_total + '</strong> imóveis importados' +
                    (errors_total > 0 ? ' &bull; <strong style="color:#dc2626">' + errors_total + ' erros</strong>' : '') +
                    ' &bull; <strong style="color:#2563eb">' + photos_total + '</strong> fotos baixadas'
                );
            }
            else setTimeout(processNextBatch, 500);
        }).fail(function() {
            if (!is_running) return;
            setStatus('Erro de conexão. Tentando novamente em 3 segundos...', 'warning');
            setTimeout(processNextBatch, 3000);
        });
    }

    function setStatus(msg, type) {
        var $el = jQuery('#upt-imob-status-msg');
        var icon = type === 'error' ? '❌' : type === 'warning' ? '⚠️' : type === 'success' ? '✅' : '⏳';
        $el.attr('class', 'upt-import-msg upt-import-msg--' + (type || 'info'))
           .html('<span class="upt-import-msg__icon" aria-hidden="true">' + icon + '</span><span class="upt-import-msg__text">' + msg + '</span>');
    }
})();
