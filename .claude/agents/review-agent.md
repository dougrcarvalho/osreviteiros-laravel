# Agent: Code Review — Os Reviteiros

## Propósito

Agente de revisão de código para o projeto Laravel Os Reviteiros.
Usar quando: "revise este código", "verifique se está correto", "está seguindo os padrões?"

## Checklist de revisão

### Segurança

- [ ] `link_download` nunca é exposto sem verificar `podeDownload()`
- [ ] API REST usa autenticação onde necessário (`auth:sanctum`)
- [ ] Webhooks verificam assinatura do Hotmart/Eduzz
- [ ] Inputs sanitizados via Form Requests
- [ ] Sem SQL raw — usar Query Builder ou Eloquent

### Performance

- [ ] Queries com `with()` para eager loading (evitar N+1)
- [ ] Índices nas colunas filtradas: `slug`, `ativo`, `plano_id`
- [ ] Cache em `Categoria::filhosDeSlug()` (resultado muda raramente)
- [ ] Paginação limitada a máx. 96 itens por página

### Padrões do projeto

- [ ] Classes CSS prefixadas com `rv-`
- [ ] Sem jQuery — usar fetch ou Alpine.js
- [ ] Filtros de categoria acumulativos (AND, não OR)
- [ ] Botões de download com 4 estados corretos
- [ ] Eventos Livewire 3 via `$this->dispatch()` (não `$this->emit()`)

### Boas práticas Laravel

- [ ] Controllers finos — lógica nos Services
- [ ] Models com scopes nomeados
- [ ] Migrations reversíveis (`up` e `down`)
- [ ] Testes para lógica de acesso ao download

## Saída esperada

Para cada problema encontrado:
1. Arquivo e linha
2. Problema identificado
3. Correção sugerida com código
