(function($) {
    'use strict';

    // Resolve helpers across nested iframes (Elementor/editor can nest frames)
    function fcResolveFn(fnName, maxDepth) {
        var depth = (typeof maxDepth === 'number') ? maxDepth : 10;

        // 1) Walk up parents
        var w = window;
        for (var i = 0; i < depth; i++) {
            try {
                if (w && typeof w[fnName] === 'function') {
                    return w[fnName].bind(w);
                }
            } catch (e1) {}

            try {
                if (!w || !w.parent || w.parent === w) { break; }
                w = w.parent;
            } catch (e2) { break; }
        }

        // 2) Try top
        try {
            if (window.top && typeof window.top[fnName] === 'function') {
                return window.top[fnName].bind(window.top);
            }
        } catch (e3) {}

        return null;
    }

    function fcNotify(type, message) {
        var fn = fcResolveFn('uptNotify');
        if (fn) {
            try { fn(type, message); } catch (e) {}
        }
    }

    function fcShowBadge(message) {
        var badgeFn = fcResolveFn('uptShowAlertBadge');
        if (badgeFn) {
            try { badgeFn(message); return; } catch (e) {}
        }

        var notifyFn = fcResolveFn('uptNotify');
        if (notifyFn) {
            try { notifyFn('media_moved', message); return; } catch (e2) {}
        }

        try { console.warn(message); } catch (e3) {}
    }

    // Local fallback confirm modal (never uses browser confirm)
    function fcLocalConfirm(message, onConfirm, options) {
        options = options || {};
        try { $('#upt-confirm-overlay, #upt-confirm-modal').remove(); } catch (e0) {}

        var overlay = $('<div id="upt-confirm-overlay"></div>').css({
            position: 'fixed', left: 0, top: 0, right: 0, bottom: 0,
            background: 'rgba(0,0,0,0.35)', zIndex: 2147483646
        });

        var modal = $('<div id="upt-confirm-modal"></div>').css({
            position: 'fixed', left: '50%', top: '50%',
            transform: 'translate(-50%, -50%)',
            background: '#fff', padding: '22px 24px',
            borderRadius: '14px',
            boxShadow: '0 12px 40px rgba(0,0,0,0.25)',
            zIndex: 2147483647,
            width: 'min(640px, calc(100vw - 40px))'
        });

        var msg = $('<p></p>').text(message || 'Tem certeza?').css({
            margin: '0 0 18px 0', color: '#222', fontSize: '15px', lineHeight: '1.45'
        });

        var contentWrapper = $('<div></div>').css({
            display: 'flex', flexDirection: 'column', gap: '10px'
        });
        contentWrapper.append(msg);

        if (options.extraContent) {
            contentWrapper.append(options.extraContent);
        }

        var actions = $('<div></div>').css({
            display: 'flex', gap: '10px', justifyContent: 'flex-end'
        });

        var btnCancel = $('<button type="button">Cancelar</button>').css({
            background: '#f2f2f2', color: '#111',
            border: '1px solid rgba(0,0,0,0.12)',
            borderRadius: '10px', padding: '10px 16px', cursor: 'pointer'
        });

        var btnDelete = $('<button type="button">Excluir</button>').css({
            background: '#c62828', color: '#fff',
            border: '1px solid rgba(0,0,0,0.12)',
            borderRadius: '10px', padding: '10px 16px', cursor: 'pointer'
        });

        btnCancel.on('mouseenter', function(){ $(this).css('background', '#e9e9e9'); })
                 .on('mouseleave', function(){ $(this).css('background', '#f2f2f2'); });

        btnDelete.on('mouseenter', function(){ $(this).css('background', '#b71c1c'); })
                 .on('mouseleave', function(){ $(this).css('background', '#c62828'); });

        function close() {
            overlay.remove();
            modal.remove();
        }

        btnCancel.on('click', close);
        overlay.on('click', close);

        btnDelete.on('click', function() {
            var confirmData = (options && typeof options.onBeforeConfirm === 'function') ? options.onBeforeConfirm() : undefined;
            close();
            try { if (typeof onConfirm === 'function') { onConfirm(confirmData); } } catch (e4) {}
        });

        actions.append(btnCancel, btnDelete);
        modal.append(contentWrapper, actions);

        $('body').append(overlay, modal);
    }

    function fcConfirm(message, onConfirm, options) {
        var fn = fcResolveFn('uptConfirmDialog');
        if (fn) {
            try {
                fn(message, function(confirmData) {
                    try { if (typeof onConfirm === 'function') { onConfirm(confirmData); } } catch (e2) {}
                }, options);
                return;
            } catch (e3) {}
        }

        // fallback inside iframe (no browser prompt)
        fcLocalConfirm(message, onConfirm, options);
    }


    $(function() {
        var sidebarList = $('#upt-folder-list');
        var imageGrid = $('#upt-image-grid');
        var currentFolderName = $('#upt-current-folder');
        var createFolderBtn = $('#create-folder-button');
        var newFolderNameInput = $('#new-folder-name');
        var newFolderParentSelect = $('#new-folder-parent');
        var createFolderStatus = $('#create-folder-status');
        var bulkDeleteFoldersBtn = $('#delete-folders-bulk-button');
        
        var actionsBar = $('#gallery-actions');
        var useImageBtn = $('#use-image-button');
        var deleteBtn = $('#delete-image-button');
        var selectAllBtn = $('#select-all-button');
        var deselectAllBtn = $('#deselect-all-button');
        var moveToWrapper = $('#move-to-folder-wrapper');
        var moveToSelect = $('#move-to-folder-select');
        var removeFromFolderBtn = $('#remove-from-folder-button');
        var clearSelectionBtn = $('#upt-clear-selection-button');

        // Folder move support (drag & drop)
        var uptFolderParentMap = {};
        var uptFoldersByParent = {};
        var uptDraggedFolderId = null;

        var uploadProgress = $('#upt-upload-progress');
        var uploadContainer = $('#upt-upload-container');
        var uploadButton = uploadContainer.find('label[for="upt-uploader"]');
        var uploadButtonText = uploadButton.find('.upt-upload-button-main-text');
        var uploadButtonSubtext = uploadButton.find('.upt-upload-button-subtext');
        var isUploading = false;
        var activeUploadRequests = [];
        var uploadProgressTimer = null;
        var uploadCancelled = false;
        var currentUploaderInput = null;
        var sidebar = $('.gallery-sidebar');
        var toggleSidebarBtn = $('#toggle-sidebar-button');
        var closeSidebarBtn = $('#close-sidebar-button');
        var galleryOverlay = $('.gallery-overlay');
        var mainCloseBtn = $('#upt-gallery-close');
        var paginationWrapper = $('#upt-gallery-pagination');

        var isModalMode = new URLSearchParams(window.location.search).has('noheader');
        var isMultiSelectMode = new URLSearchParams(window.location.search).get('selection') === 'multiple';
        var urlParams = new URLSearchParams(window.location.search);
        var isGalleryPaginationEnabled = urlParams.get('gallery_pagination') === 'yes';
        var galleryPerPage = parseInt(urlParams.get('gallery_per_page') || '', 10);
        if (!galleryPerPage || galleryPerPage < 1) {
            galleryPerPage = 30;
        }
        var galleryPaginationType = String(urlParams.get('gallery_pagination_type') || 'infinite');
        if (['numbers', 'arrows', 'prev_next', 'infinite'].indexOf(galleryPaginationType) === -1) {
            galleryPaginationType = 'infinite';
        }
        var galleryInfiniteTrigger = String(urlParams.get('gallery_pagination_infinite_trigger') || 'scroll');
        var galleryNumbersNav = String(urlParams.get('gallery_pagination_numbers_nav') || 'yes');
        var galleryLoadMoreLabel = String(urlParams.get('gallery_pagination_load_more_label') || 'Carregar mais');
        var fcGalleryVarsRaw = urlParams.get('fc_gallery_vars') || '';
        var loaderHTML = '<div class="gallery-loader"><span class="spinner is-active" style="float:none;"></span> Carregando...</div>';
        var lastSelectedItem = null;

        var galleryPaginationState = {
            enabled: isGalleryPaginationEnabled,
            type: galleryPaginationType,
            infiniteTrigger: galleryInfiniteTrigger,
            numbersNav: galleryNumbersNav,
            loadMoreLabel: galleryLoadMoreLabel,
            perPage: isGalleryPaginationEnabled ? galleryPerPage : -1,
            page: 1,
            totalPages: 1,
            isLoading: false,
            folderId: 0,
            folderName: ''
        };

        if (fcGalleryVarsRaw) {
            try {
				var parsed = null;
				try {
					parsed = JSON.parse(fcGalleryVarsRaw);
				} catch (e) {
					parsed = JSON.parse(decodeURIComponent(fcGalleryVarsRaw));
				}
                if (parsed && typeof parsed === 'object') {
                    function fcShouldApplyCssVar(name, value) {
                        if (typeof name !== 'string') return false;
                        if (typeof value !== 'string') return false;
                        var v = value.trim();
                        if (!v) return false;

                        var lc = v.toLowerCase();
                        if (/(^|-)font-size$/.test(name) && (lc === '0' || lc === '0px' || lc === '0.0px')) {
                            return false;
                        }
                        if (/(^|-)padding$/.test(name)) {
                            var parts = lc.split(/\s+/).filter(Boolean);
                            if (parts.length && parts.every(function (p) { return p === '0' || p === '0px' || p === '0.0px'; })) {
                                return false;
                            }
                        }
                        return true;
                    }

                    Object.keys(parsed).forEach(function (k) {
                        if (typeof k !== 'string') return;
                        if (k.indexOf('--fc-') !== 0 && k.indexOf('--fc-gp-') !== 0) return;
                        var v = parsed[k];
                        if (!fcShouldApplyCssVar(k, v)) return;
                        document.documentElement.style.setProperty(k, v);
                    });
                }
            } catch (e) { }
        }

        // Mantém a seleção de mídias entre mudanças de pasta e reaberturas do painel
        var globalSelectedMediaIds = [];
        var globalSelectedMediaMap = {};
        var uptFolderMap = { 0: { name: 'Midias sem pasta', parent: null } };

        function flattenFoldersForBulk(parentId, depth, acc) {
            var stack = acc || [];
            var children = uptFoldersByParent[parentId] || [];

            children.forEach(function(folder) {
                var id = parseInt(folder.term_id || 0, 10) || 0;

                if (id > 0) {
                    stack.push({ id: id, name: folder.name || 'Pasta', depth: depth });
                }

                flattenFoldersForBulk(id, depth + 1, stack);
            });

            return stack;
        }

        function getBulkFolderList() {
            return flattenFoldersForBulk(0, 0, []);
        }

        function fcEscape(text) {
            return String(text || '').replace(/[&<>'"]/g, function(ch) {
                switch (ch) {
                    case '&': return '&amp;';
                    case '<': return '&lt;';
                    case '>': return '&gt;';
                    case '"': return '&quot;';
                    case "'": return '&#39;';
                    default: return ch;
                }
            });
        }

        function buildBreadcrumb(folderId) {
            var path = [];
            var cursor = (typeof folderId === 'number') ? folderId : parseInt(folderId || 0, 10) || 0;
            var guard = 0;

            while (cursor !== null && guard < 200) {
                var node = uptFolderMap[cursor];
                var name = node && node.name ? node.name : 'Pasta';
                path.push({ id: cursor, name: name });

                var next = node ? node.parent : null;
                if (next === null || typeof next === 'undefined' || next === cursor) {
                    break;
                }
                cursor = parseInt(next || 0, 10) || 0;
                guard++;
            }

            return path.reverse();
        }

        function renderBreadcrumb(folderId) {
            var container = $('#upt-breadcrumb');
            if (!container.length) return;

            var trail = buildBreadcrumb(folderId);
            if (!trail.length) {
                container.empty();
                return;
            }

            var html = [];
            trail.forEach(function(node, idx) {
                var isLast = idx === trail.length - 1;
                var safeName = fcEscape(node.name);
                if (!isLast) {
                    html.push('<button type="button" class="upt-breadcrumb__link" data-folder-id="' + node.id + '">' + safeName + '</button>');
                    html.push('<span class="upt-breadcrumb__sep">/</span>');
                } else {
                    html.push('<span class="upt-breadcrumb__current">' + safeName + '</span>');
                }
            });

            container.html(html.join(''));
        }

        function closeAllFolderDropdowns() {
            sidebarList.find('.folder-options-dropdown').removeClass('is-open').hide();
            sidebarList.find('.folder-options-button').attr('aria-expanded', 'false');
        }

        sidebarList.on('click', '.folder-options-button', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var button = $(this);
            var dropdown = button.closest('li').find('.folder-options-dropdown');
            var isOpen = dropdown.hasClass('is-open');

            closeAllFolderDropdowns();

            if (!isOpen) {
                dropdown.addClass('is-open').show();
                button.attr('aria-expanded', 'true');

                // Position dropdown using viewport coords to avoid being clipped by scroll containers
                var rect = button[0].getBoundingClientRect();
                var ddWidth = dropdown.outerWidth();
                var left = rect.right - ddWidth;
                var top = rect.bottom + 6; // small offset below button
                dropdown.css({
                    position: 'fixed',
                    top: top + 'px',
                    left: left + 'px',
                    right: 'auto'
                });
            }
        });

        $(document).on('click', function(e) {
            if ($(e.target).closest('.folder-options-button, .folder-options-dropdown').length === 0) {
                closeAllFolderDropdowns();
            }
        });

        function applyGlobalSelectionToCurrentFolder() {
            imageGrid.find('.gallery-image-item, .gallery-video-item, .gallery-pdf-item').each(function() {
                var $item = $(this);
                var id = String($item.data('id'));
                if (globalSelectedMediaIds.indexOf(id) !== -1) {
                    $item.addClass('selected');
                }
            });
        }

        function updateGlobalSelectionForCurrentFolder() {
            var folderIds = [];
            var selectedIdsInFolder = [];

            imageGrid.find('.gallery-image-item, .gallery-video-item, .gallery-pdf-item').each(function() {
                var $item = $(this);
                var id = String($item.data('id'));
                folderIds.push(id);
                if ($item.hasClass('selected')) {
                    selectedIdsInFolder.push(id);
                }
            });

            if (isMultiSelectMode) {
                // Modo galeria (várias mídias): mantém todas as seleções de todas as pastas
                // Remove seleções antigas desta pasta
                globalSelectedMediaIds = globalSelectedMediaIds.filter(function(id) {
                    return folderIds.indexOf(id) === -1;
                });

                // Adiciona seleções atuais desta pasta
                selectedIdsInFolder.forEach(function(id) {
                    if (globalSelectedMediaIds.indexOf(id) === -1) {
                        globalSelectedMediaIds.push(id);
                    }
                });
            } else {
                // Modo mídia única (imagem/vídeo): sempre prioriza a última seleção
                if (selectedIdsInFolder.length > 0) {
                    var lastId = selectedIdsInFolder[selectedIdsInFolder.length - 1];
                    globalSelectedMediaIds = [lastId];
                } else {
                    globalSelectedMediaIds = [];
                }
            }

            // Notifica o pai para persistir a seleção enquanto o painel existir
            if (window.parent && window.parent !== window) {
                try {
                    window.parent.postMessage({ uptGallerySelectionUpdate: globalSelectedMediaIds }, '*');
                } catch (e) {}
            }
        }

        // Restaura a seleção enviada pelo pai (quando o painel é reaberto)
        window.addEventListener('message', function(event) {
            if (event.data && event.data.uptRestoreSelection) {
                var incoming = event.data.uptRestoreSelection || [];
                globalSelectedMediaIds = incoming.map(function(id) { return String(id); });
                applyGlobalSelectionToCurrentFolder();
                updateActionsBar();
            }
        });


        function loadFolders(callback) {
            sidebarList.html('<li>Carregando pastas...</li>');
            $.ajax({
                url: upt_gallery.ajax_url,
                type: 'POST',
                data: { action: 'upt_gallery_get_folders', nonce: upt_gallery.nonce },
                success: function(response) {
                    if (response.success) {
                        sidebarList.empty();
                        sidebarList.append(
                            '<li class="no-delete">' +
                                '<a href="#" class="active" data-folder-id="0">' +
                                    '<span class="upt-folder-list__icon" aria-hidden="true"></span>' +
                                    '<span class="upt-folder-list__label">Midias sem pasta</span>' +
                                '</a>' +
                            '</li>'
                        );

                        moveToSelect.find('option:gt(0)').remove();

                        // Build tree by parent
                        var folders = Array.isArray(response.data) ? response.data : [];
                        uptFolderParentMap = {};
                        uptFolderMap = { 0: { name: 'Midias sem pasta', parent: null } };
                        folders.forEach(function(f) {
                            var id = parseInt(f.term_id || 0, 10) || 0;
                            if (!id) return;
                            uptFolderParentMap[id] = parseInt(f.parent || 0, 10) || 0;
                            uptFolderMap[id] = { name: String(f.name || ''), parent: parseInt(f.parent || 0, 10) || 0 };
                        });
                        var byParent = {};
                        folders.forEach(function(f) {
                            var p = parseInt(f.parent || 0, 10) || 0;
                            if (!byParent[p]) byParent[p] = [];
                            byParent[p].push(f);
                        });

                        uptFoldersByParent = byParent;

                        Object.keys(byParent).forEach(function(k) {
                            byParent[k].sort(function(a, b) {
                                return String(a.name || '').localeCompare(String(b.name || ''));
                            });
                        });

                        // Rebuild parent select
                        if (newFolderParentSelect && newFolderParentSelect.length) {
                            newFolderParentSelect.empty();
                            newFolderParentSelect.append($('<option>', { value: 0, text: 'Criar em: Raiz' }));
                        }

                        function indentLabel(depth) {
                            if (!depth) return '';
                            var s = '';
                            for (var i = 0; i < depth; i++) s += '— ';
                            return s;
                        }

                        function renderLevel(parentId, depth) {
                            var list = byParent[parentId] || [];
                            list.forEach(function(folder) {
                                var d = depth || 0;
                                var pad = 15 + (d * 14);

                                var listItem =
                                    '<li class="upt-folder-item" draggable="true" data-term-id="' + folder.term_id + '">' +
                                        '<a href="#" data-folder-id="' + folder.term_id + '" data-folder-parent="' + (folder.parent || 0) + '" style="padding-left:' + pad + 'px">' +
                                            '<span class="upt-folder-list__icon" aria-hidden="true"></span>' +
                                            '<span class="upt-folder-list__label">' + folder.name + '</span>' +
                                        '</a>' +
                                        '<button type="button" class="folder-options-button button-icon-only" ' +
                                            'data-term-id="' + folder.term_id + '" ' +
                                            'data-term-name="' + folder.name + '" ' +
                                            'title="Opções da pasta" aria-haspopup="true" aria-expanded="false">' +
                                            '<svg class="fc-icon fc-icon-more" viewBox="0 0 24 24" aria-hidden="true">' +
                                                '<circle cx="5" cy="12" r="2" fill="#666"></circle>' +
                                                '<circle cx="12" cy="12" r="2" fill="#666"></circle>' +
                                                '<circle cx="19" cy="12" r="2" fill="#666"></circle>' +
                                            '</svg>' +
                                        '</button>' +
                                        '<div class="folder-options-dropdown" role="menu" aria-label="Ações da pasta">' +
                                            '<button type="button" class="rename-folder-button" ' +
                                                'data-term-id="' + folder.term_id + '" ' +
                                                'data-term-name="' + folder.name + '" ' +
                                                'role="menuitem">Renomear</button>' +
                                            '<button type="button" class="delete-folder-button" ' +
                                                'data-term-id="' + folder.term_id + '" ' +
                                                'data-term-name="' + folder.name + '" ' +
                                                'role="menuitem">Excluir</button>' +
                                        '</div>' +
                                    '</li>';

                                sidebarList.append(listItem);

                                moveToSelect.append($('<option>', {
                                    value: folder.term_id,
                                    text: indentLabel(d) + folder.name
                                }));

                                if (newFolderParentSelect && newFolderParentSelect.length) {
                                    newFolderParentSelect.append($('<option>', {
                                        value: folder.term_id,
                                        text: 'Criar em: ' + indentLabel(d) + folder.name
                                    }));
                                }

                                renderLevel(parseInt(folder.term_id, 10), d + 1);
                            });
                        }

                        renderLevel(0, 0);


                        // Default: parent select points to folder currently active
                        if (newFolderParentSelect && newFolderParentSelect.length) {
                            var activeId = parseInt(sidebarList.find('a.active').data('folder-id') || 0, 10) || 0;
                            if (activeId > 0 && newFolderParentSelect.find('option[value="' + activeId + '"]').length) {
                                newFolderParentSelect.val(String(activeId));
                            } else {
                                newFolderParentSelect.val('0');
                            }
                        }

                        if (callback) {
                            callback();
                        }

                        // Atualiza breadcrumb para a pasta ativa atual
                        var activeIdAfterLoad = parseInt(sidebarList.find('a.active').data('folder-id') || 0, 10) || 0;
                        renderBreadcrumb(activeIdAfterLoad);
                    } else {
                        sidebarList.html('<li>Erro ao carregar pastas.</li>');
                    }
                }
            });
        }

function loadImages(folderId, folderName, highlightId, options) {
            var opts = options || {};
            var appendMode = !!opts.append;
            var keepFolders = !!opts.keepFolders;
            var requestedPage = parseInt(opts.page || 0, 10) || 0;
            if (requestedPage < 1) {
                requestedPage = galleryPaginationState.page || 1;
            }

            galleryPaginationState.folderId = parseInt(folderId || 0, 10) || 0;
            galleryPaginationState.folderName = folderName || '';
            galleryPaginationState.page = requestedPage;

            if (!appendMode) {
                if (keepFolders) {
                    imageGrid.find('.gallery-image-item, .gallery-video-item, .gallery-pdf-item, .gallery-loader').remove();
                } else {
                    imageGrid.html(loaderHTML);
                }
            } else {
                imageGrid.find('.gallery-loader').remove();
            }

            currentFolderName.text(folderName);
            updateActionsBar();

            galleryPaginationState.isLoading = true;
            $.ajax({
                url: upt_gallery.ajax_url,
                type: 'POST',
                data: {
                    action: 'upt_gallery_get_images',
                    nonce: upt_gallery.nonce,
                    folder_id: folderId,
                    media_type: (new URLSearchParams(window.location.search).get('media_type') || ''),
                    per_page: galleryPaginationState.enabled ? galleryPaginationState.perPage : 0,
                    page: galleryPaginationState.enabled ? requestedPage : 1
                },
                success: function(response) {
                    if (response.success) {
                        var payload = response.data || {};
                        var items = Array.isArray(payload) ? payload : (payload.items || []);
                        var pagination = (payload && payload.pagination) ? payload.pagination : null;

                        if (!appendMode) {
                            if (!keepFolders) {
                                imageGrid.empty();
                            } else {
                                imageGrid.find('.gallery-image-item, .gallery-video-item, .gallery-pdf-item, .gallery-loader').remove();
                            }
                        }

                        if (pagination && galleryPaginationState.enabled) {
                            galleryPaginationState.totalPages = parseInt(pagination.total_pages || 1, 10) || 1;
                        } else {
                            galleryPaginationState.totalPages = 1;
                        }

                        if (items.length > 0) {
                            var pdfFallback = (typeof upt_gallery !== 'undefined' && upt_gallery.pdf_placeholder) ? upt_gallery.pdf_placeholder : '';

                            items.forEach(function(media) {
                                var itemClass = (media.type === 'video') ? 'gallery-video-item' : ((media.type === 'pdf') ? 'gallery-pdf-item' : 'gallery-image-item');
                                if (media.is_fallback) {
                                    itemClass += ' is-fallback-icon';
                                }
                                if (highlightId && String(media.id) === String(highlightId)) {
                                    itemClass += ' selected';
                                }
                                var fileName = media.filename || media.name || (media.full_url ? media.full_url.split('/').pop() : '');
                                if (fileName && fileName.indexOf('.') === -1 && media.full_url) {
                                    var fromUrl = media.full_url.split('/').pop();
                                    if (fromUrl && fromUrl.indexOf('.') !== -1) {
                                        fileName = fromUrl;
                                    }
                                }
                                var safeFileName = fcEscape(fileName);
                                var safeFullUrl = fcEscape(media.full_url || '');
                                var safeFileSizeHuman = fcEscape(media.file_size_human || '');

                                var videoSrc = media.full_url || media.thumbnail_url || '';
                                var safeVideoSrc = fcEscape(videoSrc);
                                var overlaySrc = (typeof upt_gallery !== 'undefined' && upt_gallery.transparent_png)
                                    ? upt_gallery.transparent_png
                                    : (media.thumbnail_url || '');
                                var safeOverlaySrc = fcEscape(overlaySrc);

                                var resolvedThumb = media.thumbnail_url;
                                if (media.type === 'image' && (!resolvedThumb || resolvedThumb === '')) {
                                    resolvedThumb = media.full_url || '';
                                }
                                if (media.type === 'pdf' && (!resolvedThumb || resolvedThumb === '')) {
                                    resolvedThumb = pdfFallback;
                                }
                                var safeResolvedThumb = fcEscape(resolvedThumb || '');

                                var lowerFileName = String(fileName || '').toLowerCase();
                                var lowerThumb = String(resolvedThumb || '').toLowerCase();
                                var isSvg = (String(media.mime_type || '') === 'image/svg+xml')
                                    || lowerFileName.indexOf('.svg') !== -1
                                    || lowerThumb.indexOf('.svg') !== -1;
                                if (media.type === 'image' && isSvg) {
                                    itemClass += ' is-svg';
                                }

                                var thumbHtml;
                                if (media.type === 'video') {
                                    thumbHtml =
                                        '<div class="upt-video-thumb">' +
                                            '<video muted playsinline preload="metadata" src="' + safeVideoSrc + '#t=1"></video>' +
                                            '<img class="upt-video-overlay" src="' + safeOverlaySrc + '" alt="" loading="lazy">' +
                                        '</div>';
                                } else if (media.type === 'pdf') {
                                    var pdfThumb = resolvedThumb || pdfFallback;
                                    var pdfOnError = pdfFallback ? ' onerror="this.onerror=null;this.src=\'' + pdfFallback + '\';"' : '';
                                    thumbHtml = '<img src="' + fcEscape(pdfThumb) + '" alt="" loading="lazy"' + pdfOnError + '>';
                                } else {
                                    thumbHtml = '<img src="' + safeResolvedThumb + '" alt="" loading="lazy">';
                                }

                                // Registra dados da mídia para reuso na seleção global
                                globalSelectedMediaMap[String(media.id)] = {
                                    id: media.id,
                                    thumbnail_url: resolvedThumb || media.thumbnail_url,
                                    full_url: media.full_url,
                                    type: media.type
                                };

                                var itemHtml =
                                    '<div class="' + itemClass + '" ' +
                                        'data-id="' + media.id + '" ' +
                                        'data-full-url="' + safeFullUrl + '" ' +
                                        'data-thumbnail-url="' + safeResolvedThumb + '" ' +
                                        'data-type="' + fcEscape(media.type) + '" ' +
                                        'data-file-name="' + safeFileName + '" ' +
                                        'data-filename="' + safeFileName + '" ' +
                                        'data-file-size-human="' + safeFileSizeHuman + '" ' +
                                        'data-download-url="' + safeFullUrl + '" ' +
                                        'title="' + safeFileName + '">' +
                                        thumbHtml +
                                        '<div class="gallery-item-name" title="' + safeFileName + '">' + safeFileName + '</div>' +
                                    '</div>';
                                imageGrid.append(itemHtml);
                            });
                        } else if (!appendMode && imageGrid.find('.upt-folder-card').length === 0) {
                            imageGrid.html('<p>Nenhuma mídia encontrada.</p>');
                            paginationWrapper.hide().empty();
                        }

                        if (galleryPaginationState.enabled) {
                            renderGalleryPagination();
                        } else {
                            paginationWrapper.hide().empty();
                        }

                        // Reaplica seleção global para esta pasta
                        applyGlobalSelectionToCurrentFolder();
                        updateActionsBar();
                    } else {
                        if (!appendMode) {
                            imageGrid.html('<p>Erro ao carregar mídias.</p>');
                        }
                    }

                    galleryPaginationState.isLoading = false;
                }
            });
        }

        function ensureLoadMoreButton() {
            if (!galleryPaginationState.enabled) { return; }

            var trigger = String(galleryPaginationState.infiniteTrigger || 'scroll');
            var shouldShowButton = (trigger === 'button' || trigger === 'both');

            var $existing = paginationWrapper.find('button.upt-load-more');
            if (!shouldShowButton || galleryPaginationState.page >= galleryPaginationState.totalPages) {
                $existing.remove();
                return;
            }

            var safeLabel = String(galleryPaginationState.loadMoreLabel || '').trim() || 'Carregar mais';
            if (!$existing.length) {
                paginationWrapper.append('<button type="button" class="upt-load-more"></button>');
                $existing = paginationWrapper.find('button.upt-load-more').last();
            }

            $existing
                .text(safeLabel)
                .prop('disabled', !!galleryPaginationState.isLoading);

            if (!paginationWrapper.find('.upt-load-more-sentinel').length) {
                paginationWrapper.append(
                    '<div class="upt-load-more-sentinel" data-next-page="" data-total-pages="" aria-hidden="true"></div>'
                );
            }

            try {
                paginationWrapper
                    .find('.upt-load-more-sentinel')
                    .attr('data-next-page', String((galleryPaginationState.page || 0) + 1))
                    .attr('data-total-pages', String(galleryPaginationState.totalPages || 1));
            } catch (e) {}
        }

        function renderGalleryPagination() {
            if (!paginationWrapper.length) {
                return;
            }

            if (!galleryPaginationState.enabled) {
                paginationWrapper.hide().empty();
                return;
            }

            var totalPages = galleryPaginationState.totalPages || 1;
            var page = galleryPaginationState.page || 1;
            if (totalPages <= 1) {
                paginationWrapper
                    .removeClass('upt-pagination-wrapper upt-pagination-infinite')
                    .addClass('upt-gallery-pagination')
                    .hide()
                    .empty();
                return;
            }

            try {
                if (paginationWrapper.closest('#upt-image-grid').length) {
                    paginationWrapper.detach();
                    imageGrid.after(paginationWrapper);
                }
            } catch (e0) {}

            paginationWrapper
                .removeClass('upt-pagination-wrapper upt-pagination-infinite')
                .addClass('upt-gallery-pagination')
                .css('display', 'flex')
                .empty();

            if (galleryPaginationState.type === 'infinite') {
                paginationWrapper.addClass('upt-pagination-infinite');
                paginationWrapper.css('display', 'flex');
                ensureLoadMoreButton();
                bindInfiniteScrollIfNeeded();
                return;
            }

            paginationWrapper.addClass('upt-pagination-wrapper');
            paginationWrapper.css('display', 'flex');

            var shouldShowNavButtons = (galleryPaginationState.type !== 'numbers') || String(galleryPaginationState.numbersNav || 'yes') === 'yes';
            var prevLabel = (galleryPaginationState.type === 'arrows') ? '‹' : 'Anterior';
            var nextLabel = (galleryPaginationState.type === 'arrows') ? '›' : 'Próximo';

            if (shouldShowNavButtons && page > 1) {
                paginationWrapper.append(
                    $('<button type="button" class="page-numbers prev"></button>')
                        .text(prevLabel)
                        .attr('data-page', String(page - 1))
                        .attr('aria-label', 'Página anterior')
                );
            }

            if (galleryPaginationState.type === 'numbers') {
                var windowSize = 7;
                var start = Math.max(1, page - Math.floor(windowSize / 2));
                var end = Math.min(totalPages, start + windowSize - 1);
                start = Math.max(1, end - windowSize + 1);

                function addPageButton(p) {
                    var $b = $('<button type="button" class="page-numbers"></button>')
                        .text(String(p))
                        .attr('data-page', String(p));

                    if (p === page) {
                        $b.addClass('current').prop('disabled', true).attr('aria-current', 'page');
                    }
                    paginationWrapper.append($b);
                }

                if (start > 1) {
                    addPageButton(1);
                    if (start > 2) {
                        paginationWrapper.append('<span class="page-numbers dots" aria-hidden="true">…</span>');
                    }
                }

                for (var p = start; p <= end; p++) {
                    addPageButton(p);
                }

                if (end < totalPages) {
                    if (end < totalPages - 1) {
                        paginationWrapper.append('<span class="page-numbers dots" aria-hidden="true">…</span>');
                    }
                    addPageButton(totalPages);
                }
            }

            if (shouldShowNavButtons && page < totalPages) {
                paginationWrapper.append(
                    $('<button type="button" class="page-numbers next"></button>')
                        .text(nextLabel)
                        .attr('data-page', String(page + 1))
                        .attr('aria-label', 'Próxima página')
                );
            }
        }

        function requestGalleryPage(nextPage, options) {
            var target = parseInt(nextPage || 0, 10) || 1;
            if (target < 1) target = 1;
            if (target > galleryPaginationState.totalPages) target = galleryPaginationState.totalPages;

            var keepFolders = !!(options && options.keepFolders);

            if (galleryPaginationState.isLoading) {
                return;
            }
            if (galleryPaginationState.enabled && target === galleryPaginationState.page && galleryPaginationState.type !== 'infinite') {
                return;
            }

            var append = !!(options && options.append);
            galleryPaginationState.page = target;
            loadImages(galleryPaginationState.folderId, galleryPaginationState.folderName, null, {
                append: append,
                keepFolders: keepFolders,
                page: target
            });
        }

        function renderChildFolders(folderId, folderName) {
            var children = uptFoldersByParent[folderId] || [];
            currentFolderName.text(folderName);
            imageGrid.empty();

            // Primeiro mostra as pastas-filhas
            children.forEach(function(child) {
                var gradSuffix = String(child.term_id || Math.random()).replace(/[^a-zA-Z0-9_-]/g, '');
                var backGradId = 'fc-folder-back-' + gradSuffix;
                var frontGradId = 'fc-folder-front-' + gradSuffix;
                var barGradId = 'fc-folder-bar-' + gradSuffix;

                var card = $('<div class="upt-folder-card" tabindex="0" role="button" aria-label="Abrir pasta ' + child.name + '"></div>');
                card.attr('data-folder-id', child.term_id);
                card.attr('data-folder-name', child.name);
                card.html(
                    '<div class="upt-folder-card__icon" aria-hidden="true">' +
                        '<svg class="upt-folder-card__svg" viewBox="136 130 800 660" preserveAspectRatio="xMidYMid meet" role="presentation" aria-hidden="true" focusable="false">' +
                            '<defs>' +
                                '<linearGradient id="' + backGradId + '" x1="533.84" y1="50" x2="533.84" y2="269.59" gradientUnits="userSpaceOnUse">' +
                                    '<stop offset="0" stop-color="#fff" />' +
                                    '<stop offset="1" stop-color="#000" />' +
                                '</linearGradient>' +
                                '<linearGradient id="' + frontGradId + '" x1="128.32" y1="514.49" x2="933.02" y2="514.49" gradientUnits="userSpaceOnUse">' +
                                    '<stop offset="0" stop-color="#000" />' +
                                    '<stop offset=".05" stop-color="#787878" stop-opacity=".53" />' +
                                    '<stop offset=".32" stop-color="#fff" stop-opacity="0" />' +
                                    '<stop offset=".68" stop-color="#fff" stop-opacity="0" />' +
                                    '<stop offset=".95" stop-color="#878787" stop-opacity=".47" />' +
                                    '<stop offset="1" stop-color="#000" />' +
                                '</linearGradient>' +
                                '<linearGradient id="' + barGradId + '" x1="532.72" y1="699.13" x2="532.72" y2="771.46" gradientUnits="userSpaceOnUse">' +
                                    '<stop offset=".35" stop-color="#000" stop-opacity="0" />' +
                                    '<stop offset=".52" stop-color="#fff" stop-opacity=".2" />' +
                                    '<stop offset=".7" stop-color="#000" stop-opacity="0" />' +
                                '</linearGradient>' +
                            '</defs>' +
                            '<g class="folder-shape">' +
                                '<path class="base-fill" d="M864.51,787.3H210.18c-36.45,0-66-29.55-66-66V192.12c0-34.15,27.69-61.84,61.84-61.84h164.94c7.37,0,14.57,2.24,20.63,6.43l52.03,38.35c15.42,11.37,34.08,17.5,53.24,17.5h371.38c30.52,0,55.26,24.74,55.26,55.26v480.47c0,32.58-26.42,59-59,59Z" />' +
                                '<path class="gradOverlay" d="M864.51,787.3H210.18c-36.45,0-66-29.55-66-66V192.12c0-34.15,27.69-61.84,61.84-61.84h164.94c7.37,0,14.57,2.24,20.63,6.43l52.03,38.35c15.42,11.37,34.08,17.5,53.24,17.5h371.38c30.52,0,55.26,24.74,55.26,55.26v480.47c0,32.58-26.42,59-59,59Z" fill="url(#' + backGradId + ')" opacity="0.4" />' +
                                '<path class="base-fill" d="M200.95,241.68h660.72c34.13,0,61.84,27.71,61.84,61.84v424.77c0,32.56-26.44,59-59,59H210.18c-36.43,0-66-29.57-66-66v-422.84c0-31.33,25.44-56.77,56.77-56.77Z" />' +
                                '<path class="gradOverlay" d="M200.95,241.68h660.72c34.13,0,61.84,27.71,61.84,61.84v424.77c0,32.56-26.44,59-59,59H210.18c-36.43,0-66-29.57-66-66v-422.84c0-31.33,25.44-56.77,56.77-56.77Z" fill="url(#' + frontGradId + ')" opacity="1" />' +
                                '<g opacity="0.3">' +
                                    '<rect x="136" y="724.45" width="800" height="21.8" fill="url(#' + barGradId + ')" />' +
                                    '<rect x="136" y="698.69" width="800" height="21.8" fill="url(#' + barGradId + ')" />' +
                                '</g>' +
                            '</g>' +
                        '</svg>' +
                        '<div class="gallery-item-name" title="' + child.name + '">' + child.name + '</div>' +
                    '</div>'
                );
                imageGrid.append(card);
            });

            // Depois, adiciona as mídias da pasta atual (sem sobrescrever as pastas)
            galleryPaginationState.page = 1;
            galleryPaginationState.folderId = parseInt(folderId || 0, 10) || 0;
            galleryPaginationState.folderName = folderName || '';
            loadImages(folderId, folderName, null, { append: true, keepFolders: true, page: 1 });

            updateActionsBar();
        }

        function openFolder(folderId, folderName) {
            var id = parseInt(folderId || 0, 10) || 0;
            var link = sidebarList.find('a[data-folder-id="' + id + '"]');
            var name = folderName || (link.length ? link.text() : '');

            galleryPaginationState.page = 1;
            galleryPaginationState.totalPages = 1;
            galleryPaginationState.folderId = id;
            galleryPaginationState.folderName = name;
            paginationWrapper.hide().empty();

            sidebarList.find('a').removeClass('active');
            if (link.length) {
                link.addClass('active');
            }

            renderBreadcrumb(id);

            if (newFolderParentSelect && newFolderParentSelect.length) {
                if (id > 0 && newFolderParentSelect.find('option[value="' + id + '"]').length) {
                    newFolderParentSelect.val(String(id));
                } else {
                    newFolderParentSelect.val('0');
                }
            }

            lastSelectedItem = null;

            var children = uptFoldersByParent[id] || [];

            // "Midias sem pasta" nunca mostra pastas, só mídias soltas
            if (id === 0) {
                loadImages(id, name, null, { page: 1 });
            } else if (children.length) {
                renderChildFolders(id, name);
            } else {
                loadImages(id, name, null, { page: 1 });
            }

            if ($(window).width() <= 768) {
                sidebar.removeClass('is-visible');
                galleryOverlay.hide();
            }
        }

        $('body').on('click', '#upt-gallery-pagination.upt-pagination-infinite .upt-load-more', function (e) {
            e.preventDefault();
            if (!galleryPaginationState.enabled) return;
            if (galleryPaginationState.page >= galleryPaginationState.totalPages) return;
            if (galleryPaginationState.isLoading) return;
            try { $(this).prop('disabled', true); } catch (e1) {}
            var nextPage = galleryPaginationState.page + 1;
            var keepFolders = imageGrid.find('.upt-folder-card').length > 0;
            requestGalleryPage(nextPage, { append: true, keepFolders: keepFolders });
        });

        $('body').on('click', '#upt-gallery-pagination.upt-pagination-wrapper .page-numbers[data-page]', function (e) {
            e.preventDefault();
            var p = parseInt($(this).attr('data-page') || 0, 10) || 1;
            var keepFolders = imageGrid.find('.upt-folder-card').length > 0;
            requestGalleryPage(p, { keepFolders: keepFolders });
        });

        function bindInfiniteScrollIfNeeded() {
            var $scrollContainer = imageGrid && imageGrid.length ? imageGrid : $('.gallery-main-content');
            if (!$scrollContainer || !$scrollContainer.length) {
                return;
            }

            $scrollContainer.off('scroll.uptGalleryInfinite');

            if (!galleryPaginationState.enabled) return;
            if (galleryPaginationState.type !== 'infinite') return;
            var trigger = String(galleryPaginationState.infiniteTrigger || 'scroll');
            if (trigger !== 'scroll' && trigger !== 'both') return;

            $scrollContainer.on('scroll.uptGalleryInfinite', function () {
                if (galleryPaginationState.isLoading) return;
                if (galleryPaginationState.page >= galleryPaginationState.totalPages) return;

                var el = this;
                var remaining = (el.scrollHeight - el.scrollTop - el.clientHeight);
                if (remaining < 240) {
                    var keepFolders = imageGrid.find('.upt-folder-card').length > 0;
                    requestGalleryPage(galleryPaginationState.page + 1, { append: true, keepFolders: keepFolders });
                }
            });
        }

        bindInfiniteScrollIfNeeded();
        
        function updateActionsBar() {
            var hasFolders = imageGrid.find('.upt-folder-card').length > 0;
            var mediaItems = imageGrid.find('.gallery-image-item, .gallery-video-item, .gallery-pdf-item');
            var hasMedia = mediaItems.length > 0;

            // Se só há pastas e nenhuma mídia, esconde ações.
            if (hasFolders && !hasMedia) {
                actionsBar.hide();
                uploadContainer.show();
                moveToWrapper.hide();
                removeFromFolderBtn.hide();
                if (useImageBtn.find('span').length) {
                    useImageBtn.find('span').text('Usar');
                }
                return;
            }
            var activeFolderId = sidebarList.find('a.active').data('folder-id');
            var useBtnSpan = useImageBtn.find('span');
            // Conta apenas as mídias visíveis marcadas como selecionadas
            var visibleSelectedCount = mediaItems.filter('.selected').length;
            var hasSelection = visibleSelectedCount > 0;

            if (hasSelection) {
                actionsBar.show();
                uploadContainer.show();
                if (useBtnSpan.length) {
                    useBtnSpan.text('Usar (' + globalSelectedMediaIds.length + ')');
                }

                if (typeof activeFolderId !== 'undefined' && activeFolderId != null) {
                    if (activeFolderId == 0) {
                        moveToWrapper.show();
                        removeFromFolderBtn.hide();
                    } else {
                        moveToWrapper.hide();
                        removeFromFolderBtn.show();
                    }
                }

            } else {
                actionsBar.hide();
                uploadContainer.show();
                moveToWrapper.hide();
                removeFromFolderBtn.hide();
                if (useBtnSpan.length) {
                    useBtnSpan.text('Usar');
                }
            }
        }

        imageGrid.on('click keypress', '.upt-folder-card', function(e) {
            if (e.type === 'keypress' && e.key !== 'Enter' && e.key !== ' ') { return; }
            e.preventDefault();
            var card = $(this);
            var id = card.data('folder-id');
            var name = card.data('folder-name');
            openFolder(id, name);
        });

        imageGrid.on('click', '.gallery-image-item, .gallery-video-item, .gallery-pdf-item', function(e) {
            var clickedItem = $(this);
            var isCtrl = e.ctrlKey || e.metaKey;

            if (isMultiSelectMode || isCtrl) {
                // Modo galeria ou uso com Ctrl/Command: permite múltipla seleção
                clickedItem.toggleClass('selected');
                lastSelectedItem = clickedItem.hasClass('selected') ? clickedItem : null;
            } else {
                // Mídia única: permite desmarcar clicando novamente no mesmo item
                if (clickedItem.hasClass('selected')) {
                    imageGrid.find('.gallery-image-item, .gallery-video-item, .gallery-pdf-item').removeClass('selected');
                    lastSelectedItem = null;
                } else {
                    imageGrid.find('.gallery-image-item, .gallery-video-item, .gallery-pdf-item').removeClass('selected');
                    clickedItem.addClass('selected');
                    lastSelectedItem = clickedItem;
                }
            }
            // Atualiza seleção global considerando apenas a pasta atual
            updateGlobalSelectionForCurrentFolder();
            updateActionsBar();
        });


        // Duplo clique: visualizar mídia em popup (tamanho real, object-fit: contain)
        imageGrid.on('dblclick', '.gallery-image-item, .gallery-video-item, .gallery-pdf-item', function(e) {
            e.preventDefault();
            var $item   = $(this);
            var fullUrl = $item.data('full-url');
            var type    = $item.data('type') || ($item.hasClass('gallery-video-item') ? 'video' : ($item.hasClass('gallery-pdf-item') ? 'pdf' : 'image'));

            if (!fullUrl) {
                return;
            }

            $('.upt-media-lightbox').remove();

            var title    = $item.data('filename') || $item.data('file-name') || $item.attr('title') || '';
            var sizeText = $item.data('file-size-human') || '';
            var downloadUrl = $item.data('download-url') || fullUrl || '';

            var contentHtml = '';
            if (type === 'video') {
                contentHtml = '<video controls autoplay playsinline>' +
                                  '<source src="' + fullUrl + '" type="video/mp4">' +
                              '</video>';
            } else if (type === 'pdf') {
                contentHtml = '<iframe src="' + fullUrl + '" style="width:100%; height:80vh; border:0;" title="PDF"></iframe>';
            } else {
                contentHtml = '<img src="' + fullUrl + '" alt="">';
            }

            var metaHtml = '';
            if (title || sizeText || downloadUrl) {
                metaHtml = '<div class="upt-media-lightbox-meta">';
                if (title) {
                    metaHtml += '<span class="upt-media-badge upt-media-badge--title" title="' + title + '">' + title + '</span>';
                }
                if (sizeText) {
                    metaHtml += '<span class="upt-media-badge upt-media-badge--size">' + sizeText + '</span>';
                }
                if (downloadUrl) {
                    metaHtml += '<a class="upt-media-badge upt-media-badge--download" href="' + downloadUrl + '" target="_blank" rel="noopener noreferrer" download>Baixar mídia</a>';
                }
                metaHtml += '</div>';
            }

            var lightboxHtml =
                '<div class="upt-media-lightbox">' +
                    '<div class="upt-media-lightbox-backdrop"></div>' +
                    '<div class="upt-media-lightbox-inner">' +
                        '<button class="upt-media-lightbox-close" type="button" aria-label="Fechar">&times;</button>' +
                        '<div class="upt-media-lightbox-content">' +
                            '<div class="upt-media-lightbox-preview">' + contentHtml + '</div>' +
                            metaHtml +
                        '</div>' +
                    '</div>' +
                '</div>';

            $('body').append(lightboxHtml);

            var $lightbox = $('.upt-media-lightbox');

            function closeLightbox() {
                $lightbox.remove();
                $(document).off('keyup.uptLightbox');
            }

            $lightbox.on('click', '.upt-media-lightbox-backdrop, .upt-media-lightbox-close', function() {
                closeLightbox();
            });

            $(document).on('keyup.uptLightbox', function(ev) {
                if (ev.key === 'Escape') {
                    closeLightbox();
                }
            });
        });

        
        useImageBtn.on('click', function() {
            if (!globalSelectedMediaIds.length) {
                return;
            }

            var mediaData = [];

            // Usa o mapa global (contém informações das mídias já carregadas em qualquer pasta)
            globalSelectedMediaIds.forEach(function(id) {
                var key = String(id);
                var data = globalSelectedMediaMap[key];
                if (data) {
                    mediaData.push({
                        id: data.id,
                        thumbnail_url: data.thumbnail_url,
                        full_url: data.full_url,
                        type: data.type || ''
                    });
                }
            });

            // Fallback: se nada foi preenchido (caso improvável), usa seleção visível atual
            if (!mediaData.length) {
                var selectedItems = imageGrid.find('.gallery-image-item.selected, .gallery-video-item.selected, .gallery-pdf-item.selected');
                selectedItems.each(function() {
                    var item = $(this);
                    mediaData.push({
                        id: item.data('id'),
                        thumbnail_url: item.data('thumbnail-url'),
                        full_url: item.data('full-url'),
                        type: item.data('type') || ''
                    });
                });
            }

            if (mediaData.length) {
                window.parent.postMessage({ uptGallerySelection: mediaData }, '*');
            }
        });

        // Exportar mídia: comportamento baseado na seleção atual
        $('#upt-export-media-button').on('click', function(e) {
            e.preventDefault();

            var baseUrl = $(this).attr('href') || '';
            if (!baseUrl) {
                return;
            }

            var ids = [];

            if (isMultiSelectMode) {
                // Modo galeria: usa lista global (todas as pastas)
                ids = (globalSelectedMediaIds || []).slice();
            } else {
                // Modo de mídia única: respeita múltiplas seleções feitas com Ctrl/Command
                imageGrid.find('.gallery-image-item.selected, .gallery-video-item.selected, .gallery-pdf-item.selected').each(function() {
                    var id = $(this).data('id');
                    if (id !== undefined && id !== null) {
                        ids.push(String(id));
                    }
                });
            }

            // Sem seleção: comportamento padrão, exporta tudo (ZIP com pastas)
            if (!ids.length) {
                window.location.href = baseUrl;
                return;
            }

            // Uma mídia: baixa arquivo individual (sem ZIP)
            if (ids.length === 1) {
                var singleUrl = baseUrl + (baseUrl.indexOf('?') === -1 ? '?' : '&') +
                    'single_media_id=' + encodeURIComponent(ids[0]);
                window.location.href = singleUrl;
                return;
            }

            // Múltiplas mídias: exporta apenas selecionadas em ZIP
            var multiUrl = baseUrl + (baseUrl.indexOf('?') === -1 ? '?' : '&') +
                'media_ids=' + encodeURIComponent(ids.join(','));
            window.location.href = multiUrl;
        });

        sidebarList.on('click', 'a', function(e) {
            e.preventDefault();
            var folderLink = $(this);
            openFolder(folderLink.data('folder-id'), folderLink.text());
        });

        $('#upt-breadcrumb').on('click', '.upt-breadcrumb__link', function() {
            var targetId = parseInt($(this).data('folder-id') || 0, 10) || 0;
            var node = uptFolderMap[targetId];
            var targetName = node && node.name ? node.name : '';
            openFolder(targetId, targetName);
        });

        function uptIsDescendantFolder(childId, possibleAncestorId) {
            var cursor = parseInt(childId || 0, 10) || 0;
            var guard = 0;
            while (cursor && guard < 200) {
                if (cursor === possibleAncestorId) return true;
                cursor = parseInt(uptFolderParentMap[cursor] || 0, 10) || 0;
                guard++;
            }
            return false;
        }

        // Drag a folder (li.upt-folder-item) and drop on another folder link to change its parent
        sidebarList.on('dragstart', 'li.upt-folder-item', function(e) {
            var termId = parseInt($(this).data('term-id') || 0, 10) || 0;
            if (!termId) return;
            uptDraggedFolderId = termId;
            try {
                if (e.originalEvent && e.originalEvent.dataTransfer) {
                    e.originalEvent.dataTransfer.setData('text/plain', String(termId));
                    e.originalEvent.dataTransfer.effectAllowed = 'move';
                }
            } catch (err) {}
        });

        sidebarList.on('dragend', 'li.upt-folder-item', function() {
            uptDraggedFolderId = null;
            sidebarList.find('a.upt-folder-drop-target').removeClass('upt-folder-drop-target');
        });

        sidebarList.on('dragover', 'a[data-folder-id]', function(e) {
            if (!uptDraggedFolderId) return;
            e.preventDefault();
            try {
                if (e.originalEvent && e.originalEvent.dataTransfer) {
                    e.originalEvent.dataTransfer.dropEffect = 'move';
                }
            } catch (err) {}
            $(this).addClass('upt-folder-drop-target');
        });

        sidebarList.on('dragleave', 'a[data-folder-id]', function() {
            $(this).removeClass('upt-folder-drop-target');
        });

        sidebarList.on('drop', 'a[data-folder-id]', function(e) {
            if (!uptDraggedFolderId) return;
            e.preventDefault();

            var targetFolderId = parseInt($(this).data('folder-id') || 0, 10) || 0;
            var draggedId = parseInt(uptDraggedFolderId || 0, 10) || 0;

            sidebarList.find('a.upt-folder-drop-target').removeClass('upt-folder-drop-target');

            if (!draggedId) return;

            // allow dropping on "Midias sem pasta" to move to root
            var newParentId = targetFolderId;

            if (newParentId === draggedId) {
                fcShowBadge('Não é possível mover uma pasta para dentro dela mesma.');
                return;
            }

            // Already under this parent
            var currentParentId = parseInt(uptFolderParentMap[draggedId] || 0, 10) || 0;
            if (currentParentId === newParentId) {
                fcShowBadge('A pasta selecionada já está dentro dessa pasta.');
                return;
            }

            // Prevent moving folder into its own subtree
            if (newParentId && uptIsDescendantFolder(newParentId, draggedId)) {
                fcShowBadge('A pasta selecionada já está dentro dessa pasta.');
                return;
            }

            $.ajax({
                url: upt_gallery.ajax_url,
                type: 'POST',
                data: {
                    action: 'upt_move_media_folder',
                    nonce: upt_gallery.nonce,
                    term_id: draggedId,
                    new_parent_id: newParentId
                },
                success: function(resp) {
                    if (resp && resp.success) {
                        fcShowBadge('Pasta movida com sucesso.');
                        // Reload folders keeping current selection
                        var activeId = parseInt(sidebarList.find('a.active').data('folder-id') || 0, 10) || 0;
                        loadFolders(function() {
                            var selector = 'a[data-folder-id="' + activeId + '"]';
                            if (sidebarList.find(selector).length) {
                                sidebarList.find('a').removeClass('active');
                                sidebarList.find(selector).addClass('active');
                            }
                        });
                    } else {
                        var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Erro ao mover a pasta.';
                        fcShowBadge(msg);
                    }
                },
                error: function() {
                    fcShowBadge('Erro de comunicação ao mover a pasta.');
                }
            });
        });

        sidebarList.on('click', '.delete-folder-button', function(e) {
            e.preventDefault();
            e.stopPropagation();

            closeAllFolderDropdowns();

            var button = $(this);
            var termId = button.data('term-id');
            var termName = button.data('term-name');

            var deleteMediaCheckbox = $('<input type="checkbox" />').prop('checked', false);
            var deleteMediaLabel = $('<label class="upt-delete-media-toggle"></label>').css({
                display: 'flex', alignItems: 'center', gap: '8px', fontSize: '14px', lineHeight: '1.4', color: '#222'
            });
            deleteMediaLabel.append(deleteMediaCheckbox, $('<span>Apagar mídias desta pasta</span>'));

            var deleteMediaHint = $('<p class="upt-delete-media-hint"></p>').text('Se marcado, a pasta e todas as mídias serão apagadas. Se desmarcado, as mídias ficarão em "Sem pasta".').css({
                margin: '0 0 0 24px', fontSize: '13px', color: '#444'
            });

            var confirmExtra = $('<div></div>').css({ display: 'flex', flexDirection: 'column', gap: '6px' })
                .append(deleteMediaLabel, deleteMediaHint);

            fcConfirm('Tem certeza que deseja excluir a pasta "' + termName + '"?', function(confirmData) {

                var deleteMedia = confirmData && confirmData.deleteMedia;

                button.prop('disabled', true);

                $.ajax({
                    url: upt_gallery.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'upt_delete_media_folder',
                        nonce: upt_gallery.nonce,
                        term_id: termId,
                        delete_media: deleteMedia ? 1 : 0
                    },
                    success: function(response) {
                        if (response.success) {
                            var deletedMedia = response.data && typeof response.data.deleted_media !== 'undefined' ? parseInt(response.data.deleted_media, 10) : 0;
                            var deletedFolderMsg = 'Pasta apagada.';
                            if (response.data && parseInt(response.data.delete_media, 10) === 1) {
                                if (!isNaN(deletedMedia) && deletedMedia > 0) {
                                    deletedFolderMsg = 'Pasta e ' + deletedMedia + ' mídias apagadas.';
                                } else {
                                    deletedFolderMsg = 'Pasta apagada junto com as mídias.';
                                }
                            }

                            var activeFolderLink = sidebarList.find('a.active');
                            if (activeFolderLink.data('folder-id') == termId) {
                                sidebarList.find('a[data-folder-id="0"]').trigger('click');
                            }
                            loadFolders();
                            fcShowBadge(deletedFolderMsg);
                        } else {
                            alert('Erro ao excluir a pasta: ' + (response.data && response.data.message ? response.data.message : 'Erro desconhecido.'));
                            button.prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Erro de comunicação.');
                        button.prop('disabled', false);
                    }
                });
            }, {
                extraContent: confirmExtra,
                onBeforeConfirm: function() {
                    return { deleteMedia: deleteMediaCheckbox.is(':checked') };
                }
            });
        });

        sidebarList.on('click', '.rename-folder-button', function(e) {
            e.preventDefault();
            e.stopPropagation();

            closeAllFolderDropdowns();

            var button = $(this);
            var termId = button.data('term-id');
            var currentName = button.data('term-name') || '';
            var newName = prompt('Digite o novo nome da pasta:', currentName);

            if (!newName || newName === currentName) {
                return;
            }

            button.prop('disabled', true);

            $.ajax({
                url: upt_gallery.ajax_url,
                type: 'POST',
                data: {
                    action: 'upt_rename_media_folder',
                    nonce: upt_gallery.nonce,
                    term_id: termId,
                    name: newName
                },
                success: function(response) {
                    if (response && response.success && response.data && response.data.name) {
                        var updatedName = response.data.name;
                        var li = button.closest('li');

                        // Atualiza somente o texto do label, mantendo ícone/estrutura
                        li.find('.upt-folder-list__label').text(updatedName);

                        // Atualiza mapa para breadcrumb
                        if (uptFolderMap[termId]) {
                            uptFolderMap[termId].name = updatedName;
                        }

                        // Sincroniza datasets usados pelos botões
                        li.find('.rename-folder-button').data('term-name', updatedName).attr('data-term-name', updatedName);
                        li.find('.folder-options-button').data('term-name', updatedName).attr('data-term-name', updatedName);

                        // Atualiza o select "mover para pasta" preservando indentação, se existir
                        var moveOption = moveToSelect.find('option[value="' + termId + '"]');
                        var existingText = moveOption.text() || '';
                        var idx = existingText.lastIndexOf(currentName);
                        var prefix = idx >= 0 ? existingText.slice(0, idx) : '';
                        moveOption.text(prefix + updatedName);

                        button.prop('disabled', false);

                        renderBreadcrumb(termId);
                    } else {
                        alert('Erro ao renomear a pasta: ' + (response && response.data && response.data.message ? response.data.message : 'Erro desconhecido.'));
                        button.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Erro de comunicação.');
                    button.prop('disabled', false);
                }
            });
        });

        bulkDeleteFoldersBtn.on('click', function() {
            var folders = getBulkFolderList();

            if (!folders.length) {
                fcShowBadge('Não há pastas para apagar.');
                return;
            }

            var listEl = $('<div class="upt-bulk-folder-list"></div>');
            var folderDepthMap = {};

            folders.forEach(function(folder) {
                folderDepthMap[folder.id] = folder.depth;
                var item = $('<label class="upt-bulk-folder-item"></label>');
                var checkbox = $('<input type="checkbox" class="upt-bulk-folder-checkbox" />').val(folder.id);
                var name = $('<span class="upt-bulk-folder-name"></span>').text(folder.name);

                name.css('padding-left', Math.min(folder.depth, 6) * 12 + 'px');

                item.append(checkbox, name);
                listEl.append(item);
            });

            var deleteMediaCheckbox = $('<input type="checkbox" class="upt-delete-media-checkbox" />');
            var deleteMediaToggle = $('<label class="upt-delete-media-toggle"></label>').append(
                deleteMediaCheckbox,
                $('<span>Apagar mídias das pastas selecionadas</span>')
            );
            var deleteMediaHint = $('<p class="upt-delete-media-hint"></p>').text('Se não marcado, as mídias permanecem em "Sem pasta".');

            var content = $('<div class="upt-bulk-delete-content"></div>');
            content.append($('<p class="upt-bulk-delete-hint">Selecione uma ou mais pastas. Subpastas também serão removidas.</p>'));
            content.append(listEl);
            content.append(deleteMediaToggle);
            content.append(deleteMediaHint);

            fcConfirm('Apagar pastas selecionadas?', function(confirmData) {
                var selectedFolders = (confirmData && Array.isArray(confirmData.selectedFolders)) ? confirmData.selectedFolders : [];
                var deleteMedia = !!(confirmData && confirmData.deleteMedia);

                if (!selectedFolders.length) {
                    fcShowBadge('Selecione ao menos uma pasta.');
                    return;
                }

                bulkDeleteFoldersBtn.prop('disabled', true);

                var successCount = 0;
                var deletedMediaTotal = 0;
                var errors = [];

                function finishBulkDelete() {
                    bulkDeleteFoldersBtn.prop('disabled', false);

                    loadFolders(function() {
                        sidebarList.find('a').removeClass('active');
                        var rootLink = sidebarList.find('a[data-folder-id="0"]');
                        if (rootLink.length) {
                            rootLink.addClass('active');
                            openFolder(0, 'Midias sem pasta');
                        }
                    });

                    if (successCount > 0) {
                        var msg = successCount + ' pasta(s) apagadas';
                        if (deleteMedia && deletedMediaTotal > 0) {
                            msg += ' junto com ' + deletedMediaTotal + ' mídia(s).';
                        } else {
                            msg += '.';
                        }
                        fcShowBadge(msg);
                    }

                    if (errors.length) {
                        alert(errors.join('\n'));
                    }
                }

                function deleteNextFolder() {
                    if (!selectedFolders.length) {
                        finishBulkDelete();
                        return;
                    }

                    var id = parseInt(selectedFolders.shift(), 10) || 0;

                    $.ajax({
                        url: upt_gallery.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'upt_delete_media_folder',
                            nonce: upt_gallery.nonce,
                            term_id: id,
                            delete_media: deleteMedia ? 1 : 0
                        },
                        success: function(response) {
                            if (response && response.success) {
                                successCount++;
                                var deletedMedia = response.data && typeof response.data.deleted_media !== 'undefined' ? parseInt(response.data.deleted_media, 10) : 0;
                                if (!isNaN(deletedMedia)) {
                                    deletedMediaTotal += deletedMedia;
                                }
                            } else {
                                var errMsg = (response && response.data && response.data.message) ? response.data.message : 'Erro ao excluir a pasta ' + id + '.';
                                errors.push(errMsg);
                            }
                            deleteNextFolder();
                        },
                        error: function() {
                            errors.push('Erro de comunicação ao excluir a pasta ' + id + '.');
                            deleteNextFolder();
                        }
                    });
                }

                deleteNextFolder();
            }, {
                extraContent: content,
                onBeforeConfirm: function() {
                    var selected = [];

                    listEl.find('input.upt-bulk-folder-checkbox:checked').each(function() {
                        var v = parseInt($(this).val(), 10);
                        if (!isNaN(v)) {
                            selected.push(v);
                        }
                    });

                    selected.sort(function(a, b) {
                        var depthA = folderDepthMap[a] || 0;
                        var depthB = folderDepthMap[b] || 0;

                        if (depthA === depthB) {
                            return b - a;
                        }

                        return depthB - depthA;
                    });

                    return {
                        selectedFolders: selected,
                        deleteMedia: deleteMediaCheckbox.is(':checked')
                    };
                }
            });
        });

        createFolderBtn.on('click', function() {
            var button = $(this);
            var folderName = newFolderNameInput.val().trim();
            var parentId = 0;
            if (newFolderParentSelect && newFolderParentSelect.length) {
                parentId = parseInt(newFolderParentSelect.val() || 0, 10) || 0;
            } else {
                parentId = parseInt(sidebarList.find('a.active').data('folder-id') || 0, 10) || 0;
            }
            if (!folderName) {
                createFolderStatus.text('Por favor, insira um nome.').addClass('upt-status-error');
                return;
            }
            button.prop('disabled', true).find('span').text('Criando...');
            createFolderStatus.text('').css('color', '');
            $.ajax({
                url: upt_gallery.ajax_url,
                type: 'POST',
                data: { action: 'upt_create_media_folder', nonce: upt_gallery.nonce, folder_name: folderName, parent_id: parentId },
                success: function(response) {
                    if (response.success) {
                        newFolderNameInput.val('');
                        loadFolders(function() {
                            sidebarList.find('a[data-folder-id="' + response.data.term_id + '"]').trigger('click');
                        });
                    } else {
                        createFolderStatus.text(response.data.message).addClass('upt-status-error');
                    }
                },
                error: function() {
                    createFolderStatus.text('Erro de comunicação.').addClass('upt-status-error');
                },
                complete: function() {
                    button.prop('disabled', false).find('span').text('Criar Pasta');
                }
            });
        });

        $('body').on('change', '#upt-uploader', function() {
            var files = this.files;
            var uploaderInput = $(this);

            if (!files.length) return;

            var activeFolderId = sidebarList.find('a.active').data('folder-id');
            var activeFolderName = sidebarList.find('a.active').text();

            // Usa o próprio botão "Adicionar Midia" como barra de progresso
            uploadProgress.hide();

            var progressText = null;
            var progressBarFill = null;

            // Estado global de upload
            uploadCancelled = false;
            isUploading = true;
            activeUploadRequests = [];
            currentUploaderInput = uploaderInput;

            if (uploadButton.length && uploadButtonText.length) {
                if (!uploadButton.data('original-text')) {
                    uploadButton.data('original-text', uploadButtonText.text());
                }
                progressText = uploadButtonText;
                progressBarFill = uploadButton;
                uploadButton.addClass('upt-uploading');

                if (uploadButtonSubtext && uploadButtonSubtext.length) {
                    uploadButtonSubtext.show();
                }

                progressText.text('0%');
                progressBarFill.css('--fc-upload-progress', '0%');
            } else {
                // Fallback: usa a barra antiga abaixo do botão, se o botão não estiver disponível
                uploadProgress.show();
                progressText = uploadProgress.find('.upt-progress-text');
                progressBarFill = uploadProgress.find('.upt-progress-bar-fill');
                if (progressText.length) {
                    progressText.text('0%');
                }
                if (progressBarFill.length) {
                    progressBarFill.css('width', '0%');
                }
            }

            var simulatedPercent = 0;
            if (uploadProgressTimer) {
                clearInterval(uploadProgressTimer);
            }
            uploadProgressTimer = setInterval(function() {
                if (uploadCancelled || !isUploading) return;
                if (!(progressText && progressText.length)) return;
                if (simulatedPercent >= 95) return;
                simulatedPercent += 3;
                if (simulatedPercent > 95) {
                    simulatedPercent = 95;
                }
                progressText.text(simulatedPercent + '%');
                if (progressBarFill && progressBarFill.length) {
                    if (progressBarFill.is(uploadButton)) {
                        progressBarFill.css('--fc-upload-progress', simulatedPercent + '%');
                    } else {
                        progressBarFill.css('width', simulatedPercent + '%');
                    }
                }
            }, 400);

            var uploadedIds = [];
            var filesToUpload = files.length;
            var filesProcessed = 0;
            var uploadErrors = false;

            Array.from(files).forEach(function(file) {
                var formData = new FormData();
                formData.append('_wpnonce', upt_gallery.upload_nonce);
                formData.append('action', 'upload-attachment');
                formData.append('async-upload', file, file.name);

                var jqXHR = $.ajax({
                    url: upt_gallery.upload_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data && response.data.id) {
                            uploadedIds.push(response.data.id);
                        } else {
                            uploadErrors = true;
                        }
                    },
                    error: function() { 
                        // Ignora erros se o upload foi cancelado
                        if (!uploadCancelled) {
                            uploadErrors = true;
                        }
                    },
                    complete: function() {
                        if (uploadCancelled) {
                            return;
                        }

                        filesProcessed++;
                        var percent = Math.round((filesProcessed / filesToUpload) * 100);
                        if (progressText && progressText.length) {
                            progressText.text(percent + '%');
                        }
                        if (progressBarFill && progressBarFill.length) {
                            if (progressBarFill.is(uploadButton)) {
                                progressBarFill.css('--fc-upload-progress', percent + '%');
                            } else {
                                progressBarFill.css('width', percent + '%');
                            }
                        }
                        if (filesProcessed === filesToUpload) {
                            isUploading = false;
                            if (!uploadErrors && !uploadCancelled) { fcNotify('media_uploaded', 'Mídia enviada (' + filesToUpload + ')'); }
                            if (uploadProgressTimer) {
                                clearInterval(uploadProgressTimer);
                                uploadProgressTimer = null;
                            }
                            if (progressText && progressText.length) {
                                progressText.text('100%');
                            }
                            if (progressBarFill && progressBarFill.length) {
                                if (progressBarFill.is(uploadButton)) {
                                    progressBarFill.css('--fc-upload-progress', '100%');
                                } else {
                                    progressBarFill.css('width', '100%');
                                }
                            }
                            var highlightId = (uploadedIds.length === 1 && filesToUpload === 1) ? uploadedIds[0] : null;
                            var targetFolderId = activeFolderId !== 0 ? activeFolderId : null;

                            var reloadFolder = function() {
                                // Reabre a pasta para manter cards + mídias atualizados
                                openFolder(activeFolderId, activeFolderName);
                                if (highlightId) {
                                    // Aplica destaque após recarregar
                                    setTimeout(function(){
                                        imageGrid.find('.gallery-image-item[data-id="' + highlightId + '"], .gallery-video-item[data-id="' + highlightId + '"]').addClass('selected');
                                        updateActionsBar();
                                    }, 120);
                                }
                            };

                            if (uploadedIds.length > 0 && targetFolderId) {
                                $.ajax({
                                    url: upt_gallery.ajax_url,
                                    type: 'POST',
                                    data: {
                                        action: 'upt_assign_to_folder',
                                        nonce: upt_gallery.nonce,
                                        attachment_ids: uploadedIds,
                                        folder_id: targetFolderId
                                    },
                                    complete: reloadFolder
                                });
                            } else {
                                reloadFolder();
                            }
                            uploadProgress.hide();
                            uploaderInput.val('');
                            if (uploadButton.length && uploadButtonText.length) {
                                var originalText = uploadButton.data('original-text') || 'Adicionar Midia';
                                uploadButtonText.text(originalText);
                                uploadButton.removeClass('upt-uploading');
                                uploadButton.css('--fc-upload-progress', '0%');
                                if (uploadButtonSubtext && uploadButtonSubtext.length) {
                                    uploadButtonSubtext.hide();
                                }
                            }
                            if (uploadErrors) {
                                alert('Algumas mídias não puderam ser enviadas.');
                            }
                        }
                    }
                });

                activeUploadRequests.push(jqXHR);
            });
        });

// Clique no botão de upload durante o envio cancela o upload atual
        uploadButton.on('click', function(e) {
            if (uploadButton.hasClass('upt-uploading')) {
                e.preventDefault();
                e.stopPropagation();
                cancelCurrentUpload();
            }
        });


        function cancelCurrentUpload() {
            if (!isUploading) return;

            uploadCancelled = true;
            isUploading = false;

            if (uploadProgressTimer) {
                clearInterval(uploadProgressTimer);
                uploadProgressTimer = null;
            }

            if (activeUploadRequests && activeUploadRequests.length) {
                activeUploadRequests.forEach(function(req) {
                    if (req && req.abort) {
                        try { req.abort(); } catch (e) {}
                    }
                });
            }
            activeUploadRequests = [];

            if (currentUploaderInput) {
                currentUploaderInput.val('');
                currentUploaderInput = null;
            }

            uploadProgress.hide();

            if (uploadButton.length && uploadButtonText.length) {
                var originalText = uploadButton.data('original-text') || 'Adicionar Midia';
                uploadButtonText.text(originalText);
                uploadButton.removeClass('upt-uploading');
                uploadButton.css('--fc-upload-progress', '0%');
            }
            if (uploadButtonSubtext && uploadButtonSubtext.length) {
                uploadButtonSubtext.hide();
            }
        }

        moveToSelect.on('change', function() {
            var folderId = $(this).val();
            var selectedItems = imageGrid.find('.gallery-image-item.selected, .gallery-video-item.selected, .gallery-pdf-item.selected');
            var imageIds = selectedItems.map(function() { return $(this).data('id'); }).get();
            var select = $(this);
    
            if (!folderId || imageIds.length === 0) return;
    
            selectedItems.css('opacity', '0.5');
    
            $.ajax({
                url: upt_gallery.ajax_url,
                type: 'POST',
                data: {
                    action: 'upt_assign_to_folder',
                    nonce: upt_gallery.nonce,
                    attachment_ids: imageIds,
                    folder_id: folderId
                },
                success: function() {
                    fcNotify('media_moved', 'Mídia movida (' + imageIds.length + ')');
                    loadImages(0, 'Midias sem pasta');
                },
                error: function() {
                    alert('Ocorreu um erro ao mover as mídias.');
                    selectedItems.css('opacity', '1');
                },
                complete: function() {
                    select.val('');
                }
            });
        });

        removeFromFolderBtn.on('click', function(e) {
            e.preventDefault();
            var selectedItems = imageGrid.find('.gallery-image-item.selected, .gallery-video-item.selected, .gallery-pdf-item.selected');
            var imageIds = selectedItems.map(function() { return $(this).data('id'); }).get();
            var activeFolderId = sidebarList.find('a.active').data('folder-id');

            if (imageIds.length > 0 && activeFolderId !== 0) {
                selectedItems.css('opacity', '0.5');
                $.ajax({
                    url: upt_gallery.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'upt_remove_from_folder',
                        nonce: upt_gallery.nonce,
                        attachment_ids: imageIds,
                        folder_id: activeFolderId
                    },
                    success: function() {
                        fcNotify('media_moved', 'Mídia movida (' + imageIds.length + ')');
                        loadImages(activeFolderId, sidebarList.find('a.active').text());
                    },
                    error: function() {
                        alert('Ocorreu um erro ao remover as mídias da pasta.');
                        selectedItems.css('opacity', '1');
                    }
                });
            }
        });

        deleteBtn.on('click', function() {
            var selectedItems = imageGrid.find('.gallery-image-item.selected, .gallery-video-item.selected, .gallery-pdf-item.selected');
            var imageIds = selectedItems.map(function() { return $(this).data('id'); }).get();

            if (!imageIds.length) { return; }

            var msg = 'Tem certeza que deseja excluir ' + imageIds.length + ' mídia(s) permanentemente?';
            fcConfirm(msg, function() {
                var activeFolderId = sidebarList.find('a.active').data('folder-id');
                var activeFolderName = sidebarList.find('a.active').text();
                selectedItems.css('opacity', '0.5');

                $.ajax({
                    url: upt_gallery.ajax_url,
                    type: 'POST',
                    data: { action: 'upt_gallery_delete_image', nonce: upt_gallery.nonce, image_id: imageIds },
                    success: function(response) {
                        if (response.success) {
                            fcNotify('media_deleted', 'Mídia apagada (' + imageIds.length + ')');
                            // Reabre a pasta para exibir novamente suas subpastas (se houver)
                            if (uptFoldersByParent[activeFolderId] && uptFoldersByParent[activeFolderId].length) {
                                openFolder(activeFolderId, activeFolderName);
                            } else {
                                loadImages(activeFolderId, activeFolderName);
                            }
                        } else {
                            alert('Erro ao excluir as mídias: ' + (response.data ? response.data.message : 'Erro desconhecido.'));
                            selectedItems.css('opacity', '1');
                        }
                    },
                    error: function() {
                        alert('Erro ao excluir as mídias.');
                        selectedItems.css('opacity', '1');
                    }
                });
            });
        });

        selectAllBtn.on('click', function(e) {
            e.preventDefault();
            imageGrid.find('.gallery-image-item, .gallery-video-item, .gallery-pdf-item').addClass('selected');
            lastSelectedItem = imageGrid.find('.gallery-image-item, .gallery-video-item, .gallery-pdf-item').last();
            updateGlobalSelectionForCurrentFolder();
            updateActionsBar();
        });

        deselectAllBtn.on('click', function(e) {
            e.preventDefault();
            imageGrid.find('.gallery-image-item, .gallery-video-item, .gallery-pdf-item').removeClass('selected');
            lastSelectedItem = null;
            updateGlobalSelectionForCurrentFolder();
            updateActionsBar();
        });

        // Clear selection button inside actions bar (modal)
        clearSelectionBtn.on('click', function(e){
            e.preventDefault();
            try { deselectAllBtn.trigger('click'); } catch(err) {
                imageGrid.find('.gallery-image-item, .gallery-video-item, .gallery-pdf-item').removeClass('selected');
                lastSelectedItem = null;
                updateGlobalSelectionForCurrentFolder();
                updateActionsBar();
            }
        });
        
        toggleSidebarBtn.on('click', function(e) {
            e.preventDefault();
            sidebar.addClass('is-visible');
        });

        function closeSidebar() {
            sidebar.removeClass('is-visible');
        }

        closeSidebarBtn.on('click', closeSidebar);
        galleryOverlay.on('click', closeSidebar);

        mainCloseBtn.on('click', function(e){
            e.preventDefault();
            window.parent.postMessage({ uptGalleryClose: true }, '*');
        });

        loadFolders();
        loadImages(0, 'Midias sem pasta');
        renderBreadcrumb(0);

        $(window).on('resize', updateActionsBar);
    });

})(jQuery);
