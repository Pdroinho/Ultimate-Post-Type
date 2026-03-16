# UPT - Catálogo Front-End

**Versão Atual:** V20.7.32-wizard

Gerenciador de catálogos completo para WordPress com submissão e gerenciamento via front-end, integração completa com Elementor e sistema avançado de galeria de mídia.

## Características Principais

- Sistema completo de catálogo com categorias hierárquicas
- Integração nativa com Elementor (10 widgets + 5 tags dinâmicas)
- Galeria de mídia organizada por taxonomias
- Presets de design (Hostinger e SaaS)
- Importação/Exportação de esquemas (XML), categorias (MD) e mídia (ZIP)
- Sistema de cache e performance otimizado
- Conversão automática para WebP
- Rastreio de cliques e analytics

## Instalação

1. Faça o upload da pasta `ultimate-post-type` para `/wp-content/plugins/`
2. Ative o plugin através do menu "Plugins" no WordPress
3. O plugin criará automaticamente as páginas necessárias

## Requisitos

- WordPress 5.0 ou superior
- PHP 7.4 ou superior
- Elementor 3.0 ou superior (para widgets Elementor)

## Funcionalidades

### Sistema de Catálogo

- Custom Post Type `upt_item` para itens do catálogo
- Taxonomia `catalog_category` hierárquica
- Custom Post Type `upt_schema` para esquemas de catálogo
- Suporte a campos personalizados
- Upload de múltiplas imagens
- PDFs e vídeos
- Itens relacionados

### Widgets Elementor

#### 1. UPT_Dashboard_Widget
Painel principal de catálogo com:
- Presets Hostinger e SaaS
- Layout responsivo com sidebar
- Suporte a abas e galeria
- Filtros integrados
- Personalização avançada de cores e fontes

#### 2. UPT_Listing_Widget
Grade de itens do catálogo com:
- Templates customizados
- Filtros embutidos
- Paginação AJAX
- Modo Link/GET para SEO

#### 3. UPT_Category_Filter_Widget
Filtro de categorias com:
- Layouts: lista e dropdown
- Modo Pai/Filho para hierarquias
- Alinhamento justificado
- Ocultar categorias vazias

#### 4. UPT_Product_Gallery_Widget
Galeria de produtos com:
- Imagem principal
- Navegação por setas
- Thumbnails interativos

#### 5. UPT_Search_Widget
Campo de busca com:
- Configuração de filtros
- Personalização de botão e input

#### 6. UPT_WhatsApp_Button_Widget
Botão WhatsApp com:
- Configuração de número e mensagem
- Estilização completa

#### 7. UPT_Dashboard_Actions_Widget
Ações do painel com:
- Botões de ação customizados

#### 8. UPT_Text_Editor_Widget
Editor de texto com:
- Configuração de conteúdo

#### 9. UPT_Video_Widget
Widget de vídeo com:
- Configuração de player

### Tags Dinâmicas Elementor

1. **custom-field-tag** - Campos personalizados
2. **dashboard-data-tag** - Dados do painel
3. **gallery-field-tag** - Campos de galeria
4. **pdf-field-tag** - Campos PDF
5. **related-item-data-tag** - Dados de itens relacionados
6. **video-field-tag** - Campos de vídeo

### Galeria de Mídia

- Organização por taxonomias
- Suporte a imagens e vídeos
- Seleção múltipla
- Exportação seletiva
- Upload via drag and drop
- Busca avançada

### Administração

- Painel admin com filtros
- Presets customizáveis
- Botão "Visualizar todos"
- Rastreio de cliques
- Dashboard de analytics

### Importação/Exportação

- **Esquemas**: Importação/Exportação via XML
- **Categorias**: Importação/Exportação via Markdown
- **Mídia**: Importação/Exportação via ZIP

## Uso

### Criando um Catálogo

1. Acesse "Catálogo > Esquemas"
2. Clique em "Adicionar Novo"
3. Configure os campos do esquema
4. Defina as categorias
5. Comece a adicionar itens

### Usando Widgets Elementor

1. Abra o Elementor
2. Arraste os widgets UPT para sua página
3. Configure as opções
4. Ajuste o estilo conforme necessário

### Gerenciando a Galeria

1. Acesse "Catálogo > Galeria"
2. Crie pastas para organizar
3. Faça upload de imagens/vídeos
4. Exporte mídia quando necessário

## Presets

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

## Desenvolvimento

### Estrutura de Arquivos

```
ultimate-post-type/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── gallery.css
│   ├── js/
│   │   ├── admin.js
│   │   ├── editor.js
│   │   ├── front.js
│   │   ├── gallery.js
│   │   ├── media-folders.js
│   │   ├── taxonomy-categories.js
│   │   ├── taxonomy-categories-metabox.js
│   │   └── upt-gallery.js
│   └── img/
│       └── 1x1.png
├── includes/
│   ├── class-admin.php
│   ├── class-ajax.php
│   ├── class-auth.php
│   ├── class-cache.php
│   ├── class-cpt.php
│   ├── class-form-handler.php
│   ├── class-image-webp.php
│   ├── class-media-folders.php
│   ├── class-roles.php
│   ├── class-schema-store.php
│   ├── class-shortcodes.php
│   ├── class-taxonomies.php
│   └── elementor/
│       ├── class-elementor.php
│       ├── tags/
│       │   ├── custom-field-tag.php
│       │   ├── dashboard-data-tag.php
│       │   ├── gallery-field-tag.php
│       │   ├── pdf-field-tag.php
│       │   ├── related-item-data-tag.php
│       │   └── video-field-tag.php
│       └── widgets/
│           ├── category-filter-widget.php
│           ├── dashboard-actions-widget.php
│           ├── dashboard-widget.php
│           ├── listing-widget.php
│           ├── product-gallery-widget.php
│           ├── search-widget.php
│           ├── text-editor-widget.php
│           ├── video-widget.php
│           └── whatsapp-button-widget.php
├── templates/
├── UPT_IA_RULES.md
├── FUTURE_PLANS.md
└── ultimate-post-type.php
```

### Classes Principais

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

## Hooks e Filtros

### Actions Disponíveis

- `upt_before_item_save`
- `upt_after_item_save`
- `upt_before_category_save`
- `upt_after_category_save`

### Filtros Disponíveis

- `upt_item_fields`
- `upt_category_fields`
- `upt_allowed_mime_types`
- `upt_gallery_image_size`

## Suporte

Para suporte e documentação adicional, consulte:

- [UPT_IA_RULES.md](file:///c:/Users/henry/Downloads/FrontCat-V20.7.32-wizard/ultimate-post-type/UPT_IA_RULES.md) - Regras oficiais para desenvolvimento
- [FUTURE_PLANS.md](file:///c:/Users/henry/Downloads/FrontCat-V20.7.32-wizard/ultimate-post-type/FUTURE_PLANS.md) - Roadmap e planos futuros

## Changelog

### V20.7.32-wizard
- Filtro de categorias com alinhamento justificado
- AJAX para carregamento de subcategorias
- Importação/Exportação de categorias via Markdown

### V20.7.x
- Múltiplas correções e melhorias de performance

## Licença

Este plugin é licenciado sob os termos da licença GPL v2 ou posterior.

## Créditos

Desenvolvido por Pedro

## Roadmap

Consulte [FUTURE_PLANS.md](file:///c:/Users/henry/Downloads/FrontCat-V20.7.32-wizard/ultimate-post-type/FUTURE_PLANS.md) para ver os planos futuros de desenvolvimento.
