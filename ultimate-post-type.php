<?php
/**
 * Plugin Name:       Ultimate Post Type
 * Description:       Gerenciador de itens de catálogo com submissão e gerenciamento via front-end.
 * Version: V20.7.35-wizard
 * Author:            Pedro e Matheus
 */

/**
 * IMPORTANTE PARA QUALQUER IA / FERRAMENTA AUTOMÁTICA:
 *
 * Antes de modificar este plugin, LEIA obrigatoriamente:
 *   UPT_IA_RULES.md  (na raiz da pasta do plugin)
 *
 * Todas as alterações devem seguir estritamente as regras documentadas lá.
 *
 * CHANGELOG:
 * [V20.7.35-wizard]
 * - Importação XML: categorias automáticas criadas a partir de TipoImovel + Cidade (ex: Casa > Aracaju).
 * - Importação XML: campo "Status do Imóvel" criado automaticamente (Venda / Aluguel / Venda e Aluguel / Consulte).
 * - Importação XML: campos PrecoLocacao e PrecoLocacaoTemporada agora importados.
 * - Importação XML: campo Empreendimento importado.
 * - Importação XML: preços formatados como moeda brasileira (ex: 410.000,00).
 * - Importação XML: sync incremental — re-importação preserva galeria e thumbnail existentes.
 * - Importação XML: corrigido bug onde todos os imóveis ficavam com todas as categorias.
 * - Importação XML: importação disponível também no /painel (tab "Importar XML").
 *
 * [V20.7.34-wizard]
 * - Importação XML de Imobiliária: reescrita completa para processamento em lotes via AJAX.
 * - Upload do XML salva como arquivo temporário; processamento em lotes de N imóveis por vez.
 * - Barra de progresso visual com contadores (total, processados, importados, fotos, erros).
 * - Retomada automática: se o processo morrer no meio, pode ser reiniciado sem duplicar imóveis.
 * - Cancelamento: botão para interromper a importação a qualquer momento.
 * - Contagem prévia: conta total de imóveis no XML antes de começar.
 * - Anti-SSRF: bloqueia download de imagens de IPs privados/locais.
 *
 * [V20.7.33-wizard]
 * - Filtro de Categorias (Elementor): adiciona alinhamento "Justificada" para equilibrar itens em 2 linhas (sem deixar 1 sozinho) e preencher a largura disponível.
 *
 * [V20.7.31-wizard]
 * - Correção do Filtro Pai/Filho: o Filho agora carrega subcategorias via AJAX público e não renderiza categorias pai.
 * - Correção de auto-detecção: o widget não lista a si mesmo como alvo e a lista de alvos atualiza no editor ao adicionar/remover widgets.
 * - Correção do endpoint público de subcategorias (static).
 *
 * [V20.7.30-wizard]
 * - Filtro de Categorias (Elementor): adiciona modos Pai/Filho para encadear filtros (pai -> subcategorias -> grade) e endpoint público para carregar subcategorias via AJAX.
 *
 * [V20.7.24-wizard]
 * - Grade do Catálogo (AJAX e modo Link/GET): ao filtrar por categoria, usa include_children=true para garantir que itens das subcategorias apareçam ao clicar na categoria pai.
 *
 * [V20.7.23-wizard]
 * - Grade do Catálogo + Filtro de Categorias: ao selecionar uma categoria, inclui itens também das subcategorias (descendentes) na filtragem (AJAX e modo Link/GET).
 *
 * [V20.7.22-wizard]
 * - Filtro de Categorias (Elementor): ao ativar "Ocultar Categorias Vazias", considera itens nas subcategorias (descendentes) e não apenas a contagem direta.
 *
 * [V20.7.21-wizard]
 * - Importação: sanitiza endereço/nome de imagens (remove espaços e vírgulas) para vincular com mídias já existentes.
 *
 * [V20.7.20-wizard]
 * - WP Admin (itens/categorias/esquemas): adiciona botão "Visualizar todos" para desativar paginação (upt_show_all=1).
 *
 * [V20.7.17-wizard]
 * - Galeria (modal): botão "Usar" vira grupo com botão de remover seleção (ícone X) colado à direita, estilo cinza.
 * - Esquemas: ordenação manual passa a listar itens mais recentes primeiro (meta upt_manual_order DESC).
 * - WP Metabox (categorias): card "Nova categoria" usa o mesmo background do bloco de subcategorias; campos permanecem brancos.
 *
 * [V20.7.15-wizard]
 * - Importação WXR: vínculo de imagem melhorado (prioriza caminho relativo extraído da URL de uploads; fallback por filename).
 * - Importação WXR: categorias não criam/alteram parent automaticamente; reutiliza termos existentes por nome/slug e só cria quando necessário.
 * - Importar mídia (ZIP): preserva nomes/caminhos do ZIP dentro de uploads (sem renomear) e registra attachments com _wp_attached_file igual ao caminho.
 *
 * [V20.7.11-wizard]
 * Ajuste: dropdown de categoria pai exibe hierarquia (níveis/subcategorias).
 * - Importar/Exportar > Categorias (MD): adicionar seletor opcional de categoria pai para importar a hierarquia sob um termo existente.
 * - Importação de categorias (MD): suporte a headings (##) e listas ("-", "*", "+"), mantendo compatibilidade com indentação.
 *
 * [V20.7.7-wizard]
 * - WP Admin: adiciona importação/exportação de categorias via arquivo .md na tela Importar/Exportar.
 * - Formato do .md: categorias em linha única e subcategorias com indentação de 2 espaços.
 *
 * [V20.7.6-wizard]
 * - Filtro (dropdown custom): ajusta caret (menor) e desloca levemente à direita para não sobrepor o texto.
 * - Filtro (dropdown custom): aumenta reserva de largura para manter folga consistente entre texto e caret.
 * - Form: criação de categoria/subcategoria com fundo branco; adiciona label "Nova categoria" e espaçamento no grupo de ações.
 * [V20.7.5-wizard]
 * - Filtro (dropdown custom): caret volta a ficar ao lado do texto (sem quebrar linha) com gap correto.
 * - Filtro (dropdown custom): cálculo de largura considera padding + gap + caret, evitando seta "grudada".
 *
 * [V20.6.8-wizard]
 * - Painel: botão "Selecionar todos" agora exibe texto "Tudo" (sem ícone) mantendo o mesmo comportamento.
 * - Painel: subcategorias no filtro recebem fonte menor e cor de destaque (limitação do select nativo: aplica ao item inteiro).
 * [V20.6.5-wizard]
 * - Painel: botão de confirmação da exclusão em massa mostra "Excluir N" (contador de selecionados).
 * - Painel: adiciona botão "Selecionar todos" no group (seleciona todos do view atual).
 * [V20.6.3-wizard]
 * - Painel: botão de exclusão em massa vira group button (Confirmar Exclusão + X para cancelar).
 * - Painel: cancelar sai do modo seleção e limpa checkboxes.
 * - Painel: botão X (cancelar) com background cinza.
 * - Painel: no modo exclusão em massa, clique em qualquer área do card alterna o checkbox.
 *
 * [V20.6.0-wizard]
 * - Importação WXR: detecção/leitura mais tolerante a namespaces/prefixos (evita "Nenhum item foi encontrado" em exports variados). * - Importação WXR: não importa anexos como itens; usa anexos apenas para mapear imagens.
 * - Importação WXR: resolve _thumbnail_id para filename/URL e vincula imagem existente na Mídia por nome.
 * - Importação: ao selecionar XML, pergunta se deve usar campos do arquivo (criar extras) ou manter apenas campos do upt.
 * - Importação: busca de mídia por filename mais resiliente (suporta sufixo -150x150 e URL encoded).
 *
 * [V20.5.7-wizard]
 * - Correção: importação XML agora repara caracteres inválidos e & não escapado (evita erro de XML inválido).
 * - Padroniza estilo do label "Sub-categoria" para ficar igual aos demais labels.
 *
 * [V20.5.4-wizard]
 * - Label 'Sub-categoria' agora usa o mesmo peso/estilo do label de categoria.
 * [V20.5.3-wizard]
 * - Filtro de categorias no painel: mantém apenas 1 seta (remove duplicidade/ausência).
 * - Campos de renomear subcategoria/vínculo e renomear categoria com background branco.
 * - Caixa de seleção: opções para habilitar renomear/excluir opções no formulário (com botões e AJAX).
 * [V20.4.8-wizard]
 * - Ajuste de cor de foco em selects para respeitar a cor accent (fc-primary-color) em vez de vermelho fixo.
 * - Correção de ações de renomear/excluir categoria e editar/excluir subcategoria para usar o campo correto (scope por linha e wrapper correto).
 * [V20.4.4-wizard]
 * - Adicionada opção 'Exibir Subcategorias' no widget de Filtro de Categorias.
 * [V20.4.3-wizard]
 */


if (!defined('ABSPATH'))
    exit;

define('UPT_PLUGIN_DIR', plugin_dir_path(__FILE__));

final class UPT
{
    private static $_instance = null;
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    private function __construct()
    {
        load_plugin_textdomain('ultimate-post-type', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->load_dependencies();
        $this->init_hooks();
    }
    private function load_dependencies()
    {
        require_once UPT_PLUGIN_DIR . 'includes/class-cpt.php';
        require_once UPT_PLUGIN_DIR . 'includes/class-taxonomies.php';
        require_once UPT_PLUGIN_DIR . 'includes/class-roles.php';
        require_once UPT_PLUGIN_DIR . 'includes/class-auth.php';
        require_once UPT_PLUGIN_DIR . 'includes/class-admin.php';
        require_once UPT_PLUGIN_DIR . 'includes/class-schema-store.php';
        require_once UPT_PLUGIN_DIR . 'includes/class-ajax.php';
        require_once UPT_PLUGIN_DIR . 'includes/class-media-folders.php';
        require_once UPT_PLUGIN_DIR . 'includes/class-image-webp.php';
        require_once UPT_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once UPT_PLUGIN_DIR . 'includes/class-cache.php';
        require_once UPT_PLUGIN_DIR . 'includes/class-imobiliaria-importer.php';
        require_once UPT_PLUGIN_DIR . 'includes/elementor/class-elementor.php';
    }

    public function enqueue_scripts()
    {
        wp_register_script('upt-main-js', plugin_dir_url(__FILE__) . 'assets/js/front.js', ['jquery'], filemtime(plugin_dir_path(__FILE__) . 'assets/js/front.js'), true);
        wp_localize_script('upt-main-js', 'upt_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'admin_url' => admin_url(), // Adiciona a URL do admin
            'nonce' => wp_create_nonce('upt_ajax_nonce'),
            'transparent_png' => plugin_dir_url(__FILE__) . 'assets/img/1x1.png',
            'pdf_placeholder' => wp_mime_type_icon('application/pdf'),
        ]);

        wp_register_style('upt-style', plugin_dir_url(__FILE__) . 'assets/css/front.css', [], filemtime(plugin_dir_path(__FILE__) . 'assets/css/front.css'));
    }

    private function init_hooks()
    {
        UPT_CPT::init();
        UPT_Taxonomies::init();
        UPT_Auth::init();
        UPT_Admin::init();
        UPT_Ajax::init();
        UPT_Media_Folders::init();
        UPT_Image_WebP::init();
        UPT_Shortcodes::init();
        UPT_Cache::init();

        add_action('upt_imob_cron_import', [$this, 'run_cron_import']);
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);

        if (did_action('elementor/loaded')) {
            UPT_Elementor::init();
        }
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_cron_schedules($schedules)
    {
        $schedules['sixhourly'] = [
            'interval' => 21600,
            'display'  => 'A cada 6 horas',
        ];
        return $schedules;
    }

    public function run_cron_import()
    {
        $config = get_option('upt_imob_cron_config', []);
        if (empty($config['url']) || empty($config['schema']) || empty($config['active'])) {
            return;
        }

        $lock_key = 'upt_imob_cron_lock';
        $lock = get_transient($lock_key);
        if ($lock) {
            return;
        }
        set_transient($lock_key, '1', 30 * MINUTE_IN_SECONDS);

        require_once UPT_PLUGIN_DIR . 'includes/class-imobiliaria-importer.php';

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $tmp = download_url($config['url'], 120);

        if (is_wp_error($tmp)) {
            $config['last_run'] = current_time('mysql') . ' (Erro: download falhou)';
            update_option('upt_imob_cron_config', $config);
            delete_transient($lock_key);
            return;
        }

        $result = UPT_Imobiliaria_Importer::prepare_upload($tmp, $config['schema'], $config['schema'], 'existing');

        @unlink($tmp);

        if (is_wp_error($result)) {
            $config['last_run'] = current_time('mysql') . ' (Erro: ' . $result->get_error_message() . ')';
            update_option('upt_imob_cron_config', $config);
            delete_transient($lock_key);
            return;
        }

        $session_id = $result;
        $total = 0;
        $offset = 0;
        $imported = 0;
        $photos = 0;
        $errors = 0;

        $count_result = UPT_Imobiliaria_Importer::ajax_count($session_id);
        if (!is_wp_error($count_result)) {
            $total = $count_result['total'];
        }

        $batch_limit = 10;
        $max_batches = 50;
        $batch_num = 0;

        while ($offset < $total && $batch_num < $max_batches) {
            $batch_result = UPT_Imobiliaria_Importer::ajax_process_batch($session_id, $offset, $batch_limit);

            if (is_wp_error($batch_result)) {
                $errors++;
                $offset += $batch_limit;
                $batch_num++;
                sleep(2);
                continue;
            }

            $imported += $batch_result['imported'];
            $photos += $batch_result['photos'];
            $errors += $batch_result['errors'];

            if (!empty($batch_result['is_finished'])) {
                break;
            }

            $offset = $batch_result['next_offset'];
            $batch_num++;
            sleep(1);
        }

        $stats = get_option('upt_imob_cron_stats', ['total' => 0, 'imported' => 0, 'errors' => 0]);
        $stats['imported'] = (isset($stats['imported']) ? $stats['imported'] : 0) + $imported;
        $stats['total'] = $total;
        $stats['errors'] = (isset($stats['errors']) ? $stats['errors'] : 0) + $errors;
        update_option('upt_imob_cron_stats', $stats);

        $config['last_run'] = current_time('mysql') . " (Importados: {$imported}, Fotos: {$photos}, Erros: {$errors})";
        update_option('upt_imob_cron_config', $config);

        UPT_Imobiliaria_Importer::ajax_cancel($session_id);

        delete_transient($lock_key);

        wp_clear_scheduled_hook('upt_imob_cron_import');
        $freq = isset($config['frequency']) ? $config['frequency'] : 'sixhourly';
        wp_schedule_event(time(), $freq, 'upt_imob_cron_import');
    }
}

function upt()
{
    return UPT::instance();
}
add_action('plugins_loaded', 'upt');

function upt_activate()
{
    $activation_buffer_level = ob_get_level();
    ob_start();
    require_once UPT_PLUGIN_DIR . 'includes/class-roles.php';
    require_once UPT_PLUGIN_DIR . 'includes/class-shortcodes.php';
    UPT_Roles::add_roles();
    UPT_Shortcodes::maybe_create_dashboard_page();
    while (ob_get_level() > $activation_buffer_level) {
        ob_end_clean();
    }
}

register_activation_hook(__FILE__, 'upt_activate');

function upt_deactivate()
{
    require_once UPT_PLUGIN_DIR . 'includes/class-roles.php';
    UPT_Roles::remove_roles();
    wp_clear_scheduled_hook('upt_imob_cron_import');
}
register_deactivation_hook(__FILE__, 'upt_deactivate');
