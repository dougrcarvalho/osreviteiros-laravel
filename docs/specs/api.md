# Spec: API REST — Os Reviteiros

## Endpoint: GET /api/familias

Endpoint paginado para o infinite scroll da grade de famílias.

### Parâmetros

| Param | Tipo | Default | Descrição |
|---|---|---|---|
| `page` | int | 1 | Página atual |
| `per_page` | int | 48 | Itens por página (máx 96) |
| `cat` | string | — | Slug da categoria (nível 1) |
| `sub` | string | — | Slug da subcategoria (nível 2) |
| `subcl` | string | — | Slug da subclassificação (nível 3) |
| `marca` | string | — | Slug do fabricante/marca (nível 4) |
| `busca` | string | — | Texto para fulltext search |
| `orderby` | string | date | `date` \| `az` \| `curtidas` |

**Filtros são acumulativos** — todos os params ativos são aplicados com AND.

### Resposta

```json
{
  "data": [
    {
      "id": 123,
      "nome": "Cadeira Escritório Ergonômica",
      "slug": "cadeira-escritorio-ergonomica",
      "url": "https://osreviteiros.com.br/familias/cadeira-escritorio-ergonomica",
      "imagem": "https://osreviteiros.com.br/wp-content/uploads/2023/cadeira.jpg",
      "fabricante": "Herman Miller",
      "is_novo": true,
      "curtidas": 42,
      "is_gratuita": false,
      "is_logged": true,
      "has_plano": true,
      "link_download": "https://hotmart.com/download/arquivo.rfa"
    }
  ],
  "meta": {
    "total": 1850,
    "per_page": 48,
    "current_page": 1,
    "last_page": 39
  }
}
```

### Lógica de `link_download`

```
is_logged = false  → link_download: null
is_logged = true   → 
  has_plano = true → link_download: <url real>
  has_plano = false (grátis) →
    is_gratuita = true  → link_download: <url real>
    is_gratuita = false → link_download: null
```

---

## Endpoint: GET /api/familias/{slug}/categorias-filhas

Retorna subcategorias/subclassificações de uma categoria pai, filtradas pelas famílias que existem dado o contexto de filtros ativo.

### Parâmetros

| Param | Tipo | Descrição |
|---|---|---|
| `parent` | string | Slug da categoria pai |
| `filtros[]` | array | Slugs dos filtros ativos (para resultado acumulativo) |

### Resposta

```json
{
  "data": [
    { "id": 5, "nome": "Cadeiras", "slug": "cadeiras", "nivel": 2 },
    { "id": 6, "nome": "Mesas",    "slug": "mesas",    "nivel": 2 }
  ]
}
```

---

## Rota de Download (protegida)

```
POST /familias/{id}/download
Authorization: Bearer {token}   ou   sessão web autenticada
```

### Resposta — sucesso

```json
{ "url": "https://link-real-para-download.rfa" }
```

### Resposta — sem permissão

```json
{ "error": "sem_permissao", "message": "Assine um plano para baixar esta família." }
```

### Resposta — não logado

```json
{ "error": "nao_autenticado", "redirect": "/login" }
```

---

## Rotas no `routes/api.php`

```php
Route::get('/familias',                  [ApiFamiliaController::class, 'index']);
Route::get('/familias/{slug}/categorias',[ApiFamiliaController::class, 'categorias']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/familias/{id}/download', [DownloadController::class, 'download']);
});

Route::post('/webhooks/hotmart', [WebhookController::class, 'hotmart']);
Route::post('/webhooks/eduzz',   [WebhookController::class, 'eduzz']);
```
