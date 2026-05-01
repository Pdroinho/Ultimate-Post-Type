# UPT - Ultimate Post Type

**Plugin WordPress para criação de catálogos dinâmicos com gestão front-end completa.**

[![Versão](https://img.shields.io/badge/versão-V20.7.35--wizard-blue)](https://github.com/Pdroinho/Ultimate-Post-Type)
[![WordPress](https://img.shields.io/badge/WordPress-6.x%2B-blue)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)](https://php.net/)
[![Licença](https://img.shields.io/badge/licença-GPL%20v2-green)](./LICENSE)

---

## Visão Geral

O **UPT (Ultimate Post Type)** transforma o WordPress em uma plataforma completa de catálogos. Com ele, você cria esquemas personalizados de dados (imóveis, veículos, produtos, serviços), gerencia categorias hierárquicas, importa dados via XML, e renderiza tudo com widgets Elementor totalmente personalizáveis — incluindo um **Card Builder visual** com drag-and-drop.

### Casos de uso

- Portais imobiliários com filtros por tipo (Aluguel/Venda) e categoria (Casa/Apartamento)
- Catálogos de veículos com busca avançada
- Listagens de produtos e serviços
- Qualquer tipo de diretório ou catalogação

---

## Funcionalidades Principais

| Feature | Descrição |
|---------|-----------|
| **Schema Builder** | Criação visual de esquemas com campos customizáveis (text, number, image, video, pdf, gallery, wysiwyg, date) |
| **Card Builder Visual** | Personalização de cards via drag-and-drop com preview em tempo real |
| **Importação XML** | Upload e processamento em lotes com progress bar, retomada automática e anti-SSRF |
| **CRON Import** | Importação automática periódica de feeds XML |
| **Filtros AJAX** | Busca em tempo real, categorias parent/child, meta filters |
| **Dashboard SAAS** | Front-end completo com login, tabs, CRUD, gráficos Chart.js, galeria |
| **Galeria de Mídia** | Seleção múltipla com pastas organizacionais, preview inline |
| **Conversão WebP** | Conversão automática de imagens no upload |
| **Cache Inteligente** | Transients para queries pesadas |
| **9 Widgets Elementor** | Grid, Dashboard, Filtros, Busca, Galeria, Vídeo, WhatsApp e mais |
| **6 Tags Dinâmicas** | Campos customizados, galeria, PDF, vídeo, items relacionados |
| **Filtros Hierárquicos** | Parent/Child com alinhamento justificado |

---

## Requisitos

| Requisito | Versão |
|-----------|--------|
| WordPress | 6.0+ |
| PHP | 7.4+ |
| Elementor | 3.0+ |
| jQuery | (incluído no WordPress) |

---

## Instalação

### Via Git (recomendado para desenvolvimento)

```bash
# Clone o repositório
git clone https://github.com/Pdroinho/Ultimate-Post-Type.git

# Copie para a pasta de plugins (ou crie um symlink)
cp -r Ultimate-Post-Type /path/to/wordpress/wp-content/plugins/ultimate-post-type

# Ou via symlink (recomendado para desenvolvimento)
ln -s /path/to/Ultimate-Post-Type /path/to/wordpress/wp-content/plugins/ultimate-post-type
```

### Via ZIP

1. Baixe o ZIP do repositório
2. Extraia para `/wp-content/plugins/ultimate-post-type/`
3. Ative o plugin no painel WordPress em **Plugins → Ativar**

### Para desenvolvimento (autocomplete no IDE)

```bash
composer install
```

Isso instala os WordPress stubs para autocomplete no Intelephense/PHPStorm.

---

## Início Rápido

### 1. Criando um Esquema

1. Acesse **Catálogo → Esquemas** no painel admin
2. Clique em **"Adicionar Novo"**
3. Defina nome e campos (text, number, image, video, pdf, gallery, wysiwyg, date)
4. Configure categorias hierárquicas

### 2. Usando os Widgets Elementor

1. Abra uma página com o **Elementor**
2. Arraste o widget **"UPT Listing (Grid)"** para a página
3. Selecione o esquema e configure filtros, paginação e card builder
4. Adicione o widget **"UPT Category Filter"** para filtros

### 3. Card Builder Visual

1. No widget Listing, ative o **Card Builder**
2. No painel `/painel` do front-end, vá em **"Card Settings"**
3. Arraste os campos, configure cores, tamanhos e pesos
4. Preview em tempo real e salve via AJAX

### 4. Importação XML

1. No painel admin, vá em **Catálogo → Importador**
2. Ou no front-end `/painel`, aba **"Importar XML"**
3. Faça upload do XML e acompanhe o processamento em lotes
4. Configure CRON para importação automática

---

## Widgets Elementor

### Grid de Catálogo (`UPT_Listing_Widget`)
- Grid responsivo com paginação AJAX, infinite scroll ou números
- Filtros por categoria, busca e meta fields
- Card Builder integrado com renderização inline
- Modo AJAX ou Link/GET (SEO-friendly)

### Dashboard SAAS (`UPT_Dashboard_Widget`)
- Dashboard completo com tabs, gráficos Chart.js
- CRUD de itens com formulário, galeria e categorias
- Importação XML com drag-and-drop
- Login e registro de usuários integrados
- Presets: Hostinger e SAAS

### Filtro de Categorias (`UPT_Category_Filter_Widget`)
- Filtro hierárquico Parent → Child
- Layouts: horizontal, vertical, dropdown, justificado
- Ocultar categorias vazias automaticamente
- Carregamento de subcategorias via AJAX

### Busca (`UPT_Search_Widget`)
- Busca com targets (campos específicos do esquema)
- Modo AJAX (tempo real) ou Link/GET
- Debounce configurável

### Galeria de Produto (`UPT_Product_Gallery_Widget`)
- Imagem principal + thumbnails
- Navegação por setas
- Lightbox integrado

### Outros Widgets
- **WhatsApp Button** — botão com número dinâmico
- **Video Player** — player de vídeo integrado
- **Text Editor** — editor de texto inline
- **Dashboard Actions** — ações contextuais

---

## Tags Dinâmicas Elementor

| Tag | Descrição |
|-----|-----------|
| `custom-field-tag` | Renderiza campos personalizados do esquema |
| `dashboard-data-tag` | Dados do dashboard |
| `gallery-field-tag` | Campos de galeria de imagens |
| `pdf-field-tag` | Campos PDF |
| `related-item-data-tag` | Dados de itens relacionados |
| `video-field-tag` | Campos de vídeo |

---

## Arquitetura

### Estrutura de Diretórios

```
ultimate-post-type/
├── ultimate-post-type.php          # Entry point + changelog
├── uninstall.php                   # Cleanup completo ao desinstalar
├── composer.json                   # WordPress stubs (dev)
├── wp-constants.php                # Constantes WP para IDE
│
├── includes/                       # Classes PHP (~20.200 linhas)
│   ├── class-admin.php             # Menus admin, enqueue condicional (~5.600 linhas)
│   ├── class-ajax.php              # 45+ endpoints AJAX (~3.400 linhas)
│   ├── class-imobiliaria-importer.php # Importação XML (911 linhas)
│   ├── class-auth.php              # Login/registro front-end
│   ├── class-cache.php             # Sistema de transients
│   ├── class-cpt.php               # Custom Post Type: catalog_item
│   ├── class-taxonomies.php        # catalog_schema, catalog_category, media_folder
│   ├── class-schema-store.php      # CRUD de esquemas
│   ├── class-media-folders.php     # Pastas virtuais de mídia
│   ├── class-image-webp.php        # Conversão WebP automática
│   ├── class-roles.php             # Capacidades customizadas
│   ├── class-shortcodes.php        # Shortcodes
│   ├── class-card-premium-actions.php # Favoritos e badges
│   ├── card-premium-render.php     # Renderização de card premium
│   ├── admin-templates/            # Templates PHP das páginas admin
│   │   ├── gallery.php             # Galeria de mídia
│   │   ├── importer.php            # Importação/exportação XML/JSON
│   │   ├── schema-builder.php      # Construtor de esquemas
│   │   ├── unused-media.php        # Mídias não usadas
│   │   └── about.php               # Sobre o plugin
│   └── elementor/
│       ├── class-elementor.php     # Registro de widgets e tags
│       ├── widgets/                # 9 widgets
│       └── tags/                   # 6 tags dinâmicas
│
├── assets/
│   ├── css/                        # CSS modular (~8.700 linhas)
│   │   ├── front.css               # Agregador front-end (10 módulos)
│   │   ├── admin.css               # Agregador admin (11 módulos)
│   │   ├── gallery.css             # Galeria (1.634 linhas)
│   │   ├── card-premium.css        # Card premium
│   │   └── admin/                  # 11 módulos por feature
│   │       ├── variables.css       # Custom properties
│   │       ├── admin-base.css      # Schema builder, forms
│   │       ├── preset-hostinger.css # Layout, cards, submissions
│   │       ├── dashboard.css       # Tabs, login, pills
│   │       ├── editor.css          # WYSIWYG editor
│   │       ├── form-fixes.css      # Correções de formulário
│   │       ├── components.css      # Alerts, dialogs, bulk UI
│   │       ├── preset-saas.css     # Layout SAAS
│   │       ├── import-wizard.css   # Wizard, stepper, CRON
│   │       ├── card-builder.css    # Card builder sortable
│   │       └── utilities.css       # Classes utilitárias (loading, status)
│   └── js/                         # JavaScript (~9.900 linhas)
│       ├── front.js                # Listing, filtros, forms (6.169 linhas)
│       ├── gallery.js              # Galeria modal (2.146 linhas)
│       ├── admin.js                # Schema builder admin
│       ├── editor.js               # WYSIWYG standalone
│       ├── card-premium.js         # Card premium
│       ├── media-folders.js        # Pastas de mídia
│       ├── upt-gallery.js          # Galeria admin
│       ├── taxonomy-categories.js  # Ordenação de categorias
│       ├── taxonomy-categories-metabox.js
│       └── admin/                  # Módulos extraídos
│           ├── dashboard.js        # Chart.js, modals, filtros
│           ├── import-wizard.js    # Dropzone, stepper, batch
│           └── card-builder.js     # Builder sortable/editor
│
├── templates/
│   ├── dashboard-main.php          # Dashboard SAAS (2.655 linhas)
│   ├── front-form-login.php        # Formulário de login
│   └── front-form-submit.php       # Formulário de submissão
│
└── languages/
    └── ultimate-post-type-pt_BR.po # Tradução PT-BR
```

### Classes Principais

| Classe | Responsabilidade |
|--------|-----------------|
| `UPT_Admin` | Menus admin, enqueue condicional por página, CRUD schemas |
| `UPT_Ajax` | 45+ endpoints AJAX centralizados |
| `UPT_Imobiliaria_Importer` | Parser XML, mapeamento de campos, download de fotos |
| `UPT_Auth` | Login/registro front-end via `wp_signon()` |
| `UPT_CPT` | Registro do CPT `catalog_item` |
| `UPT_Taxonomies` | 3 taxonomias: `catalog_schema`, `catalog_category`, `media_folder` |
| `UPT_Schema_Store` | CRUD de esquemas via WordPress options |
| `UPT_Media_Folders` | Pastas virtuais para organização de mídia |
| `UPT_Image_WebP` | Conversão automática WebP no upload |
| `UPT_Cache` | Wrapper de transients |
| `UPT_Elementor` | Registro de widgets e tags dinâmicas |

### Taxonomias e Post Types

| Tipo | Slug | Hierárquica | Descrição |
|------|------|-------------|-----------|
| `catalog_item` (CPT) | — | — | Post type principal do catálogo |
| `catalog_schema` | — | Sim | Define esquemas de campos (ex: Imóvel, Veículo) |
| `catalog_category` | — | Sim | Categorias com parent/child (ex: Aluguel > Casa) |
| `media_folder` | — | Sim | Pastas virtuais para organizar mídia |

---

## API AJAX

O plugin expõe **45+ endpoints** via WordPress AJAX API.

### Endpoints Públicos (nopriv)

| Endpoint | Descrição |
|----------|-----------|
| `upt_live_search` | Busca/filtro AJAX com paginação |
| `upt_public_get_child_categories` | Subcategorias públicas |

### Endpoints Autenticados

| Categoria | Endpoints |
|-----------|-----------|
| **CRUD Itens** | `save_item`, `delete_item`, `bulk_delete_items`, `get_all_item_ids`, `reorder_items` |
| **Busca/Filtro** | `live_search`, `filter_items`, `get_schema_counts` |
| **Categorias** | `add_category`, `update_category`, `delete_category`, `rename_category`, `get_child_categories` |
| **Esquemas** | `add_schema_option`, `delete_schema_option`, `rename_schema_option`, `reorder_fields` |
| **Drafts** | `save_draft`, `get_draft`, `clear_draft` |
| **Galeria** | `gallery_get_folders`, `gallery_get_images`, `gallery_delete_image`, `get_media_by_ids` |
| **Mídia** | `create_media_folder`, `assign_to_folder`, `remove_from_folder`, `delete_media_folder`, `rename_media_folder`, `move_media_folder` |
| **XML Import** | `imob_count`, `imob_upload`, `imob_batch`, `imob_status`, `imob_cancel` |
| **Card Builder** | `save_card_settings` |
| **CRON** | `save_cron_config`, `test_cron_import` |

---

## Segurança

- `check_ajax_referer()` com nonce em todos os endpoints AJAX
- `sanitize_text_field()` e `absint()` em todos os inputs
- `wp_verify_nonce()` em formulários front-end
- Anti-SSRF na importação XML (bloqueia IPs privados/locais)
- Validação de permissões com `current_user_can()` em ações sensíveis

---

## Importação XML de Imobiliárias

O sistema de importação suporta feeds XML padrão de imobiliárias:

- **Upload** com drag-and-drop e validação de arquivo
- **Processamento em lotes** via AJAX (N imóveis por requisição)
- **Barra de progresso** com contadores (total, processados, importados, fotos, erros)
- **Retomada automática** — re-importação preserva galeria e thumbnail existentes
- **Cancelamento** — botão para interromper a qualquer momento
- **CRON** — importação automática periódica configurável
- **Anti-SSRF** — bloqueia download de imagens de IPs privados/locais
- **Mapeamento automático** — categorias criadas a partir de TipoImóvel + Cidade
- **Campos automáticos** — Status (Venda/Aluguel), Preço formatado (R$), Empreendimento

---

## Desenvolvimento

### Padrões de Código

- **PHP:** Classes estáticas com `public static function`, WordPress hooks API
- **JavaScript:** IIFE jQuery `(function($){...})(jQuery);`, módulos separados por feature
- **CSS:** Prefixo `upt-`, custom properties em `variables.css`, módulos por feature
- **Segurança:** Nonce verification + sanitization em todos os endpoints
- **Cache-busting:** `filemtime()` em todos os `wp_enqueue_script/style`
- **Carregamento condicional:** CSS/JS carregam apenas nas páginas que precisam

### Setup do Ambiente

```bash
# Clone o repositório
git clone https://github.com/Pdroinho/Ultimate-Post-Type.git

# Instale as dependências de desenvolvimento (IDE stubs)
cd Ultimate-Post-Type
composer install

# Crie um symlink para o WordPress
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/ultimate-post-type
```

### Métricas do Projeto

| Métrica | Valor |
|---------|-------|
| Total de código | ~42.750 linhas |
| PHP | ~20.200 linhas (35 arquivos) |
| JavaScript | ~9.900 linhas (12 arquivos) |
| CSS | ~8.700 linhas (15 arquivos) |
| Templates admin | ~1.147 linhas (5 templates) |
| Endpoints AJAX | 45+ |
| Widgets Elementor | 9 |
| Tags dinâmicas | 6 |

---

## Presets de Design

### Preset Hostinger (Padrão)
- Cor principal: `#673DE6`
- Design otimizado para infraestrutura
- Cards, submissions, modais

### Preset SAAS
- Cor principal: `#6366f1`
- Sidebar escura: `#111827`
- Header branco: `#ffffff`
- Conteúdo: `#f1f5f9`
- Cards: `#ffffff`
- Logo customizável

---

## Changelog

### V20.7.35-wizard
- Importação XML: categorias automáticas a partir de TipoImóvel + Cidade
- Importação XML: campo Status do Imóvel (Venda / Aluguel / Venda e Aluguel / Consulte)
- Importação XML: campos PrecoLocacao e PrecoLocacaoTemporada importados
- Importação XML: campo Empreendimento importado
- Importação XML: preços formatados como moeda brasileira (R$ 410.000,00)
- Importação XML: sync incremental — re-importação preserva galeria e thumbnail
- Importação XML: importação disponível no /painel (tab "Importar XML")
- Card Builder visual com drag-and-drop
- Filtros parent/child respeitando hierarquia (correção)
- Refatoração front-end: CSS dividido em 10 módulos, JS extraído em 3 módulos
- Carregamento condicional de CSS/JS por página admin
- Limpeza de código: removidos ~1.900 linhas de código morto/redundante
- Templates admin: HTML extraído para 5 templates PHP separados (admin-templates/)
- AJAX: métodos privados reutilizáveis (build_table_response_args, sanitize_field_value)
- CSS utilitário: classes .upt-loading, .upt-status-error migradas do JS para CSS
- Bug fix: is_upt_request() com case sensitivity (sanitize_key converte para lowercase)
- Bug fix: filtro de categoria hierárquica (parent_term_id no tax_query)

### V20.7.34-wizard
- Importação XML: reescrita completa para processamento em lotes via AJAX
- Barra de progresso visual com contadores
- Retomada automática e cancelamento
- Anti-SSRF: bloqueia download de imagens de IPs privados

### V20.7.30-wizard
- Filtro de Categorias: modos Pai/Filho para encadear filtros
- Endpoint público para carregar subcategorias via AJAX

### V20.7.24-wizard
- Grade do Catálogo: include_children=true ao filtrar por categoria pai

---

## Licença

Este plugin é licenciado sob os termos da licença **GPL v2** ou posterior.

---

## Créditos

Desenvolvido por **Pedro** e **Matheus**.
