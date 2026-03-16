# REGRAS OFICIAIS PARA QUALQUER IA ANTES DE MODIFICAR O PLUGIN UPT

ATENÇÃO: Qualquer sistema automatizado (IA, script ou ferramenta) que for modificar este plugin DEVE ler e seguir este arquivo antes de realizar qualquer alteração.

## 1. Princípios Gerais

1. Não remover nem alterar funcionalidades existentes sem ordem explícita do usuário.
2. Toda modificação deve ser mínima, localizada e sem efeitos colaterais em outras partes do plugin.
3. Nunca renomear:
   - classes PHP já existentes;
   - actions/filters WordPress registrados;
   - handles de scripts e estilos;
   - IDs e classes HTML usados em JS.

## 2. Versão Atual

**Versão Atual:** V20.7.32-wizard

**Componentes Principais:**
- Plugin WordPress para gerenciamento de catálogos front-end
- Integração completa com Elementor (10 widgets e 5 tags dinâmicas)
- Sistema de galeria de mídia organizada por taxonomias
- Painel admin com presets (Hostinger e SaaS)
- Importação/Exportação de esquemas (XML) e categorias (MD)

## 3. Fluxo Obrigatório de Trabalho

1. Receber o ZIP do plugin, extrair e trabalhar apenas dentro da pasta `ultimate-post-type`.
2. Nunca mudar o prefixo da pasta principal.
3. Ao finalizar:
   - conferir sintaxe PHP;
   - conferir sintaxe JS;
   - gerar um novo ZIP com o número de versão correto.

## 4. Regra de Versionamento

**Formato:** `V{major}.{minor}.{patch}-{suffix}`

**Exemplo atual:** V20.7.32-wizard

**Regras:**
- Não pular versões
- Não voltar versão
- Não inventar padrões diferentes (ex.: `V45-new`, `V45a` etc.)
- A versão deve aparecer:
  - no cabeçalho do plugin (`Version:`)
  - onde existir constante de versão (ex.: `UPT_VERSION`)

## 5. Elementor - Widgets e Tags Dinâmicas

### Widgets Disponíveis (V20.7.32-wizard)

1. **UPT_Dashboard_Widget** - Painel principal de catálogo
   - Presets: Hostinger e SaaS
   - Layout: sidebar + conteúdo principal
   - Suporte a abas, galeria, filtros
   - Personalização avançada de cores e fontes

2. **UPT_Listing_Widget** - Grade de itens do catálogo
   - Suporte a templates customizados
   - Filtros embutidos
   - Paginação AJAX
   - Modo Link/GET para filtragem via URL

3. **UPT_Category_Filter_Widget** - Filtro de categorias
   - Layouts: lista e dropdown
   - Modo Pai/Filho para hierarquias
   - Alinhamento justificado disponível
   - Ocultar categorias vazias (considera descendentes)

4. **UPT_Product_Gallery_Widget** - Galeria de produtos
   - Imagem principal
   - Navegação por setas
   - Thumbnails interativos

5. **UPT_Search_Widget** - Campo de busca
   - Configuração de filtros
   - Personalização de botão e input

6. **UPT_WhatsApp_Button_Widget** - Botão WhatsApp
   - Configuração de número e mensagem
   - Estilização completa

7. **UPT_Dashboard_Actions_Widget** - Ações do painel
   - Botões de ação customizados

8. **UPT_Text_Editor_Widget** - Editor de texto
   - Configuração de conteúdo

9. **UPT_Video_Widget** - Widget de vídeo
   - Configuração de player

### Tags Dinâmicas Disponíveis

1. **custom-field-tag** - Campos personalizados
2. **dashboard-data-tag** - Dados do painel
3. **gallery-field-tag** - Campos de galeria
4. **pdf-field-tag** - Campos PDF
5. **related-item-data-tag** - Dados de itens relacionados
6. **video-field-tag** - Campos de vídeo

### Regras Críticas para Widgets Elementor

#### Estrutura de Controles
- **OBIGATÓRIO:** Toda seção iniciada com `start_controls_section()` DEVE ser fechada com `end_controls_section()`
- Nunca aninhar seções
- Controles (add_control) devem estar sempre DENTRO de uma seção
- Estrutura correta:
  ```php
  $this->start_controls_section('section_name', [...]);
      $this->add_control('control_name', [...]);
      $this->add_control('another_control', [...]);
  $this->end_controls_section();
  
  $this->start_controls_section('next_section', [...]);
  ```

#### Nomes de Widgets
- Não renomear widgets existentes
- Novos controles devem ser adicionados, nunca substituindo os existentes
- Estilos de paginação devem manter seletores originais

## 6. Galeria (Admin e Modal Elementor)

1. Manter os IDs e classes principais intactos: `#upt-gallery-app`, `#upt-export-media-button`, `.gallery-image-item`, `.gallery-video-item`, etc.
2. Manter variáveis JS principais:
   - `globalSelectedMediaIds`
   - `globalSelectedMediaMap`
   - `isMultiSelectMode`
3. Em WordPress (menu Catálogo → Galeria), o botão "Usar" deve ficar oculto.
4. No modal do Elementor, o botão "Usar (N)" deve continuar visível e funcional.
5. Não alterar a estrutura de colunas (lista de pastas à esquerda, grid de imagens à direita).

## 7. Exportar Mídia

### JS

- O handler de `#upt-export-media-button` deve seguir a lógica:
  - Se `isMultiSelectMode === true`: usar `globalSelectedMediaIds` para determinar as mídias selecionadas.
  - Se `isMultiSelectMode === false`: ler os itens `.gallery-image-item.selected` / `.gallery-video-item.selected` diretamente do DOM.

- Comportamento obrigatório:
  - 0 selecionadas → navegar para `href` base e exportar tudo (ZIP completo, pastas + "sem-pasta").
  - 1 selecionada → adicionar `single_media_id=<id>` e baixar somente esse arquivo, sem ZIP.
  - 2+ selecionadas → adicionar `media_ids=<id1,id2,...>` e baixar somente os selecionados em um ZIP.

### PHP (`handle_upt_media_export()`)

- Validar permissão com `current_user_can( 'upload_files' )`.
- Tratar três cenários:
  1. `single_media_id`: saída direta do arquivo com headers corretos.
  2. `media_ids`: ZIP apenas com esses IDs.
  3. nenhum parâmetro: exportar todas as mídias, organizadas em:
     - pastas por taxonomia (`UPT_Media_Folders::TAXONOMY`);
     - pasta `sem-pasta/` para anexos sem termo.
- Sempre limpar buffers antes de enviar arquivo (`while (ob_get_level()) ob_end_clean();`).
- Usar `ZipArchive` apenas quando o resultado for ZIP.

## 8. Dashboard e Rastreio de Cliques

- Um bloco de plugin só deve aparecer no dashboard se:
  - o CPT correspondente existir; **e**
  - houver pelo menos 1 clique rastreado.

Exemplo:
- `upt_has_buttons_cpt` = `post_type_exists('4gt_button_click') && ! empty($upt_buttons_stats['total'])`.
- `upt_has_images_cpt`  = `post_type_exists('4gt_image_click') && ! empty($upt_images_stats['total'])`.

Não exibir painéis zerados para botões/imagens antes de qualquer clique.

## 9. Painel Admin - Presets

### Preset Hostinger (Default)
- Cor principal: `#673DE6`
- Design otimizado para infraestrutura

### Preset SaaS
- Cor principal: `#6366f1`
- Sidebar escura: `#111827`
- Header branco: `#ffffff`
- Conteúdo: `#f1f5f9`
- Cards: `#ffffff`
- Logo customizável
- Largura da sidebar: 140-420px (padrão: 240px)
- Arredondamento: 0-40px (padrão: 8px)
- Botão "Adicionar Novo Item" customizável

## 10. Filtros e Categorias

### Filtro Pai/Filho
- Modo pai carrega categorias principais
- Ao selecionar pai, carrega subcategorias via AJAX público
- Endpoint público: `upt_get_subcategories` (static)
- Filho não renderiza categorias pai

### Hierarquia de Categorias
- Dropdown exibe hierarquia com níveis/subcategorias
- Subcategorias com fonte menor e cor de destaque
- Importação/Exportação suporta hierarquia completa

### Filtragem de Itens
- Ao filtrar por categoria, inclui itens de subcategorias (descendentes)
- Usa `include_children=true` para garantir consistência
- Funciona tanto em modo AJAX quanto Link/GET

## 11. Importação/Exportação

### Esquemas (XML)
- O plugin trabalha apenas com **XML** para esquemas. JSON não deve ser reativado.
- Não alterar a lógica de categorias nem o fluxo base de importação/exportação sem ordem explícita do usuário.
- Detecção tolerante a namespaces/prefixos
- Repara caracteres inválidos e & não escapado
- Vínculo de imagem por caminho relativo ou filename
- Opção de usar campos do XML ou apenas campos do upt

### Categorias (Markdown)
- Formato: categorias em linha única e subcategorias com indentação de 2 espaços
- Suporte a headings (##) e listas ("-", "*", "+")
- Importação permite selecionar categoria pai
- Exportação preserva hierarquia completa

### Mídia (ZIP)
- Preserva nomes/caminhos do ZIP dentro de uploads
- Não renomeia arquivos
- Registra attachments com `_wp_attached_file` igual ao caminho
- Sanitiza endereço/nome de imagens (remove espaços e vírgulas)

## 12. Layout e Estilo

- Não alterar:
  - grid da galeria
  - estrutura das colunas
  - IDs e classes de containers principais
- Mudanças visuais só são permitidas quando pedido e devem ser localizadas
- Cor de foco em selects respeita `fc-primary-color`

## 13. WP Admin

- Botão "Visualizar todos" para desativar paginação (`upt_show_all=1`)
- Cards com fundo branco para formulários
- Label "Nova categoria" com espaçamento
- Dropdown custom com caret ajustado
- Ajuste de cor de foco em selects

## 14. Classes Principais do Plugin

- **UPT** - Classe principal e singleton
- **UPT_CPT** - Custom Post Types
- **UPT_Taxonomies** - Taxonomias do catálogo
- **UPT_Roles** - Permissões e roles
- **UPT_Auth** - Autenticação e autorização
- **UPT_Form_Handler** - Processamento de formulários
- **UPT_Admin** - Interface administrativa
- **UPT_Schema_Store** - Armazenamento de esquemas
- **UPT_Ajax** - Manipuladores AJAX
- **UPT_Media_Folders** - Organização de mídia
- **UPT_Image_WebP** - Conversão WebP
- **UPT_Shortcodes** - Shortcodes do plugin
- **UPT_Cache** - Sistema de cache
- **UPT_Elementor** - Integração Elementor

## 15. Taxonomias e CPTs

### CPTs
- `upt_item` - Itens do catálogo
- `upt_schema` - Esquemas de catálogo
- CPTs de rastreio (4gt_button_click, 4gt_image_click, etc.)

### Taxonomias
- `catalog_category` - Categorias do catálogo (hierárquica)
- `catalog_schema` - Esquemas de catálogo

## 16. Checklist Antes de Entregar Nova Versão

Antes de gerar o ZIP final:

1. Ativar o plugin e garantir ausência de `Parse error`.
2. Confirmar:
   - Galeria no admin carrega pastas e imagens.
   - Modal no Elementor abre, seleciona e aplica imagens.
   - Exportação:
     - sem seleção → ZIP completo;
     - 1 seleção → arquivo direto;
     - múltiplas seleções → ZIP com seleção.
3. Verificar se o número da versão foi atualizado corretamente em:
   - cabeçalho do plugin;
   - constante de versão (se existir).
4. Testar widgets do Elementor:
   - Verificar estrutura de seções (start_controls_section/end_controls_section)
   - Confirmar que todos controles estão dentro de seções
   - Testar presets (Hostinger e SaaS)
   - Verificar filtros (Pai/Filho, dropdown, lista)
5. Validar importação/exportação:
   - XML (esquemas)
   - MD (categorias)
   - ZIP (mídia)

Se qualquer alteração violar uma das regras acima, ela deve ser revista antes de entregar a nova versão.
