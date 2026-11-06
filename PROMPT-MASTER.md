# PROMPT MASTER — Os Reviteiros Laravel

Cole este prompt ao iniciar uma nova sessão no Claude Code para continuar o desenvolvimento.

---

## Contexto do Projeto

Estou migrando o site **Os Reviteiros** (https://osreviteiros.com.br) de WordPress + JetEngine para **Laravel 11**.

O site é uma plataforma de download de **famílias Revit BIM** para arquitetos e engenheiros.

### Stack alvo
- Laravel 11
- Livewire 3
- Alpine.js (micro-interações, tooltips, dropdowns)
- Tailwind CSS
- MySQL

### Regras absolutas
1. **Nunca usar jQuery** — fetch nativo ou Alpine.js
2. **Prefixo `rv-`** em todas as classes CSS customizadas
3. **Filtros de categoria são SEMPRE acumulativos** (AND logic em todos os níveis ativos)
4. **`link_download` nunca é exposto sem verificar permissão** do usuário
5. Controllers finos — lógica nos Services
6. Sem TypeScript

---

## Domínio

### Hierarquia de categorias (4 níveis)

```
categorias (nível 1)
  └── subcategorias (nível 2)
        └── subclassificacao (nível 3)
              └── selecionar-marca (nível 4) = fabricante
```

Deve ser possível filtrar apenas pela categoria selecionar-marca (fabricante). E as buscas do site deve ser capaz de localizar a família, textura ou projeto por qualquer termo relacionada a ela, assim como pelo termo de qualquer nível de categoria.

Filtros via URL params: `?cat=x&sub=y&subcl=z&marca=w`
Todos acumulativos — ao clicar em subcategoria, manter filtro da categoria pai, com exceção da tela de marcar que precisa ser criada, que deve filtrar todas as famílias relacionadas aquela marca e depois pode filtrar pelas outras categorias, se o usuário quiser.

### Planos de acesso

| Plano | Permissão |
|---|---|
| Grátis | Só famílias com tag `acessar-gratis` |
| Basic / Plus / Premium | Todas as famílias |

### Download (4 estados de botão)

- Não logado → botão "Baixar Grátis" abre popup de cadastro
- Logado grátis + família paga → botão "Assinar" abre popup de upgrade  
- Logado grátis + família gratuita (`acessar-gratis`) → botão "Baixar" direto
- Logado pago → botão "Baixar" direto sempre

---

## Design

### Cores

```
--rv-azul-escuro: #042C53   (header, fundo principal)
--rv-azul-claro:  #1A6FC4   (botões primários)
--rv-dourado:     #C9A227   (acento, CTA premium, logo)
--rv-cinza-bg:    #F5F7FA   (fundo da página)
```

### Tipografia: Inter (Google Fonts)

### Grid de famílias
```css
grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
gap: 16px;
```

### Ícones: Tabler Icons CDN
```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
```

---

## Referências visuais

Veja as telas em `docs/screens/`:
- `screenshot-1.png` — Grade principal de famílias
- `screenshot-2.png` — Detalhe dos cards e botões
- `screenshot-3.png` — Vista geral
- `logo.png` — Logo Os Reviteiros (capacete dourado + texto azul)

---

## Próximos passos de desenvolvimento

### Fase 1 — Setup base

```bash
laravel new osreviteiros --jet --stack=livewire
cd osreviteiros
composer require livewire/livewire
npm install -D tailwindcss alpinejs
php artisan migrate
```

### Fase 2 — Models e Migrations

Criar na ordem:
1. `planos` (seed com os 4 planos)
2. `fabricantes`
3. `categorias` (árvore com parent_id)
4. `familias` + pivot `familia_categoria`
5. Adicionar `plano_id` à tabela `users`

### Fase 3 — Controllers e API

1. `ApiFamiliaController@index` — endpoint paginado para infinite scroll
2. `FamiliaController@index` — view com PHP server-side rendering dos primeiros cards
3. `FabricanteController@index` — grid de fabricantes
4. `DownloadController@download` — rota protegida que valida permissão

### Fase 4 — Frontend

1. CSS com design tokens (`rv-*`)
2. JavaScript do infinite scroll + filtros acumulativos
3. Tooltip de imagem 500×500
4. Popup de planos com Alpine.js

### Fase 5 — Importação de dados WP

```bash
php artisan reviteiros:importar-categorias --xml=wp-export.xml
php artisan reviteiros:importar-fabricantes --xml=wp-export.xml
php artisan reviteiros:importar-familias    --xml=wp-export.xml
php artisan reviteiros:importar-usuarios    --xml=wp-export.xml
```

### Fase 6 — Integrações

1. Webhook Hotmart → atualiza `plano_id` do usuário
2. Webhook Eduzz → mesma lógica para plano grátis
3. SEO: meta description, og:tags, rel="next/prev"

---

## Arquivos de referência neste pacote

```
CLAUDE.md                          ← Regras completas do projeto (leia sempre)
docs/specs/familias.md             ← Model, Controller, API de famílias
docs/specs/usuarios-planos.md      ← Model User, planos, popup, webhooks
docs/specs/categorias-fabricantes.md ← Hierarquia, árvore de categorias
docs/specs/api.md                  ← Documentação completa da API REST
docs/specs/infinite-scroll.md      ← JS completo do scroll + filtros
docs/design/tokens.md              ← Design tokens CSS completos
docs/design/telas.md               ← Wireframes ASCII de todas as telas
docs/screens/                      ← Screenshots reais do site atual
.claude/mcp-config.json            ← MCP servers recomendados
.claude/agents/review-agent.md     ← Checklist de revisão de código
.claude/commands/                  ← Comandos rápidos (/novo-componente, /importar-wp)
```

---

## Contexto adicional

- Site atual: https://osreviteiros.com.br
- CPT WordPress original: `familias` (plural)
- URL das famílias no WP: `/familias-revit/`
- Imagens hosted no servidor WP — migrar para S3 ou local na Fase 5
- Usuários têm senhas em hash phppass (WP) — forçar reset após migração
