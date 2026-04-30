(function($) {
    'use strict';

    if (typeof uptDashboard === 'undefined') return;

    $('#tab-4gt-submissions .open-submission-modal .upt-card-btn__label, #tab-4gt-submissions .upt-submission-action--delete .upt-card-btn__label').remove();

    $('body').on('click', '.open-submission-modal', function(e) {
        e.preventDefault();
        var id = $(this).data('submission-id');
        var $details = $('#upt-submission-details-' + id);
        if (!$details.length) return;
        $('#upt-modal-content').html(
            '<a href="#" id="upt-modal-close" class="upt-modal-close-button">&times;</a>' +
            $details.html()
        );
        $('#upt-modal-wrapper').fadeIn(200);
    });

    function uptConfirmDialog(message, onConfirm) {
        var $overlay = $('<div class="upt-confirm-overlay" role="dialog" aria-modal="true"></div>');
        var $panel = $('<div class="upt-confirm-panel"></div>');
        var $msg = $('<p class="upt-confirm-message"></p>').text(message || 'Confirmar ação?');
        var $actions = $('<div class="upt-confirm-actions"></div>');
        var $cancel = $('<button type="button" class="upt-confirm-cancel">Cancelar</button>');
        var $ok = $('<button type="button" class="upt-confirm-ok">Apagar</button>');

        $actions.append($cancel, $ok);
        $panel.append($msg, $actions);
        $overlay.append($panel);
        $('body').append($overlay);

        function close() {
            $(document).off('keydown.uptConfirm');
            $overlay.remove();
        }

        $overlay.on('click', function(ev) {
            if (ev.target === this) close();
        });
        $cancel.on('click', close);
        $ok.on('click', function() {
            close();
            if (typeof onConfirm === 'function') onConfirm();
        });
        $(document).on('keydown.uptConfirm', function(ev) {
            if (ev.key === 'Escape') close();
        });
        $cancel.trigger('focus');
    }

    $('body').on('click', '.upt-submission-action--delete', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        if (!url) return;
        uptConfirmDialog('Excluir este envio?', function() {
            window.location.href = url;
        });
    });

    $('body').on('click', '.upt-open-button-detail', function(e) {
        e.preventDefault();
        var detailId = $(this).data('detail-id');
        if (!detailId) return;
        var $details = $('#' + detailId);
        if (!$details.length) return;
        $('#upt-modal-content').html(
            '<a href="#" id="upt-modal-close" class="upt-modal-close-button">&times;</a>' +
            $details.html()
        );
        $('#upt-modal-wrapper').fadeIn(200);
    });

    var $dashboardCanvas = $('#upt-dashboard-chart');
    var isMobile = window.innerWidth <= 768;
    if (isMobile) $dashboardCanvas.parent().css('height', '320px');

    if (!$dashboardCanvas.length || typeof Chart === 'undefined') return;

    var d = uptDashboard;
    var defaultRange = 30;

    function sliceData(rangeDays) {
        var l = d.labels.slice(), f = d.forms.slice(), b = d.buttons.slice(), im = d.images.slice();
        if (!rangeDays || rangeDays >= l.length) return { labels: l, forms: f, buttons: b, images: im };
        var s = Math.max(0, l.length - rangeDays);
        return { labels: l.slice(s), forms: f.slice(s), buttons: b.slice(s), images: im.slice(s) };
    }

    function sliceCustom(fromStr, toStr) {
        var l = d.labels.slice(), f = d.forms.slice(), b = d.buttons.slice(), im = d.images.slice();
        if (!fromStr && !toStr) return { labels: l, forms: f, buttons: b, images: im };
        var start = fromStr ? new Date(fromStr) : null;
        var end = toStr ? new Date(toStr) : null;
        var ol = [], of = [], ob = [], oi = [];
        for (var i = 0; i < l.length; i++) {
            var cur = new Date(l[i]);
            if ((start === null || cur >= start) && (end === null || cur <= end)) {
                ol.push(l[i]); of.push(f[i]); ob.push(b[i]); oi.push(im[i]);
            }
        }
        if (!ol.length) return { labels: l, forms: f, buttons: b, images: im };
        return { labels: ol, forms: of, buttons: ob, images: oi };
    }

    function buildDatasets(sliced) {
        var ds = [];
        if (d.hasForms) ds.push({ label: 'Formulários', data: sliced.forms, tension: 0.3, borderWidth: 2, pointRadius: 2 });
        if (d.hasButtons) ds.push({ label: 'Cliques em botões', data: sliced.buttons, tension: 0.3, borderWidth: 2, pointRadius: 2 });
        if (d.hasImages) ds.push({ label: 'Cliques em imagens', data: sliced.images, tension: 0.3, borderWidth: 2, pointRadius: 2 });
        return ds;
    }

    var initSliced = sliceData(defaultRange);
    var dashboardChart = new Chart($dashboardCanvas, {
        type: 'line',
        data: { labels: initSliced.labels, datasets: buildDatasets(initSliced) },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: true }, tooltip: { enabled: true } },
            scales: {
                x: { ticks: { autoSkip: true, maxTicksLimit: isMobile ? 6 : 10, maxRotation: isMobile ? 0 : 45, minRotation: isMobile ? 0 : 45, font: { size: isMobile ? 8 : 11 } } },
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });

    var $rangeSelect = $('#upt-dashboard-range');
    var $customWrapper = $('#upt-dashboard-custom-dates');
    var $dateFrom = $('#upt-dashboard-date-from');
    var $dateTo = $('#upt-dashboard-date-to');
    var $formsRangeSelect = $('#upt-forms-range');
    var $formsCustomWrapper = $('#upt-forms-custom-dates');
    var $formsDateFrom = $('#upt-forms-date-from');
    var $formsDateTo = $('#upt-forms-date-to');
    var $submissionsGrid = $('.upt-submissions-grid');

    function filterSubmissions() {
        if (!$submissionsGrid.length) return;
        var val = $formsRangeSelect.length ? $formsRangeSelect.val() : '30';
        var now = new Date();
        var from = null, to = null;
        var MS = 24 * 60 * 60 * 1000;

        if (val === '7' || val === '30' || val === '90') {
            var days = parseInt(val, 10);
            from = new Date(now.getTime() - (days - 1) * MS);
            from.setHours(0, 0, 0, 0);
            to = new Date(now.getTime());
            to.setHours(23, 59, 59, 999);
        } else if (val === 'custom') {
            if ($formsDateFrom.val()) { from = new Date($formsDateFrom.val()); from.setHours(0, 0, 0, 0); }
            if ($formsDateTo.val()) { to = new Date($formsDateTo.val()); to.setHours(23, 59, 59, 999); }
        }

        $submissionsGrid.find('.upt-item-card--submission').each(function() {
            var ts = parseInt($(this).data('submission-timestamp'), 10);
            if (!ts) { $(this).show(); return; }
            var dt = new Date(ts * 1000);
            var vis = true;
            if (from && dt < from) vis = false;
            if (to && dt > to) vis = false;
            $(this).toggle(vis);
        });
    }

    function updateChart() {
        var val = $rangeSelect.val();
        var sliced;
        if (val === 'custom') {
            sliced = sliceCustom($dateFrom.val(), $dateTo.val());
        } else {
            sliced = sliceData(parseInt(val, 10) || defaultRange);
        }
        dashboardChart.data.labels = sliced.labels;
        dashboardChart.data.datasets = buildDatasets(sliced);
        dashboardChart.update();
        filterSubmissions();
    }

    $rangeSelect.on('change', function() {
        $customWrapper.toggle($rangeSelect.val() === 'custom');
        updateChart();
    });
    $dateFrom.on('change', updateChart);
    $dateTo.on('change', updateChart);

    if ($formsRangeSelect.length) {
        $formsRangeSelect.on('change', function() {
            $formsCustomWrapper.toggle($formsRangeSelect.val() === 'custom');
            filterSubmissions();
        });
        $formsDateFrom.on('change', filterSubmissions);
        $formsDateTo.on('change', filterSubmissions);
    }

    filterSubmissions();
})(jQuery);
