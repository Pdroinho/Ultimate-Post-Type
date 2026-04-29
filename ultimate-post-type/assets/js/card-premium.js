jQuery(document).ready(function ($) {

    $(document).on('click', '.upt-premium-card__menu-btn', function (e) {
        e.stopPropagation();
        var $btn = $(this);
        var itemId = $btn.data('item-id');
        var $menu = $btn.closest('.upt-premium-card').find('.upt-premium-card__context-menu[data-menu-for="' + itemId + '"]');
        $('.upt-premium-card__context-menu.is-open').not($menu).removeClass('is-open');
        $menu.toggleClass('is-open');
    });

    $(document).on('click', function () {
        $('.upt-premium-card__context-menu.is-open').removeClass('is-open');
    });

    $(document).on('click', '.upt-premium-card__context-menu', function (e) {
        e.stopPropagation();
    });

    $(document).on('click', '.upt-premium-card__fav-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $btn = $(this);
        var itemId = $btn.data('item-id');
        var $card = $btn.closest('.upt-premium-card');

        $.ajax({
            url: upt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'upt_toggle_favorite',
                nonce: upt_ajax.nonce,
                item_id: itemId
            },
            success: function (response) {
                if (response && response.success) {
                    var isFav = response.data.favorited;
                    $btn.toggleClass('is-active', isFav);
                    $card.toggleClass('is-favorited', isFav);
                    $btn.find('svg').attr('fill', isFav ? 'currentColor' : 'none');
                    $btn.attr('title', isFav ? 'Desfavoritar' : 'Favoritar');
                }
            }
        });
    });

    $(document).on('click', '.upt-duplicate-item', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $item = $(this);
        var itemId = $item.data('item-id');

        $('.upt-premium-card__context-menu.is-open').removeClass('is-open');

        $.ajax({
            url: upt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'upt_duplicate_item',
                nonce: upt_ajax.nonce,
                item_id: itemId
            },
            success: function (response) {
                if (response && response.success) {
                    uptNotify('success', 'Item duplicado com sucesso!');
                    if (typeof handleFiltersChangeDashboard === 'function') {
                        handleFiltersChangeDashboard();
                    }
                } else {
                    var msg = (response && response.data && response.data.message) ? response.data.message : 'Falha ao duplicar.';
                    uptNotify('error', msg);
                }
            },
            error: function () {
                uptNotify('error', 'Erro de rede ao duplicar.');
            }
        });
    });

    $(document).on('click', '.upt-toggle-status', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $item = $(this);
        var itemId = $item.data('item-id');
        var newStatus = $item.data('new-status');

        $('.upt-premium-card__context-menu.is-open').removeClass('is-open');

        $.ajax({
            url: upt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'upt_toggle_item_status',
                nonce: upt_ajax.nonce,
                item_id: itemId,
                new_status: newStatus
            },
            success: function (response) {
                if (response && response.success) {
                    uptNotify('success', response.data.message || 'Status atualizado!');
                    if (typeof handleFiltersChangeDashboard === 'function') {
                        handleFiltersChangeDashboard();
                    }
                } else {
                    var msg = (response && response.data && response.data.message) ? response.data.message : 'Falha ao alterar status.';
                    uptNotify('error', msg);
                }
            },
            error: function () {
                uptNotify('error', 'Erro de rede ao alterar status.');
            }
        });
    });

});
