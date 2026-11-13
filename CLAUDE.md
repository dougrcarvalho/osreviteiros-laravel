# Os Reviteiros — Projeto Laravel

## Visão Geral

Plataforma de download de famílias Revit BIM para profissionais de arquitetura e engenharia.  
Migração do WordPress (Hello Elementor + JetEngine) para Laravel 13 + Livewire 3 + Alpine.js.

**URL produção:** https://osreviteiros.com.br  
**Stack:** Laravel 13 · Livewire 3 · Alpine.js (embutido no Livewire) · Tailwind CSS 4 · MySQL 8.4 · PHP 8.5 (Sail)

> Laravel 11 foi descartado: está fora do suporte de segurança e o Composer bloqueia a instalação por falhas sem correção.

---

## Domínio de Negócio

### Entidades principais

| Entidade | Descrição |
|---|---|
| **Familia** | Arquivo Revit BIM para download (CPT `familias` no WP) |
| **Categoria** | Taxonomia de 4 níveis hierárquicos |
| **Fabricante** | Marca/fabricante associada a famílias |
| **Plano** | Nível de acesso do usuário |
| **Usuario** | Comprador/assinante da plataforma |

### Bibliotecas e taxonomias (classificações configuráveis)

O catálogo tem 3 tipos de item (`TipoItem`), cada um com URL e **taxonomias próprias** — cadastradas em `taxonomias`, não fixas no código:

| Biblioteca | URL | Taxonomias (slug = parâmetro da URL) |
|---|---|---|
| Famílias | `/familias-revit` | categoria (`cat`) → subcategoria (`sub`) → subclassificação (`subcl`); marca (`marca`) |
| Texturas | `/texturas-revit-bim` | categoria (`cat`); marca (`marca`) |
| Projetos | `/projetos-prontos-revit-bim` | categoria (`cat`); tipo (`tipo`) |

```
/familias-revit?cat=automoveis&sub=carros&subcl=suv&marca=toyota
/texturas-revit-bim?cat=pisos&marca=portobello
/projetos-prontos-revit-bim/page/2/?cat=residencial&tipo=casa
```

**Paginação SEO do infinite scroll**: cada bloco de 48 itens é uma página com URL própria (`/familias-revit/page/3/`, formato do WordPress, com barra final). A página renderiza no servidor só os itens dela, com `canonical`, `rel=prev/next` e links `<a>` rastreáveis. Conforme o usuário rola, a URL troca sozinha para a página visível (`history.replaceState`, sem criar histórico). Aberta direto no meio, a grade começa naquela página e oferece "Carregar resultados anteriores". `/page/1/` → 301 para a base; página além da última → 404. URLs antigas (`/familias`, `/texturas`, `/projetos`) → 301.

Todos os filtros são **acumulativos** (AND logic). Uma taxonomia com *pai* só aparece depois que o pai é escolhido; sem pai, aparece desde o início (faceta independente). **Para criar uma classificação nova (ex.: "Cor" em Famílias): Painel → Administração → Taxonomias** — ela passa a existir no formulário do item, nos filtros (`?cor=azul`), na busca e na API, sem alterar código. Detalhes em `docs/specs/taxonomias.md`.

### Planos de Acesso

| Plano | Slug WP | Permissão |
|---|---|---|
| Grátis | `usurio_grtis` | Só itens gratuitos (`familias.gratuita`) |
| Basic | `reviteiroa_basic` | Famílias e texturas |
| Plus | `reviteiro_plus` | **Descontinuado** — tratado como Basic e fora do popup |
| Premium | `reviteiro_premium` | Famílias, texturas, projetos prontos (+ cursos) |

Regra em `Plano::liberaTipo()` (usada por `DownloadService` via `User::planoLibera()`).

**Regra de download:**
- Não logado → abre popup de cadastro
- Item gratuito → qualquer conta logada baixa
- Logado grátis + item pago → popup de upgrade (estado `assinar`)
- Basic/Plus + projeto → popup só com o Premium (estado `upgrade`)
- Plano que cobre o tipo do item (ou funcionário) → permite download

---

## Arquitetura Laravel

### Estrutura de pastas (convenções do projeto)

```
app/
  Models/
    Familia.php
    Categoria.php
    Fabricante.php
    Plano.php
    User.php (extend Authenticatable)
  Http/
    Controllers/
      FamiliaController.php
      FabricanteController.php
      AuthController.php
    Livewire/
      FamiliaGrid.php       ← grid infinito principal
      FiltroCategoria.php   ← navegação hierárquica
      PopupCadastro.php     ← registro + planos
  Services/
    DownloadService.php     ← lógica de acesso ao link
    PlanoService.php        ← verificações de plano

resources/
  views/
    layouts/
      app.blade.php
    familias/
      index.blade.php
      _card.blade.php
    fabricantes/
      index.blade.php
      _card.blade.php
    auth/
      register.blade.php
    components/
      popup-planos.blade.php

database/
  migrations/
    *_create_familias_table.php
    *_create_categorias_table.php
    *_create_fabricantes_table.php
    *_create_planos_table.php
```

### Rotas principais

```php
// Bibliotecas (uma por TipoItem): /familias-revit, /texturas-revit-bim, /projetos-prontos-revit-bim
GET  /{biblioteca}                → FamiliaController@index
GET  /{biblioteca}/page/{n}/      → FamiliaController@index (paginação SEO; ListagemSeoService)
GET  /{biblioteca}/{slug}         → FamiliaController@show (301 se o item é de outra biblioteca)
POST /familias/{id}/download      → DownloadController@download (qualquer tipo; auth gate)

// API para infinite scroll
GET  /api/familias                → ApiFamiliaController@index
  params: page, per_page, biblioteca (familia|textura|projeto), gratis, marca, busca, orderby
          + o slug de qualquer taxonomia da biblioteca (cat, sub, subcl, cor, tipo…)
GET  /api/categorias/opcoes       → opções de uma taxonomia no contexto dos filtros (biblioteca, taxonomia, + filtros)

// Fabricantes (todas as bibliotecas; abas escolhem a biblioteca)
GET  /fabricantes                 → FabricanteController@index
GET  /fabricantes/{slug}          → FabricanteController@show (+ /page/{n}/)

// Auth
GET  /cadastro                    → AuthController@showRegister
POST /cadastro                    → AuthController@register
```

---

## Design System

### Paleta de Cores

```css
--rv-azul-escuro:    #042C53;   /* fundo hero, header */
--rv-azul-medio:     #0A4A8A;   /* hover estados */
--rv-azul-claro:     #1A6FC4;   /* botões primários */
--rv-dourado:        #C9A227;   /* acento, logo, badges */
--rv-cinza-bg:       #F5F7FA;   /* fundo geral */
--rv-cinza-card:     #FFFFFF;   /* cards */
--rv-cinza-borda:    #E2E8F0;   /* bordas */
--rv-texto-escuro:   #1A202C;   /* headings */
--rv-texto-medio:    #4A5568;   /* corpo */
--rv-texto-claro:    #718096;   /* captions */
```

### Tipografia

- **Font:** Inter (Google Fonts)
- **Heading:** 700, scale: 2xl / xl / lg
- **Body:** 400/500, 14-16px

### Ícones

- Tabler Icons via CDN: `https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css`
- Prefixo: `ti ti-{nome}`

### Grid de Famílias

```css
display: grid;
grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
gap: 16px;
```

### Card de Família

- Imagem: 220px altura (objeto-cover)
- Hover na imagem: tooltip 500×500px
- Botão de download: 4 estados (baixar / cadastrar / assinar / upgrade)

---

## Regras de Desenvolvimento

### Sempre

- Usar **Livewire** para componentes reativos (grid, filtros, popup)
- Usar **Alpine.js** para micro-interações (tooltip, dropdown)
- Prefixar classes CSS customizadas com `rv-`
- **Infinite scroll** via IntersectionObserver (não jQuery)
- Filtros de categoria são **acumulativos** em todos os níveis

### Nunca

- Não usar jQuery
- Não criar rotas para o admin (usar Laravel Nova ou Filament separado)
- Não expor `link_download` para usuários sem permissão via API

### Padrões de código

- Models com relacionamentos e scopes definidos
- Controllers finos — lógica nos Services
- Validação via Form Requests
- API retorna JSON com estrutura: `{ data, meta: { total, per_page, current_page, last_page } }`

---

## Integração de Pagamento

- **Hotmart:** planos Basic e Premium
- **Eduzz:** plano Grátis (cadastro direto)
- Após pagamento: webhook atualiza `users.plan_id` no banco

---

## SEO

- URL amigáveis: `/familias/nome-da-familia`
- Meta description gerada dinamicamente
- `rel="next/prev"` no head para paginação
- OG tags nas páginas de família

---

## Dados do WordPress para importar

### CPT `familias` → tabela `familias`

| Campo WP | Campo Laravel | Tipo |
|---|---|---|
| `post_title` | `nome` | string |
| `post_name` | `slug` | string (unique) |
| `post_content` | `descricao` | text |
| `post_date` | `created_at` | timestamp |
| `_thumbnail_id` → URL | `imagem_url` | string |
| meta `link_download` | `link_download` | string |
| meta `_curtidas` | `curtidas` | integer |

### Taxonomias → tabela `categorias` (árvore)

| Taxonomia WP | Nível |
|---|---|
| `categorias` | 1 |
| `subcategorias` | 2 |
| `subclassificacao` | 3 |
| `selecionar-marca` | 4 (também é Fabricante) |

### Usuários WP → tabela `users`

| Campo WP | Campo Laravel |
|---|---|
| `user_login` | `name` |
| `user_email` | `email` |
| `user_pass` (hash WP) | requer reset de senha |
| role | `plan_id` (mapeado) |

---

## Ambiente (Sail / Docker)

O PHP local da máquina é 8.1 — **sempre rodar via Sail**. Portas alternativas (80/3306/5173 já estão em uso por outros projetos):

| Serviço | URL / porta |
|---|---|
| App | http://localhost:8090 |
| Vite (HMR) | 5174 |
| MySQL | 127.0.0.1:3307 (`sail` / `password`, banco `reviteiros`; testes usam `testing`) |
| Mailpit | http://localhost:8026 |

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed   # planos + dados demo (só em local)
./vendor/bin/sail npm run dev                     # ou: npm run build
./vendor/bin/sail artisan test
```

Usuários demo (senha `password`): `gratis@reviteiros.test`, `basic@reviteiros.test`, `premium@reviteiros.test`.

## Decisões de implementação

- **Livewire** em `app/Http/Livewire` (config `livewire.class_namespace`). Alpine vem do bundle ESM do Livewire (`resources/js/app.js`) — não instalar Alpine separado.
- **Layout** é componente anônimo: `resources/views/components/layouts/app.blade.php` (`<x-layouts.app title="...">`).
- **Taxonomias** (`taxonomias`, model `Taxonomia`): por `tipo_item`, com `slug` (parâmetro da URL), `pai_id` (só aparece após o pai), `ordem`, `ativa` e `fonte` (`termos` = lista própria em `categorias`; `fabricante` = tabela `fabricantes`). Slugs reservados: `Taxonomia::RESERVADOS`; `marca` só pode ser de fonte fabricante. Sempre consultar via `TaxonomiaService::doTipo()`. Para criar uma nova, use o painel (ou `Taxonomia::create` / `TaxonomiaSeeder` para as padrão).
- **Termos** (`categorias`, model `Categoria`): pertencem a uma taxonomia (`taxonomia_id`), slug único **dentro** da taxonomia (como no WP). Vínculo com o item pelo pivot `familia_categoria`; ao alterar use `FamiliaService::sincronizarCategorias()` (pivot não dispara eventos). As opções de uma taxonomia são calculadas por coocorrência nos itens que atendem a todos os outros filtros (`CategoriaService::opcoes`) — não dependem de `parent_id`.
- **Marca** = `Fabricante` (uma entidade só, compartilhada entre famílias e texturas; `familias.fabricante_id`). Não é termo: a taxonomia "Marca" tem `fonte = fabricante`. O filtro `marca=` vale em qualquer biblioteca (usado na tela `/fabricantes/{slug}`).
- **Barra "Filtrar por"** (grid): três dropdowns — **Recurso** (`?recurso=gratis,premium`; grátis = `familias.gratuita`, premium = itens pagos; os dois marcados = sem filtro; `?gratis=1` antigo ainda é aceito), **Fabricantes** (a taxonomia de fonte fabricante, `?marca=a,b` — várias marcas, resultado = OR entre elas; some na tela da marca, onde ela é fixa; não é limpa ao trocar a categoria) e **Avançado** (`?incluir=a,b&excluir=c`: palavras que o item precisa ter / não pode ter em `busca_texto`). A busca por texto e as linhas de chips das demais taxonomias continuam. Também valem na API (`recurso`, `incluir`, `excluir`).
- **Grid** (`FamiliaGrid`, serve as 3 bibliotecas e a tela do fabricante): filtros em `$filtros` (slug da taxonomia → valor); a URL é escrita pelo evento `rv-url` e lida em `resources/js/rv-historico.js` (voltar/avançar → `sincronizarUrl`). Ações de navegação devem ser **métodos** (`selecionar`, `remover`, `limpar`, `filtrarBiblioteca`, `alternarGratis`) — eles disparam o `rv-url`. Mudar um filtro limpa os que dependem dele (`TaxonomiaService::descendentes`).
- **Busca**: coluna desnormalizada `familias.busca_texto` (nome, descrição, tipo, fabricante, nomes de todos os termos) com FULLTEXT, mantida por `BuscaIndexService` + observers.
- **Tipos**: `familias.tipo` (cast `TipoItem`: `familia | textura | projeto`). A tabela/model continua `familias`/`Familia` e guarda os 3 tipos. URL pública: `$familia->urlPublica()` (nunca montar `/familias-revit/...` à mão). Segmento da URL em `TipoItem::segmento()`; nomes de rota estáveis (`familias.index`, `texturas.pagina`, `projetos.show`) via `TipoItem::chaveRota()`.
- **Paginação SEO**: URLs por `App\Support\UrlListagem::pagina()` (não usar o helper `url()`, que remove a barra final de `/page/3/`). O grid guarda `paginaInicial`/`paginaFinal`; o primeiro card de cada página leva `data-pagina-inicio`/`data-url`, lidos por `resources/js/rv-paginacao.js`. O `.htaccess` não remove mais a barra final (em nginx, idem: não redirecionar `/page/3/` → `/page/3`).
- **Download**: `link_download` é `#[Hidden]` no model e só sai por `DownloadService`. O HTML nunca contém o link — o botão faz POST em `/familias/{id}/download` (vale para os 3 tipos), que redireciona.
- **Cadastro**: todo usuário nasce no plano grátis; plano pago só é aplicado pelo webhook após o pagamento. Coluna é `users.plano_id`.
- **Testes**: usar `DatabaseTruncation` (não `RefreshDatabase`) — o FULLTEXT do InnoDB não enxerga linhas não commitadas.
- **Cache**: o Laravel 13 não desserializa objetos do cache; guardar arrays e reidratar.

## Idiomas (pt_BR padrão, en, fr, es)

Só o **texto do site** é traduzido (menus, botões, home, login/cadastro, mensagens). O conteúdo do catálogo (nome/descrição de famílias, texturas, projetos, termos e marcas) fica em português; o painel `/admin` também. Se o catálogo precisar de tradução no futuro: `spatie/laravel-translatable` + plugin do Filament (e `busca_texto` por idioma).

- **URL**: português sem prefixo; `/en`, `/fr`, `/es` para os demais (`config/idiomas.php`, `App\Support\Idiomas`). `DefinirIdioma` é middleware **global** (roda antes do roteamento): tira o prefixo do caminho, define o locale e usa o prefixo como raiz das URLs geradas (`URL::forceRootUrl`; assets via `useAssetOrigin`). As rotas continuam declaradas uma vez só, sem prefixo, e `route()`/`url()` já saem com `/en/…`. As atualizações do Livewire (`/en/livewire/update`) herdam o idioma.
- **Textos**: `__('Frase em português')` — a chave **é** a frase em português (pt_BR não precisa de arquivo). Traduções em `lang/en.json`, `lang/fr.json`, `lang/es.json` (com as mensagens de validação do `laravel-lang`). `fallback_locale = pt_BR`: frase sem tradução aparece em português. Placeholders: `__('Voltar a :biblioteca', [...])`. Conteúdo vindo de config/banco (FAQ, planos, nomes de taxonomias padrão) também passa por `__($var)` e as frases entram nos JSON.
- **Nova frase**: envolver em `__()` na view/PHP e adicionar a tradução nos 3 JSON. Não montar frases por concatenação.
- **SEO**: `<x-hreflang />` (alternate + x-default), canonical por idioma, `<html lang>` e `og:locale` via `Idiomas`. Seletor: `<x-seletor-idioma />` (cabeçalho e menu do celular); `Idiomas::urlEm($codigo)` troca só o prefixo e mantém `/page/N/` e a query.
- **Números**: `Idiomas::numero()` (5.422 / 5,422 / 5 422). O cache da home (`HomeService`) tem uma entrada por idioma (guarda URLs e números formatados).
- **Testes**: o helper `get('/…')` monta a URL com `url()`, que após uma requisição `/en` ainda carrega a raiz — nos testes use URL completa (`config('app.url')`) a partir da 2ª requisição (ver `IdiomasTest`).

## Favoritos

Usuário logado favorita famílias, texturas e projetos pelo coração (`<x-favorito>`, `resources/js/rv-favorito.js`) nos cards do grid e na página do item. Tabela `favoritos` (`user_id` + `familia_id`, único por par, cascade); `User::favoritos()`, `FavoritoService` (`alternar`, `dentre`). `POST /favoritos/{familia:id}` alterna (JSON; 401 → o botão abre o popup de cadastro) e `GET /favoritos` ("Meus favoritos", 48 por página, mais recentes primeiro, só itens ativos) exigem login. Visitante clicando no coração vê o popup de cadastro.

## Área do aluno/assinante (`/minha-conta`, exige login)

`ContaController` + views em `resources/views/conta/` (`<x-conta.layout>` com abas): **Meus dados** (só o nome é editável; e-mail só pelo suporte; senha pelo fluxo "esqueci minha senha"), **Plano e assinatura** (plano atual, preço/benefícios da config da home, upgrade, cancelamento, histórico de trocas de `plano_historicos`) e **Histórico de uso** (`UsoService`: downloads totais/por tipo/itens diferentes e lista paginada, filtro `?tipo=`; vem da tabela `downloads`).
- **Upgrade** = botão para o checkout do plano (`CadastroService::checkoutUrl`, Hotmart); o plano só muda quando o pagamento confirma (webhook) ou a equipe altera. Planos oferecidos: pagos acima do atual, sem o Plus.
- **Cancelamento**: a cobrança é no gateway, então o site só **registra o pedido** (`solicitacoes_cancelamento`, `CancelamentoService`; grava na Auditoria com `forcar: true`, evento `cancelamento_solicitado`). O plano **não muda** na hora: a equipe cancela no gateway e aplica o Grátis (`AssinaturaService::alterarPlano`), que dá o pedido como atendido. O assinante pode desistir enquanto estiver pendente.

## Painel administrativo (`/admin`, Filament 4)

Filament 4 (o 5 exige Livewire 4). Funcionários ficam na mesma tabela `users`, com `users.funcao` preenchida; cliente/aluno tem `funcao = null`.

| Perfil | Acesso |
|---|---|
| **Administrador** | Tudo: catálogo, usuários, funcionários, planos, auditoria |
| **Editor** | Catálogo + usuários/assinaturas. Não vê funcionários, planos nem auditoria; não exclui usuários |

Demo: `admin@reviteiros.test` / `editor@reviteiros.test` (senha `password`).

- **Recursos** em `app/Filament/Resources/*`: Famílias/Texturas/Projetos (campos de classificação gerados das taxonomias do tipo; upload ou link), Categorias (termos de todas as taxonomias, em abas por biblioteca), Marcas/Fabricantes, **Taxonomias** (só Administrador), Usuários/alunos, Funcionários, Planos, Auditoria. Dashboard em `app/Filament/Widgets`.
- **Closures do Filament**: os parâmetros são injetados **pelo nome** — use `$query`, `$record`, `$state`, `$data`, `$get`, `$set`, `$operation` (nunca `$q`/`$r`).
- **Rótulos**: `$hasTitleCaseModelLabel = false` em todo Resource (português em caixa de frase).
- **Plano**: só muda via `AssinaturaService::alterarPlano()` (grava `plano_historicos`). Sem vencimento: vale até um webhook ou funcionário trocar.
- **Conta**: `users.status` (`ativo`/`bloqueado`). Bloquear derruba a sessão (`EncerrarContaBloqueada`) e impede o login. Último login/IP em `RegistrarUltimoLogin`. Ações de atendimento em `ContaService` / `App\Filament\Actions\ContaActions`.
- **Arquivos**: o zip enviado vai para o disco `REVITEIROS_ARQUIVOS_DISK` (`local` = privado na VPS, ou `s3`) e `link_download` passa a apontar para `/arquivos/{path}`, rota que confere o plano antes de entregar (S3: URL temporária). Imagens/logos vão para o disco público (`storage:link`). Limite: `REVITEIROS_ARQUIVOS_MAX_MB` + `docker/php/uploads.ini` (em produção, ajustar PHP-FPM e o `client_max_body_size` do nginx).
- **Auditoria** (`AuditoriaService` + trait `Auditavel`): registra só ações de **funcionários logados**; senhas nunca são gravadas. Para importações em massa use `semRegistro()`; webhooks usam `registrar(..., forcar: true)` quando fizer sentido.
- **Downloads**: cada download vira uma linha em `downloads` + `familias.downloads_count` (métricas do dashboard).

## Comandos Úteis

```bash
# Rodar migrations (e as taxonomias padrão das 3 bibliotecas — também em produção)
php artisan migrate
php artisan db:seed --class=TaxonomiaSeeder

# Importar dados do WP (script customizado)
php artisan reviteiros:import-wp --file=wp-export.xml

# Gerar chave
php artisan key:generate

# Livewire publish
php artisan livewire:publish --config
```

---

## MCP Recomendado

Ver `.claude/mcp-config.json` para configuração sugerida de servidores MCP.

## Skills Disponíveis

Ver `.claude/skills/` para skills de Blade, migrations e testes.
