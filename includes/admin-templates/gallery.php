<?php
if (!defined('ABSPATH'))
    exit;

$is_modal_mode = isset($_GET['noheader']);

if ($is_modal_mode) {
    $css_path = plugin_dir_path(__FILE__) . '../../assets/css/gallery.css';
    $js_path = plugin_dir_path(__FILE__) . '../../assets/js/gallery.js';
    $gallery_css_url = plugin_dir_url(__FILE__) . '../../assets/css/gallery.css';
    $gallery_js_url = plugin_dir_url(__FILE__) . '../../assets/js/gallery.js';
    $css_version = file_exists($css_path) ? filemtime($css_path) : '1.0';
    $js_version = file_exists($js_path) ? filemtime($js_path) : '1.0';

    $inline_styles = '';
    $accent_color = isset($_GET['accent_color']) ? sanitize_text_field(wp_unslash($_GET['accent_color'])) : '';

    if ($accent_color) {
        $inline_styles .= '<style>:root {';
        if ($accent_color) {
            if (preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $accent_color) || strpos($accent_color, 'rgb') === 0) {
                $inline_styles .= '--fc-primary-color: ' . $accent_color . ' !important;';
            }
        }
        $inline_styles .= '}</style>';
    }
?>
    <!DOCTYPE html>
    <html lang="pt-BR" style="background: transparent !important;">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="<?php echo esc_url($gallery_css_url . '?ver=' . $css_version); ?>">
        <?php echo $inline_styles; ?>
    </head>
    <body class="upt-gallery-modal-mode">
<?php
}
?>
<div class="wrap upt-gallery-wrap">
    <div class="upt-alert-config" data-upt-alert-enabled="1" data-upt-alert-media-deleted="1" data-upt-alert-media-uploaded="1" data-upt-alert-media-moved="1" data-upt-alert-duration="3"></div>
    <?php if ($is_modal_mode): ?>
        <button id="upt-gallery-close" class="button-icon-only">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg>
        </button>
    <?php else: ?>
        <h1>Galeria upt</h1>
    <?php endif; ?>

    <div class="upt-gallery-layout">
        <div class="gallery-overlay"></div>
        <aside class="gallery-sidebar">
            <div class="gallery-sidebar-header">
                <h2>Pastas</h2>
                <button id="close-sidebar-button" class="button-icon-only">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg>
                </button>
            </div>
            <div class="gallery-sidebar-content">
                <ul id="upt-folder-list" class="gallery-folder-list"></ul>
            </div>
            <div class="gallery-sidebar-footer">
                <select id="new-folder-parent" aria-label="Pasta superior" class="upt-new-folder-parent">
                    <option value="0">Criar em: Raiz</option>
                </select>
                <input type="text" id="new-folder-name" placeholder="Nome da nova pasta...">
                <div class="upt-folder-actions-row">
                    <button id="create-folder-button" class="button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10,4L12,6H20A2,2 0 0,1 22,8V18A2,2 0 0,1 20,20H4A2,2 0 0,1 2,18V6A2,2 0 0,1 4,4H10M15,11V14H12V16H15V19H17V16H20V14H17V11H15Z" /></svg>
                        <span>Criar Pasta</span>
                    </button>

                    <button id="delete-folders-bulk-button" class="button is-destructive upt-bulk-delete-folders-button" title="Apagar múltiplas pastas" aria-label="Apagar múltiplas pastas">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,4H15.5L14.79,3.29C14.61,3.11 14.35,3 14.09,3H9.91C9.65,3 9.39,3.11 9.21,3.29L8.5,4H5C4.45,4 4,4.45 4,5V6C4,6.55 4.45,7 5,7H19C19.55,7 20,6.55 20,6V5C20,4.45 19.55,4 19,4M6,19C6,20.11 6.89,21 8,21H16C17.11,21 18,20.11 18,19V7H6V19Z" /></svg>
                        <span>Apagar Pastas</span>
                    </button>
                </div>

                <div id="create-folder-status"></div>
            </div>
        </aside>

        <main class="gallery-main">
            <div class="gallery-main-header">
                <div class="header-row-1">
                    <button id="toggle-sidebar-button" class="button-icon-only">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3,6H21V8H3V6M3,11H21V13H3V11M3,16H21V18H3V16Z" /></svg>
                    </button>
                    <div class="header-title-wrap">
                        <h2 id="upt-current-folder">Todas as Mídias</h2>
                        <nav id="upt-breadcrumb" class="gallery-breadcrumb" aria-label="Caminho da pasta"></nav>
                    </div>
                </div>
                <div class="header-row-2">
                    <div class="gallery-select-actions">
                        <a href="#" id="select-all-button">Selecionar todas</a>
                        <a href="#" id="deselect-all-button">Limpar seleção</a>
                    </div>

                    <div class="gallery-header-actions">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=upt_gallery&upt_export_media=1')); ?>"
                           id="upt-export-media-button"
                           class="button button-secondary"
                           title="Exportar mídia" aria-label="Exportar mídia">
                            <svg class="upt-export-icon" style="transform: rotate(180deg);" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9,16V10H5L12,3L19,10H15V16H9M5,20V18H19V20H5Z" /></svg>
                            <span class="gallery-action-text">Exportar mídia</span>
                        </a>

                        <div id="gallery-actions" class="gallery-main-actions" style="display: none;">
                            <div id="move-to-folder-wrapper" style="display: none;">
                                <select id="move-to-folder-select">
                                    <option value="">Mover para...</option>
                                </select>
                            </div>
                            <?php if (!$is_modal_mode): ?>
                            <button id="remove-from-folder-button" class="button button-secondary" style="display: none;">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"><path d="M19.46 10.73L18.05 9.32L16 11.37L13.95 9.32L12.54 10.73L14.59 12.78L12.54 14.83L13.95 16.24L16 14.19L18.05 16.24L19.46 14.83L17.41 12.78L19.46 10.73Z" /></svg>
                                <span>Remover</span>
                            </button>
                            <?php endif; ?>
                            <button id="delete-image-button" class="button is-destructive" title="Excluir" aria-label="Excluir">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,4H15.5L14.79,3.29C14.61,3.11 14.35,3 14.09,3H9.91C9.65,3 9.39,3.11 9.21,3.29L8.5,4H5C4.45,4 4,4.45 4,5V6C4,6.55 4.45,7 5,7H19C19.55,7 20,6.55 20,6V5C20,4.45 19.55,4 19,4M6,19C6,20.11 6.89,21 8,21H16C17.11,21 18,20.11 18,19V7H6V19Z" /></svg>
                                <span>Excluir</span>
                            </button>
                            <?php if ($is_modal_mode): ?>
                            <div class="upt-use-btn-group" role="group" aria-label="Ações de seleção">
                                <button id="use-image-button" class="button button-primary" title="Usar" aria-label="Usar">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" /></svg>
                                    <span>Usar Mídia(s)</span>
                                </button>
                                <button id="upt-clear-selection-button" class="button button-secondary upt-clear-selection-button" title="Remover seleção" aria-label="Remover seleção" type="button">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.29 19.71 2.88 18.29 9.17 12 2.88 5.71 4.29 4.29 10.59 10.6l6.3-6.31z"/></svg>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div id="upt-upload-container">
                            <label for="upt-uploader" class="button button-primary upt-upload-button" title="Enviar mídia" aria-label="Enviar mídia">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9,16V10H5L12,3L19,10H15V16H9M5,20V18H19V20H5Z" /></svg>
                                <span class="upt-upload-button-main-text">Adicionar mídia</span>
                                <span class="upt-upload-button-subtext">Clique para cancelar</span>
                            </label>
                            <input type="file" id="upt-uploader" multiple style="display: none;" accept="image/*,video/*,application/pdf">
                            <div id="upt-upload-progress-wrapper" class="upt-progress-wrapper" style="display: none; margin-top: 6px;">
                                <div class="upt-progress-bar" style="position:relative;width:100%;height:6px;border-radius:999px;background:#e5e7eb;overflow:hidden;">
                                    <div class="upt-progress-bar-fill" style="position:absolute;left:0;top:0;bottom:0;width:0;border-radius:inherit;background:var(--fc-primary-color,#1d4ed8);"></div>
                                </div>
                                <span class="upt-progress-label" style="display:block;margin-top:4px;font-size:11px;color:#4b5563;">Enviando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="gallery-main-content">
                <div id="upt-image-grid" class="gallery-image-grid"></div>
                <div id="upt-gallery-pagination" class="upt-gallery-pagination" style="display:none;"></div>
            </div>
        </main>
    </div>
</div>
<?php
if ($is_modal_mode) {
    wp_enqueue_media();
?>
    <script src="<?php echo esc_url(includes_url('js/jquery/jquery.js')); ?>"></script>
    <script>
        var upt_gallery = {
            "ajax_url": "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
            "upload_url": "<?php echo esc_url(admin_url('async-upload.php')); ?>",
            "nonce": "<?php echo esc_js(wp_create_nonce('upt_ajax_nonce')); ?>",
            "upload_nonce": "<?php echo esc_js(wp_create_nonce('media-form')); ?>",
            "transparent_png": "<?php echo esc_url(plugin_dir_url(__FILE__) . '../../assets/img/1x1.png'); ?>",
            "pdf_placeholder": "<?php echo esc_url(wp_mime_type_icon('application/pdf')); ?>"
        };
    </script>
    <?php do_action('admin_footer'); ?>
    <script src="<?php echo esc_url($gallery_js_url . '?ver=' . $js_version); ?>"></script>
    </body>
    </html>
<?php
    exit;
} else {
?>
    <script>
        var upt_gallery = window.upt_gallery || {
            "ajax_url": "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
            "upload_url": "<?php echo esc_url(admin_url('async-upload.php')); ?>",
            "nonce": "<?php echo esc_js(wp_create_nonce('upt_ajax_nonce')); ?>",
            "upload_nonce": "<?php echo esc_js(wp_create_nonce('media-form')); ?>",
            "transparent_png": "<?php echo esc_url(plugin_dir_url(__FILE__) . '../../assets/img/1x1.png'); ?>",
            "pdf_placeholder": "<?php echo esc_url(wp_mime_type_icon('application/pdf')); ?>"
        };
    </script>
<?php
}
