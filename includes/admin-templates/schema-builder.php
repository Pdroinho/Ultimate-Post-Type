<?php
if (!defined('ABSPATH'))
    exit;
?>
<div class="wrap upt-wrap">
    <h1>Construtor de Esquemas e Campos</h1>
    <?php
    if ($success_msg = get_transient('upt_settings_success')) {
        add_settings_error('upt_notices', 'schema_success', $success_msg, 'success');
        delete_transient('upt_settings_success');
    }
    settings_errors();
    ?>

    <div class="upt-builder">
        <div class="upt-builder__sidebar">
            <div class="upt-card">
                <div class="upt-card__header">
                    <h2>Esquemas</h2>
                </div>
                <?php UPT_Admin::render_schema_list(); ?>
                <div class="upt-card__body">
                     <?php UPT_Admin::render_add_schema_form(); ?>
                </div>
            </div>
        </div>

        <div class="upt-builder__main">
            <div class="upt-card">
                 <div class="upt-card__body">
                    <?php
                    if (isset($_GET['schema']) && !empty($_GET['schema'])) {
                        $schema_slug = sanitize_text_field($_GET['schema']);
                        UPT_Admin::render_field_list($schema_slug);
                        if (apply_filters('upt_show_schema_items_order_admin', false)) {
                            UPT_Admin::render_schema_items_order($schema_slug);
                        }
                        UPT_Admin::render_add_field_form($schema_slug);
                    } else {
                        echo '<p>Por favor, selecione um esquema à esquerda para começar.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
