<?php
if (!defined('WP_UNINSTALL_PLUGIN'))
    exit;

global $wpdb;

$wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'catalog_item'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})");
$wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE object_id NOT IN (SELECT ID FROM {$wpdb->posts})");

$terms = $wpdb->get_results("SELECT t.term_id FROM {$wpdb->term_taxonomy} t WHERE t.taxonomy IN ('catalog_schema', 'catalog_category')");
if ($terms) {
    $term_ids = array_map('intval', wp_list_pluck($terms, 'term_id'));
    $term_ids_csv = implode(',', $term_ids);
    $wpdb->query("DELETE FROM {$wpdb->termmeta} WHERE term_id IN ({$term_ids_csv})");
    $wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN (SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id IN ({$term_ids_csv}))");
    $wpdb->query("DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ('catalog_schema', 'catalog_category')");
    $wpdb->query("DELETE FROM {$wpdb->terms} WHERE term_id IN ({$term_ids_csv})");
}

$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'upt_%'");

$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'upt_%'");

$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_upt_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_upt_%'");

$temp_dir = WP_CONTENT_DIR . '/uploads/upt_imob_temp';
if (is_dir($temp_dir)) {
    $files = glob($temp_dir . '/*');
    if ($files) {
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
    @rmdir($temp_dir);
}

delete_option('upt_schemas');
delete_option('upt_card_dashboard_fields');
delete_option('upt_card_site_fields');
delete_option('upt_imob_cron_config');
delete_option('upt_imob_cron_stats');

if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}
