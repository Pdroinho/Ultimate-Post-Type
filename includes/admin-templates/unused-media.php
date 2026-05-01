<?php
if (!defined('ABSPATH'))
    exit;
?>
<div class="wrap upt-wrap">
    <h1><?php esc_html_e('Mídias não usadas', 'upt'); ?></h1>

    <?php if (!empty($message)): ?>
        <div class="notice notice-info"><p><?php echo esc_html($message); ?></p></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="notice notice-error"><p><?php esc_html_e('Algumas mídias não puderam ser apagadas.', 'upt'); ?></p></div>
    <?php endif; ?>

    <p><?php esc_html_e('Passo 1: faça varreduras parciais do conteúdo para identificar quais mídias estão sendo usadas em posts, páginas, produtos, modelos, etc.', 'upt'); ?></p>
    <p><?php esc_html_e('Passo 2: liste as mídias que nunca foram vistas em nenhum conteúdo e apague as que realmente não fazem mais sentido no site.', 'upt'); ?></p>

    <p>
        <strong><?php esc_html_e('Estado atual da varredura de conteúdo:', 'upt'); ?></strong><br />
        <?php
        if ($state['finished_posts']) {
            esc_html_e('Varredura de conteúdo concluída.', 'upt');
        } else {
            printf(
                esc_html__('Último post analisado: ID %d. Ainda há conteúdo a ser varrido.', 'upt'),
                (int)$state['last_post_id']
            );
        }
        ?>
    </p>

    <p>
        <strong><?php esc_html_e('Resumo das mídias:', 'upt'); ?></strong><br />
        <?php if ($total_attachments > 0): ?>
            <?php printf(esc_html__('Total de mídias: %d.', 'upt'), (int)$total_attachments); ?>
            <br />
            <?php if ($total_used !== null && $total_unused !== null): ?>
                <?php printf(esc_html__('Total usadas: %d.', 'upt'), (int)$total_used); ?>
                <br />
                <?php printf(esc_html__('Total potencialmente não usadas: %d.', 'upt'), (int)$total_unused); ?>
            <?php else: ?>
                <?php esc_html_e('Execute a varredura de conteúdo para calcular usadas e não usadas.', 'upt'); ?>
            <?php endif; ?>
        <?php else: ?>
            <?php esc_html_e('Nenhuma mídia encontrada na biblioteca.', 'upt'); ?>
        <?php endif; ?>
    </p>

    <form method="post" style="margin-bottom: 20px;" id="upt-scan-posts-form">
        <?php wp_nonce_field('upt_unused_scan_posts_action', 'upt_unused_scan_posts_nonce'); ?>
        <input type="hidden" name="upt_unused_scan_posts" value="1" />
        <p>
            <button type="submit" class="button button-secondary" id="upt-scan-posts-once">
                <?php esc_html_e('Avançar varredura de conteúdo (lote)', 'upt'); ?>
            </button>
            <button type="button" class="button button-primary" id="upt-start-auto-scan">
                <?php esc_html_e('Iniciar varredura automática', 'upt'); ?>
            </button>
            <button type="button" class="button" id="upt-stop-auto-scan">
                <?php esc_html_e('Parar varredura automática', 'upt'); ?>
            </button>
        </p>
    </form>

    <form method="post" style="margin-bottom: 20px;">
        <?php wp_nonce_field('upt_unused_list_media_action', 'upt_unused_list_media_nonce'); ?>
        <p>
            <label for="upt_unused_media_page"><strong><?php esc_html_e('Página de anexos para analisar (cada página = 200 mídias):', 'upt'); ?></strong></label>
            <select name="upt_unused_media_page" id="upt_unused_media_page">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <option value="<?php echo esc_attr($i); ?>" <?php selected($current_page, $i); ?>>
                        <?php echo esc_html($i); ?>
                    </option>
                <?php endfor; ?>
            </select>
            <span class="description">
                <?php printf(esc_html__('de %d páginas (%d mídias no total).', 'upt'), (int)$total_pages, (int)$total_attachments); ?>
            </span>
            <button type="submit" class="button button-secondary" name="upt_unused_list_media" value="1">
                <?php esc_html_e('Listar mídias potencialmente não usadas desta página', 'upt'); ?>
            </button>
        </p>
    </form>

    <form method="post" style="margin-bottom: 20px;">
        <?php wp_nonce_field('upt_unused_reset_action', 'upt_unused_reset_nonce'); ?>
        <p>
            <button type="submit" class="button" name="upt_unused_reset" value="1" onclick="return confirm('<?php echo esc_js(__('Tem certeza que deseja resetar completamente o estado da varredura? Isso não apaga nenhuma mídia, apenas faz o índice de uso ser reconstruído do zero.', 'upt')); ?>');">
                <?php esc_html_e('Resetar estado de varredura', 'upt'); ?>
            </button>
        </p>
    </form>

    <?php if (!empty($unused_attachments_found)): ?>
        <hr />
        <h2><?php esc_html_e('Mídias potencialmente não usadas nesta página de anexos', 'upt'); ?></h2>

        <form method="post">
            <?php wp_nonce_field('upt_unused_delete_action', 'upt_unused_delete_nonce'); ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="upt-select-all-unused" /></th>
                        <th><?php esc_html_e('Prévia', 'upt'); ?></th>
                        <th><?php esc_html_e('Arquivo', 'upt'); ?></th>
                        <th><?php esc_html_e('Data', 'upt'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unused_attachments_found as $att_id): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="upt_unused_ids[]" value="<?php echo esc_attr($att_id); ?>" />
                            </td>
                            <td>
                                <?php echo wp_get_attachment_image($att_id, [80, 80], true); ?>
                            </td>
                            <td>
                                <?php echo esc_html(get_the_title($att_id)); ?><br />
                                <code><?php echo esc_html(basename(get_attached_file($att_id))); ?></code>
                            </td>
                            <td>
                                <?php echo esc_html(get_the_date('', $att_id)); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p>
                <button type="submit" class="button button-primary" name="upt_unused_delete" value="1" onclick="return confirm('<?php echo esc_js(__('Tem certeza que deseja remover permanentemente as mídias selecionadas?', 'upt')); ?>');">
                    <?php esc_html_e('Apagar mídias selecionadas', 'upt'); ?>
                </button>
            </p>
        </form>
    <?php elseif ($batch_type === 'media'): ?>
        <hr />
        <p><?php esc_html_e('Nenhuma mídia potencialmente não utilizada foi encontrada nesta página de anexos.', 'upt'); ?></p>
    <?php endif; ?>
</div>

<script>
(function($){
    var finished   = <?php echo $state['finished_posts'] ? 'true' : 'false'; ?>;
    var lastAction = '<?php echo esc_js($last_action); ?>';
    $(function(){
        var autoEnabled = false;
        if (window.localStorage) {
            autoEnabled = localStorage.getItem('upt_unused_auto_scan') === '1';

            if (lastAction === 'reset') {
                localStorage.setItem('upt_unused_auto_scan', '0');
                autoEnabled = false;
            }
        }

        if (!finished && autoEnabled && lastAction === 'scan' && $('#upt-scan-posts-form').length) {
            setTimeout(function(){
                $('#upt-scan-posts-form').trigger('submit');
            }, 1000);
        }

        $(document).on('click', '#upt-start-auto-scan', function(e){
            e.preventDefault();
            if (window.localStorage) {
                localStorage.setItem('upt_unused_auto_scan', '1');
            }
            $('#upt-scan-posts-form').trigger('submit');
        });

        $(document).on('click', '#upt-stop-auto-scan', function(e){
            e.preventDefault();
            if (window.localStorage) {
                localStorage.setItem('upt_unused_auto_scan', '0');
            }
            alert('<?php echo esc_js(__('Varredura automática parada. Você pode continuar manualmente se desejar.', 'upt')); ?>');
        });

        $(document).on('change', '#upt-select-all-unused', function(){
            var checked = $(this).is(':checked');
            $('input[name="upt_unused_ids[]"]').prop('checked', checked);
        });
    });
})(jQuery);
</script>
