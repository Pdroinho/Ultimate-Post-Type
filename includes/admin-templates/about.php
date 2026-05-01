<?php
if (!defined('ABSPATH'))
    exit;
?>
<div class="wrap upt-about">
    <h1><?php esc_html_e('Sobre o upt', 'upt'); ?></h1>
    <p>
        O Ultimate Post Type é um construtor de catálogos e dashboards que combina esquemas personalizados,
        cards dinâmicos, biblioteca de mídia própria e integração com Elementor.
    </p>

    <h2>1. Builder de Esquemas</h2>
    <p>Permite criar, editar, duplicar e excluir esquemas que definem os campos de cada item do catálogo.</p>
    <ul>
        <li>Criação de esquemas com múltiplos tipos de campo.</li>
        <li>Exportar e importar esquemas em XML, incluindo metadados de mídia.</li>
        <li>Recriação de campos de mídia (imagem, vídeo, galeria) ao importar.</li>
    </ul>

    <h2>2. Campos disponíveis</h2>
    <p>Conjunto de tipos de campo usados para modelar os itens.</p>
    <ul>
        <li>Texto, textarea, número, URL, data.</li>
        <li>WYSIWYG e editor completo para conteúdo rico.</li>
        <li>Select, checkbox, switch.</li>
        <li>Cor, ícone.</li>
        <li>Imagem nativa, vídeo, galeria e repeater.</li>
    </ul>

    <h2>3. CRUD de Itens (Cards)</h2>
    <p>Gestão dos itens do catálogo baseados em um esquema.</p>
    <ul>
        <li>Criar, editar, clonar e excluir itens.</li>
        <li>Ordenação manual (drag-and-drop) e ordenação automática.</li>
        <li>Busca, filtros, paginação e visualização de campos de mídia.</li>
    </ul>

    <h2>4. Salvamento e carregamento</h2>
    <p>Persistência dos dados de forma rápida e confiável.</p>
    <ul>
        <li>Salvamento via AJAX sem recarregar a página.</li>
        <li>Atualização em tempo real do grid após salvar.</li>
        <li>Recuperação completa dos valores ao editar, incluindo WYSIWYG e editor completo.</li>
    </ul>

    <h2>5. Biblioteca de Mídias do upt</h2>
    <p>Camada própria de organização de arquivos para o catálogo.</p>
    <ul>
        <li>Upload de imagens e vídeos, com suporte a pastas.</li>
        <li>Importar ZIP de mídia mantendo a estrutura de pastas.</li>
        <li>Seleção simples ou múltipla (galeria) com badges de tipo/extensão.</li>
        <li>Exportar mídia em ZIP, por seleção ou completo.</li>
    </ul>

    <h2>6. Renderização no Elementor</h2>
    <p>Widgets para exibir os dados do upt no front-end.</p>
    <ul>
        <li>Widgets de listagem, dashboard e ações.</li>
        <li>Loop de itens com mapeamento de campos para HTML.</li>
        <li>Suporte a campos de mídia, como imagem, vídeo e galeria.</li>
    </ul>

    <h2>7. Sistema de versão interna</h2>
    <p>Controle de versão e regras de modificação do plugin.</p>
    <ul>
        <li>Arquivo de regras para IAs e automações (UPT_IA_RULES.md).</li>
        <li>Exibição da versão instalada no painel.</li>
    </ul>

    <h2>8. UI/UX do Dashboard</h2>
    <p>Interface voltada para leitura rápida e operação diária.</p>
    <ul>
        <li>Grid responsivo de cards e tabela com cabeçalho fixo.</li>
        <li>Zebra, máscara de telefone e micro animações.</li>
        <li>Modais para adicionar/editar, "Salvar e adicionar outro" e atalho Ctrl+Enter.</li>
        <li>Estados vazios, skeleton e toasts de feedback.</li>
    </ul>

    <h2>9. Exportação e importação avançada</h2>
    <p>Mecanismos para transportar estrutura e dados entre sites.</p>
    <ul>
        <li>Exportar esquemas com informação de mídia associada.</li>
        <li>Importar recriando campos e tentando manter IDs quando possível.</li>
        <li>Scan de ZIP de mídia para localizar arquivos usados pelos campos.</li>
    </ul>

    <h2>10. Funções automáticas internas</h2>
    <p>Camada técnica que mantém o plugin estável.</p>
    <ul>
        <li>Sanitização e normalização de dados.</li>
        <li>Tratamento de JSON e compatibilidade com multisite.</li>
        <li>Hooks e logging interno para debug controlado.</li>
    </ul>
</div>
